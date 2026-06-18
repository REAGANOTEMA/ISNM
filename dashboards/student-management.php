<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$user = $ctx['user'];

$role = $_SESSION['role'] ?? '';
$allowed = ['School Secretary','Director ICT','Academic Registrar','Registrar','Director General','CEO','School Principal','Principal','HOD','Lecturer'];
if (!in_array($role, $allowed) && !$ctx['auth']->hasFullInstitutionAccess($role)) {
    header('Location: ../staff-login.php?error=unauthorized'); exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../views/student_data_loader.php';

$conn = getStudentsConnection();
$uid = $_SESSION['user_id'] ?? 0;
$uname = $_SESSION['full_name'] ?? 'User';

function acad_q($conn, $sql) {
    $r = $conn ? $conn->query($sql) : false;
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row[array_key_first($row)] ?? 0;
}

$dataLoader = new StudentDataLoader();
$stats = $dataLoader->getStatistics();
$search = trim($_GET['student_search'] ?? '');
$filters = [
    'program' => $_GET['program'] ?? '',
    'level' => $_GET['level'] ?? '',
    'set' => $_GET['set'] ?? '',
    'gender' => $_GET['gender'] ?? '',
    'year' => $_GET['year'] ?? '',
];
$students = $dataLoader->searchStudents($search, $filters);
$filterOptions = $dataLoader->getFilterOptions();

$total_students = acad_q($conn, "SELECT COUNT(*) v FROM students WHERE status='Active'");
$pending_admissions = acad_q($conn, "SELECT COUNT(*) v FROM student_admissions WHERE admission_status IN('Applied','Interview','Admitted')");
$active_courses = acad_q($conn, "SELECT COUNT(*) v FROM course_catalog WHERE status='Active'");
$pending_results = acad_q($conn, "SELECT COUNT(*) v FROM examination_results WHERE status='Pending'");
$clinical_active = acad_q($conn, "SELECT COUNT(*) v FROM clinical_placements WHERE status='Active'");
$overdue_attendance = acad_q($conn, "SELECT COUNT(*) v FROM student_attendance WHERE attendance_date < CURDATE() AND status='Absent'");
$fee_not_cleared = acad_q($conn, "SELECT COUNT(DISTINCT student_id) v FROM student_invoices WHERE status IN('Pending','Partially Paid','Overdue')");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($conn && $action === 'record_admission') {
        $app = $conn->real_escape_string($_POST['application_number'] ?? ('APP-'.date('Ymd').'-'.mt_rand(1000,9999)));
        $name = $conn->real_escape_string($_POST['applicant_name'] ?? '');
        $program = $conn->real_escape_string($_POST['program'] ?? '');
        $year = $conn->real_escape_string($_POST['academic_year'] ?? date('Y'));
        $status = in_array($_POST['admission_status'] ?? '', ['Applied','Interview','Admitted','Rejected','Deferred','Enrolled']) ? $_POST['admission_status'] : 'Applied';
        $date = $conn->real_escape_string($_POST['application_date'] ?? date('Y-m-d'));
        if ($name && $program) {
            $conn->query("INSERT INTO student_admissions (application_number,applicant_name,program,academic_year,admission_status,application_date,decided_by,remarks) VALUES ('$app','$name','$program','$year','$status','$date',$uid,'') ON DUPLICATE KEY UPDATE applicant_name=VALUES(applicant_name),program=VALUES(program),admission_status=VALUES(admission_status)");
            $_SESSION['success'] = 'Admission record saved.';
        }
    }
    if ($conn && $action === 'register_course') {
        $sid = intval($_POST['student_id'] ?? 0);
        $cid = intval($_POST['course_id'] ?? 0);
        $ay = $conn->real_escape_string($_POST['academic_year'] ?? date('Y'));
        $sem = $conn->real_escape_string($_POST['semester'] ?? 'First Semester');
        if ($sid && $cid) {
            $conn->query("INSERT INTO course_registrations (student_id,course_id,academic_year,semester,registration_status) VALUES ($sid,$cid,'$ay','$sem','Registered') ON DUPLICATE KEY UPDATE registration_status='Registered'");
            $_SESSION['success'] = 'Course registration saved.';
        }
    }
    if ($conn && $action === 'enter_score') {
        $session = intval($_POST['examination_session_id'] ?? 0);
        $sid = intval($_POST['student_id'] ?? 0);
        $score = floatval($_POST['score'] ?? 0);
        $max = floatval($_POST['max_score'] ?? 100);
        if ($session && $sid) {
            $conn->query("INSERT INTO assessment_scores (examination_session_id,student_id,score,max_score,entered_by) VALUES ($session,$sid,$score,$max,$uid) ON DUPLICATE KEY UPDATE score=VALUES(score),max_score=VALUES(max_score)");
            $_SESSION['success'] = 'Score saved.';
        }
    }
    header('Location: student-management.php'); exit;
}

$courses = [];
if ($conn) {
    $r = $conn->query("SELECT id, course_code, course_name, program, level, semester, is_compulsory FROM course_catalog WHERE status='Active' ORDER BY program, level, semester, course_code LIMIT 80");
    if ($r) while ($row = $r->fetch_assoc()) $courses[] = $row;
}

$pendingAdmissions = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM student_admissions WHERE admission_status IN('Applied','Interview','Admitted') ORDER BY application_date DESC LIMIT 15");
    if ($r) while ($row = $r->fetch_assoc()) $pendingAdmissions[] = $row;
}

$recentResults = [];
if ($conn) {
    $r = $conn->query("SELECT er.*, CONCAT(s.first_name,' ',s.surname) sname, s.student_number, cc.course_code FROM examination_results er JOIN students s ON er.student_id=s.id LEFT JOIN course_catalog cc ON er.course_id=cc.id ORDER BY er.created_at DESC LIMIT 15");
    if ($r) while ($row = $r->fetch_assoc()) $recentResults[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main" style="margin-left:270px">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <button class="btn btn-sm btn-outline-secondary d-md-none me-2" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
      <h4 class="d-inline fw-bold" style="color:var(--primary)">Student Management Module</h4>
      <span class="text-muted small ms-2">Core academic system</span>
    </div>
    <small class="text-muted"><?= date('l, d M Y') ?></small>
  </div>

  <?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
  <?php if(!empty($_SESSION['error'])): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

  <section id="overview">
    <div class="row g-3 mb-4">
      <?php $cards=[['Total Enrolled',$stats['total_students'],'users'],['Pending Admissions',$pending_admissions,'user-check'],['Active Courses',$active_courses,'book'],['Pending Results',$pending_results,'clipboard-check'],['Clinical Placements',$clinical_active,'hospital-user'],['Absent Records',$overdue_attendance,'calendar-times'],['Fee Not Cleared',$fee_not_cleared,'exclamation-triangle']]; foreach($cards as $c): ?>
      <div class="col-6 col-md-3"><div class="stat-card"><div class="fs-3 fw-bold"><?= $c[1] ?></div><div class="text-muted small"><i class="fas fa-<?= $c[2] ?> me-1"></i><?= $c[0] ?></div></div></div>
      <?php endforeach; ?>
    </div>

  </section>

  <section id="admissions" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-user-plus me-2"></i>Student Registration & Admission</h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#admissionModal"><i class="fas fa-plus me-1"></i>Record Admission</button>
    </div>
    <?php if(empty($pendingAdmissions)): ?><p class="text-muted small">No pending admission records.</p><?php else: ?>
    <div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Application</th><th>Applicant</th><th>Program</th><th>Year</th><th>Status</th><th>Date</th></tr></thead><tbody><?php foreach($pendingAdmissions as $a): ?>
      <tr><td><code><?= htmlspecialchars($a['application_number']) ?></code></td><td><?= htmlspecialchars($a['applicant_name']) ?></td><td><?= htmlspecialchars($a['program']) ?></td><td><?= htmlspecialchars($a['academic_year'] ?? '-') ?></td><td><span class="badge bg-warning text-dark"><?= htmlspecialchars($a['admission_status']) ?></span></td><td><?= date('d M Y',strtotime($a['application_date'])) ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>

  <section id="students" class="section-card">
    <h5><i class="fas fa-users me-2"></i>Student Academic Profile Search</h5>
    <form method="GET" class="row g-2 mb-3">
      <div class="col-md-4"><input type="text" name="student_search" class="form-control form-control-sm" placeholder="Search all student data" value="<?= htmlspecialchars($search) ?>"></div>
      <div class="col-md-2"><select name="program" class="form-select form-select-sm"><option value="">All Programs</option><?php foreach($filterOptions['programs'] as $p): ?><option <?= $filters['program']===$p?'selected':'' ?>><?= htmlspecialchars($p) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><select name="level" class="form-select form-select-sm"><option value="">All Levels</option><?php foreach($filterOptions['levels'] as $l): ?><option <?= $filters['level']===$l?'selected':'' ?>><?= htmlspecialchars($l) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><select name="set" class="form-select form-select-sm"><option value="">All Sets</option><?php foreach($filterOptions['sets'] as $s): ?><option <?= $filters['set']===$s?'selected':'' ?>><?= htmlspecialchars($s) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i></button></div>
    </form>
    <div class="table-responsive"><table class="table table-sm table-hover" id="studentTable"><thead class="table-light"><tr><th>Name</th><th>Index / NSIN</th><th>Program</th><th>Level</th><th>Set</th><th>Year</th><th>Phone</th><th>Actions</th></tr></thead><tbody><?php if(empty($students)): ?><tr><td colspan="8" class="text-center py-4 text-muted">No students found. Try another name, index number, NSIN, phone, source file, or course code.</td></tr><?php else: $idx=0; foreach(array_slice($students,0,100) as $s): $idx++; ?>
      <tr>
        <td><strong><?= htmlspecialchars($s['full_name'] ?: ($s['surname'].' '.$s['first_name'])) ?></strong></td>
        <td><code><?= htmlspecialchars($s['index_number'] ?: $s['national_id']) ?></code></td>
        <td><?= htmlspecialchars($s['program']) ?></td>
        <td><?= htmlspecialchars($s['level']) ?></td>
        <td><?= htmlspecialchars($s['set']) ?></td>
        <td><?= htmlspecialchars($s['intake_year']) ?></td>
        <td><?= htmlspecialchars($s['phone']) ?></td>
        <td nowrap>
          <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick='showStudent(<?= json_encode($s, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="View Profile"><i class="fas fa-eye"></i></button>
          <button class="btn btn-sm btn-outline-info py-0 px-1" onclick='printStudent(<?= json_encode($s, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="Print Profile"><i class="fas fa-print"></i></button>
          <a href="school-bursar.php?student=<?= urlencode($s['index_number'] ?: $s['national_id']) ?>" class="btn btn-sm btn-outline-warning py-0 px-1" title="Fee Statement"><i class="fas fa-file-invoice-dollar"></i></a>
        </td>
      </tr>
    <?php endforeach; endif; ?></tbody></table></div>

    <!-- Student Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Student Profile</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="profileBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" onclick="printProfileFromModal()"><i class="fas fa-print"></i> Print</button></div></div></div></div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content"><div class="modal-header bg-danger text-white"><h6 class="modal-title">Confirm Deactivate</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="deleteBody">Deactivate this record?</div><div class="modal-footer"><form method="POST" id="deleteForm" action="student-add.php"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="deleteId" value="0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Deactivate</button></form></div></div></div></div>

  </section>

  <section id="courses" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-book me-2"></i>Course Registration</h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal"><i class="fas fa-plus me-1"></i>Register Course</button>
    </div>
    <div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Code</th><th>Course</th><th>Program</th><th>Level</th><th>Semester</th><th>Type</th></tr></thead><tbody><?php if(empty($courses)): ?><tr><td colspan="6" class="text-muted py-4">No active courses yet. Add courses in course_catalog.</td></tr><?php else: foreach($courses as $c): ?>
      <tr><td><code><?= htmlspecialchars($c['course_code']) ?></code></td><td><?= htmlspecialchars($c['course_name']) ?></td><td><?= htmlspecialchars($c['program']) ?></td><td><?= htmlspecialchars($c['level']) ?></td><td><?= htmlspecialchars($c['semester']) ?></td><td><?= $c['is_compulsory'] ? '<span class="badge bg-primary">Compulsory</span>' : '<span class="badge bg-secondary">Elective</span>' ?></td></tr>
    <?php endforeach; endif; ?></tbody></table></div>
  </section>

  <section id="exams" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-clipboard-list me-2"></i>Examination & Results Management</h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#scoreModal"><i class="fas fa-plus me-1"></i>Enter Score</button>
    </div>
    <div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Student</th><th>Course</th><th>Year</th><th>Semester</th><th>Score</th><th>Grade</th><th>Status</th></tr></thead><tbody><?php if(empty($recentResults)): ?><tr><td colspan="7" class="text-muted py-4">No results entered yet.</td></tr><?php else: foreach($recentResults as $r): ?>
      <tr><td><strong><?= htmlspecialchars($r['sname']) ?></strong><br><small><?= htmlspecialchars($r['student_number']) ?></small></td><td><?= htmlspecialchars($r['course_code']) ?></td><td><?= htmlspecialchars($r['academic_year']) ?></td><td><?= htmlspecialchars($r['semester']) ?></td><td><?= htmlspecialchars($r['total_score']) ?></td><td><?= htmlspecialchars($r['grade'] ?? '-') ?></td><td><span class="badge <?= $r['status']==='Published'?'bg-success':($r['status']==='Approved'?'bg-primary':'bg-warning text-dark') ?>"><?= htmlspecialchars($r['status']) ?></span></td></tr>
    <?php endforeach; endif; ?></tbody></table></div>
  </section>

  <section id="attendance" class="section-card">
    <h5><i class="fas fa-calendar-check me-2"></i>Student Attendance Management</h5>
    <div class="row g-3">
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM student_attendance WHERE attendance_date=CURDATE()") ?></div><small>Today's records</small></div></div>
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold text-danger"><?= acad_q($conn,"SELECT COUNT(*) v FROM student_attendance WHERE attendance_date=CURDATE() AND status='Absent'") ?></div><small>Absent today</small></div></div>
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM student_attendance WHERE attendance_type='Clinical'") ?></div><small>Clinical/practical records</small></div></div>
    </div>
  </section>

  <section id="clinical" class="section-card">
    <h5><i class="fas fa-hospital-user me-2"></i>Clinical Placement Tracking</h5>
    <p class="text-muted small">Placement allocation, logbook tracking, supervisor evaluations, skills competency, and completion verification are backed by <code>clinical_placements</code>, <code>clinical_logbooks</code>, and <code>clinical_evaluations</code>.</p>
    <div class="row g-3">
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM clinical_placements WHERE status='Active'") ?></div><small>Active placements</small></div></div>
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM clinical_logbooks") ?></div><small>Logbook entries</small></div></div>
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM clinical_evaluations WHERE completion_verified=1") ?></div><small>Verified completions</small></div></div>
    </div>
  </section>

  <section id="discipline" class="section-card">
    <h5><i class="fas fa-gavel me-2"></i>Discipline & Student Conduct</h5>
    <p class="text-muted small">Cases, warnings, committee decisions, suspension/expulsion records, and clinical behavior notes are stored in <code>student_discipline_cases</code>.</p>
  </section>

  <section id="finance-link" class="section-card">
    <h5><i class="fas fa-file-invoice-dollar me-2"></i>Fees & Finance Link</h5>
    <div class="row g-3">
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold text-danger"><?= $fee_not_cleared ?></div><small>Students with balances</small></div></div>
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM student_invoices WHERE status='Paid'") ?></div><small>Paid invoices</small></div></div>
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM payment_receipts") ?></div><small>Receipts generated</small></div></div>
    </div>
  </section>

  <section id="communications" class="section-card">
    <h5><i class="fas fa-bullhorn me-2"></i>Communication System</h5>
    <p class="text-muted small">Student announcements, SMS/email alerts, lecturer to student messages, and emergency alerts are queued through <code>student_notices</code> and <code>notification_queue</code>.</p>
  </section>

  <section id="hostel-library" class="section-card">
    <h5><i class="fas fa-bed me-2"></i>Hostel & Library Integration</h5>
    <div class="row g-3">
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM student_hostels WHERE status='Allocated'") ?></div><small>Allocated rooms</small></div></div>
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM library_borrowings WHERE status='Borrowed'") ?></div><small>Books borrowed</small></div></div>
      <div class="col-md-4"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= acad_q($conn,"SELECT COUNT(*) v FROM library_borrowings WHERE status='Overdue'") ?></div><small>Overdue books</small></div></div>
    </div>
  </section>

  <section id="self-service" class="section-card">
    <h5><i class="fas fa-user-graduate me-2"></i>Student Self Service Portal</h5>
    <div class="row g-3">
      <div class="col-md-3"><div class="border rounded p-3">Profile, biodata, guardian and next of kin</div></div>
      <div class="col-md-3"><div class="border rounded p-3">Course registration and add/drop requests</div></div>
      <div class="col-md-3"><div class="border rounded p-3">Results, transcripts and fee status</div></div>
      <div class="col-md-3"><div class="border rounded p-3">Timetable, clinical placement and notices</div></div>
    </div>
  </section>

  <section id="reports" class="section-card">
    <h5><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h5>
    <div class="row g-3">
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $stats['total_students'] ?></div><small>Enrolled Students</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $stats['total_programs'] ?></div><small>Programs</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $stats['total_sets'] ?></div><small>Sets</small></div></div>
      <div class="col-md-3"><div class="border rounded p-3 text-center"><div class="fs-4 fw-bold"><?= $stats['total_years'] ?></div><small>Intake years</small></div></div>
    </div>
  </section>
</div>

<!-- Admission Modal -->
<div class="modal fade" id="admissionModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" class="modal-content"><input type="hidden" name="action" value="record_admission"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Record Admission</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Application Number</label><input type="text" name="application_number" class="form-control" value="APP-<?= date('Ymd') ?>"></div><div class="col-md-6"><label class="form-label">Applicant Name</label><input type="text" name="applicant_name" class="form-control" required></div><div class="col-md-6"><label class="form-label">Program</label><input type="text" name="program" class="form-control" required></div><div class="col-md-3"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?= date('Y') ?>"></div><div class="col-md-3"><label class="form-label">Status</label><select name="admission_status" class="form-select"><option>Applied</option><option>Interview</option><option>Admitted</option><option>Deferred</option><option>Rejected</option></select></div><div class="col-md-6"><label class="form-label">Application Date</label><input type="date" name="application_date" class="form-control" value="<?= date('Y-m-d') ?>"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Admission</button></div></form></div></div>

<!-- Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><input type="hidden" name="action" value="register_course"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Register Course</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-12"><label class="form-label">Student ID</label><input type="number" name="student_id" class="form-control" required></div><div class="col-12"><label class="form-label">Course ID</label><select name="course_id" class="form-select"><?php foreach($courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_code'].' , '.$c['course_name']) ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?= date('Y') ?>"></div><div class="col-md-6"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>First Semester</option><option>Second Semester</option><option>Third Semester</option></select></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Register</button></div></form></div></div>

<!-- Score Modal -->
<div class="modal fade" id="scoreModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><input type="hidden" name="action" value="enter_score"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Enter Assessment Score</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-12"><label class="form-label">Examination Session ID</label><input type="number" name="examination_session_id" class="form-control" required></div><div class="col-12"><label class="form-label">Student ID</label><input type="number" name="student_id" class="form-control" required></div><div class="col-md-6"><label class="form-label">Score</label><input type="number" name="score" class="form-control" step="0.1" required></div><div class="col-md-6"><label class="form-label">Max Score</label><input type="number" name="max_score" class="form-control" value="100"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Score</button></div></form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.sidebar nav a[href^="#"]').forEach(a=>{a.addEventListener('click',e=>{e.preventDefault();const t=document.querySelector(a.getAttribute('href'));if(t)t.scrollIntoView({behavior:'smooth',block:'start'});document.querySelectorAll('.sidebar nav a').forEach(x=>x.classList.remove('active'));a.classList.add('active');});});

var currentProfileStudent = null;
function showStudent(s) {
    currentProfileStudent = s;
    var html = '<div class="row mb-3"><div class="col-md-8"><table class="table table-sm table-borderless">';
    html += '<tr><th width="140">Full Name</th><td>' + esc(s.full_name || (s.surname + ' ' + s.first_name)) + '</td></tr>';
    html += '<tr><th>Surname</th><td>' + esc(s.surname) + '</td></tr>';
    html += '<tr><th>First Name</th><td>' + esc(s.first_name) + '</td></tr>';
    html += '<tr><th>Other Name</th><td>' + esc(s.other_name) + '</td></tr>';
    html += '<tr><th>Gender</th><td>' + esc(s.gender) + '</td></tr>';
    html += '<tr><th>Index Number</th><td>' + esc(s.index_number) + '</td></tr>';
    html += '<tr><th>Registration #</th><td>' + esc(s.registration_number) + '</td></tr>';
    html += '<tr><th>Student #</th><td>' + esc(s.student_number) + '</td></tr>';
    html += '<tr><th>National ID</th><td>' + esc(s.national_id) + '</td></tr>';
    html += '<tr><th>Date of Birth</th><td>' + esc(s.date_of_birth) + '</td></tr>';
    html += '<tr><th>Phone</th><td>' + esc(s.phone) + '</td></tr>';
    html += '<tr><th>Email</th><td>' + esc(s.email) + '</td></tr>';
    html += '<tr><th>District</th><td>' + esc(s.district) + '</td></tr>';
    html += '<tr><th>Nationality</th><td>' + esc(s.nationality) + '</td></tr>';
    html += '</table></div><div class="col-md-4 text-center">';
    if (s.passport_photo) html += '<img src="../' + s.passport_photo + '" class="img-thumbnail" style="max-width:180px;max-height:200px;">';
    else html += '<div class="border rounded p-4 text-muted"><i class="fas fa-user fa-4x"></i></div>';
    html += '</div></div>';
    html += '<h6 class="fw-bold">Academic Info</h6><table class="table table-sm table-borderless">';
    html += '<tr><th width="140">Program</th><td>' + esc(s.program) + '</td></tr>';
    html += '<tr><th>Level</th><td>' + esc(s.level) + '</td></tr>';
    html += '<tr><th>Set</th><td>' + esc(s.set) + '</td></tr>';
    html += '<tr><th>Intake Year</th><td>' + esc(s.intake_year) + '</td></tr>';
    html += '<tr><th>Intake Period</th><td>' + esc(s.intake_period) + '</td></tr>';
    html += '<tr><th>Source</th><td>' + esc(s.source_file) + '</td></tr>';
    html += '<tr><th>Course Codes</th><td>' + esc(s.course_codes) + '</td></tr>';
    html += '</table>';
    document.getElementById('profileBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('profileModal')).show();
}
function printProfileFromModal() {
    if (!currentProfileStudent) return;
    printStudent(currentProfileStudent);
}
function printStudent(s) {
    var w = window.open('', '_blank');
    w.document.write('<html><head><title>Student Profile</title>');
    w.document.write('<style>body{font-family:Arial,sans-serif;padding:40px;}h2{color:#1a237e;border-bottom:2px solid #1a237e;padding-bottom:8px;}.section{margin:20px 0;}.row{display:flex;margin:4px 0;}.label{font-weight:700;width:180px;color:#555;}.value{flex:1;}</style></head>');
    w.document.write('<body>');
    w.document.write('<div style="text-align:center;margin-bottom:20px;"><h2 style="border:none;">ISNM - Student Profile</h2></div>');
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
    w.document.write('<div class="row"><span class="label">National ID:</span><span class="value">' + esc(s.national_id) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Program:</span><span class="value">' + esc(s.program) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Level:</span><span class="value">' + esc(s.level) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Set:</span><span class="value">' + esc(s.set) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Intake Year:</span><span class="value">' + esc(s.intake_year) + '</span></div>');
    w.document.write('</div>');
    w.document.write('<p style="margin-top:40px;color:#999;font-size:11px;">Generated on ' + new Date().toLocaleDateString() + ' | ISNM Student Management System</p>');
    w.document.write('</body></html>');
    w.document.close();
    setTimeout(function() { w.print(); }, 500);
}
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
