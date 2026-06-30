<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['hr','manager','director','principal','head']);
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();
$pageTitle = 'Training & CPD';
$total = 0; $enrolled = 0; $completed = 0; $upcoming = 0; $records = [];
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM trainings");
    if ($r) $total = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM employee_training WHERE status='Enrolled'");
    if ($r) $enrolled = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM employee_training WHERE status='Completed'");
    if ($r) $completed = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM trainings WHERE start_date > CURDATE()");
    if ($r) $upcoming = (int)$r->fetch_assoc()['c'];
    $q = $conn->query("SELECT t.name training_name, s.full_name staff_name, t.start_date, t.end_date, et.status FROM employee_training et JOIN trainings t ON et.training_id=t.id LEFT JOIN staff s ON et.staff_id=s.id ORDER BY t.start_date DESC LIMIT 50");
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
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<main class="main" style="margin-left:270px;padding:32px;">
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4">
<h4 class="fw-bold mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Training & CPD</h4>
<span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>
<div class="row g-3 mb-4">
<?php $c=[['Total Trainings',$total,'primary','book-open'],['Enrolled',$enrolled,'success','user-plus'],['Completed',$completed,'info','check-circle'],['Upcoming',$upcoming,'warning','calendar-alt']]; foreach($c as $s): ?>
<div class="col-md-3">
<div class="stat-card <?= $s[2] ?>">
<div class="stat-icon"><i class="fas fa-<?= $s[3] ?>"></i></div>
<div class="stat-content"><h3><?= number_format($s[1]) ?></h3><p><?= $s[0] ?></p></div>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="content-section">
<h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Training Records</h5>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<thead class="table-light"><tr><th>Training</th><th>Staff</th><th>Start Date</th><th>End Date</th><th>Status</th></tr></thead>
<tbody>
<?php if(empty($records)): ?>
<tr><td colspan="5" class="text-center text-muted py-3">No training records found.</td></tr>
<?php else: foreach($records as $r):
$bc=$r['status']==='Completed'?'bg-success':($r['status']==='Enrolled'?'bg-primary':'bg-secondary');
?>
<tr><td><?= htmlspecialchars($r['training_name']??'-') ?></td><td><?= htmlspecialchars($r['staff_name']??'-') ?></td><td><?= htmlspecialchars($r['start_date']??'-') ?></td><td><?= htmlspecialchars($r['end_date']??'-') ?></td><td><span class="badge <?= $bc ?>"><?= htmlspecialchars($r['status']??'-') ?></span></td></tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</main>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
