<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
$conn = getStaffConnection();
$pageTitle = 'Exams & Results';

$totalExams = 0; $published = 0; $pendingGrading = 0; $current = 0;
$exams = [];
if ($conn) {
    $totalExams = $conn->query("SELECT COUNT(DISTINCT er.exam_number) c FROM examination_records er")->fetch_assoc()['c'] ?? 0;
    $published = $conn->query("SELECT COUNT(DISTINCT er.exam_number) c FROM examination_records er WHERE er.grade_status='Published'")->fetch_assoc()['c'] ?? 0;
    $pendingGrading = $conn->query("SELECT COUNT(DISTINCT er.exam_number) c FROM examination_records er WHERE er.grade_status IN('Draft','Submitted','Under Review')")->fetch_assoc()['c'] ?? 0;
    $current = $conn->query("SELECT COUNT(DISTINCT er.exam_number) c FROM examination_records er WHERE MONTH(er.created_at)=MONTH(CURDATE()) AND YEAR(er.created_at)=YEAR(CURDATE())")->fetch_assoc()['c'] ?? 0;
    $r = $conn->query("SELECT er.exam_number, er.exam_type, er.course_code, cc.course_title course_name, er.grade_status, MIN(er.created_at) exam_date, COUNT(er.student_id) total_students FROM examination_records er LEFT JOIN academic_course_catalog cc ON er.course_code=cc.course_code GROUP BY er.exam_number, er.exam_type, er.course_code, cc.course_title, er.grade_status ORDER BY exam_date DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $exams[] = $row;
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
    <h4 class="fw-bold mb-0"><i class="fas fa-file-alt me-2"></i>Exams & Results</h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-calendar-check"></i></div><div class="stat-content"><h3><?= $totalExams ?></h3><p>Total Exams</p></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $published ?></h3><p>Published</p></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-edit"></i></div><div class="stat-content"><h3><?= $pendingGrading ?></h3><p>Pending Grading</p></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-hourglass-half"></i></div><div class="stat-content"><h3><?= $current ?></h3><p>This Month</p></div></div></div>
  </div>
  <div class="content-section">
    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Exam Records</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark"><tr><th>Exam</th><th>Course</th><th>Date</th><th>Students</th><th>Status</th></tr></thead>
        <tbody><?php if (empty($exams)): ?><tr><td colspan="5" class="text-muted text-center py-3">No exam records found.</td></tr><?php else: foreach ($exams as $e): ?><tr><td><strong><?= htmlspecialchars($e['exam_type'] ?? '-') ?></strong><br><small class="text-muted"><?= htmlspecialchars($e['exam_number']) ?></small></td><td><?= htmlspecialchars($e['course_name'] ?? $e['course_code']) ?></td><td><?= $e['exam_date'] ? date('d M Y', strtotime($e['exam_date'])) : '-' ?></td><td><?= $e['total_students'] ?></td><td><span class="badge <?= $e['grade_status']==='Published'?'bg-success':($e['grade_status']==='Approved'?'bg-primary':($e['grade_status']==='Rejected'?'bg-danger':'bg-warning text-dark')) ?>"><?= htmlspecialchars($e['grade_status'] ?? 'Draft') ?></span></td></tr><?php endforeach; endif; ?></tbody>
      </table>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>