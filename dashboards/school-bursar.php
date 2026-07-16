<?php
/**
 * School Bursar Dashboard â€” Complete 11-Module Interface
 * Modules: Student Billing, Payment Processing, Reports, Budgeting,
 * Payroll Integration, Ledger/Accounts, Inventory, Communications,
 * RBAC, Student Self-Service, Integration
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/payment_gateway.php';
require_once __DIR__ . '/../includes/payroll_functions.php';


$ctx = bootstrapStaffDashboard(['bursar', 'school bursar', 'finance', 'director finance', 'director general', 'ceo']);
$auth = $ctx['auth'];
$user = $ctx['user'];
$staffConn = $ctx['staff'];
$stuConn = $ctx['students'];
$webConn = $ctx['website'];
$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['role'] ?? '';
$isSuper = $auth->hasFullInstitutionAccess($userRole);

// Account is created via the SQL setup script (setup_accounts.sql)

$page = $_GET['page'] ?? 'overview';
$sub = $_GET['sub'] ?? '';

// â”€â”€ Handle POST actions â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('verifyCsrfToken') || !verifyCsrfToken()) { $_SESSION['error'] = 'Invalid security token. Please try again.'; header('Location: school-bursar.php'); exit; }
    $action = $_POST['action'] ?? '';

    if ($action === 'record_payment' && $stuConn) {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim($_POST['payment_method'] ?? 'Cash');
        $ref = trim($_POST['reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $date = $_POST['payment_date'] ?? date('Y-m-d');
        $phone = trim($_POST['mobile_phone'] ?? '');
        $provider = trim($_POST['mobile_provider'] ?? '');
        if ($studentId && $amount > 0) {
            $payRef = 'PAY' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $slipNo = 'SLP' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $status = 'Completed';
            $gatewayMsg = '';
            if (stripos($method, 'mobile') !== false && $phone) {
                $gw = new PaymentGateway();
                $gwResult = $gw->requestPayment($provider ?: 'mtn', $phone, $amount, $payRef, $notes);
                if (!$gwResult['success']) { $status = 'Pending'; $gatewayMsg = ' (mobile money request sent, awaiting confirmation)'; }
                $ref = $ref ?: ($gwResult['transaction_id'] ?? '');
            }
            $mlc = strtolower($method); $method = in_array($mlc, ['mobile_money','mobile']) ? 'Mobile Money' : (in_array($mlc, ['bank','bank_deposit','bank_transfer']) ? 'Bank Transfer' : (in_array($mlc, ['cash']) ? 'Cash' : (in_array($mlc, ['cheque']) ? 'Cheque' : (in_array($mlc, ['card']) ? 'Card' : 'Cash'))));
            $stmt = $stuConn->prepare("INSERT INTO payments (payment_reference, student_id, amount_received, payment_method, transaction_ref, slip_number, payment_date, status, received_by, notes) VALUES (?,?,?,?,?,?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('sidsssssis', $payRef, $studentId, $amount, $method, $ref, $slipNo, $date, $status, $userId, $notes); if ($stmt->execute()) { $_SESSION['success'] = "Payment recorded. Ref: $payRef, Slip: $slipNo$gatewayMsg"; try { $upd = $stuConn->prepare("UPDATE student_fee_tracking SET amount_paid = amount_paid + ?, balance = balance - ? WHERE student_id = ? AND status != 'Paid'"); if ($upd) { $upd->bind_param('ddi', $amount, $amount, $studentId); $upd->execute(); $upd->close(); } $upd2 = $stuConn->prepare("UPDATE student_fee_assignments SET paid_amount = paid_amount + ?, status = CASE WHEN paid_amount + ? >= assigned_amount THEN 'Paid' ELSE 'Partially Paid' END WHERE student_id = ? AND status != 'Paid'"); if ($upd2) { $upd2->bind_param('ddi', $amount, $amount, $studentId); $upd2->execute(); $upd2->close(); } $upd3 = $stuConn->prepare("UPDATE student_invoices SET amount_paid = amount_paid + ?, status = CASE WHEN amount_paid + ? >= net_amount THEN 'Paid' ELSE 'Partially Paid' END WHERE student_id = ? AND status NOT IN ('Paid','Cancelled','Waived')"); if ($upd3) { $upd3->bind_param('ddi', $amount, $amount, $studentId); $upd3->execute(); $upd3->close(); } $notif = $stuConn->prepare("INSERT INTO student_notifications (student_id, title, message, type, is_read) VALUES (?, 'Payment Received', ?, 'Fee', 0)"); if ($notif) { $msg = "Your payment of " . number_format($amount) . " UGX has been recorded"; $notif->bind_param('is', $studentId, $msg); $notif->execute(); $notif->close(); } } catch (Exception $e) { error_log('Balance update failed for payment ' . $payRef . ': ' . $e->getMessage()); } } else { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); $_SESSION['error'] = 'Failed to record payment.'; } $stmt->close(); }
        }
        header('Location: school-bursar.php?page=payments'); exit;
    }

    if ($action === 'add_fee_structure' && $stuConn) {
        $name = trim($_POST['fee_name'] ?? ''); $amount = (float)($_POST['amount'] ?? 0);
        $feeType = trim($_POST['fee_type'] ?? 'Tuition'); $progId = (int)($_POST['program_id'] ?? 0);
        $year = (int)($_POST['year'] ?? date('Y'));
        if ($name && $amount > 0) {
            $stmt = $stuConn->prepare("INSERT INTO fee_structures (fee_name, fee_type, amount, program_id, academic_year, is_active) VALUES (?,?,?,?,?,1)");
            if ($stmt) { $stmt->bind_param('ssdii', $name, $feeType, $amount, $progId, $year); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); } else { $_SESSION['success'] = 'Fee structure added.'; } }
        }
        header('Location: school-bursar.php?page=billing'); exit;
    }

    if ($action === 'add_expense' && $staffConn) {
        $desc = trim($_POST['description'] ?? ''); $amount = (float)($_POST['amount'] ?? 0);
        $cat = trim($_POST['category'] ?? 'General'); $date = $_POST['expense_date'] ?? date('Y-m-d');
        if ($desc && $amount > 0) {
            $stmt = $staffConn->prepare("INSERT INTO expenses (expense_title, amount, category, description, expense_date, status, created_by) VALUES (?,?,?,?,?,'approved',?)");
            if ($stmt) { $stmt->bind_param('sdsssi', $desc, $amount, $cat, $desc, $date, $userId); if (!$stmt->execute()) { error_log('add_expense execute failed: ' . ($stmt->error ?? 'unknown')); $_SESSION['error'] = 'Failed to record expense.'; } else { $_SESSION['success'] = 'Expense recorded.'; } }
        }
        header('Location: school-bursar.php?page=budget'); exit;
    }

    if ($action === 'create_budget' && ($stuConn || $staffConn)) {
        $name = trim($_POST['budget_name'] ?? $_POST['budget_title'] ?? ''); $amount = (float)($_POST['total_amount'] ?? 0);
        $year = trim($_POST['year'] ?? date('Y'));
        $budgetConn = $stuConn ?? $staffConn;
        if ($name && $amount > 0) {
            $stmt = $budgetConn->prepare("INSERT INTO budgets (budget_name, total_amount, fiscal_year, status, created_by) VALUES (?,?,?,'Draft',?)");
            if ($stmt) { $stmt->bind_param('sdsi', $name, $amount, $year, $userId); if ($stmt->execute()) { $_SESSION['success'] = 'Budget created.'; } else { error_log('create_budget execute failed: ' . ($stmt->error ?? 'unknown')); $_SESSION['error'] = 'Failed to create budget.'; } }
        }
        header('Location: school-bursar.php?page=budget'); exit;
    }

    if ($action === 'run_payroll' && $staffConn) {
        $period = trim($_POST['period'] ?? date('Y-m'));
        $desc = trim($_POST['description'] ?? "Payroll $period");
        $staffConn->begin_transaction();
        try {
            $stmt = $staffConn->prepare("INSERT INTO payroll_runs (period, description, status, created_by) VALUES (?,?,'draft',?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $staffConn->error);
            $stmt->bind_param('ssi', $period, $desc, $userId);
            if (!$stmt->execute()) throw new Exception('Failed to create payroll run: ' . $stmt->error);
            $runId = $stmt->insert_id;
            $stmt->close();
            
            $emps = $staffConn->query("SELECT pe.staff_id, pe.basic_salary FROM payroll_employees pe WHERE pe.status='active'");
            if ($emps) {
                while ($emp = $emps->fetch_assoc()) {
                    $base = (float)($emp['basic_salary'] ?? 0);
                    $gross = $base;
                    $paye = calculatePAYE($gross);
                    $nssfArr = calculateNSSF($base);
                    $nssfEmp = $nssfArr['employee'] ?? 0;
                    $nssfEmpr = $nssfArr['employer'] ?? 0;
                    $net = $gross - $paye - $nssfEmp;
                    $det = $staffConn->prepare("INSERT INTO payroll_details (payroll_run_id, staff_id, basic_salary, gross_pay, paye_tax, nssf_employee, nssf_employer, other_deductions, net_pay) VALUES (?,?,?,?,?,?,?,?,?)");
                    if ($det) {
                        $det->bind_param('iiddddddd', $runId, $emp['staff_id'], $base, $gross, $paye, $nssfEmp, $nssfEmpr, 0.0, $net);
                        if (!$det->execute()) throw new Exception('Failed to insert payroll details for staff ' . $emp['staff_id'] . ': ' . $det->error);
                        $det->close();
                    }
                }
            }
            $totStmt = $staffConn->prepare("SELECT COALESCE(SUM(gross_pay),0) tg, COALESCE(SUM(paye_tax + nssf_employee + other_deductions),0) td, COALESCE(SUM(net_pay),0) tnet FROM payroll_details WHERE payroll_run_id=?");
            if ($totStmt) { $totStmt->bind_param('i', $runId); $totStmt->execute(); $totals = $totStmt->get_result(); $totStmt->close(); } else { $totals = null; }
            $t = ['tg' => 0, 'td' => 0, 'tnet' => 0];
            if ($totals) {
                $t = $totals->fetch_assoc();
                $updStmt = $staffConn->prepare("UPDATE payroll_runs SET total_gross=?, total_deductions=?, total_net=?, status='processed' WHERE id=?");
                if ($updStmt) {
                    $updStmt->bind_param('dddi', $t['tg'], $t['td'], $t['tnet'], $runId);
                    if (!$updStmt->execute()) {
                        throw new Exception('Failed to update payroll run totals: ' . $updStmt->error);
                    }
                    $updStmt->close();
                } else {
                    throw new Exception('Failed to prepare update: ' . $staffConn->error);
                }
            }
            $staffConn->commit();
            $_SESSION['success'] = "Payroll $period processed â€” Gross: " . number_format($t['tg']??0) . " UGX, Net: " . number_format($t['tnet']??0) . " UGX";
        } catch (Exception $e) {
            $staffConn->rollback();
            $_SESSION['error'] = 'Payroll processing failed: ' . $e->getMessage();
        }
        header('Location: school-bursar.php?page=payroll'); exit;
    }

    if ($action === 'reconcile_bank' && $staffConn) {
        $bookBal = (float)($_POST['book_balance'] ?? 0); $bankBal = (float)($_POST['bank_balance'] ?? 0);
        $date = $_POST['reconciliation_date'] ?? date('Y-m-d');
        $diff = $bankBal - $bookBal;
        $notes = trim($_POST['notes'] ?? '');
        $stmt = $staffConn->prepare("INSERT INTO bank_reconciliation (reconciliation_date, bank_balance, book_balance, difference, notes, status, reconciled_by) VALUES (?,?,?,?,?,'completed',?)");
        if ($stmt) { $stmt->bind_param('sdddsi', $date, $bankBal, $bookBal, $diff, $notes, $userId); if (!$stmt->execute()) { error_log('reconcile_bank execute failed: ' . ($stmt->error ?? 'unknown')); } else { $_SESSION['success'] = 'Bank reconciled.'; } }
        header('Location: school-bursar.php?page=ledger'); exit;
    }

    if ($action === 'send_reminder' && $stuConn) {
        $studentId = (int)($_POST['student_id'] ?? 0); $msg = trim($_POST['message'] ?? '');
        if ($studentId && $msg) {
            $stmt = $stuConn->prepare("INSERT INTO student_notifications (student_id, title, message, type, priority, is_read) VALUES (?,?,?,'Fee','Medium',0)");
            if ($stmt) { $stmt->bind_param('iss', $studentId, 'Fee Reminder', $msg); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); } else { $_SESSION['success'] = 'Reminder sent.'; } }
        }
        header('Location: school-bursar.php?page=communications'); exit;
    }

    if ($action === 'add_discount' && $stuConn) {
        $studentId = (int)($_POST['student_id'] ?? 0); $amount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? ''); $type = trim($_POST['discount_type'] ?? 'Discount');
        $adjNo = 'ADJ' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        if ($studentId && $amount > 0) {
            $stmt = $stuConn->prepare("INSERT INTO fee_adjustments (adjustment_number, student_id, adjustment_type, amount, reason, created_by) VALUES (?,?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('sisdsi', $adjNo, $studentId, $type, $amount, $reason, $userId); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); } else { $_SESSION['success'] = 'Adjustment applied.'; } }
        }
        header('Location: school-bursar.php?page=billing'); exit;
    }

    if ($action === 'approve_payment' && $stuConn) {
        $pid = (int)($_POST['payment_id'] ?? 0);
        if ($pid) { $st=$stuConn->prepare("UPDATE payments SET status='Completed', received_by=? WHERE id=?"); if($st){$st->bind_param('ii',$userId,$pid);if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); };$st->close();$_SESSION['success']='Payment verified.';} }
        header('Location: school-bursar.php?page=payments'); exit;
    }

    if ($action === 'add_asset' && $staffConn) {
        $name = trim($_POST['asset_name'] ?? ''); $cost = (float)($_POST['purchase_cost'] ?? $_POST['value'] ?? 0);
        $cat = trim($_POST['category'] ?? ''); $date = $_POST['purchase_date'] ?? date('Y-m-d');
        $life = (int)($_POST['useful_life'] ?? 5); $salvage = (float)($_POST['salvage_value'] ?? 0);
        if ($name && $cost > 0) {
            $st=$staffConn->prepare("INSERT INTO assets (asset_name, purchase_cost, value, category, purchase_date, useful_life_years, salvage_value, status, created_by) VALUES (?,?,?,?,?,?,?,'new',?)");
            if($st){$st->bind_param('sddssidi', $name, $cost, $cost, $cat, $date, $life, $salvage, $userId); if ($st->execute()) { $_SESSION['success']='Asset added with depreciation profile.'; } else { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); $_SESSION['error'] = 'Failed to add asset.'; } $st->close();}
        }
        header('Location: school-bursar.php?page=inventory'); exit;
    }

    if ($action === 'calculate_depreciation' && $staffConn) {
        $aid = (int)($_POST['asset_id'] ?? 0);
        if ($aid) {
            $stmt_a = $staffConn->prepare("SELECT * FROM assets WHERE id=?");
            if ($stmt_a) { $stmt_a->bind_param('i', $aid); $stmt_a->execute(); $a = $stmt_a->get_result()->fetch_assoc(); $stmt_a->close(); }
            if ($a) {
                $cost = (float)($a['purchase_cost']??$a['value']??0);
                $salvage = (float)($a['salvage_value']??0);
                $life = (int)($a['useful_life_years']??5);
                $yearsOwned = $a['purchase_date'] ? max(0, floor((time()-strtotime($a['purchase_date']))/31536000)) : 0;
                $deprPerYear = $cost > 0 && $life > 0 ? ($cost - $salvage) / $life : 0;
                $accumDepr = $deprPerYear * min($yearsOwned, $life);
                $currVal = max(0, $cost - $accumDepr);
                $stmt_u = $staffConn->prepare("UPDATE assets SET depreciation_value=?, value=? WHERE id=?");
                if ($stmt_u) { $stmt_u->bind_param('ddi', $accumDepr, $currVal, $aid); $stmt_u->execute(); $stmt_u->close(); }
                $_SESSION['success'] = "Asset depreciation updated: accumulated $accumDepr UGX, current value $currVal UGX.";
            }
        }
        header('Location: school-bursar.php?page=inventory'); exit;
    }

    if ($action === 'import_bank_csv' && $staffConn && isset($_FILES['bank_csv']) && $_FILES['bank_csv']['error'] === UPLOAD_ERR_OK) {
        $csvPath = $_FILES['bank_csv']['tmp_name'];
        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $imported = 0; $totalAmount = 0;
        if ($lines) {
            $header = true;
            foreach ($lines as $line) {
                if ($header) { $header = false; continue; }
                $cols = str_getcsv($line);
                if (count($cols) < 3) continue;
                $txnDate = trim($cols[0]);
                $desc = trim($cols[1] ?? '');
                $amount = (float)str_replace([',',' '],'',trim($cols[2]));
                $balance = isset($cols[3]) ? (float)str_replace([',',' '],'',trim($cols[3])) : 0;
                if ($amount == 0) continue;
                $txnDateFormatted = date('Y-m-d', strtotime($txnDate));
                $stmt = $staffConn->prepare("INSERT INTO bank_reconciliation (reconciliation_date, bank_balance, book_balance, difference, notes, status, reconciled_by) VALUES (?,?,?,0,?,'completed',?) ON DUPLICATE KEY UPDATE bank_balance=VALUES(bank_balance)");
                if ($stmt) { $stmt->bind_param('sddssi', $txnDateFormatted, $balance, $balance, "CSV: $desc (UGX $amount)", $userId); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
                $imported++; $totalAmount += abs($amount);
            }
            $_SESSION['success'] = "Bank CSV imported: $imported transactions, total UGX " . number_format($totalAmount);
        }
        header('Location: school-bursar.php?page=ledger'); exit;
    }

    if ($action === 'add_stock_item' && $staffConn) {
        $name = trim($_POST['item_name'] ?? ''); $cat = trim($_POST['category'] ?? '');
        $qty = (int)($_POST['quantity'] ?? 0); $unit = trim($_POST['unit'] ?? 'pcs');
        $price = (float)($_POST['unit_price'] ?? 0); $reorder = (int)($_POST['reorder_level'] ?? 0);
        if ($name && $qty > 0) {
            $st=$staffConn->prepare("INSERT INTO inventory_items (item_name, category, quantity, unit, unit_cost, reorder_level, status) VALUES (?,?,?,?,?,?,'in_stock')");
            if($st){$st->bind_param('ssidsi', $name, $cat, $qty, $unit, $price, $reorder);if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); } else { $_SESSION['success']='Stock item added.'; }$st->close();}
        }
        header('Location: school-bursar.php?page=inventory'); exit;
    }

    if ($action === 'add_tax_record' && $staffConn) {
        $type = trim($_POST['tax_type'] ?? 'withholding'); $amount = (float)($_POST['amount'] ?? 0);
        $period = trim($_POST['tax_period'] ?? date('Y-m')); $date = $_POST['tax_date'] ?? date('Y-m-d');
        if ($type === 'vat') {
            $start = $period . '-01';
            $end = date('Y-m-t', strtotime($start));
            $st=$staffConn->prepare("INSERT INTO bursar_vat_reports (period_start, period_end, net_vat, status) VALUES (?,?,?,'draft')");
            if($st){$st->bind_param('ssd', $start, $end, $amount);if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); } else { $_SESSION['success'] = 'Tax record added.'; }$st->close();}
        } else {
            $st=$staffConn->prepare("INSERT INTO bursar_withholding_tax (tax_date, payee_name, description, gross_amount, wht_rate, wht_amount, status) VALUES (?,?,?,?,6.00,?,'active')");
            if($st){$st->bind_param('sssdd', $date, 'Default', $period, $amount, $amount * 0.06);if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); } else { $_SESSION['success'] = 'Tax record added.'; }$st->close();}
        }
        header('Location: school-bursar.php?page=ura'); exit;
    }

    if ($action === 'update_provider_config' && $stuConn) {
        require_once __DIR__ . '/../includes/payment_gateway/PaymentService.php';
        $service = new PaymentService($stuConn);
        $providerKey = trim($_POST['provider_key'] ?? '');
        if ($providerKey) {
            $updates = [
                'is_enabled'              => (int)($_POST['is_enabled'] ?? 0),
                'api_key'                 => trim($_POST['api_key'] ?? ''),
                'api_secret'              => trim($_POST['api_secret'] ?? ''),
                'api_url'                 => trim($_POST['api_url'] ?? ''),
                'webhook_secret'          => trim($_POST['webhook_secret'] ?? ''),
                'transaction_fee_percent' => (float)($_POST['transaction_fee_percent'] ?? 0),
                'transaction_fee_fixed'   => (float)($_POST['transaction_fee_fixed'] ?? 0),
                'min_amount'              => (float)($_POST['min_amount'] ?? 0),
                'max_amount'              => (float)($_POST['max_amount'] ?? 10000000),
                'status'                  => trim($_POST['status'] ?? 'sandbox'),
            ];
            if (!empty($_POST['callback_url'])) {
                $updates['callback_url'] = trim($_POST['callback_url']);
            }
            $ok = $service->updateProviderConfig($providerKey, $updates);
            $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Provider '$providerKey' updated." : 'Update failed.';
        }
        header('Location: school-bursar.php?page=payment-providers'); exit;
    }
}

// â”€â”€ Data â”€â”€
$studentsList = []; $payments = []; $feeStructures = []; $expenses = []; $budgets = [];
$payrollRuns = []; $bankReconciliations = []; $feeAdjustments = []; $studentFees = [];
$pendingVerification = [];

if ($stuConn) {
    $sl = $stuConn->query("SELECT id, student_number, full_name, first_name, surname, program, status FROM students WHERE status != 'deleted' ORDER BY full_name LIMIT 200");
    if ($sl) while ($r = $sl->fetch_assoc()) { $studentsList[] = $r; }
    $pm = $stuConn->query("SELECT p.*, p.amount_received as amount, s.full_name as student_name FROM payments p JOIN students s ON p.student_id=s.id ORDER BY p.created_at DESC LIMIT 50");
    if ($pm) while ($r = $pm->fetch_assoc()) { $payments[] = $r; }
    $fs = $stuConn->query("SELECT * FROM fee_structures WHERE is_active=1 ORDER BY fee_name");
    if ($fs) while ($r = $fs->fetch_assoc()) { $feeStructures[] = $r; }
    $fa = $stuConn->query("SELECT fa.*, s.full_name as student_name FROM fee_adjustments fa JOIN students s ON fa.student_id=s.id ORDER BY fa.created_at DESC LIMIT 30");
    if ($fa) while ($r = $fa->fetch_assoc()) { $feeAdjustments[] = $r; }
    $pv = $stuConn->query("SELECT p.*, p.amount_received as amount, s.full_name as student_name FROM payments p JOIN students s ON p.student_id=s.id WHERE p.status='Pending' OR p.status='Completed' ORDER BY p.created_at DESC LIMIT 20");
    if ($pv) while ($r = $pv->fetch_assoc()) { $pendingVerification[] = $r; }
    $sf = $stuConn->query("SELECT sfe.*, s.full_name as student_name, sfe.amount as total_fees, (sfe.amount - COALESCE(sfe.paid_amount,0)) as balance FROM student_fees sfe JOIN students s ON sfe.student_id=s.id ORDER BY balance DESC LIMIT 30");
    if ($sf) while ($r = $sf->fetch_assoc()) { $studentFees[] = $r; }
}

if ($staffConn) {
    $ex = $staffConn->query("SELECT * FROM expenses ORDER BY created_at DESC LIMIT 30");
    if ($ex) while ($r = $ex->fetch_assoc()) { $expenses[] = $r; }
    $bd = ($stuConn ?? $staffConn)->query("SELECT * FROM budgets ORDER BY created_at DESC LIMIT 20");
    if ($bd) while ($r = $bd->fetch_assoc()) { $budgets[] = $r; }
    $pr = $staffConn->query("SELECT * FROM payroll_runs ORDER BY created_at DESC LIMIT 20");
    if ($pr) while ($r = $pr->fetch_assoc()) { $payrollRuns[] = $r; }
    $br = $staffConn->query("SELECT * FROM bank_reconciliation ORDER BY created_at DESC LIMIT 20");
    if ($br) while ($r = $br->fetch_assoc()) { $bankReconciliations[] = $r; }
}

// Stats
$totalCollectedToday = 0; $outstandingFees = 0; $clearedCount = 0; $notClearedCount = 0;
if ($stuConn) {
    $r = $stuConn->query("SELECT COALESCE(SUM(amount_received),0) as total FROM payments WHERE DATE(payment_date)=CURDATE() AND status='Completed'");
    if ($r) $totalCollectedToday = (float)$r->fetch_assoc()['total'];
    $r = $stuConn->query("SELECT COALESCE(SUM(amount - COALESCE(paid_amount,0)),0) as total FROM student_fees");
    if ($r) $outstandingFees = (float)$r->fetch_assoc()['total'];
    $r = $stuConn->query("SELECT COUNT(*) c FROM student_fees WHERE amount - COALESCE(paid_amount,0) <=0");
    if ($r) $clearedCount = (int)$r->fetch_assoc()['c'];
    $r = $stuConn->query("SELECT COUNT(*) c FROM student_fees WHERE amount - COALESCE(paid_amount,0) > 0");
    if ($r) $notClearedCount = (int)$r->fetch_assoc()['c'];
}

$pageTitle = 'Bursar Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root{--bs-primary:#059669}
.brs-content{margin-left:var(--sidebar-w, 270px);padding:24px;min-height:100vh;background:#f0fdf4}
.brs-header{background:linear-gradient(135deg,#059669,#34d399);color:#fff;padding:20px 28px;border-radius:14px;margin-bottom:20px}
.brs-header h1{margin:0;font-size:22px}
.brs-header p{margin:2px 0 0;opacity:.85;font-size:13px}
.brs-tabs{display:flex;gap:3px;margin-bottom:20px;background:#fff;padding:6px;border-radius:10px;flex-wrap:wrap;border:1px solid #e2e8f0}
.brs-tabs a{padding:7px 14px;border-radius:7px;color:#475569;text-decoration:none;font-size:12px;font-weight:500;transition:.2s;white-space:nowrap}
.brs-tabs a:hover,.brs-tabs a.active{background:#059669;color:#fff}
.brs-card{background:#fff;border-radius:10px;border:1px solid #e2e8f0;padding:18px;margin-bottom:16px}
.brs-card h3{margin:0 0 14px;font-size:15px;font-weight:600;color:#064e3b;border-bottom:2px solid #d1fae5;padding-bottom:10px}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
.stat-item{background:#fff;border-radius:10px;padding:16px;border:1px solid #e2e8f0;text-align:center}
.stat-item .num{font-size:26px;font-weight:700;color:#059669}
.stat-item .lbl{font-size:11px;color:#64748b;margin-top:2px}
.stat-item .mini{font-size:10px;color:#94a3b8}
@media(max-width:768px){.brs-content{margin-left:0;padding:14px}.brs-tabs a{padding:5px 10px;font-size:11px}}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; renderDashboardTopbar('Bursar / Finance Management'); ?>
<div class="brs-content">
<?php if (isset($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible"><?=htmlspecialchars($_SESSION['success'])?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>

<div class="brs-header"><h1>Bursar / Finance Management</h1><p><?=htmlspecialchars($user['full_name'] ?? 'Bursar')?> &middot; <?=htmlspecialchars($userRole)?></p></div>

<nav class="brs-tabs">
  <a href="school-bursar.php" class="<?=$page==='overview'?'active':''?>">Overview</a>
  <a href="school-bursar.php?page=billing" class="<?=$page==='billing'?'active':''?>">Billing</a>
  <a href="school-bursar.php?page=payments" class="<?=$page==='payments'?'active':''?>">Payments</a>
  <a href="school-bursar.php?page=reports" class="<?=$page==='reports'?'active':''?>">Reports</a>
  <a href="school-bursar.php?page=budget" class="<?=$page==='budget'?'active':''?>">Budget</a>
  <a href="school-bursar.php?page=payroll" class="<?=$page==='payroll'?'active':''?>">Payroll</a>
  <a href="school-bursar.php?page=ledger" class="<?=$page==='ledger'?'active':''?>">Ledger</a>
  <a href="school-bursar.php?page=inventory" class="<?=$page==='inventory'?'active':''?>">Inventory</a>
  <a href="school-bursar.php?page=communications" class="<?=$page==='communications'?'active':''?>">Comms</a>
  <a href="school-bursar.php?page=ura" class="<?=$page==='ura'?'active':''?>"><img src="../images/ura.png" alt="" style="height:14px;width:auto;margin-right:4px" onerror="this.style.display='none'">URA Tax</a>
</nav>

<?php if ($page === 'overview'): ?>
<div class="stats-row">
  <div class="stat-item"><div class="num"><?=number_format($totalCollectedToday)?></div><div class="lbl">Today's Collections</div></div>
  <div class="stat-item"><div class="num"><?=number_format($outstandingFees)?></div><div class="lbl">Outstanding Fees</div></div>
  <div class="stat-item"><div class="num"><?=$clearedCount?>/<?=($clearedCount+$notClearedCount)?></div><div class="lbl">Cleared / Total</div></div>
  <div class="stat-item"><div class="num"><?=$notClearedCount?></div><div class="lbl">With Balance</div></div>
  <div class="stat-item"><div class="num"><?=count($payments)?></div><div class="lbl">Recent Payments</div></div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="brs-card"><h3>Recent Transactions</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchPFRS" type="text" placeholder="Search..." onkeyup="filterTable('srchPFRS','tblPFRS')"></div>
<div class="table-responsive"><table id="tblPFRS" class="table table-sm"><thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php foreach (array_slice($payments,0,8) as $p): ?><tr>
      <td><?=htmlspecialchars($p['student_name'])?></td>
      <td><strong><?=number_format($p['amount'])?></strong></td>
      <td><?=htmlspecialchars(ucfirst($p['payment_method']??$p['method']??'-'))?></td>
      <td><span class="badge bg-<?=in_array($p['status'],['Completed','verified'])?'success':'warning'?>"><?=htmlspecialchars($p['status'])?></span></td>
      <td><?=htmlspecialchars($p['payment_date']??$p['created_at']??'')?></td>
    </tr><?php endforeach; if (empty($payments)): ?><tr><td colspan="5" class="text-muted text-center">No payments yet.</td></tr><?php endif; ?>
    </tbody></table></div></div>
  <div class="col-md-6">
    <div class="brs-card"><h3>Pending Verification</h3>
    <?php $unverified = array_filter($pendingVerification, fn($p)=>$p['status']==='Pending'||$p['status']==='Completed'); foreach (array_slice($unverified,0,5) as $p): ?>
      <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
        <div><strong><?=htmlspecialchars($p['student_name'])?></strong><br><small><?=number_format($p['amount'])?> UGX via <?=htmlspecialchars(ucfirst($p['payment_method']??$p['method']??'-'))?></small></div>
        <form method="post" class="d-inline"><input type="hidden" name="action" value="approve_payment"><input type="hidden" name="payment_id" value="<?=$p['id']?>"><?= csrfField() ?><button class="btn btn-sm btn-success">Verify</button></form>
      </div>
    <?php endforeach; if (empty($unverified)): ?><p class="text-muted small">No pending verifications.</p><?php endif; ?>
    </div>
    <div class="brs-card"><h3>Overdue Alerts</h3>
    <?php $overdue = array_filter($studentFees, fn($f)=>($f['balance']??0)>0); if (empty($overdue)): ?><p class="text-muted small">No overdue fees.</p>
    <?php else: foreach (array_slice($overdue,0,5) as $f): ?><div class="mb-1 small"><strong><?=htmlspecialchars($f['student_name'])?></strong> &mdash; Balance: <?=number_format($f['balance'])?> UGX</div><?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php elseif ($page === 'billing'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Fee Structure Setup</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="add_fee_structure">
      <div class="mb-2"><input class="form-control form-control-sm" name="fee_name" placeholder="Fee Name (e.g. Tuition)" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="amount" placeholder="Amount" step="0.01" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="program_id" placeholder="Program ID (optional)"></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="fee_type"><option value="Tuition">Tuition</option><option value="Registration">Registration</option><option value="Library">Library</option><option value="Laboratory">Laboratory</option><option value="Examination">Examination</option><option value="Other">Other</option></select></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="year" value="<?=date('Y')?>"></div>
      <button class="btn btn-sm btn-primary">Add Fee</button>
    </form></div>
    <div class="brs-card"><h3>Discounts & Adjustments</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="add_discount">
      <div class="mb-2"><select class="form-select form-select-sm" name="student_id" required><option value="">Select Student</option><?php foreach ($studentsList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?> (<?=htmlspecialchars($s['student_number'])?>)</option><?php endforeach; ?></select></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="discount_type"><option value="discount">Discount</option><option value="waiver">Waiver</option><option value="refund">Refund</option></select></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="amount" placeholder="Amount" step="0.01" required></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="reason" rows="2" placeholder="Reason"></textarea></div>
      <button class="btn btn-sm btn-primary">Apply</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Fee Structure</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchKONM" type="text" placeholder="Search..." onkeyup="filterTable('srchKONM','tblKONM')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Fee Name</th><th>Amount</th><th>Type</th><th>Program ID</th><th>Year</th></tr></thead><tbody>
    <?php foreach ($feeStructures as $f): ?><tr><td><?=htmlspecialchars($f['fee_name'])?></td><td><strong><?=number_format($f['amount'])?></strong></td><td><?=htmlspecialchars($f['fee_type']??'-')?></td><td><?=htmlspecialchars($f['program_id']??'All')?></td><td><?=htmlspecialchars($f['academic_year']??'')?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
    <div class="brs-card"><h3>Student Fee Balances</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchFICW" type="text" placeholder="Search..." onkeyup="filterTable('srchFICW','tblFICW')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Total Due</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($studentFees as $f): ?><tr><td><?=htmlspecialchars($f['student_name'])?></td><td><?=number_format($f['total_fees']??$f['total_due']??($f['assigned_amount']??($f['paid_amount']??$f['amount']??0)+($f['balance']??0)))?></td><td><?=number_format($f['paid']??$f['paid_amount']??($f['amount_paid']??0))?></td><td><strong class="<?=$f['balance']>0?'text-danger':'text-success'?>"><?=number_format($f['balance'])?></strong></td><td><?=$f['balance']<=0?'<span class="badge bg-success">Cleared</span>':'<span class="badge bg-danger">Outstanding</span>'?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'payments'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Record Payment</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="record_payment">
      <div class="mb-2"><select class="form-select form-select-sm" name="student_id" required><option value="">Select Student</option><?php foreach ($studentsList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?> (<?=htmlspecialchars($s['student_number'])?>)</option><?php endforeach; ?></select></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="amount" placeholder="Amount" step="0.01" required></div>
      <div class="mb-2"><select class="form-select form-select-sm" name="payment_method" id="payMethod"><option value="cash">Cash</option><option value="bank">Bank Deposit</option><option value="mobile_money">Mobile Money</option><option value="cheque">Cheque</option></select></div>
      <div id="momoFields" style="display:none" class="mb-2 row g-1"><div class="col-6"><input class="form-control form-control-sm" name="mobile_phone" placeholder="Phone (2567XXXXXXXX)"></div><div class="col-6"><select class="form-select form-select-sm" name="mobile_provider"><option value="mtn">MTN MoMo</option><option value="airtel">Airtel Money</option></select></div></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="reference" placeholder="Reference / Transaction ID"></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="payment_date" value="<?=date('Y-m-d')?>"></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Notes"></textarea></div>
      <button class="btn btn-sm btn-primary">Record Payment</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Payment History</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchRZVM" type="text" placeholder="Search..." onkeyup="filterTable('srchRZVM','tblRZVM')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Receipt</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php foreach ($payments as $p): ?><tr>
      <td><?=htmlspecialchars($p['student_name'])?></td>
      <td><strong><?=number_format($p['amount'])?></strong></td>
      <td><?=htmlspecialchars(ucfirst($p['payment_method']??$p['method']??'-'))?></td>
      <td><?=htmlspecialchars($p['slip_number']??$p['payment_reference']??'-')?></td>
      <td><span class="badge bg-<?=in_array($p['status'],['Completed','verified'])?'success':'warning'?>"><?=htmlspecialchars($p['status'])?></span></td>
      <td><?=htmlspecialchars($p['payment_date']??$p['created_at']??'')?></td>
    </tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'reports'): ?>
<div class="row">
  <div class="col-md-6">
    <div class="brs-card"><h3>Revenue by Category</h3>
    <?php $revCat = $stuConn ? $stuConn->query("SELECT fee_type, COUNT(*) cnt, SUM(amount) total FROM fee_structures WHERE is_active=1 GROUP BY fee_type") : null; if ($revCat): ?>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchBGCE" type="text" placeholder="Search..." onkeyup="filterTable('srchBGCE','tblBGCE')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Type</th><th>Count</th><th>Total</th></tr></thead><tbody>
    <?php while ($r = $revCat->fetch_assoc()): ?><tr><td><?=htmlspecialchars($r['fee_type']??'-')?></td><td><?=$r['cnt']?></td><td><strong><?=number_format($r['total'])?></strong></td></tr><?php endwhile; ?>
    </tbody></table></div><?php endif; ?>
  </div>
  <div class="col-md-6">
    <div class="brs-card"><h3>Collection Summary</h3>
    <?php
    $daily = $stuConn ? $stuConn->query("SELECT DATE(payment_date) as dt, COUNT(*) cnt, SUM(amount_received) total FROM payments WHERE status='Completed' GROUP BY DATE(payment_date) ORDER BY dt DESC LIMIT 14") : null;
    if ($daily): ?>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchQMIY" type="text" placeholder="Search..." onkeyup="filterTable('srchQMIY','tblQMIY')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Transactions</th><th>Amount</th></tr></thead><tbody>
    <?php while ($d = $daily->fetch_assoc()): ?><tr><td><?=$d['dt']?></td><td><?=$d['cnt']?></td><td><strong><?=number_format($d['total'])?></strong></td></tr><?php endwhile; ?>
    </tbody></table></div><?php endif; ?>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="brs-card"><h3>Debtors List (Outstanding)</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchFZOU" type="text" placeholder="Search..." onkeyup="filterTable('srchFZOU','tblFZOU')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Balance</th></tr></thead><tbody>
    <?php $debtors = array_filter($studentFees, fn($f)=>$f['balance']>0); foreach ($debtors as $f): ?><tr><td><?=htmlspecialchars($f['student_name'])?></td><td class="text-danger"><strong><?=number_format($f['balance'])?></strong></td></tr><?php endforeach; if (empty($debtors)): ?><tr><td colspan="2" class="text-muted">No outstanding balances.</td></tr><?php endif; ?>
    </tbody></table></div></div>
  <div class="col-md-6">
    <div class="brs-card"><h3>Expense Summary</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchVEJH" type="text" placeholder="Search..." onkeyup="filterTable('srchVEJH','tblVEJH')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Category</th><th>Total</th></tr></thead><tbody>
    <?php $expCat = $staffConn ? $staffConn->query("SELECT category, SUM(amount) total FROM expenses GROUP BY category ORDER BY total DESC") : null; if ($expCat) while ($e = $expCat->fetch_assoc()): ?><tr><td><?=htmlspecialchars(ucfirst($e['category']??'General'))?></td><td><strong><?=number_format($e['total'])?></strong></td></tr><?php endwhile; ?>
    </tbody></table></div></div>
</div>

<?php elseif ($page === 'budget'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Create Budget</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="create_budget">
      <div class="mb-2"><input class="form-control form-control-sm" name="budget_title" placeholder="Budget Title" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="total_amount" placeholder="Total Amount" step="0.01" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="budget_name" placeholder="Budget Name" value="Annual Budget <?=date('Y')?>"></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="year" value="<?=date('Y')?>"></div>
      <button class="btn btn-sm btn-primary">Create Budget</button>
    </form></div>
    <div class="brs-card"><h3>Record Expense</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="add_expense">
      <div class="mb-2"><input class="form-control form-control-sm" name="description" placeholder="Description" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="amount" placeholder="Amount" step="0.01" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="category" placeholder="Category (e.g. Utilities)"></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="department" placeholder="Department"></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="expense_date" value="<?=date('Y-m-d')?>"></div>
      <button class="btn btn-sm btn-primary">Record</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Budgets</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchCIGF" type="text" placeholder="Search..." onkeyup="filterTable('srchCIGF','tblCIGF')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Budget Name</th><th>Amount</th><th>Fiscal Year</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($budgets as $b): ?><tr><td><?=htmlspecialchars($b['budget_name']??$b['title']??'')?></td><td><strong><?=number_format($b['total_amount'])?></strong></td><td><?=htmlspecialchars($b['fiscal_year']??'')?></td><td><span class="badge bg-<?=$b['status']==='Approved'||$b['status']==='approved'?'success':'warning'?>"><?=htmlspecialchars($b['status'])?></span></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="brs-card"><h3>Expenses</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchYDKF" type="text" placeholder="Search..." onkeyup="filterTable('srchYDKF','tblYDKF')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Description</th><th>Amount</th><th>Category</th><th>Date</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($expenses as $e): ?><tr><td><?=htmlspecialchars($e['title']??$e['expense_title']??$e['description']??'')?></td><td><strong><?=number_format($e['amount'])?></strong></td><td><?=htmlspecialchars($e['category']??'-')?></td><td><?=htmlspecialchars($e['expense_date']??$e['date']??$e['created_at']??'')?></td><td><span class="badge bg-<?=in_array($e['status'],['approved','Approved','paid','Paid'])?'success':'warning'?>"><?=htmlspecialchars($e['status'])?></span></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'payroll'): ?>
<div class="brs-card mb-3" style="background:linear-gradient(135deg,#059669,#34d399);color:#fff;border:none">
  <div class="d-flex justify-content-between align-items-center">
    <div><h3 style="margin:0;color:#fff;border:none;padding:0">Payroll Management</h3><p style="margin:2px 0 0;opacity:.85;font-size:13px">Full payroll with PAYE/NSSF calculations, payslips, and approval workflow</p></div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="payroll.php" class="btn btn-sm" style="background:#fff;color:#059669;font-weight:600;border-radius:8px;padding:8px 20px;text-decoration:none"><i class="fas fa-external-link-alt me-1"></i>Comprehensive Payroll</a>
      <a href="bursar-payroll.php" class="btn btn-sm" style="background:rgba(255,255,255,0.2);color:#fff;font-weight:500;border-radius:8px;padding:8px 20px;text-decoration:none"><i class="fas fa-calculator me-1"></i>Bursar Payroll</a>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Quick Payroll Run</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="run_payroll">
      <div class="mb-2"><input class="form-control form-control-sm" name="period" placeholder="Period (e.g. 2026-07)" value="<?=date('Y-m')?>" required></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="description" rows="2" placeholder="Description"></textarea></div>
      <button class="btn btn-sm btn-primary"><i class="fas fa-calculator me-1"></i>Run Payroll (with PAYE/NSSF)</button>
    </form></div>
    <div class="brs-card"><h3>Staff Payroll Profiles</h3>
    <div class="table-responsive" style="max-height:300px;overflow-y:auto"><table class="table table-sm"><thead><tr><th>Staff</th><th>Basic Salary</th></tr></thead><tbody>
    <?php $ss = $staffConn ? $staffConn->query("SELECT pe.staff_id, pe.basic_salary, s.full_name FROM payroll_employees pe JOIN staff s ON pe.staff_id=s.id WHERE pe.status='active' ORDER BY s.full_name") : null; if ($ss) while ($s = $ss->fetch_assoc()): ?><tr><td><?=htmlspecialchars($s['full_name'])?></td><td><?=number_format($s['basic_salary']??0)?></td></tr><?php endwhile; if ($ss && $ss->num_rows===0): ?><tr><td colspan="2" class="text-muted text-center small">No payroll profiles yet</td></tr><?php endif; ?>
    </tbody></table></div></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Payroll History</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchZHAG" type="text" placeholder="Search..." onkeyup="filterTable('srchZHAG','tblZHAG')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Period</th><th>Total Gross</th><th>PAYE</th><th>NSSF</th><th>Net Pay</th><th>Approval Chain</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php $approvalChain = ['HR','PayrollOfficer','Bursar','DirectorFinance','CEO']; foreach ($payrollRuns as $p): ?><tr>
      <td><?=htmlspecialchars($p['period'])?></td>
      <td><strong><?=number_format($p['total_gross']??0)?></strong></td>
      <td><?=number_format($p['total_paye']??$p['total_deductions']??0)?></td>
      <td><?=number_format($p['total_nssf']??0)?></td>
      <td><strong><?=number_format($p['total_net']??0)?></strong></td>
      <td><?php if ($staffConn) { $rid=$p['id']; echo '<div class="d-flex gap-1" style="flex-wrap:wrap;min-width:160px">'; $paStmt = $staffConn->prepare("SELECT status FROM payroll_approvals WHERE payroll_run_id=? AND level=?"); foreach ($approvalChain as $lvl) { if ($paStmt) { $paStmt->bind_param('is', $rid, $lvl); $paStmt->execute(); $pa = $paStmt->get_result()->fetch_assoc(); } else { $pa = null; } $cls='secondary'; if ($pa) { $cls = $pa['status']==='approved' ? 'success' : ($pa['status']==='rejected' ? 'danger' : 'warning'); } echo '<span class="badge bg-'.$cls.'" style="font-size:9px">'.$lvl[0].'</span>'; } if ($paStmt) $paStmt->close(); echo '</div>'; } else { echo '<span class="text-muted small">-</span>'; } ?></td>
      <td><span class="badge bg-<?=in_array($p['status'],['processed','completed','paid','approved'])?'success':($p['status']==='processing'?'info':'warning')?>"><?=htmlspecialchars($p['status'])?></span></td>
      <td><?=htmlspecialchars($p['run_date']??$p['start_date']??$p['created_at']??'')?></td>
    </tr><?php endforeach; if (empty($payrollRuns)): ?><tr><td colspan="8" class="text-muted text-center">No payroll runs yet.</td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="brs-card"><h3>Integration with HR</h3>
    <p class="text-muted small">Payroll pulls staff data from HR's staff table. Staff must have a <strong>payroll profile</strong> (bank, TIN, NSSF, salary) set up in the full payroll system.</p>
    <?php $pendingPayrollSetup = $staffConn ? $staffConn->query("SELECT COUNT(*) c FROM staff WHERE id NOT IN (SELECT staff_id FROM payroll_employees WHERE staff_id IS NOT NULL) AND status='active'")->fetch_assoc()['c'] : 0; ?>
    <p class="small mb-0"><?=$pendingPayrollSetup?> active staff missing payroll profiles. <a href="bursar-payroll.php?tab=employees" class="text-primary fw-medium">Set up in Payroll System</a></p>
  </div>
</div>

<?php elseif ($page === 'ledger'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Bank Reconciliation</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="reconcile_bank">
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="bank_balance" placeholder="Bank Statement Balance" step="0.01" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="book_balance" placeholder="Book Balance" step="0.01" required></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="reconciliation_date" value="<?=date('Y-m-d')?>"></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Notes (optional)"></textarea></div>
      <button class="btn btn-sm btn-primary">Reconcile</button>
    </form>
    <hr>
    <div class="brs-card"><h3>Import Bank Statement (CSV)</h3>
    <form method="post" enctype="multipart/form-data"><?= csrfField() ?><input type="hidden" name="action" value="import_bank_csv">
      <div class="mb-2"><input type="file" class="form-control form-control-sm" name="bank_csv" accept=".csv" required></div>
      <div class="mb-2"><small class="text-muted">CSV columns: Date, Description, Amount, Balance</small></div>
      <button class="btn btn-sm btn-info">Upload & Auto-Reconcile</button>
    </form>
    </div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Chart of Accounts</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchDIJO" type="text" placeholder="Search..." onkeyup="filterTable('srchDIJO','tblDIJO')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Account Code</th><th>Name</th><th>Type</th></tr></thead><tbody>
    <?php $coa = $staffConn ? $staffConn->query("SELECT * FROM chart_of_accounts ORDER BY code") : null; if ($coa) while ($a = $coa->fetch_assoc()): ?><tr><td><?=htmlspecialchars($a['code'])?></td><td><?=htmlspecialchars($a['name'])?></td><td><?=htmlspecialchars($a['type']??'-')?></td></tr><?php endwhile; ?>
    </tbody></table></div>
    <div class="brs-card"><h3>Bank Reconciliations</h3>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchXYSO" type="text" placeholder="Search..." onkeyup="filterTable('srchXYSO','tblXYSO')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Bank Balance</th><th>Book Balance</th><th>Difference</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($bankReconciliations as $b): ?><tr><td><?=htmlspecialchars($b['reconciliation_date']??'')?></td><td><strong><?=number_format($b['bank_balance']??0)?></strong></td><td><?=number_format($b['book_balance']??0)?></td><td><?=number_format($b['difference']??0)?></td><td><span class="badge bg-<?=$b['status']==='completed'?'success':'warning'?>"><?=htmlspecialchars($b['status'])?></span></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>

<?php elseif ($page === 'inventory'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Add Fixed Asset</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="add_asset">
      <div class="mb-2"><input class="form-control form-control-sm" name="asset_name" placeholder="Asset Name" required></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="value" placeholder="Purchase Cost (UGX)" step="0.01" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="category" placeholder="Category (e.g. Furniture, IT, Vehicle)"></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="purchase_date" value="<?=date('Y-m-d')?>"></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="useful_life" placeholder="Useful Life (years)" value="5" min="1"></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="salvage_value" placeholder="Salvage Value" value="0" step="0.01"></div>
      <button class="btn btn-sm btn-primary">Add Asset</button>
    </form>
    <hr>
    <div class="brs-card"><h3>Add Stock Item</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="add_stock_item">
      <div class="mb-2"><input class="form-control form-control-sm" name="item_name" placeholder="Item Name" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="category" placeholder="Category (e.g. Office Supplies, Cleaning)"></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="quantity" placeholder="Quantity" value="1" min="1"></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="unit" placeholder="Unit (e.g. pcs, kg, liters)" value="pcs"></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="unit_price" placeholder="Unit Price" step="0.01"></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="reorder_level" placeholder="Reorder Level" value="5"></div>
      <button class="btn btn-sm btn-success">Add Stock Item</button>
    </form>
    </div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Fixed Asset Register with Depreciation</h3>
    <?php $assets = $staffConn ? $staffConn->query("SELECT * FROM assets ORDER BY created_at DESC LIMIT 20") : null; if ($assets && $assets->num_rows > 0): ?>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchQMLF" type="text" placeholder="Search..." onkeyup="filterTable('srchQMLF','tblQMLF')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Asset</th><th>Purchase Cost</th><th>Current Value</th><th>Depr/yr</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php while ($a = $assets->fetch_assoc()):
      $cost = (float)($a['purchase_cost']??$a['value']??0);
      $salvage = (float)($a['salvage_value']??0);
      $life = (int)($a['useful_life_years']??5);
      $deprPerYear = $cost > 0 && $life > 0 ? ($cost - $salvage) / $life : 0;
      $yearsOwned = $a['purchase_date'] ? max(0, floor((time()-strtotime($a['purchase_date']))/31536000)) : 0;
      $accumDepr = $deprPerYear * min($yearsOwned, $life);
      $currVal = max(0, $cost - $accumDepr);
    ?><tr>
      <td><?=htmlspecialchars($a['asset_name']??'')?></td>
      <td><strong><?=number_format($cost)?></strong></td>
      <td><?=number_format($currVal)?> <small class="text-muted">(<?=$yearsOwned?>yrs)</small></td>
      <td><?=number_format($deprPerYear)?></td>
      <td><span class="badge bg-<?=in_array($a['status'],['new','available'])?'success':'warning'?>"><?=htmlspecialchars($a['status']??'new')?></span></td>
      <td><form method="post" class="d-inline"><input type="hidden" name="action" value="calculate_depreciation"><input type="hidden" name="asset_id" value="<?=$a['id']?>"><?= csrfField() ?><button class="btn btn-sm btn-outline-info py-0" title="Update depreciation">Calc Depr</button></form></td>
    </tr><?php endwhile; ?>
    </tbody></table></div>
    <?php else: ?><p class="text-muted small">No assets tracked yet.</p><?php endif; ?>
    </div>
    <div class="brs-card"><h3>Stock / Consumables Inventory</h3>
    <?php $invItems = $staffConn ? $staffConn->query("SELECT * FROM inventory_items ORDER BY created_at DESC LIMIT 20") : null; if ($invItems && $invItems->num_rows > 0): ?>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchERKF" type="text" placeholder="Search..." onkeyup="filterTable('srchERKF','tblERKF')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Item</th><th>Category</th><th>Qty</th><th>Unit</th><th>Unit Cost</th><th>Status</th></tr></thead><tbody>
    <?php while ($i = $invItems->fetch_assoc()): $reorder = (int)($i['reorder_level']??0); $qty = (int)($i['quantity']??0); $lowStock = $reorder > 0 && $qty <= $reorder; ?>
    <tr>
      <td><?=htmlspecialchars($i['item_name']??'')?></td>
      <td><?=htmlspecialchars($i['category']??'-')?></td>
      <td><strong class="<?=$lowStock?'text-danger':''?>"><?=$qty?></strong> <?=$lowStock?'<i class="fas fa-exclamation-triangle text-danger" title="Low stock"></i>':''?></td>
      <td><?=htmlspecialchars($i['unit']??'-')?></td>
      <td><?=number_format($i['unit_cost']??0)?></td>
      <td><span class="badge bg-<?=$qty<=0?'danger':($lowStock?'warning':'success')?>"><?=$qty<=0?'Out of Stock':($lowStock?'Low':'In Stock')?></span></td>
    </tr><?php endwhile; ?>
    </tbody></table></div>
    <?php else: ?><p class="text-muted small">No stock items tracked yet.</p><?php endif; ?>
    <p class="small mt-2"><a href="#" onclick="alert('Full inventory report coming soon with CSV export.')" class="text-primary">Download Stock Report</a></p>
    </div>
  </div>
</div>

<?php elseif ($page === 'communications'): ?>
<div class="row">
  <div class="col-md-5">
    <div class="brs-card"><h3>Send Fee Reminder</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="send_reminder">
      <div class="mb-2"><select class="form-select form-select-sm" name="student_id" required><option value="">Select Student</option><?php foreach ($studentsList as $s): ?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?></option><?php endforeach; ?></select></div>
      <div class="mb-2"><textarea class="form-control form-control-sm" name="message" rows="4" placeholder="Reminder message" required>Dear student, your fee balance is due. Please clear to avoid penalties.</textarea></div>
      <button class="btn btn-sm btn-primary">Send Reminder</button>
    </form></div>
  </div>
  <div class="col-md-7">
    <div class="brs-card"><h3>Financial Announcements</h3>
    <?php $notices = $stuConn ? $stuConn->query("SELECT * FROM financial_notices ORDER BY created_at DESC LIMIT 10") : null; if ($notices && $notices->num_rows > 0): while ($n = $notices->fetch_assoc()): ?>
      <div class="mb-2 pb-2 border-bottom"><strong><?=htmlspecialchars($n['title']??$n['subject']??'Announcement')?></strong><br><small><?=htmlspecialchars(substr($n['message']??$n['content']??'',0,200))?></small><br><span class="text-muted small"><?=$n['created_at']?></span></div>
    <?php endwhile; else: ?><p class="text-muted small">No financial announcements.</p><?php endif; ?>
    </div>
  </div>
</div>

<?php elseif ($page === 'ura'): ?>
<?php $ura_logo = '../images/ura.png'; $ura_logo_abs = __DIR__ . '/../images/ura.png'; ?>
<div class="row">
  <div class="col-md-4">
    <div class="brs-card d-flex align-items-center gap-3 mb-3" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;border:none">
      <?php if (file_exists($ura_logo_abs)): ?><img src="<?=$ura_logo?>" alt="URA" style="height:50px;width:auto;filter:brightness(0) invert(1);"><?php else: ?><i class="fas fa-university fa-2x"></i><?php endif; ?>
      <div><h3 style="margin:0;color:#fff;font-size:16px;border:none;padding:0">Uganda Revenue Authority</h3><p style="margin:0;opacity:.8;font-size:12px">Tax Compliance &amp; Reporting</p></div>
    </div>
    <div class="brs-card"><h3><i class="fas fa-plus-circle me-1"></i> Add Tax Record</h3>
    <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="add_tax_record">
      <div class="mb-2"><select class="form-select form-select-sm" name="tax_type"><option value="withholding">Withholding Tax</option><option value="vat">VAT</option></select></div>
      <div class="mb-2"><input type="number" class="form-control form-control-sm" name="amount" placeholder="Amount (UGX)" step="0.01" required></div>
      <div class="mb-2"><input class="form-control form-control-sm" name="tax_period" placeholder="Period (e.g. 2026-07)" value="<?=date('Y-m')?>"></div>
      <div class="mb-2"><input type="date" class="form-control form-control-sm" name="tax_date" value="<?=date('Y-m-d')?>"></div>
      <button class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Add Record</button>
    </form></div>
  </div>
  <div class="col-md-8">
    <div class="brs-card"><h3><img src="<?=$ura_logo?>" alt="URA" style="height:22px;width:auto;margin-right:8px;vertical-align:middle" onerror="this.style.display='none'"> URA Tax Compliance Dashboard</h3>
    <div class="row">
      <div class="col-md-6">
        <h4 class="fs-6"><i class="fas fa-percent text-success me-1"></i> Withholding Tax</h4>
        <?php $wht = $staffConn ? $staffConn->query("SELECT * FROM bursar_withholding_tax ORDER BY tax_date DESC LIMIT 10") : null; if ($wht && $wht->num_rows > 0): ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchLRMF" type="text" placeholder="Search..." onkeyup="filterTable('srchLRMF','tblLRMF')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Period</th><th>Amount</th><th>Status</th></tr></thead><tbody>
        <?php while ($w = $wht->fetch_assoc()): ?><tr><td><?=htmlspecialchars($w['tax_date']??$w['period']??'')?></td><td><?=number_format($w['wht_amount']??$w['gross_amount']??0)?></td><td><?=htmlspecialchars($w['status']??'-')?></td></tr><?php endwhile; ?>
        </tbody></table></div>
        <?php else: ?><p class="text-muted small">No withholding tax records.</p><?php endif; ?>
      </div>
      <div class="col-md-6">
        <h4 class="fs-6"><i class="fas fa-chart-pie text-primary me-1"></i> VAT Reports</h4>
        <?php $vat = $staffConn ? $staffConn->query("SELECT * FROM bursar_vat_reports ORDER BY created_at DESC LIMIT 10") : null; if ($vat && $vat->num_rows > 0): ?>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchFWIA" type="text" placeholder="Search..." onkeyup="filterTable('srchFWIA','tblFWIA')"></div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Period</th><th>Net</th><th>Status</th></tr></thead><tbody>
        <?php while ($v = $vat->fetch_assoc()): ?><tr><td><?=htmlspecialchars($v['period_start']??$v['period_end']??'')?></td><td><?=number_format($v['net_vat']??$v['output_vat']??0)?></td><td><?=htmlspecialchars($v['status']??'-')?></td></tr><?php endwhile; ?>
        </tbody></table></div>
        <?php else: ?><p class="text-muted small">No VAT records.</p><?php endif; ?>
      </div>
    </div>
    <div class="d-flex gap-2 mt-3">
      <a href="ura_reporting.php" class="btn btn-sm btn-primary"><img src="<?=$ura_logo?>" alt="" style="height:14px;width:auto;margin-right:6px" onerror="this.style.display='none'">Full URA Reporting Portal</a>
      <a href="ura_reporting.php?generate=1&type=vat" class="btn btn-sm btn-outline-success"><i class="fas fa-download me-1"></i>VAT CSV</a>
      <a href="ura_reporting.php?generate=1&type=wht" class="btn btn-sm btn-outline-info"><i class="fas fa-download me-1"></i>WHT CSV</a>
    </div>
    </div>
  </div>
</div>
<?php elseif ($page === 'payment-providers'): ?>
<?php require_once __DIR__ . '/../includes/payment_gateway/PaymentService.php';
$pgService = new PaymentService($stuConn);
$pgProviders = $pgService->getEnabledProviders();
$pgAll = $stuConn ? $stuConn->query("SELECT * FROM payment_providers ORDER BY provider_type ASC, provider_name ASC") : null;
$pgStats = $pgService->getTransactionStats('month');
?>
<div class="row mb-3">
  <div class="col-md-3"><div class="brs-card text-center"><h4 class="fs-5 mb-1"><?= number_format($pgStats['total_transactions'] ?? 0) ?></h4><small class="text-muted">Transactions (Month)</small></div></div>
  <div class="col-md-3"><div class="brs-card text-center"><h4 class="fs-5 mb-1"><?= number_format($pgStats['total_amount'] ?? 0) ?></h4><small class="text-muted">Total Amount (UGX)</small></div></div>
  <div class="col-md-3"><div class="brs-card text-center"><h4 class="fs-5 mb-1"><?= number_format($pgStats['successful_count'] ?? 0) ?></h4><small class="text-muted">Successful</small></div></div>
  <div class="col-md-3"><div class="brs-card text-center"><h4 class="fs-5 mb-1"><?= number_format($pgStats['pending_count'] ?? 0) ?></h4><small class="text-muted">Pending</small></div></div>
</div>
<?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="row">
  <div class="col-md-12">
    <div class="brs-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0" style="font-size:16px"><i class="fas fa-credit-card me-1"></i> Payment Providers</h3>
        <span class="badge bg-info"><?= count($pgAll ? $pgAll->fetch_all(MYSQLI_ASSOC) : []) ?> configured</span>
      </div>
      <p class="text-muted small mb-3">Manage payment gateway integrations. Enable providers, enter API credentials, and configure fees. Bank Transfer is always available as manual verification.</p>
      <?php if ($pgAll && $pgAll->num_rows > 0): ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover" id="tblPG">
          <thead class="table-light"><tr>
            <th>Provider</th><th>Type</th><th>Currencies</th><th>Fee %</th><th>Fee Fixed</th><th>Min</th><th>Max</th><th>Status</th><th>Enabled</th><th>Action</th>
          </tr></thead>
          <tbody>
          <?php while ($pg = $pgAll->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($pg['provider_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($pg['provider_key']) ?></small></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$pg['provider_type']))) ?></span></td>
            <td><small><?= htmlspecialchars($pg['supported_currencies'] ?? 'UGX') ?></small></td>
            <td><?= number_format((float)$pg['transaction_fee_percent'], 2) ?>%</td>
            <td><?= number_format((float)$pg['transaction_fee_fixed'], 0) ?></td>
            <td><small><?= number_format((float)$pg['min_amount'], 0) ?></small></td>
            <td><small><?= number_format((float)$pg['max_amount'], 0) ?></small></td>
            <td><span class="badge bg-<?= $pg['status']==='active'?'success':($pg['status']==='sandbox'?'warning':'secondary') ?>"><?= ucfirst($pg['status']) ?></span></td>
            <td><?= $pg['is_enabled'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-muted"></i>' ?></td>
            <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#pgModal<?= htmlspecialchars($pg['provider_key']) ?>"><i class="fas fa-cog"></i> Configure</button></td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p class="text-muted">No payment providers found.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php
// Generate config modals
$pgAll2 = $stuConn ? $stuConn->query("SELECT * FROM payment_providers ORDER BY provider_name ASC") : null;
if ($pgAll2):
while ($pg = $pgAll2->fetch_assoc()):
  $pk = htmlspecialchars($pg['provider_key']);
?>
<div class="modal fade" id="pgModal<?= $pk ?>" tabindex="-1" aria-labelledby="pgModalLabel<?= $pk ?>" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="post" action="school-bursar.php?page=payment-providers">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update_provider_config">
      <input type="hidden" name="provider_key" value="<?= $pk ?>">
      <div class="modal-header"><h5 class="modal-title" id="pgModalLabel<?= $pk ?>"><i class="fas fa-cog me-1"></i> <?= htmlspecialchars($pg['provider_name']) ?> Configuration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="sandbox" <?= $pg['status']==='sandbox'?'selected':'' ?>>Sandbox (Testing)</option>
              <option value="active" <?= $pg['status']==='active'?'selected':'' ?>>Active (Live)</option>
              <option value="inactive" <?= $pg['status']==='inactive'?'selected':'' ?>>Inactive (Disabled)</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Enabled</label>
            <select name="is_enabled" class="form-select form-select-sm">
              <option value="0" <?= !$pg['is_enabled']?'selected':'' ?>>No</option>
              <option value="1" <?= $pg['is_enabled']?'selected':'' ?>>Yes</option>
            </select>
          </div>
          <div class="col-12"><hr><strong class="text-primary">API Credentials</strong></div>
          <div class="col-md-6"><label class="form-label">API Key / App ID</label><input type="text" class="form-control form-control-sm" name="api_key" value="<?= htmlspecialchars($pg['api_key'] ?? '') ?>" placeholder="Enter API key"></div>
          <div class="col-md-6"><label class="form-label">API Secret / App Secret</label><input type="password" class="form-control form-control-sm" name="api_secret" value="<?= htmlspecialchars($pg['api_secret'] ?? '') ?>" placeholder="Enter API secret"></div>
          <div class="col-md-8"><label class="form-label">API URL</label><input type="url" class="form-control form-control-sm" name="api_url" value="<?= htmlspecialchars($pg['api_url'] ?? '') ?>" placeholder="https://api.provider.com"></div>
          <div class="col-md-4"><label class="form-label">Webhook Secret</label><input type="text" class="form-control form-control-sm" name="webhook_secret" value="<?= htmlspecialchars($pg['webhook_secret'] ?? '') ?>" placeholder="Optional"></div>
          <div class="col-12"><hr><strong class="text-primary">Callback URL</strong><br><small class="text-muted">Set this URL in your provider dashboard as the webhook/callback endpoint:</small><br><code class="text-break"><?= htmlspecialchars(('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_BASE_PATH . '/includes/payment_gateway/handlers/webhook_handler.php?provider=' . $pg['provider_key'])) ?></code></div>
          <div class="col-12"><hr><strong class="text-primary">Fee Configuration</strong></div>
          <div class="col-md-4"><label class="form-label">Fee Percent (%)</label><input type="number" class="form-control form-control-sm" name="transaction_fee_percent" value="<?= htmlspecialchars($pg['transaction_fee_percent'] ?? '0') ?>" step="0.01" min="0" max="100"></div>
          <div class="col-md-4"><label class="form-label">Fixed Fee (UGX)</label><input type="number" class="form-control form-control-sm" name="transaction_fee_fixed" value="<?= htmlspecialchars($pg['transaction_fee_fixed'] ?? '0') ?>" step="1" min="0"></div>
          <div class="col-md-4"><label class="form-label">Currencies</label><input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($pg['supported_currencies'] ?? 'UGX') ?>" disabled></div>
          <div class="col-md-6"><label class="form-label">Min Amount</label><input type="number" class="form-control form-control-sm" name="min_amount" value="<?= htmlspecialchars($pg['min_amount'] ?? '0') ?>" step="1" min="0"></div>
          <div class="col-md-6"><label class="form-label">Max Amount</label><input type="number" class="form-control form-control-sm" name="max_amount" value="<?= htmlspecialchars($pg['max_amount'] ?? '10000000') ?>" step="1" min="0"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save Configuration</button></div>
    </form>
  </div></div>
</div>
<?php endwhile; endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var forms = document.querySelectorAll('#pgModalmtn_momo form, #pgModalairtel_money form, #pgModalairstripe form, #pgModalstripe form, #pgModalfpesapal form, #pgModalbank_transfer form, .modal form');
    forms.forEach(function(f) {
        var t = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
        if (!f.querySelector('input[name="csrf_token"]')) {
            var i = document.createElement('input'); i.type='hidden'; i.name='csrf_token'; i.value=t; f.appendChild(i);
        }
    });
});
</script>
<?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION['csrf_token'])?>';document.querySelectorAll('form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});var pm=document.getElementById('payMethod');if(pm){pm.addEventListener('change',function(){document.getElementById('momoFields').style.display=this.value==='mobile_money'?'flex':'none';});}
});
function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
        }
        tr[i].style.display = found ? "" : "none";
    }
}

</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
