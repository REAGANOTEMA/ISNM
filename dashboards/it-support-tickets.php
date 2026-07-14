<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['ict', 'support', 'admin']);
$staff_conn = $ctx['staff'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'IT Support';
$user_id = (int)($user['id'] ?? 0);

$ict_conn = getICTConnection();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function tkt_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { error_log('it-support-tickets getCount: ' . $e->getMessage()); return 0; }
}

function tkt_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Exception $e) { error_log('it-support-tickets getList: ' . $e->getMessage()); return []; }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ict_conn) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'create_ticket') {
        $tn = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $rn = trim($_POST['requester_name']);
        $re = trim($_POST['requester_email'] ?? '');
        $rt = trim($_POST['requester_type']);
        $it = trim($_POST['issue_type']);
        $pr = trim($_POST['priority']);
        $desc = trim($_POST['description']);
        $stmt = $ict_conn->prepare("INSERT INTO it_support_tickets (ticket_number, requester_name, requester_email, requester_type, issue_type, priority, description) VALUES (?,?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('sssssss', $tn, $rn, $re, $rt, $it, $pr, $desc); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        $_SESSION['success'] = "Support ticket $tn created successfully.";
        header('Location: it-support-tickets.php'); exit;
    }

    if ($action === 'update_status') {
        $id = (int)($_POST['ticket_id'] ?? 0);
        $status = trim($_POST['status']);
        if ($id > 0) {
            if ($status === 'resolved') {
                $stmt = $ict_conn->prepare("UPDATE it_support_tickets SET status=?, resolved_at = NOW() WHERE id=?");
                if ($stmt) { $stmt->bind_param('si', $status, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            } else {
                $stmt = $ict_conn->prepare("UPDATE it_support_tickets SET status=? WHERE id=?");
                if ($stmt) { $stmt->bind_param('si', $status, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            }
            $_SESSION['success'] = "Ticket #$id status updated to $status.";
        }
        header('Location: it-support-tickets.php'); exit;
    }

    if ($action === 'assign_ticket') {
        $id = (int)($_POST['ticket_id'] ?? 0);
        $assigned = (int)($_POST['assigned_to'] ?? 0);
        if ($id > 0) {
            $stmt = $ict_conn->prepare("UPDATE it_support_tickets SET assigned_to=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ii', $assigned, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = "Ticket #$id assigned.";
        }
        header('Location: it-support-tickets.php'); exit;
    }

    if ($action === 'add_resolution') {
        $id = (int)($_POST['ticket_id'] ?? 0);
        $notes = trim($_POST['resolution_notes'] ?? '');
        if ($id > 0 && $notes) {
            $fullNote = "\n[$user_name] $notes";
            $stmt = $ict_conn->prepare("UPDATE it_support_tickets SET status = 'resolved', resolution_notes = CONCAT(IFNULL(resolution_notes, ''), ?), resolved_at = NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('si', $fullNote, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = "Ticket #$id resolved with notes.";
        }
        header('Location: it-support-tickets.php'); exit;
    }
}

$status_filter = $_GET['status'] ?? '';
$params = [];
$types = '';
$status_where = '';
if ($status_filter && in_array($status_filter, ['open', 'in_progress', 'resolved', 'closed'])) {
    $status_where = "WHERE t.status = ?";
    $params[] = $status_filter;
    $types = 's';
}

$total_tickets = tkt_q($ict_conn, "SELECT COUNT(*) FROM it_support_tickets");
$open_tickets = tkt_q($ict_conn, "SELECT COUNT(*) FROM it_support_tickets WHERE status = 'open'");
$in_progress_tickets = tkt_q($ict_conn, "SELECT COUNT(*) FROM it_support_tickets WHERE status = 'in_progress'");
$resolved_month = tkt_q($ict_conn, "SELECT COUNT(*) FROM it_support_tickets WHERE status = 'resolved' AND resolved_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");

$sql = "SELECT t.*, s.full_name AS assigned_name FROM it_support_tickets t LEFT JOIN igangaschool_staffs.staff s ON t.assigned_to = s.id $status_where ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.created_at DESC LIMIT 100";
$stmt = $ict_conn->prepare($sql);
if ($stmt) {
    if ($types) $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $r = $stmt->get_result();
    $stmt->close();
} else $r = null;
$tickets = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

$staff_list = [];
if ($staff_conn) {
    $staff_list = tkt_fetch($staff_conn, "SELECT id, full_name FROM staff WHERE status = 'Active' ORDER BY full_name");
}

$active_section = $_GET['section'] ?? 'all';

$pageTitle = 'IT Support Tickets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root {
    --badge-low: #6c757d;
    --badge-medium: #0d6efd;
    --badge-high: #fd7e14;
    --badge-critical: #dc3545;
}
.section-card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(148,163,184,0.16); box-shadow: 0 1px 2px rgba(15,23,42,0.03), 0 4px 12px rgba(15,23,42,0.04); }
.stat-card { background: #fff; border-radius: 16px; padding: 18px; border: 1px solid rgba(148,163,184,0.16); box-shadow: 0 1px 2px rgba(15,23,42,0.03), 0 4px 12px rgba(15,23,42,0.04); display: flex; align-items: center; gap: 14px; transition: all 0.25s ease; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
.si { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.si-blue { background: #eef2ff; color: #2563eb; }
.si-green { background: #ecfdf5; color: #059669; }
.si-orange { background: #fff7ed; color: #d97706; }
.si-red { background: #fef2f2; color: #dc2626; }
.si-purple { background: #f5f3ff; color: #7c3aed; }
.stat-content h3 { font-size: 1.5rem; font-weight: 700; margin: 0; color: #0f172a; line-height: 1.2; }
.stat-content p { font-size: 0.75rem; color: #64748b; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
.top-bar { background: #fff; border-radius: 16px; padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(148,163,184,0.16); }
.form-control, .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 8px 14px; font-size: 0.875rem; }
.form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.btn { border-radius: 10px; font-weight: 500; padding: 8px 18px; }
.table th { font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
.table td { vertical-align: middle; }
.badge-low { background: var(--badge-low); }
.badge-medium { background: var(--badge-medium); }
.badge-high { background: var(--badge-high); }
.badge-critical { background: var(--badge-critical); }
.filter-btn { border-radius: 20px; padding: 4px 16px; font-size: 0.8rem; font-weight: 500; }
@media print { .no-print { display: none !important; } }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
<div class="top-bar">
    <div>
        <strong><i class="fas fa-headset me-2 text-primary"></i>IT Support Tickets</strong>
        <div class="text-muted small">Iganga School of Nursing &amp; Midwifery</div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small d-none d-md-block"><?= date('D, d M Y') ?></span>
        <button class="btn btn-sm btn-outline-success no-print" onclick="window.print()"><i class="fas fa-print me-1"></i></button>
        <a href="#" class="btn btn-sm btn-outline-danger no-print" onclick="event.preventDefault();var f=document.createElement('form');f.method='POST';f.action='../logout.php';document.body.appendChild(f);f.submit();"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
    </div>
</div>
<div class="content-area">
<?php if(!empty($_SESSION['success'])):?><div class="alert alert-success alert-dismissible fade show py-2"><?=htmlspecialchars($_SESSION['success'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']);endif;?>
<?php if(!empty($_SESSION['error'])):?><div class="alert alert-danger alert-dismissible fade show py-2"><?=htmlspecialchars($_SESSION['error'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error']);endif;?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="si si-blue"><i class="fas fa-ticket-alt"></i></div><div class="stat-content"><h3><?= number_format($total_tickets) ?></h3><p>Total Tickets</p></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="si si-red"><i class="fas fa-exclamation-circle"></i></div><div class="stat-content"><h3><?= $open_tickets ?></h3><p>Open</p></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="si si-orange"><i class="fas fa-spinner"></i></div><div class="stat-content"><h3><?= $in_progress_tickets ?></h3><p>In Progress</p></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="si si-green"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $resolved_month ?></h3><p>Resolved (30d)</p></div></div></div>
</div>

<!-- Filter Buttons -->
<div class="d-flex flex-wrap gap-2 mb-4 no-print">
    <a href="it-support-tickets.php" class="btn btn-sm filter-btn <?= !$status_filter ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
    <a href="it-support-tickets.php?status=open" class="btn btn-sm filter-btn <?= $status_filter === 'open' ? 'btn-danger' : 'btn-outline-danger' ?>">Open</a>
    <a href="it-support-tickets.php?status=in_progress" class="btn btn-sm filter-btn <?= $status_filter === 'in_progress' ? 'btn-warning' : 'btn-outline-warning' ?>">In Progress</a>
    <a href="it-support-tickets.php?status=resolved" class="btn btn-sm filter-btn <?= $status_filter === 'resolved' ? 'btn-success' : 'btn-outline-success' ?>">Resolved</a>
    <a href="it-support-tickets.php?status=closed" class="btn btn-sm filter-btn <?= $status_filter === 'closed' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Closed</a>
    <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#newTicketModal"><i class="fas fa-plus me-1"></i>New Ticket</button>
</div>

<!-- Tickets Table -->
<div class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>Support Tickets</h5>
        <span class="badge bg-secondary"><?= count($tickets) ?> tickets</span>
    </div>
    <?php if (empty($tickets)): ?>
    <div class="text-center py-5 text-muted"><i class="fas fa-ticket-alt fa-3x mb-3"></i><p>No tickets found.</p></div>
    <?php else: ?>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchDNOQ" type="text" placeholder="Search..." onkeyup="filterTable('srchDNOQ','tblDNOQ')"></div>
<div class="table-responsive">
        <table id="tblDNOQ" class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Ticket#</th>
                    <th>Requester</th>
                    <th>Issue Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Created</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): 
                    switch($t['priority']) {
                        case 'low': $pclass = 'badge-low'; break;
                        case 'medium': $pclass = 'badge-medium'; break;
                        case 'high': $pclass = 'badge-high'; break;
                        case 'critical': $pclass = 'badge-critical'; break;
                        default: $pclass = 'bg-secondary';
                    }
                    switch($t['status']) {
                        case 'open': $sclass = 'bg-danger'; break;
                        case 'in_progress': $sclass = 'bg-warning text-dark'; break;
                        case 'resolved': $sclass = 'bg-success'; break;
                        case 'closed': $sclass = 'bg-secondary'; break;
                        default: $sclass = 'bg-secondary';
                    }
                ?>
                <tr>
                    <td><code><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                    <td>
                        <strong><?= htmlspecialchars($t['requester_name']) ?></strong>
                        <small class="d-block text-muted"><?= htmlspecialchars($t['requester_type']) ?><?= $t['requester_email'] ? ' | ' . htmlspecialchars($t['requester_email']) : '' ?></small>
                    </td>
                    <td><span class="badge bg-info"><?= htmlspecialchars(ucfirst($t['issue_type'])) ?></span></td>
                    <td><span class="badge <?= $pclass ?>"><?= htmlspecialchars(ucfirst($t['priority'])) ?></span></td>
                    <td><span class="badge <?= $sclass ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $t['status']))) ?></span></td>
                    <td><small><?= htmlspecialchars($t['assigned_name'] ?? 'Unassigned') ?></small></td>
                    <td><small class="text-muted"><?= date('d M Y H:i', strtotime($t['created_at'])) ?></small></td>
                    <td class="no-print">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-cog"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($t['status'] !== 'closed'): ?>
                                <li><h6 class="dropdown-header">Update Status</h6></li>
                                <?php foreach (['open', 'in_progress', 'resolved', 'closed'] as $s): if ($s === $t['status']) continue; ?>
                                <li>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $s ?>">
                                        <button type="submit" class="dropdown-item small">
                                            <i class="fas fa-<?= $s === 'open' ? 'envelope' : ($s === 'in_progress' ? 'spinner' : ($s === 'resolved' ? 'check' : 'archive')) ?> me-2"></i>
                                            <?= ucfirst(str_replace('_', ' ', $s)) ?>
                                        </button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Assign To</h6></li>
                                <?php foreach ($staff_list as $st): ?>
                                <li>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="assign_ticket">
                                        <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                        <input type="hidden" name="assigned_to" value="<?= $st['id'] ?>">
                                        <button type="submit" class="dropdown-item small">
                                            <i class="fas fa-user me-2"></i><?= htmlspecialchars($st['full_name']) ?>
                                        </button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Resolve</h6></li>
                                <li>
                                    <button class="dropdown-item small" onclick="openResolve(<?= $t['id'] ?>)">
                                        <i class="fas fa-check-circle me-2 text-success"></i>Add Resolution Notes
                                    </button>
                                </li>
                                <?php else: ?>
                                <li><span class="dropdown-item-text small text-muted"><i class="fas fa-lock me-2"></i>Closed</span></li>
                                <?php endif; ?>
                                <?php if ($t['resolution_notes']): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Resolution</h6></li>
                                <li><span class="dropdown-item-text small text-wrap" style="max-width:250px"><?= nl2br(htmlspecialchars($t['resolution_notes'])) ?></span></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</div><!-- content-area -->
</div><!-- page-content -->

<!-- New Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create_ticket">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>New Support Ticket</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Requester Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="requester_name" required></div>
                    <div class="col-md-6"><label class="form-label">Requester Email</label><input type="email" class="form-control" name="requester_email"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="requester_type" required>
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                            <option value="faculty">Faculty</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Issue Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="issue_type" required>
                            <option value="hardware">Hardware</option>
                            <option value="software">Software</option>
                            <option value="network">Network</option>
                            <option value="account">Account</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                        <select class="form-select" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label fw-semibold">Description <span class="text-danger">*</span></label><textarea class="form-control" name="description" rows="4" required></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- Resolve Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="add_resolution">
            <input type="hidden" name="ticket_id" id="resolveTicketId">
            <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Resolve Ticket</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="form-label fw-semibold">Resolution Notes</label><textarea class="form-control" name="resolution_notes" rows="4" placeholder="Describe what was done to resolve this issue..."></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Mark Resolved</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openResolve(id) {
    document.getElementById('resolveTicketId').value = id;
    new bootstrap.Modal(document.getElementById('resolveModal')).show();
}
function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
        }
        tr[i].style.display = found ? "" : "none";
    }
}

</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION["csrf_token"] ?? "")?>';document.querySelectorAll('form[method="POST"],form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});</script>
</body>
</html>
