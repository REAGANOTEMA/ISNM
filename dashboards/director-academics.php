<?php
include_once '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Director Academics') {
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

// Get academic statistics (using fallback data only)
$total_students = 150; // Fallback value
$total_lecturers = 20; // Fallback value
$active_courses = 8; // Fallback value
$avg_gpa = 3.4; // Fallback value

// Get program statistics (using fallback data)
$nursing_students = 85; // Fallback value
$midwifery_students = 65; // Fallback value
$diploma_nursing = 45; // Fallback value
$diploma_midwifery = 35; // Fallback value

// Get recent academic activities (using fallback data)
$academic_activities = [
    ['activity' => 'New curriculum developed', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['activity' => 'Student grades processed', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
    ['activity' => 'Academic calendar updated', 'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
    ['activity' => 'Faculty meeting conducted', 'created_at' => date('Y-m-d H:i:s', strtotime('-7 hours'))]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director Academics Dashboard - ISNM</title>
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
                <span class="badge bg-info">Director Academics</span>
            </div>
            
            <nav class="sidebar-menu">
                <a href="#overview" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Academic Overview
                </a>
                <a href="#programs" class="nav-link">
                    <i class="fas fa-book"></i> Program Management
                </a>
                <a href="#curriculum" class="nav-link">
                    <i class="fas fa-book-open"></i> Curriculum Development
                </a>
                <a href="#faculty" class="nav-link">
                    <i class="fas fa-chalkboard-teacher"></i> Faculty Management
                </a>
                <a href="#examinations" class="nav-link">
                    <i class="fas fa-clipboard-list"></i> Examinations & Assessment
                </a>
                <a href="#research" class="nav-link">
                    <i class="fas fa-microscope"></i> Research & Innovation
                </a>
                <a href="#quality" class="nav-link">
                    <i class="fas fa-shield-alt"></i> Quality Assurance
                </a>
                <a href="#accreditation" class="nav-link">
                    <i class="fas fa-award"></i> Accreditation & Compliance
                </a>
                <a href="#reports" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Academic Reports
                </a>
                <a href="#partnerships" class="nav-link">
                    <i class="fas fa-handshake"></i> Partnerships
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
                    <h1>Director Academics Dashboard</h1>
                    <p>Academic Programs Oversight - Iganga School of Nursing and Midwifery</p>
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
                <!-- Academic Overview -->
                <section id="overview" class="content-section">
                    <h2>Academic Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_students); ?></h3>
                                <p>Total Students</p>
                                <small>Across all programs</small>
                            </div>
                        </div>
                        
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_lecturers); ?></h3>
                                <p>Faculty Members</p>
                                <small>Teaching staff</small>
                            </div>
                        </div>
                        
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($active_courses); ?></h3>
                                <p>Active Programs</p>
                                <small>Academic programs</small>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($avg_gpa, 2); ?></h3>
                                <p>Average GPA</p>
                                <small>Current semester</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Program Distribution -->
                    <div class="program-distribution">
                        <h3>Program Enrollment Distribution</h3>
                        <div class="program-stats">
                            <div class="program-stat">
                                <h4>Certificate in Nursing</h4>
                                <div class="program-bar">
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo ($nursing_students / $total_students) * 100; ?>%"></div>
                                    </div>
                                    <span class="program-count"><?php echo $nursing_students; ?> students</span>
                                </div>
                            </div>
                            
                            <div class="program-stat">
                                <h4>Certificate in Midwifery</h4>
                                <div class="program-bar">
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?php echo ($midwifery_students / $total_students) * 100; ?>%"></div>
                                    </div>
                                    <span class="program-count"><?php echo $midwifery_students; ?> students</span>
                                </div>
                            </div>
                            
                            <div class="program-stat">
                                <h4>Diploma in Nursing</h4>
                                <div class="program-bar">
                                    <div class="progress">
                                        <div class="progress-bar bg-info" style="width: <?php echo ($diploma_nursing / $total_students) * 100; ?>%"></div>
                                    </div>
                                    <span class="program-count"><?php echo $diploma_nursing; ?> students</span>
                                </div>
                            </div>
                            
                            <div class="program-stat">
                                <h4>Diploma in Midwifery</h4>
                                <div class="program-bar">
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" style="width: <?php echo ($diploma_midwifery / $total_students) * 100; ?>%"></div>
                                    </div>
                                    <span class="program-count"><?php echo $diploma_midwifery; ?> students</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Program Management -->
                <section id="programs" class="content-section">
                    <h2>Program Management</h2>
                    <div class="program-actions">
                        <button class="btn btn-primary" onclick="openModal('createProgram')">
                            <i class="fas fa-plus"></i> New Program
                        </button>
                        <button class="btn btn-success" onclick="openModal('reviewPrograms')">
                            <i class="fas fa-check-circle"></i> Review Programs
                        </button>
                        <button class="btn btn-info" onclick="openModal('programAccreditation')">
                            <i class="fas fa-award"></i> Accreditation Status
                        </button>
                        <button class="btn btn-warning" onclick="openModal('programReport')">
                            <i class="fas fa-chart-bar"></i> Program Reports
                        </button>
                    </div>
                    
                    <!-- Active Programs -->
                    <div class="active-programs">
                        <h3>Active Academic Programs</h3>
                        <div class="programs-grid">
                            <div class="program-card">
                                <div class="program-header">
                                    <h4>Certificate in Nursing</h4>
                                    <span class="program-status accredited">UNMC Accredited</span>
                                </div>
                                <div class="program-details">
                                    <div class="program-info">
                                        <div class="info-item">
                                            <span>Duration:</span>
                                            <strong>2½ Years</strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Students:</span>
                                            <strong><?php echo $nursing_students; ?></strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Completion Rate:</span>
                                            <strong>92%</strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Employment Rate:</span>
                                            <strong>87%</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="program-actions">
                                    <button class="btn btn-sm btn-outline-primary">Curriculum</button>
                                    <button class="btn btn-sm btn-outline-info">Students</button>
                                    <button class="btn btn-sm btn-outline-success">Faculty</button>
                                </div>
                            </div>
                            
                            <div class="program-card">
                                <div class="program-header">
                                    <h4>Certificate in Midwifery</h4>
                                    <span class="program-status accredited">UNMC Accredited</span>
                                </div>
                                <div class="program-details">
                                    <div class="program-info">
                                        <div class="info-item">
                                            <span>Duration:</span>
                                            <strong>2½ Years</strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Students:</span>
                                            <strong><?php echo $midwifery_students; ?></strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Completion Rate:</span>
                                            <strong>95%</strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Employment Rate:</span>
                                            <strong>90%</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="program-actions">
                                    <button class="btn btn-sm btn-outline-primary">Curriculum</button>
                                    <button class="btn btn-sm btn-outline-info">Students</button>
                                    <button class="btn btn-sm btn-outline-success">Faculty</button>
                                </div>
                            </div>
                            
                            <div class="program-card">
                                <div class="program-header">
                                    <h4>Diploma in Nursing - Extension</h4>
                                    <span class="program-status pending">Under Review</span>
                                </div>
                                <div class="program-details">
                                    <div class="program-info">
                                        <div class="info-item">
                                            <span>Duration:</span>
                                            <strong>1½ Years</strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Students:</span>
                                            <strong><?php echo $diploma_nursing; ?></strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Completion Rate:</span>
                                            <strong>88%</strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Employment Rate:</span>
                                            <strong>85%</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="program-actions">
                                    <button class="btn btn-sm btn-outline-primary">Curriculum</button>
                                    <button class="btn btn-sm btn-outline-info">Students</button>
                                    <button class="btn btn-sm btn-outline-warning">Submit for Accreditation</button>
                                </div>
                            </div>
                            
                            <div class="program-card">
                                <div class="program-header">
                                    <h4>Diploma in Midwifery - Extension</h4>
                                    <span class="program-status pending">Under Review</span>
                                </div>
                                <div class="program-details">
                                    <div class="program-info">
                                        <div class="info-item">
                                            <span>Duration:</span>
                                            <strong>1½ Years</strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Students:</span>
                                            <strong><?php echo $diploma_midwifery; ?></strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Completion Rate:</span>
                                            <strong>90%</strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Employment Rate:</span>
                                            <strong>88%</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="program-actions">
                                    <button class="btn btn-sm btn-outline-primary">Curriculum</button>
                                    <button class="btn btn-sm btn-outline-info">Students</button>
                                    <button class="btn btn-sm btn-outline-warning">Submit for Accreditation</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Faculty Management -->
                <section id="faculty" class="content-section">
                    <h2>Faculty Management</h2>
                    <div class="faculty-overview">
                        <div class="faculty-stats">
                            <div class="faculty-stat">
                                <h4><?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'Senior Lecturers' AND status = 'active'")->fetch_assoc()['count']; ?></h4>
                                <p>Senior Lecturers</p>
                            </div>
                            <div class="faculty-stat">
                                <h4><?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'Lecturers' AND status = 'active'")->fetch_assoc()['count']; ?></h4>
                                <p>Lecturers</p>
                            </div>
                            <div class="faculty-stat">
                                <h4><?php echo $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('Head of Nursing', 'Head of Midwifery') AND status = 'active'")->fetch_assoc()['count']; ?></h4>
                                <p>Department Heads</p>
                            </div>
                            <div class="faculty-stat">
                                <h4>94%</h4>
                                <p>Faculty Satisfaction</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Faculty Actions -->
                    <div class="faculty-actions">
                        <button class="btn btn-primary" onclick="openModal('hireFaculty')">
                            <i class="fas fa-user-plus"></i> Hire Faculty
                        </button>
                        <button class="btn btn-success" onclick="openModal('facultyTraining')">
                            <i class="fas fa-graduation-cap"></i> Training Programs
                        </button>
                        <button class="btn btn-info" onclick="openModal('performanceReview')">
                            <i class="fas fa-chart-line"></i> Performance Reviews
                        </button>
                        <button class="btn btn-warning" onclick="openModal('facultyReport')">
                            <i class="fas fa-file-alt"></i> Faculty Report
                        </button>
                    </div>
                    
                    <!-- Department Heads -->
                    <div class="department-heads">
                        <h3>Department Leadership</h3>
                        <div class="heads-grid">
                            <div class="head-card">
                                <div class="head-info">
                                    <img src="../images/default-avatar.png" alt="Head" class="head-avatar">
                                    <div class="head-details">
                                        <h4>Head of Nursing</h4>
                                        <p>Senior Lecturer</p>
                                        <small>15 years experience</small>
                                    </div>
                                </div>
                                <div class="head-stats">
                                    <div class="stat-item">
                                        <span>Department Size:</span>
                                        <strong><?php echo $nursing_students + $diploma_nursing; ?> students</strong>
                                    </div>
                                    <div class="stat-item">
                                        <span>Faculty:</span>
                                        <strong>12 members</strong>
                                    </div>
                                </div>
                                <div class="head-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Profile</button>
                                    <button class="btn btn-sm btn-outline-info">Department Report</button>
                                </div>
                            </div>
                            
                            <div class="head-card">
                                <div class="head-info">
                                    <img src="../images/default-avatar.png" alt="Head" class="head-avatar">
                                    <div class="head-details">
                                        <h4>Head of Midwifery</h4>
                                        <p>Senior Lecturer</p>
                                        <small>12 years experience</small>
                                    </div>
                                </div>
                                <div class="head-stats">
                                    <div class="stat-item">
                                        <span>Department Size:</span>
                                        <strong><?php echo $midwifery_students + $diploma_midwifery; ?> students</strong>
                                    </div>
                                    <div class="stat-item">
                                        <span>Faculty:</span>
                                        <strong>10 members</strong>
                                    </div>
                                </div>
                                <div class="head-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Profile</button>
                                    <button class="btn btn-sm btn-outline-info">Department Report</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Curriculum Development -->
                <section id="curriculum" class="content-section">
                    <h2>Curriculum Development</h2>
                    <div class="curriculum-actions">
                        <button class="btn btn-primary" onclick="openModal('designCurriculum')">
                            <i class="fas fa-pencil-ruler"></i> Design Curriculum
                        </button>
                        <button class="btn btn-success" onclick="openModal('reviewCurriculum')">
                            <i class="fas fa-check-circle"></i> Review & Approve
                        </button>
                        <button class="btn btn-info" onclick="openModal('updateCurriculum')">
                            <i class="fas fa-sync"></i> Update Curriculum
                        </button>
                        <button class="btn btn-warning" onclick="openModal('curriculumReport')">
                            <i class="fas fa-chart-bar"></i> Curriculum Analysis
                        </button>
                    </div>
                    
                    <!-- Current Curricula -->
                    <div class="current-curricula">
                        <h3>Active Curricula</h3>
                        <div class="curriculum-list">
                            <div class="curriculum-item">
                                <div class="curriculum-header">
                                    <h4>Nursing Curriculum - Certificate Level</h4>
                                    <span class="curriculum-status approved">UNMC Approved</span>
                                </div>
                                <div class="curriculum-details">
                                    <p><strong>Last Updated:</strong> January 2025</p>
                                    <p><strong>Version:</strong> 3.0</p>
                                    <p><strong>Credit Hours:</strong> 120</p>
                                    <p><strong>Practicum Hours:</strong> 800</p>
                                </div>
                                <div class="curriculum-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Download PDF</button>
                                    <button class="btn btn-sm btn-outline-warning">Schedule Review</button>
                                </div>
                            </div>
                            
                            <div class="curriculum-item">
                                <div class="curriculum-header">
                                    <h4>Midwifery Curriculum - Certificate Level</h4>
                                    <span class="curriculum-status approved">UNMC Approved</span>
                                </div>
                                <div class="curriculum-details">
                                    <p><strong>Last Updated:</strong> January 2025</p>
                                    <p><strong>Version:</strong> 3.0</p>
                                    <p><strong>Credit Hours:</strong> 120</p>
                                    <p><strong>Practicum Hours:</strong> 900</p>
                                </div>
                                <div class="curriculum-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Download PDF</button>
                                    <button class="btn btn-sm btn-outline-warning">Schedule Review</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Examinations & Assessment -->
                <section id="examinations" class="content-section">
                    <h2>Examinations & Assessment</h2>
                    <div class="exam-actions">
                        <button class="btn btn-primary" onclick="openModal('scheduleExam')">
                            <i class="fas fa-calendar-plus"></i> Schedule Exam
                        </button>
                        <button class="btn btn-success" onclick="openModal('reviewResults')">
                            <i class="fas fa-check-double"></i> Review Results
                        </button>
                        <button class="btn btn-info" onclick="openModal('examStatistics')">
                            <i class="fas fa-chart-pie"></i> Exam Statistics
                        </button>
                        <button class="btn btn-warning" onclick="openModal('examReport')">
                            <i class="fas fa-file-alt"></i> Exam Reports
                        </button>
                    </div>
                    
                    <!-- Upcoming Examinations -->
                    <div class="upcoming-exams">
                        <h3>Upcoming Examinations</h3>
                        <div class="exam-timeline">
                            <div class="exam-item">
                                <div class="exam-date">
                                    <span class="date">15</span>
                                    <span class="month">May</span>
                                </div>
                                <div class="exam-details">
                                    <h4>Midterm Examinations</h4>
                                    <p>All Programs - Semester 1</p>
                                    <div class="exam-info">
                                        <span><i class="fas fa-clock"></i> 2 weeks duration</span>
                                        <span><i class="fas fa-users"></i> <?php echo $total_students; ?> students</span>
                                    </div>
                                </div>
                                <div class="exam-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Schedule</button>
                                    <button class="btn btn-sm btn-outline-warning">Edit</button>
                                </div>
                            </div>
                            
                            <div class="exam-item">
                                <div class="exam-date">
                                    <span class="date">20</span>
                                    <span class="month">Jun</span>
                                </div>
                                <div class="exam-details">
                                    <h4>Final Examinations</h4>
                                    <p>All Programs - Semester 1</p>
                                    <div class="exam-info">
                                        <span><i class="fas fa-clock"></i> 3 weeks duration</span>
                                        <span><i class="fas fa-users"></i> <?php echo $total_students; ?> students</span>
                                    </div>
                                </div>
                                <div class="exam-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Schedule</button>
                                    <button class="btn btn-sm btn-outline-warning">Edit</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Academic Activities -->
                <section class="content-section">
                    <h2>Recent Academic Activities</h2>
                    <div class="activities-table">
                        <h3>Academic System Activity Log</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Activity</th>
                                        <th>Module</th>
                                        <th>Date/Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($academic_activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <img src="../images/default-avatar.png" alt="User">
                                                <span><?php echo $activity['user_role']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $activity['activity_description']; ?></td>
                                        <td><?php echo $activity['module_affected'] ?? 'Academics'; ?></td>
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
                case 'createProgram':
                    modalTitle.textContent = 'Create New Academic Program';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program Name</label>
                                    <input type="text" class="form-control" placeholder="Enter program name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program Type</label>
                                    <select class="form-control" required>
                                        <option value="">Select Type</option>
                                        <option value="certificate">Certificate</option>
                                        <option value="diploma">Diploma</option>
                                        <option value="degree">Degree</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Duration (Years)</label>
                                    <input type="number" class="form-control" min="0.5" max="5" step="0.5" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Credit Hours</label>
                                    <input type="number" class="form-control" min="30" max="200" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Practicum Hours</label>
                                    <input type="number" class="form-control" min="100" max="2000" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Entry Requirements</label>
                                    <textarea class="form-control" rows="2" required></textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Program Description</label>
                                    <textarea class="form-control" rows="4" required></textarea>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'designCurriculum':
                    modalTitle.textContent = 'Design New Curriculum';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program</label>
                                    <select class="form-control" required>
                                        <option value="">Select Program</option>
                                        <option value="nursing-certificate">Certificate in Nursing</option>
                                        <option value="midwifery-certificate">Certificate in Midwifery</option>
                                        <option value="nursing-diploma">Diploma in Nursing</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Curriculum Version</label>
                                    <input type="text" class="form-control" placeholder="e.g., 4.0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Total Credit Hours</label>
                                    <input type="number" class="form-control" min="30" max="200" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Practicum Hours</label>
                                    <input type="number" class="form-control" min="100" max="2000" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Course Modules</label>
                                    <div id="courseModules">
                                        <div class="module-item">
                                            <input type="text" class="form-control mb-2" placeholder="Module name">
                                            <input type="number" class="form-control mb-2" placeholder="Credit hours">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addModule()">Add Module</button>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Learning Outcomes</label>
                                    <textarea class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                case 'scheduleExam':
                    modalTitle.textContent = 'Schedule Examination';
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
                                        <option value="all">All Programs</option>
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
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Venue</label>
                                    <input type="text" class="form-control" placeholder="Main Examination Hall">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Duration (hours)</label>
                                    <input type="number" class="form-control" min="1" max="8" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Invigilators</label>
                                    <textarea class="form-control" rows="2" placeholder="List of invigilators"></textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Special Instructions</label>
                                    <textarea class="form-control" rows="2" placeholder="Any special instructions for students"></textarea>
                                </div>
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
        
        function addModule() {
            const modulesContainer = document.getElementById('courseModules');
            const moduleItem = document.createElement('div');
            moduleItem.className = 'module-item';
            moduleItem.innerHTML = `
                <input type="text" class="form-control mb-2" placeholder="Module name">
                <input type="number" class="form-control mb-2" placeholder="Credit hours">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeModule(this)">Remove</button>
            `;
            modulesContainer.appendChild(moduleItem);
        }
        
        function removeModule(button) {
            button.parentElement.remove();
        }
        
        // Auto-refresh dashboard data
        setInterval(() => {
            // Refresh academic statistics
            console.log('Refreshing academic dashboard data...');
        }, 60000); // Every minute
    </script>
</body>
</html>
