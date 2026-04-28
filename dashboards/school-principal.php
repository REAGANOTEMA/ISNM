<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';
include_once '../security-middleware.php';

// Strict dashboard protection - only principals allowed
requireRole('School Principal');

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

// Get school statistics (using fallback data only)
$total_students = 150; // Fallback value
$total_staff = 25; // Fallback value
$total_applications = 12; // Fallback value
$active_programs = 2; // Fallback value

// Get academic performance (using fallback data)
$avg_gpa = 3.4; // Fallback value
$pass_rate = 135; // Fallback value
$total_examined = 150; // Fallback value
$pass_percentage = ($pass_rate / $total_examined) * 100; // 90%

// Get recent activities (using fallback data)
$recent_activities = [
    ['activity' => 'Staff meeting conducted', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['activity' => 'Student assembly held', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
    ['activity' => 'Academic review completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
    ['activity' => 'Facility inspection done', 'created_at' => date('Y-m-d H:i:s', strtotime('-7 hours'))]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Principal Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    
    <!-- Responsive Dashboard CSS -->
    <style>
        /* Responsive Dashboard Container */
        .dashboard-container {
            min-height: 100vh;
            background: #f8f9fa;
            transition: margin-left 0.3s ease;
        }
        
        .dashboard-main {
            padding: 20px;
            max-width: 100%;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                margin-left: 0 !important;
            }
            
            .dashboard-main {
                padding: 15px;
                padding-top: 80px; /* Space for mobile menu */
            }
        }
        
        @media (min-width: 769px) {
            .dashboard-container.sidebar-collapsed {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Include Responsive Navigation -->
    <?php include_once '../includes/sidebar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Main Content Area -->
        
        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>School Principal Dashboard</h1>
                    <p>Academic Leadership & School Management - Iganga School of Nursing and Midwifery</p>
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
                <!-- School Overview -->
                <section id="overview" class="content-section">
                    <h2>School Overview</h2>
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
                                <small>Teaching & non-teaching</small>
                            </div>
                        </div>
                        
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($active_programs); ?></h3>
                                <p>Active Programs</p>
                                <small>All academic programs</small>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_applications); ?></h3>
                                <p>Pending Applications</p>
                                <small>Require review</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academic Performance -->
                    <div class="academic-performance">
                        <h3>Academic Performance Overview</h3>
                        <div class="performance-stats">
                            <div class="performance-stat">
                                <h4><?php echo number_format($avg_gpa, 2); ?></h4>
                                <p>Average GPA</p>
                                <span class="trend positive">
                                    <i class="fas fa-arrow-up"></i> 0.3 from last semester
                                </span>
                            </div>
                            <div class="performance-stat">
                                <h4><?php echo number_format($pass_percentage, 1); ?>%</h4>
                                <p>Pass Rate</p>
                                <span class="trend positive">
                                    <i class="fas fa-arrow-up"></i> 2% from last semester
                                </span>
                            </div>
                            <div class="performance-stat">
                                <h4>95%</h4>
                                <p>Attendance Rate</p>
                                <span class="trend stable">
                                    <i class="fas fa-minus"></i> No change
                                </span>
                            </div>
                            <div class="performance-stat">
                                <h4>100%</h4>
                                <p>Midwifery Pass Rate</p>
                                <span class="trend positive">
                                    <i class="fas fa-arrow-up"></i> Maintained excellence
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Academic Management -->
                <section id="academic" class="content-section">
                    <h2>Academic Management</h2>
                    <div class="academic-actions">
                        <button class="btn btn-primary" onclick="openModal('approveResults')">
                            <i class="fas fa-check-circle"></i> Approve Results
                        </button>
                        <button class="btn btn-success" onclick="openModal('scheduleExams')">
                            <i class="fas fa-calendar-alt"></i> Schedule Examinations
                        </button>
                        <button class="btn btn-info" onclick="openModal('curriculumReview')">
                            <i class="fas fa-book-open"></i> Curriculum Review
                        </button>
                        <button class="btn btn-warning" onclick="openModal('academicReport')">
                            <i class="fas fa-chart-line"></i> Academic Report
                        </button>
                    </div>
                    
                    <!-- Department Performance -->
                    <div class="department-performance">
                        <h3>Department Performance</h3>
                        <div class="department-grid">
                            <div class="dept-card">
                                <div class="dept-header">
                                    <h4>Nursing Department</h4>
                                    <span class="dept-status active">Active</span>
                                </div>
                                <div class="dept-metrics">
                                    <div class="metric">
                                        <span>Student Performance</span>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: 87%"></div>
                                        </div>
                                        <span class="value">87%</span>
                                    </div>
                                    <div class="metric">
                                        <span>Faculty Satisfaction</span>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: 92%"></div>
                                        </div>
                                        <span class="value">92%</span>
                                    </div>
                                    <div class="metric">
                                        <span>Research Output</span>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: 78%"></div>
                                        </div>
                                        <span class="value">78%</span>
                                    </div>
                                </div>
                                <div class="dept-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Faculty Meeting</button>
                                </div>
                            </div>
                            
                            <div class="dept-card">
                                <div class="dept-header">
                                    <h4>Midwifery Department</h4>
                                    <span class="dept-status active">Active</span>
                                </div>
                                <div class="dept-metrics">
                                    <div class="metric">
                                        <span>Student Performance</span>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: 95%"></div>
                                        </div>
                                        <span class="value">95%</span>
                                    </div>
                                    <div class="metric">
                                        <span>Faculty Satisfaction</span>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: 88%"></div>
                                        </div>
                                        <span class="value">88%</span>
                                    </div>
                                    <div class="metric">
                                        <span>Clinical Practice</span>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: 92%"></div>
                                        </div>
                                        <span class="value">92%</span>
                                    </div>
                                </div>
                                <div class="dept-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Faculty Meeting</button>
                                </div>
                            </div>
                            
                            <div class="dept-card">
                                <div class="dept-header">
                                    <h4>Skills Laboratory</h4>
                                    <span class="dept-status active">Active</span>
                                </div>
                                <div class="dept-metrics">
                                    <div class="metric">
                                        <span>Equipment Utilization</span>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: 85%"></div>
                                        </div>
                                        <span class="value">85%</span>
                                    </div>
                                    <div class="metric">
                                        <span>Student Satisfaction</span>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: 90%"></div>
                                        </div>
                                        <span class="value">90%</span>
                                    </div>
                                    <div class="metric">
                                        <span>Lab Safety Compliance</span>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: 98%"></div>
                                        </div>
                                        <span class="value">98%</span>
                                    </div>
                                </div>
                                <div class="dept-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-warning">Equipment Audit</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Student Affairs -->
                <section id="students" class="content-section">
                    <h2>Student Affairs</h2>
                    <div class="student-affairs-grid">
                        <div class="affairs-card">
                            <div class="affairs-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3>Admissions</h3>
                            <p>Manage student admissions and enrollment processes</p>
                            <div class="affairs-stats">
                                <span><?php echo $total_applications; ?> Pending Applications</span>
                            </div>
                            <div class="affairs-actions">
                                <button class="btn btn-primary" onclick="openModal('reviewApplications')">Review Applications</button>
                                <button class="btn btn-outline-info" onclick="openModal('admissionsReport')">Admissions Report</button>
                            </div>
                        </div>
                        
                        <div class="affairs-card">
                            <div class="affairs-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h3>Academic Records</h3>
                            <p>Monitor student academic progress and performance</p>
                            <div class="affairs-stats">
                                <span>Current Semester Active</span>
                            </div>
                            <div class="affairs-actions">
                                <button class="btn btn-primary" onclick="openModal('viewRecords')">View Records</button>
                                <button class="btn btn-outline-info" onclick="openModal('performanceReport')">Performance Report</button>
                            </div>
                        </div>
                        
                        <div class="affairs-card">
                            <div class="affairs-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h3>Student Welfare</h3>
                            <p>Oversee student welfare and support services</p>
                            <div class="affairs-stats">
                                <span>Counseling & Support Active</span>
                            </div>
                            <div class="affairs-actions">
                                <button class="btn btn-primary" onclick="openModal('welfareServices')">Welfare Services</button>
                                <button class="btn btn-outline-info" onclick="openModal('counselingReport')">Counseling Report</button>
                            </div>
                        </div>
                        
                        <div class="affairs-card">
                            <div class="affairs-icon">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <h3>Discipline</h3>
                            <p>Manage student discipline and conduct</p>
                            <div class="affairs-stats">
                                <span>Discipline Committee Active</span>
                            </div>
                            <div class="affairs-actions">
                                <button class="btn btn-primary" onclick="openModal('disciplineCases')">Discipline Cases</button>
                                <button class="btn btn-outline-info" onclick="openModal('disciplineReport')">Discipline Report</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Student Issues -->
                    <div class="recent-issues">
                        <h3>Recent Student Issues</h3>
                        <div class="issues-list">
                            <div class="issue-item">
                                <div class="issue-header">
                                    <span class="issue-type academic">Academic</span>
                                    <span class="issue-date">2 days ago</span>
                                </div>
                                <h4>Performance Concern - Nursing Year 2</h4>
                                <p>Several students showing below-average performance in clinical practice</p>
                                <div class="issue-actions">
                                    <button class="btn btn-sm btn-outline-primary">Review</button>
                                    <button class="btn btn-sm btn-outline-warning">Action Required</button>
                                </div>
                            </div>
                            
                            <div class="issue-item">
                                <div class="issue-header">
                                    <span class="issue-type welfare">Welfare</span>
                                    <span class="issue-date">5 days ago</span>
                                </div>
                                <h4>Hostel Accommodation Request</h4>
                                <p>Students requesting additional hostel facilities for next semester</p>
                                <div class="issue-actions">
                                    <button class="btn btn-sm btn-outline-primary">Review</button>
                                    <button class="btn btn-sm btn-outline-info">Under Review</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Staff Management -->
                <section id="staff" class="content-section">
                    <h2>Staff Management</h2>
                    <div class="staff-overview">
                        <div class="staff-stats">
                            <div class="staff-stat">
                                <h4><?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('Senior Lecturers', 'Lecturers') AND status = 'active'")->fetch_assoc()['count']; ?></h4>
                                <p>Teaching Staff</p>
                            </div>
                            <div class="staff-stat">
                                <h4><?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('Matrons', 'Lab Technicians', 'Drivers', 'Security', 'School Secretary', 'School Librarian') AND status = 'active'")->fetch_assoc()['count']; ?></h4>
                                <p>Support Staff</p>
                            </div>
                            <div class="staff-stat">
                                <h4><?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('Director Academics', 'Director ICT', 'Director Finance', 'Academic Registrar', 'HR Manager') AND status = 'active'")->fetch_assoc()['count']; ?></h4>
                                <p>Administrative Staff</p>
                            </div>
                            <div class="staff-stat">
                                <h4>95%</h4>
                                <p>Staff Attendance</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Staff Actions -->
                    <div class="staff-actions">
                        <button class="btn btn-primary" onclick="openModal('staffMeeting')">
                            <i class="fas fa-users"></i> Schedule Staff Meeting
                        </button>
                        <button class="btn btn-success" onclick="openModal('performanceReview')">
                            <i class="fas fa-chart-line"></i> Performance Reviews
                        </button>
                        <button class="btn btn-info" onclick="openModal('staffTraining')">
                            <i class="fas fa-graduation-cap"></i> Training & Development
                        </button>
                        <button class="btn btn-warning" onclick="openModal('staffReport')">
                            <i class="fas fa-file-alt"></i> Staff Report
                        </button>
                    </div>
                </section>
                
                <!-- Program Oversight -->
                <section id="programs" class="content-section">
                    <h2>Program Oversight</h2>
                    <div class="programs-overview">
                        <div class="program-card">
                            <div class="program-header">
                                <h3>Certificate in Nursing</h3>
                                <span class="program-status active">Active</span>
                            </div>
                            <div class="program-details">
                                <div class="program-stats">
                                    <div class="program-stat">
                                        <span>Enrolled Students:</span>
                                        <strong><?php echo $conn->query("SELECT COUNT(*) as count FROM students WHERE program = 'Certificate in Nursing' AND status = 'active'")->fetch_assoc()['count']; ?></strong>
                                    </div>
                                    <div class="program-stat">
                                        <span>Completion Rate:</span>
                                        <strong>92%</strong>
                                    </div>
                                    <div class="program-stat">
                                        <span>Employment Rate:</span>
                                        <strong>87%</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="program-actions">
                                <button class="btn btn-sm btn-outline-primary">Curriculum Review</button>
                                <button class="btn btn-sm btn-outline-info">Student Performance</button>
                            </div>
                        </div>
                        
                        <div class="program-card">
                            <div class="program-header">
                                <h3>Certificate in Midwifery</h3>
                                <span class="program-status active">Active</span>
                            </div>
                            <div class="program-details">
                                <div class="program-stats">
                                    <div class="program-stat">
                                        <span>Enrolled Students:</span>
                                        <strong><?php echo $conn->query("SELECT COUNT(*) as count FROM students WHERE program = 'Certificate in Midwifery' AND status = 'active'")->fetch_assoc()['count']; ?></strong>
                                    </div>
                                    <div class="program-stat">
                                        <span>Completion Rate:</span>
                                        <strong>95%</strong>
                                    </div>
                                    <div class="program-stat">
                                        <span>Employment Rate:</span>
                                        <strong>90%</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="program-actions">
                                <button class="btn btn-sm btn-outline-primary">Curriculum Review</button>
                                <button class="btn btn-sm btn-outline-info">Student Performance</button>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Recent Activities -->
                <section class="content-section">
                    <h2>Recent School Activities</h2>
                    <div class="activities-table">
                        <h3>System Activity Log</h3>
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
                                                <span><?php echo $activity['user_role'] ?? 'School Principal'; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $activity['activity_description'] ?? $activity['activity'] ?? 'Activity'; ?></td>
                                        <td><?php echo $activity['module_affected'] ?? 'System'; ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($activity['created_at'] ?? $activity['activity_date'] ?? 'now')); ?></td>
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
                case 'approveResults':
                    modalTitle.textContent = 'Approve Academic Results';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Select Semester</label>
                                <select class="form-control" required>
                                    <option value="">Select Semester</option>
                                    <option value="2025/2026-1">Semester 1 (2025/2026)</option>
                                    <option value="2024/2025-2">Semester 2 (2024/2025)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Program</label>
                                <select class="form-control" required>
                                    <option value="">Select Program</option>
                                    <option value="nursing">Certificate in Nursing</option>
                                    <option value="midwifery">Certificate in Midwifery</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Results to Review</label>
                                <div class="results-preview">
                                    <p>5 pending results found for review</p>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'scheduleExams':
                    modalTitle.textContent = 'Schedule Examinations';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Exam Type</label>
                                    <select class="form-control" required>
                                        <option value="">Select Type</option>
                                        <option value="midterm">Midterm Examination</option>
                                        <option value="final">Final Examination</option>
                                        <option value="practical">Practical Examination</option>
                                        <option value="supplementary">Supplementary Examination</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program</label>
                                    <select class="form-control" required>
                                        <option value="">Select Program</option>
                                        <option value="nursing">Certificate in Nursing</option>
                                        <option value="midwifery">Certificate in Midwifery</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Venue</label>
                                    <input type="text" class="form-control" placeholder="Main Examination Hall">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Invigilators</label>
                                    <textarea class="form-control" rows="2" placeholder="List of invigilators"></textarea>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'reviewApplications':
                    modalTitle.textContent = 'Review Student Applications';
                    modalBody.innerHTML = `
                        <div class="applications-review">
                            <h4>Pending Applications for Review</h4>
                            <div class="application-list">
                                <div class="application-item">
                                    <div class="app-header">
                                        <span class="app-id">ISNM2026001</span>
                                        <span class="app-date">Submitted 3 days ago</span>
                                    </div>
                                    <div class="app-details">
                                        <p><strong>Applicant:</strong> Jane Doe</p>
                                        <p><strong>Program:</strong> Certificate in Nursing</p>
                                        <p><strong>Status:</strong> Under Review</p>
                                    </div>
                                    <div class="app-actions">
                                        <button class="btn btn-sm btn-primary">Review Application</button>
                                        <button class="btn btn-sm btn-success">Approve</button>
                                        <button class="btn btn-sm btn-danger">Reject</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                default:
                    modalTitle.textContent = 'Action';
                    modalBody.innerHTML = '<p>Action content will be loaded here.</p>';
            }
            
            modal.show();
        }
        
        // Auto-refresh dashboard data
        setInterval(() => {
            // Refresh statistics
            console.log('Refreshing principal dashboard data...');
        }, 60000); // Every minute
    </script>
</body>
</html>
