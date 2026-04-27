<?php
include_once '../includes/config.php';
// Check if user is logged in and has Matron role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Matrons') {
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

// Get matron statistics (using fallback data only)
$total_students = 150; // Fallback value
$total_staff = 4; // Fallback value
$recent_applications = 8; // Fallback value
$active_programs = 2; // Fallback value
$assigned_students = 75; // Fallback value
$welfare_cases = 12; // Fallback value
$counseling_sessions = 3; // Fallback value
$health_incidents = 2; // Fallback value

// Get recent activities (using a simple approach)
$recent_activities = [
    ['activity' => 'Dashboard accessed', 'created_at' => date('Y-m-d H:i:s')],
    ['activity' => 'Student health check completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrons Dashboard - ISNM</title>
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
                <h4>Matrons Dashboard</h4>
                <p><?php echo ($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? $user['last_name'] ?? ''); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Welfare Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#students">
                            <i class="fas fa-users"></i> Student Welfare
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#counseling">
                            <i class="fas fa-comments"></i> Counseling Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#health">
                            <i class="fas fa-heartbeat"></i> Health Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#accommodation">
                            <i class="fas fa-bed"></i> Accommodation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#discipline">
                            <i class="fas fa-gavel"></i> Student Discipline
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#activities">
                            <i class="fas fa-calendar-alt"></i> Student Activities
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Welfare Reports
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
                    <h1>Matrons Dashboard</h1>
                    <p>Student Welfare & Care Management</p>
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
                <!-- Welfare Overview -->
                <section id="overview" class="content-section">
                    <h2>Student Welfare Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $assigned_students; ?></h3>
                                <p>Assigned Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-injured"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $welfare_cases; ?></h3>
                                <p>Open Welfare Cases</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $counseling_sessions; ?></h3>
                                <p>Today's Sessions</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $health_incidents; ?></h3>
                                <p>Pending Health Issues</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Welfare -->
                <section id="students" class="content-section">
                    <h2>Student Welfare Management</h2>
                    <div class="welfare-actions">
                        <button class="btn btn-primary" onclick="openModal('studentProfile')">
                            <i class="fas fa-user"></i> Student Profile
                        </button>
                        <button class="btn btn-success" onclick="openModal('welfareCase')">
                            <i class="fas fa-user-injured"></i> Welfare Case
                        </button>
                        <button class="btn btn-info" onclick="openModal('homeVisit')">
                            <i class="fas fa-home"></i> Home Visit
                        </button>
                        <button class="btn btn-warning" onclick="openModal('emergencyContact')">
                            <i class="fas fa-phone-alt"></i> Emergency Contact
                        </button>
                    </div>
                    
                    <div class="welfare-overview">
                        <h3>Recent Welfare Cases</h3>
                        <div class="welfare-cases">
                            <div class="case-card">
                                <div class="case-header">
                                    <h4>Student Mary - Homesickness</h4>
                                    <span class="case-date">Apr 22, 2026</span>
                                </div>
                                <div class="case-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Emotional Support</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-warning">In Progress</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Actions Taken:</span>
                                        <strong>Counseling session, Parent contact</strong>
                                    </div>
                                </div>
                                <div class="case-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Update Case</button>
                                </div>
                            </div>
                            
                            <div class="case-card">
                                <div class="case-header">
                                    <h4>Student John - Financial Need</h4>
                                    <span class="case-date">Apr 20, 2026</span>
                                </div>
                                <div class="case-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Financial Support</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-info">Under Review</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Actions Taken:</strong>
                                        <strong>Documentation, Scholarship application</strong>
                                    </div>
                                </div>
                                <div class="case-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Update Case</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Counseling Services -->
                <section id="counseling" class="content-section">
                    <h2>Counseling Services</h2>
                    <div class="counseling-actions">
                        <button class="btn btn-primary" onclick="openModal('scheduleSession')">
                            <i class="fas fa-calendar-plus"></i> Schedule Session
                        </button>
                        <button class="btn btn-success" onclick="openModal('counselingRecord')">
                            <i class="fas fa-file-medical"></i> Counseling Record
                        </button>
                        <button class="btn btn-info" onclick="openModal('groupCounseling')">
                            <i class="fas fa-users"></i> Group Counseling
                        </button>
                        <button class="btn btn-warning" onclick="openModal('referral')">
                            <i class="fas fa-share"></i> Referral Services
                        </button>
                    </div>
                    
                    <div class="counseling-overview">
                        <h3>Today's Counseling Schedule</h3>
                        <div class="counseling-schedule">
                            <div class="session-item">
                                <div class="session-header">
                                    <h4>Individual Counseling - Mary Student</h4>
                                    <span class="session-time">10:00 AM - 11:00 AM</span>
                                </div>
                                <div class="session-details">
                                    <div class="detail">
                                        <span>Topic:</span>
                                        <strong>Homesickness & Adjustment</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Individual Session</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Location:</span>
                                        <strong>Counseling Room A</strong>
                                    </div>
                                </div>
                                <div class="session-actions">
                                    <button class="btn btn-sm btn-outline-primary">Start Session</button>
                                    <button class="btn btn-sm btn-outline-info">Reschedule</button>
                                </div>
                            </div>
                            
                            <div class="session-item">
                                <div class="session-header">
                                    <h4>Group Counseling - First Year Students</h4>
                                    <span class="session-time">2:00 PM - 3:30 PM</span>
                                </div>
                                <div class="session-details">
                                    <div class="detail">
                                        <span>Topic:</span>
                                        <strong>Academic Stress Management</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Group Session</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Location:</span>
                                        <strong>Main Hall</strong>
                                    </div>
                                </div>
                                <div class="session-actions">
                                    <button class="btn btn-sm btn-outline-primary">Start Session</button>
                                    <button class="btn btn-sm btn-outline-info">View Participants</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Health Services -->
                <section id="health" class="content-section">
                    <h2>Health Services</h2>
                    <div class="health-actions">
                        <button class="btn btn-primary" onclick="openModal('healthCheck')">
                            <i class="fas fa-stethoscope"></i> Health Check
                        </button>
                        <button class="btn btn-success" onclick="openModal('medicalRecord')">
                            <i class="fas fa-file-medical"></i> Medical Record
                        </button>
                        <button class="btn btn-info" onclick="openModal('medication')">
                            <i class="fas fa-pills"></i> Medication Management
                        </button>
                        <button class="btn btn-warning" onclick="openModal('emergency')">
                            <i class="fas fa-ambulance"></i> Emergency Response
                        </button>
                    </div>
                    
                    <div class="health-overview">
                        <h3>Recent Health Incidents</h3>
                        <div class="health-incidents">
                            <div class="incident-card">
                                <div class="incident-header">
                                    <h4>Student Sarah - Fever</h4>
                                    <span class="incident-date">Apr 22, 2026</span>
                                </div>
                                <div class="incident-details">
                                    <div class="detail">
                                        <span>Symptoms:</span>
                                        <strong>Fever, headache, fatigue</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Action:</span>
                                        <strong>Referred to school clinic</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-warning">Under Observation</strong>
                                    </div>
                                </div>
                                <div class="incident-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-success">Update Status</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Accommodation -->
                <section id="accommodation" class="content-section">
                    <h2>Accommodation Management</h2>
                    <div class="accommodation-actions">
                        <button class="btn btn-primary" onclick="openModal('roomAssignment')">
                            <i class="fas fa-bed"></i> Room Assignment
                        </button>
                        <button class="btn btn-success" onclick="openModal('roomInspection')">
                            <i class="fas fa-clipboard-check"></i> Room Inspection
                        </button>
                        <button class="btn btn-info" onclick="openModal('maintenanceRequest')">
                            <i class="fas fa-tools"></i> Maintenance Request
                        </button>
                        <button class="btn btn-warning" onclick="openModal('accommodationReport')">
                            <i class="fas fa-chart-bar"></i> Accommodation Report
                        </button>
                    </div>
                    
                    <div class="accommodation-overview">
                        <h3>Hostel Overview</h3>
                        <div class="hostel-stats">
                            <div class="hostel-stat">
                                <h4>Girls Hostel A</h4>
                                <div class="occupancy">45/50 beds occupied</div>
                                <small>90% occupancy</small>
                            </div>
                            <div class="hostel-stat">
                                <h4>Girls Hostel B</h4>
                                <div class="occupancy">38/40 beds occupied</div>
                                <small>95% occupancy</small>
                            </div>
                            <div class="hostel-stat">
                                <h4>Maintenance Issues</h4>
                                <div class="issues-count">3 pending</div>
                                <small>Requires attention</small>
                            </div>
                            <div class="hostel-stat">
                                <h4>Room Inspections</h4>
                                <div class="inspection-rate">85%</div>
                                <small>Completed this month</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Discipline -->
                <section id="discipline" class="content-section">
                    <h2>Student Discipline</h2>
                    <div class="discipline-actions">
                        <button class="btn btn-primary" onclick="openModal('disciplineCase')">
                            <i class="fas fa-gavel"></i> Discipline Case
                        </button>
                        <button class="btn btn-success" onclick="openModal('disciplinaryAction')">
                            <i class="fas fa-exclamation-triangle"></i> Disciplinary Action
                        </button>
                        <button class="btn btn-info" onclick="openModal('behaviorReport')">
                            <i class="fas fa-chart-line"></i> Behavior Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('parentMeeting')">
                            <i class="fas fa-users"></i> Parent Meeting
                        </button>
                    </div>
                    
                    <div class="discipline-overview">
                        <h3>Recent Discipline Cases</h3>
                        <div class="discipline-cases">
                            <div class="discipline-item">
                                <div class="discipline-header">
                                    <h4>Student Peter - Late Night Return</h4>
                                    <span class="discipline-date">Apr 21, 2026</span>
                                </div>
                                <div class="discipline-details">
                                    <div class="detail">
                                        <span>Incident:</span>
                                        <strong>Returned after 10:30 PM</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Action:</span>
                                        <strong>Warning issued, parents notified</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-success">Resolved</strong>
                                    </div>
                                </div>
                                <div class="discipline-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Follow Up</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Activities -->
                <section id="activities" class="content-section">
                    <h2>Student Activities</h2>
                    <div class="activity-actions">
                        <button class="btn btn-primary" onclick="openModal('organizeActivity')">
                            <i class="fas fa-calendar-plus"></i> Organize Activity
                        </button>
                        <button class="btn btn-success" onclick="openModal('activitySchedule')">
                            <i class="fas fa-calendar"></i> Activity Schedule
                        </button>
                        <button class="btn btn-info" onclick="openModal('participation')">
                            <i class="fas fa-users"></i> Student Participation
                        </button>
                        <button class="btn btn-warning" onclick="openModal('activityReport')">
                            <i class="fas fa-chart-bar"></i> Activity Report
                        </button>
                    </div>
                    
                    <div class="activities-overview">
                        <h3>Upcoming Activities</h3>
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-header">
                                    <h4>Girls' Empowerment Workshop</h4>
                                    <span class="activity-date">Apr 25, 2026</span>
                                </div>
                                <div class="activity-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Workshop</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Participants:</span>
                                        <strong>50 registered</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Location:</span>
                                        <strong>Main Hall</strong>
                                    </div>
                                </div>
                                <div class="activity-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Manage Registration</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Welfare Activities</h2>
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
                case 'welfareCase':
                    modalTitle.textContent = 'Create Welfare Case';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Student Name</label>
                                <select class="form-control" required>
                                    <option value="">Select Student</option>
                                    <option value="mary-student">Mary Student</option>
                                    <option value="john-student">John Student</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Case Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Case Type</option>
                                    <option value="emotional">Emotional Support</option>
                                    <option value="financial">Financial Support</option>
                                    <option value="academic">Academic Issues</option>
                                    <option value="health">Health Issues</option>
                                    <option value="disciplinary">Disciplinary Issues</option>
                                </select>
                            </div>
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
                            <div class="mb-3">
                                <label class="form-label">Case Description</label>
                                <textarea class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Immediate Actions Taken</label>
                                <textarea class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Follow-up Required</label>
                                <select class="form-control" required>
                                    <option value="">Select Follow-up</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </form>
                    `;
                    break;
                case 'scheduleSession':
                    modalTitle.textContent = 'Schedule Counseling Session';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Session Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="individual">Individual Counseling</option>
                                    <option value="group">Group Counseling</option>
                                    <option value="family">Family Counseling</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Student(s)</label>
                                <select class="form-control" required>
                                    <option value="">Select Student(s)</option>
                                    <option value="mary-student">Mary Student</option>
                                    <option value="john-student">John Student</option>
                                    <option value="first-year-group">First Year Students</option>
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
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <select class="form-control" required>
                                    <option value="">Select Location</option>
                                    <option value="counseling-a">Counseling Room A</option>
                                    <option value="counseling-b">Counseling Room B</option>
                                    <option value="main-hall">Main Hall</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Session Topic</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Session Notes</label>
                                <textarea class="form-control" rows="3"></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'healthCheck':
                    modalTitle.textContent = 'Student Health Check';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Student Name</label>
                                <select class="form-control" required>
                                    <option value="">Select Student</option>
                                    <option value="sarah-student">Sarah Student</option>
                                    <option value="peter-student">Peter Student</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Chief Complaint</label>
                                <textarea class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Symptoms</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="fever">
                                    <label class="form-check-label" for="fever">Fever</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="headache">
                                    <label class="form-check-label" for="headache">Headache</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="nausea">
                                    <label class="form-check-label" for="nausea">Nausea</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="fatigue">
                                    <label class="form-check-label" for="fatigue">Fatigue</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Temperature (°C)</label>
                                <input type="number" class="form-control" step="0.1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Blood Pressure</label>
                                <input type="text" class="form-control" placeholder="120/80">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pulse Rate (bpm)</label>
                                <input type="number" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assessment & Plan</label>
                                <textarea class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Action Required</label>
                                <select class="form-control" required>
                                    <option value="">Select Action</option>
                                    <option value="observation">Observation Only</option>
                                    <option value="medication">Administer Medication</option>
                                    <option value="referral">Refer to Hospital</option>
                                    <option value="parent-contact">Contact Parents</option>
                                </select>
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
