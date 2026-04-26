<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';
include_once 'includes/photo_upload.php';

// Check if user is logged in and has appropriate access level
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 8) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
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
            case 'import_students':
                handleImportStudents();
                break;
            case 'export_students':
                handleExportStudents();
                break;
            case 'upload_photo':
                handlePhotoUpload();
                break;
        }
    }
}

// Handle photo upload
function handlePhotoUpload() {
    $student_id = $_POST['student_id'] ?? '';
    
    if (empty($student_id)) {
        $_SESSION['error'] = "Student ID is required";
        header("Location: student_accounts_management.php");
        exit();
    }
    
    if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadPassportPhoto($_FILES['passport_photo'], $student_id);
        
        if ($upload_result['success']) {
            if (updateStudentPhoto($student_id, $upload_result['filename'])) {
                $_SESSION['success'] = "Passport photo uploaded successfully!";
            } else {
                $_SESSION['error'] = "Photo uploaded but database update failed";
            }
        } else {
            $_SESSION['error'] = $upload_result['message'];
        }
    } else {
        $_SESSION['error'] = "Please select a photo to upload";
    }
    
    header("Location: student_accounts_management.php");
    exit();
}

// Get filter parameters
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';
$intake_filter = isset($_GET['intake']) ? $_GET['intake'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = '';

if (!empty($program_filter)) {
    $where_conditions[] = "program = ?";
    $params[] = $program_filter;
    $types .= 's';
}

if (!empty($intake_filter)) {
    $where_conditions[] = "intake_year = ?";
    $params[] = $intake_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search_term)) {
    $where_conditions[] = "(first_name LIKE ? OR surname LIKE ? OR other_name LIKE ? OR student_id LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $search_param = "%$search_term%";
    for ($i = 0; $i < 6; $i++) {
        $params[] = $search_param;
        $types .= 's';
    }
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get students with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) as total FROM students $where_clause";
$count_result = executeQuery($count_sql, $params, $types);
$total_students = $count_result[0]['total'];
$total_pages = ceil($total_students / $per_page);

$sql = "SELECT * FROM students $where_clause ORDER BY surname, first_name LIMIT ? OFFSET ?";
$pag_params = array_merge($params, [$per_page, $offset]);
$pag_types = $types . 'ii';
$students = executeQuery($sql, $pag_params, $pag_types);

// Get unique programs and intakes for filters
$programs = executeQuery("SELECT DISTINCT program FROM students ORDER BY program");
$intakes = executeQuery("SELECT DISTINCT intake_year FROM students ORDER BY intake_year DESC");

// Function definitions
function handleAddStudent() {
    global $conn;
    
    $student_id = generateStudentId();
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $nationality = sanitizeInput($_POST['nationality']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $program = sanitizeInput($_POST['program']);
    $level = sanitizeInput($_POST['level']);
    $intake_year = sanitizeInput($_POST['intake_year']);
    $intake_period = sanitizeInput($_POST['intake_period']);
    $guardian_name = sanitizeInput($_POST['guardian_name']);
    $guardian_phone = sanitizeInput($_POST['guardian_phone']);
    $guardian_email = sanitizeInput($_POST['guardian_email']);
    $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name']);
    $emergency_contact_phone = sanitizeInput($_POST['emergency_contact_phone']);
    
    $sql = "INSERT INTO students (student_id, first_name, surname, other_name, date_of_birth, gender, nationality, address, phone, email, program, level, intake_year, intake_period, enrollment_date, guardian_name, guardian_phone, guardian_email, emergency_contact_name, emergency_contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssssss", $student_id, $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $address, $phone, $email, $program, $level, $intake_year, $intake_period, $guardian_name, $guardian_phone, $guardian_email, $emergency_contact_name, $emergency_contact_phone);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Student Added', "Added new student: $student_id - $first_name $surname", 'students', $student_id);
        $_SESSION['success'] = "Student added successfully!";
    } else {
        $_SESSION['error'] = "Error adding student: " . $conn->error;
    }
    
    header("Location: student_accounts_management.php");
    exit();
}

function handleUpdateStudent() {
    global $conn;
    
    $student_id = sanitizeInput($_POST['student_id']);
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $nationality = sanitizeInput($_POST['nationality']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $program = sanitizeInput($_POST['program']);
    $level = sanitizeInput($_POST['level']);
    $intake_year = sanitizeInput($_POST['intake_year']);
    $intake_period = sanitizeInput($_POST['intake_period']);
    $status = sanitizeInput($_POST['status']);
    $guardian_name = sanitizeInput($_POST['guardian_name']);
    $guardian_phone = sanitizeInput($_POST['guardian_phone']);
    $guardian_email = sanitizeInput($_POST['guardian_email']);
    $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name']);
    $emergency_contact_phone = sanitizeInput($_POST['emergency_contact_phone']);
    
    $sql = "UPDATE students SET first_name = ?, surname = ?, other_name = ?, date_of_birth = ?, gender = ?, nationality = ?, address = ?, phone = ?, email = ?, program = ?, level = ?, intake_year = ?, intake_period = ?, status = ?, guardian_name = ?, guardian_phone = ?, guardian_email = ?, emergency_contact_name = ?, emergency_contact_phone = ? WHERE student_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssssssss", $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $address, $phone, $email, $program, $level, $intake_year, $intake_period, $status, $guardian_name, $guardian_phone, $guardian_email, $emergency_contact_name, $emergency_contact_phone, $student_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Student Updated', "Updated student: $student_id - $first_name $surname", 'students', $student_id);
        $_SESSION['success'] = "Student updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating student: " . $conn->error;
    }
    
    header("Location: student_accounts_management.php");
    exit();
}

function handleDeleteStudent() {
    global $conn;
    
    $student_id = sanitizeInput($_POST['student_id']);
    
    // Check if student has related records
    $check_sql = "SELECT COUNT(*) as count FROM fee_payments WHERE student_id = ?";
    $check_result = executeQuery($check_sql, [$student_id], 's');
    
    if ($check_result[0]['count'] > 0) {
        $_SESSION['error'] = "Cannot delete student with payment records. Please archive instead.";
    } else {
        $sql = "DELETE FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $student_id);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'Student Deleted', "Deleted student: $student_id", 'students', $student_id);
            $_SESSION['success'] = "Student deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting student: " . $conn->error;
        }
    }
    
    header("Location: student_accounts_management.php");
    exit();
}

function handleImportStudents() {
    if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == 0) {
        $file = $_FILES['import_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if ($handle) {
            $header = fgetcsv($handle); // Skip header row
            $imported = 0;
            $errors = [];
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                try {
                    $student_id = generateStudentId();
                    $first_name = trim($data[0] ?? '');
                    $surname = trim($data[1] ?? '');
                    $other_name = trim($data[2] ?? '');
                    $date_of_birth = trim($data[3] ?? '');
                    $gender = trim($data[4] ?? '');
                    $nationality = trim($data[5] ?? 'Uganda');
                    $phone = trim($data[6] ?? '');
                    $email = trim($data[7] ?? '');
                    $program = trim($data[8] ?? '');
                    $level = trim($data[9] ?? '');
                    $intake_year = trim($data[10] ?? '');
                    $intake_period = trim($data[11] ?? '');
                    $guardian_name = trim($data[12] ?? '');
                    $guardian_phone = trim($data[13] ?? '');
                    
                    if (empty($first_name) || empty($surname) || empty($program)) {
                        $errors[] = "Missing required data for: $first_name $surname";
                        continue;
                    }
                    
                    $sql = "INSERT INTO students (student_id, first_name, surname, other_name, date_of_birth, gender, nationality, phone, email, program, level, intake_year, intake_period, enrollment_date, guardian_name, guardian_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssssssssssss", $student_id, $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $phone, $email, $program, $level, $intake_year, $intake_period, $guardian_name, $guardian_phone);
                    
                    if ($stmt->execute()) {
                        $imported++;
                    } else {
                        $errors[] = "Error importing: $first_name $surname - " . $conn->error;
                    }
                } catch (Exception $e) {
                    $errors[] = "Error processing row: " . $e->getMessage();
                }
            }
            
            fclose($handle);
            
            if ($imported > 0) {
                logActivity($_SESSION['user_id'], $_SESSION['role'], 'Students Imported', "Imported $imported students from CSV file", 'students', null);
                $_SESSION['success'] = "Successfully imported $imported students!";
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = "Import completed with " . count($errors) . " errors. First few: " . implode("; ", array_slice($errors, 0, 3));
            }
        } else {
            $_SESSION['error'] = "Error opening file";
        }
    } else {
        $_SESSION['error'] = "Error uploading file";
    }
    
    header("Location: student_accounts_management.php");
    exit();
}

function handleExportStudents() {
    global $conn;
    
    $sql = "SELECT * FROM students ORDER BY surname, first_name";
    $result = $conn->query($sql);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, ['Student ID', 'First Name', 'Surname', 'Other Name', 'Date of Birth', 'Gender', 'Nationality', 'Phone', 'Email', 'Program', 'Level', 'Intake Year', 'Intake Period', 'Status', 'Guardian Name', 'Guardian Phone', 'Emergency Contact']);
    
    // Data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['student_id'],
            $row['first_name'],
            $row['surname'],
            $row['other_name'],
            $row['date_of_birth'],
            $row['gender'],
            $row['nationality'],
            $row['phone'],
            $row['email'],
            $row['program'],
            $row['level'],
            $row['intake_year'],
            $row['intake_period'],
            $row['status'],
            $row['guardian_name'],
            $row['guardian_phone'],
            $row['emergency_contact_name']
        ]);
    }
    
    fclose($output);
    exit();
}

function generateStudentId() {
    global $conn;
    
    do {
        $year = date('Y');
        $random = mt_rand(1000, 9999);
        $student_id = "ISNM/$year/$random";
        
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE student_id = ?";
        $check_result = executeQuery($check_sql, [$student_id], 's');
    } while ($check_result[0]['count'] > 0);
    
    return $student_id;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Accounts Management - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --dark-text: #333;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--accent-color) !important;
        }

        .main-container {
            padding: 2rem;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }

        .student-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #218838);
            border: none;
            border-radius: 8px;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #c82333);
            border: none;
            border-radius: 8px;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #e0a800);
            border: none;
            border-radius: 8px;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 0.75rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.25);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .page-link {
            color: var(--primary-color);
            border: none;
            margin: 0 2px;
            border-radius: 8px;
        }

        .page-link:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .search-box {
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .search-box input {
            padding-left: 40px;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--primary-color);
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .student-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            cursor: pointer;
        }

        .photo-upload-btn {
            position: relative;
            overflow: hidden;
        }

        .photo-upload-btn:hover {
            background-color: #5a6268 !important;
        }

        #photo_preview img {
            border: 3px solid #007bff;
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }

        .photo-requirements {
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .stats-card {
                margin-bottom: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> ISNM Student Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="student_accounts_management.php">
                            <i class="fas fa-users"></i> Student Accounts
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="reports.php?report=student_demographics">Student Demographics</a></li>
                            <li><a class="dropdown-item" href="reports.php?report=enrollment_stats">Enrollment Statistics</a></li>
                            <li><a class="dropdown-item" href="reports.php?report=academic_performance">Academic Performance</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid main-container">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-2">
                        <i class="fas fa-users text-primary"></i> Student Accounts Management
                    </h1>
                    <p class="text-muted mb-0">Manage and monitor all student accounts and information</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="fas fa-plus"></i> Add Student
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fas fa-upload"></i> Import CSV
                        </button>
                        <button type="button" class="btn btn-warning" onclick="exportStudents()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card fade-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo number_format($total_students); ?></div>
                            <div class="text-muted">Total Students</div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card fade-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number">
                                <?php 
                                $active_sql = "SELECT COUNT(*) as count FROM students WHERE status = 'active'";
                                $active_result = executeQuery($active_sql);
                                echo number_format($active_result[0]['count']);
                                ?>
                            </div>
                            <div class="text-muted">Active Students</div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card fade-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number">
                                <?php 
                                $programs_sql = "SELECT COUNT(DISTINCT program) as count FROM students";
                                $programs_result = executeQuery($programs_sql);
                                echo number_format($programs_result[0]['count']);
                                ?>
                            </div>
                            <div class="text-muted">Programs</div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-graduation-cap fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card fade-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number">
                                <?php 
                                $current_year = date('Y');
                                $year_sql = "SELECT COUNT(*) as count FROM students WHERE intake_year = ?";
                                $year_result = executeQuery($year_sql, [$current_year], 's');
                                echo number_format($year_result[0]['count']);
                                ?>
                            </div>
                            <div class="text-muted">Current Year</div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-calendar fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card fade-in">
            <form method="GET" action="student_accounts_management.php">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" name="search" placeholder="Search by name, ID, phone..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Program</label>
                        <select class="form-select" name="program">
                            <option value="">All Programs</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?php echo htmlspecialchars($program['program']); ?>" <?php echo $program_filter === $program['program'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($program['program']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Intake Year</label>
                        <select class="form-select" name="intake">
                            <option value="">All Years</option>
                            <?php foreach ($intakes as $intake): ?>
                                <option value="<?php echo htmlspecialchars($intake['intake_year']); ?>" <?php echo $intake_filter === $intake['intake_year'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($intake['intake_year']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="graduated" <?php echo $status_filter === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                            <option value="withdrawn" <?php echo $status_filter === 'withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="student_accounts_management.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Students Table -->
        <div class="student-table fade-in">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Program</th>
                            <th>Level</th>
                            <th>Intake</th>
                            <th>Status</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No students found matching your criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo getPassportPhotoUrl($student['profile_image']); ?>" alt="Student" class="student-avatar" onerror="this.src='images/default-avatar.png'">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['student_id']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name']); ?>
                                        <?php if (!empty($student['other_name'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($student['other_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="fas fa-<?php echo $student['gender'] === 'Male' ? 'mars' : 'venus'; ?>"></i>
                                            <?php echo htmlspecialchars($student['gender']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($student['program']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['level']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($student['intake_year']); ?>
                                        <?php if (!empty($student['intake_period'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($student['intake_period']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($student['status']) {
                                            case 'active':
                                                $status_class = 'bg-success';
                                                break;
                                            case 'suspended':
                                                $status_class = 'bg-warning';
                                                break;
                                            case 'graduated':
                                                $status_class = 'bg-info';
                                                break;
                                            case 'withdrawn':
                                                $status_class = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?> status-badge">
                                            <?php echo ucfirst(htmlspecialchars($student['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="tel:<?php echo htmlspecialchars($student['phone']); ?>">
                                            <?php echo htmlspecialchars($student['phone']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="viewStudent('<?php echo $student['student_id']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="editStudent('<?php echo $student['student_id']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="uploadPhoto('<?php echo $student['student_id']; ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?>')">
                                                <i class="fas fa-camera"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" onclick="viewAcademicRecord('<?php echo $student['student_id']; ?>')">
                                                <i class="fas fa-graduation-cap"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success" onclick="viewFees('<?php echo $student['student_id']; ?>')">
                                                <i class="fas fa-money-bill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Student pagination">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active_class = $i == $page ? 'active' : '';
                            echo "<li class='page-item $active_class'>
                                <a class='page-link' href='?page=$i&" . http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) . "'>$i</a>
                            </li>";
                        }

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo "<li class='page-item'>
                                <a class='page-link' href='?page=$total_pages&" . http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) . "'>$total_pages</a>
                            </li>";
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus"></i> Add New Student
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="student_accounts_management.php">
                    <input type="hidden" name="action" value="add_student">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Surname *</label>
                                <input type="text" class="form-control" name="surname" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Other Name</label>
                                <input type="text" class="form-control" name="other_name">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" name="date_of_birth" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gender *</label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nationality</label>
                                <input type="text" class="form-control" name="nationality" value="Uganda">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Phone *</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="1"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Program *</label>
                                <select class="form-select" name="program" required>
                                    <option value="">Select Program</option>
                                    <option value="Certificate Midwifery">Certificate Midwifery</option>
                                    <option value="Diploma Midwifery">Diploma Midwifery</option>
                                    <option value="Diploma Midwifery Extension">Diploma Midwifery Extension</option>
                                    <option value="Diploma Nursing Extension">Diploma Nursing Extension</option>
                                    <option value="Certificate Nursing">Certificate Nursing</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Level *</label>
                                <select class="form-select" name="level" required>
                                    <option value="">Select Level</option>
                                    <option value="Certificate">Certificate</option>
                                    <option value="Diploma">Diploma</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Intake Year *</label>
                                <input type="text" class="form-control" name="intake_year" value="<?php echo date('Y'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Intake Period</label>
                                <select class="form-select" name="intake_period">
                                    <option value="">Select Period</option>
                                    <option value="January">January</option>
                                    <option value="May">May</option>
                                    <option value="July">July</option>
                                    <option value="September">September</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Guardian Name</label>
                                <input type="text" class="form-control" name="guardian_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Guardian Phone</label>
                                <input type="tel" class="form-control" name="guardian_phone">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Guardian Email</label>
                                <input type="email" class="form-control" name="guardian_email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Name</label>
                                <input type="text" class="form-control" name="emergency_contact_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Phone</label>
                                <input type="tel" class="form-control" name="emergency_contact_phone">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload"></i> Import Students from CSV
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="student_accounts_management.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_students">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">CSV File *</label>
                            <input type="file" class="form-control" name="import_file" accept=".csv" required>
                            <div class="form-text">
                                CSV format: First Name, Surname, Other Name, Date of Birth, Gender, Nationality, Phone, Email, Program, Level, Intake Year, Intake Period, Guardian Name, Guardian Phone
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Note:</strong> The first row should contain column headers. Existing students will be skipped.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload"></i> Import Students
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Photo Upload Modal -->
    <div class="modal fade" id="photoUploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-camera"></i> Upload Passport Photo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="student_accounts_management.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_photo">
                    <input type="hidden" id="photo_student_id" name="student_id">
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <h6 id="student_name_display" class="text-muted"></h6>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Passport Photo *</label>
                            <input type="file" class="form-control" name="passport_photo" id="passport_photo" accept="image/*" required>
                            <div class="form-text">
                                <strong>Requirements:</strong><br>
                                • Format: JPG, PNG, or GIF<br>
                                • Size: Maximum 5MB<br>
                                • Dimensions: Portrait orientation (height > width)<br>
                                • Recommended: 350x450 pixels (passport size)
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div id="photo_preview" class="text-center" style="display: none;">
                                <h6>Photo Preview:</h6>
                                <img id="preview_image" src="" alt="Preview" class="img-fluid" style="max-width: 200px; border: 2px solid #ddd; border-radius: 8px;">
                                <div id="photo_requirements" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> The photo will be automatically resized to passport dimensions (350x450 pixels) while maintaining aspect ratio.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Export students function
        function exportStudents() {
            window.location.href = 'student_accounts_management.php?action=export_students';
        }

        // View student details
        function viewStudent(studentId) {
            // Implementation for viewing student details
            alert('View student details for: ' + studentId);
            // You can implement a modal or redirect to a detailed view page
        }

        // Edit student
        function editStudent(studentId) {
            // Implementation for editing student
            alert('Edit student: ' + studentId);
            // You can implement a modal with pre-filled data
        }

        // View academic record
        function viewAcademicRecord(studentId) {
            window.location.href = 'academic_records.php?student_id=' + studentId;
        }

        // View fees
        function viewFees(studentId) {
            window.location.href = 'fee_management.php?student_id=' + studentId;
        }

        // Upload photo
        function uploadPhoto(studentId, studentName) {
            $('#photo_student_id').val(studentId);
            $('#student_name_display').text(studentName);
            $('#passport_photo').val('');
            $('#photo_preview').hide();
            $('#photoUploadModal').modal('show');
        }

        // Confirm delete
        function confirmDelete(studentId, studentName) {
            if (confirm('Are you sure you want to delete ' + studentName + '? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'student_accounts_management.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_student';
                
                const studentIdInput = document.createElement('input');
                studentIdInput.type = 'hidden';
                studentIdInput.name = 'student_id';
                studentIdInput.value = studentId;
                
                form.appendChild(actionInput);
                form.appendChild(studentIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Loading state for table
        $(document).ready(function() {
            // Add loading animation when filtering
            $('form').submit(function() {
                $('.student-table').html('<div class="loading"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading students...</div>');
            });
        });

        // Photo preview functionality
        $(document).ready(function() {
            $('#passport_photo').change(function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        $('#preview_image').attr('src', e.target.result);
                        $('#photo_preview').show();
                        
                        // Check photo requirements
                        const img = new Image();
                        img.onload = function() {
                            const requirements = [];
                            
                            if (img.width > img.height) {
                                requirements.push('❌ Photo should be in portrait orientation');
                            } else {
                                requirements.push('✅ Portrait orientation OK');
                            }
                            
                            if (img.width < 200 || img.height < 250) {
                                requirements.push('❌ Photo too small (min 200x250)');
                            } else {
                                requirements.push('✅ Size OK');
                            }
                            
                            if (img.width > 800 || img.height > 1000) {
                                requirements.push('⚠️ Large file (will be resized)');
                            } else {
                                requirements.push('✅ File size OK');
                            }
                            
                            $('#photo_requirements').html(requirements.join('<br>'));
                        };
                        img.src = e.target.result;
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        });

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl+N for new student
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                $('#addStudentModal').modal('show');
            }
            // Ctrl+E for export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportStudents();
            }
        });
    </script>
</body>
</html>

<?php
// Display alerts
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
        <i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($_SESSION['error']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['error']);
}
?>
