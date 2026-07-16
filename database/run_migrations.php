<?php
/**
 * ISNM Complete Database Migration Script
 * Ensures ALL required tables, columns, indexes, and foreign keys exist.
 * Safe to run multiple times (idempotent).
 *
 * Usage: GET /database/run_migrations.php
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

$results = ['students' => [], 'staffs' => [], 'website' => [], 'ict' => [], 'errors' => []];

function addTable($conn, $dbName, $sql, $tableName) {
    global $results;
    $check = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($check && $check->num_rows > 0) {
        $results[$dbName][] = "$tableName: EXISTS";
        return;
    }
    if ($conn->query($sql)) {
        $results[$dbName][] = "$tableName: CREATED";
    } else {
        $results['errors'][] = "$dbName/$tableName: " . $conn->error;
    }
}

function addColumn($conn, $dbName, $table, $column, $definition) {
    global $results;
    $cols = $conn->query("SHOW COLUMNS FROM `$table`");
    $exists = false;
    if ($cols) { while ($c = $cols->fetch_assoc()) { if ($c['Field'] === $column) { $exists = true; break; } } }
    if ($exists) { return; }
    if ($conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
        $results[$dbName][] = "$table.$column: ADDED";
    } else {
        $results['errors'][] = "$dbName/$table.$column: " . $conn->error;
    }
}

function addIndex($conn, $dbName, $table, $indexName, $columns) {
    global $results;
    $check = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
    if ($check && $check->num_rows > 0) { return; }
    if ($conn->query("ALTER TABLE `$table` ADD INDEX `$indexName` ($columns)")) {
        $results[$dbName][] = "$table.$indexName: ADDED";
    }
}

// ══════════════════════════════════════════════════════════════
// STUDENTS DATABASE
// ══════════════════════════════════════════════════════════════
$stu = getStudentsConnection();
if ($stu) {
    addTable($stu, 'students', "CREATE TABLE `student_login_attempts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_number` VARCHAR(50) NOT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `success` TINYINT(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        INDEX `idx_student_number` (`student_number`),
        INDEX `idx_attempted_at` (`attempted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_login_attempts');

    addTable($stu, 'students', "CREATE TABLE `course_prerequisites` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `course_id` INT(11) NOT NULL,
        `prerequisite_course_id` INT(11) NOT NULL,
        `is_mandatory` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_course_prereq` (`course_id`, `prerequisite_course_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'course_prerequisites');

    addTable($stu, 'students', "CREATE TABLE `intake_plans` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `program_id` INT(11) DEFAULT NULL,
        `academic_year` VARCHAR(20) NOT NULL,
        `target_count` INT(11) DEFAULT 0,
        `actual_count` INT(11) DEFAULT 0,
        `status` ENUM('planning','active','closed') DEFAULT 'planning',
        `notes` TEXT DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'intake_plans');

    addTable($stu, 'students', "CREATE TABLE `student_requirements` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `requirement_name` VARCHAR(255) NOT NULL,
        `status` ENUM('pending','submitted','approved','rejected','missing') DEFAULT 'pending',
        `remarks` TEXT DEFAULT NULL,
        `document_path` VARCHAR(500) DEFAULT NULL,
        `verified_by` INT(11) DEFAULT NULL,
        `verified_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_student_id` (`student_id`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_requirements');

    // Ensure student_notifications table exists
    addTable($stu, 'students', "CREATE TABLE `student_notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT DEFAULT NULL,
        `type` VARCHAR(50) DEFAULT 'general',
        `priority` VARCHAR(20) DEFAULT 'Medium',
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_student_id` (`student_id`),
        INDEX `idx_is_read` (`is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_notifications');
    addColumn($stu, 'students', 'student_notifications', 'priority', "VARCHAR(20) DEFAULT 'Medium'");

    // Ensure payments table exists
    addTable($stu, 'students', "CREATE TABLE `payments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `payment_reference` VARCHAR(50) NOT NULL,
        `student_id` INT(11) NOT NULL,
        `amount_received` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `payment_method` VARCHAR(50) DEFAULT 'Cash',
        `transaction_ref` VARCHAR(100) DEFAULT NULL,
        `slip_number` VARCHAR(50) DEFAULT NULL,
        `payment_date` DATE DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'Completed',
        `received_by` INT(11) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_payment_ref` (`payment_reference`),
        INDEX `idx_student_id` (`student_id`),
        INDEX `idx_payment_date` (`payment_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payments');

    // Ensure student_fee_tracking exists
    addTable($stu, 'students', "CREATE TABLE `student_fee_tracking` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `total_fees` DECIMAL(12,2) DEFAULT 0,
        `amount_paid` DECIMAL(12,2) DEFAULT 0,
        `balance` DECIMAL(12,2) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Pending',
        `academic_year` VARCHAR(20) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_student_id` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_fee_tracking');

    // Ensure student_fee_assignments exists
    addTable($stu, 'students', "CREATE TABLE `student_fee_assignments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `fee_structure_id` INT(11) DEFAULT NULL,
        `assigned_amount` DECIMAL(12,2) DEFAULT 0,
        `paid_amount` DECIMAL(12,2) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_student_id` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_fee_assignments');

    // Ensure student_invoices exists
    addTable($stu, 'students', "CREATE TABLE `student_invoices` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `invoice_number` VARCHAR(50) NOT NULL,
        `student_id` INT(11) NOT NULL,
        `net_amount` DECIMAL(12,2) DEFAULT 0,
        `amount_paid` DECIMAL(12,2) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Pending',
        `due_date` DATE DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_invoice_number` (`invoice_number`),
        INDEX `idx_student_id` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_invoices');

    // Ensure fee_structures exists
    addTable($stu, 'students', "CREATE TABLE `fee_structures` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `fee_name` VARCHAR(255) NOT NULL,
        `fee_type` VARCHAR(50) DEFAULT 'Tuition',
        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `program_id` INT(11) DEFAULT NULL,
        `academic_year` INT(11) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'fee_structures');

    addColumn($stu, 'students', 'fee_adjustments', 'adjustment_number', "VARCHAR(50) DEFAULT NULL");
    addColumn($stu, 'students', 'fee_adjustments', 'created_by', "INT(11) DEFAULT NULL");
    addColumn($stu, 'students', 'fee_adjustments', 'discount_type', "VARCHAR(50) DEFAULT NULL");

    // Ensure expenses exists
    addTable($stu, 'students', "CREATE TABLE `expenses` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `expense_title` VARCHAR(255) NOT NULL,
        `title` VARCHAR(255) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `category` VARCHAR(100) DEFAULT NULL,
        `expense_date` DATE DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `recorded_by` INT(11) DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'expenses');
    addColumn($stu, 'students', 'expenses', 'status', "VARCHAR(20) DEFAULT 'pending'");
    addColumn($stu, 'students', 'expenses', 'created_by', "INT(11) DEFAULT NULL");

    // Ensure budgets exists
    addTable($stu, 'students', "CREATE TABLE `budgets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `budget_name` VARCHAR(255) NOT NULL,
        `budget_title` VARCHAR(255) DEFAULT NULL,
        `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `amount` DECIMAL(12,2) DEFAULT 0,
        `fiscal_year` VARCHAR(20) DEFAULT NULL,
        `year` VARCHAR(20) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'Draft',
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'budgets');
    addColumn($stu, 'students', 'budgets', 'total_amount', "DECIMAL(12,2) DEFAULT 0");
    addColumn($stu, 'students', 'budgets', 'budget_title', "VARCHAR(255) DEFAULT NULL");

    // Ensure payroll_runs exists
    addTable($stu, 'students', "CREATE TABLE `payroll_runs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `period` VARCHAR(20) NOT NULL,
        `description` VARCHAR(255) DEFAULT NULL,
        `run_date` DATE DEFAULT NULL,
        `total_gross` DECIMAL(14,2) DEFAULT 0,
        `total_deductions` DECIMAL(14,2) DEFAULT 0,
        `total_net` DECIMAL(14,2) DEFAULT 0,
        `employee_count` INT(11) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'draft',
        `processed_by` INT(11) DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_runs');
    addColumn($stu, 'students', 'payroll_runs', 'period', "VARCHAR(20) NOT NULL DEFAULT ''");
    addColumn($stu, 'students', 'payroll_runs', 'description', "VARCHAR(255) DEFAULT NULL");
    addColumn($stu, 'students', 'payroll_runs', 'created_by', "INT(11) DEFAULT NULL");

    // Ensure payroll_details exists
    addTable($stu, 'students', "CREATE TABLE `payroll_details` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `payroll_run_id` INT(11) NOT NULL,
        `staff_id` INT(11) NOT NULL,
        `basic_salary` DECIMAL(12,2) DEFAULT 0,
        `gross_pay` DECIMAL(12,2) DEFAULT 0,
        `allowances` DECIMAL(12,2) DEFAULT 0,
        `paye_tax` DECIMAL(12,2) DEFAULT 0,
        `paye` DECIMAL(12,2) DEFAULT 0,
        `nssf_employee` DECIMAL(12,2) DEFAULT 0,
        `nssf_employer` DECIMAL(12,2) DEFAULT 0,
        `nssf` DECIMAL(12,2) DEFAULT 0,
        `other_deductions` DECIMAL(12,2) DEFAULT 0,
        `net_pay` DECIMAL(12,2) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_payroll_run_id` (`payroll_run_id`),
        INDEX `idx_staff_id` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_details');
    addColumn($stu, 'students', 'payroll_details', 'gross_pay', "DECIMAL(12,2) DEFAULT 0");
    addColumn($stu, 'students', 'payroll_details', 'paye_tax', "DECIMAL(12,2) DEFAULT 0");
    addColumn($stu, 'students', 'payroll_details', 'nssf_employee', "DECIMAL(12,2) DEFAULT 0");
    addColumn($stu, 'students', 'payroll_details', 'nssf_employer', "DECIMAL(12,2) DEFAULT 0");

    // Ensure bank_reconciliation exists
    addTable($stu, 'students', "CREATE TABLE `bank_reconciliation` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `reconciliation_date` DATE NOT NULL,
        `statement_date` DATE DEFAULT NULL,
        `bank_balance` DECIMAL(14,2) DEFAULT 0,
        `book_balance` DECIMAL(14,2) DEFAULT 0,
        `difference` DECIMAL(14,2) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Pending',
        `reconciled_by` INT(11) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'bank_reconciliation');
    addColumn($stu, 'students', 'bank_reconciliation', 'reconciliation_date', "DATE NOT NULL DEFAULT '2000-01-01'");
    addColumn($stu, 'students', 'bank_reconciliation', 'reconciled_by', "INT(11) DEFAULT NULL");

    // Ensure assets table exists
    addTable($stu, 'students', "CREATE TABLE `assets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `asset_name` VARCHAR(255) NOT NULL,
        `asset_code` VARCHAR(50) DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `purchase_date` DATE DEFAULT NULL,
        `purchase_cost` DECIMAL(14,2) DEFAULT 0,
        `value` DECIMAL(14,2) DEFAULT 0,
        `current_value` DECIMAL(14,2) DEFAULT 0,
        `depreciation_value` DECIMAL(14,2) DEFAULT 0,
        `depreciation_rate` DECIMAL(5,2) DEFAULT 0,
        `useful_life_years` INT(11) DEFAULT 5,
        `salvage_value` DECIMAL(14,2) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Active',
        `location` VARCHAR(255) DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_asset_code` (`asset_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'assets');
    addColumn($stu, 'students', 'assets', 'value', "DECIMAL(14,2) DEFAULT 0");
    addColumn($stu, 'students', 'assets', 'depreciation_value', "DECIMAL(14,2) DEFAULT 0");
    addColumn($stu, 'students', 'assets', 'useful_life_years', "INT(11) DEFAULT 5");
    addColumn($stu, 'students', 'assets', 'salvage_value', "DECIMAL(14,2) DEFAULT 0");
    addColumn($stu, 'students', 'assets', 'created_by', "INT(11) DEFAULT NULL");

    // Ensure consumable_inventory exists
    addTable($stu, 'students', "CREATE TABLE `consumable_inventory` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `item_name` VARCHAR(255) NOT NULL,
        `item_code` VARCHAR(50) DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `quantity` INT(11) DEFAULT 0,
        `unit_cost` DECIMAL(12,2) DEFAULT 0,
        `reorder_level` INT(11) DEFAULT 0,
        `location` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'consumable_inventory');

    // Ensure tax_records exists
    addTable($stu, 'students', "CREATE TABLE `tax_records` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `tax_type` VARCHAR(50) NOT NULL,
        `amount` DECIMAL(12,2) DEFAULT 0,
        `period` VARCHAR(20) DEFAULT NULL,
        `reference` VARCHAR(100) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'Pending',
        `filed_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'tax_records');

    // Ensure payment_transactions exists
    addTable($stu, 'students', "CREATE TABLE `payment_transactions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `transaction_ref` VARCHAR(100) NOT NULL,
        `provider_key` VARCHAR(50) NOT NULL,
        `provider_transaction_id` VARCHAR(100) DEFAULT NULL,
        `transaction_type` VARCHAR(30) DEFAULT 'payment',
        `payment_for` VARCHAR(50) DEFAULT 'tuition',
        `transaction_id` VARCHAR(100) DEFAULT NULL,
        `student_id` INT(11) DEFAULT NULL,
        `staff_id` INT(11) DEFAULT NULL,
        `applicant_id` INT(11) DEFAULT NULL,
        `payer_name` VARCHAR(255) DEFAULT NULL,
        `payer_phone` VARCHAR(20) DEFAULT NULL,
        `payer_email` VARCHAR(255) DEFAULT NULL,
        `provider` VARCHAR(50) DEFAULT NULL,
        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `currency` VARCHAR(10) DEFAULT 'UGX',
        `fee_amount` DECIMAL(12,2) DEFAULT 0,
        `phone` VARCHAR(20) DEFAULT NULL,
        `status` VARCHAR(30) DEFAULT 'pending',
        `status_reason` TEXT DEFAULT NULL,
        `reference` VARCHAR(100) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `metadata` JSON DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `idempotency_key` VARCHAR(255) DEFAULT NULL,
        `completed_at` DATETIME DEFAULT NULL,
        `verified_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_transaction_ref` (`transaction_ref`),
        INDEX `idx_student_id` (`student_id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_provider_key` (`provider_key`),
        INDEX `idx_provider_txn` (`provider_transaction_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payment_transactions');
    addColumn($stu, 'students', 'payment_transactions', 'transaction_ref', "VARCHAR(100) NOT NULL DEFAULT ''");
    addColumn($stu, 'students', 'payment_transactions', 'provider_key', "VARCHAR(50) NOT NULL DEFAULT ''");
    addColumn($stu, 'students', 'payment_transactions', 'provider_transaction_id', "VARCHAR(100) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'transaction_type', "VARCHAR(30) DEFAULT 'payment'");
    addColumn($stu, 'students', 'payment_transactions', 'payment_for', "VARCHAR(50) DEFAULT 'tuition'");
    addColumn($stu, 'students', 'payment_transactions', 'staff_id', "INT(11) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'applicant_id', "INT(11) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'payer_name', "VARCHAR(255) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'payer_phone', "VARCHAR(20) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'payer_email', "VARCHAR(255) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'fee_amount', "DECIMAL(12,2) DEFAULT 0");
    addColumn($stu, 'students', 'payment_transactions', 'status_reason', "TEXT DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'description', "TEXT DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'ip_address', "VARCHAR(45) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'user_agent', "TEXT DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'idempotency_key', "VARCHAR(255) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'completed_at', "DATETIME DEFAULT NULL");
    addColumn($stu, 'students', 'payment_transactions', 'verified_at', "DATETIME DEFAULT NULL");

    // Ensure payment_providers exists
    addTable($stu, 'students', "CREATE TABLE `payment_providers` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `provider_key` VARCHAR(50) NOT NULL,
        `provider_name` VARCHAR(100) NOT NULL,
        `provider_type` VARCHAR(30) DEFAULT 'custom',
        `provider_category` VARCHAR(30) DEFAULT 'local',
        `is_enabled` TINYINT(1) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'sandbox',
        `mode` ENUM('sandbox','production') DEFAULT 'sandbox',
        `currency` VARCHAR(10) DEFAULT 'UGX',
        `config_json` JSON DEFAULT NULL,
        `config` JSON DEFAULT NULL,
        `api_url` VARCHAR(500) DEFAULT NULL,
        `test_api_base_url` VARCHAR(500) DEFAULT NULL,
        `min_amount` DECIMAL(12,2) DEFAULT 0,
        `max_amount` DECIMAL(12,2) DEFAULT 999999,
        `transaction_fee_percent` DECIMAL(5,2) DEFAULT 0,
        `transaction_fee_fixed` DECIMAL(12,2) DEFAULT 0,
        `sort_order` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_provider_key` (`provider_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payment_providers');
    addColumn($stu, 'students', 'payment_providers', 'status', "VARCHAR(20) DEFAULT 'sandbox'");
    addColumn($stu, 'students', 'payment_providers', 'config_json', "JSON DEFAULT NULL");
    addColumn($stu, 'students', 'payment_providers', 'provider_type', "VARCHAR(30) DEFAULT 'custom'");
    addColumn($stu, 'students', 'payment_providers', 'provider_category', "VARCHAR(30) DEFAULT 'local'");
    addColumn($stu, 'students', 'payment_providers', 'currency', "VARCHAR(10) DEFAULT 'UGX'");
    addColumn($stu, 'students', 'payment_providers', 'min_amount', "DECIMAL(12,2) DEFAULT 0");
    addColumn($stu, 'students', 'payment_providers', 'max_amount', "DECIMAL(12,2) DEFAULT 999999");
    addColumn($stu, 'students', 'payment_providers', 'sort_order', "INT(11) DEFAULT 0");
    addColumn($stu, 'students', 'payment_providers', 'api_url', "VARCHAR(500) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_providers', 'test_api_base_url', "VARCHAR(500) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_providers', 'transaction_fee_percent', "DECIMAL(5,2) DEFAULT 0");
    addColumn($stu, 'students', 'payment_providers', 'transaction_fee_fixed', "DECIMAL(12,2) DEFAULT 0");

    // Ensure payment_audit_log exists
    addTable($stu, 'students', "CREATE TABLE `payment_audit_log` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `transaction_id` VARCHAR(100) DEFAULT NULL,
        `user_id` INT(11) DEFAULT NULL,
        `user_type` VARCHAR(20) DEFAULT 'system',
        `action` VARCHAR(50) NOT NULL,
        `entity_type` VARCHAR(50) DEFAULT NULL,
        `entity_id` INT(11) DEFAULT NULL,
        `old_values` JSON DEFAULT NULL,
        `new_values` JSON DEFAULT NULL,
        `details` JSON DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_transaction_id` (`transaction_id`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_action` (`action`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payment_audit_log');
    addColumn($stu, 'students', 'payment_audit_log', 'user_type', "VARCHAR(20) DEFAULT 'system'");
    addColumn($stu, 'students', 'payment_audit_log', 'entity_type', "VARCHAR(50) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_audit_log', 'entity_id', "INT(11) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_audit_log', 'old_values', "JSON DEFAULT NULL");
    addColumn($stu, 'students', 'payment_audit_log', 'new_values', "JSON DEFAULT NULL");
    addColumn($stu, 'students', 'payment_audit_log', 'user_agent', "TEXT DEFAULT NULL");

    // Ensure payment_callbacks exists
    addTable($stu, 'students', "CREATE TABLE `payment_callbacks` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `provider_key` VARCHAR(50) NOT NULL,
        `provider` VARCHAR(50) DEFAULT NULL,
        `callback_type` VARCHAR(50) DEFAULT 'webhook',
        `transaction_id` VARCHAR(100) DEFAULT NULL,
        `request_method` VARCHAR(10) DEFAULT 'POST',
        `request_headers` JSON DEFAULT NULL,
        `request_body` JSON DEFAULT NULL,
        `request_ip` VARCHAR(45) DEFAULT NULL,
        `payload` JSON DEFAULT NULL,
        `processed` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_transaction_id` (`transaction_id`),
        INDEX `idx_provider_key` (`provider_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payment_callbacks');
    addColumn($stu, 'students', 'payment_callbacks', 'provider_key', "VARCHAR(50) NOT NULL DEFAULT ''");
    addColumn($stu, 'students', 'payment_callbacks', 'callback_type', "VARCHAR(50) DEFAULT 'webhook'");
    addColumn($stu, 'students', 'payment_callbacks', 'request_method', "VARCHAR(10) DEFAULT 'POST'");
    addColumn($stu, 'students', 'payment_callbacks', 'request_headers', "JSON DEFAULT NULL");
    addColumn($stu, 'students', 'payment_callbacks', 'request_body', "JSON DEFAULT NULL");
    addColumn($stu, 'students', 'payment_callbacks', 'request_ip', "VARCHAR(45) DEFAULT NULL");

    // Ensure payment_webhook_logs exists
    addTable($stu, 'students', "CREATE TABLE `payment_webhook_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `provider_key` VARCHAR(50) NOT NULL,
        `provider` VARCHAR(50) DEFAULT NULL,
        `event_type` VARCHAR(100) DEFAULT NULL,
        `payload` JSON DEFAULT NULL,
        `headers` JSON DEFAULT NULL,
        `signature` VARCHAR(500) DEFAULT NULL,
        `signature_valid` TINYINT(1) DEFAULT 1,
        `processing_status` VARCHAR(30) DEFAULT 'received',
        `error_message` TEXT DEFAULT NULL,
        `processing_time_ms` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_provider_key` (`provider_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payment_webhook_logs');
    addColumn($stu, 'students', 'payment_webhook_logs', 'provider_key', "VARCHAR(50) NOT NULL DEFAULT ''");
    addColumn($stu, 'students', 'payment_webhook_logs', 'headers', "JSON DEFAULT NULL");
    addColumn($stu, 'students', 'payment_webhook_logs', 'signature', "VARCHAR(500) DEFAULT NULL");
    addColumn($stu, 'students', 'payment_webhook_logs', 'processing_status', "VARCHAR(30) DEFAULT 'received'");
    addColumn($stu, 'students', 'payment_webhook_logs', 'error_message', "TEXT DEFAULT NULL");
    addColumn($stu, 'students', 'payment_webhook_logs', 'processing_time_ms', "INT(11) DEFAULT NULL");

    // Ensure payment_refunds exists
    addTable($stu, 'students', "CREATE TABLE `payment_refunds` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `refund_ref` VARCHAR(100) NOT NULL,
        `original_transaction_id` INT(11) DEFAULT NULL,
        `provider_key` VARCHAR(50) NOT NULL,
        `provider_refund_id` VARCHAR(100) DEFAULT NULL,
        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `reason` TEXT DEFAULT NULL,
        `status` VARCHAR(30) DEFAULT 'pending',
        `approved_by` INT(11) DEFAULT NULL,
        `approved_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_refund_ref` (`refund_ref`),
        INDEX `idx_original_transaction` (`original_transaction_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payment_refunds');

    // Ensure payment_receipts exists
    addTable($stu, 'students', "CREATE TABLE `payment_receipts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `receipt_number` VARCHAR(50) NOT NULL,
        `transaction_id` INT(11) DEFAULT NULL,
        `student_id` INT(11) DEFAULT NULL,
        `amount` DECIMAL(12,2) DEFAULT 0,
        `issued_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `issued_by` INT(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_receipt_number` (`receipt_number`),
        INDEX `idx_transaction_id` (`transaction_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payment_receipts');

    // Ensure payment_gateway_settings exists
    addTable($stu, 'students', "CREATE TABLE `payment_gateway_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_key` VARCHAR(100) NOT NULL,
        `setting_value` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payment_gateway_settings');

    // Ensure programs table exists
    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `programs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `program_name` VARCHAR(255) NOT NULL,
        `program_code` VARCHAR(20) DEFAULT NULL,
        `duration_years` INT(11) DEFAULT 3,
        `department` VARCHAR(100) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'programs');

    // Add missing columns to existing tables
    addColumn($stu, 'students', 'students', 'academic_year', "VARCHAR(20) DEFAULT NULL");
    addColumn($stu, 'students', 'students', 'set_name', "VARCHAR(50) DEFAULT NULL");
    addColumn($stu, 'students', 'students', 'registration_number', "VARCHAR(50) DEFAULT NULL");
    addColumn($stu, 'students', 'students', 'national_student_id_number', "VARCHAR(50) DEFAULT NULL");
    addColumn($stu, 'students', 'students', 'index_number', "VARCHAR(50) DEFAULT NULL");

    addIndex($stu, 'students', 'students', 'idx_student_number', 'student_number');
    addIndex($stu, 'students', 'students', 'idx_email', 'email');
    addIndex($stu, 'students', 'students', 'idx_status', 'status');
    addIndex($stu, 'students', 'students', 'idx_program', 'program');

    // Seed payment providers if table is empty
    $providerCount = $stu->query("SELECT COUNT(*) as cnt FROM payment_providers")->fetch_assoc()['cnt'] ?? 0;
    if ($providerCount == 0) {
        $providers = [
            ['mtn_momo', 'MTN Mobile Money', 'mobile_money', 'local', 'active', 'UGX'],
            ['airtel_money', 'Airtel Money', 'mobile_money', 'local', 'active', 'UGX'],
            ['pesapal', 'PesaPal', 'card', 'regional', 'testing', 'UGX'],
            ['flutterwave', 'Flutterwave', 'card', 'international', 'testing', 'UGX'],
            ['stripe', 'Stripe', 'card', 'international', 'testing', 'UGX'],
            ['paypal', 'PayPal', 'card', 'international', 'testing', 'USD'],
            ['bank_transfer', 'Bank Transfer', 'bank', 'local', 'active', 'UGX'],
        ];
        $ins = $stu->prepare("INSERT INTO payment_providers (provider_key, provider_name, provider_type, provider_category, status, is_enabled, currency, sort_order) VALUES (?,?,?,?,?,?,?,?)");
        if ($ins) {
            $i = 1;
            foreach ($providers as $p) {
                $pKey = $p[0]; $pName = $p[1]; $pType = $p[2]; $pCat = $p[3];
                $pStatus = $p[4]; $enabled = ($p[4] === 'active') ? 1 : 0;
                $pCurrency = $p[5]; $sortOrder = $i;
                $ins->bind_param('sssssisi', $pKey, $pName, $pType, $pCat, $pStatus, $enabled, $pCurrency, $sortOrder);
                $ins->execute();
                $i++;
            }
            $ins->close();
            $results['students'][] = 'payment_providers: SEEDED with ' . count($providers) . ' providers';
        }
    }

    $stu->close();
}

// ══════════════════════════════════════════════════════════════
// STAFFS DATABASE
// ══════════════════════════════════════════════════════════════
$staff = getStaffConnection();
if ($staff) {
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `password_changes` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) NOT NULL,
        `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_staff_id` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'password_changes');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `staff_activity_log` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) DEFAULT NULL,
        `action` VARCHAR(100) NOT NULL,
        `details` TEXT DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_staff_id` (`staff_id`),
        INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'staff_activity_log');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `audit_trail` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) DEFAULT NULL,
        `action` VARCHAR(100) NOT NULL,
        `table_name` VARCHAR(100) DEFAULT NULL,
        `record_id` INT(11) DEFAULT NULL,
        `old_values` JSON DEFAULT NULL,
        `new_values` JSON DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_staff_id` (`staff_id`),
        INDEX `idx_action` (`action`),
        INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'audit_trail');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) DEFAULT NULL,
        `user_type` VARCHAR(20) DEFAULT 'staff',
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT DEFAULT NULL,
        `type` VARCHAR(50) DEFAULT 'general',
        `is_read` TINYINT(1) DEFAULT 0,
        `link` VARCHAR(500) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_is_read` (`is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'notifications');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_key` VARCHAR(100) NOT NULL,
        `setting_value` TEXT DEFAULT NULL,
        `setting_type` VARCHAR(20) DEFAULT 'string',
        `description` TEXT DEFAULT NULL,
        `updated_by` INT(11) DEFAULT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'system_settings');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `task_assignments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `task_title` VARCHAR(255) NOT NULL,
        `task_description` TEXT DEFAULT NULL,
        `assigned_to` INT(11) DEFAULT NULL,
        `assigned_by` INT(11) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `priority` VARCHAR(20) DEFAULT 'medium',
        `due_date` DATE DEFAULT NULL,
        `completed_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_assigned_to` (`assigned_to`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'task_assignments');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `permissions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `slug` VARCHAR(100) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `category` VARCHAR(50) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'permissions');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `role_permissions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `role_id` INT(11) NOT NULL,
        `permission_id` INT(11) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'role_permissions');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `staff_login_sessions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) NOT NULL,
        `session_id` VARCHAR(128) DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `login_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `logout_at` DATETIME DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        PRIMARY KEY (`id`),
        INDEX `idx_staff_id` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'staff_login_sessions');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `approval_requests` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `requester_id` INT(11) NOT NULL,
        `requester_type` VARCHAR(20) DEFAULT 'staff',
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `category` VARCHAR(50) DEFAULT NULL,
        `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
        `reviewed_by` INT(11) DEFAULT NULL,
        `reviewed_at` DATETIME DEFAULT NULL,
        `remarks` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_requester_id` (`requester_id`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'approval_requests');

    // Ensure bursar_users has required columns
    addColumn($staff, 'staffs', 'bursar_users', 'login_attempts', "INT(11) DEFAULT 0");
    addColumn($staff, 'staffs', 'bursar_users', 'locked_until', "DATETIME DEFAULT NULL");

    // Ensure staff table has required columns
    addColumn($staff, 'staffs', 'staff', 'staff_id', "VARCHAR(50) DEFAULT NULL");
    addColumn($staff, 'staffs', 'staff', 'status', "VARCHAR(20) DEFAULT 'Active'");
    addColumn($staff, 'staffs', 'staff', 'position', "VARCHAR(150) DEFAULT NULL");
    addColumn($staff, 'staffs', 'staff', 'department', "VARCHAR(150) DEFAULT NULL");
    addColumn($staff, 'staffs', 'staff', 'login_attempts', "INT(11) DEFAULT 0");
    addColumn($staff, 'staffs', 'staff', 'locked_until', "DATETIME DEFAULT NULL");
    addColumn($staff, 'staffs', 'staff', 'is_first_login', "TINYINT(1) DEFAULT 0");
    addColumn($staff, 'staffs', 'staff', 'password_changed', "TINYINT(1) DEFAULT 1");
    addColumn($staff, 'staffs', 'staff', 'profile_photo', "VARCHAR(255) DEFAULT NULL");
    addColumn($staff, 'staffs', 'staff', 'staff_category', "ENUM('teaching','non-teaching','clinical','administrative') DEFAULT 'non-teaching'");

    addIndex($staff, 'staffs', 'staff', 'idx_email', 'email');
    addIndex($staff, 'staffs', 'staff', 'idx_role_id', 'role_id');
    addIndex($staff, 'staffs', 'staff', 'idx_status', 'status');

    // Ensure staff_roles has dashboard_path column
    addColumn($staff, 'staffs', 'staff_roles', 'dashboard_path', "VARCHAR(255) DEFAULT NULL");
    addColumn($staff, 'staffs', 'staff_roles', 'hierarchy_level', "INT(11) DEFAULT 5");
    addColumn($staff, 'staffs', 'staff_roles', 'permissions', "JSON DEFAULT NULL");

    $staff->close();
}

// ══════════════════════════════════════════════════════════════
// WEBSITE DATABASE
// ══════════════════════════════════════════════════════════════
$web = getWebsiteConnection();
if ($web) {
    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `news` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) DEFAULT NULL,
        `content` TEXT DEFAULT NULL,
        `excerpt` TEXT DEFAULT NULL,
        `image` VARCHAR(500) DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `status` ENUM('draft','published','archived') DEFAULT 'draft',
        `is_featured` TINYINT(1) DEFAULT 0,
        `published_at` DATETIME DEFAULT NULL,
        `author_id` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'news');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `events` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `event_date` DATE DEFAULT NULL,
        `event_time` TIME DEFAULT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `image` VARCHAR(500) DEFAULT NULL,
        `status` ENUM('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'events');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `testimonials` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `person_name` VARCHAR(255) NOT NULL,
        `position` VARCHAR(100) DEFAULT NULL,
        `quote` TEXT DEFAULT NULL,
        `photo` VARCHAR(500) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'testimonials');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `faqs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `question` VARCHAR(500) NOT NULL,
        `answer` TEXT DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `sort_order` INT(11) DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'faqs');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `contact_submissions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `subject` VARCHAR(255) DEFAULT NULL,
        `message` TEXT DEFAULT NULL,
        `status` ENUM('new','read','replied','archived') DEFAULT 'new',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'contact_submissions');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `volunteers` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `skills` TEXT DEFAULT NULL,
        `availability` VARCHAR(100) DEFAULT NULL,
        `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'volunteers');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `donations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `donor_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) DEFAULT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `amount` DECIMAL(12,2) DEFAULT 0,
        `purpose` VARCHAR(255) DEFAULT NULL,
        `payment_method` VARCHAR(50) DEFAULT NULL,
        `status` ENUM('pending','received','cancelled') DEFAULT 'pending',
        `receipt_number` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'donations');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `applications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `program` VARCHAR(255) DEFAULT NULL,
        `status` ENUM('pending','reviewing','accepted','rejected') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'applications');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `news_categories` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'news_categories');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `announcements` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT DEFAULT NULL,
        `target_audience` VARCHAR(100) DEFAULT 'all',
        `is_active` TINYINT(1) DEFAULT 1,
        `published_at` DATETIME DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'announcements');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `cms_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_key` VARCHAR(100) NOT NULL,
        `setting_value` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'cms_settings');

    $web->close();
}

// ══════════════════════════════════════════════════════════════
// ICT DATABASE
// ══════════════════════════════════════════════════════════════
$ict = getICTConnection();
if ($ict) {
    addTable($ict, 'ict', "CREATE TABLE IF NOT EXISTS `ict_assets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `asset_name` VARCHAR(255) NOT NULL,
        `asset_tag` VARCHAR(50) DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `serial_number` VARCHAR(100) DEFAULT NULL,
        `purchase_date` DATE DEFAULT NULL,
        `purchase_cost` DECIMAL(14,2) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Active',
        `assigned_to` INT(11) DEFAULT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_asset_tag` (`asset_tag`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'ict_assets');

    addTable($ict, 'ict', "CREATE TABLE IF NOT EXISTS `lab_inventory_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `item_name` VARCHAR(255) NOT NULL,
        `item_code` VARCHAR(50) DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `quantity` INT(11) DEFAULT 0,
        `unit_cost` DECIMAL(12,2) DEFAULT 0,
        `location` VARCHAR(255) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'Available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'lab_inventory_items');

    addTable($ict, 'ict', "CREATE TABLE IF NOT EXISTS `ict_server_status` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `server_name` VARCHAR(255) NOT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `status` ENUM('online','offline','maintenance') DEFAULT 'online',
        `last_checked` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'ict_server_status');

    addTable($ict, 'ict', "CREATE TABLE IF NOT EXISTS `ict_tickets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `priority` ENUM('low','medium','high','critical') DEFAULT 'medium',
        `status` ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
        `reported_by` INT(11) DEFAULT NULL,
        `assigned_to` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `resolved_at` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'ict_tickets');

    addTable($ict, 'ict', "CREATE TABLE IF NOT EXISTS `cybersecurity_incidents` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `incident_type` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `severity` ENUM('low','medium','high','critical') DEFAULT 'medium',
        `status` ENUM('open','investigating','resolved','closed') DEFAULT 'open',
        `reported_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `resolved_at` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'cybersecurity_incidents');

    addTable($ict, 'ict', "CREATE TABLE IF NOT EXISTS `computer_lab_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `item_name` VARCHAR(255) NOT NULL,
        `item_code` VARCHAR(50) DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `quantity` INT(11) DEFAULT 0,
        `condition_status` VARCHAR(50) DEFAULT 'Good',
        `location` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'computer_lab_items');

    $ict->close();
}

echo json_encode([
    'success' => true,
    'results' => $results,
    'summary' => [
        'students' => count($results['students']),
        'staffs' => count($results['staffs']),
        'website' => count($results['website']),
        'ict' => count($results['ict']),
        'errors' => count($results['errors']),
    ]
], JSON_PRETTY_PRINT);
