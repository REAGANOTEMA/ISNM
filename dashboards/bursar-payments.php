<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/financial_functions.php';

$ctx = bootstrapStaffDashboard(['bursar', 'accountant', 'finance']);
$staff = $ctx['staff'];
$students = $ctx['students'];
$website = $ctx['website'];
$user = $ctx['user'];

$view = $_GET['view'] ?? 'overview';
$ajax = $_GET['ajax'] ?? '';
$sid = $_GET['sid'] ?? '';
$pid = $_GET['pid'] ?? '';

function currency($n) { return 'UGX ' . number_format((float)$n, 0); }
function badge($s) {
    $m = ['verified'=>'success','pending'=>'warning','failed'=>'danger','rejected'=>'danger','approved'=>'success','cancelled'=>'dark'];
    $c = $m[strtolower($s)] ?? 'secondary';
    return '<span class="badge bg-'.$c.'">'.htmlspecialchars($s).'</span>';
}

// ── AJAX handlers ──
if ($ajax === 'search_student' && $sid) {
    header('Content-Type: application/json');
    $data = [];
    try {
        $like = '%' . $sid . '%';
        $q = "SELECT student_id, first_name, surname, program, level FROM students WHERE student_id LIKE ? OR first_name LIKE ? OR surname LIKE ? ORDER BY surname LIMIT 20";
        $stmt = $students->prepare($q);
        if ($stmt) { $stmt->bind_param('sss', $like, $like, $like); $stmt->execute(); $r = $stmt->get_result(); while ($row = $r->fetch_assoc()) $data[] = $row; $stmt->close(); }
    } catch (Exception $e) { error_log('search: '.$e->getMessage()); }
    echo json_encode($data); exit;
}

if ($ajax === 'get_balance' && $sid) {
    header('Content-Type: application/json');
    $bal = 0; $acc = 0;
    try {
        $stmt = $staff->prepare("SELECT id, balance, total_fees, amount_paid, invoice_number FROM student_fee_accounts WHERE student_id = ? AND status NOT IN ('fully_paid','cancelled') ORDER BY id DESC LIMIT 1");
        if ($stmt) { $stmt->bind_param('s', $sid); $stmt->execute(); $r = $stmt->get_result(); if ($row = $r->fetch_assoc()) { $bal = (float)$row['balance']; $acc = (int)$row['id']; } $stmt->close(); }
    } catch (Exception $e) { error_log('balance: '.$e->getMessage()); }
    echo json_encode(['balance' => $bal, 'fee_account_id' => $acc]); exit;
}

if ($ajax === 'load_verification') {
    header('Content-Type: application/json');
    $data = [];
    try {
        if ($staff) {
            $r = $staff->query("SELECT bv.*, s.first_name, s.surname FROM bursar_payment_verification bv LEFT JOIN igangaschoolofl_students_db.students s ON bv.student_id = s.student_id WHERE bv.status = 'pending' ORDER BY bv.created_at DESC LIMIT 50");
            if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
        }
    } catch (Exception $e) { error_log('verify load: '.$e->getMessage()); }
    echo json_encode($data); exit;
}

if ($ajax === 'verify_payment' && $pid) {
    header('Content-Type: application/json');
    $ok = false;
    try {
        if ($staff) {
            $v = $staff->query("SELECT * FROM bursar_payment_verification WHERE id = ".(int)$pid." AND status='pending'");
            if ($v && ($row = $v->fetch_assoc())) {
                $staff->query("UPDATE bursar_payment_verification SET status='verified', verified_by=".(int)($user['id']??0).", verified_at=NOW() WHERE id=".(int)$pid);
                $receipt = function_exists('generateReceiptNumber') ? generateReceiptNumber() : ('RCPT-'.date('Ymd').'-'.rand(1000,9999));
                $stmt = $staff->prepare("INSERT INTO fee_payments (payment_id, student_id, fee_account_id, amount_paid, payment_method, payment_reference, receipt_number, notes, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified')");
                if ($stmt) {
                    $ref = $row['payment_reference'] ?? $receipt;
                    $stmt->bind_param('ssidsssss', $receipt, $row['student_id'], $row['fee_account_id'], $row['amount'], $row['payment_method'], $ref, $receipt, $row['notes'], $row['payment_date']);
                    $stmt->execute(); $stmt->close();
                    if ((int)$row['fee_account_id'] > 0) {
                        $upd = $staff->prepare("UPDATE student_fee_accounts SET amount_paid = amount_paid + ?, balance = balance - ?, last_payment_date = ?, status = CASE WHEN (balance - ?) <= 0 THEN 'fully_paid' WHEN amount_paid + ? > 0 THEN 'partially_paid' ELSE 'unpaid' END WHERE id = ?");
                        if ($upd) { $am = (float)$row['amount']; $pd = $row['payment_date']; $upd->bind_param('ddsddi', $am, $am, $pd, $am, $am, $row['fee_account_id']); $upd->execute(); $upd->close(); }
                    }
                    $ok = true;
                }
            }
        }
    } catch (Exception $e) { error_log('verify: '.$e->getMessage()); }
    echo json_encode(['success' => $ok]); exit;
}

if ($ajax === 'reject_payment' && $pid) {
    header('Content-Type: application/json');
    try { if ($staff) $staff->query("UPDATE bursar_payment_verification SET status='rejected' WHERE id=".(int)$pid); echo json_encode(['success'=>true]); } catch (Exception $e) { echo json_encode(['success'=>false]); }
    exit;
}

if ($ajax === 'chart_data') {
    header('Content-Type: application/json');
    $data = ['labels'=>[], 'values'=>[], 'colors'=>[]];
    $colors = ['#1a237e','#059669','#d97706','#dc2626','#0891b2','#7c3aed'];
    try {
        if ($staff) {
            $r = $staff->query("SELECT payment_method, COALESCE(SUM(amount_paid),0) AS total FROM fee_payments WHERE status='verified' AND payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY payment_method ORDER BY total DESC");
            if ($r) { $i=0; while ($row = $r->fetch_assoc()) { $data['labels'][] = ucfirst(str_replace('_',' ',$row['payment_method'])); $data['values'][] = (float)$row['total']; $data['colors'][] = $colors[$i % count($colors)]; $i++; } }
        }
    } catch (Exception $e) { error_log('chart: '.$e->getMessage()); }
    echo json_encode($data); exit;
}

// ── POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = 'bursar-payments.php?view=' . urlencode($view);

    if ($action === 'record_payment' && $staff) {
        try {
            $student_id = trim($_POST['student_id'] ?? '');
            $fee_account_id = (int)($_POST['fee_account_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $method = trim($_POST['payment_method'] ?? 'cash');
            $reference = trim($_POST['reference'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $payment_date = trim($_POST['payment_date'] ?? date('Y-m-d'));
            $needs_verification = isset($_POST['needs_verification']) ? 1 : 0;
            if ($student_id === '' || $amount <= 0) { $_SESSION['error'] = 'Student ID and valid amount required.'; }
            else {
                if ($needs_verification && in_array($method, ['bank', 'cheque', 'mobile_money'])) {
                    $stmt = $staff->prepare("INSERT INTO bursar_payment_verification (student_id, fee_account_id, amount, payment_method, payment_reference, notes, payment_date, proof_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    if ($stmt) {
                        $proof = '';
                        if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
                            $ext = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
                            $proof = 'uploads/payments/proof_'.time().'_'.$student_id.'.'.$ext;
                            $dir = dirname(__DIR__ . '/'.$proof);
                            if (!is_dir($dir)) mkdir($dir, 0755, true);
                            move_uploaded_file($_FILES['proof_file']['tmp_name'], __DIR__ . '/../'.$proof);
                        }
                        $stmt->bind_param('sidsssss', $student_id, $fee_account_id, $amount, $method, $reference, $notes, $payment_date, $proof);
                        $stmt->execute() ? $_SESSION['success'] = 'Payment submitted for verification.' : $_SESSION['error'] = $stmt->error;
                        $stmt->close();
                    }
                } else {
                    $receipt = function_exists('generateReceiptNumber') ? generateReceiptNumber() : ('RCPT-'.date('Ymd').'-'.rand(1000,9999));
                    $stmt = $staff->prepare("INSERT INTO fee_payments (payment_id, student_id, fee_account_id, amount_paid, payment_method, payment_reference, receipt_number, notes, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified')");
                    if ($stmt) {
                        $stmt->bind_param('ssidsssss', $receipt, $student_id, $fee_account_id, $amount, $method, $reference, $receipt, $notes, $payment_date);
                        if ($stmt->execute()) {
                            if ($fee_account_id > 0) {
                                $upd = $staff->prepare("UPDATE student_fee_accounts SET amount_paid = amount_paid + ?, balance = balance - ?, last_payment_date = ?, status = CASE WHEN (balance - ?) <= 0 THEN 'fully_paid' WHEN amount_paid + ? > 0 THEN 'partially_paid' ELSE 'unpaid' END WHERE id = ?");
                                if ($upd) { $upd->bind_param('ddsddi', $amount, $amount, $payment_date, $amount, $amount, $fee_account_id); $upd->execute(); $upd->close(); }
                            }
                            $_SESSION['success'] = "Payment recorded. Receipt: $receipt";
                        } else { $_SESSION['error'] = 'Payment failed: '.$stmt->error; }
                        $stmt->close();
                    }
                }
            }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }
}

// ── Stats ──
$today_collected = 0; $week_collected = 0; $month_collected = 0;
$pending_verification = 0; $total_payments = 0;
try {
    if ($staff) {
        $r = $staff->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE DATE(payment_date)=CURDATE() AND status='verified'"); if ($r) $today_collected = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE YEARWEEK(payment_date)=YEARWEEK(CURDATE()) AND status='verified'"); if ($r) $week_collected = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status='verified'"); if ($r) $month_collected = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COUNT(*) AS c FROM bursar_payment_verification WHERE status='pending'"); if ($r) $pending_verification = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COUNT(*) AS c FROM fee_payments WHERE status='verified'"); if ($r) $total_payments = (int)$r->fetch_assoc()['c'];
    }
} catch (Exception $e) { error_log('pay stats: '.$e->getMessage()); }

// ── Recent payments ──
$recent_payments = [];
try { if ($staff) { $r = $staff->query("SELECT fp.*, s.first_name, s.surname FROM fee_payments fp LEFT JOIN igangaschoolofl_students_db.students s ON fp.student_id = s.student_id ORDER BY fp.created_at DESC LIMIT 50"); if ($r) while ($row = $r->fetch_assoc()) $recent_payments[] = $row; } } catch (Exception $e) { error_log('bursar-payments context: ' . $e->getMessage()); }

$pageTitle = 'Bursar - Payment Processing';
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.tab-nav { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:24px; }
.tab-nav .tn { padding:10px 18px; border-radius:10px; cursor:pointer; font-weight:500; font-size:14px; background:#f1f5f9; color:#475569; transition:all .2s; border:none; }
.tab-nav .tn:hover { background:#e2e8f0; }
.tab-nav .tn.active { background:#1a237e; color:#fff; }
.tab-content { display:none; }
.tab-content.active { display:block; }
.sri { padding:8px 12px; border-radius:8px; cursor:pointer; border:1px solid #e2e8f0; margin-bottom:4px; transition:all .15s; }
.sri:hover, .sri.active { background:#eef2ff; border-color:#1a237e; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="ma content-section" style="margin-left:270px;padding:24px">

    <div class="ph">
        <div>
            <h1><i class="fas fa-hand-holding-usd me-2"></i>Payment Processing</h1>
            <p>Record, verify, and manage student payments</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:13px"><i class="far fa-clock me-1"></i><span id="currentDate"></span></span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div><div class="stat-content"><h3><?= currency($today_collected) ?></h3><p>Today's Collections</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card info"><div class="stat-icon"><i class="fas fa-calendar-week"></i></div><div class="stat-content"><h3><?= currency($week_collected) ?></h3><p>This Week</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card success"><div class="stat-icon"><i class="fas fa-calendar-alt"></i></div><div class="stat-content"><h3><?= currency($month_collected) ?></h3><p>This Month</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card <?= $pending_verification > 0 ? 'warning' : 'success' ?>"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $pending_verification ?></h3><p>Pending Verification</p></div></div>
        </div>
    </div>

    <div class="tab-nav">
        <button class="tn active" data-tab="tab_record"><i class="fas fa-plus-circle me-1"></i>Record Payment</button>
        <button class="tn" data-tab="tab_recent"><i class="fas fa-list me-1"></i>Recent Payments</button>
        <button class="tn" data-tab="tab_verify"><i class="fas fa-check-double me-1"></i>Verification Queue <?= $pending_verification > 0 ? '<span class="badge bg-danger ms-1">'.$pending_verification.'</span>' : '' ?></button>
        <button class="tn" data-tab="tab_chart"><i class="fas fa-chart-pie me-1"></i>Payment Methods</button>
    </div>

    <!-- ══════════ Record Payment ══════════ -->
    <div id="tab_record" class="tab-content active">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-search me-2"></i>Search Student</h5>
                <div class="mt-2">
                    <div class="input-group"><input type="text" id="payStudentSearch" class="form-control fc" placeholder="Name or Student ID..." autocomplete="off"><button class="btn bb" type="button" onclick="searchPayStudent()"><i class="fas fa-search"></i></button></div>
                    <div id="payStudentResults" class="mt-2"></div>
                    <div id="payStudentInfo" class="mt-3 p-3 bg-light rounded d-none">
                        <h6 class="fw-bold" id="payStudentName"></h6>
                        <p class="mb-1 text-muted small" id="payStudentIdDisplay"></p>
                        <p class="mb-1 small"><strong>Balance:</strong> <span id="payStudentBalance" class="text-danger fw-bold"></span></p>
                        <input type="hidden" id="paySelectedStudentId">
                        <input type="hidden" id="paySelectedFeeAccountId">
                    </div>
                </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-money-bill me-2"></i>Payment Details</h5>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="record_payment">
                    <input type="hidden" name="student_id" id="formStudentId">
                    <input type="hidden" name="fee_account_id" id="formFeeAccountId" value="0">
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="fl">Amount (UGX) *</label>
                            <input type="number" name="amount" class="form-control fc" required min="1" step="100">
                        </div>
                        <div class="col-md-6">
                            <label class="fl">Payment Method *</label>
                            <select name="payment_method" id="payMethod" class="form-select fs" onchange="toggleProofUpload()">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Deposit</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fl">Reference / Transaction ID</label>
                            <input type="text" name="reference" class="form-control fc" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="fl">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control fc" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12" id="proofUploadWrap" style="display:none">
                            <label class="fl">Upload Proof of Payment (bank slip, screenshot)</label>
                            <input type="file" name="proof_file" class="form-control fc" accept="image/*,.pdf">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="needs_verification" id="needVerify" class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="needVerify">Submit for verification (non-cash payments)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="fl">Notes</label>
                            <textarea name="notes" class="form-control fc" rows="2"></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Record Payment</button>
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Recent Payments ══════════ -->
    <div id="tab_recent" class="tab-content">
        <div class="content-section"><h5><i class="fas fa-list me-2"></i>Recent Payments</h5>
        <div class="table-responsive">
            <table class="table tb">
                <thead><tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
<?php if (count($recent_payments) > 0): foreach ($recent_payments as $p): ?>
<tr>
    <td><small><?= htmlspecialchars($p['receipt_number'] ?? '-') ?></small></td>
    <td><?= htmlspecialchars(($p['surname'] ?? '').' '.($p['first_name'] ?? '')) ?><br><small class="text-muted"><?= htmlspecialchars($p['student_id']) ?></small></td>
    <td><strong><?= currency($p['amount_paid']) ?></strong></td>
    <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$p['payment_method'] ?? ''))) ?></td>
    <td><small><?= htmlspecialchars($p['payment_reference'] ?? '-') ?></small></td>
    <td><small><?= date('d/m/Y H:i', strtotime($p['payment_date'] ?? $p['created_at'] ?? 'now')) ?></small></td>
    <td><?= badge($p['status'] ?? 'verified') ?></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="7" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>

    <!-- ══════════ Verification Queue ══════════ -->
    <div id="tab_verify" class="tab-content">
        <div class="content-section"><h5><i class="fas fa-check-double me-2"></i>Payment Verification Queue</h5>
        <div id="verifyQueueOutput">
            <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>
        </div>
    </div>

    <!-- ══════════ Payment Methods Chart ══════════ -->
    <div id="tab_chart" class="tab-content">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="content-section"><h5><i class="fas fa-chart-pie me-2"></i>Payment Method Distribution (12 months)</h5>
                <canvas id="payMethodChart" height="300"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="content-section"><h5><i class="fas fa-info-circle me-2"></i>Method Summary</h5>
                <div id="methodSummary"><div class="text-center py-4 text-muted">Loading...</div></div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /ma -->

<?php
if (isset($_SESSION['success'])) { echo '<div class="alert alert-success alert-dismissible fade show attoast"><i class="fas fa-check-circle me-1"></i> '.htmlspecialchars($_SESSION['success']).' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['success']); }
if (isset($_SESSION['error'])) { echo '<div class="alert alert-danger alert-dismissible fade show attoast"><i class="fas fa-exclamation-triangle me-1"></i> '.htmlspecialchars($_SESSION['error']).' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['error']); }
?>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<script>
var payChartInstance = null;

// ── Tab switching ──
document.querySelectorAll('.tn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('.tn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function(t){ t.classList.remove('active'); });
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
        if (this.dataset.tab === 'tab_chart') setTimeout(initPayChart, 300);
        if (this.dataset.tab === 'tab_verify') loadVerificationQueue();
    });
});

// ── Toggle proof upload ──
function toggleProofUpload(){
    var m = document.getElementById('payMethod').value;
    document.getElementById('proofUploadWrap').style.display = (m === 'bank' || m === 'cheque' || m === 'mobile_money') ? 'block' : 'none';
}

// ── Student search ──
function searchPayStudent(){
    var q = document.getElementById('payStudentSearch').value.trim();
    if(!q) return;
    fetch('bursar-payments.php?ajax=search_student&sid='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(data){
        var el = document.getElementById('payStudentResults'), info = document.getElementById('payStudentInfo');
        if(info) info.classList.add('d-none'); el.innerHTML = '';
        if(!data||!data.length){ el.innerHTML = '<div class="text-muted small p-2">No students found.</div>'; return; }
        data.forEach(function(s){
            var d = document.createElement('div');
            d.className = 'sri';
            d.innerHTML = '<strong>'+esc(s.surname)+', '+esc(s.first_name)+'</strong><br><small class="text-muted">'+esc(s.student_id)+' | '+esc(s.program||'')+'</small>';
            d.addEventListener('click',function(){ selectPayStudent(s); });
            el.appendChild(d);
        });
    }).catch(function(e){ console.warn('[ISNM]', e); });
}
function selectPayStudent(s){
    document.getElementById('paySelectedStudentId').value = s.student_id;
    document.getElementById('payStudentName').textContent = s.surname+', '+s.first_name;
    document.getElementById('payStudentIdDisplay').textContent = s.student_id+' | '+ (s.program||'');
    document.getElementById('formStudentId').value = s.student_id;
    var info = document.getElementById('payStudentInfo');
    info.classList.remove('d-none');
    document.querySelectorAll('#payStudentResults .sri').forEach(function(i){ i.classList.remove('active'); });
    fetch('bursar-payments.php?ajax=get_balance&sid='+encodeURIComponent(s.student_id))
    .then(function(r){ return r.json(); })
    .then(function(d){
        document.getElementById('payStudentBalance').textContent = 'UGX '+Number(d.balance||0).toLocaleString();
        document.getElementById('paySelectedFeeAccountId').value = d.fee_account_id||0;
        document.getElementById('formFeeAccountId').value = d.fee_account_id||0;
    }).catch(function(e){ console.warn('[ISNM]', e); });
}

// ── Verification queue ──
function loadVerificationQueue(){
    var out = document.getElementById('verifyQueueOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-payments.php?ajax=load_verification')
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(!data||!data.length){ out.innerHTML = '<div class="text-center text-muted py-4">No pending verifications.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th><th>Proof</th><th>Action</th></tr></thead><tbody>';
        data.forEach(function(v){
            h += '<tr><td>'+esc(v.surname||'')+' '+esc(v.first_name||'')+'<br><small class="text-muted">'+esc(v.student_id)+'</small></td>';
            h += '<td><strong>'+Number(v.amount).toLocaleString()+'</strong></td>';
            h += '<td>'+esc(v.payment_method)+'</td>';
            h += '<td><small>'+esc(v.payment_reference||'-')+'</small></td>';
            h += '<td><small>'+esc(v.payment_date)+'</small></td>';
            h += '<td>'+(v.proof_file ? '<a href="../'+esc(v.proof_file)+'" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file"></i> View</a>' : '-')+'</td>';
            h += '<td><button class="btn btn-sm btn-success" onclick="verifyPayment('+v.id+')"><i class="fas fa-check"></i> Verify</button> <button class="btn btn-sm btn-danger" onclick="rejectPayment('+v.id+')"><i class="fas fa-times"></i></button></td></tr>';
        });
        h += '</tbody></table></div>';
        out.innerHTML = h;
    }).catch(function(e){ console.warn('[ISNM]', e); });
}
function verifyPayment(id){
    if(!confirm('Verify this payment? Receipt will be auto-generated.')) return;
    fetch('bursar-payments.php?ajax=verify_payment&pid='+id)
    .then(function(r){ return r.json(); })
    .then(function(d){ if(d.success){ loadVerificationQueue(); } else { alert('Verification failed.'); } }).catch(function(e){ console.warn('[ISNM]', e); });
}
function rejectPayment(id){
    if(!confirm('Reject this payment?')) return;
    fetch('bursar-payments.php?ajax=reject_payment&pid='+id)
    .then(function(){ loadVerificationQueue(); }).catch(function(e){ console.warn('[ISNM]', e); });
}

// ── Payment method chart ──
function initPayChart(){
    var ctx = document.getElementById('payMethodChart');
    if(!ctx) return;
    fetch('bursar-payments.php?ajax=chart_data')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(payChartInstance) payChartInstance.destroy();
        payChartInstance = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: d.labels||[],
                datasets: [{
                    data: d.values||[],
                    backgroundColor: d.colors||['#1a237e','#059669','#d97706','#dc2626','#0891b2'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, font: { size: 12 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx){
                                var total = ctx.dataset.data.reduce(function(a,b){ return a+b; },0);
                                var pct = ((ctx.parsed/total)*100).toFixed(1);
                                return 'UGX '+Number(ctx.parsed).toLocaleString()+' ('+pct+'%)';
                            }
                        }
                    }
                }
            }
        });
        var sm = document.getElementById('methodSummary');
        if(d.labels&&d.labels.length){
            var total = d.values.reduce(function(a,b){ return a+b; },0);
            var hh = '';
            d.labels.forEach(function(l,i){
                var pct = total > 0 ? ((d.values[i]/total)*100).toFixed(1) : 0;
                hh += '<div class="d-flex justify-content-between align-items-center p-2 border-bottom"><span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:'+d.colors[i]+';margin-right:8px"></span>'+esc(l)+'</span><span><strong>UGX '+Number(d.values[i]).toLocaleString()+'</strong> <span class="text-muted">('+pct+'%)</span></span></div>';
            });
            sm.innerHTML = hh;
        } else { sm.innerHTML = '<div class="text-center text-muted py-3">No data available.</div>'; }
    }).catch(function(e){ console.warn('[ISNM]', e); });
}

function esc(s){ if(!s) return ''; var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>
</body>
</html>
