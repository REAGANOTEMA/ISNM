<?php
/**
 * Director Dashboard — Donation Tracking Module
 * Track and manage all donations to ISNM
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director general', 'ceo', 'director_finance', 'principal', 'bursar']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$websiteConn = $ctx['website'];
$user = $ctx['user'];
$userId = (int)($_SESSION['user_id'] ?? 0);

// CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Ensure donations table exists
if ($websiteConn) {
    $websiteConn->query("CREATE TABLE IF NOT EXISTS donations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        donor_name VARCHAR(255) NOT NULL,
        donor_email VARCHAR(255),
        donor_phone VARCHAR(50),
        donor_type ENUM('individual','organization','government','other') DEFAULT 'individual',
        amount DECIMAL(15,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'UGX',
        payment_method VARCHAR(100),
        transaction_ref VARCHAR(100),
        purpose VARCHAR(500),
        category ENUM('tuition','infrastructure','equipment','scholarship','general','other') DEFAULT 'general',
        is_anonymous TINYINT(1) DEFAULT 0,
        status ENUM('pending','confirmed','cancelled') DEFAULT 'confirmed',
        notes TEXT,
        receipt_number VARCHAR(50),
        received_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $websiteConn) {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('Invalid security token.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'record') {
        $donorName = trim($_POST['donor_name'] ?? '');
        $donorEmail = trim($_POST['donor_email'] ?? '');
        $donorPhone = trim($_POST['donor_phone'] ?? '');
        $donorType = $_POST['donor_type'] ?? 'individual';
        $amount = (float)($_POST['amount'] ?? 0);
        $currency = $_POST['currency'] ?? 'UGX';
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $transactionRef = trim($_POST['transaction_ref'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $category = $_POST['category'] ?? 'general';
        $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        $notes = trim($_POST['notes'] ?? '');

        if ($donorName && $amount > 0) {
            $receiptNum = 'DON-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $stmt = $websiteConn->prepare("INSERT INTO donations (donor_name, donor_email, donor_phone, donor_type, amount, currency, payment_method, transaction_ref, purpose, category, is_anonymous, receipt_number, received_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('ssssdsssssisi', $donorName, $donorEmail, $donorPhone, $donorType, $amount, $currency, $paymentMethod, $transactionRef, $purpose, $category, $isAnonymous, $receiptNum, $userId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Donation recorded successfully. Receipt: ' . $receiptNum;
                } else {
                    $_SESSION['error'] = 'Failed to record donation: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = 'Donor name and valid amount are required.';
        }
        header('Location: donation-tracking.php');
        exit;
    }

    if ($action === 'update_status') {
        $id = (int)($_POST['donation_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if ($id && in_array($newStatus, ['pending', 'confirmed', 'cancelled'])) {
            $stmt = $websiteConn->prepare("UPDATE donations SET status=?, updated_at=NOW() WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('si', $newStatus, $id);
                $stmt->execute();
                $stmt->close();
            }
            $_SESSION['success'] = 'Donation status updated.';
        }
        header('Location: donation-tracking.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['donation_id'] ?? 0);
        if ($id) {
            $stmt = $websiteConn->prepare("DELETE FROM donations WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
            $_SESSION['success'] = 'Donation record deleted.';
        }
        header('Location: donation-tracking.php');
        exit;
    }
}

// Fetch donations
$donations = [];
if ($websiteConn) {
    $result = $websiteConn->query("SELECT d.* FROM donations d ORDER BY d.created_at DESC LIMIT 200");
    if ($result) {
        while ($row = $result->fetch_assoc()) $donations[] = $row;
    }
}
// Enrich with staff names from staff DB
if ($conn && !empty($donations)) {
    $staffIds = array_unique(array_filter(array_column($donations, 'received_by'), fn($v) => $v > 0));
    $staffNames = [];
    if (!empty($staffIds)) {
        $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
        $types = str_repeat('i', count($staffIds));
        $stmt = $conn->prepare("SELECT id, full_name FROM staff WHERE id IN ($placeholders)");
        if ($stmt) {
            $stmt->bind_param($types, ...$staffIds);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r) while ($row = $r->fetch_assoc()) $staffNames[$row['id']] = $row['full_name'];
            $stmt->close();
        }
    }
    foreach ($donations as &$d) {
        $d['received_by_name'] = $staffNames[$d['received_by']] ?? null;
    }
    unset($d);
}

// Calculate stats
$totalDonations = count($donations);
$totalAmount = array_sum(array_column($donations, 'amount'));
$confirmedAmount = array_sum(array_column(array_filter($donations, fn($d) => $d['status'] === 'confirmed'), 'amount'));
$pendingCount = count(array_filter($donations, fn($d) => $d['status'] === 'pending'));
$confirmedCount = count(array_filter($donations, fn($d) => $d['status'] === 'confirmed'));

// Category breakdown
$categoryTotals = [];
foreach ($donations as $d) {
    if ($d['status'] === 'confirmed') {
        $cat = $d['category'] ?? 'general';
        $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + $d['amount'];
    }
}

$pageTitle = 'Donation Tracking';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.donation-stat { transition: all 0.3s ease; }
.donation-stat:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.category-badge { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-hand-holding-heart"></i> Donation Tracking</h1>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary float-end"><i class="fas fa-print"></i> Print</button>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card donation-stat border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-donate fa-2x text-primary mb-2"></i>
                    <h3 class="text-primary mb-0"><?= number_format($totalDonations) ?></h3>
                    <p class="text-muted mb-0">Total Donations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card donation-stat border-success">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                    <h3 class="text-success mb-0">UGX <?= number_format($confirmedAmount) ?></h3>
                    <p class="text-muted mb-0">Confirmed Amount</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card donation-stat border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h3 class="text-warning mb-0"><?= $pendingCount ?></h3>
                    <p class="text-muted mb-0">Pending Review</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card donation-stat border-info">
                <div class="card-body text-center">
                    <i class="fas fa-chart-pie fa-2x text-info mb-2"></i>
                    <h3 class="text-info mb-0"><?= count($categoryTotals) ?></h3>
                    <p class="text-muted mb-0">Categories</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Breakdown -->
    <?php if (!empty($categoryTotals)): ?>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Donations by Category</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($categoryTotals as $cat => $total): ?>
                <div class="col-md-2 col-sm-4">
                    <div class="text-center p-3 rounded bg-light">
                        <i class="fas fa-<?= match($cat) { 'tuition' => 'graduation-cap', 'infrastructure' => 'building', 'equipment' => 'tools', 'scholarship' => 'award', default => 'heart' } ?> fa-2x text-primary mb-2"></i>
                        <h6 class="mb-1 text-capitalize"><?= $cat ?></h6>
                        <strong class="text-success">UGX <?= number_format($total) ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Donation Records</h5>
            <button class="btn btn-primary btn-sm" onclick="showRecordForm()"><i class="fas fa-plus me-1"></i>Record Donation</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Donor</th><th>Amount</th><th>Category</th><th>Purpose</th><th>Method</th><th>Receipt</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($donations)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No donations recorded yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($donations as $donation): ?>
                        <tr>
                            <td>
                                <?php if ($donation['is_anonymous']): ?>
                                <span class="text-muted"><i class="fas fa-user-secret me-1"></i>Anonymous</span>
                                <?php else: ?>
                                <strong><?= htmlspecialchars($donation['donor_name']) ?></strong>
                                <?php if ($donation['donor_email']): ?><br><small class="text-muted"><?= htmlspecialchars($donation['donor_email']) ?></small><?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-success"><?= $donation['currency'] ?> <?= number_format($donation['amount'], 2) ?></strong></td>
                            <td><span class="category-badge bg-light text-dark text-capitalize"><?= $donation['category'] ?></span></td>
                            <td><small><?= htmlspecialchars(substr($donation['purpose'] ?? '-', 0, 50)) ?></small></td>
                            <td><small class="text-capitalize"><?= htmlspecialchars($donation['payment_method'] ?? '-') ?></small></td>
                            <td><small class="text-muted"><?= htmlspecialchars($donation['receipt_number'] ?? '-') ?></small></td>
                            <td><small><?= date('M j, Y', strtotime($donation['created_at'])) ?></small></td>
                            <td>
                                <span class="badge bg-<?= $donation['status'] === 'confirmed' ? 'success' : ($donation['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($donation['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($donation['status'] === 'pending'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="donation_id" value="<?= $donation['id'] ?>">
                                    <input type="hidden" name="new_status" value="confirmed">
                                    <button class="btn btn-sm btn-outline-success" title="Confirm"><i class="fas fa-check"></i></button>
                                </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this donation record?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="donation_id" value="<?= $donation['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Record Donation Modal -->
<div class="modal fade" id="donationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-hand-holding-heart me-2"></i>Record New Donation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="modal-body">
                    <input type="hidden" name="action" value="record">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Donor Name *</label>
                            <input type="text" name="donor_name" class="form-control" required placeholder="Full name or organization">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Donor Type</label>
                            <select name="donor_type" class="form-select">
                                <option value="individual">Individual</option>
                                <option value="organization">Organization</option>
                                <option value="government">Government</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="donor_email" class="form-control" placeholder="donor@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="tel" name="donor_phone" class="form-control" placeholder="+256 7XX XXX XXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount *</label>
                            <input type="number" name="amount" class="form-control" min="1" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Currency</label>
                            <select name="currency" class="form-select">
                                <option value="UGX">UGX - Ugandan Shilling</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="GBP">GBP - British Pound</option>
                                <option value="EUR">EUR - Euro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="general">General Donation</option>
                                <option value="tuition">Tuition Support</option>
                                <option value="infrastructure">Infrastructure</option>
                                <option value="equipment">Equipment</option>
                                <option value="scholarship">Scholarship Fund</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card Payment</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online Payment</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Transaction Reference</label>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="Optional reference number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_anonymous" id="isAnonymous" value="1">
                                <label class="form-check-label" for="isAnonymous">Anonymous donation</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Purpose / Notes</label>
                            <textarea name="purpose" class="form-control" rows="2" placeholder="What is this donation for?"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Any internal notes"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Record Donation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRecordForm() {
    new bootstrap.Modal(document.getElementById('donationModal')).show();
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
