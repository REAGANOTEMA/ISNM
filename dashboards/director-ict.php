<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/news_management_widget.php';

$ctx = bootstrapStaffDashboard(['director', 'ict']);
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'ICT Director';
$website_conn = $ctx['website'];

// Connect to ICT database alongside others
$ict_conn = null;
try {
    $ict_conn = getICTConnection();
} catch (Exception $e) {
    $ict_conn = null;
}

// Helper: safe query
function ict_q($conn, $sql) {
    if (!$conn) return 0;
    try {
        $r = $conn->query($sql);
        if (!$r) return 0;
        $row = $r->fetch_assoc();
        return $row[array_key_first($row)] ?? 0;
    } catch (Exception $e) { return 0; }
}

// Helper: safe fetch all
function ict_fetch($conn, $sql) {
    if (!$conn) return [];
    try {
        $r = $conn->query($sql);
        if (!$r) return [];
        return $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { return []; }
}

// Generate student ID
function generateStudentIdICT() {
    global $students_conn;
    do {
        $year = date('Y');
        $random = mt_rand(1000, 9999);
        $student_id = "ISNM/$year/$random";
        $check = $students_conn->query("SELECT COUNT(*) as cnt FROM students WHERE student_id = '$student_id'");
        $row = $check ? $check->fetch_assoc() : ['cnt' => 1];
    } while ($row['cnt'] > 0);
    return $student_id;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Add Computer ──
    if ($action === 'add_computer' && $ict_conn) {
        $cid = $ict_conn->real_escape_string($_POST['computer_id']);
        $name = $ict_conn->real_escape_string($_POST['computer_name']);
        $loc = $ict_conn->real_escape_string($_POST['location']);
        $ip = $ict_conn->real_escape_string($_POST['ip_address'] ?? '');
        $mac = $ict_conn->real_escape_string($_POST['mac_address'] ?? '');
        $specs = $ict_conn->real_escape_string($_POST['specifications'] ?? '');
        $os = $ict_conn->real_escape_string($_POST['os_installed'] ?? '');
        $ict_conn->query("INSERT IGNORE INTO lab_computers (computer_id, computer_name, location, status, ip_address, mac_address, specifications, os_installed) VALUES ('$cid', '$name', '$loc', 'online', '$ip', '$mac', '$specs', '$os')");
        $_SESSION['success'] = "Computer $cid added successfully.";
        header('Location: director-ict.php');
        exit;
    }

    // ── Create Support Ticket ──
    if ($action === 'create_ticket' && $ict_conn) {
        $tn = 'TKT-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $rn = $ict_conn->real_escape_string($_POST['requester_name']);
        $re = $ict_conn->real_escape_string($_POST['requester_email'] ?? '');
        $rt = $ict_conn->real_escape_string($_POST['requester_type']);
        $it = $ict_conn->real_escape_string($_POST['issue_type']);
        $pr = $ict_conn->real_escape_string($_POST['priority']);
        $desc = $ict_conn->real_escape_string($_POST['description']);
        $ict_conn->query("INSERT INTO it_support_tickets (ticket_number, requester_name, requester_email, requester_type, issue_type, priority, description) VALUES ('$tn', '$rn', '$re', '$rt', '$it', '$pr', '$desc')");
        $_SESSION['success'] = "Support ticket $tn created.";
        header('Location: director-ict.php');
        exit;
    }

    // ── Resolve Ticket ──
    if ($action === 'resolve_ticket' && $ict_conn) {
        $id = intval($_POST['ticket_id']);
        $notes = $ict_conn->real_escape_string($_POST['resolution_notes'] ?? '');
        $ict_conn->query("UPDATE it_support_tickets SET status = 'resolved', resolution_notes = CONCAT(resolution_notes, '\n[$user_name] $notes'), resolved_at = NOW() WHERE id = $id");
        $_SESSION['success'] = "Ticket #$id resolved.";
        header('Location: director-ict.php');
        exit;
    }

    // ── Add Student ──
    if ($action === 'add_student' && $students_conn) {
        $student_id = generateStudentIdICT();
        $fn = $students_conn->real_escape_string($_POST['full_name']);
        $parts = explode(' ', trim($fn), 2);
        $first = $parts[0];
        $surname = $parts[1] ?? '';
        $phone = $students_conn->real_escape_string($_POST['phone'] ?? '');
        $email = $students_conn->real_escape_string($_POST['email'] ?? '');
        $prog = $students_conn->real_escape_string($_POST['program'] ?? '');
        $gender = $students_conn->real_escape_string($_POST['gender'] ?? '');
        $set = $students_conn->real_escape_string($_POST['set_name'] ?? date('Y'));
        $dob = $_POST['date_of_birth'] ? "'" . $students_conn->real_escape_string($_POST['date_of_birth']) . "'" : 'NULL';
        $intake = date('Y');

        $sql = "INSERT INTO students (student_id, first_name, surname, full_name, phone, email, program, gender, set_name, date_of_birth, intake_year, status, created_at)
                VALUES ('$student_id', '$first', '$surname', '$fn', '$phone', '$email', '$prog', '$gender', '$set', $dob, '$intake', 'Active', NOW())";
        if ($students_conn->query($sql)) {
            $_SESSION['success'] = "Student $fn added. ID: $student_id";
        } else {
            $_SESSION['error'] = "Error: " . $students_conn->error;
        }
        header('Location: director-ict.php');
        exit;
    }

    header('Location: director-ict.php');
    exit;
}

// ── STATISTICS ──
$total_computers = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM lab_computers WHERE status != 'deleted'");
$computers_online = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM lab_computers WHERE status = 'online'");
$computers_offline = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM lab_computers WHERE status = 'offline'");
$computers_maint = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM lab_computers WHERE status = 'maintenance'");
$pending_tickets = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM it_support_tickets WHERE status IN ('open', 'in_progress')");
$open_tickets = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM it_support_tickets WHERE status = 'open'");
$network_online = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM network_devices WHERE status = 'online'");
$network_offline = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM network_devices WHERE status = 'offline'");
$software_updates = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM software_inventory WHERE update_available = 1");
$active_bookings = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM lab_bookings WHERE DATE(booking_date) = CURDATE() AND status = 'confirmed'");
$pending_bookings = ict_q($ict_conn, "SELECT COUNT(*) as cnt FROM lab_bookings WHERE status = 'pending'");
$total_students = ict_q($students_conn, "SELECT COUNT(*) as cnt FROM students WHERE status = 'Active'");
$total_staff = ict_q($staff_conn, "SELECT COUNT(*) as cnt FROM staff WHERE status = 'Active'");

// Lists
$computers = ict_fetch($ict_conn, "SELECT * FROM lab_computers WHERE status != 'deleted' ORDER BY location, computer_name LIMIT 50");
$tickets = ict_fetch($ict_conn, "SELECT * FROM it_support_tickets ORDER BY FIELD(priority,'critical','high','medium','low'), created_at DESC LIMIT 20");
$bookings = ict_fetch($ict_conn, "SELECT * FROM lab_bookings ORDER BY booking_date DESC LIMIT 10");
$devices = ict_fetch($ict_conn, "SELECT * FROM network_devices ORDER BY device_type, device_name LIMIT 20");
$software = ict_fetch($ict_conn, "SELECT * FROM software_inventory ORDER BY software_name LIMIT 20");
$maintenance = ict_fetch($ict_conn, "SELECT * FROM maintenance_logs ORDER BY created_at DESC LIMIT 10");
$usage_stats = ict_fetch($ict_conn, "SELECT * FROM lab_usage_stats ORDER BY date DESC LIMIT 10");

// Recent activity
$recent_activities = [];
try {
    $r = $staff_conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 5");
    if ($r) $recent_activities = $r->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {}
if (empty($recent_activities)) {
    $recent_activities = [['activity' => 'Dashboard accessed', 'created_at' => date('Y-m-d H:i:s')]];
}

// Student search
$search_term = trim($_GET['student_search'] ?? '');
$found_students = [];
if ($search_term && $students_conn) {
    $like = '%' . $students_conn->real_escape_string($search_term) . '%';
    try {
        $r = $students_conn->query("SELECT id, student_id, full_name, index_number, program, phone, email, status FROM students WHERE full_name LIKE '$like' OR student_id LIKE '$like' OR index_number LIKE '$like' OR phone LIKE '$like' OR email LIKE '$like' ORDER BY full_name LIMIT 30");
        if ($r) $found_students = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Director ICT Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="dashboard-professional.css">
    <link rel="stylesheet" href="dashboard-mobile.css">
    <style>
        :root {
            --isnm-blue: #1e3a8a;
            --isnm-light-blue: #3b82f6;
            --isnm-green: #059669;
            --isnm-gold: #d97706;
            --isnm-dark-green: #0f4c3a;
            --isnm-teal: #0d9488;
            --isnm-cyan: #06b6d4;
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f4f8; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .dashboard-sidebar { width: 260px; background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); color: white; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .dashboard-sidebar .sidebar-header { padding: 24px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .dashboard-sidebar .sidebar-header img { width: 56px; height: 56px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2); margin-bottom: 8px; }
        .dashboard-sidebar .sidebar-header h4 { font-size: 1rem; font-weight: 700; margin: 0; }
        .dashboard-sidebar .sidebar-header small { opacity: 0.7; font-size: 0.8rem; }
        .sidebar-menu { padding: 12px 0; }
        .sidebar-menu .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.75); text-decoration: none; font-size: 0.88rem; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-menu .nav-link:hover { background: rgba(255,255,255,0.08); color: white; }
        .sidebar-menu .nav-link.active { background: rgba(59,130,246,0.15); color: #60a5fa; border-left-color: #3b82f6; }
        .sidebar-menu .nav-link i { width: 20px; text-align: center; font-size: 1rem; }
        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.08); margin-top: auto; }
        .dashboard-main { margin-left: 260px; flex: 1; min-height: 100vh; }
        .dashboard-header { background: white; padding: 20px 32px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .dashboard-header h1 { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
        .dashboard-header p { margin: 2px 0 0; color: #64748b; font-size: 0.88rem; }
        .header-right { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .date-time { display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 0.88rem; }
        .user-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
        .dashboard-content { padding: 28px 32px; }
        .content-section { display: none; }
        .content-section:first-of-type { display: block; }
        .content-section h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: linear-gradient(to bottom, #ffe082 0%, #ffe082 5px, #fef9e7 5px, #fef9e7 100%); border-radius: 14px; padding: 20px; transition: all 0.25s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .stat-card .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 12px; color: white; }
        .stat-card h3 { font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 4px; }
        .stat-card p { color: #64748b; font-size: 0.82rem; font-weight: 500; margin: 0; text-transform: uppercase; letter-spacing: 0.3px; }
        .card-blue .stat-icon { background: linear-gradient(135deg, #1e3a8a, #3b82f6); }
        .card-green .stat-icon { background: linear-gradient(135deg, #059669, #10b981); }
        .card-gold .stat-icon { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .card-rose .stat-icon { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .card-purple .stat-icon { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
        .card-teal .stat-icon { background: linear-gradient(135deg, #0d9488, #14b8a6); }
        .card-cyan .stat-icon { background: linear-gradient(135deg, #0891b2, #22d3ee); }
        .card-slate .stat-icon { background: linear-gradient(135deg, #475569, #94a3b8); }
        .section-card { background: linear-gradient(to bottom, #d7ccc8 0%, #d7ccc8 5px, #f0dcc8 5px, #f0dcc8 100%); border-radius: 14px; margin-bottom: 24px; overflow: hidden; }
        .section-card .card-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        .section-card .card-header h5 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px; }
        .section-card .card-body { padding: 16px 20px; }
        .search-bar { background: linear-gradient(135deg, #1e3a8a, #0f4c3a); padding: 20px; border-radius: 14px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(30,58,138,0.2); }
        .search-bar .form-control, .search-bar .form-select { border-radius: 10px; border: none; padding: 10px 14px; }
        .badge { font-weight: 600; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; }
        .priority-critical { color: #dc2626; font-weight: 700; }
        .priority-high { color: #ea580c; font-weight: 600; }
        .priority-medium { color: #ca8a04; }
        .priority-low { color: #6b7280; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .status-dot.online { background: #10b981; box-shadow: 0 0 6px rgba(16,185,129,0.5); }
        .status-dot.offline { background: #ef4444; }
        .status-dot.maintenance { background: #f59e0b; }
        .no-data { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .no-data i { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.5; }
        .activity-item { display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #1e3a8a, #3b82f6); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; font-size: 0.85rem; }
        .activity-content strong { font-size: 0.9rem; color: #1e293b; }
        .activity-content small { font-size: 0.78rem; }
        @media (max-width: 992px) {
            .dashboard-sidebar { width: 0; transform: translateX(-100%); transition: all 0.3s; }
            .dashboard-sidebar.open { width: 260px; transform: translateX(0); }
            .dashboard-main { margin-left: 0; }
            .dashboard-content { padding: 20px 16px; }
            .dashboard-header { padding: 16px 20px; }
        }
        .table > :not(caption) > * > * { padding: 10px 12px; vertical-align: middle; font-size: 0.85rem; }
        .table thead { background: #f8fafc; }
        .table thead th { font-weight: 700; color: #475569; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; }
        .modal-header { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .btn-ict { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; border: none; }
        .btn-ict:hover { background: linear-gradient(135deg, #1a3578, #1d4ed8); color: white; }
        .btn-ict-outline { border: 2px solid #1e3a8a; color: #1e3a8a; background: transparent; }
        .btn-ict-outline:hover { background: #1e3a8a; color: white; }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-4" style="z-index: 9999; min-width: 300px; animation: slideIn 0.4s ease;">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-4" style="z-index: 9999; min-width: 300px; animation: slideIn 0.4s ease;">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="dashboard-header">
                <div>
                    <h1>Director ICT Dashboard</h1>
                    <p>Technology Infrastructure & Systems Management</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-sm d-md-none btn-outline-secondary" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <div class="date-time"><i class="fas fa-calendar"></i> <span><?= date('l, F j, Y') ?></span></div>
                    <button class="btn btn-ict btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-user-plus"></i> Add Student</button>
                    <button class="btn btn-ict btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addComputerModal"><i class="fas fa-plus"></i> Add Computer</button>
                    <div class="user-menu d-flex align-items-center gap-2">
                        <img src="../images/default-avatar.png" alt="" class="user-avatar">
                        <span class="d-none d-md-inline"><?= htmlspecialchars($user_name) ?></span>
                    </div>
                </div>
            </div>

            <div class="dashboard-content">

                <!-- ═══ OVERVIEW ═══ -->
                <section id="overview" class="content-section">
                    <h2><i class="fas fa-tachometer-alt" style="color:var(--isnm-blue)"></i> System Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card card-blue"><div class="stat-icon"><i class="fas fa-desktop"></i></div><h3><?= $total_computers ?></h3><p>Total Computers</p></div>
                        <div class="stat-card card-green"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><h3><?= $computers_online ?></h3><p>Computers Online</p></div>
                        <div class="stat-card card-rose"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><h3><?= $computers_offline ?></h3><p>Computers Offline</p></div>
                        <div class="stat-card card-gold"><div class="stat-icon"><i class="fas fa-tools"></i></div><h3><?= $computers_maint ?></h3><p>Under Maintenance</p></div>
                        <div class="stat-card card-purple"><div class="stat-icon"><i class="fas fa-headset"></i></div><h3><?= $pending_tickets ?></h3><p>Pending Tickets</p></div>
                        <div class="stat-card card-teal"><div class="stat-icon"><i class="fas fa-wifi"></i></div><h3><?= $network_online ?>/<?= $network_online + $network_offline ?></h3><p>Network Devices Online</p></div>
                        <div class="stat-card card-cyan"><div class="stat-icon"><i class="fas fa-calendar-check"></i></div><h3><?= $active_bookings ?></h3><p>Today's Sessions</p></div>
                        <div class="stat-card card-slate"><div class="stat-icon"><i class="fas fa-download"></i></div><h3><?= $software_updates ?></h3><p>Software Updates</p></div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="section-card">
                                <div class="card-header"><h5><i class="fas fa-headset" style="color:var(--isnm-blue)"></i> Open IT Support Tickets</h5>
                                    <button class="btn btn-ict btn-sm" data-bs-toggle="modal" data-bs-target="#ticketModal"><i class="fas fa-plus"></i> New Ticket</button>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($tickets)): ?>
                                        <div class="no-data"><i class="fas fa-check-circle" style="color:#10b981"></i><p>No tickets — all clear!</p></div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead><tr><th>Ticket</th><th>Requester</th><th>Issue</th><th>Priority</th><th>Status</th><th>Created</th><th></th></tr></thead>
                                            <tbody>
                                                <?php foreach ($tickets as $t): ?>
                                                <tr>
                                                    <td><code><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                                                    <td><?= htmlspecialchars($t['requester_name']) ?></td>
                                                    <td><?= htmlspecialchars(substr($t['description'], 0, 40)) ?>...</td>
                                                    <td><span class="priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
                                                    <td><span class="badge bg-<?= $t['status'] === 'open' ? 'danger' : ($t['status'] === 'in_progress' ? 'warning text-dark' : ($t['status'] === 'resolved' ? 'success' : 'secondary')) ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
                                                    <td><small><?= date('M d, H:i', strtotime($t['created_at'])) ?></small></td>
                                                    <td>
                                                        <?php if ($t['status'] !== 'resolved' && $t['status'] !== 'closed'): ?>
                                                        <button class="btn btn-sm btn-outline-success" onclick="resolveTicket(<?= $t['id'] ?>)"><i class="fas fa-check"></i></button>
                                                        <?php endif; ?>
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
                        <div class="col-lg-4">
                            <div class="section-card">
                                <div class="card-header"><h5><i class="fas fa-history" style="color:var(--isnm-teal)"></i> Recent Activity</h5></div>
                                <div class="card-body">
                                    <?php foreach ($recent_activities as $a): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon"><i class="fas fa-check-circle"></i></div>
                                        <div class="activity-content">
                                            <strong><?= htmlspecialchars($a['activity'] ?? 'Activity') ?></strong>
                                            <small class="text-muted d-block"><?= date('M j, Y H:i', strtotime($a['created_at'])) ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="section-card">
                                <div class="card-header"><h5><i class="fas fa-info-circle" style="color:var(--isnm-blue)"></i> Institution Summary</h5></div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Active Students:</span><strong><?= $total_students ?></strong></div>
                                    <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Staff Accounts:</span><strong><?= $total_staff ?></strong></div>
                                    <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Pending Bookings:</span><strong><?= $pending_bookings ?></strong></div>
                                    <div class="d-flex justify-content-between py-2"><span class="text-muted">Network Devices:</span><strong><?= $network_online + $network_offline ?></strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ STUDENT MANAGEMENT ═══ -->
                <section id="students" class="content-section">
                    <h2><i class="fas fa-user-graduate" style="color:var(--isnm-blue)"></i> Student Management</h2>

                    <!-- Quick Add + Search row -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="d-flex gap-2">
                            <button class="btn btn-ict btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-user-plus me-1"></i> Add Student</button>
                        </div>
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="student_search" class="form-control form-control-sm" placeholder="Quick search by name, ID, phone..." value="<?= htmlspecialchars($search_term) ?>" style="min-width:250px">
                            <button type="submit" class="btn btn-ict-outline btn-sm"><i class="fas fa-search"></i></button>
                        </form>
                    </div>

                    <!-- Search Results (if any) -->
                    <?php if ($search_term): ?>
                    <div class="section-card mb-3">
                        <div class="card-header"><h5><i class="fas fa-search"></i> Search Results for "<?= htmlspecialchars($search_term) ?>"</h5></div>
                        <div class="card-body p-0">
                            <?php if (empty($found_students)): ?>
                                <div class="no-data"><i class="fas fa-search"></i><p>No students found</p></div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead><tr><th>ID</th><th>Name</th><th>Index</th><th>Program</th><th>Phone</th><th>Email</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($found_students as $s): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($s['student_id']) ?></code></td>
                                            <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($s['index_number'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($s['program'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                                            <td><small><?= htmlspecialchars($s['email'] ?? '—') ?></small></td>
                                            <td><span class="badge bg-<?= $s['status'] === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Full Student Set Viewer -->
                    <div class="section-card">
                        <?php renderStudentSetViewer($students_conn); ?>
                    </div>
                </section>

                <!-- ═══ LAB COMPUTERS ═══ -->
                <section id="computers" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h2 class="mb-0"><i class="fas fa-desktop" style="color:var(--isnm-blue)"></i> Lab Computers</h2>
                        <button class="btn btn-ict btn-sm" data-bs-toggle="modal" data-bs-target="#addComputerModal"><i class="fas fa-plus"></i> Add Computer</button>
                    </div>
                    <div class="section-card">
                        <div class="card-body p-0">
                            <?php if (empty($computers)): ?>
                            <div class="no-data"><i class="fas fa-desktop"></i><p>No computers registered yet</p></div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead><tr><th>ID</th><th>Name</th><th>Location</th><th>Status</th><th>IP Address</th><th>OS</th><th>Last Maintenance</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($computers as $c): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($c['computer_id']) ?></code></td>
                                            <td><?= htmlspecialchars($c['computer_name']) ?></td>
                                            <td><?= htmlspecialchars($c['location']) ?></td>
                                            <td><span class="badge bg-<?= $c['status'] === 'online' ? 'success' : ($c['status'] === 'maintenance' ? 'warning text-dark' : 'danger') ?>"><span class="status-dot <?= $c['status'] ?>"></span><?= ucfirst($c['status']) ?></span></td>
                                            <td><small><?= htmlspecialchars($c['ip_address'] ?? '—') ?></small></td>
                                            <td><small><?= htmlspecialchars($c['os_installed'] ?? '—') ?></small></td>
                                            <td><small><?= $c['last_maintenance'] ? date('M d, Y', strtotime($c['last_maintenance'])) : '—' ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- ═══ IT SUPPORT ═══ -->
                <section id="tickets" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h2 class="mb-0"><i class="fas fa-headset" style="color:var(--isnm-blue)"></i> IT Support Tickets</h2>
                        <button class="btn btn-ict btn-sm" data-bs-toggle="modal" data-bs-target="#ticketModal"><i class="fas fa-plus"></i> New Ticket</button>
                    </div>
                    <div class="section-card">
                        <div class="card-body p-0">
                            <?php if (empty($tickets)): ?>
                            <div class="no-data"><i class="fas fa-ticket-alt"></i><p>No support tickets</p></div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead><tr><th>#</th><th>Requester</th><th>Type</th><th>Issue</th><th>Priority</th><th>Status</th><th>Created</th><th></th></tr></thead>
                                    <tbody>
                                        <?php foreach ($tickets as $t): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                                            <td><?= htmlspecialchars($t['requester_name']) ?><br><small class="text-muted"><?= htmlspecialchars($t['requester_type']) ?></small></td>
                                            <td><span class="badge bg-info"><?= ucfirst($t['issue_type']) ?></span></td>
                                            <td><?= htmlspecialchars(substr($t['description'], 0, 60)) ?>...</td>
                                            <td><span class="priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
                                            <td><span class="badge bg-<?= $t['status'] === 'open' ? 'danger' : ($t['status'] === 'in_progress' ? 'warning text-dark' : ($t['status'] === 'resolved' ? 'success' : 'secondary')) ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
                                            <td><small><?= date('M d, H:i', strtotime($t['created_at'])) ?></small></td>
                                            <td>
                                                <?php if ($t['status'] !== 'resolved' && $t['status'] !== 'closed'): ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="resolveTicket(<?= $t['id'] ?>)"><i class="fas fa-check"></i> Resolve</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- ═══ BOOKINGS ═══ -->
                <section id="bookings" class="content-section">
                    <h2><i class="fas fa-calendar-alt" style="color:var(--isnm-blue)"></i> Lab Bookings</h2>
                    <div class="section-card">
                        <div class="card-body p-0">
                            <?php if (empty($bookings)): ?>
                            <div class="no-data"><i class="fas fa-calendar-times"></i><p>No bookings yet</p></div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead><tr><th>Reference</th><th>Course</th><th>Instructor</th><th>Date</th><th>Time Slot</th><th>Students</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($bookings as $b): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($b['booking_reference']) ?></code></td>
                                            <td><?= htmlspecialchars($b['course_name']) ?></td>
                                            <td><?= htmlspecialchars($b['instructor_name']) ?></td>
                                            <td><?= date('M d, Y', strtotime($b['booking_date'])) ?></td>
                                            <td><?= htmlspecialchars($b['time_slot']) ?></td>
                                            <td><?= (int)$b['number_of_students'] ?></td>
                                            <td><span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'danger') ?>"><?= ucfirst($b['status']) ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- ═══ NETWORK ═══ -->
                <section id="network" class="content-section">
                    <h2><i class="fas fa-network-wired" style="color:var(--isnm-blue)"></i> Network Devices</h2>
                    <div class="section-card">
                        <div class="card-body p-0">
                            <?php if (empty($devices)): ?>
                            <div class="no-data"><i class="fas fa-network-wired"></i><p>No network devices registered</p></div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead><tr><th>Device</th><th>Type</th><th>IP Address</th><th>Location</th><th>Status</th><th>Firmware</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($devices as $d): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($d['device_name']) ?></strong></td>
                                            <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $d['device_type'])) ?></span></td>
                                            <td><code><?= htmlspecialchars($d['ip_address']) ?></code></td>
                                            <td><?= htmlspecialchars($d['location'] ?? '—') ?></td>
                                            <td><span class="badge bg-<?= $d['status'] === 'online' ? 'success' : ($d['status'] === 'maintenance' ? 'warning text-dark' : 'danger') ?>"><span class="status-dot <?= $d['status'] ?>"></span><?= ucfirst($d['status']) ?></span></td>
                                            <td><small><?= htmlspecialchars($d['firmware_version'] ?? '—') ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- ═══ SOFTWARE ═══ -->
                <section id="software" class="content-section">
                    <h2><i class="fas fa-download" style="color:var(--isnm-blue)"></i> Software Inventory</h2>
                    <div class="section-card">
                        <div class="card-body p-0">
                            <?php if (empty($software)): ?>
                            <div class="no-data"><i class="fas fa-download"></i><p>No software registered</p></div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead><tr><th>Software</th><th>Version</th><th>License</th><th>Expiry</th><th>Installations</th><th>Update Available</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($software as $s): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($s['software_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($s['version'] ?? '—') ?></td>
                                            <td><span class="badge bg-<?= $s['license_type'] === 'commercial' ? 'warning text-dark' : ($s['license_type'] === 'educational' ? 'info' : 'success') ?>"><?= ucfirst($s['license_type']) ?></span></td>
                                            <td><?= $s['license_expiry'] ? date('M d, Y', strtotime($s['license_expiry'])) : '—' ?></td>
                                            <td><?= (int)$s['installation_count'] ?></td>
                                            <td><?= $s['update_available'] ? '<span class="badge bg-warning text-dark"><i class="fas fa-arrow-up me-1"></i>Update Available</span>' : '<span class="badge bg-success">Up to date</span>' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- ═══ MAINTENANCE ═══ -->
                <section id="maintenance" class="content-section">
                    <h2><i class="fas fa-tools" style="color:var(--isnm-blue)"></i> Maintenance Logs</h2>
                    <div class="section-card">
                        <div class="card-body p-0">
                            <?php if (empty($maintenance)): ?>
                            <div class="no-data"><i class="fas fa-check-circle" style="color:#10b981"></i><p>No maintenance records</p></div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead><tr><th>Computer</th><th>Type</th><th>Description</th><th>Performed By</th><th>Status</th><th>Scheduled</th><th>Completed</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($maintenance as $m): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($m['computer_id']) ?></code></td>
                                            <td><span class="badge bg-info"><?= ucfirst($m['maintenance_type']) ?></span></td>
                                            <td><?= htmlspecialchars(substr($m['description'], 0, 50)) ?></td>
                                            <td><?= htmlspecialchars($m['performed_by']) ?></td>
                                            <td><span class="badge bg-<?= $m['status'] === 'completed' ? 'success' : ($m['status'] === 'in_progress' ? 'warning text-dark' : ($m['status'] === 'scheduled' ? 'primary' : 'secondary')) ?>"><?= ucfirst(str_replace('_', ' ', $m['status'])) ?></span></td>
                                            <td><small><?= $m['scheduled_date'] ? date('M d, Y', strtotime($m['scheduled_date'])) : '—' ?></small></td>
                                            <td><small><?= $m['completed_date'] ? date('M d, Y', strtotime($m['completed_date'])) : '—' ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- ═══ NEWS ═══ -->
                <section id="news" class="content-section">
                    <h2><i class="fas fa-newspaper" style="color:var(--isnm-blue)"></i> News &amp; Announcements</h2>
                    <div class="section-card">
                        <?php renderNewsWidget($staff_conn, $website_conn, $user['id'] ?? 0, $user_name, $_SESSION['role'] ?? 'ICT Director', 5); ?>
                    </div>
                </section>

                <!-- ═══ REPORTS ═══ -->
                <section id="reports" class="content-section">
                    <h2><i class="fas fa-chart-bar" style="color:var(--isnm-blue)"></i> Lab Usage Reports</h2>
                    <div class="section-card">
                        <div class="card-body p-0">
                            <?php if (empty($usage_stats)): ?>
                            <div class="no-data"><i class="fas fa-chart-bar"></i><p>No usage statistics available</p></div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead><tr><th>Lab</th><th>Date</th><th>Sessions</th><th>Users</th><th>Peak Concurrent</th><th>Avg Duration</th><th>Computers Used</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($usage_stats as $u): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($u['lab_name']) ?></strong></td>
                                            <td><?= date('M d, Y', strtotime($u['date'])) ?></td>
                                            <td><?= (int)$u['total_sessions'] ?></td>
                                            <td><?= (int)$u['total_users'] ?></td>
                                            <td><?= (int)$u['peak_concurrent_users'] ?></td>
                                            <td><?= (int)$u['average_session_duration'] ?> min</td>
                                            <td><?= (int)$u['computers_used'] ?>/<?= (int)$u['computers_available'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <!-- ════ MODALS ════ -->

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="add_student">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label fw-semibold">Full Name *</label><input type="text" class="form-control" name="full_name" required></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Phone Number</label><input type="text" class="form-control" name="phone"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control" name="email"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Program *</label><select class="form-select" name="program" required>
                            <option value="">Select</option>
                            <option>Certificate in Nursing</option>
                            <option>Certificate in Midwifery</option>
                            <option>Diploma in Nursing</option>
                            <option>Diploma in Midwifery</option>
                            <option>Enrolled Comprehensive Nursing</option>
                            <option>Enrolled Psychiatric Nursing</option>
                        </select></div>
                        <div class="col-md-3"><label class="form-label fw-semibold">Gender</label><select class="form-select" name="gender"><option value="">Select</option><option>Male</option><option>Female</option></select></div>
                        <div class="col-md-3"><label class="form-label fw-semibold">Date of Birth</label><input type="date" class="form-control" name="date_of_birth"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Set / Intake Year</label><input type="text" class="form-control" name="set_name" value="<?= date('Y') ?>"></div>
                    </div>
                    <div class="mt-3 p-3 bg-light rounded">
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> A unique Student ID will be auto-generated (format: <code>ISNM/YYYY/RANDOM</code>)</small>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-ict"><i class="fas fa-save me-1"></i> Add Student</button></div>
            </form>
        </div>
    </div>

    <!-- Add Computer Modal -->
    <div class="modal fade" id="addComputerModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="add_computer">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Lab Computer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Computer ID *</label><input type="text" class="form-control" name="computer_id" required placeholder="e.g. LAB-C-001"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Computer Name *</label><input type="text" class="form-control" name="computer_name" required></div>
                        <div class="col-12"><label class="form-label fw-semibold">Location *</label><input type="text" class="form-control" name="location" required placeholder="e.g. Lab C - Floor 1"></div>
                        <div class="col-md-6"><label class="form-label">IP Address</label><input type="text" class="form-control" name="ip_address" placeholder="192.168.1.x"></div>
                        <div class="col-md-6"><label class="form-label">MAC Address</label><input type="text" class="form-control" name="mac_address" placeholder="AA:BB:CC:DD:EE:FF"></div>
                        <div class="col-md-6"><label class="form-label">Specifications</label><input type="text" class="form-control" name="specifications" placeholder="Intel i5, 8GB RAM"></div>
                        <div class="col-md-6"><label class="form-label">OS Installed</label><input type="text" class="form-control" name="os_installed" placeholder="Windows 11 Pro"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-ict"><i class="fas fa-save me-1"></i> Add Computer</button></div>
            </form>
        </div>
    </div>

    <!-- New Ticket Modal -->
    <div class="modal fade" id="ticketModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="create_ticket">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>New IT Support Ticket</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Requester Name *</label><input type="text" class="form-control" name="requester_name" required></div>
                        <div class="col-md-6"><label class="form-label">Requester Email</label><input type="email" class="form-control" name="requester_email"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Type *</label><select class="form-select" name="requester_type" required>
                            <option value="staff">Staff</option>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty</option>
                        </select></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Issue Type *</label><select class="form-select" name="issue_type" required>
                            <option value="hardware">Hardware</option>
                            <option value="software">Software</option>
                            <option value="network">Network</option>
                            <option value="account">Account</option>
                            <option value="other">Other</option>
                        </select></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Priority *</label><select class="form-select" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select></div>
                        <div class="col-12"><label class="form-label fw-semibold">Description *</label><textarea class="form-control" name="description" rows="4" required></textarea></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-ict"><i class="fas fa-save me-1"></i> Create Ticket</button></div>
            </form>
        </div>
    </div>

    <!-- Resolve Ticket Modal -->
    <div class="modal fade" id="resolveTicketModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="resolve_ticket">
                <input type="hidden" name="ticket_id" id="resolveTicketId">
                <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Resolve Support Ticket</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="form-label fw-semibold">Resolution Notes</label><textarea class="form-control" name="resolution_notes" rows="3" placeholder="Describe the resolution..."></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Mark Resolved</button></div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar navigation
            document.querySelectorAll('.sidebar-menu .nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href.startsWith('#')) {
                        e.preventDefault();
                        document.querySelectorAll('.sidebar-menu .nav-link').forEach(l => l.classList.remove('active'));
                        this.classList.add('active');
                        document.querySelectorAll('.content-section').forEach(s => s.style.display = 'none');
                        document.querySelector(href).style.display = 'block';
                        // Close sidebar on mobile
                        const sidebar = document.getElementById('sidebar');
                        if (sidebar.classList.contains('open')) sidebar.classList.remove('open');
                    }
                });
            });

            // Auto-dismiss alerts
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(a => {
                    const bs = new bootstrap.Alert(a);
                    bs.close();
                });
            }, 5000);
        });

        function resolveTicket(id) {
            document.getElementById('resolveTicketId').value = id;
            new bootstrap.Modal(document.getElementById('resolveTicketModal')).show();
        }
    </script>
</body>
</html>
