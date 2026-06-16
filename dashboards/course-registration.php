<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
$ctx = bootstrapStaffDashboard(['registrar', 'lecturers']);
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
            <i class="fas fa-book-open fa-3x"></i>
            <div>
              <h2 class="mb-1">Course Registration</h2>
              <p class="mb-0 opacity-75">Manage student course registrations, approvals, and academic plans</p>
            </div>
          </div>
        </div>
        <div class="card-body">
          <?php renderComingSoon('Course Registration', 'fas fa-user-plus', [
    ['icon'=>'fas fa-book', 'label'=>'Course Catalog', 'note'=>'Available courses'],
    ['icon'=>'fas fa-clipboard-list', 'label'=>'Registration', 'note'=>'Select courses'],
    ['icon'=>'fas fa-calendar-check', 'label'=>'Schedule', 'note'=>'View timetable'],
    ['icon'=>'fas fa-credit-card', 'label'=>'Fee Check', 'note'=>'Payment status'],
    ['icon'=>'fas fa-file-alt', 'label'=>'Form Print', 'note'=>'Registration form'],
    ['icon'=>'fas fa-history', 'label'=>'My Registrations', 'note'=>'Past semesters'],
], 'Under Development'); ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
