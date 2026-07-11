<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar', 'academics', 'director', 'principal']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'Graduation Management';

// Load graduation candidates
$candidates = [];
$tables = ['registrar_graduation', 'graduation_candidates'];
foreach ($tables as $tbl) {
    $r = $conn->query("SELECT * FROM $tbl ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows > 0) {
        while ($row = $r->fetch_assoc()) {
            $row['_source'] = $tbl;
            $candidates[] = $row;
        }
        break;
    }
}
if (empty($candidates) && $studentsConn) {
    $r = $studentsConn->query("SELECT * FROM graduation_candidates ORDER BY created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $candidates[] = $row;
}

$total = count($candidates);
$approved = count(array_filter($candidates, fn($c) => ($c['status'] ?? '') === 'approved'));
$pending = count(array_filter($candidates, fn($c) => ($c['status'] ?? '') === 'pending' || !($c['status'] ?? '')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-graduation-cap"></i> Graduation Management</h1><button onclick="window.print()" class="btn btn-sm btn-outline-secondary float-end"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Candidates</h6><h3><?= $total ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Approved</h6><h3><?= $approved ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Pending</h6><h3><?= $pending ?></h3></div></div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <h5>Graduation Candidates</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Student Name</th><th>Program</th><th>Index Number</th><th>Award</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($candidates as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['student_name'] ?? $c['full_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['program'] ?? $c['program_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['index_number'] ?? $c['student_id'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['award'] ?? $c['award_title'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($c['status'] ?? 'pending') === 'approved' ? 'success' : 'warning' ?>"><?= $c['status'] ?? 'pending' ?></span></td>
                            <td><?= $c['created_at'] ?? $c['updated_at'] ?? '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($candidates)): ?><tr><td colspan="6" class="text-center">No graduation candidates found</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
