<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar', 'finance', 'director', 'registrar', 'secretary', 'ict']);
$conn = $ctx['students'];
$user = $ctx['user'];

$records = [];
if ($conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'add' || $action === 'edit') {
            $code = $_POST['cost_center_code'];
            $name = $_POST['cost_center_name'];
            $dept = $_POST['department'] ?? '';
            $desc = $_POST['description'] ?? '';
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO cost_centers (cost_center_code, cost_center_name, department, description) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $code, $name, $dept, $desc);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $stmt->close();
                $_SESSION['success'] = "Cost center '$name' added.";
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $conn->prepare("UPDATE cost_centers SET cost_center_code=?, cost_center_name=?, department=?, description=? WHERE id=?");
                $stmt->bind_param("ssssi", $code, $name, $dept, $desc, $id);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $stmt->close();
                $_SESSION['success'] = "Cost center '$name' updated.";
            }
            header('Location: cost-center-management.php'); exit;
        }
        if ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE cost_centers SET is_active = NOT is_active WHERE id=?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Cost center status toggled.';
            header('Location: cost-center-management.php'); exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM cost_centers WHERE id=?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Cost center deleted.';
            header('Location: cost-center-management.php'); exit;
        }
    }
    $r = $conn->query("SELECT * FROM cost_centers ORDER BY cost_center_code ASC");
    if ($r) while ($row = $r->fetch_assoc()) $records[] = $row;
}

$total = count($records);
$active = count(array_filter($records, fn($c) => $c['is_active']));
$inactive = $total - $active;
$depts = array_unique(array_filter(array_column($records, 'department')));

$pageTitle = 'Cost Center Management';
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
        <h1><i class="fas fa-coins"></i> Cost Center Management</h1>
    </div>
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Cost Centers</h6><h3><?= $total ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active</h6><h3 class="text-success"><?= $active ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Inactive</h6><h3 class="text-danger"><?= $inactive ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Departments</h6><h3><?= count($depts) ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Cost Centers</h5>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#ccModal" onclick="clearModal()"><i class="fas fa-plus"></i> Add New</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($records as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['cost_center_code']) ?></strong></td>
                            <td><?= htmlspecialchars($c['cost_center_name']) ?></td>
                            <td><?= htmlspecialchars($c['department'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(mb_substr($c['description'] ?? '', 0, 60)) ?></td>
                            <td>
                                <span class="badge bg-<?= $c['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" title="Edit"
                                    onclick="editCC(<?= htmlspecialchars(json_encode($c)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('Toggle status?')">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" name="action" value="toggle" class="btn btn-sm btn-outline-<?= $c['is_active'] ? 'warning' : 'success' ?>">
                                        <i class="fas fa-<?= $c['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this cost center?')">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($records)): ?><tr><td colspan="6" class="text-center py-4">No cost centers found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ccModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-coins me-2"></i><span id="modalTitle">Add Cost Center</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="ccId" value="0">
                    <div class="mb-3">
                        <label class="form-label">Cost Center Code <span class="text-danger">*</span></label>
                        <input type="text" name="cost_center_code" id="ccCode" class="form-control" required maxlength="20" placeholder="e.g. CC-FIN">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cost Center Name <span class="text-danger">*</span></label>
                        <input type="text" name="cost_center_name" id="ccName" class="form-control" required maxlength="255" placeholder="e.g. Finance Department">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" id="ccDept" class="form-control" maxlength="100" placeholder="e.g. Finance">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="ccDesc" class="form-control" rows="3" placeholder="Optional description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function clearModal() {
    document.getElementById('modalTitle').textContent = 'Add Cost Center';
    document.getElementById('formAction').value = 'add';
    document.getElementById('ccId').value = '0';
    document.getElementById('ccCode').value = '';
    document.getElementById('ccName').value = '';
    document.getElementById('ccDept').value = '';
    document.getElementById('ccDesc').value = '';
}
function editCC(c) {
    document.getElementById('modalTitle').textContent = 'Edit Cost Center';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('ccId').value = c.id;
    document.getElementById('ccCode').value = c.cost_center_code;
    document.getElementById('ccName').value = c.cost_center_name;
    document.getElementById('ccDept').value = c.department || '';
    document.getElementById('ccDesc').value = c.description || '';
    var modal = new bootstrap.Modal(document.getElementById('ccModal'));
    modal.show();
}
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
