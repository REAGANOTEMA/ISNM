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
    $m = ['active'=>'success','inactive'=>'secondary','filed'=>'success','pending'=>'warning','overdue'=>'danger','draft'=>'secondary','approved'=>'success','paid'=>'success'];
    $c = $m[strtolower($s)] ?? 'secondary';
    return '<span class="badge bg-'.$c.'">'.htmlspecialchars($s).'</span>';
}

// ── AJAX endpoints ──
if ($ajax) {
    header('Content-Type: application/json');
    $result = [];

    try {
        if (!$staff) throw new Exception('no db');

        if ($ajax === 'tax_periods') {
            $r = $staff->query("SELECT * FROM bursar_tax_periods ORDER BY period_start DESC LIMIT 50");
            if ($r) { $result['periods'] = []; while ($row = $r->fetch_assoc()) $result['periods'][] = $row; }
        } elseif ($ajax === 'tax_filings') {
            $r = $staff->query("SELECT tf.*, tp.period_name FROM bursar_tax_filings tf LEFT JOIN bursar_tax_periods tp ON tf.tax_period_id = tp.id ORDER BY tf.filing_date DESC LIMIT 50");
            if ($r) { $result['filings'] = []; while ($row = $r->fetch_assoc()) $result['filings'][] = $row; }
        } elseif ($ajax === 'tax_revenue_data') {
            $r = $staff->query("SELECT DATE_FORMAT(payment_date,'%Y-%m') AS m, COALESCE(SUM(amount_paid),0) AS revenue FROM fee_payments WHERE status='verified' AND payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY m ORDER BY m");
            if ($r) { $result['labels'] = []; $result['revenue'] = []; while ($row = $r->fetch_assoc()) { $result['labels'][] = $row['m']; $result['revenue'][] = (float)$row['revenue']; } }
            // Calculate estimated tax (18% VAT on revenue)
            if (!empty($result['revenue'])) $result['tax'] = array_map(function($v){ return $v * 0.18; }, $result['revenue']);
            else $result['tax'] = [];
        } elseif ($ajax === 'withholding_tax') {
            $r = $staff->query("SELECT * FROM bursar_withholding_tax ORDER BY tax_date DESC LIMIT 100");
            if ($r) { $result['entries'] = []; while ($row = $r->fetch_assoc()) $result['entries'][] = $row; }
        } elseif ($ajax === 'vat_reports') {
            $r = $staff->query("SELECT * FROM bursar_vat_reports ORDER BY period_start DESC LIMIT 50");
            if ($r) { $result['reports'] = []; while ($row = $r->fetch_assoc()) $result['reports'][] = $row; }
        }
    } catch (Exception $e) { error_log('tax ajax: '.$e->getMessage()); }
    echo json_encode($result);
    exit;
}

// ── POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = 'bursar-tax.php';

    if ($action === 'add_tax_period' && $staff) {
        try {
            $name = trim($_POST['period_name'] ?? '');
            $start = trim($_POST['period_start'] ?? '');
            $end = trim($_POST['period_end'] ?? '');
            $desc = trim($_POST['notes'] ?? '');
            $stmt = $staff->prepare("INSERT INTO bursar_tax_periods (period_name, period_start, period_end, notes, status) VALUES (?, ?, ?, ?, 'active')");
            if ($stmt) { $stmt->bind_param('ssss', $name, $start, $end, $desc); $stmt->execute() ? $_SESSION['success'] = 'Tax period created.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'record_filing' && $staff) {
        try {
            $period_id = (int)($_POST['tax_period_id'] ?? 0);
            $filing_date = trim($_POST['filing_date'] ?? date('Y-m-d'));
            $tax_type = trim($_POST['tax_type'] ?? 'vat');
            $total_revenue = (float)($_POST['total_revenue'] ?? 0);
            $tax_amount = (float)($_POST['tax_amount'] ?? 0);
            $due_date = trim($_POST['due_date'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $stmt = $staff->prepare("INSERT INTO bursar_tax_filings (tax_period_id, filing_date, tax_type, total_revenue, tax_amount, due_date, filing_status, notes) VALUES (?, ?, ?, ?, ?, ?, 'filed', ?)");
            if ($stmt) { $stmt->bind_param('issddss', $period_id, $filing_date, $tax_type, $total_revenue, $tax_amount, $due_date, $notes); $stmt->execute() ? $_SESSION['success'] = 'Filing recorded.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'record_withholding' && $staff) {
        try {
            $date = trim($_POST['tax_date'] ?? date('Y-m-d'));
            $payee = trim($_POST['payee_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $gross = (float)($_POST['gross_amount'] ?? 0);
            $rate = (float)($_POST['wht_rate'] ?? 6);
            $wht_amount = $gross * ($rate / 100);
            $stmt = $staff->prepare("INSERT INTO bursar_withholding_tax (tax_date, payee_name, description, gross_amount, wht_rate, wht_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            if ($stmt) { $stmt->bind_param('sssddd', $date, $payee, $desc, $gross, $rate, $wht_amount); $stmt->execute() ? $_SESSION['success'] = 'Withholding tax recorded.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'record_vat' && $staff) {
        try {
            $start = trim($_POST['vat_period_start'] ?? '');
            $end = trim($_POST['vat_period_end'] ?? '');
            $output_vat = (float)($_POST['output_vat'] ?? 0);
            $input_vat = (float)($_POST['input_vat'] ?? 0);
            $net_vat = $output_vat - $input_vat;
            $notes = trim($_POST['vat_notes'] ?? '');
            $stmt = $staff->prepare("INSERT INTO bursar_vat_reports (period_start, period_end, output_vat, input_vat, net_vat, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
            if ($stmt) { $stmt->bind_param('ssddds', $start, $end, $output_vat, $input_vat, $net_vat, $notes); $stmt->execute() ? $_SESSION['success'] = 'VAT report saved.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }
}

// ── Stats ──
$total_tax_periods = 0; $total_filings = 0; $pending_filings = 0; $total_wht = 0;
try {
    if ($staff) {
        $r = $staff->query("SELECT COUNT(*) AS c FROM bursar_tax_periods"); if ($r) $total_tax_periods = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COUNT(*) AS c FROM bursar_tax_filings"); if ($r) $total_filings = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COUNT(*) AS c FROM bursar_tax_filings WHERE filing_status='pending'"); if ($r) $pending_filings = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COALESCE(SUM(wht_amount),0) AS t FROM bursar_withholding_tax WHERE status='active'"); if ($r) $total_wht = (float)$r->fetch_assoc()['t'];
    }
} catch (Exception $e) {}

$pageTitle = 'Bursar - URA/Tax Reporting';
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
            <h1><i class="fas fa-file-invoice me-2"></i>URA / Tax Reporting</h1>
            <p>Tax periods, withholding tax, VAT reports, revenue analysis &amp; filing tracking</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:13px"><i class="far fa-clock me-1"></i><span id="currentDate"></span></span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-calendar-alt"></i></div><div class="stat-content"><h3><?= number_format($total_tax_periods) ?></h3><p>Tax Periods</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= number_format($total_filings) ?></h3><p>Filings</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card <?= $pending_filings > 0 ? 'warning' : 'success' ?>"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= number_format($pending_filings) ?></h3><p>Pending Filings</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card info"><div class="stat-icon"><i class="fas fa-percent"></i></div><div class="stat-content"><h3><?= currency($total_wht) ?></h3><p>Withholding Tax</p></div></div>
        </div>
    </div>

    <div class="tab-nav">
        <button class="tn active" data-tab="tab_periods"><i class="fas fa-calendar me-1"></i>Tax Periods</button>
        <button class="tn" data-tab="tab_filings"><i class="fas fa-file-export me-1"></i>Filings</button>
        <button class="tn" data-tab="tab_wht"><i class="fas fa-percent me-1"></i>Withholding Tax</button>
        <button class="tn" data-tab="tab_vat"><i class="fas fa-chart-bar me-1"></i>VAT Reports</button>
        <button class="tn" data-tab="tab_chart"><i class="fas fa-line-chart me-1"></i>Revenue vs Tax</button>
    </div>

    <!-- ══════════ Tax Periods ══════════ -->
    <div id="tab_periods" class="tab-content active">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Create Tax Period</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add_tax_period">
                    <div class="row g-3 mt-2">
                        <div class="col-12"><label class="fl">Period Name *</label><input type="text" name="period_name" class="form-control fc" required placeholder="e.g. Q1 2026"></div>
                        <div class="col-6"><label class="fl">Start Date *</label><input type="date" name="period_start" class="form-control fc" required></div>
                        <div class="col-6"><label class="fl">End Date *</label><input type="date" name="period_end" class="form-control fc" required></div>
                        <div class="col-12"><label class="fl">Notes</label><textarea name="notes" class="form-control fc" rows="2"></textarea></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Create Period</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Tax Periods</h5>
                <div id="periodsOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Filings ══════════ -->
    <div id="tab_filings" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Record Filing</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="record_filing">
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <label class="fl">Tax Period *</label>
                            <select name="tax_period_id" id="filingPeriodSelect" class="form-select fs" required>
                                <option value="">-- Select Period --</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="fl">Tax Type *</label>
                            <select name="tax_type" class="form-select fs">
                                <option value="vat">VAT</option><option value="withholding">Withholding Tax</option>
                                <option value="income_tax">Income Tax</option><option value="paye">PAYE</option>
                                <option value="local_service">Local Service Tax</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="fl">Filing Date *</label><input type="date" name="filing_date" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-6"><label class="fl">Total Revenue (UGX)</label><input type="number" name="total_revenue" class="form-control fc" value="0" min="0"></div>
                        <div class="col-6"><label class="fl">Tax Amount (UGX)</label><input type="number" name="tax_amount" class="form-control fc" value="0" min="0"></div>
                        <div class="col-6"><label class="fl">Due Date</label><input type="date" name="due_date" class="form-control fc"></div>
                        <div class="col-12"><label class="fl">Notes</label><textarea name="notes" class="form-control fc" rows="2"></textarea></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Record Filing</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-history me-2"></i>Filing History</h5>
                <div id="filingsOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Withholding Tax ══════════ -->
    <div id="tab_wht" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Record Withholding Tax</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="record_withholding">
                    <div class="row g-3 mt-2">
                        <div class="col-6"><label class="fl">Date *</label><input type="date" name="tax_date" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-6"><label class="fl">WHT Rate (%)</label><select name="wht_rate" class="form-select fs"><option value="6">6%</option><option value="3">3%</option><option value="15">15%</option></select></div>
                        <div class="col-12"><label class="fl">Payee Name *</label><input type="text" name="payee_name" class="form-control fc" required></div>
                        <div class="col-12"><label class="fl">Description *</label><input type="text" name="description" class="form-control fc" required></div>
                        <div class="col-6"><label class="fl">Gross Amount *</label><input type="number" name="gross_amount" class="form-control fc" required min="1"></div>
                        <div class="col-6">
                            <label class="fl">WHT Amount</label>
                            <div class="form-control fc bg-light" id="whtPreview">UGX 0</div>
                        </div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Record</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Withholding Tax Records</h5>
                <div id="whtOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ VAT Reports ══════════ -->
    <div id="tab_vat" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Create VAT Report</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="record_vat">
                    <div class="row g-3 mt-2">
                        <div class="col-6"><label class="fl">Period Start *</label><input type="date" name="vat_period_start" class="form-control fc" required></div>
                        <div class="col-6"><label class="fl">Period End *</label><input type="date" name="vat_period_end" class="form-control fc" required></div>
                        <div class="col-6"><label class="fl">Output VAT (UGX)</label><input type="number" name="output_vat" class="form-control fc" value="0" min="0"></div>
                        <div class="col-6"><label class="fl">Input VAT (UGX)</label><input type="number" name="input_vat" class="form-control fc" value="0" min="0"></div>
                        <div class="col-12">
                            <div class="p-2 bg-light rounded small" id="netVatPreview">Net VAT: UGX 0</div>
                        </div>
                        <div class="col-12"><label class="fl">Notes</label><textarea name="vat_notes" class="form-control fc" rows="2"></textarea></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Save Report</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>VAT Reports</h5>
                <div id="vatOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Revenue vs Tax Chart ══════════ -->
    <div id="tab_chart" class="tab-content">
        <div class="content-section">
            <h5><i class="fas fa-line-chart me-2"></i>Revenue vs Estimated Tax (18% VAT) - Last 12 Months</h5>
            <canvas id="taxRevenueChart" height="320"></canvas>
        </div>
    </div>

</div><!-- /ma -->

<?php
if (isset($_SESSION['success'])) { echo '<div class="alert alert-success alert-dismissible fade show attoast"><i class="fas fa-check-circle me-1"></i> '.htmlspecialchars($_SESSION['success']).' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['success']); }
if (isset($_SESSION['error'])) { echo '<div class="alert alert-danger alert-dismissible fade show attoast"><i class="fas fa-exclamation-triangle me-1"></i> '.htmlspecialchars($_SESSION['error']).' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['error']); }
?>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<script>
var taxChartInstance = null;

document.querySelectorAll('.tn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('.tn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function(t){ t.classList.remove('active'); });
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
        if (this.dataset.tab === 'tab_periods') loadPeriods();
        if (this.dataset.tab === 'tab_filings') { loadPeriodsSelect(); loadFilings(); }
        if (this.dataset.tab === 'tab_wht') loadWHT();
        if (this.dataset.tab === 'tab_vat') loadVAT();
        if (this.dataset.tab === 'tab_chart') loadTaxChart();
    });
});

// ── Tax Periods ──
function loadPeriods(){
    var out = document.getElementById('periodsOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-tax.php?ajax=tax_periods')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d.periods||!d.periods.length){ out.innerHTML = '<div class="text-center text-muted py-4">No tax periods defined.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Name</th><th>Start</th><th>End</th><th>Status</th><th>Notes</th></tr></thead><tbody>';
        d.periods.forEach(function(p){
            h += '<tr><td><strong>'+esc(p.period_name)+'</strong></td><td><small>'+esc(p.period_start)+'</small></td><td><small>'+esc(p.period_end)+'</small></td><td>'+badgeRaw(p.status||'active')+'</td><td><small>'+esc(p.notes||'-')+'</small></td></tr>';
        });
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(){});
}

// ── Filings ──
function loadPeriodsSelect(){
    fetch('bursar-tax.php?ajax=tax_periods')
    .then(function(r){ return r.json(); })
    .then(function(d){
        var sel = document.getElementById('filingPeriodSelect');
        if(!sel) return;
        sel.innerHTML = '<option value="">-- Select Period --</option>';
        if(d.periods) d.periods.forEach(function(p){
            sel.innerHTML += '<option value="'+p.id+'">'+esc(p.period_name)+' ('+esc(p.period_start)+' to '+esc(p.period_end)+')</option>';
        });
    }).catch(function(){});
}
function loadFilings(){
    var out = document.getElementById('filingsOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-tax.php?ajax=tax_filings')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d.filings||!d.filings.length){ out.innerHTML = '<div class="text-center text-muted py-4">No filings recorded.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Period</th><th>Type</th><th>Filing Date</th><th>Revenue</th><th>Tax</th><th>Status</th><th>Due</th></tr></thead><tbody>';
        d.filings.forEach(function(f){
            h += '<tr><td><small>'+esc(f.period_name||'N/A')+'</small></td><td>'+badgeRaw(f.tax_type||'vat')+'</td><td><small>'+esc(f.filing_date)+'</small></td><td>'+currencyRaw(f.total_revenue)+'</td><td>'+currencyRaw(f.tax_amount)+'</td><td>'+badgeRaw(f.filing_status||'filed')+'</td><td><small>'+esc(f.due_date||'-')+'</small></td></tr>';
        });
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(){});
}

// ── Withholding Tax ──
function loadWHT(){
    var out = document.getElementById('whtOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-tax.php?ajax=withholding_tax')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d.entries||!d.entries.length){ out.innerHTML = '<div class="text-center text-muted py-4">No withholding tax records.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Date</th><th>Payee</th><th>Description</th><th>Gross</th><th>Rate</th><th>WHT Amount</th><th>Status</th></tr></thead><tbody>';
        d.entries.forEach(function(e){
            h += '<tr><td><small>'+esc(e.tax_date)+'</small></td><td>'+esc(e.payee_name)+'</td><td><small>'+esc(e.description)+'</small></td><td>'+currencyRaw(e.gross_amount)+'</td><td>'+parseFloat(e.wht_rate).toFixed(1)+'%</td><td>'+currencyRaw(e.wht_amount)+'</td><td>'+badgeRaw(e.status||'active')+'</td></tr>';
        });
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(){});
}

// ── VAT Reports ──
function loadVAT(){
    var out = document.getElementById('vatOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-tax.php?ajax=vat_reports')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d.reports||!d.reports.length){ out.innerHTML = '<div class="text-center text-muted py-4">No VAT reports created.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Period</th><th>Output VAT</th><th>Input VAT</th><th>Net VAT</th><th>Status</th></tr></thead><tbody>';
        d.reports.forEach(function(r){
            var net = parseFloat(r.net_vat)||0;
            h += '<tr><td><small>'+esc(r.period_start)+' to '+esc(r.period_end)+'</small></td><td>'+currencyRaw(r.output_vat)+'</td><td>'+currencyRaw(r.input_vat)+'</td><td class="'+(net>=0?'text-danger':'text-success')+' fw-bold">'+currencyRaw(net)+'</td><td>'+badgeRaw(r.status||'draft')+'</td></tr>';
        });
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(){});
}

// ── Revenue vs Tax Chart ──
function loadTaxChart(){
    var ctx = document.getElementById('taxRevenueChart');
    if(!ctx) return;
    fetch('bursar-tax.php?ajax=tax_revenue_data')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(taxChartInstance) taxChartInstance.destroy();
        if(!d.labels||!d.labels.length){
            ctx.parentNode.innerHTML = '<div class="text-center text-muted py-4">No revenue data for chart.</div>';
            return;
        }
        taxChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: d.labels,
                datasets: [
                    {
                        label: 'Revenue (UGX)',
                        data: d.revenue,
                        backgroundColor: 'rgba(26,35,126,0.7)',
                        borderColor: '#1a237e',
                        borderWidth: 2,
                        borderRadius: 4,
                        order: 2
                    },
                    {
                        label: 'Est. Tax (18% VAT)',
                        data: d.tax,
                        backgroundColor: 'rgba(220,38,38,0.7)',
                        borderColor: '#dc2626',
                        borderWidth: 2,
                        borderRadius: 4,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16, font: { size: 12 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx){ return ctx.dataset.label+': UGX '+Number(ctx.parsed.y).toLocaleString(); }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(v){ return 'UGX '+(v/1000000).toFixed(1)+'M'; } } }
                }
            }
        }).catch(function(){});
    });
}

// ── Live WHT preview ──
document.addEventListener('DOMContentLoaded', function(){
    loadPeriods();
    var grossInput = document.querySelector('input[name="gross_amount"]');
    var rateSelect = document.querySelector('select[name="wht_rate"]');
    if(grossInput&&rateSelect){
        function updateWHT(){
            var g = parseFloat(grossInput.value)||0;
            var r = parseFloat(rateSelect.value)||6;
            var wht = g * (r / 100);
            var el = document.getElementById('whtPreview');
            if(el) el.textContent = 'UGX '+Number(wht).toLocaleString();
        }
        grossInput.addEventListener('input', updateWHT);
        rateSelect.addEventListener('change', updateWHT);
    }
    // VAT net preview
    var outVat = document.querySelector('input[name="output_vat"]');
    var inVat = document.querySelector('input[name="input_vat"]');
    if(outVat&&inVat){
        function updateVAT(){
            var o = parseFloat(outVat.value)||0;
            var i = parseFloat(inVat.value)||0;
            var net = o - i;
            var el = document.getElementById('netVatPreview');
            if(el) el.innerHTML = 'Net VAT: <strong class="'+(net>=0?'text-danger':'text-success')+'">UGX '+Number(net).toLocaleString()+'</strong>';
        }
        outVat.addEventListener('input', updateVAT);
        inVat.addEventListener('input', updateVAT);
    }
    // Load chart on initial if tab active
    setTimeout(loadTaxChart, 500);
});

function badgeRaw(s){ var m={active:'success',inactive:'secondary',filed:'success',pending:'warning',overdue:'danger',draft:'secondary',paid:'success'}; var c=m[s.toLowerCase()]||'secondary'; return '<span class="badge bg-'+c+'">'+esc(s)+'</span>'; }
function currencyRaw(n){ return 'UGX '+Number(n||0).toLocaleString(); }
function esc(s){ if(!s) return ''; var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>
</body>
</html>
