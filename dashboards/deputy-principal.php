<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['deputy', 'principal']);
$staff = $ctx['staff']; $students = $ctx['students']; $website = $ctx['website'];
$user = $ctx['user']; $uid = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? ''; $uname = $_SESSION['full_name'] ?? 'Deputy Principal';
$staff_db   = defined('STAFF_DB_NAME')    ? STAFF_DB_NAME    : 'igangaschoolofl_staffs_db';
$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschoolofl_students_db';
$migrate = function($db) use ($staff_db, $students_db) {
    if (!$db) return;
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.meeting_minutes (id INT AUTO_INCREMENT PRIMARY KEY, meeting_id INT, agenda_item VARCHAR(300), discussion TEXT, resolution TEXT, action_items TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.meeting_actions (id INT AUTO_INCREMENT PRIMARY KEY, meeting_id INT, action_item TEXT, assigned_to VARCHAR(200), due_date DATE, status ENUM('pending','in_progress','completed') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.student_discipline (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, offense TEXT, reported_by VARCHAR(200), hearing_date DATE, outcome VARCHAR(500), action_taken VARCHAR(200), status ENUM('open','resolved','appealed') DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.student_discipline_records (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, violation_type VARCHAR(200), description TEXT, severity ENUM('low','medium','high') DEFAULT 'medium', action_taken VARCHAR(200), status ENUM('pending','resolved','appealed') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.student_welfare_cases (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, case_type VARCHAR(200), description TEXT, severity ENUM('low','medium','high','critical') DEFAULT 'medium', status ENUM('open','in_progress','resolved','closed') DEFAULT 'open', assigned_to VARCHAR(200), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.student_appeals (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, appeal_type VARCHAR(200), reason TEXT, outcome VARCHAR(500), status ENUM('pending','approved','rejected') DEFAULT 'pending', reviewed_by INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.quality_assurance (id INT AUTO_INCREMENT PRIMARY KEY, review_title VARCHAR(300), review_type VARCHAR(200), department VARCHAR(200), reviewer VARCHAR(200), score DECIMAL(5,2), findings TEXT, recommendations TEXT, status ENUM('draft','completed','reviewed') DEFAULT 'draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.communication_log (id INT AUTO_INCREMENT PRIMARY KEY, sender_id INT, sender_name VARCHAR(200), recipient_role VARCHAR(100), subject VARCHAR(300), message TEXT, is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.department_performance (id INT AUTO_INCREMENT PRIMARY KEY, department VARCHAR(200), metric VARCHAR(200), value DECIMAL(14,2), period VARCHAR(50), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.compliance_tracking (id INT AUTO_INCREMENT PRIMARY KEY, department VARCHAR(200), compliance_type VARCHAR(200), status ENUM('compliant','non_compliant','pending_review') DEFAULT 'pending_review', notes TEXT, reviewed_by VARCHAR(200), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.improvement_tracking (id INT AUTO_INCREMENT PRIMARY KEY, area VARCHAR(200), improvement_action TEXT, target_date DATE, progress DECIMAL(5,2) DEFAULT 0, status ENUM('planned','in_progress','completed') DEFAULT 'planned', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.deputy_tasks (id INT AUTO_INCREMENT PRIMARY KEY, task_title VARCHAR(300), description TEXT, assigned_by VARCHAR(200), priority ENUM('low','medium','high','urgent') DEFAULT 'medium', status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$staff_db}.teaching_quality_reviews (id INT AUTO_INCREMENT PRIMARY KEY, lecturer_id INT, review_date DATE, teaching_score DECIMAL(5,2), course_code VARCHAR(50), observer VARCHAR(200), feedback TEXT, status ENUM('draft','completed','reviewed') DEFAULT 'draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
$migrate($staff); $migrate($students);
$_GET['section'] = $_GET['section'] ?? $_GET['view'] ?? 'overview';
$view = $_GET['section']; if ($view === 'overview') $view = 'home';
$ajax = $_GET['ajax'] ?? ''; $sid = $_GET['sid'] ?? ''; $q = $_GET['q'] ?? '';
function currency($n) { return 'UGX ' . number_format((float)$n, 0); }
function dep_success($m) { $_SESSION['dep_success'] = $m; }
function dep_error($m) { $_SESSION['dep_error'] = $m; }
function safeCount($c, $s) { $r = $c->query($s); if (!$r) return 0; $w = $r->fetch_assoc(); return intval($w['c'] ?? 0); }

// ── AJAX DATA ENDPOINTS ─────────────────────────────────────

if ($view === 'deputy_stats' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $ts = $students ? safeCount($students, "SELECT COUNT(*)c FROM students WHERE status='Active'") : 0;
    $as = safeCount($staff, "SELECT COUNT(*)c FROM staff WHERE status='Active'");
    $ar = $students ? round(safeCount($students, "SELECT COUNT(*)c FROM student_attendance WHERE status='Present'") / max(1, safeCount($students, "SELECT COUNT(*)c FROM student_attendance")) * 100, 1) : 0;
    $pt = safeCount($staff, "SELECT COUNT(*)c FROM {$students_db}.deputy_tasks WHERE status='pending' OR status='in_progress'");
    $da = safeCount($staff, "SELECT COUNT(*)c FROM {$students_db}.department_performance WHERE value<50");
    $ua = safeCount($staff, "SELECT COUNT(*)c FROM {$students_db}.communication_log WHERE is_read=0 AND recipient_role='deputy'");
    echo json_encode(['total_students'=>$ts,'active_staff'=>$as,'attendance_rate'=>$ar,'pending_tasks'=>$pt,'department_alerts'=>$da,'unread_messages'=>$ua]); exit;
}
if ($view === 'class_monitoring_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT t.*,c.full_name lecturer_name FROM academic_timetable t LEFT JOIN staff c ON t.lecturer_id=c.id ORDER BY t.day_of_week,t.start_time LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'attendance_monitoring_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $course = $_GET['course'] ?? '';
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    $sql = "SELECT a.*, s.full_name, s.student_number FROM student_attendance a JOIN students s ON a.student_id=s.id WHERE 1=1";
    $params = [];
    $types = '';
    if ($course) { $sql .= " AND a.subject=?"; $types .= 's'; $params[] = $course; }
    if ($from) { $sql .= " AND a.date>=?"; $types .= 's'; $params[] = $from; }
    if ($to) { $sql .= " AND a.date<=?"; $types .= 's'; $params[] = $to; }
    $sql .= " ORDER BY a.date DESC LIMIT 200";
    if (!empty($params)) {
        $stmt = $students->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $r = $stmt->get_result();
    } else {
        $r = $students ? $students->query($sql) : null;
    }
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    if (!empty($params)) $stmt->close();
    echo json_encode($rows); exit;
}
if ($view === 'clinical_placement_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT cp.*, s.full_name student_name FROM {$students_db}.clinical_placements_students cp LEFT JOIN {$students_db}.students s ON cp.student_id=s.id ORDER BY cp.created_at DESC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'welfare_cases_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT w.*, s.full_name student_name FROM {$students_db}.student_welfare_cases w LEFT JOIN {$students_db}.students s ON w.student_id=s.id ORDER BY w.created_at DESC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'discipline_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT d.*, s.full_name student_name FROM {$students_db}.student_discipline d LEFT JOIN {$students_db}.students s ON d.student_id=s.id ORDER BY d.created_at DESC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'student_support_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.student_appeals ORDER BY created_at DESC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'task_list' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.deputy_tasks ORDER BY created_at DESC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'compliance_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.compliance_tracking ORDER BY created_at DESC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'improvement_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.improvement_tracking ORDER BY created_at DESC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'teaching_quality_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT r.*, s.full_name lecturer_name FROM {$staff_db}.teaching_quality_reviews r LEFT JOIN {$staff_db}.staff s ON r.lecturer_id=s.id ORDER BY r.created_at DESC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'timetable_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT t.*, c.full_name lecturer_name FROM academic_timetable t LEFT JOIN staff c ON t.lecturer_id=c.id ORDER BY t.day_of_week,t.start_time LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'department_followup_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.department_performance ORDER BY created_at DESC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'approval_list_deputy' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.communication_log WHERE recipient_role='deputy' AND is_read=0 ORDER BY created_at DESC LIMIT 50");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}

// ── AJAX WRITE ENDPOINTS ────────────────────────────────────

if ($view === 'create_task' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $tt = $_POST['task_title'] ?? '';
    $desc = $_POST['description'] ?? '';
    $pr = $_POST['priority'] ?? 'medium';
    if ($tt) {
        $sn = $uname;
        $stmt = $staff->prepare("INSERT INTO {$students_db}.deputy_tasks (task_title,description,assigned_by,priority) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $tt, $desc, $sn, $pr);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true,'id'=>$staff->insert_id]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Write failed: '.$staff->error]); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Title required']); exit;
}
if ($view === 'update_task_status' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $tid = (int)($_POST['id'] ?? 0); $st = $_POST['status'] ?? '';
    if ($tid && $st) {
        $stmt = $staff->prepare("UPDATE {$students_db}.deputy_tasks SET status=? WHERE id=?");
        $stmt->bind_param("si", $st, $tid);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Update failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'record_welfare_case' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $sid = (int)($_POST['student_id'] ?? 0); $ct = $_POST['case_type'] ?? '';
    $desc = $_POST['description'] ?? '';
    $sev = $_POST['severity'] ?? 'medium';
    $ato = $_POST['assigned_to'] ?? '';
    if ($sid && $ct) {
        $stmt = $staff->prepare("INSERT INTO {$students_db}.student_welfare_cases (student_id,case_type,description,severity,assigned_to) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $sid, $ct, $desc, $sev, $ato);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Write failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Student and case type required']); exit;
}
if ($view === 'update_welfare_status' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $wid = (int)($_POST['id'] ?? 0); $st = $_POST['status'] ?? '';
    if ($wid && $st) {
        $stmt = $staff->prepare("UPDATE {$students_db}.student_welfare_cases SET status=? WHERE id=?");
        $stmt->bind_param("si", $st, $wid);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Update failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'record_discipline' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $sid = (int)($_POST['student_id'] ?? 0); $off = $_POST['offense'] ?? '';
    $hd = $_POST['hearing_date'] ?? '';
    $act = $_POST['action_taken'] ?? '';
    $rb = $uname;
    if ($sid && $off) {
        $stmt = $staff->prepare("INSERT INTO {$students_db}.student_discipline (student_id,offense,reported_by,hearing_date,action_taken) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $sid, $off, $rb, $hd, $act);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Write failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Student and offense required']); exit;
}
if ($view === 'update_discipline_status' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $did = (int)($_POST['id'] ?? 0); $st = $_POST['status'] ?? '';
    $out = $_POST['outcome'] ?? '';
    if ($did && $st) {
        if ($out) {
            $stmt = $staff->prepare("UPDATE {$students_db}.student_discipline SET status=?, outcome=? WHERE id=?");
            $stmt->bind_param("ssi", $st, $out, $did);
        } else {
            $stmt = $staff->prepare("UPDATE {$students_db}.student_discipline SET status=? WHERE id=?");
            $stmt->bind_param("si", $st, $did);
        }
        if ($stmt->execute()) { echo json_encode(['success'=>true]); $stmt->close(); exit; }
        echo json_encode(['success'=>false,'error'=>'Update failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'forward_approval' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $aid = (int)($_POST['id'] ?? 0); $comm = $_POST['recommendation'] ?? '';
    if ($aid) {
        $sn = $uname;
        $timestamp = date('Y-m-d H:i:s');
        $newMsg = "[Deputy Review by $sn at $timestamp: $comm]\n[Forwarded to Principal for final approval]";
        $stmt = $staff->prepare("UPDATE {$students_db}.communication_log SET is_read=1, message=CONCAT(message,'\n\n',?) WHERE id=?");
        $stmt->bind_param("si", $newMsg, $aid);
        $stmt->execute();
        $stmt->close();
        
        $subject = "Forwarded by Deputy: Review";
        $body = "Item #$aid reviewed by Deputy $sn. Recommendation: $comm";
        $stmt2 = $staff->prepare("INSERT INTO {$students_db}.communication_log (sender_id,sender_name,recipient_role,subject,message) VALUES (?,'$sn','principal',?,?)");
        $stmt2->bind_param("iss", $uid, $subject, $body);
        $stmt2->execute();
        $stmt2->close();
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'send_communication_deputy' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $rt = $_POST['recipient_role'] ?? '';
    $subj = $_POST['subject'] ?? '';
    $msg = $_POST['message'] ?? '';
    if ($subj && $msg && $rt && $rt !== 'institution') {
        $sn = $uname;
        $stmt = $staff->prepare("INSERT INTO {$students_db}.communication_log (sender_id,sender_name,recipient_role,subject,message) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $uid, $sn, $rt, $subj, $msg);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Send failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Subject, message, and valid recipient required. Institution-wide broadcasting not allowed.']); exit;
}
if ($view === 'record_compliance' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $dep = $_POST['department'] ?? '';
    $ct = $_POST['compliance_type'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $st = $_POST['status'] ?? 'pending_review';
    if ($dep && $ct) {
        $sn = $uname;
        $stmt = $staff->prepare("INSERT INTO {$students_db}.compliance_tracking (department,compliance_type,status,notes,reviewed_by) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $dep, $ct, $st, $notes, $sn);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Write failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Department and compliance type required']); exit;
}
if ($view === 'record_improvement' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $area = $_POST['area'] ?? '';
    $act = $_POST['improvement_action'] ?? '';
    $td = $_POST['target_date'] ?? '';
    if ($area && $act) {
        $stmt = $staff->prepare("INSERT INTO {$students_db}.improvement_tracking (area,improvement_action,target_date) VALUES (?,?,?)");
        $stmt->bind_param("sss", $area, $act, $td);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Write failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Area and action required']); exit;
}
if ($view === 'update_improvement_progress' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $iid = (int)($_POST['id'] ?? 0); $pr = (float)($_POST['progress'] ?? 0);
    $st = $_POST['status'] ?? '';
    if ($iid) {
        if ($st) {
            $stmt = $staff->prepare("UPDATE {$students_db}.improvement_tracking SET progress=?, status=? WHERE id=?");
            $stmt->bind_param("dsi", $pr, $st, $iid);
        } else {
            $stmt = $staff->prepare("UPDATE {$students_db}.improvement_tracking SET progress=? WHERE id=?");
            $stmt->bind_param("di", $pr, $iid);
        }
        if ($stmt->execute()) { echo json_encode(['success'=>true]); $stmt->close(); exit; }
        echo json_encode(['success'=>false,'error'=>'Update failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'submit_teaching_review' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $lid = (int)($_POST['lecturer_id'] ?? 0); $rd = $_POST['review_date'] ?? date('Y-m-d');
    $ts = (float)($_POST['teaching_score'] ?? 0); $cc = $_POST['course_code'] ?? '';
    $fb = $_POST['feedback'] ?? '';
    $ob = $uname;
    if ($lid && $cc) {
        $stmt = $staff->prepare("INSERT INTO {$staff_db}.teaching_quality_reviews (lecturer_id,review_date,teaching_score,course_code,observer,feedback) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isdsss", $lid, $rd, $ts, $cc, $ob, $fb);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Write failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Lecturer and course required']); exit;
}
if ($view === 'record_attendance' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $sid = (int)($_POST['student_id'] ?? 0); $dt = $_POST['date'] ?? date('Y-m-d');
    $st = $_POST['status'] ?? 'Present'; $sub = $_POST['subject'] ?? 'General';
    if ($sid) {
        $stmt = $staff->prepare("INSERT INTO {$students_db}.student_attendance (student_id,date,subject,status,recorded_by) VALUES (?,?,?,?,?)");
        $stmt->bind_param("isssi", $sid, $dt, $sub, $st, $uid);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Write failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Student required']); exit;
}
if ($view === 'schedule_placement' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $sid = (int)($_POST['student_id'] ?? 0); $site = $_POST['placement_site'] ?? '';
    $sup = $_POST['supervisor_name'] ?? '';
    $sd = $_POST['start_date'] ?? '';
    $ed = $_POST['end_date'] ?? '';
    if ($sid && $site) {
        $stmt = $staff->prepare("INSERT INTO {$students_db}.clinical_placements_students (student_id,placement_site,supervisor_name,start_date,end_date,status) VALUES (?,?,?,?,?,'Scheduled')");
        $stmt->bind_param("issss", $sid, $site, $sup, $sd, $ed);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Write failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Student and site required']); exit;
}
if ($view === 'update_placement_status' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $pid = (int)($_POST['id'] ?? 0); $st = $_POST['status'] ?? '';
    if ($pid && $st) {
        $stmt = $staff->prepare("UPDATE {$students_db}.clinical_placements_students SET status=? WHERE id=?");
        $stmt->bind_param("si", $st, $pid);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]); $stmt->close(); exit;
        }
        echo json_encode(['success'=>false,'error'=>'Update failed']); $stmt->close(); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode([]); exit; }

// ── POST HANDLERS ───────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];
    if ($act === 'add_student_deputy' && $students) {
        $fn  = trim($_POST['first_name'] ?? '');
        $sn  = trim($_POST['surname'] ?? '');
        $on  = trim($_POST['other_name'] ?? '');
        $gen = trim($_POST['gender'] ?? 'Female');
        $crs = trim($_POST['course'] ?? '');
        $yr  = intval($_POST['year'] ?? 1);
        $sem = trim($_POST['semester'] ?? 'Semester 1');
        $ph  = trim($_POST['phone'] ?? '');
        $em  = trim($_POST['email'] ?? '');
        $gn  = trim($_POST['guardian_name'] ?? '');
        $gp  = trim($_POST['guardian_phone'] ?? '');
        if ($fn && $sn && $crs) {
            $snum = 'STU'.date('Y').str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $full = trim("$fn $on $sn");
            $stmt = $students->prepare("INSERT INTO students (student_number,first_name,surname,other_name,full_name,gender,course,current_year,year,current_semester,phone,mobile_number,email,guardian_name,guardian_phone,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active',NOW())");
            $stmt->bind_param("ssssssiiissssss", $snum, $fn, $sn, $on, $full, $gen, $crs, $yr, $yr, $sem, $ph, $ph, $em, $gn, $gp);
            $stmt->execute();
            if ($students->affected_rows > 0) { dep_success("Student $full registered."); } else { dep_error('Failed: '.$students->error); }
            $stmt->close();
        } else { dep_error('Required fields missing.'); }
        header("Location: deputy-principal.php?section=home"); exit;
    }
    if ($act === 'schedule_class' && $staff) {
        $prog=$_POST['program_code']??'';
        $cc=$_POST['course_code']??'';
        $dow=$_POST['day_of_week']??'';
        $st=$_POST['start_time']??'';
        $et=$_POST['end_time']??'';
        $venue=$_POST['venue']??'';
        $lid=intval($_POST['lecturer_id']??0);
        $ay=$_POST['academic_year']??date('Y').'-'.(date('Y')+1);
        $sem=$_POST['semester']??'Semester 1';
        $tid='TT-'.date('Ymd').'-'.mt_rand(1000,9999);
        $stmt = $staff->prepare("INSERT INTO academic_timetable (timetable_id,academic_year,semester,program_code,course_code,day_of_week,start_time,end_time,venue,lecturer_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssi", $tid, $ay, $sem, $prog, $cc, $dow, $st, $et, $venue, $lid, $uid);
        $stmt->execute();
        if ($staff->affected_rows>0) { dep_success("Class scheduled: $cc ($dow $st-$et)"); } else { dep_error('Failed to schedule: '.$staff->error); }
        $stmt->close();
        header("Location: deputy-principal.php?section=class_monitoring"); exit;
    }
    if ($act === 'assign_lecturer' && $staff) {
        $lid=intval($_POST['lecturer_id']??0);
        $cc=$_POST['course_code']??'';
        $cn=$_POST['course_name']??'';
        $sem=$_POST['semester']??'Semester 1';
        $ay=$_POST['academic_year']??date('Y').'-'.(date('Y')+1);
        $rm=$_POST['classroom']??'';
        $stmt = $staff->prepare("INSERT INTO course_assignments (lecturer_id,course_code,course_name,semester,academic_year,classroom,assigned_by) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("isssssi", $lid, $cc, $cn, $sem, $ay, $rm, $uid);
        $stmt->execute();
        if ($staff->affected_rows>0) { dep_success("Lecturer assigned to $cn"); } else { dep_error('Assignment failed: '.$staff->error); }
        $stmt->close();
        header("Location: deputy-principal.php?section=academic_monitoring"); exit;
    }
    if ($act === 'upload_material' && $staff) {
        $title=$_POST['material_title']??'';
        $dtype=$_POST['document_type']??'Teaching Material';
        if ($title && isset($_FILES['material_file']) && $_FILES['material_file']['error']===UPLOAD_ERR_OK) {
            $dir=__DIR__.'/../uploads/teaching_materials';
            if (!is_dir($dir)) @mkdir($dir,0755,true);
            $ext=strtolower(pathinfo($_FILES['material_file']['name'],PATHINFO_EXTENSION));
            $fname=time().'_'.preg_replace('/[^a-z0-9]/i','_',$title).'.'.$ext;
            $dest=$dir.'/'.$fname;
            if (move_uploaded_file($_FILES['material_file']['tmp_name'],$dest)) {
                $fpath="uploads/teaching_materials/$fname";
                $stmt = $staff->prepare("INSERT INTO generated_documents (document_type,generated_by,document_title,file_path) VALUES (?,?,?,?)");
                $stmt->bind_param("siss", $dtype, $uid, $title, $fpath);
                $stmt->execute();
                $stmt->close();
                dep_success("Material '$title' uploaded.");
            } else { dep_error('Upload failed.'); }
        } else { dep_error('Title and file required.'); }
        header("Location: deputy-principal.php?section=academic_monitoring"); exit;
    }
    if ($act === 'clinical_placement' && $students) {
        $sid=intval($_POST['student_id']??0);
        $site=$_POST['placement_site']??'';
        $sup=$_POST['supervisor_name']??'';
        $sd=$_POST['start_date']??'';
        $ed=$_POST['end_date']??'';
        if ($sid>0 && $site) {
            $stmt = $students->prepare("INSERT INTO clinical_placements_students (student_id,placement_site,supervisor_name,start_date,end_date,status) VALUES (?,?,?,?,?,'Scheduled')");
            $stmt->bind_param("issss", $sid, $site, $sup, $sd, $ed);
            $stmt->execute();
            if ($students->affected_rows>0) { dep_success('Clinical placement created.'); } else { dep_error('Placement failed: '.$students->error); }
            $stmt->close();
        } else { dep_error('Student and site required.'); }
        header("Location: deputy-principal.php?section=clinical_placement_monitoring"); exit;
    }
    if ($act === 'clinical_evaluation' && $students) {
        $pid=intval($_POST['placement_id']??0);
        $score=floatval($_POST['competency_score']??0);
        $eval=$_POST['evaluation']??'';
        if ($pid>0) {
            $stmt = $students->prepare("UPDATE clinical_placements_students SET competency_score=?, supervisor_evaluation=?, logbook_submitted=1, status='Completed' WHERE id=?");
            $stmt->bind_param("dsi", $score, $eval, $pid);
            $stmt->execute();
            $stmt->close();
            dep_success('Evaluation recorded.');
        } else { dep_error('Placement ID required.'); }
        header("Location: deputy-principal.php?section=clinical_placement_monitoring"); exit;
    }
}

$sv = $_SESSION['dep_success'] ?? ''; $ev = $_SESSION['dep_error'] ?? '';
unset($_SESSION['dep_success'], $_SESSION['dep_error']);?>
<!DOCTYPE html>
<html lang="en"><head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.scard{background:#fff;border-radius:12px;border:1px solid #e5e7eb;transition:all .2s;height:100%}
.scard:hover{box-shadow:0 4px 16px rgba(0,0,0,.06)}
.scard .sch{background:#f8fafc;padding:14px 20px;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;font-weight:600;color:#1a237e;font-size:14px}
.scard .scb{padding:20px}
.act-item{padding:10px 14px;border-left:3px solid #1a237e;background:#f8fafc;border-radius:0 8px 8px 0;margin-bottom:8px;transition:all .15s}
.act-item:hover{background:#eef2ff}
.act-item .time{font-size:11px;color:#94a3b8}
.kpi-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px;transition:all .25s;display:flex;align-items:center;gap:14px;height:100%}
.kpi-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.06);transform:translateY(-1px)}
.kpi-card .kpi-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.kpi-card .kpi-value{font-size:20px;font-weight:800;color:#0f172a;line-height:1.2}
.kpi-card .kpi-label{font-size:11px;color:#64748b;font-weight:500}
.kpi-card.primary .kpi-icon{background:#e8eaf6;color:#1a237e}
.kpi-card.success .kpi-icon{background:#dcfce7;color:#16a34a}
.kpi-card.info .kpi-icon{background:#e0f2fe;color:#0891b2}
.kpi-card.warning .kpi-icon{background:#fef3c7;color:#d97706}
.kpi-card.purple .kpi-icon{background:#f3e8ff;color:#7c3aed}
.kpi-card.danger .kpi-icon{background:#fee2e2;color:#dc2626}
.btn-sec{background:#1a237e;border:2px solid #1a237e;color:#fff;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;transition:all .2s}
.btn-sec:hover{background:#3949ab;border-color:#3949ab;color:#fff}
.btn-outline-sec{background:#fff;border:2px solid #1a237e;color:#1a237e;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;transition:all .2s}
.btn-outline-sec:hover{background:#1a237e;color:#fff}
.env-field{background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:13px;transition:border-color .2s}
.env-field:focus{border-color:#1a237e;outline:none;box-shadow:0 0 0 2px rgba(26,35,126,.1)}
.nav-sections{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px;padding:12px 0;border-bottom:2px solid #e5e7eb}
.nav-sections .nav-sec{padding:6px 14px;border-radius:8px;font-size:13px;color:#64748b;text-decoration:none;font-weight:500;transition:all .15s}
.nav-sections .nav-sec:hover{background:#eef2ff;color:#1a237e}
.nav-sections .nav-sec.active{background:#1a237e;color:#fff}
.dep-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px}
@media(max-width:768px){.dep-grid{grid-template-columns:1fr 1fr}}
</style>
</head><body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="ma content-section dashboard-section active" data-section="deputy" style="margin-left:270px;padding:24px">
<div class="ph mb-4">
<div><h1><i class="fas fa-user-friends me-2"></i>Deputy Principal Dashboard</h1><p class="text-muted">Academic &amp; Student Affairs Monitoring</p></div>
<a href="deputy-principal.php" class="bo btn-sm <?= $view==='home'?'d-none':'' ?>"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php if ($sv): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($sv) ?></div><?php endif; ?>
<?php if ($ev): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($ev) ?></div><?php endif; ?>

<!-- Navigation Tabs -->
<div class="nav-sections">
<a class="nav-sec <?= $view==='home'?'active':'' ?>" href="?section=home"><i class="fas fa-home me-1"></i>Overview</a>
<a class="nav-sec <?= in_array($view,['academic_monitoring','class_monitoring','timetable_oversight','attendance_monitoring','clinical_placement_monitoring']) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#academicSubnav" role="button"><i class="fas fa-book-open me-1"></i>Academic</a>
<a class="nav-sec <?= in_array($view,['student_welfare','student_discipline','student_support','student_appeals_tracking']) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#studentSubnav" role="button"><i class="fas fa-users me-1"></i>Student</a>
<a class="nav-sec <?= in_array($view,['department_followups','compliance_tracking','institutional_activities','task_monitoring']) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#opsSubnav" role="button"><i class="fas fa-cogs me-1"></i>Operations</a>
<a class="nav-sec <?= $view==='approvals'?'active':'' ?>" href="?section=approvals"><i class="fas fa-check-double me-1"></i>Approvals</a>
<a class="nav-sec <?= $view==='communications'?'active':'' ?>" href="?section=communications"><i class="fas fa-envelope me-1"></i>Communications</a>
<a class="nav-sec <?= in_array($view,['monitoring_reports','attendance_reports','welfare_reports','department_reports']) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#reportsSubnav" role="button"><i class="fas fa-chart-bar me-1"></i>Reports</a>
<a class="nav-sec <?= in_array($view,['teaching_quality','clinical_training_reviews','compliance_reviews','improvement_tracking']) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#qaSubnav" role="button"><i class="fas fa-clipboard-check me-1"></i>Quality</a>
</div>
<div class="collapse" id="academicSubnav"><div class="dep-grid mb-3">
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=academic_monitoring">Academic Monitoring</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=class_monitoring">Class Monitoring</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=timetable_oversight">Timetable Oversight</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=attendance_monitoring">Attendance</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=clinical_placement_monitoring">Clinical Placement</a>
</div></div>
<div class="collapse" id="studentSubnav"><div class="dep-grid mb-3">
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=student_welfare">Student Welfare</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=student_discipline">Discipline</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=student_support">Support</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=student_appeals_tracking">Appeals</a>
</div></div>
<div class="collapse" id="opsSubnav"><div class="dep-grid mb-3">
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=department_followups">Dept Follow-ups</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=compliance_tracking">Compliance</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=institutional_activities">Activities</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=task_monitoring">Task Monitor</a>
</div></div>
<div class="collapse" id="reportsSubnav"><div class="dep-grid mb-3">
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=monitoring_reports">Monitoring</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=attendance_reports">Attendance</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=welfare_reports">Welfare</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=department_reports">Department</a>
</div></div>
<div class="collapse" id="qaSubnav"><div class="dep-grid mb-3">
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=teaching_quality">Teaching Quality</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=clinical_training_reviews">Clinical Reviews</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=compliance_reviews">Compliance Reviews</a>
<a class="nav-sec btn-outline-sec btn-sm text-center" href="?section=improvement_tracking">Improvement</a>
</div></div>

<?php if ($view === 'home'): ?>
<?php
$ts = $students ? safeCount($students, "SELECT COUNT(*)c FROM students WHERE status='Active'") : 0;
$as = safeCount($staff, "SELECT COUNT(*)c FROM staff WHERE status='Active'");
$ar = $students ? round(safeCount($students, "SELECT COUNT(*)c FROM student_attendance WHERE status='Present'") / max(1, safeCount($students, "SELECT COUNT(*)c FROM student_attendance")) * 100, 1) : 0;
$pt = safeCount($staff, "SELECT COUNT(*)c FROM {$students_db}.deputy_tasks WHERE status='pending' OR status='in_progress'");
$da = safeCount($staff, "SELECT COUNT(*)c FROM {$students_db}.department_performance WHERE value<50");
$ua = safeCount($staff, "SELECT COUNT(*)c FROM {$students_db}.communication_log WHERE is_read=0 AND recipient_role='deputy'");
$activities = []; $r = $staff->query("SELECT activity_description activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 8");
if ($r) while ($rw = $r->fetch_assoc()) $activities[] = $rw;
$upcoming = []; $r = $staff->query("SELECT title, meeting_date, start_time FROM {$students_db}.meetings WHERE meeting_date>=CURDATE() ORDER BY meeting_date LIMIT 5");
if ($r) while ($rw = $r->fetch_assoc()) $upcoming[] = $rw;
?>
<div class="row g-3 mb-4">
<div class="col-md-2 col-6"><div class="kpi-card primary"><div class="kpi-icon"><i class="fas fa-users"></i></div><div><div class="kpi-value"><?= number_format($ts) ?></div><div class="kpi-label">Total Students</div></div></div></div>
<div class="col-md-2 col-6"><div class="kpi-card success"><div class="kpi-icon"><i class="fas fa-user-tie"></i></div><div><div class="kpi-value"><?= number_format($as) ?></div><div class="kpi-label">Active Staff</div></div></div></div>
<div class="col-md-2 col-6"><div class="kpi-card info"><div class="kpi-icon"><i class="fas fa-calendar-check"></i></div><div><div class="kpi-value"><?= $ar ?>%</div><div class="kpi-label">Attendance Rate</div></div></div></div>
<div class="col-md-2 col-6"><div class="kpi-card warning"><div class="kpi-icon"><i class="fas fa-tasks"></i></div><div><div class="kpi-value"><?= $pt ?></div><div class="kpi-label">Pending Tasks</div></div></div></div>
<div class="col-md-2 col-6"><div class="kpi-card danger"><div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div><div><div class="kpi-value"><?= $da ?></div><div class="kpi-label">Dept Alerts</div></div></div></div>
<div class="col-md-2 col-6"><div class="kpi-card purple"><div class="kpi-icon"><i class="fas fa-envelope"></i></div><div><div class="kpi-value"><?= $ua ?></div><div class="kpi-label">Unread Messages</div></div></div></div>
</div>
<div class="row g-3">
<div class="col-md-8">
<div class="scard"><div class="sch"><i class="fas fa-bolt me-2"></i>Quick Actions</div><div class="scb">
<div class="row g-2">
<div class="col-md-3 col-6"><a href="?section=task_monitoring" class="btn btn-sec w-100"><i class="fas fa-plus me-1"></i>New Task</a></div>
<div class="col-md-3 col-6"><a href="?section=attendance_monitoring" class="btn btn-sec w-100"><i class="fas fa-user-check me-1"></i>Attendance</a></div>
<div class="col-md-3 col-6"><a href="?section=communications" class="btn btn-sec w-100"><i class="fas fa-comments me-1"></i>Message</a></div>
<div class="col-md-3 col-6"><a href="?section=approvals" class="btn btn-sec w-100"><i class="fas fa-check-double me-1"></i>Approvals</a></div>
</div>
</div></div>
<div class="scard mt-3"><div class="sch"><i class="fas fa-clock me-2"></i>Recent Activity</div><div class="scb">
<?php if ($activities): ?>
<?php foreach ($activities as $a): ?>
<div class="act-item py-1"><div class="d-flex justify-content-between"><span><i class="fas fa-circle text-primary me-2 small"></i><?= htmlspecialchars(mb_substr($a['activity'],0,80)) ?></span><span class="time"><?= date('d M H:i',strtotime($a['created_at'])) ?></span></div></div>
<?php endforeach; ?>
<?php else: ?>
<div class="text-muted small">No recent activity.</div>
<?php endif; ?>
</div></div>
</div>
<div class="col-md-4">
<div class="scard"><div class="sch"><i class="fas fa-calendar-day me-2"></i>Upcoming Activities</div><div class="scb p-0">
<?php if ($upcoming): ?>
<?php foreach ($upcoming as $u): ?>
<div class="act-item"><div class="fw-bold small"><?= htmlspecialchars($u['title']) ?></div><div class="time"><?= htmlspecialchars($u['meeting_date']) ?> &middot; <?= htmlspecialchars($u['start_time']??'--') ?></div></div>
<?php endforeach; ?>
<?php else: ?>
<div class="p-3 text-muted small">No upcoming activities.</div>
<?php endif; ?>
</div></div>
</div>
</div>

<?php elseif ($view === 'academic_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-line me-2"></i>Academic Monitoring</div><div class="scb">
<form onsubmit="event.preventDefault(); depAssignLecturer()" class="row g-2 mb-3">
<div class="col-md-3"><select id="alLecturer" class="form-select env-field"><option value="">Select Lecturer</option>
<?php $r=$staff->query("SELECT id,full_name,position FROM staff WHERE position LIKE '%Lecturer%' OR position LIKE '%lecturer%' ORDER BY full_name"); if($r) while($l=$r->fetch_assoc()): ?><option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['full_name']) ?></option><?php endwhile; ?>
</select></div>
<div class="col-md-3"><select id="alCourse" class="form-select env-field"><option value="">Select Course</option>
<?php $r=$staff->query("SELECT id,course_code,course_title FROM academic_course_catalog WHERE status='Active' ORDER BY course_title"); if($r) while($c=$r->fetch_assoc()): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_title']) ?></option><?php endwhile; ?>
</select></div>
<div class="col-md-2"><input type="text" id="alCourseName" class="form-control env-field" placeholder="Course Name"></div>
<div class="col-md-2"><input type="text" id="alClassroom" class="form-control env-field" placeholder="Classroom"></div>
<div class="col-md-2"><button type="submit" class="btn btn-sec w-100"><i class="fas fa-user-plus me-1"></i>Assign</button></div>
</form>
<h6 class="fw-bold mb-2">Current Assignments</h6>
<div id="depAssignmentList"><div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div></div>
</div></div>
<script>
function depAssignLecturer(){
    var lid = document.getElementById('alLecturer').value; if(!lid){ alert('Select a lecturer'); return; }
    var cc = document.getElementById('alCourse').value; if(!cc){ alert('Select a course'); return; }
    var cn = document.getElementById('alCourseName').value || cc;
    var rm = document.getElementById('alClassroom').value || '';
    var fd = new FormData(); fd.append('action','assign_lecturer'); fd.append('lecturer_id',lid); fd.append('course_code',cc); fd.append('course_name',cn); fd.append('classroom',rm);
    fetch('deputy-principal.php',{method:'POST',body:fd}).then(function(){ window.location.reload(); });
}
function depLoadAssignments(){
    var el = document.getElementById('depAssignmentList');
    fetch('deputy-principal.php?view=class_monitoring_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small">No assignments.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Course</th><th>Day</th><th>Time</th><th>Venue</th><th>Lecturer</th></tr></thead><tbody>';
        d.forEach(function(t){ h+='<tr><td>'+esc(t.course_code)+'</td><td>'+esc(t.day_of_week)+'</td><td>'+esc(t.start_time)+'-'+esc(t.end_time)+'</td><td>'+esc(t.venue||'')+'</td><td>'+esc(t.lecturer_name||'-')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small">Failed to load.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadAssignments);
</script>

<?php elseif ($view === 'class_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-calendar-alt me-2"></i>Class Monitoring</div><div class="scb">
<div id="depClassData"><div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
</div></div>
<script>
function depLoadClassData(){
    var el = document.getElementById('depClassData');
    fetch('deputy-principal.php?view=class_monitoring_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted text-center py-4">No class data.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Course</th><th>Program</th><th>Day</th><th>Time</th><th>Venue</th><th>Lecturer</th><th>Year</th></tr></thead><tbody>';
        d.forEach(function(t){ h+='<tr><td><strong>'+esc(t.course_code)+'</strong></td><td>'+esc(t.program_code||'-')+'</td><td>'+esc(t.day_of_week)+'</td><td>'+esc(t.start_time)+'-'+esc(t.end_time)+'</td><td>'+esc(t.venue||'-')+'</td><td>'+esc(t.lecturer_name||'-')+'</td><td>'+esc(t.academic_year||'-')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadClassData);
</script>

<?php elseif ($view === 'timetable_oversight'): ?>
<div class="scard"><div class="sch"><i class="fas fa-table me-2"></i>Timetable Oversight</div><div class="scb">
<div id="depTimetableData"><div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
</div></div>
<script>
function depLoadTimetable(){
    var el = document.getElementById('depTimetableData');
    fetch('deputy-principal.php?view=timetable_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted text-center py-4">No timetable entries.</div>'; return; }
        var days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Day</th><th>Time</th><th>Course</th><th>Program</th><th>Venue</th><th>Lecturer</th></tr></thead><tbody>';
        days.forEach(function(day){
            var entries = d.filter(function(t){ return t.day_of_week === day; });
            if(entries.length){
                h+='<tr class="table-secondary"><td colspan="6"><strong>'+day+'</strong></td></tr>';
                entries.forEach(function(t){ h+='<tr><td></td><td>'+esc(t.start_time)+'-'+esc(t.end_time)+'</td><td>'+esc(t.course_code)+'</td><td>'+esc(t.program_code||'-')+'</td><td>'+esc(t.venue||'-')+'</td><td>'+esc(t.lecturer_name||'-')+'</td></tr>'; });
            }
        });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadTimetable);
</script>

<?php elseif ($view === 'attendance_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-user-check me-2"></i>Attendance Monitoring</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-3"><input type="text" id="attCourse" class="form-control env-field" placeholder="Course/Subject"></div>
<div class="col-md-3"><input type="date" id="attFrom" class="form-control env-field"></div>
<div class="col-md-3"><input type="date" id="attTo" class="form-control env-field"></div>
<div class="col-md-3"><button class="btn btn-sec w-100" onclick="depLoadAttendance()"><i class="fas fa-search me-1"></i>Filter</button></div>
</div>
<div id="depAttendanceData"><div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
</div></div>
<script>
function depLoadAttendance(){
    var el = document.getElementById('depAttendanceData'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    var course = document.getElementById('attCourse').value; var f = document.getElementById('attFrom').value; var t = document.getElementById('attTo').value;
    var url = 'deputy-principal.php?view=attendance_monitoring_data&ajax=1';
    if(course) url+='&course='+encodeURIComponent(course); if(f) url+='&from='+f; if(t) url+='&to='+t;
    fetch(url).then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted text-center py-4">No attendance records.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Reg No</th><th>Date</th><th>Subject</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(a){ var stCls = a.status==='Present'?'success':a.status==='Late'?'warning':'danger'; h+='<tr><td>'+esc(a.full_name)+'</td><td>'+esc(a.student_number)+'</td><td>'+esc(a.date)+'</td><td>'+esc(a.subject||'')+'</td><td><span class="badge bg-'+stCls+'">'+esc(a.status)+'</span></td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadAttendance);
</script>

<?php elseif ($view === 'clinical_placement_monitoring'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-hospital me-2"></i>Schedule Placement</div><div class="scb">
<form onsubmit="event.preventDefault(); depSchedulePlacement()">
<div class="mb-3"><label class="fl">Student</label><select id="cpStudent" class="form-select env-field"><option value="">Select</option>
<?php if($students){ $r=$students->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200"); if($r) while($s=$r->fetch_assoc()): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option><?php endwhile; } ?>
</select></div>
<div class="mb-3"><label class="fl">Placement Site</label><input type="text" id="cpSite" class="form-control env-field" required></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Supervisor</label><input type="text" id="cpSupervisor" class="form-control env-field"></div><div class="col-6"><label class="fl">Start Date</label><input type="date" id="cpStart" class="form-control env-field"></div></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">End Date</label><input type="date" id="cpEnd" class="form-control env-field"></div></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Schedule</button>
</form>
<div id="cpMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Placements</div><div class="scb p-0"><div id="depPlacementList"></div></div></div>
</div>
</div>
<script>
function depSchedulePlacement(){
    var sid = document.getElementById('cpStudent').value; if(!sid){ alert('Select student'); return; }
    var fd = new FormData(); fd.append('student_id',sid); fd.append('placement_site',document.getElementById('cpSite').value); fd.append('supervisor_name',document.getElementById('cpSupervisor').value); fd.append('start_date',document.getElementById('cpStart').value); fd.append('end_date',document.getElementById('cpEnd').value);
    fetch('deputy-principal.php?view=schedule_placement&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('cpMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Placement created.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('cpStudent').value=''; document.getElementById('cpSite').value=''; document.getElementById('cpSupervisor').value=''; document.getElementById('cpStart').value=''; document.getElementById('cpEnd').value=''; depLoadPlacements(); }
    }).catch(function(){ document.getElementById('cpMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function depLoadPlacements(){
    var el = document.getElementById('depPlacementList'); if(!el) return; el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=clinical_placement_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No placements.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Site</th><th>Supervisor</th><th>Start</th><th>End</th><th>Score</th><th>Status</th><th></th></tr></thead><tbody>';
        d.forEach(function(p){ var stCls=p.status==='Completed'?'success':p.status==='Active'?'info':'warning text-dark'; h+='<tr><td>'+esc(p.student_name||'-')+'</td><td>'+esc(p.placement_site)+'</td><td>'+esc(p.supervisor_name||'-')+'</td><td class="small">'+(p.start_date||'-')+'</td><td class="small">'+(p.end_date||'-')+'</td><td>'+(p.competency_score||'-')+'</td><td><span class="badge bg-'+stCls+'">'+esc(p.status)+'</span></td><td><select class="form-select form-select-sm" style="width:auto" onchange="depUpdatePlacementStatus('+p.id+',this.value)"><option value="">Change</option><option value="Active">Active</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option></select></td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
function depUpdatePlacementStatus(id,st){ if(!st) return; var fd=new FormData(); fd.append('id',id); fd.append('status',st); fetch('deputy-principal.php?view=update_placement_status&ajax=1',{method:'POST',body:fd}).then(function(r){ return r.json(); }).then(function(d){ if(d.success) depLoadPlacements(); }).catch(function(){}); }
document.addEventListener('DOMContentLoaded', depLoadPlacements);
</script>

<?php elseif ($view === 'student_welfare'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-heart me-2"></i>Record Welfare Case</div><div class="scb">
<form onsubmit="event.preventDefault(); depRecordWelfare()">
<div class="mb-3"><label class="fl">Student</label><select id="wfStudent" class="form-select env-field"><option value="">Select</option>
<?php if($students){ $r=$students->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200"); if($r) while($s=$r->fetch_assoc()): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option><?php endwhile; } ?>
</select></div>
<div class="mb-3"><label class="fl">Case Type</label><input type="text" id="wfType" class="form-control env-field" required placeholder="e.g. Financial, Health, Counseling"></div>
<div class="mb-3"><label class="fl">Description</label><textarea id="wfDesc" class="form-control env-field" rows="3"></textarea></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Severity</label><select id="wfSev" class="form-select env-field"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div><div class="col-6"><label class="fl">Assigned To</label><input type="text" id="wfAssigned" class="form-control env-field"></div></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Record Case</button>
</form>
<div id="wfMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Welfare Cases</div><div class="scb p-0"><div id="depWelfareList"></div></div></div>
</div>
</div>
<script>
function depRecordWelfare(){
    var sid = document.getElementById('wfStudent').value; if(!sid){ alert('Select student'); return; }
    var fd = new FormData(); fd.append('student_id',sid); fd.append('case_type',document.getElementById('wfType').value); fd.append('description',document.getElementById('wfDesc').value); fd.append('severity',document.getElementById('wfSev').value); fd.append('assigned_to',document.getElementById('wfAssigned').value);
    fetch('deputy-principal.php?view=record_welfare_case&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('wfMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Case recorded.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('wfStudent').value=''; document.getElementById('wfType').value=''; document.getElementById('wfDesc').value=''; document.getElementById('wfAssigned').value=''; depLoadWelfare(); }
    }).catch(function(){ document.getElementById('wfMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function depLoadWelfare(){
    var el = document.getElementById('depWelfareList'); if(!el) return; el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=welfare_cases_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No welfare cases.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Type</th><th>Severity</th><th>Assigned To</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>';
        d.forEach(function(w){ var sevCls=w.severity==='critical'?'danger':w.severity==='high'?'warning':w.severity==='medium'?'info':'secondary'; var stCls=w.status==='resolved'||w.status==='closed'?'success':w.status==='in_progress'?'info':'warning text-dark'; h+='<tr><td>'+esc(w.student_name||'-')+'</td><td>'+esc(w.case_type)+'</td><td><span class="badge bg-'+sevCls+'">'+esc(w.severity)+'</span></td><td class="small">'+esc(w.assigned_to||'--')+'</td><td><span class="badge bg-'+stCls+'">'+esc(w.status)+'</span></td><td class="small">'+(w.created_at||'')+'</td><td><select class="form-select form-select-sm" style="width:auto" onchange="depUpdateWelfare('+w.id+',this.value)"><option value="">Action</option><option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
function depUpdateWelfare(id,st){ if(!st) return; var fd=new FormData(); fd.append('id',id); fd.append('status',st); fetch('deputy-principal.php?view=update_welfare_status&ajax=1',{method:'POST',body:fd}).then(function(r){ return r.json(); }).then(function(d){ if(d.success) depLoadWelfare(); }).catch(function(){}); }
document.addEventListener('DOMContentLoaded', depLoadWelfare);
</script>

<?php elseif ($view === 'student_discipline'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-gavel me-2"></i>Record Discipline Case</div><div class="scb">
<form onsubmit="event.preventDefault(); depRecordDiscipline()">
<div class="mb-3"><label class="fl">Student</label><select id="discStudent" class="form-select env-field"><option value="">Select</option>
<?php if($students){ $r=$students->query("SELECT id,full_name,student_number FROM students WHERE status='Active' ORDER BY full_name LIMIT 200"); if($r) while($s=$r->fetch_assoc()): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option><?php endwhile; } ?>
</select></div>
<div class="mb-3"><label class="fl">Offense</label><textarea id="discOffense" class="form-control env-field" rows="3" required></textarea></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Hearing Date</label><input type="date" id="discHearing" class="form-control env-field"></div><div class="col-6"><label class="fl">Action Taken</label><input type="text" id="discAction" class="form-control env-field"></div></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Record</button>
</form>
<div id="discMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Discipline Cases</div><div class="scb p-0"><div id="depDisciplineList"></div></div></div>
</div>
</div>
<script>
function depRecordDiscipline(){
    var sid = document.getElementById('discStudent').value; if(!sid){ alert('Select student'); return; }
    var fd = new FormData(); fd.append('student_id',sid); fd.append('offense',document.getElementById('discOffense').value); fd.append('hearing_date',document.getElementById('discHearing').value); fd.append('action_taken',document.getElementById('discAction').value);
    fetch('deputy-principal.php?view=record_discipline&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('discMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Recorded.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('discStudent').value=''; document.getElementById('discOffense').value=''; document.getElementById('discHearing').value=''; document.getElementById('discAction').value=''; depLoadDiscipline(); }
    }).catch(function(){ document.getElementById('discMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function depLoadDiscipline(){
    var el = document.getElementById('depDisciplineList'); if(!el) return; el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=discipline_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No discipline cases.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Offense</th><th>Reported By</th><th>Hearing</th><th>Outcome</th><th>Status</th><th></th></tr></thead><tbody>';
        d.forEach(function(dc){ var stCls=dc.status==='resolved'?'success':dc.status==='appealed'?'warning':'danger'; h+='<tr><td>'+esc(dc.student_name||'-')+'</td><td>'+esc(dc.offense)+'</td><td>'+esc(dc.reported_by||'-')+'</td><td class="small">'+(dc.hearing_date||'-')+'</td><td>'+esc(dc.outcome||'-')+'</td><td><span class="badge bg-'+stCls+'">'+esc(dc.status)+'</span></td><td><select class="form-select form-select-sm" style="width:auto" onchange="depUpdateDiscipline('+dc.id+',this.value)"><option value="">Action</option><option value="open">Open</option><option value="resolved">Resolved</option><option value="appealed">Appealed</option></select></td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
function depUpdateDiscipline(id,st){ if(!st) return; var out=prompt('Outcome notes (optional):'); var fd=new FormData(); fd.append('id',id); fd.append('status',st); if(out) fd.append('outcome',out); fetch('deputy-principal.php?view=update_discipline_status&ajax=1',{method:'POST',body:fd}).then(function(r){ return r.json(); }).then(function(d){ if(d.success) depLoadDiscipline(); }).catch(function(){}); }
document.addEventListener('DOMContentLoaded', depLoadDiscipline);
</script>

<?php elseif ($view === 'student_support'): ?>
<div class="scard"><div class="sch"><i class="fas fa-hands-helping me-2"></i>Student Support Cases - Appeals &amp; Requests</div><div class="scb p-0"><div id="depSupportList"></div></div></div>
<script>
function depLoadSupport(){
    var el = document.getElementById('depSupportList'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=student_support_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No support cases.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>ID</th><th>Appeal Type</th><th>Reason</th><th>Outcome</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(a){ var stCls=a.status==='approved'?'success':a.status==='rejected'?'danger':'warning text-dark'; h+='<tr><td>'+a.id+'</td><td>'+esc(a.appeal_type)+'</td><td class="small">'+esc(mb_substr(a.reason||'',0,80))+'</td><td class="small">'+esc(a.outcome||'-')+'</td><td><span class="badge bg-'+stCls+'">'+esc(a.status)+'</span></td><td class="small">'+(a.created_at||'')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadSupport);
</script>

<?php elseif ($view === 'student_appeals_tracking'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-contract me-2"></i>Student Appeals Tracking</div><div class="scb p-0"><div id="depAppealsList"></div></div></div>
<script>
function depLoadAppeals(){
    var el = document.getElementById('depAppealsList'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=student_support_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No appeals.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>ID</th><th>Type</th><th>Reason</th><th>Outcome</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(a){ var stCls=a.status==='approved'?'success':a.status==='rejected'?'danger':'warning text-dark'; h+='<tr><td>'+a.id+'</td><td>'+esc(a.appeal_type)+'</td><td class="small">'+esc(mb_substr(a.reason||'',0,80))+'</td><td>'+esc(a.outcome||'-')+'</td><td><span class="badge bg-'+stCls+'">'+esc(a.status)+'</span></td><td class="small">'+(a.created_at||'')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadAppeals);
</script>

<?php elseif ($view === 'department_followups'): ?>
<div class="scard"><div class="sch"><i class="fas fa-clipboard-list me-2"></i>Department Follow-ups &amp; Performance</div><div class="scb p-0"><div id="depFollowupList"></div></div></div>
<script>
function depLoadFollowups(){
    var el = document.getElementById('depFollowupList'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=department_followup_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No department metrics.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Department</th><th>Metric</th><th>Value</th><th>Period</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(p){ h+='<tr><td><strong>'+esc(p.department)+'</strong></td><td>'+esc(p.metric)+'</td><td>'+esc(p.value)+'</td><td class="small">'+esc(p.period||'-')+'</td><td class="small">'+(p.created_at||'')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadFollowups);
</script>

<?php elseif ($view === 'compliance_tracking'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-check-shield me-2"></i>Record Compliance</div><div class="scb">
<form onsubmit="event.preventDefault(); depRecordCompliance()">
<div class="mb-3"><label class="fl">Department</label><input type="text" id="compDept" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Compliance Type</label><input type="text" id="compType" class="form-control env-field" required placeholder="e.g. Licensing, Accreditation, Reporting"></div>
<div class="mb-3"><label class="fl">Status</label><select id="compStatus" class="form-select env-field"><option value="compliant">Compliant</option><option value="non_compliant">Non-Compliant</option><option value="pending_review">Pending Review</option></select></div>
<div class="mb-3"><label class="fl">Notes</label><textarea id="compNotes" class="form-control env-field" rows="3"></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Record</button>
</form>
<div id="compMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Compliance Records</div><div class="scb p-0"><div id="depCompList"></div></div></div>
</div>
</div>
<script>
function depRecordCompliance(){
    var fd = new FormData(); fd.append('department',document.getElementById('compDept').value); fd.append('compliance_type',document.getElementById('compType').value); fd.append('status',document.getElementById('compStatus').value); fd.append('notes',document.getElementById('compNotes').value);
    fetch('deputy-principal.php?view=record_compliance&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('compMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Recorded.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('compDept').value=''; document.getElementById('compType').value=''; document.getElementById('compNotes').value=''; depLoadCompliance(); }
    }).catch(function(){ document.getElementById('compMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function depLoadCompliance(){
    var el = document.getElementById('depCompList'); if(!el) return; el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=compliance_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No compliance records.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Department</th><th>Type</th><th>Status</th><th>Reviewed By</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(c){ var stCls=c.status==='compliant'?'success':c.status==='non_compliant'?'danger':'warning text-dark'; h+='<tr><td>'+esc(c.department)+'</td><td>'+esc(c.compliance_type)+'</td><td><span class="badge bg-'+stCls+'">'+esc(c.status)+'</span></td><td>'+esc(c.reviewed_by||'-')+'</td><td class="small">'+(c.created_at||'')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadCompliance);
</script>

<?php elseif ($view === 'institutional_activities'): ?>
<div class="scard"><div class="sch"><i class="fas fa-calendar-week me-2"></i>Institutional Activities</div><div class="scb p-0"><div id="depActivitiesList"></div></div></div>
<script>
function depLoadActivities(){
    var el = document.getElementById('depActivitiesList');
    el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=class_monitoring_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No activities.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Course</th><th>Day</th><th>Time</th><th>Venue</th><th>Lecturer</th><th>Year</th></tr></thead><tbody>';
        d.forEach(function(t){ h+='<tr><td>'+esc(t.course_code)+'</td><td>'+esc(t.day_of_week)+'</td><td>'+esc(t.start_time)+'-'+esc(t.end_time)+'</td><td>'+esc(t.venue||'-')+'</td><td>'+esc(t.lecturer_name||'-')+'</td><td>'+esc(t.academic_year||'-')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadActivities);
</script>

<?php elseif ($view === 'task_monitoring'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Create Task</div><div class="scb">
<form onsubmit="event.preventDefault(); depCreateTask()">
<div class="mb-3"><label class="fl">Task Title *</label><input type="text" id="tkTitle" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Description</label><textarea id="tkDesc" class="form-control env-field" rows="3"></textarea></div>
<div class="mb-3"><label class="fl">Priority</label><select id="tkPriority" class="form-select env-field"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Create Task</button>
</form>
<div id="tkMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-tasks me-2"></i>Task List</div><div class="scb p-0"><div id="depTaskList"></div></div></div>
</div>
</div>
<script>
function depCreateTask(){
    var tt = document.getElementById('tkTitle').value; if(!tt){ alert('Title required'); return; }
    var fd = new FormData(); fd.append('task_title',tt); fd.append('description',document.getElementById('tkDesc').value); fd.append('priority',document.getElementById('tkPriority').value);
    fetch('deputy-principal.php?view=create_task&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('tkMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Task created.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('tkTitle').value=''; document.getElementById('tkDesc').value=''; depLoadTasks(); }
    }).catch(function(){ document.getElementById('tkMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function depLoadTasks(){
    var el = document.getElementById('depTaskList'); if(!el) return; el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=task_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No tasks.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Task</th><th>Assigned By</th><th>Priority</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>';
        d.forEach(function(t){ var prCls=t.priority==='urgent'?'danger':t.priority==='high'?'warning':t.priority==='medium'?'info':'secondary'; var stCls=t.status==='completed'?'success':t.status==='in_progress'?'info':t.status==='cancelled'?'danger':'warning text-dark'; h+='<tr><td><strong>'+esc(t.task_title)+'</strong></td><td class="small">'+esc(t.assigned_by||'-')+'</td><td><span class="badge bg-'+prCls+'">'+esc(t.priority)+'</span></td><td><span class="badge bg-'+stCls+'">'+esc(t.status)+'</span></td><td class="small">'+(t.created_at||'')+'</td><td><select class="form-select form-select-sm" style="width:auto" onchange="depUpdateTask('+t.id+',this.value)"><option value="">Change</option><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
function depUpdateTask(id,st){ if(!st) return; var fd=new FormData(); fd.append('id',id); fd.append('status',st); fetch('deputy-principal.php?view=update_task_status&ajax=1',{method:'POST',body:fd}).then(function(r){ return r.json(); }).then(function(d){ if(d.success) depLoadTasks(); }).catch(function(){}); }
document.addEventListener('DOMContentLoaded', depLoadTasks);
</script>

<?php elseif ($view === 'approvals'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-double me-2"></i>Approvals - Review &amp; Forward to Principal</div><div class="scb">
<p class="text-muted small mb-3">As Deputy Principal, you <strong>review and recommend</strong>. Final approval is done by the Principal. Items below are pending your review.</p>
<div id="depApprovalList"><div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div></div>
</div></div>
<script>
function depLoadApprovals(){
    var el = document.getElementById('depApprovalList');
    fetch('deputy-principal.php?view=approval_list_deputy&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small py-4 text-center">No items pending review.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Subject</th><th>From</th><th>Message</th><th>Date</th><th></th></tr></thead><tbody>';
        d.forEach(function(c){ h+='<tr><td><strong>'+esc(c.subject)+'</strong></td><td>'+esc(c.sender_name||'-')+'</td><td class="small">'+esc(mb_substr(c.message||'',0,100))+'</td><td class="small">'+(c.created_at||'')+'</td><td><button class="btn btn-sm btn-outline-primary" onclick="depForwardApproval('+c.id+')"><i class="fas fa-forward me-1"></i>Review &amp; Forward</button></td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
function depForwardApproval(id){
    var rec = prompt('Enter your recommendation/comments:');
    if(rec === null) return;
    var fd = new FormData(); fd.append('id',id); fd.append('recommendation',rec);
    fetch('deputy-principal.php?view=forward_approval&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        if(d.success){ depLoadApprovals(); alert('Reviewed and forwarded to Principal for final approval.'); }
        else{ alert('Failed: '+d.error); }
    }).catch(function(){ alert('Failed to forward.'); });
}
document.addEventListener('DOMContentLoaded', depLoadApprovals);
</script>

<?php elseif ($view === 'communications'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-paper-plane me-2"></i>Send Communication</div><div class="scb">
<p class="text-muted small">You can message Principal, Registrar, HODs, Lecturers, and Student Leaders. Institution-wide broadcasting is not available.</p>
<form id="depCommForm">
<div class="mb-3"><label class="fl">Recipient</label><select id="commRecipient" class="form-select env-field">
<option value="principal">Principal</option><option value="registrar">Registrar</option><option value="hods">Heads of Department</option><option value="lecturers">Lecturers</option><option value="student_leaders">Student Leaders</option>
</select></div>
<div class="mb-3"><label class="fl">Subject *</label><input type="text" id="commSubject" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Message *</label><textarea id="commBody" class="form-control env-field" rows="4" required></textarea></div>
<button type="button" class="btn btn-sec" onclick="depSendComm()"><i class="fas fa-paper-plane me-1"></i>Send</button>
</form>
<div id="commMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-inbox me-2"></i>Communication History</div><div class="scb p-0"><div id="depCommHistory"></div></div></div>
</div>
</div>
<script>
function depSendComm(){
    var rt = document.getElementById('commRecipient').value;
    var subj = document.getElementById('commSubject').value; var msg = document.getElementById('commBody').value;
    if(!subj || !msg){ alert('Subject and message required'); return; }
    var fd = new FormData(); fd.append('recipient_role',rt); fd.append('subject',subj); fd.append('message',msg);
    fetch('deputy-principal.php?view=send_communication_deputy&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('commMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Sent.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('commSubject').value=''; document.getElementById('commBody').value=''; depLoadCommHistory(); }
    }).catch(function(){ document.getElementById('commMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function depLoadCommHistory(){
    var el = document.getElementById('depCommHistory'); if(!el) return; el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=task_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(){
        fetch('deputy-principal.php?view=approval_list_deputy&ajax=1')
        .then(function(r2){ return r2.json(); }).then(function(d){
            if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No communications.</div>'; return; }
            var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Subject</th><th>From</th><th>To</th><th>Date</th><th>Status</th></tr></thead><tbody>';
            d.forEach(function(c){ h+='<tr><td>'+esc(c.subject)+'</td><td>'+esc(c.sender_name||'-')+'</td><td class="small">'+esc(c.recipient_role||'-')+'</td><td class="small">'+(c.created_at||'')+'</td><td>'+(c.is_read?'<span class="badge bg-success">Read</span>':'<span class="badge bg-warning text-dark">Pending</span>')+'</td></tr>'; });
            h+='</tbody></table></div>'; el.innerHTML=h;
        }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
    }).catch(function(){});
}
document.addEventListener('DOMContentLoaded', depLoadCommHistory);
</script>

<?php elseif ($view === 'monitoring_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-simple me-2"></i>Monitoring Reports</div><div class="scb">
<p class="text-muted small">Monitor key institutional metrics across departments.</p>
<div id="depMonReport"></div>
</div></div>
<script>
function depLoadMonReport(){
    var el = document.getElementById('depMonReport'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=department_followup_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted text-center py-4">No data available.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Department</th><th>Metric</th><th>Value</th><th>Period</th></tr></thead><tbody>';
        d.forEach(function(p){ h+='<tr><td><strong>'+esc(p.department)+'</strong></td><td>'+esc(p.metric)+'</td><td>'+esc(p.value)+'</td><td>'+esc(p.period||'-')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadMonReport);
</script>

<?php elseif ($view === 'attendance_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-calendar-check me-2"></i>Attendance Reports</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-3"><input type="date" id="arFrom" class="form-control env-field"></div>
<div class="col-md-3"><input type="date" id="arTo" class="form-control env-field"></div>
<div class="col-md-3"><input type="text" id="arCourse" class="form-control env-field" placeholder="Course filter"></div>
<div class="col-md-3"><button class="btn btn-sec w-100" onclick="depLoadAttReport()"><i class="fas fa-search me-1"></i>Generate</button></div>
</div>
<div id="depAttReport"><div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
</div></div>
<script>
function depLoadAttReport(){
    var el = document.getElementById('depAttReport'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    var f = document.getElementById('arFrom').value; var t = document.getElementById('arTo').value; var c = document.getElementById('arCourse').value;
    var url = 'deputy-principal.php?view=attendance_monitoring_data&ajax=1';
    if(f) url+='&from='+f; if(t) url+='&to='+t; if(c) url+='&course='+encodeURIComponent(c);
    fetch(url).then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted text-center py-4">No data.</div>'; return; }
        var pres = d.filter(function(a){ return a.status==='Present'; }).length;
        var abs = d.filter(function(a){ return a.status==='Absent'; }).length;
        var late = d.filter(function(a){ return a.status==='Late'; }).length;
        var total = d.length;
        el.innerHTML='<div class="row g-2 mb-3"><div class="col-3"><div class="border rounded p-2 text-center"><div class="fw-bold h4 text-success">'+pres+'</div><small>Present</small></div></div><div class="col-3"><div class="border rounded p-2 text-center"><div class="fw-bold h4 text-danger">'+abs+'</div><small>Absent</small></div></div><div class="col-3"><div class="border rounded p-2 text-center"><div class="fw-bold h4 text-warning">'+late+'</div><small>Late</small></div></div><div class="col-3"><div class="border rounded p-2 text-center"><div class="fw-bold h4 text-info">'+total+'</div><small>Total</small></div></div></div>'+
        '<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Date</th><th>Subject</th><th>Status</th></tr></thead><tbody>'+
        d.slice(0,100).map(function(a){ var sc=a.status==='Present'?'success':a.status==='Late'?'warning':'danger'; return '<tr><td>'+esc(a.full_name)+'</td><td>'+esc(a.date)+'</td><td>'+esc(a.subject||'')+'</td><td><span class="badge bg-'+sc+'">'+esc(a.status)+'</span></td></tr>'; }).join('')+
        '</tbody></table></div>';
    }).catch(function(){ el.innerHTML='<div class="text-danger">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', function(){ document.getElementById('arFrom').value=''; document.getElementById('arTo').value=''; depLoadAttReport(); });
</script>

<?php elseif ($view === 'welfare_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-heartbeat me-2"></i>Welfare Reports</div><div class="scb p-0"><div id="depWelfareReport"></div></div></div>
<script>
function depLoadWelfareReport(){
    var el = document.getElementById('depWelfareReport'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=welfare_cases_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No welfare data.</div>'; return; }
        var open = d.filter(function(w){ return w.status==='open'||w.status==='in_progress'; }).length;
        var resolved = d.filter(function(w){ return w.status==='resolved'||w.status==='closed'; }).length;
        var critical = d.filter(function(w){ return w.severity==='critical'; }).length;
        el.innerHTML='<div class="p-3"><div class="row g-2 mb-3"><div class="col-4"><div class="border rounded p-2 text-center"><div class="fw-bold h4 text-warning">'+open+'</div><small>Active Cases</small></div></div><div class="col-4"><div class="border rounded p-2 text-center"><div class="fw-bold h4 text-success">'+resolved+'</div><small>Resolved</small></div></div><div class="col-4"><div class="border rounded p-2 text-center"><div class="fw-bold h4 text-danger">'+critical+'</div><small>Critical</small></div></div></div></div>'+
        '<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Type</th><th>Severity</th><th>Status</th><th>Date</th></tr></thead><tbody>'+
        d.map(function(w){ var sc=w.severity==='critical'?'danger':w.severity==='high'?'warning':'secondary'; var stc=w.status==='resolved'||w.status==='closed'?'success':w.status==='in_progress'?'info':'warning text-dark'; return '<tr><td>'+esc(w.student_name||'-')+'</td><td>'+esc(w.case_type)+'</td><td><span class="badge bg-'+sc+'">'+esc(w.severity)+'</span></td><td><span class="badge bg-'+stc+'">'+esc(w.status)+'</span></td><td class="small">'+(w.created_at||'')+'</td></tr>'; }).join('')+
        '</tbody></table></div>';
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadWelfareReport);
</script>

<?php elseif ($view === 'department_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-building me-2"></i>Department Reports</div><div class="scb p-0"><div id="depDeptReport"></div></div></div>
<script>
function depLoadDeptReport(){
    var el = document.getElementById('depDeptReport'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=compliance_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No department data.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Department</th><th>Type</th><th>Status</th><th>Reviewed By</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(c){ var sc=c.status==='compliant'?'success':c.status==='non_compliant'?'danger':'warning text-dark'; h+='<tr><td>'+esc(c.department)+'</td><td>'+esc(c.compliance_type)+'</td><td><span class="badge bg-'+sc+'">'+esc(c.status)+'</span></td><td>'+esc(c.reviewed_by||'-')+'</td><td class="small">'+(c.created_at||'')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadDeptReport);
</script>

<?php elseif ($view === 'teaching_quality'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-chalkboard-teacher me-2"></i>Submit Teaching Review</div><div class="scb">
<form onsubmit="event.preventDefault(); depSubmitTeachingReview()">
<div class="mb-3"><label class="fl">Lecturer</label><select id="tqLecturer" class="form-select env-field"><option value="">Select</option>
<?php $r=$staff->query("SELECT id,full_name,position FROM staff WHERE position LIKE '%Lecturer%' OR position LIKE '%lecturer%' OR position LIKE '%Head%' ORDER BY full_name"); if($r) while($l=$r->fetch_assoc()): ?><option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['full_name']) ?> (<?= htmlspecialchars($l['position']) ?>)</option><?php endwhile; ?>
</select></div>
<div class="mb-3"><label class="fl">Course Code</label><select id="tqCourse" class="form-select env-field"><option value="">Select</option>
<?php $r=$staff->query("SELECT course_code,course_title FROM academic_course_catalog WHERE status='Active' ORDER BY course_title"); if($r) while($c=$r->fetch_assoc()): ?><option value="<?= htmlspecialchars($c['course_code']) ?>"><?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_title']) ?></option><?php endwhile; ?>
</select></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Review Date</label><input type="date" id="tqDate" class="form-control env-field"></div><div class="col-6"><label class="fl">Score (0-100)</label><input type="number" id="tqScore" class="form-control env-field" min="0" max="100" step="0.1"></div></div>
<div class="mb-3"><label class="fl">Feedback</label><textarea id="tqFeedback" class="form-control env-field" rows="4"></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Submit Review</button>
</form>
<div id="tqMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-star me-2"></i>Teaching Reviews</div><div class="scb p-0"><div id="depTQList"></div></div></div>
</div>
</div>
<script>
function depSubmitTeachingReview(){
    var lid = document.getElementById('tqLecturer').value; if(!lid){ alert('Select lecturer'); return; }
    var cc = document.getElementById('tqCourse').value; if(!cc){ alert('Select course'); return; }
    var fd = new FormData(); fd.append('lecturer_id',lid); fd.append('review_date',document.getElementById('tqDate').value); fd.append('teaching_score',document.getElementById('tqScore').value); fd.append('course_code',cc); fd.append('feedback',document.getElementById('tqFeedback').value);
    fetch('deputy-principal.php?view=submit_teaching_review&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('tqMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Review submitted.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('tqLecturer').value=''; document.getElementById('tqCourse').value=''; document.getElementById('tqDate').value=''; document.getElementById('tqScore').value=''; document.getElementById('tqFeedback').value=''; depLoadTQ(); }
    }).catch(function(){ document.getElementById('tqMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function depLoadTQ(){
    var el = document.getElementById('depTQList'); if(!el) return; el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=teaching_quality_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No reviews yet.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Lecturer</th><th>Course</th><th>Score</th><th>Observer</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(r){ var sc=r.status==='reviewed'?'success':r.status==='completed'?'info':'warning text-dark'; h+='<tr><td>'+esc(r.lecturer_name||'-')+'</td><td>'+esc(r.course_code)+'</td><td><strong>'+(r.teaching_score||'-')+'</strong></td><td>'+esc(r.observer||'-')+'</td><td class="small">'+(r.review_date||r.created_at||'')+'</td><td><span class="badge bg-'+sc+'">'+esc(r.status)+'</span></td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadTQ);
</script>

<?php elseif ($view === 'clinical_training_reviews'): ?>
<div class="scard"><div class="sch"><i class="fas fa-hospital-user me-2"></i>Clinical Training Reviews</div><div class="scb p-0"><div id="depClinicalReview"></div></div></div>
<script>
function depLoadClinicalReview(){
    var el = document.getElementById('depClinicalReview'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=clinical_placement_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No placements.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Site</th><th>Score</th><th>Logbook</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(p){ h+='<tr><td>'+esc(p.student_name||'-')+'</td><td>'+esc(p.placement_site)+'</td><td>'+(p.competency_score||'-')+'</td><td>'+(p.logbook_submitted?'Yes':'No')+'</td><td><span class="badge bg-'+(p.status==='Completed'?'success':p.status==='Active'?'info':'warning text-dark')+'">'+esc(p.status)+'</span></td><td class="small">'+(p.created_at||'')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadClinicalReview);
</script>

<?php elseif ($view === 'compliance_reviews'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-contract me-2"></i>Compliance Reviews</div><div class="scb p-0"><div id="depCompReview"></div></div></div>
<script>
function depLoadCompReview(){
    var el = document.getElementById('depCompReview'); el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=compliance_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No compliance data.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Department</th><th>Type</th><th>Status</th><th>Notes</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(c){ var sc=c.status==='compliant'?'success':c.status==='non_compliant'?'danger':'warning text-dark'; h+='<tr><td>'+esc(c.department)+'</td><td>'+esc(c.compliance_type)+'</td><td><span class="badge bg-'+sc+'">'+esc(c.status)+'</span></td><td class="small">'+esc(mb_substr(c.notes||'',0,80))+'</td><td class="small">'+(c.created_at||'')+'</td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', depLoadCompReview);
</script>

<?php elseif ($view === 'improvement_tracking'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-chart-line me-2"></i>Record Improvement Action</div><div class="scb">
<form onsubmit="event.preventDefault(); depRecordImprovement()">
<div class="mb-3"><label class="fl">Area</label><input type="text" id="impArea" class="form-control env-field" required placeholder="e.g. Curriculum, Facilities, Staffing"></div>
<div class="mb-3"><label class="fl">Improvement Action</label><textarea id="impAction" class="form-control env-field" rows="3" required></textarea></div>
<div class="mb-3"><label class="fl">Target Date</label><input type="date" id="impDate" class="form-control env-field"></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Record</button>
</form>
<div id="impMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Improvement Tracking</div><div class="scb p-0"><div id="depImpList"></div></div></div>
</div>
</div>
<script>
function depRecordImprovement(){
    var fd = new FormData(); fd.append('area',document.getElementById('impArea').value); fd.append('improvement_action',document.getElementById('impAction').value); fd.append('target_date',document.getElementById('impDate').value);
    fetch('deputy-principal.php?view=record_improvement&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('impMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Recorded.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('impArea').value=''; document.getElementById('impAction').value=''; document.getElementById('impDate').value=''; depLoadImprovement(); }
    }).catch(function(){ document.getElementById('impMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function depLoadImprovement(){
    var el = document.getElementById('depImpList'); if(!el) return; el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('deputy-principal.php?view=improvement_data&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML='<div class="text-muted small p-3">No improvement records.</div>'; return; }
        var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Area</th><th>Action</th><th>Target</th><th>Progress</th><th>Status</th><th></th></tr></thead><tbody>';
        d.forEach(function(i){ var stCls=i.status==='completed'?'success':i.status==='in_progress'?'info':'warning text-dark'; h+='<tr><td><strong>'+esc(i.area)+'</strong></td><td class="small">'+esc(mb_substr(i.improvement_action,0,80))+'</td><td class="small">'+(i.target_date||'-')+'</td><td>'+(i.progress||0)+'%</td><td><span class="badge bg-'+stCls+'">'+esc(i.status)+'</span></td><td><button class="btn btn-sm btn-outline-primary" onclick="depUpdateImpProgress('+i.id+')"><i class="fas fa-edit"></i></button></td></tr>'; });
        h+='</tbody></table></div>'; el.innerHTML=h;
    }).catch(function(){ el.innerHTML='<div class="text-danger small p-3">Failed.</div>'; });
}
function depUpdateImpProgress(id){
    var pr = prompt('Progress % (0-100):'); if(pr === null) return;
    var st = prompt('Status (planned/in_progress/completed):');
    var fd = new FormData(); fd.append('id',id); fd.append('progress',parseFloat(pr)||0); if(st) fd.append('status',st);
    fetch('deputy-principal.php?view=update_improvement_progress&ajax=1',{method:'POST',body:fd})
    .then(function(r){ return r.json(); }).then(function(d){ if(d.success) depLoadImprovement(); }).catch(function(){});
}
document.addEventListener('DOMContentLoaded', depLoadImprovement);
</script>

<?php endif; ?>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
function esc(s){ if(!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function mbSubstr(s,n){ if(!s) return ''; return s.length>n?s.substring(0,n)+'...':s; }
</script>
</body></html>
