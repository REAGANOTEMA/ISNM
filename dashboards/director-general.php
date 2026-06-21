<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/config_enhanced.php';
require_once __DIR__ . '/../includes/institution_stats.php';
require_once __DIR__ . '/../includes/student_profile_component.php';
require_once __DIR__ . '/../includes/student_set_viewer.php';
require_once __DIR__ . '/../includes/news_management_widget.php';
require_once __DIR__ . '/../includes/email_notifications.php';
require_once __DIR__ . '/../includes/notification_helper.php';
require_once __DIR__ . '/../views/student_data_loader.php';
require_once __DIR__ . '/../includes/institutional_framework.php';
require_once __DIR__ . '/../includes/approval_workflow.php';
require_once __DIR__ . '/../includes/approval_integration.php';
require_once __DIR__ . '/../includes/executive_overview.php';

$loader = new StudentDataLoader();

$ctx          = bootstrapStaffDashboard([]);
$auth_service = $ctx['auth'];
$conn         = $ctx['staff'];
$studentsConn = $ctx['students'];
$websiteConn  = $ctx['website'];
$user         = $ctx['user'];

$user_id   = (int)($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_name = $user['full_name'] ?? ($_SESSION['full_name'] ?? 'Director General');

$overview            = getInstitutionOverviewStats();
$total_students      = $overview['total_students'];
$total_staff         = $overview['total_staff'];
$total_applications  = $overview['website_applications'];
$pending_apps        = $overview['pending_applications'];
$loader = new StudentDataLoader();
$excel_files_summary = $loader->getExcelFileSummary();

// ── Dashboard stats cache ──
$dg_cache_key = 'dg_dashboard_stats';
$dg_use_cache  = function_exists('getCacheData') && function_exists('setCacheData');
$dg_cached     = $dg_use_cache ? getCacheData($dg_cache_key) : null;
if ($dg_cached) {
    $today_collection    = $dg_cached['today_collection'];
    $outstanding         = $dg_cached['outstanding'];
    $staff_list          = $dg_cached['staff_list'];
    $recent_activities   = $dg_cached['recent_activities'];
    $dept_list           = $dg_cached['dept_list'];
    $user_role_id        = $dg_cached['user_role_id'];
    $staffAttendanceToday= $dg_cached['staffAttendanceToday'];
    $week_collection     = $dg_cached['week_collection'];
    $month_collection    = $dg_cached['month_collection'];
    $total_expenses      = $dg_cached['total_expenses'];
    $total_revenue       = $dg_cached['total_revenue'];
    $recent_payments     = $dg_cached['recent_payments'];
    $pendingContacts     = $dg_cached['pendingContacts'];
    $pendingVolunteers   = $dg_cached['pendingVolunteers'];
    $pendingDonations    = $dg_cached['pendingDonations'];
    $pendingApplications = $dg_cached['pendingApplications'];
    $recentSubmissions   = $dg_cached['recentSubmissions'];
    $totalPending        = $pendingContacts + $pendingVolunteers + $pendingDonations + $pendingApplications;
} else {
    $today_collection = 0; $outstanding = 0;
    if ($conn) {
        $r = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE DATE(payment_date)=CURDATE() AND status IN('verified','approved')");
        if ($r) $today_collection = $r->fetch_assoc()['v'] ?? 0;
        $r2 = $conn->query("SELECT COALESCE(SUM(balance),0) v FROM student_invoices WHERE status IN('pending','partial','overdue')");
        if ($r2) $outstanding = $r2->fetch_assoc()['v'] ?? 0;
    }

    $staff_list = [];
    if ($conn) {
        $sr = $conn->query("SELECT s.id,s.staff_id,s.full_name,s.email,s.position,s.department,s.status,s.last_login,sr.role_name
            FROM staff s LEFT JOIN staff_roles sr ON s.role_id=sr.id ORDER BY s.full_name LIMIT 20");
        if ($sr) while ($row = $sr->fetch_assoc()) $staff_list[] = $row;
    }

    $recent_activities = [];
    if ($conn) {
        $ar = $conn->query("SELECT activity_type,activity_description,created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 8");
        if ($ar) while ($r = $ar->fetch_assoc()) $recent_activities[] = $r;
    }

    $dept_list = [];
    if ($conn) {
        $dr = $conn->query("SELECT id,department_name,department_code,department_level FROM staff_departments ORDER BY department_level,department_name");
        if ($dr) while ($r = $dr->fetch_assoc()) $dept_list[] = $r;
    }

    $user_role_id = 0;
    if ($conn) {
        $ri = $conn->query("SELECT role_id FROM staff WHERE id = $user_id");
        if ($ri) { $user_role_id = (int)$ri->fetch_assoc()['role_id']; }
    }

    $staffAttendanceToday = ['present' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0, 'onLeave' => 0];
    if ($conn) {
        $sa = $conn->query("SELECT status, COUNT(*) cnt FROM staff_attendance WHERE DATE(date)=CURDATE() GROUP BY status");
        if ($sa) while ($row = $sa->fetch_assoc()) {
            $k = strtolower(str_replace(' ', '_', $row['status']));
            if (isset($staffAttendanceToday[$k])) $staffAttendanceToday[$k] = (int)$row['cnt'];
        }
    }

    $week_collection = 0; $month_collection = 0; $total_expenses = 0; $total_revenue = 0;
    if ($conn) {
        $rw = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE YEARWEEK(payment_date)=YEARWEEK(CURDATE()) AND status IN('verified','approved')");
        if ($rw) $week_collection = $rw->fetch_assoc()['v'] ?? 0;
        $rm = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status IN('verified','approved')");
        if ($rm) $month_collection = $rm->fetch_assoc()['v'] ?? 0;
        $re = $conn->query("SELECT COALESCE(SUM(amount),0) v FROM expenses WHERE status IN('approved','paid')");
        if ($re) $total_expenses = $re->fetch_assoc()['v'] ?? 0;
        $rr = $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE status IN('verified','approved')");
        if ($rr) $total_revenue = $rr->fetch_assoc()['v'] ?? 0;
    }

    $recent_payments = [];
    if ($conn) {
        $rp = $conn->query("SELECT p.*, s.first_name, s.last_name, s.student_number FROM payments p LEFT JOIN students s ON p.student_id = s.id ORDER BY p.payment_date DESC LIMIT 5");
        if ($rp) while ($row = $rp->fetch_assoc()) $recent_payments[] = $row;
    }

    $pendingContacts = 0; $pendingVolunteers = 0; $pendingDonations = 0; $pendingApplications = 0;
    $recentSubmissions = [];
    if ($websiteConn) {
        $r = $websiteConn->query("SELECT COUNT(*) c FROM contact_submissions WHERE status='unread'");
        if ($r) $pendingContacts = (int)$r->fetch_assoc()['c'];
        $r = $websiteConn->query("SELECT COUNT(*) c FROM volunteer_applications WHERE status='pending'");
        if ($r) $pendingVolunteers = (int)$r->fetch_assoc()['c'];
        $r = $websiteConn->query("SELECT COUNT(*) c FROM donations WHERE status='pending'");
        if ($r) $pendingDonations = (int)$r->fetch_assoc()['c'];
        $r = $websiteConn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Pending'");
        if ($r) $pendingApplications = (int)$r->fetch_assoc()['c'];
        $union = $websiteConn->query("
            (SELECT 'contact' as type, id, CONCAT(first_name,' ',last_name) as name, subject as title, created_at FROM contact_submissions WHERE status='unread')
            UNION ALL
            (SELECT 'volunteer', id, CONCAT(first_name,' ',last_name), CONCAT(profession,' - ',opportunity), created_at FROM volunteer_applications WHERE status='pending')
            UNION ALL
            (SELECT 'donation', id, donor_name, CONCAT('UGX ',FORMAT(amount,0)), created_at FROM donations WHERE status='pending')
            UNION ALL
            (SELECT 'application', id, CONCAT(first_name,' ',surname), program_applied, submitted_at FROM student_applications WHERE status='Pending')
            ORDER BY created_at DESC LIMIT 8
        ");
        if ($union) while ($row = $union->fetch_assoc()) $recentSubmissions[] = $row;
    }
    $totalPending = $pendingContacts + $pendingVolunteers + $pendingDonations + $pendingApplications;

    if ($dg_use_cache) {
        setCacheData($dg_cache_key, [
            'today_collection'    => $today_collection,
            'outstanding'         => $outstanding,
            'staff_list'          => $staff_list,
            'recent_activities'   => $recent_activities,
            'dept_list'           => $dept_list,
            'user_role_id'        => $user_role_id,
            'staffAttendanceToday'=> $staffAttendanceToday,
            'week_collection'     => $week_collection,
            'month_collection'    => $month_collection,
            'total_expenses'      => $total_expenses,
            'total_revenue'       => $total_revenue,
            'recent_payments'     => $recent_payments,
            'pendingContacts'     => $pendingContacts,
            'pendingVolunteers'   => $pendingVolunteers,
            'pendingDonations'    => $pendingDonations,
            'pendingApplications' => $pendingApplications,
            'recentSubmissions'   => $recentSubmissions,
        ], '+2 minutes');
    }
}

$recent_students = [];
try { $recent_students = array_slice($loader->loadAllStudents(), 0, 6); } catch (Exception $e) {}

// ── DG page routing ──
// Map /director/{page} to internal section names
$dgPageToSection = [
    'overview'      => 'executive',
    'departments'   => 'departments',
    'performance'   => 'performance',
    'finance'       => 'financial',
    'staff'         => 'staff',
    'students'      => 'student',
    'submissions'   => 'services',
    'approvals'     => 'approvals',
    'assets'        => 'store',
    'communications'=> 'communications',
    'audit'         => 'audit',
    'actions'       => 'quick',
];
$dgPage  = $_GET['page'] ?? '';
$dgSection = $dgPageToSection[$dgPage] ?? 'executive';

// ── CEO vs DG branding ──
$isCEO      = (stripos($user_role, 'ceo') !== false) || (($_GET['dg_role'] ?? '') === 'ceo');
$dgRole     = $isCEO ? 'CEO' : 'Director General';
$dgIcon     = $isCEO ? 'fa-chart-line' : 'fa-crown';
$dgSubtitle = $isCEO ? 'Executive Oversight &amp; Strategy &bull; Iganga School of Nursing &amp; Midwifery' : 'Full Institution Oversight &bull; Iganga School of Nursing &amp; Midwifery';
$dgRoutePrefix = $isCEO ? '/ceo' : '/director';

// ── Reusable page toolbar (breadcrumb, back, search, export) ──
function dgToolbar(string $title, string $icon, string $badgeText = '', string $badgeClass = 'bg-primary'): void {
    $sectionLabels = [
        'executive'=>'Executive Overview','departments'=>'Department Monitoring',
        'performance'=>'Director Performance','financial'=>'Financial Overview',
        'staff'=>'Staff Management','student'=>'Student Management',
        'services'=>'Pending Submissions','approvals'=>'Approval Center',
        'store'=>'Store & Assets','communications'=>'Communications',
        'audit'=>'Audit Trail','quick'=>'Quick Actions',
    ];
    $label = $sectionLabels[$GLOBALS['dgSection']] ?? 'Dashboard';
    ?>
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:14px;padding:10px 14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
        <nav style="font-size:12px;color:#64748b;flex:1;">
            <a href="#executive" style="color:#3b82f6;text-decoration:none;" onclick="switchToSection('executive');return false;">Dashboard</a>
            <span style="margin:0 4px;">›</span>
            <span style="color:#0f172a;font-weight:600;"><?= htmlspecialchars($label) ?></span>
        </nav>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <input type="search" placeholder="Search this page…" class="dg-page-search" style="padding:4px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;width:180px;outline:none;" oninput="dgFilterTable(this.value)">
            <button onclick="window.print()" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;color:#475569;"><i class="fas fa-print me-1"></i>Print</button>
            <button onclick="dgExportCSV()" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;color:#475569;"><i class="fas fa-download me-1"></i>Export</button>
            <?php if ($badgeText): ?>
            <span class="badge <?= $badgeClass ?> rounded-pill" style="font-size:11px;"><?= htmlspecialchars($badgeText) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ann_title'])) {
    $title    = $conn ? $conn->real_escape_string(trim($_POST['ann_title'] ?? '')) : '';
    $body     = $conn ? $conn->real_escape_string(trim($_POST['ann_body'] ?? '')) : '';
    $target   = $conn ? $conn->real_escape_string($_POST['ann_target'] ?? 'All') : 'All';
    $priority = $conn ? $conn->real_escape_string($_POST['ann_priority'] ?? 'Normal') : 'Normal';
    if ($title && $body && $studentsConn) {
        $studentsConn->query("INSERT INTO announcements (title,body,target_audience,priority,posted_by,is_active,created_at) VALUES ('$title','$body','$target','$priority',$user_id,1,NOW())");
        $_SESSION['success'] = "Announcement published to all $target.";
        $nid = createNotification('New Announcement: ' . $title, $body, 'director-general.php', 'announcement', 'fas fa-bullhorn');
        if ($nid) {
            notifyAllStaff($nid);
            if (function_exists('notifyDirectorGeneral')) {
                notifyDirectorGeneral("New Announcement: $title", "The DG posted a new announcement targeting $target.\n\n$body\n\nPriority: $priority");
            }
        }
    }
    header('Location: director-general.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'])) {
    $first_name   = trim($_POST['first_name'] ?? '');
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $student_id   = trim($_POST['student_id'] ?? '');
    $program      = trim($_POST['program'] ?? '');
    $level        = trim($_POST['level'] ?? '1');
    $intake_year  = trim($_POST['intake_year'] ?? date('Y'));
    $intake_period = trim($_POST['intake_period'] ?? 'January');
    $phone        = trim($_POST['phone'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    if ($first_name && $last_name && $student_id) {
        try {
            // Insert into pending_students for DG approval
            if ($conn) {
                $stmt = $conn->prepare("INSERT INTO pending_students (first_name, middle_name, last_name, student_number, program, level, intake_year, intake_period, phone, email, date_of_birth, submitted_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_approval')");
                if ($stmt) {
                    $stmt->bind_param("sssssssssssi", $first_name, $middle_name, $last_name, $student_id, $program, $level, $intake_year, $intake_period, $phone, $email, $date_of_birth, $user_id);
                    $stmt->execute();
                    $pendingId = $stmt->insert_id;
                    $stmt->close();

                    // Submit for approval workflow
                    require_once __DIR__ . '/../includes/approval_integration.php';
                    if (function_exists('submitStudentForApproval')) {
                        submitStudentForApproval($pendingId, $conn);
                    }

                    $_SESSION['success'] = "Student $first_name $last_name submitted for DG approval.";
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error adding student: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please fill all required fields!";
    }
    header('Location: director-general.php'); exit;
}

// ── CRUD POST handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dg_action'])) {
    $action = $_POST['dg_action'];
    $ok = false; $msg = '';

    if ($action === 'add_department' && $conn) {
        $name = $conn->real_escape_string(trim($_POST['dept_name'] ?? ''));
        $code = $conn->real_escape_string(strtoupper(trim($_POST['dept_code'] ?? '')));
        $level = $conn->real_escape_string(trim($_POST['dept_level'] ?? ''));
        if ($name && $code) {
            $conn->query("INSERT INTO staff_departments (department_name,department_code,department_level) VALUES ('$name','$code','$level')");
            $ok = true; $msg = "Department $name added.";
        } else { $msg = 'Name and code required.'; }
    }

    if ($action === 'delete_department' && $conn) {
        $id = (int)($_POST['dept_id'] ?? 0);
        if ($id) { $conn->query("DELETE FROM staff_departments WHERE id=$id"); $ok = true; $msg = 'Department deleted.'; }
        else {
            $code = $conn->real_escape_string($_POST['dept_code'] ?? '');
            if ($code) { $conn->query("DELETE FROM staff_departments WHERE department_code='$code'"); $ok = true; $msg = 'Department deleted.'; }
        }
    }

    if ($action === 'add_staff' && $conn) {
        $name = $conn->real_escape_string(trim($_POST['staff_name'] ?? ''));
        $sid = $conn->real_escape_string(trim($_POST['staff_id'] ?? ''));
        $em = $conn->real_escape_string(trim($_POST['staff_email'] ?? ''));
        $ph = $conn->real_escape_string(trim($_POST['staff_phone'] ?? ''));
        $dp = $conn->real_escape_string(trim($_POST['staff_dept'] ?? ''));
        $ro = $conn->real_escape_string(trim($_POST['staff_role'] ?? 'staff'));
        $st = $conn->real_escape_string(trim($_POST['staff_status'] ?? 'Active'));
        if ($name && $sid) {
            $conn->query("INSERT INTO staff (staff_id,full_name,email,phone,department,position,status) VALUES ('$sid','$name','$em','$ph','$dp','$ro','$st')");
            $ok = true; $msg = "Staff $name added.";
        } else { $msg = 'Name and Staff ID required.'; }
    }

    if ($action === 'delete_staff' && $conn) {
        $sid = (int)($_POST['staff_id'] ?? 0);
        if ($sid) { $conn->query("DELETE FROM staff WHERE id=$sid"); $ok = true; $msg = 'Staff removed.'; }
    }

    if ($action === 'approve_submission' && $websiteConn) {
        $type = $websiteConn->real_escape_string($_POST['sub_type'] ?? '');
        $subid = (int)($_POST['sub_id'] ?? 0);
        if ($type === 'contact') $websiteConn->query("UPDATE contact_submissions SET status='resolved' WHERE id=$subid");
        elseif ($type === 'volunteer') $websiteConn->query("UPDATE volunteer_applications SET status='approved' WHERE id=$subid");
        elseif ($type === 'donation') $websiteConn->query("UPDATE donations SET status='verified' WHERE id=$subid");
        elseif ($type === 'application') $websiteConn->query("UPDATE student_applications SET status='Approved' WHERE id=$subid");
        if ($subid) { $ok = true; $msg = 'Submission approved.'; }
    }
    if ($action === 'approve_submission' && $conn && ($_POST['sub_type'] ?? '') === 'store') {
        $ref = $conn->real_escape_string($_POST['sub_ref'] ?? '');
        if ($ref) { $conn->query("UPDATE store_requests SET status='approved',approved_by=$user_id,approved_at=NOW() WHERE request_number='$ref'"); $ok = true; $msg = 'Store request approved.'; }
    }

    if ($action === 'reject_submission' && $websiteConn) {
        $type = $websiteConn->real_escape_string($_POST['sub_type'] ?? '');
        $subid = (int)($_POST['sub_id'] ?? 0);
        if ($type === 'contact') $websiteConn->query("UPDATE contact_submissions SET status='spam' WHERE id=$subid");
        elseif ($type === 'volunteer') $websiteConn->query("UPDATE volunteer_applications SET status='rejected' WHERE id=$subid");
        elseif ($type === 'donation') $websiteConn->query("UPDATE donations SET status='cancelled' WHERE id=$subid");
        elseif ($type === 'application') $websiteConn->query("UPDATE student_applications SET status='Rejected' WHERE id=$subid");
        $ok = true; $msg = 'Submission rejected.';
    }

    if ($action === 'resolve_alert') {
        $aid = (int)($_POST['alert_id'] ?? 0);
        $subType = ($_POST['sub_type'] ?? '');
        $subId = (int)($_POST['sub_id'] ?? 0);
        // alerts table is in isnm_db via $conn
        if ($aid && $conn) { $conn->query("UPDATE alerts SET status='resolved' WHERE id=$aid"); $ok = true; $msg = 'Alert resolved.'; }
        if ($subType === 'all_alerts' && $conn) { $conn->query("UPDATE alerts SET status='resolved' WHERE status='active'"); $ok = true; $msg = 'All alerts resolved.'; }
        // website submissions are in website_db via $websiteConn
        if ($subType && $subId && $websiteConn) {
            $t = $websiteConn->real_escape_string($subType);
            $q = '';
            if ($t === 'contact') $q = "UPDATE contact_submissions SET status='resolved' WHERE id=$subId";
            elseif ($t === 'volunteer') $q = "UPDATE volunteer_applications SET status='resolved' WHERE id=$subId";
            elseif ($t === 'donation') $q = "UPDATE donations SET status='verified' WHERE id=$subId";
            elseif ($t === 'application') $q = "UPDATE student_applications SET status='Resolved' WHERE id=$subId";
            if ($q) { $websiteConn->query($q); $ok = true; $msg = 'Submission resolved.'; }
        }
    }

    if ($ok) $_SESSION['success'] = $msg;
    else $_SESSION['error'] = $msg;
    header('Location: director-general.php'); exit;
}

// ── Monthly data for charts ──
$mn = []; $rv = []; $ex = [];
for ($m = 5; $m >= 0; $m--) {
    $ts = strtotime("-$m months"); $mn[] = date('M Y', $ts);
    $mo = date('m', $ts); $yr = date('Y', $ts);
    $r = $conn ? $conn->query("SELECT COALESCE(SUM(amount_received),0) v FROM payments WHERE MONTH(payment_date)=$mo AND YEAR(payment_date)=$yr AND status IN('verified','approved')") : null;
    $e = $conn ? $conn->query("SELECT COALESCE(SUM(amount),0) v FROM expenses WHERE MONTH(expense_date)=$mo AND YEAR(expense_date)=$yr AND status IN('approved','paid')") : null;
    $rv[] = $r ? (float)$r->fetch_assoc()['v'] : 0; $ex[] = $e ? (float)$e->fetch_assoc()['v'] : 0;
}
$ml = []; $mv = [];
if ($conn) {
    $mr = $conn->query("SELECT payment_method, COALESCE(SUM(amount_received),0) t FROM payments WHERE status IN('verified','approved') GROUP BY payment_method ORDER BY t DESC LIMIT 5");
    if ($mr) while ($row = $mr->fetch_assoc()) { $ml[] = $row['payment_method'] ?: 'Other'; $mv[] = (float)$row['t']; }
}
$collRate = $total_revenue > 0 ? round(min(100, ($today_collection / max(1, $total_revenue / 365)) * 100)) : 50;
$attTotal = ($staffAttendanceToday['present']??0)+($staffAttendanceToday['late']??0)+($staffAttendanceToday['absent']??0)+($staffAttendanceToday['on_leave']??0);
$presentPct = $attTotal > 0 ? round(($staffAttendanceToday['present']??0)/$attTotal*100) : 0;
?>
<?php $pageTitle = 'Director General Dashboard'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root {
  --dg-primary: #1a1a2e;
  --dg-secondary: #16213e;
  --dg-accent: #0f3460;
  --dg-gold: #e2b714;
  --dg-gold-light: #fef3c7;
  --dg-gradient: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #1e293b 100%);
  --dg-card-bg: #ffffff;
  --dg-text: #0f172a;
  --dg-text-muted: #64748b;
  --dg-border: #e2e8f0;
  --dg-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --dg-shadow-lg: 0 10px 40px rgba(0,0,0,0.08);
}
body { background: #eef1f5; font-family: 'Inter', -apple-system, sans-serif; color: var(--dg-text); font-size: 13px; overflow-x: hidden; }
body::before { content:''; position:fixed; inset:0; background:radial-gradient(ellipse at 20% 50%,rgba(59,130,246,0.03) 0%,transparent 50%),radial-gradient(ellipse at 80% 20%,rgba(5,150,105,0.02) 0%,transparent 50%); pointer-events:none; z-index:0; }
.page-content { padding: 0 !important; }

/* ── Top Bar ── */
.dg-topbar {
  background: var(--dg-gradient);
  padding: 10px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 16px rgba(0,0,0,0.15);
  margin-left: 270px;
}
@media (max-width: 768px) { .dg-topbar { margin-left: 0; } }
.dg-topbar-left { display: flex; align-items: center; gap: 10px; }
.dg-topbar-left .crown { font-size: 22px; color: var(--dg-gold); }
.dg-topbar-left h1 { font-size: 15px; font-weight: 700; color: #fff; margin: 0; letter-spacing: -0.2px; }
.dg-topbar-left .subtitle { font-size: 11px; color: rgba(255,255,255,0.65); margin: 0; }
.dg-topbar-right { display: flex; align-items: center; gap: 12px; }
.dg-topbar-right .date-badge { font-size: 12px; color: rgba(255,255,255,0.8); background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px; }
.dg-topbar-right .logout-link { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 12px; padding: 4px 12px; border-radius: 20px; transition: all 0.2s; border: 1px solid rgba(255,255,255,0.15); }
.dg-topbar-right .btn-print-top { background:rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.2); border-radius:20px; padding:4px 14px; font-size:12px; cursor:pointer; transition:all 0.2s; }
.dg-topbar-right .btn-print-top:hover { background:rgba(255,255,255,0.2); }

/* ── Content ── */
.dg-content { padding: 18px 22px 30px; max-width: 1600px; margin: 0 0 0 270px; background: #fafbfc; min-height: calc(100vh - 60px); overflow-x: hidden; word-break: break-word; }
@media (max-width: 768px) { .dg-content { margin-left: 0; } }

/* ── KPI Cards ── */
.kpi-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 14px; }
@media (max-width: 900px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 500px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
.kpi-card {
  background: var(--dg-card-bg);
  border-radius: 10px;
  padding: 14px 14px 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  transition: all 0.25s ease;
  border-left: 4px solid transparent;
  position: relative;
  overflow: hidden;
  cursor: default;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.kpi-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  margin-bottom: 6px;
}
.kpi-value { font-size: 20px; font-weight: 800; color: var(--dg-text); letter-spacing: -0.3px; line-height: 1.2; }
.kpi-label { font-size: 10px; font-weight: 700; color: var(--dg-text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.kpi-trend { font-size: 10px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; margin-top: 5px; padding: 2px 8px; border-radius: 10px; }
.kpi-bl { border-left-color: #3b82f6; } .kpi-bl .kpi-icon { background: #eff6ff; color: #2563eb; }
.kpi-gr { border-left-color: #10b981; } .kpi-gr .kpi-icon { background: #ecfdf5; color: #059669; }
.kpi-cy { border-left-color: #06b6d4; } .kpi-cy .kpi-icon { background: #ecfeff; color: #0891b2; }
.kpi-rd { border-left-color: #ef4444; } .kpi-rd .kpi-icon { background: #fef2f2; color: #dc2626; }
.kpi-or { border-left-color: #f59e0b; } .kpi-or .kpi-icon { background: #fffbeb; color: #d97706; }
.kpi-pr { border-left-color: #8b5cf6; } .kpi-pr .kpi-icon { background: #f5f3ff; color: #7c3aed; }

/* ── Analytics Row ── */
.analytics-strip {
  background: var(--dg-card-bg);
  border-radius: 10px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  padding: 12px 14px;
  margin-bottom: 14px;
  display: grid;
  grid-template-columns: 2fr 1.2fr 1fr 1fr 1.5fr;
  gap: 10px;
  border: 1px solid #f1f5f9;
}
@media (max-width: 900px) { .analytics-strip { grid-template-columns: 1fr 1fr; } }
@media (max-width: 500px) { .analytics-strip { grid-template-columns: 1fr; } }
.analytics-strip .ax { min-height: 72px; position: relative; }
.analytics-strip .ax-title { font-size: 9px; font-weight: 700; color: var(--dg-text-muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }
.analytics-strip .ax canvas { max-height: 60px; }

/* ── Section Cards ── */
.section-card {
  background: var(--dg-card-bg);
  border-radius: 10px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  padding: 16px 18px;
  margin-bottom: 14px;
  transition: box-shadow 0.2s;
  border: 1px solid #f1f5f9;
}
.section-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  flex-wrap: wrap;
  gap: 6px;
}
.section-title {
  font-size: 14px;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--dg-text);
}
.section-title i { font-size: 15px; }
.section-subtitle { font-size: 11px; color: var(--dg-text-muted); margin: 0; }

/* ── Stat blocks (for financial, attendance) ── */
.stat-block {
  padding: 10px 8px;
  border-radius: 8px;
  text-align: center;
  transition: transform 0.2s;
}
.stat-block:hover { transform: scale(1.03); }
.stat-block .stat-val { font-size: 16px; font-weight: 800; letter-spacing: -0.2px; }
.stat-block .stat-lbl { font-size: 10px; font-weight: 500; margin-top: 1px; }

/* ── Tables ── */
.dg-table { font-size: 12px; margin-bottom: 0; }
.dg-table thead th { background: #f8fafc; font-weight: 600; color: var(--dg-text-muted); text-transform: uppercase; font-size: 10px; letter-spacing: 0.4px; padding: 7px 10px; border-bottom: 2px solid var(--dg-border); }
.dg-table td { padding: 7px 10px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.dg-table tbody tr:hover { background: #f8fafc; }
.table-scroll { max-height: 260px; overflow-y: auto; }
.table-scroll::-webkit-scrollbar { width: 4px; }
.table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

/* ── Activity timeline ── */
.activity-timeline { list-style: none; padding: 0; margin: 0; }
.activity-timeline li {
  display: flex;
  gap: 10px;
  padding: 7px 0;
  border-bottom: 1px solid #f1f5f9;
  align-items: flex-start;
}
.activity-timeline li:last-child { border-bottom: none; }
.activity-badge {
  flex-shrink: 0;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 9px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.2px;
  margin-top: 1px;
}

/* ── Pending submissions ── */
.submission-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid #f1f5f9;
}
.submission-item:last-child { border-bottom: none; }
.submission-avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 13px;
}
.submission-info { flex: 1; min-width: 0; }
.submission-name { font-weight: 600; font-size: 13px; }
.submission-detail { font-size: 11px; color: var(--dg-text-muted); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.submission-time { font-size: 10px; color: #94a3b8; flex-shrink: 0; }

/* ── Director performance ── */
.dir-card {
  background: #fafbfc;
  border: 1px solid var(--dg-border);
  border-radius: 8px;
  padding: 12px;
  height: 100%;
  transition: all 0.2s;
}
.dir-card:hover { border-color: #3b82f6; box-shadow: 0 2px 12px rgba(59,130,246,0.1); }
.dir-card .dir-name { font-weight: 700; font-size: 13px; margin-bottom: 1px; }
.dir-card .dir-role { font-size: 10px; color: var(--dg-text-muted); margin-bottom: 8px; }
.metric-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; margin-right: 5px; }

/* ── Badges ── */
.badge-soft { font-weight: 500; font-size: 10px; padding: 2px 8px; border-radius: 10px; }

/* ── Modals ── */
.modern-modal .modal-content { border: none; border-radius: 14px; box-shadow: 0 25px 60px rgba(0,0,0,0.2); }
.modern-modal .modal-header { border: none; padding: 14px 18px; border-radius: 14px 14px 0 0; }
.modern-modal .modal-body { padding: 16px 18px; }
.modern-modal .modal-footer { border: none; padding: 12px 18px; }

/* ── Animations ── */
@keyframes fadeInUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideIn { from { opacity: 0; transform: translateX(-12px); } to { opacity: 1; transform: translateX(0); } }
@keyframes pulseGlow { 0%, 100% { box-shadow: 0 0 0 0 rgba(59,130,246,0.15); } 50% { box-shadow: 0 0 0 8px rgba(59,130,246,0.05); } }
.an-fade { animation: fadeInUp 0.5s ease forwards; }
.an-slide { animation: slideIn 0.4s ease forwards; }
.an-pulse { animation: pulseGlow 2s ease infinite; }
.quick-chevron { transition: transform .25s ease; }
.quick-chevron.rotated { transform: rotate(180deg); }

/* ── Responsive ── */
@media (max-width: 1200px) { .analytics-strip { grid-template-columns: 1fr 1fr; } .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 992px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
  .dg-topbar { padding: 10px 14px; flex-direction: column; align-items: flex-start; }
  .dg-content { padding: 12px; }
  .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
  .kpi-card { padding: 10px 10px 8px; }
  .kpi-value { font-size: 16px; }
  .analytics-strip { grid-template-columns: 1fr; }
  .section-card { padding: 12px 14px; }
}
@media (max-width: 480px) { .kpi-grid { grid-template-columns: 1fr 1fr; gap: 6px; } }

/* ── Print ── */
@media print {
  body { background:#fff !important; font-size:10pt; }
  .sidebar, .dashboard-sidebar, .no-print, .btn-print-top, .dg-topbar, .logout-link { display:none !important; }
  .dg-content { padding:0 !important; margin:0 !important; max-width:100% !important; background:#fff !important; }
  .section-card { box-shadow:none !important; border:1px solid #ddd !important; break-inside:avoid; page-break-inside:avoid; }
  .kpi-grid { gap:6px; }
  .kpi-card { box-shadow:none !important; border:1px solid #e2e8f0 !important; break-inside:avoid; }
  .kpi-card .kpi-icon { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .badge { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .analytics-strip { box-shadow:none !important; border:1px solid #ddd !important; }
  .an-fade { animation:none !important; opacity:1 !important; }
  .table-scroll { max-height:none !important; overflow:visible !important; }
  .approval-hub .tab-content > .tab-pane { display:block !important; opacity:1 !important; }
  .approval-hub .nav-tabs { display:none !important; }
  .quick-chevron { display:none !important; }
  .collapse { display:block !important; height:auto !important; }
}
</style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<!-- ═══ TOP BAR ═══ -->
<div class="dg-topbar an-fade">
  <div class="dg-topbar-left">
    <i class="fas <?= $dgIcon ?> crown"></i>
    <div>
      <h1><?= $dgRole ?> – <?= htmlspecialchars($user_name) ?></h1>
      <p class="subtitle"><?= $dgSubtitle ?></p>
    </div>
  </div>
  <div class="dg-topbar-right">
    <span class="date-badge"><i class="far fa-calendar-alt me-1"></i><?= date('D, d M Y') ?></span>
    <button class="btn-print-top" onclick="window.print()" title="Print Dashboard"><i class="fas fa-print me-1"></i>Print</button>
    <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
  </div>
</div>

<!-- ═══ CONTENT ═══ -->
<div class="dg-content">

<?php if(!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show py-2 an-slide" style="border:none;border-radius:10px;background:#ecfdf5;color:#065f46;">
  <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($_SESSION['success']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:12px"></button>
</div>
<?php unset($_SESSION['success']); endif; ?>

<!-- ═══ KPI CARDS (clickable) ═══ -->
<div class="kpi-grid">
  <div class="kpi-card kpi-bl an-fade" style="animation-delay:0.05s;cursor:pointer;" onclick="switchToSection('student');return false;" title="Click to view Students">
    <div class="kpi-icon"><i class="fas fa-user-graduate"></i></div>
    <div class="kpi-value"><?= number_format($total_students) ?></div>
    <div class="kpi-label">Total Students</div>
    <span class="kpi-trend" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-arrow-up"></i> Active</span>
  </div>
  <div class="kpi-card kpi-gr an-fade" style="animation-delay:0.1s;cursor:pointer;" onclick="switchToSection('staff');return false;" title="Click to view Staff">
    <div class="kpi-icon"><i class="fas fa-users"></i></div>
    <div class="kpi-value"><?= number_format($total_staff) ?></div>
    <div class="kpi-label">Total Staff</div>
    <span class="kpi-trend" style="background:#ecfdf5;color:#059669;"><i class="fas fa-check-circle"></i> Active</span>
  </div>
  <div class="kpi-card kpi-cy an-fade" style="animation-delay:0.15s;cursor:pointer;" onclick="switchToSection('financial');return false;" title="Click to view Financials">
    <div class="kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
    <div class="kpi-value">UGX <?= number_format($today_collection) ?></div>
    <div class="kpi-label">Today Collection</div>
    <?php if($today_collection > 0): ?><span class="kpi-trend" style="background:#ecfeff;color:#0891b2;"><i class="fas fa-arrow-up"></i> Today</span><?php endif; ?>
  </div>
  <div class="kpi-card kpi-rd an-fade" style="animation-delay:0.2s;cursor:pointer;" onclick="switchToSection('financial');return false;" title="Click to manage Fees">
    <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
    <div class="kpi-value">UGX <?= number_format($outstanding) ?></div>
    <div class="kpi-label">Outstanding Fees</div>
    <?php if($outstanding > 0): ?><span class="kpi-trend" style="background:#fef2f2;color:#dc2626;"><i class="fas fa-exclamation-circle"></i> Pending</span><?php endif; ?>
  </div>
  <div class="kpi-card kpi-or an-fade" style="animation-delay:0.25s;cursor:pointer;" onclick="switchToSection('services');return false;" title="Click to review Applications">
    <div class="kpi-icon"><i class="fas fa-file-alt"></i></div>
    <div class="kpi-value"><?= number_format($total_applications) ?></div>
    <div class="kpi-label">Applications</div>
    <span class="kpi-trend" style="background:#fffbeb;color:#d97706;"><i class="fas fa-clock"></i> Received</span>
  </div>
  <div class="kpi-card kpi-pr an-fade" style="animation-delay:0.3s;cursor:pointer;" onclick="switchToSection('approvals');return false;" title="Click to approve">
    <div class="kpi-icon"><i class="fas fa-hourglass-half"></i></div>
    <div class="kpi-value"><?= number_format($pending_apps) ?></div>
    <div class="kpi-label">Pending Review</div>
    <span class="kpi-trend" style="background:#f5f3ff;color:#7c3aed;"><i class="fas fa-spinner"></i> Awaiting</span>
  </div>
</div>

<!-- ═══ ANALYTICS STRIP ═══ -->
<div class="analytics-strip an-fade" style="animation-delay:0.35s" data-ax='<?= json_encode(['months'=>$mn,'rev'=>$rv,'exp'=>$ex,'methods'=>['l'=>$ml,'v'=>$mv],'attendance'=>$staffAttendanceToday,'collRate'=>$collRate]) ?>'>
  <div class="ax">
    <div class="ax-title"><i class="fas fa-chart-line me-1" style="color:#3b82f6;"></i>Revenue vs Expenses</div>
    <canvas id="chartRevenue" height="80"></canvas>
  </div>
  <div class="ax">
    <div class="ax-title"><i class="fas fa-chart-pie me-1" style="color:#8b5cf6;"></i>Payment Methods</div>
    <canvas id="chartPaymentMethods" height="80"></canvas>
  </div>
  <div class="ax">
    <div class="ax-title"><i class="fas fa-user-clock me-1" style="color:#10b981;"></i>Staff Attendance</div>
    <canvas id="chartStaffAttendance" height="80"></canvas>
  </div>
  <div class="ax">
    <div class="ax-title"><i class="fas fa-heartbeat me-1" style="color:#ef4444;"></i>Health Score</div>
    <div id="performanceGauge" style="height:80px;"></div>
  </div>
  <div class="ax">
    <div class="ax-title"><i class="fas fa-robot me-1" style="color:#f59e0b;"></i>AI Insights</div>
    <div id="aiInsightsPanel" style="font-size:10px;min-height:60px;line-height:1.4;"><span class="text-muted">Analyzing...</span></div>
    <div id="aiPredictionPanel" style="font-size:10px;margin-top:4px;"></div>
  </div>
</div>

<!-- ═══ SECTION: SERVICES (Pending Submissions) ═══ -->
<div id="services" class="content-section dashboard-section<?= $dgSection === 'services' ? ' active' : '' ?>" data-section="services">
  <div class="section-card">
    <?php dgToolbar('Pending Submissions', 'fa-inbox', $totalPending . ' New', 'bg-danger'); ?>
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-inbox" style="color:#dc2626;"></i>Pending Submissions</h3>
        <p class="section-subtitle">Items requiring your attention from website &amp; applications</p>
      </div>
    </div>
    <?php if($totalPending > 0): ?>
    <div class="row g-2 mb-3">
      <div class="col-3"><div class="stat-block" style="background:linear-gradient(135deg,#fef2f2,#fee2e2);"><div class="stat-val" style="color:#991b1b"><?= $pendingContacts ?></div><div class="stat-lbl" style="color:#7f1d1d">Messages</div></div></div>
      <div class="col-3"><div class="stat-block" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);"><div class="stat-val" style="color:#92400e"><?= $pendingVolunteers ?></div><div class="stat-lbl" style="color:#78350f">Volunteers</div></div></div>
      <div class="col-3"><div class="stat-block" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);"><div class="stat-val" style="color:#1e40af"><?= $pendingDonations ?></div><div class="stat-lbl" style="color:#1e3a8a">Donations</div></div></div>
      <div class="col-3"><div class="stat-block" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);"><div class="stat-val" style="color:#166534"><?= $pendingApplications ?></div><div class="stat-lbl" style="color:#14532d">Applications</div></div></div>
    </div>
    <?php if(!empty($recentSubmissions)): ?>
    <div class="submission-list"><?php
      $icons = ['contact'=>'fa-envelope','volunteer'=>'fa-hands-helping','donation'=>'fa-hand-holding-heart','application'=>'fa-file-alt'];
      $colors = ['contact'=>'#dc2626','volunteer'=>'#d97706','donation'=>'#2563eb','application'=>'#16a34a'];
      $labels = ['contact'=>'Message','volunteer'=>'Volunteer','donation'=>'Donation','application'=>'Application'];
      foreach($recentSubmissions as $sub): $t=$sub['type']; ?>
      <div class="submission-item">
        <div class="submission-avatar" style="background:<?= $colors[$t] ?>15;color:<?= $colors[$t] ?>"><i class="fas <?= $icons[$t] ?>"></i></div>
        <div class="submission-info">
          <div class="submission-name"><?= htmlspecialchars($sub['name']) ?></div>
          <div class="submission-detail"><?= htmlspecialchars($sub['title']) ?> <span class="badge badge-soft bg-light text-dark"><?= $labels[$t] ?></span></div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <form method="POST" style="display:inline;" onsubmit="return confirm('Resolve this submission?')">
            <input type="hidden" name="dg_action" value="resolve_alert">
            <input type="hidden" name="sub_type" value="<?= htmlspecialchars($t) ?>">
            <input type="hidden" name="sub_id" value="<?= $sub['id'] ?? 0 ?>">
            <button class="btn btn-sm" style="color:#059669;border:none;background:none;padding:0 4px;" title="Mark resolved"><i class="fas fa-check-circle"></i></button>
          </form>
          <span class="submission-time"><?= date('d M H:i',strtotime($sub['created_at'])) ?></span>
        </div>
      </div>
    <?php endforeach; ?></div><?php endif; ?>
    <?php else: ?>
    <div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2" style="color:#10b981;"></i><p>All caught up! No pending submissions.</p></div>
    <?php endif; ?>
  </div>
</div>

<!-- ═══ SECTION TABS ═══ -->
<div class="section-tabs mb-3 no-print d-flex flex-wrap gap-1" style="background:#fff;border-radius:10px;padding:6px 8px;box-shadow:0 1px 4px rgba(0,0,0,0.06);">
  <a data-no-loader class="section-tab<?= $dgSection === 'executive' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#executive" onclick="switchToSection('executive');return false;" data-tab="executive"><i class="fas fa-chart-simple me-1"></i>Executive</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'departments' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#departments" onclick="switchToSection('departments');return false;" data-tab="departments"><i class="fas fa-building me-1"></i>Departments</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'performance' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#performance" onclick="switchToSection('performance');return false;" data-tab="performance"><i class="fas fa-chart-bar me-1"></i>Performance</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'financial' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#financial" onclick="switchToSection('financial');return false;" data-tab="financial"><i class="fas fa-coins me-1"></i>Financial</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'staff' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#staff" onclick="switchToSection('staff');return false;" data-tab="staff"><i class="fas fa-users me-1"></i>Staff</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'student' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#student" onclick="switchToSection('student');return false;" data-tab="student"><i class="fas fa-user-graduate me-1"></i>Students</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'services' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#services" onclick="switchToSection('services');return false;" data-tab="services"><i class="fas fa-inbox me-1"></i>Submissions</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'approvals' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#approvals" onclick="switchToSection('approvals');return false;" data-tab="approvals"><i class="fas fa-check-double me-1"></i>Approvals</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'store' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#store" onclick="switchToSection('store');return false;" data-tab="store"><i class="fas fa-warehouse me-1"></i>Store</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'communications' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#communications" onclick="switchToSection('communications');return false;" data-tab="communications"><i class="fas fa-bullhorn me-1"></i>Comms</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'audit' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#audit" onclick="switchToSection('audit');return false;" data-tab="audit"><i class="fas fa-history me-1"></i>Audit</a>
  <a data-no-loader class="section-tab<?= $dgSection === 'quick' ? ' active' : '' ?>" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;" href="#quick" onclick="switchToSection('quick');return false;" data-tab="quick"><i class="fas fa-bolt me-1"></i>Quick</a>
</div>

<!-- ═══ SECTION: EXECUTIVE ═══ -->
<div id="executive" class="content-section dashboard-section<?= $dgSection === 'executive' ? ' active' : '' ?>" data-section="executive">
  <div class="section-card">
    <?php dgToolbar('Executive Overview', 'fa-chart-simple', 'Updated live'); ?>
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-chart-simple" style="color:#3b82f6;"></i>Executive Overview</h3>
        <p class="section-subtitle">Real-time institutional snapshot</p>
      </div>
      <span class="badge badge-soft bg-primary">Updated live</span>
    </div>
    <?= renderExecutiveOverview($studentsConn, $conn) ?>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-lg-4"><div class="section-card h-100"><h3 class="section-title" style="font-size:14px;margin-bottom:14px;"><i class="fas fa-sitemap" style="color:#0891b2;"></i>Institutional Hierarchy</h3><?php echo renderHierarchyChart($conn); ?></div></div>
    <div class="col-lg-4"><div class="section-card h-100"><div class="section-header" style="margin-bottom:12px;"><h3 class="section-title" style="font-size:14px;"><i class="fas fa-bell" style="color:#dc2626;"></i>Active Alerts</h3><?php $ac=getAlertCounts($conn); if($ac['critical']>0): ?><span class="badge bg-danger rounded-pill"><?= $ac['critical'] ?> Critical</span><?php endif; if($ac['high']>0): ?><span class="badge bg-warning text-dark rounded-pill ms-1"><?= $ac['high'] ?> High</span><?php endif; ?></div><?= renderAlertsPanel($conn,null,5) ?></div></div>
    <div class="col-lg-4"><div class="section-card h-100"><h3 class="section-title" style="font-size:14px;margin-bottom:14px;"><i class="fas fa-shield-alt" style="color:#059669;"></i>Compliance &amp; Risk</h3><div class="mb-3"><div class="fw-semibold small mb-2" style="color:#64748b;">Compliance Status</div><?= renderComplianceSummary($conn) ?></div><div><div class="fw-semibold small mb-2" style="color:#64748b;">Top Risks</div><?= renderRiskRegister($conn,4) ?></div></div></div>
  </div>
</div>

<!-- ═══ SECTION: DEPARTMENTS ═══ -->
<div id="departments" class="content-section dashboard-section<?= $dgSection === 'departments' ? ' active' : '' ?>" data-section="departments">
  <div class="section-card">
    <?php dgToolbar('Department Monitoring', 'fa-building'); ?>
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-building" style="color:#d97706;"></i>Department Performance</h3>
        <p class="section-subtitle">Status, problems, trends &amp; responsible directors</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;" data-bs-toggle="modal" data-bs-target="#addDeptModal"><i class="fas fa-plus me-1"></i>Add Department</button>
        <a href="../dashboards/hr-manager.php" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-cog me-1"></i>Manage</a>
      </div>
    </div>
    <?= renderDepartmentComparison($conn) ?>
    <?php if(!empty($dept_list)): ?>
    <hr><h4 class="section-title" style="font-size:13px;margin-bottom:10px;"><i class="fas fa-list" style="color:#64748b;"></i>All Departments</h4>
    <div class="row g-2">
      <?php foreach($dept_list as $d): ?>
      <div class="col-md-4 col-6">
        <div class="p-2 rounded d-flex justify-content-between align-items-center" style="background:#f8fafc;border:1px solid #f1f5f9;">
          <div>
            <div class="fw-bold small"><?= htmlspecialchars($d['department_name']) ?></div>
            <small style="color:#94a3b8;"><?= htmlspecialchars($d['department_code']??'') ?> &middot; <?= htmlspecialchars($d['department_level']??'') ?></small>
          </div>
          <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this department?')">
            <input type="hidden" name="dg_action" value="delete_department">
            <input type="hidden" name="dept_id" value="<?= $d['id'] ?? 0 ?>">
            <input type="hidden" name="dept_code" value="<?= htmlspecialchars($d['department_code']??'') ?>">
            <button class="btn btn-sm" style="color:#dc2626;border:none;background:none;padding:2px 6px;" title="Delete"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ═══ SECTION: PERFORMANCE ═══ -->
<div id="performance" class="content-section dashboard-section<?= $dgSection === 'performance' ? ' active' : '' ?>" data-section="performance">
  <div class="section-card">
    <?php dgToolbar('Director Performance', 'fa-chart-bar'); ?>
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-chart-bar" style="color:#059669;"></i>Director Performance Monitoring</h3>
        <p class="section-subtitle">Dept targets, completed/pending/delayed tasks</p>
      </div>
    </div>
    <div class="row g-3">
      <?php
      $dirRoles = [1,3,4,5,6,27];
      foreach($dirRoles as $rid):
        $rq=$conn?$conn->prepare("SELECT id,role_name FROM igangaschoolofl_staffs_db.staff_roles WHERE id=?"):false;
        $rn=''; $si=0;
        if($rq){$rq->bind_param('i',$rid);$rq->execute();$rr=$rq->get_result()->fetch_assoc();$rq->close();if($rr)$rn=$rr['role_name'];}
        if($rn):
          $sq=$conn->prepare("SELECT id FROM staff WHERE role_id=? AND status='Active' LIMIT 1");
          if($sq){$sq->bind_param('i',$rid);$sq->execute();$sr=$sq->get_result()->fetch_assoc();$sq->close();if($sr)$si=$sr['id'];}
      ?>
      <div class="col-md-4 col-lg-3"><?= renderDirectorPerformanceCard($si,$rid,$rn,$conn) ?></div>
      <?php endif; endforeach; ?>
    </div>
  </div>
</div>

<!-- ═══ SECTION: FINANCIAL ═══ -->
<div id="financial" class="content-section dashboard-section<?= $dgSection === 'financial' ? ' active' : '' ?>" data-section="financial">
  <div class="section-card">
    <?php dgToolbar('Financial Overview', 'fa-coins'); ?>
    <div class="row g-3">
    <div class="col-lg-7">
      <div class="section-card h-100">
        <h3 class="section-title" style="cursor:pointer;margin-bottom:16px;" data-bs-toggle="collapse" data-bs-target="#financialOverviewContent" aria-expanded="false">
          <i class="fas fa-coins" style="color:#059669;"></i>Financial Overview
          <i class="fas fa-chevron-down ms-auto quick-chevron" style="font-size:14px;color:#94a3b8;"></i>
        </h3>
        <div id="financialOverviewContent" class="collapse">
          <div class="row g-2">
            <div class="col-4 col-md-3"><div class="stat-block" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);"><div class="stat-val" style="color:#166534">UGX <?= number_format($today_collection) ?></div><div class="stat-lbl" style="color:#14532d">Today</div></div></div>
            <div class="col-4 col-md-3"><div class="stat-block" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);"><div class="stat-val" style="color:#854d0e">UGX <?= number_format($week_collection) ?></div><div class="stat-lbl" style="color:#713f12">This Week</div></div></div>
            <div class="col-4 col-md-3"><div class="stat-block" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);"><div class="stat-val" style="color:#1e40af">UGX <?= number_format($month_collection) ?></div><div class="stat-lbl" style="color:#1e3a8a">This Month</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-block" style="background:linear-gradient(135deg,#fef2f2,#fee2e2);"><div class="stat-val" style="color:#991b1b">UGX <?= number_format($outstanding) ?></div><div class="stat-lbl" style="color:#7f1d1d">Outstanding</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-block" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);"><div class="stat-val" style="color:#0f172a">UGX <?= number_format($total_revenue) ?></div><div class="stat-lbl" style="color:#475569">Total Revenue</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-block" style="background:linear-gradient(135deg,#fff7ed,#fed7aa);"><div class="stat-val" style="color:#9a3412">UGX <?= number_format($total_expenses) ?></div><div class="stat-lbl" style="color:#7c2d12">Total Expenses</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-block" style="background:linear-gradient(135deg,#f0fdf4,#bbf7d0);"><div class="stat-val" style="color:#166534">UGX <?= number_format($total_revenue-$total_expenses) ?></div><div class="stat-lbl" style="color:#14532d">Net Position</div></div></div>
            <div class="col-6 col-md-3"><div class="stat-block" style="background:linear-gradient(135deg,#faf5ff,#e9d5ff);"><div class="stat-val" style="color:#6b21a8">UGX <?= number_format($month_collection-$total_expenses) ?></div><div class="stat-lbl" style="color:#581c87">Monthly Balance</div></div></div>
          </div>
          <div class="mt-3 d-flex flex-wrap gap-2">
            <a href="../dashboards/school-bursar.php?section=record_payment" class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-plus-circle me-1"></i>Record Payment</a>
            <a href="../dashboards/school-bursar.php?section=expenses" class="btn btn-sm" style="background:#dc2626;color:#fff;border:none;border-radius:8px;"><i class="fas fa-minus-circle me-1"></i>Add Expense</a>
            <a href="../dashboards/director-finance.php" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-coins me-1"></i>Finance Dashboard</a>
            <a href="../dashboards/school-bursar.php" class="btn btn-sm" style="background:#0891b2;color:#fff;border:none;border-radius:8px;"><i class="fas fa-money-bill me-1"></i>Bursar Panel</a>
            <a href="../dashboards/budget-management.php" class="btn btn-sm" style="background:#d97706;color:#fff;border:none;border-radius:8px;"><i class="fas fa-chart-line me-1"></i>Budget</a>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="section-card h-100">
        <h3 class="section-title" style="margin-bottom:14px;"><i class="fas fa-list" style="color:#3b82f6;"></i>Recent Payments</h3>
        <?php if(empty($recent_payments)): ?>
        <p class="text-muted small" style="padding:20px;text-align:center;">No recent payments recorded.</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table dg-table">
            <thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($recent_payments as $p): $pc=in_array($p['status'],['verified','approved'])?'bg-success':'bg-warning text-dark'; ?>
            <tr>
              <td><strong style="font-size:12px;"><?= htmlspecialchars(($p['first_name']??'').' '.($p['last_name']??'')) ?></strong><br><code style="font-size:10px;"><?= htmlspecialchars($p['student_number']??'') ?></code></td>
              <td><strong>UGX <?= number_format($p['amount_received']??$p['amount_paid']??0) ?></strong></td>
              <td><?= htmlspecialchars($p['payment_method']??'-') ?></td>
              <td><span style="color:#64748b;font-size:12px;"><?= isset($p['payment_date'])?date('d M',strtotime($p['payment_date'])):'-' ?></span></td>
              <td><span class="badge badge-soft <?= $pc ?>"><?= htmlspecialchars($p['status']??'') ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div>

<!-- ═══ SECTION: STAFF ═══ -->
<div id="staff" class="content-section dashboard-section<?= $dgSection === 'staff' ? ' active' : '' ?>" data-section="staff">
  <div class="section-card">
    <?php dgToolbar('Staff Management', 'fa-id-badge'); ?>
    <div class="row g-3">
      <div class="col-lg-5">
        <div class="section-card h-100">
        <h3 class="section-title" style="margin-bottom:14px;"><i class="fas fa-clipboard-list" style="color:#2563eb;"></i>Employee Daily Analysis</h3>
        <div class="row g-2 mb-3">
          <div class="col-3"><div class="stat-block" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);"><div class="stat-val" style="color:#166534"><?= $staffAttendanceToday['present'] ?></div><div class="stat-lbl" style="color:#14532d">Present</div></div></div>
          <div class="col-3"><div class="stat-block" style="background:linear-gradient(135deg,#fef9c3,#fde68a);"><div class="stat-val" style="color:#854d0e"><?= $staffAttendanceToday['late'] ?></div><div class="stat-lbl" style="color:#713f12">Late</div></div></div>
          <div class="col-3"><div class="stat-block" style="background:linear-gradient(135deg,#fee2e2,#fecaca);"><div class="stat-val" style="color:#991b1b"><?= $staffAttendanceToday['absent'] ?></div><div class="stat-lbl" style="color:#7f1d1d">Absent</div></div></div>
          <div class="col-3"><div class="stat-block" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);"><div class="stat-val" style="color:#1e40af"><?= $staffAttendanceToday['on_leave'] ?></div><div class="stat-lbl" style="color:#1e3a8a">On Leave</div></div></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <a href="../dashboards/staff-attendance.php" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-clock me-1"></i>Full Report</a>
          <a href="../dashboards/hr-manager.php" class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-users me-1"></i>HR Dashboard</a>
          <a href="../dashboards/staff-directory.php" class="btn btn-sm" style="background:#0891b2;color:#fff;border:none;border-radius:8px;"><i class="fas fa-address-book me-1"></i>Staff Directory</a>
        </div>
        <?php if(!empty($dept_list)): ?>
        <h3 class="section-title" style="font-size:14px;margin-bottom:10px;"><i class="fas fa-building" style="color:#d97706;"></i>Departments</h3>
        <div class="row g-2">
          <?php foreach($dept_list as $d): ?>
          <div class="col-md-6 col-6"><div class="p-2 rounded" style="background:#f8fafc;border:1px solid #f1f5f9;"><div class="fw-bold small"><?= htmlspecialchars($d['department_name']) ?></div><small style="color:#94a3b8;"><?= htmlspecialchars($d['department_code']??'') ?> &middot; <?= htmlspecialchars($d['department_level']??'') ?></small></div></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="section-card">
        <div class="section-header">
          <h3 class="section-title" style="margin-bottom:0;"><i class="fas fa-id-badge" style="color:#3b82f6;"></i>All Staff (<?= count($staff_list) ?>+)</h3>
          <div class="d-flex gap-2">
            <button class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;" data-bs-toggle="modal" data-bs-target="#addStaffModal"><i class="fas fa-plus me-1"></i>Add Staff</button>
            <a href="../dashboards/hr-manager.php" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;">HR Dashboard</a>
          </div>
        </div>
        <div class="table-scroll">
          <table class="table dg-table">
            <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Department</th><th>Email</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php if(empty($staff_list)): ?><tr><td colspan="7" class="text-center text-muted py-3">No staff records found.</td></tr>
            <?php else: foreach($staff_list as $s): $bc=$s['status']==='Active'?'bg-success text-white':($s['status']==='On Leave'?'bg-warning text-dark':'bg-danger text-white'); ?>
            <tr>
              <td><code style="font-size:11px;"><?= htmlspecialchars($s['staff_id']) ?></code></td>
              <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
              <td><?= htmlspecialchars($s['role_name']??$s['position']) ?></td>
              <td><?= htmlspecialchars($s['department']??'-') ?></td>
              <td><span style="font-size:12px;color:#64748b;"><?= htmlspecialchars($s['email']) ?></span></td>
              <td><span class="badge badge-soft <?= $bc ?>"><?= htmlspecialchars($s['status']) ?></span></td>
              <td>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove <?= htmlspecialchars($s['full_name'],ENT_QUOTES) ?> from staff?')">
                  <input type="hidden" name="dg_action" value="delete_staff">
                  <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                  <button class="btn btn-sm" style="color:#dc2626;border:none;background:none;padding:0 4px;" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="section-card mt-3">
        <h3 class="section-title" style="margin-bottom:14px;"><i class="fas fa-history" style="color:#64748b;"></i>Recent System Activities</h3>
        <?php if(empty($recent_activities)): ?>
        <p class="text-muted small" style="padding:20px;text-align:center;">No recent activities recorded.</p>
        <?php else: ?>
        <ul class="activity-timeline">
          <?php foreach($recent_activities as $act): ?>
          <li>
            <span class="activity-badge bg-primary text-white"><?= htmlspecialchars($act['activity_type']) ?></span>
            <div>
              <div style="font-size:13px;"><?= htmlspecialchars($act['activity_description']??'') ?></div>
              <small style="color:#94a3b8;"><?= $act['created_at']?date('d M Y H:i',strtotime($act['created_at'])):'' ?></small>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div>

<!-- ═══ SECTION: STUDENT ═══ -->
<div id="student" class="content-section dashboard-section<?= $dgSection === 'student' ? ' active' : '' ?>" data-section="student">
  <div class="section-card">
    <?php dgToolbar('Student Management', 'fa-user-graduate'); ?>
    <div class="section-header">
      <div>
        <h3 class="section-title" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#studentManagementContent" aria-expanded="false">
          <i class="fas fa-user-graduate" style="color:#2563eb;"></i>Student Management
          <i class="fas fa-chevron-down ms-1 quick-chevron" style="font-size:12px;color:#94a3b8;"></i>
        </h3>
      </div>
      <button class="btn" style="background:#2563eb;color:#fff;border:none;border-radius:8px;" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-plus me-1"></i>Add New</button>
    </div>
    <div id="studentManagementContent" class="mt-2">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
        <div class="flex-grow-1 me-2" style="max-width:400px;"><?= displayStudentSearchBox('Search by name, ID, phone...', 'dg_search') ?></div>
        <button class="btn" style="background:#2563eb;color:#fff;border:none;border-radius:8px;white-space:nowrap;" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-plus me-1"></i>Add New Student</button>
      </div>
      <div class="alert alert-info py-2 mb-3"><i class="fas fa-info-circle me-1"></i> Use the search box above or the <strong>Student Set Viewer</strong> below to find student records.</div>
      <div class="mt-2"><?php renderStudentSetViewer($studentsConn,['title'=>'All Student Records','icon'=>'fa-users-gear','super_admin'=>true,'show_all'=>false]); ?></div>
<?php
// Student performance prediction data
$perfData = ['labels'=>[],'actual'=>[],'predicted'=>[],'courses'=>[]];
if ($conn) {
    $pr = $conn->query("SELECT c.course_name, AVG(e.score) avg_score, COUNT(e.id) total FROM examination_records e JOIN academic_course_catalog c ON e.course_id=c.id WHERE e.score IS NOT NULL GROUP BY e.course_id ORDER BY avg_score DESC LIMIT 8");
    if ($pr) {
        $allCourses = []; $scores = [];
        while ($row = $pr->fetch_assoc()) {
            $perfData['courses'][] = htmlspecialchars($row['course_name']);
            $perfData['actual'][] = round((float)$row['avg_score'], 1);
            $pred = min(100, round((float)$row['avg_score'] * 1.08, 1)); // simple prediction
            $perfData['predicted'][] = $pred;
            $perfData['labels'][] = substr(htmlspecialchars($row['course_name']), 0, 12);
        }
    }
}
?>
<div class="row g-3 mt-3">
  <div class="col-lg-7">
    <div class="section-card">
      <h5 class="section-title" style="font-size:13px;margin-bottom:12px;"><i class="fas fa-chart-line" style="color:#8b5cf6;"></i>Student Performance Prediction</h5>
      <canvas id="perfPredictionChart" height="140"></canvas>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="section-card">
      <h5 class="section-title" style="font-size:13px;margin-bottom:12px;"><i class="fas fa-robot" style="color:#f59e0b;"></i>AI Performance Insights</h5>
      <div id="aiPerfInsights" style="font-size:12px;line-height:1.6;min-height:100px;">
        <?php if (!empty($perfData['courses'])): 
          $best = $perfData['actual'][0]; $worst = end($perfData['actual']);
          $avg = round(array_sum($perfData['actual'])/count($perfData['actual']), 1);
        ?>
        <div class="mb-2 p-2 rounded" style="background:#f0fdf4;"><strong style="color:#166534;">✓ Avg Score:</strong> <span class="float-end fw-bold"><?= $avg ?>%</span></div>
        <div class="mb-2 p-2 rounded" style="background:#fef2f2;"><strong style="color:#dc2626;">⚠ Needs Focus:</strong> <span class="float-end"><?= $perfData['courses'][array_key_last($perfData['courses'])] ?? 'N/A' ?> (<?= $worst ?>%)</span></div>
        <div class="mb-2 p-2 rounded" style="background:#eff6ff;"><strong style="color:#2563eb;">★ Top Performer:</strong> <span class="float-end"><?= $perfData['courses'][0] ?? 'N/A' ?> (<?= $best ?>%)</span></div>
        <div class="p-2 rounded" style="background:#fffbeb;"><strong style="color:#d97706;">📈 Predicted Improvement:</strong> <span class="float-end fw-bold"><?= round(array_sum($perfData['predicted'])/count($perfData['predicted']) - $avg, 1) ?>%</span></div>
        <?php else: ?>
        <div class="text-muted text-center py-4"><i class="fas fa-database fa-2x mb-2"></i><p>No exam data available yet.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var perfCanvas = document.getElementById('perfPredictionChart');
  if (!perfCanvas) return;
  var perfData = <?= json_encode($perfData) ?>;
  if (!perfData.labels || perfData.labels.length === 0) { perfCanvas.parentElement.innerHTML = '<div class="text-muted text-center py-4"><i class="fas fa-chart-line fa-2x mb-2"></i><p>No performance data to chart.</p></div>'; return; }
  new Chart(perfCanvas, {
    type: 'bar',
    data: {
      labels: perfData.courses,
      datasets: [
        { label: 'Current Avg Score', data: perfData.actual, backgroundColor: 'rgba(59,130,246,0.7)', borderColor: '#3b82f6', borderWidth: 1, borderRadius: 4 },
        { label: 'Predicted Score', data: perfData.predicted, backgroundColor: 'rgba(139,92,246,0.4)', borderColor: '#8b5cf6', borderWidth: 1, borderRadius: 4, borderDash: [4,2] }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 8, font: { size: 10 } } } },
      scales: {
        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 9 }, callback: function(v){return v+'%';} } },
        x: { grid: { display: false }, ticks: { font: { size: 8 } } }
      }
    }
  });
});
</script>
    </div>
  </div>
</div>

<!-- ═══ SECTION: APPROVALS (COMPREHENSIVE HUB) ═══ -->
<div id="approvals" class="content-section dashboard-section<?= $dgSection === 'approvals' ? ' active' : '' ?>" data-section="approvals">
  <div class="section-card">
    <?php dgToolbar('Approval Center', 'fa-check-double'); ?>
    <div class="section-header">
      <div>
        <h3 class="section-title"><i class="fas fa-check-double" style="color:#3b82f6;"></i>Approval Center</h3>
        <p class="section-subtitle">Director General — final approval authority for all institution requests</p>
      </div>
      <div class="d-flex gap-2">
        <span class="badge bg-warning text-dark rounded-pill px-3 py-1" style="font-size:11px;"><i class="fas fa-clock me-1"></i><?php $ac=getAlertCounts($conn); echo ($ac['critical']+$ac['high']); ?> Urgent</span>
      </div>
    </div>
    <?= renderApprovalTabs($conn, $studentsConn) ?>
  </div>
</div>

<!-- ═══ SECTION: AUDIT ═══ -->
<div id="audit" class="content-section dashboard-section<?= $dgSection === 'audit' ? ' active' : '' ?>" data-section="audit">
  <div class="section-card">
    <?php dgToolbar('Audit Trail', 'fa-history'); ?>
    <div class="row g-3">
      <div class="col-lg-6"><div class="section-card h-100">
        <div class="section-header" style="margin-bottom:12px;">
          <h3 class="section-title" style="font-size:14px;margin-bottom:0;"><i class="fas fa-bell" style="color:#dc2626;"></i>Active Alerts</h3>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Mark all alerts as resolved?')">
            <input type="hidden" name="dg_action" value="resolve_alert">
            <input type="hidden" name="sub_type" value="all_alerts">
            <input type="hidden" name="sub_id" value="0">
            <button class="btn btn-sm" style="color:#64748b;border:1px solid #e2e8f0;background:#fff;border-radius:8px;"><i class="fas fa-check-double me-1"></i>Resolve All</button>
          </form>
        </div>
        <?= renderAlertsPanel($conn,null,8) ?>
      </div></div>
      <div class="col-lg-6"><div class="section-card h-100">
        <div class="section-header" style="margin-bottom:12px;">
          <h3 class="section-title" style="font-size:14px;margin-bottom:0;"><i class="fas fa-history" style="color:#64748b;"></i>Recent Audit Trail</h3>
          <div class="d-flex gap-2">
            <button class="btn btn-sm" style="color:#64748b;border:1px solid #e2e8f0;background:#fff;border-radius:8px;" onclick="dgExportCSV()"><i class="fas fa-download me-1"></i>Export</button>
            <span class="badge badge-soft bg-secondary">Latest actions</span>
          </div>
        </div>
        <?= renderAuditTrailTable($conn,[],8) ?>
      </div></div>
    </div>
  </div>
</div>

<!-- ═══ SECTION: STORE & ASSETS ═══ -->
<div id="store" class="content-section dashboard-section<?= $dgSection === 'store' ? ' active' : '' ?>" data-section="store">
  <div class="section-card">
    <?php dgToolbar('Store & Assets', 'fa-warehouse'); ?>
    <div class="row g-3">
    <div class="col-lg-6">
      <div class="section-card h-100">
        <h3 class="section-title" style="margin-bottom:14px;"><i class="fas fa-shopping-cart" style="color:#d97706;"></i>Pending Store Requests</h3>
        <?php
        $storeReqs=[];
        if($conn){$sr=$conn->query("SELECT sr.request_number,sr.urgency,sr.status,sr.created_at,s.full_name as requester FROM store_requests sr LEFT JOIN staff s ON sr.requested_by=s.id WHERE sr.status IN('pending','forwarded','pending_approval') ORDER BY FIELD(sr.status,'pending_approval','pending','forwarded'), FIELD(sr.urgency,'urgent','high','medium','low'),sr.created_at ASC LIMIT 5");if($sr)while($row=$sr->fetch_assoc())$storeReqs[]=$row;}
        if(empty($storeReqs)): ?><p class="text-muted small" style="padding:20px;text-align:center;">No pending store requests.</p>
        <?php else: foreach($storeReqs as $sr_):
          $statusLabel = $sr_['status'] === 'pending_approval' ? '<span class="badge bg-warning text-dark ms-1" style="font-size:9px;">Awaiting You</span>' : '';
        ?>
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f1f5f9;">
          <div><code class="fw-bold" style="font-size:12px;"><?= htmlspecialchars($sr_['request_number']) ?></code><small style="color:#94a3b8;margin-left:8px;">by <?= htmlspecialchars($sr_['requester']??'') ?></small><?= $statusLabel ?></div>
          <div class="d-flex align-items-center gap-2">
            <?php if($sr_['status']==='pending_approval'): ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Approve request <?= htmlspecialchars($sr_['request_number'],ENT_QUOTES) ?>?')">
              <input type="hidden" name="dg_action" value="approve_submission">
              <input type="hidden" name="sub_type" value="store">
              <input type="hidden" name="sub_ref" value="<?= htmlspecialchars($sr_['request_number']) ?>">
              <button class="btn btn-sm" style="color:#059669;border:none;background:none;padding:0 4px;" title="Approve"><i class="fas fa-check"></i></button>
            </form>
            <?php endif; ?>
            <span class="badge badge-soft bg-<?= $sr_['urgency']==='urgent'?'danger':($sr_['urgency']==='high'?'warning text-dark':'info') ?>"><?= $sr_['urgency'] ?></span>
            <small style="color:#94a3b8;"><?= date('d M',strtotime($sr_['created_at'])) ?></small>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="text-center mt-2"><a href="../dashboards/storekeeper.php" class="btn btn-sm" style="background:#d97706;color:#fff;border:none;border-radius:8px;"><i class="fas fa-warehouse me-1"></i>Go to Store</a></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="section-card h-100">
        <h3 class="section-title" style="margin-bottom:14px;"><i class="fas fa-tasks" style="color:#3b82f6;"></i>Official Duties</h3>
        <?php renderOfficialDuties($user_role_id,$conn); ?>
      </div>
    </div>
  </div>
</div>
</div>

<!-- ═══ SECTION: COMMUNICATIONS ═══ -->
<div id="communications" class="content-section dashboard-section<?= $dgSection === 'communications' ? ' active' : '' ?>" data-section="communications">
  <div class="section-card">
    <?php dgToolbar('Communications', 'fa-bullhorn'); ?>
    <?php renderNewsWidget($conn,$websiteConn,$user_id,$user_name,$user_role,5); ?>
  </div>
</div>

<!-- ═══ SECTION: QUICK ACTIONS ═══ -->
<div id="quick" class="content-section dashboard-section<?= $dgSection === 'quick' ? ' active' : '' ?>" data-section="quick">
  <div class="section-card">
    <?php dgToolbar('Quick Actions', 'fa-bolt'); ?>
    <h3 class="section-title" style="cursor:pointer;margin-bottom:0;" data-bs-toggle="collapse" data-bs-target="#quickActionsContent" aria-expanded="false">
      <i class="fas fa-bolt" style="color:#f59e0b;"></i>Quick Actions
      <i class="fas fa-chevron-down ms-auto quick-chevron" style="font-size:14px;color:#94a3b8;"></i>
    </h3>
    <div id="quickActionsContent" class="collapse mt-3">
      <div class="mb-3">
        <span class="badge bg-primary px-3 py-1 mb-2" style="font-size:11px;border-radius:12px;">OPERATIONS</span>
        <div class="d-flex flex-wrap gap-2">
          <a href="../news.php" class="btn btn-sm" style="background:#1e293b;color:#fff;border:none;border-radius:8px;"><i class="fas fa-newspaper me-1"></i>Manage News</a>
          <button class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;" data-bs-toggle="modal" data-bs-target="#annModal"><i class="fas fa-bullhorn me-1"></i>Send Announcement</button>
          <a href="../dashboards/staff_transcript_generation.php" class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-file-alt me-1"></i>Transcripts</a>
          <a href="../dashboards/staff_receipt_printing.php" class="btn btn-sm" style="background:#0891b2;color:#fff;border:none;border-radius:8px;"><i class="fas fa-receipt me-1"></i>Receipts</a>
          <a href="../import_students_excel.php" class="btn btn-sm" style="background:#0891b2;color:#fff;border:none;border-radius:8px;"><i class="fas fa-file-excel me-1"></i>Import Students</a>
          <button class="btn btn-sm no-print" style="background:#64748b;color:#fff;border:none;border-radius:8px;" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
        </div>
      </div>
      <div class="mb-3">
        <span class="badge bg-warning text-dark px-3 py-1 mb-2" style="font-size:11px;border-radius:12px;">EXECUTIVE</span>
        <div class="d-flex flex-wrap gap-2">
          <a href="../dashboards/director-academics.php" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-graduation-cap me-1"></i>Academics</a>
          <a href="../dashboards/director-finance.php" class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-coins me-1"></i>Finance</a>
          <a href="../dashboards/director-admissions.php" class="btn btn-sm" style="background:#0891b2;color:#fff;border:none;border-radius:8px;"><i class="fas fa-file-contract me-1"></i>Admissions</a>
          <a href="../dashboards/director-ict.php" class="btn btn-sm" style="background:#64748b;color:#fff;border:none;border-radius:8px;"><i class="fas fa-laptop-code me-1"></i>ICT</a>
        </div>
      </div>
      <div class="mb-3">
        <span class="badge bg-info px-3 py-1 mb-2" style="font-size:11px;border-radius:12px;">ADMIN</span>
        <div class="d-flex flex-wrap gap-2">
          <a href="../dashboards/school-principal.php" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-chalkboard-teacher me-1"></i>Principal</a>
          <a href="../dashboards/deputy-principal.php" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-user-check me-1"></i>Deputy</a>
          <a href="../dashboards/academic-registrar.php" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-file-alt me-1"></i>Registrar</a>
          <a href="../dashboards/school-secretary.php" class="btn btn-sm" style="background:#0891b2;color:#fff;border:none;border-radius:8px;"><i class="fas fa-envelope me-1"></i>Secretary</a>
          <a href="../dashboards/hr-manager.php" class="btn btn-sm" style="background:#dc2626;color:#fff;border:none;border-radius:8px;"><i class="fas fa-users me-1"></i>HR</a>
          <a href="../dashboards/school-bursar.php" class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-money-bill me-1"></i>Bursar</a>
        </div>
      </div>
      <div>
        <span class="badge bg-success px-3 py-1 mb-2" style="font-size:11px;border-radius:12px;">ACADEMIC</span>
        <div class="d-flex flex-wrap gap-2">
          <a href="../dashboards/head-nursing.php" class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-heartbeat me-1"></i>Nursing</a>
          <a href="../dashboards/head-midwifery.php" class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-user-md me-1"></i>Midwifery</a>
          <a href="../dashboards/senior-lecturers.php" class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-user-graduate me-1"></i>Senior</a>
          <a href="../dashboards/lecturers.php" class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-chalkboard me-1"></i>Lecturers</a>
          <a href="../dashboards/school-librarian.php" class="btn btn-sm" style="background:#0891b2;color:#fff;border:none;border-radius:8px;"><i class="fas fa-book me-1"></i>Librarian</a>
          <a href="../dashboards/student-management.php" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-users-rectangle me-1"></i>Students</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  function bindChevron(cid) {
    var el = document.getElementById(cid);
    if (!el) return;
    var ch = document.querySelector('[data-bs-target="#' + cid + '"] .quick-chevron');
    if (!ch) return;
    el.addEventListener('show.bs.collapse', function(){ ch.classList.add('rotated'); });
    el.addEventListener('hide.bs.collapse', function(){ ch.classList.remove('rotated'); });
  }
  function initChevrons() {
    ['quickActionsContent','employeeAnalysisContent','financialOverviewContent','studentManagementContent','staffTableCollapse'].forEach(bindChevron);
  }
  if (document.readyState === 'complete') initChevrons();
  else document.addEventListener('DOMContentLoaded', initChevrons);
})();

// ── DG page helpers ──
function dgFilterTable(query) {
    var q = query.toLowerCase().trim();
    document.querySelectorAll('.dashboard-section.active .dg-table tbody tr').forEach(function(row) {
        row.style.display = !q || row.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
    });
}
function dgExportCSV() {
    var table = document.querySelector('.dashboard-section.active .dg-table');
    if (!table) return;
    var rows = [].slice.call(table.querySelectorAll('tr'));
    var csv = rows.map(function(row) {
        return [].slice.call(row.querySelectorAll('th,td')).map(function(cell) {
            return '"' + cell.textContent.trim().replace(/"/g, '""') + '"';
        }).join(',');
    }).join('\n');
    var blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'director-export.csv';
    link.click();
}
</script>
<?php if (function_exists('overrideApprovalActionHandler')) overrideApprovalActionHandler(); ?>

</div><!-- /dg-content -->

<!-- ═══ SEND ANNOUNCEMENT MODAL ═══ -->
<div class="modal fade modern-modal" id="annModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;">
        <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Send Announcement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Title *</label><input type="text" name="ann_title" class="form-control" required placeholder="e.g., Staff Meeting Tomorrow"></div>
        <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Message *</label><textarea name="ann_body" class="form-control" rows="4" required style="border-radius:8px;" placeholder="Write your announcement here…"></textarea></div>
        <div class="row g-3">
          <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Target</label><select name="ann_target" class="form-select" style="border-radius:8px;"><option value="All">All</option><option value="Nursing">Nursing</option><option value="Midwifery">Midwifery</option><option value="Staff">Staff</option></select></div>
          <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Priority</label><select name="ann_priority" class="form-select" style="border-radius:8px;"><option value="Normal">Normal</option><option value="High">High</option><option value="Urgent">Urgent</option></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" style="border-radius:8px;" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn" style="background:#d97706;color:#fff;border:none;border-radius:8px;"><i class="fas fa-paper-plane me-1"></i>Publish</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ ADD DEPARTMENT MODAL ═══ -->
<div class="modal fade modern-modal" id="addDeptModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="dg_action" value="add_department">
      <div class="modal-header" style="background:linear-gradient(135deg,#059669,#047857);color:#fff;">
        <h5 class="modal-title"><i class="fas fa-building me-2"></i>Add Department</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Department Name *</label><input type="text" name="dept_name" class="form-control" required style="border-radius:8px;" placeholder="e.g., Information Technology"></div>
        <div class="row g-3">
          <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Code *</label><input type="text" name="dept_code" class="form-control" required style="border-radius:8px;" placeholder="e.g., ICT"></div>
          <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Level</label><select name="dept_level" class="form-select" style="border-radius:8px;"><option value="">Select</option><option value="1">Level 1</option><option value="2">Level 2</option><option value="3">Level 3</option></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" style="border-radius:8px;" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn" style="background:#059669;color:#fff;border:none;border-radius:8px;"><i class="fas fa-save me-1"></i>Add Department</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ ADD STAFF MODAL ═══ -->
<div class="modal fade modern-modal" id="addStaffModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="dg_action" value="add_staff">
      <div class="modal-header" style="background:linear-gradient(135deg,#0891b2,#0e7490);color:#fff;">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add Staff Member</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Full Name *</label><input type="text" name="staff_name" class="form-control" required style="border-radius:8px;"></div>
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Staff ID *</label><input type="text" name="staff_id" class="form-control" required style="border-radius:8px;" placeholder="e.g., EMP001"></div>
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Email</label><input type="email" name="staff_email" class="form-control" style="border-radius:8px;"></div>
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Phone</label><input type="text" name="staff_phone" class="form-control" style="border-radius:8px;"></div>
          <div class="col-12"><label class="form-label fw-semibold" style="font-size:13px;">Department</label><select name="staff_dept" class="form-select" style="border-radius:8px;"><option value="">Select</option><?php foreach($dept_list as $dd): ?><option value="<?= htmlspecialchars($dd['department_code']) ?>"><?= htmlspecialchars($dd['department_name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Role</label><input type="text" name="staff_role" class="form-control" style="border-radius:8px;" placeholder="e.g., Lecturer"></div>
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Status</label><select name="staff_status" class="form-select" style="border-radius:8px;"><option value="Active">Active</option><option value="On Leave">On Leave</option><option value="Inactive">Inactive</option></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" style="border-radius:8px;" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn" style="background:#0891b2;color:#fff;border:none;border-radius:8px;"><i class="fas fa-save me-1"></i>Add Staff</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ ADD NEW STUDENT MODAL ═══ -->
<div class="modal fade modern-modal" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" id="addStudentForm">
      <div class="modal-header" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:13px;">First Name *</label><input type="text" name="first_name" class="form-control" required style="border-radius:8px;"></div>
          <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:13px;">Middle Name</label><input type="text" name="middle_name" class="form-control" style="border-radius:8px;"></div>
          <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:13px;">Last Name *</label><input type="text" name="last_name" class="form-control" required style="border-radius:8px;"></div>
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Student No. *</label><input type="text" name="student_id" class="form-control" required style="border-radius:8px;"></div>
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Program</label><select name="program" class="form-select" style="border-radius:8px;"><option value="Certificate Nursing">Certificate Nursing</option><option value="Certificate Midwifery">Certificate Midwifery</option><option value="Diploma Nursing">Diploma Nursing</option></select></div>
          <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:13px;">Level</label><input type="text" name="level" class="form-control" value="1" style="border-radius:8px;"></div>
          <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:13px;">Intake Year</label><input type="text" name="intake_year" class="form-control" value="<?php echo date('Y'); ?>" style="border-radius:8px;"></div>
          <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:13px;">Period</label><select name="intake_period" class="form-select" style="border-radius:8px;"><option value="January">January</option><option value="July">July</option></select></div>
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Phone</label><input type="text" name="phone" class="form-control" style="border-radius:8px;"></div>
          <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Email</label><input type="email" name="email" class="form-control" style="border-radius:8px;"></div>
          <div class="col-md-12"><label class="form-label fw-semibold" style="font-size:13px;">Date of Birth</label><input type="date" name="date_of_birth" class="form-control" style="border-radius:8px;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" style="border-radius:8px;" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-save me-1"></i>Save Student</button>
      </div>
    </form>
  </div>
</div>

<?php echo displayStudentProfileModal('student_profile_modal'); ?>

<script>
window.allStudents = <?= json_encode($loader->loadAllStudents() ?: []) ?>;
</script>
<script>
function viewFullProfile(id){ showStudentProfileModal(id); }
function editStudent(id){ window.location.href='../student_accounts_management.php?action=edit&student_id='+id; }
function viewAcademic(id){ window.location.href='../academic_records_management.php?student_id='+id; }
function viewFees(id){ window.location.href='../dashboards/school-bursar.php?section=record_payment&student_id='+id; }
function sendMessage(id){ alert('Messaging module for student ID: '+id); }
function printProfile(){ window.print(); }
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
