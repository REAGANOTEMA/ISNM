<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
$staffConn = getStaffConnection();
$studentsConn = getStudentsConnection();
$conn = $studentsConn ?: $staffConn;
$pageTitle = 'Clinical Placement';

$total = 0; $active = 0; $completed = 0; $upcoming = 0;
$placements = [];
if ($conn) {
    $qr = $conn->query("SELECT COUNT(*) c FROM clinical_placements"); if ($qr) $total = (int)$qr->fetch_assoc()['c'];
    $qr = $conn->query("SELECT COUNT(*) c FROM clinical_placements WHERE status IN('In Progress','Scheduled')"); if ($qr) $active = (int)$qr->fetch_assoc()['c'];
    $qr = $conn->query("SELECT COUNT(*) c FROM clinical_placements WHERE status='Completed'"); if ($qr) $completed = (int)$qr->fetch_assoc()['c'];
    $qr = $conn->query("SELECT COUNT(*) c FROM clinical_placements WHERE status='Scheduled' AND start_date > CURDATE()"); if ($qr) $upcoming = (int)$qr->fetch_assoc()['c'];
    $r = $conn->query("SELECT cp.*, CONCAT(s.first_name,' ',s.last_name) student_name, s.program FROM clinical_placements cp LEFT JOIN students s ON cp.student_id=s.id ORDER BY cp.start_date DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $placements[] = $row;
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
    <h4 class="fw-bold mb-0"><i class="fas fa-hospital-user me-2"></i>Clinical Placement</h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clipboard-list"></i></div><div class="stat-content"><h3><?= $total ?></h3><p>Total Placements</p></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-play-circle"></i></div><div class="stat-content"><h3><?= $active ?></h3><p>Active</p></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $completed ?></h3><p>Completed</p></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-calendar-plus"></i></div><div class="stat-content"><h3><?= $upcoming ?></h3><p>Upcoming</p></div></div></div>
  </div>
  <div class="content-section">
    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Placement Records</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark"><tr><th>Student Name</th><th>Program</th><th>Placement Site</th><th>Start Date</th><th>End Date</th><th>Status</th></tr></thead>
        <tbody><?php if (empty($placements)): ?><tr><td colspan="6" class="text-muted text-center py-3">No placements found.</td></tr><?php else: foreach ($placements as $p): ?><tr><td><strong><?= htmlspecialchars($p['student_name'] ?? 'Unknown') ?></strong></td><td><?= htmlspecialchars($p['program'] ?? '-') ?></td><td><?= htmlspecialchars($p['placement_site'] ?? '-') ?></td><td><?= date('d M Y', strtotime($p['start_date'])) ?></td><td><?= $p['end_date'] ? date('d M Y', strtotime($p['end_date'])) : '-' ?></td><td><span class="badge <?= $p['status']==='Completed'?'bg-success':($p['status']==='In Progress'?'bg-primary':'bg-warning text-dark') ?>"><?= htmlspecialchars($p['status']) ?></span></td></tr><?php endforeach; endif; ?></tbody>
      </table>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>