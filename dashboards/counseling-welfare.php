<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'principal', 'deputy', 'matron', 'warden', 'secretary']);
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

    switch ($action) {
        case 'add_session':
            $sname = trim($_POST['student_name'] ?? '');
            $stype = trim($_POST['session_type'] ?? 'general');
            $counselor = trim($_POST['counselor_name'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $date = trim($_POST['session_date'] ?? date('Y-m-d'));
            if (!$sname) { echo json_encode(['success' => false, 'message' => 'Student name required']); exit; }
            $stmt = $conn->prepare("INSERT INTO counseling_sessions (student_name, session_type, counselor_name, notes, session_date, status, created_at) VALUES (?,?,?,?,$date,'scheduled',NOW())");
            if ($stmt) { $stmt->bind_param('ssss', $sname, $stype, $counselor, $notes); $ok = $stmt->execute(); $stmt->close(); echo json_encode(['success' => $ok, 'message' => $ok ? 'Session added' : 'Failed']); }
            else { $conn->query("CREATE TABLE IF NOT EXISTS counseling_sessions (id INT AUTO_INCREMENT PRIMARY KEY, student_name VARCHAR(200), session_type VARCHAR(50), counselor_name VARCHAR(200), notes TEXT, session_date DATE, status VARCHAR(30), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); echo json_encode(['success' => false, 'message' => 'Table created, try again']); }
            exit;

        case 'update_session':
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'scheduled');
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("UPDATE counseling_sessions SET status=? WHERE id=?");
            $stmt->bind_param('si', $status, $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Failed']);
            exit;

        case 'delete_session':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM counseling_sessions WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']);
            exit;

        case 'add_welfare':
            $sname = trim($_POST['student_name'] ?? '');
            $issue = trim($_POST['issue_description'] ?? '');
            $priority = trim($_POST['priority'] ?? 'normal');
            if (!$sname || !$issue) { echo json_encode(['success' => false, 'message' => 'Student name and issue required']); exit; }
            $stmt = $conn->prepare("INSERT INTO student_welfare_cases (student_name, issue_description, priority, status, created_at) VALUES (?,?,?,'open',NOW())");
            if ($stmt) { $stmt->bind_param('sss', $sname, $issue, $priority); $ok = $stmt->execute(); $stmt->close(); echo json_encode(['success' => $ok, 'message' => $ok ? 'Case added' : 'Failed']); }
            else { $conn->query("CREATE TABLE IF NOT EXISTS student_welfare_cases (id INT AUTO_INCREMENT PRIMARY KEY, student_name VARCHAR(200), issue_description TEXT, priority VARCHAR(20) DEFAULT 'normal', status VARCHAR(30) DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); echo json_encode(['success' => false, 'message' => 'Table created, try again']); }
            exit;

        case 'update_welfare':
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'open');
            $priority = trim($_POST['priority'] ?? '');
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            if ($priority) {
                $stmt = $conn->prepare("UPDATE student_welfare_cases SET status=?, priority=? WHERE id=?");
                $stmt->bind_param('ssi', $status, $priority, $id);
            } else {
                $stmt = $conn->prepare("UPDATE student_welfare_cases SET status=? WHERE id=?");
                $stmt->bind_param('si', $status, $id);
            }
            $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Failed']);
            exit;

        case 'delete_welfare':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM student_welfare_cases WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']);
            exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
}

$sessions = []; $welfare = [];
if ($conn) {
    @$r = $conn->query("SELECT * FROM counseling_sessions ORDER BY session_date DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $sessions[] = $row;
    @$r2 = $conn->query("SELECT * FROM student_welfare_cases ORDER BY created_at DESC LIMIT 100");
    if ($r2) while ($row = $r2->fetch_assoc()) $welfare[] = $row;
}

$pageTitle = 'Counseling & Student Welfare';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
        <h1><i class="fas fa-hand-holding-heart"></i> Counseling & Student Welfare</h1>
        <div class="float-end">
            <button class="btn btn-sm btn-primary me-1" onclick="openSessionModal()"><i class="fas fa-plus"></i> New Session</button>
            <button class="btn btn-sm btn-success me-1" onclick="openWelfareModal()"><i class="fas fa-plus"></i> New Case</button>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Counseling Sessions</h6><h3><?= count($sessions) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Welfare Cases</h6><h3><?= count($welfare) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Open Welfare</h6><h3><?= count(array_filter($welfare, fn($w) => ($w['status'] ?? '') === 'open')) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Counseling Sessions</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Student</th><th>Type</th><th>Counselor</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['student_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['session_type'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['counselor_name'] ?? '-') ?></td>
                                    <td><?= $s['session_date'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($s['status'] ?? 'scheduled') === 'completed' ? 'success' : (($s['status'] ?? '') === 'cancelled' ? 'danger' : 'warning') ?>"><?= $s['status'] ?? 'scheduled' ?></span></td>
                                    <td>
                                        <select class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="updateSession(<?=$s['id']?>,this.value)">
                                            <option value="scheduled" <?= ($s['status']??'')==='scheduled'?'selected':'' ?>>Scheduled</option>
                                            <option value="completed" <?= ($s['status']??'')==='completed'?'selected':'' ?>>Completed</option>
                                            <option value="cancelled" <?= ($s['status']??'')==='cancelled'?'selected':'' ?>>Cancelled</option>
                                        </select>
                                        <button class="btn btn-xs btn-outline-danger" onclick="deleteSession(<?=$s['id']?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sessions)): ?><tr><td colspan="6" class="text-center">No counseling sessions</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Welfare Cases</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Student</th><th>Issue</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($welfare as $w): ?>
                                <tr>
                                    <td><?= htmlspecialchars($w['student_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($w['issue_description'] ?? $w['issue'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($w['priority'] ?? 'normal') === 'high' ? 'danger' : (($w['priority'] ?? '') === 'medium' ? 'warning' : 'info') ?>"><?= $w['priority'] ?? 'normal' ?></span></td>
                                    <td><span class="badge bg-<?= ($w['status'] ?? 'open') === 'resolved' ? 'success' : (($w['status'] ?? '') === 'closed' ? 'secondary' : 'danger') ?>"><?= $w['status'] ?? 'open' ?></span></td>
                                    <td>
                                        <select class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="updateWelfare(<?=$w['id']?>,this.value)">
                                            <option value="open" <?= ($w['status']??'')==='open'?'selected':'' ?>>Open</option>
                                            <option value="in_progress" <?= ($w['status']??'')==='in_progress'?'selected':'' ?>>In Progress</option>
                                            <option value="resolved" <?= ($w['status']??'')==='resolved'?'selected':'' ?>>Resolved</option>
                                            <option value="closed" <?= ($w['status']??'')==='closed'?'selected':'' ?>>Closed</option>
                                        </select>
                                        <button class="btn btn-xs btn-outline-danger" onclick="deleteWelfare(<?=$w['id']?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($welfare)): ?><tr><td colspan="5" class="text-center">No welfare cases</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Session Modal -->
<div class="modal fade" id="sessionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">New Counseling Session</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">Student Name *</label><input type="text" class="form-control" id="sessStudent"></div>
    <div class="mb-3"><label class="form-label">Session Type</label><select class="form-control" id="sessType"><option>general</option><option>academic</option><option>personal</option><option>disciplinary</option><option>career</option></select></div>
    <div class="mb-3"><label class="form-label">Counselor Name</label><input type="text" class="form-control" id="sessCounselor"></div>
    <div class="mb-3"><label class="form-label">Session Date</label><input type="date" class="form-control" id="sessDate" value="<?= date('Y-m-d') ?>"></div>
    <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" id="sessNotes" rows="3"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="saveSession()">Save</button></div>
</div></div></div>

<!-- Welfare Modal -->
<div class="modal fade" id="welfareModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">New Welfare Case</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">Student Name *</label><input type="text" class="form-control" id="welfareStudent"></div>
    <div class="mb-3"><label class="form-label">Issue Description *</label><textarea class="form-control" id="welfareIssue" rows="3"></textarea></div>
    <div class="mb-3"><label class="form-label">Priority</label><select class="form-control" id="welfarePriority"><option value="low">Low</option><option value="normal" selected>Normal</option><option value="medium">Medium</option><option value="high">High</option></select></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" onclick="saveWelfare()">Save</button></div>
</div></div></div>

<script>
const CSRF = '<?= $_SESSION['csrf_token'] ?>';
function ajax(action, data) {
    data.append('csrf_token', CSRF);
    data.append('ajax_action', action);
    return fetch('counseling-welfare.php?ajax=1', { method: 'POST', body: data }).then(r => r.json());
}
function openSessionModal() { new bootstrap.Modal(document.getElementById('sessionModal')).show(); }
function openWelfareModal() { new bootstrap.Modal(document.getElementById('welfareModal')).show(); }
function saveSession() {
    const fd = new FormData();
    fd.append('student_name', document.getElementById('sessStudent').value);
    fd.append('session_type', document.getElementById('sessType').value);
    fd.append('counselor_name', document.getElementById('sessCounselor').value);
    fd.append('session_date', document.getElementById('sessDate').value);
    fd.append('notes', document.getElementById('sessNotes').value);
    ajax('add_session', fd).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
function saveWelfare() {
    const fd = new FormData();
    fd.append('student_name', document.getElementById('welfareStudent').value);
    fd.append('issue_description', document.getElementById('welfareIssue').value);
    fd.append('priority', document.getElementById('welfarePriority').value);
    ajax('add_welfare', fd).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
function updateSession(id, status) {
    const fd = new FormData(); fd.append('id', id); fd.append('status', status);
    ajax('update_session', fd).then(d => { if (!d.success) alert(d.message); });
}
function updateWelfare(id, status) {
    const fd = new FormData(); fd.append('id', id); fd.append('status', status);
    ajax('update_welfare', fd).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
function deleteSession(id) {
    if (!confirm('Delete this session?')) return;
    const fd = new FormData(); fd.append('id', id);
    ajax('delete_session', fd).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
function deleteWelfare(id) {
    if (!confirm('Delete this case?')) return;
    const fd = new FormData(); fd.append('id', id);
    ajax('delete_welfare', fd).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
