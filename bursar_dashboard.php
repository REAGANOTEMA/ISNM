<?php
/**
 * Bursar Portal - Professional Dashboard
 * Financial Management System for ISNM
 */

require_once 'auth-service.php';

// Check authentication
if (!$auth_service->isAuthenticated() || $_SESSION['type'] !== 'staff') {
    $_SESSION['error'] = "Access denied. Bursar privileges required.";
    header('Location: staff-login.php');
    exit;
}

require_once 'config/database.php';

$conn = getStudentsConnection();
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get dashboard statistics
$stats = array(
    'today_collection' => 0,
    'outstanding_fees' => 0,
    'students_cleared' => 0,
    'students_not_cleared' => 0,
    'total_pending_invoices' => 0,
    'total_overdue_invoices' => 0,
    'pending_approvals' => 0
);

try {
    // Today's collection
    $result = $conn->query("
        SELECT COALESCE(SUM(amount_received), 0) as total 
        FROM payments 
        WHERE DATE(payment_date) = CURDATE() AND status IN ('verified', 'approved')
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['today_collection'] = $row['total'];
    }
    
    // Outstanding fees (unpaid + partial)
    $result = $conn->query("
        SELECT COALESCE(SUM(balance), 0) as total 
        FROM student_invoices 
        WHERE status IN ('pending', 'partial', 'overdue')
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['outstanding_fees'] = $row['total'];
    }
    
    // Students cleared (all invoices paid)
    $result = $conn->query("
        SELECT COUNT(DISTINCT student_id) as count 
        FROM student_invoices 
        WHERE status = 'paid'
        AND student_id NOT IN (
            SELECT DISTINCT student_id FROM student_invoices WHERE status IN ('pending', 'partial', 'overdue')
        )
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['students_cleared'] = $row['count'];
    }
    
    // Students with pending/overdue
    $result = $conn->query("
        SELECT COUNT(DISTINCT student_id) as count 
        FROM student_invoices 
        WHERE status IN ('pending', 'partial', 'overdue')
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['students_not_cleared'] = $row['count'];
    }
    
    // Pending invoices
    $result = $conn->query("SELECT COUNT(*) as count FROM student_invoices WHERE status = 'pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_pending_invoices'] = $row['count'];
    }
    
    // Overdue invoices
    $result = $conn->query("
        SELECT COUNT(*) as count FROM student_invoices 
        WHERE status = 'overdue' AND due_date < CURDATE()
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_overdue_invoices'] = $row['count'];
    }
    
    // Pending approvals
    $result = $conn->query("
        SELECT COUNT(*) as count FROM payments WHERE status = 'pending'
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_approvals'] = (int)($row['count'] ?? 0);
    }
    $result2 = $conn->query("
        SELECT COUNT(*) as count FROM fee_adjustments WHERE status = 'pending'
    ");
    if ($result2) {
        $row2 = $result2->fetch_assoc();
        $stats['pending_approvals'] += (int)($row2['count'] ?? 0);
    }
    
} catch (Exception $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}

// Get recent transactions
$recent_transactions = array();
try {
    $result = $conn->query("
        SELECT 
            p.id,
            p.payment_reference,
            p.student_index_number,
            si.student_name,
            p.amount_received,
            p.payment_method,
            p.payment_date,
            p.status
        FROM payments p
        JOIN students s ON p.student_id = s.student_id
        ORDER BY p.payment_date DESC
        LIMIT 10
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_transactions[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Recent transactions error: ' . $e->getMessage());
}

// Fallback: if no transactions found, join with student_invoices
if (empty($recent_transactions)) {
    try {
        $result = $conn->query("
            SELECT 
                p.id,
                p.payment_reference,
                p.student_index_number,
                p.student_id,
                'Unknown Student' as student_name,
                p.amount_received,
                p.payment_method,
                p.payment_date,
                p.status
            FROM payments p
            ORDER BY p.payment_date DESC
            LIMIT 10
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_transactions[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log('Recent transactions fallback error: ' . $e->getMessage());
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bursar Dashboard - ISNM Financial Management System</title>
    <link rel="icon" type="image/png" href="images/school-logo.png">
    <link rel="shortcut icon" type="image/png" href="images/school-logo.png">
    <link rel="apple-touch-icon" href="images/school-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
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
        
        .btn-primary, .btn-secondary, .btn-danger {
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
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
            background: rgba(102, 126, 234, 0.1);
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
        
        /* Transactions Table */
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
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
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
        }
        
        /* Alerts */
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
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-icon">💼</div>
                <h2>Bursar Portal</h2>
                <p>Financial Management</p>
            </div>
            
            <nav class="sidebar-menu">
                <li><a href="bursar_dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="bursar_student_fees.php"><i class="fas fa-graduation-cap"></i> Student Fees</a></li>
                <li><a href="bursar_invoices.php"><i class="fas fa-file-invoice"></i> Invoices</a></li>
                <li><a href="bursar_payments.php"><i class="fas fa-money-bill-wave"></i> Payments</a></li>
                <li><a href="bursar_receipts.php"><i class="fas fa-receipt"></i> Receipts</a></li>
                <li><a href="bursar_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="bursar_budgets.php"><i class="fas fa-calculator"></i> Budgets</a></li>
                <li><a href="bursar_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                    <span><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                </div>
                <form action="logout.php" method="POST" id="logoutForm" style="display:none;"></form>
                <button class="btn-logout" onclick="document.getElementById('logoutForm').submit();">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1>Financial Dashboard</h1>
                <div class="top-bar-right">
                    <button class="btn-secondary" onclick="generateReport()">
                        <i class="fas fa-file-pdf"></i> Export Report
                    </button>
                    <button class="btn-primary" onclick="recordPayment()">
                        <i class="fas fa-plus"></i> Record Payment
                    </button>
                </div>
            </div>
            
            <?php if ($stats['total_overdue_invoices'] > 0): ?>
            <div class="alert-banner">
                <i class="fas fa-exclamation-circle"></i>
                <div class="alert-banner-content">
                    <strong>⚠️ Attention Required</strong>
                    <p>You have <?php echo $stats['total_overdue_invoices']; ?> overdue invoice(s) requiring immediate attention.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-label">Today's Collection</div>
                    <div class="stat-value">UGX <?php echo number_format($stats['today_collection'], 0); ?></div>
                    <div class="stat-change"><i class="fas fa-arrow-up"></i> Updated now</div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-label">Outstanding Fees</div>
                    <div class="stat-value">UGX <?php echo number_format($stats['outstanding_fees'], 0); ?></div>
                    <div class="stat-change"><?php echo $stats['students_not_cleared']; ?> students</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-label">Students Cleared</div>
                    <div class="stat-value"><?php echo $stats['students_cleared']; ?></div>
                    <div class="stat-change">Fully paid</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-label">Pending Invoices</div>
                    <div class="stat-value"><?php echo $stats['total_pending_invoices']; ?></div>
                    <div class="stat-change">Awaiting payment</div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="fas fa-hourglass-end"></i></div>
                    <div class="stat-label">Overdue Invoices</div>
                    <div class="stat-value"><?php echo $stats['total_overdue_invoices']; ?></div>
                    <div class="stat-change">Past due date</div>
                </div>
                
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                    <div class="stat-label">Pending Approvals</div>
                    <div class="stat-value"><?php echo $stats['pending_approvals']; ?></div>
                    <div class="stat-change">Awaiting review</div>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <h2 class="section-title"><i class="fas fa-history"></i> Recent Transactions</h2>
            <div class="table-container">
                <?php if (!empty($recent_transactions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Student</th>
                            <th>Index Number</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $transaction): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($transaction['payment_reference']); ?></strong></td>
                            <td><?php echo htmlspecialchars($transaction['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['student_index_number']); ?></td>
                            <td>UGX <?php echo number_format($transaction['amount_received'], 0); ?></td>
                            <td>
                                <?php 
                                    $provider = $transaction['payment_method'] ?? '';
                                    $logo_path = '';
                                    $logo_map = [
                                        'mobile_money' => '../images/mtn-logo.svg',
                                        'momo' => '../images/mtn-logo.svg',
                                        'mtn' => '../images/mtn-logo.svg',
                                        'airtel_money' => '../images/airtel-logo.svg',
                                        'airtel' => '../images/airtel-logo.svg',
                                        'bank_deposit' => '../images/bank-default.svg',
                                        'bank_transfer' => '../images/bank-default.svg',
                                        'cash' => '../images/bank-default.svg',
                                        'cheque' => '../images/bank-default.svg',
                                    ];
                                    $logo_path = $logo_map[strtolower($provider)] ?? '../images/bank-default.svg';
                                ?>
                                <?php if (file_exists($logo_path)): ?>
                                    <img src="<?php echo $logo_path; ?>" alt="<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $provider))); ?>" style="height: 20px; vertical-align: middle; margin-right: 6px; border-radius: 3px;">
                                <?php endif; ?>
                                <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($transaction['payment_method']))); ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($transaction['payment_date'])); ?></td>
                            <td>
                                <?php
                                $status = $transaction['status'];
                                $badge_class = 'badge-info';
                                if ($status === 'verified' || $status === 'approved') {
                                    $badge_class = 'badge-success';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(htmlspecialchars($status)); ?></span>
                            </td>
                            <td>
                                <a href="bursar_payment_detail.php?id=<?php echo $transaction['id']; ?>" style="color: var(--primary); text-decoration: none;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No recent transactions</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'bursar_logout.php';
            }
        }
        
        function recordPayment() {
            window.location.href = 'bursar_payments.php?action=new';
        }
        
        function generateReport() {
            alert('Report generation feature coming soon!');
        }
    </script>
</body>
</html>
