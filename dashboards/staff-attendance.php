<?php
$pageTitle = 'Staff Attendance';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['hr','manager','director','principal','admin']);
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();

$presentToday = 0; $absent = 0; $onLeave = 0; $late = 0;
$attendance = [];

if ($conn) {
    $today = date('Y-m-d');
    $r1 = $conn->query("SELECT COUNT(*) c FROM attendance WHERE date='$today' AND status='Present'");
    if ($r1) $presentToday = (int)$r1->fetch_assoc()['c'];
    $r2 = $conn->query("SELECT COUNT(*) c FROM attendance WHERE date='$today' AND status='Absent'");
    if ($r2) $absent = (int)$r2->fetch_assoc()['c'];
    $r3 = $conn->query("SELECT COUNT(*) c FROM attendance WHERE date='$today' AND status='On Leave'");
    if ($r3) $onLeave = (int)$r3->fetch_assoc()['c'];
    $r4 = $conn->query("SELECT COUNT(*) c FROM attendance WHERE date='$today' AND status='Late'");
    if ($r4) $late = (int)$r4->fetch_assoc()['c'];
    $a = $conn->query("SELECT a.*, s.full_name staff_name FROM attendance a LEFT JOIN staff s ON a.staff_id=s.id WHERE a.date='$today' ORDER BY a.check_in ASC");
    if ($a) $attendance = $a->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-check me-2"></i>Staff Attendance</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-content"><h3><?= number_format($presentToday) ?></h3><p>Present Today</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fas fa-user-times"></i></div>
                <div class="stat-content"><h3><?= number_format($absent) ?></h3><p>Absent</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-plane"></i></div>
                <div class="stat-content"><h3><?= number_format($onLeave) ?></h3><p>On Leave</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-content"><h3><?= number_format($late) ?></h3><p>Late</p></div>
            </div>
        </div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Today's Attendance (<?= date('d M Y') ?>)</h5>
        <?php if (empty($attendance)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No attendance records for today.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Staff Name</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $at): ?>
                    <tr>
                        <td><?= htmlspecialchars($at['staff_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($at['date']) ?></td>
                        <td><?= htmlspecialchars($at['check_in'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($at['check_out'] ?? '-') ?></td>
                        <td>
                            <?php $sc = $at['status'] === 'Present' ? 'success' : ($at['status'] === 'Absent' ? 'danger' : ($at['status'] === 'Late' ? 'warning text-dark' : ($at['status'] === 'On Leave' ? 'info' : 'secondary'))); ?>
                            <span class="badge bg-<?= $sc ?>"><?= htmlspecialchars($at['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
