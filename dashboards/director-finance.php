<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';

$ctx = bootstrapStaffDashboard(['director', 'finance']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Finance Director';
$website_conn = $ctx['website'];
$students_conn = $ctx['students'] ?? null;
$user_id = (int)($user['id'] ?? 0);

function safeCount($c, $s) { $r=$c->query($s); if(!$r)return 0; $w=$r->fetch_assoc(); return intval($w['c']??0); }

// ── Real SQL stats ──
$total_students   = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students") : 0;
$total_staff      = safeCount($conn,"SELECT COUNT(*)c FROM staff WHERE status='Active'");
$total_revenue    = $students_conn ? (float)(($r=$students_conn->query("SELECT COALESCE(SUM(amount_received),0) total FROM payments WHERE status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['total']:0) : 0;
$total_expenses   = (float)(($r=$conn->query("SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE status IN('approved','paid')"))&&$r?$r->fetch_assoc()['total']:0);
$outstanding_fees = $students_conn ? (float)(($r=$students_conn->query("SELECT COALESCE(SUM(balance),0) total FROM student_invoices WHERE status IN('pending','partial','overdue')"))&&$r?$r->fetch_assoc()['total']:0) : 0;
$pending_expenses = safeCount($conn,"SELECT COUNT(*)c FROM expenses WHERE status='pending'");
$pending_approvals_count = safeCount($conn,"SELECT COUNT(*)c FROM approval_requests WHERE status='Active'");
$active_budgets   = safeCount($conn,"SELECT COUNT(*)c FROM budgets WHERE status IN('approved','active')");
$total_payroll    = (float)(($r=$conn->query("SELECT COALESCE(SUM(net_salary),0) total FROM salary_structures WHERE status='active'"))&&$r?$r->fetch_assoc()['total']:0);

// ── Revenue by category ──
$tuition_revenue = $students_conn ? (float)(($r=$students_conn->query("SELECT COALESCE(SUM(amount_received),0) total FROM payments p JOIN student_invoices si ON p.invoice_id=si.id WHERE si.status NOT IN('cancelled','written_off') AND si.tuition_amount>0"))&&$r?$r->fetch_assoc()['total']:0) : 0;
$hostel_revenue = $students_conn ? (float)(($r=$students_conn->query("SELECT COALESCE(SUM(amount_received),0) total FROM payments p JOIN student_invoices si ON p.invoice_id=si.id WHERE si.accommodation_amount>0"))&&$r?$r->fetch_assoc()['total']:0) : 0;
$application_revenue = $website_conn ? (float)(($r=$website_conn->query("SELECT COALESCE(SUM(amount),0) total FROM donations WHERE purpose='application'"))&&$r?$r->fetch_assoc()['total']:0) : 0;
$other_revenue = $total_revenue - $tuition_revenue - $hostel_revenue - $application_revenue;
if ($other_revenue < 0) $other_revenue = 0;

// ── Expenses by category ──
$exp_categories = [];
$r=$conn->query("SELECT expense_category,COALESCE(SUM(amount),0) total,COUNT(*) cnt FROM expenses WHERE status IN('approved','paid') GROUP BY expense_category ORDER BY total DESC");
if($r) while($row=$r->fetch_assoc()) $exp_categories[]=$row;

// ── Load data ──
$expenses_list = []; $r=$conn->query("SELECT e.*,s.full_name requested_by_name,ap.full_name approved_by_name FROM expenses e LEFT JOIN staff s ON e.requested_by=s.id LEFT JOIN staff ap ON e.approved_by=ap.id ORDER BY e.created_at DESC LIMIT 100");
if($r) while($row=$r->fetch_assoc()) $expenses_list[]=$row;

$budgets_list = []; $r=$conn->query("SELECT b.*,s.full_name approved_by_name FROM budgets b LEFT JOIN staff s ON b.approved_by=s.id ORDER BY b.created_at DESC LIMIT 50");
if($r) while($row=$r->fetch_assoc()) $budgets_list[]=$row;

$budget_records_list = []; $r=$conn->query("SELECT * FROM budget_records ORDER BY created_at DESC LIMIT 50");
if($r) while($row=$r->fetch_assoc()) $budget_records_list[]=$row;

$payments_list = $students_conn ? [] : []; if($students_conn){ $r=$students_conn->query("SELECT p.*,s.full_name student_name FROM payments p LEFT JOIN students s ON p.student_id=s.id ORDER BY p.created_at DESC LIMIT 100");
if($r) while($row=$r->fetch_assoc()) $payments_list[]=$row; }

$fee_structures_list = []; $r=$conn->query("SELECT * FROM fee_structures WHERE is_active=1 ORDER BY fee_name");
if($r) while($row=$r->fetch_assoc()) $fee_structures_list[]=$row;

$payroll_list = []; $r=$conn->query("SELECT ss.*,st.full_name staff_name,st.position,st.department FROM salary_structures ss LEFT JOIN staff st ON ss.staff_id=st.id WHERE ss.status='active' ORDER BY st.full_name");
if($r) while($row=$r->fetch_assoc()) $payroll_list[]=$row;

$payroll_records_list = []; $r=$conn->query("SELECT pr.*,st.full_name staff_name FROM payroll_records pr LEFT JOIN staff st ON pr.staff_id=st.id ORDER BY pr.created_at DESC LIMIT 50");
if($r) while($row=$r->fetch_assoc()) $payroll_records_list[]=$row;

$recent_activities = []; $r=$conn->query("SELECT activity_description activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
if($r) while($row=$r->fetch_assoc()) $recent_activities[]=$row;

$user_role_id = 0; $ri = $conn->query("SELECT role_id FROM staff WHERE id = " . intval($user_id));
if ($ri) { $user_role_id = (int)$ri->fetch_assoc()['role_id']; }

// ── Report generation ──
$report = $_GET['report'] ?? '';
if ($report) {
    header('Content-Type: text/html; charset=utf-8');
    $from = $conn->real_escape_string($_GET['from'] ?? date('Y-m-01', strtotime('-1 month')));
    $to = $conn->real_escape_string($_GET['to'] ?? date('Y-m-d'));
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#f3f4f6}h2{color:#1f2937}.text-end{text-align:right}.text-center{text-align:center}.fw-bold{font-weight:700}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print"><button onclick="window.print()" style="padding:6px 16px;margin-bottom:12px">Print</button> <button onclick="window.close()" style="padding:6px 16px">Close</button> <button onclick="location.href=\'director-finance.php?report='.$report.'&from='.$from.'&to='.$to.'&pdf=1\'" style="padding:6px 16px">Export PDF</button></div>';

    if ($report === 'income_statement') {
        echo '<h2>Income Statement</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).'</p>';
        $rev = $students_conn ? (float)(($r=$students_conn->query("SELECT COALESCE(SUM(amount_received),0) total FROM payments WHERE status IN('verified','approved','completed') AND DATE(payment_date) BETWEEN '$from' AND '$to'"))&&$r?$r->fetch_assoc()['total']:0) : 0;
        $exp = (float)(($r=$conn->query("SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE status IN('approved','paid') AND DATE(expense_date) BETWEEN '$from' AND '$to'"))&&$r?$r->fetch_assoc()['total']:0);
        echo '<table><thead><tr><th>Item</th><th class="text-end">Amount (UGX)</th></tr></thead><tbody>';
        echo '<tr><td><strong>Revenue</strong></td><td class="text-end">'.number_format($rev,0).'</td></tr>';
        echo '<tr><td>Total Income</td><td class="text-end fw-bold">'.number_format($rev,0).'</td></tr>';
        echo '<tr><td>&nbsp;</td><td></td></tr>';
        echo '<tr><td><strong>Expenses</strong></td><td class="text-end">'.number_format($exp,0).'</td></tr>';
        $r2=$conn->query("SELECT expense_category,COALESCE(SUM(amount),0) total FROM expenses WHERE status IN('approved','paid') AND DATE(expense_date) BETWEEN '$from' AND '$to' GROUP BY expense_category");
        if($r2) while($row=$r2->fetch_assoc()){ echo '<tr><td>&nbsp;&nbsp;'.htmlspecialchars($row['expense_category']).'</td><td class="text-end">'.number_format($row['total'],0).'</td></tr>'; }
        echo '<tr><td>Total Expenses</td><td class="text-end fw-bold">'.number_format($exp,0).'</td></tr>';
        echo '<tr><td>&nbsp;</td><td></td></tr>';
        echo '<tr><td><strong>Net Income</strong></td><td class="text-end fw-bold" style="color:'.($rev-$exp>=0?'green':'red').'">'.number_format($rev-$exp,0).'</td></tr>';
        echo '</tbody></table>';
    } elseif ($report === 'trial_balance') {
        echo '<h2>Trial Balance</h2><p>As of '.htmlspecialchars($to).'</p>';
        echo '<table><thead><tr><th>Account</th><th class="text-end">Debit (UGX)</th><th class="text-end">Credit (UGX)</th></tr></thead><tbody>';
        $r=$conn->query("SELECT account_code,account_name,account_type,COALESCE(SUM(debit_amount),0) deb,COALESCE(SUM(credit_amount),0) cred FROM general_ledger WHERE entry_date<='$to' GROUP BY account_code,account_name,account_type ORDER BY account_type,account_code");
        $td=0;$tc=0; if($r) while($row=$r->fetch_assoc()){ $d=$row['deb'];$c=$row['cred'];$td+=$d;$tc+=$c; echo '<tr><td>'.htmlspecialchars($row['account_code'].' - '.$row['account_name']).'</td><td class="text-end">'.number_format($d,0).'</td><td class="text-end">'.number_format($c,0).'</td></tr>'; }
        echo '<tr><td><strong>Total</strong></td><td class="text-end fw-bold">'.number_format($td,0).'</td><td class="text-end fw-bold">'.number_format($tc,0).'</td></tr>';
        echo '</tbody></table>';
    } elseif ($report === 'cash_flow') {
        echo '<h2>Cash Flow Statement</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).'</p>';
        $in = $students_conn ? (float)(($r=$students_conn->query("SELECT COALESCE(SUM(amount_received),0) total FROM payments WHERE status IN('verified','approved','completed') AND DATE(payment_date) BETWEEN '$from' AND '$to'"))&&$r?$r->fetch_assoc()['total']:0) : 0;
        $out = (float)(($r=$conn->query("SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE status='paid' AND DATE(expense_date) BETWEEN '$from' AND '$to'"))&&$r?$r->fetch_assoc()['total']:0);
        echo '<table><thead><tr><th>Item</th><th class="text-end">Amount (UGX)</th></tr></thead><tbody>';
        echo '<tr><td><strong>Cash Inflows</strong></td><td></td></tr>';
        echo '<tr><td>&nbsp;&nbsp;Payments Received</td><td class="text-end">'.number_format($in,0).'</td></tr>';
        echo '<tr><td>Total Inflows</td><td class="text-end fw-bold">'.number_format($in,0).'</td></tr>';
        echo '<tr><td>&nbsp;</td><td></td></tr>';
        echo '<tr><td><strong>Cash Outflows</strong></td><td></td></tr>';
        $r2=$conn->query("SELECT expense_category,COALESCE(SUM(amount),0) total FROM expenses WHERE status='paid' AND DATE(expense_date) BETWEEN '$from' AND '$to' GROUP BY expense_category");
        if($r2) while($row=$r2->fetch_assoc()){ echo '<tr><td>&nbsp;&nbsp;'.htmlspecialchars($row['expense_category']).'</td><td class="text-end">'.number_format($row['total'],0).'</td></tr>'; }
        echo '<tr><td>Total Outflows</td><td class="text-end fw-bold">'.number_format($out,0).'</td></tr>';
        echo '<tr><td>&nbsp;</td><td></td></tr>';
        echo '<tr><td><strong>Net Cash Flow</strong></td><td class="text-end fw-bold" style="color:'.($in-$out>=0?'green':'red').'">'.number_format($in-$out,0).'</td></tr>';
        echo '</tbody></table>';
    } elseif ($report === 'expense_report') {
        $cat = $conn->real_escape_string($_GET['category'] ?? '');
        echo '<h2>Expense Report</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).'</p>';
        if($cat) echo '<p>Category: '.htmlspecialchars($cat).'</p>';
        $wh = "DATE(e.expense_date) BETWEEN '$from' AND '$to'";
        if($cat) $wh .= " AND e.expense_category='".$conn->real_escape_string($cat)."'";
        $r=$conn->query("SELECT e.*,s.full_name requested_by_name FROM expenses e LEFT JOIN staff s ON e.requested_by=s.id WHERE $wh ORDER BY e.expense_date DESC");
        echo '<table><thead><tr><th>ID</th><th>Category</th><th>Description</th><th class="text-end">Amount</th><th>Date</th><th>Status</th><th>Requested By</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['expense_id']).'</td><td>'.htmlspecialchars($row['expense_category']).'</td><td>'.htmlspecialchars($row['description']).'</td><td class="text-end">'.number_format($row['amount'],0).'</td><td>'.$row['expense_date'].'</td><td>'.$row['status'].'</td><td>'.htmlspecialchars($row['requested_by_name']??'-').'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'fee_collection') {
        echo '<h2>Fee Collection Report</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).'</p>';
        $r=$students_conn->query("SELECT p.payment_reference,s.full_name student_name,s.student_number,s.course,p.amount_received,p.payment_method,p.payment_date,p.status FROM payments p LEFT JOIN students s ON p.student_id=s.id WHERE DATE(p.payment_date) BETWEEN '$from' AND '$to' ORDER BY p.payment_date DESC");
        echo '<table><thead><tr><th>Receipt</th><th>Student</th><th>Program</th><th class="text-end">Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        $tt=0; if($r) while($row=$r->fetch_assoc()){ $tt+=$row['amount_received']; echo '<tr><td>'.htmlspecialchars($row['payment_reference']).'</td><td>'.htmlspecialchars($row['student_name']??$row['student_number']).'</td><td>'.htmlspecialchars($row['course']??'-').'</td><td class="text-end">'.number_format($row['amount_received'],0).'</td><td>'.htmlspecialchars($row['payment_method']).'</td><td>'.$row['payment_date'].'</td><td>'.$row['status'].'</td></tr>'; }
        echo '<tr><td colspan="3"><strong>Total</strong></td><td class="text-end fw-bold">'.number_format($tt,0).'</td><td colspan="3"></td></tr>';
        echo '</tbody></table>';
    } elseif ($report === 'tax_report') {
        echo '<h2>URA Tax Report</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).'</p>';
        $rev = $students_conn ? (float)(($r=$students_conn->query("SELECT COALESCE(SUM(amount_received),0) total FROM payments WHERE status IN('verified','approved','completed') AND DATE(payment_date) BETWEEN '$from' AND '$to'"))&&$r?$r->fetch_assoc()['total']:0) : 0;
        $exp = (float)(($r=$conn->query("SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE status IN('approved','paid') AND DATE(expense_date) BETWEEN '$from' AND '$to'"))&&$r?$r->fetch_assoc()['total']:0);
        $taxable = $rev - $exp; if($taxable<0) $taxable=0;
        $vat18 = $rev * 0.18;
        $wit = $exp * 0.06;
        echo '<table><thead><tr><th>Item</th><th class="text-end">Amount (UGX)</th></tr></thead><tbody>';
        echo '<tr><td>Gross Revenue</td><td class="text-end">'.number_format($rev,0).'</td></tr>';
        echo '<tr><td>Allowable Expenses</td><td class="text-end">'.number_format($exp,0).'</td></tr>';
        echo '<tr><td>Taxable Income</td><td class="text-end">'.number_format($taxable,0).'</td></tr>';
        echo '<tr><td>Estimated VAT (18%)</td><td class="text-end">'.number_format($vat18,0).'</td></tr>';
        echo '<tr><td>Withholding Tax (6%)</td><td class="text-end">'.number_format($wit,0).'</td></tr>';
        echo '<tr><td><strong>Estimated Tax Liability</strong></td><td class="text-end fw-bold">'.number_format($taxable*0.3,0).'</td></tr>';
        echo '</tbody></table>';
    }
    echo '</body></html>'; exit;
}

// ── AJAX endpoints ──
$ajax = $_GET['ajax'] ?? '';
$ajaxSid = intval($_GET['student_id'] ?? 0);
if ($ajax === 'student_fees' && $ajaxSid > 0) {
    header('Content-Type: application/json');
    $info=[];$inv=[];$pay=[];
    if($students_conn){ $r=$students_conn->query("SELECT * FROM students WHERE id=$ajaxSid"); if($r) $info=$r->fetch_assoc(); }
    if($students_conn){ $r=$students_conn->query("SELECT * FROM student_invoices WHERE student_id=$ajaxSid ORDER BY created_at DESC"); if($r) while($row=$r->fetch_assoc()) $inv[]=$row; }
    if($students_conn){ $r=$students_conn->query("SELECT * FROM payments WHERE student_id=$ajaxSid ORDER BY payment_date DESC"); if($r) while($row=$r->fetch_assoc()) $pay[]=$row; }
    echo json_encode(['info'=>$info,'invoices'=>$inv,'payments'=>$pay]); exit;
}
if ($ajax === 'student_search') {
    header('Content-Type: application/json');
    $q = $students_conn->real_escape_string($_GET['q']??'');
    $data=[];
    if($students_conn && $q){ $r=$students_conn->query("SELECT id,student_number,registration_number,full_name,course,phone,status FROM students WHERE full_name LIKE '%$q%' OR student_number LIKE '%$q%' OR registration_number LIKE '%$q%' LIMIT 20");
    if($r) while($row=$r->fetch_assoc()) $data[]=$row; }
    echo json_encode($data); exit;
}
if ($ajax === 'expense_detail') {
    header('Content-Type: application/json');
    $eid = intval($_GET['expense_id']??0);
    $data=[];$r=$conn->query("SELECT e.*,s.full_name requested_by_name,ap.full_name approved_by_name FROM expenses e LEFT JOIN staff s ON e.requested_by=s.id LEFT JOIN staff ap ON e.approved_by=ap.id WHERE e.id=$eid");
    if($r) $data=$r->fetch_assoc();
    echo json_encode($data); exit;
}
if ($ajax === 'budget_detail') {
    header('Content-Type: application/json');
    $bid = intval($_GET['budget_id']??0);
    $data=[];$r=$conn->query("SELECT * FROM budgets WHERE id=$bid");
    if($r) $data=$r->fetch_assoc();
    $lines=[];$r2=$conn->query("SELECT * FROM budget_lines WHERE budget_id=$bid");
    if($r2) while($row=$r2->fetch_assoc()) $lines[]=$row;
    echo json_encode(['budget'=>$data,'lines'=>$lines]); exit;
}

// ── POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_expense') {
        $cat=$conn->real_escape_string($_POST['category']??'');
        $desc=$conn->real_escape_string($_POST['description']??'');
        $amt=floatval($_POST['amount']??0);
        $dt=$_POST['expense_date']??date('Y-m-d');
        $supplier=$conn->real_escape_string($_POST['supplier']??'');
        $dept=$conn->real_escape_string($_POST['department']??'');
        $pm=$conn->real_escape_string($_POST['payment_method']??'cash');
        $eid='EXP-'.date('Ymd').'-'.mt_rand(1000,9999);
        $conn->query("INSERT INTO expenses (expense_id,description,expense_category,department,amount,expense_date,payment_method,status,requested_by,notes) VALUES ('$eid','$desc','$cat','$dept',$amt,'$dt','$pm','pending'," . intval($user_id) . ",'$supplier')");
        if($conn->affected_rows>0){ $_SESSION['success']="Expense $eid created."; $conn->query("INSERT INTO staff_activity_log (staff_id,activity_description,activity_type) VALUES (" . intval($user_id) . ",'Created expense $eid: $desc','create')"); }
        else { $_SESSION['error']='Failed: '.$conn->error; }
        header("Location: director-finance.php#expenses"); exit;
    }

    if ($action === 'edit_expense') {
        $did=intval($_POST['expense_id']??0);
        $cat=$conn->real_escape_string($_POST['category']??'');
        $desc=$conn->real_escape_string($_POST['description']??'');
        $amt=floatval($_POST['amount']??0);
        $dt=$_POST['expense_date']??date('Y-m-d');
        $supplier=$conn->real_escape_string($_POST['supplier']??'');
        $conn->query("UPDATE expenses SET expense_category='$cat',description='$desc',amount=$amt,expense_date='$dt',notes='$supplier' WHERE id=" . intval($did) . " AND status='pending'");
        $_SESSION['success']='Expense updated.';
        header("Location: director-finance.php#expenses"); exit;
    }

    if ($action === 'approve_expense') {
        $did=intval($_POST['expense_id']??0);
        $conn->query("UPDATE expenses SET status='approved',approved_by=" . intval($user_id) . ",approval_date=NOW() WHERE id=" . intval($did) . " AND status='pending'");
        if($conn->affected_rows>0){ $_SESSION['success']='Expense approved.'; $conn->query("INSERT INTO staff_activity_log (staff_id,activity_description,activity_type) VALUES (" . intval($user_id) . ",'Approved expense #$did','approve')"); }
        header("Location: director-finance.php#expenses"); exit;
    }

    if ($action === 'reject_expense') {
        $did=intval($_POST['expense_id']??0);
        $conn->query("UPDATE expenses SET status='rejected',approved_by=" . intval($user_id) . ",approval_date=NOW() WHERE id=" . intval($did) . " AND status='pending'");
        $_SESSION['success']='Expense rejected.';
        header("Location: director-finance.php#expenses"); exit;
    }

    if ($action === 'create_budget') {
        $code=$conn->real_escape_string($_POST['budget_code']??'BGT-'.date('Y').'-'.mt_rand(100,999));
        $name=$conn->real_escape_string($_POST['budget_name']??'');
        $cat=$conn->real_escape_string($_POST['budget_category']??'Operations');
        $dept=$conn->real_escape_string($_POST['department']??'');
        $fy=$conn->real_escape_string($_POST['fiscal_year']??date('Y'));
        $amt=floatval($_POST['allocated_amount']??0);
        $desc=$conn->real_escape_string($_POST['description']??'');
        $conn->query("INSERT INTO budget_records (budget_code,budget_name,budget_category,department,fiscal_year,allocated_amount,spent_amount,status,created_by) VALUES ('$code','$name','$cat','$dept','$fy',$amt,0,'Draft'," . intval($user_id) . ")");
        if($conn->affected_rows>0){ $_SESSION['success']="Budget $code created."; $conn->query("INSERT INTO staff_activity_log (staff_id,activity_description,activity_type) VALUES (" . intval($user_id) . ",'Created budget $code: $name','create')"); }
        else { $_SESSION['error']='Failed: '.$conn->error; }
        header("Location: director-finance.php#budget"); exit;
    }

    if ($action === 'approve_budget') {
        $bid=intval($_POST['budget_id']??0);
        $conn->query("UPDATE budget_records SET status='Approved',approved_by=" . intval($user_id) . " WHERE id=" . intval($bid));
        $_SESSION['success']='Budget approved.';
        header("Location: director-finance.php#budget"); exit;
    }

    if ($action === 'record_payment') {
        $sid=intval($_POST['student_id']??0);
        $amt=floatval($_POST['amount']??0);
        $pm=$conn->real_escape_string($_POST['payment_method']??'cash');
        $ref='RCT-'.date('Ymd').'-'.mt_rand(1000,9999);
        if($students_conn && $sid>0 && $amt>0){
            $students_conn->query("INSERT INTO payments (payment_reference,student_id,student_index_number,amount_received,payment_method,payment_date,status,processed_by) VALUES ('$ref'," . intval($sid) . ",'',$amt,'$pm',CURDATE(),'pending'," . intval($user_id) . ")");
            if($students_conn->affected_rows>0){ $_SESSION['success']="Payment $ref recorded."; $conn->query("INSERT INTO staff_activity_log (staff_id,activity_description,activity_type) VALUES (" . intval($user_id) . ",'Recorded payment $ref','create')"); }
            else { $_SESSION['error']='Failed: '.$students_conn->error; }
        } else { $_SESSION['error']='Student and amount required.'; }
        header("Location: director-finance.php#payments"); exit;
    }

    if ($action === 'approve_payment') {
        $pid=intval($_POST['payment_id']??0);
        if($students_conn){ $students_conn->query("UPDATE payments SET status='approved',verified_by=" . intval($user_id) . " WHERE id=" . intval($pid) . " AND status='pending'"); if($students_conn->affected_rows>0) $_SESSION['success']='Payment approved.'; }
        header("Location: director-finance.php#payments"); exit;
    }

    if ($action === 'reject_payment') {
        $pid=intval($_POST['payment_id']??0);
        if($students_conn){ $students_conn->query("UPDATE payments SET status='rejected' WHERE id=" . intval($pid)); $_SESSION['success']='Payment rejected.'; }
        header("Location: director-finance.php#payments"); exit;
    }

    header("Location: director-finance.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.btn-outline-purple { color:#8b5cf6; border-color:#8b5cf6; }
.btn-outline-purple:hover { color:#fff; background:#8b5cf6; border-color:#8b5cf6; }
.modal-content { max-height:85vh; overflow-y:auto; }
.finance-table { font-size:0.875rem; }
.finance-table th { background:#f8fafc; position:sticky; top:0; z-index:1; }
.stats-grid { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
@media (max-width:768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Director Finance Dashboard</h1>
                <p>Financial Affairs &amp; Strategic Management</p>
            </div>
            <div class="header-right">
                <div class="date-time">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo date('l, F j, Y'); ?></span>
                </div>
                <a href="../store_request.php" class="btn btn-sm btn-outline-primary ms-2"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                <a href="../news.php" class="btn btn-sm btn-outline-primary ms-1"><i class="fas fa-newspaper me-1"></i>News</a>
                <a href="../student-directory.php" class="btn btn-sm btn-outline-info ms-2"><i class="fas fa-address-book me-1"></i>Directory</a>
                <a href="../index.php" class="btn btn-sm btn-outline-secondary ms-1"><i class="fas fa-home"></i></a>
                <div class="user-menu">
                    <img src="<?= $profileImageUrl ?? '../images/username.png' ?>" alt="User" class="user-avatar">
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>
        </div>

        <?php if(!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mx-3 mt-2 py-2"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); endif; ?>
        <?php if(!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mx-3 mt-2 py-2"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="dashboard-content">
            <!-- Financial Overview -->
            <section id="overview" class="content-section dashboard-section active" data-section="overview">
                <h2>Financial Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
                        <div class="stat-content"><h3>UGX <?php echo number_format($total_revenue); ?></h3><p>Total Revenue</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:linear-gradient(135deg,#dc2626,#ef4444)"><i class="fas fa-arrow-down"></i></div>
                        <div class="stat-content"><h3>UGX <?php echo number_format($total_expenses); ?></h3><p>Total Expenses</p></div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fas fa-piggy-bank"></i></div>
                        <div class="stat-content"><h3>UGX <?php echo number_format($total_revenue - $total_expenses); ?></h3><p>Net Balance</p></div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-content"><h3>UGX <?php echo number_format($outstanding_fees); ?></h3><p>Outstanding Fees</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa)"><i class="fas fa-clock"></i></div>
                        <div class="stat-content"><h3><?= $pending_expenses + $pending_approvals_count ?></h3><p>Pending Approvals</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:linear-gradient(135deg,#0891b2,#22d3ee)"><i class="fas fa-wallet"></i></div>
                        <div class="stat-content"><h3>UGX <?php echo number_format($total_payroll); ?></h3><p>Monthly Payroll</p></div>
                    </div>
                </div>
            </section>

            <!-- Official Duties -->
            <section id="duties" class="content-section dashboard-section" data-section="duties">
                <h2><i class="fas fa-tasks me-2"></i>Official Duties &amp; Responsibilities</h2>
                <?php renderOfficialDuties($user_role_id, $conn); ?>
            </section>

            <!-- Quick Actions -->
            <section class="content-section">
                <h2><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h2>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <a href="../dashboards/school-bursar.php" class="btn btn-outline-success btn-sm"><i class="fas fa-money-bill me-1"></i>Bursar Dashboard</a>
                    <a href="../dashboards/bursar-payroll.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-wallet me-1"></i>Payroll</a>
                    <a href="../dashboards/budget-management.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-chart-line me-1"></i>Budget Management</a>
                    <a href="../dashboards/fee-structure.php" class="btn btn-outline-info btn-sm"><i class="fas fa-file-invoice me-1"></i>Fee Structure</a>
                    <a href="../ura_reporting.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-file-invoice-dollar me-1"></i>URA Tax Reports</a>
                    <a href="../dashboards/expenditure-tracking.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-receipt me-1"></i>Expenditure Tracking</a>
                    <a href="../dashboards/inventory-reports.php" class="btn btn-outline-info btn-sm"><i class="fas fa-boxes me-1"></i>Inventory Reports</a>
                    <a href="../dashboards/director-general.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-crown me-1"></i>Director General</a>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.open('director-finance.php?report=income_statement','_blank')"><i class="fas fa-print me-1"></i>Print Income Statement</button>
                </div>
            </section>

            <!-- Revenue Management -->
            <section id="revenue" class="content-section dashboard-section" data-section="revenue">
                <h2><i class="fas fa-money-bill-wave me-2"></i>Revenue Management</h2>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <input type="text" id="revSearch" class="form-control form-control-sm" style="width:200px" placeholder="Search payments..." onkeyup="filterRevenue()">
                    <input type="date" id="revFrom" class="form-control form-control-sm" style="width:160px" value="<?= date('Y-m-01') ?>">
                    <input type="date" id="revTo" class="form-control form-control-sm" style="width:160px" value="<?= date('Y-m-t') ?>">
                    <button class="btn btn-sm btn-outline-primary" onclick="filterRevenueByDate()"><i class="fas fa-filter"></i> Filter</button>
                    <button class="btn btn-sm btn-outline-success" onclick="window.open('director-finance.php?report=fee_collection&from='+document.getElementById('revFrom').value+'&to='+document.getElementById('revTo').value,'_blank')"><i class="fas fa-print"></i> Print</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.open('director-finance.php?report=income_statement&from='+document.getElementById('revFrom').value+'&to='+document.getElementById('revTo').value+'&pdf=1','_blank')"><i class="fas fa-file-pdf"></i> Export PDF</button>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-success">UGX <?= number_format($tuition_revenue) ?></strong>
                            <small class="text-muted">Tuition Fees</small>
                            <div class="progress mt-1" style="height:4px"><div class="progress-bar bg-success" style="width:<?= $total_revenue>0?($tuition_revenue/$total_revenue)*100:0 ?>%"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-info">UGX <?= number_format($hostel_revenue) ?></strong>
                            <small class="text-muted">Hostel Fees</small>
                            <div class="progress mt-1" style="height:4px"><div class="progress-bar bg-info" style="width:<?= $total_revenue>0?($hostel_revenue/$total_revenue)*100:0 ?>%"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-warning">UGX <?= number_format($application_revenue) ?></strong>
                            <small class="text-muted">Application Fees</small>
                            <div class="progress mt-1" style="height:4px"><div class="progress-bar bg-warning" style="width:<?= $total_revenue>0?($application_revenue/$total_revenue)*100:0 ?>%"></div></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-secondary">UGX <?= number_format($other_revenue) ?></strong>
                            <small class="text-muted">Other Income</small>
                            <div class="progress mt-1" style="height:4px"><div class="progress-bar bg-secondary" style="width:<?= $total_revenue>0?($other_revenue/$total_revenue)*100:0 ?>%"></div></div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                    <table class="table table-sm finance-table" id="revTable">
                        <thead><tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if(!empty($payments_list)): foreach($payments_list as $p): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($p['payment_reference']) ?></code></td>
                            <td><?= htmlspecialchars($p['student_name']??'-') ?></td>
                            <td class="fw-bold">UGX <?= number_format($p['amount_received'],0) ?></td>
                            <td><?= htmlspecialchars($p['payment_method']) ?></td>
                            <td><?= $p['payment_date'] ?></td>
                            <td><span class="badge bg-<?= $p['status']==='approved'?'success':($p['status']==='pending'?'warning':($p['status']==='rejected'?'danger':'info')) ?>"><?= $p['status'] ?></span></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center text-muted">No payment records yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Expense Management -->
            <section id="expenses" class="content-section dashboard-section" data-section="expenses">
                <h2><i class="fas fa-receipt me-2"></i>Expense Management</h2>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal"><i class="fas fa-plus me-1"></i>Add Expense</button>
                    <input type="text" id="expSearch" class="form-control form-control-sm" style="width:200px" placeholder="Search expenses..." onkeyup="filterExpenses()">
                </div>
                <?php if(!empty($exp_categories)): ?>
                <div class="row g-2 mb-3">
                    <?php foreach($exp_categories as $ec): ?>
                    <div class="col-md-2 col-4">
                        <div class="card card-body text-center py-2">
                            <strong class="small"><?= htmlspecialchars($ec['expense_category']) ?></strong>
                            <span class="fw-bold">UGX <?= number_format($ec['total'],0) ?></span>
                            <small class="text-muted"><?= $ec['cnt'] ?> items</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                    <table class="table table-sm finance-table" id="expTable">
                        <thead><tr><th>ID</th><th>Category</th><th>Description</th><th>Amount</th><th>Date</th><th>Supplier</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php if(!empty($expenses_list)): foreach($expenses_list as $e): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($e['expense_id']) ?></code></td>
                            <td><?= htmlspecialchars($e['expense_category']) ?></td>
                            <td><?= htmlspecialchars($e['description']) ?></td>
                            <td class="fw-bold">UGX <?= number_format($e['amount'],0) ?></td>
                            <td><?= $e['expense_date'] ?></td>
                            <td><?= htmlspecialchars($e['notes']??'-') ?></td>
                            <td><span class="badge bg-<?= $e['status']==='approved'?'success':($e['status']==='pending'?'warning':($e['status']==='rejected'?'danger':'secondary')) ?>"><?= $e['status'] ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info" onclick="viewExpense(<?= $e['id'] ?>)"><i class="fas fa-eye"></i></button>
                                <?php if($e['status']==='pending'): ?>
                                <button class="btn btn-sm btn-outline-success" onclick="confirmAction('approve_expense',<?= $e['id'] ?>,'Approve this expense?')"><i class="fas fa-check"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmAction('reject_expense',<?= $e['id'] ?>,'Reject this expense?')"><i class="fas fa-times"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No expenses recorded yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Student Fees -->
            <section id="fees" class="content-section dashboard-section" data-section="fees">
                <h2><i class="fas fa-user-graduate me-2"></i>Student Fees Management</h2>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" id="feeSearch" class="form-control form-control-sm" placeholder="Search by name, reg no, program..." onkeyup="searchStudentFees()">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-primary" onclick="searchStudentFees()"><i class="fas fa-search"></i> Search</button>
                    </div>
                </div>
                <div id="feeSearchResults" class="mb-3"></div>
                <div id="feeStudentDetail" style="display:none">
                    <div class="card card-body mb-3" id="feeStudentInfo"></div>
                    <ul class="nav nav-tabs mb-2 small" id="feeTabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#feeInvoices">Invoices</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#feePayments">Payment History</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="feeInvoices"><div class="table-responsive"><table class="table table-sm finance-table" id="feeInvTable"><thead><tr><th>Invoice</th><th>Type</th><th>Total</th><th>Paid</th><th>Balance</th><th>Due</th><th>Status</th></tr></thead><tbody></tbody></table></div></div>
                        <div class="tab-pane fade" id="feePayments"><div class="table-responsive"><table class="table table-sm finance-table" id="feePayTable"><thead><tr><th>Receipt</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody></tbody></table></div></div>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="generateStatement()"><i class="fas fa-file-invoice"></i> Generate Statement</button>
                        <button class="btn btn-sm btn-outline-success" onclick="window.open('director-finance.php?report=fee_collection','_blank')"><i class="fas fa-print"></i> Print Receipt</button>
                        <button class="btn btn-sm btn-outline-info" onclick="window.open('director-finance.php?report=fee_collection&pdf=1','_blank')"><i class="fas fa-file-pdf"></i> Download PDF</button>
                    </div>
                </div>
            </section>

            <!-- Payment Management -->
            <section id="payments" class="content-section dashboard-section" data-section="payments">
                <h2><i class="fas fa-money-check me-2"></i>Payment Management</h2>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-plus me-1"></i>Record Payment</button>
                    <input type="text" id="paySearch" class="form-control form-control-sm" style="width:200px" placeholder="Search payments..." onkeyup="filterPayments()">
                    <button class="btn btn-sm btn-outline-success" onclick="window.open('director-finance.php?report=fee_collection','_blank')"><i class="fas fa-print"></i> Print</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.open('director-finance.php?report=fee_collection&pdf=1','_blank')"><i class="fas fa-file-pdf"></i> Export</button>
                </div>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                    <table class="table table-sm finance-table" id="payTable">
                        <thead><tr><th>Receipt No</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Recorded By</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php if(!empty($payments_list)): foreach($payments_list as $p):
                            $recorder = '-';
                            if($p['processed_by']>0){ $rr=$conn->query("SELECT full_name FROM staff WHERE id=".(int)$p['processed_by']); if($rr) $recorder=($rr->fetch_assoc()['full_name']??'-'); }
                        ?>
                        <tr>
                            <td><code><?= htmlspecialchars($p['payment_reference']) ?></code></td>
                            <td><?= htmlspecialchars($p['student_name']??'-') ?></td>
                            <td class="fw-bold">UGX <?= number_format($p['amount_received'],0) ?></td>
                            <td><?= htmlspecialchars($p['payment_method']) ?></td>
                            <td><?= $p['payment_date'] ?></td>
                            <td><?= $recorder ?></td>
                            <td><span class="badge bg-<?= $p['status']==='approved'?'success':($p['status']==='pending'?'warning':'danger') ?>"><?= $p['status'] ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info" onclick="alert('Receipt: <?= htmlspecialchars($p['payment_reference']) ?>\nAmount: UGX <?= number_format($p['amount_received'],0) ?>\nMethod: <?= htmlspecialchars($p['payment_method']) ?>\nDate: <?= $p['payment_date'] ?>')"><i class="fas fa-eye"></i></button>
                                <?php if($p['status']==='pending'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="approve_payment">
                                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this payment?')"><i class="fas fa-check"></i></button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="reject_payment">
                                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this payment?')"><i class="fas fa-times"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No payments recorded yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Budget Management -->
            <section id="budget" class="content-section dashboard-section" data-section="budget">
                <h2><i class="fas fa-calculator me-2"></i>Budget Management</h2>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#budgetModal"><i class="fas fa-plus me-1"></i>Create Budget</button>
                </div>
                <?php
                $total_budget_alloc = 0; $total_budget_spent = 0;
                foreach($budget_records_list as $br){ $total_budget_alloc += $br['allocated_amount']; $total_budget_spent += $br['spent_amount']; }
                ?>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-primary fs-5">UGX <?= number_format($total_budget_alloc) ?></strong>
                            <small class="text-muted">Total Budget Allocation</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-warning fs-5">UGX <?= number_format($total_budget_spent) ?></strong>
                            <small class="text-muted">Budget Utilized</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-success fs-5">UGX <?= number_format($total_budget_alloc - $total_budget_spent) ?></strong>
                            <small class="text-muted">Remaining</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="fs-5"><?= $total_budget_alloc>0 ? round(($total_budget_spent/$total_budget_alloc)*100,1) : 0 ?>%</strong>
                            <small class="text-muted">Utilization Rate</small>
                            <div class="progress mt-1" style="height:6px"><div class="progress-bar" style="width:<?= $total_budget_alloc>0 ? ($total_budget_spent/$total_budget_alloc)*100 : 0 ?>%"></div></div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                    <table class="table table-sm finance-table">
                        <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Department</th><th>Allocated</th><th>Spent</th><th>Remaining</th><th>Util%</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php if(!empty($budget_records_list)): foreach($budget_records_list as $b):
                            $rem = $b['allocated_amount'] - $b['spent_amount'];
                            $util = $b['allocated_amount']>0 ? round(($b['spent_amount']/$b['allocated_amount'])*100,1) : 0;
                        ?>
                        <tr>
                            <td><code><?= htmlspecialchars($b['budget_code']) ?></code></td>
                            <td><?= htmlspecialchars($b['budget_name']) ?></td>
                            <td><?= htmlspecialchars($b['budget_category']) ?></td>
                            <td><?= htmlspecialchars($b['department']??'-') ?></td>
                            <td class="fw-bold">UGX <?= number_format($b['allocated_amount'],0) ?></td>
                            <td>UGX <?= number_format($b['spent_amount'],0) ?></td>
                            <td>UGX <?= number_format($rem,0) ?></td>
                            <td><?= $util ?>%</td>
                            <td><span class="badge bg-<?= $b['status']==='Approved'?'success':($b['status']==='Active'?'info':'warning') ?>"><?= $b['status'] ?></span></td>
                            <td>
                                <?php if($b['status']==='Draft'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="approve_budget">
                                    <input type="hidden" name="budget_id" value="<?= $b['id'] ?>">
                                    <button class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this budget?')"><i class="fas fa-check"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="10" class="text-center text-muted">No budgets created yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Payroll Management -->
            <section id="payroll" class="content-section dashboard-section" data-section="payroll">
                <h2><i class="fas fa-wallet me-2"></i>Payroll Management</h2>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-primary fs-5"><?= count($payroll_list) ?></strong>
                            <small class="text-muted">Active Staff on Payroll</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-success fs-5">UGX <?= number_format($total_payroll) ?></strong>
                            <small class="text-muted">Total Monthly Gross</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-warning fs-5">UGX <?= number_format(array_sum(array_map(function($s){return $s['total_allowances']??0;},$payroll_list))) ?></strong>
                            <small class="text-muted">Total Allowances</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-body text-center py-2">
                            <strong class="text-danger fs-5">UGX <?= number_format(array_sum(array_map(function($s){return $s['total_deductions']??0;},$payroll_list))) ?></strong>
                            <small class="text-muted">Total Deductions</small>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                    <table class="table table-sm finance-table">
                        <thead><tr><th>Staff</th><th>Position</th><th>Department</th><th>Basic Salary</th><th>Allowances</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if(!empty($payroll_list)): foreach($payroll_list as $pl): ?>
                        <tr>
                            <td><?= htmlspecialchars($pl['staff_name']??'-') ?></td>
                            <td><?= htmlspecialchars($pl['position']??'-') ?></td>
                            <td><?= htmlspecialchars($pl['department']??'-') ?></td>
                            <td>UGX <?= number_format($pl['basic_salary'],0) ?></td>
                            <td>UGX <?= number_format($pl['total_allowances']??0,0) ?></td>
                            <td class="fw-bold">UGX <?= number_format($pl['gross_salary']??$pl['basic_salary'],0) ?></td>
                            <td>UGX <?= number_format($pl['total_deductions']??0,0) ?></td>
                            <td class="fw-bold text-success">UGX <?= number_format($pl['net_salary']??$pl['basic_salary'],0) ?></td>
                            <td><span class="badge bg-<?= $pl['status']==='active'?'success':'secondary' ?>"><?= $pl['status'] ?></span></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="9" class="text-center text-muted">No payroll records yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <h4 class="mt-3"><i class="fas fa-history me-2"></i>Payroll History</h4>
                <div class="table-responsive" style="max-height:250px;overflow-y:auto">
                    <table class="table table-sm finance-table">
                        <thead><tr><th>Payslip</th><th>Staff</th><th>Month</th><th>Year</th><th>Gross</th><th>Net</th><th>Processed</th></tr></thead>
                        <tbody>
                        <?php if(!empty($payroll_records_list)): foreach($payroll_records_list as $pr): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($pr['payslip_number']??'-') ?></code></td>
                            <td><?= htmlspecialchars($pr['staff_name']??'-') ?></td>
                            <td><?= htmlspecialchars($pr['month']) ?></td>
                            <td><?= htmlspecialchars($pr['year']) ?></td>
                            <td>UGX <?= number_format($pr['gross_salary'],0) ?></td>
                            <td class="fw-bold">UGX <?= number_format($pr['net_salary'],0) ?></td>
                            <td><?= $pr['processing_date'] ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No payroll history yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Financial Reports -->
            <section id="reports" class="content-section dashboard-section" data-section="reports">
                <h2><i class="fas fa-chart-bar me-2"></i>Financial Reports</h2>
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">From</label>
                        <input type="date" id="rptFrom" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">To</label>
                        <input type="date" id="rptTo" class="form-control form-control-sm" value="<?= date('Y-m-t') ?>">
                    </div>
                </div>
                <div class="reports-grid">
                    <div class="report-card">
                        <div class="report-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <h3>Income Statement</h3>
                        <p>Profit and loss for a period</p>
                        <button class="btn btn-primary" onclick="window.open('director-finance.php?report=income_statement&from='+document.getElementById('rptFrom').value+'&to='+document.getElementById('rptTo').value,'_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon"><i class="fas fa-balance-scale"></i></div>
                        <h3>Trial Balance</h3>
                        <p>Complete trial balance report</p>
                        <button class="btn btn-primary" onclick="window.open('director-finance.php?report=trial_balance&to='+document.getElementById('rptTo').value,'_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon"><i class="fas fa-money-check"></i></div>
                        <h3>Cash Flow Statement</h3>
                        <p>Cash inflows and outflows</p>
                        <button class="btn btn-primary" onclick="window.open('director-finance.php?report=cash_flow&from='+document.getElementById('rptFrom').value+'&to='+document.getElementById('rptTo').value,'_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon"><i class="fas fa-receipt"></i></div>
                        <h3>Expense Report</h3>
                        <p>Detailed expense breakdown</p>
                        <button class="btn btn-primary" onclick="window.open('director-finance.php?report=expense_report&from='+document.getElementById('rptFrom').value+'&to='+document.getElementById('rptTo').value,'_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>Fee Collection Report</h3>
                        <p>Student fee payments collected</p>
                        <button class="btn btn-primary" onclick="window.open('director-finance.php?report=fee_collection&from='+document.getElementById('rptFrom').value+'&to='+document.getElementById('rptTo').value,'_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon"><i class="fas fa-file-contract"></i></div>
                        <h3>URA Tax Report</h3>
                        <p>Tax compliance for URA</p>
                        <button class="btn btn-primary" onclick="window.open('director-finance.php?report=tax_report&from='+document.getElementById('rptFrom').value+'&to='+document.getElementById('rptTo').value,'_blank')">Generate</button>
                    </div>
                </div>
            </section>

            <!-- News Management -->
            <section id="news" class="content-section dashboard-section" data-section="news">
                <h2><i class="fas fa-newspaper me-2"></i>News &amp; Announcements</h2>
                <?php renderNewsWidget($conn, $website_conn, $ctx['user']['id'] ?? 0, $user_name, $_SESSION['role'] ?? 'Director Finance', 5); ?>
            </section>

            <!-- Recent Activities -->
            <section id="activity" class="content-section dashboard-section" data-section="activity">
                <h2><i class="fas fa-history me-2"></i>Recent Financial Activities</h2>
                <div class="activities-list">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="activity-content flex-grow-1">
                            <strong><?php echo htmlspecialchars($activity['activity'] ?? 'Activity'); ?></strong>
                            <small class="text-muted d-block"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                        </div>
                    </div>
                    <?php endforeach; if(empty($recent_activities)): ?>
                    <p class="text-muted">No recent activities.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Approvals Section -->
            <section id="approvals" class="content-section dashboard-section" data-section="approvals">
                <h2><i class="fas fa-check-double me-2 text-primary"></i>Finance Approvals</h2>
                <p class="text-muted small mb-3">Review and manage pending financial approval requests.</p>
                <?php
                $finApprovals = getPendingApprovals($conn, 5, 20);
                if (!empty($finApprovals)):
                    foreach ($finApprovals as $apr):
                        echo renderApprovalWorkflowCard($apr, $conn);
                        echo renderApprovalActionButtons($apr['id']);
                    endforeach;
                else:
                    echo '<div class="text-muted small py-4 text-center">No pending approvals.</div>';
                endif;
                ?>
            </section>
        </div>
    </div>
</div>

<!-- Department Management -->
<div class="container-fluid px-4 pb-4">
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="section-card h-100">
                <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-sitemap me-2 text-info"></i>Your Position in Hierarchy</h6>
                <div class="d-flex align-items-center gap-2 mb-2 small">
                    <span class="badge bg-primary">Level 3</span>
                    <span class="text-muted">You report to:</span>
                    <span class="fw-semibold">Director General (Level 1)</span>
                </div>
                <?= renderHierarchyChart($conn) ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="section-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0" style="font-size:0.95rem"><i class="fas fa-bell me-2 text-danger"></i>Finance Alerts</h6>
                </div>
                <?= renderAlertsPanel($conn, 'FIN', 5) ?>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="section-card h-100">
                <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-chart-bar me-2 text-success"></i>Finance Department Performance</h6>
                <?php
                $finStaffId = 0; $finRoleId = 5;
                $sq = $conn ? $conn->prepare("SELECT id FROM staff WHERE role_id = ? AND status = 'Active' LIMIT 1") : false;
                if ($sq) { $sq->bind_param('i', $finRoleId); $sq->execute(); $sr = $sq->get_result()->fetch_assoc(); $sq->close(); if ($sr) $finStaffId = $sr['id']; }
                echo renderDirectorPerformanceCard($finStaffId, $finRoleId, 'Director Finance', $conn);
                ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="section-card h-100">
                <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-check-double me-2 text-primary"></i>Pending Finance Approvals</h6>
                <p class="small text-muted mb-2"><a href="#approvals" onclick="switchToSection('approvals');return false" class="text-decoration-none">View full approvals section &rarr;</a></p>
                <?php
                $finApprovalsMini = getPendingApprovals($conn, 5, 5);
                if (!empty($finApprovalsMini)):
                    foreach ($finApprovalsMini as $apr):
                        echo renderApprovalWorkflowCard($apr, $conn);
                        echo renderApprovalActionButtons($apr['id']);
                    endforeach;
                else:
                    echo '<div class="text-muted small py-3 text-center">No pending approvals.</div>';
                endif;
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="add_expense">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Expense</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option>Salaries & Wages</option><option>Utilities</option><option>Supplies & Materials</option><option>Maintenance</option><option>Transportation</option><option>Medical</option><option>Academic</option><option>Administrative</option><option>Capital Projects</option><option>Research</option><option>Student Services</option><option>Staff Development</option><option>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" required></textarea></div>
                    <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select"><option>cash</option><option>bank_transfer</option><option>cheque</option><option>mobile_money</option></select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Supplier</label><input type="text" name="supplier" class="form-control" placeholder="Supplier name"></div>
                    <div class="col-md-6"><label class="form-label">Department</label><input type="text" name="department" class="form-control" placeholder="Department"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="record_payment">
            <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Record Payment</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php if($students_conn){ $r=$students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 300"); if($r) while($row=$r->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']?:$row['student_number']) ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Amount (UGX)</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option>cash</option><option>bank_deposit</option><option>mobile_money</option><option>cheque</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Record Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Budget Modal -->
<div class="modal fade" id="budgetModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create_budget">
            <div class="modal-header bg-warning text-dark"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create Budget</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Budget Code</label><input type="text" name="budget_code" class="form-control" value="BGT-<?= date('Y') ?>-<?= mt_rand(100,999) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Budget Name</label><input type="text" name="budget_name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Category</label>
                        <select name="budget_category" class="form-select">
                            <option>Academic</option><option>Administrative</option><option>Operations</option><option>Capital Projects</option><option>Research</option><option>Student Services</option><option>Staff Development</option><option>Maintenance</option><option>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Department</label><input type="text" name="department" class="form-control" placeholder="All"></div>
                    <div class="col-md-6"><label class="form-label">Fiscal Year</label><input type="text" name="fiscal_year" class="form-control" value="<?= date('Y') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Allocated Amount</label><input type="number" step="0.01" name="allocated_amount" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Create Budget</button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden form for approve/reject actions -->
<form method="POST" id="actionForm" style="display:none">
    <input type="hidden" name="action" id="afAction">
    <input type="hidden" name="expense_id" id="afId">
    <input type="hidden" name="payment_id" id="afPid">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmAction(action, id, msg) {
    if(!confirm(msg||'Are you sure?')) return;
    const f = document.getElementById('actionForm');
    document.getElementById('afAction').value = action;
    document.getElementById('afId').value = id;
    f.submit();
}

function filterRevenue() {
    const q = document.getElementById('revSearch')?.value?.toLowerCase()||'';
    document.querySelectorAll('#revTable tbody tr').forEach(r=>{ r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
}

function filterRevenueByDate() {
    const f = document.getElementById('revFrom').value;
    const t = document.getElementById('revTo').value;
    document.querySelectorAll('#revTable tbody tr').forEach(r=>{
        const d = r.cells[4]?.textContent||'';
        if(!f && !t) { r.style.display=''; return; }
        r.style.display = (d>=f && d<=t) ? '' : 'none';
    });
}

function filterExpenses() {
    const q = document.getElementById('expSearch')?.value?.toLowerCase()||'';
    document.querySelectorAll('#expTable tbody tr').forEach(r=>{ r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
}

function filterPayments() {
    const q = document.getElementById('paySearch')?.value?.toLowerCase()||'';
    document.querySelectorAll('#payTable tbody tr').forEach(r=>{ r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
}

function viewExpense(id) {
    fetch('director-finance.php?ajax=expense_detail&expense_id='+id)
        .then(r=>r.json()).then(d=>{
            alert('Expense: '+d.expense_id+'\nCategory: '+d.expense_category+'\nDescription: '+d.description+'\nAmount: UGX '+Number(d.amount).toLocaleString()+'\nDate: '+d.expense_date+'\nSupplier: '+(d.notes||'-')+'\nStatus: '+d.status+'\nRequested By: '+(d.requested_by_name||'-')+'\nApproved By: '+(d.approved_by_name||'-'));
        }).catch(function(e){ console.warn('[ISNM] Expense detail fetch failed:', e); });
}

// Student Fees Module
let _feeStudentId = 0;
function searchStudentFees() {
    const q = document.getElementById('feeSearch')?.value?.trim();
    if(!q) return;
    fetch('director-finance.php?ajax=student_search&q='+encodeURIComponent(q))
        .then(r=>r.json()).then(data=>{
            const c = document.getElementById('feeSearchResults');
            if(!data.length){ c.innerHTML='<div class="text-muted small">No students found.</div>'; document.getElementById('feeStudentDetail').style.display='none'; return; }
            let h = '<div class="list-group mb-2" style="max-height:200px;overflow-y:auto">';
            data.forEach(s=>{ h+='<button class="list-group-item list-group-item-action py-1 small" onclick="loadStudentFees('+s.id+')"><strong>'+s.full_name+'</strong> <code>'+s.student_number+'</code> – '+s.course+'</button>'; });
            h += '</div>'; c.innerHTML = h;
        }).catch(function(){ document.getElementById('feeSearchResults').innerHTML = '<div class="text-danger small">Search failed.</div>'; });
}

function loadStudentFees(id) {
    _feeStudentId = id;
    fetch('director-finance.php?ajax=student_fees&student_id='+id)
        .then(r=>r.json()).then(d=>{
            const info = d.info||{};
            document.getElementById('feeStudentDetail').style.display='block';
            document.getElementById('feeStudentInfo').innerHTML = '<div class="row g-2 small"><div class="col-md-4"><strong>Name:</strong> '+(info.full_name||'-')+'</div><div class="col-md-4"><strong>Reg No:</strong> '+(info.registration_number||info.student_number||'-')+'</div><div class="col-md-4"><strong>Program:</strong> '+(info.course||'-')+'</div><div class="col-md-4"><strong>Phone:</strong> '+(info.phone||'-')+'</div><div class="col-md-4"><strong>Year:</strong> '+(info.current_year||'-')+'</div><div class="col-md-4"><strong>Status:</strong> <span class="badge bg-'+(info.status==='Active'?'success':'secondary')+'">'+(info.status||'-')+'</span></div></div>';

            let invHtml = '';
            let tInv=0,tPaid=0,tBal=0;
            (d.invoices||[]).forEach(inv=>{ tInv+=parseFloat(inv.total_amount||0); tPaid+=parseFloat(inv.amount_paid||0); tBal+=parseFloat(inv.balance||0); invHtml+='<tr><td>'+inv.invoice_number+'</td><td>'+inv.fee_type+'</td><td>UGX '+Number(inv.total_amount).toLocaleString()+'</td><td>UGX '+Number(inv.amount_paid).toLocaleString()+'</td><td class="fw-bold">UGX '+Number(inv.balance).toLocaleString()+'</td><td>'+inv.due_date+'</td><td><span class="badge bg-'+(inv.status==='paid'?'success':inv.status==='partial'?'warning':'danger')+'">'+inv.status+'</span></td></tr>'; });
            if((d.invoices||[]).length) invHtml+='<tr class="fw-bold"><td colspan="2">Totals</td><td>UGX '+tInv.toLocaleString()+'</td><td>UGX '+tPaid.toLocaleString()+'</td><td>UGX '+tBal.toLocaleString()+'</td><td colspan="2"></td></tr>';
            document.querySelector('#feeInvTable tbody').innerHTML = invHtml || '<tr><td colspan="7" class="text-center text-muted">No invoices.</td></tr>';

            let payHtml = '';
            (d.payments||[]).forEach(p=>{ payHtml+='<tr><td><code>'+p.payment_reference+'</code></td><td>UGX '+Number(p.amount_received).toLocaleString()+'</td><td>'+p.payment_method+'</td><td>'+p.payment_date+'</td><td><span class="badge bg-'+(p.status==='approved'?'success':p.status==='pending'?'warning':'danger')+'">'+p.status+'</span></td></tr>'; });
            document.querySelector('#feePayTable tbody').innerHTML = payHtml || '<tr><td colspan="5" class="text-center text-muted">No payments.</td></tr>';
        }).catch(function(){ document.getElementById('feeStudentDetail').style.display='block'; document.getElementById('feeStudentInfo').innerHTML='<div class="alert alert-danger">Failed to load fees.</div>'; });
}

function generateStatement() {
    if(!_feeStudentId) return;
    const w = window.open('director-academics.php?report=fee_statement&student_id='+_feeStudentId,'_blank');
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
