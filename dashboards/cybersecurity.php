<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'ict', 'it']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$incidents = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM security_incidents ORDER BY created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $incidents[] = $row;
}
$access_logs = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM security_access_logs ORDER BY accessed_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $access_logs[] = $row;
}
$pageTitle = 'Cybersecurity';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-shield-halved me-2"></i>Cybersecurity</h2><p>Monitor security incidents, access logs, and system integrity</p></div>
<div class="row g-4">
<div class="col-lg-6"><div class="card"><div class="card-header">Security Incidents</div><div class="card-body" style="max-height:420px;overflow-y:auto">
<?php if (empty($incidents)): ?><div class="empty-state"><i class="fas fa-shield"></i><p>No security incidents recorded.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>Type</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach ($incidents as $inc): ?>
<tr><td class="small"><?= htmlspecialchars($inc['id']??'') ?></td><td><span class="badge bg-secondary"><?= htmlspecialchars($inc['incident_type']??'') ?></span></td><td><span class="status-pill <?= ($inc['status']??'') === 'Resolved' ? 'success' : (($inc['status']??'') === 'In Progress' ? 'warning' : 'danger') ?>"><?= htmlspecialchars(str_replace('_', ' ', $inc['status']??'Open')) ?></span></td><td class="small text-nowrap"><?= htmlspecialchars($inc['created_at']??'') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">Access Logs</div><div class="card-body" style="max-height:420px;overflow-y:auto">
<?php if (empty($access_logs)): ?><div class="empty-state"><i class="fas fa-list"></i><p>No access logs recorded.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>User ID</th><th>Access Type</th><th>Location</th><th>Status</th><th>Date/Time</th></tr></thead><tbody>
<?php foreach ($access_logs as $log): ?>
<tr><td class="small"><?= htmlspecialchars($log['user_id']??'') ?></td><td><span class="badge bg-info"><?= htmlspecialchars($log['access_type']??'') ?></span></td><td class="small"><?= htmlspecialchars($log['location']??'') ?></td><td class="small"><?= htmlspecialchars($log['ip_address']??'') ?></td><td><span class="badge bg-<?= ($log['status']??'') === 'allowed' ? 'success' : 'danger' ?>"><?= htmlspecialchars($log['status']??'') ?></span></td><td class="small text-nowrap"><?= htmlspecialchars($log['accessed_at']??'') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div></div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
