<?php
// Guild President Dashboard
require_once '../auth-service.php';
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../staff-login.php?position=Guild%20President');
    exit;
}

// Allow both student and guild president roles
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, ['Guild President', 'guild president']) && ($_SESSION['type'] ?? '') !== 'student') {
    $_SESSION['error'] = "Access denied. Student privileges required.";
    header('Location: ../staff-login.php');
    exit;
}

$conn = getStudentsConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guild President Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .dashboard-container { padding: 20px; }
        .card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #0077b6; color: white; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1><i class="fas fa-crown"></i> Guild President Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
        
        <div class="card">
            <h3>Student Leadership Panel</h3>
            <p>Access student-related information and manage student affairs.</p>
            <button class="btn btn-primary" onclick="location.href='../student-login.php'">
                <i class="fas fa-users"></i> View Student Platform
            </button>
        </div>
    </div>
</body>
</html>