<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director','secretary','ict','it','principal']);
$pageTitle = 'Communications';

$messages = []; $totalSent = $unread = $drafts = 0;
$conn = getStaffConnection();
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) as c FROM communications"); if ($r) $totalSent = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) as c FROM communications WHERE is_read = 0"); if ($r) $unread = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) as c FROM communications WHERE status = 'draft'"); if ($r) $drafts = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT * FROM communications ORDER BY sent_at DESC LIMIT 50");
    if ($r) { while ($row = $r->fetch_assoc()) $messages[] = $row; }
}
if (empty($messages)) {
    $totalSent = $unread = $drafts = 0;
    $wdb = getWebsiteConnection();
    if ($wdb) {
        $r = $wdb->query("SELECT COUNT(*) as c FROM portal_messages"); if ($r) $totalSent = (int)$r->fetch_assoc()['c'];
        $r = $wdb->query("SELECT COUNT(*) as c FROM portal_messages WHERE is_read = 0"); if ($r) $unread = (int)$r->fetch_assoc()['c'];
        $r = $wdb->query("SELECT COUNT(*) as c FROM portal_messages WHERE status = 'draft'"); if ($r) $drafts = (int)$r->fetch_assoc()['c'];
        $r = $wdb->query("SELECT * FROM portal_messages ORDER BY sent_at DESC LIMIT 50");
        if ($r) { while ($row = $r->fetch_assoc()) $messages[] = $row; }
    }
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
        <h4 class="fw-bold mb-0"><i class="fas fa-comments me-2"></i>Communications</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="fas fa-paper-plane"></i></div>
            <div class="stat-content"><h3><?= $totalSent ?></h3><p>Total Sent</p></div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-inbox"></i></div>
            <div class="stat-content"><h3><?= $totalSent ?></h3><p>Received</p></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-envelope"></i></div>
            <div class="stat-content"><h3><?= $unread ?></h3><p>Unread</p></div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon"><i class="fas fa-file"></i></div>
            <div class="stat-content"><h3><?= $drafts ?></h3><p>Drafts</p></div>
        </div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Message Records</h5>
        <?php if (!empty($messages)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Subject</th><th>Sender</th><th>Recipient</th><th>Sent At</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['subject'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['sender'] ?? $m['sender_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['recipient'] ?? $m['recipient_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['sent_at'] ?? $m['created_at'] ?? '-') ?></td>
                        <td><span class="badge bg-<?= ($m['is_read'] ?? '0') == '1' ? 'success' : 'warning' ?>"><?= ($m['is_read'] ?? '0') == '1' ? 'Read' : 'Unread' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
            <i class="fas fa-database fa-2x mb-2"></i>
            <p class="mb-0">No records found.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
</main>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>