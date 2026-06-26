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

// Read schema SQL
$schemaFile = __DIR__ . '/sql/ict/lab_manager_schema.sql';
if (!file_exists($schemaFile)) {
    die("ERROR: Schema file not found: $schemaFile\n");
}
$sql = file_get_contents($schemaFile);
if (empty($sql)) {
    die("ERROR: Schema file is empty\n");
}

// Split by semicolons and execute each statement
$statements = explode(';', $sql);
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
