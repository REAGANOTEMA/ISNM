<?php
include_once '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'School Bursar') {
    header('Location: ../staff-login.php');
    exit();
}

// Database connection is already established in config.php
global $conn;

// Get user information
$username = $_SESSION['username'] ?? $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$user_id = $user['id'] ?? 0;

// Get financial statistics (using fallback data only)
$today_collections = 2500000; // Fallback value (UGX)
$week_collections = 12500000; // Fallback value (UGX)
$month_collections = 45000000; // Fallback value (UGX)
$outstanding_fees = 12000000; // Fallback value (UGX)
$total_students = 150; // Fallback value
$cleared_students = 125; // Fallback value
$not_cleared_students = 25; // Fallback value

// Get recent transactions (using fallback data)
$recent_transactions = [
    ['first_name' => 'Alice', 'last_name' => 'Student', 'student_id' => 'U001/CM/056/16', 'amount_paid' => 500000, 'payment_date' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['first_name' => 'Bob', 'last_name' => 'Student', 'student_id' => 'U002/CM/057/16', 'amount_paid' => 750000, 'payment_date' => date('Y-m-d H:i:s', strtotime('-4 hours'))],
    ['first_name' => 'Carol', 'last_name' => 'Student', 'student_id' => 'U003/CM/058/16', 'amount_paid' => 500000, 'payment_date' => date('Y-m-d H:i:s', strtotime('-6 hours'))]
];

// Get budget information (using fallback data)
$budgets = [
    ['category' => 'Academic', 'allocated' => 25000000, 'spent' => 18000000, 'remaining' => 7000000],
    ['category' => 'Administrative', 'allocated' => 15000000, 'spent' => 12000000, 'remaining' => 3000000],
    ['category' => 'Operations', 'allocated' => 10000000, 'spent' => 8500000, 'remaining' => 1500000]
];

$expenses = [
    ['description' => 'Staff salaries', 'amount' => 5000000, 'expense_date' => date('Y-m-d', strtotime('-1 day'))],
    ['description' => 'Utilities', 'amount' => 500000, 'expense_date' => date('Y-m-d', strtotime('-2 days'))],
    ['description' => 'Supplies', 'amount' => 750000, 'expense_date' => date('Y-m-d', strtotime('-3 days'))]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Bursar Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>ISNM Management</h4>
                <small><?php echo ($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? $user['last_name'] ?? ''); ?></small>
                <span class="badge bg-success">School Bursar</span>
            </div>
            
            <nav class="sidebar-menu">
                <a href="#overview" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard Overview
                </a>
                <a href="#billing" class="nav-link">
                    <i class="fas fa-file-invoice"></i> Student Billing
                </a>
                <a href="#payments" class="nav-link">
                    <i class="fas fa-money-bill-wave"></i> Payment Processing
                </a>
                <a href="#reports" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Financial Reports
                </a>
                <a href="#budget" class="nav-link">
                    <i class="fas fa-wallet"></i> Budget Management
                </a>
                <a href="#expenses" class="nav-link">
                    <i class="fas fa-receipt"></i> Expenditure
                </a>
                <a href="#fees" class="nav-link">
                    <i class="fas fa-coins"></i> Fee Structure
                </a>
                <a href="#scholarships" class="nav-link">
                    <i class="fas fa-graduation-cap"></i> Scholarships
                </a>
                <a href="#communications" class="nav-link">
                    <i class="fas fa-envelope"></i> Fee Reminders
                </a>
                <a href="#payroll" class="nav-link">
                    <i class="fas fa-users"></i> Payroll Management
                </a>
                <a href="#ura-reporting" class="nav-link">
                    <i class="fas fa-file-invoice-dollar"></i> URA Reporting
                </a>
                <a href="#accounts" class="nav-link">
                    <i class="fas fa-book"></i> Accounts & Ledger
                </a>
                <a href="#inventory" class="nav-link">
                    <i class="fas fa-boxes"></i> Asset Tracking
                </a>
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
                    <h1>School Bursar Dashboard</h1>
                    <p>Financial Management System - Iganga School of Nursing and Midwifery</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo $_SESSION['user_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Financial Overview -->
                <section id="overview" class="content-section">
                    <h2>Financial Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($today_collections); ?></h3>
                                <p>Today's Collections</p>
                                <small><i class="fas fa-arrow-up text-success"></i> 15% from yesterday</small>
                            </div>
                        </div>
                        
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($week_collections); ?></h3>
                                <p>Weekly Collections</p>
                                <small><i class="fas fa-arrow-up text-success"></i> 8% from last week</small>
                            </div>
                        </div>
                        
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($month_collections); ?></h3>
                                <p>Monthly Collections</p>
                                <small><i class="fas fa-arrow-up text-success"></i> 12% from last month</small>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($outstanding_fees); ?></h3>
                                <p>Outstanding Fees</p>
                                <small><i class="fas fa-arrow-down text-danger"></i> 5% from last month</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Fee Status -->
                    <div class="fee-status-overview">
                        <h3>Student Fee Status</h3>
                        <div class="fee-status-grid">
                            <div class="fee-stat">
                                <div class="fee-stat-header">
                                    <span>Total Students</span>
                                    <h4><?php echo number_format($total_students); ?></h4>
                                </div>
                                <div class="fee-stat-bar">
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="fee-stat">
                                <div class="fee-stat-header">
                                    <span>Cleared Students</span>
                                    <h4><?php echo number_format($cleared_students); ?></h4>
                                </div>
                                <div class="fee-stat-bar">
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?php echo ($cleared_students / $total_students) * 100; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="fee-stat">
                                <div class="fee-stat-header">
                                    <span>Not Cleared</span>
                                    <h4><?php echo number_format($not_cleared_students); ?></h4>
                                </div>
                                <div class="fee-stat-bar">
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" style="width: <?php echo ($not_cleared_students / $total_students) * 100; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Student Billing Management -->
                <section id="billing" class="content-section">
                    <h2>Student Billing & Fees Management</h2>
                    <div class="billing-actions">
                        <button class="btn btn-primary" onclick="openModal('setupFees')">
                            <i class="fas fa-plus"></i> Setup Fee Structure
                        </button>
                        <button class="btn btn-success" onclick="openModal('assignFees')">
                            <i class="fas fa-user-tag"></i> Assign Fees to Students
                        </button>
                        <button class="btn btn-info" onclick="openModal('generateInvoices')">
                            <i class="fas fa-file-invoice"></i> Generate Invoices
                        </button>
                        <button class="btn btn-warning" onclick="openModal('adjustFees')">
                            <i class="fas fa-edit"></i> Adjust Fees
                        </button>
                        <button class="btn btn-secondary" onclick="openModal('penaltyConfig')">
                            <i class="fas fa-exclamation-triangle"></i> Penalty Configuration
                        </button>
                        <button class="btn btn-outline-success" onclick="openModal('scholarshipManagement')">
                            <i class="fas fa-graduation-cap"></i> Scholarship Management
                        </button>
                    </div>
                    
                    <!-- Fee Structure Table -->
                    <div class="fee-structure-table">
                        <h3>Current Fee Structures</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Program</th>
                                        <th>Level</th>
                                        <th>Year</th>
                                        <th>Semester</th>
                                        <th>Tuition</th>
                                        <th>Accommodation</th>
                                        <th>Other Fees</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Certificate in Nursing</td>
                                        <td>Certificate</td>
                                        <td>1</td>
                                        <td>1</td>
                                        <td>UGX 1,200,000</td>
                                        <td>UGX 800,000</td>
                                        <td>UGX 400,000</td>
                                        <td>UGX 2,400,000</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Edit</button>
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Certificate in Midwifery</td>
                                        <td>Certificate</td>
                                        <td>1</td>
                                        <td>1</td>
                                        <td>UGX 1,200,000</td>
                                        <td>UGX 800,000</td>
                                        <td>UGX 400,000</td>
                                        <td>UGX 2,400,000</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Edit</button>
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- Payment Processing -->
                <section id="payments" class="content-section">
                    <h2>Payment Processing</h2>
                    <div class="payment-actions">
                        <button class="btn btn-primary" onclick="openModal('recordPayment')">
                            <i class="fas fa-plus"></i> Record Payment
                        </button>
                        <button class="btn btn-success" onclick="openModal('verifyPayments')">
                            <i class="fas fa-check"></i> Verify Payments
                        </button>
                        <button class="btn btn-info" onclick="openModal('generateReceipts')">
                            <i class="fas fa-receipt"></i> Generate Receipts
                        </button>
                        <button class="btn btn-warning" onclick="openModal('paymentReport')">
                            <i class="fas fa-chart-line"></i> Payment Report
                        </button>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <div class="recent-transactions">
                        <h3>Recent Transactions</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo $transaction['payment_id']; ?></td>
                                        <td><?php echo $transaction['first_name'] . ' ' . $transaction['last_name']; ?></td>
                                        <td><?php echo $transaction['student_id']; ?></td>
                                        <td>UGX <?php echo number_format($transaction['amount_paid']); ?></td>
                                        <td>
                                            <span class="payment-method">
                                                <?php 
                                                $method_icons = [
                                                    'cash' => 'fas fa-money-bill',
                                                    'mobile_money' => 'fas fa-mobile-alt',
                                                    'bank_deposit' => 'fas fa-university',
                                                    'cheque' => 'fas fa-money-check'
                                                ];
                                                echo '<i class="' . ($method_icons[$transaction['payment_method']] ?? 'fas fa-question') . '"></i> ' . ucfirst(str_replace('_', ' ', $transaction['payment_method']));
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($transaction['payment_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $transaction['status']; ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <?php if ($transaction['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-success">Verify</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- Budget Management -->
                <section id="budget" class="content-section">
                    <h2>Budget Management</h2>
                    <div class="budget-actions">
                        <button class="btn btn-primary" onclick="openModal('createBudget')">
                            <i class="fas fa-plus"></i> Create Budget
                        </button>
                        <button class="btn btn-success" onclick="openModal('allocateBudget')">
                            <i class="fas fa-random"></i> Allocate Funds
                        </button>
                        <button class="btn btn-info" onclick="openModal('budgetReport')">
                            <i class="fas fa-chart-pie"></i> Budget Report
                        </button>
                    </div>
                    
                    <!-- Budget Overview -->
                    <div class="budget-overview">
                        <h3>Current Budgets</h3>
                        <div class="budget-grid">
                            <?php foreach ($budgets as $budget): ?>
                            <div class="budget-card">
                                <div class="budget-header">
                                    <h4><?php echo $budget['budget_name']; ?></h4>
                                    <span class="budget-status <?php echo $budget['status']; ?>">
                                        <?php echo ucfirst($budget['status']); ?>
                                    </span>
                                </div>
                                <div class="budget-details">
                                    <div class="budget-item">
                                        <span>Total Budget:</span>
                                        <strong>UGX <?php echo number_format($budget['total_budget_amount']); ?></strong>
                                    </div>
                                    <div class="budget-item">
                                        <span>Allocated:</span>
                                        <strong>UGX <?php echo number_format($budget['allocated_amount']); ?></strong>
                                    </div>
                                    <div class="budget-item">
                                        <span>Spent:</span>
                                        <strong>UGX <?php echo number_format($budget['spent_amount']); ?></strong>
                                    </div>
                                    <div class="budget-item">
                                        <span>Remaining:</span>
                                        <strong class="text-success">UGX <?php echo number_format($budget['remaining_amount']); ?></strong>
                                    </div>
                                </div>
                                <div class="budget-progress">
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo ($budget['spent_amount'] / $budget['total_budget_amount']) * 100; ?>%"></div>
                                    </div>
                                    <small><?php echo round(($budget['spent_amount'] / $budget['total_budget_amount']) * 100, 1); ?>% spent</small>
                                </div>
                                <div class="budget-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-warning">Edit</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                
                <!-- Expenditure Management -->
                <section id="expenses" class="content-section">
                    <h2>Expenditure Management</h2>
                    <div class="expense-actions">
                        <button class="btn btn-primary" onclick="openModal('addExpense')">
                            <i class="fas fa-plus"></i> Add Expense
                        </button>
                        <button class="btn btn-success" onclick="openModal('approveExpense')">
                            <i class="fas fa-check"></i> Approve Expenses
                        </button>
                        <button class="btn btn-info" onclick="openModal('expenseReport')">
                            <i class="fas fa-chart-line"></i> Expense Report
                        </button>
                    </div>
                    
                    <!-- Recent Expenses -->
                    <div class="recent-expenses">
                        <h3>Recent Expenses</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Expense ID</th>
                                        <th>Description</th>
                                        <th>Category</th>
                                        <th>Department</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo $expense['expense_id']; ?></td>
                                        <td><?php echo $expense['expense_description']; ?></td>
                                        <td><?php echo $expense['expense_category']; ?></td>
                                        <td><?php echo $expense['department']; ?></td>
                                        <td>UGX <?php echo number_format($expense['amount']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $expense['status']; ?>">
                                                <?php echo ucfirst($expense['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <?php if ($expense['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- Budgeting & Expenditure Management -->
                <section id="budgeting" class="content-section">
                    <h2>Budgeting & Expenditure Management</h2>
                    <div class="budget-actions">
                        <button class="btn btn-primary" onclick="openModal('createBudget')">
                            <i class="fas fa-plus"></i> Create Budget
                        </button>
                        <button class="btn btn-success" onclick="openModal('allocateBudget')">
                            <i class="fas fa-random"></i> Allocate Funds
                        </button>
                        <button class="btn btn-info" onclick="openModal('budgetReport')">
                            <i class="fas fa-chart-pie"></i> Budget Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('addExpense')">
                            <i class="fas fa-receipt"></i> Add Expense
                        </button>
                        <button class="btn btn-secondary" onclick="openModal('approveExpense')">
                            <i class="fas fa-check"></i> Approve Expenses
                        </button>
                    </div>
                    
                    <!-- Budget Overview -->
                    <div class="budget-overview">
                        <h3>Current Budgets</h3>
                        <div class="budget-grid">
                            <?php foreach ($budgets as $budget): ?>
                            <div class="budget-card">
                                <div class="budget-header">
                                    <h4><?php echo $budget['budget_name']; ?></h4>
                                    <span class="budget-status <?php echo $budget['status']; ?>">
                                        <?php echo ucfirst($budget['status']); ?>
                                    </span>
                                </div>
                                <div class="budget-details">
                                    <div class="budget-item">
                                        <span>Total Budget:</span>
                                        <strong>UGX <?php echo number_format($budget['total_budget_amount']); ?></strong>
                                    </div>
                                    <div class="budget-item">
                                        <span>Allocated:</span>
                                        <strong>UGX <?php echo number_format($budget['allocated_amount']); ?></strong>
                                    </div>
                                    <div class="budget-item">
                                        <span>Spent:</span>
                                        <strong>UGX <?php echo number_format($budget['spent_amount']); ?></strong>
                                    </div>
                                    <div class="budget-item">
                                        <span>Remaining:</span>
                                        <strong class="text-success">UGX <?php echo number_format($budget['remaining_amount']); ?></strong>
                                    </div>
                                </div>
                                <div class="budget-progress">
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo ($budget['spent_amount'] / $budget['total_budget_amount']) * 100; ?>%"></div>
                                    </div>
                                    <small><?php echo round(($budget['spent_amount'] / $budget['total_budget_amount']) * 100, 1); ?>% spent</small>
                                </div>
                                <div class="budget-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-warning">Edit</button>
                                    <button class="btn btn-sm btn-outline-info">Budget vs Actual</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Expenses -->
                    <div class="recent-expenses">
                        <h3>Recent Expenditures</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Expense ID</th>
                                        <th>Description</th>
                                        <th>Category</th>
                                        <th>Department</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo $expense['expense_id']; ?></td>
                                        <td><?php echo $expense['expense_description']; ?></td>
                                        <td><?php echo $expense['expense_category']; ?></td>
                                        <td><?php echo $expense['department']; ?></td>
                                        <td>UGX <?php echo number_format($expense['amount']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $expense['status']; ?>">
                                                <?php echo ucfirst($expense['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <?php if ($expense['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Accounts & Ledger Management -->
                <section id="accounts" class="content-section">
                    <h2>Accounts & Ledger Management</h2>
                    <div class="accounts-actions">
                        <button class="btn btn-primary" onclick="openModal('generalLedger')">
                            <i class="fas fa-book"></i> General Ledger
                        </button>
                        <button class="btn btn-success" onclick="openModal('trialBalance')">
                            <i class="fas fa-balance-scale"></i> Trial Balance
                        </button>
                        <button class="btn btn-info" onclick="openModal('incomeStatement')">
                            <i class="fas fa-chart-line"></i> Income Statement
                        </button>
                        <button class="btn btn-warning" onclick="openModal('cashbook')">
                            <i class="fas fa-money-check"></i> Cashbook Management
                        </button>
                        <button class="btn btn-secondary" onclick="openModal('bankReconciliation')">
                            <i class="fas fa-university"></i> Bank Reconciliation
                        </button>
                    </div>
                    
                    <!-- Financial Summary -->
                    <div class="financial-summary">
                        <h3>Financial Overview</h3>
                        <div class="summary-grid">
                            <div class="summary-card">
                                <div class="summary-icon">
                                    <i class="fas fa-arrow-up text-success"></i>
                                </div>
                                <div class="summary-content">
                                    <h4>Total Income</h4>
                                    <h5>UGX <?php echo number_format($total_collections); ?></h5>
                                    <small>Current fiscal year</small>
                                </div>
                            </div>
                            
                            <div class="summary-card">
                                <div class="summary-icon">
                                    <i class="fas fa-arrow-down text-danger"></i>
                                </div>
                                <div class="summary-content">
                                    <h4>Total Expenses</h4>
                                    <h5>UGX <?php echo number_format($conn->query("SELECT SUM(amount) as total FROM expenses WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0); ?></h5>
                                    <small>Current fiscal year</small>
                                </div>
                            </div>
                            
                            <div class="summary-card">
                                <div class="summary-icon">
                                    <i class="fas fa-piggy-bank text-warning"></i>
                                </div>
                                <div class="summary-content">
                                    <h4>Cash Balance</h4>
                                    <h5>UGX <?php echo number_format($total_collections - ($conn->query("SELECT SUM(amount) as total FROM expenses WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0)); ?></h5>
                                    <small>Available funds</small>
                                </div>
                            </div>
                            
                            <div class="summary-card">
                                <div class="summary-icon">
                                    <i class="fas fa-users text-info"></i>
                                </div>
                                <div class="summary-content">
                                    <h4>Outstanding Fees</h4>
                                    <h5>UGX <?php echo number_format($outstanding_fees); ?></h5>
                                    <small>Student receivables</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Payroll Integration -->
                <section id="payroll" class="content-section">
                    <h2>Payroll Management</h2>
                    <div class="payroll-actions">
                        <button class="btn btn-primary" onclick="openModal('staffPayroll')">
                            <i class="fas fa-users"></i> Staff Payroll
                        </button>
                        <button class="btn btn-success" onclick="openModal('generatePayslips')">
                            <i class="fas fa-file-invoice"></i> Generate Payslips
                        </button>
                        <button class="btn btn-info" onclick="openModal('allowanceManagement')">
                            <i class="fas fa-gift"></i> Allowances & Deductions
                        </button>
                        <button class="btn btn-warning" onclick="openModal('payrollReport')">
                            <i class="fas fa-chart-bar"></i> Payroll Report
                        </button>
                    </div>
                    
                    <!-- Payroll Overview -->
                    <div class="payroll-overview">
                        <h3>Current Payroll Summary</h3>
                        <div class="payroll-stats">
                            <div class="payroll-stat">
                                <h4>Total Staff</h4>
                                <h5><?php echo $total_staff; ?></h5>
                                <small>Active employees</small>
                            </div>
                            <div class="payroll-stat">
                                <h4>Total Monthly Payroll</h4>
                                <h5>UGX <?php echo number_format($conn->query("SELECT SUM(monthly_salary) as total FROM users WHERE status = 'active'")->fetch_assoc()['total'] ?? 0); ?></h5>
                                <small>Monthly expense</small>
                            </div>
                            <div class="payroll-stat">
                                <h4>Total Allowances</h4>
                                <h5>UGX <?php echo number_format($conn->query("SELECT SUM(allowances) as total FROM users WHERE status = 'active'")->fetch_assoc()['total'] ?? 0); ?></h5>
                                <small>Monthly allowances</small>
                            </div>
                            <div class="payroll-stat">
                                <h4>Total Deductions</h4>
                                <h5>UGX <?php echo number_format($conn->query("SELECT SUM(deductions) as total FROM users WHERE status = 'active'")->fetch_assoc()['total'] ?? 0); ?></h5>
                                <small>Monthly deductions</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Inventory & Asset Financial Tracking -->
                <section id="inventory" class="content-section">
                    <h2>Inventory & Asset Financial Tracking</h2>
                    <div class="inventory-actions">
                        <button class="btn btn-primary" onclick="openModal('addAsset')">
                            <i class="fas fa-plus"></i> Add Asset
                        </button>
                        <button class="btn btn-success" onclick="openModal('assetRegister')">
                            <i class="fas fa-list"></i> Asset Register
                        </button>
                        <button class="btn btn-info" onclick="openModal('depreciationTracking')">
                            <i class="fas fa-chart-line"></i> Depreciation Tracking
                        </button>
                        <button class="btn btn-warning" onclick="openModal('assetReport')">
                            <i class="fas fa-file-alt"></i> Asset Report
                        </button>
                    </div>
                    
                    <!-- Asset Overview -->
                    <div class="asset-overview">
                        <h3>Asset Summary</h3>
                        <div class="asset-stats">
                            <div class="asset-stat">
                                <h4>Total Assets</h4>
                                <h5><?php echo $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'] ?? 0; ?></h5>
                                <small>Registered assets</small>
                            </div>
                            <div class="asset-stat">
                                <h4>Total Value</h4>
                                <h5>UGX <?php echo number_format($conn->query("SELECT SUM(purchase_value) as total FROM assets")->fetch_assoc()['total'] ?? 0); ?></h5>
                                <small>Asset value</small>
                            </div>
                            <div class="asset-stat">
                                <h4>Accumulated Depreciation</h4>
                                <h5>UGX <?php echo number_format($conn->query("SELECT SUM(accumulated_depreciation) as total FROM assets")->fetch_assoc()['total'] ?? 0); ?></h5>
                                <small>Total depreciation</small>
                            </div>
                            <div class="asset-stat">
                                <h4>Net Book Value</h4>
                                <h5>UGX <?php $asset_value = $conn->query("SELECT SUM(purchase_value) as total FROM assets")->fetch_assoc()['total'] ?? 0; $depreciation = $conn->query("SELECT SUM(accumulated_depreciation) as total FROM assets")->fetch_assoc()['total'] ?? 0; echo number_format($asset_value - $depreciation); ?></h5>
                                <small>Current value</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Communication Tools -->
                <section id="communication" class="content-section">
                    <h2>Communication Tools</h2>
                    <div class="communication-actions">
                        <button class="btn btn-primary" onclick="openModal('sendFeeReminders')">
                            <i class="fas fa-bell"></i> Send Fee Reminders
                        </button>
                        <button class="btn btn-success" onclick="openModal('sendNotifications')">
                            <i class="fas fa-envelope"></i> Send Notifications
                        </button>
                        <button class="btn btn-info" onclick="openModal('broadcastAnnouncements')">
                            <i class="fas fa-bullhorn"></i> Broadcast Financial Announcements
                        </button>
                        <button class="btn btn-warning" onclick="openModal('communicationReport')">
                            <i class="fas fa-chart-bar"></i> Communication Report
                        </button>
                    </div>
                    
                    <!-- Communication Overview -->
                    <div class="communication-overview">
                        <h3>Recent Communications</h3>
                        <div class="communication-stats">
                            <div class="comm-stat">
                                <h4>SMS Sent Today</h4>
                                <h5><?php echo $conn->query("SELECT COUNT(*) as count FROM communication_log WHERE type = 'sms' AND DATE(sent_date) = CURDATE()")->fetch_assoc()['count'] ?? 0; ?></h5>
                                <small>Fee reminders</small>
                            </div>
                            <div class="comm-stat">
                                <h4>Emails Sent Today</h4>
                                <h5><?php echo $conn->query("SELECT COUNT(*) as count FROM communication_log WHERE type = 'email' AND DATE(sent_date) = CURDATE()")->fetch_assoc()['count'] ?? 0; ?></h5>
                                <small>Notifications</small>
                            </div>
                            <div class="comm-stat">
                                <h4>Overdue Notices</h4>
                                <h5><?php echo $conn->query("SELECT COUNT(*) as count FROM communication_log WHERE type = 'overdue_notice' AND DATE(sent_date) = CURDATE()")->fetch_assoc()['count'] ?? 0; ?></h5>
                                <small>Sent today</small>
                            </div>
                            <div class="comm-stat">
                                <h4>Payment Confirmations</h4>
                                <h5><?php echo $conn->query("SELECT COUNT(*) as count FROM communication_log WHERE type = 'payment_confirmation' AND DATE(sent_date) = CURDATE()")->fetch_assoc()['count'] ?? 0; ?></h5>
                                <small>Sent today</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Payroll Management -->
                <section id="payroll" class="content-section">
                    <h2>Payroll Management</h2>
                    <div class="payroll-actions">
                        <button class="btn btn-primary" onclick="openModal('addSalary')">
                            <i class="fas fa-plus"></i> Add Staff Salary
                        </button>
                        <button class="btn btn-success" onclick="generatePayslips()">
                            <i class="fas fa-file-alt"></i> Generate Payslips
                        </button>
                        <button class="btn btn-info" onclick="printAllPayslips()">
                            <i class="fas fa-print"></i> Print All Receipts
                        </button>
                        <button class="btn btn-warning" onclick="openModal('allowances')">
                            <i class="fas fa-coins"></i> Allowances & Deductions
                        </button>
                        <button class="btn btn-secondary" onclick="openModal('payrollReport')">
                            <i class="fas fa-chart-bar"></i> Payroll Report
                        </button>
                        <button class="btn btn-outline-primary" onclick="emailAllPayslips()">
                            <i class="fas fa-envelope"></i> Email All Receipts
                        </button>
                    </div>
                    
                    <div class="payroll-overview">
                        <h3>Current Payroll</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Staff ID</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Basic Salary</th>
                                        <th>Allowances</th>
                                        <th>Deductions</th>
                                        <th>Net Salary</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $staff_query = "SELECT * FROM users WHERE role != 'Students' ORDER BY surname";
                                    $staff_result = $conn->query($staff_query);
                                    while ($staff = $staff_result->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td><?php echo $staff['staff_id'] ?? 'STF' . $staff['id']; ?></td>
                                        <td><?php echo $staff['first_name'] . ' ' . $staff['surname']; ?></td>
                                        <td><?php echo $staff['department'] ?? 'Administration'; ?></td>
                                        <td>UGX <?php echo number_format(rand(800000, 3000000)); ?></td>
                                        <td>UGX <?php echo number_format(rand(100000, 500000)); ?></td>
                                        <td>UGX <?php echo number_format(rand(50000, 200000)); ?></td>
                                        <td>UGX <?php echo number_format(rand(750000, 2800000)); ?></td>
                                        <td><span class="badge bg-success">Paid</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewPayslip(<?php echo $staff['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="printPayslip(<?php echo $staff['id']; ?>)">
                                                <i class="fas fa-print"></i> Print
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="downloadPayslip(<?php echo $staff['id']; ?>)">
                                                <i class="fas fa-download"></i> Download
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="emailPayslip(<?php echo $staff['id']; ?>)">
                                                <i class="fas fa-envelope"></i> Email
                                            </button>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- URA Reporting -->
                <section id="ura-reporting" class="content-section">
                    <h2>URA-Compatible Reporting</h2>
                    <div class="ura-actions">
                        <button class="btn btn-primary" onclick="generateURAReport('monthly')">
                            <i class="fas fa-file-invoice"></i> Generate Monthly URA Report
                        </button>
                        <button class="btn btn-success" onclick="generateURAReport('quarterly')">
                            <i class="fas fa-file-invoice"></i> Generate Quarterly URA Report
                        </button>
                        <button class="btn btn-info" onclick="generateURAReport('annual')">
                            <i class="fas fa-file-invoice"></i> Generate Annual URA Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('uraSettings')">
                            <i class="fas fa-cog"></i> URA Settings
                        </button>
                    </div>
                    
                    <div class="ura-overview">
                        <h3>Tax Compliance & Reporting</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="ura-card">
                                    <h4><i class="fas fa-chart-bar"></i> Revenue Summary</h4>
                                    <div class="ura-stats">
                                        <div class="stat-item">
                                            <span>Tuition Revenue:</span>
                                            <strong>UGX <?php echo number_format(rand(50000000, 100000000)); ?></strong>
                                        </div>
                                        <div class="stat-item">
                                            <span>Other Revenue:</span>
                                            <strong>UGX <?php echo number_format(rand(10000000, 30000000)); ?></strong>
                                        </div>
                                        <div class="stat-item">
                                            <span>Total Revenue:</span>
                                            <strong>UGX <?php echo number_format(rand(60000000, 130000000)); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ura-card">
                                    <h4><i class="fas fa-receipt"></i> Tax Information</h4>
                                    <div class="ura-stats">
                                        <div class="stat-item">
                                            <span>VAT (18%):</span>
                                            <strong>UGX <?php echo number_format(rand(10000000, 20000000)); ?></strong>
                                        </div>
                                        <div class="stat-item">
                                            <span>Withholding Tax:</span>
                                            <strong>UGX <?php echo number_format(rand(5000000, 15000000)); ?></strong>
                                        </div>
                                        <div class="stat-item">
                                            <span>TIN:</span>
                                            <strong>1012345678</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ura-reports-table">
                            <h4>Generated URA Reports</h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Report ID</th>
                                            <th>Report Type</th>
                                            <th>Period</th>
                                            <th>Generated Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>URA-2024-001</td>
                                            <td>Monthly VAT Return</td>
                                            <td>January 2024</td>
                                            <td>Feb 1, 2024</td>
                                            <td><span class="badge bg-success">Filed</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewURAReport('URA-2024-001')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="downloadURAReport('URA-2024-001')">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Asset Tracking -->
                <section id="inventory" class="content-section">
                    <h2>Inventory & Asset Financial Tracking</h2>
                    <div class="inventory-actions">
                        <button class="btn btn-primary" onclick="openModal('addAsset')">
                            <i class="fas fa-plus"></i> Add Asset
                        </button>
                        <button class="btn btn-success" onclick="openModal('assetCategories')">
                            <i class="fas fa-tags"></i> Categories
                        </button>
                        <button class="btn btn-info" onclick="openModal('depreciation')">
                            <i class="fas fa-chart-line"></i> Depreciation
                        </button>
                        <button class="btn btn-warning" onclick="openModal('assetReports')">
                            <i class="fas fa-file-alt"></i> Asset Reports
                        </button>
                    </div>
                    
                    <div class="inventory-overview">
                        <h3>Asset Register</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Asset ID</th>
                                        <th>Description</th>
                                        <th>Category</th>
                                        <th>Purchase Date</th>
                                        <th>Purchase Cost</th>
                                        <th>Current Value</th>
                                        <th>Depreciation</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $assets_query = "SELECT * FROM assets ORDER BY purchase_date DESC LIMIT 10";
                                    $assets_result = $conn->query($assets_query);
                                    while ($asset = $assets_result->fetch_assoc()) {
                                        $purchase_cost = $asset['purchase_cost'] ?? rand(1000000, 10000000);
                                        $depreciation = ($purchase_cost * 0.1);
                                        $current_value = $purchase_cost - $depreciation;
                                    ?>
                                    <tr>
                                        <td><?php echo $asset['asset_id'] ?? 'AST' . $asset['id']; ?></td>
                                        <td><?php echo $asset['description'] ?? 'Office Equipment'; ?></td>
                                        <td><?php echo $asset['category'] ?? 'Furniture'; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($asset['purchase_date'] ?? '2024-01-01')); ?></td>
                                        <td>UGX <?php echo number_format($purchase_cost); ?></td>
                                        <td>UGX <?php echo number_format($current_value); ?></td>
                                        <td>UGX <?php echo number_format($depreciation); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewAsset(<?php echo $asset['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="editAsset(<?php echo $asset['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Financial Reports -->
                <section id="reports" class="content-section">
                    <h2>Financial Reports & Analytics</h2>
                    <div class="reports-grid">
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h3>Daily Collections</h3>
                            <p>View daily revenue collection reports with detailed breakdowns</p>
                            <button class="btn btn-primary" onclick="generateReport('daily')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Weekly Summary</h3>
                            <p>Comprehensive weekly financial performance summary</p>
                            <button class="btn btn-primary" onclick="generateReport('weekly')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h3>Monthly Analysis</h3>
                            <p>Detailed monthly financial analysis and trends</p>
                            <button class="btn btn-primary" onclick="generateReport('monthly')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>Debtors List</h3>
                            <p>Students with outstanding fee balances</p>
                            <button class="btn btn-primary" onclick="generateReport('debtors')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <h3>Revenue Summary</h3>
                            <p>Revenue breakdown by category (tuition, hostel, etc.)</p>
                            <button class="btn btn-primary" onclick="generateReport('revenue')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3>Student Statements</h3>
                            <p>Individual student fee statements and payment history</p>
                            <button class="btn btn-primary" onclick="generateReport('statements')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <h3>Trial Balance</h3>
                            <p>Complete trial balance with all accounts</p>
                            <button class="btn btn-primary" onclick="generateReport('trial_balance')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Income Statement</h3>
                            <p>Profit and loss statement for the period</p>
                            <button class="btn btn-primary" onclick="generateReport('income_statement')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-money-check"></i>
                            </div>
                            <h3>Cash Flow Statement</h3>
                            <p>Cash inflows and outflows analysis</p>
                            <button class="btn btn-primary" onclick="generateReport('cash_flow')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <h3>URA-Compatible Reports</h3>
                            <p>Tax compliance reports for Uganda Revenue Authority</p>
                            <button class="btn btn-primary" onclick="generateReport('ura_reports')">Generate Report</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Dynamic content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modalAction">Execute</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
        
        // Modal functions
        function openModal(action) {
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            switch(action) {
                case 'setupFees':
                    modalTitle.textContent = 'Setup Fee Structure';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program</label>
                                    <select class="form-control" required>
                                        <option value="">Select Program</option>
                                        <option value="nursing-certificate">Certificate in Nursing</option>
                                        <option value="midwifery-certificate">Certificate in Midwifery</option>
                                        <option value="nursing-diploma">Diploma in Nursing</option>
                                        <option value="midwifery-diploma">Diploma in Midwifery</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Level</label>
                                    <select class="form-control" required>
                                        <option value="">Select Level</option>
                                        <option value="certificate">Certificate</option>
                                        <option value="diploma">Diploma</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Year</label>
                                    <input type="number" class="form-control" min="1" max="3" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Semester</label>
                                    <select class="form-control" required>
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Academic Year</label>
                                    <input type="text" class="form-control" value="2025/2026" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Tuition Fee</label>
                                    <input type="number" class="form-control" min="0" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Accommodation</label>
                                    <input type="number" class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Clinical Fee</label>
                                    <input type="number" class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Library Fee</label>
                                    <input type="number" class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">ICT Fee</label>
                                    <input type="number" class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Student Union</label>
                                    <input type="number" class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Medical Fee</label>
                                    <input type="number" class="form-control" min="0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Sports Fee</label>
                                    <input type="number" class="form-control" min="0">
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'recordPayment':
                    modalTitle.textContent = 'Record Payment';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Student ID</label>
                                    <input type="text" class="form-control" placeholder="Enter Student ID" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Amount Paid (UGX)</label>
                                    <input type="number" class="form-control" min="0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-control" required>
                                        <option value="">Select Method</option>
                                        <option value="cash">Cash</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="bank_deposit">Bank Deposit</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="online_transfer">Online Transfer</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Reference</label>
                                    <input type="text" class="form-control" placeholder="Transaction reference">
                                </div>
                                <div class="col-md-6 mb-3" id="bankField" style="display: none;">
                                    <label class="form-label">Bank Name</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3" id="mobileField" style="display: none;">
                                    <label class="form-label">Mobile Provider</label>
                                    <select class="form-control">
                                        <option value="mtn">MTN Mobile Money</option>
                                        <option value="airtel">Airtel Money</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                default:
                    modalTitle.textContent = 'Action';
                    modalBody.innerHTML = '<p>Action content will be loaded here.</p>';
            }
            
            modal.show();
        }
        
        function generateReport(type) {
            console.log('Generating report:', type);
            // Implementation for report generation
            alert('Report generation functionality will be implemented here.');
        }

        // URA Reporting Functions
        function generateURAReport(type) {
            console.log('Generating URA Report:', type);
            
            let reportTitle = '';
            let reportContent = '';
            
            switch(type) {
                case 'monthly':
                    reportTitle = 'Monthly URA VAT Return';
                    reportContent = `
                        <div class="ura-report-form">
                            <h4>Generate Monthly VAT Return</h4>
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Select Month</label>
                                    <select class="form-control" required>
                                        <option value="">Select Month</option>
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Year</label>
                                    <input type="number" class="form-control" value="<?php echo date('Y'); ?>" min="2020" max="<?php echo date('Y'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">TIN</label>
                                    <input type="text" class="form-control" value="1012345678" readonly>
                                </div>
                                <div class="alert alert-info">
                                    <strong>Note:</strong> This report will include all taxable transactions for the selected month.
                                </div>
                            </form>
                        </div>
                    `;
                    break;
                case 'quarterly':
                    reportTitle = 'Quarterly URA Tax Return';
                    reportContent = `
                        <div class="ura-report-form">
                            <h4>Generate Quarterly Tax Return</h4>
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Select Quarter</label>
                                    <select class="form-control" required>
                                        <option value="">Select Quarter</option>
                                        <option value="Q1">Q1 (Jan-Mar)</option>
                                        <option value="Q2">Q2 (Apr-Jun)</option>
                                        <option value="Q3">Q3 (Jul-Sep)</option>
                                        <option value="Q4">Q4 (Oct-Dec)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Year</label>
                                    <input type="number" class="form-control" value="<?php echo date('Y'); ?>" min="2020" max="<?php echo date('Y'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">TIN</label>
                                    <input type="text" class="form-control" value="1012345678" readonly>
                                </div>
                                <div class="alert alert-info">
                                    <strong>Note:</strong> This report will include all taxable transactions for the selected quarter.
                                </div>
                            </form>
                        </div>
                    `;
                    break;
                case 'annual':
                    reportTitle = 'Annual URA Tax Return';
                    reportContent = `
                        <div class="ura-report-form">
                            <h4>Generate Annual Tax Return</h4>
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Year</label>
                                    <input type="number" class="form-control" value="<?php echo date('Y'); ?>" min="2020" max="<?php echo date('Y'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">TIN</label>
                                    <input type="text" class="form-control" value="1012345678" readonly>
                                </div>
                                <div class="alert alert-info">
                                    <strong>Note:</strong> This report will include all taxable transactions for the entire year.
                                </div>
                            </form>
                        </div>
                    `;
                    break;
            }
            
            // Show modal with report generation form
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = reportTitle;
            modalBody.innerHTML = reportContent;
            modal.show();
        }

        function viewURAReport(reportId) {
            console.log('Viewing URA Report:', reportId);
            // Implementation for viewing URA report
            window.open('reports/ura/' + reportId + '.pdf', '_blank');
        }

        function downloadURAReport(reportId) {
            console.log('Downloading URA Report:', reportId);
            // Implementation for downloading URA report
            const link = document.createElement('a');
            link.href = 'reports/ura/' + reportId + '.pdf';
            link.download = reportId + '.pdf';
            link.click();
        }

        // Payroll Functions
        function generatePayslips() {
            console.log('Generating payslips for all staff');
            alert('Payslip generation functionality will be implemented here.');
        }

        function viewPayslip(staffId) {
            console.log('Viewing payslip for staff ID:', staffId);
            window.open('reports/payslips/staff_' + staffId + '.pdf', '_blank');
        }

        function printPayslip(staffId) {
            console.log('Printing payslip for staff ID:', staffId);
            
            // Generate payslip content
            const payslipContent = generatePayslipContent(staffId);
            
            // Create print window
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Payroll Receipt - ISNM</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
                        .logo { width: 80px; height: 80px; margin-bottom: 10px; }
                        .receipt-info { margin: 20px 0; }
                        .receipt-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        .receipt-table th, .receipt-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        .receipt-table th { background-color: #f2f2f2; }
                        .total-row { font-weight: bold; background-color: #f9f9f9; }
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; }
                        .signature { margin-top: 50px; }
                    </style>
                </head>
                <body>
                    ${payslipContent}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            // Wait for content to load, then print
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        function generatePayslipContent(staffId) {
            // Generate detailed payslip content with ISNM branding
            return `
                <div class="header">
                    <img src="../images/school-logo.png" alt="ISNM Logo" class="logo">
                    <h2>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2>
                    <h3>Payroll Receipt</h3>
                    <p>P.O. Box 418, Iganga, Uganda | Tel: 0782 990 403</p>
                </div>
                
                <div class="receipt-info">
                    <p><strong>Receipt Number:</strong> PR-${Date.now()}-${staffId}</p>
                    <p><strong>Payment Date:</strong> ${new Date().toLocaleDateString()}</p>
                    <p><strong>Payment Period:</strong> ${new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</p>
                    <p><strong>Staff ID:</strong> STF${staffId}</p>
                    <p><strong>Staff Name:</strong> Staff Member ${staffId}</p>
                    <p><strong>Department:</strong> Administration</p>
                </div>
                
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount (UGX)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Salary</td>
                            <td>${numberFormat(rand(800000, 3000000))}</td>
                        </tr>
                        <tr>
                            <td>Housing Allowance</td>
                            <td>${numberFormat(rand(100000, 500000))}</td>
                        </tr>
                        <tr>
                            <td>Transport Allowance</td>
                            <td>${numberFormat(rand(50000, 200000))}</td>
                        </tr>
                        <tr>
                            <td>Medical Allowance</td>
                            <td>${numberFormat(rand(50000, 150000))}</td>
                        </tr>
                        <tr>
                            <td><strong>Gross Pay</strong></td>
                            <td><strong>${numberFormat(rand(1000000, 3850000))}</strong></td>
                        </tr>
                        <tr>
                            <td>PAYE Tax</td>
                            <td>-${numberFormat(rand(100000, 500000))}</td>
                        </tr>
                        <tr>
                            <td>NSSF Contribution</td>
                            <td>-${numberFormat(rand(50000, 200000))}</td>
                        </tr>
                        <tr>
                            <td>Other Deductions</td>
                            <td>-${numberFormat(rand(20000, 100000))}</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Net Pay</strong></td>
                            <td><strong>${numberFormat(rand(750000, 2800000))}</strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="receipt-info">
                    <p><strong>Payment Method:</strong> Bank Transfer</p>
                    <p><strong>Bank Account:</strong> Centenary Bank - 3204555588</p>
                    <p><strong>Payment Status:</strong> <span style="color: green;">✓ PAID</span></p>
                </div>
                
                <div class="signature">
                    <p>_________________________</p>
                    <p><strong>Authorized Signature</strong></p>
                    <p>School Bursar</p>
                </div>
                
                <div class="footer">
                    <p><strong>"Chosen to Serve" - Disciplined Mind for Health Action</strong></p>
                    <p>This is a computer-generated receipt and does not require a signature</p>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                </div>
            `;
        }

        function numberFormat(num) {
            return new Intl.NumberFormat('en-US').format(num);
        }

        function downloadPayslip(staffId) {
            console.log('Downloading payslip for staff ID:', staffId);
            
            // Create download link
            const link = document.createElement('a');
            link.href = 'data:text/html;charset=utf-8,' + encodeURIComponent(generatePayslipContent(staffId));
            link.download = `payslip_${staffId}_${Date.now()}.html`;
            link.click();
        }

        function emailPayslip(staffId) {
            console.log('Emailing payslip for staff ID:', staffId);
            
            // Show email modal
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = 'Email Payroll Receipt';
            modalBody.innerHTML = `
                <div class="email-form">
                    <h4>Send Payroll Receipt via Email</h4>
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Recipient Email</label>
                            <input type="email" class="form-control" value="staff${staffId}@isnm.ac.ug" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" value="Payroll Receipt - ${new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" rows="4">Dear Staff Member,

Please find attached your payroll receipt for ${new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}.

If you have any questions regarding your payment, please contact the bursar's office.

Best regards,
ISNM Bursar Office</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <input type="checkbox" checked> Attach PDF receipt
                            </label>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            The payslip will be sent as a PDF attachment to the staff member's email.
                        </div>
                    </form>
                </div>
            `;
            modal.show();
        }

        function printAllPayslips() {
            console.log('Printing all payslips');
            
            // Show confirmation modal
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = 'Print All Payroll Receipts';
            modalBody.innerHTML = `
                <div class="bulk-print-form">
                    <h4>Print All Staff Payroll Receipts</h4>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        This will generate and print payroll receipts for all staff members.
                        Total staff: <strong>25</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Print Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="printAll" checked>
                            <label class="form-check-label" for="printAll">
                                Print all staff receipts
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="printDepartment">
                            <label class="form-check-label" for="printDepartment">
                                Print by department only
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="printSummary">
                            <label class="form-check-label" for="printSummary">
                                Include payroll summary
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="departmentSelect" style="display: none;">
                        <label class="form-label">Select Department</label>
                        <select class="form-control">
                            <option value="">All Departments</option>
                            <option value="academic">Academic</option>
                            <option value="administrative">Administrative</option>
                            <option value="support">Support Staff</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Make sure your printer is ready and has sufficient paper before proceeding.
                    </div>
                </div>
            `;
            modal.show();
            
            // Add event listener for department checkbox
            setTimeout(() => {
                const printDepartment = document.getElementById('printDepartment');
                const departmentSelect = document.getElementById('departmentSelect');
                
                printDepartment.addEventListener('change', function() {
                    departmentSelect.style.display = this.checked ? 'block' : 'none';
                });
            }, 100);
        }

        function emailAllPayslips() {
            console.log('Emailing all payslips');
            
            // Show email modal
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = 'Email All Payroll Receipts';
            modalBody.innerHTML = `
                <div class="bulk-email-form">
                    <h4>Send All Payroll Receipts via Email</h4>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        This will send payroll receipts to all staff members via email.
                        Total staff: <strong>25</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Subject</label>
                        <input type="text" class="form-control" value="Payroll Receipt - ${new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Message</label>
                        <textarea class="form-control" rows="4">Dear Staff Member,

Please find attached your payroll receipt for ${new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}.

If you have any questions regarding your payment, please contact the bursar's office.

Best regards,
ISNM Bursar Office</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="emailAll" checked>
                            <label class="form-check-label" for="emailAll">
                                Email all staff members
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="emailDepartment">
                            <label class="form-check-label" for="emailDepartment">
                                Email by department only
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="emailUnsent" checked>
                            <label class="form-check-label" for="emailUnsent">
                                Only send to staff who haven't received
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="emailDepartmentSelect" style="display: none;">
                        <label class="form-label">Select Department</label>
                        <select class="form-control">
                            <option value="">All Departments</option>
                            <option value="academic">Academic</option>
                            <option value="administrative">Administrative</option>
                            <option value="support">Support Staff</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This will send emails to multiple recipients. Please verify all email addresses before proceeding.
                    </div>
                </div>
            `;
            modal.show();
            
            // Add event listener for department checkbox
            setTimeout(() => {
                const emailDepartment = document.getElementById('emailDepartment');
                const emailDepartmentSelect = document.getElementById('emailDepartmentSelect');
                
                emailDepartment.addEventListener('change', function() {
                    emailDepartmentSelect.style.display = this.checked ? 'block' : 'none';
                });
            }, 100);
        }

        // Asset Functions
        function viewAsset(assetId) {
            console.log('Viewing asset ID:', assetId);
            // Implementation for viewing asset
        }

        function editAsset(assetId) {
            console.log('Editing asset ID:', assetId);
            // Implementation for editing asset
        }
        
        // Auto-refresh dashboard data
        setInterval(() => {
            // Refresh financial statistics
            console.log('Refreshing financial data...');
        }, 30000); // Every 30 seconds
    </script>
</body>
</html>
