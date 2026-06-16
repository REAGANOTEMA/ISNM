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
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2"></i>Financial Reports</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Financial Reports', 'fas fa-chart-pie', [
            ['icon'=>'fas fa-file-invoice-dollar', 'label'=>'Income Reports', 'note'=>'Revenue analysis'],
            ['icon'=>'fas fa-file-invoice', 'label'=>'Expense Reports', 'note'=>'Cost tracking'],
            ['icon'=>'fas fa-balance-scale', 'label'=>'Budget vs Actual', 'note'=>'Variance analysis'],
            ['icon'=>'fas fa-calendar-alt', 'label'=>'Periodic Reports', 'note'=>'Monthly/Yearly'],
            ['icon'=>'fas fa-file-export', 'label'=>'Export', 'note'=>'PDF/Excel export'],
            ['icon'=>'fas fa-chart-line', 'label'=>'Trend Analysis', 'note'=>'Financial trends'],
        ], 'Under Development'); ?>

        <div class="card-section">
            <h5 class="fw-bold mb-3"><i class="fas fa-filter me-2"></i>Date Range Filter (Preview)</h5>
            <form class="row g-3" onsubmit="event.preventDefault();">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Report Type</label>
                    <select class="form-select">
                        <option>Revenue Report</option>
                        <option>Expense Report</option>
                        <option>Balance Sheet</option>
                        <option>Profit & Loss</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" disabled><i class="fas fa-search me-1"></i>Generate</button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" disabled><i class="fas fa-file-pdf me-1"></i>Export</button>
                </div>
            </form>
            <p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle me-1"></i>Filter controls are shown as a preview and will be functional in the next release.</p>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
