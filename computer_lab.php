<?php
/**
 * Computer Lab Portal - Professional Dashboard
 * ICT Department Management System for ISNM
 * Manages computer lab resources, bookings, maintenance, and IT support
 */

require_once 'auth-service.php';

// Check authentication
if (!$auth_service->isAuthenticated() || $_SESSION['type'] !== 'staff' || !in_array($_SESSION['role'], ['Director ICT', 'IT Manager', 'Lab Technician', 'Computer Lab Manager'])) {
    $_SESSION['error'] = "Access denied. ICT department privileges required.";
    header('Location: staff-login.php');
    exit;
}

require_once 'config/database.php';

$conn = getICTConnection();
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get dashboard statistics
$stats = array(
    'total_computers' => 0,
    'computers_online' => 0,
    'computers_offline' => 0,
    'computers_under_maintenance' => 0,
    'active_sessions' => 0,
    'pending_bookings' => 0,
    'pending_tickets' => 0,
    'software_updates_pending' => 0,
    'network_devices_online' => 0,
    'network_devices_offline' => 0,
    'lab_capacity_today' => 0,
    'internet_uptime' => 99.9
);

try {
    // Total computers count
    $result = $conn->query("SELECT COUNT(*) as count FROM lab_computers WHERE status != 'deleted'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_computers'] = $row['count'];
    }
    
    // Computers online
    $result = $conn->query("SELECT COUNT(*) as count FROM lab_computers WHERE status = 'online'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['computers_online'] = $row['count'];
    }
    
    // Computers offline
    $result = $conn->query("SELECT COUNT(*) as count FROM lab_computers WHERE status = 'offline'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['computers_offline'] = $row['count'];
    }
    
    // Computers under maintenance
    $result = $conn->query("SELECT COUNT(*) as count FROM lab_computers WHERE status = 'maintenance'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['computers_under_maintenance'] = $row['count'];
    }
    
    // Active lab sessions today
    $result = $conn->query("SELECT COUNT(*) as count FROM lab_bookings WHERE DATE(booking_date) = CURDATE() AND status = 'confirmed'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['active_sessions'] = $row['count'];
    }
    
    // Pending bookings
    $result = $conn->query("SELECT COUNT(*) as count FROM lab_bookings WHERE status = 'pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_bookings'] = $row['count'];
    }
    
    // Pending IT support tickets
    $result = $conn->query("SELECT COUNT(*) as count FROM it_support_tickets WHERE status IN ('open', 'in_progress')");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_tickets'] = $row['count'];
    }
    
    // Software updates pending
    $result = $conn->query("SELECT COUNT(*) as count FROM software_inventory WHERE update_available = 1");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['software_updates_pending'] = $row['count'];
    }
    
    // Network devices online
    $result = $conn->query("SELECT COUNT(*) as count FROM network_devices WHERE status = 'online'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['network_devices_online'] = $row['count'];
    }
    
    // Network devices offline
    $result = $conn->query("SELECT COUNT(*) as count FROM network_devices WHERE status = 'offline'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['network_devices_offline'] = $row['count'];
    }
    
} catch (Exception $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}

// Get recent lab bookings
$recent_bookings = array();
try {
    $result = $conn->query("
        SELECT 
            lb.id,
            lb.booking_reference,
            lb.course_name,
            lb.instructor_name,
            lb.booking_date,
            lb.time_slot,
            lb.number_of_students,
            lb.status,
            lb.created_at
        FROM lab_bookings lb
        ORDER BY lb.created_at DESC
        LIMIT 8
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_bookings[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Recent bookings error: ' . $e->getMessage());
}

// Get pending IT support tickets
$pending_tickets = array();
try {
    $result = $conn->query("
        SELECT 
            st.id,
            st.ticket_number,
            st.requester_name,
            st.requester_type,
            st.issue_type,
            st.priority,
            st.description,
            st.status,
            st.created_at
        FROM it_support_tickets st
        WHERE st.status IN ('open', 'in_progress')
        ORDER BY 
            CASE st.priority 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                ELSE 4 
            END,
            st.created_at ASC
        LIMIT 8
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pending_tickets[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Pending tickets error: ' . $e->getMessage());
}

// Get computers needing maintenance
$maintenance_computers = array();
try {
    $result = $conn->query("
        SELECT 
            computer_id,
            computer_name,
            location,
            status,
            last_maintenance,
            issues_reported
        FROM lab_computers 
        WHERE status IN ('offline', 'maintenance') OR issues_reported IS NOT NULL
        ORDER BY status, computer_name
        LIMIT 6
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $maintenance_computers[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Maintenance computers error: ' . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Lab Dashboard - ISNM ICT Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #0077b6;
            --primary-dark: #005f92;
            --secondary: #00b4d8;
            --accent: #90e0ef;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
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
            z-index: 1000;
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
        
        .btn-primary, .btn-secondary, .btn-danger, .btn-success {
            padding: 10px 20px;
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
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 119, 182, 0.3);
        }
        
        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 2px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: white;
            border-color: var(--primary);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #0d9668;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
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
        
        .stat-card.danger {
            border-top-color: var(--danger);
        }
        
        .stat-card.info {
            border-top-color: var(--info);
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
            background: rgba(0, 119, 182, 0.1);
            color: var(--primary);
        }
        
        .stat-card.success .stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .stat-card.warning .stat-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .stat-card.danger .stat-icon {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .stat-card.info .stat-icon {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
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
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .badge-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
        }
        
        .badge-primary {
            background: rgba(0, 119, 182, 0.1);
            color: var(--primary);
        }
        
        /* Alert Banner */
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
        
        .alert-banner i {
            color: var(--warning);
            font-size: 20px;
        }
        
        .alert-banner-content {
            flex: 1;
        }
        
        .alert-banner strong {
            display: block;
            color: var(--dark);
            margin-bottom: 3px;
        }
        
        .alert-banner p {
            color: #6b7280;
            font-size: 13px;
            margin: 0;
        }
        
        /* Lab Status Grid */
        .lab-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .lab-status-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .lab-status-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .lab-status-item.online {
            border-top: 4px solid var(--success);
        }
        
        .lab-status-item.offline {
            border-top: 4px solid var(--danger);
        }
        
        .lab-status-item.maintenance {
            border-top: 4px solid var(--warning);
        }
        
        .lab-status-item .status-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .lab-status-item.online .status-icon {
            color: var(--success);
        }
        
        .lab-status-item.offline .status-icon {
            color: var(--danger);
        }
        
        .lab-status-item.maintenance .status-icon {
            color: var(--warning);
        }
        
        .lab-status-item .status-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .lab-status-item .status-count {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        /* Priority Colors */
        .priority-critical {
            color: #dc2626;
            font-weight: 700;
        }
        
        .priority-high {
            color: #ea580c;
            font-weight: 600;
        }
        
        .priority-medium {
            color: #ca8a04;
        }
        
        .priority-low {
            color: #6b7280;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
                z-index: 1000;
                transition: all 0.3s ease;
            }
            
            .sidebar.active {
                width: 260px;
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .top-bar h1 {
                font-size: 20px;
            }
            
            .top-bar-right {
                width: 100%;
                justify-content: space-between;
            }
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
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 20px;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
        }
        
        /* Network Status Indicator */
        .network-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .network-status .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .network-status .status-dot.online {
            background: var(--success);
        }
        
        .network-status .status-dot.offline {
            background: var(--danger);
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-icon">💻</div>
                <h2>Computer Lab</h2>
                <p>ICT Department</p>
            </div>
            
            <nav class="sidebar-menu">
                <li><a href="computer_lab.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="#computers"><i class="fas fa-desktop"></i> Lab Computers</a></li>
                <li><a href="#bookings"><i class="fas fa-calendar-alt"></i> Lab Bookings</a></li>
                <li><a href="#tickets"><i class="fas fa-ticket-alt"></i> IT Support</a></li>
                <li><a href="#maintenance"><i class="fas fa-tools"></i> Maintenance</a></li>
                <li><a href="#software"><i class="fas fa-download"></i> Software</a></li>
                <li><a href="#network"><i class="fas fa-network-wired"></i> Network</a></li>
                <li><a href="#inventory"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="#reports"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="#settings"><i class="fas fa-cog"></i> Settings</a></li>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                    <span><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                </div>
                <form action="auth-handler.php" method="POST" id="logoutForm" style="display:none;">
                    <input type="hidden" name="action" value="logout">
                </form>
                <button class="btn-logout" onclick="document.getElementById('logoutForm').submit();">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-laptop-code" style="color: var(--primary);"></i> Computer Lab Dashboard</h1>
                <div class="top-bar-right">
                    <button class="btn-secondary" onclick="showNetworkStatus()">
                        <i class="fas fa-wifi"></i> Network Status
                    </button>
                    <button class="btn-primary" onclick="createNewTicket()">
                        <i class="fas fa-plus"></i> New Support Ticket
                    </button>
                </div>
            </div>
            
            <?php if ($stats['computers_offline'] > 0 || $stats['pending_tickets'] > 0): ?>
            <div class="alert-banner">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="alert-banner-content">
                    <strong>⚠️ Attention Required</strong>
                    <p>
                        <?php if ($stats['computers_offline'] > 0): ?>
                            <?php echo $stats['computers_offline']; ?> computer(s) offline.
                        <?php endif; ?>
                        <?php if ($stats['pending_tickets'] > 0): ?>
                            <?php echo $stats['pending_tickets']; ?> pending support ticket(s).
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Network Status -->
            <div class="network-status">
                <div class="status-dot <?php echo ($stats['network_devices_offline'] == 0) ? 'online' : 'offline'; ?>"></div>
                <div>
                    <strong>Network Status:</strong> 
                    <?php echo ($stats['network_devices_offline'] == 0) ? 'All systems operational' : $stats['network_devices_offline'] . ' device(s) offline'; ?>
                    | Internet Uptime: <?php echo $stats['internet_uptime']; ?>%
                </div>
            </div>
            
            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-desktop"></i></div>
                    <div class="stat-label">Total Computers</div>
                    <div class="stat-value"><?php echo $stats['total_computers']; ?></div>
                    <div class="stat-change"><i class="fas fa-server"></i> Lab Equipment</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-label">Computers Online</div>
                    <div class="stat-value"><?php echo $stats['computers_online']; ?></div>
                    <div class="stat-change"><?php echo $stats['total_computers'] > 0 ? round(($stats['computers_online'] / $stats['total_computers']) * 100) : 0; ?>% availability</div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-label">Computers Offline</div>
                    <div class="stat-value"><?php echo $stats['computers_offline']; ?></div>
                    <div class="stat-change">Requires attention</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    <div class="stat-label">Under Maintenance</div>
                    <div class="stat-value"><?php echo $stats['computers_under_maintenance']; ?></div>
                    <div class="stat-change">In progress</div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-label">Active Sessions</div>
                    <div class="stat-value"><?php echo $stats['active_sessions']; ?></div>
                    <div class="stat-change">Today's bookings</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-label">Pending Bookings</div>
                    <div class="stat-value"><?php echo $stats['pending_bookings']; ?></div>
                    <div class="stat-change">Awaiting approval</div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="fas fa-headset"></i></div>
                    <div class="stat-label">Support Tickets</div>
                    <div class="stat-value"><?php echo $stats['pending_tickets']; ?></div>
                    <div class="stat-change">Open/In Progress</div>
                </div>
                
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-download"></i></div>
                    <div class="stat-label">Software Updates</div>
                    <div class="stat-value"><?php echo $stats['software_updates_pending']; ?></div>
                    <div class="stat-change">Pending deployment</div>
                </div>
            </div>
            
            <!-- Lab Status Overview -->
            <h2 class="section-title"><i class="fas fa-chart-pie"></i> Lab Status Overview</h2>
            <div class="lab-status-grid">
                <div class="lab-status-item online">
                    <div class="status-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="status-label">Online</div>
                    <div class="status-count"><?php echo $stats['computers_online']; ?></div>
                </div>
                <div class="lab-status-item offline">
                    <div class="status-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="status-label">Offline</div>
                    <div class="status-count"><?php echo $stats['computers_offline']; ?></div>
                </div>
                <div class="lab-status-item maintenance">
                    <div class="status-icon"><i class="fas fa-wrench"></i></div>
                    <div class="status-label">Maintenance</div>
                    <div class="status-count"><?php echo $stats['computers_under_maintenance']; ?></div>
                </div>
            </div>
            
            <!-- Recent Lab Bookings -->
            <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Recent Lab Bookings</h2>
            <div class="table-container">
                <?php if (!empty($recent_bookings)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Course</th>
                            <th>Instructor</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['instructor_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                            <td><?php echo htmlspecialchars($booking['time_slot']); ?></td>
                            <td><?php echo htmlspecialchars($booking['number_of_students']); ?></td>
                            <td>
                                <?php
                                $status = $booking['status'];
                                $badge_class = 'badge-info';
                                if ($status === 'confirmed') {
                                    $badge_class = 'badge-success';
                                } elseif ($status === 'cancelled') {
                                    $badge_class = 'badge-danger';
                                } elseif ($status === 'pending') {
                                    $badge_class = 'badge-warning';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(htmlspecialchars($status)); ?></span>
                            </td>
                            <td>
                                <a href="#" style="color: var(--primary); text-decoration: none;" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-calendar-times"></i>
                    <p>No recent bookings</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Pending IT Support Tickets -->
            <h2 class="section-title"><i class="fas fa-headset"></i> Pending IT Support Tickets</h2>
            <div class="table-container">
                <?php if (!empty($pending_tickets)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Requester</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_tickets as $ticket): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ticket['ticket_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ticket['requester_name']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($ticket['issue_type'])); ?></td>
                            <td>
                                <span class="priority-<?php echo htmlspecialchars($ticket['priority']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($ticket['priority'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(substr($ticket['description'], 0, 50)) . '...'; ?></td>
                            <td>
                                <?php
                                $status = $ticket['status'];
                                $badge_class = $status === 'open' ? 'badge-danger' : 'badge-warning';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(htmlspecialchars($status)); ?></span>
                            </td>
                            <td><?php echo date('M d, H:i', strtotime($ticket['created_at'])); ?></td>
                            <td>
                                <a href="#" style="color: var(--primary); text-decoration: none;" title="View Ticket">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" style="color: var(--success); text-decoration: none; margin-left: 8px;" title="Resolve">
                                    <i class="fas fa-check"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-check-circle"></i>
                    <p>No pending support tickets</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Computers Needing Maintenance -->
            <h2 class="section-title"><i class="fas fa-tools"></i> Computers Requiring Attention</h2>
            <div class="table-container">
                <?php if (!empty($maintenance_computers)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Computer ID</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Last Maintenance</th>
                            <th>Issues</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenance_computers as $computer): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($computer['computer_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($computer['computer_name']); ?></td>
                            <td><?php echo htmlspecialchars($computer['location']); ?></td>
                            <td>
                                <?php
                                $status = $computer['status'];
                                $badge_class = $status === 'online' ? 'badge-success' : ($status === 'maintenance' ? 'badge-warning' : 'badge-danger');
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(htmlspecialchars($status)); ?></span>
                            </td>
                            <td><?php echo $computer['last_maintenance'] ? date('M d, Y', strtotime($computer['last_maintenance'])) : 'Never'; ?></td>
                            <td><?php echo htmlspecialchars($computer['issues_reported'] ?? 'None'); ?></td>
                            <td>
                                <a href="#" style="color: var(--warning); text-decoration: none;" title="Schedule Maintenance">
                                    <i class="fas fa-calendar-plus"></i>
                                </a>
                                <a href="#" style="color: var(--primary); text-decoration: none; margin-left: 8px;" title="View Details">
                                    <i class="fas fa-info-circle"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-check-circle"></i>
                    <p>All computers are operational</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        function createNewTicket() {
            alert('Opening new support ticket form...');
            // In production, this would open a modal or redirect to a ticket creation page
        }
        
        function showNetworkStatus() {
            alert('Network monitoring dashboard coming soon!');
            // In production, this would open a detailed network status page
        }
        
        function viewComputerDetails(computerId) {
            alert('Viewing details for computer: ' + computerId);
            // In production, this would open a detailed view of the computer
        }
        
        // Auto-refresh stats every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>