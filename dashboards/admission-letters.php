<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['admissions', 'director', 'secretary']);
$staffDb = $ctx['staff'];
$wconn = $ctx['website'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$pageTitle = 'Admission Letters';

if (isset($_GET['ajax']) && $_GET['ajax'] === 'applicant_detail' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = intval($_GET['id']);
    $data = null;
    if ($wconn) {
        $r = $wconn->query("SELECT * FROM student_applications WHERE id=$id");
        if ($r) $data = $r->fetch_assoc();
    }
    echo json_encode($data);
    exit;
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$applicants = []; $pendingCount = 0; $admittedCount = 0; $rejectedCount = 0;

if ($wconn) {
    $where = "1=1";
    if ($search !== '') { $s = $wconn->real_escape_string($search); $where .= " AND (application_number LIKE '%$s%' OR first_name LIKE '%$s%' OR surname LIKE '%$s%' OR phone LIKE '%$s%' OR email LIKE '%$s%' OR program_applied LIKE '%$s%')"; }
    if ($statusFilter !== '') { $st = $wconn->real_escape_string($statusFilter); $where .= " AND status='$st'"; }
    $r = $wconn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Pending'"); $pendingCount = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $wconn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Admitted'"); $admittedCount = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $wconn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Rejected'"); $rejectedCount = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $wconn->query("SELECT * FROM student_applications WHERE $where ORDER BY submitted_at DESC LIMIT 150");
    if ($r) while ($row = $r->fetch_assoc()) $applicants[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status' && $wconn && $staffDb) {
        $id = intval($_POST['id'] ?? 0);
        $newStatus = $wconn->real_escape_string($_POST['status'] ?? '');
        $allowed = ['Shortlisted', 'Admitted', 'Rejected', 'Withdrawn'];
        if (in_array($newStatus, $allowed) && $id > 0) {
            $wconn->query("UPDATE student_applications SET status='$newStatus', reviewed_by={$_SESSION['user_id']}, reviewed_at=NOW() WHERE id=$id");
            if ($newStatus === 'Admitted') {
                $app = $wconn->query("SELECT * FROM student_applications WHERE id=$id");
                if ($app && ($a = $app->fetch_assoc())) {
                    $admNo = 'ADM-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
                    $stmt = $staffDb->prepare("INSERT IGNORE INTO students (first_name, surname, other_name, full_name, gender, index_number, phone, email, program, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                    if ($stmt) {
                        $fullName = trim($a['first_name'] . ' ' . ($a['other_name']??'') . ' ' . $a['surname']);
                        $idxNo = 'IND-' . str_pad($id, 5, '0', STR_PAD_LEFT);
                        $stmt->bind_param('sssssssss', $a['first_name'], $a['surname'], $a['other_name'], $fullName, $a['gender'], $idxNo, $a['phone'], $a['email'], $a['program_applied']);
                        $stmt->execute();
                        $studentId = $stmt->insert_id;
                        $stmt->close();
                        if ($studentId > 0) {
                            $staffDb->query("INSERT IGNORE INTO student_admissions (admission_number, student_id, academic_year, program, admission_date, admission_status) VALUES ('$admNo', $studentId, '" . date('Y') . '/' . (date('Y')+1) . "', '" . $staffDb->real_escape_string($a['program_applied']) . "', CURDATE(), 'Approved')");
                        }
                    }
                }
            }
            $_SESSION['success'] = "Applicant status updated to '$newStatus'.";
        }
        header('Location: admission-letters.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>@media print { body * { visibility: hidden; } .print-area, .print-area * { visibility: visible; } .print-area { position: absolute; left: 0; top: 0; width: 100%; } .no-print { display: none !important; } .main { margin-left: 0 !important; padding: 20px !important; } }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="d-flex justify-content-between align-items-center mb-4 no-print"><h4 class="fw-bold mb-0"><i class="fas fa-envelope-open-text me-2"></i>Admission Letters</h4><span class="text-muted small"><?= date('l, d M Y') ?></span></div>
<?php renderModuleSlider($user_role); ?>
<?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2 no-print"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
<?php if(!empty($_SESSION['error'])): ?><div class="alert alert-danger py-2 no-print"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

<div class="row g-3 mb-4 no-print">
  <div class="col-md-4"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $pendingCount ?></h3><p>Pending Review</p></div></div></div>
  <div class="col-md-4"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $admittedCount ?></h3><p>Admitted</p></div></div></div>
  <div class="col-md-4"><div class="stat-card danger"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-content"><h3><?= $rejectedCount ?></h3><p>Rejected</p></div></div></div>
</div>

<div class="no-print d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Applicants</h5>
  <button class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
</div>

<form method="GET" class="row g-2 mb-3 no-print">
  <div class="col-md-5"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, application #, phone, program..." value="<?= htmlspecialchars($search) ?>"></div>
  <div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">All Status</option><option <?= $statusFilter==='Pending'?'selected':''?>>Pending</option><option <?= $statusFilter==='Shortlisted'?'selected':''?>>Shortlisted</option><option <?= $statusFilter==='Admitted'?'selected':''?>>Admitted</option><option <?= $statusFilter==='Rejected'?'selected':''?>>Rejected</option><option <?= $statusFilter==='Withdrawn'?'selected':''?>>Withdrawn</option></select></div>
  <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i> Search</button></div>
  <div class="col-md-2"><a href="admission-letters.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times"></i> Clear</a></div>
</form>

<div class="print-area card"><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>#</th><th>Application #</th><th>Applicant</th><th>Gender</th><th>Phone</th><th>Program</th><th>Submitted</th><th>Status</th><th class="no-print">Actions</th></tr></thead>
<tbody>
<?php if (empty($applicants)): ?><tr><td colspan="9" class="text-center text-muted py-4">No applicants found.</td></tr><?php else: $i=1; foreach ($applicants as $a): ?>
<tr>
  <td><?= $i++ ?></td>
  <td><code><?= htmlspecialchars($a['application_number'] ?? 'APP-'.$a['id']) ?></code></td>
  <td><strong><?= htmlspecialchars($a['surname'] . ', ' . $a['first_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($a['email'] ?? '') ?></small></td>
  <td><?= htmlspecialchars($a['gender'] ?? '') ?></td>
  <td><?= htmlspecialchars($a['phone'] ?? '') ?></td>
  <td><?= htmlspecialchars($a['program_applied'] ?? '') ?></td>
  <td><small><?= $a['submitted_at'] ? date('d M Y', strtotime($a['submitted_at'])) : '-' ?></small></td>
  <td><span class="badge bg-<?= $a['status']==='Admitted'?'success':($a['status']==='Rejected'?'danger':($a['status']==='Shortlisted'?'primary':($a['status']==='Withdrawn'?'secondary':'warning text-dark'))) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
  <td class="no-print">
    <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="viewApplicant(<?= $a['id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
    <?php if ($a['status'] === 'Pending' || $a['status'] === 'Shortlisted'): ?>
    <form method="POST" style="display:inline" onsubmit="return confirm('Admit this applicant? This will create a student record.')"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="status" value="Admitted"><button class="btn btn-sm btn-outline-success py-0 px-1" title="Admit"><i class="fas fa-check"></i></button></form>
    <form method="POST" style="display:inline" onsubmit="return confirm('Reject this applicant?')"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="status" value="Rejected"><button class="btn btn-sm btn-outline-danger py-0 px-1" title="Reject"><i class="fas fa-times"></i></button></form>
    <?php endif; ?>
    <?php if ($a['status'] === 'Admitted'): ?>
    <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="printLetter(<?= $a['id'] ?>)" title="Print Letter"><i class="fas fa-print"></i></button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div></div></div>

<!-- Applicant Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Applicant Details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="detailBody"><p class="text-muted">Loading...</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div></div></div></div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
function viewApplicant(id) {
    document.getElementById('detailBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    new bootstrap.Modal(document.getElementById('detailModal')).show();
    fetch('admission-letters.php?ajax=applicant_detail&id=' + id)
    .then(r => r.json())
    .then(d => {
        if (!d) { document.getElementById('detailBody').innerHTML = '<div class="alert alert-danger">Not found.</div>'; return; }
        let h = '<div class="row"><div class="col-md-6">';
        h += '<table class="table table-sm table-borderless"><tr><th width="140">Name</th><td>' + esc(d.surname) + ', ' + esc(d.first_name) + ' ' + esc(d.other_name||'') + '</td></tr>';
        h += '<tr><th>Gender</th><td>' + esc(d.gender) + '</td></tr>';
        h += '<tr><th>Date of Birth</th><td>' + esc(d.date_of_birth) + '</td></tr>';
        h += '<tr><th>Nationality</th><td>' + esc(d.nationality) + '</td></tr>';
        h += '</table></div><div class="col-md-6">';
        h += '<table class="table table-sm table-borderless"><tr><th width="140">Phone</th><td>' + esc(d.phone) + '</td></tr>';
        h += '<tr><th>Email</th><td>' + esc(d.email) + '</td></tr>';
        h += '<tr><th>Address</th><td>' + esc(d.address||'') + '</td></tr>';
        h += '</table></div></div>';
        h += '<h6 class="fw-bold mt-3">Application Info</h6>';
        h += '<table class="table table-sm table-borderless"><tr><th width="140">Application #</th><td>' + esc(d.application_number || ('APP-' + d.id)) + '</td></tr>';
        h += '<tr><th>Program Applied</th><td>' + esc(d.program_applied) + '</td></tr>';
        h += '<tr><th>Previous School</th><td>' + esc(d.previous_school||'') + '</td></tr>';
        h += '<tr><th>Submitted</th><td>' + esc(d.submitted_at||'') + '</td></tr>';
        h += '<tr><th>Status</th><td><span class="badge bg-'+(d.status==='Admitted'?'success':(d.status==='Rejected'?'danger':(d.status==='Shortlisted'?'primary':'warning')))+'">' + esc(d.status) + '</span></td></tr>';
        h += '</table>';
        if (d.uce_results) h += '<h6 class="fw-bold mt-3">UCE Results</h6><pre class="bg-light p-2 rounded small">' + esc(d.uce_results) + '</pre>';
        if (d.uace_results) h += '<h6 class="fw-bold mt-3">UACE Results</h6><pre class="bg-light p-2 rounded small">' + esc(d.uace_results) + '</pre>';
        document.getElementById('detailBody').innerHTML = h;
    })
    .catch(() => { document.getElementById('detailBody').innerHTML = '<div class="alert alert-danger">Failed to load.</div>'; });
}
function printLetter(id) {
    fetch('admission-letters.php?ajax=applicant_detail&id=' + id)
    .then(r => r.json())
    .then(d => {
        if (!d) return;
        let w = window.open('', '_blank');
        w.document.write('<html><head><title>Admission Letter</title>');
        w.document.write('<style>body{font-family:Georgia,serif;padding:60px 80px;}h1{color:#1a237e;text-align:center;font-size:22px;border-bottom:3px double #1a237e;padding-bottom:12px;}.school{text-align:center;font-size:14px;color:#555;margin-bottom:30px;}.date{text-align:right;margin:20px 0;font-size:14px;}h3{margin-top:30px;color:#1a237e;}.ref{text-align:right;font-size:13px;color:#888;margin-bottom:20px;}p{font-size:14px;line-height:1.8;}strong{color:#1a237e;}.footer{text-align:center;margin-top:50px;padding-top:20px;border-top:1px solid #ccc;font-size:12px;color:#999;}</style></head>');
        w.document.write('<body><div class="ref"><strong>Ref:</strong> ' + esc(d.application_number || 'APP-' + d.id) + '</div>');
        w.document.write('<h1>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h1>');
        w.document.write('<p class="school">P.O. Box 418, Iganga, Uganda<br>Tel: 0782 990 403 | Email: info@igangaschoolofnursingandmidwifery.ac.ug</p>');
        w.document.write('<p class="date">' + new Date().toLocaleDateString('en-GB', {day:'numeric',month:'long',year:'numeric'}) + '</p>');
        w.document.write('<h3>ADMISSION LETTER</h3>');
        w.document.write('<p><strong>Dear ' + esc(d.surname) + ' ' + esc(d.first_name) + ',</strong></p>');
        w.document.write('<p>We are pleased to inform you that you have been granted <strong>Admission</strong> to study <strong>' + esc(d.program_applied) + '</strong> at the Iganga School of Nursing and Midwifery for the Academic Year <strong>' + new Date().getFullYear() + '/' + (new Date().getFullYear()+1) + '</strong>.</p>');
        w.document.write('<p>Your admission details are as follows:</p>');
        w.document.write('<p><strong>Application Number:</strong> ' + esc(d.application_number || 'APP-' + d.id) + '<br>');
        w.document.write('<strong>Program of Study:</strong> ' + esc(d.program_applied) + '<br>');
        w.document.write('<strong>Gender:</strong> ' + esc(d.gender) + '<br>');
        w.document.write('<strong>Date of Birth:</strong> ' + esc(d.date_of_birth) + '<br>');
        w.document.write('<strong>Academic Year:</strong> ' + new Date().getFullYear() + '/' + (new Date().getFullYear()+1) + '</p>');
        w.document.write('<p>Please report to the school on the date indicated in the reporting schedule with the following documents:</p>');
        w.document.write('<ul><li>Original and copies of your academic certificates</li><li>Birth certificate</li><li>National ID or Passport</li><li>Two passport-size photographs</li><li>Medical report from a recognized health facility</li><li>School fees payment receipt</li></ul>');
        w.document.write('<p>We look forward to welcoming you to our institution.</p>');
        w.document.write('<p style="margin-top:40px;">Yours sincerely,</p>');
        w.document.write('<p style="margin-top:60px;"><strong>Principal</strong><br>Iganga School of Nursing and Midwifery</p>');
        w.document.write('<p class="footer">"Chosen to Serve" , Disciplined Mind for Health Action</p>');
        w.document.write('</body></html>');
        w.document.close();
        setTimeout(() => { w.print(); }, 500);
    });
}
function esc(s) { if (!s) return ''; let d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
</script>
</body></html>
