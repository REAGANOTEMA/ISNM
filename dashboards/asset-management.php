<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
$ctx = bootstrapStaffDashboard(['admin', 'store', 'finance']);
$user = $ctx['user'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Asset Management – ISNM</title>
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
