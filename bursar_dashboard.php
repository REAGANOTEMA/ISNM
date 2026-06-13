<?php
require_once __DIR__ . '/auth-service.php';

if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
    header('Location: staff-login.php'); exit;
}
$role = $_SESSION['role'] ?? '';
$allowed = ['School Bursar','Bursar','Director Finance','Director General','CEO','School Principal'];
if (!in_array($role, $allowed) && !$auth_service->hasFullInstitutionAccess($role)) {
    header('Location: staff-login.php?error=unauthorized'); exit;
}

require_once __DIR__ . '/config/database.php';
$sconn = getStudentsConnection();
$stconn = getStaffConnection();
$uid = $_SESSION['user_id'];
$uname = $_SESSION['full_name'];
$urole = $_SESSION['role'];

function bq($conn, $sql) {
    $r = $conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row[array_key_first($row)] ?? 0;
}

// ── Stats ────────────────────────────────────────────────────
$today_collection  = bq($sconn, "SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE DATE(payment_date)=CURDATE() AND status IN('Completed','verified','approved')");
$week_collection   = bq($sconn, "SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE payment_date>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND status IN('Completed','verified','approved')");
$month_collection  = bq($sconn, "SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE payment_date>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND status IN('Completed','verified','approved')");
$outstanding       = bq($sconn, "SELECT COALESCE(SUM(balance),0) v FROM student_invoices WHERE status IN('Pending','partial','Overdue','Partially Paid')");
$students_cleared  = bq($sconn, "SELECT COUNT(DISTINCT s.id) v FROM students s WHERE s.status='Active' AND NOT EXISTS(SELECT 1 FROM student_invoices i WHERE i.student_id=s.id AND i.status IN('Pending','partial','Overdue','Partially Paid'))");
$students_not_cleared = bq($sconn, "SELECT COUNT(DISTINCT student_id) v FROM student_invoices WHERE status IN('Pending','partial','Overdue','Partially Paid')");
$pending_payments  = bq($sconn, "SELECT COUNT(*) v FROM payments WHERE status='Pending'");
$overdue_invoices  = bq($sconn, "SELECT COUNT(*) v FROM student_invoices WHERE status='Overdue' OR (due_date<CURDATE() AND status NOT IN('Paid','Cancelled','Waived'))");
$total_students    = bq($sconn, "SELECT COUNT(*) v FROM students WHERE status='Active'");
$budget_total       = bq($sconn, "SELECT COALESCE(SUM(total_budget),0) v FROM budgets WHERE status IN('Approved','Active')");
$pending_expenses   = bq($sconn, "SELECT COUNT(*) v FROM expenditures WHERE approval_status='Pending'");
$ledger_entries     = bq($sconn, "SELECT COUNT(*) v FROM ledger_entries");
$mobile_pending     = bq($sconn, "SELECT COUNT(*) v FROM mobile_money_transactions WHERE status='Initiated'");
$ura_draft          = bq($sconn, "SELECT COUNT(*) v FROM ura_reports WHERE status='Draft'");
$asset_count        = bq($sconn, "SELECT COUNT(*) v FROM assets WHERE status='Active'");

// ── Search ───────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$search_results = [];
if ($search) {
    $s = $sconn->real_escape_string($search);
    $r = $sconn->query("SELECT s.id,s.student_number,s.registration_number,s.full_name,s.first_name,s.surname,s.course,s.phone,s.email,
        COALESCE((SELECT SUM(total_amount) FROM student_invoices WHERE student_id=s.id),0) total_billed,
        COALESCE((SELECT SUM(amount_received) FROM payments WHERE student_id=s.id AND status IN('Completed','verified','approved')),0) total_paid
        FROM students s
        WHERE s.student_number LIKE '%$s%' OR s.registration_number LIKE '%$s%' OR s.full_name LIKE '%$s%'
          OR s.first_name LIKE '%$s%' OR s.surname LIKE '%$s%' OR s.phone LIKE '%$s%'
        LIMIT 30");
    if ($r) while ($row = $r->fetch_assoc()) { $row['balance'] = $row['total_billed'] - $row['total_paid']; $search_results[] = $row; }
}

// ── Recent Transactions ──────────────────────────────────────
$recent_tx = [];
$r = $sconn->query("SELECT p.id,p.payment_reference,p.amount_received,p.payment_method,p.payment_date,p.status,
    COALESCE(s.full_name,CONCAT(s.first_name,' ',s.surname)) sname,
    COALESCE(s.student_number,s.registration_number) snum
    FROM payments p LEFT JOIN students s ON p.student_id=s.id
    ORDER BY p.payment_date DESC, p.id DESC LIMIT 15");
if ($r) while ($row = $r->fetch_assoc()) $recent_tx[] = $row;

// ── Fee Structures ──────────────────────────────────────────
$fee_structures = [];
$r = $sconn->query("SELECT * FROM fee_structures WHERE is_active=1 ORDER BY fee_type,amount");
if ($r) while ($row = $r->fetch_assoc()) $fee_structures[] = $row;

// ── Overdue Debtors ─────────────────────────────────────────
$debtors = [];
$r = $sconn->query("SELECT COALESCE(s.full_name,CONCAT(s.first_name,' ',s.surname)) sname,
    COALESCE(s.student_number,s.registration_number) snum, s.course, s.phone,
    SUM(i.balance) total_owing, MAX(i.due_date) last_due
    FROM student_invoices i JOIN students s ON i.student_id=s.id
    WHERE i.status IN('Overdue','Pending','partial','Partially Paid')
    GROUP BY s.id ORDER BY total_owing DESC LIMIT 20");
if ($r) while ($row = $r->fetch_assoc()) $debtors[] = $row;

// ── Budgets and expenditure ─────────────────────────────────
$active_budgets = [];
$r = $sconn->query("SELECT b.*,(SELECT COALESCE(SUM(allocated_amount),0) FROM budget_allocations WHERE budget_id=b.id) allocated FROM budgets b WHERE b.status IN('Draft','Approved','Active') ORDER BY b.created_at DESC LIMIT 10");
if ($r) while ($row = $r->fetch_assoc()) $active_budgets[] = $row;

$pending_expenditures = [];
$r = $sconn->query("SELECT e.*,ba.department budget_department FROM expenditures e LEFT JOIN budgets b ON e.budget_id=b.id LEFT JOIN budget_allocations ba ON ba.budget_id=b.id WHERE e.approval_status='Pending' ORDER BY e.expense_date DESC LIMIT 15");
if ($r) while ($row = $r->fetch_assoc()) $pending_expenditures[] = $row;

// ── Mobile money and reminders ───────────────────────────────
$mobile_transactions = [];
$r = $sconn->query("SELECT mmt.*, COALESCE(s.full_name,CONCAT(s.first_name,' ',s.surname)) sname FROM mobile_money_transactions mmt LEFT JOIN students s ON mmt.student_id=s.id ORDER BY mmt.created_at DESC LIMIT 10");
if ($r) while ($row = $r->fetch_assoc()) $mobile_transactions[] = $row;

$recent_reminders = [];
$r = $sconn->query("SELECT fr.*, COALESCE(s.full_name,CONCAT(s.first_name,' ',s.surname)) sname FROM fee_reminders fr LEFT JOIN students s ON fr.student_id=s.id ORDER BY fr.sent_at DESC LIMIT 10");
if ($r) while ($row = $r->fetch_assoc()) $recent_reminders[] = $row;

// ── POST handlers ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'record_payment') {
        $sid     = intval($_POST['student_id'] ?? 0);
        $amount  = floatval($_POST['amount'] ?? 0);
        $method  = $sconn->real_escape_string($_POST['payment_method'] ?? 'Cash');
        $ref     = $sconn->real_escape_string($_POST['reference'] ?? '');
        $iid     = intval($_POST['invoice_id'] ?? 0) ?: 'NULL';
        $pdate   = $sconn->real_escape_string($_POST['payment_date'] ?? date('Y-m-d'));
        $notes   = $sconn->real_escape_string($_POST['notes'] ?? '');
        $pref    = 'PAY-'.date('Ymd').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
        $rnum    = 'RCP-'.date('Ymd').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);

        if ($sid && $amount > 0) {
            $sconn->query("INSERT INTO payments (payment_reference,student_id,invoice_id,amount_received,payment_method,transaction_ref,payment_date,status,notes,received_by,created_at)
                VALUES ('$pref',$sid,$iid,$amount,'$method','$ref','$pdate','Completed','$notes',$uid,NOW())");
            $pid = $sconn->insert_id;
            // Update invoice balance
            if ($iid !== 'NULL') {
                $sconn->query("UPDATE student_invoices SET amount_paid=amount_paid+$amount, status=CASE WHEN (net_amount-amount_paid-$amount)<=0 THEN 'Paid' WHEN amount_paid+$amount>0 THEN 'Partially Paid' ELSE status END WHERE id=$iid");
            }
            // Auto receipt
            $sconn->query("INSERT INTO payment_receipts (receipt_number,payment_id,student_id,amount,payment_method,issued_by,created_at) VALUES ('$rnum',$pid,$sid,$amount,'$method',$uid,NOW())");
            $_SESSION['success'] = "Payment of UGX ".number_format($amount,0)." recorded. Receipt: $rnum";
        } else {
            $_SESSION['error'] = "Invalid payment data.";
        }
        header('Location: bursar_dashboard.php'); exit;
    }

    if ($action === 'add_fee_structure') {
        $fname  = $sconn->real_escape_string($_POST['fee_name'] ?? '');
        $ftype  = $sconn->real_escape_string($_POST['fee_type'] ?? 'Tuition');
        $amount = floatval($_POST['amount'] ?? 0);
        $prog   = $sconn->real_escape_string($_POST['program'] ?? '');
        $yr     = intval($_POST['academic_year_level'] ?? 1);
        $sem    = $sconn->real_escape_string($_POST['semester'] ?? '');
        $ay     = $sconn->real_escape_string($_POST['academic_year'] ?? date('Y'));
        $due    = $sconn->real_escape_string($_POST['due_date'] ?? '');
        if ($fname && $amount > 0) {
            $sconn->query("INSERT INTO fee_structures (fee_name,fee_type,amount,program_id,academic_year,semester,due_date,is_mandatory,is_active,created_at) VALUES ('$fname','$ftype',$amount,NULL,'$ay','$sem','".($due?:'NULL')."',1,1,NOW())");
            $_SESSION['success'] = "Fee structure added.";
        }
        header('Location: bursar_dashboard.php#fees'); exit;
    }

    if ($action === 'send_reminder') {
        $sid = intval($_POST['student_id'] ?? 0);
        if ($sid) {
            $rnum = 'REM-'.date('Ymd').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $sconn->query("INSERT INTO fee_reminders (reminder_number,student_id,reminder_type,sent_by,created_at) VALUES ('$rnum',$sid,'Email',$uid,NOW())");
            $_SESSION['success'] = "Fee reminder sent.";
        }
        header('Location: bursar_dashboard.php#debtors'); exit;
    }

    if ($action === 'create_budget') {
        $name = $sconn->real_escape_string($_POST['budget_name'] ?? '');
        $year = $sconn->real_escape_string($_POST['fiscal_year'] ?? date('Y'));
        $total = floatval($_POST['total_budget'] ?? 0);
        if ($name && $total > 0) {
            $sconn->query("INSERT INTO budgets (budget_name,fiscal_year,total_budget,status,created_at) VALUES ('$name','$year',$total,'Draft',NOW())");
            $_SESSION['success'] = "Budget draft created.";
        }
        header('Location: bursar_dashboard.php#budgets'); exit;
    }

    if ($action === 'record_expenditure') {
        $date = $sconn->real_escape_string($_POST['expense_date'] ?? date('Y-m-d'));
        $dept = $sconn->real_escape_string($_POST['department'] ?? '');
        $category = $sconn->real_escape_string($_POST['category'] ?? 'General');
        $desc = $sconn->real_escape_string($_POST['description'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        if ($desc && $amount > 0) {
            $sconn->query("INSERT INTO expenditures (expense_date,department,category,description,amount,approval_status) VALUES ('$date','$dept','$category','$desc',$amount,'Pending')");
            $_SESSION['success'] = "Expenditure submitted for approval.";
        }
        header('Location: bursar_dashboard.php#expenditures'); exit;
    }

    if ($action === 'approve_payment') {
        $pid = intval($_POST['payment_id'] ?? 0);
        if ($pid) {
            $sconn->query("UPDATE payments SET status='Completed',verified_by=$uid,verified_at=NOW() WHERE id=$pid AND status='Pending'");
            $_SESSION['success'] = "Payment approved.";
        }
        header('Location: bursar_dashboard.php#mobile'); exit;
    }
}

$method_logos = [
    'Cash'=>'💵','Bank Transfer'=>'🏦','Mobile Money'=>'📱','MTN'=>'📱',
    'Airtel'=>'📱','Cheque'=>'📋','Card'=>'💳','MasterCard'=>'💳',
    'Visa'=>'💳','Other'=>'💰'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bursar Dashboard – ISNM</title>
<link rel="icon" href="images/school-logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root{--primary:#1e6b3a;--accent:#28a745;--sidebar-w:250px}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif;margin:0}
.sidebar{width:var(--sidebar-w);background:linear-gradient(180deg,#1e3a2f,#1e6b3a);position:fixed;height:100vh;overflow-y:auto;z-index:100;color:#fff}
.sidebar .brand{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,.1);text-align:center}
.sidebar .brand img{width:50px;border-radius:50%;border:2px solid rgba(255,255,255,.3)}
.sidebar .brand h6{margin:7px 0 2px;font-size:.82rem}
.sidebar nav a{display:flex;align-items:center;gap:9px;padding:11px 18px;color:rgba(255,255,255,.82);text-decoration:none;font-size:.86rem;transition:.2s}
.sidebar nav a:hover,.sidebar nav a.active{background:rgba(255,255,255,.15);color:#fff;border-left:3px solid #7dffb3}
.sidebar nav a i{width:16px;text-align:center}
.main{margin-left:var(--sidebar-w);padding:22px;min-height:100vh}
.stat-card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);border-left:4px solid var(--accent);transition:.2s}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.1)}
.stat-card .num{font-size:1.7rem;font-weight:700}
.stat-card .lbl{font-size:.75rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px}
.section-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:22px}
.section-card h5{color:var(--primary);font-weight:600;border-bottom:2px solid #e9ecef;padding-bottom:8px;margin-bottom:14px}
.badge-paid{background:#d1fae5;color:#065f46}
.badge-pending{background:#fef3c7;color:#92400e}
.badge-overdue{background:#fee2e2;color:#991b1b}
.badge-partial{background:#dbeafe;color:#1e40af}
.method-icon{font-size:1.1rem}
@media print{.sidebar,.no-print{display:none!important}.main{margin:0!important}}
@media(max-width:768px){.sidebar{transform:translateX(-100%);transition:.3s}.sidebar.open{transform:translateX(0)}.main{margin-left:0}}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <div class="brand">
    <img src="images/school-logo.png" alt="ISNM">
    <h6>Bursar Portal</h6>
    <small><?= htmlspecialchars($uname) ?></small>
  </div>
  <nav>
    <a href="#overview"    class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="#search">                    <i class="fas fa-search"></i> Search Students</a>
    <a href="#payments">                  <i class="fas fa-money-bill-wave"></i> Record Payment</a>
    <a href="#transactions">              <i class="fas fa-history"></i> Transactions</a>
    <a href="#fees">                      <i class="fas fa-file-invoice-dollar"></i> Fee Structures</a>
    <a href="#budgets">                   <i class="fas fa-calculator"></i> Budgets</a>
    <a href="#expenditures">              <i class="fas fa-receipt"></i> Expenditures</a>
    <a href="#ledger">                    <i class="fas fa-book"></i> Ledger & Accounts</a>
    <a href="#mobile">                    <i class="fas fa-mobile-alt"></i> Mobile Money</a>
    <a href="#assets">                    <i class="fas fa-boxes"></i> Assets</a>
    <a href="#communications-finance">    <i class="fas fa-bell"></i> Communications</a>
    <a href="#debtors">                   <i class="fas fa-exclamation-triangle"></i> Debtors List</a>
    <a href="bursar_invoices.php">        <i class="fas fa-file-invoice"></i> Invoices</a>
    <a href="bursar_receipts.php">        <i class="fas fa-receipt"></i> Receipts</a>
    <a href="bursar_reports.php">         <i class="fas fa-chart-bar"></i> Reports</a>
    <a href="bursar_budgets.php">         <i class="fas fa-calculator"></i> Budgets</a>
    <a href="bursar_settings.php">        <i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php">                 <i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
      <button class="btn btn-sm btn-outline-secondary d-md-none me-2" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
      <h4 class="d-inline fw-bold" style="color:var(--primary)">Financial Dashboard</h4>
      <span class="ms-2 text-muted small">Role: <?= htmlspecialchars($urole) ?></span>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
      <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-plus me-1"></i>Record Payment</button>
    </div>
  </div>

  <?php if(!empty($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show py-2 no-print"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php unset($_SESSION['success']); endif; ?>
  <?php if(!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show py-2 no-print"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php unset($_SESSION['error']); endif; ?>

  <?php if($overdue_invoices > 0): ?>
  <div class="alert alert-warning d-flex align-items-center py-2 mb-3 no-print">
    <i class="fas fa-exclamation-circle me-2"></i>
    <strong><?= $overdue_invoices ?> overdue invoice(s)</strong>&nbsp;require immediate attention.
  </div>
  <?php endif; ?>

  <!-- STATS -->
  <section id="overview">
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="num text-success">UGX <?= number_format($today_collection) ?></div>
          <div class="lbl"><i class="fas fa-sun me-1"></i>Today's Collection</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#3b82f6">
          <div class="num" style="color:#3b82f6">UGX <?= number_format($month_collection) ?></div>
          <div class="lbl"><i class="fas fa-calendar me-1"></i>Monthly Collection</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#ef4444">
          <div class="num text-danger">UGX <?= number_format($outstanding) ?></div>
          <div class="lbl"><i class="fas fa-exclamation-triangle me-1"></i>Outstanding Fees</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#f59e0b">
          <div class="num" style="color:#f59e0b"><?= $pending_payments ?></div>
          <div class="lbl"><i class="fas fa-hourglass-half me-1"></i>Pending Payments</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#10b981">
          <div class="num text-success"><?= $students_cleared ?></div>
          <div class="lbl"><i class="fas fa-check-circle me-1"></i>Students Cleared</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#ef4444">
          <div class="num text-danger"><?= $students_not_cleared ?></div>
          <div class="lbl"><i class="fas fa-times-circle me-1"></i>Not Cleared</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#8b5cf6">
          <div class="num" style="color:#8b5cf6"><?= $overdue_invoices ?></div>
          <div class="lbl"><i class="fas fa-clock me-1"></i>Overdue Invoices</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#0ea5e9">
          <div class="num" style="color:#0ea5e9"><?= $total_students ?></div>
          <div class="lbl"><i class="fas fa-users me-1"></i>Total Students</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#6366f1">
          <div class="num" style="color:#6366f1">UGX <?= number_format($budget_total) ?></div>
          <div class="lbl"><i class="fas fa-calculator me-1"></i>Approved Budgets</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#f59e0b">
          <div class="num" style="color:#f59e0b"><?= $pending_expenses ?></div>
          <div class="lbl"><i class="fas fa-hourglass-half me-1"></i>Pending Expenses</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#8b5cf6">
          <div class="num" style="color:#8b5cf6"><?= $mobile_pending ?></div>
          <div class="lbl"><i class="fas fa-mobile-alt me-1"></i>Mobile Money Pending</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#111827">
          <div class="num" style="color:#111827"><?= $asset_count ?></div>
          <div class="lbl"><i class="fas fa-boxes me-1"></i>Active Assets</div>
        </div>
      </div>
    </div>
  </section>

  <!-- STUDENT SEARCH -->
  <section id="search" class="section-card no-print">
    <h5><i class="fas fa-search me-2"></i>Search Student / Print Statement</h5>
    <form method="GET" class="row g-2 mb-3">
      <div class="col-md-8"><input type="text" name="q" class="form-control" placeholder="Student name, registration number, or phone…" value="<?= htmlspecialchars($search) ?>"></div>
      <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button></div>
      <?php if($search): ?><div class="col-md-2"><a href="bursar_dashboard.php" class="btn btn-outline-secondary w-100">Clear</a></div><?php endif; ?>
    </form>
    <?php if($search && !empty($search_results)): ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle">
        <thead class="table-light"><tr><th>Reg No.</th><th>Name</th><th>Course</th><th>Phone</th><th>Total Billed</th><th>Total Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($search_results as $st): $cleared = $st['balance'] <= 0; ?>
        <tr>
          <td><code><?= htmlspecialchars($st['registration_number'] ?: $st['student_number']) ?></code></td>
          <td><strong><?= htmlspecialchars($st['full_name'] ?: $st['first_name'].' '.$st['surname']) ?></strong></td>
          <td><?= htmlspecialchars($st['course'] ?? '—') ?></td>
          <td><?= htmlspecialchars($st['phone'] ?? '—') ?></td>
          <td>UGX <?= number_format($st['total_billed'],0) ?></td>
          <td>UGX <?= number_format($st['total_paid'],0) ?></td>
          <td class="<?= $cleared?'text-success fw-bold':'text-danger fw-bold' ?>">UGX <?= number_format($st['balance'],0) ?></td>
          <td><span class="badge <?= $cleared?'badge-paid':'badge-overdue' ?>"><?= $cleared?'Cleared':'Owing' ?></span></td>
          <td>
            <a href="bursar_student_fees.php?id=<?= $st['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2">View</a>
            <button onclick="printStatement(<?= $st['id'] ?>,'<?= addslashes(htmlspecialchars($st['full_name']?:$st['first_name'].' '.$st['surname'])) ?>')" class="btn btn-sm btn-outline-success py-0 px-2"><i class="fas fa-print"></i></button>
            <button onclick="recordFor(<?= $st['id'] ?>,'<?= addslashes(htmlspecialchars($st['full_name']?:$st['first_name'].' '.$st['surname'])) ?>')" class="btn btn-sm btn-outline-warning py-0 px-2"><i class="fas fa-plus"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php elseif($search): ?>
    <p class="text-muted">No students found for "<strong><?= htmlspecialchars($search) ?></strong>".</p>
    <?php endif; ?>
  </section>

  <!-- RECENT TRANSACTIONS -->
  <section id="transactions" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-history me-2"></i>Recent Transactions</h5>
      <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="fas fa-print me-1"></i>Print</button>
    </div>
    <?php if(empty($recent_tx)): ?>
    <p class="text-muted small">No transactions recorded yet.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle">
        <thead class="table-light"><tr><th>Reference</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($recent_tx as $tx):
          $method = $tx['payment_method'] ?? 'Other';
          $icon = $method_logos[$method] ?? '💰';
          $badges = ['Completed'=>'badge-paid','Pending'=>'badge-pending','Failed'=>'badge-overdue','Reversed'=>'badge-overdue'];
          $bc = $badges[$tx['status']] ?? 'badge-partial';
        ?>
        <tr>
          <td><code><?= htmlspecialchars($tx['payment_reference']) ?></code></td>
          <td><?= htmlspecialchars($tx['sname'] ?? '—') ?> <small class="text-muted"><?= htmlspecialchars($tx['snum'] ?? '') ?></small></td>
          <td><strong>UGX <?= number_format($tx['amount_received'],0) ?></strong></td>
          <td><span class="method-icon"><?= $icon ?></span> <?= htmlspecialchars($method) ?></td>
          <td><?= date('d M Y', strtotime($tx['payment_date'])) ?></td>
          <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($tx['status']) ?></span></td>
          <td><a href="bursar_payment_detail.php?id=<?= $tx['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- FEE STRUCTURES -->
  <section id="fees" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-file-invoice-dollar me-2"></i>Fee Structures</h5>
      <button class="btn btn-sm btn-primary no-print" data-bs-toggle="modal" data-bs-target="#feeModal"><i class="fas fa-plus me-1"></i>Add Fee</button>
    </div>
    <?php if(empty($fee_structures)): ?>
    <p class="text-muted small">No fee structures configured yet.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead class="table-light"><tr><th>Fee Name</th><th>Type</th><th>Amount (UGX)</th><th>Academic Year</th><th>Semester</th><th>Due Date</th><th>Mandatory</th></tr></thead>
        <tbody>
        <?php foreach($fee_structures as $fs): ?>
        <tr>
          <td><?= htmlspecialchars($fs['fee_name']) ?></td>
          <td><span class="badge bg-info text-dark"><?= htmlspecialchars($fs['fee_type']) ?></span></td>
          <td><strong><?= number_format($fs['amount'],0) ?></strong></td>
          <td><?= htmlspecialchars($fs['academic_year'] ?? '—') ?></td>
          <td><?= htmlspecialchars($fs['semester'] ?? '—') ?></td>
          <td><?= $fs['due_date'] ?: '—' ?></td>
          <td><?= $fs['is_mandatory'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- DEBTORS -->
  <section id="debtors" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-exclamation-triangle me-2" style="color:#ef4444"></i>Debtors List</h5>
      <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="fas fa-print me-1"></i>Print</button>
    </div>
    <?php if(empty($debtors)): ?>
    <p class="text-success"><i class="fas fa-check-circle me-1"></i>No outstanding debts found.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead class="table-light"><tr><th>Reg No.</th><th>Name</th><th>Course</th><th>Phone</th><th>Total Owing</th><th>Last Due</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($debtors as $d): ?>
        <tr>
          <td><code><?= htmlspecialchars($d['snum']) ?></code></td>
          <td><?= htmlspecialchars($d['sname']) ?></td>
          <td><?= htmlspecialchars($d['course'] ?? '—') ?></td>
          <td><?= htmlspecialchars($d['phone'] ?? '—') ?></td>
          <td class="text-danger fw-bold">UGX <?= number_format($d['total_owing'],0) ?></td>
          <td><?= $d['last_due'] ?: '—' ?></td>
          <td class="no-print">
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="send_reminder">
              <input type="hidden" name="student_id" value="0">
              <button class="btn btn-sm btn-outline-warning py-0 px-2"><i class="fas fa-bell"></i> Remind</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
  <!-- BUDGETS -->
  <section id="budgets" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-calculator me-2"></i>Budgeting & Expenditure Management</h5>
      <button class="btn btn-sm btn-primary no-print" data-bs-toggle="modal" data-bs-target="#budgetModal"><i class="fas fa-plus me-1"></i>Create Budget</button>
    </div>
    <?php if(empty($active_budgets)): ?><p class="text-muted small">No budget drafts or approved budgets yet.</p><?php else: ?>
    <div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Budget</th><th>Fiscal Year</th><th>Total</th><th>Allocated</th><th>Status</th></tr></thead><tbody><?php foreach($active_budgets as $b): ?>
      <tr><td><?= htmlspecialchars($b['budget_name']) ?></td><td><?= htmlspecialchars($b['fiscal_year']) ?></td><td>UGX <?= number_format($b['total_budget'],0) ?></td><td>UGX <?= number_format($b['allocated'],0) ?></td><td><span class="badge <?= $b['status']==='Approved'?'bg-success':($b['status']==='Active'?'bg-primary':'bg-secondary') ?>"><?= htmlspecialchars($b['status']) ?></span></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>

  <!-- EXPENDITURES -->
  <section id="expenditures" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-receipt me-2"></i>Expenditure Tracking</h5>
      <button class="btn btn-sm btn-warning no-print" data-bs-toggle="modal" data-bs-target="#expenseModal"><i class="fas fa-plus me-1"></i>Record Expense</button>
    </div>
    <?php if(empty($pending_expenditures)): ?><p class="text-muted small"><i class="fas fa-check-circle text-success me-1"></i>No pending expenditure approvals.</p><?php else: ?>
    <div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Date</th><th>Department</th><th>Category</th><th>Description</th><th>Amount</th><th>Status</th></tr></thead><tbody><?php foreach($pending_expenditures as $e): ?>
      <tr><td><?= date('d M Y',strtotime($e['expense_date'])) ?></td><td><?= htmlspecialchars($e['department'] ?? $e['budget_department'] ?? '—') ?></td><td><?= htmlspecialchars($e['category'] ?? '—') ?></td><td><?= htmlspecialchars(substr($e['description'],0,80)) ?></td><td>UGX <?= number_format($e['amount'],0) ?></td><td><span class="badge bg-warning text-dark"><?= htmlspecialchars($e['approval_status']) ?></span></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>

  <!-- LEDGER -->
  <section id="ledger" class="section-card">
    <h5><i class="fas fa-book me-2"></i>Accounts, Ledger & Reconciliation</h5>
    <div class="row g-3">
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $ledger_entries ?></div><small>Ledger entries</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= bq($sconn,"SELECT COUNT(*) v FROM chart_accounts WHERE is_active=1") ?></div><small>Active chart accounts</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= bq($sconn,"SELECT COUNT(*) v FROM bank_accounts WHERE is_active=1") ?></div><small>Active bank accounts</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= bq($sconn,"SELECT COUNT(*) v FROM bank_reconciliations WHERE status='Completed'") ?></div><small>Completed reconciliations</small></div></div>
    </div>
  </section>

  <!-- MOBILE MONEY -->
  <section id="mobile" class="section-card">
    <h5><i class="fas fa-mobile-alt me-2"></i>Mobile Money & Payment Verification</h5>
    <?php if(empty($mobile_transactions)): ?><p class="text-muted small">No mobile money transactions recorded yet.</p><?php else: ?>
    <div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Provider</th><th>Student</th><th>Amount</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($mobile_transactions as $m): ?>
      <tr><td><?= htmlspecialchars($m['provider']) ?></td><td><?= htmlspecialchars($m['sname'] ?? '—') ?></td><td>UGX <?= number_format($m['amount'],0) ?></td><td><?= htmlspecialchars($m['phone_number'] ?? '—') ?></td><td><span class="badge <?= $m['status']==='Completed'?'bg-success':'bg-warning text-dark' ?>"><?= htmlspecialchars($m['status']) ?></span></td><td><?php if($m['status']==='Initiated'): ?><form method="POST" class="d-inline"><input type="hidden" name="action" value="approve_payment"><input type="hidden" name="payment_id" value="<?= $m['id'] ?>"><button class="btn btn-sm btn-success py-0 px-2"><i class="fas fa-check"></i></button></form><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>

  <!-- ASSETS -->
  <section id="assets" class="section-card">
    <h5><i class="fas fa-boxes me-2"></i>Inventory & Asset Financial Tracking</h5>
    <div class="row g-3">
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $asset_count ?></div><small>Active assets</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold">UGX <?= number_format(bq($sconn,"SELECT COALESCE(SUM(purchase_amount),0) v FROM assets WHERE status='Active'")) ?></div><small>Purchase value</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= bq($sconn,"SELECT COUNT(*) v FROM asset_depreciation") ?></div><small>Depreciation entries</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $ura_draft ?></div><small>URA report drafts</small></div></div>
    </div>
  </section>

  <!-- FINANCE COMMUNICATIONS -->
  <section id="communications-finance" class="section-card">
    <h5><i class="fas fa-bell me-2"></i>Financial Communication Tools</h5>
    <p class="text-muted small">Fee reminders, overdue payment alerts, financial announcements, and notification queue entries are stored through <code>fee_reminders</code> and <code>notification_queue</code>.</p>
    <?php if(!empty($recent_reminders)): ?>
    <div class="list-group"><?php foreach($recent_reminders as $r): ?><div class="list-group-item"><strong><?= htmlspecialchars($r['sname'] ?? 'Student') ?></strong><span class="badge bg-secondary ms-2"><?= htmlspecialchars($r['reminder_type']) ?></span><small class="d-block text-muted"><?= date('d M Y H:i',strtotime($r['sent_at'])) ?></small></div><?php endforeach; ?></div>
    <?php endif; ?>
  </section>

  <!-- FINANCE REPORTS -->
  <section id="finance-reports" class="section-card">
    <h5><i class="fas fa-chart-line me-2"></i>Financial Reports & Analytics</h5>
    <div class="row g-3">
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $students_cleared ?></div><small>Students cleared</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $students_not_cleared ?></div><small>Students not cleared</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= bq($sconn,"SELECT COUNT(*) v FROM payment_receipts WHERE created_at>=CURDATE()") ?></div><small>Receipts today</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $pending_payments ?></div><small>Pending approvals</small></div></div>
    </div>
  </section>
</div>

<!-- BUDGET MODAL -->
<div class="modal fade" id="budgetModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="create_budget">
      <div class="modal-header bg-primary text-white"><h5 class="modal-title">Create Budget</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><div class="row g-3"><div class="col-12"><label class="form-label">Budget Name</label><input type="text" name="budget_name" class="form-control" required></div><div class="col-md-6"><label class="form-label">Fiscal Year</label><input type="text" name="fiscal_year" class="form-control" value="<?= date('Y') ?>"></div><div class="col-md-6"><label class="form-label">Total Budget</label><input type="number" name="total_budget" class="form-control" min="0" step="1000" required></div></div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create Budget</button></div>
    </form>
  </div>
</div>

<!-- EXPENSE MODAL -->
<div class="modal fade" id="expenseModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="record_expenditure">
      <div class="modal-header bg-warning text-dark"><h5 class="modal-title">Record Expenditure</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Expense Date</label><input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>"></div><div class="col-md-6"><label class="form-label">Department</label><input type="text" name="department" class="form-control"></div><div class="col-md-6"><label class="form-label">Category</label><input type="text" name="category" class="form-control" value="General"></div><div class="col-md-6"><label class="form-label">Amount</label><input type="number" name="amount" class="form-control" min="1" step="1000" required></div><div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" required></textarea></div></div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning">Submit for Approval</button></div>
    </form>
  </div>
</div>

<!-- RECORD PAYMENT MODAL -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="record_payment">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-money-bill-wave me-2"></i>Record Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Student ID *</label>
            <input type="number" name="student_id" id="pay_student_id" class="form-control" required placeholder="Enter student DB ID">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Amount (UGX) *</label>
            <input type="number" name="amount" class="form-control" required min="1" step="1000">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Payment Method *</label>
            <select name="payment_method" class="form-select" required>
              <option>Cash</option>
              <option>MTN Mobile Money</option>
              <option>Airtel Money</option>
              <option>Bank Transfer</option>
              <option>Cheque</option>
              <option>MasterCard</option>
              <option>Visa</option>
              <option>Stanbic Bank</option>
              <option>Centenary Bank</option>
              <option>Equity Bank</option>
              <option>Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Transaction Reference</label>
            <input type="text" name="reference" class="form-control" placeholder="e.g. MTN TxID, Bank Ref">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Payment Date</label>
            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Invoice ID (optional)</label>
            <input type="number" name="invoice_id" class="form-control" placeholder="Leave blank if not applicable">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Notes</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Record Payment</button>
      </div>
    </form>
  </div>
</div>

<!-- ADD FEE MODAL -->
<div class="modal fade" id="feeModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add_fee_structure">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Fee Structure</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="form-label fw-semibold">Fee Name *</label><input type="text" name="fee_name" class="form-control" required placeholder="e.g. Tuition Fee – Nursing Year 1"></div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Fee Type</label>
            <select name="fee_type" class="form-select">
              <option>Tuition</option><option>Registration</option><option>Library</option>
              <option>Laboratory</option><option>Examination</option><option>Graduation</option>
              <option>Hostel</option><option>Clinical</option><option>Other</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label fw-semibold">Amount (UGX) *</label><input type="number" name="amount" class="form-control" required min="0" step="1000"></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Academic Year</label><input type="text" name="academic_year" class="form-control" placeholder="2024-2025"></div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Semester</label>
            <select name="semester" class="form-select"><option value="">All</option><option>Semester 1</option><option>Semester 2</option></select>
          </div>
          <div class="col-md-6"><label class="form-label fw-semibold">Due Date</label><input type="date" name="due_date" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Fee</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function recordFor(id, name){
  document.getElementById('pay_student_id').value = id;
  const m = new bootstrap.Modal(document.getElementById('paymentModal'));
  m.show();
}
function printStatement(id, name){
  window.open('bursar_student_fees.php?id='+id+'&print=1','_blank');
}
document.querySelectorAll('.sidebar nav a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    e.preventDefault();
    const t=document.querySelector(a.getAttribute('href'));
    if(t) t.scrollIntoView({behavior:'smooth',block:'start'});
    document.querySelectorAll('.sidebar nav a').forEach(x=>x.classList.remove('active'));
    a.classList.add('active');
  });
});
</script>
</body>
</html>
