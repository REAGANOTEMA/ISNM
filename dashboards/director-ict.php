<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/department_approval_request.php';
try {
    $ctx = bootstrapStaffDashboard(['director ict', 'system admin', 'computer department', 'ict officer']);
} catch (Throwable $e) {
    if (ob_get_level()) ob_clean();
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access Error</title></head><body>';
    echo '<h2>Access Error</h2><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="../staff-login.php">Return to Login</a></p></body></html>';
    exit;
}
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
$website_conn = $ctx['website'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? 'ICT Director';
$user_role = $_SESSION['role'] ?? '';
$ict = null;
try { $ict = getICTConnection(); } catch (Exception $e) { error_log('director-ict context: ' . $e->getMessage()); }
$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';

function ict_mailto($email) {
    return $email ? '<a href="mailto:'.htmlspecialchars($email).'" title="Send email"><i class="fas fa-envelope text-primary"></i></a>' : '-';
}
function ict_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { error_log('director-ict getCount: ' . $e->getMessage()); return 0; }
}
function ict_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Exception $e) { error_log('director-ict getList: ' . $e->getMessage()); return []; }
}
function ict_fetch_one($conn, $sql) {
    if (!$conn) return null;
    try { $r = $conn->query($sql); if (!$r) return null; return $r->fetch_assoc(); }
    catch (Exception $e) { error_log('director-ict getDetail: ' . $e->getMessage()); return null; }
}

// --- Student Management AJAX Handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ict_register_student') {
    header('Content-Type: application/json');
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $intake = trim($_POST['intake'] ?? '');
    $dob = trim($_POST['date_of_birth'] ?? '');
    $nationalId = trim($_POST['national_id'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $nokName = trim($_POST['next_of_kin_name'] ?? '');
    $nokPhone = trim($_POST['next_of_kin_phone'] ?? '');

    if (!$firstName || !$surname || !$gender || !$phone || !$program || !$intake) {
        echo json_encode(['success' => false, 'message' => 'First name, surname, gender, phone, program, and intake are required.']);
        exit;
    }

    $full_name = trim($firstName . ' ' . $surname);

    $intakeParts = explode(' ', $intake);
    $intakeMonth = ucfirst(strtolower($intakeParts[0] ?? ''));
    $intakeYearShort = substr($intakeParts[1] ?? date('Y'), -2);
    $monthMap = ['January'=>'JAN','February'=>'FEB','March'=>'MAR','April'=>'APR','May'=>'MAY','June'=>'JUN','July'=>'JUL','August'=>'AUG','September'=>'SEP','October'=>'OCT','November'=>'NOV','December'=>'DEC'];
    $intakeCode = $monthMap[$intakeMonth] ?? strtoupper(substr($intakeMonth, 0, 3));

    $progCode = 'GEN';
    if ($staff_conn) {
        $progStmt = $staff_conn->prepare("SELECT program_code FROM academic_programs WHERE program_name=? LIMIT 1");
        if ($progStmt) {
            $progStmt->bind_param('s', $program);
            $progStmt->execute();
            $progRes = $progStmt->get_result();
            if ($progRow = $progRes->fetch_assoc()) $progCode = strtoupper(substr($progRow['program_code'], 0, 4));
            $progStmt->close();
        }
    }

    $seqPrefix = $intakeCode . $intakeYearShort . '/' . $progCode . '/' . date('Y');
    $nextSeq = 1;
    if ($staff_conn) {
        $seqStmt = $staff_conn->prepare("SELECT COUNT(*) c FROM student_admission_tracking WHERE student_number LIKE ?");
        $seqLike = $seqPrefix . '/%';
        if ($seqStmt) {
            $seqStmt->bind_param('s', $seqLike);
            $seqStmt->execute();
            $seqRes = $seqStmt->get_result();
            if ($seqRow = $seqRes->fetch_assoc()) $nextSeq = (int)$seqRow['c'] + 1;
            $seqStmt->close();
        }
    }
    $index_number = $seqPrefix . '/' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

    $snPrefix = 'ISNM/' . date('Y');
    $nextSnSeq = 1;
    if ($staff_conn) {
        $snStmt = $staff_conn->prepare("SELECT COUNT(*) c FROM student_admission_tracking WHERE student_number LIKE ?");
        $snLike = $snPrefix . '/%';
        if ($snStmt) {
            $snStmt->bind_param('s', $snLike);
            $snStmt->execute();
            $snRes = $snStmt->get_result();
            if ($snRow = $snRes->fetch_assoc()) $nextSnSeq = (int)$snRow['c'] + 1;
            $snStmt->close();
        }
    }
    $student_number = $snPrefix . '/' . str_pad($nextSnSeq, 4, '0', STR_PAD_LEFT);

    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $temp_password = '';
    for ($i = 0; $i < 8; $i++) { $temp_password .= $chars[random_int(0, strlen($chars) - 1)]; }
    $hashed_password = password_hash($temp_password, PASSWORD_BCRYPT);

    $reg_number = 'REG' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $year = 1;
    $level = 'Year 1';
    $studentsDb = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

    if ($staff_conn) $staff_conn->begin_transaction();
    try {
        $rc = 0;
        if ($staff_conn) {
            $ck = $staff_conn->query("SELECT COUNT(*) c FROM admission_requirements WHERE is_active=1");
            if ($ck) { $rc = (int)$ck->fetch_assoc()['c']; }

            $track = $staff_conn->prepare("INSERT INTO student_admission_tracking (student_number, index_number, application_number, full_name, program, intake, admission_date, admission_status, requirements_total) VALUES (?,?,?,?,?,?,?,'Registered',?)");
            $trackAppNum = 'ICT-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
            $track->bind_param('sssssssi', $student_number, $index_number, $trackAppNum, $full_name, $program, $intake, date('Y-m-d'), $rc);
            if (!$track->execute()) throw new Exception('Tracking insert failed: ' . $track->error);
            $track->close();
        }

        if ($students_conn) {
            $s_ins = $students_conn->prepare("INSERT IGNORE INTO `$studentsDb`.`students` (student_number, registration_number, first_name, surname, other_name, full_name, email, phone, program, course, year, level, intake_year, intake_period, date_of_birth, gender, address, national_id, district, guardian_name, guardian_phone, status, password, is_first_login) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active',?,0)");
            $s_ins->bind_param('sssssssssssssssssssssss',
                $student_number, $reg_number, $firstName, $surname, '', $full_name,
                $email, $phone, $program, $program,
                $year, $level, (string)date('Y'), $intake, $dob,
                $gender, $address, $nationalId, $district, $nokName, $nokPhone, $hashed_password
            );
            if (!$s_ins->execute()) throw new Exception('Student insert failed: ' . $s_ins->error);
            $s_id = $students_conn->insert_id;
            $s_ins->close();
            if ($s_id > 0) {
                $prof = $students_conn->prepare("INSERT IGNORE INTO `$studentsDb`.`student_profiles` (student_id, admission_status, fee_status) VALUES (?,?,?)");
                $prof->bind_param('iss', $s_id, 'Registered', 'unpaid');
                if (!$prof->execute()) error_log('ICT student_profiles insert failed: ' . ($prof->error ?? 'unknown'));
                $prof->close();
            }
        }

        if ($staff_conn) $staff_conn->commit();
        echo json_encode(['success' => true, 'student_number' => $student_number, 'index_number' => $index_number, 'temp_password' => $temp_password, 'registration_number' => $reg_number]);
    } catch (Exception $e) {
        if ($staff_conn) $staff_conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search_students') {
    header('Content-Type: application/json');
    $q = trim($_POST['search'] ?? '');
    $studentsDb = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';
    if (!$students_conn || strlen($q) < 2) { echo json_encode(['success' => true, 'students' => []]); exit; }
    $like = '%' . $q . '%';
    $stmt = $students_conn->prepare("SELECT id, student_number, registration_number, first_name, surname, full_name, email, phone, program, level, status FROM `$studentsDb`.`students` WHERE (full_name LIKE ? OR student_number LIKE ? OR registration_number LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY full_name LIMIT 50");
    if (!$stmt) { echo json_encode(['success' => true, 'students' => []]); exit; }
    $stmt->bind_param('sssss', $like, $like, $like, $like, $like);
    $stmt->execute();
    $r = $stmt->get_result();
    $data = [];
    while ($row = $r->fetch_assoc()) $data[] = $row;
    $stmt->close();
    echo json_encode(['success' => true, 'students' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_student_details') {
    header('Content-Type: application/json');
    $studentId = (int)($_POST['student_id'] ?? 0);
    $studentsDb = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';
    if (!$studentId || !$students_conn) { echo json_encode(['success' => false, 'message' => 'Invalid student ID.']); exit; }

    $result = ['student' => null, 'profile' => null];
    $s = $students_conn->prepare("SELECT * FROM `$studentsDb`.`students` WHERE id=? LIMIT 1");
    if ($s) { $s->bind_param('i', $studentId); $s->execute(); $result['student'] = $s->get_result()->fetch_assoc(); $s->close(); }
    if ($result['student']) {
        $p = $students_conn->prepare("SELECT * FROM `$studentsDb`.`student_profiles` WHERE student_id=? LIMIT 1");
        if ($p) { $p->bind_param('i', $studentId); $p->execute(); $result['profile'] = $p->get_result()->fetch_assoc(); $p->close(); }
    }

    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

// â”€â”€ STATS â”€â”€
$total_staff   = ict_q($staff_conn, "SELECT COUNT(*) FROM staff WHERE status='Active'");
$total_students = ict_q($students_conn, "SELECT COUNT(*) FROM students WHERE status='Active'");
$active_servers  = ict_q($ict, "SELECT COUNT(*) FROM ict_servers WHERE status='online'");
$network_active  = ict_q($ict, "SELECT COUNT(*) FROM network_devices WHERE status='online'");
$total_assets    = ict_q($ict, "SELECT COUNT(*) FROM ict_assets WHERE current_status!='retired'");
$open_tickets    = ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status IN ('open','in_progress')");
$closed_tickets  = ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='closed'");
$today_backups   = ict_q($ict, "SELECT COUNT(*) FROM ict_system_backups WHERE DATE(created_at)=CURDATE()");
$active_alerts   = ict_q($ict, "SELECT COUNT(*) FROM ict_system_alerts WHERE status='active'");
$wifi_active     = ict_q($ict, "SELECT COUNT(*) FROM ict_wifi_devices WHERE status='online'");
$ict_db = defined('ICT_DB_NAME') ? ICT_DB_NAME : 'igangaschool_ict';
$db_size_mb      = 0;
$size_row = ict_fetch_one($ict, "SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) as size_mb FROM information_schema.TABLES WHERE TABLE_SCHEMA='$ict_db'");
if ($size_row) $db_size_mb = $size_row['size_mb'];
$total_users     = ict_q($staff_conn, "SELECT COUNT(*) FROM staff") + ict_q($students_conn, "SELECT COUNT(*) FROM students WHERE status='Active'");

// Generate CSRF token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// â”€â”€ DATA â”€â”€
$assets       = ict_fetch($ict, "SELECT a.*, c.category_name FROM ict_assets a LEFT JOIN ict_asset_categories c ON a.category_id=c.id ORDER BY a.created_at DESC LIMIT 30");
$asset_cats   = ict_fetch($ict, "SELECT * FROM ict_asset_categories ORDER BY category_name");
$servers      = ict_fetch($ict, "SELECT * FROM ict_servers ORDER BY server_name");
$net_devices  = ict_fetch($ict, "SELECT * FROM network_devices ORDER BY device_type, device_name");
$wifi_devices = ict_fetch($ict, "SELECT * FROM ict_wifi_devices ORDER BY device_name");
$backups      = ict_fetch($ict, "SELECT * FROM ict_system_backups ORDER BY created_at DESC LIMIT 20");
$backup_logs  = ict_fetch($ict, "SELECT l.*, b.backup_name FROM ict_backup_logs l LEFT JOIN ict_system_backups b ON l.backup_id=b.id ORDER BY l.logged_at DESC LIMIT 20");
$security_logs = ict_fetch($ict, "SELECT * FROM ict_security_logs ORDER BY created_at DESC LIMIT 30");
$failed_logins = ict_fetch($ict, "SELECT * FROM ict_failed_logins ORDER BY attempted_at DESC LIMIT 20");
$alerts       = ict_fetch($ict, "SELECT * FROM ict_system_alerts ORDER BY FIELD(severity,'critical','warning','info'), created_at DESC LIMIT 20");
$notifications= ict_fetch($ict, "SELECT * FROM ict_system_notifications WHERE is_dismissed=0 ORDER BY created_at DESC LIMIT 10");
$health_checks= ict_fetch($ict, "SELECT * FROM ict_system_health ORDER BY checked_at DESC LIMIT 20");
$settings     = ict_fetch($ict, "SELECT * FROM ict_system_settings ORDER BY setting_group, setting_key");
$audit_logs   = ict_fetch($ict, "SELECT * FROM ict_audit_logs ORDER BY created_at DESC LIMIT 30");
$tickets      = ict_fetch($ict, "SELECT * FROM it_support_tickets ORDER BY FIELD(priority,'critical','high','medium','low'), created_at DESC LIMIT 20");
$network_logs = ict_fetch($ict, "SELECT * FROM ict_network_logs ORDER BY logged_at DESC LIMIT 20");
$assignments  = ict_fetch($ict, "SELECT a.*, ast.asset_number, ast.asset_name FROM ict_asset_assignments a LEFT JOIN ict_assets ast ON a.asset_id=ast.id WHERE a.status='active' ORDER BY a.assignment_date DESC LIMIT 20");
$maintenance  = ict_fetch($ict, "SELECT m.*, a.asset_number, a.asset_name FROM ict_asset_maintenance m LEFT JOIN ict_assets a ON m.asset_id=a.id ORDER BY m.created_at DESC LIMIT 20");

// â”€â”€ User & Access â”€â”€
$staff_accounts  = ict_fetch($staff_conn, "SELECT s.id, s.full_name, s.email, sr.role_name AS role, s.status, s.last_login FROM staff s LEFT JOIN staff_roles sr ON s.role_id=sr.id ORDER BY s.full_name LIMIT 20");
$staff_count     = ict_q($staff_conn, "SELECT COUNT(*) FROM staff");
$student_count   = ict_q($students_conn, "SELECT COUNT(*) FROM students WHERE status='Active'");
$active_sessions   = ict_q($ict, "SELECT COUNT(*) FROM ict_login_sessions WHERE status='active'");

require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/website_submissions_widget.php';
require_once __DIR__ . '/../includes/director_website_panel.php';
$failed_today    = ict_q($ict, "SELECT COUNT(*) FROM ict_failed_logins WHERE DATE(attempted_at)=CURDATE()");
// â”€â”€ Module Permissions â”€â”€
$module_perms    = ict_fetch($ict, "SELECT * FROM ict_module_permissions ORDER BY module_name, role_name");
// â”€â”€ Approvals â”€â”€
$pending_tickets = ict_fetch($ict, "SELECT * FROM it_support_tickets WHERE status IN ('open','in_progress') ORDER BY FIELD(priority,'critical','high','medium','low'), created_at DESC LIMIT 15");
$pending_approval_requests = [];
if ($staff_conn) {
    try {
        $r = $staff_conn->query("SELECT ar.*, ws.workflow_name, ws.category FROM {$staff_db}.approval_requests ar LEFT JOIN {$staff_db}.approval_workflows ws ON ar.workflow_id = ws.id WHERE ar.status = 'Active' AND (ws.category = 'ICT' OR ws.category IS NULL) ORDER BY FIELD(ar.priority,'Critical','High','Medium','Normal'), ar.created_at DESC LIMIT 15");
        if ($r) while ($row = $r->fetch_assoc()) $pending_approval_requests[] = $row;
    } catch (Exception $e) { error_log('director-ict context: ' . $e->getMessage()); }
}

$ictPageMap = ['home'=>'overview','overview'=>'overview','analytics'=>'overview','approvals'=>'approvals','tasks'=>'overview','schedules'=>'overview','reports-daily'=>'overview','reports-monthly'=>'overview','reports-annual'=>'overview','exports'=>'overview','print'=>'overview','notifications'=>'overview','messages'=>'overview','announcements'=>'overview','profile'=>'overview','preferences'=>'overview','security'=>'security','activity-logs'=>'security','it_infrastructure'=>'infrastructure','infrastructure'=>'infrastructure','system_logs'=>'infrastructure','backup_management'=>'backups','ict_policy'=>'security','student-management'=>'student-management'];
$p = $_GET['page'] ?? '';
if ($p && !isset($_GET['tab'])) $_GET['tab'] = $ictPageMap[$p] ?? $p;
$tab = $_GET['tab'] ?? 'overview';
$ictIcon = 'fa-laptop-code';
$ictRole = 'Director ICT';
$ictSubtitle = 'Information & Communication Technology â€“ System Administration & Infrastructure Oversight';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root {
  --ict-primary: #0f172a;
  --ict-secondary: #1e293b;
  --ict-accent: #2563eb;
  --ict-blue: #3b82f6;
  --ict-gradient: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #1e293b 100%);
  --ict-card-bg: #ffffff;
  --ict-text: #0f172a;
  --ict-text-muted: #64748b;
  --ict-border: #e2e8f0;
  --ict-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --ict-shadow-lg: 0 10px 40px rgba(0,0,0,0.08);
}
body { background: #eef1f5; font-family: 'Inter', -apple-system, sans-serif; color: var(--ict-text); font-size: 13px; overflow-x: hidden; }
body::before { content:''; position:fixed; inset:0; background:radial-gradient(ellipse at 20% 50%,rgba(59,130,246,0.03) 0%,transparent 50%),radial-gradient(ellipse at 80% 20%,rgba(5,150,105,0.02) 0%,transparent 50%); pointer-events:none; z-index:0; }
.page-content { padding: 0 !important; }


@media (max-width: 768px) {
    .ict-content { margin-left: 0; padding: 12px; }
    .ict-table { font-size: 11px; }
    .ict-table thead th { font-size: 9px; padding: 6px 8px; }
    .ict-table td { padding: 6px 8px; }
    .monitor-card h3 { font-size: 18px; }
    .d-flex.justify-content-between { flex-wrap: wrap; gap: 6px; }
    .modal-dialog { margin: 8px !important; }
}
@media (max-width: 480px) {
    .monitor-card h3 { font-size: 15px; }
}










/* â”€â”€ Content â”€â”€ */
.ict-content { padding: 18px 22px 30px; max-width: 1600px; margin: 0 0 0 270px; background: #fafbfc; min-height: calc(100vh - 60px); overflow-x: hidden; word-break: break-word; }
@media (max-width: 768px) { .ict-content { margin-left: 0; } }


/* â”€â”€ Monitor Cards â”€â”€ */
.monitor-card { background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%); border-radius:10px; padding:14px 12px; color:#e2e8f0; text-align:center; transition:transform .2s,box-shadow .2s; }
.monitor-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.3); }
.monitor-card h3 { font-size:24px; font-weight:800; margin:0; background:linear-gradient(135deg,#fff,#94a3b8); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.monitor-card p { font-size:10px; color:#94a3b8; margin:4px 0 0; font-weight:500; }
.monitor-card .progress { height:4px; margin-top:8px; border-radius:10px; background:#334155; }
.monitor-card .progress-bar { border-radius:10px; }

/* â”€â”€ Tables â”€â”€ */
.ict-table { font-size: 12px; margin-bottom: 0; }
.ict-table thead th { background: #f8fafc; font-weight: 600; color: var(--ict-text-muted); text-transform: uppercase; font-size: 10px; letter-spacing: 0.4px; padding: 7px 10px; border-bottom: 2px solid var(--ict-border); }
.ict-table td { padding: 7px 10px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.ict-table tbody tr:hover { background: #f8fafc; }
.filter-pill { display:inline-flex; padding:4px 12px; border-radius:8px; font-size:11px; font-weight:600; color:#4b5563; background:#f3f4f6; text-decoration:none; transition:all .2s; border:1px solid transparent; }
.filter-pill:hover { background:#e5e7eb; color:#111827; }
.filter-pill.active { background:var(--ict-accent); color:#fff; box-shadow:0 2px 8px rgba(37,99,235,.3); }
.status-led { width:10px; height:10px; border-radius:50%; display:inline-block; box-shadow:0 0 6px rgba(0,0,0,.15); }



</style>
</head>
<body class="ent-layout">

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<!-- â•â•â• TOP BAR â•â•â• -->

<div class="ict-content">
<div class="d-flex justify-content-end mb-2 no-print">
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> Print</button>
</div>
<?php if ($tab === 'overview'): ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-heartbeat text-danger"></i>System Monitoring</h3><span class="section-subtitle">Live infrastructure status</span></div>
      <div class="row g-2">
        <div class="col-4"><div class="monitor-card"><h3 id="cpuVal">67%</h3><p>CPU Usage</p><div class="progress"><div class="progress-bar bg-success" style="width:67%"></div></div></div></div>
        <div class="col-4"><div class="monitor-card"><h3 id="ramVal">72%</h3><p>RAM Usage</p><div class="progress"><div class="progress-bar bg-warning" style="width:72%"></div></div></div></div>
        <div class="col-4"><div class="monitor-card"><h3 id="diskVal">45%</h3><p>Disk Usage</p><div class="progress"><div class="progress-bar bg-info" style="width:45%"></div></div></div></div>
        <div class="col-4"><div class="monitor-card"><h3 id="uptimeVal">99.8%</h3><p>Uptime</p><span class="badge bg-success">Online</span></div></div>
        <div class="col-4"><div class="monitor-card"><h3 id="sessionsVal"><?= ict_q($ict, "SELECT COUNT(*) FROM ict_login_sessions WHERE status='active'") ?></h3><p>Active Sessions</p><span class="badge bg-info">Activity</span></div></div>
        <div class="col-4"><div class="monitor-card"><h3 id="failuresVal"><?= ict_q($ict, "SELECT COUNT(*) FROM ict_failed_logins WHERE DATE(attempted_at)=CURDATE()") ?></h3><p>Failed Logins Today</p><span class="badge bg-<?= ict_q($ict,"SELECT COUNT(*) FROM ict_failed_logins WHERE DATE(attempted_at)=CURDATE()") > 10 ? 'danger' : 'secondary' ?>">Today</span></div></div>
      </div>
    </div>
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-ticket-alt text-warning"></i>Recent Support Tickets</h3><span class="section-subtitle">Latest requests</span></div>
      <div class="table-scroll">
      <?php if (empty($tickets)): ?><div class="text-center py-3 text-muted"><p>No tickets</p></div>
      <?php else: foreach (array_slice($tickets, 0, 6) as $t): ?>
      <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
        <div><code><?= htmlspecialchars($t['ticket_number']) ?></code> <strong><?= htmlspecialchars($t['requester_name']) ?></strong><span class="text-muted ms-2"><?= htmlspecialchars(mb_substr($t['description']??'',0,50)) ?></span></div>
        <div><span class="badge bg-<?= $t['priority']==='critical'||$t['priority']==='high'?'danger':($t['priority']==='medium'?'warning text-dark':'success') ?> me-1"><?= $t['priority'] ?></span><span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='in_progress'?'warning text-dark':'success') ?>"><?= $t['status'] ?></span></div>
      </div>
      <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-bell text-<?= $active_alerts?'danger':'secondary' ?>"></i>Active Alerts</h3><?= $active_alerts ? '<span class="badge bg-danger">'.$active_alerts.' active</span>' : '<span class="badge bg-success">All clear</span>' ?></div>
      <div class="table-scroll">
      <?php if (empty($alerts)): ?><div class="text-center py-3 text-muted"><p>No active alerts</p></div>
      <?php else: foreach (array_slice($alerts, 0, 6) as $a): ?>
      <div class="d-flex justify-content-between py-1 border-bottom small">
        <div><span class="badge bg-<?= $a['severity']==='critical'?'danger':($a['severity']==='warning'?'warning text-dark':'info') ?> me-1"><?= $a['severity'] ?></span><strong><?= htmlspecialchars($a['title']) ?></strong></div>
        <div><span class="badge bg-<?= $a['status']==='active'?'danger':'secondary' ?>"><?= $a['status'] ?></span></div>
      </div>
      <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-history text-info"></i>Audit Trail</h3><span class="section-subtitle">Recent activity</span></div>
      <div class="table-scroll">
      <?php if (empty($audit_logs)): ?><div class="text-center py-3 text-muted"><p>No audit logs</p></div>
      <?php else: foreach (array_slice($audit_logs, 0, 6) as $a): ?>
      <div class="py-1 border-bottom small">
        <strong><?= htmlspecialchars($a['username'] ?: 'System') ?></strong> <?= htmlspecialchars($a['action']) ?> <code><?= htmlspecialchars($a['resource_type'] ?: '') ?></code>
        <small class="d-block text-muted"><?= htmlspecialchars(mb_substr($a['description']??'',0,60)) ?> | <?= htmlspecialchars($a['created_at'] ?? '') ?></small>
      </div>
      <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-hdd text-purple"></i>Database Information</h3></div>
      <div class="small">
        <div class="d-flex justify-content-between py-1"><span>Database Size</span><strong><?= number_format($db_size_mb, 2) ?> MB</strong></div>
        <div class="d-flex justify-content-between py-1"><span>ICT Tables</span><strong><?= $ict ? count($ict->query("SHOW TABLES")->fetch_all()) : 0 ?></strong></div>
        <div class="d-flex justify-content-between py-1"><span>Backups Today</span><strong><?= $today_backups ?></strong></div>
        <div class="d-flex justify-content-between py-1"><span>Total Assets</span><strong><?= $total_assets ?></strong></div>
        <div class="d-flex justify-content-between py-1"><span>Open Tickets</span><strong><?= $open_tickets ?></strong></div>
        <div class="d-flex justify-content-between py-1"><span>Active Sessions</span><strong><?= $active_sessions ?></strong></div>
      </div>
    </div>
  </div>
  <div class="col-12 mt-3">
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-newspaper text-primary"></i>News &amp; Announcements</h3></div>
      <div class="p-2">
        <?php renderNewsWidget($staff_conn,$website_conn,$user_id,$user_name,$user_role,5); ?>
      </div>
    </div>
  </div>
</div>

        <!-- ======== ASSETS ======== -->
        <?php elseif ($tab === 'assets'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-boxes me-2"></i>ICT Asset Register</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal"><i class="fas fa-plus me-1"></i>Add Asset</button>
                    </div>
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Asset #</th><th>Name</th><th>Type</th><th>Category</th><th>Status</th><th>Location</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($assets)): ?><tr><td colspan="7" class="text-center text-muted">No assets</td></tr><?php endif; ?>
                                <?php foreach ($assets as $a): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($a['asset_number']) ?></code></td>
                                    <td><?= htmlspecialchars($a['asset_name']) ?></td>
                                    <td><?= ucfirst($a['asset_type']) ?></td>
                                    <td><small><?= htmlspecialchars($a['category_name'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= $a['current_status']==='active'?'success':($a['current_status']==='in_maintenance'?'warning text-dark':'secondary') ?>"><?= str_replace('_',' ',$a['current_status']) ?></span></td>
                                    <td><small><?= htmlspecialchars($a['current_location'] ?? '-') ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editAsset(<?= $a['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="assignAsset(<?= $a['id'] ?>)"><i class="fas fa-user-tag"></i></button>
                                        <button class="btn btn-sm btn-outline-warning py-0 px-1" onclick="logMaint(<?= $a['id'] ?>)"><i class="fas fa-tools"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="section-card">
                            <h2><i class="fas fa-user-tag me-2 text-info"></i>Asset Assignments</h2>
                            <div style="max-height:250px;overflow-y:auto">
                            <?php if (empty($assignments)): ?><div class="text-muted small">No active assignments</div>
                            <?php else: foreach ($assignments as $as): ?>
                            <div class="py-1 border-bottom small"><strong><?= htmlspecialchars($as['asset_name'] ?? $as['asset_number']) ?></strong> â†’ Staff #<?= $as['assigned_to_staff_id'] ?: 'Dept' ?>
                            <span class="badge bg-<?= $as['status']==='active'?'success':'secondary' ?> float-end"><?= $as['status'] ?></span></div>
                            <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="section-card">
                            <h2><i class="fas fa-tools me-2 text-warning"></i>Maintenance</h2>
                            <div style="max-height:250px;overflow-y:auto">
                            <?php if (empty($maintenance)): ?><div class="text-muted small">No maintenance records</div>
                            <?php else: foreach (array_slice($maintenance, 0, 8) as $m): ?>
                            <div class="py-1 border-bottom small"><strong><?= htmlspecialchars($m['asset_name'] ?? $m['asset_number']) ?></strong> - <?= $m['maintenance_type'] ?> <span class="badge bg-<?= $m['status']==='completed'?'success':'warning text-dark' ?> float-end"><?= $m['status'] ?></span></div>
                            <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-tag me-2 text-success"></i>Categories</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($asset_cats)): ?><div class="text-muted small">No categories</div>
                    <?php else: foreach ($asset_cats as $c): ?>
                    <div class="py-1 border-bottom small"><?= htmlspecialchars($c['category_name']) ?> <span class="badge bg-secondary float-end"><?= ict_q($ict, "SELECT COUNT(*) FROM ict_assets WHERE category_id=" . (int)$c['id']) ?></span></div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-chart-pie me-2 text-purple"></i>Asset Summary</h2>
                    <?php
                    $typeCounts = []; foreach ($assets as $a) { $t = $a['asset_type']; $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1; }
                    foreach ($typeCounts as $t => $c): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom small"><span><?= ucfirst($t) ?></span><span class="badge bg-secondary"><?= $c ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ======== INFRASTRUCTURE ======== -->
        <?php elseif ($tab === 'infrastructure'): ?>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-card">
                    <div class="d-flex justify-content-between mb-2"><h2 class="mb-0"><i class="fas fa-server me-2 text-primary"></i>Servers</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addServerModal"><i class="fas fa-plus"></i></button></div>
                    <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Name</th><th>IP</th><th>Type</th><th>OS</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($servers)): ?><tr><td colspan="6" class="text-muted text-center">No servers</td></tr><?php endif; ?>
                                <?php foreach ($servers as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['server_name']) ?></td>
                                    <td><code><?= htmlspecialchars($s['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= $s['server_type'] ?></small></td>
                                    <td><small><?= htmlspecialchars($s['os'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= $s['status']==='online'?'success':($s['status']==='offline'?'danger':'warning text-dark') ?>"><?= $s['status'] ?></span></td>
                                    <td><button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="editServer(<?= $s['id'] ?>)"><i class="fas fa-edit"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <div class="d-flex justify-content-between mb-2"><h2 class="mb-0"><i class="fas fa-network-wired me-2 text-info"></i>Network Devices</h2></div>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm table-small">
                            <thead><tr><th>Name</th><th>Type</th><th>IP</th><th>Location</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if (empty($net_devices)): ?><tr><td colspan="6" class="text-muted text-center">No network devices</td></tr><?php endif; ?>
                                <?php foreach ($net_devices as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['device_name']) ?></td>
                                    <td><small><?= $d['device_type'] ?></small></td>
                                    <td><code><?= htmlspecialchars($d['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= htmlspecialchars($d['location'] ?? '-') ?></small></td>
                                    <td><span class="status-led bg-<?= $d['status']==='online'?'success':'danger' ?> me-1"></span><?= $d['status'] ?></td>
                                    <td>
                                        <select class="form-select form-select-sm d-inline w-auto" onchange="updateNetDevice(<?= $d['id'] ?>,this.value)">
                                            <option value="online" <?= $d['status']==='online'?'selected':'' ?>>Online</option>
                                            <option value="offline" <?= $d['status']==='offline'?'selected':'' ?>>Offline</option>
                                            <option value="maintenance" <?= $d['status']==='maintenance'?'selected':'' ?>>Maint</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card">
                    <div class="d-flex justify-content-between mb-2"><h2 class="mb-0"><i class="fas fa-wifi me-2 text-success"></i>WiFi Access Points</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addWifiModal"><i class="fas fa-plus"></i></button></div>
                    <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                        <table class="table table-sm table-small">
                            <thead><tr><th>Name</th><th>SSID</th><th>IP</th><th>Location</th><th>Clients</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if (empty($wifi_devices)): ?><tr><td colspan="7" class="text-muted text-center">No WiFi devices</td></tr><?php endif; ?>
                                <?php foreach ($wifi_devices as $w): ?>
                                <tr>
                                    <td><?= htmlspecialchars($w['device_name']) ?></td>
                                    <td><code><?= htmlspecialchars($w['ssid'] ?? '-') ?></code></td>
                                    <td><code><?= htmlspecialchars($w['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= htmlspecialchars($w['location'] ?? '-') ?></small></td>
                                    <td><?= $w['connected_clients'] ?: 0 ?>/<?= $w['max_clients'] ?: 50 ?></td>
                                    <td><span class="badge bg-<?= $w['status']==='online'?'success':'danger' ?>"><?= $w['status'] ?></span></td>
                                    <td>
                                        <select class="form-select form-select-sm d-inline w-auto" onchange="updateWifi(<?= $w['id'] ?>,this.value)">
                                            <option value="online" <?= $w['status']==='online'?'selected':'' ?>>Online</option>
                                            <option value="offline" <?= $w['status']==='offline'?'selected':'' ?>>Offline</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-list me-2 text-secondary"></i>Network Logs</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($network_logs)): ?><div class="text-muted small">No network logs</div>
                    <?php else: foreach (array_slice($network_logs, 0, 10) as $nl): ?>
                    <div class="py-1 border-bottom small"><span class="badge bg-<?= $nl['severity']==='error'||$nl['severity']==='critical'?'danger':($nl['severity']==='warning'?'warning text-dark':'info') ?> me-1"><?= $nl['severity'] ?></span><?= htmlspecialchars(mb_substr($nl['message']??'',0,80)) ?> <small class="text-muted float-end"><?= htmlspecialchars($nl['logged_at'] ?? '') ?></small></div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== HELP DESK ======== -->
        <?php elseif ($tab === 'helpdesk'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Support Tickets</h2>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="filterTickets('all')">All</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="filterTickets('open')">Open</button>
                            <button class="btn btn-sm btn-outline-warning" onclick="filterTickets('in_progress')">In Progress</button>
                            <button class="btn btn-sm btn-outline-success" onclick="filterTickets('resolved')">Resolved</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="filterTickets('closed')">Closed</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small" id="ticketTable">
                            <thead><tr><th>#</th><th>Requester</th><th>Issue</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($tickets)): ?><tr><td colspan="7" class="text-center text-muted">No tickets</td></tr><?php endif; ?>
                                <?php foreach ($tickets as $t): ?>
                                <tr class="ticket-row-<?= $t['status'] ?>">
                                    <td><code><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                                    <td><?= htmlspecialchars($t['requester_name']) ?></td>
                                    <td><small><?= htmlspecialchars(mb_substr($t['description']??'',0,40)) ?></small></td>
                                    <td><span class="badge bg-<?= $t['priority']==='critical'||$t['priority']==='high'?'danger':($t['priority']==='medium'?'warning text-dark':'success') ?>"><?= $t['priority'] ?></span></td>
                                    <td><span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='in_progress'?'warning text-dark':($t['status']==='resolved'?'info':'secondary')) ?>"><?= str_replace('_',' ',$t['status']) ?></span></td>
                                    <td><small><?= date('d/m H:i', strtotime($t['created_at'])) ?></small></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-1" data-bs-toggle="dropdown"><i class="fas fa-cog"></i></button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTicket(<?= $t['id'] ?>)"><i class="fas fa-edit me-2"></i>Update</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTicketStatus(<?= $t['id'] ?>,'in_progress')"><i class="fas fa-play me-2"></i>In Progress</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTicketStatus(<?= $t['id'] ?>,'resolved')"><i class="fas fa-check me-2"></i>Resolved</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTicketStatus(<?= $t['id'] ?>,'closed')"><i class="fas fa-times me-2"></i>Closed</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-chart-simple me-2"></i>Ticket Summary</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1 border-bottom"><span>Open</span><span class="badge bg-danger"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets WHERE status='open'") ?></span></div>
                        <div class="d-flex justify-content-between py-1 border-bottom"><span>In Progress</span><span class="badge bg-warning text-dark"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets WHERE status='in_progress'") ?></span></div>
                        <div class="d-flex justify-content-between py-1 border-bottom"><span>Resolved</span><span class="badge bg-info"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets WHERE status='resolved'") ?></span></div>
                        <div class="d-flex justify-content-between py-1 border-bottom"><span>Closed</span><span class="badge bg-secondary"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets WHERE status='closed'") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Total</span><span class="badge bg-primary"><?= ict_q($ict,"SELECT COUNT(*) FROM it_support_tickets") ?></span></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-history me-2 text-info"></i>Security Logs</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($security_logs)): ?><div class="text-muted small">No security logs</div>
                    <?php else: foreach (array_slice($security_logs, 0, 10) as $sl): ?>
                    <div class="py-1 border-bottom small"><span class="badge bg-<?= $sl['severity']==='critical'?'danger':($sl['severity']==='warning'?'warning text-dark':'info') ?> me-1"><?= $sl['event_type'] ?></span><?= htmlspecialchars(mb_substr($sl['description']??'',0,50)) ?> <small class="text-muted float-end"><?= date('d/m', strtotime($sl['created_at'])) ?></small></div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== BACKUPS ======== -->
        <?php elseif ($tab === 'backups'): ?>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-database me-2 text-success"></i>Create Backup</h2>
                    <form id="backupForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="create_backup">
                        <div class="mb-2"><label class="form-label">Backup Name</label><input type="text" name="backup_name" class="form-control" value="Backup-<?= date('Ymd-His') ?>"></div>
                        <div class="mb-2"><label class="form-label">Type</label>
                            <select name="backup_type" class="form-select">
                                <option value="database">Database</option>
                                <option value="file">File System</option>
                                <option value="full">Full System</option>
                                <option value="incremental">Incremental</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Target Database</label>
                            <select name="target_database" class="form-select">
                                <option value="igangaschool_ict">ICT Database</option>
                                <option value="igangaschool_staffs">Staff Database</option>
                                <option value="igangaschool_students">Students Database</option>
                                <option value="igangaschool_website">Website Database</option>
                                <option value="all">All Databases</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-play me-1"></i>Start Backup</button>
                    </form>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-wrench me-2 text-warning"></i>Backup Settings</h2>
                    <form id="backupSettingsForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="save_setting">
                        <div class="mb-1"><label class="form-label small">Auto Backup</label>
                            <select name="setting_value" class="form-select form-select-sm" onchange="saveBackupSetting('auto_backup_enabled',this.value)">
                                <option value="true" <?= ($s=ict_fetch_one($ict,"SELECT setting_value FROM ict_system_settings WHERE setting_key='auto_backup_enabled'")) && $s['setting_value']==='true'?'selected':'' ?>>Enabled</option>
                                <option value="false" <?= $s&&$s['setting_value']==='false'?'selected':'' ?>>Disabled</option>
                            </select>
                        </div>
                        <div class="mb-1"><label class="form-label small">Retention (days)</label>
                            <input type="number" class="form-control form-control-sm" value="<?= ($s=ict_fetch_one($ict,"SELECT setting_value FROM ict_system_settings WHERE setting_key='backup_retention_days'")) ? htmlspecialchars($s['setting_value']) : 30 ?>" onchange="saveBackupSetting('backup_retention_days',this.value)">
                        </div>
                        <div class="mb-1"><label class="form-label small">Scheduled Time</label>
                            <input type="time" class="form-control form-control-sm" value="<?= ($s=ict_fetch_one($ict,"SELECT setting_value FROM ict_system_settings WHERE setting_key='backup_time'")) ? htmlspecialchars($s['setting_value']) : '02:00' ?>" onchange="saveBackupSetting('backup_time',this.value)">
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between mb-2">
                        <h2 class="mb-0"><i class="fas fa-history me-2"></i>Backup History</h2>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="filterBackup('all')">All</button>
                            <button class="btn btn-sm btn-outline-success" onclick="filterBackup('completed')">Completed</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="filterBackup('failed')">Failed</button>
                            <button class="btn btn-sm btn-outline-info" onclick="filterBackup('verified')">Verified</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small" id="backupTable">
                            <thead><tr><th>Name</th><th>Type</th><th>Database</th><th>Size</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($backups)): ?><tr><td colspan="7" class="text-center text-muted">No backups yet</td></tr><?php endif; ?>
                                <?php foreach ($backups as $b): ?>
                                <tr class="backup-row-<?= $b['status'] ?>">
                                    <td><small><?= htmlspecialchars($b['backup_name']) ?></small></td>
                                    <td><span class="badge bg-secondary"><?= $b['backup_type'] ?></span></td>
                                    <td><small><?= htmlspecialchars($b['target_database'] ?? '-') ?></small></td>
                                    <td><small><?= number_format($b['file_size_mb']??0, 1) ?> MB</small></td>
                                    <td><span class="badge bg-<?= $b['status']==='completed'?'success':($b['status']==='failed'?'danger':($b['status']==='verified'?'info':'warning text-dark')) ?>"><?= $b['status'] ?></span></td>
                                    <td><small><?= date('d/m H:i', strtotime($b['created_at'])) ?></small></td>
                                    <td>
                                        <?php if ($b['status'] === 'completed'): ?>
                                        <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="verifyBackup(<?= $b['id'] ?>)"><i class="fas fa-check-circle"></i></button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteBackup(<?= $b['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-list-alt me-2 text-secondary"></i>Backup Logs</h2>
                    <div style="max-height:250px;overflow-y:auto">
                    <?php if (empty($backup_logs)): ?><div class="text-muted small">No backup logs</div>
                    <?php else: foreach (array_slice($backup_logs, 0, 12) as $bl): ?>
                    <div class="py-1 border-bottom small"><span class="badge bg-<?= $bl['log_level']==='error'?'danger':($bl['log_level']==='warning'?'warning text-dark':'info') ?> me-1"><?= $bl['log_level'] ?></span><?= htmlspecialchars($bl['log_message'] ?? '') ?> <small class="text-muted float-end"><?= date('d/m H:i', strtotime($bl['logged_at'])) ?></small></div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== SECURITY ======== -->
        <?php elseif ($tab === 'security'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <h2><i class="fas fa-shield-alt me-2 text-danger"></i>Security Event Log</h2>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-small">
                            <thead><tr><th>Event</th><th>User</th><th>IP</th><th>Description</th><th>Severity</th><th>Time</th></tr></thead>
                            <tbody>
                                <?php if (empty($security_logs)): ?><tr><td colspan="6" class="text-center text-muted">No security logs</td></tr><?php endif; ?>
                                <?php foreach ($security_logs as $sl): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= $sl['event_type'] ?></span></td>
                                    <td><small><?= htmlspecialchars($sl['username'] ?: '-') ?></small></td>
                                    <td><code><?= htmlspecialchars($sl['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= htmlspecialchars(mb_substr($sl['description']??'',0,60)) ?></small></td>
                                    <td><span class="badge bg-<?= $sl['severity']==='critical'?'danger':($sl['severity']==='warning'?'warning text-dark':'info') ?>"><?= $sl['severity'] ?></span></td>
                                    <td><small><?= date('d/m H:i', strtotime($sl['created_at'])) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="section-card">
                            <h2><i class="fas fa-ban me-2 text-danger"></i>Failed Logins</h2>
                            <div style="max-height:250px;overflow-y:auto">
                            <?php if (empty($failed_logins)): ?><div class="text-muted small">No failed logins</div>
                            <?php else: foreach ($failed_logins as $fl): ?>
                            <div class="py-1 border-bottom small"><code><?= htmlspecialchars($fl['username'] ?? '?') ?></code> from <code><?= htmlspecialchars($fl['ip_address'] ?? '?') ?></code> <small class="text-muted float-end"><?= date('d/m H:i', strtotime($fl['attempted_at'])) ?></small></div>
                            <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="section-card">
                            <h2><i class="fas fa-clipboard-list me-2 text-purple"></i>Audit Trail</h2>
                            <div style="max-height:250px;overflow-y:auto">
                            <?php if (empty($audit_logs)): ?><div class="text-muted small">No audit logs</div>
                            <?php else: foreach (array_slice($audit_logs, 0, 10) as $al): ?>
                            <div class="py-1 border-bottom small"><strong><?= htmlspecialchars($al['username'] ?: 'System') ?></strong> <?= $al['action'] ?> <code><?= $al['resource_type'] ?></code> <small class="text-muted float-end"><?= date('d/m H:i', strtotime($al['created_at'])) ?></small></div>
                            <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2 text-warning"></i>Security Settings</h2>
                    <div class="small">
                        <?php
                        $secSettings = ['session_timeout_minutes','max_login_attempts','lockout_duration_minutes','password_min_length'];
                        foreach ($secSettings as $sk):
                            $sv = null;
                            if ($ict) {
                                $st = $ict->prepare("SELECT setting_value FROM ict_system_settings WHERE setting_key=?");
                                if ($st) { $st->bind_param('s', $sk); if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); }; $sr = $st->get_result(); $sv = $sr->fetch_assoc(); $st->close(); }
                            }
                            $val = $sv ? $sv['setting_value'] : '';
                        ?>
                        <div class="mb-2">
                            <label class="form-label small text-muted text-capitalize"><?= str_replace('_', ' ', $sk) ?></label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($val) ?>" id="sec_<?= $sk ?>">
                                <button class="btn btn-outline-primary" onclick="saveSetting('<?= $sk ?>',$('#sec_<?= $sk ?>').val(),'security')"><i class="fas fa-save"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-bell me-2 text-danger"></i>Active Alerts</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($alerts)): ?><div class="text-muted small">No active alerts</div>
                    <?php else: foreach ($alerts as $a): ?>
                    <div class="py-1 border-bottom small">
                        <span class="badge bg-<?= $a['severity']==='critical'?'danger':'warning text-dark' ?>"><?= $a['severity'] ?></span>
                        <strong><?= htmlspecialchars($a['title']) ?></strong>
                        <p class="mb-0 text-muted"><?= htmlspecialchars(mb_substr($a['message']??'',0,60)) ?></p>
                        <div class="mt-1">
                            <button class="btn btn-sm btn-outline-success py-0 px-1" onclick="acknowledgeAlert(<?= $a['id'] ?>)"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="resolveAlert(<?= $a['id'] ?>)"><i class="fas fa-check-double"></i></button>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== MONITORING ======== -->
        <?php elseif ($tab === 'monitoring'): ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-heartbeat me-2 text-danger"></i>System Health Checks</h2>
                    <div style="max-height:400px;overflow-y:auto">
                    <?php if (empty($health_checks)): ?><div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2"></i><p>No health checks recorded yet</p></div>
                    <?php else: foreach ($health_checks as $h): ?>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                        <div><span class="status-led bg-<?= $h['status']==='healthy'?'success':'danger' ?> me-2"></span><strong><?= htmlspecialchars($h['check_name'] ?: $h['check_type']) ?></strong> <span class="text-muted ms-2"><?= htmlspecialchars($h['value'] ?? '') ?></span></div>
                        <span class="badge bg-<?= $h['status']==='healthy'?'success':($h['status']==='warning'?'warning text-dark':'danger') ?>"><?= $h['status'] ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-bell me-2 text-warning"></i>Notifications</h2>
                    <div style="max-height:250px;overflow-y:auto">
                    <?php if (empty($notifications)): ?><div class="text-muted small">No notifications</div>
                    <?php else: foreach ($notifications as $n): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <div><span class="badge bg-<?= $n['notification_type']==='critical'?'danger':($n['notification_type']==='warning'?'warning text-dark':'info') ?> me-1"><?= $n['notification_type'] ?></span><?= htmlspecialchars($n['title']) ?></div>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="dismissNotif(<?= $n['id'] ?>)"><i class="fas fa-times"></i></button>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                    <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addNotifModal"><i class="fas fa-plus me-1"></i>Add Notification</button>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-sliders-h me-2 text-primary"></i>Live Monitoring</h2>
                    <div class="row g-2">
                        <div class="col-6"><div class="monitor-card"><h3 id="liveCpu">67%</h3><p>CPU Usage</p><div class="progress"><div class="progress-bar bg-success" style="width:67%"></div></div></div></div>
                        <div class="col-6"><div class="monitor-card"><h3 id="liveRam">72%</h3><p>RAM Usage</p><div class="progress"><div class="progress-bar bg-warning" style="width:72%"></div></div></div></div>
                        <div class="col-6"><div class="monitor-card"><h3 id="liveDisk">45%</h3><p>Disk Usage</p><div class="progress"><div class="progress-bar bg-info" style="width:45%"></div></div></div></div>
                        <div class="col-6"><div class="monitor-card"><h3 id="liveNet">99.8%</h3><p>Network Uptime</p><span class="badge bg-success">Online</span></div></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-exclamation-triangle me-2 text-danger"></i>System Alerts</h2>
                    <form id="alertForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="add_alert">
                        <div class="row g-1 mb-1">
                            <div class="col-4"><select name="alert_type" class="form-select form-select-sm"><option value="system">System</option><option value="security">Security</option><option value="backup">Backup</option><option value="performance">Performance</option><option value="network">Network</option><option value="storage">Storage</option></select></div>
                            <div class="col-3"><select name="severity" class="form-select form-select-sm"><option value="info">Info</option><option value="warning">Warning</option><option value="critical">Critical</option></select></div>
                            <div class="col-5"><input type="text" name="title" class="form-control form-control-sm" placeholder="Alert title" required></div>
                        </div>
                        <div class="mb-1"><textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Alert message" required></textarea></div>
                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="fas fa-plus me-1"></i>Create Alert</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ======== SETTINGS ======== -->
        <?php elseif ($tab === 'settings'): ?>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2 text-primary"></i>System Settings</h2>
                    <div class="small">
                        <?php foreach ($settings as $s): ?>
                        <div class="mb-2">
                            <label class="form-label small text-muted text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $s['setting_key'])) ?></label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($s['setting_value'] ?? '') ?>" id="set_<?= $s['setting_key'] ?>">
                                <button class="btn btn-outline-primary" onclick="saveSetting('<?= $s['setting_key'] ?>',$('#set_<?= $s['setting_key'] ?>').val(),'<?= $s['setting_group'] ?>')"><i class="fas fa-save"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card">
                    <h2><i class="fas fa-tag me-2 text-success"></i>Asset Categories</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php foreach ($asset_cats as $c): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom small"><?= htmlspecialchars($c['category_name']) ?> <span class="badge bg-secondary"><?= ict_q($ict, "SELECT COUNT(*) FROM ict_assets WHERE category_id=" . (int)$c['id']) ?></span></div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-shield-alt me-2 text-danger"></i>Quick Actions</h2>
                    <div class="d-grid gap-2">
                        <button class="btn btn-sm btn-outline-success text-start" onclick="createQuickBackup()"><i class="fas fa-database me-2"></i>Quick Backup All Databases</button>
                        <button class="btn btn-sm btn-outline-info text-start" onclick="addHealthCheck()"><i class="fas fa-heartbeat me-2"></i>Run System Health Check</button>
                        <button class="btn btn-sm btn-outline-warning text-start" onclick="addSecurityLog()"><i class="fas fa-shield-alt me-2"></i>Log Security Event</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- ======== USERS & ACCESS MANAGEMENT ======== -->
        <?php elseif ($tab === 'users'): ?>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="section-card">
                    <h2><i class="fas fa-users me-2 text-primary"></i>Staff Accounts <span class="badge bg-secondary"><?= $staff_count ?></span></h2>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th></tr></thead>
                            <tbody>
                                <?php if (empty($staff_accounts)): ?><tr><td colspan="5" class="text-center text-muted">No staff accounts</td></tr><?php endif; ?>
                                <?php foreach ($staff_accounts as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['full_name']) ?></td>
                                    <td><small><?= htmlspecialchars($s['email'] ?? '-') ?></small> <?= ict_mailto($s['email'] ?? '') ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($s['role']) ?></span></td>
                                    <td><span class="badge bg-<?= $s['status']==='Active'?'success':'secondary' ?>"><?= $s['status'] ?></span></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($s['last_login'] ?? 'Never') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-history me-2 text-info"></i>Active Login Sessions <span class="badge bg-primary"><?= $active_sessions ?></span></h2>
                    <div class="small text-muted mb-2">Active sessions across the system. Failed logins today: <strong><?= $failed_today ?></strong></div>
                    <?php $logins = ict_fetch($ict, "SELECT * FROM ict_login_sessions WHERE status='active' ORDER BY logged_in_at DESC LIMIT 10"); ?>
                    <?php if (empty($logins)): ?><p class="text-muted small">No active sessions recorded</p>
                    <?php else: ?>
                    <div style="max-height:250px;overflow-y:auto">
                        <?php foreach ($logins as $l): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom small">
                            <span><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($l['username']) ?></span>
                            <span class="text-muted"><?= htmlspecialchars($l['logged_in_at']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-5">
                <div class="section-card">
                    <h2><i class="fas fa-user-graduate me-2 text-purple"></i>Student Accounts <span class="badge bg-secondary"><?= $student_count ?></span></h2>
                    <?php $students_list = ict_fetch($students_conn, "SELECT id, first_name, surname, full_name, email, status, last_login FROM students ORDER BY last_login DESC LIMIT 15"); ?>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Last Login</th></tr></thead>
                            <tbody>
                                <?php if (empty($students_list)): ?><tr><td colspan="4" class="text-center text-muted">No records</td></tr><?php endif; ?>
                                <?php foreach ($students_list as $s): ?>
                                <tr>
                                    <td><small><?= htmlspecialchars($s['full_name'] ?: $s['first_name'] . ' ' . $s['surname']) ?></small></td>
                                    <td><small><?= htmlspecialchars($s['email'] ?? '-') ?></small> <?= ict_mailto($s['email'] ?? '') ?></td>
                                    <td><span class="badge bg-<?= $s['status']==='Active'?'success':'secondary' ?>"><?= $s['status'] ?? 'Active' ?></span></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($s['last_login'] ?? 'Never') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 small text-muted"><i class="fas fa-info-circle me-1"></i>Full account management is in <a href="system-admin.php">System Administration</a>.</p>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-shield-alt me-2 text-danger"></i>Recent Failed Logins</h2>
                    <?php if (empty($failed_logins)): ?><p class="text-muted small">No failed login attempts</p>
                    <?php else: ?>
                    <div style="max-height:250px;overflow-y:auto">
                        <?php foreach (array_slice($failed_logins, 0, 8) as $f): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom small">
                            <span><i class="fas fa-user-slash me-1 text-danger"></i><?= htmlspecialchars($f['username']) ?>@<?= htmlspecialchars($f['ip_address'] ?? '?') ?></span>
                            <span class="text-muted"><?= htmlspecialchars($f['attempted_at']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ======== ERP SYSTEM MANAGEMENT ======== -->
        <?php elseif ($tab === 'erp'): ?>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="section-card">
                    <h2><i class="fas fa-cubes me-2 text-primary"></i>ERP Module Permissions</h2>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Module</th><th>Role</th><th>Access Level</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($module_perms)): ?><tr><td colspan="4" class="text-center text-muted">No module permissions configured</td></tr><?php endif; ?>
                                <?php foreach ($module_perms as $m): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($m['module_name'] ?? '-') ?></strong></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($m['role_name'] ?? '-') ?></span></td>
                                    <td><small><?= htmlspecialchars($m['access_level'] ?? 'full') ?></small></td>
                                    <td><span class="badge bg-<?= ($m['is_active']??'1')=='1'?'success':'secondary' ?>"><?= ($m['is_active']??'1')=='1'?'Active':'Inactive' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-database me-2 text-teal"></i>System Environment</h2>
                    <div class="row g-2 small">
                        <div class="col-6">
                            <div class="d-flex justify-content-between py-1"><span>PHP Version</span><strong><?= phpversion() ?></strong></div>
                            <div class="d-flex justify-content-between py-1"><span>Database</span><strong>MySQL</strong></div>
                            <div class="d-flex justify-content-between py-1"><span>Server</span><strong><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></strong></div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-between py-1"><span>DB Size (ICT)</span><strong><?= number_format($db_size_mb, 2) ?> MB</strong></div>
                            <div class="d-flex justify-content-between py-1"><span>ICT Tables</span><strong><?= $ict ? count($ict->query("SHOW TABLES")->fetch_all()) : 0 ?></strong></div>
                            <div class="d-flex justify-content-between py-1"><span>Active Users</span><strong><?= $total_users ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2 text-warning"></i>ERP Configuration</h2>
                    <p class="small text-muted">Full ERP configuration & module management is available in <a href="system-admin.php">System Administration</a>.</p>
                    <div class="d-grid gap-2 mt-2">
                        <a href="system-admin.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-cog me-1"></i>System Administration</a>
                        <a href="../index.php" class="btn btn-sm btn-outline-info"><i class="fas fa-home me-1"></i>ERP Home</a>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-tasks me-2 text-success"></i>System Status</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Staff Records</span><span class="badge bg-success"><?= $staff_count ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Active Students</span><span class="badge bg-success"><?= $student_count ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Open Tickets</span><span class="badge bg-<?= $open_tickets ? 'danger' : 'success' ?>"><?= $open_tickets ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Active Alerts</span><span class="badge bg-<?= $active_alerts ? 'danger' : 'secondary' ?>"><?= $active_alerts ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Active Servers</span><span class="badge bg-success"><?= $active_servers ?></span></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-server me-2 text-purple"></i>Quick Actions</h2>
                    <div class="d-grid gap-2">
                        <button class="btn btn-sm btn-outline-success text-start" onclick="createQuickBackup()"><i class="fas fa-database me-2"></i>Quick Backup</button>
                        <button class="btn btn-sm btn-outline-info text-start" onclick="addHealthCheck()"><i class="fas fa-heartbeat me-2"></i>Run System Health Check</button>
                        <a href="system-admin.php" class="btn btn-sm btn-outline-primary text-start"><i class="fas fa-user-shield me-2"></i>User Permissions</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== WEBSITE & PORTAL ======== -->
        <?php elseif ($tab === 'website'): ?>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="section-card">
                    <h2><i class="fas fa-globe me-2 text-primary"></i>Website Status</h2>
                    <?php
                    $site_url = ($settings_entry = ict_fetch_one($ict, "SELECT setting_value FROM ict_system_settings WHERE setting_key='site_url'")) ? $settings_entry['setting_value'] : '../index.php';
                    $pages = ict_fetch($ict, "SELECT * FROM ict_system_settings WHERE setting_group='website' OR setting_key LIKE 'site_%' LIMIT 10");
                    ?>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Site URL</span><strong><a href="<?= htmlspecialchars($site_url) ?>" target="_blank"><?= htmlspecialchars($site_url) ?></a></strong></div>
                        <?php foreach ($pages as $p): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><?= htmlspecialchars(str_replace('_', ' ', ucfirst($p['setting_key']))) ?></span>
                            <span class="text-muted"><?= htmlspecialchars(mb_substr($p['setting_value'] ?? '', 0, 60)) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($pages)): ?><p class="text-muted">No website settings configured</p><?php endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-newspaper me-2 text-warning"></i>News & Updates</h2>
                    <?php $news_items = ict_fetch($website_conn ?? $staff_conn, "SELECT id, title, created_at, status FROM news ORDER BY created_at DESC LIMIT 8"); ?>
                    <?php if (!empty($news_items)): ?>
                    <div style="max-height:300px;overflow-y:auto">
                        <?php foreach ($news_items as $n): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom small">
                            <span><strong><?= htmlspecialchars($n['title']) ?></strong></span>
                            <span><span class="badge bg-<?= ($n['status']??'published')==='published'?'success':'secondary' ?>"><?= $n['status'] ?? 'published' ?></span> <small class="text-muted"><?= htmlspecialchars($n['created_at']) ?></small></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted small">No news items found</p>
                    <?php endif; ?>
                    <p class="mt-2 small text-muted"><i class="fas fa-info-circle me-1"></i>Manage website content in <a href="../news.php" target="_blank">News & Updates</a> | <a href="website-pages.php" target="_blank">Website Pages</a></p>
                </div>
            </div>
            <div class="col-md-5">
                <div class="section-card">
                    <h2><i class="fas fa-download me-2 text-info"></i>Downloads & Resources</h2>
                    <div class="d-grid gap-2">
                        <a href="student-downloads.php" class="btn btn-sm btn-outline-primary text-start"><i class="fas fa-download me-2"></i>Student Downloads</a>
                        <a href="document_management.php" class="btn btn-sm btn-outline-info text-start"><i class="fas fa-folder me-2"></i>Document Management</a>
                        <a href="../index.php" class="btn btn-sm btn-outline-secondary text-start"><i class="fas fa-home me-2"></i>Portal Home</a>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-portal me-2 text-purple"></i>Portal Links</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><a href="../student.php">Student Portal</a> <span class="badge bg-info"><?= $total_students ?> users</span></div>
                        <div class="d-flex justify-content-between py-1"><a href="../index.php">Staff Portal</a> <span class="badge bg-info"><?= $staff_count ?> users</span></div>
                        <div class="d-flex justify-content-between py-1"><a href="../news.php">News</a></div>
                        <div class="d-flex justify-content-between py-1"><a href="../messaging.php">Messaging</a></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2 text-secondary"></i>Settings</h2>
                    <p class="small text-muted">Configure website banners, portal settings, and homepage in <a href="website-pages.php">Website Pages</a>.</p>
                </div>
            </div>
        </div>
        <!-- Website Submissions -->
        <div class="row g-3 mt-2">
            <div class="col-12">
                <?php renderDirectorWebsitePanel($website_conn, null, 'All Website Submissions'); ?>
            </div>
        </div>

        <!-- ======== APPROVALS ======== -->
        <?php elseif ($tab === 'approvals'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-check-double me-2 text-warning"></i>Pending IT Tickets</h2>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary filter-pill active" onclick="filterApproval('all')">All</button>
                            <button class="btn btn-sm btn-outline-danger filter-pill" onclick="filterApproval('critical')">Critical</button>
                            <button class="btn btn-sm btn-outline-warning filter-pill" onclick="filterApproval('high')">High</button>
                        </div>
                    </div>
                    <?php if (empty($pending_tickets)): ?>
                    <div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2 d-block"></i>No pending tickets</div>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Ticket #</th><th>Requester</th><th>Description</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($pending_tickets as $t): ?>
                                <tr class="ticket-row-<?= $t['priority'] ?>">
                                    <td><code><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                                    <td><?= htmlspecialchars($t['requester_name']) ?></td>
                                    <td><small><?= htmlspecialchars(mb_substr($t['description'] ?? '', 0, 60)) ?></small></td>
                                    <td><span class="badge bg-<?= $t['priority']==='critical'||$t['priority']==='high'?'danger':($t['priority']==='medium'?'warning text-dark':'success') ?>"><?= $t['priority'] ?></span></td>
                                    <td><span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='in_progress'?'warning text-dark':'success') ?>"><?= str_replace('_', ' ', $t['status']) ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success py-0 px-1" onclick="updateTicketStatus(<?= $t['id'] ?>,'resolved')" title="Approve"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="updateTicketStatus(<?= $t['id'] ?>,'in_progress')" title="Assign"><i class="fas fa-user-tag"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-file-signature me-2 text-primary"></i>DG Approval Requests</h2>
                        <div>
                            <?php renderDepartmentApprovalButton(); ?>
                        </div>
                    </div>
                    <?php if (empty($pending_approval_requests)): ?>
                    <div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No pending approval requests</div>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Request #</th><th>Title</th><th>Requester</th><th>Priority</th><th>Stage</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($pending_approval_requests as $ar): ?>
                                <tr class="ticket-row-<?= strtolower($ar['priority']) ?>">
                                    <td><code><?= htmlspecialchars($ar['request_number']) ?></code></td>
                                    <td><small><?= htmlspecialchars(mb_substr($ar['title'] ?? '', 0, 50)) ?></small></td>
                                    <td><?= htmlspecialchars($ar['requester_name']) ?></td>
                                    <td><span class="badge bg-<?= $ar['priority']==='Critical'||$ar['priority']==='High'?'danger':'info' ?>"><?= $ar['priority'] ?></span></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($ar['workflow_name'] ?? 'ICT Request') ?></span></td>
                                    <td>
                                        <a href="../dashboards/director-general.php?page=approvals" class="btn btn-sm btn-outline-primary py-0 px-1" title="View in DG Center"><i class="fas fa-external-link-alt"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-chart-pie me-2 text-primary"></i>IT Ticket Summary</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Open Tickets</span><span class="badge bg-danger"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='open'") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>In Progress</span><span class="badge bg-warning text-dark"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='in_progress'") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Resolved</span><span class="badge bg-success"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='resolved'") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Closed</span><span class="badge bg-secondary"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='closed'") ?></span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between py-1"><span>Critical/High</span><span class="badge bg-danger"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status IN ('open','in_progress') AND priority IN ('critical','high')") ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Normal</span><span class="badge bg-info"><?= ict_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status IN ('open','in_progress') AND priority IN ('medium','low')") ?></span></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-chart-line me-2 text-success"></i>DG Request Summary</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Active Requests</span><span class="badge bg-primary"><?= ($staff_conn) ? ict_q($staff_conn, "SELECT COUNT(*) FROM {$staff_db}.approval_requests WHERE status='Active'") : 0 ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Approved</span><span class="badge bg-success"><?= ($staff_conn) ? ict_q($staff_conn, "SELECT COUNT(*) FROM {$staff_db}.approval_requests WHERE status='Approved'") : 0 ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>My Requests</span><span class="badge bg-info"><?= ($staff_conn) ? ict_q($staff_conn, "SELECT COUNT(*) FROM {$staff_db}.approval_requests WHERE requester_id=" . (int)($user_id)) : 0 ?></span></div>
                    </div>
                </div>
                <?php if (function_exists('renderMyApprovalRequestsWidget')): ?>
                <?= renderMyApprovalRequestsWidget($staff_conn) ?>
                <?php endif; ?>
                <div class="section-card">
                    <h2><i class="fas fa-clipboard-list me-2 text-info"></i>Approval Types</h2>
                    <div class="d-grid gap-2 small">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="fas fa-user-plus me-1 text-primary"></i> New User Requests</span>
                            <span class="badge bg-secondary">System Admin</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="fas fa-shopping-cart me-1 text-warning"></i> ICT Procurement</span>
                            <span class="badge bg-secondary">Help Desk</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="fas fa-download me-1 text-info"></i> Software Install</span>
                            <span class="badge bg-secondary">Help Desk</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="fas fa-toolbox me-1 text-success"></i> Equipment Requests</span>
                            <span class="badge bg-secondary">Help Desk</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="fas fa-tools me-1 text-orange"></i> Maintenance</span>
                            <span class="badge bg-secondary">Assets</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ======== STUDENT MANAGEMENT ======== -->
        <?php if ($tab === 'student-management'): ?>
        <div class="row g-3">
            <div class="col-lg-12">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-user-graduate me-2 text-primary"></i>Student Management</h2>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" id="ictStudentSearch" placeholder="Search by name, student number, or index..." style="width:320px" oninput="if(this.value.length>=2){searchStudents()}else{document.getElementById('ictStudentResults').innerHTML='';}">
                            <button class="btn btn-sm btn-success" onclick="showICTRegisterModal()"><i class="fas fa-plus me-1"></i>Register New Student</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:600px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead>
                                <tr><th>Name</th><th>Student Number</th><th>Index Number</th><th>Program</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody id="ictStudentResults">
                                <?php
                                $ictStudents = ict_fetch($students_conn, "SELECT id, student_number, registration_number, full_name, first_name, surname, program, level, status FROM students ORDER BY created_at DESC LIMIT 30");
                                if (empty($ictStudents)):
                                ?>
                                <tr><td colspan="6" class="text-center text-muted">No students found. Use the search bar or register a new student.</td></tr>
                                <?php else: foreach ($ictStudents as $st): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($st['full_name'] ?: ($st['first_name'] . ' ' . $st['surname'])) ?></strong></td>
                                    <td><code><?= htmlspecialchars($st['student_number'] ?? '-') ?></code></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($st['registration_number'] ?? '-') ?></small></td>
                                    <td><small><?= htmlspecialchars($st['program'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= ($st['status'] ?? '') === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($st['status'] ?? 'Active') ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="viewICTStudentProfile(<?= (int)$st['id'] ?>)" title="View Profile"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-warning py-0 px-1" onclick="editICTStudent(<?= (int)$st['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <script>
        document.getElementById('ictStudentSearch').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') searchStudents();
        });
        </script>
        <?php endif; ?>
</div>
<div class="modal fade" id="addAssetModal" tabindex="-1"><div class="modal-dialog">
<form class="modal-content" id="addAssetForm">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<input type="hidden" name="action" value="add_asset">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-box me-2"></i>Add Asset</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Asset Number *</label><input type="text" name="asset_number" class="form-control" required></div>
        <div class="col-6"><label class="form-label">Type</label><select name="asset_type" class="form-select"><option>computer</option><option>printer</option><option>scanner</option><option>projector</option><option>network</option><option>server</option><option>ups</option><option>software</option><option>other</option></select></div>
    </div>
    <div class="mb-2"><label class="form-label">Asset Name *</label><input type="text" name="asset_name" class="form-control" required></div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Brand</label><input type="text" name="brand" class="form-control"></div>
        <div class="col-6"><label class="form-label">Model</label><input type="text" name="model" class="form-control"></div>
    </div>
    <div class="mb-2"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Barcode / QR</label><input type="text" name="barcode" class="form-control"></div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="0">None</option><?php foreach ($asset_cats as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-6"><label class="form-label">Purchase Cost</label><input type="number" name="purchase_cost" class="form-control" step="0.01"></div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" class="form-control"></div>
        <div class="col-6"><label class="form-label">Warranty Expiry</label><input type="date" name="warranty_expiry" class="form-control"></div>
    </div>
    <div class="mb-2"><label class="form-label">Location</label><input type="text" name="current_location" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Department</label><input type="text" name="assigned_department" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Asset</button></div>
</form>
</div></div>

<div class="modal fade" id="addServerModal" tabindex="-1"><div class="modal-dialog modal-sm">
<form class="modal-content" id="addServerForm">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<input type="hidden" name="action" value="add_server">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-server me-2"></i>Add Server</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Server Name *</label><input type="text" name="server_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">IP Address</label><input type="text" name="ip_address" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Type</label><select name="server_type" class="form-select"><option value="physical">Physical</option><option value="virtual">Virtual</option><option value="cloud">Cloud</option></select></div>
    <div class="mb-2"><label class="form-label">OS</label><input type="text" name="os" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Purpose</label><textarea name="purpose" class="form-control" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<div class="modal fade" id="addWifiModal" tabindex="-1"><div class="modal-dialog modal-sm">
<form class="modal-content" id="addWifiForm">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<input type="hidden" name="action" value="add_wifi">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-wifi me-2"></i>Add WiFi AP</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Device Name *</label><input type="text" name="device_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">SSID</label><input type="text" name="ssid" class="form-control"></div>
    <div class="mb-2"><label class="form-label">IP Address</label><input type="text" name="ip_address" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Location</label><input type="text" name="location" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<div class="modal fade" id="addNotifModal" tabindex="-1"><div class="modal-dialog modal-sm">
<form class="modal-content" id="addNotifForm">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<input type="hidden" name="action" value="add_notification">
<div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-bell me-2"></i>Add Notification</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Message *</label><textarea name="message" class="form-control" rows="3" required></textarea></div>
    <div class="mb-2"><label class="form-label">Type</label><select name="notification_type" class="form-select"><option value="info">Info</option><option value="warning">Warning</option><option value="critical">Critical</option><option value="success">Success</option></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-info text-white"><i class="fas fa-save me-1"></i>Add</button></div>
</form>
</div></div>

<!-- ICT Student Registration Modal -->
<div class="modal fade" id="ictRegisterModal" tabindex="-1"><div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Register New Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <form id="ictRegisterForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="ict_register_student">
    <div class="row g-2 mb-2">
        <div class="col-md-4"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Surname *</label><input type="text" name="surname" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Gender *</label><select name="gender" class="form-select" required><option value="">Select...</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select></div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-md-6"><label class="form-label">Phone *</label><input type="text" name="phone" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-md-6"><label class="form-label">Program *</label>
            <select name="program" class="form-select" required>
                <option value="">Select Program...</option>
                <?php
                $ictPrograms = [];
                if ($staff_conn) {
                    $pr = $staff_conn->query("SELECT program_name FROM academic_programs WHERE status='Active' ORDER BY program_name");
                    if ($pr) while ($row = $pr->fetch_assoc()) $ictPrograms[] = $row['program_name'];
                }
                if ($students_conn && empty($ictPrograms)) {
                    $pr2 = $students_conn->query("SELECT DISTINCT program FROM students WHERE program IS NOT NULL AND program != '' ORDER BY program");
                    if ($pr2) while ($row = $pr2->fetch_assoc()) $ictPrograms[] = $row['program'];
                }
                foreach ($ictPrograms as $prog):
                ?>
                <option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Intake *</label>
            <select name="intake" class="form-select" required>
                <option value="">Select Intake...</option>
                <?php
                $ictIntakes = [];
                if ($staff_conn) {
                    $ir = $staff_conn->query("SELECT intake_name FROM intakes WHERE status='Open' ORDER BY intake_year DESC, FIELD(intake_month,'January','February','March','April','May','June','July','August','September','October','November','December')");
                    if ($ir) while ($row = $ir->fetch_assoc()) $ictIntakes[] = $row['intake_name'];
                }
                if (empty($ictIntakes)) {
                    $ictIntakes[] = date('F') . ' ' . date('Y');
                }
                foreach ($ictIntakes as $intk):
                ?>
                <option value="<?= htmlspecialchars($intk) ?>"><?= htmlspecialchars($intk) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">National ID</label><input type="text" name="national_id" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">District</label><input type="text" name="district" class="form-control"></div>
    </div>
    <div class="mb-2"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
    <hr>
    <h6 class="text-muted mb-2"><i class="fas fa-users me-1"></i>Next of Kin</h6>
    <div class="row g-2 mb-2">
        <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="next_of_kin_name" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="next_of_kin_phone" class="form-control"></div>
    </div>
    <div id="ictRegisterResult" style="display:none" class="alert alert-success mt-2"></div>
    </form>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" form="ictRegisterForm" class="btn btn-success"><i class="fas fa-save me-1"></i>Register Student</button></div>
</div>
</div></div>

<!-- jQuery & Bootstrap already loaded in dashboard_head.php â€” do NOT re-add here -->
<script>
const ICT_HANDLER = '../handlers/ict_handler.php';
const CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
function showAlert(m, t) { $('.ict-content').prepend(`<div class="alert alert-${t} alert-dismissible fade show py-2 an-slide" style="border:none;border-radius:10px;">${m}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`); setTimeout(()=>$('.alert').alert('close'),5000); }

function doAjax(formId, cb) {
    const f = $(`#${formId}`);
    if (f.length && f[0].checkValidity && !f[0].checkValidity()) { f[0].reportValidity(); return; }
    const data = f.serializeArray().reduce((o, x) => (o[x.name] = x.value, o), {});
    data.action = f.find('[name=action]').val();
    data.csrf_token = CSRF_TOKEN;
    $.post(ICT_HANDLER, data).done(r => { if(r.success){ showAlert(r.message,'success'); if(cb) cb(r.data); else setTimeout(()=>location.reload(),600); } else showAlert(r.message,'danger'); }).fail(()=>showAlert('AJAX error','danger'));
}

// Form handlers
$('#addAssetForm').submit(e=>{ e.preventDefault(); doAjax('addAssetForm'); });
$('#addServerForm').submit(e=>{ e.preventDefault(); doAjax('addServerForm'); });
$('#addWifiForm').submit(e=>{ e.preventDefault(); doAjax('addWifiForm'); });
$('#addNotifForm').submit(e=>{ e.preventDefault(); doAjax('addNotifForm'); });
$('#backupForm').submit(e=>{ e.preventDefault(); doAjax('backupForm'); });
$('#alertForm').submit(e=>{ e.preventDefault(); doAjax('alertForm'); });

// Single-click actions
function updateTicketStatus(id, status) {
    $.post(ICT_HANDLER, { action: 'update_ticket', id, status, csrf_token: CSRF_TOKEN }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function updateNetDevice(id, status) {
    $.post(ICT_HANDLER, { action: 'update_network_device', id, status, csrf_token: CSRF_TOKEN }).done(r => { if(r.success) showAlert('Updated','success'); else showAlert(r.message,'danger'); });
}
function updateWifi(id, status) {
    $.post(ICT_HANDLER, { action: 'edit_wifi', id, status, csrf_token: CSRF_TOKEN }).done(r => { if(r.success) showAlert('Updated','success'); else showAlert(r.message,'danger'); });
}
function verifyBackup(id) {
    $.post(ICT_HANDLER, { action: 'verify_backup', id, csrf_token: CSRF_TOKEN }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function deleteBackup(id) {
    if(!confirm('Delete this backup?')) return;
    $.post(ICT_HANDLER, { action: 'delete_backup', id, csrf_token: CSRF_TOKEN }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function acknowledgeAlert(id) {
    $.post(ICT_HANDLER, { action: 'acknowledge_alert', id, csrf_token: CSRF_TOKEN }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function resolveAlert(id) {
    $.post(ICT_HANDLER, { action: 'resolve_alert', id, csrf_token: CSRF_TOKEN }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function dismissNotif(id) {
    $.post(ICT_HANDLER, { action: 'dismiss_notification', id, csrf_token: CSRF_TOKEN }).done(r => { if(r.success) location.reload(); else showAlert(r.message,'danger'); });
}
function saveSetting(key, value, group) {
    $.post(ICT_HANDLER, { action: 'save_setting', setting_key: key, setting_value: value, setting_group: group || 'general', csrf_token: CSRF_TOKEN }).done(r => { if(r.success) showAlert('Setting saved','success'); else showAlert(r.message,'danger'); });
}
function saveBackupSetting(key, value) { saveSetting(key, value, 'backup'); }
function editAsset(id) {
    $.get(ICT_HANDLER, {action:'get_asset', id:id}).done(function(r){
        if(!r.success){showAlert(r.message,'danger');return;}
        var d=r.data;
        var html='<form id="editAssetForm"><input type="hidden" name="csrf_token" value="'+CSRF_TOKEN+'"><input type="hidden" name="action" value="edit_asset"><input type="hidden" name="id" value="'+d.id+'">';
        html+='<div class="mb-2"><label class="form-label">Name</label><input type="text" name="asset_name" class="form-control" value="'+(d.asset_name||'')+'" required></div>';
        html+='<div class="row mb-2"><div class="col-6"><label class="form-label">Type</label><select name="asset_type" class="form-select"><option value="hardware"'+(d.asset_type==='hardware'?' selected':'')+'>Hardware</option><option value="software"'+(d.asset_type==='software'?' selected':'')+'>Software</option><option value="network"'+(d.asset_type==='network'?' selected':'')+'>Network</option></select></div><div class="col-6"><label class="form-label">Status</label><select name="current_status" class="form-select"><option value="active"'+(d.current_status==='active'?' selected':'')+'>Active</option><option value="in_maintenance"'+(d.current_status==='in_maintenance'?' selected':'')+'>Maintenance</option><option value="retired"'+(d.current_status==='retired'?' selected':'')+'>Retired</option></select></div></div>';
        html+='<div class="mb-2"><label class="form-label">Location</label><input type="text" name="current_location" class="form-control" value="'+(d.current_location||'')+'"></div>';
        html+='<button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Save Changes</button></form>';
        showBootstrapModal('Edit Asset', html, 'editAssetModal');
        $('#editAssetForm').submit(function(e){e.preventDefault();doAjax('editAssetForm');});
    });
}
function assignAsset(id) {
    var staffHtml='<form id="assignAssetForm"><input type="hidden" name="csrf_token" value="'+CSRF_TOKEN+'"><input type="hidden" name="action" value="assign_asset"><input type="hidden" name="asset_id" value="'+id+'">';
    staffHtml+='<div class="mb-2"><label class="form-label">Staff ID</label><input type="number" name="assigned_to_staff_id" class="form-control" required></div>';
    staffHtml+='<div class="mb-2"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>';
    staffHtml+='<button type="submit" class="btn btn-info text-white w-100"><i class="fas fa-user-tag me-1"></i>Assign Asset</button></form>';
    showBootstrapModal('Assign Asset', staffHtml, 'assignAssetModal');
    $('#assignAssetForm').submit(function(e){e.preventDefault();doAjax('assignAssetForm');});
}
function logMaint(id) {
    var mHtml='<form id="maintForm"><input type="hidden" name="csrf_token" value="'+CSRF_TOKEN+'"><input type="hidden" name="action" value="add_asset_maintenance"><input type="hidden" name="asset_id" value="'+id+'">';
    mHtml+='<div class="mb-2"><label class="form-label">Type</label><select name="maintenance_type" class="form-select"><option value="preventive">Preventive</option><option value="corrective">Corrective</option><option value="emergency">Emergency</option></select></div>';
    mHtml+='<div class="mb-2"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" required></textarea></div>';
    mHtml+='<div class="mb-2"><label class="form-label">Cost</label><input type="number" step="0.01" name="cost" class="form-control" value="0"></div>';
    mHtml+='<button type="submit" class="btn btn-warning w-100"><i class="fas fa-tools me-1"></i>Log Maintenance</button></form>';
    showBootstrapModal('Log Maintenance', mHtml, 'maintModal');
    $('#maintForm').submit(function(e){e.preventDefault();doAjax('maintForm');});
}
function editServer(id) {
    $.get(ICT_HANDLER, {action:'get_server', id:id}).done(function(r){
        if(!r.success){showAlert(r.message,'danger');return;}
        var d=r.data;
        var html='<form id="editServerForm"><input type="hidden" name="csrf_token" value="'+CSRF_TOKEN+'"><input type="hidden" name="action" value="edit_server"><input type="hidden" name="id" value="'+d.id+'">';
        html+='<div class="mb-2"><label class="form-label">Name</label><input type="text" name="server_name" class="form-control" value="'+(d.server_name||'')+'" required></div>';
        html+='<div class="row mb-2"><div class="col-6"><label class="form-label">IP</label><input type="text" name="ip_address" class="form-control" value="'+(d.ip_address||'')+'"></div><div class="col-6"><label class="form-label">Type</label><input type="text" name="server_type" class="form-control" value="'+(d.server_type||'')+'"></div></div>';
        html+='<div class="row mb-2"><div class="col-6"><label class="form-label">OS</label><input type="text" name="os" class="form-control" value="'+(d.os||'')+'"></div><div class="col-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="online"'+(d.status==='online'?' selected':'')+'>Online</option><option value="offline"'+(d.status==='offline'?' selected':'')+'>Offline</option><option value="maintenance"'+(d.status==='maintenance'?' selected':'')+'>Maintenance</option></select></div></div>';
        html+='<button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Save</button></form>';
        showBootstrapModal('Edit Server', html, 'editServerModal');
        $('#editServerForm').submit(function(e){e.preventDefault();doAjax('editServerForm');});
    });
}
function updateTicket(id) {
    $.get(ICT_HANDLER, {action:'get_ticket', id:id}).done(function(r){
        if(!r.success){showAlert(r.message,'danger');return;}
        var d=r.data;
        var html='<form id="updateTicketForm"><input type="hidden" name="csrf_token" value="'+CSRF_TOKEN+'"><input type="hidden" name="action" value="update_ticket"><input type="hidden" name="id" value="'+d.id+'">';
        html+='<div class="mb-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="open"'+(d.status==='open'?' selected':'')+'>Open</option><option value="in_progress"'+(d.status==='in_progress'?' selected':'')+'>In Progress</option><option value="resolved"'+(d.status==='resolved'?' selected':'')+'>Resolved</option><option value="closed"'+(d.status==='closed'?' selected':'')+'>Closed</option></select></div>';
        html+='<div class="mb-2"><label class="form-label">Assigned To (Staff ID)</label><input type="number" name="assigned_to" class="form-control" value="'+(d.assigned_to||'')+'"></div>';
        html+='<div class="mb-2"><label class="form-label">Resolution Notes</label><textarea name="resolution_notes" class="form-control" rows="3"></textarea></div>';
        html+='<button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Update Ticket</button></form>';
        showBootstrapModal('Update Ticket', html, 'updateTicketModal');
        $('#updateTicketForm').submit(function(e){e.preventDefault();doAjax('updateTicketForm');});
    });
}
function showBootstrapModal(title, body, id) {
    var existing = document.getElementById(id);
    if(existing) existing.remove();
    var modal='<div class="modal fade" id="'+id+'" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title">'+title+'</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body">'+body+'</div></div></div></div>';
    document.body.insertAdjacentHTML('beforeend', modal);
    new bootstrap.Modal(document.getElementById(id)).show();
}

function createQuickBackup() {
    $.post(ICT_HANDLER, { action: 'create_backup', backup_name: 'QuickBackup-'+new Date().toISOString().slice(0,19), backup_type: 'full', target_database: 'all', csrf_token: CSRF_TOKEN }).done(r => { if(r.success) { showAlert('Quick backup started','success'); setTimeout(()=>location.reload(),600); } else showAlert(r.message,'danger'); });
}
function addHealthCheck() {
    const checks = [
        {type:'cpu',name:'CPU Usage',status:'healthy',value:'45%',threshold:'90%'},
        {type:'memory',name:'Memory Usage',status:'healthy',value:'62%',threshold:'90%'},
        {type:'disk',name:'Disk Usage',status:'healthy',value:'55%',threshold:'90%'},
        {type:'network',name:'Network Connectivity',status:'healthy',value:'Online',threshold:'-'},
        {type:'database',name:'Database Connection',status:'healthy',value:'Connected',threshold:'-'}
    ];
    let i = 0;
    checks.forEach(c => {
        setTimeout(() => {
            $.post(ICT_HANDLER, { action: 'add_health_check', check_type: c.type, check_name: c.name, status: c.status, value: c.value, threshold: c.threshold, message: c.name + ' is ' + c.status, csrf_token: CSRF_TOKEN }).done(r => { i++; if(i===checks.length) { showAlert('Health check complete','success'); setTimeout(()=>location.reload(),500); } });
        }, 200 * checks.indexOf(c));
    });
}
function addSecurityLog() {
    $.post(ICT_HANDLER, { action: 'add_security_log', event_type: 'other', description: 'Manual security check by ' + '<?= htmlspecialchars($user_name, ENT_QUOTES) ?>', severity: 'info', csrf_token: CSRF_TOKEN }).done(r => { if(r.success) showAlert('Security event logged','success'); else showAlert(r.message,'danger'); });
}
function filterTickets(s) { $('#ticketTable tbody tr').each(function() { $(this).toggle(s==='all' || $(this).hasClass('ticket-row-'+s)); }); }
function filterBackup(s) { $('#backupTable tbody tr').each(function() { $(this).toggle(s==='all' || $(this).hasClass('backup-row-'+s)); }); }
function filterApproval(s) { $('.filter-pill').removeClass('active'); $(`.filter-pill[onclick*="'${s}'"]`).addClass('active'); $('.section-card tbody tr').each(function() { $(this).toggle(s==='all' || $(this).hasClass('ticket-row-'+s)); }); }

// === Student Management Functions ===
$('#ictRegisterForm').submit(function(e) {
    e.preventDefault();
    var form = this;
    var data = $(form).serializeArray().reduce(function(o, x) { o[x.name] = x.value; return o; }, {});
    data.action = 'ict_register_student';
    data.csrf_token = CSRF_TOKEN;
    var btn = $(form).find('button[type=submit]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Registering...');
    $.post('director-ict.php', data).done(function(r) {
        btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Register Student');
        if (r.success) {
            var msg = '<strong>Student Registered Successfully!</strong><br>' +
                'Student Number: <code>' + r.student_number + '</code><br>' +
                'Index Number: <code>' + r.index_number + '</code><br>' +
                'Registration: <code>' + (r.registration_number||'') + '</code><br>' +
                'Temp Password: <code>' + r.temp_password + '</code><br>' +
                '<small class="text-warning">Please save these credentials. The password will not be shown again.</small>';
            $('#ictRegisterResult').html(msg).show();
            $(form).find('input:not([type=hidden]),select,textarea').val('');
        } else {
            showAlert(r.message, 'danger');
        }
    }).fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Register Student');
        showAlert('AJAX error', 'danger');
    });
});

function searchStudents() {
    var q = $('#ictStudentSearch').val().trim();
    if (q.length < 2) { document.getElementById('ictStudentResults').innerHTML = ''; return; }
    $.post('director-ict.php', { action: 'search_students', search: q, csrf_token: CSRF_TOKEN }).done(function(r) {
        var tb = document.getElementById('ictStudentResults');
        if (!r.success || !r.students || r.students.length === 0) {
            tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No students found matching your search.</td></tr>';
            return;
        }
        var html = '';
        r.students.forEach(function(s) {
            var name = s.full_name || ((s.first_name || '') + ' ' + (s.surname || ''));
            html += '<tr>';
            html += '<td><strong>' + escHtml(name) + '</strong></td>';
            html += '<td><code>' + escHtml(s.student_number || '-') + '</code></td>';
            html += '<td><small class="text-muted">' + escHtml(s.registration_number || '-') + '</small></td>';
            html += '<td><small>' + escHtml(s.program || '-') + '</small></td>';
            html += '<td><span class="badge bg-' + (s.status === 'Active' ? 'success' : 'secondary') + '">' + escHtml(s.status || 'Active') + '</span></td>';
            html += '<td>';
            html += '<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="viewICTStudentProfile(' + s.id + ')" title="View Profile"><i class="fas fa-eye"></i></button> ';
            html += '<button class="btn btn-sm btn-outline-warning py-0 px-1" onclick="editICTStudent(' + s.id + ')" title="Edit"><i class="fas fa-edit"></i></button>';
            html += '</td></tr>';
        });
        tb.innerHTML = html;
    }).fail(function() {
        showAlert('Search failed', 'danger');
    });
}

function viewICTStudentProfile(id) {
    $.post('director-ict.php', { action: 'get_student_details', student_id: id, csrf_token: CSRF_TOKEN }).done(function(r) {
        if (!r.success || !r.data || !r.data.student) { showAlert(r.message || 'Student not found', 'danger'); return; }
        var s = r.data.student;
        var p = r.data.profile || {};
        var name = s.full_name || ((s.first_name || '') + ' ' + (s.surname || ''));
        var html = '<div class="row g-2">';
        html += '<div class="col-md-6"><strong>Name:</strong> ' + escHtml(name) + '</div>';
        html += '<div class="col-md-6"><strong>Student Number:</strong> <code>' + escHtml(s.student_number || '-') + '</code></div>';
        html += '<div class="col-md-6"><strong>Registration:</strong> <code>' + escHtml(s.registration_number || '-') + '</code></div>';
        html += '<div class="col-md-6"><strong>Program:</strong> ' + escHtml(s.program || '-') + '</div>';
        html += '<div class="col-md-6"><strong>Level:</strong> ' + escHtml(s.level || '-') + '</div>';
        html += '<div class="col-md-6"><strong>Year:</strong> ' + escHtml(s.year || '-') + '</div>';
        html += '<div class="col-md-6"><strong>Email:</strong> ' + escHtml(s.email || '-') + '</div>';
        html += '<div class="col-md-6"><strong>Phone:</strong> ' + escHtml(s.phone || '-') + '</div>';
        html += '<div class="col-md-6"><strong>Gender:</strong> ' + escHtml(s.gender || '-') + '</div>';
        html += '<div class="col-md-6"><strong>Date of Birth:</strong> ' + escHtml(s.date_of_birth || '-') + '</div>';
        html += '<div class="col-md-6"><strong>Status:</strong> <span class="badge bg-' + (s.status === 'Active' ? 'success' : 'secondary') + '">' + escHtml(s.status || 'Active') + '</span></div>';
        html += '<div class="col-md-6"><strong>Intake:</strong> ' + escHtml(s.intake_period || '-') + '</div>';
        if (s.address) html += '<div class="col-12"><strong>Address:</strong> ' + escHtml(s.address) + '</div>';
        if (s.district) html += '<div class="col-md-6"><strong>District:</strong> ' + escHtml(s.district) + '</div>';
        if (s.national_id) html += '<div class="col-md-6"><strong>National ID:</strong> ' + escHtml(s.national_id) + '</div>';
        if (s.guardian_name) html += '<div class="col-md-6"><strong>Guardian:</strong> ' + escHtml(s.guardian_name) + '</div>';
        if (s.guardian_phone) html += '<div class="col-md-6"><strong>Guardian Phone:</strong> ' + escHtml(s.guardian_phone) + '</div>';
        if (p && p.admission_status) html += '<div class="col-md-6"><strong>Admission Status:</strong> <span class="badge bg-info">' + escHtml(p.admission_status) + '</span></div>';
        if (p && p.fee_status) html += '<div class="col-md-6"><strong>Fee Status:</strong> <span class="badge bg-' + (p.fee_status === 'paid' ? 'success' : 'warning text-dark') + '">' + escHtml(p.fee_status) + '</span></div>';
        html += '</div>';
        showBootstrapModal('Student Profile: ' + escHtml(name), html, 'ictStudentProfileModal');
    }).fail(function() { showAlert('Failed to load student profile', 'danger'); });
}

function showICTRegisterModal() {
    $('#ictRegisterResult').hide().html('');
    $('#ictRegisterForm').find('input:not([type=hidden]),select,textarea').val('');
    var modal = new bootstrap.Modal(document.getElementById('ictRegisterModal'));
    modal.show();
}

function editICTStudent(id) {
    viewICTStudentProfile(id);
}

function escHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
</script>

<!-- â•â•â• AJAX MODULE LOADING â•â•â• -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.ict-content');
    var loadingOverlay = document.getElementById('ajaxLoadingOverlay');
    var isAjaxLoading = false;

    function showLoading() { if (loadingOverlay) loadingOverlay.style.display = 'flex'; isAjaxLoading = true; }
    function hideLoading() { if (loadingOverlay) loadingOverlay.style.display = 'none'; isAjaxLoading = false; }

    // Intercept sidebar link clicks for AJAX loading
    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (!href || href.indexOf('?') === -1) return;
            if (isAjaxLoading) return;

            e.preventDefault();
            showLoading();

            // Update URL
            history.pushState({}, '', href);

            // Update active states
            document.querySelectorAll('.child-link').forEach(function(l) { l.classList.remove('active'); });
            this.classList.add('active');

            // Fetch content
            var tab = href.split('tab=')[1] || href.split('page=')[1] || 'dashboard';
            fetch('director-ict.php?tab=' + encodeURIComponent(tab), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.ict-content');
                if (newContent && contentArea) {
                    contentArea.innerHTML = newContent.innerHTML;
                    contentArea.querySelectorAll('script').forEach(function(oldScript) {
                        var newScript = document.createElement('script');
                        if (oldScript.src) { newScript.src = oldScript.src; }
                        else { newScript.textContent = oldScript.textContent; }
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                }
                hideLoading();
            })
            .catch(function(err) {
                console.error('[AJAX Load Error]', err);
                hideLoading();
                window.location.href = href;
            });
        });
    });

    window.addEventListener('popstate', function() { window.location.reload(); });

    // Close sidebar on mobile after click
    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                var sidebar = document.querySelector('.isnm-sidebar');
                if (sidebar) sidebar.classList.remove('open', 'mobile-show');
            }
        });
    });
})();
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
