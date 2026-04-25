<?php
session_start();
// Check if user is logged in and has Director Finance role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Director Finance') {
    header('Location: ../login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'isnm_school');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Get financial statistics
$total_revenue = $conn->query("SELECT SUM(amount_paid) as total FROM fee_payments WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0;
$total_expenses = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0;
$pending_payments = $conn->query("SELECT COUNT(*) as count FROM fee_payments WHERE status = 'pending'")->fetch_assoc()['count'];
$outstanding_fees = $conn->query("SELECT SUM(balance) as total FROM student_accounts WHERE balance > 0")->fetch_assoc()['total'] ?? 0;

// Get recent financial activities
$recent_activities = $conn->query("SELECT * FROM financial_logs ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director Finance Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="dashboard-style.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>Finance Director Dashboard</h4>
                <p><?php echo $user['first_name'] . ' ' . $user['surname']; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Financial Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#revenue">
                            <i class="fas fa-money-bill-wave"></i> Revenue Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#expenses">
                            <i class="fas fa-receipt"></i> Expense Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#budget">
                            <i class="fas fa-calculator"></i> Budget Planning
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Financial Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#compliance">
                            <i class="fas fa-shield-alt"></i> Compliance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#audit">
                            <i class="fas fa-audit"></i> Internal Audit
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Director Finance Dashboard</h1>
                    <p>Financial Affairs & Strategic Management</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span id="currentDate"></span>
                    </div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo $user['first_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Financial Overview -->
                <section id="overview" class="content-section">
                    <h2>Financial Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-arrow-up text-success"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($total_revenue); ?></h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-arrow-down text-danger"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($total_expenses); ?></h3>
                                <p>Total Expenses</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-piggy-bank text-warning"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($total_revenue - $total_expenses); ?></h3>
                                <p>Net Balance</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle text-info"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($outstanding_fees); ?></h3>
                                <p>Outstanding Fees</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Revenue Management -->
                <section id="revenue" class="content-section">
                    <h2>Revenue Management</h2>
                    <div class="revenue-actions">
                        <button class="btn btn-primary" onclick="openModal('revenueAnalysis')">
                            <i class="fas fa-chart-line"></i> Revenue Analysis
                        </button>
                        <button class="btn btn-success" onclick="openModal('feeStructure')">
                            <i class="fas fa-list-alt"></i> Fee Structure
                        </button>
                        <button class="btn btn-info" onclick="openModal('paymentMethods')">
                            <i class="fas fa-credit-card"></i> Payment Methods
                        </button>
                        <button class="btn btn-warning" onclick="openModal('revenueForecast')">
                            <i class="fas fa-chart-area"></i> Revenue Forecast
                        </button>
                    </div>
                    
                    <div class="revenue-breakdown">
                        <h3>Revenue Breakdown by Category</h3>
                        <div class="revenue-grid">
                            <div class="revenue-item">
                                <h4>Tuition Fees</h4>
                                <div class="revenue-amount">UGX <?php echo number_format($total_revenue * 0.75); ?></div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: 75%">75%</div>
                                </div>
                                <small>75% of total revenue</small>
                            </div>
                            
                            <div class="revenue-item">
                                <h4>Hostel Fees</h4>
                                <div class="revenue-amount">UGX <?php echo number_format($total_revenue * 0.15); ?></div>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: 15%">15%</div>
                                </div>
                                <small>15% of total revenue</small>
                            </div>
                            
                            <div class="revenue-item">
                                <h4>Application Fees</h4>
                                <div class="revenue-amount">UGX <?php echo number_format($total_revenue * 0.05); ?></div>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: 5%">5%</div>
                                </div>
                                <small>5% of total revenue</small>
                            </div>
                            
                            <div class="revenue-item">
                                <h4>Other Income</h4>
                                <div class="revenue-amount">UGX <?php echo number_format($total_revenue * 0.05); ?></div>
                                <div class="progress">
                                    <div class="progress-bar bg-secondary" style="width: 5%">5%</div>
                                </div>
                                <small>5% of total revenue</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Expense Management -->
                <section id="expenses" class="content-section">
                    <h2>Expense Management</h2>
                    <div class="expense-actions">
                        <button class="btn btn-primary" onclick="openModal('addExpense')">
                            <i class="fas fa-plus"></i> Add Expense
                        </button>
                        <button class="btn btn-success" onclick="openModal('approveExpense')">
                            <i class="fas fa-check"></i> Approve Expenses
                        </button>
                        <button class="btn btn-info" onclick="openModal('expenseReport')">
                            <i class="fas fa-file-alt"></i> Expense Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('costOptimization')">
                            <i class="fas fa-cut"></i> Cost Optimization
                        </button>
                    </div>
                    
                    <div class="expense-categories">
                        <h3>Expense Categories</h3>
                        <div class="category-grid">
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>Salaries & Wages</h4>
                                    <span class="amount">UGX <?php echo number_format($total_expenses * 0.45); ?></span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" style="width: 45%">45%</div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>Utilities</h4>
                                    <span class="amount">UGX <?php echo number_format($total_expenses * 0.15); ?></span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: 15%">15%</div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>Supplies & Materials</h4>
                                    <span class="amount">UGX <?php echo number_format($total_expenses * 0.20); ?></span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: 20%">20%</div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>Maintenance</h4>
                                    <span class="amount">UGX <?php echo number_format($total_expenses * 0.10); ?></span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: 10%">10%</div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>Transportation</h4>
                                    <span class="amount">UGX <?php echo number_format($total_expenses * 0.05); ?></span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-secondary" style="width: 5%">5%</div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <div class="category-header">
                                    <h4>Other Expenses</h4>
                                    <span class="amount">UGX <?php echo number_format($total_expenses * 0.05); ?></span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" style="width: 5%">5%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Budget Planning -->
                <section id="budget" class="content-section">
                    <h2>Budget Planning</h2>
                    <div class="budget-actions">
                        <button class="btn btn-primary" onclick="openModal('createBudget')">
                            <i class="fas fa-plus"></i> Create Budget
                        </button>
                        <button class="btn btn-success" onclick="openModal('budgetAnalysis')">
                            <i class="fas fa-chart-pie"></i> Budget Analysis
                        </button>
                        <button class="btn btn-info" onclick="openModal('budgetVariance')">
                            <i class="fas fa-balance-scale"></i> Budget Variance
                        </button>
                        <button class="btn btn-warning" onclick="openModal('budgetForecast')">
                            <i class="fas fa-chart-line"></i> Budget Forecast
                        </button>
                    </div>
                    
                    <div class="budget-overview">
                        <h3>Current Fiscal Year Budget</h3>
                        <div class="budget-summary">
                            <div class="budget-item">
                                <div class="budget-label">Total Budget Allocation</div>
                                <div class="budget-value">UGX <?php echo number_format(500000000); ?></div>
                            </div>
                            <div class="budget-item">
                                <div class="budget-label">Budget Utilized</div>
                                <div class="budget-value">UGX <?php echo number_format($total_expenses + $total_revenue * 0.3); ?></div>
                            </div>
                            <div class="budget-item">
                                <div class="budget-label">Remaining Budget</div>
                                <div class="budget-value">UGX <?php echo number_format(500000000 - ($total_expenses + $total_revenue * 0.3)); ?></div>
                            </div>
                            <div class="budget-item">
                                <div class="budget-label">Utilization Rate</div>
                                <div class="budget-value"><?php echo round((($total_expenses + $total_revenue * 0.3) / 500000000) * 100, 1); ?>%</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Financial Reports -->
                <section id="reports" class="content-section">
                    <h2>Financial Reports</h2>
                    <div class="reports-grid">
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <h3>Income Statement</h3>
                            <p>Monthly profit and loss statement</p>
                            <button class="btn btn-primary" onclick="generateReport('income_statement')">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <h3>Trial Balance</h3>
                            <p>Complete trial balance report</p>
                            <button class="btn btn-primary" onclick="generateReport('trial_balance')">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-money-check"></i>
                            </div>
                            <h3>Cash Flow Statement</h3>
                            <p>Cash inflows and outflows analysis</p>
                            <button class="btn btn-primary" onclick="generateReport('cash_flow')">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <h3>URA Tax Report</h3>
                            <p>Tax compliance reports for URA</p>
                            <button class="btn btn-primary" onclick="generateReport('ura_tax')">Generate</button>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Financial Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['user_name']; ?></strong> <?php echo $activity['action']; ?></p>
                                <small><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
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
                    <button type="button" class="btn btn-primary" id="modalAction">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current date/time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Navigation
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href').substring(1);
                document.querySelectorAll('.content-section').forEach(section => {
                    section.style.display = 'none';
                });
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
            });
        });

        // Modal functions
        function openModal(action) {
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            switch(action) {
                case 'revenueAnalysis':
                    modalTitle.textContent = 'Revenue Analysis';
                    modalBody.innerHTML = `
                        <div class="revenue-analysis">
                            <h5>Revenue Trends</h5>
                            <div class="chart-placeholder">
                                <i class="fas fa-chart-line fa-3x text-muted"></i>
                                <p>Revenue trend chart would be displayed here</p>
                            </div>
                            <div class="revenue-stats">
                                <div class="stat">
                                    <strong>This Month:</strong> UGX 25,000,000
                                </div>
                                <div class="stat">
                                    <strong>Last Month:</strong> UGX 22,000,000
                                </div>
                                <div class="stat">
                                    <strong>Growth:</strong> +13.6%
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'addExpense':
                    modalTitle.textContent = 'Add Expense';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Expense Category</label>
                                <select class="form-control" required>
                                    <option value="">Select Category</option>
                                    <option value="salaries">Salaries & Wages</option>
                                    <option value="utilities">Utilities</option>
                                    <option value="supplies">Supplies & Materials</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="transport">Transportation</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" required>
                            </div>
                        </form>
                    `;
                    break;
                case 'createBudget':
                    modalTitle.textContent = 'Create Budget';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Budget Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Budget Period</label>
                                <select class="form-control" required>
                                    <option value="">Select Period</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Total Budget Amount</label>
                                <input type="number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-control" required>
                                    <option value="">Select Department</option>
                                    <option value="all">All Departments</option>
                                    <option value="academic">Academic</option>
                                    <option value="administrative">Administrative</option>
                                    <option value="support">Support Services</option>
                                </select>
                            </div>
                        </form>
                    `;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }

        function generateReport(type) {
            alert('Generating ' + type + ' report... This would generate and download the financial report.');
        }
    </script>
</body>
</html>
