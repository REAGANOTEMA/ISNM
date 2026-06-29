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
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-container" style="margin-left:270px">
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
            <?php renderStudentSetViewer($studentsDb, [
                'title' => 'Student Records',
                'icon' => 'fa-user-graduate',
                'show_all' => true,
                'per_page' => 50,
                'show_statement_link' => false
            ]); ?>
        </div>
    </div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
