<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['guild president']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? 'User';
$user_role = $user['role'] ?? '';

// ── Page routing ──
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.gld-topbar{background:linear-gradient(135deg,#b45309,#92400e,#78350f);padding:0 32px;height:64px;display:flex;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.15)}.gld-topbar-content{width:100%;display:flex;align-items:center;justify-content:space-between}.gld-topbar-left{display:flex;flex-direction:column}.gld-topbar-title{color:#fff;font-size:18px;font-weight:700;letter-spacing:.3px}.gld-topbar-subtitle{color:#fde68a;font-size:12px;margin-top:-2px}.gld-topbar-right{display:flex;align-items:center;gap:12px}.gld-date-badge{background:rgba(255,255,255,.15);color:#fff;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;backdrop-filter:blur(4px)}.gld-print-btn,.gld-logout-btn{color:#fde68a;font-size:16px;padding:6px 10px;border-radius:8px;transition:all .2s;text-decoration:none}.gld-print-btn:hover,.gld-logout-btn:hover{background:rgba(255,255,255,.2);color:#fff}
.gld-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.gld-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="gld-topbar"><div class="gld-topbar-content"><div class="gld-topbar-left"><div class="gld-topbar-title">Guild President</div><div class="gld-topbar-subtitle">Student Governance &amp; Representation</div></div><div class="gld-topbar-right"><span class="gld-date-badge"><i class="fas fa-calendar-alt me-1"></i><?= date('l, F j, Y') ?></span><a href="#" class="gld-print-btn" onclick="window.print()"><i class="fas fa-print"></i></a><a href="../logout.php" class="gld-logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div></div>
<div class="gld-content">
<?php switch ($section):
    case 'student-body': ?>
        <h1><i class="fas fa-users"></i> Student Body</h1>
        <p>Student body management and representation.</p>
        <?php renderStudentSetViewer($studentsDb, ['title' => 'Student Records','icon' => 'fa-user-graduate','show_all' => true,'per_page' => 50,'show_statement_link' => false]); ?>
        <?php break;
    case 'welfare': ?>
        <h1><i class="fas fa-heart"></i> Student Welfare</h1>
        <p class="text-muted">Welfare management module.</p>
        <?php break;
    case 'events': ?>
        <h1><i class="fas fa-calendar-alt"></i> Events</h1>
        <p class="text-muted">Student events management.</p>
        <?php break;
    case 'feedback': ?>
        <h1><i class="fas fa-comment-dots"></i> Feedback</h1>
        <p class="text-muted">Student feedback and suggestions.</p>
        <?php break;
    case 'reports': ?>
        <h1><i class="fas fa-file-alt"></i> Guild Reports</h1>
        <p class="text-muted">Reports and analytics.</p>
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
