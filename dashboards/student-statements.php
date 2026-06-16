<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';
require_once __DIR__ . '/../includes/module_coming_soon.php';
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
            <h4 class="fw-bold mb-0"><i class="fas fa-file-invoice me-2"></i>Student Statements</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Student Fee Statements', 'fas fa-file-invoice', [
            ['icon'=>'fas fa-file-alt', 'label'=>'Fee Statement', 'note'=>'Full fee breakdown'],
            ['icon'=>'fas fa-credit-card', 'label'=>'Payment History', 'note'=>'Payment records'],
            ['icon'=>'fas fa-balance-scale', 'label'=>'Balance Summary', 'note'=>'Outstanding fees'],
            ['icon'=>'fas fa-print', 'label'=>'Print Statement', 'note'=>'Printable version'],
            ['icon'=>'fas fa-download', 'label'=>'Download PDF', 'note'=>'Export statement'],
            ['icon'=>'fas fa-envelope', 'label'=>'Email Statement', 'note'=>'Send to student'],
        ]); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
