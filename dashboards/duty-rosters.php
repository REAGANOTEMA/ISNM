<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr', 'manager', 'director', 'head', 'matron', 'warden']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$pageTitle = 'Duty Rosters & Scheduling';

// ── AJAX CRUD Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'] ?? $_GET['ajax'] ?? '';
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
    }
    if (!$conn) { echo json_encode(['success' => false, 'message' => 'Database unavailable']); exit; }

    // Ensure table exists
    @$conn->query("CREATE TABLE IF NOT EXISTS duty_rosters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_name VARCHAR(200),
        shift VARCHAR(100),
        roster_date DATE,
        location VARCHAR(200),
        status VARCHAR(30) DEFAULT 'scheduled',
        notes TEXT,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    switch ($action) {
        case 'add_roster':
            $name = trim($_POST['staff_name'] ?? '');
            $shift = trim($_POST['shift'] ?? '');
            $date = trim($_POST['roster_date'] ?? date('Y-m-d'));
            $loc = trim($_POST['location'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            if (!$name) { echo json_encode(['success' => false, 'message' => 'Staff name required']); exit; }
            $stmt = $conn->prepare("INSERT INTO duty_rosters (staff_name, shift, roster_date, location, notes, created_by, created_at) VALUES (?,?,?,?,?,?,NOW())");
            $uid = (int)($user['id'] ?? 0);
            if ($stmt) {
                $stmt->bind_param('sssssi', $name, $shift, $date, $loc, $notes, $uid);
                $ok = $stmt->execute(); $stmt->close();
            } else { $ok = false; }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Roster added' : 'Failed']); exit;

        case 'update_roster':
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'scheduled');
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("UPDATE duty_rosters SET status=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('si', $status, $id); $ok = $stmt->execute(); $stmt->close();
            } else { $ok = false; }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Failed']); exit;

        case 'delete_roster':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM duty_rosters WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            } else { $ok = false; }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']); exit;

        case 'search':
            $q = '%' . trim($_POST['q'] ?? '') . '%';
            $rosters = [];
            $stmt = $conn->prepare("SELECT * FROM duty_rosters WHERE staff_name LIKE ? OR shift LIKE ? OR location LIKE ? ORDER BY roster_date DESC LIMIT 100");
            if ($stmt) {
                $stmt->bind_param('sss', $q, $q, $q); $stmt->execute(); $r = $stmt->get_result();
                while ($row = $r->fetch_assoc()) $rosters[] = $row; $stmt->close();
            }
            echo json_encode(['success' => true, 'data' => $rosters]); exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
}

$rosters = [];
if ($conn) {
    $staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';
    @$conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`duty_rosters` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_name VARCHAR(200),
        shift VARCHAR(100),
        roster_date DATE,
        location VARCHAR(200),
        status VARCHAR(30) DEFAULT 'scheduled',
        notes TEXT,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @$r = $conn->query("SELECT * FROM duty_rosters ORDER BY roster_date DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $rosters[] = $row;
    if (empty($rosters)) {
        $r2 = @$conn->query("SHOW TABLES LIKE 'duty_roster'");
        if ($r2 && $r2->num_rows > 0) {
            @$r = $conn->query("SELECT * FROM duty_roster ORDER BY created_at DESC LIMIT 100");
            if ($r) while ($row = $r->fetch_assoc()) $rosters[] = $row;
        }
    }
}
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
        <h1><i class="fas fa-calendar-alt"></i> Duty Rosters & Scheduling</h1>
        <div class="float-end">
            <input type="text" id="searchBox" class="form-control d-inline-block" style="width:200px" placeholder="Search..." onkeyup="doSearch()">
            <button class="btn btn-sm btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Duty</button>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Rosters</h6><h3><?= count($rosters) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Scheduled</h6><h3><?= count(array_filter($rosters, fn($r) => ($r['status']??'')==='scheduled')) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Completed</h6><h3><?= count(array_filter($rosters, fn($r) => ($r['status']??'')==='completed')) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Today</h6><h3><?= count(array_filter($rosters, fn($r) => ($r['roster_date']??$r['date']??'')===date('Y-m-d'))) ?></h3></div></div></div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Staff Name</th><th>Shift/Role</th><th>Date</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="rosterTable">
                        <?php foreach ($rosters as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['staff_name'] ?? $r['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['shift'] ?? $r['role'] ?? $r['duty_type'] ?? '-') ?></td>
                            <td><?= $r['roster_date'] ?? $r['date'] ?? '-' ?></td>
                            <td><?= htmlspecialchars($r['location'] ?? $r['department'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($r['status'] ?? 'scheduled') === 'completed' ? 'success' : (($r['status'] ?? '') === 'cancelled' ? 'danger' : 'primary') ?>"><?= $r['status'] ?? 'scheduled' ?></span></td>
                            <td>
                                <select class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="updateRoster(<?=$r['id']?>,this.value)">
                                    <option value="scheduled" <?= ($r['status']??'')==='scheduled'?'selected':'' ?>>Scheduled</option>
                                    <option value="completed" <?= ($r['status']??'')==='completed'?'selected':'' ?>>Completed</option>
                                    <option value="cancelled" <?= ($r['status']??'')==='cancelled'?'selected':'' ?>>Cancelled</option>
                                </select>
                                <button class="btn btn-xs btn-outline-danger" onclick="deleteRoster(<?=$r['id']?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rosters)): ?><tr><td colspan="6" class="text-center">No duty rosters found</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rosterModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Add Duty Roster</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">Staff Name *</label><input type="text" class="form-control" id="rName"></div>
    <div class="mb-3"><label class="form-label">Shift/Role</label><input type="text" class="form-control" id="rShift" placeholder="e.g. Morning, Night"></div>
    <div class="mb-3"><label class="form-label">Date *</label><input type="date" class="form-control" id="rDate" value="<?= date('Y-m-d') ?>"></div>
    <div class="mb-3"><label class="form-label">Location</label><input type="text" class="form-control" id="rLocation"></div>
    <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" id="rNotes" rows="2"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="saveRoster()">Save</button></div>
</div></div></div>

<script>
const CSRF = '<?= $_SESSION['csrf_token'] ?>';
function openModal() { new bootstrap.Modal(document.getElementById('rosterModal')).show(); }
function saveRoster() {
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('ajax_action', 'add_roster');
    fd.append('staff_name', document.getElementById('rName').value);
    fd.append('shift', document.getElementById('rShift').value);
    fd.append('roster_date', document.getElementById('rDate').value);
    fd.append('location', document.getElementById('rLocation').value);
    fd.append('notes', document.getElementById('rNotes').value);
    fetch('duty-rosters.php?ajax=1', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
function updateRoster(id, status) {
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('ajax_action', 'update_roster');
    fd.append('id', id); fd.append('status', status);
    fetch('duty-rosters.php?ajax=1', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (!d.success) alert(d.message); });
}
function deleteRoster(id) {
    if (!confirm('Delete this roster entry?')) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('ajax_action', 'delete_roster'); fd.append('id', id);
    fetch('duty-rosters.php?ajax=1', { method: 'POST', body: fd })
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
        fetch('duty-rosters.php?ajax=1', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (!d.data) return;
                const tbody = document.getElementById('rosterTable');
                if (!d.data.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center">No results</td></tr>'; return; }
                tbody.innerHTML = d.data.map(r => `<tr>
                    <td>${r.staff_name||'-'}</td><td>${r.shift||'-'}</td><td>${r.roster_date||'-'}</td>
                    <td>${r.location||'-'}</td>
                    <td><span class="badge bg-${r.status==='completed'?'success':'primary'}">${r.status||'scheduled'}</span></td>
                    <td><select class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="updateRoster(${r.id},this.value)">
                        <option value="scheduled" ${r.status==='scheduled'?'selected':''}>Scheduled</option>
                        <option value="completed" ${r.status==='completed'?'selected':''}>Completed</option>
                        <option value="cancelled" ${r.status==='cancelled'?'selected':''}>Cancelled</option>
                    </select> <button class="btn btn-xs btn-outline-danger" onclick="deleteRoster(${r.id})"><i class="fas fa-trash"></i></button></td>
                </tr>`).join('');
            });
    }, 400);
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
