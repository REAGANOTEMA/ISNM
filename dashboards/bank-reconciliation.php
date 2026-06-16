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
            <h4 class="fw-bold mb-0"><i class="fas fa-university me-2"></i>Bank Reconciliation</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Bank Reconciliation', 'fas fa-university', [
    ['icon'=>'fas fa-file-invoice', 'label'=>'Statements', 'note'=>'Upload statements'],
    ['icon'=>'fas fa-exchange-alt', 'label'=>'Matching', 'note'=>'Auto-reconcile'],
    ['icon'=>'fas fa-exclamation-triangle', 'label'=>'Discrepancies', 'note'=>'Flag issues'],
    ['icon'=>'fas fa-check-double', 'label'=>'Verification', 'note'=>'Verify entries'],
    ['icon'=>'fas fa-file-export', 'label'=>'Reports', 'note'=>'Reconciliation rep.'],
    ['icon'=>'fas fa-history', 'label'=>'History', 'note'=>'Past reconciliations'],
], 'Under Development'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
