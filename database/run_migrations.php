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

    // NOTE: $staff connection must NOT be closed here because more tables are added
    // further down in this file (lines 1128+). Closing it causes 50+ tables to silently fail.

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `applicants` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `application_number` VARCHAR(30) NOT NULL,
        `student_number` VARCHAR(50) DEFAULT NULL,
        `registration_number` VARCHAR(50) DEFAULT NULL,
        `full_name` VARCHAR(255) NOT NULL,
        `first_name` VARCHAR(100) DEFAULT NULL,
        `middle_name` VARCHAR(100) DEFAULT NULL,
        `surname` VARCHAR(100) DEFAULT NULL,
        `gender` ENUM('Male','Female','Other') DEFAULT NULL,
        `date_of_birth` DATE DEFAULT NULL,
        `email` VARCHAR(100) DEFAULT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `nationality` VARCHAR(100) DEFAULT 'Ugandan',
        `district` VARCHAR(100) DEFAULT NULL,
        `religion` VARCHAR(50) DEFAULT NULL,
        `address` TEXT DEFAULT NULL,
        `program_id` INT(11) DEFAULT NULL,
        `intake` VARCHAR(50) DEFAULT NULL,
        `application_source` ENUM('Online','Manual','Walk-in','Referral','Other') DEFAULT 'Online',
        `status` ENUM('New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered','Withdrawn') NOT NULL DEFAULT 'New',
        `rejection_reason` TEXT DEFAULT NULL,
        `guardian_name` VARCHAR(200) DEFAULT NULL,
        `guardian_phone` VARCHAR(20) DEFAULT NULL,
        `emergency_contact_name` VARCHAR(100) DEFAULT NULL,
        `emergency_contact_phone` VARCHAR(20) DEFAULT NULL,
        `submitted_at` TIMESTAMP NULL DEFAULT NULL,
        `reviewed_by` INT(11) DEFAULT NULL,
        `reviewed_at` DATETIME DEFAULT NULL,
        `approved_by` INT(11) DEFAULT NULL,
        `approved_at` DATETIME DEFAULT NULL,
        `registered_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_application_number` (`application_number`),
        INDEX `idx_student_number` (`student_number`),
        INDEX `idx_status` (`status`),
        INDEX `idx_program_id` (`program_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'applicants');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `student_admission_tracking` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_number` VARCHAR(50) DEFAULT NULL,
        `full_name` VARCHAR(255) DEFAULT NULL,
        `application_number` VARCHAR(30) DEFAULT NULL,
        `applicant_id` INT(11) DEFAULT NULL,
        `program` VARCHAR(255) DEFAULT NULL,
        `intake` VARCHAR(50) DEFAULT NULL,
        `admission_date` DATE DEFAULT NULL,
        `admission_status` ENUM('Pending','Under Review','Requirements Pending','Approved','Rejected','Registered') NOT NULL DEFAULT 'Pending',
        `requirements_total` INT(11) NOT NULL DEFAULT 0,
        `requirements_completed` INT(11) NOT NULL DEFAULT 0,
        `documents_uploaded` INT(11) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_track_app` (`application_number`),
        INDEX `idx_student_number` (`student_number`),
        INDEX `idx_applicant_id` (`applicant_id`),
        INDEX `idx_admission_status` (`admission_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_admission_tracking');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `staff_profiles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) NOT NULL,
        `national_id` VARCHAR(50) DEFAULT NULL,
        `date_of_birth` DATE DEFAULT NULL,
        `gender` ENUM('Male','Female','Other') DEFAULT NULL,
        `marital_status` VARCHAR(30) DEFAULT NULL,
        `nationality` VARCHAR(100) DEFAULT 'Ugandan',
        `district` VARCHAR(100) DEFAULT NULL,
        `subcounty` VARCHAR(100) DEFAULT NULL,
        `village` VARCHAR(100) DEFAULT NULL,
        `next_of_kin_name` VARCHAR(200) DEFAULT NULL,
        `next_of_kin_phone` VARCHAR(20) DEFAULT NULL,
        `next_of_kin_relationship` VARCHAR(50) DEFAULT NULL,
        `bank_name` VARCHAR(100) DEFAULT NULL,
        `bank_account_number` VARCHAR(50) DEFAULT NULL,
        `nssf_number` VARCHAR(50) DEFAULT NULL,
        `tin_number` VARCHAR(50) DEFAULT NULL,
        `highest_education` VARCHAR(100) DEFAULT NULL,
        `institution` VARCHAR(200) DEFAULT NULL,
        `year_graduated` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_staff_profile` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'staff_profiles');

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

    addTable($ict, 'ict', "CREATE TABLE IF NOT EXISTS `it_support_tickets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `ticket_number` VARCHAR(50) NOT NULL,
        `requester_name` VARCHAR(100) NOT NULL,
        `requester_email` VARCHAR(100) DEFAULT NULL,
        `requester_type` ENUM('student','staff','faculty') NOT NULL,
        `issue_type` ENUM('hardware','software','network','account','other') NOT NULL,
        `priority` ENUM('low','medium','high','critical') DEFAULT 'medium',
        `subject` VARCHAR(255) DEFAULT NULL,
        `description` TEXT NOT NULL,
        `status` ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
        `assigned_to` INT(11) DEFAULT NULL,
        `resolution_notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_priority` (`priority`),
        INDEX `idx_ticket_number` (`ticket_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'it_support_tickets');

    // ═══════════════════════════════════════════════════════
    // CRITICAL MISSING TABLES (referenced by multiple dashboards)
    // ═══════════════════════════════════════════════════════

    // ── Staffs DB: Core System Tables ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `departments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(150) NOT NULL,
        `code` VARCHAR(50) DEFAULT NULL, `head_id` INT(11) DEFAULT NULL,
        `description` TEXT, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'departments');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `username` VARCHAR(100) NOT NULL,
        `email` VARCHAR(255) NOT NULL, `password_hash` VARCHAR(255) NOT NULL,
        `role` VARCHAR(50) DEFAULT 'staff', `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        UNIQUE KEY `uk_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'users');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `roles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL,
        `description` TEXT, `is_system` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'roles');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `password_resets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `user_id` INT(11) NOT NULL,
        `token` VARCHAR(255) NOT NULL, `expires_at` DATETIME NOT NULL,
        `used` TINYINT(1) DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'password_resets');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `recycle_bin` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `table_name` VARCHAR(100) NOT NULL,
        `record_id` INT(11) NOT NULL, `record_data` TEXT,
        `deleted_by` INT(11) DEFAULT NULL, `deleted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `restored_at` DATETIME DEFAULT NULL, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'recycle_bin');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `error_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `level` VARCHAR(20) DEFAULT 'ERROR',
        `message` TEXT, `file` VARCHAR(255), `line` INT(11), `trace` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'error_logs');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `backup_management` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `backup_name` VARCHAR(255) NOT NULL,
        `database_name` VARCHAR(100), `file_path` VARCHAR(500), `file_size` BIGINT DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'completed', `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'backup_management');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `system_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `action` VARCHAR(100) NOT NULL,
        `details` TEXT, `user_id` INT(11) DEFAULT NULL, `ip_address` VARCHAR(45),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'system_logs');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `alerts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `title` VARCHAR(255) NOT NULL,
        `message` TEXT, `severity` VARCHAR(20) DEFAULT 'info',
        `target_role` VARCHAR(100) DEFAULT NULL, `is_read` TINYINT(1) DEFAULT 0,
        `created_by` INT(11) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'alerts');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `leave_types` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL,
        `days_allowed` INT(11) DEFAULT 30, `is_paid` TINYINT(1) DEFAULT 1,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'leave_types');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `hr_activity_log` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` INT(11) NOT NULL,
        `action` VARCHAR(100) NOT NULL, `details` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'hr_activity_log');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `official_duties` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` INT(11) NOT NULL,
        `duty_type` VARCHAR(100) NOT NULL, `description` TEXT,
        `start_date` DATE, `end_date` DATE, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'official_duties');

    // ── Staffs DB: Messaging ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `staff_inbox` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `sender_id` INT(11) NOT NULL,
        `receiver_id` INT(11) NOT NULL, `subject` VARCHAR(255),
        `message` TEXT NOT NULL, `is_read` TINYINT(1) DEFAULT 0,
        `parent_id` INT(11) DEFAULT NULL, `attachment` VARCHAR(500),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_receiver` (`receiver_id`), KEY `idx_sender` (`sender_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'staff_inbox');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `email_notifications_queue` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `recipient_email` VARCHAR(255) NOT NULL,
        `subject` VARCHAR(255) NOT NULL, `body` TEXT NOT NULL,
        `status` VARCHAR(20) DEFAULT 'pending', `attempts` INT(11) DEFAULT 0,
        `last_attempt` DATETIME DEFAULT NULL, `sent_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'email_notifications_queue');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `portal_messages` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `sender_type` VARCHAR(50) DEFAULT 'staff',
        `sender_id` INT(11) NOT NULL, `receiver_type` VARCHAR(50) DEFAULT 'student',
        `receiver_id` INT(11) NOT NULL, `subject` VARCHAR(255),
        `message` TEXT NOT NULL, `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'portal_messages');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `messages` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `sender_id` INT(11) NOT NULL,
        `receiver_id` INT(11) NOT NULL, `subject` VARCHAR(255),
        `message` TEXT NOT NULL, `is_read` TINYINT(1) DEFAULT 0,
        `attachment` VARCHAR(500), `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_recv` (`receiver_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'messages');

    // ── Staffs DB: Approval Workflow ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `approval_workflows` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
        `description` TEXT, `target_table` VARCHAR(100),
        `is_active` TINYINT(1) DEFAULT 1, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'approval_workflows');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `approval_stages` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `workflow_id` INT(11) NOT NULL,
        `stage_name` VARCHAR(255) NOT NULL, `approver_role` VARCHAR(100),
        `approval_order` INT(11) DEFAULT 1, `is_mandatory` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'approval_stages');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `approval_actions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `approval_request_id` INT(11) NOT NULL,
        `stage_id` INT(11) DEFAULT NULL, `approver_id` INT(11) NOT NULL,
        `action` VARCHAR(20) NOT NULL, `comments` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'approval_actions');

    // ── Staffs DB: Academic ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `academic_records` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `academic_year` VARCHAR(20) NOT NULL, `semester` VARCHAR(50) NOT NULL,
        `course_code` VARCHAR(50), `course_title` VARCHAR(255), `credits` DECIMAL(4,1) DEFAULT 0,
        `grade` VARCHAR(5), `grade_points` DECIMAL(3,1) DEFAULT 0, `status` VARCHAR(20) DEFAULT 'Draft',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'academic_records');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `course_assignments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `course_code` VARCHAR(50) NOT NULL,
        `course_title` VARCHAR(255), `program_code` VARCHAR(50),
        `lecturer_id` INT(11) DEFAULT NULL, `academic_year` VARCHAR(20),
        `semester` VARCHAR(50), `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'course_assignments');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `course_registrations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `course_code` VARCHAR(50) NOT NULL, `academic_year` VARCHAR(20),
        `semester` VARCHAR(50), `status` VARCHAR(20) DEFAULT 'Registered',
        `registered_by` INT(11) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`), KEY `idx_course` (`course_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'course_registrations');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `timetable` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `course_code` VARCHAR(50) NOT NULL,
        `day_of_week` VARCHAR(20) NOT NULL, `start_time` TIME NOT NULL, `end_time` TIME NOT NULL,
        `room` VARCHAR(100), `lecturer_id` INT(11) DEFAULT NULL,
        `academic_year` VARCHAR(20), `semester` VARCHAR(50),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'timetable');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `academic_timetable` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `course_code` VARCHAR(50) NOT NULL,
        `day_of_week` VARCHAR(20) NOT NULL, `start_time` TIME NOT NULL, `end_time` TIME NOT NULL,
        `room` VARCHAR(100), `lecturer_id` INT(11) DEFAULT NULL,
        `academic_year` VARCHAR(20), `semester` VARCHAR(50),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'academic_timetable');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `result_approvals` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `academic_year` VARCHAR(20) NOT NULL,
        `semester` VARCHAR(50) NOT NULL, `program_code` VARCHAR(50),
        `approved_by` INT(11) DEFAULT NULL, `status` VARCHAR(20) DEFAULT 'Pending',
        `remarks` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'result_approvals');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `generated_documents` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) DEFAULT NULL,
        `document_type` VARCHAR(100) NOT NULL, `document_title` VARCHAR(255),
        `document_content` LONGTEXT, `generated_by` INT(11) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'Generated', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'generated_documents');

    // ── Staffs DB: Transport ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `vehicles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `vehicle_name` VARCHAR(255) NOT NULL,
        `plate_number` VARCHAR(50), `vehicle_type` VARCHAR(50), `capacity` INT(11) DEFAULT 0,
        `fuel_type` VARCHAR(50) DEFAULT 'Diesel', `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'vehicles');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `transport_vehicles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `vehicle_name` VARCHAR(255) NOT NULL,
        `plate_number` VARCHAR(50), `vehicle_type` VARCHAR(50), `capacity` INT(11) DEFAULT 0,
        `fuel_type` VARCHAR(50) DEFAULT 'Diesel', `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'transport_vehicles');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `fuel_management` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `vehicle_id` INT(11) NOT NULL,
        `liters` DECIMAL(8,2) NOT NULL, `cost` DECIMAL(12,2) NOT NULL,
        `fuel_date` DATE NOT NULL, `odometer` INT(11) DEFAULT NULL,
        `driver_id` INT(11) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_vehicle` (`vehicle_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'fuel_management');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `transport_routes` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `route_name` VARCHAR(255) NOT NULL,
        `start_location` VARCHAR(255), `end_location` VARCHAR(255),
        `distance_km` DECIMAL(8,2) DEFAULT NULL, `estimated_time` VARCHAR(50),
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'transport_routes');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `transport_trips` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `vehicle_id` INT(11) NOT NULL,
        `route_id` INT(11) DEFAULT NULL, `driver_id` INT(11) DEFAULT NULL,
        `trip_date` DATE NOT NULL, `departure_time` TIME, `arrival_time` TIME,
        `passengers` INT(11) DEFAULT 0, `status` VARCHAR(20) DEFAULT 'Scheduled',
        `dg_approval_status` VARCHAR(20) DEFAULT 'Pending', `dg_approved_by` INT(11) DEFAULT NULL,
        `dg_approved_at` DATETIME DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'transport_trips');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `trip_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `trip_id` INT(11) NOT NULL,
        `vehicle_id` INT(11) NOT NULL, `start_odometer` INT(11) DEFAULT 0,
        `end_odometer` INT(11) DEFAULT 0, `fuel_used` DECIMAL(8,2) DEFAULT 0,
        `notes` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'trip_logs');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `transport_student_assignments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `trip_id` INT(11) NOT NULL,
        `student_id` INT(11) NOT NULL, `stop_location` VARCHAR(255),
        `status` VARCHAR(20) DEFAULT 'Assigned', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'transport_student_assignments');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `transport_fuel_log` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `vehicle_id` INT(11) NOT NULL,
        `liters` DECIMAL(8,2) NOT NULL, `cost` DECIMAL(12,2) NOT NULL,
        `fuel_date` DATE NOT NULL, `driver_id` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'transport_fuel_log');

    // ── Staffs DB: Store/Inventory ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `store_categories` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(150) NOT NULL,
        `description` TEXT, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'store_categories');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `store_inventory` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `item_name` VARCHAR(255) NOT NULL,
        `item_code` VARCHAR(50), `category_id` INT(11) DEFAULT NULL,
        `quantity` INT(11) DEFAULT 0, `unit` VARCHAR(50) DEFAULT 'pcs',
        `min_stock` INT(11) DEFAULT 0, `location` VARCHAR(255),
        `status` VARCHAR(20) DEFAULT 'Available', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'store_inventory');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `store_inventory_transactions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `item_id` INT(11) NOT NULL,
        `transaction_type` VARCHAR(50) NOT NULL, `quantity` INT(11) NOT NULL,
        `reference` VARCHAR(255), `performed_by` INT(11) DEFAULT NULL,
        `notes` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_item` (`item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'store_inventory_transactions');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `store_requests` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `request_number` VARCHAR(50) NOT NULL,
        `requested_by` INT(11) NOT NULL, `requester_name` VARCHAR(255),
        `requester_role` VARCHAR(100), `department` VARCHAR(150),
        `items` TEXT, `urgency` VARCHAR(20) DEFAULT 'medium',
        `status` VARCHAR(20) DEFAULT 'pending', `approved_by` INT(11) DEFAULT NULL,
        `approved_at` DATETIME DEFAULT NULL, `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        UNIQUE KEY `uk_request_number` (`request_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'store_requests');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `store_request_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `request_id` INT(11) NOT NULL,
        `item_id` INT(11) DEFAULT NULL, `quantity_requested` DECIMAL(10,2) DEFAULT 0,
        `quantity_issued` DECIMAL(10,2) DEFAULT 0, `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_request` (`request_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'store_request_items');

    // ── Staffs DB: Payroll System ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `setting_key` VARCHAR(100) NOT NULL,
        `setting_value` TEXT, `description` TEXT,
        `updated_by` INT(11) DEFAULT NULL, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        UNIQUE KEY `uk_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_settings');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_employees` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` INT(11) NOT NULL,
        `employee_number` VARCHAR(50), `basic_salary` DECIMAL(12,2) DEFAULT 0,
        `bank_name` VARCHAR(150), `bank_account` VARCHAR(100), `tax_id` VARCHAR(50),
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_staff` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_employees');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_periods` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `period_name` VARCHAR(100) NOT NULL,
        `start_date` DATE NOT NULL, `end_date` DATE NOT NULL,
        `status` VARCHAR(20) DEFAULT 'Open', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_periods');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `payroll_run_id` INT(11) NOT NULL,
        `employee_id` INT(11) NOT NULL, `basic_salary` DECIMAL(12,2) DEFAULT 0,
        `allowances` DECIMAL(12,2) DEFAULT 0, `deductions` DECIMAL(12,2) DEFAULT 0,
        `tax` DECIMAL(12,2) DEFAULT 0, `net_pay` DECIMAL(12,2) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Pending', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_items');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_payslips` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `payroll_item_id` INT(11) NOT NULL,
        `payslip_number` VARCHAR(50), `basic_salary` DECIMAL(12,2) DEFAULT 0,
        `total_allowances` DECIMAL(12,2) DEFAULT 0, `total_deductions` DECIMAL(12,2) DEFAULT 0,
        `tax` DECIMAL(12,2) DEFAULT 0, `net_pay` DECIMAL(12,2) DEFAULT 0,
        `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_payslips');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_allowance_types` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(150) NOT NULL,
        `description` TEXT, `is_taxable` TINYINT(1) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_allowance_types');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_employee_allowances` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `employee_id` INT(11) NOT NULL,
        `allowance_type_id` INT(11) NOT NULL, `amount` DECIMAL(12,2) DEFAULT 0,
        `effective_date` DATE, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_employee_allowances');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_deduction_types` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(150) NOT NULL,
        `description` TEXT, `is_percentage` TINYINT(1) DEFAULT 0,
        `percentage_value` DECIMAL(5,2) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_deduction_types');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_employee_deductions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `employee_id` INT(11) NOT NULL,
        `deduction_type_id` INT(11) NOT NULL, `amount` DECIMAL(12,2) DEFAULT 0,
        `effective_date` DATE, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_employee_deductions');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_overtime` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `employee_id` INT(11) NOT NULL,
        `hours` DECIMAL(6,2) DEFAULT 0, `rate_per_hour` DECIMAL(10,2) DEFAULT 0,
        `overtime_date` DATE NOT NULL, `status` VARCHAR(20) DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_overtime');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_bonus` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `employee_id` INT(11) NOT NULL,
        `bonus_type` VARCHAR(100) NOT NULL, `amount` DECIMAL(12,2) NOT NULL,
        `description` TEXT, `approved_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_bonus');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_loans` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `employee_id` INT(11) NOT NULL,
        `loan_amount` DECIMAL(12,2) NOT NULL, `monthly_deduction` DECIMAL(12,2) DEFAULT 0,
        `remaining_balance` DECIMAL(12,2) DEFAULT 0, `purpose` TEXT,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_loans');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_audit_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `action` VARCHAR(100) NOT NULL,
        `details` TEXT, `user_id` INT(11) DEFAULT NULL, `ip_address` VARCHAR(45),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_audit_logs');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `payroll_approval_history` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `payroll_run_id` INT(11) DEFAULT NULL,
        `approved_by` INT(11) NOT NULL, `action` VARCHAR(50) NOT NULL,
        `comments` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payroll_approval_history');

    // ── Staffs DB: Cost Centers ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `cost_centers` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `code` VARCHAR(50) NOT NULL,
        `name` VARCHAR(255) NOT NULL, `description` TEXT,
        `budget` DECIMAL(15,2) DEFAULT 0, `spent` DECIMAL(15,2) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        UNIQUE KEY `uk_code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'cost_centers');

    // ── Staffs DB: Compliance & Accreditation ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `compliance_requirements` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `area` VARCHAR(255) NOT NULL,
        `description` TEXT, `standard` VARCHAR(255),
        `status` VARCHAR(20) DEFAULT 'Pending', `due_date` DATE,
        `responsible` VARCHAR(255), `evidence` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'compliance_requirements');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `institutional_alerts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `title` VARCHAR(255) NOT NULL,
        `message` TEXT, `severity` VARCHAR(20) DEFAULT 'info',
        `target_role` VARCHAR(100), `is_read` TINYINT(1) DEFAULT 0,
        `created_by` INT(11) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'institutional_alerts');

    addColumn($staff, 'staffs', 'institutional_alerts', 'alert_title', "VARCHAR(255) DEFAULT NULL");
    addColumn($staff, 'staffs', 'institutional_alerts', 'alert_message', "TEXT DEFAULT NULL");
    addColumn($staff, 'staffs', 'institutional_alerts', 'alert_type', "VARCHAR(50) DEFAULT 'info'");
    addColumn($staff, 'staffs', 'institutional_alerts', 'priority', "VARCHAR(20) DEFAULT 'Medium'");
    addColumn($staff, 'staffs', 'institutional_alerts', 'category', "VARCHAR(100) DEFAULT 'other'");
    addColumn($staff, 'staffs', 'institutional_alerts', 'department_code', "VARCHAR(50) DEFAULT NULL");
    addColumn($staff, 'staffs', 'institutional_alerts', 'source_url', "VARCHAR(500) DEFAULT NULL");
    addColumn($staff, 'staffs', 'institutional_alerts', 'is_auto_generated', "TINYINT(1) DEFAULT 0");
    addColumn($staff, 'staffs', 'institutional_alerts', 'is_resolved', "TINYINT(1) DEFAULT 0");
    addColumn($staff, 'staffs', 'institutional_alerts', 'expires_at', "DATETIME DEFAULT NULL");
    addColumn($staff, 'staffs', 'institutional_alerts', 'resolved_by', "INT(11) DEFAULT NULL");
    addColumn($staff, 'staffs', 'institutional_alerts', 'resolved_at', "DATETIME DEFAULT NULL");

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `alert_recipients` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `alert_id` INT(11) NOT NULL,
        `recipient_id` INT(11) NOT NULL, `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'alert_recipients');

    // ── Staffs DB: Director General CMS Tables ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `director_news` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `title` VARCHAR(255) NOT NULL,
        `content` LONGTEXT, `excerpt` TEXT, `status` VARCHAR(20) DEFAULT 'draft',
        `created_by` INT(11) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'director_news');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `cms_events` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `title` VARCHAR(255) NOT NULL,
        `description` TEXT, `event_date` DATE, `start_time` TIME, `end_time` TIME,
        `location` VARCHAR(255), `event_type` VARCHAR(50) DEFAULT 'General',
        `status` VARCHAR(20) DEFAULT 'upcoming', `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'cms_events');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `cms_testimonials` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `author_name` VARCHAR(255) NOT NULL,
        `author_role` VARCHAR(100), `content` TEXT NOT NULL,
        `rating` INT(11) DEFAULT 5, `status` VARCHAR(20) DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'cms_testimonials');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `cms_faqs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `question` VARCHAR(500) NOT NULL,
        `answer` TEXT NOT NULL, `category` VARCHAR(100) DEFAULT 'General',
        `sort_order` INT(11) DEFAULT 0, `status` VARCHAR(20) DEFAULT 'published',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'cms_faqs');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `news_views` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `news_id` INT(11) NOT NULL,
        `viewer_ip` VARCHAR(45), `viewer_id` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_news` (`news_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'news_views');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `staff_departments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `department_name` VARCHAR(255) NOT NULL,
        `department_code` VARCHAR(50) NOT NULL, `department_level` VARCHAR(50),
        `head_id` INT(11) DEFAULT NULL, `description` TEXT,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        UNIQUE KEY `uk_dept_code` (`department_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'staff_departments');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `pending_students` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `student_name` VARCHAR(255), `program` VARCHAR(150),
        `status` VARCHAR(20) DEFAULT 'Pending', `submitted_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'pending_students');

    // ── Staffs DB: Scholarships ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `scholarships` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
        `description` TEXT, `amount` DECIMAL(12,2) DEFAULT 0,
        `eligibility` TEXT, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'scholarships');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `sponsorships` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `sponsor_name` VARCHAR(255) NOT NULL,
        `student_id` INT(11) DEFAULT NULL, `amount` DECIMAL(12,2) DEFAULT 0,
        `start_date` DATE, `end_date` DATE, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'sponsorships');

    // ── Staffs DB: Lab ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `lab_equipment` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
        `equipment_code` VARCHAR(50), `category` VARCHAR(100),
        `quantity` INT(11) DEFAULT 0, `condition_status` VARCHAR(50) DEFAULT 'Good',
        `location` VARCHAR(255), `status` VARCHAR(20) DEFAULT 'Available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'lab_equipment');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `lab_sessions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `session_name` VARCHAR(255) NOT NULL,
        `instructor_id` INT(11) DEFAULT NULL, `scheduled_date` DATE,
        `start_time` TIME, `end_time` TIME, `room` VARCHAR(100),
        `max_students` INT(11) DEFAULT 30, `status` VARCHAR(20) DEFAULT 'Scheduled',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'lab_sessions');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `lab_checkouts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `equipment_id` INT(11) NOT NULL,
        `student_id` INT(11) NOT NULL, `checkout_date` DATETIME NOT NULL,
        `return_date` DATETIME DEFAULT NULL, `status` VARCHAR(20) DEFAULT 'Checked Out',
        `notes` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'lab_checkouts');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `lab_demonstrations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `session_id` INT(11) NOT NULL,
        `topic` VARCHAR(255) NOT NULL, `description` TEXT,
        `instructor_id` INT(11) DEFAULT NULL, `duration_minutes` INT(11) DEFAULT 60,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'lab_demonstrations');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `lab_consumables` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
        `category` VARCHAR(100), `quantity` INT(11) DEFAULT 0,
        `unit` VARCHAR(50) DEFAULT 'pcs', `min_stock` INT(11) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Available', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'lab_consumables');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `lab_incidents` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `incident_type` VARCHAR(100) NOT NULL,
        `description` TEXT, `equipment_id` INT(11) DEFAULT NULL,
        `reported_by` INT(11) DEFAULT NULL, `severity` VARCHAR(20) DEFAULT 'Low',
        `status` VARCHAR(20) DEFAULT 'Open', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'lab_incidents');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `lab_attendance` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `session_id` INT(11) NOT NULL,
        `student_id` INT(11) NOT NULL, `status` VARCHAR(20) DEFAULT 'Present',
        `check_in_time` TIME, `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'lab_attendance');

    // ── Staffs DB: Department Requests ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `department_requests` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `department` VARCHAR(150) NOT NULL,
        `requested_by` INT(11) NOT NULL, `request_type` VARCHAR(100),
        `description` TEXT, `priority` VARCHAR(20) DEFAULT 'Medium',
        `status` VARCHAR(20) DEFAULT 'Pending', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'department_requests');

    // ── Staffs DB: Library ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `library_books` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `title` VARCHAR(255) NOT NULL,
        `author` VARCHAR(255), `isbn` VARCHAR(50), `category` VARCHAR(100),
        `quantity` INT(11) DEFAULT 1, `available` INT(11) DEFAULT 1,
        `location` VARCHAR(255), `status` VARCHAR(20) DEFAULT 'Available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'library_books');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `library_borrowing` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `book_id` INT(11) NOT NULL,
        `member_id` INT(11) NOT NULL, `borrow_date` DATE NOT NULL,
        `due_date` DATE NOT NULL, `return_date` DATE DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'Borrowed', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_book` (`book_id`), KEY `idx_member` (`member_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'library_borrowing');

    // ── Staffs DB: Salary Structures ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `salary_structures` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `grade` VARCHAR(50) NOT NULL,
        `step` INT(11) DEFAULT 1, `amount` DECIMAL(12,2) NOT NULL,
        `description` TEXT, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'salary_structures');

    // ── Staffs DB: Document Templates ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `receipt_templates` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
        `content` LONGTEXT, `type` VARCHAR(50) DEFAULT 'receipt',
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'receipt_templates');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `document_templates` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
        `content` LONGTEXT, `type` VARCHAR(50) DEFAULT 'document',
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'document_templates');

    // ── Staffs DB: Other Referenced Tables ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `quality_assurance_reviews` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `review_type` VARCHAR(100) NOT NULL,
        `reviewer_id` INT(11) DEFAULT NULL, `department` VARCHAR(150),
        `findings` TEXT, `rating` INT(11) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Pending', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'quality_assurance_reviews');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `staff_audit_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` INT(11) NOT NULL,
        `action` VARCHAR(100) NOT NULL, `description` TEXT,
        `ip_address` VARCHAR(45), `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_staff` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'staff_audit_logs');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `clinical_training` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `department` VARCHAR(150), `supervisor_id` INT(11) DEFAULT NULL,
        `start_date` DATE, `end_date` DATE, `hours` DECIMAL(6,1) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'clinical_training');

    // ── Staffs DB: Sports Events ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `sports_events` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
        `sport_type` VARCHAR(100), `event_date` DATE,
        `location` VARCHAR(255), `description` TEXT,
        `status` VARCHAR(20) DEFAULT 'Upcoming', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'sports_events');

    // ── Staffs DB: Expenditure Records ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `expenditure_records` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `category` VARCHAR(150) NOT NULL,
        `amount` DECIMAL(12,2) NOT NULL, `description` TEXT,
        `recorded_by` INT(11) DEFAULT NULL, `expense_date` DATE,
        `status` VARCHAR(20) DEFAULT 'Recorded', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'expenditure_records');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `penalty_configurations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `penalty_type` VARCHAR(100) NOT NULL,
        `amount` DECIMAL(12,2) DEFAULT 0, `description` TEXT,
        `grace_period_days` INT(11) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'penalty_configurations');

    // ── Staffs DB: Institutional Framework ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `institutional_risks` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `risk_name` VARCHAR(255) NOT NULL,
        `description` TEXT, `likelihood` VARCHAR(20) DEFAULT 'Medium',
        `impact` VARCHAR(20) DEFAULT 'Medium', `mitigation` TEXT,
        `owner_id` INT(11) DEFAULT NULL, `status` VARCHAR(20) DEFAULT 'Open',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'institutional_risks');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `data_ownership_rules` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `table_name` VARCHAR(100) NOT NULL,
        `owner_role` VARCHAR(100) NOT NULL, `description` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'data_ownership_rules');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `director_performance_reviews` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `staff_id` INT(11) NOT NULL,
        `review_period` VARCHAR(50), `rating` DECIMAL(3,1) DEFAULT 0,
        `reviewer_id` INT(11) DEFAULT NULL, `comments` TEXT,
        `status` VARCHAR(20) DEFAULT 'Draft', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'director_performance_reviews');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `department_targets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `department` VARCHAR(150) NOT NULL,
        `target_name` VARCHAR(255) NOT NULL, `target_value` DECIMAL(12,2) DEFAULT 0,
        `current_value` DECIMAL(12,2) DEFAULT 0, `period` VARCHAR(50),
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'department_targets');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `director_departments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
        `code` VARCHAR(50), `head_id` INT(11) DEFAULT NULL,
        `description` TEXT, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'director_departments');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `smart_suggestions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `user_id` INT(11) NOT NULL,
        `suggestion_type` VARCHAR(50) DEFAULT 'action', `suggestion_text` TEXT NOT NULL,
        `priority` VARCHAR(20) DEFAULT 'medium', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'smart_suggestions');

    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `document_generation_log` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `document_type` VARCHAR(100) NOT NULL,
        `document_id` INT(11) DEFAULT 0, `generated_by` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_type` (`document_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'document_generation_log');

    // ── Staffs DB: Subscriptions ──
    addTable($staff, 'staffs', "CREATE TABLE IF NOT EXISTS `announcements` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `title` VARCHAR(255) NOT NULL,
        `content` TEXT, `target_audience` VARCHAR(100) DEFAULT 'all',
        `priority` VARCHAR(20) DEFAULT 'normal', `status` VARCHAR(20) DEFAULT 'published',
        `created_by` INT(11) DEFAULT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'announcements');

    // ── Students DB: Student Tables ──
    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_requests` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `request_type` VARCHAR(100) NOT NULL, `reason` TEXT,
        `status` VARCHAR(20) DEFAULT 'Pending', `reviewed_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_requests');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_messages` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `sender_type` VARCHAR(50) DEFAULT 'system', `sender_id` INT(11) DEFAULT NULL,
        `subject` VARCHAR(255), `message` TEXT NOT NULL,
        `is_read` TINYINT(1) DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_messages');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_downloads` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `document_name` VARCHAR(255) NOT NULL, `document_type` VARCHAR(100),
        `file_path` VARCHAR(500), `downloaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_downloads');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_profiles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `bio` TEXT, `avatar_url` VARCHAR(500),
        `emergency_contact` VARCHAR(255), `emergency_phone` VARCHAR(50),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        UNIQUE KEY `uk_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_profiles');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_warnings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `warning_type` VARCHAR(100) NOT NULL, `description` TEXT,
        `issued_by` INT(11) DEFAULT NULL, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_warnings');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_semester_gpa` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `academic_year` VARCHAR(20) NOT NULL, `semester` VARCHAR(50) NOT NULL,
        `gpa` DECIMAL(3,2) DEFAULT 0, `total_credits` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_semester_gpa');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_academic_records` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `academic_year` VARCHAR(20), `semester` VARCHAR(50),
        `course_code` VARCHAR(50), `grade` VARCHAR(5), `credits` DECIMAL(4,1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_academic_records');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_fee_accounts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `total_fees` DECIMAL(12,2) DEFAULT 0, `total_paid` DECIMAL(12,2) DEFAULT 0,
        `balance` DECIMAL(12,2) DEFAULT 0, `academic_year` VARCHAR(20),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_fee_accounts');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_attendance` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `date` DATE NOT NULL, `status` VARCHAR(20) DEFAULT 'Present',
        `course_code` VARCHAR(50), `remarks` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`), KEY `idx_date` (`date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_attendance');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_academic_profiles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `program` VARCHAR(150), `level` INT(11) DEFAULT 1,
        `gpa` DECIMAL(3,2) DEFAULT 0, `total_credits` INT(11) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        UNIQUE KEY `uk_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_academic_profiles');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `clinical_placements_students` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `placement_site` VARCHAR(255), `department` VARCHAR(150),
        `supervisor` VARCHAR(255), `start_date` DATE, `end_date` DATE,
        `hours` DECIMAL(6,1) DEFAULT 0, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'clinical_placements_students');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `student_fees` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `fee_type` VARCHAR(100) NOT NULL, `amount` DECIMAL(12,2) NOT NULL,
        `academic_year` VARCHAR(20), `semester` VARCHAR(50),
        `status` VARCHAR(20) DEFAULT 'Pending', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_fees');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `expenditure_records` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `category` VARCHAR(150) NOT NULL,
        `amount` DECIMAL(12,2) NOT NULL, `description` TEXT,
        `recorded_by` INT(11) DEFAULT NULL, `expense_date` DATE,
        `status` VARCHAR(20) DEFAULT 'Recorded', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'expenditure_records');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `penalty_configurations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `penalty_type` VARCHAR(100) NOT NULL,
        `amount` DECIMAL(12,2) DEFAULT 0, `description` TEXT,
        `grace_period_days` INT(11) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'penalty_configurations');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `subscription_deductions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `subscription_id` INT(11) NOT NULL,
        `amount` DECIMAL(12,2) NOT NULL, `deduction_date` DATE NOT NULL,
        `status` VARCHAR(20) DEFAULT 'Processed', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'subscription_deductions');

    addTable($stu, 'students', "CREATE TABLE IF NOT EXISTS `payment_subscriptions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `student_id` INT(11) NOT NULL,
        `subscription_type` VARCHAR(100) NOT NULL, `amount` DECIMAL(12,2) NOT NULL,
        `frequency` VARCHAR(50) DEFAULT 'Monthly', `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        KEY `idx_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'payment_subscriptions');

    // ── Website DB: Missing Tables ──
    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `volunteer_applications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255), `phone` VARCHAR(50),
        `role` VARCHAR(100), `interest` VARCHAR(255),
        `message` TEXT, `status` VARCHAR(20) DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'volunteer_applications');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `student_applications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255), `phone` VARCHAR(50),
        `program` VARCHAR(150), `course` VARCHAR(150),
        `status` VARCHAR(20) DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'student_applications');

    addTable($web, 'website', "CREATE TABLE IF NOT EXISTS `pages` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `title` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL, `content` LONGTEXT,
        `status` VARCHAR(20) DEFAULT 'published', `sort_order` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`),
        UNIQUE KEY `uk_slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'pages');

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
