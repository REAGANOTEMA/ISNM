<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
$ctx = bootstrapStaffDashboard(['hr', 'admin', 'principal']);
$user = $ctx['user'];
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:40px 32px">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <?php renderComingSoon('Staff Attendance', 'fas fa-clipboard-check', [
            ['icon'=>'fas fa-fingerprint', 'label'=>'Check In/Out', 'note'=>'Daily attendance'],
            ['icon'=>'fas fa-calendar-alt', 'label'=>'Monthly Logs', 'note'=>'Attendance records'],
            ['icon'=>'fas fa-clock', 'label'=>'Late Tracking', 'note'=>'Late arrivals'],
            ['icon'=>'fas fa-calendar-week', 'label'=>'Leave Calendar', 'note'=>'Absence mgmt'],
            ['icon'=>'fas fa-file-export', 'label'=>'Reports', 'note'=>'Export attendance'],
            ['icon'=>'fas fa-chart-bar', 'label'=>'Analytics', 'note'=>'Attendance stats'],
        ], 'Under Development'); ?>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
