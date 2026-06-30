<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr', 'manager', 'director']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_item' && $conn) {
    $item = trim($_POST['item'] ?? '');
    $dept = trim($_POST['department'] ?? 'all');
    if ($item) {
        $stmt = $conn->prepare("INSERT INTO onboarding_checklist (item_name, department, created_by) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssi', $item, $dept, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: onboarding.php'); exit;
}

$checklist = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM onboarding_checklist ORDER BY created_at DESC");
    if ($r) while ($row = $r->fetch_assoc()) $checklist[] = $row;
}

$pageTitle = 'Onboarding & Orientation';
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
        <h1><i class="fas fa-clipboard-list"></i> Onboarding & Orientation</h1>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Checklist Items</h6><h3><?= count($checklist) ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h5>Add Onboarding Item</h5></div>
        <div class="card-body">
            <form method="post" class="row g-2">
                <div class="col-md-6"><input name="item" class="form-control" placeholder="Item name (e.g., Submit ID copy)" required></div>
                <div class="col-md-4">
                    <select name="department" class="form-select">
                        <option value="all">All Departments</option>
                        <option value="academic">Academic</option>
                        <option value="finance">Finance</option>
                        <option value="hr">HR</option>
                        <option value="ict">ICT</option>
                        <option value="nursing">Nursing</option>
                        <option value="midwifery">Midwifery</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" name="action" value="add_item" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add</button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h5>Onboarding Checklist</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Item</th><th>Department</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($checklist as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['item_name']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($c['department'] ?? 'all') ?></span></td>
                            <td><span class="badge bg-<?= ($c['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>"><?= $c['status'] ?? 'active' ?></span></td>
                            <td><?= $c['created_at'] ?? '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($checklist)): ?><tr><td colspan="4" class="text-center">No onboarding items defined</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
