<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
$ctx = bootstrapStaffDashboard(['registrar']);
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$students_conn = getStudentsConnection();
$staff_conn    = getStaffConnection();
$website_conn  = $ctx['website'];

// ── AJAX endpoints (exit before HTML) ─────────────────────────────
$ajaxAction = $_GET['ajax'] ?? '';
$ajaxSid    = intval($_GET['student_id'] ?? 0);
// Helper (must be before report/AJAX handlers)
function safeCount($conn, $sql) {
    $r = $conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return intval($row['c'] ?? 0);
}

// ── Report generation (exit before HTML) ──────────────────────────
$reportType = $_GET['report'] ?? '';
if ($reportType) {
    require_once __DIR__ . '/../config/database.php';
    $students_conn = getStudentsConnection(); $staff_conn = getStaffConnection();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#f3f4f6}h2{color:#1f2937}.text-right{text-align:right}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print"><button onclick="window.print()" style="padding:6px 16px;margin-bottom:12px">Print</button> <button onclick="window.close()" style="padding:6px 16px">Close</button></div>';
    if ($reportType === 'student_list') {
        $r = $students_conn->query("SELECT student_number,registration_number,full_name,course,current_year,gender,status,phone,email FROM students ORDER BY surname,first_name");
        echo '<h2>Student List</h2><table><thead><tr><th>#</th><th>Reg No</th><th>Full Name</th><th>Program</th><th>Year</th><th>Gender</th><th>Status</th><th>Phone</th></tr></thead><tbody>';
        $i=1; if ($r) while ($row=$r->fetch_assoc()) { echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['current_year'].'</td><td>'.htmlspecialchars($row['gender']).'</td><td>'.htmlspecialchars($row['status']).'</td><td>'.htmlspecialchars($row['phone']).'</td></tr>'; }
        echo '</tbody></table><p style="margin-top:8px"><em>Total: '.($i-1).' students</em></p>';
    } elseif ($reportType === 'by_program') {
        $r = $students_conn->query("SELECT course,COUNT(*) c FROM students WHERE status='Active' GROUP BY course ORDER BY c DESC");
        echo '<h2>Students by Program</h2><table><thead><tr><th>Program</th><th>Count</th></tr></thead><tbody>';
        $total=0; if ($r) while ($row=$r->fetch_assoc()) { $total+=$row['c']; echo '<tr><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['c'].'</td></tr>'; }
        echo '<tr><th>Total</th><th>'.$total.'</th></tr></tbody></table>';
    } elseif ($reportType === 'fee_summary') {
        $r = $students_conn->query("SELECT s.id,s.full_name,s.student_number,s.registration_number,s.course,COALESCE((SELECT SUM(total_amount) FROM student_invoices WHERE student_id=s.id),0) total_inv,COALESCE((SELECT SUM(amount_received) FROM payments WHERE student_id=s.id),0) total_paid FROM students s WHERE s.status='Active' ORDER BY s.full_name");
        echo '<h2>Fee Summary</h2><table><thead><tr><th>#</th><th>Name</th><th>Reg No</th><th>Program</th><th>Invoiced</th><th>Paid</th><th>Balance</th></tr></thead><tbody>';
        $i=1; if ($r) while ($row=$r->fetch_assoc()) { $bal=$row['total_inv']-$row['total_paid']; echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.htmlspecialchars($row['course']).'</td><td class="text-right">'.number_format($row['total_inv'],0).'</td><td class="text-right">'.number_format($row['total_paid'],0).'</td><td class="text-right">'.number_format($bal,0).'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($reportType === 'academic') {
        echo '<h2>Academic Report</h2><table><thead><tr><th>Student</th><th>Reg No</th><th>Courses Registered</th><th>Avg Marks</th><th>Attendance %</th></tr></thead><tbody>';
        $r = $students_conn->query("SELECT id,full_name,student_number,registration_number,course FROM students WHERE status='Active' ORDER BY full_name LIMIT 100");
        if ($r) while ($row=$r->fetch_assoc()) {
            $sid=$row['id'];
            $cr = safeCount($staff_conn,"SELECT COUNT(*) c FROM course_registrations WHERE student_id=$sid AND status='Registered'");
            $am = $staff_conn->query("SELECT AVG(marks_obtained) m FROM examination_records WHERE student_id=$sid");
            $avg = ($am && $amr=$am->fetch_assoc()) ? round($amr['m'],1) : '-';
            $at = $students_conn->query("SELECT COUNT(*) t,SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) p FROM student_attendance WHERE student_id=$sid");
            $apc = '-'; if ($at && $atr=$at->fetch_assoc() && $atr['t']>0) $apc = round(($atr['p']/$atr['t'])*100,1).'%';
            echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.$cr.'</td><td>'.$avg.'</td><td>'.$apc.'</td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</body></html>'; exit;
}

if ($ajaxAction && $ajaxSid > 0) {
    header('Content-Type: application/json');
    if ($ajaxAction === 'get_financial') {
        $invoices = []; $payments = [];
        $r = $students_conn->query("SELECT id,invoice_number,fee_type,total_amount,discount_amount,amount_paid,balance,status,due_date,issue_date FROM student_invoices WHERE student_id=$ajaxSid ORDER BY issue_date DESC");
        if ($r) while ($row = $r->fetch_assoc()) $invoices[] = $row;
        $r = $students_conn->query("SELECT id,payment_reference,amount_received,payment_method,payment_date,status FROM payments WHERE student_id=$ajaxSid ORDER BY payment_date DESC");
        if ($r) while ($row = $r->fetch_assoc()) $payments[] = $row;
        $totalInv = array_sum(array_column($invoices,'total_amount'));
        $totalPaid = array_sum(array_column($payments,'amount_received'));
        echo json_encode(['invoices'=>$invoices,'payments'=>$payments,'total_invoiced'=>$totalInv,'total_paid'=>$totalPaid,'balance'=>$totalInv - $totalPaid]);
        exit;
    }
    if ($ajaxAction === 'get_results') {
        $data = [];
        $r = $staff_conn->query("SELECT er.exam_number,er.exam_type,er.course_code,er.marks_obtained,er.total_marks,er.grade,er.continuous_assessment_marks,er.final_exam_marks,er.grade_status,er.created_at FROM examination_records er WHERE er.student_id=$ajaxSid ORDER BY er.created_at DESC");
        if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }
    if ($ajaxAction === 'get_attendance') {
        $data = [];
        $r = $students_conn->query("SELECT id,date,subject,course_code,status,remarks FROM student_attendance WHERE student_id=$ajaxSid ORDER BY date DESC LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }
    if ($ajaxAction === 'get_courses') {
        $data = [];
        $r = $students_conn->query("SELECT scr.id,scr.course_id,scr.academic_year,scr.semester,scr.status,scr.registration_date,cc.course_code,cc.course_name FROM student_course_registrations scr LEFT JOIN igangaschoolofl_staffs_db.academic_course_catalog cc ON scr.course_id=cc.id WHERE scr.student_id=$ajaxSid ORDER BY scr.registration_date DESC");
        if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }
    if ($ajaxAction === 'get_documents') {
        $data = [];
        $r = $staff_conn->query("SELECT id,document_type,document_title,file_path,generation_date FROM generated_documents WHERE student_id=$ajaxSid ORDER BY generation_date DESC");
        if ($r) while ($row = $r->fetch_assoc()) $data[] = $row;
        echo json_encode($data);
        exit;
    }
    echo json_encode([]);
    exit;
}

// Stats
$total_students    = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status='Active'");
$new_admissions    = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)");
$pending_approvals = safeCount($staff_conn,    "SELECT COUNT(*) c FROM grading_approval_workflow WHERE current_stage IN('HOD Review','Registrar Approval','Principal Final Approval')");
$exam_pending      = safeCount($staff_conn,    "SELECT COUNT(*) c FROM examination_records WHERE grade IS NULL OR grade=''");
$course_regs       = safeCount($staff_conn,    "SELECT COUNT(*) c FROM course_registrations WHERE status='Registered'");
$grad_candidates   = safeCount($students_conn, "SELECT COUNT(*) c FROM students WHERE status IN('Graduated','graduation_candidate')");
$notifications     = safeCount($students_conn, "SELECT COUNT(*) c FROM student_notifications WHERE is_read=0");
$cal_reminders     = safeCount($staff_conn,    "SELECT COUNT(*) c FROM academic_calendar WHERE semester_start_date BETWEEN NOW() AND DATE_ADD(NOW(),INTERVAL 30 DAY)");
$trash_count       = safeCount($students_conn, "SELECT COUNT(*) c FROM students_trash");

// Search
$search = trim($_GET['q'] ?? '');
$filter_program = $_GET['program'] ?? '';
$filter_status  = $_GET['status'] ?? '';
$filter_year    = $_GET['year'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = ["1=1"];
$params = []; $types = '';
if ($search) {
    $where[] = "(first_name LIKE ? OR surname LIKE ? OR student_number LIKE ? OR registration_number LIKE ? OR national_student_id_number LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s,$s]);
    $types .= 'sssss';
}
if ($filter_program) { $where[] = "course=?"; $params[] = $filter_program; $types .= 's'; }
if ($filter_status)  { $where[] = "status=?";  $params[] = $filter_status;  $types .= 's'; }
if ($filter_year)    { $where[] = "current_year=?"; $params[] = $filter_year; $types .= 'i'; }

$sql_where = implode(' AND ', $where);
$total_found = 0;
$students = [];

$cnt_sql = "SELECT COUNT(*) c FROM students WHERE $sql_where";
$cnt_stmt = $students_conn->prepare($cnt_sql);
if ($types) $cnt_stmt->bind_param($types, ...$params);
$cnt_stmt->execute();
$total_found = $cnt_stmt->get_result()->fetch_assoc()['c'];
$cnt_stmt->close();

$data_sql = "SELECT id,student_number,registration_number,national_student_id_number,first_name,surname,other_name,full_name,course,current_year,current_semester,set_name,gender,status,phone,email,intake_date,created_at FROM students WHERE $sql_where ORDER BY surname,first_name LIMIT $per_page OFFSET $offset";
$d_stmt = $students_conn->prepare($data_sql);
if ($types) $d_stmt->bind_param($types, ...$params);
$d_stmt->execute();
$res = $d_stmt->get_result();
while ($row = $res->fetch_assoc()) $students[] = $row;
$d_stmt->close();
$total_pages = max(1, ceil($total_found / $per_page));

// Calendar
$calendars = [];
$cal_r = $staff_conn->query("SELECT * FROM academic_calendar ORDER BY created_at DESC LIMIT 5");
if ($cal_r) while ($row = $cal_r->fetch_assoc()) $calendars[] = $row;

// Trash
$trash = [];
$tr_r = $students_conn->query("SELECT * FROM students_trash ORDER BY deleted_at DESC LIMIT 20");
if ($tr_r) while ($row = $tr_r->fetch_assoc()) $trash[] = $row;

// Course catalog for registration
$courses_catalog = [];
$cc_r = $staff_conn->query("SELECT id,course_code,course_name FROM academic_course_catalog ORDER BY course_name");
if ($cc_r) while ($row = $cc_r->fetch_assoc()) $courses_catalog[] = $row;

// Fee types
$fee_types = ['Tuition','Functional Fee','Accommodation','Library','Lab','Examination','Uniform','Activity Fee','Other'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_student') {
        $fn  = $students_conn->real_escape_string(trim($_POST['first_name']  ?? ''));
        $sn  = $students_conn->real_escape_string(trim($_POST['surname']     ?? ''));
        $on  = $students_conn->real_escape_string(trim($_POST['other_name']  ?? ''));
        $dob = $students_conn->real_escape_string(trim($_POST['dob']         ?? ''));
        $gen = $students_conn->real_escape_string(trim($_POST['gender']      ?? 'Other'));
        $crs = $students_conn->real_escape_string(trim($_POST['course']      ?? ''));
        $yr  = intval($_POST['year'] ?? 1);
        $sem = $students_conn->real_escape_string(trim($_POST['semester']    ?? ''));
        $ph  = $students_conn->real_escape_string(trim($_POST['phone']       ?? ''));
        $em  = $students_conn->real_escape_string(trim($_POST['email']       ?? ''));
        $nat = $students_conn->real_escape_string(trim($_POST['nationality'] ?? 'Ugandan'));
        $snum = 'STU'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
        $full = trim("$fn $on $sn");
        $students_conn->query("INSERT INTO students (student_number,first_name,surname,other_name,full_name,date_of_birth,gender,course,program,current_year,year,current_semester,phone,mobile_number,email,nationality,status,created_at) VALUES ('$snum','$fn','$sn','$on','$full','$dob','$gen','$crs','$crs',$yr,$yr,'$sem','$ph','$ph','$em','$nat','Active',NOW())");
        if ($students_conn->affected_rows > 0) {
            $students_conn->query("INSERT INTO academic_registrar_activity_log (activity,created_by,created_at) VALUES ('Added new student: $full',{$_SESSION['user_id']},NOW())");
            $_SESSION['success'] = "Student $full added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add student: ".$students_conn->error;
        }
        header("Location: academic-registrar.php"); exit;
    }

    if ($action === 'edit_student') {
        $id  = intval($_POST['id']);
        $fn  = $students_conn->real_escape_string(trim($_POST['first_name']  ?? ''));
        $sn  = $students_conn->real_escape_string(trim($_POST['surname']     ?? ''));
        $on  = $students_conn->real_escape_string(trim($_POST['other_name']  ?? ''));
        $crs = $students_conn->real_escape_string(trim($_POST['course']      ?? ''));
        $yr  = intval($_POST['year'] ?? 1);
        $sem = $students_conn->real_escape_string(trim($_POST['semester']    ?? ''));
        $ph  = $students_conn->real_escape_string(trim($_POST['phone']       ?? ''));
        $em  = $students_conn->real_escape_string(trim($_POST['email']       ?? ''));
        $st  = $students_conn->real_escape_string(trim($_POST['status']      ?? 'Active'));
        $full = trim("$fn $on $sn");
        $students_conn->query("UPDATE students SET first_name='$fn',surname='$sn',other_name='$on',full_name='$full',course='$crs',program='$crs',current_year=$yr,year=$yr,current_semester='$sem',phone='$ph',mobile_number='$ph',email='$em',status='$st',updated_at=NOW() WHERE id=$id");
        $_SESSION['success'] = "Student updated.";
        header("Location: academic-registrar.php"); exit;
    }

    if ($action === 'trash_student') {
        $id = intval($_POST['id']);
        $row_r = $students_conn->query("SELECT * FROM students WHERE id=$id");
        if ($row_r && $orig = $row_r->fetch_assoc()) {
            $snap = $students_conn->real_escape_string(json_encode($orig));
            $snum = $students_conn->real_escape_string($orig['student_number']);
            $fname = $students_conn->real_escape_string($orig['full_name'] ?? $orig['first_name'].' '.$orig['surname']);
            $em = $students_conn->real_escape_string($orig['email']);
            $crs = $students_conn->real_escape_string($orig['course']);
            $students_conn->query("INSERT INTO students_trash (original_id,student_number,full_name,email,course,snapshot_data,deleted_by,deleted_at) VALUES ($id,'$snum','$fname','$em','$crs','$snap',{$_SESSION['user_id']},NOW())");
            $students_conn->query("UPDATE students SET status='deleted' WHERE id=$id");
            $students_conn->query("INSERT INTO academic_registrar_activity_log (activity,created_by,created_at) VALUES ('Moved to trash: $fname',{$_SESSION['user_id']},NOW())");
            $_SESSION['success'] = "Student moved to trash.";
        }
        header("Location: academic-registrar.php#trash"); exit;
    }

    if ($action === 'restore_student') {
        $tid = intval($_POST['trash_id']);
        $tr = $students_conn->query("SELECT * FROM students_trash WHERE id=$tid");
        if ($tr && $t = $tr->fetch_assoc()) {
            $oid = $t['original_id'];
            $students_conn->query("UPDATE students SET status='Active',updated_at=NOW() WHERE id=$oid");
            $students_conn->query("UPDATE students_trash SET restored_at=NOW() WHERE id=$tid");
            $students_conn->query("DELETE FROM students_trash WHERE id=$tid");
            $_SESSION['success'] = "Student restored.";
        }
        header("Location: academic-registrar.php#trash"); exit;
    }

    if ($action === 'delete_permanent') {
        $tid = intval($_POST['trash_id']);
        $tr = $students_conn->query("SELECT original_id,full_name FROM students_trash WHERE id=$tid");
        if ($tr && $t = $tr->fetch_assoc()) {
            $oid = $t['original_id'];
            $nm = $students_conn->real_escape_string($t['full_name']);
            $students_conn->query("DELETE FROM students WHERE id=$oid");
            $students_conn->query("DELETE FROM students_trash WHERE id=$tid");
            $students_conn->query("INSERT INTO academic_registrar_activity_log (activity,created_by,created_at) VALUES ('Permanently deleted: $nm',{$_SESSION['user_id']},NOW())");
            $_SESSION['success'] = "Student permanently deleted.";
        }
        header("Location: academic-registrar.php#trash"); exit;
    }

    if ($action === 'record_payment') {
        $sid = intval($_POST['student_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $method = $students_conn->real_escape_string($_POST['payment_method'] ?? 'Cash');
        $ref = $students_conn->real_escape_string($_POST['reference'] ?? '');
        $notes = $students_conn->real_escape_string($_POST['notes'] ?? '');
        $pdate = $students_conn->real_escape_string($_POST['payment_date'] ?? date('Y-m-d'));
        if ($sid > 0 && $amount > 0) {
            $pref = 'PAY-'.date('Ymd').'-'.mt_rand(10000,99999);
            $stmt = $students_conn->prepare("INSERT INTO payments (payment_reference,student_id,amount_received,payment_method,payment_date,status,notes) VALUES (?,?,?,?,?,'Completed',?)");
            $stmt->bind_param("sidsss", $pref, $sid, $amount, $method, $pdate, $notes);
            if ($stmt->execute()) {
                $pid = $stmt->insert_id;
                $stmt->close();
                $rno = 'RCT-'.date('Ymd').'-'.str_pad($pid,4,'0',STR_PAD_LEFT);
                $students_conn->query("INSERT INTO payment_receipts (receipt_number,payment_id,student_id,amount,payment_method,issued_by) VALUES ('$rno',$pid,$sid,$amount,'$method',{$_SESSION['user_id']})");
                $students_conn->query("UPDATE student_invoices SET amount_paid=amount_paid+$amount,status=CASE WHEN (balance-$amount)<=0 THEN 'Paid' WHEN amount_paid+$amount>0 THEN 'Partially Paid' ELSE status END WHERE student_id=$sid AND status IN('Pending','Partially Paid','Overdue')");
                $_SESSION['success'] = "Payment recorded. Receipt: $rno";
            } else {
                $_SESSION['error'] = 'Payment failed: '.$stmt->error;
                $stmt->close();
            }
        } else { $_SESSION['error'] = 'Valid student and amount required.'; }
        header("Location: academic-registrar.php"); exit;
    }

    if ($action === 'create_invoice') {
        $sid = intval($_POST['student_id'] ?? 0);
        $fee_type = $students_conn->real_escape_string($_POST['fee_type'] ?? 'Tuition');
        $amount = floatval($_POST['total_amount'] ?? 0);
        $due_date = $students_conn->real_escape_string($_POST['due_date'] ?? '');
        if ($sid > 0 && $amount > 0) {
            $invNo = 'INV-'.date('Y').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $stmt = $students_conn->prepare("INSERT INTO student_invoices (invoice_number,student_id,fee_type,total_amount,amount_paid,due_date,status,created_by) VALUES (?,?,?,?,0,?,{$_SESSION['user_id']})");
            $stmt->bind_param("sisds", $invNo, $sid, $fee_type, $amount, $due_date);
            if ($stmt->execute()) { $_SESSION['success'] = "Invoice $invNo created."; } else { $_SESSION['error'] = 'Invoice failed: '.$stmt->error; }
            $stmt->close();
        } else { $_SESSION['error'] = 'Student and amount required.'; }
        header("Location: academic-registrar.php"); exit;
    }

    if ($action === 'upload_document') {
        $sid = intval($_POST['student_id'] ?? 0);
        $docType = $staff_conn->real_escape_string($_POST['document_type'] ?? 'Other');
        $title = $staff_conn->real_escape_string($_POST['document_title'] ?? '');
        if ($sid > 0 && $title && isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../uploads/student_docs/' . $sid;
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            $fname = time() . '_' . preg_replace('/[^a-z0-9]/i', '_', $title) . '.' . $ext;
            $dest = $dir . '/' . $fname;
            if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $dest)) {
                $fpath = "uploads/student_docs/$sid/$fname";
                $stmt = $staff_conn->prepare("INSERT INTO generated_documents (document_type,student_id,generated_by,document_title,file_path) VALUES (?,?,{$_SESSION['user_id']},?,?)");
                $stmt->bind_param("siss", $docType, $sid, $title, $fpath);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success'] = "Document '$title' uploaded.";
            } else { $_SESSION['error'] = 'Upload failed.'; }
        } else { $_SESSION['error'] = 'Title and file required.'; }
        header("Location: academic-registrar.php"); exit;
    }

    if ($action === 'register_course') {
        $sid = intval($_POST['student_id'] ?? 0);
        $courseId = intval($_POST['course_id'] ?? 0);
        $ay = $staff_conn->real_escape_string($_POST['academic_year'] ?? date('Y'));
        $sem = $staff_conn->real_escape_string($_POST['semester'] ?? 'Semester 1');
        if ($sid > 0 && $courseId > 0) {
            $students_conn->query("INSERT IGNORE INTO student_course_registrations (student_id,course_id,academic_year,semester,status) VALUES ($sid,$courseId,'$ay','$sem','Registered')");
            $_SESSION['success'] = 'Course registered.';
        } else { $_SESSION['error'] = 'Student and course required.'; }
        header("Location: academic-registrar.php"); exit;
    }

    if ($action === 'add_calendar') {
        $ay  = $staff_conn->real_escape_string($_POST['academic_year'] ?? date('Y').'-'.date('Y',strtotime('+1 year')));
        $sem = $staff_conn->real_escape_string($_POST['semester'] ?? '');
        $ss  = $_POST['semester_start'] ?? date('Y-m-d');
        $se  = $_POST['semester_end']   ?? date('Y-m-d',strtotime('+4 months'));
        $es  = $_POST['exam_start']     ?? date('Y-m-d',strtotime('+3 months'));
        $ee  = $_POST['exam_end']       ?? date('Y-m-d',strtotime('+4 months'));
        $rd  = $_POST['result_date']    ?? '';
        $rg  = $_POST['reg_deadline']   ?? date('Y-m-d');
        $cid = 'CAL-'.$ay.'-'.substr($sem,0,2).'-'.mt_rand(100,999);
        $staff_conn->query("INSERT INTO academic_calendar (calendar_id,academic_year,semester,semester_start_date,semester_end_date,exam_start_date,exam_end_date,result_publication_date,registration_deadline,status,created_by,created_at) VALUES ('$cid','$ay','$sem','$ss','$se','$es','$ee','$rd','$rg','Upcoming',{$_SESSION['user_id']},NOW())");
        $_SESSION['success'] = "Academic calendar entry added.";
        header("Location: academic-registrar.php#calendar"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.btn-outline-purple { color:#8b5cf6; border-color:#8b5cf6; }
.btn-outline-purple:hover { color:#fff; background:#8b5cf6; border-color:#8b5cf6; }
.btn-tbl { padding:2px 6px; font-size:12px; }
.tab-pane { font-size:13px; }
</style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main content-section dashboard-section active" style="margin-left:270px" data-section="overview">
  <!-- Topbar -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <button class="btn btn-sm btn-outline-secondary d-md-none me-2" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
      <h4 class="d-inline fw-bold" style="color:var(--primary)">Academic Registrar Dashboard</h4>
    </div>
    <small class="text-muted"><?= date('l, d M Y') ?></small>
  </div>

  <?php if(!empty($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php unset($_SESSION['success']); endif; ?>
  <?php if(!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php unset($_SESSION['error']); endif; ?>

  <!-- OVERVIEW STATS -->
  <section id="overview">
    <div class="row g-3 mb-4">
      <?php $cards=[
        ['Total Students',     $total_students,    'users',          'var(--primary)'],
        ['New Admissions(30d)',$new_admissions,    'user-check',     '#10b981'],
        ['Pending Approvals',  $pending_approvals, 'hourglass-half', '#f59e0b'],
        ['Exam Results Pending',$exam_pending,     'pen-nib',        '#ef4444'],
        ['Course Registrations',$course_regs,      'book-open',      '#8b5cf6'],
        ['Graduation Candidates',$grad_candidates, 'graduation-cap', '#3b82f6'],
        ['Notifications',      $notifications,     'bell',           '#ec4899'],
        ['Calendar Reminders', $cal_reminders,     'calendar-alt',   '#f97316'],
      ];
      foreach($cards as $c): ?>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="num" style="color:<?=$c[3]?>"><?=$c[1]?></div>
              <div class="lbl"><?=$c[0]?></div>
            </div>
            <i class="fas fa-<?=$c[2]?> fa-lg mt-1" style="color:<?=$c[3]?>;opacity:.6"></i>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- STUDENT MANAGEMENT -->
  <section id="students" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-users me-2"></i>Student Records</h5>
      <div class="d-flex gap-2">
        <a href="../import_students_excel.php" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i>Import from Excel</a>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-plus me-1"></i>Register Student</button>
      </div>
    </div>

    <!-- Search & Filter -->
    <form method="GET" class="row g-2 mb-3">
      <div class="col-md-4"><input type="text" name="q" class="form-control form-control-sm" placeholder="Search name, student no, national ID…" value="<?= htmlspecialchars($search) ?>"></div>
      <div class="col-md-2">
        <select name="program" class="form-select form-select-sm">
          <option value="">All Programs</option>
          <?php foreach(['Certificate Nursing','Certificate Midwifery','Diploma Nursing','Diploma Midwifery'] as $p): ?>
          <option <?= $filter_program===$p?'selected':'' ?> value="<?= htmlspecialchars($p) ?>"><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <?php foreach(['Active','Inactive','Graduated','Suspended','Withdrawn'] as $s): ?>
          <option <?= $filter_status===$s?'selected':'' ?> value="<?= $s ?>"><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <select name="year" class="form-select form-select-sm">
          <option value="">Year</option>
          <?php for($y=1;$y<=3;$y++): ?>
          <option <?= $filter_year==$y?'selected':'' ?> value="<?= $y ?>">Year <?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button></div>
      <div class="col-md-1"><a href="academic-registrar.php" class="btn btn-sm btn-outline-secondary w-100">Clear</a></div>
    </form>

    <p class="text-muted small mb-2">Showing <?= count($students) ?> of <?= $total_found ?> students</p>

    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Reg No.</th><th>National ID</th><th>Full Name</th><th>Program</th><th>Year</th><th>Set</th><th>Gender</th><th>Phone</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if(empty($students)): ?>
          <tr><td colspan="11" class="text-center text-muted py-4">No students found</td></tr>
        <?php else: foreach($students as $i=>$s):
          $fullname = htmlspecialchars($s['full_name'] ?: trim($s['first_name'].' '.($s['other_name']??'').' '.$s['surname']));
          $badges = ['Active'=>'badge-active','Inactive'=>'badge-inactive','Graduated'=>'badge-graduated','Suspended'=>'badge-suspended'];
          $bc = $badges[$s['status']] ?? 'badge-deleted';
        ?>
          <tr>
            <td><?= $offset+$i+1 ?></td>
            <td><code><?= htmlspecialchars($s['registration_number'] ?: $s['student_number']) ?></code></td>
            <td><code><?= htmlspecialchars($s['national_student_id_number'] ?? '-') ?></code></td>
            <td><strong><?= $fullname ?></strong></td>
            <td><?= htmlspecialchars($s['course'] ?? '-') ?></td>
            <td><?= $s['current_year'] ?? '-' ?></td>
            <td><?= htmlspecialchars($s['set_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($s['gender'] ?? '-') ?></td>
            <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($s['status']) ?></span></td>
            <td>
              <div class="d-flex gap-1 flex-nowrap">
                <button class="btn btn-sm btn-outline-primary btn-tbl" title="Edit" onclick="editStudent(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-outline-info btn-tbl" title="View" onclick="viewStudent(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="fas fa-eye"></i></button>
                <button class="btn btn-sm btn-outline-secondary btn-tbl" title="Print" onclick="printStudent(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="fas fa-print"></i></button>
                <button class="btn btn-sm btn-outline-warning btn-tbl" title="Fee" onclick="studentFeeModal(<?= $s['id'] ?>,'<?= addslashes($fullname) ?>')"><i class="fas fa-money-bill"></i></button>
                <button class="btn btn-sm btn-outline-success btn-tbl" title="Payment" onclick="studentPaymentModal(<?= $s['id'] ?>,'<?= addslashes($fullname) ?>')"><i class="fas fa-credit-card"></i></button>
                <button class="btn btn-sm btn-outline-info btn-tbl" title="Results" onclick="studentResultsModal(<?= $s['id'] ?>,'<?= addslashes($fullname) ?>')"><i class="fas fa-chart-bar"></i></button>
                <button class="btn btn-sm btn-outline-purple btn-tbl" title="Courses" onclick="studentCoursesModal(<?= $s['id'] ?>,'<?= addslashes($fullname) ?>')"><i class="fas fa-book"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-tbl" title="Trash" onclick="trashStudent(<?= $s['id'] ?>, '<?= addslashes($fullname) ?>')"><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <nav><ul class="pagination pagination-sm justify-content-center mb-0">
      <?php for($p=1;$p<=$total_pages;$p++): ?>
      <li class="page-item <?= $p==$page?'active':'' ?>">
        <a class="page-link" href="?q=<?= urlencode($search) ?>&program=<?= urlencode($filter_program) ?>&status=<?= urlencode($filter_status) ?>&year=<?= urlencode($filter_year) ?>&page=<?= $p ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
  </section>

  <!-- ACADEMIC CALENDAR -->
  <section id="calendar" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#calendarModal"><i class="fas fa-plus me-1"></i>Add Entry</button>
    </div>
    <?php if(empty($calendars)): ?>
    <p class="text-muted small">No calendar entries yet.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead class="table-light"><tr><th>ID</th><th>Year</th><th>Semester</th><th>Starts</th><th>Ends</th><th>Exams</th><th>Results</th><th>Reg Deadline</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($calendars as $c): ?>
        <tr>
          <td><code><?= htmlspecialchars($c['calendar_id']) ?></code></td>
          <td><?= htmlspecialchars($c['academic_year']) ?></td>
          <td><?= htmlspecialchars($c['semester']) ?></td>
          <td><?= $c['semester_start_date'] ?></td>
          <td><?= $c['semester_end_date'] ?></td>
          <td><?= $c['exam_start_date'] ?> – <?= $c['exam_end_date'] ?></td>
          <td><?= $c['result_publication_date'] ?: '-' ?></td>
          <td><?= $c['registration_deadline'] ?: '-' ?></td>
          <td><span class="badge bg-<?= $c['status']==='Current'?'success':($c['status']==='Upcoming'?'info':'secondary') ?>"><?= $c['status'] ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- FEE & FINANCE -->
  <section id="finance" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-money-bill-wave me-2"></i>Fee &amp; Finance</h5>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createInvoiceModal"><i class="fas fa-file-invoice me-1"></i>Create Invoice</button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal"><i class="fas fa-credit-card me-1"></i>Record Payment</button>
      </div>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-md-3">
        <select id="financeStudent" class="form-select form-select-sm" onchange="loadStudentFinance()">
          <option value="">Select student…</option>
          <?php
          $fsr = $students_conn->query("SELECT id,full_name,student_number,registration_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
          if ($fsr) while ($f = $fsr->fetch_assoc()):
            $flbl = htmlspecialchars($f['full_name'] ?: $f['student_number']);
            $freg = htmlspecialchars($f['registration_number'] ?: $f['student_number']);
          ?>
          <option value="<?= $f['id'] ?>"><?= $flbl ?> (<?= $freg ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div id="financeData" class="small text-muted">Select a student to view fee records.</div>
  </section>

  <!-- COURSE REGISTRATION -->
  <section id="courses" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-book-open me-2"></i>Course Registration</h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#registerCourseModal"><i class="fas fa-plus me-1"></i>Register Course</button>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-md-3">
        <select id="courseStudent" class="form-select form-select-sm" onchange="loadStudentCourses()">
          <option value="">Select student…</option>
          <?php
          $csr = $students_conn->query("SELECT id,full_name,student_number,registration_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
          if ($csr) while ($c = $csr->fetch_assoc()):
            $clbl = htmlspecialchars($c['full_name'] ?: $c['student_number']);
            $creg = htmlspecialchars($c['registration_number'] ?: $c['student_number']);
          ?>
          <option value="<?= $c['id'] ?>"><?= $clbl ?> (<?= $creg ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div id="courseData" class="small text-muted">Select a student to view course registrations.</div>
  </section>

  <!-- ATTENDANCE -->
  <section id="attendance" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-calendar-check me-2"></i>Attendance</h5>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-md-3">
        <select id="attStudent" class="form-select form-select-sm" onchange="loadStudentAttendance()">
          <option value="">Select student…</option>
          <?php
          $asr = $students_conn->query("SELECT id,full_name,student_number,registration_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
          if ($asr) while ($a = $asr->fetch_assoc()):
            $albl = htmlspecialchars($a['full_name'] ?: $a['student_number']);
            $areg = htmlspecialchars($a['registration_number'] ?: $a['student_number']);
          ?>
          <option value="<?= $a['id'] ?>"><?= $albl ?> (<?= $areg ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div id="attendanceData" class="small text-muted">Select a student to view attendance records.</div>
  </section>

  <!-- EXAM RESULTS -->
  <section id="results" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-chart-bar me-2"></i>Examination Results</h5>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-md-3">
        <select id="resultStudent" class="form-select form-select-sm" onchange="loadStudentResults()">
          <option value="">Select student…</option>
          <?php
          $rsr = $students_conn->query("SELECT id,full_name,student_number,registration_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
          if ($rsr) while ($r = $rsr->fetch_assoc()):
            $rlbl = htmlspecialchars($r['full_name'] ?: $r['student_number']);
            $rreg = htmlspecialchars($r['registration_number'] ?: $r['student_number']);
          ?>
          <option value="<?= $r['id'] ?>"><?= $rlbl ?> (<?= $rreg ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div id="resultData" class="small text-muted">Select a student to view results.</div>
  </section>

  <!-- DOCUMENT MANAGEMENT -->
  <section id="documents" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-file-alt me-2"></i>Document Management</h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocModal"><i class="fas fa-upload me-1"></i>Upload Document</button>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-md-3">
        <select id="docStudent" class="form-select form-select-sm" onchange="loadStudentDocs()">
          <option value="">Select student…</option>
          <?php
          $dsr = $students_conn->query("SELECT id,full_name,student_number,registration_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
          if ($dsr) while ($d = $dsr->fetch_assoc()):
            $dlbl = htmlspecialchars($d['full_name'] ?: $d['student_number']);
            $dreg = htmlspecialchars($d['registration_number'] ?: $d['student_number']);
          ?>
          <option value="<?= $d['id'] ?>"><?= $dlbl ?> (<?= $dreg ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div id="docData" class="small text-muted">Select a student to view documents.</div>
  </section>

  <!-- REPORTS -->
  <section id="reports" class="section-card">
    <h5><i class="fas fa-file-pdf me-2"></i>Reports</h5>
    <div class="row g-2">
      <?php
      $report_links = [
        ['Student List','users','All active students with full profile.','?report=student_list'],
        ['Program Report','layer-group','Students grouped by program.','?report=by_program'],
        ['Fee Summary','money-bill-wave','Invoice & payment totals per student.','?report=fee_summary'],
        ['Academic Report','chart-bar','Course registrations & results summary.','?report=academic'],
      ];
      foreach($report_links as $rpt): ?>
      <div class="col-md-3">
        <div class="card card-body py-3 text-center" style="cursor:pointer" onclick="window.open('academic-registrar.php<?= $rpt[3] ?>','_blank')">
          <i class="fas fa-<?= $rpt[1] ?> fa-2x mb-2" style="color:var(--primary)"></i>
          <strong class="small"><?= $rpt[0] ?></strong>
          <small class="text-muted"><?= $rpt[2] ?></small>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- TRASH BIN -->
  <section id="trash" class="section-card">
    <h5><i class="fas fa-trash-alt me-2"></i>Trash Bin (<?= $trash_count ?> records)</h5>
    <?php if(empty($trash)): ?>
    <p class="text-muted small">Trash bin is empty.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead class="table-light"><tr><th>Reg No.</th><th>Full Name</th><th>Course</th><th>Deleted</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($trash as $t): ?>
        <tr>
          <td><code><?= htmlspecialchars($t['student_number']) ?></code></td>
          <td><?= htmlspecialchars($t['full_name']) ?></td>
          <td><?= htmlspecialchars($t['course']) ?></td>
          <td><?= $t['deleted_at'] ?></td>
          <td>
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="restore_student">
              <input type="hidden" name="trash_id" value="<?= $t['id'] ?>">
              <button class="btn btn-sm btn-success btn-tbl"><i class="fas fa-undo"></i> Restore</button>
            </form>
            <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete? This cannot be undone.')">
              <input type="hidden" name="action" value="delete_permanent">
              <input type="hidden" name="trash_id" value="<?= $t['id'] ?>">
              <button class="btn btn-sm btn-danger btn-tbl"><i class="fas fa-times"></i> Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- NEWS MANAGEMENT -->
  <section class="section-card">
    <h5><i class="fas fa-newspaper me-2"></i>News &amp; Announcements</h5>
    <?php renderNewsWidget($staff_conn, $website_conn, $user['id'] ?? 0, $user['full_name'] ?? 'Registrar', $user['role'] ?? 'Academic Registrar', 5); ?>
  </section>

  <!-- Student Records -->
  <section id="student-records" class="section-card">
    <?php renderStudentSetViewer($students_conn, [
      'title' => 'Student Records',
      'icon' => 'fa-user-graduate',
      'show_all' => true,
      'per_page' => 50,
      'show_statement_link' => false
    ]); ?>
  </section>

</div><!-- /main -->

<!-- ADD STUDENT MODAL -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add_student">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Register New Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label fw-semibold">First Name *</label><input type="text" name="first_name" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Surname *</label><input type="text" name="surname" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Other Names</label><input type="text" name="other_name" class="form-control"></div>
          <div class="col-md-3"><label class="form-label fw-semibold">Date of Birth</label><input type="date" name="dob" class="form-control"></div>
          <div class="col-md-3"><label class="form-label fw-semibold">Gender</label>
            <select name="gender" class="form-select">
              <option value="Female">Female</option><option value="Male">Male</option><option value="Other">Other</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label fw-semibold">Program/Course *</label>
            <select name="course" class="form-select" required>
              <option value="">Select Program</option>
              <option>Certificate Nursing</option><option>Certificate Midwifery</option><option>Diploma Nursing</option><option>Diploma Midwifery</option>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label fw-semibold">Year of Study</label>
            <select name="year" class="form-select"><option value="1">Year 1</option><option value="2">Year 2</option><option value="3">Year 3</option></select>
          </div>
          <div class="col-md-3"><label class="form-label fw-semibold">Semester</label>
            <select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select>
          </div>
          <div class="col-md-3"><label class="form-label fw-semibold">Phone</label><input type="text" name="phone" class="form-control"></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" name="email" class="form-control"></div>
          <div class="col-md-3"><label class="form-label fw-semibold">Nationality</label><input type="text" name="nationality" class="form-control" value="Ugandan"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Register Student</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT STUDENT MODAL -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit_student">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label fw-semibold">First Name</label><input type="text" name="first_name" id="edit_fn" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Surname</label><input type="text" name="surname" id="edit_sn" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Other Names</label><input type="text" name="other_name" id="edit_on" class="form-control"></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Program</label>
            <select name="course" id="edit_crs" class="form-select">
              <option>Certificate Nursing</option><option>Certificate Midwifery</option><option>Diploma Nursing</option><option>Diploma Midwifery</option>
            </select>
          </div>
          <div class="col-md-2"><label class="form-label fw-semibold">Year</label>
            <select name="year" id="edit_yr" class="form-select"><option value="1">1</option><option value="2">2</option><option value="3">3</option></select>
          </div>
          <div class="col-md-4"><label class="form-label fw-semibold">Semester</label>
            <select name="semester" id="edit_sem" class="form-select"><option>Semester 1</option><option>Semester 2</option></select>
          </div>
          <div class="col-md-4"><label class="form-label fw-semibold">Phone</label><input type="text" name="phone" id="edit_ph" class="form-control"></div>
          <div class="col-md-5"><label class="form-label fw-semibold">Email</label><input type="email" name="email" id="edit_em" class="form-control"></div>
          <div class="col-md-3"><label class="form-label fw-semibold">Status</label>
            <select name="status" id="edit_st" class="form-select">
              <option>Active</option><option>Inactive</option><option>Graduated</option><option>Suspended</option><option>Withdrawn</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- VIEW STUDENT MODAL -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Student Profile</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewStudentBody"></div>
      <div class="modal-footer">
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i>Print</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ADD CALENDAR MODAL -->
<div class="modal fade" id="calendarModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add_calendar">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Add Calendar Entry</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-6"><label class="form-label fw-semibold">Academic Year</label><input type="text" name="academic_year" class="form-control" placeholder="2024-2025" required></div>
          <div class="col-6"><label class="form-label fw-semibold">Semester</label>
            <select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select>
          </div>
          <div class="col-6"><label class="form-label fw-semibold">Semester Start</label><input type="date" name="semester_start" class="form-control" required></div>
          <div class="col-6"><label class="form-label fw-semibold">Semester End</label><input type="date" name="semester_end" class="form-control" required></div>
          <div class="col-6"><label class="form-label fw-semibold">Exam Start</label><input type="date" name="exam_start" class="form-control" required></div>
          <div class="col-6"><label class="form-label fw-semibold">Exam End</label><input type="date" name="exam_end" class="form-control" required></div>
          <div class="col-6"><label class="form-label fw-semibold">Results Date</label><input type="date" name="result_date" class="form-control"></div>
          <div class="col-6"><label class="form-label fw-semibold">Reg. Deadline</label><input type="date" name="reg_deadline" class="form-control" required></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- CREATE INVOICE MODAL -->
<div class="modal fade" id="createInvoiceModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="create_invoice">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i>Create Invoice</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label fw-semibold">Student</label>
          <select name="student_id" class="form-select" required>
            <option value="">Select student…</option>
            <?php
            $isr = $students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
            if ($isr) while ($i = $isr->fetch_assoc()): ?>
            <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['full_name'] ?: $i['student_number']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Fee Type</label>
          <select name="fee_type" class="form-select" required>
            <?php foreach($fee_types as $ft): ?><option><?= $ft ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Amount</label><input type="number" step="0.01" name="total_amount" class="form-control" required></div>
        <div class="mb-2"><label class="form-label fw-semibold">Due Date</label><input type="date" name="due_date" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Create Invoice</button>
      </div>
    </form>
  </div>
</div>

<!-- RECORD PAYMENT MODAL -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="record_payment">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Record Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label fw-semibold">Student</label>
          <select name="student_id" class="form-select" required id="payStudentSelect">
            <option value="">Select student…</option>
            <?php
            $psr = $students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
            if ($psr) while ($p = $psr->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name'] ?: $p['student_number']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
        <div class="mb-2"><label class="form-label fw-semibold">Payment Method</label>
          <select name="payment_method" class="form-select">
            <option>Cash</option><option>Bank Transfer</option><option>Mobile Money</option><option>Cheque</option><option>Card</option><option>Other</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Reference (optional)</label><input type="text" name="reference" class="form-control" placeholder="Transaction ref / slip no"></div>
        <div class="mb-2"><label class="form-label fw-semibold">Payment Date</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="mb-2"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>Record Payment</button>
      </div>
    </form>
  </div>
</div>

<!-- UPLOAD DOCUMENT MODAL -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload_document">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label fw-semibold">Student</label>
          <select name="student_id" class="form-select" required>
            <option value="">Select student…</option>
            <?php
            $usr = $students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
            if ($usr) while ($u = $usr->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name'] ?: $u['student_number']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Document Type</label>
          <select name="document_type" class="form-select">
            <option>Transcript</option><option>Result Slip</option><option>Certificate</option><option>Receipt</option><option>ID</option><option>Admission Letter</option><option>Other</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Title</label><input type="text" name="document_title" class="form-control" required></div>
        <div class="mb-2"><label class="form-label fw-semibold">File</label><input type="file" name="doc_file" class="form-control" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-info text-white"><i class="fas fa-upload me-1"></i>Upload</button>
      </div>
    </form>
  </div>
</div>

<!-- REGISTER COURSE MODAL -->
<div class="modal fade" id="registerCourseModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="register_course">
      <div class="modal-header bg-purple text-white" style="background:#8b5cf6">
        <h5 class="modal-title"><i class="fas fa-book me-2"></i>Register Course</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label fw-semibold">Student</label>
          <select name="student_id" class="form-select" required>
            <option value="">Select student…</option>
            <?php
            $crr = $students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
            if ($crr) while ($cr = $crr->fetch_assoc()): ?>
            <option value="<?= $cr['id'] ?>"><?= htmlspecialchars($cr['full_name'] ?: $cr['student_number']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Course</label>
          <select name="course_id" class="form-select" required>
            <option value="">Select course…</option>
            <?php foreach($courses_catalog as $cc): ?>
            <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['course_code']) ?> – <?= htmlspecialchars($cc['course_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Academic Year</label>
          <select name="academic_year" class="form-select">
            <option>2024-2025</option><option>2025-2026</option><option selected><?= date('Y').'-'.(date('Y')+1) ?></option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label fw-semibold">Semester</label>
          <select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn" style="background:#8b5cf6;color:#fff"><i class="fas fa-save me-1"></i>Register</button>
      </div>
    </form>
  </div>
</div>

<!-- TRASH confirm modal -->
<form method="POST" id="trashForm">
  <input type="hidden" name="action" value="trash_student">
  <input type="hidden" name="id" id="trash_id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editStudent(s){
  document.getElementById('edit_id').value  = s.id;
  document.getElementById('edit_fn').value  = s.first_name;
  document.getElementById('edit_sn').value  = s.surname;
  document.getElementById('edit_on').value  = s.other_name||'';
  document.getElementById('edit_ph').value  = s.phone||'';
  document.getElementById('edit_em').value  = s.email||'';
  const crs = document.getElementById('edit_crs');
  for(let o of crs.options) if(o.value===s.course) o.selected=true;
  document.getElementById('edit_yr').value  = s.current_year||1;
  const sem = document.getElementById('edit_sem');
  for(let o of sem.options) if(o.value===s.current_semester) o.selected=true;
  const st = document.getElementById('edit_st');
  for(let o of st.options) if(o.value===s.status) o.selected=true;
  new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}
function trashStudent(id, name){
  if(!confirm('Move '+name+' to trash?')) return;
  document.getElementById('trash_id').value = id;
  document.getElementById('trashForm').submit();
}
function viewStudent(s){
  const fn = s.full_name || (s.first_name+' '+(s.other_name||'')+' '+s.surname);
  document.getElementById('viewStudentBody').innerHTML = `
    <div class="row g-2 small">
      <div class="col-md-6"><strong>Full Name:</strong> ${fn}</div>
      <div class="col-md-6"><strong>Reg No:</strong> ${s.registration_number||s.student_number}</div>
      <div class="col-md-6"><strong>National ID:</strong> ${s.national_student_id_number||'-'}</div>
      <div class="col-md-6"><strong>Program:</strong> ${s.course||'-'}</div>
      <div class="col-md-3"><strong>Year:</strong> ${s.current_year||'-'}</div>
      <div class="col-md-3"><strong>Semester:</strong> ${s.current_semester||'-'}</div>
      <div class="col-md-3"><strong>Set:</strong> ${s.set_name||'-'}</div>
      <div class="col-md-3"><strong>Gender:</strong> ${s.gender||'-'}</div>
      <div class="col-md-6"><strong>Phone:</strong> ${s.phone||'-'}</div>
      <div class="col-md-6"><strong>Email:</strong> ${s.email||'-'}</div>
      <div class="col-md-6"><strong>Intake Date:</strong> ${s.intake_date||'-'}</div>
      <div class="col-md-6"><strong>Status:</strong> <span class="badge bg-success">${s.status}</span></div>
    </div>`;
  new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
}

// ── Print student profile ──
function printStudent(s){
  const fn = s.full_name || (s.first_name+' '+(s.other_name||'')+' '+s.surname);
  const w = window.open('','_blank');
  w.document.write(`<!DOCTYPE html><html><head><title>Student Profile</title>
  <style>body{font-family:sans-serif;padding:30px}table{width:100%;border-collapse:collapse}td{padding:6px 8px;border-bottom:1px solid #ddd}h2{color:#1f2937}.header{text-align:center;margin-bottom:20px}@media print{body{print-color-adjust:exact}}</style></head><body>
  <div class="header"><h2>Student Profile</h2></div>
  <table><tr><td><strong>Full Name:</strong></td><td>${fn}</td></tr>
  <tr><td><strong>Reg No:</strong></td><td>${s.registration_number||s.student_number}</td></tr>
  <tr><td><strong>National ID:</strong></td><td>${s.national_student_id_number||'-'}</td></tr>
  <tr><td><strong>Program:</strong></td><td>${s.course||'-'}</td></tr>
  <tr><td><strong>Year:</strong></td><td>${s.current_year||'-'}</td></tr>
  <tr><td><strong>Semester:</strong></td><td>${s.current_semester||'-'}</td></tr>
  <tr><td><strong>Set:</strong></td><td>${s.set_name||'-'}</td></tr>
  <tr><td><strong>Gender:</strong></td><td>${s.gender||'-'}</td></tr>
  <tr><td><strong>Phone:</strong></td><td>${s.phone||'-'}</td></tr>
  <tr><td><strong>Email:</strong></td><td>${s.email||'-'}</td></tr>
  <tr><td><strong>Intake:</strong></td><td>${s.intake_date||'-'}</td></tr>
  <tr><td><strong>Status:</strong></td><td>${s.status}</td></tr></table>
  <script>window.onload=function(){window.print()};<\/script></body></html>`);
  w.document.close();
}

// ── Student Fee Modal ──
function studentFeeModal(id, name){
  const d = document.getElementById('financeData');
  d.innerHTML = '<em>Loading…</em>';
  document.querySelector('#finance').scrollIntoView({behavior:'smooth'});
  fetch('academic-registrar.php?ajax=get_financial&student_id='+id)
    .then(r=>r.json()).then(data=>{
      let h = `<div class="d-flex gap-3 mb-2"><span><strong>Invoiced:</strong> ${data.total_invoiced.toLocaleString()}</span>
        <span><strong>Paid:</strong> ${data.total_paid.toLocaleString()}</span>
        <span><strong>Balance:</strong> ${data.balance.toLocaleString()}</span></div>`;
      if(data.invoices.length){
        h += '<h6 class="mt-2">Invoices</h6><table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Invoice</th><th>Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th></tr></thead><tbody>';
        data.invoices.forEach((inv,i)=>{ h+=`<tr><td>${i+1}</td><td>${inv.invoice_number}</td><td>${inv.fee_type}</td><td>${Number(inv.total_amount).toLocaleString()}</td><td>${Number(inv.amount_paid).toLocaleString()}</td><td>${Number(inv.balance).toLocaleString()}</td><td>${inv.status}</td><td>${inv.due_date||'-'}</td></tr>`; });
        h += '</tbody></table>';
      } else { h += '<p class="text-muted">No invoices.</p>'; }
      if(data.payments.length){
        h += '<h6 class="mt-2">Payments</h6><table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Ref</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        data.payments.forEach((pay,i)=>{ h+=`<tr><td>${i+1}</td><td>${pay.payment_reference}</td><td>${Number(pay.amount_received).toLocaleString()}</td><td>${pay.payment_method}</td><td>${pay.payment_date}</td><td>${pay.status}</td></tr>`; });
        h += '</tbody></table>';
      } else { h += '<p class="text-muted">No payments recorded.</p>'; }
      document.getElementById('financeStudent').value = id;
      d.innerHTML = h;
    }).catch(()=>{ d.innerHTML = '<span class="text-danger">Error loading data.</span>'; });
}

// ── Student Payment Modal ──
function studentPaymentModal(id, name){
  const sel = document.getElementById('payStudentSelect');
  for(let o of sel.options) if(o.value==id) o.selected=true;
  new bootstrap.Modal(document.getElementById('recordPaymentModal')).show();
}

// ── Student Results Modal ──
function studentResultsModal(id, name){
  const d = document.getElementById('resultData');
  d.innerHTML = '<em>Loading…</em>';
  document.querySelector('#results').scrollIntoView({behavior:'smooth'});
  fetch('academic-registrar.php?ajax=get_results&student_id='+id)
    .then(r=>r.json()).then(data=>{
      document.getElementById('resultStudent').value = id;
      if(data.length){
        let h = '<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Exam</th><th>Type</th><th>Course</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Status</th></tr></thead><tbody>';
        data.forEach((r,i)=>{ h+=`<tr><td>${i+1}</td><td>${r.exam_number}</td><td>${r.exam_type}</td><td>${r.course_code}</td><td>${r.continuous_assessment_marks||'-'}</td><td>${r.final_exam_marks||'-'}</td><td>${r.marks_obtained}/${r.total_marks}</td><td><strong>${r.grade||'-'}</strong></td><td>${r.grade_status||'-'}</td></tr>`; });
        h += '</tbody></table>';
        d.innerHTML = h;
      } else { d.innerHTML = '<p class="text-muted">No results found.</p>'; }
    }).catch(()=>{ d.innerHTML = '<span class="text-danger">Error loading data.</span>'; });
}

// ── Student Courses Modal ──
function studentCoursesModal(id, name){
  const d = document.getElementById('courseData');
  d.innerHTML = '<em>Loading…</em>';
  document.querySelector('#courses').scrollIntoView({behavior:'smooth'});
  fetch('academic-registrar.php?ajax=get_courses&student_id='+id)
    .then(r=>r.json()).then(data=>{
      document.getElementById('courseStudent').value = id;
      if(data.length){
        let h = '<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Course</th><th>Code</th><th>Year</th><th>Semester</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        data.forEach((c,i)=>{ h+=`<tr><td>${i+1}</td><td>${c.course_name||'-'}</td><td>${c.course_code||'-'}</td><td>${c.academic_year}</td><td>${c.semester}</td><td>${c.status}</td><td>${c.registration_date||'-'}</td></tr>`; });
        h += '</tbody></table>';
        d.innerHTML = h;
      } else { d.innerHTML = '<p class="text-muted">No course registrations.</p>'; }
    }).catch(()=>{ d.innerHTML = '<span class="text-danger">Error loading data.</span>'; });
}

// ── Section loaders ──
function loadStudentFinance(){ const sid=document.getElementById('financeStudent').value; if(sid) studentFeeModal(sid,''); }
function loadStudentCourses(){ const sid=document.getElementById('courseStudent').value; if(sid) studentCoursesModal(sid,''); }
function loadStudentAttendance(){
  const sid=document.getElementById('attStudent').value;
  if(!sid){ document.getElementById('attendanceData').innerHTML='<span class="text-muted">Select a student.</span>'; return; }
  const d=document.getElementById('attendanceData');
  d.innerHTML='<em>Loading…</em>';
  fetch('academic-registrar.php?ajax=get_attendance&student_id='+sid)
    .then(r=>r.json()).then(data=>{
      if(data.length){
        let h='<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Date</th><th>Subject</th><th>Course</th><th>Status</th><th>Remarks</th></tr></thead><tbody>';
        data.forEach((a,i)=>{ h+=`<tr><td>${i+1}</td><td>${a.date}</td><td>${a.subject||'-'}</td><td>${a.course_code||'-'}</td><td><span class="badge ${a.status==='Present'?'bg-success':a.status==='Late'?'bg-warning':'bg-secondary'}">${a.status}</span></td><td>${a.remarks||'-'}</td></tr>`; });
        h+='</tbody></table>'; d.innerHTML=h;
      } else { d.innerHTML='<p class="text-muted">No attendance records.</p>'; }
    }).catch(()=>{ d.innerHTML='<span class="text-danger">Error loading data.</span>'; });
}
function loadStudentResults(){ const sid=document.getElementById('resultStudent').value; if(sid) studentResultsModal(sid,''); }
function loadStudentDocs(){
  const sid=document.getElementById('docStudent').value;
  if(!sid){ document.getElementById('docData').innerHTML='<span class="text-muted">Select a student.</span>'; return; }
  const d=document.getElementById('docData');
  d.innerHTML='<em>Loading…</em>';
  fetch('academic-registrar.php?ajax=get_documents&student_id='+sid)
    .then(r=>r.json()).then(data=>{
      if(data.length){
        let h='<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Title</th><th>Type</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
        data.forEach((doc,i)=>{
          const dlPath = doc.file_path ? '../'+doc.file_path : '#';
          h+=`<tr><td>${i+1}</td><td>${doc.document_title}</td><td>${doc.document_type}</td><td>${doc.generation_date}</td><td>${doc.file_path ? '<a href="'+dlPath+'" target="_blank" class="btn btn-sm btn-outline-primary btn-tbl"><i class="fas fa-download"></i></a>' : '-'}</td></tr>`;
        });
        h+='</tbody></table>'; d.innerHTML=h;
      } else { d.innerHTML='<p class="text-muted">No documents uploaded.</p>'; }
    }).catch(()=>{ d.innerHTML='<span class="text-danger">Error loading data.</span>'; });
}

// ── Smooth scroll for sidebar nav ──
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
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
