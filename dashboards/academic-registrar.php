<?php
// Include unified authentication system
require_once '../auth-service.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

// Strict dashboard protection - only Academic Registrar allowed
if (!$auth_service->isAuthenticated()) {
    header('Location: ../staff-login.php');
    exit();
}

// Check if user has the correct role
$userRole = $_SESSION['role'] ?? '';
if (stripos($userRole, 'academic') === false && stripos($userRole, 'registrar') === false) {
    header('Location: ../staff-login.php?error=unauthorized');
    exit();
}

// Enhanced database connections
$students_conn = new mysqli('localhost', 'root', '', 'students_db');
$staff_conn = new mysqli('localhost', 'root', '', 'staffs_db');

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
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';

// Handle search and filter functionality
$search_term = $_GET['search'] ?? '';
$filter_course = $_GET['course'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_semester = $_GET['semester'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_student':
            handleAddStudent();
            break;
        case 'update_student':
            handleUpdateStudent();
            break;
        case 'delete_student':
            handleDeleteStudent();
            break;
        case 'generate_transcript':
            handleGenerateTranscript();
            break;
        case 'generate_results':
            handleGenerateResults();
            break;
        case 'bulk_import':
            handleBulkImport();
            break;
    }
}

// Get real academic registrar statistics from database
$stats_sql = "SELECT 
    COUNT(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as total_students,
    COUNT(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_registrations,
    COUNT(DISTINCT course) as total_courses,
    COUNT(CASE WHEN graduation_status = 'Graduated' THEN 1 ELSE 0 END) as graduates_this_year,
    AVG(gpa) as avg_gpa
FROM students 
WHERE YEAR(enrollment_date) = YEAR(CURRENT_DATE())";
$stats_result = $students_conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get recent students for profile display (using fallback data)
$recent_students = [
    ['first_name' => 'Alice', 'surname' => 'Student', 'program' => 'Nursing', 'status' => 'active'],
    ['first_name' => 'Bob', 'surname' => 'Student', 'program' => 'Midwifery', 'status' => 'active'],
    ['first_name' => 'Carol', 'surname' => 'Student', 'program' => 'Nursing', 'status' => 'active'],
    ['first_name' => 'David', 'surname' => 'Student', 'program' => 'Midwifery', 'status' => 'active']
];

// Get real activity logs from database
$activity_sql = "SELECT activity, created_at FROM academic_registrar_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 10";
$activity_result = $students_conn->query($activity_sql);
$recent_activities = $activity_result ? $activity_result->fetch_all(MYSQLI_ASSOC) : [];

// Enhanced functionality functions
function handleAddStudent() {
    global $students_conn, $staff_conn;
    
    $student_id = generateStudentId();
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name'] ?? '');
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $nationality = sanitizeInput($_POST['nationality']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email']);
    $course = sanitizeInput($_POST['course']);
    $year_of_study = sanitizeInput($_POST['year_of_study']);
    $semester = sanitizeInput($_POST['semester']);
    $registration_date = date('Y-m-d');
    
    $sql = "INSERT INTO students (student_id, first_name, surname, other_name, date_of_birth, gender, nationality, address, phone, email, course, year_of_study, semester, registration_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $students_conn->prepare($sql);
    $stmt->bind_param("ssssssssssssss", $student_id, $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $address, $phone, $email, $course, $year_of_study, $semester, $registration_date, 'Active', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student added successfully!";
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "New student registered: $first_name $surname", $_SESSION['user_id']);
        $log_stmt->execute();
    } else {
        $_SESSION['error'] = "Failed to add student.";
    }
    
    header("Location: academic-registrar.php");
    exit();
}

function handleUpdateStudent() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name'] ?? '');
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $nationality = sanitizeInput($_POST['nationality']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email']);
    $course = sanitizeInput($_POST['course']);
    $year_of_study = sanitizeInput($_POST['year_of_study']);
    $semester = sanitizeInput($_POST['semester']);
    
    $sql = "UPDATE students SET first_name = ?, surname = ?, other_name = ?, date_of_birth = ?, gender = ?, nationality = ?, address = ?, phone = ?, email = ?, course = ?, year_of_study = ?, semester = ?, updated_by = NOW() WHERE student_id = ?";
    
    $stmt = $students_conn->prepare($sql);
    $stmt->bind_param("ssssssssssss", $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $address, $phone, $email, $course, $year_of_study, $semester, $_SESSION['user_id'], $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student updated successfully!";
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "Student updated: $first_name $surname", $_SESSION['user_id']);
        $log_stmt->execute();
    } else {
        $_SESSION['error'] = "Failed to update student.";
    }
    
    header("Location: academic-registrar.php");
    exit();
}

function handleDeleteStudent() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    
    $sql = "DELETE FROM students WHERE student_id = ?";
    $stmt = $students_conn->prepare($sql);
    $stmt->bind_param("s", $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student deleted successfully!";
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "Student deleted: $student_id", $_SESSION['user_id']);
        $log_stmt->execute();
    } else {
        $_SESSION['error'] = "Failed to delete student.";
    }
    
    header("Location: academic-registrar.php");
    exit();
}

function handleGenerateTranscript() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    
    // Get student details
    $student_sql = "SELECT * FROM students WHERE student_id = ?";
    $stmt = $students_conn->prepare($student_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if ($student) {
        // Generate transcript
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="transcript_' . $student_id . '.pdf"');
        
        // Include transcript template
        include '../templates/professional_transcript_template.php';
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "Transcript generated for: $student_id", $_SESSION['user_id']);
        $log_stmt->execute();
    }
    
    exit();
}

function handleGenerateResults() {
    global $students_conn;
    
    $student_id = $_POST['student_id'];
    $semester = $_POST['semester'] ?? 'All';
    
    // Get student details and results
    $student_sql = "SELECT * FROM students WHERE student_id = ?";
    $stmt = $students_conn->prepare($student_sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if ($student) {
        // Generate results
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="results_' . $student_id . '_' . $semester . '.pdf"');
        
        // Include results template
        include '../templates/professional_results_template.php';
        
        // Log activity
        $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
        $log_stmt = $students_conn->prepare($log_sql);
        $log_stmt->bind_param("sis", "Results generated for: $student_id", $_SESSION['user_id']);
        $log_stmt->execute();
    }
    
    exit();
}

function handleBulkImport() {
    global $students_conn;
    
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['excel_file']['name'];
        $file_tmp = $_FILES['excel_file']['tmp_name'];
        $file_size = $_FILES['excel_file']['size'];
        
        // Validate file type
        $allowed_types = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (in_array($_FILES['excel_file']['type'], $allowed_types)) {
            
            // Process Excel file
            require '../includes/excel_reader.php';
            $data = readExcelFile($file_tmp);
            
            $imported_count = 0;
            $error_count = 0;
            
            foreach ($data as $row) {
                if ($imported_count > 0) { // Skip header row
                    $student_id = generateStudentId();
                    $first_name = sanitizeInput($row['first_name'] ?? '');
                    $surname = sanitizeInput($row['surname'] ?? '');
                    $other_name = sanitizeInput($row['other_name'] ?? '');
                    $date_of_birth = sanitizeInput($row['date_of_birth'] ?? '');
                    $gender = sanitizeInput($row['gender'] ?? '');
                    $nationality = sanitizeInput($row['nationality'] ?? '');
                    $address = sanitizeInput($row['address'] ?? '');
                    $phone = sanitizeInput($row['phone'] ?? '');
                    $email = sanitizeInput($row['email'] ?? '');
                    $course = sanitizeInput($row['course'] ?? '');
                    $year_of_study = sanitizeInput($row['year_of_study'] ?? '');
                    $semester = sanitizeInput($row['semester'] ?? '');
                    
                    if (!empty($first_name) && !empty($surname)) {
                        $sql = "INSERT INTO students (student_id, first_name, surname, other_name, date_of_birth, gender, nationality, address, phone, email, course, year_of_study, semester, registration_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $students_conn->prepare($sql);
                        $stmt->bind_param("ssssssssssssss", $student_id, $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $address, $phone, $email, $course, $year_of_study, $semester, date('Y-m-d'), 'Active', $_SESSION['user_id']);
                        
                        if ($stmt->execute()) {
                            $imported_count++;
                        } else {
                            $error_count++;
                        }
                    }
                }
                
                $_SESSION['success'] = "Bulk import completed: $imported_count students imported, $error_count errors.";
                
                // Log activity
                $log_sql = "INSERT INTO academic_registrar_activity_log (activity, created_at, created_by) VALUES (?, NOW(), ?)";
                $log_stmt = $students_conn->prepare($log_sql);
                $log_stmt->bind_param("sis", "Bulk import: $imported_count students", $_SESSION['user_id']);
                $log_stmt->execute();
            } else {
                $_SESSION['error'] = "Invalid file type. Please upload Excel file.";
            }
        } else {
            $_SESSION['error'] = "File upload error. Please try again.";
        }
    }
    
    header("Location: academic-registrar.php");
    exit();
}

function generateStudentId() {
    return 'STU' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT) . date('Y');
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Get filtered students
function getFilteredStudents($search_term, $filter_course, $filter_year, $filter_semester, $filter_status) {
    global $students_conn;
    
    $sql = "SELECT * FROM students WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $sql .= " AND (first_name LIKE ? OR surname LIKE ? OR student_id LIKE ? OR email LIKE ?)";
        $params = array_merge($params, ["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"]);
        $types .= "ssss";
    }
    
    if (!empty($filter_course)) {
        $sql .= " AND course = ?";
        $params = array_merge($params, [$filter_course]);
        $types .= "s";
    }
    
    if (!empty($filter_year)) {
        $sql .= " AND year_of_study = ?";
        $params = array_merge($params, [$filter_year]);
        $types .= "s";
    }
    
    if (!empty($filter_semester)) {
        $sql .= " AND semester = ?";
        $params = array_merge($params, [$filter_semester]);
        $types .= "s";
    }
    
    if (!empty($filter_status)) {
        $sql .= " AND status = ?";
        $params = array_merge($params, [$filter_status]);
        $types .= "s";
    }
    
    $sql .= " ORDER BY surname, first_name";
    
    $stmt = $students_conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->execute();
    }
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get courses for filter
function getCourses() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT course FROM students ORDER BY course";
    $result = $students_conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get years for filter
function getYears() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT year_of_study FROM students ORDER BY year_of_study DESC";
    $result = $students_conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get semesters for filter
function getSemesters() {
    global $students_conn;
    
    $sql = "SELECT DISTINCT semester FROM students ORDER BY semester";
    $result = $students_conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Registrar Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/enhanced_dashboard.css" rel="stylesheet">
    <style>
        /* Enhanced Professional Styling */
        .dashboard-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #3498db 100%);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transform: translateX(0);
            transition: all 0.3s ease;
        }
        
        .sidebar:hover {
            transform: translateX(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        
        .stat-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .btn-enhanced {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #764ba2 0%, #667eea 100%);
        }
        
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
        }
        
        .activity-item {
            background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .notification-badge {
            background: linear-gradient(45deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.8rem;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .advanced-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            height: 300px;
            position: relative;
            overflow: hidden;
        }
        
        .chart-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #6c757d;
            font-size: 1.2rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>Academic Registrar Dashboard</h4>
                <p><?php echo ($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? $user['last_name'] ?? ''); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Registration Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#applications">
                            <i class="fas fa-file-alt"></i> Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#registration">
                            <i class="fas fa-user-plus"></i> Student Registration
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#records">
                            <i class="fas fa-folder"></i> Student Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#transcripts">
                            <i class="fas fa-graduation-cap"></i> Transcripts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#certificates">
                            <i class="fas fa-certificate"></i> Certificates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Reports
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
                    <h1>Academic Registrar Dashboard</h1>
                    <p>Student Records & Registration Management</p>
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
                <!-- Registration Overview -->
                <section id="overview" class="content-section">
                    <h2>Registration Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_applications; ?></h3>
                                <p>Pending Applications</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $registered_students; ?></h3>
                                <p>Registered Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_registrations; ?></h3>
                                <p>Pending Registrations</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $transcripts_issued; ?></h3>
                                <p>Transcripts Issued (This Month)</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Student Profiles -->
                <section id="student-profiles" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Recent Student Profiles</h2>
                        <div>
                            <button class="btn btn-primary" onclick="openModal('viewAllStudents')">
                                <i class="fas fa-users"></i> View All Students
                            </button>
                            <button class="btn btn-success" onclick="openModal('addStudent')">
                                <i class="fas fa-user-plus"></i> Add New Student
                            </button>
                        </div>
                    </div>
                    
                    <!-- Student Search -->
                    <?php echo displayStudentSearchBox('Search students by name, ID, or phone...', 'registrarSearchResults'); ?>
                    
                    <!-- Student Profile Cards -->
                    <div class="row mt-4">
                        <?php foreach ($recent_students as $student): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <?php echo displayStudentProfileCard($student['student_id'], 'compact'); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Applications -->
                <section id="applications" class="content-section">
                    <h2>Application Management</h2>
                    <div class="application-actions">
                        <button class="btn btn-primary" onclick="openModal('reviewApplication')">
                            <i class="fas fa-eye"></i> Review Applications
                        </button>
                        <button class="btn btn-success" onclick="openModal('approveApplication')">
                            <i class="fas fa-check"></i> Approve Applications
                        </button>
                        <button class="btn btn-info" onclick="openModal('rejectApplication')">
                            <i class="fas fa-times"></i> Reject Applications
                        </button>
                        <button class="btn btn-warning" onclick="openModal('interviewSchedule')">
                            <i class="fas fa-calendar"></i> Schedule Interviews
                        </button>
                    </div>
                    
                    <div class="applications-table">
                        <h3>Recent Applications</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Application ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>APP-2026-001</td>
                                        <td>John Doe</td>
                                        <td>Certificate Nursing</td>
                                        <td>Apr 20, 2026</td>
                                        <td><span class="status-badge pending">Pending Review</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>APP-2026-002</td>
                                        <td>Jane Smith</td>
                                        <td>Certificate Midwifery</td>
                                        <td>Apr 19, 2026</td>
                                        <td><span class="status-badge in-progress">Under Review</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>APP-2026-003</td>
                                        <td>Michael Johnson</td>
                                        <td>Diploma Nursing</td>
                                        <td>Apr 18, 2026</td>
                                        <td><span class="status-badge approved">Approved</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-info">Register</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Student Registration -->
                <section id="registration" class="content-section">
                    <h2>Student Registration</h2>
                    <div class="registration-actions">
                        <button class="btn btn-primary" onclick="openModal('newRegistration')">
                            <i class="fas fa-user-plus"></i> New Registration
                        </button>
                        <button class="btn btn-success" onclick="openModal('bulkRegistration')">
                            <i class="fas fa-users"></i> Bulk Registration
                        </button>
                        <button class="btn btn-info" onclick="openModal('registrationReport')">
                            <i class="fas fa-chart-bar"></i> Registration Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('registrationAudit')">
                            <i class="fas fa-audit"></i> Registration Audit
                        </button>
                    </div>
                    
                    <div class="registration-overview">
                        <h3>Registration Statistics by Program</h3>
                        <div class="registration-stats">
                            <div class="stat-card">
                                <h4>Certificate Nursing</h4>
                                <div class="stat-number">120</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                            <div class="stat-card">
                                <h4>Certificate Midwifery</h4>
                                <div class="stat-number">95</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                            <div class="stat-card">
                                <h4>Diploma Nursing</h4>
                                <div class="stat-number">60</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                            <div class="stat-card">
                                <h4>Diploma Midwifery</h4>
                                <div class="stat-number">40</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Records -->
                <section id="records" class="content-section">
                    <h2>Student Records Management</h2>
                    <div class="records-actions">
                        <button class="btn btn-primary" onclick="openModal('searchStudent')">
                            <i class="fas fa-search"></i> Search Student
                        </button>
                        <button class="btn btn-success" onclick="openModal('updateRecord')">
                            <i class="fas fa-edit"></i> Update Record
                        </button>
                        <button class="btn btn-info" onclick="openModal('transferStudent')">
                            <i class="fas fa-exchange-alt"></i> Transfer Student
                        </button>
                        <button class="btn btn-warning btn-enhanced" onclick="openModal('deactivateStudent')">
                            <i class="fas fa-user-times"></i> Deactivate Student
                        </button>
                        <button class="btn btn-info btn-enhanced" onclick="openModal('bulkImport')">
                            <i class="fas fa-file-excel"></i> Bulk Import
                        </button>
                        <button class="btn btn-success btn-enhanced" onclick="openModal('generateReports')">
                            <i class="fas fa-chart-bar"></i> Generate Reports
                        </button>
                    </div>
                    
                    <div class="records-search">
                        <h3>Quick Student Search</h3>
                        <div class="search-form">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Enter Student ID, Name, or Email">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Transcripts -->
                <section id="transcripts" class="content-section">
                    <h2>Academic Transcripts</h2>
                    <div class="transcript-actions">
                        <button class="btn btn-primary" onclick="openModal('generateTranscript')">
                            <i class="fas fa-file-alt"></i> Generate Transcript
                        </button>
                        <button class="btn btn-success" onclick="openModal('verifyTranscript')">
                            <i class="fas fa-check-circle"></i> Verify Transcript
                        </button>
                        <button class="btn btn-info" onclick="openModal('transcriptTemplate')">
                            <i class="fas fa-file-code"></i> Transcript Template
                        </button>
                        <button class="btn btn-warning" onclick="openModal('transcriptLog')">
                            <i class="fas fa-list-alt"></i> Transcript Log
                        </button>
                    </div>
                    
                    <div class="transcript-overview">
                        <h3>Recent Transcript Requests</h3>
                        <div class="transcript-list">
                            <div class="transcript-item">
                                <div class="transcript-header">
                                    <h4>STU-2023-001 - John Doe</h4>
                                    <span class="status-badge completed">Completed</span>
                                </div>
                                <div class="transcript-details">
                                    <div class="detail">
                                        <span>Program:</span>
                                        <strong>Certificate Nursing</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Requested:</span>
                                        <strong>Apr 15, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Purpose:</span>
                                        <strong>Job Application</strong>
                                    </div>
                                </div>
                                <div class="transcript-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                    <button class="btn btn-sm btn-outline-info">Reprint</button>
                                </div>
                            </div>
                            
                            <div class="transcript-item">
                                <div class="transcript-header">
                                    <h4>STU-2023-045 - Jane Smith</h4>
                                    <span class="status-badge in-progress">Processing</span>
                                </div>
                                <div class="transcript-details">
                                    <div class="detail">
                                        <span>Program:</span>
                                        <strong>Certificate Midwifery</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Requested:</span>
                                        <strong>Apr 18, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Purpose:</span>
                                        <strong>Further Studies</strong>
                                    </div>
                                </div>
                                <div class="transcript-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-warning">Process</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Registrar Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon'] ?? 'check-circle'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['user_name'] ?? 'Academic Registrar'; ?></strong> <?php echo $activity['action'] ?? $activity['activity'] ?? 'Activity'; ?></p>
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
                case 'reviewApplication':
                    modalTitle.textContent = 'Review Application';
                    modalBody.innerHTML = `
                        <div class="application-review">
                            <h5>Application Details</h5>
                            <div class="applicant-info">
                                <div class="info-row">
                                    <strong>Application ID:</strong> APP-2026-001
                                </div>
                                <div class="info-row">
                                    <strong>Name:</strong> John Doe
                                </div>
                                <div class="info-row">
                                    <strong>Program:</strong> Certificate Nursing
                                </div>
                                <div class="info-row">
                                    <strong>Applied Date:</strong> April 20, 2026
                                </div>
                                <div class="info-row">
                                    <strong>Qualifications:</strong> UCE - English (C4), Math (C3), Biology (C3), Chemistry (C4), Physics (P7)
                                </div>
                            </div>
                            <div class="review-actions">
                                <h6>Review Comments</h6>
                                <textarea class="form-control mb-3" rows="3" placeholder="Add review comments..."></textarea>
                                <div class="decision-buttons">
                                    <button class="btn btn-success">Approve Application</button>
                                    <button class="btn btn-warning">Request Interview</button>
                                    <button class="btn btn-danger">Reject Application</button>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'newRegistration':
                    modalTitle.textContent = 'New Student Registration';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Student ID</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Application ID</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Surname</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Other Names</label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
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
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Intake</label>
                                        <select class="form-control" required>
                                            <option value="">Select Intake</option>
                                            <option value="january">January 2026</option>
                                            <option value="july">July 2026</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" required></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'generateTranscript':
                    modalTitle.textContent = 'Generate Academic Transcript';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transcript Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="official">Official Transcript</option>
                                    <option value="unofficial">Unofficial Transcript</option>
                                    <option value="provisional">Provisional Transcript</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purpose</label>
                                <select class="form-control" required>
                                    <option value="">Select Purpose</option>
                                    <option value="job">Job Application</option>
                                    <option value="further-studies">Further Studies</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="personal">Personal Use</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Include</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeGrades" checked>
                                    <label class="form-check-label" for="includeGrades">Grades and GPA</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeAttendance">
                                    <label class="form-check-label" for="includeAttendance">Attendance Records</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeConduct">
                                    <label class="form-check-label" for="includeConduct">Conduct Report</label>
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
    
    <!-- Student Profile Modal -->
    <?php echo displayStudentProfileModal(''); ?>
    
    <!-- Student Profile Styles -->
    <?php echo getStudentProfileStyles(); ?>
    
    <script>
    // Override modal functions for registrar dashboard
    function viewFullProfile(studentId) {
        showStudentProfileModal(studentId);
    }
    
    function editStudent(studentId) {
        window.location.href = '../student_accounts_management.php?action=edit&student_id=' + studentId;
    }
    
    function viewAcademic(studentId) {
        window.location.href = '../academic_records.php?student_id=' + studentId;
    }
    
    function viewFees(studentId) {
        window.location.href = '../fee_management.php?student_id=' + studentId;
    }
    
    function sendMessage(studentId) {
        // Implement messaging functionality
        alert('Messaging functionality would be implemented here for student: ' + studentId);
    }
    
    function printProfile(studentId) {
        window.print();
    }
    </script>
</body>
</html>
