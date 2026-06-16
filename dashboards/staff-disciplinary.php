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
      <?php renderComingSoon('Staff Disciplinary', 'fas fa-gavel', [
            ['icon'=>'fas fa-exclamation-circle', 'label'=>'Report Issue', 'note'=>'File a report'],
            ['icon'=>'fas fa-folder-open', 'label'=>'Case Files', 'note'=>'Manage cases'],
            ['icon'=>'fas fa-clipboard-list', 'label'=>'Hearings', 'note'=>'Schedule hearings'],
            ['icon'=>'fas fa-file-alt', 'label'=>'Statements', 'note'=>'Collect statements'],
            ['icon'=>'fas fa-stamp', 'label'=>'Decisions', 'note'=>'Record outcomes'],
            ['icon'=>'fas fa-archive', 'label'=>'Records', 'note'=>'Historical data'],
        ], 'Under Development'); ?>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
