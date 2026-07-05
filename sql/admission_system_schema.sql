-- ================================================================
-- ISNM School Management System — Complete Database Schema
-- Target: MySQL/MariaDB 10.4+ | Engine: InnoDB | Charset: utf8mb4
-- ================================================================

-- ── 1. STAFF & AUTH ──

-- Staff roles
CREATE TABLE IF NOT EXISTS `staff_roles` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `role_name`   VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff accounts (igangaschoolofl_staffs_db.staff)
CREATE TABLE IF NOT EXISTS `staff` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`     VARCHAR(255) NOT NULL,
  `email`         VARCHAR(100) NOT NULL UNIQUE,
  `phone`         VARCHAR(20) DEFAULT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `role_id`       INT DEFAULT NULL,
  `position`      VARCHAR(100) DEFAULT NULL,
  `department`    VARCHAR(100) DEFAULT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `login_attempts` INT NOT NULL DEFAULT 0,
  `locked_until`  TIMESTAMP NULL DEFAULT NULL,
  `reset_token`   VARCHAR(255) DEFAULT NULL,
  `reset_expiry`  TIMESTAMP NULL DEFAULT NULL,
  `last_login`    TIMESTAMP NULL DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_staff_role` (`role_id`),
  INDEX `idx_staff_email` (`email`),
  FOREIGN KEY (`role_id`) REFERENCES `staff_roles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. STUDENTS ──

-- Main students table (igangaschoolofl_students_db.students)
CREATE TABLE IF NOT EXISTS `students` (
  `id`                      INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`              VARCHAR(50) DEFAULT NULL UNIQUE,
  `student_number`          VARCHAR(50) DEFAULT NULL UNIQUE,
  `registration_number`     VARCHAR(50) DEFAULT NULL UNIQUE,
  `index_number`            VARCHAR(50) DEFAULT NULL,
  `first_name`              VARCHAR(100) NOT NULL,
  `surname`                 VARCHAR(100) NOT NULL,
  `full_name`               VARCHAR(255) NOT NULL,
  `other_name`              VARCHAR(100) DEFAULT NULL,
  `gender`                  ENUM('Male','Female','Other') DEFAULT NULL,
  `date_of_birth`           DATE DEFAULT NULL,
  `email`                   VARCHAR(100) DEFAULT NULL,
  `phone`                   VARCHAR(20) DEFAULT NULL,
  `mobile_number`           VARCHAR(20) DEFAULT NULL,
  `nationality`             VARCHAR(100) DEFAULT 'Ugandan',
  `district`                VARCHAR(100) DEFAULT NULL,
  `address`                 TEXT DEFAULT NULL,
  `program`                 VARCHAR(255) DEFAULT NULL,
  `course`                  VARCHAR(100) DEFAULT NULL,
  `level`                   VARCHAR(50) DEFAULT NULL,
  `set_name`                VARCHAR(100) DEFAULT NULL COMMENT 'e.g. Set 25, Set 28',
  `intake_year`             YEAR DEFAULT NULL,
  `intake_period`           VARCHAR(50) DEFAULT NULL,
  `current_year`            INT DEFAULT 1,
  `current_semester`        VARCHAR(20) DEFAULT NULL,
  `student_category`        VARCHAR(100) DEFAULT NULL,
  `status`                  ENUM('Active','Inactive','Graduated','Suspended','Withdrawn','deleted') NOT NULL DEFAULT 'Active',
  `password`                VARCHAR(255) DEFAULT NULL,
  `is_first_login`          TINYINT(1) NOT NULL DEFAULT 1,
  `password_changed`        TINYINT(1) NOT NULL DEFAULT 0,
  `login_attempts`          INT NOT NULL DEFAULT 0,
  `locked_until`            TIMESTAMP NULL DEFAULT NULL,
  `last_login`              TIMESTAMP NULL DEFAULT NULL,
  `profile_picture`         VARCHAR(500) DEFAULT NULL,
  `passport_photo`          VARCHAR(500) DEFAULT NULL,
  `guardian_name`           VARCHAR(200) DEFAULT NULL,
  `guardian_phone`          VARCHAR(20) DEFAULT NULL,
  `guardian_email`          VARCHAR(100) DEFAULT NULL,
  `emergency_contact_name`  VARCHAR(100) DEFAULT NULL,
  `emergency_contact_phone` VARCHAR(20) DEFAULT NULL,
  `emergency_contact_email` VARCHAR(100) DEFAULT NULL,
  `created_at`              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_stu_name` (`full_name`),
  INDEX `idx_stu_program` (`program`),
  INDEX `idx_stu_set` (`set_name`),
  INDEX `idx_stu_status` (`status`),
  INDEX `idx_stu_phone` (`phone`),
  INDEX `idx_stu_email` (`email`),
  INDEX `idx_stu_index` (`index_number`),
  INDEX `idx_stu_student_number` (`student_number`),
  INDEX `idx_stu_reg_number` (`registration_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student academic profiles (year/semester tracking)
CREATE TABLE IF NOT EXISTS `student_academic_profiles` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `student_number`  VARCHAR(50) NOT NULL,
  `full_name`       VARCHAR(255) NOT NULL,
  `program`         VARCHAR(255) DEFAULT NULL,
  `academic_year`   YEAR DEFAULT NULL,
  `semester`        VARCHAR(20) DEFAULT NULL,
  `status`          ENUM('Active','Completed','Dropped','Transferred') NOT NULL DEFAULT 'Active',
  `gpa`             DECIMAL(4,2) DEFAULT NULL,
  `remarks`         TEXT DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sap_student` (`student_number`),
  INDEX `idx_sap_year` (`academic_year`),
  INDEX `idx_sap_program` (`program`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Semester GPA records
CREATE TABLE IF NOT EXISTS `student_semester_gpa` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`        INT NOT NULL,
  `academic_year`     VARCHAR(20) DEFAULT NULL,
  `semester`          VARCHAR(20) DEFAULT NULL,
  `total_credits`     DECIMAL(6,2) DEFAULT 0,
  `earned_credits`    DECIMAL(6,2) DEFAULT 0,
  `semester_gpa`      DECIMAL(4,2) DEFAULT 0,
  `cumulative_gpa`    DECIMAL(4,2) DEFAULT 0,
  `academic_standing` VARCHAR(50) DEFAULT NULL,
  `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sg_student` (`student_id`),
  INDEX `idx_sg_year_sem` (`academic_year`,`semester`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Academic course records per student
CREATE TABLE IF NOT EXISTS `student_academic_records` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`    INT NOT NULL,
  `academic_year` VARCHAR(20) DEFAULT NULL,
  `semester`      VARCHAR(20) DEFAULT NULL,
  `subject`       VARCHAR(255) DEFAULT NULL,
  `course_code`   VARCHAR(50) DEFAULT NULL,
  `marks`         DECIMAL(5,2) DEFAULT NULL,
  `grade`         VARCHAR(5) DEFAULT NULL,
  `credit_hours`  DECIMAL(4,1) DEFAULT 0,
  `gpa`           DECIMAL(4,2) DEFAULT NULL,
  `remarks`       TEXT DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ar_student` (`student_id`),
  INDEX `idx_ar_course` (`course_code`),
  INDEX `idx_ar_year_sem` (`academic_year`,`semester`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student extended profiles
CREATE TABLE IF NOT EXISTS `student_profiles` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`      INT NOT NULL UNIQUE,
  `bio`             TEXT DEFAULT NULL,
  `skills`          TEXT DEFAULT NULL,
  `medical_info`    TEXT DEFAULT NULL,
  `next_of_kin`     VARCHAR(255) DEFAULT NULL,
  `next_of_kin_phone` VARCHAR(20) DEFAULT NULL,
  `sponsor_name`    VARCHAR(255) DEFAULT NULL,
  `sponsor_phone`   VARCHAR(20) DEFAULT NULL,
  `sponsor_email`   VARCHAR(100) DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. ACADEMICS ──

-- Course catalog
CREATE TABLE IF NOT EXISTS `course_catalog` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `course_code`   VARCHAR(20) NOT NULL UNIQUE,
  `course_name`   VARCHAR(255) NOT NULL,
  `credit_hours`  DECIMAL(4,1) DEFAULT 0,
  `is_compulsory` TINYINT(1) NOT NULL DEFAULT 0,
  `department`    VARCHAR(100) DEFAULT NULL,
  `level`         VARCHAR(50) DEFAULT NULL,
  `status`        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cc_level` (`level`),
  INDEX `idx_cc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student course registrations
CREATE TABLE IF NOT EXISTS `student_course_registrations` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`    INT NOT NULL,
  `course_id`     INT DEFAULT NULL,
  `course_code`   VARCHAR(20) DEFAULT NULL,
  `course_name`   VARCHAR(255) DEFAULT NULL,
  `academic_year` VARCHAR(20) DEFAULT NULL,
  `semester`      VARCHAR(20) DEFAULT NULL,
  `status`        ENUM('Registered','Dropped','Completed','Incomplete') NOT NULL DEFAULT 'Registered',
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_scr_student` (`student_id`),
  INDEX `idx_scr_course` (`course_code`),
  INDEX `idx_scr_year_sem` (`academic_year`,`semester`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `course_catalog`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student class attendance
CREATE TABLE IF NOT EXISTS `student_attendance` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`      INT NOT NULL,
  `date`            DATE DEFAULT NULL,
  `attendance_date` DATE DEFAULT NULL,
  `time_in`         TIME DEFAULT NULL,
  `time_out`        TIME DEFAULT NULL,
  `status`          ENUM('Present','Absent','Late','Excused','Holiday') NOT NULL DEFAULT 'Present',
  `subject`         VARCHAR(255) DEFAULT NULL,
  `lecturer`        VARCHAR(255) DEFAULT NULL,
  `remarks`         TEXT DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_att_student` (`student_id`),
  INDEX `idx_att_date` (`date`),
  INDEX `idx_att_status` (`status`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. ADMISSIONS ──

-- Academic programs offered
CREATE TABLE IF NOT EXISTS `academic_programs` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `program_code`    VARCHAR(20) NOT NULL UNIQUE,
  `program_name`    VARCHAR(255) NOT NULL,
  `program_type`    ENUM('Certificate','Diploma','Degree','Short Course') NOT NULL DEFAULT 'Diploma',
  `department`      VARCHAR(100) DEFAULT NULL,
  `duration_years`  DECIMAL(3,1) NOT NULL DEFAULT 2.0,
  `total_fee`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `status`          ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_prog_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Intake periods
CREATE TABLE IF NOT EXISTS `intakes` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `intake_name`         VARCHAR(100) NOT NULL,
  `intake_month`        VARCHAR(20) NOT NULL,
  `intake_year`         YEAR NOT NULL,
  `application_start`   DATE DEFAULT NULL,
  `application_deadline` DATE DEFAULT NULL,
  `status`              ENUM('Open','Closed','Upcoming') NOT NULL DEFAULT 'Upcoming',
  `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_intake` (`intake_month`,`intake_year`),
  INDEX `idx_intake_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Applicant (prospective student) records (igangaschoolofl_staffs_db.applicants)
CREATE TABLE IF NOT EXISTS `applicants` (
  `id`                      INT AUTO_INCREMENT PRIMARY KEY,
  `application_number`      VARCHAR(30) NOT NULL UNIQUE,
  `student_number`          VARCHAR(50) DEFAULT NULL UNIQUE,
  `registration_number`     VARCHAR(50) DEFAULT NULL,
  `portal_username`         VARCHAR(100) DEFAULT NULL,
  `portal_password_hash`    VARCHAR(255) DEFAULT NULL,
  `full_name`               VARCHAR(255) NOT NULL,
  `first_name`              VARCHAR(100) DEFAULT NULL,
  `middle_name`             VARCHAR(100) DEFAULT NULL,
  `surname`                 VARCHAR(100) DEFAULT NULL,
  `gender`                  ENUM('Male','Female','Other') DEFAULT NULL,
  `date_of_birth`           DATE DEFAULT NULL,
  `email`                   VARCHAR(100) DEFAULT NULL,
  `phone`                   VARCHAR(20) DEFAULT NULL,
  `alternative_phone`       VARCHAR(20) DEFAULT NULL,
  `nationality`             VARCHAR(100) DEFAULT 'Ugandan',
  `district`                VARCHAR(100) DEFAULT NULL,
  `county`                  VARCHAR(100) DEFAULT NULL,
  `religion`                VARCHAR(50) DEFAULT NULL,
  `marital_status`          ENUM('Single','Married','Divorced','Widowed') DEFAULT 'Single',
  `address`                 TEXT DEFAULT NULL,
  `photo_path`              VARCHAR(500) DEFAULT NULL,
  `program_id`              INT DEFAULT NULL,
  `intake`                  VARCHAR(50) DEFAULT NULL,
  `intake_id`               INT DEFAULT NULL,
  `application_source`      ENUM('Online','Manual','Walk-in','Referral','Other') DEFAULT 'Online',
  `status`                  ENUM('New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered','Withdrawn') NOT NULL DEFAULT 'New',
  `rejection_reason`        TEXT DEFAULT NULL,
  `previous_education`      TEXT DEFAULT NULL,
  `previous_institution`    VARCHAR(255) DEFAULT NULL,
  `previous_qualification`  VARCHAR(255) DEFAULT NULL,
  `last_attended_school`    VARCHAR(255) DEFAULT NULL,
  `guardian_name`           VARCHAR(200) DEFAULT NULL,
  `guardian_phone`          VARCHAR(20) DEFAULT NULL,
  `guardian_email`          VARCHAR(100) DEFAULT NULL,
  `guardian_relationship`   VARCHAR(50) DEFAULT NULL,
  `emergency_contact_name`  VARCHAR(100) DEFAULT NULL,
  `emergency_contact_phone` VARCHAR(20) DEFAULT NULL,
  `submitted_at`            TIMESTAMP NULL DEFAULT NULL,
  `reviewed_by`             INT DEFAULT NULL,
  `reviewed_at`             TIMESTAMP NULL DEFAULT NULL,
  `approved_by`             INT DEFAULT NULL,
  `approved_at`             TIMESTAMP NULL DEFAULT NULL,
  `registered_at`           TIMESTAMP NULL DEFAULT NULL,
  `created_at`              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_app_status` (`status`),
  INDEX `idx_app_program` (`program_id`),
  INDEX `idx_app_intake` (`intake`),
  INDEX `idx_app_name` (`full_name`),
  INDEX `idx_app_phone` (`phone`),
  INDEX `idx_app_email` (`email`),
  INDEX `idx_app_created` (`created_at`),
  FOREIGN KEY (`program_id`) REFERENCES `academic_programs`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`intake_id`) REFERENCES `intakes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. ADMISSION REQUIREMENTS ──

-- Master list of admission requirements
CREATE TABLE IF NOT EXISTS `admission_requirements` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `requirement_name` VARCHAR(255) NOT NULL,
  `type`            ENUM('Document','Certificate','ID','Photo','Form','Other') NOT NULL DEFAULT 'Document',
  `display_order`   INT NOT NULL DEFAULT 0,
  `is_mandatory`    TINYINT(1) NOT NULL DEFAULT 1,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_req_active` (`is_active`),
  INDEX `idx_req_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Per-applicant requirement status tracking
CREATE TABLE IF NOT EXISTS `applicant_requirement_status` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id`    INT NOT NULL,
  `requirement_id`  INT NOT NULL,
  `status`          ENUM('Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received','Not Yet Given') NOT NULL DEFAULT 'Not Submitted',
  `remarks`         TEXT DEFAULT NULL COMMENT 'System/admin remarks',
  `director_notes`  TEXT DEFAULT NULL COMMENT 'Admission Director private notes',
  `submitted_by`    INT DEFAULT NULL,
  `submitted_at`    TIMESTAMP NULL DEFAULT NULL,
  `verified_by`     INT DEFAULT NULL,
  `verified_at`     TIMESTAMP NULL DEFAULT NULL,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_app_req` (`applicant_id`,`requirement_id`),
  INDEX `idx_ars_status` (`status`),
  FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student uploaded documents linked to requirements
CREATE TABLE IF NOT EXISTS `student_documents` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id`        INT NOT NULL,
  `requirement_id`      INT DEFAULT NULL,
  `document_name`       VARCHAR(255) NOT NULL,
  `document_type`       VARCHAR(100) DEFAULT NULL,
  `file_path`           VARCHAR(500) NOT NULL,
  `file_size`           INT DEFAULT NULL,
  `file_mime`           VARCHAR(100) DEFAULT NULL,
  `verification_status` ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `verification_remarks` TEXT DEFAULT NULL,
  `verified_by`         INT DEFAULT NULL,
  `verified_at`         TIMESTAMP NULL DEFAULT NULL,
  `document_status`     ENUM('Active','Deleted') NOT NULL DEFAULT 'Active',
  `uploaded_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_doc_app` (`applicant_id`),
  INDEX `idx_doc_ver` (`verification_status`),
  FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Requirement history audit log
CREATE TABLE IF NOT EXISTS `requirement_history` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id`    INT NOT NULL,
  `requirement_id`  INT DEFAULT NULL,
  `action`          VARCHAR(100) NOT NULL,
  `previous_status` VARCHAR(50) DEFAULT NULL,
  `new_status`      VARCHAR(50) DEFAULT NULL,
  `performed_by`    INT DEFAULT NULL,
  `remarks`         TEXT DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_rh_app` (`applicant_id`),
  INDEX `idx_rh_action` (`action`),
  FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 6. ADMISSION WORKFLOW ──

-- Admission decisions
CREATE TABLE IF NOT EXISTS `admission_decisions` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id`      INT NOT NULL,
  `decision`          ENUM('Approved','Rejected','Deferred','Waitlisted') NOT NULL,
  `decision_reason`   TEXT DEFAULT NULL,
  `decided_by`        INT DEFAULT NULL,
  `decided_at`        TIMESTAMP NULL DEFAULT NULL,
  `notified_applicant` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_dec_app` (`applicant_id`),
  FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Interview scheduling
CREATE TABLE IF NOT EXISTS `admission_interviews` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id`    INT NOT NULL,
  `interviewer_id`  INT DEFAULT NULL,
  `interview_date`  DATETIME NOT NULL,
  `interview_mode`  ENUM('In-Person','Online','Phone') NOT NULL DEFAULT 'In-Person',
  `interview_link`  VARCHAR(500) DEFAULT NULL,
  `interview_score` DECIMAL(5,2) DEFAULT NULL,
  `interview_outcome` ENUM('Pass','Fail','Pending','Reschedule') DEFAULT 'Pending',
  `notes`           TEXT DEFAULT NULL,
  `recommendation`  TEXT DEFAULT NULL,
  `created_by`      INT DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_int_app` (`applicant_id`),
  INDEX `idx_int_date` (`interview_date`),
  FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admission tracking summary per applicant
CREATE TABLE IF NOT EXISTS `student_admission_tracking` (
  `id`                   INT AUTO_INCREMENT PRIMARY KEY,
  `student_number`       VARCHAR(50) DEFAULT NULL,
  `application_number`   VARCHAR(30) NOT NULL,
  `applicant_id`         INT DEFAULT NULL,
  `program`              VARCHAR(255) DEFAULT NULL,
  `intake`               VARCHAR(50) DEFAULT NULL,
  `admission_date`       DATE DEFAULT NULL,
  `admission_status`     ENUM('Pending','Under Review','Requirements Pending','Approved','Rejected','Registered') NOT NULL DEFAULT 'Pending',
  `requirements_total`   INT NOT NULL DEFAULT 0,
  `requirements_completed` INT NOT NULL DEFAULT 0,
  `documents_uploaded`   INT NOT NULL DEFAULT 0,
  `interview_scheduled`  TINYINT(1) NOT NULL DEFAULT 0,
  `interview_date`       DATETIME DEFAULT NULL,
  `interview_notes`      TEXT DEFAULT NULL,
  `communication_count`  INT NOT NULL DEFAULT 0,
  `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_track_app` (`application_number`),
  INDEX `idx_track_status` (`admission_status`),
  INDEX `idx_track_student` (`student_number`),
  FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 7. STUDENT SERVICES ──

-- Student requests (leave, deferral, transfer, withdrawal, transcript)
CREATE TABLE IF NOT EXISTS `student_requests` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`    INT NOT NULL,
  `request_type`  ENUM('Leave of Absence','Deferral','Transfer','Withdrawal','Transcript','Other') NOT NULL DEFAULT 'Other',
  `reason`        TEXT NOT NULL,
  `status`        ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `admin_response` TEXT DEFAULT NULL,
  `responded_by`  INT DEFAULT NULL,
  `responded_at`  TIMESTAMP NULL DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sr_student` (`student_id`),
  INDEX `idx_sr_status` (`status`),
  INDEX `idx_sr_type` (`request_type`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student notifications / announcements
CREATE TABLE IF NOT EXISTS `student_notifications` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`  INT DEFAULT NULL COMMENT 'NULL = broadcast to all',
  `title`       VARCHAR(255) NOT NULL,
  `message`     TEXT DEFAULT NULL,
  `type`        ENUM('info','success','warning','danger','announcement') NOT NULL DEFAULT 'info',
  `priority`    ENUM('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
  `link`        VARCHAR(500) DEFAULT NULL,
  `created_by`  INT DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sn_student` (`student_id`),
  INDEX `idx_sn_read` (`is_read`),
  INDEX `idx_sn_type` (`type`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student messages (inbox)
CREATE TABLE IF NOT EXISTS `student_messages` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`  INT NOT NULL,
  `sender`      VARCHAR(255) DEFAULT 'System',
  `subject`     VARCHAR(255) DEFAULT NULL,
  `message`     TEXT NOT NULL,
  `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sm_student` (`student_id`),
  INDEX `idx_sm_read` (`is_read`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 8. FINANCE ──

-- Student fee tracking
CREATE TABLE IF NOT EXISTS `student_fee_tracking` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`      INT NOT NULL,
  `fee_type`        VARCHAR(100) DEFAULT NULL,
  `amount`          DECIMAL(14,2) NOT NULL DEFAULT 0,
  `paid`            DECIMAL(14,2) NOT NULL DEFAULT 0,
  `balance`         DECIMAL(14,2) NOT NULL DEFAULT 0,
  `due_date`        DATE DEFAULT NULL,
  `status`          ENUM('Pending','Paid','Partial','Overdue','Waived') NOT NULL DEFAULT 'Pending',
  `academic_year`   VARCHAR(20) DEFAULT NULL,
  `semester`        VARCHAR(20) DEFAULT NULL,
  `remarks`         TEXT DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ft_student` (`student_id`),
  INDEX `idx_ft_status` (`status`),
  INDEX `idx_ft_year` (`academic_year`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payment transactions
CREATE TABLE IF NOT EXISTS `student_payment_transactions` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`      INT NOT NULL,
  `fee_id`          INT DEFAULT NULL,
  `amount`          DECIMAL(14,2) NOT NULL,
  `payment_method`  ENUM('Cash','Bank Transfer','Mobile Money','Cheque','Other') NOT NULL DEFAULT 'Cash',
  `transaction_ref` VARCHAR(100) DEFAULT NULL,
  `paid_by`         VARCHAR(255) DEFAULT NULL,
  `receipt_number`  VARCHAR(50) DEFAULT NULL,
  `payment_date`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `remarks`         TEXT DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pt_student` (`student_id`),
  INDEX `idx_pt_method` (`payment_method`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`fee_id`) REFERENCES `student_fee_tracking`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 9. CLINICAL ──

-- Clinical placements / rotations
CREATE TABLE IF NOT EXISTS `student_clinical_placements` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`          INT NOT NULL,
  `facility_name`       VARCHAR(255) NOT NULL,
  `facility_location`   VARCHAR(255) DEFAULT NULL,
  `department`          VARCHAR(100) DEFAULT NULL,
  `start_date`          DATE DEFAULT NULL,
  `end_date`            DATE DEFAULT NULL,
  `supervisor_name`     VARCHAR(255) DEFAULT NULL,
  `supervisor_phone`    VARCHAR(20) DEFAULT NULL,
  `supervisor_email`    VARCHAR(100) DEFAULT NULL,
  `supervisor_evaluation` TEXT DEFAULT NULL,
  `status`              ENUM('Active','Completed','Upcoming','Cancelled') NOT NULL DEFAULT 'Active',
  `hours_completed`     INT DEFAULT 0,
  `hours_required`      INT DEFAULT 0,
  `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cp_student` (`student_id`),
  INDEX `idx_cp_status` (`status`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clinical logbook entries
CREATE TABLE IF NOT EXISTS `student_clinical_logbook_entries` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`      INT NOT NULL,
  `date`            DATE DEFAULT NULL,
  `procedure_name`  VARCHAR(255) NOT NULL,
  `description`     TEXT DEFAULT NULL,
  `supervisor_name` VARCHAR(255) DEFAULT NULL,
  `supervisor_comment` TEXT DEFAULT NULL,
  `verification_status` ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `verified_by`     INT DEFAULT NULL,
  `verified_at`     TIMESTAMP NULL DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cle_student` (`student_id`),
  INDEX `idx_cle_status` (`verification_status`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Skills competency tracking
CREATE TABLE IF NOT EXISTS `student_competencies` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`      INT NOT NULL,
  `skill_name`      VARCHAR(255) NOT NULL,
  `skill_category`  VARCHAR(100) DEFAULT NULL,
  `proficiency`     ENUM('Not Attempted','Beginner','Intermediate','Competent','Expert') NOT NULL DEFAULT 'Not Attempted',
  `date_assessed`   DATE DEFAULT NULL,
  `assessed_by`     VARCHAR(255) DEFAULT NULL,
  `notes`           TEXT DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sc_student` (`student_id`),
  INDEX `idx_sc_category` (`skill_category`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 10. STUDENT LIFE ──

-- Disciplinary warnings
CREATE TABLE IF NOT EXISTS `student_warnings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`    INT NOT NULL,
  `warning_type`  VARCHAR(100) DEFAULT NULL,
  `severity`      ENUM('Verbal','Written','Final','Suspension') NOT NULL DEFAULT 'Written',
  `reason`        TEXT NOT NULL,
  `issued_by`     VARCHAR(255) DEFAULT NULL,
  `issued_date`   DATE DEFAULT NULL,
  `warning_date`  DATE DEFAULT NULL,
  `status`        ENUM('Active','Resolved','Expired') NOT NULL DEFAULT 'Active',
  `resolution`    TEXT DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sw_student` (`student_id`),
  INDEX `idx_sw_status` (`status`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hostel allocation
CREATE TABLE IF NOT EXISTS `student_hostel_allocations` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`      INT NOT NULL UNIQUE,
  `hostel_name`     VARCHAR(100) DEFAULT NULL,
  `room_number`     VARCHAR(20) DEFAULT NULL,
  `bed_number`      VARCHAR(20) DEFAULT NULL,
  `check_in_date`   DATE DEFAULT NULL,
  `check_out_date`  DATE DEFAULT NULL,
  `status`          ENUM('Active','Checked Out','Reserved') NOT NULL DEFAULT 'Active',
  `fee_per_semester` DECIMAL(14,2) DEFAULT 0,
  `remarks`         TEXT DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ha_status` (`status`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Library borrowing
CREATE TABLE IF NOT EXISTS `student_library_borrowing` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`      INT NOT NULL,
  `book_title`      VARCHAR(255) NOT NULL,
  `book_author`     VARCHAR(255) DEFAULT NULL,
  `isbn`            VARCHAR(50) DEFAULT NULL,
  `borrow_date`     DATE DEFAULT NULL,
  `due_date`        DATE DEFAULT NULL,
  `return_date`     DATE DEFAULT NULL,
  `fine_amount`     DECIMAL(10,2) DEFAULT 0,
  `fine_paid`       TINYINT(1) NOT NULL DEFAULT 0,
  `status`          ENUM('Borrowed','Returned','Overdue','Lost') NOT NULL DEFAULT 'Borrowed',
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_lb_student` (`student_id`),
  INDEX `idx_lb_status` (`status`),
  INDEX `idx_lb_due` (`due_date`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student timetables
CREATE TABLE IF NOT EXISTS `student_timetables` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `student_id`  INT NOT NULL,
  `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time`  TIME NOT NULL,
  `end_time`    TIME NOT NULL,
  `subject`     VARCHAR(255) NOT NULL,
  `lecturer`    VARCHAR(255) DEFAULT NULL,
  `room`        VARCHAR(100) DEFAULT NULL,
  `semester`    VARCHAR(20) DEFAULT NULL,
  `academic_year` VARCHAR(20) DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tt_student` (`student_id`),
  INDEX `idx_tt_day` (`day_of_week`),
  INDEX `idx_tt_time` (`start_time`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 11. COMMUNICATIONS ──

-- Admission communication log
CREATE TABLE IF NOT EXISTS `admission_communications` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id`      INT NOT NULL,
  `sender_id`         INT DEFAULT NULL,
  `communication_type` ENUM('Email','SMS','Portal','WhatsApp','Internal Note') NOT NULL DEFAULT 'Portal',
  `subject`           VARCHAR(255) DEFAULT NULL,
  `message`           TEXT NOT NULL,
  `status`            ENUM('Sent','Delivered','Read','Failed') DEFAULT 'Sent',
  `sent_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_com_app` (`applicant_id`),
  INDEX `idx_com_type` (`communication_type`),
  FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admission notifications
CREATE TABLE IF NOT EXISTS `admission_notifications` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT DEFAULT NULL,
  `user_id`     INT DEFAULT NULL,
  `type`        ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `title`       VARCHAR(255) NOT NULL,
  `message`     TEXT DEFAULT NULL,
  `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
  `link`        VARCHAR(500) DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_n_app` (`applicant_id`),
  INDEX `idx_n_user` (`user_id`),
  INDEX `idx_n_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 12. AUDIT ──

-- Activity audit log
CREATE TABLE IF NOT EXISTS `admission_activity_logs` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id`  INT DEFAULT NULL,
  `user_id`       INT DEFAULT NULL,
  `action`        VARCHAR(100) NOT NULL,
  `description`   TEXT DEFAULT NULL,
  `ip_address`    VARCHAR(45) DEFAULT NULL,
  `user_agent`    TEXT DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_log_app` (`applicant_id`),
  INDEX `idx_log_user` (`user_id`),
  INDEX `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 13. INDEXES SUMMARY ──
-- (All indexes are defined inline above. This section is for reference.)

-- Key performance indexes:
--   applicants:  (status), (program_id), (intake), (full_name), (phone), (email), (created_at)
--   students:    (full_name), (program), (set_name), (status), (phone), (email), (index_number),
--                (student_number), (registration_number)
--   applicant_requirement_status: (applicant_id,requirement_id) UNIQUE, (status)
--   student_admission_tracking:   (application_number) UNIQUE, (status), (student_number)
--   student_fee_tracking:         (student_id), (status), (academic_year)
--   student_attendance:           (student_id), (date), (status)
--   student_academic_records:     (student_id), (course_code), (academic_year,semester)
--   student_requests:             (student_id), (status), (request_type)
--   admission_activity_logs:      (applicant_id), (user_id), (created_at)

-- ================================================================
-- END OF SCHEMA
-- ================================================================
