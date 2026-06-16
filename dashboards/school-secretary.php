<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['school secretary', 'secretary']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';

// Database connection
$conn = getStaffConnection();

// Get additional user details from session (already loaded by bootstrapStaffDashboard)
$user_id    = $_SESSION['user_id'] ?? 0;
$user_role  = $_SESSION['role']    ?? '';
$user_email = $_SESSION['email']   ?? '';
$user_name  = $_SESSION['full_name'] ?? '';

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
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>School Secretary Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
    <style>
        body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;margin:0}
        .page-content{margin-left:280px;flex:1;min-height:100vh}
        @media(max-width:768px){.page-content{margin-left:0}}
        .top-bar{background:#fff;padding:14px 22px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.07);position:sticky;top:0;z-index:100}
        .content-area{padding:22px}
        .stat-card{background:linear-gradient(to bottom,#ffe082 0%,#ffe082 5px,#fef9e7 5px,#fef9e7 100%);border-radius:14px;padding:20px;display:flex;align-items:center;gap:14px;transition:transform .25s}
        .stat-card:hover{transform:translateY(-4px)}
        .si{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0}
        .si-blue{background:linear-gradient(135deg,#1a237e,#3949ab)}
        .si-green{background:linear-gradient(135deg,#2e7d32,#43a047)}
        .si-cyan{background:linear-gradient(135deg,#0277bd,#039be5)}
        .si-orange{background:linear-gradient(135deg,#e65100,#fb8c00)}
        .si-purple{background:linear-gradient(135deg,#4a148c,#8e24aa)}
        .si-red{background:linear-gradient(135deg,#b71c1c,#ef5350)}
        .stat-content h3{font-size:1.6rem;font-weight:700;margin:0;line-height:1}
        .stat-content p{font-size:.77rem;color:#666;margin:2px 0 0}
        .section-card{background:#fff;border-radius:14px;padding:20px;margin-bottom:22px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
        .section-card h2{font-size:1rem;font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #f0f2f5}
        .stat-icon{background:linear-gradient(135deg,#1a237e,#3949ab)}
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; ?>
    
    <div class="page-content">
      <div class="top-bar">
        <div>
          <strong><i class="fas fa-user-tie me-2 text-primary"></i>School Secretary Dashboard</strong>
          <div class="text-muted small">Administrative Support &amp; Office Management | <?php echo htmlspecialchars($user_name); ?></div>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span class="text-muted small d-none d-md-block" id="currentDate"></span>
          <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
        </div>
      </div>

      <div class="content-area">
                <!-- Office Overview -->
                <section id="overview" class="section-card">
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
                <section id="correspondence" class="section-card">
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
                <section id="appointments" class="section-card">
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
                <section id="meetings" class="section-card">
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
                <section id="documents" class="section-card">
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
                <section id="communications" class="section-card">
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
                document.querySelectorAll('.section-card').forEach(section => {
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

        // Attach modalAction handler to publish announcements from this dashboard
        document.addEventListener('DOMContentLoaded', function() {
            const modalActionBtn = document.getElementById('modalAction');
            if (!modalActionBtn) return;
            modalActionBtn.addEventListener('click', function() {
                const modalTitle = document.getElementById('modalTitle').textContent || '';
                if (modalTitle.includes('Make School Announcement')) {
                    const title = document.getElementById('annTitle').value.trim();
                    const content = document.getElementById('annContent').value.trim();
                    const target = document.getElementById('annTarget').value;
                    const priority = document.getElementById('annPriority').value;
                    const expiry = document.getElementById('annExpiry').value || '';

                    if (!title || !content) { alert('Title and message are required.'); return; }

                    const fd = new FormData();
                    fd.append('title', title);
                    fd.append('content', content);
                    fd.append('announcement_type', 'school');
                    fd.append('target_audience', target);
                    fd.append('priority', priority);
                    fd.append('expiry_date', expiry);
                    fd.append('status', 'published');

                    const modalBody = document.getElementById('modalBody');
                    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><p class="mt-3">Publishing announcement...</p></div>';

                    fetch('../includes/ajax_publish_announcement.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp.success) { modalBody.innerHTML = '<div class="alert alert-success">Announcement published successfully.</div>'; setTimeout(()=>location.reload(),900); }
                            else { modalBody.innerHTML = '<div class="alert alert-danger">Failed: ' + (resp.message || 'Unknown') + '</div>'; }
                        })
                        .catch(() => { modalBody.innerHTML = '<div class="alert alert-danger">Network error while publishing announcement.</div>'; });
                }
            });
        });
    </script>
    </div><!-- /content-area -->
</div><!-- /page-content -->
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

