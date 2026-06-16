<?php
/**
 * Student Records by Set – Standalone page for all dashboards
 * Accessible to all authenticated staff roles.
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';

$ctx          = bootstrapStaffDashboard([]);
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
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Records – ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="dashboard-professional.css" rel="stylesheet">
<style>
:root { --primary: #1e3a8a; }
body { background: #f0f4f8; font-family: 'Inter', 'Segoe UI', sans-serif; }
.page-wrapper { max-width: 1400px; margin: 0 auto; padding: 20px; }
.top-bar { background: #fff; border-radius: 14px; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07); margin-bottom: 24px; }
.top-bar h1 { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; }
.top-bar h1 i { color: var(--primary); margin-right: 10px; }
.content-card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
@media print {
    .top-bar, .no-print { display: none !important; }
    .page-wrapper { padding: 0 !important; }
    .content-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
    body { background: white !important; }
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="page-wrapper" style="margin-left:260px">
    <div class="top-bar no-print">
        <div>
            <h1><i class="fas fa-users-gear"></i>Student Records</h1>
            <small class="text-muted">View students by set, program & level — <?= htmlspecialchars($user_name) ?> (<?= htmlspecialchars($user_role) ?>)</small>
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
</body>
</html>
