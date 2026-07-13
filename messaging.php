<?php
$pageTitle = 'Messaging';
require_once __DIR__ . '/includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
$conn = getStaffConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);

$totalMessages = 0; $sentMessages = 0; $receivedMessages = 0; $unreadMessages = 0;
$messages = [];
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM staff_inbox WHERE sender_id = $userId OR recipient_id = $userId");
    if ($r) $totalMessages = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM staff_inbox WHERE sender_id = $userId AND is_deleted_sender = 0");
    if ($r) $sentMessages = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM staff_inbox WHERE recipient_id = $userId AND is_deleted_recipient = 0");
    if ($r) $receivedMessages = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM staff_inbox WHERE recipient_id = $userId AND is_read = 0 AND is_deleted_recipient = 0");
    if ($r) $unreadMessages = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT id, subject, sender_name, recipient_name, created_at AS sent_at, is_read FROM staff_inbox WHERE (sender_id = $userId AND is_deleted_sender = 0) OR (recipient_id = $userId AND is_deleted_recipient = 0) ORDER BY created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $messages[] = $row;
}
?>
<?php include_once __DIR__ . '/includes/dashboard_head.php'; ?>
<body>
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>
<main class="main" style="margin-left:270px;padding:32px;">
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-envelope me-2"></i>Messaging</h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-envelope"></i></div><div class="stat-content"><h3><?= $totalMessages ?></h3><p>Total</p></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-paper-plane"></i></div><div class="stat-content"><h3><?= $sentMessages ?></h3><p>Sent</p></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-inbox"></i></div><div class="stat-content"><h3><?= $receivedMessages ?></h3><p>Received</p></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-envelope-open"></i></div><div class="stat-content"><h3><?= $unreadMessages ?></h3><p>Unread</p></div></div></div>
  </div>
  <div class="content-section">
    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Messages</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark"><tr><th>Subject</th><th>Sender</th><th>Recipient</th><th>Date</th><th>Status</th></tr></thead>
        <tbody><?php if (empty($messages)): ?><tr><td colspan="5" class="text-muted text-center py-3">No messages found.</td></tr><?php else: foreach ($messages as $m): ?><tr><td><?= htmlspecialchars($m['subject'] ?? '-') ?></td><td><?= htmlspecialchars($m['sender_name'] ?? '-') ?></td><td><?= htmlspecialchars($m['recipient_name'] ?? '-') ?></td><td><?= htmlspecialchars($m['sent_at'] ?? '-') ?></td><td><span class="badge <?= ($m['is_read'] ?? 0) ? 'bg-success' : 'bg-warning text-dark' ?>"><?= ($m['is_read'] ?? 0) ? 'Read' : 'Unread' ?></span></td></tr><?php endforeach; endif; ?></tbody>
      </table>
    </div>
  </div>
</div>
</main>
<?php include_once __DIR__ . '/includes/dashboard_footer.php'; ?>
</body>
</html>
