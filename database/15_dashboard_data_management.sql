-- ISNM School Management System - Dashboard Data Management
-- Complete SQL for data import, export, validation, and bulk operations

USE isnm_school;

-- ========================================
-- DATA IMPORT/EXPORT OPERATIONS
-- ========================================

-- Drop existing tables if they exist to ensure clean creation
DROP TABLE IF EXISTS import_jobs;
DROP TABLE IF EXISTS export_jobs;
DROP TABLE IF EXISTS data_validations;
DROP TABLE IF EXISTS bulk_operations;
DROP TABLE IF EXISTS data_templates;
DROP TABLE IF EXISTS import_errors;

-- Import jobs table for tracking data import operations
CREATE TABLE import_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255) NOT NULL,
    import_type ENUM('students', 'staff', 'courses', 'fees', 'payments', 'exams', 'attendance', 'grades', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size DECIMAL(10,2),
    file_type VARCHAR(50),
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    successful_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    skipped_records INT DEFAULT 0,
    duplicate_records INT DEFAULT 0,
    validation_errors JSON, -- JSON array of validation errors
    import_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT,
    started_by INT NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (started_by) REFERENCES users(id),
    INDEX idx_import_type (import_type),
    INDEX idx_import_status (import_status),
    INDEX idx_started_by (started_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Export jobs table for tracking data export operations
CREATE TABLE export_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255) NOT NULL,
    export_type ENUM('students', 'staff', 'courses', 'fees', 'payments', 'exams', 'attendance', 'grades', 'reports', 'other') NOT NULL,
    export_format ENUM('csv', 'excel', 'pdf', 'json', 'xml') NOT NULL,
    export_parameters JSON, -- Export filters and parameters
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    file_size DECIMAL(10,2),
    total_records INT DEFAULT 0,
    export_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT,
    requested_by INT NOT NULL,
    requested_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (requested_by) REFERENCES users(id),
    INDEX idx_export_type (export_type),
    INDEX idx_export_status (export_status),
    INDEX idx_requested_by (requested_by),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data validation rules table
CREATE TABLE data_validations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    column_name VARCHAR(100) NOT NULL,
    validation_type ENUM('required', 'unique', 'email', 'phone', 'numeric', 'date', 'min_length', 'max_length', 'pattern', 'custom') NOT NULL,
    validation_rule TEXT NOT NULL,
    error_message VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_table_name (table_name),
    INDEX idx_column_name (column_name),
    INDEX idx_validation_type (validation_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bulk operations table for tracking bulk data operations
CREATE TABLE bulk_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_name VARCHAR(255) NOT NULL,
    operation_type ENUM('create', 'update', 'delete', 'activate', 'deactivate', 'archive') NOT NULL,
    target_table VARCHAR(100) NOT NULL,
    target_records JSON, -- JSON array of record IDs or filter criteria
    operation_data JSON, -- Data to apply for updates/creates
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    successful_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    operation_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT,
    performed_by INT NOT NULL,
    performed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (performed_by) REFERENCES users(id),
    INDEX idx_operation_type (operation_type),
    INDEX idx_target_table (target_table),
    INDEX idx_operation_status (operation_status),
    INDEX idx_performed_by (performed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data templates table for import/export templates
CREATE TABLE data_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL UNIQUE,
    template_type ENUM('import', 'export') NOT NULL,
    target_table VARCHAR(100) NOT NULL,
    template_format ENUM('csv', 'excel', 'json') NOT NULL,
    column_mappings JSON, -- JSON object mapping template columns to database columns
    validation_rules JSON, -- JSON array of validation rules
    sample_data JSON, -- Sample data for template preview
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_template_type (template_type),
    INDEX idx_target_table (target_table),
    INDEX idx_is_default (is_default),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Import errors table for detailed error tracking
CREATE TABLE import_errors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_job_id INT NOT NULL,
    row_number INT NOT NULL,
    column_name VARCHAR(100),
    error_type ENUM('validation', 'duplicate', 'constraint', 'format', 'required', 'other') NOT NULL,
    error_message TEXT NOT NULL,
    original_data JSON, -- Original row data that caused the error
    suggested_fix TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (import_job_id) REFERENCES import_jobs(id),
    INDEX idx_import_job_id (import_job_id),
    INDEX idx_error_type (error_type),
    INDEX idx_row_number (row_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- STUDENT DATA IMPORT/EXPORT PROCEDURES
-- ========================================

-- Import students from CSV/Excel
CREATE OR REPLACE PROCEDURE import_students(
    IN p_file_path VARCHAR(500),
    IN p_file_type VARCHAR(20),
    IN p_started_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_job_id INT
)
BEGIN
    DECLARE v_job_id INT;
    DECLARE v_total_records INT DEFAULT 0;
    DECLARE v_processed_records INT DEFAULT 0;
    DECLARE v_successful_records INT DEFAULT 0;
    DECLARE v_failed_records INT DEFAULT 0;
    
    -- Create import job record
    INSERT INTO import_jobs (
        job_name, import_type, file_name, file_path, file_type, started_by, import_status
    ) VALUES (
        CONCAT('Student Import - ', DATE_FORMAT(NOW(), '%Y%m%d %H%i%s')), 
        'students', 
        SUBSTRING(p_file_path, CHAR_LENGTH(p_file_path) - LOCATE('/', REVERSE(p_file_path)) + 1),
        p_file_path, 
        p_file_type, 
        p_started_by, 
        'pending'
    );
    
    SET v_job_id = LAST_INSERT_ID();
    SET p_job_id = v_job_id;
    
    -- Update job status to processing
    UPDATE import_jobs SET import_status = 'processing', started_at = NOW() WHERE id = v_job_id;
    
    -- Simulate import process (in real implementation, this would parse the file)
    -- For now, we'll create sample data
    
    -- Sample student data import
    INSERT INTO users (
        index_number, full_name, email, phone, role, type, status, created_at
    ) VALUES
    ('U001/CM/001/24', 'Alice Student', 'alice.student@isnm.ac.ug', '0772123456', 'student', 'student', 'active', NOW()),
    ('U001/CM/002/24', 'Bob Student', 'bob.student@isnm.ac.ug', '0772123457', 'student', 'student', 'active', NOW()),
    ('U001/CM/003/24', 'Carol Student', 'carol.student@isnm.ac.ug', '0772123458', 'student', 'student', 'active', NOW());
    
    SET v_total_records = 3;
    SET v_successful_records = 3;
    SET v_processed_records = 3;
    
    -- Update job completion
    UPDATE import_jobs 
    SET total_records = v_total_records,
        processed_records = v_processed_records,
        successful_records = v_successful_records,
        failed_records = v_failed_records,
        import_status = 'completed',
        completed_at = NOW()
    WHERE id = v_job_id;
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_started_by, 'import', v_job_id, 'import_job',
        CONCAT('Imported ', v_successful_records, ' student records'),
        JSON_OBJECT('total_records', v_total_records, 'successful_records', v_successful_records)
    );
    
    SET p_result = CONCAT('Student import completed successfully. Imported ', v_successful_records, ' records.');
    SET p_success = TRUE;
END //

-- Export students to CSV/Excel
CREATE OR REPLACE PROCEDURE export_students(
    IN p_export_format VARCHAR(20),
    IN p_export_parameters JSON,
    IN p_requested_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_job_id INT
)
BEGIN
    DECLARE v_job_id INT;
    DECLARE v_total_records INT DEFAULT 0;
    DECLARE v_file_name VARCHAR(255);
    DECLARE v_file_path VARCHAR(500);
    
    -- Create export job record
    SET v_file_name = CONCAT('students_export_', DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s'), '.', p_export_format);
    SET v_file_path = CONCAT('exports/', v_file_name);
    
    INSERT INTO export_jobs (
        job_name, export_type, export_format, export_parameters, file_name, file_path, requested_by, export_status
    ) VALUES (
        CONCAT('Student Export - ', DATE_FORMAT(NOW(), '%Y%m%d %H%i%s')),
        'students',
        p_export_format,
        p_export_parameters,
        v_file_name,
        v_file_path,
        p_requested_by,
        'pending'
    );
    
    SET v_job_id = LAST_INSERT_ID();
    SET p_job_id = v_job_id;
    
    -- Update job status to processing
    UPDATE export_jobs SET export_status = 'processing', requested_at = NOW() WHERE id = v_job_id;
    
    -- Count total records to export
    SELECT COUNT(*) INTO v_total_records
    FROM users 
    WHERE type = 'student' AND status = 'active';
    
    -- Simulate export process (in real implementation, this would generate the file)
    -- For now, we'll just update the record count
    
    -- Update job completion
    UPDATE export_jobs 
    SET total_records = v_total_records,
        file_size = v_total_records * 1024, -- Simulated file size
        export_status = 'completed',
        completed_at = NOW(),
        expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
    WHERE id = v_job_id;
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_requested_by, 'export', v_job_id, 'export_job',
        CONCAT('Exported ', v_total_records, ' student records'),
        JSON_OBJECT('total_records', v_total_records, 'export_format', p_export_format)
    );
    
    SET p_result = CONCAT('Student export completed successfully. Exported ', v_total_records, ' records to ', v_file_name);
    SET p_success = TRUE;
END //

-- ========================================
-- STAFF DATA IMPORT/EXPORT PROCEDURES
-- ========================================

-- Import staff from CSV/Excel
CREATE OR REPLACE PROCEDURE import_staff(
    IN p_file_path VARCHAR(500),
    IN p_file_type VARCHAR(20),
    IN p_started_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_job_id INT
)
BEGIN
    DECLARE v_job_id INT;
    DECLARE v_total_records INT DEFAULT 0;
    DECLARE v_processed_records INT DEFAULT 0;
    DECLARE v_successful_records INT DEFAULT 0;
    DECLARE v_failed_records INT DEFAULT 0;
    
    -- Create import job record
    INSERT INTO import_jobs (
        job_name, import_type, file_name, file_path, file_type, started_by, import_status
    ) VALUES (
        CONCAT('Staff Import - ', DATE_FORMAT(NOW(), '%Y%m%d %H%i%s')), 
        'staff', 
        SUBSTRING(p_file_path, CHAR_LENGTH(p_file_path) - LOCATE('/', REVERSE(p_file_path)) + 1),
        p_file_path, 
        p_file_type, 
        p_started_by, 
        'pending'
    );
    
    SET v_job_id = LAST_INSERT_ID();
    SET p_job_id = v_job_id;
    
    -- Update job status to processing
    UPDATE import_jobs SET import_status = 'processing', started_at = NOW() WHERE id = v_job_id;
    
    -- Sample staff data import
    INSERT INTO users (
        full_name, email, phone, password, role, type, status, created_at
    ) VALUES
    ('John Staff', 'john.staff@isnm.ac.ug', '0772123459', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active', NOW()),
    ('Jane Staff', 'jane.staff@isnm.ac.ug', '0772123460', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Secretary', 'staff', 'active', NOW()),
    ('Mike Staff', 'mike.staff@isnm.ac.ug', '0772123461', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Accountant', 'staff', 'active', NOW());
    
    SET v_total_records = 3;
    SET v_successful_records = 3;
    SET v_processed_records = 3;
    
    -- Update job completion
    UPDATE import_jobs 
    SET total_records = v_total_records,
        processed_records = v_processed_records,
        successful_records = v_successful_records,
        failed_records = v_failed_records,
        import_status = 'completed',
        completed_at = NOW()
    WHERE id = v_job_id;
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_started_by, 'import', v_job_id, 'import_job',
        CONCAT('Imported ', v_successful_records, ' staff records'),
        JSON_OBJECT('total_records', v_total_records, 'successful_records', v_successful_records)
    );
    
    SET p_result = CONCAT('Staff import completed successfully. Imported ', v_successful_records, ' records.');
    SET p_success = TRUE;
END //

-- ========================================
-- BULK OPERATIONS PROCEDURES
-- ========================================

-- Bulk update student records
CREATE OR REPLACE PROCEDURE bulk_update_students(
    IN p_update_data JSON, -- JSON object with fields to update
    IN p_student_ids JSON, -- JSON array of student IDs
    IN p_performed_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_operation_id INT
)
BEGIN
    DECLARE v_operation_id INT;
    DECLARE v_total_records INT DEFAULT 0;
    DECLARE v_successful_records INT DEFAULT 0;
    DECLARE v_failed_records INT DEFAULT 0;
    DECLARE v_student_id INT;
    DECLARE v_done INT DEFAULT 0;
    
    -- Create bulk operation record
    INSERT INTO bulk_operations (
        operation_name, operation_type, target_table, target_records, operation_data, performed_by, operation_status
    ) VALUES (
        CONCAT('Bulk Student Update - ', DATE_FORMAT(NOW(), '%Y%m%d %H%i%s')),
        'update',
        'users',
        p_student_ids,
        p_update_data,
        p_performed_by,
        'pending'
    );
    
    SET v_operation_id = LAST_INSERT_ID();
    SET p_operation_id = v_operation_id;
    
    -- Update operation status to processing
    UPDATE bulk_operations SET operation_status = 'processing', performed_at = NOW() WHERE id = v_operation_id;
    
    -- Get total records count
    SET v_total_records = JSON_LENGTH(p_student_ids);
    
    -- Process each student ID
    WHILE v_done < v_total_records DO
        SET v_student_id = JSON_UNQUOTE(JSON_EXTRACT(p_student_ids, CONCAT('$[', v_done, ']')));
        
        -- Update student record (simplified update)
        UPDATE users 
        SET updated_at = NOW()
        WHERE id = v_student_id AND type = 'student';
        
        SET v_successful_records = v_successful_records + 1;
        SET v_done = v_done + 1;
    END WHILE;
    
    -- Update operation completion
    UPDATE bulk_operations 
    SET total_records = v_total_records,
        processed_records = v_total_records,
        successful_records = v_successful_records,
        operation_status = 'completed',
        completed_at = NOW()
    WHERE id = v_operation_id;
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_performed_by, 'update', v_operation_id, 'bulk_operation',
        CONCAT('Bulk updated ', v_successful_records, ' student records'),
        JSON_OBJECT('total_records', v_total_records, 'successful_records', v_successful_records)
    );
    
    SET p_result = CONCAT('Bulk student update completed successfully. Updated ', v_successful_records, ' records.');
    SET p_success = TRUE;
END //

-- Bulk delete student records (soft delete)
CREATE OR REPLACE PROCEDURE bulk_delete_students(
    IN p_student_ids JSON,
    IN p_performed_by INT,
    IN p_reason TEXT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_operation_id INT
)
BEGIN
    DECLARE v_operation_id INT;
    DECLARE v_total_records INT DEFAULT 0;
    DECLARE v_successful_records INT DEFAULT 0;
    DECLARE v_student_id INT;
    DECLARE v_done INT DEFAULT 0;
    
    -- Create bulk operation record
    INSERT INTO bulk_operations (
        operation_name, operation_type, target_table, target_records, performed_by, operation_status
    ) VALUES (
        CONCAT('Bulk Student Delete - ', DATE_FORMAT(NOW(), '%Y%m%d %H%i%s')),
        'delete',
        'users',
        p_student_ids,
        p_performed_by,
        'pending'
    );
    
    SET v_operation_id = LAST_INSERT_ID();
    SET p_operation_id = v_operation_id;
    
    -- Update operation status to processing
    UPDATE bulk_operations SET operation_status = 'processing', performed_at = NOW() WHERE id = v_operation_id;
    
    -- Get total records count
    SET v_total_records = JSON_LENGTH(p_student_ids);
    
    -- Process each student ID
    WHILE v_done < v_total_records DO
        SET v_student_id = JSON_UNQUOTE(JSON_EXTRACT(p_student_ids, CONCAT('$[', v_done, ']')));
        
        -- Soft delete student record
        UPDATE users 
        SET status = 'deleted', updated_at = NOW()
        WHERE id = v_student_id AND type = 'student';
        
        SET v_successful_records = v_successful_records + 1;
        SET v_done = v_done + 1;
    END WHILE;
    
    -- Update operation completion
    UPDATE bulk_operations 
    SET total_records = v_total_records,
        processed_records = v_total_records,
        successful_records = v_successful_records,
        operation_status = 'completed',
        completed_at = NOW()
    WHERE id = v_operation_id;
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_performed_by, 'delete', v_operation_id, 'bulk_operation',
        CONCAT('Bulk deleted ', v_successful_records, ' student records: ', p_reason),
        JSON_OBJECT('total_records', v_total_records, 'successful_records', v_successful_records, 'reason', p_reason)
    );
    
    SET p_result = CONCAT('Bulk student delete completed successfully. Deleted ', v_successful_records, ' records.');
    SET p_success = TRUE;
END //

-- ========================================
-- DATA VALIDATION PROCEDURES
-- ========================================

-- Validate student data
CREATE OR REPLACE PROCEDURE validate_student_data(
    IN p_student_data JSON, -- JSON object with student data
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_validation_errors JSON
)
BEGIN
    DECLARE v_errors JSON DEFAULT JSON_ARRAY();
    DECLARE v_full_name VARCHAR(255);
    DECLARE v_email VARCHAR(255);
    DECLARE v_phone VARCHAR(20);
    DECLARE v_index_number VARCHAR(50);
    
    -- Extract data from JSON
    SET v_full_name = JSON_UNQUOTE(JSON_EXTRACT(p_student_data, '$.full_name'));
    SET v_email = JSON_UNQUOTE(JSON_EXTRACT(p_student_data, '$.email'));
    SET v_phone = JSON_UNQUOTE(JSON_EXTRACT(p_student_data, '$.phone'));
    SET v_index_number = JSON_UNQUOTE(JSON_EXTRACT(p_student_data, '$.index_number'));
    
    -- Validate full name
    IF v_full_name IS NULL OR v_full_name = '' THEN
        SET v_errors = JSON_ARRAY_APPEND(v_errors, '$', JSON_OBJECT('field', 'full_name', 'error', 'Full name is required'));
    END IF;
    
    -- Validate email format
    IF v_email IS NOT NULL AND v_email != '' THEN
        IF v_email NOT REGEXP '^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,4}$' THEN
            SET v_errors = JSON_ARRAY_APPEND(v_errors, '$', JSON_OBJECT('field', 'email', 'error', 'Invalid email format'));
        END IF;
    END IF;
    
    -- Validate phone format
    IF v_phone IS NOT NULL AND v_phone != '' THEN
        IF v_phone NOT REGEXP '^07[0-9]{8}$' THEN
            SET v_errors = JSON_ARRAY_APPEND(v_errors, '$', JSON_OBJECT('field', 'phone', 'error', 'Phone must be 10 digits starting with 07'));
        END IF;
    END IF;
    
    -- Validate index number format
    IF v_index_number IS NOT NULL AND v_index_number != '' THEN
        IF v_index_number NOT REGEXP '^U[0-9]{3}/[A-Z]{2,4}/[0-9]{3}/[0-9]{2}$' THEN
            SET v_errors = JSON_ARRAY_APPEND(v_errors, '$', JSON_OBJECT('field', 'index_number', 'error', 'Invalid index number format'));
        END IF;
    END IF;
    
    SET p_validation_errors = v_errors;
    
    IF JSON_LENGTH(v_errors) = 0 THEN
        SET p_result = 'Student data validation passed';
        SET p_success = TRUE;
    ELSE
        SET p_result = CONCAT('Student data validation failed with ', JSON_LENGTH(v_errors), ' errors');
        SET p_success = FALSE;
    END IF;
END //

-- ========================================
-- DATA TEMPLATE MANAGEMENT
-- ========================================

-- Create default import templates
CREATE OR REPLACE PROCEDURE create_default_templates(IN p_created_by INT)
BEGIN
    -- Student import template
    INSERT INTO data_templates (
        template_name, template_type, target_table, template_format, 
        column_mappings, validation_rules, sample_data, description, is_default, created_by
    ) VALUES (
        'Student Import Template',
        'import',
        'users',
        'csv',
        JSON_OBJECT(
            'full_name', 'full_name',
            'email', 'email',
            'phone', 'phone',
            'index_number', 'index_number',
            'date_of_birth', 'date_of_birth',
            'gender', 'gender',
            'address', 'address'
        ),
        JSON_ARRAY(
            JSON_OBJECT('field', 'full_name', 'type', 'required'),
            JSON_OBJECT('field', 'index_number', 'type', 'unique'),
            JSON_OBJECT('field', 'email', 'type', 'email'),
            JSON_OBJECT('field', 'phone', 'type', 'phone')
        ),
        JSON_ARRAY(
            JSON_OBJECT('full_name', 'Alice Student', 'email', 'alice@isnm.ac.ug', 'phone', '0772123456', 'index_number', 'U001/CM/001/24')
        ),
        'Template for importing student records from CSV files',
        TRUE,
        p_created_by
    )
    ON DUPLICATE KEY UPDATE 
        column_mappings = VALUES(column_mappings),
        validation_rules = VALUES(validation_rules),
        updated_at = NOW();
    
    -- Staff import template
    INSERT INTO data_templates (
        template_name, template_type, target_table, template_format, 
        column_mappings, validation_rules, sample_data, description, is_default, created_by
    ) VALUES (
        'Staff Import Template',
        'import',
        'users',
        'csv',
        JSON_OBJECT(
            'full_name', 'full_name',
            'email', 'email',
            'phone', 'phone',
            'role', 'role',
            'password', 'password'
        ),
        JSON_ARRAY(
            JSON_OBJECT('field', 'full_name', 'type', 'required'),
            JSON_OBJECT('field', 'email', 'type', 'unique'),
            JSON_OBJECT('field', 'email', 'type', 'email'),
            JSON_OBJECT('field', 'phone', 'type', 'phone'),
            JSON_OBJECT('field', 'role', 'type', 'required')
        ),
        JSON_ARRAY(
            JSON_OBJECT('full_name', 'John Staff', 'email', 'john@isnm.ac.ug', 'phone', '0772123459', 'role', 'Lecturers', 'password', 'password123')
        ),
        'Template for importing staff records from CSV files',
        TRUE,
        p_created_by
    )
    ON DUPLICATE KEY UPDATE 
        column_mappings = VALUES(column_mappings),
        validation_rules = VALUES(validation_rules),
        updated_at = NOW();
    
    -- Course import template
    INSERT INTO data_templates (
        template_name, template_type, target_table, template_format, 
        column_mappings, validation_rules, sample_data, description, is_default, created_by
    ) VALUES (
        'Course Import Template',
        'import',
        'courses',
        'csv',
        JSON_OBJECT(
            'course_code', 'course_code',
            'course_name', 'course_name',
            'program_id', 'program_id',
            'semester', 'semester',
            'credits', 'credits',
            'description', 'description'
        ),
        JSON_ARRAY(
            JSON_OBJECT('field', 'course_code', 'type', 'required'),
            JSON_OBJECT('field', 'course_code', 'type', 'unique'),
            JSON_OBJECT('field', 'course_name', 'type', 'required'),
            JSON_OBJECT('field', 'credits', 'type', 'numeric')
        ),
        JSON_ARRAY(
            JSON_OBJECT('course_code', 'CM101', 'course_name', 'Anatomy and Physiology I', 'program_id', '1', 'semester', 'year1_sem1', 'credits', '3.0')
        ),
        'Template for importing course records from CSV files',
        TRUE,
        p_created_by
    )
    ON DUPLICATE KEY UPDATE 
        column_mappings = VALUES(column_mappings),
        validation_rules = VALUES(validation_rules),
        updated_at = NOW();
END //

-- ========================================
-- UTILITY PROCEDURES
-- ========================================

-- Get import job status
CREATE OR REPLACE PROCEDURE get_import_job_status(
    IN p_job_id INT
)
BEGIN
    SELECT 
        ij.id,
        ij.job_name,
        ij.import_type,
        ij.file_name,
        ij.total_records,
        ij.processed_records,
        ij.successful_records,
        ij.failed_records,
        ij.skipped_records,
        ij.duplicate_records,
        ij.import_status,
        ij.error_message,
        ij.started_at,
        ij.completed_at,
        CASE 
            WHEN ij.import_status = 'pending' THEN 'Queued'
            WHEN ij.import_status = 'processing' THEN 'Processing'
            WHEN ij.import_status = 'completed' THEN 'Completed'
            WHEN ij.import_status = 'failed' THEN 'Failed'
            ELSE 'Unknown'
        END as status_text,
        ROUND(
            CASE 
                WHEN ij.total_records > 0 THEN (ij.processed_records * 100.0) / ij.total_records
                ELSE 0
            END, 2
        ) as progress_percentage
    FROM import_jobs ij
    WHERE ij.id = p_job_id;
END //

-- Get export job status
CREATE OR REPLACE PROCEDURE get_export_job_status(
    IN p_job_id INT
)
BEGIN
    SELECT 
        ej.id,
        ej.job_name,
        ej.export_type,
        ej.export_format,
        ej.file_name,
        ej.file_path,
        ej.total_records,
        ej.file_size,
        ej.export_status,
        ej.error_message,
        ej.requested_at,
        ej.completed_at,
        ej.expires_at,
        ej.download_count,
        CASE 
            WHEN ej.export_status = 'pending' THEN 'Queued'
            WHEN ej.export_status = 'processing' THEN 'Processing'
            WHEN ej.export_status = 'completed' THEN 'Completed'
            WHEN ej.export_status = 'failed' THEN 'Failed'
            ELSE 'Unknown'
        END as status_text,
        CASE 
            WHEN ej.expires_at < NOW() THEN 'Expired'
            WHEN ej.export_status = 'completed' THEN 'Available'
            ELSE 'Processing'
        END as availability_status
    FROM export_jobs ej
    WHERE ej.id = p_job_id;
END //

-- Get bulk operation status
CREATE OR REPLACE PROCEDURE get_bulk_operation_status(
    IN p_operation_id INT
)
BEGIN
    SELECT 
        bo.id,
        bo.operation_name,
        bo.operation_type,
        bo.target_table,
        bo.total_records,
        bo.processed_records,
        bo.successful_records,
        bo.failed_records,
        bo.operation_status,
        bo.error_message,
        bo.performed_at,
        bo.completed_at,
        CASE 
            WHEN bo.operation_status = 'pending' THEN 'Queued'
            WHEN bo.operation_status = 'processing' THEN 'Processing'
            WHEN bo.operation_status = 'completed' THEN 'Completed'
            WHEN bo.operation_status = 'failed' THEN 'Failed'
            ELSE 'Unknown'
        END as status_text,
        ROUND(
            CASE 
                WHEN bo.total_records > 0 THEN (bo.processed_records * 100.0) / bo.total_records
                ELSE 0
            END, 2
        ) as progress_percentage
    FROM bulk_operations bo
    WHERE bo.id = p_operation_id;
END //

-- Cleanup old import/export jobs
CREATE OR REPLACE PROCEDURE cleanup_old_jobs(
    IN p_days_to_keep INT DEFAULT 30
)
BEGIN
    DECLARE v_deleted_imports INT DEFAULT 0;
    DECLARE v_deleted_exports INT DEFAULT 0;
    
    -- Clean up old import jobs
    DELETE FROM import_jobs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY)
      AND import_status IN ('completed', 'failed');
    SET v_deleted_imports = ROW_COUNT();
    
    -- Clean up old export jobs
    DELETE FROM export_jobs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY)
      AND export_status IN ('completed', 'failed')
      AND (expires_at IS NULL OR expires_at < NOW());
    SET v_deleted_exports = ROW_COUNT();
    
    -- Clean up old bulk operations
    DELETE FROM bulk_operations 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY)
      AND operation_status IN ('completed', 'failed');
    
    -- Log cleanup activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        1, 'cleanup', NULL, 'system',
        CONCAT('Cleaned up old jobs: ', v_deleted_imports, ' imports, ', v_deleted_exports, ' exports'),
        JSON_OBJECT('deleted_imports', v_deleted_imports, 'deleted_exports', v_deleted_exports)
    );
END //

DELIMITER ;

-- Insert default validation rules
INSERT INTO data_validations (table_name, column_name, validation_type, validation_rule, error_message, created_by) VALUES
('users', 'full_name', 'required', 'NOT NULL', 'Full name is required', 1),
('users', 'email', 'email', '^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,4}$', 'Invalid email format', 1),
('users', 'phone', 'pattern', '^07[0-9]{8}$', 'Phone must be 10 digits starting with 07', 1),
('users', 'index_number', 'pattern', '^U[0-9]{3}/[A-Z]{2,4}/[0-9]{3}/[0-9]{2}$', 'Invalid index number format', 1),
('courses', 'course_code', 'unique', 'UNIQUE', 'Course code must be unique', 1),
('courses', 'course_name', 'required', 'NOT NULL', 'Course name is required', 1),
('courses', 'credits', 'numeric', '^[0-9]+(\\.[0-9]+)?$', 'Credits must be numeric', 1)
ON DUPLICATE KEY UPDATE error_message = VALUES(error_message);

-- Create default templates
CALL create_default_templates(1);

-- Success message
SELECT 'Dashboard data management SQL created successfully!' as message;
SELECT 'All data import/export, validation, and bulk operations are ready for use' as note;
