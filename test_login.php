<?php
// Test script to simulate login with position parameter
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth-service.php';

// Create auth service instance
$auth_service = new AuthenticationService();

// Simulate login with Director General position
$email = 'director@isnm.ac.ug'; // Example email
$password = 'password123'; // Example password
$requestedPosition = 'Director General';

// Authenticate staff
$result = $auth_service->authenticateStaff($email, $password);

if ($result) {
    echo "Login successful!<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Full Name: " . $_SESSION['full_name'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
    echo "Position: " . $_SESSION['position'] . "<br>";
    echo "Dashboard Path: " . $_SESSION['dashboard_path'] . "<br>";
    
    // Test dashboard route resolution
    $dashboard = $auth_service->getDashboardRoute($_SESSION['role']);
    echo "Dashboard Route: " . $dashboard . "<br>";
    
    // Test organogram position resolution
    $resolvedRole = $auth_service->resolveOrganogramPosition($requestedPosition);
    echo "Resolved Role from Organogram: " . $resolvedRole . "<br>";
    $dashboardFromPosition = $auth_service->getDashboardRoute($resolvedRole);
    echo "Dashboard Route from Position: " . $dashboardFromPosition . "<br>";
} else {
    echo "Login failed.<br>";
}
?>