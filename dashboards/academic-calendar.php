<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'academics', 'registrar', 'principal']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$calendars = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM academic_calendar ORDER BY semester_start_date DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $calendars[] = $row;
}
$pageTitle = 'Academic Calendar';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?></head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</h2><p>View semesters, exam periods, and key academic deadlines</p></div>
<div class="card"><div class="card-header">Semester Calendar</div><div class="card-body">
<?php if (empty($calendars)): ?><div class="empty-state"><i class="fas fa-calendar"></i><p>No academic calendar entries found.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Academic Year</th><th>Semester</th><th>Start Date</th><th>End Date</th><th>Exam Start</th><th>Exam End</th><th>Status</th></tr></thead><tbody>
<?php foreach ($calendars as $c): ?>
<tr><td><strong><?= htmlspecialchars($c['academic_year']) ?></strong></td><td><?= htmlspecialchars($c['semester']) ?></td><td><?= htmlspecialchars($c['semester_start_date']) ?></td><td><?= htmlspecialchars($c['semester_end_date']) ?></td><td><?= htmlspecialchars($c['exam_start_date']) ?></td><td><?= htmlspecialchars($c['exam_end_date']) ?></td><td><span class="status-pill <?= ($c['status']??'Upcoming') === 'Current' ? 'success' : (($c['status']??'') === 'Completed' ? 'info' : 'warning') ?>"><?= htmlspecialchars($c['status']??'Upcoming') ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
