<?php
$pageTitle = 'Staff Attendance';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr','manager','director','principal','admin']);
$conn = $ctx['staff'];
require_once __DIR__ . '/../includes/config_enhanced.php';

// Handle check-in/check-out actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => 'Unknown action'];
    $action = $_POST['action'];
    $today = date('Y-m-d');
    $now = date('H:i:s');
    if ($action === 'check_in' && $conn) {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        if ($staff_id) {
            $status = 'Present';
            $check = $conn->query("SELECT id FROM attendance WHERE staff_id=$staff_id AND date='$today'");
            if ($check && $check->num_rows > 0) {
                $response['error'] = 'Already checked in today';
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance (staff_id, date, check_in, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('isss', $staff_id, $today, $now, $status);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else {
            $response['error'] = 'Staff ID required';
        }
    } elseif ($action === 'check_out' && $conn) {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        if ($staff_id) {
            $stmt = $conn->prepare("UPDATE attendance SET check_out=? WHERE staff_id=? AND date=? AND check_out IS NULL");
            $stmt->bind_param('sis', $now, $staff_id, $today);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
            } else {
                $response['error'] = 'No active check-in found for today';
            }
            $stmt->close();
        } else {
            $response['error'] = 'Staff ID required';
        }
    } elseif ($action === 'mark_absent' && $conn) {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        if ($staff_id) {
            $check = $conn->query("SELECT id FROM attendance WHERE staff_id=$staff_id AND date='$today'");
            if ($check && $check->num_rows > 0) {
                $response['error'] = 'Attendance already recorded for today';
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance (staff_id, date, status) VALUES (?, ?, 'Absent')");
                $stmt->bind_param('is', $staff_id, $today);
                $response['success'] = $stmt->execute();
                $response['error'] = $stmt->error;
                $stmt->close();
            }
        } else {
            $response['error'] = 'Staff ID required';
        }
    }
    echo json_encode($response);
    exit;
}

$presentToday = 0; $absent = 0; $onLeave = 0; $late = 0;
$attendance = [];

if ($conn) {
    $today = date('Y-m-d');
    $r1 = $conn->query("SELECT COUNT(*) c FROM attendance WHERE date='$today' AND status='Present'");
    if ($r1) $presentToday = (int)$r1->fetch_assoc()['c'];
    $r2 = $conn->query("SELECT COUNT(*) c FROM attendance WHERE date='$today' AND status='Absent'");
    if ($r2) $absent = (int)$r2->fetch_assoc()['c'];
    $r3 = $conn->query("SELECT COUNT(*) c FROM attendance WHERE date='$today' AND status='On Leave'");
    if ($r3) $onLeave = (int)$r3->fetch_assoc()['c'];
    $r4 = $conn->query("SELECT COUNT(*) c FROM attendance WHERE date='$today' AND status='Late'");
    if ($r4) $late = (int)$r4->fetch_assoc()['c'];
    $a = $conn->query("SELECT a.*, s.full_name staff_name FROM attendance a LEFT JOIN staff s ON a.staff_id=s.id WHERE a.date='$today' ORDER BY a.check_in ASC");
    if ($a) $attendance = $a->fetch_all(MYSQLI_ASSOC);
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
        <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-check me-2"></i>Staff Attendance</h4> <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-content"><h3><?= number_format($presentToday) ?></h3><p>Present Today</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fas fa-user-times"></i></div>
                <div class="stat-content"><h3><?= number_format($absent) ?></h3><p>Absent</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-plane"></i></div>
                <div class="stat-content"><h3><?= number_format($onLeave) ?></h3><p>On Leave</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-content"><h3><?= number_format($late) ?></h3><p>Late</p></div>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="content-section">
                <h5 class="fw-bold mb-3"><i class="fas fa-sign-in-alt me-2"></i>Quick Actions</h5>
                <div class="mb-3">
                    <label class="form-label">Select Staff</label>
                    <select id="attendanceStaff" class="form-select">
                        <option value="">Choose staff...</option>
                        <?php
                        $allStaff = [];
                        if ($conn) {
                            $as = $conn->query("SELECT id, full_name FROM staff WHERE status='Active' ORDER BY full_name");
                            if ($as) while ($row = $as->fetch_assoc()) echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['full_name']).'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="doCheckIn()"><i class="fas fa-sign-in-alt me-1"></i>Check In</button>
                    <button class="btn btn-warning" onclick="doCheckOut()"><i class="fas fa-sign-out-alt me-1"></i>Check Out</button>
                    <button class="btn btn-secondary" onclick="doMarkAbsent()"><i class="fas fa-user-times me-1"></i>Mark Absent</button>
                </div>
                <div id="attendanceMsg" class="mt-2 small"></div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="content-section">
                <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Today's Attendance (<?= date('d M Y') ?>)</h5>
                <?php if (empty($attendance)): ?>
                <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No attendance records for today.</p></div>
                <?php else: ?>
                <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchNUJK" type="text" placeholder="Search..." onkeyup="filterTable('srchNUJK','tblNUJK')"></div>
<div class="table-responsive">
                    <table id="tblNUJK" class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr><th>Staff Name</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance as $at): ?>
                            <tr>
                                <td><?= htmlspecialchars($at['staff_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($at['date']) ?></td>
                                <td><?= htmlspecialchars($at['check_in'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($at['check_out'] ?? '-') ?></td>
                                <td>
                                    <?php $sc = $at['status'] === 'Present' ? 'success' : ($at['status'] === 'Absent' ? 'danger' : ($at['status'] === 'Late' ? 'warning text-dark' : ($at['status'] === 'On Leave' ? 'info' : 'secondary'))); ?>
                                    <span class="badge bg-<?= $sc ?>"><?= htmlspecialchars($at['status']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
function doCheckIn() {
    var sid = document.getElementById('attendanceStaff').value;
    if (!sid) { document.getElementById('attendanceMsg').innerHTML = '<div class="text-danger">Select a staff member</div>'; return; }
    var fd = new FormData();
    fd.append('action', 'check_in');
    fd.append('staff_id', sid);
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('attendanceMsg').innerHTML = d.success ? '<div class="text-success">Checked in successfully!</div>' : '<div class="text-danger">' + (d.error || 'Failed') + '</div>';
            if (d.success) setTimeout(function() { window.location.reload(); }, 800);
        })
        .catch(function(e) { document.getElementById('attendanceMsg').innerHTML = '<div class="text-danger">Error</div>'; });
}
function doCheckOut() {
    var sid = document.getElementById('attendanceStaff').value;
    if (!sid) { document.getElementById('attendanceMsg').innerHTML = '<div class="text-danger">Select a staff member</div>'; return; }
    var fd = new FormData();
    fd.append('action', 'check_out');
    fd.append('staff_id', sid);
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('attendanceMsg').innerHTML = d.success ? '<div class="text-success">Checked out successfully!</div>' : '<div class="text-danger">' + (d.error || 'Failed') + '</div>';
            if (d.success) setTimeout(function() { window.location.reload(); }, 800);
        })
        .catch(function(e) { document.getElementById('attendanceMsg').innerHTML = '<div class="text-danger">Error</div>'; });
}
function doMarkAbsent() {
    var sid = document.getElementById('attendanceStaff').value;
    if (!sid) { document.getElementById('attendanceMsg').innerHTML = '<div class="text-danger">Select a staff member</div>'; return; }
    if (!confirm('Mark this staff as absent?')) return;
    var fd = new FormData();
    fd.append('action', 'mark_absent');
    fd.append('staff_id', sid);
    fd.append('csrf_token', window.CSRF_TOKEN);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('attendanceMsg').innerHTML = d.success ? '<div class="text-success">Marked as absent</div>' : '<div class="text-danger">' + (d.error || 'Failed') + '</div>';
            if (d.success) setTimeout(function() { window.location.reload(); }, 800);
        })
        .catch(function(e) { document.getElementById('attendanceMsg').innerHTML = '<div class="text-danger">Error</div>'; });
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
</body>
</html>
