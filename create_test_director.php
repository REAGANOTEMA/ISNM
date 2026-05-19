<?php
// Create a test director staff member with proper staff_id
require_once 'config/database.php';

// Get database connection
$staff_conn = getStaffConnection();

// First, let's check if the Director General role exists
$role_stmt = $staff_conn->prepare("SELECT id FROM staff_roles WHERE role_name = ?");
$role_term = 'Director General';
$role_stmt->bind_param("s", $role_term);
$role_stmt->execute();
$role_result = $role_stmt->get_result();

if ($role_result->num_rows === 0) {
    die("ERROR: Director General role does not exist. Please run the database setup first.");
}

$role_row = $role_result->fetch_assoc();
$role_id = $role_row['id'];

// Generate a unique staff ID
$staff_id = 'DIR' . date('Y') . sprintf('%06d', mt_rand(1, 999999));

// Check if we already have a test director staff member
$email_check = 'director.test@isnm.ac.ug';
$check_stmt = $staff_conn->prepare("SELECT id FROM staff WHERE email = ?");
$check_stmt->bind_param("s", $email_check);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    // Create test director staff member
    $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
    
    $insert_stmt = $staff_conn->prepare("
        INSERT INTO staff (staff_id, full_name, email, phone, password, role_id, position, department, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active')
    ");
    
    $full_name = 'Test Director General';
    $phone = '0771234567';
    $department = 'Administration';
    $position = 'Director General';
    
    $insert_stmt->bind_param("ssssissi", $staff_id, $full_name, $email_check, $phone, $hashed_password, $role_id, $position, $department);
    
    if ($insert_stmt->execute()) {
        echo "SUCCESS: Created test director staff member.<br>";
        echo "Staff ID: " . $staff_id . "<br>";
        echo "Email: " . $email_check . "<br>";
        echo "Password: password123<br>";
        echo "Role ID: " . $role_id . "<br>";
    } else {
        echo "ERROR creating staff member: " . $insert_stmt->error . "<br>";
    }
    
    $insert_stmt->close();
} else {
    $existing = $check_result->fetch_assoc();
    echo "INFO: Test director staff member already exists.<br>";
    echo "Staff ID: " . $existing['staff_id'] . "<br>";
    echo "Email: " . $existing['email'] . "<br>";
}

// Close connections
$staff_conn->close();
?>