-- ============================================================================
-- ISNM MISSING TABLES MIGRATION
-- Created: 2026-07-19
-- Purpose: Create all tables referenced in PHP code but missing from schema dumps
-- Safe: All use CREATE TABLE IF NOT EXISTS - preserves existing data
-- ============================================================================

-- ============================================================================
-- DATABASE: igangaschool_students
-- ============================================================================

USE `igangaschool_students`;

-- student_login_attempts: Rate limiting for student authentication
CREATE TABLE IF NOT EXISTS `student_login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_number` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `success` TINYINT(1) DEFAULT 0,
    KEY `idx_student_number` (`student_number`),
    KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- course_prerequisites: Course prerequisite requirements
CREATE TABLE IF NOT EXISTS `course_prerequisites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_code` VARCHAR(20) NOT NULL,
    `prerequisite_code` VARCHAR(20) NOT NULL,
    `minimum_grade` VARCHAR(5) DEFAULT 'D',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_course_prereq` (`course_code`, `prerequisite_code`),
    KEY `idx_course` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- lab_inventory_items: Lab equipment and inventory tracking
CREATE TABLE IF NOT EXISTS `lab_inventory_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) DEFAULT 'General',
    `quantity` INT DEFAULT 0,
    `minimum_level` INT DEFAULT 0,
    `unit` VARCHAR(50) DEFAULT 'piece',
    `location` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Active','Inactive','Out of Stock') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- student_fee_tracking: Tracks student fee balances per fee category
CREATE TABLE IF NOT EXISTS `student_fee_tracking` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_number` VARCHAR(50) NOT NULL,
    `fee_category` VARCHAR(100) NOT NULL,
    `total_amount` DECIMAL(12,2) DEFAULT 0,
    `amount_paid` DECIMAL(12,2) DEFAULT 0,
    `balance` DECIMAL(12,2) GENERATED ALWAYS AS (`total_amount` - `amount_paid`) STORED,
    `academic_year` VARCHAR(10) DEFAULT NULL,
    `semester` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('Pending','Partial','Paid','Overdue','Waived') DEFAULT 'Pending',
    `due_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_student_number` (`student_number`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- student_fee_assignments: Assigns fee structures to students
CREATE TABLE IF NOT EXISTS `student_fee_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_number` VARCHAR(50) NOT NULL,
    `fee_structure_id` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `discount` DECIMAL(12,2) DEFAULT 0,
    `scholarship_id` INT DEFAULT NULL,
    `academic_year` VARCHAR(10) DEFAULT NULL,
    `semester` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('Active','Waived','Cancelled') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_structure` (`fee_structure_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- student_invoices: Student invoice records
CREATE TABLE IF NOT EXISTS `student_invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL,
    `student_id` INT NOT NULL,
    `student_number` VARCHAR(50) NOT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `amount_paid` DECIMAL(12,2) DEFAULT 0,
    `balance` DECIMAL(12,2) GENERATED ALWAYS AS (`total_amount` - `amount_paid`) STORED,
    `description` TEXT DEFAULT NULL,
    `due_date` DATE DEFAULT NULL,
    `status` ENUM('Draft','Sent','Paid','Partial','Overdue','Cancelled') DEFAULT 'Draft',
    `academic_year` VARCHAR(10) DEFAULT NULL,
    `semester` VARCHAR(20) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_invoice_number` (`invoice_number`),
    KEY `idx_student` (`student_id`),
    KEY `idx_student_number` (`student_number`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================================
-- DATABASE: igangaschool_staffs
-- ============================================================================

USE `igangaschool_staffs`;

-- enrollments: Student course enrollments (referenced by department-restrictions.php)
CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_number` VARCHAR(50) NOT NULL,
    `course_code` VARCHAR(20) NOT NULL,
    `course_title` VARCHAR(255) DEFAULT NULL,
    `semester` VARCHAR(20) DEFAULT NULL,
    `academic_year` VARCHAR(10) DEFAULT NULL,
    `enrollment_date` DATE DEFAULT (CURRENT_DATE),
    `status` ENUM('Enrolled','Dropped','Completed','Withdrawn') DEFAULT 'Enrolled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_student_number` (`student_number`),
    KEY `idx_course` (`course_code`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- role_permissions: Extended role-based permissions (referenced by enterprise_auth.php)
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `permission_key` VARCHAR(100) NOT NULL,
    `permission_value` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_role_permission` (`role_id`, `permission_key`),
    KEY `idx_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- news_categories: Categories for news articles (referenced by dashboards/news.php)
CREATE TABLE IF NOT EXISTS `news_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Matron Tables (referenced by dashboards/matrons.php) ──

CREATE TABLE IF NOT EXISTS `activity_participation` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `activity_id` INT DEFAULT NULL,
    `activity_name` VARCHAR(255) NOT NULL,
    `participation_date` DATE NOT NULL,
    `status` ENUM('Present','Absent','Excused') DEFAULT 'Present',
    `remarks` TEXT DEFAULT NULL,
    `recorded_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_date` (`participation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `activity_schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `activity_name` VARCHAR(255) NOT NULL,
    `activity_type` VARCHAR(100) DEFAULT 'General',
    `description` TEXT DEFAULT NULL,
    `schedule_date` DATE NOT NULL,
    `schedule_time` TIME DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `max_participants` INT DEFAULT 0,
    `status` ENUM('Scheduled','Active','Completed','Cancelled') DEFAULT 'Scheduled',
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_date` (`schedule_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `behavior_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `behavior_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `severity` ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    `action_taken` TEXT DEFAULT NULL,
    `reported_by` INT DEFAULT NULL,
    `report_date` DATE DEFAULT (CURRENT_DATE),
    `status` ENUM('Open','Under Review','Resolved','Escalated') DEFAULT 'Open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_date` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `counseling_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `counselor_name` VARCHAR(255) DEFAULT NULL,
    `session_date` DATE NOT NULL,
    `session_type` VARCHAR(100) DEFAULT 'General',
    `notes` TEXT DEFAULT NULL,
    `follow_up_date` DATE DEFAULT NULL,
    `status` ENUM('Scheduled','Completed','Cancelled','Follow-up') DEFAULT 'Completed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_date` (`session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `discipline_cases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `offense_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `date_reported` DATE DEFAULT (CURRENT_DATE),
    `reported_by` INT DEFAULT NULL,
    `action_taken` TEXT DEFAULT NULL,
    `penalty` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Open','Under Review','Resolved','Appealed') DEFAULT 'Open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_date` (`date_reported`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `emergency_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `emergency_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `date_reported` DATE DEFAULT (CURRENT_DATE),
    `time_reported` TIME DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `action_taken` TEXT DEFAULT NULL,
    `reported_by` INT DEFAULT NULL,
    `status` ENUM('Active','Resolved','Closed') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_date` (`date_reported`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `group_counseling` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `session_date` DATE NOT NULL,
    `session_time` TIME DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `facilitator` VARCHAR(255) DEFAULT NULL,
    `max_participants` INT DEFAULT 20,
    `status` ENUM('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_date` (`session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `hostel_activities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `activity_name` VARCHAR(255) NOT NULL,
    `activity_type` VARCHAR(100) DEFAULT 'General',
    `description` TEXT DEFAULT NULL,
    `activity_date` DATE NOT NULL,
    `activity_time` TIME DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `hostel_block` VARCHAR(50) DEFAULT NULL,
    `organized_by` INT DEFAULT NULL,
    `status` ENUM('Planned','Active','Completed','Cancelled') DEFAULT 'Planned',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_date` (`activity_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `maintenance_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `requester_id` INT DEFAULT NULL,
    `requester_name` VARCHAR(255) DEFAULT NULL,
    `facility_type` VARCHAR(100) DEFAULT 'Hostel',
    `location` VARCHAR(255) NOT NULL,
    `issue_description` TEXT NOT NULL,
    `priority` ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    `status` ENUM('Open','In Progress','Completed','Rejected') DEFAULT 'Open',
    `assigned_to` VARCHAR(255) DEFAULT NULL,
    `resolution_notes` TEXT DEFAULT NULL,
    `date_reported` DATE DEFAULT (CURRENT_DATE),
    `date_resolved` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_location` (`location`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `parent_meetings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `parent_name` VARCHAR(255) NOT NULL,
    `parent_phone` VARCHAR(30) DEFAULT NULL,
    `meeting_date` DATE NOT NULL,
    `meeting_time` TIME DEFAULT NULL,
    `reason` TEXT DEFAULT NULL,
    `outcome` TEXT DEFAULT NULL,
    `organized_by` INT DEFAULT NULL,
    `status` ENUM('Scheduled','Completed','Cancelled','Rescheduled') DEFAULT 'Scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_date` (`meeting_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `room_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `hostel_name` VARCHAR(255) NOT NULL,
    `room_number` VARCHAR(50) NOT NULL,
    `bed_number` VARCHAR(20) DEFAULT NULL,
    `assignment_date` DATE DEFAULT (CURRENT_DATE),
    `release_date` DATE DEFAULT NULL,
    `status` ENUM('Active','Released','Transferred') DEFAULT 'Active',
    `assigned_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_room` (`hostel_name`, `room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_medications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `medication_name` VARCHAR(255) NOT NULL,
    `dosage` VARCHAR(100) DEFAULT NULL,
    `frequency` VARCHAR(100) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `prescribed_by` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('Active','Completed','Discontinued') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_referrals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `referral_type` VARCHAR(100) NOT NULL,
    `referred_to` VARCHAR(255) NOT NULL,
    `reason` TEXT NOT NULL,
    `referred_by` INT DEFAULT NULL,
    `referral_date` DATE DEFAULT (CURRENT_DATE),
    `status` ENUM('Pending','In Progress','Completed','Cancelled') DEFAULT 'Pending',
    `outcome` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_date` (`referral_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Lecturer Tables (referenced by dashboards/lecturers.php, senior-lecturers.php) ──

CREATE TABLE IF NOT EXISTS `course_evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_code` VARCHAR(20) NOT NULL,
    `course_title` VARCHAR(255) DEFAULT NULL,
    `lecturer_id` INT NOT NULL,
    `semester` VARCHAR(20) DEFAULT NULL,
    `academic_year` VARCHAR(10) DEFAULT NULL,
    `evaluation_date` DATE DEFAULT (CURRENT_DATE),
    `overall_rating` DECIMAL(3,1) DEFAULT NULL,
    `teaching_quality` DECIMAL(3,1) DEFAULT NULL,
    `course_content` DECIMAL(3,1) DEFAULT NULL,
    `comments` TEXT DEFAULT NULL,
    `evaluator_type` ENUM('Student','Peer','External') DEFAULT 'Student',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_lecturer` (`lecturer_id`),
    KEY `idx_course` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_syllabi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_code` VARCHAR(20) NOT NULL,
    `course_title` VARCHAR(255) DEFAULT NULL,
    `lecturer_id` INT NOT NULL,
    `semester` VARCHAR(20) DEFAULT NULL,
    `academic_year` VARCHAR(10) DEFAULT NULL,
    `objectives` TEXT DEFAULT NULL,
    `topics` TEXT DEFAULT NULL,
    `textbooks` TEXT DEFAULT NULL,
    `assessment_methods` TEXT DEFAULT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('Draft','Submitted','Approved','Published') DEFAULT 'Draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_lecturer` (`lecturer_id`),
    KEY `idx_course` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lecture_schedule` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lecturer_id` INT NOT NULL,
    `course_code` VARCHAR(20) NOT NULL,
    `course_title` VARCHAR(255) DEFAULT NULL,
    `day_of_week` VARCHAR(10) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room` VARCHAR(100) DEFAULT NULL,
    `semester` VARCHAR(20) DEFAULT NULL,
    `academic_year` VARCHAR(10) DEFAULT NULL,
    `status` ENUM('Active','Cancelled','Completed') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_lecturer` (`lecturer_id`),
    KEY `idx_day` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lecturer_counseling` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lecturer_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `session_date` DATE NOT NULL,
    `session_type` VARCHAR(100) DEFAULT 'Academic',
    `notes` TEXT DEFAULT NULL,
    `recommendations` TEXT DEFAULT NULL,
    `follow_up_date` DATE DEFAULT NULL,
    `status` ENUM('Completed','Follow-up Required','Cancelled') DEFAULT 'Completed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_lecturer` (`lecturer_id`),
    KEY `idx_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lecturer_grades` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lecturer_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `student_number` VARCHAR(50) DEFAULT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `course_code` VARCHAR(20) NOT NULL,
    `semester` VARCHAR(20) DEFAULT NULL,
    `academic_year` VARCHAR(10) DEFAULT NULL,
    `ca_marks` DECIMAL(5,2) DEFAULT NULL,
    `exam_marks` DECIMAL(5,2) DEFAULT NULL,
    `total_marks` DECIMAL(5,2) DEFAULT NULL,
    `grade` VARCHAR(5) DEFAULT NULL,
    `status` ENUM('Draft','Submitted','Approved') DEFAULT 'Draft',
    `submitted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_lecturer` (`lecturer_id`),
    KEY `idx_student` (`student_id`),
    KEY `idx_course` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `research_conferences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `staff_id` INT NOT NULL,
    `conference_name` VARCHAR(255) NOT NULL,
    `conference_type` VARCHAR(100) DEFAULT 'Conference',
    `location` VARCHAR(255) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `paper_title` VARCHAR(500) DEFAULT NULL,
    `role` VARCHAR(100) DEFAULT 'Attendee',
    `funding_source` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Planned','Attended','Completed','Cancelled') DEFAULT 'Planned',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `research_publications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `staff_id` INT NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `publication_type` VARCHAR(100) DEFAULT 'Journal Article',
    `journal_name` VARCHAR(255) DEFAULT NULL,
    `publication_date` DATE DEFAULT NULL,
    `doi` VARCHAR(200) DEFAULT NULL,
    `authors` TEXT DEFAULT NULL,
    `abstract` TEXT DEFAULT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('Draft','Submitted','Published','Rejected') DEFAULT 'Draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `research_supervisions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supervisor_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `research_title` VARCHAR(500) NOT NULL,
    `research_type` VARCHAR(100) DEFAULT 'Dissertation',
    `start_date` DATE DEFAULT NULL,
    `expected_end_date` DATE DEFAULT NULL,
    `progress_percentage` INT DEFAULT 0,
    `status` ENUM('Active','On Hold','Completed','Defended') DEFAULT 'Active',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_supervisor` (`supervisor_id`),
    KEY `idx_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Curriculum Table (referenced by dashboards/curriculum-management.php) ──

CREATE TABLE IF NOT EXISTS `curriculum_development` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `program_name` VARCHAR(255) NOT NULL,
    `course_code` VARCHAR(20) NOT NULL,
    `course_title` VARCHAR(255) NOT NULL,
    `version` VARCHAR(10) DEFAULT '1.0',
    `objectives` TEXT DEFAULT NULL,
    `content_outline` TEXT DEFAULT NULL,
    `teaching_methods` TEXT DEFAULT NULL,
    `assessment_methods` TEXT DEFAULT NULL,
    `recommended_textbooks` TEXT DEFAULT NULL,
    `developed_by` INT DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `status` ENUM('Draft','Under Review','Approved','Active','Archived') DEFAULT 'Draft',
    `effective_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_program` (`program_name`),
    KEY `idx_course` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Intake Planning Table (referenced by dashboards/intake-planning.php) ──

CREATE TABLE IF NOT EXISTS `intake_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `program_name` VARCHAR(255) NOT NULL,
    `intake_period` VARCHAR(50) NOT NULL,
    `academic_year` VARCHAR(10) NOT NULL,
    `planned_capacity` INT DEFAULT 0,
    `enrolled_count` INT DEFAULT 0,
    `target_female` INT DEFAULT 0,
    `target_male` INT DEFAULT 0,
    `actual_female` INT DEFAULT 0,
    `actual_male` INT DEFAULT 0,
    `status` ENUM('Planning','Open','Closed','Completed') DEFAULT 'Planning',
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_intake_plan` (`program_name`, `intake_period`, `academic_year`),
    KEY `idx_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Grade Appeals Table (referenced by dashboards/endpoints/grade_appeals.php) ──

CREATE TABLE IF NOT EXISTS `grade_appeals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_number` VARCHAR(50) DEFAULT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `course_code` VARCHAR(20) NOT NULL,
    `course_title` VARCHAR(255) DEFAULT NULL,
    `semester` VARCHAR(20) DEFAULT NULL,
    `original_grade` VARCHAR(5) DEFAULT NULL,
    `appealed_grade` VARCHAR(5) DEFAULT NULL,
    `reason` TEXT NOT NULL,
    `evidence_path` VARCHAR(500) DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL,
    `review_notes` TEXT DEFAULT NULL,
    `status` ENUM('Submitted','Under Review','Approved','Rejected','Final') DEFAULT 'Submitted',
    `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_course` (`course_code`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- staff_inbox: Internal messaging inbox
CREATE TABLE IF NOT EXISTS `staff_inbox` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `sender_name` VARCHAR(255) DEFAULT NULL,
    `recipient_id` INT NOT NULL,
    `recipient_name` VARCHAR(255) DEFAULT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `is_broadcast` TINYINT(1) DEFAULT 0,
    `priority` ENUM('Low','Normal','High','Urgent') DEFAULT 'Normal',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sender` (`sender_id`),
    KEY `idx_recipient` (`recipient_id`),
    KEY `idx_unread` (`recipient_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- student_requests: Student request desk records
CREATE TABLE IF NOT EXISTS `student_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_number` VARCHAR(50) NOT NULL,
    `student_name` VARCHAR(255) DEFAULT NULL,
    `request_type` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('Pending','In Progress','Resolved','Rejected') DEFAULT 'Pending',
    `assigned_to` INT DEFAULT NULL,
    `assigned_to_name` VARCHAR(255) DEFAULT NULL,
    `response` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_student` (`student_id`),
    KEY `idx_student_number` (`student_number`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- student_messages: Student-staff messaging
CREATE TABLE IF NOT EXISTS `student_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `sender_type` ENUM('student','staff') NOT NULL,
    `recipient_id` INT NOT NULL,
    `recipient_type` ENUM('student','staff') NOT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sender` (`sender_id`, `sender_type`),
    KEY `idx_recipient` (`recipient_id`, `recipient_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================================
-- DATABASE: igangaschool_website
-- ============================================================================

USE `igangaschool_website`;

-- news_categories for website DB too (referenced by dashboards/news.php sync)
CREATE TABLE IF NOT EXISTS `news_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- pages: CMS pages for website management
CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `content` LONGTEXT DEFAULT NULL,
    `excerpt` TEXT DEFAULT NULL,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` TEXT DEFAULT NULL,
    `featured_image` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('draft','published','archived') DEFAULT 'draft',
    `page_type` VARCHAR(50) DEFAULT 'page',
    `display_order` INT DEFAULT 0,
    `author_id` INT DEFAULT NULL,
    `published_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================================
-- DATABASE: igangaschool_ict
-- ============================================================================

USE `igangaschool_ict`;

-- ICT Incident Reports
CREATE TABLE IF NOT EXISTS `ict_incidents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `incident_type` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `severity` ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    `reported_by` INT DEFAULT NULL,
    `reported_by_name` VARCHAR(255) DEFAULT NULL,
    `assigned_to` INT DEFAULT NULL,
    `affected_system` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Open','In Progress','Resolved','Closed','Escalated') DEFAULT 'Open',
    `resolution_notes` TEXT DEFAULT NULL,
    `date_reported` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_resolved` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_severity` (`severity`),
    KEY `idx_status` (`status`),
    KEY `idx_date` (`date_reported`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ICT Maintenance Requests
CREATE TABLE IF NOT EXISTS `ict_maintenance_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` INT DEFAULT NULL,
    `asset_name` VARCHAR(255) DEFAULT NULL,
    `request_type` ENUM('Repair','Upgrade','Replacement','Installation','Other') DEFAULT 'Repair',
    `description` TEXT NOT NULL,
    `priority` ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    `requested_by` INT DEFAULT NULL,
    `requested_by_name` VARCHAR(255) DEFAULT NULL,
    `assigned_to` INT DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Requested','Approved','In Progress','Completed','Rejected') DEFAULT 'Requested',
    `estimated_cost` DECIMAL(10,2) DEFAULT NULL,
    `actual_cost` DECIMAL(10,2) DEFAULT NULL,
    `completion_notes` TEXT DEFAULT NULL,
    `date_requested` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_completed` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ICT Network Devices
CREATE TABLE IF NOT EXISTS `ict_network_devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `device_name` VARCHAR(255) NOT NULL,
    `device_type` ENUM('Router','Switch','Access Point','Firewall','Server','Other') NOT NULL,
    `manufacturer` VARCHAR(100) DEFAULT NULL,
    `model` VARCHAR(100) DEFAULT NULL,
    `serial_number` VARCHAR(100) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `mac_address` VARCHAR(17) DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('Active','Inactive','Maintenance','Retired') DEFAULT 'Active',
    `purchase_date` DATE DEFAULT NULL,
    `warranty_expiry` DATE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_type` (`device_type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ICT Reports
CREATE TABLE IF NOT EXISTS `ict_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_title` VARCHAR(255) NOT NULL,
    `report_type` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `content` LONGTEXT DEFAULT NULL,
    `generated_by` INT DEFAULT NULL,
    `report_period_start` DATE DEFAULT NULL,
    `report_period_end` DATE DEFAULT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('Draft','Final','Archived') DEFAULT 'Draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_type` (`report_type`),
    KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ICT Software Inventory
CREATE TABLE IF NOT EXISTS `ict_software_inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `software_name` VARCHAR(255) NOT NULL,
    `version` VARCHAR(50) DEFAULT NULL,
    `developer` VARCHAR(255) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `license_type` ENUM('Free','Open Source','Commercial','Educational','Trial') DEFAULT 'Free',
    `total_licenses` INT DEFAULT 0,
    `used_licenses` INT DEFAULT 0,
    `cost_per_license` DECIMAL(10,2) DEFAULT 0,
    `purchase_date` DATE DEFAULT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `status` ENUM('Active','Expired','Under Review') DEFAULT 'Active',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ICT Software Licenses
CREATE TABLE IF NOT EXISTS `ict_software_licenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `software_id` INT NOT NULL,
    `license_key` VARCHAR(500) DEFAULT NULL,
    `license_type` VARCHAR(50) DEFAULT NULL,
    `assigned_to` INT DEFAULT NULL,
    `assigned_to_name` VARCHAR(255) DEFAULT NULL,
    `assignment_date` DATE DEFAULT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `status` ENUM('Available','Assigned','Expired','Revoked') DEFAULT 'Available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_software` (`software_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ICT Support Tickets
CREATE TABLE IF NOT EXISTS `ict_support_tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(20) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `category` VARCHAR(100) DEFAULT 'General',
    `priority` ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    `reported_by` INT NOT NULL,
    `reported_by_name` VARCHAR(255) DEFAULT NULL,
    `assigned_to` INT DEFAULT NULL,
    `assigned_to_name` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Open','In Progress','Waiting','Resolved','Closed') DEFAULT 'Open',
    `resolution_notes` TEXT DEFAULT NULL,
    `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_assigned` DATETIME DEFAULT NULL,
    `date_resolved` DATETIME DEFAULT NULL,
    `date_closed` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_ticket_number` (`ticket_number`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    KEY `idx_reported_by` (`reported_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ICT Ticket Assignments
CREATE TABLE IF NOT EXISTS `ict_ticket_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `assigned_to` INT NOT NULL,
    `assigned_to_name` VARCHAR(255) DEFAULT NULL,
    `assigned_by` INT DEFAULT NULL,
    `assignment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT DEFAULT NULL,
    KEY `idx_ticket` (`ticket_id`),
    KEY `idx_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ICT Ticket Comments
CREATE TABLE IF NOT EXISTS `ict_ticket_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `commenter_id` INT NOT NULL,
    `commenter_name` VARCHAR(255) DEFAULT NULL,
    `comment` TEXT NOT NULL,
    `is_internal` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================================
-- DONE
-- ============================================================================
SELECT 'Migration complete: All missing tables created safely with IF NOT EXISTS' AS status;
