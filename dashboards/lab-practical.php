<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['lecturer', 'head', 'nursing', 'midwifery', 'lab']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'Lab Practical Sessions';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($conn) {
    @$conn->query("CREATE TABLE IF NOT EXISTS lab_practical_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_name VARCHAR(255) NOT NULL,
        subject VARCHAR(255) DEFAULT '',
        session_date DATE DEFAULT NULL,
        session_time TIME DEFAULT NULL,
        location VARCHAR(255) DEFAULT '',
        instructor VARCHAR(255) DEFAULT '',
        max_students INT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    @$conn->query("CREATE TABLE IF NOT EXISTS lab_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT DEFAULT NULL,
        student_name VARCHAR(255) DEFAULT '',
        student_id VARCHAR(100) DEFAULT '',
        session_date DATE DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'present',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    @$conn->query("CREATE TABLE IF NOT EXISTS lab_equipment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        equipment_name VARCHAR(255) DEFAULT '',
        quantity INT DEFAULT 0,
        condition_status VARCHAR(50) DEFAULT 'good',
        location VARCHAR(255) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

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
            $name = trim($_POST['session_name'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $date = !empty($_POST['date']) ? $_POST['date'] : null;
            $time = !empty($_POST['time']) ? $_POST['time'] : null;
            $location = trim($_POST['location'] ?? '');
            $instructor = trim($_POST['instructor'] ?? '');
            $max = (int)($_POST['max_students'] ?? 0);
            if (!$name) { echo json_encode(['success' => false, 'message' => 'Session name is required']); exit; }
            $stmt = $conn->prepare("INSERT INTO lab_practical_sessions (session_name, subject, session_date, session_time, location, instructor, max_students, status) VALUES (?,?,?,?,?,?,?,'scheduled')");
            if ($stmt) {
                $stmt->bind_param('ssssssi', $name, $subject, $date, $time, $location, $instructor, $max);
                $ok = $stmt->execute(); $stmt->close();
                echo json_encode(['success' => $ok, 'message' => $ok ? 'Session added' : 'Failed to add session']); exit;
            }
            echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']); exit;

        case 'update_session':
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['session_name'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $date = !empty($_POST['date']) ? $_POST['date'] : null;
            $time = !empty($_POST['time']) ? $_POST['time'] : null;
            $location = trim($_POST['location'] ?? '');
            $instructor = trim($_POST['instructor'] ?? '');
            $max = (int)($_POST['max_students'] ?? 0);
            $status = trim($_POST['status'] ?? 'scheduled');
            if (!$id || !$name) { echo json_encode(['success' => false, 'message' => 'Missing required fields']); exit; }
            $stmt = $conn->prepare("UPDATE lab_practical_sessions SET session_name=?, subject=?, session_date=?, session_time=?, location=?, instructor=?, max_students=?, status=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('ssssssisi', $name, $subject, $date, $time, $location, $instructor, $max, $status, $id);
                $ok = $stmt->execute(); $stmt->close();
                echo json_encode(['success' => $ok, 'message' => $ok ? 'Session updated' : 'Failed to update']); exit;
            }
            echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']); exit;

        case 'delete_session':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM lab_practical_sessions WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
                echo json_encode(['success' => $ok, 'message' => $ok ? 'Session deleted' : 'Failed to delete']); exit;
            }
            echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']); exit;

        case 'add_attendance':
            $sid = (int)($_POST['session_id'] ?? 0);
            $student = trim($_POST['student_name'] ?? '');
            $sidNum = trim($_POST['student_id'] ?? '');
            $date = !empty($_POST['session_date']) ? $_POST['session_date'] : null;
            $status = trim($_POST['status'] ?? 'present');
            $notes = trim($_POST['notes'] ?? '');
            if (!$student) { echo json_encode(['success' => false, 'message' => 'Student name is required']); exit; }
            $stmt = $conn->prepare("INSERT INTO lab_attendance (session_id, student_name, student_id, session_date, status, notes) VALUES (?,?,?,?,?,?)");
            if ($stmt) {
                $stmt->bind_param('isssss', $sid, $student, $sidNum, $date, $status, $notes);
                $ok = $stmt->execute(); $stmt->close();
                echo json_encode(['success' => $ok, 'message' => $ok ? 'Attendance recorded' : 'Failed']); exit;
            }
            echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']); exit;

        case 'delete_attendance':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM lab_attendance WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
                echo json_encode(['success' => $ok, 'message' => $ok ? 'Record deleted' : 'Failed']); exit;
            }
            echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']); exit;

        case 'search':
            $q = '%' . trim($_POST['query'] ?? '') . '%';
            $sessions = [];
            $stmt = $conn->prepare("SELECT * FROM lab_practical_sessions WHERE session_name LIKE ? OR subject LIKE ? OR instructor LIKE ? OR location LIKE ? ORDER BY session_date DESC LIMIT 100");
            if ($stmt) {
                $stmt->bind_param('ssss', $q, $q, $q, $q);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) { $row['_source'] = 'lab_practical_sessions'; $sessions[] = $row; }
                }
                $stmt->close();
            }
            echo json_encode(['success' => true, 'data' => $sessions]); exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
}

$sessions = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    foreach (['lab_practical_sessions', 'lab_skills_sessions', 'lab_skills_demonstrations', 'skills_lab_sessions'] as $tbl) {
        $r = @$db->query("SELECT * FROM $tbl ORDER BY session_date DESC LIMIT 100");
        if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) { $row['_source'] = $tbl; $sessions[] = $row; } break 2; }
    }
}

$attendance = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    $r = @$db->query("SELECT * FROM lab_attendance ORDER BY session_date DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $attendance[] = $row; break; }
}

$equipment = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    $r = @$db->query("SELECT * FROM lab_equipment ORDER BY equipment_name LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $equipment[] = $row; break; }
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
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-microscope"></i> Lab Practical Sessions</h1>
        <div class="d-flex gap-2">
            <input type="text" class="form-control" id="searchInput" placeholder="Search sessions..." style="width:250px" onkeyup="searchSessions()">
            <button class="btn btn-primary" onclick="openSessionModal()"><i class="fas fa-plus"></i> Add Session</button>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Practical Sessions</h6><h3 id="sessionCount"><?= count($sessions) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Attendance Records</h6><h3><?= count($attendance) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Equipment Items</h6><h3><?= count($equipment) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5>Upcoming & Recent Sessions</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Session</th><th>Module</th><th>Instructor</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="sessionsTableBody">
                                <?php foreach ($sessions as $s): ?>
                                <tr data-id="<?= (int)($s['id'] ?? 0) ?>">
                                    <td><?= htmlspecialchars($s['session_name'] ?? $s['title'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['module'] ?? $s['course'] ?? $s['subject'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['instructor'] ?? $s['facilitator'] ?? '-') ?></td>
                                    <td><?= $s['session_date'] ?? $s['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($s['status'] ?? 'scheduled') === 'completed' ? 'success' : 'primary' ?>"><?= $s['status'] ?? 'scheduled' ?></span></td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-xs btn-outline-primary" title="Edit" onclick="editSession(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-xs btn-outline-danger" title="Delete" onclick="deleteSession(<?= (int)($s['id'] ?? 0) ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sessions)): ?><tr id="emptyRow"><td colspan="6" class="text-center">No sessions recorded</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Attendance Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Attendance Records</h5>
                    <button class="btn btn-sm btn-success text-white" onclick="openAttendanceModal()"><i class="fas fa-plus"></i> Record Attendance</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Student</th><th>ID</th><th>Session Date</th><th>Status</th><th>Notes</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($attendance as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['student_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['student_id'] ?? '-') ?></td>
                                    <td><?= $a['session_date'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($a['status'] ?? 'present') === 'present' ? 'success' : ($a['status'] === 'absent' ? 'danger' : 'warning') ?>"><?= $a['status'] ?? 'present' ?></span></td>
                                    <td><?= htmlspecialchars($a['notes'] ?? '') ?></td>
                                    <td><button class="btn btn-xs btn-outline-danger" onclick="deleteAttendance(<?= (int)($a['id'] ?? 0) ?>)"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($attendance)): ?><tr><td colspan="6" class="text-center">No attendance records</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Lab Equipment</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Equipment</th><th>Qty</th><th>Condition</th><th>Location</th></tr></thead>
                            <tbody>
                                <?php foreach ($equipment as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['equipment_name'] ?? $e['name'] ?? '-') ?></td>
                                    <td><?= $e['quantity'] ?? $e['qty'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($e['condition_status'] ?? $e['status'] ?? 'good') === 'good' ? 'success' : 'warning' ?>"><?= $e['condition_status'] ?? $e['status'] ?? 'good' ?></span></td>
                                    <td><?= htmlspecialchars($e['location'] ?? $e['room'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($equipment)): ?><tr><td colspan="4" class="text-center">No equipment registered</td></tr><?php endif; ?>
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
  <div class="modal-header"><h5 class="modal-title" id="sessionModalTitle">Add Lab Session</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="sessionId">
    <div class="mb-3"><label class="form-label">Session Name *</label><input type="text" class="form-control" id="sessionName"></div>
    <div class="mb-3"><label class="form-label">Subject/Module</label><input type="text" class="form-control" id="sessionSubject"></div>
    <div class="row mb-3">
      <div class="col-md-6"><label class="form-label">Date</label><input type="date" class="form-control" id="sessionDate"></div>
      <div class="col-md-6"><label class="form-label">Time</label><input type="time" class="form-control" id="sessionTime"></div>
    </div>
    <div class="mb-3"><label class="form-label">Location</label><input type="text" class="form-control" id="sessionLocation"></div>
    <div class="mb-3"><label class="form-label">Instructor</label><input type="text" class="form-control" id="sessionInstructor"></div>
    <div class="row mb-3">
      <div class="col-md-6"><label class="form-label">Max Students</label><input type="number" class="form-control" id="sessionMaxStudents" min="0" value="0"></div>
      <div class="col-md-6"><label class="form-label">Status</label>
        <select class="form-control" id="sessionStatus">
          <option value="scheduled">Scheduled</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button class="btn btn-primary" onclick="saveSession()">Save</button>
  </div>
</div></div></div>

<!-- Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Record Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">Session</label><select class="form-control" id="attSessionId"><option value="0">-- Select --</option><?php foreach ($sessions as $ss): ?><option value="<?= (int)($ss['id'] ?? 0) ?>"><?= htmlspecialchars($ss['session_name'] ?? $ss['title'] ?? 'Session') ?> (<?= $ss['session_date'] ?? '' ?>)</option><?php endforeach; ?></select></div>
    <div class="mb-3"><label class="form-label">Student Name *</label><input type="text" class="form-control" id="attStudentName"></div>
    <div class="mb-3"><label class="form-label">Student ID</label><input type="text" class="form-control" id="attStudentId"></div>
    <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" id="attDate"></div>
    <div class="mb-3"><label class="form-label">Status</label>
      <select class="form-control" id="attStatus"><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="excused">Excused</option></select>
    </div>
    <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" id="attNotes" rows="2"></textarea></div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button class="btn btn-success" onclick="saveAttendance()">Save</button>
  </div>
</div></div></div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
const CSRF = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
const AJAX_URL = 'lab-practical.php?ajax=1';

function openSessionModal(data) {
    document.getElementById('sessionId').value = data ? (data.id || '') : '';
    document.getElementById('sessionName').value = data ? (data.session_name || data.title || '') : '';
    document.getElementById('sessionSubject').value = data ? (data.subject || data.module || data.course || '') : '';
    document.getElementById('sessionDate').value = data ? (data.session_date || '') : '';
    document.getElementById('sessionTime').value = data ? (data.session_time || '') : '';
    document.getElementById('sessionLocation').value = data ? (data.location || '') : '';
    document.getElementById('sessionInstructor').value = data ? (data.instructor || data.facilitator || '') : '';
    document.getElementById('sessionMaxStudents').value = data ? (data.max_students || 0) : 0;
    document.getElementById('sessionStatus').value = data ? (data.status || 'scheduled') : 'scheduled';
    document.getElementById('sessionModalTitle').textContent = data ? 'Edit Lab Session' : 'Add Lab Session';
    new bootstrap.Modal(document.getElementById('sessionModal')).show();
}

function editSession(row) { openSessionModal(row); }

function saveSession() {
    const id = document.getElementById('sessionId').value;
    const name = document.getElementById('sessionName').value.trim();
    if (!name) { alert('Session name is required'); return; }
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('ajax_action', id ? 'update_session' : 'add_session');
    if (id) fd.append('id', id);
    fd.append('session_name', name);
    fd.append('subject', document.getElementById('sessionSubject').value);
    fd.append('date', document.getElementById('sessionDate').value);
    fd.append('time', document.getElementById('sessionTime').value);
    fd.append('location', document.getElementById('sessionLocation').value);
    fd.append('instructor', document.getElementById('sessionInstructor').value);
    fd.append('max_students', document.getElementById('sessionMaxStudents').value);
    fd.append('status', document.getElementById('sessionStatus').value);
    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert(d.message); });
}

function deleteSession(id) {
    if (!id || !confirm('Delete this session?')) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('ajax_action', 'delete_session');
    fd.append('id', id);
    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert(d.message); });
}

function openAttendanceModal() {
    document.getElementById('attSessionId').value = '0';
    document.getElementById('attStudentName').value = '';
    document.getElementById('attStudentId').value = '';
    document.getElementById('attDate').value = '';
    document.getElementById('attStatus').value = 'present';
    document.getElementById('attNotes').value = '';
    new bootstrap.Modal(document.getElementById('attendanceModal')).show();
}

function saveAttendance() {
    const name = document.getElementById('attStudentName').value.trim();
    if (!name) { alert('Student name is required'); return; }
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('ajax_action', 'add_attendance');
    fd.append('session_id', document.getElementById('attSessionId').value);
    fd.append('student_name', name);
    fd.append('student_id', document.getElementById('attStudentId').value);
    fd.append('session_date', document.getElementById('attDate').value);
    fd.append('status', document.getElementById('attStatus').value);
    fd.append('notes', document.getElementById('attNotes').value);
    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert(d.message); });
}

function deleteAttendance(id) {
    if (!id || !confirm('Delete this record?')) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('ajax_action', 'delete_attendance');
    fd.append('id', id);
    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert(d.message); });
}

let searchTimer;
function searchSessions() {
    clearTimeout(searchTimer);
    const q = document.getElementById('searchInput').value.trim();
    if (!q) { location.reload(); return; }
    searchTimer = setTimeout(() => {
        const fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('ajax_action', 'search');
        fd.append('query', q);
        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (!d.success) { alert(d.message); return; }
                const tbody = document.getElementById('sessionsTableBody');
                const rows = d.data.map(s => {
                    const src = s._source || 'lab_practical_sessions';
                    const name = s.session_name || s.title || '-';
                    const mod = s.module || s.course || s.subject || '-';
                    const inst = s.instructor || s.facilitator || '-';
                    const date = s.session_date || s.created_at || '-';
                    const st = s.status || 'scheduled';
                    const cls = st === 'completed' ? 'success' : 'primary';
                    return `<tr data-id="${s.id||0}">
                        <td>${name}</td><td>${mod}</td><td>${inst}</td><td>${date}</td>
                        <td><span class="badge bg-${cls}">${st}</span></td>
                        <td class="text-nowrap">
                            <button class="btn btn-xs btn-outline-primary" title="Edit" onclick='editSession(${JSON.stringify(s).replace(/'/g,"&#39;")})'><i class="fas fa-edit"></i></button>
                            <button class="btn btn-xs btn-outline-danger" title="Delete" onclick="deleteSession(${s.id||0})"><i class="fas fa-trash"></i></button>
                        </td></tr>`;
                }).join('');
                tbody.innerHTML = rows || '<tr><td colspan="6" class="text-center">No results found</td></tr>';
                document.getElementById('sessionCount').textContent = d.data.length;
            });
    }, 350);
}
</script>
</body>
</html>
