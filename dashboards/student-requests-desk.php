<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['secretary', 'registrar', 'director', 'principal', 'deputy']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'Student Requests & Messages';

// Ensure tables exist
if ($studentsConn) {
    $studentsConn->query("CREATE TABLE IF NOT EXISTS student_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        student_name VARCHAR(255) DEFAULT NULL,
        request_type VARCHAR(100) NOT NULL,
        description TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $studentsConn->query("CREATE TABLE IF NOT EXISTS student_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_name VARCHAR(255) DEFAULT NULL,
        `from` VARCHAR(255) DEFAULT NULL,
        subject VARCHAR(255) DEFAULT NULL,
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$requests = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM student_requests ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $requests[] = $row; break; }
}

$messages = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM student_messages ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $messages[] = $row; break; }
}

$totalRequests = count($requests);
$pendingReqs = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'pending'));
$totalMessages = count($messages);
$unreadMsgs = count(array_filter($messages, fn($m) => ($m['is_read'] ?? 0) == 0));
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
        <h1><i class="fas fa-inbox"></i> Student Requests & Messages</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Requests</h6><h3><?= $totalRequests ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Pending</h6><h3><?= $pendingReqs ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Messages</h6><h3><?= $totalMessages ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Unread</h6><h3><?= $unreadMsgs ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Student Requests</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Student</th><th>Request Type</th><th>Description</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['student_name'] ?? $req['student_id'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($req['request_type'] ?? $req['type'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(substr($req['description'] ?? $req['details'] ?? '', 0, 60)) ?></td>
                                    <td><span class="badge bg-<?= ($req['status'] ?? 'pending') === 'resolved' ? 'success' : 'warning' ?>"><?= $req['status'] ?? 'pending' ?></span></td>
                                    <td><?= $req['created_at'] ?? '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($requests)): ?><tr><td colspan="5" class="text-center">No student requests</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Student Messages</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>From</th><th>Subject</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['sender_name'] ?? $m['from'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($m['subject'] ?? substr($m['message'] ?? '', 0, 40)) ?></td>
                                    <td><?= $m['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($m['is_read'] ?? 0) ? 'success' : 'warning' ?>"><?= ($m['is_read'] ?? 0) ? 'Read' : 'Unread' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($messages)): ?><tr><td colspan="4" class="text-center">No messages</td></tr><?php endif; ?>
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
