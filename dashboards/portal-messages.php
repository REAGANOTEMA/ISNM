<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'secretary', 'ict', 'it']);
$conn = $ctx['staff'];
$websiteConn = $ctx['website'];
$user = $ctx['user'];

$pageTitle = 'Portal Messages';

$messages = [];
if ($websiteConn) {
    $r = $websiteConn->query("SELECT * FROM portal_messages ORDER BY created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $messages[] = $row;
}

$notifications = [];
foreach ([$websiteConn, $conn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $notifications[] = $row; break; }
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
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-envelope"></i> Portal Messages & Notifications</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Portal Messages</h6><h3><?= count($messages) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Notifications</h6><h3><?= count($notifications) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Portal Messages</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>From</th><th>To</th><th>Subject</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['sender_name'] ?? $m['from'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($m['recipient_name'] ?? $m['to'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($m['subject'] ?? substr($m['message'] ?? '', 0, 40)) ?></td>
                                    <td><?= $m['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($m['is_read'] ?? 0) ? 'success' : 'warning' ?>"><?= ($m['is_read'] ?? 0) ? 'Read' : 'Unread' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($messages)): ?><tr><td colspan="5" class="text-center">No portal messages</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Notifications</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Title</th><th>Type</th><th>Message</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($notifications as $n): ?>
                                <tr>
                                    <td><?= htmlspecialchars($n['title'] ?? substr($n['message'] ?? '', 0, 30)) ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($n['type'] ?? $n['notification_type'] ?? 'info') ?></span></td>
                                    <td><?= htmlspecialchars(substr($n['message'] ?? '', 0, 50)) ?></td>
                                    <td><?= $n['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($n['is_read'] ?? 0) ? 'success' : 'warning' ?>"><?= ($n['is_read'] ?? 0) ? 'Read' : 'Unread' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($notifications)): ?><tr><td colspan="5" class="text-center">No notifications</td></tr><?php endif; ?>
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
