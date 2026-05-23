<?php
require_once 'config/database.php';

// Test students connection
try {
    $conn = getStudentsConnection();
    echo "Students connection: Success!<br>";
    $conn->close();
} catch (Exception $e) {
    echo "Students connection: Failed - " . $e->getMessage() . "<br>";
}

// Test staff connection
try {
    $conn = getStaffConnection();
    echo "Staff connection: Success!<br>";
    $conn->close();
} catch (Exception $e) {
    echo "Staff connection: Failed - " . $e->getMessage() . "<br>";
}

// Test website connection
try {
    $conn = getWebsiteConnection();
    echo "Website connection: Success!<br>";
    $conn->close();
} catch (Exception $e) {
    echo "Website connection: Failed - " . $e->getMessage() . "<br>";
}
?>