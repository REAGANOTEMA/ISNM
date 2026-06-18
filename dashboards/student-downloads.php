<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'secretary', 'registrar', 'ict']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'Student Downloads';

$downloads = [];
foreach ([$studentsConn, $conn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM student_downloads ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $downloads[] = $row; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-download"></i> Student Downloads</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Downloads</h6><h3><?= count($downloads) ?></h3></div></div></div>
    </div>
    <div class="card">
        <div class="card-header"><h5>Download History</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Student</th><th>Document</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($downloads as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['student_name'] ?? $d['student_id'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($d['document_name'] ?? $d['file_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($d['document_type'] ?? $d['type'] ?? '-') ?></td>
                            <td><?= $d['created_at'] ?? '-' ?></td>
                            <td><span class="badge bg-success">Downloaded</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($downloads)): ?><tr><td colspan="5" class="text-center">No download records</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
