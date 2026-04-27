<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';
include_once '../includes/photo_upload.php';
include_once '../includes/student_profile_component.php';

// Check if user is logged in and has HR Manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HR Manager') {
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

// Get HR statistics (using fallback data only)
$total_staff = 25; // Fallback value
$active_staff = 22; // Fallback value
$pending_applications = 5; // Fallback value
$on_leave = 3; // Fallback value
$total_students = 150; // Fallback value
$active_students = 145; // Fallback value
$recent_hires = 8; // Fallback value
$open_positions = 4; // Fallback value

// Get recent students for HR overview (using fallback data)
$recent_students = [
    ['first_name' => 'Alice', 'surname' => 'Student', 'program' => 'Nursing', 'status' => 'active'],
    ['first_name' => 'Bob', 'surname' => 'Student', 'program' => 'Midwifery', 'status' => 'active'],
    ['first_name' => 'Carol', 'surname' => 'Student', 'program' => 'Nursing', 'status' => 'active'],
    ['first_name' => 'David', 'surname' => 'Student', 'program' => 'Midwifery', 'status' => 'active']
];

// Get recent HR activities (using fallback data)
$recent_activities = [
    ['activity' => 'New staff member hired', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['activity' => 'Leave request processed', 'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))],
    ['activity' => 'Student application reviewed', 'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours'))],
    ['activity' => 'Payroll processed', 'created_at' => date('Y-m-d H:i:s', strtotime('-8 hours'))]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Manager Dashboard - ISNM</title>
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
                <h4>HR Manager Dashboard</h4>
                <p><?php echo ($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? $user['last_name'] ?? ''); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> HR Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#recruitment">
                            <i class="fas fa-user-plus"></i> Recruitment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#staff">
                            <i class="fas fa-users"></i> Staff Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#payroll">
                            <i class="fas fa-money-check"></i> Payroll
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#leave">
                            <i class="fas fa-calendar-alt"></i> Leave Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#performance">
                            <i class="fas fa-chart-line"></i> Performance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#training">
                            <i class="fas fa-graduation-cap"></i> Training & Development
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-file-alt"></i> HR Reports
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
                    <h1>HR Manager Dashboard</h1>
                    <p>Human Resources & Staff Management</p>
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
                <!-- HR Overview -->
                <section id="overview" class="content-section">
                    <h2>HR Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_staff; ?></h3>
                                <p>Total Staff</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $active_staff; ?></h3>
                                <p>Active Staff</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_applications; ?></h3>
                                <p>Pending Applications</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $on_leave; ?></h3>
                                <p>Staff on Leave</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Overview -->
                <section id="students" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Student Overview</h2>
                        <div>
                            <button class="btn btn-primary" onclick="window.location.href='../student_accounts_management.php'">
                                <i class="fas fa-users"></i> Manage Students
                            </button>
                            <button class="btn btn-success" onclick="window.location.href='../academic_records_management.php'">
                                <i class="fas fa-graduation-cap"></i> Academic Records
                            </button>
                        </div>
                    </div>
                    
                    <!-- Student Statistics -->
                    <div class="stats-grid mb-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_students; ?></h3>
                                <p>Total Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $active_students; ?></h3>
                                <p>Active Students</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Search -->
                    <?php echo displayStudentSearchBox('Search students by name, ID, or phone...', 'hrSearchResults'); ?>
                    
                    <!-- Student Profile Cards -->
                    <div class="row mt-4">
                        <?php foreach ($recent_students as $student): ?>
                            <div class="col-md-6 col-lg-3 mb-4">
                                <?php echo displayStudentProfileCard($student['student_id'], 'compact'); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Recruitment -->
                <section id="recruitment" class="content-section">
                    <h2>Recruitment Management</h2>
                    <div class="recruitment-actions">
                        <button class="btn btn-primary" onclick="openModal('jobPosting')">
                            <i class="fas fa-bullhorn"></i> Post Job
                        </button>
                        <button class="btn btn-success" onclick="openModal('reviewApplications')">
                            <i class="fas fa-eye"></i> Review Applications
                        </button>
                        <button class="btn btn-info" onclick="openModal('scheduleInterview')">
                            <i class="fas fa-calendar"></i> Schedule Interview
                        </button>
                        <button class="btn btn-warning" onclick="openModal('recruitmentReport')">
                            <i class="fas fa-chart-bar"></i> Recruitment Report
                        </button>
                    </div>
                    
                    <div class="recruitment-overview">
                        <h3>Current Job Openings</h3>
                        <div class="job-openings">
                            <div class="job-card">
                                <div class="job-header">
                                    <h4>Senior Lecturer - Nursing</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="job-details">
                                    <div class="detail">
                                        <span>Department:</span>
                                        <strong>Academic</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Full-time</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Applications:</span>
                                        <strong>12 received</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Closing Date:</span>
                                        <strong>May 30, 2026</strong>
                                    </div>
                                </div>
                                <div class="job-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Applications</button>
                                    <button class="btn btn-sm btn-outline-info">Edit Posting</button>
                                </div>
                            </div>
                            
                            <div class="job-card">
                                <div class="job-header">
                                    <h4>Laboratory Technician</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="job-details">
                                    <div class="detail">
                                        <span>Department:</span>
                                        <strong>Support Services</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Full-time</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Applications:</span>
                                        <strong>8 received</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Closing Date:</span>
                                        <strong>May 25, 2026</strong>
                                    </div>
                                </div>
                                <div class="job-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Applications</button>
                                    <button class="btn btn-sm btn-outline-info">Edit Posting</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Staff Management -->
                <section id="staff" class="content-section">
                    <h2>Staff Management</h2>
                    <div class="staff-actions">
                        <button class="btn btn-primary" onclick="openModal('addStaff')">
                            <i class="fas fa-user-plus"></i> Add Staff
                        </button>
                        <button class="btn btn-success" onclick="openModal('editStaff')">
                            <i class="fas fa-edit"></i> Edit Staff
                        </button>
                        <button class="btn btn-info" onclick="openModal('staffDirectory')">
                            <i class="fas fa-address-book"></i> Staff Directory
                        </button>
                        <button class="btn btn-warning" onclick="openModal('staffPerformance')">
                            <i class="fas fa-chart-line"></i> Performance Review
                        </button>
                    </div>
                    
                    <div class="staff-table">
                        <h3>Staff Directory</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Join Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Dr. Sarah Johnson</td>
                                        <td>Senior Lecturer</td>
                                        <td>Academic</td>
                                        <td><span class="status-badge active">Active</span></td>
                                        <td>Jan 15, 2020</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Edit</button>
                                            <button class="btn btn-sm btn-outline-warning">Reset Password</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Prof. Michael Brown</td>
                                        <td>Head of Nursing</td>
                                        <td>Academic</td>
                                        <td><span class="status-badge active">Active</span></td>
                                        <td>Mar 10, 2019</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Edit</button>
                                            <button class="btn btn-sm btn-outline-warning">Reset Password</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mrs. Grace Nakato</td>
                                        <td>Lecturer</td>
                                        <td>Academic</td>
                                        <td><span class="status-badge on-leave">On Leave</span></td>
                                        <td>Jun 01, 2021</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Edit</button>
                                            <button class="btn btn-sm btn-outline-warning">Reset Password</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Payroll -->
                <section id="payroll" class="content-section">
                    <h2>Payroll Management</h2>
                    <div class="payroll-actions">
                        <button class="btn btn-primary" onclick="openModal('processPayroll')">
                            <i class="fas fa-calculator"></i> Process Payroll
                        </button>
                        <button class="btn btn-success" onclick="openModal('payslips')">
                            <i class="fas fa-file-invoice"></i> Generate Payslips
                        </button>
                        <button class="btn btn-info" onclick="openModal('salaryStructure')">
                            <i class="fas fa-sitemap"></i> Salary Structure
                        </button>
                        <button class="btn btn-warning" onclick="openModal('payrollReport')">
                            <i class="fas fa-chart-bar"></i> Payroll Report
                        </button>
                    </div>
                    
                    <div class="payroll-overview">
                        <h3>Current Payroll Summary</h3>
                        <div class="payroll-stats">
                            <div class="payroll-stat">
                                <h4>Total Monthly Payroll</h4>
                                <div class="amount">UGX 45,000,000</div>
                                <small>For <?php echo $active_staff; ?> active staff</small>
                            </div>
                            <div class="payroll-stat">
                                <h4>Last Processed</h4>
                                <div class="date">April 30, 2026</div>
                                <small>Next processing: May 31, 2026</small>
                            </div>
                            <div class="payroll-stat">
                                <h4>Pending Approvals</h4>
                                <div class="count">3</div>
                                <small>Awaiting management approval</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Leave Management -->
                <section id="leave" class="content-section">
                    <h2>Leave Management</h2>
                    <div class="leave-actions">
                        <button class="btn btn-primary" onclick="openModal('leaveRequest')">
                            <i class="fas fa-plus"></i> New Leave Request
                        </button>
                        <button class="btn btn-success" onclick="openModal('approveLeave')">
                            <i class="fas fa-check"></i> Approve Leave
                        </button>
                        <button class="btn btn-info" onclick="openModal('leaveCalendar')">
                            <i class="fas fa-calendar"></i> Leave Calendar
                        </button>
                        <button class="btn btn-warning" onclick="openModal('leaveReport')">
                            <i class="fas fa-chart-bar"></i> Leave Report
                        </button>
                    </div>
                    
                    <div class="leave-overview">
                        <h3>Recent Leave Requests</h3>
                        <div class="leave-requests">
                            <div class="leave-item">
                                <div class="leave-header">
                                    <h4>Dr. Sarah Johnson</h4>
                                    <span class="status-badge pending">Pending</span>
                                </div>
                                <div class="leave-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Annual Leave</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Duration:</span>
                                        <strong>May 15-20, 2026 (5 days)</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Reason:</span>
                                        <strong>Family vacation</strong>
                                    </div>
                                </div>
                                <div class="leave-actions">
                                    <button class="btn btn-sm btn-outline-success">Approve</button>
                                    <button class="btn btn-sm btn-outline-danger">Reject</button>
                                </div>
                            </div>
                            
                            <div class="leave-item">
                                <div class="leave-header">
                                    <h4>Prof. Michael Brown</h4>
                                    <span class="status-badge approved">Approved</span>
                                </div>
                                <div class="leave-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Sick Leave</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Duration:</span>
                                        <strong>May 10-12, 2026 (3 days)</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Reason:</span>
                                        <strong>Medical appointment</strong>
                                    </div>
                                </div>
                                <div class="leave-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Performance -->
                <section id="performance" class="content-section">
                    <h2>Performance Management</h2>
                    <div class="performance-actions">
                        <button class="btn btn-primary" onclick="openModal('performanceReview')">
                            <i class="fas fa-star"></i> Performance Review
                        </button>
                        <button class="btn btn-success" onclick="openModal('kpiTracking')">
                            <i class="fas fa-chart-line"></i> KPI Tracking
                        </button>
                        <button class="btn btn-info" onclick="openModal('goalsSetting')">
                            <i class="fas fa-bullseye"></i> Goals Setting
                        </button>
                        <button class="btn btn-warning" onclick="openModal('performanceReport')">
                            <i class="fas fa-file-alt"></i> Performance Report
                        </button>
                    </div>
                    
                    <div class="performance-overview">
                        <h3>Performance Summary</h3>
                        <div class="performance-stats">
                            <div class="perf-stat">
                                <h4>Staff Performance Rating</h4>
                                <div class="rating">4.2 / 5.0</div>
                                <small>Average across all staff</small>
                            </div>
                            <div class="perf-stat">
                                <h4>Reviews Completed</h4>
                                <div class="count">38 / 48</div>
                                <small>This quarter</small>
                            </div>
                            <div class="perf-stat">
                                <h4>Goals Achievement</h4>
                                <div class="percentage">87%</div>
                                <small>Overall goal completion rate</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent HR Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon'] ?? 'check-circle'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['user_name'] ?? 'HR Manager'; ?></strong> <?php echo $activity['action'] ?? $activity['activity'] ?? 'Activity'; ?></p>
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
                case 'addStaff':
                    modalTitle.textContent = 'Add New Staff Member';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Surname</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Position</label>
                                        <select class="form-control" required>
                                            <option value="">Select Position</option>
                                            <option value="lecturer">Lecturer</option>
                                            <option value="senior-lecturer">Senior Lecturer</option>
                                            <option value="lab-technician">Laboratory Technician</option>
                                            <option value="admin-staff">Administrative Staff</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Department</label>
                                        <select class="form-control" required>
                                            <option value="">Select Department</option>
                                            <option value="academic">Academic</option>
                                            <option value="administrative">Administrative</option>
                                            <option value="support">Support Services</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Employment Type</label>
                                        <select class="form-control" required>
                                            <option value="">Select Type</option>
                                            <option value="full-time">Full-time</option>
                                            <option value="part-time">Part-time</option>
                                            <option value="contract">Contract</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" required></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'processPayroll':
                    modalTitle.textContent = 'Process Monthly Payroll';
                    modalBody.innerHTML = `
                        <div class="payroll-processing">
                            <h5>Payroll Processing for May 2026</h5>
                            <div class="payroll-summary">
                                <div class="summary-item">
                                    <strong>Total Staff:</strong> 48
                                </div>
                                <div class="summary-item">
                                    <strong>Gross Payroll:</strong> UGX 45,000,000
                                </div>
                                <div class="summary-item">
                                    <strong>Deductions:</strong> UGX 5,400,000
                                </div>
                                <div class="summary-item">
                                    <strong>Net Payroll:</strong> UGX 39,600,000
                                </div>
                            </div>
                            <div class="payroll-actions">
                                <h6>Processing Options</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeAllowances" checked>
                                    <label class="form-check-label" for="includeAllowances">Include allowances and bonuses</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="generatePayslips" checked>
                                    <label class="form-check-label" for="generatePayslips">Generate payslips automatically</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications">
                                    <label class="form-check-label" for="emailNotifications">Send email notifications to staff</label>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'leaveRequest':
                    modalTitle.textContent = 'New Leave Request';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Staff Member</label>
                                <select class="form-control" required>
                                    <option value="">Select Staff</option>
                                    <option value="dr-sarah">Dr. Sarah Johnson</option>
                                    <option value="prof-michael">Prof. Michael Brown</option>
                                    <option value="mrs-grace">Mrs. Grace Nakato</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Leave Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="annual">Annual Leave</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="maternity">Maternity Leave</option>
                                    <option value="paternity">Paternity Leave</option>
                                    <option value="compassionate">Compassionate Leave</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <textarea class="form-control" rows="3" required></textarea>
                            </div>
                        </form>
                    `;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }
    </script>
    
    <!-- Student Profile Modal -->
    <?php echo displayStudentProfileModal(''); ?>
    
    <!-- Student Profile Styles -->
    <?php echo getStudentProfileStyles(); ?>
    
    <script>
    // Student profile functions
    function viewFullProfile(studentId) {
        showStudentProfileModal(studentId);
    }
    
    function editStudent(studentId) {
        window.location.href = '../student_accounts_management.php?action=edit&student_id=' + studentId;
    }
    
    function viewAcademic(studentId) {
        window.location.href = '../academic_records_management.php?student_id=' + studentId;
    }
    
    function viewFees(studentId) {
        window.location.href = '../fee_management.php?student_id=' + studentId;
    }
    
    function sendMessage(studentId) {
        alert('Messaging functionality would be implemented here for student: ' + studentId);
    }
    
    function printProfile(studentId) {
        window.print();
    }
    </script>
</body>
</html>
