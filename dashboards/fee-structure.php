<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';

$feeStructures = [];
if ($studentsDb) {
    $r = $studentsDb->query("SELECT * FROM fee_structures ORDER BY created_at DESC LIMIT 50");
    if ($r && !($r === false)) {
        while ($row = $r->fetch_assoc()) $feeStructures[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-dollar-sign me-2"></i>Fee Structure Management</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <div class="card-section text-center py-5 mb-4">
            <div class="coming-soon-icon mb-3"><i class="fas fa-tools"></i></div>
            <h5>Fee Structure Management</h5>
            <p class="text-muted">This module is under development. Full CRUD for fee structures, program-based fee assignment, and bulk updates coming soon.</p>
            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Coming Soon</span>
        </div>

        <?php if (!empty($feeStructures)): ?>
        <div class="card-section">
            <h5 class="fw-bold mb-3"><i class="fas fa-table me-2"></i>Existing Fee Structures</h5>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <?php $cols = array_keys($feeStructures[0]); foreach ($cols as $col): ?>
                            <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $col))) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feeStructures as $fs): ?>
                        <tr>
                            <?php foreach ($fs as $val): ?>
                            <td><?= htmlspecialchars($val ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">Showing <?= count($feeStructures) ?> record(s) from <code>fee_structures</code>.</p>
        </div>
        <?php else: ?>
        <div class="card-section">
            <div class="text-center py-4 text-muted">
                <i class="fas fa-database fa-2x mb-2"></i>
                <p class="mb-0">No fee structures found in the database yet.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
