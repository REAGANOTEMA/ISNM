<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';

$ctx = bootstrapStaffDashboard(['head of nursing']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Head of Nursing';
$user_id = (int)($user['id'] ?? 0);

// ── Page routing ──
$pageToSection = [
    'home'       => 'overview',
    'overview'   => 'overview',
    'students'   => 'students',
    'clinical'   => 'clinical',
    'timetable'  => 'timetable',
    'courses'    => 'courses',
    'staff'      => 'staff',
];
$page  = $_GET['page'] ?? 'home';
$section = $pageToSection[$page] ?? 'overview';

$profileImageUrl = '../images/username.png';
$profileSettingsFile = __DIR__ . '/../includes/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    include_once $profileSettingsFile;
    if (function_exists('getStaffProfileImageUrl')) {
        $url = getStaffProfileImageUrl($user_id);
        if ($url) $profileImageUrl = $url;
    }
}

// Set dashboard statistics from database
$total_students = 0;
$total_staff = 0;
$active_programs = 0;
$nursing_courses = 0;

try {
    if ($ctx['students']) {
        $result = $ctx['students']->query("SELECT COUNT(*) as cnt FROM students");
        if ($result) $total_students = (int)$result->fetch_assoc()['cnt'];
    }
    $staff_result = $conn->query("SELECT COUNT(*) as cnt FROM staff");
    if ($staff_result) $total_staff = (int)$staff_result->fetch_assoc()['cnt'];
    $prog_result = $conn->query("SELECT COUNT(*) as cnt FROM academic_programs WHERE department LIKE '%Nursing%' AND status='Active'");
    if ($prog_result) $active_programs = (int)$prog_result->fetch_assoc()['cnt'];
    $course_result = $conn->query("SELECT COUNT(DISTINCT course_code) as cnt FROM course_assignments WHERE course_name LIKE '%Nursing%' AND status='Active'");
    if ($course_result) $nursing_courses = (int)$course_result->fetch_assoc()['cnt'];
} catch (Exception $e) {
    error_log('head-nursing stats: ' . $e->getMessage());
}

// Get nursing students
$nursing_students = [];
if ($ctx['students']) {
    try {
        $r = $ctx['students']->query("SELECT id, first_name, surname, program, level, status FROM students WHERE program LIKE '%Nursing%' ORDER BY first_name LIMIT 50");
        if ($r) $nursing_students = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}

// Get programs
$programs_data = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT program_name, duration, (SELECT COUNT(*) FROM igangaschoolofl_students_db.students WHERE program LIKE CONCAT('%', program_name, '%')) AS enrolled FROM academic_programs WHERE department LIKE '%Nursing%' AND status='Active'");
        if ($r) $programs_data = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
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
    } catch (Exception $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.nurs-topbar{background:linear-gradient(135deg,#0d9488,#0f766e,#115e59);padding:0 32px;height:64px;display:flex;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.15)}.nurs-topbar-content{width:100%;display:flex;align-items:center;justify-content:space-between}.nurs-topbar-left{display:flex;flex-direction:column}.nurs-topbar-title{color:#fff;font-size:18px;font-weight:700;letter-spacing:.3px}.nurs-topbar-subtitle{color:#ccfbf1;font-size:12px;margin-top:-2px}.nurs-topbar-right{display:flex;align-items:center;gap:12px}.nurs-date-badge{background:rgba(255,255,255,.15);color:#fff;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;backdrop-filter:blur(4px)}.nurs-print-btn,.nurs-logout-btn{color:#ccfbf1;font-size:16px;padding:6px 10px;border-radius:8px;transition:all .2s;text-decoration:none}.nurs-print-btn:hover,.nurs-logout-btn:hover{background:rgba(255,255,255,.2);color:#fff}
.nurs-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.nurs-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="nurs-topbar"><div class="nurs-topbar-content"><div class="nurs-topbar-left"><div class="nurs-topbar-title">Head of Nursing</div><div class="nurs-topbar-subtitle">Nursing Department &amp; Clinical Training</div></div><div class="nurs-topbar-right"><span class="nurs-date-badge"><i class="fas fa-calendar-alt me-1"></i><?= date('l, F j, Y') ?></span><a href="#" class="nurs-print-btn" onclick="window.print()"><i class="fas fa-print"></i></a><a href="../logout.php" class="nurs-logout-btn"><i class="fas fa-sign-out-alt"></i></a></div></div></div>
<div class="nurs-content">

<?php switch ($section):
    case 'overview': ?>
        <section id="overview" class="content-section dashboard-section active" data-section="overview">
            <h2>Department Overview</h2>
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_students); ?></h3>
                        <p>Total Nursing Students</p>
                    </div>
                </div>
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_staff); ?></h3>
                        <p>Faculty Members</p>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($nursing_courses); ?></h3>
                        <p>Active Courses</p>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($active_programs); ?></h3>
                        <p>Active Programs</p>
                    </div>
                </div>
            </div>
        </section>
        <?php break;
    case 'students': ?>
        <section id="students" class="content-section dashboard-section active" data-section="students">
            <h2><i class="fas fa-user-graduate me-2"></i>Nursing Students</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Student Name</th><th>Program</th><th>Year</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (empty($nursing_students)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No nursing students found</td></tr>
                        <?php else: ?>
                        <?php foreach ($nursing_students as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?></td>
                            <td><?= htmlspecialchars($s['program'] ?? '-') ?></td>
                            <td>Year <?= htmlspecialchars($s['level'] ?? '?') ?></td>
                            <td><span class="badge bg-<?= $s['status']==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($s['status'] ?? 'Active') ?></span></td>
                            <td><button class="btn btn-sm btn-outline-primary">View</button></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php break;
    case 'courses': ?>
        <section id="courses" class="content-section dashboard-section active" data-section="courses">
            <h2><i class="fas fa-book-open me-2"></i>Course Management</h2>
            <p class="text-muted">Manage nursing courses, curriculum, and syllabi.</p>
        </section>
        <?php break;
    case 'clinical': ?>
        <section id="clinical" class="content-section dashboard-section active" data-section="clinical">
            <h2><i class="fas fa-clinic-medical me-2"></i>Clinical Placements</h2>
            <?php if (file_exists(__DIR__ . '/clinical-placement.php')): ?>
                <?php include __DIR__ . '/clinical-placement.php'; ?>
            <?php else: ?>
                <p class="text-muted">Clinical placement management module.</p>
            <?php endif; ?>
        </section>
        <?php break;
    case 'timetable': ?>
        <section id="timetable" class="content-section dashboard-section active" data-section="timetable">
            <h2><i class="fas fa-calendar-week me-2"></i>Timetable</h2>
            <?php if (file_exists(__DIR__ . '/timetable.php')): ?>
                <?php include __DIR__ . '/timetable.php'; ?>
            <?php else: ?>
            <p class="text-muted">Nursing department timetable will appear here.</p>
            <?php endif; ?>
        </section>
        <?php break;
    case 'staff': ?>
        <section id="staff" class="content-section dashboard-section active" data-section="staff">
            <h2><i class="fas fa-users me-2"></i>Department Staff</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Name</th><th>Position</th><th>Email</th></tr></thead>
                    <tbody>
                        <?php
                        $staff_list = [];
                        if ($conn) {
                            $sr = $conn->query("SELECT full_name, position, email FROM staff WHERE department LIKE '%Nursing%' ORDER BY full_name");
                            if ($sr) $staff_list = $sr->fetch_all(MYSQLI_ASSOC);
                        }
                        if (empty($staff_list)): ?>
                        <tr><td colspan="3" class="text-center text-muted">No nursing staff found</td></tr>
                        <?php else: ?>
                        <?php foreach ($staff_list as $s): ?>
                        <tr><td><?= htmlspecialchars($s['full_name']) ?></td><td><?= htmlspecialchars($s['position']) ?></td><td><?= htmlspecialchars($s['email']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php break;
    default: ?>
        <section id="overview" class="content-section dashboard-section active" data-section="overview">
            <h2>Department Overview</h2>
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_students); ?></h3>
                        <p>Total Nursing Students</p>
                    </div>
                </div>
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_staff); ?></h3>
                        <p>Faculty Members</p>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($nursing_courses); ?></h3>
                        <p>Active Courses</p>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($active_programs); ?></h3>
                        <p>Active Programs</p>
                    </div>
                </div>
            </div>
        </section>
        <?php break;
endswitch; ?>

</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
