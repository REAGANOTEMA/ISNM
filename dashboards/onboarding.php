<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr', 'manager', 'director']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$userId = (int)($_SESSION['user_id'] ?? 0);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add_item') {
        $item = trim($_POST['item'] ?? '');
        $dept = trim($_POST['department'] ?? 'all');
        if ($item) {
            $stmt = $conn->prepare("INSERT INTO onboarding_checklist (item_name, department, created_by) VALUES (?, ?, ?)");
            if ($stmt) { $stmt->bind_param('ssi', $item, $dept, $userId); $stmt->execute(); $stmt->close(); }
        }
        header('Location: onboarding.php'); exit;
    }

    if ($action === 'edit_item') {
        $id = (int)($_POST['item_id'] ?? 0);
        $item = trim($_POST['item'] ?? '');
        $dept = trim($_POST['department'] ?? 'all');
        $status = trim($_POST['status'] ?? 'active');
        if ($id && $item) {
            $stmt = $conn->prepare("UPDATE onboarding_checklist SET item_name=?, department=?, status=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('sssi', $item, $dept, $status, $id); $stmt->execute(); $stmt->close(); }
        }
        header('Location: onboarding.php'); exit;
    }

    if ($action === 'delete_item') {
        $id = (int)($_POST['item_id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM onboarding_checklist WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
        }
        header('Location: onboarding.php'); exit;
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['item_id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("UPDATE onboarding_checklist SET status = IF(status='active','inactive','active') WHERE id=?");
            if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
        }
        header('Location: onboarding.php'); exit;
    }
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
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active</h6><h3><?= count(array_filter($checklist, fn($c) => ($c['status'] ?? 'active') === 'active')) ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h5>Add Onboarding Item</h5></div>
        <div class="card-body">
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
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
                    <thead><tr><th>Item</th><th>Department</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($checklist as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['item_name']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($c['department'] ?? 'all') ?></span></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="item_id" value="<?= (int)$c['id'] ?>">
                                    <button type="submit" name="action" value="toggle_status" class="btn btn-sm btn-<?= ($c['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>"><?= $c['status'] ?? 'active' ?></button>
                                </form>
                            </td>
                            <td><?= $c['created_at'] ?? '-' ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= (int)$c['id'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="post" style="display:inline" onsubmit="return confirm('Delete this item?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="item_id" value="<?= (int)$c['id'] ?>">
                                    <button type="submit" name="action" value="delete_item" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= (int)$c['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="item_id" value="<?= (int)$c['id'] ?>">
                                        <div class="modal-header"><h5 class="modal-title">Edit Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <div class="mb-3"><label class="form-label">Item Name</label><input name="item" class="form-control" value="<?= htmlspecialchars($c['item_name']) ?>" required></div>
                                            <div class="mb-3"><label class="form-label">Department</label>
                                                <select name="department" class="form-select">
                                                    <?php foreach (['all'=>'All','academic'=>'Academic','finance'=>'Finance','hr'=>'HR','ict'=>'ICT','nursing'=>'Nursing','midwifery'=>'Midwifery'] as $v => $l): ?>
                                                    <option value="<?= $v ?>" <?= ($c['department'] ?? 'all') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3"><label class="form-label">Status</label>
                                                <select name="status" class="form-select">
                                                    <option value="active" <?= ($c['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="inactive" <?= ($c['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="action" value="edit_item" class="btn btn-primary">Save Changes</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($checklist)): ?><tr><td colspan="5" class="text-center">No onboarding items defined</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
