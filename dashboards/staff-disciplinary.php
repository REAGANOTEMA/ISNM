<?php
$pageTitle = 'Staff Disciplinary';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr','manager','director','principal']);
$conn = $ctx['staff'];

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

// Ensure disciplinary_actions table exists
if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS disciplinary_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        offense_type VARCHAR(255) NOT NULL,
        description TEXT,
        incident_date DATE DEFAULT NULL,
        action_taken TEXT,
        status VARCHAR(50) DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid security token.']);
        exit;
    }
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => 'Unknown action'];
    $action = $_POST['action'];
    if ($action === 'add_case' && $conn) {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        $offense = trim($_POST['offense_type'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $date = trim($_POST['incident_date'] ?? date('Y-m-d'));
        $action_taken = trim($_POST['action_taken'] ?? '');
        if ($staff_id && $offense) {
            $stmt = $conn->prepare("INSERT INTO disciplinary_actions (staff_id, offense_type, description, incident_date, action_taken, status) VALUES (?, ?, ?, ?, ?, 'Open')");
            if ($stmt) {
                $stmt->bind_param('issss', $staff_id, $offense, $desc, $date, $action_taken);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else {
            $response['error'] = 'Staff and offense type are required';
        }
    } elseif ($action === 'update_case' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $action_taken = trim($_POST['action_taken'] ?? '');
        if ($id && $status) {
            $stmt = $conn->prepare("UPDATE disciplinary_actions SET status=?, action_taken=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('ssi', $status, $action_taken, $id);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else {
            $response['error'] = 'Case ID and status are required';
        }
    } elseif ($action === 'delete_case' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM disciplinary_actions WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else {
            $response['error'] = 'Case ID is required';
        }
    }
    echo json_encode($response);
    exit;
}

$totalCases = 0; $openCases = 0; $resolved = 0; $thisMonth = 0;
$cases = [];

if ($conn) {
    $r1 = $conn->query("SELECT COUNT(*) c FROM disciplinary_actions");
    if ($r1) $totalCases = (int)$r1->fetch_assoc()['c'];
    $r2 = $conn->query("SELECT COUNT(*) c FROM disciplinary_actions WHERE status='Open'");
    if ($r2) $openCases = (int)$r2->fetch_assoc()['c'];
    $r3 = $conn->query("SELECT COUNT(*) c FROM disciplinary_actions WHERE status='Resolved'");
    if ($r3) $resolved = (int)$r3->fetch_assoc()['c'];
    $r4 = $conn->query("SELECT COUNT(*) c FROM disciplinary_actions WHERE MONTH(incident_date)=MONTH(NOW()) AND YEAR(incident_date)=YEAR(NOW())");
    if ($r4) $thisMonth = (int)$r4->fetch_assoc()['c'];
    $c = $conn->query("SELECT d.*, s.full_name staff_name FROM disciplinary_actions d LEFT JOIN staff s ON d.staff_id=s.id ORDER BY d.incident_date DESC LIMIT 50");
    if ($c) $cases = $c->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-gavel me-2"></i>Staff Disciplinary</h4> <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
                <div class="stat-content"><h3><?= number_format($totalCases) ?></h3><p>Total Cases</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-content"><h3><?= number_format($openCases) ?></h3><p>Open</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <div class="stat-content"><h3><?= number_format($resolved) ?></h3><p>Resolved</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-calendar-month"></i></div>
                <div class="stat-content"><h3><?= number_format($thisMonth) ?></h3><p>This Month</p></div>
            </div>
        </div>
    </div>
    <div class="content-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Disciplinary Cases</h5>
            <button class="btn btn-primary btn-sm" onclick="showAddCase()"><i class="fas fa-plus me-1"></i>Add Case</button>
        </div>
        <?php if (empty($cases)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No disciplinary records found.</p></div>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchLHFA" type="text" placeholder="Search..." onkeyup="filterTable('srchLHFA','tblLHFA')"></div>
<div class="table-responsive">
            <table id="tblLHFA" class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Staff Name</th><th>Incident Date</th><th>Offense Type</th><th>Description</th><th>Action Taken</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $dc): ?>
                    <tr>
                        <td><?= htmlspecialchars($dc['staff_name'] ?? '-') ?></td>
                        <td><?= !empty($dc['incident_date']) ? date('d M Y', strtotime($dc['incident_date'])) : '-' ?></td>
                        <td><?= htmlspecialchars($dc['offense_type'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars(mb_substr($dc['description'] ?? '-', 0, 60)) ?><?= strlen($dc['description'] ?? '') > 60 ? '...' : '' ?></small></td>
                        <td><?= htmlspecialchars($dc['action_taken'] ?? '-') ?></td>
                        <td><span class="badge bg-<?= ($dc['status'] ?? 'Open') === 'Resolved' ? 'success' : 'danger' ?>"><?= htmlspecialchars($dc['status'] ?? 'Open') ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editCase(<?= $dc['id'] ?>, '<?= htmlspecialchars($dc['status'] ?? 'Open', ENT_QUOTES) ?>', '<?= htmlspecialchars($dc['action_taken'] ?? '', ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCase(<?= $dc['id'] ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<!-- Add Case Modal -->
<div class="modal fade" id="addCaseModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-gavel me-2"></i>Add Disciplinary Case</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="addCaseForm" onsubmit="event.preventDefault(); submitAddCase()">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Staff *</label><select name="staff_id" class="form-select" required>
                    <option value="">Select Staff</option>
                    <?php
                    $staffOpts = [];
                    if ($conn) {
                        $so = $conn->query("SELECT id, full_name FROM staff WHERE status='Active' ORDER BY full_name");
                        if ($so) while ($row = $so->fetch_assoc()) echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['full_name']).'</option>';
                    }
                    ?>
                </select></div>
                <div class="mb-3"><label class="form-label">Offense Type *</label><input type="text" name="offense_type" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Incident Date</label><input type="date" name="incident_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                <div class="mb-3"><label class="form-label">Action Taken</label><input type="text" name="action_taken" class="form-control"></div>
                <input type="hidden" name="action" value="add_case">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Case Modal -->
<div class="modal fade" id="editCaseModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Case</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="editCaseForm" onsubmit="event.preventDefault(); submitEditCase()">
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_case_id">
                <div class="mb-3"><label class="form-label">Status</label><select name="status" id="edit_case_status" class="form-select"><option value="Open">Open</option><option value="Resolved">Resolved</option><option value="Dismissed">Dismissed</option></select></div>
                <div class="mb-3"><label class="form-label">Action Taken</label><textarea name="action_taken" id="edit_case_action" class="form-control" rows="3"></textarea></div>
                <input type="hidden" name="action" value="update_case">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function showAddCase() { new bootstrap.Modal(document.getElementById('addCaseModal')).show(); }
function editCase(id, status, actionTaken) {
    document.getElementById('edit_case_id').value = id;
    document.getElementById('edit_case_status').value = status;
    document.getElementById('edit_case_action').value = actionTaken;
    new bootstrap.Modal(document.getElementById('editCaseModal')).show();
}
function deleteCase(id) {
    if (!confirm('Delete this disciplinary case?')) return;
    var fd = new FormData();
    fd.append('action', 'delete_case');
    fd.append('id', id);
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) { window.location.reload(); }
            else { alert('Error: ' + (d.error || 'Failed')); }
        })
        .catch(function(e) { alert('Error deleting case'); });
}
function submitAddCase() {
    var fd = new FormData(document.getElementById('addCaseForm'));
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) { window.location.reload(); }
            else { alert('Error: ' + (d.error || 'Failed')); }
        })
        .catch(function(e) { alert('Error adding case'); });
}
function submitEditCase() {
    var fd = new FormData(document.getElementById('editCaseForm'));
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) { window.location.reload(); }
            else { alert('Error: ' + (d.error || 'Failed')); }
        })
        .catch(function(e) { alert('Error updating case'); });
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
</body>
</html>
