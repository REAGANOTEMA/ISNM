<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';

$ctx = bootstrapStaffDashboard(['director', 'finance']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'Finance Director';
$website_conn = $ctx['website'];

// Set dashboard statistics from database
$total_students = 0;
$total_staff = 0;
$total_revenue = 0;
$total_expenses = 0;
$outstanding_fees = 0;

try {
    $students_conn = $ctx['students'] ?? null;
    if ($students_conn) {
        $result = $students_conn->query("SELECT COUNT(*) as cnt FROM students");
        if ($result) $total_students = (int)$result->fetch_assoc()['cnt'];

        $result = $students_conn->query("SELECT COALESCE(SUM(amount_received), 0) as total FROM payments WHERE status = 'Completed'");
        if ($result) $total_revenue = (float)$result->fetch_assoc()['total'];

        $result = $students_conn->query("SELECT COALESCE(SUM(balance), 0) as total FROM student_invoices WHERE status IN ('Pending','Partially Paid','Overdue')");
        if ($result) $outstanding_fees = (float)$result->fetch_assoc()['total'];
    }

    if ($conn) {
        $result = $conn->query("SELECT COUNT(*) as cnt FROM staff");
        if ($result) $total_staff = (int)$result->fetch_assoc()['cnt'];

        $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses");
        if ($result) $total_expenses = (float)$result->fetch_assoc()['total'];
    }
} catch (Exception $e) {}

// Get recent activities
$recent_activities = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_activities[] = $row;
            }
        }
    } catch (Exception $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
    <div class="dashboard-container">
        <?php include_once '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Director Finance Dashboard</h1>
                    <p>Financial Affairs & Strategic Management</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <a href="../store_request.php" class="btn btn-sm btn-outline-primary ms-2"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                    <a href="../news.php" class="btn btn-sm btn-outline-primary ms-1"><i class="fas fa-newspaper me-1"></i>News</a>
                    <a href="../student-directory.php" class="btn btn-sm btn-outline-info ms-2"><i class="fas fa-address-book me-1"></i>Directory</a>
                    <a href="../index.php" class="btn btn-sm btn-outline-secondary ms-1"><i class="fas fa-home"></i></a>
                    <div class="user-menu">
                        <img src="<?= $profileImageUrl ?>" alt="User" class="user-avatar">
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Financial Overview -->
                <section id="overview" class="content-section">
                    <h2>Financial Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($total_revenue); ?></h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($total_expenses); ?></h3>
                                <p>Total Expenses</p>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($total_revenue - $total_expenses); ?></h3>
                                <p>Net Balance</p>
                            </div>
                        </div>
                        
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
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
                    <h2><i class="fas fa-money-bill-wave me-2"></i>Revenue Management</h2>
                    <div class="revenue-breakdown">
                        <h3 class="mb-3">Revenue Breakdown by Category</h3>
                        <div class="revenue-grid">
                            <div class="revenue-item">
                                <h4>Tuition Fees</h4>
                                <div class="revenue-amount fw-bold text-success">UGX <?php echo number_format($total_revenue * 0.75); ?></div>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: 75%"></div>
                                </div>
                                <small class="text-muted">75% of total revenue</small>
                            </div>
                            
                            <div class="revenue-item">
                                <h4>Hostel Fees</h4>
                                <div class="revenue-amount fw-bold text-info">UGX <?php echo number_format($total_revenue * 0.15); ?></div>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: 15%"></div>
                                </div>
                                <small class="text-muted">15% of total revenue</small>
                            </div>
                            
                            <div class="revenue-item">
                                <h4>Application Fees</h4>
                                <div class="revenue-amount fw-bold text-warning">UGX <?php echo number_format($total_revenue * 0.05); ?></div>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-warning" style="width: 5%"></div>
                                </div>
                                <small class="text-muted">5% of total revenue</small>
                            </div>
                            
                            <div class="revenue-item">
                                <h4>Other Income</h4>
                                <div class="revenue-amount fw-bold text-secondary">UGX <?php echo number_format($total_revenue * 0.05); ?></div>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-secondary" style="width: 5%"></div>
                                </div>
                                <small class="text-muted">5% of total revenue</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Expense Management -->
                <section id="expenses" class="content-section">
                    <h2><i class="fas fa-receipt me-2"></i>Expense Management</h2>
                    <div class="expense-categories">
                        <h3 class="mb-3">Expense Categories</h3>
                        <div class="category-grid">
                            <div class="category-card">
                                <h4>Salaries & Wages</h4>
                                <span class="amount fw-bold">UGX <?php echo number_format($total_expenses * 0.45); ?></span>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" style="width: 45%"></div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <h4>Utilities</h4>
                                <span class="amount fw-bold">UGX <?php echo number_format($total_expenses * 0.15); ?></span>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: 15%"></div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <h4>Supplies & Materials</h4>
                                <span class="amount fw-bold">UGX <?php echo number_format($total_expenses * 0.20); ?></span>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" style="width: 20%"></div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <h4>Maintenance</h4>
                                <span class="amount fw-bold">UGX <?php echo number_format($total_expenses * 0.10); ?></span>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: 10%"></div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <h4>Transportation</h4>
                                <span class="amount fw-bold">UGX <?php echo number_format($total_expenses * 0.05); ?></span>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-secondary" style="width: 5%"></div>
                                </div>
                            </div>
                            
                            <div class="category-card">
                                <h4>Other Expenses</h4>
                                <span class="amount fw-bold">UGX <?php echo number_format($total_expenses * 0.05); ?></span>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger" style="width: 5%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Budget Planning -->
                <section id="budget" class="content-section">
                    <h2><i class="fas fa-calculator me-2"></i>Budget Planning</h2>
                    <div class="budget-overview">
                        <h3 class="mb-3">Current Fiscal Year Budget</h3>
                        <div class="budget-summary">
                            <div class="budget-item">
                                <div class="budget-label text-muted mb-1">Total Budget Allocation</div>
                                <div class="budget-value fs-3 fw-bold text-primary">UGX <?php echo number_format(500000000); ?></div>
                            </div>
                            <div class="budget-item">
                                <div class="budget-label text-muted mb-1">Budget Utilized</div>
                                <div class="budget-value fs-3 fw-bold text-warning">UGX <?php echo number_format($total_expenses + $total_revenue * 0.3); ?></div>
                            </div>
                            <div class="budget-item">
                                <div class="budget-label text-muted mb-1">Remaining Budget</div>
                                <div class="budget-value fs-3 fw-bold text-success">UGX <?php echo number_format(500000000 - ($total_expenses + $total_revenue * 0.3)); ?></div>
                            </div>
                            <div class="budget-item">
                                <div class="budget-label text-muted mb-1">Utilization Rate</div>
                                <div class="budget-value fs-3 fw-bold"><?php echo round((($total_expenses + $total_revenue * 0.3) / 500000000) * 100, 1); ?>%</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Financial Reports -->
                <section id="reports" class="content-section">
                    <h2><i class="fas fa-chart-bar me-2"></i>Financial Reports</h2>
                    <div class="reports-grid">
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <h3>Income Statement</h3>
                            <p>Monthly profit and loss statement</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <h3>Trial Balance</h3>
                            <p>Complete trial balance report</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-money-check"></i>
                            </div>
                            <h3>Cash Flow Statement</h3>
                            <p>Cash inflows and outflows analysis</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <h3>URA Tax Report</h3>
                            <p>Tax compliance reports for URA</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                    </div>
                </section>

                <!-- News Management -->
                <section id="news" class="content-section">
                    <h2><i class="fas fa-newspaper me-2"></i>News &amp; Announcements</h2>
                    <?php renderNewsWidget($conn, $website_conn, $ctx['user']['id'] ?? 0, $user_name, $_SESSION['role'] ?? 'Director Finance', 5); ?>
                </section>

                <!-- Recent Activities -->
                <section id="activity" class="content-section">
                    <h2><i class="fas fa-history me-2"></i>Recent Financial Activities</h2>
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
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
