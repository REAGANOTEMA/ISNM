<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
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
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-hospital-user me-2"></i>Clinical Placement</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Clinical Placement Management', 'fas fa-hospital-user', [
            ['icon'=>'fas fa-map-marker-alt', 'label'=>'Placement Sites', 'note'=>'Hospital assignments'],
            ['icon'=>'fas fa-users', 'label'=>'Student Groups', 'note'=>'Batch allocations'],
            ['icon'=>'fas fa-calendar-alt', 'label'=>'Rotations', 'note'=>'Schedule rotations'],
            ['icon'=>'fas fa-file-medical', 'label'=>'Logbooks', 'note'=>'Track progress'],
            ['icon'=>'fas fa-star', 'label'=>'Supervision', 'note'=>'Supervisor feedback'],
            ['icon'=>'fas fa-chart-bar', 'label'=>'Reports', 'note'=>'Placement reports'],
        ], 'Under Development'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
