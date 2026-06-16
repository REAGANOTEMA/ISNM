<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
$ctx = bootstrapStaffDashboard([]);
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
      <div class="card dev-card">
        <div class="card-header text-white">
          <div class="d-flex align-items-center gap-3 mb-1">
            <i class="fas fa-bullhorn fa-3x"></i>
            <div>
              <h2 class="mb-1">Student Announcements</h2>
              <p class="mb-0 opacity-75">Create, schedule, and manage announcements targeted to students</p>
            </div>
          </div>
        </div>
        <div class="card-body">
          <?php renderComingSoon('Student Announcements', 'fas fa-bullhorn', [
    ['icon'=>'fas fa-newspaper', 'label'=>'School News', 'note'=>'Official notices'],
    ['icon'=>'fas fa-calendar-alt', 'label'=>'Events', 'note'=>'Upcoming events'],
    ['icon'=>'fas fa-exclamation-circle', 'label'=>'Alerts', 'note'=>'Urgent notices'],
    ['icon'=>'fas fa-file-pdf', 'label'=>'Circulars', 'note'=>'Downloadable PDFs'],
    ['icon'=>'fas fa-bell', 'label'=>'Push Notifications', 'note'=>'Get notified'],
    ['icon'=>'fas fa-clock', 'label'=>'Archive', 'note'=>'Past announcements'],
], 'Under Development'); ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
