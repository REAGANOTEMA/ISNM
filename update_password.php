<?php
// Update the test director's password to a proper bcrypt hash
require_once 'config/database.php';

// Get database connection
$staff_conn = getStaffConnection();

// Email of our test director
$email_check = 'director.test@isnm.ac.ug';

// Generate a proper bcrypt hash for the password 'password123'
$password = 'password123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Update the staff member's password
$update_stmt = $staff_conn->prepare("UPDATE staff SET password = ? WHERE email = ?");
$update_stmt->bind_param("ss", $hashed_password, $email_check);

if ($update_stmt->execute()) {
    echo "SUCCESS: Updated password for staff member with email: " . $email_check . "<br>";
    echo "New hash: " . $hashed_password . "<br>";
} else {
    echo "ERROR updating password: " . $update_stmt->error . "<br>";
}

$update_stmt->close();
$staff_conn->close();
?>