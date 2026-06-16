<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
$ctx = bootstrapStaffDashboard(['registrar', 'admissions', 'admin']);
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
      <?php renderComingSoon('Add New Student', 'fas fa-user-plus', [
            ['icon'=>'fas fa-file-alt', 'label'=>'Enrollment Form', 'note'=>'Student details'],
            ['icon'=>'fas fa-id-card', 'label'=>'Documents', 'note'=>'Upload docs'],
            ['icon'=>'fas fa-credit-card', 'label'=>'Fee Payment', 'note'=>'Initial payment'],
            ['icon'=>'fas fa-calendar-check', 'label'=>'Placement', 'note'=>'Class assignment'],
            ['icon'=>'fas fa-stethoscope', 'label'=>'Medical Check', 'note'=>'Health records'],
            ['icon'=>'fas fa-bed', 'label'=>'Hostel', 'note'=>'Accommodation'],
        ], 'Under Development'); ?>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
