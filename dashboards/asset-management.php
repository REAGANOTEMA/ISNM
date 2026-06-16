<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
$ctx = bootstrapStaffDashboard(['admin', 'store', 'finance']);
$user = $ctx['user'];
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:40px 32px">
  <?php renderComingSoon('Asset & Equipment Management', 'fas fa-boxes', [
    ['icon'=>'fas fa-laptop', 'label'=>'Asset Register', 'note'=>'Track all assets'],
    ['icon'=>'fas fa-tag', 'label'=>'Tagging', 'note'=>'Asset labeling'],
    ['icon'=>'fas fa-tools', 'label'=>'Maintenance', 'note'=>'Service schedules'],
    ['icon'=>'fas fa-exchange-alt', 'label'=>'Transfers', 'note'=>'Department moves'],
    ['icon'=>'fas fa-clipboard-list', 'label'=>'Inventory', 'note'=>'Stock counts'],
    ['icon'=>'fas fa-chart-pie', 'label'=>'Reports', 'note'=>'Asset analytics'],
  ], 'Under Development'); ?>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
