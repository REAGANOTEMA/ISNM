<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';
include_once '../security-middleware.php';

// Strict dashboard protection - only secretaries allowed
requireRole('School Secretary');

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

// Get secretary statistics (using fallback data only)
$total_students = 150; // Fallback value
$total_staff = 2; // Fallback value
$recent_applications = 8; // Fallback value
$active_programs = 2; // Fallback value
$total_documents = 245; // Fallback value
$pending_letters = 12; // Fallback value
$appointments_today = 5; // Fallback value
$meetings_scheduled = 3; // Fallback value

// Get recent activities (using a simple approach)
$recent_activities = [
    ['activity' => 'Dashboard accessed', 'created_at' => date('Y-m-d H:i:s')],
    ['activity' => 'Document processed', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Secretary Dashboard - ISNM</title>
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
                <h4>School Secretary Dashboard</h4>
                <p><?php echo ($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? $user['last_name'] ?? ''); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Office Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#correspondence">
                            <i class="fas fa-envelope"></i> Correspondence
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#appointments">
                            <i class="fas fa-calendar"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#meetings">
                            <i class="fas fa-users"></i> Meetings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#documents">
                            <i class="fas fa-file-alt"></i> Document Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#communications">
                            <i class="fas fa-bullhorn"></i> Communications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Office Reports
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
                    <h1>School Secretary Dashboard</h1>
                    <p>Administrative Support & Office Management</p>
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
                <!-- Office Overview -->
                <section id="overview" class="content-section">
                    <h2>Office Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_documents; ?></h3>
                                <p>Total Documents</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_letters; ?></h3>
                                <p>Pending Letters</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $appointments_today; ?></h3>
                                <p>Appointments Today</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $meetings_scheduled; ?></h3>
                                <p>Meetings Scheduled</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Correspondence -->
                <section id="correspondence" class="content-section">
                    <h2>Correspondence Management</h2>
                    <div class="correspondence-actions">
                        <button class="btn btn-primary" onclick="openModal('newLetter')">
                            <i class="fas fa-plus"></i> New Letter
                        </button>
                        <button class="btn btn-success" onclick="openModal('outgoingMail')">
                            <i class="fas fa-paper-plane"></i> Outgoing Mail
                        </button>
                        <button class="btn btn-info" onclick="openModal('incomingMail')">
                            <i class="fas fa-inbox"></i> Incoming Mail
                        </button>
                        <button class="btn btn-warning" onclick="openModal('mailLog')">
                            <i class="fas fa-list-alt"></i> Mail Log
                        </button>
                    </div>
                    
                    <div class="correspondence-overview">
                        <h3>Recent Correspondence</h3>
                        <div class="correspondence-list">
                            <div class="correspondence-item">
                                <div class="correspondence-header">
                                    <h4>Letter to Ministry of Education</h4>
                                    <span class="status-badge pending">Pending</span>
                                </div>
                                <div class="correspondence-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Official Letter</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Date:</span>
                                        <strong>Apr 22, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Priority:</span>
                                        <strong class="text-warning">High</strong>
                                    </div>
                                </div>
                                <div class="correspondence-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Process</button>
                                </div>
                            </div>
                            
                            <div class="correspondence-item">
                                <div class="correspondence-header">
                                    <h4>Application Response - John Doe</h4>
                                    <span class="status-badge completed">Completed</span>
                                </div>
                                <div class="correspondence-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Admission Letter</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Date:</span>
                                        <strong>Apr 21, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Priority:</span>
                                        <strong class="text-success">Normal</strong>
                                    </div>
                                </div>
                                <div class="correspondence-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-info">Reprint</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Appointments -->
                <section id="appointments" class="content-section">
                    <h2>Appointment Management</h2>
                    <div class="appointment-actions">
                        <button class="btn btn-primary" onclick="openModal('scheduleAppointment')">
                            <i class="fas fa-plus"></i> Schedule Appointment
                        </button>
                        <button class="btn btn-success" onclick="openModal('todayAppointments')">
                            <i class="fas fa-calendar-day"></i> Today's Appointments
                        </button>
                        <button class="btn btn-info" onclick="openModal('appointmentCalendar')">
                            <i class="fas fa-calendar-alt"></i> Appointment Calendar
                        </button>
                        <button class="btn btn-warning" onclick="openModal('appointmentReport')">
                            <i class="fas fa-chart-bar"></i> Appointment Report
                        </button>
                    </div>
                    
                    <div class="appointments-overview">
                        <h3>Today's Appointments</h3>
                        <div class="appointments-list">
                            <div class="appointment-item">
                                <div class="appointment-header">
                                    <h4>9:00 AM - Parent Meeting</h4>
                                    <span class="status-badge scheduled">Scheduled</span>
                                </div>
                                <div class="appointment-details">
                                    <div class="detail">
                                        <span>Visitor:</span>
                                        <strong>Mrs. Sarah Kamya (Parent)</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Purpose:</span>
                                        <strong>Discuss student progress</strong>
                                    </div>
                                    <div class="detail">
                                        <span>With:</span>
                                        <strong>School Principal</strong>
                                    </div>
                                </div>
                                <div class="appointment-actions">
                                    <button class="btn btn-sm btn-outline-success">Check In</button>
                                    <button class="btn btn-sm btn-outline-warning">Reschedule</button>
                                </div>
                            </div>
                            
                            <div class="appointment-item">
                                <div class="appointment-header">
                                    <h4>11:00 AM - Staff Interview</h4>
                                    <span class="status-badge scheduled">Scheduled</span>
                                </div>
                                <div class="appointment-details">
                                    <div class="detail">
                                        <span>Visitor:</span>
                                        <strong>John Smith (Applicant)</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Purpose:</span>
                                        <strong>Lecturer position interview</strong>
                                    </div>
                                    <div class="detail">
                                        <span>With:</span>
                                        <strong>HR Manager</strong>
                                    </div>
                                </div>
                                <div class="appointment-actions">
                                    <button class="btn btn-sm btn-outline-success">Check In</button>
                                    <button class="btn btn-sm btn-outline-warning">Reschedule</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Meetings -->
                <section id="meetings" class="content-section">
                    <h2>Meeting Management</h2>
                    <div class="meeting-actions">
                        <button class="btn btn-primary" onclick="openModal('scheduleMeeting')">
                            <i class="fas fa-plus"></i> Schedule Meeting
                        </button>
                        <button class="btn btn-success" onclick="openModal('meetingMinutes')">
                            <i class="fas fa-file-alt"></i> Meeting Minutes
                        </button>
                        <button class="btn btn-info" onclick="openModal('meetingCalendar')">
                            <i class="fas fa-calendar"></i> Meeting Calendar
                        </button>
                        <button class="btn btn-warning" onclick="openModal('meetingRoom')">
                            <i class="fas fa-door-open"></i> Room Booking
                        </button>
                    </div>
                    
                    <div class="meetings-overview">
                        <h3>Upcoming Meetings</h3>
                        <div class="meetings-list">
                            <div class="meeting-item">
                                <div class="meeting-header">
                                    <h4>Staff Meeting</h4>
                                    <span class="meeting-date">Apr 25, 2026 - 2:00 PM</span>
                                </div>
                                <div class="meeting-details">
                                    <div class="detail">
                                        <span>Location:</span>
                                        <strong>Main Hall</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Attendees:</span>
                                        <strong>All Staff (48)</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Agenda:</span>
                                        <strong>Monthly review and planning</strong>
                                    </div>
                                </div>
                                <div class="meeting-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Send Reminder</button>
                                </div>
                            </div>
                            
                            <div class="meeting-item">
                                <div class="meeting-header">
                                    <h4>Board of Governors Meeting</h4>
                                    <span class="meeting-date">Apr 28, 2026 - 10:00 AM</span>
                                </div>
                                <div class="meeting-details">
                                    <div class="detail">
                                        <span>Location:</span>
                                        <strong>Board Room</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Attendees:</span>
                                        <strong>Board Members (12)</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Agenda:</span>
                                        <strong>Strategic planning session</strong>
                                    </div>
                                </div>
                                <div class="meeting-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Send Reminder</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Document Management -->
                <section id="documents" class="content-section">
                    <h2>Document Management</h2>
                    <div class="document-actions">
                        <button class="btn btn-primary" onclick="openModal('uploadDocument')">
                            <i class="fas fa-upload"></i> Upload Document
                        </button>
                        <button class="btn btn-success" onclick="openModal('documentSearch')">
                            <i class="fas fa-search"></i> Search Documents
                        </button>
                        <button class="btn btn-info" onclick="openModal('documentArchive')">
                            <i class="fas fa-archive"></i> Document Archive
                        </button>
                        <button class="btn btn-warning" onclick="openModal('documentReport')">
                            <i class="fas fa-chart-bar"></i> Document Report
                        </button>
                    </div>
                    
                    <div class="documents-overview">
                        <h3>Recent Documents</h3>
                        <div class="documents-list">
                            <div class="document-item">
                                <div class="document-header">
                                    <h4>Academic Calendar 2026</h4>
                                    <span class="document-type">PDF</span>
                                </div>
                                <div class="document-details">
                                    <div class="detail">
                                        <span>Uploaded:</span>
                                        <strong>Apr 20, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Size:</span>
                                        <strong>2.4 MB</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Category:</span>
                                        <strong>Academic</strong>
                                    </div>
                                </div>
                                <div class="document-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                    <button class="btn btn-sm btn-outline-info">Share</button>
                                </div>
                            </div>
                            
                            <div class="document-item">
                                <div class="document-header">
                                    <h4>Staff Handbook 2026</h4>
                                    <span class="document-type">DOCX</span>
                                </div>
                                <div class="document-details">
                                    <div class="detail">
                                        <span>Uploaded:</span>
                                        <strong>Apr 18, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Size:</span>
                                        <strong>1.8 MB</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Category:</span>
                                        <strong>Administrative</strong>
                                    </div>
                                </div>
                                <div class="document-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                    <button class="btn btn-sm btn-outline-info">Share</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Communications -->
                <section id="communications" class="content-section">
                    <h2>School Communications</h2>
                    <div class="communication-actions">
                        <button class="btn btn-primary" onclick="openModal('announcement')">
                            <i class="fas fa-bullhorn"></i> Make Announcement
                        </button>
                        <button class="btn btn-success" onclick="openModal('newsletter')">
                            <i class="fas fa-newspaper"></i> School Newsletter
                        </button>
                        <button class="btn btn-info" onclick="openModal('noticeBoard')">
                            <i class="fas fa-clipboard"></i> Notice Board
                        </button>
                        <button class="btn btn-warning" onclick="openModal('emergencyAlert')">
                            <i class="fas fa-exclamation-triangle"></i> Emergency Alert
                        </button>
                    </div>
                    
                    <div class="communications-overview">
                        <h3>Recent Communications</h3>
                        <div class="communications-list">
                            <div class="communication-item">
                                <div class="communication-header">
                                    <h4>Mid-Semester Examinations Notice</h4>
                                    <span class="comm-type announcement">Announcement</span>
                                </div>
                                <div class="communication-details">
                                    <div class="detail">
                                        <span>Date:</span>
                                        <strong>Apr 22, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Audience:</span>
                                        <strong>All Students</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-success">Published</strong>
                                    </div>
                                </div>
                                <div class="communication-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-warning">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger">Retract</button>
                                </div>
                            </div>
                            
                            <div class="communication-item">
                                <div class="communication-header">
                                    <h4>Staff Meeting Reminder</h4>
                                    <span class="comm-type reminder">Reminder</span>
                                </div>
                                <div class="communication-details">
                                    <div class="detail">
                                        <span>Date:</span>
                                        <strong>Apr 21, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Audience:</span>
                                        <strong>All Staff</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-success">Sent</strong>
                                    </div>
                                </div>
                                <div class="communication-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-info">Resend</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Office Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon'] ?? 'check-circle'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['user_name'] ?? 'User'; ?></strong> <?php echo $activity['action'] ?? $activity['activity'] ?? 'Activity'; ?></p>
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
                case 'newLetter':
                    modalTitle.textContent = 'Compose New Letter';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Letter Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="official">Official Letter</option>
                                    <option value="admission">Admission Letter</option>
                                    <option value="recommendation">Recommendation Letter</option>
                                    <option value="complaint">Complaint Response</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Recipient</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Letter Content</label>
                                <textarea class="form-control" rows="8" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select class="form-control" required>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </form>
                    `;
                    break;
                case 'scheduleAppointment':
                    modalTitle.textContent = 'Schedule New Appointment';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Visitor Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purpose of Visit</label>
                                <textarea class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Appointment Date</label>
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
                                <label class="form-label">Meeting With</label>
                                <select class="form-control" required>
                                    <option value="">Select Staff</option>
                                    <option value="principal">School Principal</option>
                                    <option value="deputy">Deputy Principal</option>
                                    <option value="hr">HR Manager</option>
                                    <option value="registrar">Academic Registrar</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Special Instructions</label>
                                <textarea class="form-control" rows="2"></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'scheduleMeeting':
                    modalTitle.textContent = 'Schedule New Meeting';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Meeting Title</label>
                                <input type="text" class="form-control" required>
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
                                    <option value="main-hall">Main Hall</option>
                                    <option value="board-room">Board Room</option>
                                    <option value="conference-room">Conference Room</option>
                                    <option value="classroom-a">Classroom A</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Attendees</label>
                                <div class="attendees-selection">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="all-staff">
                                        <label class="form-check-label" for="all-staff">All Staff</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="board-members">
                                        <label class="form-check-label" for="board-members">Board Members</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="lecturers">
                                        <label class="form-check-label" for="lecturers">Lecturers Only</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Agenda</label>
                                <textarea class="form-control" rows="4" required></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'announcement':
                    modalTitle.textContent = 'Make School Announcement';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Announcement Title</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Target Audience</label>
                                <select class="form-control" required>
                                    <option value="">Select Audience</option>
                                    <option value="all">All (Students & Staff)</option>
                                    <option value="students">Students Only</option>
                                    <option value="staff">Staff Only</option>
                                    <option value="lecturers">Lecturers Only</option>
                                    <option value="parents">Parents Only</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority Level</label>
                                <select class="form-control" required>
                                    <option value="normal">Normal</option>
                                    <option value="important">Important</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Publishing Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="publish-website" checked>
                                    <label class="form-check-label" for="publish-website">Publish on website</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="publish-noticeboard" checked>
                                    <label class="form-check-label" for="publish-noticeboard">Display on notice board</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="send-email">
                                    <label class="form-check-label" for="send-email">Send email notification</label>
                                </div>
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
