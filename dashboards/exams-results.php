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
            <h4 class="fw-bold mb-0"><i class="fas fa-graduation-cap me-2"></i>Exams & Results</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Exams & Results Management', 'fas fa-file-alt', [
            ['icon'=>'fas fa-calendar-check', 'label'=>'Exam Scheduling', 'note'=>'Create & manage exams'],
            ['icon'=>'fas fa-edit', 'label'=>'Grade Entry', 'note'=>'Enter student marks'],
            ['icon'=>'fas fa-scroll', 'label'=>'Transcripts', 'note'=>'Generate transcripts'],
            ['icon'=>'fas fa-chart-line', 'label'=>'Analytics', 'note'=>'Performance charts'],
            ['icon'=>'fas fa-clipboard-list', 'label'=>'Continuous Assessment', 'note'=>'CA scores'],
            ['icon'=>'fas fa-certificate', 'label'=>'Certificates', 'note'=>'Print certificates'],
        ], 'Under Development'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
