<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/auto_deduction_processor.php';

$ctx = bootstrapStaffDashboard(['bursar','finance','director','registrar']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($studentsDb) {
    $studentsDb->query("CREATE TABLE IF NOT EXISTS payment_subscriptions (id INT AUTO_INCREMENT PRIMARY KEY, student_id VARCHAR(50) DEFAULT '', subscription_type VARCHAR(50) DEFAULT 'fee_installment', reference_type VARCHAR(50) DEFAULT '', reference_id INT DEFAULT 0, total_amount DECIMAL(14,2) DEFAULT 0, installment_amount DECIMAL(14,2) DEFAULT 0, frequency VARCHAR(20) DEFAULT 'monthly', total_installments INT DEFAULT 1, installments_collected INT DEFAULT 0, start_date DATE DEFAULT NULL, next_due_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, payment_method VARCHAR(50) DEFAULT 'mobile_money', payment_provider VARCHAR(100) DEFAULT '', phone_number VARCHAR(50) DEFAULT '', status VARCHAR(50) DEFAULT 'active', notes TEXT, created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_ps_student (student_id), INDEX idx_ps_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $studentsDb->query("CREATE TABLE IF NOT EXISTS subscription_deductions (id INT AUTO_INCREMENT PRIMARY KEY, subscription_id INT NOT NULL, student_id VARCHAR(50) DEFAULT '', installment_number INT DEFAULT 0, amount DECIMAL(14,2) DEFAULT 0, due_date DATE DEFAULT NULL, processed_date DATETIME DEFAULT NULL, status VARCHAR(50) DEFAULT 'pending', payment_reference VARCHAR(100) DEFAULT '', payment_id INT DEFAULT NULL, failure_reason TEXT, attempt_count INT DEFAULT 0, last_attempt_date DATETIME DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_sd_sub (subscription_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$studentId = $_GET['student_id'] ?? '';
$subscriptions = $studentId ? getStudentSubscriptions($studentId) : [];
$allSubscriptions = [];
$stats = [];

// AJAX endpoint for subscription detail
if (isset($_GET['ajax']) && $_GET['ajax'] === 'subscription_detail' && isset($_GET['id']) && $studentsDb) {
    header('Content-Type: application/json');
    $subId = (int)$_GET['id'];
    $stmt = $studentsDb->prepare("SELECT ps.*, s.full_name, s.student_number, s.program FROM payment_subscriptions ps LEFT JOIN students s ON CAST(s.id AS CHAR) = ps.student_id WHERE ps.id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $subId);
        $stmt->execute();
        $sub = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else { $sub = null; }
    $deductions = [];
    if ($sub) {
        $dStmt = $studentsDb->prepare("SELECT * FROM subscription_deductions WHERE subscription_id = ? ORDER BY installment_number");
        if ($dStmt) {
            $dStmt->bind_param('i', $subId);
            $dStmt->execute();
            $deductions = isnm_fetch_all($dStmt->get_result());
            $dStmt->close();
        }
    }
    echo json_encode(['subscription' => $sub, 'deductions' => $deductions]);
    exit;
}
if (in_array(strtolower($userRole), ['director general','director ict','school secretary','school principal','bursar','finance','director finance'])) {
    $allSubscriptions = getAllSubscriptions();
    $stats = getSubscriptionStats();
}
$action = $_POST['action'] ?? '';
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
}
if ($action === 'create' && !empty($_POST['student_id'])) {
    $result = createSubscription($_POST);
}
if ($action === 'cancel' && !empty($_POST['subscription_id'])) {
    $result = ['success' => cancelSubscription((int)$_POST['subscription_id'])];
}
if ($action === 'pause' && !empty($_POST['subscription_id'])) {
    $result = ['success' => pauseSubscription((int)$_POST['subscription_id'])];
}
if ($action === 'resume' && !empty($_POST['subscription_id'])) {
    $result = ['success' => resumeSubscription((int)$_POST['subscription_id'])];
}
if ($action === 'run_auto' && in_array(strtolower($userRole), ['director general','director ict','school secretary','bursar'])) {
    require_once __DIR__ . '/../includes/auto_deduction_processor.php';
    $procResult = processAutoDeductions(100);
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
            <h4 class="fw-bold mb-0"><i class="bi bi-arrow-repeat me-2"></i>Payment Subscriptions</h4>
            <div>
                <span class="text-muted small me-3"><?= date('l, d M Y') ?></span>
                <?php if (in_array(strtolower($userRole), ['director general','director ict','school secretary','bursar'])): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Run auto deduction processor?')">
                    <input type="hidden" name="action" value="run_auto">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-play-fill me-1"></i>Run Auto Deductions</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($procResult)): ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span><i class="bi bi-info-circle me-2"></i>Auto Deduction Run: <strong><?= $procResult['success'] ?> succeeded</strong>, <?= $procResult['failed'] ?> failed, <?= $procResult['skipped'] ?> skipped</span>
            <small class="text-muted"><?= date('H:i:s') ?></small>
        </div>
        <?php endif; ?>
        <?php if ($result && isset($result['success'])): ?>
        <div class="alert alert-<?= $result['success'] ? 'success' : 'danger' ?> alert-dismissible fade show">
            <i class="bi bi-<?= $result['success'] ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= $result['success'] ? 'Operation completed successfully' : ($result['error'] ?? 'Operation failed') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($stats)): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card-section text-center py-3">
                    <div class="fs-1 text-primary mb-1"><i class="bi bi-arrow-repeat"></i></div>
                    <h3 class="fw-bold mb-0"><?= $stats['active'] ?? 0 ?></h3>
                    <small class="text-muted">Active Subscriptions</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-section text-center py-3">
                    <div class="fs-1 text-success mb-1"><i class="bi bi-check-circle"></i></div>
                    <h3 class="fw-bold mb-0"><?= $stats['completed'] ?? 0 ?></h3>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-section text-center py-3">
                    <div class="fs-1 text-info mb-1"><i class="bi bi-cash-stack"></i></div>
                    <h3 class="fw-bold mb-0">UGX <?= number_format($stats['monthly_projected'] ?? 0) ?></h3>
                    <small class="text-muted">Projected Monthly</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-section text-center py-3">
                    <div class="fs-1 text-warning mb-1"><i class="bi bi-piggy-bank"></i></div>
                    <h3 class="fw-bold mb-0">UGX <?= number_format($stats['total_collected'] ?? 0) ?></h3>
                    <small class="text-muted">Total Collected (Auto)</small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array(strtolower($userRole), ['director general','director ict','school secretary','bursar','finance','director finance'])): ?>
        <div class="card-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-list-ul me-2"></i>All Subscriptions</h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" id="searchSub" placeholder="Search student..." style="width:200px" onkeyup="filterSubscriptions()">
                    <select class="form-select form-select-sm" id="filterStatus" style="width:140px" onchange="filterSubscriptions()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <?php if (empty($allSubscriptions)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-arrow-repeat" style="font-size:48px;opacity:.3"></i>
                <p class="mt-2">No subscriptions found. Create one from a student's payment page.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle" id="subTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Program</th>
                            <th>Total / Installment</th>
                            <th>Progress</th>
                            <th>Next Due</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allSubscriptions as $i => $s): ?>
                        <tr data-student="<?= strtolower(htmlspecialchars($s['full_name'] ?? '')) ?>" data-status="<?= $s['status'] ?>">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($s['full_name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($s['student_number'] ?: $s['student_id']) ?></small>
                            </td>
                            <td><small><?= htmlspecialchars($s['program'] ?? '-') ?></small></td>
                            <td>
                                <strong>UGX <?= number_format($s['total_amount']) ?></strong>
                                <br><small class="text-muted">UGX <?= number_format($s['installment_amount']) ?>/mo</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;">
                                        <div class="progress-bar bg-success" style="width:<?= $s['total_installments'] > 0 ? min(100, ($s['installments_collected']/$s['total_installments'])*100) : 0 ?>%"></div>
                                    </div>
                                    <small><?= $s['installments_collected'] ?>/<?= $s['total_installments'] ?></small>
                                </div>
                            </td>
                            <td><small><?= $s['next_due_date'] ? date('d M Y', strtotime($s['next_due_date'])) : '-' ?></small></td>
                            <td><small><?= htmlspecialchars(ucfirst(str_replace('_',' ',$s['payment_method'] ?? ''))) ?></small></td>
                            <td>
                                <span class="badge bg-<?= $s['status'] === 'active' ? 'success' : ($s['status'] === 'paused' ? 'warning' : ($s['status'] === 'completed' ? 'primary' : 'secondary')) ?>">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'active'): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="action" value="pause">
                                    <input type="hidden" name="subscription_id" value="<?= $s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-warning" title="Pause"><i class="bi bi-pause-fill"></i></button>
                                </form>
                                <?php elseif ($s['status'] === 'paused'): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="action" value="resume">
                                    <input type="hidden" name="subscription_id" value="<?= $s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-success" title="Resume"><i class="bi bi-play-fill"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($s['status'] !== 'completed' && $s['status'] !== 'cancelled'): ?>
                                <form method="post" style="display:inline" onsubmit="return confirm('Cancel this subscription?')">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="subscription_id" value="<?= $s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Cancel"><i class="bi bi-x-circle"></i></button>
                                </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-info" title="View Details" onclick="viewSubscription(<?= $s['id'] ?>)"><i class="bi bi-eye"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($studentId): ?>
        <div class="card-section">
            <h5 class="fw-bold mb-3"><i class="bi bi-person-check me-2"></i>Subscriptions for Student: <?= htmlspecialchars($studentId) ?></h5>
            <?php if (empty($subscriptions)): ?>
            <p class="text-muted">No subscriptions found for this student.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>#</th><th>Type</th><th>Total</th><th>Installment</th><th>Progress</th><th>Next Due</th><th>Status</th><th>Total Deducted</th></tr></thead>
                    <tbody>
                        <?php foreach ($subscriptions as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$s['subscription_type']))) ?></td>
                            <td>UGX <?= number_format($s['total_amount']) ?></td>
                            <td>UGX <?= number_format($s['installment_amount']) ?></td>
                            <td><?= $s['installments_collected'] ?>/<?= $s['total_installments'] ?></td>
                            <td><?= $s['next_due_date'] ? date('d M Y', strtotime($s['next_due_date'])) : '-' ?></td>
                            <td><span class="badge bg-<?= $s['status'] === 'active' ? 'success' : ($s['status'] === 'paused' ? 'warning' : ($s['status'] === 'completed' ? 'primary' : 'secondary')) ?>"><?= ucfirst($s['status']) ?></span></td>
                            <td>UGX <?= number_format($s['total_deducted'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card-section">
            <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>Create New Subscription</h5>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="create">
                <div class="col-md-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" name="student_id" class="form-control" required placeholder="e.g. STU001" value="<?= htmlspecialchars($studentId) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total Amount (UGX)</label>
                    <input type="number" name="total_amount" class="form-control" required min="1000" step="1000">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Installments</label>
                    <input type="number" name="total_installments" class="form-control" required min="1" max="36" value="6">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Frequency</label>
                    <select name="frequency" class="form-select">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="quarterly">Quarterly</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank">Bank</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Provider</label>
                    <select name="payment_provider" class="form-select">
                        <option value="">Select Provider</option>
                        <option value="mtn_momo">MTN Mobile Money</option>
                        <option value="airtel_money">Airtel Money</option>
                        <option value="stanbic_bank">Stanbic Bank</option>
                        <option value="equity_bank">Equity Bank</option>
                        <option value="centenary_bank">Centenary Bank</option>
                        <option value="pearl_bank">Pearl Bank</option>
                        <option value="uba_bank">UBA Bank</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone_number" class="form-control" placeholder="07XXXXXXXX">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Subscription Type</label>
                    <select name="subscription_type" class="form-select">
                        <option value="fee_installment">Fee Installment</option>
                        <option value="hostel">Hostel Fees</option>
                        <option value="library">Library Fees</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterSubscriptions() {
    var q = document.getElementById('searchSub').value.toLowerCase();
    var s = document.getElementById('filterStatus').value;
    document.querySelectorAll('#subTable tbody tr').forEach(function(r){
        var matchName = !q || r.dataset.student.includes(q);
        var matchStatus = !s || r.dataset.status === s;
        r.style.display = (matchName && matchStatus) ? '' : 'none';
    });
}
function viewSubscription(id) {
    fetch('payment-subscriptions.php?ajax=subscription_detail&id=' + id)
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var s = data.subscription;
        var deductions = data.deductions || [];
        if (!s) { alert('Subscription not found'); return; }
        var collected = parseFloat(s.installments_collected || 0);
        var total = parseFloat(s.total_installments || 1);
        var pct = Math.min(100, (collected / total) * 100).toFixed(0);
        var html = '<div class="modal fade" id="viewSubModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">';
        html += '<div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Subscription Details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>';
        html += '<div class="modal-body">';
        html += '<div class="row g-3 mb-4">';
        html += '<div class="col-md-6"><strong>Student:</strong> ' + (s.full_name || 'N/A') + '<br><small class="text-muted">' + (s.student_number || s.student_id) + '</small></div>';
        html += '<div class="col-md-6"><strong>Program:</strong> ' + (s.program || '-') + '</div>';
        html += '<div class="col-md-6"><strong>Type:</strong> ' + (s.subscription_type || '-').replace(/_/g, ' ') + '</div>';
        html += '<div class="col-md-6"><strong>Status:</strong> <span class="badge bg-' + (s.status === 'active' ? 'success' : (s.status === 'paused' ? 'warning' : 'secondary')) + '">' + (s.status || '') + '</span></div>';
        html += '<div class="col-md-6"><strong>Total:</strong> UGX ' + parseFloat(s.total_amount || 0).toLocaleString() + '</div>';
        html += '<div class="col-md-6"><strong>Installment:</strong> UGX ' + parseFloat(s.installment_amount || 0).toLocaleString() + ' / ' + (s.frequency || 'monthly') + '</div>';
        html += '<div class="col-md-6"><strong>Method:</strong> ' + (s.payment_method || '-').replace(/_/g, ' ') + '</div>';
        html += '<div class="col-md-6"><strong>Provider:</strong> ' + (s.payment_provider || '-') + ' ' + (s.phone_number || '') + '</div>';
        html += '<div class="col-md-6"><strong>Start:</strong> ' + (s.start_date || '-') + '</div>';
        html += '<div class="col-md-6"><strong>Next Due:</strong> ' + (s.next_due_date || '-') + '</div>';
        html += '</div>';
        html += '<div class="mb-3"><strong>Progress:</strong> ' + collected + '/' + total + ' installments (' + pct + '%)';
        html += '<div class="progress mt-1" style="height:10px;"><div class="progress-bar bg-success" style="width:' + pct + '%"></div></div></div>';
        if (deductions.length > 0) {
            html += '<h6><i class="fas fa-history me-1"></i>Payment History</h6>';
            html += '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Amount</th><th>Due Date</th><th>Processed</th><th>Status</th><th>Reference</th></tr></thead><tbody>';
            deductions.forEach(function(d) {
                var stCls = d.status === 'completed' ? 'success' : (d.status === 'failed' ? 'danger' : (d.status === 'pending' ? 'warning' : 'secondary'));
                html += '<tr><td>' + d.installment_number + '</td><td>UGX ' + parseFloat(d.amount || 0).toLocaleString() + '</td><td>' + (d.due_date || '-') + '</td><td>' + (d.processed_date || '-') + '</td><td><span class="badge bg-' + stCls + '">' + (d.status || '') + '</span></td><td><small>' + (d.payment_reference || '-') + '</small></td></tr>';
            });
            html += '</tbody></table></div>';
        } else {
            html += '<p class="text-muted">No payment history found.</p>';
        }
        html += '</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>';
        html += '</div></div></div>';
        document.body.insertAdjacentHTML('beforeend', html);
        var modal = new bootstrap.Modal(document.getElementById('viewSubModal'));
        modal.show();
        document.getElementById('viewSubModal').addEventListener('hidden.bs.modal', function() { this.remove(); });
    })
    .catch(function(e) { alert('Failed to load subscription details'); console.error(e); });
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION["csrf_token"] ?? "")?>';document.querySelectorAll('form[method="POST"],form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});</script>
</body>
</html>
