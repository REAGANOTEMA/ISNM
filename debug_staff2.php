<?php
/**
 * Debug script for staff database SQL: try to execute statement by statement.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = 'ReagaN23#';
$port = 3307;
$db = 'igangaschoolofl_staffs_db';

echo "Setting up database: $db<br>";

// Connect to MySQL server (without selecting database)
$conn = new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop and create database
if ($conn->query("DROP DATABASE IF EXISTS `$db`")) {
    echo "  Database dropped (if existed).<br>";
} else {
    echo "  Warning: Could not drop database: " . $conn->error . "<br>";
}

if ($conn->query("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    echo "  Database created successfully.<br>";
} else {
    echo "  Error creating database: " . $conn->error . "<br>";
    $conn->close();
    exit;
}

// Select the database
if (!$conn->select_db($db)) {
    echo "  Error selecting database: " . $conn->error . "<br>";
    $conn->close();
    exit;
}
echo "  Selected database.<br>";

// Import the SQL file
$sql_file = 'sql/staffs/04_final_complete_staffs_database.sql';
if (file_exists($sql_file)) {
    echo "  Importing schema from $sql_file<br>";
    $sql_content = file_get_contents($sql_file);
    
    // For the staff database, we need to handle DELIMITER and comments
    if ($db === 'igangaschoolofl_staffs_db') {
        // Remove DELIMITER lines
        $sql_content = preg_replace('/^\s*DELIMITER\s+.*\s*$/m', '', $sql_content);
        // Remove lines that are only comments (single-line: // or --)
        $lines = explode("\n", $sql_content);
        $cleaned_lines = array();
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^\\/{2,}/', $trimmed) || preg_match('/^--/', $trimmed)) {
                continue;
            }
            $cleaned_lines[] = $line;
        }
        $sql_content = implode("\n", $cleaned_lines);
    }
    
    // Now, let's split by semicolon and try to execute each statement
    // We have to be careful: we don't want to split semicolons inside strings.
    // But for simplicity, we'll assume that the SQL file does not have semicolons inside strings except in the procedure bodies, which we have removed the delimiter for.
    // Actually, we removed the DELIMITER lines, so the procedures and triggers are now using semicolon as the delimiter inside their bodies, which will break the splitting.
    // So we need to change the delimiter inside the procedures and triggers to something else, or we need to not split at all and rely on multi_query.
    // Since multi_query is failing, let's try to split by semicolon and see if we can get a more specific error.
    
    // We'll split by semicolon and then trim each piece.
    $statements = explode(';', $sql_content);
    echo "  Split into " . count($statements) . " statements.<br>";
    
    // Disable foreign key checks for smoother import
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    $success = true;
    foreach ($statements as $i => $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) {
            continue;
        }
        // echo "    Executing statement $i: " . substr($stmt, 0, 100) . "...<br>";
        if (!$conn->query($stmt)) {
            echo "      Error at statement $i: " . $conn->error . "<br>";
            echo "      Statement: <pre>" . htmlspecialchars($stmt) . "</pre>";
            $success = false;
            break;
        }
        // else {
        //     echo "      Statement $i executed successfully.<br>";
        // }
    }
    
    if ($success) {
        echo "  All statements executed successfully.<br>";
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
} else {
    echo "  SQL file not found: $sql_file<br>";
}

$conn->close();
?>