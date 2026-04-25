<?php
session_start();
// Check if user is logged in and has Deputy Principal role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Deputy Principal') {
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

// Get academic statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_lecturers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role LIKE '%Lecturer%'")->fetch_assoc()['count'];
$active_courses = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")->fetch_assoc()['count'];
$average_attendance = $conn->query("SELECT AVG(attendance_rate) as avg FROM attendance_records")->fetch_assoc()['avg'] ?? 0;

// Get recent academic activities
$recent_activities = $conn->query("SELECT * FROM academic_logs ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deputy Principal Dashboard - ISNM</title>
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
                <h4>Deputy Principal Dashboard</h4>
                <p><?php echo $user['first_name'] . ' ' . $user['surname']; ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Academic Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#teaching">
                            <i class="fas fa-chalkboard-teacher"></i> Teaching & Learning
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#students">
                            <i class="fas fa-user-graduate"></i> Student Affairs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#examinations">
                            <i class="fas fa-file-alt"></i> Examinations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#clinical">
                            <i class="fas fa-hospital"></i> Clinical Training
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#discipline">
                            <i class="fas fa-gavel"></i> Student Discipline
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Academic Reports
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
                    <h1>Deputy Principal Dashboard</h1>
                    <p>Assistant Academic Officer & Student Support</p>
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
                <!-- Academic Overview -->
                <section id="overview" class="content-section">
                    <h2>Academic Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_students; ?></h3>
                                <p>Total Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_lecturers; ?></h3>
                                <p>Lecturers</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $active_courses; ?></h3>
                                <p>Active Courses</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo round($average_attendance, 1); ?>%</h3>
                                <p>Avg Attendance</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Teaching & Learning -->
                <section id="teaching" class="content-section">
                    <h2>Teaching & Learning</h2>
                    <div class="teaching-actions">
                        <button class="btn btn-primary" onclick="openModal('scheduleClass')">
                            <i class="fas fa-calendar-plus"></i> Schedule Class
                        </button>
                        <button class="btn btn-success" onclick="openModal('assignLecturer')">
                            <i class="fas fa-user-plus"></i> Assign Lecturer
                        </button>
                        <button class="btn btn-info" onclick="openModal('curriculumReview')">
                            <i class="fas fa-book-open"></i> Curriculum Review
                        </button>
                        <button class="btn btn-warning" onclick="openModal('teachingMaterials')">
                            <i class="fas fa-file-alt"></i> Teaching Materials
                        </button>
                    </div>
                    
                    <div class="teaching-overview">
                        <h3>Current Teaching Schedule</h3>
                        <div class="schedule-grid">
                            <div class="schedule-card">
                                <div class="schedule-header">
                                    <h4>Nursing Fundamentals</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="schedule-details">
                                    <div class="detail">
                                        <span>Lecturer:</span>
                                        <strong>Dr. Sarah Johnson</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Time:</span>
                                        <strong>8:00 AM - 10:00 AM</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Room:</span>
                                        <strong>Classroom A</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>45/50</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="schedule-card">
                                <div class="schedule-header">
                                    <h4>Anatomy & Physiology</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="schedule-details">
                                    <div class="detail">
                                        <span>Lecturer:</span>
                                        <strong>Prof. Michael Brown</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Time:</span>
                                        <strong>10:30 AM - 12:30 PM</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Room:</span>
                                        <strong>Laboratory 1</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>38/40</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="schedule-card">
                                <div class="schedule-header">
                                    <h4>Midwifery Practice</h4>
                                    <span class="status-badge scheduled">Scheduled</span>
                                </div>
                                <div class="schedule-details">
                                    <div class="detail">
                                        <span>Lecturer:</span>
                                        <strong>Mrs. Grace Nakato</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Time:</span>
                                        <strong>2:00 PM - 4:00 PM</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Room:</span>
                                        <strong>Skills Lab</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>25/25</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Affairs -->
                <section id="students" class="content-section">
                    <h2>Student Affairs</h2>
                    <div class="student-actions">
                        <button class="btn btn-primary" onclick="openModal('studentRegistration')">
                            <i class="fas fa-user-plus"></i> Student Registration
                        </button>
                        <button class="btn btn-success" onclick="openModal('studentRecords')">
                            <i class="fas fa-folder"></i> Student Records
                        </button>
                        <button class="btn btn-info" onclick="openModal('attendanceTracking')">
                            <i class="fas fa-user-check"></i> Attendance Tracking
                        </button>
                        <button class="btn btn-warning" onclick="openModal('studentPerformance')">
                            <i class="fas fa-chart-line"></i> Performance Analysis
                        </button>
                    </div>
                    
                    <div class="student-overview">
                        <h3>Student Performance Overview</h3>
                        <div class="performance-grid">
                            <div class="performance-card">
                                <h4>Certificate Nursing</h4>
                                <div class="performance-metrics">
                                    <div class="metric">
                                        <span>Total Students:</span>
                                        <strong>120</strong>
                                    </div>
                                    <div class="metric">
                                        <span>Average GPA:</span>
                                        <strong>3.8</strong>
                                    </div>
                                    <div class="metric">
                                        <span>Pass Rate:</span>
                                        <strong>92%</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="performance-card">
                                <h4>Certificate Midwifery</h4>
                                <div class="performance-metrics">
                                    <div class="metric">
                                        <span>Total Students:</span>
                                        <strong>95</strong>
                                    </div>
                                    <div class="metric">
                                        <span>Average GPA:</span>
                                        <strong>3.9</strong>
                                    </div>
                                    <div class="metric">
                                        <span>Pass Rate:</span>
                                        <strong>100%</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="performance-card">
                                <h4>Diploma Nursing</h4>
                                <div class="performance-metrics">
                                    <div class="metric">
                                        <span>Total Students:</span>
                                        <strong>60</strong>
                                    </div>
                                    <div class="metric">
                                        <span>Average GPA:</span>
                                        <strong>3.7</strong>
                                    </div>
                                    <div class="metric">
                                        <span>Pass Rate:</span>
                                        <strong>88%</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="performance-card">
                                <h4>Diploma Midwifery</h4>
                                <div class="performance-metrics">
                                    <div class="metric">
                                        <span>Total Students:</span>
                                        <strong>40</strong>
                                    </div>
                                    <div class="metric">
                                        <span>Average GPA:</span>
                                        <strong>3.8</strong>
                                    </div>
                                    <div class="metric">
                                        <span>Pass Rate:</span>
                                        <strong>95%</strong>
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
                        <button class="btn btn-primary" onclick="openModal('scheduleExam')">
                            <i class="fas fa-calendar-plus"></i> Schedule Exam
                        </button>
                        <button class="btn btn-success" onclick="openModal('examResults')">
                            <i class="fas fa-chart-bar"></i> Exam Results
                        </button>
                        <button class="btn btn-info" onclick="openModal('examAnalysis')">
                            <i class="fas fa-analytics"></i> Performance Analysis
                        </button>
                        <button class="btn btn-warning" onclick="openModal('examReports')">
                            <i class="fas fa-file-alt"></i> Generate Reports
                        </button>
                    </div>
                    
                    <div class="exam-overview">
                        <h3>Upcoming Examinations</h3>
                        <div class="exam-schedule">
                            <div class="exam-item">
                                <div class="exam-header">
                                    <h4>Mid-Semester Examinations</h4>
                                    <span class="exam-date">May 15-20, 2026</span>
                                </div>
                                <div class="exam-details">
                                    <div class="detail">
                                        <span>Programs:</span>
                                        <strong>All Programs</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-warning">Preparation</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>315</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="exam-item">
                                <div class="exam-header">
                                    <h4>Final Practical Examinations</h4>
                                    <span class="exam-date">June 10-15, 2026</span>
                                </div>
                                <div class="exam-details">
                                    <div class="detail">
                                        <span>Programs:</span>
                                        <strong>Certificate Programs</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-info">Planning</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>215</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Clinical Training -->
                <section id="clinical" class="content-section">
                    <h2>Clinical Training</h2>
                    <div class="clinical-actions">
                        <button class="btn btn-primary" onclick="openModal('clinicalPlacement')">
                            <i class="fas fa-hospital"></i> Clinical Placement
                        </button>
                        <button class="btn btn-success" onclick="openModal('clinicalEvaluation')">
                            <i class="fas fa-clipboard-check"></i> Clinical Evaluation
                        </button>
                        <button class="btn btn-info" onclick="openModal('clinicalSites')">
                            <i class="fas fa-map-marked-alt"></i> Clinical Sites
                        </button>
                        <button class="btn btn-warning" onclick="openModal('clinicalReports')">
                            <i class="fas fa-file-medical"></i> Clinical Reports
                        </button>
                    </div>
                    
                    <div class="clinical-overview">
                        <h3>Clinical Placements Overview</h3>
                        <div class="placements-grid">
                            <div class="placement-card">
                                <div class="placement-header">
                                    <h4>Iganga Hospital</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="placement-details">
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>45</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Duration:</span>
                                        <strong>8 weeks</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Supervisor:</span>
                                        <strong>PNO Iganga</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="placement-card">
                                <div class="placement-header">
                                    <h4>Mbale Regional Hospital</h4>
                                    <span class="status-badge active">Active</span>
                                </div>
                                <div class="placement-details">
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>38</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Duration:</span>
                                        <strong>8 weeks</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Supervisor:</span>
                                        <strong>PNO Mbale</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="placement-card">
                                <div class="placement-header">
                                    <h4>Tororo Hospital</h4>
                                    <span class="status-badge scheduled">Upcoming</span>
                                </div>
                                <div class="placement-details">
                                    <div class="detail">
                                        <span>Students:</span>
                                        <strong>32</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Duration:</span>
                                        <strong>8 weeks</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Supervisor:</span>
                                        <strong>PNO Tororo</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Academic Activities</h2>
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
                case 'scheduleClass':
                    modalTitle.textContent = 'Schedule Class';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Course</label>
                                <select class="form-control" required>
                                    <option value="">Select Course</option>
                                    <option value="nursing-fundamentals">Nursing Fundamentals</option>
                                    <option value="anatomy">Anatomy & Physiology</option>
                                    <option value="midwifery">Midwifery Practice</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lecturer</label>
                                <select class="form-control" required>
                                    <option value="">Select Lecturer</option>
                                    <option value="dr-sarah">Dr. Sarah Johnson</option>
                                    <option value="prof-michael">Prof. Michael Brown</option>
                                    <option value="mrs-grace">Mrs. Grace Nakato</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time</label>
                                <input type="time" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Room</label>
                                <input type="text" class="form-control" required>
                            </div>
                        </form>
                    `;
                    break;
                case 'studentRegistration':
                    modalTitle.textContent = 'Student Registration';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Surname</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Program</label>
                                <select class="form-control" required>
                                    <option value="">Select Program</option>
                                    <option value="cert-nursing">Certificate Nursing</option>
                                    <option value="cert-midwifery">Certificate Midwifery</option>
                                    <option value="diploma-nursing">Diploma Nursing</option>
                                    <option value="diploma-midwifery">Diploma Midwifery</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" required>
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
