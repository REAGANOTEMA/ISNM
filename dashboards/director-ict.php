<?php
session_start();
// Check if user is logged in and has Director ICT role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Director ICT') {
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

// Get IT statistics
$total_computers = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE category = 'Computer'")->fetch_assoc()['count'];
$active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$system_uptime = "99.8%"; // This would come from system monitoring
$network_status = "Optimal";
$storage_used = "68%";

// Get recent IT activities
$recent_activities = $conn->query("SELECT * FROM it_logs ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director ICT Dashboard - ISNM</title>
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
                <h4>ICT Director Dashboard</h4>
                <p><?php echo $user['first_name'] . ' ' . $user['surname']; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> System Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#infrastructure">
                            <i class="fas fa-server"></i> Infrastructure
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#network">
                            <i class="fas fa-network-wired"></i> Network Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#security">
                            <i class="fas fa-shield-alt"></i> Security
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#users">
                            <i class="fas fa-users"></i> User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#support">
                            <i class="fas fa-headset"></i> IT Support
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Reports
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
                    <h1>Director ICT Dashboard</h1>
                    <p>Technology Infrastructure & Systems Management</p>
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
                <!-- System Overview -->
                <section id="overview" class="content-section">
                    <h2>System Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_computers; ?></h3>
                                <p>Total Computers</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $active_users; ?></h3>
                                <p>Active Users</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $system_uptime; ?></h3>
                                <p>System Uptime</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
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
                    <h2>IT Infrastructure</h2>
                    <div class="infrastructure-actions">
                        <button class="btn btn-primary" onclick="openModal('addEquipment')">
                            <i class="fas fa-plus"></i> Add Equipment
                        </button>
                        <button class="btn btn-success" onclick="openModal('maintenance')">
                            <i class="fas fa-tools"></i> Schedule Maintenance
                        </button>
                        <button class="btn btn-info" onclick="openModal('inventory')">
                            <i class="fas fa-list"></i> Inventory Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('backup')">
                            <i class="fas fa-save"></i> Backup Systems
                        </button>
                    </div>
                    
                    <div class="infrastructure-grid">
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
                    <h2>Network Management</h2>
                    <div class="network-actions">
                        <button class="btn btn-primary" onclick="openModal('networkConfig')">
                            <i class="fas fa-cog"></i> Network Configuration
                        </button>
                        <button class="btn btn-success" onclick="openModal('bandwidth')">
                            <i class="fas fa-chart-line"></i> Bandwidth Monitor
                        </button>
                        <button class="btn btn-info" onclick="openModal('firewall')">
                            <i class="fas fa-fire"></i> Firewall Rules
                        </button>
                        <button class="btn btn-warning" onclick="openModal('vpn')">
                            <i class="fas fa-lock"></i> VPN Management
                        </button>
                    </div>
                    
                    <div class="network-status">
                        <h3>Network Status Overview</h3>
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
                    <h2>Security Management</h2>
                    <div class="security-actions">
                        <button class="btn btn-primary" onclick="openModal('userSecurity')">
                            <i class="fas fa-user-shield"></i> User Security
                        </button>
                        <button class="btn btn-success" onclick="openModal('antivirus')">
                            <i class="fas fa-virus"></i> Antivirus Scan
                        </button>
                        <button class="btn btn-info" onclick="openModal('accessLogs')">
                            <i class="fas fa-list-alt"></i> Access Logs
                        </button>
                        <button class="btn btn-warning" onclick="openModal('securityAudit')">
                            <i class="fas fa-audit"></i> Security Audit
                        </button>
                    </div>
                    
                    <div class="security-overview">
                        <h3>Security Status</h3>
                        <div class="security-metrics">
                            <div class="security-metric">
                                <div class="metric-header">
                                    <h4>Threat Level</h4>
                                    <span class="threat-level low">Low</span>
                                </div>
                                <p>No active threats detected</p>
                            </div>
                            
                            <div class="security-metric">
                                <div class="metric-header">
                                    <h4>Last Scan</h4>
                                    <span class="scan-status recent">2 hours ago</span>
                                </div>
                                <p>0 threats found</p>
                            </div>
                            
                            <div class="security-metric">
                                <div class="metric-header">
                                    <h4>Failed Logins</h4>
                                    <span class="login-status normal">3 today</span>
                                </div>
                                <p>All within normal range</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- IT Support -->
                <section id="support" class="content-section">
                    <h2>IT Support Tickets</h2>
                    <div class="support-actions">
                        <button class="btn btn-primary" onclick="openModal('newTicket')">
                            <i class="fas fa-plus"></i> New Ticket
                        </button>
                        <button class="btn btn-success" onclick="openModal('assignTicket')">
                            <i class="fas fa-user-plus"></i> Assign Ticket
                        </button>
                        <button class="btn btn-info" onclick="openModal('ticketReport')">
                            <i class="fas fa-chart-bar"></i> Support Report
                        </button>
                    </div>
                    
                    <div class="tickets-table">
                        <h3>Recent Support Tickets</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                        <td><span class="priority high">High</span></td>
                                        <td><span class="status-badge in-progress">In Progress</span></td>
                                        <td>2 hours ago</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Resolve</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#IT-002</td>
                                        <td>Mary Lecturer</td>
                                        <td>Slow computer performance</td>
                                        <td><span class="priority medium">Medium</span></td>
                                        <td><span class="status-badge pending">Pending</span></td>
                                        <td>4 hours ago</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Resolve</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent IT Activities</h2>
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
                case 'addEquipment':
                    modalTitle.textContent = 'Add IT Equipment';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Equipment Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="Computer">Computer</option>
                                    <option value="Printer">Printer</option>
                                    <option value="Server">Server</option>
                                    <option value="Network">Network Device</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Brand/Model</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Serial Number</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" class="form-control" required>
                            </div>
                        </form>
                    `;
                    break;
                case 'maintenance':
                    modalTitle.textContent = 'Schedule Maintenance';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Equipment</label>
                                <select class="form-control" required>
                                    <option value="">Select Equipment</option>
                                    <option value="comp-lab-01">Computer Lab - PC 01</option>
                                    <option value="comp-lab-02">Computer Lab - PC 02</option>
                                    <option value="server-main">Main Server</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Maintenance Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="routine">Routine Check</option>
                                    <option value="repair">Repair</option>
                                    <option value="upgrade">Upgrade</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Scheduled Date</label>
                                <input type="date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Technician</label>
                                <input type="text" class="form-control" required>
                            </div>
                        </form>
                    `;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }
    </script>
</body>
</html>
