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
            <h4 class="fw-bold mb-0"><i class="fas fa-user-plus me-2"></i>Recruitment</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Staff Recruitment', 'fas fa-user-plus', [
            ['icon'=>'fas fa-bullhorn', 'label'=>'Job Postings', 'note'=>'Advertise vacancies'],
            ['icon'=>'fas fa-file-alt', 'label'=>'Applications', 'note'=>'Receive applications'],
            ['icon'=>'fas fa-filter', 'label'=>'Shortlisting', 'note'=>'Filter candidates'],
            ['icon'=>'fas fa-calendar-check', 'label'=>'Interviews', 'note'=>'Schedule interviews'],
            ['icon'=>'fas fa-file-signature', 'label'=>'Offers', 'note'=>'Generate offer letters'],
            ['icon'=>'fas fa-clipboard-list', 'label'=>'Onboarding', 'note'=>'New hire process'],
        ], 'Under Development'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
