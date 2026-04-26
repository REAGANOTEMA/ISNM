<?php
session_start();
include_once '../includes/config.php';
include_once '../includes/functions.php';
include_once '../includes/photo_upload.php';
include_once '../includes/student_profile_component.php';

// Check if user is logged in and has Director General role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Director General') {
    header('Location: ../login.php');
    exit();
}

// Get system statistics
$total_students_sql = "SELECT COUNT(*) as count FROM students WHERE status = 'active'";
$total_students_result = executeQuery($total_students_sql);
$total_students = $total_students_result[0]['count'];

$total_staff_sql = "SELECT COUNT(*) as count FROM users WHERE role != 'Students' AND status = 'active'";
$total_staff_result = executeQuery($total_staff_sql);
$total_staff = $total_staff_result[0]['count'];

$total_applications_sql = "SELECT COUNT(*) as count FROM applications";
$total_applications_result = executeQuery($total_applications_sql);
$total_applications = $total_applications_result[0]['count'];

$pending_applications_sql = "SELECT COUNT(*) as count FROM applications WHERE status = 'pending'";
$pending_applications_result = executeQuery($pending_applications_sql);
$pending_applications = $pending_applications_result[0]['count'];

// Get financial overview
$total_collections_sql = "SELECT SUM(amount_paid) as total FROM fee_payments WHERE status = 'verified'";
$total_collections_result = executeQuery($total_collections_sql);
$total_collections = $total_collections_result[0]['total'] ?? 0;

$outstanding_fees_sql = "SELECT SUM(balance) as total FROM student_fee_accounts WHERE balance > 0";
$outstanding_fees_result = executeQuery($outstanding_fees_sql);
$outstanding_fees = $outstanding_fees_result[0]['total'] ?? 0;

// Get recent students for profile display
$recent_students_sql = "SELECT * FROM students ORDER BY created_at DESC LIMIT 12";
$recent_students = executeQuery($recent_students_sql);

// Get recent activities from all system modules
$recent_activities = $conn->query("
    SELECT 
        al.*, 
        u.first_name, 
        u.surname, 
        u.role as user_role,
        CASE 
            WHEN al.module_affected = 'students' THEN 'Student Affairs'
            WHEN al.module_affected = 'finance' THEN 'Financial Affairs'
            WHEN al.module_affected = 'academic' THEN 'Academic Affairs'
            WHEN al.module_affected = 'hr' THEN 'Human Resources'
            WHEN al.module_affected = 'ict' THEN 'ICT Services'
            ELSE al.module_affected
        END as department_name
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.activity_date DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get system-wide statistics
$system_stats = [
    'total_logins_today' => $conn->query("SELECT COUNT(*) as count FROM login_logs WHERE DATE(login_time) = CURDATE()")->fetch_assoc()['count'],
    'total_payments_today' => $conn->query("SELECT COUNT(*) as count FROM fee_payments WHERE DATE(payment_date) = CURDATE()")->fetch_assoc()['count'],
    'total_applications_today' => $conn->query("SELECT COUNT(*) as count FROM applications WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'],
    'system_uptime' => '99.8%',
    'active_sessions' => $conn->query("SELECT COUNT(*) as count FROM active_sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)")->fetch_assoc()['count']
];

// Get department performance metrics
$department_performance = $conn->query("
    SELECT 
        department,
        performance_score,
        efficiency_rate,
        satisfaction_rate,
        last_updated
    FROM department_performance 
    ORDER BY performance_score DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director General Dashboard - ISNM</title>
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
                <small><?php echo $_SESSION['user_name']; ?></small>
                <span class="badge bg-primary">Director General</span>
            </div>
            
            <nav class="sidebar-menu">
                <a href="#overview" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard Overview
                </a>
                <a href="#academic" class="nav-link">
                    <i class="fas fa-graduation-cap"></i> Academic Management
                </a>
                <a href="#financial" class="nav-link">
                    <i class="fas fa-coins"></i> Financial Overview
                </a>
                <a href="#staff" class="nav-link">
                    <i class="fas fa-users"></i> Staff Management
                </a>
                <a href="#students" class="nav-link">
                    <i class="fas fa-user-graduate"></i> Student Affairs
                </a>
                <a href="#reports" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Reports & Analytics
                </a>
                <a href="#communications" class="nav-link">
                    <i class="fas fa-envelope"></i> Communications
                </a>
                <a href="#settings" class="nav-link">
                    <i class="fas fa-cog"></i> System Settings
                </a>
                <a href="#audit" class="nav-link">
                    <i class="fas fa-shield-alt"></i> Security & Audit
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
                    <h1>Director General Dashboard</h1>
                    <p>Complete oversight of Iganga School of Nursing and Midwifery operations</p>
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
                <!-- Key Statistics -->
                <section id="overview" class="content-section">
                    <h2>System Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_students); ?></h3>
                                <p>Total Students</p>
                                <small>Active enrollment</small>
                            </div>
                        </div>
                        
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_staff); ?></h3>
                                <p>Total Staff</p>
                                <small>All departments</small>
                            </div>
                        </div>
                        
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_applications); ?></h3>
                                <p>Total Applications</p>
                                <small>All time</small>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($pending_applications); ?></h3>
                                <p>Pending Applications</p>
                                <small>Require review</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Financial Overview -->
                    <div class="financial-overview">
                        <h3>Financial Summary</h3>
                        <div class="financial-stats">
                            <div class="financial-stat">
                                <h4>UGX <?php echo number_format($total_collections); ?></h4>
                                <p>Total Collections</p>
                                <span class="trend positive">
                                    <i class="fas fa-arrow-up"></i> 12% from last month
                                </span>
                            </div>
                            <div class="financial-stat">
                                <h4>UGX <?php echo number_format($outstanding_fees); ?></h4>
                                <p>Outstanding Fees</p>
                                <span class="trend negative">
                                    <i class="fas fa-arrow-down"></i> 5% from last month
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Student Profiles Overview -->
                <section id="student-profiles" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Student Profiles Overview</h2>
                        <div>
                            <button class="btn btn-primary" onclick="window.location.href='../student_accounts_management.php'">
                                <i class="fas fa-users"></i> Manage All Students
                            </button>
                            <button class="btn btn-success" onclick="window.location.href='../bulk_photo_upload.php'">
                                <i class="fas fa-camera"></i> Photo Upload
                            </button>
                            <button class="btn btn-info" onclick="window.location.href='../import_student_data.php'">
                                <i class="fas fa-upload"></i> Import Students
                            </button>
                        </div>
                    </div>
                    
                    <!-- Student Search -->
                    <?php echo displayStudentSearchBox('Search students by name, ID, or phone...', 'directorSearchResults'); ?>
                    
                    <!-- Student Profile Cards -->
                    <div class="row mt-4">
                        <?php foreach ($recent_students as $student): ?>
                            <div class="col-md-6 col-lg-3 mb-4">
                                <?php echo displayStudentProfileCard($student['student_id'], 'compact'); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <!-- System Activity Monitor -->
                <section id="system-monitor" class="content-section">
                    <h2>System Activity Monitor</h2>
                    <div class="system-stats-grid">
                        <div class="system-stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $system_stats['total_logins_today']; ?></h3>
                                <p>Logins Today</p>
                                <small>Active users</small>
                            </div>
                        </div>
                        
                        <div class="system-stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $system_stats['total_payments_today']; ?></h3>
                                <p>Payments Today</p>
                                <small>Transactions</small>
                            </div>
                        </div>
                        
                        <div class="system-stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $system_stats['total_applications_today']; ?></h3>
                                <p>Applications Today</p>
                                <small>New submissions</small>
                            </div>
                        </div>
                        
                        <div class="system-stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $system_stats['active_sessions']; ?></h3>
                                <p>Active Sessions</p>
                                <small>Currently logged in</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Department Performance -->
                    <h3>Department Performance Metrics</h3>
                    <div class="department-grid">
                        <?php foreach ($department_performance as $dept): ?>
                        <div class="dept-card">
                            <div class="dept-header">
                                <h3><?php echo $dept['department']; ?></h3>
                                <span class="dept-status active">Active</span>
                            </div>
                            <div class="dept-metrics">
                                <div class="metric">
                                    <span>Performance Score</span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $dept['performance_score']; ?>%"></div>
                                    </div>
                                    <span class="value"><?php echo $dept['performance_score']; ?>%</span>
                                </div>
                                <div class="metric">
                                    <span>Efficiency Rate</span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $dept['efficiency_rate']; ?>%"></div>
                                    </div>
                                    <span class="value"><?php echo $dept['efficiency_rate']; ?>%</span>
                                </div>
                                <div class="metric">
                                    <span>Satisfaction Rate</span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $dept['satisfaction_rate']; ?>%"></div>
                                    </div>
                                    <span class="value"><?php echo $dept['satisfaction_rate']; ?>%</span>
                                </div>
                            </div>
                            <div class="dept-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewDepartmentDetails('<?php echo $dept['department']; ?>')">View Details</button>
                                <button class="btn btn-sm btn-outline-info" onclick="viewDepartmentReports('<?php echo $dept['department']; ?>')">Reports</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <!-- Staff Management -->
                <section id="staff-management" class="content-section">
                    <h2>Staff Management Overview</h2>
                    <div class="staff-actions">
                        <button class="btn btn-primary" onclick="openModal('addStaff')">
                            <i class="fas fa-user-plus"></i> Add Staff
                        </button>
                        <button class="btn btn-success" onclick="openModal('editStaff')">
                            <i class="fas fa-user-edit"></i> Edit Staff
                        </button>
                        <button class="btn btn-info" onclick="openModal('resetPassword')">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                        <button class="btn btn-warning" onclick="openModal('managePermissions')">
                            <i class="fas fa-shield-alt"></i> Manage Permissions
                        </button>
                        <button class="btn btn-danger" onclick="openModal('deleteStaff')">
                            <i class="fas fa-user-times"></i> Remove Staff
                        </button>
                    </div>
                    
                    <div class="staff-overview">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $staff_query = "SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.role != 'Student' ORDER BY u.surname, u.first_name LIMIT 10";
                                    $staff_result = $conn->query($staff_query);
                                    while ($staff_member = $staff_result->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <img src="../images/default-avatar.png" alt="User">
                                                <span><?php echo $staff_member['first_name'] . ' ' . $staff_member['surname']; ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-primary"><?php echo $staff_member['role']; ?></span></td>
                                        <td><?php echo $staff_member['department_name'] ?? 'Administration'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $staff_member['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $staff_member['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $staff_member['last_login'] ? date('M j, Y', strtotime($staff_member['last_login'])) : 'Never'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editStaffMember(<?php echo $staff_member['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="resetStaffPassword(<?php echo $staff_member['id']; ?>)">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="viewStaffProfile(<?php echo $staff_member['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteStaffMember(<?php echo $staff_member['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- Recent Activities -->
                <section class="content-section">
                    <h2>Recent System Activities</h2>
                    <div class="activities-table">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Activity</th>
                                        <th>Department</th>
                                        <th>Date/Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <img src="../images/default-avatar.png" alt="User">
                                                <span><?php echo $activity['user_role']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $activity['activity_description']; ?></td>
                                        <td><?php echo $activity['module_affected'] ?? 'System'; ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($activity['activity_date'])); ?></td>
                                        <td>
                                            <span class="status-badge success">Completed</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- Quick Actions -->
                <section class="content-section">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions">
                        <button class="action-btn" onclick="openModal('generateReport')">
                            <i class="fas fa-file-alt"></i>
                            <span>Generate Report</span>
                        </button>
                        <button class="action-btn" onclick="openModal('sendAnnouncement')">
                            <i class="fas fa-bullhorn"></i>
                            <span>Send Announcement</span>
                        </button>
                        <button class="action-btn" onclick="openModal('approveBudget')">
                            <i class="fas fa-check-circle"></i>
                            <span>Approve Budget</span>
                        </button>
                        <button class="action-btn" onclick="openModal('viewAnalytics')">
                            <i class="fas fa-chart-line"></i>
                            <span>View Analytics</span>
                        </button>
                        <button class="action-btn" onclick="openModal('systemAudit')">
                            <i class="fas fa-shield-alt"></i>
                            <span>System Audit</span>
                        </button>
                        <button class="action-btn" onclick="openModal('emergencyAlert')">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Emergency Alert</span>
                        </button>
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
                case 'generateReport':
                    modalTitle.textContent = 'Generate System Report';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Report Type</label>
                                <select class="form-control">
                                    <option>Monthly Performance Report</option>
                                    <option>Financial Summary</option>
                                    <option>Academic Performance</option>
                                    <option>Staff Performance</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Period</label>
                                <select class="form-control">
                                    <option>Last 30 Days</option>
                                    <option>Last Quarter</option>
                                    <option>Last 6 Months</option>
                                    <option>Last Year</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Format</label>
                                <select class="form-control">
                                    <option>PDF</option>
                                    <option>Excel</option>
                                    <option>Word</option>
                                </select>
                            </div>
                        </form>
                    `;
                    break;
                case 'sendAnnouncement':
                    modalTitle.textContent = 'Send System Announcement';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Announcement Title</label>
                                <input type="text" class="form-control" placeholder="Enter title">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4" placeholder="Enter your announcement"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Target Audience</label>
                                <select class="form-control">
                                    <option>All Staff and Students</option>
                                    <option>Staff Only</option>
                                    <option>Students Only</option>
                                    <option>Specific Department</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select class="form-control">
                                    <option>Normal</option>
                                    <option>High</option>
                                    <option>Urgent</option>
                                </select>
                            </div>
                        </form>
                    `;
                    break;
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
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select class="form-control" required>
                                            <option value="">Select Role</option>
                                            <option value="Director General">Director General</option>
                                            <option value="CEO">Chief Executive Officer</option>
                                            <option value="Director Academics">Director Academics</option>
                                            <option value="Director ICT">Director ICT</option>
                                            <option value="Director Finance">Director Finance</option>
                                            <option value="Principal">Principal</option>
                                            <option value="Deputy Principal">Deputy Principal</option>
                                            <option value="School Bursar">School Bursar</option>
                                            <option value="Academic Registrar">Academic Registrar</option>
                                            <option value="HR Manager">HR Manager</option>
                                            <option value="School Secretary">School Secretary</option>
                                            <option value="School Librarian">School Librarian</option>
                                            <option value="Head of Nursing">Head of Nursing</option>
                                            <option value="Head of Midwifery">Head of Midwifery</option>
                                            <option value="Senior Lecturers">Senior Lecturers</option>
                                            <option value="Lecturers">Lecturers</option>
                                            <option value="Matrons">Matrons</option>
                                            <option value="Wardens">Wardens</option>
                                            <option value="Lab Technicians">Lab Technicians</option>
                                            <option value="Non-Teaching Staff">Non-Teaching Staff</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Department</label>
                                        <select class="form-control" required>
                                            <option value="">Select Department</option>
                                            <option value="Academic Affairs">Academic Affairs</option>
                                            <option value="Financial Affairs">Financial Affairs</option>
                                            <option value="ICT Services">ICT Services</option>
                                            <option value="Administration">Administration</option>
                                            <option value="Student Affairs">Student Affairs</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Generate Password</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="generatedPassword" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="generatePassword()">Generate</button>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'editStaff':
                    modalTitle.textContent = 'Edit Staff Member';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Select Staff Member</label>
                                <select class="form-control" id="staffSelect" onchange="loadStaffDetails()" required>
                                    <option value="">Select Staff</option>
                                </select>
                            </div>
                            <div id="staffDetails">
                                <!-- Staff details will be loaded here -->
                            </div>
                        </form>
                    `;
                    break;
                case 'resetPassword':
                    modalTitle.textContent = 'Reset Staff Password';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Select Staff Member</label>
                                <select class="form-control" required>
                                    <option value="">Select Staff</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newPassword">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generatePassword()">Generate</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Reset</label>
                                <textarea class="form-control" rows="2" placeholder="Enter reason for password reset"></textarea>
                            </div>
                            <div class="alert alert-warning">
                                <strong>Warning:</strong> The staff member will be notified of the password change.
                            </div>
                        </form>
                    `;
                    break;
                case 'deleteStaff':
                    modalTitle.textContent = 'Remove Staff Member';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Select Staff Member</label>
                                <select class="form-control" required>
                                    <option value="">Select Staff</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Removal</label>
                                <select class="form-control" required>
                                    <option value="">Select Reason</option>
                                    <option value="resignation">Resignation</option>
                                    <option value="termination">Termination</option>
                                    <option value="retirement">Retirement</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Additional Notes</label>
                                <textarea class="form-control" rows="3" placeholder="Enter additional details"></textarea>
                            </div>
                            <div class="alert alert-danger">
                                <strong>Warning:</strong> This action cannot be undone. The staff member will lose access to the system.
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
        
        // Staff management functions
        function editStaffMember(staffId) {
            console.log('Editing staff member:', staffId);
            openModal('editStaff');
        }
        
        function resetStaffPassword(staffId) {
            console.log('Resetting password for staff:', staffId);
            openModal('resetPassword');
        }
        
        function viewStaffProfile(staffId) {
            console.log('Viewing staff profile:', staffId);
            // Implementation for viewing staff profile
        }
        
        function deleteStaffMember(staffId) {
            console.log('Deleting staff member:', staffId);
            openModal('deleteStaff');
        }
        
        function viewDepartmentDetails(department) {
            console.log('Viewing department details:', department);
            // Implementation for viewing department details
        }
        
        function viewDepartmentReports(department) {
            console.log('Viewing department reports:', department);
            // Implementation for viewing department reports
        }
        
        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('newPassword').value = password;
            document.getElementById('confirmPassword').value = password;
            if (document.getElementById('generatedPassword')) {
                document.getElementById('generatedPassword').value = password;
            }
        }
        
        function loadStaffDetails() {
            // Implementation for loading staff details dynamically
            console.log('Loading staff details...');
        }
        
        // Auto-refresh dashboard data
        setInterval(() => {
            // Refresh statistics
            console.log('Refreshing dashboard data...');
        }, 60000); // Every minute
    </script>
    
    <!-- Student Profile Modal -->
    <?php echo displayStudentProfileModal(''); ?>
    
    <!-- Student Profile Styles -->
    <?php echo getStudentProfileStyles(); ?>
    
    <script>
    // Override modal functions for director dashboard
    function viewFullProfile(studentId) {
        showStudentProfileModal(studentId);
    }
    
    function editStudent(studentId) {
        window.location.href = '../student_accounts_management.php?action=edit&student_id=' + studentId;
    }
    
    function viewAcademic(studentId) {
        window.location.href = '../academic_records.php?student_id=' + studentId;
    }
    
    function viewFees(studentId) {
        window.location.href = '../fee_management.php?student_id=' + studentId;
    }
    
    function sendMessage(studentId) {
        // Implement messaging functionality
        alert('Messaging functionality would be implemented here for student: ' + studentId);
    }
    
    function printProfile(studentId) {
        window.print();
    }
    </script>
</body>
</html>
