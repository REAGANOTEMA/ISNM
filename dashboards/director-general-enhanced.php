<?php
// ISNM Enhanced Director General Dashboard
// Professional 3D graphics and modern design with school branding

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include unified authentication system
require_once '../auth-service.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

// Strict dashboard protection - only directors allowed
if (!$auth_service->isAuthenticated()) {
    header('Location: ../staff-login.php');
    exit();
}

// Check if user has the correct role
$userRole = $_SESSION['role'] ?? '';
if (stripos($userRole, 'director') === false && stripos($userRole, 'general') === false) {
    header('Location: ../staff-login.php?error=unauthorized');
    exit();
}

// Database connections
$staff_conn = new mysqli('localhost', 'root', '', 'staffs_db');
$students_conn = new mysqli('localhost', 'root', '', 'students_db');
$website_conn = new mysqli('localhost', 'root', '', 'website_db');

if ($staff_conn->connect_error) {
    die("Staff DB connection failed: " . $staff_conn->connect_error);
}

if ($students_conn->connect_error) {
    die("Students DB connection failed: " . $students_conn->connect_error);
}

if ($website_conn->connect_error) {
    die("Website DB connection failed: " . $website_conn->connect_error);
}

// Set charset
$staff_conn->set_charset("utf8mb4");
$students_conn->set_charset("utf8mb4");
$website_conn->set_charset("utf8mb4");

// Get user information from session
$staff_id = $_SESSION['user_id'] ?? 0;
$staff_email = $_SESSION['email'] ?? '';
$staff_name = $_SESSION['full_name'] ?? '';

// Get system statistics with real data
$total_students = 0;
$total_staff = 0;
$total_applications = 0;
$active_programs = 0;
$total_collections = 0;
$outstanding_fees = 0;

// Get real statistics from students database
$students_sql = "SELECT COUNT(*) as total FROM students WHERE status = 'Active'";
$students_result = $students_conn->query($students_sql);
if ($students_result) {
    $total_students = $students_result->fetch_assoc()['total'];
}

// Get real statistics from staff database
$staff_sql = "SELECT COUNT(*) as total FROM staff WHERE status = 'Active'";
$staff_result = $staff_conn->query($staff_sql);
if ($staff_result) {
    $total_staff = $staff_result->fetch_assoc()['total'];
}

// Get applications from website database
$apps_sql = "SELECT COUNT(*) as total FROM applications WHERE status = 'New'";
$apps_result = $website_conn->query($apps_sql);
if ($apps_result) {
    $total_applications = $apps_result->fetch_assoc()['total'];
}

// Get active programs from website database
$programs_sql = "SELECT COUNT(*) as total FROM pages WHERE status = 'Published'";
$programs_result = $website_conn->query($programs_sql);
if ($programs_result) {
    $active_programs = $programs_result->fetch_assoc()['total'];
}

// Get financial data from staff database
$finance_sql = "SELECT SUM(amount) as total FROM financial_records WHERE record_type = 'Collection' AND DATE(transaction_date) = CURDATE()";
$finance_result = $staff_conn->query($finance_sql);
if ($finance_result) {
    $total_collections = $finance_result->fetch_assoc()['total'] ?? 0;
}

// Get outstanding fees from students database
$fees_sql = "SELECT SUM(balance) as total FROM students WHERE status = 'Active' AND balance > 0";
$fees_result = $students_conn->query($fees_sql);
if ($fees_result) {
    $outstanding_fees = $fees_result->fetch_assoc()['total'] ?? 0;
}

// Get recent activities
$activities_sql = "SELECT COUNT(*) as total FROM staff_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$activities_result = $staff_conn->query($activities_sql);
$recent_activities = $activities_result ? $activities_result->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director General Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/staff_dashboard_enhanced.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-container {
            perspective: 1000px;
            padding: 20px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            transform-style: preserve-3d;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-10px) rotateX(5deg) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            border-radius: 16px;
            z-index: -1;
            transition: all 0.5s ease;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            transform: translateZ(-20px);
            transition: all 0.5s ease;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .school-header {
            background: var(--gradient-primary);
            color: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .school-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .school-logo:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }
        
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .activity-feed {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .activity-text {
            flex: 1;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- School Header with Logo -->
        <div class="school-header">
            <img src="../images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <div>
                <h1>ISNM School Management System</h1>
                <p>Director General Dashboard</p>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Total Students Card -->
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_students); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            
            <!-- Total Staff Card -->
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_staff); ?></div>
                <div class="stat-label">Total Staff</div>
            </div>
            
            <!-- Applications Card -->
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_applications); ?></div>
                <div class="stat-label">New Applications</div>
            </div>
            
            <!-- Collections Card -->
            <div class="stat-card">
                <div class="stat-number">UGX <?php echo number_format($total_collections); ?></div>
                <div class="stat-label">Today's Collections</div>
            </div>
            
            <!-- Outstanding Fees Card -->
            <div class="stat-card">
                <div class="stat-number">UGX <?php echo number_format($outstanding_fees); ?></div>
                <div class="stat-label">Outstanding Fees</div>
            </div>
            
            <!-- Active Programs Card -->
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($active_programs); ?></div>
                <div class="stat-label">Active Programs</div>
            </div>
            
            <!-- Recent Activities Card -->
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($recent_activities); ?></div>
                <div class="stat-label">Recent Activities</div>
            </div>
        </div>
        
        <!-- Chart Container -->
        <div class="chart-container">
            <h3>System Overview</h3>
            <canvas id="systemChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Activity Feed -->
        <div class="activity-feed">
            <h3>Recent Activities</h3>
            <?php
            $activities_sql = "SELECT sal.activity_type, sal.activity_description, sal.created_at, s.full_name 
                               FROM staff_activity_log sal 
                               JOIN staff s ON sal.staff_id = s.id 
                               ORDER BY sal.created_at DESC 
                               LIMIT 10";
            $activities_result = $staff_conn->query($activities_sql);
            
            if ($activities_result) {
                while ($activity = $activities_result->fetch_assoc()) {
                    echo '<div class="activity-item">';
                    echo '<span class="activity-time">' . date('M j, Y H:i', strtotime($activity['created_at'])) . '</span>';
                    echo '<span class="activity-text">' . htmlspecialchars($activity['activity_description']) . ' - <strong>' . htmlspecialchars($activity['full_name']) . '</strong></span>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
    
    <script>
        // Initialize Chart.js
        const ctx = document.getElementById('systemChart').getContext('2d');
        
        // Create gradient background
        const gradient = ctx.createLinearGradient(0, 0, 0, 0, 1);
        gradient.addColorStop(0, 'rgba(102, 126, 234, 0.8)');
        gradient.addColorStop(1, 'rgba(102, 126, 234, 0.2)');
        
        // Create chart
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Students', 'Staff', 'Applications', 'Collections'],
                datasets: [{
                    data: [<?php echo $total_students; ?>, <?php echo $total_staff; ?>, <?php echo $total_applications; ?>, <?php echo $total_collections; ?>],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#ffffff',
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
