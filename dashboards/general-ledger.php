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
            <h4 class="fw-bold mb-0"><i class="fas fa-book me-2"></i>General Ledger</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('General Ledger', 'fas fa-book', [
            ['icon'=>'fas fa-journal-whills', 'label'=>'Chart of Accounts', 'note'=>'Account structure'],
            ['icon'=>'fas fa-exchange-alt', 'label'=>'Journal Entries', 'note'=>'Record transactions'],
            ['icon'=>'fas fa-file-invoice', 'label'=>'Trial Balance', 'note'=>'Account balances'],
            ['icon'=>'fas fa-balance-scale', 'label'=>'Income Statement', 'note'=>'P&L summary'],
            ['icon'=>'fas fa-file-alt', 'label'=>'Balance Sheet', 'note'=>'Financial position'],
            ['icon'=>'fas fa-chart-bar', 'label'=>'Audit Trail', 'note'=>'Transaction log'],
        ], 'Under Development'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
