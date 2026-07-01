<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/financial_functions.php';

$ctx = bootstrapStaffDashboard(['bursar', 'accountant', 'finance', 'director_finance']);
$staff = $ctx['staff'];
$students = $ctx['students'];
$website = $ctx['website'];
$user = $ctx['user'];

$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschoolofl_students_db';

$ajax = $_GET['ajax'] ?? '';

function currency($n) { return 'UGX ' . number_format((float)$n, 0); }
function badge($s) {
    $m = ['verified'=>'success','pending'=>'warning','fully_paid'=>'success','partially_paid'=>'info','unpaid'=>'secondary','overdue'=>'danger','approved'=>'success','draft'=>'secondary'];
    $c = $m[strtolower($s)] ?? 'secondary';
    return '<span class="badge bg-'.$c.'">'.htmlspecialchars($s).'</span>';
}

// ── AJAX endpoints ──
if ($ajax) {
    header('Content-Type: application/json');
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-d');
    $result = ['headers' => [], 'rows' => [], 'total' => 0, 'chart_labels' => [], 'chart_values' => []];

    try { if (!$staff) throw new Exception('no db');

    if ($ajax === 'revenue_summary') {
        $result['headers'] = ['Month', 'Collections', 'Invoices Generated', 'Outstanding', 'Net Revenue'];
        $stmt = $staff->prepare("SELECT DATE_FORMAT(fp.payment_date,'%Y-%m') AS m, COALESCE(SUM(fp.amount_paid),0) AS collected FROM fee_payments fp WHERE DATE(fp.payment_date) BETWEEN ? AND ? AND fp.status='verified' GROUP BY m ORDER BY m");
        $invoices_q = $staff->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') AS m, COUNT(*) AS inv_count, COALESCE(SUM(total_fees),0) AS total_billed FROM student_fee_accounts WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY m ORDER BY m");
        if ($stmt) { $stmt->bind_param('ss', $from, $to); $stmt->execute(); $r = $stmt->get_result(); $collected = []; if ($r) while ($row = $r->fetch_assoc()) $collected[$row['m']] = (float)$row['collected']; $stmt->close(); }
        if ($invoices_q) { $invoices_q->bind_param('ss', $from, $to); $invoices_q->execute(); $r2 = $invoices_q->get_result(); $inv_data = []; if ($r2) while ($row = $r2->fetch_assoc()) $inv_data[$row['m']] = ['count'=>$row['inv_count'],'total'=>$row['total_billed']]; $invoices_q->close(); }
        $all_months = array_unique(array_merge(array_keys($collected), array_keys($inv_data)));
        sort($all_months);
        foreach ($all_months as $m) {
            $coll = $collected[$m] ?? 0;
            $inv = $inv_data[$m] ?? ['count'=>0,'total'=>0];
            $outstanding = max(0, $inv['total'] - $coll);
            $result['rows'][] = [$m, currency($coll), (int)$inv['count'] . ' / ' . currency($inv['total']), currency($outstanding), currency($coll - $outstanding)];
            $result['total'] += $coll;
            $result['chart_labels'][] = $m;
            $result['chart_values'][] = $coll;
        }
    } elseif ($ajax === 'expense_breakdown') {
        $result['headers'] = ['Category', 'Budgeted', 'Spent', 'Remaining', '% Used'];
        $cats = [
            ['name'=>'Salaries & Wages', 'budgeted'=>850000000, 'table'=>'bursar_payroll'],
            ['name'=>'Utilities & Services', 'budgeted'=>120000000, 'table'=>'expenditures'],
            ['name'=>'Supplies & Materials', 'budgeted'=>95000000, 'table'=>'expenditures'],
            ['name'=>'Maintenance & Repairs', 'budgeted'=>45000000, 'table'=>'expenditures'],
            ['name'=>'Academic Programs', 'budgeted'=>200000000, 'table'=>'expenditures'],
            ['name'=>'Administrative', 'budgeted'=>75000000, 'table'=>'expenditures'],
            ['name'=>'Student Welfare', 'budgeted'=>60000000, 'table'=>'expenditures'],
        ];
        foreach ($cats as $cat) {
            $spent = 0;
            try {
                if ($cat['table'] === 'expenditures') {
                    $stmt = $staff->prepare("SELECT COALESCE(SUM(amount),0) AS t FROM expenditures WHERE category=? AND DATE(expense_date) BETWEEN ? AND ?");
                    if ($stmt) { $stmt->bind_param('sss', $cat['name'], $from, $to); $stmt->execute(); $r = $stmt->get_result(); if ($row = $r->fetch_assoc()) $spent = (float)$row['t']; $stmt->close(); }
                } elseif ($cat['table'] === 'bursar_payroll') {
                    $r = $staff->query("SELECT COALESCE(SUM(net_pay),0) AS t FROM bursar_payroll WHERE DATE(pay_date) BETWEEN '$from' AND '$to'");
                    if ($r) $spent = (float)$r->fetch_assoc()['t'];
                }
            } catch (Exception $e) {}
            $remaining = max(0, $cat['budgeted'] - $spent);
            $pct = $cat['budgeted'] > 0 ? round(($spent / $cat['budgeted']) * 100, 1) : 0;
            $result['rows'][] = [$cat['name'], currency($cat['budgeted']), currency($spent), currency($remaining), $pct . '%'];
            $result['total'] += $spent;
            $result['chart_labels'][] = $cat['name'];
            $result['chart_values'][] = $spent;
        }
    } elseif ($ajax === 'budget_vs_actual') {
        $result['headers'] = ['Department', 'Budget', 'Actual Spend', 'Variance', 'Status'];
        $depts = [
            ['name'=>'Academic Affairs', 'budget'=>300000000],
            ['name'=>'Finance & Admin', 'budget'=>150000000],
            ['name'=>'Student Affairs', 'budget'=>120000000],
            ['name'=>'ICT Department', 'budget'=>80000000],
            ['name'=>'Health Services', 'budget'=>100000000],
            ['name'=>'Library', 'budget'=>60000000],
        ];
        foreach ($depts as $d) {
            $actual = 0;
            try {
                $r = $staff->query("SELECT COALESCE(SUM(amount),0) AS t FROM expenditures WHERE department='" . $staff->real_escape_string($d['name']) . "' AND DATE(expense_date) BETWEEN '$from' AND '$to'");
                if ($r) $actual = (float)$r->fetch_assoc()['t'];
            } catch (Exception $e) {}
            $variance = $d['budget'] - $actual;
            $status = $variance >= 0 ? 'On Track' : 'Over Budget';
            $result['rows'][] = [$d['name'], currency($d['budget']), currency($actual), currency($variance), $status];
            $result['chart_labels'][] = $d['name'];
            $result['chart_values'][] = $actual;
        }
    } elseif ($ajax === 'monthly_trend') {
        $result['headers'] = ['Month', 'Revenue', 'Expenses', 'Net Income'];
        $stmt = $staff->prepare("SELECT DATE_FORMAT(fp.payment_date,'%Y-%m') AS m, COALESCE(SUM(fp.amount_paid),0) AS rev FROM fee_payments fp WHERE DATE(fp.payment_date) BETWEEN ? AND ? AND fp.status='verified' GROUP BY m ORDER BY m");
        $rev = []; if ($stmt) { $stmt->bind_param('ss', $from, $to); $stmt->execute(); $r = $stmt->get_result(); if ($r) while ($row = $r->fetch_assoc()) $rev[$row['m']] = (float)$row['rev']; $stmt->close(); }
        $exp = []; $stmt2 = $staff->prepare("SELECT DATE_FORMAT(expense_date,'%Y-%m') AS m, COALESCE(SUM(amount),0) AS exp FROM expenditures WHERE DATE(expense_date) BETWEEN ? AND ? GROUP BY m ORDER BY m");
        if ($stmt2) { $stmt2->bind_param('ss', $from, $to); $stmt2->execute(); $r2 = $stmt2->get_result(); if ($r2) while ($row = $r2->fetch_assoc()) $exp[$row['m']] = (float)$row['exp']; $stmt2->close(); }
        $months = array_unique(array_merge(array_keys($rev), array_keys($exp))); sort($months);
        foreach ($months as $m) {
            $r_val = $rev[$m] ?? 0; $e_val = $exp[$m] ?? 0;
            $result['rows'][] = [$m, currency($r_val), currency($e_val), currency($r_val - $e_val)];
            $result['chart_labels'][] = $m;
            $result['chart_values'][] = $r_val - $e_val;
        }
    } elseif ($ajax === 'top_debtors') {
        $result['headers'] = ['Student ID', 'Name', 'Program', 'Total Fees', 'Paid', 'Balance', 'Days Overdue'];
        $r = $staff->query("SELECT sfa.*, s.first_name, s.surname, s.program, DATEDIFF(CURDATE(), sfa.due_date) AS days_overdue FROM student_fee_accounts sfa LEFT JOIN `{$students_db}`.students s ON sfa.student_id = s.student_id WHERE sfa.status NOT IN ('fully_paid','cancelled') ORDER BY sfa.balance DESC LIMIT 50");
        if ($r) while ($row = $r->fetch_assoc()) {
            $overdue = max(0, (int)($row['days_overdue'] ?? 0));
            $result['rows'][] = [$row['student_id'], htmlspecialchars(($row['surname']??'').' '.($row['first_name']??'')), htmlspecialchars($row['program']??'-'), currency($row['total_fees']), currency($row['amount_paid']), '<strong class="text-danger">'.currency($row['balance']).'</strong>', $overdue . ' days'];
            $result['total'] += (float)$row['balance'];
        }
    }

    } catch (Exception $e) { error_log('fin-reports ajax: '.$e->getMessage()); }
    echo json_encode($result);
    exit;
}

// ── Summary Stats ──
$ytd_revenue = 0; $month_revenue = 0; $total_outstanding = 0; $ytd_expenses = 0; $total_invoices = 0; $collection_rate = 0;
try {
    if ($staff) {
        $r = $staff->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE YEAR(payment_date)=YEAR(CURDATE()) AND status='verified'"); if ($r) $ytd_revenue = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status='verified'"); if ($r) $month_revenue = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COALESCE(SUM(balance),0) AS t FROM student_fee_accounts WHERE status NOT IN ('fully_paid','cancelled')"); if ($r) $total_outstanding = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COALESCE(SUM(amount),0) AS t FROM expenditures WHERE YEAR(expense_date)=YEAR(CURDATE())"); if ($r) $ytd_expenses = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COUNT(*) AS c FROM student_fee_accounts WHERE YEAR(created_at)=YEAR(CURDATE())"); if ($r) $total_invoices = (int)$r->fetch_assoc()['c'];
        if ($ytd_revenue + $total_outstanding > 0) $collection_rate = round(($ytd_revenue / ($ytd_revenue + $total_outstanding)) * 100, 1);
    }
} catch (Exception $e) {}

$pageTitle = 'Financial Reports & Analytics';
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
.kpi-ring { width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; font-weight:700; color:#fff; margin:0 auto 12px; }
.kpi-ring.primary { background:linear-gradient(135deg, var(--isnm-blue), var(--isnm-light-blue)); }
.kpi-ring.success { background:linear-gradient(135deg, var(--isnm-green), #10b981); }
.kpi-ring.warning { background:linear-gradient(135deg, var(--isnm-gold), #f59e0b); }
.kpi-ring.danger { background:linear-gradient(135deg, #dc2626, #ef4444); }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="ma content-section" style="margin-left:270px;padding:24px">

    <div class="ph">
        <div>
            <h1><i class="fas fa-chart-bar me-2"></i>Financial Reports & Analytics</h1>
            <p>Revenue analysis, expense tracking, budget monitoring &amp; financial summaries</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:13px"><i class="far fa-clock me-1"></i><span id="currentDate"></span></span>
        </div>
    </div>

    <!-- ── KPI Cards ── -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6 text-center">
            <div class="content-section" style="padding:20px">
                <div class="kpi-ring primary"><?= number_format($collection_rate) ?>%</div>
                <h6 style="margin:0;font-size:13px;color:#666">Collection Rate</h6>
            </div>
        </div>
        <div class="col-md-2 col-6 text-center">
            <div class="content-section" style="padding:20px">
                <div class="kpi-ring success"><?= currency($month_revenue) ?></div>
                <h6 style="margin:0;font-size:13px;color:#666">This Month Revenue</h6>
            </div>
        </div>
        <div class="col-md-2 col-6 text-center">
            <div class="content-section" style="padding:20px">
                <div class="kpi-ring primary"><?= currency($ytd_revenue) ?></div>
                <h6 style="margin:0;font-size:13px;color:#666">YTD Revenue</h6>
            </div>
        </div>
        <div class="col-md-2 col-6 text-center">
            <div class="content-section" style="padding:20px">
                <div class="kpi-ring danger"><?= currency($ytd_expenses) ?></div>
                <h6 style="margin:0;font-size:13px;color:#666">YTD Expenses</h6>
            </div>
        </div>
        <div class="col-md-2 col-6 text-center">
            <div class="content-section" style="padding:20px">
                <div class="kpi-ring warning"><?= currency($total_outstanding) ?></div>
                <h6 style="margin:0;font-size:13px;color:#666">Outstanding Balance</h6>
            </div>
        </div>
        <div class="col-md-2 col-6 text-center">
            <div class="content-section" style="padding:20px">
                <div class="kpi-ring success"><?= number_format($total_invoices) ?></div>
                <h6 style="margin:0;font-size:13px;color:#666">YTD Invoices</h6>
            </div>
        </div>
    </div>

    <!-- ── Tab Navigation ── -->
    <div class="tab-nav">
        <button class="tn active" data-tab="tab_revenue"><i class="fas fa-chart-line me-1"></i>Revenue Summary</button>
        <button class="tn" data-tab="tab_expenses"><i class="fas fa-receipt me-1"></i>Expense Breakdown</button>
        <button class="tn" data-tab="tab_budget"><i class="fas fa-balance-scale me-1"></i>Budget vs Actual</button>
        <button class="tn" data-tab="tab_trend"><i class="fas fa-chart-area me-1"></i>Monthly Trend</button>
        <button class="tn" data-tab="tab_debtors"><i class="fas fa-user-clock me-1"></i>Top Debtors</button>
        <button class="tn" data-tab="tab_exports"><i class="fas fa-download me-1"></i>Export Reports</button>
    </div>

    <!-- ══════════ Revenue Summary ══════════ -->
    <div id="tab_revenue" class="tab-content active">
        <div class="row g-4">
            <div class="col-md-8">
                <div class="content-section">
                    <h5><i class="fas fa-chart-line me-2"></i>Revenue Summary</h5>
                    <form onsubmit="event.preventDefault(); loadRevenue()" class="row g-2 mb-3">
                        <div class="col-md-3"><label class="fl">From</label><input type="date" id="revFrom" class="form-control fc" value="<?= date('Y-m-01') ?>"></div>
                        <div class="col-md-3"><label class="fl">To</label><input type="date" id="revTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn bb w-100"><i class="fas fa-search me-1"></i>Generate</button></div>
                    </form>
                    <div id="revOutput"><div class="text-center text-muted py-4">Select period and click Generate.</div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-section">
                    <h5><i class="fas fa-chart-pie me-2"></i>Revenue Distribution</h5>
                    <canvas id="revPieChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Expense Breakdown ══════════ -->
    <div id="tab_expenses" class="tab-content">
        <div class="row g-4">
            <div class="col-md-8">
                <div class="content-section">
                    <h5><i class="fas fa-receipt me-2"></i>Expense Breakdown by Category</h5>
                    <form onsubmit="event.preventDefault(); loadExpenses()" class="row g-2 mb-3">
                        <div class="col-md-3"><label class="fl">From</label><input type="date" id="expFrom" class="form-control fc" value="<?= date('Y-m-01') ?>"></div>
                        <div class="col-md-3"><label class="fl">To</label><input type="date" id="expTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn bb w-100"><i class="fas fa-search me-1"></i>Generate</button></div>
                    </form>
                    <div id="expOutput"><div class="text-center text-muted py-4">Select period and click Generate.</div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-section">
                    <h5><i class="fas fa-chart-bar me-2"></i>Spending Distribution</h5>
                    <canvas id="expBarChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Budget vs Actual ══════════ -->
    <div id="tab_budget" class="tab-content">
        <div class="row g-4">
            <div class="col-md-8">
                <div class="content-section">
                    <h5><i class="fas fa-balance-scale me-2"></i>Budget vs Actual by Department</h5>
                    <form onsubmit="event.preventDefault(); loadBudget()" class="row g-2 mb-3">
                        <div class="col-md-3"><label class="fl">From</label><input type="date" id="budFrom" class="form-control fc" value="<?= date('Y-m-01') ?>"></div>
                        <div class="col-md-3"><label class="fl">To</label><input type="date" id="budTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn bb w-100"><i class="fas fa-search me-1"></i>Generate</button></div>
                    </form>
                    <div id="budOutput"><div class="text-center text-muted py-4">Select period and click Generate.</div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-section">
                    <h5><i class="fas fa-chart-bar me-2"></i>Budget Comparison</h5>
                    <canvas id="budChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Monthly Trend ══════════ -->
    <div id="tab_trend" class="tab-content">
        <div class="content-section">
            <h5><i class="fas fa-chart-area me-2"></i>Monthly Revenue vs Expenses Trend</h5>
            <form onsubmit="event.preventDefault(); loadTrend()" class="row g-2 mb-3">
                <div class="col-md-2"><label class="fl">From</label><input type="date" id="trdFrom" class="form-control fc" value="<?= date('Y-m-01') ?>"></div>
                <div class="col-md-2"><label class="fl">To</label><input type="date" id="trdTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn bb w-100"><i class="fas fa-search me-1"></i>Generate</button></div>
            </form>
            <canvas id="trendLineChart" height="250"></canvas>
            <div id="trendTable" class="mt-3"></div>
        </div>
    </div>

    <!-- ══════════ Top Debtors ══════════ -->
    <div id="tab_debtors" class="tab-content">
        <div class="content-section">
            <h5><i class="fas fa-user-clock me-2"></i>Top Outstanding Debtors</h5>
            <div class="d-flex gap-2 mb-3">
                <button class="btn bo btn-sm" onclick="loadDebtors()"><i class="fas fa-sync me-1"></i>Refresh</button>
                <button class="btn bo btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
            </div>
            <div id="debtorsOutput"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
        </div>
    </div>

    <!-- ══════════ Export Reports ══════════ -->
    <div id="tab_exports" class="tab-content">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="content-section text-center" style="cursor:pointer" onclick="exportReport('revenue')">
                    <div class="report-icon"><i class="fas fa-chart-line"></i></div>
                    <h5>Revenue Report</h5>
                    <p class="text-muted mb-0">Monthly and quarterly revenue summaries</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-section text-center" style="cursor:pointer" onclick="exportReport('expense')">
                    <div class="report-icon" style="background:linear-gradient(135deg, var(--isnm-green), #10b981)"><i class="fas fa-receipt"></i></div>
                    <h5>Expense Report</h5>
                    <p class="text-muted mb-0">Category-wise expense breakdown</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-section text-center" style="cursor:pointer" onclick="exportReport('budget')">
                    <div class="report-icon" style="background:linear-gradient(135deg, var(--isnm-gold), #f59e0b)"><i class="fas fa-balance-scale"></i></div>
                    <h5>Budget vs Actual</h5>
                    <p class="text-muted mb-0">Department-wise budget utilization</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-section text-center" style="cursor:pointer" onclick="exportReport('debtors')">
                    <div class="report-icon" style="background:linear-gradient(135deg, #dc2626, #ef4444)"><i class="fas fa-user-clock"></i></div>
                    <h5>Debtors Report</h5>
                    <p class="text-muted mb-0">Outstanding balances by student</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-section text-center" style="cursor:pointer" onclick="exportReport('collection')">
                    <div class="report-icon" style="background:linear-gradient(135deg, #0891b2, #22d3ee)"><i class="fas fa-money-bill-wave"></i></div>
                    <h5>Collection Report</h5>
                    <p class="text-muted mb-0">Daily and weekly collection analysis</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-section text-center" style="cursor:pointer" onclick="exportReport('comprehensive')">
                    <div class="report-icon" style="background:linear-gradient(135deg, #7c3aed, #a78bfa)"><i class="fas fa-file-alt"></i></div>
                    <h5>Comprehensive Report</h5>
                    <p class="text-muted mb-0">Full financial overview with all metrics</p>
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
var charts = {};

document.querySelectorAll('.tn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('.tn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function(t){ t.classList.remove('active'); });
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
    });
});

function renderTable(headers, rows, total, totalLabel){
    var h = '<div class="table-responsive"><table class="table tb"><thead><tr>';
    headers.forEach(function(hd){ h += '<th>'+hd+'</th>'; });
    h += '</tr></thead><tbody>';
    rows.forEach(function(r){ h += '<tr>'; r.forEach(function(c){ h += '<td>'+c+'</td>'; }); h += '</tr>'; });
    if(total !== undefined && total !== 0) h += '<tr class="fw-bold table-light"><td colspan="'+(headers.length-1)+'" class="text-end">'+totalLabel+'</td><td>'+Number(total).toLocaleString()+'</td></tr>';
    h += '</tbody></table></div>';
    return h;
}

function makePie(id, labels, values){
    if(charts[id]) charts[id].destroy();
    var ctx = document.getElementById(id);
    if(!ctx || !labels.length) return;
    var colors = ['#1a237e','#059669','#d97706','#dc2626','#0891b2','#7c3aed','#84cc16','#f97316'];
    charts[id] = new Chart(ctx, {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: values, backgroundColor: colors.slice(0, labels.length), borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } },
            tooltip: { callbacks: { label: function(c){ var t = c.dataset.data.reduce(function(a,b){return a+b;},0); return 'UGX '+Number(c.parsed).toLocaleString()+' ('+ (t>0?((c.parsed/t)*100).toFixed(1):0) +'%)'; } } } } }
    });
}

function makeBar(id, labels, values){
    if(charts[id]) charts[id].destroy();
    var ctx = document.getElementById(id);
    if(!ctx || !labels.length) return;
    var colors = ['#1a237e','#059669','#d97706','#dc2626','#0891b2','#7c3aed','#84cc16','#f97316'];
    charts[id] = new Chart(ctx, {
        type: 'bar',
        data: { labels: labels, datasets: [{ label: 'Amount (UGX)', data: values, backgroundColor: colors.slice(0, labels.length), borderRadius: 6 }] },
        options: { responsive: true, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: function(v){ return (v/1000000).toFixed(0)+'M'; } } } } }
    });
}

function makeLine(id, labels, datasets){
    if(charts[id]) charts[id].destroy();
    var ctx = document.getElementById(id);
    if(!ctx || !labels.length) return;
    charts[id] = new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: { responsive: true, interaction: { intersect: false, mode: 'index' },
            plugins: { tooltip: { callbacks: { label: function(c){ return c.dataset.label+': UGX '+Number(c.parsed.y).toLocaleString(); } } } },
            scales: { y: { beginAtZero: true, ticks: { callback: function(v){ return (v/1000000).toFixed(0)+'M'; } } } } }
    });
}

function loadRevenue(){
    var f = document.getElementById('revFrom').value, t = document.getElementById('revTo').value;
    var out = document.getElementById('revOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('financial-reports.php?ajax=revenue_summary&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-center text-muted py-4">No data for this period.</div>'; return; }
        out.innerHTML = renderTable(d.headers, d.rows, d.total, 'Total Revenue');
        makePie('revPieChart', d.chart_labels, d.chart_values);
    }).catch(function(){ out.innerHTML = '<div class="alert alert-danger">Failed to load report.</div>'; });
}

function loadExpenses(){
    var f = document.getElementById('expFrom').value, t = document.getElementById('expTo').value;
    var out = document.getElementById('expOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('financial-reports.php?ajax=expense_breakdown&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-center text-muted py-4">No data.</div>'; return; }
        out.innerHTML = renderTable(d.headers, d.rows, d.total, 'Total Expenses');
        makeBar('expBarChart', d.chart_labels, d.chart_values);
    }).catch(function(){ out.innerHTML = '<div class="alert alert-danger">Failed to load.</div>'; });
}

function loadBudget(){
    var f = document.getElementById('budFrom').value, t = document.getElementById('budTo').value;
    var out = document.getElementById('budOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('financial-reports.php?ajax=budget_vs_actual&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-center text-muted py-4">No data.</div>'; return; }
        out.innerHTML = renderTable(d.headers, d.rows);
        makeBar('budChart', d.chart_labels, d.chart_values);
    }).catch(function(){ out.innerHTML = '<div class="alert alert-danger">Failed to load.</div>'; });
}

function loadTrend(){
    var f = document.getElementById('trdFrom').value, t = document.getElementById('trdTo').value;
    var tbl = document.getElementById('trendTable');
    tbl.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('financial-reports.php?ajax=monthly_trend&from='+f+'&to='+t)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ tbl.innerHTML = '<div class="text-center text-muted py-4">No data.</div>'; return; }
        tbl.innerHTML = renderTable(d.headers, d.rows);
        makeLine('trendLineChart', d.chart_labels, [
            { label: 'Revenue', data: d.chart_values.map(function(v,i){ return parseFloat(d.rows[i][1].replace(/[^0-9.-]/g,''))||0; }), borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.08)', fill: true, tension: 0.4, pointRadius: 4, borderWidth: 2 },
            { label: 'Expenses', data: d.chart_values.map(function(v,i){ return parseFloat(d.rows[i][2].replace(/[^0-9.-]/g,''))||0; }), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.08)', fill: true, tension: 0.4, pointRadius: 4, borderWidth: 2 }
        ]);
    }).catch(function(){ tbl.innerHTML = '<div class="alert alert-danger">Failed to load trend.</div>'; });
}

function loadDebtors(){
    var out = document.getElementById('debtorsOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('financial-reports.php?ajax=top_debtors')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-center text-muted py-4">No outstanding debtors.</div>'; return; }
        out.innerHTML = renderTable(d.headers, d.rows, d.total, 'Total Outstanding');
    }).catch(function(){ out.innerHTML = '<div class="alert alert-danger">Failed to load.</div>'; });
}

function exportReport(type){
    var form = document.createElement('form');
    form.method = 'POST'; form.action = 'financial-reports.php'; form.target = '_blank';
    var a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='export_report'; form.appendChild(a);
    var b = document.createElement('input'); b.type='hidden'; b.name='report_type'; b.value=type; form.appendChild(b);
    document.body.appendChild(form); form.submit(); form.remove();
}

document.addEventListener('DOMContentLoaded', function(){ loadRevenue(); });
</script>
</body>
</html>
