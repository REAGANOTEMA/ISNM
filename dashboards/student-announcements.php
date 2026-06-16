<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Announcements – ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="dashboard-professional.css" rel="stylesheet">
<style>
:root{--primary:#2c5f8a;--accent:#1a9e6e}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif}
.dev-card{border:none;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08)}
.dev-card .card-header{background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:28px 32px;border:none}
.dev-card .card-header h2{font-weight:700;letter-spacing:-.5px}
.dev-card .card-body{padding:36px 32px}
.dev-card .feature-list{list-style:none;padding:0;margin:0}
.dev-card .feature-list li{padding:10px 0;border-bottom:1px solid #e9ecef;display:flex;align-items:center;gap:12px;font-size:.95rem}
.dev-card .feature-list li:last-child{border-bottom:none}
.dev-card .feature-list li i{width:20px;text-align:center}
.badge-soon{background:#fef3c7;color:#92400e;font-size:.7rem;padding:4px 14px;border-radius:20px;font-weight:600}
</style>
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
