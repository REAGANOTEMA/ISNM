<?php
require_once __DIR__ . '/includes/staff_dashboard_access.php';
try {
    $ctx = bootstrapStaffDashboard(['computer lab', 'ict', 'it', 'lab technician', 'director ict']);
} catch (Throwable $e) {
    if (ob_get_level()) ob_clean();
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access Error</title></head><body>';
    echo '<h2>Access Error</h2><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="staff-login.php">Return to Login</a></p></body></html>';
    exit;
}
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
$website_conn = $ctx['website'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_name = $user['full_name'] ?? 'Computer Lab Manager';
$ict = null;
try { $ict = getICTConnection(); } catch (Exception $e) {}

function lab_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { error_log('root_computer_lab getCount: ' . $e->getMessage()); return 0; }
}
function lab_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return isnm_fetch_all($r); }
    catch (Exception $e) { error_log('root_computer_lab getList: ' . $e->getMessage()); return []; }
}
function lab_fetch_one($conn, $sql) {
    if (!$conn) return null;
    try { $r = $conn->query($sql); if (!$r) return null; return $r->fetch_assoc(); }
    catch (Exception $e) { error_log('root_computer_lab getDetail: ' . $e->getMessage()); return null; }
}

$total_computers = lab_q($ict, "SELECT COUNT(*) FROM lab_computers WHERE status != 'deleted'");
$computers_online = lab_q($ict, "SELECT COUNT(*) FROM lab_computers WHERE status='online'");
$computers_offline = lab_q($ict, "SELECT COUNT(*) FROM lab_computers WHERE status='offline'");
$computers_maintenance = lab_q($ict, "SELECT COUNT(*) FROM lab_computers WHERE status='maintenance'");
$active_sessions = lab_q($ict, "SELECT COUNT(*) FROM lab_bookings WHERE CURDATE() BETWEEN DATE(booking_date) AND DATE(booking_date) AND status='confirmed'");
$pending_bookings = lab_q($ict, "SELECT COUNT(*) FROM lab_bookings WHERE status='pending'");
$pending_tickets = lab_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status IN ('open','in_progress')");
$total_equipment = lab_q($ict, "SELECT COUNT(*) FROM lab_equipment WHERE status='active'");
$software_count = lab_q($ict, "SELECT COUNT(*) FROM software_inventory");
$printing_jobs_today = lab_q($ict, "SELECT COUNT(*) FROM lab_printing_jobs WHERE DATE(created_at)=CURDATE()");
$student_attendance_today = lab_q($ict, "SELECT COUNT(*) FROM lab_attendance WHERE DATE(created_at)=CURDATE() AND status='present'");

$recent_bookings = lab_fetch($ict, "SELECT id, booking_reference, course_name, instructor_name, booking_date, time_slot, number_of_students, status, created_at FROM lab_bookings ORDER BY created_at DESC LIMIT 8");
$pending_tickets_list = lab_fetch($ict, "SELECT id, ticket_number, requester_name, requester_type, issue_type, priority, description, status, created_at FROM it_support_tickets WHERE status IN ('open','in_progress') ORDER BY FIELD(priority,'critical','high','medium','low'), created_at ASC LIMIT 8");
$maintenance_computers = lab_fetch($ict, "SELECT computer_id, computer_name, location, status, last_maintenance, issues_reported FROM lab_computers WHERE status IN ('offline','maintenance') OR issues_reported IS NOT NULL ORDER BY status, computer_name LIMIT 6");
$computers = lab_fetch($ict, "SELECT * FROM lab_computers WHERE status != 'deleted' ORDER BY computer_name");
$equipment = lab_fetch($ict, "SELECT * FROM lab_equipment WHERE status='active' ORDER BY equipment_name");
$software_items = lab_fetch($ict, "SELECT * FROM software_inventory ORDER BY software_name");
$inventory_items = lab_fetch($ict, "SELECT * FROM lab_inventory_items ORDER BY item_name");
$printing_jobs = lab_fetch($ict, "SELECT * FROM lab_printing_jobs ORDER BY created_at DESC LIMIT 20");
$attendance_records = lab_fetch($ict, "SELECT * FROM lab_attendance ORDER BY created_at DESC LIMIT 20");
$id_card_requests = lab_fetch($ict, "SELECT * FROM lab_id_card_requests ORDER BY created_at DESC LIMIT 20");
$sessions = lab_fetch($ict, "SELECT * FROM lab_bookings ORDER BY booking_date DESC LIMIT 20");
$total_students = 0;
$students_list = [];
if ($students_conn) {
    $r = $students_conn->query("SELECT COUNT(*) as cnt FROM students WHERE status='Active'");
    if ($r) $total_students = (int)$r->fetch_assoc()['cnt'];
    $r = $students_conn->query("SELECT id, index_number, full_name, phone, email, program, gender, set_name, status FROM students WHERE status != 'deleted' ORDER BY full_name ASC LIMIT 200");
    if ($r) $students_list = isnm_fetch_all($r);
}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    $csrf = $_POST['csrf_token'] ?? '';
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        $_SESSION['error'] = 'Invalid security token. Please refresh and try again.';
        header('Location: computer_lab.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    // Student handlers (students DB â€” works without ICT DB)
    if ($action === 'add_student' && $students_conn) {
        $index = 'ISNM/' . date('Y') . '/' . str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $fn = trim($_POST['full_name'] ?? '');
        $parts = explode(' ', $fn, 2);
        $first = $parts[0];
        $surname = $parts[1] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $prog = $_POST['program'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $set = $_POST['set_name'] ?? date('Y');
        $dob = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $intakeYear = date('Y');
        $temp_password = bin2hex(random_bytes(4));
        $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
        $stmt = $students_conn->prepare("INSERT INTO students (index_number, first_name, surname, full_name, phone, email, program, gender, set_name, date_of_birth, intake_year, intake_period, status, password, is_first_login, password_changed, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, 0, 1, NOW())");
        if ($stmt) {
            $intakePeriod = date('n') <= 6 ? 'January' : 'July';
            $stmt->bind_param("sssssssssssss", $index, $first, $surname, $fn, $phone, $email, $prog, $gender, $set, $dob, $intakeYear, $intakePeriod, $password_hash);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Student $fn added successfully! Index: $index | Password: $temp_password — student can log in at student-login.php";
            } else {
                $_SESSION['error'] = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: computer_lab.php');
        exit;
    }

    if ($action === 'edit_student' && $students_conn) {
        $id = (int)$_POST['id'];
        $fn = trim($_POST['full_name'] ?? '');
        $parts = explode(' ', $fn, 2);
        $first = $parts[0];
        $surname = $parts[1] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $prog = $_POST['program'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $set = $_POST['set_name'] ?? '';
        $status = $_POST['status'] ?? 'Active';
        $idx = $_POST['index_number'] ?? '';
        $stmt = $students_conn->prepare("UPDATE students SET index_number=?, first_name=?, surname=?, full_name=?, phone=?, email=?, program=?, gender=?, set_name=?, status=?, updated_at=NOW() WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("ssssssssssi", $idx, $first, $surname, $fn, $phone, $email, $prog, $gender, $set, $status, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Student $fn updated.";
        header('Location: computer_lab.php?section=students');
        exit;
    }

    if ($action === 'delete_student' && $students_conn) {
        $id = (int)$_POST['id'];
        $stmt = $students_conn->prepare("UPDATE students SET status='deleted' WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Student removed.";
        header('Location: computer_lab.php?section=students');
        exit;
    }

    if ($ict) {
    if ($action === 'add_computer') {
        $cid = $_POST['computer_id'] ?? '';
        $name = $_POST['computer_name'] ?? '';
        $loc = $_POST['location'] ?? '';
        $ip = $_POST['ip_address'] ?? '';
        $mac = $_POST['mac_address'] ?? '';
        $specs = $_POST['specifications'] ?? '';
        $os = $_POST['os_installed'] ?? '';
        $stmt = $ict->prepare("INSERT IGNORE INTO lab_computers (computer_id, computer_name, location, status, ip_address, mac_address, specifications, os_installed) VALUES (?, ?, ?, 'online', ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssss", $cid, $name, $loc, $ip, $mac, $specs, $os);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Computer $cid added.";
        header('Location: computer_lab.php?section=computers');
        exit;
    }

    if ($action === 'edit_computer') {
        $id = (int)$_POST['id'];
        $name = $_POST['computer_name'] ?? '';
        $loc = $_POST['location'] ?? '';
        $ip = $_POST['ip_address'] ?? '';
        $mac = $_POST['mac_address'] ?? '';
        $specs = $_POST['specifications'] ?? '';
        $os = $_POST['os_installed'] ?? '';
        $status = $_POST['status'] ?? 'online';
        $stmt = $ict->prepare("UPDATE lab_computers SET computer_name=?, location=?, ip_address=?, mac_address=?, specifications=?, os_installed=?, status=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("sssssssi", $name, $loc, $ip, $mac, $specs, $os, $status, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Computer updated.";
        header('Location: computer_lab.php?section=computers');
        exit;
    }

    if ($action === 'delete_computer') {
        $id = (int)$_POST['id'];
        $stmt = $ict->prepare("UPDATE lab_computers SET status='deleted' WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Computer removed.";
        header('Location: computer_lab.php?section=computers');
        exit;
    }

    if ($action === 'create_ticket') {
        $tn = 'TKT-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $rn = $_POST['requester_name'] ?? '';
        $re = $_POST['requester_email'] ?? '';
        $rt = $_POST['requester_type'] ?? '';
        $it = $_POST['issue_type'] ?? '';
        $pr = $_POST['priority'] ?? '';
        $desc = $_POST['description'] ?? '';
        $stmt = $ict->prepare("INSERT INTO it_support_tickets (ticket_number, requester_name, requester_email, requester_type, issue_type, priority, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssss", $tn, $rn, $re, $rt, $it, $pr, $desc);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Ticket $tn created.";
        header('Location: computer_lab.php?section=support');
        exit;
    }

    if ($action === 'resolve_ticket') {
        $id = (int)$_POST['ticket_id'];
        $notes = $_POST['resolution_notes'] ?? '';
        $uname = $_SESSION['full_name'] ?? 'Lab Staff';
        $stmt = $ict->prepare("UPDATE it_support_tickets SET status='resolved', resolution_notes=CONCAT(resolution_notes,?), resolved_at=NOW() WHERE id=?");
        if ($stmt) {
            $noteVal = "\n[$uname] $notes";
            $stmt->bind_param("si", $noteVal, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Ticket #$id resolved.";
        header('Location: computer_lab.php?section=support');
        exit;
    }

    if ($action === 'add_booking') {
        $ref = 'BK-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $cn = $_POST['course_name'] ?? '';
        $in = $_POST['instructor_name'] ?? '';
        $bd = $_POST['booking_date'] ?? '';
        $ts = $_POST['time_slot'] ?? '';
        $ns = (int)($_POST['number_of_students'] ?? 0);
        $stmt = $ict->prepare("INSERT INTO lab_bookings (booking_reference, course_name, instructor_name, booking_date, time_slot, number_of_students, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        if ($stmt) {
            $stmt->bind_param("sssssi", $ref, $cn, $in, $bd, $ts, $ns);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Booking $ref created.";
        header('Location: computer_lab.php?section=sessions');
        exit;
    }

    if ($action === 'update_booking_status') {
        $id = (int)$_POST['id'];
        $st = $_POST['status'] ?? '';
        $stmt = $ict->prepare("UPDATE lab_bookings SET status=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("si", $st, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Booking updated.";
        header('Location: computer_lab.php?section=sessions');
        exit;
    }

    if ($action === 'add_equipment') {
        $en = $_POST['equipment_name'] ?? '';
        $ec = $_POST['equipment_code'] ?? '';
        $cat = $_POST['category'] ?? '';
        $qty = (int)($_POST['quantity'] ?? 0);
        $loc = $_POST['location'] ?? '';
        $stmt = $ict->prepare("INSERT INTO lab_equipment (equipment_name, equipment_code, category, quantity, location, status) VALUES (?, ?, ?, ?, ?, 'active')");
        if ($stmt) {
            $stmt->bind_param("sssii", $en, $ec, $cat, $qty, $loc);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Equipment added.";
        header('Location: computer_lab.php?section=equipment');
        exit;
    }

    if ($action === 'delete_equipment') {
        $id = (int)$_POST['id'];
        $stmt = $ict->prepare("UPDATE lab_equipment SET status='inactive' WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Equipment removed.";
        header('Location: computer_lab.php?section=equipment');
        exit;
    }

    if ($action === 'create_print_job') {
        $pn = $_POST['patient_name'] ?? $_POST['document_name'] ?? 'Print Job';
        $pt = $_POST['print_type'] ?? '';
        $pc = (int)($_POST['page_count'] ?? 0);
        $cc = (int)($_POST['copy_count'] ?: 1);
        $un = $_POST['user_name'] ?? $_SESSION['full_name'] ?? '';
        $stmt = $ict->prepare("INSERT INTO lab_printing_jobs (job_name, print_type, page_count, copy_count, user_name, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        if ($stmt) {
            $stmt->bind_param("ssisi", $pn, $pt, $pc, $cc, $un);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Print job submitted.";
        header('Location: computer_lab.php?section=printing');
        exit;
    }

    if ($action === 'update_print_status') {
        $id = (int)$_POST['id'];
        $st = $_POST['status'] ?? '';
        $stmt = $ict->prepare("UPDATE lab_printing_jobs SET status=?, completed_at=NOW() WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("si", $st, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Print job updated.";
        header('Location: computer_lab.php?section=printing');
        exit;
    }

    if ($action === 'add_software') {
        $sn = $_POST['software_name'] ?? '';
        $sv = $_POST['version'] ?? '';
        $lic = $_POST['license_type'] ?? '';
        $exp = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $stmt = $ict->prepare("INSERT INTO software_inventory (software_name, version, license_type, expiry_date, status) VALUES (?, ?, ?, ?, 'active')");
        if ($stmt) {
            $stmt->bind_param("ssss", $sn, $sv, $lic, $exp);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Software added.";
        header('Location: computer_lab.php?section=software');
        exit;
    }

    if ($action === 'delete_software') {
        $id = (int)$_POST['id'];
        $stmt = $ict->prepare("UPDATE software_inventory SET status='deleted' WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Software removed.";
        header('Location: computer_lab.php?section=software');
        exit;
    }

    if ($action === 'add_inventory_item') {
        $in = $_POST['item_name'] ?? '';
        $ic = $_POST['item_code'] ?? '';
        $cat = $_POST['category'] ?? '';
        $qty = (int)($_POST['quantity'] ?? 0);
        $un = $_POST['unit'] ?? 'pcs';
        $stmt = $ict->prepare("INSERT INTO lab_inventory_items (item_name, item_code, category, quantity, unit) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssii", $in, $ic, $cat, $qty, $un);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Inventory item added.";
        header('Location: computer_lab.php?section=inventory');
        exit;
    }

    if ($action === 'update_inventory_qty') {
        $id = (int)$_POST['id'];
        $qty = (int)($_POST['quantity'] ?? 0);
        $stmt = $ict->prepare("UPDATE lab_inventory_items SET quantity=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("ii", $qty, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Inventory updated.";
        header('Location: computer_lab.php?section=inventory');
        exit;
    }

    if ($action === 'record_attendance') {
        $sid = $_POST['student_id'] ?? '';
        $sn = $_POST['student_name'] ?? '';
        $ss = $_POST['session'] ?? 'Lab Session';
        $st = $_POST['status'] ?? 'present';
        $stmt = $ict->prepare("INSERT INTO lab_attendance (student_id, student_name, session, status) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $sid, $sn, $ss, $st);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "Attendance recorded.";
        header('Location: computer_lab.php?section=attendance');
        exit;
    }

    if ($action === 'request_id_card') {
        $sid = $_POST['student_id'] ?? '';
        $sn = $_POST['student_name'] ?? '';
        $sp = $_POST['program'] ?? '';
        $stmt = $ict->prepare("INSERT INTO lab_id_card_requests (student_id, student_name, program, status) VALUES (?, ?, ?, 'pending')");
        if ($stmt) {
            $stmt->bind_param("sss", $sid, $sn, $sp);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "ID card request submitted.";
        header('Location: computer_lab.php?section=id-cards');
        exit;
    }

    if ($action === 'update_id_card_status') {
        $id = (int)$_POST['id'];
        $st = $_POST['status'] ?? '';
        $stmt = $ict->prepare("UPDATE lab_id_card_requests SET status=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("si", $st, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        $_SESSION['success'] = "ID card status updated.";
        header('Location: computer_lab.php?section=id-cards');
        exit;
    }

    header('Location: computer_lab.php');
    exit;
} // end if ($ict)
}

$section = $_GET['section'] ?? 'dashboard';
$pageTitle = 'Computer Lab Manager';
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/includes/dashboard_head.php'; ?>
<style>
:root { --sidebar-width: 260px; }
.stat-card { background:#fff; border-radius:12px; padding:16px; border:1px solid #e5e7eb; display:flex; align-items:center; gap:14px; transition:all .2s; }
.stat-card:hover { box-shadow:0 4px 14px rgba(0,0,0,0.07); transform:translateY(-1px); }
.stat-card .icon-circle { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.stat-card h4 { font-size:18px; font-weight:700; margin:0; line-height:1.2; }
.stat-card p { margin:0; font-size:11px; color:#6b7280; }
.section-card { background:#fff; border-radius:12px; padding:18px; border:1px solid #e5e7eb; margin-bottom:14px; }
.section-card h2 { font-size:14px; font-weight:700; margin-bottom:12px; color:#111827; }
.bg-primary-soft { background:#eef2ff; color:#4f46e5; }
.bg-blue-soft { background:#eff6ff; color:#2563eb; }
.bg-green-soft { background:#f0fdf4; color:#16a34a; }
.bg-red-soft { background:#fef2f2; color:#dc2626; }
.bg-orange-soft { background:#fff7ed; color:#ea580c; }
.bg-yellow-soft { background:#fefce8; color:#ca8a04; }
.bg-teal-soft { background:#f0fdfa; color:#0d9488; }
.bg-pink-soft { background:#fdf2f8; color:#db2777; }
.bg-cyan-soft { background:#ecfeff; color:#0891b2; }
.bg-purple-soft { background:#faf5ff; color:#9333ea; }
.bg-indigo-soft { background:#eef2ff; color:#4f46e5; }
.bg-cyan-soft { background:#ecfeff; color:#0891b2; }
.bg-gray-soft { background:#f3f4f6; color:#4b5563; }
.nav-pills-lab { display:flex; flex-wrap:wrap; gap:3px; margin-bottom:14px; padding:6px; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; }
.nav-pills-lab .nav-link { padding:5px 10px; border-radius:6px; font-size:11px; font-weight:500; color:#4b5563; text-decoration:none; white-space:nowrap; }
.nav-pills-lab .nav-link:hover { background:#e5e7eb; }
.nav-pills-lab .nav-link.active { background:#2563eb; color:#fff; }
.table-small td, .table-small th { padding:4px 8px!important; font-size:12px; }
.status-led { width:10px; height:10px; border-radius:50%; display:inline-block; }
.monitor-card { background:#1e293b; border-radius:10px; padding:14px; color:#e2e8f0; text-align:center; }
.monitor-card h3 { font-size:28px; font-weight:700; margin:0; }
.monitor-card p { font-size:11px; color:#94a3b8; margin:4px 0 0; }
.monitor-card .progress { height:4px; margin-top:8px; }
.badge-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>
<div class="page-content">
    <div class="top-bar">
        <div><strong><i class="fas fa-desktop me-2 text-primary"></i>Computer Lab Manager</strong><span class="text-muted small ms-2"><?= htmlspecialchars($user_name) ?></span></div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-md-block"><?= date('D, d M Y H:i') ?></span>
            <?php if ($pending_tickets): ?><span class="badge bg-danger"><?= $pending_tickets ?> tickets</span><?php endif; ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-home"></i></a>
            <a href="#" class="btn btn-sm btn-outline-danger" onclick="event.preventDefault();var f=document.createElement('form');f.method='POST';f.action='logout.php';document.body.appendChild(f);f.submit();"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <div class="content-section active content-area">
        <?php if ($msg = $_SESSION['success'] ?? null): ?><div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; unset($_SESSION['success']); ?>
        <?php if ($err = $_SESSION['error'] ?? null): ?><div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; unset($_SESSION['error']); ?>

        <!-- Section Tabs -->
        <ul class="nav nav-pills-lab">
            <li><a class="nav-link <?= $section==='dashboard'?'active':'' ?>" href="?section=dashboard"><i class="fas fa-chart-pie me-1"></i>Dashboard</a></li>
            <li><a class="nav-link <?= $section==='students'?'active':'' ?>" href="?section=students"><i class="fas fa-user-graduate me-1"></i>Students</a></li>
            <li><a class="nav-link <?= $section==='computers'?'active':'' ?>" href="?section=computers"><i class="fas fa-desktop me-1"></i>Computers</a></li>
            <li><a class="nav-link <?= $section==='sessions'?'active':'' ?>" href="?section=sessions"><i class="fas fa-calendar-alt me-1"></i>Sessions</a></li>
            <li><a class="nav-link <?= $section==='id-cards'?'active':'' ?>" href="?section=id-cards"><i class="fas fa-id-card me-1"></i>ID Cards</a></li>
            <li><a class="nav-link <?= $section==='equipment'?'active':'' ?>" href="?section=equipment"><i class="fas fa-tools me-1"></i>Equipment</a></li>
            <li><a class="nav-link <?= $section==='printing'?'active':'' ?>" href="?section=printing"><i class="fas fa-print me-1"></i>Printing</a></li>
            <li><a class="nav-link <?= $section==='support'?'active':'' ?>" href="?section=support"><i class="fas fa-headset me-1"></i>Support<?= $pending_tickets ? ' <span class="badge bg-danger">'.$pending_tickets.'</span>' : '' ?></a></li>
            <li><a class="nav-link <?= $section==='software'?'active':'' ?>" href="?section=software"><i class="fas fa-download me-1"></i>Software</a></li>
            <li><a class="nav-link <?= $section==='inventory'?'active':'' ?>" href="?section=inventory"><i class="fas fa-boxes me-1"></i>Inventory</a></li>
            <li><a class="nav-link <?= $section==='attendance'?'active':'' ?>" href="?section=attendance"><i class="fas fa-clipboard-check me-1"></i>Attendance</a></li>
            <li><a class="nav-link <?= $section==='reports'?'active':'' ?>" href="?section=reports"><i class="fas fa-chart-bar me-1"></i>Reports</a></li>
            <li><a class="nav-link <?= $section==='settings'?'active':'' ?>" href="?section=settings"><i class="fas fa-cog me-1"></i>Settings</a></li>
        </ul>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• DASHBOARD â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php if ($section === 'dashboard'): ?>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card" onclick="location='?section=students'" style="cursor:pointer"><div class="icon-circle bg-primary-soft"><i class="fas fa-user-graduate"></i></div><div><h4><?= $total_students ?></h4><p>Total Students</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-blue-soft"><i class="fas fa-desktop"></i></div><div><h4><?= $total_computers ?></h4><p>Total Computers</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-green-soft"><i class="fas fa-check-circle"></i></div><div><h4><?= $computers_online ?></h4><p>Online</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-red-soft"><i class="fas fa-times-circle"></i></div><div><h4><?= $computers_offline ?></h4><p>Offline</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-orange-soft"><i class="fas fa-tools"></i></div><div><h4><?= $computers_maintenance ?></h4><p>Maintenance</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-teal-soft"><i class="fas fa-calendar-check"></i></div><div><h4><?= $active_sessions ?></h4><p>Active Sessions</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-yellow-soft"><i class="fas fa-clock"></i></div><div><h4><?= $pending_bookings ?></h4><p>Pending Bookings</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-pink-soft"><i class="fas fa-headset"></i></div><div><h4><?= $pending_tickets ?></h4><p>Open Tickets</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-cyan-soft"><i class="fas fa-download"></i></div><div><h4><?= $software_count ?></h4><p>Software Titles</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-purple-soft"><i class="fas fa-print"></i></div><div><h4><?= $printing_jobs_today ?></h4><p>Print Jobs Today</p></div></div></div>
            <div class="col-6 col-md-4 col-lg-3 col-xl"><div class="stat-card"><div class="icon-circle bg-indigo-soft"><i class="fas fa-user-check"></i></div><div><h4><?= $student_attendance_today ?></h4><p>Attendance Today</p></div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="section-card">
                    <h2><i class="fas fa-heartbeat me-2 text-danger"></i>Lab Status Overview</h2>
                    <div class="row g-2">
                        <div class="col-4"><div class="monitor-card"><h3 style="color:#22c55e"><?= $computers_online ?></h3><p>Computers Online</p><div class="progress"><div class="progress-bar bg-success" style="width:<?= $total_computers>0?round($computers_online/$total_computers*100):0 ?>%"></div></div></div></div>
                        <div class="col-4"><div class="monitor-card"><h3 style="color:#ef4444"><?= $computers_offline ?></h3><p>Computers Offline</p><div class="progress"><div class="progress-bar bg-danger" style="width:<?= $total_computers>0?round($computers_offline/$total_computers*100):0 ?>%"></div></div></div></div>
                        <div class="col-4"><div class="monitor-card"><h3 style="color:#f59e0b"><?= $computers_maintenance ?></h3><p>In Maintenance</p><div class="progress"><div class="progress-bar bg-warning" style="width:<?= $total_computers>0?round($computers_maintenance/$total_computers*100):0 ?>%"></div></div></div></div>
                        <div class="col-4"><div class="monitor-card"><h3><?= $active_sessions ?></h3><p>Sessions Today</p><span class="badge bg-success">Active</span></div></div>
                        <div class="col-4"><div class="monitor-card"><h3><?= $pending_bookings ?></h3><p>Pending Bookings</p><span class="badge bg-warning text-dark">Pending</span></div></div>
                        <div class="col-4"><div class="monitor-card"><h3><?= $pending_tickets ?></h3><p>Open Tickets</p><span class="badge bg-<?= $pending_tickets>0?'danger':'secondary' ?>"><?= $pending_tickets>0?'Action Needed':'OK' ?></span></div></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-calendar-alt me-2 text-warning"></i>Recent Lab Bookings</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($recent_bookings)): ?><div class="text-center py-3 text-muted"><p>No recent bookings</p></div>
                    <?php else: foreach ($recent_bookings as $b): ?>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                        <div><strong><code><?= htmlspecialchars($b['booking_reference']) ?></code></strong> <?= htmlspecialchars($b['course_name']) ?> <span class="text-muted ms-2"><?= htmlspecialchars($b['instructor_name']) ?></span></div>
                        <div><span class="badge bg-<?= $b['status']==='confirmed'?'success':($b['status']==='pending'?'warning text-dark':'danger') ?>"><?= $b['status'] ?></span> <small class="text-muted"><?= date('d/m', strtotime($b['booking_date'])) ?></small></div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-headset me-2 text-info"></i>Pending Support Tickets</h2>
                    <div style="max-height:250px;overflow-y:auto">
                    <?php if (empty($pending_tickets_list)): ?><div class="text-center py-3 text-muted"><p>No pending tickets</p></div>
                    <?php else: foreach ($pending_tickets_list as $t): ?>
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                        <div><code><?= htmlspecialchars($t['ticket_number']) ?></code> <strong><?= htmlspecialchars($t['requester_name']) ?></strong><span class="text-muted ms-2"><?= htmlspecialchars(mb_substr($t['description']??'',0,50)) ?></span></div>
                        <div><span class="badge bg-<?= $t['priority']==='critical'||$t['priority']==='high'?'danger':($t['priority']==='medium'?'warning text-dark':'success') ?> me-1"><?= $t['priority'] ?></span><span class="badge bg-<?= $t['status']==='open'?'danger':'warning text-dark' ?>"><?= $t['status'] ?></span></div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="section-card">
                    <h2><i class="fas fa-tools me-2 text-warning"></i>Computers Requiring Attention</h2>
                    <div style="max-height:300px;overflow-y:auto">
                    <?php if (empty($maintenance_computers)): ?><div class="text-center py-3 text-muted"><p>All computers operational</p></div>
                    <?php else: foreach ($maintenance_computers as $c): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <div><strong><code><?= htmlspecialchars($c['computer_id']) ?></code></strong> <?= htmlspecialchars($c['computer_name']) ?> <span class="text-muted"><?= htmlspecialchars($c['location']) ?></span></div>
                        <div><span class="badge bg-<?= $c['status']==='online'?'success':($c['status']==='maintenance'?'warning text-dark':'danger') ?>"><?= $c['status'] ?></span></div>
                    </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-boxes me-2 text-purple"></i>Lab Overview</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Total Equipment</span><strong><?= $total_equipment ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Software Titles</span><strong><?= $software_count ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Print Jobs Today</span><strong><?= $printing_jobs_today ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Attendance Records Today</span><strong><?= $student_attendance_today ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• STUDENTS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'students'): ?>
        <div class="row g-3">
            <div class="col-12">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Student Management</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-plus me-1"></i>Add Student</button>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>#</th><th>Index Number</th><th>Full Name</th><th>Program</th><th>Phone</th><th>Email</th><th>Gender</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($students_list)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3">No students found. Add one above.</td></tr>
                            <?php else: $i=1; foreach ($students_list as $s): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><code><?= htmlspecialchars($s['index_number'] ?? '') ?></code></td>
                                    <td><?= htmlspecialchars($s['full_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($s['program'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($s['gender'] ?? '') ?></td>
                                    <td><span class="badge bg-<?= ($s['status']??'')==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($s['status'] ?? '') ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0" onclick="editStudent(<?= $s['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger py-0" onclick="deleteStudent(<?= $s['id'] ?>,'<?= htmlspecialchars(addslashes($s['full_name'] ?? '')) ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• COMPUTERS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'computers'): ?>
        <div class="row g-3">
            <div class="col-12">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-desktop me-2"></i>Lab Computers</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addComputerModal"><i class="fas fa-plus me-1"></i>Add Computer</button>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>ID</th><th>Name</th><th>Location</th><th>IP</th><th>OS</th><th>Status</th><th>Last Maint</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($computers)): ?><tr><td colspan="8" class="text-center text-muted">No computers registered</td></tr><?php endif; ?>
                                <?php foreach ($computers as $c): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($c['computer_id']) ?></code></td>
                                    <td><?= htmlspecialchars($c['computer_name']) ?></td>
                                    <td><small><?= htmlspecialchars($c['location']) ?></small></td>
                                    <td><code><?= htmlspecialchars($c['ip_address'] ?? '-') ?></code></td>
                                    <td><small><?= htmlspecialchars($c['os_installed'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-<?= $c['status']==='online'?'success':($c['status']==='maintenance'?'warning text-dark':'danger') ?>"><?= $c['status'] ?></span></td>
                                    <td><small><?= $c['last_maintenance'] ? date('d/m/Y', strtotime($c['last_maintenance'])) : 'Never' ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editComputer(<?= $c['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteComputer(<?= $c['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• SESSIONS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'sessions'): ?>
        <div class="row g-3">
            <div class="col-12">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Lab Sessions / Bookings</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBookingModal"><i class="fas fa-plus me-1"></i>New Booking</button>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Reference</th><th>Course</th><th>Instructor</th><th>Date</th><th>Time</th><th>Students</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($sessions)): ?><tr><td colspan="8" class="text-center text-muted">No sessions yet</td></tr><?php endif; ?>
                                <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($s['booking_reference']) ?></code></td>
                                    <td><?= htmlspecialchars($s['course_name']) ?></td>
                                    <td><?= htmlspecialchars($s['instructor_name']) ?></td>
                                    <td><small><?= date('d M Y', strtotime($s['booking_date'])) ?></small></td>
                                    <td><small><?= htmlspecialchars($s['time_slot']) ?></small></td>
                                    <td><?= $s['number_of_students'] ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="update_booking_status">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                                <option value="pending" <?= $s['status']==='pending'?'selected':'' ?>>Pending</option>
                                                <option value="confirmed" <?= $s['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                                                <option value="cancelled" <?= $s['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                                                <option value="completed" <?= $s['status']==='completed'?'selected':'' ?>>Completed</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0 px-1" title="View"><i class="fas fa-eye"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ID CARDS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'id-cards'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-id-card me-2"></i>Student ID Card Requests</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#idCardModal"><i class="fas fa-plus me-1"></i>New Request</button>
                    </div>
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>Status</th><th>Requested</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($id_card_requests)): ?><tr><td colspan="6" class="text-center text-muted">No ID card requests</td></tr><?php endif; ?>
                                <?php foreach ($id_card_requests as $r): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($r['student_id']) ?></code></td>
                                    <td><?= htmlspecialchars($r['student_name']) ?></td>
                                    <td><small><?= htmlspecialchars($r['program'] ?? '-') ?></small></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="update_id_card_status">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                                <option value="pending" <?= $r['status']==='pending'?'selected':'' ?>>Pending</option>
                                                <option value="processing" <?= $r['status']==='processing'?'selected':'' ?>>Processing</option>
                                                <option value="ready" <?= $r['status']==='ready'?'selected':'' ?>>Ready</option>
                                                <option value="issued" <?= $r['status']==='issued'?'selected':'' ?>>Issued</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><small><?= date('d/m/Y', strtotime($r['created_at'])) ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success py-0 px-1" title="Print"><i class="fas fa-print"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-info-circle me-2 text-info"></i>Summary</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Total Requests</span><strong><?= count($id_card_requests) ?></strong></div>
                        <?php
                        $pending_req = count(array_filter($id_card_requests, fn($r)=>$r['status']==='pending'));
                        $ready_req = count(array_filter($id_card_requests, fn($r)=>$r['status']==='ready'));
                        ?>
                        <div class="d-flex justify-content-between py-1"><span>Pending</span><span class="badge bg-warning text-dark"><?= $pending_req ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Ready for Pickup</span><span class="badge bg-success"><?= $ready_req ?></span></div>
                    </div>
                </div>
                <div class="section-card">
                    <h2><i class="fas fa-search me-2 text-primary"></i>Search Student</h2>
                    <form method="GET" action="computer_lab.php" class="d-flex gap-2">
                        <input type="hidden" name="section" value="id-cards">
                        <input type="text" name="student_search" class="form-control form-control-sm" placeholder="Name or ID..." value="<?= htmlspecialchars($_GET['student_search'] ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                    </form>
                    <?php
                    $search_s = trim($_GET['student_search'] ?? '');
                    if ($search_s && $students_conn):
                        $like = '%' . $search_s . '%';
                        $stmt = $students_conn->prepare("SELECT student_id, full_name, program FROM students WHERE full_name LIKE ? OR student_id LIKE ? LIMIT 10");
                        $found = [];
                        if ($stmt) {
                            $stmt->bind_param("ss", $like, $like);
                            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                            $res = $stmt->get_result();
                            if ($res) $found = isnm_fetch_all($res);
                            $stmt->close();
                        }
                        if (!empty($found)):
                    ?>
                    <div class="mt-2 small">
                        <?php foreach ($found as $f): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><code><?= htmlspecialchars($f['student_id']) ?></code> <?= htmlspecialchars($f['full_name']) ?></span>
                            <button class="btn btn-sm btn-outline-primary py-0" onclick="fillIdCardForm('<?= htmlspecialchars($f['student_id']) ?>','<?= htmlspecialchars($f['full_name']) ?>','<?= htmlspecialchars($f['program'] ?? '') ?>')"><i class="fas fa-id-card"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; endif; ?>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• EQUIPMENT â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'equipment'): ?>
        <div class="row g-3">
            <div class="col-12">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-tools me-2"></i>Lab Equipment</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipmentModal"><i class="fas fa-plus me-1"></i>Add Equipment</button>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Quantity</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($equipment)): ?><tr><td colspan="7" class="text-center text-muted">No equipment registered</td></tr><?php endif; ?>
                                <?php foreach ($equipment as $e): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($e['equipment_code']) ?></code></td>
                                    <td><?= htmlspecialchars($e['equipment_name']) ?></td>
                                    <td><small><?= htmlspecialchars($e['category'] ?? '-') ?></small></td>
                                    <td><?= $e['quantity'] ?></td>
                                    <td><small><?= htmlspecialchars($e['location'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-success"><?= $e['status'] ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteEquipment(<?= $e['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• PRINTING â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'printing'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-print me-2"></i>Printing Centre</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#printJobModal"><i class="fas fa-plus me-1"></i>New Print Job</button>
                    </div>
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Job Name</th><th>Type</th><th>Pages</th><th>Copies</th><th>User</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($printing_jobs)): ?><tr><td colspan="8" class="text-center text-muted">No print jobs</td></tr><?php endif; ?>
                                <?php foreach ($printing_jobs as $j): ?>
                                <tr>
                                    <td><small><?= htmlspecialchars($j['job_name']) ?></small></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($j['print_type']) ?></span></td>
                                    <td><?= $j['page_count'] ?></td>
                                    <td><?= $j['copy_count'] ?></td>
                                    <td><small><?= htmlspecialchars($j['user_name']) ?></small></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="update_print_status">
                                            <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                            <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                                <option value="pending" <?= $j['status']==='pending'?'selected':'' ?>>Pending</option>
                                                <option value="printing" <?= $j['status']==='printing'?'selected':'' ?>>Printing</option>
                                                <option value="completed" <?= $j['status']==='completed'?'selected':'' ?>>Completed</option>
                                                <option value="cancelled" <?= $j['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><small><?= date('d/m H:i', strtotime($j['created_at'])) ?></small></td>
                                    <td>
                                        <?php if ($j['status'] === 'completed'): ?>
                                        <button class="btn btn-sm btn-outline-info py-0 px-1" title="Receipt"><i class="fas fa-receipt"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-chart-simple me-2"></i>Today's Summary</h2>
                    <?php
                    $completed_today = lab_q($ict, "SELECT COUNT(*) FROM lab_printing_jobs WHERE DATE(created_at)=CURDATE() AND status='completed'");
                    $total_pages_today = lab_q($ict, "SELECT COALESCE(SUM(page_count * copy_count),0) FROM lab_printing_jobs WHERE DATE(created_at)=CURDATE()");
                    ?>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Jobs Today</span><strong><?= $printing_jobs_today ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Completed</span><span class="badge bg-success"><?= $completed_today ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Total Pages Printed</span><strong><?= $total_pages_today ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• SUPPORT â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'support'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-headset me-2"></i>IT Support Tickets</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ticketModal"><i class="fas fa-plus me-1"></i>New Ticket</button>
                    </div>
                    <?php $all_tickets = lab_fetch($ict, "SELECT * FROM it_support_tickets ORDER BY FIELD(priority,'critical','high','medium','low'), created_at DESC LIMIT 30"); ?>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>#</th><th>Requester</th><th>Issue</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($all_tickets)): ?><tr><td colspan="7" class="text-center text-muted">No tickets</td></tr><?php endif; ?>
                                <?php foreach ($all_tickets as $t): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                                    <td><?= htmlspecialchars($t['requester_name']) ?></td>
                                    <td><small><?= htmlspecialchars(mb_substr($t['description']??'',0,40)) ?></small></td>
                                    <td><span class="badge bg-<?= $t['priority']==='critical'||$t['priority']==='high'?'danger':($t['priority']==='medium'?'warning text-dark':'success') ?>"><?= $t['priority'] ?></span></td>
                                    <td><span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='in_progress'?'warning text-dark':($t['status']==='resolved'?'info':'secondary')) ?>"><?= str_replace('_',' ',$t['status']) ?></span></td>
                                    <td><small><?= date('d/m H:i', strtotime($t['created_at'])) ?></small></td>
                                    <td>
                                        <?php if ($t['status'] === 'open' || $t['status'] === 'in_progress'): ?>
                                        <button class="btn btn-sm btn-outline-success py-0 px-1" onclick="resolveTicket(<?= $t['id'] ?>)"><i class="fas fa-check"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-chart-simple me-2"></i>Ticket Summary</h2>
                    <?php
                    $open_count = lab_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='open'");
                    $in_progress_count = lab_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='in_progress'");
                    $resolved_count = lab_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='resolved'");
                    $closed_count = lab_q($ict, "SELECT COUNT(*) FROM it_support_tickets WHERE status='closed'");
                    ?>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Open</span><span class="badge bg-danger"><?= $open_count ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>In Progress</span><span class="badge bg-warning text-dark"><?= $in_progress_count ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Resolved</span><span class="badge bg-info"><?= $resolved_count ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Closed</span><span class="badge bg-secondary"><?= $closed_count ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• SOFTWARE â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'software'): ?>
        <div class="row g-3">
            <div class="col-12">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-download me-2"></i>Software Inventory</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSoftwareModal"><i class="fas fa-plus me-1"></i>Add Software</button>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Name</th><th>Version</th><th>License</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($software_items)): ?><tr><td colspan="6" class="text-center text-muted">No software registered</td></tr><?php endif; ?>
                                <?php foreach ($software_items as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['software_name']) ?></td>
                                    <td><small><?= htmlspecialchars($s['version'] ?? '-') ?></small></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($s['license_type'] ?? '-') ?></span></td>
                                    <td><small><?= $s['expiry_date'] ? date('d/m/Y', strtotime($s['expiry_date'])) : 'N/A' ?></small></td>
                                    <td><span class="badge bg-<?= $s['status']==='active'?'success':'secondary' ?>"><?= $s['status'] ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteSoftware(<?= $s['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• INVENTORY â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'inventory'): ?>
        <div class="row g-3">
            <div class="col-12">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-boxes me-2"></i>Lab Inventory</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addInventoryModal"><i class="fas fa-plus me-1"></i>Add Item</button>
                    </div>
                    <div class="table-responsive" style="max-height:550px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Code</th><th>Item</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($inventory_items)): ?><tr><td colspan="6" class="text-center text-muted">No inventory items</td></tr><?php endif; ?>
                                <?php foreach ($inventory_items as $item): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($item['item_code'] ?? '-') ?></code></td>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td><small><?= htmlspecialchars($item['category'] ?? '-') ?></small></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="update_inventory_qty">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <div class="input-group input-group-sm" style="width:120px">
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" class="form-control form-control-sm" style="width:60px">
                                                <button type="submit" class="btn btn-sm btn-outline-primary py-0"><i class="fas fa-save"></i></button>
                                            </div>
                                        </form>
                                    </td>
                                    <td><small><?= htmlspecialchars($item['unit'] ?? 'pcs') ?></small></td>
                                    <td></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ATTENDANCE â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'attendance'): ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Lab Attendance Records</h2>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#attendanceModal"><i class="fas fa-plus me-1"></i>Record Attendance</button>
                    </div>
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-sm table-hover table-small">
                            <thead><tr><th>Student ID</th><th>Name</th><th>Session</th><th>Status</th><th>Date/Time</th></tr></thead>
                            <tbody>
                                <?php if (empty($attendance_records)): ?><tr><td colspan="5" class="text-center text-muted">No attendance records</td></tr><?php endif; ?>
                                <?php foreach ($attendance_records as $a): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($a['student_id']) ?></code></td>
                                    <td><?= htmlspecialchars($a['student_name']) ?></td>
                                    <td><small><?= htmlspecialchars($a['session']) ?></small></td>
                                    <td><span class="badge bg-<?= $a['status']==='present'?'success':($a['status']==='late'?'warning text-dark':'danger') ?>"><?= $a['status'] ?></span></td>
                                    <td><small><?= date('d/m H:i', strtotime($a['created_at'])) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="section-card">
                    <h2><i class="fas fa-chart-simple me-2"></i>Today's Summary</h2>
                    <?php
                    $present_today = lab_q($ict, "SELECT COUNT(*) FROM lab_attendance WHERE DATE(created_at)=CURDATE() AND status='present'");
                    $absent_today = lab_q($ict, "SELECT COUNT(*) FROM lab_attendance WHERE DATE(created_at)=CURDATE() AND status='absent'");
                    ?>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Present Today</span><strong><?= $present_today ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Absent Today</span><strong><?= $absent_today ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Total Records</span><strong><?= count($attendance_records) ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• REPORTS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'reports'): ?>
        <div class="row g-3">
            <div class="col-12">
                <div class="section-card">
                    <h2><i class="fas fa-chart-bar me-2"></i>Lab Reports & Analytics</h2>
                    <div class="row g-2 mt-2">
                        <div class="col-md-3">
                            <div class="card border p-3 text-center">
                                <i class="fas fa-desktop fa-2x text-primary mb-2"></i>
                                <h5><?= $total_computers ?></h5>
                                <small class="text-muted">Total Computers</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border p-3 text-center">
                                <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                <h5><?= $active_sessions ?></h5>
                                <small class="text-muted">Sessions Today</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border p-3 text-center">
                                <i class="fas fa-print fa-2x text-info mb-2"></i>
                                <h5><?= $printing_jobs_today ?></h5>
                                <small class="text-muted">Print Jobs Today</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border p-3 text-center">
                                <i class="fas fa-user-check fa-2x text-warning mb-2"></i>
                                <h5><?= $student_attendance_today ?></h5>
                                <small class="text-muted">Attendance Today</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted small mb-0">Detailed reports with date range filtering coming soon. Use the Dashboard for real-time analytics.</p>
                </div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• SETTINGS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <?php elseif ($section === 'settings'): ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-cog me-2"></i>Lab Settings</h2>
                    <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="save_settings">
                        <div class="mb-2"><label class="form-label small">Lab Name</label><input type="text" class="form-control form-control-sm" name="lab_name" value="Computer Lab"></div>
                        <div class="mb-2"><label class="form-label small">Capacity (computers)</label><input type="number" class="form-control form-control-sm" name="capacity" value="<?= $total_computers ?>"></div>
                        <div class="mb-2"><label class="form-label small">Operating Hours</label><input type="text" class="form-control form-control-sm" name="hours" value="8:00 AM - 6:00 PM"></div>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Save Settings</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-info-circle me-2 text-info"></i>Lab Information</h2>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1"><span>Total Computers</span><strong><?= $total_computers ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Online</span><span class="badge bg-success"><?= $computers_online ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Offline</span><span class="badge bg-danger"><?= $computers_offline ?></span></div>
                        <div class="d-flex justify-content-between py-1"><span>Under Maintenance</span><span class="badge bg-warning text-dark"><?= $computers_maintenance ?></span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between py-1"><span>Software Titles</span><strong><?= $software_count ?></strong></div>
                        <div class="d-flex justify-content-between py-1"><span>Equipment Items</span><strong><?= $total_equipment ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="section-card">
            <h2><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Section Not Found</h2>
            <p class="text-muted">The requested section "<?= htmlspecialchars($section) ?>" was not found.</p>
            <a href="?page=home" class="btn btn-sm btn-primary"><i class="fas fa-home me-1"></i>Back to Dashboard</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- â•â•â• MODALS â•â•â• -->

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add_student">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Full Name *</label><input type="text" class="form-control" name="full_name" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Phone</label><input type="text" class="form-control" name="phone"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control" name="email"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Program *</label>
                        <select class="form-select" name="program" required>
                            <option value="">Select</option>
                            <option>Certificate in Nursing</option>
                            <option>Certificate in Midwifery</option>
                            <option>Diploma in Nursing</option>
                            <option>Diploma in Midwifery</option>
                            <option>Enrolled Comprehensive Nursing</option>
                            <option>Enrolled Psychiatric Nursing</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Gender</label>
                        <select class="form-select" name="gender"><option value="">Select</option><option>Male</option><option>Female</option></select>
                    </div>
                    <div class="col-md-3"><label class="form-label fw-semibold">DOB</label><input type="date" class="form-control" name="date_of_birth"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Set / Year</label><input type="text" class="form-control" name="set_name" value="<?= date('Y') ?>"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="edit_student">
            <input type="hidden" name="id" id="editStudentId">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Full Name *</label><input type="text" class="form-control" name="full_name" id="editFullName" required></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Index Number</label><input type="text" class="form-control" name="index_number" id="editIndexNumber"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Phone</label><input type="text" class="form-control" name="phone" id="editPhone"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control" name="email" id="editEmail"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Program</label>
                        <select class="form-select" name="program" id="editProgram">
                            <option value="">Select</option>
                            <option>Certificate in Nursing</option>
                            <option>Certificate in Midwifery</option>
                            <option>Diploma in Nursing</option>
                            <option>Diploma in Midwifery</option>
                            <option>Enrolled Comprehensive Nursing</option>
                            <option>Enrolled Psychiatric Nursing</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Gender</label>
                        <select class="form-select" name="gender" id="editGender"><option>Male</option><option>Female</option></select>
                    </div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Set / Year</label><input type="text" class="form-control" name="set_name" id="editSetName"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Status</label>
                        <select class="form-select" name="status" id="editStatus">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Graduated">Graduated</option>
                            <option value="Withdrawn">Withdrawn</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Update Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Student Modal -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="delete_student">
            <input type="hidden" name="id" id="deleteStudentId">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Student</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteStudentName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Computer Modal -->
<div class="modal fade" id="addComputerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add_computer">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Lab Computer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Computer ID *</label><input type="text" class="form-control" name="computer_id" required placeholder="e.g. LAB-C-001"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Name *</label><input type="text" class="form-control" name="computer_name" required></div>
                    <div class="col-12"><label class="form-label fw-semibold">Location *</label><input type="text" class="form-control" name="location" required></div>
                    <div class="col-md-6"><label class="form-label">IP Address</label><input type="text" class="form-control" name="ip_address"></div>
                    <div class="col-md-6"><label class="form-label">MAC Address</label><input type="text" class="form-control" name="mac_address"></div>
                    <div class="col-md-6"><label class="form-label">Specifications</label><input type="text" class="form-control" name="specifications"></div>
                    <div class="col-md-6"><label class="form-label">OS</label><input type="text" class="form-control" name="os_installed"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Computer Modal -->
<div class="modal fade" id="editComputerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="edit_computer">
            <input type="hidden" name="id" id="editComputerId">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Computer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Name</label><input type="text" class="form-control" name="computer_name" id="editComputerName" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Location</label><input type="text" class="form-control" name="location" id="editComputerLocation" required></div>
                    <div class="col-md-6"><label class="form-label">IP Address</label><input type="text" class="form-control" name="ip_address" id="editComputerIp"></div>
                    <div class="col-md-6"><label class="form-label">MAC Address</label><input type="text" class="form-control" name="mac_address" id="editComputerMac"></div>
                    <div class="col-md-6"><label class="form-label">Specifications</label><input type="text" class="form-control" name="specifications" id="editComputerSpecs"></div>
                    <div class="col-md-6"><label class="form-label">OS</label><input type="text" class="form-control" name="os_installed" id="editComputerOs"></div>
                    <div class="col-12"><label class="form-label">Status</label>
                        <select class="form-select" name="status" id="editComputerStatus">
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Computer Modal -->
<div class="modal fade" id="deleteComputerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="delete_computer">
            <input type="hidden" name="id" id="deleteComputerId">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Remove Computer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove this computer?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Remove</button>
            </div>
        </form>
    </div>
</div>

<!-- Ticket Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="create_ticket">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>New Support Ticket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Requester Name *</label><input type="text" class="form-control" name="requester_name" required></div>
                    <div class="col-md-6"><label class="form-label">Requester Email</label><input type="email" class="form-control" name="requester_email"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Type *</label>
                        <select class="form-select" name="requester_type" required><option value="staff">Staff</option><option value="student">Student</option><option value="faculty">Faculty</option></select>
                    </div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Issue Type *</label>
                        <select class="form-select" name="issue_type" required><option value="hardware">Hardware</option><option value="software">Software</option><option value="network">Network</option><option value="account">Account</option><option value="other">Other</option></select>
                    </div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Priority *</label>
                        <select class="form-select" name="priority" required><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select>
                    </div>
                    <div class="col-12"><label class="form-label fw-semibold">Description *</label><textarea class="form-control" name="description" rows="4" required></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- Resolve Ticket Modal -->
<div class="modal fade" id="resolveTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="resolve_ticket">
            <input type="hidden" name="ticket_id" id="resolveTicketId">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Resolve Ticket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="form-label fw-semibold">Resolution Notes</label><textarea class="form-control" name="resolution_notes" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Mark Resolved</button>
            </div>
        </form>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="addBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add_booking">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>New Lab Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Course Name *</label><input type="text" class="form-control" name="course_name" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Instructor *</label><input type="text" class="form-control" name="instructor_name" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Date *</label><input type="date" class="form-control" name="booking_date" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Time Slot *</label>
                        <select class="form-select" name="time_slot" required>
                            <option value="8:00-10:00">8:00 AM - 10:00 AM</option>
                            <option value="10:00-12:00">10:00 AM - 12:00 PM</option>
                            <option value="12:00-14:00">12:00 PM - 2:00 PM</option>
                            <option value="14:00-16:00">2:00 PM - 4:00 PM</option>
                            <option value="16:00-18:00">4:00 PM - 6:00 PM</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Number of Students *</label><input type="number" class="form-control" name="number_of_students" required min="1"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Booking</button>
            </div>
        </form>
    </div>
</div>

<!-- Equipment Modal -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add_equipment">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Equipment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Equipment Code *</label><input type="text" class="form-control" name="equipment_code" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Name *</label><input type="text" class="form-control" name="equipment_name" required></div>
                    <div class="col-md-6"><label class="form-label">Category</label>
                        <select class="form-select" name="category"><option>Furniture</option><option>Electronics</option><option>Medical</option><option>Other</option></select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Quantity *</label><input type="number" class="form-control" name="quantity" required min="1" value="1"></div>
                    <div class="col-12"><label class="form-label">Location</label><input type="text" class="form-control" name="location"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Print Job Modal -->
<div class="modal fade" id="printJobModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="create_print_job">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-print me-2"></i>New Print Job</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Document Name</label><input type="text" class="form-control" name="document_name" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Print Type *</label>
                        <select class="form-select" name="print_type" required><option value="bw">Black & White</option><option value="color">Color</option></select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Page Count *</label><input type="number" class="form-control" name="page_count" required min="1" value="1"></div>
                    <div class="col-md-6"><label class="form-label">Copies</label><input type="number" class="form-control" name="copy_count" min="1" value="1"></div>
                    <div class="col-md-6"><label class="form-label">User Name</label><input type="text" class="form-control" name="user_name" value="<?= htmlspecialchars($user_name) ?>"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-print me-1"></i> Submit Print Job</button>
            </div>
        </form>
    </div>
</div>

<!-- Software Modal -->
<div class="modal fade" id="addSoftwareModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add_software">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Software</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Software Name *</label><input type="text" class="form-control" name="software_name" required></div>
                    <div class="col-md-6"><label class="form-label">Version</label><input type="text" class="form-control" name="version"></div>
                    <div class="col-md-6"><label class="form-label">License Type</label>
                        <select class="form-select" name="license_type"><option>Open Source</option><option>Proprietary</option><option>Educational</option><option>Trial</option></select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Expiry Date</label><input type="date" class="form-control" name="expiry_date"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add_inventory_item">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Item Name *</label><input type="text" class="form-control" name="item_name" required></div>
                    <div class="col-md-6"><label class="form-label">Item Code</label><input type="text" class="form-control" name="item_code"></div>
                    <div class="col-md-6"><label class="form-label">Category</label>
                        <select class="form-select" name="category"><option>Consumables</option><option>Hardware</option><option>Stationery</option><option>Cleaning</option><option>Other</option></select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Quantity *</label><input type="number" class="form-control" name="quantity" required min="0" value="0"></div>
                    <div class="col-md-6"><label class="form-label">Unit</label><input type="text" class="form-control" name="unit" value="pcs"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="record_attendance">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Record Attendance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Student ID *</label><input type="text" class="form-control" name="student_id" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Student Name *</label><input type="text" class="form-control" name="student_name" required></div>
                    <div class="col-md-6"><label class="form-label">Session</label><input type="text" class="form-control" name="session" value="Lab Session"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Status *</label>
                        <select class="form-select" name="status" required><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Record</button>
            </div>
        </form>
    </div>
</div>

<!-- ID Card Request Modal -->
<div class="modal fade" id="idCardModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="request_id_card">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-id-card me-2"></i>Student ID Card Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Student ID *</label><input type="text" class="form-control" name="student_id" id="idCardStudentId" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Student Name *</label><input type="text" class="form-control" name="student_name" id="idCardStudentName" required></div>
                    <div class="col-12"><label class="form-label">Program</label><input type="text" class="form-control" name="program" id="idCardProgram"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Submit Request</button>
            </div>
        </form>
    </div>
</div>

<!-- â•â•â• FOOTER â•â•â• -->
<?php include_once __DIR__ . '/includes/dashboard_footer.php'; ?>

<script>
function editComputer(id) {
    var computers = <?= json_encode($computers) ?>;
    var c = computers.find(function(x) { return x.id == id; });
    if (!c) return;
    document.getElementById('editComputerId').value = c.id;
    document.getElementById('editComputerName').value = c.computer_name;
    document.getElementById('editComputerLocation').value = c.location;
    document.getElementById('editComputerIp').value = c.ip_address || '';
    document.getElementById('editComputerMac').value = c.mac_address || '';
    document.getElementById('editComputerSpecs').value = c.specifications || '';
    document.getElementById('editComputerOs').value = c.os_installed || '';
    document.getElementById('editComputerStatus').value = c.status || 'online';
    new bootstrap.Modal(document.getElementById('editComputerModal')).show();
}

function deleteComputer(id) {
    document.getElementById('deleteComputerId').value = id;
    new bootstrap.Modal(document.getElementById('deleteComputerModal')).show();
}

function resolveTicket(id) {
    document.getElementById('resolveTicketId').value = id;
    new bootstrap.Modal(document.getElementById('resolveTicketModal')).show();
}

function deleteEquipment(id) {
    if (!confirm('Remove this equipment?')) return;
    var f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = '<input type="hidden" name="action" value="delete_equipment"><input type="hidden" name="id" value="' + id + '">';
    document.body.appendChild(f);
    f.submit();
}

function deleteSoftware(id) {
    if (!confirm('Remove this software?')) return;
    var f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = '<input type="hidden" name="action" value="delete_software"><input type="hidden" name="id" value="' + id + '">';
    document.body.appendChild(f);
    f.submit();
}

function editStudent(id) {
    var students = <?= json_encode($students_list) ?>;
    var s = students.find(function(x) { return x.id == id; });
    if (!s) return;
    document.getElementById('editStudentId').value = s.id;
    document.getElementById('editFullName').value = s.full_name || '';
    document.getElementById('editIndexNumber').value = s.index_number || '';
    document.getElementById('editPhone').value = s.phone || '';
    document.getElementById('editEmail').value = s.email || '';
    document.getElementById('editProgram').value = s.program || '';
    document.getElementById('editGender').value = s.gender || 'Male';
    document.getElementById('editSetName').value = s.set_name || '';
    document.getElementById('editStatus').value = s.status || 'Active';
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}

function deleteStudent(id, name) {
    document.getElementById('deleteStudentId').value = id;
    document.getElementById('deleteStudentName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteStudentModal')).show();
}

function fillIdCardForm(studentId, studentName, program) {
    document.getElementById('idCardStudentId').value = studentId;
    document.getElementById('idCardStudentName').value = studentName;
    document.getElementById('idCardProgram').value = program;
}

// Auto-dismiss alerts
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(a) {
        try { var bs = new bootstrap.Alert(a); bs.close(); } catch(e) {}
    });
}, 5000);
</script>
</body>
</html>
