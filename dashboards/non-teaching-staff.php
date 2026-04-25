<?php
session_start();
// Check if user is logged in and has Non-Teaching Staff role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Non-Teaching Staff') {
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

// Get staff statistics
$pending_tasks = $conn->query("SELECT COUNT(*) as count FROM staff_tasks WHERE assigned_to = $user_id AND status = 'pending'")->fetch_assoc()['count'];
$completed_tasks = $conn->query("SELECT COUNT(*) as count FROM staff_tasks WHERE assigned_to = $user_id AND status = 'completed' AND DATE(completed_at) = CURDATE()")->fetch_assoc()['count'];
$leave_balance = $conn->query("SELECT days_remaining as balance FROM leave_balance WHERE staff_id = $user_id")->fetch_assoc()['balance'] ?? 0;
$attendance_rate = $conn->query("SELECT AVG(attendance_status = 'present') as rate FROM attendance WHERE staff_id = $user_id AND MONTH(attendance_date) = MONTH(CURRENT_DATE)")->fetch_assoc()['rate'] ?? 0;

// Get recent staff activities
$recent_activities = $conn->query("SELECT * FROM staff_logs WHERE staff_id = $user_id ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Non-Teaching Staff Dashboard - ISNM</title>
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
                <h4>Non-Teaching Staff Dashboard</h4>
                <p><?php echo $user['first_name'] . ' ' . $user['surname']; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Staff Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tasks">
                            <i class="fas fa-tasks"></i> Task Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#attendance">
                            <i class="fas fa-user-check"></i> Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#leave">
                            <i class="fas fa-calendar-alt"></i> Leave Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#documents">
                            <i class="fas fa-file-alt"></i> Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#training">
                            <i class="fas fa-graduation-cap"></i> Training & Development
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#communications">
                            <i class="fas fa-envelope"></i> Communications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Staff Reports
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
                    <h1>Non-Teaching Staff Dashboard</h1>
                    <p>Administrative Support & Operations</p>
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
                <!-- Staff Overview -->
                <section id="overview" class="content-section">
                    <h2>Staff Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_tasks; ?></h3>
                                <p>Pending Tasks</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $completed_tasks; ?></h3>
                                <p>Completed Today</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $leave_balance; ?></h3>
                                <p>Leave Balance (Days)</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo round($attendance_rate * 100, 1); ?>%</h3>
                                <p>Attendance Rate</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Task Management -->
                <section id="tasks" class="content-section">
                    <h2>Task Management</h2>
                    <div class="task-actions">
                        <button class="btn btn-primary" onclick="openModal('newTask')">
                            <i class="fas fa-plus"></i> New Task
                        </button>
                        <button class="btn btn-success" onclick="openModal('taskList')">
                            <i class="fas fa-list"></i> Task List
                        </button>
                        <button class="btn btn-info" onclick="openModal('taskReport')">
                            <i class="fas fa-chart-bar"></i> Task Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('taskCalendar')">
                            <i class="fas fa-calendar"></i> Task Calendar
                        </button>
                    </div>
                    
                    <div class="tasks-overview">
                        <h3>My Tasks</h3>
                        <div class="task-list">
                            <div class="task-item">
                                <div class="task-header">
                                    <h4>Prepare Monthly Report</h4>
                                    <span class="task-priority high">High Priority</span>
                                </div>
                                <div class="task-details">
                                    <div class="detail">
                                        <span>Due Date:</span>
                                        <strong>Apr 30, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Assigned By:</span>
                                        <strong>HR Manager</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-warning">In Progress</strong>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Mark Complete</button>
                                </div>
                            </div>
                            
                            <div class="task-item">
                                <div class="task-header">
                                    <h4>Update Student Records</h4>
                                    <span class="task-priority medium">Medium Priority</span>
                                </div>
                                <div class="task-details">
                                    <div class="detail">
                                        <span>Due Date:</span>
                                        <strong>Apr 25, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Assigned By:</span>
                                        <strong>Academic Registrar</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-info">Pending</strong>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Start Task</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Attendance -->
                <section id="attendance" class="content-section">
                    <h2>Attendance Management</h2>
                    <div class="attendance-actions">
                        <button class="btn btn-primary" onclick="openModal('checkIn')">
                            <i class="fas fa-sign-in-alt"></i> Check In
                        </button>
                        <button class="btn btn-success" onclick="openModal('checkOut')">
                            <i class="fas fa-sign-out-alt"></i> Check Out
                        </button>
                        <button class="btn btn-info" onclick="openModal('attendanceHistory')">
                            <i class="fas fa-history"></i> Attendance History
                        </button>
                        <button class="btn btn-warning" onclick="openModal('attendanceReport')">
                            <i class="fas fa-chart-bar"></i> Attendance Report
                        </button>
                    </div>
                    
                    <div class="attendance-overview">
                        <h3>Today's Attendance</h3>
                        <div class="attendance-status">
                            <div class="status-item">
                                <div class="status-indicator <?php echo $attendance_rate > 0 ? 'present' : 'not-checked'; ?>"></div>
                                <div class="status-info">
                                    <h4>Current Status</h4>
                                    <p><?php echo $attendance_rate > 0 ? 'Checked In' : 'Not Checked In'; ?></p>
                                </div>
                            </div>
                            
                            <div class="attendance-summary">
                                <h4>Monthly Summary</h4>
                                <div class="summary-stats">
                                    <div class="stat">
                                        <span>Days Present:</span>
                                        <strong>18</strong>
                                    </div>
                                    <div class="stat">
                                        <span>Days Absent:</span>
                                        <strong>2</strong>
                                    </div>
                                    <div class="stat">
                                        <span>Leave Days:</span>
                                        <strong>1</strong>
                                    </div>
                                    <div class="stat">
                                        <span>Attendance Rate:</span>
                                        <strong><?php echo round($attendance_rate * 100, 1); ?>%</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Leave Management -->
                <section id="leave" class="content-section">
                    <h2>Leave Management</h2>
                    <div class="leave-actions">
                        <button class="btn btn-primary" onclick="openModal('leaveRequest')">
                            <i class="fas fa-calendar-plus"></i> Request Leave
                        </button>
                        <button class="btn btn-success" onclick="openModal('leaveBalance')">
                            <i class="fas fa-balance-scale"></i> Leave Balance
                        </button>
                        <button class="btn btn-info" onclick="openModal('leaveHistory')">
                            <i class="fas fa-history"></i> Leave History
                        </button>
                        <button class="btn btn-warning" onclick="openModal('leavePolicy')">
                            <i class="fas fa-book"></i> Leave Policy
                        </button>
                    </div>
                    
                    <div class="leave-overview">
                        <h3>Leave Balance Summary</h3>
                        <div class="leave-balance">
                            <div class="balance-item">
                                <h4>Annual Leave</h4>
                                <div class="balance-info">
                                    <div class="balance-days"><?php echo $leave_balance; ?></div>
                                    <small>days remaining</small>
                                </div>
                            </div>
                            
                            <div class="balance-item">
                                <h4>Sick Leave</h4>
                                <div class="balance-info">
                                    <div class="balance-days">10</div>
                                    <small>days remaining</small>
                                </div>
                            </div>
                            
                            <div class="balance-item">
                                <h4>Compassionate Leave</h4>
                                <div class="balance-info">
                                    <div class="balance-days">5</div>
                                    <small>days remaining</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="recent-leave">
                            <h4>Recent Leave Requests</h4>
                            <div class="leave-item">
                                <div class="leave-header">
                                    <h5>Annual Leave - Family Visit</h5>
                                    <span class="leave-status approved">Approved</span>
                                </div>
                                <div class="leave-details">
                                    <div class="detail">
                                        <span>Period:</span>
                                        <strong>Apr 10-12, 2026 (3 days)</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Applied:</span>
                                        <strong>Apr 1, 2026</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Documents -->
                <section id="documents" class="content-section">
                    <h2>Document Management</h2>
                    <div class="document-actions">
                        <button class="btn btn-primary" onclick="openModal('uploadDocument')">
                            <i class="fas fa-upload"></i> Upload Document
                        </button>
                        <button class="btn btn-success" onclick="openModal('documentLibrary')">
                            <i class="fas fa-folder"></i> Document Library
                        </button>
                        <button class="btn btn-info" onclick="openModal('sharedDocuments')">
                            <i class="fas fa-share"></i> Shared Documents
                        </button>
                        <button class="btn btn-warning" onclick="openModal('documentArchive')">
                            <i class="fas fa-archive"></i> Document Archive
                        </button>
                    </div>
                    
                    <div class="documents-overview">
                        <h3>My Documents</h3>
                        <div class="documents-list">
                            <div class="document-item">
                                <div class="document-header">
                                    <h4>Employment Contract</h4>
                                    <span class="document-type">PDF</span>
                                </div>
                                <div class="document-details">
                                    <div class="detail">
                                        <span>Size:</span>
                                        <strong>1.2 MB</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Uploaded:</span>
                                        <strong>Jan 15, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Category:</span>
                                        <strong>Employment</strong>
                                    </div>
                                </div>
                                <div class="document-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Training & Development -->
                <section id="training" class="content-section">
                    <h2>Training & Development</h2>
                    <div class="training-actions">
                        <button class="btn btn-primary" onclick="openModal('trainingRequest')">
                            <i class="fas fa-graduation-cap"></i> Training Request
                        </button>
                        <button class="btn btn-success" onclick="openModal('trainingSchedule')">
                            <i class="fas fa-calendar"></i> Training Schedule
                        </button>
                        <button class="btn btn-info" onclick="openModal('certifications')">
                            <i class="fas fa-certificate"></i> Certifications
                        </button>
                        <button class="btn btn-warning" onclick="openModal('skillsAssessment')">
                            <i class="fas fa-chart-line"></i> Skills Assessment
                        </button>
                    </div>
                    
                    <div class="training-overview">
                        <h3>Upcoming Training</h3>
                        <div class="training-list">
                            <div class="training-item">
                                <div class="training-header">
                                    <h4>Office Management Workshop</h4>
                                    <span class="training-date">May 5-6, 2026</span>
                                </div>
                                <div class="training-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Professional Development</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Location:</span>
                                        <strong>ISNM Training Room</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-success">Registered</strong>
                                    </div>
                                </div>
                                <div class="training-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Download Materials</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Communications -->
                <section id="communications" class="content-section">
                    <h2>Communications</h2>
                    <div class="communication-actions">
                        <button class="btn btn-primary" onclick="openModal('sendMessage')">
                            <i class="fas fa-envelope"></i> Send Message
                        </button>
                        <button class="btn btn-success" onclick="openModal('inbox')">
                            <i class="fas fa-inbox"></i> Inbox
                        </button>
                        <button class="btn btn-info" onclick="openModal('announcements')">
                            <i class="fas fa-bullhorn"></i> Announcements
                        </button>
                        <button class="btn btn-warning" onclick="openModal('notifications')">
                            <i class="fas fa-bell"></i> Notifications
                        </button>
                    </div>
                    
                    <div class="communications-overview">
                        <h3>Recent Messages</h3>
                        <div class="message-list">
                            <div class="message-item">
                                <div class="message-header">
                                    <h4>From: HR Manager</h4>
                                    <span class="message-date">Apr 22, 2026</span>
                                </div>
                                <div class="message-content">
                                    <p>Reminder: Monthly staff meeting tomorrow at 10 AM in the main hall.</p>
                                </div>
                                <div class="message-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Reply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Staff Activities</h2>
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
                case 'newTask':
                    modalTitle.textContent = 'Create New Task';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Task Title</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Task Description</label>
                                <textarea class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Priority Level</label>
                                        <select class="form-control" required>
                                            <option value="">Select Priority</option>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Task Category</label>
                                <select class="form-control" required>
                                    <option value="">Select Category</option>
                                    <option value="administrative">Administrative</option>
                                    <option value="technical">Technical</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="communication">Communication</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assigned By</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Required Resources</label>
                                <textarea class="form-control" rows="2"></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'checkIn':
                    modalTitle.textContent = 'Check In';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Check In Time</label>
                                <input type="time" class="form-control" id="checkInTime" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Today's Tasks</label>
                                <textarea class="form-control" rows="3" placeholder="List your main tasks for today..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                            </div>
                        </form>
                    `;
                    // Set current time
                    setTimeout(() => {
                        const now = new Date();
                        const time = now.toTimeString().slice(0,5);
                        document.getElementById('checkInTime').value = time;
                    }, 100);
                    break;
                case 'leaveRequest':
                    modalTitle.textContent = 'Request Leave';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Leave Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Leave Type</option>
                                    <option value="annual">Annual Leave</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="maternity">Maternity Leave</option>
                                    <option value="paternity">Paternity Leave</option>
                                    <option value="compassionate">Compassionate Leave</option>
                                    <option value="study">Study Leave</option>
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
                                <label class="form-label">Number of Days</label>
                                <input type="number" class="form-control" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Leave</label>
                                <textarea class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Emergency Contact During Leave</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Handover Arrangements</label>
                                <textarea class="form-control" rows="3" placeholder="Describe how your responsibilities will be covered..."></textarea>
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
