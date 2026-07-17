<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
$ctx = bootstrapStaffDashboard(['director', 'academics', 'principal', 'head']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $conn) {
    if (!verifyCsrfToken()) { die('Invalid CSRF token'); }
    if ($_POST['action'] === 'add_requirement') {
        $name = $_POST['name'] ?? '';
        $desc = $_POST['description'] ?? '';
        $deadline = $_POST['deadline'] ?? '';
        if ($deadline) {
            $stmt = $conn->prepare("INSERT INTO compliance_requirements (requirement_name, description, deadline, status, created_by) VALUES (?, ?, ?, 'pending', ?)");
            $stmt->bind_param("sssi", $name, $desc, $deadline, $userId);
        } else {
            $stmt = $conn->prepare("INSERT INTO compliance_requirements (requirement_name, description, deadline, status, created_by) VALUES (?, ?, NULL, 'pending', ?)");
            $stmt->bind_param("ssi", $name, $desc, $userId);
        }
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $stmt->close();
        header('Location: accreditation.php'); exit;
    }
}

$requirements = []; $compliance = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM compliance_requirements ORDER BY created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $requirements[] = $row;
    $r2 = $conn->query("SELECT * FROM compliance_tracking ORDER BY created_at DESC LIMIT 50");
    if ($r2) while ($row = $r2->fetch_assoc()) $compliance[] = $row;
}

$pageTitle = 'Accreditation & Compliance';
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
        <h1><i class="fas fa-certificate"></i> Accreditation & Compliance</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Requirements</h6><h3><?= count($requirements) ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Compliance Records</h6><h3><?= count($compliance) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Add Compliance Requirement</h5></div>
                <div class="card-body">
                    <form method="post" class="row g-2">
                        <?php csrfField(); ?>
                        <div class="col-12"><input name="name" class="form-control" placeholder="Requirement Name" required></div>
                        <div class="col-8"><input name="description" class="form-control" placeholder="Description"></div>
                        <div class="col-4"><input name="deadline" class="form-control" type="date"></div>
                        <div class="col-12"><button type="submit" name="action" value="add_requirement" class="btn btn-primary"><i class="fas fa-plus"></i> Add Requirement</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>Requirements List</h5></div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($requirements as $req): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong><?= htmlspecialchars($req['requirement_name']) ?></strong><br><small><?= htmlspecialchars($req['description'] ?? '') ?></small></span>
                            <span class="badge bg-<?= $req['status'] === 'completed' ? 'success' : 'warning' ?> align-self-center"><?= $req['status'] ?></span>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($requirements)): ?><li class="list-group-item text-center">No requirements</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5>Compliance Tracking</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Area</th><th>Status</th><th>Notes</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($compliance as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['compliance_area'] ?? $c['area'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= ($c['status'] ?? 'pending') === 'compliant' ? 'success' : 'danger' ?>"><?= $c['status'] ?? 'pending' ?></span></td>
                            <td><?= htmlspecialchars($c['notes'] ?? '') ?></td>
                            <td><?= $c['created_at'] ?? '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($compliance)): ?><tr><td colspan="4" class="text-center">No compliance records</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
