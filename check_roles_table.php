<?php
header('Content-Type: text/plain');

// Check the structure of staff_roles table
require_once __DIR__ . '/config/database.php';

try {
    $conn = getStaffConnection();
    
    // Describe the staff_roles table
    $result = $conn->query("DESCRIBE staff_roles");
    echo "staff_roles table structure:\n";
    echo "-----------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo "Field: " . $row['Field'] . ", Type: " . $row['Type'] . ", Null: " . $row['Null'] . 
             ", Key: " . $row['Key'] . ", Default: " . $row['Default'] . ", Extra: " . $row['Extra'] . "\n";
    }
    $result->free();
    
    // Show current roles
    echo "\nCurrent roles in staff_roles:\n";
    echo "-----------------------------\n";
    $result = $conn->query("SELECT * FROM staff_roles");
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Role: " . $row['role_name'] . ", Dashboard: " . $row['dashboard_path'] . "\n";
    }
    $result->free();
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>