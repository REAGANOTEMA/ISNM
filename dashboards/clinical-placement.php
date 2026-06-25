<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';
$ctx = bootstrapStaffDashboard(['nursing','midwifery','head','lecturer','director']);
$user = $ctx['user'];
$staffConn = getStaffConnection();
$studentsConn = getStudentsConnection();
$conn = $studentsConn ?: $staffConn;
$pageTitle = 'Clinical Placement';

$dept = $_GET['dept'] ?? '';
$view = $_GET['view'] ?? '';

$where = "1=1";
if ($dept === 'nursing') {
    $where .= " AND (cp.department='Nursing' OR s.program LIKE '%Nursing%' OR s.program LIKE '%Certificate in Nursing%')";
} elseif ($dept === 'midwifery') {
    $where .= " AND (cp.department='Midwifery' OR s.program LIKE '%Midwifery%')";
}
if ($view === 'assessment') {
    $where .= " AND cp.status IN ('In Progress','Scheduled')";
}
if ($view === 'antenatal') {
    $where .= " AND (cp.rotation_area LIKE '%Antenatal%' OR cp.rotation_area LIKE '%ANC%')";
} elseif ($view === 'delivery') {
    $where .= " AND (cp.rotation_area LIKE '%Delivery%' OR cp.rotation_area LIKE '%Labor%' OR cp.rotation_area LIKE '%L&D%')";
} elseif ($view === 'postnatal') {
    $where .= " AND (cp.rotation_area LIKE '%Postnatal%' OR cp.rotation_area LIKE '%PNC%')";
} elseif ($view === 'fp') {
    $where .= " AND (cp.rotation_area LIKE '%Family Planning%' OR cp.rotation_area LIKE '%FP%')";
}

$total = 0; $midwifery_total = 0; $nursing_total = 0; $assessment_count = 0;
$placements = []; $reports = [];
$active_dept = $dept;

if ($conn) {
    $qr = $conn->query("SELECT COUNT(*) c FROM clinical_placements cp LEFT JOIN students s ON cp.student_id=s.id WHERE $where"); if ($qr) $total = (int)$qr->fetch_assoc()['c'];
    if (!$dept) {
        $qr = $conn->query("SELECT COUNT(*) c FROM clinical_placements cp LEFT JOIN students s ON cp.student_id=s.id WHERE cp.department='Nursing' OR s.program LIKE '%Nursing%'"); if ($qr) $nursing_total = (int)$qr->fetch_assoc()['c'];
        $qr = $conn->query("SELECT COUNT(*) c FROM clinical_placements cp LEFT JOIN students s ON cp.student_id=s.id WHERE cp.department='Midwifery' OR s.program LIKE '%Midwifery%'"); if ($qr) $midwifery_total = (int)$qr->fetch_assoc()['c'];
    }
    $qr = $conn->query("SELECT COUNT(*) c FROM clinical_placements WHERE status IN('In Progress','Scheduled') AND $where"); if ($qr) $assessment_count = (int)$qr->fetch_assoc()['c'];
    $r = $conn->query("SELECT cp.*, CONCAT(s.first_name,' ',s.last_name) student_name, s.program FROM clinical_placements cp LEFT JOIN students s ON cp.student_id=s.id WHERE $where ORDER BY cp.start_date DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $placements[] = $row;

    if ($view === 'assessment') {
        $rr = $conn->query("SELECT cp.*, CONCAT(s.first_name,' ',s.last_name) student_name, s.program, cp.supervisor_notes FROM clinical_placements cp LEFT JOIN students s ON cp.student_id=s.id WHERE $where ORDER BY cp.start_date DESC");
        if ($rr) while ($row = $rr->fetch_assoc()) $reports[] = $row;
    }
}

$sectionLabel = 'All Placements';
if ($dept === 'nursing' && !$view) $sectionLabel = 'Nursing Clinical Logbook';
elseif ($dept === 'midwifery' && !$view) $sectionLabel = 'Midwifery Clinical Placements';
elseif ($view === 'assessment') $sectionLabel = 'Practical Assessment';
elseif ($view === 'antenatal') $sectionLabel = 'Antenatal Care';
elseif ($view === 'delivery') $sectionLabel = 'Labor & Delivery';
elseif ($view === 'postnatal') $sectionLabel = 'Postnatal Care';
elseif ($view === 'fp') $sectionLabel = 'Family Planning';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.clin-nav{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:20px;padding:8px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0}
.clin-nav a{text-decoration:none;padding:6px 14px;border-radius:6px;font-size:0.82rem;color:#475569;transition:all 0.15s;white-space:nowrap}
.clin-nav a:hover{background:#e2e8f0;color:#1e293b}
.clin-nav a.active{background:#3b82f6;color:#fff;font-weight:500}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-hospital-user me-2"></i><?= htmlspecialchars($sectionLabel) ?></h4>
    <span class="text-muted small"><?= date('l, d M Y') ?></span>
  </div>

  <nav class="clin-nav">
    <a href="clinical-placement.php" class="<?= !$dept && !$view ? 'active' : '' ?>"><i class="fas fa-th-list me-1"></i>All</a>
    <a href="clinical-placement.php?dept=nursing" class="<?= $dept === 'nursing' && !$view ? 'active' : '' ?>"><i class="fas fa-notes-medical me-1"></i>Nursing Logbook</a>
    <a href="clinical-placement.php?dept=midwifery" class="<?= $dept === 'midwifery' && !$view ? 'active' : '' ?>"><i class="fas fa-female me-1"></i>Midwifery</a>
    <a href="clinical-placement.php?view=assessment" class="<?= $view === 'assessment' ? 'active' : '' ?>"><i class="fas fa-clipboard-check me-1"></i>Assessment</a>
    <?php if ($dept === 'midwifery' || $view): ?>
    <a href="clinical-placement.php?dept=midwifery&view=antenatal" class="<?= $view === 'antenatal' ? 'active' : '' ?>"><i class="fas fa-baby me-1"></i>Antenatal</a>
    <a href="clinical-placement.php?dept=midwifery&view=delivery" class="<?= $view === 'delivery' ? 'active' : '' ?>"><i class="fas fa-ambulance me-1"></i>Delivery</a>
    <a href="clinical-placement.php?dept=midwifery&view=postnatal" class="<?= $view === 'postnatal' ? 'active' : '' ?>"><i class="fas fa-heartbeat me-1"></i>Postnatal</a>
    <a href="clinical-placement.php?dept=midwifery&view=fp" class="<?= $view === 'fp' ? 'active' : '' ?>"><i class="fas fa-hand-holding-heart me-1"></i>Family Planning</a>
    <?php endif; ?>
  </nav>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clipboard-list"></i></div><div class="stat-content"><h3><?= $total ?></h3><p>Total</p></div></div></div>
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="fas fa-play-circle"></i></div><div class="stat-content"><h3><?= $assessment_count ?></h3><p>Active/Scheduled</p></div></div></div>
    <?php if (!$dept): ?>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="fas fa-notes-medical"></i></div><div class="stat-content"><h3><?= $nursing_total ?></h3><p>Nursing</p></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="fas fa-female"></i></div><div class="stat-content"><h3><?= $midwifery_total ?></h3><p>Midwifery</p></div></div></div>
    <?php endif; ?>
  </div>

  <div class="content-section">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Placement Records</h5>
      <?php if ($view === 'assessment' && !empty($reports)): ?>
      <button class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="fas fa-print me-1"></i>Print Report</button>
      <?php endif; ?>
    </div>

    <?php if ($view === 'assessment'): ?>
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="fw-bold"><i class="fas fa-clipboard-check me-2 text-primary"></i>Practical Assessment Summary</h6>
        <?php if (!empty($reports)): ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover small mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Program</th><th>Site</th><th>Duration</th><th>Supervisor Notes</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($reports as $p): ?><tr>
              <td><strong><?= htmlspecialchars($p['student_name'] ?? 'Unknown') ?></strong></td>
              <td><?= htmlspecialchars($p['program'] ?? '-') ?></td>
              <td><?= htmlspecialchars($p['placement_site'] ?? '-') ?></td>
              <td><?= date('d M', strtotime($p['start_date'])) ?> - <?= $p['end_date'] ? date('d M Y', strtotime($p['end_date'])) : 'Ongoing' ?></td>
              <td><small><?= htmlspecialchars(substr($p['supervisor_notes'] ?? '', 0, 80)) ?></small></td>
              <td><span class="badge <?= $p['status']==='Completed'?'bg-success':($p['status']==='In Progress'?'bg-primary':'bg-warning text-dark') ?>"><?= htmlspecialchars($p['status']) ?></span></td>
            </tr><?php endforeach; ?></tbody>
          </table>
        </div>
        <?php else: ?><p class="text-muted text-center py-3 mb-0">No assessment records found.</p><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark"><tr><th>Student Name</th><th>Program</th><th>Placement Site</th><th>Start Date</th><th>End Date</th><th>Rotation Area</th><th>Status</th></tr></thead>
        <tbody><?php if (empty($placements)): ?><tr><td colspan="7" class="text-muted text-center py-3">No placements found.</td></tr><?php else: foreach ($placements as $p): ?><tr>
          <td><strong><?= htmlspecialchars($p['student_name'] ?? 'Unknown') ?></strong></td>
          <td><?= htmlspecialchars($p['program'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['placement_site'] ?? '-') ?></td>
          <td><?= date('d M Y', strtotime($p['start_date'])) ?></td>
          <td><?= $p['end_date'] ? date('d M Y', strtotime($p['end_date'])) : '-' ?></td>
          <td><?= htmlspecialchars($p['rotation_area'] ?? '-') ?></td>
          <td><span class="badge <?= $p['status']==='Completed'?'bg-success':($p['status']==='In Progress'?'bg-primary':'bg-warning text-dark') ?>"><?= htmlspecialchars($p['status']) ?></span></td>
        </tr><?php endforeach; endif; ?></tbody>
      </table>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
