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
            <h4 class="fw-bold mb-0"><i class="fas fa-bed me-2"></i>Hostel Management</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Hostel Management', 'fas fa-bed', [
            ['icon'=>'fas fa-building', 'label'=>'Room Allocation', 'note'=>'Assign rooms'],
            ['icon'=>'fas fa-door-open', 'label'=>'Room Inventory', 'note'=>'Bed availability'],
            ['icon'=>'fas fa-money-bill', 'label'=>'Hostel Fees', 'note'=>'Fee collection'],
            ['icon'=>'fas fa-exclamation-triangle', 'label'=>'Maintenance', 'note'=>'Report issues'],
            ['icon'=>'fas fa-clipboard-check', 'label'=>'Check-in/out', 'note'=>'Move in/out'],
            ['icon'=>'fas fa-chart-pie', 'label'=>'Occupancy', 'note'=>'Usage analytics'],
        ], 'Planned'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
