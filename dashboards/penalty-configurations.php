<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar', 'finance', 'director', 'registrar', 'secretary', 'ict']);
$conn = $ctx['students'];
$user = $ctx['user'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['penalty_name']);
        $type = trim($_POST['penalty_type'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO penalty_configurations (penalty_name, penalty_type, amount, description) VALUES (?, ?, ?, ?)");
            if ($stmt) { $stmt->bind_param('ssds', $name, $type, $amount, $desc); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = "Penalty '$name' added.";
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE penalty_configurations SET penalty_name=?, penalty_type=?, amount=?, description=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssdsi', $name, $type, $amount, $desc, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = "Penalty '$name' updated.";
        }
        header('Location: penalty-configurations.php'); exit;
    }
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE penalty_configurations SET is_active = NOT is_active WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        $_SESSION['success'] = 'Penalty status toggled.';
        header('Location: penalty-configurations.php'); exit;
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM penalty_configurations WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        $_SESSION['success'] = 'Penalty deleted.';
        header('Location: penalty-configurations.php'); exit;
    }
}

$records = [];
$r = $conn->query("SELECT * FROM penalty_configurations ORDER BY penalty_name ASC");
if ($r) while ($row = $r->fetch_assoc()) $records[] = $row;

$total = count($records);
$active = count(array_filter($records, fn($p) => $p['is_active']));
$inactive = $total - $active;
$totalAmount = array_sum(array_column($records, 'amount'));

$pageTitle = 'Penalty Configurations';
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
        <h1><i class="fas fa-gavel"></i> Penalty Configurations</h1><button onclick="window.print()" class="btn btn-sm btn-outline-secondary float-end"><i class="fas fa-print"></i> Print</button>
    </div>
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Penalties</h6><h3><?= $total ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active</h6><h3 class="text-success"><?= $active ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Inactive</h6><h3 class="text-danger"><?= $inactive ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Total Amount</h6><h3 class="text-primary"><?= number_format($totalAmount, 2) ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Penalty Configurations</h5>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#penaltyModal" onclick="clearModal()"><i class="fas fa-plus"></i> Add New</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead><tr><th>Penalty Name</th><th>Type</th><th>Amount</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($records as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['penalty_name']) ?></strong></td>
                            <td><?= htmlspecialchars($p['penalty_type'] ?? '-') ?></td>
                            <td><?= number_format((float)$p['amount'], 2) ?></td>
                            <td><?= htmlspecialchars(mb_substr($p['description'] ?? '', 0, 60)) ?></td>
                            <td>
                                <span class="badge bg-<?= $p['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" title="Edit"
                                    onclick="editPenalty(<?= htmlspecialchars(json_encode($p)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('Toggle status?')">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="action" value="toggle" class="btn btn-sm btn-outline-<?= $p['is_active'] ? 'warning' : 'success' ?>">
                                        <i class="fas fa-<?= $p['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this penalty?')">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($records)): ?><tr><td colspan="6" class="text-center py-4">No penalty configurations found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="penaltyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-gavel me-2"></i><span id="modalTitle">Add Penalty</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="penaltyId" value="0">
                    <div class="mb-3">
                        <label class="form-label">Penalty Name <span class="text-danger">*</span></label>
                        <input type="text" name="penalty_name" id="penaltyName" class="form-control" required maxlength="100" placeholder="e.g. Late Registration">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penalty Type</label>
                        <input type="text" name="penalty_type" id="penaltyType" class="form-control" maxlength="100" placeholder="e.g. Late Fee, Fine">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">UGX</span>
                            <input type="number" step="0.01" name="amount" id="penaltyAmount" class="form-control" required value="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="penaltyDesc" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
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
    document.getElementById('modalTitle').textContent = 'Add Penalty';
    document.getElementById('formAction').value = 'add';
    document.getElementById('penaltyId').value = '0';
    document.getElementById('penaltyName').value = '';
    document.getElementById('penaltyType').value = '';
    document.getElementById('penaltyAmount').value = '0.00';
    document.getElementById('penaltyDesc').value = '';
}
function editPenalty(p) {
    document.getElementById('modalTitle').textContent = 'Edit Penalty';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('penaltyId').value = p.id;
    document.getElementById('penaltyName').value = p.penalty_name;
    document.getElementById('penaltyType').value = p.penalty_type || '';
    document.getElementById('penaltyAmount').value = p.amount;
    document.getElementById('penaltyDesc').value = p.description || '';
    var modal = new bootstrap.Modal(document.getElementById('penaltyModal'));
    modal.show();
}
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION["csrf_token"] ?? "")?>';document.querySelectorAll('form[method="POST"],form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});</script>
</body>
</html>
