<?php
/**
 * MASTER DATABASE REPAIR & AUDIT FIX
 * 
 * ISNM School Management System
 * Repairs all database schema issues across all databases.
 * MySQL 8.0 compatible (no ADD COLUMN IF NOT EXISTS).
 * PHP 8.2+ compatible.
 * 
 * SAFE TO RE-RUN.
 */

require_once __DIR__ . '/config/database.php';

$output = [];
$errors = [];

function log_fix($db, $msg, $type = 'FIX') {
    global $output;
    $output[] = "[$type] [$db] $msg";
    echo "[$type] [$db] $msg\n";
}

function log_error($db, $msg) {
    global $errors;
    $errors[] = "[ERROR] [$db] $msg";
    echo "[ERROR] [$db] $msg\n";
}

function safe_query($conn, $sql) {
    if (!$conn || $conn->connect_error) return false;
    try { return $conn->query($sql); } catch (Throwable $e) { return false; }
}

function column_exists($conn, $table, $column) {
    $r = safe_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($r && $r->num_rows > 0) { $r->free(); return true; }
    if ($r) $r->free();
    return false;
}

function table_exists($conn, $table) {
    $r = safe_query($conn, "SHOW TABLES LIKE '$table'");
    if ($r && $r->num_rows > 0) { $r->free(); return true; }
    if ($r) $r->free();
    return false;
}

function add_column_if_missing($conn, $db, $table, $column, $definition) {
    if (column_exists($conn, $table, $column)) {
        log_fix($db, "Column `$column` already exists in `$table` — skipped");
        return;
    }
    $r = safe_query($conn, "ALTER TABLE `$table` ADD COLUMN $definition");
    if ($r !== false) {
        log_fix($db, "ADD COLUMN `$column` TO `$table`");
    } else {
        log_error($db, "Failed to add `$column` to `$table`: " . $conn->error);
    }
}

function run_pk_auto_increment($conn, $db, $table) {
    $r = safe_query($conn, "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' AND COLUMN_KEY='PRI' AND EXTRA LIKE '%auto_increment%'");
    if ($r) {
        $row = $r->fetch_assoc();
        $r->free();
        if ($row && $row['cnt'] > 0) {
            log_fix($db, "`$table` already has AUTO_INCREMENT PRIMARY KEY — skipped");
            return;
        }
    }
    $r2 = safe_query($conn, "ALTER TABLE `$table` MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (id)");
    if ($r2 !== false) {
        log_fix($db, "Fixed AUTO_INCREMENT + PRIMARY KEY on `$table`");
    } else {
        log_error($db, "Failed to fix `$table`: " . $conn->error);
    }
}

echo "================================================================" . "\n";
echo "ISNM MASTER DATABASE REPAIR TOOL" . "\n";
echo "Repairing all databases..." . "\n";
echo "================================================================" . "\n\n";

// ============================================================
// 1. REPAIR STUDENTS DATABASE
// ============================================================
echo "--- REPAIRING STUDENTS DATABASE ---\n";
try {
    $conn = getStudentsConnection();
    if ($conn && !$conn->connect_error) {
        $dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
        log_fix('students_db', "Connected to $dbName on {$conn->server_info}");

        run_pk_auto_increment($conn, 'students_db', 'students');
        safe_query($conn, "ALTER TABLE students ADD UNIQUE INDEX idx_student_number_unique (student_number)");
        safe_query($conn, "DELETE FROM announcements WHERE id = 0");
        run_pk_auto_increment($conn, 'students_db', 'announcements');
        add_column_if_missing($conn, 'students_db', 'announcements', 'status', "`status` VARCHAR(50) DEFAULT 'published' AFTER `is_active`");
        add_column_if_missing($conn, 'students_db', 'announcements', 'view_count', "`view_count` INT(11) DEFAULT 0 AFTER `status`");
        add_column_if_missing($conn, 'students_db', 'announcements', 'attachment_path', "`attachment_path` VARCHAR(500) DEFAULT NULL AFTER `view_count`");
        add_column_if_missing($conn, 'students_db', 'announcements', 'posted_date', "`posted_date` DATETIME DEFAULT NULL AFTER `attachment_path`");

        $tables_with_issues = ['academic_registrar_activity_log','asset_categories','assets','bank_transactions','budget_records','budgets','bursar_chart_of_accounts','bursar_general_ledger','bursar_tax_filings','bursar_tax_periods','bursar_users','cash_book','chart_of_accounts','clinical_placements','clinical_placements_students'];
        foreach ($tables_with_issues as $tbl) {
            if (table_exists($conn, $tbl)) {
                run_pk_auto_increment($conn, 'students_db', $tbl);
            }
        }

        try { $conn->close(); } catch (Throwable $ignore) {}
    } else {
        log_error('students_db', 'Cannot connect — ' . ($conn ? $conn->connect_error : 'unknown'));
    }
} catch (Throwable $e) {
    log_error('students_db', 'Exception: ' . $e->getMessage());
}

// ============================================================
// 2. REPAIR STAFFS DATABASE
// ============================================================
echo "\n--- REPAIRING STAFFS DATABASE ---\n";
try {
    $conn = getStaffConnection();
    if ($conn && !$conn->connect_error) {
        $dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
        log_fix('staffs_db', "Connected to $dbName on {$conn->server_info}");

        // Add full_name to users
        add_column_if_missing($conn, 'staffs_db', 'users', 'full_name', "`full_name` VARCHAR(255) DEFAULT NULL AFTER `username`");
        safe_query($conn, "UPDATE users SET full_name = username WHERE full_name IS NULL OR full_name = ''");

        // Create messages table if not exists
        if (!table_exists($conn, 'messages')) {
            $r = safe_query($conn, "CREATE TABLE `messages` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `sender_id` INT(11) NOT NULL,
                `receiver_id` INT(11) NOT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `message` TEXT NOT NULL,
                `message_type` VARCHAR(50) DEFAULT 'text',
                `attachment_path` VARCHAR(500) DEFAULT NULL,
                `priority` VARCHAR(20) DEFAULT 'medium',
                `status` VARCHAR(20) DEFAULT 'sent',
                `parent_message_id` INT(11) DEFAULT NULL,
                `read_date` DATETIME DEFAULT NULL,
                `sent_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
                PRIMARY KEY (`id`),
                KEY `idx_messages_sender` (`sender_id`),
                KEY `idx_messages_receiver` (`receiver_id`),
                KEY `idx_messages_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            if ($r !== false) {
                log_fix('staffs_db', "Created `messages` table");
            } else {
                log_error('staffs_db', "Failed to create `messages`: " . $conn->error);
            }
        } else {
            log_fix('staffs_db', "`messages` table already exists — skipped");
        }

        // Add missing columns to announcements
        add_column_if_missing($conn, 'staffs_db', 'announcements', 'announcement_type', "`announcement_type` VARCHAR(50) DEFAULT 'general' AFTER `body`");
        add_column_if_missing($conn, 'staffs_db', 'announcements', 'attachment_path', "`attachment_path` VARCHAR(500) DEFAULT NULL AFTER `announcement_type`");
        add_column_if_missing($conn, 'staffs_db', 'announcements', 'expiry_date', "`expiry_date` DATE DEFAULT NULL AFTER `attachment_path`");
        add_column_if_missing($conn, 'staffs_db', 'announcements', 'view_count', "`view_count` INT(11) DEFAULT 0 AFTER `expiry_date`");
        add_column_if_missing($conn, 'staffs_db', 'announcements', 'status', "`status` VARCHAR(50) DEFAULT 'published' AFTER `view_count`");
        add_column_if_missing($conn, 'staffs_db', 'announcements', 'posted_date', "`posted_date` TIMESTAMP NULL AFTER `status`");
        safe_query($conn, "UPDATE announcements SET posted_date = created_at WHERE posted_date IS NULL");
        safe_query($conn, "UPDATE announcements SET `status` = 'published' WHERE `status` IS NULL OR `status` = ''");

        // Add missing columns to student_finance
        add_column_if_missing($conn, 'staffs_db', 'student_finance', 'tuition_fee', "`tuition_fee` DECIMAL(15,2) DEFAULT 0.00 AFTER `student_id`");
        add_column_if_missing($conn, 'staffs_db', 'student_finance', 'amount_paid', "`amount_paid` DECIMAL(15,2) DEFAULT 0.00 AFTER `tuition_fee`");
        add_column_if_missing($conn, 'staffs_db', 'student_finance', 'payment_method', "`payment_method` VARCHAR(50) DEFAULT NULL AFTER `amount_paid`");
        add_column_if_missing($conn, 'staffs_db', 'student_finance', 'payment_date', "`payment_date` DATE DEFAULT NULL AFTER `payment_method`");
        add_column_if_missing($conn, 'staffs_db', 'student_finance', 'payment_status', "`payment_status` VARCHAR(50) DEFAULT 'pending' AFTER `payment_date`");
        add_column_if_missing($conn, 'staffs_db', 'student_finance', 'semester', "`semester` VARCHAR(50) DEFAULT NULL AFTER `payment_status`");
        add_column_if_missing($conn, 'staffs_db', 'student_finance', 'academic_year', "`academic_year` VARCHAR(20) DEFAULT NULL AFTER `semester`");
        add_column_if_missing($conn, 'staffs_db', 'student_finance', 'receipt_number', "`receipt_number` VARCHAR(100) DEFAULT NULL AFTER `academic_year`");

        // Add rate to payroll_overtime
        add_column_if_missing($conn, 'staffs_db', 'payroll_overtime', 'rate', "`rate` DECIMAL(10,2) DEFAULT 0.00 AFTER `hours`");

        // Fix staff_roles role_name
        add_column_if_missing($conn, 'staffs_db', 'staff_roles', 'role_name', "`role_name` VARCHAR(100) DEFAULT NULL AFTER `name`");

        try { $conn->close(); } catch (Throwable $ignore) {}
    } else {
        log_error('staffs_db', 'Cannot connect — ' . ($conn ? $conn->connect_error : 'unknown'));
    }
} catch (Throwable $e) {
    log_error('staffs_db', 'Exception: ' . $e->getMessage());
}

// ============================================================
// 3. REPAIR ICT DATABASE
// ============================================================
echo "\n--- REPAIRING ICT DATABASE ---\n";
try {
    $conn = getICTConnection();
    if ($conn && !$conn->connect_error) {
        $dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
        log_fix('ict_db', "Connected to $dbName on {$conn->server_info}");
        run_pk_auto_increment($conn, 'ict_db', 'ict_assets');
        add_column_if_missing($conn, 'ict_db', 'ict_system_settings', 'updated_by', "`updated_by` INT(11) DEFAULT NULL AFTER `setting_value`");
        try { $conn->close(); } catch (Throwable $ignore) {}
    } else {
        log_error('ict_db', 'Cannot connect — ' . ($conn ? $conn->connect_error : 'unknown'));
    }
} catch (Throwable $e) {
    log_error('ict_db', 'Exception: ' . $e->getMessage());
}

// ============================================================
// 4. REPAIR WEBSITE DATABASE
// ============================================================
echo "\n--- REPAIRING WEBSITE DATABASE ---\n";
try {
    $conn = getWebsiteConnection();
    if ($conn && !$conn->connect_error) {
        $dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
        log_fix('website_db', "Connected to $dbName on {$conn->server_info}");
        try { $conn->close(); } catch (Throwable $ignore) {}
    } else {
        log_error('website_db', 'Cannot connect — ' . ($conn ? $conn->connect_error : 'unknown'));
    }
} catch (Throwable $e) {
    log_error('website_db', 'Exception: ' . $e->getMessage());
}

// ============================================================
// SUMMARY
// ============================================================
echo "\n================================================================" . "\n";
echo "REPAIR COMPLETE" . "\n";
echo "================================================================" . "\n";
echo "Total fixes applied: " . count($output) . "\n";
echo "Total errors: " . count($errors) . "\n\n";

if (!empty($errors)) {
    foreach ($errors as $e) {
        echo "  $e\n";
    }
}

// Write repair log
$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}

$log = "ISNM Database Repair Log - " . date('Y-m-d H:i:s') . "\n";
$log .= str_repeat('=', 80) . "\n\nFixes Applied:\n";
foreach ($output as $o) { $log .= "  $o\n"; }
if (!empty($errors)) {
    $log .= "\nErrors:\n";
    foreach ($errors as $e) { $log .= "  $e\n"; }
}

$logFile = $logDir . '/db_repair_log_' . date('Ymd_His') . '.txt';
file_put_contents($logFile, $log);
echo "\nLog written to: $logFile\n";
