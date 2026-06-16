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
            <h4 class="fw-bold mb-0"><i class="fas fa-star me-2"></i>Performance Appraisal</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Staff Performance Appraisal', 'fas fa-chart-line', [
            ['icon'=>'fas fa-clipboard-check', 'label'=>'Self Assessment', 'note'=>'Staff self-review'],
            ['icon'=>'fas fa-users', 'label'=>'Peer Review', 'note'=>'Colleague feedback'],
            ['icon'=>'fas fa-star', 'label'=>'Supervisor Review', 'note'=>'Manager evaluation'],
            ['icon'=>'fas fa-trophy', 'label'=>'Goals & KPIs', 'note'=>'Target tracking'],
            ['icon'=>'fas fa-file-signature', 'label'=>'Appraisal Forms', 'note'=>'Custom forms'],
            ['icon'=>'fas fa-chart-bar', 'label'=>'Reports', 'note'=>'Performance stats'],
        ], 'Under Development'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
