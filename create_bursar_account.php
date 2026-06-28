<?php
/**
 * Create Bursar Staff Account
 * Run this script to create the bursar user account
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';

$bursar_email = isnm_env('BURSAR_EMAIL', 'bursar@igangaschoolofnursingandmidwifery.ac.ug');
$bursar_password = isnm_env('BURSAR_PASSWORD');
$bursar_name = 'School Bursar';
$bursar_phone = '0782990403';

$conn = getStaffConnection();

// Check if role exists
$role_stmt = $conn->prepare("SELECT id FROM staff_roles WHERE role_name = 'School Bursar'");
$role_stmt->execute();
$role_result = $role_stmt->get_result();

if ($role_row = $role_result->fetch_assoc()) {
    $role_id = $role_row['id'];
} else {
    // Try to find bursar role with different name
    $role_stmt = $conn->prepare("SELECT id FROM staff_roles WHERE role_name LIKE '%bursar%'");
    $role_stmt->execute();
    $role_result = $role_stmt->get_result();
    if ($role_row = $role_result->fetch_assoc()) {
        $role_id = $role_row['id'];
    } else {
        // Insert the role
        $insert_role = $conn->prepare("INSERT INTO staff_roles (role_name, dashboard_path) VALUES ('School Bursar', 'dashboards/school-bursar.php')");
        $insert_role->execute();
        $role_id = $conn->insert_id;
    }
}

// Check if bursar already exists
$check_stmt = $conn->prepare("SELECT id FROM staff WHERE email = ?");
$check_stmt->bind_param("s", $bursar_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo "Bursar account already exists with email: $bursar_email\n";
    
    // Update password
    $hashed_password = password_hash($bursar_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE staff SET password = ? WHERE email = ?");
    $update_stmt->bind_param("ss", $hashed_password, $bursar_email);
    $update_stmt->execute();
    echo "Password updated successfully.\n";
} else {
    // Create new bursar account
    $hashed_password = password_hash($bursar_password, PASSWORD_DEFAULT);
    
    $insert_stmt = $conn->prepare("
        INSERT INTO staff (full_name, email, phone, password, role_id, position, department, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'School Bursar', 'Finance', 'Active', NOW())
    ");
    $insert_stmt->bind_param("sssii", $bursar_name, $bursar_email, $bursar_phone, $hashed_password, $role_id);
    
    if ($insert_stmt->execute()) {
        echo "Bursar account created successfully!\n";
        echo "Email: $bursar_email\n";
        echo "Password: $bursar_password\n";
    } else {
        echo "Error creating bursar account: " . $conn->error . "\n";
    }
}

echo "\nThe bursar can now login at the staff login page.\n";
?>