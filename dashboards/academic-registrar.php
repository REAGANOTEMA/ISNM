<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
$ctx = bootstrapStaffDashboard(['registrar']);
$user = $ctx['user'];
$students_conn = getStudentsConnection();
$staff_conn    = getStaffConnection();
$website_conn  = $ctx['website'];

// Helper
function safeCount($conn, $sql) {
    $r = $conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return intval($row['c'] ?? 0);
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
</head>
<body>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main" style="margin-left:270px">
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
              <button class="btn btn-sm btn-outline-primary btn-tbl" onclick="editStudent(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-outline-danger btn-tbl" onclick="trashStudent(<?= $s['id'] ?>, '<?= addslashes($fullname) ?>')"><i class="fas fa-trash"></i></button>
              <button class="btn btn-sm btn-outline-info btn-tbl" onclick="viewStudent(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="fas fa-eye"></i></button>
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
// Smooth scroll for sidebar nav
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
