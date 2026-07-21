<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director','principal','deputy','secretary','matron','warden','head']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$pageTitle = 'Student Discipline';

$profileImageUrl = '../images/username.png';
$profileSettingsFile = __DIR__ . '/../includes/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    include_once $profileSettingsFile;
    if (function_exists('getStaffProfileImageUrl')) {
        $url = getStaffProfileImageUrl($user_id);
        if ($url) $profileImageUrl = $url;
    }
}

$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

if ($studentsDb) {
    $studentsDb->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`student_discipline` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, offense TEXT, reported_by VARCHAR(200), hearing_date DATE, outcome VARCHAR(500), action_taken VARCHAR(200), status ENUM('open','resolved','appealed') DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $studentsDb->query("ALTER TABLE `student_discipline` ADD COLUMN IF NOT EXISTS `case_type` VARCHAR(100) DEFAULT NULL AFTER `student_id`");
    $studentsDb->query("ALTER TABLE `student_discipline` ADD COLUMN IF NOT EXISTS `description` TEXT DEFAULT NULL AFTER `case_type`");
    $studentsDb->query("ALTER TABLE `student_discipline` ADD COLUMN IF NOT EXISTS `severity` ENUM('Low','Medium','High','Critical') DEFAULT 'Medium' AFTER `description`");
    $studentsDb->query("ALTER TABLE `student_discipline` ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL AFTER `action_taken`");
    $studentsDb->query("ALTER TABLE `student_discipline` ADD COLUMN IF NOT EXISTS `closed_date` DATETIME DEFAULT NULL AFTER `notes`");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => 'Unknown action'];
    $action = $_POST['action'];

    if ($action === 'add_case' && $studentsDb) {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $case_type = trim($_POST['case_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $severity = trim($_POST['severity'] ?? 'Medium');
        $reported_by = trim($_POST['reported_by'] ?? '');
        if ($student_id && $case_type) {
            $stmt = $studentsDb->prepare("INSERT INTO student_discipline (student_id, case_type, description, severity, reported_by, status) VALUES (?, ?, ?, ?, ?, 'Open')");
            if ($stmt) {
                $stmt->bind_param('issss', $student_id, $case_type, $description, $severity, $reported_by);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else {
            $response['error'] = 'Student and case type are required';
        }
    } elseif ($action === 'update_case' && $studentsDb) {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($id && $status) {
            $stmt = $studentsDb->prepare("UPDATE student_discipline SET status=?, notes=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('ssi', $status, $notes, $id);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else {
            $response['error'] = 'Case ID and status are required';
        }
    } elseif ($action === 'close_case' && $studentsDb) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $studentsDb->prepare("UPDATE student_discipline SET status='Closed', closed_date=NOW() WHERE id=?");
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

$totalCases = $openCases = $resolvedCases = $warningCases = 0;
$records = [];
if ($studentsDb) {
    try {
        $r = $studentsDb->query("SELECT COUNT(*) as c FROM student_discipline");
        if ($r) $totalCases = (int)$r->fetch_assoc()['c'];
        $r = $studentsDb->query("SELECT COUNT(*) as c FROM student_discipline WHERE status IN ('open','Open')");
        if ($r) $openCases = (int)$r->fetch_assoc()['c'];
        $r = $studentsDb->query("SELECT COUNT(*) as c FROM student_discipline WHERE status IN ('resolved','Resolved')");
        if ($r) $resolvedCases = (int)$r->fetch_assoc()['c'];
        $r = $studentsDb->query("SELECT COUNT(*) as c FROM student_discipline WHERE action_taken LIKE '%warning%'");
        if ($r) $warningCases = (int)$r->fetch_assoc()['c'];
        $r = $studentsDb->query("SELECT d.*, CONCAT(s.first_name,' ',s.surname) as student_name FROM student_discipline d LEFT JOIN students s ON d.student_id=s.id ORDER BY d.created_at DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $records[] = $row;
    } catch (Exception $e) { error_log('student-discipline context: ' . $e->getMessage()); }
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
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-gavel me-2"></i>Student Discipline</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <?php if ($flashMsg): ?>
    <div class="alert alert-<?= $flashType === 'error' ? 'danger' : $flashType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashMsg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <div class="stats-grid">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-folder-open"></i></div><div class="stat-content"><h3><?= $totalCases ?></h3><p>Total Cases</p></div></div>
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $openCases ?></h3><p>Open</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-double"></i></div><div class="stat-content"><h3><?= $resolvedCases ?></h3><p>Resolved</p></div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-content"><h3><?= $warningCases ?></h3><p>Warning Issued</p></div></div>
    </div>
    <div class="content-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Disciplinary Cases</h5>
            <button class="btn btn-primary btn-sm" onclick="showAddCase()"><i class="fas fa-plus me-1"></i>Add Case</button>
        </div>
        <?php if (empty($records)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No disciplinary records found.</p></div>
        <?php else: ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchSD" type="text" placeholder="Search..." onkeyup="filterTable('srchSD','tblSD')"></div>
        <div class="table-responsive">
            <table id="tblSD" class="table table-striped table-hover align-middle">
                <thead><tr><th>Student</th><th>Case Type</th><th>Description</th><th>Severity</th><th>Reported By</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($records)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No disciplinary records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($records as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['student_name'] ?? 'ID: '.$d['student_id']) ?></td>
                        <td><?= htmlspecialchars($d['case_type'] ?? $d['offense'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars(mb_substr($d['description'] ?? $d['offense'] ?? '-', 0, 60)) ?><?= strlen($d['description'] ?? $d['offense'] ?? '') > 60 ? '...' : '' ?></small></td>
                        <td><span class="badge bg-<?= ($d['severity'] ?? '') === 'Critical' ? 'dark' : (($d['severity'] ?? '') === 'High' ? 'danger' : (($d['severity'] ?? '') === 'Medium' ? 'warning' : 'secondary')) ?>"><?= htmlspecialchars($d['severity'] ?? '-') ?></span></td>
                        <td><?= htmlspecialchars($d['reported_by'] ?? '-') ?></td>
                        <td><span class="badge bg-<?= in_array($d['status'] ?? '', ['resolved','Resolved','Closed']) ? 'success' : (in_array($d['status'] ?? '', ['open','Open']) ? 'warning' : 'secondary') ?>"><?= htmlspecialchars(ucfirst($d['status'] ?? 'Unknown')) ?></span></td>
                        <td>
                            <?php if (!in_array($d['status'] ?? '', ['Closed','closed'])): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick='editCase(<?= $d["id"] ?>, <?= json_encode($d["status"] ?? "open") ?>, <?= json_encode($d["notes"] ?? "") ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="closeCase(<?= $d['id'] ?>)" title="Close"><i class="fas fa-times-circle"></i></button>
                            <?php else: ?>
                            <span class="text-muted small"><i class="fas fa-check-circle text-success"></i> Closed<?= !empty($d['closed_date']) ? ' ' . date('d M Y', strtotime($d['closed_date'])) : '' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
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
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-gavel me-2"></i>Add Discipline Case</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="addCaseForm" onsubmit="event.preventDefault(); submitAddCase()">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Student *</label><select name="student_id" class="form-select" required>
                    <option value="">Select Student</option>
                    <?php
                    if ($studentsDb) {
                        $so = $studentsDb->query("SELECT id, CONCAT(first_name,' ',surname) as full_name FROM students ORDER BY first_name, surname");
                        if ($so) while ($row = $so->fetch_assoc()) echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['full_name']).'</option>';
                    }
                    ?>
                </select></div>
                <div class="mb-3"><label class="form-label">Case Type *</label><select name="case_type" class="form-select" required>
                    <option value="">Select Case Type</option>
                    <option value="Academic Misconduct">Academic Misconduct</option>
                    <option value="Behavioral">Behavioral</option>
                    <option value="Attendance">Attendance</option>
                    <option value="Other">Other</option>
                </select></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Describe the incident..."></textarea></div>
                <div class="mb-3"><label class="form-label">Severity *</label><select name="severity" class="form-select" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select></div>
                <div class="mb-3"><label class="form-label">Reported By</label><input type="text" name="reported_by" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['full_name'] ?? '') ?>"></div>
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
                <div class="mb-3"><label class="form-label">Status</label><select name="status" id="edit_case_status" class="form-select">
                    <option value="Open">Open</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Closed">Closed</option>
                    <option value="Appealed">Appealed</option>
                </select></div>
                <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" id="edit_case_notes" class="form-control" rows="3" placeholder="Add notes or resolution details..."></textarea></div>
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
function editCase(id, status, notes) {
    document.getElementById('edit_case_id').value = id;
    document.getElementById('edit_case_status').value = status;
    document.getElementById('edit_case_notes').value = notes || '';
    new bootstrap.Modal(document.getElementById('editCaseModal')).show();
}
function closeCase(id) {
    if (!confirm('Close this discipline case? This will mark it as Closed.')) return;
    var fd = new FormData();
    fd.append('action', 'close_case');
    fd.append('id', id);
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) { window.location.reload(); }
            else { alert('Error: ' + (d.error || 'Failed')); }
        })
        .catch(function(e) { alert('Error closing case'); });
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
