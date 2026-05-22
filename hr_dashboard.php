<?php
/**
 * HR Portal - Professional Dashboard
 * Human Resources Management System for ISNM
 */

session_start();

// Check authentication — unified single login
if (!isset($_SESSION['hr_id']) && (!isset($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff')) {
    header('Location: staff-login.php');
    exit;
}

require_once 'config/database.php';

$conn = getStaffConnection();
$user_id = $_SESSION['hr_id'];
$user_name = $_SESSION['hr_name'];

// Get dashboard statistics
$stats = array(
    'total_staff' => 0,
    'staff_on_leave_today' => 0,
    'upcoming_contract_expirations' => 0,
    'pending_leave_approvals' => 0,
    'recent_hires' => 0,
    'attendance_rate_today' => 0,
    'active_training_programs' => 0,
    'performance_reviews_pending' => 0
);

try {
    // Total staff count
    $result = $conn->query("
        SELECT COUNT(*) as count FROM staff_records 
        WHERE status = 'active'
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_staff'] = $row['count'];
    }
    
    // Staff on leave today
    $result = $conn->query("
        SELECT COUNT(DISTINCT staff_id) as count FROM leave_requests 
        WHERE CURDATE() BETWEEN start_date AND end_date 
        AND status = 'approved'
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['staff_on_leave_today'] = $row['count'];
    }
    
    // Upcoming contract expirations (next 30 days)
    $result = $conn->query("
        SELECT COUNT(*) as count FROM employment_contracts 
        WHERE contract_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND contract_status IN ('active', 'expiring')
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['upcoming_contract_expirations'] = $row['count'];
    }
    
    // Pending leave approvals
    $result = $conn->query("
        SELECT COUNT(*) as count FROM leave_requests 
        WHERE status = 'pending'
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_leave_approvals'] = $row['count'];
    }
    
    // Recent hires (last 30 days)
    $result = $conn->query("
        SELECT COUNT(*) as count FROM staff_records 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['recent_hires'] = $row['count'];
    }
    
    // Today's attendance rate
    $result = $conn->query("
        SELECT 
            COUNT(CASE WHEN attendance_status = 'present' THEN 1 END) as present,
            COUNT(*) as total
        FROM attendance 
        WHERE attendance_date = CURDATE()
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['total'] > 0) {
            $stats['attendance_rate_today'] = round(($row['present'] / $row['total']) * 100);
        }
    }
    
    // Active training programs
    $result = $conn->query("
        SELECT COUNT(*) as count FROM training_programs 
        WHERE status = 'ongoing'
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['active_training_programs'] = $row['count'];
    }
    
    // Performance reviews pending
    $result = $conn->query("
        SELECT COUNT(*) as count FROM staff_appraisals 
        WHERE status IN ('draft', 'submitted')
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['performance_reviews_pending'] = $row['count'];
    }
    
} catch (Exception $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}

// Get recent hires
$recent_hires = array();
try {
    $result = $conn->query("
        SELECT 
            sr.id,
            sr.staff_id,
            sr.full_name,
            ed.job_title,
            ed.department,
            sr.created_at
        FROM staff_records sr
        LEFT JOIN employment_details ed ON sr.id = ed.staff_id
        WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
        ORDER BY sr.created_at DESC
        LIMIT 8
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_hires[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Recent hires error: ' . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard - ISNM Human Resources Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #e74c3c;
            --primary-dark: #c0392b;
            --secondary: #e67e22;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f3f4f6;
            --dark: #1f2937;
            --border: #e5e7eb;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light);
            color: var(--dark);
        }
        
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Navigation */
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-header {
            padding: 0 20px 30px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            margin-bottom: 20px;
        }
        
        .sidebar-header .logo-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            border-left-color: white;
        }
        
        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 20px;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 12px;
        }
        
        .user-info strong {
            display: block;
            margin-bottom: 3px;
        }
        
        .sidebar .btn-logout {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        
        .sidebar .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }
        
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .top-bar h1 {
            font-size: 28px;
            color: var(--dark);
        }
        
        .top-bar-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn-primary {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-top: 4px solid;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.primary {
            border-top-color: var(--primary);
        }
        
        .stat-card.success {
            border-top-color: var(--success);
        }
        
        .stat-card.warning {
            border-top-color: var(--warning);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
        }
        
        .stat-card.primary .stat-icon {
            background: rgba(231, 76, 60, 0.1);
            color: var(--primary);
        }
        
        .stat-card.success .stat-icon {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .stat-card.warning .stat-icon {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .stat-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .stat-change {
            font-size: 12px;
            color: var(--success);
            font-weight: 600;
        }
        
        /* Section Title */
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 24px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-container thead {
            background: var(--light);
            border-bottom: 2px solid var(--border);
        }
        
        .table-container th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-container td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        
        .table-container tbody tr:hover {
            background: var(--light);
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Alert */
        .alert-banner {
            background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
            border-left: 4px solid var(--warning);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-icon">👥</div>
                <h2>HR Portal</h2>
                <p>Human Resources</p>
            </div>
            
            <nav class="sidebar-menu">
                <li><a href="#dashboard" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="hr_staff_records.php"><i class="fas fa-users"></i> Staff Records</a></li>
                <li><a href="hr_recruitment.php"><i class="fas fa-briefcase"></i> Recruitment</a></li>
                <li><a href="hr_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="hr_leave.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></li>
                <li><a href="hr_payroll.php"><i class="fas fa-money-bill-wave"></i> Payroll</a></li>
                <li><a href="hr_performance.php"><i class="fas fa-chart-line"></i> Performance</a></li>
                <li><a href="hr_training.php"><i class="fas fa-graduation-cap"></i> Training</a></li>
                <li><a href="hr_reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="hr_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($user_name); ?></strong>
                    <span><?php echo htmlspecialchars($_SESSION['hr_role']); ?></span>
                </div>
                <button class="btn-logout" onclick="logout();">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1>HR Dashboard</h1>
                <div class="top-bar-right">
                    <button class="btn-primary" onclick="generateReport()">
                        <i class="fas fa-file-pdf"></i> Export Report
                    </button>
                </div>
            </div>
            
            <?php if ($stats['upcoming_contract_expirations'] > 0): ?>
            <div class="alert-banner">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>⚠️ Attention Required</strong>
                    <p><?php echo $stats['upcoming_contract_expirations']; ?> contract(s) expiring in the next 30 days.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-label">Total Staff</div>
                    <div class="stat-value"><?php echo $stats['total_staff']; ?></div>
                    <div class="stat-change"><i class="fas fa-arrow-up"></i> Active</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-label">On Leave Today</div>
                    <div class="stat-value"><?php echo $stats['staff_on_leave_today']; ?></div>
                    <div class="stat-change">Staff members</div>
                </div>
                
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-hourglass-end"></i></div>
                    <div class="stat-label">Contract Expirations</div>
                    <div class="stat-value"><?php echo $stats['upcoming_contract_expirations']; ?></div>
                    <div class="stat-change">Next 30 days</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-check-square"></i></div>
                    <div class="stat-label">Pending Approvals</div>
                    <div class="stat-value"><?php echo $stats['pending_leave_approvals']; ?></div>
                    <div class="stat-change">Leave requests</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                    <div class="stat-label">Recent Hires</div>
                    <div class="stat-value"><?php echo $stats['recent_hires']; ?></div>
                    <div class="stat-change">Last 30 days</div>
                </div>
                
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    <div class="stat-label">Attendance Rate</div>
                    <div class="stat-value"><?php echo $stats['attendance_rate_today']; ?>%</div>
                    <div class="stat-change">Today</div>
                </div>
            </div>
            
            <!-- Recent Hires -->
            <h2 class="section-title"><i class="fas fa-user-plus"></i> Recent Hires</h2>
            <div class="table-container">
                <?php if (!empty($recent_hires)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Full Name</th>
                            <th>Job Title</th>
                            <th>Department</th>
                            <th>Hire Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_hires as $hire): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($hire['staff_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($hire['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($hire['job_title'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($hire['department'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($hire['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No recent hires</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'hr_logout.php';
            }
        }
        
        function generateReport() {
            alert('Report generation feature coming soon!');
        }
    </script>
</body>
</html>
