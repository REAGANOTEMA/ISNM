<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director','secretary','ict','it','principal']);
$conn = $ctx['staff'];
$user = $ctx['user'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search = trim($_GET['search'] ?? '');
$filterPriority = $_GET['priority'] ?? '';

$total = 0; $activeCount = 0; $expiredCount = 0;
$alerts = [];

if ($conn) {
    $total = (int)(($r=$conn->query("SELECT COUNT(*) c FROM institutional_alerts"))&&$r?$r->fetch_assoc()['c']:0);
    $activeCount = (int)(($r=$conn->query("SELECT COUNT(*) c FROM institutional_alerts WHERE (expires_at IS NULL OR expires_at >= NOW()) AND is_resolved = 0"))&&$r?$r->fetch_assoc()['c']:0);
    $expiredCount = (int)(($r=$conn->query("SELECT COUNT(*) c FROM institutional_alerts WHERE expires_at IS NOT NULL AND expires_at < NOW()"))&&$r?$r->fetch_assoc()['c']:0);

    $where = ["1=1"];
    $params = [];
    $types = '';
    if ($search) { $where[] = "(alert_title LIKE ? OR alert_message LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }
    if ($filterPriority) { $where[] = "priority=?"; $params[] = $filterPriority; $types .= 's'; }
    $ws = implode(' AND ', $where);
    $stmt = $conn->prepare("SELECT * FROM institutional_alerts WHERE $ws ORDER BY created_at DESC LIMIT 100");
    if ($stmt) { if ($types) $stmt->bind_param($types, ...$params); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    if ($r) while ($row = $r->fetch_assoc()) $alerts[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $action = $_POST['action'] ?? '';
    if ($conn && $action === 'add_alert') {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['alert_message'] ?? '');
        $priority = trim($_POST['priority'] ?? 'Medium');
        $category = trim($_POST['category'] ?? 'other');
        $expires = trim($_POST['expires_at'] ?? '');
        if ($title && $message) {
            $stmt = $conn->prepare("INSERT INTO institutional_alerts (alert_title, alert_message, priority, category, expires_at, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt) { $expVal = $expires !== '' ? $expires : null; $stmt->bind_param('sssss', $title, $message, $priority, $category, $expVal); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = 'Alert created and broadcast.';
        }
        header('Location: institutional-alerts.php');
        exit;
    }
    if ($conn && $action === 'toggle_resolved') {
        $id = (int)($_POST['id'] ?? 0);
        $current = (int)($_POST['current'] ?? 0);
        if ($current) {
            $stmt = $conn->prepare("UPDATE institutional_alerts SET is_resolved=NULL, resolved_at=NULL, resolved_by=NULL WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        } else {
            $resolvedBy = (int)($user['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE institutional_alerts SET is_resolved=1, resolved_at=NOW(), resolved_by=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ii', $resolvedBy, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        }
        header('Location: institutional-alerts.php');
        exit;
    }
    if ($conn && $action === 'delete_alert') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt1 = $conn->prepare("DELETE FROM alert_recipients WHERE alert_id=?");
        if ($stmt1) { $stmt1->bind_param('i', $id); if (!$stmt1->execute()) { error_log('$stmt1 execute failed: ' . ($stmt1->error ?? 'unknown')); }; $stmt1->close(); }
        $stmt2 = $conn->prepare("DELETE FROM institutional_alerts WHERE id=?");
        if ($stmt2) { $stmt2->bind_param('i', $id); if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); }; $stmt2->close(); }
        $_SESSION['success'] = 'Alert deleted.';
        header('Location: institutional-alerts.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.priority-Low { background:#e3f2fd;color:#1565c0; }
.priority-Medium { background:#fff8e1;color:#f9a825; }
.priority-High { background:#fbe9e7;color:#d84315; }
.priority-Critical { background:#ffebee;color:#c62828; }
.status-active { background:#e8f5e9;color:#2e7d32; }
.status-expired { background:#f5f5f5;color:#616161; }
.status-resolved { background:#ede7f6;color:#4527a0; }
.alert-row td { vertical-align:middle; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-broadcast-tower me-2"></i>Institutional Alerts</h4>
            <div>
                <span class="text-muted small me-3"><?= date('l, d M Y') ?></span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAlertModal"><i class="fas fa-plus me-1"></i>New Alert</button>
            </div>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); endif; ?>

        <div class="row g-3 mt-2">
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-primary mb-2"><i class="fas fa-broadcast-tower"></i></div><h3 class="fw-bold mb-0"><?= $total ?></h3><small class="text-muted">Total Alerts</small></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-success mb-2"><i class="fas fa-check-circle"></i></div><h3 class="fw-bold mb-0"><?= $activeCount ?></h3><small class="text-muted">Active</small></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-secondary mb-2"><i class="fas fa-clock"></i></div><h3 class="fw-bold mb-0"><?= $expiredCount ?></h3><small class="text-muted">Expired</small></div></div></div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Alerts</h5>
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" style="width:200px" placeholder="Search alerts..." value="<?= htmlspecialchars($search) ?>">
                    <select name="priority" class="form-select form-select-sm" style="width:auto">
                        <option value="">All Priorities</option>
                        <?php foreach (['Low', 'Medium', 'High', 'Critical'] as $p): ?>
                        <option value="<?= $p ?>" <?= $filterPriority === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
                    <a href="institutional-alerts.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
                </form>
            </div>
            <div class="card-body p-0">
                <?php if (empty($alerts)): ?>
                <div class="text-center py-5"><i class="fas fa-broadcast-tower fa-3x mb-3 text-muted" style="opacity:.3;"></i><p class="text-muted">No alerts yet. Create the first broadcast alert.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Expires</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alerts as $a):
                                $expiresAt = $a['expires_at'] ?? null;
                                $isExpired = $expiresAt && strtotime($expiresAt) < time();
                                if ($a['is_resolved']) { $status = 'resolved'; $statusLabel = 'Resolved'; }
                                elseif ($isExpired) { $status = 'expired'; $statusLabel = 'Expired'; }
                                else { $status = 'active'; $statusLabel = 'Active'; }
                            ?>
                            <tr class="alert-row">
                                <td class="fw-semibold"><?= htmlspecialchars($a['alert_title'] ?? $a['title'] ?? '') ?></td>
                                <td><div style="max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($a['alert_message'] ?? '') ?></div></td>
                                <td><span class="badge priority-<?= $a['priority'] ?>"><?= htmlspecialchars($a['priority']) ?></span></td>
                                <td><span class="badge status-<?= $status ?>"><?= $statusLabel ?></span></td>
                                <td class="small"><?= date('d M Y H:i', strtotime($a['created_at'])) ?></td>
                                <td class="small"><?= !empty($expiresAt) ? date('d M Y', strtotime($expiresAt)) : '&mdash;' ?></td>â€”' ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('<?= $a['is_resolved'] ? 'Reactivate' : 'Deactivate' ?> this alert?')">
                                        <input type="hidden" name="action" value="toggle_resolved">
                                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                        <input type="hidden" name="current" value="<?= $a['is_resolved'] ?>">
                                        <button class="btn btn-sm btn-outline-<?= $a['is_resolved'] ? 'success' : 'secondary' ?> py-0 px-1" title="<?= $a['is_resolved'] ? 'Activate' : 'Deactivate' ?>"><i class="fas fa-<?= $a['is_resolved'] ? 'eye' : 'eye-slash' ?>"></i></button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this alert permanently?')">
                                        <input type="hidden" name="action" value="delete_alert">
                                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Alert Modal -->
<div class="modal fade" id="addAlertModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" class="modal-content"><input type="hidden" name="action" value="add_alert">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-broadcast-tower me-2"></i>Broadcast New Alert</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
    <div class="col-12"><label class="form-label fw-semibold">Alert Title *</label><input type="text" name="title" class="form-control" required maxlength="255" placeholder="e.g. System Maintenance"></div>
    <div class="col-12"><label class="form-label fw-semibold">Alert Message *</label><textarea name="alert_message" class="form-control" rows="4" required placeholder="Describe the alert details..."></textarea></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Priority</label><select name="priority" class="form-select"><?php foreach (['Low', 'Medium', 'High', 'Critical'] as $p): ?><option value="<?= $p ?>" <?= $p === 'Medium' ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Category</label><select name="category" class="form-select"><?php foreach (['attendance', 'academic', 'finance', 'admissions', 'system', 'staff', 'compliance', 'approval', 'other'] as $c): ?><option value="<?= $c ?>" <?= $c === 'other' ? 'selected' : '' ?>><?= ucfirst($c) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Expires At</label><input type="date" name="expires_at" class="form-control"></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Broadcast Alert</button></div>
</form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION["csrf_token"] ?? "")?>';document.querySelectorAll('form[method="POST"],form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});</script>
</body>
</html>
