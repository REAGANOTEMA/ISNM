<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Performance Appraisal - ISNM</title>
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
            <h4 class="fw-bold mb-0"><i class="fas fa-star me-2"></i>Performance Appraisal</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <div class="card-section text-center py-5">
            <div class="coming-soon-icon mb-3"><i class="fas fa-chart-simple"></i></div>
            <h5>Performance Appraisal</h5>
            <p class="text-muted">This module is under development. Staff evaluations, goal setting, performance reviews, 360-degree feedback, and appraisal reports coming soon.</p>
            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Coming Soon</span>
            <hr class="my-4" style="max-width:400px;margin:auto;">
            <div class="row g-3 mt-2" style="max-width:600px;margin:auto;">
                <div class="col-4"><div class="border rounded p-3 bg-light"><i class="fas fa-bullseye fa-2x text-primary mb-2"></i><br><small>Goal Setting</small></div></div>
                <div class="col-4"><div class="border rounded p-3 bg-light"><i class="fas fa-clipboard-check fa-2x text-primary mb-2"></i><br><small>Evaluations</small></div></div>
                <div class="col-4"><div class="border rounded p-3 bg-light"><i class="fas fa-file-signature fa-2x text-primary mb-2"></i><br><small>Reviews</small></div></div>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
