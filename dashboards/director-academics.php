<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/dashboard_components.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';
require_once __DIR__ . '/../includes/website_submissions_widget.php';

$ctx = bootstrapStaffDashboard(['director academics', 'director general', 'ceo', 'principal']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Director of Academics';
$website_conn = $ctx['website'];
$students_conn = $ctx['students'] ?? null;
$user_id = (int)($user['id'] ?? 0);

$profileImageUrl = '../images/username.png';
$profileSettingsFile = __DIR__ . '/../includes/profile_settings.php';
if (file_exists($profileSettingsFile)) {
    include_once $profileSettingsFile;
    if (function_exists('getStaffProfileImageUrl')) {
        $url = getStaffProfileImageUrl($user_id);
        if ($url) $profileImageUrl = $url;
    }
}

function sc($c, $s) { if (!$c) return 0; $r=$c->query($s); if(!$r)return 0; $w=$r->fetch_assoc(); return intval($w['c']??0); }

$acadPageMap = ['home'=>'overview','overview'=>'overview','analytics'=>'overview','programs'=>'overview','courses'=>'overview','exams'=>'overview','timetable'=>'overview','clinical'=>'overview','attendance'=>'overview','quality'=>'overview','academic_records'=>'overview','reports'=>'overview','approvals'=>'overview','tasks'=>'overview','schedules'=>'overview','reports-daily'=>'overview','reports-monthly'=>'overview','reports-annual'=>'overview','exports'=>'overview','print'=>'overview','notifications'=>'overview','messages'=>'overview','announcements'=>'overview','profile'=>'overview','preferences'=>'overview','security'=>'overview','activity-logs'=>'overview'];
$p = $_GET['page'] ?? '';
if ($p && !isset($_GET['section'])) $_GET['section'] = $acadPageMap[$p] ?? $p;
$section = $_GET['section'] ?? 'overview';

// â”€â”€ Executive Stats â”€â”€
$total_students    = $students_conn ? sc($students_conn,"SELECT COUNT(*)c FROM students") : 0;
$active_students   = $students_conn ? sc($students_conn,"SELECT COUNT(*)c FROM students WHERE status='Active'") : 0;
$total_lecturers   = sc($conn,"SELECT COUNT(*)c FROM staff WHERE position LIKE '%Lecturer%' OR position LIKE '%lecturer%'");
$active_courses    = sc($conn,"SELECT COUNT(*)c FROM academic_course_catalog WHERE status='Active'");
$active_programs   = sc($conn,"SELECT COUNT(*)c FROM academic_programs WHERE status='Active'");
$total_exams       = sc($conn,"SELECT COUNT(*)c FROM examination_records");
$published_exams   = sc($conn,"SELECT COUNT(*)c FROM examination_records WHERE status='Published'");
$pending_approvals = sc($conn,"SELECT COUNT(*)c FROM approval_requests WHERE status='Active'");
$curr_year = date('Y');

$avg_gpa = 0; $r=$conn->query("SELECT ROUND(AVG(gpa),2) avg FROM academic_records WHERE assessment_type='Exam'");
if($r){ $row=$r->fetch_assoc(); $avg_gpa=round((float)($row['avg']??0),2); }

$avg_attendance = 0;
if($students_conn){ $r=$students_conn->query("SELECT ROUND(AVG(rate)*100,1) avg FROM (SELECT COUNT(*) total,SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END)/COUNT(*) rate FROM student_attendance GROUP BY student_id) t"); if($r){$w=$r->fetch_assoc();$avg_attendance=round((float)($w['avg']??0),1);} }

// â”€â”€ Master Data â”€â”€
$programs = []; $r=$conn->query("SELECT * FROM academic_programs ORDER BY program_name");
if($r) while($row=$r->fetch_assoc()) $programs[]=$row;

$courses_catalog = []; $r=$conn->query("SELECT cc.*,p.program_name FROM academic_course_catalog cc LEFT JOIN academic_programs p ON cc.program_code=p.program_code WHERE cc.status='Active' ORDER BY cc.course_title");
if($r) while($row=$r->fetch_assoc()) $courses_catalog[]=$row;

$exams = []; $r=$conn->query("SELECT * FROM examination_records ORDER BY exam_date DESC LIMIT 30");
if($r) while($row=$r->fetch_assoc()) $exams[]=$row;

$lecturers = []; $r=$conn->query("SELECT id,full_name,position,department,email,phone FROM staff WHERE position LIKE '%Lecturer%' OR position LIKE '%lecturer%' OR position LIKE '%Head%' ORDER BY full_name");
if($r) while($row=$r->fetch_assoc()) $lecturers[]=$row;

$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';

$course_assignments = []; $r=$conn->query("SELECT ca.*,s.full_name lecturer_name,cc.course_title FROM course_assignments ca LEFT JOIN staff s ON ca.lecturer_id=s.id LEFT JOIN academic_course_catalog cc ON ca.course_code=cc.course_code ORDER BY s.full_name");
if($r) while($row=$r->fetch_assoc()) $course_assignments[]=$row;

$timetable = []; $r=$conn->query("SELECT t.*,s.full_name lecturer_name FROM timetable t LEFT JOIN staff s ON t.lecturer_id=s.id ORDER BY t.day_of_week,t.start_time");
if($r) while($row=$r->fetch_assoc()) $timetable[]=$row;

$clinical = []; $r=$conn->query("SELECT ct.*,s.full_name,st.full_name student_name FROM clinical_training ct LEFT JOIN staff s ON ct.supervisor_id=s.id LEFT JOIN $students_db.students st ON ct.student_id=st.id ORDER BY ct.start_date DESC LIMIT 50");
if($r) while($row=$r->fetch_assoc()) $clinical[]=$row;

$attendance = []; if($students_conn){ $r=$students_conn->query("SELECT a.*,s.full_name,s.program FROM student_attendance a LEFT JOIN students s ON a.student_id=s.id ORDER BY a.date DESC LIMIT 50"); if($r) while($row=$r->fetch_assoc()) $attendance[]=$row; }

$quality = []; $r=$conn->query("SELECT qa.*,s.full_name reviewer_name FROM quality_assurance qa LEFT JOIN staff s ON qa.reviewed_by=s.id ORDER BY qa.review_date DESC LIMIT 20");
if($r) while($row=$r->fetch_assoc()) $quality[]=$row;

$academic_records = []; $r=$conn->query("SELECT ar.*,s.full_name student_name,cc.course_title FROM academic_records ar LEFT JOIN $students_db.students s ON ar.student_id=s.id LEFT JOIN academic_course_catalog cc ON ar.course_code=cc.course_code ORDER BY ar.id DESC LIMIT 100");
if($r) while($row=$r->fetch_assoc()) $academic_records[]=$row;

$result_approvals = []; $r=$conn->query("SELECT ra.*,s.full_name approved_by_name FROM result_approvals ra LEFT JOIN staff s ON ra.approved_by=s.id ORDER BY ra.approval_date DESC LIMIT 30");
if($r) while($row=$r->fetch_assoc()) $result_approvals[]=$row;

$recent_activities = []; $r=$conn->query("SELECT activity_description activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
if($r) while($row=$r->fetch_assoc()) $recent_activities[]=$row;

$enrollment_by_prog = []; if($students_conn){ $r=$students_conn->query("SELECT program,COUNT(*)c FROM students WHERE status='Active' GROUP BY program ORDER BY c DESC"); if($r) while($row=$r->fetch_assoc()) $enrollment_by_prog[]=$row; }

$user_role_id = 0; $ri = $conn->query("SELECT role_id FROM staff WHERE id = ".intval($user_id)); if ($ri) { $user_role_id = (int)$ri->fetch_assoc()['role_id']; }

// â”€â”€ Program Enrollment Stats â”€â”€
function enrollmentStats($conn, $students_conn, $program_name) {
  if (!$students_conn) return 0;
  $stmt = $students_conn->prepare("SELECT COUNT(*)c FROM students WHERE program=? AND status='Active'");
  if (!$stmt) return 0;
  $stmt->bind_param("s", $program_name);
  if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
  $r = $stmt->get_result();
  $row = $r->fetch_assoc();
  $stmt->close();
  return $row ? intval($row['c']) : 0;
}

// â”€â”€ Report generation â”€â”€
$report = $_GET['report'] ?? '';
if ($report) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px}th{background:#f3f4f6}h2{color:#1f2937}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print"><button class="btn btn-primary btn-sm" onclick="window.print()" style="margin-bottom:12px">Print</button> <button class="btn btn-secondary btn-sm" onclick="window.close()">Close</button></div>';
    if ($report === 'student_progress') {
        echo '<h2>Student Progress Report</h2>';
        $r=$students_conn->query("SELECT s.full_name,s.student_number,s.program,s.level,COUNT(ar.id)exams,ROUND(AVG(ar.marks),1)avg_marks FROM students s LEFT JOIN staffs_db.academic_records ar ON s.id=ar.student_id WHERE s.status='Active' GROUP BY s.id ORDER BY s.full_name LIMIT 100");
        echo '<table><thead><tr><th>Name</th><th>Reg No</th><th>Program</th><th>Year</th><th>Exams</th><th>Avg Marks</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['student_number']).'</td><td>'.htmlspecialchars($row['program']).'</td><td>'.$row['level'].'</td><td>'.($row['exams']??0).'</td><td>'.round($row['avg_marks']??0,1).'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'attendance_report') {
        echo '<h2>Attendance Report</h2>';
        if($students_conn){ $r=$students_conn->query("SELECT s.full_name,s.student_number,s.program,COUNT(a.id)total,SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END)present FROM students s LEFT JOIN student_attendance a ON s.id=a.student_id WHERE s.status='Active' GROUP BY s.id ORDER BY s.full_name LIMIT 100");
        echo '<table><thead><tr><th>Name</th><th>Reg No</th><th>Program</th><th>Total</th><th>Present</th><th>Rate</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ $rt=$row['total']>0?round(($row['present']/$row['total'])*100,1).'%':'-'; echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['student_number']).'</td><td>'.htmlspecialchars($row['program']).'</td><td>'.$row['total'].'</td><td>'.$row['present'].'</td><td>'.$rt.'</td></tr>'; }
        echo '</tbody></table>'; }
    } elseif ($report === 'graduation') {
        echo '<h2>Graduation Report</h2>';
        $r=$students_conn->query("SELECT program,COUNT(*)total FROM students WHERE status IN('Graduated','graduation_candidate') GROUP BY program");
        echo '<table><thead><tr><th>Program</th><th>Graduating</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['program']).'</td><td>'.$row['total'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'academic_performance') {
        echo '<h2>Academic Performance</h2>';
        $r=$conn->query("SELECT course_code,COUNT(*)total,SUM(CASE WHEN grade IN('A','B','C','D') THEN 1 ELSE 0 END)passed,ROUND(AVG(marks),1)avg_marks,ROUND(AVG(gpa),2)avg_gpa FROM academic_records WHERE assessment_type='Exam' GROUP BY course_code");
        echo '<table><thead><tr><th>Course</th><th>Students</th><th>Passed</th><th>Pass Rate</th><th>Avg Marks</th><th>Avg GPA</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ $pr=$row['total']>0?round(($row['passed']/$row['total'])*100,1).'%':'-'; echo '<tr><td>'.htmlspecialchars($row['course_code']).'</td><td>'.$row['total'].'</td><td>'.$row['passed'].'</td><td>'.$pr.'</td><td>'.round($row['avg_marks']??0,1).'</td><td>'.round($row['avg_gpa']??0,2).'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'program_courses') {
        $pc = $_GET['program_code']??'';
        echo '<h2>Courses for: '.htmlspecialchars($pc).'</h2>';
        $stmt = $conn->prepare("SELECT cc.*,p.program_name FROM academic_course_catalog cc LEFT JOIN academic_programs p ON cc.program_code=p.program_code WHERE cc.program_code=? ORDER BY cc.year_of_study,cc.semester,cc.course_code");
        if (!$stmt) { echo '<div class="alert alert-warning">Query failed</div>'; } else {
        $stmt->bind_param("s", $pc);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $r = $stmt->get_result();
        echo '<table><thead><tr><th>Code</th><th>Title</th><th>Year</th><th>Semester</th><th>Credits</th><th>Status</th></tr></thead><tbody>';
        if($r && $r->num_rows>0) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['course_code']).'</td><td>'.htmlspecialchars($row['course_title']).'</td><td>'.$row['year_of_study'].'</td><td>'.$row['semester'].'</td><td>'.$row['credits'].'</td><td>'.htmlspecialchars($row['status']).'</td></tr>'; }
        else echo '<tr><td colspan="6" class="text-center text-muted">No courses found.</td></tr>';
        echo '</tbody></table>';
        $stmt->close(); }
    } elseif ($report === 'program_enrollment') {
        $fp = $_GET['program'] ?? '';
        echo '<h2>Program Enrollment Report</h2>';
        if($fp){
            echo '<p><strong>Filtered:</strong> '.htmlspecialchars($fp).'</p>';
            $stmt = $students_conn->prepare("SELECT program,COUNT(*)total,SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END)active FROM students WHERE program=? GROUP BY program");
            if (!$stmt) { echo '<div class="alert alert-warning">Query failed</div>'; $r = null; } else {
            $stmt->bind_param("s", $fp);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $r = $stmt->get_result(); }
        }
        else { $r=$students_conn->query("SELECT program,COUNT(*)total,SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END)active FROM students GROUP BY program"); }
        echo '<table><thead><tr><th>Program</th><th>Total</th><th>Active</th><th>Inactive</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ $in=$row['total']-$row['active']; echo '<tr><td>'.htmlspecialchars($row['program']).'</td><td>'.$row['total'].'</td><td>'.$row['active'].'</td><td>'.$in.'</td></tr>'; }
        echo '</tbody></table>';
        if($fp && isset($stmt) && is_object($stmt)) $stmt->close();
    } elseif ($report === 'lecturer_workload') {
        echo '<h2>Lecturer Workload</h2>';
        $r=$conn->query("SELECT s.full_name,s.position,s.department,COUNT(ca.id)courses_assigned FROM staff s LEFT JOIN course_assignments ca ON s.id=ca.lecturer_id WHERE s.position LIKE '%Lecturer%' OR s.position LIKE '%lecturer%' GROUP BY s.id ORDER BY courses_assigned DESC");
        echo '<table><thead><tr><th>Lecturer</th><th>Position</th><th>Department</th><th>Courses</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['position']).'</td><td>'.htmlspecialchars($row['department']??'-').'</td><td>'.$row['courses_assigned'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'exam_timetable') {
        echo '<h2>Examination Timetable</h2>';
        $r=$conn->query("SELECT e.*,cc.course_title FROM examination_records e LEFT JOIN academic_course_catalog cc ON e.course_code=cc.course_code ORDER BY e.exam_date");
        echo '<table><thead><tr><th>Date</th><th>Exam No</th><th>Course</th><th>Type</th><th>Room</th><th>Status</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.$row['exam_date'].'</td><td>'.$row['exam_number'].'</td><td>'.htmlspecialchars($row['course_code']).(!empty($row['course_title'])?' - '.htmlspecialchars($row['course_title']):'').'</td><td>'.$row['exam_type'].'</td><td>'.$row['exam_room'].'</td><td>'.$row['status'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'enrollment_summary') {
        echo '<h2>Enrollment Summary</h2>';
        $r=$students_conn->query("SELECT program,COUNT(*)total,SUM(CASE WHEN gender='Male' THEN 1 ELSE 0 END)males,SUM(CASE WHEN gender='Female' THEN 1 ELSE 0 END)females,SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END)active FROM students GROUP BY program");
        echo '<table><thead><tr><th>Program</th><th>Total</th><th>Male</th><th>Female</th><th>Active</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['program']).'</td><td>'.$row['total'].'</td><td>'.$row['males'].'</td><td>'.$row['females'].'</td><td>'.$row['active'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'student_detail') {
        $sid = intval($_GET['student_id']??0);
        echo '<h2>Student Detail Report</h2>';
        if($sid && $students_conn){ $qs=$students_conn->query("SELECT * FROM students WHERE id=".intval($sid)); $s=$qs?$qs->fetch_assoc():null; if($s){ echo '<table>'; foreach($s as $k=>$v){ echo '<tr><td><strong>'.ucwords(str_replace('_',' ',$k)).':</strong></td><td>'.htmlspecialchars($v??'-').'</td></tr>'; } echo '</table>'; } }
    }
    echo '</body></html>'; exit;
}

// â”€â”€ AJAX â”€â”€
$ajax = $_GET['ajax'] ?? '';
$ajaxSid = intval($_GET['student_id'] ?? 0);
$ajaxAction = $_GET['action'] ?? '';
if ($ajax && $ajaxSid > 0) {
    header('Content-Type: application/json');
    if ($ajax === 'student_profile') {
        $info=[];$r=$students_conn->query("SELECT * FROM students WHERE id=".intval($ajaxSid)); if($r)$info=$r->fetch_assoc();
        $att=[];if($students_conn){$r=$students_conn->query("SELECT date,status FROM student_attendance WHERE student_id=".intval($ajaxSid)." ORDER BY date DESC LIMIT 30");if($r)while($row=$r->fetch_assoc())$att[]=$row;}
        $ar=[];$r=$conn->query("SELECT ar.course_code,ar.assessment_type,ar.marks,ar.grade,ar.gpa,cc.course_title FROM academic_records ar LEFT JOIN academic_course_catalog cc ON ar.course_code=cc.course_code WHERE ar.student_id=".intval($ajaxSid)." ORDER BY ar.id DESC");if($r)while($row=$r->fetch_assoc())$ar[]=$row;
        $docs=[];$r=$conn->query("SELECT id,document_type,document_title,file_path,generation_date FROM generated_documents WHERE student_id=".intval($ajaxSid));if($r)while($row=$r->fetch_assoc())$docs[]=$row;
        echo json_encode(['info'=>$info,'attendance'=>$att,'academic_records'=>$ar,'documents'=>$docs]); exit;
    }
    echo json_encode([]); exit;
}

// â”€â”€ POST â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Safe prepared statement helper
    function safe_prepare_execute($conn, $sql, $types, $params) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) { $_SESSION['error'] = 'Database error: ' . $conn->error; return false; }
        if ($types && $params) $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $ok = $conn->affected_rows >= 0;
        if (!$ok) $_SESSION['error'] = $conn->error;
        $stmt->close();
        return $ok;
    }

    if ($action === 'create_exam') {
        $cc=$_POST['course_code']??'';
        $et=$_POST['exam_type']??'';
        $pc=$_POST['program_code']??'';
        $sd=$_POST['exam_date']??'';
        $rm=$_POST['exam_room']??'';
        $sem=$_POST['semester']??'Semester 1';
        $ay=$_POST['academic_year']??date('Y').'-'.(date('Y')+1);
        $en='EXM-'.date('Ymd').'-'.mt_rand(1000,9999);
        if(safe_prepare_execute($conn,"INSERT INTO examination_records (exam_number,exam_type,course_code,program_code,exam_date,exam_room,semester,academic_year,status,created_by) VALUES (?,?,?,?,?,?,?,?,'Scheduled',?)","ssssssssi",[$en,$et,$cc,$pc,$sd,$rm,$sem,$ay,$user_id]))
            $_SESSION['success']="Exam $en created.";
        header("Location: director-academics.php?section=$section"); exit;
    }

    if ($action === 'publish_results') {
        $en=$_POST['exam_number']??'';
        if(safe_prepare_execute($conn,"UPDATE examination_records SET status='Published' WHERE exam_number=?","s",[$en]))
            $_SESSION['success']="Results published for $en.";
        header("Location: director-academics.php?section=$section"); exit;
    }

    if ($action === 'enter_marks') {
        $en=$_POST['exam_number']??'';
        $sid=intval($_POST['student_id']??0);
        $mk=floatval($_POST['marks']??0);
        $gr=$_POST['grade']??'';
        $cc=$_POST['course_code']??'';
        $cn=$_POST['course_name']??'';
        $gp=floatval($_POST['gpa']??0);
        if($en && $sid){
            if(safe_prepare_execute($conn,"INSERT INTO academic_records (student_id,course_code,course_name,assessment_type,marks,grade,gpa,graded_by) VALUES (?,?,?,'Exam',?,?,?,?)","issdsdi",[$sid,$cc,$cn,$mk,$gr,$gp,$user_id]))
                $_SESSION['success']='Marks entered.';
        }
        else { $_SESSION['error']='Exam and student required.'; }
        header("Location: director-academics.php?section=$section"); exit;
    }

    if ($action === 'transcript_request') {
        $sid=intval($_POST['student_id']??0);
        $dn=$_POST['document_title']??'Transcript';
        $dt=$_POST['document_type']??'Transcript';
        if($sid){
            if(safe_prepare_execute($conn,"INSERT INTO generated_documents (document_type,student_id,generated_by,document_title,file_path) VALUES (?,?,?,?,'')","sisi",[$dt,$sid,$user_id,$dn]))
                $_SESSION['success']='Document generated.';
        }
        header("Location: director-academics.php?section=$section"); exit;
    }

    if ($action === 'approve_result') {
        $en=$_POST['exam_number']??'';
        $stat=$_POST['approval_status']??'Approved';
        $cmt=$_POST['comments']??'';
        if(safe_prepare_execute($conn,"INSERT INTO result_approvals (exam_number,status,comments,approved_by,approval_date) VALUES (?,?,?,?," . "NOW())","sssi",[$en,$stat,$cmt,$user_id]))
            $_SESSION['success']="Result $stat for $en.";
        header("Location: director-academics.php?section=$section"); exit;
    }

    if ($action === 'create_quality_review') {
        $title=$_POST['review_title']??'';
        $dept=$_POST['department']??'';
        $area=$_POST['review_area']??'';
        $finding=$_POST['findings']??'';
        $rec=$_POST['recommendations']??'';
        $stat=$_POST['status']??'Open';
        if(safe_prepare_execute($conn,"INSERT INTO quality_assurance (review_title,department,review_area,findings,recommendations,status,reviewed_by,review_date) VALUES (?,?,?,?,?,?,?,NOW())","ssssssi",[$title,$dept,$area,$finding,$rec,$stat,$user_id]))
            $_SESSION['success']='Quality review created.';
        header("Location: director-academics.php?section=$section"); exit;
    }

    if ($action === 'assign_lecturer') {
        $lid=intval($_POST['lecturer_id']??0);
        $cc=$_POST['course_code']??'';
        $sem=$_POST['semester']??'Semester 1';
        $ay=$_POST['academic_year']??date('Y').'-'.(date('Y')+1);
        if($lid && $cc){
            if(safe_prepare_execute($conn,"INSERT INTO course_assignments (lecturer_id,course_code,semester,academic_year,assigned_by) VALUES (?,?,?,?,?)","isssi",[$lid,$cc,$sem,$ay,$user_id]))
                $_SESSION['success']='Lecturer assigned.';
        }
        header("Location: director-academics.php?section=$section"); exit;
    }

    if ($action === 'add_timetable') {
        $lid=intval($_POST['lecturer_id']??0);
        $cc=$_POST['course_code']??'';
        $dow=$_POST['day_of_week']??'';
        $st=$_POST['start_time']??'';
        $et=$_POST['end_time']??'';
        $rm=$_POST['room']??'';
        if(safe_prepare_execute($conn,"INSERT INTO timetable (lecturer_id,course_code,day_of_week,start_time,end_time,room) VALUES (?,?,?,?,?,?)","isssss",[$lid,$cc,$dow,$st,$et,$rm]))
            $_SESSION['success']='Timetable entry added.';
        header("Location: director-academics.php?section=$section"); exit;
    }

    if ($action === 'record_attendance') {
        $sid=intval($_POST['student_id']??0);
        $dt=$_POST['date']??date('Y-m-d');
        $st=$_POST['status']??'Present';
        if($sid && $students_conn){
            if(safe_prepare_execute($students_conn,"INSERT INTO student_attendance (student_id,date,status) VALUES (?,?,?)","iss",[$sid,$dt,$st]))
                $_SESSION['success']='Attendance recorded.';
        }
        header("Location: director-academics.php?section=$section"); exit;
    }

    if ($action === 'edit_exam') {
        $en=$_POST['exam_number']??'';
        $et=$_POST['exam_type']??'';
        $cc=$_POST['course_code']??'';
        $sd=$_POST['exam_date']??'';
        $rm=$_POST['exam_room']??'';
        if($en){
            if(safe_prepare_execute($conn,"UPDATE examination_records SET exam_type=?,course_code=?,exam_date=?,exam_room=? WHERE exam_number=?","sssss",[$et,$cc,$sd,$rm,$en]))
                $_SESSION['success']="Exam $en updated.";
        }
        header("Location: director-academics.php?section=exams"); exit;
    }

    if ($action === 'delete_exam') {
        $en=$_POST['exam_number']??'';
        if($en){
            if(safe_prepare_execute($conn,"DELETE FROM examination_records WHERE exam_number=?","s",[$en]))
                $_SESSION['success']="Exam $en deleted.";
        }
        header("Location: director-academics.php?section=exams"); exit;
    }

    if ($action === 'edit_marks') {
        $rid=intval($_POST['record_id']??0);
        $mk=floatval($_POST['marks']??0);
        $gr=$_POST['grade']??'';
        $gp=floatval($_POST['gpa']??0);
        if($rid){
            if(safe_prepare_execute($conn,"UPDATE academic_records SET marks=?,grade=?,gpa=? WHERE id=?","dsdi",[$mk,$gr,$gp,$rid]))
                $_SESSION['success']='Marks updated.';
        }
        header("Location: director-academics.php?section=results"); exit;
    }

    if ($action === 'delete_marks') {
        $rid=intval($_POST['record_id']??0);
        if($rid){
            if(safe_prepare_execute($conn,"DELETE FROM academic_records WHERE id=?","i",[$rid]))
                $_SESSION['success']='Record deleted.';
        }
        header("Location: director-academics.php?section=results"); exit;
    }

    if ($action === 'delete_course_assignment') {
        $aid=intval($_POST['assignment_id']??0);
        if($aid){
            if(safe_prepare_execute($conn,"DELETE FROM course_assignments WHERE id=?","i",[$aid]))
                $_SESSION['success']='Assignment removed.';
        }
        header("Location: director-academics.php?section=lecturers"); exit;
    }

    if ($action === 'delete_timetable') {
        $tid=intval($_POST['timetable_id']??0);
        if($tid){
            if(safe_prepare_execute($conn,"DELETE FROM timetable WHERE id=?","i",[$tid]))
                $_SESSION['success']='Timetable entry removed.';
        }
        header("Location: director-academics.php?section=timetable"); exit;
    }

    if ($action === 'delete_quality_review') {
        $qrid=intval($_POST['review_id']??0);
        if($qrid){
            if(safe_prepare_execute($conn,"DELETE FROM quality_assurance WHERE id=?","i",[$qrid]))
                $_SESSION['success']='Review deleted.';
        }
        header("Location: director-academics.php?section=quality"); exit;
    }

    header("Location: director-academics.php?section=$section"); exit;
}

// â”€â”€ Section Nav Builder â”€â”€
$navSections = [
  ['id'=>'overview','icon'=>'fa-tachometer-alt','label'=>'Overview'],
  ['id'=>'analytics','icon'=>'fa-chart-pie','label'=>'Analytics'],
  ['id'=>'programs','icon'=>'fa-book','label'=>'Programs'],
  ['id'=>'courses','icon'=>'fa-layer-group','label'=>'Courses'],
  ['id'=>'enrollment','icon'=>'fa-users','label'=>'Enrollment'],
  ['id'=>'curriculum','icon'=>'fa-sitemap','label'=>'Curriculum'],
  ['id'=>'exams','icon'=>'fa-clipboard-list','label'=>'Exams'],
  ['id'=>'results','icon'=>'fa-star','label'=>'Results'],
  ['id'=>'approvals','icon'=>'fa-check-double','label'=>'Approvals'],
  ['id'=>'transcripts','icon'=>'fa-file-alt','label'=>'Transcripts'],
  ['id'=>'attendance','icon'=>'fa-calendar-check','label'=>'Attendance'],
  ['id'=>'clinical','icon'=>'fa-heartbeat','label'=>'Clinical'],
  ['id'=>'lecturers','icon'=>'fa-chalkboard-teacher','label'=>'Lecturers'],
  ['id'=>'timetable','icon'=>'fa-clock','label'=>'Timetable'],
  ['id'=>'quality','icon'=>'fa-shield-alt','label'=>'Quality'],
  ['id'=>'reports','icon'=>'fa-chart-bar','label'=>'Reports'],
  ['id'=>'duties','icon'=>'fa-tasks','label'=>'Duties'],
  ['id'=>'activity','icon'=>'fa-history','label'=>'Activity'],
  ['id'=>'submissions','icon'=>'fa-globe','label'=>'Website Apps'],
];
function navItem($id,$icon,$label,$section){$act=$section===$id?'active':'';return "<a href=\"?section=$id\" class=\"acad-nav-item $act\" data-section=\"$id\"><i class=\"fas $icon\"></i><span>$label</span></a>";}
$acadIcon = 'fa-graduation-cap';
$acadRole = 'Director Academics';
$acadSubtitle = 'Academic Programs Oversight â€“ Curriculum, Exams & Quality Assurance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root {
  --acad-primary: #1a237e;
  --acad-accent: #3949ab;
  --acad-card-bg: #ffffff;
  --acad-text: #0f172a;
  --acad-text-muted: #64748b;
  --acad-border: #e2e8f0;
  --acad-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --acad-shadow-lg: 0 10px 40px rgba(0,0,0,0.08);
  --acad-gradient: linear-gradient(135deg, #1a237e 0%, #283593 50%, #1a237e 100%);
}
body { background: #eef1f5; font-family: 'Inter', -apple-system, sans-serif; color: var(--acad-text); font-size: 13px; overflow-x: hidden; }
body::before { content:''; position:fixed; inset:0; background:radial-gradient(ellipse at 20% 50%,rgba(99,102,241,0.03) 0%,transparent 50%),radial-gradient(ellipse at 80% 20%,rgba(5,150,105,0.02) 0%,transparent 50%); pointer-events:none; z-index:0; }
.page-content { padding: 0 !important; }

/* â”€â”€ Top Bar â”€â”€ */

@media (max-width: 768px) {
    .acad-content { margin-left: 0; padding: 12px; }
    .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .kpi-card { padding: 10px 10px 8px; }
    .kpi-value { font-size: 15px; }
    .section-card { padding: 12px 14px; }
    .section-title { font-size: 13px; }
    .acad-table { font-size: 11px; }
    .acad-table thead th { font-size: 9px; padding: 6px 8px; }
    .acad-table td { padding: 6px 8px; }
    .d-flex.justify-content-between { flex-wrap: wrap; gap: 6px; }
    .modal-dialog { margin: 8px !important; }
}
@media (max-width: 480px) {
    .kpi-grid { grid-template-columns: 1fr 1fr; gap: 6px; }
    .kpi-card { padding: 8px; }
    .kpi-value { font-size: 13px; }
    .kpi-label { font-size: 9px; }
    .section-card { padding: 10px 12px; }
}










/* â”€â”€ Content â”€â”€ */
.acad-content { padding: 18px 22px 30px; max-width: 1600px; margin: 0 0 0 270px; background: #fafbfc; min-height: calc(100vh - 60px); overflow-x: hidden; word-break: break-word; }
@media (max-width: 768px) { .acad-content { margin-left: 0; } }

/* â”€â”€ KPI Cards â”€â”€ */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 14px; }
@media (max-width: 1200px) { .kpi-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 900px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 500px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
.kpi-card {
  background: var(--acad-card-bg);
  border-radius: 10px;
  padding: 12px 14px 10px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  transition: all 0.25s ease;
  border-left: 4px solid transparent;
  position: relative;
  overflow: hidden;
  cursor: default;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.kpi-icon { width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; margin-bottom: 5px; }
.kpi-value { font-size: 18px; font-weight: 800; color: var(--acad-text); letter-spacing: -0.3px; line-height: 1.2; }
.kpi-label { font-size: 10px; font-weight: 700; color: var(--acad-text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.kpi-card .kpi-trend { font-size: 10px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; margin-top: 4px; padding: 2px 8px; border-radius: 10px; }
.kpi-bl { border-left-color: #3949ab; } .kpi-bl .kpi-icon { background: #eef2ff; color: #4338ca; }
.kpi-gr { border-left-color: #10b981; } .kpi-gr .kpi-icon { background: #ecfdf5; color: #059669; }
.kpi-cy { border-left-color: #06b6d4; } .kpi-cy .kpi-icon { background: #ecfeff; color: #0891b2; }
.kpi-rd { border-left-color: #ef4444; } .kpi-rd .kpi-icon { background: #fef2f2; color: #dc2626; }
.kpi-or { border-left-color: #f59e0b; } .kpi-or .kpi-icon { background: #fffbeb; color: #d97706; }
.kpi-pr { border-left-color: #7c3aed; } .kpi-pr .kpi-icon { background: #f5f3ff; color: #7c3aed; }

/* â”€â”€ Section Cards â”€â”€ */
.section-card {
  background: var(--acad-card-bg);
  border-radius: 10px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  padding: 16px 18px;
  margin-bottom: 14px;
  transition: box-shadow 0.2s;
  border: 1px solid #f1f5f9;
}
.section-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 6px; }
.section-title { font-size: 14px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; color: var(--acad-text); }
.section-title i { font-size: 15px; }
.section-subtitle { font-size: 11px; color: var(--acad-text-muted); margin: 0; }

/* â”€â”€ Tables â”€â”€ */
.acad-table { font-size: 12px; margin-bottom: 0; }
.acad-table thead th { background: #f8fafc; font-weight: 600; color: var(--acad-text-muted); text-transform: uppercase; font-size: 10px; letter-spacing: 0.4px; padding: 7px 10px; border-bottom: 2px solid var(--acad-border); }
.acad-table td { padding: 7px 10px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.acad-table tbody tr:hover { background: #f8fafc; }
.table-scroll { max-height: 260px; overflow-y: auto; }
.table-scroll::-webkit-scrollbar { width: 4px; }
.table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

/* â”€â”€ Animations â”€â”€ */
@keyframes fadeInUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideIn { from { opacity: 0; transform: translateX(-12px); } to { opacity: 1; transform: translateX(0); } }
.an-fade { animation: fadeInUp 0.5s ease forwards; }
.an-slide { animation: slideIn 0.4s ease forwards; }

/* â”€â”€ Responsive â”€â”€ */
@media (max-width: 1200px) { .kpi-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 992px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
  
  .acad-content { padding: 12px; }
  .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
  .kpi-card { padding: 10px 10px 8px; }
  .kpi-value { font-size: 15px; }
  .section-card { padding: 12px 14px; }
}
@media (max-width: 480px) { .kpi-grid { grid-template-columns: 1fr 1fr; gap: 6px; } }

/* â”€â”€ Print â”€â”€ */
@media print {
  body { background:#fff !important; font-size:10pt; }
  .sidebar, .dashboard-sidebar, .no-print, .btn-print-top, 
  .acad-content { padding:0 !important; margin:0 !important; max-width:100% !important; background:#fff !important; }
  .dashboard-section { display:block !important; }
  .section-card { box-shadow:none !important; border:1px solid #ddd !important; break-inside:avoid; page-break-inside:avoid; }
  .kpi-grid { gap:6px; }
  .kpi-card { box-shadow:none !important; border:1px solid #e2e8f0 !important; break-inside:avoid; }
  .kpi-card .kpi-icon { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .badge { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .an-fade { animation:none !important; opacity:1 !important; }
  .table-scroll { max-height:none !important; overflow:visible !important; }
}

/* â”€â”€ Section visibility â”€â”€ */
.dashboard-section:not(.active) { display:none; }
</style>
</head>
<body class="ent-layout">

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<!-- â•â•â• TOP BAR â•â•â• -->

<div class="acad-content">
<div style="text-align:right;margin-bottom:8px" class="no-print"><button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> Print</button></div>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-sitemap text-info"></i>Your Position in Hierarchy</h3><span class="section-subtitle">Level 3 â€“ Reports to Director General</span></div>
      <?= renderHierarchyChart($conn) ?>
    </div>
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-history text-info"></i>Recent Activities</h3></div>
      <div class="table-scroll">
      <?php if (empty($recent_activities)): ?><div class="text-center py-3 text-muted"><p>No recent activities</p></div>
      <?php else: foreach (array_slice($recent_activities, 0, 6) as $act): ?>
      <div class="py-1 border-bottom small">
        <strong><?= htmlspecialchars($act['activity'] ?? 'Activity') ?></strong>
        <small class="d-block text-muted"><?= date('M j, Y H:i', strtotime($act['created_at'])) ?></small>
      </div>
      <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-chart-bar text-success"></i>Department Performance</h3></div>
      <?php
      $acadStaffId = 0;
      $sq = $conn ? $conn->prepare("SELECT id FROM staff WHERE role_id = 4 AND status = 'Active' LIMIT 1") : false;
      if ($sq) { if (!$sq->execute()) { error_log('$sq execute failed: ' . ($sq->error ?? 'unknown')); }; $sr = $sq->get_result()->fetch_assoc(); $sq->close(); if ($sr) $acadStaffId = $sr['id']; }
      echo renderDirectorPerformanceCard($acadStaffId, 4, 'Director Academics', $conn);
      ?>
    </div>
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-chart-pie text-primary"></i>Performance Metrics</h3></div>
      <?php
      $pass_rate = 0; $r=$conn->query("SELECT COUNT(*)total,SUM(CASE WHEN grade IN('A','B','C','D') THEN 1 ELSE 0 END)passed FROM academic_records WHERE assessment_type='Exam'");
      if($r && $rw=$r->fetch_assoc()){ $pass_rate = $rw['total']>0 ? round(($rw['passed']/$rw['total'])*100,1) : 0; }
      $exam_count = sc($conn,"SELECT COUNT(DISTINCT course_code)c FROM academic_records WHERE assessment_type='Exam'");
      ?>
      <div class="row g-2 text-center mb-2">
        <div class="col-4"><div class="p-2 rounded-3" style="background:#f0fdf4"><div class="fw-bold text-success fs-5"><?= $pass_rate ?>%</div><small class="text-muted">Pass Rate</small></div></div>
        <div class="col-4"><div class="p-2 rounded-3" style="background:#eef2ff"><div class="fw-bold text-primary fs-5"><?= $exam_count ?></div><small class="text-muted">Course Exams</small></div></div>
        <div class="col-4"><div class="p-2 rounded-3" style="background:#fffbeb"><div class="fw-bold text-warning fs-5"><?= $published_exams ?>/<?= $total_exams ?></div><small class="text-muted">Published</small></div></div>
      </div>
    </div>
    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-bell text-danger"></i>Pending Approvals</h3><span class="badge bg-<?= $pending_approvals ? 'danger' : 'success' ?>"><?= $pending_approvals ?: 'All clear' ?></span></div>
      <?php
      $acadApprovals = getPendingApprovals($conn, 4, 5);
      if (!empty($acadApprovals)):
        foreach ($acadApprovals as $apr):
          echo '<div class="py-1 border-bottom small"><strong>' . htmlspecialchars($apr['title']??'Request') . '</strong> <span class="badge bg-warning float-end">' . ($apr['priority']??'Normal') . '</span></div>';
        endforeach;
      else:
        echo '<div class="text-center py-2 text-muted small"><p>No pending approvals</p></div>';
      endif;
      ?>
    </div>
  </div>
</div>

            <!-- â•â•â•â•â•â•â•â•â•â•â• ANALYTICS â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="analytics-section" class="content-section <?= $section==='analytics'?'active':'' ?> dashboard-section" data-section="analytics">
                <h2><i class="fas fa-chart-pie me-2"></i>Academic Analytics</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Enrollment by Program</h6>
                            <?php if(!empty($enrollment_by_prog)): $maxC = max(array_column($enrollment_by_prog,'c')); ?>
                            <div style="display:flex;flex-direction:column;gap:8px">
                                <?php foreach($enrollment_by_prog as $e): $pct = $maxC>0 ? round(($e['c']/$maxC)*100) : 0; ?>
                                <div>
                                    <div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($e['program']) ?></span><strong><?= $e['c'] ?></strong></div>
                                    <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden"><div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#1a237e,#3949ab);border-radius:4px;transition:width .5s"></div></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: echo renderEmptyState('No enrollment data','fas fa-users'); endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Performance Metrics</h6>
                            <?php
                            $pass_rate = 0; $r=$conn->query("SELECT COUNT(*)total,SUM(CASE WHEN grade IN('A','B','C','D') THEN 1 ELSE 0 END)passed FROM academic_records WHERE assessment_type='Exam'");
                            if($r && $rw=$r->fetch_assoc()){ $pass_rate = $rw['total']>0 ? round(($rw['passed']/$rw['total'])*100,1) : 0; }
                            $exam_count = sc($conn,"SELECT COUNT(DISTINCT course_code)c FROM academic_records WHERE assessment_type='Exam'");
                            $top_courses = []; $r=$conn->query("SELECT course_code,COUNT(*)c,ROUND(AVG(marks),1)avg_m FROM academic_records WHERE assessment_type='Exam' GROUP BY course_code ORDER BY avg_m DESC LIMIT 5");
                            if($r) while($row=$r->fetch_assoc()) $top_courses[]=$row;
                            ?>
                            <div class="row g-2 text-center mb-3">
                                <div class="col-4"><div class="p-2 rounded-3" style="background:#f0fdf4"><div class="fw-bold text-success fs-5"><?= $pass_rate ?>%</div><small class="text-muted">Pass Rate</small></div></div>
                                <div class="col-4"><div class="p-2 rounded-3" style="background:#eef2ff"><div class="fw-bold text-primary fs-5"><?= $exam_count ?></div><small class="text-muted">Courses</small></div></div>
                                <div class="col-4"><div class="p-2 rounded-3" style="background:#fffbeb"><div class="fw-bold text-warning fs-5"><?= $published_exams ?>/<?= $total_exams ?></div><small class="text-muted">Published</small></div></div>
                            </div>
                            <?php if(!empty($top_courses)): ?>
                            <h6 class="fw-bold mt-3 mb-2 small">Top Performing Courses</h6>
                            <table class="table table-sm"><thead><tr><th>Course</th><th>Students</th><th>Avg</th></tr></thead><tbody>
                            <?php foreach($top_courses as $tc): ?><tr><td><?= htmlspecialchars($tc['course_code']) ?></td><td><?= $tc['c'] ?></td><td><?= $tc['avg_m'] ?></td></tr><?php endforeach; ?>
                            </tbody></table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• PROGRAMS â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="programs-section" class="content-section <?= $section==='programs'?'active':'' ?> dashboard-section" data-section="programs">
                <h2><i class="fas fa-book me-2"></i>Program Management</h2>
                <?php if (!empty($programs)): ?>
                <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(350px,1fr));">
                    <?php foreach($programs as $p):
                        $pcount = enrollmentStats($conn,$students_conn,$p['program_name']);
                    ?>
                    <div class="stat-card">
                        <h3 class="fw-bold"><?= htmlspecialchars($p['program_name']) ?></h3>
                        <p class="text-muted mb-2"><?= $p['program_type'] ?> | <?= $p['duration_years'] ?> Year | <?= htmlspecialchars($p['department']) ?></p>
                        <div class="d-flex justify-content-between align-items-center"><span class="text-muted">Active Students:</span><strong><?= $pcount ?></strong></div>
                        <div class="d-flex justify-content-between align-items-center mt-1"><span class="text-muted">Status:</span><strong class="text-<?= $p['status']==='Active'?'success':'secondary' ?>"><?= $p['status'] ?></strong></div>
                        <div class="mt-2 d-flex gap-1 flex-wrap">
                            <button class="btn btn-sm btn-outline-info" onclick="window.open('director-academics.php?report=program_courses&program_code=<?= urlencode($p['program_code']) ?>','_blank')"><i class="fas fa-eye"></i> Courses</button>
                            <button class="btn btn-sm btn-outline-success" onclick="window.open('director-academics.php?report=program_enrollment&program=<?= urlencode($p['program_name']) ?>','_blank')"><i class="fas fa-users"></i> Students</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="generateReport('program_courses','program_code=<?= urlencode($p['program_code']) ?>')"><i class="fas fa-print"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: echo '<div class="empty-state"><i class="fas fa-book"></i><p>No programs configured yet.</p></div>'; endif; ?>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• COURSES â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="courses-section" class="content-section <?= $section==='courses'?'active':'' ?> dashboard-section" data-section="courses">
                <h2><i class="fas fa-layer-group me-2"></i>Courses & Modules</h2>
                <?php if(!empty($courses_catalog)): ?>
                <div class="mb-2"><input type="text" id="courseSearch" class="form-control form-control-sm" placeholder="Search courses..." onkeyup="filterTable('courseSearch','courseTable')"></div>
                <div class="table-responsive" style="max-height:450px;overflow-y:auto">
                    <table class="table table-sm table-hover" id="courseTable">
                        <thead><tr><th>Code</th><th>Title</th><th>Program</th><th>Year</th><th>Semester</th><th>Credits</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach($courses_catalog as $c): ?>
                        <tr><td><code><?= htmlspecialchars($c['course_code']) ?></code></td><td><?= htmlspecialchars($c['course_title']) ?></td><td><?= htmlspecialchars($c['program_name']??$c['program_code']) ?></td><td><?= $c['year_of_study'] ?></td><td><?= $c['semester'] ?></td><td><?= $c['credits'] ?></td><td><span class="badge bg-success"><?= htmlspecialchars($c['status']) ?></span></td>
                        <td><button class="btn btn-sm btn-outline-info" onclick="alert('Course: <?= addslashes($c['course_title']) ?>\nCode: <?= $c['course_code'] ?>\nProgram: <?= addslashes($c['program_name']??$c['program_code']) ?>\nCredits: <?= $c['credits'] ?>\nYear: <?= $c['year_of_study'] ?> Semester: <?= $c['semester'] ?>')"><i class="fas fa-eye"></i></button></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: echo '<div class="empty-state"><i class="fas fa-layer-group"></i><p>No courses in catalog.</p></div>'; endif; ?>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• ENROLLMENT â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="enrollment-section" class="content-section <?= $section==='enrollment'?'active':'' ?> dashboard-section" data-section="enrollment">
                <h2><i class="fas fa-users me-2"></i>Enrollment Statistics</h2>
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Program Enrollment Breakdown</h6>
                            <?php if(!empty($enrollment_by_prog)): ?>
                            <table class="table table-sm"><thead><tr><th>Program</th><th>Total</th><th>% Share</th></tr></thead><tbody>
                            <?php $gt=array_sum(array_column($enrollment_by_prog,'c')); foreach($enrollment_by_prog as $e): $sp=$gt>0?round(($e['c']/$gt)*100,1):0; ?>
                            <tr><td><?= htmlspecialchars($e['program']) ?></td><td><strong><?= $e['c'] ?></strong></td><td><?= $sp ?>%</td></tr>
                            <?php endforeach; ?>
                            </tbody></table>
                            <?php else: echo renderEmptyState('No enrollment data','fas fa-users'); endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Quick Stats</h6>
                            <div class="d-flex flex-column gap-3">
                                <div><small class="text-muted">Total Students</small><div class="fw-bold fs-5"><?= number_format($total_students) ?></div></div>
                                <div><small class="text-muted">Active Students</small><div class="fw-bold fs-5 text-success"><?= number_format($active_students) ?></div></div>
                                <div><small class="text-muted">Inactive/Incomplete</small><div class="fw-bold fs-5 text-danger"><?= number_format($total_students-$active_students) ?></div></div>
                                <div><small class="text-muted">Programs Offered</small><div class="fw-bold fs-5"><?= $active_programs ?></div></div>
                                <button class="btn btn-sm btn-outline-primary mt-2" onclick="window.open('director-academics.php?report=enrollment_summary','_blank')"><i class="fas fa-file-alt me-1"></i> Detailed Report</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• CURRICULUM â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="curriculum-section" class="content-section <?= $section==='curriculum'?'active':'' ?> dashboard-section" data-section="curriculum">
                <h2><i class="fas fa-sitemap me-2"></i>Curriculum Management</h2>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Curriculum Structure</h6>
                            <?php if(!empty($programs)): ?>
                            <div class="accordion" id="curriculumAccordion">
                                <?php foreach($programs as $i=>$p): ?>
                                <div class="accordion-item mb-2 border rounded-3">
                                    <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold py-2" data-bs-toggle="collapse" data-bs-target="#c<?= $i ?>"><?= htmlspecialchars($p['program_name']) ?> (<?= $p['duration_years'] ?>yr)</button></h2>
                                    <div id="c<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#curriculumAccordion">
                                        <div class="accordion-body p-2">
                                            <?php
                                            $ccs=[];
                                            $ccStmt=$conn->prepare("SELECT * FROM academic_course_catalog WHERE program_code=? ORDER BY year_of_study,semester");
                                            if($ccStmt){
                                            $ccStmt->bind_param("s",$p['program_code']);
                                            if (!$ccStmt->execute()) { error_log('$ccStmt execute failed: ' . ($ccStmt->error ?? 'unknown')); };
                                            $ccRes=$ccStmt->get_result();
                                            if($ccRes) while($row=$ccRes->fetch_assoc()) $ccs[]=$row;
                                            $ccStmt->close(); }
                                            if(!empty($ccs)): foreach($ccs as $cc): ?>
                                            <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom small">
                                                <span><code><?= htmlspecialchars($cc['course_code']) ?></code> <?= htmlspecialchars($cc['course_title']) ?></span>
                                                <span class="text-muted">Yr<?= $cc['year_of_study'] ?> S<?= $cc['semester'] ?> | <?= $cc['credits'] ?>cr</span>
                                            </div>
                                            <?php endforeach; else: echo '<p class="small text-muted p-2 mb-0">No courses mapped.</p>'; endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: echo renderEmptyState('No programs','fas fa-sitemap'); endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Curriculum Overview</h6>
                            <?php
                            $total_courses = sc($conn,"SELECT COUNT(*)c FROM academic_course_catalog");
                            $total_credits = 0; $r=$conn->query("SELECT SUM(credits)c FROM academic_course_catalog WHERE status='Active'"); if($r && $w=$r->fetch_assoc()) $total_credits=intval($w['c']??0);
                            $total_assignments = sc($conn,"SELECT COUNT(*)c FROM course_assignments");
                            ?>
                            <div class="row g-2 text-center">
                                <div class="col-4"><div class="p-3 rounded-3" style="background:#eef2ff"><div class="fw-bold text-primary fs-5"><?= $total_courses ?></div><small>Courses</small></div></div>
                                <div class="col-4"><div class="p-3 rounded-3" style="background:#f0fdf4"><div class="fw-bold text-success fs-5"><?= $total_credits ?></div><small>Credits</small></div></div>
                                <div class="col-4"><div class="p-3 rounded-3" style="background:#fffbeb"><div class="fw-bold text-warning fs-5"><?= $total_assignments ?></div><small>Assignments</small></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• EXAMS â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="exams-section" class="content-section <?= $section==='exams'?'active':'' ?> dashboard-section" data-section="exams">
                <h2><i class="fas fa-clipboard-list me-2"></i>Examination Centre</h2>
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <button class="btn btn-sm btn-primary" onclick="openExamModal()"><i class="fas fa-plus me-1"></i>Create Exam</button>
                    <button class="btn btn-sm btn-success" onclick="openEnterMarksModal()"><i class="fas fa-edit me-1"></i>Enter Marks</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.open('director-academics.php?report=exam_timetable','_blank')"><i class="fas fa-calendar-alt me-1"></i>Exam Timetable</button>
                </div>
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Exam No</th><th>Type</th><th>Course</th><th>Program</th><th>Date</th><th>Room</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php if(empty($exams)): ?>
                        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No exams scheduled.</p></div></td></tr>
                        <?php else: foreach($exams as $e): ?>
                        <tr><td><code><?= htmlspecialchars($e['exam_number']) ?></code></td><td><?= htmlspecialchars($e['exam_type']) ?></td><td><?= htmlspecialchars($e['course_code']) ?></td><td><?= htmlspecialchars($e['program_code']??'All') ?></td><td><?= $e['exam_date'] ?></td><td><?= htmlspecialchars($e['exam_room']??'-') ?></td>
                        <td><span class="badge bg-<?= $e['status']==='Published'?'success':($e['status']==='Scheduled'?'warning':'info') ?>"><?= $e['status'] ?></span></td>
                        <td><div class="d-flex gap-1"><?php if($e['status']!=='Published'): ?><form method="POST" class="d-inline"><input type="hidden" name="action" value="publish_results"><input type="hidden" name="exam_number" value="<?= htmlspecialchars($e['exam_number']) ?>"><button class="btn btn-sm btn-outline-success" onclick="return confirm('Publish results?')"><i class="fas fa-check"></i></button></form><?php endif; ?>
                        <button class="btn btn-sm btn-outline-warning" onclick="approveResult('<?= htmlspecialchars($e['exam_number']) ?>')"><i class="fas fa-check-double"></i></button>
                        <form method="POST" class="d-inline"><input type="hidden" name="action" value="delete_exam"><input type="hidden" name="exam_number" value="<?= htmlspecialchars($e['exam_number']) ?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this exam?')"><i class="fas fa-trash"></i></button></form></div></td></tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="section-card">
                    <div class="d-flex align-items-center justify-content-between mb-3"><h6 class="fw-bold mb-0" style="font-size:.95rem"><i class="fas fa-bell me-2 text-danger"></i>Department Alerts</h6></div>
                    <?= renderAlertsPanel($conn, 'ACAD', 5) ?>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• RESULTS â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="results-section" class="content-section <?= $section==='results'?'active':'' ?> dashboard-section" data-section="results">
                <h2><i class="fas fa-star me-2"></i>Results Management</h2>
                <?php if(!empty($academic_records)): ?>
                <div class="mb-2"><input type="text" id="resultSearch" class="form-control form-control-sm" placeholder="Search results..." onkeyup="filterTable('resultSearch','resultTable')"></div>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                    <table class="table table-sm table-hover" id="resultTable">
                        <thead><tr><th>Student</th><th>Course</th><th>Type</th><th>Marks</th><th>Grade</th><th>GPA</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach($academic_records as $ar): ?>
                        <tr><td><?= htmlspecialchars($ar['student_name']??"ID:{$ar['student_id']}") ?></td><td><?= htmlspecialchars($ar['course_code']) ?> <?= htmlspecialchars($ar['course_title']??'') ?></td><td><?= htmlspecialchars($ar['assessment_type']) ?></td><td><strong><?= $ar['marks'] ?></strong></td><td><span class="badge bg-<?= in_array($ar['grade'],['A','B']) ? 'success' : ($ar['grade']==='F' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($ar['grade']) ?></span></td><td><?= $ar['gpa'] ?? '-' ?></td>
                        <td><div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-warning" onclick="editMarks(<?= $ar['id'] ?>,'<?= $ar['marks'] ?>','<?= $ar['grade'] ?>','<?= $ar['gpa']??0 ?>')"><i class="fas fa-edit"></i></button>
                        <form method="POST" class="d-inline"><input type="hidden" name="action" value="delete_marks"><input type="hidden" name="record_id" value="<?= $ar['id'] ?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this record?')"><i class="fas fa-trash"></i></button></form>
                        </div></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: echo '<div class="empty-state"><i class="fas fa-star"></i><p>No results recorded yet.</p></div>'; endif; ?>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• APPROVALS â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="approvals-section" class="content-section <?= $section==='approvals'?'active':'' ?> dashboard-section" data-section="approvals">
                <h2><i class="fas fa-check-double me-2"></i>Results Approval Center</h2>
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Approval History</h6>
                            <?php if(!empty($result_approvals)): ?>
                            <table class="table table-sm"><thead><tr><th>Exam</th><th>Status</th><th>By</th><th>Date</th><th>Comments</th></tr></thead><tbody>
                            <?php foreach($result_approvals as $ra): ?>
                            <tr><td><code><?= htmlspecialchars($ra['exam_number']) ?></code></td><td><span class="badge bg-<?= $ra['status']==='Approved'?'success':'danger' ?>"><?= htmlspecialchars($ra['status']) ?></span></td><td><?= htmlspecialchars($ra['approved_by_name']??'System') ?></td><td><?= $ra['approval_date'] ?></td><td><small><?= htmlspecialchars($ra['comments']??'-') ?></small></td></tr>
                            <?php endforeach; ?>
                            </tbody></table>
                            <?php else: echo renderEmptyState('No approval actions yet','fas fa-check-double'); endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Pending Approvals</h6>
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
                        </div>
                    </div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• TRANSCRIPTS â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="transcripts-section" class="content-section <?= $section==='transcripts'?'active':'' ?> dashboard-section" data-section="transcripts">
                <h2><i class="fas fa-file-alt me-2"></i>Transcript Management</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Generate Document</h6>
                            <form method="POST" class="row g-2">
                                <input type="hidden" name="action" value="transcript_request">
                                <div class="col-12"><label class="form-label">Student</label><select name="student_id" class="form-select" required>
                                    <option value="">Select Student</option>
                                    <?php if($students_conn){$r=$students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");if($r)while($row=$r->fetch_assoc()):?><option value="<?=$row['id']?>"><?=htmlspecialchars($row['full_name']?:$row['student_number'])?></option><?php endwhile;} ?>
                                </select></div>
                                <div class="col-12"><label class="form-label">Document Type</label><select name="document_type" class="form-select"><option>Transcript</option><option>Certificate</option><option>Academic Record</option><option>Letter</option></select></div>
                                <div class="col-12"><label class="form-label">Title</label><input type="text" name="document_title" class="form-control" value="Academic Transcript"></div>
                                <div class="col-12"><button class="btn btn-primary btn-sm"><i class="fas fa-file-alt me-1"></i>Generate</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Recent Documents</h6>
                            <?php
                            $docs = []; $r=$conn->query("SELECT gd.*,s.full_name student_name FROM generated_documents gd LEFT JOIN $students_db.students s ON gd.student_id=s.id ORDER BY gd.id DESC LIMIT 15");
                            if($r) while($row=$r->fetch_assoc()) $docs[]=$row;
                            if(!empty($docs)): foreach($docs as $d): ?>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                                <span><?= htmlspecialchars($d['document_title']) ?> <small class="text-muted">- <?= htmlspecialchars($d['student_name']??"-") ?></small></span>
                                <span class="text-muted"><?= htmlspecialchars($d['document_type']) ?></span>
                            </div>
                            <?php endforeach; else: echo renderEmptyState('No documents','fas fa-file-alt'); endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• ATTENDANCE â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="attendance-section" class="content-section <?= $section==='attendance'?'active':'' ?> dashboard-section" data-section="attendance">
                <h2><i class="fas fa-calendar-check me-2"></i>Student Attendance</h2>
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Record Attendance</h6>
                            <form method="POST" class="row g-2">
                                <input type="hidden" name="action" value="record_attendance">
                                <div class="col-12"><label class="form-label">Student</label><select name="student_id" class="form-select" required>
                                    <option value="">Select Student</option>
                                    <?php if($students_conn){$r=$students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");if($r)while($row=$r->fetch_assoc()):?><option value="<?=$row['id']?>"><?=htmlspecialchars($row['full_name']?:$row['student_number'])?></option><?php endwhile;} ?>
                                </select></div>
                                <div class="col-6"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                                <div class="col-6"><label class="form-label">Status</label><select name="status" class="form-select"><option>Present</option><option>Absent</option><option>Late</option><option>Excused</option></select></div>
                                <div class="col-12"><button class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Record</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Recent Attendance</h6>
                            <?php if(!empty($attendance)): ?>
                            <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                                <table class="table table-sm"><thead><tr><th>Student</th><th>Date</th><th>Status</th></tr></thead><tbody>
                                <?php foreach($attendance as $a): ?>
                                <tr><td><?= htmlspecialchars($a['full_name']??"-") ?></td><td><?= $a['date'] ?></td><td><span class="badge bg-<?= $a['status']==='Present'?'success':($a['status']==='Absent'?'danger':'warning') ?>"><?= htmlspecialchars($a['status']) ?></span></td></tr>
                                <?php endforeach; ?>
                                </tbody></table>
                            </div>
                            <?php else: echo renderEmptyState('No attendance records','fas fa-calendar-check'); endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• CLINICAL â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="clinical-section" class="content-section <?= $section==='clinical'?'active':'' ?> dashboard-section" data-section="clinical">
                <h2><i class="fas fa-heartbeat me-2"></i>Clinical Training Programs</h2>
                <?php if(!empty($clinical)): ?>
                <div class="mb-2"><input type="text" id="clinicalSearch" class="form-control form-control-sm" placeholder="Search clinical..." onkeyup="filterTable('clinicalSearch','clinicalTable')"></div>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                    <table class="table table-sm table-hover" id="clinicalTable">
                        <thead><tr><th>Student</th><th>Supervisor</th><th>Start</th><th>End</th><th>Department</th><th>Hours</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach($clinical as $cl): ?>
                        <tr><td><?= htmlspecialchars($cl['student_name']??"ID:{$cl['student_id']}") ?></td><td><?= htmlspecialchars($cl['supervisor_name']??$cl['full_name']??'Unassigned') ?></td><td><?= $cl['start_date'] ?></td><td><?= $cl['end_date']??'-' ?></td><td><?= htmlspecialchars($cl['department']??$cl['clinical_area']??'-') ?></td><td><?= $cl['total_hours']??$cl['hours']??'-' ?></td><td><span class="badge bg-<?= ($cl['status']??'Active')==='Completed'?'success':(($cl['status']??'Active')==='Active'?'primary':'warning') ?>"><?= htmlspecialchars($cl['status']??'Active') ?></span></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: echo '<div class="empty-state"><i class="fas fa-heartbeat"></i><p>No clinical training records.</p></div>'; endif; ?>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• LECTURERS â•â•â•â•â•â•â•â•â•â•â• -->
                <section id="lecturers-section" class="content-section <?= $section==='lecturers'?'active':'' ?> dashboard-section" data-section="lecturers">
                <h2><i class="fas fa-chalkboard-teacher me-2"></i>Lecturer Management <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="fas fa-print"></i> Print</button></h2>
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Lecturers & Teaching Load</h6>
                            <?php if(!empty($lecturers)): ?>
                            <div class="mb-2"><input type="text" id="lecturerSearch" class="form-control form-control-sm" placeholder="Search lecturers..." onkeyup="filterTable('lecturerSearch','lecturerTable')"></div>
                            <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                                <table class="table table-sm table-hover" id="lecturerTable">
                                    <thead><tr><th>Name</th><th>Position</th><th>Department</th><th>Email</th><th>Courses</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach($lecturers as $l):
                                        $lc = sc($conn,"SELECT COUNT(*)c FROM course_assignments WHERE lecturer_id={$l['id']}");
                                    ?>
                                    <tr><td><strong><?= htmlspecialchars($l['full_name']) ?></strong></td><td><?= htmlspecialchars($l['position']) ?></td><td><?= htmlspecialchars($l['department']??'-') ?></td><td><?php if(!empty($l['email'])): ?><a href="mailto:<?= htmlspecialchars($l['email']) ?>" class="text-decoration-none" title="Send email"><i class="fas fa-envelope text-primary me-1"></i><small><?= htmlspecialchars($l['email']) ?></small></a><?php else: ?><small class="text-muted">-</small><?php endif; ?></td><td><span class="badge bg-info"><?= $lc ?></span></td>
                                    <td><button class="btn btn-sm btn-outline-primary" onclick="assignCourse(<?= $l['id'] ?>,'<?= addslashes($l['full_name']) ?>')"><i class="fas fa-plus"></i> Course</button></td></tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: echo renderEmptyState('No lecturers found','fas fa-chalkboard-teacher'); endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Assign Course to Lecturer</h6>
                            <form method="POST" class="row g-2">
                                <input type="hidden" name="action" value="assign_lecturer">
                                <div class="col-12"><label class="form-label">Lecturer</label><select name="lecturer_id" class="form-select" required><option value="">Select Lecturer</option>
                                <?php foreach($lecturers as $l): ?><option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['full_name']) ?></option><?php endforeach; ?>
                                </select></div>
                                <div class="col-12"><label class="form-label">Course</label><select name="course_code" class="form-select" required><option value="">Select Course</option>
                                <?php foreach($courses_catalog as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_title']) ?></option><?php endforeach; ?>
                                </select></div>
                                <div class="col-6"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select></div>
                                <div class="col-6"><label class="form-label">Year</label><input type="text" name="academic_year" class="form-control" value="<?= date('Y').'-'.(date('Y')+1) ?>"></div>
                                <div class="col-12"><button class="btn btn-primary btn-sm"><i class="fas fa-check me-1"></i>Assign</button></div>
                            </form>
                        </div>
                        <div class="section-card mt-3">
                            <h6 class="fw-bold mb-3">Workload Summary</h6>
                            <?php
                            $workload = []; $r=$conn->query("SELECT s.full_name,COUNT(ca.id)cnt FROM staff s LEFT JOIN course_assignments ca ON s.id=ca.lecturer_id WHERE s.position LIKE '%Lecturer%' OR s.position LIKE '%lecturer%' GROUP BY s.id ORDER BY cnt DESC LIMIT 5");
                            if($r) while($row=$r->fetch_assoc()) $workload[]=$row;
                            if(!empty($workload)): foreach($workload as $w): ?>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom small"><span><?= htmlspecialchars($w['full_name']) ?></span><span class="badge bg-<?= $w['cnt']>3?'warning':'success' ?>"><?= $w['cnt'] ?> courses</span></div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• TIMETABLE â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="timetable-section" class="content-section <?= $section==='timetable'?'active':'' ?> dashboard-section" data-section="timetable">
                <h2><i class="fas fa-clock me-2"></i>Timetable Management</h2>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Add Timetable Entry</h6>
                            <form method="POST" class="row g-2">
                                <input type="hidden" name="action" value="add_timetable">
                                <div class="col-12"><label class="form-label">Lecturer</label><select name="lecturer_id" class="form-select" required><option value="">Select</option>
                                <?php foreach($lecturers as $l): ?><option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['full_name']) ?></option><?php endforeach; ?>
                                </select></div>
                                <div class="col-12"><label class="form-label">Course</label><select name="course_code" class="form-select" required><option value="">Select</option>
                                <?php foreach($courses_catalog as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?></option><?php endforeach; ?>
                                </select></div>
                                <div class="col-6"><label class="form-label">Day</label><select name="day_of_week" class="form-select"><option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option></select></div>
                                <div class="col-6"><label class="form-label">Room</label><input type="text" name="room" class="form-control"></div>
                                <div class="col-6"><label class="form-label">Start</label><input type="time" name="start_time" class="form-control" required></div>
                                <div class="col-6"><label class="form-label">End</label><input type="time" name="end_time" class="form-control" required></div>
                                <div class="col-12"><button class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Entry</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Class Schedule</h6>
                            <?php if(!empty($timetable)): ?>
                            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>Day</th><th>Time</th><th>Course</th><th>Lecturer</th><th>Room</th></tr></thead>
                                    <tbody>
                                    <?php foreach($timetable as $t): ?>
                                    <tr><td><span class="badge bg-primary"><?= htmlspecialchars($t['day_of_week']) ?></span></td><td><?= $t['start_time'] ?> - <?= $t['end_time'] ?></td><td><?= htmlspecialchars($t['course_code']) ?></td><td><?= htmlspecialchars($t['lecturer_name']??'-') ?></td><td><?= htmlspecialchars($t['room']??'-') ?></td></tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: echo renderEmptyState('No timetable entries','fas fa-clock'); endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• QUALITY â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="quality-section" class="content-section <?= $section==='quality'?'active':'' ?> dashboard-section" data-section="quality">
                <h2><i class="fas fa-shield-alt me-2"></i>Quality Assurance & Accreditation</h2>
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">New Quality Review</h6>
                            <form method="POST" class="row g-2">
                                <input type="hidden" name="action" value="create_quality_review">
                                <div class="col-12"><label class="form-label">Review Title</label><input type="text" name="review_title" class="form-control" required></div>
                                <div class="col-6"><label class="form-label">Department</label><input type="text" name="department" class="form-control"></div>
                                <div class="col-6"><label class="form-label">Review Area</label><input type="text" name="review_area" class="form-control"></div>
                                <div class="col-12"><label class="form-label">Findings</label><textarea name="findings" class="form-control" rows="2"></textarea></div>
                                <div class="col-12"><label class="form-label">Recommendations</label><textarea name="recommendations" class="form-control" rows="2"></textarea></div>
                                <div class="col-12"><label class="form-label">Status</label><select name="status" class="form-select"><option>Open</option><option>In Progress</option><option>Resolved</option><option>Closed</option></select></div>
                                <div class="col-12"><button class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save Review</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="section-card">
                            <h6 class="fw-bold mb-3">Quality Reviews</h6>
                            <?php if(!empty($quality)): ?>
                            <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                                <table class="table table-sm"><thead><tr><th>Title</th><th>Department</th><th>Area</th><th>Status</th><th>Reviewer</th><th>Date</th></tr></thead><tbody>
                                <?php foreach($quality as $q): ?>
                                <tr><td><?= htmlspecialchars($q['review_title']) ?></td><td><?= htmlspecialchars($q['department']??'-') ?></td><td><?= htmlspecialchars($q['review_area']??'-') ?></td><td><span class="badge bg-<?= $q['status']==='Resolved'||$q['status']==='Closed'?'success':($q['status']==='In Progress'?'warning':'danger') ?>"><?= htmlspecialchars($q['status']) ?></span></td><td><?= htmlspecialchars($q['reviewer_name']??'-') ?></td><td><?= $q['review_date'] ?></td></tr>
                                <?php endforeach; ?>
                                </tbody></table>
                            </div>
                            <?php else: echo renderEmptyState('No quality reviews','fas fa-shield-alt'); endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• REPORTS â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="reports-section" class="content-section <?= $section==='reports'?'active':'' ?> dashboard-section" data-section="reports">
                <h2><i class="fas fa-chart-bar me-2"></i>Academic Reports</h2>
                <div class="reports-grid">
                    <div class="report-card"><div class="report-icon"><i class="fas fa-file-alt"></i></div><h3>Student Progress</h3><p>Track academic progress</p><button class="btn btn-primary btn-sm" onclick="window.open('director-academics.php?report=student_progress','_blank')">Generate</button></div>
                    <div class="report-card"><div class="report-icon"><i class="fas fa-chart-line"></i></div><h3>Attendance Report</h3><p>Class attendance records</p><button class="btn btn-primary btn-sm" onclick="window.open('director-academics.php?report=attendance_report','_blank')">Generate</button></div>
                    <div class="report-card"><div class="report-icon"><i class="fas fa-graduation-cap"></i></div><h3>Graduation Report</h3><p>Graduation statistics</p><button class="btn btn-primary btn-sm" onclick="window.open('director-academics.php?report=graduation','_blank')">Generate</button></div>
                    <div class="report-card"><div class="report-icon"><i class="fas fa-star"></i></div><h3>Academic Performance</h3><p>Student performance summary</p><button class="btn btn-primary btn-sm" onclick="window.open('director-academics.php?report=academic_performance','_blank')">Generate</button></div>
                    <div class="report-card"><div class="report-icon"><i class="fas fa-layer-group"></i></div><h3>Program Enrollment</h3><p>Students by program</p><button class="btn btn-primary btn-sm" onclick="window.open('director-academics.php?report=program_enrollment','_blank')">Generate</button></div>
                    <div class="report-card"><div class="report-icon"><i class="fas fa-chalkboard-teacher"></i></div><h3>Lecturer Workload</h3><p>Courses per lecturer</p><button class="btn btn-primary btn-sm" onclick="window.open('director-academics.php?report=lecturer_workload','_blank')">Generate</button></div>
                    <div class="report-card"><div class="report-icon"><i class="fas fa-calendar-alt"></i></div><h3>Exam Timetable</h3><p>Full exam schedule</p><button class="btn btn-primary btn-sm" onclick="window.open('director-academics.php?report=exam_timetable','_blank')">Generate</button></div>
                    <div class="report-card"><div class="report-icon"><i class="fas fa-venus-mars"></i></div><h3>Enrollment Summary</h3><p>Gender & status breakdown</p><button class="btn btn-primary btn-sm" onclick="window.open('director-academics.php?report=enrollment_summary','_blank')">Generate</button></div>
                </div>
            </section>

            <!-- â•â•â•â•â•â•â•â•â•â•â• DUTIES â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="duties-section" class="content-section <?= $section==='duties'?'active':'' ?> dashboard-section" data-section="duties">
                <h2><i class="fas fa-tasks me-2"></i>Official Duties</h2>
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

            <!-- â•â•â•â•â•â•â•â•â•â•â• ACTIVITY â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="activity-section" class="content-section <?= $section==='activity'?'active':'' ?> dashboard-section" data-section="activity">
                <h2><i class="fas fa-history me-2"></i>Recent Activities</h2>
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

            <!-- â•â•â•â•â•â•â•â•â•â•â• WEBSITE SUBMISSIONS â•â•â•â•â•â•â•â•â•â•â• -->
            <section id="submissions-section" class="content-section <?= $section==='submissions'?'active':'' ?> dashboard-section" data-section="submissions">
                <div class="section-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title"><i class="fas fa-globe" style="color:#2563eb;"></i>Website Student Applications</h3>
                            <p class="section-subtitle">Student applications submitted through the website</p>
                        </div>
                    </div>
                    <?php
                    if ($website_conn) {
                        renderWebsiteSubmissionsWidget($website_conn, ['applications'], 20);
                    } else {
                        echo '<div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2" style="color:#94a3b8;"></i><p>Website database unavailable.</p></div>';
                    }
                    ?>
                </div>
            </section>

    <div class="section-card">
      <div class="section-header"><h3 class="section-title"><i class="fas fa-newspaper text-primary"></i>News &amp; Announcements</h3></div>
      <div class="p-2">
        <?php renderNewsWidget($conn,$website_conn,$user_id,$user_name,$user_role,5); ?>
      </div>
    </div>

</div>
</div>
<!-- â•â•â•â•â•â•â•â•â•â•â• MODALS â•â•â•â•â•â•â•â•â•â•â• -->

    <!-- Create Exam Modal -->
    <div class="modal fade" id="createExamModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><input type="hidden" name="action" value="create_exam">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create Exam</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Course</label><select name="course_code" class="form-select" required><option value="">Select Course</option><?php foreach($courses_catalog as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?> â€“ <?= htmlspecialchars($c['course_title']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Exam Type</label><select name="exam_type" class="form-select"><option>Mid Semester</option><option>End of Semester</option><option>Supplementary</option><option>Practical</option></select></div>
            <div class="col-md-6"><label class="form-label">Program</label><select name="program_code" class="form-select"><option value="">All Programs</option><?php foreach($programs as $p): ?><option value="<?= htmlspecialchars($p['program_code']) ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="exam_date" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Room</label><input type="text" name="exam_room" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select></div>
            <div class="col-md-12"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?= date('Y').'-'.(date('Y')+1) ?>"></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create Exam</button></div>
    </form></div></div>

    <!-- Enter Marks Modal -->
    <div class="modal fade" id="enterMarksModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" class="modal-content"><input type="hidden" name="action" value="enter_marks">
        <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Enter Exam Marks</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-4"><label class="form-label">Exam</label><select name="exam_number" class="form-select" required><option value="">Select Exam</option><?php foreach($exams as $e): ?><option value="<?= htmlspecialchars($e['exam_number']) ?>"><?= htmlspecialchars($e['exam_number']) ?> â€“ <?= htmlspecialchars($e['course_code']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Student</label><select name="student_id" class="form-select" required><option value="">Select Student</option><?php if($students_conn){$r=$students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");if($r)while($row=$r->fetch_assoc()):?><option value="<?=$row['id']?>"><?=htmlspecialchars($row['full_name']?:$row['student_number'])?></option><?php endwhile;} ?></select></div>
            <div class="col-md-4"><label class="form-label">Course Code</label><input type="text" name="course_code" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Course Name</label><input type="text" name="course_name" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Marks</label><input type="number" step="0.1" name="marks" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Grade</label><select name="grade" class="form-select"><option>A</option><option>B</option><option>C</option><option>D</option><option>F</option></select></div>
            <div class="col-md-4"><label class="form-label">GPA</label><input type="number" step="0.01" name="gpa" class="form-control"></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save Marks</button></div>
    </form></div></div>

    <!-- Student Profile Modal -->
    <div class="modal fade" id="studentProfileModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content">
        <div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-user-graduate me-2"></i>Student Profile</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="studentProfileBody"><div class="text-center py-4"><em>Loading...</em></div></div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" onclick="printProfile()"><i class="fas fa-print"></i> Print</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div></div></div>

    <!-- Approve Result Modal -->
    <div class="modal fade" id="approveResultModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><input type="hidden" name="action" value="approve_result">
        <div class="modal-header bg-warning text-white"><h5 class="modal-title"><i class="fas fa-check-double me-2"></i>Approve Results</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-12"><label class="form-label">Exam Number</label><input type="text" name="exam_number" id="approveExamNumber" class="form-control" readonly></div>
            <div class="col-12"><label class="form-label">Status</label><select name="approval_status" class="form-select"><option>Approved</option><option>Returned</option><option>Rejected</option></select></div>
            <div class="col-12"><label class="form-label">Comments</label><textarea name="comments" class="form-control" rows="2"></textarea></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Submit</button></div>
    </form></div></div>

    <!-- Edit Marks Modal -->
    <div class="modal fade" id="editMarksModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><input type="hidden" name="action" value="edit_marks"><input type="hidden" name="record_id" id="editMarksRecordId">
        <div class="modal-header bg-warning text-white"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Marks</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-4"><label class="form-label">Marks</label><input type="number" step="0.1" name="marks" id="editMarksMarks" class="form-control" required></div>
            <div class="col-4"><label class="form-label">Grade</label><select name="grade" id="editMarksGrade" class="form-select"><option>A</option><option>B</option><option>C</option><option>D</option><option>F</option></select></div>
            <div class="col-4"><label class="form-label">GPA</label><input type="number" step="0.01" name="gpa" id="editMarksGpa" class="form-control"></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning">Update</button></div>
    </form></div></div>

    <script>
    // â”€â”€ Modal Fns â”€â”€
    function openExamModal(){ new bootstrap.Modal(document.getElementById('createExamModal')).show(); }
    function openEnterMarksModal(){ new bootstrap.Modal(document.getElementById('enterMarksModal')).show(); }
    function approveResult(en){ document.getElementById('approveExamNumber').value=en; new bootstrap.Modal(document.getElementById('approveResultModal')).show(); }
    function editMarks(id,marks,grade,gpa){
        document.getElementById('editMarksRecordId').value=id;
        document.getElementById('editMarksMarks').value=marks;
        document.getElementById('editMarksGrade').value=grade;
        document.getElementById('editMarksGpa').value=gpa;
        new bootstrap.Modal(document.getElementById('editMarksModal')).show();
    }

    // â”€â”€ Student Profile â”€â”€
    function viewStudentProfile(id) {
        const modal = new bootstrap.Modal(document.getElementById('studentProfileModal'));
        document.getElementById('studentProfileBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading...</p></div>';
        modal.show();
        fetch('director-academics.php?ajax=student_profile&student_id='+id)
            .then(r=>r.json()).then(d=>{
                let info=d.info||{}, att=d.attendance||[], ar=d.academic_records||[], docs=d.documents||[];
                let pres=att.filter(a=>a.status==='Present').length, rate=att.length?Math.round(pres/att.length*100):0;
                let h=`<ul class="nav nav-tabs mb-3" id="pTabs"><li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#pPers">Personal</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pAcad">Academic</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pRec">Records</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pDoc">Documents</a></li></ul>
                <div class="tab-content">
                <div class="tab-pane fade show active" id="pPers"><div class="row g-2 small">
                    <div class="col-md-6"><strong>Name:</strong> ${info.full_name||''}</div>
                    <div class="col-md-6"><strong>Reg:</strong> ${info.registration_number||info.student_number||'-'}</div>
                    <div class="col-md-6"><strong>Phone:</strong> ${info.phone||'-'}</div>
                    <div class="col-md-6"><strong>Email:</strong> ${info.email||'-'}</div>
                    <div class="col-md-6"><strong>Gender:</strong> ${info.gender||'-'}</div>
                    <div class="col-md-6"><strong>DOB:</strong> ${info.date_of_birth||'-'}</div>
                    <div class="col-md-6"><strong>National ID:</strong> ${info.national_student_id_number||'-'}</div>
                    <div class="col-md-6"><strong>Guardian:</strong> ${info.guardian_name||info.parent_name||'-'}</div>
                </div></div>
                <div class="tab-pane fade" id="pAcad"><div class="row g-2 small">
                    <div class="col-md-4"><strong>Program:</strong> ${info.program||'-'}</div>
                    <div class="col-md-4"><strong>Year:</strong> ${info.level||'-'}</div>
                    <div class="col-md-4"><strong>Semester:</strong> ${info.current_semester||'-'}</div>
                    <div class="col-md-4"><strong>Set:</strong> ${info.set_name||'-'}</div>
                    <div class="col-md-4"><strong>Intake:</strong> ${info.intake_date||'-'}</div>
                    <div class="col-12 mt-2"><strong>Attendance:</strong> ${rate}% (${pres}/${att.length})</div>
                </div></div>
                <div class="tab-pane fade" id="pRec"><div class="small">${ar.length ? ar.map(r=>'<div class="d-flex justify-content-between border-bottom py-1"><span>'+r.course_code+' '+r.assessment_type+'</span><span>Marks: '+r.marks+' Grade: '+r.grade+' GPA: '+(r.gpa||'-')+'</span></div>').join('') : '<p class="text-muted">No records.</p>'}</div></div>
                <div class="tab-pane fade" id="pDoc"><div class="small">${docs.length ? docs.map(d=>'<div class="mb-1">'+(d.file_path?'<a href="../'+d.file_path+'" target="_blank">'+d.document_title+'</a>':'<span>'+d.document_title+'</span>')+' <small class="text-muted">('+d.document_type+')</small></div>').join('') : '<p class="text-muted">No documents.</p>'}</div></div>
                </div>`;
                document.getElementById('studentProfileBody').innerHTML = h;
                setTimeout(()=>{ document.querySelectorAll('#pTabs a').forEach(t=>{ t.addEventListener('click',e=>{ e.preventDefault(); new bootstrap.Tab(t).show(); }); }); }, 100);
            }).catch(function(){ document.getElementById('studentProfileBody').innerHTML = '<div class="alert alert-danger text-center m-3">Failed to load profile.</div>'; });
    }

    function printProfile(){
        const c = document.getElementById('studentProfileBody').innerHTML;
        const w = window.open('','_blank');
        w.document.write('<!DOCTYPE html><html><head><title>Student Profile</title><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ddd;padding:6px 8px}th{background:#f3f4f6}h2{color:#1f2937}@media print{body{print-color-adjust:exact}}</style></head><body><h2>Student Profile</h2>'+c+'<script>window.onload=function(){window.print()}<\/script></body></html>');
        w.document.close();
    }

    // â”€â”€ Table Filters â”€â”€
    function filterTable(inputId, tableId){
        const q = document.getElementById(inputId)?.value?.toLowerCase()||'';
        document.querySelectorAll('#'+tableId+' tbody tr').forEach(r=>{ r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
    }

    // â”€â”€ Course Assignment â”€â”€
    function assignCourse(lid, name){
        const f = document.createElement('form'); f.method='POST'; f.className='d-inline';
        f.innerHTML = '<input name="action" value="assign_lecturer"><input name="lecturer_id" value="'+lid+'">' +
            '<select name="course_code"><?php foreach($courses_catalog as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?></option><?php endforeach; ?></select>' +
            '<input type="hidden" name="semester" value="Semester 1"><input type="hidden" name="academic_year" value="<?= date('Y').'-'.(date('Y')+1) ?>">' +
            '<button class="btn btn-sm btn-primary" onclick="return confirm(\'Assign course to '+name+'?\')">Go</button>';
        document.body.appendChild(f); f.submit();
    }

    // â”€â”€ Report Helper â”€â”€
    function generateReport(report, params){
        window.open('director-academics.php?report='+report+'&'+params,'_blank');
    }

    // â”€â”€ Hash Routing (sidebar #anchor support) â”€â”€
    (function(){
        var h = window.location.hash.replace('#','');
        if(h && window.location.search.indexOf('section=')===-1){
            window.location.href = '?section='+h;
        }
    })();
    window.addEventListener('hashchange', function(){
        var h = window.location.hash.replace('#','');
        if(h) window.location.href = '?section='+h;
    });
    </script>

<!-- â•â•â• AJAX MODULE LOADING â•â•â• -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.acad-content');
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
            fetch('director-academics.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.acad-content');
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

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
