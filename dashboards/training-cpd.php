<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['hr','manager','director','principal','head']);
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();

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
    if ($action === 'add_training' && $conn) {
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['training_type'] ?? '');
        $provider = trim($_POST['provider'] ?? '');
        $start = trim($_POST['start_date'] ?? '');
        $end = trim($_POST['end_date'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $max = (int)($_POST['max_participants'] ?? 50);
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO trainings (name, training_type, provider, start_date, end_date, description, max_participants, status) VALUES (?,?,?,?,?,?,?,'Planned')");
            $stmt->bind_param('ssssssi', $name, $type, $provider, $start, $end, $desc, $max);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'Training name required'; }
    } elseif ($action === 'enroll_staff' && $conn) {
        $training_id = (int)($_POST['training_id'] ?? 0);
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        if ($training_id && $staff_id) {
            $stmt = $conn->prepare("INSERT INTO employee_training (training_id, staff_id, status) VALUES (?,?,'Enrolled')");
            $stmt->bind_param('ii', $training_id, $staff_id);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'Training and staff required'; }
    } elseif ($action === 'update_enrollment' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if ($id && $status) {
            $stmt = $conn->prepare("UPDATE employee_training SET status=?, completion_date=IF(?='Completed',CURDATE(),completion_date) WHERE id=?");
            $stmt->bind_param('ssi', $status, $status, $id);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'ID and status required'; }
    } elseif ($action === 'delete_training' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $conn->query("DELETE FROM employee_training WHERE training_id=$id");
            $stmt = $conn->prepare("DELETE FROM trainings WHERE id=?");
            $stmt->bind_param('i', $id);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'ID required'; }
    }
    echo json_encode($response); exit;
}

$pageTitle = 'Training & CPD';
$total = 0; $enrolled = 0; $completed = 0; $upcoming = 0; $records = [];
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM trainings");
    if ($r) $total = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM employee_training WHERE status='Enrolled'");
    if ($r) $enrolled = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM employee_training WHERE status='Completed'");
    if ($r) $completed = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM trainings WHERE start_date > CURDATE()");
    if ($r) $upcoming = (int)$r->fetch_assoc()['c'];
    $q = $conn->query("SELECT t.name training_name, s.full_name staff_name, t.start_date, t.end_date, et.status FROM employee_training et JOIN trainings t ON et.training_id=t.id LEFT JOIN staff s ON et.staff_id=s.id ORDER BY t.start_date DESC LIMIT 50");
    if ($q) $records = $q->fetch_all(MYSQLI_ASSOC);
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
<main class="main" style="margin-left:270px;padding:32px;">
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4">
<h4 class="fw-bold mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Training & CPD</h4> <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button>
<span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>
<div class="row g-3 mb-4">
<?php $c=[['Total Trainings',$total,'primary','book-open'],['Enrolled',$enrolled,'success','user-plus'],['Completed',$completed,'info','check-circle'],['Upcoming',$upcoming,'warning','calendar-alt']]; foreach($c as $s): ?>
<div class="col-md-3">
<div class="stat-card <?= $s[2] ?>">
<div class="stat-icon"><i class="fas fa-<?= $s[3] ?>"></i></div>
<div class="stat-content"><h3><?= number_format($s[1]) ?></h3><p><?= $s[0] ?></p></div>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="content-section">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Training Records</h5>
        <div><button class="btn btn-primary btn-sm" onclick="showAddTraining()"><i class="fas fa-plus me-1"></i>Add Training</button> <button class="btn btn-success btn-sm" onclick="showEnrollStaff()"><i class="fas fa-user-plus me-1"></i>Enroll Staff</button></div>
    </div>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchDBCO" type="text" placeholder="Search..." onkeyup="filterTable('srchDBCO','tblDBCO')"></div>
<div class="table-responsive">
        <table id="tblDBCO" class="table table-striped table-hover align-middle">
            <thead class="table-light"><tr><th>Training</th><th>Staff</th><th>Provider</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if(empty($records)): ?>
            <tr><td colspan="7" class="text-center text-muted py-3">No training records found.</td></tr>
            <?php else: foreach($records as $r):
                $bc=$r['status']==='Completed'?'bg-success':($r['status']==='Enrolled'?'bg-primary':'bg-secondary');
            ?>
            <tr><td><?= htmlspecialchars($r['training_name']??'-') ?></td><td><?= htmlspecialchars($r['staff_name']??'-') ?></td><td><?= htmlspecialchars($r['provider']??'-') ?></td><td><?= htmlspecialchars($r['start_date']??'-') ?></td><td><?= htmlspecialchars($r['end_date']??'-') ?></td><td><span class="badge <?= $bc ?>"><?= htmlspecialchars($r['status']??'-') ?></span></td>
            <td><button class="btn btn-sm btn-outline-warning" onclick="updateEnrollment(<?= $r['id'] ?? 0 ?>, '<?= htmlspecialchars($r['status']??'', ENT_QUOTES) ?>')"><i class="fas fa-sync"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="deleteTraining(<?= $r['training_id'] ?? 0 ?>)"><i class="fas fa-trash"></i></button></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</main>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<!-- Add Training Modal -->
<div class="modal fade" id="addTrainingModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-chalkboard-teacher me-2"></i>Add Training</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="addTrainingForm" onsubmit="event.preventDefault(); submitAddTraining()">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Training Name *</label><input type="text" name="name" class="form-control" required></div>
                <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Type</label><input type="text" name="training_type" class="form-control"></div><div class="col-6"><label class="form-label">Provider</label><input type="text" name="provider" class="form-control"></div></div>
                <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control"></div><div class="col-6"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control"></div></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                <div class="mb-3"><label class="form-label">Max Participants</label><input type="number" name="max_participants" class="form-control" value="50"></div>
                <input type="hidden" name="action" value="add_training">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Enroll Staff Modal -->
<div class="modal fade" id="enrollStaffModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Enroll Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="enrollStaffForm" onsubmit="event.preventDefault(); submitEnrollStaff()">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Training *</label><select name="training_id" class="form-select" required><option value="">Select Training</option><?php if ($conn) { $to = $conn->query("SELECT id, name FROM trainings ORDER BY name"); if ($to) while ($row = $to->fetch_assoc()) echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['name']).'</option>'; } ?></select></div>
                <div class="mb-3"><label class="form-label">Staff *</label><select name="staff_id" class="form-select" required><option value="">Select Staff</option><?php if ($conn) { $so = $conn->query("SELECT id, full_name FROM staff WHERE status='Active' ORDER BY full_name"); if ($so) while ($row = $so->fetch_assoc()) echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['full_name']).'</option>'; } ?></select></div>
                <input type="hidden" name="action" value="enroll_staff">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-user-plus me-1"></i>Enroll</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Update Enrollment Modal -->
<div class="modal fade" id="updateEnrollModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-sync me-2"></i>Update Enrollment Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="updateEnrollForm" onsubmit="event.preventDefault(); submitUpdateEnroll()">
            <div class="modal-body">
                <input type="hidden" name="id" id="update_enroll_id">
                <div class="mb-3"><label class="form-label">Status</label><select name="status" id="update_enroll_status" class="form-select"><option value="Enrolled">Enrolled</option><option value="Completed">Completed</option><option value="Dropped">Dropped</option></select></div>
                <input type="hidden" name="action" value="update_enrollment">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function showAddTraining() { new bootstrap.Modal(document.getElementById('addTrainingModal')).show(); }
function showEnrollStaff() { new bootstrap.Modal(document.getElementById('enrollStaffModal')).show(); }
function updateEnrollment(id, currentStatus) {
    document.getElementById('update_enroll_id').value = id;
    document.getElementById('update_enroll_status').value = currentStatus;
    new bootstrap.Modal(document.getElementById('updateEnrollModal')).show();
}
function deleteTraining(id) {
    if (!confirm('Delete this training and all enrollments?')) return;
    var fd = new FormData(); fd.append('action', 'delete_training'); fd.append('id', id);
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
}
function submitAddTraining() {
    var fd = new FormData(document.getElementById('addTrainingForm'));
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
}
function submitEnrollStaff() {
    var fd = new FormData(document.getElementById('enrollStaffForm'));
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
}
function submitUpdateEnroll() {
    var fd = new FormData(document.getElementById('updateEnrollForm'));
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
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
