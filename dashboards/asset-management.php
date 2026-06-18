<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
$conn = getStaffConnection();
$pageTitle = 'Asset Management';

$total = 0; $assigned = 0; $available = 0; $maintenance = 0;
$assets = [];
if ($conn) {
    $total = $conn->query("SELECT COUNT(*) c FROM assets")->fetch_assoc()['c'] ?? 0;
    $assigned = $conn->query("SELECT COUNT(*) c FROM assets WHERE status='in_use'")->fetch_assoc()['c'] ?? 0;
    $available = $conn->query("SELECT COUNT(*) c FROM assets WHERE status='new'")->fetch_assoc()['c'] ?? 0;
    $maintenance = $conn->query("SELECT COUNT(*) c FROM assets WHERE status='under_maintenance'")->fetch_assoc()['c'] ?? 0;
    $r = $conn->query("SELECT a.*, ac.category_name, sr.full_name assigned_name FROM assets a LEFT JOIN asset_categories ac ON a.asset_category_id=ac.id LEFT JOIN staff_records sr ON a.assigned_to=sr.id ORDER BY a.created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $assets[] = $row;
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
    <h4 class="fw-bold mb-0"><i class="fas fa-boxes me-2"></i>Asset Management</h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-box"></i></div><div class="stat-content"><h3><?= $total ?></h3><p>Total Assets</p></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-user-check"></i></div><div class="stat-content"><h3><?= $assigned ?></h3><p>Assigned</p></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $available ?></h3><p>Available</p></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-tools"></i></div><div class="stat-content"><h3><?= $maintenance ?></h3><p>Under Maintenance</p></div></div></div>
  </div>
  <div class="content-section">
    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Asset Register</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark"><tr><th>Asset Name</th><th>Category</th><th>Serial No</th><th>Condition</th><th>Assigned To</th><th>Status</th></tr></thead>
        <tbody><?php if (empty($assets)): ?><tr><td colspan="6" class="text-muted text-center py-3">No assets found.</td></tr><?php else: foreach ($assets as $a): ?><tr><td><?= htmlspecialchars($a['asset_name']) ?></td><td><?= htmlspecialchars($a['category_name'] ?? '-') ?></td><td><code><?= htmlspecialchars($a['asset_code'] ?? '-') ?></code></td><td><span class="badge <?= $a['status']==='new'?'bg-success':($a['status']==='in_use'?'bg-primary':($a['status']==='under_maintenance'?'bg-warning text-dark':'bg-secondary')) ?>"><?= htmlspecialchars($a['status'] ?? '-') ?></span></td><td><?= htmlspecialchars($a['assigned_name'] ?? 'Unassigned') ?></td><td><?= htmlspecialchars($a['status']) ?></td></tr><?php endforeach; endif; ?></tbody>
      </table>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>