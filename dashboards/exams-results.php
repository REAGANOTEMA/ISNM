<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['academics','registrar','director','lecturer','head']);
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$staffConn = $ctx['staff'];
$studentsConn = $ctx['students'];
$conn = $staffConn;
$pageTitle = 'Exams & Results';
$uid = $_SESSION['user_id'] ?? 0;
$students_db_name = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

// â”€â”€ AJAX endpoint for exam student list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (isset($_GET['ajax']) && $_GET['ajax'] === 'exam_students' && isset($_GET['exam'])) {
    header('Content-Type: application/json');
    $examNumber = trim($_GET['exam']);
    $data = [];
    if ($conn && $examNumber) {
        $stmt = $conn->prepare("SELECT er.id, er.student_id, er.continuous_assessment_marks, er.final_exam_marks, er.marks_obtained, er.grade, CONCAT(s.first_name,' ',s.surname) full_name, s.index_number, s.student_number FROM examination_records er JOIN {$students_db_name}.students s ON er.student_id=s.id WHERE er.exam_number=? ORDER BY s.surname, s.first_name");
        if ($stmt) { $stmt->bind_param('s', $examNumber); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $r = $stmt->get_result(); $stmt->close(); } else $r = null;
        if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

$totalExams = 0; $published = 0; $pendingGrading = 0; $current = 0;
$exams = []; $courses = []; $students = [];

if ($conn) {
    $qr = $conn->query("SELECT COUNT(DISTINCT exam_number) c FROM examination_records"); $totalExams = $qr ? (int)$qr->fetch_assoc()['c'] : 0;
    $qr = $conn->query("SELECT COUNT(DISTINCT exam_number) c FROM examination_records WHERE grade_status='Published'"); $published = $qr ? (int)$qr->fetch_assoc()['c'] : 0;
    $qr = $conn->query("SELECT COUNT(DISTINCT exam_number) c FROM examination_records WHERE grade_status IN('Draft','Submitted','Under Review')"); $pendingGrading = $qr ? (int)$qr->fetch_assoc()['c'] : 0;
    $qr = $conn->query("SELECT COUNT(DISTINCT exam_number) c FROM examination_records WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"); $current = $qr ? (int)$qr->fetch_assoc()['c'] : 0;

    $search = trim($_GET['search'] ?? '');
    $filterType = trim($_GET['exam_type'] ?? '');
    $filterStatus = trim($_GET['status'] ?? '');
    $where = "1=1";
    $params = [];
    $types = '';
    if ($search !== '') { $where .= " AND (er.exam_number LIKE ? OR er.course_code LIKE ? OR cc.course_title LIKE ? OR er.exam_type LIKE ?)"; $like = "%$search%"; $params = array_merge($params, [$like, $like, $like, $like]); $types .= 'ssss'; }
    if ($filterType !== '') { $where .= " AND er.exam_type=?"; $params[] = $filterType; $types .= 's'; }
    if ($filterStatus !== '') { $where .= " AND er.grade_status=?"; $params[] = $filterStatus; $types .= 's'; }
    $stmt = $conn->prepare("SELECT er.exam_number, er.exam_type, er.course_code, cc.course_title course_name, er.grade_status, MIN(er.created_at) exam_date, COUNT(er.student_id) total_students FROM examination_records er LEFT JOIN academic_course_catalog cc ON er.course_code=cc.course_code WHERE $where GROUP BY er.exam_number, er.exam_type, er.course_code, cc.course_title, er.grade_status ORDER BY exam_date DESC LIMIT 100");
    if ($stmt) { if ($types) $stmt->bind_param($types, ...$params); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    if ($r) while ($row = $r->fetch_assoc()) $exams[] = $row;

    $cr = $conn->query("SELECT course_code, course_title FROM academic_course_catalog WHERE status='Active' ORDER BY course_code");
    if ($cr) while ($row = $cr->fetch_assoc()) $courses[] = $row;
    $sr = $studentsConn->query("SELECT id, CONCAT(first_name,' ',surname) full_name, index_number, student_number FROM students WHERE status='Active' ORDER BY surname LIMIT 500");
    if ($sr) while ($row = $sr->fetch_assoc()) $students[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!$conn) { $_SESSION['error'] = 'Database connection failed'; header('Location: exams-results.php'); exit; }

    if ($action === 'create_exam') {
        $course_code = trim($_POST['course_code'] ?? '');
        $exam_type = trim($_POST['exam_type'] ?? 'Final');
        $total_marks = floatval($_POST['total_marks'] ?? 100);
        $exam_number = strtoupper(substr($exam_type,0,3)) . '-' . $course_code . '-' . date('Ymd') . '-' . mt_rand(100,999);
        $student_ids = $_POST['student_ids'] ?? [];
        if (empty($course_code) || empty($student_ids)) {
            $_SESSION['error'] = 'Course and at least one student required.';
        } else {
            $inserted = 0;
            $stmt = $conn->prepare("INSERT IGNORE INTO examination_records (exam_number, student_id, course_code, exam_type, total_marks, grade_status, lecturer_id, created_at) VALUES (?, ?, ?, ?, ?, 'Draft', ?, NOW())");
            if ($stmt) {
                foreach ($student_ids as $sid) {
                    $sid = intval($sid);
                    if ($sid <= 0) continue;
                    $stmt->bind_param("sissdi", $exam_number, $sid, $course_code, $exam_type, $total_marks, $uid);
                    if ($stmt->execute()) $inserted++;
                }
                $stmt->close();
            }
            $_SESSION['success'] = "Exam '$exam_number' created with $inserted students.";
        }
        header('Location: exams-results.php'); exit;
    }

    if ($action === 'enter_marks') {
        $exam_number = trim($_POST['exam_number'] ?? '');
        $students_data = $_POST['students'] ?? [];
        if (empty($exam_number) || empty($students_data)) {
            $_SESSION['error'] = 'Exam number and student marks required.';
        } else {
            $updated = 0;
            $stmt = $conn->prepare("UPDATE examination_records SET continuous_assessment_marks=?, final_exam_marks=?, marks_obtained=?, grade=?, grade_status='Submitted' WHERE exam_number=? AND student_id=?");
            if ($stmt) {
                foreach ($students_data as $sid => $marks) {
                    $sid = intval($sid);
                    $ca = floatval($marks['ca'] ?? 0);
                    $exam = floatval($marks['exam'] ?? 0);
                    $total = $ca + $exam;
                    $grade = $total >= 80 ? 'A' : ($total >= 70 ? 'B+' : ($total >= 60 ? 'B' : ($total >= 50 ? 'C' : ($total >= 40 ? 'D' : 'F'))));
                    $stmt->bind_param("dddssi", $ca, $exam, $total, $grade, $exam_number, $sid);
                    if ($stmt->execute()) $updated++;
                }
                $stmt->close();
            }
            $_SESSION['success'] = "Marks entered for $updated students.";
        }
        header('Location: exams-results.php'); exit;
    }

    if ($action === 'update_status') {
        $exam_number = trim($_POST['exam_number'] ?? '');
        $new_status = trim($_POST['new_status'] ?? '');
        $allowed = ['Draft','Submitted','Under Review','Approved','Published','Rejected'];
        if (in_array($new_status, $allowed) && $exam_number) {
            $stmt = $conn->prepare("UPDATE examination_records SET grade_status=? WHERE exam_number=?");
            if ($stmt) { $stmt->bind_param('ss', $new_status, $exam_number); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = "Exam status updated to '$new_status'.";
        }
        header('Location: exams-results.php'); exit;
    }

    if ($action === 'delete_exam') {
        $exam_number = trim($_POST['exam_number'] ?? '');
        if ($exam_number) {
            $stmt = $conn->prepare("DELETE FROM examination_records WHERE exam_number=?");
            if ($stmt) { $stmt->bind_param('s', $exam_number); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = "Exam '$exam_number' deleted.";
        }
        header('Location: exams-results.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
@media print { body * { visibility: hidden; } .print-area, .print-area * { visibility: visible; } .print-area { position: absolute; left: 0; top: 0; width: 100%; } .no-print { display: none !important; } .main { margin-left: 0 !important; padding: 20px !important; } }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<section class="content-section dashboard-section active" data-section="overview">
<div class="main" style="margin-left:270px;padding:32px">
  <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h4 class="fw-bold mb-0"><i class="fas fa-file-alt me-2"></i>Exams & Results</h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>

  <?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2 no-print"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
  <?php if(!empty($_SESSION['error'])): ?><div class="alert alert-danger py-2 no-print"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

  <div class="row g-3 mb-4 no-print">
    <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-calendar-check"></i></div><div class="stat-content"><h3><?= $totalExams ?></h3><p>Total Exams</p></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $published ?></h3><p>Published</p></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-edit"></i></div><div class="stat-content"><h3><?= $pendingGrading ?></h3><p>Pending Grading</p></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-hourglass-half"></i></div><div class="stat-content"><h3><?= $current ?></h3><p>This Month</p></div></div></div>
  </div>

  <div class="no-print d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Exam Records</h5>
    <div>
      <button class="btn btn-sm btn-outline-primary me-1" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#examModal"><i class="fas fa-plus me-1"></i>Create Exam</button>
    </div>
  </div>

  <form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search exam number, course..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"></div>
    <div class="col-md-2"><select name="exam_type" class="form-select form-select-sm"><option value="">All Types</option><option <?= ($_GET['exam_type']??'')==='Mid-Semester'?'selected':''?>>Mid-Semester</option><option <?= ($_GET['exam_type']??'')==='Final'?'selected':''?>>Final</option><option <?= ($_GET['exam_type']??'')==='Supplementary'?'selected':''?>>Supplementary</option></select></div>
    <div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">All Status</option><option <?= ($_GET['status']??'')==='Draft'?'selected':''?>>Draft</option><option <?= ($_GET['status']??'')==='Submitted'?'selected':''?>>Submitted</option><option <?= ($_GET['status']??'')==='Under Review'?'selected':''?>>Under Review</option><option <?= ($_GET['status']??'')==='Approved'?'selected':''?>>Approved</option><option <?= ($_GET['status']??'')==='Published'?'selected':''?>>Published</option><option <?= ($_GET['status']??'')==='Rejected'?'selected':''?>>Rejected</option></select></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i></button></div>
    <div class="col-md-2"><a href="exams-results.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times"></i> Clear</a></div>
  </form>

  <div class="print-area table-responsive">
    <table class="table table-striped table-hover">
      <thead class="table-dark"><tr><th>Exam</th><th>Course</th><th>Date</th><th>Students</th><th>Status</th><th class="no-print">Actions</th></tr></thead>
      <tbody><?php if (empty($exams)): ?><tr><td colspan="6" class="text-muted text-center py-3">No exam records found.</td></tr><?php else: foreach ($exams as $e): ?><tr>
        <td><strong><?= htmlspecialchars($e['exam_type'] ?? '-') ?></strong><br><small class="text-muted"><?= htmlspecialchars($e['exam_number']) ?></small></td>
        <td><?= htmlspecialchars($e['course_name'] ?? $e['course_code']) ?><br><small class="text-muted"><?= htmlspecialchars($e['course_code']) ?></small></td>
        <td><?= $e['exam_date'] ? date('d M Y', strtotime($e['exam_date'])) : '-' ?></td>
        <td><?= $e['total_students'] ?></td>
        <td><span class="badge <?= $e['grade_status']==='Published'?'bg-success':($e['grade_status']==='Approved'?'bg-primary':($e['grade_status']==='Rejected'?'bg-danger':($e['grade_status']==='Under Review'?'bg-info':'bg-warning text-dark'))) ?>"><?= htmlspecialchars($e['grade_status'] ?? 'Draft') ?></span></td>
        <td class="no-print">
          <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="viewExam('<?= htmlspecialchars($e['exam_number'], ENT_QUOTES) ?>')" title="Enter Marks"><i class="fas fa-pen"></i></button>
          <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="printExam('<?= htmlspecialchars($e['exam_number'], ENT_QUOTES) ?>')" title="Print Results"><i class="fas fa-print"></i></button>
          <?php if ($e['grade_status'] === 'Draft'): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Submit for review?')"><input type="hidden" name="action" value="update_status"><input type="hidden" name="exam_number" value="<?= htmlspecialchars($e['exam_number']) ?>"><input type="hidden" name="new_status" value="Submitted"><button class="btn btn-sm btn-outline-warning py-0 px-1" title="Submit"><i class="fas fa-paper-plane"></i></button></form>
          <?php elseif ($e['grade_status'] === 'Submitted'): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Approve results?')"><input type="hidden" name="action" value="update_status"><input type="hidden" name="exam_number" value="<?= htmlspecialchars($e['exam_number']) ?>"><input type="hidden" name="new_status" value="Approved"><button class="btn btn-sm btn-outline-success py-0 px-1" title="Approve"><i class="fas fa-check"></i></button></form>
          <?php elseif ($e['grade_status'] === 'Approved'): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Publish results?')"><input type="hidden" name="action" value="update_status"><input type="hidden" name="exam_number" value="<?= htmlspecialchars($e['exam_number']) ?>"><input type="hidden" name="new_status" value="Published"><button class="btn btn-sm btn-outline-success py-0 px-1" title="Publish"><i class="fas fa-globe"></i></button></form>
          <?php endif; ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this exam?')"><input type="hidden" name="action" value="delete_exam"><input type="hidden" name="exam_number" value="<?= htmlspecialchars($e['exam_number']) ?>"><button class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="fas fa-trash-alt"></i></button></form>
        </td>
      </tr><?php endforeach; endif; ?></tbody>
    </table>
  </div>
</div>

<!-- Create Exam Modal -->
<div class="modal fade" id="examModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><form method="POST" class="modal-content"><input type="hidden" name="action" value="create_exam"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Create Exam</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body">
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Course *</label><select name="course_code" class="form-select" required><option value="">-- Select Course --</option><?php foreach($courses as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code'].' - '.$c['course_title']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label">Exam Type *</label><select name="exam_type" class="form-select" required><option value="Mid-Semester">Mid-Semester</option><option value="Final" selected>Final</option><option value="Supplementary">Supplementary</option></select></div>
    <div class="col-md-3"><label class="form-label">Total Marks</label><input type="number" name="total_marks" class="form-control" value="100" min="1"></div>
    <div class="col-12"><label class="form-label">Select Students *</label>
      <div class="row g-2" style="max-height:300px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;padding:8px">
        <?php foreach($students as $s): ?>
        <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="student_ids[]" value="<?= $s['id'] ?>" id="s_<?= $s['id'] ?>"><label class="form-check-label" for="s_<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> <small class="text-muted">(<?= htmlspecialchars($s['index_number']?:$s['student_number']) ?>)</small></label></div></div>
        <?php endforeach; ?>
      </div>
      <div class="mt-2"><button type="button" class="btn btn-sm btn-link p-0" onclick="document.querySelectorAll('#examModal input[type=checkbox]').forEach(c=>c.checked=true)">Select All</button> | <button type="button" class="btn btn-sm btn-link p-0" onclick="document.querySelectorAll('#examModal input[type=checkbox]').forEach(c=>c.checked=false)">Clear All</button></div>
    </div>
  </div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Exam</button></div></form></div></div>

<!-- Enter Marks Modal -->
<div class="modal fade" id="marksModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Enter Marks</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="marksBody"><p class="text-muted">Loading...</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveMarks()"><i class="fas fa-save me-1"></i>Save Marks</button></div></div></div></div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</section>
<script>
let currentExamNumber = '';
function viewExam(examNumber) {
    currentExamNumber = examNumber;
    document.getElementById('marksBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading students...</div>';
    new bootstrap.Modal(document.getElementById('marksModal')).show();
    fetch('exams-results.php?ajax=exam_students&exam=' + encodeURIComponent(examNumber))
    .then(r => r.json())
    .then(data => {
        let h = '<form id="marksForm"><input type="hidden" name="exam_number" value="' + esc(examNumber) + '">';
        h += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>#</th><th>Student</th><th>Index</th><th>CA Marks</th><th>Exam Marks</th><th>Total</th><th>Grade</th></tr></thead><tbody>';
        if (!data || !data.length) {
            h += '<tr><td colspan="7" class="text-muted text-center py-3">No students found for this exam.</td></tr>';
        } else {
            data.forEach(function(s, i) {
                h += '<tr><td>' + (i + 1) + '</td><td>' + esc(s.full_name || s.surname + ' ' + s.first_name) + '</td>';
                h += '<td>' + esc(s.index_number || s.student_number || '') + '</td>';
                h += '<td><input type="number" name="students[' + s.student_id + '][ca]" class="form-control form-control-sm ca-input" value="' + (parseFloat(s.continuous_assessment_marks) || 0) + '" step="0.5" style="width:80px"></td>';
                h += '<td><input type="number" name="students[' + s.student_id + '][exam]" class="form-control form-control-sm exam-input" value="' + (parseFloat(s.final_exam_marks) || 0) + '" step="0.5" style="width:80px"></td>';
                let tot = (parseFloat(s.continuous_assessment_marks) || 0) + (parseFloat(s.final_exam_marks) || 0);
                h += '<td class="total-cell fw-bold">' + tot + '</td>';
                h += '<td class="grade-cell">' + getGrade(tot) + '</td></tr>';
            });
        }
        h += '</tbody></table></div></form>';
        document.getElementById('marksBody').innerHTML = h;
        document.querySelectorAll('.ca-input, .exam-input').forEach(el => {
            el.addEventListener('input', function() {
                let row = this.closest('tr');
                let ca = parseFloat(row.querySelector('.ca-input').value) || 0;
                let exam = parseFloat(row.querySelector('.exam-input').value) || 0;
                let total = ca + exam;
                row.querySelector('.total-cell').textContent = total;
                row.querySelector('.grade-cell').textContent = getGrade(total);
            });
        });
    })
    .catch(() => { document.getElementById('marksBody').innerHTML = '<div class="alert alert-danger">Failed to load students.</div>'; });
}
function saveMarks() {
    let form = document.getElementById('marksForm');
    if (!form) return;
    let data = new FormData(form);
    data.append('action', 'enter_marks');
    data.append('csrf_token', window.CSRF_TOKEN);
    fetch('exams-results.php', { method: 'POST', body: data })
    .then(() => { location.reload(); })
    .catch(() => { alert('Failed to save marks.'); });
}
function printExam(examNumber) {
    let w = window.open('', '_blank');
    w.document.write('<html><head><title>Exam Results - ' + esc(examNumber) + '</title>');
    w.document.write('<style>body{font-family:Arial,sans-serif;padding:30px;}h2{color:#1a237e;border-bottom:2px solid #1a237e;padding-bottom:8px;}table{width:100%;border-collapse:collapse;margin-top:15px;}th,td{border:1px solid #ccc;padding:8px 10px;text-align:left;}th{background:#1a237e;color:#fff;}.center{text-align:center;}</style></head>');
    w.document.write('<body><h2>Exam Results</h2><p><strong>Exam:</strong> ' + esc(examNumber) + '</p>');
    w.document.write('<table><thead><tr><th>#</th><th>Student</th><th>Index</th><th>CA Marks</th><th>Exam Marks</th><th>Total</th><th>Grade</th></tr></thead><tbody id="printBody"><tr><td colspan="7" class="center">Loading...</td></tr></tbody></table>');
    w.document.write('<p style="margin-top:30px;color:#999;font-size:11px;">Generated on ' + new Date().toLocaleDateString() + ' | ISNM</p>');
    w.document.write('</body></html>');
    w.document.close();
    fetch('exams-results.php?ajax=exam_students&exam=' + encodeURIComponent(examNumber))
    .then(r => r.json())
    .then(data => {
        let body = '';
        if (data && data.length) {
            data.forEach(function(s, i) {
                let ca = parseFloat(s.continuous_assessment_marks) || 0;
                let exam = parseFloat(s.final_exam_marks) || 0;
                let total = ca + exam;
                body += '<tr><td>' + (i + 1) + '</td><td>' + esc(s.full_name || s.surname + ' ' + s.first_name) + '</td>';
                body += '<td>' + esc(s.index_number || '') + '</td><td>' + ca + '</td><td>' + exam + '</td><td>' + total + '</td><td>' + getGrade(total) + '</td></tr>';
            });
        } else {
            body = '<tr><td colspan="7" class="center text-muted">No records found.</td></tr>';
        }
        w.document.getElementById('printBody').innerHTML = body;
        setTimeout(() => { w.print(); }, 300);
    })
    .catch(() => { w.document.getElementById('printBody').innerHTML = '<tr><td colspan="7" class="center text-danger">Failed to load data.</td></tr>'; });
}
function getGrade(total) {
    if (total >= 80) return 'A';
    if (total >= 70) return 'B+';
    if (total >= 60) return 'B';
    if (total >= 50) return 'C';
    if (total >= 40) return 'D';
    return 'F';
}
function esc(s) { if (!s) return ''; let d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
</script>
</body>
</html>
