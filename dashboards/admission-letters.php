<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['admissions', 'director', 'secretary']);
$staffDb = $ctx['staff'];
$wconn = $ctx['website'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$pageTitle = 'Admission Letters';

$view = $_GET['view'] ?? '';
$allowedViews = ['applications', 'clearance'];

if (isset($_GET['ajax']) && $_GET['ajax'] === 'applicant_detail' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = intval($_GET['id']);
    $data = null;
    if ($wconn) {
        $stmt = $wconn->prepare("SELECT * FROM student_applications WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($r) $data = $r->fetch_assoc();
        $stmt->close();
    }
    echo json_encode($data);
    exit;
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$applicants = []; $pendingCount = 0; $admittedCount = 0; $rejectedCount = 0; $shortlistedCount = 0;
$clearanceData = []; $totalReqs = 0;

if ($wconn) {
    $where = "1=1";
    $params = [];
    $types = '';
    
    if ($search !== '') { 
        $like = "%$search%";
        $where .= " AND (application_number LIKE ? OR first_name LIKE ? OR surname LIKE ? OR phone LIKE ? OR email LIKE ? OR program_applied LIKE ?)"; 
        $types .= 'ssssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($statusFilter !== '') { 
        $where .= " AND status=?"; 
        $types .= 's';
        $params[] = $statusFilter;
    }
    
    $r = $wconn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Pending'"); $pendingCount = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $wconn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Admitted'"); $admittedCount = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $wconn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Rejected'"); $rejectedCount = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $wconn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Shortlisted'"); $shortlistedCount = $r ? (int)$r->fetch_assoc()['c'] : 0;
    
    if (!empty($params)) {
        $stmt = $wconn->prepare("SELECT * FROM student_applications WHERE $where ORDER BY submitted_at DESC LIMIT 150");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $r = $stmt->get_result();
    } else {
        $r = $wconn->query("SELECT * FROM student_applications WHERE $where ORDER BY submitted_at DESC LIMIT 150");
    }
    if ($r) while ($row = $r->fetch_assoc()) $applicants[] = $row;
    if (!empty($params)) $stmt->close();

    if ($view === 'clearance') {
        $r = $wconn->query("SELECT COUNT(*) c FROM admission_requirements WHERE is_active=1"); if ($r) $totalReqs = (int)$r->fetch_assoc()['c'];
        $rc = $wconn->query("SELECT sa.id, sa.application_number, sa.first_name, sa.surname, sa.program_applied, sa.status,
            (SELECT COUNT(*) FROM applicant_requirement_status ars WHERE ars.applicant_id=sa.id AND ars.status='Verified') verified_reqs
            FROM student_applications sa ORDER BY sa.submitted_at DESC LIMIT 100");
        if ($rc) while ($row = $rc->fetch_assoc()) $clearanceData[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status' && $wconn && $staffDb) {
        $id = intval($_POST['id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $allowed = ['Shortlisted', 'Admitted', 'Rejected', 'Withdrawn'];
        if (in_array($newStatus, $allowed) && $id > 0) {
            $userId = intval($_SESSION['user_id']);
            $stmt = $wconn->prepare("UPDATE student_applications SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?");
            $stmt->bind_param("sii", $newStatus, $userId, $id);
            $stmt->execute();
            $stmt->close();
            if ($newStatus === 'Admitted') {
                $stmt2 = $wconn->prepare("SELECT * FROM student_applications WHERE id = ?");
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
                $app = $stmt2->get_result();
                if ($app && ($a = $app->fetch_assoc())) {
                    $stmt2->close();
                    $admNo = 'ADM-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
                    $stmt = $staffDb->prepare("INSERT IGNORE INTO students (first_name, surname, other_name, full_name, gender, index_number, phone, email, program, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                    if ($stmt) {
                        $fullName = trim($a['first_name'] . ' ' . ($a['other_names']??$a['other_name']??'') . ' ' . $a['surname']);
                        $idxNo = 'IND-' . str_pad($id, 5, '0', STR_PAD_LEFT);
                        $stmt->bind_param('sssssssss', $a['first_name'], $a['surname'], $a['other_names']??$a['other_name']??'', $fullName, $a['gender'], $idxNo, $a['phone'], $a['email'], $a['program_applied']);
                        $stmt->execute();
                        $studentId = $stmt->insert_id;
                        $stmt->close();
                        if ($studentId > 0) {
                            $program_applied = $a['program_applied'];
                            $academic_year = date('Y') . '/' . (date('Y')+1);
                            $stmt3 = $staffDb->prepare("INSERT IGNORE INTO student_admissions (admission_number, student_id, academic_year, program, admission_date, admission_status) VALUES (?, ?, ?, ?, CURDATE(), 'Approved')");
                            $stmt3->bind_param("siss", $admNo, $studentId, $academic_year, $program_applied);
                            $stmt3->execute();
                            $stmt3->close();
                        }
                    }
                } else {
                    $stmt2->close();
                }
            }
            $_SESSION['success'] = "Applicant status updated to '$newStatus'.";
        }
        header('Location: admission-letters.php' . ($view ? '?view=' . urlencode($view) : '')); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>@media print { body * { visibility: hidden; } .print-area, .print-area * { visibility: visible; } .print-area { position: absolute; left: 0; top: 0; width: 100%; } .no-print { display: none !important; } .main { margin-left: 0 !important; padding: 20px !important; } }
.sec-nav{display:flex;gap:2px;margin-bottom:18px;padding:6px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0}
.sec-nav a{text-decoration:none;padding:7px 16px;border-radius:6px;font-size:0.85rem;color:#475569;transition:all 0.15s}
.sec-nav a:hover{background:#e2e8f0}
.sec-nav a.active{background:#3b82f6;color:#fff;font-weight:500}
.cs{display:none}.cs.active{display:block}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">

<div class="d-flex justify-content-between align-items-center mb-2 no-print">
  <h4 class="fw-bold mb-0"><i class="fas fa-envelope-open-text me-2"></i>Admission Letters</h4>
  <span class="text-muted small"><?= date('l, d M Y') ?></span>
</div>

<nav class="sec-nav no-print">
  <a href="admission-letters.php" class="<?= !$view ? 'active' : '' ?>"><i class="fas fa-home me-1"></i>Overview</a>
  <a href="admission-letters.php?view=applications" class="<?= $view === 'applications' ? 'active' : '' ?>"><i class="fas fa-file-alt me-1"></i>Applications</a>
  <a href="admission-letters.php?view=clearance" class="<?= $view === 'clearance' ? 'active' : '' ?>"><i class="fas fa-clipboard-check me-1"></i>Clearance</a>
</nav>

<?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2 no-print"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
<?php if(!empty($_SESSION['error'])): ?><div class="alert alert-danger py-2 no-print"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

<!-- ═══ OVERVIEW ═══ -->
<section class="cs <?= !$view ? 'active' : '' ?>" id="sec-overview">
<div class="row g-3 mb-4 no-print">
  <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $pendingCount ?></h3><p>Pending Review</p></div></div></div>
  <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-user-check"></i></div><div class="stat-content"><h3><?= $shortlistedCount ?></h3><p>Shortlisted</p></div></div></div>
  <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $admittedCount ?></h3><p>Admitted</p></div></div></div>
  <div class="col-md-3"><div class="stat-card danger"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-content"><h3><?= $rejectedCount ?></h3><p>Rejected</p></div></div></div>
</div>

<div class="card mb-4">
  <div class="card-body text-center py-4">
    <i class="fas fa-envelope-open-text fa-3x mb-3" style="color:#3b82f6"></i>
    <h5 class="fw-bold">Admission Letters Management</h5>
    <p class="text-muted small mb-3">Manage student applications, generate admission letters, and track clearance status.</p>
    <div class="d-flex justify-content-center gap-2">
      <a href="admission-letters.php?view=applications" class="btn btn-sm btn-primary"><i class="fas fa-file-alt me-1"></i>View Applications</a>
      <a href="admission-letters.php?view=clearance" class="btn btn-sm btn-outline-primary"><i class="fas fa-clipboard-check me-1"></i>Check Clearance</a>
    </div>
  </div>
</div>
</section>

<!-- ═══ APPLICATIONS ═══ -->
<section class="cs <?= $view === 'applications' ? 'active' : '' ?>" id="sec-applications">
<div class="row g-3 mb-4 no-print">
  <div class="col-md-4"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $pendingCount ?></h3><p>Pending Review</p></div></div></div>
  <div class="col-md-4"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $admittedCount ?></h3><p>Admitted</p></div></div></div>
  <div class="col-md-4"><div class="stat-card danger"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-content"><h3><?= $rejectedCount ?></h3><p>Rejected</p></div></div></div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Applicants</h5>
  <button class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
</div>

<form method="GET" class="row g-2 mb-3 no-print">
  <input type="hidden" name="view" value="applications">
  <div class="col-md-5"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, application #, phone, program..." value="<?= htmlspecialchars($search) ?>"></div>
  <div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">All Status</option><option <?= $statusFilter==='Pending'?'selected':''?>>Pending</option><option <?= $statusFilter==='Shortlisted'?'selected':''?>>Shortlisted</option><option <?= $statusFilter==='Admitted'?'selected':''?>>Admitted</option><option <?= $statusFilter==='Rejected'?'selected':''?>>Rejected</option><option <?= $statusFilter==='Withdrawn'?'selected':''?>>Withdrawn</option></select></div>
  <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i> Search</button></div>
  <div class="col-md-2"><a href="admission-letters.php?view=applications" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times"></i> Clear</a></div>
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
</section>

<!-- ═══ CLEARANCE ═══ -->
<section class="cs <?= $view === 'clearance' ? 'active' : '' ?>" id="sec-clearance">
<div class="row g-3 mb-4 no-print">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <h5 class="fw-bold mb-3"><i class="fas fa-clipboard-check me-2 text-success"></i>Requirements Clearance Status</h5>
        <p class="text-muted small mb-3">Track which applicants have fulfilled all admission requirements. Total requirements: <strong><?= $totalReqs ?></strong></p>
        <?php if (empty($clearanceData)): ?>
        <div class="table-responsive">
          <table class="table table-hover small mb-0">
            <thead class="table-light"><tr><th>#</th><th>Application #</th><th>Applicant</th><th>Program</th><th>Verified Reqs</th><th>Total Reqs</th><th>Progress</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!empty($applicants)): $i=1; foreach ($applicants as $a):
                $vr = 0;
                if ($wconn) { $qvr = $wconn->query("SELECT COUNT(*) c FROM applicant_requirement_status ars JOIN student_applications sa ON ars.applicant_id=sa.id WHERE sa.id=" . intval($a['id']) . " AND ars.status='Verified'"); if ($qvr) $vr = (int)$qvr->fetch_assoc()['c']; }
                $pct = $totalReqs > 0 ? min(100, round($vr/$totalReqs*100)) : 0;
              ?><tr>
                <td><?= $i++ ?></td>
                <td><code><?= htmlspecialchars($a['application_number'] ?? 'APP-'.$a['id']) ?></code></td>
                <td><strong><?= htmlspecialchars($a['surname'] . ', ' . $a['first_name']) ?></strong></td>
                <td><?= htmlspecialchars($a['program_applied'] ?? '') ?></td>
                <td><span class="fw-bold"><?= $vr ?></span></td>
                <td><?= $totalReqs ?></td>
                <td style="min-width:120px"><div class="progress" style="height:8px"><div class="progress-bar bg-<?= $pct>=100?'success':($pct>=50?'primary':'warning') ?>" style="width:<?= $pct ?>%"></div></div><small class="text-muted"><?= $pct ?>%</small></td>
                <td><span class="badge bg-<?= $a['status']==='Admitted'?'success':($a['status']==='Rejected'?'danger':'warning text-dark') ?>"><?= htmlspecialchars($a['status']) ?></span></td>
              </tr><?php endforeach; else: ?><tr><td colspan="8" class="text-center text-muted py-3">No applicant data available for clearance tracking.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover small mb-0">
            <thead class="table-light"><tr><th>#</th><th>Application #</th><th>Applicant</th><th>Program</th><th>Verified Reqs</th><th>Total Reqs</th><th>Progress</th><th>Status</th></tr></thead>
            <tbody><?php $i=1; foreach ($clearanceData as $c): $pct = $totalReqs > 0 ? min(100, round($c['verified_reqs']/$totalReqs*100)) : 0; ?><tr>
              <td><?= $i++ ?></td>
              <td><code><?= htmlspecialchars($c['application_number'] ?? 'APP-'.$c['id']) ?></code></td>
              <td><strong><?= htmlspecialchars($c['surname'] . ', ' . $c['first_name']) ?></strong></td>
              <td><?= htmlspecialchars($c['program_applied'] ?? '') ?></td>
              <td><span class="fw-bold"><?= (int)$c['verified_reqs'] ?></span></td>
              <td><?= $totalReqs ?></td>
              <td style="min-width:120px"><div class="progress" style="height:8px"><div class="progress-bar bg-<?= $pct>=100?'success':($pct>=50?'primary':'warning') ?>" style="width:<?= $pct ?>%"></div></div><small class="text-muted"><?= $pct ?>%</small></td>
              <td><span class="badge bg-<?= $c['status']==='Admitted'?'success':($c['status']==='Rejected'?'danger':'warning text-dark') ?>"><?= htmlspecialchars($c['status']) ?></span></td>
            </tr><?php endforeach; ?></tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</section>

<!-- Applicant Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Applicant Details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="detailBody"><p class="text-muted">Loading...</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div></div></div></div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</div>
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
    }).catch(function(){});
}
function esc(s) { if (!s) return ''; let d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
</script>
</body></html>
