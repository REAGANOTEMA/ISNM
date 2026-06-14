<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../views/student_data_loader.php';

$ctx = bootstrapStaffDashboard(['admissions', 'director']);
$studentsConn = $ctx['students'];
$staffConn = $ctx['staff'];
$user = $ctx['user'];
$userName = $user['full_name'] ?? 'Admissions User';

$loader = new StudentDataLoader();
$allStudents = $loader->loadAllStudents();

// Define the required items list
$requiredItems = [
    'surgical_gloves' => 'Surgical Gloves',
    'examination_gloves' => 'Examination Gloves',
    'photocopying_ream' => 'Photocopying Ream',
    'ruled_paper_reams' => 'Ruled Paper Reams',
    'omo' => 'Omo',
    'toilet_papers' => 'Toilet Papers',
    'compound_brooms' => 'Compound brooms',
    'soft_brooms' => 'Soft brooms',
    'rake' => 'Rake',
    'cobweb_brush' => 'Cobweb brush',
    'scrubbing_brush' => 'Scrubbing Brush',
    'squeezer' => 'Squeezer',
    'toilet_brush' => 'Toilet Brush',
    'jik' => 'JIK',
    'vim' => 'Vim',
    'mops' => 'Mops',
    'sanitizer' => 'Sanitizer',
    'liquid_soap' => 'Liquid Soap',
    'face_masks' => 'Face Masks',
    'heavy_duty_gloves' => 'Heavy duty Gloves',
];

// Load or create requirements tracking storage (JSON file for simplicity)
$requirementsFile = __DIR__ . '/../data/student_requirements.json';
$requirementsData = [];
if (file_exists($requirementsFile)) {
    $requirementsData = json_decode(file_get_contents($requirementsFile), true) ?? [];
}

// Handle saving requirements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_requirements') {
        $studentKey = $_POST['student_key'] ?? '';
        $clearedItems = $_POST['cleared_items'] ?? [];
        
        $requirementsData[$studentKey] = [
            'student_key' => $studentKey,
            'cleared_items' => $clearedItems,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userName,
        ];
        
        // Ensure data directory exists
        if (!is_dir(dirname($requirementsFile))) {
            mkdir(dirname($requirementsFile), 0755, true);
        }
        
        file_put_contents($requirementsFile, json_encode($requirementsData, JSON_PRETTY_PRINT));
        $_SESSION['success'] = 'Requirements saved successfully!';
        header('Location: director-admissions.php');
        exit;
    }
    
    if ($_POST['action'] === 'add_student') {
        // Save to a JSON file for new students (or you could use DB)
        $newStudentsFile = __DIR__ . '/../data/new_students.json';
        $newStudents = [];
        if (file_exists($newStudentsFile)) {
            $newStudents = json_decode(file_get_contents($newStudentsFile), true) ?? [];
        }
        
        $newStudent = [
            'id' => uniqid(),
            'full_name' => $_POST['full_name'] ?? '',
            'registration_number' => $_POST['registration_number'] ?? '',
            'index_number' => $_POST['index_number'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'program' => $_POST['program'] ?? '',
            'intake_year' => $_POST['intake_year'] ?? date('Y'),
            'gender' => $_POST['gender'] ?? '',
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $userName,
            'source_file' => 'manual_add'
        ];
        
        $newStudents[] = $newStudent;
        
        if (!is_dir(dirname($newStudentsFile))) {
            mkdir(dirname($newStudentsFile), 0755, true);
        }
        
        file_put_contents($newStudentsFile, json_encode($newStudents, JSON_PRETTY_PRINT));
        $_SESSION['success'] = 'Student added successfully!';
        header('Location: director-admissions.php');
        exit;
    }
}

// Load manually added students too
$newStudentsFile = __DIR__ . '/../data/new_students.json';
if (file_exists($newStudentsFile)) {
    $manualStudents = json_decode(file_get_contents($newStudentsFile), true) ?? [];
    foreach ($manualStudents as $student) {
        $allStudents[] = $student;
    }
}

$studentsTables = tableList($studentsConn);
$staffTables = tableList($staffConn);

$stats = [
    'applications' => 0,
    'pending' => 0,
    'admitted' => 0,
    'enrolled' => 0,
];

if (in_array('student_admissions', $studentsTables, true)) {
    $stats['applications'] = scalar($studentsConn, 'SELECT COUNT(*) AS c FROM student_admissions');
    $stats['pending'] = scalar($studentsConn, "SELECT COUNT(*) AS c FROM student_admissions WHERE admission_status IN ('Applied','Interview')");
    $stats['admitted'] = scalar($studentsConn, "SELECT COUNT(*) AS c FROM student_admissions WHERE admission_status = 'Admitted'");
    $stats['enrolled'] = scalar($studentsConn, "SELECT COUNT(*) AS c FROM student_admissions WHERE admission_status = 'Enrolled'");
}

$stats['active_students'] = in_array('students', $studentsTables, true)
    ? scalar($studentsConn, "SELECT COUNT(*) AS c FROM students WHERE status = 'Active'")
    : 0;

$recentApplications = [];
if (in_array('student_admissions', $studentsTables, true)) {
    $recentApplications = rows($studentsConn, "SELECT application_number, applicant_name, program, academic_year, admission_status, application_date FROM student_admissions ORDER BY application_date DESC, id DESC LIMIT 8");
}

$recentActivity = rows($staffConn, "SELECT activity_description AS activity, created_at FROM staff_activity_log WHERE module_accessed IN ('admissions', 'requirements', 'student_admissions') OR activity_description LIKE '%admission%' ORDER BY created_at DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director Admissions Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="../dashboards/dashboard-mobile.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f8fafc;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .search-bar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 30px;
            border-radius: 24px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.3);
        }
        
        .search-bar .form-control, .search-bar .form-select {
            border-radius: 16px;
            border: none;
            padding: 14px 24px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .search-bar .form-control:focus, .search-bar .form-select:focus {
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 28px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 28px;
            font-weight: 600;
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        .stat-card {
            border-radius: 20px;
            padding: 28px;
            border: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: all 0.4s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card:hover::before {
            top: -30%;
            right: -30%;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .student-card {
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 24px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid #e5e7eb;
            background: white;
        }
        
        .student-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.12);
            border-color: var(--primary);
        }
        
        .student-header {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            padding: 28px;
            position: relative;
            overflow: hidden;
        }
        
        .student-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-radius: 50%;
        }
        
        .student-header .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 700;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 14px;
            padding: 28px;
        }
        
        .requirement-item {
            padding: 16px 20px;
            border-radius: 14px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .requirement-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
            border-color: #cbd5e1;
        }
        
        .requirement-item.cleared {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-color: #6ee7b7;
        }
        
        .requirement-item.cleared:hover {
            background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
        }
        
        .requirement-item input[type="checkbox"] {
            width: 26px;
            height: 26px;
            cursor: pointer;
            border-radius: 8px;
            border: 2px solid #cbd5e1;
            transition: all 0.3s ease;
        }
        
        .requirement-item input[type="checkbox"]:checked {
            background: var(--success);
            border-color: var(--success);
        }
        
        .progress-section {
            padding: 0 28px 28px;
        }
        
        .progress {
            border-radius: 100px;
            height: 14px;
            background: #e2e8f0;
            overflow: hidden;
        }
        
        .progress-bar {
            border-radius: 100px;
            background: linear-gradient(90deg, var(--success) 0%, #059669 100%);
            transition: width 0.6s ease;
        }
        
        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .section-heading h2 {
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }
        
        .sidebar-menu .nav-link {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu .nav-link:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            transform: translateX(5px);
        }
        
        .sidebar-menu .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 24px 24px 0 0;
            padding: 24px 28px;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .table {
            border-radius: 16px;
            overflow: hidden;
        }
        
        .table thead {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .table thead th {
            border: none;
            padding: 16px;
            font-weight: 600;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
        }
        
        .badge {
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 600;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @media print {
            .dashboard-sidebar, .dashboard-header, .search-bar, .save-btn, nav, .btn {
                display: none !important;
            }
            .dashboard-main, .dashboard-container {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }
            .student-card {
                break-inside: avoid;
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #e5e7eb;
            }
            body {
                background: white;
            }
        }
        
        .timeline-item {
            padding: 20px;
            background: #f8fafc;
            border-radius: 16px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="notification">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="dashboard-container">
        <div class="dashboard-sidebar glass-effect">
            <div class="sidebar-header text-center">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo mb-3" style="width: 80px; height: 80px;">
                <h4 class="mb-1">ISNM Management</h4>
                <small class="text-muted"><?php echo htmlspecialchars($userName); ?></small>
                <span class="badge bg-primary mt-2">Director of Admissions</span>
            </div>
            <nav class="sidebar-menu mt-4">
                <a href="#overview" class="nav-link active"><i class="fas fa-chart-pie me-3"></i> Overview</a>
                <a href="#requirements" class="nav-link"><i class="fas fa-clipboard-check me-3"></i> Requirements Portal</a>
                <a href="#applications" class="nav-link"><i class="fas fa-file-alt me-3"></i> Applications</a>
                <a href="#activity" class="nav-link"><i class="fas fa-history me-3"></i> Activity</a>
            </nav>
            <div class="sidebar-footer mt-auto">
                <a href="../logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
        </div>
        <div class="dashboard-main">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1 class="mb-1">Director Admissions Dashboard</h1>
                    <p class="text-muted mb-0">Admissions, requirements, and applicant tracking</p>
                </div>
                <div class="header-right d-flex gap-3 align-items-center">
                    <div class="date-time glass-effect px-4 py-2 rounded-3">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <span class="fw-semibold"><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-user-plus"></i>
                        Add New Student
                    </button>
                    <div class="user-menu d-flex align-items-center gap-2">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <span class="fw-semibold"><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </div>
            <div class="dashboard-content">
                <section id="overview" class="content-section">
                    <div class="stats-grid">
                        <div class="stat-card bg-white shadow-lg">
                            <div class="stat-icon text-primary"><i class="fas fa-file-signature"></i></div>
                            <div class="stat-content">
                                <h3 class="fw-bold text-dark mb-1"><?php echo number_format($stats['applications']); ?></h3>
                                <p class="text-muted mb-0">Total Applications</p>
                            </div>
                        </div>
                        <div class="stat-card bg-white shadow-lg">
                            <div class="stat-icon text-warning"><i class="fas fa-clock"></i></div>
                            <div class="stat-content">
                                <h3 class="fw-bold text-dark mb-1"><?php echo number_format($stats['pending']); ?></h3>
                                <p class="text-muted mb-0">Pending Review</p>
                            </div>
                        </div>
                        <div class="stat-card bg-white shadow-lg">
                            <div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-content">
                                <h3 class="fw-bold text-dark mb-1"><?php echo number_format($stats['admitted']); ?></h3>
                                <p class="text-muted mb-0">Admitted</p>
                            </div>
                        </div>
                        <div class="stat-card bg-white shadow-lg">
                            <div class="stat-icon text-info"><i class="fas fa-user-graduate"></i></div>
                            <div class="stat-content">
                                <h3 class="fw-bold text-dark mb-1"><?php echo number_format($stats['enrolled']); ?></h3>
                                <p class="text-muted mb-0">Enrolled</p>
                            </div>
                        </div>
                        <div class="stat-card bg-white shadow-lg">
                            <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                            <div class="stat-content">
                                <h3 class="fw-bold text-dark mb-1"><?php echo number_format($stats['active_students']); ?></h3>
                                <p class="text-muted mb-0">Active Students</p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section id="requirements" class="content-section">
                    <div class="section-heading">
                        <div>
                            <h2><i class="fas fa-clipboard-check me-2 text-primary"></i>Requirements Portal</h2>
                            <span class="text-muted">Track and clear student admission requirements</span>
                        </div>
                    </div>
                    
                    <div class="search-bar">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-primary"></i></span>
                                    <input type="text" id="studentSearch" class="form-control" placeholder="Search by name, admission number, phone...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select id="filterName" class="form-select">
                                    <option value="">All Names</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="filterAdmission" class="form-select">
                                    <option value="">All Admission #</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="filterPhone" class="form-select">
                                    <option value="">All Phones</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button id="clearFilters" class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2">
                                    <i class="fas fa-redo"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="studentsList">
                        <?php foreach ($allStudents as $index => $student): 
                            $studentKey = md5(($student['index_number'] ?? $student['registration_number'] ?? $student['full_name'] ?? $index));
                            $studentReq = $requirementsData[$studentKey] ?? ['cleared_items' => []];
                            $clearedCount = count($studentReq['cleared_items'] ?? []);
                            $totalItems = count($requiredItems);
                            $progressPercent = $totalItems > 0 ? round(($clearedCount / $totalItems) * 100) : 0;
                        ?>
                        <div class="student-card" data-name="<?php echo htmlspecialchars(strtolower($student['full_name'] ?? '')); ?>" data-admission="<?php echo htmlspecialchars(strtolower($student['registration_number'] ?? $student['index_number'] ?? '')); ?>" data-phone="<?php echo htmlspecialchars(strtolower($student['phone'] ?? '')); ?>">
                            <form method="POST" class="requirements-form">
                                <input type="hidden" name="action" value="save_requirements">
                                <input type="hidden" name="student_key" value="<?php echo htmlspecialchars($studentKey); ?>">
                                
                                <div class="student-header d-flex justify-content-between align-items-center">
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="avatar">
                                            <?php echo strtoupper(substr($student['full_name'] ?? 'S', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($student['full_name'] ?? 'Unknown Student'); ?></h4>
                                            <div class="text-muted">
                                                <?php if (!empty($student['registration_number'])): ?>
                                                    <span class="me-3"><i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($student['registration_number']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($student['index_number'])): ?>
                                                    <span class="me-3"><i class="fas fa-hashtag me-1"></i> <?php echo htmlspecialchars($student['index_number']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($student['phone'])): ?>
                                                    <span class="me-3"><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($student['phone']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($student['program'])): ?>
                                                    <span><i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($student['program']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-2">
                                            <strong class="text-dark fs-5"><?php echo $clearedCount; ?> / <?php echo $totalItems; ?> Cleared</strong>
                                        </div>
                                        <div class="progress mb-1" style="width: 220px;">
                                            <div class="progress-bar" style="width: <?php echo $progressPercent; ?>%"></div>
                                        </div>
                                        <small class="text-muted fw-semibold"><?php echo $progressPercent; ?>% Complete</small>
                                    </div>
                                </div>
                                
                                <div class="requirements-grid">
                                    <?php foreach ($requiredItems as $itemKey => $itemName): 
                                        $isCleared = in_array($itemKey, $studentReq['cleared_items'] ?? []);
                                    ?>
                                    <div class="requirement-item <?php echo $isCleared ? 'cleared' : ''; ?>">
                                        <input class="form-check-input" type="checkbox" name="cleared_items[]" value="<?php echo htmlspecialchars($itemKey); ?>" id="req_<?php echo $studentKey; ?>_<?php echo $itemKey; ?>" <?php echo $isCleared ? 'checked' : ''; ?>>
                                        <label class="form-check-label mb-0 fw-medium" for="req_<?php echo $studentKey; ?>_<?php echo $itemKey; ?>">
                                            <?php echo htmlspecialchars($itemName); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="progress-section d-flex justify-content-end gap-3">
                                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" onclick="window.print()">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                    <button type="submit" class="btn btn-primary save-btn d-flex align-items-center gap-2">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <section id="applications" class="content-section">
                    <div class="section-heading">
                        <div>
                            <h2><i class="fas fa-file-alt me-2 text-primary"></i>Recent Applications</h2>
                            <span class="text-muted">Latest admissions records</span>
                        </div>
                    </div>
                    <div class="table-responsive bg-white rounded-3 shadow-lg p-4">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr><th>Application</th><th>Applicant</th><th>Program</th><th>Year</th><th>Status</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentApplications)): ?>
                                    <tr><td colspan="6" class="text-muted py-5 text-center">No admissions records found yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentApplications as $application): ?>
                                        <tr>
                                            <td><span class="fw-semibold"><?php echo htmlspecialchars($application['application_number'] ?? ''); ?></span></td>
                                            <td><?php echo htmlspecialchars($application['applicant_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($application['program'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($application['academic_year'] ?? ''); ?></td>
                                            <td><span class="badge bg-info text-white"><?php echo htmlspecialchars($application['admission_status'] ?? ''); ?></span></td>
                                            <td><?php echo htmlspecialchars($application['application_date'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                
                <section id="activity" class="content-section">
                    <div class="section-heading">
                        <div>
                            <h2><i class="fas fa-history me-2 text-primary"></i>Recent Admissions Activity</h2>
                            <span class="text-muted">Audit trail from staff logs</span>
                        </div>
                    </div>
                    <div class="bg-white rounded-3 shadow-lg p-4">
                        <?php if (empty($recentActivity)): ?>
                            <p class="text-muted py-5 text-center">No admissions activity logged yet.</p>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="timeline-item d-flex gap-3 align-items-start">
                                    <div class="timeline-icon"><i class="fas fa-check"></i></div>
                                    <div class="flex-grow-1">
                                        <strong class="text-dark d-block"><?php echo htmlspecialchars($activity['activity'] ?? ''); ?></strong>
                                        <small class="text-muted"><?php echo htmlspecialchars($activity['created_at'] ?? ''); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="add_student">
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" required placeholder="Enter full name">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Registration Number</label>
                                <input type="text" class="form-control" name="registration_number" placeholder="Enter registration number">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Index Number</label>
                                <input type="text" class="form-control" name="index_number" placeholder="Enter index number">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Phone Number *</label>
                                <input type="text" class="form-control" name="phone" required placeholder="Enter phone number">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" placeholder="Enter email address">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Program *</label>
                                <select class="form-select" name="program" required>
                                    <option value="">Select program</option>
                                    <option value="Certificate Nursing">Certificate Nursing</option>
                                    <option value="Certificate Midwifery">Certificate Midwifery</option>
                                    <option value="Diploma Nursing">Diploma Nursing</option>
                                    <option value="Diploma Midwifery">Diploma Midwifery</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Intake Year</label>
                                <input type="number" class="form-control" name="intake_year" value="<?php echo date('Y'); ?>" placeholder="Year">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer gap-2 p-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="fas fa-save"></i> Add Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar navigation
            document.querySelectorAll('.sidebar-menu .nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.sidebar-menu .nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    const target = this.getAttribute('href');
                    document.querySelectorAll('.content-section').forEach(section => {
                        section.style.display = 'none';
                    });
                    document.querySelector(target).style.display = 'block';
                });
            });
            
            const searchInput = document.getElementById('studentSearch');
            const filterName = document.getElementById('filterName');
            const filterAdmission = document.getElementById('filterAdmission');
            const filterPhone = document.getElementById('filterPhone');
            const clearBtn = document.getElementById('clearFilters');
            const studentCards = document.querySelectorAll('.student-card');
            
            // Populate filter options
            const names = new Set();
            const admissions = new Set();
            const phones = new Set();
            
            studentCards.forEach(card => {
                const name = card.dataset.name;
                const admission = card.dataset.admission;
                const phone = card.dataset.phone;
                
                if (name) names.add(name);
                if (admission) admissions.add(admission);
                if (phone) phones.add(phone);
            });
            
            // Add options to filters
            names.forEach(name => {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name.charAt(0).toUpperCase() + name.slice(1);
                filterName.appendChild(option);
            });
            
            admissions.forEach(admission => {
                const option = document.createElement('option');
                option.value = admission;
                option.textContent = admission;
                filterAdmission.appendChild(option);
            });
            
            phones.forEach(phone => {
                const option = document.createElement('option');
                option.value = phone;
                option.textContent = phone;
                filterPhone.appendChild(option);
            });
            
            // Filter function
            function filterStudents() {
                const searchTerm = searchInput.value.toLowerCase();
                const nameVal = filterName.value;
                const admissionVal = filterAdmission.value;
                const phoneVal = filterPhone.value;
                
                studentCards.forEach(card => {
                    const cardName = card.dataset.name;
                    const cardAdmission = card.dataset.admission;
                    const cardPhone = card.dataset.phone;
                    
                    let show = true;
                    
                    if (searchTerm) {
                        const haystack = (cardName + ' ' + cardAdmission + ' ' + cardPhone).toLowerCase();
                        if (!haystack.includes(searchTerm)) {
                            show = false;
                        }
                    }
                    
                    if (nameVal && cardName !== nameVal) show = false;
                    if (admissionVal && cardAdmission !== admissionVal) show = false;
                    if (phoneVal && cardPhone !== phoneVal) show = false;
                    
                    card.style.display = show ? 'block' : 'none';
                });
            }
            
            // Event listeners
            searchInput.addEventListener('input', filterStudents);
            filterName.addEventListener('change', filterStudents);
            filterAdmission.addEventListener('change', filterStudents);
            filterPhone.addEventListener('change', filterStudents);
            
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterName.value = '';
                filterAdmission.value = '';
                filterPhone.value = '';
                filterStudents();
            });
            
            // Update cleared status visually when checkbox changes
            document.querySelectorAll('.requirement-item input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const item = this.closest('.requirement-item');
                    if (this.checked) {
                        item.classList.add('cleared');
                    } else {
                        item.classList.remove('cleared');
                    }
                });
            });
            
            // Auto-hide notifications
            setTimeout(() => {
                const alerts = document.querySelectorAll('.notification');
                alerts.forEach(alert => {
                    alert.style.animation = 'slideIn 0.5s ease reverse';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>
<?php
function tableList($conn) {
    if (!$conn) return [];
    $result = $conn->query('SHOW TABLES');
    $tables = [];
    if (!$result) return $tables;
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    return $tables;
}

function scalar($conn, $sql) {
    if (!$conn) return 0;
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return (int) ($row['c'] ?? 0);
}

function rows($conn, $sql) {
    if (!$conn) return [];
    $result = $conn->query($sql);
    if (!$result) return [];
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
