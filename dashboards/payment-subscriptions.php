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

$studentId = $_GET['student_id'] ?? '';
$subscriptions = $studentId ? getStudentSubscriptions($studentId) : [];
$allSubscriptions = [];
$stats = [];
if (in_array(strtolower($userRole), ['director general','director ict','school secretary','school principal','bursar','finance','director finance'])) {
    $allSubscriptions = getAllSubscriptions();
    $stats = getSubscriptionStats();
}
$action = $_POST['action'] ?? '';
$result = null;
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
    alert('Subscription #' + id + ' details - Full history view coming soon.');
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
