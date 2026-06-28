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

function currency($n) { return 'UGX ' . number_format((float)$n, 0); }
function badge($s) {
    $m = ['active'=>'success','inactive'=>'secondary','fully_paid'=>'success','partially_paid'=>'info','unpaid'=>'warning','overdue'=>'danger','cancelled'=>'dark','pending'=>'warning','approved'=>'success','verified'=>'success'];
    $c = $m[strtolower($s)] ?? 'secondary';
    return '<span class="badge bg-'.$c.'">'.htmlspecialchars($s).'</span>';
}

// ── AJAX handlers ──
if ($ajax === 'search_student' && $sid) {
    header('Content-Type: application/json');
    $data = [];
    try {
        $like = '%' . $sid . '%';
        $q = "SELECT student_id, first_name, surname, program, current_year FROM students WHERE student_id LIKE ? OR first_name LIKE ? OR surname LIKE ? ORDER BY surname LIMIT 20";
        $stmt = $students->prepare($q);
        if ($stmt) { $stmt->bind_param('sss', $like, $like, $like); $stmt->execute(); $r = $stmt->get_result(); while ($row = $r->fetch_assoc()) $data[] = $row; $stmt->close(); }
    } catch (Exception $e) { error_log('search: '.$e->getMessage()); }
    echo json_encode($data); exit;
}

if ($ajax === 'get_fee_balance' && $sid) {
    header('Content-Type: application/json');
    $bal = 0; $inv = 0;
    try {
        $stmt = $staff->prepare("SELECT id, balance FROM student_fee_accounts WHERE student_id = ? AND status NOT IN ('fully_paid','cancelled') ORDER BY id DESC LIMIT 1");
        if ($stmt) { $stmt->bind_param('s', $sid); $stmt->execute(); $r = $stmt->get_result(); if ($row = $r->fetch_assoc()) { $bal = (float)$row['balance']; $inv = (int)$row['id']; } $stmt->close(); }
    } catch (Exception $e) { error_log('balance: '.$e->getMessage()); }
    echo json_encode(['balance' => $bal, 'fee_account_id' => $inv]); exit;
}

if ($ajax === 'get_fee_structure' && $sid) {
    header('Content-Type: application/json');
    $fees = []; $student = [];
    try {
        $stmt = $students->prepare("SELECT program, current_year FROM students WHERE student_id = ? LIMIT 1");
        if ($stmt) { $stmt->bind_param('s', $sid); $stmt->execute(); $prog = $stmt->get_result(); $student = $prog ? ($prog->fetch_assoc() ?: []) : []; $stmt->close(); }
        if (!empty($student['program'])) {
            $p = $student['program']; $y = $student['current_year'] ?? '';
            $stmt = $staff->prepare("SELECT * FROM fee_structures WHERE (program = '' OR program = ?) AND (year_level = '' OR year_level = ?) ORDER BY id");
            if ($stmt) { $stmt->bind_param('ss', $p, $y); $stmt->execute(); $r = $stmt->get_result(); if ($r) while ($row = $r->fetch_assoc()) $fees[] = $row; $stmt->close(); }
        }
    } catch (Exception $e) { error_log('fee_struct: '.$e->getMessage()); }
    echo json_encode(['student' => $student, 'items' => $fees]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = 'bursar-billing.php?view=' . urlencode($view);

    if ($action === 'save_fee_structure' && $staff) {
        try {
            $name = trim($_POST['item_name'] ?? ''); $amount = (float)($_POST['item_amount'] ?? 0);
            $prog = trim($_POST['program'] ?? ''); $year = trim($_POST['year_level'] ?? '');
            $cat = trim($_POST['category'] ?? 'tuition');
            $stmt = $staff->prepare("INSERT INTO fee_structures (item_name, amount, program, year_level, category) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) { $stmt->bind_param('sdsss', $name, $amount, $prog, $year, $cat); $stmt->execute() ? $_SESSION['success'] = 'Fee item added.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'delete_fee_item' && $staff) {
        try { $id = (int)($_POST['item_id'] ?? 0); $staff->query("DELETE FROM fee_structures WHERE id = " . intval($id)); $_SESSION['success'] = 'Fee item deleted.'; } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'edit_fee_item' && $staff) {
        try {
            $id = (int)($_POST['item_id'] ?? 0); $name = trim($_POST['item_name'] ?? ''); $amount = (float)($_POST['item_amount'] ?? 0);
            $prog = trim($_POST['program'] ?? ''); $year = trim($_POST['year_level'] ?? ''); $cat = trim($_POST['category'] ?? 'tuition');
            $stmt = $staff->prepare("UPDATE fee_structures SET item_name=?, amount=?, program=?, year_level=?, category=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('sdsssi', $name, $amount, $prog, $year, $cat, $id); $stmt->execute(); $stmt->close(); $_SESSION['success'] = 'Fee item updated.'; }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'generate_invoice' && $staff) {
        try {
            $student_id = trim($_POST['student_id'] ?? '');
            $academic_year = trim($_POST['academic_year'] ?? date('Y').'/'.(date('Y')+1));
            $semester = trim($_POST['semester'] ?? '1');
            $total_fees = (float)($_POST['total_amount'] ?? 0);
            $due_date = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));
            $description = trim($_POST['description'] ?? '');
            if ($student_id === '' || $total_fees <= 0) { $_SESSION['error'] = 'Student and amount required.'; }
            else {
                $prefix = 'INV-'.date('Y').'-';
                $cnt = $staff->query("SELECT COUNT(*) AS c FROM student_fee_accounts WHERE invoice_number LIKE '$prefix%'");
                $num = $cnt ? ((int)$cnt->fetch_assoc()['c'] + 1) : 1;
                $inv_no = $prefix . str_pad($num, 5, '0', STR_PAD_LEFT);
                $stmt = $staff->prepare("INSERT INTO student_fee_accounts (student_id, academic_year, semester, invoice_number, total_fees, amount_paid, balance, due_date, description, status) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, 'unpaid')");
                if ($stmt) { $stmt->bind_param('ssssdds', $student_id, $academic_year, $semester, $inv_no, $total_fees, $total_fees, $due_date, $description); $stmt->execute() ? $_SESSION['success'] = "Invoice $inv_no created for ".currency($total_fees) : $_SESSION['error'] = $stmt->error; $stmt->close(); }
            }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'apply_discount' && $staff) {
        try {
            $account_id = (int)($_POST['fee_account_id'] ?? 0);
            $discount_type = trim($_POST['discount_type'] ?? 'percentage');
            $discount_value = (float)($_POST['discount_value'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            if ($account_id <= 0 || $discount_value <= 0) { $_SESSION['error'] = 'Invalid discount parameters.'; }
            else {
                $qrAcc = $staff->query("SELECT total_fees, balance, amount_paid FROM student_fee_accounts WHERE id = " . intval($account_id)); $acc = $qrAcc ? $qrAcc->fetch_assoc() : null;
                if ($acc) {
                    $total = (float)$acc['total_fees'];
                    $discount_amount = $discount_type === 'percentage' ? ($total * $discount_value / 100) : $discount_value;
                    $new_balance = max(0, (float)$acc['balance'] - $discount_amount);
                    $staff->query("UPDATE student_fee_accounts SET balance = $new_balance, total_fees = total_fees - $discount_amount, discounts = COALESCE(discounts,0) + $discount_amount WHERE id = " . intval($account_id));
                    $stmt = $staff->prepare("INSERT INTO bursar_discounts (fee_account_id, discount_type, discount_value, discount_amount, reason, applied_by, applied_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    if ($stmt) { $uid = (int)($user['id'] ?? 0); $stmt->bind_param('isddsi', $account_id, $discount_type, $discount_value, $discount_amount, $reason, $uid); $stmt->execute(); $stmt->close(); }
                    $_SESSION['success'] = 'Discount of '.currency($discount_amount).' applied.';
                } else { $_SESSION['error'] = 'Fee account not found.'; }
            }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'save_scholarship' && $staff) {
        try {
            $name = trim($_POST['scholarship_name'] ?? ''); $type = trim($_POST['scholarship_type'] ?? 'partial');
            $value = (float)($_POST['scholarship_value'] ?? 0); $desc = trim($_POST['description'] ?? '');
            $provider = trim($_POST['provider'] ?? '');
            $stmt = $staff->prepare("INSERT INTO bursar_scholarships (scholarship_name, scholarship_type, scholarship_value, description, provider, status) VALUES (?, ?, ?, ?, ?, 'active')");
            if ($stmt) { $stmt->bind_param('ssdss', $name, $type, $value, $desc, $provider); $stmt->execute() ? $_SESSION['success'] = 'Scholarship created.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'save_penalty' && $staff) {
        try {
            $name = trim($_POST['penalty_name'] ?? ''); $type = trim($_POST['penalty_type'] ?? 'percentage');
            $value = (float)($_POST['penalty_value'] ?? 0); $grace_days = (int)($_POST['grace_days'] ?? 0);
            $max_charge = (float)($_POST['max_charge'] ?? 0);
            $stmt = $staff->prepare("INSERT INTO bursar_penalty_config (penalty_name, penalty_type, penalty_value, grace_days, max_charge, status) VALUES (?, ?, ?, ?, ?, 'active')");
            if ($stmt) { $stmt->bind_param('ssdii', $name, $type, $value, $grace_days, $max_charge); $stmt->execute() ? $_SESSION['success'] = 'Penalty rule created.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'save_sponsorship' && $staff) {
        try {
            $student_id = trim($_POST['student_id'] ?? ''); $sponsor_name = trim($_POST['sponsor_name'] ?? '');
            $sponsor_contact = trim($_POST['sponsor_contact'] ?? ''); $sponsor_email = trim($_POST['sponsor_email'] ?? '');
            $coverage = (float)($_POST['coverage_percent'] ?? 100);
            $stmt = $staff->prepare("INSERT INTO bursar_sponsorships (student_id, sponsor_name, sponsor_contact, sponsor_email, coverage_percent, status) VALUES (?, ?, ?, ?, ?, 'active')");
            if ($stmt) { $stmt->bind_param('ssssd', $student_id, $sponsor_name, $sponsor_contact, $sponsor_email, $coverage); $stmt->execute() ? $_SESSION['success'] = 'Sponsorship recorded.' : $_SESSION['error'] = $stmt->error; $stmt->close(); }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }

    if ($action === 'write_off' && $staff) {
        try {
            $account_id = (int)($_POST['fee_account_id'] ?? 0); $reason = trim($_POST['write_off_reason'] ?? '');
            if ($account_id > 0) {
                $stmt = $staff->prepare("UPDATE student_fee_accounts SET status = 'cancelled', write_off_reason = ?, balance = 0 WHERE id = ?");
                if ($stmt) { $stmt->bind_param('si', $reason, $account_id); $stmt->execute(); $stmt->close(); }
                $_SESSION['success'] = 'Invoice written off.';
            }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header("Location: $redirect"); exit;
    }
}

// ── Stats ──
$total_billing = 0; $total_cleared = 0; $total_outstanding = 0; $invoice_count = 0;
try {
    if ($staff) {
        $r = $staff->query("SELECT COALESCE(SUM(total_fees),0) AS t FROM student_fee_accounts"); if ($r) $total_billing = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COUNT(*) AS c FROM student_fee_accounts WHERE status='fully_paid'"); if ($r) $total_cleared = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COALESCE(SUM(balance),0) AS t FROM student_fee_accounts WHERE status NOT IN ('fully_paid','cancelled')"); if ($r) $total_outstanding = (float)$r->fetch_assoc()['t'];
        $r = $staff->query("SELECT COUNT(*) AS c FROM student_fee_accounts"); if ($r) $invoice_count = (int)$r->fetch_assoc()['c'];
    }
} catch (Exception $e) { error_log('billing stats: '.$e->getMessage()); }

// ── Fee structure list ──
$fee_items = [];
try { if ($staff) { $r = $staff->query("SELECT * FROM fee_structures ORDER BY category, item_name"); if ($r) while ($row = $r->fetch_assoc()) $fee_items[] = $row; } } catch (Exception $e) {}

// ── Invoices list ──
$invoices = [];
try { if ($staff) { $r = $staff->query("SELECT sfa.*, s.first_name, s.surname, s.program FROM student_fee_accounts sfa LEFT JOIN igangaschoolofl_students_db.students s ON sfa.student_id = s.student_id ORDER BY sfa.created_at DESC LIMIT 100"); if ($r) while ($row = $r->fetch_assoc()) $invoices[] = $row; } } catch (Exception $e) {}

// ── Scholarships ──
$scholarships = [];
try { if ($staff) { $r = $staff->query("SELECT * FROM bursar_scholarships ORDER BY scholarship_name"); if ($r) while ($row = $r->fetch_assoc()) $scholarships[] = $row; } } catch (Exception $e) {}

// ── Penalties ──
$penalties = [];
try { if ($staff) { $r = $staff->query("SELECT * FROM bursar_penalty_config ORDER BY penalty_name"); if ($r) while ($row = $r->fetch_assoc()) $penalties[] = $row; } } catch (Exception $e) {}

// ── Sponsorships ──
$sponsorships = [];
try { if ($staff) { $r = $staff->query("SELECT bs.*, s.first_name, s.surname FROM bursar_sponsorships bs LEFT JOIN igangaschoolofl_students_db.students s ON bs.student_id = s.student_id ORDER BY bs.created_at DESC LIMIT 50"); if ($r) while ($row = $r->fetch_assoc()) $sponsorships[] = $row; } } catch (Exception $e) {}

// ── Discounts ──
$discounts = [];
try { if ($staff) { $r = $staff->query("SELECT bd.*, sfa.student_id, sfa.invoice_number FROM bursar_discounts bd LEFT JOIN student_fee_accounts sfa ON bd.fee_account_id = sfa.id ORDER BY bd.applied_at DESC LIMIT 50"); if ($r) while ($row = $r->fetch_assoc()) $discounts[] = $row; } } catch (Exception $e) {}

$pageTitle = 'Bursar - Billing & Fees';
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
<div class="ma content-section" style="margin-left:270px;padding:24px">

    <div class="ph">
        <div>
            <h1><i class="fas fa-file-invoice-dollar me-2"></i>Student Billing & Fees</h1>
            <p>Fee structures, invoicing, discounts, scholarships &amp; sponsorships</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:13px"><i class="far fa-clock me-1"></i><span id="currentDate"></span></span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-file-invoice"></i></div><div class="stat-content"><h3><?= currency($total_billing) ?></h3><p>Total Billing</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= number_format($total_cleared) ?></h3><p>Cleared Students</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-content"><h3><?= currency($total_outstanding) ?></h3><p>Outstanding</p></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card info"><div class="stat-icon"><i class="fas fa-calculator"></i></div><div class="stat-content"><h3><?= number_format($invoice_count) ?></h3><p>Total Invoices</p></div></div>
        </div>
    </div>

    <div class="tab-nav">
        <button class="tn active" data-tab="tab_fee_structure"><i class="fas fa-tags me-1"></i>Fee Structure</button>
        <button class="tn" data-tab="tab_invoices"><i class="fas fa-file-invoice me-1"></i>Invoices</button>
        <button class="tn" data-tab="tab_discounts"><i class="fas fa-percentage me-1"></i>Discounts &amp; Waivers</button>
        <button class="tn" data-tab="tab_scholarships"><i class="fas fa-graduation-cap me-1"></i>Scholarships</button>
        <button class="tn" data-tab="tab_sponsorships"><i class="fas fa-handshake me-1"></i>Sponsorships</button>
        <button class="tn" data-tab="tab_penalties"><i class="fas fa-clock me-1"></i>Penalties</button>
    </div>

    <!-- ══════════ Fee Structure ══════════ -->
    <div id="tab_fee_structure" class="tab-content active">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Add / Edit Fee Item</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="save_fee_structure">
                    <input type="hidden" name="item_id" id="edit_item_id" value="0">
                    <div class="row g-3 mt-2">
                        <div class="col-12"><label class="fl">Item Name *</label><input type="text" name="item_name" id="edit_item_name" class="form-control fc" required></div>
                        <div class="col-6"><label class="fl">Amount (UGX) *</label><input type="number" name="item_amount" id="edit_item_amount" class="form-control fc" required min="1"></div>
                        <div class="col-6">
                            <label class="fl">Category</label>
                            <select name="category" id="edit_item_category" class="form-select fs">
                                <option value="tuition">Tuition</option><option value="accommodation">Accommodation</option>
                                <option value="clinical">Clinical Fees</option><option value="examination">Examination</option>
                                <option value="library">Library</option><option value="activity">Activity</option><option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="fl">Program</label>
                            <select name="program" id="edit_item_program" class="form-select fs">
                                <option value="">All Programs</option>
                                <option>Certificate Midwifery</option><option>Diploma Midwifery</option>
                                <option>Diploma Nursing Extension</option><option>Certificate Nursing</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="fl">Year / Level</label>
                            <select name="year_level" id="edit_item_year" class="form-select fs">
                                <option value="">All Years</option><option value="Year 1">Year 1</option>
                                <option value="Year 2">Year 2</option><option value="Year 3">Year 3</option>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Save Item</button>
                            <button type="button" class="btn bo btn-sm" id="cancelEditBtn" style="display:none" onclick="cancelEdit()">Cancel</button>
                        </div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Current Fee Structures</h5>
                <div class="table-responsive">
                    <table class="table tb">
                        <thead><tr><th>Item</th><th>Category</th><th>Amount</th><th>Program</th><th>Year</th><th></th></tr></thead>
                        <tbody>
<?php if (count($fee_items) > 0): foreach ($fee_items as $f): ?>
<tr>
    <td><?= htmlspecialchars($f['item_name']) ?></td>
    <td><?= htmlspecialchars($f['category'] ?? 'tuition') ?></td>
    <td><?= currency($f['amount']) ?></td>
    <td><?= htmlspecialchars($f['program'] ?: 'All') ?></td>
    <td><?= htmlspecialchars($f['year_level'] ?: 'All') ?></td>
    <td>
        <button class="btn btn-sm btn-outline-primary" onclick='editFeeItem(<?= json_encode($f) ?>)'><i class="fas fa-edit"></i></button>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this fee item?')">
            <input type="hidden" name="action" value="delete_fee_item"><input type="hidden" name="item_id" value="<?= $f['id'] ?>">
            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
        </form>
    </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="6" class="text-center text-muted py-3">No fee items defined.</td></tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Invoices ══════════ -->
    <div id="tab_invoices" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-file-invoice me-2"></i>Generate Invoice</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="generate_invoice">
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <label class="fl">Student *</label>
                            <div class="input-group"><input type="text" id="invStudentSearch" class="form-control fc" placeholder="Search student..." autocomplete="off"><button class="btn bb" type="button" onclick="searchInvoiceStudent()"><i class="fas fa-search"></i></button></div>
                            <div id="invStudentResults" class="mt-1"></div>
                            <input type="hidden" name="student_id" id="invStudentId">
                        </div>
                        <div class="col-6"><label class="fl">Academic Year</label><input type="text" name="academic_year" class="form-control fc" value="<?= date('Y').'/'.(date('Y')+1) ?>"></div>
                        <div class="col-6"><label class="fl">Semester</label><select name="semester" class="form-select fs"><option value="1">Semester 1</option><option value="2">Semester 2</option></select></div>
                        <div class="col-6"><label class="fl">Due Date</label><input type="date" name="due_date" class="form-control fc" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"></div>
                        <div class="col-6"><label class="fl">Total Amount *</label><input type="number" name="total_amount" id="invTotal" class="form-control fc" required min="1" step="100"></div>
                        <div class="col-12"><label class="fl">Description</label><textarea name="description" class="form-control fc" rows="2" placeholder="Optional invoice notes..."></textarea></div>
                        <div class="col-12"><div id="invFeePreview" class="small text-muted"></div></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-file-invoice me-1"></i>Generate Invoice</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Invoice Ledger</h5>
                <div class="table-responsive">
                    <table class="table tb">
                        <thead><tr><th>Invoice</th><th>Student</th><th>Program</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th></tr></thead>
                        <tbody>
<?php if (count($invoices) > 0): foreach ($invoices as $inv): $bal = (float)$inv['balance']; ?>
<tr>
    <td><small><?= htmlspecialchars($inv['invoice_number']) ?></small></td>
    <td><?= htmlspecialchars(($inv['surname'] ?? '') . ' ' . ($inv['first_name'] ?? '')) ?><br><small class="text-muted"><?= htmlspecialchars($inv['student_id']) ?></small></td>
    <td><small><?= htmlspecialchars($inv['program'] ?? '-') ?></small></td>
    <td><?= currency($inv['total_fees']) ?></td>
    <td><?= currency($inv['amount_paid']) ?></td>
    <td class="<?= $bal > 0 ? 'text-danger fw-bold' : 'text-success' ?>"><?= currency($bal) ?></td>
    <td><?= badge($inv['status']) ?></td>
    <td><small><?= date('d/m/Y', strtotime($inv['due_date'] ?? 'now')) ?></small></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="8" class="text-center text-muted py-3">No invoices found.</td></tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Discounts & Waivers ══════════ -->
    <div id="tab_discounts" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-percentage me-2"></i>Apply Discount / Waiver</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="apply_discount">
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <label class="fl">Invoice / Fee Account *</label>
                            <select name="fee_account_id" class="form-select fs" required>
                                <option value="">-- Select Invoice --</option>
<?php foreach ($invoices as $inv): if ($inv['status'] !== 'fully_paid' && $inv['status'] !== 'cancelled'): ?>
<option value="<?= $inv['id'] ?>"><?= htmlspecialchars($inv['invoice_number']) ?> - <?= htmlspecialchars(($inv['surname'] ?? '').' '.($inv['first_name'] ?? '')) ?> (<?= currency($inv['balance']) ?>)</option>
<?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="fl">Type</label>
                            <select name="discount_type" class="form-select fs">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (UGX)</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="fl">Value *</label><input type="number" name="discount_value" class="form-control fc" required min="1" step="100"></div>
                        <div class="col-12"><label class="fl">Reason *</label><textarea name="reason" class="form-control fc" required rows="2" placeholder="e.g. Hardship discount, Merit scholarship, etc."></textarea></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-check me-1"></i>Apply</button></div>
                    </div>
                </form>
                <hr>
                <h6 class="fw-bold mt-3">Write Off Invoice</h6>
                <form method="POST" onsubmit="return confirm('Permanently write off this invoice? This cannot be undone.')">
                    <input type="hidden" name="action" value="write_off">
                    <div class="row g-2">
                        <div class="col-7">
                            <select name="fee_account_id" class="form-select fs">
                                <option value="">-- Select --</option>
<?php foreach ($invoices as $inv): if ($inv['status'] !== 'fully_paid' && $inv['status'] !== 'cancelled'): ?>
<option value="<?= $inv['id'] ?>"><?= htmlspecialchars($inv['invoice_number']) ?> (<?= currency($inv['balance']) ?>)</option>
<?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5"><input type="text" name="write_off_reason" class="form-control fc" placeholder="Reason"></div>
                        <div class="col-12 text-end"><button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-times me-1"></i>Write Off</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-history me-2"></i>Discount History</h5>
                <div class="table-responsive">
                    <table class="table tb">
                        <thead><tr><th>Invoice</th><th>Type</th><th>Amount</th><th>Reason</th><th>Date</th></tr></thead>
                        <tbody>
<?php if (count($discounts) > 0): foreach ($discounts as $d): ?>
<tr><td><?= htmlspecialchars($d['invoice_number'] ?? $d['fee_account_id']) ?></td><td><?= htmlspecialchars($d['discount_type']) ?></td><td><?= currency($d['discount_amount']) ?></td><td><small><?= htmlspecialchars($d['reason']) ?></small></td><td><?= date('d/m/Y', strtotime($d['applied_at'] ?? 'now')) ?></td></tr>
<?php endforeach; else: ?>
<tr><td colspan="5" class="text-center text-muted py-3">No discounts applied yet.</td></tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Scholarships ══════════ -->
    <div id="tab_scholarships" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-plus-circle me-2"></i>Create Scholarship</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="save_scholarship">
                    <div class="row g-3 mt-2">
                        <div class="col-12"><label class="fl">Scholarship Name *</label><input type="text" name="scholarship_name" class="form-control fc" required placeholder="e.g. Government Merit Scholarship"></div>
                        <div class="col-6">
                            <label class="fl">Type</label>
                            <select name="scholarship_type" class="form-select fs">
                                <option value="full">Full</option><option value="partial">Partial</option>
                                <option value="merit">Merit-based</option><option value="need">Need-based</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="fl">Value (UGX)</label><input type="number" name="scholarship_value" class="form-control fc" min="0"></div>
                        <div class="col-12"><label class="fl">Provider</label><input type="text" name="provider" class="form-control fc" placeholder="e.g. Ministry of Health, Private Sponsor"></div>
                        <div class="col-12"><label class="fl">Description</label><textarea name="description" class="form-control fc" rows="2"></textarea></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Create</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Available Scholarships</h5>
                <div class="table-responsive">
                    <table class="table tb">
                        <thead><tr><th>Name</th><th>Type</th><th>Value</th><th>Provider</th><th>Status</th></tr></thead>
                        <tbody>
<?php if (count($scholarships) > 0): foreach ($scholarships as $s): ?>
<tr><td><?= htmlspecialchars($s['scholarship_name']) ?></td><td><?= htmlspecialchars($s['scholarship_type']) ?></td><td><?= currency($s['scholarship_value']) ?></td><td><?= htmlspecialchars($s['provider'] ?? '-') ?></td><td><?= badge($s['status'] ?? 'active') ?></td></tr>
<?php endforeach; else: ?>
<tr><td colspan="5" class="text-center text-muted py-3">No scholarships defined.</td></tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Sponsorships ══════════ -->
    <div id="tab_sponsorships" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-handshake me-2"></i>Record Sponsorship</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="save_sponsorship">
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <label class="fl">Student *</label>
                            <div class="input-group"><input type="text" id="spStudentSearch" class="form-control fc" placeholder="Search student..." autocomplete="off"><button class="btn bb" type="button" onclick="searchSponsorshipStudent()"><i class="fas fa-search"></i></button></div>
                            <div id="spStudentResults" class="mt-1"></div>
                            <input type="hidden" name="student_id" id="spStudentId">
                        </div>
                        <div class="col-12"><label class="fl">Sponsor Name *</label><input type="text" name="sponsor_name" class="form-control fc" required></div>
                        <div class="col-6"><label class="fl">Contact</label><input type="text" name="sponsor_contact" class="form-control fc"></div>
                        <div class="col-6"><label class="fl">Email</label><input type="email" name="sponsor_email" class="form-control fc"></div>
                        <div class="col-6"><label class="fl">Coverage (%)</label><input type="number" name="coverage_percent" class="form-control fc" value="100" min="1" max="100"></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Record</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Active Sponsorships</h5>
                <div class="table-responsive">
                    <table class="table tb">
                        <thead><tr><th>Student</th><th>Sponsor</th><th>Contact</th><th>Coverage</th><th>Status</th></tr></thead>
                        <tbody>
<?php if (count($sponsorships) > 0): foreach ($sponsorships as $sp): ?>
<tr><td><?= htmlspecialchars(($sp['surname'] ?? '').' '.($sp['first_name'] ?? '')) ?><br><small class="text-muted"><?= htmlspecialchars($sp['student_id']) ?></small></td><td><?= htmlspecialchars($sp['sponsor_name']) ?></td><td><small><?= htmlspecialchars($sp['sponsor_contact'] ?? '-') ?></small></td><td><?= (float)$sp['coverage_percent'] ?>%</td><td><?= badge($sp['status'] ?? 'active') ?></td></tr>
<?php endforeach; else: ?>
<tr><td colspan="5" class="text-center text-muted py-3">No sponsorships recorded.</td></tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ Penalties ══════════ -->
    <div id="tab_penalties" class="tab-content">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="content-section"><h5><i class="fas fa-clock me-2"></i>Late Payment Penalty Config</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="save_penalty">
                    <div class="row g-3 mt-2">
                        <div class="col-12"><label class="fl">Penalty Name *</label><input type="text" name="penalty_name" class="form-control fc" required placeholder="e.g. Late Fee - 1st Month"></div>
                        <div class="col-6">
                            <label class="fl">Type</label>
                            <select name="penalty_type" class="form-select fs">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (UGX)</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="fl">Value *</label><input type="number" name="penalty_value" class="form-control fc" required min="1" step="100"></div>
                        <div class="col-6"><label class="fl">Grace Days</label><input type="number" name="grace_days" class="form-control fc" value="0" min="0"></div>
                        <div class="col-6"><label class="fl">Max Charge (0=unlimited)</label><input type="number" name="max_charge" class="form-control fc" value="0" min="0"></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Save Rule</button></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="content-section"><h5><i class="fas fa-list me-2"></i>Penalty Rules</h5>
                <div class="table-responsive">
                    <table class="table tb">
                        <thead><tr><th>Name</th><th>Type</th><th>Value</th><th>Grace Days</th><th>Max Charge</th><th>Status</th></tr></thead>
                        <tbody>
<?php if (count($penalties) > 0): foreach ($penalties as $p): ?>
<tr><td><?= htmlspecialchars($p['penalty_name']) ?></td><td><?= htmlspecialchars($p['penalty_type']) ?></td><td><?= $p['penalty_type'] === 'percentage' ? $p['penalty_value'].'%' : currency($p['penalty_value']) ?></td><td><?= $p['grace_days'] ?></td><td><?= (float)$p['max_charge'] > 0 ? currency($p['max_charge']) : 'Unlimited' ?></td><td><?= badge($p['status'] ?? 'active') ?></td></tr>
<?php endforeach; else: ?>
<tr><td colspan="6" class="text-center text-muted py-3">No penalty rules configured.</td></tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
// ── Tab switching ──
document.querySelectorAll('.tn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('.tn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function(t){ t.classList.remove('active'); });
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
    });
});

// ── Edit fee item ──
function editFeeItem(item){
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_item_name').value = item.item_name;
    document.getElementById('edit_item_amount').value = item.amount;
    document.getElementById('edit_item_category').value = item.category || 'tuition';
    document.getElementById('edit_item_program').value = item.program || '';
    document.getElementById('edit_item_year').value = item.year_level || '';
    document.getElementById('cancelEditBtn').style.display = 'inline-block';
    // change form action to edit
    var form = document.querySelector('#tab_fee_structure form');
    form.querySelector('input[name="action"]').value = 'edit_fee_item';
    form.scrollIntoView({behavior:'smooth'});
}
function cancelEdit(){
    document.getElementById('edit_item_id').value = 0;
    document.getElementById('edit_item_name').value = '';
    document.getElementById('edit_item_amount').value = '';
    document.getElementById('edit_item_category').value = 'tuition';
    document.getElementById('edit_item_program').value = '';
    document.getElementById('edit_item_year').value = '';
    document.getElementById('cancelEditBtn').style.display = 'none';
    var form = document.querySelector('#tab_fee_structure form');
    form.querySelector('input[name="action"]').value = 'save_fee_structure';
}

// ── Invoice student search ──
function searchInvoiceStudent(){
    var q = document.getElementById('invStudentSearch').value.trim();
    if(!q) return;
    fetch('bursar-billing.php?ajax=search_student&sid='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(data){
        var el = document.getElementById('invStudentResults'); el.innerHTML = '';
        if(!data||!data.length){ el.innerHTML = '<div class="text-muted small p-2">No students found.</div>'; return; }
        data.forEach(function(s){
            var d = document.createElement('div');
            d.className = 'sri';
            d.innerHTML = '<strong>'+esc(s.surname)+', '+esc(s.first_name)+'</strong><br><small class="text-muted">'+esc(s.student_id)+' | '+esc(s.program||'')+'</small>';
            d.addEventListener('click',function(){ selectInvStudent(s); });
            el.appendChild(d);
        });
    }).catch(function(){});
}
function selectInvStudent(s){
    document.getElementById('invStudentId').value = s.student_id;
    document.getElementById('invStudentSearch').value = s.surname+', '+s.first_name+' ('+s.student_id+')';
    document.getElementById('invStudentResults').innerHTML = '';
    fetch('bursar-billing.php?ajax=get_fee_structure&sid='+encodeURIComponent(s.student_id))
    .then(function(r){ return r.json(); })
    .then(function(d){
        var total = 0, html = '<div class="mt-2 p-2 bg-light rounded"><small><strong>Fee Items:</strong><br>';
        if(d.items&&d.items.length){
            d.items.forEach(function(f){ total += parseFloat(f.amount)||0; html += '&bull; '+esc(f.item_name)+': UGX '+Number(f.amount).toLocaleString()+'<br>'; });
            html += '<strong>Total: UGX '+Number(total).toLocaleString()+'</strong>';
        } else { html += 'No fee structure found for this program.'; }
        html += '</small></div>';
        document.getElementById('invFeePreview').innerHTML = html;
        document.getElementById('invTotal').value = total||'';
    }).catch(function(){});
}

// ── Sponsorship student search ──
function searchSponsorshipStudent(){
    var q = document.getElementById('spStudentSearch').value.trim();
    if(!q) return;
    fetch('bursar-billing.php?ajax=search_student&sid='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(data){
        var el = document.getElementById('spStudentResults'); el.innerHTML = '';
        if(!data||!data.length){ el.innerHTML = '<div class="text-muted small p-2">No students found.</div>'; return; }
        data.forEach(function(s){
            var d = document.createElement('div');
            d.className = 'sri';
            d.innerHTML = '<strong>'+esc(s.surname)+', '+esc(s.first_name)+'</strong><br><small class="text-muted">'+esc(s.student_id)+'</small>';
            d.addEventListener('click',function(){
                document.getElementById('spStudentId').value = s.student_id;
                document.getElementById('spStudentSearch').value = s.surname+', '+s.first_name+' ('+s.student_id+')';
                document.getElementById('spStudentResults').innerHTML = '';
            });
            el.appendChild(d);
        });
    }).catch(function(){});
}

function esc(s){ if(!s) return ''; var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>
</body>
</html>
