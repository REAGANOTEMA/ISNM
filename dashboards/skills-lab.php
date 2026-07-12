<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['skills lab']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? '';
$role = $user['role_name'] ?? $user['role'] ?? 'Skills Lab Manager';
$staff = $ctx['staff'];
$students = $ctx['students'];
$website = $ctx['website'];

if (isset($_GET['page']) && !isset($_GET['view'])) $_GET['view'] = $_GET['page'];
$view = $_GET['view'] ?? 'home';
$ajax = $_GET['ajax'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$q = $_GET['q'] ?? '';

$db = $students;

// â”€â”€ AJAX Endpoints â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

// Equipment CRUD
if ($view === 'equipment' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            if ($q) {
                $like = '%' . $q . '%';
                $stmt = $db->prepare("SELECT * FROM lab_equipment WHERE equipment_name LIKE ? OR equipment_code LIKE ? ORDER BY equipment_name ASC");
                $stmt->bind_param("ss", $like, $like);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $r = $stmt->get_result();
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            } else {
                $r = $db->query("SELECT * FROM lab_equipment ORDER BY equipment_name ASC");
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
            }
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'equipment' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $code = $data['equipment_code'] ?? '';
    $name = $data['equipment_name'] ?? '';
    $cat = $data['category'] ?? 'other';
    $cond = $data['condition_status'] ?? 'good';
    $qty = (int)($data['quantity'] ?? 1);
    $loc = $data['location'] ?? '';
    $lmaint = $data['last_maintenance'] ?: null;
    $stat = $data['status'] ?? 'active';
    try {
        if ($id) {
            $stmt = $db->prepare("UPDATE lab_equipment SET equipment_code=?, equipment_name=?, category=?, condition_status=?, quantity=?, location=?, last_maintenance=?, status=? WHERE id=?");
            $stmt->bind_param("ssssisssi", $code, $name, $cat, $cond, $qty, $loc, $lmaint, $stat, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        } else {
            $stmt = $db->prepare("INSERT INTO lab_equipment (equipment_code, equipment_name, category, condition_status, quantity, location, last_maintenance, status) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssisss", $code, $name, $cat, $cond, $qty, $loc, $lmaint, $stat);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'equipment' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_equipment WHERE id=" . intval($id)); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Checkouts CRUD
if ($view === 'checkouts' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            if ($q) {
                $like = '%' . $q . '%';
                $stmt = $db->prepare("SELECT c.*, e.equipment_name, e.equipment_code FROM lab_checkouts c JOIN lab_equipment e ON c.equipment_id=e.id WHERE c.borrower_id LIKE ? OR c.borrower_name LIKE ? OR e.equipment_name LIKE ? ORDER BY c.checkout_date DESC LIMIT 200");
                $stmt->bind_param("sss", $like, $like, $like);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $r = $stmt->get_result();
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            } else {
                $r = $db->query("SELECT c.*, e.equipment_name, e.equipment_code FROM lab_checkouts c JOIN lab_equipment e ON c.equipment_id=e.id ORDER BY c.checkout_date DESC LIMIT 200");
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
            }
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'checkouts' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $eid = (int)($data['equipment_id'] ?? 0);
    $bid = $data['borrower_id'] ?? '';
    $bname = $data['borrower_name'] ?? '';
    $erd = $data['expected_return'] ?? '';
    $notes = $data['notes'] ?? '';
    try {
        if ($id) {
            $ard = $data['actual_return'] ?: null;
            $stat = $data['status'] ?? 'checked_out';
            $stmt = $db->prepare("UPDATE lab_checkouts SET expected_return=?, actual_return=?, status=?, notes=? WHERE id=?");
            $stmt->bind_param("ssssi", $erd, $ard, $stat, $notes, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        } else {
            $stmt = $db->prepare("INSERT INTO lab_checkouts (equipment_id, borrower_id, borrower_name, expected_return, notes) VALUES (?,?,?,?,?)");
            $stmt->bind_param("issss", $eid, $bid, $bname, $erd, $notes);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'checkouts' && $ajax === 'return' && $id) {
    header('Content-Type: application/json');
    try {
        $stmt = $db->prepare("SELECT equipment_id FROM lab_checkouts WHERE id=?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $r = $stmt->get_result();
        if ($c = $r->fetch_assoc()) {
            $stmt->close();
            $stmt = $db->prepare("UPDATE lab_checkouts SET actual_return=CURDATE(), status='returned' WHERE id=?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            echo json_encode(['success' => true]);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'error' => 'Not found']);
        }
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'checkouts' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_checkouts WHERE id=" . intval($id)); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Sessions CRUD
if ($view === 'sessions' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            if ($q) {
                $like = '%' . $q . '%';
                $stmt = $db->prepare("SELECT * FROM lab_sessions WHERE session_name LIKE ? OR instructor_name LIKE ? ORDER BY scheduled_date DESC LIMIT 200");
                $stmt->bind_param("ss", $like, $like);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $r = $stmt->get_result();
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            } else {
                $r = $db->query("SELECT * FROM lab_sessions ORDER BY scheduled_date DESC LIMIT 200");
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
            }
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'sessions' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $sname = $data['session_name'] ?? '';
    $iid = (int)($data['instructor_id'] ?? 0);
    $iname = $data['instructor_name'] ?? '';
    $sdate = $data['scheduled_date'] ?? '';
    $stime = $data['scheduled_time'] ?: null;
    $dur = (int)($data['duration_minutes'] ?? 60);
    $max = (int)($data['max_students'] ?? 30);
    $room = $data['room'] ?? '';
    $stat = $data['status'] ?? 'scheduled';
    $notes = $data['notes'] ?? '';
    try {
        if ($id) {
            $stmt = $db->prepare("UPDATE lab_sessions SET session_name=?, instructor_id=?, instructor_name=?, scheduled_date=?, scheduled_time=?, duration_minutes=?, max_students=?, room=?, status=?, notes=? WHERE id=?");
            $stmt->bind_param("sisssiisssi", $sname, $iid, $iname, $sdate, $stime, $dur, $max, $room, $stat, $notes, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        } else {
            $stmt = $db->prepare("INSERT INTO lab_sessions (session_name, instructor_id, instructor_name, scheduled_date, scheduled_time, duration_minutes, max_students, room, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sisssiisss", $sname, $iid, $iname, $sdate, $stime, $dur, $max, $room, $stat, $notes);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'sessions' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_sessions WHERE id=" . intval($id)); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Skills demonstrations
if ($view === 'skills' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            if ($q) {
                $like = '%' . $q . '%';
                $stmt = $db->prepare("SELECT d.*, s.session_name FROM lab_demonstrations d LEFT JOIN lab_sessions s ON d.session_id=s.id WHERE d.skill_name LIKE ? OR d.description LIKE ? ORDER BY d.demo_date DESC LIMIT 200");
                $stmt->bind_param("ss", $like, $like);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $r = $stmt->get_result();
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            } else {
                $r = $db->query("SELECT d.*, s.session_name FROM lab_demonstrations d LEFT JOIN lab_sessions s ON d.session_id=s.id ORDER BY d.demo_date DESC LIMIT 200");
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
            }
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'skills' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $sesid = (int)($data['session_id'] ?? 0);
    $skn = $data['skill_name'] ?? '';
    $desc = $data['description'] ?? '';
    $iid = (int)($data['instructor_id'] ?? 0);
    $ddate = $data['demo_date'] ?? date('Y-m-d');
    $scount = (int)($data['students_count'] ?? 0);
    try {
        if ($id) {
            $stmt = $db->prepare("UPDATE lab_demonstrations SET session_id=?, skill_name=?, description=?, instructor_id=?, demo_date=?, students_count=? WHERE id=?");
            $stmt->bind_param("isssiii", $sesid, $skn, $desc, $iid, $ddate, $scount, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        } else {
            $stmt = $db->prepare("INSERT INTO lab_demonstrations (session_id, skill_name, description, instructor_id, demo_date, students_count) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("isssii", $sesid, $skn, $desc, $iid, $ddate, $scount);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'skills' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_demonstrations WHERE id=" . intval($id)); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Consumables CRUD
if ($view === 'consumables' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            if ($q) {
                $like = '%' . $q . '%';
                $stmt = $db->prepare("SELECT * FROM lab_consumables WHERE item_name LIKE ? OR category LIKE ? ORDER BY item_name ASC");
                $stmt->bind_param("ss", $like, $like);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $r = $stmt->get_result();
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            } else {
                $r = $db->query("SELECT * FROM lab_consumables ORDER BY item_name ASC");
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
            }
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'consumables' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $in = $data['item_name'] ?? '';
    $cat = $data['category'] ?? '';
    $qty = (float)($data['quantity'] ?? 0);
    $unit = $data['unit'] ?? 'pieces';
    $msl = (float)($data['min_stock_level'] ?? 10);
    $uc = (float)($data['unit_cost'] ?? 0);
    $supp = $data['supplier'] ?? '';
    $lod = $data['last_ordered_date'] ?: null;
    $notes = $data['notes'] ?? '';
    try {
        if ($id) {
            $stmt = $db->prepare("UPDATE lab_consumables SET item_name=?, category=?, quantity=?, unit=?, min_stock_level=?, unit_cost=?, supplier=?, last_ordered_date=?, notes=? WHERE id=?");
            $stmt->bind_param("sssd ddssii", $in, $cat, $qty, $unit, $msl, $uc, $supp, $lod, $notes, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        } else {
            $stmt = $db->prepare("INSERT INTO lab_consumables (item_name, category, quantity, unit, min_stock_level, unit_cost, supplier, last_ordered_date, notes) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssd ddsis", $in, $cat, $qty, $unit, $msl, $uc, $supp, $lod, $notes);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'consumables' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_consumables WHERE id=" . intval($id)); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Lab Attendance
if ($view === 'attendance' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
            if ($sessionId) {
                $stmt = $db->prepare("SELECT a.*, s.session_name AS session_title, s.scheduled_date AS session_date FROM lab_attendance a JOIN lab_sessions s ON a.session_id=s.id WHERE a.session_id=? ORDER BY a.created_at DESC LIMIT 300");
                $stmt->bind_param("i", $sessionId);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $r = $stmt->get_result();
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            } else {
                $r = $db->query("SELECT a.*, s.session_name AS session_title, s.scheduled_date AS session_date FROM lab_attendance a JOIN lab_sessions s ON a.session_id=s.id ORDER BY a.created_at DESC LIMIT 300");
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
            }
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
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
    $stmt = $db->prepare("INSERT INTO lab_attendance (session_id, student_id, attendance_status, check_in_time, marked_by) VALUES (?, ?, ?, CURTIME(), ?) ON DUPLICATE KEY UPDATE attendance_status=?, marked_by=?");
    foreach ($students as $s) {
        $stid = $s['student_id'] ?? '';
        $stat = $s['attendance_status'] ?? 'present';
        if (!$stid) continue;
        try {
            $stmt->bind_param("isssii", $sid, $stid, $stat, $mid, $stat, $mid);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $success++;
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
    }
    $stmt->close();
    echo json_encode(['success' => true, 'updated' => $success]); exit;
}
if ($view === 'attendance' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_attendance WHERE id=" . intval($id)); echo json_encode(['success' => true]); }
    catch (Exception $e) { echo json_encode(['success' => false]); }
    exit;
}

// Sessions list for dropdown
if ($view === 'attendance' && $ajax === 'sessions') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            $r = $db->query("SELECT id, session_name, scheduled_date FROM lab_sessions ORDER BY scheduled_date DESC LIMIT 50");
            if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
    }
    echo json_encode($rows); exit;
}

// Incidents CRUD
if ($view === 'incidents' && $ajax === 'get') {
    header('Content-Type: application/json');
    $rows = [];
    if ($db) {
        try {
            if ($q) {
                $like = '%' . $q . '%';
                $stmt = $db->prepare("SELECT * FROM lab_incidents WHERE description LIKE ? OR incident_type LIKE ? ORDER BY incident_date DESC, incident_time DESC LIMIT 200");
                $stmt->bind_param("ss", $like, $like);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $r = $stmt->get_result();
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            } else {
                $r = $db->query("SELECT * FROM lab_incidents ORDER BY incident_date DESC, incident_time DESC LIMIT 200");
                if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
            }
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
    }
    echo json_encode(['data' => $rows]); exit;
}
if ($view === 'incidents' && $ajax === 'save') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);
    $idate = $data['incident_date'] ?? date('Y-m-d');
    $itime = $data['incident_time'] ?: null;
    $itype = $data['incident_type'] ?? 'other';
    $sev = $data['severity'] ?? 'minor';
    $desc = $data['description'] ?? '';
    $ei = $data['equipment_involved'] ?? '';
    $si = $data['student_involved'] ?? '';
    $at = $data['action_taken'] ?? '';
    $stat = $data['status'] ?? 'open';
    $uid = (int)($user['id'] ?? 0);
    try {
        if ($id) {
            $stmt = $db->prepare("UPDATE lab_incidents SET incident_date=?, incident_time=?, incident_type=?, severity=?, description=?, equipment_involved=?, student_involved=?, action_taken=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssssi", $idate, $itime, $itype, $sev, $desc, $ei, $si, $at, $stat, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        } else {
            $stmt = $db->prepare("INSERT INTO lab_incidents (incident_date, incident_time, reported_by, incident_type, severity, description, equipment_involved, student_involved, action_taken, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssiissssss", $idate, $itime, $uid, $itype, $sev, $desc, $ei, $si, $at, $stat);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
    exit;
}
if ($view === 'incidents' && $ajax === 'delete' && $id) {
    header('Content-Type: application/json');
    try { $db->query("DELETE FROM lab_incidents WHERE id=" . intval($id)); echo json_encode(['success' => true]); }
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
            $r = $db->query("SELECT COUNT(*) FROM lab_checkouts WHERE status='checked_out'"); if ($r) $stats['active_checkouts'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_checkouts WHERE status='checked_out' AND expected_return < CURDATE()"); if ($r) $stats['overdue'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_sessions WHERE status='scheduled'"); if ($r) $stats['sessions'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_equipment WHERE status='maintenance'"); if ($r) $stats['pending_maintenance'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_consumables WHERE quantity <= min_stock_level"); if ($r) $stats['low_stock'] = (int)$r->fetch_row()[0];
            $r = $db->query("SELECT COUNT(*) FROM lab_incidents WHERE status IN ('open','investigating')"); if ($r) $stats['incidents'] = (int)$r->fetch_row()[0];
        } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
    }
    echo json_encode($stats); exit;
}

// â”€â”€ Stats for home page (PHP-side initial) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$equipment_count = 0; $checkout_count = 0; $overdue_count = 0; $scheduled_sessions = 0;
$maintenance_count = 0; $low_stock_count = 0; $incident_count = 0; $total_students = 0;
if ($db) {
    try {
        $r = $db->query("SELECT COUNT(*) FROM lab_equipment"); if ($r) $equipment_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_checkouts WHERE status='checked_out'"); if ($r) $checkout_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_checkouts WHERE status='checked_out' AND expected_return < CURDATE()"); if ($r) $overdue_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_sessions WHERE status IN ('scheduled','ongoing')"); if ($r) $scheduled_sessions = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_equipment WHERE status='maintenance'"); if ($r) $maintenance_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_consumables WHERE quantity <= min_stock_level"); if ($r) $low_stock_count = (int)$r->fetch_row()[0];
        $r = $db->query("SELECT COUNT(*) FROM lab_incidents WHERE status IN ('open','investigating')"); if ($r) $incident_count = (int)$r->fetch_row()[0];
    } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
}

// Get students for dropdowns
$students_list = [];
if ($students) {
    try {
        $r = $students->query("SELECT id, admission_number, first_name, last_name FROM students WHERE status='Active' ORDER BY first_name ASC LIMIT 500");
        if ($r) $students_list = $r->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) { error_log('skills-lab context: ' . $e->getMessage()); }
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

.badge-status { padding: .35em .65em; font-size: .78rem; font-weight: 500; border-radius: 50px; }
.alert-low-stock { border-left: 4px solid var(--sl-warning); }
.alert-overdue { border-left: 4px solid var(--sl-danger); }
.alert-maintenance { border-left: 4px solid var(--sl-info); }

.skl-content{margin-left:270px;padding:24px;min-height:100vh}
@media(max-width:768px){.skl-content{margin-left:0!important;padding:12px!important}}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="skl-content">

    <div id="overview" class="content-section dashboard-section active container-fluid py-4 px-4" data-section="overview">
        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6"><div class="card stats-card-lab"><div class="card-body d-flex align-items-center gap-3">
                <div class="stats-icon" style="background:rgba(13,110,253,.12);color:var(--sl-primary)"><i class="fas fa-tools"></i></div>
                <div><h3 class="fw-bold mb-0" id="stat-equipment"><?= $equipment_count ?></h3> <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button><small class="text-muted">Equipment Items</small></div>
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


        <script>
        (function loadStats(){ fetch('?view=home&ajax=stats').then(r=>r.json()).then(d=>{
            if(d.equipment!==undefined){ ['equipment','checkouts','overdue','sessions','maintenance'].forEach(k=>{
                const el=document.getElementById('stat-'+k); if(el) el.textContent=d[k];
            });
            document.getElementById('stat-lowstock')&&(document.getElementById('stat-lowstock').textContent=d.low_stock);
            document.getElementById('stat-incidents')&&(document.getElementById('stat-incidents').textContent=d.incidents);}
        }).catch(function(e){ console.warn('[ISNM] Stats load failed:', e); }); })();
        </script>

    </div><!-- /overview -->

    <div id="equipment" class="content-section dashboard-section container-fluid py-4 px-4" data-section="equipment">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-tools me-2"></i>Equipment Inventory</h4>
            <div>
                <input type="text" id="eq-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search equipment...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openEqModal()"><i class="fas fa-plus me-1"></i>Add Equipment</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchVFHR" type="text" placeholder="Search..." onkeyup="filterTable('srchVFHR','tblVFHR')"></div>
<div class="table-responsive">
            <table class="table table-hover mb-0" id="eq-table"><thead class="table-light"><tr>
                <th>Code</th><th>Name</th><th>Category</th><th>Qty</th><th>Condition</th><th>Location</th><th>Status</th><th style="width:120px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="eqModal"><div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="eqModalTitle">Add Equipment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="eqForm">
                <input type="hidden" name="id" id="eq-id">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Equipment Code *</label><input type="text" name="equipment_code" id="eq-code" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Equipment Name *</label><input type="text" name="equipment_name" id="eq-name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Category *</label><select name="category" id="eq-cat" class="form-select"><option value="mannequin">Mannequin</option><option value="model">Model</option><option value="instrument">Instrument</option><option value="furniture">Furniture</option><option value="consumable">Consumable</option><option value="other">Other</option></select></div>
                    <div class="col-md-3"><label class="form-label">Quantity *</label><input type="number" name="quantity" id="eq-qty" class="form-control" value="1" min="1"></div>
                    <div class="col-md-3"><label class="form-label">Condition</label><select name="condition_status" id="eq-cond" class="form-select"><option value="excellent">Excellent</option><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select></div>
                    <div class="col-md-3"><label class="form-label">Status</label><select name="status" id="eq-stat" class="form-select"><option value="active">Active</option><option value="maintenance">Maintenance</option><option value="retired">Retired</option></select></div>
                    <div class="col-md-6"><label class="form-label">Location</label><input type="text" name="location" id="eq-loc" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Last Maintenance</label><input type="date" name="last_maintenance" id="eq-lmaint" class="form-control"></div>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveEq()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>
    </div>

    <div id="checkouts" class="content-section dashboard-section container-fluid py-4 px-4" data-section="checkouts">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-hand-holding me-2"></i>Equipment Check out / Check in</h4>
            <div>
                <input type="text" id="co-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search student or equipment...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openCoModal()"><i class="fas fa-plus me-1"></i>New Check out</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchHMNX" type="text" placeholder="Search..." onkeyup="filterTable('srchHMNX','tblHMNX')"></div>
<div class="table-responsive">
            <table class="table table-hover mb-0" id="co-table"><thead class="table-light"><tr>
                <th>ID</th><th>Equipment</th><th>Borrower</th><th>Check out</th><th>Expected Return</th><th>Status</th><th style="width:140px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="coModal"><div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="coModalTitle">New Check out</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="coForm">
                <input type="hidden" name="id" id="co-id">
                <div class="mb-3"><label class="form-label">Equipment *</label><select name="equipment_id" id="co-eid" class="form-select" required><option value="">-- Select Equipment --</option></select></div>
                <div class="mb-3"><label class="form-label">Borrower ID *</label><input type="text" name="borrower_id" id="co-bid" class="form-control" list="studentList" required></div>
                <div class="mb-3"><label class="form-label">Borrower Name</label><input type="text" name="borrower_name" id="co-bname" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Expected Return *</label><input type="date" name="expected_return" id="co-erd" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" id="co-notes" class="form-control" rows="2"></textarea></div>
                <div id="co-return-fields" style="display:none">
                    <hr><h6>Return</h6>
                    <div class="mb-3"><label class="form-label">Actual Return</label><input type="date" name="actual_return" id="co-ard" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Status</label><select name="status" id="co-stat" class="form-select"><option value="checked_out">Checked Out</option><option value="returned">Returned</option><option value="lost_damaged">Lost / Damaged</option></select></div>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveCo()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>
    </div>

    <div id="sessions" class="content-section dashboard-section container-fluid py-4 px-4" data-section="sessions">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-calendar-alt me-2"></i>Practical Sessions</h4>
            <div>
                <input type="text" id="ses-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search sessions...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openSesModal()"><i class="fas fa-plus me-1"></i>New Session</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchSBQM" type="text" placeholder="Search..." onkeyup="filterTable('srchSBQM','tblSBQM')"></div>
<div class="table-responsive">
            <table class="table table-hover mb-0" id="ses-table"><thead class="table-light"><tr>
                <th>Session Name</th><th>Date</th><th>Time</th><th>Instructor</th><th>Room</th><th>Status</th><th style="width:120px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="sesModal"><div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="sesModalTitle">New Session</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="sesForm">
                <input type="hidden" name="id" id="ses-id">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Session Name *</label><input type="text" name="session_name" id="ses-name" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Instructor ID</label><input type="number" name="instructor_id" id="ses-iid" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Instructor Name</label><input type="text" name="instructor_name" id="ses-iname" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Date *</label><input type="date" name="scheduled_date" id="ses-date" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Time</label><input type="time" name="scheduled_time" id="ses-st" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Duration (min)</label><input type="number" name="duration_minutes" id="ses-dur" class="form-control" value="60"></div>
                    <div class="col-md-2"><label class="form-label">Max Students</label><input type="number" name="max_students" id="ses-max" class="form-control" value="30"></div>
                    <div class="col-md-4"><label class="form-label">Room</label><input type="text" name="room" id="ses-room" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Status</label><select name="status" id="ses-stat" class="form-select"><option value="scheduled">Scheduled</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" id="ses-notes" class="form-control" rows="2"></textarea></div>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveSes()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>
    </div>

    <div id="skills" class="content-section dashboard-section container-fluid py-4 px-4" data-section="skills">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-certificate me-2"></i>Skills Demonstrations</h4>
            <div>
                <input type="text" id="sk-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search skill or student...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openSkModal()"><i class="fas fa-plus me-1"></i>Record Skill</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchHCLI" type="text" placeholder="Search..." onkeyup="filterTable('srchHCLI','tblHCLI')"></div>
<div class="table-responsive">
            <table class="table table-hover mb-0" id="sk-table"><thead class="table-light"><tr>
                <th>Skill</th><th>Description</th><th>Session</th><th>Demo Date</th><th>Instructor ID</th><th>Students</th><th style="width:100px">Actions</th>
            </tr></thead><tbody></tbody></table>
        </div></div></div>
        <div class="modal fade" id="skModal"><div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 id="skModalTitle">Record Skill Demonstration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="skForm">
                <input type="hidden" name="id" id="sk-id">
                <div class="mb-3"><label class="form-label">Session *</label><select name="session_id" id="sk-sesid" class="form-select" required><option value="">-- Select Session --</option></select></div>
                <div class="mb-3"><label class="form-label">Skill Name *</label><input type="text" name="skill_name" id="sk-name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="sk-desc" class="form-control" rows="2"></textarea></div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><label class="form-label">Instructor ID</label><input type="number" name="instructor_id" id="sk-iid" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Demo Date</label><input type="date" name="demo_date" id="sk-date" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Students Count</label><input type="number" name="students_count" id="sk-scount" class="form-control" value="0" min="0"></div>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveSk()"><i class="fas fa-save me-1"></i>Save</button></div>
        </div></div></div>
    </div>

    <div id="consumables" class="content-section dashboard-section container-fluid py-4 px-4" data-section="consumables">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-boxes me-2"></i>Consumables Inventory</h4>
            <div>
                <input type="text" id="con-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search items...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openConModal()"><i class="fas fa-plus me-1"></i>Add Item</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchJVCH" type="text" placeholder="Search..." onkeyup="filterTable('srchJVCH','tblJVCH')"></div>
<div class="table-responsive">
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
    </div>

    <div id="attendance" class="content-section dashboard-section container-fluid py-4 px-4" data-section="attendance">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-list me-2"></i>Lab Attendance</h4>
            <div>
                <select id="att-session-filter" class="form-select form-select-sm d-inline-block" style="width:300px" onchange="loadAtt()"><option value="">-- All Sessions --</option></select>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchPGBA" type="text" placeholder="Search..." onkeyup="filterTable('srchPGBA','tblPGBA')"></div>
<div class="table-responsive">
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
    </div>

    <div id="incidents" class="content-section dashboard-section container-fluid py-4 px-4" data-section="incidents">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Incident Reports</h4>
            <div>
                <input type="text" id="inc-search" class="form-control form-control-sm d-inline-block" style="width:250px" placeholder="Search incidents...">
                <button class="btn btn-primary btn-sm ms-2" onclick="openIncModal()"><i class="fas fa-plus me-1"></i>Report Incident</button>
            </div>
        </div>
        <div class="card"><div class="card-body p-0"><div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchKROW" type="text" placeholder="Search..." onkeyup="filterTable('srchKROW','tblKROW')"></div>
<div class="table-responsive">
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
    </div>

</div><!-- /skl-content -->

<!-- Student datalist -->
<datalist id="studentList">
<?php foreach ($students_list as $s): ?>
    <option value="<?= htmlspecialchars($s['admission_number']) ?>"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></option>
<?php endforeach; ?>
</datalist>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<script>
// â”€â”€ Shared helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Data Loader â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Equipment â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function loadEq() { loadTable('?view=equipment&ajax=get', 'eq-table', r => `<tr>
    <td>${esc(r.equipment_code)}</td><td>${esc(r.equipment_name)}</td><td><span class="badge bg-secondary">${esc(r.category)}</span></td>
    <td>${r.quantity}</td>
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
    setVal('eq-name', data?.equipment_name||'');
    setVal('eq-cat', data?.category||'other');
    setVal('eq-qty', data?.quantity||1);
    setVal('eq-cond', data?.condition_status||'good');
    setVal('eq-stat', data?.status||'active');
    setVal('eq-loc', data?.location||'');
    setVal('eq-lmaint', data?.last_maintenance||'');
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
    data.csrf_token = window.CSRF_TOKEN;
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

// â”€â”€ Checkouts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function loadCo() { loadTable('?view=checkouts&ajax=get', 'co-table', r => `<tr class="${r.expected_return < todayStr() && r.status==='checked_out'?'table-danger':''}">
    <td>${r.id}</td><td>${esc(r.equipment_name||'')} (${esc(r.equipment_code||'')})</td><td>${esc(r.borrower_id)}</td>
    <td>${r.checkout_date ? r.checkout_date.substring(0,10) : ''}</td>
    <td>${esc(r.expected_return)}</td>
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
    setVal('co-bid', data?.borrower_id||'');
    setVal('co-bname', data?.borrower_name||'');
    setVal('co-erd', data?.expected_return||'');
    setVal('co-notes', data?.notes||'');
    if (data?.id) {
        setVal('co-ard', data?.actual_return ? data.actual_return.substring(0,10) : '');
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
    data.csrf_token = window.CSRF_TOKEN;
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

// â”€â”€ Sessions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function loadSes() { loadTable('?view=sessions&ajax=get', 'ses-table', r => `<tr>
    <td><strong>${esc(r.session_name)}</strong></td>
    <td>${esc(r.scheduled_date)}</td>
    <td>${r.scheduled_time ? r.scheduled_time.substring(0,5) : ''}</td>
    <td>${esc(r.instructor_name)}</td><td>${esc(r.room)}</td>
    <td><span class="badge-status bg-${r.status==='scheduled'?'primary':r.status==='ongoing'?'success':r.status==='completed'?'secondary':'danger'}">${esc(r.status)}</span></td>
    <td>
        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editSes(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteSes(${r.id})"><i class="fas fa-trash"></i></button>
    </td>
</tr>`); }
function openSesModal(data) {
    setVal('ses-id', data?.id||''); setVal('ses-name', data?.session_name||'');
    setVal('ses-iid', data?.instructor_id||''); setVal('ses-iname', data?.instructor_name||'');
    setVal('ses-date', data?.scheduled_date||''); setVal('ses-st', data?.scheduled_time||'');
    setVal('ses-dur', data?.duration_minutes||60); setVal('ses-max', data?.max_students||30);
    setVal('ses-room', data?.room||'');
    setVal('ses-stat', data?.status||'scheduled');
    setVal('ses-notes', data?.notes||'');
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
    data.csrf_token = window.CSRF_TOKEN;
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

// â”€â”€ Skills â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function loadSk() { loadTable('?view=skills&ajax=get', 'sk-table', r => `<tr>
    <td><strong>${esc(r.skill_name)}</strong></td><td>${esc(r.description||'').substring(0,40)}</td>
    <td>${esc(r.session_name||'')}</td>
    <td>${esc(r.demo_date)}</td>
    <td>${esc(r.instructor_id||'')}</td><td>${r.students_count}</td>
    <td>
        <button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editSk(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="deleteSk(${r.id})"><i class="fas fa-trash"></i></button>
    </td>
</tr>`); }
function openSkModal(data) {
    setVal('sk-id', data?.id||'');
    setVal('sk-name', data?.skill_name||'');
    setVal('sk-desc', data?.description||'');
    setVal('sk-iid', data?.instructor_id||'');
    setVal('sk-date', data?.demo_date||new Date().toISOString().substring(0,10));
    setVal('sk-scount', data?.students_count||0);
    loadSkSessionList(data?.session_id);
    document.getElementById('skModalTitle').textContent = data?.id ? 'Edit Demonstration' : 'Record Demonstration';
    new bootstrap.Modal(document.getElementById('skModal')).show();
}
function loadSkSessionList(selectedId) {
    fetch('?view=attendance&ajax=sessions').then(r=>r.json()).then(d => {
        const sel = document.getElementById('sk-sesid');
        if (sel) sel.innerHTML = '<option value="">-- Select Session --</option>' + d.map(x => `<option value="${x.id}" ${x.id==selectedId?'selected':''}>${esc(x.session_name)} (${x.scheduled_date})</option>`).join('');
    });
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
    data.csrf_token = window.CSRF_TOKEN;
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

// â”€â”€ Consumables â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
    data.csrf_token = window.CSRF_TOKEN;
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

// â”€â”€ Attendance â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
        if (sel) sel.innerHTML = '<option value="">-- All Sessions --</option>' + d.map(x => `<option value="${x.id}">${esc(x.session_name)} (${x.scheduled_date})</option>`).join('');
        const sel2 = document.getElementById('att-session-id');
        if (sel2) sel2.innerHTML = '<option value="">-- Select Session --</option>' + d.map(x => `<option value="${x.id}">${esc(x.session_name)} (${x.scheduled_date})</option>`).join('');
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
    fetch('?view=attendance&ajax=save', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({session_id: sid, students, csrf_token: window.CSRF_TOKEN})})
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

// â”€â”€ Incidents â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
    data.csrf_token = window.CSRF_TOKEN;
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

// â”€â”€ Live search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€â”€ Escaping â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// â”€â”€ Init â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.addEventListener('DOMContentLoaded', function() {
    const view = window.location.hash.replace('#', '') || 'home';
    if (view==='equipment') loadEq();
    else if (view==='checkouts') { loadCo(); loadCoEquipmentList(); }
    else if (view==='sessions') loadSes();
    else if (view==='skills') loadSk();
    else if (view==='consumables') loadCon();
    else if (view==='attendance') { loadAttSessions(); loadAtt(); }
    else if (view==='incidents') loadInc();
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
</body>
</html>