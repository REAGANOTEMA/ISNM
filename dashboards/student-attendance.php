<?php
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();
$pageTitle = 'Student Attendance';
$present = 0; $absent = 0; $late = 0; $total = 0; $records = [];
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM student_attendance WHERE attendance_date=CURDATE() AND status='Present'");
    if ($r) $present = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM student_attendance WHERE attendance_date=CURDATE() AND status='Absent'");
    if ($r) $absent = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM student_attendance WHERE attendance_date=CURDATE() AND status='Late'");
    if ($r) $late = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM student_attendance");
    if ($r) $total = (int)$r->fetch_assoc()['c'];
    $q = $conn->query("SELECT CONCAT(s.first_name,' ',s.surname) student_name, a.attendance_date date, a.time_in, a.time_out, a.status FROM student_attendance a LEFT JOIN students s ON a.student_id=s.id WHERE a.attendance_date=CURDATE() ORDER BY a.time_in DESC");
    if ($q) $records = $q->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main" style="margin-left:270px;padding:32px;">
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4">
<h4 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2"></i>Student Attendance</h4>
<span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>
<div class="row g-3 mb-4">
<?php $c=[['Present Today',$present,'success','user-check'],['Absent',$absent,'danger','user-times'],['Late',$late,'warning','clock'],['Total Records',$total,'info','database']]; foreach($c as $s): ?>
<div class="col-md-3">
<div class="stat-card <?= $s[2] ?>">
<div class="stat-icon"><i class="fas fa-<?= $s[3] ?>"></i></div>
<div class="stat-content"><h3><?= number_format($s[1]) ?></h3><p><?= $s[0] ?></p></div>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="content-section">
<h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Today's Attendance</h5>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<thead class="table-light"><tr><th>Student</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th></tr></thead>
<tbody>
<?php if(empty($records)): ?>
<tr><td colspan="5" class="text-center text-muted py-3">No attendance records for today.</td></tr>
<?php else: foreach($records as $r):
$st=$r['status']??'';
$bc=$st==='Present'?'bg-success':($st==='Absent'?'bg-danger':($st==='Late'?'bg-warning text-dark':($st==='Half Day'?'bg-info':'bg-secondary')));
?>
<tr><td><?= htmlspecialchars($r['student_name']??'-') ?></td><td><?= htmlspecialchars($r['date']??'-') ?></td><td><?= htmlspecialchars($r['time_in']??'-') ?></td><td><?= htmlspecialchars($r['time_out']??'-') ?></td><td><span class="badge <?= $bc ?>"><?= htmlspecialchars($st) ?></span></td></tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</main>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
