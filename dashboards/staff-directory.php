<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director','secretary','ict','hr','registrar','head']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';

$staffMembers = [];
$staffByDept = [];
if ($staffDb) {
    $r = $staffDb->query("SELECT s.*, r.role_name FROM staff s LEFT JOIN staff_roles r ON s.role_id = r.id WHERE s.status = 'Active' ORDER BY s.full_name ASC");
    if ($r) {
        while ($row = $r->fetch_assoc()) $staffMembers[] = $row;
    }
}
foreach ($staffMembers as $s) {
    $dept = $s['department'] ?? 'Other';
    if (!isset($staffByDept[$dept])) $staffByDept[$dept] = [];
    $staffByDept[$dept][] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-address-book me-2"></i>Staff Directory</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="fs-1 text-primary mb-2"><i class="fas fa-users"></i></div>
                        <h3 class="fw-bold mb-0"><?= count($staffMembers) ?></h3>
                        <small class="text-muted">Active Staff</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="fs-1 text-success mb-2"><i class="fas fa-layer-group"></i></div>
                        <h3 class="fw-bold mb-0"><?= count($staffByDept) ?></h3>
                        <small class="text-muted">Departments</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="fs-1 text-info mb-2"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h3 class="fw-bold mb-0"><?= count(array_filter($staffMembers, fn($s)=>($s['role_name']??'')==='Lecturer')) ?></h3>
                        <small class="text-muted">Lecturers</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="fs-1 text-warning mb-2"><i class="fas fa-user-tie"></i></div>
                        <h3 class="fw-bold mb-0"><?= count(array_filter($staffMembers, fn($s)=>!in_array(($s['role_name']??''), ['Lecturer','']) && !empty($s['role_name']))) ?></h3>
                        <small class="text-muted">Administration</small>
                    </div>
                </div>
            </div>
        </div>

        <?php foreach ($staffByDept as $dept => $members): ?>
        <div class="card-section mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-building me-2"></i><?= htmlspecialchars($dept) ?> <span class="badge bg-secondary ms-2"><?= count($members) ?></span></h5>
            <div class="row g-3">
                <?php foreach ($members as $s): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card border h-100 shadow-sm">
                        <div class="card-body text-center p-3">
                            <?php if (!empty($s['passport'])): ?>
                                <img src="<?= htmlspecialchars($s['passport']) ?>" class="rounded-circle mb-2" style="width:64px;height:64px;object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-2" style="width:64px;height:64px;font-size:24px;font-weight:700;">
                                    <?= strtoupper(substr($s['full_name']??'S',0,1)) ?>
                                </div>
                            <?php endif; ?>
                            <h6 class="fw-bold mb-1 small"><?= htmlspecialchars($s['full_name']??'Unknown') ?></h6>
                            <small class="text-muted d-block"><?= htmlspecialchars($s['role_name']??'Staff') ?></small>
                            <?php if (!empty($s['phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($s['phone']) ?>" class="btn btn-sm btn-outline-success mt-2 rounded-pill px-3">
                                    <i class="fas fa-phone me-1"></i>Call
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($staffMembers)): ?>
        <div class="card-section text-center py-4">
            <i class="fas fa-users fa-3x mb-3 text-muted" style="opacity:.3;"></i>
            <p class="text-muted">No active staff members found in the database.</p>
            <small class="text-muted">Run <code>sql_migration.sql</code> to populate staff data.</small>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
