<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'ict', 'it']);
$conn = $ctx['staff'];
$user = $ctx['user'];

// ── AJAX CRUD Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'] ?? $_GET['ajax'] ?? '';
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
    }
    if (!$conn) { echo json_encode(['success' => false, 'message' => 'Database unavailable']); exit; }

    // Ensure tables exist
    @$conn->query("CREATE TABLE IF NOT EXISTS security_incidents (id INT AUTO_INCREMENT PRIMARY KEY, incident_type VARCHAR(100), description TEXT, severity VARCHAR(20) DEFAULT 'medium', status VARCHAR(30) DEFAULT 'Open', reported_by VARCHAR(200), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    @$conn->query("CREATE TABLE IF NOT EXISTS security_access_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id VARCHAR(100), access_type VARCHAR(50), location VARCHAR(200), ip_address VARCHAR(50), status VARCHAR(20) DEFAULT 'allowed', accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    switch ($action) {
        case 'add_incident':
            $type = trim($_POST['incident_type'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $sev = trim($_POST['severity'] ?? 'medium');
            $reporter = trim($_POST['reported_by'] ?? '');
            if (!$type) { echo json_encode(['success' => false, 'message' => 'Incident type required']); exit; }
            $stmt = $conn->prepare("INSERT INTO security_incidents (incident_type, description, severity, status, reported_by, created_at) VALUES (?,?,?,'Open',?,NOW())");
            $stmt->bind_param('ssss', $type, $desc, $sev, $reporter); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Incident added' : 'Failed']); exit;

        case 'update_incident':
            $id = (int)($_POST['id'] ?? 0); $status = trim($_POST['status'] ?? 'Open');
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("UPDATE security_incidents SET status=? WHERE id=?");
            $stmt->bind_param('si', $status, $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Failed']); exit;

        case 'delete_incident':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM security_incidents WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']); exit;

        case 'add_access_log':
            $uid = trim($_POST['user_id'] ?? '');
            $atype = trim($_POST['access_type'] ?? '');
            $loc = trim($_POST['location'] ?? '');
            $ip = trim($_POST['ip_address'] ?? '');
            $status = trim($_POST['status'] ?? 'allowed');
            if (!$uid) { echo json_encode(['success' => false, 'message' => 'User ID required']); exit; }
            $stmt = $conn->prepare("INSERT INTO security_access_logs (user_id, access_type, location, ip_address, status, accessed_at) VALUES (?,?,?,?,?,NOW())");
            $stmt->bind_param('sssss', $uid, $atype, $loc, $ip, $status); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Log added' : 'Failed']); exit;

        case 'delete_access_log':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM security_access_logs WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']); exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
}

$incidents = []; $access_logs = [];
if ($conn) {
    @$r = $conn->query("SELECT * FROM security_incidents ORDER BY created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $incidents[] = $row;
    @$r = $conn->query("SELECT * FROM security_access_logs ORDER BY accessed_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $access_logs[] = $row;
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$pageTitle = 'Cybersecurity';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-shield-halved me-2"></i>Cybersecurity <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button></h2><p>Monitor security incidents, access logs, and system integrity</p></div>
<div class="row g-4">
<div class="col-lg-6"><div class="card"><div class="card-header d-flex justify-content-between align-items-center">Security Incidents <button class="btn btn-sm btn-primary" onclick="openIncidentModal()"><i class="fas fa-plus"></i> Add</button></div><div class="card-body" style="max-height:420px;overflow-y:auto">
<?php if (empty($incidents)): ?><div class="empty-state"><i class="fas fa-shield"></i><p>No security incidents recorded.</p></div>
<?php else: ?>
<div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchIncidents" type="text" placeholder="Search..." onkeyup="filterTable('srchIncidents','tblIncidents')"></div>
<div class="table-responsive"><table id="tblIncidents" class="table table-sm"><thead><tr><th>Type</th><th>Severity</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
<?php foreach ($incidents as $inc): ?>
<tr>
<td><span class="badge bg-secondary"><?= htmlspecialchars($inc['incident_type']??'') ?></span></td>
<td><span class="badge bg-<?= ($inc['severity']??'')==='critical'?'danger':(($inc['severity']??'')==='high'?'warning':'info') ?>"><?= htmlspecialchars($inc['severity']??'medium') ?></span></td>
<td>
<select class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="updateIncident(<?=$inc['id']?>,this.value)">
<option value="Open" <?= ($inc['status']??'')==='Open'?'selected':'' ?>>Open</option>
<option value="In Progress" <?= ($inc['status']??'')==='In Progress'?'selected':'' ?>>In Progress</option>
<option value="Resolved" <?= ($inc['status']??'')==='Resolved'?'selected':'' ?>>Resolved</option>
<option value="Closed" <?= ($inc['status']??'')==='Closed'?'selected':'' ?>>Closed</option>
</select>
</td>
<td class="small text-nowrap"><?= htmlspecialchars(substr($inc['created_at']??'',0,10)) ?></td>
<td><button class="btn btn-xs btn-outline-danger" onclick="deleteIncident(<?=$inc['id']?>)"><i class="fas fa-trash"></i></button></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header d-flex justify-content-between align-items-center">Access Logs <button class="btn btn-sm btn-info text-white" onclick="openLogModal()"><i class="fas fa-plus"></i> Add</button></div><div class="card-body" style="max-height:420px;overflow-y:auto">
<?php if (empty($access_logs)): ?><div class="empty-state"><i class="fas fa-list"></i><p>No access logs recorded.</p></div>
<?php else: ?>
<div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchLogs" type="text" placeholder="Search..." onkeyup="filterTable('srchLogs','tblLogs')"></div>
<div class="table-responsive"><table id="tblLogs" class="table table-sm"><thead><tr><th>User</th><th>Type</th><th>Location</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
<?php foreach ($access_logs as $log): ?>
<tr>
<td class="small"><?= htmlspecialchars($log['user_id']??'') ?></td>
<td><span class="badge bg-info"><?= htmlspecialchars($log['access_type']??'') ?></span></td>
<td class="small"><?= htmlspecialchars($log['location']??'') ?></td>
<td><span class="badge bg-<?= ($log['status']??'') === 'allowed' ? 'success' : 'danger' ?>"><?= htmlspecialchars($log['status']??'') ?></span></td>
<td class="small text-nowrap"><?= htmlspecialchars(substr($log['accessed_at']??'',0,16)) ?></td>
<td><button class="btn btn-xs btn-outline-danger" onclick="deleteLog(<?=$log['id']?>)"><i class="fas fa-trash"></i></button></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
</div>
</div>

<!-- Incident Modal -->
<div class="modal fade" id="incidentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Report Security Incident</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">Incident Type *</label><select class="form-control" id="iType"><option>Unauthorized Access</option><option>Data Breach</option><option>Malware</option><option>Phishing</option><option>DDoS</option><option>Insider Threat</option><option>Hardware Theft</option><option>Other</option></select></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="iDesc" rows="3"></textarea></div>
    <div class="mb-3"><label class="form-label">Severity</label><select class="form-control" id="iSeverity"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
    <div class="mb-3"><label class="form-label">Reported By</label><input type="text" class="form-control" id="iReporter"></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" onclick="saveIncident()">Report</button></div>
</div></div></div>

<!-- Log Modal -->
<div class="modal fade" id="logModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Add Access Log</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">User ID *</label><input type="text" class="form-control" id="lUser"></div>
    <div class="mb-3"><label class="form-label">Access Type</label><select class="form-control" id="lType"><option>login</option><option>logout</option><option>file_access</option><option>admin_panel</option><option>api_call</option></select></div>
    <div class="mb-3"><label class="form-label">Location</label><input type="text" class="form-control" id="lLocation"></div>
    <div class="mb-3"><label class="form-label">IP Address</label><input type="text" class="form-control" id="lIP"></div>
    <div class="mb-3"><label class="form-label">Status</label><select class="form-control" id="lStatus"><option>allowed</option><option>denied</option></select></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-info text-white" onclick="saveLog()">Save</button></div>
</div></div></div>

<script>
const CSRF = '<?= $_SESSION['csrf_token'] ?>';
function openIncidentModal() { new bootstrap.Modal(document.getElementById('incidentModal')).show(); }
function openLogModal() { new bootstrap.Modal(document.getElementById('logModal')).show(); }
function saveIncident() {
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('ajax_action', 'add_incident');
    fd.append('incident_type', document.getElementById('iType').value);
    fd.append('description', document.getElementById('iDesc').value);
    fd.append('severity', document.getElementById('iSeverity').value);
    fd.append('reported_by', document.getElementById('iReporter').value);
    fetch('cybersecurity.php?ajax=1', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); });
}
function updateIncident(id, status) {
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('ajax_action', 'update_incident');
    fd.append('id', id); fd.append('status', status);
    fetch('cybersecurity.php?ajax=1', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{ if(!d.success) alert(d.message); });
}
function deleteIncident(id) {
    if(!confirm('Delete?')) return;
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('ajax_action', 'delete_incident'); fd.append('id', id);
    fetch('cybersecurity.php?ajax=1', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); });
}
function saveLog() {
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('ajax_action', 'add_access_log');
    fd.append('user_id', document.getElementById('lUser').value);
    fd.append('access_type', document.getElementById('lType').value);
    fd.append('location', document.getElementById('lLocation').value);
    fd.append('ip_address', document.getElementById('lIP').value);
    fd.append('status', document.getElementById('lStatus').value);
    fetch('cybersecurity.php?ajax=1', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); });
}
function deleteLog(id) {
    if(!confirm('Delete?')) return;
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('ajax_action', 'delete_access_log'); fd.append('id', id);
    fetch('cybersecurity.php?ajax=1', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); });
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
</body></html>
