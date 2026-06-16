<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['bursar', 'finance']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];

$view  = $_GET['view'] ?? 'home';
$ajax  = $_GET['ajax'] ?? '';
$sid   = $_GET['sid'] ?? '';
$q     = $_GET['q'] ?? '';

// ── AJAX endpoints (must exit before any HTML output) ────────────

// record_payment - get student fee balance
if ($view === 'record_payment' && $ajax === '1' && $sid) {
    header('Content-Type: application/json');
    $balance = 0;
    $fee_account_id = 0;
    try {
        if ($staffDb) {
            $stmt = $staffDb->prepare("SELECT id, balance FROM student_fee_accounts WHERE student_id = ? AND status NOT IN ('fully_paid','cancelled') ORDER BY id DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $sid);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $balance = (float)$row['balance'];
                    $fee_account_id = (int)$row['id'];
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) { error_log('ajax fee: ' . $e->getMessage()); }
    echo json_encode(['balance' => $balance, 'fee_account_id' => $fee_account_id]);
    exit;
}

// student_statement - load full statement
if ($view === 'student_statement' && $ajax === '1' && $sid) {
    header('Content-Type: application/json');
    $txns = [];
    try {
        if ($staffDb) {
            $stmt = $staffDb->prepare("SELECT payment_date AS date, amount_paid AS amount, receipt_number, payment_method FROM fee_payments WHERE student_id = ? AND status='verified' ORDER BY payment_date ASC");
            if ($stmt) {
                $stmt->bind_param("s", $sid);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $txns[] = ['date' => $row['date'], 'description' => 'Payment - ' . ($row['receipt_number'] ?? '') . ' (' . ($row['payment_method'] ?? '') . ')', 'type' => 'payment', 'amount' => (float)$row['amount']];
                }
                $stmt->close();
            }
            $stmt = $staffDb->prepare("SELECT created_at AS date, total_fees AS amount, invoice_number FROM student_fee_accounts WHERE student_id = ? ORDER BY created_at ASC");
            if ($stmt) {
                $stmt->bind_param("s", $sid);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $txns[] = ['date' => $row['date'], 'description' => 'Invoice - ' . ($row['invoice_number'] ?? ''), 'type' => 'invoice', 'amount' => (float)$row['amount']];
                }
                $stmt->close();
            }
            usort($txns, function($a, $b) { return strcmp($a['date'], $b['date']); });
        }
    } catch (Exception $e) { error_log('ajax stmt: ' . $e->getMessage()); }
    echo json_encode(['opening_balance' => 0, 'transactions' => $txns]);
    exit;
}

// receipt_print - search by receipt number or student
if ($view === 'receipt_print' && $ajax === '1' && $q) {
    header('Content-Type: application/json');
    $data = ['found' => false];
    try {
        if ($staffDb && $studentsDb) {
            $like = "%$q%";
            $stmt = $staffDb->prepare("SELECT fp.payment_id, fp.student_id, fp.amount_paid, fp.payment_method, fp.payment_reference AS ref, fp.receipt_number, fp.payment_date, fp.status, s.first_name, s.surname FROM fee_payments fp LEFT JOIN students s ON fp.student_id = s.student_id WHERE fp.receipt_number LIKE ? OR fp.student_id LIKE ? OR s.first_name LIKE ? OR s.surname LIKE ? ORDER BY fp.payment_date DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ssss", $like, $like, $like, $like);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $bal = 0;
                    $bStmt = $staffDb->prepare("SELECT balance FROM student_fee_accounts WHERE student_id = ? AND status NOT IN ('fully_paid','cancelled') ORDER BY id DESC LIMIT 1");
                    if ($bStmt) {
                        $bStmt->bind_param("s", $row['student_id']);
                        $bStmt->execute();
                        $bRes = $bStmt->get_result();
                        if ($bRow = $bRes->fetch_assoc()) $bal = (float)$bRow['balance'];
                        $bStmt->close();
                    }
                    $data = [
                        'found' => true,
                        'receipt_number' => $row['receipt_number'],
                        'payment_date' => $row['payment_date'],
                        'student_name' => ($row['surname'] ?? '') . ', ' . ($row['first_name'] ?? ''),
                        'student_id' => $row['student_id'],
                        'amount' => (float)$row['amount_paid'],
                        'method' => ucfirst(str_replace('_', ' ', $row['payment_method'] ?? '')),
                        'reference' => $row['ref'] ?? '',
                        'status' => ucfirst($row['status'] ?? ''),
                        'balance' => $bal
                    ];
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) { error_log('ajax receipt: ' . $e->getMessage()); }
    echo json_encode($data);
    exit;
}

// financial_reports
if ($view === 'financial_reports' && $ajax === '1') {
    header('Content-Type: application/json');
    $from   = $_GET['from'] ?? date('Y-m-01');
    $to     = $_GET['to'] ?? date('Y-m-d');
    $type   = $_GET['type'] ?? 'daily_collections';
    $result = ['headers' => [], 'rows' => [], 'total' => 0];
    try {
        if ($staffDb) {
            if ($type === 'daily_collections') {
                $result['headers'] = ['Date', 'Transactions', 'Total Collected'];
                $r = $staffDb->query("SELECT DATE(payment_date) AS dt, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='verified' GROUP BY DATE(payment_date) ORDER BY dt");
                if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['dt'], $row['cnt'], 'UGX ' . number_format($row['tot'])]; $result['total'] += $row['tot']; }
            } elseif ($type === 'monthly_summary') {
                $result['headers'] = ['Month', 'Payments', 'Total'];
                $r = $staffDb->query("SELECT DATE_FORMAT(payment_date,'%Y-%m') AS m, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='verified' GROUP BY DATE_FORMAT(payment_date,'%Y-%m') ORDER BY m");
                if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['m'], $row['cnt'], 'UGX ' . number_format($row['tot'])]; $result['total'] += $row['tot']; }
            } elseif ($type === 'revenue_by_category') {
                $result['headers'] = ['Payment Method', 'Transactions', 'Total'];
                $r = $staffDb->query("SELECT payment_method, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to' AND status='verified' GROUP BY payment_method");
                if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [ucfirst(str_replace('_', ' ', $row['payment_method'] ?? 'Unknown')), $row['cnt'], 'UGX ' . number_format($row['tot'])]; $result['total'] += $row['tot']; }
            } elseif ($type === 'outstanding_debtors') {
                $result['headers'] = ['Student ID', 'Total Fees', 'Paid', 'Balance'];
                $r = $staffDb->query("SELECT student_id, total_fees, amount_paid, balance FROM student_fee_accounts WHERE status NOT IN ('fully_paid','cancelled') ORDER BY balance DESC LIMIT 50");
                if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['student_id'], 'UGX ' . number_format($row['total_fees']), 'UGX ' . number_format($row['amount_paid']), 'UGX ' . number_format($row['balance'])]; $result['total'] += $row['balance']; }
            }
        }
    } catch (Exception $e) { error_log('ajax report: ' . $e->getMessage()); }
    echo json_encode($result);
    exit;
}

// daily_collections
if ($view === 'daily_collections' && $ajax === '1') {
    header('Content-Type: application/json');
    $date = $_GET['date'] ?? date('Y-m-d');
    $data = ['total' => 0, 'count' => 0, 'methods' => [], 'payments' => []];
    try {
        if ($staffDb) {
            $r = $staffDb->query("SELECT fp.*, s.first_name, s.surname FROM fee_payments fp LEFT JOIN students s ON fp.student_id = s.student_id WHERE DATE(fp.payment_date) = '$date' AND fp.status='verified' ORDER BY fp.payment_date DESC");
            if ($r) {
                $data['count'] = $r->num_rows;
                while ($row = $r->fetch_assoc()) {
                    $data['total'] += (float)$row['amount_paid'];
                    $m = ucfirst(str_replace('_', ' ', $row['payment_method'] ?? 'Unknown'));
                    $data['methods'][$m] = ($data['methods'][$m] ?? 0) + (float)$row['amount_paid'];
                    $data['payments'][] = ['student_name' => ($row['surname'] ?? '') . ' ' . ($row['first_name'] ?? ''), 'student_id' => $row['student_id'], 'receipt_number' => $row['receipt_number'] ?? '', 'amount' => $row['amount_paid'], 'method' => $m];
                }
            }
        }
    } catch (Exception $e) { error_log('ajax daily: ' . $e->getMessage()); }
    echo json_encode($data);
    exit;
}

// generate_invoice - fetch fee structure for student's program
if ($view === 'generate_invoice' && $ajax === '1' && $sid) {
    header('Content-Type: application/json');
    $fees = [];
    try {
        if ($studentsDb && $staffDb) {
            $prog = $studentsDb->query("SELECT program FROM students WHERE student_id = '" . $studentsDb->real_escape_string($sid) . "' LIMIT 1");
            if ($prog && ($p = $prog->fetch_assoc())) {
                $program = $p['program'];
                $fs = $staffDb->query("SELECT item_name, amount FROM fee_structures WHERE program = '' OR program = '" . $staffDb->real_escape_string($program) . "' ORDER BY id");
                if ($fs) while ($f = $fs->fetch_assoc()) $fees[] = $f;
            }
        }
    } catch (Exception $e) { error_log('ajax fee struct: ' . $e->getMessage()); }
    echo json_encode($fees);
    exit;
}

// If an ajax param was present but no endpoint matched, just exit silently
if (isset($_GET['ajax'])) exit;

// ── POST Handlers ────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'record_payment' && $staffDb) {
        try {
            $student_id    = trim($_POST['student_id'] ?? '');
            $fee_account_id = (int)($_POST['fee_account_id'] ?? 0);
            $amount        = (float)($_POST['amount'] ?? 0);
            $method        = trim($_POST['payment_method'] ?? 'cash');
            $reference     = trim($_POST['reference'] ?? '');
            $notes         = trim($_POST['notes'] ?? '');
            $payment_date  = trim($_POST['payment_date'] ?? date('Y-m-d'));
            if ($student_id === '' || $amount <= 0) {
                $_SESSION['error'] = 'Student ID and valid amount required.';
            } else {
                $receipt = generateReceiptNumber();
                $stmt = $staffDb->prepare("INSERT INTO fee_payments (payment_id, student_id, fee_account_id, amount_paid, payment_method, payment_reference, receipt_number, notes, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified')");
                if ($stmt) {
                    $pid = $receipt;
                    $stmt->bind_param("ssidsssss", $pid, $student_id, $fee_account_id, $amount, $method, $reference, $receipt, $notes, $payment_date);
                    if ($stmt->execute()) {
                        if ($fee_account_id > 0) {
                            $upd = $staffDb->prepare("UPDATE student_fee_accounts SET amount_paid = amount_paid + ?, balance = balance - ?, last_payment_date = ?, status = CASE WHEN (balance - ?) <= 0 THEN 'fully_paid' WHEN amount_paid + ? > 0 THEN 'partially_paid' ELSE 'unpaid' END WHERE id = ?");
                            if ($upd) {
                                $upd->bind_param("ddsddi", $amount, $amount, $payment_date, $amount, $amount, $fee_account_id);
                                $upd->execute();
                                $upd->close();
                            }
                        }
                        $_SESSION['success'] = "Payment recorded. Receipt: $receipt";
                    } else {
                        $_SESSION['error'] = 'Payment failed: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error'] = 'Prepare failed: ' . $staffDb->error;
                }
            }
        } catch (Exception $e) { $_SESSION['error'] = 'Error: ' . $e->getMessage(); }
        header('Location: bursar.php?view=record_payment');
        exit;
    }

    if ($action === 'generate_invoice' && $staffDb) {
        try {
            $student_id    = trim($_POST['student_id'] ?? '');
            $academic_year = trim($_POST['academic_year'] ?? date('Y') . '/' . (date('Y') + 1));
            $total_fees    = (float)($_POST['total_amount'] ?? 0);
            $due_date      = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));
            if ($student_id === '' || $total_fees <= 0) {
                $_SESSION['error'] = 'Student and amount are required.';
            } else {
                $inv_prefix = 'INV-' . date('Y') . '-';
                $cnt = $staffDb->query("SELECT COUNT(*) AS c FROM student_fee_accounts WHERE invoice_number LIKE '$inv_prefix%'");
                $inv_no = $inv_prefix . str_pad(($cnt ? (int)$cnt->fetch_assoc()['c'] + 1 : 1), 5, '0', STR_PAD_LEFT);
                $stmt = $staffDb->prepare("INSERT INTO student_fee_accounts (student_id, academic_year, invoice_number, total_fees, amount_paid, balance, due_date, status) VALUES (?, ?, ?, ?, 0, ?, ?, 'unpaid')");
                if ($stmt) {
                    $stmt->bind_param("sssdd", $student_id, $academic_year, $inv_no, $total_fees, $total_fees, $due_date);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Invoice $inv_no created for UGX " . number_format($total_fees);
                    } else {
                        $_SESSION['error'] = 'Invoice creation failed: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error'] = 'Prepare failed: ' . $staffDb->error;
                }
            }
        } catch (Exception $e) { $_SESSION['error'] = 'Error: ' . $e->getMessage(); }
        header('Location: bursar.php?view=generate_invoice');
        exit;
    }

    if ($action === 'add_fee_item' && $staffDb) {
        try {
            $name    = trim($_POST['item_name'] ?? '');
            $amount  = (float)($_POST['item_amount'] ?? 0);
            $program = trim($_POST['program'] ?? '');
            $year    = trim($_POST['year'] ?? '');
            $stmt = $staffDb->prepare("INSERT INTO fee_structures (item_name, amount, program, year_level) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sdss", $name, $amount, $program, $year);
                $stmt->execute() ? $_SESSION['success'] = 'Fee item added.' : $_SESSION['error'] = $stmt->error;
                $stmt->close();
            }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header('Location: bursar.php?view=fee_structure');
        exit;
    }

    if ($action === 'delete_fee_item' && $staffDb) {
        try {
            $id = (int)($_POST['item_id'] ?? 0);
            $staffDb->query("DELETE FROM fee_structures WHERE id = $id");
            $_SESSION['success'] = 'Fee item deleted.';
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header('Location: bursar.php?view=fee_structure');
        exit;
    }

    if ($action === 'add_budget' && $staffDb) {
        try {
            $name  = trim($_POST['budget_name'] ?? '');
            $fy    = trim($_POST['fiscal_year'] ?? date('Y'));
            $dept  = trim($_POST['department'] ?? '');
            $total = (float)($_POST['total_budget'] ?? 0);
            $stmt = $staffDb->prepare("INSERT INTO budgets (name, fiscal_year, department, total_budget, spent) VALUES (?, ?, ?, ?, 0)");
            if ($stmt) {
                $stmt->bind_param("sssd", $name, $fy, $dept, $total);
                $stmt->execute() ? $_SESSION['success'] = 'Budget added.' : $_SESSION['error'] = $stmt->error;
                $stmt->close();
            }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header('Location: bursar.php?view=budget');
        exit;
    }

    if ($action === 'delete_budget' && $staffDb) {
        try {
            $id = (int)($_POST['budget_id'] ?? 0);
            $staffDb->query("DELETE FROM budgets WHERE id = $id");
            $_SESSION['success'] = 'Budget deleted.';
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header('Location: bursar.php?view=budget');
        exit;
    }
}

// ── Dashboard Stats ───────────────────────────────────────────────

$today_collections = 0;
$week_collections  = 0;
$outstanding_fees  = 0;
$students_cleared  = 0;

try {
    if ($staffDb) {
        $r = $staffDb->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE DATE(payment_date)=CURDATE() AND status='verified'");
        if ($r) $today_collections = (float)$r->fetch_assoc()['t'];
        $r = $staffDb->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE YEARWEEK(payment_date)=YEARWEEK(CURDATE()) AND status='verified'");
        if ($r) $week_collections = (float)$r->fetch_assoc()['t'];
        $r = $staffDb->query("SELECT COALESCE(SUM(balance),0) AS t FROM student_fee_accounts WHERE status NOT IN ('fully_paid','cancelled')");
        if ($r) $outstanding_fees = (float)$r->fetch_assoc()['t'];
        $r = $staffDb->query("SELECT COUNT(*) AS c FROM student_fee_accounts WHERE status='fully_paid'");
        if ($r) $students_cleared = (int)$r->fetch_assoc()['c'];
    }
} catch (Exception $e) { error_log('bursar stats: ' . $e->getMessage()); }

// ── Helpers ───────────────────────────────────────────────────────

function bsBadge($s) {
    $m = ['verified'=>'success','pending'=>'warning','failed'=>'danger','fully_paid'=>'success','partially_paid'=>'info','unpaid'=>'secondary','cancelled'=>'dark'];
    $c = $m[strtolower($s)] ?? 'secondary';
    return "<span class=\"badge bg-$c\">" . htmlspecialchars($s) . '</span>';
}
function currency($n) { return 'UGX ' . number_format((float)$n, 0); }

// ── HTML ──────────────────────────────────────────────────────────
$pageTitle = 'Bursar Dashboard';
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?> - ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
<style>
:root{--bp:#1a237e;--bs:#3949ab}
*{box-sizing:border-box}
body{font-family:'Inter','Segoe UI',sans-serif;background:#f0f2f5;margin:0;padding:0;min-height:100vh}
.ma{margin-left:270px;padding:24px 28px;min-height:100vh}
.ph{background:#fff;border-radius:16px;padding:20px 28px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.ph h1{margin:0;font-size:22px;font-weight:700;color:var(--bp)}
.ph p{margin:2px 0 0;font-size:13px;color:#64748b}
.sc{background:#fff;border-radius:14px;padding:18px 22px;box-shadow:0 1px 4px rgba(0,0,0,.06);height:100%;transition:transform .15s;border-left:4px solid var(--bp)}
.sc:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.sc .si{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;background:linear-gradient(135deg,var(--bp),var(--bs));margin-bottom:10px}
.sc .sv{font-size:26px;font-weight:700;color:#0f172a;margin:0}
.sc .sl{font-size:13px;color:#64748b;margin:2px 0 0}
.ag{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:28px}
.ab{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 10px;text-align:center;cursor:pointer;transition:all .15s;text-decoration:none;color:#334155;display:flex;flex-direction:column;align-items:center;gap:6px}
.ab:hover{border-color:var(--bp);color:var(--bp);box-shadow:0 2px 8px rgba(26,35,126,.1)}
.ab i{font-size:22px;color:var(--bp)}
.ab span{font-size:12px;font-weight:500}
.cc{background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);border:none;margin-bottom:20px}
.cc .ch{background:0 0;border-bottom:1px solid #f1f5f9;padding:16px 22px;font-weight:600;font-size:15px;color:#0f172a}
.cc .cb{padding:20px 22px}
.st{font-size:16px;font-weight:600;color:#0f172a;margin:0 0 16px}
.bb{background:linear-gradient(135deg,var(--bp),var(--bs));color:#fff;border:none;border-radius:8px;padding:8px 20px;font-size:13px;font-weight:500}
.bb:hover{color:#fff;box-shadow:0 4px 12px rgba(26,35,126,.25)}
.bo{border:1px solid var(--bp);color:var(--bp);background:0 0;border-radius:8px;padding:8px 20px;font-size:13px;font-weight:500}
.bo:hover{background:var(--bp);color:#fff}
.tb{font-size:13px;margin:0}
.tb thead th{background:#f8fafc;color:#475569;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.3px;border-bottom:2px solid #e2e8f0;padding:10px 12px}
.tb td{padding:10px 12px;vertical-align:middle;border-bottom:1px solid #f1f5f9}
.tb tr:hover td{background:#f8fafc}
.fl{font-size:13px;font-weight:500;color:#334155;margin-bottom:4px}
.fc,.fs{border-radius:8px;border:1px solid #e2e8f0;font-size:13px;padding:8px 12px}
.fc:focus,.fs:focus{border-color:var(--bp);box-shadow:0 0 0 3px rgba(26,35,126,.1)}
.sri{padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:8px;cursor:pointer;transition:all .1s}
.sri:hover{border-color:var(--bp);background:#f8fafc}
.sri.active{border-color:var(--bp);background:#eef2ff}
.pr{max-width:700px;margin:0 auto;background:#fff;padding:30px 35px;border-radius:12px}
.rh{text-align:center;margin-bottom:24px;border-bottom:2px dashed #e2e8f0;padding-bottom:20px}
.rh h3{font-weight:700;color:#0f172a;margin:0 0 4px}
.rh p{margin:0;color:#64748b;font-size:13px}
.rb .rl{display:flex;justify-content:space-between;padding:6px 0;font-size:13px;border-bottom:1px dotted #e2e8f0}
.rb .rl strong{color:#334155}
.rtot{font-size:18px;font-weight:700;color:var(--bp);text-align:right;padding-top:12px}
.attoast{position:fixed;top:20px;right:20px;z-index:9999;min-width:300px}
@media print{body{background:#fff!important}.ma{margin-left:0!important;padding:0!important}.no-print,.ph,.ag,.ch,.bb,.bo,.attoast{display:none!important}.cc{box-shadow:none!important;border:1px solid #ddd!important;border-radius:0!important}.tb td,.tb th{border-color:#bbb!important}.pr{padding:0!important;border-radius:0!important;box-shadow:none!important}}
@media(max-width:768px){.ma{margin-left:0;padding:16px}.ag{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="ma">

    <div class="ph">
        <div>
            <h1><i class="fas fa-calculator me-2"></i>Bursar Dashboard</h1>
            <p>Financial Management &mdash; IGANGA SCHOOL OF NURSING &amp; MIDWIFERY</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="liveClock" class="text-muted" style="font-size:13px"><i class="far fa-clock me-1"></i><span></span></span>
            <a href="bursar.php" class="bo btn-sm <?= $view === 'home' ? 'd-none' : '' ?>"><i class="fas fa-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <?php if ($view === 'home'): ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="sc"><div class="si"><i class="fas fa-money-bill-wave"></i></div><div class="sv"><?= currency($today_collections) ?></div><div class="sl">Today's Collections</div></div></div>
        <div class="col-md-3 col-6"><div class="sc" style="border-left-color:#0891b2"><div class="si" style="background:linear-gradient(135deg,#0891b2,#06b6d4)"><i class="fas fa-calendar-week"></i></div><div class="sv"><?= currency($week_collections) ?></div><div class="sl">This Week</div></div></div>
        <div class="col-md-3 col-6"><div class="sc" style="border-left-color:#d97706"><div class="si" style="background:linear-gradient(135deg,#d97706,#f59e0b)"><i class="fas fa-exclamation-triangle"></i></div><div class="sv"><?= currency($outstanding_fees) ?></div><div class="sl">Outstanding Fees</div></div></div>
        <div class="col-md-3 col-6"><div class="sc" style="border-left-color:#16a34a"><div class="si" style="background:linear-gradient(135deg,#16a34a,#22c55e)"><i class="fas fa-user-check"></i></div><div class="sv"><?= number_format($students_cleared) ?></div><div class="sl">Students Cleared</div></div></div>
    </div>

    <div class="ag">
        <a href="?view=record_payment" class="ab"><i class="fas fa-hand-holding-usd"></i><span>Record Payment</span></a>
        <a href="?view=generate_invoice" class="ab"><i class="fas fa-file-invoice"></i><span>Generate Invoice</span></a>
        <a href="?view=fee_structure" class="ab"><i class="fas fa-tags"></i><span>Fee Structure</span></a>
        <a href="?view=student_statement" class="ab"><i class="fas fa-file-alt"></i><span>Student Statement</span></a>
        <a href="?view=receipt_print" class="ab"><i class="fas fa-receipt"></i><span>Receipt Print</span></a>
        <a href="?view=financial_reports" class="ab"><i class="fas fa-chart-bar"></i><span>Financial Reports</span></a>
        <a href="?view=budget" class="ab"><i class="fas fa-wallet"></i><span>Budget</span></a>
        <a href="?view=daily_collections" class="ab"><i class="fas fa-list-ol"></i><span>Daily Collections</span></a>
        <a href="payment-subscriptions.php" class="ab"><i class="fas fa-sync"></i><span>Auto-Deductions</span></a>
    </div>

    <div class="cc">
        <div class="ch">Recent Transactions</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>#</th><th>Student</th><th>Receipt</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$txnRows = '';
$txnCount = 0;
try {
    if ($staffDb) {
        $rp = $staffDb->query("SELECT fp.payment_id, fp.student_id, fp.amount_paid, fp.payment_method, fp.receipt_number, fp.payment_date, fp.status, s.first_name, s.surname FROM fee_payments fp LEFT JOIN students s ON fp.student_id = s.student_id ORDER BY fp.payment_date DESC LIMIT 10");
        if ($rp) {
            while ($row = $rp->fetch_assoc()) {
                $txnCount++;
                $txnRows .= '<tr><td>' . $txnCount . '</td><td>' . htmlspecialchars(($row['surname'] ?? '') . ' ' . ($row['first_name'] ?? '')) . '<br><small class="text-muted">' . htmlspecialchars($row['student_id']) . '</small></td><td>' . htmlspecialchars($row['receipt_number'] ?? 'N/A') . '</td><td><strong>' . currency($row['amount_paid']) . '</strong></td><td>' . htmlspecialchars(ucfirst(str_replace('_',' ',$row['payment_method'] ?? ''))) . '</td><td>' . date('d/m/Y', strtotime($row['payment_date'])) . '</td><td>' . bsBadge($row['status']) . '</td></tr>';
            }
        }
    }
} catch (Exception $e) { error_log('recent txn: ' . $e->getMessage()); }
echo $txnRows ?: '<tr><td colspan="7" class="text-center text-muted py-4">No recent transactions.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php endif; ?><!-- /home -->

    <!-- ======================== record_payment ======================== -->
    <?php if ($view === 'record_payment'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-hand-holding-usd me-2"></i>Record Payment</div>
        <div class="cb">
            <div class="row g-4">
                <div class="col-md-5">
                    <form id="paymentSearchForm" onsubmit="event.preventDefault(); searchStudentForPayment()">
                        <label class="fl">Search Student</label>
                        <div class="input-group">
                            <input type="text" id="payStudentQuery" class="form-control" placeholder="Name or Index Number..." autocomplete="off">
                            <button class="btn bb" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                    <div id="payStudentResults" class="mt-3"></div>
                    <div id="payStudentInfo" class="mt-3 p-3 bg-light rounded d-none">
                        <h6 class="fw-bold" id="payStudentName"></h6>
                        <p class="mb-1 text-muted small" id="payStudentId"></p>
                        <p class="mb-1 small"><strong>Program:</strong> <span id="payStudentProgram"></span></p>
                        <p class="mb-0 small"><strong>Balance:</strong> <span id="payStudentBalance" class="text-danger fw-bold"></span></p>
                        <input type="hidden" id="paySelectedStudentId">
                        <input type="hidden" id="paySelectedFeeAccountId">
                    </div>
                </div>
                <div class="col-md-7">
                    <form method="POST" action="bursar.php?view=record_payment">
                        <input type="hidden" name="action" value="record_payment">
                        <input type="hidden" name="student_id" id="formStudentId">
                        <input type="hidden" name="fee_account_id" id="formFeeAccountId" value="0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fl">Amount (UGX) *</label>
                                <input type="number" name="amount" class="form-control fc" required min="1" step="100">
                            </div>
                            <div class="col-md-6">
                                <label class="fl">Payment Method *</label>
                                <select name="payment_method" class="form-select fs" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fl">Reference</label>
                                <input type="text" name="reference" class="form-control fc" placeholder="Transaction ref...">
                            </div>
                            <div class="col-md-6">
                                <label class="fl">Date</label>
                                <input type="date" name="payment_date" class="form-control fc" value="<?= date('Y-m-d') ?>">
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
    <?php endif; ?>

    <!-- ======================== generate_invoice ======================== -->
    <?php if ($view === 'generate_invoice'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-file-invoice me-2"></i>Generate Invoice</div>
        <div class="cb">
            <form method="POST" action="bursar.php?view=generate_invoice">
                <input type="hidden" name="action" value="generate_invoice">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fl">Student *</label>
                        <select name="student_id" class="form-select fs" required>
                            <option value="">-- Select Student --</option>
<?php
try {
    if ($studentsDb) {
        $sl = $studentsDb->query("SELECT student_id, first_name, surname FROM students WHERE status='Active' ORDER BY surname");
        if ($sl) while ($s = $sl->fetch_assoc()) echo '<option value="' . htmlspecialchars($s['student_id']) . '">' . htmlspecialchars($s['surname'] . ', ' . $s['first_name'] . ' (' . $s['student_id'] . ')') . '</option>';
    }
} catch (Exception $e) { error_log('student list: ' . $e->getMessage()); }
?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fl">Academic Year</label>
                        <input type="text" name="academic_year" class="form-control fc" value="<?= date('Y') . '/' . (date('Y') + 1) ?>">
                    </div>
                    <div class="col-md-4"><label class="fl">Tuition</label><input type="number" class="form-control fc fee-item" value="0" readonly></div>
                    <div class="col-md-4"><label class="fl">Accommodation</label><input type="number" class="form-control fc fee-item" value="0" readonly></div>
                    <div class="col-md-4"><label class="fl">Clinical</label><input type="number" class="form-control fc fee-item" value="0" readonly></div>
                    <div class="col-md-4"><label class="fl">Examination</label><input type="number" class="form-control fc fee-item" value="0" readonly></div>
                    <div class="col-md-4"><label class="fl">Library</label><input type="number" class="form-control fc fee-item" value="0" readonly></div>
                    <div class="col-md-4"><label class="fl">Activity</label><input type="number" class="form-control fc fee-item" value="0" readonly></div>
                    <div class="col-md-6">
                        <label class="fl">Total Amount (UGX) *</label>
                        <input type="number" name="total_amount" id="invTotal" class="form-control fc fw-bold" required min="1" step="100" style="font-size:18px">
                    </div>
                    <div class="col-md-6">
                        <label class="fl">Due Date</label>
                        <input type="date" name="due_date" class="form-control fc" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn bb"><i class="fas fa-file-invoice me-1"></i>Generate Invoice</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== fee_structure ======================== -->
    <?php if ($view === 'fee_structure'): ?>
    <div class="row g-4">
        <div class="col-md-5">
            <div class="cc"><div class="ch"><i class="fas fa-plus-circle me-2"></i>Add Fee Item</div>
            <div class="cb">
                <form method="POST">
                    <input type="hidden" name="action" value="add_fee_item">
                    <div class="row g-3">
                        <div class="col-12"><label class="fl">Item Name *</label><input type="text" name="item_name" class="form-control fc" required></div>
                        <div class="col-6"><label class="fl">Amount *</label><input type="number" name="item_amount" class="form-control fc" required min="1"></div>
                        <div class="col-6">
                            <label class="fl">Program</label>
                            <select name="program" class="form-select fs"><option value="">All</option><option>Certificate Midwifery</option><option>Diploma Midwifery</option><option>Diploma Nursing Extension</option><option>Certificate Nursing</option></select>
                        </div>
                        <div class="col-12"><label class="fl">Year / Level</label><input type="text" name="year" class="form-control fc" placeholder="e.g. Year 1"></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Add Item</button></div>
                    </div>
                </form>
            </div></div>
        </div>
        <div class="col-md-7">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Current Fee Structures</div>
            <div class="cb p-0">
                <div class="table-responsive">
                    <table class="table tb">
                        <thead><tr><th>Item</th><th>Amount</th><th>Program</th><th>Year</th><th></th></tr></thead>
                        <tbody>
<?php
$feeRows = '';
try {
    if ($staffDb) {
        $fs = $staffDb->query("SELECT * FROM fee_structures ORDER BY item_name");
        if ($fs && $fs->num_rows > 0) {
            while ($f = $fs->fetch_assoc()) {
                $feeRows .= '<tr><td>' . htmlspecialchars($f['item_name']) . '</td><td>' . currency($f['amount']) . '</td><td>' . htmlspecialchars($f['program'] ?? 'All') . '</td><td>' . htmlspecialchars($f['year_level'] ?? '-') . '</td>
                <td><form method="POST" onsubmit="return confirm(\'Delete?\')" style="display:inline"><input type="hidden" name="action" value="delete_fee_item"><input type="hidden" name="item_id" value="' . $f['id'] . '"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td></tr>';
            }
        }
    }
} catch (Exception $e) { error_log('fee structure: ' . $e->getMessage()); }
echo $feeRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No fee items defined.</td></tr>';
?>
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== student_statement ======================== -->
    <?php if ($view === 'student_statement'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-file-alt me-2"></i>Student Statement</div>
        <div class="cb">
            <form id="stmtSearchForm" onsubmit="event.preventDefault(); searchStatementStudent()" class="row g-2 mb-4">
                <div class="col-md-6"><div class="input-group"><input type="text" id="stmtQuery" class="form-control" placeholder="Search by name or index number..."><button class="btn bb" type="submit"><i class="fas fa-search"></i></button></div></div>
            </form>
            <div id="stmtSearchResults" class="mb-3"></div>
            <div id="stmtOutput"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== receipt_print ======================== -->
    <?php if ($view === 'receipt_print'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-receipt me-2"></i>Receipt Print</div>
        <div class="cb">
            <form id="receiptSearchForm" onsubmit="event.preventDefault(); searchReceipt()" class="row g-2 mb-4">
                <div class="col-md-5"><input type="text" id="receiptQuery" class="form-control" placeholder="Receipt number or student name..."></div>
                <div class="col-md-2"><button class="btn bb w-100" type="submit"><i class="fas fa-search"></i></button></div>
            </form>
            <div id="receiptSearchResults" class="mb-3"></div>
            <div id="receiptOutput" class="pr d-none"></div>
            <div class="text-center mt-3 no-print" id="receiptPrintBtnWrap" style="display:none">
                <button class="btn bb" onclick="window.print()"><i class="fas fa-print me-1"></i>Print Receipt</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== financial_reports ======================== -->
    <?php if ($view === 'financial_reports'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-chart-bar me-2"></i>Financial Reports</div>
        <div class="cb">
            <form id="reportForm" onsubmit="event.preventDefault(); generateReport()" class="row g-3 mb-4">
                <div class="col-md-3"><label class="fl">From</label><input type="date" id="rptFrom" class="form-control fc" value="<?= date('Y-m-01') ?>"></div>
                <div class="col-md-3"><label class="fl">To</label><input type="date" id="rptTo" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-4">
                    <label class="fl">Report Type</label>
                    <select id="rptType" class="form-select fs">
                        <option value="daily_collections">Daily Collections</option>
                        <option value="monthly_summary">Monthly Summary</option>
                        <option value="revenue_by_category">Revenue by Category</option>
                        <option value="outstanding_debtors">Outstanding Debtors</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn bb w-100"><i class="fas fa-search me-1"></i>Generate</button></div>
            </form>
            <div class="d-flex gap-2 mb-3 no-print" id="rptActions" style="display:none">
                <button class="btn bo btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
                <button class="btn bo btn-sm" onclick="exportReportExcel()"><i class="fas fa-file-excel me-1"></i>Export Excel</button>
            </div>
            <div id="rptOutput"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== budget ======================== -->
    <?php if ($view === 'budget'): ?>
    <div class="row g-4">
        <div class="col-md-5">
            <div class="cc"><div class="ch"><i class="fas fa-plus-circle me-2"></i>Add Budget</div>
            <div class="cb">
                <form method="POST">
                    <input type="hidden" name="action" value="add_budget">
                    <div class="row g-3">
                        <div class="col-12"><label class="fl">Budget Name *</label><input type="text" name="budget_name" class="form-control fc" required placeholder="e.g. Academic Year 2025/26"></div>
                        <div class="col-6"><label class="fl">Fiscal Year</label><input type="text" name="fiscal_year" class="form-control fc" value="<?= date('Y') ?>"></div>
                        <div class="col-6"><label class="fl">Department</label><input type="text" name="department" class="form-control fc" placeholder="Finance"></div>
                        <div class="col-12"><label class="fl">Total Budget (UGX) *</label><input type="number" name="total_budget" class="form-control fc" required min="1"></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Save Budget</button></div>
                    </div>
                </form>
            </div></div>
        </div>
        <div class="col-md-7">
            <div class="cc"><div class="ch"><i class="fas fa-wallet me-2"></i>Budget vs Actual</div>
            <div class="cb p-0">
                <div class="table-responsive">
                    <table class="table tb">
                        <thead><tr><th>Name</th><th>Fiscal Year</th><th>Dept</th><th>Budget</th><th>Spent</th><th>Remaining</th><th></th></tr></thead>
                        <tbody>
<?php
$budgetRows = '';
try {
    if ($staffDb) {
        $bg = $staffDb->query("SELECT b.*, COALESCE(SUM(fp.amount_paid),0) AS actual_spent FROM budgets b LEFT JOIN fee_payments fp ON fp.payment_date LIKE CONCAT(b.fiscal_year,'%') GROUP BY b.id ORDER BY b.created_at DESC");
        if ($bg && $bg->num_rows > 0) {
            while ($b = $bg->fetch_assoc()) {
                $rem = $b['total_budget'] - $b['actual_spent'];
                $pct = $b['total_budget'] > 0 ? round(($b['actual_spent']/$b['total_budget'])*100) : 0;
                $budgetRows .= '<tr><td>' . htmlspecialchars($b['name']) . '</td><td>' . htmlspecialchars($b['fiscal_year']) . '</td><td>' . htmlspecialchars($b['department'] ?? '-') . '</td><td>' . currency($b['total_budget']) . '</td><td>' . currency($b['actual_spent']) . '</td><td>' . currency($rem) . ' <small class="text-muted">(' . $pct . '%)</small></td>
                <td><form method="POST" onsubmit="return confirm(\'Delete?\')" style="display:inline"><input type="hidden" name="action" value="delete_budget"><input type="hidden" name="budget_id" value="' . $b['id'] . '"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td></tr>';
            }
        }
    }
} catch (Exception $e) { error_log('budget: ' . $e->getMessage()); }
echo $budgetRows ?: '<tr><td colspan="7" class="text-center text-muted py-3">No budgets defined.</td></tr>';
?>
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== daily_collections ======================== -->
    <?php if ($view === 'daily_collections'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-list-ol me-2"></i>Daily Collections</div>
        <div class="cb">
            <form id="dailyForm" onsubmit="event.preventDefault(); loadDailyCollections()" class="row g-2 mb-4">
                <div class="col-md-3"><input type="date" id="dailyDate" class="form-control fc" value="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-2"><button type="submit" class="btn bb w-100"><i class="fas fa-search"></i> Load</button></div>
            </form>
            <div id="dailyOutput"></div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /ma -->

<?php
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show attoast"><i class="fas fa-check-circle me-1"></i> ' . htmlspecialchars($_SESSION['success']) . ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show attoast"><i class="fas fa-exclamation-triangle me-1"></i> ' . htmlspecialchars($_SESSION['error']) . ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['error']);
}
?>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<script>
(function(){
    var el = document.querySelector('#liveClock span');
    if(el){ function t(){ el.textContent = new Date().toLocaleString('en-UG',{weekday:'short',year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}); } t(); setInterval(t,1000); }
})();

// student search
function searchStudentForPayment(){
    var q = document.getElementById('payStudentQuery').value.trim();
    if(!q) return;
    fetch('../includes/ajax_student_search.php?q='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(d){
        var el = document.getElementById('payStudentResults'), info = document.getElementById('payStudentInfo');
        el.innerHTML = ''; info.classList.add('d-none');
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small">No students found.</div>'; return; }
        d.forEach(function(s){
            var di = document.createElement('div');
            di.className = 'sri';
            di.innerHTML = '<strong>'+s.surname+', '+s.first_name+'</strong><br><small class="text-muted">'+s.student_id+' | '+(s.program||'')+'</small>';
            di.addEventListener('click',function(){ selectPaymentStudent(s); });
            el.appendChild(di);
        });
    }).catch(function(){ document.getElementById('payStudentResults').innerHTML = '<div class="text-danger small">Search failed.</div>'; });
}
function selectPaymentStudent(s){
    document.getElementById('paySelectedStudentId').value = s.student_id;
    document.getElementById('payStudentName').textContent = s.surname+', '+s.first_name;
    document.getElementById('payStudentId').textContent = s.student_id;
    document.getElementById('payStudentProgram').textContent = s.program||'N/A';
    document.getElementById('formStudentId').value = s.student_id;
    var info = document.getElementById('payStudentInfo');
    info.classList.remove('d-none');
    document.querySelectorAll('#payStudentResults .sri').forEach(function(i){ i.classList.remove('active'); });
    fetch('bursar.php?view=record_payment&ajax=1&sid='+encodeURIComponent(s.student_id))
    .then(function(r){ return r.json(); })
    .then(function(d){
        document.getElementById('payStudentBalance').textContent = 'UGX '+Number(d.balance||0).toLocaleString();
        document.getElementById('paySelectedFeeAccountId').value = d.fee_account_id||0;
        document.getElementById('formFeeAccountId').value = d.fee_account_id||0;
    }).catch(function(){ document.getElementById('payStudentBalance').textContent = 'N/A'; });
}

// statement
function searchStatementStudent(){
    var q = document.getElementById('stmtQuery').value.trim();
    if(!q) return;
    fetch('../includes/ajax_student_search.php?q='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(d){
        var el = document.getElementById('stmtSearchResults'); el.innerHTML = '';
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small">No students found.</div>'; return; }
        d.forEach(function(s){
            var di = document.createElement('div');
            di.className = 'sri';
            di.innerHTML = '<strong>'+s.surname+', '+s.first_name+'</strong><br><small class="text-muted">'+s.student_id+'</small>';
            di.addEventListener('click',function(){ loadStatement(s); });
            el.appendChild(di);
        });
    }).catch(function(){ document.getElementById('stmtSearchResults').innerHTML = '<div class="text-danger small">Search failed.</div>'; });
}
function loadStatement(s){
    document.getElementById('stmtSearchResults').innerHTML = '';
    var out = document.getElementById('stmtOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar.php?view=student_statement&ajax=1&sid='+encodeURIComponent(s.student_id))
    .then(function(r){ return r.json(); })
    .then(function(d){
        var h = '<div class="mb-3 d-flex justify-content-between align-items-center no-print"><h5 class="fw-bold mb-0">Statement: '+s.surname+', '+s.first_name+' ('+s.student_id+')</h5><button class="btn bo btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button></div>';
        h += '<div class="table-responsive"><table class="table tb"><thead><tr><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead><tbody>';
        var bal = 0;
        h += '<tr class="table-secondary"><td colspan="4"><strong>Opening Balance</strong></td><td><strong>0</strong></td></tr>';
        (d.transactions||[]).forEach(function(tx){
            var debit = tx.type==='invoice'?Number(tx.amount||0):0;
            var credit = tx.type==='payment'?Number(tx.amount||0):0;
            bal = bal + debit - credit;
            h += '<tr><td>'+(tx.date||'')+'</td><td>'+(tx.description||'')+'</td><td>'+(debit?Number(debit).toLocaleString():'-')+'</td><td>'+(credit?Number(credit).toLocaleString():'-')+'</td><td><strong>'+Number(bal).toLocaleString()+'</strong></td></tr>';
        });
        h += '<tr class="table-light fw-bold"><td colspan="4">Closing Balance</td><td>'+Number(bal).toLocaleString()+'</td></tr>';
        h += '</tbody></table></div>';
        out.innerHTML = h;
    }).catch(function(){ out.innerHTML = '<div class="text-danger">Failed to load statement.</div>'; });
}

// receipt
function searchReceipt(){
    var q = document.getElementById('receiptQuery').value.trim();
    if(!q) return;
    var out = document.getElementById('receiptOutput'), bw = document.getElementById('receiptPrintBtnWrap');
    out.classList.add('d-none'); bw.style.display='none';
    fetch('bursar.php?view=receipt_print&ajax=1&q='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.found){ out.innerHTML = '<div class="alert alert-warning mt-3">No receipt found.</div>'; out.classList.remove('d-none'); return; }
        var h = '<div class="rh"><h3>IGANGA SCHOOL OF NURSING & MIDWIFERY</h3><p>P.O. Box 123, Iganga, Uganda</p><h4 class="mt-2">OFFICIAL RECEIPT</h4></div>';
        h += '<div class="rb">';
        h += '<div class="rl"><strong>Receipt No:</strong><span>'+esc(d.receipt_number||'')+'</span></div>';
        h += '<div class="rl"><strong>Date:</strong><span>'+(d.payment_date||'')+'</span></div>';
        h += '<div class="rl"><strong>Student:</strong><span>'+esc(d.student_name||'')+'</span></div>';
        h += '<div class="rl"><strong>Student ID:</strong><span>'+esc(d.student_id||'')+'</span></div>';
        h += '<div class="rl"><strong>Amount:</strong><span class="fw-bold">UGX '+Number(d.amount||0).toLocaleString()+'</span></div>';
        h += '<div class="rl"><strong>Method:</strong><span>'+esc(d.method||'')+'</span></div>';
        if(d.reference) h += '<div class="rl"><strong>Reference:</strong><span>'+esc(d.reference)+'</span></div>';
        h += '<div class="rl"><strong>Balance:</strong><span>UGX '+Number(d.balance||0).toLocaleString()+'</span></div>';
        h += '<div class="rl"><strong>Status:</strong><span>'+esc(d.status||'')+'</span></div></div>';
        h += '<div class="rtot mt-3">UGX '+Number(d.amount||0).toLocaleString()+'</div>';
        h += '<p class="text-center text-muted mt-3" style="font-size:11px">Thank you for your payment</p>';
        out.innerHTML = h; out.classList.remove('d-none'); bw.style.display='block';
    }).catch(function(){ out.innerHTML = '<div class="alert alert-danger mt-3">Failed.</div>'; out.classList.remove('d-none'); });
}

// reports
function generateReport(){
    var f = document.getElementById('rptFrom').value, t = document.getElementById('rptTo').value, tp = document.getElementById('rptType').value;
    var out = document.getElementById('rptOutput'), acts = document.getElementById('rptActions');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'; acts.style.display='none';
    fetch('bursar.php?view=financial_reports&ajax=1&from='+f+'&to='+t+'&type='+tp)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.rows||!d.rows.length){ out.innerHTML = '<div class="text-muted text-center py-4">No data found.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb" id="rptTable"><thead><tr>';
        (d.headers||[]).forEach(function(hd){ h += '<th>'+hd+'</th>'; });
        h += '</tr></thead><tbody>';
        d.rows.forEach(function(r){ h += '<tr>'; r.forEach(function(c){ h += '<td>'+c+'</td>'; }); h += '</tr>'; });
        if(d.total!==undefined) h += '<tr class="fw-bold table-light"><td colspan="'+(d.headers.length-1)+'" class="text-end">Total</td><td>'+Number(d.total).toLocaleString()+'</td></tr>';
        h += '</tbody></table></div>'; out.innerHTML = h; acts.style.display='flex';
    }).catch(function(){ out.innerHTML = '<div class="alert alert-danger">Failed.</div>'; });
}
function exportReportExcel(){
    var tbl = document.getElementById('rptTable');
    if(!tbl) return;
    var html = '<html><head><meta charset="UTF-8"><title>Financial Report</title></head><body>'+tbl.outerHTML+'</body></html>';
    var blob = new Blob([html], {type:'application/vnd.ms-excel'});
    var a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = 'financial_report_'+new Date().toISOString().slice(0,10)+'.xls'; a.click();
}
function loadDailyCollections(){
    var date = document.getElementById('dailyDate').value;
    if(!date) return;
    var out = document.getElementById('dailyOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('bursar.php?view=daily_collections&ajax=1&date='+date)
    .then(function(r){ return r.json(); })
    .then(function(d){
        var h = '<div class="row g-3 mb-3"><div class="col-md-3"><div class="sc" style="border-left-color:#16a34a"><div class="sv h4">UGX '+Number(d.total||0).toLocaleString()+'</div><div class="sl">Total for '+date+'</div></div></div>';
        h += '<div class="col-md-3"><div class="sc" style="border-left-color:#0891b2"><div class="sv h4">'+(d.count||0)+'</div><div class="sl">Transactions</div></div></div></div>';
        if(d.methods&&Object.keys(d.methods).length){
            h += '<h6 class="fw-bold mb-2">Payment Methods</h6><div class="row g-2 mb-3">';
            for(var m in d.methods) h += '<div class="col-md-3"><div class="p-2 bg-light rounded small"><strong>'+m+':</strong> UGX '+Number(d.methods[m]).toLocaleString()+'</div></div>';
            h += '</div>';
        }
        if(d.payments&&d.payments.length){
            h += '<div class="table-responsive"><table class="table tb"><thead><tr><th>#</th><th>Student</th><th>Receipt</th><th>Amount</th><th>Method</th></tr></thead><tbody>';
            d.payments.forEach(function(p,i){ h += '<tr><td>'+(i+1)+'</td><td>'+esc(p.student_name||p.student_id||'')+'</td><td>'+esc(p.receipt_number||'')+'</td><td>UGX '+Number(p.amount||0).toLocaleString()+'</td><td>'+esc(p.method||'')+'</td></tr>'; });
            h += '</tbody></table></div>';
        } else { h += '<div class="text-muted text-center py-3">No payments on this date.</div>'; }
        out.innerHTML = h;
    }).catch(function(){ out.innerHTML = '<div class="alert alert-danger">Failed.</div>'; });
}

// invoice fee items auto-populate
document.addEventListener('DOMContentLoaded', function(){
    var items = document.querySelectorAll('.fee-item'), total = document.getElementById('invTotal');
    if(items.length&&total){
        function calc(){ var s=0; items.forEach(function(el){ s+=parseFloat(el.value)||0; }); total.value=s||''; }
        items.forEach(function(el){ el.addEventListener('input',calc); });
    }
    var sel = document.querySelector('select[name="student_id"]');
    if(sel){
        sel.addEventListener('change',function(){
            var sid = this.value; if(!sid) return;
            fetch('bursar.php?view=generate_invoice&ajax=1&sid='+encodeURIComponent(sid))
            .then(function(r){ return r.json(); })
            .then(function(fees){
                if(fees&&fees.length){
                    var fields = ['Tuition','Accommodation','Clinical','Examination','Library','Activity'];
                    items.forEach(function(el,idx){
                        var label = fields[idx]||'', matched = fees.find(function(f){ return f.item_name===label; });
                        if(matched) el.value = parseFloat(matched.amount)||0;
                    });
                    var s=0; items.forEach(function(el){ s+=parseFloat(el.value)||0; }); total.value=s||'';
                }
            }).catch(function(){});
        });
    }
});
function esc(s){ if(!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
</script>
</body>
</html>
