<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/notification_helper.php';
$ctx = bootstrapStaffDashboard(['registrar','director','academics','admissions','head']);
$user = $ctx['user'];
$auth_service = $ctx['auth'];
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];

$role = $_SESSION['role'] ?? '';
$roleLower = strtolower(trim($role));
$isSuperAdmin = $auth_service->hasFullInstitutionAccess($role);

$allowed = ['School Secretary','Director ICT','Academic Registrar','Registrar','Director General','CEO','School Principal','Principal','HOD','Lecturer'];
if (!in_array($role, $allowed) && !$isSuperAdmin) {
    header('Location: ../student-login.php?error=unauthorized'); exit;
}

define('PERM_VIEW', 'view');
define('PERM_EDIT_ACADEMIC', 'edit_academic');
define('PERM_EDIT_FINANCE', 'edit_finance');
define('PERM_EDIT_ADMISSION', 'edit_admission');
define('PERM_MANAGE_STUDENTS', 'manage_students');
define('PERM_DELETE', 'delete');

function getUserPermissions($roleLower) {
    $superRoles = ['director general','ceo','chief executive officer','system admin','system administrator'];
    $perm = [];
    if (in_array($roleLower, $superRoles)) {
        return [PERM_VIEW, PERM_EDIT_ACADEMIC, PERM_EDIT_FINANCE, PERM_EDIT_ADMISSION, PERM_MANAGE_STUDENTS, PERM_DELETE];
    }
    $perm[] = PERM_VIEW;
    if (str_contains($roleLower, 'registrar') || $roleLower === 'school secretary' || $roleLower === 'secretary') {
        $perm[] = PERM_EDIT_ACADEMIC; $perm[] = PERM_MANAGE_STUDENTS;
    }
    if (str_contains($roleLower, 'admission')) {
        $perm[] = PERM_EDIT_ADMISSION; $perm[] = PERM_MANAGE_STUDENTS;
    }
    if (str_contains($roleLower, 'director') || $roleLower === 'principal' || $roleLower === 'deputy') {
        $perm[] = PERM_EDIT_ADMISSION; $perm[] = PERM_EDIT_ACADEMIC; $perm[] = PERM_MANAGE_STUDENTS;
    }
    if (str_contains($roleLower, 'bursar') || str_contains($roleLower, 'finance') || $roleLower === 'accountant') {
        $perm[] = PERM_EDIT_FINANCE;
    }
    if (str_contains($roleLower, 'lecturer') || str_contains($roleLower, 'teacher') || str_contains($roleLower, 'head')) {
        $perm[] = PERM_EDIT_ACADEMIC;
    }
    if (str_contains($roleLower, 'matron') || str_contains($roleLower, 'warden') || str_contains($roleLower, 'sickbay')) {
        return [PERM_VIEW];
    }
    return $perm;
}

$userPerms = getUserPermissions($roleLower);
$canView = in_array(PERM_VIEW, $userPerms);
$canEditAcademic = in_array(PERM_EDIT_ACADEMIC, $userPerms);
$canEditFinance = in_array(PERM_EDIT_FINANCE, $userPerms);
$canEditAdmission = in_array(PERM_EDIT_ADMISSION, $userPerms);
$canManageStudents = in_array(PERM_MANAGE_STUDENTS, $userPerms);
$canDelete = in_array(PERM_DELETE, $userPerms);

require_once __DIR__ . '/../views/student_data_loader.php';

$conn = $studentsDb;
$uid = $_SESSION['user_id'] ?? 0;
$uname = $_SESSION['full_name'] ?? 'User';

$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';
$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';

if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`student_admissions` (id INT AUTO_INCREMENT PRIMARY KEY, application_number VARCHAR(50) UNIQUE, applicant_name VARCHAR(300) NOT NULL, program VARCHAR(200) DEFAULT '', academic_year VARCHAR(20) DEFAULT NULL, admission_status VARCHAR(50) DEFAULT 'Applied', application_date DATE DEFAULT NULL, decided_by INT DEFAULT 0, remarks TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`course_catalog` (id INT AUTO_INCREMENT PRIMARY KEY, course_code VARCHAR(50) NOT NULL, course_name VARCHAR(300) NOT NULL, program VARCHAR(200) DEFAULT '', level VARCHAR(50) DEFAULT '', semester VARCHAR(100) DEFAULT '', is_compulsory TINYINT(1) DEFAULT 1, status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`examination_results` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, course_id INT DEFAULT 0, score DECIMAL(8,2) DEFAULT 0, max_score DECIMAL(8,2) DEFAULT 100, status VARCHAR(50) DEFAULT 'Pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`clinical_placements` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, facility_name VARCHAR(300) DEFAULT '', department VARCHAR(200) DEFAULT '', start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`student_attendance` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, attendance_date DATE DEFAULT NULL, time_in TIME DEFAULT NULL, time_out TIME DEFAULT NULL, status VARCHAR(50) DEFAULT 'Present', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id), KEY idx_date (attendance_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`student_invoices` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, amount DECIMAL(12,2) DEFAULT 0, status VARCHAR(50) DEFAULT 'Pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`course_registrations` (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, course_code VARCHAR(50) DEFAULT '', course_id INT DEFAULT 0, academic_year VARCHAR(20) DEFAULT NULL, semester VARCHAR(100) DEFAULT NULL, registration_status VARCHAR(50) DEFAULT 'Registered', status VARCHAR(50) DEFAULT 'Registered', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$students_db}`.`assessment_scores` (id INT AUTO_INCREMENT PRIMARY KEY, examination_session_id INT DEFAULT 0, student_id INT NOT NULL, score DECIMAL(8,2) DEFAULT 0, max_score DECIMAL(8,2) DEFAULT 100, entered_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function acad_q($conn, $sql) {
    $r = $conn ? $conn->query($sql) : false;
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row[array_key_first($row)] ?? 0;
}

$dataLoader = new StudentDataLoader();
$stats = $dataLoader->getStatistics();

$hasSearched = isset($_GET['student_search']) && trim($_GET['student_search']) !== '';
$hasFiltered = false;
foreach (['program','level','set','gender','year'] as $k) {
    if (!empty($_GET[$k])) { $hasFiltered = true; break; }
}
$showResults = $hasSearched || $hasFiltered;

$search = trim($_GET['student_search'] ?? '');
$filters = [
    'program' => $_GET['program'] ?? '',
    'level' => $_GET['level'] ?? '',
    'set' => $_GET['set'] ?? '',
    'gender' => $_GET['gender'] ?? '',
    'year' => $_GET['year'] ?? '',
];
$students = [];
if ($showResults) {
    $students = $dataLoader->searchStudents($search, $filters);
}
$filterOptions = $dataLoader->getFilterOptions();

$total_students = acad_q($conn, "SELECT COUNT(*) v FROM students WHERE status='Active'");
$pending_admissions = acad_q($conn, "SELECT COUNT(*) v FROM student_admissions WHERE admission_status IN('Applied','Interview','Admitted')");
$active_courses = acad_q($conn, "SELECT COUNT(*) v FROM course_catalog WHERE status='Active'");
$pending_results = acad_q($conn, "SELECT COUNT(*) v FROM examination_results WHERE status='Pending'");
$clinical_active = acad_q($conn, "SELECT COUNT(*) v FROM clinical_placements WHERE status='Active'");
$overdue_attendance = acad_q($conn, "SELECT COUNT(*) v FROM student_attendance WHERE attendance_date < CURDATE() AND status='Absent'");
$fee_not_cleared = acad_q($conn, "SELECT COUNT(DISTINCT student_id) v FROM student_invoices WHERE status IN('Pending','Partially Paid','Overdue')");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verifyCSRFToken') && !verifyCSRFToken()) { $_SESSION['error'] = 'Invalid security token.'; header('Location: student-management.php'); exit; }
    $action = $_POST['action'] ?? '';
    if ($conn && $action === 'record_admission') {
        if (!$canEditAdmission) { $_SESSION['error'] = 'Permission denied.'; header('Location: student-management.php'); exit; }
        $app = $_POST['application_number'] ?? ('APP-'.date('Ymd').'-'.mt_rand(1000,9999));
        $name = $_POST['applicant_name'] ?? '';
        $program = $_POST['program'] ?? '';
        $year = $_POST['academic_year'] ?? date('Y');
        $status = in_array($_POST['admission_status'] ?? '', ['Applied','Interview','Admitted','Rejected','Deferred','Enrolled']) ? $_POST['admission_status'] : 'Applied';
        $date = $_POST['application_date'] ?? date('Y-m-d');
        if ($name && $program) {
            $stmt = $conn->prepare("INSERT INTO student_admissions (application_number,applicant_name,program,academic_year,admission_status,application_date,decided_by,remarks) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE applicant_name=VALUES(applicant_name),program=VALUES(program),admission_status=VALUES(admission_status)");
            $stmt->bind_param("ssssssi", $app, $name, $program, $year, $status, $date, $uid);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Admission record saved.';
        }
    }
    if ($conn && $action === 'register_course') {
        if (!$canEditAcademic) { $_SESSION['error'] = 'Permission denied.'; header('Location: student-management.php'); exit; }
        $sid = intval($_POST['student_id'] ?? 0);
        $cid = intval($_POST['course_id'] ?? 0);
        $ay = $_POST['academic_year'] ?? date('Y');
        $sem = $_POST['semester'] ?? 'First Semester';
        if ($sid && $cid) {
            $stmt = $conn->prepare("INSERT INTO course_registrations (student_id,course_id,academic_year,semester,registration_status) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE registration_status='Registered'");
            $stmt->bind_param("iisss", $sid, $cid, $ay, $sem);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Course registration saved.';
            $nid = createNotification('Course Registration', "Student #$sid registered for course #$cid.", 'student-management.php', 'info', 'fas fa-book');
            if ($nid) notifyAllStaff($nid);
        }
    }
    if ($conn && $action === 'enter_score') {
        if (!$canEditAcademic) { $_SESSION['error'] = 'Permission denied.'; header('Location: student-management.php'); exit; }
        $session = intval($_POST['examination_session_id'] ?? 0);
        $sid = intval($_POST['student_id'] ?? 0);
        $score = floatval($_POST['score'] ?? 0);
        $max = floatval($_POST['max_score'] ?? 100);
        if ($session && $sid) {
            $stmt = $conn->prepare("INSERT INTO assessment_scores (examination_session_id,student_id,score,max_score,entered_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score),max_score=VALUES(max_score)");
            $stmt->bind_param("iiddd", $session, $sid, $score, $max, $uid);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Score saved.';
        }
    }
    header('Location: student-management.php'); exit;
}

$courses = [];
if ($conn && $canEditAcademic) {
    $r = $conn->query("SELECT id, course_code, course_name, program, level, semester, is_compulsory FROM course_catalog WHERE status='Active' ORDER BY program, level, semester, course_code LIMIT 80");
    if ($r) while ($row = $r->fetch_assoc()) $courses[] = $row;
}

$pendingAdmissions = [];
if ($conn && $canEditAdmission) {
    $r = $conn->query("SELECT * FROM student_admissions WHERE admission_status IN('Applied','Interview','Admitted') ORDER BY application_date DESC LIMIT 15");
    if ($r) while ($row = $r->fetch_assoc()) $pendingAdmissions[] = $row;
}

$recentResults = [];
if ($conn && $canEditAcademic) {
    $r = $conn->query("SELECT er.*, s.full_name as sname, s.student_number, cc.course_code FROM examination_results er JOIN students s ON er.student_id=s.id LEFT JOIN course_catalog cc ON er.course_id=cc.id ORDER BY er.created_at DESC LIMIT 15");
    if ($r) while ($row = $r->fetch_assoc()) $recentResults[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.search-hero { background: linear-gradient(135deg, #1a237e 0%, #283593 100%); border-radius: 16px; padding: 32px; margin-bottom: 24px; color: #fff; }
.search-hero h2 { font-weight: 700; margin-bottom: 4px; }
.search-hero p { opacity: 0.85; font-size: 14px; margin-bottom: 20px; }
.search-hero .form-control, .search-hero .form-select { border-radius: 10px; border: none; padding: 10px 16px; font-size: 14px; }
.search-hero .btn-search { background: #ffd700; color: #1a237e; font-weight: 600; border-radius: 10px; padding: 10px 24px; border: none; }
.search-hero .btn-search:hover { background: #ffed4a; }
.empty-state { text-align: center; padding: 60px 20px; }
.empty-state i { font-size: 64px; color: #c0c0c0; margin-bottom: 16px; }
.empty-state h5 { color: #666; font-weight: 600; }
.empty-state p { color: #999; max-width: 400px; margin: 0 auto; }
.stat-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-2px); }
.page-header { margin-bottom: 24px; }
.page-header h4 { color: #1a237e; font-weight: 700; }
.badge-role { font-size: 11px; padding: 2px 10px; border-radius: 20px; }
.profile-card { border: none; border-radius: 12px; overflow: hidden; }
.profile-card .card-header { background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%); color: #fff; padding: 20px 24px; border: none; }
.profile-card .card-body { padding: 24px; }
.profile-info-table td { padding: 6px 12px; vertical-align: top; }
.profile-info-table td:first-child { font-weight: 600; color: #555; width: 160px; white-space: nowrap; }
.action-btn-group { display: flex; gap: 4px; flex-wrap: wrap; }
.role-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; margin-bottom: 8px; }
@media (max-width: 768px) { .search-hero { padding: 20px; } .profile-info-table td:first-child { width: 100px; } }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="main" style="margin-left:270px;padding:20px;background:#f4f5f9;min-height:100vh;">
  <div class="page-header d-flex justify-content-between align-items-center">
    <div>
      <h4 class="d-inline fw-bold">Student Management</h4>
      <span class="text-muted small ms-2"><i class="fas fa-shield-alt me-1"></i><?= htmlspecialchars($role) ?></span>
    </div>
    <small class="text-muted"><?= date('l, d M Y') ?></small>
  </div>

  <?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2 alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if(!empty($_SESSION['error'])): ?><div class="alert alert-danger py-2 alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

  <section id="overview">
    <div class="row g-3 mb-4">
      <?php $cards=[
        ['Total Enrolled',$stats['total_students'],'users','#1a237e'],
        ['Active Programs',$stats['total_programs'] ?? $active_courses,'book','#2e7d32'],
        ['Pending Admissions',$pending_admissions,'user-check','#e65100'],
        ['Clinical Placements',$clinical_active,'hospital-user','#1565c0'],
        ['Fee Not Cleared',$fee_not_cleared,'exclamation-triangle','#c62828'],
      ]; foreach($cards as $c): ?>
      <div class="col-6 col-md-2"><div class="stat-card"><div class="fs-3 fw-bold" style="color:<?= $c[3] ?>"><?= $c[1] ?></div><div class="text-muted small"><i class="fas fa-<?= $c[2] ?> me-1"></i><?= $c[0] ?></div></div></div>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="search-section">
    <div class="search-hero">
      <h2><i class="fas fa-search me-2"></i>Find Students</h2>
      <p>Search by name, index number, registration number, NSIN, phone, email, program, or level</p>
      <form method="GET" id="studentSearchForm">
        <div class="row g-2">
          <div class="col-md-4">
            <input type="text" name="student_search" class="form-control form-control-lg" placeholder="Type name, ID, phone, program..." value="<?= htmlspecialchars($search) ?>" autofocus>
          </div>
          <div class="col-md-2">
            <select name="program" class="form-select"><option value="">All Programs</option><?php foreach($filterOptions['programs'] as $p): ?><option <?= $filters['program']===$p?'selected':'' ?>><?= htmlspecialchars($p) ?></option><?php endforeach; ?></select>
          </div>
          <div class="col-md-1">
            <select name="level" class="form-select"><option value="">Level</option><?php foreach($filterOptions['levels'] as $l): ?><option <?= $filters['level']===$l?'selected':'' ?>><?= htmlspecialchars($l) ?></option><?php endforeach; ?></select>
          </div>
          <div class="col-md-1">
            <select name="set" class="form-select"><option value="">Set</option><?php foreach($filterOptions['sets'] as $s): ?><option <?= $filters['set']===$s?'selected':'' ?>><?= htmlspecialchars($s) ?></option><?php endforeach; ?></select>
          </div>
          <div class="col-md-1">
            <select name="gender" class="form-select"><option value="">Gender</option><option <?= $filters['gender']==='Male'?'selected':'' ?>>Male</option><option <?= $filters['gender']==='Female'?'selected':'' ?>>Female</option></select>
          </div>
          <div class="col-md-1">
            <select name="year" class="form-select"><option value="">Year</option><?php foreach(($filterOptions['intake_years'] ?? []) as $y): ?><option <?= $filters['year']===$y?'selected':'' ?>><?= htmlspecialchars($y) ?></option><?php endforeach; ?></select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-search w-100"><i class="fas fa-search me-1"></i> Search</button>
          </div>
        </div>
        <?php if ($showResults): ?>
        <div class="mt-2 text-center">
          <a href="student-management.php" class="btn btn-sm btn-outline-light"><i class="fas fa-times me-1"></i>Clear Search</a>
        </div>
        <?php endif; ?>
      </form>
    </div>
  </section>

  <section id="results-section">
    <?php if ($showResults): ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Search Results <span class="badge bg-secondary ms-1"><?= count($students) ?> found</span></h5>
        <small class="text-muted">Showing up to 100 results</small>
      </div>
      <div class="card section-card p-0">
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchDRLE" type="text" placeholder="Search..." onkeyup="filterTable('srchDRLE','tblDRLE')"></div>
<div class="table-responsive">
          <table class="table table-hover mb-0" id="studentTable">
            <thead class="table-light">
              <tr>
                <th>Name</th><th>Index / NSIN</th><th>Program</th><th>Level</th><th>Set</th><th>Phone</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($students)): ?>
              <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-search fa-2x mb-2 d-block"></i>No students match your search criteria. Try different terms.</td></tr>
              <?php else: $idx=0; foreach(array_slice($students,0,100) as $s): $idx++; ?>
              <tr>
                <td><strong><?= htmlspecialchars($s['full_name'] ?: ($s['surname'].' '.$s['first_name'])) ?></strong></td>
                <td><code><?= htmlspecialchars($s['index_number'] ?: $s['national_id']) ?></code></td>
                <td><?= htmlspecialchars($s['program']) ?></td>
                <td><?= htmlspecialchars($s['level']) ?></td>
                <td><?= htmlspecialchars($s['set']) ?></td>
                <td><?= htmlspecialchars($s['phone']) ?></td>
                <td class="action-btn-group">
                  <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick='showStudentProfile(<?= json_encode($s, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="View Full Profile"><i class="fas fa-eye"></i></button>
                  <?php if ($canManageStudents): ?>
                  <a href="student-add.php?search=<?= urlencode($s['index_number'] ?: $s['student_number']) ?>" class="btn btn-sm btn-outline-info py-0 px-2" title="Edit"><i class="fas fa-edit"></i></a>
                  <?php endif; ?>
                  <?php if ($canEditFinance): ?>
                  <a href="school-bursar.php?student=<?= urlencode($s['index_number'] ?: $s['national_id']) ?>" class="btn btn-sm btn-outline-warning py-0 px-2" title="Fee Statement"><i class="fas fa-file-invoice-dollar"></i></a>
                  <?php endif; ?>
                  <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick='printStudentProfile(<?= json_encode($s, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="Print"><i class="fas fa-print"></i></button>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-user-graduate"></i>
        <h5>Search for Students</h5>
        <p>Use the search fields above to find students. Enter a name, index number, registration number, phone, or use the filters to narrow results.</p>
        <div class="row mt-4 justify-content-center">
          <div class="col-md-3"><div class="stat-card text-center p-3"><i class="fas fa-users fa-2x mb-2" style="color:#1a237e"></i><div class="fw-bold"><?= $stats['total_students'] ?></div><small class="text-muted">Total Students</small></div></div>
          <div class="col-md-3"><div class="stat-card text-center p-3"><i class="fas fa-layer-group fa-2x mb-2" style="color:#2e7d32"></i><div class="fw-bold"><?= $stats['total_programs'] ?></div><small class="text-muted">Programs</small></div></div>
          <div class="col-md-3"><div class="stat-card text-center p-3"><i class="fas fa-tags fa-2x mb-2" style="color:#e65100"></i><div class="fw-bold"><?= $stats['total_sets'] ?></div><small class="text-muted">Sets</small></div></div>
        </div>
      </div>
    <?php endif; ?>
  </section>

  <?php if ($canEditAdmission && !$showResults): ?>
  <section id="admissions" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-user-plus me-2"></i>Student Registration & Admission</h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#admissionModal"><i class="fas fa-plus me-1"></i>Record Admission</button>
    </div>
    <?php if(empty($pendingAdmissions)): ?><p class="text-muted small">No pending admission records.</p><?php else: ?>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchDUMP" type="text" placeholder="Search..." onkeyup="filterTable('srchDUMP','tblDUMP')"></div>
<div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Application</th><th>Applicant</th><th>Program</th><th>Year</th><th>Status</th><th>Date</th></tr></thead><tbody><?php foreach($pendingAdmissions as $a): ?>
      <tr><td><code><?= htmlspecialchars($a['application_number']) ?></code></td><td><?= htmlspecialchars($a['applicant_name']) ?></td><td><?= htmlspecialchars($a['program']) ?></td><td><?= htmlspecialchars($a['academic_year'] ?? '-') ?></td><td><span class="badge bg-warning text-dark"><?= htmlspecialchars($a['admission_status']) ?></span></td><td><?= date('d M Y',strtotime($a['application_date'])) ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($canEditAcademic && !$showResults): ?>
  <section id="courses-section" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-book me-2"></i>Active Courses</h5>
    </div>
    <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchUWHD" type="text" placeholder="Search..." onkeyup="filterTable('srchUWHD','tblUWHD')"></div>
<div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Code</th><th>Course</th><th>Program</th><th>Level</th><th>Semester</th><th>Type</th></tr></thead><tbody><?php if(empty($courses)): ?><tr><td colspan="6" class="text-muted py-4">No active courses yet.</td></tr><?php else: foreach($courses as $c): ?>
      <tr><td><code><?= htmlspecialchars($c['course_code']) ?></code></td><td><?= htmlspecialchars($c['course_name']) ?></td><td><?= htmlspecialchars($c['program']) ?></td><td><?= htmlspecialchars($c['level']) ?></td><td><?= htmlspecialchars($c['semester']) ?></td><td><?= $c['is_compulsory'] ? '<span class="badge bg-primary">Compulsory</span>' : '<span class="badge bg-secondary">Elective</span>' ?></td></tr>
    <?php endforeach; endif; ?></tbody></table></div>
  </section>
  <?php endif; ?>
</div>

<!-- Student Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content profile-card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Student Profile</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="card-body" id="profileBody"><div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x mb-3" style="color:#1a237e"></i><p>Loading profile...</p></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" onclick="printProfileFromModal()"><i class="fas fa-print me-1"></i> Print</button></div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
var currentProfileStudent = null;
var userPerms = { canEditAcademic: <?= json_encode($canEditAcademic) ?>, canEditFinance: <?= json_encode($canEditFinance) ?>, canEditAdmission: <?= json_encode($canEditAdmission) ?>, canManageStudents: <?= json_encode($canManageStudents) ?>, canDelete: <?= json_encode($canDelete) ?> };

function showStudentProfile(s) {
    currentProfileStudent = s;
    var html = '<div class="row">';
    html += '<div class="col-md-4 text-center mb-3">';
    if (s.passport_photo) html += '<img src="../' + s.passport_photo + '" class="img-thumbnail rounded" style="max-width:180px;max-height:200px;object-fit:cover;">';
    else html += '<div class="border rounded p-4 text-muted d-inline-block"><i class="fas fa-user fa-5x"></i></div>';
    html += '<h5 class="mt-2">' + esc(s.full_name || (s.surname + ' ' + s.first_name)) + '</h5>';
    html += '<span class="badge bg-primary">' + esc(s.program) + '</span> ';
    if (s.level) html += '<span class="badge bg-secondary">Level ' + esc(s.level) + '</span>';
    html += '</div><div class="col-md-8">';
    html += '<ul class="nav nav-tabs" id="profileTabs" role="tablist">';
    html += '<li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-personal">Personal</button></li>';
    html += '<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-academic">Academic</button></li>';
    html += '<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-finance">Finance</button></li>';
    html += '</ul>';
    html += '<div class="tab-content mt-3"><div class="tab-pane active" id="tab-personal">';
    html += '<table class="profile-info-table"><tbody>';
    html += '<tr><td>Surname</td><td>' + esc(s.surname) + '</td></tr>';
    html += '<tr><td>First Name</td><td>' + esc(s.first_name) + '</td></tr>';
    html += '<tr><td>Other Name</td><td>' + esc(s.other_name) + '</td></tr>';
    html += '<tr><td>Gender</td><td>' + esc(s.gender) + '</td></tr>';
    html += '<tr><td>Date of Birth</td><td>' + esc(s.date_of_birth) + '</td></tr>';
    html += '<tr><td>Phone</td><td>' + esc(s.phone) + '</td></tr>';
    html += '<tr><td>Email</td><td>' + esc(s.email) + '</td></tr>';
    html += '<tr><td>District</td><td>' + esc(s.district) + '</td></tr>';
    html += '<tr><td>Nationality</td><td>' + esc(s.nationality) + '</td></tr>';
    html += '<tr><td>National ID</td><td><code>' + esc(s.national_id) + '</code></td></tr>';
    html += '</tbody></table></div>';
    html += '<div class="tab-pane" id="tab-academic">';
    html += '<table class="profile-info-table"><tbody>';
    html += '<tr><td>Index Number</td><td><code>' + esc(s.index_number) + '</code></td></tr>';
    html += '<tr><td>Registration #</td><td><code>' + esc(s.registration_number) + '</code></td></tr>';
    html += '<tr><td>Student #</td><td><code>' + esc(s.student_number) + '</code></td></tr>';
    html += '<tr><td>Program</td><td>' + esc(s.program) + '</td></tr>';
    html += '<tr><td>Level</td><td>' + esc(s.level) + '</td></tr>';
    html += '<tr><td>Set</td><td>' + esc(s.set) + '</td></tr>';
    html += '<tr><td>Intake Year</td><td>' + esc(s.intake_year) + '</td></tr>';
    html += '<tr><td>Intake Period</td><td>' + esc(s.intake_period) + '</td></tr>';
    html += '<tr><td>Course Codes</td><td>' + esc(s.course_codes) + '</td></tr>';
    html += '</tbody></table></div>';
    html += '<div class="tab-pane" id="tab-finance">';
    html += '<p class="text-muted small">Financial records for this student. <a href="school-bursar.php?student=' + urlenc(s.index_number || s.student_number) + '" target="_blank">View full fee statement &rarr;</a></p>';
    html += '</div></div></div></div>';
    html += '<div class="mt-3 pt-3 border-top d-flex gap-2">';
    if (userPerms.canManageStudents) {
        html += '<a href="student-add.php?search=' + urlenc(s.index_number || s.student_number) + '" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Edit Profile</a>';
    }
    if (userPerms.canEditFinance) {
        html += '<a href="school-bursar.php?student=' + urlenc(s.index_number || s.national_id) + '" class="btn btn-sm btn-outline-warning"><i class="fas fa-file-invoice-dollar me-1"></i>Fee Statement</a>';
    }
    if (userPerms.canEditAcademic) {
        html += '<a href="student-attendance.php?search=' + urlenc(s.index_number || s.student_number) + '" class="btn btn-sm btn-outline-info"><i class="fas fa-calendar-check me-1"></i>Attendance</a>';
    }
    html += '<button class="btn btn-sm btn-outline-secondary" onclick="printStudentProfile(s)"><i class="fas fa-print me-1"></i>Print</button>';
    html += '</div>';
    document.getElementById('profileBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('profileModal')).show();
}

function printProfileFromModal() {
    if (!currentProfileStudent) return;
    printStudentProfile(currentProfileStudent);
}

function printStudentProfile(s) {
    var w = window.open('', '_blank');
    w.document.write('<html><head><title>Student Profile - ISNM</title>');
    w.document.write('<style>body{font-family:Arial,sans-serif;padding:40px;color:#333;}h2{color:#1a237e;border-bottom:2px solid #1a237e;padding-bottom:8px;}.section{margin:24px 0;}h4{color:#1a237e;margin-bottom:12px;}.row{display:flex;margin:3px 0;}.label{font-weight:700;width:200px;color:#555;}.value{flex:1;}</style></head>');
    w.document.write('<body>');
    w.document.write('<div style="text-align:center;margin-bottom:20px;"><h2 style="border:none;">ISNM - Student Profile</h2><p style="color:#999;">Iganga School of Nursing and Midwifery</p></div>');
    w.document.write('<div class="section"><h4>Personal Information</h4>');
    w.document.write('<div class="row"><span class="label">Full Name:</span><span class="value">' + esc(s.full_name || (s.surname + ' ' + s.first_name)) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Surname:</span><span class="value">' + esc(s.surname) + '</span></div>');
    w.document.write('<div class="row"><span class="label">First Name:</span><span class="value">' + esc(s.first_name) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Other Name:</span><span class="value">' + esc(s.other_name) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Gender:</span><span class="value">' + esc(s.gender) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Date of Birth:</span><span class="value">' + esc(s.date_of_birth) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Phone:</span><span class="value">' + esc(s.phone) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Email:</span><span class="value">' + esc(s.email) + '</span></div>');
    w.document.write('<div class="row"><span class="label">District:</span><span class="value">' + esc(s.district) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Nationality:</span><span class="value">' + esc(s.nationality) + '</span></div>');
    w.document.write('</div><div class="section"><h4>Academic Information</h4>');
    w.document.write('<div class="row"><span class="label">Index Number:</span><span class="value">' + esc(s.index_number) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Registration #:</span><span class="value">' + esc(s.registration_number) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Student #:</span><span class="value">' + esc(s.student_number) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Program:</span><span class="value">' + esc(s.program) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Level:</span><span class="value">' + esc(s.level) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Set:</span><span class="value">' + esc(s.set) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Intake Year:</span><span class="value">' + esc(s.intake_year) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Intake Period:</span><span class="value">' + esc(s.intake_period) + '</span></div>');
    w.document.write('</div>');
    w.document.write('<p style="margin-top:40px;color:#999;font-size:11px;">Generated on ' + new Date().toLocaleDateString() + ' | ISNM Student Management System</p>');
    w.document.write('</body></html>');
    w.document.close();
    setTimeout(function() { w.print(); }, 500);
}

function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function urlenc(s) { return encodeURIComponent(s || ''); }

document.getElementById('studentSearchForm')?.addEventListener('submit', function(e) {
    var q = this.querySelector('[name="student_search"]').value.trim();
    if (!q && !this.querySelector('[name="program"]').value && !this.querySelector('[name="level"]').value && !this.querySelector('[name="set"]').value && !this.querySelector('[name="gender"]').value && !this.querySelector('[name="year"]').value) {
        e.preventDefault();
        alert('Please enter a search term or select a filter.');
    }
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
</body>
</html>
