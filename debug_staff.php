<?php
/**
 * Debug script for staff database SQL.
 */

$host = 'localhost';
$user = 'igangaschoolofl_staffs_db';
$pass = 'AgKzJjZZnT5q58jCahs8';
$port = 3306;
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
    
    // Disable foreign key checks for smoother import
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    if ($conn->multi_query($sql_content)) {
        echo "  Schema imported successfully.<br>";
        do {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        echo "  Error importing schema: " . $conn->error . "<br>";
        echo "  SQL content (first 2000 chars):<br>";
        echo "<pre>" . htmlspecialchars(substr($sql_content, 0, 2000)) . "</pre>";
        // Let's also try to split by semicolon and run each statement to find the problematic one
        echo "  Trying to split by semicolon and execute each statement...<br>";
        $statements = explode(';', $sql_content);
        foreach ($statements as $i => $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt)) {
                continue;
            }
            echo "    Executing statement $i: " . substr($stmt, 0, 100) . "...<br>";
            if (!$conn->query($stmt)) {
                echo "      Error: " . $conn->error . "<br>";
                echo "      Statement: <pre>" . htmlspecialchars($stmt) . "</pre>";
                break;
            }
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
} else {
    echo "  SQL file not found: $sql_file<br>";
}

$conn->close();
?>