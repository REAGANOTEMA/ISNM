<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr', 'manager', 'director']);
$conn = $ctx['staff'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => 'Unknown action'];
    $action = $_POST['action'];
    if ($action === 'submit_resignation' && $conn) {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $last_day = trim($_POST['last_working_date'] ?? '');
        if ($staff_id && $reason) {
            $stmt = $conn->prepare("INSERT INTO staff_resignations (staff_id, resignation_date, last_working_date, reason, status) VALUES (?, CURDATE(), ?, ?, 'pending')");
            $stmt->bind_param('iss', $staff_id, $last_day, $reason);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'Staff and reason required'; }
    } elseif ($action === 'process_resignation' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($id && $status) {
            $stmt = $conn->prepare("UPDATE staff_resignations SET status=?, notes=CONCAT(IFNULL(notes,''),?) WHERE id=?");
            $notesWithSep = "\nProcessed: ".$notes;
            $stmt->bind_param('ssi', $status, $notesWithSep, $id);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'ID and status required'; }
    } elseif ($action === 'delete_resignation' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM staff_resignations WHERE id=?");
            $stmt->bind_param('i', $id);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'ID required'; }
    }
    echo json_encode($response); exit;
}

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
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Staff Resignations</h5>
            <button class="btn btn-primary btn-sm" onclick="showSubmitResignation()"><i class="fas fa-plus me-1"></i>Submit Resignation</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Staff Name</th><th>Reason</th><th>Notice Date</th><th>Last Working Day</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($resignations as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['staff_name'] ?? $r['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(substr($r['reason'] ?? $r['exit_reason'] ?? '', 0, 50)) ?></td>
                            <td><?= $r['resignation_date'] ?? $r['notice_date'] ?? $r['created_at'] ?? '-' ?></td>
                            <td><?= $r['last_working_date'] ?? $r['exit_date'] ?? '-' ?></td>
                            <td><span class="badge bg-<?= ($r['status'] ?? 'pending') === 'approved' ? 'success' : (($r['status'] ?? '') === 'rejected' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($r['status'] ?? 'pending') ?></span></td>
                            <td>
                                <?php if (($r['status'] ?? '') === 'pending'): ?>
                                <button class="btn btn-sm btn-outline-success" onclick="processResignation(<?= $r['id'] ?>,'approved')"><i class="fas fa-check"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="processResignation(<?= $r['id'] ?>,'rejected')"><i class="fas fa-times"></i></button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteResignation(<?= $r['id'] ?>)"><i class="fas fa-trash"></i></button>
                            </td>
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

<!-- Submit Resignation Modal -->
<div class="modal fade" id="submitResigModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-door-open me-2"></i>Submit Resignation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="submitResigForm" onsubmit="event.preventDefault(); submitResignation()">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Staff *</label><select name="staff_id" class="form-select" required><option value="">Select Staff</option><?php if ($conn) { $so = $conn->query("SELECT id, full_name FROM staff WHERE status='Active' ORDER BY full_name"); if ($so) while ($row = $so->fetch_assoc()) echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['full_name']).'</option>'; } ?></select></div>
                <div class="mb-3"><label class="form-label">Last Working Date</label><input type="date" name="last_working_date" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Reason *</label><textarea name="reason" class="form-control" rows="4" required></textarea></div>
                <input type="hidden" name="action" value="submit_resignation">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Submit</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function showSubmitResignation() { new bootstrap.Modal(document.getElementById('submitResigModal')).show(); }
function processResignation(id, status) {
    if (!confirm((status === 'approved' ? 'Approve' : 'Reject') + ' this resignation?')) return;
    var fd = new FormData(); fd.append('action', 'process_resignation'); fd.append('id', id); fd.append('status', status); fd.append('notes', 'Processed by admin');
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
}
function deleteResignation(id) {
    if (!confirm('Delete this resignation record?')) return;
    var fd = new FormData(); fd.append('action', 'delete_resignation'); fd.append('id', id);
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
}
function submitResignation() {
    var fd = new FormData(document.getElementById('submitResigForm'));
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
}
</script>
</body>
</html>
