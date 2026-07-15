<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'academics', 'registrar', 'principal']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);

$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';

if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_calendar` (id INT AUTO_INCREMENT PRIMARY KEY, academic_year VARCHAR(20) NOT NULL, semester VARCHAR(100) DEFAULT NULL, start_date DATE NULL, end_date DATE NULL, exam_start_date DATE NULL, exam_end_date DATE NULL, is_current TINYINT(1) DEFAULT 0, status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_year_sem (academic_year, semester)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$calendars = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM academic_calendar ORDER BY start_date DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $calendars[] = $row;
}

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_calendar') {
        $ay = $_POST['academic_year'] ?? '';
        $sem = $_POST['semester'] ?? '';
        $sd = $_POST['start_date'] ?? null;
        $ed = $_POST['end_date'] ?? null;
        $esd = $_POST['exam_start_date'] ?? null;
        $eed = $_POST['exam_end_date'] ?? null;
        if ($ay) {
            $stmt = $conn->prepare("INSERT INTO academic_calendar (academic_year, semester, start_date, end_date, exam_start_date, exam_end_date, status) VALUES (?,?,?,?,?,?,'Active')");
            $stmt->bind_param("ssssss", $ay, $sem, $sd, $ed, $esd, $eed);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = "Calendar entry added for $ay.";
        }
        header('Location: academic-calendar.php'); exit;
    }
    if ($action === 'delete_calendar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM academic_calendar WHERE id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Calendar entry deleted.';
        }
        header('Location: academic-calendar.php'); exit;
    }
}

$pageTitle = 'Academic Calendar';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</h2><p>View semesters, exam periods, and key academic deadlines</p></div>

<?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Add Calendar Entry</h5>
    </div>
    <div class="card-body">
        <form method="post" class="row g-2">
            <div class="col-md-2"><input name="academic_year" class="form-control" placeholder="Academic Year (e.g. 2025-2026)" required></div>
            <div class="col-md-2"><input name="semester" class="form-control" placeholder="Semester"></div>
            <div class="col-md-2"><label class="form-label small">Start Date</label><input name="start_date" type="date" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small">End Date</label><input name="end_date" type="date" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small">Exam Start</label><input name="exam_start_date" type="date" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small">Exam End</label><input name="exam_end_date" type="date" class="form-control"></div>
            <div class="col-12 mt-2"><button type="submit" name="action" value="add_calendar" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Entry</button></div>
        </form>
    </div>
</div>

<div class="card"><div class="card-header">Semester Calendar</div><div class="card-body">
<?php if (empty($calendars)): ?><div class="empty-state"><i class="fas fa-calendar"></i><p>No academic calendar entries found.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Academic Year</th><th>Semester</th><th>Start Date</th><th>End Date</th><th>Exam Start</th><th>Exam End</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach ($calendars as $c): ?>
<tr>
<td><strong><?= htmlspecialchars($c['academic_year']) ?></strong></td>
<td><?= htmlspecialchars($c['semester'] ?? '-') ?></td>
<td><?= htmlspecialchars($c['start_date'] ?? '-') ?></td>
<td><?= htmlspecialchars($c['end_date'] ?? '-') ?></td>
<td><?= htmlspecialchars($c['exam_start_date'] ?? '-') ?></td>
<td><?= htmlspecialchars($c['exam_end_date'] ?? '-') ?></td>
<td><span class="status-pill <?= ($c['status']??'Upcoming') === 'Active' ? 'success' : (($c['status']??'') === 'Completed' ? 'info' : 'warning') ?>"><?= htmlspecialchars($c['status']??'Upcoming') ?></span></td>
<td>
    <form method="post" style="display:inline" onsubmit="return confirm('Delete this entry?')">
        <input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button type="submit" name="action" value="delete_calendar" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
    </form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
