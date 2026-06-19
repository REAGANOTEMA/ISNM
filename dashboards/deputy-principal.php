<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';
include_once '../includes/photo_upload.php';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';

$ctx = bootstrapStaffDashboard(['deputy', 'principal']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$website_conn = $ctx['website'];
$students_conn = $ctx['students'] ?? null;
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_role_id = 0; $ri = $conn->query("SELECT role_id FROM staff WHERE id = $user_id");
if ($ri) { $user_role_id = (int)$ri->fetch_assoc()['role_id']; }
$user_email = $user['email'] ?? '';
$user_name = $user['full_name'] ?? '';

$display_name = $user['full_name'] ?? 'Deputy Principal';
$name_parts = explode(' ', trim($display_name), 2);
$first_name = $name_parts[0] ?? 'User';
$last_name = $name_parts[1] ?? '';

// ── Helper ──
function safeCount($c, $s) { $r=$c->query($s); if(!$r)return 0; $w=$r->fetch_assoc(); return intval($w['c']??0); }

// ── Real stats ──
$total_students   = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students") : 0;
$active_students  = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students WHERE status='Active'") : 0;
$total_staff      = safeCount($conn,"SELECT COUNT(*)c FROM staff");
$lecturers        = safeCount($conn,"SELECT COUNT(*)c FROM staff WHERE position LIKE '%Lecturer%' OR position LIKE '%lecturer%'");
$active_courses   = safeCount($conn,"SELECT COUNT(*)c FROM academic_course_catalog WHERE status='Active'");
$active_programs  = safeCount($conn,"SELECT COUNT(*)c FROM academic_programs WHERE status='Active'");
$recent_applications = $website_conn ? safeCount($website_conn,"SELECT COUNT(*)c FROM applications") : 0;
$avg_attendance   = $students_conn ? round(safeCount($students_conn,"SELECT COUNT(*)c FROM student_attendance WHERE status='Present'") / max(1,safeCount($students_conn,"SELECT COUNT(*)c FROM student_attendance")) * 100, 1) : 0;

// ── Load real data ──
$lecturer_list = []; $r=$conn->query("SELECT id,full_name,email,position FROM staff WHERE position LIKE '%Lecturer%' OR position LIKE '%lecturer%' OR position LIKE '%Head%' ORDER BY full_name");
if($r) while($row=$r->fetch_assoc()) $lecturer_list[]=$row;

$course_list = []; $r=$conn->query("SELECT id,course_code,course_title,program_code FROM academic_course_catalog WHERE status='Active' ORDER BY course_title");
if($r) while($row=$r->fetch_assoc()) $course_list[]=$row;

$program_list = []; $r=$conn->query("SELECT id,program_code,program_name,program_type,department,duration_years,status FROM academic_programs ORDER BY program_name");
if($r) while($row=$r->fetch_assoc()) $program_list[]=$row;

$timetable_entries = []; $r=$conn->query("SELECT t.*,c.full_name lecturer_name FROM academic_timetable t LEFT JOIN staff c ON t.lecturer_id=c.id ORDER BY t.day_of_week,t.start_time LIMIT 50");
if($r) while($row=$r->fetch_assoc()) $timetable_entries[]=$row;

$assignments = []; $r=$conn->query("SELECT ca.*,s.full_name lecturer_name FROM course_assignments ca LEFT JOIN staff s ON ca.lecturer_id=s.id ORDER BY ca.created_at DESC LIMIT 30");
if($r) while($row=$r->fetch_assoc()) $assignments[]=$row;

$placements = $students_conn ? [] : []; if($students_conn){ $r=$students_conn->query("SELECT cp.*,s.full_name student_name FROM clinical_placements_students cp LEFT JOIN students s ON cp.student_id=s.id ORDER BY cp.created_at DESC LIMIT 50");
if($r) while($row=$r->fetch_assoc()) $placements[]=$row; }

$materials = []; $r=$conn->query("SELECT d.*,s.full_name staff_name FROM generated_documents d LEFT JOIN staff s ON d.generated_by=s.id WHERE d.document_type IN('Teaching Material','Lecture Notes','Curriculum') OR d.document_title LIKE '%material%' OR d.document_title LIKE '%lecture%' ORDER BY d.created_at DESC LIMIT 30");
if($r) while($row=$r->fetch_assoc()) $materials[]=$row;

$recent_activities = []; $r=$conn->query("SELECT activity_description activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
if($r) while($row=$r->fetch_assoc()) $recent_activities[]=$row;

// ── Report generation ──
$report = $_GET['report'] ?? '';
if ($report) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#f3f4f6}h2{color:#1f2937}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print"><button onclick="window.print()" style="padding:6px 16px;margin-bottom:12px">Print</button> <button onclick="window.close()" style="padding:6px 16px">Close</button></div>';
    if ($report === 'exam_performance') {
        echo '<h2>Exam Performance Report</h2>';
        $r = $conn->query("SELECT course_code,COUNT(*) total,SUM(CASE WHEN grade IN('A','B','C','D') THEN 1 ELSE 0 END) passed,AVG(marks) avg_marks FROM academic_records WHERE assessment_type='Exam' GROUP BY course_code");
        echo '<table><thead><tr><th>Course</th><th>Total Students</th><th>Passed</th><th>Pass Rate</th><th>Avg Marks</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ $pr=$row['total']>0?round(($row['passed']/$row['total'])*100,1):0; echo '<tr><td>'.htmlspecialchars($row['course_code']).'</td><td>'.$row['total'].'</td><td>'.$row['passed'].'</td><td>'.$pr.'%</td><td>'.round($row['avg_marks'],1).'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'attendance_report') {
        echo '<h2>Attendance Report</h2>';
        if($students_conn){ $r=$students_conn->query("SELECT s.full_name,s.student_number,s.course,COUNT(a.id) total,SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) present FROM students s LEFT JOIN student_attendance a ON s.id=a.student_id WHERE s.status='Active' GROUP BY s.id ORDER BY s.full_name LIMIT 100");
        echo '<table><thead><tr><th>Name</th><th>Reg No</th><th>Program</th><th>Total Days</th><th>Present</th><th>Rate</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ $rt=$row['total']>0?round(($row['present']/$row['total'])*100,1).'%':'-'; echo '<tr><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['student_number']).'</td><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['total'].'</td><td>'.$row['present'].'</td><td>'.$rt.'</td></tr>'; }
        echo '</tbody></table>'; }
    } elseif ($report === 'student_list') {
        echo '<h2>Student List</h2>';
        if($students_conn){ $r=$students_conn->query("SELECT student_number,registration_number,full_name,course,current_year,gender,status,phone,email FROM students WHERE status='Active' ORDER BY full_name");
        echo '<table><thead><tr><th>#</th><th>Reg No</th><th>Name</th><th>Program</th><th>Year</th><th>Gender</th><th>Status</th></tr></thead><tbody>';
        $i=1; if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.$i++.'</td><td>'.htmlspecialchars($row['registration_number']?:$row['student_number']).'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['course']).'</td><td>'.$row['current_year'].'</td><td>'.htmlspecialchars($row['gender']).'</td><td>'.htmlspecialchars($row['status']).'</td></tr>'; }
        echo '</tbody></table>'; }
    } elseif ($report === 'clinical_report') {
        echo '<h2>Clinical Placements Report</h2>';
        if($students_conn){ $r=$students_conn->query("SELECT cp.*,s.full_name FROM clinical_placements_students cp LEFT JOIN students s ON cp.student_id=s.id ORDER BY cp.created_at DESC LIMIT 100");
        echo '<table><thead><tr><th>Student</th><th>Site</th><th>Supervisor</th><th>Start</th><th>End</th><th>Score</th><th>Status</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['full_name']??'-').'</td><td>'.htmlspecialchars($row['placement_site']).'</td><td>'.htmlspecialchars($row['supervisor_name']??'-').'</td><td>'.$row['start_date'].'</td><td>'.($row['end_date']??'-').'</td><td>'.($row['competency_score']??'-').'</td><td>'.$row['status'].'</td></tr>'; }
        echo '</tbody></table>'; }
    } elseif ($report === 'evaluation_report') {
        echo '<h2>Clinical Evaluation Report</h2>';
        if($students_conn){ $r=$students_conn->query("SELECT cp.*,s.full_name FROM clinical_placements_students cp LEFT JOIN students s ON cp.student_id=s.id WHERE cp.competency_score IS NOT NULL ORDER BY cp.competency_score DESC");
        echo '<table><thead><tr><th>Student</th><th>Site</th><th>Score</th><th>Logbook</th><th>Evaluation</th><th>Status</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['full_name']??'-').'</td><td>'.htmlspecialchars($row['placement_site']).'</td><td>'.($row['competency_score']??'-').'</td><td>'.($row['logbook_submitted']?'Yes':'No').'</td><td>'.htmlspecialchars(substr($row['supervisor_evaluation']??'',0,100)).'</td><td>'.$row['status'].'</td></tr>'; }
        echo '</tbody></table>'; }
    }
    echo '</body></html>'; exit;
}

// ── AJAX endpoint ──
$ajax = $_GET['ajax'] ?? '';
$ajaxSid = intval($_GET['student_id'] ?? 0);
$ajaxPid = intval($_GET['program_id'] ?? 0);
if ($ajax && $ajaxSid > 0) {
    header('Content-Type: application/json');
    if ($ajax === 'student_profile') {
        $info = []; $r=$students_conn->query("SELECT * FROM students WHERE id=$ajaxSid");
        if($r) $info=$r->fetch_assoc();
        $att=[];
        if($students_conn){ $r=$students_conn->query("SELECT date,status FROM student_attendance WHERE student_id=$ajaxSid ORDER BY date DESC LIMIT 20");
        if($r) while($row=$r->fetch_assoc()) $att[]=$row; }
        $inv=[]; if($students_conn){ $r=$students_conn->query("SELECT invoice_number,fee_type,total_amount,amount_paid,balance,status FROM student_invoices WHERE student_id=$ajaxSid ORDER BY created_at DESC");
        if($r) while($row=$r->fetch_assoc()) $inv[]=$row; }
        $pay=[]; if($students_conn){ $r=$students_conn->query("SELECT payment_reference,amount_received,payment_method,payment_date,status FROM payments WHERE student_id=$ajaxSid ORDER BY payment_date DESC");
        if($r) while($row=$r->fetch_assoc()) $pay[]=$row; }
        echo json_encode(['info'=>$info,'attendance'=>$att,'invoices'=>$inv,'payments'=>$pay]);
        exit;
    }
    echo json_encode([]); exit;
}
if ($ajax && $ajaxPid > 0) {
    header('Content-Type: application/json');
    if ($ajax === 'program_courses') {
        $prog=$conn->query("SELECT program_code FROM academic_programs WHERE id=$ajaxPid")->fetch_assoc();
        $code=$prog['program_code']??'';
        $data=[];$r=$conn->query("SELECT id,course_code,course_title,credits,year_of_study,semester FROM academic_course_catalog WHERE program_code='$code' ORDER BY year_of_study,semester,course_code");
        if($r) while($row=$r->fetch_assoc()) $data[]=$row;
        echo json_encode($data); exit;
    }
    echo json_encode([]); exit;
}

// ── POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_student_deputy') {
        $fn  = $students_conn->real_escape_string(trim($_POST['first_name'] ?? ''));
        $sn  = $students_conn->real_escape_string(trim($_POST['surname'] ?? ''));
        $on  = $students_conn->real_escape_string(trim($_POST['other_name'] ?? ''));
        $gen = $students_conn->real_escape_string(trim($_POST['gender'] ?? 'Female'));
        $crs = $students_conn->real_escape_string(trim($_POST['course'] ?? ''));
        $yr  = intval($_POST['year'] ?? 1);
        $sem = $students_conn->real_escape_string(trim($_POST['semester'] ?? 'Semester 1'));
        $ph  = $students_conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $em  = $students_conn->real_escape_string(trim($_POST['email'] ?? ''));
        $gn  = $students_conn->real_escape_string(trim($_POST['guardian_name'] ?? ''));
        $gp  = $students_conn->real_escape_string(trim($_POST['guardian_phone'] ?? ''));
        if($fn && $sn && $crs && $students_conn){
            $snum = 'STU'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $full = trim("$fn $on $sn");
            $students_conn->query("INSERT INTO students (student_number,first_name,surname,other_name,full_name,gender,course,program,current_year,year,current_semester,phone,mobile_number,email,guardian_name,guardian_phone,status,created_at) VALUES ('$snum','$fn','$sn','$on','$full','$gen','$crs','$crs',$yr,$yr,'$sem','$ph','$ph','$em','$gn','$gp','Active',NOW())");
            if($students_conn->affected_rows>0){ $_SESSION['success']="Student $full registered."; }
            else { $_SESSION['error']='Failed: '.$students_conn->error; }
        } else { $_SESSION['error']='Required fields missing.'; }
        header("Location: deputy-principal.php"); exit;
    }

    if ($action === 'schedule_class') {
        $prog=$conn->real_escape_string($_POST['program_code']??'');
        $cc=$conn->real_escape_string($_POST['course_code']??'');
        $dow=$conn->real_escape_string($_POST['day_of_week']??'');
        $st=$_POST['start_time']??'';
        $et=$_POST['end_time']??'';
        $venue=$conn->real_escape_string($_POST['venue']??'');
        $lid=intval($_POST['lecturer_id']??0);
        $ay=$conn->real_escape_string($_POST['academic_year']??date('Y').'-'.(date('Y')+1));
        $sem=$conn->real_escape_string($_POST['semester']??'Semester 1');
        $tid='TT-'.date('Ymd').'-'.mt_rand(1000,9999);
        $conn->query("INSERT INTO academic_timetable (timetable_id,academic_year,semester,program_code,course_code,day_of_week,start_time,end_time,venue,lecturer_id,created_by) VALUES ('$tid','$ay','$sem','$prog','$cc','$dow','$st','$et','$venue',$lid,$user_id)");
        if($conn->affected_rows>0){ $_SESSION['success']="Class scheduled: $cc ($dow $st-$et)"; }
        else { $_SESSION['error']='Failed to schedule: '.$conn->error; }
        header("Location: deputy-principal.php"); exit;
    }

    if ($action === 'assign_lecturer') {
        $lid=intval($_POST['lecturer_id']??0);
        $cc=$conn->real_escape_string($_POST['course_code']??'');
        $cn=$conn->real_escape_string($_POST['course_name']??'');
        $sem=$conn->real_escape_string($_POST['semester']??'Semester 1');
        $ay=$conn->real_escape_string($_POST['academic_year']??date('Y').'-'.(date('Y')+1));
        $rm=$conn->real_escape_string($_POST['classroom']??'');
        $conn->query("INSERT INTO course_assignments (lecturer_id,course_code,course_name,semester,academic_year,classroom,assigned_by) VALUES ($lid,'$cc','$cn','$sem','$ay','$rm',$user_id)");
        if($conn->affected_rows>0){ $_SESSION['success']="Lecturer assigned to $cn"; }
        else { $_SESSION['error']='Assignment failed: '.$conn->error; }
        header("Location: deputy-principal.php"); exit;
    }

    if ($action === 'upload_material') {
        $title=$conn->real_escape_string($_POST['material_title']??'');
        $dtype=$conn->real_escape_string($_POST['document_type']??'Teaching Material');
        if($title && isset($_FILES['material_file']) && $_FILES['material_file']['error']===UPLOAD_ERR_OK){
            $dir=__DIR__.'/../uploads/teaching_materials';
            if(!is_dir($dir)) @mkdir($dir,0755,true);
            $ext=strtolower(pathinfo($_FILES['material_file']['name'],PATHINFO_EXTENSION));
            $fname=time().'_'.preg_replace('/[^a-z0-9]/i','_',$title).'.'.$ext;
            $dest=$dir.'/'.$fname;
            if(move_uploaded_file($_FILES['material_file']['tmp_name'],$dest)){
                $fpath="uploads/teaching_materials/$fname";
                $conn->query("INSERT INTO generated_documents (document_type,generated_by,document_title,file_path) VALUES ('$dtype',$user_id,'$title','$fpath')");
                $_SESSION['success']="Material '$title' uploaded.";
            } else { $_SESSION['error']='Upload failed.'; }
        } else { $_SESSION['error']='Title and file required.'; }
        header("Location: deputy-principal.php"); exit;
    }

    if ($action === 'clinical_placement') {
        $sid=intval($_POST['student_id']??0);
        $site=$students_conn->real_escape_string($_POST['placement_site']??'');
        $sup=$students_conn->real_escape_string($_POST['supervisor_name']??'');
        $sd=$_POST['start_date']??'';
        $ed=$_POST['end_date']??'';
        if($students_conn && $sid>0 && $site){
            $students_conn->query("INSERT INTO clinical_placements_students (student_id,placement_site,supervisor_name,start_date,end_date,status) VALUES ($sid,'$site','$sup','$sd','$ed','Scheduled')");
            if($students_conn->affected_rows>0){ $_SESSION['success']='Clinical placement created.'; }
            else { $_SESSION['error']='Placement failed: '.$students_conn->error; }
        } else { $_SESSION['error']='Student and site required.'; }
        header("Location: deputy-principal.php"); exit;
    }

    if ($action === 'clinical_evaluation') {
        $pid=intval($_POST['placement_id']??0);
        $score=floatval($_POST['competency_score']??0);
        $eval=$students_conn->real_escape_string($_POST['evaluation']??'');
        if($students_conn && $pid>0){
            $students_conn->query("UPDATE clinical_placements_students SET competency_score=$score, supervisor_evaluation='$eval', logbook_submitted=1, status='Completed' WHERE id=$pid");
            $_SESSION['success']='Evaluation recorded.';
        } else { $_SESSION['error']='Placement ID required.'; }
        header("Location: deputy-principal.php"); exit;
    }

    if ($action === 'schedule_exam') {
        $cc=$conn->real_escape_string($_POST['course_code']??'');
        $etype=$conn->real_escape_string($_POST['exam_type']??'');
        $sd=$_POST['exam_date']??'';
        $rm=$conn->real_escape_string($_POST['exam_room']??'');
        $prog=$conn->real_escape_string($_POST['program_code']??'');
        $sem=$conn->real_escape_string($_POST['semester']??'Semester 1');
        $ay=$conn->real_escape_string($_POST['academic_year']??date('Y').'-'.(date('Y')+1));
        $en='EXM-'.date('Ymd').'-'.mt_rand(1000,9999);
        $conn->query("INSERT INTO examination_records (exam_number,exam_type,course_code,semester,academic_year,program_code,exam_date,exam_room,status,created_by) VALUES ('$en','$etype','$cc','$sem','$ay','$prog','$sd','$rm','Scheduled',$user_id)");
        if($conn->affected_rows>0){ $_SESSION['success']="Exam $en scheduled."; }
        else { $_SESSION['error']='Failed: '.$conn->error; }
        header("Location: deputy-principal.php"); exit;
    }

    if ($action === 'record_attendance') {
        $sid=intval($_POST['student_id']??0);
        $dt=$_POST['attendance_date']??date('Y-m-d');
        $st=$students_conn->real_escape_string($_POST['attendance_status']??'Present');
        $sub=$students_conn->real_escape_string($_POST['subject']??'General');
        if($students_conn && $sid>0){
            $students_conn->query("INSERT INTO student_attendance (student_id,date,subject,status,recorded_by) VALUES ($sid,'$dt','$sub','$st',$user_id)");
            $_SESSION['success']='Attendance recorded.';
        } else { $_SESSION['error']='Student required.'; }
        header("Location: deputy-principal.php"); exit;
    }

    header("Location: deputy-principal.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.btn-outline-purple { color:#8b5cf6; border-color:#8b5cf6; }
.btn-outline-purple:hover { color:#fff; background:#8b5cf6; border-color:#8b5cf6; }
.modal-content { max-height:80vh; overflow-y:auto; }
</style>
</head>
<body>
    <div class="dashboard-container">
        <?php include_once '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Deputy Principal Dashboard</h1>
                    <p>Assistant Academic Officer & Student Support</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span id="currentDate"></span>
                    </div>
                    <div class="user-menu">
                        <img src="<?= $profileImageUrl ?>" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo $user['first_name'] ?? 'User'; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </header>

            <?php if(!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show m-3 py-2"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); endif; ?>
            <?php if(!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show m-3 py-2"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['error']); endif; ?>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Academic Overview -->
                <section id="overview" class="content-section dashboard-section active" data-section="overview">
                    <h2>Academic Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-content">
                                <h3><?= $total_students ?></h3>
                                <p>Total Students</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="stat-content">
                                <h3><?= $lecturers ?></h3>
                                <p>Lecturers</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-book"></i></div>
                            <div class="stat-content">
                                <h3><?= $active_courses ?></h3>
                                <p>Active Courses</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                            <div class="stat-content">
                                <h3><?= $avg_attendance ?>%</h3>
                                <p>Avg Attendance</p>
                            </div>
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
                        <a href="../dashboards/school-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-chalkboard-teacher me-1"></i>School Principal</a>
                        <a href="../dashboards/director-academics.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-graduation-cap me-1"></i>Director Academics</a>
                        <a href="../dashboards/academic-registrar.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-alt me-1"></i>Academic Registrar</a>
                        <a href="../dashboards/head-nursing.php" class="btn btn-outline-success btn-sm"><i class="fas fa-heartbeat me-1"></i>Head of Nursing</a>
                        <a href="../dashboards/head-midwifery.php" class="btn btn-outline-success btn-sm"><i class="fas fa-user-md me-1"></i>Head of Midwifery</a>
                        <a href="../dashboards/lecturers.php" class="btn btn-outline-info btn-sm"><i class="fas fa-chalkboard me-1"></i>Lecturers</a>
                        <a href="../dashboards/matrons.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-user-shield me-1"></i>Matrons</a>
                        <a href="../dashboards/wardens.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-door-open me-1"></i>Wardens</a>
                        <a href="../student-directory.php" class="btn btn-outline-info btn-sm"><i class="fas fa-address-book me-1"></i>Student Directory</a>
                        <a href="../dashboards/staff_transcript_generation.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-file-alt me-1"></i>Transcripts</a>
                    </div>
                </section>

                <!-- Teaching & Learning -->
                <section id="teaching" class="content-section dashboard-section" data-section="teaching">
                    <h2>Teaching & Learning</h2>
                    <div class="teaching-actions">
                        <button class="btn btn-primary" onclick="openModal('scheduleClass')"><i class="fas fa-calendar-plus"></i> Schedule Class</button>
                        <button class="btn btn-success" onclick="openModal('assignLecturer')"><i class="fas fa-user-plus"></i> Assign Lecturer</button>
                        <button class="btn btn-info" onclick="openModal('curriculumReview')"><i class="fas fa-book-open"></i> Curriculum Review</button>
                        <button class="btn btn-warning" onclick="openModal('teachingMaterials')"><i class="fas fa-file-alt"></i> Teaching Materials</button>
                    </div>
                    <div class="teaching-overview">
                        <h3>Current Teaching Schedule</h3>
                        <?php if(empty($timetable_entries)): ?>
                        <p class="text-muted">No classes scheduled yet.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead><tr><th>Course</th><th>Day</th><th>Time</th><th>Venue</th><th>Lecturer</th><th>Year</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach($timetable_entries as $t): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($t['course_code']) ?></strong></td>
                                    <td><?= $t['day_of_week'] ?></td>
                                    <td><?= $t['start_time'] ?>-<?= $t['end_time'] ?></td>
                                    <td><?= htmlspecialchars($t['venue']??'-') ?></td>
                                    <td><?= htmlspecialchars($t['lecturer_name']??'-') ?></td>
                                    <td><?= htmlspecialchars($t['academic_year']) ?></td>
                                    <td><span class="badge bg-<?= $t['timetable_status']==='Published'?'success':($t['timetable_status']==='Approved'?'info':'secondary') ?>"><?= $t['timetable_status'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Student Affairs -->
                <section id="students" class="content-section dashboard-section" data-section="students">
                    <h2>Student Affairs</h2>
                    <div class="student-actions">
                        <button class="btn btn-primary" onclick="openModal('studentRegistration')"><i class="fas fa-user-plus"></i> Student Registration</button>
                        <button class="btn btn-success" onclick="openModal('studentRecords')"><i class="fas fa-folder"></i> Student Records</button>
                        <button class="btn btn-info" onclick="openModal('attendanceTracking')"><i class="fas fa-user-check"></i> Attendance Tracking</button>
                        <button class="btn btn-warning" onclick="openModal('studentPerformance')"><i class="fas fa-chart-line"></i> Performance Analysis</button>
                    </div>
                    <div class="student-overview">
                        <h3>Student Performance Overview</h3>
                        <div class="performance-grid">
                            <?php foreach($program_list as $prog):
                                $pcode = $conn->real_escape_string($prog['program_code']);
                                $pname = $prog['program_name'];
                                $count = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students WHERE course='$pname' AND status='Active'") : 0;
                                $avgGpa = '-';
                                $passRate = '-';
                            ?>
                            <div class="performance-card">
                                <h4><?= htmlspecialchars($pname) ?></h4>
                                <div class="performance-metrics">
                                    <div class="metric"><span>Total Students:</span><strong><?= $count ?></strong></div>
                                    <div class="metric"><span>Duration:</span><strong><?= $prog['duration_years'] ?> Years</strong></div>
                                    <div class="metric"><span>Status:</span><strong class="text-<?= $prog['status']==='Active'?'success':'secondary' ?>"><?= $prog['status'] ?></strong></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- Examinations -->
                <section id="examinations" class="content-section dashboard-section" data-section="examinations">
                    <h2>Examination Management</h2>
                    <div class="exam-actions">
                        <button class="btn btn-primary" onclick="openModal('scheduleExam')"><i class="fas fa-calendar-plus"></i> Schedule Exam</button>
                        <button class="btn btn-success" onclick="openModal('examResults')"><i class="fas fa-chart-bar"></i> Exam Results</button>
                        <button class="btn btn-info" onclick="openModal('examAnalysis')"><i class="fas fa-analytics"></i> Performance Analysis</button>
                        <button class="btn btn-warning" onclick="openModal('examReports')"><i class="fas fa-file-alt"></i> Generate Reports</button>
                    </div>
                    <div class="exam-overview">
                        <h3>Upcoming Examinations</h3>
                        <?php
                        $exams = []; $r=$conn->query("SELECT exam_number,exam_type,course_code,semester,academic_year,exam_date,exam_room,status FROM examination_records WHERE exam_date >= CURDATE() OR status='Scheduled' ORDER BY exam_date LIMIT 10");
                        if($r) while($row=$r->fetch_assoc()) $exams[]=$row;
                        if(empty($exams)): ?>
                        <p class="text-muted">No exams scheduled.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead><tr><th>Exam</th><th>Type</th><th>Course</th><th>Date</th><th>Room</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach($exams as $e): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($e['exam_number']) ?></code></td>
                                    <td><?= htmlspecialchars($e['exam_type']) ?></td>
                                    <td><?= htmlspecialchars($e['course_code']) ?></td>
                                    <td><?= $e['exam_date'] ?></td>
                                    <td><?= htmlspecialchars($e['exam_room']??'-') ?></td>
                                    <td><span class="badge bg-<?= $e['status']==='Published'?'success':($e['status']==='Scheduled'?'warning':'info') ?>"><?= $e['status'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Clinical Training -->
                <section id="clinical" class="content-section dashboard-section" data-section="clinical">
                    <h2>Clinical Training</h2>
                    <div class="clinical-actions">
                        <button class="btn btn-primary" onclick="openModal('clinicalPlacement')"><i class="fas fa-hospital"></i> Clinical Placement</button>
                        <button class="btn btn-success" onclick="openModal('clinicalEvaluation')"><i class="fas fa-clipboard-check"></i> Clinical Evaluation</button>
                        <button class="btn btn-info" onclick="openModal('clinicalSites')"><i class="fas fa-map-marked-alt"></i> Clinical Sites</button>
                        <button class="btn btn-warning" onclick="openModal('clinicalReports')"><i class="fas fa-file-medical"></i> Clinical Reports</button>
                    </div>
                    <div class="clinical-overview">
                        <h3>Clinical Placements Overview</h3>
                        <?php if(empty($placements)): ?>
                        <p class="text-muted">No clinical placements yet.</p>
                        <?php else: ?>
                        <div class="placements-grid">
                            <?php
                            $sites = [];
                            foreach($placements as $p){
                                $key = $p['placement_site'];
                                if(!isset($sites[$key])) $sites[$key] = ['count'=>0, 'status'=>$p['status']];
                                $sites[$key]['count']++;
                            }
                            foreach($sites as $site=>$info): ?>
                            <div class="placement-card">
                                <div class="placement-header">
                                    <h4><?= htmlspecialchars($site) ?></h4>
                                    <span class="status-badge <?= $info['status']==='Active'?'active':'scheduled' ?>"><?= $info['status'] ?: 'Scheduled' ?></span>
                                </div>
                                <div class="placement-details">
                                    <div class="detail"><span>Students:</span><strong><?= $info['count'] ?></strong></div>
                                    <div class="detail"><span>Status:</span><strong><?= htmlspecialchars($info['status']?:'Scheduled') ?></strong></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- News Management -->
                <section class="activities-section">
                    <h2><i class="fas fa-newspaper me-2"></i>News Management</h2>
                    <?php renderNewsWidget($conn, $website_conn, $user['id'] ?? 0, $user['full_name'] ?? 'Deputy Principal', $user['role'] ?? 'Deputy Principal', 5); ?>
                </section>

                <!-- Student Records -->
                <section id="student-records" class="activities-section dashboard-section" data-section="student-records">
                    <?php renderStudentSetViewer($students_conn, [
                        'title' => 'Student Records',
                        'icon' => 'fa-user-graduate',
                        'show_all' => true,
                        'per_page' => 50,
                        'show_statement_link' => false
                    ]); ?>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Academic Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="activity-content">
                                <p><strong><?= htmlspecialchars($activity['activity'] ?? 'Activity') ?></strong></p>
                                <small><?= date('M j, Y H:i', strtotime($activity['created_at'])) ?></small>
                            </div>
                        </div>
                        <?php endforeach; if(empty($recent_activities)): ?>
                        <p class="text-muted">No recent activities.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Hierarchy, Alerts, Performance, Approvals -->
    <div class="container-fluid px-4 pb-4">
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="section-card h-100">
                    <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-sitemap me-2 text-info"></i>Your Position in Hierarchy</h6>
                    <div class="d-flex align-items-center gap-2 mb-2 small">
                        <span class="badge bg-primary">Level 3</span>
                        <span class="text-muted">You report to:</span>
                        <span class="fw-semibold">School Principal (Level 2)</span>
                    </div>
                    <?= renderHierarchyChart($conn) ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100">
                    <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-bell me-2 text-danger"></i>Department Alerts</h6>
                    <?= renderAlertsPanel($conn, 'ACA', 5) ?>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="section-card h-100">
                    <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-chart-bar me-2 text-success"></i>Department Performance</h6>
                    <?php
                    $depStaffId = 0;
                    $sq = $conn ? $conn->prepare("SELECT id FROM staff WHERE position LIKE '%Deputy%' AND status='Active' LIMIT 1") : false;
                    if ($sq) { $sq->execute(); $sr = $sq->get_result()->fetch_assoc(); $sq->close(); if ($sr) $depStaffId = $sr['id']; }
                    echo renderDirectorPerformanceCard($depStaffId, 0, 'Deputy Principal', $conn);
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100">
                    <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-check-double me-2 text-primary"></i>Pending Approvals</h6>
                    <?php
                    $pendingApprovals = getPendingApprovals($conn, 0, 5);
                    if (!empty($pendingApprovals)):
                        foreach ($pendingApprovals as $apr):
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

    <!-- Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="modalForm" enctype="multipart/form-data">
                    <div class="modal-body" id="modalBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalActionBtn">Save</button>
                    </div>
                </form>
            </div>
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
                <div class="modal-body" id="studentProfileBody">
                    <div class="text-center py-4"><em>Loading...</em></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="printStudentProfile()"><i class="fas fa-print"></i> Print</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateDateTime() {
        const now = new Date();
        document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);



    function openModal(action) {
        const modal = new bootstrap.Modal(document.getElementById('actionModal'));
        const title = document.getElementById('modalTitle');
        const body = document.getElementById('modalBody');
        const form = document.getElementById('modalForm');
        const submitBtn = document.getElementById('modalActionBtn');

        form.onsubmit = function(e){ return true; };
        form.enctype = 'application/x-www-form-urlencoded';

        switch(action) {
            // ─── Schedule Class ───
            case 'scheduleClass':
                title.textContent = 'Schedule Class';
                body.innerHTML = `<input type="hidden" name="action" value="schedule_class">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Program</label><select name="program_code" class="form-select" required>
                            <option value="">Select Program</option>
                            <?php foreach($program_list as $p): ?><option value="<?= htmlspecialchars($p['program_code']) ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-4"><label class="form-label">Course</label><select name="course_code" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach($course_list as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?> – <?= htmlspecialchars($c['course_title']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-4"><label class="form-label">Day</label><select name="day_of_week" class="form-select" required>
                            <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option>
                        </select></div>
                        <div class="col-md-3"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Venue</label><input type="text" name="venue" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Lecturer</label><select name="lecturer_id" class="form-select">
                            <option value="">Select</option>
                            <?php foreach($lecturer_list as $l): ?><option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['full_name']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-4"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?= date('Y').'-'.(date('Y')+1) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select></div>
                    </div>`;
                submitBtn.textContent = 'Schedule Class';
                break;

            // ─── Assign Lecturer ───
            case 'assignLecturer':
                title.textContent = 'Assign Lecturer to Course';
                body.innerHTML = `<input type="hidden" name="action" value="assign_lecturer">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Lecturer</label><select name="lecturer_id" class="form-select" required>
                            <option value="">Select Lecturer</option>
                            <?php foreach($lecturer_list as $l): ?><option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['full_name']) ?> (<?= htmlspecialchars($l['position']) ?>)</option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-6"><label class="form-label">Course</label><select name="course_code" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach($course_list as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?> – <?= htmlspecialchars($c['course_title']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-6"><label class="form-label">Course Name</label><input type="text" name="course_name" class="form-control" required placeholder="e.g. Nursing Fundamentals"></div>
                        <div class="col-md-3"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?= date('Y').'-'.(date('Y')+1) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select></div>
                        <div class="col-md-6"><label class="form-label">Classroom</label><input type="text" name="classroom" class="form-control" placeholder="e.g. Room 101"></div>
                    </div>`;
                submitBtn.textContent = 'Assign Lecturer';
                break;

            // ─── Curriculum Review ───
            case 'curriculumReview':
                title.textContent = 'Curriculum Review';
                let html = `<ul class="nav nav-tabs mb-3" id="curriculumTabs">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#progTab">Programs</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#courseTab">Courses</a></li>
                </ul><div class="tab-content">
                    <div class="tab-pane fade show active" id="progTab">
                        <table class="table table-sm table-hover"><thead><tr><th>Code</th><th>Program</th><th>Type</th><th>Department</th><th>Duration</th><th>Status</th></tr></thead><tbody>`;
                <?php foreach($program_list as $p): ?>
                html += `<tr><td><?= htmlspecialchars($p['program_code']) ?></td><td><?= htmlspecialchars($p['program_name']) ?></td><td><?= $p['program_type'] ?></td><td><?= htmlspecialchars($p['department']) ?></td><td><?= $p['duration_years'] ?> yrs</td><td><span class="badge bg-<?= $p['status']==='Active'?'success':'secondary' ?>"><?= $p['status'] ?></span></td></tr>`;
                <?php endforeach; ?>
                html += `</tbody></table></div>
                    <div class="tab-pane fade" id="courseTab">
                        <table class="table table-sm table-hover"><thead><tr><th>Code</th><th>Title</th><th>Credits</th><th>Program</th><th>Year</th><th>Semester</th></tr></thead><tbody>`;
                <?php foreach($course_list as $c): ?>
                html += `<tr><td><?= htmlspecialchars($c['course_code']) ?></td><td><?= htmlspecialchars($c['course_title']) ?></td><td><?= $c['credits'] ?></td><td><?= htmlspecialchars($c['program_code']??'-') ?></td><td><?= $c['year_of_study']??'-' ?></td><td><?= htmlspecialchars($c['semester']??'-') ?></td></tr>`;
                <?php endforeach; ?>
                html += `</tbody></table></div></div>`;
                body.innerHTML = html;
                submitBtn.parentElement.style.display = 'none';
                setTimeout(() => { if(typeof bootstrap !== 'undefined'){ document.querySelectorAll('#curriculumTabs a').forEach(t=>{ t.addEventListener('click',e=>{ e.preventDefault(); new bootstrap.Tab(t).show(); }); }); } }, 100);
                return modal.show();
                break;

            // ─── Teaching Materials ───
            case 'teachingMaterials':
                title.textContent = 'Teaching Materials';
                form.enctype = 'multipart/form-data';
                html = `<input type="hidden" name="action" value="upload_material">
                    <h6>Upload New Material</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label">Title</label><input type="text" name="material_title" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Type</label><select name="document_type" class="form-select"><option>Teaching Material</option><option>Lecture Notes</option><option>Curriculum</option><option>Assignment</option><option>Other</option></select></div>
                        <div class="col-md-12"><label class="form-label">File</label><input type="file" name="material_file" class="form-control" required></div>
                    </div>
                    <h6>Existing Materials</h6>`;
                <?php if(empty($materials)): ?>
                html += `<p class="text-muted">No materials uploaded yet.</p>`;
                <?php else: ?>
                html += `<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Title</th><th>Type</th><th>Uploaded By</th><th>Date</th><th>Action</th></tr></thead><tbody>`;
                <?php foreach($materials as $m): ?>
                html += `<tr><td><?= htmlspecialchars($m['document_title']) ?></td><td><?= htmlspecialchars($m['document_type']) ?></td><td><?= htmlspecialchars($m['staff_name']??'-') ?></td><td><?= $m['generation_date'] ?></td><td><?= $m['file_path'] ? '<a href="../'.htmlspecialchars($m['file_path']).'" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a>' : '-' ?></td></tr>`;
                <?php endforeach; ?>
                html += `</tbody></table></div>`;
                <?php endif; ?>
                body.innerHTML = html;
                submitBtn.textContent = 'Upload Material';
                submitBtn.parentElement.style.display = '';
                break;

            // ─── Student Registration ───
            case 'studentRegistration':
                title.textContent = 'Student Registration';
                body.innerHTML = `<input type="hidden" name="action" value="studentRegistration">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Surname *</label><input type="text" name="surname" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Other Names</label><input type="text" name="other_name" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><option>Female</option><option>Male</option></select></div>
                        <div class="col-md-4"><label class="form-label">Program *</label><select name="course" class="form-select" required>
                            <option value="">Select Program</option>
                            <?php foreach($program_list as $p): ?><option value="<?= htmlspecialchars($p['program_name']) ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-3"><label class="form-label">Year</label><select name="year" class="form-select"><option value="1">Year 1</option><option value="2">Year 2</option><option value="3">Year 3</option></select></div>
                        <div class="col-md-3"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select></div>
                        <div class="col-md-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Guardian Name</label><input type="text" name="guardian_name" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Guardian Phone</label><input type="text" name="guardian_phone" class="form-control"></div>
                    </div>`;
                submitBtn.textContent = 'Register Student';
                form.onsubmit = function() {
                    const fd = new FormData(this);
                    fd.set('action', 'add_student_deputy');
                    fetch('deputy-principal.php', { method:'POST', body:fd }).then(r=>{ window.location.reload(); });
                    return false;
                };
                break;

            // ─── Student Records ───
            case 'studentRecords':
                title.textContent = 'Student Records';
                <?php
                $students = [];
                if($students_conn){ $r=$students_conn->query("SELECT id,student_number,registration_number,full_name,first_name,surname,course,current_year,gender,phone,mobile_number,email,status,index_number,national_student_id_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
                if($r) while($row=$r->fetch_assoc()) $students[]=$row; }
                ?>
                html = `<div class="mb-2"><input type="text" id="studentSearchInput" class="form-control form-control-sm" placeholder="Search by name, reg no, phone..." onkeyup="filterStudentTable()"></div>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                    <table class="table table-sm table-hover" id="studentRecordsTable"><thead><tr><th>Reg No</th><th>Name</th><th>Program</th><th>Year</th><th>Phone/Mobile</th><th>Index No</th><th>Actions</th></tr></thead><tbody>`;
                <?php foreach($students as $s):
                    $sname = htmlspecialchars($s['full_name'] ?: trim($s['first_name'].' '.$s['surname']));
                    $sreg = htmlspecialchars($s['registration_number'] ?: $s['student_number']);
                    $sphone = htmlspecialchars($s['phone']??'');
                    $smobile = htmlspecialchars($s['mobile_number']??'');
                    $sindex = htmlspecialchars($s['index_number']??$s['national_student_id_number']??'');
                ?>
                html += `<tr>
                    <td><code><?= $sreg ?></code></td>
                    <td><?= $sname ?></td>
                    <td><?= htmlspecialchars($s['course']??'-') ?></td>
                    <td><?= $s['current_year']??'-' ?></td>
                    <td><?= $sphone ?><?= ($sphone && $smobile && $smobile!==$sphone) ? '<br><small class="text-muted">M: '.$smobile.'</small>' : ($smobile ? '<br><small class="text-muted">'.$smobile.'</small>' : '-') ?></td>
                    <td><?= $sindex ?: '-' ?></td>
                    <td><button class="btn btn-sm btn-outline-info" onclick="viewStudentProfile(<?= $s['id'] ?>)"><i class="fas fa-eye"></i></button>
                    <a href="../print-student.php?id=<?= $s['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></a>
                    <button class="btn btn-sm btn-outline-warning" onclick="openEditStudentModal(<?= $s['id'] ?>)"><i class="fas fa-edit"></i></button></td>
                </tr>`;
                <?php endforeach; ?>
                html += `</tbody></table></div>`;
                body.innerHTML = html;
                submitBtn.parentElement.style.display = 'none';
                break;

            // ─── Attendance Tracking ───
            case 'attendanceTracking':
                title.textContent = 'Record Attendance';
                body.innerHTML = `<input type="hidden" name="action" value="record_attendance">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Student</label><select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php if($students_conn){ $sr=$students_conn->query("SELECT id,full_name,student_number,registration_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
                            if($sr) while($s=$sr->fetch_assoc()): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name'] ?: $s['student_number']) ?></option><?php endwhile; } ?>
                        </select></div>
                        <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Status</label><select name="attendance_status" class="form-select">
                            <option>Present</option><option>Absent</option><option>Late</option><option>Excused</option>
                        </select></div>
                        <div class="col-md-12"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" placeholder="e.g. Nursing Fundamentals"></div>
                    </div>`;
                submitBtn.textContent = 'Record Attendance';
                break;

            // ─── Performance Analysis ───
            case 'studentPerformance':
                title.textContent = 'Performance Analysis';
                form.onsubmit = function(){ return false; };
                html = `<div class="row g-3">
                    <?php foreach($program_list as $p):
                        $pname = $conn->real_escape_string($p['program_name']);
                        $cnt = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students WHERE course='$pname' AND status='Active'") : 0;
                    ?>
                    <div class="col-md-6">
                        <div class="card card-body">
                            <h6><?= htmlspecialchars($p['program_name']) ?></h6>
                            <div class="d-flex justify-content-between"><span>Students:</span><strong><?= $cnt ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Duration:</span><strong><?= $p['duration_years'] ?> years</strong></div>
                            <div class="d-flex justify-content-between"><span>Type:</span><strong><?= $p['program_type'] ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Status:</span><strong class="text-<?= $p['status']==='Active'?'success':'secondary' ?>"><?= $p['status'] ?></strong></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>`;
                body.innerHTML = html;
                submitBtn.parentElement.style.display = 'none';
                break;

            // ─── Schedule Exam ───
            case 'scheduleExam':
                title.textContent = 'Schedule Exam';
                body.innerHTML = `<input type="hidden" name="action" value="schedule_exam">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Course</label><select name="course_code" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach($course_list as $c): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-4"><label class="form-label">Exam Type</label><select name="exam_type" class="form-select">
                            <option>Mid Semester</option><option>End of Semester</option><option>Supplementary</option><option>Practical</option>
                        </select></div>
                        <div class="col-md-4"><label class="form-label">Program</label><select name="program_code" class="form-select">
                            <option value="">All Programs</option>
                            <?php foreach($program_list as $p): ?><option value="<?= htmlspecialchars($p['program_code']) ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-4"><label class="form-label">Exam Date</label><input type="date" name="exam_date" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Room</label><input type="text" name="exam_room" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option></select></div>
                        <div class="col-md-6"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?= date('Y').'-'.(date('Y')+1) ?>"></div>
                    </div>`;
                submitBtn.textContent = 'Schedule Exam';
                break;

            // ─── Exam Results ───
            case 'examResults':
                title.textContent = 'Exam Results';
                form.onsubmit = function(){ return false; };
                <?php
                $examsAll = []; $r=$conn->query("SELECT exam_number,exam_type,course_code,exam_date,status FROM examination_records ORDER BY created_at DESC LIMIT 50");
                if($r) while($row=$r->fetch_assoc()) $examsAll[]=$row;
                ?>
                html = `<p class="text-muted">Click "View Results" to enter marks for an exam.</p>
                    <div class="table-responsive" style="max-height:350px;overflow-y:auto">
                    <table class="table table-sm table-hover"><thead><tr><th>Exam</th><th>Type</th><th>Course</th><th>Date</th><th>Status</th></tr></thead><tbody>`;
                <?php foreach($examsAll as $e): ?>
                html += `<tr><td><code><?= htmlspecialchars($e['exam_number']) ?></code></td><td><?= htmlspecialchars($e['exam_type']) ?></td><td><?= htmlspecialchars($e['course_code']) ?></td><td><?= $e['exam_date'] ?></td><td><span class="badge bg-<?= $e['status']==='Published'?'success':'warning' ?>"><?= $e['status'] ?></span></td></tr>`;
                <?php endforeach; ?>
                html += `</tbody></table></div>`;
                body.innerHTML = html;
                submitBtn.parentElement.style.display = 'none';
                break;

            // ─── Exam Analysis ───
            case 'examAnalysis':
                title.textContent = 'Performance Analysis';
                form.onsubmit = function(){ return false; };
                <?php
                $passStats = [];
                $r=$conn->query("SELECT course_code,COUNT(*) total,SUM(CASE WHEN grade IN('A','B','C','D') THEN 1 ELSE 0 END) passed FROM academic_records WHERE assessment_type='Exam' GROUP BY course_code LIMIT 20");
                if($r) while($row=$r->fetch_assoc()) $passStats[]=$row;
                ?>
                html = `<?php if(empty($passStats)): ?><p class="text-muted">No exam results available for analysis.</p><?php else: ?>
                    <table class="table table-sm table-hover"><thead><tr><th>Course</th><th>Total</th><th>Passed</th><th>Pass Rate</th></tr></thead><tbody>
                    <?php foreach($passStats as $ps): $pr = $ps['total']>0 ? round(($ps['passed']/$ps['total'])*100,1) : 0; ?>
                    <tr><td><?= htmlspecialchars($ps['course_code']) ?></td><td><?= $ps['total'] ?></td><td><?= $ps['passed'] ?></td><td><strong><?= $pr ?>%</strong></td></tr>
                    <?php endforeach; ?>
                    </tbody></table><?php endif; ?>`;
                body.innerHTML = html;
                submitBtn.parentElement.style.display = 'none';
                break;

            // ─── Exam Reports ───
            case 'examReports':
                title.textContent = 'Generate Reports';
                form.onsubmit = function(){ return false; };
                body.innerHTML = `<div class="row g-3">
                    <div class="col-md-6">
                        <div class="card card-body text-center" style="cursor:pointer" onclick="window.open('deputy-principal.php?report=exam_performance','_blank')">
                            <i class="fas fa-chart-bar fa-3x mb-2" style="color:var(--primary)"></i>
                            <strong>Exam Performance Report</strong>
                            <small class="text-muted">View all exam results with pass rates</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-body text-center" style="cursor:pointer" onclick="window.open('deputy-principal.php?report=attendance_report','_blank')">
                            <i class="fas fa-calendar-check fa-3x mb-2" style="color:var(--primary)"></i>
                            <strong>Attendance Report</strong>
                            <small class="text-muted">Student attendance summary</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-body text-center" style="cursor:pointer" onclick="window.open('deputy-principal.php?report=student_list','_blank')">
                            <i class="fas fa-users fa-3x mb-2" style="color:var(--primary)"></i>
                            <strong>Student List</strong>
                            <small class="text-muted">All active students</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-body text-center" style="cursor:pointer" onclick="window.open('deputy-principal.php?report=clinical_report','_blank')">
                            <i class="fas fa-hospital fa-3x mb-2" style="color:var(--primary)"></i>
                            <strong>Clinical Report</strong>
                            <small class="text-muted">Clinical placements summary</small>
                        </div>
                    </div>
                </div>`;
                submitBtn.parentElement.style.display = 'none';
                break;

            // ─── Clinical Placement ───
            case 'clinicalPlacement':
                title.textContent = 'Clinical Placement';
                body.innerHTML = `<input type="hidden" name="action" value="clinical_placement">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Student</label><select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php if($students_conn){ $sr=$students_conn->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200");
                            if($sr) while($s=$sr->fetch_assoc()): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name'] ?: $s['student_number']) ?></option><?php endwhile; } ?>
                        </select></div>
                        <div class="col-md-6"><label class="form-label">Placement Site</label><input type="text" name="placement_site" class="form-control" required placeholder="e.g. Iganga Hospital"></div>
                        <div class="col-md-6"><label class="form-label">Supervisor Name</label><input type="text" name="supervisor_name" class="form-control" placeholder="e.g. PNO Iganga"></div>
                        <div class="col-md-3"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control"></div>
                    </div>`;
                submitBtn.textContent = 'Create Placement';
                break;

            // ─── Clinical Evaluation ───
            case 'clinicalEvaluation':
                title.textContent = 'Clinical Evaluation';
                form.onsubmit = function(){ return false; };
                <?php
                $eval_placements = $students_conn ? [] : [];
                if($students_conn){ $r=$students_conn->query("SELECT cp.id,cp.placement_site,cp.student_id,cp.status,cp.competency_score,s.full_name FROM clinical_placements_students cp LEFT JOIN students s ON cp.student_id=s.id WHERE cp.status IN('Active','Scheduled') ORDER BY cp.created_at DESC LIMIT 50");
                if($r) while($row=$r->fetch_assoc()) $eval_placements[]=$row; }
                ?>
                html = `<?php if(empty($eval_placements)): ?><p class="text-muted">No active placements for evaluation.</p><?php else: ?>
                    <div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Student</th><th>Site</th><th>Status</th><th>Score</th><th>Action</th></tr></thead><tbody>
                    <?php foreach($eval_placements as $ep): ?>
                    <tr>
                        <td><?= htmlspecialchars($ep['full_name']??'-') ?></td>
                        <td><?= htmlspecialchars($ep['placement_site']) ?></td>
                        <td><?= $ep['status'] ?></td>
                        <td><?= $ep['competency_score'] ?? '-' ?></td>
                        <td><button class="btn btn-sm btn-outline-success" onclick="evaluatePlacement(<?= $ep['id'] ?>, '<?= addslashes($ep['placement_site']) ?>')">Evaluate</button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody></table></div><?php endif; ?>`;
                body.innerHTML = html;
                submitBtn.parentElement.style.display = 'none';
                break;

            // ─── Clinical Sites ───
            case 'clinicalSites':
                title.textContent = 'Clinical Sites';
                form.onsubmit = function(){ return false; };
                <?php
                $sites = $students_conn ? [] : [];
                if($students_conn){ $r=$students_conn->query("SELECT DISTINCT placement_site,COUNT(*) cnt FROM clinical_placements_students GROUP BY placement_site ORDER BY cnt DESC");
                if($r) while($row=$r->fetch_assoc()) $sites[]=$row; }
                ?>
                html = `<?php if(empty($sites)): ?><p class="text-muted">No clinical sites recorded.</p><?php else: ?>
                    <table class="table table-sm table-hover"><thead><tr><th>Site</th><th>Students Assigned</th></tr></thead><tbody>
                    <?php foreach($sites as $st): ?>
                    <tr><td><strong><?= htmlspecialchars($st['placement_site']) ?></strong></td><td><?= $st['cnt'] ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table><?php endif; ?>`;
                body.innerHTML = html;
                submitBtn.parentElement.style.display = 'none';
                break;

            // ─── Clinical Reports ───
            case 'clinicalReports':
                title.textContent = 'Clinical Reports';
                form.onsubmit = function(){ return false; };
                body.innerHTML = `<div class="row g-3">
                    <div class="col-md-6">
                        <div class="card card-body text-center" style="cursor:pointer" onclick="window.open('deputy-principal.php?report=clinical_report','_blank')">
                            <i class="fas fa-file-medical fa-3x mb-2" style="color:var(--primary)"></i>
                            <strong>Clinical Placements Report</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-body text-center" style="cursor:pointer" onclick="window.open('deputy-principal.php?report=evaluation_report','_blank')">
                            <i class="fas fa-clipboard-check fa-3x mb-2" style="color:var(--primary)"></i>
                            <strong>Clinical Evaluation Report</strong>
                        </div>
                    </div>
                </div>`;
                submitBtn.parentElement.style.display = 'none';
                break;

            default:
                title.textContent = 'Coming Soon';
                body.innerHTML = '<p class="text-muted">This feature is being implemented.</p>';
                submitBtn.parentElement.style.display = 'none';
        }
        modal.show();
    }

    function viewStudentProfile(id){
        const modal = new bootstrap.Modal(document.getElementById('studentProfileModal'));
        document.getElementById('studentProfileBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading profile...</p></div>';
        modal.show();
        fetch('deputy-principal.php?ajax=student_profile&student_id='+id)
            .then(r=>r.json()).then(d=>{
                let info = d.info || {};
                let att = d.attendance || [];
                let inv = d.invoices || [];
                let pay = d.payments || [];
                let totalPaid = pay.reduce((s,p)=>s+parseFloat(p.amount_received||0),0);
                let totalInv = inv.reduce((s,iv)=>s+parseFloat(iv.total_amount||0),0);
                let attPres = att.filter(a=>a.status==='Present').length;
                let attRate = att.length>0 ? Math.round(attPres/att.length*100) : 0;
                document.getElementById('studentProfileBody').innerHTML = `
                    <ul class="nav nav-tabs mb-3" id="profileTabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#persTab">Personal</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#acadTab">Academic</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#finTab">Finance</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="persTab">
                            <div class="row g-2 small">
                                <div class="col-md-6"><strong>Name:</strong> ${info.full_name||''}</div>
                                <div class="col-md-6"><strong>Reg No:</strong> ${info.registration_number||info.student_number||'-'}</div>
                                <div class="col-md-6"><strong>Phone:</strong> ${info.phone||'-'}</div>
                                <div class="col-md-6"><strong>Email:</strong> ${info.email||'-'}</div>
                                <div class="col-md-6"><strong>Gender:</strong> ${info.gender||'-'}</div>
                                <div class="col-md-6"><strong>DOB:</strong> ${info.date_of_birth||'-'}</div>
                                <div class="col-md-6"><strong>Nationality:</strong> ${info.nationality||'-'}</div>
                                <div class="col-md-6"><strong>Guardian:</strong> ${info.guardian_name||info.parent_name||'-'}</div>
                                <div class="col-md-6"><strong>Guardian Phone:</strong> ${info.guardian_phone||info.parent_phone||'-'}</div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="acadTab">
                            <div class="row g-2 small">
                                <div class="col-md-4"><strong>Program:</strong> ${info.course||'-'}</div>
                                <div class="col-md-4"><strong>Year:</strong> ${info.current_year||'-'}</div>
                                <div class="col-md-4"><strong>Semester:</strong> ${info.current_semester||'-'}</div>
                                <div class="col-md-4"><strong>Set:</strong> ${info.set_name||'-'}</div>
                                <div class="col-md-4"><strong>Intake:</strong> ${info.intake_date||'-'}</div>
                                <div class="col-md-4"><strong>Status:</strong> <span class="badge bg-success">${info.status||'-'}</span></div>
                                <div class="col-12 mt-2"><strong>Attendance Rate:</strong> ${attRate}% (${attPres}/${att.length} days present)</div>
                                ${att.length>0 ? `<div class="col-12 mt-1"><table class="table table-sm table-bordered mt-1"><thead><tr><th>Date</th><th>Status</th></tr></thead><tbody>${att.map(a=>`<tr><td>${a.date}</td><td>${a.status}</td></tr>`).join('')}</tbody></table></div>` : ''}
                            </div>
                        </div>
                        <div class="tab-pane fade" id="finTab">
                            <div class="row g-2 small">
                                <div class="col-md-4"><strong>Total Invoiced:</strong> ${totalInv.toLocaleString()}</div>
                                <div class="col-md-4"><strong>Total Paid:</strong> ${totalPaid.toLocaleString()}</div>
                                <div class="col-md-4"><strong>Balance:</strong> ${(totalInv-totalPaid).toLocaleString()}</div>
                                ${inv.length>0 ? `<div class="col-12 mt-2"><table class="table table-sm table-bordered"><thead><tr><th>Invoice</th><th>Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>${inv.map(iv=>`<tr><td>${iv.invoice_number}</td><td>${iv.fee_type}</td><td>${Number(iv.total_amount).toLocaleString()}</td><td>${Number(iv.amount_paid).toLocaleString()}</td><td>${Number(iv.balance).toLocaleString()}</td><td>${iv.status}</td></tr>`).join('')}</tbody></table></div>` : ''}
                                ${pay.length>0 ? `<div class="col-12 mt-2"><strong>Payments</strong><table class="table table-sm table-bordered"><thead><tr><th>Ref</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead><tbody>${pay.map(p=>`<tr><td>${p.payment_reference}</td><td>${Number(p.amount_received).toLocaleString()}</td><td>${p.payment_method}</td><td>${p.payment_date}</td></tr>`).join('')}</tbody></table></div>` : ''}
                            </div>
                        </div>
                    </div>`;
                setTimeout(()=>{
                    document.querySelectorAll('#profileTabs a').forEach(t=>{
                        t.addEventListener('click',e=>{ e.preventDefault(); new bootstrap.Tab(t).show(); });
                    });
                },100);
            }).catch(()=>{
                document.getElementById('studentProfileBody').innerHTML = '<p class="text-danger">Error loading profile.</p>';
            });
    }

    function printStudentProfile(){
        const c = document.getElementById('studentProfileBody').innerHTML;
        const w = window.open('','_blank');
        w.document.write('<!DOCTYPE html><html><head><title>Student Profile</title><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ddd;padding:6px 8px}th{background:#f3f4f6}h2{color:#1f2937}.text-right{text-align:right}@media print{body{print-color-adjust:exact}}</style></head><body><h2>Student Profile</h2>'+c+'<script>window.onload=function(){window.print()}<\/script></body></html>');
        w.document.close();
    }

    function filterStudentTable(){
        const q = document.getElementById('studentSearchInput')?.value?.toLowerCase()||'';
        document.querySelectorAll('#studentRecordsTable tbody tr').forEach(r=>{
            r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    function evaluatePlacement(id, site){
        openModal('clinicalEvaluation');
        document.getElementById('modalTitle').textContent = 'Evaluate: '+site;
        document.getElementById('modalBody').innerHTML = '<input type="hidden" name="action" value="clinical_evaluation"><input type="hidden" name="placement_id" value="'+id+'">'+
            '<div class="mb-3"><label class="form-label">Competency Score (0-100)</label><input type="number" name="competency_score" class="form-control" min="0" max="100" step="0.1" required></div>'+
            '<div class="mb-3"><label class="form-label">Evaluation Notes</label><textarea name="evaluation" class="form-control" rows="4" required></textarea></div>';
        document.getElementById('modalActionBtn').textContent = 'Submit Evaluation';
        document.getElementById('modalActionBtn').parentElement.style.display = '';
        document.getElementById('modalForm').onsubmit = function(){ return true; };
    }

    function filterStudentTable(){
        const q = document.getElementById('studentSearchInput')?.value?.toLowerCase()||'';
        document.querySelectorAll('#studentRecordsTable tbody tr').forEach(r=>{
            r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    // Show overview by default
    document.querySelectorAll('.content-section').forEach(s=>s.style.display='none');
    const firstSection = document.getElementById('overview');
    if(firstSection) firstSection.style.display='block';
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
