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
            <h4 class="fw-bold mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Training & CPD</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Training & CPD', 'fas fa-graduation-cap', [
            ['icon'=>'fas fa-book', 'label'=>'Courses', 'note'=>'CPD programs'],
            ['icon'=>'fas fa-calendar-alt', 'label'=>'Workshops', 'note'=>'Schedule events'],
            ['icon'=>'fas fa-certificate', 'label'=>'Certifications', 'note'=>'Track certificates'],
            ['icon'=>'fas fa-chart-bar', 'label'=>'Progress', 'note'=>'Staff development'],
            ['icon'=>'fas fa-file-export', 'label'=>'Reports', 'note'=>'Training reports'],
            ['icon'=>'fas fa-globe', 'label'=>'External CPD', 'note'=>'External programs'],
        ], 'Planned'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
