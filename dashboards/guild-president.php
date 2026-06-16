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
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Guild President Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .dashboard-container { padding: 20px; }
        .card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #0077b6; color: white; }
    </style>
    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-container" style="margin-left:260px">
        <h1><i class="fas fa-crown"></i> Guild President Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
        
        <div class="text-center mb-3">
            <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
            <a href="../store_request.php" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-shopping-cart me-1"></i>Store</a>
            <a href="../news.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-newspaper me-1"></i>News</a>
            <a href="student-records.php" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-users-gear me-1"></i> Students</a>
        </div>
        
        <div class="card">
            <h3>Student Leadership Panel</h3>
            <p>Access student-related information and manage student affairs.</p>
            <button class="btn btn-primary" onclick="location.href='../student-login.php'">
                <i class="fas fa-users"></i> View Student Platform
            </button>
        </div>
    </div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>