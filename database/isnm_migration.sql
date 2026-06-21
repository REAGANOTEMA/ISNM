-- ============================================================
-- ISNM Database Migration - Performance Indexes + Missing Tables
-- SAFE to run on existing databases (uses IF NOT EXISTS).
-- Run via phpMyAdmin or: mysql -u root -p < database/isnm_migration.sql
-- ============================================================

-- ============================================================
-- PART 1: Financial Clearance Table
-- ============================================================

-- staffs_db
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.financial_clearance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) DEFAULT 'Annual',
    clearance_status ENUM('Cleared','Not Cleared','Pending Review') DEFAULT 'Pending Review',
    cleared_by INT DEFAULT NULL,
    cleared_at TIMESTAMP NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clearance (student_id, academic_year, semester),
    INDEX idx_fc_student (student_id),
    INDEX idx_fc_status (clearance_status),
    INDEX idx_fc_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- students_db
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.financial_clearance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) DEFAULT 'Annual',
    clearance_status ENUM('Cleared','Not Cleared','Pending Review') DEFAULT 'Pending Review',
    cleared_by INT DEFAULT NULL,
    cleared_at TIMESTAMP NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clearance (student_id, academic_year, semester),
    INDEX idx_fc_student (student_id),
    INDEX idx_fc_status (clearance_status),
    INDEX idx_fc_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PART 2: Late Payment / Penalty Settings Table
-- ============================================================

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.late_payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO igangaschoolofl_staffs_db.late_payment_settings (setting_key, setting_value, description) VALUES
('grace_period_days', '15', 'Days after due date before late fee applies'),
('late_fee_percentage', '5', 'Percentage penalty on outstanding amount'),
('late_fee_fixed', '20000', 'Fixed late fee amount (UGX)'),
('max_late_fee', '100000', 'Maximum late fee cap (UGX)');

CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.late_payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO igangaschoolofl_students_db.late_payment_settings (setting_key, setting_value, description) VALUES
('grace_period_days', '15', 'Days after due date before late fee applies'),
('late_fee_percentage', '5', 'Percentage penalty on outstanding amount'),
('late_fee_fixed', '20000', 'Fixed late fee amount (UGX)'),
('max_late_fee', '100000', 'Maximum late fee cap (UGX)');

-- ============================================================
-- PART 3: Payment Approval Workflow Table
-- ============================================================

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.payment_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    payment_type VARCHAR(50) NOT NULL DEFAULT 'fee_payment',
    requested_by INT NOT NULL,
    approved_by INT DEFAULT NULL,
    approval_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    approval_remarks TEXT DEFAULT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    INDEX idx_pa_payment (payment_id),
    INDEX idx_pa_status (approval_status),
    INDEX idx_pa_requested (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PART 4: Phantom Tables - fee_payments and student_fee_accounts
-- These tables exist only in code, not in SQL schema files.
-- ============================================================

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.fee_payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    fee_account_id INT DEFAULT NULL,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_reference VARCHAR(100) DEFAULT NULL,
    receipt_number VARCHAR(50) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    payment_date DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fp_student (student_id),
    INDEX idx_fp_date (payment_date),
    INDEX idx_fp_status (status),
    INDEX idx_fp_date_status (payment_date, status),
    INDEX idx_fp_receipt (receipt_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.student_fee_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    invoice_number VARCHAR(50) DEFAULT NULL,
    total_fees DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    due_date DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_payment_date DATE DEFAULT NULL,
    INDEX idx_sfa_student (student_id),
    INDEX idx_sfa_status (status),
    INDEX idx_sfa_student_status (student_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PART 5: Cache Management Table (used by getCacheData/setCacheData)
-- ============================================================

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.cache_management (
    id INT NOT NULL AUTO_INCREMENT,
    cache_key VARCHAR(255) NOT NULL,
    cache_type ENUM('system','user','data','reports','templates','dashboard','session') DEFAULT 'system',
    cache_data LONGTEXT,
    expiry_time TIMESTAMP NULL DEFAULT (NOW() + INTERVAL 1 HOUR),
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cache_key (cache_key),
    INDEX idx_cache_expiry (expiry_time),
    INDEX idx_cache_type (cache_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PART 6: Performance Indexes
-- ============================================================

DROP PROCEDURE IF EXISTS AddIdxIfMissing;
DELIMITER //
CREATE PROCEDURE AddIdxIfMissing(
    IN p_db_name VARCHAR(64),
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_columns TEXT
)
BEGIN
    DECLARE idx_count INT DEFAULT 0;
    SET @schema = p_db_name;
    SET @table  = p_table_name;
    SET @index  = p_index_name;
    SELECT COUNT(*) INTO idx_count
    FROM information_schema.statistics
    WHERE table_schema = @schema
      AND table_name   = @table
      AND index_name   = @index;
    IF idx_count = 0 THEN
        SET @sql = CONCAT('CREATE INDEX ', p_index_name, ' ON ', @schema, '.', @table, ' (', p_index_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

-- students_db indexes
CALL AddIdxIfMissing('igangaschoolofl_students_db','students','idx_students_status','status');
CALL AddIdxIfMissing('igangaschoolofl_students_db','students','idx_students_program','program');
CALL AddIdxIfMissing('igangaschoolofl_students_db','students','idx_students_student_number','student_number');
CALL AddIdxIfMissing('igangaschoolofl_students_db','students','idx_students_name','surname,first_name');
CALL AddIdxIfMissing('igangaschoolofl_students_db','fee_structures','idx_fs_program','program_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','fee_structures','idx_fs_academic_year','academic_year');
CALL AddIdxIfMissing('igangaschoolofl_students_db','student_invoices','idx_si_student_id','student_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','student_invoices','idx_si_status','status');
CALL AddIdxIfMissing('igangaschoolofl_students_db','student_invoices','idx_si_created','created_at');
CALL AddIdxIfMissing('igangaschoolofl_students_db','student_invoices','idx_si_student_status','student_id,status');
CALL AddIdxIfMissing('igangaschoolofl_students_db','student_fees','idx_sf_student_id','student_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','student_fees','idx_sf_status','status');
CALL AddIdxIfMissing('igangaschoolofl_students_db','payments','idx_payments_student','student_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','payments','idx_payments_date','payment_date');
CALL AddIdxIfMissing('igangaschoolofl_students_db','payments','idx_payments_status','status');
CALL AddIdxIfMissing('igangaschoolofl_students_db','payments','idx_payments_ref','payment_reference');
CALL AddIdxIfMissing('igangaschoolofl_students_db','fee_adjustments','idx_fa_student','student_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','fee_reminders','idx_fr_student','student_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','budgets','idx_budgets_fiscal_year','fiscal_year');
CALL AddIdxIfMissing('igangaschoolofl_students_db','budgets','idx_budgets_status','status');
CALL AddIdxIfMissing('igangaschoolofl_students_db','budget_records','idx_br_budget','budget_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','expenditure_records','idx_er_date','expenditure_date');
CALL AddIdxIfMissing('igangaschoolofl_students_db','expenditure_records','idx_er_budget','budget_record_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','chart_of_accounts','idx_coa_type','account_type');
CALL AddIdxIfMissing('igangaschoolofl_students_db','general_ledger','idx_gl_date','transaction_date');
CALL AddIdxIfMissing('igangaschoolofl_students_db','general_ledger','idx_gl_account','account_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','general_ledger','idx_gl_type','transaction_type');
CALL AddIdxIfMissing('igangaschoolofl_students_db','cash_book','idx_cb_date','transaction_date');
CALL AddIdxIfMissing('igangaschoolofl_students_db','assets','idx_assets_category','category_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','assets','idx_assets_status','status');
CALL AddIdxIfMissing('igangaschoolofl_students_db','hostel_rooms','idx_hr_status','status');
CALL AddIdxIfMissing('igangaschoolofl_students_db','hostel_allocations','idx_ha_student','student_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','hostel_allocations','idx_ha_status','status');
CALL AddIdxIfMissing('igangaschoolofl_students_db','proof_of_payments','idx_pop_student','student_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','student_course_registrations','idx_cr_student','student_id');
CALL AddIdxIfMissing('igangaschoolofl_students_db','student_course_registrations','idx_cr_status','status');

-- staffs_db indexes
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','staff','idx_staff_role','role_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','staff','idx_staff_full_name','full_name');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','staff','idx_staff_department','department');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','fee_payments','idx_fp_student','student_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','fee_payments','idx_fp_date','payment_date');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','fee_payments','idx_fp_status','status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','fee_payments','idx_fp_date_status','payment_date,status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','fee_payments','idx_fp_receipt','receipt_number');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','student_fee_accounts','idx_sfa_student','student_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','student_fee_accounts','idx_sfa_status','status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','student_fee_accounts','idx_sfa_student_status','student_id,status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','payments','idx_pay_student','student_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','payments','idx_pay_date','payment_date');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','payments','idx_pay_status','status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','attendance','idx_att_date','attendance_date');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','attendance','idx_att_staff','staff_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','attendance','idx_att_date_staff','attendance_date,staff_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','disciplinary_actions','idx_da_staff','staff_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','disciplinary_actions','idx_da_status','status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','staff_appraisals','idx_app_staff','staff_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','staff_appraisals','idx_app_status','status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','staff_appraisals','idx_app_date','created_at');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','recruitment_jobs','idx_rec_status','status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','recruitment_jobs','idx_rec_date','posted_date');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','recruitment_applications','idx_ja_position','job_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','recruitment_applications','idx_ja_status','status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','staff_training','idx_train_date','start_date');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','bursar_payments','idx_bp_date','payment_date');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','bursar_payments','idx_bp_status','status');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db','bursar_daily_collections','idx_bdc_date','collection_date');

-- isnm_db indexes (skipped — isnm_db has no tables; auth uses staffs_db/bursar/students)

-- website_db indexes
CALL AddIdxIfMissing('igangaschoolofl_website_db','student_applications','idx_sa_status','status');
CALL AddIdxIfMissing('igangaschoolofl_website_db','student_applications','idx_sa_date','submitted_at');
CALL AddIdxIfMissing('igangaschoolofl_website_db','contact_submissions','idx_cs_status','status');
CALL AddIdxIfMissing('igangaschoolofl_website_db','contact_submissions','idx_cs_date','created_at');
CALL AddIdxIfMissing('igangaschoolofl_website_db','news','idx_news_date','created_at');
CALL AddIdxIfMissing('igangaschoolofl_website_db','news','idx_news_status','status');

-- ict_db indexes
CALL AddIdxIfMissing('igangaschoolofl_ict','lab_computers','idx_lc_status','status');
CALL AddIdxIfMissing('igangaschoolofl_ict','lab_bookings','idx_lb_date','booking_date');
CALL AddIdxIfMissing('igangaschoolofl_ict','it_support_tickets','idx_ist_status','status');
CALL AddIdxIfMissing('igangaschoolofl_ict','it_support_tickets','idx_ist_priority','priority');

-- ============================================================
-- PART 6: Document Settings Table (Transcripts & Certificates)
-- ============================================================
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.document_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    setting_type ENUM('text','textarea','image','select','number') DEFAULT 'text',
    description VARCHAR(255) DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT IGNORE INTO igangaschoolofl_staffs_db.document_settings (setting_key, setting_value, setting_group, setting_type, description) VALUES
('institution_name', 'International School of Nursing & Midwifery', 'institution', 'text', 'Full institution name for documents'),
('institution_short_name', 'ISNM', 'institution', 'text', 'Short name / acronym'),
('institution_address', 'P.O. Box 1234, Kampala, Uganda', 'institution', 'text', 'Postal address'),
('institution_phone', '+256 700 000 000', 'institution', 'text', 'Phone number'),
('institution_email', 'registrar@isnm.ac.ug', 'institution', 'text', 'Registrar email'),
('institution_motto', '"Chosen to Serve, Based on a Disciplined Mind for Health Action"', 'institution', 'text', 'School motto'),
('principal_name', '_______________________', 'signatures', 'text', 'Principal full name'),
('director_name', '_______________________', 'signatures', 'text', 'Director General full name'),
('registrar_name', '_______________________', 'signatures', 'text', 'Academic Registrar full name'),
('transcript_fee', '50000', 'fees', 'number', 'Transcript processing fee (UGX)'),
('transcript_purposes', 'Academic,Employment,Transfer,Further Studies,Other', 'transcript', 'text', 'Comma-separated list of transcript purpose options'),
('transcript_default_type', 'transcript', 'transcript', 'text', 'Default transcript type (transcript or progress)'),
('transcript_footer', 'This is a computer-generated document. No signature required if digitally verified.', 'transcript', 'textarea', 'Footer text on transcripts'),
('transcript_verify_url', 'https://isnm.ac.ug/verify', 'transcript', 'text', 'Verification URL prefix'),
('logo_path', 'images/school-logo.png', 'appearance', 'text', 'Path to school logo image'),
('background_color', '#0f4c3a', 'appearance', 'text', 'Primary background color for headers'),
('accent_color', '#d4a843', 'appearance', 'text', 'Gold accent color'),
('font_family', 'Georgia, Times New Roman, serif', 'appearance', 'text', 'Font stack for documents');

DROP PROCEDURE IF EXISTS AddIdxIfMissing;
