/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.23-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: igangaschoolofl_students_db
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
-- Table structure for table `academic_registrar_activity_log`
--

DROP TABLE IF EXISTS `academic_registrar_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_registrar_activity_log` (
  `id` int(11) NOT NULL,
  `activity` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_registrar_activity_log`
--

LOCK TABLES `academic_registrar_activity_log` WRITE;
/*!40000 ALTER TABLE `academic_registrar_activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_registrar_activity_log` ENABLE KEYS */;
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
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `target_audience` enum('All','Nursing','Midwifery','Year1','Year2','Year3','Staff') DEFAULT 'All',
  `priority` enum('Normal','High','Urgent') DEFAULT 'Normal',
  `posted_by` int(11) DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (0,'Welcome to New Academic Year 2024/2025','We welcome all students and staff to the new academic year. Registration is now open for all programs. Please complete your registration before the deadline.','All','High',5,'2025-03-31',1,'2026-07-03 04:38:06'),(0,'Semester 1 Examination Schedule Released','The examination timetable for Semester 1 has been released. All students should check their examination dates and venues. Examinations begin on 10th December 2024.','All','High',7,'2025-01-15',1,'2026-07-03 04:38:06'),(0,'Clinical Placement Guidelines','All Diploma Year 2 and Year 3 students scheduled for clinical placements must attend the orientation session on Friday 15th November 2024. Bring your clinical gear.','','Normal',3,'2025-01-31',1,'2026-07-03 04:38:06'),(0,'Staff Training Workshop','All staff members are invited to a capacity building workshop on ICT Skills for Education on 20th November 2024. Attendance is mandatory.','Staff','Normal',23,'2025-01-15',1,'2026-07-03 04:38:06'),(0,'Fee Payment Deadline Reminder','Students with outstanding fees are reminded that the deadline for Semester 1 fee payment is 30th September 2024. Defaulters will not be allowed to sit for examinations.','','Urgent',25,'2024-10-31',1,'2026-07-03 04:38:06'),(0,'Library Hours Extended During Exams','The library will extend its operating hours during the examination period. The library will now be open from 7:00 AM to 9:00 PM on weekdays.','All','',10,'2025-01-15',1,'2026-07-03 04:38:06'),(0,'Health and Safety Protocols','All students and staff are reminded to follow the health and safety protocols at all times. Hand washing stations are available at all entry points.','All','Normal',5,'2025-06-30',1,'2026-07-03 04:38:06'),(0,'Sports Week Activities','The annual sports week will be held from 18th to 22nd November 2024. All students are encouraged to participate. Registration at the Guild Office.','','',21,'2025-01-31',1,'2026-07-03 04:38:06'),(0,'Nursing Council Registration Update','Final year students are reminded to complete their Nursing and Midwifery Council registration. The deadline has been extended to 31st January 2025.','','High',7,'2025-02-28',1,'2026-07-03 04:38:06'),(0,'Holiday Notice - Christmas Break','The institution will close for Christmas break on 20th December 2024 and reopen on 6th January 2025. Merry Christmas and Happy New Year!','All','',5,'2025-01-31',1,'2026-07-03 04:38:06'),(0,'Welcome to New Academic Year 2024/2025','We welcome all students and staff to the new academic year. Registration is now open for all programs. Please complete your registration before the deadline.','All','High',5,'2025-03-31',1,'2026-07-03 04:51:14'),(0,'Semester 1 Examination Schedule Released','The examination timetable for Semester 1 has been released. All students should check their examination dates and venues. Examinations begin on 10th December 2024.','All','High',7,'2025-01-15',1,'2026-07-03 04:51:14'),(0,'Clinical Placement Guidelines','All Diploma Year 2 and Year 3 students scheduled for clinical placements must attend the orientation session on Friday 15th November 2024. Bring your clinical gear.','','Normal',3,'2025-01-31',1,'2026-07-03 04:51:14'),(0,'Staff Training Workshop','All staff members are invited to a capacity building workshop on ICT Skills for Education on 20th November 2024. Attendance is mandatory.','Staff','Normal',23,'2025-01-15',1,'2026-07-03 04:51:14'),(0,'Fee Payment Deadline Reminder','Students with outstanding fees are reminded that the deadline for Semester 1 fee payment is 30th September 2024. Defaulters will not be allowed to sit for examinations.','','Urgent',25,'2024-10-31',1,'2026-07-03 04:51:14'),(0,'Library Hours Extended During Exams','The library will extend its operating hours during the examination period. The library will now be open from 7:00 AM to 9:00 PM on weekdays.','All','',10,'2025-01-15',1,'2026-07-03 04:51:14'),(0,'Health and Safety Protocols','All students and staff are reminded to follow the health and safety protocols at all times. Hand washing stations are available at all entry points.','All','Normal',5,'2025-06-30',1,'2026-07-03 04:51:14'),(0,'Sports Week Activities','The annual sports week will be held from 18th to 22nd November 2024. All students are encouraged to participate. Registration at the Guild Office.','','',21,'2025-01-31',1,'2026-07-03 04:51:14'),(0,'Nursing Council Registration Update','Final year students are reminded to complete their Nursing and Midwifery Council registration. The deadline has been extended to 31st January 2025.','','High',7,'2025-02-28',1,'2026-07-03 04:51:14'),(0,'Holiday Notice - Christmas Break','The institution will close for Christmas break on 20th December 2024 and reopen on 6th January 2025. Merry Christmas and Happy New Year!','All','',5,'2025-01-31',1,'2026-07-03 04:51:14');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
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
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_name` varchar(200) DEFAULT NULL,
  `visitor_phone` varchar(50) DEFAULT NULL,
  `visitor_email` varchar(100) DEFAULT NULL,
  `staff_id` int(11) DEFAULT 0,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('pending','approved','completed','cancelled') DEFAULT 'pending',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_categories`
--

DROP TABLE IF EXISTS `asset_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `depreciation_rate` decimal(5,2) DEFAULT 0.00,
  `useful_life_years` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_categories`
--

LOCK TABLES `asset_categories` WRITE;
/*!40000 ALTER TABLE `asset_categories` DISABLE KEYS */;
INSERT INTO `asset_categories` VALUES (0,'Furniture','Desks, chairs, tables, cabinets',0.00,NULL,'2026-07-03 04:33:38','2026-07-03 04:33:38'),(0,'Electronics','Computers, printers, projectors',0.00,NULL,'2026-07-03 04:33:38','2026-07-03 04:33:38'),(0,'Medical Equipment','Beds, monitors, diagnostic tools',0.00,NULL,'2026-07-03 04:33:38','2026-07-03 04:33:38'),(0,'Vehicles','School vehicles, ambulances',0.00,NULL,'2026-07-03 04:33:38','2026-07-03 04:33:38'),(0,'Buildings','School buildings and structures',0.00,NULL,'2026-07-03 04:33:38','2026-07-03 04:33:38'),(0,'Library','Books and library equipment',0.00,NULL,'2026-07-03 04:33:38','2026-07-03 04:33:38');
/*!40000 ALTER TABLE `asset_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_tag` varchar(50) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `current_value` decimal(12,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('Active','Disposed','Lost','Under Maintenance') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_findings`
--

DROP TABLE IF EXISTS `audit_findings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_findings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `finding_title` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `department` varchar(200) DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `reported_by` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_findings`
--

LOCK TABLES `audit_findings` WRITE;
/*!40000 ALTER TABLE `audit_findings` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_findings` ENABLE KEYS */;
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
-- Table structure for table `bank_transactions`
--

DROP TABLE IF EXISTS `bank_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_transactions` (
  `id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` varchar(255) DEFAULT '',
  `reference` varchar(100) DEFAULT '',
  `debit` decimal(12,2) DEFAULT 0.00,
  `credit` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `reconciled` tinyint(1) DEFAULT 0,
  `reconciled_by` int(11) DEFAULT 0,
  `reconciled_at` datetime DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_transactions`
--

LOCK TABLES `bank_transactions` WRITE;
/*!40000 ALTER TABLE `bank_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budget_approvals`
--

DROP TABLE IF EXISTS `budget_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) DEFAULT 0,
  `request_type` varchar(50) DEFAULT NULL,
  `requested_by` int(11) DEFAULT 0,
  `amount` decimal(14,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
  `approver_id` int(11) DEFAULT 0,
  `approver_name` varchar(200) DEFAULT NULL,
  `approver_comments` text DEFAULT NULL,
  `escalated_to` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_approvals`
--

LOCK TABLES `budget_approvals` WRITE;
/*!40000 ALTER TABLE `budget_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `budget_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budget_records`
--

DROP TABLE IF EXISTS `budget_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_records` (
  `id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL,
  `budget_item` varchar(255) NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL,
  `spent_amount` decimal(12,2) DEFAULT 0.00,
  `remaining_amount` decimal(12,2) GENERATED ALWAYS AS (`allocated_amount` - `spent_amount`) STORED,
  `status` enum('Active','Exhausted','Cancelled') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_records`
--

LOCK TABLES `budget_records` WRITE;
/*!40000 ALTER TABLE `budget_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `budget_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `budget_name` varchar(255) NOT NULL,
  `fiscal_year` varchar(20) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Draft','Approved','Active','Closed') DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgets`
--

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_chart_of_accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_chart_of_accounts`
--

LOCK TABLES `bursar_chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `bursar_chart_of_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_general_ledger`
--

DROP TABLE IF EXISTS `bursar_general_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_general_ledger` (
  `id` int(11) NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `account_id` int(11) DEFAULT 0,
  `cost_center_id` int(11) DEFAULT 0,
  `transaction_type` enum('Debit','Credit') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(50) DEFAULT '',
  `reference_id` varchar(50) DEFAULT '',
  `description` text DEFAULT NULL,
  `entry_date` date DEFAULT curdate(),
  `posted_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_general_ledger`
--

LOCK TABLES `bursar_general_ledger` WRITE;
/*!40000 ALTER TABLE `bursar_general_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_general_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_tax_filings`
--

DROP TABLE IF EXISTS `bursar_tax_filings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_tax_filings` (
  `id` int(11) NOT NULL,
  `tax_period_id` int(11) NOT NULL,
  `filing_date` date DEFAULT curdate(),
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `total_tax` decimal(12,2) DEFAULT 0.00,
  `filing_reference` varchar(100) DEFAULT '',
  `status` enum('Draft','Filed','Amended') DEFAULT 'Draft',
  `filed_by` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `id` int(11) NOT NULL,
  `period_name` varchar(100) NOT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Open','Closed','Filed') DEFAULT 'Open',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_tax_periods`
--

LOCK TABLES `bursar_tax_periods` WRITE;
/*!40000 ALTER TABLE `bursar_tax_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_tax_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_users`
--

DROP TABLE IF EXISTS `bursar_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('bursar','accounts_assistant','auditor') DEFAULT 'bursar',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_users`
--

LOCK TABLES `bursar_users` WRITE;
/*!40000 ALTER TABLE `bursar_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capital_projects`
--

DROP TABLE IF EXISTS `capital_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `capital_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_name` varchar(300) DEFAULT NULL,
  `project_code` varchar(100) DEFAULT NULL,
  `budget` decimal(14,2) DEFAULT 0.00,
  `spent` decimal(14,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planning','active','completed','cancelled') DEFAULT 'planning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capital_projects`
--

LOCK TABLES `capital_projects` WRITE;
/*!40000 ALTER TABLE `capital_projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `capital_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_book`
--

DROP TABLE IF EXISTS `cash_book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_book` (
  `id` int(11) NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `entry_type` enum('Receipt','Payment') NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `related_student_id` int(11) DEFAULT NULL,
  `transaction_date` date DEFAULT curdate(),
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashbook`
--

LOCK TABLES `cashbook` WRITE;
/*!40000 ALTER TABLE `cashbook` DISABLE KEYS */;
/*!40000 ALTER TABLE `cashbook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chart_of_accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
INSERT INTO `chart_of_accounts` VALUES (1,'1000','Cash and Cash Equivalents','Asset',NULL,'Cash on hand and in bank',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(2,'1100','Accounts Receivable','Asset',NULL,'Student fees receivable',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(3,'1200','Inventory','Asset',NULL,'Supplies and inventory',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(4,'1500','Fixed Assets','Asset',NULL,'Property, plant and equipment',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(5,'2000','Accounts Payable','Liability',NULL,'Amounts owed to suppliers',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(6,'2100','Accrued Liabilities','Liability',NULL,'Accrued expenses',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(7,'3000','Net Assets','Equity',NULL,'Institution net worth',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(8,'4000','Tuition Revenue','Revenue',NULL,'Income from student tuition',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(9,'4100','Registration Revenue','Revenue',NULL,'Income from student registration',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(10,'4200','Other Revenue','Revenue',NULL,'Miscellaneous income',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(11,'5000','Salary Expenses','Expense',NULL,'Staff salaries and wages',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(12,'5100','Administrative Expenses','Expense',NULL,'Office and administrative costs',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(13,'5200','Operational Expenses','Expense',NULL,'Day-to-day operational costs',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(14,'5300','Maintenance Expenses','Expense',NULL,'Facility maintenance costs',1,'2026-06-14 19:51:20','2026-06-14 19:51:20');
/*!40000 ALTER TABLE `chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `circulars`
--

DROP TABLE IF EXISTS `circulars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `circulars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(300) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `issued_by` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `file_name` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','issued','archived') DEFAULT 'issued',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `circulars`
--

LOCK TABLES `circulars` WRITE;
/*!40000 ALTER TABLE `circulars` DISABLE KEYS */;
/*!40000 ALTER TABLE `circulars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_placements`
--

DROP TABLE IF EXISTS `clinical_placements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_placements` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `facility_name` varchar(255) NOT NULL,
  `facility_location` varchar(255) DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `hours_completed` int(11) DEFAULT 0,
  `skills_assessment` text DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_placements`
--

LOCK TABLES `clinical_placements` WRITE;
/*!40000 ALTER TABLE `clinical_placements` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinical_placements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_placements_students`
--

DROP TABLE IF EXISTS `clinical_placements_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_placements_students` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `placement_site` varchar(200) NOT NULL,
  `supervisor_name` varchar(150) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `competency_score` decimal(5,2) DEFAULT NULL,
  `logbook_submitted` tinyint(1) DEFAULT 0,
  `supervisor_evaluation` text DEFAULT NULL,
  `status` enum('Scheduled','Active','Completed','Withdrawn') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_placements_students`
--

LOCK TABLES `clinical_placements_students` WRITE;
/*!40000 ALTER TABLE `clinical_placements_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinical_placements_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_sites`
--

DROP TABLE IF EXISTS `clinical_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(200) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `capacity` int(11) DEFAULT 20,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_sites`
--

LOCK TABLES `clinical_sites` WRITE;
/*!40000 ALTER TABLE `clinical_sites` DISABLE KEYS */;
INSERT INTO `clinical_sites` VALUES (1,'Iganga Regional Referral Hospital','Iganga Town',30,'Dr. Wasswa Moses','+256-772-123456','Active','2026-07-03 04:51:14'),(2,'Iganga Health Centre IV','Iganga Municipality',20,'Sr. Namukasa Florence','+256-782-234567','Active','2026-07-03 04:51:14'),(3,'Bugiri District Hospital','Bugiri Town',25,'Dr. Ochieng James','+256-702-345678','Active','2026-07-03 04:51:14'),(4,'Namutumba Health Centre III','Namutumba',15,'Sr. Nabirye Sarah','+256-772-456789','Active','2026-07-03 04:51:14'),(5,'Kaliro Health Centre III','Kaliro Town',15,'Mr. Wamboga John','+256-782-567890','Active','2026-07-03 04:51:14'),(6,'Mayuge Health Centre III','Mayuge District',12,'Dr. Mugisha Patrick','+256-702-678901','Active','2026-07-03 04:51:14'),(7,'Busolwe Hospital','Butaleja District',20,'Sr. Ajok Betty','+256-772-789012','Active','2026-07-03 04:51:14'),(8,'Kamuli District Hospital','Kamuli Town',25,'Dr. Ssemwanga Robert','+256-782-890123','Active','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `clinical_sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `committee_actions`
--

DROP TABLE IF EXISTS `committee_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `committee_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `responsible` varchar(200) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `committee_actions`
--

LOCK TABLES `committee_actions` WRITE;
/*!40000 ALTER TABLE `committee_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `committee_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_log`
--

DROP TABLE IF EXISTS `communication_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(200) DEFAULT NULL,
  `recipient_role` varchar(100) DEFAULT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_log`
--

LOCK TABLES `communication_log` WRITE;
/*!40000 ALTER TABLE `communication_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_log` ENABLE KEYS */;
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
-- Table structure for table `compliance_alerts`
--

DROP TABLE IF EXISTS `compliance_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_title` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `compliance_type` enum('financial','ura','regulatory') DEFAULT 'financial',
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('open','acknowledged','resolved') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_alerts`
--

LOCK TABLES `compliance_alerts` WRITE;
/*!40000 ALTER TABLE `compliance_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_tracking`
--

DROP TABLE IF EXISTS `compliance_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(200) DEFAULT NULL,
  `compliance_type` varchar(200) DEFAULT NULL,
  `status` enum('compliant','non_compliant','pending_review') DEFAULT 'pending_review',
  `notes` text DEFAULT NULL,
  `reviewed_by` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_tracking`
--

LOCK TABLES `compliance_tracking` WRITE;
/*!40000 ALTER TABLE `compliance_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_directory`
--

DROP TABLE IF EXISTS `contact_directory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_directory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(200) DEFAULT NULL,
  `organization` varchar(200) DEFAULT NULL,
  `position` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_directory`
--

LOCK TABLES `contact_directory` WRITE;
/*!40000 ALTER TABLE `contact_directory` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_directory` ENABLE KEYS */;
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
-- Table structure for table `correspondence`
--

DROP TABLE IF EXISTS `correspondence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `correspondence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('incoming','outgoing') DEFAULT 'incoming',
  `reference` varchar(100) DEFAULT NULL,
  `sender_name` varchar(200) DEFAULT NULL,
  `recipient_name` varchar(200) DEFAULT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `date_sent` date DEFAULT NULL,
  `file_name` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','actioned','closed','archived') DEFAULT 'pending',
  `handled_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `correspondence`
--

LOCK TABLES `correspondence` WRITE;
/*!40000 ALTER TABLE `correspondence` DISABLE KEYS */;
/*!40000 ALTER TABLE `correspondence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cost_centers`
--

DROP TABLE IF EXISTS `cost_centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cost_centers` (
  `id` int(11) NOT NULL,
  `cost_center_code` varchar(20) NOT NULL,
  `cost_center_name` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cost_centers`
--

LOCK TABLES `cost_centers` WRITE;
/*!40000 ALTER TABLE `cost_centers` DISABLE KEYS */;
INSERT INTO `cost_centers` VALUES (1,'CC-EXEC','Executive Office','Executive Office',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(2,'CC-NUR','Nursing Department','Nursing Department',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(3,'CC-MID','Midwifery Department','Midwifery Department',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(4,'CC-ACAD','Academic Affairs','Academic Affairs',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(5,'CC-FIN','Finance Department','Finance Department',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(6,'CC-HR','Human Resources','Human Resources',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(7,'CC-LIB','Library Services','Library Services',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(8,'CC-STU','Student Affairs','Student Affairs',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(9,'CC-SEC','Security Services','Security Services',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(10,'CC-ICT','Information Technology','Information Technology',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(11,'CC-FAC','Facilities Management','Facilities Management',NULL,1,'2026-06-14 19:51:20','2026-06-14 19:51:20');
/*!40000 ALTER TABLE `cost_centers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_assignments`
--

DROP TABLE IF EXISTS `course_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive','Completed') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_lecturer` (`lecturer_id`),
  KEY `idx_year_sem` (`academic_year`,`semester`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_assignments`
--

LOCK TABLES `course_assignments` WRITE;
/*!40000 ALTER TABLE `course_assignments` DISABLE KEYS */;
INSERT INTO `course_assignments` VALUES (1,1,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(2,2,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(3,3,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(4,7,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(5,8,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(6,9,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(7,11,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(8,12,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(9,13,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(10,17,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(11,18,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(12,20,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(13,21,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(14,22,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(15,26,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(16,27,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(17,28,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(18,31,14,'2024/2025','Semester 5',3,'Active','2026-07-03 04:51:14'),(19,32,14,'2024/2025','Semester 5',3,'Active','2026-07-03 04:51:14'),(20,33,14,'2024/2025','Semester 5',3,'Active','2026-07-03 04:51:14'),(21,34,14,'2024/2025','Semester 5',3,'Active','2026-07-03 04:51:14'),(22,37,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(23,38,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(24,39,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(25,40,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(26,41,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14'),(27,42,14,'2024/2025','Semester 5',3,'Active','2026-07-03 04:51:14'),(28,43,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(29,44,14,'2024/2025','Semester 1',3,'Active','2026-07-03 04:51:14'),(30,45,14,'2024/2025','Semester 3',3,'Active','2026-07-03 04:51:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_catalog`
--

LOCK TABLES `course_catalog` WRITE;
/*!40000 ALTER TABLE `course_catalog` DISABLE KEYS */;
INSERT INTO `course_catalog` VALUES (1,'CNN101','Fundamentals of Nursing I','Certificate in Nursing','Certificate','Semester 1',4,1,'Active','2026-07-03 03:56:26'),(2,'CNN102','Anatomy & Physiology I','Certificate in Nursing','Certificate','Semester 1',3,1,'Active','2026-07-03 03:56:26'),(3,'CNN103','Community Health Nursing I','Certificate in Nursing','Certificate','Semester 1',3,1,'Active','2026-07-03 03:56:26'),(4,'CNN104','Medical Surgical Nursing I','Certificate in Nursing','Certificate','Semester 2',4,1,'Active','2026-07-03 03:56:26'),(5,'CNN105','Anatomy & Physiology II','Certificate in Nursing','Certificate','Semester 2',3,1,'Active','2026-07-03 03:56:26'),(6,'CNN106','Pharmacology I','Certificate in Nursing','Certificate','Semester 2',3,1,'Active','2026-07-03 03:56:26'),(7,'CNN201','Fundamentals of Nursing II','Certificate in Nursing','Certificate','Semester 3',4,1,'Active','2026-07-03 03:56:26'),(8,'CNN202','Psychiatric Nursing','Certificate in Nursing','Certificate','Semester 3',3,1,'Active','2026-07-03 03:56:26'),(9,'CNN203','Pediatric Nursing','Certificate in Nursing','Certificate','Semester 3',3,1,'Active','2026-07-03 03:56:26'),(10,'CNN204','Community Health Nursing II','Certificate in Nursing','Certificate','Semester 4',4,1,'Active','2026-07-03 03:56:26'),(11,'CNM101','Introduction to Midwifery','Certificate in Midwifery','Certificate','Semester 1',4,1,'Active','2026-07-03 03:56:26'),(12,'CNM102','Anatomy for Midwives','Certificate in Midwifery','Certificate','Semester 1',3,1,'Active','2026-07-03 03:56:26'),(13,'CNM103','Fundamentals of Midwifery Care','Certificate in Midwifery','Certificate','Semester 1',4,1,'Active','2026-07-03 03:56:26'),(14,'CNM104','Antenatal Care','Certificate in Midwifery','Certificate','Semester 2',4,1,'Active','2026-07-03 03:56:26'),(15,'CNM105','Labour & Delivery Management','Certificate in Midwifery','Certificate','Semester 2',5,1,'Active','2026-07-03 03:56:26'),(16,'CNM106','Postnatal Care','Certificate in Midwifery','Certificate','Semester 2',3,1,'Active','2026-07-03 03:56:26'),(17,'CNM201','Emergency Midwifery','Certificate in Midwifery','Certificate','Semester 3',4,1,'Active','2026-07-03 03:56:26'),(18,'CNM202','Neonatal Care','Certificate in Midwifery','Certificate','Semester 3',3,1,'Active','2026-07-03 03:56:26'),(19,'CNM203','Community Midwifery','Certificate in Midwifery','Certificate','Semester 4',4,1,'Active','2026-07-03 03:56:26'),(20,'DNM101','Nursing Science I','Diploma in Nursing','Diploma','Semester 1',4,1,'Active','2026-07-03 03:56:26'),(21,'DNM102','Human Anatomy & Physiology I','Diploma in Nursing','Diploma','Semester 1',3,1,'Active','2026-07-03 03:56:26'),(22,'DNM103','Nutrition & Dietetics','Diploma in Nursing','Diploma','Semester 1',3,1,'Active','2026-07-03 03:56:26'),(23,'DNM104','Medical Surgical Nursing I','Diploma in Nursing','Diploma','Semester 2',5,1,'Active','2026-07-03 03:56:26'),(24,'DNM105','Pharmacology I','Diploma in Nursing','Diploma','Semester 2',3,1,'Active','2026-07-03 03:56:26'),(25,'DNM106','Pathology & Microbiology','Diploma in Nursing','Diploma','Semester 2',3,1,'Active','2026-07-03 03:56:26'),(26,'DNM201','Medical Surgical Nursing II','Diploma in Nursing','Diploma','Semester 3',5,1,'Active','2026-07-03 03:56:26'),(27,'DNM202','Pediatric Nursing','Diploma in Nursing','Diploma','Semester 3',4,1,'Active','2026-07-03 03:56:26'),(28,'DNM203','Psychiatric Nursing','Diploma in Nursing','Diploma','Semester 3',3,1,'Active','2026-07-03 03:56:26'),(29,'DNM204','Community Health Nursing I','Diploma in Nursing','Diploma','Semester 4',4,1,'Active','2026-07-03 03:56:26'),(30,'DNM205','Nursing Research','Diploma in Nursing','Diploma','Semester 4',3,0,'Active','2026-07-03 03:56:26'),(31,'DNM301','Medical Surgical Nursing III','Diploma in Nursing','Diploma','Semester 5',5,1,'Active','2026-07-03 03:56:26'),(32,'DNM302','Community Health Nursing II','Diploma in Nursing','Diploma','Semester 5',4,1,'Active','2026-07-03 03:56:26'),(33,'DNM303','Nursing Management & Leadership','Diploma in Nursing','Diploma','Semester 5',4,1,'Active','2026-07-03 03:56:26'),(34,'DNM304','Clinical Practicum I','Diploma in Nursing','Diploma','Semester 5',6,1,'Active','2026-07-03 03:56:26'),(35,'DNM305','Final Clinical Practicum','Diploma in Nursing','Diploma','Semester 6',8,1,'Active','2026-07-03 03:56:26'),(36,'DNM306','Nursing Ethics & Legal Issues','Diploma in Nursing','Diploma','Semester 6',3,1,'Active','2026-07-03 03:56:26'),(37,'DMM101','Midwifery Science I','Diploma in Midwifery','Diploma','Semester 1',4,1,'Active','2026-07-03 03:56:26'),(38,'DMM102','Anatomy for Midwives','Diploma in Midwifery','Diploma','Semester 1',3,1,'Active','2026-07-03 03:56:26'),(39,'DMM103','Reproductive Health','Diploma in Midwifery','Diploma','Semester 1',3,1,'Active','2026-07-03 03:56:26'),(40,'DMM201','Advanced Midwifery Practice','Diploma in Midwifery','Diploma','Semester 3',5,1,'Active','2026-07-03 03:56:26'),(41,'DMM202','Maternal Health','Diploma in Midwifery','Diploma','Semester 3',4,1,'Active','2026-07-03 03:56:26'),(42,'DMM301','Midwifery Clinical Practicum','Diploma in Midwifery','Diploma','Semester 5',8,1,'Active','2026-07-03 03:56:26'),(43,'DNE101','Foundations of Education','Diploma in Nursing Education','Diploma','Semester 1',3,1,'Active','2026-07-03 03:56:26'),(44,'DNE102','Educational Psychology','Diploma in Nursing Education','Diploma','Semester 1',3,1,'Active','2026-07-03 03:56:26'),(45,'DNE201','Curriculum Development','Diploma in Nursing Education','Diploma','Semester 3',4,1,'Active','2026-07-03 03:56:26'),(46,'DNE202','Teaching Methods in Nursing','Diploma in Nursing Education','Diploma','Semester 3',4,1,'Active','2026-07-03 03:56:26'),(47,'DNE301','Practice Teaching','Diploma in Nursing Education','Diploma','Semester 5',6,1,'Active','2026-07-03 03:56:26'),(48,'CNN101','Fundamentals of Nursing I','Certificate in Nursing','Certificate','Semester 1',4,1,'Active','2026-07-03 04:05:12'),(49,'CNN102','Anatomy & Physiology I','Certificate in Nursing','Certificate','Semester 1',3,1,'Active','2026-07-03 04:05:12'),(50,'CNN103','Community Health Nursing I','Certificate in Nursing','Certificate','Semester 1',3,1,'Active','2026-07-03 04:05:12'),(51,'CNN104','Medical Surgical Nursing I','Certificate in Nursing','Certificate','Semester 2',4,1,'Active','2026-07-03 04:05:12'),(52,'CNN105','Anatomy & Physiology II','Certificate in Nursing','Certificate','Semester 2',3,1,'Active','2026-07-03 04:05:12'),(53,'CNN106','Pharmacology I','Certificate in Nursing','Certificate','Semester 2',3,1,'Active','2026-07-03 04:05:12'),(54,'CNN201','Fundamentals of Nursing II','Certificate in Nursing','Certificate','Semester 3',4,1,'Active','2026-07-03 04:05:12'),(55,'CNN202','Psychiatric Nursing','Certificate in Nursing','Certificate','Semester 3',3,1,'Active','2026-07-03 04:05:12'),(56,'CNN203','Pediatric Nursing','Certificate in Nursing','Certificate','Semester 3',3,1,'Active','2026-07-03 04:05:12'),(57,'CNN204','Community Health Nursing II','Certificate in Nursing','Certificate','Semester 4',4,1,'Active','2026-07-03 04:05:12'),(58,'CNM101','Introduction to Midwifery','Certificate in Midwifery','Certificate','Semester 1',4,1,'Active','2026-07-03 04:05:12'),(59,'CNM102','Anatomy for Midwives','Certificate in Midwifery','Certificate','Semester 1',3,1,'Active','2026-07-03 04:05:12'),(60,'CNM103','Fundamentals of Midwifery Care','Certificate in Midwifery','Certificate','Semester 1',4,1,'Active','2026-07-03 04:05:12'),(61,'CNM104','Antenatal Care','Certificate in Midwifery','Certificate','Semester 2',4,1,'Active','2026-07-03 04:05:12'),(62,'CNM105','Labour & Delivery Management','Certificate in Midwifery','Certificate','Semester 2',5,1,'Active','2026-07-03 04:05:12'),(63,'CNM106','Postnatal Care','Certificate in Midwifery','Certificate','Semester 2',3,1,'Active','2026-07-03 04:05:12'),(64,'CNM201','Emergency Midwifery','Certificate in Midwifery','Certificate','Semester 3',4,1,'Active','2026-07-03 04:05:12'),(65,'CNM202','Neonatal Care','Certificate in Midwifery','Certificate','Semester 3',3,1,'Active','2026-07-03 04:05:12'),(66,'CNM203','Community Midwifery','Certificate in Midwifery','Certificate','Semester 4',4,1,'Active','2026-07-03 04:05:12'),(67,'DNM101','Nursing Science I','Diploma in Nursing','Diploma','Semester 1',4,1,'Active','2026-07-03 04:05:12'),(68,'DNM102','Human Anatomy & Physiology I','Diploma in Nursing','Diploma','Semester 1',3,1,'Active','2026-07-03 04:05:12'),(69,'DNM103','Nutrition & Dietetics','Diploma in Nursing','Diploma','Semester 1',3,1,'Active','2026-07-03 04:05:12'),(70,'DNM104','Medical Surgical Nursing I','Diploma in Nursing','Diploma','Semester 2',5,1,'Active','2026-07-03 04:05:12'),(71,'DNM105','Pharmacology I','Diploma in Nursing','Diploma','Semester 2',3,1,'Active','2026-07-03 04:05:12'),(72,'DNM106','Pathology & Microbiology','Diploma in Nursing','Diploma','Semester 2',3,1,'Active','2026-07-03 04:05:12'),(73,'DNM201','Medical Surgical Nursing II','Diploma in Nursing','Diploma','Semester 3',5,1,'Active','2026-07-03 04:05:12'),(74,'DNM202','Pediatric Nursing','Diploma in Nursing','Diploma','Semester 3',4,1,'Active','2026-07-03 04:05:12'),(75,'DNM203','Psychiatric Nursing','Diploma in Nursing','Diploma','Semester 3',3,1,'Active','2026-07-03 04:05:12'),(76,'DNM204','Community Health Nursing I','Diploma in Nursing','Diploma','Semester 4',4,1,'Active','2026-07-03 04:05:12'),(77,'DNM205','Nursing Research','Diploma in Nursing','Diploma','Semester 4',3,0,'Active','2026-07-03 04:05:12'),(78,'DNM301','Medical Surgical Nursing III','Diploma in Nursing','Diploma','Semester 5',5,1,'Active','2026-07-03 04:05:12'),(79,'DNM302','Community Health Nursing II','Diploma in Nursing','Diploma','Semester 5',4,1,'Active','2026-07-03 04:05:12'),(80,'DNM303','Nursing Management & Leadership','Diploma in Nursing','Diploma','Semester 5',4,1,'Active','2026-07-03 04:05:12'),(81,'DNM304','Clinical Practicum I','Diploma in Nursing','Diploma','Semester 5',6,1,'Active','2026-07-03 04:05:12'),(82,'DNM305','Final Clinical Practicum','Diploma in Nursing','Diploma','Semester 6',8,1,'Active','2026-07-03 04:05:12'),(83,'DNM306','Nursing Ethics & Legal Issues','Diploma in Nursing','Diploma','Semester 6',3,1,'Active','2026-07-03 04:05:12'),(84,'DMM101','Midwifery Science I','Diploma in Midwifery','Diploma','Semester 1',4,1,'Active','2026-07-03 04:05:12'),(85,'DMM102','Anatomy for Midwives','Diploma in Midwifery','Diploma','Semester 1',3,1,'Active','2026-07-03 04:05:12'),(86,'DMM103','Reproductive Health','Diploma in Midwifery','Diploma','Semester 1',3,1,'Active','2026-07-03 04:05:12'),(87,'DMM201','Advanced Midwifery Practice','Diploma in Midwifery','Diploma','Semester 3',5,1,'Active','2026-07-03 04:05:12'),(88,'DMM202','Maternal Health','Diploma in Midwifery','Diploma','Semester 3',4,1,'Active','2026-07-03 04:05:12'),(89,'DMM301','Midwifery Clinical Practicum','Diploma in Midwifery','Diploma','Semester 5',8,1,'Active','2026-07-03 04:05:12'),(90,'DNE101','Foundations of Education','Diploma in Nursing Education','Diploma','Semester 1',3,1,'Active','2026-07-03 04:05:12'),(91,'DNE102','Educational Psychology','Diploma in Nursing Education','Diploma','Semester 1',3,1,'Active','2026-07-03 04:05:12'),(92,'DNE201','Curriculum Development','Diploma in Nursing Education','Diploma','Semester 3',4,1,'Active','2026-07-03 04:05:12'),(93,'DNE202','Teaching Methods in Nursing','Diploma in Nursing Education','Diploma','Semester 3',4,1,'Active','2026-07-03 04:05:12'),(94,'DNE301','Practice Teaching','Diploma in Nursing Education','Diploma','Semester 5',6,1,'Active','2026-07-03 04:05:12'),(95,'CNN101','Fundamentals of Nursing I','Certificate in Nursing','Certificate','Semester 1',4,1,'Active','2026-07-03 04:38:06'),(96,'CNN102','Anatomy & Physiology I','Certificate in Nursing','Certificate','Semester 1',3,1,'Active','2026-07-03 04:38:06'),(97,'CNN103','Community Health Nursing I','Certificate in Nursing','Certificate','Semester 1',3,1,'Active','2026-07-03 04:38:06'),(98,'CNN104','Medical Surgical Nursing I','Certificate in Nursing','Certificate','Semester 2',4,1,'Active','2026-07-03 04:38:06'),(99,'CNN105','Anatomy & Physiology II','Certificate in Nursing','Certificate','Semester 2',3,1,'Active','2026-07-03 04:38:06'),(100,'CNN106','Pharmacology I','Certificate in Nursing','Certificate','Semester 2',3,1,'Active','2026-07-03 04:38:06'),(101,'CNN201','Fundamentals of Nursing II','Certificate in Nursing','Certificate','Semester 3',4,1,'Active','2026-07-03 04:38:06'),(102,'CNN202','Psychiatric Nursing','Certificate in Nursing','Certificate','Semester 3',3,1,'Active','2026-07-03 04:38:06'),(103,'CNN203','Pediatric Nursing','Certificate in Nursing','Certificate','Semester 3',3,1,'Active','2026-07-03 04:38:06'),(104,'CNN204','Community Health Nursing II','Certificate in Nursing','Certificate','Semester 4',4,1,'Active','2026-07-03 04:38:06'),(105,'CNM101','Introduction to Midwifery','Certificate in Midwifery','Certificate','Semester 1',4,1,'Active','2026-07-03 04:38:06'),(106,'CNM102','Anatomy for Midwives','Certificate in Midwifery','Certificate','Semester 1',3,1,'Active','2026-07-03 04:38:06'),(107,'CNM103','Fundamentals of Midwifery Care','Certificate in Midwifery','Certificate','Semester 1',4,1,'Active','2026-07-03 04:38:06'),(108,'CNM104','Antenatal Care','Certificate in Midwifery','Certificate','Semester 2',4,1,'Active','2026-07-03 04:38:06'),(109,'CNM105','Labour & Delivery Management','Certificate in Midwifery','Certificate','Semester 2',5,1,'Active','2026-07-03 04:38:06'),(110,'CNM106','Postnatal Care','Certificate in Midwifery','Certificate','Semester 2',3,1,'Active','2026-07-03 04:38:06'),(111,'CNM201','Emergency Midwifery','Certificate in Midwifery','Certificate','Semester 3',4,1,'Active','2026-07-03 04:38:06'),(112,'CNM202','Neonatal Care','Certificate in Midwifery','Certificate','Semester 3',3,1,'Active','2026-07-03 04:38:06'),(113,'CNM203','Community Midwifery','Certificate in Midwifery','Certificate','Semester 4',4,1,'Active','2026-07-03 04:38:06'),(114,'DNM101','Nursing Science I','Diploma in Nursing','Diploma','Semester 1',4,1,'Active','2026-07-03 04:38:06'),(115,'DNM102','Human Anatomy & Physiology I','Diploma in Nursing','Diploma','Semester 1',3,1,'Active','2026-07-03 04:38:06'),(116,'DNM103','Nutrition & Dietetics','Diploma in Nursing','Diploma','Semester 1',3,1,'Active','2026-07-03 04:38:06'),(117,'DNM104','Medical Surgical Nursing I','Diploma in Nursing','Diploma','Semester 2',5,1,'Active','2026-07-03 04:38:06'),(118,'DNM105','Pharmacology I','Diploma in Nursing','Diploma','Semester 2',3,1,'Active','2026-07-03 04:38:06'),(119,'DNM106','Pathology & Microbiology','Diploma in Nursing','Diploma','Semester 2',3,1,'Active','2026-07-03 04:38:06'),(120,'DNM201','Medical Surgical Nursing II','Diploma in Nursing','Diploma','Semester 3',5,1,'Active','2026-07-03 04:38:06'),(121,'DNM202','Pediatric Nursing','Diploma in Nursing','Diploma','Semester 3',4,1,'Active','2026-07-03 04:38:06'),(122,'DNM203','Psychiatric Nursing','Diploma in Nursing','Diploma','Semester 3',3,1,'Active','2026-07-03 04:38:06'),(123,'DNM204','Community Health Nursing I','Diploma in Nursing','Diploma','Semester 4',4,1,'Active','2026-07-03 04:38:06'),(124,'DNM205','Nursing Research','Diploma in Nursing','Diploma','Semester 4',3,0,'Active','2026-07-03 04:38:06'),(125,'DNM301','Medical Surgical Nursing III','Diploma in Nursing','Diploma','Semester 5',5,1,'Active','2026-07-03 04:38:06'),(126,'DNM302','Community Health Nursing II','Diploma in Nursing','Diploma','Semester 5',4,1,'Active','2026-07-03 04:38:06'),(127,'DNM303','Nursing Management & Leadership','Diploma in Nursing','Diploma','Semester 5',4,1,'Active','2026-07-03 04:38:06'),(128,'DNM304','Clinical Practicum I','Diploma in Nursing','Diploma','Semester 5',6,1,'Active','2026-07-03 04:38:06'),(129,'DNM305','Final Clinical Practicum','Diploma in Nursing','Diploma','Semester 6',8,1,'Active','2026-07-03 04:38:06'),(130,'DNM306','Nursing Ethics & Legal Issues','Diploma in Nursing','Diploma','Semester 6',3,1,'Active','2026-07-03 04:38:06'),(131,'DMM101','Midwifery Science I','Diploma in Midwifery','Diploma','Semester 1',4,1,'Active','2026-07-03 04:38:06'),(132,'DMM102','Anatomy for Midwives','Diploma in Midwifery','Diploma','Semester 1',3,1,'Active','2026-07-03 04:38:06'),(133,'DMM103','Reproductive Health','Diploma in Midwifery','Diploma','Semester 1',3,1,'Active','2026-07-03 04:38:06'),(134,'DMM201','Advanced Midwifery Practice','Diploma in Midwifery','Diploma','Semester 3',5,1,'Active','2026-07-03 04:38:06'),(135,'DMM202','Maternal Health','Diploma in Midwifery','Diploma','Semester 3',4,1,'Active','2026-07-03 04:38:06'),(136,'DMM301','Midwifery Clinical Practicum','Diploma in Midwifery','Diploma','Semester 5',8,1,'Active','2026-07-03 04:38:06'),(137,'DNE101','Foundations of Education','Diploma in Nursing Education','Diploma','Semester 1',3,1,'Active','2026-07-03 04:38:06'),(138,'DNE102','Educational Psychology','Diploma in Nursing Education','Diploma','Semester 1',3,1,'Active','2026-07-03 04:38:06'),(139,'DNE201','Curriculum Development','Diploma in Nursing Education','Diploma','Semester 3',4,1,'Active','2026-07-03 04:38:06'),(140,'DNE202','Teaching Methods in Nursing','Diploma in Nursing Education','Diploma','Semester 3',4,1,'Active','2026-07-03 04:38:06'),(141,'DNE301','Practice Teaching','Diploma in Nursing Education','Diploma','Semester 5',6,1,'Active','2026-07-03 04:38:06'),(142,'CNN101','Fundamentals of Nursing I','Certificate in Nursing','Certificate','Semester 1',4,1,'Active','2026-07-03 04:51:14'),(143,'CNN102','Anatomy & Physiology I','Certificate in Nursing','Certificate','Semester 1',3,1,'Active','2026-07-03 04:51:14'),(144,'CNN103','Community Health Nursing I','Certificate in Nursing','Certificate','Semester 1',3,1,'Active','2026-07-03 04:51:14'),(145,'CNN104','Medical Surgical Nursing I','Certificate in Nursing','Certificate','Semester 2',4,1,'Active','2026-07-03 04:51:14'),(146,'CNN105','Anatomy & Physiology II','Certificate in Nursing','Certificate','Semester 2',3,1,'Active','2026-07-03 04:51:14'),(147,'CNN106','Pharmacology I','Certificate in Nursing','Certificate','Semester 2',3,1,'Active','2026-07-03 04:51:14'),(148,'CNN201','Fundamentals of Nursing II','Certificate in Nursing','Certificate','Semester 3',4,1,'Active','2026-07-03 04:51:14'),(149,'CNN202','Psychiatric Nursing','Certificate in Nursing','Certificate','Semester 3',3,1,'Active','2026-07-03 04:51:14'),(150,'CNN203','Pediatric Nursing','Certificate in Nursing','Certificate','Semester 3',3,1,'Active','2026-07-03 04:51:14'),(151,'CNN204','Community Health Nursing II','Certificate in Nursing','Certificate','Semester 4',4,1,'Active','2026-07-03 04:51:14'),(152,'CNM101','Introduction to Midwifery','Certificate in Midwifery','Certificate','Semester 1',4,1,'Active','2026-07-03 04:51:14'),(153,'CNM102','Anatomy for Midwives','Certificate in Midwifery','Certificate','Semester 1',3,1,'Active','2026-07-03 04:51:14'),(154,'CNM103','Fundamentals of Midwifery Care','Certificate in Midwifery','Certificate','Semester 1',4,1,'Active','2026-07-03 04:51:14'),(155,'CNM104','Antenatal Care','Certificate in Midwifery','Certificate','Semester 2',4,1,'Active','2026-07-03 04:51:14'),(156,'CNM105','Labour & Delivery Management','Certificate in Midwifery','Certificate','Semester 2',5,1,'Active','2026-07-03 04:51:14'),(157,'CNM106','Postnatal Care','Certificate in Midwifery','Certificate','Semester 2',3,1,'Active','2026-07-03 04:51:14'),(158,'CNM201','Emergency Midwifery','Certificate in Midwifery','Certificate','Semester 3',4,1,'Active','2026-07-03 04:51:14'),(159,'CNM202','Neonatal Care','Certificate in Midwifery','Certificate','Semester 3',3,1,'Active','2026-07-03 04:51:14'),(160,'CNM203','Community Midwifery','Certificate in Midwifery','Certificate','Semester 4',4,1,'Active','2026-07-03 04:51:14'),(161,'DNM101','Nursing Science I','Diploma in Nursing','Diploma','Semester 1',4,1,'Active','2026-07-03 04:51:14'),(162,'DNM102','Human Anatomy & Physiology I','Diploma in Nursing','Diploma','Semester 1',3,1,'Active','2026-07-03 04:51:14'),(163,'DNM103','Nutrition & Dietetics','Diploma in Nursing','Diploma','Semester 1',3,1,'Active','2026-07-03 04:51:14'),(164,'DNM104','Medical Surgical Nursing I','Diploma in Nursing','Diploma','Semester 2',5,1,'Active','2026-07-03 04:51:14'),(165,'DNM105','Pharmacology I','Diploma in Nursing','Diploma','Semester 2',3,1,'Active','2026-07-03 04:51:14'),(166,'DNM106','Pathology & Microbiology','Diploma in Nursing','Diploma','Semester 2',3,1,'Active','2026-07-03 04:51:14'),(167,'DNM201','Medical Surgical Nursing II','Diploma in Nursing','Diploma','Semester 3',5,1,'Active','2026-07-03 04:51:14'),(168,'DNM202','Pediatric Nursing','Diploma in Nursing','Diploma','Semester 3',4,1,'Active','2026-07-03 04:51:14'),(169,'DNM203','Psychiatric Nursing','Diploma in Nursing','Diploma','Semester 3',3,1,'Active','2026-07-03 04:51:14'),(170,'DNM204','Community Health Nursing I','Diploma in Nursing','Diploma','Semester 4',4,1,'Active','2026-07-03 04:51:14'),(171,'DNM205','Nursing Research','Diploma in Nursing','Diploma','Semester 4',3,0,'Active','2026-07-03 04:51:14'),(172,'DNM301','Medical Surgical Nursing III','Diploma in Nursing','Diploma','Semester 5',5,1,'Active','2026-07-03 04:51:14'),(173,'DNM302','Community Health Nursing II','Diploma in Nursing','Diploma','Semester 5',4,1,'Active','2026-07-03 04:51:14'),(174,'DNM303','Nursing Management & Leadership','Diploma in Nursing','Diploma','Semester 5',4,1,'Active','2026-07-03 04:51:14'),(175,'DNM304','Clinical Practicum I','Diploma in Nursing','Diploma','Semester 5',6,1,'Active','2026-07-03 04:51:14'),(176,'DNM305','Final Clinical Practicum','Diploma in Nursing','Diploma','Semester 6',8,1,'Active','2026-07-03 04:51:14'),(177,'DNM306','Nursing Ethics & Legal Issues','Diploma in Nursing','Diploma','Semester 6',3,1,'Active','2026-07-03 04:51:14'),(178,'DMM101','Midwifery Science I','Diploma in Midwifery','Diploma','Semester 1',4,1,'Active','2026-07-03 04:51:14'),(179,'DMM102','Anatomy for Midwives','Diploma in Midwifery','Diploma','Semester 1',3,1,'Active','2026-07-03 04:51:14'),(180,'DMM103','Reproductive Health','Diploma in Midwifery','Diploma','Semester 1',3,1,'Active','2026-07-03 04:51:14'),(181,'DMM201','Advanced Midwifery Practice','Diploma in Midwifery','Diploma','Semester 3',5,1,'Active','2026-07-03 04:51:14'),(182,'DMM202','Maternal Health','Diploma in Midwifery','Diploma','Semester 3',4,1,'Active','2026-07-03 04:51:14'),(183,'DMM301','Midwifery Clinical Practicum','Diploma in Midwifery','Diploma','Semester 5',8,1,'Active','2026-07-03 04:51:14'),(184,'DNE101','Foundations of Education','Diploma in Nursing Education','Diploma','Semester 1',3,1,'Active','2026-07-03 04:51:14'),(185,'DNE102','Educational Psychology','Diploma in Nursing Education','Diploma','Semester 1',3,1,'Active','2026-07-03 04:51:14'),(186,'DNE201','Curriculum Development','Diploma in Nursing Education','Diploma','Semester 3',4,1,'Active','2026-07-03 04:51:14'),(187,'DNE202','Teaching Methods in Nursing','Diploma in Nursing Education','Diploma','Semester 3',4,1,'Active','2026-07-03 04:51:14'),(188,'DNE301','Practice Teaching','Diploma in Nursing Education','Diploma','Semester 5',6,1,'Active','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `course_catalog` ENABLE KEYS */;
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
-- Table structure for table `department_performance`
--

DROP TABLE IF EXISTS `department_performance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_performance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(200) DEFAULT NULL,
  `metric` varchar(200) DEFAULT NULL,
  `value` decimal(14,2) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_performance`
--

LOCK TABLES `department_performance` WRITE;
/*!40000 ALTER TABLE `department_performance` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_performance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_requests`
--

DROP TABLE IF EXISTS `department_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_requests` (
  `id` int(11) NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `from_department` varchar(100) NOT NULL,
  `to_department` varchar(100) NOT NULL DEFAULT 'Store',
  `item_name` varchar(200) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit` varchar(50) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `urgency` enum('Normal','Urgent','Emergency') DEFAULT 'Normal',
  `status` enum('Pending','Approved','Rejected','Fulfilled') DEFAULT 'Pending',
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_requests`
--

LOCK TABLES `department_requests` WRITE;
/*!40000 ALTER TABLE `department_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deputy_tasks`
--

DROP TABLE IF EXISTS `deputy_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deputy_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_title` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_by` varchar(200) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deputy_tasks`
--

LOCK TABLES `deputy_tasks` WRITE;
/*!40000 ALTER TABLE `deputy_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `deputy_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_tracking`
--

DROP TABLE IF EXISTS `document_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_title` varchar(300) DEFAULT NULL,
  `doc_type` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `file_name` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','filed','archived') DEFAULT 'draft',
  `uploaded_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_tracking`
--

LOCK TABLES `document_tracking` WRITE;
/*!40000 ALTER TABLE `document_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_tracking` ENABLE KEYS */;
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
-- Table structure for table `exam_results`
--

DROP TABLE IF EXISTS `exam_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_results`
--

LOCK TABLES `exam_results` WRITE;
/*!40000 ALTER TABLE `exam_results` DISABLE KEYS */;
INSERT INTO `exam_results` VALUES (1,1,0,96.00,'A','Pass','2026-07-03 04:51:14'),(2,3,0,61.00,'A','Pass','2026-07-03 04:51:14'),(3,5,0,89.00,'A','Pass','2026-07-03 04:51:14'),(4,7,0,73.00,'B','Pass','2026-07-03 04:51:14'),(5,9,0,67.00,'A','Pass','2026-07-03 04:51:14'),(6,11,0,74.00,'A','Pass','2026-07-03 04:51:14'),(7,13,0,63.00,'B+','Pass','2026-07-03 04:51:14'),(8,14,0,72.00,'A','Pass','2026-07-03 04:51:14'),(9,16,0,69.00,'A','Pass','2026-07-03 04:51:14'),(10,18,0,94.00,'B','Pass','2026-07-03 04:51:14'),(11,20,0,61.00,'A','Pass','2026-07-03 04:51:14'),(12,22,0,89.00,'A','Pass','2026-07-03 04:51:14'),(13,24,0,88.00,'B+','Pass','2026-07-03 04:51:14'),(14,26,0,85.00,'A','Pass','2026-07-03 04:51:14'),(15,28,0,76.00,'B','Pass','2026-07-03 04:51:14'),(16,29,0,62.00,'A','Pass','2026-07-03 04:51:14'),(17,31,0,65.00,'B','Pass','2026-07-03 04:51:14'),(18,33,0,73.00,'A','Pass','2026-07-03 04:51:14'),(19,35,0,98.00,'B','Pass','2026-07-03 04:51:14'),(20,37,0,84.00,'A','Pass','2026-07-03 04:51:14'),(21,39,0,60.00,'B+','Pass','2026-07-03 04:51:14'),(22,41,0,67.00,'A','Pass','2026-07-03 04:51:14'),(23,43,0,62.00,'B+','Pass','2026-07-03 04:51:14'),(24,44,0,80.00,'A','Pass','2026-07-03 04:51:14'),(25,46,0,78.00,'A','Pass','2026-07-03 04:51:14'),(26,48,0,62.00,'A','Pass','2026-07-03 04:51:14'),(27,50,0,67.00,'A','Pass','2026-07-03 04:51:14'),(28,52,0,99.00,'B','Pass','2026-07-03 04:51:14'),(29,54,0,64.00,'B','Pass','2026-07-03 04:51:14'),(30,56,0,91.00,'B+','Pass','2026-07-03 04:51:14'),(31,58,0,60.00,'B+','Pass','2026-07-03 04:51:14'),(32,59,0,64.00,'A','Pass','2026-07-03 04:51:14'),(33,1,0,96.00,'B','Pass','2026-07-03 04:51:14'),(34,3,0,76.00,'A','Pass','2026-07-03 04:51:14'),(35,5,0,84.00,'A','Pass','2026-07-03 04:51:14'),(36,7,0,99.00,'A','Pass','2026-07-03 04:51:14'),(37,9,0,63.00,'A','Pass','2026-07-03 04:51:14'),(38,11,0,86.00,'A','Pass','2026-07-03 04:51:14'),(39,13,0,97.00,'A','Pass','2026-07-03 04:51:14'),(40,14,0,98.00,'A','Pass','2026-07-03 04:51:14'),(41,16,0,90.00,'B','Pass','2026-07-03 04:51:14'),(42,18,0,99.00,'A','Pass','2026-07-03 04:51:14'),(43,20,0,95.00,'B','Pass','2026-07-03 04:51:14'),(44,22,0,82.00,'B','Pass','2026-07-03 04:51:14'),(45,24,0,79.00,'A','Pass','2026-07-03 04:51:14'),(46,26,0,77.00,'A','Pass','2026-07-03 04:51:14'),(47,28,0,63.00,'A','Pass','2026-07-03 04:51:14'),(48,29,0,83.00,'A','Pass','2026-07-03 04:51:14'),(49,31,0,82.00,'A','Pass','2026-07-03 04:51:14'),(50,33,0,74.00,'A','Pass','2026-07-03 04:51:14'),(51,35,0,60.00,'A','Pass','2026-07-03 04:51:14'),(52,37,0,60.00,'A','Pass','2026-07-03 04:51:14'),(53,39,0,97.00,'A','Pass','2026-07-03 04:51:14'),(54,41,0,85.00,'A','Pass','2026-07-03 04:51:14'),(55,43,0,83.00,'A','Pass','2026-07-03 04:51:14'),(56,44,0,76.00,'A','Pass','2026-07-03 04:51:14'),(57,46,0,88.00,'B+','Pass','2026-07-03 04:51:14'),(58,48,0,86.00,'A','Pass','2026-07-03 04:51:14'),(59,50,0,74.00,'A','Pass','2026-07-03 04:51:14'),(60,52,0,61.00,'A','Pass','2026-07-03 04:51:14'),(61,54,0,71.00,'A','Pass','2026-07-03 04:51:14'),(62,56,0,63.00,'B','Pass','2026-07-03 04:51:14'),(63,58,0,80.00,'B+','Pass','2026-07-03 04:51:14'),(64,59,0,89.00,'A','Pass','2026-07-03 04:51:14'),(65,1,0,85.00,'B+','Pass','2026-07-03 04:51:14'),(66,3,0,79.00,'A','Pass','2026-07-03 04:51:14'),(67,5,0,70.00,'B+','Pass','2026-07-03 04:51:14'),(68,7,0,84.00,'A','Pass','2026-07-03 04:51:14'),(69,9,0,63.00,'A','Pass','2026-07-03 04:51:14'),(70,11,0,72.00,'A','Pass','2026-07-03 04:51:14'),(71,13,0,65.00,'A','Pass','2026-07-03 04:51:14'),(72,14,0,95.00,'A','Pass','2026-07-03 04:51:14'),(73,16,0,84.00,'A','Pass','2026-07-03 04:51:14'),(74,18,0,92.00,'A','Pass','2026-07-03 04:51:14'),(75,20,0,97.00,'A','Pass','2026-07-03 04:51:14'),(76,22,0,86.00,'A','Pass','2026-07-03 04:51:14'),(77,24,0,97.00,'A','Pass','2026-07-03 04:51:14'),(78,26,0,94.00,'A','Pass','2026-07-03 04:51:14'),(79,28,0,67.00,'A','Pass','2026-07-03 04:51:14'),(80,29,0,96.00,'A','Pass','2026-07-03 04:51:14'),(81,31,0,82.00,'A','Pass','2026-07-03 04:51:14'),(82,33,0,75.00,'A','Pass','2026-07-03 04:51:14'),(83,35,0,72.00,'A','Pass','2026-07-03 04:51:14'),(84,37,0,70.00,'B','Pass','2026-07-03 04:51:14'),(85,39,0,96.00,'A','Pass','2026-07-03 04:51:14'),(86,41,0,66.00,'A','Pass','2026-07-03 04:51:14'),(87,43,0,97.00,'A','Pass','2026-07-03 04:51:14'),(88,44,0,73.00,'A','Pass','2026-07-03 04:51:14'),(89,46,0,91.00,'A','Pass','2026-07-03 04:51:14'),(90,48,0,97.00,'A','Pass','2026-07-03 04:51:14'),(91,50,0,87.00,'A','Pass','2026-07-03 04:51:14'),(92,52,0,88.00,'A','Pass','2026-07-03 04:51:14'),(93,54,0,81.00,'B+','Pass','2026-07-03 04:51:14'),(94,56,0,80.00,'A','Pass','2026-07-03 04:51:14'),(95,58,0,99.00,'B','Pass','2026-07-03 04:51:14'),(96,59,0,92.00,'A','Pass','2026-07-03 04:51:14'),(97,1,0,69.00,'A','Pass','2026-07-03 04:51:14'),(98,3,0,71.00,'A','Pass','2026-07-03 04:51:14'),(99,5,0,71.00,'A','Pass','2026-07-03 04:51:14'),(100,7,0,93.00,'A','Pass','2026-07-03 04:51:14'),(101,9,0,70.00,'A','Pass','2026-07-03 04:51:14'),(102,11,0,86.00,'B','Pass','2026-07-03 04:51:14'),(103,13,0,89.00,'A','Pass','2026-07-03 04:51:14'),(104,14,0,91.00,'A','Pass','2026-07-03 04:51:14'),(105,16,0,61.00,'A','Pass','2026-07-03 04:51:14'),(106,18,0,93.00,'A','Pass','2026-07-03 04:51:14'),(107,20,0,89.00,'B+','Pass','2026-07-03 04:51:14'),(108,22,0,66.00,'B+','Pass','2026-07-03 04:51:14'),(109,24,0,86.00,'A','Pass','2026-07-03 04:51:14'),(110,26,0,67.00,'B+','Pass','2026-07-03 04:51:14'),(111,28,0,77.00,'B+','Pass','2026-07-03 04:51:14'),(112,29,0,72.00,'A','Pass','2026-07-03 04:51:14'),(113,31,0,77.00,'A','Pass','2026-07-03 04:51:14'),(114,33,0,87.00,'A','Pass','2026-07-03 04:51:14'),(115,35,0,93.00,'A','Pass','2026-07-03 04:51:14'),(116,37,0,98.00,'B','Pass','2026-07-03 04:51:14'),(117,39,0,83.00,'A','Pass','2026-07-03 04:51:14'),(118,41,0,95.00,'A','Pass','2026-07-03 04:51:14'),(119,43,0,88.00,'A','Pass','2026-07-03 04:51:14'),(120,44,0,82.00,'A','Pass','2026-07-03 04:51:14'),(121,46,0,67.00,'A','Pass','2026-07-03 04:51:14'),(122,48,0,73.00,'A','Pass','2026-07-03 04:51:14'),(123,50,0,90.00,'A','Pass','2026-07-03 04:51:14'),(124,52,0,87.00,'A','Pass','2026-07-03 04:51:14'),(125,54,0,68.00,'B+','Pass','2026-07-03 04:51:14'),(126,56,0,83.00,'A','Pass','2026-07-03 04:51:14'),(127,58,0,96.00,'A','Pass','2026-07-03 04:51:14'),(128,59,0,95.00,'B+','Pass','2026-07-03 04:51:14'),(129,1,0,95.00,'B','Pass','2026-07-03 04:51:14'),(130,3,0,89.00,'B','Pass','2026-07-03 04:51:14'),(131,5,0,82.00,'A','Pass','2026-07-03 04:51:14'),(132,7,0,71.00,'A','Pass','2026-07-03 04:51:14'),(133,9,0,79.00,'A','Pass','2026-07-03 04:51:14'),(134,11,0,89.00,'A','Pass','2026-07-03 04:51:14'),(135,13,0,83.00,'A','Pass','2026-07-03 04:51:14'),(136,14,0,74.00,'A','Pass','2026-07-03 04:51:14'),(137,16,0,85.00,'B+','Pass','2026-07-03 04:51:14'),(138,18,0,76.00,'A','Pass','2026-07-03 04:51:14'),(139,20,0,94.00,'A','Pass','2026-07-03 04:51:14'),(140,22,0,87.00,'A','Pass','2026-07-03 04:51:14'),(141,24,0,65.00,'B','Pass','2026-07-03 04:51:14'),(142,26,0,87.00,'B+','Pass','2026-07-03 04:51:14'),(143,28,0,87.00,'A','Pass','2026-07-03 04:51:14'),(144,29,0,84.00,'A','Pass','2026-07-03 04:51:14'),(145,31,0,79.00,'B+','Pass','2026-07-03 04:51:14'),(146,33,0,96.00,'A','Pass','2026-07-03 04:51:14'),(147,35,0,79.00,'A','Pass','2026-07-03 04:51:14'),(148,37,0,93.00,'A','Pass','2026-07-03 04:51:14'),(149,39,0,83.00,'B+','Pass','2026-07-03 04:51:14'),(150,41,0,85.00,'A','Pass','2026-07-03 04:51:14'),(151,43,0,94.00,'B+','Pass','2026-07-03 04:51:14'),(152,44,0,60.00,'A','Pass','2026-07-03 04:51:14'),(153,46,0,71.00,'B','Pass','2026-07-03 04:51:14'),(154,48,0,78.00,'A','Pass','2026-07-03 04:51:14'),(155,50,0,82.00,'A','Pass','2026-07-03 04:51:14'),(156,52,0,79.00,'A','Pass','2026-07-03 04:51:14'),(157,54,0,63.00,'A','Pass','2026-07-03 04:51:14'),(158,56,0,66.00,'B+','Pass','2026-07-03 04:51:14'),(159,58,0,60.00,'A','Pass','2026-07-03 04:51:14'),(160,59,0,70.00,'A','Pass','2026-07-03 04:51:14'),(161,1,0,91.00,'B+','Pass','2026-07-03 04:51:14'),(162,3,0,82.00,'A','Pass','2026-07-03 04:51:14'),(163,5,0,99.00,'B','Pass','2026-07-03 04:51:14'),(164,7,0,63.00,'B+','Pass','2026-07-03 04:51:14'),(165,9,0,97.00,'B+','Pass','2026-07-03 04:51:14'),(166,11,0,99.00,'B','Pass','2026-07-03 04:51:14'),(167,13,0,77.00,'A','Pass','2026-07-03 04:51:14'),(168,14,0,76.00,'A','Pass','2026-07-03 04:51:14'),(169,16,0,85.00,'B','Pass','2026-07-03 04:51:14'),(170,18,0,86.00,'B','Pass','2026-07-03 04:51:14'),(171,20,0,60.00,'A','Pass','2026-07-03 04:51:14'),(172,22,0,61.00,'A','Pass','2026-07-03 04:51:14'),(173,24,0,90.00,'A','Pass','2026-07-03 04:51:14'),(174,26,0,61.00,'B+','Pass','2026-07-03 04:51:14'),(175,28,0,60.00,'B','Pass','2026-07-03 04:51:14'),(176,29,0,62.00,'B','Pass','2026-07-03 04:51:14'),(177,31,0,63.00,'A','Pass','2026-07-03 04:51:14'),(178,33,0,81.00,'A','Pass','2026-07-03 04:51:14'),(179,35,0,78.00,'A','Pass','2026-07-03 04:51:14'),(180,37,0,93.00,'A','Pass','2026-07-03 04:51:14'),(181,39,0,83.00,'B+','Pass','2026-07-03 04:51:14'),(182,41,0,83.00,'A','Pass','2026-07-03 04:51:14'),(183,43,0,66.00,'B+','Pass','2026-07-03 04:51:14'),(184,44,0,95.00,'A','Pass','2026-07-03 04:51:14'),(185,46,0,64.00,'A','Pass','2026-07-03 04:51:14'),(186,48,0,78.00,'B+','Pass','2026-07-03 04:51:14'),(187,50,0,71.00,'A','Pass','2026-07-03 04:51:14'),(188,52,0,86.00,'B','Pass','2026-07-03 04:51:14'),(189,54,0,88.00,'A','Pass','2026-07-03 04:51:14'),(190,56,0,74.00,'A','Pass','2026-07-03 04:51:14'),(191,58,0,80.00,'A','Pass','2026-07-03 04:51:14'),(192,59,0,80.00,'A','Pass','2026-07-03 04:51:14'),(193,1,0,62.00,'A','Pass','2026-07-03 04:51:14'),(194,3,0,83.00,'A','Pass','2026-07-03 04:51:14'),(195,5,0,87.00,'A','Pass','2026-07-03 04:51:14'),(196,7,0,86.00,'B+','Pass','2026-07-03 04:51:14'),(197,9,0,87.00,'A','Pass','2026-07-03 04:51:14'),(198,11,0,95.00,'A','Pass','2026-07-03 04:51:14'),(199,13,0,96.00,'A','Pass','2026-07-03 04:51:14'),(200,14,0,67.00,'A','Pass','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `exam_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `duration` int(11) DEFAULT 0,
  `total_marks` int(11) DEFAULT 100,
  `passing_marks` int(11) DEFAULT 50,
  `term` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
INSERT INTO `exams` VALUES (1,'Fundamentals of Nursing I - CAT1','CAT',NULL,NULL,'2024-10-15',60,30,15,'Term 1','2024/2025','completed','2026-07-03 03:56:26'),(2,'Fundamentals of Nursing I - Final','Final',NULL,NULL,'2024-12-10',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 03:56:26'),(3,'Anatomy & Physiology I - CAT1','CAT',NULL,NULL,'2024-10-16',60,30,15,'Term 1','2024/2025','completed','2026-07-03 03:56:26'),(4,'Anatomy & Physiology I - Final','Final',NULL,NULL,'2024-12-11',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 03:56:26'),(5,'Intro to Midwifery - CAT1','CAT',NULL,NULL,'2024-10-17',60,30,15,'Term 1','2024/2025','completed','2026-07-03 03:56:26'),(6,'Intro to Midwifery - Final','Final',NULL,NULL,'2024-12-12',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 03:56:26'),(7,'Nursing Science I - CAT1','CAT',NULL,NULL,'2024-10-18',60,30,15,'Term 1','2024/2025','completed','2026-07-03 03:56:26'),(8,'Nursing Science I - Final','Final',NULL,NULL,'2024-12-13',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 03:56:26'),(9,'Med Surg Nursing I - CAT1','CAT',NULL,NULL,'2025-02-20',60,30,15,'Term 2','2024/2025','scheduled','2026-07-03 03:56:26'),(10,'Med Surg Nursing I - Final','Final',NULL,NULL,'2025-04-25',180,100,50,'Term 2','2024/2025','scheduled','2026-07-03 03:56:26'),(11,'Community Health I - CAT1','CAT',NULL,NULL,'2024-10-20',60,30,15,'Term 1','2024/2025','completed','2026-07-03 03:56:26'),(12,'Community Health I - Final','Final',NULL,NULL,'2024-12-15',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 03:56:26'),(13,'Pharmacology I - CAT1','CAT',NULL,NULL,'2025-02-22',60,30,15,'Term 2','2024/2025','scheduled','2026-07-03 03:56:26'),(14,'Med Surg Nursing II - CAT1','CAT',NULL,NULL,'2025-06-10',60,30,15,'Term 3','2024/2025','scheduled','2026-07-03 03:56:26'),(15,'Med Surg Nursing II - Final','Final',NULL,NULL,'2025-08-15',180,100,50,'Term 3','2024/2025','scheduled','2026-07-03 03:56:26'),(16,'Fundamentals of Nursing I - CAT1','CAT',NULL,NULL,'2024-10-15',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:05:12'),(17,'Fundamentals of Nursing I - Final','Final',NULL,NULL,'2024-12-10',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:05:12'),(18,'Anatomy & Physiology I - CAT1','CAT',NULL,NULL,'2024-10-16',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:05:12'),(19,'Anatomy & Physiology I - Final','Final',NULL,NULL,'2024-12-11',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:05:12'),(20,'Intro to Midwifery - CAT1','CAT',NULL,NULL,'2024-10-17',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:05:12'),(21,'Intro to Midwifery - Final','Final',NULL,NULL,'2024-12-12',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:05:12'),(22,'Nursing Science I - CAT1','CAT',NULL,NULL,'2024-10-18',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:05:12'),(23,'Nursing Science I - Final','Final',NULL,NULL,'2024-12-13',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:05:12'),(24,'Med Surg Nursing I - CAT1','CAT',NULL,NULL,'2025-02-20',60,30,15,'Term 2','2024/2025','scheduled','2026-07-03 04:05:12'),(25,'Med Surg Nursing I - Final','Final',NULL,NULL,'2025-04-25',180,100,50,'Term 2','2024/2025','scheduled','2026-07-03 04:05:12'),(26,'Community Health I - CAT1','CAT',NULL,NULL,'2024-10-20',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:05:12'),(27,'Community Health I - Final','Final',NULL,NULL,'2024-12-15',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:05:12'),(28,'Pharmacology I - CAT1','CAT',NULL,NULL,'2025-02-22',60,30,15,'Term 2','2024/2025','scheduled','2026-07-03 04:05:12'),(29,'Med Surg Nursing II - CAT1','CAT',NULL,NULL,'2025-06-10',60,30,15,'Term 3','2024/2025','scheduled','2026-07-03 04:05:12'),(30,'Med Surg Nursing II - Final','Final',NULL,NULL,'2025-08-15',180,100,50,'Term 3','2024/2025','scheduled','2026-07-03 04:05:12'),(31,'Fundamentals of Nursing I - CAT1','CAT',NULL,NULL,'2024-10-15',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:38:06'),(32,'Fundamentals of Nursing I - Final','Final',NULL,NULL,'2024-12-10',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:38:06'),(33,'Anatomy & Physiology I - CAT1','CAT',NULL,NULL,'2024-10-16',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:38:06'),(34,'Anatomy & Physiology I - Final','Final',NULL,NULL,'2024-12-11',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:38:06'),(35,'Intro to Midwifery - CAT1','CAT',NULL,NULL,'2024-10-17',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:38:06'),(36,'Intro to Midwifery - Final','Final',NULL,NULL,'2024-12-12',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:38:06'),(37,'Nursing Science I - CAT1','CAT',NULL,NULL,'2024-10-18',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:38:06'),(38,'Nursing Science I - Final','Final',NULL,NULL,'2024-12-13',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:38:06'),(39,'Med Surg Nursing I - CAT1','CAT',NULL,NULL,'2025-02-20',60,30,15,'Term 2','2024/2025','scheduled','2026-07-03 04:38:06'),(40,'Med Surg Nursing I - Final','Final',NULL,NULL,'2025-04-25',180,100,50,'Term 2','2024/2025','scheduled','2026-07-03 04:38:06'),(41,'Community Health I - CAT1','CAT',NULL,NULL,'2024-10-20',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:38:06'),(42,'Community Health I - Final','Final',NULL,NULL,'2024-12-15',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:38:06'),(43,'Pharmacology I - CAT1','CAT',NULL,NULL,'2025-02-22',60,30,15,'Term 2','2024/2025','scheduled','2026-07-03 04:38:06'),(44,'Med Surg Nursing II - CAT1','CAT',NULL,NULL,'2025-06-10',60,30,15,'Term 3','2024/2025','scheduled','2026-07-03 04:38:06'),(45,'Med Surg Nursing II - Final','Final',NULL,NULL,'2025-08-15',180,100,50,'Term 3','2024/2025','scheduled','2026-07-03 04:38:06'),(46,'Fundamentals of Nursing I - CAT1','CAT',NULL,NULL,'2024-10-15',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:51:14'),(47,'Fundamentals of Nursing I - Final','Final',NULL,NULL,'2024-12-10',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:51:14'),(48,'Anatomy & Physiology I - CAT1','CAT',NULL,NULL,'2024-10-16',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:51:14'),(49,'Anatomy & Physiology I - Final','Final',NULL,NULL,'2024-12-11',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:51:14'),(50,'Intro to Midwifery - CAT1','CAT',NULL,NULL,'2024-10-17',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:51:14'),(51,'Intro to Midwifery - Final','Final',NULL,NULL,'2024-12-12',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:51:14'),(52,'Nursing Science I - CAT1','CAT',NULL,NULL,'2024-10-18',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:51:14'),(53,'Nursing Science I - Final','Final',NULL,NULL,'2024-12-13',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:51:14'),(54,'Med Surg Nursing I - CAT1','CAT',NULL,NULL,'2025-02-20',60,30,15,'Term 2','2024/2025','scheduled','2026-07-03 04:51:14'),(55,'Med Surg Nursing I - Final','Final',NULL,NULL,'2025-04-25',180,100,50,'Term 2','2024/2025','scheduled','2026-07-03 04:51:14'),(56,'Community Health I - CAT1','CAT',NULL,NULL,'2024-10-20',60,30,15,'Term 1','2024/2025','completed','2026-07-03 04:51:14'),(57,'Community Health I - Final','Final',NULL,NULL,'2024-12-15',180,100,50,'Term 1','2024/2025','scheduled','2026-07-03 04:51:14'),(58,'Pharmacology I - CAT1','CAT',NULL,NULL,'2025-02-22',60,30,15,'Term 2','2024/2025','scheduled','2026-07-03 04:51:14'),(59,'Med Surg Nursing II - CAT1','CAT',NULL,NULL,'2025-06-10',60,30,15,'Term 3','2024/2025','scheduled','2026-07-03 04:51:14'),(60,'Med Surg Nursing II - Final','Final',NULL,NULL,'2025-08-15',180,100,50,'Term 3','2024/2025','scheduled','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenditure_approvals`
--

DROP TABLE IF EXISTS `expenditure_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenditure_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) DEFAULT 0,
  `request_type` varchar(50) DEFAULT NULL,
  `requested_by` int(11) DEFAULT 0,
  `amount` decimal(14,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
  `approver_id` int(11) DEFAULT 0,
  `approver_name` varchar(200) DEFAULT NULL,
  `approver_comments` text DEFAULT NULL,
  `escalated_to` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenditure_approvals`
--

LOCK TABLES `expenditure_approvals` WRITE;
/*!40000 ALTER TABLE `expenditure_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenditure_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenditure_records`
--

DROP TABLE IF EXISTS `expenditure_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenditure_records` (
  `id` int(11) NOT NULL,
  `expenditure_number` varchar(50) NOT NULL,
  `budget_record_id` int(11) DEFAULT NULL,
  `expenditure_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `expenditure_date` date DEFAULT curdate(),
  `approved_by` int(11) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `supporting_document` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenditure_records`
--

LOCK TABLES `expenditure_records` WRITE;
/*!40000 ALTER TABLE `expenditure_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenditure_records` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_adjustments`
--

DROP TABLE IF EXISTS `fee_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_adjustments` (
  `id` int(11) NOT NULL,
  `adjustment_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `adjustment_type` enum('Discount','Waiver','Penalty','Refund','Other') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_adjustments`
--

LOCK TABLES `fee_adjustments` WRITE;
/*!40000 ALTER TABLE `fee_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_reminders`
--

DROP TABLE IF EXISTS `fee_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_reminders` (
  `id` int(11) NOT NULL,
  `reminder_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `reminder_type` enum('Email','SMS','Letter','Call') DEFAULT 'Email',
  `reminder_date` timestamp NULL DEFAULT current_timestamp(),
  `sent_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_reminders`
--

LOCK TABLES `fee_reminders` WRITE;
/*!40000 ALTER TABLE `fee_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_reminders` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structure`
--

LOCK TABLES `fee_structure` WRITE;
/*!40000 ALTER TABLE `fee_structure` DISABLE KEYS */;
INSERT INTO `fee_structure` VALUES (1,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Tuition',850000.00,'Semester 1 Tuition Fee',1,'2026-07-03 03:56:26'),(2,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Functional',150000.00,'Functional Fee',1,'2026-07-03 03:56:26'),(3,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Examination',50000.00,'Examination Fee',1,'2026-07-03 03:56:26'),(4,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Practical',100000.00,'Practical / Clinical Fee',1,'2026-07-03 03:56:26'),(5,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Tuition',900000.00,'Semester 1 Tuition Fee',1,'2026-07-03 03:56:26'),(6,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Functional',150000.00,'Functional Fee',1,'2026-07-03 03:56:26'),(7,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Examination',50000.00,'Examination Fee',1,'2026-07-03 03:56:26'),(8,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Practical',120000.00,'Practical / Clinical Fee',1,'2026-07-03 03:56:26'),(9,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Tuition',1200000.00,'Semester 1 Tuition Fee',1,'2026-07-03 03:56:26'),(10,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Functional',200000.00,'Functional Fee',1,'2026-07-03 03:56:26'),(11,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 03:56:26'),(12,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Practical',150000.00,'Practical / Clinical Fee',1,'2026-07-03 03:56:26'),(13,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Tuition',1250000.00,'Semester 1 Tuition Fee',1,'2026-07-03 03:56:26'),(14,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Functional',200000.00,'Functional Fee',1,'2026-07-03 03:56:26'),(15,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 03:56:26'),(16,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Practical',160000.00,'Practical / Clinical Fee',1,'2026-07-03 03:56:26'),(17,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Tuition',1100000.00,'Semester 1 Tuition Fee',1,'2026-07-03 03:56:26'),(18,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Functional',180000.00,'Functional Fee',1,'2026-07-03 03:56:26'),(19,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 03:56:26'),(20,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Practical',130000.00,'Practical / Clinical Fee',1,'2026-07-03 03:56:26'),(21,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Tuition',850000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:05:12'),(22,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Functional',150000.00,'Functional Fee',1,'2026-07-03 04:05:12'),(23,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Examination',50000.00,'Examination Fee',1,'2026-07-03 04:05:12'),(24,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Practical',100000.00,'Practical / Clinical Fee',1,'2026-07-03 04:05:12'),(25,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Tuition',900000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:05:12'),(26,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Functional',150000.00,'Functional Fee',1,'2026-07-03 04:05:12'),(27,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Examination',50000.00,'Examination Fee',1,'2026-07-03 04:05:12'),(28,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Practical',120000.00,'Practical / Clinical Fee',1,'2026-07-03 04:05:12'),(29,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Tuition',1200000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:05:12'),(30,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Functional',200000.00,'Functional Fee',1,'2026-07-03 04:05:12'),(31,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 04:05:12'),(32,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Practical',150000.00,'Practical / Clinical Fee',1,'2026-07-03 04:05:12'),(33,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Tuition',1250000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:05:12'),(34,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Functional',200000.00,'Functional Fee',1,'2026-07-03 04:05:12'),(35,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 04:05:12'),(36,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Practical',160000.00,'Practical / Clinical Fee',1,'2026-07-03 04:05:12'),(37,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Tuition',1100000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:05:12'),(38,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Functional',180000.00,'Functional Fee',1,'2026-07-03 04:05:12'),(39,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 04:05:12'),(40,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Practical',130000.00,'Practical / Clinical Fee',1,'2026-07-03 04:05:12'),(41,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Tuition',850000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:38:06'),(42,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Functional',150000.00,'Functional Fee',1,'2026-07-03 04:38:06'),(43,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Examination',50000.00,'Examination Fee',1,'2026-07-03 04:38:06'),(44,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Practical',100000.00,'Practical / Clinical Fee',1,'2026-07-03 04:38:06'),(45,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Tuition',900000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:38:06'),(46,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Functional',150000.00,'Functional Fee',1,'2026-07-03 04:38:06'),(47,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Examination',50000.00,'Examination Fee',1,'2026-07-03 04:38:06'),(48,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Practical',120000.00,'Practical / Clinical Fee',1,'2026-07-03 04:38:06'),(49,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Tuition',1200000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:38:06'),(50,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Functional',200000.00,'Functional Fee',1,'2026-07-03 04:38:06'),(51,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 04:38:06'),(52,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Practical',150000.00,'Practical / Clinical Fee',1,'2026-07-03 04:38:06'),(53,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Tuition',1250000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:38:06'),(54,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Functional',200000.00,'Functional Fee',1,'2026-07-03 04:38:06'),(55,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 04:38:06'),(56,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Practical',160000.00,'Practical / Clinical Fee',1,'2026-07-03 04:38:06'),(57,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Tuition',1100000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:38:06'),(58,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Functional',180000.00,'Functional Fee',1,'2026-07-03 04:38:06'),(59,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 04:38:06'),(60,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Practical',130000.00,'Practical / Clinical Fee',1,'2026-07-03 04:38:06'),(61,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Tuition',850000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:51:14'),(62,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Functional',150000.00,'Functional Fee',1,'2026-07-03 04:51:14'),(63,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Examination',50000.00,'Examination Fee',1,'2026-07-03 04:51:14'),(64,'Certificate in Nursing','Certificate','2024/2025','Semester 1','Practical',100000.00,'Practical / Clinical Fee',1,'2026-07-03 04:51:14'),(65,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Tuition',900000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:51:14'),(66,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Functional',150000.00,'Functional Fee',1,'2026-07-03 04:51:14'),(67,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Examination',50000.00,'Examination Fee',1,'2026-07-03 04:51:14'),(68,'Certificate in Midwifery','Certificate','2024/2025','Semester 1','Practical',120000.00,'Practical / Clinical Fee',1,'2026-07-03 04:51:14'),(69,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Tuition',1200000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:51:14'),(70,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Functional',200000.00,'Functional Fee',1,'2026-07-03 04:51:14'),(71,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 04:51:14'),(72,'Diploma in Nursing','Diploma','2024/2025','Semester 1','Practical',150000.00,'Practical / Clinical Fee',1,'2026-07-03 04:51:14'),(73,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Tuition',1250000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:51:14'),(74,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Functional',200000.00,'Functional Fee',1,'2026-07-03 04:51:14'),(75,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 04:51:14'),(76,'Diploma in Midwifery','Diploma','2024/2025','Semester 1','Practical',160000.00,'Practical / Clinical Fee',1,'2026-07-03 04:51:14'),(77,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Tuition',1100000.00,'Semester 1 Tuition Fee',1,'2026-07-03 04:51:14'),(78,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Functional',180000.00,'Functional Fee',1,'2026-07-03 04:51:14'),(79,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Examination',75000.00,'Examination Fee',1,'2026-07-03 04:51:14'),(80,'Diploma in Nursing Education','Diploma','2024/2025','Semester 1','Practical',130000.00,'Practical / Clinical Fee',1,'2026-07-03 04:51:14');
/*!40000 ALTER TABLE `fee_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_structures`
--

DROP TABLE IF EXISTS `fee_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL,
  `fee_name` varchar(255) NOT NULL,
  `fee_type` enum('Tuition','Registration','Library','Laboratory','Examination','Graduation','Other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `due_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structures`
--

LOCK TABLES `fee_structures` WRITE;
/*!40000 ALTER TABLE `fee_structures` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_structures` ENABLE KEYS */;
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
-- Table structure for table `finance_assets`
--

DROP TABLE IF EXISTS `finance_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_name` varchar(300) DEFAULT NULL,
  `asset_tag` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(14,2) DEFAULT 0.00,
  `current_value` decimal(14,2) DEFAULT 0.00,
  `depreciation_rate` decimal(5,2) DEFAULT 0.00,
  `location` varchar(200) DEFAULT NULL,
  `assigned_to` varchar(200) DEFAULT NULL,
  `status` enum('active','disposed','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_assets`
--

LOCK TABLES `finance_assets` WRITE;
/*!40000 ALTER TABLE `finance_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `finance_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_messages`
--

DROP TABLE IF EXISTS `finance_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) DEFAULT 0,
  `sender_name` varchar(200) DEFAULT NULL,
  `recipient_role` varchar(100) DEFAULT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_messages`
--

LOCK TABLES `finance_messages` WRITE;
/*!40000 ALTER TABLE `finance_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `finance_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_notices`
--

DROP TABLE IF EXISTS `finance_notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(300) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `published_by` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_notices`
--

LOCK TABLES `finance_notices` WRITE;
/*!40000 ALTER TABLE `finance_notices` DISABLE KEYS */;
/*!40000 ALTER TABLE `finance_notices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financial_clearance`
--

DROP TABLE IF EXISTS `financial_clearance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_clearance` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) DEFAULT 'Annual',
  `clearance_status` enum('Cleared','Not Cleared','Pending Review') DEFAULT 'Pending Review',
  `cleared_by` int(11) DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_clearance`
--

LOCK TABLES `financial_clearance` WRITE;
/*!40000 ALTER TABLE `financial_clearance` DISABLE KEYS */;
/*!40000 ALTER TABLE `financial_clearance` ENABLE KEYS */;
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
  `sender_role` varchar(100) DEFAULT NULL,
  `recipient_role` varchar(100) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
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
  `title` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `audience` varchar(50) DEFAULT 'all',
  `published_by` int(11) DEFAULT NULL,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
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
-- Table structure for table `financial_reports`
--

DROP TABLE IF EXISTS `financial_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_reports` (
  `id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('Income Statement','Balance Sheet','Cash Flow','Budget vs Actual','Fee Collection','Expenditure','Custom') NOT NULL,
  `report_period` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `report_data` longtext DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('Draft','Final','Archived') DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_reports`
--

LOCK TABLES `financial_reports` WRITE;
/*!40000 ALTER TABLE `financial_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `financial_reports` ENABLE KEYS */;
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
-- Table structure for table `general_ledger`
--

DROP TABLE IF EXISTS `general_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `general_ledger` (
  `id` int(11) NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `account_id` int(11) NOT NULL,
  `cost_center_id` int(11) DEFAULT NULL,
  `transaction_type` enum('Debit','Credit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date DEFAULT curdate(),
  `posted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_ledger`
--

LOCK TABLES `general_ledger` WRITE;
/*!40000 ALTER TABLE `general_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `general_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `graduation_candidates`
--

DROP TABLE IF EXISTS `graduation_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `graduation_candidates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `status` enum('Pending','Cleared','Graduated','Deferred') DEFAULT 'Pending',
  `clearance_bursar` tinyint(1) DEFAULT 0,
  `clearance_library` tinyint(1) DEFAULT 0,
  `clearance_registrar` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `graduation_candidates`
--

LOCK TABLES `graduation_candidates` WRITE;
/*!40000 ALTER TABLE `graduation_candidates` DISABLE KEYS */;
/*!40000 ALTER TABLE `graduation_candidates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel_allocations`
--

DROP TABLE IF EXISTS `hostel_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hostel_allocations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `room_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `check_in_date` date DEFAULT curdate(),
  `check_out_date` date DEFAULT NULL,
  `status` enum('Active','Checked Out','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel_allocations`
--

LOCK TABLES `hostel_allocations` WRITE;
/*!40000 ALTER TABLE `hostel_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `hostel_allocations` ENABLE KEYS */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_block_name` (`block_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel_blocks`
--

LOCK TABLES `hostel_blocks` WRITE;
/*!40000 ALTER TABLE `hostel_blocks` DISABLE KEYS */;
INSERT INTO `hostel_blocks` VALUES (1,'Block A - Queen Anne',24,'Female','Active',NULL,'2026-07-03 04:51:14'),(2,'Block B - Victoria',24,'Female','Active',NULL,'2026-07-03 04:51:14'),(3,'Block C - Florence Nightingale',16,'Female','Active',NULL,'2026-07-03 04:51:14'),(4,'Block D - Mary Seacole',16,'Female','Active',NULL,'2026-07-03 04:51:14'),(5,'Block E - Male Hostel',16,'Male','Active',NULL,'2026-07-03 04:51:14');
/*!40000 ALTER TABLE `hostel_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel_rooms`
--

DROP TABLE IF EXISTS `hostel_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hostel_rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `hostel_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `occupancy` int(11) NOT NULL DEFAULT 0,
  `fee_per_semester` decimal(12,2) DEFAULT 0.00,
  `status` enum('Available','Full','Maintenance') DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel_rooms`
--

LOCK TABLES `hostel_rooms` WRITE;
/*!40000 ALTER TABLE `hostel_rooms` DISABLE KEYS */;
INSERT INTO `hostel_rooms` VALUES (0,'QA-1-01','Block A - Queen Anne',4,3,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-2-01','Block A - Queen Anne',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'QA-3-01','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-1-01','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-2-01','Block B - Victoria',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'VB-3-01','Block B - Victoria',4,2,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-1-01','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-2-01','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-3-01','Block C - Florence Nightingale',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'MS-1-01','Block D - Mary Seacole',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-2-01','Block D - Mary Seacole',4,1,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-3-01','Block D - Mary Seacole',4,2,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-1-01','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-2-01','Block E - Male Hostel',4,3,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-1-02','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-2-02','Block A - Queen Anne',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'QA-3-02','Block A - Queen Anne',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'VB-1-02','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-2-02','Block B - Victoria',4,1,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-3-02','Block B - Victoria',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'FN-1-02','Block C - Florence Nightingale',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'FN-2-02','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-3-02','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-1-02','Block D - Mary Seacole',4,3,250000.00,'Full','2026-07-03 04:51:14'),(0,'MS-2-02','Block D - Mary Seacole',4,1,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-3-02','Block D - Mary Seacole',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-1-02','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-2-02','Block E - Male Hostel',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'QA-1-03','Block A - Queen Anne',4,2,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-2-03','Block A - Queen Anne',4,1,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-3-03','Block A - Queen Anne',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'VB-1-03','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-2-03','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-3-03','Block B - Victoria',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'FN-1-03','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-2-03','Block C - Florence Nightingale',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'FN-3-03','Block C - Florence Nightingale',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'MS-1-03','Block D - Mary Seacole',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-2-03','Block D - Mary Seacole',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-3-03','Block D - Mary Seacole',4,2,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-1-03','Block E - Male Hostel',4,3,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-2-03','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-1-04','Block A - Queen Anne',4,2,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-2-04','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-3-04','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-1-04','Block B - Victoria',4,3,250000.00,'Full','2026-07-03 04:51:14'),(0,'VB-2-04','Block B - Victoria',4,3,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-3-04','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-1-04','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-2-04','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-3-04','Block C - Florence Nightingale',4,1,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-1-04','Block D - Mary Seacole',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-2-04','Block D - Mary Seacole',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-3-04','Block D - Mary Seacole',4,1,250000.00,'Full','2026-07-03 04:51:14'),(0,'MH-1-04','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-2-04','Block E - Male Hostel',4,3,250000.00,'Full','2026-07-03 04:51:14'),(0,'QA-1-05','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-2-05','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-3-05','Block A - Queen Anne',4,3,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-1-05','Block B - Victoria',4,1,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-2-05','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-3-05','Block B - Victoria',4,3,250000.00,'Full','2026-07-03 04:51:14'),(0,'FN-1-05','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-2-05','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-3-05','Block C - Florence Nightingale',4,3,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-1-05','Block D - Mary Seacole',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'MS-2-05','Block D - Mary Seacole',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-3-05','Block D - Mary Seacole',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'MH-1-05','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-2-05','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-1-06','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-2-06','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-3-06','Block A - Queen Anne',4,2,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-1-06','Block B - Victoria',4,3,250000.00,'Full','2026-07-03 04:51:14'),(0,'VB-2-06','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-3-06','Block B - Victoria',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'FN-1-06','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-2-06','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-3-06','Block C - Florence Nightingale',4,1,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-1-06','Block D - Mary Seacole',4,1,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-2-06','Block D - Mary Seacole',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'MS-3-06','Block D - Mary Seacole',4,2,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-1-06','Block E - Male Hostel',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'MH-2-06','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-1-07','Block A - Queen Anne',4,3,250000.00,'Full','2026-07-03 04:51:14'),(0,'QA-2-07','Block A - Queen Anne',4,2,250000.00,'Full','2026-07-03 04:51:14'),(0,'QA-3-07','Block A - Queen Anne',4,3,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-1-07','Block B - Victoria',4,3,250000.00,'Full','2026-07-03 04:51:14'),(0,'VB-2-07','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-3-07','Block B - Victoria',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'FN-1-07','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-2-07','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-3-07','Block C - Florence Nightingale',4,2,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-1-07','Block D - Mary Seacole',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-2-07','Block D - Mary Seacole',4,3,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-3-07','Block D - Mary Seacole',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'MH-1-07','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-2-07','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-1-08','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'QA-2-08','Block A - Queen Anne',4,1,250000.00,'Full','2026-07-03 04:51:14'),(0,'QA-3-08','Block A - Queen Anne',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-1-08','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-2-08','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'VB-3-08','Block B - Victoria',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-1-08','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-2-08','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'FN-3-08','Block C - Florence Nightingale',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-1-08','Block D - Mary Seacole',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-2-08','Block D - Mary Seacole',4,1,250000.00,'Available','2026-07-03 04:51:14'),(0,'MS-3-08','Block D - Mary Seacole',4,0,250000.00,'Full','2026-07-03 04:51:14'),(0,'MH-1-08','Block E - Male Hostel',4,0,250000.00,'Available','2026-07-03 04:51:14'),(0,'MH-2-08','Block E - Male Hostel',4,1,250000.00,'Full','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `hostel_rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `improvement_tracking`
--

DROP TABLE IF EXISTS `improvement_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `improvement_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area` varchar(200) DEFAULT NULL,
  `improvement_action` text DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('planned','in_progress','completed') DEFAULT 'planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `improvement_tracking`
--

LOCK TABLES `improvement_tracking` WRITE;
/*!40000 ALTER TABLE `improvement_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `improvement_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `income_tax_rates`
--

DROP TABLE IF EXISTS `income_tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `income_tax_rates` (
  `id` int(11) NOT NULL,
  `tax_bracket_name` varchar(100) NOT NULL,
  `min_income` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_income` decimal(12,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fiscal_year` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `income_tax_rates`
--

LOCK TABLES `income_tax_rates` WRITE;
/*!40000 ALTER TABLE `income_tax_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `income_tax_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institutional_kpis`
--

DROP TABLE IF EXISTS `institutional_kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `institutional_kpis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kpi_name` varchar(300) DEFAULT NULL,
  `kpi_category` varchar(200) DEFAULT NULL,
  `target_value` decimal(14,2) DEFAULT NULL,
  `current_value` decimal(14,2) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `status` enum('on_track','at_risk','behind') DEFAULT 'on_track',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institutional_kpis`
--

LOCK TABLES `institutional_kpis` WRITE;
/*!40000 ALTER TABLE `institutional_kpis` DISABLE KEYS */;
/*!40000 ALTER TABLE `institutional_kpis` ENABLE KEYS */;
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
-- Table structure for table `lab_attendance`
--

DROP TABLE IF EXISTS `lab_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_attendance` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `attendance_status` enum('present','absent','late','excused') DEFAULT 'present',
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
-- Table structure for table `lab_consumables`
--

DROP TABLE IF EXISTS `lab_consumables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_consumables` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) NOT NULL DEFAULT 'pieces',
  `min_stock_level` decimal(10,2) DEFAULT 10.00,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(255) DEFAULT NULL,
  `last_ordered_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `id` int(11) NOT NULL,
  `equipment_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('mannequin','model','instrument','furniture','consumable','other') NOT NULL DEFAULT 'other',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `available_quantity` int(11) NOT NULL DEFAULT 1,
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `location` varchar(255) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `status` enum('active','maintenance','retired') DEFAULT 'active',
  `image_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `lab_equipment_checkouts`
--

DROP TABLE IF EXISTS `lab_equipment_checkouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_equipment_checkouts` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `checked_out_by` int(11) NOT NULL COMMENT 'staff_id',
  `checkout_date` datetime DEFAULT current_timestamp(),
  `expected_return_date` date NOT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `quantity_checked_out` int(11) NOT NULL DEFAULT 1,
  `quantity_returned` int(11) DEFAULT 0,
  `purpose` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('checked_out','returned','overdue','lost_damaged') DEFAULT 'checked_out',
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
-- Table structure for table `lab_incidents`
--

DROP TABLE IF EXISTS `lab_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_incidents` (
  `id` int(11) NOT NULL,
  `incident_date` date NOT NULL DEFAULT curdate(),
  `incident_time` time DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `incident_type` enum('injury','equipment_damage','safety_hazard','near_miss','other') NOT NULL DEFAULT 'other',
  `severity` enum('minor','moderate','serious','critical') DEFAULT 'minor',
  `description` text NOT NULL,
  `equipment_involved` varchar(255) DEFAULT NULL,
  `student_involved` varchar(255) DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `lab_practical_sessions`
--

DROP TABLE IF EXISTS `lab_practical_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_practical_sessions` (
  `id` int(11) NOT NULL,
  `session_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `max_students` int(11) DEFAULT 30,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `lab_skills_demonstrations`
--

DROP TABLE IF EXISTS `lab_skills_demonstrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_skills_demonstrations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `date_demonstrated` date NOT NULL DEFAULT curdate(),
  `competency` enum('exceeds_expectations','meets_expectations','needs_improvement','unsatisfactory') DEFAULT 'meets_expectations',
  `attempt_number` int(11) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
-- Table structure for table `late_payment_settings`
--

DROP TABLE IF EXISTS `late_payment_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `late_payment_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `late_payment_settings`
--

LOCK TABLES `late_payment_settings` WRITE;
/*!40000 ALTER TABLE `late_payment_settings` DISABLE KEYS */;
INSERT INTO `late_payment_settings` VALUES (1,'grace_period_days','15','Days after due date before late fee applies',NULL,'2026-06-21 08:58:13'),(2,'late_fee_percentage','5','Percentage penalty on outstanding amount',NULL,'2026-06-21 08:58:13'),(3,'late_fee_fixed','20000','Fixed late fee amount (UGX)',NULL,'2026-06-21 08:58:13'),(4,'max_late_fee','100000','Maximum late fee cap (UGX)',NULL,'2026-06-21 08:58:13');
/*!40000 ALTER TABLE `late_payment_settings` ENABLE KEYS */;
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
  KEY `idx_la_isbn` (`isbn`)
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
  `id` int(11) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `total_copies` int(11) DEFAULT 1,
  `available_copies` int(11) DEFAULT 1,
  `shelf_location` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_books`
--

LOCK TABLES `library_books` WRITE;
/*!40000 ALTER TABLE `library_books` DISABLE KEYS */;
INSERT INTO `library_books` VALUES (1,'Myles Textbook for Midwives','Jayne Marshall','978-0702051876','Elsevier',2021,'Textbook',6,5,'Section A - Shelf 1','2026-07-03 04:38:06'),(2,'Fundamentals of Nursing','Carol Taylor','978-1496384584','Wolters Kluwer',2022,'Textbook',10,8,'Section A - Shelf 2','2026-07-03 04:38:06'),(3,'Medical-Surgical Nursing','Donna Ignatavicius','978-0323596480','Elsevier',2021,'Textbook',5,4,'Section A - Shelf 3','2026-07-03 04:38:06'),(4,'Anatomy and Physiology for Nurses','Roger Watson','978-1608318023','Saunders',2020,'Textbook',7,6,'Section A - Shelf 4','2026-07-03 04:38:06'),(5,'Pharmacology for Nurses','Michael Weatherley','978-0702077111','Elsevier',2022,'Textbook',4,3,'Section A - Shelf 5','2026-07-03 04:38:06'),(6,'Psychiatric Mental Health Nursing','Mary Ann Boyd','978-1496309112','Wolters Kluwer',2021,'Textbook',5,4,'Section B - Shelf 1','2026-07-03 04:38:06'),(7,'Community Health Nursing','Mary Jo Clark','978-1284165210','Jones & Bartlett',2022,'Textbook',5,5,'Section B - Shelf 2','2026-07-03 04:38:06'),(8,'Maternal Child Nursing Care','Shannon Perry','978-1496309112','Elsevier',2022,'Textbook',6,6,'Section B - Shelf 3','2026-07-03 04:38:06'),(9,'Pediatric Nursing','Mary Jo Brancaglioni','978-1608317790','Saunders',2021,'Textbook',4,3,'Section B - Shelf 4','2026-07-03 04:38:06'),(10,'Clinical Skills for Nursing','Elizabeth Boahene','978-0702073144','Elsevier',2023,'Reference',5,5,'Section C - Shelf 1','2026-07-03 04:38:06'),(11,'Nursing Research Methods','Diane Polit','978-1119538639','Wolters Kluwer',2020,'Reference',4,4,'Section C - Shelf 2','2026-07-03 04:38:06'),(12,'Nursing Ethics & Professional Responsibility','Janie Butts','978-0323476638','Jones & Bartlett',2022,'Reference',3,3,'Section C - Shelf 3','2026-07-03 04:38:06'),(13,'Clinical Handbook of Fluids Electrolytes','Linda Honan','978-1496384591','Wolters Kluwer',2021,'Handbook',3,2,'Section C - Shelf 4','2026-07-03 04:38:06'),(14,'Nursing Diagnosis Handbook','Gail Ackley','978-0135218334','Elsevier',2022,'Handbook',7,6,'Section D - Shelf 1','2026-07-03 04:38:06'),(15,'UGANDA Nursing and Midwifery Council Guidelines','UNMC','978-1719643436','UNMC Press',2023,'Regulation',12,10,'Section D - Shelf 2','2026-07-03 04:38:06'),(16,'Oxford Dictionary of Medical Terms','Oxford University Press','978-0198765432','Oxford',2020,'Dictionary',3,3,'Reference Desk','2026-07-03 04:38:06'),(17,'Holes Human Anatomy & Physiology','David Shier','978-0143774617','McGraw Hill',2021,'Textbook',5,4,'Section A - Shelf 6','2026-07-03 04:38:06'),(18,'Lippincott Manual of Nursing Practice','Sandra Nettina','978-1605479767','Wolters Kluwer',2022,'Handbook',5,5,'Reference Desk','2026-07-03 04:38:06'),(19,'Brunner & Suddarths Textbook of Medical-Surgical Nursing','Janice Hinkle','978-0323555968','Wolters Kluwer',2022,'Textbook',8,6,'Section A - Shelf 7','2026-07-03 04:38:06'),(20,'Foundations of Nursing','Cooper Gosnell','978-0134444819','Elsevier',2020,'Textbook',8,7,'Section A - Shelf 8','2026-07-03 04:38:06'),(1,'Myles Textbook for Midwives','Jayne Marshall','978-0702051876','Elsevier',2021,'Textbook',6,5,'Section A - Shelf 1','2026-07-03 04:51:14'),(2,'Fundamentals of Nursing','Carol Taylor','978-1496384584','Wolters Kluwer',2022,'Textbook',10,8,'Section A - Shelf 2','2026-07-03 04:51:14'),(3,'Medical-Surgical Nursing','Donna Ignatavicius','978-0323596480','Elsevier',2021,'Textbook',5,4,'Section A - Shelf 3','2026-07-03 04:51:14'),(4,'Anatomy and Physiology for Nurses','Roger Watson','978-1608318023','Saunders',2020,'Textbook',7,6,'Section A - Shelf 4','2026-07-03 04:51:14'),(5,'Pharmacology for Nurses','Michael Weatherley','978-0702077111','Elsevier',2022,'Textbook',4,3,'Section A - Shelf 5','2026-07-03 04:51:14'),(6,'Psychiatric Mental Health Nursing','Mary Ann Boyd','978-1496309112','Wolters Kluwer',2021,'Textbook',5,4,'Section B - Shelf 1','2026-07-03 04:51:14'),(7,'Community Health Nursing','Mary Jo Clark','978-1284165210','Jones & Bartlett',2022,'Textbook',5,5,'Section B - Shelf 2','2026-07-03 04:51:14'),(8,'Maternal Child Nursing Care','Shannon Perry','978-1496309112','Elsevier',2022,'Textbook',6,6,'Section B - Shelf 3','2026-07-03 04:51:14'),(9,'Pediatric Nursing','Mary Jo Brancaglioni','978-1608317790','Saunders',2021,'Textbook',4,3,'Section B - Shelf 4','2026-07-03 04:51:14'),(10,'Clinical Skills for Nursing','Elizabeth Boahene','978-0702073144','Elsevier',2023,'Reference',5,5,'Section C - Shelf 1','2026-07-03 04:51:14'),(11,'Nursing Research Methods','Diane Polit','978-1119538639','Wolters Kluwer',2020,'Reference',4,4,'Section C - Shelf 2','2026-07-03 04:51:14'),(12,'Nursing Ethics & Professional Responsibility','Janie Butts','978-0323476638','Jones & Bartlett',2022,'Reference',3,3,'Section C - Shelf 3','2026-07-03 04:51:14'),(13,'Clinical Handbook of Fluids Electrolytes','Linda Honan','978-1496384591','Wolters Kluwer',2021,'Handbook',3,2,'Section C - Shelf 4','2026-07-03 04:51:14'),(14,'Nursing Diagnosis Handbook','Gail Ackley','978-0135218334','Elsevier',2022,'Handbook',7,6,'Section D - Shelf 1','2026-07-03 04:51:14'),(15,'UGANDA Nursing and Midwifery Council Guidelines','UNMC','978-1719643436','UNMC Press',2023,'Regulation',12,10,'Section D - Shelf 2','2026-07-03 04:51:14'),(16,'Oxford Dictionary of Medical Terms','Oxford University Press','978-0198765432','Oxford',2020,'Dictionary',3,3,'Reference Desk','2026-07-03 04:51:14'),(17,'Holes Human Anatomy & Physiology','David Shier','978-0143774617','McGraw Hill',2021,'Textbook',5,4,'Section A - Shelf 6','2026-07-03 04:51:14'),(18,'Lippincott Manual of Nursing Practice','Sandra Nettina','978-1605479767','Wolters Kluwer',2022,'Handbook',5,5,'Reference Desk','2026-07-03 04:51:14'),(19,'Brunner & Suddarths Textbook of Medical-Surgical Nursing','Janice Hinkle','978-0323555968','Wolters Kluwer',2022,'Textbook',8,6,'Section A - Shelf 7','2026-07-03 04:51:14'),(20,'Foundations of Nursing','Cooper Gosnell','978-0134444819','Elsevier',2020,'Textbook',8,7,'Section A - Shelf 8','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `library_books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_borrowing`
--

DROP TABLE IF EXISTS `library_borrowing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_borrowing` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date DEFAULT curdate(),
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_paid` tinyint(1) DEFAULT 0,
  `status` enum('Borrowed','Returned','Overdue','Lost') DEFAULT 'Borrowed',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_borrowing`
--

LOCK TABLES `library_borrowing` WRITE;
/*!40000 ALTER TABLE `library_borrowing` DISABLE KEYS */;
INSERT INTO `library_borrowing` VALUES (0,'ISNM/0001/25',3,'2024-10-21','2024-11-04','2024-09-22',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0002/25',11,'2024-10-02','2024-09-22',NULL,16217.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0003/25',10,'2024-09-29','2024-11-08',NULL,17106.00,1,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0004/25',19,'2024-09-30','2024-10-22','2024-10-11',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0005/25',12,'2024-09-24','2024-09-30',NULL,18876.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0006/25',1,'2024-10-20','2024-11-12','2024-09-19',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0007/25',5,'2024-09-20','2024-11-09','2024-10-10',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0008/25',16,'2024-10-26','2024-10-02','2024-10-21',1120.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0009/25',19,'2024-10-22','2024-10-17',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0010/25',17,'2024-09-23','2024-10-06','2024-09-25',13131.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0011/25',10,'2024-09-18','2024-09-19','2024-10-01',22094.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0012/25',17,'2024-09-11','2024-10-11','2024-09-21',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0013/25',8,'2024-09-11','2024-10-28',NULL,0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0014/25',13,'2024-09-01','2024-09-21','2024-10-09',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0015/25',12,'2024-10-28','2024-11-12',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0016/25',5,'2024-09-27','2024-10-19','2024-11-13',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0017/25',3,'2024-10-06','2024-10-19',NULL,9609.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0018/25',16,'2024-09-24','2024-10-24',NULL,7773.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0019/25',11,'2024-10-01','2024-09-16','2024-11-10',29770.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0020/25',7,'2024-10-28','2024-11-08','2024-10-09',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0021/25',18,'2024-10-19','2024-10-09','2024-10-25',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0022/25',4,'2024-09-23','2024-10-08','2024-09-15',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0023/25',2,'2024-10-25','2024-10-03','2024-09-19',37754.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0024/25',17,'2024-09-14','2024-10-22','2024-10-18',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0025/25',12,'2024-10-08','2024-10-04','2024-11-08',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0026/25',1,'2024-09-05','2024-10-05','2024-10-02',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0027/25',6,'2024-10-19','2024-09-30','2024-10-25',26520.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0028/25',6,'2024-10-05','2024-11-11',NULL,0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0029/25',7,'2024-10-23','2024-10-11','2024-10-15',30380.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0030/25',16,'2024-10-20','2024-10-30',NULL,0.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0031/25',12,'2024-10-28','2024-09-15',NULL,2161.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0032/25',12,'2024-09-26','2024-10-04','2024-11-01',8004.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0033/25',7,'2024-10-18','2024-09-18','2024-09-29',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0034/25',7,'2024-09-26','2024-09-26','2024-10-27',0.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0035/25',14,'2024-09-05','2024-10-05','2024-10-11',9984.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0036/25',15,'2024-10-29','2024-10-29','2024-10-12',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0037/25',15,'2024-09-29','2024-09-22',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0038/25',19,'2024-10-03','2024-11-08','2024-09-21',0.00,1,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0039/25',20,'2024-09-01','2024-09-20','2024-10-27',0.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0040/25',9,'2024-09-13','2024-11-01','2024-10-05',0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0041/24',7,'2024-10-01','2024-10-20','2024-10-16',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0042/24',14,'2024-10-30','2024-09-16',NULL,0.00,1,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0043/24',15,'2024-10-09','2024-09-15',NULL,6210.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0044/24',1,'2024-09-21','2024-10-21','2024-09-24',25020.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0045/24',15,'2024-10-28','2024-10-21',NULL,15663.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0046/24',17,'2024-09-24','2024-10-12',NULL,0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0047/24',11,'2024-09-10','2024-09-30','2024-09-30',34733.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0048/24',18,'2024-10-21','2024-10-19','2024-09-26',15672.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0049/24',6,'2024-10-04','2024-11-11',NULL,0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0050/24',14,'2024-09-13','2024-09-15','2024-09-29',22160.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0051/24',14,'2024-09-12','2024-11-05','2024-09-25',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0052/24',10,'2024-10-22','2024-11-11',NULL,10653.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0053/24',8,'2024-09-04','2024-09-21','2024-10-22',49151.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0054/24',1,'2024-09-05','2024-10-05','2024-10-14',5302.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0055/24',17,'2024-09-14','2024-10-22','2024-10-18',0.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0056/24',15,'2024-09-14','2024-11-13',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0057/24',14,'2024-10-15','2024-10-25',NULL,14981.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0058/24',19,'2024-09-25','2024-09-28','2024-11-08',1890.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0059/24',5,'2024-09-18','2024-11-02',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0060/24',12,'2024-10-30','2024-09-26',NULL,0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0061/24',12,'2024-10-28','2024-09-16',NULL,0.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0062/24',14,'2024-09-27','2024-09-26','2024-10-14',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0063/24',16,'2024-09-10','2024-10-16',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0064/24',11,'2024-09-18','2024-11-04','2024-09-23',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0065/24',12,'2024-09-11','2024-09-23',NULL,0.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0066/24',3,'2024-09-08','2024-09-29','2024-10-15',17001.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0067/24',6,'2024-10-07','2024-09-26',NULL,0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0068/24',10,'2024-10-24','2024-09-15','2024-11-04',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0069/24',7,'2024-10-11','2024-10-07','2024-09-16',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0070/24',6,'2024-10-28','2024-11-08','2024-10-24',0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0071/24',5,'2024-09-12','2024-10-02','2024-10-21',0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0072/24',13,'2024-10-27','2024-11-03',NULL,3299.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0073/24',4,'2024-10-20','2024-10-26','2024-10-18',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0074/24',18,'2024-09-01','2024-10-04','2024-09-18',0.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0075/24',13,'2024-09-11','2024-09-21',NULL,45653.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0076/24',7,'2024-10-26','2024-10-23','2024-09-25',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0077/24',12,'2024-09-19','2024-11-06','2024-10-18',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0078/24',14,'2024-09-19','2024-10-15','2024-10-11',0.00,1,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0079/24',5,'2024-09-30','2024-10-25','2024-10-21',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0080/24',19,'2024-10-11','2024-10-22',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0081/24',9,'2024-10-02','2024-10-09','2024-11-07',0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0082/24',13,'2024-10-09','2024-10-07','2024-10-25',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0083/24',5,'2024-10-07','2024-10-11','2024-10-22',49951.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0084/24',12,'2024-10-25','2024-11-05','2024-10-09',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0085/24',3,'2024-10-19','2024-10-29',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0086/24',7,'2024-10-28','2024-11-05','2024-10-20',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0087/24',19,'2024-10-06','2024-09-20','2024-10-01',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0088/24',8,'2024-10-07','2024-11-03','2024-09-28',0.00,1,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0089/24',19,'2024-10-14','2024-11-09','2024-09-30',0.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0090/24',7,'2024-09-23','2024-11-09','2024-11-03',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0091/23',5,'2024-10-01','2024-11-06','2024-10-23',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0092/23',6,'2024-09-07','2024-10-29','2024-10-22',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0093/23',2,'2024-09-12','2024-10-26','2024-10-17',2063.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0094/23',7,'2024-10-21','2024-09-29','2024-11-09',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0095/23',16,'2024-09-21','2024-10-13',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0096/23',10,'2024-09-24','2024-10-18','2024-10-14',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0097/23',2,'2024-09-23','2024-10-24',NULL,49840.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0098/23',20,'2024-10-14','2024-10-31','2024-09-26',29904.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0099/23',16,'2024-09-01','2024-10-25','2024-10-22',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0100/23',7,'2024-09-02','2024-09-23','2024-10-27',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0101/23',7,'2024-10-16','2024-10-31','2024-10-25',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0102/23',17,'2024-10-17','2024-10-05','2024-11-05',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0103/23',20,'2024-09-18','2024-10-19','2024-09-28',0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0104/23',16,'2024-10-05','2024-10-13','2024-09-15',42644.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0105/23',13,'2024-09-27','2024-09-28','2024-10-17',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0106/23',15,'2024-10-12','2024-09-28',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0107/23',3,'2024-10-02','2024-09-27','2024-10-18',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0108/23',5,'2024-10-09','2024-10-15','2024-09-30',0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0109/23',16,'2024-10-05','2024-10-15','2024-11-02',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0110/23',18,'2024-09-20','2024-09-20','2024-09-20',0.00,1,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0111/23',20,'2024-10-04','2024-11-08','2024-10-21',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0112/23',3,'2024-09-14','2024-11-05','2024-10-01',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0113/23',16,'2024-10-02','2024-10-02','2024-10-27',42292.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0114/23',16,'2024-09-01','2024-10-22',NULL,10486.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0115/23',18,'2024-10-02','2024-09-19','2024-10-31',0.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0116/23',3,'2024-09-30','2024-09-21',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0117/23',4,'2024-10-14','2024-09-22','2024-11-09',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0118/23',9,'2024-10-03','2024-10-11','2024-10-13',6480.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0119/23',20,'2024-10-25','2024-10-23','2024-10-20',0.00,0,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0120/23',11,'2024-09-22','2024-09-30',NULL,0.00,1,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0121/23',17,'2024-09-22','2024-10-03','2024-10-01',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0122/23',1,'2024-09-30','2024-10-10','2024-10-28',3372.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0123/23',7,'2024-09-23','2024-11-07','2024-09-26',22043.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0124/23',4,'2024-10-10','2024-11-03',NULL,0.00,1,'Overdue','2026-07-03 04:51:14'),(0,'ISNM/0125/23',7,'2024-10-20','2024-09-25','2024-10-06',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0126/23',10,'2024-09-08','2024-09-21',NULL,0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0127/23',20,'2024-10-30','2024-09-18','2024-10-24',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0128/23',7,'2024-10-25','2024-10-22','2024-09-22',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0129/23',12,'2024-09-20','2024-09-15',NULL,0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0130/23',12,'2024-09-09','2024-11-10',NULL,0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0131/23',10,'2024-09-18','2024-09-19','2024-09-30',9311.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0132/23',16,'2024-09-28','2024-11-10','2024-10-30',41121.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0133/23',6,'2024-10-21','2024-10-07','2024-10-26',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0134/23',2,'2024-10-12','2024-09-28',NULL,0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0135/23',16,'2024-10-22','2024-09-17','2024-11-01',0.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0136/23',6,'2024-09-27','2024-10-05','2024-11-05',0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0137/23',3,'2024-10-30','2024-10-22',NULL,36401.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0138/23',8,'2024-09-11','2024-10-31','2024-10-01',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0139/23',11,'2024-09-27','2024-10-20','2024-10-05',1440.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0140/23',10,'2024-10-20','2024-10-24','2024-09-17',47909.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0141/23',2,'2024-10-22','2024-09-23',NULL,0.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0142/23',9,'2024-10-20','2024-11-06','2024-11-06',0.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0143/23',5,'2024-09-04','2024-10-21','2024-10-19',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0144/23',7,'2024-10-19','2024-09-15','2024-11-09',34942.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0145/23',1,'2024-10-03','2024-10-21','2024-09-24',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0146/23',11,'2024-09-30','2024-11-09',NULL,0.00,0,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0147/23',10,'2024-09-01','2024-10-19','2024-10-18',0.00,0,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0148/23',20,'2024-10-17','2024-09-15','2024-10-18',0.00,1,'Returned','2026-07-03 04:51:14'),(0,'ISNM/0149/23',12,'2024-09-15','2024-10-18',NULL,0.00,1,'Borrowed','2026-07-03 04:51:14'),(0,'ISNM/0150/23',17,'2024-10-08','2024-10-30','2024-09-21',15153.00,1,'Returned','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `library_borrowing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_fines`
--

DROP TABLE IF EXISTS `library_fines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_fines` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_title` varchar(200) DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_fines`
--

LOCK TABLES `library_fines` WRITE;
/*!40000 ALTER TABLE `library_fines` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_fines` ENABLE KEYS */;
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
INSERT INTO `medicine_stock` VALUES (1,'PARA001','Paracetamol','Acetaminophen','Painkiller','Tablet','500mg',NULL,NULL,200,'tablets',50,50.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A1',0,'1-2 tablets every 4-6 hours as needed for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(2,'IBU001','Ibuprofen','Ibuprofen','Anti-inflammatory','Tablet','400mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2027-10-31','Cabinet A1',0,'1 tablet 3 times daily after meals',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(3,'AMOX001','Amoxicillin','Amoxicillin','Antibiotic','Capsule','500mg',NULL,NULL,100,'capsules',20,200.00,NULL,'UGX',NULL,'2027-08-31','Cabinet B1',1,'1 capsule 3 times daily for 7 days',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(4,'CTM001','Chlorpheniramine','Chlorpheniramine Maleate','Allergy','Tablet','4mg',NULL,NULL,100,'tablets',20,50.00,NULL,'UGX',NULL,'2027-11-30','Cabinet A2',0,'1 tablet every 4-6 hours for allergies',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(5,'ORS001','Oral Rehydration Salts','ORS','Other','Powder','20.5g/sachet',NULL,NULL,100,'sachets',30,500.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Dissolve 1 sachet in 1L water, drink after each loose stool',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(6,'ART001','Artemether/Lumefantrine','Coartem','Antimalarial','Tablet','20/120mg',NULL,NULL,60,'tablets',20,1500.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B2',1,'4 tablets twice daily for 3 days',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(7,'VITC001','Vitamin C','Ascorbic Acid','Vitamins','Tablet','500mg',NULL,NULL,300,'tablets',50,30.00,NULL,'UGX',NULL,'2028-12-31','Cabinet C1',0,'1 tablet daily for immune support',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(8,'MET001','Metered Dose Inhaler','Salbutamol','Respiratory','Inhaler','100mcg/dose',NULL,NULL,10,'inhalers',3,15000.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A3',1,'1-2 puffs as needed for asthma symptoms',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(9,'ANT001','Antacid','Aluminum/Magnesium Hydroxide','Digestive','Tablet','500mg',NULL,NULL,200,'tablets',40,100.00,NULL,'UGX',NULL,'2027-11-30','Cabinet C1',0,'1-2 tablets after meals or when symptomatic',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(10,'HYD001','Hydrocortisone Cream','Hydrocortisone','Dermatological','Cream','1%',NULL,NULL,20,'tubes',5,5000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply thin layer to affected area 2-3 times daily',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(11,'DIA001','Diazepam','Diazepam','Painkiller','Tablet','5mg',NULL,NULL,30,'tablets',10,200.00,NULL,'UGX',NULL,'2026-12-31','Cabinet B2',1,'1 tablet at bedtime for anxiety or muscle spasms',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(12,'BAN001','Bandages','Cotton Bandage','First Aid','Other','4 inches x 5 meters',NULL,NULL,50,'rolls',10,1500.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'For wound dressing and injury management',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(13,'GAU001','Gauze Swabs','Sterile Gauze','First Aid','Other','10x10cm',NULL,NULL,200,'packs',50,800.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'Sterile swabs for wound cleaning and dressing',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(14,'GLU001','Glucose Powder','Dextrose','Vitamins','Powder','500g',NULL,NULL,10,'packs',3,5000.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Mix 2 tablespoons in water for energy',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(15,'ALC001','Alcohol Swabs','Isopropyl Alcohol','First Aid','Solution','70%',NULL,NULL,300,'swabs',50,100.00,NULL,'UGX',NULL,'2028-12-31','Shelf E1',0,'Use for cleaning skin before injections',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(16,'CLO001','Chloroquine','Chloroquine Phosphate','Antimalarial','Tablet','250mg',NULL,NULL,50,'tablets',15,300.00,NULL,'UGX',NULL,'2027-05-31','Cabinet B2',1,'As prescribed for malaria treatment',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(17,'MEF001','Mefenamic Acid','Mefenamic Acid','Painkiller','Capsule','500mg',NULL,NULL,80,'capsules',20,200.00,NULL,'UGX',NULL,'2027-07-31','Cabinet A1',0,'1 capsule 3 times daily for pain and inflammation',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(18,'METR001','Metronidazole','Metronidazole','Antibiotic','Tablet','400mg',NULL,NULL,100,'tablets',20,150.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B1',1,'1 tablet 3 times daily for 5-7 days',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(19,'DIC001','Diclofenac Gel','Diclofenac Diethylamine','Anti-inflammatory','Cream','1%',NULL,NULL,15,'tubes',5,7000.00,NULL,'UGX',NULL,'2027-10-31','Cabinet D1',0,'Apply to affected area 3-4 times daily',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(20,'CET001','Cetirizine','Cetirizine Hydrochloride','Allergy','Tablet','10mg',NULL,NULL,100,'tablets',20,100.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A2',0,'1 tablet daily for allergy symptoms',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(21,'ASP001','Aspirin','Acetylsalicylic Acid','Painkiller','Tablet','300mg',NULL,NULL,100,'tablets',25,50.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A1',0,'1-2 tablets every 4-6 hours for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(22,'ZIN001','Zinc Tablets','Zinc Sulfate','Vitamins','Tablet','20mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2028-09-30','Cabinet C1',0,'1 tablet daily for immune support and wound healing',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(23,'CLOT001','Clotrimazole Cream','Clotrimazole','Antifungal','Cream','1%',NULL,NULL,15,'tubes',5,4000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply to affected area twice daily for 2 weeks',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(24,'EYE001','Eye Drops','Chloramphenicol','Other','Drops','0.5%',NULL,NULL,20,'bottles',5,5000.00,NULL,'UGX',NULL,'2027-04-30','Cabinet A3',1,'1-2 drops in affected eye every 2-4 hours',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(25,'BET001','Betadine Solution','Povidone-Iodine','First Aid','Solution','10%',NULL,NULL,10,'bottles',3,8000.00,NULL,'UGX',NULL,'2028-03-31','Shelf E1',0,'Apply to wounds for disinfection',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19');
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
-- Table structure for table `meeting_actions`
--

DROP TABLE IF EXISTS `meeting_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) DEFAULT NULL,
  `action_item` text DEFAULT NULL,
  `assigned_to` varchar(200) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_actions`
--

LOCK TABLES `meeting_actions` WRITE;
/*!40000 ALTER TABLE `meeting_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `meeting_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meeting_attendees`
--

DROP TABLE IF EXISTS `meeting_attendees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_attendees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) NOT NULL,
  `attendee_name` varchar(200) DEFAULT NULL,
  `attendee_role` varchar(100) DEFAULT NULL,
  `attended` enum('pending','present','absent') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_attendees`
--

LOCK TABLES `meeting_attendees` WRITE;
/*!40000 ALTER TABLE `meeting_attendees` DISABLE KEYS */;
/*!40000 ALTER TABLE `meeting_attendees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meeting_minutes`
--

DROP TABLE IF EXISTS `meeting_minutes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_minutes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) DEFAULT NULL,
  `agenda_item` varchar(300) DEFAULT NULL,
  `discussion` text DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `action_items` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_minutes`
--

LOCK TABLES `meeting_minutes` WRITE;
/*!40000 ALTER TABLE `meeting_minutes` DISABLE KEYS */;
/*!40000 ALTER TABLE `meeting_minutes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(300) DEFAULT NULL,
  `meeting_type` varchar(100) DEFAULT NULL,
  `meeting_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `agenda` text DEFAULT NULL,
  `minutes` text DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meetings`
--

LOCK TABLES `meetings` WRITE;
/*!40000 ALTER TABLE `meetings` DISABLE KEYS */;
/*!40000 ALTER TABLE `meetings` ENABLE KEYS */;
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `id` int(11) NOT NULL,
  `notification_type` enum('fee_reminder','payment_received','invoice_generated','budget_alert','system') DEFAULT 'system',
  `recipient_type` enum('student','staff','bursar') NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `channel` enum('email','sms','in_app') DEFAULT 'in_app',
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
-- Table structure for table `official_letters`
--

DROP TABLE IF EXISTS `official_letters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `official_letters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `letter_type` varchar(100) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `title` varchar(300) DEFAULT NULL,
  `recipient_name` varchar(200) DEFAULT NULL,
  `recipient_address` text DEFAULT NULL,
  `body` text DEFAULT NULL,
  `letter_date` date DEFAULT NULL,
  `issued_by` varchar(200) DEFAULT NULL,
  `file_name` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','issued','signed','archived') DEFAULT 'draft',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `official_letters`
--

LOCK TABLES `official_letters` WRITE;
/*!40000 ALTER TABLE `official_letters` DISABLE KEYS */;
/*!40000 ALTER TABLE `official_letters` ENABLE KEYS */;
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
-- Table structure for table `payment_receipts`
--

DROP TABLE IF EXISTS `payment_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_receipts` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_date` timestamp NULL DEFAULT current_timestamp(),
  `issued_by` int(11) DEFAULT NULL,
  `voided` tinyint(1) DEFAULT 0,
  `voided_at` timestamp NULL DEFAULT NULL,
  `voided_by` int(11) DEFAULT NULL,
  `void_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_receipts`
--

LOCK TABLES `payment_receipts` WRITE;
/*!40000 ALTER TABLE `payment_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_subscriptions`
--

DROP TABLE IF EXISTS `payment_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_subscriptions` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `subscription_type` enum('fee_installment','hostel','library','other') NOT NULL DEFAULT 'fee_installment',
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'eg: fee_structure_id, hostel_room_id',
  `reference_id` int(11) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `installment_amount` decimal(12,2) NOT NULL,
  `frequency` enum('monthly','weekly','quarterly') NOT NULL DEFAULT 'monthly',
  `total_installments` int(11) NOT NULL,
  `installments_collected` int(11) NOT NULL DEFAULT 0,
  `start_date` date NOT NULL DEFAULT curdate(),
  `next_due_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `payment_method` enum('mobile_money','bank','cash') DEFAULT 'mobile_money',
  `payment_provider` varchar(50) DEFAULT NULL COMMENT 'mtn_momo, airtel_money, etc.',
  `phone_number` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `status` enum('active','paused','completed','cancelled','failed') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL COMMENT 'student_id or staff_id who created',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `id` int(11) NOT NULL,
  `payment_reference` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `amount_received` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Mobile Money','Cheque','Card','Other') DEFAULT 'Cash',
  `payment_date` date DEFAULT curdate(),
  `transaction_ref` varchar(100) DEFAULT NULL,
  `slip_number` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Completed','Failed','Reversed') DEFAULT 'Completed',
  `received_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `idx_pay_student_date` (`student_id`,`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (0,'PAY000000-01',0,NULL,112982.00,'Cheque','2024-09-19','TXN185821','SLIP452361','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,452173.00,'Cash','2024-09-12','TXN57050','SLIP154655','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,401064.00,'Bank Transfer','2024-09-25','TXN994875','SLIP193444','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,591296.00,'Mobile Money','2024-09-12','TXN579063','SLIP749140','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,104255.00,'Cheque','2024-09-27','TXN365248','SLIP975855','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,491767.00,'Cheque','2024-09-05','TXN29359','SLIP346983','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,423422.00,'Cash','2024-08-02','TXN549244','SLIP668795','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,448121.00,'Mobile Money','2024-08-18','TXN2624','SLIP156850','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,488190.00,'Mobile Money','2024-09-13','TXN403976','SLIP837069','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,586709.00,'Mobile Money','2024-09-21','TXN228228','SLIP563608','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,166678.00,'Cheque','2024-08-29','TXN470825','SLIP914898','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,181010.00,'Cash','2024-09-20','TXN8672','SLIP520420','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,388045.00,'Mobile Money','2024-09-22','TXN380779','SLIP300892','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,281062.00,'Cheque','2024-08-28','TXN542949','SLIP354677','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,172268.00,'Bank Transfer','2024-09-21','TXN322303','SLIP32560','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,197947.00,'Cheque','2024-09-19','TXN460909','SLIP840787','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,510605.00,'Bank Transfer','2024-08-28','TXN523054','SLIP250796','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,442409.00,'Bank Transfer','2024-08-19','TXN505275','SLIP614138','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,377433.00,'Cheque','2024-09-29','TXN179283','SLIP911385','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,109540.00,'Mobile Money','2024-09-14','TXN661256','SLIP59283','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,256323.00,'Mobile Money','2024-09-29','TXN788703','SLIP976610','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,358473.00,'Bank Transfer','2024-09-13','TXN653532','SLIP96729','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,361524.00,'Mobile Money','2024-08-04','TXN305577','SLIP359433','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,540217.00,'Mobile Money','2024-09-28','TXN918631','SLIP659016','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,369593.00,'Bank Transfer','2024-09-28','TXN727611','SLIP707505','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,277346.00,'Bank Transfer','2024-08-12','TXN437','SLIP430218','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,174892.00,'Mobile Money','2024-09-20','TXN835109','SLIP649601','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,471340.00,'Cheque','2024-09-05','TXN680892','SLIP619658','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,127806.00,'Mobile Money','2024-09-25','TXN385837','SLIP143321','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,379546.00,'Mobile Money','2024-08-10','TXN654597','SLIP822333','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,173937.00,'Mobile Money','2024-09-25','TXN774007','SLIP115371','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,227416.00,'Cheque','2024-09-22','TXN594636','SLIP345909','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,572820.00,'Bank Transfer','2024-09-06','TXN5759','SLIP182494','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,547597.00,'Cheque','2024-09-27','TXN998943','SLIP124064','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,411745.00,'Bank Transfer','2024-09-21','TXN43402','SLIP649496','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,158640.00,'Bank Transfer','2024-09-20','TXN274889','SLIP861266','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,340835.00,'Cheque','2024-09-10','TXN915087','SLIP542193','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,582853.00,'Cash','2024-08-07','TXN957306','SLIP448642','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,285645.00,'Bank Transfer','2024-08-27','TXN662160','SLIP994565','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,593175.00,'Cheque','2024-09-16','TXN61976','SLIP966195','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,422525.00,'Mobile Money','2024-09-11','TXN510853','SLIP459760','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,483120.00,'Mobile Money','2024-09-27','TXN448747','SLIP361025','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,329442.00,'Cash','2024-09-10','TXN766386','SLIP791675','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,429609.00,'Cheque','2024-09-07','TXN375161','SLIP992792','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,519241.00,'Cash','2024-09-03','TXN131486','SLIP993281','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,385976.00,'Cheque','2024-09-11','TXN778843','SLIP843068','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,539406.00,'Cheque','2024-09-11','TXN844710','SLIP159982','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,232890.00,'Cheque','2024-08-27','TXN690312','SLIP109257','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,337675.00,'Cash','2024-09-19','TXN947247','SLIP279733','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,378461.00,'Cheque','2024-08-04','TXN445233','SLIP57287','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,575368.00,'Bank Transfer','2024-08-04','TXN539039','SLIP524496','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,102681.00,'Mobile Money','2024-08-16','TXN892636','SLIP711627','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,540113.00,'Mobile Money','2024-09-11','TXN654040','SLIP198536','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,115279.00,'Bank Transfer','2024-09-11','TXN799708','SLIP915784','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,189900.00,'Cash','2024-08-14','TXN639185','SLIP539456','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,489863.00,'Mobile Money','2024-08-04','TXN469964','SLIP163364','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,303464.00,'Bank Transfer','2024-08-31','TXN876105','SLIP874684','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,472552.00,'Cash','2024-08-17','TXN55764','SLIP462719','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,173152.00,'Mobile Money','2024-08-17','TXN359489','SLIP963694','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,470004.00,'Cheque','2024-09-19','TXN696938','SLIP10409','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,580614.00,'Cheque','2024-09-29','TXN629730','SLIP175967','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,595321.00,'Mobile Money','2024-08-10','TXN497263','SLIP22395','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,410093.00,'Cash','2024-08-19','TXN439689','SLIP273883','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,125174.00,'Mobile Money','2024-09-29','TXN706901','SLIP536192','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,380129.00,'Cash','2024-08-17','TXN835804','SLIP330661','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,172947.00,'Bank Transfer','2024-08-15','TXN36256','SLIP432056','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,125757.00,'Cheque','2024-09-09','TXN378085','SLIP933055','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,365513.00,'Cheque','2024-09-11','TXN865787','SLIP268733','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,473152.00,'Cheque','2024-08-24','TXN162490','SLIP649382','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,479721.00,'Cheque','2024-09-28','TXN287759','SLIP537826','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,512929.00,'Bank Transfer','2024-08-07','TXN959992','SLIP495507','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,398780.00,'Bank Transfer','2024-09-12','TXN64784','SLIP182745','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,459686.00,'Cash','2024-08-06','TXN279239','SLIP141120','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,533943.00,'Cheque','2024-09-28','TXN135305','SLIP746412','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,263075.00,'Mobile Money','2024-09-28','TXN721026','SLIP667795','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,187949.00,'Cheque','2024-09-21','TXN635814','SLIP620597','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,197772.00,'Cash','2024-09-29','TXN617265','SLIP107300','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,442350.00,'Cash','2024-08-28','TXN964868','SLIP462530','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,309023.00,'Bank Transfer','2024-08-16','TXN187408','SLIP159845','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,218499.00,'Bank Transfer','2024-09-18','TXN965094','SLIP376594','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,593844.00,'Cheque','2024-08-05','TXN975160','SLIP635122','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,225066.00,'Mobile Money','2024-09-28','TXN844484','SLIP294204','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,568783.00,'Cheque','2024-08-13','TXN651365','SLIP616612','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,164482.00,'Cheque','2024-09-05','TXN555304','SLIP12358','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,297939.00,'Cheque','2024-09-01','TXN792848','SLIP392359','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,391626.00,'Bank Transfer','2024-09-26','TXN513271','SLIP727862','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,149749.00,'Mobile Money','2024-08-17','TXN413473','SLIP254253','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,115423.00,'Mobile Money','2024-09-21','TXN149713','SLIP154089','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,260652.00,'Cash','2024-09-15','TXN354122','SLIP498471','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,314996.00,'Bank Transfer','2024-09-28','TXN950128','SLIP802384','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,180769.00,'Mobile Money','2024-09-01','TXN388810','SLIP389795','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,491273.00,'Bank Transfer','2024-08-23','TXN615403','SLIP969754','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,101281.00,'Cash','2024-08-31','TXN239723','SLIP668388','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,411387.00,'Cash','2024-09-10','TXN49932','SLIP224031','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,585180.00,'Cash','2024-09-29','TXN398114','SLIP28238','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,573422.00,'Bank Transfer','2024-08-25','TXN86553','SLIP211716','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,499462.00,'Mobile Money','2024-08-25','TXN924480','SLIP420672','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,264959.00,'Mobile Money','2024-09-26','TXN577947','SLIP45327','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,346398.00,'Mobile Money','2024-08-10','TXN823995','SLIP635188','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,451978.00,'Bank Transfer','2024-09-27','TXN953355','SLIP889182','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,392924.00,'Mobile Money','2024-09-03','TXN969597','SLIP195177','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,133546.00,'Bank Transfer','2024-09-02','TXN492162','SLIP815635','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,400844.00,'Bank Transfer','2024-08-01','TXN328552','SLIP634856','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,194314.00,'Cash','2024-09-07','TXN19169','SLIP214915','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,108534.00,'Mobile Money','2024-08-10','TXN437172','SLIP730477','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,270437.00,'Bank Transfer','2024-09-02','TXN171501','SLIP231320','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,421050.00,'Bank Transfer','2024-09-09','TXN732327','SLIP692476','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,232700.00,'Cash','2024-08-28','TXN509594','SLIP192985','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,318071.00,'Bank Transfer','2024-09-12','TXN696495','SLIP381423','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,508815.00,'Cheque','2024-08-16','TXN500957','SLIP705223','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,111624.00,'Cash','2024-09-25','TXN663854','SLIP519932','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,404050.00,'Mobile Money','2024-09-04','TXN453938','SLIP532066','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,249258.00,'Cheque','2024-09-05','TXN242755','SLIP454628','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,372438.00,'Mobile Money','2024-08-11','TXN757753','SLIP285226','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,176435.00,'Cheque','2024-08-06','TXN697861','SLIP234972','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,140637.00,'Bank Transfer','2024-08-16','TXN212994','SLIP274547','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,466878.00,'Cheque','2024-08-02','TXN586676','SLIP860129','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,370310.00,'Cash','2024-09-29','TXN590429','SLIP977002','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,156863.00,'Bank Transfer','2024-09-20','TXN321851','SLIP68424','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,288281.00,'Bank Transfer','2024-08-16','TXN257578','SLIP513761','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,498035.00,'Mobile Money','2024-09-18','TXN718563','SLIP171348','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,450525.00,'Cheque','2024-09-21','TXN290773','SLIP895231','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,401920.00,'Mobile Money','2024-09-21','TXN279560','SLIP829752','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,255041.00,'Cash','2024-08-23','TXN694181','SLIP344320','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,419529.00,'Cash','2024-09-23','TXN985444','SLIP243760','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,231232.00,'Bank Transfer','2024-08-08','TXN846018','SLIP876600','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,522475.00,'Bank Transfer','2024-08-27','TXN414667','SLIP753624','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,362061.00,'Mobile Money','2024-08-14','TXN52339','SLIP582772','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,478423.00,'Cash','2024-09-24','TXN437346','SLIP459687','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,593198.00,'Bank Transfer','2024-09-18','TXN368287','SLIP425211','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,110597.00,'Cheque','2024-08-06','TXN949479','SLIP483110','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,383555.00,'Mobile Money','2024-08-14','TXN990191','SLIP261651','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,268839.00,'Cheque','2024-08-31','TXN810567','SLIP540302','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,234905.00,'Bank Transfer','2024-09-19','TXN971990','SLIP366103','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,557271.00,'Mobile Money','2024-09-07','TXN718827','SLIP708906','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,294026.00,'Cheque','2024-09-24','TXN77225','SLIP675408','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,172685.00,'Bank Transfer','2024-08-05','TXN233151','SLIP964746','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,162140.00,'Bank Transfer','2024-08-16','TXN133294','SLIP877607','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,594080.00,'Mobile Money','2024-09-04','TXN953140','SLIP39460','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,268938.00,'Bank Transfer','2024-09-20','TXN493885','SLIP945292','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,222405.00,'Mobile Money','2024-08-13','TXN867701','SLIP719174','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,596383.00,'Cheque','2024-08-04','TXN847393','SLIP77170','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,521835.00,'Cheque','2024-08-25','TXN55381','SLIP67372','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,185358.00,'Bank Transfer','2024-09-14','TXN771488','SLIP621914','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,497554.00,'Cash','2024-08-10','TXN488958','SLIP953777','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,251008.00,'Bank Transfer','2024-08-21','TXN742253','SLIP698166','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,232036.00,'Cash','2024-08-21','TXN7868','SLIP28071','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,158376.00,'Mobile Money','2024-08-09','TXN238790','SLIP751489','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,120539.00,'Cheque','2024-09-07','TXN304142','SLIP626565','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'PAY000000-01',0,NULL,210201.00,'Cash','2024-08-28','TXN584840','SLIP573138','Completed',25,'Tuition Fee Payment','2026-07-03 04:51:14','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_approvals`
--

DROP TABLE IF EXISTS `payroll_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) DEFAULT 0,
  `request_type` varchar(50) DEFAULT NULL,
  `requested_by` int(11) DEFAULT 0,
  `amount` decimal(14,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
  `approver_id` int(11) DEFAULT 0,
  `approver_name` varchar(200) DEFAULT NULL,
  `approver_comments` text DEFAULT NULL,
  `escalated_to` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
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
-- Table structure for table `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `gross_salary` decimal(12,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) DEFAULT 0.00,
  `processed_by` int(11) DEFAULT 0,
  `processing_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('Draft','Processed','Approved','Paid') DEFAULT 'Draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_records`
--

LOCK TABLES `payroll_records` WRITE;
/*!40000 ALTER TABLE `payroll_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslips` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `payroll_record_id` int(11) DEFAULT 0,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `status` enum('Generated','Sent','Paid') DEFAULT 'Generated',
  `generated_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslips`
--

LOCK TABLES `payslips` WRITE;
/*!40000 ALTER TABLE `payslips` DISABLE KEYS */;
/*!40000 ALTER TABLE `payslips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penalty_configurations`
--

DROP TABLE IF EXISTS `penalty_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `penalty_configurations` (
  `id` int(11) NOT NULL,
  `penalty_name` varchar(100) NOT NULL,
  `penalty_type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penalty_configurations`
--

LOCK TABLES `penalty_configurations` WRITE;
/*!40000 ALTER TABLE `penalty_configurations` DISABLE KEYS */;
INSERT INTO `penalty_configurations` VALUES (1,'Late Registration','Late Fee',50000.00,'Penalty for late course registration',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(2,'Late Payment (1-7 days)','Late Fee',10000.00,'Penalty for fee payment 1-7 days after due date',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(3,'Late Payment (8-14 days)','Late Fee',25000.00,'Penalty for fee payment 8-14 days after due date',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(4,'Late Payment (15+ days)','Late Fee',50000.00,'Penalty for fee payment more than 15 days after due date',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(5,'Lost Library Book','Replacement',30000.00,'Replacement fee for lost library book',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(6,'Damaged Property','Damage',20000.00,'Penalty for damaging school property',1,'2026-06-14 19:51:20','2026-06-14 19:51:20'),(7,'ID Card Replacement','Administrative',10000.00,'Fee for replacement of lost student ID card',1,'2026-06-14 19:51:20','2026-06-14 19:51:20');
/*!40000 ALTER TABLE `penalty_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `principal_notices`
--

DROP TABLE IF EXISTS `principal_notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `principal_notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(300) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `published_by` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `principal_notices`
--

LOCK TABLES `principal_notices` WRITE;
/*!40000 ALTER TABLE `principal_notices` DISABLE KEYS */;
INSERT INTO `principal_notices` VALUES (1,'APPLICATIONS NOW OPEN!','Dear Prospective Students, Parents, and Guardians,\r\nOn behalf of Iganga School of Nursing and Midwifery, I am delighted to announce that applications for admission are now officially open.\r\nIf you aspire to build a rewarding career in nursing or midwifery and become a compassionate, skilled healthcare professional, this is your opportunity to join a respected institution committed to academic excellence, professionalism, and quality healthcare training.\r\nWe warmly invite all qualified applicants to apply and take the first step toward a fulfilling future in the healthcare profession.\r\nWhy Choose Iganga School of Nursing and Midwifery?\r\nQuality nursing and midwifery education\r\nExperienced and dedicated tutors\r\nPractical, hands-on clinical training\r\nA supportive learning environment\r\nCommitment to excellence and professional growth\r\nApply today and secure your place for the upcoming intake.\r\nFor application procedures and further inquiries, please contact the admissions office or visit the school during working hours.\r\nWe look forward to welcoming you to the Iganga School of Nursing and Midwifery family.','All','School Principal','2026-06-28 09:36:43');
/*!40000 ALTER TABLE `principal_notices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `procurement_requests`
--

DROP TABLE IF EXISTS `procurement_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `procurement_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pr_number` varchar(100) DEFAULT NULL,
  `title` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT 0.00,
  `department` varchar(200) DEFAULT NULL,
  `supplier_name` varchar(200) DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected') DEFAULT 'draft',
  `requested_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `procurement_requests`
--

LOCK TABLES `procurement_requests` WRITE;
/*!40000 ALTER TABLE `procurement_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `procurement_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree') DEFAULT 'Diploma',
  `duration_years` int(11) DEFAULT 2,
  `total_fee` decimal(12,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (0,'CNM','Certificate in Midwifery','Certificate',2,1220000.00,1,'2026-07-02 07:23:48','2026-07-02 07:23:48'),(0,'CNN','Certificate in Nursing','Certificate',2,1150000.00,1,'2026-07-02 07:23:48','2026-07-02 07:23:48'),(0,'DNM','Diploma in Nursing','Diploma',3,1625000.00,1,'2026-07-02 07:23:48','2026-07-02 07:23:48'),(0,'DMM','Diploma in Midwifery','Diploma',3,1685000.00,1,'2026-07-02 07:23:48','2026-07-02 07:23:48'),(0,'DNE','Diploma in Nursing Education','Diploma',3,1485000.00,1,'2026-07-02 07:23:48','2026-07-02 07:23:48'),(0,'BNM','Bachelor of Science in Nursing','Degree',4,3100000.00,1,'2026-07-02 07:23:48','2026-07-02 07:23:48'),(0,'CNM','Certificate in Midwifery','Certificate',2,1220000.00,1,'2026-07-02 08:08:51','2026-07-02 08:08:51'),(0,'CNN','Certificate in Nursing','Certificate',2,1150000.00,1,'2026-07-02 08:08:51','2026-07-02 08:08:51'),(0,'DNM','Diploma in Nursing','Diploma',3,1625000.00,1,'2026-07-02 08:08:51','2026-07-02 08:08:51'),(0,'DMM','Diploma in Midwifery','Diploma',3,1685000.00,1,'2026-07-02 08:08:51','2026-07-02 08:08:51'),(0,'DNE','Diploma in Nursing Education','Diploma',3,1485000.00,1,'2026-07-02 08:08:51','2026-07-02 08:08:51'),(0,'BNM','Bachelor of Science in Nursing','Degree',4,3100000.00,1,'2026-07-02 08:08:51','2026-07-02 08:08:51'),(0,'CNM','Certificate in Midwifery','Certificate',2,1220000.00,1,'2026-07-03 03:56:26','2026-07-03 03:56:26'),(0,'CNN','Certificate in Nursing','Certificate',2,1150000.00,1,'2026-07-03 03:56:26','2026-07-03 03:56:26'),(0,'DNM','Diploma in Nursing','Diploma',3,1625000.00,1,'2026-07-03 03:56:26','2026-07-03 03:56:26'),(0,'DMM','Diploma in Midwifery','Diploma',3,1685000.00,1,'2026-07-03 03:56:26','2026-07-03 03:56:26'),(0,'DNE','Diploma in Nursing Education','Diploma',3,1485000.00,1,'2026-07-03 03:56:26','2026-07-03 03:56:26'),(0,'BNM','Bachelor of Science in Nursing','Degree',4,3100000.00,1,'2026-07-03 03:56:26','2026-07-03 03:56:26'),(0,'CNM','Certificate in Midwifery','Certificate',2,1220000.00,1,'2026-07-03 04:05:12','2026-07-03 04:05:12'),(0,'CNN','Certificate in Nursing','Certificate',2,1150000.00,1,'2026-07-03 04:05:12','2026-07-03 04:05:12'),(0,'DNM','Diploma in Nursing','Diploma',3,1625000.00,1,'2026-07-03 04:05:12','2026-07-03 04:05:12'),(0,'DMM','Diploma in Midwifery','Diploma',3,1685000.00,1,'2026-07-03 04:05:12','2026-07-03 04:05:12'),(0,'DNE','Diploma in Nursing Education','Diploma',3,1485000.00,1,'2026-07-03 04:05:12','2026-07-03 04:05:12'),(0,'BNM','Bachelor of Science in Nursing','Degree',4,3100000.00,1,'2026-07-03 04:05:12','2026-07-03 04:05:12'),(0,'CNM','Certificate in Midwifery','Certificate',2,1220000.00,1,'2026-07-03 04:38:06','2026-07-03 04:38:06'),(0,'CNN','Certificate in Nursing','Certificate',2,1150000.00,1,'2026-07-03 04:38:06','2026-07-03 04:38:06'),(0,'DNM','Diploma in Nursing','Diploma',3,1625000.00,1,'2026-07-03 04:38:06','2026-07-03 04:38:06'),(0,'DMM','Diploma in Midwifery','Diploma',3,1685000.00,1,'2026-07-03 04:38:06','2026-07-03 04:38:06'),(0,'DNE','Diploma in Nursing Education','Diploma',3,1485000.00,1,'2026-07-03 04:38:06','2026-07-03 04:38:06'),(0,'BNM','Bachelor of Science in Nursing','Degree',4,3100000.00,1,'2026-07-03 04:38:06','2026-07-03 04:38:06'),(0,'CNM','Certificate in Midwifery','Certificate',2,1220000.00,1,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'CNN','Certificate in Nursing','Certificate',2,1150000.00,1,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'DNM','Diploma in Nursing','Diploma',3,1625000.00,1,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'DMM','Diploma in Midwifery','Diploma',3,1685000.00,1,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'DNE','Diploma in Nursing Education','Diploma',3,1485000.00,1,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'BNM','Bachelor of Science in Nursing','Degree',4,3100000.00,1,'2026-07-03 04:51:14','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proof_of_payments`
--

DROP TABLE IF EXISTS `proof_of_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proof_of_payments` (
  `id` int(11) NOT NULL,
  `proof_number` varchar(50) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_title` varchar(300) DEFAULT NULL,
  `review_type` varchar(200) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `reviewer` varchar(200) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` enum('draft','completed','reviewed') DEFAULT 'draft',
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
-- Table structure for table `registrar_certificates`
--

DROP TABLE IF EXISTS `registrar_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrar_certificates` (
  `id` int(11) NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `certificate_type` enum('Certificate','Diploma','Degree','Transcript') DEFAULT 'Certificate',
  `gpa` decimal(5,2) DEFAULT NULL,
  `cgpa` decimal(5,2) DEFAULT NULL,
  `class_of_award` varchar(100) DEFAULT NULL,
  `status` enum('Draft','Generated','Issued','Collected','Cancelled') DEFAULT 'Draft',
  `generated_by` int(11) DEFAULT NULL,
  `generated_date` datetime DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issued_date` datetime DEFAULT NULL,
  `collected_by` varchar(255) DEFAULT NULL,
  `collected_date` datetime DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_certificates`
--

LOCK TABLES `registrar_certificates` WRITE;
/*!40000 ALTER TABLE `registrar_certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrar_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrar_settings`
--

DROP TABLE IF EXISTS `registrar_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrar_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `description` varchar(500) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_settings`
--

LOCK TABLES `registrar_settings` WRITE;
/*!40000 ALTER TABLE `registrar_settings` DISABLE KEYS */;
INSERT INTO `registrar_settings` VALUES (1,'current_academic_year','2025','academic','Current active academic year',NULL,'2026-06-19 06:48:04'),(2,'current_semester','Semester 1','academic','Current active semester',NULL,'2026-06-19 06:48:04'),(3,'institution_name','ISNM','general','Institution Name',NULL,'2026-06-19 06:48:04'),(4,'transcript_fee','50000','fees','Transcript processing fee',NULL,'2026-06-19 06:48:04'),(5,'certificate_fee','100000','fees','Certificate processing fee',NULL,'2026-06-19 06:48:04'),(6,'grading_system','letter','academic','Grading system (letter/percentage/GPA)',NULL,'2026-06-19 06:48:04'),(7,'pass_mark','50','academic','Minimum pass mark',NULL,'2026-06-19 06:48:04'),(8,'currency','UGX','general','Default currency',NULL,'2026-06-19 06:48:04'),(9,'auto_generate_transcripts','1','settings','Auto-generate transcripts on grade approval',NULL,'2026-06-19 06:48:04'),(10,'graduation_batch','2025','academic','Current graduation batch',NULL,'2026-06-19 06:48:04');
/*!40000 ALTER TABLE `registrar_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrar_transcript_requests`
--

DROP TABLE IF EXISTS `registrar_transcript_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrar_transcript_requests` (
  `id` int(11) NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `purpose` varchar(500) DEFAULT NULL,
  `copies_requested` int(11) DEFAULT 1,
  `copies_issued` int(11) DEFAULT 0,
  `fee` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('Pending','Paid','Waived') DEFAULT 'Pending',
  `status` enum('Pending','Processing','Ready','Issued','Collected','Rejected') DEFAULT 'Pending',
  `requested_by` varchar(255) DEFAULT NULL,
  `request_date` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_date` datetime DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issued_date` datetime DEFAULT NULL,
  `collected_by` varchar(255) DEFAULT NULL,
  `collected_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_transcript_requests`
--

LOCK TABLES `registrar_transcript_requests` WRITE;
/*!40000 ALTER TABLE `registrar_transcript_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrar_transcript_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `request_tracking`
--

DROP TABLE IF EXISTS `request_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `request_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_title` varchar(300) DEFAULT NULL,
  `request_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` varchar(200) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `requested_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `request_tracking`
--

LOCK TABLES `request_tracking` WRITE;
/*!40000 ALTER TABLE `request_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `request_tracking` ENABLE KEYS */;
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
-- Table structure for table `risk_register`
--

DROP TABLE IF EXISTS `risk_register`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_register` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `risk_name` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `likelihood` enum('low','medium','high') DEFAULT 'medium',
  `impact` enum('low','medium','high') DEFAULT 'medium',
  `mitigation` text DEFAULT NULL,
  `status` enum('active','monitored','resolved') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_register`
--

LOCK TABLES `risk_register` WRITE;
/*!40000 ALTER TABLE `risk_register` DISABLE KEYS */;
/*!40000 ALTER TABLE `risk_register` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_components`
--

DROP TABLE IF EXISTS `salary_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_components` (
  `id` int(11) NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `component_type` enum('Earning','Deduction') DEFAULT 'Earning',
  `description` text DEFAULT NULL,
  `is_percentage` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_components`
--

LOCK TABLES `salary_components` WRITE;
/*!40000 ALTER TABLE `salary_components` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secretary_messages`
--

DROP TABLE IF EXISTS `secretary_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `secretary_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) DEFAULT 0,
  `sender_name` varchar(200) DEFAULT NULL,
  `recipient_type` varchar(50) DEFAULT NULL,
  `recipient_id` int(11) DEFAULT 0,
  `subject` varchar(300) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `attachment` varchar(500) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
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
INSERT INTO `sickness_directory` VALUES (1,'MLR','Malaria','Infectious','Fever, chills, headache, sweating, fatigue','Mosquito-borne parasitic infection common in tropical regions',0,'Artemisinin-based combination therapy, antimalarials','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(2,'TYP','Typhoid','Infectious','Prolonged fever, abdominal pain, headache, constipation or diarrhea','Bacterial infection spread through contaminated food/water',1,'Antibiotics (ciprofloxacin, azithromycin), hydration','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(3,'FLU','Influenza','Infectious','Fever, cough, sore throat, body aches, fatigue','Viral respiratory infection spread through droplets',1,'Rest, fluids, antipyretics, antivirals if severe','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(4,'COLD','Common Cold','Infectious','Runny nose, sneezing, sore throat, cough, mild fever','Viral upper respiratory tract infection',1,'Rest, antihistamines, decongestants, vitamin C','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(5,'URTI','Upper Respiratory Tract Infection','Infectious','Cough, sore throat, nasal congestion, fever','Bacterial or viral infection of upper airways',1,'Antibiotics if bacterial, rest, fluids','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(6,'HDCH','Headache/Tension Headache','Non-Infectious','Head pain, pressure around forehead, neck tension','Common tension-type headache from stress or fatigue',0,'Rest, analgesics (paracetamol, ibuprofen)','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(7,'GSTR','Gastritis','Non-Infectious','Abdominal pain, nausea, bloating, indigestion','Inflammation of stomach lining from diet, stress, or infection',0,'Antacids, dietary changes, proton pump inhibitors','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(8,'DIAR','Diarrhea','Infectious','Loose watery stools, abdominal cramps, dehydration','Common infection from contaminated food/water or viruses',1,'ORS, hydration, antidiarrheals, antibiotics if bacterial','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(9,'ALLG','Allergic Reaction','Non-Infectious','Rash, itching, sneezing, watery eyes, swelling','Immune response to allergens (food, dust, pollen, drugs)',0,'Antihistamines, corticosteroids, avoid triggers','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(10,'INJR','Injury/Accident','Injury','Pain, swelling, bruising, bleeding, limited mobility','Physical trauma from falls, sports, or accidents',0,'First aid, rest, ice, compression, elevation, analgesics','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(11,'ANEM','Anemia','Nutritional','Fatigue, weakness, pale skin, shortness of breath, dizziness','Low red blood cell count from iron deficiency or other causes',0,'Iron supplements, dietary changes, B12 if needed','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(12,'MALN','Malnutrition','Nutritional','Weight loss, fatigue, poor growth, weakened immunity','Inadequate nutrient intake affecting overall health',0,'Nutritional supplementation, diet counseling','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(13,'CONS','Constipation','Non-Infectious','Infrequent bowel movements, straining, hard stools','Common digestive issue from diet or lifestyle factors',0,'Increased fiber intake, hydration, laxatives if needed','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(14,'SORE','Sore Throat','Infectious','Pain or scratchiness in throat, difficulty swallowing','Viral or bacterial throat infection',1,'Warm salt water gargle, lozenges, antibiotics if strep','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(15,'EYEI','Eye Infection','Infectious','Redness, itching, discharge, swollen eyelids','Bacterial or viral conjunctivitis',1,'Antibiotic or antiviral eye drops, hygiene','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(16,'SKIN','Skin Infection/Rash','Infectious','Redness, itching, bumps, blisters, peeling','Fungal, bacterial, or viral skin infection',1,'Topical or oral antibiotics/antifungals, hygiene','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(17,'FATG','Fatigue/General Malaise','Non-Infectious','Tiredness, low energy, reduced motivation','General feeling of being unwell without specific diagnosis',0,'Rest, nutrition, hydration, stress management','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(18,'MSTR','Menstrual Cramps','Non-Infectious','Lower abdominal pain, back pain, nausea during menstruation','Painful menstrual periods common in young women',0,'Analgesics, heat therapy, rest, NSAIDs','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(19,'ANXT','Anxiety/Stress','Mental Health','Worry, restlessness, rapid heartbeat, difficulty concentrating','Mental health condition common among students under academic pressure',0,'Counseling, stress management, relaxation techniques','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(20,'BACK','Back Pain','Non-Infectious','Lower or upper back pain, stiffness, muscle tension','Musculoskeletal pain from poor posture, heavy lifting, or strain',0,'Rest, analgesics, physiotherapy, posture correction','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(21,'THRP','Throat Infection/Pharyngitis','Infectious','Sore throat, red tonsils, swollen lymph nodes, fever','Inflammation of the pharynx from viral or bacterial infection',1,'Antibiotics if bacterial, rest, warm fluids','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(22,'TOOT','Toothache','Non-Infectious','Tooth pain, sensitivity, swelling around tooth','Dental pain from cavities, infection, or impaction',0,'Analgesics, dental referral, antibiotics if infected','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(23,'URIN','Urinary Tract Infection','Infectious','Painful urination, frequent urination, lower abdominal pain','Bacterial infection of the urinary tract',0,'Antibiotics, increased fluid intake, cranberry','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(24,'ACNE','Acne/Skin Breakout','Non-Infectious','Pimples, blackheads, whiteheads, inflamed skin','Common skin condition from hormonal changes and stress',0,'Topical treatments, hygiene, dietary changes','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19'),(25,'FUNG','Fungal Infection','Infectious','Itching, redness, peeling skin, rash with defined edges','Fungal skin infection common in tropical climates',1,'Antifungal creams or oral medication, keep area dry','Active',NULL,'2026-06-20 08:42:19','2026-06-20 08:42:19');
/*!40000 ALTER TABLE `sickness_directory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sponsorships`
--

DROP TABLE IF EXISTS `sponsorships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sponsorships` (
  `id` int(11) NOT NULL,
  `sponsorship_code` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `sponsor_name` varchar(255) NOT NULL,
  `sponsor_type` enum('Government','NGO','Private','Self','Other') DEFAULT 'Self',
  `sponsorship_type` enum('Full','Partial','Tuition Only','Other') DEFAULT 'Partial',
  `amount` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `status` enum('Active','Expired','Cancelled') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sponsorships`
--

LOCK TABLES `sponsorships` WRITE;
/*!40000 ALTER TABLE `sponsorships` DISABLE KEYS */;
/*!40000 ALTER TABLE `sponsorships` ENABLE KEYS */;
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
-- Table structure for table `staff_salaries`
--

DROP TABLE IF EXISTS `staff_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_salaries` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `base_salary` decimal(12,2) NOT NULL,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) GENERATED ALWAYS AS (`base_salary` + `allowances` - `deductions`) STORED,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Pending') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_salaries`
--

LOCK TABLES `staff_salaries` WRITE;
/*!40000 ALTER TABLE `staff_salaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_salaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `strategic_initiatives`
--

DROP TABLE IF EXISTS `strategic_initiatives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `strategic_initiatives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) DEFAULT NULL,
  `initiative_name` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `strategic_initiatives`
--

LOCK TABLES `strategic_initiatives` WRITE;
/*!40000 ALTER TABLE `strategic_initiatives` DISABLE KEYS */;
/*!40000 ALTER TABLE `strategic_initiatives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `strategic_plans`
--

DROP TABLE IF EXISTS `strategic_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `strategic_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_name` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','active','completed','cancelled') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `strategic_plans`
--

LOCK TABLES `strategic_plans` WRITE;
/*!40000 ALTER TABLE `strategic_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `strategic_plans` ENABLE KEYS */;
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
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `credits` decimal(3,1) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_academic_records`
--

LOCK TABLES `student_academic_records` WRITE;
/*!40000 ALTER TABLE `student_academic_records` DISABLE KEYS */;
INSERT INTO `student_academic_records` VALUES (0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',67.73,4.0,3.05,2.88,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',63.05,4.0,2.52,3.77,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',83.82,4.0,3.50,3.32,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',60.72,4.0,3.84,3.14,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',98.97,4.0,3.27,3.48,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',86.91,4.0,2.77,3.83,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',92.35,4.0,3.04,3.07,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',98.26,4.0,2.98,3.62,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',83.28,4.0,3.42,3.00,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',64.76,4.0,2.68,2.87,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',86.90,4.0,3.56,3.30,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',60.79,4.0,3.30,3.40,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',70.91,4.0,2.67,3.64,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',98.07,4.0,3.13,2.89,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',85.71,4.0,2.78,2.50,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',69.05,4.0,3.69,2.94,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',82.79,4.0,2.72,2.52,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',62.61,4.0,3.20,2.72,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',69.36,4.0,2.74,2.66,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',84.20,4.0,2.74,3.99,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',74.84,4.0,3.18,2.71,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',71.25,4.0,3.08,2.65,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',75.29,4.0,3.85,3.03,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',73.69,4.0,3.65,3.73,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',77.70,4.0,3.81,2.58,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',61.17,4.0,2.85,2.62,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',67.75,4.0,3.87,3.98,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',87.15,4.0,2.82,2.57,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',91.30,4.0,2.74,3.17,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',78.21,4.0,4.00,3.44,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',76.26,4.0,3.64,3.39,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',84.27,4.0,2.50,2.79,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',66.67,4.0,3.99,3.18,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',83.69,4.0,3.53,3.47,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',62.91,4.0,3.42,3.78,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',82.36,4.0,3.29,3.91,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',93.51,4.0,3.47,3.57,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',98.36,4.0,3.91,3.74,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',62.17,4.0,3.03,3.40,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',98.15,4.0,3.87,3.55,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',89.61,4.0,3.09,3.63,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',85.72,4.0,3.21,3.16,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',82.44,4.0,3.22,3.56,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',72.86,4.0,3.49,3.02,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',85.77,4.0,2.52,2.72,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',97.16,4.0,3.44,3.04,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',79.86,4.0,3.60,2.78,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',61.97,4.0,2.62,2.90,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',96.02,4.0,3.41,3.00,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',66.73,4.0,3.02,2.84,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',84.62,4.0,3.62,3.81,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',91.84,4.0,3.80,3.92,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',81.34,4.0,2.99,2.55,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',75.76,4.0,3.43,3.86,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',88.05,4.0,3.18,2.73,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',85.00,4.0,3.81,3.22,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',81.95,4.0,3.02,2.62,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',84.95,4.0,3.99,2.67,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',80.46,4.0,3.77,3.57,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',80.26,4.0,3.72,3.33,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',99.34,4.0,3.89,3.54,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',72.34,4.0,3.27,3.44,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',66.37,4.0,3.95,3.06,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',85.05,4.0,2.93,3.34,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',97.63,4.0,3.91,3.80,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',98.49,4.0,2.92,3.25,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',93.10,4.0,2.71,2.84,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',95.07,4.0,2.87,3.42,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',89.27,4.0,3.58,3.10,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',60.34,4.0,3.28,3.34,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',68.72,4.0,2.95,3.75,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',83.42,4.0,2.92,3.45,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',89.66,4.0,3.59,3.10,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',95.42,4.0,3.97,2.86,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',74.62,4.0,3.88,3.24,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',61.86,4.0,2.68,3.19,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',75.10,4.0,2.54,2.51,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',89.92,4.0,3.82,2.71,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',80.61,4.0,3.60,2.68,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',87.95,4.0,2.88,2.75,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',74.89,4.0,2.62,2.94,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',70.51,4.0,3.67,2.65,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',69.45,4.0,3.30,3.94,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',89.69,4.0,3.29,3.14,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',74.61,4.0,2.87,2.69,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',66.58,4.0,2.64,3.95,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',94.93,4.0,3.56,3.85,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',68.96,4.0,3.95,2.75,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',64.40,4.0,3.68,3.41,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',83.97,4.0,3.91,3.84,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',84.49,4.0,2.62,3.37,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',78.69,4.0,3.12,3.51,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',93.16,4.0,2.81,3.30,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',66.11,4.0,3.67,3.15,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',95.92,4.0,3.95,2.72,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',85.81,4.0,3.67,3.95,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',84.21,4.0,3.28,3.67,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',73.23,4.0,3.48,2.93,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',76.66,4.0,3.58,3.04,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',63.29,4.0,3.27,2.99,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',69.61,4.0,3.52,3.51,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',87.52,4.0,3.11,3.97,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',79.25,4.0,3.02,2.95,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',74.57,4.0,3.20,2.84,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',99.55,4.0,3.62,3.64,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',83.44,4.0,2.81,2.91,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',98.42,4.0,3.30,3.66,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',66.40,4.0,3.76,3.56,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',84.96,4.0,3.00,3.67,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',70.20,4.0,3.27,3.70,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',96.41,4.0,2.75,2.64,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',83.67,4.0,2.55,3.10,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',68.64,4.0,3.15,3.30,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',66.01,4.0,3.56,2.61,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',81.61,4.0,3.27,3.92,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',71.22,4.0,3.80,3.22,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',82.98,4.0,3.20,3.41,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',74.11,4.0,3.79,2.86,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',76.46,4.0,2.76,3.47,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',84.07,4.0,3.82,3.41,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',62.97,4.0,2.86,3.95,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',74.64,4.0,3.36,3.65,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',95.99,4.0,3.65,2.68,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',67.71,4.0,2.55,3.37,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',70.63,4.0,3.89,3.76,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',81.78,4.0,3.23,3.69,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',67.98,4.0,3.17,3.46,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',72.82,4.0,2.59,3.03,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',91.66,4.0,2.87,3.81,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',79.89,4.0,3.42,3.37,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',72.60,4.0,2.66,3.38,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',72.26,4.0,3.54,3.32,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',84.47,4.0,2.66,3.56,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B+',68.02,4.0,2.63,3.75,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',99.14,4.0,2.81,2.67,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',73.83,4.0,3.88,3.33,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',94.70,4.0,2.81,3.13,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',67.21,4.0,3.16,3.46,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',83.99,4.0,2.92,3.41,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',92.32,4.0,3.76,3.66,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',77.30,4.0,2.66,2.87,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',94.31,4.0,3.29,2.57,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',66.73,4.0,3.78,3.66,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',68.90,4.0,2.77,2.87,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',87.72,4.0,3.11,3.91,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',85.75,4.0,3.61,3.64,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',87.44,4.0,3.47,2.76,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',66.57,4.0,2.51,3.31,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','A',91.10,4.0,3.77,3.87,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Fundamentals of Nursing I','CNN101','B',89.76,4.0,3.45,3.93,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',74.97,3.0,3.00,3.33,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',63.80,3.0,2.84,3.77,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',71.26,3.0,3.57,3.56,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',95.45,3.0,2.84,3.23,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',68.30,3.0,3.76,3.39,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',74.69,3.0,3.33,3.48,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',66.80,3.0,3.95,2.98,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',82.46,3.0,3.54,3.68,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',94.03,3.0,3.60,2.65,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',71.94,3.0,3.29,3.62,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',63.31,3.0,3.84,2.84,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',82.65,3.0,3.23,3.58,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',72.58,3.0,3.90,3.57,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',89.07,3.0,2.97,3.08,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',92.18,3.0,2.57,3.71,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',62.51,3.0,3.44,3.92,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',74.91,3.0,3.01,3.38,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',91.00,3.0,2.73,3.18,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',86.31,3.0,3.81,3.11,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',92.39,3.0,3.75,3.59,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',98.46,3.0,3.10,2.67,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',82.05,3.0,3.43,3.15,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',69.16,3.0,2.85,3.21,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',96.13,3.0,3.29,3.87,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',75.33,3.0,2.61,2.84,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',95.43,3.0,3.53,3.66,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',90.69,3.0,3.07,3.39,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',76.08,3.0,3.25,2.92,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',90.50,3.0,2.57,3.90,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',97.22,3.0,2.51,2.88,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',69.74,3.0,2.59,3.37,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',93.88,3.0,2.62,3.80,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',96.78,3.0,2.63,3.53,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',77.44,3.0,3.72,3.63,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',73.81,3.0,3.65,3.70,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',62.79,3.0,2.90,2.71,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',99.31,3.0,2.92,3.16,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',78.42,3.0,2.86,3.73,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',75.27,3.0,3.71,3.85,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',63.37,3.0,3.16,3.93,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',73.84,3.0,3.11,4.00,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',94.42,3.0,3.98,3.04,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',65.53,3.0,2.73,3.05,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',88.99,3.0,3.29,3.20,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',75.35,3.0,3.47,2.61,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',97.45,3.0,3.08,2.69,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',60.42,3.0,3.42,2.57,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',90.17,3.0,3.46,3.89,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',94.61,3.0,2.70,2.62,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',88.07,3.0,3.32,3.44,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',84.06,3.0,3.28,3.67,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',77.75,3.0,2.72,3.09,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',80.42,3.0,3.90,2.72,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',65.61,3.0,3.94,3.05,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',89.17,3.0,3.62,3.32,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',91.61,3.0,3.24,2.66,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',86.14,3.0,3.05,3.83,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',97.64,3.0,3.62,3.87,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',95.14,3.0,3.13,3.21,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',69.29,3.0,3.74,3.18,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',79.19,3.0,2.65,2.58,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',88.25,3.0,3.42,3.90,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',75.89,3.0,3.20,2.71,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',79.60,3.0,2.83,3.42,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',70.78,3.0,2.62,3.38,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',91.18,3.0,3.65,3.26,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',67.68,3.0,2.88,3.55,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',84.62,3.0,3.77,3.06,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',83.47,3.0,3.86,3.66,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',85.42,3.0,3.90,3.62,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',80.87,3.0,3.62,2.75,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',78.15,3.0,3.25,2.69,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',71.78,3.0,3.15,2.92,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',70.28,3.0,2.69,3.79,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',63.44,3.0,3.44,3.80,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',88.25,3.0,2.72,3.42,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',70.19,3.0,3.13,3.01,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',65.66,3.0,3.12,3.46,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',95.67,3.0,3.36,2.77,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',84.12,3.0,3.55,3.53,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',82.33,3.0,3.74,3.18,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',84.86,3.0,3.57,3.57,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',99.61,3.0,3.51,3.07,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',72.50,3.0,3.84,3.27,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',60.05,3.0,2.91,3.06,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',81.11,3.0,2.84,3.32,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',97.10,3.0,3.68,2.75,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',93.36,3.0,3.65,3.01,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',97.38,3.0,3.25,3.56,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',99.41,3.0,3.83,3.22,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',69.93,3.0,2.54,3.10,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',75.54,3.0,2.78,3.66,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',99.47,3.0,3.11,2.59,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',98.38,3.0,2.61,3.22,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',84.95,3.0,3.75,2.96,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',88.66,3.0,2.70,3.26,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',74.57,3.0,3.04,3.57,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',73.31,3.0,2.76,3.81,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',85.17,3.0,3.39,2.62,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',95.67,3.0,3.36,2.81,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',96.71,3.0,3.50,3.36,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',82.99,3.0,2.95,3.68,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',91.49,3.0,3.42,3.58,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',83.28,3.0,3.50,3.38,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',94.33,3.0,3.29,2.58,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',69.33,3.0,2.70,3.97,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',79.37,3.0,3.96,3.10,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',91.67,3.0,3.01,2.98,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',98.03,3.0,2.51,2.78,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',98.03,3.0,2.58,3.10,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',62.80,3.0,3.68,3.58,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',77.49,3.0,2.65,2.76,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',73.54,3.0,3.97,3.82,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',88.92,3.0,2.79,3.69,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',82.11,3.0,3.41,3.05,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',60.13,3.0,3.96,3.77,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',63.96,3.0,3.26,2.86,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',86.08,3.0,2.86,2.85,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',84.79,3.0,3.55,3.48,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',77.89,3.0,3.86,2.78,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',96.07,3.0,3.98,2.82,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',74.87,3.0,2.54,2.51,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',92.85,3.0,2.80,3.29,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',93.22,3.0,3.09,3.24,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',78.94,3.0,3.71,3.41,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',71.40,3.0,3.34,3.92,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',88.05,3.0,3.12,3.96,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',66.43,3.0,3.94,2.95,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',72.50,3.0,3.44,2.81,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',69.05,3.0,3.58,3.90,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',86.75,3.0,3.80,2.98,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',60.84,3.0,2.68,3.31,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',60.62,3.0,2.65,3.20,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B+',71.58,3.0,3.18,3.10,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',60.11,3.0,2.64,3.17,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',79.97,3.0,3.38,3.17,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',99.61,3.0,3.33,3.70,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',69.47,3.0,2.81,2.98,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',98.14,3.0,3.73,2.85,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',96.01,3.0,3.00,3.97,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',81.32,3.0,3.97,2.95,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',96.12,3.0,3.75,3.19,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',82.78,3.0,3.22,3.55,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',89.13,3.0,2.65,3.00,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',89.12,3.0,3.41,3.78,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',88.83,3.0,2.84,3.98,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','B',81.06,3.0,3.82,3.74,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',98.57,3.0,3.03,3.83,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',65.53,3.0,3.42,3.49,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Anatomy & Physiology I','CNN102','A',73.37,3.0,2.92,3.09,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',87.59,3.0,2.84,2.60,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',62.18,3.0,2.98,3.15,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',68.94,3.0,3.69,2.96,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',75.13,3.0,3.44,3.98,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',87.15,3.0,2.90,2.92,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',69.13,3.0,2.95,3.71,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',63.61,3.0,3.30,3.10,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',89.25,3.0,3.25,2.98,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',72.90,3.0,2.57,2.88,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',98.61,3.0,2.84,2.84,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',82.83,3.0,3.26,3.74,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',83.37,3.0,2.62,3.48,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',83.67,3.0,3.36,2.63,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',75.23,3.0,3.57,3.14,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',88.43,3.0,3.33,3.47,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',96.08,3.0,3.71,2.97,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',83.07,3.0,3.21,3.44,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',90.11,3.0,3.37,3.47,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',80.51,3.0,2.64,3.88,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',96.69,3.0,3.36,2.68,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',62.40,3.0,3.47,2.56,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',78.12,3.0,3.24,2.68,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',78.48,3.0,3.78,3.81,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',74.98,3.0,3.22,2.92,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',60.21,3.0,2.67,3.33,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',77.50,3.0,3.90,3.02,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',84.99,3.0,2.99,3.64,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',94.41,3.0,3.72,3.23,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',77.93,3.0,2.95,2.75,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',63.93,3.0,3.60,3.07,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',74.01,3.0,3.48,2.84,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',79.84,3.0,3.79,3.72,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',99.38,3.0,3.20,3.08,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',78.62,3.0,3.63,3.06,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',96.60,3.0,3.63,2.53,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',64.38,3.0,2.57,3.88,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',78.50,3.0,3.97,3.28,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',90.95,3.0,3.79,3.99,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',96.02,3.0,3.06,2.76,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',66.98,3.0,3.49,3.65,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',97.40,3.0,2.71,3.84,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',92.08,3.0,2.84,3.60,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',87.88,3.0,3.32,3.48,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',66.14,3.0,3.85,2.55,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',68.95,3.0,3.59,3.95,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',73.56,3.0,3.63,3.62,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',64.62,3.0,2.75,3.23,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',67.84,3.0,2.78,3.02,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',90.53,3.0,2.85,3.80,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',86.24,3.0,2.98,3.46,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',73.50,3.0,2.63,3.12,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',91.17,3.0,3.23,2.66,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',87.28,3.0,3.25,3.17,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',75.55,3.0,3.55,3.02,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',62.32,3.0,3.15,4.00,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',76.92,3.0,2.60,2.59,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',77.52,3.0,2.70,3.04,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',94.29,3.0,2.69,2.60,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',81.22,3.0,3.72,3.24,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',90.78,3.0,2.76,3.35,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',92.92,3.0,2.80,3.32,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',71.09,3.0,3.43,2.91,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',86.94,3.0,3.81,3.02,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',80.47,3.0,3.74,3.39,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',88.48,3.0,2.59,2.75,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',91.49,3.0,3.94,3.17,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',74.64,3.0,3.71,3.93,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',91.53,3.0,3.93,3.12,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',98.94,3.0,3.62,3.71,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',81.33,3.0,2.95,3.83,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',61.59,3.0,3.37,3.67,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',96.39,3.0,2.66,3.71,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',63.18,3.0,2.95,2.92,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',82.50,3.0,3.06,2.78,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',78.66,3.0,3.88,2.79,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',87.25,3.0,2.54,2.61,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',72.10,3.0,3.39,2.60,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',81.72,3.0,2.60,3.58,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',90.33,3.0,3.47,3.91,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',63.50,3.0,2.63,2.77,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',83.72,3.0,2.63,3.50,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',70.33,3.0,3.15,3.11,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',74.68,3.0,3.53,3.00,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',99.79,3.0,2.77,3.87,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B',66.47,3.0,3.16,3.55,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',91.74,3.0,3.01,2.96,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',88.56,3.0,3.98,3.69,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','B+',73.60,3.0,3.51,3.03,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',83.87,3.0,3.69,2.78,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',64.98,3.0,2.52,3.56,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',74.06,3.0,2.91,2.94,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',78.94,3.0,3.02,2.99,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',96.65,3.0,3.77,3.22,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',97.14,3.0,2.52,2.95,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',71.49,3.0,2.69,3.67,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',71.67,3.0,3.81,3.24,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',91.46,3.0,3.05,3.23,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',64.89,3.0,3.50,3.95,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',70.66,3.0,3.75,3.05,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'Semester 1','2024/2025','Community Health Nursing I','CNN103','A',81.61,3.0,3.58,3.97,'Pass','2026-07-03 04:51:14','2026-07-03 04:51:14');
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
-- Table structure for table `student_appeals`
--

DROP TABLE IF EXISTS `student_appeals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_appeals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `appeal_type` varchar(200) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `outcome` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_appeals`
--

LOCK TABLES `student_appeals` WRITE;
/*!40000 ALTER TABLE `student_appeals` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_appeals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_attendance`
--

DROP TABLE IF EXISTS `student_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_attendance`
--

LOCK TABLES `student_attendance` WRITE;
/*!40000 ALTER TABLE `student_attendance` DISABLE KEYS */;
INSERT INTO `student_attendance` VALUES (0,0,'2024-09-03',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Absent',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Absent',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Absent',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Absent',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Absent',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Absent',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Absent',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Absent',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Late',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-03',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-10',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-17',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-09-24',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,0,'2024-10-01',NULL,'CNN101','Present',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `student_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_course_registrations`
--

DROP TABLE IF EXISTS `student_course_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_course_registrations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `registration_date` date DEFAULT curdate(),
  `status` enum('Registered','Dropped','Completed') DEFAULT 'Registered',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_course_registrations`
--

LOCK TABLES `student_course_registrations` WRITE;
/*!40000 ALTER TABLE `student_course_registrations` DISABLE KEYS */;
INSERT INTO `student_course_registrations` VALUES (0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',1,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',2,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',3,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',11,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',12,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',13,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',48,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',49,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',50,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',58,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',59,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',60,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',95,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',96,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',97,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',105,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',106,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',107,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',142,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',143,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',144,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',152,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',153,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14'),(0,'0',154,'2024/2025','Semester 1','2026-07-03','Registered','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `student_course_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `student_dashboard_view`
--

DROP TABLE IF EXISTS `student_dashboard_view`;
/*!50001 DROP VIEW IF EXISTS `student_dashboard_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `student_dashboard_view` AS SELECT
 1 AS `id`,
  1 AS `student_number`,
  1 AS `full_name`,
  1 AS `course`,
  1 AS `year`,
  1 AS `set_name`,
  1 AS `email`,
  1 AS `profile_picture`,
  1 AS `current_gpa`,
  1 AS `fee_balance`,
  1 AS `attendance_rate` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `student_discipline`
--

DROP TABLE IF EXISTS `student_discipline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_discipline` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `incident_date` date NOT NULL,
  `incident_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `action_taken` varchar(255) DEFAULT NULL,
  `action_date` date DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `status` enum('Open','Resolved','Appealed') DEFAULT 'Open',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_discipline`
--

LOCK TABLES `student_discipline` WRITE;
/*!40000 ALTER TABLE `student_discipline` DISABLE KEYS */;
INSERT INTO `student_discipline` VALUES (0,'183366','2024-10-10','Minor','Late submission of assignment','Warning issued','2024-10-12',NULL,'Resolved','2026-07-03 04:51:14'),(0,'183364','2024-10-15','Minor','Absence from practical session','Make-up session scheduled','2024-10-17',NULL,'Resolved','2026-07-03 04:51:14'),(0,'183362','2024-11-01','Major','Plagiarism in coursework','Under review',NULL,NULL,'Open','2026-07-03 04:51:14'),(0,'183359','2024-10-20','Minor','Uniform violation','Verbal warning','2024-10-21',NULL,'Resolved','2026-07-03 04:51:14'),(0,'183357','2024-11-05','Minor','Noise in dormitory after hours','Written warning','2024-11-06',NULL,'Resolved','2026-07-03 04:51:14'),(0,'183354','2024-11-10','Major','Unauthorized absence from clinical','Parent notified','2024-11-12',NULL,'Resolved','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `student_discipline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_discipline_records`
--

DROP TABLE IF EXISTS `student_discipline_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_discipline_records` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `action_taken` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Resolved','Closed') DEFAULT 'Pending',
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
-- Table structure for table `student_downloads`
--

DROP TABLE IF EXISTS `student_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_downloads` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_downloads`
--

LOCK TABLES `student_downloads` WRITE;
/*!40000 ALTER TABLE `student_downloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_downloads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_fee_assignments`
--

DROP TABLE IF EXISTS `student_fee_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_fee_assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `assigned_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) GENERATED ALWAYS AS (`assigned_amount` - `paid_amount`) STORED,
  `status` enum('Unpaid','Partially Paid','Paid','Waived') DEFAULT 'Unpaid',
  `due_date` date DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `student_id` int(11) DEFAULT NULL,
  `fee_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_fee_tracking`
--

LOCK TABLES `student_fee_tracking` WRITE;
/*!40000 ALTER TABLE `student_fee_tracking` DISABLE KEYS */;
INSERT INTO `student_fee_tracking` VALUES (1,0,'Tuition',1220000.00,781334.00,438666.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(2,0,'Tuition',1220000.00,761717.00,458283.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(3,0,'Tuition',1220000.00,464587.00,755413.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(4,0,'Tuition',1220000.00,637782.00,582218.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(5,0,'Tuition',1220000.00,795152.00,424848.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(6,0,'Tuition',1220000.00,262414.00,957586.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(7,0,'Tuition',1220000.00,326613.00,893387.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(8,0,'Tuition',1220000.00,645824.00,574176.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(9,0,'Tuition',1220000.00,449280.00,770720.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(10,0,'Tuition',1220000.00,908928.00,311072.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(11,0,'Tuition',1220000.00,596803.00,623197.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(12,0,'Tuition',1220000.00,857232.00,362768.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(13,0,'Tuition',1220000.00,695749.00,524251.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(14,0,'Tuition',1220000.00,707052.00,512948.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(15,0,'Tuition',1220000.00,448012.00,771988.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(16,0,'Tuition',1220000.00,718906.00,501094.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(17,0,'Tuition',1220000.00,450493.00,769507.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(18,0,'Tuition',1220000.00,695748.00,524252.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(19,0,'Tuition',1220000.00,327261.00,892739.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(20,0,'Tuition',1220000.00,949063.00,270937.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(21,0,'Tuition',1150000.00,363530.00,786470.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(22,0,'Tuition',1150000.00,370462.00,779538.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(23,0,'Tuition',1150000.00,561720.00,588280.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(24,0,'Tuition',1150000.00,697215.00,452785.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(25,0,'Tuition',1150000.00,800916.00,349084.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(26,0,'Tuition',1150000.00,912937.00,237063.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(27,0,'Tuition',1150000.00,361935.00,788065.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(28,0,'Tuition',1150000.00,470866.00,679134.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(29,0,'Tuition',1150000.00,268524.00,881476.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(30,0,'Tuition',1150000.00,530025.00,619975.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(31,0,'Tuition',1150000.00,844553.00,305447.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(32,0,'Tuition',1150000.00,832690.00,317310.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(33,0,'Tuition',1150000.00,629793.00,520207.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(34,0,'Tuition',1150000.00,450895.00,699105.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(35,0,'Tuition',1150000.00,965095.00,184905.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(36,0,'Tuition',1150000.00,872790.00,277210.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(37,0,'Tuition',1150000.00,468665.00,681335.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(38,0,'Tuition',1150000.00,324958.00,825042.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(39,0,'Tuition',1150000.00,818793.00,331207.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(40,0,'Tuition',1150000.00,519092.00,630908.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(41,0,'Tuition',1625000.00,739083.00,885917.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(42,0,'Tuition',1625000.00,338139.00,1286861.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(43,0,'Tuition',1625000.00,873446.00,751554.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(44,0,'Tuition',1625000.00,752812.00,872188.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(45,0,'Tuition',1625000.00,943724.00,681276.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(46,0,'Tuition',1625000.00,660183.00,964817.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(47,0,'Tuition',1625000.00,269744.00,1355256.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(48,0,'Tuition',1625000.00,768173.00,856827.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(49,0,'Tuition',1625000.00,431634.00,1193366.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(50,0,'Tuition',1625000.00,453649.00,1171351.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(51,0,'Tuition',1625000.00,773345.00,851655.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(52,0,'Tuition',1625000.00,705778.00,919222.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(53,0,'Tuition',1625000.00,208856.00,1416144.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(54,0,'Tuition',1625000.00,326944.00,1298056.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(55,0,'Tuition',1625000.00,808156.00,816844.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(56,0,'Tuition',1625000.00,459948.00,1165052.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(57,0,'Tuition',1625000.00,475270.00,1149730.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(58,0,'Tuition',1625000.00,796509.00,828491.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(59,0,'Tuition',1625000.00,756736.00,868264.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(60,0,'Tuition',1625000.00,394153.00,1230847.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(61,0,'Tuition',1625000.00,300555.00,1324445.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(62,0,'Tuition',1625000.00,920320.00,704680.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(63,0,'Tuition',1625000.00,299933.00,1325067.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(64,0,'Tuition',1625000.00,938707.00,686293.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(65,0,'Tuition',1625000.00,393734.00,1231266.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(66,0,'Tuition',1625000.00,552552.00,1072448.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(67,0,'Tuition',1625000.00,581556.00,1043444.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(68,0,'Tuition',1625000.00,250125.00,1374875.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(69,0,'Tuition',1625000.00,905957.00,719043.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(70,0,'Tuition',1625000.00,379413.00,1245587.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(71,0,'Tuition',1685000.00,579193.00,1105807.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(72,0,'Tuition',1685000.00,757728.00,927272.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(73,0,'Tuition',1685000.00,251061.00,1433939.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(74,0,'Tuition',1685000.00,382121.00,1302879.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(75,0,'Tuition',1685000.00,957424.00,727576.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(76,0,'Tuition',1685000.00,240758.00,1444242.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(77,0,'Tuition',1685000.00,531517.00,1153483.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(78,0,'Tuition',1685000.00,935312.00,749688.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(79,0,'Tuition',1685000.00,482011.00,1202989.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(80,0,'Tuition',1685000.00,204117.00,1480883.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(81,0,'Tuition',1685000.00,974552.00,710448.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(82,0,'Tuition',1685000.00,860413.00,824587.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(83,0,'Tuition',1685000.00,378406.00,1306594.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(84,0,'Tuition',1685000.00,710792.00,974208.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(85,0,'Tuition',1685000.00,618741.00,1066259.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(86,0,'Tuition',1685000.00,761332.00,923668.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(87,0,'Tuition',1685000.00,950435.00,734565.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(88,0,'Tuition',1685000.00,668181.00,1016819.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(89,0,'Tuition',1685000.00,289601.00,1395399.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(90,0,'Tuition',1685000.00,843461.00,841539.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(91,0,'Tuition',1500000.00,748502.00,751498.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(92,0,'Tuition',1500000.00,212128.00,1287872.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(93,0,'Tuition',1500000.00,215136.00,1284864.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(94,0,'Tuition',1500000.00,239295.00,1260705.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(95,0,'Tuition',1500000.00,351069.00,1148931.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(96,0,'Tuition',1500000.00,837462.00,662538.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(97,0,'Tuition',1500000.00,534101.00,965899.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(98,0,'Tuition',1500000.00,758119.00,741881.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(99,0,'Tuition',1500000.00,388294.00,1111706.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(100,0,'Tuition',1500000.00,267115.00,1232885.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(101,0,'Tuition',1500000.00,770692.00,729308.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(102,0,'Tuition',1500000.00,452116.00,1047884.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(103,0,'Tuition',1500000.00,548506.00,951494.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(104,0,'Tuition',1500000.00,386179.00,1113821.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(105,0,'Tuition',1500000.00,885380.00,614620.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(106,0,'Tuition',1500000.00,668364.00,831636.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(107,0,'Tuition',1500000.00,485678.00,1014322.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(108,0,'Tuition',1500000.00,223302.00,1276698.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(109,0,'Tuition',1500000.00,259474.00,1240526.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(110,0,'Tuition',1500000.00,427465.00,1072535.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(111,0,'Tuition',1500000.00,358903.00,1141097.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(112,0,'Tuition',1500000.00,312120.00,1187880.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(113,0,'Tuition',1500000.00,283893.00,1216107.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(114,0,'Tuition',1500000.00,283106.00,1216894.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(115,0,'Tuition',1500000.00,363851.00,1136149.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(116,0,'Tuition',1500000.00,769938.00,730062.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(117,0,'Tuition',1500000.00,958137.00,541863.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(118,0,'Tuition',1500000.00,680872.00,819128.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(119,0,'Tuition',1500000.00,329949.00,1170051.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(120,0,'Tuition',1500000.00,207130.00,1292870.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(121,0,'Tuition',1500000.00,645804.00,854196.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(122,0,'Tuition',1500000.00,807632.00,692368.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(123,0,'Tuition',1500000.00,300747.00,1199253.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(124,0,'Tuition',1500000.00,480838.00,1019162.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(125,0,'Tuition',1500000.00,501952.00,998048.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(126,0,'Tuition',1500000.00,867246.00,632754.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(127,0,'Tuition',1500000.00,230374.00,1269626.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(128,0,'Tuition',1500000.00,750131.00,749869.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(129,0,'Tuition',1500000.00,459535.00,1040465.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(130,0,'Tuition',1500000.00,647283.00,852717.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(131,0,'Tuition',1500000.00,857810.00,642190.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(132,0,'Tuition',1500000.00,547201.00,952799.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(133,0,'Tuition',1500000.00,762577.00,737423.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(134,0,'Tuition',1500000.00,371282.00,1128718.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(135,0,'Tuition',1500000.00,968679.00,531321.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(136,0,'Tuition',1500000.00,329551.00,1170449.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(137,0,'Tuition',1500000.00,941719.00,558281.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(138,0,'Tuition',1500000.00,319943.00,1180057.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(139,0,'Tuition',1500000.00,974556.00,525444.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(140,0,'Tuition',1500000.00,512954.00,987046.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(141,0,'Tuition',1500000.00,241102.00,1258898.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(142,0,'Tuition',1500000.00,266647.00,1233353.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(143,0,'Tuition',1500000.00,409930.00,1090070.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(144,0,'Tuition',1500000.00,249708.00,1250292.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(145,0,'Tuition',1500000.00,618752.00,881248.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(146,0,'Tuition',1500000.00,544634.00,955366.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(147,0,'Tuition',1500000.00,666918.00,833082.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(148,0,'Tuition',1500000.00,700687.00,799313.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(149,0,'Tuition',1500000.00,502680.00,997320.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14'),(150,0,'Tuition',1500000.00,211341.00,1288659.00,'2024/2025','Semester 1','2024-09-30','Partial','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `student_fee_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_fees`
--

DROP TABLE IF EXISTS `student_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('Unpaid','Partially Paid','Paid','Overdue') DEFAULT 'Unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_hostel_allocations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `hostel_name` varchar(100) DEFAULT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `allocation_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_fee` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Vacated','Transferred') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_assignment_id` int(11) DEFAULT NULL,
  `fee_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `net_amount` decimal(12,2) GENERATED ALWAYS AS (`total_amount` - `discount_amount`) STORED,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) GENERATED ALWAYS AS (`net_amount` - `amount_paid`) STORED,
  `status` enum('Draft','Pending','Partially Paid','Paid','Overdue','Cancelled','Waived') DEFAULT 'Pending',
  `due_date` date DEFAULT NULL,
  `issue_date` date DEFAULT curdate(),
  `payment_method` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_invoices`
--

LOCK TABLES `student_invoices` WRITE;
/*!40000 ALTER TABLE `student_invoices` DISABLE KEYS */;
INSERT INTO `student_invoices` VALUES (0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,848665.00,371335.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,609304.00,610696.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,400523.00,819477.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,874703.00,345297.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,671946.00,548054.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,635623.00,584377.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,262276.00,957724.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,104511.00,1115489.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,435728.00,784272.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,165107.00,1054893.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,218352.00,1001648.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,496440.00,723560.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,127145.00,1092855.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,646407.00,573593.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,350597.00,869403.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,513766.00,706234.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,617041.00,602959.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,643907.00,576093.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,468411.00,751589.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1220000.00,0.00,1220000.00,310337.00,909663.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,846451.00,303549.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,801244.00,348756.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,566870.00,583130.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,330617.00,819383.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,652477.00,497523.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,570535.00,579465.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,795244.00,354756.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,564614.00,585386.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,337341.00,812659.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,692862.00,457138.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,752288.00,397712.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,782856.00,367144.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,757416.00,392584.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,538513.00,611487.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,320318.00,829682.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,686050.00,463950.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,769297.00,380703.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,888334.00,261666.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,433779.00,716221.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1150000.00,0.00,1150000.00,203893.00,946107.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,418131.00,1206869.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,578974.00,1046026.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,740477.00,884523.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,265465.00,1359535.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,605896.00,1019104.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,533085.00,1091915.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,747738.00,877262.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,439437.00,1185563.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,653970.00,971030.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,251538.00,1373462.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,795783.00,829217.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,724301.00,900699.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,334158.00,1290842.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,197886.00,1427114.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,686955.00,938045.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,341119.00,1283881.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,344728.00,1280272.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,600287.00,1024713.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,267250.00,1357750.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,235390.00,1389610.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,275202.00,1349798.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,569839.00,1055161.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,323591.00,1301409.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,608438.00,1016562.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,371416.00,1253584.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,731768.00,893232.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,844592.00,780408.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,327658.00,1297342.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,604513.00,1020487.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1625000.00,0.00,1625000.00,339594.00,1285406.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,584429.00,915571.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,203362.00,1296638.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,763527.00,736473.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,707549.00,792451.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,347163.00,1152837.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,313168.00,1186832.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,424353.00,1075647.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,282263.00,1217737.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,838255.00,661745.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,844487.00,655513.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,807668.00,692332.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,604883.00,895117.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,501410.00,998590.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,592400.00,907600.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,557773.00,942227.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,111664.00,1388336.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,385001.00,1114999.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,690015.00,809985.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,595074.00,904926.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,805322.00,694678.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,541392.00,958608.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,190992.00,1309008.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,830787.00,669213.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,280959.00,1219041.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,412435.00,1087565.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,319296.00,1180704.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,259177.00,1240823.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,237997.00,1262003.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,312455.00,1187545.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,748286.00,751714.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,304064.00,1195936.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,775464.00,724536.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,465126.00,1034874.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,699241.00,800759.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,400826.00,1099174.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,606406.00,893594.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,129554.00,1370446.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,328554.00,1171446.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,354106.00,1145894.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,684871.00,815129.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,662035.00,837965.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,355562.00,1144438.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,491709.00,1008291.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,491856.00,1008144.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,884157.00,615843.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,445214.00,1054786.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,273601.00,1226399.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,732363.00,767637.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,341014.00,1158986.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,207979.00,1292021.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,716857.00,783143.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,460346.00,1039654.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,851162.00,648838.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,374770.00,1125230.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,820363.00,679637.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,477508.00,1022492.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,626453.00,873547.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,799740.00,700260.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,419342.00,1080658.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,397492.00,1102508.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,629432.00,870568.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,254687.00,1245313.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,885139.00,614861.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,361632.00,1138368.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,652747.00,847253.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,478837.00,1021163.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,335947.00,1164053.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,143225.00,1356775.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,408283.00,1091717.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,711739.00,788261.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,633849.00,866151.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,134026.00,1365974.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,268585.00,1231415.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,840847.00,659153.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,898480.00,601520.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,269861.00,1230139.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,153866.00,1346134.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,659746.00,840254.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,337135.00,1162865.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14'),(0,'INV000000-S1',0,NULL,'Tuition',NULL,1500000.00,0.00,1500000.00,406435.00,1093565.00,'Partially Paid','2024-09-30','2024-08-01',NULL,NULL,'2026-07-03 04:51:14','2026-07-03 04:51:14');
/*!40000 ALTER TABLE `student_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `student_login_view`
--

DROP TABLE IF EXISTS `student_login_view`;
/*!50001 DROP VIEW IF EXISTS `student_login_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `student_login_view` AS SELECT
 1 AS `id`,
  1 AS `student_number`,
  1 AS `full_name`,
  1 AS `email`,
  1 AS `password`,
  1 AS `course`,
  1 AS `status`,
  1 AS `last_login`,
  1 AS `login_attempts`,
  1 AS `is_first_login` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `student_messages`
--

DROP TABLE IF EXISTS `student_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_messages` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `department_email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `replied` tinyint(1) DEFAULT 0,
  `reply_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `replied_at` timestamp NULL DEFAULT NULL
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
-- Table structure for table `student_notifications`
--

DROP TABLE IF EXISTS `student_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('General','Academic','Fee','Attendance','Exam','Event','Matron','Bursar') DEFAULT 'General',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `link_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `link_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_notifications`
--

LOCK TABLES `student_notifications` WRITE;
/*!40000 ALTER TABLE `student_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_password_resets`
--

DROP TABLE IF EXISTS `student_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_password_resets` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `id` int(11) NOT NULL,
  `penalty_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `penalty_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `applied_by` int(11) DEFAULT NULL,
  `applied_date` timestamp NULL DEFAULT current_timestamp(),
  `waived` tinyint(1) DEFAULT 0,
  `waived_by` int(11) DEFAULT NULL,
  `waived_at` timestamp NULL DEFAULT NULL,
  `waiver_reason` text DEFAULT NULL,
  `status` enum('Active','Waived','Paid') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `achievements` text DEFAULT NULL,
  `education_background` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `request_type` enum('Leave of Absence','Deferral','Transfer','Withdrawal','Other') NOT NULL,
  `reason` text NOT NULL,
  `supporting_doc` varchar(500) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_semester_gpa`
--

LOCK TABLES `student_semester_gpa` WRITE;
/*!40000 ALTER TABLE `student_semester_gpa` DISABLE KEYS */;
INSERT INTO `student_semester_gpa` VALUES (1,0,'2024/2025','Semester 1',18,13,3.36,2.09,'Good Standing',18,15,6,1,NULL,'2026-07-03 04:51:14');
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
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `lecturer` varchar(100) DEFAULT NULL,
  `classroom` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_welfare_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `case_type` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `assigned_to` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `national_student_id_number` varchar(50) DEFAULT NULL,
  `index_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `full_name` varchar(300) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `current_year` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `set_name` varchar(50) DEFAULT NULL,
  `current_semester` varchar(20) DEFAULT NULL,
  `intake_date` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Other',
  `nationality` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_email` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `passport_photo` varchar(500) DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted') DEFAULT 'Active',
  `last_login` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `password_changed` tinyint(1) DEFAULT 0,
  `is_first_login` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `idx_stu_email` (`email`),
  KEY `idx_stu_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (0,'ISNM/0001/25',NULL,NULL,'UACE/CNM/0001','Mary','Muwonge',NULL,'Sarah Ssenyonjo','student1@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-774571227','+256-774571227','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0002/25',NULL,NULL,'UACE/CNM/0002','Peace','Nakamya',NULL,'Jane Ochieng','student2@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-776779291','+256-776779291','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0003/25',NULL,NULL,'UACE/CNM/0003','Moses','Okello',NULL,'David Nanteza','student3@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-781279895','+256-781279895','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0004/25',NULL,NULL,'UACE/CNM/0004','Ruth','Sserwadda',NULL,'Mary Ssenyonjo','student4@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-781236556','+256-781236556','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0005/25',NULL,NULL,'UACE/CNM/0005','Jane','Muwonge',NULL,'John Okello','student5@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-775043713','+256-775043713','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0006/25',NULL,NULL,'UACE/CNM/0006','Esther','Nabirye',NULL,'Mary Ochieng','student6@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-784260559','+256-784260559','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Male','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0007/25',NULL,NULL,'UACE/CNM/0007','Peace','Nabirye',NULL,'Joy Nabirye','student7@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-703988337','+256-703988337','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0008/25',NULL,NULL,'UACE/CNM/0008','Faith','Namukwaya',NULL,'Sarah Ssenyonjo','student8@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-703728063','+256-703728063','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0009/25',NULL,NULL,'UACE/CNM/0009','Esther','Nakamya',NULL,'John Okello','student9@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-782284500','+256-782284500','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0010/25',NULL,NULL,'UACE/CNM/0010','David','Kintu',NULL,'Alice Nanteza','student10@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-785019393','+256-785019393','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0011/25',NULL,NULL,'UACE/CNM/0011','Joy','Wasswa',NULL,'Esther Nakato','student11@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-788356144','+256-788356144','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0012/25',NULL,NULL,'UACE/CNM/0012','Esther','Lubega',NULL,'Faith Sserwadda','student12@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-777122632','+256-777122632','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Male','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0013/25',NULL,NULL,'UACE/CNM/0013','John','Wasswa',NULL,'Faith Nakamya','student13@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-778607555','+256-778607555','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0014/25',NULL,NULL,'UACE/CNM/0014','Peter','Mukasa',NULL,'David Nanteza','student14@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-772111880','+256-772111880','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0015/25',NULL,NULL,'UACE/CNM/0015','Samuel','Wasswa',NULL,'Mary Kizza','student15@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-780170078','+256-780170078','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0016/25',NULL,NULL,'UACE/CNM/0016','Samuel','Kizza',NULL,'David Ochieng','student16@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-785312802','+256-785312802','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0017/25',NULL,NULL,'UACE/CNM/0017','Samuel','Lubega',NULL,'Jane Nakato','student17@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-774130995','+256-774130995','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0018/25',NULL,NULL,'UACE/CNM/0018','Esther','Ochieng',NULL,'Grace Nakato','student18@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-707727624','+256-707727624','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Male','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0019/25',NULL,NULL,'UACE/CNM/0019','Mary','Muwonge',NULL,'Grace Nabirye','student19@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-708314660','+256-708314660','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0020/25',NULL,NULL,'UACE/CNM/0020','Peter','Nanteza',NULL,'Ruth Kintu','student20@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-781502155','+256-781502155','Certificate in Midwifery','Certificate in Midwifery',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0021/25',NULL,NULL,'UACE/CNN/0021','Mary','Namukwaya',NULL,'Sarah Muwonge','student21@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-782204948','+256-782204948','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0022/25',NULL,NULL,'UACE/CNN/0022','Ruth','Kizza',NULL,'Moses Nakamya','student22@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-700903908','+256-700903908','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0023/25',NULL,NULL,'UACE/CNN/0023','Ruth','Ochieng',NULL,'Joy Nakato','student23@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-709011142','+256-709011142','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0024/25',NULL,NULL,'UACE/CNN/0024','Mary','Nabirye',NULL,'John Okello','student24@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-782412749','+256-782412749','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Male','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0025/25',NULL,NULL,'UACE/CNN/0025','Samuel','Sserwadda',NULL,'Joy Nanteza','student25@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-772757319','+256-772757319','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0026/25',NULL,NULL,'UACE/CNN/0026','Grace','Ssenyonjo',NULL,'Sarah Mukasa','student26@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-781924183','+256-781924183','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0027/25',NULL,NULL,'UACE/CNN/0027','Ruth','Mukasa',NULL,'David Nakamya','student27@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-789578199','+256-789578199','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0028/25',NULL,NULL,'UACE/CNN/0028','Moses','Ochieng',NULL,'Esther Ssenyonjo','student28@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-784557465','+256-784557465','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0029/25',NULL,NULL,'UACE/CNN/0029','David','Ochieng',NULL,'Peter Muwonge','student29@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-784068713','+256-784068713','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0030/25',NULL,NULL,'UACE/CNN/0030','John','Namukwaya',NULL,'Peace Kizza','student30@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-789042037','+256-789042037','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Male','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0031/25',NULL,NULL,'UACE/CNN/0031','Moses','Nabirye',NULL,'Ruth Nakato','student31@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-774563401','+256-774563401','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0032/25',NULL,NULL,'UACE/CNN/0032','Esther','Nanteza',NULL,'Faith Namukwaya','student32@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-708761693','+256-708761693','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0033/25',NULL,NULL,'UACE/CNN/0033','Mary','Ochieng',NULL,'Samuel Mukasa','student33@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-787886390','+256-787886390','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0034/25',NULL,NULL,'UACE/CNN/0034','Peter','Okello',NULL,'Esther Kintu','student34@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-706063539','+256-706063539','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0035/25',NULL,NULL,'UACE/CNN/0035','Peter','Mukasa',NULL,'John Kintu','student35@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-707425760','+256-707425760','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0036/25',NULL,NULL,'UACE/CNN/0036','Moses','Namukwaya',NULL,'Moses Kintu','student36@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-789443939','+256-789443939','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Male','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0037/25',NULL,NULL,'UACE/CNN/0037','Mary','Ssenyonjo',NULL,'Peter Kintu','student37@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-786637356','+256-786637356','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0038/25',NULL,NULL,'UACE/CNN/0038','Grace','Kizza',NULL,'Ruth Nabirye','student38@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-779129500','+256-779129500','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0039/25',NULL,NULL,'UACE/CNN/0039','Esther','Nabirye',NULL,'Sarah Ssenyonjo','student39@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-775114752','+256-775114752','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0040/25',NULL,NULL,'UACE/CNN/0040','Alice','Kizza',NULL,'Mary Okello','student40@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-772095035','+256-772095035','Certificate in Nursing','Certificate in Nursing',1,1,'Certificate',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0041/24',NULL,NULL,'UACE/DNM/0041','Joy','Ssenyonjo',NULL,'Esther Nakamya','student41@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-778993733','+256-778993733','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0042/24',NULL,NULL,'UACE/DNM/0042','Sarah','Ssenyonjo',NULL,'Ruth Nakamya','student42@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-702057084','+256-702057084','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Male','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0043/24',NULL,NULL,'UACE/DNM/0043','David','Wasswa',NULL,'Jane Nakamya','student43@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-789414023','+256-789414023','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0044/24',NULL,NULL,'UACE/DNM/0044','David','Lubega',NULL,'John Mukasa','student44@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-771067107','+256-771067107','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0045/24',NULL,NULL,'UACE/DNM/0045','Samuel','Ochieng',NULL,'Samuel Sserwadda','student45@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-778896965','+256-778896965','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0046/24',NULL,NULL,'UACE/DNM/0046','Peter','Mukasa',NULL,'Grace Sserwadda','student46@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-787898635','+256-787898635','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0047/24',NULL,NULL,'UACE/DNM/0047','Sarah','Muwonge',NULL,'Moses Nabirye','student47@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-774581010','+256-774581010','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0048/24',NULL,NULL,'UACE/DNM/0048','Peace','Nanteza',NULL,'Joy Wasswa','student48@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-770178473','+256-770178473','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Male','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0049/24',NULL,NULL,'UACE/DNM/0049','Peter','Wasswa',NULL,'Samuel Kintu','student49@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-704546145','+256-704546145','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0050/24',NULL,NULL,'UACE/DNM/0050','Samuel','Nakato',NULL,'David Nanteza','student50@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-772195098','+256-772195098','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0051/24',NULL,NULL,'UACE/DNM/0051','Ruth','Sserwadda',NULL,'Esther Kizza','student51@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-784744390','+256-784744390','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0052/24',NULL,NULL,'UACE/DNM/0052','Peace','Lubega',NULL,'Jane Sserwadda','student52@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-784913420','+256-784913420','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0053/24',NULL,NULL,'UACE/DNM/0053','Joy','Ochieng',NULL,'Samuel Wasswa','student53@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-704965732','+256-704965732','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0054/24',NULL,NULL,'UACE/DNM/0054','Alice','Ssenyonjo',NULL,'Jane Kintu','student54@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-783688931','+256-783688931','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Male','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0055/24',NULL,NULL,'UACE/DNM/0055','Esther','Namukwaya',NULL,'Jane Namukwaya','student55@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-786236264','+256-786236264','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0056/24',NULL,NULL,'UACE/DNM/0056','Ruth','Wasswa',NULL,'Samuel Kintu','student56@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-703507071','+256-703507071','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0057/24',NULL,NULL,'UACE/DNM/0057','Peter','Kintu',NULL,'David Sserwadda','student57@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-771885879','+256-771885879','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0058/24',NULL,NULL,'UACE/DNM/0058','Peter','Mukasa',NULL,'David Kintu','student58@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-773974868','+256-773974868','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0059/24',NULL,NULL,'UACE/DNM/0059','John','Namukwaya',NULL,'Alice Ochieng','student59@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-709375762','+256-709375762','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0060/24',NULL,NULL,'UACE/DNM/0060','Joy','Namukwaya',NULL,'Joy Ssenyonjo','student60@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-782151802','+256-782151802','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Male','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0061/24',NULL,NULL,'UACE/DNM/0061','Grace','Namukwaya',NULL,'Jane Nakato','student61@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-770184003','+256-770184003','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0062/24',NULL,NULL,'UACE/DNM/0062','David','Nakato',NULL,'Peter Nakamya','student62@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-708222949','+256-708222949','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0063/24',NULL,NULL,'UACE/DNM/0063','Jane','Nakamya',NULL,'Sarah Nanteza','student63@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-705229417','+256-705229417','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0064/24',NULL,NULL,'UACE/DNM/0064','Moses','Nanteza',NULL,'Grace Ochieng','student64@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-775654586','+256-775654586','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0065/24',NULL,NULL,'UACE/DNM/0065','Esther','Wasswa',NULL,'Mary Kizza','student65@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-781748308','+256-781748308','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0066/24',NULL,NULL,'UACE/DNM/0066','Sarah','Ssenyonjo',NULL,'Alice Kizza','student66@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-700988607','+256-700988607','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Male','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0067/24',NULL,NULL,'UACE/DNM/0067','Faith','Mukasa',NULL,'Esther Lubega','student67@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-770387594','+256-770387594','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0068/24',NULL,NULL,'UACE/DNM/0068','Jane','Muwonge',NULL,'John Mukasa','student68@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-771188826','+256-771188826','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0069/24',NULL,NULL,'UACE/DNM/0069','Samuel','Okello',NULL,'Ruth Ochieng','student69@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-783249504','+256-783249504','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0070/24',NULL,NULL,'UACE/DNM/0070','Peace','Wasswa',NULL,'Peter Kintu','student70@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-707752690','+256-707752690','Diploma in Nursing','Diploma in Nursing',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0071/24',NULL,NULL,'UACE/DMM/0071','David','Muwonge',NULL,'Alice Lubega','student71@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-780560207','+256-780560207','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0072/24',NULL,NULL,'UACE/DMM/0072','Joy','Lubega',NULL,'Faith Wasswa','student72@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-781327322','+256-781327322','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Male','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0073/24',NULL,NULL,'UACE/DMM/0073','Peace','Okello',NULL,'Mary Sserwadda','student73@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-777382056','+256-777382056','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0074/24',NULL,NULL,'UACE/DMM/0074','Grace','Namukwaya',NULL,'Peace Sserwadda','student74@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-774208337','+256-774208337','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0075/24',NULL,NULL,'UACE/DMM/0075','Esther','Sserwadda',NULL,'Samuel Nakamya','student75@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-774107687','+256-774107687','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0076/24',NULL,NULL,'UACE/DMM/0076','Faith','Nakamya',NULL,'Faith Muwonge','student76@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-786791936','+256-786791936','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0077/24',NULL,NULL,'UACE/DMM/0077','Mary','Ssenyonjo',NULL,'David Kizza','student77@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-780318876','+256-780318876','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0078/24',NULL,NULL,'UACE/DMM/0078','Esther','Sserwadda',NULL,'John Kizza','student78@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-702962891','+256-702962891','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Male','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0079/24',NULL,NULL,'UACE/DMM/0079','Mary','Kintu',NULL,'Esther Kintu','student79@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-704378691','+256-704378691','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0080/24',NULL,NULL,'UACE/DMM/0080','John','Ochieng',NULL,'Peter Ssenyonjo','student80@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-770329121','+256-770329121','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0081/24',NULL,NULL,'UACE/DMM/0081','Faith','Namukwaya',NULL,'Mary Ochieng','student81@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-780482903','+256-780482903','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0082/24',NULL,NULL,'UACE/DMM/0082','Alice','Kintu',NULL,'Peace Nakato','student82@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-775910832','+256-775910832','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0083/24',NULL,NULL,'UACE/DMM/0083','Peace','Muwonge',NULL,'Peter Kizza','student83@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-779177049','+256-779177049','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0084/24',NULL,NULL,'UACE/DMM/0084','Mary','Sserwadda',NULL,'Faith Wasswa','student84@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-782343559','+256-782343559','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Male','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0085/24',NULL,NULL,'UACE/DMM/0085','Peace','Sserwadda',NULL,'Mary Muwonge','student85@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-700702837','+256-700702837','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0086/24',NULL,NULL,'UACE/DMM/0086','Moses','Nabirye',NULL,'Ruth Namukwaya','student86@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-705633283','+256-705633283','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0087/24',NULL,NULL,'UACE/DMM/0087','Grace','Nabirye',NULL,'Mary Muwonge','student87@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-709698010','+256-709698010','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0088/24',NULL,NULL,'UACE/DMM/0088','Alice','Nabirye',NULL,'Grace Namukwaya','student88@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-774207258','+256-774207258','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0089/24',NULL,NULL,'UACE/DMM/0089','Alice','Namukwaya',NULL,'Alice Muwonge','student89@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-779644139','+256-779644139','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0090/24',NULL,NULL,'UACE/DMM/0090','Faith','Nakato',NULL,'David Nakato','student90@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-701964811','+256-701964811','Diploma in Midwifery','Diploma in Midwifery',2,2,'Diploma',NULL,NULL,NULL,NULL,'Male','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0091/23',NULL,NULL,'UACE/DNE/0091','Peter','Kintu',NULL,'Sarah Nanteza','student91@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-707456515','+256-707456515','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0092/23',NULL,NULL,'UACE/DNE/0092','Sarah','Ssenyonjo',NULL,'Jane Nanteza','student92@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-703110553','+256-703110553','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0093/23',NULL,NULL,'UACE/DNE/0093','John','Namukwaya',NULL,'Peace Okello','student93@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-706268467','+256-706268467','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0094/23',NULL,NULL,'UACE/DNE/0094','Grace','Ssenyonjo',NULL,'Grace Ssenyonjo','student94@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-707729037','+256-707729037','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0095/23',NULL,NULL,'UACE/DNE/0095','Ruth','Muwonge',NULL,'Peter Wasswa','student95@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-702229232','+256-702229232','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0096/23',NULL,NULL,'UACE/DNE/0096','Samuel','Nabirye',NULL,'David Muwonge','student96@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-775787748','+256-775787748','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0097/23',NULL,NULL,'UACE/DNE/0097','Esther','Kizza',NULL,'Sarah Okello','student97@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-709144794','+256-709144794','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0098/23',NULL,NULL,'UACE/DNE/0098','Alice','Namukwaya',NULL,'Jane Mukasa','student98@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-770803830','+256-770803830','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0099/23',NULL,NULL,'UACE/DNE/0099','Joy','Kintu',NULL,'Grace Lubega','student99@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-778936284','+256-778936284','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0100/23',NULL,NULL,'UACE/DNE/0100','Moses','Namukwaya',NULL,'John Namukwaya','student100@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-777276039','+256-777276039','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0101/23',NULL,NULL,'UACE/DNE/0101','Mary','Nakamya',NULL,'Peace Lubega','student101@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-778611329','+256-778611329','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0102/23',NULL,NULL,'UACE/DNE/0102','Peace','Okello',NULL,'Esther Nabirye','student102@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-789652358','+256-789652358','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0103/23',NULL,NULL,'UACE/DNE/0103','Peace','Nanteza',NULL,'Jane Nabirye','student103@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-774669700','+256-774669700','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0104/23',NULL,NULL,'UACE/DNE/0104','Sarah','Nakato',NULL,'Joy Wasswa','student104@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-706474214','+256-706474214','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0105/23',NULL,NULL,'UACE/DNE/0105','Ruth','Namukwaya',NULL,'Samuel Ssenyonjo','student105@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-785793679','+256-785793679','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0106/23',NULL,NULL,'UACE/DNE/0106','Peter','Lubega',NULL,'Mary Ochieng','student106@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-787326480','+256-787326480','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0107/23',NULL,NULL,'UACE/DNE/0107','Peace','Nanteza',NULL,'Sarah Ochieng','student107@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-708782505','+256-708782505','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0108/23',NULL,NULL,'UACE/DNE/0108','Peace','Wasswa',NULL,'Grace Wasswa','student108@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-773306947','+256-773306947','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0109/23',NULL,NULL,'UACE/DNE/0109','Mary','Okello',NULL,'Joy Ochieng','student109@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-788873342','+256-788873342','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0110/23',NULL,NULL,'UACE/DNE/0110','David','Ssenyonjo',NULL,'Sarah Nakato','student110@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-774319221','+256-774319221','Diploma in Nursing Education','Diploma in Nursing Education',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0111/23',NULL,NULL,'UACE/BNM/0111','Faith','Kizza',NULL,'David Okello','student111@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-770352439','+256-770352439','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0112/23',NULL,NULL,'UACE/BNM/0112','Esther','Wasswa',NULL,'Jane Kintu','student112@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-787234563','+256-787234563','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0113/23',NULL,NULL,'UACE/BNM/0113','John','Mukasa',NULL,'Peace Namukwaya','student113@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-788968397','+256-788968397','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0114/23',NULL,NULL,'UACE/BNM/0114','Joy','Lubega',NULL,'David Wasswa','student114@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-770111618','+256-770111618','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0115/23',NULL,NULL,'UACE/BNM/0115','Esther','Muwonge',NULL,'Alice Muwonge','student115@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-773868815','+256-773868815','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0116/23',NULL,NULL,'UACE/BNM/0116','Alice','Nakato',NULL,'Sarah Kizza','student116@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-703897961','+256-703897961','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0117/23',NULL,NULL,'UACE/BNM/0117','Esther','Kizza',NULL,'David Okello','student117@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-782735922','+256-782735922','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0118/23',NULL,NULL,'UACE/BNM/0118','Ruth','Kintu',NULL,'Sarah Nabirye','student118@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-779544120','+256-779544120','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0119/23',NULL,NULL,'UACE/BNM/0119','Ruth','Nakato',NULL,'Faith Ssenyonjo','student119@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-788750458','+256-788750458','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0120/23',NULL,NULL,'UACE/BNM/0120','John','Ssenyonjo',NULL,'Jane Nakamya','student120@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-708298256','+256-708298256','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0121/23',NULL,NULL,'UACE/BNM/0121','Mary','Kizza',NULL,'Samuel Lubega','student121@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-776638003','+256-776638003','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0122/23',NULL,NULL,'UACE/BNM/0122','Samuel','Mukasa',NULL,'Grace Kizza','student122@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-702398474','+256-702398474','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0123/23',NULL,NULL,'UACE/BNM/0123','Sarah','Kintu',NULL,'David Namukwaya','student123@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-773040163','+256-773040163','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0124/23',NULL,NULL,'UACE/BNM/0124','Mary','Lubega',NULL,'Alice Muwonge','student124@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-776200061','+256-776200061','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0125/23',NULL,NULL,'UACE/BNM/0125','Esther','Muwonge',NULL,'Ruth Nabirye','student125@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-783854961','+256-783854961','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0126/23',NULL,NULL,'UACE/BNM/0126','Jane','Okello',NULL,'Peter Namukwaya','student126@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-780195603','+256-780195603','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0127/23',NULL,NULL,'UACE/BNM/0127','John','Wasswa',NULL,'John Nabirye','student127@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-700147629','+256-700147629','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0128/23',NULL,NULL,'UACE/BNM/0128','Esther','Nakamya',NULL,'Peace Nanteza','student128@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-703247691','+256-703247691','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0129/23',NULL,NULL,'UACE/BNM/0129','Sarah','Namukwaya',NULL,'John Nakato','student129@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-705370294','+256-705370294','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0130/23',NULL,NULL,'UACE/BNM/0130','Ruth','Namukwaya',NULL,'Peter Nanteza','student130@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-773191526','+256-773191526','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0131/23',NULL,NULL,'UACE/BNM/0131','John','Ochieng',NULL,'Jane Ochieng','student131@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-779818316','+256-779818316','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0132/23',NULL,NULL,'UACE/BNM/0132','Mary','Mukasa',NULL,'Sarah Kintu','student132@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-789279968','+256-789279968','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0133/23',NULL,NULL,'UACE/BNM/0133','Sarah','Namukwaya',NULL,'Moses Ssenyonjo','student133@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-776894125','+256-776894125','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0134/23',NULL,NULL,'UACE/BNM/0134','Peter','Ochieng',NULL,'John Okello','student134@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-788814668','+256-788814668','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0135/23',NULL,NULL,'UACE/BNM/0135','Samuel','Kintu',NULL,'Sarah Mukasa','student135@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-787082209','+256-787082209','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0136/23',NULL,NULL,'UACE/BNM/0136','Sarah','Kizza',NULL,'Alice Kizza','student136@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-772069777','+256-772069777','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0137/23',NULL,NULL,'UACE/BNM/0137','Ruth','Wasswa',NULL,'Sarah Nakato','student137@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-776502037','+256-776502037','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0138/23',NULL,NULL,'UACE/BNM/0138','Faith','Wasswa',NULL,'Ruth Lubega','student138@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-771525324','+256-771525324','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0139/23',NULL,NULL,'UACE/BNM/0139','Grace','Sserwadda',NULL,'David Lubega','student139@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-789051629','+256-789051629','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0140/23',NULL,NULL,'UACE/BNM/0140','Moses','Nabirye',NULL,'Mary Kintu','student140@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-708857305','+256-708857305','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0141/23',NULL,NULL,'UACE/BNM/0141','Sarah','Sserwadda',NULL,'Esther Nakato','student141@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-702819948','+256-702819948','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0142/23',NULL,NULL,'UACE/BNM/0142','John','Ssenyonjo',NULL,'Ruth Muwonge','student142@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-707780517','+256-707780517','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0143/23',NULL,NULL,'UACE/BNM/0143','Faith','Okello',NULL,'Ruth Okello','student143@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-709800177','+256-709800177','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0144/23',NULL,NULL,'UACE/BNM/0144','Esther','Wasswa',NULL,'Jane Nabirye','student144@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-777854116','+256-777854116','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Kamuli',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0145/23',NULL,NULL,'UACE/BNM/0145','Sarah','Kintu',NULL,'Peter Muwonge','student145@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-783672096','+256-783672096','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0146/23',NULL,NULL,'UACE/BNM/0146','Mary','Ssenyonjo',NULL,'David Kizza','student146@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-789642933','+256-789642933','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Iganga',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0147/23',NULL,NULL,'UACE/BNM/0147','Sarah','Okello',NULL,'Sarah Sserwadda','student147@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-789421624','+256-789421624','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0148/23',NULL,NULL,'UACE/BNM/0148','Ruth','Ochieng',NULL,'Peace Nanteza','student148@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-708379498','+256-708379498','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Mayuge',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0149/23',NULL,NULL,'UACE/BNM/0149','Peter','Nanteza',NULL,'Esther Nanteza','student149@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-787109143','+256-787109143','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Female','Ugandan','Bugiri',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13'),(0,'ISNM/0150/23',NULL,NULL,'UACE/BNM/0150','Mary','Lubega',NULL,'Ruth Nakamya','student150@isnm.ac.ug','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+256-779197463','+256-779197463','Bachelor of Science in Nursing','Bachelor of Science in Nursing',3,3,'Degree',NULL,NULL,NULL,NULL,'Male','Ugandan','Jinja',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active',NULL,NULL,0,0,1,'2026-07-03 04:51:13','2026-07-03 04:51:13');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`igangaschoolofl_students_db`@`localhost`*/ /*!50003 TRIGGER `students_before_insert` BEFORE INSERT ON `students` FOR EACH ROW BEGIN
    IF NEW.full_name IS NULL OR NEW.full_name = '' THEN
        SET NEW.full_name = TRIM(CONCAT(NEW.first_name, ' ', COALESCE(NEW.other_name, ''), ' ', NEW.surname));
    END IF;

    IF (NEW.phone IS NULL OR NEW.phone = '') AND NEW.mobile_number IS NOT NULL THEN
        SET NEW.phone = NEW.mobile_number;
    END IF;
    IF (NEW.mobile_number IS NULL OR NEW.mobile_number = '') AND NEW.phone IS NOT NULL THEN
        SET NEW.mobile_number = NEW.phone;
    END IF;

    IF (NEW.program IS NULL OR NEW.program = '') AND NEW.course IS NOT NULL THEN
        SET NEW.program = NEW.course;
    END IF;
    IF (NEW.course IS NULL OR NEW.course = '') AND NEW.program IS NOT NULL THEN
        SET NEW.course = NEW.program;
    END IF;

    IF (NEW.current_year IS NULL OR NEW.current_year = 0) AND NEW.year IS NOT NULL THEN
        SET NEW.current_year = NEW.year;
    END IF;
    IF (NEW.year IS NULL OR NEW.year = 0) AND NEW.current_year IS NOT NULL THEN
        SET NEW.year = NEW.current_year;
    END IF;

    IF (NEW.profile_picture IS NULL OR NEW.profile_picture = '') AND NEW.passport_photo IS NOT NULL THEN
        SET NEW.profile_picture = NEW.passport_photo;
    END IF;
    IF (NEW.passport_photo IS NULL OR NEW.passport_photo = '') AND NEW.profile_picture IS NOT NULL THEN
        SET NEW.passport_photo = NEW.profile_picture;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`igangaschoolofl_students_db`@`localhost`*/ /*!50003 TRIGGER `students_before_update` BEFORE UPDATE ON `students` FOR EACH ROW BEGIN
    IF NEW.full_name IS NULL OR NEW.full_name = '' THEN
        SET NEW.full_name = TRIM(CONCAT(NEW.first_name, ' ', COALESCE(NEW.other_name, ''), ' ', NEW.surname));
    END IF;

    IF (NEW.phone IS NULL OR NEW.phone = '') AND NEW.mobile_number IS NOT NULL THEN
        SET NEW.phone = NEW.mobile_number;
    END IF;
    IF (NEW.mobile_number IS NULL OR NEW.mobile_number = '') AND NEW.phone IS NOT NULL THEN
        SET NEW.mobile_number = NEW.phone;
    END IF;

    IF (NEW.program IS NULL OR NEW.program = '') AND NEW.course IS NOT NULL THEN
        SET NEW.program = NEW.course;
    END IF;
    IF (NEW.course IS NULL OR NEW.course = '') AND NEW.program IS NOT NULL THEN
        SET NEW.course = NEW.program;
    END IF;

    IF (NEW.current_year IS NULL OR NEW.current_year = 0) AND NEW.year IS NOT NULL THEN
        SET NEW.current_year = NEW.year;
    END IF;
    IF (NEW.year IS NULL OR NEW.year = 0) AND NEW.current_year IS NOT NULL THEN
        SET NEW.year = NEW.current_year;
    END IF;

    IF (NEW.profile_picture IS NULL OR NEW.profile_picture = '') AND NEW.passport_photo IS NOT NULL THEN
        SET NEW.profile_picture = NEW.passport_photo;
    END IF;
    IF (NEW.passport_photo IS NULL OR NEW.passport_photo = '') AND NEW.profile_picture IS NOT NULL THEN
        SET NEW.passport_photo = NEW.profile_picture;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `students_trash`
--

DROP TABLE IF EXISTS `students_trash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `students_trash` (
  `id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `full_name` varchar(300) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `snapshot_data` longtext DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT current_timestamp(),
  `restored_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students_trash`
--

LOCK TABLES `students_trash` WRITE;
/*!40000 ALTER TABLE `students_trash` DISABLE KEYS */;
/*!40000 ALTER TABLE `students_trash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_deductions`
--

DROP TABLE IF EXISTS `subscription_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_deductions` (
  `id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `installment_number` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `processed_date` datetime DEFAULT NULL,
  `status` enum('pending','success','failed','skipped') NOT NULL DEFAULT 'pending',
  `payment_reference` varchar(50) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL COMMENT 'FK to payments.id if successful',
  `failure_reason` text DEFAULT NULL,
  `attempt_count` int(11) DEFAULT 0,
  `last_attempt_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_deductions`
--

LOCK TABLES `subscription_deductions` WRITE;
/*!40000 ALTER TABLE `subscription_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_payments`
--

DROP TABLE IF EXISTS `supplier_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) DEFAULT 0,
  `payment_number` varchar(100) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `invoice_ref` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_payments`
--

LOCK TABLES `supplier_payments` WRITE;
/*!40000 ALTER TABLE `supplier_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(300) DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `performance_rating` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable`
--

DROP TABLE IF EXISTS `timetable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `program` varchar(100) NOT NULL,
  `year_of_study` int(11) DEFAULT 1,
  `semester` varchar(50) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `course_code` varchar(30) DEFAULT NULL,
  `lecturer` varchar(150) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable`
--

LOCK TABLES `timetable` WRITE;
/*!40000 ALTER TABLE `timetable` DISABLE KEYS */;
INSERT INTO `timetable` VALUES (0,'Certificate in Nursing',1,'Semester 1','Monday','08:00-10:00','Fundamentals of Nursing I','CNN101','Sr. Nakamya Florence','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Nursing',1,'Semester 1','Wednesday','10:00-12:00','Fundamentals of Nursing I','CNN101','Sr. Nakamya Florence','Skills Lab 1','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Nursing',1,'Semester 1','Tuesday','08:00-10:00','Anatomy & Physiology I','CNN102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Nursing',1,'Semester 1','Thursday','14:00-16:00','Anatomy & Physiology I','CNN102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Nursing',1,'Semester 1','Wednesday','08:00-10:00','Community Health Nursing I','CNN103','Mrs. Nabirye Sarah','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Nursing',1,'Semester 1','Friday','08:00-12:00','Community Health Nursing I','CNN103','Mrs. Nabirye Sarah','Community Site','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Midwifery',1,'Semester 1','Monday','10:00-12:00','Introduction to Midwifery','CNM101','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Midwifery',1,'Semester 1','Thursday','08:00-10:00','Introduction to Midwifery','CNM101','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Midwifery',1,'Semester 1','Tuesday','10:00-12:00','Anatomy for Midwives','CNM102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Midwifery',1,'Semester 1','Wednesday','14:00-16:00','Fundamentals of Midwifery Care','CNM103','Mrs. Musimenta Grace','Skills Lab 2','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Midwifery',1,'Semester 1','Friday','10:00-12:00','Fundamentals of Midwifery Care','CNM103','Mrs. Musimenta Grace','Skills Lab 2','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',1,'Semester 1','Monday','08:00-10:00','Nursing Science I','DNM101','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',1,'Semester 1','Thursday','10:00-12:00','Nursing Science I','DNM101','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',1,'Semester 1','Tuesday','14:00-16:00','Human Anatomy & Physiology I','DNM102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',1,'Semester 1','Wednesday','10:00-12:00','Nutrition & Dietetics','DNM103','Mrs. Nalwoga Christine','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',1,'Semester 2','Monday','14:00-16:00','Medical Surgical Nursing I','DNM104','Sr. Nakamya Florence','Skills Lab 1','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',1,'Semester 2','Friday','08:00-12:00','Medical Surgical Nursing I','DNM104','Sr. Nakamya Florence','Ward 3','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Midwifery',1,'Semester 1','Tuesday','08:00-10:00','Midwifery Science I','DMM101','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Midwifery',1,'Semester 1','Wednesday','08:00-10:00','Anatomy for Midwives','DMM102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Midwifery',1,'Semester 1','Friday','14:00-16:00','Reproductive Health','DMM103','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing Education',1,'Semester 1','Monday','10:00-12:00','Foundations of Education','DNE101','Dr. Waswa Robert','Lecture Hall D','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing Education',1,'Semester 1','Thursday','14:00-16:00','Educational Psychology','DNE102','Dr. Waswa Robert','Lecture Hall D','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',2,'Semester 3','Monday','08:00-10:00','Medical Surgical Nursing II','DNM201','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',2,'Semester 3','Wednesday','14:00-16:00','Pediatric Nursing','DNM202','Sr. Nakamya Florence','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',2,'Semester 3','Friday','10:00-12:00','Psychiatric Nursing','DNM203','Mrs. Nabirye Sarah','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',3,'Semester 5','Tuesday','08:00-12:00','Clinical Practicum I','DNM304','Head of Nursing','Iganga RRH','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Diploma in Nursing',3,'Semester 5','Thursday','10:00-12:00','Nursing Management & Leadership','DNM303','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:05:12'),(0,'Certificate in Nursing',1,'Semester 1','Monday','08:00-10:00','Fundamentals of Nursing I','CNN101','Sr. Nakamya Florence','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Nursing',1,'Semester 1','Wednesday','10:00-12:00','Fundamentals of Nursing I','CNN101','Sr. Nakamya Florence','Skills Lab 1','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Nursing',1,'Semester 1','Tuesday','08:00-10:00','Anatomy & Physiology I','CNN102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Nursing',1,'Semester 1','Thursday','14:00-16:00','Anatomy & Physiology I','CNN102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Nursing',1,'Semester 1','Wednesday','08:00-10:00','Community Health Nursing I','CNN103','Mrs. Nabirye Sarah','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Nursing',1,'Semester 1','Friday','08:00-12:00','Community Health Nursing I','CNN103','Mrs. Nabirye Sarah','Community Site','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Midwifery',1,'Semester 1','Monday','10:00-12:00','Introduction to Midwifery','CNM101','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Midwifery',1,'Semester 1','Thursday','08:00-10:00','Introduction to Midwifery','CNM101','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Midwifery',1,'Semester 1','Tuesday','10:00-12:00','Anatomy for Midwives','CNM102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Midwifery',1,'Semester 1','Wednesday','14:00-16:00','Fundamentals of Midwifery Care','CNM103','Mrs. Musimenta Grace','Skills Lab 2','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Midwifery',1,'Semester 1','Friday','10:00-12:00','Fundamentals of Midwifery Care','CNM103','Mrs. Musimenta Grace','Skills Lab 2','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',1,'Semester 1','Monday','08:00-10:00','Nursing Science I','DNM101','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',1,'Semester 1','Thursday','10:00-12:00','Nursing Science I','DNM101','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',1,'Semester 1','Tuesday','14:00-16:00','Human Anatomy & Physiology I','DNM102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',1,'Semester 1','Wednesday','10:00-12:00','Nutrition & Dietetics','DNM103','Mrs. Nalwoga Christine','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',1,'Semester 2','Monday','14:00-16:00','Medical Surgical Nursing I','DNM104','Sr. Nakamya Florence','Skills Lab 1','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',1,'Semester 2','Friday','08:00-12:00','Medical Surgical Nursing I','DNM104','Sr. Nakamya Florence','Ward 3','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Midwifery',1,'Semester 1','Tuesday','08:00-10:00','Midwifery Science I','DMM101','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Midwifery',1,'Semester 1','Wednesday','08:00-10:00','Anatomy for Midwives','DMM102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Midwifery',1,'Semester 1','Friday','14:00-16:00','Reproductive Health','DMM103','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing Education',1,'Semester 1','Monday','10:00-12:00','Foundations of Education','DNE101','Dr. Waswa Robert','Lecture Hall D','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing Education',1,'Semester 1','Thursday','14:00-16:00','Educational Psychology','DNE102','Dr. Waswa Robert','Lecture Hall D','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',2,'Semester 3','Monday','08:00-10:00','Medical Surgical Nursing II','DNM201','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',2,'Semester 3','Wednesday','14:00-16:00','Pediatric Nursing','DNM202','Sr. Nakamya Florence','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',2,'Semester 3','Friday','10:00-12:00','Psychiatric Nursing','DNM203','Mrs. Nabirye Sarah','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',3,'Semester 5','Tuesday','08:00-12:00','Clinical Practicum I','DNM304','Head of Nursing','Iganga RRH','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Diploma in Nursing',3,'Semester 5','Thursday','10:00-12:00','Nursing Management & Leadership','DNM303','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:38:06'),(0,'Certificate in Nursing',1,'Semester 1','Monday','08:00-10:00','Fundamentals of Nursing I','CNN101','Sr. Nakamya Florence','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Nursing',1,'Semester 1','Wednesday','10:00-12:00','Fundamentals of Nursing I','CNN101','Sr. Nakamya Florence','Skills Lab 1','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Nursing',1,'Semester 1','Tuesday','08:00-10:00','Anatomy & Physiology I','CNN102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Nursing',1,'Semester 1','Thursday','14:00-16:00','Anatomy & Physiology I','CNN102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Nursing',1,'Semester 1','Wednesday','08:00-10:00','Community Health Nursing I','CNN103','Mrs. Nabirye Sarah','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Nursing',1,'Semester 1','Friday','08:00-12:00','Community Health Nursing I','CNN103','Mrs. Nabirye Sarah','Community Site','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Midwifery',1,'Semester 1','Monday','10:00-12:00','Introduction to Midwifery','CNM101','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Midwifery',1,'Semester 1','Thursday','08:00-10:00','Introduction to Midwifery','CNM101','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Midwifery',1,'Semester 1','Tuesday','10:00-12:00','Anatomy for Midwives','CNM102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Midwifery',1,'Semester 1','Wednesday','14:00-16:00','Fundamentals of Midwifery Care','CNM103','Mrs. Musimenta Grace','Skills Lab 2','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Certificate in Midwifery',1,'Semester 1','Friday','10:00-12:00','Fundamentals of Midwifery Care','CNM103','Mrs. Musimenta Grace','Skills Lab 2','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',1,'Semester 1','Monday','08:00-10:00','Nursing Science I','DNM101','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',1,'Semester 1','Thursday','10:00-12:00','Nursing Science I','DNM101','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',1,'Semester 1','Tuesday','14:00-16:00','Human Anatomy & Physiology I','DNM102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',1,'Semester 1','Wednesday','10:00-12:00','Nutrition & Dietetics','DNM103','Mrs. Nalwoga Christine','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',1,'Semester 2','Monday','14:00-16:00','Medical Surgical Nursing I','DNM104','Sr. Nakamya Florence','Skills Lab 1','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',1,'Semester 2','Friday','08:00-12:00','Medical Surgical Nursing I','DNM104','Sr. Nakamya Florence','Ward 3','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Midwifery',1,'Semester 1','Tuesday','08:00-10:00','Midwifery Science I','DMM101','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Midwifery',1,'Semester 1','Wednesday','08:00-10:00','Anatomy for Midwives','DMM102','Mr. Okello David','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Midwifery',1,'Semester 1','Friday','14:00-16:00','Reproductive Health','DMM103','Mrs. Musimenta Grace','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing Education',1,'Semester 1','Monday','10:00-12:00','Foundations of Education','DNE101','Dr. Waswa Robert','Lecture Hall D','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing Education',1,'Semester 1','Thursday','14:00-16:00','Educational Psychology','DNE102','Dr. Waswa Robert','Lecture Hall D','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',2,'Semester 3','Monday','08:00-10:00','Medical Surgical Nursing II','DNM201','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',2,'Semester 3','Wednesday','14:00-16:00','Pediatric Nursing','DNM202','Sr. Nakamya Florence','Lecture Hall A','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',2,'Semester 3','Friday','10:00-12:00','Psychiatric Nursing','DNM203','Mrs. Nabirye Sarah','Lecture Hall B','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',3,'Semester 5','Tuesday','08:00-12:00','Clinical Practicum I','DNM304','Head of Nursing','Iganga RRH','2024/2025',NULL,'2026-07-03 04:51:14'),(0,'Diploma in Nursing',3,'Semester 5','Thursday','10:00-12:00','Nursing Management & Leadership','DNM303','Dr. Mubiru John','Lecture Hall C','2024/2025',NULL,'2026-07-03 04:51:14');
/*!40000 ALTER TABLE `timetable` ENABLE KEYS */;
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
-- Table structure for table `view_program_grouping`
--

DROP TABLE IF EXISTS `view_program_grouping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `view_program_grouping` (
  `department` varchar(20) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT NULL,
  `course_level` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `view_program_grouping`
--

LOCK TABLES `view_program_grouping` WRITE;
/*!40000 ALTER TABLE `view_program_grouping` DISABLE KEYS */;
/*!40000 ALTER TABLE `view_program_grouping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `view_student_grouping`
--

DROP TABLE IF EXISTS `view_student_grouping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `view_student_grouping` (
  `program` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted') DEFAULT NULL,
  `set_name` varchar(50) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `student_count` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `view_student_grouping`
--

LOCK TABLES `view_student_grouping` WRITE;
/*!40000 ALTER TABLE `view_student_grouping` DISABLE KEYS */;
/*!40000 ALTER TABLE `view_student_grouping` ENABLE KEYS */;
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
-- Dumping events for database 'igangaschoolofl_students_db'
--

--
-- Dumping routines for database 'igangaschoolofl_students_db'
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
CREATE DEFINER=`igangaschoolofl_students_db`@`localhost` PROCEDURE `AddColIfMissing`(IN `p_schema` VARCHAR(255), IN `p_table` VARCHAR(255), IN `p_col` VARCHAR(255), IN `p_def` TEXT)
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
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `MigratePayroll` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`igangaschoolofl__iq8pceee4-m-wnDL2NXS9rg9R7iAKa3p`@`localhost` PROCEDURE `MigratePayroll`()
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

--
-- Final view structure for view `student_dashboard_view`
--

/*!50001 DROP VIEW IF EXISTS `student_dashboard_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`igangaschoolofl_students_db`@`localhost` SQL SECURITY INVOKER */
/*!50001 VIEW `student_dashboard_view` AS select `s`.`id` AS `id`,`s`.`student_number` AS `student_number`,coalesce(`s`.`full_name`,trim(concat(`s`.`first_name`,' ',coalesce(`s`.`other_name`,''),' ',`s`.`surname`))) AS `full_name`,coalesce(`s`.`course`,`s`.`program`) AS `course`,coalesce(`s`.`year`,`s`.`current_year`) AS `year`,`s`.`set_name` AS `set_name`,`s`.`email` AS `email`,coalesce(`s`.`profile_picture`,`s`.`passport_photo`) AS `profile_picture`,coalesce(`sa`.`gpa`,0) AS `current_gpa`,coalesce(`sf`.`balance`,0) AS `fee_balance`,coalesce(`sa2`.`attendance_rate`,0) AS `attendance_rate` from (((`students` `s` left join (select `student_academic_records`.`student_id` AS `student_id`,`student_academic_records`.`gpa` AS `gpa` from `student_academic_records` where `student_academic_records`.`semester` = (select max(`student_academic_records`.`semester`) from `student_academic_records`) group by `student_academic_records`.`student_id`) `sa` on(`s`.`id` = `sa`.`student_id`)) left join (select `student_attendance`.`student_id` AS `student_id`,sum(case when `student_attendance`.`status` = 'Present' then 1 else 0 end) * 100.0 / count(0) AS `attendance_rate` from `student_attendance` group by `student_attendance`.`student_id`) `sa2` on(`s`.`id` = `sa2`.`student_id`)) left join (select `student_fees`.`student_id` AS `student_id`,sum(`student_fees`.`amount`) AS `balance` from `student_fees` where `student_fees`.`status` in ('Unpaid','Partially Paid','Overdue') group by `student_fees`.`student_id`) `sf` on(`s`.`id` = `sf`.`student_id`)) where `s`.`status` = 'Active' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `student_login_view`
--

/*!50001 DROP VIEW IF EXISTS `student_login_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`igangaschoolofl_students_db`@`localhost` SQL SECURITY INVOKER */
/*!50001 VIEW `student_login_view` AS select `students`.`id` AS `id`,`students`.`student_number` AS `student_number`,coalesce(`students`.`full_name`,trim(concat(`students`.`first_name`,' ',coalesce(`students`.`other_name`,''),' ',`students`.`surname`))) AS `full_name`,`students`.`email` AS `email`,`students`.`password` AS `password`,coalesce(`students`.`course`,`students`.`program`) AS `course`,`students`.`status` AS `status`,`students`.`last_login` AS `last_login`,`students`.`login_attempts` AS `login_attempts`,`students`.`is_first_login` AS `is_first_login` from `students` where `students`.`status` = 'Active' */;
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

-- Dump completed on 2026-07-05 16:56:22
