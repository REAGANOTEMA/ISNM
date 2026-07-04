<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/news_management_widget.php';

$ctx          = bootstrapStaffDashboard(['hr manager']);
$auth_service = $ctx['auth'];
$user         = $ctx['user'];
$user_role    = $_SESSION['role'] ?? '';
$staff_conn   = $ctx['staff'];
$students_conn = $ctx['students'];
$website_conn  = $ctx['website'];
$user_id      = (int)($_SESSION['user_id'] ?? 0);
$user_name    = $_SESSION['full_name'] ?? 'HR Manager';

// ── Page routing ──
$pageToSection = [
    'home'           => 'overview',
    'overview'       => 'overview',
    'staff-directory'=> 'staff',
    'attendance'     => 'staff',
    'leave'          => 'leave',
    'performance'    => 'staff',
    'training'       => 'staff',
    'recruitment'    => 'staff',
    'contracts'      => 'staff',
    'disciplinary'   => 'staff',
    'licenses'       => 'staff',
    'payroll'        => 'staff',
    'onboarding'     => 'staff',
];
$page  = $_GET['page'] ?? 'home';
$section = $pageToSection[$page] ?? 'overview';

$stats = getDashboardStats($staff_conn, $user_id, 'HR Manager');

// ── Primary counts ──
$active_staff = 0; $on_leave = 0; $pending_leave = 0; $total_contracts = 0;
$open_cases = 0; $expiring_licenses = 0; $active_trainings = 0; $open_vacancies = 0;
if ($staff_conn) {
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff WHERE status='Active'"); if ($r) $active_staff = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff WHERE status='On Leave'"); if ($r) $on_leave = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_leave_requests WHERE status='Pending'"); if ($r) $pending_leave = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM employment_contracts WHERE status='active'"); if ($r) $total_contracts = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_disciplinary WHERE status='Open'"); if ($r) $open_cases = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_licenses WHERE status='valid' AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"); if ($r) $expiring_licenses = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_training WHERE status='In Progress'"); if ($r) $active_trainings = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_recruitment WHERE status='Open'"); if ($r) $open_vacancies = (int)$r->fetch_assoc()['c'];
}

// ── Staff list ──
$staff_list = [];
if ($staff_conn) {
    $sl = $staff_conn->query("SELECT s.id,s.staff_id,s.full_name,s.email,s.position,s.department,s.status,s.hire_date,s.employment_type,sr.role_name FROM staff s LEFT JOIN staff_roles sr ON s.role_id=sr.id ORDER BY s.full_name LIMIT 30");
    if ($sl) $staff_list = $sl->fetch_all(MYSQLI_ASSOC);
}

// ── Leave requests ──
$leave_requests = [];
if ($staff_conn) {
    $lr = $staff_conn->query("SELECT slr.*,s.full_name FROM staff_leave_requests slr JOIN staff s ON slr.staff_id=s.id ORDER BY slr.created_at DESC LIMIT 10");
    if ($lr) $leave_requests = $lr->fetch_all(MYSQLI_ASSOC);
}

// ── Roles ──
$roles = [];
if ($staff_conn) {
    $rr = $staff_conn->query("SELECT id, role_name FROM staff_roles ORDER BY role_name");
    if ($rr) $roles = $rr->fetch_all(MYSQLI_ASSOC);
}

// ── Departments ──
$departments = [];
if ($staff_conn) {
    $dd = $staff_conn->query("SELECT id, department_name FROM staff_departments ORDER BY department_name");
    if ($dd) $departments = $dd->fetch_all(MYSQLI_ASSOC);
}

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_staff') {
        $fn = trim($_POST['full_name'] ?? ''); $em = trim($_POST['email'] ?? '');
        $pos = trim($_POST['position'] ?? ''); $dept = trim($_POST['department'] ?? '');
        $rid = (int)($_POST['role_id'] ?? 0); $ph = trim($_POST['phone'] ?? '');
        $empType = trim($_POST['employment_type'] ?? 'full-time');
        $empCat = trim($_POST['employment_category'] ?? 'administrative');
        if ($fn && $em && $staff_conn) {
            $sid = 'STAFF'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $hash = password_hash('isnm2026', PASSWORD_BCRYPT);
            $stmt = $staff_conn->prepare("INSERT INTO staff (staff_id,full_name,email,password,phone,position,department,role_id,employment_type,employment_category,status,hire_date,login_attempts,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,'Active',CURDATE(),0,NOW())");
            if ($stmt) { $stmt->bind_param('sssssssiss',$sid,$fn,$em,$hash,$ph,$pos,$dept,$rid,$empType,$empCat); $stmt->execute(); $_SESSION['success'] = "Staff $fn added."; }
        }
        header('Location: hr-manager.php'); exit;
    }
    if ($action === 'edit_staff') {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $fn = trim($_POST['full_name'] ?? ''); $em = trim($_POST['email'] ?? '');
        $pos = trim($_POST['position'] ?? ''); $dept = trim($_POST['department'] ?? '');
        $rid = (int)($_POST['role_id'] ?? 0); $ph = trim($_POST['phone'] ?? '');
        $st = trim($_POST['status'] ?? 'Active');
        $empType = trim($_POST['employment_type'] ?? 'full-time');
        $empCat = trim($_POST['employment_category'] ?? 'administrative');
        $resp = ['success' => false, 'error' => 'Missing fields'];
        if ($id && $fn && $em && $staff_conn) {
            $stmt = $staff_conn->prepare("UPDATE staff SET full_name=?, email=?, phone=?, position=?, department=?, role_id=?, employment_type=?, employment_category=?, status=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('sssssisssi', $fn, $em, $ph, $pos, $dept, $rid, $empType, $empCat, $st, $id);
                $resp = ['success' => $stmt->execute(), 'error' => $stmt->error];
                $stmt->close();
            }
        }
        echo json_encode($resp); exit;
    }
    if ($action === 'delete_staff') {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $resp = ['success' => false, 'error' => 'Invalid ID'];
        if ($id && $staff_conn) {
            $stmt = $staff_conn->prepare("DELETE FROM staff WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $resp = ['success' => $stmt->execute(), 'error' => $stmt->error];
                $stmt->close();
            }
        }
        echo json_encode($resp); exit;
    }
    if (in_array($action, ['approve_leave','reject_leave'])) {
        $lid = (int)($_POST['leave_id'] ?? 0);
        $status = ($action === 'approve_leave') ? 'Approved' : 'Rejected';
        if ($staff_conn && $lid) {
            $stmt = $staff_conn->prepare("UPDATE staff_leave_requests SET status=?, approved_by=?, approval_date=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('sii',$status,$user_id,$lid); $stmt->execute(); }
            $_SESSION['success'] = "Leave $status.";
        }
        header('Location: hr-manager.php#leave'); exit;
    }
}
$pageTitle = 'HR Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root { --hr-primary: #dc2626; --hr-dark: #991b1b; }

.hr-content{margin-left:270px;padding:24px;min-height:100vh}
.hr-section { display: none; }
.hr-section.active { display: block; }
.kpi-card { background: #fff; border-radius: 12px; padding: 18px; border: 1px solid #e5e7eb; border-left: 4px solid var(--hr-primary); transition: all .2s; }
.kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.kpi-card .num { font-size: 1.5rem; font-weight: 700; color: var(--hr-primary); margin: 0; }
.kpi-card .lbl { font-size: 12px; color: #6b7280; margin: 0; }
.form-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; }
.form-card .hd { background: #f8fafc; padding: 12px 18px; border-bottom: 1px solid #e5e7eb; border-radius: 12px 12px 0 0; font-weight: 600; color: var(--hr-dark); font-size: 14px; }
.form-card .bd { padding: 18px; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { background: #f8fafc; color: #475569; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
.data-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; }
.badge-hr { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
.bg-hr-success { background: #dcfce7; color: #166534; }
.bg-hr-warning { background: #fef3c7; color: #92400e; }
.bg-hr-danger { background: #fee2e2; color: #991b1b; }
.bg-hr-info { background: #dbeafe; color: #1e40af; }
@media(max-width:768px){.hr-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>



<div class="hr-content">
    <?php if ($msg = $_SESSION['success'] ?? ''): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($msg) ?></div><?php unset($_SESSION['success']); endif; ?>
    <?php if ($err = $_SESSION['error'] ?? ''): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($err) ?></div><?php unset($_SESSION['error']); endif; ?>

    <!-- ═══════════════ SECTION: OVERVIEW ═══════════════ -->
    <div class="hr-section<?= $section==='overview'?' active':'' ?>" id="section-overview">
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6"><div class="kpi-card"><p class="num"><?= $active_staff ?></p><p class="lbl">Active Staff</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#f59e0b"><p class="num" style="color:#f59e0b"><?= $on_leave ?></p><p class="lbl">On Leave</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#3b82f6"><p class="num" style="color:#3b82f6"><?= $pending_leave ?></p><p class="lbl">Pending Leave</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#8b5cf6"><p class="num" style="color:#8b5cf6"><?= $open_cases ?></p><p class="lbl">Disciplinary Cases</p></div></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#059669"><p class="num" style="color:#059669"><?= $total_contracts ?></p><p class="lbl">Active Contracts</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#dc2626"><p class="num" style="color:#dc2626"><?= $expiring_licenses ?></p><p class="lbl">Licenses Expiring</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#0891b2"><p class="num" style="color:#0891b2"><?= $active_trainings ?></p><p class="lbl">Active Trainings</p></div></div>
            <div class="col-md-3 col-6"><div class="kpi-card" style="border-left-color:#7c3aed"><p class="num" style="color:#7c3aed"><?= $open_vacancies ?></p><p class="lbl">Open Positions</p></div></div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-bolt me-2"></i>Quick Actions</div>
                    <div class="bd">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal"><i class="fas fa-user-plus me-1"></i>Add Staff</button>
                            <a href="staff-directory.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-address-book me-1"></i>Directory</a>
                            <a href="leave-management.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-calendar-alt me-1"></i>Leave</a>
                            <a href="staff-attendance.php" class="btn btn-sm btn-outline-success"><i class="fas fa-clock me-1"></i>Attendance</a>
                            <a href="performance-appraisal.php" class="btn btn-sm btn-outline-info"><i class="fas fa-chart-line me-1"></i>Appraisals</a>
                            <a href="training-cpd.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-graduation-cap me-1"></i>Training</a>
                            <a href="recruitment.php" class="btn btn-sm btn-outline-dark"><i class="fas fa-briefcase me-1"></i>Recruit</a>
                            <a href="contracts-management.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-contract me-1"></i>Contracts</a>
                            <a href="professional-licenses.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-certificate me-1"></i>Licenses</a>
                            <a href="duty-rosters.php" class="btn btn-sm btn-outline-info"><i class="fas fa-calendar-week me-1"></i>Rosters</a>
                            <a href="onboarding.php" class="btn btn-sm btn-outline-success"><i class="fas fa-user-check me-1"></i>Onboarding</a>
                            <a href="payroll.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-money-check-alt me-1"></i>Payroll</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-history me-2"></i>Staff at a Glance</div>
                    <div class="bd p-0">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead><tr><th>Staff ID</th><th>Name</th><th>Role</th><th>Department</th><th>Status</th></tr></thead>
                                <tbody>
<?php foreach ($staff_list as $s): $bc = $s['status']==='Active'?'bg-hr-success':($s['status']==='On Leave'?'bg-hr-warning':'bg-hr-danger'); ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($s['staff_id']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                                    <td class="small"><?= htmlspecialchars($s['role_name'] ?? $s['position']) ?></td>
                                    <td class="small"><?= htmlspecialchars($s['department'] ?? '-') ?></td>
                                    <td><span class="badge-hr <?= $bc ?>"><?= $s['status'] ?></span></td>
                                </tr>
<?php endforeach; if (empty($staff_list)): ?><tr><td colspan="5" class="text-center text-muted py-3">No staff records.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: STAFF RECORDS ═══════════════ -->
    <div class="hr-section" id="section-staff">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-id-card me-2"></i>Staff Records</div>
                    <div class="bd p-0">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead><tr><th>ID</th><th>Name</th><th>Position</th><th>Type</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
<?php foreach ($staff_list as $s): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($s['staff_id']) ?></code></td>
                                    <td><a href="staff_profile_management.php?id=<?= $s['id'] ?>"><strong><?= htmlspecialchars($s['full_name']) ?></strong></a></td>
                                    <td class="small"><?= htmlspecialchars($s['position']) ?></td>
                                    <td><span class="badge-hr bg-hr-info"><?= htmlspecialchars($s['employment_type'] ?? 'N/A') ?></span></td>
                                    <td class="small"><?= htmlspecialchars($s['employment_category'] ?? '-') ?></td>
                                    <td><span class="badge-hr <?= $s['status']==='Active'?'bg-hr-success':'bg-hr-warning' ?>"><?= $s['status'] ?></span></td>
                                    <td>
                                        <a href="staff_profile_management.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                        <button class="btn btn-sm btn-outline-warning" title="Edit" onclick="editStaff(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['phone'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($s['position'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($s['department'] ?? '', ENT_QUOTES) ?>', <?= (int)($s['role_id'] ?? 0) ?>, '<?= htmlspecialchars($s['employment_type'] ?? 'full-time', ENT_QUOTES) ?>', '<?= htmlspecialchars($s['employment_category'] ?? 'administrative', ENT_QUOTES) ?>', '<?= htmlspecialchars($s['status'] ?? 'Active', ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteStaff(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
<?php endforeach; if (empty($staff_list)): ?><tr><td colspan="7" class="text-center text-muted py-3">No records.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-card mb-3">
                    <div class="hd"><i class="fas fa-user-tag me-2"></i>Roles & Departments</div>
                    <div class="bd">
                        <p><strong>Total Roles:</strong> <?= count($roles) ?></p>
                        <p><strong>Total Departments:</strong> <?= count($departments) ?></p>
                        <a href="staff-directory.php" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-address-book me-1"></i>Manage Roles & Departments</a>
                    </div>
                </div>
                <div class="form-card mb-3">
                    <div class="hd"><i class="fas fa-file-contract me-2"></i>Contracts</div>
                    <div class="bd">
                        <p><strong>Active Contracts:</strong> <?= $total_contracts ?></p>
                        <a href="contracts-management.php" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-file-signature me-1"></i>Manage Contracts</a>
                    </div>
                </div>
                <div class="form-card">
                    <div class="hd"><i class="fas fa-upload me-2"></i>Documents</div>
                    <div class="bd">
                        <a href="staff_profile_management.php" class="btn btn-sm btn-outline-info w-100"><i class="fas fa-file-upload me-1"></i>Upload Documents</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: ATTENDANCE ═══════════════ -->
    <div class="hr-section" id="section-attendance">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-clock me-2"></i>Today's Attendance</div>
                    <div class="bd">
<?php
$todayPresent = 0; $todayLate = 0; $todayAbsent = 0;
if ($staff_conn) {
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_attendance WHERE attendance_date=CURDATE() AND status='Present'"); if ($r) $todayPresent = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_attendance WHERE attendance_date=CURDATE() AND status='Late'"); if ($r) $todayLate = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_attendance WHERE attendance_date=CURDATE() AND status='Absent'"); if ($r) $todayAbsent = (int)$r->fetch_assoc()['c'];
}
?>
                        <div class="row g-2 text-center">
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-success"><?= $todayPresent ?></div><small>Present</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-warning"><?= $todayLate ?></div><small>Late</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-danger"><?= $todayAbsent ?></div><small>Absent</small></div></div>
                        </div>
                        <a href="staff-attendance.php" class="btn btn-sm btn-outline-primary mt-3 w-100"><i class="fas fa-calendar-check me-1"></i>Full Attendance Dashboard</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-calendar-week me-2"></i>Shift Scheduling</div>
                    <div class="bd">
                        <a href="duty-rosters.php" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-calendar-alt me-1"></i>Duty Rosters & Shifts</a>
                        <a href="clinical-placement.php" class="btn btn-sm btn-outline-info w-100"><i class="fas fa-clinic-medical me-1"></i>Clinical Placements</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: LEAVE ═══════════════ -->
    <div class="hr-section" id="section-leave">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-calendar-alt me-2"></i>Leave Requests</div>
                    <div class="bd p-0">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead><tr><th>Staff</th><th>Type</th><th>Start</th><th>End</th><th>Days</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
<?php foreach ($leave_requests as $lr): $bc = $lr['status']==='Approved'?'bg-hr-success':($lr['status']==='Rejected'?'bg-hr-danger':'bg-hr-warning'); ?>
                                <tr>
                                    <td><?= htmlspecialchars($lr['full_name']) ?></td>
                                    <td><?= htmlspecialchars($lr['leave_type']) ?></td>
                                    <td class="small"><?= $lr['start_date'] ?></td>
                                    <td class="small"><?= $lr['end_date'] ?></td>
                                    <td><?= $lr['total_days'] ?></td>
                                    <td><span class="badge-hr <?= $bc ?>"><?= $lr['status'] ?></span></td>
                                    <td>
<?php if ($lr['status']==='Pending'): ?>
                                        <form method="POST" class="d-inline"><input type="hidden" name="action" value="approve_leave"><input type="hidden" name="leave_id" value="<?= $lr['id'] ?>"><button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button></form>
                                        <form method="POST" class="d-inline"><input type="hidden" name="action" value="reject_leave"><input type="hidden" name="leave_id" value="<?= $lr['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button></form>
<?php else: ?><span class="text-muted small">Processed</span><?php endif; ?>
                                    </td>
                                </tr>
<?php endforeach; if (empty($leave_requests)): ?><tr><td colspan="7" class="text-center text-muted py-3">No leave requests.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-card mb-3">
                    <div class="hd"><i class="fas fa-cog me-2"></i>Leave Management</div>
                    <div class="bd">
                        <a href="leave-management.php" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-calendar-alt me-1"></i>Full Leave Dashboard</a>
                        <a href="leave-management.php#calendar" class="btn btn-sm btn-outline-info w-100 mb-2"><i class="fas fa-calendar me-1"></i>Leave Calendar</a>
                        <a href="leave-management.php#balances" class="btn btn-sm btn-outline-success w-100"><i class="fas fa-balance-scale me-1"></i>Leave Balances</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: PERFORMANCE ═══════════════ -->
    <div class="hr-section" id="section-performance">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-chart-line me-2"></i>Appraisals</div>
                    <div class="bd">
<?php $pendingApps = 0; $completedApps = 0;
if ($staff_conn) {
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_appraisals WHERE status='Pending'"); if ($r) $pendingApps = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_appraisals WHERE status='Completed'"); if ($r) $completedApps = (int)$r->fetch_assoc()['c'];
} ?>
                        <div class="row g-2 text-center mb-3">
                            <div class="col-6"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-warning"><?= $pendingApps ?></div><small>Pending</small></div></div>
                            <div class="col-6"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-success"><?= $completedApps ?></div><small>Completed</small></div></div>
                        </div>
                        <a href="performance-appraisal.php" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-clipboard-check me-1"></i>Manage Appraisals</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card mb-3">
                    <div class="hd"><i class="fas fa-chart-bar me-2"></i>Evaluation & KPIs</div>
                    <div class="bd">
                        <a href="performance-appraisal.php#evaluations" class="btn btn-sm btn-outline-info w-100 mb-2"><i class="fas fa-user-check me-1"></i>Lecturer Evaluations</a>
                        <a href="clinical-placement.php#supervision" class="btn btn-sm btn-outline-success w-100 mb-2"><i class="fas fa-clinic-medical me-1"></i>Clinical Supervision</a>
                        <a href="quality-assurance.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-check-circle me-1"></i>Quality Assurance</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: TRAINING & CPD ═══════════════ -->
    <div class="hr-section" id="section-training">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-graduation-cap me-2"></i>Training & CPD Overview</div>
                    <div class="bd">
<?php $totalTraining = 0; $completedTraining = 0;
if ($staff_conn) {
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_training"); if ($r) $totalTraining = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_training WHERE status='Completed'"); if ($r) $completedTraining = (int)$r->fetch_assoc()['c'];
} ?>
                        <div class="row g-2 text-center mb-3">
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-primary"><?= $totalTraining ?></div><small>Total</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-success"><?= $completedTraining ?></div><small>Completed</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-warning"><?= $active_trainings ?></div><small>In Progress</small></div></div>
                        </div>
                        <a href="training-cpd.php" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-graduation-cap me-1"></i>Full Training Dashboard</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card mb-3">
                    <div class="hd"><i class="fas fa-certificate me-2"></i>Mandatory Training & Certificates</div>
                    <div class="bd">
                        <p class="small text-muted">Track mandatory trainings: infection control, ethics, fire safety, BLS/ACLS</p>
                        <a href="training-cpd.php#certificates" class="btn btn-sm btn-outline-warning w-100 mb-2"><i class="fas fa-certificate me-1"></i>Certificate Management</a>
                        <a href="training-cpd.php#mandatory" class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-exclamation-triangle me-1"></i>Mandatory Training</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: RECRUITMENT ═══════════════ -->
    <div class="hr-section" id="section-recruitment">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-briefcase me-2"></i>Recruitment Pipeline</div>
                    <div class="bd">
<?php $applicants = 0; $shortlisted = 0;
if ($staff_conn) {
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_recruitment WHERE status='Open'"); if ($r) $openVacancies = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM job_applications"); if ($r) $applicants = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM job_applications WHERE status='Shortlisted'"); if ($r) $shortlisted = (int)$r->fetch_assoc()['c'];
} ?>
                        <div class="row g-2 text-center mb-3">
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-primary"><?= $open_vacancies ?></div><small>Open Positions</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-info"><?= $applicants ?></div><small>Applicants</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-success"><?= $shortlisted ?></div><small>Shortlisted</small></div></div>
                        </div>
                        <a href="recruitment.php" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-briefcase me-1"></i>Manage Recruitment</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card mb-3">
                    <div class="hd"><i class="fas fa-user-check me-2"></i>Onboarding</div>
                    <div class="bd">
                        <a href="onboarding.php" class="btn btn-sm btn-outline-success w-100 mb-2"><i class="fas fa-clipboard-list me-1"></i>Onboarding Checklist</a>
                        <a href="resignations.php" class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-1"></i>Resignations & Exit</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: PAYROLL (HR VIEW) ═══════════════ -->
    <div class="hr-section" id="section-payroll">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-wallet me-2"></i>Salary Structure</div>
                    <div class="bd">
                        <p class="small text-muted">HR view of salary structures and staff compensation.</p>
                        <a href="../payroll.php?section=employees" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-users me-1"></i>Payroll Employee Profiles</a>
                        <a href="../payroll.php?section=allowances" class="btn btn-sm btn-outline-success w-100 mb-2"><i class="fas fa-plus-circle me-1"></i>Allowances</a>
                        <a href="../payroll.php?section=deductions" class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-minus-circle me-1"></i>Deductions</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-file-invoice me-2"></i>Payslips</div>
                    <div class="bd">
                        <a href="../payroll.php?section=payslips" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-file-invoice me-1"></i>View Payslips</a>
                        <a href="../payroll.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-calculator me-1"></i>Payroll Processing</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-history me-2"></i>Payroll History</div>
                    <div class="bd">
                        <a href="../payroll.php?section=processing" class="btn btn-sm btn-outline-info w-100"><i class="fas fa-cogs me-1"></i>Payroll Runs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: DISCIPLINARY ═══════════════ -->
    <div class="hr-section" id="section-disciplinary">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-gavel me-2"></i>Disciplinary Cases</div>
                    <div class="bd">
<?php $underInvestigation = 0; $resolvedCases = 0;
if ($staff_conn) {
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_disciplinary WHERE status='Under Investigation'"); if ($r) $underInvestigation = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_disciplinary WHERE status='Resolved'"); if ($r) $resolvedCases = (int)$r->fetch_assoc()['c'];
} ?>
                        <div class="row g-2 text-center mb-3">
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-danger"><?= $open_cases ?></div><small>Open</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-info"><?= $underInvestigation ?></div><small>Investigating</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-success"><?= $resolvedCases ?></div><small>Resolved</small></div></div>
                        </div>
                        <a href="staff-disciplinary.php" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-balance-scale me-1"></i>Manage Disciplinary Cases</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-file-alt me-2"></i>Investigation & Sanctions</div>
                    <div class="bd">
                        <p class="small text-muted">Track incidents, investigations, and sanctions including warnings, suspensions, and terminations.</p>
                        <a href="staff-disciplinary.php#incidents" class="btn btn-sm btn-outline-warning w-100"><i class="fas fa-exclamation-triangle me-1"></i>Incident Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: LICENSING & COMPLIANCE ═══════════════ -->
    <div class="hr-section" id="section-licensing">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-certificate me-2"></i>Professional Licenses</div>
                    <div class="bd">
<?php $validLicenses = 0; $expiredLicenses = 0; $totalLicenses = 0;
if ($staff_conn) {
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_licenses WHERE status='valid'"); if ($r) $validLicenses = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_licenses WHERE status='expired'"); if ($r) $expiredLicenses = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff_licenses"); if ($r) $totalLicenses = (int)$r->fetch_assoc()['c'];
} ?>
                        <div class="row g-2 text-center mb-3">
                            <div class="col-3"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-success"><?= $validLicenses ?></div><small>Valid</small></div></div>
                            <div class="col-3"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-danger"><?= $expiredLicenses ?></div><small>Expired</small></div></div>
                            <div class="col-3"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-warning"><?= $expiring_licenses ?></div><small>Expiring Soon</small></div></div>
                            <div class="col-3"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-primary"><?= $totalLicenses ?></div><small>Total</small></div></div>
                        </div>

<?php if ($expiring_licenses > 0): ?>
                        <div class="alert alert-warning py-2 small"><i class="fas fa-exclamation-triangle me-1"></i><?= $expiring_licenses ?> license(s) expiring within 30 days. <a href="professional-licenses.php" class="alert-link">View</a></div>
<?php endif; ?>
<?php if ($expiredLicenses > 0): ?>
                        <div class="alert alert-danger py-2 small"><i class="fas fa-times-circle me-1"></i><?= $expiredLicenses ?> expired license(s) — clinical assignments restricted. <a href="professional-licenses.php" class="alert-link">Review</a></div>
<?php endif; ?>
                        <a href="professional-licenses.php" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-certificate me-1"></i>Manage Licenses</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card mb-3">
                    <div class="hd"><i class="fas fa-shield-alt me-2"></i>Compliance Alerts</div>
                    <div class="bd">
                        <div class="list-group">
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                License Expiry Alerts <span class="badge bg-warning rounded-pill"><?= $expiring_licenses ?></span>
                            </div>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                Expired Licenses (Blocked from Clinical) <span class="badge bg-danger rounded-pill"><?= $expiredLicenses ?></span>
                            </div>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                CPD Certification Expiry <span class="badge bg-info rounded-pill">--</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: DEPLOYMENT & ROTATION ═══════════════ -->
    <div class="hr-section" id="section-deployment">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-clinic-medical me-2"></i>Clinical Rotation Scheduling</div>
                    <div class="bd">
                        <p class="small text-muted">Assign clinical instructors and teaching staff to wards, hospitals, skills labs, and class teaching.</p>
                        <a href="clinical-placement.php" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-hospital me-1"></i>Clinical Placements</a>
                        <a href="duty-rosters.php" class="btn btn-sm btn-outline-info w-100 mb-2"><i class="fas fa-calendar-week me-1"></i>Duty Rosters</a>
                        <a href="skills-lab.php" class="btn btn-sm btn-outline-success w-100"><i class="fas fa-flask me-1"></i>Skills Lab Schedule</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-chalkboard-teacher me-2"></i>Teaching & Instructor Allocation</div>
                    <div class="bd">
                        <p class="small text-muted">Track teaching hours, clinical supervision hours, and instructor deployment across departments.</p>
<?php $instructorCount = 0; $clinicalSites = 0; $activeRotations = 0;
if ($staff_conn) {
    $r = $staff_conn->query("SELECT COUNT(*) c FROM staff WHERE employment_category='academic' AND status='Active'"); if ($r) $instructorCount = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(DISTINCT facility_name) c FROM clinical_placements WHERE status='Active'"); if ($r) $clinicalSites = (int)$r->fetch_assoc()['c'];
    $r = $staff_conn->query("SELECT COUNT(*) c FROM clinical_placements WHERE status='Active' AND CURDATE() BETWEEN IFNULL(start_date, CURDATE()) AND IFNULL(end_date, CURDATE())"); if ($r) $activeRotations = (int)$r->fetch_assoc()['c'];
} ?>
                        <div class="row g-2 text-center mb-3">
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-primary"><?= $instructorCount ?></div><small>Instructors</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-success"><?= $clinicalSites ?></div><small>Clinical Sites</small></div></div>
                            <div class="col-4"><div class="p-3 border rounded bg-light"><div class="fs-3 fw-bold text-info"><?= $activeRotations ?></div><small>Active Rotations</small></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: COMMUNICATION ═══════════════ -->
    <div class="hr-section" id="section-comms">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-bullhorn me-2"></i>HR Announcements</div>
                    <div class="bd">
                        <a href="../news.php" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-newspaper me-1"></i>Manage News & Announcements</a>
                        <a href="../messaging.php" class="btn btn-sm btn-outline-info w-100"><i class="fas fa-comments me-1"></i>Staff Messaging</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-envelope me-2"></i>Policy Updates & Notices</div>
                    <div class="bd">
                        <p class="small text-muted">Communicate HR policy changes, institutional notices, and emergency alerts to all staff.</p>
                        <a href="../institutional-alerts.php" class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-bell me-1"></i>Send Emergency Alert</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: REPORTS ═══════════════ -->
    <div class="hr-section" id="section-reports">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-users me-2"></i>Staff Reports</div>
                    <div class="bd">
                        <p class="small text-muted">Staff utilization, turnover, demographic reports.</p>
                        <a href="staff-directory.php" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-download me-1"></i>Staff Directory Export</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-calendar-alt me-2"></i>Attendance & Leave Reports</div>
                    <div class="bd">
                        <a href="staff-attendance.php" class="btn btn-sm btn-outline-success w-100 mb-2"><i class="fas fa-clock me-1"></i>Attendance Summary</a>
                        <a href="leave-management.php" class="btn btn-sm btn-outline-warning w-100"><i class="fas fa-calendar-alt me-1"></i>Leave Usage Reports</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-chart-bar me-2"></i>Compliance & Training Reports</div>
                    <div class="bd">
                        <a href="professional-licenses.php" class="btn btn-sm btn-outline-danger w-100 mb-2"><i class="fas fa-certificate me-1"></i>License Compliance</a>
                        <a href="training-cpd.php" class="btn btn-sm btn-outline-info w-100"><i class="fas fa-graduation-cap me-1"></i>Training Completion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SECTION: SETTINGS ═══════════════ -->
    <div class="hr-section" id="section-settings">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-building me-2"></i>Departments</div>
                    <div class="bd">
                        <p><strong>Total:</strong> <?= count($departments) ?></p>
                        <div class="mb-2">
<?php foreach ($departments as $d): ?>
                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($d['department_name']) ?></span>
<?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-user-tag me-2"></i>Roles</div>
                    <div class="bd">
                        <p><strong>Total:</strong> <?= count($roles) ?></p>
                        <div class="mb-2">
<?php foreach ($roles as $r): ?>
                            <span class="badge bg-info me-1"><?= htmlspecialchars($r['role_name']) ?></span>
<?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-card">
                    <div class="hd"><i class="fas fa-cog me-2"></i>System Settings</div>
                    <div class="bd">
                        <a href="../organogram.php" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-sitemap me-1"></i>Organogram</a>
                        <a href="../includes/settings_modal.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-sliders-h me-1"></i>HR Configuration</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ ADD STAFF MODAL ═══ -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add_staff">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add Staff Member</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12"><label class="form-label fw-semibold">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
          <div class="col-12"><label class="form-label fw-semibold">Email *</label><input type="email" name="email" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Phone</label><input type="text" name="phone" class="form-control"></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Position</label><input type="text" name="position" class="form-control"></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Department</label>
            <select name="department" class="form-select">
              <option value="">-- Select --</option>
<?php foreach ($departments as $d): ?>
              <option value="<?= htmlspecialchars($d['department_name']) ?>"><?= htmlspecialchars($d['department_name']) ?></option>
<?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label fw-semibold">Role</label>
            <select name="role_id" class="form-select">
              <option value="0">Select Role</option>
<?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
<?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label fw-semibold">Employment Type</label>
            <select name="employment_type" class="form-select">
              <option value="full-time">Full Time</option>
              <option value="part-time">Part Time</option>
              <option value="contract">Contract</option>
              <option value="locum">Locum</option>
              <option value="temporary">Temporary</option>
              <option value="intern">Intern</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label fw-semibold">Category</label>
            <select name="employment_category" class="form-select">
              <option value="academic">Academic</option>
              <option value="clinical">Clinical</option>
              <option value="administrative">Administrative</option>
              <option value="support">Support</option>
            </select>
          </div>
          <div class="col-12"><small class="text-muted">Default password: <code>isnm2026</code></small></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Staff</button>
      </div>
    </form>
  </div>
</div>

<script>
(function() {
    var sections = document.querySelectorAll('.hr-section');

    function showSection(id) {
        sections.forEach(function(s) { s.classList.remove('active'); });
        var target = document.getElementById('section-' + id);
        if (target) target.classList.add('active');
    }

    var hash = window.location.hash.replace('#section-', '');
    if (hash && document.getElementById('section-' + hash)) showSection(hash);

    function updateClock() {
        var el = document.getElementById('hrClock');
        if (!el) return;
        var now = new Date();
        el.textContent = now.toLocaleDateString('en-UG', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + now.toLocaleTimeString('en-UG');
    }
    updateClock();
    setInterval(updateClock, 1000);
})();
</script>

<!-- ═══ AJAX MODULE LOADING ═══ -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.hr-content');
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

            var section = href.split('section=')[1] || href.split('page=')[1] || 'home';
            fetch('hr-manager.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.hr-content');
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

function openProfileModal(){var m=document.getElementById('profileModal');if(m){var bsModal=new bootstrap.Modal(m);bsModal.show();}}
function editStaff(id,name,email,phone,position,department,roleId,empType,empCat,status){
    document.getElementById('edit_id').value=id;
    document.getElementById('edit_full_name').value=name;
    document.getElementById('edit_email').value=email;
    document.getElementById('edit_phone').value=phone||'';
    document.getElementById('edit_position').value=position||'';
    document.getElementById('edit_department').value=department||'';
    document.getElementById('edit_role_id').value=roleId||'';
    document.getElementById('edit_employment_type').value=empType||'full-time';
    document.getElementById('edit_employment_category').value=empCat||'administrative';
    document.getElementById('edit_status').value=status||'Active';
    new bootstrap.Modal(document.getElementById('editStaffModal')).show();
}
function submitEditStaff(){
    var fd=new FormData(document.getElementById('editStaffForm'));
    fetch(window.location.href,{method:'POST',body:fd})
    .then(function(r){return r.json()})
    .then(function(d){
        if(d.success){window.location.reload();}
        else{alert('Error: '+(d.error||'Failed'));}
    })
    .catch(function(e){alert('Error updating staff');});
}
function deleteStaff(id,name){
    if(!confirm('Delete staff member "'+name+'"? This cannot be undone.')) return;
    var fd=new FormData();
    fd.append('action','delete_staff');
    fd.append('id',id);
    fetch(window.location.href,{method:'POST',body:fd})
    .then(function(r){return r.json()})
    .then(function(d){
        if(d.success){window.location.reload();}
        else{alert('Error: '+(d.error||'Failed'));}
    })
    .catch(function(e){alert('Error deleting staff');});
}
</script>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="editStaffForm" onsubmit="event.preventDefault(); submitEditStaff()">
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="action" value="edit_staff">
                <div class="row g-3">
                    <div class="col-md-6"><div class="mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" id="edit_full_name" class="form-control" required></div></div>
                    <div class="col-md-6"><div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" id="edit_email" class="form-control" required></div></div>
                    <div class="col-md-6"><div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" id="edit_phone" class="form-control"></div></div>
                    <div class="col-md-6"><div class="mb-3"><label class="form-label">Position</label><input type="text" name="position" id="edit_position" class="form-control"></div></div>
                    <div class="col-md-6"><div class="mb-3"><label class="form-label">Department</label><input type="text" name="department" id="edit_department" class="form-control"></div></div>
                    <div class="col-md-6"><div class="mb-3"><label class="form-label">Role</label><select name="role_id" id="edit_role_id" class="form-select">
                        <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option><?php endforeach; ?>
                    </select></div></div>
                    <div class="col-md-4"><div class="mb-3"><label class="form-label">Employment Type</label><select name="employment_type" id="edit_employment_type" class="form-select"><option value="full-time">Full Time</option><option value="part-time">Part Time</option><option value="contract">Contract</option><option value="intern">Intern</option></select></div></div>
                    <div class="col-md-4"><div class="mb-3"><label class="form-label">Category</label><select name="employment_category" id="edit_employment_category" class="form-select"><option value="administrative">Administrative</option><option value="academic">Academic</option><option value="support">Support</option><option value="management">Management</option></select></div></div>
                    <div class="col-md-4"><div class="mb-3"><label class="form-label">Status</label><select name="status" id="edit_status" class="form-select"><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="On Leave">On Leave</option><option value="Terminated">Terminated</option></select></div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
            </div>
        </form>
    </div></div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/profile_settings.php';
if (function_exists('renderProfileModal')) renderProfileModal();
if (function_exists('renderProfileStyles')) renderProfileStyles();
if (function_exists('renderProfileScripts')) renderProfileScripts();
?>
</body>
</html>
