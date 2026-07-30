<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['non teaching', 'staff']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_email = $user['email'] ?? '';
$user_name = $user['full_name'] ?? '';

$profileImageUrl = '../images/username.png';
$profileSettingsFile = __DIR__ . '/../includes/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    include_once $profileSettingsFile;
    if (function_exists('getStaffProfileImageUrl')) {
        $url = getStaffProfileImageUrl($user_id);
        if ($url) $profileImageUrl = $url;
    }
}

// Get staff statistics from database
$students_db = $ctx['students'];
$total_students = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM students")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$total_staff = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM staff")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$recent_applications = ($students_db && ($q = $students_db->query("SELECT COUNT(*) FROM student_admissions")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$active_programs = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM academic_programs")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$pending_tasks = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM staff_appraisals WHERE staff_id = " . intval($user_id) . " AND status IN ('draft','submitted')")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$completed_tasks = ($conn && ($q = $conn->query("SELECT COUNT(*) FROM staff_appraisals WHERE staff_id = " . intval($user_id) . " AND status = 'finalized' AND DATE(created_at) = CURDATE()")) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$leave_balance = ($conn && ($q = $conn->query("SELECT COALESCE(SUM(remaining_days),0) FROM leave_balance WHERE staff_id = " . intval($user_id))) && ($r = $q->fetch_row())) ? (int) $r[0] : 0;
$attendance_rate = 0.0;
if ($conn) {
    $q = $conn->query("SELECT COUNT(*) FROM staff_attendance WHERE staff_id = " . intval($user_id) . " AND DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    if ($q && $r = $q->fetch_row()) {
        $total = (int) $r[0];
        $q = $conn->query("SELECT COUNT(*) FROM staff_attendance WHERE staff_id = " . intval($user_id) . " AND DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND status = 'Present'");
        if ($q && $r = $q->fetch_row()) {
            $present = (int) $r[0];
            $attendance_rate = $total > 0 ? round($present / $total, 2) : 0.0;
        }
    }
}

// Get recent activities
$recent_activities = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_activities[] = $row;
            }
        }
} catch (Exception $e) { error_log('non-teaching-staff context: ' . $e->getMessage()); }
}

// ── CSRF helper ──
function nts_csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function nts_verify_csrf() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ── AJAX POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($conn || $ctx['staff'])) {
    $db = $ctx['staff'] ?: $conn;
    header('Content-Type: application/json');

    // Ensure tables exist
    $db->query("CREATE TABLE IF NOT EXISTS staff_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
        category VARCHAR(100),
        due_date DATE,
        assigned_by VARCHAR(255),
        status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $db->query("CREATE TABLE IF NOT EXISTS staff_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        date DATE NOT NULL,
        check_in TIME,
        check_out TIME,
        notes TEXT,
        status ENUM('Present','Absent','Late','Half Day') DEFAULT 'Present',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $db->query("CREATE TABLE IF NOT EXISTS staff_leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        leave_type VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        days INT NOT NULL DEFAULT 1,
        reason TEXT,
        emergency_contact VARCHAR(255),
        handover_notes TEXT,
        status ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $action = $_POST['action'] ?? '';

    // ── Task: Create ──
    if ($action === 'create_task') {
        if (!nts_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority    = $_POST['priority'] ?? 'medium';
        $category    = trim($_POST['category'] ?? '');
        $dueDate     = $_POST['due_date'] ?? null;
        $assignedBy  = trim($_POST['assigned_by'] ?? '');
        if (!$title) { echo json_encode(['success'=>false,'message'=>'Title is required']); exit; }
        $stmt = $db->prepare("INSERT INTO staff_tasks (staff_id,title,description,priority,category,due_date,assigned_by) VALUES (?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param('issssss', $user_id, $title, $description, $priority, $category, $dueDate, $assignedBy);
            if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Task created','id'=>$db->insert_id]); }
            else { echo json_encode(['success'=>false,'message'=>'Failed to create task']); }
            $stmt->close();
        } else { echo json_encode(['success'=>false,'message'=>'Failed to prepare statement']); }
        exit;
    }

    // ── Task: Update status ──
    if ($action === 'update_task_status') {
        if (!nts_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }
        $taskId   = (int)($_POST['task_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'completed';
        if (!in_array($newStatus, ['pending','in_progress','completed','cancelled'])) { echo json_encode(['success'=>false,'message'=>'Invalid status']); exit; }
        $stmt = $db->prepare("UPDATE staff_tasks SET status=? WHERE id=? AND staff_id=?");
        if ($stmt) {
            $stmt->bind_param('sii', $newStatus, $taskId, $user_id);
            if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Task updated']); }
            else { echo json_encode(['success'=>false,'message'=>'Failed to update task']); }
            $stmt->close();
        } else { echo json_encode(['success'=>false,'message'=>'Failed to prepare statement']); }
        exit;
    }

    // ── Task: Delete ──
    if ($action === 'delete_task') {
        if (!nts_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }
        $taskId = (int)($_POST['task_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM staff_tasks WHERE id=? AND staff_id=?");
        if ($stmt) {
            $stmt->bind_param('ii', $taskId, $user_id);
            if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Task deleted']); }
            else { echo json_encode(['success'=>false,'message'=>'Failed to delete task']); }
            $stmt->close();
        } else { echo json_encode(['success'=>false,'message'=>'Failed to prepare statement']); }
        exit;
    }

    // ── Attendance: Check In ──
    if ($action === 'check_in') {
        if (!nts_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }
        $today = date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        $checkTime = date('H:i:s');
        $hour = (int)date('H');
        $status = ($hour > 9) ? 'Late' : 'Present';
        $stmt = $db->prepare("INSERT INTO staff_attendance (staff_id,date,check_in,notes,status) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE check_in=VALUES(check_in),notes=VALUES(notes),status=VALUES(status)");
        if ($stmt) {
            $stmt->bind_param('issss', $user_id, $today, $checkTime, $notes, $status);
            if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Checked in successfully','time'=>$checkTime,'status'=>$status]); }
            else { echo json_encode(['success'=>false,'message'=>'Check-in failed']); }
            $stmt->close();
        } else { echo json_encode(['success'=>false,'message'=>'Failed to prepare statement']); }
        exit;
    }

    // ── Attendance: Check Out ──
    if ($action === 'check_out') {
        if (!nts_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }
        $today = date('Y-m-d');
        $checkOutTime = date('H:i:s');
        $stmt = $db->prepare("UPDATE staff_attendance SET check_out=? WHERE staff_id=? AND date=? AND check_out IS NULL");
        if ($stmt) {
            $stmt->bind_param('sis', $checkOutTime, $user_id, $today);
            if ($stmt->execute() && $stmt->affected_rows > 0) { echo json_encode(['success'=>true,'message'=>'Checked out successfully','time'=>$checkOutTime]); }
            elseif ($stmt->affected_rows === 0) { echo json_encode(['success'=>false,'message'=>'No check-in found for today or already checked out']); }
            else { echo json_encode(['success'=>false,'message'=>'Check-out failed']); }
            $stmt->close();
        } else { echo json_encode(['success'=>false,'message'=>'Failed to prepare statement']); }
        exit;
    }

    // ── Leave: Submit request ──
    if ($action === 'submit_leave') {
        if (!nts_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }
        $leaveType     = $_POST['leave_type'] ?? '';
        $startDate     = $_POST['start_date'] ?? '';
        $endDate       = $_POST['end_date'] ?? '';
        $reason        = trim($_POST['reason'] ?? '');
        $emergencyContact = trim($_POST['emergency_contact'] ?? '');
        $handoverNotes = trim($_POST['handover_notes'] ?? '');
        $validTypes = ['annual','sick','maternity','paternity','compassionate','study'];
        if (!in_array($leaveType, $validTypes)) { echo json_encode(['success'=>false,'message'=>'Invalid leave type']); exit; }
        if (!$startDate || !$endDate) { echo json_encode(['success'=>false,'message'=>'Start and end dates are required']); exit; }
        $start = new DateTime($startDate);
        $end   = new DateTime($endDate);
        $days  = $end->diff($start)->days + 1;
        if ($days < 1) { echo json_encode(['success'=>false,'message'=>'Invalid date range']); exit; }
        $stmt = $db->prepare("INSERT INTO staff_leave_requests (staff_id,leave_type,start_date,end_date,days,reason,emergency_contact,handover_notes) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param('isssisss', $user_id, $leaveType, $startDate, $endDate, $days, $reason, $emergencyContact, $handoverNotes);
            if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Leave request submitted','id'=>$db->insert_id,'days'=>$days]); }
            else { echo json_encode(['success'=>false,'message'=>'Failed to submit leave request']); }
            $stmt->close();
        } else { echo json_encode(['success'=>false,'message'=>'Failed to prepare statement']); }
        exit;
    }

    // ── Leave: Cancel request ──
    if ($action === 'cancel_leave') {
        if (!nts_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }
        $leaveId = (int)($_POST['leave_id'] ?? 0);
        $stmt = $db->prepare("UPDATE staff_leave_requests SET status='cancelled' WHERE id=? AND staff_id=? AND status='pending'");
        if ($stmt) {
            $stmt->bind_param('ii', $leaveId, $user_id);
            if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Leave request cancelled']); }
            else { echo json_encode(['success'=>false,'message'=>'Failed to cancel leave request']); }
            $stmt->close();
        } else { echo json_encode(['success'=>false,'message'=>'Failed to prepare statement']); }
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
}
?>
<?php
$pageToSection = [
    'home'           => 'overview',
    'overview'       => 'overview',
    'tasks'          => 'tasks',
    'attendance'     => 'attendance',
    'leave'          => 'leave',
    'documents'      => 'documents',
    'training'       => 'training',
    'communications' => 'communications',
    'activities'     => 'activities',
];
$requestedPage = $_GET['page'] ?? 'home';
$section = $pageToSection[$requestedPage] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
    <div class="dashboard-container">
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Non Teaching Staff Dashboard</h1>
                    <p>Administrative Support & Operations</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span id="currentDate"></span>
                    </div>
                    <div class="user-menu">
                        <img src="<?= $profileImageUrl ?? '../images/username.png' ?>" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo htmlspecialchars($user_name); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="section-tabs">
                    <a class="section-tab<?= $section==='overview'?' active':'' ?>" data-tab="overview" onclick="switchToSection('overview')">Overview</a>
                    <a class="section-tab<?= $section==='tasks'?' active':'' ?>" data-tab="tasks" onclick="switchToSection('tasks')">Tasks</a>
                    <a class="section-tab<?= $section==='attendance'?' active':'' ?>" data-tab="attendance" onclick="switchToSection('attendance')">Attendance</a>
                    <a class="section-tab<?= $section==='leave'?' active':'' ?>" data-tab="leave" onclick="switchToSection('leave')">Leave</a>
                    <a class="section-tab<?= $section==='documents'?' active':'' ?>" data-tab="documents" onclick="switchToSection('documents')">Documents</a>
                    <a class="section-tab<?= $section==='training'?' active':'' ?>" data-tab="training" onclick="switchToSection('training')">Training</a>
                    <a class="section-tab<?= $section==='communications'?' active':'' ?>" data-tab="communications" onclick="switchToSection('communications')">Communications</a>
                    <a class="section-tab<?= $section==='activities'?' active':'' ?>" data-tab="activities" onclick="switchToSection('activities')">Activities</a>
                </div>
                <!-- Staff Overview -->
                <section id="overview" class="content-section dashboard-section<?= $section==='overview'?' active':'' ?>" data-section="overview">
                    <h2>Staff Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_tasks; ?></h3>
                                <p>Pending Tasks</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $completed_tasks; ?></h3>
                                <p>Completed Today</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $leave_balance; ?></h3>
                                <p>Leave Balance (Days)</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo round($attendance_rate * 100, 1); ?>%</h3>
                                <p>Attendance Rate</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Task Management -->
                <section id="tasks" class="content-section dashboard-section<?= $section==='tasks'?' active':'' ?>" data-section="tasks">
                    <h2>Task Management</h2>
                    <div class="task-actions">
                        <button class="btn btn-primary" onclick="openModal('newTask')">
                            <i class="fas fa-plus"></i> New Task
                        </button>
                    </div>
                    <?php
                    $staff_tasks = [];
                    if ($conn) {
                        try {
                            $conn->query("CREATE TABLE IF NOT EXISTS staff_tasks (id INT AUTO_INCREMENT PRIMARY KEY,staff_id INT NOT NULL,title VARCHAR(255) NOT NULL,description TEXT,priority ENUM('low','medium','high','urgent') DEFAULT 'medium',category VARCHAR(100),due_date DATE,assigned_by VARCHAR(255),status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                            $stmt = $conn->prepare("SELECT * FROM staff_tasks WHERE staff_id=? ORDER BY FIELD(status,'pending','in_progress','completed','cancelled'), due_date ASC");
                            if ($stmt) {
                                $stmt->bind_param('i', $user_id);
                                $stmt->execute();
                                $staff_tasks = isnm_fetch_all($stmt->get_result());
                                $stmt->close();
                            }
                        } catch (Exception $e) { error_log('nts tasks: ' . $e->getMessage()); }
                    }
                    ?>
                    <div class="tasks-overview">
                        <h3>My Tasks (<?= count($staff_tasks) ?>)</h3>
                        <?php if (empty($staff_tasks)): ?>
                            <p class="text-muted text-center py-3">No tasks yet. Click "New Task" to create one.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Title</th><th>Priority</th><th>Category</th><th>Due</th><th>Assigned By</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php foreach ($staff_tasks as $t): ?>
                                <tr>
                                    <td><?= htmlspecialchars($t['title']) ?></td>
                                    <td><span class="badge bg-<?= $t['priority']==='urgent'?'danger':($t['priority']==='high'?'warning':($t['priority']==='medium'?'info':'secondary')) ?>"><?= ucfirst($t['priority']) ?></span></td>
                                    <td><?= htmlspecialchars($t['category']??'-') ?></td>
                                    <td><?= $t['due_date'] ? date('M j, Y', strtotime($t['due_date'])) : '-' ?></td>
                                    <td><?= htmlspecialchars($t['assigned_by']??'-') ?></td>
                                    <td><span class="badge bg-<?= $t['status']==='completed'?'success':($t['status']==='in_progress'?'primary':($t['status']==='cancelled'?'dark':'warning')) ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                                    <td>
                                        <?php if ($t['status'] !== 'completed' && $t['status'] !== 'cancelled'): ?>
                                        <button class="btn btn-sm btn-outline-success" onclick="updateTask(<?= $t['id'] ?>,'completed')" title="Mark Complete"><i class="fas fa-check"></i></button>
                                        <?php if ($t['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="updateTask(<?= $t['id'] ?>,'in_progress')" title="Start"><i class="fas fa-play"></i></button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?= $t['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?= $t['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Attendance -->
                <section id="attendance" class="content-section dashboard-section<?= $section==='attendance'?' active':'' ?>" data-section="attendance">
                    <h2>Attendance Management</h2>
                    <div class="attendance-actions">
                        <button class="btn btn-primary" onclick="openModal('checkIn')">
                            <i class="fas fa-sign-in-alt"></i> Check In
                        </button>
                        <button class="btn btn-success" onclick="openModal('checkOut')">
                            <i class="fas fa-sign-out-alt"></i> Check Out
                        </button>
                    </div>
                    <?php
                    $today_attendance = null;
                    $month_present = 0; $month_absent = 0; $month_late = 0;
                    if ($conn) {
                        try {
                            $conn->query("CREATE TABLE IF NOT EXISTS staff_attendance (id INT AUTO_INCREMENT PRIMARY KEY,staff_id INT NOT NULL,date DATE NOT NULL,check_in TIME,check_out TIME,notes TEXT,status ENUM('Present','Absent','Late','Half Day') DEFAULT 'Present',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                            $today = date('Y-m-d');
                            $stmt = $conn->prepare("SELECT * FROM staff_attendance WHERE staff_id=? AND date=?");
                            if ($stmt) {
                                $stmt->bind_param('is', $user_id, $today);
                                $stmt->execute();
                                $today_attendance = $stmt->get_result()->fetch_assoc();
                                $stmt->close();
                            }
                            $stmt = $conn->prepare("SELECT status,COUNT(*) as cnt FROM staff_attendance WHERE staff_id=? AND MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE()) GROUP BY status");
                            if ($stmt) {
                                $stmt->bind_param('i', $user_id);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                while ($row = $res->fetch_assoc()) {
                                    if ($row['status']==='Present') $month_present = (int)$row['cnt'];
                                    elseif ($row['status']==='Late') $month_late = (int)$row['cnt'];
                                    elseif ($row['status']==='Absent') $month_absent = (int)$row['cnt'];
                                }
                                $stmt->close();
                            }
                        } catch (Exception $e) { error_log('nts attendance: ' . $e->getMessage()); }
                    }
                    ?>
                    <div class="attendance-overview">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Today's Status</h5>
                                        <?php if ($today_attendance): ?>
                                        <p><strong>Status:</strong> <span class="badge bg-<?= $today_attendance['status']==='Present'?'success':($today_attendance['status']==='Late'?'warning':'secondary') ?>"><?= $today_attendance['status'] ?></span></p>
                                        <p><strong>Check In:</strong> <?= $today_attendance['check_in'] ? date('h:i A', strtotime($today_attendance['check_in'])) : 'N/A' ?></p>
                                        <p><strong>Check Out:</strong> <?= $today_attendance['check_out'] ? date('h:i A', strtotime($today_attendance['check_out'])) : ($today_attendance['check_in'] ? 'Not yet checked out' : 'N/A') ?></p>
                                        <?php else: ?>
                                        <p class="text-muted">Not checked in today.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Monthly Summary (<?= date('M Y') ?>)</h5>
                                        <div class="summary-stats">
                                            <div class="stat"><span>Present:</span><strong class="text-success"><?= $month_present ?></strong></div>
                                            <div class="stat"><span>Late:</span><strong class="text-warning"><?= $month_late ?></strong></div>
                                            <div class="stat"><span>Absent:</span><strong class="text-danger"><?= $month_absent ?></strong></div>
                                            <div class="stat"><span>Rate:</span><strong><?= ($month_present+$month_late+$month_absent)>0 ? round($month_present/($month_present+$month_late+$month_absent)*100,1).'%' : '-' ?></strong></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Leave Management -->
                <section id="leave" class="content-section dashboard-section<?= $section==='leave'?' active':'' ?>" data-section="leave">
                    <h2>Leave Management</h2>
                    <div class="leave-actions">
                        <button class="btn btn-primary" onclick="openModal('leaveRequest')">
                            <i class="fas fa-calendar-plus"></i> Request Leave
                        </button>
                    </div>
                    <?php
                    $leave_requests = [];
                    if ($conn) {
                        try {
                            $conn->query("CREATE TABLE IF NOT EXISTS staff_leave_requests (id INT AUTO_INCREMENT PRIMARY KEY,staff_id INT NOT NULL,leave_type VARCHAR(50) NOT NULL,start_date DATE NOT NULL,end_date DATE NOT NULL,days INT NOT NULL DEFAULT 1,reason TEXT,emergency_contact VARCHAR(255),handover_notes TEXT,status ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                            $stmt = $conn->prepare("SELECT * FROM staff_leave_requests WHERE staff_id=? ORDER BY created_at DESC LIMIT 10");
                            if ($stmt) {
                                $stmt->bind_param('i', $user_id);
                                $stmt->execute();
                                $leave_requests = isnm_fetch_all($stmt->get_result());
                                $stmt->close();
                            }
                        } catch (Exception $e) { error_log('nts leave: ' . $e->getMessage()); }
                    }
                    ?>
                    <div class="leave-overview">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Leave Requests</h5>
                                        <?php if (empty($leave_requests)): ?>
                                        <p class="text-muted text-center py-3">No leave requests yet.</p>
                                        <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead><tr><th>Type</th><th>Period</th><th>Days</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
                                                <tbody>
                                                <?php foreach ($leave_requests as $lr): ?>
                                                <tr>
                                                    <td><?= ucfirst($lr['leave_type']) ?></td>
                                                    <td><?= date('M j', strtotime($lr['start_date'])) ?> - <?= date('M j, Y', strtotime($lr['end_date'])) ?></td>
                                                    <td><?= $lr['days'] ?></td>
                                                    <td><?= htmlspecialchars(mb_substr($lr['reason']??'',0,40)) ?></td>
                                                    <td><span class="badge bg-<?= $lr['status']==='approved'?'success':($lr['status']==='rejected'?'danger':($lr['status']==='cancelled'?'dark':'warning')) ?>"><?= ucfirst($lr['status']) ?></span></td>
                                                    <td>
                                                        <?php if ($lr['status']==='pending'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelLeave(<?= $lr['id'] ?>)"><i class="fas fa-times"></i></button>
                                                        <?php else: ?>-<?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Documents -->
                <section id="documents" class="content-section dashboard-section<?= $section==='documents'?' active':'' ?>" data-section="documents">
                    <h2>Document Management</h2>
                    <div class="document-actions">
                        <button class="btn btn-primary" onclick="openModal('uploadDocument')">
                            <i class="fas fa-upload"></i> Upload Document
                        </button>
                        <button class="btn btn-success" onclick="openModal('documentLibrary')">
                            <i class="fas fa-folder"></i> Document Library
                        </button>
                        <button class="btn btn-info" onclick="openModal('sharedDocuments')">
                            <i class="fas fa-share"></i> Shared Documents
                        </button>
                        <button class="btn btn-warning" onclick="openModal('documentArchive')">
                            <i class="fas fa-archive"></i> Document Archive
                        </button>
                    </div>
                    
                    <div class="documents-overview">
                        <h3>My Documents</h3>
                        <div class="documents-list">
                            <div class="document-item">
                                <div class="document-header">
                                    <h4>Employment Contract</h4>
                                    <span class="document-type">PDF</span>
                                </div>
                                <div class="document-details">
                                    <div class="detail">
                                        <span>Size:</span>
                                        <strong>1.2 MB</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Uploaded:</span>
                                        <strong>Jan 15, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Category:</span>
                                        <strong>Employment</strong>
                                    </div>
                                </div>
                                <div class="document-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Training & Development -->
                <section id="training" class="content-section dashboard-section<?= $section==='training'?' active':'' ?>" data-section="training">
                    <h2>Training & Development</h2>
                    <div class="training-actions">
                        <button class="btn btn-primary" onclick="openModal('trainingRequest')">
                            <i class="fas fa-graduation-cap"></i> Training Request
                        </button>
                        <button class="btn btn-success" onclick="openModal('trainingSchedule')">
                            <i class="fas fa-calendar"></i> Training Schedule
                        </button>
                        <button class="btn btn-info" onclick="openModal('certifications')">
                            <i class="fas fa-certificate"></i> Certifications
                        </button>
                        <button class="btn btn-warning" onclick="openModal('skillsAssessment')">
                            <i class="fas fa-chart-line"></i> Skills Assessment
                        </button>
                    </div>
                    
                    <div class="training-overview">
                        <h3>Upcoming Training</h3>
                        <div class="training-list">
                            <div class="training-item">
                                <div class="training-header">
                                    <h4>Office Management Workshop</h4>
                                    <span class="training-date">May 5 , 6, 2026</span>
                                </div>
                                <div class="training-details">
                                    <div class="detail">
                                        <span>Type:</span>
                                        <strong>Professional Development</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Location:</span>
                                        <strong>ISNM Training Room</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Status:</span>
                                        <strong class="text-success">Registered</strong>
                                    </div>
                                </div>
                                <div class="training-actions">
                                    <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-info">Download Materials</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Communications -->
                <section id="communications" class="content-section dashboard-section<?= $section==='communications'?' active':'' ?>" data-section="communications">
                    <h2>Communications</h2>
                    <div class="communication-actions">
                        <button class="btn btn-primary" onclick="openModal('sendMessage')">
                            <i class="fas fa-envelope"></i> Send Message
                        </button>
                        <button class="btn btn-success" onclick="openModal('inbox')">
                            <i class="fas fa-inbox"></i> Inbox
                        </button>
                        <button class="btn btn-info" onclick="openModal('announcements')">
                            <i class="fas fa-bullhorn"></i> Announcements
                        </button>
                        <button class="btn btn-warning" onclick="openModal('notifications')">
                            <i class="fas fa-bell"></i> Notifications
                        </button>
                    </div>
                    
                    <div class="communications-overview">
                        <h3>Recent Messages</h3>
                        <div class="message-list">
                            <div class="message-item">
                                <div class="message-header">
                                    <h4>From: HR Manager</h4>
                                    <span class="message-date">Apr 22, 2026</span>
                                </div>
                                <div class="message-content">
                                    <p>Reminder: Monthly staff meeting tomorrow at 10 AM in the main hall.</p>
                                </div>
                                <div class="message-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Reply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section id="activities" class="activities-section dashboard-section<?= $section==='activities'?' active':'' ?>" data-section="activities">
                    <h2>Recent Staff Activities</h2>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current date/time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Navigation — delegate to universal section switcher
        document.querySelectorAll('.dashboard-sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var sec = this.getAttribute('href').substring(1);
                if (typeof switchToSection === 'function') {
                    switchToSection(sec);
                }
            });
        });

        // Modal functions
        let currentModalAction = '';
        function openModal(action) {
            currentModalAction = action;
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            switch(action) {
                case 'newTask':
                    modalTitle.textContent = 'Create New Task';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Task Title</label>
                                <input type="text" class="form-control" id="taskTitle" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Task Description</label>
                                <textarea class="form-control" id="taskDesc" rows="4" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Priority Level</label>
                                        <select class="form-control" id="taskPriority" required>
                                            <option value="">Select Priority</option>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" class="form-control" id="taskDueDate" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Task Category</label>
                                <select class="form-control" id="taskCategory" required>
                                    <option value="">Select Category</option>
                                    <option value="administrative">Administrative</option>
                                    <option value="technical">Technical</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="communication">Communication</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assigned By</label>
                                <input type="text" class="form-control" id="taskAssignedBy" required>
                            </div>
                        </form>
                    `;
                    break;
                case 'checkIn':
                    modalTitle.textContent = 'Check In';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Check In Time</label>
                                <input type="time" class="form-control" id="checkInTime" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" id="checkInNotes" rows="3" placeholder="Any additional notes..."></textarea>
                            </div>
                        </form>
                    `;
                    setTimeout(() => {
                        const now = new Date();
                        const time = now.toTimeString().slice(0,5);
                        const el = document.getElementById('checkInTime');
                        if (el) el.value = time;
                    }, 100);
                    break;
                case 'checkOut':
                    modalTitle.textContent = 'Check Out';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Check Out Time</label>
                                <input type="time" class="form-control" id="checkOutTime" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Work Summary</label>
                                <textarea class="form-control" id="checkOutNotes" rows="3" placeholder="Summarize your work today..."></textarea>
                            </div>
                        </form>
                    `;
                    setTimeout(() => {
                        const now = new Date();
                        const time = now.toTimeString().slice(0,5);
                        const el = document.getElementById('checkOutTime');
                        if (el) el.value = time;
                    }, 100);
                    break;
                case 'leaveRequest':
                    modalTitle.textContent = 'Request Leave';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Leave Type</label>
                                <select class="form-control" id="leaveType" required>
                                    <option value="">Select Leave Type</option>
                                    <option value="annual">Annual Leave</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="maternity">Maternity Leave</option>
                                    <option value="paternity">Paternity Leave</option>
                                    <option value="compassionate">Compassionate Leave</option>
                                    <option value="study">Study Leave</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="leaveStart" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="leaveEnd" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Leave</label>
                                <textarea class="form-control" id="leaveReason" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Emergency Contact During Leave</label>
                                <input type="text" class="form-control" id="leaveEmergency" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Handover Arrangements</label>
                                <textarea class="form-control" id="leaveHandover" rows="3" placeholder="Describe how your responsibilities will be covered..."></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'announcements':
                    modalTitle.textContent = 'Post Announcement';
                    modalBody.innerHTML = `
                        <form id="sendAnnouncementForm">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input id="annTitle" type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Target Audience</label>
                                <select id="annTarget" class="form-control">
                                    <option value="all">All</option>
                                    <option value="students">Students</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select id="annPriority" class="form-control">
                                    <option value="normal">Normal</option>
                                    <option value="important">Important</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea id="annContent" class="form-control" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input id="annExpiry" type="date" class="form-control">
                            </div>
                        </form>`;
                    break;
            }
            modal.show();
        }

        function postAction(action, data) {
            data.append('action', action);
            data.append('csrf_token', window.CSRF_TOKEN || '');
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><p class="mt-3">Processing...</p></div>';
            return fetch(window.location.href, { method: 'POST', body: data })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        modalBody.innerHTML = '<div class="alert alert-success">' + (resp.message||'Done') + '</div>';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        modalBody.innerHTML = '<div class="alert alert-danger">' + (resp.message||'Failed') + '</div>';
                    }
                    return resp;
                })
                .catch(() => { modalBody.innerHTML = '<div class="alert alert-danger">Network error.</div>'; });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modalActionBtn = document.getElementById('modalAction');
            if (!modalActionBtn) return;
            modalActionBtn.addEventListener('click', function() {
                const title = document.getElementById('modalTitle').textContent || '';

                if (title.includes('New Task')) {
                    const fd = new FormData();
                    fd.append('title', document.getElementById('taskTitle').value.trim());
                    fd.append('description', document.getElementById('taskDesc').value.trim());
                    fd.append('priority', document.getElementById('taskPriority').value);
                    fd.append('due_date', document.getElementById('taskDueDate').value);
                    fd.append('category', document.getElementById('taskCategory').value);
                    fd.append('assigned_by', document.getElementById('taskAssignedBy').value.trim());
                    if (!fd.get('title')) { alert('Title required.'); return; }
                    postAction('create_task', fd);
                }
                else if (title === 'Check In') {
                    const fd = new FormData();
                    fd.append('notes', document.getElementById('checkInNotes').value.trim());
                    postAction('check_in', fd);
                }
                else if (title === 'Check Out') {
                    const fd = new FormData();
                    fd.append('notes', document.getElementById('checkOutNotes').value.trim());
                    postAction('check_out', fd);
                }
                else if (title.includes('Request Leave')) {
                    const fd = new FormData();
                    fd.append('leave_type', document.getElementById('leaveType').value);
                    fd.append('start_date', document.getElementById('leaveStart').value);
                    fd.append('end_date', document.getElementById('leaveEnd').value);
                    fd.append('reason', document.getElementById('leaveReason').value.trim());
                    fd.append('emergency_contact', document.getElementById('leaveEmergency').value.trim());
                    fd.append('handover_notes', document.getElementById('leaveHandover').value.trim());
                    if (!fd.get('leave_type') || !fd.get('start_date') || !fd.get('end_date')) { alert('Fill required fields.'); return; }
                    postAction('submit_leave', fd);
                }
                else if (title.includes('Announcement')) {
                    const fd = new FormData();
                    fd.append('title', document.getElementById('annTitle').value.trim());
                    fd.append('content', document.getElementById('annContent').value.trim());
                    fd.append('announcement_type', 'general');
                    fd.append('target_audience', document.getElementById('annTarget').value);
                    fd.append('priority', document.getElementById('annPriority').value);
                    fd.append('expiry_date', document.getElementById('annExpiry').value || '');
                    fd.append('status', 'published');
                    fd.append('csrf_token', window.CSRF_TOKEN || '');
                    if (!fd.get('title') || !fd.get('content')) { alert('Title and message required.'); return; }
                    const modalBody = document.getElementById('modalBody');
                    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><p class="mt-3">Publishing...</p></div>';
                    fetch('../includes/ajax_publish_announcement.php', { method: 'POST', body: fd })
                        .then(r => r.json()).then(resp => {
                            if (resp.success) { modalBody.innerHTML = '<div class="alert alert-success">Published.</div>'; setTimeout(()=>location.reload(),900); }
                            else { modalBody.innerHTML = '<div class="alert alert-danger">Failed: ' + (resp.message||'Unknown') + '</div>'; }
                        }).catch(()=>{ modalBody.innerHTML = '<div class="alert alert-danger">Network error.</div>'; });
                }
            });
        });

        function updateTask(id, status) {
            if (!confirm('Update task status?')) return;
            const fd = new FormData();
            fd.append('action', 'update_task_status');
            fd.append('task_id', id);
            fd.append('status', status);
            fd.append('csrf_token', window.CSRF_TOKEN || '');
            fetch(window.location.href, { method:'POST', body:fd }).then(r=>r.json()).then(r=>{
                if (r.success) location.reload(); else alert(r.message||'Failed');
            });
        }
        function deleteTask(id) {
            if (!confirm('Delete this task?')) return;
            const fd = new FormData();
            fd.append('action', 'delete_task');
            fd.append('task_id', id);
            fd.append('csrf_token', window.CSRF_TOKEN || '');
            fetch(window.location.href, { method:'POST', body:fd }).then(r=>r.json()).then(r=>{
                if (r.success) location.reload(); else alert(r.message||'Failed');
            });
        }
        function cancelLeave(id) {
            if (!confirm('Cancel this leave request?')) return;
            const fd = new FormData();
            fd.append('action', 'cancel_leave');
            fd.append('leave_id', id);
            fd.append('csrf_token', window.CSRF_TOKEN || '');
            fetch(window.location.href, { method:'POST', body:fd }).then(r=>r.json()).then(r=>{
                if (r.success) location.reload(); else alert(r.message||'Failed');
            });
        }
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

