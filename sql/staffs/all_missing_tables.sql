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
