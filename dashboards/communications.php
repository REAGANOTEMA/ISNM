<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
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
            <i class="fas fa-comments fa-3x"></i>
            <div>
              <h2 class="mb-1">Communications Center</h2>
              <p class="mb-0 opacity-75">Internal messaging, email campaigns, SMS alerts, and notification hub</p>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-semibold mb-0" style="color:var(--primary)"><i class="fas fa-list-check me-2"></i>Module Features</h5>
            <span class="badge-soon"><i class="fas fa-clock me-1"></i>Coming Soon</span>
          </div>
          <ul class="feature-list">
            <li><i class="fas fa-envelope text-primary"></i> Send individual and bulk email messages</li>
            <li><i class="fas fa-sms text-success"></i> SMS notifications for urgent alerts and reminders</li>
            <li><i class="fas fa-inbox text-warning"></i> Centralised inbox for staff student messaging</li>
            <li><i class="fas fa-tags text-info"></i> Tag based contact groups and mailing lists</li>
            <li><i class="fas fa-file-alt text-secondary"></i> Communication templates and history log</li>
          </ul>
          <hr class="my-4">
          <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i> This module is under active development. Full functionality will be available in the next system update.</p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
