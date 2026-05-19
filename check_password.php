<?php
// Check what password is actually stored in the database for our test director
require_once 'config/database.php';

// Get database connection
$staff_conn = getStaffConnection();

// Check our test director's password
$email_check = 'director.test@isnm.ac.ug';
$stmt = $staff_conn->prepare("SELECT id, full_name, email, password FROM staff WHERE email = ?");
$stmt->bind_param("s", $email_check);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $staff = $result->fetch_assoc();
    echo "Staff found:<br>";
    echo "ID: " . $staff['id'] . "<br>";
    echo "Full Name: " . $staff['full_name'] . "<br>";
    echo "Email: " . $staff['email'] . "<br>";
    echo "Password (hash): " . $staff['password'] . "<br>";
    echo "Password length: " . strlen($staff['password']) . "<br>";
    
    // Check if it looks like a bcrypt hash
    if (substr($staff['password'], 0, 4) == '$2y$' || substr($staff['password'], 0, 4) == '$2a$') {
        echo "Password appears to be a bcrypt hash<br>";
    } else {
        echo "Password does NOT appear to be a bcrypt hash<br>";
    }
    
    // Try to verify our test password against it
    $test_password = 'password123';
    $is_valid = password_verify($test_password, $staff['password']);
    echo "Password 'password123' validates: " . ($is_valid ? 'YES' : 'NO') . "<br>";
    
    // Try the default password
    $default_password = '12345678';
    $is_valid_default = password_verify($default_password, $staff['password']);
    echo "Password '12345678' validates: " . ($is_valid_default ? 'YES' : 'NO') . "<br>";
} else {
    echo "Staff member not found with email: " . $email_check . "<br>";
}

$stmt->close();
$staff_conn->close();
?>