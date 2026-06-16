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
            <h4 class="fw-bold mb-0"><i class="fas fa-calendar-alt me-2"></i>Timetable Management</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <?php renderComingSoon('Timetable Management', 'fas fa-calendar-alt', [
            ['icon'=>'fas fa-clock', 'label'=>'Class Schedule', 'note'=>'Lecture timings'],
            ['icon'=>'fas fa-chalkboard-teacher', 'label'=>'Lecturer Assignments', 'note'=>'Allocate teachers'],
            ['icon'=>'fas fa-users-class', 'label'=>'Room Booking', 'note'=>'Venue allocation'],
            ['icon'=>'fas fa-calendar-week', 'label'=>'Weekly View', 'note'=>'Week timetables'],
            ['icon'=>'fas fa-print', 'label'=>'Print', 'note'=>'Export timetables'],
            ['icon'=>'fas fa-bell', 'label'=>'Notifications', 'note'=>'Schedule alerts'],
        ], 'Planned'); ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
