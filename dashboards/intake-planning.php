<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['admissions', 'director', 'secretary', 'registrar']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$intakes = [];
if ($conn) {
    $r = $conn->query("SELECT YEAR(admission_date) AS intake_year, program, COUNT(*) AS student_count FROM students GROUP BY YEAR(admission_date), program ORDER BY intake_year DESC, student_count DESC");
    if ($r) while ($row = $r->fetch_assoc()) $intakes[] = $row;
}
$programs = [];
if ($conn) {
    $r = $conn->query("SELECT id, program_name, program_code, duration_years FROM academic_programs WHERE status='Active' ORDER BY program_name");
    if ($r) while ($row = $r->fetch_assoc()) $programs[] = $row;
}
$pageTitle = 'Intake Planning';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?></head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-calendar-plus me-2"></i>Intake Planning</h2><p>Plan and manage student intakes across programs</p></div>
<div class="row g-4">
<div class="col-md-8"><div class="card"><div class="card-header">Intake History</div><div class="card-body">
<?php if (empty($intakes)): ?><div class="empty-state"><i class="fas fa-database"></i><p>No intake data available.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Year</th><th>Program</th><th>Students</th></tr></thead><tbody>
<?php foreach ($intakes as $i): ?>
<tr><td><strong><?= htmlspecialchars($i['intake_year']) ?></strong></td><td><?= htmlspecialchars($i['program']) ?></td><td><span class="badge bg-primary"><?= (int)$i['student_count'] ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-md-4"><div class="card"><div class="card-header">Active Programs</div><div class="card-body">
<?php if (empty($programs)): ?><p class="text-muted small">No programs configured.</p>
<?php else: ?>
<?php foreach ($programs as $p): ?>
<div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
<div><strong class="small"><?= htmlspecialchars($p['program_name']) ?></strong><br><span class="text-muted small"><?= htmlspecialchars($p['program_code']) ?> &middot; <?= (int)$p['duration_years'] ?> yrs</span></div>
<span class="badge bg-info"><?= htmlspecialchars($p['program_code']) ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></div></div></div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
