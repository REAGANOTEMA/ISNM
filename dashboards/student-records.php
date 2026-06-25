<?php
/**
 * Student Records by Set – Standalone page for all dashboards
 * Accessible to all authenticated staff roles.
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';

$ctx          = bootstrapStaffDashboard(['registrar','director','academics','lecturer','head','principal']);
$auth_service = $ctx['auth'];
$user         = $ctx['user'];
$students_conn = $ctx['students'];
$staff_conn   = $ctx['staff'];
$user_name    = $user['full_name'] ?? 'User';
$user_role    = $_SESSION['role'] ?? '';

$isSuperAdmin = $auth_service->hasFullInstitutionAccess($user_role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-wrapper" style="margin-left:270px">
    <div class="top-bar no-print">
        <div>
            <h1><i class="fas fa-users-gear"></i>Student Records</h1>
            <small class="text-muted">View students by set, program & level , <?= htmlspecialchars($user_name) ?> (<?= htmlspecialchars($user_role) ?>)</small>
        </div>
        <div class="d-flex gap-2">
            <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>
    </div>

    <div class="content-card">
        <?php
        renderStudentSetViewer($students_conn, [
            'title'       => $isSuperAdmin ? 'All Student Records – Full Institution View' : 'Student Records by Set',
            'icon'        => $isSuperAdmin ? 'fa-users-gear' : 'fa-users',
            'super_admin' => $isSuperAdmin,
            'show_all'    => $isSuperAdmin,
        ]);
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
