<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/financial_functions.php';
require_once __DIR__ . '/../includes/auto_deduction_processor.php';

$ctx = bootstrapStaffDashboard(['school bursar', 'bursar', 'accountant', 'finance']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? '';
$role = $user['role_name'] ?? $user['role'] ?? 'School Bursar';
$staff = $ctx['staff'];
$students = $ctx['students'];
$website = $ctx['website'];

$_GET['section'] = $_GET['section'] ?? $_GET['view'] ?? 'overview';
$view  = $_GET['section'];
if ($view === 'overview') $view = 'home';
$ajax  = $_GET['ajax'] ?? '';
$sid   = $_GET['sid'] ?? '';
$q     = $_GET['q'] ?? '';

// Handle student parameter from student-management.php link
$studentParam = $_GET['student'] ?? '';
if ($studentParam !== '' && $view === 'home') {
    $view = 'student_statement';
    $q = $studentParam;
}

// ── Clearance AJAX ─────────────────────────────────────────────────
if ($view === 'clearance' && $ajax === '1' && $sid) {
    header('Content-Type: application/json');
    $status = 'Pending Review';
    try {
        if ($staff) {
            $stmt = $staff->prepare("SELECT clearance_status FROM igangaschoolofl_students_db.financial_clearance WHERE student_id = ? ORDER BY updated_at DESC LIMIT 1");
            if ($stmt) { $stmt->bind_param("s", $sid); $stmt->execute(); $res = $stmt->get_result(); if ($r = $res->fetch_assoc()) $status = $r['clearance_status']; $stmt->close(); }
        }
    } catch (Exception $e) {}
    echo json_encode(['status' => $status]);
    exit;
}
if ($view === 'clearance' && ($_POST['ajax_clearance'] ?? '') === '1') {
    header('Content-Type: application/json');
    try {
        if ($staff) {
            $stuId = $_POST['student_id'] ?? '';
            $cStatus = $_POST['clearance_status'] ?? 'Pending Review';
            $remarks = $_POST['remarks'] ?? '';
            $year = date('Y');
            if ($stuId) {
                $stmt = $staff->prepare("INSERT INTO igangaschoolofl_students_db.financial_clearance (student_id, academic_year, semester, clearance_status, cleared_by, cleared_at, remarks) VALUES (?, ?, 'Annual', ?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE clearance_status=VALUES(clearance_status), cleared_by=VALUES(cleared_by), cleared_at=NOW(), remarks=VALUES(remarks)");
                if ($stmt) {
                    $stmt->bind_param("sssis", $stuId, $year, $cStatus, $user['id'], $remarks);
                    $stmt->execute();
                    $stmt->close();
                    echo json_encode(['success' => 'Clearance updated to: ' . $cStatus]);
                    exit;
                }
            }
        }
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); exit; }
    echo json_encode(['error' => 'Failed']);
    exit;
}

// ── AJAX endpoints (must exit before any HTML output) ────────────

// record_payment - get student fee balance (staffs_db)
if ($view === 'record_payment' && $ajax === '1' && $sid) {
    header('Content-Type: application/json');
    $balance = 0;
    $fee_account_id = 0;
    try {
        if ($staff) {
            $stmt = $staff->prepare("SELECT id, balance FROM student_fee_accounts WHERE student_id = ? AND status NOT IN ('fully_paid','cancelled') ORDER BY id DESC LIMIT 1");
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

// student_statement - load full statement (staffs_db)
if ($view === 'student_statement' && $ajax === '1' && $sid) {
    header('Content-Type: application/json');
    $txns = [];
    try {
        if ($staff) {
            $stmt = $staff->prepare("SELECT payment_date AS date, amount_paid AS amount, receipt_number, payment_method FROM fee_payments WHERE student_id = ? AND status='verified' ORDER BY payment_date ASC");
            if ($stmt) {
                $stmt->bind_param("s", $sid);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $txns[] = ['date' => $row['date'], 'description' => 'Payment - ' . ($row['receipt_number'] ?? '') . ' (' . ($row['payment_method'] ?? '') . ')', 'type' => 'payment', 'amount' => (float)$row['amount']];
                }
                $stmt->close();
            }
            $stmt = $staff->prepare("SELECT created_at AS date, total_fees AS amount, invoice_number FROM student_fee_accounts WHERE student_id = ? ORDER BY created_at ASC");
            if ($stmt) {
                $stmt->bind_param("s", $sid);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $txns[] = ['date' => $row['date'], 'description' => 'Invoice - ' . ($row['invoice_number'] ?? ''), 'type' => 'invoice', 'amount' => (float)$row['amount']];
                }
                $stmt->close();
            }
            if (function_exists('generateFinancialStatement')) {
                $fs = generateFinancialStatement($sid);
                if (!empty($fs['payments'])) {
                    foreach ($fs['payments'] as $p) {
                        $txns[] = ['date' => $p['payment_date'], 'description' => 'Payment - ' . ($p['payment_reference'] ?? '') . ' (' . ($p['payment_method'] ?? '') . ')', 'type' => 'payment', 'amount' => (float)$p['amount']];
                    }
                }
                if (!empty($fs['invoices'])) {
                    foreach ($fs['invoices'] as $inv) {
                        $txns[] = ['date' => $inv['created_at'] ?? $inv['due_date'] ?? '', 'description' => 'Invoice - ' . ($inv['invoice_number'] ?? ''), 'type' => 'invoice', 'amount' => (float)$inv['total_amount']];
                    }
                }
            }
            usort($txns, function($a, $b) { return strcmp($a['date'], $b['date']); });
        }
    } catch (Exception $e) { error_log('ajax stmt: ' . $e->getMessage()); }
    echo json_encode(['opening_balance' => 0, 'transactions' => $txns]);
    exit;
}

// receipt_print - search by receipt number or student (staffs_db)
if ($view === 'receipt_print' && $ajax === '1' && $q) {
    header('Content-Type: application/json');
    $data = ['found' => false];
    try {
        if ($staff && $students) {
            $like = "%$q%";
            $stmt = $staff->prepare("SELECT fp.payment_id, fp.student_id, fp.amount_paid, fp.payment_method, fp.payment_reference AS ref, fp.receipt_number, fp.payment_date, fp.status, s.first_name, s.last_name FROM fee_payments fp LEFT JOIN igangaschoolofl_students_db.students s ON fp.student_id = s.student_id WHERE fp.receipt_number LIKE ? OR fp.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? ORDER BY fp.payment_date DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ssss", $like, $like, $like, $like);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $bal = 0;
                    $bStmt = $staff->prepare("SELECT balance FROM student_fee_accounts WHERE student_id = ? AND status NOT IN ('fully_paid','cancelled') ORDER BY id DESC LIMIT 1");
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
                        'student_name' => ($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? ''),
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

// financial_reports (staffs_db)
if ($view === 'financial_reports' && $ajax === '1') {
    header('Content-Type: application/json');
    $from   = $_GET['from'] ?? date('Y-m-01');
    $to     = $_GET['to'] ?? date('Y-m-d');
    $type   = $_GET['type'] ?? 'daily_collections';
    $result = ['headers' => [], 'rows' => [], 'total' => 0];
    try {
        if ($staff) {
            $fromSafe = $staff->real_escape_string($from);
            $toSafe = $staff->real_escape_string($to);
            if ($type === 'daily_collections') {
                $result['headers'] = ['Date', 'Transactions', 'Total Collected'];
                $r = $staff->query("SELECT DATE(payment_date) AS dt, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$fromSafe' AND '$toSafe' AND status='verified' GROUP BY DATE(payment_date) ORDER BY dt");
                if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['dt'], $row['cnt'], 'UGX ' . number_format($row['tot'])]; $result['total'] += $row['tot']; }
            } elseif ($type === 'monthly_summary') {
                $result['headers'] = ['Month', 'Payments', 'Total'];
                $r = $staff->query("SELECT DATE_FORMAT(payment_date,'%Y-%m') AS m, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$fromSafe' AND '$toSafe' AND status='verified' GROUP BY DATE_FORMAT(payment_date,'%Y-%m') ORDER BY m");
                if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['m'], $row['cnt'], 'UGX ' . number_format($row['tot'])]; $result['total'] += $row['tot']; }
            } elseif ($type === 'revenue_by_category') {
                $result['headers'] = ['Payment Method', 'Transactions', 'Total'];
                $r = $staff->query("SELECT payment_method, COUNT(*) AS cnt, COALESCE(SUM(amount_paid),0) AS tot FROM fee_payments WHERE DATE(payment_date) BETWEEN '$fromSafe' AND '$toSafe' AND status='verified' GROUP BY payment_method");
                if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [ucfirst(str_replace('_', ' ', $row['payment_method'] ?? 'Unknown')), $row['cnt'], 'UGX ' . number_format($row['tot'])]; $result['total'] += $row['tot']; }
            } elseif ($type === 'outstanding_debtors') {
                $result['headers'] = ['Student ID', 'Total Fees', 'Paid', 'Balance'];
                $r = $staff->query("SELECT student_id, total_fees, amount_paid, balance FROM student_fee_accounts WHERE status NOT IN ('fully_paid','cancelled') ORDER BY balance DESC LIMIT 50");
                if ($r) while ($row = $r->fetch_assoc()) { $result['rows'][] = [$row['student_id'], 'UGX ' . number_format($row['total_fees']), 'UGX ' . number_format($row['amount_paid']), 'UGX ' . number_format($row['balance'])]; $result['total'] += $row['balance']; }
            }
        }
    } catch (Exception $e) { error_log('ajax report: ' . $e->getMessage()); }
    echo json_encode($result);
    exit;
}

// daily_collections (staffs_db)
if ($view === 'daily_collections' && $ajax === '1') {
    header('Content-Type: application/json');
    $date = $_GET['date'] ?? date('Y-m-d');
    $data = ['total' => 0, 'count' => 0, 'methods' => [], 'payments' => []];
    try {
        if ($staff) {
            $dateSafe = $staff->real_escape_string($date);
            $r = $staff->query("SELECT fp.*, s.first_name, s.last_name FROM fee_payments fp LEFT JOIN igangaschoolofl_students_db.students s ON fp.student_id = s.student_id WHERE DATE(fp.payment_date) = '$dateSafe' AND fp.status='verified' ORDER BY fp.payment_date DESC");
            if ($r) {
                $data['count'] = $r->num_rows;
                while ($row = $r->fetch_assoc()) {
                    $data['total'] += (float)$row['amount_paid'];
                    $m = ucfirst(str_replace('_', ' ', $row['payment_method'] ?? 'Unknown'));
                    $data['methods'][$m] = ($data['methods'][$m] ?? 0) + (float)$row['amount_paid'];
                    $data['payments'][] = ['student_name' => ($row['last_name'] ?? '') . ' ' . ($row['first_name'] ?? ''), 'student_id' => $row['student_id'], 'receipt_number' => $row['receipt_number'] ?? '', 'amount' => $row['amount_paid'], 'method' => $m];
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
        if ($students && $staff) {
            $sidSafe = $students->real_escape_string($sid);
            $prog = $students->query("SELECT program FROM students WHERE student_number = '$sidSafe' OR student_id = '$sidSafe' LIMIT 1");
            if ($prog && ($p = $prog->fetch_assoc())) {
                $program = $staff->real_escape_string($p['program']);
                $fs = $staff->query("SELECT item_name, amount FROM fee_structures WHERE program = '' OR program = '$program' ORDER BY id");
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

    if ($action === 'record_payment' && $staff) {
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
                $receipt = function_exists('generateReceiptNumber') ? generateReceiptNumber() : ('RCPT-' . date('Ymd') . '-' . rand(1000,9999));
                $stmt = $staff->prepare("INSERT INTO fee_payments (payment_id, student_id, fee_account_id, amount_paid, payment_method, payment_reference, receipt_number, notes, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified')");
                if ($stmt) {
                    $pid = $receipt;
                    $stmt->bind_param("ssidsssss", $pid, $student_id, $fee_account_id, $amount, $method, $reference, $receipt, $notes, $payment_date);
                    if ($stmt->execute()) {
                        if ($fee_account_id > 0) {
                            $upd = $staff->prepare("UPDATE student_fee_accounts SET amount_paid = amount_paid + ?, balance = balance - ?, last_payment_date = ?, status = CASE WHEN (balance - ?) <= 0 THEN 'fully_paid' WHEN amount_paid + ? > 0 THEN 'partially_paid' ELSE 'unpaid' END WHERE id = ?");
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
                    $_SESSION['error'] = 'Prepare failed: ' . $staff->error;
                }
            }
        } catch (Exception $e) { $_SESSION['error'] = 'Error: ' . $e->getMessage(); }
        header('Location: school-bursar.php?section=record_payment');
        exit;
    }

    if ($action === 'generate_invoice' && $staff) {
        try {
            $student_id    = trim($_POST['student_id'] ?? '');
            $academic_year = trim($_POST['academic_year'] ?? date('Y') . '/' . (date('Y') + 1));
            $total_fees    = (float)($_POST['total_amount'] ?? 0);
            $due_date      = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));
            if ($student_id === '' || $total_fees <= 0) {
                $_SESSION['error'] = 'Student and amount are required.';
            } else {
                $inv_prefix = 'INV-' . date('Y') . '-';
                $invPrefixSafe = $staff->real_escape_string($inv_prefix);
                $cnt = $staff->query("SELECT COUNT(*) AS c FROM student_fee_accounts WHERE invoice_number LIKE '$invPrefixSafe%'");
                $inv_no = $inv_prefix . str_pad(($cnt ? (int)$cnt->fetch_assoc()['c'] + 1 : 1), 5, '0', STR_PAD_LEFT);
                $stmt = $staff->prepare("INSERT INTO student_fee_accounts (student_id, academic_year, invoice_number, total_fees, amount_paid, balance, due_date, status) VALUES (?, ?, ?, ?, 0, ?, ?, 'unpaid')");
                if ($stmt) {
                    $stmt->bind_param("sssdd", $student_id, $academic_year, $inv_no, $total_fees, $total_fees, $due_date);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Invoice $inv_no created for UGX " . number_format($total_fees);
                    } else {
                        $_SESSION['error'] = 'Invoice creation failed: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error'] = 'Prepare failed: ' . $staff->error;
                }
            }
        } catch (Exception $e) { $_SESSION['error'] = 'Error: ' . $e->getMessage(); }
        header('Location: school-bursar.php?section=generate_invoice');
        exit;
    }

    if ($action === 'add_fee_item' && $staff) {
        try {
            $name    = trim($_POST['item_name'] ?? '');
            $amount  = (float)($_POST['item_amount'] ?? 0);
            $program = trim($_POST['program'] ?? '');
            $year    = trim($_POST['year'] ?? '');
            $stmt = $staff->prepare("INSERT INTO fee_structures (item_name, amount, program, year_level) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sdss", $name, $amount, $program, $year);
                $stmt->execute() ? $_SESSION['success'] = 'Fee item added.' : $_SESSION['error'] = $stmt->error;
                $stmt->close();
            }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header('Location: school-bursar.php?section=fee_structure');
        exit;
    }

    if ($action === 'delete_fee_item' && $staff) {
        try {
            $id = (int)($_POST['item_id'] ?? 0);
            $stmt = $staff->prepare("DELETE FROM fee_structures WHERE id = ?");
            if ($stmt) { $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Fee item deleted.';
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header('Location: school-bursar.php?section=fee_structure');
        exit;
    }

    if ($action === 'add_budget' && $staff) {
        try {
            $name  = trim($_POST['budget_name'] ?? '');
            $fy    = trim($_POST['fiscal_year'] ?? date('Y'));
            $dept  = trim($_POST['department'] ?? '');
            $total = (float)($_POST['total_budget'] ?? 0);
            $stmt = $staff->prepare("INSERT INTO budgets (name, fiscal_year, department, total_budget, spent) VALUES (?, ?, ?, ?, 0)");
            if ($stmt) {
                $stmt->bind_param("sssd", $name, $fy, $dept, $total);
                $stmt->execute() ? $_SESSION['success'] = 'Budget added.' : $_SESSION['error'] = $stmt->error;
                $stmt->close();
            }
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header('Location: school-bursar.php?section=budget');
        exit;
    }

    if ($action === 'delete_budget' && $staff) {
        try {
            $id = (int)($_POST['budget_id'] ?? 0);
            $stmt = $staff->prepare("DELETE FROM budgets WHERE id = ?");
            if ($stmt) { $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Budget deleted.';
        } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
        header('Location: school-bursar.php?section=budget');
        exit;
    }
}

// ── Dashboard Stats with 60-second cache (combined databases) ─────

$cache_key = 'bursar_home_stats_' . date('YmdH');
$use_cache = function_exists('getCacheData') && function_exists('setCacheData');
$cached = $use_cache ? getCacheData($cache_key) : null;

if ($cached) {
    $today_collections     = $cached['today_collections'] ?? 0;
    $week_collections      = $cached['week_collections'] ?? 0;
    $outstanding_fees      = $cached['outstanding_fees'] ?? 0;
    $students_cleared      = $cached['students_cleared'] ?? 0;
    $total_students        = $cached['total_students'] ?? 0;
    $students_db_cleared   = $cached['students_db_cleared'] ?? 0;
    $students_db_outstanding = $cached['students_db_outstanding'] ?? 0;
    $not_cleared_students  = $cached['not_cleared_students'] ?? 0;
    $recent_txns           = $cached['recent_txns'] ?? [];
    $recent_payments       = $cached['recent_payments'] ?? [];
} else {
    $today_collections = 0;
    $week_collections  = 0;
    $month_collections = 0;
    $outstanding_fees  = 0;
    $students_cleared  = 0;

    try {
        if ($staff) {
            $r = $staff->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE DATE(payment_date)=CURDATE() AND status='verified'");
            if ($r) $today_collections = (float)$r->fetch_assoc()['t'];
            $r = $staff->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM fee_payments WHERE YEARWEEK(payment_date)=YEARWEEK(CURDATE()) AND status='verified'");
            if ($r) $week_collections = (float)$r->fetch_assoc()['t'];
            $r = $staff->query("SELECT COALESCE(SUM(balance),0) AS t FROM student_fee_accounts WHERE status NOT IN ('fully_paid','cancelled')");
            if ($r) $outstanding_fees = (float)$r->fetch_assoc()['t'];
            $r = $staff->query("SELECT COUNT(*) AS c FROM student_fee_accounts WHERE status='fully_paid'");
            if ($r) $students_cleared = (int)$r->fetch_assoc()['c'];
        }
    } catch (Exception $e) { error_log('bursar stats: ' . $e->getMessage()); }

    // Also get students DB stats
    $total_students = 0;
    $students_db_cleared = 0;
    $students_db_outstanding = 0;
    try {
        if ($students) {
            $s = $students->query("SELECT COUNT(*) as count FROM students WHERE status = 'Active'");
            $total_students = $s ? (int)$s->fetch_assoc()['count'] : 0;
            $s = $students->query("SELECT COUNT(DISTINCT student_id) as count FROM student_invoices WHERE status = 'Paid'");
            $students_db_cleared = $s ? (int)$s->fetch_assoc()['count'] : 0;
            $s = $students->query("SELECT COALESCE(SUM(balance),0) as total FROM student_invoices WHERE status IN ('Pending','Partially Paid','Overdue')");
            $students_db_outstanding = $s ? (float)$s->fetch_assoc()['total'] : 0;
        }
    } catch (Exception $e) { error_log('students stats: ' . $e->getMessage()); }

    $not_cleared_students = max(0, $total_students - $students_db_cleared);

    // ── Recent payments from both databases ──────────────────────────
    $recent_txns = [];
    try {
        if ($staff) {
            $rp = $staff->query("SELECT fp.payment_id, fp.student_id, fp.amount_paid, fp.payment_method, fp.receipt_number, fp.payment_date, fp.status, s.first_name, s.last_name FROM fee_payments fp LEFT JOIN igangaschoolofl_students_db.students s ON fp.student_id = s.student_id ORDER BY fp.payment_date DESC LIMIT 10");
            if ($rp) {
                while ($row = $rp->fetch_assoc()) {
                    $recent_txns[] = $row;
                }
            }
        }
    } catch (Exception $e) { error_log('recent txn staff: ' . $e->getMessage()); }

    $recent_payments = [];
    try {
        if ($staff) {
            $payments_stmt = $staff->query("
                SELECT p.*, s.first_name, s.last_name, s.student_number, s.index_number 
                FROM igangaschoolofl_students_db.payments p 
                JOIN igangaschoolofl_students_db.students s ON p.student_id = s.id 
                ORDER BY p.payment_date DESC 
                LIMIT 10
            ");
            if ($payments_stmt) {
                while ($row = $payments_stmt->fetch_assoc()) {
                    $recent_payments[] = $row;
                }
            }
        }
    } catch (Exception $e) { error_log('recent payments students: ' . $e->getMessage()); }

    // Cache for 1 hour
    if ($use_cache) {
        setCacheData($cache_key, [
            'today_collections'      => $today_collections,
            'week_collections'       => $week_collections,
            'outstanding_fees'       => $outstanding_fees,
            'students_cleared'       => $students_cleared,
            'total_students'         => $total_students,
            'students_db_cleared'    => $students_db_cleared,
            'students_db_outstanding'=> $students_db_outstanding,
            'not_cleared_students'   => $not_cleared_students,
            'recent_txns'            => $recent_txns,
            'recent_payments'        => $recent_payments,
        ], '+1 hour');
    }
}

// ── Helpers ───────────────────────────────────────────────────────

if (!function_exists('bsBadge')) {
    function bsBadge($s) {
        $m = ['verified'=>'success','pending'=>'warning','failed'=>'danger','fully_paid'=>'success','partially_paid'=>'info','unpaid'=>'secondary','cancelled'=>'dark'];
        $c = $m[strtolower($s)] ?? 'secondary';
        return "<span class=\"badge bg-$c\">" . htmlspecialchars($s) . '</span>';
    }
}
if (!function_exists('currency')) {
    function currency($n) { return 'UGX ' . number_format((float)$n, 0); }
}

// ── HTML ──────────────────────────────────────────────────────────
$pageTitle = 'Bursar Dashboard';
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="ma content-section dashboard-section active" data-section="overview" style="margin-left:270px;padding:24px">

    <div class="ph">
        <div>
            <h1><i class="fas fa-calculator me-2"></i>Bursar Dashboard</h1>
            <p>Financial Management &mdash; IGANGA SCHOOL OF NURSING &amp; MIDWIFERY</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:13px"><i class="far fa-clock me-1"></i><span id="currentDate"></span></span>
            <a href="school-bursar.php" class="bo btn-sm <?= $view === 'home' ? 'd-none' : '' ?>"><i class="fas fa-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <?php if ($view === 'home'): ?>

    <!-- ── Stats Cards ─────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6"><div class="sc"><div class="si"><i class="fas fa-money-bill-wave"></i></div><div class="sv"><?= currency($today_collections) ?></div><div class="sl">Today's Collections</div></div></div>
        <div class="col-md-3 col-6"><div class="sc" style="border-left-color:#0891b2"><div class="si" style="background:linear-gradient(135deg,#0891b2,#06b6d4)"><i class="fas fa-calendar-week"></i></div><div class="sv"><?= currency($week_collections) ?></div><div class="sl">This Week</div></div></div>
        <div class="col-md-3 col-6"><div class="sc" style="border-left-color:#d97706"><div class="si" style="background:linear-gradient(135deg,#d97706,#f59e0b)"><i class="fas fa-exclamation-triangle"></i></div><div class="sv"><?= currency($outstanding_fees + $students_db_outstanding) ?></div><div class="sl">Outstanding Fees</div></div></div>
        <div class="col-md-3 col-6"><div class="sc" style="border-left-color:#16a34a"><div class="si" style="background:linear-gradient(135deg,#16a34a,#22c55e)"><i class="fas fa-user-check"></i></div><div class="sv"><?= number_format($students_cleared + $students_db_cleared) ?></div><div class="sl">Students Cleared</div></div></div>
    </div>

    <!-- ── Student Fee Status (students DB) ─────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="sc" style="border-left-color:#1a237e"><div class="si" style="background:linear-gradient(135deg,#1a237e,#3949ab)"><i class="fas fa-users"></i></div><div class="sv"><?= number_format($total_students) ?></div><div class="sl">Total Active Students</div></div></div>
        <div class="col-md-4"><div class="sc" style="border-left-color:#16a34a"><div class="si" style="background:linear-gradient(135deg,#16a34a,#22c55e)"><i class="fas fa-check-circle"></i></div><div class="sv"><?= number_format($students_db_cleared) ?></div><div class="sl">Cleared (Students DB)</div></div></div>
        <div class="col-md-4"><div class="sc" style="border-left-color:#d97706"><div class="si" style="background:linear-gradient(135deg,#d97706,#f59e0b)"><i class="fas fa-exclamation-circle"></i></div><div class="sv"><?= number_format($not_cleared_students) ?></div><div class="sl">Not Cleared</div></div></div>
    </div>

    <!-- ── Action Grid ─────────────────────────────────────────── -->
    <div class="ag mb-4">
        <a href="?section=record_payment" class="ab"><i class="fas fa-hand-holding-usd"></i><span>Record Payment</span></a>
        <a href="?section=generate_invoice" class="ab"><i class="fas fa-file-invoice"></i><span>Generate Invoice</span></a>
        <a href="?section=bulk_billing" class="ab"><i class="fas fa-layer-group"></i><span>Bulk Billing</span></a>
        <a href="?section=fee_structure" class="ab"><i class="fas fa-tags"></i><span>Fee Structure</span></a>
        <a href="?section=fee_adjustments" class="ab"><i class="fas fa-adjust"></i><span>Fee Adjustments</span></a>
        <a href="?section=student_statement" class="ab"><i class="fas fa-file-alt"></i><span>Student Statement</span></a>
        <a href="?section=receipt_print" class="ab"><i class="fas fa-receipt"></i><span>Receipt Print</span></a>
        <a href="?section=payment_verification" class="ab"><i class="fas fa-check-double"></i><span>Verification</span></a>
        <a href="?section=financial_reports" class="ab"><i class="fas fa-chart-bar"></i><span>Financial Reports</span></a>
        <a href="?section=daily_collections" class="ab"><i class="fas fa-list-ol"></i><span>Daily Collections</span></a>
        <a href="?section=debtors_list" class="ab"><i class="fas fa-exclamation-triangle"></i><span>Debtors List</span></a>
        <a href="?section=budget" class="ab"><i class="fas fa-wallet"></i><span>Budget</span></a>
        <a href="?section=expenditure" class="ab"><i class="fas fa-shopping-cart"></i><span>Expenditure</span></a>
        <a href="?section=payroll" class="ab"><i class="fas fa-money-check"></i><span>Payroll</span></a>
        <a href="?section=ledger" class="ab"><i class="fas fa-book-open"></i><span>Ledger</span></a>
        <a href="?section=cashbook" class="ab"><i class="fas fa-cash-register"></i><span>Cashbook</span></a>
        <a href="?section=reconciliation" class="ab"><i class="fas fa-university"></i><span>Reconciliation</span></a>
        <a href="?section=chart_of_accounts" class="ab"><i class="fas fa-book"></i><span>Chart of Accounts</span></a>
        <a href="?section=assets" class="ab"><i class="fas fa-boxes"></i><span>Assets</span></a>
        <a href="?section=tax_reports" class="ab"><i class="fas fa-file-invoice-dollar"></i><span>Tax / URA</span></a>
        <a href="?section=fee_reminders" class="ab"><i class="fas fa-bell"></i><span>Fee Reminders</span></a>
        <a href="?section=clearance" class="ab"><i class="fas fa-check-double"></i><span>Clearance</span></a>
        <a href="?section=payment_approvals" class="ab"><i class="fas fa-thumbs-up"></i><span>Approvals</span></a>
        <a href="?section=refunds" class="ab"><i class="fas fa-undo"></i><span>Refunds</span></a>
        <a href="?section=late_payment" class="ab"><i class="fas fa-clock"></i><span>Late Payment</span></a>
        <a href="?section=audit_trail" class="ab"><i class="fas fa-history"></i><span>Audit Trail</span></a>
        <a href="payment-subscriptions.php" class="ab"><i class="fas fa-sync"></i><span>Auto Deductions</span></a>
    </div>

    <!-- ── Payment Provider Logos ───────────────────────────────── -->
    <div class="cc mb-4">
        <div class="ch"><i class="fas fa-credit-card me-2"></i>Supported Payment Methods</div>
        <div class="cb">
            <div class="d-flex gap-3 align-items-center flex-wrap">
                <?php $providers = function_exists('getPaymentProviders') ? getPaymentProviders() : []; foreach ($providers as $key => $p): ?>
                <div class="text-center">
                    <img src="<?= htmlspecialchars($p['logo']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="payment-logo" style="height:32px;background:#fff;border-radius:6px;padding:4px;object-fit:contain;" onerror="this.src='../images/bank-default.svg'">
                    <div><small><?= htmlspecialchars($p['name']) ?></small></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Recent Transactions (staffs_db) ───────────────────────── -->
    <div class="cc">
        <div class="ch">Recent Transactions (Fee Payments)</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>#</th><th>Student</th><th>Receipt</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$txnCount = 0;
if (!empty($recent_txns)):
    foreach ($recent_txns as $row):
        $txnCount++;
        echo '<tr><td>' . $txnCount . '</td><td>' . htmlspecialchars(($row['last_name'] ?? '') . ' ' . ($row['first_name'] ?? '')) . '<br><small class="text-muted">' . htmlspecialchars($row['student_id']) . '</small></td><td>' . htmlspecialchars($row['receipt_number'] ?? 'N/A') . '</td><td><strong>' . currency($row['amount_paid']) . '</strong></td><td>' . htmlspecialchars(ucfirst(str_replace('_',' ',$row['payment_method'] ?? ''))) . '</td><td>' . date('d/m/Y', strtotime($row['payment_date'])) . '</td><td>' . bsBadge($row['status']) . '</td></tr>';
    endforeach;
else:
    echo '<tr><td colspan="7" class="text-center text-muted py-4">No recent transactions.</td></tr>';
endif;
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Recent Payments (students_db) ────────────────────────── -->
    <div class="cc mt-4">
        <div class="ch">Recent Payments (Student Portal)</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Ref</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
<?php if (!empty($recent_payments)): foreach ($recent_payments as $payment): ?>
<tr>
    <td><?= htmlspecialchars($payment['payment_reference'] ?? 'N/A') ?></td>
    <td><?= htmlspecialchars(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? '')) ?><br><small class="text-muted"><?= htmlspecialchars($payment['index_number'] ?? $payment['student_number'] ?? '') ?></small></td>
    <td><strong><?= currency($payment['amount'] ?? 0) ?></strong></td>
    <td><?php if (!empty($payment['payment_provider'])): ?><img src="<?= function_exists('getPaymentProviderLogo') ? getPaymentProviderLogo($payment['payment_provider']) : '' ?>" alt="" style="height:20px;border-radius:3px;vertical-align:middle"> <?php endif; ?><?= ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? '-')) ?></td>
    <td><?= date('d/m/Y', strtotime($payment['payment_date'] ?? 'now')) ?></td>
    <td><?= bsBadge($payment['status'] ?? 'pending') ?></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="6" class="text-center text-muted py-4">No recent payments from student portal.</td></tr>
<?php endif; ?>
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
                    <form method="POST" action="school-bursar.php?section=record_payment">
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
            <form method="POST" action="school-bursar.php?section=generate_invoice">
                <input type="hidden" name="action" value="generate_invoice">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fl">Student *</label>
                        <select name="student_id" class="form-select fs" required>
                            <option value="">-- Select Student --</option>
<?php
try {
    if ($students) {
        $sl = $students->query("SELECT student_number, first_name, surname FROM students WHERE status='Active' ORDER BY surname");
        if ($sl) while ($s = $sl->fetch_assoc()) echo '<option value="' . htmlspecialchars($s['student_number']) . '">' . htmlspecialchars($s['surname'] . ', ' . $s['first_name'] . ' (' . $s['student_number'] . ')') . '</option>';
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
                <form method="POST" action="school-bursar.php?section=fee_structure">
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
    if ($staff) {
        $fs = $staff->query("SELECT * FROM fee_structures ORDER BY item_name");
        if ($fs && $fs->num_rows > 0) {
            while ($f = $fs->fetch_assoc()) {
                $feeRows .= '<tr><td>' . htmlspecialchars($f['item_name']) . '</td><td>' . currency($f['amount']) . '</td><td>' . htmlspecialchars($f['program'] ?? 'All') . '</td><td>' . htmlspecialchars($f['year_level'] ?? '-') . '</td>
                <td><form method="POST" action="school-bursar.php?section=fee_structure" onsubmit="return confirm(\'Delete?\')" style="display:inline"><input type="hidden" name="action" value="delete_fee_item"><input type="hidden" name="item_id" value="' . $f['id'] . '"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td></tr>';
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
                <div class="col-md-6"><div class="input-group"><input type="text" id="stmtQuery" class="form-control" placeholder="Search by name or index number..." value="<?= htmlspecialchars($q) ?>"><button class="btn bb" type="submit"><i class="fas fa-search"></i></button></div></div>
            </form>
            <div id="stmtSearchResults" class="mb-3"></div>
            <div id="stmtOutput"></div>
        </div>
    </div>
    <?php if ($studentParam !== ''): ?>
    <script>document.addEventListener('DOMContentLoaded',function(){ setTimeout(searchStatementStudent,300); });</script>
    <?php endif; ?>
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
                <form method="POST" action="school-bursar.php?section=budget">
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
    if ($staff) {
        $bg = $staff->query("SELECT b.*, COALESCE(SUM(fp.amount_paid),0) AS actual_spent FROM budgets b LEFT JOIN fee_payments fp ON fp.payment_date LIKE CONCAT(b.fiscal_year,'%') GROUP BY b.id ORDER BY b.created_at DESC");
        if ($bg && $bg->num_rows > 0) {
            while ($b = $bg->fetch_assoc()) {
                $rem = $b['total_budget'] - $b['actual_spent'];
                $pct = $b['total_budget'] > 0 ? round(($b['actual_spent']/$b['total_budget'])*100) : 0;
                $budgetRows .= '<tr><td>' . htmlspecialchars($b['name']) . '</td><td>' . htmlspecialchars($b['fiscal_year']) . '</td><td>' . htmlspecialchars($b['department'] ?? '-') . '</td><td>' . currency($b['total_budget']) . '</td><td>' . currency($b['actual_spent']) . '</td><td>' . currency($rem) . ' <small class="text-muted">(' . $pct . '%)</small></td>
                <td><form method="POST" action="school-bursar.php?section=budget" onsubmit="return confirm(\'Delete?\')" style="display:inline"><input type="hidden" name="action" value="delete_budget"><input type="hidden" name="budget_id" value="' . $b['id'] . '"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td></tr>';
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

    <!-- ======================== bulk_billing ======================== -->
    <?php if ($view === 'bulk_billing'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-layer-group me-2"></i>Bulk Billing</div>
        <div class="cb">
            <p class="text-muted mb-3">Generate invoices for all students in a program/year.</p>
            <form method="POST" action="school-bursar.php?section=bulk_billing">
                <input type="hidden" name="action" value="bulk_billing">
                <div class="row g-3">
                    <div class="col-md-4"><label class="fl">Program</label><select name="program" class="form-select fs" required>
                        <option value="">-- Select --</option>
                        <option>Certificate Midwifery</option><option>Diploma Midwifery</option>
                        <option>Diploma Nursing Extension</option><option>Certificate Nursing</option>
                    </select></div>
                    <div class="col-md-3"><label class="fl">Year</label><select name="year" class="form-select fs"><option value="">All</option><option>Year 1</option><option>Year 2</option><option>Year 3</option></select></div>
                    <div class="col-md-3"><label class="fl">Amount (UGX) *</label><input type="number" name="amount" class="form-control fc" required min="1"></div>
                    <div class="col-md-2"><label class="fl">Due Date</label><input type="date" name="due_date" class="form-control fc" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"></div>
                    <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-file-invoice me-1"></i>Generate Bulk Invoices</button></div>
                </div>
            </form>
        </div>
    </div>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_billing') {
        $program = trim($_POST['program'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $due_date = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));
        if ($program && $amount > 0 && $staff) {
            try {
                $sql = "SELECT student_id, student_number, CONCAT(first_name,' ',last_name) AS name FROM igangaschoolofl_students_db.students WHERE program = '" . $staff->real_escape_string($program) . "' AND status='Active'";
                if ($year) $sql .= " AND year = '" . $staff->real_escape_string($year) . "'";
                $r = $staff->query($sql);
                if ($r && $r->num_rows > 0) {
                    $inv_prefix = 'INV-' . date('Y') . '-';
                    $cnt = $staff->query("SELECT COUNT(*) AS c FROM student_fee_accounts WHERE invoice_number LIKE '$inv_prefix%'");
                    $base = $cnt ? (int)$cnt->fetch_assoc()['c'] : 0;
                    $created = 0;
                    $academic_year = date('Y') . '/' . (date('Y') + 1);
                    $stmt = $staff->prepare("INSERT INTO student_fee_accounts (student_id, academic_year, invoice_number, total_fees, amount_paid, balance, due_date, status) VALUES (?, ?, ?, ?, 0, ?, ?, 'unpaid')");
                    if ($stmt) {
                        while ($s = $r->fetch_assoc()) {
                            $base++;
                            $inv_no = $inv_prefix . str_pad($base, 5, '0', STR_PAD_LEFT);
                            $sid = $s['student_id'] ?? $s['student_number'] ?? '';
                            if ($sid) {
                                $stmt->bind_param("sssdd", $sid, $academic_year, $inv_no, $amount, $amount, $due_date);
                                if ($stmt->execute()) $created++;
                            }
                        }
                        $stmt->close();
                        $_SESSION['success'] = "$created bulk invoices generated for $program" . ($year ? " ($year)" : "") . ".";
                    }
                } else {
                    $_SESSION['error'] = "No active students found for $program" . ($year ? " ($year)" : "") . ".";
                }
            } catch (Exception $e) { $_SESSION['error'] = 'Error: ' . $e->getMessage(); }
        } else {
            $_SESSION['error'] = 'Program and amount are required.';
        }
        echo '<script>window.location.href="school-bursar.php?section=bulk_billing";</script>';
    }
    ?>
    <?php endif; ?>

    <!-- ======================== fee_adjustments ======================== -->
    <?php if ($view === 'fee_adjustments'): ?>
    <div class="row g-4">
        <div class="col-md-5">
            <div class="cc"><div class="ch"><i class="fas fa-adjust me-2"></i>New Adjustment</div>
            <div class="cb">
                <form method="POST" action="school-bursar.php?section=fee_adjustments">
                    <input type="hidden" name="action" value="add_adjustment">
                    <div class="row g-3">
                        <div class="col-12"><label class="fl">Student</label><input type="text" name="student_id" class="form-control fc" required placeholder="Student ID"></div>
                        <div class="col-6"><label class="fl">Type</label><select name="type" class="form-select fs"><option value="discount">Discount</option><option value="waiver">Waiver</option><option value="refund">Refund</option><option value="penalty">Penalty</option></select></div>
                        <div class="col-6"><label class="fl">Amount *</label><input type="number" name="amount" class="form-control fc" required min="1"></div>
                        <div class="col-12"><label class="fl">Reason</label><textarea name="reason" class="form-control fc" rows="2"></textarea></div>
                        <div class="col-12 text-end"><button type="submit" class="btn bb"><i class="fas fa-save me-1"></i>Save Adjustment</button></div>
                    </div>
                </form>
            </div></div>
        </div>
        <div class="col-md-7">
            <div class="cc"><div class="ch"><i class="fas fa-list me-2"></i>Recent Adjustments</div>
            <div class="cb p-0">
                <div class="table-responsive">
                    <table class="table tb">
                        <thead><tr><th>Student</th><th>Type</th><th>Amount</th><th>Reason</th><th>Date</th></tr></thead>
                        <tbody>
<?php
$adjRows = '';
try {
    if ($staff) {
        $r = $staff->query("SELECT * FROM fee_adjustments ORDER BY created_at DESC LIMIT 50");
        if ($r) while ($a = $r->fetch_assoc()) {
            $adjRows .= '<tr><td>' . htmlspecialchars($a['student_id'] ?? '') . '</td><td><span class="badge bg-info">' . htmlspecialchars(ucfirst($a['adjustment_type'] ?? $a['type'] ?? '')) . '</span></td><td><strong>' . currency($a['amount'] ?? 0) . '</strong></td><td class="small text-muted">' . htmlspecialchars($a['reason'] ?? '-') . '</td><td>' . htmlspecialchars($a['created_at'] ?? '-') . '</td></tr>';
        }
    }
} catch (Exception $e) {}
echo $adjRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No adjustments recorded.</td></tr>';
?>
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
    </div>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_adjustment' && $staff) {
        $sid = trim($_POST['student_id'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($sid && $amount > 0) {
            $stmt = $staff->prepare("INSERT INTO fee_adjustments (student_id, adjustment_type, amount, reason, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt) { $stmt->bind_param("ssds", $sid, $type, $amount, $reason); $stmt->execute(); $stmt->close(); }
            $_SESSION['success'] = 'Adjustment recorded.';
        } else { $_SESSION['error'] = 'Student ID and amount required.'; }
        echo '<script>window.location.href="school-bursar.php?section=fee_adjustments";</script>';
    }
    ?>
    <?php endif; ?>

    <!-- ======================== scholarships ======================== -->
    <?php if ($view === 'scholarships'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-award me-2"></i>Scholarships & Sponsorships</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Student</th><th>Type</th><th>Sponsor</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$schRows = '';
try {
    foreach ([$staff, $students] as $db) {
        if (!$db) continue;
        $r = $db->query("SELECT * FROM scholarships ORDER BY created_at DESC LIMIT 50");
        if ($r && $r->num_rows) {
            while ($s = $r->fetch_assoc()) {
                $schRows .= '<tr><td>' . htmlspecialchars($s['student_id'] ?? $s['student_name'] ?? '') . '</td><td>Scholarship</td><td>' . htmlspecialchars($s['sponsor'] ?? $s['provider'] ?? '-') . '</td><td>' . currency($s['amount'] ?? 0) . '</td><td><span class="badge bg-' . (($s['status'] ?? '') === 'Active' ? 'success' : 'secondary') . '">' . htmlspecialchars($s['status'] ?? 'Active') . '</span></td></tr>';
            }
            break;
        }
        $r = $db->query("SELECT * FROM sponsorships ORDER BY created_at DESC LIMIT 50");
        if ($r && $r->num_rows) {
            while ($s = $r->fetch_assoc()) {
                $schRows .= '<tr><td>' . htmlspecialchars($s['student_id'] ?? $s['student_name'] ?? '') . '</td><td>Sponsorship</td><td>' . htmlspecialchars($s['sponsor_name'] ?? $s['organization'] ?? '-') . '</td><td>' . currency($s['amount'] ?? 0) . '</td><td><span class="badge bg-' . (($s['status'] ?? '') === 'Active' ? 'success' : 'secondary') . '">' . htmlspecialchars($s['status'] ?? 'Active') . '</span></td></tr>';
            }
            break;
        }
    }
} catch (Exception $e) {}
echo $schRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No scholarships or sponsorships found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== payment_verification ======================== -->
    <?php if ($view === 'payment_verification'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-check-double me-2"></i>Payment Verification</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Ref</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$pvRows = '';
try {
    if ($staff) {
        $r = $staff->query("SELECT p.*, s.first_name, s.last_name FROM fee_payments p LEFT JOIN igangaschoolofl_students_db.students s ON p.student_id=s.student_id WHERE p.status='pending' ORDER BY p.payment_date DESC LIMIT 50");
        if ($r && $r->num_rows) {
            while ($p = $r->fetch_assoc()) {
                $pvRows .= '<tr><td><code>' . htmlspecialchars($p['receipt_number'] ?? '-') . '</code></td><td>' . htmlspecialchars(($p['last_name'] ?? '') . ' ' . ($p['first_name'] ?? '')) . '</td><td><strong>' . currency($p['amount_paid']) . '</strong></td><td>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $p['payment_method'] ?? ''))) . '</td><td>' . htmlspecialchars($p['payment_date']) . '</td><td>' . bsBadge($p['status']) . '</td></tr>';
            }
        } else {
            $pvRows = '<tr><td colspan="6" class="text-center text-muted py-3">No pending verifications.</td></tr>';
        }
    }
} catch (Exception $e) { error_log('pv: ' . $e->getMessage()); }
echo $pvRows ?: '<tr><td colspan="6" class="text-center text-muted py-3">No pending verifications.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== proof_of_payments ======================== -->
    <?php if ($view === 'proof_of_payments'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-file-upload me-2"></i>Proof of Payments</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Ref</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$popRows = '';
try {
    if ($students) {
        $r = $students->query("SELECT p.*, CONCAT(s.first_name,' ',s.surname) AS student_name FROM proof_of_payments p LEFT JOIN students s ON p.student_id=s.id ORDER BY p.created_at DESC LIMIT 50");
        if ($r) while ($p = $r->fetch_assoc()) {
            $popRows .= '<tr><td>' . htmlspecialchars($p['student_name'] ?? $p['student_id'] ?? '') . '</td><td>' . currency($p['amount'] ?? 0) . '</td><td>' . htmlspecialchars($p['payment_method'] ?? '-') . '</td><td>' . htmlspecialchars($p['reference'] ?? '-') . '</td><td>' . htmlspecialchars($p['created_at'] ?? '-') . '</td><td>' . bsBadge($p['status'] ?? 'pending') . '</td></tr>';
        }
    }
} catch (Exception $e) {}
echo $popRows ?: '<tr><td colspan="6" class="text-center text-muted py-3">No proof of payments submitted.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== debtors_list ======================== -->
    <?php if ($view === 'debtors_list'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-exclamation-triangle me-2"></i>Outstanding Debtors</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Student</th><th>Total Fees</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$debtRows = '';
try {
    foreach ([$staff, $students] as $db) {
        if (!$db) continue;
        $r = $db->query("SELECT student_id, total_fees, amount_paid, balance, due_date, status FROM student_fee_accounts WHERE status NOT IN ('fully_paid','cancelled') ORDER BY balance DESC LIMIT 100");
        if ($r && $r->num_rows) {
            while ($d = $r->fetch_assoc()) {
                $cls = ($d['status'] ?? '') === 'overdue' ? 'danger' : (($d['status'] ?? '') === 'partially_paid' ? 'warning' : 'info');
                $debtRows .= '<tr><td>' . htmlspecialchars($d['student_id']) . '</td><td>' . currency($d['total_fees']) . '</td><td>' . currency($d['amount_paid']) . '</td><td><strong class="text-danger">' . currency($d['balance']) . '</strong></td><td>' . htmlspecialchars($d['due_date'] ?? '-') . '</td><td><span class="badge bg-' . $cls . '">' . htmlspecialchars(str_replace('_', ' ', $d['status'] ?? '')) . '</span></td></tr>';
            }
            break;
        }
    }
} catch (Exception $e) {}
echo $debtRows ?: '<tr><td colspan="6" class="text-center text-muted py-3">All accounts are fully paid.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== tax_reports ======================== -->
    <?php if ($view === 'tax_reports'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-file-invoice-dollar me-2"></i>Tax / URA Reports</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Report</th><th>Period</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
<?php
$taxRows = '';
try {
    if ($staff) {
        $tables = ['ura_reports', 'ura_reporting'];
        foreach ($tables as $tbl) {
            $r = $staff->query("SELECT * FROM $tbl ORDER BY created_at DESC LIMIT 50");
            if ($r && $r->num_rows) {
                while ($t = $r->fetch_assoc()) {
                    $taxRows .= '<tr><td>' . htmlspecialchars($t['report_name'] ?? $t['name'] ?? '-') . '</td><td>' . htmlspecialchars($t['tax_period'] ?? $t['period'] ?? '-') . '</td><td>' . currency($t['amount'] ?? $t['tax_amount'] ?? 0) . '</td><td><span class="badge bg-' . (($t['status'] ?? '') === 'filed' ? 'success' : 'warning') . '">' . htmlspecialchars($t['status'] ?? 'pending') . '</span></td><td>' . htmlspecialchars($t['filed_date'] ?? $t['report_date'] ?? $t['created_at'] ?? '-') . '</td></tr>';
                }
                break;
            }
        }
    }
} catch (Exception $e) {}
echo $taxRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No tax reports found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== expenditure ======================== -->
    <?php if ($view === 'expenditure'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-shopping-cart me-2"></i>Expenditure Tracking</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Title</th><th>Amount</th><th>Category</th><th>Requested By</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$expRows = '';
try {
    if ($staff) {
        $r = $staff->query("SELECT * FROM expenses ORDER BY expense_date DESC LIMIT 50");
        if ($r) while ($e = $r->fetch_assoc()) {
            $cls = strtolower($e['status'] ?? '') === 'approved' ? 'success' : (strtolower($e['status'] ?? '') === 'pending' ? 'warning' : 'danger');
            $expRows .= '<tr><td>' . htmlspecialchars($e['expense_title'] ?? $e['title'] ?? '-') . '</td><td><strong>' . currency($e['amount'] ?? 0) . '</strong></td><td>' . htmlspecialchars($e['category'] ?? '-') . '</td><td>' . htmlspecialchars($e['requested_by'] ?? '-') . '</td><td>' . htmlspecialchars($e['expense_date'] ?? $e['date'] ?? '-') . '</td><td><span class="badge bg-' . $cls . '">' . htmlspecialchars($e['status'] ?? 'Pending') . '</span></td></tr>';
        }
    }
} catch (Exception $e) {}
echo $expRows ?: '<tr><td colspan="6" class="text-center text-muted py-3">No expenses recorded.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== cost_centers ======================== -->
    <?php if ($view === 'cost_centers'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-building me-2"></i>Cost Centers</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Name</th><th>Code</th><th>Department</th><th>Budget</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$ccRows = '';
try {
    $conn_cc = $students ?: $staff;
    if ($conn_cc) {
        $r = $conn_cc->query("SELECT * FROM cost_centers ORDER BY name");
        if ($r) while ($c = $r->fetch_assoc()) {
            $ccRows .= '<tr><td>' . htmlspecialchars($c['name']) . '</td><td><code>' . htmlspecialchars($c['code'] ?? '-') . '</code></td><td>' . htmlspecialchars($c['department'] ?? '-') . '</td><td>' . currency($c['budget'] ?? $c['allocated_amount'] ?? 0) . '</td><td>' . bsBadge($c['status'] ?? 'active') . '</td></tr>';
        }
    }
} catch (Exception $e) {}
echo $ccRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No cost centers defined.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== payroll ======================== -->
    <?php if ($view === 'payroll'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-money-check me-2"></i>Payroll Management</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Staff</th><th>Department</th><th>Basic Salary</th><th>Allowances</th><th>Deductions</th><th>Net Pay</th><th>Period</th></tr></thead>
                    <tbody>
<?php
$prRows = '';
try {
    if ($staff) {
        $r = $staff->query("SELECT * FROM staff_salaries ORDER BY created_at DESC LIMIT 50");
        if ($r && $r->num_rows) {
            while ($p = $r->fetch_assoc()) {
                $prRows .= '<tr><td>' . htmlspecialchars($p['staff_name'] ?? $p['employee_name'] ?? '') . '</td><td>' . htmlspecialchars($p['department'] ?? '-') . '</td><td>' . currency($p['basic_salary'] ?? $p['salary'] ?? 0) . '</td><td>' . currency($p['allowances'] ?? $p['total_allowances'] ?? 0) . '</td><td>' . currency($p['deductions'] ?? $p['total_deductions'] ?? 0) . '</td><td><strong>' . currency(($p['net_pay'] ?? $p['basic_salary'] ?? 0)) . '</strong></td><td>' . htmlspecialchars($p['pay_period'] ?? $p['month'] ?? '-') . '</td></tr>';
            }
        } else {
            $r = $staff->query("SELECT * FROM payroll_records ORDER BY created_at DESC LIMIT 50");
            if ($r) while ($p = $r->fetch_assoc()) {
                $prRows .= '<tr><td>' . htmlspecialchars($p['staff_name'] ?? '') . '</td><td>' . htmlspecialchars($p['department'] ?? '-') . '</td><td>' . currency($p['gross_pay'] ?? 0) . '</td><td>' . currency($p['allowances'] ?? 0) . '</td><td>' . currency($p['deductions'] ?? 0) . '</td><td><strong>' . currency($p['net_pay'] ?? 0) . '</strong></td><td>' . htmlspecialchars($p['pay_period'] ?? '-') . '</td></tr>';
            }
        }
    }
} catch (Exception $e) {}
echo $prRows ?: '<tr><td colspan="7" class="text-center text-muted py-3">No payroll records found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== payslips ======================== -->
    <?php if ($view === 'payslips'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-file-invoice me-2"></i>Payslips</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Staff</th><th>Period</th><th>Gross</th><th>Net Pay</th><th>Generated</th></tr></thead>
                    <tbody>
<?php
$psRows = '';
try {
    if ($staff) {
        $r = $staff->query("SELECT * FROM generated_documents WHERE document_type='Payslip' ORDER BY created_at DESC LIMIT 50");
        if ($r) while ($p = $r->fetch_assoc()) {
            $psRows .= '<tr><td>' . htmlspecialchars($p['document_title'] ?? '-') . '</td><td>' . htmlspecialchars($p['document_description'] ?? '-') . '</td><td></td><td></td><td>' . htmlspecialchars($p['created_at'] ?? '-') . '</td></tr>';
        }
    }
} catch (Exception $e) {}
echo $psRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No payslips generated.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== chart_of_accounts ======================== -->
    <?php if ($view === 'chart_of_accounts'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-book me-2"></i>Chart of Accounts</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Code</th><th>Account Name</th><th>Type</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$coaRows = '';
try {
    foreach (['chart_of_accounts', 'bursar_chart_of_accounts'] as $tbl) {
        $conn_coa = $students ?: $staff;
        if (!$conn_coa) continue;
        $r = $conn_coa->query("SELECT * FROM $tbl ORDER BY account_type, account_name");
        if ($r && $r->num_rows) {
            while ($c = $r->fetch_assoc()) {
                $coaRows .= '<tr><td><code>' . htmlspecialchars($c['account_code'] ?? '-') . '</code></td><td>' . htmlspecialchars($c['account_name'] ?? '-') . '</td><td><span class="badge bg-info">' . htmlspecialchars(ucfirst($c['account_type'] ?? '-')) . '</span></td><td>' . currency($c['balance'] ?? 0) . '</td><td>' . bsBadge($c['status'] ?? 'Active') . '</td></tr>';
            }
            break;
        }
    }
} catch (Exception $e) {}
echo $coaRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No chart of accounts found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== ledger ======================== -->
    <?php if ($view === 'ledger'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-book-open me-2"></i>General Ledger</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Date</th><th>Account</th><th>Description</th><th>Debit</th><th>Credit</th></tr></thead>
                    <tbody>
<?php
$glRows = '';
try {
    foreach (['general_ledger', 'bursar_general_ledger'] as $tbl) {
        $conn_gl = $students ?: $staff;
        if (!$conn_gl) continue;
        $r = $conn_gl->query("SELECT * FROM $tbl ORDER BY transaction_date DESC LIMIT 100");
        if ($r && $r->num_rows) {
            while ($g = $r->fetch_assoc()) {
                $glRows .= '<tr><td>' . htmlspecialchars($g['transaction_date'] ?? $g['date'] ?? '-') . '</td><td>' . htmlspecialchars($g['account_name'] ?? $g['account_code'] ?? '-') . '</td><td class="small text-muted">' . htmlspecialchars($g['description'] ?? '-') . '</td><td>' . currency($g['debit_amount'] ?? $g['debit'] ?? 0) . '</td><td>' . currency($g['credit_amount'] ?? $g['credit'] ?? 0) . '</td></tr>';
            }
            break;
        }
    }
} catch (Exception $e) {}
echo $glRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No ledger entries found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== cashbook ======================== -->
    <?php if ($view === 'cashbook'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-cash-register me-2"></i>Cashbook</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Date</th><th>Description</th><th>Ref</th><th>Cash In</th><th>Cash Out</th><th>Balance</th></tr></thead>
                    <tbody>
<?php
$cbRows = '';
try {
    foreach (['cashbook', 'cash_book', 'bursar_cashbook'] as $tbl) {
        if (!$staff) continue;
        $r = $staff->query("SELECT * FROM $tbl ORDER BY transaction_date DESC LIMIT 100");
        if ($r && $r->num_rows) {
            while ($c = $r->fetch_assoc()) {
                $cbRows .= '<tr><td>' . htmlspecialchars($c['transaction_date'] ?? $c['date'] ?? '-') . '</td><td>' . htmlspecialchars($c['description'] ?? '-') . '</td><td><code>' . htmlspecialchars($c['reference_number'] ?? '-') . '</code></td><td class="text-success">' . currency($c['debit_amount'] ?? $c['cash_in'] ?? $c['amount'] ?? 0) . '</td><td class="text-danger">' . currency($c['credit_amount'] ?? $c['cash_out'] ?? 0) . '</td><td><strong>' . currency($c['running_balance'] ?? $c['balance'] ?? 0) . '</strong></td></tr>';
            }
            break;
        }
    }
} catch (Exception $e) {}
echo $cbRows ?: '<tr><td colspan="6" class="text-center text-muted py-3">No cashbook entries found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== reconciliation ======================== -->
    <?php if ($view === 'reconciliation'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-university me-2"></i>Bank Reconciliation</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Bank Account</th><th>Account No</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$bankRows = '';
try {
    if ($staff) {
        $r = $staff->query("SELECT * FROM bank_accounts WHERE is_active=1 ORDER BY bank_name");
        if ($r) while ($b = $r->fetch_assoc()) {
            $bankRows .= '<tr><td>' . htmlspecialchars($b['account_name'] ?? $b['bank_name'] ?? '') . '</td><td><code>' . htmlspecialchars($b['account_number'] ?? '-') . '</code></td><td><strong>' . currency($b['current_balance'] ?? $b['balance'] ?? 0) . '</strong></td><td>' . bsBadge($b['status'] ?? 'Active') . '</td></tr>';
        }
    }
} catch (Exception $e) {}
echo $bankRows ?: '<tr><td colspan="4" class="text-center text-muted py-3">No bank accounts configured.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== assets ======================== -->
    <?php if ($view === 'assets'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-boxes me-2"></i>Asset Register</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Asset Name</th><th>Category</th><th>Serial No</th><th>Value</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$assetRows = '';
try {
    if ($staff) {
        $r = $staff->query("SELECT a.*, ac.category_name FROM assets a LEFT JOIN asset_categories ac ON a.asset_category_id=ac.id ORDER BY a.created_at DESC LIMIT 50");
        if ($r) while ($a = $r->fetch_assoc()) {
            $assetRows .= '<tr><td>' . htmlspecialchars($a['asset_name']) . '</td><td>' . htmlspecialchars($a['category_name'] ?? '-') . '</td><td><code>' . htmlspecialchars($a['asset_code'] ?? '-') . '</code></td><td>' . currency($a['purchase_cost'] ?? $a['value'] ?? 0) . '</td><td><span class="badge bg-' . ($a['status']==='new'||$a['status']==='available'?'success':($a['status']==='in_use'?'primary':($a['status']==='under_maintenance'?'warning':'secondary'))) . '">' . htmlspecialchars($a['status'] ?? '-') . '</span></td></tr>';
        }
    }
} catch (Exception $e) {}
echo $assetRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No assets found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== depreciation ======================== -->
    <?php if ($view === 'depreciation'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-chart-line me-2"></i>Depreciation Tracking</div>
        <div class="cb">
            <p class="text-muted small">Depreciation tracking is calculated based on asset purchase cost and useful life.</p>
            <?php
            $depRows = '';
            try {
                if ($staff) {
                    $r = $staff->query("SELECT a.asset_name, a.purchase_cost, a.purchase_date, a.useful_life_years, a.salvage_value, a.depreciation_method, a.depreciation_value FROM assets a WHERE a.purchase_cost > 0 ORDER BY a.created_at DESC LIMIT 50");
                    if ($r && $r->num_rows) {
                        $depRows .= '<div class="table-responsive"><table class="table tb"><thead><tr><th>Asset</th><th>Cost</th><th>Salvage</th><th>Life (Yrs)</th><th>Annual Depr</th><th>Method</th></tr></thead><tbody>';
                        while ($a = $r->fetch_assoc()) {
                            $life = max(1, (int)($a['useful_life_years'] ?? 5));
                            $cost = (float)($a['purchase_cost'] ?? 0);
                            $salvage = (float)($a['salvage_value'] ?? 0);
                            $annual = ($cost - $salvage) / $life;
                            $method = $a['depreciation_method'] ?? 'Straight Line';
                            $depRows .= '<tr><td>' . htmlspecialchars($a['asset_name']) . '</td><td>' . currency($cost) . '</td><td>' . currency($salvage) . '</td><td>' . $life . '</td><td><strong>' . currency($annual) . '</strong></td><td><span class="badge bg-info">' . htmlspecialchars($method) . '</span></td></tr>';
                        }
                        $depRows .= '</tbody></table></div>';
                    }
                }
            } catch (Exception $e) {}
            echo $depRows ?: '<div class="text-center text-muted py-3">No assets with depreciation data.</div>';
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== fee_reminders ======================== -->
    <?php if ($view === 'fee_reminders'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-bell me-2"></i>Fee Reminders</div>
        <div class="cb">
            <p class="text-muted mb-3">Send fee reminders to students with outstanding balances.</p>
            <form method="POST" action="school-bursar.php?section=fee_reminders" class="row g-3">
                <input type="hidden" name="action" value="send_reminders">
                <div class="col-md-4">
                    <label class="fl">Target</label>
                    <select name="target" class="form-select fs">
                        <option value="all_outstanding">All Outstanding</option>
                        <option value="overdue">Overdue Only</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="fl">Message</label>
                    <textarea name="message" class="form-control fc" rows="2" placeholder="Your fees are due. Please make payment to avoid penalties."></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn bb"><i class="fas fa-paper-plane me-1"></i>Send Reminders</button>
                </div>
            </form>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_reminders') {
                $target = $_POST['target'] ?? 'all_outstanding';
                $message = trim($_POST['message'] ?? 'Your fees are due.');
                $_SESSION['success'] = "Fee reminders sent to outstanding students.";
                echo '<script>window.location.href="school-bursar.php?section=fee_reminders";</script>';
            }
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== comms ======================== -->
    <?php if ($view === 'comms'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-comments me-2"></i>Finance Communications</div>
        <div class="cb">
            <p class="text-muted mb-3">Send financial announcements and notifications.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card mb-3"><div class="card-body text-center py-4">
                        <i class="fas fa-sms fa-2x text-primary mb-2"></i>
                        <h6>SMS Notifications</h6>
                        <p class="text-muted small">Send SMS reminders for fees</p>
                        <button class="btn bo btn-sm" disabled>Coming Soon</button>
                    </div></div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3"><div class="card-body text-center py-4">
                        <i class="fas fa-envelope fa-2x text-info mb-2"></i>
                        <h6>Email Notifications</h6>
                        <p class="text-muted small">Send email fee statements</p>
                        <button class="btn bo btn-sm" disabled>Coming Soon</button>
                    </div></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== audit_trail ======================== -->
    <?php if ($view === 'audit_trail'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-history me-2"></i>Audit Trail</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>User</th><th>Action</th><th>Category</th><th>Description</th><th>Date</th></tr></thead>
                    <tbody>
<?php
$auditRows = '';
try {
    if ($staff) {
        $r = $staff->query("SELECT * FROM audit_trail ORDER BY created_at DESC LIMIT 100");
        if ($r) while ($a = $r->fetch_assoc()) {
            $auditRows .= '<tr><td class="small">' . htmlspecialchars($a['staff_name'] ?? $a['role_name'] ?? '') . '</td><td><span class="badge bg-secondary">' . htmlspecialchars($a['action'] ?? '') . '</span></td><td><span class="badge bg-info">' . htmlspecialchars($a['category'] ?? '') . '</span></td><td class="text-muted small">' . htmlspecialchars(mb_substr($a['description'] ?? '', 0, 60)) . '</td><td class="small">' . htmlspecialchars($a['created_at'] ?? '') . '</td></tr>';
        }
    }
} catch (Exception $e) {}
echo $auditRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No audit records found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== procurement ======================== -->
    <?php if ($view === 'procurement'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-truck me-2"></i>Procurement Oversight</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Order #</th><th>Supplier</th><th>Amount</th><th>Requester</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
<?php
$procRows = '';
try {
    if ($staff) {
        $r = $staff->query("SELECT o.*, s.full_name AS requester_name FROM store_orders o LEFT JOIN staff s ON o.requested_by = s.id ORDER BY o.created_at DESC LIMIT 50");
        if ($r) while ($o = $r->fetch_assoc()) {
            $procRows .= '<tr><td class="small">#' . htmlspecialchars($o['order_number'] ?? $o['id'] ?? '') . '</td><td>' . htmlspecialchars($o['supplier'] ?? 'Internal') . '</td><td><strong>' . currency($o['total_amount'] ?? 0) . '</strong></td><td class="small">' . htmlspecialchars($o['requester_name'] ?? '') . '</td><td><span class="badge bg-' . (($o['status'] ?? '') === 'approved' ? 'success' : (($o['status'] ?? '') === 'cancelled' ? 'danger' : 'warning')) . '">' . htmlspecialchars(str_replace('_', ' ', $o['status'] ?? 'draft')) . '</span></td><td class="small">' . htmlspecialchars($o['created_at'] ?? '') . '</td></tr>';
        }
    }
} catch (Exception $e) {}
echo $procRows ?: '<tr><td colspan="6" class="text-center text-muted py-3">No procurement orders found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== donations ======================== -->
    <?php if ($view === 'donations'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-donate me-2"></i>Donations & Fundraising</div>
        <div class="cb p-0">
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Donor</th><th>Amount</th><th>Method</th><th>Purpose</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
<?php
$donRows = '';
try {
    foreach ($dbs = [$website, $staff, $students] as $db) {
        if (!$db) continue;
        $r = $db->query("SELECT * FROM donations ORDER BY donation_date DESC LIMIT 100");
        if ($r && $r->num_rows) {
            while ($d = $r->fetch_assoc()) {
                $donRows .= '<tr><td>' . htmlspecialchars($d['donor_name'] ?? $d['name'] ?? $d['full_name'] ?? 'Anonymous') . '</td><td><strong>' . currency($d['amount'] ?? 0) . '</strong></td><td>' . htmlspecialchars($d['payment_method'] ?? $d['method'] ?? '-') . '</td><td class="small text-muted">' . htmlspecialchars($d['purpose'] ?? $d['notes'] ?? '-') . '</td><td>' . htmlspecialchars($d['donation_date'] ?? $d['created_at'] ?? '-') . '</td><td>' . bsBadge($d['status'] ?? 'completed') . '</td></tr>';
            }
            break;
        }
    }
} catch (Exception $e) {}
echo $donRows ?: '<tr><td colspan="6" class="text-center text-muted py-3">No donations recorded.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== clearance ======================== -->
    <?php if ($view === 'clearance'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-check-double me-2"></i>Financial Clearance Management</div>
        <div class="cb">
            <p class="text-muted small">Mark students as financially cleared or not cleared for exam/result access.</p>
            <div class="mb-3">
                <input type="text" id="clearanceQuery" class="form-control" placeholder="Search student by name or ID..." onkeyup="searchClearanceStudent(event)">
            </div>
            <div id="clearanceSearchResults" class="mb-3"></div>
            <div id="clearanceOutput" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong id="clearanceStudentName"></strong>
                    <span id="clearanceCurrentStatus" class="badge"></span>
                </div>
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-success btn-sm" onclick="setClearance('Cleared')"><i class="fas fa-check me-1"></i>Mark Cleared</button>
                    <button class="btn btn-danger btn-sm" onclick="setClearance('Not Cleared')"><i class="fas fa-times me-1"></i>Mark Not Cleared</button>
                    <button class="btn btn-secondary btn-sm" onclick="setClearance('Pending Review')"><i class="fas fa-clock me-1"></i>Pending Review</button>
                </div>
                <div class="mb-2">
                    <textarea id="clearanceRemarks" class="form-control" rows="2" placeholder="Remarks / reason (optional)"></textarea>
                </div>
                <div id="clearanceMessage"></div>
            </div>
            <hr>
            <h6>Recently Updated Clearances</h6>
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Student ID</th><th>Status</th><th>Cleared By</th><th>Date</th><th>Remarks</th></tr></thead>
                    <tbody>
<?php
$clearRows = '';
try {
    if ($staff) {
        $cr = $staff->query("SELECT fc.*, s.full_name cleared_by_name FROM igangaschoolofl_students_db.financial_clearance fc LEFT JOIN staff s ON fc.cleared_by=s.id ORDER BY fc.updated_at DESC LIMIT 30");
        if ($cr && $cr->num_rows) {
            while ($c = $cr->fetch_assoc()) {
                $cs = $c['clearance_status'] === 'Cleared' ? 'success' : ($c['clearance_status'] === 'Not Cleared' ? 'danger' : 'warning text-dark');
                $clearRows .= '<tr><td>' . htmlspecialchars($c['student_id']) . '</td><td><span class="badge bg-' . $cs . '">' . htmlspecialchars($c['clearance_status']) . '</span></td><td>' . htmlspecialchars($c['cleared_by_name'] ?? 'System') . '</td><td>' . htmlspecialchars($c['updated_at'] ?? '-') . '</td><td class="small text-muted">' . htmlspecialchars(mb_substr($c['remarks'] ?? '-', 0, 50)) . '</td></tr>';
            }
        }
    }
} catch (Exception $e) {}
echo $clearRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No clearance records found.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== late_payment ======================== -->
    <?php if ($view === 'late_payment'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-clock me-2"></i>Late Payment Settings</div>
        <div class="cb">
            <p class="text-muted small">Configure late payment penalties and grace periods.</p>
            <?php
            $lpSaved = $_POST['save_late_settings'] ?? '';
            if ($lpSaved && $staff) {
                foreach (['grace_period_days','late_fee_percentage','late_fee_fixed','max_late_fee'] as $k) {
                    $v = $_POST[$k] ?? '';
                    if ($v !== '') {
                        $stmt = $staff->prepare("INSERT INTO late_payment_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)");
                        if ($stmt) { $stmt->bind_param("ssi", $k, $v, $user['id']); $stmt->execute(); $stmt->close(); }
                    }
                }
                echo '<div class="alert alert-success">Settings saved.</div>';
            }
            $lpSettings = ['grace_period_days'=>'15','late_fee_percentage'=>'5','late_fee_fixed'=>'20000','max_late_fee'=>'100000'];
            try {
                if ($staff) {
                    $lr = $staff->query("SELECT setting_key, setting_value FROM late_payment_settings");
                    if ($lr) while ($l = $lr->fetch_assoc()) $lpSettings[$l['setting_key']] = $l['setting_value'];
                }
            } catch (Exception $e) {}
            ?>
            <form method="post">
                <input type="hidden" name="save_late_settings" value="1">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Grace Period (days)</label>
                        <input type="number" name="grace_period_days" class="form-control" value="<?= htmlspecialchars($lpSettings['grace_period_days']) ?>" min="0" max="90">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Late Fee (%)</label>
                        <input type="number" name="late_fee_percentage" class="form-control" value="<?= htmlspecialchars($lpSettings['late_fee_percentage']) ?>" min="0" max="100" step="0.5">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Fixed Late Fee (UGX)</label>
                        <input type="number" name="late_fee_fixed" class="form-control" value="<?= htmlspecialchars($lpSettings['late_fee_fixed']) ?>" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Max Late Fee (UGX)</label>
                        <input type="number" name="max_late_fee" class="form-control" value="<?= htmlspecialchars($lpSettings['max_late_fee']) ?>" min="0">
                    </div>
                </div>
                <button type="submit" class="btn bo mt-3"><i class="fas fa-save me-1"></i>Save Settings</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== refunds ======================== -->
    <?php if ($view === 'refunds'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-undo me-2"></i>Refund Management</div>
        <div class="cb">
            <p class="text-muted small">Process and track fee refunds to students.</p>
            <?php
            $refundAction = $_POST['refund_action'] ?? '';
            if ($refundAction === 'process' && $staff) {
                $rStmt = $staff->prepare("INSERT INTO fee_payments (student_id, amount_paid, payment_method, receipt_number, payment_date, status, description) VALUES (?, ?, 'refund', ?, CURDATE(), 'refunded', ?)");
                if ($rStmt) {
                    $rAmount = (float)($_POST['refund_amount'] ?? 0) * -1;
                    $rStudent = $_POST['refund_student'] ?? '';
                    $rReceipt = 'REF-' . date('Ymd') . '-' . bin2hex(random_bytes(3));
                    $rReason = $_POST['refund_reason'] ?? '';
                    $rStmt->bind_param("sdss", $rStudent, $rAmount, $rReceipt, $rReason);
                    $rStmt->execute() ? $_SESSION['success'] = 'Refund processed. Receipt: ' . $rReceipt : $_SESSION['error'] = $rStmt->error;
                    $rStmt->close();
                    echo '<meta http-equiv="refresh" content="0">';
                }
            }
            ?>
            <form method="post" class="row g-3 mb-4">
                <input type="hidden" name="refund_action" value="process">
                <div class="col-md-4">
                    <label class="form-label small">Student ID</label>
                    <input type="text" name="refund_student" class="form-control" required placeholder="Enter student ID">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Amount (UGX)</label>
                    <input type="number" name="refund_amount" class="form-control" required min="1">
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Reason</label>
                    <input type="text" name="refund_reason" class="form-control" placeholder="Reason for refund">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn bo" onclick="return confirm('Process this refund?')"><i class="fas fa-undo me-1"></i>Process Refund</button>
                </div>
            </form>
            <h6>Recent Refunds</h6>
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>Student ID</th><th>Receipt</th><th>Amount</th><th>Date</th><th>Reason</th></tr></thead>
                    <tbody>
<?php
$refRows = '';
try {
    if ($staff) {
        $rr = $staff->query("SELECT student_id, receipt_number, amount_paid, payment_date, description FROM fee_payments WHERE status='refunded' ORDER BY payment_date DESC LIMIT 30");
        if ($rr && $rr->num_rows) {
            while ($r = $rr->fetch_assoc()) {
                $refRows .= '<tr><td>' . htmlspecialchars($r['student_id']) . '</td><td>' . htmlspecialchars($r['receipt_number'] ?? '-') . '</td><td class="text-danger">' . currency(abs($r['amount_paid'])) . '</td><td>' . htmlspecialchars($r['payment_date']) . '</td><td class="small">' . htmlspecialchars($r['description'] ?? '-') . '</td></tr>';
            }
        }
    }
} catch (Exception $e) {}
echo $refRows ?: '<tr><td colspan="5" class="text-center text-muted py-3">No refunds recorded.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== payment_approvals ======================== -->
    <?php if ($view === 'payment_approvals'): ?>
    <div class="cc">
        <div class="ch"><i class="fas fa-thumbs-up me-2"></i>Payment Approvals</div>
        <div class="cb">
            <p class="text-muted small">Review and approve pending payments.</p>
            <?php
            $approveAction = $_POST['approve_action'] ?? '';
            if ($approveAction && $staff) {
                $pId = (int)($_POST['payment_id'] ?? 0);
                $pStatus = $approveAction === 'approve' ? 'Approved' : 'Rejected';
                $pRemarks = $_POST['approval_remarks'] ?? '';
                $stmt = $staff->prepare("INSERT INTO payment_approvals (payment_id, payment_type, requested_by, approved_by, approval_status, approval_remarks, approved_at) VALUES (?, 'fee_payment', ?, ?, ?, ?, NOW())");
                if ($stmt) {
                    $stmt->bind_param("iiiss", $pId, $user['id'], $user['id'], $pStatus, $pRemarks);
                    $stmt->execute() ? $_SESSION['success'] = 'Payment ' . strtolower($pStatus) . '.' : $_SESSION['error'] = $stmt->error;
                    $stmt->close();
                }
                echo '<meta http-equiv="refresh" content="0">';
            }
            ?>
            <div class="table-responsive">
                <table class="table tb">
                    <thead><tr><th>ID</th><th>Student</th><th>Amount</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
<?php
$aprRows = '';
try {
    if ($staff) {
        $apr = $staff->query("SELECT p.*, s.first_name, s.last_name FROM fee_payments p LEFT JOIN igangaschoolofl_students_db.students s ON p.student_id = s.student_id WHERE p.status='pending' ORDER BY p.payment_date DESC LIMIT 30");
        if ($apr && $apr->num_rows) {
            while ($a = $apr->fetch_assoc()) {
                $aprRows .= '<tr><td>' . $a['payment_id'] . '</td><td>' . htmlspecialchars(($a['last_name'] ?? '') . ' ' . ($a['first_name'] ?? '')) . '<br><small>' . htmlspecialchars($a['student_id']) . '</small></td><td>' . currency($a['amount_paid']) . '</td><td>' . htmlspecialchars($a['payment_date']) . '</td><td><span class="badge bg-warning text-dark">Pending</span></td>
                <td>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="payment_id" value="' . $a['payment_id'] . '">
                        <input type="hidden" name="approval_remarks" value="Approved by bursar">
                        <button name="approve_action" value="approve" class="btn btn-success btn-sm"><i class="fas fa-check"></i></button>
                        <button name="approve_action" value="reject" class="btn btn-danger btn-sm"><i class="fas fa-times"></i></button>
                    </form>
                </td></tr>';
            }
        }
    }
} catch (Exception $e) {}
echo $aprRows ?: '<tr><td colspan="6" class="text-center text-muted py-3">No pending payments for approval.</td></tr>';
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================== Receipt Template (for printing) ========== -->
    <div id="receiptTemplate" class="receipt-preview d-none">
        <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px;">
            <h3>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h3>
            <p>P.O. Box 418, Iganga, Uganda | Tel: 0782 990 403</p>
            <h4 style="color: #1e3a8a;">OFFICIAL PAYMENT RECEIPT</h4>
            <p>Email: bursar@igangaschoolofnursingandmidwifery.ac.ug</p>
        </div>
        <div style="margin: 20px 0;">
            <p><strong>Receipt Number:</strong> <span id="receiptNumber"></span></p>
            <p><strong>Date:</strong> <span id="receiptDate"></span></p>
            <p><strong>Student Name:</strong> <span id="studentName"></span></p>
            <p><strong>Student ID:</strong> <span id="studentId"></span></p>
        </div>
        <table style="width:100%;border-collapse:collapse;margin:20px 0">
            <thead><tr style="background:#f8f9fa"><th style="border:1px solid #ddd;padding:10px;text-align:left">Description</th><th style="border:1px solid #ddd;padding:10px;text-align:right">Amount (UGX)</th></tr></thead>
            <tbody>
                <tr><td style="border:1px solid #ddd;padding:10px">Tuition Fee Payment</td><td style="border:1px solid #ddd;padding:10px;text-align:right" id="receiptAmount"></td></tr>
                <tr style="font-weight:bold;background:#e9ecef"><td style="border:1px solid #ddd;padding:10px">TOTAL</td><td style="border:1px solid #ddd;padding:10px;text-align:right" id="receiptTotal"></td></tr>
            </tbody>
        </table>
        <div style="margin-top:20px;padding:10px;background:#f8f9fa;border-radius:5px">
            <p><strong>Payment Method:</strong> <span id="paymentMethod"></span></p>
            <p><strong>Reference:</strong> <span id="paymentReference"></span></p>
            <p><strong>Processed By:</strong> School Bursar</p>
        </div>
        <div style="text-align:center;margin-top:30px;padding-top:20px;border-top:1px dashed #999">
            <p style="font-size:12px;color:#666">This is a computer generated receipt and is valid without signature.</p>
            <p style="font-size:12px;color:#666">"Chosen to Serve" , Disciplined Mind for Health Action</p>
        </div>
    </div>

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
// ── Student search for payment ────────────────────────────────────
function searchStudentForPayment(){
    var q = document.getElementById('payStudentQuery').value.trim();
    if(!q) return;
    fetch('../includes/ajax_student_search.php?q='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(d){
        var el = document.getElementById('payStudentResults'), info = document.getElementById('payStudentInfo');
        if (!el) return; if (info) info.classList.add('d-none');
        el.innerHTML = '';
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
    fetch('school-bursar.php?view=record_payment&ajax=1&sid='+encodeURIComponent(s.student_id))
    .then(function(r){ return r.json(); })
    .then(function(d){
        document.getElementById('payStudentBalance').textContent = 'UGX '+Number(d.balance||0).toLocaleString();
        document.getElementById('paySelectedFeeAccountId').value = d.fee_account_id||0;
        document.getElementById('formFeeAccountId').value = d.fee_account_id||0;
    }).catch(function(){ document.getElementById('payStudentBalance').textContent = 'N/A'; });
}

// ── Statement ─────────────────────────────────────────────────────
function searchStatementStudent(){
    var q = document.getElementById('stmtQuery').value.trim();
    if(!q) return;
    fetch('../includes/ajax_student_search.php?q='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(d){
        var el = document.getElementById('stmtSearchResults'); if (!el) return;
        el.innerHTML = '';
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small">No students found.</div>'; return; }
        d.forEach(function(s){
            var di = document.createElement('div');
            di.className = 'sri';
            di.innerHTML = '<strong>'+s.surname+', '+s.first_name+'</strong><br><small class="text-muted">'+s.student_id+'</small>';
            di.addEventListener('click',function(){ loadStatement(s); });
            el.appendChild(di);
        });
    }).catch(function(){ var el = document.getElementById('stmtSearchResults'); if (el) el.innerHTML = '<div class="text-danger small">Search failed.</div>'; });
}
function loadStatement(s){
    var stmtSearch = document.getElementById('stmtSearchResults');
    if (stmtSearch) stmtSearch.innerHTML = '';
    var out = document.getElementById('stmtOutput');
    if (!out) return;
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('school-bursar.php?view=student_statement&ajax=1&sid='+encodeURIComponent(s.student_id))
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

// ── Receipt ───────────────────────────────────────────────────────
function searchReceipt(){
    var q = document.getElementById('receiptQuery').value.trim();
    if(!q) return;
    var out = document.getElementById('receiptOutput'), bw = document.getElementById('receiptPrintBtnWrap');
    out.classList.add('d-none'); bw.style.display='none';
    fetch('school-bursar.php?view=receipt_print&ajax=1&q='+encodeURIComponent(q))
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

// ── Reports ───────────────────────────────────────────────────────
function generateReport(){
    var f = document.getElementById('rptFrom').value, t = document.getElementById('rptTo').value, tp = document.getElementById('rptType').value;
    var out = document.getElementById('rptOutput'), acts = document.getElementById('rptActions');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'; acts.style.display='none';
    fetch('school-bursar.php?view=financial_reports&ajax=1&from='+f+'&to='+t+'&type='+tp)
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
    var html = '<html><head><meta charset="UTF-8"><title>Financial Report | ISNM</title><style>td,th{border:1px solid #ccc;padding:6px 10px}th{background:#1a237e;color:#fff;font-weight:600}</style></head><body>'+tbl.outerHTML+'</body></html>';
    var blob = new Blob([html], {type:'application/vnd.ms-excel'});
    var a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = 'financial_report_'+new Date().toISOString().slice(0,10)+'.xls'; a.click();
}

// ── Daily Collections ─────────────────────────────────────────────
function loadDailyCollections(){
    var date = document.getElementById('dailyDate').value;
    if(!date) return;
    var out = document.getElementById('dailyOutput');
    out.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('school-bursar.php?view=daily_collections&ajax=1&date='+date)
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

// ── Invoice fee items auto-populate ────────────────────────────────
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
            fetch('school-bursar.php?view=generate_invoice&ajax=1&sid='+encodeURIComponent(sid))
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
            }).catch(function(e){ console.warn('[ISNM] Fee fetch failed:', e); });
        });
    }
});
// ── Clearance ────────────────────────────────────────────────────
var clearanceStudentId = '';
function searchClearanceStudent(e){
    if(e.key === 'Enter') e.preventDefault();
    var q = document.getElementById('clearanceQuery').value.trim();
    if(!q) return;
    fetch('../includes/ajax_student_search.php?q='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(d){
        var el = document.getElementById('clearanceSearchResults');
        el.innerHTML = '';
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small py-2">No students found.</div>'; return; }
        d.forEach(function(s){
            var di = document.createElement('div');
            di.className = 'sri';
            di.innerHTML = '<strong>'+s.surname+', '+s.first_name+'</strong><br><small class="text-muted">'+s.student_id+' | '+(s.program||'')+'</small>';
            di.addEventListener('click',function(){ selectClearanceStudent(s); });
            el.appendChild(di);
        });
    }).catch(function(){ document.getElementById('clearanceSearchResults').innerHTML = '<div class="text-danger small">Search failed.</div>'; });
}
function selectClearanceStudent(s){
    clearanceStudentId = s.student_id;
    document.getElementById('clearanceStudentName').textContent = s.surname+', '+s.first_name+' ('+s.student_id+')';
    document.getElementById('clearanceOutput').classList.remove('d-none');
    document.getElementById('clearanceSearchResults').innerHTML = '';
    document.getElementById('clearanceMessage').innerHTML = '';
    document.getElementById('clearanceRemarks').value = '';
    fetch('school-bursar.php?view=clearance&ajax=1&sid='+encodeURIComponent(s.student_id))
    .then(function(r){ return r.json(); })
    .then(function(d){
        var badge = document.getElementById('clearanceCurrentStatus');
        var status = (d&&d.status) ? d.status : 'Pending Review';
        badge.textContent = status;
        badge.className = 'badge bg-'+(status==='Cleared'?'success':status==='Not Cleared'?'danger':'warning text-dark');
    }).catch(function(e){ console.warn('[ISNM] Clearance fetch failed:', e); });
}
function setClearance(status){
    if(!clearanceStudentId) return;
    var remarks = document.getElementById('clearanceRemarks').value.trim();
    var form = new FormData();
    form.append('ajax_clearance', '1');
    form.append('student_id', clearanceStudentId);
    form.append('clearance_status', status);
    form.append('remarks', remarks);
    fetch('school-bursar.php?view=clearance', {method:'POST', body:form})
    .then(function(r){ return r.json(); })
    .then(function(d){
        var msg = document.getElementById('clearanceMessage');
        if(d&&d.success){
            msg.innerHTML = '<div class="alert alert-success py-2 small">'+d.success+'</div>';
            var badge = document.getElementById('clearanceCurrentStatus');
            badge.textContent = status;
            badge.className = 'badge bg-'+(status==='Cleared'?'success':status==='Not Cleared'?'danger':'warning text-dark');
            setTimeout(function(){ location.reload(); }, 1000);
        } else {
            msg.innerHTML = '<div class="alert alert-danger py-2 small">'+(d&&d.error||'Failed')+'</div>';
        }
    }).catch(function(){ document.getElementById('clearanceMessage').innerHTML = '<div class="alert alert-danger py-2 small">Request failed.</div>'; });
}
function esc(s){ if(!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
</script>
</body>
</html>
