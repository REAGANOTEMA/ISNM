<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['director', 'ict']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'ICT Director';

// Set ICT dashboard statistics with fallbacks
$total_computers = 60;
$active_users = 204;
$system_uptime = '99.9%';
$storage_used = '450 GB';
$total_students = 150;
$total_staff = 2;

// Try to get real stats
try {
    require_once __DIR__ . '/../config/database.php';
    $students_conn = getStudentsConnection();
    if ($students_conn) {
        $result = $students_conn->query("SELECT COUNT(*) as cnt FROM students");
        if ($result) $total_students = $result->fetch_assoc()['cnt'] ?? 150;
    }
    
    $staff_result = $conn->query("SELECT COUNT(*) as cnt FROM staff");
    if ($staff_result) $total_staff = $staff_result->fetch_assoc()['cnt'] ?? 2;
} catch (Exception $e) {}

// Get recent activities with fallback
$recent_activities = [
    ['activity' => 'Dashboard accessed', 'created_at' => date('Y-m-d H:i:s')],
    ['activity' => 'IT system maintenance completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))]
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
    <title>Director ICT Dashboard - ISNM</title>
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
        
        .infra-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .infra-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }
        
        .infra-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .infra-header h4 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
        }
        
        .status-badge {
            padding: 5px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        
        .status-badge.active {
            background: #dcfce7;
            color: #166534;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item span {
            color: #64748b;
        }
        
        .detail-item strong {
            color: #0f172a;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .status-indicator {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
        
        .status-indicator.online {
            background: #10b981;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }
        
        .status-indicator.warning {
            background: #f59e0b;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
        }
        
        .status-info h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }
        
        .status-info p {
            margin: 3px 0 0;
            color: #64748b;
            font-size: 0.9rem;
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
                <span class="badge bg-info">Director ICT</span>
            </div>
            
            <nav class="sidebar-menu">
                <a href="#overview" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> System Overview
                </a>
                <a href="#infrastructure" class="nav-link">
                    <i class="fas fa-server"></i> Infrastructure
                </a>
                <a href="#network" class="nav-link">
                    <i class="fas fa-network-wired"></i> Network Management
                </a>
                <a href="#security" class="nav-link">
                    <i class="fas fa-shield-alt"></i> Security
                </a>
                <a href="#support" class="nav-link">
                    <i class="fas fa-headset"></i> IT Support
                </a>
                <a href="#activity" class="nav-link">
                    <i class="fas fa-history"></i> Activity Log
                </a>
                <a href="../news.php" class="nav-link">
                    <i class="fas fa-newspaper"></i> Manage News
                </a>
                <a href="../store_request.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> Store Request
                </a>
                <a href="../student-directory.php" class="nav-link">
                    <i class="fas fa-address-book"></i> Student Directory
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-home"></i> Homepage
                </a>
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
                    <h1>Director ICT Dashboard</h1>
                    <p>Technology Infrastructure & Systems Management</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- System Overview -->
                <section id="overview" class="content-section">
                    <h2>System Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6);">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_computers); ?></h3>
                                <p>Total Computers</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #059669, #10b981);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($active_users); ?></h3>
                                <p>Active Users</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #d97706, #f59e0b);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $system_uptime; ?></h3>
                                <p>System Uptime</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #0f4c3a, #059669);">
                                <i class="fas fa-hdd"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $storage_used; ?></h3>
                                <p>Storage Used</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Infrastructure -->
                <section id="infrastructure" class="content-section">
                    <h2><i class="fas fa-server me-2"></i>IT Infrastructure</h2>
                    <div class="infra-grid">
                        <div class="infra-card">
                            <div class="infra-header">
                                <h4>Computer Laboratory</h4>
                                <span class="status-badge active">Operational</span>
                            </div>
                            <div class="infra-details">
                                <div class="detail-item">
                                    <span>Total Computers:</span>
                                    <strong>60</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Functional:</span>
                                    <strong class="text-success">58</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Under Maintenance:</span>
                                    <strong class="text-warning">2</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Internet Speed:</span>
                                    <strong>100 Mbps</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="infra-card">
                            <div class="infra-header">
                                <h4>Network Infrastructure</h4>
                                <span class="status-badge active">Optimal</span>
                            </div>
                            <div class="infra-details">
                                <div class="detail-item">
                                    <span>Active Connections:</span>
                                    <strong>156</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Network Coverage:</span>
                                    <strong>100%</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Bandwidth Usage:</span>
                                    <strong>65%</strong>
                                </div>
                                <div class="detail-item">
                                    <span>WiFi Points:</span>
                                    <strong>12</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="infra-card">
                            <div class="infra-header">
                                <h4>Server Infrastructure</h4>
                                <span class="status-badge active">Running</span>
                            </div>
                            <div class="infra-details">
                                <div class="detail-item">
                                    <span>Active Servers:</span>
                                    <strong>4</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Database Server:</span>
                                    <strong class="text-success">Online</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Web Server:</span>
                                    <strong class="text-success">Online</strong>
                                </div>
                                <div class="detail-item">
                                    <span>Backup Server:</span>
                                    <strong class="text-success">Online</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Network Management -->
                <section id="network" class="content-section">
                    <h2><i class="fas fa-network-wired me-2"></i>Network Management</h2>
                    <div class="network-status mb-4">
                        <h3 class="mb-3">Network Status Overview</h3>
                        <div class="status-grid">
                            <div class="status-item">
                                <div class="status-indicator online"></div>
                                <div class="status-info">
                                    <h4>Main Network</h4>
                                    <p>All systems operational</p>
                                </div>
                            </div>
                            
                            <div class="status-item">
                                <div class="status-indicator online"></div>
                                <div class="status-info">
                                    <h4>Student WiFi</h4>
                                    <p>156 active connections</p>
                                </div>
                            </div>
                            
                            <div class="status-item">
                                <div class="status-indicator online"></div>
                                <div class="status-info">
                                    <h4>Staff Network</h4>
                                    <p>48 active connections</p>
                                </div>
                            </div>
                            
                            <div class="status-item">
                                <div class="status-indicator warning"></div>
                                <div class="status-info">
                                    <h4>Guest Network</h4>
                                    <p>Limited bandwidth</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Security -->
                <section id="security" class="content-section">
                    <h2><i class="fas fa-shield-alt me-2"></i>Security Management</h2>
                    <div class="security-overview">
                        <h3 class="mb-3">Security Status</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                                    <i class="fas fa-shield-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>Low</h3>
                                    <p>Threat Level</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1e3a8a);">
                                    <i class="fas fa-clock-rotate-left"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>2 hours ago</h3>
                                    <p>Last Scan</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div class="stat-content">
                                    <h3>3</h3>
                                    <p>Failed Logins Today</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- IT Support -->
                <section id="support" class="content-section">
                    <h2><i class="fas fa-headset me-2"></i>IT Support Tickets</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>User</th>
                                    <th>Issue</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#IT-001</td>
                                    <td>John Student</td>
                                    <td>Cannot access student portal</td>
                                    <td><span class="badge bg-danger">High</span></td>
                                    <td><span class="badge bg-warning">In Progress</span></td>
                                    <td>2 hours ago</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">View</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#IT-002</td>
                                    <td>Mary Lecturer</td>
                                    <td>Slow computer performance</td>
                                    <td><span class="badge bg-warning text-dark">Medium</span></td>
                                    <td><span class="badge bg-secondary">Pending</span></td>
                                    <td>4 hours ago</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">View</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section id="activity" class="content-section">
                    <h2><i class="fas fa-history me-2"></i>Recent IT Activities</h2>
                    <div>
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
