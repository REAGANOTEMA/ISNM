<?php
// ISNM Bursar Payroll Management System
// Professional payroll management for bursar

require_once __DIR__ . '/../includes/staff_dashboard_access.php';

function currency($n) { return 'UGX ' . number_format((float)$n, 0); }

$ctx = bootstrapStaffDashboard(['bursar', 'payroll', 'finance']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';

$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
if ($staff_conn) $staff_conn->set_charset("utf8mb4");
if ($students_conn) $students_conn->set_charset("utf8mb4");

// Get user information from session
$staff_id = $_SESSION['user_id'] ?? 0;
$staff_email = $_SESSION['email'] ?? '';
$staff_name = $_SESSION['full_name'] ?? '';

// Handle payroll actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_staff_salary':
            handleAddStaffSalary();
            break;
        case 'update_staff_salary':
            handleUpdateStaffSalary();
            break;
        case 'process_payroll':
            handleProcessPayroll();
            break;
        case 'generate_payslips':
            handleGeneratePayslips();
            break;
    }
}

function handleAddStaffSalary() {
    global $ctx;
    $staff_id = (int)($_POST['staff_id'] ?? 0);
    $basic_salary = (float)($_POST['basic_salary'] ?? 0);
    $allowances = (float)($_POST['allowances'] ?? 0);
    $deductions = (float)(($_POST['nssf_tax'] ?? 0) + ($_POST['paye_tax'] ?? 0));
    $net_salary = $basic_salary + $allowances - $deductions;
    $overtime_rate = (float)($_POST['overtime_rate'] ?? 0);
    $nssf_tax_v = (float)($_POST['nssf_tax'] ?? 0);
    $paye_tax_v = (float)($_POST['paye_tax'] ?? 0);
    $effective_date = $ctx['staff']->real_escape_string($_POST['effective_date'] ?? date('Y-m-d'));
    $uid = (int)($_SESSION['user_id'] ?? 0);

    $stmt = $ctx['staff']->prepare("INSERT INTO staff_salaries (staff_id, basic_salary, allowances, overtime_rate, nssf_tax, paye_tax, effective_date, created_by, deductions, net_salary, status) VALUES (?,?,?,?,?,?,?,?,?,?,'Active')");
    if (!$stmt) { $_SESSION['error']='Prepare: '.$ctx['staff']->error; header("Location: bursar-payroll.php"); exit; }
    $stmt->bind_param("idddddsidd", $staff_id, $basic_salary, $allowances, $overtime_rate, $nssf_tax_v, $paye_tax_v, $effective_date, $uid, $deductions, $net_salary);
    $stmt->execute() ? $_SESSION['success']='Staff salary added.' : $_SESSION['error']='Failed: '.$stmt->error;
    $stmt->close();
    header("Location: bursar-payroll.php"); exit();
}

function handleUpdateStaffSalary() {
    global $ctx;
    $salary_id = (int)($_POST['salary_id'] ?? 0);
    $basic_salary = (float)($_POST['basic_salary'] ?? 0);
    $allowances = (float)($_POST['allowances'] ?? 0);
    $deductions = (float)(($_POST['nssf_tax'] ?? 0) + ($_POST['paye_tax'] ?? 0));
    $net_salary = $basic_salary + $allowances - $deductions;
    $overtime_rate = (float)($_POST['overtime_rate'] ?? 0);
    $nssf_tax_v = (float)($_POST['nssf_tax'] ?? 0);
    $paye_tax_v = (float)($_POST['paye_tax'] ?? 0);
    $effective_date = $ctx['staff']->real_escape_string($_POST['effective_date'] ?? date('Y-m-d'));

    $stmt = $ctx['staff']->prepare("UPDATE staff_salaries SET basic_salary=?, allowances=?, overtime_rate=?, nssf_tax=?, paye_tax=?, effective_date=?, deductions=?, net_salary=? WHERE id=?");
    if (!$stmt) { $_SESSION['error']='Prepare: '.$ctx['staff']->error; header("Location: bursar-payroll.php"); exit; }
    $stmt->bind_param("ddddddsddi", $basic_salary, $allowances, $overtime_rate, $nssf_tax_v, $paye_tax_v, $effective_date, $deductions, $net_salary, $salary_id);
    $stmt->execute() ? $_SESSION['success']='Salary updated.' : $_SESSION['error']='Update failed: '.$stmt->error;
    $stmt->close();
    header("Location: bursar-payroll.php"); exit();
}

function handleProcessPayroll() {
    global $ctx;
    $month = (int)($_POST['month'] ?? 0);
    $year = (int)($_POST['year'] ?? 0);
    if ($month < 1 || $month > 12 || $year < 2000) { $_SESSION['error']='Invalid period.'; header("Location: bursar-payroll.php"); exit(); }

    $staffDb = $ctx['staff'];
    $salaries = $staffDb->query("SELECT s.id, s.full_name, ss.basic_salary, ss.allowances, ss.nssf_tax, ss.paye_tax, ss.deductions FROM staff s LEFT JOIN staff_salaries ss ON s.id=ss.staff_id AND (ss.status='Active' OR ss.status IS NULL) WHERE s.status='Active'");
    if (!$salaries) { $_SESSION['error']='Query failed'; header("Location: bursar-payroll.php"); exit(); }

    $processed = 0;
    $uid = (int)($_SESSION['user_id'] ?? 0);
    while ($s = $salaries->fetch_assoc()) {
        $basic = (float)($s['basic_salary'] ?? 0);
        $allw = (float)($s['allowances'] ?? 0);
        $nssf = (float)($s['nssf_tax'] ?? 0);
        $paye = (float)($s['paye_tax'] ?? 0);
        $ded  = (float)($s['deductions'] ?? 0);
        $gross      = $basic + $allw;
        $net        = $gross - $nssf - $paye - $ded;
        $total_ded  = $ded + $nssf + $paye;
        $sid        = $s['id'];

        $stmt = $staffDb->prepare("INSERT INTO payroll_records (staff_id, month, year, gross_salary, total_allowances, total_deductions, nssf_tax, paye_tax, net_salary, net_payment, processed_by, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,'Processed') ON DUPLICATE KEY UPDATE gross_salary=VALUES(gross_salary), net_salary=VALUES(net_salary), status='Processed'");
        if (!$stmt) continue;
        $stmt->bind_param("iiidddddddi", $sid, $month, $year, $gross, $allw, $total_ded, $nssf, $paye, $net, $net, $uid);
        if ($stmt->execute()) $processed++;
        $stmt->close();
    }
    $_SESSION['success'] = "Payroll processed: $processed staff members.";
    header("Location: bursar-payroll.php"); exit();
}

function handleGeneratePayslips() {
    global $ctx;
    $month = (int)($_POST['month'] ?? 0);
    $year = (int)($_POST['year'] ?? 0);
    if ($month < 1 || $month > 12 || $year < 2000) { $_SESSION['error']='Invalid period.'; header("Location: bursar-payroll.php"); exit(); }

    $staffDb = $ctx['staff'];
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $result = $staffDb->query("SELECT pr.*, s.full_name, s.email FROM payroll_records pr JOIN staff s ON pr.staff_id=s.id WHERE pr.month=$month AND pr.year=$year ORDER BY s.full_name");
    if (!$result) { $_SESSION['error']='Query failed'; header("Location: bursar-payroll.php"); exit(); }

    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $content = generatePayslipContent($row);
        $title   = $staffDb->real_escape_string('Payslip - '.$row['full_name'].' - '.date('F', mktime(0,0,0,$month,1)).' '.$year);
        $code    = 'PAY_'.uniqid();
        $sid2    = $row['staff_id'];
        $gsal    = $row['gross_salary'];
        $nsal    = $row['net_salary'];
        $stmt = $staffDb->prepare("INSERT INTO generated_documents (document_type, staff_id, document_title, document_content, access_code, document_description, month, year, gross_salary, net_pay, generated_by, generation_date) VALUES ('Payslip',?,?,?,?,?,?,?,?,?,?,NOW())");
        if (!$stmt) continue;
        $stmt->bind_param("issssiiddi", $sid2, $title, $content, $code, $title, $month, $year, $gsal, $nsal, $uid);
        if ($stmt->execute()) $count++;
        $stmt->close();
    }
    $_SESSION['success'] = "$count payslips generated.";
    header("Location: bursar-payroll.php"); exit();
}

function generatePayslipContent($p) {
    $basic = ($p['gross_salary'] ?? 0) - ($p['total_allowances'] ?? 0);
    $netPmt = $p['net_payment'] ?? $p['net_salary'] ?? 0;
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Payslip | ISNM</title>
<style>
body{font-family:Arial,sans-serif;margin:20px;color:#333}.payslip{max-width:800px;margin:0 auto;padding:30px;border:2px solid #1a237e;border-radius:10px}
.header{text-align:center;padding-bottom:20px;border-bottom:3px solid #1a237e;margin-bottom:24px}
.header h2{color:#1a237e;margin:0 0 4px}.header h3{color:#283593;margin:0 0 8px}
.school-info{text-align:center;font-size:13px;color:#555;margin-bottom:20px}
table{width:100%;border-collapse:collapse;margin-bottom:20px}
th,td{border:1px solid #ccc;padding:10px;text-align:left;font-size:13px}
th{background:#1a237e;color:#fff;font-weight:600;text-align:center}
.total-row{font-weight:700;background:#e8eaf6;font-size:14px}
.label{color:#666;font-size:12px}.amt{text-align:right;font-weight:600}
.signature{margin-top:30px;text-align:right;font-style:italic;font-size:12px;color:#666;border-top:1px dashed #ccc;padding-top:20px}
.footer{text-align:center;font-size:11px;color:#999;margin-top:20px}
</style></head><body>
<div class="payslip">
<div class="header"><h2>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2><h3>MONTHLY PAYSLIP</h3></div>
<div class="school-info"><p>P.O. Box 418, Iganga, Uganda | Tel: 0782 990 403 | Email: info@isnm.ug</p></div>
<table><thead><tr><th colspan="2">EMPLOYEE DETAILS</th><th>AMOUNT (UGX)</th></tr></thead>
<tbody>
<tr><td colspan="2"><strong>Name:</strong> '.htmlspecialchars($p['full_name']??'N/A').'<br><span class="label">Email: '.htmlspecialchars($p['email']??'').' | Staff ID: '.($p['staff_id']??$p['id']??'--').'</span></td><td class="amt">'.number_format($p['gross_salary']??0,2).'</td></tr>
<tr><td colspan="2"><strong>Basic Salary</strong></td><td class="amt">'.number_format($basic,2).'</td></tr>
<tr><td colspan="2"><strong>Total Allowances</strong></td><td class="amt">'.number_format($p['total_allowances']??0,2).'</td></tr>
<tr><td colspan="2"><strong>NSSF Contribution</strong></td><td class="amt">-'.number_format($p['nssf_tax']??0,2).'</td></tr>
<tr><td colspan="2"><strong>PAYE Tax</strong></td><td class="amt">-'.number_format($p['paye_tax']??0,2).'</td></tr>
<tr><td colspan="2"><strong>Other Deductions</strong></td><td class="amt">-'.number_format(($p['total_deductions']??0)-($p['nssf_tax']??0)-($p['paye_tax']??0),2).'</td></tr>
<tr class="total-row"><td colspan="2">NET PAY</td><td class="amt">'.number_format($p['net_salary']??0,2).'</td></tr>
</tbody></table>
<div class="signature">
<p><strong>Period:</strong> '.date('F',mktime(0,0,0,$p['month']??1,1)).' '.($p['year']??'').'</p>
<p><strong>Generated:</strong> '.date('Y-m-d H:i').' | <strong>By:</strong> '.htmlspecialchars($_SESSION['full_name']??'Bursar').'</p>
<p><em>Electronically generated payslip — valid without signature.</em></p>
</div>
<div class="footer">"Chosen to Serve, Disciplined Mind for Health Action"</div>
</div></body></html>';
}

$current_month = date('m');
$current_year = date('Y');
$payroll_count = 0;
$payroll_total = 0;
try {
    if ($staff_conn) {
        $stmt = $staff_conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(net_salary),0) as total_net FROM payroll_records WHERE month = ? AND year = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $current_month, $current_year);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($d = $r->fetch_assoc()) { $payroll_count = $d['total']; $payroll_total = $d['total_net']; }
            $stmt->close();
        }
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.stat-card-mini{background:#fff;border-radius:12px;padding:20px;border:1px solid #e5e7eb;border-left:4px solid #1a237e;transition:all .2s}
.stat-card-mini:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.stat-card-mini .num{font-size:1.6rem;font-weight:700;color:#1a237e;margin:0}
.stat-card-mini .lbl{font-size:13px;color:#6b7280;margin:0}
.form-card{background:#fff;border-radius:12px;border:1px solid #e5e7eb;transition:all .2s;height:100%}
.form-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.form-card .card-hd{background:#f8fafc;padding:14px 20px;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;font-weight:600;color:#1a237e;font-size:14px}
.form-card .card-bd{padding:20px}
.form-card label{font-size:13px;color:#4b5563;margin-bottom:4px;font-weight:500}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="ma content-section dashboard-section active" data-section="payroll" style="margin-left:270px;padding:24px">
    <div class="ph mb-4">
        <div><h1><i class="fas fa-money-check-alt me-2"></i>Payroll Management</h1><p class="text-muted">Staff salaries, payroll processing & payslip generation</p></div>
        <a href="school-bursar.php" class="bo btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    <?php if ($msg = $_SESSION['success'] ?? ''): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($msg) ?></div><?php unset($_SESSION['success']); endif; ?>
    <?php if ($err = $_SESSION['error'] ?? ''): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($err) ?></div><?php unset($_SESSION['error']); endif; ?>

    <!-- KPI row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="stat-card-mini" style="border-left-color:#1a237e"><p class="num"><?= $payroll_count ?></p><p class="lbl">Current Month Staff</p></div></div>
        <div class="col-md-3 col-6"><div class="stat-card-mini" style="border-left-color:#16a34a"><p class="num">UGX <?= number_format($payroll_total) ?></p><p class="lbl">Total Net Pay (This Month)</p></div></div>
        <div class="col-md-3 col-6"><div class="stat-card-mini" style="border-left-color:#0891b2"><p class="num"><?= date('F Y') ?></p><p class="lbl">Current Period</p></div></div>
        <div class="col-md-3 col-6"><div class="stat-card-mini" style="border-left-color:#d97706"><p class="num"><?= date('d/m/Y') ?></p><p class="lbl">Processing Date</p></div></div>
    </div>

    <!-- Main forms -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="form-card">
                <div class="card-hd"><i class="fas fa-plus-circle me-2"></i>Add Staff Salary</div>
                <div class="card-bd">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="staff_id">Staff Member *</label>
                            <select class="form-control fc" id="staff_id" name="staff_id" required>
                                <option value="">-- Select --</option>
                                <?php
                                $staff_result = $staff_conn->query("SELECT id, full_name, position FROM staff WHERE status='Active' ORDER BY full_name");
                                if ($staff_result) while ($s = $staff_result->fetch_assoc())
                                    echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['full_name']).' ('.htmlspecialchars($s['position']).')</option>';
                                ?>
                            </select>
                        </div>
                        <div class="mb-3"><label>Basic Salary (UGX) *</label><input type="number" name="basic_salary" class="form-control fc" step="0.01" required></div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label>Allowances</label><input type="number" name="allowances" class="form-control fc" step="0.01" value="0"></div>
                            <div class="col-6"><label>Overtime Rate/hr</label><input type="number" name="overtime_rate" class="form-control fc" step="0.01" value="0"></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label>NSSF Tax</label><input type="number" name="nssf_tax" class="form-control fc" step="0.01" value="0"></div>
                            <div class="col-6"><label>PAYE Tax</label><input type="number" name="paye_tax" class="form-control fc" step="0.01" value="0"></div>
                        </div>
                        <div class="mb-3"><label>Effective Date</label><input type="date" name="effective_date" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                        <button type="submit" name="action" value="add_staff_salary" class="btn bb w-100"><i class="fas fa-save me-1"></i>Add Salary</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-card">
                <div class="card-hd"><i class="fas fa-calculator me-2"></i>Process Payroll</div>
                <div class="card-bd">
                    <form method="POST">
                        <div class="mb-3"><label>Month *</label>
                            <select class="form-control fc" name="month" required>
                                <option value="">-- Select --</option>
                                <?php for ($m=1;$m<=12;$m++): ?>
                                    <option value="<?= str_pad($m,2,'0',STR_PAD_LEFT) ?>" <?= $m==date('m')?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label>Year *</label>
                            <select class="form-control fc" name="year" required>
                                <?php for ($y=date('Y');$y>=date('Y')-5;$y--): ?>
                                    <option value="<?= $y ?>" <?= $y==date('Y')?'selected':'' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="alert alert-info small py-2 mb-3"><i class="fas fa-info-circle me-1"></i>Processes payroll for all active staff with salary records.</div>
                        <button type="submit" name="action" value="process_payroll" class="btn bb w-100"><i class="fas fa-play me-1"></i>Process Payroll</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-card">
                <div class="card-hd"><i class="fas fa-file-invoice me-2"></i>Generate Payslips</div>
                <div class="card-bd">
                    <form method="POST">
                        <div class="mb-3"><label>Month *</label>
                            <select class="form-control fc" name="month" required>
                                <option value="">-- Select --</option>
                                <?php for ($m=1;$m<=12;$m++): ?>
                                    <option value="<?= str_pad($m,2,'0',STR_PAD_LEFT) ?>"><?= date('F',mktime(0,0,0,$m,1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label>Year *</label>
                            <select class="form-control fc" name="year" required>
                                <?php for ($y=date('Y');$y>=date('Y')-5;$y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="alert alert-info small py-2 mb-3"><i class="fas fa-info-circle me-1"></i>Generates HTML payslips for processed payroll records.</div>
                        <button type="submit" name="action" value="generate_payslips" class="btn bb w-100"><i class="fas fa-file me-1"></i>Generate Payslips</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Salary Records Table -->
    <div class="cc mt-4">
        <div class="ch"><i class="fas fa-list me-2"></i>Staff Salary Records</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Staff</th><th>Position</th><th>Basic</th><th>Allowances</th><th>Deductions</th><th>Net</th><th>Effective</th></tr></thead>
                    <tbody>
<?php
$salRows = '';
try {
    if ($staff_conn) {
        $r = $staff_conn->query("SELECT ss.*, s.full_name, s.position FROM staff_salaries ss LEFT JOIN staff s ON ss.staff_id=s.id WHERE ss.status='Active' ORDER BY ss.created_at DESC LIMIT 30");
        if ($r) while ($p = $r->fetch_assoc()) {
            $totDed = ($p['deductions']??0) + ($p['nssf_tax']??0) + ($p['paye_tax']??0);
            $net = ($p['basic_salary']??0) + ($p['allowances']??0) - $totDed;
            $salRows .= '<tr><td>'.htmlspecialchars($p['full_name']??'Staff #'.$p['staff_id']).'</td><td class="small text-muted">'.htmlspecialchars($p['position']??'-').'</td><td>'.currency($p['basic_salary']??0).'</td><td>'.currency($p['allowances']??0).'</td><td>'.currency($totDed).'</td><td><strong>'.currency($net).'</strong></td><td class="small">'.htmlspecialchars($p['effective_date']??'-').'</td></tr>';
        }
    }
} catch (Exception $e) {}
echo $salRows ?: '<tr><td colspan="7" class="text-center text-muted py-3">No active salary records.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>

