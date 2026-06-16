<?php
require_once __DIR__ . '/includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Course Registration – Student Portal – ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="dashboards/dashboard-professional.css" rel="stylesheet">
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
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:40px 32px">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card dev-card">
        <div class="card-header text-white">
          <div class="d-flex align-items-center gap-3 mb-1">
            <i class="fas fa-pen-to-square fa-3x"></i>
            <div>
              <h2 class="mb-1">Course Registration</h2>
              <p class="mb-0 opacity-75">Register for courses each semester, view approved registrations, and track status</p>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-semibold mb-0" style="color:var(--primary)"><i class="fas fa-list-check me-2"></i>Module Features</h5>
            <span class="badge-soon"><i class="fas fa-clock me-1"></i>Coming Soon</span>
          </div>
          <ul class="feature-list">
            <li><i class="fas fa-book text-primary"></i> Browse available courses for current semester</li>
            <li><i class="fas fa-check-square text-success"></i> Select and register for courses online</li>
            <li><i class="fas fa-hourglass-half text-warning"></i> Track registration approval status</li>
            <li><i class="fas fa-history text-info"></i> View registration history across semesters</li>
            <li><i class="fas fa-print text-secondary"></i> Print course registration confirmation slip</li>
          </ul>
          <hr class="my-4">
          <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i> This module is under active development. Full functionality will be available in the next system update.</p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/includes/dashboard_footer.php'; ?>
</body>
</html>
