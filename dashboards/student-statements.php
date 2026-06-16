<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';
require_once __DIR__ . '/../includes/module_coming_soon.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Statements - ISNM</title>
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
            <h4 class="fw-bold mb-0"><i class="fas fa-file-invoice me-2"></i>Student Statements</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Student Fee Statements', 'fas fa-file-invoice', [
            ['icon'=>'fas fa-file-alt', 'label'=>'Fee Statement', 'note'=>'Full fee breakdown'],
            ['icon'=>'fas fa-credit-card', 'label'=>'Payment History', 'note'=>'Payment records'],
            ['icon'=>'fas fa-balance-scale', 'label'=>'Balance Summary', 'note'=>'Outstanding fees'],
            ['icon'=>'fas fa-print', 'label'=>'Print Statement', 'note'=>'Printable version'],
            ['icon'=>'fas fa-download', 'label'=>'Download PDF', 'note'=>'Export statement'],
            ['icon'=>'fas fa-envelope', 'label'=>'Email Statement', 'note'=>'Send to student'],
        ]); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
