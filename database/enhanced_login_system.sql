-- Enhanced ISNM School Management System Database with Login System
-- Students login with NSIN number, name, and contact number
-- Staff login with names and passwords

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS isnm_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE isnm_school;

-- Drop existing tables to start fresh
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS academic_summary;
DROP TABLE IF EXISTS academic_records;
DROP TABLE IF EXISTS fee_payments;
DROP TABLE IF EXISTS student_fee_accounts;
DROP TABLE IF EXISTS fee_structures;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS staff_applications;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS payroll;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS organizational_positions;
DROP TABLE IF EXISTS users;

-- Enhanced users table for all system users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default-avatar.png',
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `nsin_number` varchar(20) DEFAULT NULL, -- NSIN number for students
  `religion` varchar(50) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(2) DEFAULT 0,
  `account_locked` tinyint(1) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `nsin_number` (`nsin_number`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organizational positions table
CREATE TABLE `organizational_positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position_title` varchar(100) NOT NULL,
  `position_category` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `reporting_to` varchar(100) DEFAULT NULL,
  `access_level` int(2) DEFAULT 1,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Applications table for student applications
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `nsin_number` varchar(20) NOT NULL, -- NSIN number required
  `address` text DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `program_applied` varchar(100) NOT NULL,
  `level_applied` varchar(50) NOT NULL,
  `intake_year` varchar(4) NOT NULL,
  `intake_period` varchar(50) DEFAULT NULL,
  `previous_school` varchar(200) DEFAULT NULL,
  `previous_qualification` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(150) DEFAULT NULL,
  `guardian_address` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `application_date` date NOT NULL,
  `status` enum('pending','under_review','approved','rejected','withdrawn') DEFAULT 'pending',
  `reviewer_comments` text DEFAULT NULL,
  `reviewer_id` varchar(20) DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nsin_number` (`nsin_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students table with enhanced login fields
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `application_id` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `nsin_number` varchar(20) NOT NULL, -- NSIN number for login
  `address` text DEFAULT NULL,
  `phone` varchar(20) NOT NULL, -- Contact number for login
  `email` varchar(150) NOT NULL,
  `program` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `intake_year` varchar(4) NOT NULL,
  `intake_period` varchar(50) DEFAULT NULL,
  `current_year` int(2) DEFAULT 1,
  `current_semester` int(2) DEFAULT 1,
  `enrollment_date` date NOT NULL,
  `expected_graduation_date` date DEFAULT NULL,
  `status` enum('active','suspended','graduated','withdrawn') DEFAULT 'active',
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(150) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default-student.png',
  `login_attempts` int(2) DEFAULT 0,
  `account_locked` tinyint(1) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nsin_number` (`nsin_number`),
  KEY `application_id` (`application_id`),
  KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fee structures table
CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `semester` int(1) NOT NULL,
  `tuition_fees` decimal(10,2) NOT NULL,
  `registration_fees` decimal(10,2) NOT NULL,
  `library_fees` decimal(10,2) NOT NULL,
  `laboratory_fees` decimal(10,2) NOT NULL,
  `examination_fees` decimal(10,2) NOT NULL,
  `student_union_fees` decimal(10,2) NOT NULL,
  `other_fees` decimal(10,2) DEFAULT 0,
  `total_fees` decimal(10,2) NOT NULL,
  `payment_deadline` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_structure_unique` (`program`, `level`, `academic_year`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student fee accounts table
CREATE TABLE `student_fee_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `program` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `year` int(2) NOT NULL,
  `semester` int(2) NOT NULL,
  `total_fees` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0,
  `balance` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('unpaid','partially_paid','fully_paid','overdue') DEFAULT 'unpaid',
  `last_payment_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `academic_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fee payments table
CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(20) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `fee_account_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_deposit','mobile_money','cheque','online_transfer') NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'verified',
  `verified_by` varchar(20) DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic records table
CREATE TABLE `academic_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `semester` int(1) NOT NULL,
  `year` int(2) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `course_type` enum('Core','Elective','Practical') NOT NULL,
  `credits` decimal(3,1) NOT NULL,
  `assessment_marks` decimal(5,2) DEFAULT 0,
  `exam_marks` decimal(5,2) DEFAULT 0,
  `total_marks` decimal(5,2) NOT NULL,
  `grade` varchar(2) NOT NULL,
  `grade_points` decimal(3,2) NOT NULL,
  `gpa_contribution` decimal(5,2) NOT NULL,
  `lecturer` varchar(100) NOT NULL,
  `entered_by` varchar(20) NOT NULL,
  `entry_date` date NOT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `academic_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic summary table
CREATE TABLE `academic_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `semester` int(1) NOT NULL,
  `gpa` decimal(3,2) NOT NULL,
  `class_position` int(5) NOT NULL,
  `total_students` int(5) NOT NULL,
  `total_credits` decimal(5,1) NOT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `summary_unique` (`student_id`, `academic_year`, `semester`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `sender_id` varchar(20) NOT NULL,
  `sender_role` varchar(50) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message_content` text NOT NULL,
  `message_type` enum('general','academic','financial','administrative','emergency') NOT NULL,
  `priority` enum('normal','important','urgent') DEFAULT 'normal',
  `sent_date` date NOT NULL,
  `status` enum('sent','read','deleted') DEFAULT 'sent',
  `read_date` date DEFAULT NULL,
  `deleted_date` date DEFAULT NULL,
  `parent_message_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `sender_id` (`sender_id`),
  KEY `parent_message_id` (`parent_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Appointments table
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff applications table
CREATE TABLE `staff_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `position_applied` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `experience_years` int(2) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `application_date` date NOT NULL,
  `status` enum('pending','under_review','shortlisted','interviewed','offered','rejected','withdrawn') DEFAULT 'pending',
  `reviewer_comments` text DEFAULT NULL,
  `reviewer_id` varchar(20) DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leave requests table
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(20) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(3) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approver_id` varchar(20) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `approver_comments` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payroll table
CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(20) NOT NULL,
  `pay_period` varchar(20) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) DEFAULT 0,
  `overtime` decimal(10,2) DEFAULT 0,
  `gross_pay` decimal(10,2) NOT NULL,
  `paye` decimal(10,2) DEFAULT 0,
  `nssf` decimal(10,2) DEFAULT 0,
  `other_deductions` decimal(10,2) DEFAULT 0,
  `net_pay` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `status` enum('pending','processed','paid') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_description` text NOT NULL,
  `module_affected` varchar(100) DEFAULT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `activity_date` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `activity_date` (`activity_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert organizational positions
INSERT INTO `organizational_positions` (`position_title`, `position_category`, `department`, `access_level`, `description`) VALUES
('Director General', 'Executive', 'Administration', 10, 'Overall management of the institution'),
('Chief Executive Officer', 'Executive', 'Administration', 9, 'Chief executive officer'),
('Director Academics', 'Director', 'Academics', 8, 'Oversee academic programs'),
('Director ICT', 'Director', 'ICT', 8, 'Manage ICT infrastructure'),
('Director Finance', 'Director', 'Finance', 8, 'Oversee financial operations'),
('School Principal', 'Management', 'Administration', 7, 'Overall school management'),
('Deputy Principal', 'Management', 'Administration', 6, 'Assist principal in school management'),
('School Bursar', 'Management', 'Finance', 6, 'Manage school finances'),
('Academic Registrar', 'Management', 'Academics', 6, 'Manage academic records and registration'),
('HR Manager', 'Management', 'HR', 6, 'Manage human resources'),
('Head of Department', 'Management', 'Academics', 5, 'Manage departmental operations'),
('Lecturers', 'Academic', 'Academics', 4, 'Teaching and research'),
('Students', 'Student', 'Academics', 1, 'Student users'),
('School Secretary', 'Administrative', 'Administration', 5, 'Administrative support');

-- Insert system users with proper login details (Staff login with names and passwords)
INSERT INTO `users` (`user_id`, `username`, `password`, `first_name`, `last_name`, `email`, `phone`, `role`, `department`, `status`) VALUES
('DIR001', 'john.mugisha', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Mugisha', 'director@isnm.ac.ug', '+256771234567', 'Director General', 'Administration', 'active'),
('CEO001', 'sarah.nakato', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Nakato', 'ceo@isnm.ac.ug', '+256772345678', 'Chief Executive Officer', 'Administration', 'active'),
('DIR002', 'michael.mukasa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael', 'Mukasa', 'academics@isnm.ac.ug', '+256773456789', 'Director Academics', 'Academics', 'active'),
('DIR003', 'david.ssekandi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Ssekandi', 'ict@isnm.ac.ug', '+256774567890', 'Director ICT', 'ICT', 'active'),
('DIR004', 'grace.namulinda', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Grace', 'Namulinda', 'finance@isnm.ac.ug', '+256775678901', 'Director Finance', 'Finance', 'active'),
('PRN001', 'peter.lutaaya', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Peter', 'Lutaaya', 'principal@isnm.ac.ug', '+256776789012', 'School Principal', 'Administration', 'active'),
('SEC001', 'joy.nabwire', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Joy', 'Nabwire', 'secretary@isnm.ac.ug', '+256777890123', 'School Secretary', 'Administration', 'active'),
('REG001', 'henry.mugisha', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Henry', 'Mugisha', 'registrar@isnm.ac.ug', '+256778901234', 'Academic Registrar', 'Academics', 'active'),
('BUR001', 'patience.nabasumba', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Patience', 'Nabasumba', 'bursar@isnm.ac.ug', '+256779012345', 'School Bursar', 'Finance', 'active'),
('HRM001', 'robert.ssewanyana', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert', 'Ssewanyana', 'hr@isnm.ac.ug', '+256780123456', 'HR Manager', 'HR', 'active');

-- Insert sample students with NSIN numbers (Students login with NSIN number, name, and contact number)
INSERT INTO `students` (`student_id`, `first_name`, `surname`, `other_name`, `date_of_birth`, `gender`, `nationality`, `nsin_number`, `address`, `phone`, `email`, `program`, `level`, `intake_year`, `intake_period`, `enrollment_date`, `status`) VALUES
('ISNM/2025/1001', 'Aisha', 'Nakato', 'Mariam', '2000-03-15', 'Female', 'Ugandan', 'CM1234567890123', 'P.O. Box 456, Kampala', '+256771234567', 'aisha.nakato@students.isnm.ac.ug', 'Diploma Nursing', 'Diploma', '2025', 'January', '2025-01-15', 'active'),
('ISNM/2025/1002', 'Brian', 'Mugisha', 'Peter', '1999-07-22', 'Male', 'Ugandan', 'CM1234567890124', 'P.O. Box 789, Jinja', '+256772345678', 'brian.mugisha@students.isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2025', 'January', '2025-01-15', 'active'),
('ISNM/2025/1003', 'Catherine', 'Nalwoga', 'Grace', '2001-01-10', 'Female', 'Ugandan', 'CM1234567890125', 'P.O. Box 234, Mbale', '+256773456789', 'catherine.nalwoga@students.isnm.ac.ug', 'Diploma Midwifery', 'Diploma', '2025', 'May', '2025-05-10', 'active'),
('ISNM/2025/1004', 'David', 'Ssempijja', 'James', '2000-11-28', 'Male', 'Ugandan', 'CM1234567890126', 'P.O. Box 567, Iganga', '+256774567890', 'david.ssempijja@students.isnm.ac.ug', 'Certificate Nursing', 'Certificate', '2025', 'May', '2025-05-10', 'active'),
('ISNM/2025/1005', 'Esther', 'Namubiru', 'Faith', '1999-09-05', 'Female', 'Ugandan', 'CM1234567890127', 'P.O. Box 890, Tororo', '+256775678901', 'esther.namubiru@students.isnm.ac.ug', 'Diploma Nursing Extension', 'Diploma', '2025', 'July', '2025-07-15', 'active'),
('ISNM/2025/1006', 'Frank', 'Mbabazi', 'Samuel', '2001-04-18', 'Male', 'Ugandan', 'CM1234567890128', 'P.O. Box 123, Busia', '+256776789012', 'frank.mbabazi@students.isnm.ac.ug', 'Diploma Midwifery Extension', 'Diploma', '2025', 'July', '2025-07-15', 'active'),
('ISNM/2025/1007', 'Grace', 'Nakimuli', 'Hope', '2000-06-30', 'Female', 'Ugandan', 'CM1234567890129', 'P.O. Box 456, Pallisa', '+256777890123', 'grace.nakimuli@students.isnm.ac.ug', 'Certificate Nursing', 'Certificate', '2025', 'September', '2025-09-01', 'active'),
('ISNM/2025/1008', 'Henry', 'Ssegawa', 'Paul', '1999-12-12', 'Male', 'Ugandan', 'CM1234567890130', 'P.O. Box 789, Budaka', '+256778901234', 'henry.ssegawa@students.isnm.ac.ug', 'Certificate Midwifery', 'Certificate', '2025', 'September', '2025-09-01', 'active'),
('ISNM/2025/1009', 'Irene', 'Nalubega', 'Joy', '2001-02-25', 'Female', 'Ugandan', 'CM1234567890131', 'P.O. Box 234, Kibuku', '+256779012345', 'irene.nalubega@students.isnm.ac.ug', 'Diploma Nursing', 'Diploma', '2025', 'September', '2025-09-01', 'active'),
('ISNM/2025/1010', 'Joseph', 'Mukasa', 'Michael', '2000-08-14', 'Male', 'Ugandan', 'CM1234567890132', 'P.O. Box 567, Butaleja', '+256780123456', 'joseph.mukasa@students.isnm.ac.ug', 'Diploma Midwifery', 'Diploma', '2025', 'September', '2025-09-01', 'active');

-- Insert sample fee structures
INSERT INTO `fee_structures` (`program`, `level`, `academic_year`, `semester`, `tuition_fees`, `registration_fees`, `library_fees`, `laboratory_fees`, `examination_fees`, `student_union_fees`, `total_fees`, `payment_deadline`) VALUES
('Certificate Nursing', 'Certificate', '2025/2026', 1, 800000, 50000, 30000, 40000, 35000, 25000, 980000, '2025-09-30'),
('Certificate Nursing', 'Certificate', '2025/2026', 2, 800000, 50000, 30000, 40000, 35000, 25000, 980000, '2026-02-28'),
('Diploma Nursing', 'Diploma', '2025/2026', 1, 1200000, 75000, 45000, 60000, 50000, 35000, 1465000, '2025-09-30'),
('Diploma Nursing', 'Diploma', '2025/2026', 2, 1200000, 75000, 45000, 60000, 50000, 35000, 1465000, '2026-02-28'),
('Certificate Midwifery', 'Certificate', '2025/2026', 1, 850000, 55000, 35000, 45000, 40000, 30000, 1055000, '2025-09-30'),
('Certificate Midwifery', 'Certificate', '2025/2026', 2, 850000, 55000, 35000, 45000, 40000, 30000, 1055000, '2026-02-28'),
('Diploma Midwifery', 'Diploma', '2025/2026', 1, 1300000, 80000, 50000, 70000, 55000, 40000, 1595000, '2025-09-30'),
('Diploma Midwifery', 'Diploma', '2025/2026', 2, 1300000, 80000, 50000, 70000, 55000, 40000, 1595000, '2026-02-28');

-- Create indexes for better performance
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_students_program ON students(program);
CREATE INDEX idx_students_nsin ON students(nsin_number);
CREATE INDEX idx_students_phone ON students(phone);
CREATE INDEX idx_academic_records_student_year ON academic_records(student_id, academic_year);
CREATE INDEX idx_messages_student_date ON messages(student_id, sent_date);
CREATE INDEX idx_activity_logs_date ON activity_logs(activity_date);

-- Create views for common queries
CREATE VIEW student_summary AS
SELECT 
    s.student_id,
    s.first_name,
    s.surname,
    s.nsin_number,
    s.phone,
    s.program,
    s.level,
    s.status,
    COUNT(ar.id) as total_courses,
    COALESCE(AVG(ar.grade_points), 0) as average_gpa,
    COALESCE(SUM(sfa.balance), 0) as outstanding_balance
FROM students s
LEFT JOIN academic_records ar ON s.student_id = ar.student_id
LEFT JOIN student_fee_accounts sfa ON s.student_id = sfa.student_id AND sfa.balance > 0
GROUP BY s.student_id;

CREATE VIEW staff_summary AS
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.username,
    u.role,
    u.department,
    u.status,
    COUNT(DISTINCT lr.id) as leave_requests,
    COUNT(DISTINCT sa.id) as staff_applications
FROM users u
LEFT JOIN leave_requests lr ON u.user_id = lr.staff_id
LEFT JOIN staff_applications sa ON u.user_id = sa.reviewer_id
WHERE u.role != 'Student'
GROUP BY u.user_id;

-- Stored procedures for common operations
DELIMITER //
CREATE PROCEDURE GetStudentTranscript(IN student_id_param VARCHAR(20))
BEGIN
    SELECT 
        ar.academic_year,
        ar.semester,
        ar.course_code,
        ar.course_name,
        ar.credits,
        ar.total_marks,
        ar.grade,
        ar.grade_points,
        ar.lecturer
    FROM academic_records ar
    WHERE ar.student_id = student_id_param
    ORDER BY ar.academic_year ASC, ar.semester ASC;
END //

CREATE PROCEDURE AuthenticateStudent(IN nsin_number_param VARCHAR(20), IN first_name_param VARCHAR(100), IN phone_param VARCHAR(20))
BEGIN
    SELECT 
        s.student_id,
        s.first_name,
        s.surname,
        s.email,
        s.phone,
        s.program,
        s.level,
        s.status,
        s.login_attempts,
        s.account_locked,
        s.locked_until
    FROM students s
    WHERE s.nsin_number = nsin_number_param 
    AND s.first_name = first_name_param 
    AND s.phone = phone_param
    AND s.status = 'active'
    AND (s.account_locked = 0 OR s.locked_until < NOW());
END //

CREATE PROCEDURE AuthenticateStaff(IN username_param VARCHAR(50), IN password_param VARCHAR(255))
BEGIN
    SELECT 
        u.user_id,
        u.username,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        u.role,
        u.department,
        u.status,
        u.login_attempts,
        u.account_locked,
        u.locked_until
    FROM users u
    WHERE u.username = username_param 
    AND u.password = password_param
    AND u.status = 'active'
    AND (u.account_locked = 0 OR u.locked_until < NOW());
END //

DELIMITER ;

-- Triggers for maintaining data integrity
DELIMITER //
CREATE TRIGGER update_fee_account_balance 
AFTER INSERT ON fee_payments
FOR EACH ROW
BEGIN
    UPDATE student_fee_accounts 
    SET amount_paid = amount_paid + NEW.amount_paid,
        balance = balance - NEW.amount_paid,
        last_payment_date = NEW.payment_date,
        status = CASE 
            WHEN (balance - NEW.amount_paid) <= 0 THEN 'fully_paid'
            WHEN amount_paid > 0 THEN 'partially_paid'
            ELSE 'unpaid'
        END
    WHERE id = NEW.fee_account_id;
END //

CREATE TRIGGER log_student_login_attempt
AFTER UPDATE ON students
FOR EACH ROW
BEGIN
    IF NEW.login_attempts > OLD.login_attempts OR NEW.account_locked != OLD.account_locked THEN
        INSERT INTO activity_logs (user_id, user_role, activity_type, activity_description, module_affected, record_id, activity_date)
        VALUES (
            NEW.student_id,
            'Student',
            CASE 
                WHEN NEW.account_locked = 1 AND OLD.account_locked = 0 THEN 'Account Locked'
                WHEN NEW.login_attempts > OLD.login_attempts THEN 'Login Failed'
                ELSE 'Login Attempt Updated'
            END,
            CONCAT('Student login attempt updated for: ', NEW.student_id, ' - Attempts: ', NEW.login_attempts),
            'students',
            NEW.student_id,
            NOW()
        );
    END IF;
END //

DELIMITER ;

-- Set default values and constraints
ALTER TABLE academic_records ADD CONSTRAINT chk_total_marks CHECK (total_marks >= 0 AND total_marks <= 100);
ALTER TABLE academic_records ADD CONSTRAINT chk_grade_points CHECK (grade_points >= 0 AND grade_points <= 4.0);
ALTER TABLE student_fee_accounts ADD CONSTRAINT chk_balance CHECK (balance >= 0);
ALTER TABLE fee_payments ADD CONSTRAINT chk_amount_paid CHECK (amount_paid > 0);

-- Final database setup complete
SELECT 'Enhanced ISNM School Management System Database with Login System Setup Complete!' as status;
