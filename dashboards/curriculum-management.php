<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'academics', 'principal', 'head']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';

if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_curriculum_development` (id INT AUTO_INCREMENT PRIMARY KEY, program_code VARCHAR(50) NOT NULL, revision_number INT DEFAULT 1, academic_year VARCHAR(20) DEFAULT NULL, description TEXT, status VARCHAR(50) DEFAULT 'Draft', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_program (program_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$curricula = [];
if ($conn) {
    $r = $conn->query("SELECT c.*, p.program_name FROM academic_curriculum_development c LEFT JOIN academic_programs p ON c.program_code = p.program_code ORDER BY c.created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $curricula[] = $row;
}
$courses = [];
if ($conn) {
    $r = $conn->query("SELECT course_code, course_title, credits, program_code FROM academic_course_catalog ORDER BY course_title LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $courses[] = $row;
}
$pageTitle = 'Curriculum Management';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-book-open me-2"></i>Curriculum Management <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button></h2><p>Develop and manage academic curriculum, course catalogs, and program structures</p></div>
<div class="row g-4">
<div class="col-lg-7"><div class="card"><div class="card-header">Curriculum Development</div><div class="card-body">
<?php if (empty($curricula)): ?><div class="empty-state"><i class="fas fa-book"></i><p>No curriculum entries yet.</p></div>
<?php else: ?>
<div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchHZLE" type="text" placeholder="Search..." onkeyup="filterTable('srchHZLE','tblHZLE')"></div>
<div class="table-responsive"><table id="tblHZLE" class="table table-hover"><thead><tr><th>Program</th><th>Revision</th><th>Academic Year</th><th>Status</th><th>Created</th></tr></thead><tbody>
<?php foreach ($curricula as $c): ?>
<tr><td><?= htmlspecialchars($c['program_name']??$c['program_code']) ?></td><td>v<?= (int)($c['revision_number']??1) ?></td><td><?= htmlspecialchars($c['academic_year']??'') ?></td><td><span class="status-pill <?= ($c['status']??'Draft') === 'Approved' ? 'success' : (($c['status']??'') === 'Implemented' ? 'info' : 'warning') ?>"><?= htmlspecialchars($c['status']??'Draft') ?></span></td><td class="small"><?= htmlspecialchars($c['created_at']??'') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-header">Course Catalog (<?= count($courses) ?>)</div><div class="card-body" style="max-height:400px;overflow-y:auto">
<?php if (empty($courses)): ?><p class="text-muted small text-center py-3">No courses defined.</p>
<?php else: ?>
<?php foreach ($courses as $c): ?>
<div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
<div><strong class="small"><?= htmlspecialchars($c['course_code']) ?></strong><br><span class="text-muted small"><?= htmlspecialchars($c['course_title']) ?></span></div>
<span class="badge bg-primary"><?= (int)$c['credits'] ?> cr</span>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></div></div></div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
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
</body></html>
