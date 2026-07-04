-- ============================================================================
-- ISNM Missing Responsive System SQL Tables
-- These tables support: News Publishing, Form Routing, Notifications, etc.
-- ============================================================================

-- ============================================================================
-- STUDENTS DATABASE TABLES
-- ============================================================================

-- TABLE: form_submissions
-- PURPOSE: Unified table for all form submissions (applications, contacts, etc.)
-- DATABASE: igangaschoolofl_students_db
CREATE TABLE IF NOT EXISTS `form_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `form_type` VARCHAR(50) NOT NULL COMMENT 'application, contact, feedback, complaint, volunteer',
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `subject` VARCHAR(255),
  `message` LONGTEXT,
  `status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, read, responded, closed',
  `assigned_to` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_type (form_type),
  INDEX idx_email (email),
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: feedback_submissions
-- PURPOSE: User feedback collection
-- DATABASE: igangaschoolofl_students_db
CREATE TABLE IF NOT EXISTS `feedback_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `rating` INT,
  `subject` VARCHAR(255),
  `feedback` LONGTEXT NOT NULL,
  `category` VARCHAR(100),
  `status` VARCHAR(50) DEFAULT 'received',
  `reviewed_by` INT,
  `reviewed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_email (email),
  INDEX idx_rating (rating),
  INDEX idx_category (category),
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: complaint_submissions
-- PURPOSE: User complaints and grievances
-- DATABASE: igangaschoolofl_students_db
CREATE TABLE IF NOT EXISTS `complaint_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complainant_name` VARCHAR(255) NOT NULL,
  `complainant_email` VARCHAR(255) NOT NULL,
  `complainant_phone` VARCHAR(20),
  `subject` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `department` VARCHAR(100),
  `severity` VARCHAR(50) DEFAULT 'medium' COMMENT 'low, medium, high, urgent',
  `status` VARCHAR(50) DEFAULT 'filed' COMMENT 'filed, acknowledged, investigating, resolved, closed',
  `assigned_to` INT,
  `resolution` LONGTEXT,
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_email (complainant_email),
  INDEX idx_status (status),
  INDEX idx_severity (severity),
  INDEX idx_department (department),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: volunteer_applications
-- PURPOSE: Volunteer application management
-- DATABASE: igangaschoolofl_students_db
CREATE TABLE IF NOT EXISTS `volunteer_applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `surname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `skills` LONGTEXT,
  `availability` LONGTEXT,
  `motivation` LONGTEXT,
  `experience` LONGTEXT,
  `status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, reviewed, accepted, rejected, interviewed',
  `reviewed_by` INT,
  `review_date` TIMESTAMP NULL,
  `decision` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_email (email),
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- STAFF DATABASE TABLES
-- ============================================================================

-- TABLE: notifications (if not exists)
-- PURPOSE: System notifications for staff members
-- DATABASE: igangaschoolofl_staffs_db
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL COMMENT 'application, contact, feedback, complaint, system',
  `title` VARCHAR(255) NOT NULL,
  `message` LONGTEXT NOT NULL,
  `related_id` INT,
  `from_email` VARCHAR(255),
  `is_read` BOOLEAN DEFAULT FALSE,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_staff_unread (staff_id, is_read),
  INDEX idx_created (created_at),
  INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- WEBSITE DATABASE TABLES
-- ============================================================================

-- TABLE: website_announcements
-- PURPOSE: News and announcements published on website by directors
-- DATABASE: igangaschoolofl_website_db
CREATE TABLE IF NOT EXISTS `website_announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `category` VARCHAR(100) COMMENT 'General, Academic, Administrative, Event, etc.',
  `author` VARCHAR(255) COMMENT 'Director or staff name',
  `image_url` VARCHAR(500),
  `featured` BOOLEAN DEFAULT FALSE COMMENT 'Show on homepage',
  `status` VARCHAR(50) DEFAULT 'published' COMMENT 'draft, published, archived',
  `views` INT DEFAULT 0,
  `published_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FULLTEXT INDEX idx_search (title, content),
  INDEX idx_status (status),
  INDEX idx_featured (featured),
  INDEX idx_category (category),
  INDEX idx_published (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VERIFICATION SCRIPT OUTPUT
-- ============================================================================

-- The following tables already exist in your databases and do NOT need recreation:

-- ✓ igangaschoolofl_students_db.applications (already exists)
-- ✓ igangaschoolofl_students_db.contact_submissions (already exists)
-- ✓ igangaschoolofl_students_db.notifications (already exists - will CREATE IF NOT EXISTS)
-- ✓ igangaschoolofl_staffs_db.notifications (already exists - will CREATE IF NOT EXISTS)
-- ✓ igangaschoolofl_staffs_db.student_fee_accounts (already exists)
-- ✓ igangaschoolofl_website_db.contact_submissions (already exists)

-- The following NEW tables are CREATED by this script:
-- ✓ igangaschoolofl_students_db.form_submissions (NEW)
-- ✓ igangaschoolofl_students_db.feedback_submissions (NEW)
-- ✓ igangaschoolofl_students_db.complaint_submissions (NEW)
-- ✓ igangaschoolofl_students_db.volunteer_applications (NEW)
-- ✓ igangaschoolofl_website_db.website_announcements (NEW)

-- ============================================================================
-- END OF SQL SCRIPT
-- ============================================================================
