<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';

$ctx = bootstrapStaffDashboard(['admissions', 'director']);
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'] ?? null;
$website_conn = $ctx['website'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Director Admissions';

function safeCount($c, $s) { $r=$c->query($s); if(!$r)return 0; $w=$r->fetch_assoc(); return intval($w['c']??0); }

// ── Real stats ──
$total_apps       = $website_conn ? safeCount($website_conn,"SELECT COUNT(*)c FROM student_applications") : 0;
$pending_apps     = $website_conn ? safeCount($website_conn,"SELECT COUNT(*)c FROM student_applications WHERE status='Pending'") : 0;
$admitted_students = safeCount($staff_conn,"SELECT COUNT(*)c FROM student_admissions WHERE admission_status='Approved'");
$enrolled_students = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students WHERE status='Active'") : 0;
$active_students   = $students_conn ? safeCount($students_conn,"SELECT COUNT(*)c FROM students WHERE status='Active'") : 0;

// ── Load data ──
$applicants = $website_conn ? [] : []; if($website_conn){ $r=$website_conn->query("SELECT * FROM student_applications ORDER BY submitted_at DESC LIMIT 50");
if($r) while($row=$r->fetch_assoc()) $applicants[]=$row; }

$programs = []; $r=$staff_conn->query("SELECT program_code,program_name,program_type,department,duration_years,status FROM academic_programs WHERE status='Active' ORDER BY program_name");
if($r) while($row=$r->fetch_assoc()) $programs[]=$row;

$req_items = []; $r=$staff_conn->query("SELECT * FROM requirement_items ORDER BY display_order");
if($r) while($row=$r->fetch_assoc()) $req_items[]=$row;

$search_student = trim($_GET['search_student'] ?? '');
$showStudentList = $search_student !== '';
$students_list = [];
if ($students_conn && $showStudentList) {
    $sw = "WHERE 1=1";
    if ($search_student !== '') { $ss = $students_conn->real_escape_string($search_student); $sw .= " AND (full_name LIKE '%$ss%' OR first_name LIKE '%$ss%' OR surname LIKE '%$ss%' OR index_number LIKE '%$ss%' OR student_number LIKE '%$ss%' OR registration_number LIKE '%$ss%' OR phone LIKE '%$ss%' OR national_student_id_number LIKE '%$ss%')"; }
    $r = $students_conn->query("SELECT id,student_number,registration_number,full_name,first_name,surname,course,phone,mobile_number,email,status,index_number,national_student_id_number FROM students $sw ORDER BY full_name LIMIT 200");
    if ($r) while($row=$r->fetch_assoc()) $students_list[]=$row;
}

$admissions_list = []; $r=$staff_conn->query("SELECT sa.*,s.full_name,s.student_number FROM student_admissions sa LEFT JOIN igangaschoolofl_students_db.students s ON sa.student_id=s.id ORDER BY sa.created_at DESC LIMIT 50");
if($r) while($row=$r->fetch_assoc()) $admissions_list[]=$row;

$recent_activities = []; $r=$staff_conn->query("SELECT activity_description activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
if($r) while($row=$r->fetch_assoc()) $recent_activities[]=$row;

$user_role_id = 0; $ri = $staff_conn->query("SELECT role_id FROM staff WHERE id = $user_id");
if ($ri) { $user_role_id = (int)$ri->fetch_assoc()['role_id']; }

// ── Report generation ──
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
    }
    echo '</body></html>'; exit;
}

// ── AJAX ──
$ajax = $_GET['ajax'] ?? '';
$ajaxSid = intval($_GET['student_id'] ?? 0);
if ($ajax && $ajaxSid > 0) {
    header('Content-Type: application/json');
    if ($ajax === 'student_requirements') {
        $cleared = [];
        $r=$staff_conn->query("SELECT item_id,cleared FROM requirement_clearances WHERE student_id=$ajaxSid");
        if($r) while($row=$r->fetch_assoc()) $cleared[$row['item_id']] = $row['cleared'];
        echo json_encode(['cleared'=>$cleared]); exit;
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

// ── POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add student to SQL instead of JSON
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
            } else {
                $_SESSION['error'] = 'Failed: '.$students_conn->error;
            }
        } else { $_SESSION['error'] = 'Name and program required.'; }
        header("Location: director-admissions.php"); exit;
    }

    // Save requirements to SQL instead of JSON
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

    // Approve application -> create admission
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

    // Reject application
    if ($action === 'reject_application') {
        $appId = intval($_POST['application_id'] ?? 0);
        if ($website_conn) $website_conn->query("UPDATE student_applications SET status='Rejected',reviewed_by=$user_id,reviewed_at=NOW() WHERE id=$appId");
        $_SESSION['success'] = 'Application rejected.';
        header("Location: director-admissions.php"); exit;
    }

    // Upload document
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

    // Delete document
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
.btn-outline-purple { color:#8b5cf6; border-color:#8b5cf6; }
.btn-outline-purple:hover { color:#fff; background:#8b5cf6; border-color:#8b5cf6; }
.modal-content { max-height:85vh; overflow-y:auto; }
</style>
</head>
<body>
    <div class="dashboard-container">
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Director Admissions Dashboard</h1>
                    <p>Admissions Management, Iganga School of Nursing and Midwifery</p>
                </div>
                <div class="header-right">
                    <div class="date-time"><i class="fas fa-calendar"></i><span id="currentDate"><?php echo date('l, F j, Y'); ?></span></div>
                    <a href="../student-directory.php" class="btn btn-sm btn-outline-info ms-2"><i class="fas fa-address-book me-1"></i>Directory</a>
                    <a href="../index.php" class="btn btn-sm btn-outline-secondary ms-1"><i class="fas fa-home"></i></a>
                    <div class="user-menu">
                        <img src="<?= $profileImageUrl ?? '../images/username.png' ?>" alt="User" class="user-avatar">
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                </div>
            </header>

            <?php if(!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show m-3 py-2"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); endif; ?>
            <?php if(!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show m-3 py-2"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['error']); endif; ?>

            <div class="dashboard-content">
                <!-- Overview -->
                <section id="overview" class="content-section dashboard-section active" data-section="overview">
                    <h2>Admissions Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                            <div class="stat-content"><h3><?= $total_apps ?></h3><p>Total Applications</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            <div class="stat-content"><h3><?= $pending_apps ?></h3><p>Pending Review</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-content"><h3><?= $admitted_students ?></h3><p>Admitted Students</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                            <div class="stat-content"><h3><?= $enrolled_students ?></h3><p>Enrolled Students</p></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-content"><h3><?= $active_students ?></h3><p>Active Students</p></div>
                        </div>
                    </div>
                </section>

                <!-- Official Duties -->
                <section id="duties" class="content-section dashboard-section" data-section="duties">
                    <h2><i class="fas fa-tasks me-2"></i>Official Duties &amp; Responsibilities</h2>
                    <?php renderOfficialDuties($user_role_id, $staff_conn); ?>
                </section>

                <!-- Quick Actions -->
                <section id="quick-actions" class="content-section dashboard-section" data-section="quick-actions">
                    <h2><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h2>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <a href="../import_students_excel.php" class="btn btn-outline-success btn-sm"><i class="fas fa-file-excel me-1"></i>Import Students</a>
                        <a href="../student-directory.php" class="btn btn-outline-info btn-sm"><i class="fas fa-address-book me-1"></i>Student Directory</a>
                        <a href="../dashboards/academic-registrar.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-alt me-1"></i>Academic Registrar</a>
                        <a href="../dashboards/school-principal.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-chalkboard-teacher me-1"></i>School Secretary</a>
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-user-plus me-1"></i>Add Student</button>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i>Print Overview</button>
                    </div>
                </section>

                <!-- Applications Management -->
                <section id="applications" class="content-section dashboard-section" data-section="applications">
                    <h2><i class="fas fa-file-alt me-2"></i>Applications Management</h2>
                    <?php if(empty($applicants)): ?>
                    <p class="text-muted">No student applications in database.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>App No</th><th>Applicant</th><th>Program</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach($applicants as $a):
                                $aname = htmlspecialchars(trim($a['first_name'].' '.$a['surname']));
                            ?>
                            <tr>
                                <td><code><?= htmlspecialchars($a['application_number']) ?></code></td>
                                <td><?= $aname ?></td>
                                <td><?= htmlspecialchars($a['program_applied']) ?></td>
                                <td><?= $a['submitted_at'] ?></td>
                                <td><span class="badge bg-<?= $a['status']==='Admitted'?'success':($a['status']==='Rejected'?'danger':($a['status']==='Pending'?'warning':'info')) ?>"><?= $a['status'] ?></span></td>
                                <td>
                                    <?php if($a['status']==='Pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="approve_application">
                                        <input type="hidden" name="application_id" value="<?= $a['id'] ?>">
                                        <button class="btn btn-sm btn-outline-success" onclick="return confirm('Approve and convert to student?')"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="reject_application">
                                        <input type="hidden" name="application_id" value="<?= $a['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject application?')"><i class="fas fa-times"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-info" onclick="alert('Name: <?= $aname ?>\nProgram: <?= htmlspecialchars($a['program_applied']) ?>\nPhone: <?= htmlspecialchars($a['phone']) ?>\nEmail: <?= htmlspecialchars($a['email']??'-') ?>\nDOB: <?= $a['date_of_birth'] ?>\nNationality: <?= htmlspecialchars($a['nationality']??'-') ?>')"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </section>

                <!-- Requirements Portal -->
                <section id="requirements" class="content-section dashboard-section" data-section="requirements">
                    <h2><i class="fas fa-clipboard-check me-2"></i>Requirements Portal</h2>
                    <p class="text-muted small">Track admission requirements per student.</p>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <select id="reqStudentId" class="form-select form-select-sm" onchange="loadStudentRequirements()">
                                <option value="">Select student…</option>
                                <?php if($students_conn){ $r=$students_conn->query("SELECT id,full_name,student_number FROM students ORDER BY full_name LIMIT 200"); if($r) while($row=$r->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']?:$row['student_number']) ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                    </div>
                    <form method="POST" id="requirementsForm">
                        <input type="hidden" name="action" value="save_requirements">
                        <input type="hidden" name="student_id" id="reqStudentIdHidden">
                        <div id="requirementsList" class="small text-muted">Select a student to view requirements.</div>
                        <div id="requirementsSubmitArea" style="display:none" class="mt-2">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Save Requirements</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.open('director-admissions.php?report=clearance','_blank')"><i class="fas fa-print me-1"></i>Print Report</button>
                        </div>
                    </form>
                </section>

                <!-- Student Directory -->
                <section id="directory" class="content-section dashboard-section" data-section="directory">
                    <h2><i class="fas fa-address-book me-2"></i>Student Directory</h2>
                    <div class="mb-2">
                        <input type="text" id="dirSearch" class="form-control form-control-sm" placeholder="Search by name, reg no, program..." onkeyup="filterDirectory()">
                    </div>
                    <?php if(empty($students_list)): ?>
                    <p class="text-muted">No students found.</p>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm table-hover" id="dirTable">
                            <thead><tr><th>Reg No</th><th>Name</th><th>Program</th><th>Phone/Mobile</th><th>Index No</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach($students_list as $s):
                                $sdname = htmlspecialchars($s['full_name'] ?: trim($s['first_name'].' '.$s['surname']));
                                $sdreg = htmlspecialchars($s['registration_number'] ?: $s['student_number']);
                                $sdphone = htmlspecialchars($s['phone']??'');
                                $sdmobile = htmlspecialchars($s['mobile_number']??'');
                                $sdindex = htmlspecialchars($s['index_number']??$s['national_student_id_number']??'');
                            ?>
                            <tr>
                                <td><code><?= $sdreg ?></code></td>
                                <td><?= $sdname ?></td>
                                <td><?= htmlspecialchars($s['course']??'-') ?></td>
                                <td><?= $sdphone ?: '-' ?><?= ($sdmobile && $sdmobile!==$sdphone) ? '<br><small>M: '.$sdmobile.'</small>' : '' ?></td>
                                <td><?= $sdindex ?: '-' ?></td>
                                <td><span class="badge bg-<?= $s['status']==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewStudentProfile(<?= $s['id'] ?>)"><i class="fas fa-eye"></i></button>
                                    <a href="../print-student.php?id=<?= $s['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></a>
                                    <button class="btn btn-sm btn-outline-warning" onclick="window.open('director-academics.php?report=fee_statement&student_id=<?= $s['id'] ?>','_blank')"><i class="fas fa-file-invoice"></i></button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="uploadDoc(<?= $s['id'] ?>, '<?= addslashes($sdname) ?>')"><i class="fas fa-upload"></i></button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="openEditStudentModal(<?= $s['id'] ?>)"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </section>

                <!-- Reports -->
                <section id="reports" class="content-section dashboard-section" data-section="reports">
                    <h2><i class="fas fa-chart-bar me-2"></i>Admissions Reports</h2>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card card-body text-center py-3" style="cursor:pointer" onclick="window.open('director-admissions.php?report=applications','_blank')">
                                <i class="fas fa-file-alt fa-2x mb-2" style="color:var(--primary)"></i>
                                <strong class="small">Applications Report</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-body text-center py-3" style="cursor:pointer" onclick="window.open('director-admissions.php?report=admitted','_blank')">
                                <i class="fas fa-check-circle fa-2x mb-2" style="color:var(--primary)"></i>
                                <strong class="small">Admitted Students</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-body text-center py-3" style="cursor:pointer" onclick="window.open('director-admissions.php?report=clearance','_blank')">
                                <i class="fas fa-clipboard-check fa-2x mb-2" style="color:var(--primary)"></i>
                                <strong class="small">Clearance Report</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-body text-center py-3" style="cursor:pointer" onclick="window.open('director-admissions.php?report=intake','_blank')">
                                <i class="fas fa-calendar-alt fa-2x mb-2" style="color:var(--primary)"></i>
                                <strong class="small">Intake Report</strong>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- News -->
                <section id="news" class="content-section dashboard-section" data-section="news">
                    <h2><i class="fas fa-newspaper me-2"></i>News &amp; Announcements</h2>
                    <?php renderNewsWidget($staff_conn, $website_conn, $user['id'] ?? 0, $user_name, $user_role, 5); ?>
                </section>

                <!-- Recent Activities -->
                <section id="activity" class="content-section dashboard-section" data-section="activity">
                    <h2><i class="fas fa-history me-2"></i>Recent Admissions Activity</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $act): ?>
                        <div class="activity-item">
                            <div class="activity-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="activity-content">
                                <strong><?php echo htmlspecialchars($act['activity'] ?? 'Activity'); ?></strong>
                                <small class="text-muted d-block"><?php echo date('M j, Y H:i', strtotime($act['created_at'])); ?></small>
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
                        <span class="fw-semibold">Director General (Level 1)</span>
                    </div>
                    <?= renderHierarchyChart($staff_conn) ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100">
                    <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-bell me-2 text-danger"></i>Admissions Alerts</h6>
                    <?= renderAlertsPanel($staff_conn, 'ADM', 5) ?>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="section-card h-100">
                    <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-chart-bar me-2 text-success"></i>Admissions Department Performance</h6>
                    <?php
                    $admStaffId = 0;
                    $sq = $staff_conn ? $staff_conn->prepare("SELECT id FROM staff WHERE role_id = 15 AND status = 'Active' LIMIT 1") : false;
                    if ($sq) { $sq->execute(); $sr = $sq->get_result()->fetch_assoc(); $sq->close(); if ($sr) $admStaffId = $sr['id']; }
                    echo renderDirectorPerformanceCard($admStaffId, 15, 'Director Admissions', $staff_conn);
                    ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card h-100">
                    <h6 class="fw-bold mb-3" style="font-size:0.95rem"><i class="fas fa-check-double me-2 text-primary"></i>Pending Admissions Approvals</h6>
                    <?php
                    $pendingApprovals = getPendingApprovals($staff_conn, 15, 5);
                    if (!empty($pendingApprovals)):
                        foreach ($pendingApprovals as $apr):
                            echo renderApprovalWorkflowCard($apr, $staff_conn);
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

    <!-- Add Student Modal -->
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
                        <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Registration Number</label><input type="text" name="registration_number" class="form-control" placeholder="Auto-generated if empty"></div>
                        <div class="col-md-4"><label class="form-label">Index Number</label><input type="text" name="index_number" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><option>Female</option><option>Male</option></select></div>
                        <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Phone *</label><input type="text" name="phone" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="1"></textarea></div>
                        <div class="col-md-6"><label class="form-label">Program *</label>
                            <select name="program" class="form-select" required>
                                <option value="">Select Program</option>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Register Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Document Modal -->
    <div class="modal fade" id="uploadDocModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_doc">
                <input type="hidden" name="student_id" id="uploadDocStudentId">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Document</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Document Type</label>
                        <select name="doc_type" class="form-select">
                            <option>Admission Letter</option><option>Certificate</option><option>Passport Photo</option><option>Identification</option><option>Medical Form</option><option>Other</option>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Title</label><input type="text" name="doc_title" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">File</label><input type="file" name="doc_file" class="form-control" required></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white"><i class="fas fa-upload me-1"></i>Upload</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function loadStudentRequirements(){
        const sid = document.getElementById('reqStudentId').value;
        const hidden = document.getElementById('reqStudentIdHidden');
        const list = document.getElementById('requirementsList');
        const submitArea = document.getElementById('requirementsSubmitArea');
        if(!sid){ list.innerHTML='<span class="text-muted">Select a student.</span>'; submitArea.style.display='none'; return; }
        hidden.value=sid;
        list.innerHTML='<em>Loading...</em>';
        fetch('director-admissions.php?ajax=student_requirements&student_id='+sid)
            .then(r=>r.json()).then(d=>{
                let cleared = d.cleared||{};
                let h = '<div class="row g-2">';
                <?php foreach($req_items as $ri): ?>
                h += `<div class="col-md-4"><div class="form-check">
                    <input class="form-check-input" type="checkbox" name="cleared_items[]" value="<?= $ri['id'] ?>" id="req_<?= $ri['id'] ?>" ${cleared[<?= $ri['id'] ?>] ? 'checked' : ''}>
                    <label class="form-check-label small" for="req_<?= $ri['id'] ?>"><?= htmlspecialchars($ri['item_name']) ?></label>
                </div></div>`;
                <?php endforeach; ?>
                h += '</div>';
                list.innerHTML = h;
                submitArea.style.display = '';
            }).catch(()=>{ list.innerHTML='<span class="text-danger">Error loading.</span>'; });
    }

    function viewStudentProfile(id){
        const modal = new bootstrap.Modal(document.getElementById('studentProfileModal'));
        document.getElementById('studentProfileBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading...</p></div>';
        modal.show();
        fetch('director-admissions.php?ajax=student_profile&student_id='+id)
            .then(r=>r.json()).then(d=>{
                let info = d.info||{};
                let inv = d.invoices||[], pay = d.payments||[], docs = d.documents||[];
                let tPaid = pay.reduce((s,p)=>s+parseFloat(p.amount_received||0),0);
                let tInv = inv.reduce((s,iv)=>s+parseFloat(iv.total_amount||0),0);
                let h = `<ul class="nav nav-tabs mb-3" id="sTabs">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#sPers">Personal</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sAcad">Academic</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sFin">Finance</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sDoc">Documents</a></li>
                </ul><div class="tab-content">
                    <div class="tab-pane fade show active" id="sPers"><div class="row g-2 small">
                        <div class="col-md-6"><strong>Name:</strong> ${info.full_name||''}</div>
                        <div class="col-md-6"><strong>Reg No:</strong> ${info.registration_number||info.student_number||'-'}</div>
                        <div class="col-md-6"><strong>National ID:</strong> ${info.national_student_id_number||'-'}</div>
                        <div class="col-md-6"><strong>Phone:</strong> ${info.phone||'-'}</div>
                        <div class="col-md-6"><strong>Email:</strong> ${info.email||'-'}</div>
                        <div class="col-md-6"><strong>DOB:</strong> ${info.date_of_birth||'-'}</div>
                        <div class="col-md-6"><strong>Gender:</strong> ${info.gender||'-'}</div>
                        <div class="col-md-6"><strong>Address:</strong> ${info.address||'-'}</div>
                        <div class="col-md-6"><strong>Guardian:</strong> ${info.guardian_name||'-'}</div>
                        <div class="col-md-6"><strong>Guardian Phone:</strong> ${info.guardian_phone||'-'}</div>
                        <div class="col-md-6"><strong>Emergency:</strong> ${info.emergency_contact_name||'-'}</div>
                        <div class="col-md-6"><strong>Emergency Phone:</strong> ${info.emergency_contact_phone||'-'}</div>
                    </div></div>
                    <div class="tab-pane fade" id="sAcad"><div class="row g-2 small">
                        <div class="col-md-4"><strong>Program:</strong> ${info.course||'-'}</div>
                        <div class="col-md-4"><strong>Year:</strong> ${info.current_year||'-'}</div>
                        <div class="col-md-4"><strong>Semester:</strong> ${info.current_semester||'-'}</div>
                        <div class="col-md-4"><strong>Intake:</strong> ${info.intake_date||'-'}</div>
                        <div class="col-md-4"><strong>Set:</strong> ${info.set_name||'-'}</div>
                        <div class="col-md-4"><strong>Status:</strong> <span class="badge bg-success">${info.status||'-'}</span></div>
                    </div></div>
                    <div class="tab-pane fade" id="sFin"><div class="row g-2 small">
                        <div class="col-md-4"><strong>Invoiced:</strong> ${tInv.toLocaleString()}</div>
                        <div class="col-md-4"><strong>Paid:</strong> ${tPaid.toLocaleString()}</div>
                        <div class="col-md-4"><strong>Balance:</strong> ${(tInv-tPaid).toLocaleString()}</div>
                        <div class="col-12 mt-2"><button class="btn btn-sm btn-outline-primary" onclick="window.open('director-academics.php?report=fee_statement&student_id=${id}','_blank')"><i class="fas fa-file-invoice"></i> Full Statement</button></div>
                    </div></div>
                    <div class="tab-pane fade" id="sDoc"><div class="small">${docs.length ? docs.map(d=>'<div class="mb-1 d-flex justify-content-between align-items-center">'+(d.file_path ? '<a href="../'+d.file_path+'" target="_blank">'+d.document_title+'</a>' : '<span>'+d.document_title+'</span>')+' <small class="text-muted">('+d.document_type+')</small> <form method="POST" class="d-inline"><input type="hidden" name="action" value="delete_doc"><input type="hidden" name="document_id" value="'+d.id+'"><button class="btn btn-sm btn-outline-danger btn-tbl py-0" onclick="return confirm(\'Delete?\')"><i class="fas fa-times"></i></button></form></div>').join('') : '<p class="text-muted">No documents.</p>'}</div></div>
                </div>`;
                document.getElementById('studentProfileBody').innerHTML = h;
                setTimeout(()=>{ document.querySelectorAll('#sTabs a').forEach(t=>{ t.addEventListener('click',e=>{ e.preventDefault(); new bootstrap.Tab(t).show(); }); }); },100);
            }).catch(function(){ document.getElementById('studentProfileBody').innerHTML = '<div class="alert alert-danger text-center m-3">Failed to load profile.</div>'; });
    }

    function printStudentProfile(){
        const c = document.getElementById('studentProfileBody').innerHTML;
        const w = window.open('','_blank');
        w.document.write('<!DOCTYPE html><html><head><title>Student Profile</title><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ddd;padding:6px 8px}th{background:#f3f4f6}h2{color:#1f2937}@media print{body{print-color-adjust:exact}}</style></head><body><h2>Student Profile</h2>'+c+'<script>window.onload=function(){window.print()}<\/script></body></html>');
        w.document.close();
    }

    function filterDirectory(){
        const q = document.getElementById('dirSearch')?.value?.toLowerCase()||'';
        document.querySelectorAll('#dirTable tbody tr').forEach(r=>{ r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
    }

    function uploadDoc(id, name){
        document.getElementById('uploadDocStudentId').value = id;
        new bootstrap.Modal(document.getElementById('uploadDocModal')).show();
    }
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
