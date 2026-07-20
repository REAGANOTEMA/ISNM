<?php
/**
 * ISNM Payroll Management System — Main Dashboard
 * Single-page dashboard with hash-based section navigation.
 */

require_once __DIR__ . '/includes/payroll_functions.php';
require_once __DIR__ . '/includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['payroll', 'bursar', 'finance', 'hr', 'accountant', 'auditor', 'principal']);
$user = $ctx['user'];
$staffId = (int)($user['id'] ?? 0);
$userRole = $user['role'] ?? '';
$staffConn = $ctx['staff'];
$payrollConn = getPayrollConnection();

$stats = getPayrollDashboardStats();
$periods = getPayrollPeriods();
$employees = getPayrollEmployees('active');

$pageTitle = 'Payroll Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/includes/dashboard_head.php'; ?>
<style>
:root { --payroll-primary: #1a237e; --payroll-accent: #283593; }
.section-nav { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
.section-nav .sn-btn { padding: 8px 16px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 13px; font-weight: 500; cursor: pointer; transition: all .2s; text-decoration: none; }
.section-nav .sn-btn:hover { border-color: var(--payroll-primary); color: var(--payroll-primary); background: #eef2ff; }
.section-nav .sn-btn.active { background: var(--payroll-primary); color: #fff; border-color: var(--payroll-primary); }
.payroll-section { display: none; }
.payroll-section.active { display: block; }
.kpi-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; border-left: 4px solid var(--payroll-primary); transition: all .2s; }
.kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.kpi-card .num { font-size: 1.6rem; font-weight: 700; color: var(--payroll-primary); margin: 0; }
.kpi-card .lbl { font-size: 13px; color: #6b7280; margin: 0; }
.form-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; }
.form-card .card-hd { background: #f8fafc; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; border-radius: 12px 12px 0 0; font-weight: 600; color: var(--payroll-primary); font-size: 14px; }
.form-card .card-bd { padding: 20px; }
.form-card label { font-size: 13px; color: #4b5563; margin-bottom: 4px; font-weight: 500; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; padding: 12px; border-bottom: 2px solid #e2e8f0; }
.data-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.badge-payroll { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-active { background: #dcfce7; color: #166534; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #dbeafe; color: #1e40af; }
.badge-paid { background: #e0e7ff; color: #3730a3; }
.badge-draft { background: #f3f4f6; color: #4b5563; }
.badge-closed { background: #fce7f3; color: #9d174d; }
@media (max-width: 768px) { .section-nav { gap: 4px; } .section-nav .sn-btn { font-size: 12px; padding: 6px 12px; } }
</style>
</head>
<body>
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content" style="margin-left:270px;padding:24px">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 style="color:var(--payroll-primary);font-weight:700;margin:0"><i class="fas fa-money-check-alt me-2"></i>Payroll Management</h1>
            <p class="text-muted mb-0">Enterprise payroll system — processing, approvals, payslips & reporting</p>
        </div>
        <div class="text-end small text-muted">
            <div id="payrollClock"></div>
            <div><?= htmlspecialchars($userRole) ?></div>
        </div>
    </div>

    <?php if ($msg = $_SESSION['success'] ?? ''): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($msg) ?></div><?php unset($_SESSION['success']); endif; ?>
    <?php if ($err = $_SESSION['error'] ?? ''): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($err) ?></div><?php unset($_SESSION['error']); endif; ?>

    <!-- Section Navigation -->
    <div class="section-nav" id="sectionNav">
        <a href="#dashboard" class="sn-btn active" data-section="dashboard"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
        <a href="#employees" class="sn-btn" data-section="employees"><i class="fas fa-users me-1"></i>Employees</a>
        <a href="#allowances" class="sn-btn" data-section="allowances"><i class="fas fa-plus-circle me-1"></i>Allowances</a>
        <a href="#deductions" class="sn-btn" data-section="deductions"><i class="fas fa-minus-circle me-1"></i>Deductions</a>
        <a href="#overtime" class="sn-btn" data-section="overtime"><i class="fas fa-clock me-1"></i>Overtime</a>
        <a href="#bonus" class="sn-btn" data-section="bonus"><i class="fas fa-gift me-1"></i>Bonus</a>
        <a href="#loans" class="sn-btn" data-section="loans"><i class="fas fa-hand-holding-usd me-1"></i>Loans</a>
        <a href="#periods" class="sn-btn" data-section="periods"><i class="fas fa-calendar me-1"></i>Periods</a>
        <a href="#processing" class="sn-btn" data-section="processing"><i class="fas fa-cogs me-1"></i>Processing</a>
        <a href="#approvals" class="sn-btn" data-section="approvals"><i class="fas fa-check-double me-1"></i>Approvals</a>
        <a href="#payslips" class="sn-btn" data-section="payslips"><i class="fas fa-file-invoice me-1"></i>Payslips</a>
        <a href="#payments" class="sn-btn" data-section="payments"><i class="fas fa-money-bill me-1"></i>Payments</a>
        <a href="#reports" class="sn-btn" data-section="reports"><i class="fas fa-chart-bar me-1"></i>Reports</a>
        <a href="#settings" class="sn-btn" data-section="settings"><i class="fas fa-cog me-1"></i>Settings</a>
        <a href="#leave" class="sn-btn" data-section="leave"><i class="fas fa-calendar-minus me-1"></i>Leave</a>
    </div>

    <!-- ══════════════════ SECTION: DASHBOARD ══════════════════ -->
    <div class="payroll-section active" id="section-dashboard">
        <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Payroll Overview</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6"><div class="kpi-card"><p class="num" id="kpiEmployees"><?= $stats['total_employees'] ?></p><p class="lbl">Active Employees</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#16a34a"><p class="num" style="color:#16a34a" id="kpiPeriod"><?= htmlspecialchars($stats['current_period']) ?></p><p class="lbl">Current Period</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#0891b2"><p class="num" style="color:#0891b2" id="kpiGross"><?= formatCurrencyUGX($stats['monthly_gross']) ?></p><p class="lbl">Monthly Gross Pay</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#d97706"><p class="num" style="color:#d97706" id="kpiNet"><?= formatCurrencyUGX($stats['monthly_net']) ?></p><p class="lbl">Monthly Net Pay</p></div></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#7c3aed"><p class="num" style="color:#7c3aed"><?= formatCurrencyUGX($stats['monthly_tax']) ?></p><p class="lbl">PAYE Tax (Month)</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#dc2626"><p class="num" style="color:#dc2626"><?= formatCurrencyUGX($stats['monthly_nssf']) ?></p><p class="lbl">NSSF (Month)</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#059669"><p class="num" style="color:#059669"><?= $stats['pending_approvals'] ?></p><p class="lbl">Pending Approvals</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#2563eb"><p class="num" style="color:#2563eb"><?= count($periods) ?></p><p class="lbl">Total Periods</p></div></div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: EMPLOYEES ══════════════════ -->
    <div class="payroll-section" id="section-employees">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-users me-2"></i>Payroll Employee Profiles</h5>
            <button class="btn btn-sm btn-primary" onclick="showEmployeeModal()"><i class="fas fa-plus me-1"></i>Add Profile</button>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="employeeTable">
                <thead><tr><th>#</th><th>Name</th><th>Position</th><th>Type</th><th>Monthly Salary</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
<?php foreach ($employees as $i => $e): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($e['full_name'] ?? '') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($e['position'] ?? '') ?></td>
                        <td><span class="badge-payroll badge-<?= ($e['employment_type'] ?? '') === 'permanent' ? 'active' : 'draft' ?>"><?= htmlspecialchars($e['employment_type'] ?? '') ?></span></td>
                        <td><?= formatCurrencyUGX($e['monthly_salary'] ?? 0) ?></td>
                        <td class="small"><?= htmlspecialchars($e['payment_method'] ?? '') ?></td>
                        <td><span class="badge-payroll badge-<?= ($e['payroll_status'] ?? '') === 'active' ? 'active' : 'closed' ?>"><?= htmlspecialchars($e['payroll_status'] ?? '') ?></span></td>
                        <td><button class="btn btn-sm btn-outline-primary" onclick="editEmployee(<?= $e['id'] ?>)"><i class="fas fa-edit"></i></button></td>
                    </tr>
<?php endforeach; if (empty($employees)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">No payroll profiles yet.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══════════════════ SECTION: ALLOWANCES ══════════════════ -->
    <div class="payroll-section" id="section-allowances">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-plus-circle me-2"></i>Assign Allowance</div>
                    <div class="card-bd">
                        <form method="POST" action="handlers/payroll_handler.php">
                            <div class="mb-3"><label>Employee *</label>
                                <select name="payroll_employee_id" class="form-control" required>
                                    <option value="">-- Select --</option>
<?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
<?php endforeach; ?>
                                </select></div>
                            <div class="mb-3"><label>Allowance Type *</label>
                                <select name="allowance_type_id" class="form-control" required>
                                    <option value="">-- Select --</option>
<?php
$allowTypes = $payrollConn ? $payrollConn->query("SELECT id, allowance_name FROM payroll_allowance_types WHERE is_active=1 ORDER BY display_order") : null;
if ($allowTypes) while ($at = $allowTypes->fetch_assoc()):
?>
                                    <option value="<?= $at['id'] ?>"><?= htmlspecialchars($at['allowance_name']) ?></option>
<?php endwhile; ?>
                                </select></div>
                            <div class="mb-3"><label>Amount (UGX) *</label><input type="number" name="amount" class="form-control" step="0.01" required></div>
                            <div class="mb-3 form-check"><input type="checkbox" name="is_taxable" class="form-check-input" checked><label class="form-check-label">Taxable</label></div>
                            <div class="mb-3 form-check"><input type="checkbox" name="is_recurring" class="form-check-input" checked><label class="form-check-label">Recurring</label></div>
                            <div class="mb-3"><label>Effective From</label><input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <input type="hidden" name="action" value="assign_allowance">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Assign</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-list me-2"></i>Active Allowances</div>
                    <div class="card-bd p-0">
                        <table class="data-table">
                            <thead><tr><th>Employee</th><th>Allowance</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
<?php
$activeAlls = $payrollConn ? $payrollConn->query("SELECT pea.*, pat.allowance_name, s.full_name FROM payroll_employee_allowances pea JOIN payroll_allowance_types pat ON pea.allowance_type_id=pat.id JOIN payroll_employees pe ON pea.payroll_employee_id=pe.id JOIN staff s ON pe.staff_id=s.id WHERE pea.status='active' LIMIT 20") : null;
if ($activeAlls) while ($aa = $activeAlls->fetch_assoc()):
?>
                            <tr>
                                <td class="small"><?= htmlspecialchars($aa['full_name']) ?></td>
                                <td><?= htmlspecialchars($aa['allowance_name']) ?></td>
                                <td><?= formatCurrencyUGX($aa['amount']) ?></td>
                                <td><span class="badge-payroll badge-active">Active</span></td>
                                <td>
                                    <form method="POST" action="handlers/payroll_handler.php" style="display:inline">
                                        <input type="hidden" name="allowance_id" value="<?= $aa['id'] ?>">
                                        <input type="hidden" name="action" value="remove_allowance">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this allowance?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
<?php endwhile; if (!$activeAlls || $activeAlls->num_rows === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No active allowances.</td></tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: DEDUCTIONS ══════════════════ -->
    <div class="payroll-section" id="section-deductions">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-minus-circle me-2"></i>Assign Deduction</div>
                    <div class="card-bd">
                        <form method="POST" action="handlers/payroll_handler.php">
                            <div class="mb-3"><label>Employee *</label>
                                <select name="payroll_employee_id" class="form-control" required>
                                    <option value="">-- Select --</option>
<?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
<?php endforeach; ?>
                                </select></div>
                            <div class="mb-3"><label>Deduction Type *</label>
                                <select name="deduction_type_id" class="form-control" required>
                                    <option value="">-- Select --</option>
<?php
$dedTypes = $payrollConn ? $payrollConn->query("SELECT id, deduction_name FROM payroll_deduction_types WHERE is_active=1 ORDER BY display_order") : null;
if ($dedTypes) while ($dt = $dedTypes->fetch_assoc()):
?>
                                    <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['deduction_name']) ?></option>
<?php endwhile; ?>
                                </select></div>
                            <div class="mb-3"><label>Amount (UGX) *</label><input type="number" name="amount" class="form-control" step="0.01" required></div>
                            <div class="mb-3 form-check"><input type="checkbox" name="is_recurring" class="form-check-input" checked><label class="form-check-label">Recurring</label></div>
                            <div class="mb-3"><label>Effective From</label><input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <input type="hidden" name="action" value="assign_deduction">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Assign</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-list me-2"></i>Active Deductions</div>
                    <div class="card-bd p-0">
                        <table class="data-table">
                            <thead><tr><th>Employee</th><th>Deduction</th><th>Amount</th><th>Type</th><th>Action</th></tr></thead>
                            <tbody>
<?php
$activeDeds = $payrollConn ? $payrollConn->query("SELECT ped.*, pdt.deduction_name, pdt.is_statutory, s.full_name FROM payroll_employee_deductions ped JOIN payroll_deduction_types pdt ON ped.deduction_type_id=pdt.id JOIN payroll_employees pe ON ped.payroll_employee_id=pe.id JOIN staff s ON pe.staff_id=s.id WHERE ped.status='active' LIMIT 20") : null;
if ($activeDeds) while ($ad = $activeDeds->fetch_assoc()):
?>
                            <tr>
                                <td class="small"><?= htmlspecialchars($ad['full_name']) ?></td>
                                <td><?= htmlspecialchars($ad['deduction_name']) ?></td>
                                <td><?= formatCurrencyUGX($ad['amount']) ?></td>
                                <td><span class="badge-payroll badge-<?= $ad['is_statutory'] ? 'approved' : 'draft' ?>"><?= $ad['is_statutory'] ? 'Statutory' : 'Voluntary' ?></span></td>
                                <td>
                                    <form method="POST" action="handlers/payroll_handler.php" style="display:inline">
                                        <input type="hidden" name="deduction_id" value="<?= $ad['id'] ?>">
                                        <input type="hidden" name="action" value="remove_deduction">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this deduction?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
<?php endwhile; if (!$activeDeds || $activeDeds->num_rows === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No active deductions.</td></tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: OVERTIME ══════════════════ -->
    <div class="payroll-section" id="section-overtime">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-clock me-2"></i>Record Overtime</div>
                    <div class="card-bd">
                        <form method="POST" action="handlers/payroll_handler.php">
                            <div class="mb-3"><label>Employee *</label>
                                <select name="payroll_employee_id" class="form-control" required>
                                    <option value="">-- Select --</option>
<?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
<?php endforeach; ?>
                                </select></div>
                            <div class="mb-3"><label>Overtime Type *</label>
                                <select name="overtime_type" class="form-control">
                                    <option value="normal">Normal (1.5x)</option>
                                    <option value="weekend">Weekend (2.0x)</option>
                                    <option value="holiday">Holiday (2.5x)</option>
                                    <option value="night">Night (2.0x)</option>
                                </select></div>
                            <div class="mb-3"><label>Hours Worked *</label><input type="number" name="hours_worked" class="form-control" step="0.5" required></div>
                            <div class="mb-3"><label>Date</label><input type="date" name="overtime_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                            <input type="hidden" name="action" value="add_overtime">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Record</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-list me-2"></i>Pending Overtime</div>
                    <div class="card-bd p-0">
                        <table class="data-table">
                            <thead><tr><th>Employee</th><th>Type</th><th>Hours</th><th>Amount</th><th>Date</th><th>Action</th></tr></thead>
                            <tbody>
<?php
$pendingOT = $payrollConn ? $payrollConn->query("SELECT po.*, s.full_name FROM payroll_overtime po JOIN payroll_employees pe ON po.payroll_employee_id=pe.id JOIN staff s ON pe.staff_id=s.id WHERE po.status='pending' LIMIT 20") : null;
if ($pendingOT) while ($ot = $pendingOT->fetch_assoc()):
?>
                            <tr>
                                <td class="small"><?= htmlspecialchars($ot['full_name']) ?></td>
                                <td><?= $ot['overtime_type'] ?></td>
                                <td><?= $ot['hours_worked'] ?></td>
                                <td><?= formatCurrencyUGX($ot['total_amount'] ?? ($ot['hours_worked'] * $ot['rate_multiplier'] * $ot['hourly_rate'])) ?></td>
                                <td class="small"><?= $ot['overtime_date'] ?></td>
                                <td>
                                    <form method="POST" action="handlers/payroll_handler.php" style="display:inline">
                                        <input type="hidden" name="overtime_id" value="<?= $ot['id'] ?>">
                                        <input type="hidden" name="action" value="approve_overtime">
                                        <button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button>
                                    </form>
                                </td>
                            </tr>
<?php endwhile; if (!$pendingOT || $pendingOT->num_rows === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">No pending overtime.</td></tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: BONUS ══════════════════ -->
    <div class="payroll-section" id="section-bonus">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-gift me-2"></i>Record Bonus</div>
                    <div class="card-bd">
                        <form method="POST" action="handlers/payroll_handler.php">
                            <div class="mb-3"><label>Employee *</label>
                                <select name="payroll_employee_id" class="form-control" required>
                                    <option value="">-- Select --</option>
<?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
<?php endforeach; ?>
                                </select></div>
                            <div class="mb-3"><label>Bonus Name *</label><input type="text" name="bonus_name" class="form-control" required></div>
                            <div class="mb-3"><label>Type</label>
                                <select name="bonus_type" class="form-control">
                                    <option value="performance">Performance</option>
                                    <option value="commission">Commission</option>
                                    <option value="annual">Annual</option>
                                    <option value="appreciation">Appreciation</option>
                                    <option value="productivity">Productivity</option>
                                    <option value="one_time">One Time</option>
                                    <option value="other">Other</option>
                                </select></div>
                            <div class="mb-3"><label>Amount (UGX) *</label><input type="number" name="amount" class="form-control" step="0.01" required></div>
                            <div class="mb-3 form-check"><input type="checkbox" name="is_taxable" class="form-check-input" checked><label class="form-check-label">Taxable</label></div>
                            <div class="mb-3"><label>Date</label><input type="date" name="bonus_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <input type="hidden" name="action" value="add_bonus">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Record</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-list me-2"></i>Bonus Records</div>
                    <div class="card-bd p-0">
                        <table class="data-table">
                            <thead><tr><th>Employee</th><th>Name</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
<?php
$bonusRows = $payrollConn ? $payrollConn->query("SELECT pb.*, s.full_name FROM payroll_bonus pb JOIN payroll_employees pe ON pb.payroll_employee_id=pe.id JOIN staff s ON pe.staff_id=s.id ORDER BY pb.created_at DESC LIMIT 20") : null;
if ($bonusRows) while ($b = $bonusRows->fetch_assoc()):
?>
                            <tr>
                                <td class="small"><?= htmlspecialchars($b['full_name']) ?></td>
                                <td><?= htmlspecialchars($b['bonus_name']) ?></td>
                                <td class="small"><?= $b['bonus_type'] ?></td>
                                <td><?= formatCurrencyUGX($b['amount']) ?></td>
                                <td><span class="badge-payroll badge-<?= $b['status'] === 'paid' ? 'paid' : ($b['status'] === 'approved' ? 'approved' : 'pending') ?>"><?= $b['status'] ?></span></td>
                            </tr>
<?php endwhile; if (!$bonusRows || $bonusRows->num_rows === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No bonus records.</td></tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: LOANS ══════════════════ -->
    <div class="payroll-section" id="section-loans">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-hand-holding-usd me-2"></i>New Loan/Advance</div>
                    <div class="card-bd">
                        <form method="POST" action="handlers/payroll_handler.php">
                            <div class="mb-3"><label>Employee *</label>
                                <select name="payroll_employee_id" class="form-control" required>
                                    <option value="">-- Select --</option>
<?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
<?php endforeach; ?>
                                </select></div>
                            <div class="mb-3"><label>Type</label>
                                <select name="loan_type" class="form-control">
                                    <option value="staff_loan">Staff Loan</option>
                                    <option value="salary_advance">Salary Advance</option>
                                    <option value="emergency">Emergency Loan</option>
                                    <option value="other">Other</option>
                                </select></div>
                            <div class="mb-3"><label>Principal Amount (UGX) *</label><input type="number" name="principal_amount" class="form-control" step="0.01" required></div>
                            <div class="mb-3"><label>Interest Rate (%)</label><input type="number" name="interest_rate" class="form-control" step="0.01" value="0"></div>
                            <div class="mb-3"><label>Installments</label><input type="number" name="installments" class="form-control" value="1" min="1"></div>
                            <div class="mb-3"><label>Loan Date</label><input type="date" name="loan_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <input type="hidden" name="action" value="add_loan">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Record Loan</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-list me-2"></i>Active Loans</div>
                    <div class="card-bd p-0">
                        <table class="data-table">
                            <thead><tr><th>Employee</th><th>Loan #</th><th>Total</th><th>Paid</th><th>Balance</th><th>Installment</th><th>Status</th></tr></thead>
                            <tbody>
<?php
$loanRows = $payrollConn ? $payrollConn->query("SELECT pl.*, s.full_name FROM payroll_loans pl JOIN payroll_employees pe ON pl.payroll_employee_id=pe.id JOIN staff s ON pe.staff_id=s.id WHERE pl.status IN ('active','pending') LIMIT 20") : null;
if ($loanRows) while ($ln = $loanRows->fetch_assoc()):
?>
                            <tr>
                                <td class="small"><?= htmlspecialchars($ln['full_name']) ?></td>
                                <td class="small"><?= htmlspecialchars($ln['loan_number']) ?></td>
                                <td><?= formatCurrencyUGX($ln['total_amount'] ?? ($ln['principal_amount'] ?? 0)) ?></td>
                                <td><?= formatCurrencyUGX($ln['amount_paid'] ?? 0) ?></td>
                                <td><strong><?= formatCurrencyUGX($ln['balance'] ?? $ln['principal_amount']) ?></strong></td>
                                <td><?= formatCurrencyUGX($ln['installment_amount']) ?></td>
                                <td>
                                    <span class="badge-payroll badge-<?= $ln['status'] === 'active' ? 'active' : 'pending' ?>"><?= $ln['status'] ?></span>
<?php if ($ln['status'] === 'pending'): ?>
                                    <form method="POST" action="handlers/payroll_handler.php" style="display:inline">
                                        <input type="hidden" name="loan_id" value="<?= $ln['id'] ?>">
                                        <input type="hidden" name="action" value="approve_loan">
                                        <button class="btn btn-sm btn-outline-success ms-1"><i class="fas fa-check"></i></button>
                                    </form>
<?php endif; ?>
                                </td>
                            </tr>
<?php endwhile; if (!$loanRows || $loanRows->num_rows === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">No active loans.</td></tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: PERIODS ══════════════════ -->
    <div class="payroll-section" id="section-periods">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-calendar-plus me-2"></i>Create Period</div>
                    <div class="card-bd">
                        <form method="POST" action="handlers/payroll_handler.php">
                            <div class="mb-3"><label>Month *</label>
                                <select name="month" class="form-control" required>
<?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $m == date('m') ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
<?php endfor; ?>
                                </select></div>
                            <div class="mb-3"><label>Year *</label>
                                <select name="year" class="form-control" required>
<?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
<?php endfor; ?>
                                </select></div>
                            <input type="hidden" name="action" value="create_period">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i>Create Period</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-calendar-alt me-2"></i>Payroll Periods</div>
                    <div class="card-bd p-0">
                        <table class="data-table">
                            <thead><tr><th>Period</th><th>Code</th><th>Start</th><th>End</th><th>Pay Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
<?php foreach ($periods as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['period_name']) ?></td>
                                <td class="small"><?= htmlspecialchars($p['period_code']) ?></td>
                                <td class="small"><?= $p['start_date'] ?></td>
                                <td class="small"><?= $p['end_date'] ?></td>
                                <td class="small"><?= $p['payment_date'] ?></td>
                                <td><span class="badge-payroll badge-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
                                <td>
<?php if ($p['status'] === 'draft'): ?>
                                    <form method="POST" action="handlers/payroll_handler.php" style="display:inline">
                                        <input type="hidden" name="period_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="open_period">
                                        <button class="btn btn-sm btn-outline-success" title="Open Period"><i class="fas fa-unlock"></i></button>
                                    </form>
<?php elseif ($p['status'] === 'open'): ?>
                                    <span class="small text-muted">Ready for processing</span>
<?php endif; ?>
                                </td>
                            </tr>
<?php endforeach; if (empty($periods)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">No periods created yet.</td></tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: PROCESSING ══════════════════ -->
    <div class="payroll-section" id="section-processing">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-cogs me-2"></i>Process Payroll Run</div>
                    <div class="card-bd">
                        <form method="POST" action="handlers/payroll_handler.php">
                            <div class="mb-3"><label>Payroll Period *</label>
                                <select name="payroll_period_id" class="form-control" required>
                                    <option value="">-- Select Open Period --</option>
<?php foreach ($periods as $p): if ($p['status'] === 'open' || $p['status'] === 'draft'): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['period_name']) ?> (<?= $p['status'] ?>)</option>
<?php endif; endforeach; ?>
                                </select></div>
                            <div class="alert alert-info small py-2 mb-3"><i class="fas fa-info-circle me-1"></i>Processes payroll for all active employees with computed PAYE, NSSF, allowances, deductions, overtime, bonuses, and loan installments.</div>
                            <input type="hidden" name="action" value="process_payroll">
                            <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Process payroll for all active employees?')"><i class="fas fa-play me-1"></i>Process Payroll</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-history me-2"></i>Recent Payroll Runs</div>
                    <div class="card-bd p-0">
                        <table class="data-table">
                            <thead><tr><th>Run #</th><th>Period</th><th>Employees</th><th>Gross</th><th>Net</th><th>Tax</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
<?php
$runs = $payrollConn ? $payrollConn->query("SELECT pr.*, pp.period_name FROM payroll_runs pr JOIN payroll_periods pp ON pr.payroll_period_id=pp.id ORDER BY pr.created_at DESC LIMIT 20") : null;
if ($runs) while ($run = $runs->fetch_assoc()):
?>
                            <tr>
                                <td class="small"><?= htmlspecialchars($run['run_number']) ?></td>
                                <td class="small"><?= htmlspecialchars($run['period_name']) ?></td>
                                <td><?= $run['total_employees'] ?></td>
                                <td><?= formatCurrencyUGX($run['total_gross']) ?></td>
                                <td><strong><?= formatCurrencyUGX($run['total_net']) ?></strong></td>
                                <td><?= formatCurrencyUGX($run['total_tax']) ?></td>
                                <td><span class="badge-payroll badge-<?= $run['status'] === 'completed' ? 'active' : ($run['status'] === 'processing' ? 'pending' : 'draft') ?>"><?= $run['status'] ?></span></td>
                                <td>
<?php if ($run['status'] === 'completed'): ?>
                                    <form method="POST" action="handlers/payroll_handler.php" style="display:inline">
                                        <input type="hidden" name="payroll_run_id" value="<?= $run['id'] ?>">
                                        <input type="hidden" name="action" value="generate_payslips">
                                        <button class="btn btn-sm btn-outline-primary" title="Generate Payslips"><i class="fas fa-file-invoice"></i></button>
                                    </form>
<?php endif; ?>
                                </td>
                            </tr>
<?php endwhile; if (!$runs || $runs->num_rows === 0): ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">No payroll runs.</td></tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: APPROVALS ══════════════════ -->
    <div class="payroll-section" id="section-approvals">
        <div class="form-card">
            <div class="card-hd"><i class="fas fa-check-double me-2"></i>Approval Dashboard</div>
            <div class="card-bd p-0">
                <table class="data-table">
                    <thead><tr><th>Run #</th><th>Period</th><th>Total Net</th><th>Processed By</th><th>Submitted</th><th>Approve</th><th>Authorize</th><th>Reject</th></tr></thead>
                    <tbody>
<?php
$pendingRuns = $payrollConn ? $payrollConn->query("SELECT pr.*, pp.period_name FROM payroll_runs pr JOIN payroll_periods pp ON pr.payroll_period_id=pp.id WHERE pr.status='completed' ORDER BY pr.created_at DESC LIMIT 10") : null;
if ($pendingRuns) while ($apr = $pendingRuns->fetch_assoc()):
?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($apr['run_number']) ?></td>
                            <td><?= htmlspecialchars($apr['period_name']) ?></td>
                            <td><strong><?= formatCurrencyUGX($apr['total_net']) ?></strong></td>
                            <td class="small"><?= $apr['processed_by'] ?></td>
                            <td class="small"><?= $apr['processed_at'] ?></td>
                            <td>
                                <form method="POST" action="handlers/payroll_handler.php" style="display:inline">
                                    <input type="hidden" name="payroll_run_id" value="<?= $apr['id'] ?>">
                                    <input type="hidden" name="step" value="Payroll Officer Verification">
                                    <input type="hidden" name="action" value="approve_run">
                                    <button class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this run?')"><i class="fas fa-check"></i> Verify</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="handlers/payroll_handler.php" style="display:inline">
                                    <input type="hidden" name="payroll_run_id" value="<?= $apr['id'] ?>">
                                    <input type="hidden" name="comments" value="Authorized for payment">
                                    <input type="hidden" name="action" value="authorize_run">
                                    <button class="btn btn-sm btn-outline-primary" onclick="return confirm('Authorize for payment?')"><i class="fas fa-check-double"></i> Authorize</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="handlers/payroll_handler.php" style="display:inline">
                                    <input type="hidden" name="payroll_run_id" value="<?= $apr['id'] ?>">
                                    <input type="hidden" name="comments" value="Rejected">
                                    <input type="hidden" name="action" value="reject_run">
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this run?')"><i class="fas fa-times"></i></button>
                                </form>
                            </td>
                        </tr>
<?php endwhile; if (!$pendingRuns || $pendingRuns->num_rows === 0): ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">No pending approvals.</td></tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: PAYSLIPS ══════════════════ -->
    <div class="payroll-section" id="section-payslips">
        <div class="form-card">
            <div class="card-hd"><i class="fas fa-file-invoice me-2"></i>Generated Payslips</div>
            <div class="card-bd p-0">
                <table class="data-table">
                    <thead><tr><th>Payslip #</th><th>Employee</th><th>Period</th><th>View</th><th>Printed</th><th>Emailed</th></tr></thead>
                    <tbody>
<?php
$payslips = $payrollConn ? $payrollConn->query("SELECT pps.*, s.full_name, pp.period_name FROM payroll_payslips pps JOIN staff s ON pps.staff_id=s.id JOIN payroll_runs pr ON pps.payroll_run_id=pr.id JOIN payroll_periods pp ON pr.payroll_period_id=pp.id ORDER BY pps.created_at DESC LIMIT 30") : null;
if ($payslips) while ($ps = $payslips->fetch_assoc()):
?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($ps['payslip_number']) ?></td>
                            <td><?= htmlspecialchars($ps['full_name']) ?></td>
                            <td class="small"><?= htmlspecialchars($ps['period_name']) ?></td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="viewPayslip(<?= $ps['id'] ?>)"><i class="fas fa-eye"></i></button></td>
                            <td><?= $ps['is_printed'] ? '<i class="fas fa-check text-success"></i>' : '-' ?></td>
                            <td><?= $ps['is_emailed'] ? '<i class="fas fa-check text-success"></i>' : '-' ?></td>
                        </tr>
<?php endwhile; if (!$payslips || $payslips->num_rows === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No payslips generated yet.</td></tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Payslip View Modal -->
        <div class="modal fade" id="payslipModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Payslip</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="payslipContent"></div></div></div></div>
    </div>

    <!-- ══════════════════ SECTION: PAYMENTS ══════════════════ -->
    <div class="payroll-section" id="section-payments">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-money-bill me-2"></i>Record Payment</div>
                    <div class="card-bd">
                        <form method="POST" action="handlers/payroll_handler.php">
                            <div class="mb-3"><label>Payroll Run *</label>
                                <select name="payroll_run_id" class="form-control" required>
                                    <option value="">-- Select Approved Run --</option>
<?php
$paidRuns = $payrollConn ? $payrollConn->query("SELECT pr.id, pr.run_number, pp.period_name FROM payroll_runs pr JOIN payroll_periods pp ON pr.payroll_period_id=pp.id WHERE pr.status='completed' ORDER BY pr.created_at DESC") : null;
if ($paidRuns) while ($pr2 = $paidRuns->fetch_assoc()):
?>
                                    <option value="<?= $pr2['id'] ?>"><?= htmlspecialchars($pr2['run_number']) ?> - <?= htmlspecialchars($pr2['period_name']) ?></option>
<?php endwhile; ?>
                                </select></div>
                            <div class="mb-3"><label>Payment Method</label>
                                <select name="payment_method" class="form-control">
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="cash">Cash</option>
                                </select></div>
                            <div class="mb-3"><label>Payment Date</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                            <div class="mb-3"><label>Reference Number</label><input type="text" name="reference_number" class="form-control"></div>
                            <input type="hidden" name="action" value="record_payment">
                            <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Record payment?')"><i class="fas fa-check me-1"></i>Record Payment</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-list me-2"></i>Payment History</div>
                    <div class="card-bd p-0">
                        <table class="data-table">
                            <thead><tr><th>Reference</th><th>Method</th><th>Amount</th><th>Employees</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
<?php
$payments = $payrollConn ? $payrollConn->query("SELECT * FROM payroll_payments ORDER BY created_at DESC LIMIT 20") : null;
if ($payments) while ($pmt = $payments->fetch_assoc()):
?>
                            <tr>
                                <td class="small"><?= htmlspecialchars($pmt['reference_number'] ?? '-') ?></td>
                                <td class="small"><?= str_replace('_', ' ', $pmt['payment_method']) ?></td>
                                <td><?= formatCurrencyUGX($pmt['total_amount']) ?></td>
                                <td><?= $pmt['employee_count'] ?></td>
                                <td class="small"><?= $pmt['payment_date'] ?></td>
                                <td><span class="badge-payroll badge-<?= $pmt['status'] === 'completed' ? 'paid' : 'pending' ?>"><?= $pmt['status'] ?></span></td>
                            </tr>
<?php endwhile; if (!$payments || $payments->num_rows === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">No payments recorded.</td></tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: REPORTS ══════════════════ -->
    <div class="payroll-section" id="section-reports">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-chart-bar me-2"></i>Payroll Reports</div>
                    <div class="card-bd">
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action" onclick="alert('Report generation coming soon.')"><i class="fas fa-file-pdf me-2 text-danger"></i>Payroll Summary Report</a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="alert('Report generation coming soon.')"><i class="fas fa-file-excel me-2 text-success"></i>Employee Payroll Register</a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="alert('Report generation coming soon.')"><i class="fas fa-file-invoice me-2 text-primary"></i>PAYE Tax Report</a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="alert('Report generation coming soon.')"><i class="fas fa-file-invoice me-2 text-info"></i>NSSF Schedule Report</a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="alert('Report generation coming soon.')"><i class="fas fa-university me-2 text-warning"></i>Bank Payment Schedule</a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="alert('Report generation coming soon.')"><i class="fas fa-chart-pie me-2 text-secondary"></i>Cost Center Analysis</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-card">
                    <div class="card-hd"><i class="fas fa-history me-2"></i>Generated Reports</div>
                    <div class="card-bd p-0">
                        <table class="data-table">
                            <thead><tr><th>Report Name</th><th>Type</th><th>Period</th><th>Generated</th><th>Download</th></tr></thead>
                            <tbody>
<?php
$reports = $payrollConn ? $payrollConn->query("SELECT * FROM payroll_reports ORDER BY generated_at DESC LIMIT 10") : null;
if ($reports) while ($rp = $reports->fetch_assoc()):
?>
                            <tr>
                                <td><?= htmlspecialchars($rp['report_name']) ?></td>
                                <td class="small"><?= htmlspecialchars($rp['report_type']) ?></td>
                                <td class="small"><?= $rp['report_period'] ?? '-' ?></td>
                                <td class="small"><?= $rp['generated_at'] ?></td>
                                <td><?= $rp['file_path'] ? '<a href="'.htmlspecialchars($rp['file_path']).'" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a>' : '-' ?></td>
                            </tr>
<?php endwhile; if (!$reports || $reports->num_rows === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No reports generated.</td></tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ SECTION: SETTINGS ══════════════════ -->
    <div class="payroll-section" id="section-settings">
        <div class="form-card">
            <div class="card-hd"><i class="fas fa-cog me-2"></i>Payroll Settings</div>
            <div class="card-bd">
                <form method="POST" action="handlers/payroll_handler.php">
                    <div class="row g-3">
<?php
$settings = $payrollConn ? $payrollConn->query("SELECT * FROM payroll_settings WHERE setting_group IN ('general','statutory','payslip') ORDER BY setting_group, id") : null;
if ($settings) while ($s = $settings->fetch_assoc()):
?>
                        <div class="col-md-4">
                            <label class="small text-muted"><?= htmlspecialchars($s['description'] ?: $s['setting_key']) ?></label>
                            <input type="text" name="setting_<?= htmlspecialchars($s['setting_key']) ?>" class="form-control" value="<?= htmlspecialchars($s['setting_value'] ?? '') ?>">
                        </div>
<?php endwhile; ?>
                    </div>
                    <div class="mt-3">
                        <input type="hidden" name="action" value="save_settings">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- LEAVE MANAGEMENT -->
<div class="payroll-section" id="section-leave">
<div class="row g-3 mb-3">
<div class="col-md-8">
<div class="form-card">
<div class="card-hd d-flex justify-content-between align-items-center">
<span><i class="fas fa-calendar-minus me-2"></i>Leave Management</span>
<button class="btn btn-sm btn-primary" onclick="document.getElementById('leaveModal').style.display='flex'"><i class="fas fa-plus me-1"></i>New Leave Request</button>
</div>
<div class="card-bd">
<?php
$leaveTypes = $payrollConn ? $payrollConn->query("SELECT * FROM leave_types WHERE is_active=1 ORDER BY type_name") : null;
$leaveRequests = $payrollConn ? $payrollConn->query("SELECT lr.*, s.full_name, lt.type_name FROM leave_requests lr JOIN staff s ON lr.staff_id=s.id LEFT JOIN leave_types lt ON lr.leave_type_id=lt.id ORDER BY lr.created_at DESC LIMIT 30") : null;
if (!$leaveRequests) $leaveRequests = $payrollConn ? $payrollConn->query("SELECT sl.*, s.full_name FROM staff_leave sl JOIN staff s ON sl.staff_id=s.id ORDER BY sl.created_at DESC LIMIT 30") : null;
?>
<table class="data-table">
<thead><tr><th>Employee</th><th>Leave Type</th><th>Start</th><th>End</th><th>Days</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php if ($leaveRequests) while ($lr = $leaveRequests->fetch_assoc()):
$days = 0;
if (!empty($lr['start_date']) && !empty($lr['end_date'])) { $d1 = new DateTime($lr['start_date']); $d2 = new DateTime($lr['end_date']); $days = $d2->diff($d1)->days + 1; }
if ($days === 0 && !empty($lr['days_taken'])) $days = $lr['days_taken'];
$leaveName = $lr['type_name'] ?? $lr['leave_type'] ?? '-';
$status = $lr['status'] ?? 'Pending';
?>
<tr>
<td><strong><?= htmlspecialchars($lr['full_name'] ?? '') ?></strong></td>
<td><?= htmlspecialchars($leaveName) ?></td>
<td><?= !empty($lr['start_date']) ? date('M j, Y', strtotime($lr['start_date'])) : '-' ?></td>
<td><?= !empty($lr['end_date']) ? date('M j, Y', strtotime($lr['end_date'])) : '-' ?></td>
<td><?= $days ?></td>
<td><span class="badge-payroll badge-<?= $status==='Approved'?'active':($status==='Rejected'?'closed':'pending') ?>"><?= htmlspecialchars($status) ?></span></td>
<td>
<?php if ($status === 'Pending'): ?>
<form method="POST" action="handlers/payroll_handler.php" style="display:inline"><input type="hidden" name="action" value="approve_leave"><input type="hidden" name="leave_id" value="<?= $lr['id'] ?>"><button class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button></form>
<form method="POST" action="handlers/payroll_handler.php" style="display:inline"><input type="hidden" name="action" value="reject_leave"><input type="hidden" name="leave_id" value="<?= $lr['id'] ?>"><button class="btn btn-sm btn-danger" title="Reject"><i class="fas fa-times"></i></button></form>
<?php else: ?><small class="text-muted">—</small><?php endif; ?>
</td></tr>
<?php endwhile; if (!$leaveRequests || $leaveRequests->num_rows === 0): ?>
<tr><td colspan="7" class="text-center text-muted py-3">No leave requests found.</td></tr>
<?php endif; ?>
</tbody></table>
</div></div></div>
<div class="col-md-4">
<div class="form-card mb-3">
<div class="card-hd"><i class="fas fa-balance-scale me-2"></i>Leave Balances</div>
<div class="card-bd">
<?php $balances = $payrollConn ? $payrollConn->query("SELECT lb.*, s.full_name, lt.type_name FROM leave_balances lb JOIN staff s ON lb.staff_id=s.id LEFT JOIN leave_types lt ON lb.leave_type_id=lt.id WHERE lb.year=YEAR(CURDATE()) ORDER BY s.full_name") : null; ?>
<table class="data-table">
<thead><tr><th>Employee</th><th>Type</th><th>Total</th><th>Used</th><th>Left</th></tr></thead>
<tbody>
<?php if ($balances) while ($bal = $balances->fetch_assoc()): ?>
<tr><td style="font-size:12px"><?= htmlspecialchars($bal['full_name'] ?? '') ?></td><td style="font-size:12px"><?= htmlspecialchars($bal['type_name'] ?? '') ?></td><td><?= $bal['total_days'] ?? 0 ?></td><td><?= $bal['used_days'] ?? 0 ?></td><td><strong><?= ($bal['remaining_days'] ?? 0) - ($bal['used_days'] ?? 0) ?></strong></td></tr>
<?php endwhile; if (!$balances || $balances->num_rows === 0): ?>
<tr><td colspan="5" class="text-center text-muted py-3">No balances.</td></tr>
<?php endif; ?>
</tbody></table>
</div></div>
<div class="form-card">
<div class="card-hd"><i class="fas fa-plus me-2"></i>Add Leave Type</div>
<div class="card-bd">
<form method="POST" action="handlers/payroll_handler.php">
<input type="hidden" name="action" value="add_leave_type">
<div class="mb-2"><label>Type Name *</label><input type="text" name="type_name" class="form-control" required placeholder="e.g. Annual Leave"></div>
<div class="mb-2"><label>Days Per Year</label><input type="number" name="days_per_year" class="form-control" value="30" min="1"></div>
<div class="mb-2"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
<button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-save me-1"></i>Add Type</button>
</form>
</div></div>
</div></div></div>

<!-- LEAVE REQUEST MODAL -->
<div id="leaveModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center">
<div style="background:#fff;border-radius:12px;width:90%;max-width:500px;padding:24px">
<div class="d-flex justify-content-between align-items-center mb-3"><h5 style="margin:0">New Leave Request</h5><button onclick="document.getElementById('leaveModal').style.display='none'" style="background:none;border:none;font-size:1.2rem">&times;</button></div>
<form method="POST" action="handlers/payroll_handler.php">
<input type="hidden" name="action" value="create_leave_request">
<div class="mb-3"><label>Employee *</label><select name="staff_id" class="form-control" required><option value="">-- Select --</option><?php if ($employees) foreach ($employees as $emp): ?><option value="<?= $emp['staff_id'] ?? $emp['id'] ?>"><?= htmlspecialchars($emp['full_name'] ?? '') ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label>Leave Type *</label><select name="leave_type_id" class="form-control" required><option value="">-- Select --</option><?php if ($leaveTypes) while ($lt = $leaveTypes->fetch_assoc()): ?><option value="<?= $lt['id'] ?>"><?= htmlspecialchars($lt['type_name'] ?? $lt['leave_type_name'] ?? '') ?> (<?= $lt['days_per_year'] ?? 0 ?> days)</option><?php endwhile; ?></select></div>
<div class="row mb-3"><div class="col"><label>Start Date *</label><input type="date" name="start_date" class="form-control" required></div><div class="col"><label>End Date *</label><input type="date" name="end_date" class="form-control" required></div></div>
<div class="mb-3"><label>Reason</label><textarea name="reason" class="form-control" rows="3"></textarea></div>
<div class="text-end"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Submit</button></div>
</form>
</div></div>

<!-- Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="handlers/payroll_handler.php">
        <div class="modal-header"><h5 class="modal-title" id="empModalTitle">Add Payroll Profile</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="profile_id" id="empProfileId" value="0">
            <div class="mb-3"><label>Staff Member *</label>
                <select name="staff_id" id="empStaffId" class="form-control" required>
                    <option value="">-- Select --</option>
<?php
$allStaff = $staffConn ? $staffConn->query("SELECT id, full_name, position FROM staff WHERE status='Active' ORDER BY full_name") : null;
if ($allStaff) while ($st = $allStaff->fetch_assoc()):
?>
                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']) ?> (<?= htmlspecialchars($st['position']) ?>)</option>
<?php endwhile; ?>
                </select></div>
            <div class="mb-3"><label>Employment Type</label>
                <select name="employment_type" id="empType" class="form-control">
                    <option value="permanent">Permanent</option>
                    <option value="contract">Contract</option>
                    <option value="part_time">Part Time</option>
                    <option value="temporary">Temporary</option>
                    <option value="intern">Intern</option>
                </select></div>
            <div class="mb-3"><label>Monthly Salary (UGX) *</label><input type="number" name="monthly_salary" id="empSalary" class="form-control" step="0.01" required></div>
            <div class="mb-3"><label>Payment Method</label>
                <select name="payment_method" id="empPayMethod" class="form-control">
                    <option value="bank">Bank Transfer</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="cheque">Cheque</option>
                    <option value="cash">Cash</option>
                </select></div>
            <div class="mb-3"><label>Bank Name</label><input type="text" name="bank_name" id="empBank" class="form-control"></div>
            <div class="mb-3"><label>Bank Account</label><input type="text" name="bank_account_number" id="empAccount" class="form-control"></div>
            <div class="mb-3"><label>Mobile Money</label><input type="text" name="mobile_money_number" id="empMobile" class="form-control"></div>
            <div class="mb-3"><label>TIN</label><input type="text" name="tin" id="empTin" class="form-control"></div>
            <div class="mb-3"><label>NSSF Number</label><input type="text" name="nssf_number" id="empNssf" class="form-control"></div>
        </div>
        <div class="modal-footer">
            <input type="hidden" name="action" value="create_employee_profile">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
        </div>
    </form>
</div></div></div>

<?php include_once __DIR__ . '/includes/dashboard_footer.php'; ?>

<script>
(function() {
    // ── Section Navigation ──
    var navBtns = document.querySelectorAll('.sn-btn');
    var sections = document.querySelectorAll('.payroll-section');

    function showSection(id) {
        sections.forEach(function(s) { s.classList.remove('active'); });
        navBtns.forEach(function(b) { b.classList.remove('active'); });
        var target = document.getElementById('section-' + id);
        if (target) target.classList.add('active');
        var btn = document.querySelector('.sn-btn[data-section="' + id + '"]');
        if (btn) btn.classList.add('active');
    }

    navBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var section = this.getAttribute('data-section');
            if (section) {
                showSection(section);
                history.replaceState(null, '', '#section-' + section);
            }
        });
    });

    // Restore section from hash on load
    var hash = window.location.hash.replace('#section-', '');
    if (hash && document.getElementById('section-' + hash)) {
        showSection(hash);
    }

    // ── Employee Modal ──
    window.showEmployeeModal = function() {
        document.getElementById('empModalTitle').textContent = 'Add Payroll Profile';
        document.getElementById('empProfileId').value = '0';
        document.getElementById('empStaffId').value = '';
        document.getElementById('empType').value = 'permanent';
        document.getElementById('empSalary').value = '';
        document.getElementById('empPayMethod').value = 'bank';
        document.getElementById('empBank').value = '';
        document.getElementById('empAccount').value = '';
        document.getElementById('empMobile').value = '';
        document.getElementById('empTin').value = '';
        document.getElementById('empNssf').value = '';
        var modal = new bootstrap.Modal(document.getElementById('employeeModal'));
        modal.show();
    };

    window.editEmployee = function(id) {
        // For future AJAX-powered editing — currently redirects to add mode
        alert('Edit mode: ' + id + ' — AJAX loading coming soon.');
    };

    // ── Payslip Viewer ──
    window.viewPayslip = function(id) {
        var content = document.getElementById('payslipContent');
        if (!content) return;
        content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading payslip...</p></div>';
        var modal = new bootstrap.Modal(document.getElementById('payslipModal'));
        modal.show();

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'handlers/payroll_handler.php?action=get_payslip_html&payslip_id=' + id, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var d = JSON.parse(xhr.responseText);
                    if (d.success && d.data) {
                        content.innerHTML = d.data;
                    } else {
                        content.innerHTML = '<div class="alert alert-warning">' + (d.message || 'Payslip not found.') + '</div>';
                    }
                } catch(e) {
                    content.innerHTML = '<div class="alert alert-danger">Invalid response.</div>';
                }
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load payslip.</div>';
            }
        };
        xhr.onerror = function() {
            content.innerHTML = '<div class="alert alert-danger">Network error.</div>';
        };
        xhr.send();
    };

    // ── Live Clock ──
    function updateClock() {
        var el = document.getElementById('payrollClock');
        if (!el) return;
        var now = new Date();
        el.textContent = now.toLocaleDateString('en-UG', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + now.toLocaleTimeString('en-UG');
    }
    updateClock();
    setInterval(updateClock, 1000);
})();
</script>
</body>
</html>
<?php // Connection reuse — don't close?>
