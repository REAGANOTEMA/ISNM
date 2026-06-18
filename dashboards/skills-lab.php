<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['skills lab', 'skills', 'lab manager', 'laboratory']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? '';
$role = $user['role_name'] ?? $user['role'] ?? 'Skills Lab Manager';
$staff = $ctx['staff'];
$students = $ctx['students'];
$website = $ctx['website'];

$view = $_GET['view'] ?? 'home';
$ajax = $_GET['ajax'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$q = $_GET['q'] ?? '';

$db = $students ?: $staff;

// ── AJAX Endpoints ────────────────────────────────────────────────────

// Equipment CRUD
if ($view === 'equipment' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $cond = $q ? "WHERE name LIKE '%" . $db->real_escape_string($q) . "%' OR equipment_code LIKE '%" . $db->real_escape_string($q) . "%'" : '';
            $r = $db->query("SELECT * FROM lab_equipment $cond ORDER BY name ASC");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {}
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'equipment' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $code = $db->real_escape_string($data['equipment_code'] ?? '');
    $name = $db->real_escape_string($data['name'] ?? '');
    $desc = $db->real_escape_string($data['description'] ?? '');
    $cat = $db->real_escape_string($data['category'] ?? 'other');
    $qty = (int)($data['quantity'] ?? 1);
    $avail = (int)($data['available_quantity'] ?? $qty);
    $cond = $db->real_escape_string($data['condition_status'] ?? 'good');
    $loc = $db->real_escape_string($data['location'] ?? '');
    $serial = $db->real_escape_string($data['serial_number'] ?? '');
    $pdate = $data['purchase_date'] ? "'" . $db->real_escape_string($data['purchase_date']) . "'" : 'NULL';
    $pcost = $data['purchase_cost'] !== '' ? (float)$data['purchase_cost'] : 'NULL';
    $supplier = $db->real_escape_string($data['supplier'] ?? '');
    $lmaint = $data['last_maintenance_date'] ? "'" . $db->real_escape_string($data['last_maintenance_date']) . "'" : 'NULL';
    $nmaint = $data['next_maintenance_date'] ? "'" . $db->real_escape_string($data['next_maintenance_date']) . "'" : 'NULL';
    $stat = $db->real_escape_string($data['status'] ?? 'active');
    $notes = $db->real_escape_string($data['notes'] ?? '');
    try {
        if ($id) {
            $db->query("UPDATE lab_equipment SET equipment_code='$code', name='$name', description='$desc', category='$cat', quantity=$qty, available_quantity=$avail, condition_status='$cond', location='$loc', serial_number='$serial', purchase_date=$pdate, purchase_cost=$pcost, supplier='$supplier', last_maintenance_date=$lmaint, next_maintenance_date=$nmaint, status='$stat', notes='$notes' WHERE id=$id");
        } else {
            $db->query("INSERT INTO lab_equipment (equipment_code, name, description, category, quantity, available_quantity, condition_status, location, serial_number, purchase_date, purchase_cost, supplier, last_maintenance_date, next_maintenance_date, status, notes) VALUES ('$code','$name','$desc','$cat',$qty,$avail,'$cond','$loc','$serial',$pdate,$pcost,'$supplier',$lmaint,$nmaint,'$stat','$notes')");
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'equipment' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_equipment WHERE id=$id"); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Checkouts CRUD
if ($view === 'checkouts' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $cond = $q ? "WHERE c.student_id LIKE '%" . $db->real_escape_string($q) . "%' OR e.name LIKE '%" . $db->real_escape_string($q) . "%'" : '';
            $r = $db->query("SELECT c.*, e.name AS equipment_name, e.equipment_code FROM lab_equipment_checkouts c JOIN lab_equipment e ON c.equipment_id=e.id $cond ORDER BY c.checkout_date DESC LIMIT 200");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {}
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'checkouts' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $eid = (int)($data['equipment_id'] ?? 0);
    $sid = $db->real_escape_string($data['student_id'] ?? '');
    $cb = (int)($user['id'] ?? 0);
    $erd = $db->real_escape_string($data['expected_return_date'] ?? '');
    $qty = (int)($data['quantity_checked_out'] ?? 1);
    $purp = $db->real_escape_string($data['purpose'] ?? '');
    $notes = $db->real_escape_string($data['notes'] ?? '');
    try {
        if ($id) {
            $ard = $data['actual_return_date'] ? "'" . $db->real_escape_string($data['actual_return_date']) . "'" : 'NULL';
            $qr = (int)($data['quantity_returned'] ?? 0);
            $stat = $db->real_escape_string($data['status'] ?? 'checked_out');
            $db->query("UPDATE lab_equipment_checkouts SET expected_return_date='$erd', actual_return_date=$ard, quantity_returned=$qr, status='$stat', notes='$notes' WHERE id=$id");
        } else {
            $db->query("INSERT INTO lab_equipment_checkouts (equipment_id, student_id, checked_out_by, expected_return_date, quantity_checked_out, purpose, notes) VALUES ($eid,'$sid',$cb,'$erd',$qty,'$purp','$notes')");
            $db->query("UPDATE lab_equipment SET available_quantity = GREATEST(available_quantity - $qty, 0) WHERE id=$eid");
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'checkouts' && $ajax === 'return' && $id) {
    header('Content-Type: application/json');
    try {
        $r = $db->query("SELECT equipment_id, quantity_checked_out FROM lab_equipment_checkouts WHERE id=$id");
        if ($c = $r->fetch_assoc()) {
            $db->query("UPDATE lab_equipment_checkouts SET actual_return_date=NOW(), quantity_returned=quantity_checked_out, status='returned' WHERE id=$id");
            $db->query("UPDATE lab_equipment SET available_quantity = available_quantity + {$c['quantity_checked_out']} WHERE id={$c['equipment_id']}");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Not found']);
        }
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'checkouts' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_equipment_checkouts WHERE id=$id"); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Sessions CRUD
if ($view === 'sessions' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $cond = $q ? "WHERE title LIKE '%" . $db->real_escape_string($q) . "%' OR session_code LIKE '%" . $db->real_escape_string($q) . "%'" : '';
            $r = $db->query("SELECT * FROM lab_practical_sessions $cond ORDER BY session_date DESC LIMIT 200");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {}
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'sessions' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $sc = $db->real_escape_string($data['session_code'] ?? '');
    $title = $db->real_escape_string($data['title'] ?? '');
    $desc = $db->real_escape_string($data['description'] ?? '');
    $inst = $db->real_escape_string($data['instructor'] ?? '');
    $prog = $db->real_escape_string($data['program'] ?? '');
    $yl = $db->real_escape_string($data['year_level'] ?? '');
    $sem = $db->real_escape_string($data['semester'] ?? '');
    $sdate = $db->real_escape_string($data['session_date'] ?? '');
    $stime = $data['start_time'] ? "'" . $db->real_escape_string($data['start_time']) . "'" : 'NULL';
    $etime = $data['end_time'] ? "'" . $db->real_escape_string($data['end_time']) . "'" : 'NULL';
    $loc = $db->real_escape_string($data['location'] ?? '');
    $max = (int)($data['max_students'] ?? 30);
    $stat = $db->real_escape_string($data['status'] ?? 'scheduled');
    $notes = $db->real_escape_string($data['notes'] ?? '');
    try {
        if ($id) {
            $db->query("UPDATE lab_practical_sessions SET session_code='$sc', title='$title', description='$desc', instructor='$inst', program='$prog', year_level='$yl', semester='$sem', session_date='$sdate', start_time=$stime, end_time=$etime, location='$loc', max_students=$max, status='$stat', notes='$notes' WHERE id=$id");
        } else {
            $db->query("INSERT INTO lab_practical_sessions (session_code, title, description, instructor, program, year_level, semester, session_date, start_time, end_time, location, max_students, status, notes) VALUES ('$sc','$title','$desc','$inst','$prog','$yl','$sem','$sdate',$stime,$etime,'$loc',$max,'$stat','$notes')");
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'sessions' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_practical_sessions WHERE id=$id"); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Skills demonstrations
if ($view === 'skills' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $cond = $q ? "WHERE s.skill_name LIKE '%" . $db->real_escape_string($q) . "%' OR s.student_id LIKE '%" . $db->real_escape_string($q) . "%'" : '';
            $r = $db->query("SELECT s.* FROM lab_skills_demonstrations s $cond ORDER BY s.date_demonstrated DESC LIMIT 200");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {}
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'skills' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $sid = $db->real_escape_string($data['student_id'] ?? '');
    $skn = $db->real_escape_string($data['skill_name'] ?? '');
    $skc = $db->real_escape_string($data['skill_category'] ?? '');
    $inst = $db->real_escape_string($data['instructor'] ?? '');
    $dd = $db->real_escape_string($data['date_demonstrated'] ?? date('Y-m-d'));
    $comp = $db->real_escape_string($data['competency'] ?? 'meets_expectations');
    $att = (int)($data['attempt_number'] ?? 1);
    $notes = $db->real_escape_string($data['notes'] ?? '');
    $nrd = $data['next_review_date'] ? "'" . $db->real_escape_string($data['next_review_date']) . "'" : 'NULL';
    $uid = (int)($user['id'] ?? 0);
    try {
        if ($id) {
            $db->query("UPDATE lab_skills_demonstrations SET student_id='$sid', skill_name='$skn', skill_category='$skc', instructor='$inst', date_demonstrated='$dd', competency='$comp', attempt_number=$att, notes='$notes', next_review_date=$nrd WHERE id=$id");
        } else {
            $db->query("INSERT INTO lab_skills_demonstrations (student_id, skill_name, skill_category, instructor, date_demonstrated, competency, attempt_number, notes, next_review_date, verified_by) VALUES ('$sid','$skn','$skc','$inst','$dd','$comp',$att,'$notes',$nrd,$uid)");
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'skills' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_skills_demonstrations WHERE id=$id"); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Consumables CRUD
if ($view === 'consumables' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $cond = $q ? "WHERE item_name LIKE '%" . $db->real_escape_string($q) . "%' OR category LIKE '%" . $db->real_escape_string($q) . "%'" : '';
            $r = $db->query("SELECT * FROM lab_consumables $cond ORDER BY item_name ASC");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {}
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'consumables' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $in = $db->real_escape_string($data['item_name'] ?? '');
    $cat = $db->real_escape_string($data['category'] ?? '');
    $qty = (float)($data['quantity'] ?? 0);
    $unit = $db->real_escape_string($data['unit'] ?? 'pieces');
    $msl = (float)($data['min_stock_level'] ?? 10);
    $uc = (float)($data['unit_cost'] ?? 0);
    $supp = $db->real_escape_string($data['supplier'] ?? '');
    $lod = $data['last_ordered_date'] ? "'" . $db->real_escape_string($data['last_ordered_date']) . "'" : 'NULL';
    $notes = $db->real_escape_string($data['notes'] ?? '');
    try {
        if ($id) {
            $db->query("UPDATE lab_consumables SET item_name='$in', category='$cat', quantity=$qty, unit='$unit', min_stock_level=$msl, unit_cost=$uc, supplier='$supp', last_ordered_date=$lod, notes='$notes' WHERE id=$id");
        } else {
            $db->query("INSERT INTO lab_consumables (item_name, category, quantity, unit, min_stock_level, unit_cost, supplier, last_ordered_date, notes) VALUES ('$in','$cat',$qty,'$unit',$msl,$uc,'$supp',$lod,'$notes')");
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'consumables' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_consumables WHERE id=$id"); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Lab Attendance
if ($view === 'attendance' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $sessionFilter = isset($_GET['session_id']) ? "WHERE a.session_id=" . (int)$_GET['session_id'] : '';
            $r = $db->query("SELECT a.*, s.title AS session_title, s.session_date FROM lab_attendance a JOIN lab_practical_sessions s ON a.session_id=s.id $sessionFilter ORDER BY a.created_at DESC LIMIT 300");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {}
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'attendance' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $sid = (int)($data['session_id'] ?? 0);
    $students = $data['students'] ?? []; // array of {student_id, status}
    if (!is_array($students)) $students = [];
    $mid = (int)($user['id'] ?? 0);
    $success = 0;
    foreach ($students as $s) {
        $stid = $db->real_escape_string($s['student_id'] ?? '');
        $stat = $db->real_escape_string($s['attendance_status'] ?? 'present');
        if (!$stid) continue;
        try {
            $db->query("INSERT INTO lab_attendance (session_id, student_id, attendance_status, check_in_time, marked_by) VALUES ($sid, '$stid', '$stat', CURTIME(), $mid) ON DUPLICATE KEY UPDATE attendance_status='$stat', marked_by=$mid");
            $success++;
        } catch (Exception $e) {}
    }
    echo json_encode(['success' => true, 'updated' => $success]); exit;
}
if ($view === 'attendance' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_attendance WHERE id=$id"); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Sessions list for dropdown
if ($view === 'attendance' && $ajax === 'sessions') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $r = $db->query("SELECT id, session_code, title, session_date FROM lab_practical_sessions ORDER BY session_date DESC LIMIT 50");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {}
    }
    echo json_encode($rows); exit;
}

// Incidents CRUD
if ($view === 'incidents' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $cond = $q ? "WHERE description LIKE '%" . $db->real_escape_string($q) . "%' OR incident_type LIKE '%" . $db->real_escape_string($q) . "%'" : '';
            $r = $db->query("SELECT * FROM lab_incidents $cond ORDER BY incident_date DESC, incident_time DESC LIMIT 200");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {}
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'incidents' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $idate = $db->real_escape_string($data['incident_date'] ?? date('Y-m-d'));
    $itime = $data['incident_time'] ? "'" . $db->real_escape_string($data['incident_time']) . "'" : 'NULL';
    $itype = $db->real_escape_string($data['incident_type'] ?? 'other');
    $sev = $db->real_escape_string($data['severity'] ?? 'minor');
    $desc = $db->real_escape_string($data['description'] ?? '');
    $ei = $db->real_escape_string($data['equipment_involved'] ?? '');
    $si = $db->real_escape_string($data['student_involved'] ?? '');
    $at = $db->real_escape_string($data['action_taken'] ?? '');
    $stat = $db->real_escape_string($data['status'] ?? 'open');
    $uid = (int)($user['id'] ?? 0);
    try {
        if ($id) {
            $db->query("UPDATE lab_incidents SET incident_date='$idate', incident_time=$itime, incident_type='$itype', severity='$sev', description='$desc', equipment_involved='$ei', student_involved='$si', action_taken='$at', status='$stat' WHERE id=$id");
        } else {
            $db->query("INSERT INTO lab_incidents (incident_date, incident_time, reported_by, incident_type, severity, description, equipment_involved, student_involved, action_taken, status) VALUES ('$idate',$itime,$uid,'$itype','$sev','$desc','$ei','$si','$at','$stat')");
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'incidents' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_incidents WHERE id=$id"); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Stats endpoint for dashboard home
if ($view === 'home' && $ajax === 'stats') {
    header('Content-Type: application/json');
    $stats = ['equipment'=>0,'active_checkouts'=>0,'overdue'=>0,'sessions'=>0,'pending_maintenance'=>0,'low_stock'=>0,'incidents'=>0];
    if ($db) {
        try {
            $r = $db->query("SELECT COUNT(*) FROM lab_equipment"); if ($r) $stats['equipment'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_equipment_checkouts WHERE status='checked_out'"); if ($r) $stats['active_checkouts'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_equipment_checkouts WHERE status='checked_out' AND expected_return_date < CURDATE()"); if ($r) $stats['overdue'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_practical_sessions WHERE status='scheduled'"); if ($r) $stats['sessions'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_equipment WHERE status='maintenance'"); if ($r) $stats['pending_maintenance'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_consumables WHERE quantity <= min_stock_level"); if ($r) $stats['low_stock'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_incidents WHERE status IN ('open','investigating')"); if ($r) $stats['incidents'] = (int)$r->fetch_row()[0];
        } catch (Exception $e) {}
    }
    echo json_encode($stats); exit;
}

// ── Stats for home page (PHP-side initial) ────────────────────────────
$equipment_count = 0; $checkout_count = 0; $overdue_count = 0; $scheduled_sessions = 0;
$maintenance_count = 0; $low_stock_count = 0; $incident_count = 0; $total_students = 0;
if ($db) {
    try {
        $r = $db->query("SELECT COUNT(*) FROM lab_equipment"); if ($r) $equipment_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_equipment_checkouts WHERE status='checked_out'"); if ($r) $checkout_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_equipment_checkouts WHERE status='checked_out' AND expected_return_date < CURDATE()"); if ($r) $overdue_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_practical_sessions WHERE status IN ('scheduled','ongoing')"); if ($r) $scheduled_sessions = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_equipment WHERE status='maintenance'"); if ($r) $maintenance_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_consumables WHERE quantity <= min_stock_level"); if ($r) $low_stock_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_incidents WHERE status IN ('open','investigating')"); if ($r) $incident_count = (int)$r->fetch_row()[0];
    } catch (Exception $e) {}
}

// Get students for dropdowns
$students_list = [];
if ($students) {
    try {
        $r = $students->query("SELECT id, admission_number, first_name, last_name FROM students WHERE status='Active' ORDER BY first_name ASC LIMIT 500");
        if ($r) $students_list = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root {
    --sl-primary: #0d6efd;
    --sl-success: #198754;
    --sl-warning: #ffc107;
    --sl-danger: #dc3545;
    --sl-info: #0dcaf0;
    --sl-dark: #212529;
}
.stats-card-lab { border-radius: 16px; border: none; transition: all .3s ease; overflow: hidden; }
.stats-card-lab:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,.12); }
.stats-card-lab .card-body { padding: 1.5rem; }
.stats-card-lab .stats-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.module-card { border-radius: 16px; border: 1.5px solid rgba(0,0,0,.06); transition: all .3s ease; cursor: pointer; background: linear-gradient(145deg, #fff, #f8faff); }
.module-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(13,110,253,.15); border-color: var(--sl-primary); }
.module-card .card-body { padding: 1.75rem; text-align: center; }
.module-card .module-icon { width: 64px; height: 64px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 1rem; }
.badge-status { padding: .35em .65em; font-size: .78rem; font-weight: 500; border-radius: 50px; }
.alert-low-stock { border-left: 4px solid var(--sl-warning); }
.alert-overdue { border-left: 4px solid var(--sl-danger); }
.alert-maintenance { border-left: 4px solid var(--sl-info); }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div style="margin-left:270px">
    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-flask me-2" style="color:var(--sl-primary)"></i>Skills Lab Dashboard</h1>
                    <p class="mb-0">Skills Laboratory Management System , <?= htmlspecialchars($role) ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="user-info">
                        <span class="me-3"><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($user_name) ?></span>
                        <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
                        <a href="../news.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-newspaper me-1"></i>News</a>
                        <a href="../logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4 px-4">

        <!-- Nav tabs -->
        <ul class="nav nav-tabs nav-fill mb-4 border-0" style="background:rgba(13,110,253,.04); border-radius:14px; padding:6px;">
            <li class="nav-item"><a class="nav-link <?= $view==='home'?'active fw-bold':'' ?>" href="?view=home"><i class="fas fa-home me-1"></i>Home</a></li>
            <li class="nav-item"><a class="nav-link <?= $view==='equipment'?'active fw-bold':'' ?>" href="?view=equipment"><i class="fas fa-tools me-1"></i>Equipment</a></li>
            <li class="nav-item"><a class="nav-link <?= $view==='checkouts'?'active fw-bold':'' ?>" href="?view=checkouts"><i class="fas fa-hand-holding me-1"></i>Check outs</a></li>
            <li class="nav-item"><a class="nav-link <?= $view==='sessions'?'active fw-bold':'' ?>" href="?view=sessions"><i class="fas fa-calendar-alt me-1"></i>Sessions</a></li>
            <li class="nav-item"><a class="nav-link <?= $view==='skills'?'active fw-bold':'' ?>" href="?view=skills"><i class="fas fa-certificate me-1"></i>Skills</a></li>
            <li class="nav-item"><a class="nav-link <?= $view==='consumables'?'active fw-bold':'' ?>" href="?view=consumables"><i class="fas fa-boxes me-1"></i>Consumables</a></li>
            <li class="nav-item"><a class="nav-link <?= $view==='attendance'?'active fw-bold':'' ?>" href="?view=attendance"><i class="fas fa-clipboard-list me-1"></i>Attendance</a></li>
            <li class="nav-item"><a class="nav-link <?= $view==='incidents'?'active fw-bold':'' ?>" href="?view=incidents"><i class="fas fa-exclamation-triangle me-1"></i>Incidents</a></li>
        </ul>

<?php if ($view === 'home'): ?>
        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6"><div class="card stats-card-lab"><div class="card-body d-flex align-items-center gap-3">
                <div class="stats-icon" style="background:rgba(13,110,253,.12);color:var(--sl-primary)"><i class="fas fa-tools"></i></div>
                <div><h3 class="fw-bold mb-0" id="stat-equipment"><?= $equipment_count ?></h3><small class="text-muted">Equipment Items</small></div>
            </div></div></div>
            <div class="col-md-3 col-6"><div class="card stats-card-lab"><div class="card-body d-flex align-items-center gap-3">
                <div class="stats-icon" style="background:rgba(25,135,84,.12);color:var(--sl-success)"><i class="fas fa-hand-holding"></i></div>
                <div><h3 class="fw-bold mb-0" id="stat-checkouts"><?= $checkout_count ?></h3><small class="text-muted">Active Check outs</small></div>
            </div></div></div>
            <div class="col-md-3 col-6"><div class="card stats-card-lab"><div class="card-body d-flex align-items-center gap-3">
                <div class="stats-icon" style="background:rgba(220,53,69,.12);color:var(--sl-danger)"><i class="fas fa-exclamation-circle"></i></div>
                <div><h3 class="fw-bold mb-0" id="stat-overdue"><?= $overdue_count ?></h3><small class="text-muted">Overdue Returns</small></div>
            </div></div></div>
            <div class="col-md-3 col-6"><div class="card stats-card-lab"><div class="card-body d-flex align-items-center gap-3">
                <div class="stats-icon" style="background:rgba(111,66,193,.12);color:#6f42c1"><i class="fas fa-calendar-check"></i></div>
                <div><h3 class="fw-bold mb-0" id="stat-sessions"><?= $scheduled_sessions ?></h3><small class="text-muted">Upcoming Sessions</small></div>
            </div></div></div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6"><div class="card stats-card-lab"><div class="card-body d-flex align-items-center gap-3">
                <div class="stats-icon" style="background:rgba(13,202,240,.12);color:var(--sl-info)"><i class="fas fa-wrench"></i></div>
                <div><h3 class="fw-bold mb-0" id="stat-maintenance"><?= $maintenance_count ?></h3><small class="text-muted">Under Maintenance</small></div>
            </div></div></div>
            <div class="col-md-3 col-6"><div class="card stats-card-lab"><div class="card-body d-flex align-items-center gap-3">
                <div class="stats-icon" style="background:rgba(255,193,7,.15);color:var(--sl-warning)"><i class="fas fa-exclamation-triangle"></i></div>
                <div><h3 class="fw-bold mb-0" id="stat-lowstock"><?= $low_stock_count ?></h3><small class="text-muted">Low Stock Items</small></div>
            </div></div></div>
            <div class="col-md-3 col-6"><div class="card stats-card-lab"><div class="card-body d-flex align-items-center gap-3">
                <div class="stats-icon" style="background:rgba(220,53,69,.12);color:var(--sl-danger)"><i class="fas fa-bug"></i></div>
                <div><h3 class="fw-bold mb-0" id="stat-incidents"><?= $incident_count ?></h3><small class="text-muted">Open Incidents</small></div>
            </div></div></div>
            <div class="col-md-3 col-6"><div class="card stats-card-lab"><div class="card-body d-flex align-items-center gap-3">
                <div class="stats-icon" style="background:rgba(25,135,84,.12);color:var(--sl-success)"><i class="fas fa-users"></i></div>
                <div><h3 class="fw-bold mb-0"><?= count($students_list) ?></h3><small class="text-muted">Active Students</small></div>
            </div></div></div>
        </div>

        <?php require_once __DIR__ . '/../includes/dashboard_module_slider.php'; renderModuleSlider($user_role); ?>

        <!-- Quick Access Modules -->
        <h5 class="fw-bold mb-3"><i class="fas fa-th-large me-2"></i>Quick Access</h5>
        <div class="row g-3">
            <div class="col-md-3"><a href="?view=equipment" class="text-decoration-none"><div class="card module-card h-100"><div class="card-body">
                <div class="module-icon" style="background:rgba(13,110,253,.1);color:var(--sl-primary)"><i class="fas fa-tools"></i></div>
                <h6 class="fw-bold">Equipment Inventory</h6><p class="small text-muted mb-0">Manage mannequins, models & instruments</p>
            </div></div></a></div>
            <div class="col-md-3"><a href="?view=checkouts" class="text-decoration-none"><div class="card module-card h-100"><div class="card-body">
                <div class="module-icon" style="background:rgba(25,135,84,.1);color:var(--sl-success)"><i class="fas fa-hand-holding"></i></div>
                <h6 class="fw-bold">Check out / Check in</h6><p class="small text-muted mb-0">Track equipment borrowed by students</p>
            </div></div></a></div>
            <div class="col-md-3"><a href="?view=sessions" class="text-decoration-none"><div class="card module-card h-100"><div class="card-body">
                <div class="module-icon" style="background:rgba(111,66,193,.1);color:#6f42c1"><i class="fas fa-calendar-alt"></i></div>
                <h6 class="fw-bold">Practical Sessions</h6><p class="small text-muted mb-0">Schedule & manage lab sessions</p>
            </div></div></a></div>
            <div class="col-md-3"><a href="?view=skills" class="text-decoration-none"><div class="card module-card h-100"><div class="card-body">
                <div class="module-icon" style="background:rgba(255,193,7,.15);color:var(--sl-warning)"><i class="fas fa-certificate"></i></div>
                <h6 class="fw-bold">Skills Demonstrations</h6><p class="small text-muted mb-0">Record student competency assessments</p>
            </div></div></a></div>
            <div class="col-md-3"><a href="?view=consumables" class="text-decoration-none"><div class="card module-card h-100"><div class="card-body">
                <div class="module-icon" style="background:rgba(13,202,240,.1);color:var(--sl-info)"><i class="fas fa-boxes"></i></div>
                <h6 class="fw-bold">Consumables</h6><p class="small text-muted mb-0">Track supplies & reorder levels</p>
            </div></div></a></div>
            <div class="col-md-3"><a href="?view=attendance" class="text-decoration-none"><div class="card module-card h-100"><div class="card-body">
                <div class="module-icon" style="background:rgba(13,110,253,.1);color:var(--sl-primary)"><i class="fas fa-clipboard-list"></i></div>
                <h6 class="fw-bold">Lab Attendance</h6><p class="small text-muted mb-0">Record & monitor student attendance</p>
            </div></div></a></div>
            <div class="col-md-3"><a href="?view=incidents" class="text-decoration-none"><div class="card module-card h-100"><div class="card-body">
                <div class="module-icon" style="background:rgba(220,53,69,.1);color:var(--sl-danger)"><i class="fas fa-exclamation-triangle"></i></div>
                <h6 class="fw-bold">Incident Reports</h6><p class="small text-muted mb-0">Log accidents, damages & hazards</p>
            </div></div></a></div>
        </div>
        <script>
        (function loadStats(){ fetch('?view=home&ajax=stats').then(r=>r.json()).then(d=>{
            if(d.equipment!==undefined){ ['equipment','checkouts','overdue','sessions','maintenance'].forEach(k=>{
                const el=document.getElementById('stat-'+k); if(el) el.textContent=d[k];
            });
            document.getElementById('stat-lowstock')&&(document.getElementById('stat-lowstock').textContent=d.low_stock);
            document.getElementById('stat-incidents')&&(document.getElementById('stat-incidents').textContent=d.incidents);}
        }).catch(()=>{}); })();
        </script>

<?php elseif ($view === 'equipment'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-tools me-2"></i>Equipment Inventory</h4>
            <div>
                <input type="text" id="eq-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search equipment...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openEqModal()"><i class="fas fa-plus me-1"></i>Add Equipment</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0" id="eq-table"><thead class="table-light"><tr>
                <th>Code</th><th>Name</th><th>Category</th><th>Qty</th><th>Avail</th><th>Condition</th><th>Location</th><th>Status</th><th style="width:120px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="eqModal"><div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="eqModalTitle">Add Equipment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="eqForm">
                <input type="hidden" name="id" id="eq-id">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Equipment Code *</label><input type="text" name="equipment_code" id="eq-code" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Name *</label><input type="text" name="name" id="eq-name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Category *</label><select name="category" id="eq-cat" class="form-select"><option value="mannequin">Mannequin</option><option value="model">Model</option><option value="instrument">Instrument</option><option value="furniture">Furniture</option><option value="consumable">Consumable</option><option value="other">Other</option></select></div>
                    <div class="col-md-3"><label class="form-label">Quantity *</label><input type="number" name="quantity" id="eq-qty" class="form-control" value="1" min="1"></div>
                    <div class="col-md-3"><label class="form-label">Available</label><input type="number" name="available_quantity" id="eq-avail" class="form-control" min="0"></div>
                    <div class="col-md-3"><label class="form-label">Condition</label><select name="condition_status" id="eq-cond" class="form-select"><option value="excellent">Excellent</option><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select></div>
                    <div class="col-md-3"><label class="form-label">Status</label><select name="status" id="eq-stat" class="form-select"><option value="active">Active</option><option value="maintenance">Maintenance</option><option value="retired">Retired</option></select></div>
                    <div class="col-md-6"><label class="form-label">Location</label><input type="text" name="location" id="eq-loc" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Serial Number</label><input type="text" name="serial_number" id="eq-serial" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" id="eq-pdate" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Purchase Cost</label><input type="number" step="0.01" name="purchase_cost" id="eq-pcost" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Supplier</label><input type="text" name="supplier" id="eq-supp" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Last Maintenance</label><input type="date" name="last_maintenance_date" id="eq-lmaint" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Next Maintenance</label><input type="date" name="next_maintenance_date" id="eq-nmaint" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="eq-desc" class="form-control" rows="2"></textarea></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" id="eq-notes" class="form-control" rows="2"></textarea></div>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveEq()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>

<?php elseif ($view === 'checkouts'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-hand-holding me-2"></i>Equipment Check out / Check in</h4>
            <div>
                <input type="text" id="co-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search student or equipment...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openCoModal()"><i class="fas fa-plus me-1"></i>New Check out</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0" id="co-table"><thead class="table-light"><tr>
                <th>ID</th><th>Equipment</th><th>Student ID</th><th>Check out</th><th>Expected Return</th><th>Qty</th><th>Status</th><th style="width:140px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="coModal"><div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="coModalTitle">New Check out</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="coForm">
                <input type="hidden" name="id" id="co-id">
                <div class="mb-3"><label class="form-label">Equipment *</label><select name="equipment_id" id="co-eid" class="form-select" required><option value="">-- Select Equipment --</option></select></div>
                <div class="mb-3"><label class="form-label">Student ID *</label><input type="text" name="student_id" id="co-sid" class="form-control" list="studentList" required></div>
                <div class="mb-3"><label class="form-label">Expected Return Date *</label><input type="date" name="expected_return_date" id="co-erd" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Quantity *</label><input type="number" name="quantity_checked_out" id="co-qty" class="form-control" value="1" min="1"></div>
                <div class="mb-3"><label class="form-label">Purpose</label><textarea name="purpose" id="co-purpose" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" id="co-notes" class="form-control" rows="2"></textarea></div>
                <div id="co-return-fields" style="display:none">
                    <hr><h6>Return</h6>
                    <div class="mb-3"><label class="form-label">Actual Return Date</label><input type="date" name="actual_return_date" id="co-ard" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Quantity Returned</label><input type="number" name="quantity_returned" id="co-qr" class="form-control" value="0" min="0"></div>
                    <div class="mb-3"><label class="form-label">Status</label><select name="status" id="co-stat" class="form-select"><option value="checked_out">Checked Out</option><option value="returned">Returned</option><option value="lost_damaged">Lost / Damaged</option></select></div>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveCo()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>

<?php elseif ($view === 'sessions'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-calendar-alt me-2"></i>Practical Sessions</h4>
            <div>
                <input type="text" id="ses-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search sessions...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openSesModal()"><i class="fas fa-plus me-1"></i>New Session</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0" id="ses-table"><thead class="table-light"><tr>
                <th>Code</th><th>Title</th><th>Date</th><th>Time</th><th>Instructor</th><th>Program</th><th>Location</th><th>Status</th><th style="width:120px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="sesModal"><div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="sesModalTitle">New Session</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="sesForm">
                <input type="hidden" name="id" id="ses-id">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Session Code *</label><input type="text" name="session_code" id="ses-code" class="form-control" required></div>
                    <div class="col-md-8"><label class="form-label">Title *</label><input type="text" name="title" id="ses-title" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Instructor</label><input type="text" name="instructor" id="ses-instr" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Program</label><input type="text" name="program" id="ses-prog" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Year Level</label><input type="text" name="year_level" id="ses-yl" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Date *</label><input type="date" name="session_date" id="ses-date" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Start Time</label><input type="time" name="start_time" id="ses-st" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">End Time</label><input type="time" name="end_time" id="ses-et" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Max Students</label><input type="number" name="max_students" id="ses-max" class="form-control" value="30"></div>
                    <div class="col-md-6"><label class="form-label">Location</label><input type="text" name="location" id="ses-loc" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Semester</label><input type="text" name="semester" id="ses-sem" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Status</label><select name="status" id="ses-stat" class="form-select"><option value="scheduled">Scheduled</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="ses-desc" class="form-control" rows="2"></textarea></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" id="ses-notes" class="form-control" rows="2"></textarea></div>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveSes()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>

<?php elseif ($view === 'skills'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-certificate me-2"></i>Skills Demonstrations</h4>
            <div>
                <input type="text" id="sk-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search skill or student...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openSkModal()"><i class="fas fa-plus me-1"></i>Record Skill</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0" id="sk-table"><thead class="table-light"><tr>
                <th>Student ID</th><th>Skill</th><th>Category</th><th>Date</th><th>Competency</th><th>Attempt</th><th>Instructor</th><th style="width:100px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="skModal"><div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 id="skModalTitle">Record Skill Demonstration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="skForm">
                <input type="hidden" name="id" id="sk-id">
                <div class="mb-3"><label class="form-label">Student ID *</label><input type="text" name="student_id" id="sk-sid" class="form-control" list="studentList" required></div>
                <div class="mb-3"><label class="form-label">Skill Name *</label><input type="text" name="skill_name" id="sk-name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Category</label><input type="text" name="skill_category" id="sk-cat" class="form-control" placeholder="e.g., Assessment, Injection, Wound Care"></div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="date_demonstrated" id="sk-date" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Competency</label><select name="competency" id="sk-comp" class="form-select"><option value="exceeds_expectations">Exceeds Expectations</option><option value="meets_expectations" selected>Meets Expectations</option><option value="needs_improvement">Needs Improvement</option><option value="unsatisfactory">Unsatisfactory</option></select></div>
                    <div class="col-md-4"><label class="form-label">Attempt #</label><input type="number" name="attempt_number" id="sk-att" class="form-control" value="1" min="1"></div>
                </div>
                <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" id="sk-notes" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">Next Review Date</label><input type="date" name="next_review_date" id="sk-nrd" class="form-control"></div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveSk()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>

<?php elseif ($view === 'consumables'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-boxes me-2"></i>Consumables Inventory</h4>
            <div>
                <input type="text" id="con-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search items...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openConModal()"><i class="fas fa-plus me-1"></i>Add Item</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0" id="con-table"><thead class="table-light"><tr>
                <th>Item</th><th>Category</th><th>Qty</th><th>Unit</th><th>Min Stock</th><th>Unit Cost</th><th>Supplier</th><th>Status</th><th style="width:100px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="conModal"><div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 id="conModalTitle">Add Consumable</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="conForm">
                <input type="hidden" name="id" id="con-id">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Item Name *</label><input type="text" name="item_name" id="con-name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Category</label><input type="text" name="category" id="con-cat" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Quantity *</label><input type="number" step="0.01" name="quantity" id="con-qty" class="form-control" value="0"></div>
                    <div class="col-md-4"><label class="form-label">Unit</label><select name="unit" id="con-unit" class="form-select"><option value="pieces">Pieces</option><option value="pairs">Pairs</option><option value="boxes">Boxes</option><option value="litres">Litres</option><option value="ml">Millilitres</option><option value="grams">Grams</option><option value="kg">Kilograms</option><option value="rolls">Rolls</option><option value="packs">Packs</option></select></div>
                    <div class="col-md-4"><label class="form-label">Min Stock Level</label><input type="number" step="0.01" name="min_stock_level" id="con-msl" class="form-control" value="10"></div>
                    <div class="col-md-4"><label class="form-label">Unit Cost (UGX)</label><input type="number" step="0.01" name="unit_cost" id="con-uc" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Supplier</label><input type="text" name="supplier" id="con-supp" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Last Ordered</label><input type="date" name="last_ordered_date" id="con-lod" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" id="con-notes" class="form-control" rows="2"></textarea></div>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveCon()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>

<?php elseif ($view === 'attendance'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-list me-2"></i>Lab Attendance</h4>
            <div>
                <select id="att-session-filter" class="form-select form-select-sm d-inline-block" style="width:300px" onchange="loadAtt()"><option value="">-- All Sessions --</option></select>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0" id="att-table"><thead class="table-light"><tr>
                <th>Session</th><th>Date</th><th>Student ID</th><th>Status</th><th>Check in</th><th>Marked By</th><th style="width:80px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <!-- Batch Attendance Modal -->
        <div class="modal fade" id="attBatchModal"><div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Take Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Session *</label><select id="att-session-id" class="form-select"><option value="">-- Select Session --</option></select></div>
                <div class="mb-3"><label class="form-label">Student IDs (one per line) *</label><textarea id="att-student-ids" class="form-control" rows="5" placeholder="e.g.,&#10;NSN001&#10;NSN002&#10;NSN003"></textarea></div>
                <div class="mb-3"><label class="form-label">Default Status</label><select id="att-default-status" class="form-select"><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="excused">Excused</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveBatchAtt()"><i class="fas fa-check me-1"></i>Record Attendance</button></div>
        </div></div></div>
        <button class="btn btn-primary btn-sm mt-2" onclick="openAttBatchModal()"><i class="fas fa-check-double me-1"></i>Take Batch Attendance</button>

<?php elseif ($view === 'incidents'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Incident Reports</h4>
            <div>
                <input type="text" id="inc-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search incidents...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openIncModal()"><i class="fas fa-plus me-1"></i>Report Incident</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0" id="inc-table"><thead class="table-light"><tr>
                <th>Date</th><th>Time</th><th>Type</th><th>Severity</th><th>Description</th><th>Equipment</th><th>Status</th><th style="width:100px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="incModal"><div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 id="incModalTitle">Report Incident</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="incForm">
                <input type="hidden" name="id" id="inc-id">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Date *</label><input type="date" name="incident_date" id="inc-date" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Time</label><input type="time" name="incident_time" id="inc-time" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Type *</label><select name="incident_type" id="inc-type" class="form-select"><option value="injury">Injury</option><option value="equipment_damage">Equipment Damage</option><option value="safety_hazard">Safety Hazard</option><option value="near_miss">Near Miss</option><option value="other">Other</option></select></div>
                    <div class="col-md-4"><label class="form-label">Severity</label><select name="severity" id="inc-sev" class="form-select"><option value="minor">Minor</option><option value="moderate">Moderate</option><option value="serious">Serious</option><option value="critical">Critical</option></select></div>
                    <div class="col-md-4"><label class="form-label">Equipment Involved</label><input type="text" name="equipment_involved" id="inc-ei" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Student Involved</label><input type="text" name="student_involved" id="inc-si" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Description *</label><textarea name="description" id="inc-desc" class="form-control" rows="3" required></textarea></div>
                    <div class="col-12"><label class="form-label">Action Taken</label><textarea name="action_taken" id="inc-at" class="form-control" rows="2"></textarea></div>
                    <div class="col-12"><label class="form-label">Status</label><select name="status" id="inc-stat" class="form-select"><option value="open">Open</option><option value="investigating">Investigating</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></div>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveInc()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>

<?php endif; ?>

    </div><!-- /container-fluid -->
</div><!-- /margin-left:270px -->

<!-- Student datalist -->
<datalist id="studentList">
<?php foreach ($students_list as $s): ?>
    <option value="<?= htmlspecialchars($s['admission_number']) ?>"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></option>
<?php endforeach; ?>
</datalist>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<script>
// ── Shared helpers ─────────────────────────────────────────────
function getVal(id) { return document.getElementById(id)?.value || ''; }
function setVal(id, v) { const el = document.getElementById(id); if(el) el.value = v; }
function showToast(msg, type) {
    const c = document.createElement('div');
    c.className = 'position-fixed bottom-0 end-0 p-3'; c.style.zIndex='9999';
    c.innerHTML = `<div class="toast align-items-center text-bg-${type} border-0" role="alert"><div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
    document.body.appendChild(c);
    const t = new bootstrap.Toast(c.querySelector('.toast')); t.show();
    setTimeout(() => c.remove(), 4000);
}

// ── Data Loader ────────────────────────────────────────────────
function loadTable(endpoint, tableId, renderFn) {
    const search = document.getElementById(tableId.replace('table','').replace('-','') + '-search');
    const q = search ? search.value : '';
    fetch(endpoint + '&q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(d => {
            const tbody = document.querySelector('#' + tableId + ' tbody');
            if (!tbody) return;
            if (!d.data || !d.data.length) { tbody.innerHTML = '<tr><td colspan="99" class="text-center text-muted py-4">No records found</td></tr>'; return; }
            tbody.innerHTML = d.data.map(renderFn).join('');
        });
}

// ── Equipment ──────────────────────────────────────────────────
function loadEq() { loadTable('?view=equipment&ajax=get', 'eq-table', r => `<tr>
    <td>${esc(r.equipment_code)}</td><td>${esc(r.name)}</td><td><span class="badge bg-secondary">${esc(r.category)}</span></td>
    <td>${r.quantity}</td><td>${r.available_quantity}</td>
    <td><span class="badge-status bg-${r.condition_status==='excellent'?'success':r.condition_status==='good'?'primary':r.condition_status==='fair'?'warning':'danger'}">${esc(r.condition_status)}</span></td>
    <td>${esc(r.location)}</td>
    <td><span class="badge-status bg-${r.status==='active'?'success':r.status==='maintenance'?'info':'secondary'}">${esc(r.status)}</span></td>
    <td>
        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editEq(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteEq(${r.id})"><i class="fas fa-trash"></i></button>
    </td>
</tr>`); }
function openEqModal(data) {
    setVal('eq-id', data?.id||'');
    setVal('eq-code', data?.equipment_code||'');
    setVal('eq-name', data?.name||'');
    setVal('eq-cat', data?.category||'other');
    setVal('eq-qty', data?.quantity||1);
    setVal('eq-avail', data?.available_quantity||'');
    setVal('eq-cond', data?.condition_status||'good');
    setVal('eq-stat', data?.status||'active');
    setVal('eq-loc', data?.location||'');
    setVal('eq-serial', data?.serial_number||'');
    setVal('eq-pdate', data?.purchase_date||'');
    setVal('eq-pcost', data?.purchase_cost||'');
    setVal('eq-supp', data?.supplier||'');
    setVal('eq-lmaint', data?.last_maintenance_date||'');
    setVal('eq-nmaint', data?.next_maintenance_date||'');
    setVal('eq-desc', data?.description||'');
    setVal('eq-notes', data?.notes||'');
    document.getElementById('eqModalTitle').textContent = data?.id ? 'Edit Equipment' : 'Add Equipment';
    new bootstrap.Modal(document.getElementById('eqModal')).show();
}
function editEq(id) {
    fetch('?view=equipment&ajax=get&q=').then(r=>r.json()).then(d => {
        const item = d.data.find(x => x.id == id);
        if (item) openEqModal(item);
    });
}
function saveEq() {
    const f = document.getElementById('eqForm');
    const data = Object.fromEntries(new FormData(f));
    fetch('?view=equipment&ajax=save', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)})
        .then(r=>r.json()).then(d => {
            if (d.success) { bootstrap.Modal.getInstance(document.getElementById('eqModal')).hide(); loadEq(); showToast('Equipment saved','success'); }
            else showToast('Error: '+d.error,'danger');
        });
}
function deleteEq(id) {
    if (!confirm('Delete this equipment?')) return;
    fetch('?view=equipment&ajax=delete&id='+id).then(r=>r.json()).then(d => {
        if (d.success) { loadEq(); showToast('Deleted','success'); } else showToast('Delete failed','danger');
    });
}

// ── Checkouts ──────────────────────────────────────────────────
function loadCo() { loadTable('?view=checkouts&ajax=get', 'co-table', r => `<tr class="${r.expected_return_date < todayStr() && r.status==='checked_out'?'table-danger':''}">
    <td>${r.id}</td><td>${esc(r.equipment_name||'')} (${esc(r.equipment_code||'')})</td><td>${esc(r.student_id)}</td>
    <td>${r.checkout_date ? r.checkout_date.substring(0,10) : ''}</td>
    <td>${esc(r.expected_return_date)}</td><td>${r.quantity_checked_out}</td>
    <td><span class="badge-status bg-${r.status==='checked_out'?'warning':r.status==='returned'?'success':'danger'}">${esc(r.status)}</span></td>
    <td>
        ${r.status==='checked_out'?`<button class="btn btn-sm btn-outline-success py-0 px-1" onclick="returnCo(${r.id})"><i class="fas fa-undo"></i></button>`:''}
        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editCo(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteCo(${r.id})"><i class="fas fa-trash"></i></button>
    </td>
</tr>`); }
function todayStr() {
    const d = new Date(); return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
}
function loadCoEquipmentList() {
    fetch('?view=equipment&ajax=get').then(r=>r.json()).then(d => {
        const sel = document.getElementById('co-eid');
        if (!sel) return;
        sel.innerHTML = '<option value="">-- Select Equipment --</option>' + d.data.map(x => `<option value="${x.id}">${esc(x.name)} (${x.equipment_code}) , Avail: ${x.available_quantity}</option>`).join('');
    });
}
function openCoModal(data) {
    setVal('co-id', data?.id||'');
    setVal('co-eid', data?.equipment_id||'');
    setVal('co-sid', data?.student_id||'');
    setVal('co-erd', data?.expected_return_date||'');
    setVal('co-qty', data?.quantity_checked_out||1);
    setVal('co-purpose', data?.purpose||'');
    setVal('co-notes', data?.notes||'');
    if (data?.id) {
        setVal('co-ard', data?.actual_return_date ? data.actual_return_date.substring(0,10) : '');
        setVal('co-qr', data?.quantity_returned||0);
        setVal('co-stat', data?.status||'checked_out');
        document.getElementById('co-return-fields').style.display = 'block';
        document.getElementById('coModalTitle').textContent = 'Edit Check out';
    } else {
        document.getElementById('co-return-fields').style.display = 'none';
        document.getElementById('coModalTitle').textContent = 'New Check out';
        setVal('co-erd', new Date(Date.now()+7*86400000).toISOString().substring(0,10));
    }
    loadCoEquipmentList();
    new bootstrap.Modal(document.getElementById('coModal')).show();
}
function editCo(id) {
    fetch('?view=checkouts&ajax=get').then(r=>r.json()).then(d => {
        const item = d.data.find(x => x.id == id);
        if (item) openCoModal(item);
    });
}
function saveCo() {
    const f = document.getElementById('coForm');
    const data = Object.fromEntries(new FormData(f));
    fetch('?view=checkouts&ajax=save', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)})
        .then(r=>r.json()).then(d => {
            if (d.success) { bootstrap.Modal.getInstance(document.getElementById('coModal')).hide(); loadCo(); showToast('Saved','success'); }
            else showToast('Error: '+d.error,'danger');
        });
}
function returnCo(id) {
    if (!confirm('Mark this item as returned?')) return;
    fetch('?view=checkouts&ajax=return&id='+id).then(r=>r.json()).then(d => {
        if (d.success) { loadCo(); showToast('Returned','success'); } else showToast('Error','danger');
    });
}
function deleteCo(id) {
    if (!confirm('Delete this checkout record?')) return;
    fetch('?view=checkouts&ajax=delete&id='+id).then(r=>r.json()).then(d => {
        if (d.success) { loadCo(); showToast('Deleted','success'); } else showToast('Delete failed','danger');
    });
}

// ── Sessions ───────────────────────────────────────────────────
function loadSes() { loadTable('?view=sessions&ajax=get', 'ses-table', r => `<tr>
    <td>${esc(r.session_code)}</td><td><strong>${esc(r.title)}</strong></td>
    <td>${esc(r.session_date)}</td>
    <td>${r.start_time ? r.start_time.substring(0,5) : ''}${r.end_time ? ' to '+r.end_time.substring(0,5) : ''}</td>
    <td>${esc(r.instructor)}</td><td>${esc(r.program)}</td><td>${esc(r.location)}</td>
    <td><span class="badge-status bg-${r.status==='scheduled'?'primary':r.status==='ongoing'?'success':r.status==='completed'?'secondary':'danger'}">${esc(r.status)}</span></td>
    <td>
        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editSes(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteSes(${r.id})"><i class="fas fa-trash"></i></button>
    </td>
</tr>`); }
function openSesModal(data) {
    setVal('ses-id', data?.id||''); setVal('ses-code', data?.session_code||'');
    setVal('ses-title', data?.title||''); setVal('ses-instr', data?.instructor||'');
    setVal('ses-prog', data?.program||''); setVal('ses-yl', data?.year_level||'');
    setVal('ses-date', data?.session_date||''); setVal('ses-st', data?.start_time||'');
    setVal('ses-et', data?.end_time||''); setVal('ses-max', data?.max_students||30);
    setVal('ses-loc', data?.location||''); setVal('ses-sem', data?.semester||'');
    setVal('ses-stat', data?.status||'scheduled');
    setVal('ses-desc', data?.description||''); setVal('ses-notes', data?.notes||'');
    document.getElementById('sesModalTitle').textContent = data?.id ? 'Edit Session' : 'New Session';
    new bootstrap.Modal(document.getElementById('sesModal')).show();
}
function editSes(id) {
    fetch('?view=sessions&ajax=get').then(r=>r.json()).then(d => {
        const item = d.data.find(x => x.id == id);
        if (item) openSesModal(item);
    });
}
function saveSes() {
    const f = document.getElementById('sesForm');
    const data = Object.fromEntries(new FormData(f));
    fetch('?view=sessions&ajax=save', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)})
        .then(r=>r.json()).then(d => {
            if (d.success) { bootstrap.Modal.getInstance(document.getElementById('sesModal')).hide(); loadSes(); showToast('Saved','success'); }
            else showToast('Error: '+d.error,'danger');
        });
}
function deleteSes(id) {
    if (!confirm('Delete this session?')) return;
    fetch('?view=sessions&ajax=delete&id='+id).then(r=>r.json()).then(d => {
        if (d.success) { loadSes(); showToast('Deleted','success'); } else showToast('Delete failed','danger');
    });
}

// ── Skills ─────────────────────────────────────────────────────
function loadSk() { loadTable('?view=skills&ajax=get', 'sk-table', r => `<tr>
    <td>${esc(r.student_id)}</td><td><strong>${esc(r.skill_name)}</strong></td><td>${esc(r.skill_category||'')}</td>
    <td>${esc(r.date_demonstrated)}</td>
    <td><span class="badge-status bg-${r.competency==='exceeds_expectations'?'success':r.competency==='meets_expectations'?'primary':r.competency==='needs_improvement'?'warning':'danger'}">${esc(r.competency.replace(/_/g,' '))}</span></td>
    <td>${r.attempt_number}</td><td>${esc(r.instructor||'')}</td>
    <td>
        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editSk(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteSk(${r.id})"><i class="fas fa-trash"></i></button>
    </td>
</tr>`); }
function openSkModal(data) {
    setVal('sk-id', data?.id||''); setVal('sk-sid', data?.student_id||'');
    setVal('sk-name', data?.skill_name||''); setVal('sk-cat', data?.skill_category||'');
    setVal('sk-date', data?.date_demonstrated||new Date().toISOString().substring(0,10));
    setVal('sk-comp', data?.competency||'meets_expectations');
    setVal('sk-att', data?.attempt_number||1);
    setVal('sk-notes', data?.notes||''); setVal('sk-nrd', data?.next_review_date||'');
    document.getElementById('skModalTitle').textContent = data?.id ? 'Edit Skill Record' : 'Record Skill Demonstration';
    new bootstrap.Modal(document.getElementById('skModal')).show();
}
function editSk(id) {
    fetch('?view=skills&ajax=get').then(r=>r.json()).then(d => {
        const item = d.data.find(x => x.id == id);
        if (item) openSkModal(item);
    });
}
function saveSk() {
    const f = document.getElementById('skForm');
    const data = Object.fromEntries(new FormData(f));
    fetch('?view=skills&ajax=save', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)})
        .then(r=>r.json()).then(d => {
            if (d.success) { bootstrap.Modal.getInstance(document.getElementById('skModal')).hide(); loadSk(); showToast('Saved','success'); }
            else showToast('Error: '+d.error,'danger');
        });
}
function deleteSk(id) {
    if (!confirm('Delete this skill record?')) return;
    fetch('?view=skills&ajax=delete&id='+id).then(r=>r.json()).then(d => {
        if (d.success) { loadSk(); showToast('Deleted','success'); } else showToast('Delete failed','danger');
    });
}

// ── Consumables ────────────────────────────────────────────────
function loadCon() { loadTable('?view=consumables&ajax=get', 'con-table', r => `<tr class="${parseFloat(r.quantity)<=parseFloat(r.min_stock_level)?'alert-low-stock':''}">
    <td><strong>${esc(r.item_name)}</strong></td><td>${esc(r.category||'')}</td><td>${r.quantity}</td><td>${esc(r.unit)}</td>
    <td>${r.min_stock_level}</td><td>${r.unit_cost ? Number(r.unit_cost).toLocaleString() : ''}</td>
    <td>${esc(r.supplier||'')}</td>
    <td>${parseFloat(r.quantity)<=parseFloat(r.min_stock_level)?'<span class="badge-status bg-warning">Low Stock</span>':'<span class="badge-status bg-success">In Stock</span>'}</td>
    <td>
        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editCon(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteCon(${r.id})"><i class="fas fa-trash"></i></button>
    </td>
</tr>`); }
function openConModal(data) {
    setVal('con-id', data?.id||''); setVal('con-name', data?.item_name||'');
    setVal('con-cat', data?.category||''); setVal('con-qty', data?.quantity||0);
    setVal('con-unit', data?.unit||'pieces'); setVal('con-msl', data?.min_stock_level||10);
    setVal('con-uc', data?.unit_cost||''); setVal('con-supp', data?.supplier||'');
    setVal('con-lod', data?.last_ordered_date||''); setVal('con-notes', data?.notes||'');
    document.getElementById('conModalTitle').textContent = data?.id ? 'Edit Consumable' : 'Add Consumable';
    new bootstrap.Modal(document.getElementById('conModal')).show();
}
function editCon(id) {
    fetch('?view=consumables&ajax=get').then(r=>r.json()).then(d => {
        const item = d.data.find(x => x.id == id);
        if (item) openConModal(item);
    });
}
function saveCon() {
    const f = document.getElementById('conForm');
    const data = Object.fromEntries(new FormData(f));
    fetch('?view=consumables&ajax=save', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)})
        .then(r=>r.json()).then(d => {
            if (d.success) { bootstrap.Modal.getInstance(document.getElementById('conModal')).hide(); loadCon(); showToast('Saved','success'); }
            else showToast('Error: '+d.error,'danger');
        });
}
function deleteCon(id) {
    if (!confirm('Delete this consumable?')) return;
    fetch('?view=consumables&ajax=delete&id='+id).then(r=>r.json()).then(d => {
        if (d.success) { loadCon(); showToast('Deleted','success'); } else showToast('Delete failed','danger');
    });
}

// ── Attendance ──────────────────────────────────────────────────
function loadAtt() {
    const sid = document.getElementById('att-session-filter')?.value || '';
    const endpoint = '?view=attendance&ajax=get' + (sid ? '&session_id='+sid : '');
    fetch(endpoint).then(r=>r.json()).then(d => {
        const tbody = document.querySelector('#att-table tbody');
        if (!tbody) return;
        if (!d.data || !d.data.length) { tbody.innerHTML = '<tr><td colspan="99" class="text-center text-muted py-4">No records found</td></tr>'; return; }
        tbody.innerHTML = d.data.map(r => `<tr>
            <td>${esc(r.session_title||'')}</td><td>${esc(r.session_date||'')}</td>
            <td>${esc(r.student_id)}</td>
            <td><span class="badge-status bg-${r.attendance_status==='present'?'success':r.attendance_status==='late'?'warning':r.attendance_status==='excused'?'info':'danger'}">${esc(r.attendance_status)}</span></td>
            <td>${r.check_in_time ? r.check_in_time.substring(0,5) : ''}</td>
            <td>${r.marked_by||''}</td>
            <td><button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteAtt(${r.id})"><i class="fas fa-trash"></i></button></td>
        </tr>`).join('');
    });
}
function loadAttSessions() {
    fetch('?view=attendance&ajax=sessions').then(r=>r.json()).then(d => {
        const sel = document.getElementById('att-session-filter');
        if (sel) sel.innerHTML = '<option value="">-- All Sessions --</option>' + d.map(x => `<option value="${x.id}">${esc(x.session_code)} , ${esc(x.title)} (${x.session_date})</option>`).join('');
        const sel2 = document.getElementById('att-session-id');
        if (sel2) sel2.innerHTML = '<option value="">-- Select Session --</option>' + d.map(x => `<option value="${x.id}">${esc(x.session_code)} , ${esc(x.title)} (${x.session_date})</option>`).join('');
    });
}
function openAttBatchModal() {
    new bootstrap.Modal(document.getElementById('attBatchModal')).show();
}
function saveBatchAtt() {
    const sid = document.getElementById('att-session-id').value;
    const raw = document.getElementById('att-student-ids').value.trim();
    const dflt = document.getElementById('att-default-status').value;
    if (!sid || !raw) { alert('Session and at least one Student ID required'); return; }
    const students = raw.split('\n').map(s => s.trim()).filter(Boolean).map(sid => ({student_id: sid, attendance_status: dflt}));
    fetch('?view=attendance&ajax=save', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({session_id: sid, students})})
        .then(r=>r.json()).then(d => {
            if (d.success) { bootstrap.Modal.getInstance(document.getElementById('attBatchModal')).hide(); loadAtt(); showToast(d.updated+' records saved','success'); }
            else showToast('Error','danger');
        });
}
function deleteAtt(id) {
    if (!confirm('Delete this attendance record?')) return;
    fetch('?view=attendance&ajax=delete&id='+id).then(r=>r.json()).then(d => {
        if (d.success) { loadAtt(); showToast('Deleted','success'); } else showToast('Delete failed','danger');
    });
}

// ── Incidents ───────────────────────────────────────────────────
function loadInc() { loadTable('?view=incidents&ajax=get', 'inc-table', r => `<tr>
    <td>${esc(r.incident_date)}</td><td>${r.incident_time ? r.incident_time.substring(0,5) : ''}</td>
    <td><span class="badge bg-secondary">${esc(r.incident_type.replace(/_/g,' '))}</span></td>
    <td><span class="badge-status bg-${r.severity==='minor'?'success':r.severity==='moderate'?'warning':r.severity==='serious'?'danger':'dark'}">${esc(r.severity)}</span></td>
    <td><small>${esc((r.description||'').substring(0,60))}${r.description?.length>60?'...':''}</small></td>
    <td>${esc(r.equipment_involved||'')}</td>
    <td><span class="badge-status bg-${r.status==='open'?'danger':r.status==='investigating'?'warning':r.status==='resolved'?'info':'secondary'}">${esc(r.status)}</span></td>
    <td>
        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editInc(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteInc(${r.id})"><i class="fas fa-trash"></i></button>
    </td>
</tr>`); }
function openIncModal(data) {
    setVal('inc-id', data?.id||''); setVal('inc-date', data?.incident_date||new Date().toISOString().substring(0,10));
    setVal('inc-time', data?.incident_time||''); setVal('inc-type', data?.incident_type||'other');
    setVal('inc-sev', data?.severity||'minor'); setVal('inc-ei', data?.equipment_involved||'');
    setVal('inc-si', data?.student_involved||''); setVal('inc-desc', data?.description||'');
    setVal('inc-at', data?.action_taken||''); setVal('inc-stat', data?.status||'open');
    document.getElementById('incModalTitle').textContent = data?.id ? 'Edit Incident' : 'Report Incident';
    new bootstrap.Modal(document.getElementById('incModal')).show();
}
function editInc(id) {
    fetch('?view=incidents&ajax=get').then(r=>r.json()).then(d => {
        const item = d.data.find(x => x.id == id);
        if (item) openIncModal(item);
    });
}
function saveInc() {
    const f = document.getElementById('incForm');
    const data = Object.fromEntries(new FormData(f));
    fetch('?view=incidents&ajax=save', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)})
        .then(r=>r.json()).then(d => {
            if (d.success) { bootstrap.Modal.getInstance(document.getElementById('incModal')).hide(); loadInc(); showToast('Saved','success'); }
            else showToast('Error: '+d.error,'danger');
        });
}
function deleteInc(id) {
    if (!confirm('Delete this incident report?')) return;
    fetch('?view=incidents&ajax=delete&id='+id).then(r=>r.json()).then(d => {
        if (d.success) { loadInc(); showToast('Deleted','success'); } else showToast('Delete failed','danger');
    });
}

// ── Live search ────────────────────────────────────────────────
document.addEventListener('keyup', function(e) {
    if (e.target.id.endsWith('-search')) {
        clearTimeout(window._searchTimer);
        window._searchTimer = setTimeout(() => {
            const map = {'eq-search':'loadEq','co-search':'loadCo','ses-search':'loadSes','sk-search':'loadSk','con-search':'loadCon','inc-search':'loadInc'};
            const fn = map[e.target.id];
            if (fn) window[fn]();
        }, 300);
    }
});

// ─── Escaping ──────────────────────────────────────────────────
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ── Init ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const view = '<?= $view ?>';
    if (view==='equipment') loadEq();
    else if (view==='checkouts') { loadCo(); loadCoEquipmentList(); }
    else if (view==='sessions') loadSes();
    else if (view==='skills') loadSk();
    else if (view==='consumables') loadCon();
    else if (view==='attendance') { loadAttSessions(); loadAtt(); }
    else if (view==='incidents') loadInc();
});
</script>
</body>
</html>