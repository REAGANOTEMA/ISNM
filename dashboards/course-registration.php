<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
$conn = getStaffConnection();
$pageTitle = 'Course Registration';

$total = 0; $thisSemester = 0; $pending = 0; $completed = 0;
$registrations = [];
if ($conn) {
    $total = $conn->query("SELECT COUNT(*) c FROM course_registrations")->fetch_assoc()['c'] ?? 0;
    $thisSemester = $conn->query("SELECT COUNT(*) c FROM course_registrations WHERE semester LIKE CONCAT('%', QUARTER(CURDATE()), '%') OR created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)")->fetch_assoc()['c'] ?? 0;
    $pending = $conn->query("SELECT COUNT(*) c FROM course_registrations WHERE status='Registered'")->fetch_assoc()['c'] ?? 0;
    $completed = $conn->query("SELECT COUNT(*) c FROM course_registrations WHERE status='Completed'")->fetch_assoc()['c'] ?? 0;
    $r = $conn->query("SELECT cr.*, CONCAT(s.first_name,' ',s.last_name) student_name, cc.course_title course_name FROM course_registrations cr LEFT JOIN students s ON cr.student_id=s.id LEFT JOIN academic_course_catalog cc ON cr.course_code=cc.course_code ORDER BY cr.created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $registrations[] = $row;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-user-plus me-2"></i>Course Registration</h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clipboard-list"></i></div><div class="stat-content"><h3><?= $total ?></h3><p>Total Registrations</p></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-calendar-alt"></i></div><div class="stat-content"><h3><?= $thisSemester ?></h3><p>This Semester</p></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-hourglass-half"></i></div><div class="stat-content"><h3><?= $pending ?></h3><p>Pending</p></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $completed ?></h3><p>Completed</p></div></div></div>
  </div>
  <div class="content-section">
    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Registration Records</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark"><tr><th>Student Name</th><th>Course</th><th>Semester</th><th>Reg Date</th><th>Status</th></tr></thead>
        <tbody><?php if (empty($registrations)): ?><tr><td colspan="5" class="text-muted text-center py-3">No registrations found.</td></tr><?php else: foreach ($registrations as $r): ?><tr><td><strong><?= htmlspecialchars($r['student_name'] ?? 'Unknown') ?></strong></td><td><?= htmlspecialchars($r['course_name'] ?? $r['course_code']) ?></td><td><?= htmlspecialchars($r['semester'] ?? '-') ?></td><td><?= isset($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '-' ?></td><td><span class="badge <?= $r['status']==='Completed'?'bg-success':($r['status']==='Registered'?'bg-primary':'bg-secondary') ?>"><?= htmlspecialchars($r['status']) ?></span></td></tr><?php endforeach; endif; ?></tbody>
      </table>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>