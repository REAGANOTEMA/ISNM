<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';

$leaveRecords = [];
if ($staffDb) {
    $r = $staffDb->query("SELECT l.*, CONCAT(s.first_name,' ',s.last_name) staff_name FROM staff_leave_records l LEFT JOIN staff s ON l.staff_id = s.id ORDER BY l.created_at DESC LIMIT 50");
    if ($r && !($r === false)) {
        while ($row = $r->fetch_assoc()) $leaveRecords[] = $row;
    }
} elseif ($studentsDb) {
    $r = $studentsDb->query("SELECT * FROM staff_leave_records ORDER BY created_at DESC LIMIT 50");
    if ($r && !($r === false)) {
        while ($row = $r->fetch_assoc()) $leaveRecords[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave Management - ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
.card-section { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.page-header { border-bottom: 2px solid #e9ecef; padding-bottom: 12px; margin-bottom: 20px; }
.coming-soon-icon { font-size: 48px; color: #0d6efd; opacity: .6; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-plane me-2"></i>Leave Management</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <div class="card-section text-center py-5 mb-4">
            <div class="coming-soon-icon mb-3"><i class="fas fa-calendar-alt"></i></div>
            <h5>Leave Management</h5>
            <p class="text-muted">This module is under development. Leave applications, approval workflows, leave balances, and staff leave calendar coming soon.</p>
            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Coming Soon</span>
        </div>

        <?php if (!empty($leaveRecords)): ?>
        <div class="card-section">
            <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Recent Leave Records</h5>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <?php $cols = array_keys($leaveRecords[0]); foreach ($cols as $col): ?>
                            <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $col))) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaveRecords as $l): ?>
                        <tr>
                            <?php foreach ($l as $val): ?>
                            <td><?= htmlspecialchars($val ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">Showing <?= count($leaveRecords) ?> leave record(s).</p>
        </div>
        <?php else: ?>
        <div class="card-section">
            <div class="text-center py-4 text-muted">
                <i class="fas fa-database fa-2x mb-2"></i>
                <p class="mb-0">No leave records found in the database yet.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
