-- ============================================================
-- HR Module Complete Schema — ISNM ERP
-- Adds missing columns for Staff Records (next-of-kin, DOB, etc.)
-- Creates junction tables for HR integration with other modules.
-- Safe to run multiple times.
-- ============================================================

-- 1. ENRICH staff TABLE with HR fields
ALTER TABLE staff
  ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS gender ENUM('Male','Female','Other') NULL AFTER date_of_birth,
  ADD COLUMN IF NOT EXISTS marital_status ENUM('Single','Married','Divorced','Widowed') DEFAULT 'Single' AFTER gender,
  ADD COLUMN IF NOT EXISTS nationality VARCHAR(100) DEFAULT 'Ugandan' AFTER marital_status,
  ADD COLUMN IF NOT EXISTS religion VARCHAR(100) NULL AFTER nationality,
  ADD COLUMN IF NOT EXISTS nin VARCHAR(20) NULL AFTER religion,
  ADD COLUMN IF NOT EXISTS next_of_kin_name VARCHAR(150) NULL AFTER address,
  ADD COLUMN IF NOT EXISTS next_of_kin_phone VARCHAR(20) NULL AFTER next_of_kin_name,
  ADD COLUMN IF NOT EXISTS next_of_kin_relationship VARCHAR(50) NULL AFTER next_of_kin_phone,
  ADD COLUMN IF NOT EXISTS next_of_kin_address TEXT NULL AFTER next_of_kin_relationship,
  ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(150) NULL AFTER next_of_kin_address,
  ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(20) NULL AFTER emergency_contact_name,
  ADD COLUMN IF NOT EXISTS highest_qualification VARCHAR(200) NULL AFTER emergency_contact_phone,
  ADD COLUMN IF NOT EXISTS year_of_experience INT DEFAULT 0 AFTER highest_qualification,
  ADD COLUMN IF NOT EXISTS staff_category ENUM('teaching','non-teaching','clinical','administrative') DEFAULT 'non-teaching' AFTER year_of_experience,
  ADD COLUMN IF NOT EXISTS contract_end_date DATE NULL AFTER staff_category,
  ADD COLUMN IF NOT EXISTS resignation_date DATE NULL AFTER contract_end_date,
  ADD COLUMN IF NOT EXISTS resignation_reason TEXT NULL AFTER resignation_date;

-- 2. STAFF DOCUMENTS TABLE (CV, certificates, appointment letters)
CREATE TABLE IF NOT EXISTS staff_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  document_type VARCHAR(50) NOT NULL COMMENT 'CV, Certificate, Appointment Letter, Academic Document, ID, Other',
  document_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_size INT DEFAULT 0,
  mime_type VARCHAR(100) NULL,
  uploaded_by INT NULL,
  is_verified TINYINT(1) DEFAULT 0,
  verified_by INT NULL,
  verified_at DATETIME NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  INDEX idx_doc_staff (staff_id),
  INDEX idx_doc_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. WORK HISTORY TABLE (within institution)
CREATE TABLE IF NOT EXISTS staff_work_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  position VARCHAR(200) NOT NULL,
  department VARCHAR(200) NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  salary_grade VARCHAR(50) NULL,
  supervisor VARCHAR(200) NULL,
  reason_for_change VARCHAR(500) NULL COMMENT 'Promotion, transfer, demotion, new hire',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  INDEX idx_wh_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. INTERVIEW SCHEDULING
CREATE TABLE IF NOT EXISTS interview_schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vacancy_id INT NOT NULL,
  candidate_id INT NOT NULL,
  interview_date DATETIME NOT NULL,
  interview_type ENUM('in-person','virtual','phone') DEFAULT 'in-person',
  interview_location VARCHAR(255) NULL,
  interviewers TEXT NULL COMMENT 'Comma-separated staff IDs or names',
  interview_status ENUM('scheduled','completed','cancelled','rescheduled') DEFAULT 'scheduled',
  score DECIMAL(5,2) NULL,
  notes TEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vacancy_id) REFERENCES job_vacancies(id) ON DELETE CASCADE,
  INDEX idx_is_vacancy (vacancy_id),
  INDEX idx_is_date (interview_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. INTERVIEW SCORING
CREATE TABLE IF NOT EXISTS interview_scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  interview_id INT NOT NULL,
  criteria VARCHAR(200) NOT NULL,
  score DECIMAL(5,2) NOT NULL,
  max_score DECIMAL(5,2) DEFAULT 10,
  comments TEXT NULL,
  scored_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (interview_id) REFERENCES interview_schedules(id) ON DELETE CASCADE,
  INDEX idx_isc_interview (interview_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. APPOINTMENT LETTER GENERATION
CREATE TABLE IF NOT EXISTS appointment_letters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  letter_type ENUM('appointment','confirmation','promotion','transfer','suspension','termination','retirement') DEFAULT 'appointment',
  letter_number VARCHAR(50) UNIQUE,
  title VARCHAR(255) NOT NULL,
  content LONGTEXT NOT NULL,
  issue_date DATE NOT NULL,
  signed_by INT NULL,
  pdf_path VARCHAR(500) NULL,
  status ENUM('draft','issued','acknowledged') DEFAULT 'draft',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  INDEX idx_al_staff (staff_id),
  INDEX idx_al_type (letter_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. ONBOARDING CHECKLIST ITEMS (per-staff)
CREATE TABLE IF NOT EXISTS onboarding_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  checklist_item VARCHAR(255) NOT NULL,
  assigned_to INT NULL,
  due_date DATE NULL,
  status ENUM('pending','in_progress','completed','waived') DEFAULT 'pending',
  completed_at DATETIME NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  INDEX idx_ot_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. CPD / TRAINING NEEDS ASSESSMENT
CREATE TABLE IF NOT EXISTS training_needs_assessment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  skill_gap VARCHAR(500) NOT NULL,
  priority ENUM('high','medium','low') DEFAULT 'medium',
  suggested_training VARCHAR(500) NULL,
  department_priority INT DEFAULT 0,
  status ENUM('identified','approved','completed','cancelled') DEFAULT 'identified',
  reviewed_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  INDEX idx_tna_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. PROMOTION RECOMMENDATIONS
CREATE TABLE IF NOT EXISTS promotion_recommendations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  current_position VARCHAR(200) NOT NULL,
  recommended_position VARCHAR(200) NOT NULL,
  current_salary DECIMAL(12,2) NULL,
  recommended_salary DECIMAL(12,2) NULL,
  reason TEXT NOT NULL,
  recommendation_date DATE NOT NULL,
  recommended_by INT NULL,
  approved_by INT NULL,
  approval_status ENUM('pending','approved','rejected','deferred') DEFAULT 'pending',
  approval_date DATE NULL,
  effective_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  INDEX idx_pr_staff (staff_id),
  INDEX idx_pr_status (approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. WARNING LETTERS (verbal, written, final)
CREATE TABLE IF NOT EXISTS warning_letters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  warning_type ENUM('verbal','written','final') NOT NULL,
  warning_date DATE NOT NULL,
  issued_by INT NULL,
  reason TEXT NOT NULL,
  description TEXT NULL,
  action_required TEXT NULL,
  follow_up_date DATE NULL,
  status ENUM('issued','acknowledged','resolved','escalated') DEFAULT 'issued',
  resolution_date DATE NULL,
  pdf_path VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  INDEX idx_wl_staff (staff_id),
  INDEX idx_wl_type (warning_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. CONTRACT RENEWAL REMINDERS
CREATE TABLE IF NOT EXISTS contract_renewal_reminders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contract_id INT NOT NULL,
  reminder_date DATE NOT NULL,
  reminder_type ENUM('30_days','14_days','7_days','expired') NOT NULL,
  sent_to VARCHAR(500) NULL,
  status ENUM('sent','pending','acknowledged') DEFAULT 'pending',
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (contract_id) REFERENCES employment_contracts(id) ON DELETE CASCADE,
  INDEX idx_crr_contract (contract_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. EXIT INTERVIEW / SEPARATION
CREATE TABLE IF NOT EXISTS exit_interviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  resignation_id INT NULL,
  exit_date DATE NOT NULL,
  reason_category ENUM('career','relocation','family','health','retirement','conflict','other') NULL,
  reason_detail TEXT NULL,
  feedback TEXT NULL,
  assets_returned TINYINT(1) DEFAULT 0,
  clearance_status ENUM('pending','in_progress','completed') DEFAULT 'pending',
  conducted_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
  INDEX idx_ei_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. HR DASHBOARD KPI CACHE
CREATE TABLE IF NOT EXISTS hr_dashboard_cache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cache_key VARCHAR(100) UNIQUE NOT NULL,
  cache_value LONGTEXT NULL,
  expires_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cache_key (cache_key),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Add indexes for frequently queried HR columns
ALTER TABLE staff
  ADD INDEX IF NOT EXISTS idx_staff_nin (nin),
  ADD INDEX IF NOT EXISTS idx_staff_gender (gender),
  ADD INDEX IF NOT EXISTS idx_staff_category (staff_category),
  ADD INDEX IF NOT EXISTS idx_staff_contract_end (contract_end_date),
  ADD INDEX IF NOT EXISTS idx_staff_dob (date_of_birth);

ALTER TABLE leave_requests
  ADD INDEX IF NOT EXISTS idx_lr_dates (start_date, end_date);

ALTER TABLE staff_attendance
  ADD INDEX IF NOT EXISTS idx_sa_staff_date (staff_id, date);

ALTER TABLE employment_contracts
  ADD INDEX IF NOT EXISTS idx_ec_dates (start_date, end_date),
  ADD INDEX IF NOT EXISTS idx_ec_status (status);
