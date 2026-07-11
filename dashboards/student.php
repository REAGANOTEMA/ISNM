<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';
require_once __DIR__ . '/../includes/financial_functions.php';
require_once __DIR__ . '/../includes/auto_deduction_processor.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/ISNM/');
    session_start();
}
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['type'] ?? '') !== 'student') {
    header('Location: ../student-login.php?error=student_access_required');
    exit;
}
// Redirect to the unified student portal
header('Location: student-portal.php');
exit;

$staffDb = getStaffConnection();
$studentsDb = getStudentsConnection();
$websiteDb = getWebsiteConnection();
$auth_service = new AuthenticationService();
$user = $auth_service->getCurrentUser();
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? 'Student';
$user_role = $user['role'] ?? '';

$profileImageUrl = '../images/username.png';
$profileSettingsFile = __DIR__ . '/../includes/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    include_once $profileSettingsFile;
    if (function_exists('getStaffProfileImageUrl')) {
        $url = getStaffProfileImageUrl($user_id);
        if ($url) $profileImageUrl = $url;
    }
}

if (!function_exists('tableExists')) {
    function tableExists($conn, $tableName) {
        $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
        return $result && $result->num_rows > 0;
    }
}

        // Get student information
        $student_id = (int)($_SESSION['user_id'] ?? 0);
        $student_result = $studentsDb->query("SELECT * FROM students WHERE id = $student_id LIMIT 1");
        $student_info = ($student_result) ? $student_result->fetch_assoc() : [];

        // Get student academic profile
        $academic_result = $studentsDb->query("SELECT * FROM student_academic_records WHERE student_id = " . ($student_info['id'] ?? 0) . " ORDER BY semester DESC, academic_year DESC LIMIT 1");
        $academic_profile = ($academic_result) ? $academic_result->fetch_assoc() : [];

        // Get examination records (grades)
        $exam_result = $studentsDb->query("SELECT *, 
                                                    NULL as current_stage, 
                                                    NULL as registrar_status, 
                                                    NULL as principal_status 
                                            FROM student_academic_records 
                                            WHERE student_id = " . ($student_info['id'] ?? 0) . " 
                                            ORDER BY created_at DESC LIMIT 20");
        $examination_records = ($exam_result) ? $exam_result->fetch_all(MYSQLI_ASSOC) : [];

// Get fee/invoice summary information
if (tableExists($studentsDb, 'student_fee_accounts')) {
    $fee_result = $studentsDb->query("SELECT * FROM student_fee_accounts WHERE student_id = " . ($student_info['id'] ?? 0) . " ORDER BY academic_year DESC, semester DESC");
    $fee_account = ($fee_result) ? $fee_result->fetch_all(MYSQLI_ASSOC) : [];
    $invoice_summary = [
        'total_fees' => $fee_account[0]['total_fees'] ?? 0,
        'amount_paid' => $fee_account[0]['amount_paid'] ?? 0,
        'balance' => $fee_account[0]['balance'] ?? 0,
    ];
    $pending_invoices = [];
    $next_invoice = $fee_account[0] ?? [];
} else {
    $fee_account = [];
    $inv_result = $studentsDb->query("SELECT COALESCE(SUM(total_amount), 0) as total_fees, COALESCE(SUM(amount_paid), 0) as amount_paid, COALESCE(SUM(balance), 0) as balance FROM student_invoices WHERE student_id = " . ($student_info['id'] ?? 0));
    $invoice_summary = ($inv_result) ? $inv_result->fetch_assoc() : ['total_fees' => 0, 'amount_paid' => 0, 'balance' => 0];
    $pending_result = $studentsDb->query("SELECT id, invoice_number, academic_year, semester, total_amount, amount_paid, balance, due_date FROM student_invoices WHERE student_id = " . ($student_info['id'] ?? 0) . " AND status IN ('pending', 'partial', 'overdue') ORDER BY due_date ASC");
    $pending_invoices = ($pending_result) ? $pending_result->fetch_all(MYSQLI_ASSOC) : [];
    $next_result = $studentsDb->query("SELECT id, invoice_number, academic_year, semester, total_amount, amount_paid, balance, due_date FROM student_invoices WHERE student_id = " . ($student_info['id'] ?? 0) . " AND status IN ('pending', 'partial', 'overdue') ORDER BY due_date ASC LIMIT 1");
    $next_invoice = ($next_result) ? $next_result->fetch_assoc() : [];
}

// Get recent staff messages
$messages = [];
if (tableExists($studentsDb, 'messages')) {
    $msg_result = $studentsDb->query("SELECT * FROM messages WHERE receiver_id = " . ($student_info['id'] ?? 0) . " ORDER BY sent_date DESC LIMIT 5");
    $messages = ($msg_result) ? $msg_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get payment history
if (tableExists($studentsDb, 'payments')) {
    $pay_result = $studentsDb->query("SELECT * FROM payments WHERE student_id = " . ($student_info['id'] ?? 0) . " ORDER BY payment_date DESC LIMIT 5");
    $payment_history = ($pay_result) ? $pay_result->fetch_all(MYSQLI_ASSOC) : [];
} elseif (tableExists($studentsDb, 'fee_payments')) {
    $pay_result = $studentsDb->query("SELECT fp.* FROM fee_payments fp JOIN student_fee_accounts sfa ON fp.fee_account_id = sfa.id WHERE sfa.student_id = " . ($student_info['id'] ?? 0) . " ORDER BY fp.payment_date DESC LIMIT 5");
    $payment_history = ($pay_result) ? $pay_result->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $payment_history = [];
}

// Get live announcements for students
$announcements = [];
if (tableExists($studentsDb, 'announcements')) {
    $ann_result = $studentsDb->query("SELECT a.*, COALESCE(s.full_name, CONCAT(s.first_name, ' ', s.surname)) AS posted_by_name FROM announcements a LEFT JOIN " . STAFF_DB_NAME . ".staff s ON a.posted_by = s.id WHERE a.is_active = 1 AND (a.target_audience = 'all' OR a.target_audience = 'students') AND (a.expires_at IS NULL OR a.expires_at >= CURDATE()) ORDER BY a.priority DESC, a.created_at DESC LIMIT 5");
    $announcements = ($ann_result) ? $ann_result->fetch_all(MYSQLI_ASSOC) : [];
} elseif (tableExists($studentsDb, 'student_notifications')) {
    $ann_result = $studentsDb->query("SELECT id, title AS title, message AS content, type AS announcement_type, priority, created_at AS posted_date, 'staff' AS posted_by_name, 'students' AS target_audience FROM student_notifications WHERE student_id = " . ($student_info['id'] ?? 0) . " ORDER BY created_at DESC LIMIT 5");
    $announcements = ($ann_result) ? $ann_result->fetch_all(MYSQLI_ASSOC) : [];
} elseif (tableExists($studentsDb, 'messages')) {
    $ann_result = $studentsDb->query("SELECT id, subject AS title, message AS content, message_type AS announcement_type, priority, sent_date AS posted_date, 'staff' AS posted_by_name, 'students' AS target_audience FROM messages WHERE receiver_id = " . ($student_info['id'] ?? 0) . " AND message_type = 'announcement' ORDER BY sent_date DESC LIMIT 5");
    $announcements = ($ann_result) ? $ann_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get academic records for transcript
$acad_result = $studentsDb->query("SELECT * FROM student_academic_profiles WHERE student_id = " . ($student_info['id'] ?? 0));
$academic_records = ($acad_result) ? $acad_result->fetch_all(MYSQLI_ASSOC) : [];

// Get current timetable for the student
$timetable = [];
if (!empty($student_info['student_id']) && !empty($student_info['current_semester'])) {
    $timetable = getStudentTimetable($student_info['student_id'], $student_info['current_semester']);
}

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024;
        if (!in_array($_FILES['profile_photo']['type'], $allowed)) {
            $_SESSION['error_msg'] = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP.';
        } elseif ($_FILES['profile_photo']['size'] > $max_size) {
            $_SESSION['error_msg'] = 'File too large. Maximum is 5MB.';
        } else {
            $upload_dir = '../studentUploads/profile_images/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $filename = 'student_' . $student_id . '_' . time() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $filepath)) {
                $stmt = $studentsDb->prepare("UPDATE students SET profile_picture = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("si", $filename, $student_id);
                    if ($stmt->execute()) {
                        $_SESSION['success_msg'] = 'Profile photo updated successfully!';
                    }
                    $stmt->close();
                }
            }
        }
    } else {
        $_SESSION['error_msg'] = 'Please select a file to upload.';
    }
    header('Location: student.php#profile');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
@media(max-width:768px){.main{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
    <!-- Include Responsive Navigation -->
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Main Content Area -->
        <div class="dashboard-main">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Student Dashboard</h1>
                    <p>Welcome back, <?php echo $student_info['first_name']; ?>! Here's your academic overview</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <div class="user-menu">
                        <img src="<?= $profileImageUrl ?>" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo $student_info['first_name'] . ' ' . $student_info['surname']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Student Overview -->
                <section id="overview" class="content-section">
                    <h2>Academic Overview</h2>
                    <div class="student-info-cards">
                                <div class="info-card">
                                    <div class="info-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div class="info-content">
                                        <h4>Student Information</h4>
                                        <p><strong>ID:</strong> <?php echo $student_info['id']; ?></p>
                                        <p><strong>Program:</strong> <?php echo $student_info['program']; ?></p>
                                        <p><strong>Level:</strong> <?php echo $student_info['level']; ?></p>
                                        <p><strong>Year:</strong> <?php echo $student_info['current_year']; ?></p>
                                        <p><strong>Semester:</strong> <?php echo $student_info['current_semester']; ?></p>
                                    </div>
                                </div>
                            <div class="info-content">
                                <h4>Student Information</h4>
                                <p><strong>ID:</strong> <?php echo $student_info['id']; ?></p>
                                <p><strong>Program:</strong> <?php echo $student_info['program']; ?></p>
                                <p><strong>Level:</strong> <?php echo $student_info['level']; ?></p>
                                <p><strong>Year:</strong> <?php echo $student_info['current_year']; ?></p>
                                <p><strong>Semester:</strong> <?php echo $student_info['current_semester']; ?></p>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="info-content">
                                <h4>Academic Performance</h4>
                                <?php if (!empty($academic_records)): ?>
                                <p><strong>Current GPA:</strong> <?php echo $academic_records[0]['gpa'] ?? 'N/A'; ?></p>
                                <p><strong>Last Semester:</strong> <?php echo $academic_records[0]['semester']; ?></p>
                                <p><strong>Class Position:</strong> <?php echo $academic_records[0]['class_position'] ?? 'N/A'; ?>/<?php echo $academic_records[0]['total_students'] ?? 'N/A'; ?></p>
                                <p><strong>Attendance:</strong> <?php echo $academic_records[0]['attendance_percentage'] ?? 'N/A'; ?>%</p>
                                <?php else: ?>
                                <p>No academic records available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="info-content">
                                <h4>Financial Status</h4>
                                <?php if (!empty($fee_account)): ?>
                                <p><strong>Total Fees:</strong> UGX <?php echo number_format($fee_account[0]['total_fees']); ?></p>
                                <p><strong>Paid:</strong> UGX <?php echo number_format($fee_account[0]['amount_paid']); ?></p>
                                <p><strong>Balance:</strong> UGX <?php echo number_format($fee_account[0]['balance']); ?></p>
                                <p><strong>Status:</strong> <span class="status-badge <?php echo $fee_account[0]['status']; ?>"><?php echo ucfirst($fee_account[0]['status']); ?></span></p>
                                <?php else: ?>
                                <p>No fee information available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <a href="../print_transcript.php" class="action-btn" style="text-decoration:none;display:flex;flex-direction:column;align-items:center;gap:6px;padding:15px;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;color:#166534;transition:all 0.2s" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                            <i class="fas fa-file-alt" style="font-size:24px"></i>
                            <span style="font-size:13px;font-weight:600">View Transcript</span>
                        </a>
                        <button class="action-btn" onclick="openModal('payFees')">
                            <i class="fas fa-credit-card"></i>
                            <span>Pay Fees</span>
                        </button>
                        <button class="action-btn" onclick="openModal('downloadDocuments')">
                            <i class="fas fa-download"></i>
                            <span>Download Documents</span>
                        </button>
                        <button class="action-btn" onclick="openModal('sendMessage')">
                            <i class="fas fa-envelope"></i>
                            <span>Send Message</span>
                        </button>
                    </div>
                </section>
                
                <!-- Profile Section -->
                <section id="profile" class="content-section">
                    <h2>My Profile</h2>
                    <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success_msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php unset($_SESSION['success_msg']); endif; ?>
                    <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error_msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php unset($_SESSION['error_msg']); endif; ?>
                    <div class="profile-section">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?php
                                $photo = !empty($student_info['profile_picture']) ? '../studentUploads/profile_images/' . htmlspecialchars($student_info['profile_picture']) : '../images/username.png';
                                ?>
                                <img src="<?= $photo ?>" alt="Profile" class="avatar-img" id="studentProfileImage">
                                <label for="profilePhotoInput" class="btn btn-sm btn-primary" style="cursor:pointer">
                                    <i class="fas fa-camera"></i> Change Photo
                                </label>
                                <form id="profilePhotoForm" method="POST" enctype="multipart/form-data" style="display:none">
                                    <input type="hidden" name="action" value="upload_photo">
                                    <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/*" onchange="document.getElementById('profilePhotoForm').submit()">
                                </form>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo $student_info['first_name'] . ' ' . $student_info['surname'] . ' ' . $student_info['other_name']; ?></h3>
                                <p><strong>Student ID:</strong> <?php echo $student_info['student_id']; ?></p>
                                <p><strong>Program:</strong> <?php echo $student_info['program']; ?></p>
                                <p><strong>Email:</strong> <?php echo $student_info['email']; ?></p>
                                <p><strong>Phone:</strong> <?php echo $student_info['phone']; ?></p>
                            </div>
                            <div class="profile-actions">
                                <button class="btn btn-outline-primary" onclick="openCommunicationCenter()">
                                    <i class="fas fa-edit"></i> Request Profile Update
                                </button>
                                <button class="btn btn-outline-secondary" onclick="openCommunicationCenter()">
                                    <i class="fas fa-lock"></i> Request Password Help
                                </button>
                                <button class="btn btn-outline-info" onclick="printProfile()">
                                    <i class="fas fa-print"></i> Print Profile
                                </button>
                                <button class="btn btn-outline-success" onclick="openModal('downloadDocuments')">
                                    <i class="fas fa-download"></i> Download Documents
                                </button>
                            </div>
                        </div>
                        
                        <div class="profile-details">
                            <div class="detail-section">
                                <h4>Personal Information</h4>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span>Date of Birth:</span>
                                        <strong><?php echo date('F j, Y', strtotime($student_info['date_of_birth'])); ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Gender:</span>
                                        <strong><?php echo $student_info['gender']; ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Nationality:</span>
                                        <strong><?php echo $student_info['nationality']; ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Address:</span>
                                        <strong><?php echo $student_info['address']; ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h4>Academic Information</h4>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span>Enrollment Date:</span>
                                        <strong><?php echo date('F j, Y', strtotime($student_info['enrollment_date'])); ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Current Year:</span>
                                        <strong>Year <?php echo $student_info['current_year']; ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Current Semester:</span>
                                        <strong>Semester <?php echo $student_info['current_semester']; ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Expected Graduation:</span>
                                        <strong><?php echo $student_info['expected_graduation_date'] ? date('F j, Y', strtotime($student_info['expected_graduation_date'])) : 'TBD'; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Academics Section -->
                <section id="academics" class="content-section">
                    <h2>Academic Records</h2>
                    <div class="academic-tabs">
                        <ul class="nav nav-tabs" id="academicTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab">
                                    <i class="fas fa-chart-bar"></i> Results
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="transcript-tab" data-bs-toggle="tab" data-bs-target="#transcript" type="button" role="tab">
                                    <i class="fas fa-file-alt"></i> Transcript
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab">
                                    <i class="fas fa-calendar-check"></i> Attendance
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="courses-tab" data-bs-toggle="tab" data-bs-target="#courses" type="button" role="tab">
                                    <i class="fas fa-book"></i> Courses
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="academicTabContent">
                            <div class="tab-pane fade show active" id="results" role="tabpanel">
                                <div class="results-table">
                                    <h3>Course Grades & Results</h3>
                                    <div class="grade-summary-cards">
                                        <div class="summary-card">
                                            <div class="summary-icon">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                            <div class="summary-content">
                                                <h4>Current GPA</h4>
                                                <h3><?php echo number_format($academic_profile['gpa'] ?? 0, 2); ?></h3>
                                                <p><?php echo $academic_profile['academic_status'] ?? 'Good Standing'; ?></p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div class="summary-content">
                                                <h4>Total Courses</h4>
                                                <h3><?php echo count($examination_records); ?></h3>
                                                <p>Completed</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <div class="summary-content">
                                                <h4>Passed Courses</h4>
                                                <h3>
                                                    <?php
                                                    $passed = count(array_filter($examination_records, fn($r) => in_array($r['grade'] ?? '', ['A', 'B', 'C', 'D'])));
                                                    echo $passed;
                                                    ?>
                                                </h3>
                                                <p>Out of <?php echo count($examination_records); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Course Code</th>
                                                    <th>Course Name</th>
                                                    <th>CA Marks</th>
                                                    <th>Exam Marks</th>
                                                    <th>Total</th>
                                                    <th>Grade</th>
                                                    <th>Publication Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($examination_records as $record): ?>
                                                <tr>
                                                    <td><?php echo $record['course_code']; ?></td>
                                                    <td><?php echo $record['course_name']; ?></td>
                                                    <td><?php echo $record['continuous_assessment_marks'] ?? 0; ?></td>
                                                    <td><?php echo $record['final_exam_marks'] ?? 0; ?></td>
                                                    <td><?php echo $record['total_marks_calculated'] ?? 0; ?></td>
                                                    <td><span class="grade-badge <?php echo strtolower($record['grade'] ?? ''); ?>"><?php echo $record['grade'] ?? 'N/A'; ?></span></td>
                                                    <td>
                                                        <?php if ($record['current_stage'] == 'Published'): ?>
                                                            <span class="status-badge published">Published</span>
                                                        <?php elseif ($record['current_stage'] == 'Rejected'): ?>
                                                            <span class="status-badge rejected">Rejected</span>
                                                        <?php else: ?>
                                                            <span class="status-badge pending">Pending Approval</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="transcript" role="tabpanel">
                                <div class="transcript-section">
                                    <h3>Academic Transcript</h3>
                                    <div class="transcript-preview">
                                        <div class="transcript-header">
                                            <img src="../images/school-logo.png" alt="ISNM Logo" class="transcript-logo">
                                            <div class="transcript-title">
                                                <h2>Iganga School of Nursing and Midwifery</h2>
                                                <p>Official Academic Transcript</p>
                                            </div>
                                        </div>
                                        <div class="transcript-student-info">
                                            <p><strong>Name:</strong> <?php echo $student_info['first_name'] . ' ' . $student_info['surname']; ?></p>
                                            <p><strong>Student ID:</strong> <?php echo $student_info['student_id']; ?></p>
                                            <p><strong>Program:</strong> <?php echo $student_info['program']; ?></p>
                                        </div>
                                        <div class="transcript-content">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Year</th>
                                                            <th>Semester</th>
                                                            <th>Courses</th>
                                                            <th>Credits</th>
                                                            <th>GPA</th>
                                                            <th>Remarks</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($academic_records as $record): ?>
                                                        <tr>
                                                            <td><?php echo $record['year']; ?></td>
                                                            <td><?php echo $record['semester']; ?></td>
                                                            <td><?php echo $record['courses'] ?? 'N/A'; ?></td>
                                                            <td><?php echo $record['credits'] ?? 'N/A'; ?></td>
                                                            <td><?php echo $record['gpa'] ?? 'N/A'; ?></td>
                                                            <td><?php echo $record['remarks'] ?? 'N/A'; ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="transcript-actions">
                                        <a href="../print_transcript.php?action=download&student_id=<?= (int)($student_info['id'] ?? 0) ?>" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <a href="../print_transcript.php?action=print&student_id=<?= (int)($student_info['id'] ?? 0) ?>" class="btn btn-info" target="_blank">
                                            <i class="fas fa-print"></i> Print
                                        </a>
                                        <a href="../print_transcript.php?student_id=<?= (int)($student_info['id'] ?? 0) ?>" class="btn btn-outline-success">
                                            <i class="fas fa-external-link-alt"></i> Full Portal
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="attendance" role="tabpanel">
                                <div class="attendance-section">
                                    <h3>Attendance Records</h3>
                                    <div class="attendance-chart">
                                        <div class="attendance-stat">
                                            <div class="attendance-circle">
                                                <span class="attendance-percentage"><?php echo $academic_records[0]['attendance_percentage'] ?? '0'; ?>%</span>
                                            </div>
                                            <p>Overall Attendance</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="courses" role="tabpanel">
                                <div class="courses-section">
                                    <h3>Current Courses</h3>
                                    <div class="courses-grid">
                                        <div class="course-card" style="animation: fadeInUp 0.6s ease-out 0.1s both;">
                                            <div class="course-header">
                                                <div class="course-icon">
                                                    <i class="fas fa-heartbeat"></i>
                                                </div>
                                                <div class="course-badge">Core</div>
                                            </div>
                                            <h4>Nursing Fundamentals</h4>
                                            <p>Core nursing principles and practices</p>
                                            <div class="course-details">
                                                <span><i class="fas fa-graduation-cap"></i> Credits: 4</span>
                                                <span><i class="fas fa-user-tie"></i> Dr. Sarah Johnson</span>
                                            </div>
                                            <div class="course-progress">
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 75%"></div>
                                                </div>
                                                <small>75% Complete</small>
                                            </div>
                                        </div>
                                        <div class="course-card" style="animation: fadeInUp 0.6s ease-out 0.2s both;">
                                            <div class="course-header">
                                                <div class="course-icon">
                                                    <i class="fas fa-user-md"></i>
                                                </div>
                                                <div class="course-badge">Core</div>
                                            </div>
                                            <h4>Anatomy & Physiology</h4>
                                            <p>Human body structure and functions</p>
                                            <div class="course-details">
                                                <span><i class="fas fa-graduation-cap"></i> Credits: 3</span>
                                                <span><i class="fas fa-user-tie"></i> Prof. Michael Brown</span>
                                            </div>
                                            <div class="course-progress">
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 60%"></div>
                                                </div>
                                                <small>60% Complete</small>
                                            </div>
                                        </div>
                                        <div class="course-card" style="animation: fadeInUp 0.6s ease-out 0.3s both;">
                                            <div class="course-header">
                                                <div class="course-icon">
                                                    <i class="fas fa-pills"></i>
                                                </div>
                                                <div class="course-badge">Elective</div>
                                            </div>
                                            <h4>Pharmacology</h4>
                                            <p>Medication administration and drug interactions</p>
                                            <div class="course-details">
                                                <span><i class="fas fa-graduation-cap"></i> Credits: 3</span>
                                                <span><i class="fas fa-user-tie"></i> Dr. Emily Chen</span>
                                            </div>
                                            <div class="course-progress">
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 45%"></div>
                                                </div>
                                                <small>45% Complete</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Finances Section -->
                <section id="finances" class="content-section">
                    <h2>Financial Information</h2>
                    <div class="financial-overview">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="finance-card">
                                    <div class="finance-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="finance-details">
                                        <h3>Total Fees</h3>
                                        <p class="amount">UGX <?php echo number_format($fee_account[0]['total_fees'] ?? 0); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="finance-card">
                                    <div class="finance-icon">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="finance-details">
                                        <h3>Paid</h3>
                                        <p class="amount">UGX <?php echo number_format($fee_account[0]['amount_paid'] ?? 0); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="finance-card">
                                    <div class="finance-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="finance-details">
                                        <h3>Balance</h3>
                                        <p class="amount">UGX <?php echo number_format($fee_account[0]['balance'] ?? 0); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                        $studentStringId = $student_info['student_id'] ?? $student_info['id'] ?? '';
                        $activeSub = [];
                        if ($studentStringId) {
                            $subs = getStudentSubscriptions($studentStringId);
                            $activeSub = array_filter($subs, fn($s) => $s['status'] === 'active');
                        }
                        ?>
                        <div class="payment-actions">
                            <button class="btn btn-primary btn-lg" onclick="showStudentPaymentModal()">
                                <i class="fas fa-credit-card"></i> Make Payment
                            </button>
                            <button class="btn btn-outline-info" onclick="openModal('viewPaymentHistory')">
                                <i class="fas fa-history"></i> Payment History
                            </button>
                            <button class="btn btn-outline-success" onclick="openModal('downloadReceipt')">
                                <i class="fas fa-receipt"></i> Download Receipt
                            </button>
                            <a href="payment-subscriptions.php?student_id=<?= urlencode($studentStringId) ?>" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-arrow-repeat"></i> <?= !empty($activeSub) ? 'Manage Auto-Pay' : 'Setup Auto-Pay' ?>
                            </a>
                        </div>
                        <?php if (!empty($activeSub)): ?>
                        <div class="mt-3 p-3 bg-light rounded border">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                <div>
                                    <strong>Auto Deduction Active</strong>
                                    <br><small class="text-muted">
                                        <?= count($activeSub) ?> subscription(s) active &middot;
                                        Next deduction: <?= date('d M Y', strtotime($activeSub[0]['next_due_date'])) ?> &middot;
                                        UGX <?= number_format($activeSub[0]['installment_amount']) ?>/mo
                                    </small>
                                </div>
                                <a href="payment-subscriptions.php?student_id=<?= urlencode($studentStringId) ?>" class="btn btn-sm btn-outline-primary ms-auto">Details</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($pending_invoices)): ?>
                        <div class="alert alert-warning mt-4">
                            <h5>Pending Invoice</h5>
                            <p><strong>Invoice:</strong> <?php echo htmlspecialchars($next_invoice['invoice_number'] ?? 'N/A'); ?></p>
                            <p><strong>Amount Due:</strong> UGX <?php echo number_format($next_invoice['balance'] ?? 0); ?></p>
                            <p><strong>Due Date:</strong> <?php echo htmlspecialchars($next_invoice['due_date'] ?? 'Not set'); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Recent Payments -->
                        <div class="recent-payments">
                            <h3>Recent Payments</h3>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_history as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['payment_id']; ?></td>
                                            <td>
                                                <?php 
                                                $provider = $payment['payment_provider'] ?? $payment['payment_method'] ?? '';
                                                if ($provider): 
                                                ?>
                                                <img src="<?php echo getPaymentProviderLogo($provider); ?>" alt="<?php echo htmlspecialchars($provider); ?>" style="height: 22px; border-radius: 3px; vertical-align: middle; margin-right: 6px;">
                                                <?php endif; ?>
                                                <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $payment['status']; ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['receipt_generated']): ?>
                                                <button class="btn btn-sm btn-outline-primary">Download</button>
                                                <?php else: ?>
                                                <span class="text-muted">Not Generated</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Announcements Section -->
                <section id="announcements" class="content-section">
                    <h2>Staff Announcements</h2>
                    <?php if (!empty($announcements)): ?>
                        <div class="announcements-list">
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-card mb-3 p-3 border rounded shadow-sm">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                            <p class="text-muted mb-1"><?php echo ucfirst(htmlspecialchars($announcement['announcement_type'])); ?> announcement</p>
                                        </div>
                                        <span class="badge bg-<?php echo $announcement['priority'] === 'urgent' ? 'danger' : ($announcement['priority'] === 'high' ? 'warning' : 'secondary'); ?> text-capitalize"><?php echo htmlspecialchars($announcement['priority']); ?></span>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Posted by <?php echo htmlspecialchars($announcement['posted_by_name'] ?? 'Staff'); ?> on <?php echo date('M j, Y', strtotime($announcement['posted_date'])); ?></small>
                                        <small class="text-muted">Target: <?php echo htmlspecialchars(ucfirst($announcement['target_audience'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No announcements available right now. Check back later for updates from school staff.</div>
                    <?php endif; ?>
                </section>
                
                <!-- Messages Section -->
                <section id="messages" class="content-section">
                    <h2>Communication Center</h2>
                    <div class="communication-actions">
                        <button class="btn btn-primary" onclick="openModal('composeMessage')">
                            <i class="fas fa-plus"></i> Compose Message
                        </button>
                        <button class="btn btn-success" onclick="openModal('messageMatron')">
                            <i class="fas fa-female"></i> Message Matron
                        </button>
                        <button class="btn btn-info" onclick="openModal('messageWarden')">
                            <i class="fas fa-male"></i> Message Warden
                        </button>
                        <button class="btn btn-warning" onclick="openModal('messagePrincipal')">
                            <i class="fas fa-university"></i> Message Principal
                        </button>
                        <button class="btn btn-outline-primary" onclick="openModal('messageLecturer')">
                            <i class="fas fa-chalkboard-teacher"></i> Message Lecturer
                        </button>
                    </div>
                    
                    <div class="messages-container">
                        <div class="message-filters">
                            <button class="btn btn-primary active">All Messages</button>
                            <button class="btn btn-outline-primary">Unread</button>
                            <button class="btn btn-outline-primary">Academic</button>
                            <button class="btn btn-outline-primary">Financial</button>
                            <button class="btn btn-outline-primary">Administrative</button>
                            <button class="btn btn-outline-primary">Personal</button>
                            <button class="btn btn-outline-primary">Emergency</button>
                        </div>
                        
                        <div class="messages-list">
                            <?php foreach ($messages as $message): ?>
                            <div class="message-item <?php echo $message['status'] === 'read' ? 'read' : 'unread'; ?>">
                                <div class="message-header">
                                    <div class="message-sender">
                                        <strong><?php echo $message['sender_role']; ?></strong>
                                        <span class="message-date"><?php echo date('M j, Y H:i', strtotime($message['sent_date'])); ?></span>
                                    </div>
                                    <div class="message-priority">
                                        <span class="priority-badge <?php echo $message['priority']; ?>">
                                            <?php echo ucfirst($message['priority']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="message-subject">
                                    <h4><?php echo $message['subject']; ?></h4>
                                </div>
                                <div class="message-preview">
                                    <p><?php echo substr($message['message_content'], 0, 100) . '...'; ?></p>
                                </div>
                                <div class="message-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewMessage('<?php echo $message['message_id']; ?>')">Read More</button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="replyMessage('<?php echo $message['message_id']; ?>')">Reply</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                
                <!-- Communication Section -->
                <section id="communication" class="content-section">
                    <h2>Communication Center</h2>
                    <div class="communication-grid">
                        <div class="comm-card">
                            <div class="comm-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h3>Contact Principal</h3>
                            <p>Send messages to the School Principal</p>
                            <button class="btn btn-primary" onclick="openCommunicationCenter()">Send Message</button>
                        </div>
                        
                        <div class="comm-card">
                            <div class="comm-icon">
                                <i class="fas fa-female"></i>
                            </div>
                            <h3>Contact Matron</h3>
                            <p>Get in touch with the student matron for welfare issues</p>
                            <button class="btn btn-primary" onclick="openCommunicationCenter()">Send Message</button>
                        </div>
                        
                        <div class="comm-card">
                            <div class="comm-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h3>Contact Classmates</h3>
                            <p>Communicate with fellow students in your class</p>
                            <button class="btn btn-primary" onclick="openCommunicationCenter()">Find Classmates</button>
                        </div>
                        
                        <div class="comm-card">
                            <div class="comm-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h3>Contact Lecturers</h3>
                            <p>Reach out to your course instructors</p>
                            <button class="btn btn-primary" onclick="openCommunicationCenter()">View Lecturers</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="modalAction">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalTitle">Make Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="paymentModalBody">
                        <form id="studentPaymentForm" autocomplete="off">
                            <input type="hidden" id="studentId" value="<?php echo htmlspecialchars($student_info['id'] ?? 0); ?>">
                            <input type="hidden" id="invoiceId" value="<?php echo htmlspecialchars($next_invoice['id'] ?? ''); ?>">
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    <label class="form-label">Invoice</label>
                                    <input type="text" class="form-control" id="invoiceNumber" value="<?php echo htmlspecialchars($next_invoice['invoice_number'] ?? 'Not available'); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Amount Due (UGX)</label>
                                    <input type="text" class="form-control" id="invoiceBalance" value="UGX <?php echo number_format($next_invoice['balance'] ?? $invoice_summary['balance'] ?? 0); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Amount (UGX)</label>
                                    <input type="number" class="form-control" id="paymentAmount" min="1000" step="1000" value="<?php echo max(1000, number_format($next_invoice['balance'] ?? $invoice_summary['balance'] ?? 0, 0, '.', '')); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-control" id="paymentMethod" required>
                                        <option value="mtn_momo">MTN Mobile Money</option>
                                        <option value="airtel_money">Airtel Money</option>
                                        <option value="bank_deposit">Bank Deposit</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="paymentPhone" value="<?php echo htmlspecialchars($student_info['phone'] ?? $student_info['mobile_number'] ?? ''); ?>" placeholder="07XXXXXXXX" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" class="form-control" id="paymentReference" placeholder="Payment reference" value="PMT-<?php echo date('YmdHis'); ?>" required>
                                </div>
                                <div class="col-12" id="bankFields" style="display:none;">
                                    <div class="row gy-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Bank Name</label>
                                            <select class="form-control" id="bankName">
                                                <option value="">Select bank</option>
                                                <option value="centenary_bank">Centenary Bank</option>
                                                <option value="stanbic_bank">Stanbic Bank</option>
                                                <option value="equity_bank">Equity Bank</option>
                                                <option value="pearl_bank">Pearl Bank</option>
                                                <option value="uba_bank">UBA Bank</option>
                                                <option value="dfcu_bank">DFCU Bank</option>
                                                <option value="absa_bank">Absa Bank</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Account Number</label>
                                            <input type="text" class="form-control" id="bankAccountNumber" placeholder="Bank account number">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 alert alert-info" id="paymentHint">
                                <i class="fas fa-info-circle"></i> Enter the amount you are paying, choose a method, and add your mobile number. Bank deposit payments will be verified by the finance office.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmPayment">Confirm Payment</button>
                    </div>
                </div>
            </div>
        </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment integration functions
        function openPaymentModal(method) {
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            const modalTitle = document.getElementById('paymentModalTitle');
            const modalBody = document.getElementById('paymentModalBody');
            
            let title = '';
            let content = '';
            
            switch(method) {
                case 'mtn':
                    title = 'MTN Mobile Money Payment';
                    content = `
                        <div class="payment-form">
                            <div class="payment-header">
                                <i class="fas fa-mobile-alt mtn-icon"></i>
                                <h4>Pay with MTN Mobile Money</h4>
                            </div>
                            <div class="payment-details">
                                <div class="form-group">
                                    <label>Amount (UGX)</label>
                                    <input type="number" class="form-control" id="mtnAmount" min="1000" step="1000" required>
                                </div>
                                <div class="form-group">
                                    <label>Mobile Money Number</label>
                                    <input type="tel" class="form-control" id="mtnNumber" value="<?php echo $student_info['phone']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Payment Reason</label>
                                    <select class="form-control" id="mtnReason" required>
                                        <option value="">Select Reason</option>
                                        <option value="tuition">Tuition Fees</option>
                                        <option value="accommodation">Accommodation</option>
                                        <option value="clinical">Clinical Fees</option>
                                        <option value="other">Other Fees</option>
                                    </select>
                                </div>
                            </div>
                            <div class="payment-info">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <p>You will receive a prompt on your MTN Mobile Money number to confirm this payment.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'airtel':
                    title = 'Airtel Money Payment';
                    content = `
                        <div class="payment-form">
                            <div class="payment-header">
                                <i class="fas fa-mobile-alt airtel-icon"></i>
                                <h4>Pay with Airtel Money</h4>
                            </div>
                            <div class="payment-details">
                                <div class="form-group">
                                    <label>Amount (UGX)</label>
                                    <input type="number" class="form-control" id="airtelAmount" min="1000" step="1000" required>
                                </div>
                                <div class="form-group">
                                    <label>Mobile Money Number</label>
                                    <input type="tel" class="form-control" id="airtelNumber" value="<?php echo $student_info['phone']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Payment Reason</label>
                                    <select class="form-control" id="airtelReason" required>
                                        <option value="">Select Reason</option>
                                        <option value="tuition">Tuition Fees</option>
                                        <option value="accommodation">Accommodation</option>
                                        <option value="clinical">Clinical Fees</option>
                                        <option value="other">Other Fees</option>
                                    </select>
                                </div>
                            </div>
                            <div class="payment-info">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <p>You will receive a prompt on your Airtel Money number to confirm this payment.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'bank':
                    title = 'Bank Deposit Payment';
                    content = `
                        <div class="payment-form">
                            <div class="payment-header">
                                <i class="fas fa-university bank-icon"></i>
                                <h4>Bank Deposit</h4>
                            </div>
                            <div class="payment-details">
                                <div class="form-group">
                                    <label>Amount (UGX)</label>
                                    <input type="number" class="form-control" id="bankAmount" min="1000" step="1000" required>
                                </div>
                                <div class="form-group">
                                    <label>Bank Name</label>
                                    <select class="form-control" id="bankName" required>
                                        <option value="">Select Bank</option>
                                        <option value="centenary">Centenary Bank</option>
                                        <option value="stanbic">Stanbic Bank</option>
                                        <option value="equity">Equity Bank</option>
                                        <option value="pearl">Pearl Bank</option>
                                        <option value="uba">UBA Bank</option>
                                        <option value="dfcu">DFCU Bank</option>
                                        <option value="absa">Absa Bank</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Deposit Slip Number</label>
                                    <input type="text" class="form-control" id="depositSlip" required>
                                </div>
                                <div class="form-group">
                                    <label>Deposit Date</label>
                                    <input type="date" class="form-control" id="depositDate" required>
                                </div>
                                <div class="form-group">
                                    <label>Upload Deposit Slip</label>
                                    <input type="file" class="form-control" id="depositSlipFile" accept="image/*,.pdf" required>
                                </div>
                            </div>
                            <div class="payment-info">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Upload a clear photo of your deposit slip. Our finance team will verify and approve your payment.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'cash':
                    title = 'Cash Payment';
                    content = `
                        <div class="payment-form">
                            <div class="payment-header">
                                <i class="fas fa-money-bill cash-icon"></i>
                                <h4>Cash Payment at School</h4>
                            </div>
                            <div class="payment-details">
                                <div class="form-group">
                                    <label>Amount (UGX)</label>
                                    <input type="number" class="form-control" id="cashAmount" min="1000" step="1000" required>
                                </div>
                                <div class="form-group">
                                    <label>Payment Reason</label>
                                    <select class="form-control" id="cashReason" required>
                                        <option value="">Select Reason</option>
                                        <option value="tuition">Tuition Fees</option>
                                        <option value="accommodation">Accommodation</option>
                                        <option value="clinical">Clinical Fees</option>
                                        <option value="other">Other Fees</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Payment Date</label>
                                    <input type="date" class="form-control" id="cashDate" required>
                                </div>
                            </div>
                            <div class="payment-info">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <p>Please visit the bursar's office to make cash payments. Bring this confirmation with you.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
            }
            
            modalTitle.textContent = title;
            modalBody.innerHTML = content;
            modal.show();
        }
        
        function showStudentPaymentModal() {
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            document.getElementById('invoiceBalance').value = 'UGX ' + Number(<?php echo json_encode(floatval($next_invoice['balance'] ?? $invoice_summary['balance'] ?? 0)); ?>).toLocaleString();
            document.getElementById('paymentAmount').value = Number(<?php echo json_encode(floatval($next_invoice['balance'] ?? $invoice_summary['balance'] ?? 0)); ?>).toFixed(0);
            modal.show();
        }

        var paymentMethodEl = document.getElementById('paymentMethod');
        if (paymentMethodEl) {
            paymentMethodEl.addEventListener('change', function () {
                var bankPanel = document.getElementById('bankFields');
                if (bankPanel) bankPanel.style.display = this.value === 'bank_deposit' ? 'block' : 'none';
            });
        }

        function submitStudentPayment() {
            const studentId = document.getElementById('studentId').value;
            const invoiceId = document.getElementById('invoiceId').value;
            const amount = document.getElementById('paymentAmount').value;
            const method = document.getElementById('paymentMethod').value;
            const phone = document.getElementById('paymentPhone').value;
            const reference = document.getElementById('paymentReference').value;
            const bankName = document.getElementById('bankName').value;
            const accountNumber = document.getElementById('bankAccountNumber').value;

            if (!studentId || !amount || amount <= 0 || !method || !phone || !reference) {
                alert('Please fill in all required payment fields.');
                return;
            }

            const payload = new FormData();
            payload.append('action', method === 'mtn_momo' ? 'initiate_mtn_payment' : method === 'airtel_money' ? 'initiate_airtel_payment' : 'initiate_bank_payment');
            payload.append('student_id', studentId);
            payload.append('invoice_id', invoiceId);
            payload.append('amount', amount);
            payload.append('phone', phone);
            payload.append('reference', reference);
            payload.append('bank_name', bankName);
            payload.append('account_number', accountNumber);
            payload.append('payment_method', method);

            const modalBody = document.getElementById('paymentModalBody');
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3">Submitting your payment request. Please wait...</p>
                </div>
            `;

            fetch('../payment_processor.php', {
                method: 'POST',
                body: payload
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalBody.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">Payment Request Sent</h4>
                            <p>${data.message}</p>
                            <p><strong>Payment Reference:</strong> ${data.payment_reference || reference}</p>
                            <button class="btn btn-primary mt-3" onclick="window.location.reload()">Refresh Dashboard</button>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Payment failed.</strong><br>${data.message || 'Please try again or contact the bursar.'}
                        </div>
                    `;
                }
            })
            .catch(() => {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        An error occurred while sending your payment request. Please refresh the page and try again.
                    </div>
                `;
            });
        }
        
        function downloadReceipt() {
            // Implementation for downloading receipt
            console.log('Downloading receipt...');
            window.print();
        }
        
        // Payment confirmation handler
        var confirmPaymentEl = document.getElementById('confirmPayment');
        if (confirmPaymentEl) {
            confirmPaymentEl.addEventListener('click', function() {
                submitStudentPayment();
            });
        }
        
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
                case 'viewTranscript':
                    modalTitle.textContent = 'Academic Transcript';
                    modalBody.innerHTML = `
                        <div class="transcript-preview">
                            <h4>Official Academic Transcript</h4>
                            <p>Your complete academic record will be displayed here.</p>
                            <div class="text-center">
                                <button class="btn btn-primary" onclick="downloadTranscript()">
                                    <i class="fas fa-download"></i> Download PDF
                                </button>
                                <button class="btn btn-info" onclick="printTranscript()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                    `;
                    break;
                case 'payFees':
                case 'makePayment':
                    modalTitle.textContent = 'Make Payment';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Payment Amount (UGX)</label>
                                <input type="number" class="form-control" min="10000" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-control" required>
                                    <option value="">Select Method</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_deposit">Bank Deposit</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transaction Reference</label>
                                <input type="text" class="form-control" placeholder="Enter transaction reference">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Upload Proof (Optional)</label>
                                <input type="file" class="form-control" accept="image/*,.pdf">
                            </div>
                        </form>
                    `;
                    break;
                case 'sendMessage':
                    modalTitle.textContent = 'Compose Message';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Recipient</label>
                                <select class="form-control" required>
                                    <option value="">Select Recipient</option>
                                    <option value="principal">School Principal</option>
                                    <option value="matron">Matron</option>
                                    <option value="lecturer">Lecturer</option>
                                    <option value="classmate">Classmate</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4" required></textarea>
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
        
        function downloadTranscript() {
            var id = <?= (int)($student_info['id'] ?? 0) ?>;
            if (id) window.open('../print_transcript.php?action=download&student_id=' + id, '_blank');
        }
        
        function printTranscript() {
            var id = <?= (int)($student_info['id'] ?? 0) ?>;
            if (id) window.open('../print_transcript.php?action=print&student_id=' + id, '_blank');
        }
        
        function viewMessage(messageId) {
            console.log('Viewing message:', messageId);
            // Implementation for viewing full message
        }
        
        function replyMessage(messageId) {
            console.log('Replying to message:', messageId);
            // Implementation for replying to message
        }
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
        }
        tr[i].style.display = found ? "" : "none";
    }
}
</script>
</body>
</html>

