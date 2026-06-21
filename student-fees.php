<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: student-login.php');
    exit;
}
$isStaff = ($_SESSION['type'] ?? '') === 'staff';
$isStudent = ($_SESSION['type'] ?? '') === 'student';
if (!$isStaff && !$isStudent) {
    header('Location: student-login.php');
    exit;
}
$auth_service = new AuthenticationService();
$user = $auth_service->getCurrentUser();
$staffDb = getStaffConnection();
$studentsDb = getStudentsConnection();

$studentNumber = $user['student_number'] ?? ($_SESSION['student_number'] ?? ($_GET['student'] ?? ''));
$view = $_GET['view'] ?? 'dashboard';

$studentInfo = null;
$invoices = [];
$payments = [];
$balanceInfo = ['total_billed' => 0, 'total_paid' => 0, 'balance' => 0];

if ($studentNumber && $studentsDb) {
    try {
        $stmt = $studentsDb->prepare("SELECT * FROM students WHERE student_number = ?");
        $stmt->execute([$studentNumber]);
        $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt2 = $studentsDb->prepare("SELECT si.*, fs.program, fs.academic_year FROM student_invoices si LEFT JOIN fee_structures fs ON si.fee_structure_id = fs.id WHERE si.student_id = ? ORDER BY si.created_at DESC");
        $stmt2->execute([$studentNumber]);
        $invoices = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $stmt3 = $studentsDb->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt3->execute([$studentNumber]);
        $payments = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        $totalBilled = 0; $totalPaid = 0;
        foreach ($invoices as $inv) {
            $totalBilled += (float)($inv['total_amount'] ?? 0);
            $totalPaid += (float)($inv['amount_paid'] ?? 0);
        }
        $balanceInfo = ['total_billed' => $totalBilled, 'total_paid' => $totalPaid, 'balance' => $totalBilled - $totalPaid];
    } catch (Exception $e) {}
}

$fullName = $studentInfo ? htmlspecialchars(($studentInfo['surname'] ?? '') . ' ' . ($studentInfo['firstname'] ?? '')) : 'Student';
$program = $studentInfo ? htmlspecialchars($studentInfo['program'] ?? 'N/A') : 'N/A';
$balClass = $balanceInfo['balance'] > 0 ? 'danger' : 'success';
$balIcon = $balanceInfo['balance'] > 0 ? 'exclamation-triangle' : 'check';

$pageTitle = 'Student Fees Portal';
include_once __DIR__ . '/includes/dashboard_head.php';
include_once __DIR__ . '/includes/sidebar.php';
$customCss = <<<CSS
:root{--primary:#2c5f8a;--accent:#1a9e6e}
body{background:#f0f4f8}
.fee-card{border:none;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08);transition:transform .2s}
.fee-card:hover{transform:translateY(-2px)}
.fee-card .card-header{background:linear-gradient(135deg,#2c5f8a,#1a9e6e);padding:20px 24px;border:none}
.fee-card .card-header h5{font-weight:700}
.fee-card .card-body{padding:24px}
.kpi-stat{border-radius:12px;padding:16px 20px;height:100%;border:none;transition:transform .2s}
.kpi-stat:hover{transform:translateY(-2px)}
.kpi-stat .kpi-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem}
.bg-soft-success{background:#e6f7ee;color:#0a6e3a}
.bg-soft-danger{background:#fee9e7;color:#b71c1c}
.bg-soft-primary{background:#e3edf7;color:#1a4972}
.bg-soft-warning{background:#fef5e7;color:#92400e}
.badge-status{padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:600}
.nav-tabs .nav-link{color:#555;border:none;padding:10px 20px;font-weight:500;border-radius:10px 10px 0 0}
.nav-tabs .nav-link.active{color:var(--primary);background:#fff;font-weight:600;border-bottom:3px solid var(--primary)}
.nav-tabs .nav-link:hover:not(.active){color:var(--primary);background:rgba(44,95,138,.05)}
.pay-card{border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);transition:transform .2s;cursor:pointer}
.pay-card:hover{transform:translateY(-3px);box-shadow:0 8px 30px rgba(0,0,0,.1)}
.pay-card .pay-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
@media print{body{background:#fff!important}.sidebar,.main .nav-tabs,.no-print{display:none!important}.main{margin-left:0!important;padding:20px!important}}
@media(max-width:768px){.main{margin-left:0!important;padding:16px!important}}
CSS;
?>
<style><?php echo $customCss; ?></style>
</head>
<body>
<div class="main" style="margin-left:270px;padding:32px">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1" style="color:var(--primary)"><i class="fas fa-money-bill-wave me-2"></i>Fees Portal</h4>
      <p class="text-muted mb-0 small"><i class="fas fa-user me-1"></i><?php echo $fullName; ?> &middot; <?php echo $program; ?> &middot; <strong>Student #<?php echo htmlspecialchars($studentNumber); ?></strong></p>
    </div>
    <div class="d-flex gap-2 no-print">
      <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i>Print</button>
    </div>
  </div>

<?php if (!$studentNumber): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>No student number found. Please log in again.</div>
    <?php include_once __DIR__ . '/includes/dashboard_footer.php'; return; ?>
<?php endif; ?>

  <!-- KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="kpi-stat bg-white"><div class="d-flex align-items-center gap-3"><div class="kpi-icon bg-soft-primary"><i class="fas fa-file-invoice text-primary"></i></div><div><small class="text-muted d-block">Total Billed</small><strong class="fs-5">UGX <?php echo number_format($balanceInfo['total_billed']); ?></strong></div></div></div>
    </div>
    <div class="col-md-3 col-6">
      <div class="kpi-stat bg-white"><div class="d-flex align-items-center gap-3"><div class="kpi-icon bg-soft-success"><i class="fas fa-check-circle text-success"></i></div><div><small class="text-muted d-block">Total Paid</small><strong class="fs-5">UGX <?php echo number_format($balanceInfo['total_paid']); ?></strong></div></div></div>
    </div>
    <div class="col-md-3 col-6">
      <div class="kpi-stat bg-white"><div class="d-flex align-items-center gap-3"><div class="kpi-icon bg-soft-<?php echo $balClass; ?>"><i class="fas fa-<?php echo $balIcon; ?> text-<?php echo $balClass; ?>"></i></div><div><small class="text-muted d-block">Balance</small><strong class="fs-5">UGX <?php echo number_format($balanceInfo['balance']); ?></strong></div></div></div>
    </div>
    <div class="col-md-3 col-6">
      <div class="kpi-stat bg-white"><div class="d-flex align-items-center gap-3"><div class="kpi-icon bg-soft-warning"><i class="fas fa-receipt text-warning"></i></div><div><small class="text-muted d-block">Transactions</small><strong class="fs-5"><?php echo count($payments); ?></strong></div></div></div>
    </div>
  </div>

  <!-- Tab Navigation -->
  <ul class="nav nav-tabs mb-4 no-print" id="feeTabs" role="tablist">
    <li class="nav-item"><a class="nav-link <?php echo $view === 'dashboard' ? 'active' : ''; ?>" id="tab-dashboard" data-bs-toggle="tab" href="#dashboard" role="tab"><i class="fas fa-home me-1"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $view === 'statement' ? 'active' : ''; ?>" id="tab-statement" data-bs-toggle="tab" href="#statement" role="tab"><i class="fas fa-file-invoice me-1"></i>Fee Statement</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $view === 'pay' ? 'active' : ''; ?>" id="tab-pay" data-bs-toggle="tab" href="#pay" role="tab"><i class="fas fa-credit-card me-1"></i>Pay Fees</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $view === 'receipts' ? 'active' : ''; ?>" id="tab-receipts" data-bs-toggle="tab" href="#receipts" role="tab"><i class="fas fa-receipt me-1"></i>Receipts</a></li>
  </ul>

  <div class="tab-content">
    <!-- Dashboard Tab -->
    <div class="tab-pane fade <?php echo $view === 'dashboard' ? 'show active' : ''; ?>" id="dashboard" role="tabpanel">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card fee-card"><div class="card-header text-white"><h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Payments</h5></div><div class="card-body p-0">
<?php if (count($payments) > 0): ?>
            <div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Date</th><th>Amount</th><th>Method</th><th>Ref</th><th>Status</th></tr></thead><tbody>
<?php foreach (array_slice($payments, 0, 10) as $p): $statusClass = match($p['status'] ?? ''){'approved'=>'success','verified'=>'primary','rejected'=>'danger',default=>'warning'}; ?>
              <tr>
                <td class="small"><?php echo htmlspecialchars($p['created_at'] ?? '-'); ?></td>
                <td><strong>UGX <?php echo number_format($p['amount'] ?? 0); ?></strong></td>
                <td><?php echo htmlspecialchars($p['payment_method'] ?? '-'); ?></td>
                <td class="small"><?php echo htmlspecialchars($p['transaction_id'] ?? '-'); ?></td>
                <td><span class="badge-status bg-soft-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($p['status'] ?? 'pending'); ?></span></td>
              </tr>
<?php endforeach; ?>
            </tbody></table></div>
<?php else: ?>
            <div class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 d-block"></i><p>No payment records found.</p></div>
<?php endif; ?>
          </div></div>
        </div>
        <div class="col-lg-4">
          <div class="card fee-card"><div class="card-header text-white"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Summary</h5></div><div class="card-body">
            <div class="mb-3"><small class="text-muted d-block">Student</small><strong><?php echo $fullName; ?></strong></div>
            <div class="mb-3"><small class="text-muted d-block">Program</small><strong><?php echo $program; ?></strong></div>
<?php $outstandingCount = count(array_filter($invoices, fn($i) => ($i['status'] ?? '') !== 'paid' && ($i['status'] ?? '') !== 'cancelled')); ?>
            <div class="mb-3"><small class="text-muted d-block">Outstanding Invoices</small><strong class="text-<?php echo $balanceInfo['balance'] > 0 ? 'danger' : 'success'; ?>"><?php echo $outstandingCount; ?> invoices</strong></div>
            <hr>
            <p class="small text-muted mb-0"><i class="fas fa-arrow-up me-1"></i>Use the tabs above to view your full statement or make a payment.</p>
          </div></div>
        </div>
      </div>
    </div>

    <!-- Statement Tab -->
    <div class="tab-pane fade <?php echo $view === 'statement' ? 'show active' : ''; ?>" id="statement" role="tabpanel">
      <div class="card fee-card"><div class="card-header text-white"><h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Fee Statement</h5></div><div class="card-body p-0">
<?php if (count($invoices) > 0): ?>
        <div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>#</th><th>Invoice</th><th>Program</th><th>Year</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Due</th><th>Status</th></tr></thead><tbody>
<?php $i = 1; foreach ($invoices as $inv): $bal = ($inv['total_amount'] ?? 0) - ($inv['amount_paid'] ?? 0); $balClass2 = $bal > 0 ? 'danger' : 'success'; $statusClass2 = match($inv['status'] ?? ''){'paid'=>'success','partial'=>'warning','overdue'=>'danger','pending'=>'primary','cancelled'=>'secondary',default=>'secondary'}; ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td class="small"><?php echo htmlspecialchars($inv['invoice_number'] ?? '-'); ?></td>
            <td class="small"><?php echo htmlspecialchars($inv['program'] ?? '-'); ?></td>
            <td class="small"><?php echo htmlspecialchars($inv['academic_year'] ?? '-'); ?></td>
            <td><strong>UGX <?php echo number_format($inv['total_amount'] ?? 0); ?></strong></td>
            <td>UGX <?php echo number_format($inv['amount_paid'] ?? 0); ?></td>
            <td><strong class="text-<?php echo $balClass2; ?>">UGX <?php echo number_format($bal); ?></strong></td>
            <td class="small"><?php echo htmlspecialchars($inv['due_date'] ?? '-'); ?></td>
            <td><span class="badge-status bg-soft-<?php echo $statusClass2; ?>"><?php echo htmlspecialchars($inv['status'] ?? 'unknown'); ?></span></td>
          </tr>
<?php endforeach; ?>
        </tbody></table></div>
<?php else: ?>
        <div class="text-center py-5 text-muted"><i class="fas fa-file-invoice fa-3x mb-3 d-block"></i><p>No invoices found for your account.</p></div>
<?php endif; ?>
      </div></div>
    </div>

    <!-- Pay Fees Tab -->
    <div class="tab-pane fade <?php echo $view === 'pay' ? 'show active' : ''; ?>" id="pay" role="tabpanel">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card fee-card"><div class="card-header text-white"><h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Make a Payment</h5></div><div class="card-body">
            <p class="text-muted mb-4">Select a payment method below to proceed with your fee payment.</p>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card pay-card p-3" onclick="showPayMethod('mobile')">
                  <div class="d-flex align-items-center gap-3">
                    <div class="pay-icon bg-soft-success"><i class="fas fa-mobile-alt text-success fa-2x"></i></div>
                    <div><h6 class="mb-1 fw-bold">Mobile Money</h6><small class="text-muted">MTN / Airtel</small></div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card pay-card p-3" onclick="showPayMethod('bank')">
                  <div class="d-flex align-items-center gap-3">
                    <div class="pay-icon bg-soft-primary"><i class="fas fa-university text-primary fa-2x"></i></div>
                    <div><h6 class="mb-1 fw-bold">Bank Transfer</h6><small class="text-muted">Deposit & upload proof</small></div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card pay-card p-3" onclick="showPayMethod('cash')">
                  <div class="d-flex align-items-center gap-3">
                    <div class="pay-icon bg-soft-warning"><i class="fas fa-money-bill-wave text-warning fa-2x"></i></div>
                    <div><h6 class="mb-1 fw-bold">Cash Payment</h6><small class="text-muted">Pay at school bursar</small></div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card pay-card p-3" onclick="showPayMethod('cheque')">
                  <div class="d-flex align-items-center gap-3">
                    <div class="pay-icon" style="background:#f0e6ff;color:#6b3fa0"><i class="fas fa-file-invoice fa-2x"></i></div>
                    <div><h6 class="mb-1 fw-bold">Cheque</h6><small class="text-muted">Banker's cheque</small></div>
                  </div>
                </div>
              </div>
            </div>

            <div id="payFormContainer" class="mt-4" style="display:none">
              <hr>
              <form id="payForm" onsubmit="submitPayment(event)">
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($studentNumber); ?>">
                <input type="hidden" name="payment_method" id="payMethod">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Amount (UGX)</label>
                    <input type="number" name="amount" class="form-control" min="1000" step="500" required placeholder="e.g. 500000">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold">Phone Number <small class="text-muted">(if mobile money)</small></label>
                    <input type="text" name="phone" class="form-control" id="phoneField" placeholder="07XX XXX XXX" disabled>
                  </div>
                  <div class="col-12" id="bankFields" style="display:none">
                    <div class="alert alert-info mb-0"><i class="fas fa-info-circle me-1"></i>Bank Details: <strong>Iganga School of Nursing &amp; Midwifery</strong><br>Bank: Stanbic Bank Uganda &middot; Account: 9030012345678 &middot; Branch: Iganga</div>
                  </div>
                  <div class="col-12" id="proofUpload" style="display:none">
                    <label class="form-label small fw-semibold">Upload Proof of Payment</label>
                    <input type="file" name="proof" class="form-control" accept="image/*,.pdf">
                    <small class="text-muted">Screenshot or photo of bank receipt/deposit slip</small>
                  </div>
                  <div class="col-12">
                    <label class="form-label small fw-semibold">Notes (optional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Any additional information"></textarea>
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-2"></i>Submit Payment Request</button>
                  </div>
                </div>
              </form>
            </div>
          </div></div>
        </div>
        <div class="col-lg-4">
          <div class="card fee-card"><div class="card-header text-white"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Payment Info</h5></div><div class="card-body">
            <p class="small text-muted mb-3">Your payment request will be reviewed and verified by the bursar's office. Once approved, your fee balance will be updated automatically.</p>
            <hr>
            <h6 class="fw-bold">Current Balance</h6>
            <h3 class="text-<?php echo $balClass; ?> fw-bold">UGX <?php echo number_format($balanceInfo['balance']); ?></h3>
            <hr>
            <p class="small text-muted mb-0"><i class="fas fa-clock me-1"></i>Verification typically takes 24-48 hours.</p>
          </div></div>
        </div>
      </div>
    </div>

    <!-- Receipts Tab -->
    <div class="tab-pane fade <?php echo $view === 'receipts' ? 'show active' : ''; ?>" id="receipts" role="tabpanel">
      <div class="card fee-card"><div class="card-header text-white"><h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Payment Receipts</h5></div><div class="card-body p-0">
<?php
$receipts = [];
try {
    $stmt = $studentsDb->prepare("SELECT * FROM payment_receipts WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->execute([$studentNumber]);
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<?php if (count($receipts) > 0): ?>
        <div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Receipt #</th><th>Date</th><th>Payment Ref</th><th>Amount</th><th>Method</th><th class="no-print">Actions</th></tr></thead><tbody>
<?php foreach ($receipts as $r): ?>
          <tr>
            <td class="small fw-semibold"><?php echo htmlspecialchars($r['receipt_number'] ?? '-'); ?></td>
            <td class="small"><?php echo htmlspecialchars($r['created_at'] ?? '-'); ?></td>
            <td class="small"><?php echo htmlspecialchars($r['payment_id'] ?? '-'); ?></td>
            <td><strong>UGX <?php echo number_format($r['amount'] ?? 0); ?></strong></td>
            <td><?php echo htmlspecialchars($r['payment_method'] ?? '-'); ?></td>
            <td class="no-print">
              <a href="javascript:void(0)" onclick="printReceipt(this)" class="btn btn-sm btn-outline-primary"><i class="fas fa-print"></i></a>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody></table></div>
<?php else: ?>
        <div class="text-center py-5 text-muted"><i class="fas fa-receipt fa-3x mb-3 d-block"></i><p>No receipts available yet. Receipts are generated after payment verification.</p></div>
<?php endif; ?>
      </div></div>
    </div>
  </div>
</div>

<script>
function showPayMethod(method) {
  var container = document.getElementById('payFormContainer');
  container.style.display = 'block';
  document.getElementById('payMethod').value = method;
  document.getElementById('phoneField').disabled = method !== 'mobile';
  document.getElementById('bankFields').style.display = method === 'bank' ? 'block' : 'none';
  document.getElementById('proofUpload').style.display = method === 'bank' ? 'block' : 'none';
  var cards = document.querySelectorAll('.pay-card');
  for (var i = 0; i < cards.length; i++) { cards[i].style.border = 'none'; }
  event.currentTarget.style.border = '2px solid var(--accent)';
  container.scrollIntoView({behavior:'smooth',block:'center'});
}
function submitPayment(e) {
  e.preventDefault();
  var form = document.getElementById('payForm');
  var formData = new FormData(form);
  formData.append('action', 'student_payment_request');
  fetch('includes/ajax_student_payment.php', {method:'POST', body:formData})
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.success) {
        alert('Payment request submitted successfully! Reference: ' + d.reference);
        location.reload();
      } else {
        alert('Error: ' + (d.message || 'Submission failed'));
      }
    })
    .catch(function() { alert('Network error. Please try again.'); });
}
function printReceipt(el) {
  var row = el.closest('tr');
  var cells = row.querySelectorAll('td');
  var receiptNo = cells[0].textContent.trim();
  var date = cells[1].textContent.trim();
  var amount = cells[3].textContent.trim();
  var method = cells[4].textContent.trim();
  var w = window.open('', '_blank');
  w.document.write('<!DOCTYPE html><html><head><title>Receipt ' + receiptNo + '</title><style>body{font-family:Arial,sans-serif;padding:40px;max-width:600px;margin:auto}.header{text-align:center;margin-bottom:30px;border-bottom:2px solid #333;padding-bottom:20px}.details{margin-bottom:20px}.details td{padding:8px 0}.amount{font-size:1.3rem;font-weight:bold;color:#1a9e6e}.footer{margin-top:40px;padding-top:20px;border-top:1px solid #ccc;text-align:center;font-size:.85rem;color:#666}</style></head><body><div class="header"><h2>Payment Receipt</h2><p>Iganga School of Nursing & Midwifery</p></div><table class="details"><tr><td><strong>Receipt No:</strong></td><td>' + receiptNo + '</td></tr><tr><td><strong>Date:</strong></td><td>' + date + '</td></tr><tr><td><strong>Payment Method:</strong></td><td>' + method + '</td></tr><tr><td><strong>Amount:</strong></td><td class="amount">' + amount + '</td></tr><tr><td><strong>Student:</strong></td><td><?php echo $fullName; ?></td></tr><tr><td><strong>Student #:</strong></td><td><?php echo htmlspecialchars($studentNumber); ?></td></tr></table><div class="footer"><p>This is a system-generated receipt. For official receipts, contact the bursar\'s office.</p><p>Iganga School of Nursing & Midwifery &copy; 2025</p></div></body></html>');
  w.document.close();
  w.print();
}
</script>
<?php include_once __DIR__ . '/includes/dashboard_footer.php'; ?>
</body>
</html>
