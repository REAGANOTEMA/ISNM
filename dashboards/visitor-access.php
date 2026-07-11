<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['security', 'director', 'manager']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$pageTitle = 'Visitor & Access Control';

$visitors = []; $accessLogs = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM visitor_logs ORDER BY check_in_time DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $visitors[] = $row;
    $r2 = $conn->query("SELECT * FROM access_control_logs ORDER BY access_time DESC LIMIT 100");
    if ($r2) while ($row = $r2->fetch_assoc()) $accessLogs[] = $row;
}
$loggedIn = count(array_filter($visitors, fn($v) => !($v['check_out_time'] ?? '')));
$today = count(array_filter($visitors, fn($v) => substr($v['check_in_time'] ?? '', 0, 10) === date('Y-m-d')));
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
        <h1><i class="fas fa-shield-alt"></i> Visitor & Access Control</h1><button onclick="window.print()" class="btn btn-sm btn-outline-secondary float-end"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total Visitors</h6><h3><?= count($visitors) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Currently Inside</h6><h3><?= $loggedIn ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Today's Visitors</h6><h3><?= $today ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Visitor Logs</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Name</th><th>Purpose</th><th>Check In</th><th>Check Out</th><th>Host</th></tr></thead>
                            <tbody>
                                <?php foreach ($visitors as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v['visitor_name'] ?? $v['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($v['purpose'] ?? $v['reason'] ?? '-') ?></td>
                                    <td><?= ($v['check_in_time'] ?? '') ? date('d M Y g:i A', strtotime($v['check_in_time'])) : '-' ?></td>
                                    <td><?= $v['check_out_time'] ? date('d M Y g:i A', strtotime($v['check_out_time'])) : '<span class="badge bg-success">Inside</span>' ?></td>
                                    <td><?= htmlspecialchars($v['person_visiting'] ?? $v['host'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($visitors)): ?><tr><td colspan="5" class="text-center">No visitor logs</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Access Control Logs</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>User</th><th>Action</th><th>Location</th><th>Time</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($accessLogs as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['person_name'] ?? $a['user'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['access_type'] ?? $a['event'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['access_point'] ?? $a['area'] ?? '-') ?></td>
                                    <td><?= $a['access_time'] ? date('d M Y g:i A', strtotime($a['access_time'])) : ($a['created_at'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($a['status'] ?? 'granted') === 'granted' ? 'success' : 'danger' ?>"><?= $a['status'] ?? 'granted' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($accessLogs)): ?><tr><td colspan="5" class="text-center">No access logs</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
