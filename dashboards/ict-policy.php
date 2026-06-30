<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'ict', 'it']);
$conn_staff = $ctx['staff'];
$user = $ctx['user'];

$infrastructure = [];
if ($conn_staff) {
    $r = $conn_staff->query("SELECT * FROM it_infrastructure ORDER BY asset_type, asset_name");
    if ($r) while ($row = $r->fetch_assoc()) $infrastructure[] = $row;
}
$compliance = [];
if ($conn_staff) {
    $r = $conn_staff->query("SELECT * FROM compliance_records ORDER BY created_at DESC LIMIT 30");
    if ($r) while ($row = $r->fetch_assoc()) $compliance[] = $row;
}
try {
    $conn_ict = getDatabaseConnection('ict');
    $software = [];
    if ($conn_ict) {
        $r = $conn_ict->query("SELECT * FROM software_inventory ORDER BY software_name");
        if ($r) while ($row = $r->fetch_assoc()) $software[] = $row;
    }
} catch (Exception $e) { $software = []; }
$pageTitle = 'ICT Policy';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-file-lines me-2"></i>ICT Policy & Infrastructure</h2><p>Manage ICT policies, IT infrastructure, software inventory, and compliance</p></div>
<div class="row g-4">
<div class="col-md-6"><div class="card"><div class="card-header">IT Infrastructure (<?= count($infrastructure) ?>)</div><div class="card-body" style="max-height:350px;overflow-y:auto">
<?php if (empty($infrastructure)): ?><div class="empty-state"><i class="fas fa-server"></i><p>No infrastructure assets recorded.</p></div>
<?php else: ?>
<?php $cat = ''; foreach ($infrastructure as $a): if ($cat !== $a['asset_type']): $cat = $a['asset_type']; ?><h6 class="mt-2 mb-1 text-primary small fw-bold"><?= htmlspecialchars($cat) ?></h6><?php endif; ?>
<div class="d-flex justify-content-between small border-bottom pb-1 mb-1">
<span><?= htmlspecialchars($a['asset_name']??$a['asset_code']??'') ?></span><span class="badge bg-<?= ($a['status']??'') === 'Operational' ? 'success' : (($a['status']??'') === 'Under Maintenance' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($a['status']??'') ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></div></div>
<div class="col-md-6"><div class="card"><div class="card-header">Software Inventory (<?= count($software) ?>)</div><div class="card-body" style="max-height:350px;overflow-y:auto">
<?php if (empty($software)): ?><div class="empty-state"><i class="fas fa-code"></i><p>No software inventory data.</p></div>
<?php else: ?><?php foreach ($software as $s): ?>
<div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2 small">
<div><strong><?= htmlspecialchars($s['software_name']) ?></strong><br><span class="text-muted">v<?= htmlspecialchars($s['version']??'') ?> &middot; <?= htmlspecialchars($s['category']??'') ?></span></div>
<span class="badge bg-<?= ($s['license_type']??'educational') === 'commercial' ? 'warning' : 'success' ?>"><?= htmlspecialchars($s['license_type']??'') ?></span>
</div>
<?php endforeach; ?><?php endif; ?>
</div></div></div>
<div class="col-12"><div class="card"><div class="card-header">Compliance Records</div><div class="card-body">
<?php if (empty($compliance)): ?><div class="empty-state"><i class="fas fa-clipboard"></i><p>No compliance records.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Type</th><th>Document</th><th>Issue Date</th><th>Expiry Date</th><th>Status</th></tr></thead><tbody>
<?php foreach ($compliance as $c): ?>
<tr><td class="small"><?= htmlspecialchars($c['compliance_type']??'') ?></td><td class="small"><?= htmlspecialchars($c['document_name']??'') ?></td><td class="small"><?= htmlspecialchars($c['issue_date']??'') ?></td><td class="small"><?= htmlspecialchars($c['expiry_date']??'N/A') ?></td><td><span class="status-pill <?= ($c['status']??'') === 'Valid' ? 'success' : (($c['status']??'') === 'Expired' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($c['status']??'Pending') ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div></div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
