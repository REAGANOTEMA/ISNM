<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr', 'manager', 'director', 'head', 'nursing', 'midwifery']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$userId = (int)($_SESSION['user_id'] ?? 0);
$pageTitle = 'Professional Licenses';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_license' && $conn) {
    $staff = trim($_POST['staff_name'] ?? '');
    $lic = trim($_POST['license_number'] ?? '');
    $type = trim($_POST['license_type'] ?? '');
    $expiry = trim($_POST['expiry_date'] ?? '');
    $body = trim($_POST['issuing_body'] ?? '');
    if ($staff && $lic) {
        $stmt = $conn->prepare("INSERT INTO professional_licenses (staff_name, license_number, license_type, expiry_date, issuing_body, created_by) VALUES (?, ?, ?, NULLIF(?, ''), ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sssssi', $staff, $lic, $type, $expiry, $body, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: professional-licenses.php'); exit;
}

$licenses = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM professional_licenses ORDER BY expiry_date ASC");
    if ($r) while ($row = $r->fetch_assoc()) $licenses[] = $row;
}

$now = date('Y-m-d');
$expiring = count(array_filter($licenses, fn($l) => ($l['expiry_date'] ?? '') > $now && ($l['expiry_date'] ?? '') <= date('Y-m-d', strtotime('+90 days'))));
$expired = count(array_filter($licenses, fn($l) => ($l['expiry_date'] ?? '') < $now && ($l['expiry_date'] ?? '') !== ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-id-card"></i> Professional Licenses</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total Licenses</h6><h3><?= count($licenses) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Expiring Soon (90d)</h6><h3 class="text-warning"><?= $expiring ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Expired</h6><h3 class="text-danger"><?= $expired ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h5>Register New License</h5></div>
        <div class="card-body">
            <form method="post" class="row g-2">
                <div class="col-md-3"><input name="staff_name" class="form-control" placeholder="Staff Name" required></div>
                <div class="col-md-2"><input name="license_number" class="form-control" placeholder="License #" required></div>
                <div class="col-md-2">
                    <select name="license_type" class="form-select">
                        <option value="nursing">Nursing</option>
                        <option value="midwifery">Midwifery</option>
                        <option value="medical">Medical</option>
                        <option value="teaching">Teaching</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-2"><input name="expiry_date" class="form-control" type="date"></div>
                <div class="col-md-2"><input name="issuing_body" class="form-control" placeholder="Issuing Body"></div>
                <div class="col-md-1"><button type="submit" name="action" value="add_license" class="btn btn-primary w-100"><i class="fas fa-plus"></i></button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5>License Registry</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Staff</th><th>License #</th><th>Type</th><th>Issuing Body</th><th>Expiry</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($licenses as $l): ?>
                        <?php $exp = $l['expiry_date'] ?? ''; $isExpired = $exp && $exp < $now; $isExpiring = $exp && $exp >= $now && $exp <= date('Y-m-d', strtotime('+90 days')); ?>
                        <tr>
                            <td><?= htmlspecialchars($l['staff_name']) ?></td>
                            <td><?= htmlspecialchars($l['license_number']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($l['license_type']) ?></span></td>
                            <td><?= htmlspecialchars($l['issuing_body'] ?? '-') ?></td>
                            <td><?= $exp ?></td>
                            <td>
                                <?php if ($isExpired): ?><span class="badge bg-danger">Expired</span>
                                <?php elseif ($isExpiring): ?><span class="badge bg-warning">Expiring Soon</span>
                                <?php else: ?><span class="badge bg-success">Valid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($licenses)): ?><tr><td colspan="6" class="text-center">No licenses registered</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
