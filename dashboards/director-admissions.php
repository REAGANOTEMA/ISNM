<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['admissions', 'director']);
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'] ?? null;
$website_conn = $ctx['website'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Director Admissions';

function safeCount($c, $s) { $r=$c->query($s); if(!$r)return 0; $w=$r->fetch_assoc(); return intval($w['c']??0); }

$total_apps        = $website_conn ? safeCount($website_conn,"SELECT COUNT(*)c FROM student_applications") : 0;
$pending_apps      = $website_conn ? safeCount($website_conn,"SELECT COUNT(*)c FROM student_applications WHERE status='Pending'") : 0;
$admitted_students = safeCount($staff_conn,"SELECT COUNT(*)c FROM student_admissions WHERE admission_status='Approved'");
$enrolled_students = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students WHERE status='Active'") : 0;

$applicants = []; if($website_conn){ $r=$website_conn->query("SELECT * FROM student_applications ORDER BY submitted_at DESC LIMIT 50"); if($r) while($row=$r->fetch_assoc()) $applicants[]=$row; }
$programs = []; $r=$staff_conn->query("SELECT program_code,program_name,program_type,department,duration_years,status FROM academic_programs WHERE status='Active' ORDER BY program_name"); if($r) while($row=$r->fetch_assoc()) $programs[]=$row;
// Migrate: ensure unique key on requirement_clearances
if ($staff_conn) {
    $uk = $staff_conn->query("SHOW INDEX FROM requirement_clearances WHERE Key_name='student_item'");
    if ($uk && $uk->num_rows === 0) {
        $staff_conn->query("ALTER TABLE requirement_clearances ADD UNIQUE KEY student_item (student_id, item_id)");
    }
}

$req_items = []; $r=$staff_conn->query("SELECT * FROM requirement_items ORDER BY display_order"); if($r) while($row=$r->fetch_assoc()) $req_items[]=$row;
$total_req_items = count($req_items);

$admissions_list = []; $r=$staff_conn->query("SELECT sa.*,s.full_name,s.student_number,s.course,s.phone FROM student_admissions sa LEFT JOIN igangaschoolofl_students_db.students s ON sa.student_id=s.id ORDER BY sa.created_at DESC LIMIT 50"); if($r) while($row=$r->fetch_assoc()) $admissions_list[]=$row;

// Requirements stats
$total_students_req = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status='Active'");
$total_clearances = safeCount($staff_conn, "SELECT COUNT(*) c FROM requirement_clearances WHERE cleared=1");
$cleared_students = 0;
if ($total_req_items > 0) {
    $cr = $staff_conn->query("SELECT COUNT(*) c FROM (SELECT student_id FROM requirement_clearances WHERE cleared=1 GROUP BY student_id HAVING COUNT(DISTINCT item_id) = $total_req_items) sub");
    if ($cr) { $crr = $cr->fetch_assoc(); $cleared_students = intval($crr['c'] ?? 0); }
}

$report = $_GET['report'] ?? '';
if ($report) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px}th{background:#f3f4f6}h2{color:#1f2937}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print"><button onclick="window.print()" style="padding:6px 16px;margin-bottom:12px">Print</button> <button onclick="window.close()" style="padding:6px 16px">Close</button></div>';
    if ($report === 'applications') {
        echo '<h2>Applications Report</h2>';
        $r=$website_conn->query("SELECT application_number,CONCAT(first_name,' ',surname) name,program_applied,status,submitted_at FROM student_applications ORDER BY submitted_at DESC");
        echo '<table><thead><tr><th>App No</th><th>Applicant</th><th>Program</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['application_number']).'</td><td>'.htmlspecialchars($row['name']).'</td><td>'.htmlspecialchars($row['program_applied']).'</td><td>'.$row['submitted_at'].'</td><td>'.$row['status'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'admitted') {
        echo '<h2>Admitted Students Report</h2>';
        $r=$staff_conn->query("SELECT sa.*,s.full_name,s.student_number,s.course,s.phone FROM student_admissions sa LEFT JOIN igangaschoolofl_students_db.students s ON sa.student_id=s.id WHERE sa.admission_status='Approved' ORDER BY sa.admission_date DESC");
        echo '<table><thead><tr><th>Adm No</th><th>Student</th><th>Reg No</th><th>Program</th><th>Year</th><th>Date</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['admission_number']).'</td><td>'.htmlspecialchars($row['full_name']??'-').'</td><td>'.htmlspecialchars($row['student_number']??'-').'</td><td>'.htmlspecialchars($row['program']).'</td><td>'.$row['academic_year'].'</td><td>'.$row['admission_date'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'clearance') {
        echo '<h2>Requirements Clearance Report</h2>';
        $r=$staff_conn->query("SELECT rc.*,s.full_name student_name,ri.item_name FROM requirement_clearances rc LEFT JOIN igangaschoolofl_students_db.students s ON rc.student_id=s.id LEFT JOIN requirement_items ri ON rc.item_id=ri.id ORDER BY rc.student_id,ri.display_order");
        echo '<table><thead><tr><th>Student</th><th>Item</th><th>Cleared</th><th>Cleared By</th><th>Date</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['student_name']??$row['student_id']).'</td><td>'.htmlspecialchars($row['item_name']).'</td><td>'.($row['cleared']?'Yes':'No').'</td><td>'.$row['cleared_by'].'</td><td>'.$row['cleared_at'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'intake') {
        echo '<h2>Intake Report</h2>';
        $r=$staff_conn->query("SELECT program,academic_year,COUNT(*) total FROM student_admissions GROUP BY program,academic_year ORDER BY academic_year DESC");
        echo '<table><thead><tr><th>Program</th><th>Year</th><th>Admitted</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['program']).'</td><td>'.$row['academic_year'].'</td><td>'.$row['total'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'financial') {
        echo '<h2>Financial Clearance Report</h2>';
        $r=$students_conn->query("SELECT s.id,s.full_name,s.student_number,s.course,COALESCE(si.total_invoiced,0) invoiced,COALESCE(p.total_paid,0) paid,COALESCE(si.total_invoiced,0)-COALESCE(p.total_paid,0) balance FROM students s LEFT JOIN (SELECT student_id,SUM(total_amount) total_invoiced FROM student_invoices GROUP BY student_id) si ON s.id=si.student_id LEFT JOIN (SELECT student_id,SUM(amount_received) total_paid FROM payments WHERE status='Completed' GROUP BY student_id) p ON s.id=p.student_id WHERE s.status='Active' ORDER BY balance DESC");
        echo '<table><thead><tr><th>Student</th><th>Reg No</th><th>Program</th><th>Invoiced</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ $bal = floatval($row['balance']); echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['student_number']).'</td><td>'.htmlspecialchars($row['course']).'</td><td class="text-right">'.number_format($row['invoiced'],0).'</td><td class="text-right">'.number_format($row['paid'],0).'</td><td class="text-right"><strong>'.number_format($bal,0).'</strong></td><td>'.($bal<=0?'<span class="text-success fw-bold">CLEARED</span>':'<span class="text-danger fw-bold">OUTSTANDING</span>').'</td></tr>'; }
        echo '</tbody></table>';
    }
    echo '</body></html>'; exit;
}

$ajax = $_GET['ajax'] ?? '';
$ajaxSid = intval($_GET['student_id'] ?? 0);

if ($ajax === 'search_students') {
    header('Content-Type: application/json');
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode([]); exit; }
    $sq = $students_conn->real_escape_string($q);
    $r = $students_conn->query("SELECT id,full_name,student_number,registration_number,index_number,course,phone,status FROM students WHERE full_name LIKE '%$sq%' OR student_number LIKE '%$sq%' OR registration_number LIKE '%$sq%' OR phone LIKE '%$sq%' OR index_number LIKE '%$sq%' ORDER BY full_name LIMIT 30");
    $out = [];
    if ($r) { while($row = $r->fetch_assoc()) $out[] = $row; }
    echo json_encode($out); exit;
}

if ($ajax === 'student_profile_data') {
    header('Content-Type: application/json');
    $sid = intval($_GET['student_id'] ?? 0);
    if (!$sid) { echo json_encode([]); exit; }
    $info = []; $rn = $students_conn->query("SELECT * FROM students WHERE id=$sid"); if ($rn) $info = $rn->fetch_assoc() ?: [];
    $req = []; $rr = $staff_conn->query("SELECT rc.*, ri.item_name, ri.display_order, ri.item_category FROM requirement_clearances rc RIGHT JOIN requirement_items ri ON rc.item_id=ri.id AND rc.student_id=$sid ORDER BY ri.display_order"); if ($rr) { while($row=$rr->fetch_assoc()) $req[] = $row; }
    $docs = []; $rd = $staff_conn->query("SELECT id,document_type,document_title,file_path,generated_by,generation_date FROM generated_documents WHERE student_id=$sid ORDER BY generation_date DESC"); if ($rd) { while($row=$rd->fetch_assoc()) $docs[] = $row; }
    $adm = []; $ra = $staff_conn->query("SELECT * FROM student_admissions WHERE student_id=$sid ORDER BY created_at DESC"); if ($ra) { while($row=$ra->fetch_assoc()) $adm[] = $row; }
    echo json_encode(['info'=>$info,'requirements'=>$req,'documents'=>$docs,'admissions'=>$adm]); exit;
}

if ($ajax === 'save_requirement_item') {
    header('Content-Type: application/json');
    $sid = intval($_POST['student_id'] ?? 0);
    $iid = intval($_POST['item_id'] ?? 0);
    $cleared = intval($_POST['cleared'] ?? 0);
    $notes = $staff_conn->real_escape_string(trim($_POST['notes'] ?? ''));
    if ($sid && $iid) {
        $staff_conn->query("INSERT INTO requirement_clearances (student_id, item_id, cleared, cleared_by, cleared_at, notes) VALUES ($sid, $iid, $cleared, $user_id, NOW(), '$notes') ON DUPLICATE KEY UPDATE cleared=$cleared, cleared_by=$user_id, cleared_at=NOW(), notes=IF('$notes'='',notes,'$notes')");
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false, 'error'=>'Invalid IDs']); exit;
}

if ($ajax === 'mark_all_cleared') {
    header('Content-Type: application/json');
    $sid = intval($_POST['student_id'] ?? 0);
    if ($sid) {
        foreach ($req_items as $ri) {
            $iid = $ri['id'];
            $staff_conn->query("INSERT INTO requirement_clearances (student_id, item_id, cleared, cleared_by, cleared_at) VALUES ($sid, $iid, 1, $user_id, NOW()) ON DUPLICATE KEY UPDATE cleared=1, cleared_by=$user_id, cleared_at=NOW()");
        }
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}

if ($ajax === 'unmark_all') {
    header('Content-Type: application/json');
    $sid = intval($_POST['student_id'] ?? 0);
    if ($sid) {
        $staff_conn->query("UPDATE requirement_clearances SET cleared=0 WHERE student_id=$sid");
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}

if ($ajax && $ajaxSid > 0) {
    header('Content-Type: application/json');
    if ($ajax === 'student_requirements') {
        $cleared = [];
        $r=$staff_conn->query("SELECT item_id,cleared FROM requirement_clearances WHERE student_id=$ajaxSid");
        if($r) while($row=$r->fetch_assoc()) $cleared[$row['item_id']] = $row['cleared'];
        echo json_encode(['cleared'=>$cleared]); exit;
    }
    if ($ajax === 'student_financial') {
        $info=[];$r=$students_conn->query("SELECT id,full_name,student_number,registration_number,course,phone,email,status FROM students WHERE id=$ajaxSid"); if($r)$info=$r->fetch_assoc();
        $inv=[];if($students_conn){$r=$students_conn->query("SELECT invoice_number,fee_type,total_amount,amount_paid,balance,status FROM student_invoices WHERE student_id=$ajaxSid ORDER BY issue_date DESC");if($r)while($row=$r->fetch_assoc())$inv[]=$row;}
        $pay=[];if($students_conn){$r=$students_conn->query("SELECT payment_reference,amount_received,payment_method,payment_date,status FROM payments WHERE student_id=$ajaxSid ORDER BY payment_date DESC");if($r)while($row=$r->fetch_assoc())$pay[]=$row;}
        echo json_encode(['info'=>$info,'invoices'=>$inv,'payments'=>$pay]); exit;
    }
    if ($ajax === 'student_profile') {
        $info=[];$r=$students_conn->query("SELECT * FROM students WHERE id=$ajaxSid"); if($r)$info=$r->fetch_assoc();
        $inv=[];if($students_conn){$r=$students_conn->query("SELECT invoice_number,fee_type,total_amount,amount_paid,balance,status FROM student_invoices WHERE student_id=$ajaxSid");if($r)while($row=$r->fetch_assoc())$inv[]=$row;}
        $pay=[];if($students_conn){$r=$students_conn->query("SELECT payment_reference,amount_received,payment_method,payment_date,status FROM payments WHERE student_id=$ajaxSid");if($r)while($row=$r->fetch_assoc())$pay[]=$row;}
        $docs=[];$r=$staff_conn->query("SELECT id,document_type,document_title,file_path,generation_date FROM generated_documents WHERE student_id=$ajaxSid");if($r)while($row=$r->fetch_assoc())$docs[]=$row;
        echo json_encode(['info'=>$info,'invoices'=>$inv,'payments'=>$pay,'documents'=>$docs]); exit;
    }
    echo json_encode([]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_student') {
        $fn  = $students_conn->real_escape_string(trim($_POST['full_name'] ?? ''));
        $reg = $students_conn->real_escape_string(trim($_POST['registration_number'] ?? ''));
        $ind = $students_conn->real_escape_string(trim($_POST['index_number'] ?? ''));
        $ph  = $students_conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $em  = $students_conn->real_escape_string(trim($_POST['email'] ?? ''));
        $prog = $students_conn->real_escape_string(trim($_POST['program'] ?? ''));
        $year = intval($_POST['intake_year'] ?? date('Y'));
        $gen = $students_conn->real_escape_string(trim($_POST['gender'] ?? 'Female'));
        $dob = $_POST['date_of_birth'] ?? '';
        $addr = $students_conn->real_escape_string(trim($_POST['address'] ?? ''));
        $guardian = $students_conn->real_escape_string(trim($_POST['guardian_name'] ?? ''));
        $gphone = $students_conn->real_escape_string(trim($_POST['guardian_phone'] ?? ''));
        $emer = $students_conn->real_escape_string(trim($_POST['emergency_name'] ?? ''));
        $emer_ph = $students_conn->real_escape_string(trim($_POST['emergency_phone'] ?? ''));

        if ($fn && $prog && $students_conn) {
            $parts = explode(' ', trim($fn), 2);
            $first = $students_conn->real_escape_string($parts[0]);
            $sur = $students_conn->real_escape_string($parts[1] ?? '');
            $snum = $reg ?: 'STU'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $students_conn->query("INSERT INTO students (student_number,registration_number,national_student_id_number,first_name,surname,full_name,gender,date_of_birth,phone,mobile_number,email,address,guardian_name,guardian_phone,emergency_contact_name,emergency_contact_phone,course,program,current_year,intake_date,status,created_at) VALUES ('$snum','$reg','$ind','$first','$sur','$fn','$gen','$dob','$ph','$ph','$em','$addr','$guardian','$gphone','$emer','$emer_ph','$prog','$prog',$year,'$year-01-01','Active',NOW())");
            if ($students_conn->affected_rows > 0) {
                $sid = $students_conn->insert_id;
                $admNo = 'ADM-'.date('Y').'-'.str_pad($sid,4,'0',STR_PAD_LEFT);
                $staff_conn->query("INSERT INTO student_admissions (admission_number,student_id,academic_year,program,admission_date,admission_status) VALUES ('$admNo',$sid,'$year','$prog',CURDATE(),'Approved')");
                $_SESSION['success'] = "Student $fn registered. Admission No: $admNo";
            } else { $_SESSION['error'] = 'Failed: '.$students_conn->error; }
        } else { $_SESSION['error'] = 'Name and program required.'; }
        header("Location: director-admissions.php"); exit;
    }

    if ($action === 'save_requirements') {
        $sid = intval($_POST['student_id'] ?? 0);
        $items = $_POST['cleared_items'] ?? [];
        if ($sid > 0) {
            $staff_conn->query("DELETE FROM requirement_clearances WHERE student_id=$sid");
            foreach ($items as $itemId) {
                $iid = intval($itemId);
                if ($iid > 0) $staff_conn->query("INSERT INTO requirement_clearances (student_id,item_id,cleared,cleared_by,cleared_at) VALUES ($sid,$iid,1,$user_id,NOW())");
            }
            $_SESSION['success'] = 'Requirements updated.';
        }
        header("Location: director-admissions.php#requirements"); exit;
    }

    if ($action === 'approve_application') {
        $appId = intval($_POST['application_id'] ?? 0);
        if ($website_conn && $appId > 0) {
            $qrAp = $website_conn->query("SELECT * FROM student_applications WHERE id=$appId"); $ap = $qrAp ? $qrAp->fetch_assoc() : null;
            if ($ap) {
                $fn = $ap['first_name']; $sn = $ap['surname']; $on = $ap['other_name']??'';
                $full = trim("$fn $on $sn"); $gen = $ap['gender']; $ph = $ap['phone'];
                $em = $ap['email']??''; $prog = $ap['program_applied'];
                $dob = $ap['date_of_birth']; $nat = $ap['nationality']??'Ugandan';
                $addr = $ap['address']??''; $snum = 'STU'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
                $students_conn->query("INSERT INTO students (student_number,first_name,surname,other_name,full_name,gender,date_of_birth,phone,mobile_number,email,address,nationality,course,program,status,created_at) VALUES ('$snum','$fn','$sn','$on','$full','$gen','$dob','$ph','$ph','$em','$addr','$nat','$prog','$prog','Active',NOW())");
                if ($students_conn->affected_rows > 0) {
                    $sid = $students_conn->insert_id;
                    $admNo = 'ADM-'.date('Y').'-'.str_pad($sid,4,'0',STR_PAD_LEFT);
                    $currYear = date('Y');
                    $staff_conn->query("INSERT INTO student_admissions (admission_number,student_id,academic_year,program,admission_date,admission_status) VALUES ('$admNo',$sid,'$currYear','$prog',CURDATE(),'Approved')");
                    $website_conn->query("UPDATE student_applications SET status='Admitted',reviewed_by=$user_id,reviewed_at=NOW() WHERE id=$appId");
                    $_SESSION['success'] = "Applicant converted to student: $full";
                } else { $_SESSION['error'] = 'Failed: '.$students_conn->error; }
            }
        }
        header("Location: director-admissions.php"); exit;
    }

    if ($action === 'reject_application') {
        $appId = intval($_POST['application_id'] ?? 0);
        if ($website_conn) $website_conn->query("UPDATE student_applications SET status='Rejected',reviewed_by=$user_id,reviewed_at=NOW() WHERE id=$appId");
        $_SESSION['success'] = 'Application rejected.';
        header("Location: director-admissions.php"); exit;
    }

    if ($action === 'add_req_item') {
        $iname = $staff_conn->real_escape_string(trim($_POST['item_name'] ?? ''));
        $order = intval($_POST['display_order'] ?? 0);
        if ($iname) {
            $staff_conn->query("INSERT INTO requirement_items (item_name,display_order) VALUES ('$iname',$order)");
            $_SESSION['success'] = "Requirement '$iname' added.";
        }
        header("Location: director-admissions.php#requirements"); exit;
    }

    if ($action === 'delete_req_item') {
        $iid = intval($_POST['item_id'] ?? 0);
        if ($iid > 0) {
            $staff_conn->query("DELETE FROM requirement_clearances WHERE item_id=$iid");
            $staff_conn->query("DELETE FROM requirement_items WHERE id=$iid");
            $_SESSION['success'] = 'Requirement item deleted.';
        }
        header("Location: director-admissions.php#requirements"); exit;
    }

    if ($action === 'upload_doc') {
        $sid = intval($_POST['student_id'] ?? 0);
        $title = $staff_conn->real_escape_string($_POST['doc_title'] ?? '');
        $dtype = $staff_conn->real_escape_string($_POST['doc_type'] ?? 'Admission Letter');
        if ($sid > 0 && $title && isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../uploads/admission_docs/' . $sid;
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            $fname = time() . '_' . preg_replace('/[^a-z0-9]/i', '_', $title) . '.' . $ext;
            if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $dir . '/' . $fname)) {
                $fpath = "uploads/admission_docs/$sid/$fname";
                $staff_conn->query("INSERT INTO generated_documents (document_type,student_id,generated_by,document_title,file_path) VALUES ('$dtype',$sid,$user_id,'$title','$fpath')");
                $_SESSION['success'] = "Document '$title' uploaded.";
            } else { $_SESSION['error'] = 'Upload failed.'; }
        } else { $_SESSION['error'] = 'Title and file required.'; }
        header("Location: director-admissions.php"); exit;
    }

    if ($action === 'delete_doc') {
        $did = intval($_POST['document_id'] ?? 0);
        $qrD = $staff_conn->query("SELECT file_path FROM generated_documents WHERE id=$did"); $d = $qrD ? $qrD->fetch_assoc() : null;
        if ($d && $d['file_path']) { $fp = __DIR__.'/../'.$d['file_path']; if (file_exists($fp)) @unlink($fp); }
        $staff_conn->query("DELETE FROM generated_documents WHERE id=$did");
        $_SESSION['success'] = 'Document deleted.';
        header("Location: director-admissions.php"); exit;
    }

    header("Location: director-admissions.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root { --adm-primary: #7c3aed; --adm-primary-light: #8b5cf6; --adm-bg: #f8fafc; }
.dashboard-header { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #3730a3 100%) !important; }
.dashboard-header h1 { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0; letter-spacing: -0.5px; }
.dashboard-header p { font-size: 0.8rem; color: rgba(255,255,255,0.7); margin: 2px 0 0 0; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px; }
.stat-card { background: #fff; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; transition: all 0.2s; }
.stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
.stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff; flex-shrink: 0; }
.stat-content h3 { font-size: 1.6rem; font-weight: 700; margin: 0; color: #1e293b; line-height: 1.2; }
.stat-content p { font-size: 0.75rem; margin: 0; color: #64748b; }
.section-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.section-card h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: #1e293b; }
.nav-tabs .nav-link { font-size: 0.85rem; padding: 8px 16px; color: #64748b; border: none; border-bottom: 2px solid transparent; font-weight: 500; }
.nav-tabs .nav-link.active { color: #7c3aed; border-bottom-color: #7c3aed; background: transparent; }
.progress { border-radius: 8px; background: #f1f5f9; }
.table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
.table td { font-size: 0.85rem; vertical-align: middle; color: #334155; }
.btn-sm { font-size: 0.78rem; padding: 3px 10px; border-radius: 6px; }
.btn-primary { background: #7c3aed; border-color: #7c3aed; }
.btn-primary:hover { background: #6d28d9; border-color: #6d28d9; }
.btn-outline-primary { color: #7c3aed; border-color: #7c3aed; }
.btn-outline-primary:hover { background: #7c3aed; border-color: #7c3aed; color: #fff; }
.alert { border-radius: 10px; font-size: 0.85rem; }
.form-control, .form-select { border-radius: 8px; font-size: 0.85rem; border-color: #e2e8f0; }
.form-control:focus, .form-select:focus { border-color: #7c3aed; box-shadow: 0 0 0 2px rgba(124,58,237,0.15); }
.modal-content { border-radius: 14px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.modal-header { border-radius: 14px 14px 0 0; }
.badge { font-size: 0.7rem; padding: 3px 8px; border-radius: 6px; font-weight: 500; }
.bg-purple { background-color: #7c3aed !important; }
.section-nav { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 16px; }
.section-nav a { padding: 6px 14px; border-radius: 8px; font-size: 0.8rem; color: #64748b; background: #f1f5f9; text-decoration: none; font-weight: 500; transition: all 0.15s; }
.section-nav a:hover, .section-nav a.active { background: #7c3aed; color: #fff; }
.card-header { font-size: 0.85rem; font-weight: 600; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.content-section { display: none; }
.content-section.active { display: block; }
.dashboard-content { padding: 0; }

/* Search results dropdown */
.search-results-dropdown { position:absolute; z-index:1000; width:100%; max-height:280px; overflow-y:auto; background:#fff; border:1px solid #e2e8f0; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.12); }
.search-result-item { display:flex; align-items:center; gap:10px; padding:10px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.1s; }
.search-result-item:hover { background:#f5f3ff; }
.search-result-item:last-child { border-bottom:none; }
.search-result-item .sr-name { font-weight:600; color:#1e293b; font-size:0.85rem; }
.search-result-item .sr-meta { font-size:0.75rem; color:#64748b; }
.search-result-item .sr-badge { font-size:0.65rem; }

/* Student info card */
.student-info-card { background:linear-gradient(135deg,#f5f3ff,#ede9fe); border:1px solid #c4b5fd; border-radius:12px; padding:16px; }
.student-avatar-lg { width:48px;height:48px;border-radius:50%;background:#7c3aed;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0; }

/* Requirement items */
.req-item-card { display:flex; align-items:center; gap:10px; padding:12px 14px; border:1px solid #e2e8f0; border-radius:10px; transition:all 0.15s; }
.req-item-card:hover { border-color:#c4b5fd; background:#fafaff; }
.req-item-card.cleared { background:#f0fdf4; border-color:#86efac; }
.req-item-card .req-check { width:18px;height:18px;cursor:pointer; }
.req-item-card .req-name { flex:1; font-size:0.85rem; font-weight:500; color:#1e293b; }
.req-item-card .req-status { font-size:0.7rem; }
.req-item-card .req-notes-input { font-size:0.75rem; border:1px solid #e2e8f0; border-radius:6px; padding:2px 8px; width:160px; }
.req-item-card .req-notes-input:focus { border-color:#7c3aed; outline:none; }

/* Profile modal tabs */
.profile-section { padding:20px; }
.profile-section h6 { font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; margin-bottom:12px; }
.profile-info-row { display:flex; padding:6px 0; border-bottom:1px solid #f1f5f9; }
.profile-info-row:last-child { border-bottom:none; }
.profile-info-label { width:140px; flex-shrink:0; font-size:0.8rem; color:#64748b; }
.profile-info-value { flex:1; font-size:0.85rem; color:#1e293b; font-weight:500; }

/* Directory search results */
.dir-result-card { display:flex; align-items:center; gap:14px; padding:14px 16px; border:1px solid #e2e8f0; border-radius:10px; transition:all 0.15s; }
.dir-result-card:hover { border-color:#c4b5fd; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
.dir-result-card .dir-avatar { width:44px;height:44px;border-radius:50%;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem; }
.dir-result-card .dir-info { flex:1; min-width:0; }
.dir-result-card .dir-name { font-weight:600; font-size:0.9rem; color:#1e293b; }
.dir-result-card .dir-meta { font-size:0.78rem; color:#64748b; display:flex;flex-wrap:wrap;gap:6px; }

/* Loading spinner overlay */
.loading-overlay { text-align:center; padding:30px; color:#7c3aed; }

/* Toast notification */
.toast-notification { position:fixed; top:20px; right:20px; z-index:999999; padding:12px 20px; border-radius:10px; font-size:0.85rem; font-weight:500; color:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.15); transform:translateX(120%); transition:transform 0.3s ease; }
.toast-notification.show { transform:translateX(0); }
.toast-notification.success { background:#059669; }
.toast-notification.error { background:#dc2626; }
.toast-notification.info { background:#0284c7; }

/* Mobile responsive */
@media (max-width:768px) {
  .stats-grid { grid-template-columns: repeat(2,1fr); }
  .req-item-card { flex-wrap:wrap; }
  .req-item-card .req-notes-input { width:100%; }
  .profile-info-row { flex-direction:column; gap:2px; }
  .profile-info-label { width:100%; }
  .dir-result-card { flex-wrap:wrap; }
  .search-student-box { position:relative; }
}
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <header class="dashboard-header">
            <div class="header-left">
                <h1>Director Admissions & Requirements</h1>
                <p>Applications, Admissions, Requirements Clearance & Student Management</p>
            </div>
            <div class="header-right">
                <div class="date-time"><i class="fas fa-calendar"></i><span id="currentDate"><?php echo date('l, F j, Y'); ?></span></div>
                <a href="../index.php" class="btn btn-sm btn-outline-light ms-1"><i class="fas fa-home"></i></a>
            </div>
        </header>

        <?php if(!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mx-3 mt-3 mb-0 py-2"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); endif; ?>
        <?php if(!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3 mb-0 py-2"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="p-3">
            <!-- Section Navigation -->
            <div class="section-nav">
                <a href="#" class="active" data-section="overview"><i class="fas fa-chart-pie me-1"></i>Overview</a>
                <a href="#" data-section="applications"><i class="fas fa-file-alt me-1"></i>Applications</a>
                <a href="#" data-section="admissions"><i class="fas fa-check-circle me-1"></i>Admissions</a>
                <a href="#" data-section="requirements"><i class="fas fa-clipboard-check me-1"></i>Requirements</a>
                <a href="#" data-section="directory"><i class="fas fa-address-book me-1"></i>Student Search</a>
                <a href="#" data-section="reports"><i class="fas fa-chart-bar me-1"></i>Reports</a>
            </div>

            <!-- ===== OVERVIEW ===== -->
            <section id="sec-overview" class="content-section active">
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon" style="background:#7c3aed"><i class="fas fa-file-alt"></i></div><div class="stat-content"><h3><?= $total_apps ?></h3><p>Total Applications</p></div></div>
                    <div class="stat-card"><div class="stat-icon" style="background:#f59e0b"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $pending_apps ?></h3><p>Pending Review</p></div></div>
                    <div class="stat-card"><div class="stat-icon" style="background:#059669"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $admitted_students ?></h3><p>Admitted</p></div></div>
                    <div class="stat-card"><div class="stat-icon" style="background:#0284c7"><i class="fas fa-user-graduate"></i></div><div class="stat-content"><h3><?= $enrolled_students ?></h3><p>Enrolled Students</p></div></div>
                    <div class="stat-card"><div class="stat-icon" style="background:#0891b2"><i class="fas fa-list-check"></i></div><div class="stat-content"><h3><?= $total_req_items ?></h3><p>Requirement Items</p></div></div>
                    <div class="stat-card"><div class="stat-icon" style="background:#8b5cf6"><i class="fas fa-user-check"></i></div><div class="stat-content"><h3><?= $cleared_students ?> / <?= $total_students_req ?></h3><p>Fully Cleared</p></div></div>

                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="section-card"><h2><i class="fas fa-bolt text-warning"></i>Quick Actions</h2>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-user-plus me-1"></i>Add Student</button>
                                <a href="../import_students_excel.php" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i>Import Excel</a>
                                <a href="../student-directory.php" class="btn btn-sm btn-outline-info"><i class="fas fa-address-book me-1"></i>Directory</a>
                                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="section-card"><h2><i class="fas fa-info-circle text-info"></i>At a Glance</h2>
                            <div class="row g-2 small">
                                <div class="col-6"><span class="text-muted">Pending Applications:</span> <strong><?= $pending_apps ?></strong></div>
                                <div class="col-6"><span class="text-muted">Admitted:</span> <strong><?= $admitted_students ?></strong></div>
                                <div class="col-6"><span class="text-muted">Fully Cleared:</span> <strong><?= $cleared_students ?> / <?= $total_students_req ?></strong></div>
                                <div class="col-6"><span class="text-muted">Requirement Items:</span> <strong><?= $total_req_items ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== APPLICATIONS ===== -->
            <section id="sec-applications" class="content-section">
                <div class="section-card">
                    <h2><i class="fas fa-file-alt" style="color:#7c3aed"></i>Applications Management</h2>
                    <?php if(empty($applicants)): ?>
                    <p class="text-muted small">No student applications in database.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>App No</th><th>Applicant</th><th>Program</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach($applicants as $a): $aname = htmlspecialchars(trim($a['first_name'].' '.$a['surname'])); ?>
                            <tr>
                                <td><code><?= htmlspecialchars($a['application_number']) ?></code></td>
                                <td><?= $aname ?></td>
                                <td><?= htmlspecialchars($a['program_applied']) ?></td>
                                <td><?= $a['submitted_at'] ?></td>
                                <td><span class="badge bg-<?= $a['status']==='Admitted'?'success':($a['status']==='Rejected'?'danger':($a['status']==='Pending'?'warning':'info')) ?>"><?= $a['status'] ?></span></td>
                                <td>
                                    <?php if($a['status']==='Pending'): ?>
                                    <form method="POST" class="d-inline"><input type="hidden" name="action" value="approve_application"><input type="hidden" name="application_id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-outline-success" onclick="return confirm('Approve and convert to student?')" title="Approve"><i class="fas fa-check"></i></button></form>
                                    <form method="POST" class="d-inline"><input type="hidden" name="action" value="reject_application"><input type="hidden" name="application_id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject application?')" title="Reject"><i class="fas fa-times"></i></button></form>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-info" onclick="alert('Name: <?= $aname ?>\nProgram: <?= htmlspecialchars($a['program_applied']) ?>\nPhone: <?= htmlspecialchars($a['phone']) ?>\nEmail: <?= htmlspecialchars($a['email']??'-') ?>\nDOB: <?= $a['date_of_birth'] ?>\nNationality: <?= htmlspecialchars($a['nationality']??'-') ?>')" title="View"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ===== ADMISSIONS ===== -->
            <section id="sec-admissions" class="content-section">
                <div class="section-card">
                    <h2><i class="fas fa-check-circle" style="color:#059669"></i>Admitted Students</h2>
                    <?php if(empty($admissions_list)): ?>
                    <p class="text-muted small">No admission records yet.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Adm No</th><th>Student</th><th>Reg No</th><th>Program</th><th>Year</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach($admissions_list as $a): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($a['admission_number']) ?></code></td>
                                <td><?= htmlspecialchars($a['full_name']??'-') ?></td>
                                <td><?= htmlspecialchars($a['student_number']??'-') ?></td>
                                <td><?= htmlspecialchars($a['program']) ?></td>
                                <td><?= $a['academic_year'] ?></td>
                                <td><?= $a['admission_date'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ===== REQUIREMENTS PORTAL ===== -->
            <section id="sec-requirements" class="content-section">
                <div class="section-card">
                    <h2><i class="fas fa-clipboard-check" style="color:#7c3aed"></i>Requirements Portal</h2>
                    <p class="text-muted small mb-3">Record and track admission requirements clearance per student.</p>

                    <div class="stats-grid small mb-3">
                        <div class="stat-card p-2"><div class="stat-icon" style="font-size:1.1rem;width:36px;height:36px;background:#7c3aed"><i class="fas fa-list-check"></i></div><div class="stat-content"><h4 style="font-size:1.1rem;margin:0"><?= $total_req_items ?></h4><p style="font-size:0.7rem;margin:0;color:#64748b">Requirement Items</p></div></div>
                        <div class="stat-card p-2"><div class="stat-icon" style="font-size:1.1rem;width:36px;height:36px;background:#0284c7"><i class="fas fa-users"></i></div><div class="stat-content"><h4 style="font-size:1.1rem;margin:0"><?= $total_students_req ?></h4><p style="font-size:0.7rem;margin:0;color:#64748b">Active Students</p></div></div>
                        <div class="stat-card p-2"><div class="stat-icon" style="font-size:1.1rem;width:36px;height:36px;background:#059669"><i class="fas fa-check-double"></i></div><div class="stat-content"><h4 style="font-size:1.1rem;margin:0"><?= $total_clearances ?></h4><p style="font-size:0.7rem;margin:0;color:#64748b">Total Clearances</p></div></div>
                        <div class="stat-card p-2"><div class="stat-icon" style="font-size:1.1rem;width:36px;height:36px;background:#8b5cf6"><i class="fas fa-user-check"></i></div><div class="stat-content"><h4 style="font-size:1.1rem;margin:0"><?= $cleared_students ?> / <?= $total_students_req ?></h4><p style="font-size:0.7rem;margin:0;color:#64748b">Fully Cleared</p></div></div>
                    </div>

                    <ul class="nav nav-tabs mb-3" id="reqTabs">
                        <li class="nav-item"><a class="nav-link active" href="#reqPerStudent" data-bs-toggle="tab">Per-Student Clearance</a></li>
                        <li class="nav-item"><a class="nav-link" href="#reqOverview" data-bs-toggle="tab">Requirements Overview</a></li>
                        <li class="nav-item"><a class="nav-link" href="#reqManageItems" data-bs-toggle="tab">Manage Items</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="reqPerStudent">
                            <!-- Search section - no preloaded students -->
                            <div class="search-student-box mb-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="reqStudentSearch" class="form-control" placeholder="Search by name, admission number, or phone..." autocomplete="off">
                                    <button class="btn btn-primary" onclick="searchReqStudent()"><i class="fas fa-search me-1"></i>Search</button>
                                </div>
                                <div id="reqSearchResults" class="search-results-dropdown mt-1" style="display:none"></div>
                            </div>

                            <!-- Student info card (hidden until selected) -->
                            <div id="reqStudentInfoCard" class="student-info-card mb-3" style="display:none">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div class="student-avatar-lg"><i class="fas fa-user-graduate fa-2x"></i></div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0" id="reqSName">-</h5>
                                        <div class="d-flex gap-3 small text-muted flex-wrap">
                                            <span><i class="fas fa-id-card me-1"></i><span id="reqSReg">-</span></span>
                                            <span><i class="fas fa-book me-1"></i><span id="reqSProgram">-</span></span>
                                            <span><i class="fas fa-phone me-1"></i><span id="reqSPhone">-</span></span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="progress-circle" id="reqProgressCircle">
                                            <svg width="60" height="60" viewBox="0 0 60 60"><circle cx="30" cy="30" r="26" fill="none" stroke="#e5e7eb" stroke-width="4"/><circle id="reqProgressArc" cx="30" cy="30" r="26" fill="none" stroke="#7c3aed" stroke-width="4" stroke-dasharray="163.36" stroke-dashoffset="163.36" transform="rotate(-90 30 30)"/><text x="30" y="30" text-anchor="middle" dominant-baseline="central" font-size="10" font-weight="bold" fill="#1e293b" id="reqProgressPct">0%</text></svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="progress mt-2" style="height:6px">
                                    <div id="reqProgressBar" class="progress-bar bg-purple" role="progressbar" style="width:0%"></div>
                                </div>
                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <span class="badge bg-success" id="reqBadgeCleared">0 Cleared</span>
                                    <span class="badge bg-warning text-dark" id="reqBadgePending">0 Pending</span>
                                </div>
                            </div>

                            <!-- Requirements checklist -->
                            <div id="requirementsList">
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
                                    <p>Search for a student above to manage their requirement clearance.</p>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="reqOverview">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>#</th><th>Requirement Item</th><th>Category</th><th>Students Cleared</th><th>Progress</th><th>Order</th></tr></thead>
                                    <tbody>
                                        <?php $ri_idx=1; foreach($req_items as $ri):
                                            $rc = safeCount($staff_conn, "SELECT COUNT(*) c FROM requirement_clearances WHERE item_id={$ri['id']} AND cleared=1");
                                            $pct = $total_students_req > 0 ? round(($rc/$total_students_req)*100) : 0;
                                            $bar = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <tr>
                                            <td><?= $ri_idx++ ?></td>
                                            <td><?= htmlspecialchars($ri['item_name']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($ri['item_category'] ?? 'General') ?></span></td>
                                            <td><?= $rc ?> / <?= $total_students_req ?></td>
                                            <td style="min-width:180px"><div class="progress" style="height:18px"><div class="progress-bar <?= $bar ?>" role="progressbar" style="width:<?= $pct ?>%"><?= $pct ?>%</div></div></td>
                                            <td><?= $ri['display_order'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="reqManageItems">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <div class="card"><div class="card-header py-2"><strong>Add New Requirement</strong></div>
                                        <div class="card-body">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="add_req_item">
                                                <div class="mb-2"><label class="form-label small">Item Name</label><input type="text" name="item_name" class="form-control form-control-sm" required placeholder="e.g. O-Level Certificate"></div>
                                                <div class="mb-2"><label class="form-label small">Display Order</label><input type="number" name="display_order" class="form-control form-control-sm" value="<?= count($req_items)+1 ?>"></div>
                                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Add Item</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead><tr><th>Item</th><th>Category</th><th>Order</th><th>Action</th></tr></thead>
                                            <tbody>
                                                <?php foreach($req_items as $ri): ?>
                                                <tr><td><?= htmlspecialchars($ri['item_name']) ?></td><td><span class="badge bg-secondary"><?= htmlspecialchars($ri['item_category'] ?? 'General') ?></span></td><td><?= $ri['display_order'] ?></td>
                                                    <td><form method="POST" class="d-inline" onsubmit="return confirm('Delete this requirement item?')"><input type="hidden" name="action" value="delete_req_item"><input type="hidden" name="item_id" value="<?= $ri['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0"><i class="fas fa-trash"></i></button></form></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== STUDENT SEARCH ===== -->
            <section id="sec-directory" class="content-section">
                <div class="section-card">
                    <h2><i class="fas fa-search" style="color:#0284c7"></i>Student Search</h2>
                    <p class="text-muted small mb-3">Find students by name, admission number, or phone number.</p>
                    <div class="search-box mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="dirSearchInput" class="form-control" placeholder="Type name, admission number, or phone..." autocomplete="off">
                            <button class="btn btn-primary" onclick="searchDirectory()"><i class="fas fa-search me-1"></i>Search</button>
                        </div>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <small class="text-muted">Search by:</small>
                            <span class="badge bg-light text-dark border">Full Name</span>
                            <span class="badge bg-light text-dark border">Admission Number</span>
                            <span class="badge bg-light text-dark border">Phone Number</span>
                        </div>
                    </div>
                    <div id="dirResults">
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-user-graduate fa-4x mb-3 opacity-25"></i>
                            <p class="mb-1">Enter at least 2 characters to search for students.</p>
                            <small>Results will appear here.</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== REPORTS ===== -->
            <section id="sec-reports" class="content-section">
                <div class="section-card">
                    <h2><i class="fas fa-chart-bar" style="color:#7c3aed"></i>Admissions Reports</h2>
                    <div class="row g-3">
                        <div class="col-md-3 col-6"><div class="card card-body text-center py-3" style="cursor:pointer;border-radius:10px;border:1px solid #e2e8f0;transition:all 0.2s" onclick="window.open('director-admissions.php?report=applications','_blank')"><i class="fas fa-file-alt fa-2x mb-2" style="color:#7c3aed"></i><strong class="small">Applications</strong></div></div>
                        <div class="col-md-3 col-6"><div class="card card-body text-center py-3" style="cursor:pointer;border-radius:10px;border:1px solid #e2e8f0;transition:all 0.2s" onclick="window.open('director-admissions.php?report=admitted','_blank')"><i class="fas fa-check-circle fa-2x mb-2" style="color:#059669"></i><strong class="small">Admitted</strong></div></div>
                        <div class="col-md-3 col-6"><div class="card card-body text-center py-3" style="cursor:pointer;border-radius:10px;border:1px solid #e2e8f0;transition:all 0.2s" onclick="window.open('director-admissions.php?report=clearance','_blank')"><i class="fas fa-clipboard-check fa-2x mb-2" style="color:#8b5cf6"></i><strong class="small">Clearance</strong></div></div>
                        <div class="col-md-3 col-6"><div class="card card-body text-center py-3" style="cursor:pointer;border-radius:10px;border:1px solid #e2e8f0;transition:all 0.2s" onclick="window.open('director-admissions.php?report=intake','_blank')"><i class="fas fa-calendar-alt fa-2x mb-2" style="color:#0284c7"></i><strong class="small">Intake</strong></div></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="add_student">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Register New Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Registration Number</label><input type="text" name="registration_number" class="form-control" placeholder="Auto-generated if empty"></div>
                    <div class="col-md-4"><label class="form-label">Index Number</label><input type="text" name="index_number" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><option>Female</option><option>Male</option></select></div>
                    <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Phone *</label><input type="text" name="phone" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="1"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Program *</label>
                        <select name="program" class="form-select" required><option value="">Select Program</option>
                        <?php foreach($programs as $p): ?><option value="<?= htmlspecialchars($p['program_name']) ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Intake Year</label><input type="number" name="intake_year" class="form-control" value="<?= date('Y') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Guardian Name</label><input type="text" name="guardian_name" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Guardian Phone</label><input type="text" name="guardian_phone" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Emergency Contact</label><input type="text" name="emergency_name" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Emergency Phone</label><input type="text" name="emergency_phone" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Register</button></div>
        </form>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_doc">
            <input type="hidden" name="student_id" id="uploadDocStudentId">
            <div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Document</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Type</label><select name="doc_type" class="form-select"><option>Admission Letter</option><option>Certificate</option><option>Passport Photo</option><option>Identification</option><option>Medical Form</option><option>Other</option></select></div>
                <div class="mb-2"><label class="form-label">Title</label><input type="text" name="doc_title" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">File</label><input type="file" name="doc_file" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-info text-white"><i class="fas fa-upload me-1"></i>Upload</button></div>
        </form>
    </div>
</div>

<!-- Student Profile Modal -->
<div class="modal fade" id="studentProfileModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#1e1b4b,#3730a3);color:#fff"><h5 class="modal-title"><i class="fas fa-user-graduate me-2"></i>Student Profile</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-0" id="studentProfileBody"><div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x" style="color:#7c3aed"></i><p class="mt-2 text-muted">Loading profile...</p></div></div>
            <div class="modal-footer bg-light"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== Section Navigation =====
(function(){
    var navLinks = document.querySelectorAll('.section-nav a');
    navLinks.forEach(function(link){
        link.addEventListener('click', function(e){
            e.preventDefault();
            var section = this.getAttribute('data-section');
            navLinks.forEach(function(a){ a.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('.content-section').forEach(function(s){ s.classList.remove('active'); });
            var target = document.getElementById('sec-' + section);
            if (target) target.classList.add('active');
            if (section === 'requirements') {
                var tabEl = document.querySelector('#reqTabs .nav-link');
                if (tabEl && typeof bootstrap !== 'undefined') {
                    var tab = new bootstrap.Tab(tabEl);
                    if (tab) tab.show();
                }
            }
        });
    });
})();

// ===== Toast Notification =====
function showToast(msg, type){
    type = type || 'success';
    var existing = document.getElementById('appToast');
    if (existing) existing.remove();
    var t = document.createElement('div');
    t.id = 'appToast';
    t.className = 'toast-notification ' + type;
    t.innerHTML = msg;
    document.body.appendChild(t);
    setTimeout(function(){ t.classList.add('show'); }, 50);
    setTimeout(function(){ t.classList.remove('show'); setTimeout(function(){ t.remove(); }, 300); }, 3500);
}

// ===== REQUIREMENTS PORTAL =====
var _selectedReqStudentId = 0;

function searchReqStudent(){
    var q = document.getElementById('reqStudentSearch').value.trim();
    var resultsDiv = document.getElementById('reqSearchResults');
    if (q.length < 2) { resultsDiv.style.display = 'none'; return; }
    resultsDiv.style.display = '';
    resultsDiv.innerHTML = '<div class="p-2 text-center text-muted small"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
    fetch('director-admissions.php?ajax=search_students&q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (!data || !data.length) {
                resultsDiv.innerHTML = '<div class="p-2 text-center text-muted small">No students found matching "'+htmlEsc(q)+'".</div>';
                return;
            }
            var h = '';
            data.forEach(function(s){
                var name = htmlEsc(s.full_name || '');
                var reg = htmlEsc(s.registration_number || s.student_number || '-');
                var prog = htmlEsc(s.course || '');
                var phone = htmlEsc(s.phone || '');
                var status = s.status || '';
                var badge = status === 'Active' ? 'bg-success' : 'bg-secondary';
                h += '<div class="search-result-item" onclick="selectReqStudent('+s.id+')">'+
                    '<div class="student-avatar-lg" style="width:36px;height:36px;font-size:0.8rem"><i class="fas fa-user-graduate"></i></div>'+
                    '<div style="flex:1;min-width:0"><div class="sr-name">'+name+'</div><div class="sr-meta">'+reg+' | '+prog+' | '+phone+'</div></div>'+
                    '<span class="sr-badge badge '+badge+'">'+status+'</span>'+
                    '</div>';
            });
            resultsDiv.innerHTML = h;
        })
        .catch(function(){
            resultsDiv.innerHTML = '<div class="p-2 text-center text-danger small">Search failed.</div>';
        });
}

document.addEventListener('DOMContentLoaded', function(){
    var reqInput = document.getElementById('reqStudentSearch');
    if (reqInput) {
        reqInput.addEventListener('keyup', function(e){
            if (e.key === 'Enter') { searchReqStudent(); }
            else { setTimeout(searchReqStudent, 300); }
        });
        reqInput.addEventListener('blur', function(){
            setTimeout(function(){ var rd = document.getElementById('reqSearchResults'); if(rd) rd.style.display = 'none'; }, 200);
        });
        reqInput.addEventListener('focus', function(){
            if (this.value.length >= 2) {
                var rd = document.getElementById('reqSearchResults');
                if (rd) rd.style.display = '';
            }
        });
    }
});

function selectReqStudent(id){
    _selectedReqStudentId = id;
    document.getElementById('reqSearchResults').style.display = 'none';
    document.getElementById('reqStudentSearch').value = '';
    loadRequirements(id);
}

function loadRequirements(sid){
    var list = document.getElementById('requirementsList');
    list.innerHTML = '<div class="loading-overlay"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading requirements...</p></div>';
    document.getElementById('reqStudentInfoCard').style.display = 'none';

    fetch('director-admissions.php?ajax=student_profile_data&student_id=' + sid)
        .then(function(r){ return r.json(); })
        .then(function(d){
            var info = d.info || {};
            var reqData = d.requirements || [];
            var totalItems = <?= $total_req_items ?>;

            // Build cleared map
            var clearedMap = {};
            var notesMap = {};
            reqData.forEach(function(r){
                if (r.cleared == 1) clearedMap[r.item_id] = true;
                if (r.notes) notesMap[r.item_id] = r.notes;
            });

            var clearedCount = Object.keys(clearedMap).length;
            var pct = totalItems > 0 ? Math.round((clearedCount / totalItems) * 100) : 0;

            // Show student info card
            document.getElementById('reqSName').textContent = info.full_name || '-';
            document.getElementById('reqSReg').textContent = info.registration_number || info.student_number || '-';
            document.getElementById('reqSProgram').textContent = info.course || '-';
            document.getElementById('reqSPhone').textContent = info.phone || '-';
            updateReqProgress(pct, clearedCount, totalItems - clearedCount);
            document.getElementById('reqStudentInfoCard').style.display = '';

            // Build requirement items
            if (totalItems === 0) {
                list.innerHTML = '<div class="text-center py-4 text-muted"><p>No requirement items configured.</p></div>';
                return;
            }

            var h = '<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">'+
                '<span class="fw-bold small text-muted">REQUIREMENT CHECKLIST ('+totalItems+' items)</span>'+
                '<div class="d-flex gap-2">'+
                '<button class="btn btn-sm btn-success" onclick="markAllCleared()"><i class="fas fa-check-double me-1"></i>Mark All Cleared</button>'+
                '<button class="btn btn-sm btn-outline-secondary" onclick="unmarkAll()"><i class="fas fa-undo me-1"></i>Reset All</button>'+
                '</div></div>'+
                '<div class="row g-2" id="reqChecklist">';

            <?php foreach($req_items as $ri): ?>
            (function(itemId, itemName, itemCat){
                var isCleared = clearedMap[itemId] || false;
                var notes = notesMap[itemId] || '';
                h += '<div class="col-md-6 col-lg-4">'+
                    '<div class="req-item-card' + (isCleared ? ' cleared' : '') + '" data-item-id="' + itemId + '">'+
                    '<input type="checkbox" class="req-check" id="rcb_' + itemId + '" ' + (isCleared ? 'checked' : '') + ' onchange="toggleRequirement('+sid+','+itemId+',this.checked)">'+
                    '<div style="flex:1;min-width:0"><div class="req-name">' + htmlEsc(itemName) + '</div>'+
                    '<div><span class="badge bg-light text-muted border" style="font-size:0.65rem">' + htmlEsc(itemCat) + '</span></div></div>'+
                    '<div class="req-status">' + (isCleared ? '<span class="badge bg-success"><i class="fas fa-check"></i> Cleared</span>' : '<span class="badge bg-warning text-dark">Pending</span>') + '</div>'+
                    '<input type="text" class="req-notes-input" placeholder="Notes..." value="' + htmlEsc(notes) + '" onblur="saveReqNotes('+sid+','+itemId+',this.value)">'+
                    '</div></div>';
            })(<?= $ri['id'] ?>, <?= json_encode($ri['item_name']) ?>, <?= json_encode($ri['item_category'] ?? 'General') ?>);
            <?php endforeach; ?>

            h += '</div></div>';
            list.innerHTML = h;
        })
        .catch(function(e){
            console.error(e);
            list.innerHTML = '<div class="alert alert-danger text-center m-3">Failed to load student data.</div>';
        });
}

function toggleRequirement(sid, iid, checked){
    var formData = new FormData();
    formData.append('student_id', sid);
    formData.append('item_id', iid);
    formData.append('cleared', checked ? 1 : 0);
    fetch('director-admissions.php?ajax=save_requirement_item', { method:'POST', body: formData })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.success) {
                var card = document.querySelector('.req-item-card[data-item-id="'+iid+'"]');
                if (card) {
                    card.classList.toggle('cleared', checked);
                    var statusEl = card.querySelector('.req-status');
                    if (statusEl) {
                        statusEl.innerHTML = checked ? '<span class="badge bg-success"><i class="fas fa-check"></i> Cleared</span>' : '<span class="badge bg-warning text-dark">Pending</span>';
                    }
                }
                reloadReqProgress(sid);
                showToast(checked ? 'Item cleared' : 'Item unmarked', 'info');
            } else {
                showToast('Failed to save', 'error');
            }
        })
        .catch(function(){ showToast('Network error', 'error'); });
}

function saveReqNotes(sid, iid, notes){
    var formData = new FormData();
    formData.append('student_id', sid);
    formData.append('item_id', iid);
    formData.append('notes', notes);
    fetch('director-admissions.php?ajax=save_requirement_item', { method:'POST', body: formData })
        .then(function(r){ return r.json(); })
        .catch(function(){});
}

function markAllCleared(){
    if (!_selectedReqStudentId) return;
    if (!confirm('Mark all requirements as cleared for this student?')) return;
    var formData = new FormData();
    formData.append('student_id', _selectedReqStudentId);
    fetch('director-admissions.php?ajax=mark_all_cleared', { method:'POST', body: formData })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.success) {
                showToast('All requirements cleared!', 'success');
                loadRequirements(_selectedReqStudentId);
            } else { showToast('Failed', 'error'); }
        })
        .catch(function(){ showToast('Network error', 'error'); });
}

function unmarkAll(){
    if (!_selectedReqStudentId) return;
    if (!confirm('Reset all requirements for this student?')) return;
    var formData = new FormData();
    formData.append('student_id', _selectedReqStudentId);
    fetch('director-admissions.php?ajax=unmark_all', { method:'POST', body: formData })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.success) {
                showToast('All requirements reset.', 'info');
                loadRequirements(_selectedReqStudentId);
            } else { showToast('Failed', 'error'); }
        })
        .catch(function(){ showToast('Network error', 'error'); });
}

function reloadReqProgress(sid){
    // Refresh progress by re-fetching just the data
    fetch('director-admissions.php?ajax=student_profile_data&student_id=' + sid)
        .then(function(r){ return r.json(); })
        .then(function(d){
            var reqData = d.requirements || [];
            var totalItems = <?= $total_req_items ?>;
            var clearedCount = 0;
            reqData.forEach(function(r){ if (r.cleared == 1) clearedCount++; });
            var pct = totalItems > 0 ? Math.round((clearedCount / totalItems) * 100) : 0;
            updateReqProgress(pct, clearedCount, totalItems - clearedCount);
        })
        .catch(function(){});
}

function updateReqProgress(pct, cleared, pending){
    var arc = document.getElementById('reqProgressArc');
    var pctEl = document.getElementById('reqProgressPct');
    var bar = document.getElementById('reqProgressBar');
    var badgeCleared = document.getElementById('reqBadgeCleared');
    var badgePending = document.getElementById('reqBadgePending');
    if (arc) {
        var circumference = 2 * Math.PI * 26;
        var offset = circumference - (pct / 100) * circumference;
        arc.style.strokeDasharray = circumference;
        arc.style.strokeDashoffset = offset;
    }
    if (pctEl) pctEl.textContent = pct + '%';
    if (bar) { bar.style.width = pct + '%'; bar.textContent = ''; }
    if (badgeCleared) badgeCleared.textContent = cleared + ' Cleared';
    if (badgePending) badgePending.textContent = pending + ' Pending';
}

// ===== DIRECTORY SEARCH =====
function searchDirectory(){
    var q = document.getElementById('dirSearchInput').value.trim();
    var results = document.getElementById('dirResults');
    if (q.length < 2) {
        results.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-user-graduate fa-4x mb-3 opacity-25"></i><p>Enter at least 2 characters to search.</p></div>';
        return;
    }
    results.innerHTML = '<div class="loading-overlay"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Searching students...</p></div>';
    fetch('director-admissions.php?ajax=search_students&q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (!data || !data.length) {
                results.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-search fa-3x mb-3 opacity-25"></i><p>No students found matching "<strong>'+htmlEsc(q)+'</strong>".</p></div>';
                return;
            }
            var h = '<div class="d-flex justify-content-between align-items-center mb-2"><span class="small text-muted">Found <strong>'+data.length+'</strong> student(s)</span></div>';
            data.forEach(function(s){
                var name = htmlEsc(s.full_name || '');
                var reg = htmlEsc(s.registration_number || s.student_number || '-');
                var prog = htmlEsc(s.course || '-');
                var phone = htmlEsc(s.phone || '-');
                var status = s.status || '';
                var badge = status === 'Active' ? 'bg-success' : 'bg-secondary';
                h += '<div class="dir-result-card mb-2">'+
                    '<div class="dir-avatar"><i class="fas fa-user-graduate"></i></div>'+
                    '<div class="dir-info"><div class="dir-name">'+name+'</div>'+
                    '<div class="dir-meta"><span><i class="fas fa-id-card me-1"></i>'+reg+'</span><span><i class="fas fa-book me-1"></i>'+prog+'</span><span><i class="fas fa-phone me-1"></i>'+phone+'</span></div></div>'+
                    '<span class="badge '+badge+' me-2">'+status+'</span>'+
                    '<button class="btn btn-sm btn-outline-primary" onclick="viewStudentProfile('+s.id+')"><i class="fas fa-eye me-1"></i>View</button>'+
                    '<button class="btn btn-sm btn-outline-info" onclick="uploadDoc('+s.id+',\''+name+'\')"><i class="fas fa-upload"></i></button>'+
                    '</div>';
            });
            results.innerHTML = h;
        })
        .catch(function(){
            results.innerHTML = '<div class="alert alert-danger text-center m-3">Search failed. Please try again.</div>';
        });
}

document.addEventListener('DOMContentLoaded', function(){
    var dirInput = document.getElementById('dirSearchInput');
    if (dirInput) {
        dirInput.addEventListener('keyup', function(e){
            if (e.key === 'Enter') searchDirectory();
        });
    }
});

// ===== STUDENT PROFILE =====
function viewStudentProfile(id){
    var modal = new bootstrap.Modal(document.getElementById('studentProfileModal'));
    document.getElementById('studentProfileBody').innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x" style="color:#7c3aed"></i><p class="mt-2 text-muted">Loading profile...</p></div>';
    modal.show();
    fetch('director-admissions.php?ajax=student_profile_data&student_id='+id)
        .then(function(r){ return r.json(); })
        .then(function(d){
            var info = d.info || {};
            var reqData = d.requirements || [];
            var docs = d.documents || [];
            var adm = d.admissions || [];

            var clearedSet = {};
            reqData.forEach(function(r){ if (r.cleared == 1) clearedSet[r.item_id] = r; });

            var totalReq = <?= $total_req_items ?>;
            var clearedCount = Object.keys(clearedSet).length;
            var pct = totalReq > 0 ? Math.round((clearedCount/totalReq)*100) : 0;

            var h = '<div style="padding:20px">'+
                '<div class="row g-3">'+
                '<div class="col-md-4">'+
                '<div class="text-center p-3" style="background:#f8fafc;border-radius:10px">'+
                '<div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.8rem">'+
                '<i class="fas fa-user-graduate"></i></div>'+
                '<h5 class="mb-0" style="font-size:1rem">'+(info.full_name||'')+'</h5>'+
                '<div class="small text-muted">'+(info.registration_number||info.student_number||'-')+'</div>'+
                '<div class="mt-2"><span class="badge bg-'+(info.status==='Active'?'success':'secondary')+'">'+(info.status||'-')+'</span></div>'+
                '</div>'+
                '<div class="mt-3 p-3 rounded" style="background:#f5f3ff;border:1px solid #e0e7ff">'+
                '<div class="small fw-bold text-purple mb-2"><i class="fas fa-clipboard-check me-1"></i> Clearance Progress</div>'+
                '<div class="d-flex align-items-center gap-3 mb-2">'+
                '<div class="progress-circle"><svg width="56" height="56" viewBox="0 0 60 60"><circle cx="30" cy="30" r="26" fill="none" stroke="#e5e7eb" stroke-width="4"/>'+
                '<circle cx="30" cy="30" r="26" fill="none" stroke="#7c3aed" stroke-width="4" stroke-dasharray="163.36" stroke-dashoffset="'+((100-pct)/100*163.36)+'" transform="rotate(-90 30 30)"/>'+
                '<text x="30" y="30" text-anchor="middle" dominant-baseline="central" font-size="10" font-weight="bold" fill="#1e293b">'+pct+'%</text></svg></div>'+
                '<div><div class="fw-bold" style="font-size:1.1rem">'+clearedCount+' / '+totalReq+'</div><div class="small text-muted">items cleared</div></div>'+
                '</div><div class="progress" style="height:4px"><div class="progress-bar bg-purple" style="width:'+pct+'%"></div></div>'+
                '</div>'+
                '</div>'+
                '<div class="col-md-8">'+
                '<ul class="nav nav-tabs mb-3" id="sTabs">'+
                '<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#sPers">Personal</a></li>'+
                '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sAcad">Academic</a></li>'+
                '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sReq">Requirements</a></li>'+
                '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sDoc">Documents</a></li>'+
                '</ul>'+
                '<div class="tab-content">'+
                '<div class="tab-pane fade show active" id="sPers">'+
                '<div class="profile-section"><h6>Personal Information</h6>'+
                '<div class="profile-info-row"><div class="profile-info-label">Full Name</div><div class="profile-info-value">'+(info.full_name||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Reg Number</div><div class="profile-info-value">'+(info.registration_number||info.student_number||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">National ID</div><div class="profile-info-value">'+(info.national_student_id_number||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Gender</div><div class="profile-info-value">'+(info.gender||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Date of Birth</div><div class="profile-info-value">'+(info.date_of_birth||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Phone</div><div class="profile-info-value">'+(info.phone||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Email</div><div class="profile-info-value">'+(info.email||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Address</div><div class="profile-info-value">'+(info.address||'-')+'</div></div>'+
                '<h6 class="mt-3">Guardian</h6>'+
                '<div class="profile-info-row"><div class="profile-info-label">Guardian Name</div><div class="profile-info-value">'+(info.guardian_name||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Guardian Phone</div><div class="profile-info-value">'+(info.guardian_phone||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Emergency</div><div class="profile-info-value">'+(info.emergency_contact_name||'-')+' / '+(info.emergency_contact_phone||'-')+'</div></div>'+
                '</div></div>'+
                '<div class="tab-pane fade" id="sAcad">'+
                '<div class="profile-section"><h6>Academic Information</h6>'+
                '<div class="profile-info-row"><div class="profile-info-label">Program</div><div class="profile-info-value">'+(info.course||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Current Year</div><div class="profile-info-value">'+(info.current_year||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Intake Date</div><div class="profile-info-value">'+(info.intake_date||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Set</div><div class="profile-info-value">'+(info.set_name||'-')+'</div></div>'+
                '<div class="profile-info-row"><div class="profile-info-label">Status</div><div class="profile-info-value"><span class="badge bg-success">'+(info.status||'-')+'</span></div></div>'+
                (adm.length ? '<h6 class="mt-3">Admission Records</h6>'+adm.map(function(a){ return '<div class="profile-info-row"><div class="profile-info-label">'+(a.admission_number||'Admission')+'</div><div class="profile-info-value">'+(a.program||'-')+' | '+(a.academic_year||'-')+' | <span class="badge bg-'+(a.admission_status==='Approved'?'success':'warning')+'">'+(a.admission_status||'-')+'</span></div></div>'; }).join('') : '')+
                '</div></div>'+
                '<div class="tab-pane fade" id="sReq">'+
                '<div class="profile-section"><h6>Requirements Clearance</h6>';
            if (totalReq === 0) {
                h += '<p class="text-muted small">No requirement items configured.</p>';
            } else {
                h += '<div class="row g-2">';
                reqData.forEach(function(r){
                    var cleared = r.cleared == 1;
                    var notes = r.notes || '';
                    h += '<div class="col-md-6" id="profileReq-'+r.item_id+'">'+
                        '<div class="req-item-card'+(cleared?' cleared':'')+'" style="padding:10px 12px">'+
                        '<span style="font-size:1rem">'+(cleared?'<i class="fas fa-check-circle" style="color:#059669"></i>':'<i class="far fa-circle" style="color:#9ca3af"></i>')+'</span>'+
                        '<div style="flex:1"><div style="font-size:0.82rem;font-weight:500">'+(r.item_name||'')+'</div>'+
                        (notes ? '<div style="font-size:0.7rem;color:#64748b"><i class="fas fa-comment me-1"></i>'+htmlEsc(notes)+'</div>' : '')+'</div>'+
                        '<div>'+ (cleared?'<span class="badge bg-success">Cleared</span>':'<span class="badge bg-warning text-dark">Pending</span>') + '</div>'+
                        '</div></div>';
                });
                h += '</div>';
            }
            h += '</div></div>'+
                '<div class="tab-pane fade" id="sDoc">'+
                '<div class="profile-section"><h6>Documents</h6>';
            if (docs.length) {
                h += '<div class="list-group list-group-flush">';
                docs.forEach(function(d){
                    h += '<div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2" style="border:0;border-bottom:1px solid #f1f5f9">'+
                        '<div>'+
                        (d.file_path ? '<a href="../'+d.file_path+'" target="_blank" style="font-size:0.85rem;font-weight:500;color:#7c3aed">'+(d.document_title||'Document')+'</a>' : '<span style="font-size:0.85rem;font-weight:500">'+(d.document_title||'Document')+'</span>')+
                        '<br><small class="text-muted">'+(d.document_type||'')+' | '+(d.generation_date||'')+'</small></div>'+
                        '<form method="POST" class="d-inline" onsubmit="return confirm(\'Delete document?\')"><input type="hidden" name="action" value="delete_doc"><input type="hidden" name="document_id" value="'+d.id+'"><button class="btn btn-sm btn-outline-danger py-0"><i class="fas fa-trash"></i></button></form>'+
                        '</div>';
                });
                h += '</div>';
            } else {
                h += '<p class="text-muted small">No documents uploaded.</p>';
            }
            h += '<button class="btn btn-sm btn-outline-primary mt-2" onclick="uploadDoc('+id+',\''+htmlEsc(info.full_name||'')+'\')"><i class="fas fa-upload me-1"></i>Upload Document</button>'+
                '</div></div>'+
                '</div></div></div></div>';

            document.getElementById('studentProfileBody').innerHTML = h;
            setTimeout(function(){
                document.querySelectorAll('#sTabs a').forEach(function(t){
                    t.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (typeof bootstrap !== 'undefined') {
                            new bootstrap.Tab(t).show();
                        }
                    });
                });
            }, 50);
        })
        .catch(function(e){
            console.error(e);
            document.getElementById('studentProfileBody').innerHTML = '<div class="alert alert-danger text-center m-3 py-4">Failed to load profile. Please try again.</div>';
        });
}

// ===== DOCUMENT UPLOAD =====
function uploadDoc(id, name){
    document.getElementById('uploadDocStudentId').value = id;
    new bootstrap.Modal(document.getElementById('uploadDocModal')).show();
}

// ===== UTILITY =====
function htmlEsc(s){
    if (typeof s !== 'string') return String(s || '');
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
