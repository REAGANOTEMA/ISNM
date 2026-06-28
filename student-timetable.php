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
$timetable = [];
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

if ($studentsDb) {
    $stmt = $studentsDb->prepare("SELECT * FROM students WHERE student_number=? OR id=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("si", $studentNumber, $userId);
        $stmt->execute();
        $sr = $stmt->get_result();
        $studentInfo = $sr ? $sr->fetch_assoc() : [];
        $stmt->close();
    }

    $sidInt = (int)($studentInfo['id'] ?? $userId);
    $program = $studentInfo['program']??'';
    $year = (int)($studentInfo['year_of_study']??1);

    // Try student_timetables first, then timetable
    $tt = $studentsDb->query("SELECT * FROM student_timetables WHERE student_id=$sidInt ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), time_slot");
    if ($tt && $tt->num_rows > 0) {
        $timetable = $tt->fetch_all(MYSQLI_ASSOC);
    } else {
        $stmt2 = $studentsDb->prepare("SELECT * FROM timetable WHERE program=? AND year_of_study=?");
        if ($stmt2) {
            $stmt2->bind_param("si", $program, $year);
            $stmt2->execute();
            $tt2 = $stmt2->get_result();
            if ($tt2) {
                $timetable = $tt2->fetch_all(MYSQLI_ASSOC);
            }
            $stmt2->close();
        }
    }
}

$fullName = $studentInfo ? htmlspecialchars(($studentInfo['surname']??'') . ' ' . ($studentInfo['firstname']??'')) : 'Student';
$program = $studentInfo ? htmlspecialchars($studentInfo['program']??'N/A') : 'N/A';
$yearOfStudy = $studentInfo ? (int)($studentInfo['year_of_study']??1) : 1;

$grouped = [];
foreach ($timetable as $e) {
    $d = $e['day_of_week'] ?? $e['day'] ?? '';
    $grouped[$d][] = $e;
}

$pageTitle = 'Student Timetable';
require_once __DIR__ . '/includes/dashboard_head.php';
include_once __DIR__ . '/includes/sidebar.php';
?>
<style>
:root{--primary:#2c5f8a;--accent:#1a9e6e}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif}
.tt-card{border:none;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08);margin-bottom:24px}
.tt-card .card-header{background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:20px 28px;border:none;color:#fff}
.tt-card .card-body{padding:0}
.day-tab{padding:8px 18px;border-radius:8px;cursor:pointer;transition:all .2s;font-size:.9rem;text-decoration:none;color:#64748b}
.day-tab:hover{background:rgba(44,95,138,.08);color:#2c5f8a}
.day-tab.active{background:#2c5f8a;color:#fff;font-weight:600}
.entry-row{border-bottom:1px solid #e9ecef;padding:12px 28px;display:flex;align-items:center;gap:16px}
.entry-row:last-child{border-bottom:none}
.entry-time{min-width:100px;font-weight:600;color:#2c5f8a;font-size:.9rem}
.entry-subject{flex:1;font-weight:500}
.entry-detail{font-size:.85rem;color:#64748b}
.empty-day{padding:40px;text-align:center}
</style>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-calendar-days me-2" style="color:#2c5f8a"></i>My Timetable</h2>
            <p class="text-muted mb-0">Weekly class schedule for <?= $program ?> (Year <?= $yearOfStudy ?>)</p>
        </div>
        <div class="text-end">
            <div class="fw-semibold"><?= $fullName ?></div>
            <small class="text-muted">Y<?= $yearOfStudy ?> · <?= $program ?></small>
        </div>
    </div>

    <?php if (empty($timetable)): ?>
    <div class="tt-card">
        <div class="card-body text-center py-5">
            <i class="fas fa-calendar-days fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Timetable not yet published</h5>
            <p class="text-muted mb-0">Your class schedule will appear here once published by the registrar.</p>
        </div>
    </div>
    <?php else: ?>

    <div class="tt-card">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-table me-2"></i>Weekly Schedule</h5>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 p-3 border-bottom">
                <?php foreach ($days as $d): 
                    $hasEntry = isset($grouped[$d]);
                    $today = date('l') === $d;
                ?>
                <span class="day-tab <?= $today ? 'active' : '' ?> <?= !$hasEntry ? 'opacity-50' : '' ?>">
                    <?= $d ?> <?= $hasEntry ? '<small class="ms-1">('.count($grouped[$d]).')</small>' : '' ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php 
            $today = date('l');
            $displayDay = isset($grouped[$today]) ? $today : array_key_first($grouped);
            foreach ($days as $d): 
                if (!isset($grouped[$d])) continue;
                $isToday = $d === $today;
            ?>
            <div class="day-schedule" data-day="<?= $d ?>" <?= $isToday ? '' : 'style="display:none"' ?>>
                <div class="px-3 pt-3 pb-1 fw-semibold" style="color:#2c5f8a;font-size:.85rem"><?= $d ?></div>
                <?php foreach ($grouped[$d] as $e): 
                    $time = $e['time_slot'] ?? ($e['start_time']??'') . ' - ' . ($e['end_time']??'');
                    $subject = $e['subject'] ?? $e['course_name'] ?? $e['course_code'] ?? '—';
                    $lecturer = $e['lecturer'] ?? '';
                    $room = $e['room'] ?? $e['classroom'] ?? $e['venue'] ?? '';
                ?>
                <div class="entry-row">
                    <div class="entry-time"><?= htmlspecialchars($time) ?></div>
                    <div class="entry-subject">
                        <?= htmlspecialchars($subject) ?>
                        <?php if ($lecturer): ?>
                        <div class="entry-detail"><i class="fas fa-chalkboard-teacher me-1"></i><?= htmlspecialchars($lecturer) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($room): ?>
                    <div class="entry-detail"><i class="fas fa-location-dot me-1"></i><?= htmlspecialchars($room) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.day-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.day-schedule').forEach(s => s.style.display = 'none');
        const day = this.textContent.trim().split(' ')[0];
        const el = document.querySelector('.day-schedule[data-day="' + day + '"]');
        if (el) el.style.display = 'block';
    });
});
</script>
<?php require_once __DIR__ . '/includes/dashboard_footer.php'; ?>