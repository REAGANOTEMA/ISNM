<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';

$attendance = [];
if ($studentsDb) {
    $r = $studentsDb->query("SELECT a.*, CONCAT(s.first_name,' ',s.surname) student_name, s.student_number FROM student_attendance a LEFT JOIN students s ON a.student_id = s.id ORDER BY a.attendance_date DESC LIMIT 50");
    if ($r && !($r === false)) {
        while ($row = $r->fetch_assoc()) $attendance[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Attendance - ISNM</title>
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
            <h4 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2"></i>Student Attendance</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <div class="card-section text-center py-5 mb-4">
            <div class="coming-soon-icon mb-3"><i class="fas fa-clipboard-list"></i></div>
            <h5>Student Attendance</h5>
            <p class="text-muted">This module is under development. Class attendance tracking, biometric integration, attendance reports, and analytics coming soon.</p>
            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Coming Soon</span>
        </div>

        <?php if (!empty($attendance)): ?>
        <div class="card-section">
            <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Recent Attendance Records</h5>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Student #</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $a): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($a['student_name'] ?? '—') ?></strong></td>
                            <td><code><?= htmlspecialchars($a['student_number'] ?? '—') ?></code></td>
                            <td><?= htmlspecialchars($a['attendance_date'] ?? '—') ?></td>
                            <td>
                                <?php $st = $a['status'] ?? 'Unknown'; ?>
                                <span class="badge <?= $st === 'Present' ? 'bg-success' : ($st === 'Absent' ? 'bg-danger' : ($st === 'Late' ? 'bg-warning text-dark' : 'bg-secondary')) ?>">
                                    <?= htmlspecialchars($st) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($a['attendance_type'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($a['remarks'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">Showing <?= count($attendance) ?> recent attendance record(s).</p>
        </div>
        <?php else: ?>
        <div class="card-section">
            <div class="text-center py-4 text-muted">
                <i class="fas fa-database fa-2x mb-2"></i>
                <p class="mb-0">No attendance records found in the database yet.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
