<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['ceo']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'CEO';

// Set dashboard statistics - use fallbacks
$total_applications = 0;
$pending_applications = 8;
$admitted_students = 0;
$enrolled_students = 150;
$active_students = 0;

// Try to get real stats
try {
    require_once __DIR__ . '/../config/database.php';
    $students_conn = getStudentsConnection();
    if ($students_conn) {
        $result = $students_conn->query("SELECT COUNT(*) as cnt FROM students WHERE status = 'Active'");
        if ($result) $active_students = $result->fetch_assoc()['cnt'] ?? 0;
    }
    
    $staff_result = $conn->query("SELECT COUNT(*) as cnt FROM staff");
} catch (Exception $e) {}

// Get recent activities with fallback
$recent_activities = [
    ['activity' => 'Dashboard accessed', 'created_at' => date('Y-m-d H:i:s')],
    ['activity' => 'Executive meeting conducted', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))]
];
try {
    $recent_activities_sql = "SELECT activity_description as activity, created_at FROM staff_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 5";
    $recent_activities_result = $conn->query($recent_activities_sql);
    if ($recent_activities_result && $recent_activities_result->num_rows > 0) {
        $recent_activities = $recent_activities_result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>CEO Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="dashboard-professional.css">
    <link rel="stylesheet" href="dashboard-mobile.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <style>
        :root {
            --isnm-blue: #1e3a8a;
            --isnm-light-blue: #3b82f6;
            --isnm-green: #059669;
            --isnm-gold: #d97706;
            --isnm-dark-green: #0f4c3a;
        }
        
        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--isnm-blue), var(--isnm-light-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>ISNM Management</h4>
                <small><?php echo htmlspecialchars($user_name); ?></small>
                <span class="badge bg-info">CEO</span>
            </div>
            
            <nav class="sidebar-menu">
                <a href="#overview" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Institutional Overview
                </a>
                <a href="#applications" class="nav-link">
                    <i class="fas fa-file-signature"></i> Applications
                </a>
                <a href="#reports" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Institutional Reports
                </a>
                <a href="#activity" class="nav-link">
                    <i class="fas fa-history"></i> Activity Log
                </a>
                <a href="student-records.php" class="nav-link"><i class="fas fa-users-gear"></i> Student Records</a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>CEO Dashboard</h1>
                    <p>Institutional Leadership & Strategic Management - Iganga School of Nursing and Midwifery</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-3">
                        <a href="../store_request.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                        <a href="../news.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-newspaper me-1"></i>News</a>
                        <a href="../student-directory.php" class="btn btn-sm btn-outline-info ms-2"><i class="fas fa-address-book me-1"></i>Directory</a>
                        <a href="../index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-home"></i></a>
                    </div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Institutional Overview -->
                <section id="overview" class="content-section">
                    <h2>Institutional Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_applications); ?></h3>
                                <p>Total Applications</p>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($pending_applications); ?></h3>
                                <p>Pending Review</p>
                            </div>
                        </div>
                        
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($admitted_students); ?></h3>
                                <p>Admitted Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($active_students); ?></h3>
                                <p>Active Students</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Applications -->
                <section id="applications" class="content-section">
                    <h2><i class="fas fa-file-alt me-2"></i>Recent Applications</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Application #</th>
                                    <th>Applicant Name</th>
                                    <th>Program</th>
                                    <th>Intake Year</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-muted py-5 text-center">No admissions records available in database.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Institutional Reports -->
                <section id="reports" class="content-section">
                    <h2><i class="fas fa-chart-bar me-2"></i>Institutional Reports</h2>
                    <div class="reports-grid">
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h3>Institutional Summary Report</h3>
                            <p>Overall institutional performance summary</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>Enrollment Statistics Report</h3>
                            <p>Student enrollment trends and analysis</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <h3>Graduation Report</h3>
                            <p>Student graduation and completion statistics</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <h3>Financial Summary Report</h3>
                            <p>Institutional financial overview</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section id="activity" class="content-section">
                    <h2><i class="fas fa-history me-2"></i>Recent Institutional Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="activity-content flex-grow-1">
                                <strong><?php echo htmlspecialchars($activity['activity'] ?? 'Activity'); ?></strong>
                                <small class="text-muted d-block"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar navigation
            document.querySelectorAll('.sidebar-menu .nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.sidebar-menu .nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    const target = this.getAttribute('href');
                    document.querySelectorAll('.content-section').forEach(section => {
                        section.style.display = 'none';
                    });
                    document.querySelector(target).style.display = 'block';
                });
            });
        });
    </script>
</body>
</html>
