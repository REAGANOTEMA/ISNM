<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['matron']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_email = $user['email'] ?? '';
$user_name = $user['full_name'] ?? '';

// Get matron statistics from database
$students_db = $ctx['students'];
$total_students = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM students")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_staff = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM staff")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$recent_applications = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM student_admissions")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$active_programs = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM academic_programs")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$assigned_students = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM hostel_allocations WHERE status = 'Active'")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$welfare_cases = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM student_welfare_cases WHERE status NOT IN ('Resolved','Closed')")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$counseling_sessions = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM student_counseling_sessions WHERE session_date = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$health_incidents = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM student_health_incidents WHERE resolved = 0")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;

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
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

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
                        <img src="<?= $profileImageUrl ?? '../images/username.png' ?>" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo htmlspecialchars($user_name); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content content-section">
                <div class="section-tabs">
                    <a class="section-tab active" data-tab="overview" onclick="switchToSection('overview')">Overview</a>
                    <a class="section-tab" data-tab="students" onclick="switchToSection('students')">Student Welfare</a>
                    <a class="section-tab" data-tab="counseling" onclick="switchToSection('counseling')">Counseling</a>
                    <a class="section-tab" data-tab="health" onclick="switchToSection('health')">Health</a>
                    <a class="section-tab" data-tab="accommodation" onclick="switchToSection('accommodation')">Accommodation</a>
                    <a class="section-tab" data-tab="discipline" onclick="switchToSection('discipline')">Discipline</a>
                    <a class="section-tab" data-tab="activities" onclick="switchToSection('activities')">Activities</a>
                </div>
                <!-- Welfare Overview -->
                <section id="overview" class="content-section dashboard-section active" data-section="overview">
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
                <section id="students" class="content-section dashboard-section" data-section="students">
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
                                    <h4>Student Mary , Homesickness</h4>
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
                                    <h4>Student John , Financial Need</h4>
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
                <section id="counseling" class="content-section dashboard-section" data-section="counseling">
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
                                    <h4>Individual Counseling , Mary Student</h4>
                                    <span class="session-time">10:00 AM to 11:00 AM</span>
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
                                    <h4>Group Counseling , First Year Students</h4>
                                    <span class="session-time">2:00 PM to 3:30 PM</span>
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
                <section id="health" class="content-section dashboard-section" data-section="health">
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
                                    <h4>Student Sarah , Fever</h4>
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
                <section id="accommodation" class="content-section dashboard-section" data-section="accommodation">
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
                <section id="discipline" class="content-section dashboard-section" data-section="discipline">
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
                                    <h4>Student Peter , Late Night Return</h4>
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
                <section id="activities" class="content-section dashboard-section" data-section="activities">
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
        document.querySelectorAll('.dashboard-sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.dashboard-sidebar .nav-link').forEach(l => l.classList.remove('active'));
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
                        <form action="../handlers/welfare_handler.php" method="POST">
                            <input type="hidden" name="action" value="create_welfare_case">
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="number" class="form-control" name="student_id" placeholder="Enter student ID number" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Case Type</label>
                                <select class="form-control" name="case_type" required>
                                    <option value="">Select Case Type</option>
                                    <option value="Academic Support">Academic Support</option>
                                    <option value="Personal Counseling">Personal Counseling</option>
                                    <option value="Financial Support">Financial Support</option>
                                    <option value="Health Issues">Health Issues</option>
                                    <option value="Disciplinary Issues">Disciplinary Issues</option>
                                    <option value="Homesickness">Homesickness</option>
                                    <option value="Family Problems">Family Problems</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority Level</label>
                                <select class="form-control" name="priority" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Case Description</label>
                                <textarea class="form-control" name="case_description" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Immediate Actions Taken</label>
                                <textarea class="form-control" name="immediate_actions" rows="3"></textarea>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="follow_up_required" id="fu" checked>
                                <label class="form-check-label" for="fu">Follow up Required</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Welfare Case</button>
                        </form>
                    `;
                    break;
                case 'scheduleSession':
                    modalTitle.textContent = 'Schedule Counseling Session';
                    modalBody.innerHTML = `
                        <form action="../handlers/welfare_handler.php" method="POST">
                            <input type="hidden" name="action" value="schedule_session">
                            <div class="mb-3">
                                <label class="form-label">Session Type</label>
                                <select class="form-control" name="session_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Individual">Individual Counseling</option>
                                    <option value="Group">Group Counseling</option>
                                    <option value="Family">Family Counseling</option>
                                    <option value="Crisis">Crisis Intervention</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="number" class="form-control" name="student_id" placeholder="Enter student ID number" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date</label>
                                        <input type="date" class="form-control" name="session_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Time</label>
                                        <input type="time" class="form-control" name="session_time" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Issues Discussed</label>
                                <textarea class="form-control" name="issues_discussed" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Schedule Session</button>
                        </form>
                    `;
                    break;
                case 'healthCheck':
                    modalTitle.textContent = 'Student Health Check';
                    modalBody.innerHTML = `
                        <form action="../handlers/welfare_handler.php" method="POST">
                            <input type="hidden" name="action" value="create_health_incident">
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="number" class="form-control" name="student_id" placeholder="Enter student ID number" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Chief Complaint</label>
                                <textarea class="form-control" name="description" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assessment & Plan</label>
                                <textarea class="form-control" name="actions_taken" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Action Required</label>
                                <select class="form-control" name="incident_type" required>
                                    <option value="">Select Action</option>
                                    <option value="Injury">Injury</option>
                                    <option value="Illness">Illness</option>
                                    <option value="Allergic Reaction">Allergic Reaction</option>
                                    <option value="Mental Health">Mental Health</option>
                                    <option value="Emergency">Emergency</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Submit Health Record</button>
                        </form>
                    `;
                    break;
            }
            
            modal.show();
        }

        // Save button submits the form in the modal body
        document.getElementById('modalAction')?.addEventListener('click', function() {
            const form = document.querySelector('#modalBody form');
            if (form) form.submit();
        });
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

