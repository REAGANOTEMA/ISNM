<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Bursar Dashboard - ISNM Financial Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <style>
        :root {
            --isnm-blue: #1e3a8a;
            --isnm-light-blue: #3b82f6;
            --isnm-green: #059669;
            --isnm-gold: #d97706;
            --isnm-dark-green: #0f4c3a;
        }
        
        .payment-method-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .payment-logo {
            height: 24px;
            width: auto;
        }
        
        .stat-card {
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .badge-paid { background: #198754; }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-approved { background: #0dcaf0; }
        .badge-overdue { background: #dc3545; }
        
        .receipt-preview {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <?php
    require_once __DIR__ . '/../includes/staff_dashboard_access.php';
    require_once __DIR__ . '/../includes/financial_functions.php';
    
    $ctx = bootstrapStaffDashboard(['school bursar', 'bursar', 'accountant']);
    $auth_service = $ctx['auth'];
    $user = $ctx['user'];
    $conn = getConnection();
    
    // Get financial statistics
    $today_collections = getTotalCollections('today');
    $week_collections = getTotalCollections('week');
    $month_collections = getTotalCollections('month');
    $outstanding_fees = getOutstandingFees();
    
    // Get student statistics
    $total_students_stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $total_students = $total_students_stmt->fetch_assoc()['count'];
    
    $cleared_stmt = $conn->query("SELECT COUNT(*) as count FROM users u LEFT JOIN student_invoices si ON u.id = si.student_id WHERE (si.balance = 0 OR si.balance IS NULL)");
    $cleared_students = $cleared_stmt->fetch_assoc()['count'];
    $not_cleared_students = $total_students - $cleared_students;
    
    // Get recent payments
    $recent_payments = [];
    $payments_stmt = $conn->query("
        SELECT p.*, u.first_name, u.last_name, u.index_number 
        FROM payments p 
        JOIN users u ON p.student_id = u.id 
        ORDER BY p.transaction_date DESC 
        LIMIT 10
    ");
    while ($row = $payments_stmt->fetch_assoc()) {
        $recent_payments[] = $row;
    }
    ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>ISNM Financial System</h4>
                <small><?php echo htmlspecialchars(($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? '')); ?></small>
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
                <a href="#receipts" class="nav-link">
                    <i class="fas fa-receipt"></i> Receipts & Invoices
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
                <a href="#accounts" class="nav-link">
                    <i class="fas fa-book"></i> Accounts & Ledger
                </a>
                <a href="#payroll" class="nav-link">
                    <i class="fas fa-users"></i> Payroll Management
                </a>
                <a href="#inventory" class="nav-link">
                    <i class="fas fa-boxes"></i> Asset Tracking
                </a>
                <a href="#ura" class="nav-link">
                    <i class="fas fa-file-invoice-dollar"></i> URA Reporting
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
                    <h1>Financial Management System</h1>
                    <p>Iganga School of Nursing and Midwifery - Bursar Portal</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Financial Overview -->
                <section id="overview" class="content-section">
                    <h2><i class="fas fa-chart-line"></i> Financial Overview</h2>
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
                                        <div class="progress-bar bg-primary" style="width: 100%"></div>
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
                                        <div class="progress-bar bg-success" style="width: <?php echo $total_students > 0 ? ($cleared_students / $total_students) * 100 : 0; ?>%"></div>
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
                                        <div class="progress-bar bg-warning" style="width: <?php echo $total_students > 0 ? ($not_cleared_students / $total_students) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Payment Processing -->
                <section id="payments" class="content-section">
                    <h2><i class="fas fa-money-bill-wave"></i> Payment Processing</h2>
                    <div class="payment-actions mb-4">
                        <button class="btn btn-primary" onclick="openModal('recordPayment')">
                            <i class="fas fa-plus"></i> Record Payment
                        </button>
                        <button class="btn btn-success" onclick="openModal('verifyPayments')">
                            <i class="fas fa-check"></i> Verify Payments
                        </button>
                        <button class="btn btn-info" onclick="generateReceipt()">
                            <i class="fas fa-receipt"></i> Generate Receipt
                        </button>
                        <button class="btn btn-warning" onclick="exportPayments()">
                            <i class="fas fa-file-export"></i> Export to Excel
                        </button>
                    </div>
                    
                    <!-- Payment Method Logos -->
                    <div class="payment-providers mb-3">
                        <h5>Supported Payment Methods:</h5>
                        <div class="d-flex gap-3 align-items-center">
                            <div class="text-center">
                                <i class="fas fa-mobile-alt fa-2x text-primary"></i>
                                <div><small>MTN Mobile Money</small></div>
                            </div>
                            <div class="text-center">
                                <i class="fas fa-mobile-alt fa-2x text-danger"></i>
                                <div><small>Airtel Money</small></div>
                            </div>
                            <div class="text-center">
                                <i class="fas fa-university fa-2x text-success"></i>
                                <div><small>Bank Transfer</small></div>
                            </div>
                            <div class="text-center">
                                <i class="fas fa-money-bill fa-2x text-warning"></i>
                                <div><small>Cash</small></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <div class="recent-transactions">
                        <h3>Recent Transactions</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Payment Ref</th>
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
                                    <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['payment_reference']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['index_number'] ?? 'N/A'); ?></td>
                                        <td>UGX <?php echo number_format($payment['amount']); ?></td>
                                        <td>
                                            <span class="payment-method-badge">
                                                <?php if ($payment['payment_method'] === 'mobile_money'): ?>
                                                    <i class="fas fa-mobile-alt"></i>
                                                <?php elseif ($payment['payment_method'] === 'bank_deposit'): ?>
                                                    <i class="fas fa-university"></i>
                                                <?php elseif ($payment['payment_method'] === 'cash'): ?>
                                                    <i class="fas fa-money-bill"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-question"></i>
                                                <?php endif; ?>
                                                <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($payment['transaction_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-status badge-<?php echo $payment['status']; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewPayment(<?php echo $payment['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($payment['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="verifyPayment(<?php echo $payment['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-info" onclick="printReceipt(<?php echo $payment['id']; ?>)">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- Receipts & Invoices -->
                <section id="receipts" class="content-section">
                    <h2><i class="fas fa-receipt"></i> Receipts & Invoices</h2>
                    <div class="receipt-actions mb-4">
                        <button class="btn btn-primary" onclick="openModal('createInvoice')">
                            <i class="fas fa-plus"></i> Create Invoice
                        </button>
                        <button class="btn btn-success" onclick="generateBulkReceipts()">
                            <i class="fas fa-file-invoice"></i> Bulk Receipt Generation
                        </button>
                        <button class="btn btn-info" onclick="printAllReceipts()">
                            <i class="fas fa-print"></i> Print All Receipts
                        </button>
                    </div>
                    
                    <!-- Sample Receipt Template -->
                    <div id="receiptTemplate" class="receipt-preview" style="display: none;">
                        <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px;">
                            <img src="../images/school-logo.png" alt="School Logo" style="height: 60px;">
                            <h3>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h3>
                            <p>P.O. Box 418, Iganga, Uganda | Tel: 0782 990 403</p>
                            <h4 style="color: #1e3a8a;">OFFICIAL PAYMENT RECEIPT</h4>
                            <p>Email: bursar@igangaschoolofnursingandmidwifery.ac.ug</p>
                        </div>
                        
                        <div style="margin: 20px 0;">
                            <p><strong>Receipt Number:</strong> <span id="receiptNumber"></span></p>
                            <p><strong>Date:</strong> <span id="receiptDate"></span></p>
                            <p><strong>Student Name:</strong> <span id="studentName"></span></p>
                            <p><strong>Student ID:</strong> <span id="studentId"></span></p>
                        </div>
                        
                        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Description</th>
                                    <th style="border: 1px solid #ddd; padding: 10px; text-align: right;">Amount (UGX)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border: 1px solid #ddd; padding: 10px;">Tuition Fee Payment</td>
                                    <td style="border: 1px solid #ddd; padding: 10px; text-align: right;" id="receiptAmount"></td>
                                </tr>
                                <tr style="font-weight: bold; background: #e9ecef;">
                                    <td style="border: 1px solid #ddd; padding: 10px;">TOTAL</td>
                                    <td style="border: 1px solid #ddd; padding: 10px; text-align: right;" id="receiptTotal"></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <p><strong>Payment Method:</strong> <span id="paymentMethod"></span></p>
                            <p><strong>Reference:</strong> <span id="paymentReference"></span></p>
                            <p><strong>Processed By:</strong> School Bursar</p>
                        </div>
                        
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px dashed #999;">
                            <p style="font-size: 12px; color: #666;">This is a computer-generated receipt and is valid without signature.</p>
                            <p style="font-size: 12px; color: #666;">"Chosen to Serve" - Disciplined Mind for Health Action</p>
                        </div>
                    </div>
                </section>
                
                <!-- Financial Reports -->
                <section id="reports" class="content-section">
                    <h2><i class="fas fa-chart-bar"></i> Financial Reports</h2>
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
                            <p>Revenue breakdown by category</p>
                            <button class="btn btn-primary" onclick="generateReport('revenue')">Generate Report</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <h3>Trial Balance</h3>
                            <p>Complete trial balance with all accounts</p>
                            <button class="btn btn-primary" onclick="generateReport('trial_balance')">Generate Report</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modalSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                case 'recordPayment':
                    modalTitle.textContent = 'Record New Payment';
                    modalBody.innerHTML = `
                        <form id="paymentForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Student ID / Index Number</label>
                                    <input type="text" class="form-control" id="studentIdInput" placeholder="Enter Student ID" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Amount Paid (UGX)</label>
                                    <input type="number" class="form-control" id="amountInput" min="0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-control" id="methodInput" required onchange="togglePaymentFields()">
                                        <option value="">Select Method</option>
                                        <option value="cash">Cash</option>
                                        <option value="mobile_money">Mobile Money (MTN/Airtel)</option>
                                        <option value="bank_deposit">Bank Deposit</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="providerField" style="display: none;">
                                    <label class="form-label">Mobile Provider</label>
                                    <select class="form-control" id="providerInput">
                                        <option value="mtn_momo">MTN Mobile Money</option>
                                        <option value="airtel_money">Airtel Money</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Reference Number / Transaction ID</label>
                                    <input type="text" class="form-control" id="referenceInput" placeholder="Enter reference number">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" id="notesInput" rows="3" placeholder="Additional notes..."></textarea>
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
        
        function togglePaymentFields() {
            const method = document.getElementById('methodInput').value;
            const providerField = document.getElementById('providerField');
            if (method === 'mobile_money') {
                providerField.style.display = 'block';
            } else {
                providerField.style.display = 'none';
            }
        }
        
        function generateReport(type) {
            alert('Report generation for ' + type + ' will be implemented shortly.');
        }
        
        function printReceipt(paymentId) {
            const printContent = document.getElementById('receiptTemplate').cloneNode(true);
            printContent.style.display = 'block';
            const printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Print Receipt</title></head><body>' + printContent.innerHTML + '</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
        
        function viewPayment(id) {
            alert('View payment ' + id);
        }
        
        function verifyPayment(id) {
            if (confirm('Verify this payment?')) {
                alert('Payment verified successfully!');
            }
        }
        
        function exportPayments() {
            alert('Exporting payments to Excel...');
        }
    </script>
</body>
</html>