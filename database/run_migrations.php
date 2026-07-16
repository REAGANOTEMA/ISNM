<?php
/**
 * ISNM Comprehensive Database Migration Script
 *
 * Creates missing tables and columns across all 4 databases:
 * - igangaschool_staffs
 * - igangaschool_students
 * - igangaschool_website
 * - igangaschool_ict
 *
 * Usage:
 *   Browser: http://localhost/ISNM/database/run_migrations.php
 *   CLI:     php database/run_migrations.php
 *   JSON:    http://localhost/ISNM/database/run_migrations.php?format=json
 *
 * Safety: Uses CREATE TABLE IF NOT EXISTS and checks information_schema
 * before ALTER TABLE. Never deletes existing data.
 */

require_once __DIR__ . '/../config/database.php';

$isCli  = (php_sapi_name() === 'cli');
$format = $_GET['format'] ?? ($isCli ? 'text' : 'html');

$results   = [];
$logFile   = __DIR__ . '/../logs/migration.log';
$logHandle = null;

if (!is_dir(dirname($logFile))) {
    @mkdir(dirname($logFile), 0755, true);
}
$logHandle = @fopen($logFile, 'a');

/* ──────────────────────────────────────────────
   Helpers
   ────────────────────────────────────────────── */

function logMsg(string $msg, bool $isError = false): void {
    global $logHandle;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . ($isError ? 'ERROR: ' : '') . $msg . PHP_EOL;
    if ($logHandle) {
        fwrite($logHandle, $line);
    }
    if ($isCli) {
        echo ($isError ? "\033[31m" : "\033[32m") . $msg . "\033[0m" . PHP_EOL;
    }
}

function out(string $msg, string $type = 'info'): void {
    global $results, $isCli;
    $colors = ['info' => '36m', 'success' => '32m', 'warn' => '33m', 'error' => '31m', 'skip' => '35m'];
    $c = $colors[$type] ?? '0m';
    $prefix = $type === 'success' ? 'OK' : ($type === 'warn' ? 'WARN' : ($type === 'error' ? 'FAIL' : ($type === 'skip' ? 'SKIP' : '...')));
    $entry = ['msg' => $msg, 'type' => $type];
    $results[] = $entry;

    if ($isCli) {
        echo "\033[{$c}[{$prefix}]\033[0m {$msg}" . PHP_EOL;
    }
    logMsg("[{$prefix}] {$msg}", $type === 'error');
}

/**
 * Execute a query, suppressing duplicate-column errors (1060) and
 * "table already exists" (1050) so the script is fully idempotent.
 */
function safeQuery(mysqli $conn, string $sql, string $label): bool {
    if (!$conn->query($sql)) {
        $code = $conn->errno;
        // 1050 = table already exists, 1060 = duplicate column name,
        // 1061 = duplicate key name, 1826 = duplicate index
        if (in_array($code, [1050, 1060, 1061, 1826], true)) {
            out("{$label}: already exists (skipped)", 'skip');
            return true;
        }
        out("{$label}: {$conn->error}", 'error');
        logMsg("SQL error on {$label}: {$conn->error} (code {$code})", true);
        return false;
    }
    out($label, 'success');
    return true;
}

/**
 * Check if a column exists in the given database/table.
 */
function columnExists(mysqli $conn, string $db, string $table, string $col): bool {
    $res = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = '{$db}' AND TABLE_NAME = '{$table}' AND COLUMN_NAME = '{$col}'"
    );
    if (!$res) return false;
    $row = $res->fetch_assoc();
    return ($row['cnt'] > 0);
}

/**
 * Check if a table exists in the given database.
 */
function tableExists(mysqli $conn, string $db, string $table): bool {
    $res = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = '{$db}' AND TABLE_NAME = '{$table}'"
    );
    if (!$res) return false;
    $row = $res->fetch_assoc();
    return ($row['cnt'] > 0);
}

/**
 * Check if an index exists.
 */
function indexExists(mysqli $conn, string $db, string $table, string $indexName): bool {
    $res = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = '{$db}' AND TABLE_NAME = '{$table}' AND INDEX_NAME = '{$indexName}'"
    );
    if (!$res) return false;
    $row = $res->fetch_assoc();
    return ($row['cnt'] > 0);
}

/**
 * Add a column to a table if it doesn't already exist.
 */
function addColumnIfMissing(mysqli $conn, string $db, string $table, string $col, string $def, string $after = ''): void {
    if (columnExists($conn, $db, $table, $col)) {
        return; // silent skip
    }
    $afterClause = $after !== '' ? " AFTER `{$after}`" : '';
    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}{$afterClause}";
    safeQuery($conn, $sql, "ALTER {$table} ADD `{$col}`");
}

/**
 * Create an index if it doesn't already exist.
 */
function createIndexIfMissing(mysqli $conn, string $db, string $table, string $indexName, string $columns, string $unique = ''): void {
    if (indexExists($conn, $db, $table, $indexName)) {
        return;
    }
    $sql = "CREATE {$unique}INDEX `{$indexName}` ON `{$table}` ({$columns})";
    safeQuery($conn, $sql, "INDEX {$table}.{$indexName}");
}

/* ──────────────────────────────────────────────
   Start
   ────────────────────────────────────────────── */

$startTime = microtime(true);
out("ISNM Database Migration started at " . date('Y-m-d H:i:s'), 'info');

$staffDb   = 'igangaschool_staffs';
$studentDb = 'igangaschool_students';
$websiteDb = 'igangaschool_website';
$ictDb     = 'igangaschool_ict';

$outcomes = [];

/* ============================================================
   1. STAFF DATABASE — igangaschool_staffs
   ============================================================ */
out("═══ Staff Database ({$staffDb}) ═══", 'info');
$staffConn = getStaffConnection();
if (!$staffConn) {
    out("FATAL: Cannot connect to staff database", 'error');
    $outcomes['staff'] = 'connection_failed';
} else {
    $outcomes['staff'] = 'ok';
    out("Connected to staff database", 'success');

    // ── 1a. Missing tables ──

    // notifications
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT DEFAULT NULL,
        `type` VARCHAR(30) DEFAULT 'info',
        `audience` VARCHAR(50) DEFAULT 'all',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_notifications_active` (`is_active`),
        KEY `idx_notifications_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE notifications');

    // notification_reads
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `notification_reads` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `notification_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `user_type` VARCHAR(30) DEFAULT 'staff',
        `read_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_notif_read` (`notification_id`, `user_id`, `user_type`),
        KEY `idx_notif_reads_user` (`user_id`, `user_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE notification_reads');

    // staff_inbox
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `staff_inbox` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `sender_id` INT(11) DEFAULT NULL,
        `sender_name` VARCHAR(200) DEFAULT NULL,
        `recipient_id` INT(11) NOT NULL,
        `recipient_name` VARCHAR(200) DEFAULT NULL,
        `subject` VARCHAR(300) NOT NULL,
        `message` TEXT DEFAULT NULL,
        `priority` ENUM('low','normal','high','urgent') DEFAULT 'normal',
        `is_read` TINYINT(1) DEFAULT 0,
        `is_deleted_sender` TINYINT(1) DEFAULT 0,
        `is_deleted_recipient` TINYINT(1) DEFAULT 0,
        `attachment_path` VARCHAR(500) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_inbox_recipient` (`recipient_id`, `is_read`),
        KEY `idx_inbox_sender` (`sender_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE staff_inbox');

    // system_modules
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `system_modules` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `module_name` VARCHAR(100) NOT NULL,
        `module_key` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `icon` VARCHAR(100) DEFAULT NULL,
        `url` VARCHAR(500) DEFAULT NULL,
        `parent_id` INT(11) DEFAULT NULL,
        `sort_order` INT(11) DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `permissions` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_module_key` (`module_key`),
        KEY `idx_modules_parent` (`parent_id`),
        KEY `idx_modules_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE system_modules');

    // system_settings
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_key` VARCHAR(100) NOT NULL,
        `setting_value` TEXT DEFAULT NULL,
        `setting_group` VARCHAR(50) DEFAULT 'general',
        `description` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE system_settings');

    // system_activity_logs
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `system_activity_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) DEFAULT NULL,
        `username` VARCHAR(100) DEFAULT NULL,
        `role` VARCHAR(100) DEFAULT NULL,
        `action` VARCHAR(200) NOT NULL,
        `entity_type` VARCHAR(100) DEFAULT NULL,
        `entity_id` INT(11) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` VARCHAR(500) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_sal_user` (`user_id`),
        KEY `idx_sal_action` (`action`),
        KEY `idx_sal_entity` (`entity_type`, `entity_id`),
        KEY `idx_sal_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE system_activity_logs');

    // password_changes
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `password_changes` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `user_type` VARCHAR(30) DEFAULT 'staff',
        `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_pwdchg_user` (`user_id`, `user_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE password_changes');

    // student_documents
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `student_documents` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `document_type` VARCHAR(50) NOT NULL,
        `document_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(500) NOT NULL,
        `file_size` BIGINT(20) DEFAULT 0,
        `uploaded_by` INT(11) DEFAULT NULL,
        `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_studocs_student` (`student_id`),
        KEY `idx_studocs_type` (`document_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_documents');

    // student_fee_tracking
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `student_fee_tracking` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `fee_type` VARCHAR(50) NOT NULL,
        `amount` DECIMAL(12,2) DEFAULT 0.00,
        `amount_paid` DECIMAL(12,2) DEFAULT 0.00,
        `balance` DECIMAL(12,2) GENERATED ALWAYS AS (`amount` - `amount_paid`) STORED,
        `academic_year` VARCHAR(20) DEFAULT NULL,
        `semester` VARCHAR(20) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `due_date` DATE DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_ft_student` (`student_id`),
        KEY `idx_ft_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_fee_tracking');

    // student_fee_assignments
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `student_fee_assignments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `fee_structure_id` INT(11) DEFAULT NULL,
        `assigned_amount` DECIMAL(12,2) DEFAULT 0.00,
        `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
        `status` VARCHAR(20) DEFAULT 'pending',
        `academic_year` VARCHAR(20) DEFAULT NULL,
        `semester` VARCHAR(20) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_sfa_student` (`student_id`),
        KEY `idx_sfa_structure` (`fee_structure_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_fee_assignments');

    // student_invoices
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `student_invoices` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `invoice_number` VARCHAR(50) NOT NULL,
        `net_amount` DECIMAL(12,2) DEFAULT 0.00,
        `amount_paid` DECIMAL(12,2) DEFAULT 0.00,
        `status` VARCHAR(20) DEFAULT 'pending',
        `due_date` DATE DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_inv_number` (`invoice_number`),
        KEY `idx_inv_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_invoices');

    // student_notifications
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `student_notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT DEFAULT NULL,
        `type` VARCHAR(30) DEFAULT 'info',
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_studnotif_student` (`student_id`, `is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_notifications');

    // payments
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `payment_reference` VARCHAR(50) NOT NULL,
        `student_id` INT(11) NOT NULL,
        `amount_received` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `payment_method` VARCHAR(50) DEFAULT 'Cash',
        `transaction_ref` VARCHAR(100) DEFAULT NULL,
        `slip_number` VARCHAR(100) DEFAULT NULL,
        `payment_date` DATE DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'completed',
        `received_by` INT(11) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_pay_ref` (`payment_reference`),
        KEY `idx_pay_student` (`student_id`),
        KEY `idx_pay_date` (`payment_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE payments');

    // fee_structures
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `fee_structures` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `fee_name` VARCHAR(200) NOT NULL,
        `fee_type` VARCHAR(50) DEFAULT NULL,
        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `program_id` INT(11) DEFAULT NULL,
        `academic_year` VARCHAR(20) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_fs_program` (`program_id`),
        KEY `idx_fs_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE fee_structures');

    // expenses
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `expenses` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `expense_title` VARCHAR(255) DEFAULT NULL,
        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `category` VARCHAR(100) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `expense_date` DATE DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'approved',
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_exp_date` (`expense_date`),
        KEY `idx_exp_category` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE expenses');

    // bank_reconciliation
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `bank_reconciliation` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `book_balance` DECIMAL(15,2) DEFAULT 0.00,
        `bank_balance` DECIMAL(15,2) DEFAULT 0.00,
        `difference` DECIMAL(15,2) DEFAULT 0.00,
        `reconciliation_date` DATE DEFAULT NULL,
        `reconciled_by` INT(11) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE bank_reconciliation');

    // student_welfare_cases
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `student_welfare_cases` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `case_type` VARCHAR(50) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'open',
        `reported_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_welfare_student` (`student_id`),
        KEY `idx_welfare_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_welfare_cases');

    // student_welfare_notes
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `student_welfare_notes` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `case_id` INT(11) NOT NULL,
        `note` TEXT DEFAULT NULL,
        `added_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_wn_case` (`case_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_welfare_notes');

    // security_incidents
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `security_incidents` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `incident_type` VARCHAR(50) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `severity` VARCHAR(20) DEFAULT 'medium',
        `status` VARCHAR(20) DEFAULT 'open',
        `reported_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_secinc_status` (`status`),
        KEY `idx_secinc_severity` (`severity`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE security_incidents');

    // security_incident_notes
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `security_incident_notes` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `incident_id` INT(11) NOT NULL,
        `note` TEXT DEFAULT NULL,
        `added_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_sin_incident` (`incident_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE security_incident_notes');

    // access_control_logs (safeQuery with IF NOT EXISTS handles existing)
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `access_control_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `person_name` VARCHAR(200) DEFAULT NULL,
        `person_type` VARCHAR(50) DEFAULT 'Visitor',
        `access_point` VARCHAR(100) DEFAULT NULL,
        `access_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `access_type` VARCHAR(20) DEFAULT 'Entry',
        `badge_number` VARCHAR(50) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_acl_person` (`person_name`),
        KEY `idx_acl_time` (`access_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE access_control_logs');

    // ict_assets
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `ict_assets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `asset_name` VARCHAR(255) NOT NULL,
        `asset_tag` VARCHAR(50) NOT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `status` VARCHAR(30) DEFAULT 'active',
        `purchase_date` DATE DEFAULT NULL,
        `purchase_cost` DECIMAL(12,2) DEFAULT 0.00,
        `assigned_to` INT(11) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_ict_tag` (`asset_tag`),
        KEY `idx_ict_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_assets');

    // ict_asset_categories
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `ict_asset_categories` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `category_name` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_asset_categories');

    // ict_server_status
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `ict_server_status` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `server_name` VARCHAR(200) NOT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `status` VARCHAR(30) DEFAULT 'unknown',
        `cpu_usage` DECIMAL(5,2) DEFAULT 0.00,
        `memory_usage` DECIMAL(5,2) DEFAULT 0.00,
        `disk_usage` DECIMAL(5,2) DEFAULT 0.00,
        `last_checked` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_server_status');

    // ict_backup_logs
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `ict_backup_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `backup_type` VARCHAR(50) NOT NULL,
        `file_name` VARCHAR(500) DEFAULT NULL,
        `file_size` BIGINT(20) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'pending',
        `started_at` TIMESTAMP NULL DEFAULT NULL,
        `completed_at` TIMESTAMP NULL DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_ibl_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_backup_logs');

    // ict_tickets
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `ict_tickets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `priority` VARCHAR(20) DEFAULT 'normal',
        `status` VARCHAR(20) DEFAULT 'open',
        `assigned_to` INT(11) DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_it_status` (`status`),
        KEY `idx_it_priority` (`priority`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_tickets');

    // ict_audit_logs
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `ict_audit_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) DEFAULT NULL,
        `action` VARCHAR(100) NOT NULL,
        `details` TEXT DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_ial_user` (`user_id`),
        KEY `idx_ial_action` (`action`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_audit_logs');

    // lab_equipment
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `lab_equipment` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `equipment_name` VARCHAR(255) NOT NULL,
        `equipment_code` VARCHAR(50) NOT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `quantity` INT(11) DEFAULT 1,
        `location` VARCHAR(255) DEFAULT NULL,
        `status` VARCHAR(30) DEFAULT 'available',
        `last_maintenance` DATE DEFAULT NULL,
        `next_maintenance` DATE DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_le_code` (`equipment_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE lab_equipment');

    // lab_bookings
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `lab_bookings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `lab_name` VARCHAR(200) NOT NULL,
        `booked_by` INT(11) NOT NULL,
        `booking_date` DATE NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `purpose` TEXT DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_lb_date` (`booking_date`),
        KEY `idx_lb_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE lab_bookings');

    // lab_attendance
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `lab_attendance` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `lab_name` VARCHAR(200) NOT NULL,
        `attendance_date` DATE NOT NULL,
        `status` VARCHAR(20) DEFAULT 'present',
        `marked_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_la_student` (`student_id`),
        KEY `idx_la_date` (`attendance_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE lab_attendance');

    // payroll_employees
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `payroll_employees` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) NOT NULL,
        `basic_salary` DECIMAL(12,2) DEFAULT 0.00,
        `housing_allowance` DECIMAL(12,2) DEFAULT 0.00,
        `transport_allowance` DECIMAL(12,2) DEFAULT 0.00,
        `status` VARCHAR(20) DEFAULT 'active',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_pe_staff` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE payroll_employees');

    // payroll_runs
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `payroll_runs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `period` VARCHAR(50) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `total_gross` DECIMAL(15,2) DEFAULT 0.00,
        `total_paye` DECIMAL(15,2) DEFAULT 0.00,
        `total_nssf` DECIMAL(15,2) DEFAULT 0.00,
        `total_deductions` DECIMAL(15,2) DEFAULT 0.00,
        `total_net` DECIMAL(15,2) DEFAULT 0.00,
        `run_date` DATE DEFAULT NULL,
        `status` ENUM('draft','approved','processed','paid','completed','processing') DEFAULT 'draft',
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_pr_status` (`status`),
        KEY `idx_pr_period` (`period`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE payroll_runs');

    // payroll_details
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `payroll_details` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `payroll_run_id` INT(11) NOT NULL,
        `staff_id` INT(11) NOT NULL,
        `basic_salary` DECIMAL(12,2) DEFAULT 0.00,
        `housing_allowance` DECIMAL(12,2) DEFAULT 0.00,
        `transport_allowance` DECIMAL(12,2) DEFAULT 0.00,
        `gross_pay` DECIMAL(12,2) DEFAULT 0.00,
        `paye_tax` DECIMAL(12,2) DEFAULT 0.00,
        `nssf_employee` DECIMAL(12,2) DEFAULT 0.00,
        `nssf_employer` DECIMAL(12,2) DEFAULT 0.00,
        `other_deductions` DECIMAL(12,2) DEFAULT 0.00,
        `net_pay` DECIMAL(12,2) DEFAULT 0.00,
        `payment_status` VARCHAR(20) DEFAULT 'pending',
        `status` VARCHAR(20) DEFAULT 'calculated',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_pd_run` (`payroll_run_id`),
        KEY `idx_pd_staff` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE payroll_details');

    // salary_structures
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `salary_structures` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) DEFAULT NULL,
        `base_salary` DECIMAL(12,2) DEFAULT 0.00,
        `housing_allowance` DECIMAL(12,2) DEFAULT 0.00,
        `transport_allowance` DECIMAL(12,2) DEFAULT 0.00,
        `effective_date` DATE DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_ss_staff_id` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE salary_structures');

    // payroll_settings
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `payroll_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_key` VARCHAR(100) NOT NULL,
        `setting_value` TEXT DEFAULT NULL,
        `updated_by` INT(11) DEFAULT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_ps_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE payroll_settings');

    // payroll_approvals
    safeQuery($staffConn, "CREATE TABLE IF NOT EXISTS `payroll_approvals` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `payroll_run_id` INT(11) NOT NULL,
        `level` ENUM('HR','PayrollOfficer','Bursar','DirectorFinance','CEO') NOT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `approved_by` INT(11) DEFAULT NULL,
        `approved_at` TIMESTAMP NULL DEFAULT NULL,
        `comments` TEXT DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_pa_run` (`payroll_run_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE payroll_approvals');

    // ── 1b. Add missing columns to existing staff table ──
    out("── Adding missing columns to staff table ──", 'info');
    addColumnIfMissing($staffConn, $staffDb, 'staff', 'address', 'TEXT DEFAULT NULL');
    addColumnIfMissing($staffConn, $staffDb, 'staff', 'emergency_contact', 'VARCHAR(200) DEFAULT NULL');
    addColumnIfMissing($staffConn, $staffDb, 'staff', 'emergency_phone', 'VARCHAR(20) DEFAULT NULL');
    addColumnIfMissing($staffConn, $staffDb, 'staff', 'national_id', 'VARCHAR(50) DEFAULT NULL');
    addColumnIfMissing($staffConn, $staffDb, 'staff', 'qualifications', 'TEXT DEFAULT NULL');

    // ── 1c. staff_roles: add role_description if missing ──
    out("── Adding missing columns to staff_roles table ──", 'info');
    addColumnIfMissing($staffConn, $staffDb, 'staff_roles', 'role_description', 'TEXT DEFAULT NULL');

    // ── 1d. approval_requests: add rejection_reason if missing ──
    out("── Adding missing columns to approval_requests table ──", 'info');
    addColumnIfMissing($staffConn, $staffDb, 'approval_requests', 'rejection_reason', 'TEXT DEFAULT NULL');

    // ── 1e. System modules seed data ──
    out("── Seeding system_modules data ──", 'info');
    $modulesData = [
        ['Dashboard', 'dashboard', 'Main dashboard', 'fas fa-tachometer-alt', '/dashboards/', NULL, 1],
        ['Staff Management', 'staff', 'Manage staff records', 'fas fa-users', '/admin_panel/staff_management.php', NULL, 2],
        ['Student Management', 'students', 'Manage student records', 'fas fa-user-graduate', '/admin_panel/student_management.php', NULL, 3],
        ['Academic', 'academic', 'Academic affairs', 'fas fa-graduation-cap', '/admin_panel/academic/', NULL, 4],
        ['Finance', 'finance', 'Financial management', 'fas fa-money-bill-wave', '/admin_panel/finance/', NULL, 5],
        ['Bursar', 'bursar', 'Bursar operations', 'fas fa-cash-register', '/admin_panel/bursar/', NULL, 6],
        ['HR Module', 'hr', 'Human resources', 'fas fa-user-tie', '/admin_panel/hr/', NULL, 7],
        ['ICT Module', 'ict', 'ICT department', 'fas fa-laptop', '/admin_panel/ict/', NULL, 8],
        ['Library', 'library', 'Library management', 'fas fa-book', '/admin_panel/library/', NULL, 9],
        ['Reports', 'reports', 'System reports', 'fas fa-chart-bar', '/admin_panel/reports/', NULL, 10],
        ['Settings', 'settings', 'System settings', 'fas fa-cog', '/settings.php', NULL, 11],
        ['Approvals', 'approvals', 'Approval workflows', 'fas fa-check-double', '/admin_panel/approvals/', NULL, 12],
        ['Security', 'security', 'Security & access control', 'fas fa-shield-alt', '/admin_panel/security/', NULL, 13],
        ['Notifications', 'notifications', 'Notification center', 'fas fa-bell', '/notifications.php', NULL, 14],
        ['Admissions', 'admissions', 'Student admissions', 'fas fa-user-plus', '/admin_panel/admissions/', NULL, 15],
        ['Payroll', 'payroll', 'Payroll management', 'fas fa-money-check', '/admin_panel/payroll/', NULL, 16],
        ['Website CMS', 'cms', 'Website content', 'fas fa-globe', '/cms/', NULL, 17],
    ];

    foreach ($modulesData as $m) {
        $check = $staffConn->query("SELECT COUNT(*) AS cnt FROM system_modules WHERE module_key = '{$staffConn->real_escape_string($m[1])}'");
        if ($check && $check->fetch_assoc()['cnt'] == 0) {
            $esc = function($v) use ($staffConn) { return $staffConn->real_escape_string($v); };
            $sql = "INSERT INTO system_modules (module_name, module_key, description, icon, url, parent_id, sort_order)
                    VALUES ('{$esc($m[0])}', '{$esc($m[1])}', '{$esc($m[2])}', '{$esc($m[3])}', '{$esc($m[4])}', " . ($m[5] === NULL ? 'NULL' : "'{$esc($m[5])}'") . ", {$m[6]})";
            $staffConn->query($sql);
        }
    }
    out("system_modules seed data: applied", 'success');

    // ── 1f. Indexes for staff database ──
    out("── Creating performance indexes (staff DB) ──", 'info');
    createIndexIfMissing($staffConn, $staffDb, 'payments', 'idx_payments_student', 'student_id');
    createIndexIfMissing($staffConn, $staffDb, 'payments', 'idx_payments_ref', 'payment_reference');
    createIndexIfMissing($staffConn, $staffDb, 'student_fee_tracking', 'idx_sft_student', 'student_id');
    createIndexIfMissing($staffConn, $staffDb, 'student_invoices', 'idx_si_student', 'student_id');
    createIndexIfMissing($staffConn, $staffDb, 'notifications', 'idx_notif_user_read', 'id', '');

    $staffConn->close();
}

/* ============================================================
   2. STUDENT DATABASE — igangaschool_students
   ============================================================ */
out("═══ Student Database ({$studentDb}) ═══", 'info');
$studentConn = getStudentsConnection();
if (!$studentConn) {
    out("FATAL: Cannot connect to student database", 'error');
    $outcomes['student'] = 'connection_failed';
} else {
    $outcomes['student'] = 'ok';
    out("Connected to student database", 'success');

    // ── 2a. Missing tables ──

    // payments
    safeQuery($studentConn, "CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `payment_reference` VARCHAR(50) NOT NULL,
        `student_id` INT(11) NOT NULL,
        `amount_received` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `payment_method` VARCHAR(50) DEFAULT 'Cash',
        `transaction_ref` VARCHAR(100) DEFAULT NULL,
        `slip_number` VARCHAR(100) DEFAULT NULL,
        `payment_date` DATE DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'completed',
        `received_by` INT(11) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_pay_ref` (`payment_reference`),
        KEY `idx_pay_student` (`student_id`),
        KEY `idx_pay_date` (`payment_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE payments (students)');

    // fee_structures
    safeQuery($studentConn, "CREATE TABLE IF NOT EXISTS `fee_structures` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `fee_name` VARCHAR(200) NOT NULL,
        `fee_type` VARCHAR(50) DEFAULT NULL,
        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `program_id` INT(11) DEFAULT NULL,
        `academic_year` VARCHAR(20) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_fs_program` (`program_id`),
        KEY `idx_fs_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE fee_structures (students)');

    // student_fee_tracking
    safeQuery($studentConn, "CREATE TABLE IF NOT EXISTS `student_fee_tracking` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `fee_type` VARCHAR(50) NOT NULL,
        `amount` DECIMAL(12,2) DEFAULT 0.00,
        `amount_paid` DECIMAL(12,2) DEFAULT 0.00,
        `balance` DECIMAL(12,2) GENERATED ALWAYS AS (`amount` - `amount_paid`) STORED,
        `academic_year` VARCHAR(20) DEFAULT NULL,
        `semester` VARCHAR(20) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `due_date` DATE DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_ft_student` (`student_id`),
        KEY `idx_ft_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_fee_tracking (students)');

    // student_fee_assignments
    safeQuery($studentConn, "CREATE TABLE IF NOT EXISTS `student_fee_assignments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `fee_structure_id` INT(11) DEFAULT NULL,
        `assigned_amount` DECIMAL(12,2) DEFAULT 0.00,
        `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
        `status` VARCHAR(20) DEFAULT 'pending',
        `academic_year` VARCHAR(20) DEFAULT NULL,
        `semester` VARCHAR(20) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_sfa_student` (`student_id`),
        KEY `idx_sfa_structure` (`fee_structure_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_fee_assignments (students)');

    // student_invoices
    safeQuery($studentConn, "CREATE TABLE IF NOT EXISTS `student_invoices` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `invoice_number` VARCHAR(50) NOT NULL,
        `net_amount` DECIMAL(12,2) DEFAULT 0.00,
        `amount_paid` DECIMAL(12,2) DEFAULT 0.00,
        `status` VARCHAR(20) DEFAULT 'pending',
        `due_date` DATE DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_inv_number` (`invoice_number`),
        KEY `idx_inv_student` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_invoices (students)');

    // student_notifications
    safeQuery($studentConn, "CREATE TABLE IF NOT EXISTS `student_notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT DEFAULT NULL,
        `type` VARCHAR(30) DEFAULT 'info',
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_studnotif_student` (`student_id`, `is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_notifications (students)');

    // student_documents
    safeQuery($studentConn, "CREATE TABLE IF NOT EXISTS `student_documents` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `document_type` VARCHAR(50) NOT NULL,
        `document_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(500) NOT NULL,
        `file_size` BIGINT(20) DEFAULT 0,
        `uploaded_by` INT(11) DEFAULT NULL,
        `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_studocs_student` (`student_id`),
        KEY `idx_studocs_type` (`document_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_documents (students)');

    // news
    safeQuery($studentConn, "CREATE TABLE IF NOT EXISTS `news` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(300) NOT NULL,
        `slug` VARCHAR(300) NOT NULL,
        `summary` TEXT DEFAULT NULL,
        `content` LONGTEXT DEFAULT NULL,
        `featured_image` VARCHAR(500) DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `tags` TEXT DEFAULT NULL,
        `status` ENUM('draft','published','archived') DEFAULT 'draft',
        `is_featured` TINYINT(1) DEFAULT 0,
        `published_at` TIMESTAMP NULL DEFAULT NULL,
        `scheduled_at` TIMESTAMP NULL DEFAULT NULL,
        `archived_at` TIMESTAMP NULL DEFAULT NULL,
        `author_id` INT(11) DEFAULT NULL,
        `author_name` VARCHAR(200) DEFAULT NULL,
        `views` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_news_slug` (`slug`),
        KEY `idx_news_status` (`status`, `published_at`),
        KEY `idx_news_cat` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE news (students)');

    // ── 2b. Add missing columns to students table ──
    out("── Adding missing columns to students table ──", 'info');
    addColumnIfMissing($studentConn, $studentDb, 'students', 'guardian_email', 'VARCHAR(100) DEFAULT NULL');
    addColumnIfMissing($studentConn, $studentDb, 'students', 'emergency_phone', 'VARCHAR(20) DEFAULT NULL');
    addColumnIfMissing($studentConn, $studentDb, 'students', 'profile_photo', 'VARCHAR(500) DEFAULT NULL');
    addColumnIfMissing($studentConn, $studentDb, 'students', 'national_id_number', 'VARCHAR(50) DEFAULT NULL');

    // ── 2c. Indexes ──
    out("── Creating performance indexes (student DB) ──", 'info');
    createIndexIfMissing($studentConn, $studentDb, 'payments', 'idx_payments_student', 'student_id');
    createIndexIfMissing($studentConn, $studentDb, 'payments', 'idx_payments_ref', 'payment_reference');
    createIndexIfMissing($studentConn, $studentDb, 'student_fee_tracking', 'idx_sft_student', 'student_id');
    createIndexIfMissing($studentConn, $studentDb, 'student_invoices', 'idx_si_student', 'student_id');
    createIndexIfMissing($studentConn, $studentDb, 'news', 'idx_news_status_pub', 'status, published_at');

    $studentConn->close();
}

/* ============================================================
   3. WEBSITE DATABASE — igangaschool_website
   ============================================================ */
out("═══ Website Database ({$websiteDb}) ═══", 'info');
$websiteConn = getWebsiteConnection();
if (!$websiteConn) {
    out("FATAL: Cannot connect to website database", 'error');
    $outcomes['website'] = 'connection_failed';
} else {
    $outcomes['website'] = 'ok';
    out("Connected to website database", 'success');

    // ── 3a. Missing tables ──

    // news
    safeQuery($websiteConn, "CREATE TABLE IF NOT EXISTS `news` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(300) NOT NULL,
        `slug` VARCHAR(300) NOT NULL,
        `summary` TEXT DEFAULT NULL,
        `content` LONGTEXT DEFAULT NULL,
        `featured_image` VARCHAR(500) DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `tags` TEXT DEFAULT NULL,
        `status` ENUM('draft','published','archived') DEFAULT 'draft',
        `is_featured` TINYINT(1) DEFAULT 0,
        `published_at` TIMESTAMP NULL DEFAULT NULL,
        `scheduled_at` TIMESTAMP NULL DEFAULT NULL,
        `archived_at` TIMESTAMP NULL DEFAULT NULL,
        `author_id` INT(11) DEFAULT NULL,
        `author_name` VARCHAR(200) DEFAULT NULL,
        `views` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_news_slug` (`slug`),
        KEY `idx_news_status` (`status`, `published_at`),
        KEY `idx_news_cat` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE news (website)');

    // news_categories
    safeQuery($websiteConn, "CREATE TABLE IF NOT EXISTS `news_categories` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `sort_order` INT(11) DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_nc_slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE news_categories (website)');

    // notifications
    safeQuery($websiteConn, "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT DEFAULT NULL,
        `type` VARCHAR(30) DEFAULT 'info',
        `audience` VARCHAR(50) DEFAULT 'all',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_notifications_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE notifications (website)');

    // notification_reads
    safeQuery($websiteConn, "CREATE TABLE IF NOT EXISTS `notification_reads` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `notification_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `user_type` VARCHAR(30) DEFAULT 'staff',
        `read_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_notif_read` (`notification_id`, `user_id`, `user_type`),
        KEY `idx_notif_reads_user` (`user_id`, `user_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE notification_reads (website)');

    // contact_submissions
    safeQuery($websiteConn, "CREATE TABLE IF NOT EXISTS `contact_submissions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(200) NOT NULL,
        `email` VARCHAR(200) NOT NULL,
        `phone` VARCHAR(30) DEFAULT NULL,
        `department` VARCHAR(100) DEFAULT NULL,
        `subject` VARCHAR(300) DEFAULT NULL,
        `message` TEXT NOT NULL,
        `status` VARCHAR(20) DEFAULT 'new',
        `responded_by` INT(11) DEFAULT NULL,
        `responded_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_cs_status` (`status`),
        KEY `idx_cs_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE contact_submissions (website)');

    // volunteer_applications
    safeQuery($websiteConn, "CREATE TABLE IF NOT EXISTS `volunteer_applications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `full_name` VARCHAR(200) NOT NULL,
        `email` VARCHAR(200) NOT NULL,
        `phone` VARCHAR(30) DEFAULT NULL,
        `skills` TEXT DEFAULT NULL,
        `availability` VARCHAR(100) DEFAULT NULL,
        `motivation` TEXT DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'pending',
        `reviewed_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_va_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE volunteer_applications (website)');

    // donations
    safeQuery($websiteConn, "CREATE TABLE IF NOT EXISTS `donations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `donor_name` VARCHAR(200) NOT NULL,
        `donor_email` VARCHAR(200) DEFAULT NULL,
        `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `payment_method` VARCHAR(50) DEFAULT NULL,
        `reference_number` VARCHAR(100) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'completed',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_don_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE donations (website)');

    // student_profiles
    safeQuery($websiteConn, "CREATE TABLE IF NOT EXISTS `student_profiles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `student_number` VARCHAR(50) NOT NULL,
        `full_name` VARCHAR(200) NOT NULL,
        `email` VARCHAR(200) DEFAULT NULL,
        `phone` VARCHAR(30) DEFAULT NULL,
        `program` VARCHAR(100) DEFAULT NULL,
        `year` INT(11) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'active',
        `photo_url` VARCHAR(500) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_sp_number` (`student_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE student_profiles (website)');

    // ── 3b. Indexes ──
    out("── Creating performance indexes (website DB) ──", 'info');
    createIndexIfMissing($websiteConn, $websiteDb, 'news', 'idx_news_status_pub', 'status, published_at');
    createIndexIfMissing($websiteConn, $websiteDb, 'notifications', 'idx_notif_active', 'is_active');

    $websiteConn->close();
}

/* ============================================================
   4. ICT DATABASE — igangaschool_ict
   ============================================================ */
out("═══ ICT Database ({$ictDb}) ═══", 'info');
$ictConn = getICTConnection();
if (!$ictConn) {
    out("FATAL: Cannot connect to ICT database", 'error');
    $outcomes['ict'] = 'connection_failed';
} else {
    $outcomes['ict'] = 'ok';
    out("Connected to ICT database", 'success');

    // ── 4a. Missing tables ──

    // ict_assets
    safeQuery($ictConn, "CREATE TABLE IF NOT EXISTS `ict_assets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `asset_name` VARCHAR(255) NOT NULL,
        `asset_tag` VARCHAR(50) NOT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `status` VARCHAR(30) DEFAULT 'active',
        `purchase_date` DATE DEFAULT NULL,
        `purchase_cost` DECIMAL(12,2) DEFAULT 0.00,
        `assigned_to` INT(11) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_ict_tag` (`asset_tag`),
        KEY `idx_ict_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_assets (ict)');

    // ict_asset_categories
    safeQuery($ictConn, "CREATE TABLE IF NOT EXISTS `ict_asset_categories` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `category_name` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_asset_categories (ict)');

    // ict_server_status
    safeQuery($ictConn, "CREATE TABLE IF NOT EXISTS `ict_server_status` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `server_name` VARCHAR(200) NOT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `status` VARCHAR(30) DEFAULT 'unknown',
        `cpu_usage` DECIMAL(5,2) DEFAULT 0.00,
        `memory_usage` DECIMAL(5,2) DEFAULT 0.00,
        `disk_usage` DECIMAL(5,2) DEFAULT 0.00,
        `last_checked` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_server_status (ict)');

    // ict_backup_logs
    safeQuery($ictConn, "CREATE TABLE IF NOT EXISTS `ict_backup_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `backup_type` VARCHAR(50) NOT NULL,
        `file_name` VARCHAR(500) DEFAULT NULL,
        `file_size` BIGINT(20) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'pending',
        `started_at` TIMESTAMP NULL DEFAULT NULL,
        `completed_at` TIMESTAMP NULL DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_ibl_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_backup_logs (ict)');

    // ict_tickets
    safeQuery($ictConn, "CREATE TABLE IF NOT EXISTS `ict_tickets` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `priority` VARCHAR(20) DEFAULT 'normal',
        `status` VARCHAR(20) DEFAULT 'open',
        `assigned_to` INT(11) DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_it_status` (`status`),
        KEY `idx_it_priority` (`priority`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_tickets (ict)');

    // ict_audit_logs
    safeQuery($ictConn, "CREATE TABLE IF NOT EXISTS `ict_audit_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) DEFAULT NULL,
        `action` VARCHAR(100) NOT NULL,
        `details` TEXT DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_ial_user` (`user_id`),
        KEY `idx_ial_action` (`action`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE ict_audit_logs (ict)');

    // cybersecurity_incidents
    safeQuery($ictConn, "CREATE TABLE IF NOT EXISTS `cybersecurity_incidents` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `incident_type` VARCHAR(50) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `severity` VARCHAR(20) DEFAULT 'medium',
        `status` VARCHAR(20) DEFAULT 'open',
        `reported_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_csi_status` (`status`),
        KEY `idx_csi_severity` (`severity`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", 'TABLE cybersecurity_incidents (ict)');

    // Seed ict_asset_categories if empty
    $chk = $ictConn->query("SELECT COUNT(*) AS cnt FROM ict_asset_categories");
    if ($chk && $chk->fetch_assoc()['cnt'] == 0) {
        $categories = [
            ['Computers', 'Desktop and laptop computers'],
            ['Printers', 'Printers, scanners, copiers'],
            ['Network', 'Switches, routers, access points'],
            ['Projectors', 'Projectors and displays'],
            ['Peripherals', 'Keyboards, mice, monitors, cables'],
            ['Servers', 'Server hardware and components'],
            ['Mobile Devices', 'Tablets, phones'],
            ['Software', 'Software licenses'],
        ];
        foreach ($categories as $cat) {
            $ictConn->query("INSERT INTO ict_asset_categories (category_name, description) VALUES ('{$ictConn->real_escape_string($cat[0])}', '{$ictConn->real_escape_string($cat[1])}')");
        }
        out("ict_asset_categories: seeded with default categories", 'success');
    }

    $ictConn->close();
}

/* ============================================================
   Summary
   ============================================================ */

$elapsed = round(microtime(true) - $startTime, 2);
$total   = count($results);
$success = count(array_filter($results, fn($r) => $r['type'] === 'success'));
$skipped = count(array_filter($results, fn($r) => $r['type'] === 'skip'));
$errors  = count(array_filter($results, fn($r) => $r['type'] === 'error'));

$summary = [
    'timestamp'        => date('Y-m-d H:i:s'),
    'elapsed_seconds'  => $elapsed,
    'total_operations' => $total,
    'created'          => $success,
    'skipped'          => $skipped,
    'errors'           => $errors,
    'databases'        => $outcomes,
];

out('', 'info');
out("════════════════════════════════════════", 'info');
out("Migration complete in {$elapsed}s", 'info');
out("Created: {$success} | Skipped (exists): {$skipped} | Errors: {$errors}", $errors > 0 ? 'error' : 'success');
out("════════════════════════════════════════", 'info');

if ($logHandle) {
    fclose($logHandle);
}

/* ── Output ── */

if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => $errors > 0 ? 'completed_with_errors' : 'success',
        'summary' => $summary,
        'results' => $results,
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($isCli) {
    echo PHP_EOL . "Summary: " . json_encode($summary, JSON_PRETTY_PRINT) . PHP_EOL;
    exit;
}

// HTML output for browser
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISNM Database Migration</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin-bottom: 8px; color: #38bdf8; }
        .meta { color: #94a3b8; font-size: 0.85rem; margin-bottom: 20px; }
        .summary-box { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 16px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; }
        .stat { text-align: center; }
        .stat .num { font-size: 1.8rem; font-weight: 700; }
        .stat .label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat.success .num { color: #22c55e; }
        .stat.skip .num { color: #a855f7; }
        .stat.error .num { color: #ef4444; }
        .stat.info .num { color: #38bdf8; }
        .log { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 16px; max-height: 60vh; overflow-y: auto; }
        .log-entry { padding: 4px 0; font-size: 0.85rem; font-family: 'Cascadia Code', 'Fira Code', monospace; border-bottom: 1px solid #1e293b; }
        .log-entry:hover { background: #334155; }
        .log-entry .badge { display: inline-block; width: 48px; text-align: center; font-weight: 700; font-size: 0.7rem; padding: 2px 4px; border-radius: 3px; margin-right: 8px; }
        .badge-ok { background: #166534; color: #4ade80; }
        .badge-skip { background: #581c87; color: #c084fc; }
        .badge-fail { background: #7f1d1d; color: #fca5a5; }
        .badge-info { background: #1e3a5f; color: #7dd3fc; }
        .db-status { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .db-chip { padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .db-ok { background: #14532d; color: #4ade80; border: 1px solid #22c55e; }
        .db-fail { background: #450a0a; color: #fca5a5; border: 1px solid #ef4444; }
    </style>
</head>
<body>
<div class="container">
    <h1>ISNM Database Migration</h1>
    <div class="meta">Run at <?= htmlspecialchars($summary['timestamp']) ?> &mdash; took <?= $summary['elapsed_seconds'] ?>s</div>

    <div class="db-status">
        <?php foreach ($outcomes as $db => $status): ?>
            <span class="db-chip <?= $status === 'ok' ? 'db-ok' : 'db-fail' ?>">
                <?= $db ?>: <?= $status === 'ok' ? 'Connected' : 'Failed' ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="summary-box">
        <div class="stat info"><div class="num"><?= $total ?></div><div class="label">Operations</div></div>
        <div class="stat success"><div class="num"><?= $success ?></div><div class="label">Created</div></div>
        <div class="stat skip"><div class="num"><?= $skipped ?></div><div class="label">Skipped</div></div>
        <div class="stat error"><div class="num"><?= $errors ?></div><div class="label">Errors</div></div>
    </div>

    <div class="log">
        <?php foreach ($results as $r): ?>
            <div class="log-entry">
                <?php
                $badgeClass = match($r['type']) {
                    'success' => 'badge-ok',
                    'skip'    => 'badge-skip',
                    'error'   => 'badge-fail',
                    default   => 'badge-info',
                };
                $badgeText = match($r['type']) {
                    'success' => 'OK',
                    'skip'    => 'SKIP',
                    'error'   => 'FAIL',
                    default   => 'INFO',
                };
                ?>
                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                <?= htmlspecialchars($r['msg']) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
