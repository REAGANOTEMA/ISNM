<?php
$pageTitle = 'Recruitment';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr','manager','director','principal']);
$conn = $ctx['staff'];
if ($conn) {
    $staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`staff_recruitment` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        position_title VARCHAR(200) NOT NULL,
        department VARCHAR(100) DEFAULT '',
        description TEXT,
        requirements TEXT,
        salary_range VARCHAR(100) DEFAULT '',
        posted_date DATE DEFAULT NULL,
        closing_date DATE DEFAULT NULL,
        status VARCHAR(30) DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`job_applications` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        position_id INT DEFAULT 0,
        applicant_name VARCHAR(200) DEFAULT '',
        email VARCHAR(200) DEFAULT '',
        phone VARCHAR(50) DEFAULT '',
        resume_path VARCHAR(500) DEFAULT '',
        cover_letter TEXT,
        status VARCHAR(50) DEFAULT 'Applied',
        notes TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_position (position_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

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
    if ($action === 'add_position' && $conn) {
        $title = trim($_POST['position_title'] ?? '');
        $dept = trim($_POST['department'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $req = trim($_POST['requirements'] ?? '');
        $salary = trim($_POST['salary_range'] ?? '');
        $closing = trim($_POST['closing_date'] ?? '');
        if ($title) {
            $stmt = $conn->prepare("INSERT INTO staff_recruitment (position_title, department, description, requirements, salary_range, posted_date, closing_date, status) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 'Open')");
            if ($stmt) {
                $stmt->bind_param('ssssss', $title, $dept, $desc, $req, $salary, $closing);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else { $response['error'] = 'Position title required'; }
    } elseif ($action === 'update_position' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['position_title'] ?? '');
        $dept = trim($_POST['department'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $req = trim($_POST['requirements'] ?? '');
        $salary = trim($_POST['salary_range'] ?? '');
        $status = trim($_POST['status'] ?? 'Open');
        if ($id && $title) {
            $stmt = $conn->prepare("UPDATE staff_recruitment SET position_title=?, department=?, description=?, requirements=?, salary_range=?, status=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('ssssssi', $title, $dept, $desc, $req, $salary, $status, $id);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else { $response['error'] = 'ID and title required'; }
    } elseif ($action === 'delete_position' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM staff_recruitment WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else { $response['error'] = 'ID required'; }
    }
    echo json_encode($response); exit;
}
$user = $ctx['user'];

$openPositions = 0; $totalApplicants = 0; $shortlisted = 0; $hiredThisMonth = 0;
$positions = [];

if ($conn) {
    $r1 = $conn->query("SELECT COUNT(*) c FROM staff_recruitment WHERE status='Open'");
    if ($r1) $openPositions = (int)$r1->fetch_assoc()['c'];
    $r2 = $conn->query("SELECT COUNT(*) c FROM job_applications");
    if ($r2) $totalApplicants = (int)$r2->fetch_assoc()['c'];
    $r3 = $conn->query("SELECT COUNT(*) c FROM job_applications WHERE status='Shortlisted'");
    if ($r3) $shortlisted = (int)$r3->fetch_assoc()['c'];
    $r4 = $conn->query("SELECT COUNT(*) c FROM job_applications WHERE status='Hired' AND MONTH(updated_at)=MONTH(NOW()) AND YEAR(updated_at)=YEAR(NOW())");
    if ($r4) $hiredThisMonth = (int)$r4->fetch_assoc()['c'];
    $p = $conn->query("SELECT r.*, (SELECT COUNT(*) FROM job_applications ja WHERE ja.position_id=r.id) applicants_count FROM staff_recruitment r ORDER BY r.posted_date DESC LIMIT 50");
    if ($p) $positions = $p->fetch_all(MYSQLI_ASSOC);
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
        <h4 class="fw-bold mb-0"><i class="fas fa-user-tie me-2"></i>Recruitment</h4> <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                <div class="stat-content"><h3><?= number_format($openPositions) ?></h3><p>Open Positions</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-content"><h3><?= number_format($totalApplicants) ?></h3><p>Total Applicants</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-filter"></i></div>
                <div class="stat-content"><h3><?= number_format($shortlisted) ?></h3><p>Shortlisted</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-content"><h3><?= number_format($hiredThisMonth) ?></h3><p>Hired This Month</p></div>
            </div>
        </div>
    </div>
    <div class="content-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Open Positions</h5>
            <button class="btn btn-primary btn-sm" onclick="showAddPosition()"><i class="fas fa-plus me-1"></i>Add Position</button>
        </div>
        <?php if (empty($positions)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No recruitment positions found.</p></div>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchJZQM" type="text" placeholder="Search..." onkeyup="filterTable('srchJZQM','tblJZQM')"></div>
<div class="table-responsive">
            <table id="tblJZQM" class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Position Title</th><th>Department</th><th>Applicants</th><th>Status</th><th>Posted Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($positions as $pos): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pos['position_title'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($pos['department'] ?? '-') ?></td>
                        <td><?= (int)($pos['applicants_count'] ?? 0) ?></td>
                        <td><span class="badge bg-<?= ($pos['status'] ?? 'Open') === 'Open' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($pos['status'] ?? 'Open') ?></span></td>
                        <td><?= !empty($pos['posted_date']) ? date('d M Y', strtotime($pos['posted_date'])) : '-' ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editPosition(<?= $pos['id'] ?>, '<?= htmlspecialchars($pos['position_title'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($pos['department'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($pos['description'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($pos['requirements'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($pos['salary_range'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($pos['status'] ?? 'Open', ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deletePosition(<?= $pos['id'] ?>,'<?= htmlspecialchars($pos['position_title'] ?? '', ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
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
<!-- Add Position Modal -->
<div class="modal fade" id="addPositionModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-briefcase me-2"></i>Add Position</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="addPositionForm" onsubmit="event.preventDefault(); submitAddPosition()">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><div class="mb-3"><label class="form-label">Position Title *</label><input type="text" name="position_title" class="form-control" required></div></div>
                    <div class="col-6"><div class="mb-3"><label class="form-label">Department</label><input type="text" name="department" class="form-control"></div></div>
                    <div class="col-6"><div class="mb-3"><label class="form-label">Salary Range</label><input type="text" name="salary_range" class="form-control"></div></div>
                    <div class="col-6"><div class="mb-3"><label class="form-label">Closing Date</label><input type="date" name="closing_date" class="form-control"></div></div>
                    <div class="col-12"><div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div></div>
                    <div class="col-12"><div class="mb-3"><label class="form-label">Requirements</label><textarea name="requirements" class="form-control" rows="3"></textarea></div></div>
                </div>
                <input type="hidden" name="action" value="add_position">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Post Position</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Position Modal -->
<div class="modal fade" id="editPositionModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Position</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="editPositionForm" onsubmit="event.preventDefault(); submitEditPosition()">
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_pos_id">
                <div class="row g-3">
                    <div class="col-6"><div class="mb-3"><label class="form-label">Position Title *</label><input type="text" name="position_title" id="edit_pos_title" class="form-control" required></div></div>
                    <div class="col-6"><div class="mb-3"><label class="form-label">Department</label><input type="text" name="department" id="edit_pos_dept" class="form-control"></div></div>
                    <div class="col-6"><div class="mb-3"><label class="form-label">Salary Range</label><input type="text" name="salary_range" id="edit_pos_salary" class="form-control"></div></div>
                    <div class="col-6"><div class="mb-3"><label class="form-label">Status</label><select name="status" id="edit_pos_status" class="form-select"><option value="Open">Open</option><option value="Closed">Closed</option><option value="Filled">Filled</option><option value="Cancelled">Cancelled</option></select></div></div>
                    <div class="col-12"><div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="edit_pos_desc" class="form-control" rows="3"></textarea></div></div>
                    <div class="col-12"><div class="mb-3"><label class="form-label">Requirements</label><textarea name="requirements" id="edit_pos_req" class="form-control" rows="3"></textarea></div></div>
                </div>
                <input type="hidden" name="action" value="update_position">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function showAddPosition() { new bootstrap.Modal(document.getElementById('addPositionModal')).show(); }
function editPosition(id, title, dept, desc, req, salary, status) {
    document.getElementById('edit_pos_id').value = id;
    document.getElementById('edit_pos_title').value = title;
    document.getElementById('edit_pos_dept').value = dept;
    document.getElementById('edit_pos_desc').value = desc;
    document.getElementById('edit_pos_req').value = req;
    document.getElementById('edit_pos_salary').value = salary;
    document.getElementById('edit_pos_status').value = status;
    new bootstrap.Modal(document.getElementById('editPositionModal')).show();
}
function deletePosition(id, title) {
    if (!confirm('Delete "' + title + '"? This cannot be undone.')) return;
    var fd = new FormData();
    fd.append('action', 'delete_position');
    fd.append('id', id);
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) { window.location.reload(); } else { alert('Error: ' + (d.error || 'Failed')); } })
        .catch(function(e) { alert('Error deleting position'); });
}
function submitAddPosition() {
    var fd = new FormData(document.getElementById('addPositionForm'));
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) { window.location.reload(); } else { alert('Error: ' + (d.error || 'Failed')); } })
        .catch(function(e) { alert('Error adding position'); });
}
function submitEditPosition() {
    var fd = new FormData(document.getElementById('editPositionForm'));
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) { window.location.reload(); } else { alert('Error: ' + (d.error || 'Failed')); } })
        .catch(function(e) { alert('Error updating position'); });
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
