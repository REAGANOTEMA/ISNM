<?php
include_once '../includes/config.php';
// Check if user is logged in and has Head of Midwifery role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Head of Midwifery') {
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

// Get midwifery department statistics (using fallback data only)
$total_students = 150; // Fallback value
$total_staff = 2; // Fallback value
$recent_applications = 8; // Fallback value
$active_programs = 2; // Fallback value
$midwifery_courses = 16; // Fallback value

// Get recent activities (using a simple approach)
$recent_activities = [
    ['activity' => 'Dashboard accessed', 'created_at' => date('Y-m-d H:i:s')],
    ['activity' => 'Midwifery department meeting conducted', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Head of Midwifery Dashboard - ISNM</title>
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
                <h4>Head of Midwifery Dashboard</h4>
                <p><?php echo ($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? $user['last_name'] ?? ''); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Department Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#curriculum">
                            <i class="fas fa-book"></i> Curriculum Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#lecturers">
                            <i class="fas fa-chalkboard-teacher"></i> Lecturer Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#students">
                            <i class="fas fa-user-graduate"></i> Student Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#clinical">
                            <i class="fas fa-hospital"></i> Clinical Training
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#skills-lab">
                            <i class="fas fa-flask"></i> Skills Laboratory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#examinations">
                            <i class="fas fa-file-alt"></i> Examinations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Department Reports
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
                    <h1>Head of Midwifery Dashboard</h1>
                    <p>Midwifery Department Leadership</p>
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
                <!-- Department Overview -->
                <section id="overview" class="content-section">
                    <h2>Midwifery Department Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $midwifery_students; ?></h3>
                                <p>Midwifery Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $midwifery_lecturers; ?></h3>
                                <p>Midwifery Lecturers</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $midwifery_courses; ?></h3>
                                <p>Active Courses</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-hospital"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $clinical_sites; ?></h3>
                                <p>Clinical Sites</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Curriculum Management -->
                <section id="curriculum" class="content-section">
                    <h2>Curriculum Management</h2>
                    <div class="curriculum-actions">
                        <button class="btn btn-primary" onclick="openModal('courseDevelopment')">
                            <i class="fas fa-plus"></i> Course Development
                        </button>
                        <button class="btn btn-success" onclick="openModal('curriculumReview')">
                            <i class="fas fa-book-open"></i> Curriculum Review
                        </button>
                        <button class="btn btn-info" onclick="openModal('syllabusManagement')">
                            <i class="fas fa-list-alt"></i> Syllabus Management
                        </button>
                        <button class="btn btn-warning" onclick="openModal('competencyFramework')">
                            <i class="fas fa-tasks"></i> Competency Framework
                        </button>
                    </div>
                    
                    <div class="curriculum-overview">
                        <h3>Midwifery Program Curriculum</h3>
                        <div class="curriculum-grid">
                            <div class="curriculum-card">
                                <div class="curriculum-header">
                                    <h4>Certificate in Midwifery</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="curriculum-details">
                                    <div class="detail">
                                        <span>Duration:</span>
                                        <strong>2.5 years</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Courses:</span>
                                        <strong>26 modules</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Credits:</span>
                                        <strong>130 credits</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Last Review:</span>
                                        <strong>Feb 2026</strong>
                                    </div>
                                </div>
                                <div class="curriculum-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Edit Curriculum</button>
                                </div>
                            </div>
                            
                            <div class="curriculum-card">
                                <div class="curriculum-header">
                                    <h4>Diploma in Midwifery - Extension</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="curriculum-details">
                                    <div class="detail">
                                        <span>Duration:</span>
                                        <strong>1.5 years</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Courses:</span>
                                        <strong>18 modules</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Credits:</span>
                                        <strong>90 credits</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Last Review:</span>
                                        <strong>Mar 2026</strong>
                                    </div>
                                </div>
                                <div class="curriculum-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Edit Curriculum</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Lecturer Management -->
                <section id="lecturers" class="content-section">
                    <h2>Lecturer Management</h2>
                    <div class="lecturer-actions">
                        <button class="btn btn-primary" onclick="openModal('assignLecturer')">
                            <i class="fas fa-user-plus"></i> Assign Lecturer
                        </button>
                        <button class="btn btn-success" onclick="openModal('lecturerSchedule')">
                            <i class="fas fa-calendar"></i> Lecturer Schedule
                        </button>
                        <button class="btn btn-info" onclick="openModal('lecturerPerformance')">
                            <i class="fas fa-chart-line"></i> Performance Review
                        </button>
                        <button class="btn btn-warning" onclick="openModal('professionalDevelopment')">
                            <i class="fas fa-graduation-cap"></i> Professional Development
                        </button>
                    </div>
                    
                    <div class="lecturers-overview">
                        <h3>Midwifery Department Lecturers</h3>
                        <div class="lecturers-table">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Courses</th>
                                            <th>Load</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Mrs. Grace Nakato</td>
                                            <td>Senior Lecturer</td>
                                            <td>Midwifery Practice, Antenatal Care</td>
                                            <td>20 hrs/week</td>
                                            <td><span class="status-badge active">Active</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary">View</button>
                                                <button class="btn btn-sm btn-outline-success">Schedule</button>
                                                <button class="btn btn-sm btn-outline-info">Review</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Ms. Sarah Muwanga</td>
                                            <td>Lecturer</td>
                                            <td>Postnatal Care, Neonatal Care</td>
                                            <td>16 hrs/week</td>
                                            <td><span class="status-badge active">Active</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary">View</button>
                                                <button class="btn btn-sm btn-outline-success">Schedule</button>
                                                <button class="btn btn-sm btn-outline-info">Review</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Management -->
                <section id="students" class="content-section">
                    <h2>Student Management</h2>
                    <div class="student-actions">
                        <button class="btn btn-primary" onclick="openModal('studentProgress')">
                            <i class="fas fa-chart-line"></i> Student Progress
                        </button>
                        <button class="btn btn-success" onclick="openModal('studentAttendance')">
                            <i class="fas fa-user-check"></i> Attendance Tracking
                        </button>
                        <button class="btn btn-info" onclick="openModal('studentPerformance')">
                            <i class="fas fa-graduation-cap"></i> Performance Analysis
                        </button>
                        <button class="btn btn-warning" onclick="openModal('studentMentoring')">
                            <i class="fas fa-comments"></i> Student Mentoring
                        </button>
                    </div>
                    
                    <div class="student-overview">
                        <h3>Student Performance Overview</h3>
                        <div class="performance-stats">
                            <div class="perf-stat">
                                <h4>Certificate Midwifery</h4>
                                <div class="avg-gpa">3.9</div>
                                <small>Average GPA</small>
                            </div>
                            <div class="perf-stat">
                                <h4>Diploma Midwifery</h4>
                                <div class="avg-gpa">3.8</div>
                                <small>Average GPA</small>
                            </div>
                            <div class="perf-stat">
                                <h4>Attendance Rate</h4>
                                <div class="attendance-rate">95%</div>
                                <small>Department average</small>
                            </div>
                            <div class="perf-stat">
                                <h4>Pass Rate</h4>
                                <div class="pass-rate">100%</div>
                                <small>Last semester</small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Clinical Training -->
                <section id="clinical" class="content-section">
                    <h2>Clinical Training Management</h2>
                    <div class="clinical-actions">
                        <button class="btn btn-primary" onclick="openModal('clinicalPlacement')">
                            <i class="fas fa-hospital"></i> Clinical Placement
                        </button>
                        <button class="btn btn-success" onclick="openModal('clinicalSupervision')">
                            <i class="fas fa-user-md"></i> Clinical Supervision
                        </button>
                        <button class="btn btn-info" onclick="openModal('clinicalEvaluation')">
                            <i class="fas fa-clipboard-check"></i> Clinical Evaluation
                        </button>
                        <button class="btn btn-warning" onclick="openModal('deliveryExperience')">
                            <i class="fas fa-baby"></i> Delivery Experience
                        </button>
                    </div>
                    
                    <div class="clinical-overview">
                        <h3>Clinical Training Sites</h3>
                        <div class="clinical-sites">
                            <div class="site-card">
                                <div class="site-header">
                                    <h4>Iganga Regional Referral Hospital</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="site-details">
                                    <div class="detail">
                                        <span>Students Placed:</span>
                                        <strong>35</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Departments:</span>
                                        <strong>Antenatal, Postnatal, Labor Ward</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Supervisor:</span>
                                        <strong>PNO Iganga</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Duration:</span>
                                        <strong>8 weeks rotation</strong>
                                    </div>
                                </div>
                                <div class="site-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Student List</button>
                                </div>
                            </div>
                            
                            <div class="site-card">
                                <div class="site-header">
                                    <h4>Bugiri Hospital</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="site-details">
                                    <div class="detail">
                                        <span>Students Placed:</span>
                                        <strong>28</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Departments:</span>
                                        <strong>Family Planning, Neonatal</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Supervisor:</span>
                                        <strong>PNO Bugiri</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Duration:</span>
                                        <strong>8 weeks rotation</strong>
                                    </div>
                                </div>
                                <div class="site-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Student List</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Skills Laboratory -->
                <section id="skills-lab" class="content-section">
                    <h2>Skills Laboratory Management</h2>
                    <div class="lab-actions">
                        <button class="btn btn-primary" onclick="openModal('labSchedule')">
                            <i class="fas fa-calendar"></i> Lab Schedule
                        </button>
                        <button class="btn btn-success" onclick="openModal('equipmentManagement')">
                            <i class="fas fa-tools"></i> Equipment Management
                        </button>
                        <button class="btn btn-info" onclick="openModal('skillsAssessment')">
                            <i class="fas fa-clipboard-check"></i> Skills Assessment
                        </button>
                        <button class="btn btn-warning" onclick="openModal('labInventory')">
                            <i class="fas fa-boxes"></i> Lab Inventory
                        </button>
                    </div>
                    
                    <div class="lab-overview">
                        <h3>Midwifery Skills Laboratory</h3>
                        <div class="lab-stats">
                            <div class="lab-stat">
                                <h4>Delivery Models</h4>
                                <div class="count">8</div>
                                <small>Available</small>
                            </div>
                            <div class="lab-stat">
                                <h4>Neonatal Simulators</h4>
                                <div class="count">6</div>
                                <small>Available</small>
                            </div>
                            <div class="lab-stat">
                                <h4>Practice Sessions/Week</h4>
                                <div class="count">24</div>
                                <small>Scheduled</small>
                            </div>
                            <div class="lab-stat">
                                <h4>Students Trained</h4>
                                <div class="count">135</div>
                                <small>This semester</small>
                            </div>
                        </div>
                        
                        <div class="lab-schedule">
                            <h4>Today's Lab Sessions</h4>
                            <div class="session-list">
                                <div class="session-item">
                                    <div class="session-header">
                                        <h5>Normal Delivery Procedure</h5>
                                        <span class="session-time">8:00 AM - 10:00 AM</span>
                                    </div>
                                    <div class="session-details">
                                        <span>Instructor:</span>
                                        <strong>Mrs. Grace Nakato</strong>
                                        <span>Students:</span>
                                        <strong>12</strong>
                                    </div>
                                </div>
                                
                                <div class="session-item">
                                    <div class="session-header">
                                        <h5>Neonatal Resuscitation</h5>
                                        <span class="session-time">10:30 AM - 12:30 PM</span>
                                    </div>
                                    <div class="session-details">
                                        <span>Instructor:</span>
                                        <strong>Ms. Sarah Muwanga</strong>
                                        <span>Students:</span>
                                        <strong>10</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Examinations -->
                <section id="examinations" class="content-section">
                    <h2>Examination Management</h2>
                    <div class="exam-actions">
                        <button class="btn btn-primary" onclick="openModal('examSchedule')">
                            <i class="fas fa-calendar"></i> Exam Schedule
                        </button>
                        <button class="btn btn-success" onclick="openModal('examResults')">
                            <i class="fas fa-chart-bar"></i> Results Analysis
                        </button>
                        <button class="btn btn-info" onclick="openModal('osceExams')">
                            <i class="fas fa-user-md"></i> OSCE Exams
                        </button>
                        <button class="btn btn-warning" onclick="openModal('examReports')">
                            <i class="fas fa-file-alt"></i> Exam Reports
                        </button>
                    </div>
                    
                    <div class="exam-overview">
                        <h3>Upcoming Midwifery Examinations</h3>
                        <div class="exam-schedule">
                            <div class="exam-item">
                                <div class="exam-header">
                                    <h4>Midwifery Fundamentals Final Exam</h4>
                                    <span class="exam-date">May 16, 2026</span>
                                </div>
                                <div class="exam-details">
                                    <div class="detail">
                                        <span>Cohort:</span>
                                        <strong>2024 Intake</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>35</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Examiner:</span>
                                        <strong>Mrs. Grace Nakato</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-warning">Preparation</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="exam-item">
                                <div class="exam-header">
                                    <h4>OSCE Assessment - Practical Skills</h4>
                                    <span class="exam-date">May 22, 2026</span>
                                </div>
                                <div class="exam-details">
                                    <div class="detail">
                                        <span>Cohort:</span>
                                        <strong>2024 Intake</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>35</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Examiners:</span>
                                        <strong>3 examiners</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-info">Planning</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Department Activities</h2>
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
                case 'courseDevelopment':
                    modalTitle.textContent = 'Midwifery Course Development';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Course Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Course Code</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Credits</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Duration (weeks)</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Course Description</label>
                                <textarea class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Learning Outcomes</label>
                                <textarea class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Clinical Competencies</label>
                                <textarea class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assessment Methods</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="written-exam">
                                    <label class="form-check-label" for="written-exam">Written Examination</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="osce">
                                    <label class="form-check-label" for="osce">OSCE Assessment</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="clinical-eval">
                                    <label class="form-check-label" for="clinical-eval">Clinical Evaluation</label>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'labSchedule':
                    modalTitle.textContent = 'Skills Laboratory Schedule';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Session Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Session Type</option>
                                    <option value="normal-delivery">Normal Delivery Procedure</option>
                                    <option value="cesarean">Cesarean Section Assistance</option>
                                    <option value="neonatal">Neonatal Care</option>
                                    <option value="postnatal">Postnatal Care</option>
                                    <option value="family-planning">Family Planning</option>
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
                                        <label class="form-label">Instructor</label>
                                        <select class="form-control" required>
                                            <option value="">Select Instructor</option>
                                            <option value="grace-nakato">Mrs. Grace Nakato</option>
                                            <option value="sarah-muwanga">Ms. Sarah Muwanga</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Max Students</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Equipment Required</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="delivery-model">
                                    <label class="form-check-label" for="delivery-model">Delivery Model</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="neonatal-simulator">
                                    <label class="form-check-label" for="neonatal-simulator">Neonatal Simulator</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="resuscitation-kit">
                                    <label class="form-check-label" for="resuscitation-kit">Resuscitation Kit</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Learning Objectives</label>
                                <textarea class="form-control" rows="3" required></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'osceExams':
                    modalTitle.textContent = 'OSCE Examination Setup';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Exam Title</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Exam Date</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Duration (minutes)</label>
                                        <input type="number" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">OSCE Stations</label>
                                <div id="stations-container">
                                    <div class="station-item border p-3 mb-3">
                                        <h5>Station 1</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-2">
                                                    <label class="form-label">Skill</label>
                                                    <select class="form-control">
                                                        <option value="">Select Skill</option>
                                                        <option value="normal-delivery">Normal Delivery</option>
                                                        <option value="neonatal-resuscitation">Neonatal Resuscitation</option>
                                                        <option value="postnatal-examination">Postnatal Examination</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-2">
                                                    <label class="form-label">Time (minutes)</label>
                                                    <input type="number" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Checklist Items</label>
                                            <textarea class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addStation()">Add Station</button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Examiners</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="examiner1">
                                    <label class="form-check-label" for="examiner1">Mrs. Grace Nakato</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="examiner2">
                                    <label class="form-check-label" for="examiner2">Ms. Sarah Muwanga</label>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }

        function addStation() {
            const stationsContainer = document.getElementById('stations-container');
            const stationCount = stationsContainer.children.length + 1;
            const newStation = document.createElement('div');
            newStation.className = 'station-item border p-3 mb-3';
            newStation.innerHTML = `
                <h5>Station ${stationCount}</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label">Skill</label>
                            <select class="form-control">
                                <option value="">Select Skill</option>
                                <option value="normal-delivery">Normal Delivery</option>
                                <option value="neonatal-resuscitation">Neonatal Resuscitation</option>
                                <option value="postnatal-examination">Postnatal Examination</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label">Time (minutes)</label>
                            <input type="number" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Checklist Items</label>
                    <textarea class="form-control" rows="2"></textarea>
                </div>
            `;
            stationsContainer.appendChild(newStation);
        }
    </script>
</body>
</html>
