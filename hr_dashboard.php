<?php
require_once __DIR__ . '/auth-service.php';

if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
    header('Location: staff-login.php'); exit;
}
$role = $_SESSION['role'] ?? '';
$allowed_roles = ['HR Manager','Director General','CEO','School Principal'];
if (!in_array($role, $allowed_roles) && !$auth_service->hasFullInstitutionAccess($role)) {
    header('Location: staff-login.php?error=unauthorized'); exit;
}

require_once __DIR__ . '/config/database.php';
$sconn = getStaffConnection();
$uid   = $_SESSION['user_id'];
$uname = $_SESSION['full_name'];

function hrq($conn, $sql) {
    $r = $conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row[array_key_first($row)] ?? 0;
}

// ── Stats ───────────────────────────────────────────────────
$total_staff        = hrq($sconn, "SELECT COUNT(*) v FROM staff WHERE status='Active'");
$on_leave_today     = hrq($sconn, "SELECT COUNT(DISTINCT staff_id) v FROM staff_leave_requests WHERE status='Approved' AND CURDATE() BETWEEN start_date AND end_date");
$pending_leaves     = hrq($sconn, "SELECT COUNT(*) v FROM staff_leave_requests WHERE status='Pending'");
$expiring_contracts = hrq($sconn, "SELECT COUNT(*) v FROM staff_contracts WHERE status='Active' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)");
$open_jobs          = hrq($sconn, "SELECT COUNT(*) v FROM recruitment_jobs WHERE status='Open'");
$pending_apps       = hrq($sconn, "SELECT COUNT(*) v FROM recruitment_applications WHERE status='Received'");
$expiring_compliance= hrq($sconn, "SELECT COUNT(*) v FROM compliance_records WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND status='Valid'");
$disciplinary_open  = hrq($sconn, "SELECT COUNT(*) v FROM disciplinary_records WHERE status IN('Pending','Under Investigation')");

// ── Department breakdown ─────────────────────────────────────
$dept_stats = [];
$r = $sconn->query("SELECT department, COUNT(*) cnt FROM staff WHERE status='Active' GROUP BY department ORDER BY cnt DESC");
if ($r) while ($row = $r->fetch_assoc()) $dept_stats[] = $row;

// ── All staff list ───────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$filter_dept = $_GET['dept'] ?? '';
$filter_status = $_GET['status'] ?? '';
$where = ["1=1"];
$staff_list = [];
if ($search) { $s = $sconn->real_escape_string($search); $where[] = "(s.full_name LIKE '%$s%' OR s.email LIKE '%$s%' OR s.staff_id LIKE '%$s%' OR s.position LIKE '%$s%')"; }
if ($filter_dept)   { $d = $sconn->real_escape_string($filter_dept);   $where[] = "s.department='$d'"; }
if ($filter_status) { $st = $sconn->real_escape_string($filter_status); $where[] = "s.status='$st'"; }
$sql_w = implode(' AND ', $where);
$r = $sconn->query("SELECT s.id,s.staff_id,s.full_name,s.email,s.phone,s.position,s.department,s.status,s.hire_date,sr.role_name
    FROM staff s LEFT JOIN staff_roles sr ON s.role_id=sr.id WHERE $sql_w ORDER BY s.full_name LIMIT 50");
if ($r) while ($row = $r->fetch_assoc()) $staff_list[] = $row;

// ── Recent hires ─────────────────────────────────────────────
$recent_hires = [];
$r = $sconn->query("SELECT s.id,s.staff_id,s.full_name,s.position,s.department,s.hire_date,sr.role_name FROM staff s LEFT JOIN staff_roles sr ON s.role_id=sr.id WHERE s.created_at>=DATE_SUB(NOW(),INTERVAL 60 DAY) ORDER BY s.created_at DESC LIMIT 8");
if ($r) while ($row = $r->fetch_assoc()) $recent_hires[] = $row;

// ── Pending leave requests ───────────────────────────────────
$leave_requests = [];
$r = $sconn->query("SELECT lr.*,s.full_name,s.department FROM staff_leave_requests lr JOIN staff s ON lr.staff_id=s.id WHERE lr.status='Pending' ORDER BY lr.created_at ASC LIMIT 15");
if ($r) while ($row = $r->fetch_assoc()) $leave_requests[] = $row;

// ── Open recruitment ─────────────────────────────────────────
$jobs = [];
$r = $sconn->query("SELECT rj.*,(SELECT COUNT(*) FROM recruitment_applications WHERE job_id=rj.id) applicant_count FROM recruitment_jobs rj WHERE rj.status='Open' ORDER BY rj.posted_date DESC LIMIT 10");
if ($r) while ($row = $r->fetch_assoc()) $jobs[] = $row;

// ── Department requests sent to HR ───────────────────────────
$dept_requests = [];
$sconn2 = getStudentsConnection();
if ($sconn2) {
    $r2 = $sconn2->query("SELECT * FROM department_requests WHERE to_department='HR' OR to_department='HR Manager' ORDER BY created_at DESC LIMIT 15");
    if ($r2) while ($row = $r2->fetch_assoc()) $dept_requests[] = $row;
}

// ── POST handlers ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_staff') {
        $fn   = $sconn->real_escape_string(trim($_POST['full_name'] ?? ''));
        $em   = $sconn->real_escape_string(trim($_POST['email'] ?? ''));
        $ph   = $sconn->real_escape_string(trim($_POST['phone'] ?? ''));
        $pos  = $sconn->real_escape_string(trim($_POST['position'] ?? ''));
        $dept = $sconn->real_escape_string(trim($_POST['department'] ?? ''));
        $rid  = intval($_POST['role_id'] ?? 0);
        $hd   = $sconn->real_escape_string($_POST['hire_date'] ?? date('Y-m-d'));
        $sid  = 'STF-'.strtoupper(substr(preg_replace('/\s+/','',$fn),0,3)).'-'.date('y').str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);
        $pw   = password_hash('isnm@2025', PASSWORD_BCRYPT);
        if ($fn && $em) {
            $sconn->query("INSERT INTO staff (staff_id,full_name,email,phone,position,department,role_id,password,status,hire_date,is_first_login,created_at) VALUES ('$sid','$fn','$em','$ph','$pos','$dept',$rid,'$pw','Active','$hd',1,NOW())");
            if ($sconn->affected_rows > 0) {
                $sconn->query("INSERT INTO staff_activity_log (staff_id,activity_type,activity_description,module_accessed,created_at) VALUES ($uid,'Account Created','New staff registered: $fn','HR Management',NOW())");
                $_SESSION['success'] = "Staff member '$fn' added. Default password: isnm@2025";
            } else { $_SESSION['error'] = "Failed: ".$sconn->error; }
        }
        header('Location: hr_dashboard.php'); exit;
    }

    if ($action === 'approve_leave') {
        $lid = intval($_POST['leave_id'] ?? 0);
        $sconn->query("UPDATE staff_leave_requests SET status='Approved',approved_by=$uid,approval_date=NOW() WHERE id=$lid");
        $_SESSION['success'] = "Leave request approved.";
        header('Location: hr_dashboard.php#leaves'); exit;
    }

    if ($action === 'reject_leave') {
        $lid = intval($_POST['leave_id'] ?? 0);
        $remarks = $sconn->real_escape_string($_POST['remarks'] ?? 'Not approved');
        $sconn->query("UPDATE staff_leave_requests SET status='Rejected',approved_by=$uid,approval_date=NOW(),approval_remarks='$remarks' WHERE id=$lid");
        $_SESSION['success'] = "Leave request rejected.";
        header('Location: hr_dashboard.php#leaves'); exit;
    }

    if ($action === 'post_job') {
        $title = $sconn->real_escape_string($_POST['job_title'] ?? '');
        $dept  = $sconn->real_escape_string($_POST['department'] ?? '');
        $type  = $sconn->real_escape_string($_POST['job_type'] ?? 'Full Time');
        $cat   = $sconn->real_escape_string($_POST['category'] ?? 'Academic');
        $desc  = $sconn->real_escape_string($_POST['description'] ?? '');
        $reqs  = $sconn->real_escape_string($_POST['requirements'] ?? '');
        $dl    = $sconn->real_escape_string($_POST['deadline'] ?? '');
        $vac   = intval($_POST['vacancies'] ?? 1);
        $jcode = 'JOB-'.date('Ym').'-'.str_pad(mt_rand(1,999),3,'0',STR_PAD_LEFT);
        if ($title) {
            $sconn->query("INSERT INTO recruitment_jobs (job_code,job_title,department,job_type,job_category,description,requirements,application_deadline,vacancies,status,posted_by,posted_date,created_at) VALUES ('$jcode','$title','$dept','$type','$cat','$desc','$reqs','".($dl?:'NULL')."',$vac,'Open',$uid,NOW(),NOW())");
            $_SESSION['success'] = "Job vacancy posted.";
        }
        header('Location: hr_dashboard.php#recruitment'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>HR Dashboard – ISNM</title>
<link rel="icon" href="images/school-logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root{--primary:#c0392b;--accent:#e74c3c;--sidebar-w:250px}
body{background:#f0f4f8;font-family:'Segoe UI',sans-serif;margin:0}
.sidebar{width:var(--sidebar-w);background:linear-gradient(180deg,#7b1818,var(--primary));position:fixed;height:100vh;overflow-y:auto;z-index:100;color:#fff}
.sidebar .brand{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,.1);text-align:center}
.sidebar .brand img{width:50px;border-radius:50%;border:2px solid rgba(255,255,255,.3)}
.sidebar .brand h6{margin:7px 0 2px;font-size:.82rem}
.sidebar nav a{display:flex;align-items:center;gap:9px;padding:11px 18px;color:rgba(255,255,255,.82);text-decoration:none;font-size:.86rem;transition:.2s}
.sidebar nav a:hover,.sidebar nav a.active{background:rgba(255,255,255,.15);color:#fff;border-left:3px solid #ffa5a5}
.sidebar nav a i{width:16px;text-align:center}
.main{margin-left:var(--sidebar-w);padding:22px;min-height:100vh}
.stat-card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);border-left:4px solid var(--accent);transition:.2s}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.1)}
.stat-card .num{font-size:1.75rem;font-weight:700}
.stat-card .lbl{font-size:.75rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px}
.section-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:22px}
.section-card h5{color:var(--primary);font-weight:600;border-bottom:2px solid #e9ecef;padding-bottom:8px;margin-bottom:14px}
@media(max-width:768px){.sidebar{transform:translateX(-100%);transition:.3s}.sidebar.open{transform:translateX(0)}.main{margin-left:0}}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <div class="brand">
    <img src="images/school-logo.png" alt="ISNM">
    <h6>HR Portal</h6>
    <small><?= htmlspecialchars($uname) ?></small>
  </div>
  <nav>
    <a href="#overview"    class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="#staff">                    <i class="fas fa-users"></i> Staff Records</a>
    <a href="#add-staff">                <i class="fas fa-user-plus"></i> Add Staff</a>
    <a href="#leaves">                   <i class="fas fa-calendar-check"></i> Leave Requests</a>
    <a href="#recruitment">              <i class="fas fa-briefcase"></i> Recruitment</a>
    <a href="#dept-requests">            <i class="fas fa-inbox"></i> Dept. Requests</a>
    <a href="hr_attendance.php">         <i class="fas fa-fingerprint"></i> Attendance</a>
    <a href="hr_payroll.php">            <i class="fas fa-money-bill-wave"></i> Payroll</a>
    <a href="hr_performance.php">        <i class="fas fa-chart-line"></i> Performance</a>
    <a href="hr_training.php">           <i class="fas fa-graduation-cap"></i> Training & CPD</a>
    <a href="hr_reports.php">            <i class="fas fa-file-alt"></i> Reports</a>
    <a href="hr_settings.php">           <i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php">                <i class="fas fa-sign-out-alt"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <button class="btn btn-sm btn-outline-secondary d-md-none me-2" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
      <h4 class="d-inline fw-bold" style="color:var(--primary)">HR Dashboard</h4>
    </div>
    <small class="text-muted"><?= date('l, d M Y') ?></small>
  </div>

  <?php if(!empty($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php unset($_SESSION['success']); endif; ?>
  <?php if(!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php unset($_SESSION['error']); endif; ?>

  <?php if($expiring_contracts > 0): ?>
  <div class="alert alert-warning py-2 mb-3"><i class="fas fa-exclamation-circle me-1"></i><strong><?= $expiring_contracts ?> contract(s)</strong> expiring in the next 30 days.</div>
  <?php endif; ?>

  <!-- STATS -->
  <section id="overview">
    <div class="row g-3 mb-4">
      <?php $cards=[
        ['Total Staff',          $total_staff,         'users',            '#3b82f6'],
        ['On Leave Today',       $on_leave_today,      'calendar-alt',     '#f59e0b'],
        ['Pending Leave',        $pending_leaves,      'clock',            '#ef4444'],
        ['Expiring Contracts',   $expiring_contracts,  'hourglass-end',    '#8b5cf6'],
        ['Open Jobs',            $open_jobs,           'briefcase',        '#10b981'],
        ['Job Applications',     $pending_apps,        'file-alt',         '#06b6d4'],
        ['Expiring Compliance',  $expiring_compliance, 'shield-alt',       '#f97316'],
        ['Open Disciplinary',    $disciplinary_open,   'gavel',            '#ef4444'],
      ];
      foreach($cards as $c): ?>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:<?= $c[3] ?>">
          <div class="d-flex justify-content-between">
            <div>
              <div class="num" style="color:<?= $c[3] ?>"><?= $c[1] ?></div>
              <div class="lbl"><?= $c[0] ?></div>
            </div>
            <i class="fas fa-<?= $c[2] ?> fa-lg mt-2" style="color:<?= $c[3] ?>;opacity:.5"></i>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Department Breakdown -->
    <?php if(!empty($dept_stats)): ?>
    <div class="section-card mb-4">
      <h5><i class="fas fa-building me-2"></i>Staff by Department</h5>
      <div class="row g-2">
        <?php foreach($dept_stats as $d): ?>
        <div class="col-6 col-md-3">
          <div class="border rounded p-2 text-center">
            <div class="fw-bold fs-5"><?= $d['cnt'] ?></div>
            <small class="text-muted"><?= htmlspecialchars($d['department'] ?: 'Unassigned') ?></small>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </section>

  <!-- STAFF RECORDS -->
  <section id="staff" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-users me-2"></i>Staff Records</h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal"><i class="fas fa-plus me-1"></i>Add Staff</button>
    </div>
    <form method="GET" class="row g-2 mb-3">
      <div class="col-md-5"><input type="text" name="q" class="form-control form-control-sm" placeholder="Search name, email, staff ID…" value="<?= htmlspecialchars($search) ?>"></div>
      <div class="col-md-3">
        <select name="dept" class="form-select form-select-sm">
          <option value="">All Departments</option>
          <?php foreach(array_column($dept_stats,'department') as $dp): ?>
          <option <?= $filter_dept===$dp?'selected':'' ?> value="<?= htmlspecialchars($dp) ?>"><?= htmlspecialchars($dp) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <?php foreach(['Active','Inactive','On Leave','Suspended'] as $st): ?>
          <option <?= $filter_status===$st?'selected':'' ?> value="<?= $st ?>"><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button></div>
    </form>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle">
        <thead class="table-light"><tr><th>Staff ID</th><th>Full Name</th><th>Role</th><th>Department</th><th>Phone</th><th>Email</th><th>Status</th><th>Hire Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($staff_list)): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">No staff found</td></tr>
        <?php else: foreach($staff_list as $s):
          $bc = $s['status']==='Active'?'bg-success':($s['status']==='On Leave'?'bg-warning text-dark':'bg-danger');
        ?>
          <tr>
            <td><code><?= htmlspecialchars($s['staff_id']) ?></code></td>
            <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
            <td><?= htmlspecialchars($s['role_name'] ?? $s['position']) ?></td>
            <td><?= htmlspecialchars($s['department'] ?? '—') ?></td>
            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
            <td><small><?= htmlspecialchars($s['email']) ?></small></td>
            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($s['status']) ?></span></td>
            <td><?= $s['hire_date'] ? date('d M Y', strtotime($s['hire_date'])) : '—' ?></td>
            <td>
              <a href="hr_staff_records.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fas fa-eye"></i></a>
              <button class="btn btn-sm btn-outline-warning py-0 px-2" onclick="editStaff(<?= $s['id'] ?>)"><i class="fas fa-edit"></i></button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- LEAVE REQUESTS -->
  <section id="leaves" class="section-card">
    <h5><i class="fas fa-calendar-check me-2"></i>Pending Leave Requests (<?= count($leave_requests) ?>)</h5>
    <?php if(empty($leave_requests)): ?>
    <p class="text-muted small"><i class="fas fa-check-circle text-success me-1"></i>No pending leave requests.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle">
        <thead class="table-light"><tr><th>Staff</th><th>Dept</th><th>Leave Type</th><th>From</th><th>To</th><th>Days</th><th>Reason</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($leave_requests as $lr): ?>
        <tr>
          <td><strong><?= htmlspecialchars($lr['full_name']) ?></strong></td>
          <td><?= htmlspecialchars($lr['department'] ?? '—') ?></td>
          <td><span class="badge bg-info text-dark"><?= htmlspecialchars($lr['leave_type']) ?></span></td>
          <td><?= date('d M Y',strtotime($lr['start_date'])) ?></td>
          <td><?= date('d M Y',strtotime($lr['end_date'])) ?></td>
          <td><?= $lr['total_days'] ?></td>
          <td><small><?= htmlspecialchars(substr($lr['reason']??'',0,50)) ?>…</small></td>
          <td>
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="approve_leave">
              <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
              <button class="btn btn-sm btn-success py-0 px-2"><i class="fas fa-check"></i></button>
            </form>
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="reject_leave">
              <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
              <input type="hidden" name="remarks" value="Not approved by HR Manager">
              <button class="btn btn-sm btn-danger py-0 px-2"><i class="fas fa-times"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- RECRUITMENT -->
  <section id="recruitment" class="section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fas fa-briefcase me-2"></i>Open Vacancies</h5>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#jobModal"><i class="fas fa-plus me-1"></i>Post Job</button>
    </div>
    <?php if(empty($jobs)): ?>
    <p class="text-muted small">No open vacancies.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead class="table-light"><tr><th>Job Code</th><th>Title</th><th>Department</th><th>Type</th><th>Deadline</th><th>Applicants</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($jobs as $j): ?>
        <tr>
          <td><code><?= htmlspecialchars($j['job_code']) ?></code></td>
          <td><?= htmlspecialchars($j['job_title']) ?></td>
          <td><?= htmlspecialchars($j['department'] ?? '—') ?></td>
          <td><span class="badge bg-secondary"><?= htmlspecialchars($j['job_type']) ?></span></td>
          <td><?= $j['application_deadline'] ? date('d M Y',strtotime($j['application_deadline'])) : '—' ?></td>
          <td><span class="badge bg-info text-dark"><?= $j['applicant_count'] ?></span></td>
          <td><span class="badge bg-success"><?= htmlspecialchars($j['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- DEPARTMENT REQUESTS -->
  <section id="dept-requests" class="section-card">
    <h5><i class="fas fa-inbox me-2"></i>Department Requests to HR (<?= count($dept_requests) ?>)</h5>
    <?php if(empty($dept_requests)): ?>
    <p class="text-muted small">No incoming requests from departments.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead class="table-light"><tr><th>Ref</th><th>From</th><th>Item</th><th>Qty</th><th>Urgency</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach($dept_requests as $req): ?>
        <tr>
          <td><code><?= htmlspecialchars($req['request_number']) ?></code></td>
          <td><?= htmlspecialchars($req['from_department']) ?></td>
          <td><?= htmlspecialchars($req['item_name']) ?></td>
          <td><?= $req['quantity'] ?> <?= htmlspecialchars($req['unit'] ?? '') ?></td>
          <td><span class="badge <?= $req['urgency']==='Emergency'?'bg-danger':($req['urgency']==='Urgent'?'bg-warning text-dark':'bg-secondary') ?>"><?= $req['urgency'] ?></span></td>
          <td><span class="badge <?= $req['status']==='Pending'?'bg-warning text-dark':($req['status']==='Approved'?'bg-success':'bg-danger') ?>"><?= $req['status'] ?></span></td>
          <td><?= date('d M Y',strtotime($req['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- RECENT HIRES -->
  <section class="section-card">
    <h5><i class="fas fa-user-tie me-2"></i>Recent Hires (Last 60 days)</h5>
    <?php if(empty($recent_hires)): ?>
    <p class="text-muted small">No recent hires.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead class="table-light"><tr><th>Staff ID</th><th>Name</th><th>Role</th><th>Department</th><th>Hire Date</th></tr></thead>
        <tbody>
        <?php foreach($recent_hires as $h): ?>
        <tr>
          <td><code><?= htmlspecialchars($h['staff_id']) ?></code></td>
          <td><strong><?= htmlspecialchars($h['full_name']) ?></strong></td>
          <td><?= htmlspecialchars($h['role_name'] ?? $h['position']) ?></td>
          <td><?= htmlspecialchars($h['department'] ?? '—') ?></td>
          <td><?= $h['hire_date'] ? date('d M Y',strtotime($h['hire_date'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
</div>

<!-- ADD STAFF MODAL -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add_staff">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Register New Staff</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label fw-semibold">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Email *</label><input type="email" name="email" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Phone</label><input type="text" name="phone" class="form-control"></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Position *</label><input type="text" name="position" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Department</label><input type="text" name="department" class="form-control"></div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Role</label>
            <select name="role_id" class="form-select">
              <option value="0">-- Select Role --</option>
              <?php
              $r_roles = $sconn->query("SELECT id,role_name FROM staff_roles ORDER BY role_name");
              if($r_roles) while($rr=$r_roles->fetch_assoc()): ?>
              <option value="<?= $rr['id'] ?>"><?= htmlspecialchars($rr['role_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label fw-semibold">Hire Date</label><input type="date" name="hire_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
          <div class="col-12"><div class="alert alert-info py-2 mb-0 small"><i class="fas fa-info-circle me-1"></i>Default password will be <strong>isnm@2025</strong>. Staff must change it on first login.</div></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-save me-1"></i>Register Staff</button>
      </div>
    </form>
  </div>
</div>

<!-- POST JOB MODAL -->
<div class="modal fade" id="jobModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="post_job">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-briefcase me-2"></i>Post Job Vacancy</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-8"><label class="form-label fw-semibold">Job Title *</label><input type="text" name="job_title" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Vacancies</label><input type="number" name="vacancies" class="form-control" value="1" min="1"></div>
          <div class="col-md-4"><label class="form-label fw-semibold">Department</label><input type="text" name="department" class="form-control"></div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Category</label>
            <select name="category" class="form-select"><option>Academic</option><option>Administrative</option><option>Support</option><option>Management</option></select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Job Type</label>
            <select name="job_type" class="form-select"><option>Full Time</option><option>Part Time</option><option>Contract</option><option>Temporary</option></select>
          </div>
          <div class="col-12"><label class="form-label fw-semibold">Description *</label><textarea name="description" class="form-control" rows="3" required></textarea></div>
          <div class="col-12"><label class="form-label fw-semibold">Requirements</label><textarea name="requirements" class="form-control" rows="2"></textarea></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Application Deadline</label><input type="date" name="deadline" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Post Vacancy</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editStaff(id){ window.location.href='hr_staff_records.php?id='+id+'&edit=1'; }
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
</body>
</html>
