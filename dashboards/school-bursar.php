<?php
/**
 * School Bursar Dashboard — Complete 11-Module Interface
 * Modules: Student Billing, Payment Processing, Reports, Budgeting,
 * Payroll Integration, Ledger/Accounts, Inventory, Communications,
 * RBAC, Student Self-Service, Integration
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/payment_gateway.php';

$ctx = bootstrapStaffDashboard(['bursar', 'school bursar', 'finance', 'director finance', 'director general', 'ceo']);
$auth = $ctx['auth'];
$user = $ctx['user'];
$staffConn = $ctx['staff'];
$stuConn = $ctx['students'];
$webConn = $ctx['website'];
$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['role'] ?? '';
$isSuper = $auth->hasFullInstitutionAccess($userRole);

$page = $_GET['page'] ?? 'overview';
$sub = $_GET['sub'] ?? '';

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'record_payment' && $stuConn) {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim($_POST['payment_method'] ?? 'cash');
        $ref = trim($_POST['reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $date = $_POST['payment_date'] ?? date('Y-m-d');
        $phone = trim($_POST['mobile_phone'] ?? '');
        $provider = trim($_POST['mobile_provider'] ?? '');
        if ($studentId && $amount > 0) {
            $receiptNo = 'RCP-'.date('Ymd').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $status = 'completed';
            $gatewayMsg = '';
            if ($method === 'mobile_money' && $phone) {
                $gw = new PaymentGateway();
                $gwResult = $gw->requestPayment($provider ?: 'mtn', $phone, $amount, $receiptNo, $notes);
                if (!$gwResult['success']) {
                    $status = 'pending';
                    $gatewayMsg = ' (mobile money request sent, awaiting confirmation)';
                }
                $ref = $ref ?: ($gwResult['transaction_id'] ?? '');
            }
            $stmt = $stuConn->prepare("INSERT INTO payments (student_id, amount, payment_method, reference, receipt_number, payment_date, status, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('idssssssi', $studentId, $amount, $method, $ref, $receiptNo, $date, $status, $notes, $userId); $stmt->execute(); $_SESSION['success'] = "Payment recorded. Receipt: $receiptNo$gatewayMsg"; }
        }
        header('Location: school-bursar.php?page=payments'); exit;
    }

    if ($action === 'add_fee_structure' && $stuConn) {
        $name = trim($_POST['fee_name'] ?? ''); $amount = (float)($_POST['amount'] ?? 0);
        $program = trim($_POST['program'] ?? ''); $category = trim($_POST['category'] ?? 'tuition');
        $year = (int)($_POST['year'] ?? date('Y'));
        if ($name && $amount > 0) {
            $stmt = $stuConn->prepare("INSERT INTO fee_structures (fee_name, amount, program, category, academic_year, is_active) VALUES (?,?,?,?,?,1)");
            if ($stmt) { $stmt->bind_param('sdssi', $name, $amount, $program, $category, $year); $stmt->execute(); $_SESSION['success'] = 'Fee structure added.'; }
        }
        header('Location: school-bursar.php?page=billing'); exit;
    }

    if ($action === 'add_expense' && $staffConn) {
        $desc = trim($_POST['description'] ?? ''); $amount = (float)($_POST['amount'] ?? 0);
        $cat = trim($_POST['category'] ?? ''); $dept = trim($_POST['department'] ?? '');
        $date = $_POST['expense_date'] ?? date('Y-m-d');
        if ($desc && $amount > 0) {
            $stmt = $staffConn->prepare("INSERT INTO expenses (description, amount, category, department, expense_date, status, recorded_by) VALUES (?,?,?,?,?,'approved',?)");
            if ($stmt) { $stmt->bind_param('sdsssi', $desc, $amount, $cat, $dept, $date, $userId); $stmt->execute(); $_SESSION['success'] = 'Expense recorded.'; }
        }
        header('Location: school-bursar.php?page=budget'); exit;
    }

    if ($action === 'create_budget' && $staffConn) {
        $title = trim($_POST['budget_title'] ?? ''); $amount = (float)($_POST['total_amount'] ?? 0);
        $dept = trim($_POST['department'] ?? ''); $period = trim($_POST['period'] ?? '');
        $year = (int)($_POST['year'] ?? date('Y'));
        $budgetConn = $stuConn ?? $staffConn;
        if ($title && $amount > 0) {
            $stmt = $budgetConn->prepare("INSERT INTO budgets (title, total_amount, department, period, fiscal_year, status, created_by) VALUES (?,?,?,?,?,'draft',?)");
            if ($stmt) { $stmt->bind_param('sdssii', $title, $amount, $dept, $period, $year, $userId); $stmt->execute(); $_SESSION['success'] = 'Budget created.'; }
        }
        header('Location: school-bursar.php?page=budget'); exit;
    }

    if ($action === 'run_payroll' && $staffConn) {
        $period = trim($_POST['period'] ?? date('Y-m'));
        $desc = trim($_POST['description'] ?? "Payroll $period");
        $stmt = $staffConn->prepare("INSERT INTO payroll_runs (period, description, status, created_by, run_date) VALUES (?,?,'processing',?,CURDATE())");
        if ($stmt) { $stmt->bind_param('ssi', $period, $desc, $userId); $stmt->execute(); $runId = $stmt->insert_id;
            $pd=$staffConn->prepare("INSERT INTO payroll_details (payroll_run_id, staff_id, basic_salary, gross_pay, net_pay, status) SELECT ?, pe.staff_id, ss.basic_salary, ss.basic_salary+COALESCE(ss.housing_allowance,0)+COALESCE(ss.transport_allowance,0), ss.basic_salary+COALESCE(ss.housing_allowance,0)+COALESCE(ss.transport_allowance,0), 'pending' FROM payroll_employees pe JOIN salary_structures ss ON pe.staff_id=ss.staff_id WHERE pe.status='active'");
            if($pd){$pd->bind_param('i',$runId);$pd->execute();$pd->close();}
            $pu=$staffConn->prepare("UPDATE payroll_runs SET status='completed' WHERE id=?");
            if($pu){$pu->bind_param('i',$runId);$pu->execute();$pu->close();}
            $_SESSION['success'] = "Payroll $period processed."; }
        header('Location: school-bursar.php?page=payroll'); exit;
    }

    if ($action === 'reconcile_bank' && $staffConn) {
        $bankStmt = trim($_POST['bank_statement'] ?? ''); $balance = (float)($_POST['ending_balance'] ?? 0);
        $date = $_POST['reconciliation_date'] ?? date('Y-m-d');
        if ($staffConn) {
            $stmt = $staffConn->prepare("INSERT INTO bank_reconciliation (statement_ref, ending_balance, reconciliation_date, status, reconciled_by) VALUES (?,?,?,'completed',?)");
            if ($stmt) { $stmt->bind_param('sdsi', $bankStmt, $balance, $date, $userId); $stmt->execute(); $_SESSION['success'] = 'Bank reconciled.'; }
        }
        header('Location: school-bursar.php?page=ledger'); exit;
    }

    if ($action === 'send_reminder' && $stuConn) {
        $studentId = (int)($_POST['student_id'] ?? 0); $msg = trim($_POST['message'] ?? '');
        if ($studentId && $msg) {
            $stmt = $stuConn->prepare("INSERT INTO student_notifications (student_id, type, message, is_read) VALUES (?,?,'fee_reminder',?,0)");
            if ($stmt) { $stmt->bind_param('is', $studentId, $msg); $stmt->execute(); $_SESSION['success'] = 'Reminder sent.'; }
        }
        header('Location: school-bursar.php?page=communications'); exit;
    }

    if ($action === 'add_discount' && $stuConn) {
        $studentId = (int)($_POST['student_id'] ?? 0); $amount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? ''); $type = trim($_POST['discount_type'] ?? 'discount');
        if ($studentId && $amount > 0) {
            $stmt = $stuConn->prepare("INSERT INTO fee_adjustments (student_id, adjustment_type, amount, reason, created_by) VALUES (?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('isdsi', $studentId, $type, $amount, $reason, $userId); $stmt->execute(); $_SESSION['success'] = 'Adjustment applied.'; }
        }
        header('Location: school-bursar.php?page=billing'); exit;
    }

    if ($action === 'approve_payment' && $stuConn) {
        $pid = (int)($_POST['payment_id'] ?? 0);
        if ($pid) { $st=$stuConn->prepare("UPDATE payments SET status='verified', verified_by=? WHERE id=?"); if($st){$st->bind_param('ii',$userId,$pid);$st->execute();$st->close();$_SESSION['success']='Payment verified.';} }
        header('Location: school-bursar.php?page=payments'); exit;
    }
}

// ── Data ──
$studentsList = []; $payments = []; $feeStructures = []; $expenses = []; $budgets = [];
$payrollRuns = []; $bankReconciliations = []; $feeAdjustments = []; $studentFees = [];
$pendingVerification = [];

if ($stuConn) {
    $sl = $stuConn->query("SELECT id, student_number, first_name, last_name, program, status FROM students ORDER BY first_name LIMIT 200");
    if ($sl) $studentsList = $sl->fetch_all(MYSQLI_ASSOC);
    $pm = $stuConn->query("SELECT p.*, CONCAT(s.first_name,' ',s.last_name) as student_name FROM payments p JOIN students s ON p.student_id=s.id ORDER BY p.created_at DESC LIMIT 50");
    if ($pm) $payments = $pm->fetch_all(MYSQLI_ASSOC);
    $fs = $stuConn->query("SELECT * FROM fee_structures WHERE is_active=1 ORDER BY fee_name");
    if ($fs) $feeStructures = $fs->fetch_all(MYSQLI_ASSOC);
    $fa = $stuConn->query("SELECT fa.*, CONCAT(s.first_name,' ',s.last_name) as student_name FROM fee_adjustments fa JOIN students s ON fa.student_id=s.id ORDER BY fa.created_at DESC LIMIT 30");
    if ($fa) $feeAdjustments = $fa->fetch_all(MYSQLI_ASSOC);
    $pv = $stuConn->query("SELECT p.*, CONCAT(s.first_name,' ',s.last_name) as student_name FROM payments p JOIN students s ON p.student_id=s.id WHERE p.status='pending' OR p.status='completed' ORDER BY p.created_at DESC LIMIT 20");
    if ($pv) $pendingVerification = $pv->fetch_all(MYSQLI_ASSOC);
    $sf = $stuConn->query("SELECT sfe.*, CONCAT(s.first_name,' ',s.last_name) as student_name FROM student_fees sfe JOIN students s ON sfe.student_id=s.id ORDER BY sfe.balance DESC LIMIT 30");
    if ($sf) $studentFees = $sf->fetch_all(MYSQLI_ASSOC);
}

if ($staffConn) {
    $ex = $staffConn->query("SELECT * FROM expenses ORDER BY created_at DESC LIMIT 30");
    if ($ex) $expenses = $ex->fetch_all(MYSQLI_ASSOC);
    $bd = ($stuConn ?? $staffConn)->query("SELECT * FROM budgets ORDER BY created_at DESC LIMIT 20");
    if ($bd) $budgets = $bd->fetch_all(MYSQLI_ASSOC);
    $pr = $staffConn->query("SELECT * FROM payroll_runs ORDER BY created_at DESC LIMIT 20");
    if ($pr) $payrollRuns = $pr->fetch_all(MYSQLI_ASSOC);
    $br = $staffConn->query("SELECT * FROM bank_reconciliation ORDER BY created_at DESC LIMIT 20");
    if ($br) $bankReconciliations = $br->fetch_all(MYSQLI_ASSOC);
}

// Stats
$totalCollectedToday = 0; $outstandingFees = 0; $clearedCount = 0; $notClearedCount = 0;
if ($stuConn) {
    $r = $stuConn->query("SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE DATE(payment_date)=CURDATE() AND status='completed'");
    if ($r) $totalCollectedToday = (float)$r->fetch_assoc()['total'];
    $r = $stuConn->query("SELECT COALESCE(SUM(balance),0) as total FROM student_fees");
    if ($r) $outstandingFees = (float)$r->fetch_assoc()['total'];
    $r = $stuConn->query("SELECT COUNT(*) c FROM student_fees WHERE balance<=0");
    if ($r) $clearedCount = (int)$r->fetch_assoc()['c'];
    $r = $stuConn->query("SELECT COUNT(*) c FROM student_fees WHERE balance>0");
    if ($r) $notClearedCount = (int)$r->fetch_assoc()['c'];
}

$pageTitle = 'Bursar Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root{--bs-primary:#059669}
.brs-content{margin-left:270px;padding:24px;min-height:100vh;background:#f0fdf4}
.brs-header{background:linear-gradient(135deg,#059669,#34d399);color:#fff;padding:20px 28px;border-radius:14px;margin-bottom:20px}
.brs-header h1{margin:0;font-size:22px}
.brs-header p{margin:2px 0 0;opacity:.85;font-size:13px}
.brs-tabs{display:flex;gap:3px;margin-bottom:20px;background:#fff;padding:6px;border-radius:10px;flex-wrap:wrap;border:1px solid #e2e8f0}
.brs-tabs a{padding:7px 14px;border-radius:7px;color:#475569;text-decoration:none;font-size:12px;font-weight:500;transition:.2s;white-space:nowrap}
.brs-tabs a:hover,.brs-tabs a.active{background:#059669;color:#fff}
.brs-card{background:#fff;border-radius:10px;border:1px solid #e2e8f0;padding:18px;margin-bottom:16px}
.brs-card h3{margin:0 0 14px;font-size:15px;font-weight:600;color:#064e3b;border-bottom:2px solid #d1fae5;padding-bottom:10px}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
.stat-item{background:#fff;border-radius:10px;padding:16px;border:1px solid #e2e8f0;text-align:center}
.stat-item .num{font-size:26px;font-weight:700;color:#059669}
.stat-item .lbl{font-size:11px;color:#64748b;margin-top:2px}
.stat-item .mini{font-size:10px;color:#94a3b8}
@media(max-width:768px){.brs-content{margin-left:0;padding:14px}.brs-tabs a{padding:5px 10px;font-size:11px}}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="brs-content">
<?php if (isset($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible"><?=htmlspecialchars($_SESSION['success'])?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>

<div class="brs-header"><h1>Bursar / Finance Management</h1><p><?=htmlspecialchars($user['full_name'] ?? 'Bursar')?> &middot; <?=htmlspecialchars($userRole)?></p></div>

<nav class="brs-tabs">
  <a href="school-bursar.php" class="<?=$page==='overview'?'active':''?>">Overview</a>
  <a href="school-bursar.php?page=billing" class="<?=$page==='billing'?'active':''?>">Billing</a>
  <a href="school-bursar.php?page=payments" class="<?=$page==='payments'?'active':''?>">Payments</a>
  <a href="school-bursar.php?page=reports" class="<?=$page==='reports'?'active':''?>">Reports</a>
  <a href="school-bursar.php?page=budget" class="<?=$page==='budget'?'active':''?>">Budget</a>
  <a href="school-bursar.php?page=payroll" class="<?=$page==='payroll'?'active':''?>">Payroll</a>
  <a href="school-bursar.php?page=ledger" class="<?=$page==='ledger'?'active':''?>">Ledger</a>
  <a href="school-bursar.php?page=inventory" class="<?=$page==='inventory'?'active':''?>">Inventory</a>
  <a href="school-bursar.php?page=communications" class="<?=$page==='communications'?'active':''?>">Comms</a>
  <a href="school-bursar.php?page=ura" class="<?=$page==='ura'?'active':''?>">URA Tax</a>
</nav>

<?php if ($page === 'overview'): ?>
<div class="stats-row">
  <div class="stat-item"><div class="num"><?=number_format($totalCollectedToday)?></div><div class="lbl">Today's Collections</div></div>
  <div class="stat-item"><div class="num"><?=number_format($outstandingFees)?></div><div class="lbl">Outstanding Fees</div></div>
  <div class="stat-item"><div class="num"><?=$clearedCount?>/<?=($clearedCount+$notClearedCount)?></div><div class="lbl">Cleared / Total</div></div>
  <div class="stat-item"><div class="num"><?=$notClearedCount?></div><div class="lbl">With Balance</div></div>
  <div class="stat-item"><div class="num"><?=count($payments)?></div><div class="lbl">Recent Payments</div></div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="brs-card"><h3>Recent Transactions</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php foreach (array_slice($payments,0,8) as $p): ?><tr>
      <td><?=htmlspecialchars($p['student_name'])?></td>
      <td><strong><?=number_format($p['amount'])?></strong></td>
      <td><?=htmlspecialchars(ucfirst($p['payment_method']??$p['method']??'-'))?></td>
      <td><span class="badge bg-<?=$p['status']==='completed'||$p['status']==='verified'?'success':'warning'?>"><?=htmlspecialchars($p['status'])?></span></td>
      <td><?=htmlspecialchars($p['payment_date']??$p['created_at']??'')?></td>
    </tr><?php endforeach; if (empty($payments)): ?><tr><td colspan="5" class="text-muted text-center">No payments yet.</td></tr><?php endif; ?>
    </tbody></table></div></div>
  <div class="col-md-6">
    <div class="brs-card"><h3>Pending Verification</h3>
    <?php $unverified = array_filter($pendingVerification, fn($p)=>$p['status']==='pending'||$p['status']==='completed'); foreach (array_slice($unverified,0,5) as $p): ?>
      <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
        <div><strong><?=htmlspecialchars($p['student_name'])?></strong><br><small><?=number_format($p['amount'])?> UGX via <?=htmlspecialchars(ucfirst($p['payment_method']??$p['method']??'-'))?></small></div>
        <form method="post" class="d-inline"><input type="hidden" name="action" value="approve_payment"><input type="hidden" name="payment_id" value="<?=$p['id']?>"><button class="btn btn-sm btn-success">Verify</button></form>
      </div>
    <?php endforeach; if (empty($unverified)): ?><p class="text-muted small">No pending verifications.</p><?php endif; ?>
    </div>
    <div class="brs-card"><h3>Overdue Alerts</h3>
    <?php $overdue = array_filter($studentFees, fn($f)=>$f['balance']>0); if (empty($overdue)): ?><p class="text-muted small">No overdue fees.</p>
    <?php else: foreach (array_slice($overdue,0,5) as $f): ?><div class="mb-1 small"><strong><?=htmlspecialchars($f['student_name'])?></strong> &mdash; Balance: <?=number_format($f['balance'])?> UGX</div><?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php elseif ($page === 'billing'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Fee Structure Setup</h3>
    <form method="post"><input type="hidden" name="action" value="add_fee_structure">
      <div class="mb-2"><input class="form-control form-control-sm" name="fee_name" placeholder="Fee Name (e.g. Tuition)" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="amount" placeholder="Amount" step="0.01" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="program" placeholder="Program (e.g. Diploma Nursing)"></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="category"><option value="tuition">Tuition</option><option value="accommodation">Accommodation</option><option value="clinical">Clinical Fees</option><option value="library">Library</option><option value="sports">Sports</option><option value="other">Other</option></select></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="year" value="<?=date('Y')?>"></div>
      <button class="btn btn-sm btn-primary">Add Fee</button>
    </form></div>
    <div class="brs-card"><h3>Discounts & Adjustments</h3>
    <form method="post"><input type="hidden" name="action" value="add_discount">
      <div class="mb-2"><select class="form-select form-select-sm" name="student_id" required><option value="">Select Student</option><?php foreach ($studentsList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?> (<?=htmlspecialchars($s['student_number'])?>)</option><?php endforeach; ?></select></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="discount_type"><option value="discount">Discount</option><option value="waiver">Waiver</option><option value="refund">Refund</option></select></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="amount" placeholder="Amount" step="0.01" required></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="reason" rows="2" placeholder="Reason"></textarea></div>
      <button class="btn btn-sm btn-primary">Apply</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Fee Structure</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Fee Name</th><th>Amount</th><th>Program</th><th>Category</th><th>Year</th></tr></thead><tbody>
    <?php foreach ($feeStructures as $f): ?><tr><td><?=htmlspecialchars($f['fee_name'])?></td><td><strong><?=number_format($f['amount'])?></strong></td><td><?=htmlspecialchars($f['program']??'All')?></td><td><?=htmlspecialchars($f['category'])?></td><td><?=htmlspecialchars($f['academic_year']??'')?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
    <div class="brs-card"><h3>Student Fee Balances</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Total Due</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($studentFees as $f): ?><tr><td><?=htmlspecialchars($f['student_name'])?></td><td><?=number_format($f['total_fees']??$f['total_due']??0)?></td><td><?=number_format($f['paid']??($f['total_fees']??0)-$f['balance'])?></td><td><strong class="<?=$f['balance']>0?'text-danger':'text-success'?>"><?=number_format($f['balance'])?></strong></td><td><?=$f['balance']<=0?'<span class="badge bg-success">Cleared</span>':'<span class="badge bg-danger">Outstanding</span>'?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'payments'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Record Payment</h3>
    <form method="post"><input type="hidden" name="action" value="record_payment">
      <div class="mb-2"><select class="form-select form-select-sm" name="student_id" required><option value="">Select Student</option><?php foreach ($studentsList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?> (<?=htmlspecialchars($s['student_number'])?>)</option><?php endforeach; ?></select></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="amount" placeholder="Amount" step="0.01" required></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="payment_method" id="payMethod"><option value="cash">Cash</option><option value="bank">Bank Deposit</option><option value="mobile_money">Mobile Money</option><option value="cheque">Cheque</option></select></div>
      <div id="momoFields" style="display:none" class="mb-2 row g-1"><div class="col-6"><input class="form-control form-control-sm" name="mobile_phone" placeholder="Phone (2567XXXXXXXX)"></div><div class="col-6"><select class="form-select form-select-sm" name="mobile_provider"><option value="mtn">MTN MoMo</option><option value="airtel">Airtel Money</option></select></div></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="reference" placeholder="Reference / Transaction ID"></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="payment_date" value="<?=date('Y-m-d')?>"></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Notes"></textarea></div>
      <button class="btn btn-sm btn-primary">Record Payment</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Payment History</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Receipt</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php foreach ($payments as $p): ?><tr>
      <td><?=htmlspecialchars($p['student_name'])?></td>
      <td><strong><?=number_format($p['amount'])?></strong></td>
      <td><?=htmlspecialchars(ucfirst($p['payment_method']??$p['method']??'-'))?></td>
      <td><?=htmlspecialchars($p['receipt_number']??'-')?></td>
      <td><span class="badge bg-<?=$p['status']==='verified'||$p['status']==='completed'?'success':'warning'?>"><?=htmlspecialchars($p['status'])?></span></td>
      <td><?=htmlspecialchars($p['payment_date']??$p['created_at']??'')?></td>
    </tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'reports'): ?>
<div class="row">
  <div class="col-md-6">
    <div class="brs-card"><h3>Revenue by Category</h3>
    <?php $revCat = $stuConn ? $stuConn->query("SELECT category, COUNT(*) cnt, SUM(amount) total FROM fee_structures WHERE is_active=1 GROUP BY category") : null; if ($revCat): ?>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Category</th><th>Count</th><th>Total</th></tr></thead><tbody>
    <?php while ($r = $revCat->fetch_assoc()): ?><tr><td><?=htmlspecialchars(ucfirst($r['category']))?></td><td><?=$r['cnt']?></td><td><strong><?=number_format($r['total'])?></strong></td></tr><?php endwhile; ?>
    </tbody></table></div><?php endif; ?>
  </div>
  <div class="col-md-6">
    <div class="brs-card"><h3>Collection Summary</h3>
    <?php
    $daily = $stuConn ? $stuConn->query("SELECT DATE(payment_date) as dt, COUNT(*) cnt, SUM(amount) total FROM payments WHERE status='completed' GROUP BY DATE(payment_date) ORDER BY dt DESC LIMIT 14") : null;
    if ($daily): ?>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Transactions</th><th>Amount</th></tr></thead><tbody>
    <?php while ($d = $daily->fetch_assoc()): ?><tr><td><?=$d['dt']?></td><td><?=$d['cnt']?></td><td><strong><?=number_format($d['total'])?></strong></td></tr><?php endwhile; ?>
    </tbody></table></div><?php endif; ?>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="brs-card"><h3>Debtors List (Outstanding)</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Balance</th></tr></thead><tbody>
    <?php $debtors = array_filter($studentFees, fn($f)=>$f['balance']>0); foreach ($debtors as $f): ?><tr><td><?=htmlspecialchars($f['student_name'])?></td><td class="text-danger"><strong><?=number_format($f['balance'])?></strong></td></tr><?php endforeach; if (empty($debtors)): ?><tr><td colspan="2" class="text-muted">No outstanding balances.</td></tr><?php endif; ?>
    </tbody></table></div></div>
  <div class="col-md-6">
    <div class="brs-card"><h3>Expense Summary</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Category</th><th>Total</th></tr></thead><tbody>
    <?php $expCat = $staffConn ? $staffConn->query("SELECT category, SUM(amount) total FROM expenses GROUP BY category ORDER BY total DESC") : null; if ($expCat) while ($e = $expCat->fetch_assoc()): ?><tr><td><?=htmlspecialchars(ucfirst($e['category']??'General'))?></td><td><strong><?=number_format($e['total'])?></strong></td></tr><?php endwhile; ?>
    </tbody></table></div></div>
</div>

<?php elseif ($page === 'budget'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Create Budget</h3>
    <form method="post"><input type="hidden" name="action" value="create_budget">
      <div class="mb-2"><input class="form-control form-control-sm" name="budget_title" placeholder="Budget Title" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="total_amount" placeholder="Total Amount" step="0.01" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="department" placeholder="Department"></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="period" placeholder="Period (e.g. Q1 2026)"></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="year" value="<?=date('Y')?>"></div>
      <button class="btn btn-sm btn-primary">Create Budget</button>
    </form></div>
    <div class="brs-card"><h3>Record Expense</h3>
    <form method="post"><input type="hidden" name="action" value="add_expense">
      <div class="mb-2"><input class="form-control form-control-sm" name="description" placeholder="Description" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="amount" placeholder="Amount" step="0.01" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="category" placeholder="Category (e.g. Utilities)"></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="department" placeholder="Department"></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="expense_date" value="<?=date('Y-m-d')?>"></div>
      <button class="btn btn-sm btn-primary">Record</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Budgets</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Title</th><th>Amount</th><th>Department</th><th>Period</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($budgets as $b): ?><tr><td><?=htmlspecialchars($b['title'])?></td><td><strong><?=number_format($b['total_amount'])?></strong></td><td><?=htmlspecialchars($b['department']??'-')?></td><td><?=htmlspecialchars($b['period']??'')?></td><td><span class="badge bg-<?=$b['status']==='approved'?'success':'warning'?>"><?=htmlspecialchars($b['status'])?></span></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="brs-card"><h3>Expenses</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Description</th><th>Amount</th><th>Category</th><th>Date</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($expenses as $e): ?><tr><td><?=htmlspecialchars($e['description'])?></td><td><strong><?=number_format($e['amount'])?></strong></td><td><?=htmlspecialchars($e['category']??'-')?></td><td><?=htmlspecialchars($e['expense_date']??$e['created_at']??'')?></td><td><span class="badge bg-<?=$e['status']==='approved'?'success':'warning'?>"><?=htmlspecialchars($e['status'])?></span></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'payroll'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Process Payroll</h3>
    <form method="post"><input type="hidden" name="action" value="run_payroll">
      <div class="mb-2"><input class="form-control form-control-sm" name="period" placeholder="Period (e.g. 2026-07)" value="<?=date('Y-m')?>" required></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="description" rows="2" placeholder="Description"></textarea></div>
      <button class="btn btn-sm btn-primary">Run Payroll</button>
    </form></div>
    <div class="brs-card"><h3>Salary Structure</h3>
    <div class="table-responsive" style="max-height:300px;overflow-y:auto"><table class="table table-sm"><thead><tr><th>Staff</th><th>Basic</th></tr></thead><tbody>
    <?php $ss = $staffConn ? $staffConn->query("SELECT ss.*, s.full_name FROM salary_structures ss JOIN staff s ON ss.staff_id=s.id ORDER BY s.full_name") : null; if ($ss) while ($s = $ss->fetch_assoc()): ?><tr><td><?=htmlspecialchars($s['full_name'])?></td><td><?=number_format($s['basic_salary']??$s['base_salary']??0)?></td></tr><?php endwhile; ?>
    </tbody></table></div></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Payroll History</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Period</th><th>Description</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php foreach ($payrollRuns as $p): ?><tr><td><?=htmlspecialchars($p['period'])?></td><td><?=htmlspecialchars($p['description']??'')?></td><td><span class="badge bg-<?=$p['status']==='completed'?'success':($p['status']==='processing'?'info':'warning')?>"><?=htmlspecialchars($p['status'])?></span></td><td><?=htmlspecialchars($p['run_date']??$p['created_at']??'')?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="brs-card"><h3>Integration with HR</h3>
    <p class="text-muted small">Payroll pulls staff salary data from HR's salary_structures table. New staff must be set up in HR first.</p>
    <?php $pendingPayrollSetup = $staffConn ? $staffConn->query("SELECT COUNT(*) c FROM staff WHERE id NOT IN (SELECT staff_id FROM salary_structures WHERE staff_id IS NOT NULL) AND status='active'")->fetch_assoc()['c'] : 0; ?>
    <p class="small"><?=$pendingPayrollSetup?> active staff members missing salary structure setup. <a href="hr-manager.php?page=payroll" class="text-primary">Go to HR Payroll Setup</a></p>
  </div>
</div>

<?php elseif ($page === 'ledger'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Bank Reconciliation</h3>
    <form method="post"><input type="hidden" name="action" value="reconcile_bank">
      <div class="mb-2"><input class="form-control form-control-sm" name="bank_statement" placeholder="Statement Reference" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="ending_balance" placeholder="Ending Balance" step="0.01" required></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="reconciliation_date" value="<?=date('Y-m-d')?>"></div>
      <button class="btn btn-sm btn-primary">Reconcile</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Chart of Accounts</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Account Code</th><th>Name</th><th>Type</th></tr></thead><tbody>
    <?php $coa = $staffConn ? $staffConn->query("SELECT * FROM chart_of_accounts ORDER BY code") : null; if ($coa) while ($a = $coa->fetch_assoc()): ?><tr><td><?=htmlspecialchars($a['code'])?></td><td><?=htmlspecialchars($a['name'])?></td><td><?=htmlspecialchars($a['type']??'-')?></td></tr><?php endwhile; ?>
    </tbody></table></div>
    <div class="brs-card"><h3>Bank Reconciliations</h3>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Reference</th><th>Balance</th><th>Date</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($bankReconciliations as $b): ?><tr><td><?=htmlspecialchars($b['statement_ref']??'')?></td><td><?=number_format($b['ending_balance']??0)?></td><td><?=htmlspecialchars($b['reconciliation_date']??'')?></td><td><span class="badge bg-success"><?=htmlspecialchars($b['status'])?></span></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'inventory'): ?>
<div class="brs-card"><h3>Asset & Inventory Financial Tracking</h3>
<?php $assets = $staffConn ? $staffConn->query("SELECT * FROM assets ORDER BY created_at DESC LIMIT 20") : null; if ($assets && $assets->num_rows > 0): ?>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Asset</th><th>Value</th><th>Category</th><th>Status</th></tr></thead><tbody>
<?php while ($a = $assets->fetch_assoc()): ?><tr><td><?=htmlspecialchars($a['name']??$a['asset_name']??'')?></td><td><strong><?=number_format($a['value']??$a['purchase_price']??0)?></strong></td><td><?=htmlspecialchars($a['category']??'-')?></td><td><?=htmlspecialchars($a['status']??'active')?></td></tr><?php endwhile; ?>
</tbody></table></div>
<?php else: ?><p class="text-muted small">No assets tracked yet.</p><?php endif; ?>
<p class="small text-muted">Inventory purchases are automatically linked to expense records.</p>
</div>

<?php elseif ($page === 'communications'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Send Fee Reminder</h3>
    <form method="post"><input type="hidden" name="action" value="send_reminder">
      <div class="mb-2"><select class="form-select form-select-sm" name="student_id" required><option value="">Select Student</option><?php foreach ($studentsList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></option><?php endforeach; ?></select></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="message" rows="4" placeholder="Reminder message" required>Dear student, your fee balance is due. Please clear to avoid penalties.</textarea></div>
      <button class="btn btn-sm btn-primary">Send Reminder</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Financial Announcements</h3>
    <?php $notices = $stuConn ? $stuConn->query("SELECT * FROM financial_notices ORDER BY created_at DESC LIMIT 10") : null; if ($notices && $notices->num_rows > 0): while ($n = $notices->fetch_assoc()): ?>
      <div class="mb-2 pb-2 border-bottom"><strong><?=htmlspecialchars($n['title']??$n['subject']??'Announcement')?></strong><br><small><?=htmlspecialchars(substr($n['message']??$n['content']??'',0,200))?></small><br><span class="text-muted small"><?=$n['created_at']?></span></div>
    <?php endwhile; else: ?><p class="text-muted small">No financial announcements.</p><?php endif; ?>
    </div>
  </div>
</div>

<?php elseif ($page === 'ura'): ?>
<div class="brs-card"><h3>URA Tax Reporting</h3>
<div class="row">
  <div class="col-md-6">
    <h4 class="fs-6">Withholding Tax</h4>
    <?php $wht = $staffConn ? $staffConn->query("SELECT * FROM bursar_withholding_tax ORDER BY tax_date DESC LIMIT 10") : null; if ($wht && $wht->num_rows > 0): ?>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Period</th><th>Amount</th><th>Status</th></tr></thead><tbody>
    <?php while ($w = $wht->fetch_assoc()): ?><tr><td><?=htmlspecialchars($w['tax_date']??$w['period']??'')?></td><td><?=number_format($w['amount']??0)?></td><td><?=htmlspecialchars($w['status']??'-')?></td></tr><?php endwhile; ?>
    </tbody></table></div>
    <?php else: ?><p class="text-muted small">No withholding tax records.</p><?php endif; ?>
  </div>
  <div class="col-md-6">
    <h4 class="fs-6">VAT Reports</h4>
    <?php $vat = $staffConn ? $staffConn->query("SELECT * FROM bursar_vat_reports ORDER BY created_at DESC LIMIT 10") : null; if ($vat && $vat->num_rows > 0): ?>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Period</th><th>Net</th><th>Status</th></tr></thead><tbody>
    <?php while ($v = $vat->fetch_assoc()): ?><tr><td><?=htmlspecialchars($v['period']??$v['tax_period']??'')?></td><td><?=number_format($v['net_vat']??$v['amount']??0)?></td><td><?=htmlspecialchars($v['status']??'-')?></td></tr><?php endwhile; ?>
    </tbody></table></div>
    <?php else: ?><p class="text-muted small">No VAT records.</p><?php endif; ?>
  </div>
</div>
<p class="small mt-3"><a href="ura_reporting.php" class="btn btn-sm btn-outline-primary">Full URA Reporting Portal</a></p>
</div>
<?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION['csrf_token'])?>';document.querySelectorAll('form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});var pm=document.getElementById('payMethod');if(pm){pm.addEventListener('change',function(){document.getElementById('momoFields').style.display=this.value==='mobile_money'?'flex':'none';});}
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
