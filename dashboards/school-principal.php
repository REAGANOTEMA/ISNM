<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['school principal', 'principal']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';

// Database connection
$students_conn = getStudentsConnection();
$staff_conn = getStaffConnection();

if ($students_conn->connect_error) {
    die("Students DB connection failed: " . $students_conn->connect_error);
}

if ($staff_conn->connect_error) {
    die("Staff DB connection failed: " . $staff_conn->connect_error);
}

// Set charset
$students_conn->set_charset("utf8mb4");
$staff_conn->set_charset("utf8mb4");

// Get user information from session
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';

// Get School Principal dashboard statistics using stored procedure
$stats_query = "CALL get_dashboard_statistics(?, ?)";
$stats_stmt = $staff_conn->prepare($stats_query);
$stats_stmt->bind_param("is", $user_id, $user_role);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Set statistics from database or fallback values
$total_students = $stats['total_students'] ?? 150;
$total_staff = $stats['total_staff'] ?? 25;
$pending_applications = $stats['pending_applications'] ?? 8;
$active_programs = $stats['active_programs'] ?? 2;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'approve_grade':
            handlePrincipalApproveGrade();
            break;
        case 'reject_grade':
            handlePrincipalRejectGrade();
            break;
        case 'approve_graduation':
            handleApproveGraduation();
            break;
    }
}

// Get academic performance
$avg_gpa_sql = "SELECT AVG(gpa) as avg_gpa FROM student_academic_profiles WHERE academic_status = 'Good Standing'";
$avg_gpa_result = $students_conn->query($avg_gpa_sql);
$avg_gpa = $avg_gpa_result ? $avg_gpa_result->fetch_assoc()['avg_gpa'] : 0;

// Get pending principal approvals
$pending_approvals_sql = "SELECT COUNT(*) as count FROM grading_approval_workflow WHERE current_stage = 'Principal Final Approval'";
$pending_approvals_result = $staff_conn->query($pending_approvals_sql);
$pending_approvals = $pending_approvals_result ? $pending_approvals_result->fetch_assoc()['count'] : 0;

// Get graduation candidates
$graduation_candidates_sql = "SELECT COUNT(*) as count FROM students WHERE status = 'Graduated' OR graduation_status = 'Pending'";
$graduation_candidates_result = $students_conn->query($graduation_candidates_sql);
$graduation_candidates = $graduation_candidates_result ? $graduation_candidates_result->fetch_assoc()['count'] : 0;

// Get recent activities from database
$activity_sql = "SELECT activity_description as activity, created_at FROM staff_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 10";
$activity_result = $staff_conn->query($activity_sql);
$recent_activities = $activity_result ? $activity_result->fetch_all(MYSQLI_ASSOC) : [];

// Principal approval handler functions
function handlePrincipalApproveGrade() {
    global $staff_conn;
    
    $workflow_number = sanitizeInput($_POST['workflow_number']);
    $comments = sanitizeInput($_POST['comments'] ?? '');
    
    // Get workflow details
    $workflow_sql = "SELECT * FROM grading_approval_workflow WHERE workflow_number = ?";
    $stmt = $staff_conn->prepare($workflow_sql);
    $stmt->bind_param("s", $workflow_number);
    $stmt->execute();
    $workflow = $stmt->get_result()->fetch_assoc();
    
    // Update workflow to published
    $update_sql = "UPDATE grading_approval_workflow SET current_stage = 'Published', principal_status = 'Approved', principal_approved_by = ?, principal_approved_at = NOW(), principal_comments = ?, updated_at = NOW() WHERE workflow_number = ?";
    $stmt = $staff_conn->prepare($update_sql);
    $stmt->bind_param("iss", $_SESSION['user_id'], $comments, $workflow_number);
    
    if ($stmt->execute()) {
        // Log grade change
        if ($workflow) {
            logGradeChange($workflow_number, $workflow['examination_record_id'], $_SESSION['user_id'], null, null, null, null, null, null, "Principal approved and published grade");
            
            // Send notification
            sendGradingNotification($workflow_number, $_SESSION['user_id'], $_SESSION['user_id'], 'Grade Published', 'Grade has been published by Principal');
        }
        
        $_SESSION['success'] = "Grade approved and published!";
    } else {
        $_SESSION['error'] = "Failed to approve grade.";
    }
    
    header("Location: school-principal.php#grading-approval");
    exit();
}

function handlePrincipalRejectGrade() {
    global $staff_conn;
    
    $workflow_number = sanitizeInput($_POST['workflow_number']);
    $rejection_reason = sanitizeInput($_POST['rejection_reason']);
    
    // Get workflow details
    $workflow_sql = "SELECT * FROM grading_approval_workflow WHERE workflow_number = ?";
    $stmt = $staff_conn->prepare($workflow_sql);
    $stmt->bind_param("s", $workflow_number);
    $stmt->execute();
    $workflow = $stmt->get_result()->fetch_assoc();
    
    // Update workflow to rejected
    $update_sql = "UPDATE grading_approval_workflow SET current_stage = 'Rejected', principal_status = 'Rejected', rejection_reason = ?, updated_at = NOW() WHERE workflow_number = ?";
    $stmt = $staff_conn->prepare($update_sql);
    $stmt->bind_param("ss", $rejection_reason, $workflow_number);
    
    if ($stmt->execute()) {
        // Log grade change
        if ($workflow) {
            logGradeChange($workflow_number, $workflow['examination_record_id'], $_SESSION['user_id'], null, null, null, null, null, null, "Principal rejected grade: " . $rejection_reason);
            
            // Send notification
            sendGradingNotification($workflow_number, $_SESSION['user_id'], $_SESSION['user_id'], 'Grade Rejected', 'Grade has been rejected by Principal: ' . $rejection_reason);
        }
        
        $_SESSION['success'] = "Grade rejected successfully.";
    } else {
        $_SESSION['error'] = "Failed to reject grade.";
    }
    
    header("Location: school-principal.php#grading-approval");
    exit();
}

function handleApproveGraduation() {
    global $students_conn;
    
    $student_ids = $_POST['student_ids'] ?? [];
    
    foreach ($student_ids as $student_id) {
        $update_sql = "UPDATE students SET graduation_status = 'Graduated', graduation_date = CURDATE() WHERE id = ?";
        $stmt = $students_conn->prepare($update_sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
    }
    
    $_SESSION['success'] = "Graduation approved for " . count($student_ids) . " students.";
    header("Location: school-principal.php#graduation");
    exit();
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function logGradeChange($workflow_number, $examination_record_id, $changed_by, $previous_grade, $new_grade, $previous_ca, $new_ca, $previous_exam, $new_exam, $reason) {
    global $staff_conn;
    
    $sql = "INSERT INTO grade_change_history (workflow_number, examination_record_id, changed_by, previous_grade, new_grade, previous_ca_marks, new_ca_marks, previous_exam_marks, new_exam_marks, change_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("sisssdddss", $workflow_number, $examination_record_id, $changed_by, $previous_grade, $new_grade, $previous_ca, $new_ca, $previous_exam, $new_exam, $reason);
    return $stmt->execute();
}

function sendGradingNotification($workflow_number, $recipient_id, $sender_id, $notification_type, $message) {
    global $staff_conn;
    
    $notification_id = 'NOT-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    $sql = "INSERT INTO grading_notifications (notification_id, workflow_number, recipient_id, sender_id, notification_type, message) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $staff_conn->prepare($sql);
    $stmt->bind_param("ssisss", $notification_id, $workflow_number, $recipient_id, $sender_id, $notification_type, $message);
    return $stmt->execute();
}

// Calculate pass percentage
$pass_rate_sql = "SELECT COUNT(CASE WHEN grade IN ('A', 'B', 'C', 'D') THEN 1 ELSE 0 END) as pass_count, COUNT(*) as total_count FROM examination_records WHERE grade IS NOT NULL";
$pass_rate_result = $students_conn->query($pass_rate_sql);
$pass_rate_data = $pass_rate_result ? $pass_rate_result->fetch_assoc() : ['pass_count' => 0, 'total_count' => 1];
$pass_percentage = $pass_rate_data['total_count'] > 0 ? ($pass_rate_data['pass_count'] / $pass_rate_data['total_count']) * 100 : 0;
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
        
        /* Grading System Styles for Principal Dashboard */
        .grading-approval-stats, .graduation-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .academic-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .filter-group select {
            min-width: 150px;
        }
        
        .student-academic-table, .graduation-list {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .graduation-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.good-standing {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.probation {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.suspension {
            background: #fee2e2;
            color: #991b1b;
        }
        
        @media (max-width: 768px) {
            .academic-filters {
                flex-direction: column;
            }
            
            .filter-group select {
                width: 100%;
            }
            
            .graduation-actions {
                flex-direction: column;
            }
            
            .graduation-actions button {
                width: 100%;
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

                <!-- Grading Approval (Principal) -->
                <section id="grading-approval" class="content-section">
                    <h2>Principal Grade Approval</h2>
                    <div class="grading-approval-stats">
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_approvals; ?></h3>
                                <p>Pending Final Approvals</p>
                                <small>Requires your attention</small>
                            </div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3>
                                    <?php
                                    $published_sql = "SELECT COUNT(*) as count FROM grading_approval_workflow WHERE current_stage = 'Published'";
                                    $published_result = $staff_conn->query($published_sql);
                                    $published_count = $published_result ? $published_result->fetch_assoc()['count'] : 0;
                                    echo $published_count;
                                    ?>
                                </h3>
                                <p>Published Results</p>
                                <small>This semester</small>
                            </div>
                        </div>
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($pass_percentage, 1); ?>%</h3>
                                <p>Overall Pass Rate</p>
                                <small>All courses</small>
                            </div>
                        </div>
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($avg_gpa, 2); ?></h3>
                                <p>Average GPA</p>
                                <small>School-wide</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pending-approvals">
                        <h3>Pending Grade Approvals</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Workflow #</th>
                                        <th>Course</th>
                                        <th>Student</th>
                                        <th>Total Marks</th>
                                        <th>Grade</th>
                                        <th>Registrar Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $pending_grades_sql = "SELECT gaw.*, er.course_code, er.course_name, s.student_number, s.first_name, s.last_name 
                                                          FROM grading_approval_workflow gaw
                                                          JOIN examination_records er ON gaw.examination_record_id = er.id
                                                          JOIN students s ON er.student_id = s.id
                                                          WHERE gaw.current_stage = 'Principal Final Approval'
                                                          ORDER BY gaw.submitted_at DESC LIMIT 10";
                                    $pending_grades_result = $staff_conn->query($pending_grades_sql);
                                    $pending_grades = $pending_grades_result ? $pending_grades_result->fetch_all(MYSQLI_ASSOC) : [];
                                    
                                    foreach ($pending_grades as $grade):
                                    ?>
                                    <tr>
                                        <td><?php echo $grade['workflow_number']; ?></td>
                                        <td><?php echo $grade['course_code'] . ' - ' . $grade['course_name']; ?></td>
                                        <td><?php echo $grade['student_number'] . ' - ' . $grade['first_name'] . ' ' . $grade['last_name']; ?></td>
                                        <td><?php echo $grade['total_marks_calculated'] ?? 0; ?></td>
                                        <td><?php echo $grade['grade'] ?? 'N/A'; ?></td>
                                        <td><span class="status-badge <?php echo strtolower($grade['registrar_status']); ?>"><?php echo $grade['registrar_status']; ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success" onclick="principalApproveGrade('<?php echo $grade['workflow_number']; ?>')">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="principalRejectGrade('<?php echo $grade['workflow_number']; ?>')">Reject</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Student Academic Overview -->
                <section id="student-academic" class="content-section">
                    <h2>Student Academic Overview</h2>
                    <div class="academic-filters">
                        <div class="filter-group">
                            <label>Program:</label>
                            <select class="form-control" id="filterProgram">
                                <option value="">All Programs</option>
                                <option value="Nursing">Nursing</option>
                                <option value="Midwifery">Midwifery</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Year:</label>
                            <select class="form-control" id="filterYear">
                                <option value="">All Years</option>
                                <option value="1">Year 1</option>
                                <option value="2">Year 2</option>
                                <option value="3">Year 3</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Academic Standing:</label>
                            <select class="form-control" id="filterStanding">
                                <option value="">All</option>
                                <option value="Good Standing">Good Standing</option>
                                <option value="Probation">Probation</option>
                                <option value="Suspension">Suspension</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" onclick="filterStudents()">Filter</button>
                    </div>
                    
                    <div class="student-academic-table">
                        <h3>Student Academic Performance</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Year</th>
                                        <th>GPA</th>
                                        <th>Academic Standing</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $students_sql = "SELECT s.*, sap.gpa, sap.academic_status 
                                                   FROM students s 
                                                   LEFT JOIN student_academic_profiles sap ON s.id = sap.student_id 
                                                   WHERE s.status = 'Active' 
                                                   ORDER BY sap.gpa DESC LIMIT 10";
                                    $students_result = $students_conn->query($students_sql);
                                    $students = $students_result ? $students_result->fetch_all(MYSQLI_ASSOC) : [];
                                    
                                    foreach ($students as $student):
                                    ?>
                                    <tr>
                                        <td><?php echo $student['student_number']; ?></td>
                                        <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                        <td><?php echo $student['program']; ?></td>
                                        <td><?php echo $student['year_of_study']; ?></td>
                                        <td><?php echo number_format($student['gpa'] ?? 0, 2); ?></td>
                                        <td><span class="status-badge <?php echo strtolower(str_replace(' ', '-', $student['academic_status'] ?? 'good-standing')); ?>"><?php echo $student['academic_status'] ?? 'Good Standing'; ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View Profile</button>
                                            <button class="btn btn-sm btn-outline-info">View Grades</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Graduation Management -->
                <section id="graduation" class="content-section">
                    <h2>Graduation Management</h2>
                    <div class="graduation-stats">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $graduation_candidates; ?></h3>
                                <p>Graduation Candidates</p>
                                <small>Pending approval</small>
                            </div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <div class="stat-content">
                                <h3>
                                    <?php
                                    $graduated_sql = "SELECT COUNT(*) as count FROM students WHERE graduation_status = 'Graduated'";
                                    $graduated_result = $students_conn->query($graduated_sql);
                                    $graduated_count = $graduated_result ? $graduated_result->fetch_assoc()['count'] : 0;
                                    echo $graduated_count;
                                    ?>
                                </h3>
                                <p>Graduated This Year</p>
                                <small>Successful completions</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="graduation-list">
                        <h3>Graduation Candidates</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAllGraduates" onchange="toggleAllGraduates()"></th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Cumulative GPA</th>
                                        <th>Completion Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $candidates_sql = "SELECT s.*, sap.gpa as cumulative_gpa 
                                                      FROM students s 
                                                      LEFT JOIN student_academic_profiles sap ON s.id = sap.student_id 
                                                      WHERE s.graduation_status = 'Pending' AND s.year_of_study >= 2
                                                      ORDER BY sap.gpa DESC LIMIT 10";
                                    $candidates_result = $students_conn->query($candidates_sql);
                                    $candidates = $candidates_result ? $candidates_result->fetch_all(MYSQLI_ASSOC) : [];
                                    
                                    foreach ($candidates as $candidate):
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" class="graduate-checkbox" value="<?php echo $candidate['id']; ?>"></td>
                                        <td><?php echo $candidate['student_number']; ?></td>
                                        <td><?php echo $candidate['first_name'] . ' ' . $candidate['last_name']; ?></td>
                                        <td><?php echo $candidate['program']; ?></td>
                                        <td><?php echo number_format($candidate['cumulative_gpa'] ?? 0, 2); ?></td>
                                        <td><span class="status-badge pending">Pending Review</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="printTranscript('<?php echo $candidate['student_number']; ?>')">Print Transcript</button>
                                            <button class="btn btn-sm btn-outline-info">View Academic Record</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="graduation-actions">
                            <button class="btn btn-success" onclick="approveSelectedGraduation()">Approve Selected for Graduation</button>
                            <button class="btn btn-info" onclick="openModal('graduationReport')">Graduation Report</button>
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
                case 'reviewApplication':
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
            console.log('Refreshing principal dashboard data...');
        }, 60000);
        
        function principalApproveGrade(workflowNumber) {
            if (confirm('Approve this grade for final publication?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'school-principal.php';
                form.innerHTML = '<input type="hidden" name="action" value="approve_grade"><input type="hidden" name="workflow_number" value="' + workflowNumber + '"><input type="hidden" name="comments" value="Approved by Principal">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function principalRejectGrade(workflowNumber) {
            const reason = prompt('Enter rejection reason:');
            if (reason) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'school-principal.php';
                form.innerHTML = '<input type="hidden" name="action" value="reject_grade"><input type="hidden" name="workflow_number" value="' + workflowNumber + '"><input type="hidden" name="rejection_reason" value="' + reason + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleAllGraduates() {
            const checkboxes = document.querySelectorAll('.graduate-checkbox');
            const selectAll = document.getElementById('selectAllGraduates');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }
        
        function approveSelectedGraduation() {
            const checkboxes = document.querySelectorAll('.graduate-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select students to approve for graduation.');
                return;
            }
            if (confirm('Approve ' + checkboxes.length + ' students for graduation?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'school-principal.php';
                form.innerHTML = '<input type="hidden" name="action" value="approve_graduation">';
                checkboxes.forEach(cb => {
                    form.innerHTML += '<input type="hidden" name="student_ids[]" value="' + cb.value + '">';
                });
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function filterStudents() {
            alert('Filter functionality would be implemented here.');
        }
        
        function printTranscript(studentNumber) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Transcript - ' + studentNumber + '</title>');
            printWindow.document.write('<style>@media print { body { font-family: Arial, sans-serif; } .header { text-align: center; margin-bottom: 20px; } .logo { max-width: 100px; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<div class="header"><img src="../images/school-logo.png" class="logo" alt="ISNM Logo"><h2>Iganga School of Nursing and Midwifery</h2><h3>Official Academic Transcript</h3><p>Student #: ' + studentNumber + '</p></div>');
            printWindow.document.write('<p>This is an official academic transcript. For verification, contact the School Principal.</p>');
            printWindow.document.write('<button onclick="window.print()">Print</button>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
        }
    </script>
</body>
</html>

