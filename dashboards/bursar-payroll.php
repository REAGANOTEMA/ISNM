<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/payroll_functions.php';

$ctx = bootstrapStaffDashboard(['school bursar','bursar']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
if ($staff_conn) $staff_conn->set_charset("utf8mb4");

$tab = $_GET['tab'] ?? 'overview';

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staff_conn) {
    $action = $_POST['action'] ?? '';

    // 1. Employee Profile
    if ($action === 'add_employee' || $action === 'update_employee') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $bnk = trim($_POST['bank_name'] ?? '');
        $bac = trim($_POST['bank_account'] ?? '');
        $tin = trim($_POST['tin_number'] ?? '');
        $nss = trim($_POST['nssf_number'] ?? '');
        $stp = $_POST['salary_type'] ?? 'monthly';
        $bs  = (float)($_POST['basic_salary'] ?? 0);
        $sg  = trim($_POST['salary_grade'] ?? '');
        if ($action === 'add_employee') {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_employees (staff_id, bank_name, bank_account, tax_identification, nssf_number, salary_type, basic_salary, salary_grade) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE bank_name=VALUES(bank_name), bank_account=VALUES(bank_account), tax_identification=VALUES(tax_identification), nssf_number=VALUES(nssf_number), salary_type=VALUES(salary_type), basic_salary=VALUES(basic_salary), salary_grade=VALUES(salary_grade)");
            if ($stmt) { $stmt->bind_param('isssssds', $sid, $bnk, $bac, $tin, $nss, $stp, $bs, $sg); $stmt->execute(); $_SESSION['success']='Employee payroll profile saved.'; }
        }
        header('Location: bursar-payroll.php?tab=employees'); exit;
    }

    // 2. Allowance
    if ($action === 'add_allowance') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $at  = trim($_POST['allowance_type'] ?? '');
        $am  = (float)($_POST['amount'] ?? 0);
        $mo  = $_POST['month'] ?? date('Y-m');
        if ($sid && $at) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_allowances (staff_id, allowance_type, amount, month, created_by) VALUES (?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('isdsi', $sid, $at, $am, $mo, $user_id); $stmt->execute(); $_SESSION['success']='Allowance added.'; }
        }
        header('Location: bursar-payroll.php?tab=allowances'); exit;
    }

    // 3. Deduction
    if ($action === 'add_deduction') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $dt  = trim($_POST['deduction_type'] ?? '');
        $am  = (float)($_POST['amount'] ?? 0);
        $mo  = $_POST['month'] ?? date('Y-m');
        if ($sid && $dt) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_deductions (staff_id, deduction_type, amount, month, created_by) VALUES (?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('isdsi', $sid, $dt, $am, $mo, $user_id); $stmt->execute(); $_SESSION['success']='Deduction added.'; }
        }
        header('Location: bursar-payroll.php?tab=deductions'); exit;
    }

    // 4. Overtime
    if ($action === 'add_overtime') {
        $sid  = (int)($_POST['staff_id'] ?? 0);
        $hrs  = (float)($_POST['hours'] ?? 0);
        $rt   = (float)($_POST['rate'] ?? 0);
        $mo   = $_POST['month'] ?? date('Y-m');
        $tot  = $hrs * $rt;
        if ($sid && $hrs > 0) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_overtime (staff_id, hours, rate, total_pay, month, approved_by) VALUES (?,?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('idddsi', $sid, $hrs, $rt, $tot, $mo, $user_id); $stmt->execute(); $_SESSION['success']='Overtime added.'; }
        }
        header('Location: bursar-payroll.php?tab=overtime'); exit;
    }

    // 5. Bonus
    if ($action === 'add_bonus') {
        $sid = (int)($_POST['staff_id'] ?? 0);
        $bt  = trim($_POST['bonus_type'] ?? '');
        $am  = (float)($_POST['amount'] ?? 0);
        $mo  = $_POST['month'] ?? date('Y-m');
        if ($sid && $bt) {
            $stmt = $staff_conn->prepare("INSERT INTO payroll_bonuses (staff_id, bonus_type, amount, month, created_by) VALUES (?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('isdsi', $sid, $bt, $am, $mo, $user_id); $stmt->execute(); $_SESSION['success']='Bonus added.'; }
        }
        header('Location: bursar-payroll.php?tab=bonuses'); exit;
    }

    // 6. Create Payroll Run
    if ($action === 'create_run') {
        $period = trim($_POST['period'] ?? date('Y-m'));
        $desc   = trim($_POST['description'] ?? '');
        $sd     = $_POST['start_date'] ?? null;
        $ed     = $_POST['end_date'] ?? null;
        $stmt = $staff_conn->prepare("INSERT INTO payroll_runs (period, description, start_date, end_date, status, created_by) VALUES (?,?,?,?,'draft',?)");
        if ($stmt) { $stmt->bind_param('ssssi', $period, $desc, $sd, $ed, $user_id); $stmt->execute(); $run_id = $stmt->insert_id; $_SESSION['success']="Payroll run #$run_id created."; }
        header('Location: bursar-payroll.php?tab=run'); exit;
    }

    // 7. Process Payroll Run (calculate all)
    if ($action === 'process_run') {
        $rid = (int)($_POST['run_id'] ?? 0);
        $period = '';
        $r = $staff_conn->query("SELECT * FROM payroll_runs WHERE id=$rid");
        if ($r) $rr = $r->fetch_assoc();
        if ($rr) {
            $period = $rr['period'];
            $emps = $staff_conn->query("SELECT pe.*, s.id as sid FROM payroll_employees pe JOIN staff s ON pe.staff_id = s.id WHERE pe.status='active' OR pe.status IS NULL");
            while ($e = $emps->fetch_assoc()) {
                $sid   = $e['sid'];
                $base  = (float)($e['basic_salary'] ?? 0);
                $alw   = (float)$staff_conn->query("SELECT COALESCE(SUM(amount),0) c FROM payroll_allowances WHERE staff_id=$sid AND month='$period'")->fetch_assoc()['c'];
                $ot    = (float)$staff_conn->query("SELECT COALESCE(SUM(total_pay),0) c FROM payroll_overtime WHERE staff_id=$sid AND month='$period'")->fetch_assoc()['c'];
                $bn    = (float)$staff_conn->query("SELECT COALESCE(SUM(amount),0) c FROM payroll_bonuses WHERE staff_id=$sid AND month='$period'")->fetch_assoc()['c'];
                $dd    = (float)$staff_conn->query("SELECT COALESCE(SUM(amount),0) c FROM payroll_deductions WHERE staff_id=$sid AND month='$period'")->fetch_assoc()['c'];
                $gross = $base + $alw + $ot + $bn;
                $paye  = payCalculatePAYE($gross);
                $nssf_c = payCalculateNSSF($base);
                $net   = $gross - $paye - $nssf_c['employee'] - $dd;
                $ins = $staff_conn->prepare("INSERT INTO payroll_details (payroll_run_id, staff_id, basic_salary, total_allowances, overtime_pay, bonuses, gross_pay, paye_tax, nssf_employee, nssf_employer, other_deductions, net_pay) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE gross_pay=VALUES(gross_pay), net_pay=VALUES(net_pay)");
                if ($ins) { $ins->bind_param('iidddddddddd', $rid, $sid, $base, $alw, $ot, $bn, $gross, $paye, $nssf_c['employee'], $nssf_c['employer'], $dd, $net); $ins->execute(); }
            }
            $staff_conn->query("UPDATE payroll_runs SET total_gross=(SELECT COALESCE(SUM(gross_pay),0) FROM payroll_details WHERE payroll_run_id=$rid), total_deductions=(SELECT COALESCE(SUM(paye_tax+nssf_employee+other_deductions),0) FROM payroll_details WHERE payroll_run_id=$rid), total_net=(SELECT COALESCE(SUM(net_pay),0) FROM payroll_details WHERE payroll_run_id=$rid) WHERE id=$rid");
            $_SESSION['success']="Payroll run #$rid processed.";
        }
        header('Location: bursar-payroll.php?tab=run'); exit;
    }

    // 8. Approve Run
    if ($action === 'approve_run') {
        $rid   = (int)($_POST['run_id'] ?? 0);
        $level = trim($_POST['approval_level'] ?? 'Bursar');
        $stmt = $staff_conn->prepare("INSERT INTO payroll_approvals (payroll_run_id, level, status, approved_by) VALUES (?,?,'approved',?) ON DUPLICATE KEY UPDATE status='approved', approved_by=VALUES(approved_by), updated_at=NOW()");
        if ($stmt) { $stmt->bind_param('isi', $rid, $level, $user_id); $stmt->execute(); }
        $staff_conn->query("UPDATE payroll_runs SET status='approved', approved_by=$user_id, approved_at=NOW() WHERE id=$rid");
        $_SESSION['success']="Payroll run #$rid approved at $level level.";
        header('Location: bursar-payroll.php?tab=approvals'); exit;
    }

    // 9. Generate Payslips
    if ($action === 'generate_payslips') {
        $rid = (int)($_POST['run_id'] ?? 0);
        $r = $staff_conn->query("SELECT pd.*, pr.period FROM payroll_details pd JOIN payroll_runs pr ON pd.payroll_run_id = pr.id WHERE pd.payroll_run_id = $rid AND pd.payment_status='pending'");
        while ($d = $r->fetch_assoc()) {
            $sid = $d['staff_id'];
            $ref = 'PAY-' . $d['period'] . '-' . str_pad($sid, 4, '0', STR_PAD_LEFT);
            $ins = $staff_conn->prepare("INSERT INTO payslips (staff_id, payslip_number, salary_month, basic_salary, allowances, gross_salary, deductions, net_salary, payroll_run_id, payroll_detail_id, payment_ref, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,'generated') ON DUPLICATE KEY UPDATE payment_ref=VALUES(payment_ref)");
            if ($ins) { $ins->bind_param('issdddddiis', $sid, $ref, $d['period'], $d['basic_salary'], $d['total_allowances'], $d['gross_pay'], ($d['paye_tax']+$d['nssf_employee']+$d['other_deductions']), $d['net_pay'], $rid, $d['id'], $ref); $ins->execute(); }
            $staff_conn->query("UPDATE payroll_details SET payment_reference='$ref' WHERE id={$d['id']}");
        }
        $staff_conn->query("UPDATE payroll_runs SET status='processed' WHERE id=$rid");
        $_SESSION['success']="Payslips generated for run #$rid.";
        header('Location: bursar-payroll.php?tab=payslips'); exit;
    }

    // 10. Mark Paid
    if ($action === 'mark_paid') {
        $rid  = (int)($_POST['run_id'] ?? 0);
        $meth = $_POST['payment_method'] ?? 'bank_transfer';
        $allowed_methods = ['bank_transfer','cash','cheque','mobile_money_mtn','mobile_money_airtel'];
        if (!in_array($meth, $allowed_methods, true)) { $meth = 'bank_transfer'; }
        $stmt1 = $staff_conn->prepare("UPDATE payroll_details SET payment_method=?, payment_status='paid', payment_date=CURDATE() WHERE payroll_run_id=?");
        if ($stmt1) { $stmt1->bind_param('si', $meth, $rid); $stmt1->execute(); $stmt1->close(); }
        $stmt2 = $staff_conn->prepare("UPDATE payslips SET status='paid', payment_method=?, payment_date=CURDATE() WHERE payroll_run_id=? AND status='generated'");
        if ($stmt2) { $stmt2->bind_param('si', $meth, $rid); $stmt2->execute(); $stmt2->close(); }
        $stmt3 = $staff_conn->prepare("UPDATE payroll_runs SET status='paid' WHERE id=?");
        if ($stmt3) { $stmt3->bind_param('i', $rid); $stmt3->execute(); $stmt3->close(); }
        $_SESSION['success']="Payroll run #$rid marked as paid.";
        header('Location: bursar-payroll.php?tab=payment'); exit;
    }
}

// ── Data Fetching ──
$payroll_employees = [];
$payroll_runs = [];
$active_run = null;
$payslips_list = [];
$approvals = [];
$stats = ['total_employees' => 0, 'total_gross' => 0, 'pending_approvals' => 0, 'active_runs' => 0];

if ($staff_conn) {
    $stats['total_employees'] = (int)($staff_conn->query("SELECT COUNT(*) c FROM payroll_employees")->fetch_assoc()['c'] ?? 0);
    $stats['total_gross'] = (float)($staff_conn->query("SELECT COALESCE(SUM(total_gross),0) c FROM payroll_runs WHERE status IN ('draft','approved')")->fetch_assoc()['c'] ?? 0);
    $stats['pending_approvals'] = (int)($staff_conn->query("SELECT COUNT(*) c FROM payroll_approvals WHERE status='pending'")->fetch_assoc()['c'] ?? 0);
    $stats['active_runs'] = (int)($staff_conn->query("SELECT COUNT(*) c FROM payroll_runs WHERE status IN ('draft','approved')")->fetch_assoc()['c'] ?? 0);

    $r = $staff_conn->query("SELECT pe.*, s.full_name, s.staff_id, d.department_name FROM payroll_employees pe JOIN staff s ON pe.staff_id = s.id LEFT JOIN staff_departments d ON s.department_id = d.id ORDER BY s.full_name");
    if ($r) $payroll_employees = $r->fetch_all(MYSQLI_ASSOC);

    $r = $staff_conn->query("SELECT pr.*, cr.full_name as created_name, ap.full_name as approved_name FROM payroll_runs pr LEFT JOIN staff cr ON pr.created_by = cr.id LEFT JOIN staff ap ON pr.approved_by = ap.id ORDER BY pr.created_at DESC");
    if ($r) $payroll_runs = $r->fetch_all(MYSQLI_ASSOC);

    $r = $staff_conn->query("SELECT ps.*, s.full_name FROM payslips ps JOIN staff s ON ps.staff_id = s.id ORDER BY ps.generated_date DESC LIMIT 100");
    if ($r) $payslips_list = $r->fetch_all(MYSQLI_ASSOC);

    $r = $staff_conn->query("SELECT pa.*, s.full_name as approver_name FROM payroll_approvals pa LEFT JOIN staff s ON pa.approved_by = s.id ORDER BY pa.updated_at DESC LIMIT 50");
    if ($r) $approvals = $r->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root{--bs-primary:#1a237e;--bs-primary-dark:#0d1442}
.bursar-payroll .stat-card{background:#fff;border-radius:12px;padding:20px;border:1px solid #e5e7eb;border-left:4px solid var(--bs-primary);transition:all .2s;height:100%}
.bursar-payroll .stat-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.bursar-payroll .stat-card .num{font-size:1.5rem;font-weight:700;color:var(--bs-primary);margin:0}
.bursar-payroll .stat-card .lbl{font-size:12px;color:#6b7280;margin:0}
.bursar-payroll .cc{border:1px solid #e5e7eb;border-radius:12px;background:#fff;overflow:hidden}
.bursar-payroll .ch{padding:14px 20px;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:14px;background:#f8fafc;color:var(--bs-primary)}
.bursar-payroll .nav-tabs .nav-link{color:#1a237e;font-weight:500;font-size:13px;padding:10px 16px;border:none;border-bottom:3px solid transparent}
.bursar-payroll .nav-tabs .nav-link.active{color:var(--bs-primary);border-bottom-color:var(--bs-primary);background:0 0}
.bursar-payroll .nav-tabs .nav-link:hover{border-bottom-color:#c5cae9}
.bursar-payroll .tb th{font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;border-bottom:2px solid #e5e7eb;padding:8px 12px;white-space:nowrap}
.bursar-payroll .tb td{font-size:13px;padding:8px 12px;vertical-align:middle}
.bursar-payroll .fc{font-size:13px;border-radius:8px;border:1px solid #d1d5db}
.bursar-payroll .bb{background:var(--bs-primary);color:#fff;border-radius:8px;font-size:13px;font-weight:500;padding:8px 20px}
.bursar-payroll .bb:hover{background:var(--bs-primary-dark);color:#fff}
.bursar-payroll .badge-status{font-size:11px;padding:3px 10px;border-radius:20px;font-weight:500}
</style>
</head>
<body class="bursar-payroll">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="ma" style="margin-left:270px;padding:24px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1" style="color:var(--bs-primary)"><i class="fas fa-money-check-alt me-2"></i>Bursar Payroll</h2>
            <p class="text-muted mb-0 small">Comprehensive payroll management — link with HR, calculate, approve & pay</p>
        </div>
        <a href="school-bursar.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <?php if ($msg = $_SESSION['success'] ?? ''): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($msg) ?></div><?php unset($_SESSION['success']); endif; ?>
    <?php if ($err = $_SESSION['error'] ?? ''): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($err) ?></div><?php unset($_SESSION['error']); endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4 flex-nowrap overflow-auto">
        <li class="nav-item"><a class="nav-link <?= $tab==='overview'?'active':'' ?>" href="bursar-payroll.php?tab=overview"><i class="fas fa-chart-pie me-1"></i>Overview</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='employees'?'active':'' ?>" href="bursar-payroll.php?tab=employees"><i class="fas fa-users me-1"></i>Employees</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='allowances'?'active':'' ?>" href="bursar-payroll.php?tab=allowances"><i class="fas fa-plus-circle me-1"></i>Allowances</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='deductions'?'active':'' ?>" href="bursar-payroll.php?tab=deductions"><i class="fas fa-minus-circle me-1"></i>Deductions</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='overtime'?'active':'' ?>" href="bursar-payroll.php?tab=overtime"><i class="fas fa-clock me-1"></i>Overtime</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='bonuses'?'active':'' ?>" href="bursar-payroll.php?tab=bonuses"><i class="fas fa-gift me-1"></i>Bonuses</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='run'?'active':'' ?>" href="bursar-payroll.php?tab=run"><i class="fas fa-calculator me-1"></i>Payroll Run</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='payslips'?'active':'' ?>" href="bursar-payroll.php?tab=payslips"><i class="fas fa-file-invoice me-1"></i>Payslips</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='approvals'?'active':'' ?>" href="bursar-payroll.php?tab=approvals"><i class="fas fa-check-double me-1"></i>Approvals</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='payment'?'active':'' ?>" href="bursar-payroll.php?tab=payment"><i class="fas fa-credit-card me-1"></i>Payment</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='reports'?'active':'' ?>" href="bursar-payroll.php?tab=reports"><i class="fas fa-file-alt me-1"></i>Reports</a></li>
    </ul>

    <!-- ── OVERVIEW ── -->
    <?php if ($tab === 'overview'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="stat-card" style="border-left-color:#1a237e"><p class="num"><?= $stats['total_employees'] ?></p><p class="lbl">Payroll Employees</p></div></div>
        <div class="col-md-3 col-6"><div class="stat-card" style="border-left-color:#16a34a"><p class="num">UGX <?= number_format($stats['total_gross']) ?></p><p class="lbl">Current Gross Pay</p></div></div>
        <div class="col-md-3 col-6"><div class="stat-card" style="border-left-color:#d97706"><p class="num"><?= $stats['pending_approvals'] ?></p><p class="lbl">Pending Approvals</p></div></div>
        <div class="col-md-3 col-6"><div class="stat-card" style="border-left-color:#0891b2"><p class="num"><?= $stats['active_runs'] ?></p><p class="lbl">Active Runs</p></div></div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Recent Payroll Runs</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>#</th><th>Period</th><th>Gross</th><th>Net</th><th>Status</th></tr></thead>
                    <tbody><?php foreach (array_slice($payroll_runs, 0, 5) as $r): ?>
                        <tr><td><?= $r['id'] ?></td><td><?= htmlspecialchars($r['period']) ?></td><td>UGX <?= number_format($r['total_gross']??0) ?></td><td><strong>UGX <?= number_format($r['total_net']??0) ?></strong></td><td><?= payStatusBadge($r['status'] ?? 'draft') ?></td></tr>
                    <?php endforeach; if (empty($payroll_runs)): ?><tr><td colspan="5" class="text-center text-muted py-3">No payroll runs yet.</td></tr><?php endif; ?></tbody>
                </table></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="cc"><div class="ch"><i class="fas fa-user-check me-2"></i>Payroll Employees</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Staff</th><th>Department</th><th>Basic Salary</th><th>Type</th></tr></thead>
                    <tbody><?php foreach (array_slice($payroll_employees, 0, 5) as $e): ?>
                        <tr><td><small><?= htmlspecialchars($e['full_name'] ?? '') ?></small></td><td><small><?= htmlspecialchars($e['department_name'] ?? '-') ?></small></td><td>UGX <?= number_format($e['basic_salary']??0) ?></td><td><span class="badge bg-secondary"><?= htmlspecialchars($e['salary_type'] ?? 'monthly') ?></span></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── EMPLOYEES ── -->
    <?php if ($tab === 'employees'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="cc"><div class="ch"><i class="fas fa-user-plus me-2"></i>Add / Update Payroll Profile</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Staff *</label>
                            <select name="staff_id" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <?php $r = $staff_conn->query("SELECT id, full_name FROM staff WHERE status='active' OR status='Active' ORDER BY full_name");
                                if ($r) while ($s = $r->fetch_assoc()) echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['full_name']).'</option>'; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="small fw-medium">Bank Name</label><input name="bank_name" class="form-control fc"></div>
                            <div class="col-6"><label class="small fw-medium">Account No.</label><input name="bank_account" class="form-control fc"></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="small fw-medium">TIN</label><input name="tin_number" class="form-control fc"></div>
                            <div class="col-6"><label class="small fw-medium">NSSF No.</label><input name="nssf_number" class="form-control fc"></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-4"><label class="small fw-medium">Type</label>
                                <select name="salary_type" class="form-control fc"><option value="monthly">Monthly</option><option value="annual">Annual</option></select>
                            </div>
                            <div class="col-4"><label class="small fw-medium">Grade</label>
                                <select name="salary_grade" class="form-control fc"><option value="">--</option><option>Lecturer</option><option>Support Staff</option><option>Administration</option><option>Clinical</option></select>
                            </div>
                            <div class="col-4"><label class="small fw-medium">Basic Salary</label><input name="basic_salary" type="number" step="0.01" class="form-control fc"></div>
                        </div>
                        <button type="submit" name="action" value="add_employee" class="btn bb w-100 mt-2"><i class="fas fa-save me-1"></i>Save Profile</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Payroll Employees (Linked to HR Master)</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Staff</th><th>Department</th><th>Basic Salary</th><th>Type</th><th>Grade</th><th>Bank</th><th>TIN / NSSF</th></tr></thead>
                    <tbody><?php foreach ($payroll_employees as $e): ?>
                        <tr>
                            <td><small><?= htmlspecialchars($e['full_name'] ?? '') ?></small></td>
                            <td><small><?= htmlspecialchars($e['department_name'] ?? '-') ?></small></td>
                            <td><strong>UGX <?= number_format($e['basic_salary']??0) ?></strong></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($e['salary_type'] ?? 'monthly') ?></span></td>
                            <td><small><?= htmlspecialchars($e['salary_grade'] ?? '-') ?></small></td>
                            <td><small class="text-muted"><?= htmlspecialchars($e['bank_name'] ?? '-') ?></small></td>
                            <td><small><?= htmlspecialchars($e['tax_identification'] ?? '-') ?> / <?= htmlspecialchars($e['nssf_number'] ?? '-') ?></small></td>
                        </tr>
                    <?php endforeach; if (empty($payroll_employees)): ?><tr><td colspan="7" class="text-center text-muted py-3">No employees in payroll yet. Add profiles from the form.</td></tr><?php endif; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── ALLOWANCES ── -->
    <?php if ($tab === 'allowances'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="cc"><div class="ch"><i class="fas fa-plus me-2"></i>Add Allowance</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Staff *</label>
                            <select name="staff_id" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($payroll_employees as $e) echo '<option value="'.$e['staff_id'].'">'.htmlspecialchars($e['full_name']).'</option>'; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Type *</label>
                            <select name="allowance_type" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <option>Housing Allowance</option><option>Transport Allowance</option><option>Medical Allowance</option>
                                <option>Airtime Allowance</option><option>Lunch Allowance</option><option>Hardship Allowance</option><option>Other Allowance</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Amount (UGX) *</label><input name="amount" type="number" step="0.01" class="form-control fc" required></div>
                        <div class="mb-2"><label class="small fw-medium">Month</label><input name="month" type="month" class="form-control fc" value="<?= date('Y-m') ?>"></div>
                        <button type="submit" name="action" value="add_allowance" class="btn bb w-100"><i class="fas fa-save me-1"></i>Add Allowance</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Allowances</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Staff</th><th>Type</th><th>Amount</th><th>Month</th></tr></thead>
                    <tbody><?php $r = $staff_conn->query("SELECT pa.*, s.full_name FROM payroll_allowances pa JOIN staff s ON pa.staff_id = s.id ORDER BY pa.created_at DESC LIMIT 100");
                    if ($r) while ($a = $r->fetch_assoc()): ?>
                        <tr><td><small><?= htmlspecialchars($a['full_name']) ?></small></td><td><?= htmlspecialchars($a['allowance_type']) ?></td><td><strong>UGX <?= number_format($a['amount']) ?></strong></td><td><small><?= htmlspecialchars($a['month']) ?></small></td></tr>
                    <?php endwhile; if (!$r || $r->num_rows === 0): ?><tr><td colspan="4" class="text-center text-muted py-3">No allowances recorded.</td></tr><?php endif; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── DEDUCTIONS ── -->
    <?php if ($tab === 'deductions'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="cc"><div class="ch"><i class="fas fa-minus me-2"></i>Add Deduction</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Staff *</label>
                            <select name="staff_id" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($payroll_employees as $e) echo '<option value="'.$e['staff_id'].'">'.htmlspecialchars($e['full_name']).'</option>'; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Type *</label>
                            <select name="deduction_type" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <option>Salary Advance</option><option>Loan Repayment</option><option>SACCO Contribution</option>
                                <option>Insurance Deduction</option><option>Union Dues</option><option>Disciplinary Deduction</option><option>Local Tax</option><option>Other Deduction</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Amount (UGX) *</label><input name="amount" type="number" step="0.01" class="form-control fc" required></div>
                        <div class="mb-2"><label class="small fw-medium">Month</label><input name="month" type="month" class="form-control fc" value="<?= date('Y-m') ?>"></div>
                        <button type="submit" name="action" value="add_deduction" class="btn bb w-100"><i class="fas fa-save me-1"></i>Add Deduction</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Non-Statutory Deductions</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Staff</th><th>Type</th><th>Amount</th><th>Month</th></tr></thead>
                    <tbody><?php $r = $staff_conn->query("SELECT pd.*, s.full_name FROM payroll_deductions pd JOIN staff s ON pd.staff_id = s.id ORDER BY pd.created_at DESC LIMIT 100");
                    if ($r) while ($d = $r->fetch_assoc()): ?>
                        <tr><td><small><?= htmlspecialchars($d['full_name']) ?></small></td><td><?= htmlspecialchars($d['deduction_type']) ?></td><td><strong class="text-danger">-UGX <?= number_format($d['amount']) ?></strong></td><td><small><?= htmlspecialchars($d['month']) ?></small></td></tr>
                    <?php endwhile; if (!$r || $r->num_rows === 0): ?><tr><td colspan="4" class="text-center text-muted py-3">No deductions recorded.</td></tr><?php endif; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── OVERTIME ── -->
    <?php if ($tab === 'overtime'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="cc"><div class="ch"><i class="fas fa-clock me-2"></i>Add Overtime</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Staff *</label>
                            <select name="staff_id" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($payroll_employees as $e) echo '<option value="'.$e['staff_id'].'">'.htmlspecialchars($e['full_name']).'</option>'; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="small fw-medium">Hours *</label><input name="hours" type="number" step="0.5" class="form-control fc" required></div>
                            <div class="col-6"><label class="small fw-medium">Rate (UGX/hr) *</label><input name="rate" type="number" step="0.01" class="form-control fc" required></div>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Month</label><input name="month" type="month" class="form-control fc" value="<?= date('Y-m') ?>"></div>
                        <button type="submit" name="action" value="add_overtime" class="btn bb w-100"><i class="fas fa-save me-1"></i>Add Overtime</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Overtime Records</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Staff</th><th>Hours</th><th>Rate</th><th>Total Pay</th><th>Approved By</th><th>Month</th></tr></thead>
                    <tbody><?php $r = $staff_conn->query("SELECT po.*, s.full_name, ap.full_name as approver FROM payroll_overtime po JOIN staff s ON po.staff_id = s.id LEFT JOIN staff ap ON po.approved_by = ap.id ORDER BY po.created_at DESC LIMIT 100");
                    if ($r) while ($o = $r->fetch_assoc()): ?>
                        <tr><td><small><?= htmlspecialchars($o['full_name']) ?></small></td><td><?= $o['hours'] ?></td><td>UGX <?= number_format($o['rate']) ?></td><td><strong>UGX <?= number_format($o['total_pay']) ?></strong></td><td><small><?= htmlspecialchars($o['approver'] ?? '-') ?></small></td><td><small><?= htmlspecialchars($o['month']) ?></small></td></tr>
                    <?php endwhile; if (!$r || $r->num_rows === 0): ?><tr><td colspan="6" class="text-center text-muted py-3">No overtime records.</td></tr><?php endif; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── BONUSES ── -->
    <?php if ($tab === 'bonuses'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="cc"><div class="ch"><i class="fas fa-gift me-2"></i>Add Bonus</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Staff *</label>
                            <select name="staff_id" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($payroll_employees as $e) echo '<option value="'.$e['staff_id'].'">'.htmlspecialchars($e['full_name']).'</option>'; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Type *</label>
                            <select name="bonus_type" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <option>Performance Bonus</option><option>Commission</option><option>Appreciation Bonus</option>
                                <option>Academic Excellence</option><option>Clinical Supervision</option><option>13th Cheque</option><option>Other Bonus</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Amount (UGX) *</label><input name="amount" type="number" step="0.01" class="form-control fc" required></div>
                        <div class="mb-2"><label class="small fw-medium">Month</label><input name="month" type="month" class="form-control fc" value="<?= date('Y-m') ?>"></div>
                        <button type="submit" name="action" value="add_bonus" class="btn bb w-100"><i class="fas fa-save me-1"></i>Add Bonus</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Bonuses & Incentives</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Staff</th><th>Type</th><th>Amount</th><th>Month</th></tr></thead>
                    <tbody><?php $r = $staff_conn->query("SELECT pb.*, s.full_name FROM payroll_bonuses pb JOIN staff s ON pb.staff_id = s.id ORDER BY pb.created_at DESC LIMIT 100");
                    if ($r) while ($b = $r->fetch_assoc()): ?>
                        <tr><td><small><?= htmlspecialchars($b['full_name']) ?></small></td><td><?= htmlspecialchars($b['bonus_type']) ?></td><td><strong class="text-success">UGX <?= number_format($b['amount']) ?></strong></td><td><small><?= htmlspecialchars($b['month']) ?></small></td></tr>
                    <?php endwhile; if (!$r || $r->num_rows === 0): ?><tr><td colspan="4" class="text-center text-muted py-3">No bonuses recorded.</td></tr><?php endif; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── PAYROLL RUN ── -->
    <?php if ($tab === 'run'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="cc"><div class="ch"><i class="fas fa-plus me-2"></i>Create Payroll Run</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Period (YYYY-MM) *</label><input name="period" type="month" class="form-control fc" value="<?= date('Y-m') ?>" required></div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="small fw-medium">Start Date</label><input name="start_date" type="date" class="form-control fc"></div>
                            <div class="col-6"><label class="small fw-medium">End Date</label><input name="end_date" type="date" class="form-control fc"></div>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Description</label><textarea name="description" class="form-control fc" rows="2"></textarea></div>
                        <button type="submit" name="action" value="create_run" class="btn bb w-100"><i class="fas fa-plus me-1"></i>Create Run (Draft)</button>
                    </form>
                </div>
            </div>
            <div class="cc mt-3"><div class="ch"><i class="fas fa-play me-2"></i>Process Existing Run</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Select Run *</label>
                            <select name="run_id" class="form-control fc" required>
                                <option value="">-- Draft Runs --</option>
                                <?php foreach ($payroll_runs as $r) if (($r['status']??'') === 'draft') echo '<option value="'.$r['id'].'">#'.$r['id'].' — '.htmlspecialchars($r['period']).'</option>'; ?>
                            </select>
                        </div>
                        <div class="alert alert-info small py-2 mb-2"><i class="fas fa-info-circle me-1"></i>Calculates PAYE (Ugandan brackets), NSSF 5%/10%, gross & net for all employees.</div>
                        <button type="submit" name="action" value="process_run" class="btn bb w-100"><i class="fas fa-calculator me-1"></i>Process Payroll</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Payroll Runs</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Run</th><th>Period</th><th>Start</th><th>End</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody><?php foreach ($payroll_runs as $r): ?>
                        <tr>
                            <td><code>#<?= $r['id'] ?></code></td>
                            <td><strong><?= htmlspecialchars($r['period']) ?></strong></td>
                            <td><small><?= htmlspecialchars($r['start_date'] ?? '-') ?></small></td>
                            <td><small><?= htmlspecialchars($r['end_date'] ?? '-') ?></small></td>
                            <td>UGX <?= number_format($r['total_gross']??0) ?></td>
                            <td class="text-danger">UGX <?= number_format($r['total_deductions']??0) ?></td>
                            <td><strong>UGX <?= number_format($r['total_net']??0) ?></strong></td>
                            <td><?= payStatusBadge($r['status'] ?? 'draft') ?></td>
                            <td><small><?= htmlspecialchars($r['created_name'] ?? '') ?></small></td>
                        </tr>
                    <?php endforeach; if (empty($payroll_runs)): ?><tr><td colspan="9" class="text-center text-muted py-3">No payroll runs yet.</td></tr><?php endif; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── PAYSLIPS ── -->
    <?php if ($tab === 'payslips'): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="cc"><div class="ch"><i class="fas fa-file-invoice me-2"></i>Generate Payslips</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Payroll Run (processed) *</label>
                            <select name="run_id" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($payroll_runs as $r) if (in_array($r['status'] ?? '', ['processed','approved','paid','draft'])) echo '<option value="'.$r['id'].'">#'.$r['id'].' — '.htmlspecialchars($r['period']).' ('.($r['status']??'draft').')</option>'; ?>
                            </select>
                        </div>
                        <button type="submit" name="action" value="generate_payslips" class="btn bb w-100"><i class="fas fa-file me-1"></i>Generate Payslips</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Generated Payslips</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Ref</th><th>Staff</th><th>Period</th><th>Basic</th><th>Gross</th><th>Net</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($payslips_list as $ps): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($ps['payslip_number']) ?></code></td>
                            <td><small><?= htmlspecialchars($ps['full_name']) ?></small></td>
                            <td><?= htmlspecialchars($ps['salary_month']) ?></td>
                            <td>UGX <?= number_format($ps['basic_salary']??0) ?></td>
                            <td>UGX <?= number_format($ps['gross_salary']??0) ?></td>
                            <td><strong>UGX <?= number_format($ps['net_salary']??0) ?></strong></td>
                            <td><?= payStatusBadge($ps['status'] ?? 'generated') ?></td>
                        </tr>
                    <?php endforeach; if (empty($payslips_list)): ?><tr><td colspan="7" class="text-center text-muted py-3">No payslips generated.</td></tr><?php endif; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── APPROVALS ── -->
    <?php if ($tab === 'approvals'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="cc"><div class="ch"><i class="fas fa-check me-2"></i>Approve Payroll Run</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Payroll Run *</label>
                            <select name="run_id" class="form-control fc" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($payroll_runs as $r) echo '<option value="'.$r['id'].'">#'.$r['id'].' — '.htmlspecialchars($r['period']).' ('.($r['status']??'draft').')</option>'; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Approval Level *</label>
                            <select name="approval_level" class="form-control fc" required>
                                <option value="Bursar">Bursar</option>
                                <option value="DirectorFinance">Director Finance</option>
                            </select>
                        </div>
                        <button type="submit" name="action" value="approve_run" class="btn bb w-100"><i class="fas fa-check-double me-1"></i>Approve</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Approval History</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Run #</th><th>Level</th><th>Status</th><th>Approved By</th><th>Date</th></tr></thead>
                    <tbody><?php foreach ($approvals as $a): ?>
                        <tr>
                            <td><code>#<?= $a['payroll_run_id'] ?></code></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($a['level']) ?></span></td>
                            <td><?= payStatusBadge($a['status'] ?? 'pending') ?></td>
                            <td><small><?= htmlspecialchars($a['approver_name'] ?? '-') ?></small></td>
                            <td><small><?= htmlspecialchars($a['updated_at'] ?? '') ?></small></td>
                        </tr>
                    <?php endforeach; if (empty($approvals)): ?><tr><td colspan="5" class="text-center text-muted py-3">No approvals yet.</td></tr><?php endif; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── PAYMENT ── -->
    <?php if ($tab === 'payment'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="cc"><div class="ch"><i class="fas fa-credit-card me-2"></i>Mark Run as Paid</div>
                <div class="p-3">
                    <form method="POST">
                        <div class="mb-2"><label class="small fw-medium">Payroll Run *</label>
                            <select name="run_id" class="form-control fc" required>
                                <option value="">-- Processed/Approved Runs --</option>
                                <?php foreach ($payroll_runs as $r) if (in_array($r['status']??'', ['processed','approved'])) echo '<option value="'.$r['id'].'">#'.$r['id'].' — '.htmlspecialchars($r['period']).' (UGX '.number_format($r['total_net']??0).')</option>'; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="small fw-medium">Payment Method *</label>
                            <select name="payment_method" class="form-control fc" required>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="mobile_money_mtn">Mobile Money (MTN)</option>
                                <option value="mobile_money_airtel">Mobile Money (Airtel)</option>
                            </select>
                        </div>
                        <button type="submit" name="action" value="mark_paid" class="btn bb w-100"><i class="fas fa-check me-1"></i>Mark as Paid</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Payment Status</div>
                <div class="table-responsive"><table class="table tb mb-0">
                    <thead><tr><th>Run #</th><th>Period</th><th>Net Pay</th><th>Employees</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($payroll_runs as $r): ?>
                        <tr>
                            <td><code>#<?= $r['id'] ?></code></td>
                            <td><strong><?= htmlspecialchars($r['period']) ?></strong></td>
                            <td>UGX <?= number_format($r['total_net']??0) ?></td>
                            <td><?= (int)($staff_conn->query("SELECT COUNT(*) c FROM payroll_details WHERE payroll_run_id={$r['id']}")->fetch_assoc()['c'] ?? 0) ?></td>
                            <td><?= payStatusBadge($r['status'] ?? 'draft') ?></td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── REPORTS ── -->
    <?php if ($tab === 'reports'): ?>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="cc"><div class="ch"><i class="fas fa-file-alt me-2"></i>Payroll Summary Report</div>
                <div class="p-3">
                    <form method="GET" action="bursar-payroll.php" target="_blank">
                        <input type="hidden" name="tab" value="reports">
                        <div class="mb-2"><label class="small fw-medium">Payroll Run</label>
                            <select name="report_run" class="form-control fc">
                                <option value="">-- Select --</option>
                                <?php foreach ($payroll_runs as $r) echo '<option value="'.$r['id'].'">#'.$r['id'].' — '.htmlspecialchars($r['period']).'</option>'; ?>
                            </select>
                        </div>
                        <button type="submit" name="report_type" value="summary" class="btn bb w-100"><i class="fas fa-eye me-1"></i>View Summary</button>
                    </form>
                </div>
            </div>
            <?php if (($report_run = (int)($_GET['report_run'] ?? 0)) && $staff_conn): ?>
                <?php $rd = $staff_conn->query("SELECT pd.*, s.full_name, d.department_name FROM payroll_details pd JOIN staff s ON pd.staff_id = s.id LEFT JOIN staff_departments d ON s.department_id = d.id WHERE pd.payroll_run_id = $report_run ORDER BY s.full_name");
                $rr = $staff_conn->query("SELECT * FROM payroll_runs WHERE id=$report_run")->fetch_assoc(); ?>
                <?php if ($rd && $rr): ?>
                <div class="cc mt-3"><div class="ch"><i class="fas fa-print me-2"></i>Run #<?= $report_run ?> — <?= htmlspecialchars($rr['period']) ?></div>
                    <div class="table-responsive"><table class="table tb mb-0">
                        <thead><tr><th>Staff</th><th>Dept</th><th>Basic</th><th>Allowances</th><th>OT</th><th>Bonuses</th><th>Gross</th><th>PAYE</th><th>NSSF</th><th>Deductions</th><th>Net</th></tr></thead>
                        <tbody><?php $tg=0;$tn=0; while ($d = $rd->fetch_assoc()): $tg+=$d['gross_pay'];$tn+=$d['net_pay']; ?>
                            <tr>
                                <td><small><?= htmlspecialchars($d['full_name']) ?></small></td>
                                <td><small><?= htmlspecialchars($d['department_name'] ?? '-') ?></small></td>
                                <td>UGX <?= number_format($d['basic_salary']??0) ?></td>
                                <td>UGX <?= number_format($d['total_allowances']??0) ?></td>
                                <td>UGX <?= number_format($d['overtime_pay']??0) ?></td>
                                <td>UGX <?= number_format($d['bonuses']??0) ?></td>
                                <td><strong>UGX <?= number_format($d['gross_pay']??0) ?></strong></td>
                                <td class="text-danger">-UGX <?= number_format($d['paye_tax']??0) ?></td>
                                <td class="text-danger">-UGX <?= number_format(($d['nssf_employee']??0)+($d['nssf_employer']??0)) ?></td>
                                <td class="text-danger">-UGX <?= number_format($d['other_deductions']??0) ?></td>
                                <td><strong>UGX <?= number_format($d['net_pay']??0) ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                        <tr class="fw-bold" style="background:#f8fafc"><td colspan="6">TOTALS</td><td>UGX <?= number_format($tg) ?></td><td></td><td></td><td></td><td><strong>UGX <?= number_format($tn) ?></strong></td></tr>
                        </tbody>
                    </table></div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <div class="cc"><div class="ch"><i class="fas fa-building me-2"></i>Department Payroll Report</div>
                <div class="p-3">
                    <form method="GET" action="bursar-payroll.php">
                        <input type="hidden" name="tab" value="reports">
                        <div class="mb-2"><label class="small fw-medium">Payroll Run</label>
                            <select name="report_run" class="form-control fc">
                                <option value="">-- Select --</option>
                                <?php foreach ($payroll_runs as $r) echo '<option value="'.$r['id'].'">#'.$r['id'].' — '.htmlspecialchars($r['period']).'</option>'; ?>
                            </select>
                        </div>
                        <button type="submit" name="report_type" value="dept" class="btn bb w-100"><i class="fas fa-eye me-1"></i>View Department Report</button>
                    </form>
                </div>
            </div>
            <div class="cc mt-3">
                <div class="ch"><i class="fas fa-calculator me-2"></i>PAYE Tax Report</div>
                <div class="p-3">
                    <form method="GET" action="bursar-payroll.php">
                        <input type="hidden" name="tab" value="reports">
                        <div class="mb-2"><label class="small fw-medium">Payroll Run</label>
                            <select name="report_run" class="form-control fc">
                                <option value="">-- Select --</option>
                                <?php foreach ($payroll_runs as $r) echo '<option value="'.$r['id'].'">#'.$r['id'].' — '.htmlspecialchars($r['period']).'</option>'; ?>
                            </select>
                        </div>
                        <button type="submit" name="report_type" value="tax" class="btn bb w-100"><i class="fas fa-file-invoice-dollar me-1"></i>View Tax Report</button>
                    </form>
                    <?php if ($report_run && ($_GET['report_type'] ?? '') === 'tax' && $staff_conn): ?>
                        <?php $tx = $staff_conn->query("SELECT SUM(paye_tax) as total_paye, SUM(nssf_employee+nssf_employer) as total_nssf FROM payroll_details WHERE payroll_run_id=$report_run")->fetch_assoc(); ?>
                        <div class="mt-3 small">
                            <p><strong>PAYE Tax:</strong> UGX <?= number_format($tx['total_paye']??0) ?></p>
                            <p><strong>NSSF Total:</strong> UGX <?= number_format($tx['total_nssf']??0) ?></p>
                            <p><strong>Statutory Total:</strong> UGX <?= number_format(($tx['total_paye']??0)+($tx['total_nssf']??0)) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
