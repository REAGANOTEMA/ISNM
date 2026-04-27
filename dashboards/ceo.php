<?php
include_once '../includes/config.php';
// Check if user is logged in and has CEO role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Chief Executive Officer') {
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

// Get system statistics (using fallback data only)
$total_students = 150; // Fallback value
$total_staff = 2; // Fallback value
$recent_applications = 8; // Fallback value
$active_programs = 2; // Fallback value

// Get recent activities (using a simple approach)
$recent_activities = [
    ['activity' => 'Dashboard accessed', 'created_at' => date('Y-m-d H:i:s')],
    ['activity' => 'Executive meeting conducted', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEO Dashboard - ISNM</title>
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
                <h4>CEO Dashboard</h4>
                <p><?php echo ($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? $user['last_name'] ?? ''); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#strategic">
                            <i class="fas fa-chess"></i> Strategic Planning
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#operations">
                            <i class="fas fa-cogs"></i> Operations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#financial">
                            <i class="fas fa-chart-line"></i> Financial Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#staff">
                            <i class="fas fa-users"></i> Staff Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-file-alt"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#communications">
                            <i class="fas fa-envelope"></i> Communications
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
                    <h1>Chief Executive Officer Dashboard</h1>
                    <p>Strategic Management & System Oversight</p>
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
                <!-- Overview Section -->
                <section id="overview" class="content-section">
                    <h2>System Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_students; ?></h3>
                                <p>Total Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_staff; ?></h3>
                                <p>Total Staff</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_applications; ?></h3>
                                <p>Pending Applications</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill"></i>
                            </div>
                            <div class="stat-content">
                                <h3>UGX <?php echo number_format($total_revenue); ?></h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Strategic Planning -->
                <section id="strategic" class="content-section">
                    <h2>Strategic Planning</h2>
                    <div class="strategic-actions">
                        <button class="btn btn-primary" onclick="openModal('strategicPlan')">
                            <i class="fas fa-plus"></i> Create Strategic Plan
                        </button>
                        <button class="btn btn-success" onclick="openModal('kpiTracking')">
                            <i class="fas fa-chart-bar"></i> KPI Tracking
                        </button>
                        <button class="btn btn-info" onclick="openModal('riskAssessment')">
                            <i class="fas fa-exclamation-triangle"></i> Risk Assessment
                        </button>
                        <button class="btn btn-warning" onclick="openModal('partnerships')">
                            <i class="fas fa-handshake"></i> Partnerships
                        </button>
                    </div>
                    
                    <div class="strategic-overview">
                        <h3>Current Strategic Initiatives</h3>
                        <div class="initiatives-grid">
                            <div class="initiative-card">
                                <h4>Academic Excellence</h4>
                                <p>Maintain 100% pass rate in midwifery and improve nursing performance</p>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 85%"></div>
                                </div>
                                <small>85% Complete</small>
                            </div>
                            
                            <div class="initiative-card">
                                <h4>Infrastructure Development</h4>
                                <p>Complete administration block and boys' hostel construction</p>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 60%"></div>
                                </div>
                                <small>60% Complete</small>
                            </div>
                            
                            <div class="initiative-card">
                                <h4>Technology Integration</h4>
                                <p>Implement digital learning platforms and online systems</p>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 70%"></div>
                                </div>
                                <small>70% Complete</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Operations -->
                <section id="operations" class="content-section">
                    <h2>Operations Management</h2>
                    <div class="operations-grid">
                        <div class="operation-card">
                            <div class="operation-header">
                                <h4>Academic Operations</h4>
                                <span class="status-badge active">Active</span>
                            </div>
                            <div class="operation-metrics">
                                <div class="metric">
                                    <span>Classes Running:</span>
                                    <strong>24</strong>
                                </div>
                                <div class="metric">
                                    <span>Exams Scheduled:</span>
                                    <strong>8</strong>
                                </div>
                                <div class="metric">
                                    <span>Clinical Placements:</span>
                                    <strong>156</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="operation-card">
                            <div class="operation-header">
                                <h4>Financial Operations</h4>
                                <span class="status-badge active">Active</span>
                            </div>
                            <div class="operation-metrics">
                                <div class="metric">
                                    <span>Daily Collections:</span>
                                    <strong>UGX 2.5M</strong>
                                </div>
                                <div class="metric">
                                    <span>Outstanding:</span>
                                    <strong>UGX 15M</strong>
                                </div>
                                <div class="metric">
                                    <span>Expenses:</span>
                                    <strong>UGX 1.8M</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="operation-card">
                            <div class="operation-header">
                                <h4>Administrative Operations</h4>
                                <span class="status-badge active">Active</span>
                            </div>
                            <div class="operation-metrics">
                                <div class="metric">
                                    <span>Staff Present:</span>
                                    <strong>45/48</strong>
                                </div>
                                <div class="metric">
                                    <span>Students Present:</span>
                                    <strong>298/315</strong>
                                </div>
                                <div class="metric">
                                    <span>Facilities:</span>
                                    <strong>Optimal</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent System Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon'] ?? 'check-circle'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['action'] ?? $activity['activity'] ?? 'Activity'; ?></strong></p>
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
                case 'strategicPlan':
                    modalTitle.textContent = 'Create Strategic Plan';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Plan Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Timeline</label>
                                <input type="date" class="form-control">
                            </div>
                        </form>
                    `;
                    break;
                case 'kpiTracking':
                    modalTitle.textContent = 'KPI Tracking';
                    modalBody.innerHTML = `
                        <div class="kpi-overview">
                            <h5>Current KPIs</h5>
                            <div class="kpi-list">
                                <div class="kpi-item">
                                    <span>Student Enrollment Rate</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 92%">92%</div>
                                    </div>
                                </div>
                                <div class="kpi-item">
                                    <span>Academic Performance</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 88%">88%</div>
                                    </div>
                                </div>
                                <div class="kpi-item">
                                    <span>Financial Sustainability</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 75%">75%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }
    </script>
</body>
</html>
