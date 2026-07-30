<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: student-login.php'); exit;
}
$isStaff = ($_SESSION['type'] ?? '') === 'staff';
$isStudent = ($_SESSION['type'] ?? '') === 'student';
if (!$isStaff && !$isStudent) {
    header('Location: student-login.php'); exit;
}
$auth_service = new AuthenticationService();
$user = $auth_service->getCurrentUser();
$staffDb = getStaffConnection();
$studentsDb = getStudentsConnection();
$userId = (int)($user['id'] ?? 0);
$studentNumber = $user['student_number'] ?? ($_SESSION['student_number'] ?? '');

$studentInfo = [];
$registrations = [];
$availableCourses = [];

if ($studentsDb) {
    $stmt = $studentsDb->prepare("SELECT * FROM students WHERE student_number=? OR id=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("si", $studentNumber, $userId);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $sr = $stmt->get_result();
        $studentInfo = $sr ? $sr->fetch_assoc() : [];
        $stmt->close();
    }
    $sidInt = (int)($studentInfo['id'] ?? $userId);
    $program = $studentInfo['program']??'';
    $year = (int)($studentInfo['year_of_study']??1);

    $rg = $studentsDb->query("SELECT cr.*, ac.course_title, ac.credits FROM student_course_registrations cr LEFT JOIN academic_course_catalog ac ON cr.course_id = ac.id WHERE cr.student_id = " . (int)$sidInt . " ORDER BY cr.registration_date DESC");
    if (!$rg || $rg->num_rows === 0) {
        $rg = $studentsDb->query("SELECT * FROM course_registrations WHERE student_id=" . (int)$sidInt . " ORDER BY id DESC");
    }
    if ($rg) {
        $registrations = isnm_fetch_all($rg);
    }

    $stmt2 = $studentsDb->prepare("SELECT * FROM academic_course_catalog WHERE program_code=? AND year_of_study=? AND status='Active'");
    if ($stmt2) {
        $stmt2->bind_param("si", $program, $year);
        if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
        $avail = $stmt2->get_result();
        if ($avail) {
            $availableCourses = isnm_fetch_all($avail);
        }
        $stmt2->close();
    }
}

$fullName = $studentInfo ? htmlspecialchars(($studentInfo['surname']??'') . ' ' . ($studentInfo['firstname']??'')) : 'Student';
$program = $studentInfo ? htmlspecialchars($studentInfo['program']??'N/A') : 'N/A';
$yearOfStudy = $studentInfo ? (int)($studentInfo['year_of_study']??1) : 1;

$pageTitle = 'Course Registration';
require_once __DIR__ . '/includes/dashboard_head.php';
include_once __DIR__ . '/includes/sidebar.php';
?>
<style>
:root{--primary:#2c5f8a;--accent:#1a9e6e}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif}
.reg-card{border:none;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08);margin-bottom:24px}
.reg-card .card-header{background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:20px 28px;border:none;color:#fff}
.reg-card .card-body{padding:24px 28px}
.status-badge{font-size:.75rem;padding:4px 12px;border-radius:20px;font-weight:600}
.status-Registered,.status-active{background:#dcfce7;color:#166534}
.status-Completed{background:#e0f2fe;color:#075985}
.status-Dropped{background:#fef2f2;color:#991b1b}
@media(max-width:768px){.main{margin-left:0!important;padding:16px!important}.d-flex.justify-content-between{flex-direction:column;gap:8px}}
</style>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-pen-to-square me-2" style="color:#2c5f8a"></i>Course Registration</h2>
            <p class="text-muted mb-0">Register for courses and view current registrations</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold"><?= $fullName ?></div>
            <small class="text-muted">Y<?= $yearOfStudy ?> Â· <?= $program ?></small>
        </div>
    </div>

    <?php if (empty($registrations) && empty($availableCourses)): ?>
    <div class="reg-card">
        <div class="card-body text-center py-5">
            <i class="fas fa-pen-to-square fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Registration not yet open</h5>
            <p class="text-muted mb-0">Course registration will be available once the registrar opens enrollment.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($registrations)): ?>
    <div class="reg-card">
        <div class="card-header">
            <h5 class="mb-0 fw-semibold"><i class="fas fa-list-check me-2"></i>My Registrations</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Course</th><th>Code</th><th>Semester</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php $i=1; foreach ($registrations as $r): 
                            $status = $r['status'] ?? 'Registered';
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($r['course_title'] ?? $r['course_name'] ?? $r['course_code'] ?? 'â€”') ?></td>
                            <td><?= htmlspecialchars($r['course_code'] ?? 'â€”') ?></td>
                            <td><?= htmlspecialchars($r['semester'] ?? $r['academic_year'] ?? 'â€”') ?></td>
                            <td><span class="status-badge status-<?= $status ?>"><?= htmlspecialchars($status) ?></span></td>
                            <td><?= htmlspecialchars($r['registration_date'] ?? 'â€”') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($availableCourses)): ?>
    <div class="reg-card">
        <div class="card-header">
            <h5 class="mb-0 fw-semibold"><i class="fas fa-book-open me-2"></i>Available Courses</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Code</th><th>Course Title</th><th>Credits</th><th>Year</th><th>Semester</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($availableCourses as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['course_code']??'') ?></td>
                            <td><?= htmlspecialchars($c['course_title']??'') ?></td>
                            <td><?= (int)($c['credits']??0) ?></td>
                            <td><?= (int)($c['year_of_study']??$yearOfStudy) ?></td>
                            <td><?= htmlspecialchars($c['semester']??'â€”') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/dashboard_footer.php'; ?>