<?php
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();
$pageTitle = 'Leave Management';
$pending = 0; $approvedMonth = 0; $onLeave = 0; $balances = 0; $records = [];
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM leave_requests WHERE status='Pending'");
    if ($r) $pending = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM leave_requests WHERE status='Approved' AND MONTH(start_date)=MONTH(CURDATE()) AND YEAR(start_date)=YEAR(CURDATE())");
    if ($r) $approvedMonth = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM leave_requests WHERE status='Approved' AND CURDATE() BETWEEN start_date AND end_date");
    if ($r) $onLeave = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COALESCE(SUM(balance_days),0) c FROM leave_balance");
    if ($r) $balances = (int)$r->fetch_assoc()['c'];
    $q = $conn->query("SELECT s.full_name staff_name, lt.type_name leave_type, lr.start_date, lr.end_date, DATEDIFF(lr.end_date,lr.start_date)+1 days, lr.status FROM leave_requests lr JOIN staff s ON lr.staff_id=s.id LEFT JOIN leave_types lt ON lr.leave_type_id=lt.id ORDER BY lr.created_at DESC LIMIT 50");
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
<h4 class="fw-bold mb-0"><i class="fas fa-plane me-2"></i>Leave Management</h4>
<span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>
<div class="row g-3 mb-4">
<?php $c=[['Pending Requests',$pending,'warning','hourglass-half'],['Approved This Month',$approvedMonth,'success','check-circle'],['On Leave Today',$onLeave,'info','calendar-day'],['Remaining Balances',$balances,'primary','wallet']]; foreach($c as $s): ?>
<div class="col-md-3">
<div class="stat-card <?= $s[2] ?>">
<div class="stat-icon"><i class="fas fa-<?= $s[3] ?>"></i></div>
<div class="stat-content"><h3><?= number_format($s[1]) ?></h3><p><?= $s[0] ?></p></div>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="content-section">
<h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Recent Leave Requests</h5>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<thead class="table-light"><tr><th>Staff</th><th>Leave Type</th><th>Start Date</th><th>End Date</th><th>Days</th><th>Status</th></tr></thead>
<tbody>
<?php if(empty($records)): ?>
<tr><td colspan="6" class="text-center text-muted py-3">No leave requests found.</td></tr>
<?php else: foreach($records as $r):
$st=$r['status']??'';
$bc=$st==='Approved'?'bg-success':($st==='Pending'?'bg-warning text-dark':($st==='Rejected'?'bg-danger':'bg-secondary'));
?>
<tr><td><?= htmlspecialchars($r['staff_name']??'-') ?></td><td><?= htmlspecialchars($r['leave_type']??'-') ?></td><td><?= htmlspecialchars($r['start_date']??'-') ?></td><td><?= htmlspecialchars($r['end_date']??'-') ?></td><td><?= htmlspecialchars($r['days']??'-') ?></td><td><span class="badge <?= $bc ?>"><?= htmlspecialchars($st) ?></span></td></tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</main>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
