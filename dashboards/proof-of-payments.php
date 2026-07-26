<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar', 'finance', 'director', 'registrar', 'secretary', 'ict']);
$conn = $ctx['students'];
$user = $ctx['user'];
$userId = (int)($_SESSION['user_id'] ?? 0);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS proof_of_payments (id INT AUTO_INCREMENT PRIMARY KEY, proof_number VARCHAR(50) DEFAULT '', student_id INT DEFAULT 0, payment_id INT DEFAULT 0, document_path VARCHAR(500) DEFAULT '', document_type VARCHAR(50) DEFAULT '', notes TEXT, verified TINYINT(1) DEFAULT 0, verified_by INT DEFAULT NULL, verified_at DATETIME DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_pop_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'verify') {
        $id = (int)($_POST['id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $stmt = $conn->prepare("UPDATE proof_of_payments SET verified=1, verified_by=?, verified_at=NOW(), notes=? WHERE id=?");
        if ($stmt) { $stmt->bind_param('isi', $userId, $notes, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        $_SESSION['success'] = 'Payment proof verified.';
        header('Location: proof-of-payments.php'); exit;
    }
    if ($action === 'unverify') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE proof_of_payments SET verified=0, verified_by=NULL, verified_at=NULL, notes='' WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        $_SESSION['success'] = 'Payment proof unverified.';
        header('Location: proof-of-payments.php'); exit;
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT document_path FROM proof_of_payments WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $qrRow = $stmt->get_result(); $stmt->close(); }
        else $qrRow = null;
        $row = $qrRow ? $qrRow->fetch_assoc() : null;
        if ($row && $row['document_path']) {
            $file = __DIR__ . '/../' . $row['document_path'];
            if (file_exists($file)) @unlink($file);
        }
        $stmt = $conn->prepare("DELETE FROM proof_of_payments WHERE id=?");
        if ($stmt) { $stmt->bind_param('i', $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        $_SESSION['success'] = 'Proof of payment deleted.';
        header('Location: proof-of-payments.php'); exit;
    }
}

$records = [];
$r = $conn->query("SELECT p.*, s.first_name, s.surname, s.student_number, py.payment_reference AS payment_receipt FROM proof_of_payments p LEFT JOIN students s ON p.student_id=s.id LEFT JOIN payments py ON p.payment_id=py.id ORDER BY p.created_at DESC");
if ($r) while ($row = $r->fetch_assoc()) $records[] = $row;

$total = count($records);
$verified = count(array_filter($records, fn($p) => $p['verified']));
$unverified = $total - $verified;

$pageTitle = 'Proof of Payments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.verify-notes-area { resize: vertical; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-file-invoice"></i> Proof of Payments</h1>
    </div>
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total Submissions</h6><h3><?= $total ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Verified</h6><h3 class="text-success"><?= $verified ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Unverified</h6><h3 class="text-warning"><?= $unverified ?></h3></div></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Payment Proofs</h5>
            <span class="badge bg-info"><?= $total ?> records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead><tr><th>Proof #</th><th>Student</th><th>Receipt</th><th>Document</th><th>Uploaded</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($records as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['proof_number']) ?></strong></td>
                            <td>
                                <?= htmlspecialchars(($p['full_name'] ?? '') ?: (($p['first_name'] ?? '') . ' ' . ($p['surname'] ?? ''))) ?>
                                <br><small class="text-muted"><?= htmlspecialchars($p['student_number'] ?? '') ?></small>
                            </td>
                            <td><small><?= htmlspecialchars($p['payment_receipt'] ?? '-') ?></small></td>
                            <td>
                                <?php if ($p['document_path']): ?>
                                <a href="../<?= htmlspecialchars($p['document_path']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php else: ?>
                                <span class="text-muted">No file</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= $p['created_at'] ?? '-' ?></small></td>
                            <td>
                                <?php if ($p['verified']): ?>
                                <span class="badge bg-success">Verified</span>
                                <br><small class="text-muted">by <?= $p['verified_by'] ?> at <?= $p['verified_at'] ?? '-' ?></small>
                                <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$p['verified']): ?>
                                <button class="btn btn-sm btn-success" onclick="verifyProof(<?= $p['id'] ?>)">
                                    <i class="fas fa-check"></i> Verify
                                </button>
                                <?php else: ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Revert verification?')">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="action" value="unverify" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this proof?')">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($records)): ?><tr><td colspan="7" class="text-center py-4">No proof of payments found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="verifyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Verify Payment Proof</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="verify">
                    <input type="hidden" name="id" id="verifyId" value="0">
                    <p>Are you sure you want to verify this payment proof?</p>
                    <div class="mb-3">
                        <label class="form-label">Verification Notes</label>
                        <textarea name="notes" class="form-control verify-notes-area" rows="3" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Confirm Verify</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function verifyProof(id) {
    document.getElementById('verifyId').value = id;
    var modal = new bootstrap.Modal(document.getElementById('verifyModal'));
    modal.show();
}
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION["csrf_token"] ?? "")?>';document.querySelectorAll('form[method="POST"],form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});</script>
</body>
</html>
