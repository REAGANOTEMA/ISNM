<?php
/**
 * Lab Manager Schema Migration Runner.
 * Creates missing tables and columns in the ICT database.
 * Run: php db_migrate_lab_manager.php
 * Or open in browser: http://localhost/isnm/db_migrate_lab_manager.php
 */

require_once __DIR__ . '/config/database.php';

echo "<pre>";
echo "=== Lab Manager Schema Migration ===\n\n";

// Connect to ICT database
$ict = @getICTConnection();
if (!$ict) {
    die("ERROR: Could not connect to ICT database.\nCheck that ICT_DB_* credentials are correct in .env\n");
}
echo "Connected to: " . ICT_DB_NAME . "\n\n";

// Inline CREATE TABLE statements for skills lab tables
$statements = [
    "CREATE TABLE IF NOT EXISTS `skills_lab_equipment` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `equipment_code` VARCHAR(50) DEFAULT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `quantity` INT(11) DEFAULT 0,
        `condition_status` VARCHAR(50) DEFAULT 'Good',
        `location` VARCHAR(255) DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'Available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `skills_lab_checkouts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `equipment_id` INT(11) NOT NULL,
        `student_id` INT(11) NOT NULL,
        `checkout_date` DATETIME NOT NULL,
        `return_date` DATETIME DEFAULT NULL,
        `status` VARCHAR(20) DEFAULT 'Checked Out',
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `skills_lab_sessions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `session_name` VARCHAR(255) NOT NULL,
        `instructor_id` INT(11) DEFAULT NULL,
        `scheduled_date` DATE DEFAULT NULL,
        `start_time` TIME DEFAULT NULL,
        `end_time` TIME DEFAULT NULL,
        `room` VARCHAR(100) DEFAULT NULL,
        `max_students` INT(11) DEFAULT 30,
        `status` VARCHAR(20) DEFAULT 'Scheduled',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `skills_lab_skills` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `skill_name` VARCHAR(255) NOT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `is_mandatory` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `skills_lab_consumables` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `category` VARCHAR(100) DEFAULT NULL,
        `quantity` INT(11) DEFAULT 0,
        `unit` VARCHAR(50) DEFAULT 'pcs',
        `min_stock` INT(11) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'Available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `skills_lab_incidents` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `incident_type` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `equipment_id` INT(11) DEFAULT NULL,
        `reported_by` INT(11) DEFAULT NULL,
        `severity` VARCHAR(20) DEFAULT 'Low',
        `status` VARCHAR(20) DEFAULT 'Open',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];
$count = 0;
$errors = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    // Skip comments and DELIMITER statements
    if (strpos($stmt, '--') === 0) continue;
    if (strpos($stmt, 'DELIMITER') !== false) continue;
    
    $result = @$ict->query($stmt);
    if ($result === false) {
        $err = $ict->error;
        // Ignore "already exists" or "duplicate" errors for CREATE TABLE IF NOT EXISTS and INSERT IGNORE
        if (strpos($err, 'already exists') !== false || 
            strpos($err, 'Duplicate') !== false ||
            strpos($err, 'doesn\'t exist') !== false) {
            echo "  SKIP (expected): " . substr($stmt, 0, 60) . "...\n";
            continue;
        }
        $errors[] = $err;
        echo "  ERROR: $err\n  SQL: " . substr($stmt, 0, 80) . "...\n\n";
    } else {
        $count++;
    }
}

echo "\n=== Migration Summary ===\n";
echo "Statements executed successfully: $count\n";
if (!empty($errors)) {
    echo "Errors: " . count($errors) . "\n";
    foreach ($errors as $e) {
        echo "  - $e\n";
    }
} else {
    echo "No errors.\n";
}

// Check existing tables
echo "\n=== Tables in ICT Database ===\n";
$tables = $ict->query("SHOW TABLES");
if ($tables) {
    while ($row = $tables->fetch_array()) {
        $tname = $row[0];
        $ct = $ict->query("SELECT COUNT(*) as c FROM $tname")->fetch_assoc();
        echo "  $tname (" . ($ct['c'] ?? 0) . " rows)\n";
    }
}

$ict->close();
echo "\nDone.\n";
