<?php
$pageTitle = 'Notifications';
require_once __DIR__ . '/includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
$conn = getStaffConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);

require_once __DIR__ . '/includes/notification_helper.php';

$totalNotifications = 0; $unreadNotifications = 0; $readNotifications = 0; $recentNotifications = 0;
$notifications = [];
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM notifications");
    if ($r) $totalNotifications = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM notification_reads WHERE user_id = $userId");
    if ($r) $readNotifications = (int)$r->fetch_assoc()['c'];
    $unreadNotifications = max(0, $totalNotifications - $readNotifications);
    $r = $conn->query("SELECT COUNT(*) c FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($r) $recentNotifications = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT n.id, n.title, n.message, n.type, n.created_at, (SELECT COUNT(*) FROM notification_reads nr WHERE nr.notification_id = n.id AND nr.user_id = $userId) AS is_read FROM notifications n ORDER BY n.created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $notifications[] = $row;
}
?>
<?php include_once __DIR__ . '/includes/dashboard_head.php'; ?>
<body>
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>
<main class="main" style="margin-left:270px;padding:32px;">
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-bell me-2"></i>Notifications</h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-bell"></i></div><div class="stat-content"><h3><?= $totalNotifications ?></h3><p>Total</p></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-bell-slash"></i></div><div class="stat-content"><h3><?= $unreadNotifications ?></h3><p>Unread</p></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $readNotifications ?></h3><p>Read</p></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $recentNotifications ?></h3><p>Last 7 Days</p></div></div></div>
  </div>
  <div class="content-section">
    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Notification History</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark"><tr><th>Title</th><th>Message</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
        <tbody><?php if (empty($notifications)): ?><tr><td colspan="5" class="text-muted text-center py-3">No notifications found.</td></tr><?php else: foreach ($notifications as $n): ?><tr><td><?= htmlspecialchars($n['title'] ?? '-') ?></td><td><?= htmlspecialchars(mb_substr($n['message'] ?? '', 0, 80)) ?><?= (isset($n['message']) && mb_strlen($n['message']) > 80) ? '...' : '' ?></td><td><span class="badge bg-<?= $n['type'] === 'alert' ? 'danger' : ($n['type'] === 'warning' ? 'warning text-dark' : ($n['type'] === 'info' ? 'info' : 'primary')) ?>"><?= htmlspecialchars($n['type'] ?? 'info') ?></span></td><td><?= htmlspecialchars($n['created_at'] ?? '-') ?></td><td><span class="badge <?= ($n['is_read'] ?? 0) ? 'bg-success' : 'bg-warning text-dark' ?>"><?= ($n['is_read'] ?? 0) ? 'Read' : 'Unread' ?></span></td></tr><?php endforeach; endif; ?></tbody>
      </table>
    </div>
  </div>
</div>
</main>
<?php include_once __DIR__ . '/includes/dashboard_footer.php'; ?>
</body>
</html>
