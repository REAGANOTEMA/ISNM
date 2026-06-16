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
            <h4 class="fw-bold mb-0"><i class="fas fa-file-contract me-2"></i>Contract Management</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Contract Management', 'fas fa-file-signature', [
    ['icon'=>'fas fa-file-contract', 'label'=>'Contracts', 'note'=>'Staff contracts'],
    ['icon'=>'fas fa-calendar-alt', 'label'=>'Renewals', 'note'=>'Contract expiry'],
    ['icon'=>'fas fa-clock', 'label'=>'Probation', 'note'=>'Probation tracking'],
    ['icon'=>'fas fa-file-pdf', 'label'=>'Templates', 'note'=>'Contract templates'],
    ['icon'=>'fas fa-clipboard-check', 'label'=>'Compliance', 'note'=>'Policy checks'],
    ['icon'=>'fas fa-archive', 'label'=>'Archive', 'note'=>'Past contracts'],
], 'Under Development'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
