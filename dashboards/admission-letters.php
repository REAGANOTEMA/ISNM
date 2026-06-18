<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['admissions', 'director', 'secretary']);
$conn = $ctx['staff'];
$wconn = $ctx['website'];
$user = $ctx['user'];

$letters = [];
if ($conn && $wconn) {
    $r = $conn->query("SELECT a.*, ap.application_number, ap.first_name, ap.surname, ap.program_applied, ap.submitted_at FROM student_admissions a LEFT JOIN igangaschoolofl_website_db.student_applications ap ON a.student_id = ap.id ORDER BY a.created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $letters[] = $row;
}
$pageTitle = 'Admission Letters';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?></head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-envelope-open-text me-2"></i>Admission Letters</h2><p>Manage and generate admission offer letters</p></div>
<div class="card"><div class="card-header">Admission Records</div><div class="card-body">
<?php if (empty($letters)): ?><div class="empty-state"><i class="fas fa-inbox"></i><p>No admission records yet.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>#</th><th>Admission No.</th><th>Applicant</th><th>Program</th><th>Academic Year</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php $i=1; foreach ($letters as $l): ?>
<tr><td><?= $i++ ?></td><td class="small"><?= htmlspecialchars($l['admission_number']??'') ?></td><td><?= htmlspecialchars(($l['first_name']??'') . ' ' . ($l['surname']??'')) ?></td><td><?= htmlspecialchars($l['program']??'') ?></td><td><?= htmlspecialchars($l['academic_year']??'') ?></td><td><span class="status-pill <?= strtolower($l['admission_status']??'pending') === 'approved' ? 'success' : (strtolower($l['admission_status']??'pending') === 'rejected' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($l['admission_status']??'Pending') ?></span></td><td class="small"><?= htmlspecialchars($l['created_at']??'') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
