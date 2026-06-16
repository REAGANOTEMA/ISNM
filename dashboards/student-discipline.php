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
            <h4 class="fw-bold mb-0"><i class="fas fa-gavel me-2"></i>Student Discipline</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Student Discipline', 'fas fa-gavel', [
    ['icon'=>'fas fa-exclamation-triangle', 'label'=>'Report Incident', 'note'=>'File a report'],
    ['icon'=>'fas fa-clipboard-list', 'label'=>'Case Tracking', 'note'=>'Follow cases'],
    ['icon'=>'fas fa-file-alt', 'label'=>'Statements', 'note'=>'Submit statements'],
    ['icon'=>'fas fa-balance-scale', 'label'=>'Hearings', 'note'=>'Case review'],
    ['icon'=>'fas fa-stamp', 'label'=>'Outcomes', 'note'=>'View decisions'],
    ['icon'=>'fas fa-chart-bar', 'label'=>'Statistics', 'note'=>'Discipline stats'],
], 'Under Development'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
