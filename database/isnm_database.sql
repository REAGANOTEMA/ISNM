-- Iganga School of Nursing and Midwifery Database Structure
-- Created for comprehensive school management system

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `isnm_school`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
-- Universal user table for all system users
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default-avatar.png',
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizational_positions`
-- Defines the organizational structure
--

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

-- --------------------------------------------------------

--
-- Table structure for table `applications`
-- Student application records
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `country_of_residence` varchar(50) NOT NULL,
  `home_district` varchar(100) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `spouse_name` varchar(100) DEFAULT NULL,
  `number_of_children` int(2) DEFAULT 0,
  `disability` varchar(10) DEFAULT 'No',
  `disability_type` varchar(100) DEFAULT NULL,
  `disability_description` text DEFAULT NULL,
  `fee_payer` varchar(50) DEFAULT NULL,
  `parent_name` varchar(200) DEFAULT NULL,
  `parent_nationality` varchar(50) DEFAULT NULL,
  `parent_address` text DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `parent_email` varchar(150) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_email` varchar(150) DEFAULT NULL,
  `program_applied` varchar(100) NOT NULL,
  `level_applying` varchar(50) NOT NULL,
  `intake_period` varchar(50) DEFAULT NULL,
  `uce_index_number` varchar(50) DEFAULT NULL,
  `uce_year` varchar(4) DEFAULT NULL,
  `uce_english_grade` varchar(5) DEFAULT NULL,
  `uce_maths_grade` varchar(5) DEFAULT NULL,
  `uce_biology_grade` varchar(5) DEFAULT NULL,
  `uce_chemistry_grade` varchar(5) DEFAULT NULL,
  `uce_physics_grade` varchar(5) DEFAULT NULL,
  `uace_index_number` varchar(50) DEFAULT NULL,
  `uace_year` varchar(4) DEFAULT NULL,
  `uace_grades` text DEFAULT NULL,
  `diploma_exam_number` varchar(50) DEFAULT NULL,
  `diploma_year_completion` varchar(4) DEFAULT NULL,
  `diploma_year_entry` varchar(4) DEFAULT NULL,
  `diploma_grades` text DEFAULT NULL,
  `sports_activities` text DEFAULT NULL,
  `positions_held` text DEFAULT NULL,
  `course_motivation` text DEFAULT NULL,
  `status` enum('pending','under_review','accepted','rejected','admitted') DEFAULT 'pending',
  `interview_date` datetime DEFAULT NULL,
  `interview_score` decimal(5,2) DEFAULT NULL,
  `admission_date` datetime DEFAULT NULL,
  `student_id_assigned` varchar(20) DEFAULT NULL,
  `academic_document_path` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `additional_documents` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
-- Enrolled students information
--

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
  `address` text DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
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
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`),
  KEY `application_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
-- Fee structure for different programs
--

CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `year` int(2) NOT NULL,
  `semester` int(2) DEFAULT 1,
  `tuition_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `accommodation_fee` decimal(10,2) DEFAULT 0.00,
  `clinical_fee` decimal(10,2) DEFAULT 0.00,
  `library_fee` decimal(10,2) DEFAULT 0.00,
  `ict_fee` decimal(10,2) DEFAULT 0.00,
  `student_union_fee` decimal(10,2) DEFAULT 0.00,
  `medical_fee` decimal(10,2) DEFAULT 0.00,
  `sports_fee` decimal(10,2) DEFAULT 0.00,
  `development_fee` decimal(10,2) DEFAULT 0.00,
  `other_fees` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'UGX',
  `academic_year` varchar(9) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_accounts`
-- Individual student fee accounts
--

CREATE TABLE `student_fee_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `program` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `year` int(2) NOT NULL,
  `semester` int(2) NOT NULL,
  `total_fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `late_fee_penalty` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `scholarship_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('unpaid','partially_paid','fully_paid','overdue') DEFAULT 'unpaid',
  `due_date` date NOT NULL,
  `last_payment_date` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
-- Payment records
--

CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(30) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `fee_account_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_deposit','mobile_money','cheque','online_transfer') NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `mobile_money_provider` enum('mtn','airtel') DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` datetime NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `receipt_generated` tinyint(1) DEFAULT 0,
  `payment_proof_path` varchar(255) DEFAULT NULL,
  `verified_by` varchar(100) DEFAULT NULL,
  `verification_date` datetime DEFAULT NULL,
  `status` enum('pending','verified','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `student_id` (`student_id`),
  KEY `fee_account_id` (`fee_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_records`
-- Student academic information
--

CREATE TABLE `academic_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `semester` int(2) NOT NULL,
  `year` int(2) NOT NULL,
  `program` varchar(100) NOT NULL,
  `courses` text DEFAULT NULL,
  `grades` text DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `class_position` int(3) DEFAULT NULL,
  `total_students` int(3) DEFAULT NULL,
  `attendance_percentage` decimal(5,2) DEFAULT NULL,
  `conduct_grade` varchar(10) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `transcript_generated` tinyint(1) DEFAULT 0,
  `transcript_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
-- Internal communication system
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` varchar(30) NOT NULL,
  `sender_id` varchar(20) NOT NULL,
  `sender_role` varchar(50) NOT NULL,
  `recipient_id` varchar(20) NOT NULL,
  `recipient_role` varchar(50) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message_content` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `message_type` enum('general','academic','financial','administrative','emergency') DEFAULT 'general',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('sent','delivered','read','replied') DEFAULT 'sent',
  `sent_date` datetime NOT NULL,
  `read_date` datetime DEFAULT NULL,
  `reply_to_message_id` varchar(30) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_id` (`message_id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_id` (`recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
-- Budget management
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` varchar(20) NOT NULL,
  `budget_name` varchar(200) NOT NULL,
  `department` varchar(100) NOT NULL,
  `budget_type` enum('annual','semester','term','project') NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `total_budget_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allocated_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `spent_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'UGX',
  `status` enum('draft','approved','active','completed','cancelled') DEFAULT 'draft',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_by` varchar(20) NOT NULL,
  `approved_by` varchar(20) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_id` (`budget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
-- Expense tracking
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_id` varchar(20) NOT NULL,
  `budget_id` varchar(20) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `expense_category` varchar(100) NOT NULL,
  `expense_description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'UGX',
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','mobile_money','cheque') NOT NULL,
  `vendor_name` varchar(200) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `approved_by` varchar(20) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_id` (`expense_id`),
  KEY `budget_id` (`budget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
-- Asset and inventory management
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` varchar(20) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(5) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_value` decimal(12,2) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `condition_status` enum('new','good','fair','poor','damaged') DEFAULT 'good',
  `warranty_expiry` date DEFAULT NULL,
  `depreciation_rate` decimal(5,2) DEFAULT NULL,
  `current_value` decimal(12,2) DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `status` enum('available','in_use','maintenance','disposed') DEFAULT 'available',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
-- System configuration
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_description` varchar(255) DEFAULT NULL,
  `setting_category` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
-- System activity tracking
--

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

-- --------------------------------------------------------

--
-- Insert default organizational positions
--

INSERT INTO `organizational_positions` (`position_title`, `position_category`, `department`, `reporting_to`, `access_level`, `description`) VALUES
('Director General', 'Executive Leadership', NULL, NULL, 10, 'Overall Institution Leadership'),
('Chief Executive Officer', 'Executive Leadership', NULL, 'Director General', 9, 'Overall Institution Leadership'),
('Director Academics', 'Executive Leadership', 'Academic Affairs', 'Director General', 8, 'Academic Programs Oversight'),
('Director ICT', 'Executive Leadership', 'Technology', 'Director General', 8, 'IT Infrastructure & Systems'),
('Director Finance', 'Executive Leadership', 'Financial Affairs', 'Director General', 8, 'Financial Management'),
('School Principal', 'School Management', 'Academic Leadership', 'Director Academics', 7, 'Academic Leadership'),
('Deputy Principal', 'School Management', 'Academic Support', 'School Principal', 6, 'Academic Support'),
('School Bursar', 'School Management', 'Financial Operations', 'Director Finance', 7, 'Financial Operations'),
('Academic Registrar', 'Administrative Staff', 'Student Records', 'School Principal', 6, 'Student Registration'),
('HR Manager', 'Administrative Staff', 'Human Resources', 'Director General', 6, 'Staff Management'),
('School Secretary', 'Administrative Staff', 'Administrative Support', 'School Principal', 4, 'Office Administration'),
('School Librarian', 'Administrative Staff', 'Library Management', 'Director Academics', 5, 'Library Resources'),
('Head of Nursing', 'Academic Staff', 'Nursing Department', 'Director Academics', 6, 'Nursing Program Leadership'),
('Head of Midwifery', 'Academic Staff', 'Midwifery Department', 'Director Academics', 6, 'Midwifery Program Leadership'),
('Senior Lecturers', 'Academic Staff', 'Teaching Staff', 'Director Academics', 5, 'Advanced Teaching'),
('Lecturers', 'Academic Staff', 'Teaching Staff', 'Head of Nursing', 4, 'Classroom Teaching'),
('Matrons', 'Support Staff', 'Student Welfare', 'School Principal', 4, 'Student Care & Support'),
('Lab Technicians', 'Support Staff', 'Laboratory Services', 'Director ICT', 4, 'Lab Management'),
('Drivers', 'Support Staff', 'Transport Services', 'School Principal', 3, 'Transportation Management'),
('Security', 'Support Staff', 'Campus Security', 'School Principal', 3, 'Safety & Security'),
('Guild President', 'Student Leadership', 'Student Government', 'School Principal', 3, 'Student Government'),
('Class Representatives', 'Student Leadership', 'Class Leadership', 'Lecturers', 2, 'Class Representation'),
('Students', 'Student Leadership', 'Student Body', 'Class Representatives', 1, 'All Student Access');

-- --------------------------------------------------------

--
-- Insert default system settings
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_description`, `setting_category`) VALUES
('school_name', 'Iganga School of Nursing and Midwifery', 'Official name of the institution', 'general'),
('school_acronym', 'ISNM', 'Short form of school name', 'general'),
('school_motto', 'Chosen to serve', 'School motto', 'general'),
('school_vision', 'To have a healthy and disease free community', 'School vision statement', 'general'),
('school_mission', 'To produce world class and competitive health workers through the use of modern teaching methods, technology and research', 'School mission statement', 'general'),
('school_address', 'P.O. Box 418, Iganga', 'School postal address', 'contact'),
('school_phone', '+256 787 630 255, +256 799 979 625, +256 753 134 690', 'School phone numbers', 'contact'),
('school_email', 'iganganursingschool@gmail.com', 'School email address', 'contact'),
('school_website', 'www.isnm.ac.ug', 'School website', 'contact'),
('academic_year', '2025/2026', 'Current academic year', 'academic'),
('currency', 'UGX', 'Default currency', 'financial'),
('late_fee_percentage', '5', 'Percentage for late payment penalty', 'financial'),
('application_fee', '95000', 'Application fee amount', 'financial'),
('developer_name', 'Reagan Otema', 'System developer name', 'system'),
('developer_whatsapp_mtn', '+256772514889', 'Developer MTN WhatsApp', 'system'),
('developer_whatsapp_airtel', '+256730314979', 'Developer Airtel WhatsApp', 'system');

-- --------------------------------------------------------

--
-- Create indexes for better performance
--

CREATE INDEX idx_students_program ON students(program);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_fee_accounts_student ON student_fee_accounts(student_id);
CREATE INDEX idx_fee_accounts_status ON student_fee_accounts(status);
CREATE INDEX idx_payments_student ON fee_payments(student_id);
CREATE INDEX idx_payments_date ON fee_payments(payment_date);
CREATE INDEX idx_messages_recipient ON messages(recipient_id, recipient_role);
CREATE INDEX idx_messages_sender ON messages(sender_id, sender_role);
CREATE INDEX idx_activity_user ON activity_logs(user_id);
CREATE INDEX idx_activity_date ON activity_logs(activity_date);

-- --------------------------------------------------------

--
-- Foreign key constraints
--

ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE SET NULL;

ALTER TABLE `student_fee_accounts`
  ADD CONSTRAINT `fk_fee_accounts_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

ALTER TABLE `fee_payments`
  ADD CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_fee_account` FOREIGN KEY (`fee_account_id`) REFERENCES `student_fee_accounts` (`id`) ON DELETE CASCADE;

ALTER TABLE `academic_records`
  ADD CONSTRAINT `fk_academic_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_budget` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`budget_id`) ON DELETE SET NULL;

COMMIT;
