<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../views/student_data_loader.php';

$ctx = bootstrapStaffDashboard(['admissions', 'director']);
$students_conn = $ctx['students'];
$staff_conn = $ctx['staff'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'Admissions User';

$loader = new StudentDataLoader();
$all_students = $loader->loadAllStudents();

// Define the required items list
$required_items = [
    'surgical_gloves' => 'Surgical Gloves',
    'examination_gloves' => 'Examination Gloves',
    'photocopying_ream' => 'Photocopying Ream',
    'ruled_paper_reams' => 'Ruled Paper Reams',
    'omo' => 'Omo',
    'toilet_papers' => 'Toilet Papers',
    'compound_brooms' => 'Compound Brooms',
    'soft_brooms' => 'Soft Brooms',
    'rake' => 'Rake',
    'cobweb_brush' => 'Cobweb Brush',
    'scrubbing_brush' => 'Scrubbing Brush',
    'squeezer' => 'Squeezer',
    'toilet_brush' => 'Toilet Brush',
    'jik' => 'JIK',
    'vim' => 'Vim',
    'mops' => 'Mops',
    'sanitizer' => 'Sanitizer',
    'liquid_soap' => 'Liquid Soap',
    'face_masks' => 'Face Masks',
    'heavy_duty_gloves' => 'Heavy Duty Gloves',
];

// Load or create requirements tracking storage
$requirements_file = __DIR__ . '/../data/student_requirements.json';
$requirements_data = [];
if (file_exists($requirements_file)) {
    $requirements_data = json_decode(file_get_contents($requirements_file), true) ?? [];
}

// Handle saving requirements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_requirements') {
        $student_key = $_POST['student_key'] ?? '';
        $cleared_items = $_POST['cleared_items'] ?? [];
        
        $requirements_data[$student_key] = [
            'student_key' => $student_key,
            'cleared_items' => $cleared_items,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $user_name,
        ];
        
        if (!is_dir(dirname($requirements_file))) {
            mkdir(dirname($requirements_file), 0755, true);
        }
        
        file_put_contents($requirements_file, json_encode($requirements_data, JSON_PRETTY_PRINT));
        $_SESSION['success'] = 'Student requirements saved successfully!';
        header('Location: director-admissions.php');
        exit;
    }
    
    if ($_POST['action'] === 'add_student') {
        $new_students_file = __DIR__ . '/../data/new_students.json';
        $new_students = [];
        if (file_exists($new_students_file)) {
            $new_students = json_decode(file_get_contents($new_students_file), true) ?? [];
        }
        
        $new_student = [
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
            'created_by' => $user_name,
            'source_file' => 'manual_add'
        ];
        
        $new_students[] = $new_student;
        
        if (!is_dir(dirname($new_students_file))) {
            mkdir(dirname($new_students_file), 0755, true);
        }
        
        file_put_contents($new_students_file, json_encode($new_students, JSON_PRETTY_PRINT));
        $_SESSION['success'] = 'New student added successfully!';
        header('Location: director-admissions.php');
        exit;
    }
}

// Load manually added students
$new_students_file = __DIR__ . '/../data/new_students.json';
if (file_exists($new_students_file)) {
    $manual_students = json_decode(file_get_contents($new_students_file), true) ?? [];
    foreach ($manual_students as $student) {
        $all_students[] = $student;
    }
}

// Dashboard statistics with fallbacks
$total_applications = 0;
$pending_applications = 8;
$admitted_students = 0;
$enrolled_students = 150;
$active_students = 0;

try {
    if ($students_conn) {
        $result = $students_conn->query("SELECT COUNT(*) as cnt FROM students WHERE status = 'Active'");
        if ($result) {
            $active_students = $result->fetch_assoc()['cnt'] ?? 0;
        }
    }
} catch (Exception $e) {}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Director Admissions Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="dashboard-professional.css">
    <link rel="stylesheet" href="dashboard-mobile.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <style>
        :root {
            --isnm-blue: #1e3a8a;
            --isnm-light-blue: #3b82f6;
            --isnm-green: #059669;
            --isnm-gold: #d97706;
            --isnm-dark-green: #0f4c3a;
        }
        
        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 14px;
            padding: 0 0 20px 0;
        }
        
        .requirement-item {
            padding: 14px 18px;
            border-radius: 12px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }
        
        .requirement-item:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        .requirement-item.cleared {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-color: #6ee7b7;
        }
        
        .requirement-item input[type="checkbox"] {
            width: 24px;
            height: 24px;
            cursor: pointer;
            border-radius: 8px;
            border: 2px solid #cbd5e1;
        }
        
        .student-header {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .student-header .avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--isnm-blue), var(--isnm-light-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(30, 58, 138, 0.2);
        }
        
        .search-bar {
            background: linear-gradient(135deg, var(--isnm-blue) 0%, var(--isnm-dark-green) 100%);
            padding: 24px;
            border-radius: 18px;
            margin-bottom: 24px;
            box-shadow: 0 8px 30px rgba(30, 58, 138, 0.25);
        }
        
        .search-bar .form-control,
        .search-bar .form-select {
            border-radius: 12px;
            border: none;
            padding: 12px 18px;
            transition: all 0.3s ease;
        }
        
        .search-bar .form-control:focus,
        .search-bar .form-select:focus {
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
        }
        
        .student-card {
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            background: white;
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }
        
        .student-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--isnm-blue) 0%, var(--isnm-light-blue) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1a3578 0%, #2563eb 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--isnm-green) 0%, #10b981 100%);
            border: none;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--isnm-green), #10b981);
        }
        
        @media print {
            .dashboard-sidebar,
            .dashboard-header,
            .search-bar,
            .btn,
            nav {
                display: none !important;
            }
            
            .dashboard-main,
            .dashboard-container {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }
            
            .student-card {
                break-inside: avoid;
                page-break-inside: avoid;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-4" style="z-index: 9999; animation: slideIn 0.5s ease;">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>ISNM Management</h4>
                <small><?php echo htmlspecialchars($user_name); ?></small>
                <span class="badge bg-info">Director Admissions</span>
            </div>
            
            <nav class="sidebar-menu">
                <a href="#overview" class="nav-link active">
                    <i class="fas fa-chart-pie"></i> Overview
                </a>
                <a href="#requirements" class="nav-link">
                    <i class="fas fa-clipboard-check"></i> Requirements Portal
                </a>
                <a href="#applications" class="nav-link">
                    <i class="fas fa-file-alt"></i> Applications
                </a>
                <a href="#activity" class="nav-link">
                    <i class="fas fa-history"></i> Activity Log
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
                    <h1>Director Admissions Dashboard</h1>
                    <p>Admissions & Requirements Management - Iganga School of Nursing and Midwifery</p>
                </div>
                <div class="header-right d-flex gap-3 align-items-center">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <a href="../store_request.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                    <a href="../news.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-newspaper me-1"></i>News</a>
                    <a href="../student-directory.php" class="btn btn-sm btn-outline-info ms-2"><i class="fas fa-address-book me-1"></i>Directory</a>
                    <a href="../index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-home"></i></a>
                    <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-user-plus"></i> Add Student
                    </button>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Overview Section -->
                <section id="overview" class="content-section">
                    <h2>Admissions Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_applications ?: 0); ?></h3>
                                <p>Total Applications</p>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($pending_applications); ?></h3>
                                <p>Pending Review</p>
                            </div>
                        </div>
                        
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($admitted_students); ?></h3>
                                <p>Admitted Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($enrolled_students); ?></h3>
                                <p>Enrolled Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($active_students); ?></h3>
                                <p>Active Students</p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Requirements Portal Section -->
                <section id="requirements" class="content-section">
                    <h2><i class="fas fa-clipboard-check me-2"></i>Requirements Portal</h2>
                    <p class="text-muted mb-4">Track and manage student admission requirements</p>
                    
                    <!-- Search and Filter Bar -->
                    <div class="search-bar">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                    <input type="text" id="studentSearch" class="form-control" placeholder="Search students by name, admission number, or phone...">
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
                    
                    <!-- Students List -->
                    <div id="studentsList">
                        <?php foreach ($all_students as $index => $student): 
                            $student_key = md5(($student['index_number'] ?? $student['registration_number'] ?? $student['full_name'] ?? $index));
                            $student_req = $requirements_data[$student_key] ?? ['cleared_items' => []];
                            $cleared_count = count($student_req['cleared_items'] ?? []);
                            $total_count = count($required_items);
                            $progress_percent = $total_count > 0 ? round(($cleared_count / $total_count) * 100) : 0;
                        ?>
                        <div class="student-card" data-name="<?php echo htmlspecialchars(strtolower($student['full_name'] ?? '')); ?>" data-admission="<?php echo htmlspecialchars(strtolower($student['registration_number'] ?? $student['index_number'] ?? '')); ?>" data-phone="<?php echo htmlspecialchars(strtolower($student['phone'] ?? '')); ?>">
                            <form method="POST" class="requirements-form">
                                <input type="hidden" name="action" value="save_requirements">
                                <input type="hidden" name="student_key" value="<?php echo htmlspecialchars($student_key); ?>">
                                
                                <div class="student-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div class="d-flex gap-4 align-items-center">
                                        <div class="avatar">
                                            <?php echo strtoupper(substr($student['full_name'] ?? 'S', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h4 class="mb-1 fw-bold"><?php echo htmlspecialchars($student['full_name'] ?? 'Unknown Student'); ?></h4>
                                            <div class="text-muted small">
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
                                            <strong class="fs-5"><?php echo $cleared_count; ?> / <?php echo $total_count; ?> Cleared</strong>
                                        </div>
                                        <div class="progress" style="width: 240px;">
                                            <div class="progress-bar" style="width: <?php echo $progress_percent; ?>%"></div>
                                        </div>
                                        <small class="text-muted fw-semibold"><?php echo $progress_percent; ?>% Complete</small>
                                    </div>
                                </div>
                                
                                <div class="p-4">
                                    <div class="requirements-grid">
                                        <?php foreach ($required_items as $item_key => $item_name): 
                                            $is_cleared = in_array($item_key, $student_req['cleared_items'] ?? []);
                                        ?>
                                        <div class="requirement-item <?php echo $is_cleared ? 'cleared' : ''; ?>">
                                            <input class="form-check-input" type="checkbox" name="cleared_items[]" value="<?php echo htmlspecialchars($item_key); ?>" id="req_<?php echo $student_key; ?>_<?php echo $item_key; ?>" <?php echo $is_cleared ? 'checked' : ''; ?>>
                                            <label class="form-check-label mb-0 fw-medium" for="req_<?php echo $student_key; ?>_<?php echo $item_key; ?>">
                                                <?php echo htmlspecialchars($item_name); ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end gap-3 p-4 pt-0">
                                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" onclick="window.print()">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <!-- Applications Section -->
                <section id="applications" class="content-section">
                    <h2><i class="fas fa-file-alt me-2"></i>Recent Applications</h2>
                    <p class="text-muted mb-4">Latest admissions records</p>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Application #</th>
                                    <th>Applicant</th>
                                    <th>Program</th>
                                    <th>Intake Year</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-muted py-5 text-center">No admissions records available in database.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                
                <!-- Activity Section -->
                <section id="activity" class="content-section">
                    <h2><i class="fas fa-history me-2"></i>Recent Admissions Activity</h2>
                    <p class="text-muted mb-4">Audit trail from staff logs</p>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="timeline-icon" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--isnm-blue), var(--isnm-light-blue)); display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong class="d-block">Dashboard accessed</strong>
                                    <small class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addStudentModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Add New Student
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="add_student">
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" required placeholder="Enter student's full name">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Registration Number</label>
                                <input type="text" class="form-control" name="registration_number" placeholder="Enter registration number">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Index Number</label>
                                <input type="text" class="form-control" name="index_number" placeholder="Enter index number">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number *</label>
                                <input type="text" class="form-control" name="phone" required placeholder="Enter phone number">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control" name="email" placeholder="Enter email address">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Program *</label>
                                <select class="form-select" name="program" required>
                                    <option value="">Select program</option>
                                    <option value="Certificate in Nursing">Certificate in Nursing</option>
                                    <option value="Certificate in Midwifery">Certificate in Midwifery</option>
                                    <option value="Diploma in Nursing">Diploma in Nursing</option>
                                    <option value="Diploma in Midwifery">Diploma in Midwifery</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Intake Year</label>
                                <input type="number" class="form-control" name="intake_year" value="<?php echo date('Y'); ?>" placeholder="Year">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date of Birth</label>
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
        });
    </script>
</body>
</html>
