<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';

$ctx = bootstrapStaffDashboard(['director', 'academics']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Director of Academics';
$website_conn = $ctx['website'];
$students_conn = $ctx['students'] ?? null;
$user_id = (int)($user['id'] ?? 0);

function safeCount($c, $s) { $r=$c->query($s); if(!$r)return 0; $w=$r->fetch_assoc(); return intval($w['c']??0); }

// ── Real stats ──
$total_students   = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students") : 0;
$total_lecturers  = safeCount($conn,"SELECT COUNT(*)c FROM staff WHERE position LIKE '%Lecturer%' OR position LIKE '%lecturer%'");
$active_courses   = safeCount($conn,"SELECT COUNT(*)c FROM academic_course_catalog WHERE status='Active'");
$active_programs  = safeCount($conn,"SELECT COUNT(*)c FROM academic_programs WHERE status='Active'");
$avg_gpa = 0;
$r=$conn->query("SELECT AVG(marks) avg FROM academic_records WHERE assessment_type='Exam'");
if($r){ $row=$r->fetch_assoc(); $avg_gpa=round((float)($row['avg']??0),1); }

// ── Load real data ──
$programs = []; $r=$conn->query("SELECT * FROM academic_programs ORDER BY program_name");
if($r) while($row=$r->fetch_assoc()) $programs[]=$row;

$courses_catalog = []; $r=$conn->query("SELECT cc.*,p.program_name FROM academic_course_catalog cc LEFT JOIN academic_programs p ON cc.program_code=p.program_code WHERE cc.status='Active' ORDER BY cc.course_title");
if($r) while($row=$r->fetch_assoc()) $courses_catalog[]=$row;

$exams = []; $r=$conn->query("SELECT exam_number,exam_type,course_code,program_code,semester,exam_date,exam_room,status FROM examination_records ORDER BY exam_date DESC LIMIT 20");
if($r) while($row=$r->fetch_assoc()) $exams[]=$row;

$lecturers = []; $r=$conn->query("SELECT id,full_name,position,department FROM staff WHERE position LIKE '%Lecturer%' OR position LIKE '%lecturer%' OR position LIKE '%Head%' ORDER BY full_name");
if($r) while($row=$r->fetch_assoc()) $lecturers[]=$row;

$students = $students_conn ? [] : []; if($students_conn){ $r=$students_conn->query("SELECT id,student_number,registration_number,full_name,first_name,surname,course,current_year,gender,phone,mobile_number,email,status,index_number,national_student_id_number FROM students ORDER BY full_name LIMIT 200");
if($r) while($row=$r->fetch_assoc()) $students[]=$row; }

$user_role_id = 0; $ri = $conn->query("SELECT role_id FROM staff WHERE id = " . intval($user_id));
if ($ri) { $user_role_id = (int)$ri->fetch_assoc()['role_id']; }

$recent_activities = []; $r=$conn->query("SELECT activity_description activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
if($r) while($row=$r->fetch_assoc()) $recent_activities[]=$row;

// ── Report generation ──
$report = $_GET['report'] ?? '';
if ($report) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px}th{background:#f3f4f6}h2{color:#1f2937}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print"><button onclick="window.print()" style="padding:6px 16px;margin-bottom:12px">Print</button> <button onclick="window.close()" style="padding:6px 16px">Close</button></div>';
    if ($report === 'student_progress') {
        echo '<h2>Student Progress Report</h2>';
        $r=$students_conn->query("SELECT s.full_name,s.student_number,s.course,s.current_year,COUNT(ar.id) exams,AVG(ar.marks) avg_marks FROM students s LEFT JOIN staffs_db.academic_records ar ON s.id=ar.student_id WHERE s.status='Active' GROUP BY s.id ORDER BY s.full_name LIMIT 100");
        echo '<table><thead><tr><th>Name</th><th>Reg No</th><th>Program</th><th>Year</th><th>Exams</th><th>Avg Marks</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['student_number']).'</td><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['current_year'].'</td><td>'.($row['exams']??0).'</td><td>'.round($row['avg_marks']??0,1).'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'attendance_report') {
        echo '<h2>Attendance Report</h2>';
        if($students_conn){ $r=$students_conn->query("SELECT s.full_name,s.student_number,s.course,COUNT(a.id) total,SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) present FROM students s LEFT JOIN student_attendance a ON s.id=a.student_id WHERE s.status='Active' GROUP BY s.id ORDER BY s.full_name LIMIT 100");
        echo '<table><thead><tr><th>Name</th><th>Reg No</th><th>Program</th><th>Total Days</th><th>Present</th><th>Rate</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ $rt=$row['total']>0?round(($row['present']/$row['total'])*100,1).'%':'-'; echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['student_number']).'</td><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['total'].'</td><td>'.$row['present'].'</td><td>'.$rt.'</td></tr>'; }
        echo '</tbody></table>'; }
    } elseif ($report === 'graduation') {
        echo '<h2>Graduation Report</h2>';
        $r=$students_conn->query("SELECT course,COUNT(*) total FROM students WHERE status IN('Graduated','graduation_candidate') GROUP BY course");
        echo '<table><thead><tr><th>Program</th><th>Graduating Students</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['total'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'academic_performance') {
        echo '<h2>Academic Performance Report</h2>';
        $r=$conn->query("SELECT course_code,COUNT(*) total,SUM(CASE WHEN grade IN('A','B','C','D') THEN 1 ELSE 0 END) passed,AVG(marks) avg_marks,AVG(gpa) avg_gpa FROM academic_records WHERE assessment_type='Exam' GROUP BY course_code");
        echo '<table><thead><tr><th>Course</th><th>Students</th><th>Passed</th><th>Pass Rate</th><th>Avg Marks</th><th>Avg GPA</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ $pr=$row['total']>0?round(($row['passed']/$row['total'])*100,1).'%':'-'; echo '<tr><td>'.htmlspecialchars($row['course_code']).'</td><td>'.$row['total'].'</td><td>'.$row['passed'].'</td><td>'.$pr.'</td><td>'.round($row['avg_marks']??0,1).'</td><td>'.round($row['avg_gpa']??0,2).'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'program_courses') {
        $pc = $conn->real_escape_string($_GET['program_code']??'');
        echo '<h2>Courses for Program: '.htmlspecialchars($pc).'</h2>';
        $r=$conn->query("SELECT cc.*,p.program_name FROM academic_course_catalog cc LEFT JOIN academic_programs p ON cc.program_code=p.program_code WHERE cc.program_code='$pc' ORDER BY cc.year_of_study,cc.semester,cc.course_code");
        echo '<table><thead><tr><th>Code</th><th>Title</th><th>Year</th><th>Semester</th><th>Credits</th><th>Status</th></tr></thead><tbody>';
        if($r && $r->num_rows>0) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['course_code']).'</td><td>'.htmlspecialchars($row['course_title']).'</td><td>'.$row['year_of_study'].'</td><td>'.$row['semester'].'</td><td>'.$row['credits'].'</td><td>'.htmlspecialchars($row['status']).'</td></tr>'; }
        else echo '<tr><td colspan="6" class="text-center text-muted">No courses found for this program.</td></tr>';
        echo '</tbody></table>';
    } elseif ($report === 'program_enrollment') {
        $filterProg = $_GET['program'] ?? '';
        echo '<h2>Program Enrollment Report</h2>';
        if($filterProg){ echo '<p><strong>Filtered by:</strong> '.htmlspecialchars($filterProg).'</p>'; $r=$students_conn->query("SELECT course,COUNT(*) total,SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) active FROM students WHERE course='".$students_conn->real_escape_string($filterProg)."' GROUP BY course"); }
        else { $r=$students_conn->query("SELECT course,COUNT(*) total,SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) active FROM students GROUP BY course"); }
        echo '<table><thead><tr><th>Program</th><th>Total</th><th>Active</th><th>Inactive</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ $in=$row['total']-$row['active']; echo '<tr><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['total'].'</td><td>'.$row['active'].'</td><td>'.$in.'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'lecturer_workload') {
        echo '<h2>Lecturer Workload Report</h2>';
        $r=$conn->query("SELECT s.full_name,s.position,s.department,COUNT(ca.id) courses_assigned FROM staff s LEFT JOIN course_assignments ca ON s.id=ca.lecturer_id WHERE s.position LIKE '%Lecturer%' OR s.position LIKE '%lecturer%' GROUP BY s.id ORDER BY courses_assigned DESC");
        echo '<table><thead><tr><th>Lecturer</th><th>Position</th><th>Department</th><th>Courses Assigned</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['position']).'</td><td>'.htmlspecialchars($row['department']??'-').'</td><td>'.$row['courses_assigned'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'fee_statement') {
        $sid = intval($_GET['student_id']??0);
        echo '<h2>Fee Statement</h2>';
        if($sid && $students_conn){ $qs=$students_conn->query("SELECT * FROM students WHERE id=" . intval($sid)); $s=$qs?$qs->fetch_assoc():null; if($s){ echo '<p><strong>'.htmlspecialchars($s['full_name']).'</strong> ('.htmlspecialchars($s['registration_number']?:$s['student_number']).') - '.htmlspecialchars($s['course']).'</p>'; }
        $r=$students_conn->query("SELECT invoice_number,fee_type,total_amount,amount_paid,balance,due_date,status FROM student_invoices WHERE student_id=" . intval($sid));
        echo '<table><thead><tr><th>Invoice</th><th>Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Due</th><th>Status</th></tr></thead><tbody>';
        $ttl=0;$tpd=0; if($r) while($row=$r->fetch_assoc()){ $ttl+=$row['total_amount'];$tpd+=$row['amount_paid']; echo '<tr><td>'.$row['invoice_number'].'</td><td>'.$row['fee_type'].'</td><td>'.number_format($row['total_amount'],0).'</td><td>'.number_format($row['amount_paid'],0).'</td><td>'.number_format($row['balance'],0).'</td><td>'.$row['due_date'].'</td><td>'.$row['status'].'</td></tr>'; }
        echo '</tbody></table><p><strong>Total Invoiced:</strong> '.number_format($ttl,0).' | <strong>Total Paid:</strong> '.number_format($tpd,0).' | <strong>Balance:</strong> '.number_format($ttl-$tpd,0).'</p>'; }
    } elseif ($report === 'student_detail') {
        $sid = intval($_GET['student_id']??0);
        echo '<h2>Student Detail Report</h2>';
        if($sid && $students_conn){ $qs=$students_conn->query("SELECT * FROM students WHERE id=" . intval($sid)); $s=$qs?$qs->fetch_assoc():null; if($s){ echo '<table>'; foreach($s as $k=>$v){ echo '<tr><td><strong>'.ucwords(str_replace('_',' ',$k)).':</strong></td><td>'.htmlspecialchars($v??'-').'</td></tr>'; } echo '</table>'; } }
    }
    echo '</body></html>'; exit;
}

// ── AJAX ──
$ajax = $_GET['ajax'] ?? '';
$ajaxSid = intval($_GET['student_id'] ?? 0);
if ($ajax && $ajaxSid > 0) {
    header('Content-Type: application/json');
    if ($ajax === 'student_profile') {
        $info=[];$r=$students_conn->query("SELECT * FROM students WHERE id=$ajaxSid"); if($r)$info=$r->fetch_assoc();
        $att=[];if($students_conn){$r=$students_conn->query("SELECT date,status FROM student_attendance WHERE student_id=$ajaxSid ORDER BY date DESC LIMIT 30");if($r)while($row=$r->fetch_assoc())$att[]=$row;}
        $inv=[];if($students_conn){$r=$students_conn->query("SELECT invoice_number,fee_type,total_amount,amount_paid,balance,status FROM student_invoices WHERE student_id=$ajaxSid");if($r)while($row=$r->fetch_assoc())$inv[]=$row;}
        $pay=[];if($students_conn){$r=$students_conn->query("SELECT payment_reference,amount_received,payment_method,payment_date,status FROM payments WHERE student_id=$ajaxSid");if($r)while($row=$r->fetch_assoc())$pay[]=$row;}
        $docs=[];$r=$conn->query("SELECT id,document_type,document_title,file_path,generation_date FROM generated_documents WHERE student_id=$ajaxSid");if($r)while($row=$r->fetch_assoc())$docs[]=$row;
        echo json_encode(['info'=>$info,'attendance'=>$att,'invoices'=>$inv,'payments'=>$pay,'documents'=>$docs]); exit;
    }
    echo json_encode([]); exit;
}

// ── POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_exam') {
        $cc=$conn->real_escape_string($_POST['course_code']??'');
        $et=$conn->real_escape_string($_POST['exam_type']??'');
        $pc=$conn->real_escape_string($_POST['program_code']??'');
        $sd=$_POST['exam_date']??'';
        $rm=$conn->real_escape_string($_POST['exam_room']??'');
        $sem=$conn->real_escape_string($_POST['semester']??'Semester 1');
        $ay=$conn->real_escape_string($_POST['academic_year']??date('Y').'-'.(date('Y')+1));
        $en='EXM-'.date('Ymd').'-'.mt_rand(1000,9999);
        $conn->query("INSERT INTO examination_records (exam_number,exam_type,course_code,program_code,exam_date,exam_room,semester,academic_year,status,created_by) VALUES ('$en','$et','$cc','$pc','$sd','$rm','$sem','$ay','Scheduled',$user_id)");
        if($conn->affected_rows>0)$_SESSION['success']="Exam $en created."; else $_SESSION['error']=$conn->error;
        header("Location: director-academics.php"); exit;
    }

    if ($action === 'publish_results') {
        $en=$conn->real_escape_string($_POST['exam_number']??'');
        $conn->query("UPDATE examination_records SET status='Published' WHERE exam_number='$en'");
        $_SESSION['success']="Results published for $en.";
        header("Location: director-academics.php"); exit;
    }

    if ($action === 'enter_marks') {
        $en=$conn->real_escape_string($_POST['exam_number']??'');
        $sid=intval($_POST['student_id']??0);
        $mk=floatval($_POST['marks']??0);
        $gr=$conn->real_escape_string($_POST['grade']??'');
        $cc=$conn->real_escape_string($_POST['course_code']??'');
        if($en && $sid){ $conn->query("INSERT INTO academic_records (student_id,course_code,course_name,assessment_type,marks,grade,graded_by) VALUES ($sid,'$cc','','Exam',$mk,'$gr',$user_id)"); $_SESSION['success']='Marks entered.'; }
        else { $_SESSION['error']='Exam and student required.'; }
        header("Location: director-academics.php"); exit;
    }

    if ($action === 'transcript_request') {
        $sid=intval($_POST['student_id']??0);
        $dn=$conn->real_escape_string($_POST['document_title']??'Transcript');
        if($sid){ $conn->query("INSERT INTO generated_documents (document_type,student_id,generated_by,document_title,file_path) VALUES ('Transcript',$sid,$user_id,'$dn','')"); $_SESSION['success']='Transcript generated.'; }
        header("Location: director-academics.php"); exit;
    }

    header("Location: director-academics.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root {
  --card-radius: 16px;
  --card-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 4px 12px rgba(15,23,42,0.05), 0 12px 30px rgba(15,23,42,0.03);
  --card-hover-shadow: 0 4px 8px rgba(15,23,42,0.05), 0 12px 24px rgba(15,23,42,0.06), 0 24px 48px rgba(15,23,42,0.04);
}
/* Override chocolate/yellow gradients — clean white cards */
.content-section,
.stat-card,
.report-card,
.section-card {
  background: #fff !important;
  border: 1px solid rgba(148,163,184,0.16) !important;
}
/* Override card accent stripes */
.stat-card,
.report-card {
  border-top: 4px solid transparent !important;
  border-radius: var(--card-radius) !important;
}
.stat-card.success { border-top-color: #059669 !important; }
.stat-card.primary { border-top-color: #1a237e !important; }
.stat-card.info    { border-top-color: #0284c7 !important; }
.stat-card.warning { border-top-color: #d97706 !important; }
/* Disable hover-transform on content sections (too janky) */
.content-section:hover,
.stat-card:hover,
.report-card:hover,
.section-card:hover {
  transform: none !important;
}
/* Stat card layout fix */
.stat-card {
  display: flex !important;
  align-items: center !important;
  gap: 18px !important;
  padding: 22px 24px !important;
  transition: box-shadow 0.3s ease !important;
}
.stat-card:hover {
  box-shadow: 0 4px 8px rgba(15,23,42,0.05), 0 12px 24px rgba(15,23,42,0.06), 0 24px 48px rgba(15,23,42,0.04) !important;
}
.stat-content h3 { font-size: 1.7rem !important; font-weight: 800 !important; color: #0f172a !important; }
.stat-content p  { font-size: 0.82rem !important; color: #64748b !important; font-weight: 500 !important; }
/* Section card inside content-sections */
.section-card {
  background: #fff !important;
  padding: 20px 22px !important;
  border-radius: var(--card-radius) !important;
  box-shadow: var(--card-shadow) !important;
  transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.35s cubic-bezier(0.34,1.56,0.64,1) !important;
}
.section-card:hover {
  transform: translateY(-3px) !important;
  box-shadow: var(--card-hover-shadow) !important;
}
/* Report cards */
.report-card {
  padding: 24px 20px !important;
  text-align: center !important;
}
.report-card h3 { font-size: 1rem !important; font-weight: 700 !important; color: #0f172a !important; margin-bottom: 6px !important; }
.report-card p  { font-size: 0.82rem !important; color: #64748b !important; margin-bottom: 14px !important; }
.report-card .btn { border-radius: 8px !important; font-weight: 600 !important; font-size: 0.82rem !important; padding: 6px 18px !important; }
/* Empty states */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #94a3b8;
}
.empty-state i {
  font-size: 2.8rem;
  margin-bottom: 12px;
  opacity: 0.5;
}
.empty-state p {
  font-size: 0.9rem;
  margin: 0;
}
/* Activities list */
.activities-list { display: flex; flex-direction: column; gap: 8px; }
.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 10px;
  border-left: 3px solid #1a237e;
  transition: background 0.2s;
}
.activity-item:hover { background: #f1f5f9; }
.activity-icon {
  width: 32px; height: 32px;
  background: linear-gradient(135deg, #1a237e, #3949ab);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 14px; flex-shrink: 0;
}
.activity-content { font-size: 0.85rem; }
.activity-content strong { color: #0f172a; display: block; margin-bottom: 2px; }
/* Tables */
.table thead th {
  background: #f8fafc !important;
  color: #475569 !important;
  font-weight: 600 !important;
  font-size: 0.78rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.04em !important;
  border-bottom: 2px solid #e2e8f0 !important;
  padding: 10px 12px !important;
}
.table td {
  padding: 10px 12px !important;
  font-size: 0.85rem !important;
  vertical-align: middle !important;
}
.table-hover tbody tr:hover { background: #f1f5f9 !important; }
/* Badges */
.badge { font-weight: 600 !important; font-size: 0.75rem !important; padding: 4px 10px !important; border-radius: 6px !important; }
/* Buttons in tables */
.table .btn-sm { padding: 4px 8px !important; font-size: 0.78rem !important; border-radius: 6px !important; }
/* Program cards */
#programs .stat-card {
  display: block !important;
  padding: 20px 22px !important;
  border-top: 4px solid #1a237e !important;
}
#programs .stat-card h3 { font-size: 1.05rem !important; }
/* Dashboard sections headings */
.content-section h2 {
  font-size: 1.15rem !important;
  font-weight: 700 !important;
  color: #0f172a !important;
  margin-bottom: 16px !important;
  padding-bottom: 10px !important;
  border-bottom: 2px solid #f1f5f9 !important;
}
.content-section h2 i { color: #1a237e !important; }
/* Header */
.dashboard-header h1 { font-size: 1.35rem !important; font-weight: 700 !important; color: #0f172a !important; }
.dashboard-header p { font-size: 0.85rem !important; color: #64748b !important; margin: 0 !important; }
/* Search input */
#studentSearch { border-radius: 8px !important; border: 1px solid #e2e8f0 !important; font-size: 0.85rem !important; padding: 8px 14px !important; }
#studentSearch:focus { border-color: #1a237e !important; box-shadow: 0 0 0 3px rgba(26,35,126,0.1) !important; }
/* Hierarchy chart container */
.section-card .hierarchy-chart { margin-top: 12px; }
/* Reports grid */
.reports-grid { gap: 18px !important; }
/* Quick Actions buttons */
#quick .btn { border-radius: 8px !important; font-weight: 500 !important; font-size: 0.82rem !important; padding: 6px 16px !important; }
/* Modal improvements */
.modal-content { border: none !important; border-radius: 16px !important; max-height: 85vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.2) !important; }
.modal-header { border-radius: 16px 16px 0 0 !important; padding: 14px 20px !important; }
.modal-header .modal-title { font-weight: 700 !important; font-size: 1rem !important; }
.modal-footer { border-top: 1px solid #f1f5f9 !important; padding: 12px 20px !important; }
.modal-body { padding: 20px !important; }
.form-label { font-weight: 600 !important; font-size: 0.82rem !important; color: #374151 !important; margin-bottom: 4px !important; }
.form-select, .form-control { border-radius: 8px !important; border: 1px solid #e2e8f0 !important; font-size: 0.85rem !important; padding: 8px 12px !important; }
.form-select:focus, .form-control:focus { border-color: #1a237e !important; box-shadow: 0 0 0 3px rgba(26,35,126,0.1) !important; }
/* Alerts panel inside section-card */
.section-card .alert-item { font-size: 0.85rem !important; }
/* Responsive: stack stat cards on small screens */
@media (max-width: 768px) {
  .stats-grid { grid-template-columns: 1fr 1fr !important; }
  .content-section { padding: 16px !important; }
}
@media (max-width: 480px) {
  .stats-grid { grid-template-columns: 1fr !important; }
}
</style>
</head>
<body>
    <div class="dashboard-container">
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Academic Director</h1>
                    <p>Academic Programs Oversight, Iganga School of Nursing and Midwifery</p>
                </div>
                <div class="header-right">
                    <div class="date-time"><i class="fas fa-calendar"></i><span><?php echo date('l, F j, Y'); ?></span></div>
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

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Academic Overview -->
                <section id="overview" class="content-section dashboard-section active" data-section="overview">
                    <h2 class="mb-3">Academic Overview</h2>
                    <div class="stats-grid mb-4">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                            <div class="stat-content"><h3><?php echo number_format($total_students); ?></h3><p>Total Students</p></div>
                        </div>
                        <div class="stat-card primary">
                            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="stat-content"><h3><?php echo number_format($total_lecturers); ?></h3><p>Total Lecturers</p></div>
                        </div>
                        <div class="stat-card info">
                            <div class="stat-icon"><i class="fas fa-book"></i></div>
                            <div class="stat-content"><h3><?php echo number_format($active_courses); ?></h3><p>Active Courses</p></div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="stat-content"><h3><?php echo number_format($avg_gpa, 1); ?></h3><p>Average GPA</p></div>
                        </div>
                    </div>

                    <div class="row g-3">
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
                                <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-chart-bar me-2 text-success"></i>Department Performance</h6>
                                <?php
                                $acadStaffId = 0;
                                $sq = $conn ? $conn->prepare("SELECT id FROM staff WHERE role_id = 4 AND status = 'Active' LIMIT 1") : false;
                                if ($sq) { $sq->execute(); $sr = $sq->get_result()->fetch_assoc(); $sq->close(); if ($sr) $acadStaffId = $sr['id']; }
                                echo renderDirectorPerformanceCard($acadStaffId, 4, 'Director Academics', $conn);
                                ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Official Duties -->
                <section id="duties" class="content-section dashboard-section" data-section="duties">
                    <h2><i class="fas fa-tasks me-2"></i>Official Duties &amp; Responsibilities</h2>
                    <?php
                    ob_start();
                    renderOfficialDuties($user_role_id, $conn);
                    $dutyHtml = ob_get_clean();
                    if (trim($dutyHtml) !== '') {
                        echo $dutyHtml;
                    } else {
                        echo '<div class="empty-state"><i class="fas fa-tasks"></i><p>No duties assigned yet.</p></div>';
                    }
                    ?>
                </section>

                <!-- Quick Actions -->
                <section id="quick" class="content-section dashboard-section" data-section="quick">
                    <h2><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h2>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <a href="../dashboards/academic-registrar.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-alt me-1"></i>Academic Registrar</a>
                        <a href="../dashboards/school-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-chalkboard-teacher me-1"></i>School Principal</a>
                        <a href="../dashboards/deputy-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-check me-1"></i>Deputy Principal</a>
                        <a href="../dashboards/head-nursing.php" class="btn btn-outline-success btn-sm"><i class="fas fa-heartbeat me-1"></i>Head of Nursing</a>
                        <a href="../dashboards/head-midwifery.php" class="btn btn-outline-success btn-sm"><i class="fas fa-user-md me-1"></i>Head of Midwifery</a>
                        <a href="../dashboards/lecturers.php" class="btn btn-outline-info btn-sm"><i class="fas fa-chalkboard me-1"></i>Lecturers</a>
                        <a href="../student-directory.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-address-book me-1"></i>Student Directory</a>
                        <a href="../dashboards/staff_transcript_generation.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-file-alt me-1"></i>Transcripts</a>
                    </div>
                </section>

                <!-- Program Management -->
                <section id="programs" class="content-section dashboard-section" data-section="programs">
                    <h2><i class="fas fa-book me-2"></i>Program Management</h2>
                    <?php if (!empty($programs)): ?>
                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                        <?php foreach($programs as $p):
                            $pcount = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students WHERE course='".$conn->real_escape_string($p['program_name'])."' AND status='Active'") : 0;
                        ?>
                        <div class="stat-card">
                            <h3 class="fw-bold"><?= htmlspecialchars($p['program_name']) ?></h3>
                            <p class="text-muted mb-2"><?= $p['program_type'] ?> | <?= $p['duration_years'] ?> Year Program | <?= htmlspecialchars($p['department']) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Active Students:</span>
                                <strong><?= $pcount ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="text-muted">Status:</span>
                                <strong class="text-<?= $p['status']==='Active'?'success':'secondary' ?>"><?= $p['status'] ?></strong>
                            </div>
                            <div class="mt-2 d-flex gap-1">
                                <button class="btn btn-sm btn-outline-info" onclick="viewProgramCourses('<?= htmlspecialchars($p['program_code']) ?>')"><i class="fas fa-eye"></i> Courses</button>
                                <button class="btn btn-sm btn-outline-success" onclick="viewEnrolledStudents('<?= htmlspecialchars($p['program_name']) ?>')"><i class="fas fa-users"></i> Students</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-book"></i><p>No programs configured yet.</p></div>
                    <?php endif; ?>
                </section>

                <!-- Examinations & Assessment -->
                <section id="exams" class="content-section dashboard-section" data-section="exams">
                    <h2><i class="fas fa-clipboard-list me-2"></i>Examinations & Assessment</h2>
                    <div class="mb-3">
                        <button class="btn btn-sm btn-primary" onclick="openExamModal()"><i class="fas fa-plus me-1"></i>Create Exam</button>
                        <button class="btn btn-sm btn-success" onclick="openEnterMarksModal()"><i class="fas fa-edit me-1"></i>Enter Marks</button>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Exam No</th><th>Type</th><th>Course</th><th>Program</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php if(empty($exams)): ?>
                            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No exams scheduled. Create one above.</p></div></td></tr>
                            <?php else: foreach($exams as $e): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($e['exam_number']) ?></code></td>
                                <td><?= htmlspecialchars($e['exam_type']) ?></td>
                                <td><?= htmlspecialchars($e['course_code']) ?></td>
                                <td><?= htmlspecialchars($e['program_code']??'All') ?></td>
                                <td><?= $e['exam_date'] ?></td>
                                <td><span class="badge bg-<?= $e['status']==='Published'?'success':($e['status']==='Scheduled'?'warning':'info') ?>"><?= $e['status'] ?></span></td>
                                <td>
                                    <?php if($e['status']!=='Published'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="publish_results">
                                        <input type="hidden" name="exam_number" value="<?= htmlspecialchars($e['exam_number']) ?>">
                                        <button class="btn btn-sm btn-outline-success" onclick="return confirm('Publish results?')"><i class="fas fa-check"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Department Alerts (moved into exams tab) -->
                    <div class="section-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold mb-0" style="font-size:0.95rem"><i class="fas fa-bell me-2 text-danger"></i>Department Alerts</h6>
                        </div>
                        <?= renderAlertsPanel($conn, 'ACAD', 5) ?>
                    </div>
                </section>

                <!-- Reports -->
                <section id="reports" class="content-section dashboard-section" data-section="reports">
                    <h2><i class="fas fa-chart-bar me-2"></i>Academic Reports</h2>
                    <div class="reports-grid">
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-file-alt"></i></div>
                            <h3>Student Progress Report</h3>
                            <p>Track student academic progress</p>
                            <button class="btn btn-primary" onclick="window.open('director-academics.php?report=student_progress','_blank')">Generate</button>
                        </div>
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-chart-line"></i></div>
                            <h3>Attendance Report</h3>
                            <p>View class attendance records</p>
                            <button class="btn btn-primary" onclick="window.open('director-academics.php?report=attendance_report','_blank')">Generate</button>
                        </div>
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-graduation-cap"></i></div>
                            <h3>Graduation Report</h3>
                            <p>Student graduation statistics</p>
                            <button class="btn btn-primary" onclick="window.open('director-academics.php?report=graduation','_blank')">Generate</button>
                        </div>
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-star"></i></div>
                            <h3>Academic Performance</h3>
                            <p>Overall student performance summary</p>
                            <button class="btn btn-primary" onclick="window.open('director-academics.php?report=academic_performance','_blank')">Generate</button>
                        </div>
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-layer-group"></i></div>
                            <h3>Program Enrollment</h3>
                            <p>Students by program</p>
                            <button class="btn btn-primary" onclick="window.open('director-academics.php?report=program_enrollment','_blank')">Generate</button>
                        </div>
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <h3>Lecturer Workload</h3>
                            <p>Courses per lecturer</p>
                            <button class="btn btn-primary" onclick="window.open('director-academics.php?report=lecturer_workload','_blank')">Generate</button>
                        </div>
                    </div>
                </section>

                <!-- News Management -->
                <section id="news" class="content-section dashboard-section" data-section="news">
                    <h2><i class="fas fa-newspaper me-2"></i>News &amp; Announcements</h2>
                    <?php renderNewsWidget($conn, $website_conn, $ctx['user']['id'] ?? 0, $user_name, $_SESSION['role'] ?? 'Director Academics', 5); ?>
                </section>

                <!-- Student Records -->
                <section id="student-records" class="content-section dashboard-section" data-section="student-records">
                    <h2><i class="fas fa-user-graduate me-2"></i>Student Records</h2>
                    <?php if(!empty($students)): ?>
                    <div class="mb-2"><input type="text" id="studentSearch" class="form-control form-control-sm" placeholder="Search by name, reg no..." onkeyup="filterStudentTable()"></div>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover" id="studentTable">
                            <thead><tr><th>Reg No</th><th>Name</th><th>Program</th><th>Year</th><th>Phone/Mobile</th><th>Index No</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach($students as $s):
                                $sname = htmlspecialchars($s['full_name'] ?: trim($s['first_name'].' '.$s['surname']));
                                $sreg = htmlspecialchars($s['registration_number'] ?: $s['student_number']);
                                $sphone = htmlspecialchars($s['phone']??'');
                                $smobile = htmlspecialchars($s['mobile_number']??'');
                                $sindex = htmlspecialchars($s['index_number']??$s['national_student_id_number']??'');
                            ?>
                            <tr>
                                <td><code><?= $sreg ?></code></td>
                                <td><?= $sname ?></td>
                                <td><?= htmlspecialchars($s['course']??'-') ?></td>
                                <td><?= $s['current_year']??'-' ?></td>
                                <td><?= $sphone ?: '-' ?><?= ($smobile && $smobile!==$sphone) ? '<br><small>M: '.$smobile.'</small>' : '' ?></td>
                                <td><?= $sindex ?: '-' ?></td>
                                <td><span class="badge bg-<?= $s['status']==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewStudentProfile(<?= $s['id'] ?>)"><i class="fas fa-eye"></i></button>
                                    <a href="../print-student.php?id=<?= $s['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></a>
                                    <button class="btn btn-sm btn-outline-warning" onclick="feeStatement(<?= $s['id'] ?>)"><i class="fas fa-money-bill"></i></button>
                                    <button class="btn btn-sm btn-outline-purple" onclick="generateTranscript(<?= $s['id'] ?>, '<?= addslashes($sname) ?>')"><i class="fas fa-file-alt"></i></button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="openEditStudentModal(<?= $s['id'] ?>)"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><i class="fas fa-user-graduate"></i><p>No student records available.</p></div>
                    <?php endif; ?>
                </section>

                <!-- Recent Activities -->
                <section id="activity" class="content-section dashboard-section" data-section="activity">
                    <h2><i class="fas fa-history me-2"></i>Recent Academic Activities</h2>
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
                        <div class="empty-state"><i class="fas fa-history"></i><p>No recent activities.</p></div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Approvals -->
                <section id="approvals" class="content-section dashboard-section" data-section="approvals">
                    <h2><i class="fas fa-check-double me-2 text-primary"></i>Pending Approvals</h2>
                    <?php
                    $acadApprovals = getPendingApprovals($conn, 4, 10);
                    if (!empty($acadApprovals)):
                        foreach ($acadApprovals as $apr):
                            echo renderApprovalWorkflowCard($apr, $conn);
                            echo renderApprovalActionButtons($apr['id']);
                        endforeach;
                    else:
                        echo '<div class="empty-state"><i class="fas fa-check-double"></i><p>No pending approvals.</p></div>';
                    endif;
                    ?>
                </section>
            </div>
        </div>
    </div>

    <!-- Create Exam Modal -->
    <div class="modal fade" id="createExamModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="create_exam">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create Exam</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Course</label><select name="course_code" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach($courses_catalog as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?> – <?= htmlspecialchars($c['course_title']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-6"><label class="form-label">Exam Type</label><select name="exam_type" class="form-select"><option>Mid Semester</option><option>End of Semester</option><option>Supplementary</option><option>Practical</option></select></div>
                        <div class="col-md-6"><label class="form-label">Program</label><select name="program_code" class="form-select">
                            <option value="">All Programs</option>
                            <?php foreach($programs as $p): ?><option value="<?= htmlspecialchars($p['program_code']) ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-6"><label class="form-label">Exam Date</label><input type="date" name="exam_date" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Room</label><input type="text" name="exam_room" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select></div>
                        <div class="col-md-12"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?= date('Y').'-'.(date('Y')+1) ?>"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Exam</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Profile Modal -->
    <div class="modal fade" id="studentProfileModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-user-graduate me-2"></i>Student Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentProfileBody"><div class="text-center py-4"><em>Loading...</em></div></div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" onclick="printStudentProfile()"><i class="fas fa-print"></i> Print</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enter Marks Modal -->
    <div class="modal fade" id="enterMarksModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="enter_marks">
                <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Enter Exam Marks</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Exam</label><select name="exam_number" class="form-select" required>
                            <option value="">Select Exam</option>
                            <?php foreach($exams as $e): ?><option value="<?= htmlspecialchars($e['exam_number']) ?>"><?= htmlspecialchars($e['exam_number']) ?> – <?= htmlspecialchars($e['course_code']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-4"><label class="form-label">Student</label><select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php if($students_conn){$r=$students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");if($r)while($row=$r->fetch_assoc()):?><option value="<?=$row['id']?>"><?=htmlspecialchars($row['full_name']?:$row['student_number'])?></option><?php endwhile;} ?>
                        </select></div>
                        <div class="col-md-4"><label class="form-label">Marks</label><input type="number" step="0.1" name="marks" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Grade</label><select name="grade" class="form-select"><option>A</option><option>B</option><option>C</option><option>D</option><option>F</option></select></div>
                        <div class="col-md-6"><label class="form-label">Course Code</label><input type="text" name="course_code" class="form-control" required></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Marks</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function openExamModal(){ new bootstrap.Modal(document.getElementById('createExamModal')).show(); }
    function openEnterMarksModal(){ new bootstrap.Modal(document.getElementById('enterMarksModal')).show(); }

    function viewStudentProfile(id) {
        const modal = new bootstrap.Modal(document.getElementById('studentProfileModal'));
        document.getElementById('studentProfileBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading...</p></div>';
        modal.show();
        fetch('director-academics.php?ajax=student_profile&student_id='+id)
            .then(r=>r.json()).then(d=>{
                let info = d.info || {};
                let inv = d.invoices || []; let pay = d.payments || []; let att = d.attendance || [];
                let docs = d.documents || []; let tPaid = pay.reduce((s,p)=>s+parseFloat(p.amount_received||0),0);
                let tInv = inv.reduce((s,iv)=>s+parseFloat(iv.total_amount||0),0);
                let pres = att.filter(a=>a.status==='Present').length;
                let rate = att.length ? Math.round(pres/att.length*100) : 0;
                let h = `<ul class="nav nav-tabs mb-3" id="pTabs"><li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#pPers">Personal</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pAcad">Academic</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pFin">Finance</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pDoc">Documents</a></li></ul>
                    <div class="tab-content">
                    <div class="tab-pane fade show active" id="pPers"><div class="row g-2 small">
                        <div class="col-md-6"><strong>Name:</strong> ${info.full_name||''}</div>
                        <div class="col-md-6"><strong>Reg No:</strong> ${info.registration_number||info.student_number||'-'}</div>
                        <div class="col-md-6"><strong>Phone:</strong> ${info.phone||'-'}</div>
                        <div class="col-md-6"><strong>Email:</strong> ${info.email||'-'}</div>
                        <div class="col-md-6"><strong>Gender:</strong> ${info.gender||'-'}</div>
                        <div class="col-md-6"><strong>DOB:</strong> ${info.date_of_birth||'-'}</div>
                        <div class="col-md-6"><strong>National ID:</strong> ${info.national_student_id_number||'-'}</div>
                        <div class="col-md-6"><strong>Guardian:</strong> ${info.guardian_name||info.parent_name||'-'}</div>
                        <div class="col-md-6"><strong>Guardian Phone:</strong> ${info.guardian_phone||info.parent_phone||'-'}</div>
                    </div></div>
                    <div class="tab-pane fade" id="pAcad"><div class="row g-2 small">
                        <div class="col-md-4"><strong>Program:</strong> ${info.course||'-'}</div>
                        <div class="col-md-4"><strong>Year:</strong> ${info.current_year||'-'}</div>
                        <div class="col-md-4"><strong>Semester:</strong> ${info.current_semester||'-'}</div>
                        <div class="col-md-4"><strong>Set:</strong> ${info.set_name||'-'}</div>
                        <div class="col-md-4"><strong>Intake:</strong> ${info.intake_date||'-'}</div>
                        <div class="col-12 mt-2"><strong>Attendance:</strong> ${rate}% (${pres}/${att.length})</div>
                    </div></div>
                    <div class="tab-pane fade" id="pFin"><div class="row g-2 small">
                        <div class="col-md-4"><strong>Invoiced:</strong> ${tInv.toLocaleString()}</div>
                        <div class="col-md-4"><strong>Paid:</strong> ${tPaid.toLocaleString()}</div>
                        <div class="col-md-4"><strong>Balance:</strong> ${(tInv-tPaid).toLocaleString()}</div>
                        <div class="col-12 mt-2"><button class="btn btn-sm btn-outline-primary" onclick="window.open('director-academics.php?report=fee_statement&student_id=${id}','_blank')"><i class="fas fa-file-invoice"></i> Full Statement</button></div>
                    </div></div>
                    <div class="tab-pane fade" id="pDoc"><div class="small">${docs.length ? docs.map(d=>'<div class="mb-1">'+(d.file_path?'<a href="../'+d.file_path+'" target="_blank">'+d.document_title+'</a>':'<span>'+d.document_title+'</span>')+' <small class="text-muted">('+d.document_type+')</small></div>').join('') : '<p class="text-muted">No documents.</p>'}</div></div>
                    </div>`;
                document.getElementById('studentProfileBody').innerHTML = h;
                setTimeout(()=>{ document.querySelectorAll('#pTabs a').forEach(t=>{ t.addEventListener('click',e=>{ e.preventDefault(); new bootstrap.Tab(t).show(); }); }); }, 100);
            }).catch(function(){ document.getElementById('studentProfileBody').innerHTML = '<div class="alert alert-danger text-center m-3">Failed to load profile.</div>'; });
    }

    function printStudentProfile(){
        const c = document.getElementById('studentProfileBody').innerHTML;
        const w = window.open('','_blank');
        w.document.write('<!DOCTYPE html><html><head><title>Student Profile</title><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ddd;padding:6px 8px}th{background:#f3f4f6}h2{color:#1f2937}@media print{body{print-color-adjust:exact}}</style></head><body><h2>Student Profile</h2>'+c+'<script>window.onload=function(){window.print()}<\/script></body></html>');
        w.document.close();
    }

    function filterStudentTable(){
        const q = document.getElementById('studentSearch')?.value?.toLowerCase()||'';
        document.querySelectorAll('#studentTable tbody tr').forEach(r=>{ r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
    }

    function viewProgramCourses(code) {
        window.open('director-academics.php?report=program_courses&program_code='+encodeURIComponent(code),'_blank');
    }

    function viewEnrolledStudents(prog) {
        window.open('director-academics.php?report=program_enrollment&program='+encodeURIComponent(prog),'_blank');
    }

    function feeStatement(id) {
        window.open('director-academics.php?report=fee_statement&student_id='+id,'_blank');
    }

    function generateTranscript(id, name) {
        if(!confirm('Generate transcript for '+name+'?')) return;
        const f = document.createElement('form'); f.method='POST'; f.action='director-academics.php';
        f.innerHTML = '<input name="action" value="transcript_request"><input name="student_id" value="'+id+'"><input name="document_title" value="Transcript - '+name+'">';
        document.body.appendChild(f); f.submit();
    }
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
