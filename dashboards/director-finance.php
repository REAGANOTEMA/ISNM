<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/website_submissions_widget.php';
require_once __DIR__ . '/../includes/director_website_panel.php';
$ctx = bootstrapStaffDashboard(['director finance', 'director general', 'ceo']);
$staff = $ctx['staff']; $students = $ctx['students']; $website = $ctx['website'];
$user = $ctx['user']; $uid = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? ''; $uname = $_SESSION['full_name'] ?? 'Finance Director';
$staff_db   = defined('STAFF_DB_NAME')    ? STAFF_DB_NAME    : 'igangaschool_staffs';
$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';
$migrate = function($db) use ($staff_db, $students_db) {
    if (!$db) return;
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.finance_messages (id INT AUTO_INCREMENT PRIMARY KEY, sender_id INT DEFAULT 0, sender_name VARCHAR(200), recipient_role VARCHAR(100), subject VARCHAR(300), message TEXT, is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.finance_notices (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300), content TEXT, audience VARCHAR(100), published_by VARCHAR(200), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.budget_approvals (id INT AUTO_INCREMENT PRIMARY KEY, budget_id INT DEFAULT 0, request_type VARCHAR(50), requested_by INT DEFAULT 0, amount DECIMAL(14,2) DEFAULT 0, description TEXT, status ENUM('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending', approver_id INT DEFAULT 0, approver_name VARCHAR(200), approver_comments TEXT, escalated_to INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.expenditure_approvals (id INT AUTO_INCREMENT PRIMARY KEY, budget_id INT DEFAULT 0, request_type VARCHAR(50), requested_by INT DEFAULT 0, amount DECIMAL(14,2) DEFAULT 0, description TEXT, status ENUM('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending', approver_id INT DEFAULT 0, approver_name VARCHAR(200), approver_comments TEXT, escalated_to INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.procurement_requests (id INT AUTO_INCREMENT PRIMARY KEY, pr_number VARCHAR(100), title VARCHAR(300), description TEXT, amount DECIMAL(14,2) DEFAULT 0, department VARCHAR(200), supplier_name VARCHAR(200), status ENUM('draft','pending','approved','rejected') DEFAULT 'draft', requested_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.suppliers (id INT AUTO_INCREMENT PRIMARY KEY, supplier_name VARCHAR(300), contact_person VARCHAR(200), phone VARCHAR(50), email VARCHAR(100), address TEXT, category VARCHAR(100), status ENUM('active','inactive') DEFAULT 'active', performance_rating DECIMAL(5,2) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.supplier_payments (id INT AUTO_INCREMENT PRIMARY KEY, supplier_id INT DEFAULT 0, payment_number VARCHAR(100), amount DECIMAL(14,2) DEFAULT 0, payment_method VARCHAR(50), payment_date DATE, invoice_ref VARCHAR(100), status VARCHAR(50) DEFAULT 'pending', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.finance_assets (id INT AUTO_INCREMENT PRIMARY KEY, asset_name VARCHAR(300), asset_tag VARCHAR(100), category VARCHAR(100), purchase_date DATE, purchase_price DECIMAL(14,2) DEFAULT 0, current_value DECIMAL(14,2) DEFAULT 0, depreciation_rate DECIMAL(5,2) DEFAULT 0, location VARCHAR(200), assigned_to VARCHAR(200), status ENUM('active','disposed','maintenance') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.capital_projects (id INT AUTO_INCREMENT PRIMARY KEY, project_name VARCHAR(300), project_code VARCHAR(100), budget DECIMAL(14,2) DEFAULT 0, spent DECIMAL(14,2) DEFAULT 0, start_date DATE, end_date DATE, status ENUM('planning','active','completed','cancelled') DEFAULT 'planning', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.audit_findings (id INT AUTO_INCREMENT PRIMARY KEY, finding_title VARCHAR(300), description TEXT, severity ENUM('low','medium','high','critical') DEFAULT 'medium', department VARCHAR(200), status ENUM('open','in_progress','resolved','closed') DEFAULT 'open', reported_by VARCHAR(200), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.risk_register (id INT AUTO_INCREMENT PRIMARY KEY, risk_name VARCHAR(300), description TEXT, category VARCHAR(100), likelihood ENUM('low','medium','high') DEFAULT 'medium', impact ENUM('low','medium','high') DEFAULT 'medium', mitigation TEXT, status ENUM('active','monitored','resolved') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.compliance_alerts (id INT AUTO_INCREMENT PRIMARY KEY, alert_title VARCHAR(300), description TEXT, compliance_type ENUM('financial','ura','regulatory') DEFAULT 'financial', severity ENUM('low','medium','high','critical') DEFAULT 'medium', status ENUM('open','acknowledged','resolved') DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.payroll_approvals (id INT AUTO_INCREMENT PRIMARY KEY, budget_id INT DEFAULT 0, request_type VARCHAR(50), requested_by INT DEFAULT 0, amount DECIMAL(14,2) DEFAULT 0, description TEXT, status ENUM('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending', approver_id INT DEFAULT 0, approver_name VARCHAR(200), approver_comments TEXT, escalated_to INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$staff_db}.payroll_history (id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, gross_salary DECIMAL(14,2) DEFAULT 0, deductions DECIMAL(14,2) DEFAULT 0, net_salary DECIMAL(14,2) DEFAULT 0, payment_date DATE DEFAULT NULL, payment_method VARCHAR(50) DEFAULT '', status VARCHAR(50) DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_staff (staff_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
$migrate($staff); $migrate($students);
$page = $_GET['page'] ?? '';
$pageMap = ['revenue'=>'revenue_summary','budget'=>'budget_planning','expenditure'=>'expenditure_monitoring','payroll'=>'payroll_review','ledger'=>'general_ledger','audit'=>'audit_logs','procurement'=>'purchase_requests','assets'=>'asset_register'];
$_GET['section'] = $_GET['section'] ?? $_GET['view'] ?? ($pageMap[$page] ?? 'overview');
$view = $_GET['section']; if ($view === 'overview') $view = 'home';
$ajax = $_GET['ajax'] ?? ''; $q = $_GET['q'] ?? '';
function currency($n) { return 'UGX ' . number_format((float)$n, 0); }
function fin_success($m) { $_SESSION['fin_success'] = $m; }
function fin_error($m) { $_SESSION['fin_error'] = $m; }

// -- AJAX Endpoints --

if ($ajax === 'finance_stats' && $staff) {
    header('Content-Type: application/json');
    $tr = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
    $te = (float)(($r=$staff->query("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE status IN('approved','paid')"))&&$r?$r->fetch_assoc()['t']:0);
    $of = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(balance),0) t FROM {$students_db}.student_invoices WHERE status IN('pending','partial','overdue')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
    $ba = (float)(($r=$staff->query("SELECT COALESCE(SUM(allocated_amount),0) t FROM {$students_db}.budget_records WHERE status IN('Approved','Active')"))&&$r?$r->fetch_assoc()['t']:0);
    $bs = (float)(($r=$staff->query("SELECT COALESCE(SUM(spent_amount),0) t FROM {$students_db}.budget_records WHERE status IN('Approved','Active')"))&&$r?$r->fetch_assoc()['t']:0);
    $bu = $ba > 0 ? round(($bs/$ba)*100,1) : 0;
    $pa = 0;
    $pa += (int)(($r=$staff->query("SELECT COUNT(*)c FROM {$students_db}.budget_approvals WHERE status='pending'"))&&$r?$r->fetch_assoc()['c']:0);
    $pa += (int)(($r=$staff->query("SELECT COUNT(*)c FROM {$students_db}.expenditure_approvals WHERE status='pending'"))&&$r?$r->fetch_assoc()['c']:0);
    $pa += (int)(($r=$staff->query("SELECT COUNT(*)c FROM {$students_db}.payroll_approvals WHERE status='pending'"))&&$r?$r->fetch_assoc()['c']:0);
    $hs = min(100,max(0,round(($tr>0?($tr-$te)/$tr*50:0)+(100-$bu)*0.3+($of>0?10:20))));
    echo json_encode(['total_revenue'=>$tr,'total_expenses'=>$te,'net_position'=>$tr-$te,'outstanding_fees'=>$of,'budget_utilization'=>$bu,'pending_approvals'=>$pa,'health_score'=>$hs]); exit;
}
if ($ajax === 'revenue_data' && $staff) {
    header('Content-Type: application/json');
    $f = $_GET['from']??date('Y-m-01'); $t = $_GET['to']??date('Y-m-d'); $rows=[];
    if ($students) { $stmt=$students->prepare("SELECT p.*,s.full_name student_name,s.student_number FROM {$students_db}.payments p LEFT JOIN {$students_db}.students s ON p.student_id=s.id WHERE DATE(p.payment_date) BETWEEN ? AND ? ORDER BY p.payment_date DESC LIMIT 200"); if($stmt){$stmt->bind_param('ss',$f,$t);$stmt->execute();$r=$stmt->get_result();if($r)while($rw=$r->fetch_assoc())$rows[]=$rw;$stmt->close();} }
    echo json_encode($rows); exit;
}
if ($ajax === 'fee_collection_data' && $staff) {
    header('Content-Type: application/json');
    $f = $_GET['from']??date('Y-m-01'); $t = $_GET['to']??date('Y-m-d'); $rows=[];
    if ($students) { $stmt=$students->prepare("SELECT si.*,s.full_name student_name,s.student_number,s.program FROM {$students_db}.student_invoices si LEFT JOIN {$students_db}.students s ON si.student_id=s.id WHERE DATE(si.created_at) BETWEEN ? AND ? ORDER BY si.created_at DESC LIMIT 200"); if($stmt){$stmt->bind_param('ss',$f,$t);$stmt->execute();$r=$stmt->get_result();if($r)while($rw=$r->fetch_assoc())$rows[]=$rw;$stmt->close();} }
    echo json_encode($rows); exit;
}
if ($ajax === 'payment_list' && $staff) {
    header('Content-Type: application/json');
    $st = $_GET['status']??''; $rows=[];
    if ($students) {
        if($st){$stmt=$students->prepare("SELECT p.*,s.full_name student_name,s.student_number FROM {$students_db}.payments p LEFT JOIN {$students_db}.students s ON p.student_id=s.id WHERE p.status=? ORDER BY p.payment_date DESC LIMIT 200");$stmt->bind_param('s',$st);$stmt->execute();$r=$stmt->get_result();$stmt->close();}
        else{$r=$students->query("SELECT p.*,s.full_name student_name,s.student_number FROM {$students_db}.payments p LEFT JOIN {$students_db}.students s ON p.student_id=s.id ORDER BY p.payment_date DESC LIMIT 200");}
        if($r)while($rw=$r->fetch_assoc())$rows[]=$rw;
    }
    echo json_encode($rows); exit;
}
if ($ajax === 'expenditure_list' && $staff) {
    header('Content-Type: application/json');
    $st = $_GET['status']??''; $rows=[];
    if($st){$stmt=$staff->prepare("SELECT e.*,s.full_name requested_by_name FROM expenses e LEFT JOIN staff s ON e.requested_by=s.id WHERE e.status=? ORDER BY e.created_at DESC LIMIT 200");$stmt->bind_param('s',$st);$stmt->execute();$r=$stmt->get_result();$stmt->close();}
    else{$r=$staff->query("SELECT e.*,s.full_name requested_by_name FROM expenses e LEFT JOIN staff s ON e.requested_by=s.id ORDER BY e.created_at DESC LIMIT 200");}
    if($r)while($rw=$r->fetch_assoc())$rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'ledger_data' && $staff) {
    header('Content-Type: application/json');
    $f=$_GET['from']??date('Y-m-01'); $t=$_GET['to']??date('Y-m-d'); $rows=[];
    $stmt=$staff->prepare("SELECT * FROM {$students_db}.general_ledger WHERE entry_date BETWEEN ? AND ? ORDER BY entry_date DESC LIMIT 200");
    if($stmt){$stmt->bind_param('ss',$f,$t);$stmt->execute();$r=$stmt->get_result();if($r)while($rw=$r->fetch_assoc())$rows[]=$rw;$stmt->close();}
    echo json_encode($rows); exit;
}
if ($ajax === 'supplier_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.suppliers ORDER BY supplier_name ASC"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'asset_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.finance_assets ORDER BY created_at DESC"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'project_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.capital_projects ORDER BY created_at DESC"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'audit_finding_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.audit_findings ORDER BY created_at DESC"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'risk_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.risk_register ORDER BY created_at DESC"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'compliance_alert_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.compliance_alerts ORDER BY created_at DESC"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'procurement_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.procurement_requests ORDER BY created_at DESC"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'budget_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.budget_records ORDER BY created_at DESC"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'payroll_data' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT ss.*,st.full_name staff_name,st.position,st.department FROM salary_structures ss LEFT JOIN staff st ON ss.staff_id=st.id WHERE ss.status='active' ORDER BY st.full_name"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'payroll_history_data' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT ph.*,st.full_name staff_name FROM payroll_history ph LEFT JOIN staff st ON ph.staff_id=st.id ORDER BY ph.created_at DESC LIMIT 100"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'staff_cost_data' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT st.department,COUNT(*)staff_count,COALESCE(SUM(ss.net_salary),0) total_salary FROM staff st LEFT JOIN salary_structures ss ON st.id=ss.staff_id AND ss.status='active' WHERE st.status='Active' GROUP BY st.department ORDER BY total_salary DESC"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'supplier_payment_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT sp.*,s.supplier_name FROM {$students_db}.supplier_payments sp LEFT JOIN {$students_db}.suppliers s ON sp.supplier_id=s.id ORDER BY sp.created_at DESC LIMIT 100"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'update_audit_status' && $staff) {
    header('Content-Type: application/json');
    $id=(int)($_POST['id']??0); $st=$_POST['status']??'';
    $validSt=['open','in_progress','resolved','closed'];
    if($id&&in_array($st,$validSt)){
        $stmt=$staff->prepare("UPDATE {$students_db}.audit_findings SET status=? WHERE id=?");
        if($stmt){$stmt->bind_param('si',$st,$id);if($stmt->execute()&&$stmt->affected_rows>=0){echo json_encode(['success'=>true]);$stmt->close();exit;}}
    }
    echo json_encode(['success'=>false,'error'=>'Update failed']); exit;
}
if ($ajax === 'approval_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $type=$_GET['type']??'';
    $tables = ['budget_approvals','expenditure_approvals','payroll_approvals'];
    foreach($tables as $tbl) {
        if($type && $tbl !== $type.'_approvals') continue;
        $r=$staff->query("SELECT a.*,'$tbl' as tbl FROM {$students_db}.$tbl a WHERE a.status='pending' ORDER BY a.created_at DESC LIMIT 50");
        if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    }
    echo json_encode($rows); exit;
}
if ($ajax === 'budget_variance_data' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.budget_records WHERE status IN('Approved','Active') ORDER BY budget_name"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'cash_flow_data' && $staff) {
    header('Content-Type: application/json');
    $f=$_GET['from']??date('Y-01-01'); $t=$_GET['to']??date('Y-m-d'); $rows=[];
    if ($students) {
        $stmt=$students->prepare("SELECT DATE(payment_date) dt,COALESCE(SUM(amount_received),0) inflow,0 outflow FROM {$students_db}.payments WHERE status IN('verified','approved','completed') AND DATE(payment_date) BETWEEN ? AND ? GROUP BY DATE(payment_date) ORDER BY dt");
        if($stmt){$stmt->bind_param('ss',$f,$t);$stmt->execute();$r=$stmt->get_result();if($r)while($rw=$r->fetch_assoc())$rows[]=$rw;$stmt->close();}
    }
    echo json_encode($rows); exit;
}

// -- AJAX Write Endpoints --

if ($ajax === 'create_budget' && $staff) {
    header('Content-Type: application/json');
    $code=$_POST['budget_code']??'BGT-'.date('Y').'-'.mt_rand(100,999);
    $name=$_POST['budget_name']??'';
    $cat=$_POST['budget_category']??'Operations';
    $dept=$_POST['department']??'';
    $fy=$_POST['fiscal_year']??date('Y');
    $amt=(float)($_POST['allocated_amount']??0);
    $desc=$_POST['description']??'';
    if(!$name||!$amt){ echo json_encode(['success'=>false,'error'=>'Name and amount required']); exit; }
    $stmt=$staff->prepare("INSERT INTO {$students_db}.budget_records (budget_code,budget_name,budget_category,department,fiscal_year,allocated_amount,spent_amount,status,created_by) VALUES (?,?,?,?,?,?,0,'Draft',?)");
    if($stmt){$stmt->bind_param('sssssdi',$code,$name,$cat,$dept,$fy,$amt,$uid);if($stmt->execute()){echo json_encode(['success'=>true,'code'=>$code]);exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'approve_budget_request' && $staff) {
    header('Content-Type: application/json');
    $id=(int)($_POST['id']??0); $st=$_POST['status']??'approved';
    $cmt=$_POST['comments']??'';
    if($id){
        $stmt=$staff->prepare("UPDATE {$students_db}.budget_approvals SET status=?,approver_id=?,approver_name=?,approver_comments=? WHERE id=?");
        if($stmt){$stmt->bind_param('sissi',$st,$uid,$uname,$cmt,$id);if($stmt->execute()&&$stmt->affected_rows>0){echo json_encode(['success'=>true]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>'Update failed']);$stmt->close();exit;}
    }
    echo json_encode(['success'=>false]); exit;
}
if ($ajax === 'create_expenditure' && $staff) {
    header('Content-Type: application/json');
    $cat=$_POST['category']??'';
    $desc=$_POST['description']??'';
    $amt=(float)($_POST['amount']??0);
    $dt=$_POST['expense_date']??date('Y-m-d');
    $eid='EXP-'.date('Ymd').'-'.mt_rand(1000,9999);
    if(!$cat||!$desc||!$amt){ echo json_encode(['success'=>false,'error'=>'Required fields missing']); exit; }
    $stmt=$staff->prepare("INSERT INTO expenses (expense_id,description,expense_category,amount,expense_date,status,requested_by) VALUES (?,?,?,?,?,'pending',?)");
    if($stmt){$stmt->bind_param('sssdsi',$eid,$desc,$cat,$amt,$dt,$uid);if($stmt->execute()){echo json_encode(['success'=>true,'id'=>$eid]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'approve_expenditure' && $staff) {
    header('Content-Type: application/json');
    $id=(int)($_POST['id']??0);
    if($id){ $stmt=$staff->prepare("UPDATE expenses SET status='approved',approved_by=?,approval_date=NOW() WHERE id=? AND status='pending'"); if($stmt){ $stmt->bind_param('ii',$uid,$id); $stmt->execute(); if($stmt->affected_rows>0){ echo json_encode(['success'=>true]); exit; }} echo json_encode(['success'=>false,'error'=>'Approve failed']); exit; }
    echo json_encode(['success'=>false]); exit;
}
if ($ajax === 'reject_expenditure' && $staff) {
    header('Content-Type: application/json');
    $id=(int)($_POST['id']??0);
    if($id){ $stmt=$staff->prepare("UPDATE expenses SET status='rejected',approved_by=?,approval_date=NOW() WHERE id=? AND status='pending'"); if($stmt){ $stmt->bind_param('ii',$uid,$id); $stmt->execute(); if($stmt->affected_rows>0){ echo json_encode(['success'=>true]); exit; }} echo json_encode(['success'=>false,'error'=>'Reject failed']); exit; }
    echo json_encode(['success'=>false]); exit;
}
if ($ajax === 'approve_payroll' && $staff) {
    header('Content-Type: application/json');
    $id=(int)($_POST['id']??0); $st=$_POST['status']??'approved';
    $cmt=$_POST['comments']??'';
    if($id){
        $stmt=$staff->prepare("UPDATE {$students_db}.payroll_approvals SET status=?,approver_id=?,approver_name=?,approver_comments=? WHERE id=?");
        if($stmt){$stmt->bind_param('sissi',$st,$uid,$uname,$cmt,$id);if($stmt->execute()&&$stmt->affected_rows>0){echo json_encode(['success'=>true]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>'Update failed']);$stmt->close();exit;}
    }
    echo json_encode(['success'=>false]); exit;
}
if ($ajax === 'submit_approval_action' && $staff) {
    header('Content-Type: application/json');
    $id=(int)($_POST['id']??0); $tbl=$_POST['table']??'';
    $st=$_POST['status']??'approved';
    $cmt=$_POST['comments']??'';
    $escl=(int)($_POST['escalated_to']??0);
    $validTables = ['budget_approvals','expenditure_approvals','payroll_approvals'];
    if(!$id||!$tbl||!in_array($tbl,$validTables)){ echo json_encode(['success'=>false,'error'=>'Invalid parameters']); exit; }
    if($st==='escalated'){
        $stmt=$staff->prepare("UPDATE {$students_db}.$tbl SET status=?,approver_id=?,approver_name=?,approver_comments=?,escalated_to=? WHERE id=?");
        if($stmt){$stmt->bind_param('sissii',$st,$uid,$uname,$cmt,$escl,$id);if($stmt->execute()&&$stmt->affected_rows>0){echo json_encode(['success'=>true]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>'Update failed']);$stmt->close();exit;}
    } else {
        $stmt=$staff->prepare("UPDATE {$students_db}.$tbl SET status=?,approver_id=?,approver_name=?,approver_comments=? WHERE id=?");
        if($stmt){$stmt->bind_param('sissi',$st,$uid,$uname,$cmt,$id);if($stmt->execute()&&$stmt->affected_rows>0){echo json_encode(['success'=>true]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>'Update failed']);$stmt->close();exit;}
    }
    echo json_encode(['success'=>false,'error'=>'Update failed or no changes']); exit;
}
if ($ajax === 'create_supplier' && $staff) {
    header('Content-Type: application/json');
    $sn=$_POST['supplier_name']??'';
    $cp=$_POST['contact_person']??'';
    $ph=$_POST['phone']??'';
    $em=$_POST['email']??'';
    $ad=$_POST['address']??'';
    $ct=$_POST['category']??'General';
    if(!$sn){ echo json_encode(['success'=>false,'error'=>'Supplier name required']); exit; }
    $stmt=$staff->prepare("INSERT INTO {$students_db}.suppliers (supplier_name,contact_person,phone,email,address,category) VALUES (?,?,?,?,?,?)");
    if($stmt){$stmt->bind_param('ssssss',$sn,$cp,$ph,$em,$ad,$ct);if($stmt->execute()){echo json_encode(['success'=>true,'id'=>$staff->insert_id]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'record_supplier_payment' && $staff) {
    header('Content-Type: application/json');
    $sid=(int)($_POST['supplier_id']??0); $amt=(float)($_POST['amount']??0);
    $pm=$_POST['payment_method']??'bank';
    $pd=$_POST['payment_date']??date('Y-m-d'); $ir=$_POST['invoice_ref']??'';
    $pn='SP-'.date('Ymd').'-'.mt_rand(1000,9999);
    if(!$sid||!$amt){ echo json_encode(['success'=>false,'error'=>'Supplier and amount required']); exit; }
    $status = 'pending';
    $stmt=$staff->prepare("INSERT INTO {$students_db}.supplier_payments (supplier_id,payment_number,amount,payment_method,payment_date,invoice_ref,status,created_by) VALUES (?,?,?,?,?,?,?,?)");
    if($stmt){$stmt->bind_param('idsssssi',$sid,$pn,$amt,$pm,$pd,$ir,$status,$uid);if($stmt->execute()){echo json_encode(['success'=>true,'ref'=>$pn]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'create_asset' && $staff) {
    header('Content-Type: application/json');
    $an=$_POST['asset_name']??'';
    $at=$_POST['asset_tag']??'';
    $ac=$_POST['category']??'Equipment';
    $pd=$_POST['purchase_date']??date('Y-m-d');
    $pp=(float)($_POST['purchase_price']??0);
    $dr=(float)($_POST['depreciation_rate']??0);
    $lo=$_POST['location']??'';
    $as=$_POST['assigned_to']??'';
    if(!$an){ echo json_encode(['success'=>false,'error'=>'Asset name required']); exit; }
    $stmt=$staff->prepare("INSERT INTO {$students_db}.finance_assets (asset_name,asset_tag,category,purchase_date,purchase_price,current_value,depreciation_rate,location,assigned_to) VALUES (?,?,?,?,?,?,?,?,?)");
    if($stmt){$stmt->bind_param('ssssddsss',$an,$at,$ac,$pd,$pp,$pp,$dr,$lo,$as);if($stmt->execute()){echo json_encode(['success'=>true,'id'=>$staff->insert_id]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'create_project' && $staff) {
    header('Content-Type: application/json');
    $pn=$_POST['project_name']??'';
    $pc=$_POST['project_code']??'PRJ-'.date('Y').'-'.mt_rand(100,999);
    $bd=(float)($_POST['budget']??0);
    $sd=$_POST['start_date']??date('Y-m-d'); $ed=$_POST['end_date']??'';
    if(!$pn){ echo json_encode(['success'=>false,'error'=>'Project name required']); exit; }
    $status = 'planning';
    $stmt=$staff->prepare("INSERT INTO {$students_db}.capital_projects (project_name,project_code,budget,spent,start_date,end_date,status) VALUES (?,?,?,0,?,?,?)");
    if($stmt){$stmt->bind_param('ssdsss',$pn,$pc,$bd,$sd,$ed,$status);if($stmt->execute()){echo json_encode(['success'=>true,'code'=>$pc]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'create_audit_finding' && $staff) {
    header('Content-Type: application/json');
    $ft=$_POST['finding_title']??'';
    $fd=$_POST['description']??'';
    $fv=$_POST['severity']??'medium';
    $fdep=$_POST['department']??'';
    if(!$ft){ echo json_encode(['success'=>false,'error'=>'Finding title required']); exit; }
    $stmt=$staff->prepare("INSERT INTO {$students_db}.audit_findings (finding_title,description,severity,department,reported_by) VALUES (?,?,?,?,?)");
    if($stmt){$stmt->bind_param('sssss',$ft,$fd,$fv,$fdep,$uname);if($stmt->execute()){echo json_encode(['success'=>true,'id'=>$staff->insert_id]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'create_risk' && $staff) {
    header('Content-Type: application/json');
    $rn=$_POST['risk_name']??'';
    $rd=$_POST['description']??'';
    $rc=$_POST['category']??'';
    $rl=$_POST['likelihood']??'medium';
    $ri=$_POST['impact']??'medium';
    $rm=$_POST['mitigation']??'';
    if(!$rn){ echo json_encode(['success'=>false,'error'=>'Risk name required']); exit; }
    $stmt=$staff->prepare("INSERT INTO {$students_db}.risk_register (risk_name,description,category,likelihood,impact,mitigation) VALUES (?,?,?,?,?,?)");
    if($stmt){$stmt->bind_param('ssssss',$rn,$rd,$rc,$rl,$ri,$rm);if($stmt->execute()){echo json_encode(['success'=>true,'id'=>$staff->insert_id]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'create_compliance_alert' && $staff) {
    header('Content-Type: application/json');
    $at=$_POST['alert_title']??'';
    $ad=$_POST['description']??'';
    $ct=$_POST['compliance_type']??'financial';
    $sv=$_POST['severity']??'medium';
    if(!$at){ echo json_encode(['success'=>false,'error'=>'Alert title required']); exit; }
    $stmt=$staff->prepare("INSERT INTO {$students_db}.compliance_alerts (alert_title,description,compliance_type,severity) VALUES (?,?,?,?)");
    if($stmt){$stmt->bind_param('ssss',$at,$ad,$ct,$sv);if($stmt->execute()){echo json_encode(['success'=>true,'id'=>$staff->insert_id]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'create_procurement' && $staff) {
    header('Content-Type: application/json');
    $pt=$_POST['title']??'';
    $pd=$_POST['description']??'';
    $pa=(float)($_POST['amount']??0);
    $pdep=$_POST['department']??'';
    $ps=$_POST['supplier_name']??'';
    $prn='PR-'.date('Ymd').'-'.mt_rand(1000,9999);
    if(!$pt){ echo json_encode(['success'=>false,'error'=>'Title required']); exit; }
    $status = 'draft';
    $stmt=$staff->prepare("INSERT INTO {$students_db}.procurement_requests (pr_number,title,description,amount,department,supplier_name,status,requested_by) VALUES (?,?,?,?,?,?,?,?)");
    if($stmt){$stmt->bind_param('sssdsssi',$prn,$pt,$pd,$pa,$pdep,$ps,$status,$uid);if($stmt->execute()){echo json_encode(['success'=>true,'pr'=>$prn]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'send_finance_message' && $staff) {
    header('Content-Type: application/json');
    $subj=$_POST['subject']??'';
    $msg=$_POST['message']??'';
    $rr=$_POST['recipient_role']??'all';
    if(!$subj||!$msg){ echo json_encode(['success'=>false,'error'=>'Subject and message required']); exit; }
    $stmt=$staff->prepare("INSERT INTO {$students_db}.finance_messages (sender_id,sender_name,recipient_role,subject,message) VALUES (?,?,?,?,?)");
    if($stmt){$stmt->bind_param('issss',$uid,$uname,$rr,$subj,$msg);if($stmt->execute()){echo json_encode(['success'=>true]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}
if ($ajax === 'publish_finance_notice' && $staff) {
    header('Content-Type: application/json');
    $t=$_POST['title']??'';
    $c=$_POST['content']??'';
    $a=$_POST['audience']??'all';
    if(!$t||!$c){ echo json_encode(['success'=>false,'error'=>'Title and content required']); exit; }
    $stmt=$staff->prepare("INSERT INTO {$students_db}.finance_notices (title,content,audience,published_by) VALUES (?,?,?,?)");
    if($stmt){$stmt->bind_param('ssss',$t,$c,$a,$uname);if($stmt->execute()){echo json_encode(['success'=>true]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>$stmt->error]);$stmt->close();exit;}
    echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit;
}

if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode([]); exit; }

// -- POST Handlers --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];
    if ($act === 'add_expense' && $staff) {
        $cat=$_POST['category']??'';
        $desc=$_POST['description']??'';
        $amt=(float)($_POST['amount']??0);
        $dt=$_POST['expense_date']??date('Y-m-d');
        $eid='EXP-'.date('Ymd').'-'.mt_rand(1000,9999);
        if($cat&&$desc&&$amt){
            $stmt=$staff->prepare("INSERT INTO expenses (expense_id,description,expense_category,amount,expense_date,status,requested_by) VALUES (?,?,?,?,?,'pending',?)");
            if($stmt){$stmt->bind_param('sssdsi',$eid,$desc,$cat,$amt,$dt,$uid);if($stmt->execute()){fin_success("Expense $eid created.");}else{fin_error('Write failed.');}$stmt->close();}else{fin_error('Write failed.');}
        }else{ fin_error('Required fields missing.'); }
        header('Location: director-finance.php?section=expenditure_monitoring'); exit;
    }
    if ($act === 'approve_expense' && $staff) {
        $id=(int)($_POST['expense_id']??0);
        if($id){ $stmt=$staff->prepare("UPDATE expenses SET status='approved',approved_by=?,approval_date=NOW() WHERE id=? AND status='pending'"); if($stmt){$stmt->bind_param('ii',$uid,$id);$stmt->execute();if($stmt->affected_rows>0){fin_success('Expense approved.');}else{fin_error('Approve failed.');}$stmt->close();}else{fin_error('Approve failed.');} }
        header('Location: director-finance.php?section=expenditure_monitoring'); exit;
    }
    if ($act === 'reject_expense' && $staff) {
        $id=(int)($_POST['expense_id']??0);
        if($id){ $stmt=$staff->prepare("UPDATE expenses SET status='rejected',approved_by=?,approval_date=NOW() WHERE id=? AND status='pending'"); if($stmt){$stmt->bind_param('ii',$uid,$id);$stmt->execute();if($stmt->affected_rows>0){fin_success('Expense rejected.');}else{fin_error('Reject failed.');}$stmt->close();}else{fin_error('Reject failed.');} }
        header('Location: director-finance.php?section=expenditure_monitoring'); exit;
    }
    if ($act === 'create_budget' && $staff) {
        $code=$_POST['budget_code']??'BGT-'.date('Y').'-'.mt_rand(100,999);
        $name=$_POST['budget_name']??'';
        $cat=$_POST['budget_category']??'Operations';
        $dept=$_POST['department']??'';
        $fy=$_POST['fiscal_year']??date('Y');
        $amt=(float)($_POST['allocated_amount']??0);
        if($name&&$amt){
            $stmt=$staff->prepare("INSERT INTO {$students_db}.budget_records (budget_code,budget_name,budget_category,department,fiscal_year,allocated_amount,spent_amount,status,created_by) VALUES (?,?,?,?,?,?,0,'Draft',?)");
            if($stmt){$stmt->bind_param('sssssdi',$code,$name,$cat,$dept,$fy,$amt,$uid);if($stmt->execute()){fin_success("Budget $code created.");}else{fin_error('Write failed.');}$stmt->close();}else{fin_error('Write failed.');}
        }else{ fin_error('Name and amount required.'); }
        header('Location: director-finance.php?section=budget_planning'); exit;
    }
    if ($act === 'approve_budget' && $staff) {
        $bid=(int)($_POST['budget_id']??0);
        if($bid){ $stmt=$staff->prepare("UPDATE {$students_db}.budget_records SET status='Approved',approved_by=? WHERE id=?"); if($stmt){$stmt->bind_param('ii',$uid,$bid);$stmt->execute();if($stmt->affected_rows>0){fin_success('Budget approved.');}else{fin_error('Approve failed.');}$stmt->close();}else{fin_error('Approve failed.');} }
        header('Location: director-finance.php?section=budget_planning'); exit;
    }
    if ($act === 'record_payment' && $staff) {
        $sid=(int)($_POST['student_id']??0); $amt=(float)($_POST['amount']??0);
        $pm=$_POST['payment_method']??'cash';
        $ref='RCT-'.date('Ymd').'-'.mt_rand(1000,9999);
        $payDate = date('Y-m-d');
        if($students&&$sid&&$amt){
            $stmt=$students->prepare("INSERT INTO {$students_db}.payments (payment_reference,student_id,amount_received,payment_method,payment_date,status,processed_by) VALUES (?,?,?,?,?,?,?)");
            if($stmt){$stmt->bind_param('sidsssi',$ref,$sid,$amt,$pm,$payDate,'pending',$uid);if($stmt->execute()){fin_success("Payment $ref recorded.");}else{fin_error('Write failed.');}$stmt->close();}else{fin_error('Write failed.');}
        }else{ fin_error('Student and amount required.'); }
        header('Location: director-finance.php?section=payment_verification'); exit;
    }
    if ($act === 'approve_payment' && $staff) {
        $pid=(int)($_POST['payment_id']??0);
        if($students&&$pid){ $stmt=$students->prepare("UPDATE {$students_db}.payments SET status='approved',verified_by=? WHERE id=? AND status='pending'"); if($stmt){$stmt->bind_param('ii',$uid,$pid);$stmt->execute();if($stmt->affected_rows>0){fin_success('Payment approved.');}else{fin_error('Approve failed.');}$stmt->close();}else{fin_error('Approve failed.');} }
        header('Location: director-finance.php?section=payment_verification'); exit;
    }
    if ($act === 'reject_payment' && $staff) {
        $pid=(int)($_POST['payment_id']??0);
        if($students&&$pid){ $stmt=$students->prepare("UPDATE {$students_db}.payments SET status='rejected' WHERE id=?"); if($stmt){$stmt->bind_param('i',$pid);$stmt->execute();if($stmt->affected_rows>0){fin_success('Payment rejected.');}else{fin_error('Reject failed.');}$stmt->close();}else{fin_error('Reject failed.');} }
        header('Location: director-finance.php?section=payment_verification'); exit;
    }
    if ($act === 'edit_expense' && $staff) {
        $id=(int)($_POST['expense_id']??0);
        $cat=$_POST['category']??'';
        $desc=$_POST['description']??'';
        $amt=(float)($_POST['amount']??0);
        $dt=$_POST['expense_date']??date('Y-m-d');
        if($id&&$cat&&$desc&&$amt){
            $stmt=$staff->prepare("UPDATE expenses SET expense_category=?,description=?,amount=?,expense_date=?,updated_at=NOW() WHERE id=?");
            if($stmt){$stmt->bind_param('ssdsi',$cat,$desc,$amt,$dt,$id);if($stmt->execute()){fin_success("Expense updated.");}else{fin_error('Update failed.');}$stmt->close();}
        }else{ fin_error('Required fields missing.'); }
        header('Location: director-finance.php?section=expenditure_monitoring'); exit;
    }
    if ($act === 'delete_expense' && $staff) {
        $id=(int)($_POST['expense_id']??0);
        if($id){ $stmt=$staff->prepare("DELETE FROM expenses WHERE id=?"); if($stmt){$stmt->bind_param('i',$id);$stmt->execute();if($stmt->affected_rows>0){fin_success('Expense deleted.');}else{fin_error('Delete failed.');}$stmt->close();}else{fin_error('Delete failed.');} }
        header('Location: director-finance.php?section=expenditure_monitoring'); exit;
    }
    if ($act === 'edit_budget' && $staff) {
        $id=(int)($_POST['budget_id']??0);
        $name=$_POST['budget_name']??'';
        $cat=$_POST['budget_category']??'Operations';
        $amt=(float)($_POST['allocated_amount']??0);
        if($id&&$name&&$amt){
            $stmt=$staff->prepare("UPDATE {$students_db}.budget_records SET budget_name=?,budget_category=?,allocated_amount=? WHERE id=?");
            if($stmt){$stmt->bind_param('ssdi',$name,$cat,$amt,$id);if($stmt->execute()){fin_success("Budget updated.");}else{fin_error('Update failed.');}$stmt->close();}
        }else{ fin_error('Required fields missing.'); }
        header('Location: director-finance.php?section=budget_planning'); exit;
    }
    if ($act === 'delete_budget' && $staff) {
        $id=(int)($_POST['budget_id']??0);
        if($id){ $stmt=$staff->prepare("DELETE FROM {$students_db}.budget_records WHERE id=?"); if($stmt){$stmt->bind_param('i',$id);$stmt->execute();if($stmt->affected_rows>0){fin_success('Budget deleted.');}else{fin_error('Delete failed.');}$stmt->close();}else{fin_error('Delete failed.');} }
        header('Location: director-finance.php?section=budget_planning'); exit;
    }
    header('Location: director-finance.php'); exit;
}
$sv = $_SESSION['fin_success'] ?? ''; $ev = $_SESSION['fin_error'] ?? '';
unset($_SESSION['fin_success'], $_SESSION['fin_error']);
?>
<!DOCTYPE html>
<html lang="en"><head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>









.fin-content{margin-left:270px;padding:24px;min-height:100vh}
.scard{background:#fff;border-radius:12px;border:1px solid #e5e7eb;transition:all .2s;height:100%}
.scard:hover{box-shadow:0 4px 16px rgba(0,0,0,.06)}
.scard .sch{background:#f8fafc;padding:14px 20px;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;font-weight:600;color:#1e40af;font-size:14px}
.scard .scb{padding:20px}
.act-item{padding:10px 14px;border-left:3px solid #1e40af;background:#f8fafc;border-radius:0 8px 8px 0;margin-bottom:8px;transition:all .15s}
.act-item:hover{background:#eef2ff}
.act-item .time{font-size:11px;color:#94a3b8}
.kpi-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px;transition:all .25s;display:flex;align-items:center;gap:14px;height:100%}
.kpi-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.06);transform:translateY(-1px)}
.kpi-card .kpi-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.kpi-card .kpi-value{font-size:20px;font-weight:800;color:#0f172a;line-height:1.2}
.kpi-card .kpi-label{font-size:11px;color:#64748b;font-weight:500}
.kpi-card.primary .kpi-icon{background:#dbeafe;color:#1e40af}
.kpi-card.success .kpi-icon{background:#dcfce7;color:#16a34a}
.kpi-card.info .kpi-icon{background:#e0f2fe;color:#0891b2}
.kpi-card.warning .kpi-icon{background:#fef3c7;color:#d97706}
.kpi-card.purple .kpi-icon{background:#f3e8ff;color:#7c3aed}
.kpi-card.danger .kpi-icon{background:#fee2e2;color:#dc2626}
.btn-sec{background:#1e40af;border:2px solid #1e40af;color:#fff;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;transition:all .2s}
.btn-sec:hover{background:#3b82f6;border-color:#3b82f6;color:#fff}
.btn-outline-sec{background:#fff;border:2px solid #1e40af;color:#1e40af;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;transition:all .2s}
.btn-outline-sec:hover{background:#1e40af;color:#fff}
.env-field{background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:13px;transition:border-color .2s}
.env-field:focus{border-color:#1e40af;outline:none;box-shadow:0 0 0 2px rgba(30,64,175,.1)}
.modal-content{max-height:85vh;overflow-y:auto}
@media(max-width:768px){
    .fin-content{margin-left:0!important;padding:12px!important}
    .kpi-card{padding:12px 10px}
    .kpi-card .kpi-value{font-size:16px}
    .kpi-card .kpi-label{font-size:10px}
    .scard .sch{padding:10px 14px;font-size:13px}
    .scard .scb{padding:12px 14px}
    .table-responsive{font-size:11px}
    .btn-sec,.btn-outline-sec{font-size:11px;padding:6px 10px}
    .d-flex.justify-content-between{flex-wrap:wrap;gap:6px}
}
@media(max-width:480px){
    .kpi-card .kpi-value{font-size:14px}
    .scard .sch{font-size:12px;padding:8px 10px}
    .scard .scb{padding:10px}
    .d-grid.gap-2 .btn{font-size:11px;padding:6px 8px}
}
</style>
</head><body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="fin-content dashboard-section active" data-section="finance">
<?php if ($sv): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($sv) ?></div><?php endif; ?>
<?php if ($ev): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($ev) ?></div><?php endif; ?>

<?php if ($view === 'home'): ?>
<?php
$totalRevenue = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$totalExpenses = (float)(($r=$staff->query("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE status IN('approved','paid')"))&&$r?$r->fetch_assoc()['t']:0);
$outstandingFees = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(balance),0) t FROM {$students_db}.student_invoices WHERE status IN('pending','partial','overdue')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$budgetAlloc = (float)(($r=$staff->query("SELECT COALESCE(SUM(allocated_amount),0) t FROM {$students_db}.budget_records WHERE status IN('Approved','Active')"))&&$r?$r->fetch_assoc()['t']:0);
$budgetSpent = (float)(($r=$staff->query("SELECT COALESCE(SUM(spent_amount),0) t FROM {$students_db}.budget_records WHERE status IN('Approved','Active')"))&&$r?$r->fetch_assoc()['t']:0);
$budgetUtil = $budgetAlloc>0?round(($budgetSpent/$budgetAlloc)*100,1):0;
$pendingCount = 0;
$pendingCount += (int)(($r=$staff->query("SELECT COUNT(*)c FROM {$students_db}.budget_approvals WHERE status='pending'"))&&$r?$r->fetch_assoc()['c']:0);
$pendingCount += (int)(($r=$staff->query("SELECT COUNT(*)c FROM {$students_db}.expenditure_approvals WHERE status='pending'"))&&$r?$r->fetch_assoc()['c']:0);
$pendingCount += (int)(($r=$staff->query("SELECT COUNT(*)c FROM {$students_db}.payroll_approvals WHERE status='pending'"))&&$r?$r->fetch_assoc()['c']:0);
$netPos = $totalRevenue - $totalExpenses;
$healthScore = min(100,max(0,round(($totalRevenue>0?($totalRevenue-$totalExpenses)/$totalRevenue*50:0)+max(0,100-$budgetUtil)*0.3+($outstandingFees>0?10:20))));
$recentActs = [];
$r=$staff->query("SELECT activity_description,created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 8"); if($r) while($rw=$r->fetch_assoc()) $recentActs[]=$rw;
if(count($recentActs)<8){ $r=$staff->query("SELECT CONCAT('Expense: ',description) activity_description,created_at FROM expenses ORDER BY created_at DESC LIMIT 4"); if($r) while($rw=$r->fetch_assoc()) $recentActs[]=$rw; }
if(count($recentActs)<8 && $students){ $r=$students->query("SELECT CONCAT('Payment: ',payment_reference) activity_description,created_at FROM {$students_db}.payments ORDER BY created_at DESC LIMIT 4"); if($r) while($rw=$r->fetch_assoc()) $recentActs[]=$rw; }
usort($recentActs,function($a,$b){ return strtotime($b['created_at'])-strtotime($a['created_at']); });
$recentActs = array_slice($recentActs,0,8);
?>
<div class="row g-3 mb-4">
<div class="col-md-4 col-lg-2 col-6"><div class="kpi-card primary"><div class="kpi-icon"><i class="fas fa-arrow-up"></i></div><div><div class="kpi-value"><?= currency($totalRevenue) ?></div><div class="kpi-label">Total Revenue</div></div></div></div>
<div class="col-md-4 col-lg-2 col-6"><div class="kpi-card danger"><div class="kpi-icon"><i class="fas fa-arrow-down"></i></div><div><div class="kpi-value"><?= currency($totalExpenses) ?></div><div class="kpi-label">Total Expenses</div></div></div></div>
<div class="col-md-4 col-lg-2 col-6"><div class="kpi-card info"><div class="kpi-icon"><i class="fas fa-scale-balanced"></i></div><div><div class="kpi-value" style="color:<?= $netPos>=0?'#16a34a':'#dc2626' ?>"><?= currency(abs($netPos)) ?></div><div class="kpi-label"><?= $netPos>=0?'Net Surplus':'Net Deficit' ?></div></div></div></div>
<div class="col-md-4 col-lg-2 col-6"><div class="kpi-card warning"><div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div><div><div class="kpi-value"><?= currency($outstandingFees) ?></div><div class="kpi-label">Outstanding Fees</div></div></div></div>
<div class="col-md-4 col-lg-2 col-6"><div class="kpi-card purple"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><div><div class="kpi-value"><?= $budgetUtil ?>%</div><div class="kpi-label">Budget Utilized</div></div></div></div>
<div class="col-md-4 col-lg-2 col-6"><div class="kpi-card danger"><div class="kpi-icon"><i class="fas fa-clock"></i></div><div><div class="kpi-value"><?= $pendingCount ?></div><div class="kpi-label">Pending Approvals</div></div></div></div>
</div>
<div class="row g-3">
<div class="col-md-8">
<div class="scard"><div class="sch"><i class="fas fa-heartbeat me-2"></i>Financial Health Score</div><div class="scb">
<div class="d-flex align-items-center gap-3">
<div style="width:90px;height:90px;border-radius:50%;background:<?= $healthScore>=70?'#16a34a':($healthScore>=40?'#d97706':'#dc2626') ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:800;flex-shrink:0"><?= $healthScore ?></div>
<div><p class="mb-1 fw-bold">Overall Financial Health</p>
<p class="small text-muted mb-0">Based on revenue vs expenses, budget utilization, and outstanding fees.</p>
<div class="progress mt-2" style="height:8px;max-width:300px"><div class="progress-bar bg-<?= $healthScore>=70?'success':($healthScore>=40?'warning':'danger') ?>" style="width:<?= $healthScore ?>%"></div></div>
</div></div></div></div>
<div class="scard mt-3"><div class="sch"><i class="fas fa-clock me-2"></i>Recent Financial Activities</div><div class="scb">
<?php if($recentActs): foreach($recentActs as $a): ?>
<div class="act-item py-1"><div class="d-flex justify-content-between"><span><i class="fas fa-circle text-primary me-2 small"></i><?= htmlspecialchars(mb_substr($a['activity_description'],0,80)) ?></span><span class="time"><?= date('d M H:i',strtotime($a['created_at'])) ?></span></div></div>
<?php endforeach; else: ?>
<div class="text-muted small">No recent financial activity recorded.</div>
<?php endif; ?>
</div></div>
</div>
<div class="col-md-4">
<div class="scard"><div class="sch"><i class="fas fa-gauge-high me-2"></i>Quick Links</div><div class="scb">
<div class="d-grid gap-2">
<a href="?section=approval_center" class="btn btn-sec w-100"><i class="fas fa-check-double me-1"></i>Approval Center <?= $pendingCount>0?'<span class="badge bg-light text-dark ms-1">'.$pendingCount.'</span>':'' ?></a>
<a href="?section=budget_monitoring" class="btn btn-outline-sec w-100"><i class="fas fa-calculator me-1"></i>Budget Monitoring</a>
<a href="?section=revenue_summary" class="btn btn-outline-sec w-100"><i class="fas fa-money-bill-wave me-1"></i>Revenue Summary</a>
<a href="?section=expenditure_monitoring" class="btn btn-outline-sec w-100"><i class="fas fa-receipt me-1"></i>Expenditure</a>
<a href="?section=payroll_review" class="btn btn-outline-sec w-100"><i class="fas fa-wallet me-1"></i>Payroll Review</a>
<a href="?section=finance_reports" class="btn btn-outline-sec w-100"><i class="fas fa-print me-1"></i>Reports</a>
</div></div></div>
<div class="scard mt-3"><div class="sch"><i class="fas fa-wallet me-2"></i>Net Position</div><div class="scb text-center">
<div class="fs-1 fw-bold" style="color:<?= $netPos>=0?'#16a34a':'#dc2626' ?>"><?= currency(abs($netPos)) ?></div>
<p class="small text-muted"><?= $netPos>=0?'Revenue exceeds expenses':'Expenses exceed revenue' ?> for all time</p>
</div></div>
</div>
</div>
<div class="row mt-3">
  <div class="col-12">
    <div class="scard"><div class="sch"><i class="fas fa-newspaper me-2"></i>News &amp; Announcements</div><div class="scb">
      <?php renderNewsWidget($staff,$website,$uid,$uname,$role,5); ?>
    </div></div>
  </div>
</div>
<?php endif; ?>

<?php if ($view === 'donations'): ?>
<div class="scard"><div class="sch"><i class="fas fa-hand-holding-heart me-2"></i>Website Donations</div><div class="scb">
<?php
if ($website) {
    renderDirectorWebsitePanel($website, ['donations'], 'Donation Submissions');
} else {
    echo '<div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p>Website database unavailable.</p></div>';
}
?>
</div></div>
<?php endif; ?>

<?php if ($view === 'revenue_summary'): ?>
<div class="scard"><div class="sch"><i class="fas fa-money-bill-wave me-2"></i>Revenue Summary</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-3"><input type="date" id="revFrom" class="form-control env-field" value="<?= date('Y-m-01') ?>"></div>
<div class="col-md-3"><input type="date" id="revTo" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-md-2"><button class="btn btn-sec w-100" onclick="loadRevenue()"><i class="fas fa-filter me-1"></i>Filter</button></div>
<div class="col-md-2"><button class="btn btn-outline-sec w-100" onclick="exportTable('revTable')"><i class="fas fa-file-excel me-1"></i>Export</button></div>
<div class="col-md-2"><button class="btn btn-outline-sec w-100" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button></div>
</div>
<div class="table-responsive"><table class="table tb" id="revTable"><thead><tr><th>Receipt</th><th>Student</th><th>Number</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody id="revBody"><tr><td colspan="7" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
function loadRevenue(){
    var f=document.getElementById('revFrom').value,t=document.getElementById('revTo').value;
    var el=document.getElementById('revBody'); el.innerHTML='<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
    fetch('director-finance.php?ajax=revenue_data&from='+f+'&to='+t).then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="7" class="text-center text-muted">No revenue data found.</td></tr>';return;}
        var h=''; var tt=0;
        d.forEach(function(p){tt+=parseFloat(p.amount_received||0);var stCls=p.status==='approved'||p.status==='verified'?'success':p.status==='pending'?'warning text-dark':'secondary';
        h+='<tr><td><code>'+esc(p.payment_reference)+'</code></td><td>'+esc(p.student_name||'-')+'</td><td class="small">'+esc(p.student_number||'')+'</td><td class="fw-bold">'+currency(p.amount_received)+'</td><td>'+esc(p.payment_method)+'</td><td class="small">'+esc(p.payment_date)+'</td><td><span class="badge bg-'+stCls+'">'+esc(p.status)+'</span></td></tr>';});
        h+='<tr class="fw-bold"><td colspan="3">Total</td><td>'+currency(tt)+'</td><td colspan="3"></td></tr>';
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="7" class="text-center text-danger">Failed to load.</td></tr>';});
}
document.addEventListener('DOMContentLoaded',loadRevenue);
</script>
<?php endif; ?>

<?php if ($view === 'revenue_sources'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-pie me-2"></i>Revenue Sources</div><div class="scb">
<?php
$tuitionRev = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(p.amount_received),0) t FROM {$students_db}.payments p JOIN {$students_db}.student_invoices si ON p.invoice_id=si.id WHERE si.tuition_amount>0 AND p.status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$hostelRev = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(p.amount_received),0) t FROM {$students_db}.payments p JOIN {$students_db}.student_invoices si ON p.invoice_id=si.id WHERE si.accommodation_amount>0 AND p.status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$appRev = $website ? (float)(($r=$website->query("SELECT COALESCE(SUM(amount),0) t FROM donations WHERE purpose='application'"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$totalRevenue = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$totalRevCalc = $tuitionRev+$hostelRev+$appRev; $otherRev = max(0,$totalRevenue-$totalRevCalc);
$sources = [['label'=>'Tuition Fees','value'=>$tuitionRev,'color'=>'#1e40af'],['label'=>'Hostel Fees','value'=>$hostelRev,'color'=>'#0891b2'],['label'=>'Application Fees','value'=>$appRev,'color'=>'#d97706'],['label'=>'Other Income','value'=>$otherRev,'color'=>'#7c3aed']];
$maxVal = max(array_column($sources,'value')); if($maxVal<=0)$maxVal=1;
?>
<div class="row g-3">
<?php foreach($sources as $s): $pct = $totalRevenue>0?round(($s['value']/$totalRevenue)*100,1):0; ?>
<div class="col-md-3 col-6">
<div class="border rounded p-3 text-center">
<div style="width:60px;height:60px;border-radius:50%;background:<?= $s['color'] ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;margin:0 auto"><?= $pct ?>%</div>
<h6 class="mt-2 fw-bold"><?= currency($s['value']) ?></h6>
<p class="small text-muted mb-0"><?= $s['label'] ?></p>
<div class="progress mt-2" style="height:6px"><div class="progress-bar" style="width:<?= ($s['value']/$maxVal)*100 ?>%;background:<?= $s['color'] ?>"></div></div>
</div></div>
<?php endforeach; ?>
</div></div></div>
<?php endif; ?>

<?php if ($view === 'revenue_trends'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-line me-2"></i>Revenue Trends</div><div class="scb">
<?php
$monthlyRev = []; $mLabels = [];
for($m=11;$m>=0;$m--){ $dt = date('Y-m',strtotime("-$m months")); $mLabels[]=$dt; $mRev=0; if($students){ $r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed') AND DATE_FORMAT(payment_date,'%Y-%m')='$dt'"); if($r) $mRev=(float)($r->fetch_assoc()['t']??0); } $monthlyRev[]=$mRev; }
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Month</th><th>Revenue</th><th>Trend</th></tr></thead><tbody>
<?php foreach($mLabels as $i=>$l): $mr=$monthlyRev[$i]; $prev=$i>0?$monthlyRev[$i-1]:0; $chg=$prev>0?round(($mr-$prev)/$prev*100,1):0; ?>
<tr><td><?= htmlspecialchars(date('F Y',strtotime($l.'-01'))) ?></td><td class="fw-bold"><?= currency($mr) ?></td><td><span class="badge bg-<?= $chg>=0?'success':'danger' ?>"><?= $chg>=0?'+':'' ?><?= $chg ?>%</span></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'revenue_analysis'): ?>
<?php
$raTuitionRev = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(p.amount_received),0) t FROM {$students_db}.payments p JOIN {$students_db}.student_invoices si ON p.invoice_id=si.id WHERE si.tuition_amount>0 AND p.status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$raHostelRev = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(p.amount_received),0) t FROM {$students_db}.payments p JOIN {$students_db}.student_invoices si ON p.invoice_id=si.id WHERE si.accommodation_amount>0 AND p.status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$raAppRev = $website ? (float)(($r=$website->query("SELECT COALESCE(SUM(amount),0) t FROM donations WHERE purpose='application'"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$raTotalRev = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$raTotCalc = $raTuitionRev+$raHostelRev+$raAppRev; $raOther = max(0,$raTotalRev-$raTotCalc);
$raSources = [['label'=>'Tuition Fees','value'=>$raTuitionRev,'color'=>'#1e40af'],['label'=>'Hostel Fees','value'=>$raHostelRev,'color'=>'#0891b2'],['label'=>'Application Fees','value'=>$raAppRev,'color'=>'#d97706'],['label'=>'Other Income','value'=>$raOther,'color'=>'#7c3aed']];
$raMonthlyRev = []; for($m=11;$m>=0;$m--){ $dt = date('Y-m',strtotime("-$m months")); $mRev=0; if($students){ $r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed') AND DATE_FORMAT(payment_date,'%Y-%m')='$dt'"); if($r) $mRev=(float)($r->fetch_assoc()['t']??0); } $raMonthlyRev[]=$mRev; }
?>
<div class="row g-3">
<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-percent me-2"></i>Revenue Composition</div><div class="scb">
<?php foreach($raSources as $s): $pct = $raTotCalc>0?round(($s['value']/$raTotCalc)*100,1):0; ?>
<div class="d-flex justify-content-between align-items-center mb-2"><span><?= $s['label'] ?></span><span class="fw-bold"><?= $pct ?>%</span></div>
<div class="progress mb-3" style="height:8px"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $s['color'] ?>"></div></div>
<?php endforeach; if(!$raSources): ?><div class="text-muted small">No data available.</div><?php endif; ?>
</div></div></div>
<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-calculator me-2"></i>Revenue Metrics</div><div class="scb">
<div class="mb-3"><label class="fl">Average Monthly Revenue</label><div class="fw-bold fs-5"><?= currency($raTotalRev>0?$raTotalRev/max(1,count($raMonthlyRev)):0) ?></div></div>
<div class="mb-3"><label class="fl">Top Revenue Source</label><div class="fw-bold"><?php $raTopSrc = $raSources[0]??null; echo $raTopSrc ? htmlspecialchars($raTopSrc['label']).' ('.($raTotalRev>0?round(($raTopSrc['value']/$raTotalRev)*100,1):0).'%)' : 'N/A'; ?></div></div>
</div></div></div>
</div>
<?php endif; ?>

<?php if ($view === 'fee_performance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-gauge me-2"></i>Fee Collection Performance</div><div class="scb">
<?php
$totalInvoiced = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(total_amount),0) t FROM {$students_db}.student_invoices WHERE status NOT IN('cancelled','written_off')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$totalCollected = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$collectionRate = $totalInvoiced>0?round(($totalCollected/$totalInvoiced)*100,1):0;
?>
<div class="row g-3">
<div class="col-md-3 col-6"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4 text-primary"><?= currency($totalInvoiced) ?></div><small class="text-muted">Total Invoiced</small></div></div>
<div class="col-md-3 col-6"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4 text-success"><?= currency($totalCollected) ?></div><small class="text-muted">Total Collected</small></div></div>
<div class="col-md-3 col-6"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4 text-warning"><?= currency($totalInvoiced-$totalCollected) ?></div><small class="text-muted">Outstanding</small></div></div>
<div class="col-md-3 col-6"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4" style="color:<?= $collectionRate>=70?'#16a34a':'#dc2626' ?>"><?= $collectionRate ?>%</div><small class="text-muted">Collection Rate</small></div></div>
</div>
<div class="progress mt-3" style="height:12px"><div class="progress-bar bg-<?= $collectionRate>=70?'success':($collectionRate>=40?'warning':'danger') ?>" style="width:<?= $collectionRate ?>%"><?= $collectionRate ?>%</div></div>
</div></div>
<?php endif; ?>

<?php if ($view === 'fee_collection'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-invoice me-2"></i>Fee Collection Details</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-3"><input type="date" id="fcFrom" class="form-control env-field" value="<?= date('Y-m-01') ?>"></div>
<div class="col-md-3"><input type="date" id="fcTo" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-md-2"><button class="btn btn-sec w-100" onclick="loadFeeCollection()"><i class="fas fa-filter me-1"></i>Filter</button></div>
</div>
<div class="table-responsive"><table class="table tb" id="fcTable"><thead><tr><th>Student</th><th>Number</th><th>Program</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody id="fcBody"><tr><td colspan="7" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
function loadFeeCollection(){
    var f=document.getElementById('fcFrom').value,t=document.getElementById('fcTo').value;
    var el=document.getElementById('fcBody'); el.innerHTML='<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>';
    fetch('director-finance.php?ajax=fee_collection_data&from='+f+'&to='+t).then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="7" class="text-center text-muted">No data.</td></tr>';return;}
        var h='';d.forEach(function(si){var stCls=si.status==='paid'||si.status==='completed'?'success':si.status==='partial'?'warning text-dark':si.status==='overdue'?'danger':'secondary';
        h+='<tr><td>'+esc(si.student_name||'-')+'</td><td class="small">'+esc(si.student_number||'')+'</td><td>'+esc(si.program||'-')+'</td><td>'+currency(si.total_amount)+'</td><td>'+currency(si.amount_paid||0)+'</td><td class="fw-bold">'+currency(si.balance)+'</td><td><span class="badge bg-'+stCls+'">'+esc(si.status)+'</span></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="7" class="text-center text-danger">Failed.</td></tr>';});
}
document.addEventListener('DOMContentLoaded',loadFeeCollection);
</script>
<?php endif; ?>

<?php if ($view === 'outstanding_balances'): ?>
<div class="scard"><div class="sch"><i class="fas fa-exclamation-circle me-2"></i>Outstanding Balances</div><div class="scb">
<?php
$outstandingList = []; if($students){ $r=$students->query("SELECT si.*,s.full_name student_name,s.student_number,s.program,s.phone FROM {$students_db}.student_invoices si LEFT JOIN {$students_db}.students s ON si.student_id=s.id WHERE si.balance>0 AND si.status IN('pending','partial','overdue') ORDER BY si.balance DESC LIMIT 100"); if($r) while($rw=$r->fetch_assoc()) $outstandingList[]=$rw; }
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Number</th><th>Program</th><th>Phone</th><th>Balance</th><th>Status</th></tr></thead><tbody>
<?php if($outstandingList): foreach($outstandingList as $o): ?>
<tr><td><?= htmlspecialchars($o['student_name']??'-') ?></td><td class="small"><?= htmlspecialchars($o['student_number']??'') ?></td><td><?= htmlspecialchars($o['program']??'-') ?></td><td><?= htmlspecialchars($o['phone']??'-') ?></td><td class="fw-bold text-danger"><?= currency($o['balance']) ?></td><td><span class="badge bg-<?= $o['status']==='overdue'?'danger':($o['status']==='partial'?'warning text-dark':'secondary') ?>"><?= htmlspecialchars($o['status']) ?></span></td></tr>
<?php endforeach; else: ?>
<tr><td colspan="6" class="text-center text-muted">No outstanding balances.</td></tr>
<?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'clearance_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-circle me-2"></i>Finance Clearance Monitoring</div><div class="scb">
<p class="text-muted small">Monitor student finance clearance status. Students with zero balance are cleared.</p>
<?php
$clearList = []; if($students){ $r=$students->query("SELECT s.id,s.full_name,s.student_number,s.program,s.status,COALESCE((SELECT SUM(balance) FROM {$students_db}.student_invoices WHERE student_id=s.id AND status IN('pending','partial','overdue')),0) balance FROM {$students_db}.students s WHERE s.status='Active' ORDER BY balance DESC LIMIT 50"); if($r) while($rw=$r->fetch_assoc()) $clearList[]=$rw; }
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Number</th><th>Program</th><th>Balance</th><th>Cleared</th></tr></thead><tbody>
<?php if($clearList): foreach($clearList as $c): $cleared = (float)$c['balance']<=0; ?>
<tr><td><?= htmlspecialchars($c['full_name']??'-') ?></td><td class="small"><?= htmlspecialchars($c['student_number']??'') ?></td><td><?= htmlspecialchars($c['program']??'-') ?></td><td class="fw-bold"><?= currency($c['balance']) ?></td><td><?php if($cleared): ?><span class="badge bg-success"><i class="fas fa-check me-1"></i>Cleared</span><?php else: ?><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Not Cleared</span><?php endif; ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="5" class="text-center text-muted">No data.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'payment_verification'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-double me-2"></i>Payment Verification</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-3"><select id="pvStatus" class="form-select env-field" onchange="loadPayments()"><option value="">All Status</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select></div>
<div class="col-md-2"><button class="btn btn-sec w-100" data-bs-toggle="modal" data-bs-target="#payModal"><i class="fas fa-plus me-1"></i>Record Payment</button></div>
</div>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody id="pvBody"><tr><td colspan="7" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
function loadPayments(){
    var st=document.getElementById('pvStatus').value;
    var el=document.getElementById('pvBody'); el.innerHTML='<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>';
    fetch('director-finance.php?ajax=payment_list&status='+st).then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="7" class="text-center text-muted">No payments.</td></tr>';return;}
        var h='';d.forEach(function(p){var stCls=p.status==='approved'?'success':p.status==='pending'?'warning text-dark':'danger';
        h+='<tr><td><code>'+esc(p.payment_reference)+'</code></td><td>'+esc(p.student_name||'-')+'</td><td class="fw-bold">'+currency(p.amount_received)+'</td><td>'+esc(p.payment_method)+'</td><td class="small">'+esc(p.payment_date)+'</td><td><span class="badge bg-'+stCls+'">'+esc(p.status)+'</span></td><td>';
        if(p.status==='pending'){h+='<form method="POST" class="d-inline"><input type="hidden" name="action" value="approve_payment"><input type="hidden" name="payment_id" value="'+p.id+'"><button class="btn btn-sm btn-outline-success me-1" onclick="return confirm(\'Approve?\')"><i class="fas fa-check"></i></button></form><form method="POST" class="d-inline"><input type="hidden" name="action" value="reject_payment"><input type="hidden" name="payment_id" value="'+p.id+'"><button class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Reject?\')"><i class="fas fa-times"></i></button></form>';}
        h+='</td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="7" class="text-center text-danger">Failed.</td></tr>';});
}
document.addEventListener('DOMContentLoaded',loadPayments);
</script>
<?php endif; ?>

<?php if ($view === 'high_value'): ?>
<div class="scard"><div class="sch"><i class="fas fa-gem me-2"></i>High Value Payments</div><div class="scb">
<?php
$highValList = []; if($students){ $r=$students->query("SELECT p.*,s.full_name student_name FROM {$students_db}.payments p LEFT JOIN {$students_db}.students s ON p.student_id=s.id WHERE p.amount_received>=1000000 ORDER BY p.amount_received DESC LIMIT 100"); if($r) while($rw=$r->fetch_assoc()) $highValList[]=$rw; }
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>
<?php if($highValList): foreach($highValList as $p): ?>
<tr><td><code><?= htmlspecialchars($p['payment_reference']) ?></code></td><td><?= htmlspecialchars($p['student_name']??'-') ?></td><td class="fw-bold text-primary"><?= currency($p['amount_received']) ?></td><td><?= htmlspecialchars($p['payment_method']) ?></td><td class="small"><?= $p['payment_date'] ?></td><td><span class="badge bg-<?= $p['status']==='approved'?'success':($p['status']==='pending'?'warning text-dark':'danger') ?>"><?= $p['status'] ?></span></td></tr>
<?php endforeach; else: ?><tr><td colspan="6" class="text-center text-muted">No high-value payments.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'suspicious'): ?>
<div class="scard"><div class="sch"><i class="fas fa-shield-exclamation me-2"></i>Suspicious Transactions</div><div class="scb">
<?php
$suspList = []; if($students){ $r=$students->query("SELECT p.*,s.full_name student_name FROM {$students_db}.payments p LEFT JOIN {$students_db}.students s ON p.student_id=s.id WHERE p.amount_received>5000000 OR p.status='rejected' OR p.payment_method='cash' ORDER BY p.amount_received DESC LIMIT 100"); if($r) while($rw=$r->fetch_assoc()) $suspList[]=$rw; }
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Reason</th></tr></thead><tbody>
<?php if($suspList): foreach($suspList as $p): $reason = $p['amount_received']>5000000?'High amount':($p['status']==='rejected'?'Rejected':'Cash transaction'); ?>
<tr><td><code><?= htmlspecialchars($p['payment_reference']) ?></code></td><td><?= htmlspecialchars($p['student_name']??'-') ?></td><td class="fw-bold"><?= currency($p['amount_received']) ?></td><td><?= htmlspecialchars($p['payment_method']) ?></td><td class="small"><?= $p['payment_date'] ?></td><td><span class="badge bg-warning text-dark"><?= $reason ?></span></td></tr>
<?php endforeach; else: ?><tr><td colspan="6" class="text-center text-muted">No suspicious transactions.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'failed_payments'): ?>
<div class="scard"><div class="sch"><i class="fas fa-times-circle me-2"></i>Failed/Rejected Payments</div><div class="scb">
<?php
$failList = []; if($students){ $r=$students->query("SELECT p.*,s.full_name student_name FROM {$students_db}.payments p LEFT JOIN {$students_db}.students s ON p.student_id=s.id WHERE p.status='rejected' ORDER BY p.payment_date DESC LIMIT 100"); if($r) while($rw=$r->fetch_assoc()) $failList[]=$rw; }
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>
<?php if($failList): foreach($failList as $p): ?>
<tr><td><code><?= htmlspecialchars($p['payment_reference']) ?></code></td><td><?= htmlspecialchars($p['student_name']??'-') ?></td><td class="fw-bold text-danger"><?= currency($p['amount_received']) ?></td><td><?= htmlspecialchars($p['payment_method']) ?></td><td class="small"><?= $p['payment_date'] ?></td><td><span class="badge bg-danger">Rejected</span></td></tr>
<?php endforeach; else: ?><tr><td colspan="6" class="text-center text-muted">No rejected payments.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'budget_planning'): ?>
<div class="scard"><div class="sch"><i class="fas fa-pen-ruler me-2"></i>Budget Planning</div><div class="scb">
<button class="btn btn-sec mb-3" data-bs-toggle="modal" data-bs-target="#budgetModal"><i class="fas fa-plus me-1"></i>New Budget</button>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Code</th><th>Name</th><th>Category</th><th>FY</th><th>Allocated</th><th>Spent</th><th>Remaining</th><th>Util%</th><th>Status</th><th></th></tr></thead><tbody id="budgetBody"><tr><td colspan="10" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
function loadBudgets(){
    var el=document.getElementById('budgetBody'); if(!el)return;
    el.innerHTML='<tr><td colspan="10" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>';
    fetch('director-finance.php?ajax=budget_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="10" class="text-center text-muted">No budgets.</td></tr>';return;}
        var h='';d.forEach(function(b){var rem=b.allocated_amount-b.spent_amount;var util=b.allocated_amount>0?round((b.spent_amount/b.allocated_amount)*100,1):0;var stCls=b.status==='Approved'?'success':b.status==='Active'?'info':b.status==='Draft'?'warning text-dark':'secondary';
        h+='<tr><td><code>'+esc(b.budget_code)+'</code></td><td>'+esc(b.budget_name)+'</td><td>'+esc(b.budget_category)+'</td><td>'+esc(b.fiscal_year)+'</td><td>'+currency(b.allocated_amount)+'</td><td>'+currency(b.spent_amount)+'</td><td>'+currency(rem)+'</td><td>'+util+'%</td><td><span class="badge bg-'+stCls+'">'+esc(b.status)+'</span></td><td>'+(b.status==='Draft'?'<form method="POST" class="d-inline"><input type="hidden" name="action" value="approve_budget"><input type="hidden" name="budget_id" value="'+b.id+'"><button class="btn btn-sm btn-outline-success" onclick="return confirm(\'Approve budget?\')"><i class="fas fa-check"></i></button></form>':'')+'</td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="10" class="text-center text-danger">Failed.</td></tr>';});
}
function round(v,p){var m=Math.pow(10,p);return Math.round(v*m)/m;}
document.addEventListener('DOMContentLoaded',loadBudgets);
</script>
<?php endif; ?>

<?php if ($view === 'budget_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-simple me-2"></i>Budget Monitoring</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Budget</th><th>Allocated</th><th>Spent</th><th>Remaining</th><th>Utilization</th><th>Status</th></tr></thead><tbody id="bmBody"><tr><td colspan="6" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('bmBody');if(!el)return;
    fetch('director-finance.php?ajax=budget_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="6" class="text-center text-muted">No budgets.</td></tr>';return;}
        var h='';d.forEach(function(b){var rem=b.allocated_amount-b.spent_amount;var util=b.allocated_amount>0?round((b.spent_amount/b.allocated_amount)*100,1):0;var stCls=b.status==='Approved'||b.status==='Active'?'success':b.status==='Draft'?'warning text-dark':'secondary';
        h+='<tr><td><strong>'+esc(b.budget_name)+'</strong><br><small class="text-muted">'+esc(b.budget_category)+'</small></td><td>'+currency(b.allocated_amount)+'</td><td>'+currency(b.spent_amount)+'</td><td>'+currency(rem)+'</td><td><div class="progress" style="height:8px"><div class="progress-bar bg-'+(util>90?'danger':util>70?'warning':'success')+'" style="width:'+util+'%">'+util+'%</div></div></td><td><span class="badge bg-'+stCls+'">'+esc(b.status)+'</span></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="6" class="text-center text-danger">Failed.</td></tr>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'budget_variance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-bar me-2"></i>Budget vs Actual</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Budget</th><th>Allocated</th><th>Spent</th><th>Variance</th><th>Variance %</th></tr></thead><tbody id="bvBody"><tr><td colspan="5" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('bvBody');if(!el)return;
    fetch('director-finance.php?ajax=budget_variance_data').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="5" class="text-center text-muted">No data.</td></tr>';return;}
        var h='';d.forEach(function(b){var varAmt=b.allocated_amount-b.spent_amount;var varPct=b.allocated_amount>0?round((varAmt/b.allocated_amount)*100,1):0;
        h+='<tr><td><strong>'+esc(b.budget_name)+'</strong></td><td>'+currency(b.allocated_amount)+'</td><td>'+currency(b.spent_amount)+'</td><td class="fw-bold '+(varAmt>=0?'text-success':'text-danger')+'">'+currency(Math.abs(varAmt))+'</td><td><span class="badge bg-'+(varPct>20?'success':varPct>0?'warning':'danger')+'">'+varPct+'%</span></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="5" class="text-center text-danger">Failed.</td></tr>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'budget_approvals'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-circle me-2"></i>Budget Approvals</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Request</th><th>Amount</th><th>Requester</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody id="baBody"><tr><td colspan="6" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('baBody');if(!el)return;
    fetch('director-finance.php?ajax=approval_list&type=budget').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="6" class="text-center text-muted">No pending budget approvals.</td></tr>';return;}
        var h='';d.forEach(function(a){var stCls=a.status==='pending'?'warning text-dark':a.status==='approved'?'success':a.status==='rejected'?'danger':'info';
        h+='<tr><td>'+esc(a.description||'Budget Request')+'</td><td class="fw-bold">'+currency(a.amount)+'</td><td>ID:'+a.requested_by+'</td><td><span class="badge bg-'+stCls+'">'+esc(a.status)+'</span></td><td class="small">'+esc(a.created_at)+'</td><td><button class="btn btn-sm btn-outline-success me-1" onclick="approvalAction('+a.id+',\'budget_approvals\',\'approved\')"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-outline-danger" onclick="approvalAction('+a.id+',\'budget_approvals\',\'rejected\')"><i class="fas fa-times"></i></button></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="6" class="text-center text-danger">Failed.</td></tr>';});
});
function approvalAction(id,tbl,st){
    if(!confirm('Confirm '+st+'?'))return;
    var fd=new FormData();fd.append('id',id);fd.append('table',tbl);fd.append('status',st);fd.append('comments','');fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=submit_approval_action',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(d.success)location.reload();else alert('Failed');}).catch(function(){alert('Error');});
}
</script>
<?php endif; ?>

<?php if ($view === 'budget_adjustments'): ?>
<div class="scard"><div class="sch"><i class="fas fa-sliders me-2"></i>Budget Adjustments</div><div class="scb">
<p class="text-muted small">Request adjustments to existing budgets.</p>
<div class="row g-3">
<div class="col-md-6">
<form method="POST">
<input type="hidden" name="action" value="create_budget_adjustment">
<div class="mb-3"><label class="fl">Budget</label><select name="budget_id" class="form-select env-field">
<?php $r=$staff->query("SELECT id,budget_name,budget_code FROM {$students_db}.budget_records WHERE status IN('Approved','Active')"); if($r) while($b=$r->fetch_assoc()) echo '<option value="'.$b['id'].'">'.htmlspecialchars($b['budget_code'].' - '.$b['budget_name']).'</option>'; ?>
</select></div>
<div class="mb-3"><label class="fl">Adjustment Amount</label><input type="number" name="adjustment_amount" class="form-control env-field" step="0.01" required></div>
<div class="mb-3"><label class="fl">Reason</label><textarea name="reason" class="form-control env-field" rows="3" required></textarea></div>
<button type="submit" class="btn btn-sec">Submit Adjustment</button>
</form>
</div>
<div class="col-md-6"><div class="text-muted small">Adjustment history will appear here.</div></div>
</div></div></div>
<?php endif; ?>

<?php if ($view === 'budget_requests'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-invoice me-2"></i>Budget Requests</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Request</th><th>Amount</th><th>Department</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php
$brList=[];$r=$staff->query("SELECT * FROM {$students_db}.budget_approvals WHERE request_type='budget' ORDER BY created_at DESC LIMIT 50"); if($r) while($rw=$r->fetch_assoc()) $brList[]=$rw;
if($brList): foreach($brList as $b): ?>
<tr><td><?= htmlspecialchars(mb_substr($b['description']??'',0,60)) ?></td><td class="fw-bold"><?= currency($b['amount']) ?></td><td><?= $b['requested_by'] ?></td><td><span class="badge bg-<?= $b['status']==='approved'?'success':($b['status']==='pending'?'warning text-dark':($b['status']==='rejected'?'danger':'info')) ?>"><?= htmlspecialchars($b['status']) ?></span></td><td class="small"><?= $b['created_at'] ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="5" class="text-center text-muted">No budget requests.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'expenditure_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-receipt me-2"></i>Expenditure Monitoring</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-2"><select id="expStatus" class="form-select env-field" onchange="loadExpenses()"><option value="">All</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="paid">Paid</option><option value="rejected">Rejected</option></select></div>
<div class="col-md-2"><button class="btn btn-sec w-100" data-bs-toggle="modal" data-bs-target="#expModal"><i class="fas fa-plus me-1"></i>Add Expense</button></div>
</div>
<div class="table-responsive"><table class="table tb"><thead><tr><th>ID</th><th>Category</th><th>Description</th><th>Amount</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody id="expBody"><tr><td colspan="7" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
function loadExpenses(){
    var st=document.getElementById('expStatus').value;
    var el=document.getElementById('expBody'); el.innerHTML='<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>';
    fetch('director-finance.php?ajax=expenditure_list&status='+st).then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="7" class="text-center text-muted">No expenses.</td></tr>';return;}
        var h='';d.forEach(function(e){var stCls=e.status==='approved'||e.status==='paid'?'success':e.status==='pending'?'warning text-dark':'danger';
        h+='<tr><td><code>'+esc(e.expense_id)+'</code></td><td>'+esc(e.expense_category)+'</td><td>'+esc(mbSubstr(e.description,50))+'</td><td class="fw-bold">'+currency(e.amount)+'</td><td class="small">'+esc(e.expense_date)+'</td><td><span class="badge bg-'+stCls+'">'+esc(e.status)+'</span></td><td>';
        if(e.status==='pending'){h+='<form method="POST" class="d-inline"><input type="hidden" name="action" value="approve_expense"><input type="hidden" name="expense_id" value="'+e.id+'"><button class="btn btn-sm btn-outline-success me-1" onclick="return confirm(\'Approve?\')"><i class="fas fa-check"></i></button></form><form method="POST" class="d-inline"><input type="hidden" name="action" value="reject_expense"><input type="hidden" name="expense_id" value="'+e.id+'"><button class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Reject?\')"><i class="fas fa-times"></i></button></form>';}
        h+='</td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="7" class="text-center text-danger">Failed.</td></tr>';});
}
document.addEventListener('DOMContentLoaded',loadExpenses);
</script>
<?php endif; ?>

<?php if ($view === 'dept_expenditures'): ?>
<div class="scard"><div class="sch"><i class="fas fa-building me-2"></i>Department Expenditures</div><div class="scb">
<?php
$deptExps = []; $r=$staff->query("SELECT department,expense_category,COALESCE(SUM(amount),0) total,COUNT(*) cnt FROM expenses WHERE status IN('approved','paid') GROUP BY department,expense_category ORDER BY total DESC"); if($r) while($rw=$r->fetch_assoc()) $deptExps[]=$rw;
$depts = []; foreach($deptExps as $d){ $depts[$d['department']??'General'][] = $d; }
?>
<?php foreach($depts as $dept=>$items): $deptTotal = array_sum(array_column($items,'total')); ?>
<div class="mb-3"><h6 class="fw-bold"><?= htmlspecialchars($dept) ?> <small class="text-muted">(Total: <?= currency($deptTotal) ?>)</small></h6>
<?php foreach($items as $i): $pct = $deptTotal>0?round(($i['total']/$deptTotal)*100,1):0; ?>
<div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($i['expense_category']) ?></span><span><?= currency($i['total']) ?> (<?= $pct ?>%)</span></div>
<div class="progress mb-2" style="height:4px"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; ?>
</div>
<?php endforeach; if(!$depts): ?><div class="text-muted small">No departmental expenditure data.</div><?php endif; ?>
</div></div>
<?php endif; ?>

<?php if ($view === 'operating_expenses'): ?>
<div class="scard"><div class="sch"><i class="fas fa-cogs me-2"></i>Operating Expenses</div><div class="scb">
<?php
$opExps = []; $r=$staff->query("SELECT expense_category,COALESCE(SUM(amount),0) total,COUNT(*) cnt FROM expenses WHERE status IN('approved','paid') AND expense_category IN('Utilities','Office Supplies','Travel','Maintenance','Communication','Insurance') GROUP BY expense_category ORDER BY total DESC"); if($r) while($rw=$r->fetch_assoc()) $opExps[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Category</th><th>Total</th><th>Count</th><th>Average</th></tr></thead><tbody>
<?php if($opExps): foreach($opExps as $o): ?>
<tr><td><?= htmlspecialchars($o['expense_category']) ?></td><td class="fw-bold"><?= currency($o['total']) ?></td><td><?= $o['cnt'] ?></td><td><?= $o['cnt']>0?currency($o['total']/$o['cnt']):currency(0) ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="4" class="text-center text-muted">No operating expenses.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'capital_expenses'): ?>
<div class="scard"><div class="sch"><i class="fas fa-building-columns me-2"></i>Capital Expenses</div><div class="scb">
<?php
$capExps = []; $r=$staff->query("SELECT expense_category,COALESCE(SUM(amount),0) total,COUNT(*) cnt FROM expenses WHERE status IN('approved','paid') AND expense_category IN('Equipment','Construction','Renovation','Vehicles','Furniture','IT Infrastructure') GROUP BY expense_category ORDER BY total DESC"); if($r) while($rw=$r->fetch_assoc()) $capExps[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Category</th><th>Total</th><th>Count</th></tr></thead><tbody>
<?php if($capExps): foreach($capExps as $o): ?>
<tr><td><?= htmlspecialchars($o['expense_category']) ?></td><td class="fw-bold"><?= currency($o['total']) ?></td><td><?= $o['cnt'] ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="3" class="text-center text-muted">No capital expenses.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'expenditure_approvals'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-circle me-2"></i>Expenditure Approvals</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Description</th><th>Amount</th><th>Requester</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody id="eaBody"><tr><td colspan="6" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('eaBody');if(!el)return;
    fetch('director-finance.php?ajax=approval_list&type=expenditure').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="6" class="text-center text-muted">No pending expenditure approvals.</td></tr>';return;}
        var h='';d.forEach(function(a){var stCls=a.status==='pending'?'warning text-dark':a.status==='approved'?'success':a.status==='rejected'?'danger':'info';
        h+='<tr><td>'+esc(a.description||'Expenditure Request')+'</td><td class="fw-bold">'+currency(a.amount)+'</td><td>ID:'+a.requested_by+'</td><td><span class="badge bg-'+stCls+'">'+esc(a.status)+'</span></td><td class="small">'+esc(a.created_at)+'</td><td><button class="btn btn-sm btn-outline-success me-1" onclick="approvalAction('+a.id+',\'expenditure_approvals\',\'approved\')"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-outline-danger" onclick="approvalAction('+a.id+',\'expenditure_approvals\',\'rejected\')"><i class="fas fa-times"></i></button></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="6" class="text-center text-danger">Failed.</td></tr>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'procurement_finance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-cart-shopping me-2"></i>Procurement Finance Overview</div><div class="scb">
<p class="text-muted small">Monitor procurement requests and related financial commitments.</p>
<div class="table-responsive"><table class="table tb"><thead><tr><th>PR#</th><th>Title</th><th>Amount</th><th>Department</th><th>Supplier</th><th>Status</th></tr></thead><tbody id="procBody"><tr><td colspan="6" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('procBody');if(!el)return;
    fetch('director-finance.php?ajax=procurement_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="6" class="text-center text-muted">No procurement requests.</td></tr>';return;}
        var h='';d.forEach(function(p){var stCls=p.status==='approved'?'success':p.status==='pending'?'warning text-dark':p.status==='rejected'?'danger':'secondary';
        h+='<tr><td><code>'+esc(p.pr_number)+'</code></td><td>'+esc(p.title)+'</td><td class="fw-bold">'+currency(p.amount)+'</td><td>'+esc(p.department||'-')+'</td><td>'+esc(p.supplier_name||'-')+'</td><td><span class="badge bg-'+stCls+'">'+esc(p.status)+'</span></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="6" class="text-center text-danger">Failed.</td></tr>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'payroll_review'): ?>
<div class="scard"><div class="sch"><i class="fas fa-wallet me-2"></i>Payroll Review</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Staff</th><th>Position</th><th>Department</th><th>Basic Salary</th><th>Allowances</th><th>Deductions</th><th>Net</th></tr></thead><tbody id="prBody"><tr><td colspan="7" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('prBody');if(!el)return;
    fetch('director-finance.php?ajax=payroll_data').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="7" class="text-center text-muted">No payroll data.</td></tr>';return;}
        var h='';d.forEach(function(s){h+='<tr><td><strong>'+esc(s.staff_name||'-')+'</strong></td><td>'+esc(s.position||'-')+'</td><td>'+esc(s.department||'-')+'</td><td>'+currency(s.basic_salary||0)+'</td><td>'+currency(s.total_allowances||0)+'</td><td>'+currency(s.total_deductions||0)+'</td><td class="fw-bold">'+currency(s.net_salary||0)+'</td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="7" class="text-center text-danger">Failed.</td></tr>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'salary_analysis'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-bar me-2"></i>Salary Analysis</div><div class="scb">
<?php
$salStats = []; $r=$staff->query("SELECT st.department,COUNT(*) cnt,COALESCE(SUM(ss.basic_salary),0) total_basic,COALESCE(SUM(ss.net_salary),0) total_net FROM staff st LEFT JOIN salary_structures ss ON st.id=ss.staff_id AND ss.status='active' WHERE st.status='Active' GROUP BY st.department ORDER BY total_net DESC"); if($r) while($rw=$r->fetch_assoc()) $salStats[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Department</th><th>Staff Count</th><th>Total Basic</th><th>Total Net</th><th>Avg Net</th></tr></thead><tbody>
<?php if($salStats): foreach($salStats as $s): ?>
<tr><td><?= htmlspecialchars($s['department']??'General') ?></td><td><?= $s['cnt'] ?></td><td><?= currency($s['total_basic']) ?></td><td class="fw-bold"><?= currency($s['total_net']) ?></td><td><?= $s['cnt']>0?currency($s['total_net']/$s['cnt']):currency(0) ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="5" class="text-center text-muted">No data.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'allowance_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Allowance Monitoring</div><div class="scb">
<?php
$allowList = []; $r=$staff->query("SELECT ss.*,st.full_name staff_name,st.department FROM salary_structures ss LEFT JOIN staff st ON ss.staff_id=st.id WHERE ss.total_allowances>0 AND ss.status='active' ORDER BY ss.total_allowances DESC"); if($r) while($rw=$r->fetch_assoc()) $allowList[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Staff</th><th>Department</th><th>Total Allowances</th></tr></thead><tbody>
<?php if($allowList): foreach($allowList as $a): ?>
<tr><td><?= htmlspecialchars($a['staff_name']??'-') ?></td><td><?= htmlspecialchars($a['department']??'-') ?></td><td class="fw-bold"><?= currency($a['total_allowances']) ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="3" class="text-center text-muted">No allowance data.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'deduction_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-minus-circle me-2"></i>Deduction Monitoring</div><div class="scb">
<?php
$dedList = []; $r=$staff->query("SELECT ss.*,st.full_name staff_name,st.department FROM salary_structures ss LEFT JOIN staff st ON ss.staff_id=st.id WHERE ss.total_deductions>0 AND ss.status='active' ORDER BY ss.total_deductions DESC"); if($r) while($rw=$r->fetch_assoc()) $dedList[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Staff</th><th>Department</th><th>Total Deductions</th></tr></thead><tbody>
<?php if($dedList): foreach($dedList as $a): ?>
<tr><td><?= htmlspecialchars($a['staff_name']??'-') ?></td><td><?= htmlspecialchars($a['department']??'-') ?></td><td class="fw-bold"><?= currency($a['total_deductions']) ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="3" class="text-center text-muted">No deduction data.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'payroll_approvals'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-circle me-2"></i>Payroll Approvals</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Request</th><th>Amount</th><th>Requester</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody id="paBody"><tr><td colspan="6" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('paBody');if(!el)return;
    fetch('director-finance.php?ajax=approval_list&type=payroll').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="6" class="text-center text-muted">No pending payroll approvals.</td></tr>';return;}
        var h='';d.forEach(function(a){var stCls=a.status==='pending'?'warning text-dark':a.status==='approved'?'success':a.status==='rejected'?'danger':'info';
        h+='<tr><td>'+esc(a.description||'Payroll Request')+'</td><td class="fw-bold">'+currency(a.amount)+'</td><td>ID:'+a.requested_by+'</td><td><span class="badge bg-'+stCls+'">'+esc(a.status)+'</span></td><td class="small">'+esc(a.created_at)+'</td><td><button class="btn btn-sm btn-outline-success me-1" onclick="approvalAction('+a.id+',\'payroll_approvals\',\'approved\')"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-outline-danger" onclick="approvalAction('+a.id+',\'payroll_approvals\',\'rejected\')"><i class="fas fa-times"></i></button></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="6" class="text-center text-danger">Failed.</td></tr>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'payroll_history'): ?>
<div class="scard"><div class="sch"><i class="fas fa-history me-2"></i>Payroll History</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Staff</th><th>Period</th><th>Basic</th><th>Net</th><th>Status</th><th>Date</th></tr></thead><tbody id="phBody"><tr><td colspan="6" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('phBody');if(!el)return;
    fetch('director-finance.php?ajax=payroll_history_data').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="6" class="text-center text-muted">No payroll history.</td></tr>';return;}
        var h='';d.forEach(function(p){h+='<tr><td>'+esc(p.staff_name||'-')+'</td><td>'+esc(p.pay_period||'-')+'</td><td>'+currency(p.basic_salary||0)+'</td><td class="fw-bold">'+currency(p.net_salary||0)+'</td><td><span class="badge bg-'+((p.status||'')==='paid'?'success':'info')+'">'+esc(p.status||'processed')+'</span></td><td class="small">'+esc(p.created_at)+'</td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="6" class="text-center text-danger">Failed.</td></tr>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'payroll_audit'): ?>
<div class="scard"><div class="sch"><i class="fas fa-search me-2"></i>Payroll Audit Trail</div><div class="scb">
<?php
$auditLogs = []; $r=$staff->query("SELECT * FROM staff_activity_log WHERE activity_type IN('payroll','salary') ORDER BY created_at DESC LIMIT 100"); if($r) while($rw=$r->fetch_assoc()) $auditLogs[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Action</th><th>Staff ID</th><th>Date</th></tr></thead><tbody>
<?php if($auditLogs): foreach($auditLogs as $l): ?>
<tr><td><?= htmlspecialchars(mb_substr($l['activity_description'],0,80)) ?></td><td><?= $l['staff_id'] ?></td><td class="small"><?= $l['created_at'] ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="3" class="text-center text-muted">No audit logs.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'staff_cost_analytics'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-pie me-2"></i>Staff Cost Analytics</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Department</th><th>Staff Count</th><th>Total Salary Cost</th></tr></thead><tbody id="scBody"><tr><td colspan="3" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('scBody');if(!el)return;
    fetch('director-finance.php?ajax=staff_cost_data').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="3" class="text-center text-muted">No data.</td></tr>';return;}
        var h='';d.forEach(function(s){h+='<tr><td>'+esc(s.department||'General')+'</td><td>'+s.staff_count+'</td><td class="fw-bold">'+currency(s.total_salary)+'</td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="3" class="text-center text-danger">Failed.</td></tr>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'payroll_forecasting'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-simple me-2"></i>Payroll Forecasting</div><div class="scb">
<?php
$currentTotal = (float)(($r=$staff->query("SELECT COALESCE(SUM(net_salary),0) t FROM salary_structures WHERE status='active'"))&&$r?$r->fetch_assoc()['t']:0);
$staffCount = (int)(($r=$staff->query("SELECT COUNT(*)c FROM staff WHERE status='Active'"))&&$r?$r->fetch_assoc()['c']:0);
$projectedAnnual = $currentTotal * 12;
$growthRate = 0.05; $nextYear = $projectedAnnual * (1+$growthRate);
?>
<div class="row g-3">
<div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4 text-primary"><?= currency($currentTotal) ?></div><small>Monthly Payroll</small></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4 text-info"><?= currency($projectedAnnual) ?></div><small>Annual Projected</small></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4 text-warning"><?= currency($nextYear) ?></div><small>Next Year (5% growth)</small></div></div>
</div>
<div class="mt-3 text-muted small">*Forecast based on current active payroll data. Staff count: <?= $staffCount ?></div>
</div></div>
<?php endif; ?>

<?php if ($view === 'general_ledger'): ?>
<div class="scard"><div class="sch"><i class="fas fa-book me-2"></i>General Ledger</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-3"><input type="date" id="glFrom" class="form-control env-field" value="<?= date('Y-01-01') ?>"></div>
<div class="col-md-3"><input type="date" id="glTo" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-md-2"><button class="btn btn-sec w-100" onclick="loadLedger()"><i class="fas fa-filter me-1"></i>Filter</button></div>
</div>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Date</th><th>Account</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead><tbody id="glBody"><tr><td colspan="5" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
function loadLedger(){
    var f=document.getElementById('glFrom').value,t=document.getElementById('glTo').value;
    var el=document.getElementById('glBody');el.innerHTML='<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>';
    fetch('director-finance.php?ajax=ledger_data&from='+f+'&to='+t).then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="5" class="text-center text-muted">No entries.</td></tr>';return;}
        var h='';var td=0,tc=0;d.forEach(function(e){td+=parseFloat(e.debit_amount||0);tc+=parseFloat(e.credit_amount||0);
        h+='<tr><td class="small">'+esc(e.entry_date)+'</td><td>'+esc(e.account_code||'')+' '+esc(e.account_name||'')+'</td><td>'+esc(e.description||'')+'</td><td class="text-end">'+(e.debit_amount>0?currency(e.debit_amount):'')+'</td><td class="text-end">'+(e.credit_amount>0?currency(e.credit_amount):'')+'</td></tr>';});
        h+='<tr class="fw-bold"><td colspan="3">Total</td><td class="text-end">'+currency(td)+'</td><td class="text-end">'+currency(tc)+'</td></tr>';
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="5" class="text-center text-danger">Failed.</td></tr>';});
}
document.addEventListener('DOMContentLoaded',loadLedger);
</script>
<?php endif; ?>

<?php if ($view === 'ledger_review'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-double me-2"></i>Ledger Review</div><div class="scb">
<p class="text-muted small">Review and verify ledger entries for accuracy.</p>
<?php
$pendingLedger = []; $r=$staff->query("SELECT * FROM {$students_db}.general_ledger WHERE status='pending' OR status IS NULL ORDER BY entry_date DESC LIMIT 50"); if($r) while($rw=$r->fetch_assoc()) $pendingLedger[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Date</th><th>Account</th><th>Amount</th><th>Type</th><th>Status</th></tr></thead><tbody>
<?php if($pendingLedger): foreach($pendingLedger as $l): ?>
<tr><td class="small"><?= $l['entry_date'] ?></td><td><?= htmlspecialchars($l['account_name']??$l['account_code']) ?></td><td class="fw-bold"><?= currency($l['debit_amount']?:$l['credit_amount']) ?></td><td><span class="badge bg-<?= $l['debit_amount']>0?'danger':'success' ?>"><?= $l['debit_amount']>0?'Debit':'Credit' ?></span></td><td><span class="badge bg-warning text-dark">Pending</span></td></tr>
<?php endforeach; else: ?><tr><td colspan="5" class="text-center text-muted">No pending entries.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'income_statement'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-invoice-dollar me-2"></i>Income Statement</div><div class="scb">
<?php
$isFrom = $_GET['from']??date('Y-m-01'); $isTo = $_GET['to']??date('Y-m-d');
$isRev = 0; $isExp = 0; $expCats = [];
if($students){ $stmt=$students->prepare("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed') AND DATE(payment_date) BETWEEN ? AND ?"); if($stmt){$stmt->bind_param('ss',$isFrom,$isTo);$stmt->execute();$r=$stmt->get_result();if($r)$isRev=(float)$r->fetch_assoc()['t'];$stmt->close();} }
if($staff){ $stmt=$staff->prepare("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE status IN('approved','paid') AND DATE(expense_date) BETWEEN ? AND ?"); if($stmt){$stmt->bind_param('ss',$isFrom,$isTo);$stmt->execute();$r=$stmt->get_result();if($r)$isExp=(float)$r->fetch_assoc()['t'];$stmt->close();} }
if($staff){ $stmt=$staff->prepare("SELECT expense_category,COALESCE(SUM(amount),0) t FROM expenses WHERE status IN('approved','paid') AND DATE(expense_date) BETWEEN ? AND ? GROUP BY expense_category ORDER BY t DESC"); if($stmt){$stmt->bind_param('ss',$isFrom,$isTo);$stmt->execute();$r=$stmt->get_result();if($r)while($rw=$r->fetch_assoc())$expCats[]=$rw;$stmt->close();} }
?>
<form class="row g-2 mb-3" method="GET"><input type="hidden" name="section" value="income_statement">
<div class="col-md-3"><input type="date" name="from" class="form-control env-field" value="<?= $isFrom ?>"></div>
<div class="col-md-3"><input type="date" name="to" class="form-control env-field" value="<?= $isTo ?>"></div>
<div class="col-md-2"><button type="submit" class="btn btn-sec w-100"><i class="fas fa-sync me-1"></i>Update</button></div>
<div class="col-md-2"><button type="button" class="btn btn-outline-sec w-100" onclick="window.open('director-finance.php?report=income_statement&from=<?= $isFrom ?>&to=<?= $isTo ?>','_blank')"><i class="fas fa-print me-1"></i>Print</button></div>
</form>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Item</th><th class="text-end">Amount</th></tr></thead><tbody>
<tr><td><strong>Revenue</strong></td><td class="text-end fw-bold"><?= currency($isRev) ?></td></tr>
<?php foreach($expCats as $ec): ?><tr><td>&nbsp;&nbsp;<?= htmlspecialchars($ec['expense_category']) ?></td><td class="text-end"><?= currency($ec['t']) ?></td></tr><?php endforeach; ?>
<tr><td><strong>Total Expenses</strong></td><td class="text-end fw-bold"><?= currency($isExp) ?></td></tr>
<tr><td class="fw-bold fs-5" style="color:<?= ($isRev-$isExp)>=0?'#16a34a':'#dc2626' ?>">Net Income</td><td class="text-end fw-bold fs-5" style="color:<?= ($isRev-$isExp)>=0?'#16a34a':'#dc2626' ?>"><?= currency($isRev-$isExp) ?></td></tr>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'balance_sheet'): ?>
<div class="scard"><div class="sch"><i class="fas fa-scale-balanced me-2"></i>Balance Sheet</div><div class="scb">
<?php
$totalAssets = (float)(($r=$staff->query("SELECT COALESCE(SUM(current_value),0) t FROM {$students_db}.finance_assets WHERE status='active'"))&&$r?$r->fetch_assoc()['t']:0);
$totalRevenueBal = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$totalExpenseBal = (float)(($r=$staff->query("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE status IN('approved','paid')"))&&$r?$r->fetch_assoc()['t']:0);
$equity = $totalRevenueBal - $totalExpenseBal;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Item</th><th class="text-end">Amount</th></tr></thead><tbody>
<tr><td class="fw-bold">Assets</td><td></td></tr>
<tr><td>&nbsp;&nbsp;Total Fixed Assets</td><td class="text-end"><?= currency($totalAssets) ?></td></tr>
<tr><td>&nbsp;&nbsp;Cash & Receivables (approx)</td><td class="text-end"><?= currency($totalRevenueBal) ?></td></tr>
<tr><td class="fw-bold">Total Assets</td><td class="text-end fw-bold"><?= currency($totalAssets+$totalRevenueBal) ?></td></tr>
<tr><td>&nbsp;</td><td></td></tr>
<tr><td class="fw-bold">Liabilities & Equity</td><td></td></tr>
<tr><td>&nbsp;&nbsp;Retained Earnings</td><td class="text-end"><?= currency($equity) ?></td></tr>
<tr><td class="fw-bold">Total Liabilities & Equity</td><td class="text-end fw-bold"><?= currency($equity+$totalRevenueBal) ?></td></tr>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'cash_flow'): ?>
<div class="scard"><div class="sch"><i class="fas fa-money-bill-trend-up me-2"></i>Cash Flow</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-3"><input type="date" id="cfFrom" class="form-control env-field" value="<?= date('Y-01-01') ?>"></div>
<div class="col-md-3"><input type="date" id="cfTo" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-md-2"><button class="btn btn-sec w-100" onclick="loadCashFlow()"><i class="fas fa-filter me-1"></i>Filter</button></div>
</div>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Date</th><th>Inflow</th><th>Outflow</th><th>Net</th></tr></thead><tbody id="cfBody"><tr><td colspan="4" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
function loadCashFlow(){
    var f=document.getElementById('cfFrom').value,t=document.getElementById('cfTo').value;
    var el=document.getElementById('cfBody');el.innerHTML='<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>';
    fetch('director-finance.php?ajax=cash_flow_data&from='+f+'&to='+t).then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="4" class="text-center text-muted">No data.</td></tr>';return;}
        var h='';var ti=0,to=0;d.forEach(function(c){ti+=parseFloat(c.inflow||0);to+=parseFloat(c.outflow||0);var net=c.inflow-c.outflow;
        h+='<tr><td class="small">'+esc(c.dt)+'</td><td>'+currency(c.inflow)+'</td><td>'+currency(c.outflow)+'</td><td class="fw-bold" style="color:'+(net>=0?'#16a34a':'#dc2626')+'">'+currency(net)+'</td></tr>';});
        h+='<tr class="fw-bold"><td>Total</td><td>'+currency(ti)+'</td><td>'+currency(to)+'</td><td style="color:'+(ti-to>=0?'#16a34a':'#dc2626')+'">'+currency(ti-to)+'</td></tr>';
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="4" class="text-center text-danger">Failed.</td></tr>';});
}
document.addEventListener('DOMContentLoaded',loadCashFlow);
</script>
<?php endif; ?>

<?php if ($view === 'bank_reconciliation'): ?>
<div class="scard"><div class="sch"><i class="fas fa-handshake me-2"></i>Bank Reconciliation</div><div class="scb">
<?php
$totalPayments = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status='approved'"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$totalExpenses = (float)(($r=$staff->query("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE status='paid'"))&&$r?$r->fetch_assoc()['t']:0);
$bankBalance = $totalPayments - $totalExpenses;
$bookBalance = $totalPayments - $totalExpenses;
$difference = $bankBalance - $bookBalance;
?>
<div class="row g-3">
<div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fw-bold fs-5 text-success"><?= currency($totalPayments) ?></div><small>Total Collections (Banked)</small></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fw-bold fs-5 text-danger"><?= currency($totalExpenses) ?></div><small>Total Payments</small></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fw-bold fs-5 <?= $difference==0?'text-success':'text-warning' ?>"><?= currency($difference) ?></div><small>Difference</small></div></div>
</div>
<div class="mt-3 p-3 bg-light rounded"><p class="small text-muted mb-0">Bank Balance: <?= currency($bankBalance) ?> | Book Balance: <?= currency($bookBalance) ?> | Status: <span class="badge bg-<?= $difference==0?'success':'warning text-dark' ?>"><?= $difference==0?'Reconciled':'Unreconciled' ?></span></p></div>
</div></div>
<?php endif; ?>

<?php if ($view === 'reconciliation_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-lines me-2"></i>Reconciliation Reports</div><div class="scb">
<p class="text-muted small">Generate reconciliation summary reports.</p>
<?php
$recFrom = $_GET['from']??date('Y-m-01'); $recTo = $_GET['to']??date('Y-m-d');
$recPay = 0; $recExp = 0;
if($students){ $stmt=$students->prepare("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status='approved' AND DATE(payment_date) BETWEEN ? AND ?"); if($stmt){$stmt->bind_param('ss',$recFrom,$recTo);$stmt->execute();$r=$stmt->get_result();if($r)$recPay=(float)$r->fetch_assoc()['t'];$stmt->close();} }
if($staff){ $stmt=$staff->prepare("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE status='paid' AND DATE(expense_date) BETWEEN ? AND ?"); if($stmt){$stmt->bind_param('ss',$recFrom,$recTo);$stmt->execute();$r=$stmt->get_result();if($r)$recExp=(float)$r->fetch_assoc()['t'];$stmt->close();} }
?>
<form class="row g-2 mb-3" method="GET"><input type="hidden" name="section" value="reconciliation_reports">
<div class="col-md-3"><input type="date" name="from" class="form-control env-field" value="<?= $recFrom ?>"></div>
<div class="col-md-3"><input type="date" name="to" class="form-control env-field" value="<?= $recTo ?>"></div>
<div class="col-md-2"><button type="submit" class="btn btn-sec w-100"><i class="fas fa-sync me-1"></i>Generate</button></div>
</form>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Item</th><th class="text-end">Amount</th></tr></thead><tbody>
<tr><td>Total Collections</td><td class="text-end"><?= currency($recPay) ?></td></tr>
<tr><td>Total Payments</td><td class="text-end"><?= currency($recExp) ?></td></tr>
<tr><td class="fw-bold">Reconciled Balance</td><td class="text-end fw-bold"><?= currency($recPay-$recExp) ?></td></tr>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'audit_logs'): ?>
<div class="scard"><div class="sch"><i class="fas fa-history me-2"></i>Audit Logs</div><div class="scb">
<?php
$auditList = []; $r=$staff->query("SELECT * FROM staff_activity_log ORDER BY created_at DESC LIMIT 100"); if($r) while($rw=$r->fetch_assoc()) $auditList[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Action</th><th>Staff</th><th>Type</th><th>Date</th></tr></thead><tbody>
<?php if($auditList): foreach($auditList as $a): ?>
<tr><td><?= htmlspecialchars(mb_substr($a['activity_description'],0,80)) ?></td><td><?= $a['staff_id'] ?></td><td><span class="badge bg-secondary"><?= htmlspecialchars($a['activity_type']??'general') ?></span></td><td class="small"><?= $a['created_at'] ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="4" class="text-center text-muted">No audit logs.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'audit_findings'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Report Finding</div><div class="scb">
<form onsubmit="event.preventDefault(); createAuditFinding()">
<div class="mb-3"><label class="fl">Finding Title *</label><input type="text" id="afTitle" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Description</label><textarea id="afDesc" class="form-control env-field" rows="3"></textarea></div>
<div class="row g-2 mb-3">
<div class="col-4"><label class="fl">Severity</label><select id="afSeverity" class="form-select env-field"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
<div class="col-4"><label class="fl">Department</label><input type="text" id="afDept" class="form-control env-field"></div>
</div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Report Finding</button>
</form>
<div id="afMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Audit Findings</div><div class="scb p-0"><div id="afList"></div></div></div>
</div>
</div>
<script>
function createAuditFinding(){
    var fd=new FormData();fd.append('finding_title',document.getElementById('afTitle').value);fd.append('description',document.getElementById('afDesc').value);fd.append('severity',document.getElementById('afSeverity').value);fd.append('department',document.getElementById('afDept').value);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=create_audit_finding',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        document.getElementById('afMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Finding reported.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){document.getElementById('afTitle').value='';document.getElementById('afDesc').value='';document.getElementById('afDept').value='';loadAuditFindings();}
    }).catch(function(){document.getElementById('afMsg').innerHTML='<div class="alert alert-danger py-1 small">Failed.</div>';});
}
function loadAuditFindings(){
    var el=document.getElementById('afList');if(!el)return;
    el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('director-finance.php?ajax=audit_finding_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No findings.</div>';return;}
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Severity</th><th>Dept</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(f){var svCls=f.severity==='critical'?'danger':f.severity==='high'?'warning':f.severity==='medium'?'info':'secondary';var stCls=f.status==='resolved'?'success':f.status==='in_progress'?'info':f.status==='closed'?'secondary':'danger';
        h+='<tr><td>'+esc(f.finding_title)+'</td><td><span class="badge bg-'+svCls+'">'+esc(f.severity)+'</span></td><td>'+esc(f.department||'-')+'</td><td><span class="badge bg-'+stCls+'">'+esc(f.status)+'</span></td><td class="small">'+esc(f.created_at)+'</td></tr>';});
        h+='</tbody></table></div>';el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadAuditFindings);
</script>
<?php endif; ?>

<?php if ($view === 'audit_reviews'): ?>
<div class="scard"><div class="sch"><i class="fas fa-clipboard-check me-2"></i>Audit Reviews</div><div class="scb">
<p class="text-muted small">Review and update status of audit findings.</p>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Finding</th><th>Severity</th><th>Current Status</th><th>Action</th></tr></thead><tbody id="arBody"><tr><td colspan="4" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('arBody');if(!el)return;
    fetch('director-finance.php?ajax=audit_finding_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="4" class="text-center text-muted">No findings.</td></tr>';return;}
        var h='';d.forEach(function(f){var svCls=f.severity==='critical'?'danger':f.severity==='high'?'warning':f.severity==='medium'?'info':'secondary';
        h+='<tr><td>'+esc(f.finding_title)+'</td><td><span class="badge bg-'+svCls+'">'+esc(f.severity)+'</span></td><td><span class="badge bg-'+(f.status==='resolved'?'success':f.status==='in_progress'?'info':'secondary')+'">'+esc(f.status)+'</span></td><td><select class="form-select form-select-sm" style="width:auto" onchange="updateFindingStatus('+f.id+',this.value)"><option value="">Change</option><option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="4" class="text-center text-danger">Failed.</td></tr>';});
});
function updateFindingStatus(id,st){
    if(!st)return;
    if(!confirm('Update status to '+st+'?'))return;
    var fd=new FormData();fd.append('id',id);fd.append('status',st);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=update_audit_status',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(d.success)location.reload();else alert('Failed');}).catch(function(){alert('Error');});
}
</script>
<?php endif; ?>

<?php if ($view === 'compliance_overview'): ?>
<div class="scard"><div class="sch"><i class="fas fa-shield me-2"></i>Compliance Overview</div><div class="scb">
<?php
$compStats = []; $r=$staff->query("SELECT compliance_type,COUNT(*) cnt,SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) open_count FROM {$students_db}.compliance_alerts GROUP BY compliance_type"); if($r) while($rw=$r->fetch_assoc()) $compStats[]=$rw;
?>
<div class="row g-3">
<div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4 text-primary"><?= array_sum(array_column($compStats,'cnt')) ?></div><small>Total Alerts</small></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4 text-danger"><?= array_sum(array_column($compStats,'open_count')) ?></div><small>Open Alerts</small></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fw-bold fs-4 text-success"><?= array_sum(array_column($compStats,'cnt'))-array_sum(array_column($compStats,'open_count')) ?></div><small>Resolved</small></div></div>
</div></div></div>
<?php endif; ?>

<?php if ($view === 'ura_compliance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-invoice-dollar me-2"></i>URA Tax Compliance</div><div class="scb">
<?php
$uraRevenue = $students ? (float)(($r=$students->query("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed')"))&&$r?$r->fetch_assoc()['t']:0) : 0;
$uraExpenses = (float)(($r=$staff->query("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE status IN('approved','paid')"))&&$r?$r->fetch_assoc()['t']:0);
$taxable = max(0,$uraRevenue-$uraExpenses);
$vat18 = $uraRevenue * 0.18;
$wit = $uraExpenses * 0.06;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Item</th><th class="text-end">Amount (UGX)</th></tr></thead><tbody>
<tr><td>Gross Revenue</td><td class="text-end"><?= currency($uraRevenue) ?></td></tr>
<tr><td>Allowable Expenses</td><td class="text-end"><?= currency($uraExpenses) ?></td></tr>
<tr><td>Estimated VAT (18%)</td><td class="text-end"><?= currency($vat18) ?></td></tr>
<tr><td>Withholding Tax (6%)</td><td class="text-end"><?= currency($wit) ?></td></tr>
</tbody></table></div>
<div class="mt-2"><button class="btn btn-outline-sec" onclick="window.open('director-finance.php?report=tax_report','_blank')"><i class="fas fa-print me-1"></i> Print Tax Report</button></div>
</div></div>
<?php endif; ?>

<?php if ($view === 'regulatory_compliance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-gavel me-2"></i>Regulatory Compliance</div><div class="scb">
<?php
$regAlerts = []; $r=$staff->query("SELECT * FROM {$students_db}.compliance_alerts WHERE compliance_type='regulatory' ORDER BY created_at DESC LIMIT 50"); if($r) while($rw=$r->fetch_assoc()) $regAlerts[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Alert</th><th>Severity</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php if($regAlerts): foreach($regAlerts as $a): ?><tr><td><?= htmlspecialchars($a['alert_title']) ?></td><td><span class="badge bg-<?= $a['severity']==='critical'?'danger':($a['severity']==='high'?'warning':($a['severity']==='medium'?'info':'secondary')) ?>"><?= htmlspecialchars($a['severity']) ?></span></td><td><span class="badge bg-<?= $a['status']==='resolved'?'success':($a['status']==='acknowledged'?'info':'warning text-dark') ?>"><?= htmlspecialchars($a['status']) ?></span></td><td class="small"><?= $a['created_at'] ?></td></tr><?php endforeach; else: ?><tr><td colspan="4" class="text-center text-muted">No regulatory alerts.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'risk_register'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Add Risk</div><div class="scb">
<form onsubmit="event.preventDefault(); createRisk()">
<div class="mb-3"><label class="fl">Risk Name *</label><input type="text" id="riskName" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Description</label><textarea id="riskDesc" class="form-control env-field" rows="2"></textarea></div>
<div class="row g-2 mb-3">
<div class="col-4"><label class="fl">Category</label><input type="text" id="riskCat" class="form-control env-field"></div>
<div class="col-4"><label class="fl">Likelihood</label><select id="riskLh" class="form-select env-field"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
<div class="col-4"><label class="fl">Impact</label><select id="riskImp" class="form-select env-field"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
</div>
<div class="mb-3"><label class="fl">Mitigation</label><textarea id="riskMit" class="form-control env-field" rows="2"></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Add Risk</button>
</form>
<div id="riskMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Risk Register</div><div class="scb p-0"><div id="riskList"></div></div></div>
</div>
</div>
<script>
function createRisk(){
    var fd=new FormData();fd.append('risk_name',document.getElementById('riskName').value);fd.append('description',document.getElementById('riskDesc').value);fd.append('category',document.getElementById('riskCat').value);fd.append('likelihood',document.getElementById('riskLh').value);fd.append('impact',document.getElementById('riskImp').value);fd.append('mitigation',document.getElementById('riskMit').value);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=create_risk',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        document.getElementById('riskMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Risk added.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){document.getElementById('riskName').value='';document.getElementById('riskDesc').value='';document.getElementById('riskCat').value='';document.getElementById('riskMit').value='';loadRisks();}
    }).catch(function(){document.getElementById('riskMsg').innerHTML='<div class="alert alert-danger py-1 small">Failed.</div>';});
}
function loadRisks(){
    var el=document.getElementById('riskList');if(!el)return;
    el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('director-finance.php?ajax=risk_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No risks.</div>';return;}
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Risk</th><th>Category</th><th>L</th><th>I</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(r){var lhCls=r.likelihood==='high'?'danger':r.likelihood==='medium'?'warning':'success';var imCls=r.impact==='high'?'danger':r.impact==='medium'?'warning':'success';
        h+='<tr><td><strong>'+esc(r.risk_name)+'</strong></td><td>'+esc(r.category||'-')+'</td><td><span class="badge bg-'+lhCls+'">'+esc(r.likelihood)+'</span></td><td><span class="badge bg-'+imCls+'">'+esc(r.impact)+'</span></td><td><span class="badge bg-'+(r.status==='active'?'warning text-dark':r.status==='monitored'?'info':'success')+'">'+esc(r.status)+'</span></td></tr>';});
        h+='</tbody></table></div>';el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadRisks);
</script>
<?php endif; ?>

<?php if ($view === 'compliance_alerts'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Create Alert</div><div class="scb">
<form onsubmit="event.preventDefault(); createCompAlert()">
<div class="mb-3"><label class="fl">Alert Title *</label><input type="text" id="caTitle" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Description</label><textarea id="caDesc" class="form-control env-field" rows="3"></textarea></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Type</label><select id="caType" class="form-select env-field"><option value="financial">Financial</option><option value="ura">URA</option><option value="regulatory">Regulatory</option></select></div>
<div class="col-6"><label class="fl">Severity</label><select id="caSev" class="form-select env-field"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
</div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Create Alert</button>
</form>
<div id="caMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Compliance Alerts</div><div class="scb p-0"><div id="caList"></div></div></div>
</div>
</div>
<script>
function createCompAlert(){
    var fd=new FormData();fd.append('alert_title',document.getElementById('caTitle').value);fd.append('description',document.getElementById('caDesc').value);fd.append('compliance_type',document.getElementById('caType').value);fd.append('severity',document.getElementById('caSev').value);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=create_compliance_alert',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        document.getElementById('caMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Alert created.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){document.getElementById('caTitle').value='';document.getElementById('caDesc').value='';loadCompAlerts();}
    }).catch(function(){document.getElementById('caMsg').innerHTML='<div class="alert alert-danger py-1 small">Failed.</div>';});
}
function loadCompAlerts(){
    var el=document.getElementById('caList');if(!el)return;
    el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('director-finance.php?ajax=compliance_alert_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No alerts.</div>';return;}
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Type</th><th>Severity</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(a){var tpCls=a.compliance_type==='ura'?'danger':a.compliance_type==='regulatory'?'warning':'info';var svCls=a.severity==='critical'?'danger':a.severity==='high'?'warning':a.severity==='medium'?'info':'secondary';
        h+='<tr><td>'+esc(a.alert_title)+'</td><td><span class="badge bg-'+tpCls+'">'+esc(a.compliance_type)+'</span></td><td><span class="badge bg-'+svCls+'">'+esc(a.severity)+'</span></td><td><span class="badge bg-'+(a.status==='resolved'?'success':a.status==='acknowledged'?'info':'warning text-dark')+'">'+esc(a.status)+'</span></td><td class="small">'+esc(a.created_at)+'</td></tr>';});
        h+='</tbody></table></div>';el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadCompAlerts);
</script>
<?php endif; ?>

<?php if ($view === 'purchase_requests'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>New Procurement Request</div><div class="scb">
<form onsubmit="event.preventDefault(); createProcurement()">
<div class="mb-3"><label class="fl">Title *</label><input type="text" id="prTitle" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Description</label><textarea id="prDesc" class="form-control env-field" rows="2"></textarea></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Amount</label><input type="number" id="prAmt" class="form-control env-field" step="0.01"></div>
<div class="col-6"><label class="fl">Department</label><input type="text" id="prDept" class="form-control env-field"></div>
</div>
<div class="mb-3"><label class="fl">Supplier</label><input type="text" id="prSupp" class="form-control env-field"></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Submit Request</button>
</form>
<div id="prMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Purchase Requests</div><div class="scb p-0"><div id="prList"></div></div></div>
</div>
</div>
<script>
function createProcurement(){
    var fd=new FormData();fd.append('title',document.getElementById('prTitle').value);fd.append('description',document.getElementById('prDesc').value);fd.append('amount',document.getElementById('prAmt').value);fd.append('department',document.getElementById('prDept').value);fd.append('supplier_name',document.getElementById('prSupp').value);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=create_procurement',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        document.getElementById('prMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Request created: '+esc(d.pr)+'</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){document.getElementById('prTitle').value='';document.getElementById('prDesc').value='';document.getElementById('prAmt').value='';document.getElementById('prDept').value='';document.getElementById('prSupp').value='';loadProcurement();}
    }).catch(function(){document.getElementById('prMsg').innerHTML='<div class="alert alert-danger py-1 small">Failed.</div>';});
}
function loadProcurement(){
    var el=document.getElementById('prList');if(!el)return;
    el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('director-finance.php?ajax=procurement_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No requests.</div>';return;}
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>PR#</th><th>Title</th><th>Amount</th><th>Dept</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(p){var stCls=p.status==='approved'?'success':p.status==='pending'?'warning text-dark':p.status==='rejected'?'danger':'secondary';
        h+='<tr><td><code>'+esc(p.pr_number)+'</code></td><td>'+esc(p.title)+'</td><td>'+currency(p.amount)+'</td><td>'+esc(p.department||'-')+'</td><td><span class="badge bg-'+stCls+'">'+esc(p.status)+'</span></td></tr>';});
        h+='</tbody></table></div>';el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadProcurement);
</script>
<?php endif; ?>

<?php if ($view === 'quotations'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-invoice me-2"></i>Quotations Management</div><div class="scb">
<p class="text-muted small">Quotations are managed as part of procurement requests. <a href="?section=purchase_requests">View Purchase Requests</a></p>
</div></div>
<?php endif; ?>

<?php if ($view === 'supplier_evaluation'): ?>
<div class="scard"><div class="sch"><i class="fas fa-star me-2"></i>Supplier Evaluation</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Supplier</th><th>Category</th><th>Contact</th><th>Phone</th><th>Rating</th><th>Status</th></tr></thead><tbody id="seBody"><tr><td colspan="6" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('seBody');if(!el)return;
    fetch('director-finance.php?ajax=supplier_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="6" class="text-center text-muted">No suppliers.</td></tr>';return;}
        var h='';d.forEach(function(s){h+='<tr><td><strong>'+esc(s.supplier_name)+'</strong></td><td>'+esc(s.category||'-')+'</td><td>'+esc(s.contact_person||'-')+'</td><td>'+esc(s.phone||'-')+'</td><td>'+(s.performance_rating>0?'<span class="badge bg-'+(s.performance_rating>=4?'success':s.performance_rating>=3?'warning':'danger')+'">'+s.performance_rating+'</span>':'<span class="text-muted">--</span>')+'</td><td><span class="badge bg-'+((s.status||'active')==='active'?'success':'secondary')+'">'+esc(s.status)+'</span></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="6" class="text-center text-danger">Failed.</td></tr>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'supplier_payments'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Record Supplier Payment</div><div class="scb">
<form onsubmit="event.preventDefault(); recordSuppPayment()">
<div class="mb-3"><label class="fl">Supplier</label><select id="spSupp" class="form-select env-field">
<?php $r=$staff->query("SELECT id,supplier_name FROM {$students_db}.suppliers WHERE status='active' ORDER BY supplier_name"); if($r) while($s=$r->fetch_assoc()) echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['supplier_name']).'</option>'; ?>
</select></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Amount *</label><input type="number" id="spAmt" class="form-control env-field" step="0.01" required></div>
<div class="col-6"><label class="fl">Method</label><select id="spMethod" class="form-select env-field"><option value="bank">Bank Transfer</option><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="mobile">Mobile Money</option></select></div>
</div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Date</label><input type="date" id="spDate" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-6"><label class="fl">Invoice Ref</label><input type="text" id="spInv" class="form-control env-field"></div>
</div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Record Payment</button>
</form>
<div id="spMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Supplier Payments</div><div class="scb">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Payment#</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody id="spList"><tr><td colspan="5" class="text-center text-muted">Loading...</td></tr></tbody></table></div>
</div></div>
</div>
</div>
<script>
function recordSuppPayment(){
    var fd=new FormData();fd.append('supplier_id',document.getElementById('spSupp').value);fd.append('amount',document.getElementById('spAmt').value);fd.append('payment_method',document.getElementById('spMethod').value);fd.append('payment_date',document.getElementById('spDate').value);fd.append('invoice_ref',document.getElementById('spInv').value);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=record_supplier_payment',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        document.getElementById('spMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Payment recorded: '+esc(d.ref)+'</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){document.getElementById('spAmt').value='';document.getElementById('spInv').value='';loadSuppPayments();}
    }).catch(function(){document.getElementById('spMsg').innerHTML='<div class="alert alert-danger py-1 small">Failed.</div>';});
}
function loadSuppPayments(){
    var el=document.getElementById('spList');if(!el)return;
    fetch('director-finance.php?ajax=supplier_payment_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<tr><td colspan="5" class="text-center text-muted">No payments.</td></tr>';return;}
        var h='';d.forEach(function(p){var stCls=p.status==='approved'?'success':p.status==='pending'?'warning text-dark':'secondary';
        h+='<tr><td><code>'+esc(p.payment_number)+'</code></td><td>'+currency(p.amount)+'</td><td>'+esc(p.payment_method)+'</td><td class="small">'+esc(p.payment_date)+'</td><td><span class="badge bg-'+stCls+'">'+esc(p.status)+'</span></td></tr>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<tr><td colspan="5" class="text-center text-danger">Failed.</td></tr>';});
}
document.addEventListener('DOMContentLoaded',loadSuppPayments);
</script>
<?php endif; ?>

<?php if ($view === 'supplier_performance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-simple me-2"></i>Supplier Performance</div><div class="scb">
<?php
$topSuppliers = []; $r=$staff->query("SELECT s.*,COALESCE(SUM(sp.amount),0) total_paid FROM {$students_db}.suppliers s LEFT JOIN {$students_db}.supplier_payments sp ON s.id=sp.supplier_id GROUP BY s.id ORDER BY total_paid DESC LIMIT 10"); if($r) while($rw=$r->fetch_assoc()) $topSuppliers[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Supplier</th><th>Total Paid</th><th>Rating</th><th>Status</th></tr></thead><tbody>
<?php if($topSuppliers): foreach($topSuppliers as $s): ?>
<tr><td><strong><?= htmlspecialchars($s['supplier_name']) ?></strong></td><td class="fw-bold"><?= currency($s['total_paid']) ?></td><td><span class="badge bg-<?= $s['performance_rating']>=4?'success':($s['performance_rating']>=3?'warning':'danger') ?>"><?= $s['performance_rating']>0?$s['performance_rating']:'N/A' ?></span></td><td><span class="badge bg-<?= $s['status']==='active'?'success':'secondary' ?>"><?= $s['status'] ?></span></td></tr>
<?php endforeach; else: ?><tr><td colspan="4" class="text-center text-muted">No supplier data.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'outstanding_supplier_balances'): ?>
<div class="scard"><div class="sch"><i class="fas fa-credit-card me-2"></i>Outstanding Supplier Balances</div><div class="scb">
<?php
$outSupp = []; $r=$staff->query("SELECT s.supplier_name,COALESCE(SUM(sp.amount),0) total_paid FROM {$students_db}.suppliers s LEFT JOIN {$students_db}.supplier_payments sp ON s.id=sp.supplier_id AND sp.status='pending' GROUP BY s.id HAVING total_paid>0 ORDER BY total_paid DESC"); if($r) while($rw=$r->fetch_assoc()) $outSupp[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Supplier</th><th>Outstanding</th></tr></thead><tbody>
<?php if($outSupp): foreach($outSupp as $s): ?><tr><td><?= htmlspecialchars($s['supplier_name']) ?></td><td class="fw-bold text-danger"><?= currency($s['total_paid']) ?></td></tr><?php endforeach; else: ?><tr><td colspan="2" class="text-center text-muted">No outstanding balances.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'asset_register'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Add Asset</div><div class="scb">
<form onsubmit="event.preventDefault(); createAsset()">
<div class="mb-3"><label class="fl">Asset Name *</label><input type="text" id="asName" class="form-control env-field" required></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Asset Tag</label><input type="text" id="asTag" class="form-control env-field"></div>
<div class="col-6"><label class="fl">Category</label><select id="asCat" class="form-select env-field"><option>Equipment</option><option>Furniture</option><option>Vehicle</option><option>Building</option><option>IT</option><option>Other</option></select></div>
</div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Purchase Price</label><input type="number" id="asPrice" class="form-control env-field" step="0.01"></div>
<div class="col-6"><label class="fl">Depreciation Rate (%)</label><input type="number" id="asDep" class="form-control env-field" step="0.01"></div>
</div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Purchase Date</label><input type="date" id="asDate" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-6"><label class="fl">Location</label><input type="text" id="asLoc" class="form-control env-field"></div>
</div>
<div class="mb-3"><label class="fl">Assigned To</label><input type="text" id="asAssign" class="form-control env-field"></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Add Asset</button>
</form>
<div id="asMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Asset Register</div><div class="scb p-0"><div id="asList"></div></div></div>
</div>
</div>
<script>
function createAsset(){
    var fd=new FormData();fd.append('asset_name',document.getElementById('asName').value);fd.append('asset_tag',document.getElementById('asTag').value);fd.append('category',document.getElementById('asCat').value);fd.append('purchase_price',document.getElementById('asPrice').value);fd.append('depreciation_rate',document.getElementById('asDep').value);fd.append('purchase_date',document.getElementById('asDate').value);fd.append('location',document.getElementById('asLoc').value);fd.append('assigned_to',document.getElementById('asAssign').value);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=create_asset',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        document.getElementById('asMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Asset added.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){document.getElementById('asName').value='';document.getElementById('asTag').value='';document.getElementById('asPrice').value='';document.getElementById('asDep').value='';document.getElementById('asLoc').value='';document.getElementById('asAssign').value='';loadAssets();}
    }).catch(function(){document.getElementById('asMsg').innerHTML='<div class="alert alert-danger py-1 small">Failed.</div>';});
}
function loadAssets(){
    var el=document.getElementById('asList');if(!el)return;
    el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('director-finance.php?ajax=asset_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No assets.</div>';return;}
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Asset</th><th>Tag</th><th>Category</th><th>Price</th><th>Current Value</th><th>Location</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(a){var stCls=a.status==='active'?'success':a.status==='disposed'?'danger':'warning';
        h+='<tr><td><strong>'+esc(a.asset_name)+'</strong></td><td><code>'+esc(a.asset_tag||'')+'</code></td><td>'+esc(a.category)+'</td><td>'+currency(a.purchase_price)+'</td><td>'+currency(a.current_value)+'</td><td>'+esc(a.location||'-')+'</td><td><span class="badge bg-'+stCls+'">'+esc(a.status)+'</span></td></tr>';});
        h+='</tbody></table></div>';el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadAssets);
</script>
<?php endif; ?>

<?php if ($view === 'asset_valuation'): ?>
<div class="row g-3">
<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-calculator me-2"></i>Asset Valuation Summary</div><div class="scb">
<?php
$totalPurVal = (float)(($r=$staff->query("SELECT COALESCE(SUM(purchase_price),0) t FROM {$students_db}.finance_assets WHERE status='active'"))&&$r?$r->fetch_assoc()['t']:0);
$totalCurVal = (float)(($r=$staff->query("SELECT COALESCE(SUM(current_value),0) t FROM {$students_db}.finance_assets WHERE status='active'"))&&$r?$r->fetch_assoc()['t']:0);
$totalDep = $totalPurVal - $totalCurVal;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Metric</th><th class="text-end">Amount</th></tr></thead><tbody>
<tr><td>Total Purchase Value</td><td class="text-end"><?= currency($totalPurVal) ?></td></tr>
<tr><td>Total Current Value</td><td class="text-end"><?= currency($totalCurVal) ?></td></tr>
<tr><td>Total Depreciation</td><td class="text-end text-danger"><?= currency($totalDep) ?></td></tr>
</tbody></table></div></div></div></div>
<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-chart-pie me-2"></i>Assets by Category</div><div class="scb">
<?php
$assetCats = []; $r=$staff->query("SELECT category,COUNT(*) cnt,COALESCE(SUM(current_value),0) total FROM {$students_db}.finance_assets WHERE status='active' GROUP BY category ORDER BY total DESC"); if($r) while($rw=$r->fetch_assoc()) $assetCats[]=$rw;
if($assetCats): foreach($assetCats as $ac): $pct = $totalCurVal>0?round(($ac['total']/$totalCurVal)*100,1):0; ?>
<div class="d-flex justify-content-between small"><span><?= htmlspecialchars($ac['category']) ?> (<?= $ac['cnt'] ?>)</span><span class="fw-bold"><?= currency($ac['total']) ?> (<?= $pct ?>%)</span></div>
<div class="progress mb-2" style="height:4px"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; else: ?><div class="text-muted small">No asset data.</div><?php endif; ?>
</div></div></div></div>
<?php endif; ?>

<?php if ($view === 'depreciation_tracking'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-line me-2"></i>Depreciation Tracking</div><div class="scb">
<?php
$depAssets = []; $r=$staff->query("SELECT * FROM {$students_db}.finance_assets WHERE depreciation_rate>0 AND status='active' ORDER BY depreciation_rate DESC"); if($r) while($rw=$r->fetch_assoc()) $depAssets[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Asset</th><th>Purchase Price</th><th>Current Value</th><th>Dep Rate</th><th>Annual Dep</th></tr></thead><tbody>
<?php if($depAssets): foreach($depAssets as $a): $annualDep = $a['purchase_price']*($a['depreciation_rate']/100); ?>
<tr><td><?= htmlspecialchars($a['asset_name']) ?></td><td><?= currency($a['purchase_price']) ?></td><td><?= currency($a['current_value']) ?></td><td><?= $a['depreciation_rate'] ?>%</td><td class="fw-bold text-danger"><?= currency($annualDep) ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="5" class="text-center text-muted">No depreciable assets.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'capital_projects'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>New Project</div><div class="scb">
<form onsubmit="event.preventDefault(); createProject()">
<div class="mb-3"><label class="fl">Project Name *</label><input type="text" id="pjName" class="form-control env-field" required></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Budget</label><input type="number" id="pjBud" class="form-control env-field" step="0.01"></div>
<div class="col-6"><label class="fl">Project Code</label><input type="text" id="pjCode" class="form-control env-field" placeholder="Auto-generated"></div>
</div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Start Date</label><input type="date" id="pjStart" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-6"><label class="fl">End Date</label><input type="date" id="pjEnd" class="form-control env-field"></div>
</div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Create Project</button>
</form>
<div id="pjMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Capital Projects</div><div class="scb p-0"><div id="pjList"></div></div></div>
</div>
</div>
<script>
function createProject(){
    var fd=new FormData();fd.append('project_name',document.getElementById('pjName').value);fd.append('budget',document.getElementById('pjBud').value);fd.append('project_code',document.getElementById('pjCode').value);fd.append('start_date',document.getElementById('pjStart').value);fd.append('end_date',document.getElementById('pjEnd').value);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=create_project',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        document.getElementById('pjMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Project created: '+esc(d.code)+'</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){document.getElementById('pjName').value='';document.getElementById('pjBud').value='';document.getElementById('pjCode').value='';document.getElementById('pjEnd').value='';loadProjects();}
    }).catch(function(){document.getElementById('pjMsg').innerHTML='<div class="alert alert-danger py-1 small">Failed.</div>';});
}
function loadProjects(){
    var el=document.getElementById('pjList');if(!el)return;
    el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('director-finance.php?ajax=project_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No projects.</div>';return;}
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Project</th><th>Code</th><th>Budget</th><th>Spent</th><th>Remaining</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(p){var stCls=p.status==='active'?'success':p.status==='completed'?'info':p.status==='cancelled'?'danger':'warning text-dark';var rem=p.budget-p.spent;
        h+='<tr><td><strong>'+esc(p.project_name)+'</strong></td><td><code>'+esc(p.project_code)+'</code></td><td>'+currency(p.budget)+'</td><td>'+currency(p.spent)+'</td><td>'+currency(rem)+'</td><td><span class="badge bg-'+stCls+'">'+esc(p.status)+'</span></td></tr>';});
        h+='</tbody></table></div>';el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadProjects);
</script>
<?php endif; ?>

<?php if ($view === 'project_performance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-gauge-high me-2"></i>Project Performance</div><div class="scb">
<?php
$allProj = []; $r=$staff->query("SELECT * FROM {$students_db}.capital_projects ORDER BY status"); if($r) while($rw=$r->fetch_assoc()) $allProj[]=$rw;
?>
<div class="table-responsive"><table class="table tb"><thead><tr><th>Project</th><th>Progress</th><th>Budget Utilization</th><th>Status</th></tr></thead><tbody>
<?php if($allProj): foreach($allProj as $p): $util = $p['budget']>0?round(($p['spent']/$p['budget'])*100,1):0; $stCls=($p['status']==='active'?'success':($p['status']==='completed'?'info':($p['status']==='cancelled'?'danger':'warning text-dark'))); ?>
<tr><td><strong><?= htmlspecialchars($p['project_name']) ?></strong></td><td><div class="progress" style="height:8px;min-width:100px"><div class="progress-bar" style="width:<?= $util ?>%"><?= $util ?>%</div></div></td><td><?= currency($p['spent']) ?> / <?= currency($p['budget']) ?></td><td><span class="badge bg-<?= $stCls ?>"><?= htmlspecialchars($p['status']) ?></span></td></tr>
<?php endforeach; else: ?><tr><td colspan="4" class="text-center text-muted">No projects.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if ($view === 'finance_messages'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-paper-plane me-2"></i>Send Message</div><div class="scb">
<form onsubmit="event.preventDefault(); sendFMsg()">
<div class="mb-3"><label class="fl">Recipient Role</label><select id="fmRole" class="form-select env-field"><option value="all">All Staff</option><option value="finance">Finance Team</option><option value="management">Management</option><option value="bursar">Bursar</option><option value="director">Director General</option></select></div>
<div class="mb-3"><label class="fl">Subject *</label><input type="text" id="fmSubj" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Message *</label><textarea id="fmMsg" class="form-control env-field" rows="4" required></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-paper-plane me-1"></i>Send</button>
</form>
<div id="fmMsgDiv" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-inbox me-2"></i>Finance Communications</div><div class="scb p-0"><div id="fmList"></div></div></div>
</div>
</div>
<script>
function sendFMsg(){
    var fd=new FormData();fd.append('recipient_role',document.getElementById('fmRole').value);fd.append('subject',document.getElementById('fmSubj').value);fd.append('message',document.getElementById('fmMsg').value);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=send_finance_message',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        document.getElementById('fmMsgDiv').innerHTML=d.success?'<div class="alert alert-success py-1 small">Message sent.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){document.getElementById('fmSubj').value='';document.getElementById('fmMsg').value='';loadFMessages();}
    }).catch(function(){document.getElementById('fmMsgDiv').innerHTML='<div class="alert alert-danger py-1 small">Failed.</div>';});
}
function loadFMessages(){
    var el=document.getElementById('fmList');if(!el)return;
    el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('director-finance.php?ajax=finance_message_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No messages.</div>';return;}
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Subject</th><th>To</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(m){h+='<tr><td><strong>'+esc(m.subject)+'</strong></td><td class="small">'+esc(m.recipient_role||'All')+'</td><td class="small">'+esc(m.created_at)+'</td><td>'+(m.is_read?'<span class="badge bg-success">Read</span>':'<span class="badge bg-warning text-dark">Sent</span>')+'</td></tr>';});
        h+='</tbody></table></div>';el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadFMessages);
</script>
<?php endif; ?>

<?php if ($view === 'finance_notices'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-bullhorn me-2"></i>Publish Notice</div><div class="scb">
<form onsubmit="event.preventDefault(); pubNotice()">
<div class="mb-3"><label class="fl">Title *</label><input type="text" id="fnTitle" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Content *</label><textarea id="fnContent" class="form-control env-field" rows="4" required></textarea></div>
<div class="mb-3"><label class="fl">Audience</label><select id="fnAud" class="form-select env-field"><option value="all">All Staff</option><option value="finance">Finance Team</option><option value="management">Management</option></select></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-paper-plane me-1"></i>Publish</button>
</form>
<div id="fnMsgDiv" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Published Notices</div><div class="scb p-0"><div id="fnList"></div></div></div>
</div>
</div>
<script>
function pubNotice(){
    var fd=new FormData();fd.append('title',document.getElementById('fnTitle').value);fd.append('content',document.getElementById('fnContent').value);fd.append('audience',document.getElementById('fnAud').value);fd.append('csrf_token', window.CSRF_TOKEN);
    fetch('director-finance.php?ajax=publish_finance_notice',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        document.getElementById('fnMsgDiv').innerHTML=d.success?'<div class="alert alert-success py-1 small">Notice published.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){document.getElementById('fnTitle').value='';document.getElementById('fnContent').value='';loadFNotices();}
    }).catch(function(){document.getElementById('fnMsgDiv').innerHTML='<div class="alert alert-danger py-1 small">Failed.</div>';});
}
function loadFNotices(){
    var el=document.getElementById('fnList');if(!el)return;
    el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('director-finance.php?ajax=finance_notice_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No notices.</div>';return;}
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Audience</th><th>Published By</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(n){h+='<tr><td><strong>'+esc(n.title)+'</strong></td><td><span class="badge bg-info">'+esc(n.audience||'All')+'</span></td><td class="small">'+esc(n.published_by)+'</td><td class="small">'+esc(n.created_at)+'</td></tr>';});
        h+='</tbody></table></div>';el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadFNotices);
</script>
<?php endif; ?>

<?php if ($view === 'approval_requests'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-signature me-2"></i>Approval Requests</div><div class="scb">
<p class="text-muted small">View all pending approval requests from various departments.</p>
<div class="row g-3" id="appReqList"></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('appReqList');if(!el)return;
    el.innerHTML='<div class="text-center py-3 col-12"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    fetch('director-finance.php?ajax=approval_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="col-12 text-center text-muted py-3">No pending approval requests.</div>';return;}
        var h='';d.forEach(function(a){var stCls=a.status==='pending'?'warning text-dark':a.status==='approved'?'success':a.status==='rejected'?'danger':'info';
        h+='<div class="col-md-6"><div class="border rounded p-3"><div class="d-flex justify-content-between"><strong>'+esc(a.description||'Request')+'</strong><span class="badge bg-'+stCls+'">'+esc(a.status)+'</span></div><div class="small text-muted mt-1">Amount: '+currency(a.amount)+' | ID: '+a.id+' | Table: '+esc(a.tbl)+'</div><div class="small text-muted">'+esc(a.created_at)+'</div><div class="mt-2 d-flex gap-1"><button class="btn btn-sm btn-outline-success" onclick="approvalAction('+a.id+',\''+a.tbl+'\',\'approved\')"><i class="fas fa-check"></i> Approve</button><button class="btn btn-sm btn-outline-danger" onclick="approvalAction('+a.id+',\''+a.tbl+'\',\'rejected\')"><i class="fas fa-times"></i> Reject</button></div></div></div>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="col-12 text-center text-danger py-3">Failed to load.</div>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'finance_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-lines me-2"></i>Financial Reports</div><div class="scb">
<p class="text-muted small">Generate and export financial reports.</p>
<div class="row g-3">
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-file-invoice-dollar fa-2x text-primary mb-2"></i><h6>Income Statement</h6><p class="small text-muted">Revenue vs expenses for a period</p><a href="?section=income_statement" class="btn btn-sm btn-sec">View</a> <button class="btn btn-sm btn-outline-sec" onclick="window.open('director-finance.php?report=income_statement','_blank')"><i class="fas fa-print"></i></button></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-scale-balanced fa-2x text-success mb-2"></i><h6>Balance Sheet</h6><p class="small text-muted">Assets, liabilities & equity</p><a href="?section=balance_sheet" class="btn btn-sm btn-sec">View</a> <button class="btn btn-sm btn-outline-sec" onclick="window.print()"><i class="fas fa-print"></i></button></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-money-bill-trend-up fa-2x text-info mb-2"></i><h6>Cash Flow</h6><p class="small text-muted">Cash inflows and outflows</p><a href="?section=cash_flow" class="btn btn-sm btn-sec">View</a> <button class="btn btn-sm btn-outline-sec" onclick="window.print()"><i class="fas fa-print"></i></button></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-receipt fa-2x text-warning mb-2"></i><h6>Expense Report</h6><p class="small text-muted">Detailed expense listing</p><a href="?section=expenditure_monitoring" class="btn btn-sm btn-sec">View</a> <button class="btn btn-sm btn-outline-sec" onclick="window.open('director-finance.php?report=expense_report','_blank')"><i class="fas fa-print"></i></button></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-file-invoice fa-2x text-danger mb-2"></i><h6>Fee Collection Report</h6><p class="small text-muted">Student fee collection details</p><a href="?section=fee_collection" class="btn btn-sm btn-sec">View</a> <button class="btn btn-sm btn-outline-sec" onclick="window.open('director-finance.php?report=fee_collection','_blank')"><i class="fas fa-print"></i></button></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-file-invoice-dollar fa-2x text-secondary mb-2"></i><h6>URA Tax Report</h6><p class="small text-muted">Estimated tax liability</p><a href="?section=ura_compliance" class="btn btn-sm btn-sec">View</a> <button class="btn btn-sm btn-outline-sec" onclick="window.open('director-finance.php?report=tax_report','_blank')"><i class="fas fa-print"></i></button></div></div>
</div></div></div>
<?php endif; ?>

<?php if ($view === 'approval_center'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-double me-2"></i>Approval Center</div><div class="scb">
<p class="text-muted small">Centralized approval hub for all finance-related requests.</p>
<div class="row g-3" id="acList"></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var el=document.getElementById('acList');if(!el)return;
    el.innerHTML='<div class="col-12 text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    fetch('director-finance.php?ajax=approval_list').then(function(r){return r.json()}).then(function(d){
        if(!d||!d.length){el.innerHTML='<div class="col-12 text-center text-muted py-4"><i class="fas fa-check-circle fa-3x mb-2"></i><p>No pending approvals.</p></div>';return;}
        var h='';d.forEach(function(a){var prCls=a.status==='pending'?'warning text-dark':'info';var tblName=a.tbl?esc(a.tbl).replace('_approvals','').replace('_',' '):'Unknown';
        h+='<div class="col-md-6 col-lg-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><div><h6 class="fw-bold mb-1">'+esc(a.description||'Approval Request')+'</h6><span class="badge bg-'+prCls+' mb-2">'+tblName+'</span></div></div><div class="small text-muted mb-2"><div>Amount: <strong>'+currency(a.amount)+'</strong></div><div>Requester ID: '+a.requested_by+'</div><div>Date: '+(a.created_at||'')+'</div></div><div class="d-flex flex-wrap gap-1 mt-2"><button class="btn btn-sm btn-success" onclick="approvalAction('+a.id+',\''+a.tbl+'\',\'approved\')"><i class="fas fa-check me-1"></i>Approve</button><button class="btn btn-sm btn-danger" onclick="approvalActionModal('+a.id+',\''+a.tbl+'\',\'rejected\')"><i class="fas fa-times me-1"></i>Reject</button><button class="btn btn-sm btn-warning text-dark" onclick="approvalActionModal('+a.id+',\''+a.tbl+'\',\'changes_requested\')"><i class="fas fa-edit me-1"></i>Changes</button><button class="btn btn-sm btn-purple" onclick="approvalActionModal('+a.id+',\''+a.tbl+'\',\'escalated\')"><i class="fas fa-arrow-up me-1"></i>Escalate</button></div></div></div></div>';});
        el.innerHTML=h;
    }).catch(function(){el.innerHTML='<div class="col-12 text-center text-danger py-3">Failed to load.</div>';});
});
function approvalActionModal(id,tbl,st){
    var cmt=prompt('Enter comments for "'+st+'" action:');
    if(cmt===null)return;
    var fd=new FormData();fd.append('id',id);fd.append('table',tbl);fd.append('status',st);fd.append('comments',cmt||'');fd.append('csrf_token', window.CSRF_TOKEN);
    if(st==='escalated'){var dg=prompt('Escalate to (DG user ID):');if(!dg)return;fd.append('escalated_to',parseInt(dg)||0);}
    fetch('director-finance.php?ajax=submit_approval_action',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(d.success)location.reload();else alert('Failed: '+(d.error||'Unknown'));}).catch(function(){alert('Error');});
}
</script>
<style>.btn-purple{background:#7c3aed;border-color:#7c3aed;color:#fff}.btn-purple:hover{background:#6d28d9;color:#fff}</style>
<?php endif; ?>

<!-- Modals -->
<div class="modal fade" id="budgetModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Create Budget</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" name="action" value="create_budget">
<div class="mb-3"><label class="fl">Budget Name *</label><input type="text" name="budget_name" class="form-control env-field" required></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Category</label><select name="budget_category" class="form-select env-field"><option>Operations</option><option>Capital</option><option>Personnel</option><option>Research</option><option>Development</option></select></div>
<div class="col-6"><label class="fl">Fiscal Year</label><input type="text" name="fiscal_year" class="form-control env-field" value="<?= date('Y') ?>"></div>
</div>
<div class="mb-3"><label class="fl">Allocated Amount *</label><input type="number" name="allocated_amount" class="form-control env-field" step="0.01" required></div>
<div class="mb-3"><label class="fl">Department</label><input type="text" name="department" class="form-control env-field"></div>
<div class="mb-3"><label class="fl">Description</label><textarea name="description" class="form-control env-field" rows="2"></textarea></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Create Budget</button></div>
</form></div></div></div>

<div class="modal fade" id="expModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" name="action" value="add_expense">
<div class="mb-3"><label class="fl">Category *</label><select name="category" class="form-select env-field"><option>Utilities</option><option>Office Supplies</option><option>Travel</option><option>Maintenance</option><option>Communication</option><option>Equipment</option><option>Construction</option><option>Consultancy</option><option>Training</option><option>Other</option></select></div>
<div class="mb-3"><label class="fl">Description *</label><textarea name="description" class="form-control env-field" rows="2" required></textarea></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Amount *</label><input type="number" name="amount" class="form-control env-field" step="0.01" required></div>
<div class="col-6"><label class="fl">Date</label><input type="date" name="expense_date" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
</div>
<div class="mb-3"><label class="fl">Department</label><input type="text" name="department" class="form-control env-field"></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Add Expense</button></div>
</form></div></div></div>

<div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Record Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" name="action" value="record_payment">
<div class="mb-3"><label class="fl">Student ID *</label><input type="number" name="student_id" class="form-control env-field" required placeholder="Enter student ID"></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Amount *</label><input type="number" name="amount" class="form-control env-field" step="0.01" required></div>
<div class="col-6"><label class="fl">Method</label><select name="payment_method" class="form-select env-field"><option value="cash">Cash</option><option value="bank">Bank Transfer</option><option value="cheque">Cheque</option><option value="mobile">Mobile Money</option><option value="pos">POS</option></select></div>
</div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Record Payment</button></div>
</form></div></div></div>

<?php
// -- Additional AJAX endpoints (referenced by JS but appended after sections) --
if ($ajax === 'finance_message_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.finance_messages ORDER BY created_at DESC LIMIT 50"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'finance_notice_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT * FROM {$students_db}.finance_notices ORDER BY created_at DESC LIMIT 50"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}
if ($ajax === 'update_audit_status' && $staff) {
    header('Content-Type: application/json');
    $id=(int)($_POST['id']??0); $st=$_POST['status']??'';
    if($id&&$st){
        $stmt=$staff->prepare("UPDATE {$students_db}.audit_findings SET status=? WHERE id=?");
        if($stmt){$stmt->bind_param('si',$st,$id);if($stmt->execute()&&$stmt->affected_rows>0){echo json_encode(['success'=>true]);$stmt->close();exit;}echo json_encode(['success'=>false,'error'=>'Update failed']);$stmt->close();exit;}
    }
    echo json_encode(['success'=>false]); exit;
}
if ($ajax === 'supplier_payment_list' && $staff) {
    header('Content-Type: application/json');
    $rows=[]; $r=$staff->query("SELECT sp.*,s.supplier_name FROM {$students_db}.supplier_payments sp LEFT JOIN {$students_db}.suppliers s ON sp.supplier_id=s.id ORDER BY sp.created_at DESC LIMIT 100"); if($r) while($rw=$r->fetch_assoc()) $rows[]=$rw;
    echo json_encode($rows); exit;
}

// -- Report Generation (print/PDF) --
$report = $_GET['report'] ?? '';
if ($report) {
    header('Content-Type: text/html; charset=utf-8');
    $from = $_GET['from'] ?? date('Y-m-01', strtotime('-1 month'));
    $to = $_GET['to'] ?? date('Y-m-d');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#f3f4f6}h2{color:#1f2937}.text-end{text-align:right}.fw-bold{font-weight:700}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print text-end mb-2"><button onclick="window.close()" class="btn btn-sm btn-outline-secondary" style="padding:6px 16px">Close</button></div>';

    if ($report === 'income_statement') {
        echo '<h2>Income Statement</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).'</p>';
        $rev = 0;
        if ($students) {
            $stmt = $students->prepare("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed') AND DATE(payment_date) BETWEEN ? AND ?");
            if ($stmt) { $stmt->bind_param('ss', $from, $to); $stmt->execute(); $r = $stmt->get_result(); if ($r) $rev = (float)$r->fetch_assoc()['t']; $stmt->close(); }
        }
        $exp = 0;
        $stmt = $staff->prepare("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE status IN('approved','paid') AND DATE(expense_date) BETWEEN ? AND ?");
        if ($stmt) { $stmt->bind_param('ss', $from, $to); $stmt->execute(); $r = $stmt->get_result(); if ($r) $exp = (float)$r->fetch_assoc()['t']; $stmt->close(); }
        echo '<table><thead><tr><th>Item</th><th class="text-end">Amount</th></tr></thead><tbody>';
        echo '<tr><td><strong>Revenue</strong></td><td class="text-end">'.number_format($rev,0).'</td></tr>';
        echo '<tr><td>Total Income</td><td class="text-end fw-bold">'.number_format($rev,0).'</td></tr>';
        $r2=null; $s2=$staff->prepare("SELECT expense_category,COALESCE(SUM(amount),0) t FROM expenses WHERE status IN('approved','paid') AND DATE(expense_date) BETWEEN ? AND ? GROUP BY expense_category");
        if($s2){$s2->bind_param('ss',$from,$to);$s2->execute();$r2=$s2->get_result();$s2->close();}
        if($r2) while($row=$r2->fetch_assoc()){ echo '<tr><td>&nbsp;&nbsp;'.htmlspecialchars($row['expense_category']).'</td><td class="text-end">'.number_format($row['t'],0).'</td></tr>'; }
        echo '<tr><td>Total Expenses</td><td class="text-end fw-bold">'.number_format($exp,0).'</td></tr>';
        echo '<tr><td><strong>Net Income</strong></td><td class="text-end fw-bold" style="color:'.($rev-$exp>=0?'green':'red').'">'.number_format($rev-$exp,0).'</td></tr>';
        echo '</tbody></table>';
    } elseif ($report === 'expense_report') {
        echo '<h2>Expense Report</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).'</p>';
        $sr=null; $ss=$staff->prepare("SELECT e.*,s.full_name requested_by_name FROM expenses e LEFT JOIN staff s ON e.requested_by=s.id WHERE DATE(e.expense_date) BETWEEN ? AND ? ORDER BY e.expense_date DESC");
        if($ss){$ss->bind_param('ss',$from,$to);$ss->execute();$sr=$ss->get_result();$ss->close();}
        echo '<table><thead><tr><th>ID</th><th>Category</th><th>Description</th><th class="text-end">Amount</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        if($sr) while($row=$sr->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['expense_id']).'</td><td>'.htmlspecialchars($row['expense_category']).'</td><td>'.htmlspecialchars($row['description']).'</td><td class="text-end">'.number_format($row['amount'],0).'</td><td>'.$row['expense_date'].'</td><td>'.$row['status'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'fee_collection') {
        echo '<h2>Fee Collection Report</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).'</p>';
        $qr=null; $qs=$students->prepare("SELECT p.payment_reference,s.full_name student_name,s.student_number,s.program,p.amount_received,p.payment_method,p.payment_date,p.status FROM {$students_db}.payments p LEFT JOIN {$students_db}.students s ON p.student_id=s.id WHERE DATE(p.payment_date) BETWEEN ? AND ? ORDER BY p.payment_date DESC");
        if($qs){$qs->bind_param('ss',$from,$to);$qs->execute();$qr=$qs->get_result();$qs->close();}
        echo '<table><thead><tr><th>Receipt</th><th>Student</th><th>Program</th><th class="text-end">Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        $tt=0; if($qr) while($row=$qr->fetch_assoc()){ $tt+=$row['amount_received']; echo '<tr><td>'.htmlspecialchars($row['payment_reference']).'</td><td>'.htmlspecialchars($row['student_name']??$row['student_number']).'</td><td>'.htmlspecialchars($row['program']??'-').'</td><td class="text-end">'.number_format($row['amount_received'],0).'</td><td>'.htmlspecialchars($row['payment_method']).'</td><td>'.$row['payment_date'].'</td><td>'.$row['status'].'</td></tr>'; }
        echo '<tr><td colspan="3"><strong>Total</strong></td><td class="text-end fw-bold">'.number_format($tt,0).'</td><td colspan="3"></td></tr>';
        echo '</tbody></table>';
    } elseif ($report === 'tax_report') {
        echo '<h2>URA Tax Report</h2><p>Period: '.htmlspecialchars($from).' to '.htmlspecialchars($to).'</p>';
        $rev=0; if($students){$rs=$students->prepare("SELECT COALESCE(SUM(amount_received),0) t FROM {$students_db}.payments WHERE status IN('verified','approved','completed') AND DATE(payment_date) BETWEEN ? AND ?");if($rs){$rs->bind_param('ss',$from,$to);$rs->execute();$rr=$rs->get_result();$rs->close();if($rr)$rev=(float)$rr->fetch_assoc()['t'];}}
        $exp=0; $es=$staff->prepare("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE status IN('approved','paid') AND DATE(expense_date) BETWEEN ? AND ?");if($es){$es->bind_param('ss',$from,$to);$es->execute();$er=$es->get_result();$es->close();if($er)$exp=(float)$er->fetch_assoc()['t'];}
        $taxable = max(0,$rev-$exp);
        echo '<table><thead><tr><th>Item</th><th class="text-end">Amount</th></tr></thead><tbody>';
        echo '<tr><td>Gross Revenue</td><td class="text-end">'.number_format($rev,0).'</td></tr>';
        echo '<tr><td>Allowable Expenses</td><td class="text-end">'.number_format($exp,0).'</td></tr>';
        echo '<tr><td>Taxable Income</td><td class="text-end">'.number_format($taxable,0).'</td></tr>';
        echo '<tr><td>Estimated VAT (18%)</td><td class="text-end">'.number_format($rev*0.18,0).'</td></tr>';
        echo '<tr><td>Withholding Tax (6%)</td><td class="text-end">'.number_format($exp*0.06,0).'</td></tr>';
        echo '</tbody></table>';
    }
    echo '</body></html>'; exit;
}
?>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<!-- ═══ AJAX MODULE LOADING ═══ -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.fin-content');
    var loadingOverlay = document.getElementById('ajaxLoadingOverlay');
    var isAjaxLoading = false;

    function showLoading() { if (loadingOverlay) loadingOverlay.style.display = 'flex'; isAjaxLoading = true; }
    function hideLoading() { if (loadingOverlay) loadingOverlay.style.display = 'none'; isAjaxLoading = false; }

    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (!href || href.indexOf('?') === -1) return;
            if (isAjaxLoading) return;

            e.preventDefault();
            showLoading();
            history.pushState({}, '', href);
            document.querySelectorAll('.child-link').forEach(function(l) { l.classList.remove('active'); });
            this.classList.add('active');

            var section = href.split('section=')[1] || href.split('page=')[1] || 'overview';
            fetch('director-finance.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.fin-content');
                if (newContent && contentArea) {
                    contentArea.innerHTML = newContent.innerHTML;
                    contentArea.querySelectorAll('script').forEach(function(oldScript) {
                        var newScript = document.createElement('script');
                        if (oldScript.src) { newScript.src = oldScript.src; }
                        else { newScript.textContent = oldScript.textContent; }
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                }
                hideLoading();
            })
            .catch(function(err) {
                console.error('[AJAX Load Error]', err);
                hideLoading();
                window.location.href = href;
            });
        });
    });

    window.addEventListener('popstate', function() { window.location.reload(); });

    document.querySelectorAll('.child-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                var sidebar = document.querySelector('.isnm-sidebar');
                if (sidebar) sidebar.classList.remove('open', 'mobile-show');
            }
        });
    });
})();
</script>

<script>
function esc(s){ if(!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function mbSubstr(s,n){ if(!s) return ''; return s.length>n?s.substring(0,n)+'...':s; }
function currency(n){ n=parseFloat(n)||0; return 'UGX '+n.toLocaleString('en-US',{minimumFractionDigits:0,maximumFractionDigits:0}); }
function exportTable(id){ var el=document.getElementById(id);if(!el)return; var csv=[];var rows=el.querySelectorAll('tr');rows.forEach(function(r){var cols=[];r.querySelectorAll('th,td').forEach(function(c){cols.push('"'+c.textContent.trim()+'"');});csv.push(cols.join(','));});var blob=new Blob([csv.join('\n')],{type:'text/csv'});var a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='finance_report.csv';a.click(); }
</script>
</div>
</body></html>
