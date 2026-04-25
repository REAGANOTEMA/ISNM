<?php
session_start();
// Check if user is logged in and has Lab Technician role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Lab Technicians') {
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

// Get lab statistics
$total_equipment = $conn->query("SELECT COUNT(*) as count FROM lab_equipment WHERE status = 'active'")->fetch_assoc()['count'];
$pending_maintenance = $conn->query("SELECT COUNT(*) as count FROM lab_equipment WHERE status = 'maintenance'")->fetch_assoc()['count'];
$scheduled_sessions = $conn->query("SELECT COUNT(*) as count FROM lab_sessions WHERE technician_id = $user_id AND DATE(session_date) = CURDATE()")->fetch_assoc()['count'];
$inventory_items = $conn->query("SELECT COUNT(*) as count FROM lab_inventory WHERE quantity <= reorder_level")->fetch_assoc()['count'];

// Get recent lab activities
$recent_activities = $conn->query("SELECT * FROM lab_logs WHERE technician_id = $user_id ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Technicians Dashboard - ISNM</title>
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
                <h4>Lab Technician Dashboard</h4>
                <p><?php echo $user['first_name'] . ' ' . $user['surname']; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Lab Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#equipment">
                            <i class="fas fa-tools"></i> Equipment Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#sessions">
                            <i class="fas fa-flask"></i> Lab Sessions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#inventory">
                            <i class="fas fa-boxes"></i> Inventory Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#maintenance">
                            <i class="fas fa-wrench"></i> Maintenance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#safety">
                            <i class="fas fa-shield-alt"></i> Safety & Compliance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Lab Reports
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
                    <h1>Lab Technician Dashboard</h1>
                    <p>Laboratory Services & Equipment Management</p>
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
                <!-- Lab Overview -->
                <section id="overview" class="content-section">
                    <h2>Laboratory Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_equipment; ?></h3>
                                <p>Total Equipment</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_maintenance; ?></h3>
                                <p>Pending Maintenance</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-flask"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $scheduled_sessions; ?></h3>
                                <p>Today's Sessions</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $inventory_items; ?></h3>
                                <p>Low Stock Items</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Equipment Management -->
                <section id="equipment" class="content-section">
                    <h2>Equipment Management</h2>
                    <div class="equipment-actions">
                        <button class="btn btn-primary" onclick="openModal('addEquipment')">
                            <i class="fas fa-plus"></i> Add Equipment
                        </button>
                        <button class="btn btn-success" onclick="openModal('equipmentStatus')">
                            <i class="fas fa-info-circle"></i> Equipment Status
                        </button>
                        <button class="btn btn-info" onclick="openModal('calibration')">
                            <i class="fas fa-balance-scale"></i> Calibration
                        </button>
                        <button class="btn btn-warning" onclick="openModal('equipmentReport')">
                            <i class="fas fa-chart-bar"></i> Equipment Report
                        </button>
                    </div>
                    
                    <div class="equipment-overview">
                        <h3>Equipment Inventory</h3>
                        <div class="equipment-grid">
                            <div class="equipment-card">
                                <div class="equipment-header">
                                    <h4>Microscope Set</h4>
                                    <span class="status-badge active">Operational</span>
                                </div>
                                <div class="equipment-details">
                                    <div class="detail">
                                        <span>Quantity:</span>
                                        <strong>15 units</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Last Maintenance:</span>
                                        <strong>Mar 15, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Next Service:</span>
                                        <strong>Jun 15, 2026</strong>
                                    </div>
                                </div>
                                <div class="equipment-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Schedule Service</button>
                                </div>
                            </div>
                            
                            <div class="equipment-card">
                                <div class="equipment-header">
                                    <h4>Centrifuge Machine</h4>
                                    <span class="status-badge maintenance">Maintenance</span>
                                </div>
                                <div class="equipment-details">
                                    <div class="detail">
                                        <span>Quantity:</span>
                                        <strong>3 units</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Last Maintenance:</span>
                                        <strong>Jan 10, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-warning">Under Maintenance</strong>
                                    </div>
                                </div>
                                <div class="equipment-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Update Status</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Lab Sessions -->
                <section id="sessions" class="content-section">
                    <h2>Lab Sessions</h2>
                    <div class="session-actions">
                        <button class="btn btn-primary" onclick="openModal('scheduleSession')">
                            <i class="fas fa-calendar-plus"></i> Schedule Session
                        </button>
                        <button class="btn btn-success" onclick="openModal('sessionSetup')">
                            <i class="fas fa-flask"></i> Session Setup
                        </button>
                        <button class="btn btn-info" onclick="openModal('sessionReport')">
                            <i class="fas fa-clipboard-check"></i> Session Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('sessionCalendar')">
                            <i class="fas fa-calendar"></i> Session Calendar
                        </button>
                    </div>
                    
                    <div class="sessions-overview">
                        <h3>Today's Lab Sessions</h3>
                        <div class="session-list">
                            <div class="session-item">
                                <div class="session-header">
                                    <h4>Nursing Skills Lab - Vital Signs</h4>
                                    <span class="session-time">8:00 AM - 10:00 AM</span>
                                </div>
                                <div class="session-details">
                                    <div class="detail">
                                        <span>Instructor:</span>
                                        <strong>Mrs. Grace Nakato</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>25 students</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Lab:</span>
                                        <strong>Skills Lab A</strong>
                                    </div>
                                </div>
                                <div class="session-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Mark Setup Complete</button>
                                </div>
                            </div>
                            
                            <div class="session-item">
                                <div class="session-header">
                                    <h4>Anatomy & Physiology - Cell Biology</h4>
                                    <span class="session-time">2:00 PM - 4:00 PM</span>
                                </div>
                                <div class="session-details">
                                    <div class="detail">
                                        <span>Instructor:</span>
                                        <strong>Prof. Michael Brown</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>30 students</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Lab:</span>
                                        <strong>Science Lab B</strong>
                                    </div>
                                </div>
                                <div class="session-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Mark Setup Complete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Inventory Management -->
                <section id="inventory" class="content-section">
                    <h2>Inventory Management</h2>
                    <div class="inventory-actions">
                        <button class="btn btn-primary" onclick="openModal('addInventory')">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                        <button class="btn btn-success" onclick="openModal('stockCheck')">
                            <i class="fas fa-clipboard-check"></i> Stock Check
                        </button>
                        <button class="btn btn-info" onclick="openModal('reorderItems')">
                            <i class="fas fa-shopping-cart"></i> Reorder Items
                        </button>
                        <button class="btn btn-warning" onclick="openModal('inventoryReport')">
                            <i class="fas fa-chart-bar"></i> Inventory Report
                        </button>
                    </div>
                    
                    <div class="inventory-overview">
                        <h3>Low Stock Items</h3>
                        <div class="inventory-list">
                            <div class="inventory-item">
                                <div class="inventory-header">
                                    <h4>Microscope Slides</h4>
                                    <span class="stock-status low">Low Stock</span>
                                </div>
                                <div class="inventory-details">
                                    <div class="detail">
                                        <span>Current Stock:</span>
                                        <strong class="text-danger">25 units</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Reorder Level:</span>
                                        <strong>50 units</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Last Ordered:</span>
                                        <strong>Apr 1, 2026</strong>
                                    </div>
                                </div>
                                <div class="inventory-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Reorder Now</button>
                                </div>
                            </div>
                            
                            <div class="inventory-item">
                                <div class="inventory-header">
                                    <h4>Test Tubes</h4>
                                    <span class="stock-status low">Low Stock</span>
                                </div>
                                <div class="inventory-details">
                                    <div class="detail">
                                        <span>Current Stock:</span>
                                        <strong class="text-danger">100 units</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Reorder Level:</span>
                                        <strong>200 units</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Last Ordered:</span>
                                        <strong>Mar 20, 2026</strong>
                                    </div>
                                </div>
                                <div class="inventory-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Reorder Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Maintenance -->
                <section id="maintenance" class="content-section">
                    <h2>Maintenance Management</h2>
                    <div class="maintenance-actions">
                        <button class="btn btn-primary" onclick="openModal('scheduleMaintenance')">
                            <i class="fas fa-wrench"></i> Schedule Maintenance
                        </button>
                        <button class="btn btn-success" onclick="openModal('maintenanceLog')">
                            <i class="fas fa-clipboard"></i> Maintenance Log
                        </button>
                        <button class="btn btn-info" onclick="openModal('maintenanceReport')">
                            <i class="fas fa-chart-line"></i> Maintenance Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('vendorManagement')">
                            <i class="fas fa-truck"></i> Vendor Management
                        </button>
                    </div>
                    
                    <div class="maintenance-overview">
                        <h3>Pending Maintenance</h3>
                        <div class="maintenance-list">
                            <div class="maintenance-item">
                                <div class="maintenance-header">
                                    <h4>Centrifuge Machine - Unit 2</h4>
                                    <span class="maintenance-date">Scheduled: Apr 25, 2026</span>
                                </div>
                                <div class="maintenance-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Preventive Maintenance</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Technician:</span>
                                        <strong>External Service Provider</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Priority:</span>
                                        <strong class="text-warning">Medium</strong>
                                    </div>
                                </div>
                                <div class="maintenance-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Mark Complete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Safety & Compliance -->
                <section id="safety" class="content-section">
                    <h2>Safety & Compliance</h2>
                    <div class="safety-actions">
                        <button class="btn btn-primary" onclick="openModal('safetyInspection')">
                            <i class="fas fa-clipboard-check"></i> Safety Inspection
                        </button>
                        <button class="btn btn-success" onclick="openModal('safetyTraining')">
                            <i class="fas fa-graduation-cap"></i> Safety Training
                        </button>
                        <button class="btn btn-info" onclick="openModal('incidentReport')">
                            <i class="fas fa-exclamation-triangle"></i> Incident Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('complianceCheck')">
                            <i class="fas fa-shield-alt"></i> Compliance Check
                        </button>
                    </div>
                    
                    <div class="safety-overview">
                        <h3>Safety Status</h3>
                        <div class="safety-stats">
                            <div class="safety-stat">
                                <h4>Safety Inspections</h4>
                                <div class="inspection-rate">95%</div>
                                <small>Completed this month</small>
                            </div>
                            <div class="safety-stat">
                                <h4>Incidents This Month</h4>
                                <div class="incident-count">0</div>
                                <small>No incidents reported</small>
                            </div>
                            <div class="safety-stat">
                                <h4>Training Completed</h4>
                                <div class="training-rate">88%</div>
                                <small>Staff trained</small>
                            </div>
                            <div class="safety-stat">
                                <h4>Compliance Score</h4>
                                <div class="compliance-score">92%</div>
                                <small>Overall compliance</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Lab Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['action']; ?></strong></p>
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
                    modalTitle.textContent = 'Add New Equipment';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Equipment Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Equipment Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="microscope">Microscope</option>
                                    <option value="centrifuge">Centrifuge</option>
                                    <option value="incubator">Incubator</option>
                                    <option value="autoclave">Autoclave</option>
                                    <option value="spectrophotometer">Spectrophotometer</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Lab Location</label>
                                        <select class="form-control" required>
                                            <option value="">Select Lab</option>
                                            <option value="skills-a">Skills Lab A</option>
                                            <option value="skills-b">Skills Lab B</option>
                                            <option value="science-a">Science Lab A</option>
                                            <option value="science-b">Science Lab B</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Purchase Date</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Warranty Period (months)</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Model/Serial Number</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Maintenance Schedule</label>
                                <select class="form-control" required>
                                    <option value="">Select Schedule</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                        </form>
                    `;
                    break;
                case 'scheduleSession':
                    modalTitle.textContent = 'Schedule Lab Session';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Session Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Session Type</option>
                                    <option value="practical">Practical Session</option>
                                    <option value="demonstration">Demonstration</option>
                                    <option value="experiment">Experiment</option>
                                    <option value="assessment">Assessment</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Course/Subject</label>
                                <select class="form-control" required>
                                    <option value="">Select Course</option>
                                    <option value="nursing-fundamentals">Nursing Fundamentals</option>
                                    <option value="anatomy">Anatomy & Physiology</option>
                                    <option value="pharmacology">Pharmacology</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instructor</label>
                                <select class="form-control" required>
                                    <option value="">Select Instructor</option>
                                    <option value="grace-nakato">Mrs. Grace Nakato</option>
                                    <option value="michael-brown">Prof. Michael Brown</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Time</label>
                                        <input type="time" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Lab Room</label>
                                        <select class="form-control" required>
                                            <option value="">Select Lab</option>
                                            <option value="skills-a">Skills Lab A</option>
                                            <option value="skills-b">Skills Lab B</option>
                                            <option value="science-a">Science Lab A</option>
                                            <option value="science-b">Science Lab B</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Number of Students</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Equipment Required</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="microscope">
                                    <label class="form-check-label" for="microscope">Microscope</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="slides">
                                    <label class="form-check-label" for="slides">Microscope Slides</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="test-tubes">
                                    <label class="form-check-label" for="test-tubes">Test Tubes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="centrifuge">
                                    <label class="form-check-label" for="centrifuge">Centrifuge</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Special Requirements</label>
                                <textarea class="form-control" rows="2"></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'addInventory':
                    modalTitle.textContent = 'Add Inventory Item';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Item Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-control" required>
                                    <option value="">Select Category</option>
                                    <option value="consumables">Consumables</option>
                                    <option value="reagents">Reagents</option>
                                    <option value="glassware">Glassware</option>
                                    <option value="safety">Safety Equipment</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Unit</label>
                                        <select class="form-control" required>
                                            <option value="">Select Unit</option>
                                            <option value="pieces">Pieces</option>
                                            <option value="boxes">Boxes</option>
                                            <option value="bottles">Bottles</option>
                                            <option value="packs">Packs</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Reorder Level</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Unit Cost (UGX)</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Storage Location</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Safety Information</label>
                                <textarea class="form-control" rows="2"></textarea>
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
