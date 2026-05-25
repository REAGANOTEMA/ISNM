<?php
header('Content-Type: text/plain');

// Check the structure of staff table
require_once __DIR__ . '/config/database.php';

try {
    $conn = getStaffConnection();
    
    // Describe the staff table
    $result = $conn->query("DESCRIBE staff");
    echo "staff table structure:\n";
    echo "----------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo "Field: " . $row['Field'] . ", Type: " . $row['Type'] . ", Null: " . $row['Null'] . 
             ", Key: " . $row['Key'] . ", Default: " . (is_null($row['Default']) ? 'NULL' : $row['Default']) . 
             ", Extra: " . $row['Extra'] . "\n";
    }
    $result->free();
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>