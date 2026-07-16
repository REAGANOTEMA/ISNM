<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
$ctx = bootstrapStaffDashboard(['guild president']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? 'User';
$user_role = $user['role'] ?? '';
$students_db_name = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

// â”€â”€ Page routing â”€â”€
$pageToSection = [
    'home'          => 'overview',
    'overview'      => 'overview',
    'student-body'  => 'student-body',
    'guild_management' => 'student-body',
    'welfare'       => 'welfare',
    'events'        => 'events',
    'sports_events' => 'events',
    'feedback'      => 'feedback',
    'reports'       => 'reports',
];
$page  = $_GET['page'] ?? 'home';
$section = $pageToSection[$page] ?? 'overview';

require_once __DIR__ . '/../includes/student_set_viewer.php';

$totalStudents = 0; $activeStudents = 0; $programs = [];
if ($studentsDb) {
    $r = $studentsDb->query("SELECT COUNT(*) as c FROM students");
    if ($r) $totalStudents = (int)$r->fetch_assoc()['c'];
    $r = $studentsDb->query("SELECT COUNT(*) as c FROM students WHERE status='Active'");
    if ($r) $activeStudents = (int)$r->fetch_assoc()['c'];
    $r = $studentsDb->query("SELECT DISTINCT program FROM students WHERE program IS NOT NULL AND program != '' ORDER BY program");
    if ($r) while ($row = $r->fetch_assoc()) $programs[] = $row['program'];
}

// â”€â”€ Data for sections â”€â”€
$welfareCases = []; $welfareOpen = 0; $welfareResolved = 0; $counselingSessions = [];
$upcomingEvents = []; $sportsEvents = []; $studentDiscipline = []; $disciplineOpen = 0;
if ($staffDb) {
    $r = $staffDb->query("SELECT wc.*, s.full_name as student_name FROM welfare_cases wc LEFT JOIN {$students_db_name}.students s ON wc.student_id=s.id ORDER BY wc.created_at DESC LIMIT 15");
    if ($r) $welfareCases = $r->fetch_all(MYSQLI_ASSOC);
    $r = $staffDb->query("SELECT COUNT(*) c FROM welfare_cases WHERE status IN ('open','in_progress')");
    if ($r) $welfareOpen = (int)$r->fetch_assoc()['c'];
    $r = $staffDb->query("SELECT COUNT(*) c FROM welfare_cases WHERE status IN ('resolved','closed')");
    if ($r) $welfareResolved = (int)$r->fetch_assoc()['c'];
    $r = $staffDb->query("SELECT cs.*, s.full_name as student_name FROM counseling_sessions cs LEFT JOIN igangaschool_students.students s ON cs.student_id=s.id ORDER BY cs.session_date DESC LIMIT 10");
    if ($r) $counselingSessions = $r->fetch_all(MYSQLI_ASSOC);
    $r = $staffDb->query("SELECT * FROM calendar_events WHERE event_date >= CURDATE() AND is_active=1 ORDER BY event_date ASC LIMIT 10");
    if ($r) $upcomingEvents = $r->fetch_all(MYSQLI_ASSOC);
    $r = $staffDb->query("SELECT * FROM sports_events WHERE event_date >= NOW() ORDER BY event_date ASC LIMIT 10");
    if ($r) $sportsEvents = $r->fetch_all(MYSQLI_ASSOC);
    $r = $staffDb->query("SELECT sd.*, s.full_name as student_name FROM student_discipline_records sd LEFT JOIN igangaschool_students.students s ON sd.student_id=s.id ORDER BY sd.created_at DESC LIMIT 15");
    if ($r) $studentDiscipline = $r->fetch_all(MYSQLI_ASSOC);
    $r = $staffDb->query("SELECT COUNT(*) c FROM student_discipline_records WHERE status IN ('Pending','Open','Under Investigation')");
    if ($r) $disciplineOpen = (int)$r->fetch_assoc()['c'];
}

// ── CSRF helpers ──
function gp_csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function gp_verify_csrf() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ── AJAX POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staffDb) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // Ensure tables
    $staffDb->query("CREATE TABLE IF NOT EXISTS welfare_cases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        case_type VARCHAR(100),
        description TEXT,
        status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
        reported_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $staffDb->query("CREATE TABLE IF NOT EXISTS guild_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        category VARCHAR(100),
        subject VARCHAR(255),
        message TEXT,
        priority ENUM('normal','important','urgent') DEFAULT 'normal',
        status ENUM('pending','reviewed','acted') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $staffDb->query("CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE,
        start_time TIME,
        end_time TIME,
        location VARCHAR(255),
        event_type VARCHAR(100),
        is_active TINYINT(1) DEFAULT 1,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // ── Welfare: Create case ──
    if ($action === 'create_welfare') {
        if (!gp_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF']); exit; }
        $studentId = (int)($_POST['student_id'] ?? 0);
        $caseType  = trim($_POST['case_type'] ?? '');
        $desc      = trim($_POST['description'] ?? '');
        if (!$studentId || !$caseType) { echo json_encode(['success'=>false,'message'=>'Student and case type required']); exit; }
        $stmt = $staffDb->prepare("INSERT INTO welfare_cases (student_id,case_type,description,reported_by) VALUES (?,?,?,?)");
        $stmt->bind_param('issi', $studentId, $caseType, $desc, $user_id);
        if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Welfare case created','id'=>$staffDb->insert_id]); }
        else { echo json_encode(['success'=>false,'message'=>'Failed to create welfare case']); }
        $stmt->close(); exit;
    }

    // ── Welfare: Update status ──
    if ($action === 'update_welfare') {
        if (!gp_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF']); exit; }
        $caseId   = (int)($_POST['case_id'] ?? 0);
        $newState = $_POST['status'] ?? 'open';
        if (!in_array($newState, ['open','in_progress','resolved','closed'])) { echo json_encode(['success'=>false,'message'=>'Invalid status']); exit; }
        $stmt = $staffDb->prepare("UPDATE welfare_cases SET status=? WHERE id=?");
        $stmt->bind_param('si', $newState, $caseId);
        if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Welfare case updated']); }
        else { echo json_encode(['success'=>false,'message'=>'Failed to update']); }
        $stmt->close(); exit;
    }

    // ── Feedback: Submit suggestion ──
    if ($action === 'submit_feedback') {
        if (!gp_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF']); exit; }
        $category = trim($_POST['category'] ?? 'General');
        $subject  = trim($_POST['subject'] ?? '');
        $message  = trim($_POST['message'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';
        if (!$subject || !$message) { echo json_encode(['success'=>false,'message'=>'Subject and message required']); exit; }
        $stmt = $staffDb->prepare("INSERT INTO guild_feedback (staff_id,category,subject,message,priority) VALUES (?,?,?,?,?)");
        $stmt->bind_param('issss', $user_id, $category, $subject, $message, $priority);
        if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Feedback submitted','id'=>$staffDb->insert_id]); }
        else { echo json_encode(['success'=>false,'message'=>'Failed to submit feedback']); }
        $stmt->close(); exit;
    }

    // ── Events: Create guild event ──
    if ($action === 'create_event') {
        if (!gp_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF']); exit; }
        $title     = trim($_POST['title'] ?? '');
        $desc      = trim($_POST['description'] ?? '');
        $eventDate = $_POST['event_date'] ?? null;
        $startTime = $_POST['start_time'] ?? null;
        $endTime   = $_POST['end_time'] ?? null;
        $location  = trim($_POST['location'] ?? '');
        $eventType = trim($_POST['event_type'] ?? 'Guild');
        if (!$title || !$eventDate) { echo json_encode(['success'=>false,'message'=>'Title and date required']); exit; }
        $stmt = $staffDb->prepare("INSERT INTO calendar_events (title,description,event_date,start_time,end_time,LOCATION,event_type,created_by) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssi', $title, $desc, $eventDate, $startTime, $endTime, $location, $eventType, $user_id);
        if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Event created','id'=>$staffDb->insert_id]); }
        else { echo json_encode(['success'=>false,'message'=>'Failed to create event']); }
        $stmt->close(); exit;
    }

    // ── Events: Delete event ──
    if ($action === 'delete_event') {
        if (!gp_verify_csrf()) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF']); exit; }
        $eventId = (int)($_POST['event_id'] ?? 0);
        $stmt = $staffDb->prepare("DELETE FROM calendar_events WHERE id=? AND created_by=?");
        $stmt->bind_param('ii', $eventId, $user_id);
        if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Event deleted']); }
        else { echo json_encode(['success'=>false,'message'=>'Failed to delete']); }
        $stmt->close(); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>

.gld-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.gld-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="gld-content">
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <input type="text" class="form-control form-control-sm" id="guildFilterAll" placeholder="Filter all tables..." style="width:250px" onkeyup="filterGuildTables(this.value)">
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print page"><i class="fas fa-print me-1"></i>Print</button>
        <a href="mailto:?subject=Guild%20Dashboard%20Report&body=Guild%20President%20Dashboard%20-%20<?= urlencode(date('Y-m-d')) ?>" class="btn btn-sm btn-outline-info" title="Email report"><i class="fas fa-envelope"></i></a>
    </div>
</div>
<?php switch ($section):
    case 'student-body': ?>
        <h1><i class="fas fa-users"></i> Student Body</h1>
        <p>Student body management and representation.</p>
        <?php renderStudentSetViewer($studentsDb, ['title' => 'Student Records','icon' => 'fa-user-graduate','show_all' => true,'per_page' => 50,'show_statement_link' => false]); ?>
        <?php break;
    case 'welfare': ?>
        <div class="content-header d-flex justify-content-between align-items-center"><h1><i class="fas fa-heart me-2"></i>Student Welfare</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="mb-3">
            <button class="btn btn-primary btn-sm" onclick="openGuildModal('createWelfare')"><i class="fas fa-plus me-1"></i>Report Welfare Case</button>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6>Open Cases</h6><h3 class="text-warning"><?= $welfareOpen ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6>Resolved</h6><h3 class="text-success"><?= $welfareResolved ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6>Counseling Sessions</h6><h3 class="text-info"><?= count($counselingSessions) ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6>Discipline Cases</h6><h3 class="text-danger"><?= $disciplineOpen ?></h3></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><div class="card"><div class="card-body"><h5><i class="fas fa-notes-medical me-2"></i>Recent Welfare Cases</h5>
                <input type="text" class="form-control form-control-sm mb-2" placeholder="Filter welfare cases..." onkeyup="filterGuildTables(this.value)">
                <?php if (empty($welfareCases)): ?><p class="text-muted text-center py-3">No welfare cases recorded.</p>
                <?php else: ?>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Type</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($welfareCases as $w): ?><tr><td><?= htmlspecialchars($w['student_name']??$w['student_id']??'-') ?></td><td><?= htmlspecialchars($w['case_type']??'-') ?></td><td><span class="badge bg-<?= in_array($w['status']??'',['resolved','closed'])?'success':(($w['status']??'')==='open'?'warning':'secondary') ?>"><?= htmlspecialchars($w['status']??'N/A') ?></span></td><td><?= htmlspecialchars($w['created_at']??'') ?></td><td>
                    <select class="form-select form-select-sm d-inline-block w-auto" style="width:100px" onchange="updateWelfareStatus(<?= (int)$w['id'] ?>, this.value)">
                        <option value="open" <?= ($w['status']??'')==='open'?'selected':'' ?>>Open</option>
                        <option value="in_progress" <?= ($w['status']??'')==='in_progress'?'selected':'' ?>>In Progress</option>
                        <option value="resolved" <?= ($w['status']??'')==='resolved'?'selected':'' ?>>Resolved</option>
                        <option value="closed" <?= ($w['status']??'')==='closed'?'selected':'' ?>>Closed</option>
                    </select>
                </td></tr><?php endforeach; ?>
                </tbody></table></div>
                <?php endif; ?>
            </div></div></div>
            <div class="col-md-6"><div class="card"><div class="card-body"><h5><i class="fas fa-comments me-2"></i>Recent Counseling Sessions</h5>
                <?php if (empty($counselingSessions)): ?><p class="text-muted text-center py-3">No counseling sessions recorded.</p>
                <?php else: ?>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Type</th><th>Date</th><th>Status</th></tr></thead><tbody>
                <?php foreach ($counselingSessions as $cs): ?><tr><td><?= htmlspecialchars($cs['student_name']??$cs['student_id']??'-') ?></td><td><?= htmlspecialchars($cs['session_type']??'-') ?></td><td><?= htmlspecialchars($cs['session_date']??'') ?></td><td><span class="badge bg-<?= ($cs['status']??'')==='completed'?'success':'info' ?>"><?= htmlspecialchars($cs['status']??'Scheduled') ?></span></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <?php endif; ?>
            </div></div></div>
        </div>
        <?php break;
    case 'events': ?>
        <div class="content-header d-flex justify-content-between align-items-center"><h1><i class="fas fa-calendar-alt me-2"></i>Events</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="mb-3">
            <button class="btn btn-primary btn-sm" onclick="openGuildModal('createEvent')"><i class="fas fa-plus me-1"></i>Create Guild Event</button>
        </div>
        <div class="row g-3">
            <div class="col-md-7"><div class="card"><div class="card-body"><h5><i class="fas fa-calendar-day me-2"></i>Upcoming Events</h5>
                <?php if (empty($upcomingEvents)): ?><p class="text-muted text-center py-3">No upcoming events scheduled.</p>
                <?php else: ?>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Title</th><th>Date</th><th>Location</th><th>Type</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($upcomingEvents as $e): ?><tr><td><strong><?= htmlspecialchars($e['title']) ?></strong></td><td><?= htmlspecialchars($e['event_date']) ?> <?= htmlspecialchars($e['start_time']??'') ?></td><td><?= htmlspecialchars($e['location']??'-') ?></td><td><span class="badge bg-info"><?= htmlspecialchars($e['event_type']??'General') ?></span></td><td>
                    <?php if ((int)($e['created_by'] ?? 0) === $user_id): ?>
                    <button class="btn btn-danger btn-sm" onclick="if(confirm('Delete this event?'))deleteGuildEvent(<?= (int)$e['id'] ?>)"><i class="fas fa-trash"></i></button>
                    <?php endif; ?>
                </td></tr><?php endforeach; ?>
                </tbody></table></div>
                <?php endif; ?>
            </div></div></div>
            <div class="col-md-5"><div class="card"><div class="card-body"><h5><i class="fas fa-futbol me-2"></i>Sports Events</h5>
                <?php if (empty($sportsEvents)): ?><p class="text-muted text-center py-3">No upcoming sports events.</p>
                <?php else: ?>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Event</th><th>Sport</th><th>Date</th></tr></thead><tbody>
                <?php foreach ($sportsEvents as $se): ?><tr><td><?= htmlspecialchars($se['name']) ?></td><td><?= htmlspecialchars($se['sport_type']??'-') ?></td><td><?= htmlspecialchars($se['event_date']??'') ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <?php endif; ?>
            </div></div></div>
        </div>
        <?php break;
    case 'feedback': ?>
        <div class="content-header d-flex justify-content-between align-items-center"><h1><i class="fas fa-comment-dots me-2"></i>Student Feedback & Discipline</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="mb-3">
            <button class="btn btn-primary btn-sm" onclick="openGuildModal('submitFeedback')"><i class="fas fa-paper-plane me-1"></i>Submit Suggestion/Feedback</button>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><div class="card"><div class="card-body"><h5><i class="fas fa-gavel me-2"></i>Recent Discipline Records</h5>
                <?php if (empty($studentDiscipline)): ?><p class="text-muted text-center py-3">No discipline records found.</p>
                <?php else: ?>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Offense</th><th>Status</th><th>Date</th></tr></thead><tbody>
                <?php foreach ($studentDiscipline as $d): ?><tr><td><?= htmlspecialchars($d['student_name']??$d['student_id']??'-') ?></td><td><?= htmlspecialchars(mb_substr($d['offense']??$d['description']??$d['incident']??'',0,50)) ?></td><td><span class="badge bg-<?= ($d['status']??'')==='Resolved'?'success':'danger' ?>"><?= htmlspecialchars($d['status']??'Open') ?></span></td><td><?= htmlspecialchars($d['created_at']??$d['incident_date']??'') ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <?php endif; ?>
                <p class="mt-2"><a href="student-discipline.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i>Manage Discipline</a></p>
            </div></div></div>
            <div class="col-md-6"><div class="card"><div class="card-body"><h5><i class="fas fa-lightbulb me-2"></i>Student Requests & Suggestions</h5>
                <?php
                $studentRequests = [];
                if ($studentsDb) { $r = $studentsDb->query("SELECT * FROM student_requests ORDER BY created_at DESC LIMIT 10"); if ($r) $studentRequests = $r->fetch_all(MYSQLI_ASSOC); }
                if (empty($studentRequests)): ?><p class="text-muted text-center py-3">No student requests yet.</p>
                <?php else: ?>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Type</th><th>Reason</th><th>Status</th><th>Date</th></tr></thead><tbody>
                <?php foreach ($studentRequests as $sr): ?><tr><td><?= htmlspecialchars($sr['request_type']??$sr['type']??'-') ?></td><td><?= htmlspecialchars(mb_substr($sr['reason']??$sr['details']??'',0,50)) ?></td><td><span class="badge bg-<?= ($sr['status']??'')==='Approved'?'success':(($sr['status']??'')==='Pending'?'warning':'secondary') ?>"><?= htmlspecialchars($sr['status']??'Pending') ?></span></td><td><?= htmlspecialchars($sr['created_at']??'') ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <?php endif; ?>
                <p class="mt-2"><a href="student-requests-desk.php" class="btn btn-sm btn-outline-info"><i class="fas fa-external-link-alt me-1"></i>View All Requests</a></p>
            </div></div></div>
        </div>
        <?php break;
    case 'reports': ?>
        <div class="content-header"><h1><i class="fas fa-file-alt me-2"></i>Guild Reports & Analytics</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card text-center border-primary"><div class="card-body"><h6 class="text-primary">Total Students</h6><h3 class="text-primary"><?= $totalStudents ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-center border-success"><div class="card-body"><h6 class="text-success">Active Students</h6><h3 class="text-success"><?= $activeStudents ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-center border-warning"><div class="card-body"><h6 class="text-warning">Open Welfare Cases</h6><h3 class="text-warning"><?= $welfareOpen ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-center border-info"><div class="card-body"><h6 class="text-info">Programs</h6><h3 class="text-info"><?= count($programs) ?></h3></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><div class="card"><div class="card-body"><h5><i class="fas fa-chart-pie me-2"></i>Summary</h5>
                <table class="table table-sm"><tbody>
                    <tr><td>Total Welfare Cases</td><td class="fw-bold"><?= $welfareOpen + $welfareResolved ?></td></tr>
                    <tr><td>Resolved Cases</td><td class="fw-bold text-success"><?= $welfareResolved ?></td></tr>
                    <tr><td>Open Cases</td><td class="fw-bold text-warning"><?= $welfareOpen ?></td></tr>
                    <tr><td>Counseling Sessions</td><td class="fw-bold text-info"><?= count($counselingSessions) ?></td></tr>
                    <tr><td>Upcoming Events</td><td class="fw-bold"><?= count($upcomingEvents) ?></td></tr>
                    <tr><td>Sports Events</td><td class="fw-bold"><?= count($sportsEvents) ?></td></tr>
                    <tr><td>Active Discipline Cases</td><td class="fw-bold text-danger"><?= $disciplineOpen ?></td></tr>
                </tbody></table>
            </div></div></div>
            <div class="col-md-6"><div class="card"><div class="card-body"><h5><i class="fas fa-link me-2"></i>Quick Links</h5>
                <div class="d-grid gap-2">
                    <a href="student-management.php" class="btn btn-outline-primary"><i class="fas fa-user-graduate me-2"></i>Student Management</a>
                    <a href="counseling-welfare.php" class="btn btn-outline-success"><i class="fas fa-heart me-2"></i>Counselling & Welfare</a>
                    <a href="student-discipline.php" class="btn btn-outline-danger"><i class="fas fa-gavel me-2"></i>Student Discipline</a>
                    <a href="student-requests-desk.php" class="btn btn-outline-info"><i class="fas fa-inbox me-2"></i>Student Requests</a>
                    <a href="../student-directory.php" class="btn btn-outline-secondary"><i class="fas fa-address-book me-2"></i>Student Directory</a>
                </div>
            </div></div></div>
        </div>
        <?php break;
    default: ?>
        <h1><i class="fas fa-crown"></i> Guild President Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($user_name ?? 'User'); ?></p>
        <div class="text-center mb-3">
            <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
            <a href="../store_request.php" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-shopping-cart me-1"></i>Store</a>
            <a href="../news.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-newspaper me-1"></i>News</a>
            <a href="student-records.php" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-users-gear me-1"></i> Students</a>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="fs-1 text-primary mb-2"><i class="fas fa-user-graduate"></i></div>
                    <h3 class="fw-bold mb-0"><?= $totalStudents ?></h3>
                    <small class="text-muted">Total Students</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="fs-1 text-success mb-2"><i class="fas fa-check-circle"></i></div>
                    <h3 class="fw-bold mb-0"><?= $activeStudents ?></h3>
                    <small class="text-muted">Active Students</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="fs-1 text-info mb-2"><i class="fas fa-book"></i></div>
                    <h3 class="fw-bold mb-0"><?= count($programs) ?></h3>
                    <small class="text-muted">Programs</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="fs-1 text-warning mb-2"><i class="fas fa-users"></i></div>
                    <h3 class="fw-bold mb-0"><?= count($programs) > 0 ? round($activeStudents / max(count($programs),1)) : 0 ?></h3>
                    <small class="text-muted">Avg per Program</small>
                </div>
            </div>
        </div>
        <div class="card">
            <h3>Student Leadership Panel</h3>
            <p>Access student related information and manage student affairs.</p>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" onclick="location.href='../student-login.php'">
                    <i class="fas fa-users"></i> View Student Platform
                </button>
                <button class="btn btn-outline-info" onclick="location.href='student-records.php'">
                    <i class="fas fa-users-gear"></i> Student Records
                </button>
            </div>
        </div>
        <div class="card">
            <h5 class="fw-bold mb-3"><i class="fas fa-user-graduate me-2"></i>Browse Students</h5>
            <?php renderStudentSetViewer($studentsDb, ['title' => 'Student Records','icon' => 'fa-user-graduate','show_all' => true,'per_page' => 50,'show_statement_link' => false]); ?>
        </div>
        <?php break;
endswitch; ?>
</div>

<!-- Guild Action Modal -->
<div class="modal fade" id="guildModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="guildModalTitle">Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="guildModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="guildModalAction">Submit</button>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
function openGuildModal(action) {
    const modal = new bootstrap.Modal(document.getElementById('guildModal'));
    const title = document.getElementById('guildModalTitle');
    const body  = document.getElementById('guildModalBody');
    window._guildAction = action;

    switch(action) {
        case 'createWelfare':
            title.textContent = 'Report Welfare Case';
            body.innerHTML = `
                <form>
                    <div class="mb-3"><label class="form-label">Student ID</label><input type="number" class="form-control" id="gwStudentId" required></div>
                    <div class="mb-3"><label class="form-label">Case Type</label>
                        <select class="form-select" id="gwCaseType" required>
                            <option value="">Select...</option>
                            <option value="academic">Academic</option><option value="health">Health</option><option value="financial">Financial</option>
                            <option value="psychosocial">Psychosocial</option><option value="housing">Housing</option><option value="other">Other</option>
                        </select></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="gwDesc" rows="4" required></textarea></div>
                </form>`;
            break;
        case 'createEvent':
            title.textContent = 'Create Guild Event';
            body.innerHTML = `
                <form>
                    <div class="mb-3"><label class="form-label">Event Title</label><input type="text" class="form-control" id="geTitle" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="geDesc" rows="3"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Date</label><input type="date" class="form-control" id="geDate" required></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Start</label><input type="time" class="form-control" id="geStart"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">End</label><input type="time" class="form-control" id="geEnd"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3"><label class="form-label">Location</label><input type="text" class="form-control" id="geLocation"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Type</label>
                            <select class="form-select" id="geType"><option value="Guild">Guild</option><option value="Sports">Sports</option><option value="Academic">Academic</option><option value="Social">Social</option></select></div>
                    </div>
                </form>`;
            break;
        case 'submitFeedback':
            title.textContent = 'Submit Suggestion / Feedback';
            body.innerHTML = `
                <form>
                    <div class="mb-3"><label class="form-label">Category</label>
                        <select class="form-select" id="gfCategory"><option value="General">General</option><option value="Academic">Academic</option><option value="Welfare">Welfare</option><option value="Infrastructure">Infrastructure</option><option value="Other">Other</option></select></div>
                    <div class="mb-3"><label class="form-label">Subject</label><input type="text" class="form-control" id="gfSubject" required></div>
                    <div class="mb-3"><label class="form-label">Priority</label>
                        <select class="form-select" id="gfPriority"><option value="normal">Normal</option><option value="important">Important</option><option value="urgent">Urgent</option></select></div>
                    <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" id="gfMessage" rows="5" required></textarea></div>
                </form>`;
            break;
    }
    modal.show();
}

document.getElementById('guildModalAction').addEventListener('click', function() {
    const action = window._guildAction;
    const fd = new FormData();
    fd.append('action', action === 'createWelfare' ? 'create_welfare' : action === 'createEvent' ? 'create_event' : 'submit_feedback');
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    const body = document.getElementById('guildModalBody');

    if (action === 'createWelfare') {
        fd.append('student_id', document.getElementById('gwStudentId').value);
        fd.append('case_type', document.getElementById('gwCaseType').value);
        fd.append('description', document.getElementById('gwDesc').value);
        if (!fd.get('student_id') || !fd.get('case_type')) { alert('Fill required fields.'); return; }
    } else if (action === 'createEvent') {
        fd.append('title', document.getElementById('geTitle').value);
        fd.append('description', document.getElementById('geDesc').value);
        fd.append('event_date', document.getElementById('geDate').value);
        fd.append('start_time', document.getElementById('geStart').value);
        fd.append('end_time', document.getElementById('geEnd').value);
        fd.append('location', document.getElementById('geLocation').value);
        fd.append('event_type', document.getElementById('geType').value);
        if (!fd.get('title') || !fd.get('event_date')) { alert('Title and date required.'); return; }
    } else if (action === 'submitFeedback') {
        fd.append('category', document.getElementById('gfCategory').value);
        fd.append('subject', document.getElementById('gfSubject').value);
        fd.append('priority', document.getElementById('gfPriority').value);
        fd.append('message', document.getElementById('gfMessage').value);
        if (!fd.get('subject') || !fd.get('message')) { alert('Subject and message required.'); return; }
    }

    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><p class="mt-3">Submitting...</p></div>';
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) { body.innerHTML = '<div class="alert alert-success">' + resp.message + '</div>'; setTimeout(()=>location.reload(), 1000); }
            else { body.innerHTML = '<div class="alert alert-danger">' + (resp.message||'Failed') + '</div>'; }
        })
        .catch(() => { body.innerHTML = '<div class="alert alert-danger">Network error.</div>'; });
});

function updateWelfareStatus(caseId, newStatus) {
    const fd = new FormData();
    fd.append('action', 'update_welfare');
    fd.append('case_id', caseId);
    fd.append('status', newStatus);
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) { location.reload(); }
            else { alert(resp.message || 'Failed to update status.'); }
        })
        .catch(() => alert('Network error.'));
}

function deleteGuildEvent(eventId) {
    const fd = new FormData();
    fd.append('action', 'delete_event');
    fd.append('event_id', eventId);
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) { location.reload(); }
            else { alert(resp.message || 'Failed to delete event.'); }
        })
        .catch(() => alert('Network error.'));
}
</script>
</body>
</html>
