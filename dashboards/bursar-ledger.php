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

function currency($n) { return 'UGX ' . number_format((float)$n, 0); }
function badge($s) {
    $m = ['active'=>'success','inactive'=>'secondary','approved'=>'success','pending'=>'warning','reconciled'=>'success','unreconciled'=>'warning'];
    $c = $m[strtolower($s)] ?? 'secondary';
    return '<span class="badge bg-'.$c.'">'.htmlspecialchars($s).'</span>';
}

// ── AJAX endpoints ──
if ($ajax) {
    header('Content-Type: application/json');
    $result = [];

    try {
        if (!$staff) throw new Exception('no db');

        if ($ajax === 'trial_balance') {
            $as_of = $_GET['as_of'] ?? date('Y-m-d');
            $debits = 0; $credits = 0;
            $stmt = $staff->prepare("SELECT account_code, account_name, account_type, COALESCE(SUM(debit_amount),0) AS deb, COALESCE(SUM(credit_amount),0) AS cred FROM bursar_general_ledger WHERE entry_date <= ? GROUP BY account_code, account_name, account_type ORDER BY account_type, account_code");
            if ($stmt) {
                $stmt->bind_param('s', $as_of);
                $stmt->execute();
                $r = $stmt->get_result();
                if ($r) { while ($row = $r->fetch_assoc()) { $d = (float)$row['deb']; $c = (float)$row['cred']; $bal = $d - $c; $result['rows'][] = [$row['account_code'], htmlspecialchars($row['account_name']), htmlspecialchars($row['account_type']), $bal >= 0 ? currency($bal) : '-', $bal < 0 ? currency(abs($bal)) : '-']; if ($bal >= 0) $debits += $bal; else $credits += abs($bal); } }
                $stmt->close();
            }
            $result['total_debit'] = $debits; $result['total_credit'] = $credits;
        } elseif ($ajax === 'income_statement') {
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            $income = 0; $expenses = 0;
            $stmt = $staff->prepare("SELECT account_name, account_type, COALESCE(SUM(credit_amount - debit_amount),0) AS bal FROM bursar_general_ledger WHERE entry_date BETWEEN ? AND ? AND account_type IN ('income','revenue') GROUP BY account_name, account_type");
            if ($stmt) {
                $stmt->bind_param('ss', $from, $to);
                $stmt->execute();
                $r = $stmt->get_result();
                if ($r) { $result['income_items'] = []; while ($row = $r->fetch_assoc()) { $b = (float)$row['bal']; $result['income_items'][] = [htmlspecialchars($row['account_name']), currency($b)]; $income += $b; } }
                $stmt->close();
            }
            $stmt = $staff->prepare("SELECT account_name, account_type, COALESCE(SUM(debit_amount - credit_amount),0) AS bal FROM bursar_general_ledger WHERE entry_date BETWEEN ? AND ? AND account_type IN ('expense','cost_of_sales') GROUP BY account_name, account_type");
            if ($stmt) {
                $stmt->bind_param('ss', $from, $to);
                $stmt->execute();
                $r = $stmt->get_result();
                if ($r) { $result['expense_items'] = []; while ($row = $r->fetch_assoc()) { $b = (float)$row['bal']; $result['expense_items'][] = [htmlspecialchars($row['account_name']), currency($b)]; $expenses += $b; } }
                $stmt->close();
            }
            $result['total_income'] = $income; $result['total_expenses'] = $expenses; $result['net_income'] = $income - $expenses;
        } elseif ($ajax === 'ledger_entries') {
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            $stmt = $staff->prepare("SELECT gl.*, coa.account_name, coa.account_type FROM bursar_general_ledger gl LEFT JOIN bursar_chart_of_accounts coa ON gl.account_code = coa.account_code WHERE gl.entry_date BETWEEN ? AND ? ORDER BY gl.entry_date DESC, gl.id DESC LIMIT 200");
            if ($stmt) {
                $stmt->bind_param('ss', $from, $to);
                $stmt->execute();
                $r = $stmt->get_result();
                if ($r) { $result['entries'] = []; while ($row = $r->fetch_assoc()) $result['entries'][] = $row; }
                $stmt->close();
            }
        } elseif ($ajax === 'cashbook') {
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            $stmt = $staff->prepare("SELECT * FROM bursar_cashbook WHERE transaction_date BETWEEN ? AND ? ORDER BY transaction_date DESC, id DESC LIMIT 200");
            if ($stmt) {
                $stmt->bind_param('ss', $from, $to);
                $stmt->execute();
                $r = $stmt->get_result();
                if ($r) { $result['entries'] = []; while ($row = $r->fetch_assoc()) $result['entries'][] = $row; }
                $stmt->close();
            }
        } elseif ($ajax === 'reconciliation') {
            $r = $staff->query("SELECT * FROM bursar_bank_reconciliation ORDER BY reconciliation_date DESC LIMIT 50");
            if ($r) { $result['entries'] = []; while ($row = $r->fetch_assoc()) $result['entries'][] = $row; }
        } elseif ($ajax === 'accounts_list') {
            $r = $staff->query("SELECT * FROM bursar_chart_of_accounts ORDER BY account_type, account_code");
            if ($r) { $result['accounts'] = []; while ($row = $r->fetch_assoc()) $result['accounts'][] = $row; }
        }
    } catch (Exception $e) { error_log('ledger ajax: '.$e->getMessage()); }
    echo json_encode($result);
    exit;
}

// ── POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = 'bursar-ledger.php';

    if ($action === 'add_account' && $staff) {
        try {
            $code = trim($_POST['account_code'] ?? '');
            $name = trim($_POST['account_name'] ?? '');
            $type = trim($_POST['account_type'] ?? 'income');
            $desc = trim($_POST['account_description'] ?? '');
            $stmt = $staff->prepare("INSERT INTO bursar_chart_of_accounts (account_code, account_name, account_type, description, status) VALUES (?, ?, ?, ?, 'active')");
            if ($stmt) { $stmt->bind_param('ssss', $code, $name, $type, $desc); $stmt->execute() ? $_SESSION['success'] = 'Account created.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'add_ledger_entry' && $staff) {
        try {
            $date = trim($_POST['entry_date'] ?? date('Y-m-d'));
            $code = trim($_POST['account_code'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $ref = trim($_POST['reference'] ?? '');
            $debit = (float)($_POST['debit_amount'] ?? 0);
            $credit = (float)($_POST['credit_amount'] ?? 0);
            $stmt = $staff->prepare("INSERT INTO bursar_general_ledger (entry_date, account_code, description, reference, debit_amount, credit_amount, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) { $uid = (int)($user['id'] ?? 0); $stmt->bind_param('ssssddi', $date, $code, $desc, $ref, $debit, $credit, $uid); $stmt->execute() ? $_SESSION['success'] = 'Journal entry recorded.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'add_cashbook_entry' && $staff) {
        try {
            $date = trim($_POST['txn_date'] ?? date('Y-m-d'));
            $type = trim($_POST['txn_type'] ?? 'receipt');
            $desc = trim($_POST['txn_description'] ?? '');
            $amount = (float)($_POST['txn_amount'] ?? 0);
            $category = trim($_POST['txn_category'] ?? '');
            $ref = trim($_POST['txn_reference'] ?? '');
            $stmt = $staff->prepare("INSERT INTO bursar_cashbook (transaction_date, transaction_type, description, amount, category, reference, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) { $uid = (int)($user['id'] ?? 0); $stmt->bind_param('sssdssi', $date, $type, $desc, $amount, $category, $ref, $uid); $stmt->execute() ? $_SESSION['success'] = 'Cashbook entry added.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'add_reconciliation' && $staff) {
        try {
            $date = trim($_POST['recon_date'] ?? date('Y-m-d'));
            $bank_bal = (float)($_POST['bank_balance'] ?? 0);
            $book_bal = (float)($_POST['book_balance'] ?? 0);
            $diff = $book_bal - $bank_bal;
            $notes = trim($_POST['recon_notes'] ?? '');
            $stmt = $staff->prepare("INSERT INTO bursar_bank_reconciliation (reconciliation_date, bank_balance, book_balance, difference, notes, status, reconciled_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) { $st = abs($diff) < 1 ? 'reconciled' : 'unreconciled'; $uid = (int)($user['id'] ?? 0); $stmt->bind_param('sdddssi', $date, $bank_bal, $book_bal, $diff, $notes, $st, $uid); $stmt->execute() ? $_SESSION['success'] = 'Reconciliation recorded.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }
}

// ── Stats ──
$total_income = 0; $total_expenses = 0; $cashbook_balance = 0; $accounts_count = 0;
try {
    if ($staff) {
        $r = $staff->query("SELECT COALESCE(SUM(credit_amount - debit_amount),0) AS t FROM bursar_general_ledger WHERE account_type IN ('income','revenue')"); if ($r) $total_income = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COALESCE(SUM(debit_amount - credit_amount),0) AS t FROM bursar_general_ledger WHERE account_type IN ('expense','cost_of_sales')"); if ($r) $total_expenses = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COALESCE(SUM(CASE WHEN transaction_type='receipt' THEN amount ELSE -amount END),0) AS bal FROM bursar_cashbook"); if ($r) $cashbook_balance = (float)$r->fetch_assoc()['bal'];
        $r = $staff->query("SELECT COUNT(*) AS c FROM bursar_chart_of_accounts WHERE status='active'"); if ($r) $accounts_count = (int)$r->fetch_assoc()['c'];
    }
} catch (Exception $e) {}

$pageTitle = 'Bursar - Ledger & Accounts';
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
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="ma" style="margin-left:270px;padding:24px">

    <div class="ph">
        <div>
            <h1><i class="fas fa-book me-2"></i>Accounts & Ledger Management</h1>
            <p>Chart of accounts, general ledger, trial balance, income statement, cashbook &amp; bank reconciliation</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:13px"><i class="far fa-clock me-1"></i><span id="currentDate"></span></span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card success"><div class="stat-icon"><i class="fas fa-arrow-up"></i></div><div class="stat-content"><h3><?= currency($total_income) ?></h3><p>Total Income</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card danger"><div class="stat-icon"><i class="fas fa-arrow-down"></i></div><div class="stat-content"><h3><?= currency($total_expenses) ?></h3><p>Total Expenses</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-wallet"></i></div><div class="stat-content"><h3><?= currency($cashbook_balance) ?></h3><p>Cashbook Balance</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card info"><div class="stat-icon"><i class="fas fa-folder"></i></div><div class="stat-content"><h3><?= number_format($accounts_count) ?></h3><p>Active Accounts</p></div></div>
        </div>
    </div>

    <div class="tab-nav">
        <button class="tn active" data-tab="tab_coa"><i class="fas fa-sitemap me-1"></i>Chart of Accounts</button>
        <button class="tn" data-tab="tab_ledger"><i class="fas fa-journal-whills me-1"></i>General Ledger</button>
        <button class="tn" data-tab="tab_trial"><i class="fas fa-balance-scale me-1"></i>Trial Balance</button>
        <button class="tn" data-tab="tab_income"><i class="fas fa-file-invoice-dollar me-1"></i>Income Statement</button>
        <button class="tn" data-tab="tab_cashbook"><i class="fas fa-cash-register me-1"></i>Cashbook</button>
        <button class="tn" data-tab="tab_recon"><i class="fas fa-university me-1"></i>Bank Reconciliation</button>
    </div>

    <!-- ══════════ Chart of Accounts ══════════ -->
    <div id="tab_coa" class="tab-content active">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Add Account</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add_account">
                    <div class="row g-3 mt-2">
                        <div class="col-md-6"><label class="fl">Account Code *</label><input type="text" name="account_code" class="form-control fc" required placeholder="e.g. 4000"></div>
                        <div class="col-md-6">
                            <label class="fl">Type *</label>
                            <select name="account_type" class="form-select fs">
                                <option value="asset">Asset</option><option value="liability">Liability</option>
                                <option value="income">Income / Revenue</option><option value="expense">Expense</option>
                                <option value="equity">Equity</option><option value="cost_of_sales">Cost of Sales</option>
                            </select>
                        </div>
                        <div class="col-12"><label class="fl">Account Name *</label><input type="text" name="account_name" class="form-control fc" required placeholder="e.g. Tuition Fee Income"></div>
                        <div class="col-12"><label class="fl">Description</label><textarea name="account_description" class="form-control fc" rows="2"></textarea></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Create Account</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Chart of Accounts</h5>
                <div id="coaOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ General Ledger ══════════ -->
    <div id="tab_ledger" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Journal Entry</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add_ledger_entry">
                    <div class="row g-3 mt-2">
                        <div class="col-6"><label class="fl">Date *</label><input type="date" name="entry_date" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-6">
                            <label class="fl">Account *</label>
                            <select name="account_code" id="ledgerAccountCode" class="form-select fs" required>
                                <option value="">-- Select Account --</option>
                            </select>
                        </div>
                        <div class="col-12"><label class="fl">Description *</label><input type="text" name="description" class="form-control fc" required></div>
                        <div class="col-6"><label class="fl">Reference</label><input type="text" name="reference" class="form-control fc" placeholder="e.g. INV-001"></div>
                        <div class="col-6"><label class="fl">Debit Amount</label><input type="number" name="debit_amount" class="form-control fc" value="0" min="0" step="100"></div>
                        <div class="col-6"><label class="fl">Credit Amount</label><input type="number" name="credit_amount" class="form-control fc" value="0" min="0" step="100"></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Post Entry</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Ledger Entries</h5>
                <form onsubmit="event.preventDefault(); loadLedgerEntries()" class="row g-2 mb-3">
                    <div class="col-md-4"><input type="date" id="ledgerFrom" class="form-control fc" value="<?= date('Y-m-01') ?>"></div>
                    <div class="col-md-4"><input type="date" id="ledgerTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-4"><button class="btn bb w-100"><i class="fas fa-search"></i> Load</button></div>
                </form>
                <div id="ledgerOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Trial Balance ══════════ -->
    <div id="tab_trial" class="tab-content">
        <div class="content-section"><h5><i class="fas fa-balance-scale me-2"></i>Trial Balance</h5>
        <form onsubmit="event.preventDefault(); loadTrialBalance()" class="row g-2 mb-3">
            <div class="col-md-3"><input type="date" id="tbAsOf" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-2"><button class="btn bb w-100"><i class="fas fa-search"></i> Generate</button></div>
        </form>
        <div id="tbOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
        </div>
    </div>

    <!-- ══════════ Income Statement ══════════ -->
    <div id="tab_income" class="tab-content">
        <div class="content-section"><h5><i class="fas fa-file-invoice-dollar me-2"></i>Income Statement</h5>
        <form onsubmit="event.preventDefault(); loadIncomeStatement()" class="row g-2 mb-3">
            <div class="col-md-4"><input type="date" id="incFrom" class="form-control fc" value="<?= date('Y-01-01') ?>"></div>
            <div class="col-md-4"><input type="date" id="incTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-2"><button class="btn bb w-100"><i class="fas fa-search"></i> Generate</button></div>
        </form>
        <div id="incOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
        </div>
    </div>

    <!-- ══════════ Cashbook ══════════ -->
    <div id="tab_cashbook" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Cashbook Entry</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add_cashbook_entry">
                    <div class="row g-3 mt-2">
                        <div class="col-6"><label class="fl">Date *</label><input type="date" name="txn_date" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-6">
                            <label class="fl">Type *</label>
                            <select name="txn_type" class="form-select fs">
                                <option value="receipt">Receipt (Inflow)</option>
                                <option value="payment">Payment (Outflow)</option>
                            </select>
                        </div>
                        <div class="col-12"><label class="fl">Description *</label><input type="text" name="txn_description" class="form-control fc" required></div>
                        <div class="col-6"><label class="fl">Amount *</label><input type="number" name="txn_amount" class="form-control fc" required min="1" step="100"></div>
                        <div class="col-6"><label class="fl">Category</label><select name="txn_category" class="form-select fs"><option value="">Uncategorized</option><option value="tuition">Tuition</option><option value="accommodation">Accommodation</option><option value="clinical">Clinical</option><option value="salary">Salary</option><option value="utilities">Utilities</option><option value="supplies">Supplies</option></select></div>
                        <div class="col-12"><label class="fl">Reference</label><input type="text" name="txn_reference" class="form-control fc" placeholder="e.g. RCPT-001"></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Add Entry</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Cashbook Transactions</h5>
                <form onsubmit="event.preventDefault(); loadCashbook()" class="row g-2 mb-3">
                    <div class="col-md-4"><input type="date" id="cbFrom" class="form-control fc" value="<?= date('Y-m-01') ?>"></div>
                    <div class="col-md-4"><input type="date" id="cbTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-4"><button class="btn bb w-100"><i class="fas fa-search"></i> Load</button></div>
                </form>
                <div id="cashbookOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Bank Reconciliation ══════════ -->
    <div id="tab_recon" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Record Reconciliation</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add_reconciliation">
                    <div class="row g-3 mt-2">
                        <div class="col-12"><label class="fl">Date *</label><input type="date" name="recon_date" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-6"><label class="fl">Bank Statement Balance *</label><input type="number" name="bank_balance" class="form-control fc" required min="0" step="100"></div>
                        <div class="col-6"><label class="fl">Book Balance *</label><input type="number" name="book_balance" class="form-control fc" required min="0" step="100"></div>
                        <div class="col-12">
                            <div class="p-2 bg-light rounded small" id="diffPreview">Difference: UGX 0</div>
                        </div>
                        <div class="col-12"><label class="fl">Notes</label><textarea name="recon_notes" class="form-control fc" rows="2"></textarea></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Record</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-history me-2"></i>Reconciliation History</h5>
                <div id="reconOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
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
document.querySelectorAll('.tn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('.tn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function(t){ t.classList.remove('active'); });
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
        if (this.dataset.tab === 'tab_coa') loadAccounts();
        if (this.dataset.tab === 'tab_ledger') loadLedgerEntries();
        if (this.dataset.tab === 'tab_trial') loadTrialBalance();
        if (this.dataset.tab === 'tab_income') loadIncomeStatement();
        if (this.dataset.tab === 'tab_cashbook') loadCashbook();
        if (this.dataset.tab === 'tab_recon') loadReconciliation();
    });
});

// ── Chart of Accounts ──
function loadAccounts(){
    var out = document.getElementById('coaOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-ledger.php?ajax=accounts_list')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d.accounts||!d.accounts.length){ out.innerHTML = '<div class="text-center text-muted py-4">No accounts defined. Create one above.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Code</th><th>Account Name</th><th>Type</th><th>Status</th></tr></thead><tbody>';
        d.accounts.forEach(function(a){
            h += '<tr><td>'+esc(a.account_code)+'</td><td>'+esc(a.account_name)+'</td><td>'+esc(a.account_type)+'</td><td>'+badgeRaw(a.status||'active')+'</td></tr>';
        });
        h += '</tbody></table></div>'; out.innerHTML = h;
        // Populate ledger account select
        var sel = document.getElementById('ledgerAccountCode');
        if(sel){
            sel.innerHTML = '<option value="">-- Select Account --</option>';
            d.accounts.forEach(function(a){
                sel.innerHTML += '<option value="'+esc(a.account_code)+'">'+esc(a.account_code)+' - '+esc(a.account_name)+' ('+esc(a.account_type)+')</option>';
            });
        }
    }).catch(function(e){ console.warn('[ISNM]', e); });
}

// ── General Ledger ──
function loadLedgerEntries(){
    var f = document.getElementById('ledgerFrom').value, t = document.getElementById('ledgerTo').value;
    var out = document.getElementById('ledgerOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-ledger.php?ajax=ledger_entries&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d.entries||!d.entries.length){ out.innerHTML = '<div class="text-center text-muted py-4">No ledger entries in this period.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Date</th><th>Account</th><th>Description</th><th>Ref</th><th>Debit</th><th>Credit</th></tr></thead><tbody>';
        d.entries.forEach(function(e){
            h += '<tr><td><small>'+esc(e.entry_date)+'</small></td><td><small>'+esc(e.account_code||'')+'<br>'+esc(e.account_name||'')+'</small></td><td>'+esc(e.description||'')+'</td><td><small>'+esc(e.reference||'-')+'</small></td><td>'+(parseFloat(e.debit_amount||0)>0?currencyRaw(e.debit_amount):'-')+'</td><td>'+(parseFloat(e.credit_amount||0)>0?currencyRaw(e.credit_amount):'-')+'</td></tr>';
        });
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(e){ console.warn('[ISNM]', e); });
}

// ── Trial Balance ──
function loadTrialBalance(){
    var d = document.getElementById('tbAsOf').value;
    var out = document.getElementById('tbOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-ledger.php?ajax=trial_balance&as_of='+d)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-center text-muted py-4">No entries found. Post some journal entries first.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Code</th><th>Account</th><th>Type</th><th>Debit</th><th>Credit</th></tr></thead><tbody>';
        d.rows.forEach(function(r){
            h += '<tr><td>'+r[0]+'</td><td>'+r[1]+'</td><td>'+r[2]+'</td><td>'+r[3]+'</td><td>'+r[4]+'</td></tr>';
        });
        h += '<tr class="fw-bold table-light"><td colspan="3" class="text-end">Total</td><td>'+currencyRaw(d.total_debit||0)+'</td><td>'+currencyRaw(d.total_credit||0)+'</td></tr>';
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(e){ console.warn('[ISNM]', e); });
}

// ── Income Statement ──
function loadIncomeStatement(){
    var f = document.getElementById('incFrom').value, t = document.getElementById('incTo').value;
    var out = document.getElementById('incOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-ledger.php?ajax=income_statement&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        var h = '<h6 class="fw-bold">Income Statement</h6><p class="text-muted small">Period: '+esc(f)+' to '+esc(t)+'</p>';
        h += '<div class="table-responsive"><table class="table tb"><thead><tr><th>Item</th><th class="text-end">Amount</th></tr></thead><tbody>';
        h += '<tr class="table-primary"><td colspan="2"><strong>INCOME</strong></td></tr>';
        if(d.income_items&&d.income_items.length){ d.income_items.forEach(function(it){ h += '<tr><td>&nbsp;&nbsp;'+it[0]+'</td><td class="text-end">'+it[1]+'</td></tr>'; }); }
        h += '<tr class="fw-bold"><td>Total Income</td><td class="text-end">'+currencyRaw(d.total_income||0)+'</td></tr>';
        h += '<tr><td colspan="2">&nbsp;</td></tr>';
        h += '<tr class="table-danger"><td colspan="2"><strong>EXPENSES</strong></td></tr>';
        if(d.expense_items&&d.expense_items.length){ d.expense_items.forEach(function(it){ h += '<tr><td>&nbsp;&nbsp;'+it[0]+'</td><td class="text-end">'+it[1]+'</td></tr>'; }); }
        h += '<tr class="fw-bold"><td>Total Expenses</td><td class="text-end">'+currencyRaw(d.total_expenses||0)+'</td></tr>';
        h += '<tr><td colspan="2">&nbsp;</td></tr>';
        var netColor = (d.net_income||0) >= 0 ? 'text-success' : 'text-danger';
        h += '<tr class="fw-bold '+netColor+'"><td><strong>NET INCOME</strong></td><td class="text-end"><strong>'+currencyRaw(d.net_income||0)+'</strong></td></tr>';
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(e){ console.warn('[ISNM]', e); });
}

// ── Cashbook ──
function loadCashbook(){
    var f = document.getElementById('cbFrom').value, t = document.getElementById('cbTo').value;
    var out = document.getElementById('cashbookOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-ledger.php?ajax=cashbook&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d.entries||!d.entries.length){ out.innerHTML = '<div class="text-center text-muted py-4">No cashbook entries.</div>'; return; }
        var bal = 0;
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Category</th><th>Inflow</th><th>Outflow</th><th>Balance</th></tr></thead><tbody>';
        d.entries.forEach(function(e){
            var amt = parseFloat(e.amount)||0;
            if(e.transaction_type === 'receipt'){ bal += amt; h += '<tr><td><small>'+esc(e.transaction_date)+'</small></td><td><span class="badge bg-success">Receipt</span></td><td>'+esc(e.description||'')+'</td><td><small>'+esc(e.category||'-')+'</small></td><td>'+currencyRaw(amt)+'</td><td>-</td><td>'+currencyRaw(bal)+'</td></tr>'; }
            else { bal -= amt; h += '<tr><td><small>'+esc(e.transaction_date)+'</small></td><td><span class="badge bg-danger">Payment</span></td><td>'+esc(e.description||'')+'</td><td><small>'+esc(e.category||'-')+'</small></td><td>-</td><td>'+currencyRaw(amt)+'</td><td>'+currencyRaw(bal)+'</td></tr>'; }
        });
        h += '<tr class="fw-bold table-light"><td colspan="6" class="text-end">Closing Balance</td><td>'+currencyRaw(bal)+'</td></tr>';
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(e){ console.warn('[ISNM]', e); });
}

// ── Reconciliation ──
function loadReconciliation(){
    var out = document.getElementById('reconOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-ledger.php?ajax=reconciliation')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d.entries||!d.entries.length){ out.innerHTML = '<div class="text-center text-muted py-4">No reconciliation records found.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Date</th><th>Bank Balance</th><th>Book Balance</th><th>Difference</th><th>Status</th><th>Notes</th></tr></thead><tbody>';
        d.entries.forEach(function(e){
            var diff = parseFloat(e.difference)||0;
            h += '<tr><td><small>'+esc(e.reconciliation_date)+'</small></td><td>'+currencyRaw(e.bank_balance)+'</td><td>'+currencyRaw(e.book_balance)+'</td><td class="'+(Math.abs(diff)<1?'text-success':'text-danger')+'">'+currencyRaw(diff)+'</td><td>'+badgeRaw(e.status||'unreconciled')+'</td><td><small>'+esc(e.notes||'-')+'</small></td></tr>';
        });
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(e){ console.warn('[ISNM]', e); });
}

// ── Utility ──
function badgeRaw(s){ var m={active:'success',inactive:'secondary',reconciled:'success',unreconciled:'warning'}; var c=m[s.toLowerCase()]||'secondary'; return '<span class="badge bg-'+c+'">'+esc(s)+'</span>'; }
function currencyRaw(n){ return 'UGX '+Number(n||0).toLocaleString(); }
function esc(s){ if(!s) return ''; var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

// ── Live diff preview for reconciliation ──
document.addEventListener('DOMContentLoaded', function(){
    loadAccounts();
    var bankInput = document.querySelector('input[name="bank_balance"]');
    var bookInput = document.querySelector('input[name="book_balance"]');
    if(bankInput&&bookInput){
        function updateDiff(){
            var b = parseFloat(bankInput.value)||0, k = parseFloat(bookInput.value)||0;
            var diff = k - b;
            var el = document.getElementById('diffPreview');
            if(el) el.innerHTML = 'Difference: <strong class="'+(Math.abs(diff)<1?'text-success':'text-danger')+'">UGX '+Number(diff).toLocaleString()+'</strong>'+(Math.abs(diff)<1?' <span class="text-success">(In balance)</span>':'');
        }
        bankInput.addEventListener('input', updateDiff);
        bookInput.addEventListener('input', updateDiff);
    }
});
</script>
</body>
</html>
