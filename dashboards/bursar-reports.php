<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/financial_functions.php';

$ctx = bootstrapStaffDashboard(['bursar', 'accountant', 'finance']);
$staff = $ctx['staff'];
$students = $ctx['students'];
$website = $ctx['website'];
$user = $ctx['user'];

$ajax = $_GET['ajax'] ?? '';

function currency($n) { return 'UGX ' . number_format((float)$n, 0); }
function badge($s) {
    $m = ['verified'=>'success','pending'=>'warning','fully_paid'=>'success','partially_paid'=>'info','unpaid'=>'secondary','overdue'=>'danger'];
    $c = $m[strtolower($s)] ?? 'secondary';
    return '<span class="badge bg-'.$c.'">'.htmlspecialchars($s).'</span>';
}

// ── AJAX report endpoints ──
if ($ajax) {
    header('Content-Type: application/json');
    $from = $staff->real_escape_string($_GET['from'] ?? date('Y-m-01'));
    $to = $staff->real_escape_string($_GET['to'] ?? date('Y-m-d'));
    $result = ['headers' => [], 'rows' => [], 'total' => 0, 'chart_labels' => [], 'chart_values' => []];

    try { if (!$staff) throw new Exception('no db');

    if ($ajax === 'daily') {
        $result['headers'] = ['Date', 'Transactions', 'Total Collected'];
        $r = $staff->query("SELECT DATE(payment_date) AS dt, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='verified' GROUP BY DATE(payment_date) ORDER BY dt");
        if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['dt'], $row['cnt'], currency($row['tot'])]; $result['total'] += $row['tot']; $result['chart_labels'][] = $row['dt']; $result['chart_values'][] = (float)$row['tot']; }
    } elseif ($ajax === 'weekly') {
        $result['headers'] = ['Week Starting', 'Transactions', 'Total'];
        $r = $staff->query("SELECT DATE_SUB(payment_date, INTERVAL WEEKDAY(payment_date) DAY) AS wk, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='verified' GROUP BY wk ORDER BY wk");
        if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['wk'], $row['cnt'], currency($row['tot'])]; $result['total'] += $row['tot']; $result['chart_labels'][] = $row['wk']; $result['chart_values'][] = (float)$row['tot']; }
    } elseif ($ajax === 'monthly') {
        $result['headers'] = ['Month', 'Payments', 'Total'];
        $r = $staff->query("SELECT DATE_FORMAT(payment_date,'%Y-%m') AS m, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='verified' GROUP BY m ORDER BY m");
        if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['m'], $row['cnt'], currency($row['tot'])]; $result['total'] += $row['tot']; $result['chart_labels'][] = $row['m']; $result['chart_values'][] = (float)$row['tot']; }
    } elseif ($ajax === 'revenue_category') {
        $result['headers'] = ['Category', 'Amount', 'Percentage'];
        $grand_total = 0;
        $r = $staff->query("SELECT payment_method, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='verified' GROUP BY payment_method");
        if ($r) { $all = []; while ($row = $r->fetch_assoc()) { $all[] = $row; $grand_total += (float)$row['tot']; } foreach ($all as $row) { $pct = $grand_total > 0 ? round(((float)$row['tot']/$grand_total)*100, 1).'%' : '0%'; $result['rows'][] = [ucfirst(str_replace('_',' ',$row['payment_method'])), currency($row['tot']), $pct]; $result['chart_labels'][] = ucfirst(str_replace('_',' ',$row['payment_method'])); $result['chart_values'][] = (float)$row['tot']; } $result['total'] = $grand_total; }
    } elseif ($ajax === 'outstanding') {
        $result['headers'] = ['Student ID', 'Student Name', 'Program', 'Total Fees', 'Paid', 'Balance'];
        $r = $staff->query("SELECT sfa.*, s.first_name, s.surname, s.program FROM student_fee_accounts sfa LEFT JOIN students s ON sfa.student_id = s.student_id WHERE sfa.status NOT IN ('fully_paid','cancelled') ORDER BY sfa.balance DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['student_id'], htmlspecialchars(($row['surname']??'').' '.($row['first_name']??'')), htmlspecialchars($row['program']??'-'), currency($row['total_fees']), currency($row['amount_paid']), '<strong class="text-danger">'.currency($row['balance']).'</strong>']; $result['total'] += (float)$row['balance']; }
    } elseif ($ajax === 'statement' && !empty($_GET['sid'])) {
        $sid = $staff->real_escape_string($_GET['sid']);
        $result['headers'] = ['Date', 'Description', 'Debit', 'Credit', 'Balance'];
        $txns = []; $bal = 0;
        $inv = $staff->query("SELECT created_at AS dt, invoice_number, total_fees FROM student_fee_accounts WHERE student_id='$sid' ORDER BY created_at ASC");
        if ($inv) while ($row = $inv->fetch_assoc()) { $txns[] = ['dt'=>$row['dt'], 'desc'=>'Invoice '.$row['invoice_number'], 'debit'=>(float)$row['total_fees'], 'credit'=>0]; }
        $pay = $staff->query("SELECT payment_date AS dt, receipt_number, amount_paid FROM fee_payments WHERE student_id='$sid' AND status='verified' ORDER BY payment_date ASC");
        if ($pay) while ($row = $pay->fetch_assoc()) { $txns[] = ['dt'=>$row['dt'], 'desc'=>'Payment '.$row['receipt_number'], 'debit'=>0, 'credit'=>(float)$row['amount_paid']]; }
        usort($txns, function($a,$b){ return strcmp($a['dt'], $b['dt']); });
        foreach ($txns as $tx) { $bal += (float)$tx['debit'] - (float)$tx['credit']; $result['rows'][] = [$tx['dt'], htmlspecialchars($tx['desc']), $tx['debit'] > 0 ? currency($tx['debit']) : '-', $tx['credit'] > 0 ? currency($tx['credit']) : '-', '<strong>'.currency($bal).'</strong>']; }
        $result['total'] = $bal;
    }

    } catch (Exception $e) { error_log('report ajax: '.$e->getMessage()); }
    echo json_encode($result);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'export_pdf') {
    header('Content-Type: text/html; charset=utf-8');
    $from = $_POST['from'] ?? date('Y-m-01'); $to = $_POST['to'] ?? date('Y-m-d');
    $type = $_POST['report_type'] ?? 'monthly';
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#1a237e;color:#fff}h2{color:#1a237e}.text-end{text-align:right}</style></head><body>';
    echo '<h2>ISNM Financial Report</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).' | Type: '.htmlspecialchars(ucfirst($type)).'</p>';
    echo '<p>Generated: '.date('d M Y H:i').'</p><table><thead><tr><th>#</th><th>Description</th><th>Amount</th></tr></thead><tbody>';
    $q = $type === 'monthly' ? "SELECT DATE_FORMAT(payment_date,'%Y-%m') AS label, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='verified' GROUP BY label ORDER BY label" : "SELECT DATE(payment_date) AS label, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='verified' GROUP BY label ORDER BY label";
    $r = $staff->query($q); $i = 1; $gt = 0;
    if ($r) while ($row = $r->fetch_assoc()) { echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['label']).' ('.$row['cnt'].' txns)</td><td class="text-end">'.number_format($row['tot'],0).'</td></tr>'; $gt += $row['tot']; }
    echo '<tr style="font-weight:bold"><td colspan="2">Total</td><td class="text-end">'.number_format($gt,0).'</td></tr>';
    echo '</tbody></table><p><em>End of report</em></p></body></html>';
    exit;
}

// ── Stats ──
$ytd_revenue = 0; $month_revenue = 0; $total_outstanding = 0; $total_invoices = 0;
try {
    if ($staff) {
        $r = $staff->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE YEAR(payment_date)=YEAR(CURDATE()) AND status='verified'"); if ($r) $ytd_revenue = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status='verified'"); if ($r) $month_revenue = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COALESCE(SUM(balance),0) AS t FROM student_fee_accounts WHERE status NOT IN ('fully_paid','cancelled')"); if ($r) $total_outstanding = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COUNT(*) AS c FROM student_fee_accounts"); if ($r) $total_invoices = (int)$r->fetch_assoc()['c'];
    }
} catch (Exception $e) {}

$pageTitle = 'Bursar - Financial Reports';
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
            <h1><i class="fas fa-chart-bar me-2"></i>Financial Reports & Analytics</h1>
            <p>Revenue summaries, outstanding balances, student statements &amp; exports</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:13px"><i class="far fa-clock me-1"></i><span id="currentDate"></span></span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-chart-line"></i></div><div class="stat-content"><h3><?= currency($ytd_revenue) ?></h3><p>YTD Revenue</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card success"><div class="stat-icon"><i class="fas fa-calendar-alt"></i></div><div class="stat-content"><h3><?= currency($month_revenue) ?></h3><p>This Month</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-content"><h3><?= currency($total_outstanding) ?></h3><p>Outstanding Balance</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card info"><div class="stat-icon"><i class="fas fa-file-invoice"></i></div><div class="stat-content"><h3><?= number_format($total_invoices) ?></h3><p>Total Invoices</p></div></div>
        </div>
    </div>

    <div class="tab-nav">
        <button class="tn active" data-tab="tab_collections"><i class="fas fa-money-bill-wave me-1"></i>Collections</button>
        <button class="tn" data-tab="tab_outstanding"><i class="fas fa-exclamation-circle me-1"></i>Outstanding Debtors</button>
        <button class="tn" data-tab="tab_revenue"><i class="fas fa-chart-pie me-1"></i>Revenue by Category</button>
        <button class="tn" data-tab="tab_statement"><i class="fas fa-file-alt me-1"></i>Student Statement</button>
        <button class="tn" data-tab="tab_chart"><i class="fas fa-line-chart me-1"></i>Revenue Chart</button>
    </div>

    <!-- ══════════ Collections ══════════ -->
    <div id="tab_collections" class="tab-content active">
        <div class="content-section">
            <h5><i class="fas fa-filter me-2"></i>Collection Report</h5>
            <form id="reportForm" onsubmit="event.preventDefault(); loadReport()" class="row g-2 mb-3">
                <div class="col-md-3"><label class="fl">From</label><input type="date" id="rptFrom" class="form-control fc" value="<?= date('Y-m-01') ?>"></div>
                <div class="col-md-3"><label class="fl">To</label><input type="date" id="rptTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-3">
                    <label class="fl">Period</label>
                    <select id="rptType" class="form-select fs">
                        <option value="daily">Daily Collections</option>
                        <option value="weekly">Weekly Collections</option>
                        <option value="monthly">Monthly Collections</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn bb w-100"><i class="fas fa-search me-1"></i>Generate</button></div>
            </form>
            <div class="d-flex gap-2 mb-3 no-print" id="rptActions" style="display:none">
                <button class="btn bo btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
                <button class="btn bo btn-sm" onclick="exportExcel()"><i class="fas fa-file-excel me-1"></i>Export Excel</button>
                <button class="btn bo btn-sm" onclick="exportPDF()"><i class="fas fa-file-pdf me-1"></i>Export PDF</button>
            </div>
            <div id="rptOutput"><div class="text-center text-muted py-4">Select period and click Generate.</div></div>
        </div>
    </div>

    <!-- ══════════ Outstanding Debtors ══════════ -->
    <div id="tab_outstanding" class="tab-content">
        <div class="content-section">
            <h5><i class="fas fa-exclamation-circle me-2"></i>Outstanding Balances / Debtors List</h5>
            <div class="d-flex gap-2 mb-3">
                <button class="btn bo btn-sm" onclick="loadOutstanding()"><i class="fas fa-sync me-1"></i>Refresh</button>
                <button class="btn bo btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
                <button class="btn bo btn-sm" onclick="exportExcel()"><i class="fas fa-file-excel me-1"></i>Export</button>
            </div>
            <div id="outstandingOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
        </div>
    </div>

    <!-- ══════════ Revenue by Category ══════════ -->
    <div id="tab_revenue" class="tab-content">
        <div class="row g-4">
            <div class="col-md-7">
                <div class="content-section">
                    <h5><i class="fas fa-chart-pie me-2"></i>Revenue by Category (Fee Structure)</h5>
                    <form onsubmit="event.preventDefault(); loadRevenueCategory()" class="row g-2 mb-3">
                        <div class="col-md-4"><label class="fl">From</label><input type="date" id="revFrom" class="form-control fc" value="<?= date('Y-m-01') ?>"></div>
                        <div class="col-md-4"><label class="fl">To</label><input type="date" id="revTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn bb w-100"><i class="fas fa-search"></i> Generate</button></div>
                    </form>
                    <div id="revCategoryOutput"><div class="text-center text-muted py-4">Set parameters and search.</div></div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="content-section">
                    <h5><i class="fas fa-chart-pie me-2"></i>Distribution</h5>
                    <canvas id="revPieChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Student Statement ══════════ -->
    <div id="tab_statement" class="tab-content">
        <div class="content-section">
            <h5><i class="fas fa-file-alt me-2"></i>Student Statement of Account</h5>
            <form onsubmit="event.preventDefault(); loadStatement()" class="row g-2 mb-3">
                <div class="col-md-5">
                    <div class="input-group"><input type="text" id="stmtStudentQuery" class="form-control fc" placeholder="Search student by name or ID..." autocomplete="off"><button class="btn bb" type="button" onclick="searchStatementStudent()"><i class="fas fa-search"></i></button></div>
                    <div id="stmtSearchResults" class="mt-2"></div>
                    <input type="hidden" id="stmtStudentId">
                </div>
            </form>
            <div class="d-flex gap-2 mb-3 no-print" id="stmtActions" style="display:none">
                <button class="btn bo btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
                <button class="btn bo btn-sm" onclick="exportExcel()"><i class="fas fa-file-excel me-1"></i>Export Excel</button>
            </div>
            <div id="stmtOutput"></div>
        </div>
    </div>

    <!-- ══════════ Revenue Chart ══════════ -->
    <div id="tab_chart" class="tab-content">
        <div class="content-section">
            <h5><i class="fas fa-line-chart me-2"></i>Monthly Revenue Chart</h5>
            <canvas id="revenueLineChart" height="300"></canvas>
        </div>
    </div>

</div><!-- /ma -->

<?php
if (isset($_SESSION['success'])) { echo '<div class="alert alert-success alert-dismissible fade show attoast"><i class="fas fa-check-circle me-1"></i> '.htmlspecialchars($_SESSION['success']).' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['success']); }
if (isset($_SESSION['error'])) { echo '<div class="alert alert-danger alert-dismissible fade show attoast"><i class="fas fa-exclamation-triangle me-1"></i> '.htmlspecialchars($_SESSION['error']).' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['error']); }
?>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<script>
var revPieChart = null, revLineChart = null;

document.querySelectorAll('.tn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('.tn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function(t){ t.classList.remove('active'); });
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
        if (this.dataset.tab === 'tab_outstanding') loadOutstanding();
        if (this.dataset.tab === 'tab_chart') loadRevenueChart();
        if (this.dataset.tab === 'tab_revenue') loadRevenueCategory();
    });
});

// ── Collections Report ──
function loadReport(){
    var f = document.getElementById('rptFrom').value, t = document.getElementById('rptTo').value, tp = document.getElementById('rptType').value;
    var out = document.getElementById('rptOutput'), acts = document.getElementById('rptActions');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'; acts.style.display = 'none';
    fetch('bursar-reports.php?ajax='+tp+'&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-center text-muted py-4">No data for this period.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb" id="rptTable"><thead><tr>';
        (d.headers||[]).forEach(function(hd){ h += '<th>'+hd+'</th>'; });
        h += '</tr></thead><tbody>';
        d.rows.forEach(function(r){ h += '<tr>'; r.forEach(function(c){ h += '<td>'+c+'</td>'; }); h += '</tr>'; });
        if(d.total !== undefined) h += '<tr class="fw-bold table-light"><td colspan="'+(d.headers.length-1)+'" class="text-end">Total</td><td>'+Number(d.total).toLocaleString()+'</td></tr>';
        h += '</tbody></table></div>'; out.innerHTML = h; acts.style.display = 'flex';
    }).catch(function(){ out.innerHTML = '<div class="alert alert-danger">Failed to load report.</div>'; });
}

// ── Outstanding ──
function loadOutstanding(){
    var out = document.getElementById('outstandingOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-reports.php?ajax=outstanding')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-center text-muted py-4">No outstanding balances.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb" id="rptTable"><thead><tr>';
        (d.headers||[]).forEach(function(hd){ h += '<th>'+hd+'</th>'; });
        h += '</tr></thead><tbody>';
        d.rows.forEach(function(r){ h += '<tr>'; r.forEach(function(c){ h += '<td>'+c+'</td>'; }); h += '</tr>'; });
        if(d.total !== undefined) h += '<tr class="fw-bold table-danger"><td colspan="5" class="text-end">Total Outstanding</td><td>'+Number(d.total).toLocaleString()+'</td></tr>';
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(){ out.innerHTML = '<div class="alert alert-danger">Failed to load.</div>'; });
}

// ── Revenue by Category ──
var revPieInstance = null;
function loadRevenueCategory(){
    var f = document.getElementById('revFrom').value, t = document.getElementById('revTo').value;
    var out = document.getElementById('revCategoryOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar-reports.php?ajax=revenue_category&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-center text-muted py-4">No data.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb" id="rptTable"><thead><tr>';
        (d.headers||[]).forEach(function(hd){ h += '<th>'+hd+'</th>'; });
        h += '</tr></thead><tbody>';
        d.rows.forEach(function(r){ h += '<tr>'; r.forEach(function(c){ h += '<td>'+c+'</td>'; }); h += '</tr>'; });
        if(d.total !== undefined) h += '<tr class="fw-bold table-light"><td colspan="2" class="text-end">Total</td><td>100%</td></tr>';
        h += '</tbody></table></div>'; out.innerHTML = h;
        if(revPieInstance) revPieInstance.destroy();
        var ctx = document.getElementById('revPieChart');
        if(ctx && d.chart_labels && d.chart_labels.length){
            var colors = ['#1a237e','#059669','#d97706','#dc2626','#0891b2','#7c3aed','#84cc16','#f97316'];
            revPieInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: d.chart_labels,
                    datasets: [{
                        data: d.chart_values,
                        backgroundColor: colors.slice(0, d.chart_labels.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: function(ctx){
                                    var total = ctx.dataset.data.reduce(function(a,b){ return a+b; },0);
                                    var pct = total > 0 ? ((ctx.parsed/total)*100).toFixed(1) : 0;
                                    return 'UGX '+Number(ctx.parsed).toLocaleString()+' ('+pct+'%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
}

// ── Student Statement ──
function searchStatementStudent(){
    var q = document.getElementById('stmtStudentQuery').value.trim();
    if(!q) return;
    fetch('bursar-payments.php?ajax=search_student&sid='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(data){
        var el = document.getElementById('stmtSearchResults'); el.innerHTML = '';
        if(!data||!data.length){ el.innerHTML = '<div class="text-muted small p-2">No students found.</div>'; return; }
        data.forEach(function(s){
            var d = document.createElement('div');
            d.className = 'sri';
            d.innerHTML = '<strong>'+esc(s.surname)+', '+esc(s.first_name)+'</strong><br><small class="text-muted">'+esc(s.student_id)+' | '+esc(s.program||'')+'</small>';
            d.addEventListener('click',function(){
                document.getElementById('stmtStudentId').value = s.student_id;
                document.getElementById('stmtStudentQuery').value = s.surname+', '+s.first_name+' ('+s.student_id+')';
                document.getElementById('stmtSearchResults').innerHTML = '';
                loadStatement(s.student_id, s);
            });
            el.appendChild(d);
        });
    });
}
function loadStatement(sid, s){
    var out = document.getElementById('stmtOutput'), acts = document.getElementById('stmtActions');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'; acts.style.display = 'none';
    fetch('bursar-reports.php?ajax=statement&sid='+encodeURIComponent(sid))
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-center text-muted py-4">No transactions found.</div>'; return; }
        var name = s ? esc(s.surname)+', '+esc(s.first_name) : sid;
        var h = '<h6 class="fw-bold">Statement: '+name+' ('+esc(sid)+')</h6>';
        h += '<div class="table-responsive"><table class="table tb" id="rptTable"><thead><tr>';
        (d.headers||[]).forEach(function(hd){ h += '<th>'+hd+'</th>'; });
        h += '</tr></thead><tbody>';
        d.rows.forEach(function(r){ h += '<tr>'; r.forEach(function(c){ h += '<td>'+c+'</td>'; }); h += '</tr>'; });
        if(d.total !== undefined) h += '<tr class="fw-bold table-light"><td colspan="4" class="text-end">Closing Balance</td><td>'+Number(d.total).toLocaleString()+'</td></tr>';
        h += '</tbody></table></div>'; out.innerHTML = h; acts.style.display = 'flex';
    });
}

// ── Revenue Chart ──
function loadRevenueChart(){
    var ctx = document.getElementById('revenueLineChart');
    if(!ctx) return;
    var f = new Date().getFullYear()+'-01-01', t = new Date().toISOString().slice(0,10);
    fetch('bursar-reports.php?ajax=monthly&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(revLineChart) revLineChart.destroy();
        if(!d.chart_labels||!d.chart_labels.length){
            ctx.parentNode.innerHTML = '<div class="text-center text-muted py-4">No revenue data available.</div>';
            return;
        }
        revLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: d.chart_labels,
                datasets: [{
                    label: 'Revenue (UGX)',
                    data: d.chart_values,
                    borderColor: '#1a237e',
                    backgroundColor: 'rgba(26,35,126,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1a237e',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx){ return 'UGX '+Number(ctx.parsed.y).toLocaleString(); }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(v){ return 'UGX '+(v/1000).toFixed(0)+'K'; } } }
                }
            }
        });
    });
}
document.addEventListener('DOMContentLoaded', function(){ loadRevenueChart(); });

// ── Export helpers ──
function exportExcel(){
    var tbl = document.getElementById('rptTable');
    if(!tbl) return;
    var html = '<html><head><meta charset="UTF-8"><title>ISNM Report</title><style>td,th{border:1px solid #ccc;padding:6px 10px}th{background:#1a237e;color:#fff}</style></head><body>'+tbl.outerHTML+'</body></html>';
    var blob = new Blob([html], {type:'application/vnd.ms-excel'});
    var a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = 'ISNM_Report_'+new Date().toISOString().slice(0,10)+'.xls'; a.click();
}
function exportPDF(){
    var f = document.getElementById('rptFrom') ? document.getElementById('rptFrom').value : '';
    var t = document.getElementById('rptTo') ? document.getElementById('rptTo').value : '';
    var tp = document.getElementById('rptType') ? document.getElementById('rptType').value : 'monthly';
    var form = document.createElement('form'); form.method = 'POST'; form.action = 'bursar-reports.php';
    form.target = '_blank';
    ['from','to','report_type'].forEach(function(n){
        var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = n;
        inp.value = n==='from'?f:(n==='to'?t:tp); form.appendChild(inp);
    });
    var act = document.createElement('input'); act.type = 'hidden'; act.name = 'action'; act.value = 'export_pdf'; form.appendChild(act);
    document.body.appendChild(form); form.submit(); form.remove();
}

function esc(s){ if(!s) return ''; var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>
</body>
</html>
