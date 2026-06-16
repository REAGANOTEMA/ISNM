<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
$ctx = bootstrapStaffDashboard([]);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recruitment - ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
.card-section { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.page-header { border-bottom: 2px solid #e9ecef; padding-bottom: 12px; margin-bottom: 20px; }
.coming-soon-icon { font-size: 48px; color: #0d6efd; opacity: .6; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-user-plus me-2"></i>Recruitment</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Staff Recruitment', 'fas fa-user-plus', [
            ['icon'=>'fas fa-bullhorn', 'label'=>'Job Postings', 'note'=>'Advertise vacancies'],
            ['icon'=>'fas fa-file-alt', 'label'=>'Applications', 'note'=>'Receive applications'],
            ['icon'=>'fas fa-filter', 'label'=>'Shortlisting', 'note'=>'Filter candidates'],
            ['icon'=>'fas fa-calendar-check', 'label'=>'Interviews', 'note'=>'Schedule interviews'],
            ['icon'=>'fas fa-file-signature', 'label'=>'Offers', 'note'=>'Generate offer letters'],
            ['icon'=>'fas fa-clipboard-list', 'label'=>'Onboarding', 'note'=>'New hire process'],
        ], 'Under Development'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
