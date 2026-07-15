<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar', 'academics', 'director', 'principal']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];
$pageTitle = 'Graduation Management';

// ── AJAX CRUD Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'] ?? $_GET['ajax'] ?? '';
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
    }
    if (!$conn) { echo json_encode(['success' => false, 'message' => 'Database unavailable']); exit; }

    $tbl = null;
    foreach (['graduation_candidates', 'registrar_graduation'] as $t) {
        @$r = $conn->query("SELECT 1 FROM $t LIMIT 1");
        if ($r) { $tbl = $t; break; }
    }
    if (!$tbl) {
        $conn->query("CREATE TABLE IF NOT EXISTS graduation_candidates (
            id INT AUTO_INCREMENT PRIMARY KEY, student_name VARCHAR(300), full_name VARCHAR(300),
            program VARCHAR(200), program_name VARCHAR(200), index_number VARCHAR(50),
            student_id_col VARCHAR(50), award VARCHAR(200), award_title VARCHAR(200),
            status VARCHAR(30) DEFAULT 'pending', notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $tbl = 'graduation_candidates';
    }

    switch ($action) {
        case 'add_candidate':
            $sname = trim($_POST['student_name'] ?? '');
            $prog = trim($_POST['program'] ?? '');
            $idx = trim($_POST['index_number'] ?? '');
            $award = trim($_POST['award'] ?? '');
            if (!$sname) { echo json_encode(['success' => false, 'message' => 'Student name required']); exit; }
            $stmt = $conn->prepare("INSERT INTO $tbl (student_name, full_name, program, program_name, index_number, award, award_title, status, created_at) VALUES (?,?,?,?,?,?,'Diploma','pending',NOW())");
            $stmt->bind_param('ssssss', $sname, $sname, $prog, $prog, $idx, $award);
            $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Candidate added' : 'Failed']); exit;

        case 'approve_candidate':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("UPDATE $tbl SET status='approved' WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Approved' : 'Failed']); exit;

        case 'reject_candidate':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("UPDATE $tbl SET status='rejected' WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Rejected' : 'Failed']); exit;

        case 'delete_candidate':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM $tbl WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']); exit;

        case 'search':
            $q = '%' . trim($_POST['q'] ?? '') . '%';
            $candidates = [];
            $stmt = $conn->prepare("SELECT * FROM $tbl WHERE student_name LIKE ? OR full_name LIKE ? OR index_number LIKE ? OR program LIKE ? ORDER BY created_at DESC LIMIT 100");
            $stmt->bind_param('ssss', $q, $q, $q, $q); $stmt->execute(); $r = $stmt->get_result();
            while ($row = $r->fetch_assoc()) { $row['_source'] = $tbl; $candidates[] = $row; } $stmt->close();
            echo json_encode(['success' => true, 'data' => $candidates]); exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
}

// Load graduation candidates
$candidates = [];
$tables = ['registrar_graduation', 'graduation_candidates'];
foreach ($tables as $tbl) {
    @$r = $conn->query("SELECT * FROM $tbl ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows > 0) {
        while ($row = $r->fetch_assoc()) { $row['_source'] = $tbl; $candidates[] = $row; }
        break;
    }
}
if (empty($candidates) && $studentsConn) {
    @$r = $studentsConn->query("SELECT * FROM graduation_candidates ORDER BY created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $candidates[] = $row;
}

$total = count($candidates);
$approved = count(array_filter($candidates, fn($c) => ($c['status'] ?? '') === 'approved'));
$pending = count(array_filter($candidates, fn($c) => ($c['status'] ?? '') === 'pending' || !($c['status'] ?? '')));
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
        <h1><i class="fas fa-graduation-cap"></i> Graduation Management</h1>
        <div class="float-end">
            <input type="text" id="searchBox" class="form-control d-inline-block" style="width:200px" placeholder="Search..." onkeyup="doSearch()">
            <button class="btn btn-sm btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Candidate</button>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Candidates</h6><h3><?= $total ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Approved</h6><h3><?= $approved ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Pending</h6><h3><?= $pending ?></h3></div></div></div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Student Name</th><th>Program</th><th>Index Number</th><th>Award</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody id="gradTable">
                        <?php foreach ($candidates as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['student_name'] ?? $c['full_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['program'] ?? $c['program_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['index_number'] ?? $c['student_id'] ?? $c['student_id_col'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['award'] ?? $c['award_title'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($c['status'] ?? 'pending') === 'approved' ? 'success' : (($c['status'] ?? '') === 'rejected' ? 'danger' : 'warning') ?>"><?= $c['status'] ?? 'pending' ?></span></td>
                            <td><?= $c['created_at'] ?? '-' ?></td>
                            <td>
                                <?php if (($c['status'] ?? 'pending') !== 'approved'): ?>
                                <button class="btn btn-xs btn-success" onclick="approveCandidate(<?=$c['id']?>)" title="Approve"><i class="fas fa-check"></i></button>
                                <?php endif; ?>
                                <?php if (($c['status'] ?? 'pending') !== 'rejected'): ?>
                                <button class="btn btn-xs btn-warning" onclick="rejectCandidate(<?=$c['id']?>)" title="Reject"><i class="fas fa-times"></i></button>
                                <?php endif; ?>
                                <button class="btn btn-xs btn-outline-danger" onclick="deleteCandidate(<?=$c['id']?>)" title="Delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($candidates)): ?><tr><td colspan="7" class="text-center">No graduation candidates found</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="gradModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Add Graduation Candidate</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">Student Name *</label><input type="text" class="form-control" id="gName"></div>
    <div class="mb-3"><label class="form-label">Program</label><input type="text" class="form-control" id="gProgram"></div>
    <div class="mb-3"><label class="form-label">Index Number</label><input type="text" class="form-control" id="gIndex"></div>
    <div class="mb-3"><label class="form-label">Award</label><input type="text" class="form-control" id="gAward" value="Diploma"></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="saveCandidate()">Save</button></div>
</div></div></div>

<script>
const CSRF = '<?= $_SESSION['csrf_token'] ?>';
function openModal() { new bootstrap.Modal(document.getElementById('gradModal')).show(); }
function saveCandidate() {
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('ajax_action', 'add_candidate');
    fd.append('student_name', document.getElementById('gName').value);
    fd.append('program', document.getElementById('gProgram').value);
    fd.append('index_number', document.getElementById('gIndex').value);
    fd.append('award', document.getElementById('gAward').value);
    fetch('graduation-management.php?ajax=1', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
function approveCandidate(id) {
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('ajax_action', 'approve_candidate'); fd.append('id', id);
    fetch('graduation-management.php?ajax=1', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
function rejectCandidate(id) {
    if (!confirm('Reject this candidate?')) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('ajax_action', 'reject_candidate'); fd.append('id', id);
    fetch('graduation-management.php?ajax=1', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
function deleteCandidate(id) {
    if (!confirm('Delete this candidate?')) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('ajax_action', 'delete_candidate'); fd.append('id', id);
    fetch('graduation-management.php?ajax=1', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
let searchTimer;
function doSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const q = document.getElementById('searchBox').value;
        if (q.length < 2) { location.reload(); return; }
        const fd = new FormData();
        fd.append('ajax_action', 'search'); fd.append('q', q);
        fetch('graduation-management.php?ajax=1', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (!d.data) return;
                const tbody = document.getElementById('gradTable');
                if (!d.data.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-center">No results</td></tr>'; return; }
                tbody.innerHTML = d.data.map(c => `<tr>
                    <td>${c.student_name||c.full_name||'-'}</td><td>${c.program||c.program_name||'-'}</td>
                    <td>${c.index_number||c.student_id||'-'}</td><td>${c.award||c.award_title||'-'}</td>
                    <td><span class="badge bg-${c.status==='approved'?'success':c.status==='rejected'?'danger':'warning'}">${c.status||'pending'}</span></td>
                    <td>${c.created_at||'-'}</td>
                    <td><button class="btn btn-xs btn-success" onclick="approveCandidate(${c.id})"><i class="fas fa-check"></i></button>
                    <button class="btn btn-xs btn-warning" onclick="rejectCandidate(${c.id})"><i class="fas fa-times"></i></button>
                    <button class="btn btn-xs btn-outline-danger" onclick="deleteCandidate(${c.id})"><i class="fas fa-trash"></i></button></td>
                </tr>`).join('');
            });
    }, 400);
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
