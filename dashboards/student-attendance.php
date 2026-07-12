<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['lecturer', 'hods', 'registrar', 'admin']);
$conn = $ctx['students'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$pageTitle = 'Student Attendance';

$present = 0; $absent = 0; $late = 0; $total = 0; $records = []; $students = [];
$dateFilter = $_GET['date'] ?? date('Y-m-d');
$searchFilter = trim($_GET['search'] ?? '');
$programFilter = trim($_GET['program'] ?? '');
$levelFilter = trim($_GET['level'] ?? '');
$hasAttFilter = $searchFilter !== '' || $programFilter !== '' || $levelFilter !== '';
if ($conn) {
    $r = $conn->query("SELECT COUNT(*) c FROM student_attendance WHERE attendance_date=CURDATE() AND status='Present'");
    if ($r) $present = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM student_attendance WHERE attendance_date=CURDATE() AND status='Absent'");
    if ($r) $absent = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM student_attendance WHERE attendance_date=CURDATE() AND status='Late'");
    if ($r) $late = (int)$r->fetch_assoc()['c'];
    $r = $conn->query("SELECT COUNT(*) c FROM student_attendance");
    if ($r) $total = (int)$r->fetch_assoc()['c'];
    $types = 's';
    $params = [$dateFilter];
    $w = "a.attendance_date=?";
    if ($searchFilter !== '') {
        $like = '%' . $searchFilter . '%';
        $w .= " AND (CONCAT(s.first_name,' ',s.surname) LIKE ? OR s.index_number LIKE ? OR s.student_number LIKE ?)";
        $types .= 'sss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $stmt = $conn->prepare("SELECT a.*, CONCAT(s.first_name,' ',s.surname) student_name, s.index_number, s.student_number FROM student_attendance a LEFT JOIN students s ON a.student_id=s.id WHERE $w ORDER BY a.time_in DESC");
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $q = $stmt->get_result();
        if ($q) $records = $q->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    if ($programFilter !== '' || $levelFilter !== '') {
        $aw = "WHERE status='Active'";
        $ptypes = '';
        $pparams = [];
        if ($programFilter !== '') {
            $aw .= " AND program=?";
            $ptypes .= 's';
            $pparams[] = $programFilter;
        }
        if ($levelFilter !== '') {
            $aw .= " AND level=?";
            $ptypes .= 's';
            $pparams[] = $levelFilter;
        }
        $stmt = $conn->prepare("SELECT id, CONCAT(first_name,' ',surname) full_name, index_number FROM students $aw ORDER BY surname LIMIT 300");
        if ($stmt) {
            if (!empty($pparams)) $stmt->bind_param($ptypes, ...$pparams);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $sr = $stmt->get_result();
            if ($sr) while ($row = $sr->fetch_assoc()) $students[] = $row;
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!$conn) { $_SESSION['error'] = 'DB connection failed'; header('Location: student-attendance.php'); exit; }

    if ($action === 'record_attendance') {
        $attendanceDate = $_POST['attendance_date'] ?? date('Y-m-d');
        $entries = $_POST['attendance'] ?? [];
        $count = 0;
        $stmt = $conn->prepare("INSERT IGNORE INTO student_attendance (student_id, attendance_date, time_in, time_out, status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            foreach ($entries as $sid => $data) {
                $sid = intval($sid);
                $timeIn = $data['time_in'] ?? date('H:i:s');
                $timeOut = $data['time_out'] ?? '';
                $status = $data['status'] ?? 'Present';
                $stmt->bind_param("issss", $sid, $attendanceDate, $timeIn, $timeOut, $status);
                if ($stmt->execute()) $count++;
            }
            $stmt->close();
        }
        $_SESSION['success'] = "Attendance recorded for $count students.";
        header('Location: student-attendance.php?date=' . urlencode($attendanceDate)); exit;
    }

    if ($action === 'delete_attendance') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM student_attendance WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = 'Attendance record deleted.';
        }
        header('Location: student-attendance.php?date=' . urlencode($dateFilter)); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>@media print { body * { visibility: hidden; } .print-area, .print-area * { visibility: visible; } .print-area { position: absolute; left: 0; top: 0; width: 100%; } .no-print { display: none !important; } .main { margin-left: 0 !important; padding: 20px !important; } }</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<section class="content-section dashboard-section active" data-section="overview">
<main class="main" style="margin-left:270px;padding:32px;">
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
<h4 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2"></i>Student Attendance</h4>
<span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>

<?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2 no-print"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
<?php if(!empty($_SESSION['error'])): ?><div class="alert alert-danger py-2 no-print"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

<div class="row g-3 mb-4 no-print">
<?php $c=[['Present Today',$present,'success','user-check'],['Absent',$absent,'danger','user-times'],['Late',$late,'warning','clock'],['Total Records',$total,'info','database']]; foreach($c as $s): ?>
<div class="col-md-3"><div class="stat-card <?= $s[2] ?>"><div class="stat-icon"><i class="fas fa-<?= $s[3] ?>"></i></div><div class="stat-content"><h3><?= number_format($s[1]) ?></h3><p><?= $s[0] ?></p></div></div></div>
<?php endforeach; ?>
</div>

<div class="no-print d-flex justify-content-between align-items-center mb-3">
<h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Attendance Records</h5>
<div>
<button class="btn btn-sm btn-outline-primary me-1" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
<button class="btn btn-sm btn-primary" onclick="openAttendanceModal()"><i class="fas fa-plus me-1"></i>Record Attendance</button>
</div>
</div>
<?php if ($programFilter === '' && $levelFilter === ''): ?>
<div class="alert alert-info py-2 mb-3 no-print"><i class="fas fa-info-circle me-1"></i> Select a <strong>Program</strong> and/or <strong>Level</strong> filter above to record attendance for a specific group.</div>
<?php endif; ?>

<form method="GET" class="row g-2 mb-3 no-print">
<div class="col-md-2"><input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFilter) ?>"></div>
<div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search student name, index..." value="<?= htmlspecialchars($searchFilter) ?>"></div>
<div class="col-md-2"><select name="program" class="form-select form-select-sm"><option value="">All Programs</option><?php
if ($conn) { $pr = $conn->query("SELECT DISTINCT program FROM students WHERE status='Active' ORDER BY program"); if ($pr) while ($p = $pr->fetch_assoc()) echo '<option ' . ($programFilter===$p['program']?'selected':'') . '>' . htmlspecialchars($p['program']) . '</option>'; }
?></select></div>
<div class="col-md-2"><select name="level" class="form-select form-select-sm"><option value="">All Levels</option><?php
if ($conn) { $lr = $conn->query("SELECT DISTINCT level FROM students WHERE status='Active' ORDER BY level"); if ($lr) while ($l = $lr->fetch_assoc()) echo '<option ' . ($levelFilter===$l['level']?'selected':'') . '>' . htmlspecialchars($l['level']) . '</option>'; }
?></select></div>
<div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i> Filter</button></div>
</form>
<div class="mb-3 no-print">
<a href="student-attendance.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-times"></i> Clear All</a>
</div>

<div class="print-area content-section">
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<thead class="table-light"><tr><th>#</th><th>Student</th><th>Index</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th><th class="no-print">Action</th></tr></thead>
<tbody>
<?php if(empty($records) && $hasAttFilter): ?>
<tr><td colspan="8" class="text-center text-muted py-3">No attendance records for this date.</td></tr>
<?php elseif(empty($records) && !$hasAttFilter): ?>
<tr><td colspan="8" class="text-center text-muted py-3">Use the filters above to search attendance records.</td></tr>
<?php else: $i=0; foreach($records as $r): $i++;
$st=$r['status']??'';
$bc=$st==='Present'?'bg-success':($st==='Absent'?'bg-danger':($st==='Late'?'bg-warning text-dark':($st==='Excused'?'bg-info':'bg-secondary')));
?>
<tr>
<td><?= $i ?></td>
<td><strong><?= htmlspecialchars($r['student_name']??'-') ?></strong></td>
<td><small><?= htmlspecialchars($r['index_number']??$r['student_number']??'') ?></small></td>
<td><?= htmlspecialchars($r['attendance_date']??$r['date']??'-') ?></td>
<td><?= htmlspecialchars($r['time_in']??'-') ?></td>
<td><?= htmlspecialchars($r['time_out']??'-') ?></td>
<td><span class="badge <?= $bc ?>"><?= htmlspecialchars($st) ?></span></td>
<td class="no-print">
<form method="POST" style="display:inline" onsubmit="return confirm('Delete this record?')"><input type="hidden" name="action" value="delete_attendance"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0 px-1"><i class="fas fa-trash-alt"></i></button></form>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</main>
</section>

<!-- Record Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><form method="POST" class="modal-content"><input type="hidden" name="action" value="record_attendance"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Record Attendance</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body">
<div class="mb-3"><label class="form-label">Date *</label><input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
<?php if (empty($students)): ?>
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-1"></i> No students loaded. Please select a <strong>Program</strong> and/or <strong>Level</strong> filter from the search form above, then click "Record Attendance" again.</div>
<?php else: ?>
<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th><input type="checkbox" onchange="document.querySelectorAll('.att-check').forEach(c=>c.checked=this.checked)"></th><th>Student</th><th>Index</th><th>Time In</th><th>Time Out</th><th>Status</th></tr></thead><tbody>
<?php foreach($students as $s): ?>
<tr>
<td><input type="checkbox" class="att-check" name="attendance[<?= $s['id'] ?>][selected]" value="1"></td>
<td><?= htmlspecialchars($s['full_name']) ?></td>
<td><small><?= htmlspecialchars($s['index_number']??'') ?></small></td>
<td><input type="time" name="attendance[<?= $s['id'] ?>][time_in]" class="form-control form-control-sm" value="<?= date('H:i') ?>"></td>
<td><input type="time" name="attendance[<?= $s['id'] ?>][time_out]" class="form-control form-control-sm"></td>
<td><select name="attendance[<?= $s['id'] ?>][status]" class="form-select form-select-sm"><option value="Present">Present</option><option value="Absent">Absent</option><option value="Late">Late</option><option value="Excused">Excused</option></select></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="reset" class="btn btn-outline-secondary">Reset</button><button type="submit" class="btn btn-primary" <?= empty($students) ? 'disabled' : '' ?>><i class="fas fa-save me-1"></i>Save Attendance</button></div></form></div></div>
<script>
function openAttendanceModal() {
    <?php if ($programFilter === '' && $levelFilter === ''): ?>
    alert('Please select a Program and/or Level filter first, then click Record Attendance.');
    <?php else: ?>
    new bootstrap.Modal(document.getElementById('attendanceModal')).show();
    <?php endif; ?>
}
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
