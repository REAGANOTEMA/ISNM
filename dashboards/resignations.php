<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr', 'manager', 'director']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$pageTitle = 'Resignations & Exit Management';

$resignations = [];
$r = $conn->query("SELECT * FROM staff_resignations ORDER BY created_at DESC LIMIT 100");
if ($r) while ($row = $r->fetch_assoc()) $resignations[] = $row;

$exits = count($resignations);
$pending = count(array_filter($resignations, fn($r) => ($r['status'] ?? '') === 'pending'));
$approved = count(array_filter($resignations, fn($r) => ($r['status'] ?? '') === 'approved'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-door-open"></i> Resignations & Exit Management</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total Resignations</h6><h3><?= $exits ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Pending</h6><h3><?= $pending ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Approved</h6><h3><?= $approved ?></h3></div></div></div>
    </div>
    <div class="card">
        <div class="card-header"><h5>Staff Resignations</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Staff Name</th><th>Position</th><th>Reason</th><th>Notice Date</th><th>Last Working Day</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($resignations as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['staff_name'] ?? $r['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['position'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(substr($r['reason'] ?? $r['exit_reason'] ?? '', 0, 50)) ?></td>
                            <td><?= $r['notice_date'] ?? $r['created_at'] ?? '-' ?></td>
                            <td><?= $r['last_working_day'] ?? $r['exit_date'] ?? '-' ?></td>
                            <td><span class="badge bg-<?= ($r['status'] ?? 'pending') === 'approved' ? 'success' : (($r['status'] ?? '') === 'rejected' ? 'danger' : 'warning') ?>"><?= $r['status'] ?? 'pending' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($resignations)): ?><tr><td colspan="6" class="text-center">No resignation records</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
