<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar', 'academics', 'secretary']);
$user = $ctx['user'];
$staffConn = $ctx['staff'];
$studentsConn = $ctx['students'];
$conn = $studentsConn ?: $staffConn;
$pageTitle = 'Course Registration';

// ── AJAX CRUD Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'] ?? $_GET['ajax'] ?? '';
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!empty($csrf) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $csrf)) {
        // valid
    } elseif ($action !== 'search') {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    if (!$conn) { echo json_encode(['success' => false, 'message' => 'Database unavailable']); exit; }

    switch ($action) {
        case 'add_registration':
            $sid = (int)($_POST['student_id'] ?? 0);
            $snum = trim($_POST['student_number'] ?? '');
            $ccode = trim($_POST['course_code'] ?? '');
            $cname = trim($_POST['course_name'] ?? '');
            $sem = trim($_POST['semester'] ?? '');
            $yr = trim($_POST['academic_year'] ?? '');
            if (!$snum || !$cname) { echo json_encode(['success' => false, 'message' => 'Student number and course name required']); exit; }
            if (!$sid) { $sr = $conn->query("SELECT id FROM students WHERE student_number='$snum' LIMIT 1"); if ($sr && $sr->num_rows) $sid = (int)$sr->fetch_assoc()['id']; }
            $stmt = $conn->prepare("INSERT INTO course_registrations (student_id, student_number, course_code, course_name, semester, academic_year, status, created_at) VALUES (?,?,?,?,?,?,'Registered',NOW())");
            if ($stmt) { $stmt->bind_param('isssss', $sid, $snum, $ccode, $cname, $sem, $yr); $ok = $stmt->execute(); $stmt->close(); echo json_encode(['success' => $ok, 'message' => $ok ? 'Registration added' : 'Failed']); }
            else echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
            exit;

        case 'update_registration':
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'Registered');
            $grade = trim($_POST['grade'] ?? '');
            $marks = $_POST['marks'] !== '' ? (float)$_POST['marks'] : null;
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $allowed = ['Registered','Completed','Dropped','Failed'];
            if (!in_array($status, $allowed)) $status = 'Registered';
            if ($marks !== null) {
                $stmt = $conn->prepare("UPDATE course_registrations SET status=?, grade=?, marks=? WHERE id=?");
                $stmt->bind_param('ssdi', $status, $grade, $marks, $id);
            } else {
                $stmt = $conn->prepare("UPDATE course_registrations SET status=?, grade=? WHERE id=?");
                $stmt->bind_param('ssi', $status, $grade, $id);
            }
            $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Failed']);
            exit;

        case 'delete_registration':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM course_registrations WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']);
            exit;

        case 'search':
            $q = '%' . trim($_POST['q'] ?? '') . '%';
            $registrations = [];
            $stmt = $conn->prepare("SELECT cr.*, CONCAT(s.first_name,' ',s.surname) student_name, cc.course_title course_name FROM course_registrations cr LEFT JOIN students s ON cr.student_id=s.id LEFT JOIN igangaschool_staffs.academic_course_catalog cc ON cr.course_code=cc.course_code WHERE cr.student_number LIKE ? OR cr.course_name LIKE ? OR s.first_name LIKE ? OR s.surname LIKE ? ORDER BY cr.created_at DESC LIMIT 50");
            if ($stmt) { $stmt->bind_param('ssss', $q, $q, $q, $q); $stmt->execute(); $r = $stmt->get_result(); while ($row = $r->fetch_assoc()) $registrations[] = $row; $stmt->close(); }
            echo json_encode(['success' => true, 'data' => $registrations]);
            exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

$total = 0; $thisSemester = 0; $pending = 0; $completed = 0;
$registrations = [];
if ($conn) {
    $qr = $conn->query("SELECT COUNT(*) c FROM course_registrations"); if ($qr) $total = (int)$qr->fetch_assoc()['c'];
    $qr = $conn->query("SELECT COUNT(*) c FROM course_registrations WHERE status='Registered'"); if ($qr) $pending = (int)$qr->fetch_assoc()['c'];
    $qr = $conn->query("SELECT COUNT(*) c FROM course_registrations WHERE status='Completed'"); if ($qr) $completed = (int)$qr->fetch_assoc()['c'];
    $qr = $conn->query("SELECT COUNT(*) c FROM course_registrations WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)"); if ($qr) $thisSemester = (int)$qr->fetch_assoc()['c'];
    $r = $conn->query("SELECT cr.*, CONCAT(s.first_name,' ',s.surname) student_name, cc.course_title course_name FROM course_registrations cr LEFT JOIN students s ON cr.student_id=s.id LEFT JOIN igangaschool_staffs.academic_course_catalog cc ON cr.course_code=cc.course_code ORDER BY cr.created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $registrations[] = $row;
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-user-plus me-2"></i>Course Registration</h4>
    <div>
      <input type="text" id="searchBox" class="form-control d-inline-block" style="width:220px" placeholder="Search student or course..." onkeyup="doSearch()">
      <button class="btn btn-primary btn-sm" onclick="openModal()"><i class="fas fa-plus me-1"></i>New Registration</button>
    </div>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clipboard-list"></i></div><div class="stat-content"><h3><?= $total ?></h3><p>Total Registrations</p></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-calendar-alt"></i></div><div class="stat-content"><h3><?= $thisSemester ?></h3><p>This Semester</p></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-hourglass-half"></i></div><div class="stat-content"><h3><?= $pending ?></h3><p>Pending</p></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $completed ?></h3><p>Completed</p></div></div></div>
  </div>
  <div class="content-section">
    <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Registration Records</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark"><tr><th>Student Name</th><th>Student #</th><th>Course</th><th>Semester</th><th>Reg Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="regTable">
        <?php if (empty($registrations)): ?><tr><td colspan="7" class="text-muted text-center py-3">No registrations found.</td></tr>
        <?php else: foreach ($registrations as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['student_name'] ?? 'Unknown') ?></strong></td>
          <td><?= htmlspecialchars($r['student_number'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['course_name'] ?? $r['course_code']) ?></td>
          <td><?= htmlspecialchars($r['semester'] ?? '-') ?></td>
          <td><?= isset($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '-' ?></td>
          <td><span class="badge <?= $r['status']==='Completed'?'bg-success':($r['status']==='Registered'?'bg-primary':'bg-secondary') ?>"><?= htmlspecialchars($r['status']) ?></span></td>
          <td>
            <button class="btn btn-xs btn-outline-primary" onclick='editReg(<?=json_encode($r)?>)'><i class="fas fa-edit"></i></button>
            <button class="btn btn-xs btn-outline-danger" onclick="deleteReg(<?=$r['id']?>)"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="regModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="regModalTitle">New Registration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="regId">
    <div class="mb-3"><label class="form-label">Student Number *</label><input type="text" class="form-control" id="regSnum" required></div>
    <div class="mb-3"><label class="form-label">Course Code</label><input type="text" class="form-control" id="regCcode"></div>
    <div class="mb-3"><label class="form-label">Course Name *</label><input type="text" class="form-control" id="regCname" required></div>
    <div class="mb-3"><label class="form-label">Semester</label><input type="text" class="form-control" id="regSem" value="<?= htmlspecialchars(($_GET['semester'] ?? 'Semester ' . ceil(date('n') / 6))) ?>"></div>
    <div class="mb-3"><label class="form-label">Academic Year</label><input type="text" class="form-control" id="regYear" value="<?= date('Y') ?>"></div>
    <div class="mb-3" id="editFields" style="display:none">
      <label class="form-label">Status</label>
      <select class="form-control" id="regStatus"><option>Registered</option><option>Completed</option><option>Dropped</option><option>Failed</option></select>
      <label class="form-label mt-2">Grade</label><input type="text" class="form-control" id="regGrade" placeholder="e.g. A">
      <label class="form-label mt-2">Marks</label><input type="number" class="form-control" id="regMarks" step="0.01">
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="saveReg()">Save</button></div>
</div></div></div>

<script>
const CSRF = '<?= $_SESSION['csrf_token'] ?>';
function openModal() {
    document.getElementById('regId').value = '';
    document.getElementById('regSnum').value = '';
    document.getElementById('regCcode').value = '';
    document.getElementById('regCname').value = '';
    document.getElementById('editFields').style.display = 'none';
    document.getElementById('regModalTitle').textContent = 'New Registration';
    new bootstrap.Modal(document.getElementById('regModal')).show();
}
function editReg(r) {
    document.getElementById('regId').value = r.id;
    document.getElementById('regSnum').value = r.student_number || '';
    document.getElementById('regCcode').value = r.course_code || '';
    document.getElementById('regCname').value = r.course_name || '';
    document.getElementById('regSem').value = r.semester || '';
    document.getElementById('regYear').value = r.academic_year || '';
    document.getElementById('regStatus').value = r.status || 'Registered';
    document.getElementById('regGrade').value = r.grade || '';
    document.getElementById('regMarks').value = r.marks || '';
    document.getElementById('editFields').style.display = 'block';
    document.getElementById('regModalTitle').textContent = 'Edit Registration';
    new bootstrap.Modal(document.getElementById('regModal')).show();
}
function saveReg() {
    const id = document.getElementById('regId').value;
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    if (id) {
        fd.append('ajax_action', 'update_registration');
        fd.append('id', id);
        fd.append('status', document.getElementById('regStatus').value);
        fd.append('grade', document.getElementById('regGrade').value);
        fd.append('marks', document.getElementById('regMarks').value);
    } else {
        fd.append('ajax_action', 'add_registration');
        fd.append('student_number', document.getElementById('regSnum').value);
        fd.append('course_code', document.getElementById('regCcode').value);
        fd.append('course_name', document.getElementById('regCname').value);
        fd.append('semester', document.getElementById('regSem').value);
        fd.append('academic_year', document.getElementById('regYear').value);
    }
    fetch('course-registration.php?ajax=1', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
function deleteReg(id) {
    if (!confirm('Delete this registration?')) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('ajax_action', 'delete_registration');
    fd.append('id', id);
    fetch('course-registration.php?ajax=1', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}
let searchTimer;
function doSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const q = document.getElementById('searchBox').value;
        if (q.length < 2) { location.reload(); return; }
        const fd = new FormData();
        fd.append('ajax_action', 'search');
        fd.append('q', q);
        fetch('course-registration.php?ajax=1', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (!d.success) return;
                const tbody = document.getElementById('regTable');
                if (!d.data.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-center">No results</td></tr>'; return; }
                tbody.innerHTML = d.data.map(r => `<tr>
                    <td><strong>${r.student_name||'Unknown'}</strong></td>
                    <td>${r.student_number||'-'}</td>
                    <td>${r.course_name||r.course_code||'-'}</td>
                    <td>${r.semester||'-'}</td>
                    <td>${r.created_at||'-'}</td>
                    <td><span class="badge bg-${r.status==='Completed'?'success':r.status==='Registered'?'primary':'secondary'}">${r.status}</span></td>
                    <td><button class="btn btn-xs btn-outline-primary" onclick='editReg(${JSON.stringify(r)})'><i class="fas fa-edit"></i></button> <button class="btn btn-xs btn-outline-danger" onclick="deleteReg(${r.id})"><i class="fas fa-trash"></i></button></td>
                </tr>`).join('');
            });
    }, 400);
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
