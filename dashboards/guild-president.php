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
    'welfare'       => 'welfare',
    'events'        => 'events',
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
<?php switch ($section):
    case 'student-body': ?>
        <h1><i class="fas fa-users"></i> Student Body</h1>
        <p>Student body management and representation.</p>
        <?php renderStudentSetViewer($studentsDb, ['title' => 'Student Records','icon' => 'fa-user-graduate','show_all' => true,'per_page' => 50,'show_statement_link' => false]); ?>
        <?php break;
    case 'welfare': ?>
        <div class="content-header"><h1><i class="fas fa-heart me-2"></i>Student Welfare</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6>Open Cases</h6><h3 class="text-warning"><?= $welfareOpen ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6>Resolved</h6><h3 class="text-success"><?= $welfareResolved ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6>Counseling Sessions</h6><h3 class="text-info"><?= count($counselingSessions) ?></h3></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><h6>Discipline Cases</h6><h3 class="text-danger"><?= $disciplineOpen ?></h3></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><div class="card"><div class="card-body"><h5><i class="fas fa-notes-medical me-2"></i>Recent Welfare Cases</h5>
                <?php if (empty($welfareCases)): ?><p class="text-muted text-center py-3">No welfare cases recorded.</p>
                <?php else: ?>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Type</th><th>Status</th><th>Date</th></tr></thead><tbody>
                <?php foreach ($welfareCases as $w): ?><tr><td><?= htmlspecialchars($w['student_name']??$w['student_id']??'-') ?></td><td><?= htmlspecialchars($w['case_type']??'-') ?></td><td><span class="badge bg-<?= in_array($w['status']??'',['resolved','closed'])?'success':(($w['status']??'')==='open'?'warning':'secondary') ?>"><?= htmlspecialchars($w['status']??'N/A') ?></span></td><td><?= htmlspecialchars($w['created_at']??'') ?></td></tr><?php endforeach; ?>
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
        <div class="content-header"><h1><i class="fas fa-calendar-alt me-2"></i>Events</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
        <div class="row g-3">
            <div class="col-md-7"><div class="card"><div class="card-body"><h5><i class="fas fa-calendar-day me-2"></i>Upcoming Events</h5>
                <?php if (empty($upcomingEvents)): ?><p class="text-muted text-center py-3">No upcoming events scheduled.</p>
                <?php else: ?>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Title</th><th>Date</th><th>Location</th><th>Type</th></tr></thead><tbody>
                <?php foreach ($upcomingEvents as $e): ?><tr><td><strong><?= htmlspecialchars($e['title']) ?></strong></td><td><?= htmlspecialchars($e['event_date']) ?> <?= htmlspecialchars($e['start_time']??'') ?></td><td><?= htmlspecialchars($e['location']??'-') ?></td><td><span class="badge bg-info"><?= htmlspecialchars($e['event_type']??'General') ?></span></td></tr><?php endforeach; ?>
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
        <div class="content-header"><h1><i class="fas fa-comment-dots me-2"></i>Student Feedback & Discipline</h1><span class="text-muted"><?= date('l, d M Y') ?></span></div>
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
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
