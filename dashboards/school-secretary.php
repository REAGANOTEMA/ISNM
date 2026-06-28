<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['school secretary', 'secretary']);
$staff = $ctx['staff']; $students = $ctx['students']; $website = $ctx['website'];
$user = $ctx['user']; $uid = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? ''; $uname = $_SESSION['full_name'] ?? 'Secretary';
$staff_db   = defined('STAFF_DB_NAME')    ? STAFF_DB_NAME    : 'igangaschoolofl_staffs_db';
$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschoolofl_students_db';
$migrate = function($db) use ($staff_db, $students_db) {
    if (!$db) return;
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.appointments (id INT AUTO_INCREMENT PRIMARY KEY, visitor_name VARCHAR(200), visitor_phone VARCHAR(50), visitor_email VARCHAR(100), staff_id INT DEFAULT 0, appointment_date DATE, appointment_time TIME, purpose TEXT, status ENUM('pending','approved','completed','cancelled') DEFAULT 'pending', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.meetings (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300), meeting_type VARCHAR(100), meeting_date DATE, start_time TIME, end_time TIME, location VARCHAR(200), agenda TEXT, minutes TEXT, status ENUM('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.meeting_attendees (id INT AUTO_INCREMENT PRIMARY KEY, meeting_id INT NOT NULL, attendee_name VARCHAR(200), attendee_role VARCHAR(100), attended ENUM('pending','present','absent') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.document_tracking (id INT AUTO_INCREMENT PRIMARY KEY, doc_title VARCHAR(300), doc_type VARCHAR(100), category VARCHAR(100), file_name VARCHAR(300), file_path VARCHAR(500), reference_number VARCHAR(100), description TEXT, status ENUM('draft','filed','archived') DEFAULT 'draft', uploaded_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.request_tracking (id INT AUTO_INCREMENT PRIMARY KEY, request_title VARCHAR(300), request_type VARCHAR(100), description TEXT, assigned_to VARCHAR(200), priority ENUM('low','normal','high','urgent') DEFAULT 'normal', status ENUM('pending','approved','rejected','completed') DEFAULT 'pending', requested_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.secretary_messages (id INT AUTO_INCREMENT PRIMARY KEY, sender_id INT DEFAULT 0, sender_name VARCHAR(200), recipient_type VARCHAR(50), recipient_id INT DEFAULT 0, subject VARCHAR(300), message TEXT, attachment VARCHAR(500), is_read TINYINT DEFAULT 0, read_at DATETIME DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.circulars (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300), reference VARCHAR(100), body TEXT, issued_by VARCHAR(200), department VARCHAR(100), file_name VARCHAR(300), file_path VARCHAR(500), status ENUM('draft','issued','archived') DEFAULT 'issued', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.correspondence (id INT AUTO_INCREMENT PRIMARY KEY, type ENUM('incoming','outgoing') DEFAULT 'incoming', reference VARCHAR(100), sender_name VARCHAR(200), recipient_name VARCHAR(200), subject VARCHAR(300), body TEXT, date_received DATE, date_sent DATE, file_name VARCHAR(300), file_path VARCHAR(500), status ENUM('pending','actioned','closed','archived') DEFAULT 'pending', handled_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.official_letters (id INT AUTO_INCREMENT PRIMARY KEY, letter_type VARCHAR(100), reference VARCHAR(100), title VARCHAR(300), recipient_name VARCHAR(200), recipient_address TEXT, body TEXT, letter_date DATE, issued_by VARCHAR(200), file_name VARCHAR(300), file_path VARCHAR(500), status ENUM('draft','issued','signed','archived') DEFAULT 'draft', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.contact_directory (id INT AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(200), organization VARCHAR(200), position VARCHAR(200), phone VARCHAR(50), email VARCHAR(100), address TEXT, category VARCHAR(100), notes TEXT, created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
$migrate($staff); $migrate($students);
$_GET['section'] = $_GET['section'] ?? $_GET['view'] ?? 'overview';
$view = $_GET['section']; if ($view === 'overview') $view = 'home';
$ajax = $_GET['ajax'] ?? ''; $sid = $_GET['sid'] ?? ''; $q = $_GET['q'] ?? '';
function currency($n) { return 'UGX ' . number_format((float)$n, 0); }
function sec_success($m) { $_SESSION['sec_success'] = $m; }
function sec_error($m) { $_SESSION['sec_error'] = $m; }if ($view === 'comms_send' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $rt = $_POST['recipient_type'] ?? ''; $subj = $_POST['subject'] ?? ''; $msg = $_POST['message'] ?? '';
    if ($subj && $msg) {
        $ok = false;
        if ($rt === 'staff_all' || $rt === 'staff') {
            $r = $staff->query("SELECT id FROM staff WHERE status='Active'");
            $ins = $staff->prepare("INSERT INTO {$students_db}.secretary_messages (sender_id,sender_name,recipient_type,recipient_id,subject,message) VALUES (?,?,'staff',?,?,?)");
            if ($ins) { while ($rw = $r->fetch_assoc()) { $ins->bind_param("issis", $uid, $uname, $rw['id'], $subj, $msg); $ins->execute(); } $ins->close(); $ok = true; }
        } elseif ($rt === 'students_all' || $rt === 'students') {
            $ins = $staff->prepare("INSERT INTO {$students_db}.secretary_messages (sender_id,sender_name,recipient_type,recipient_id,subject,message) VALUES (?,?,'students',0,?,?)");
            if ($ins) { $ins->bind_param("isss", $uid, $uname, $subj, $msg); $ok = $ins->execute(); $ins->close(); }
        } else {
            $ins = $staff->prepare("INSERT INTO {$students_db}.secretary_messages (sender_id,sender_name,recipient_type,recipient_id,subject,message) VALUES (?,?,?,0,?,?)");
            if ($ins) { $ins->bind_param("issss", $uid, $uname, $rt, $subj, $msg); $ok = $ins->execute(); $ins->close(); }
        }
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Subject and message required']); exit;
}
if ($view === 'comms_fetch' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.secretary_messages WHERE (recipient_id=0 OR recipient_id=$uid) ORDER BY created_at DESC LIMIT 50");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    echo json_encode($rows); exit;
}
if ($view === 'appointment_book' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $vn = $_POST['visitor_name']??'';
    $vp = $_POST['visitor_phone']??'';
    $ve = $_POST['visitor_email']??'';
    $si = (int)($_POST['staff_id']??0);
    $ad = $_POST['appointment_date']??'';
    $at = $_POST['appointment_time']??'';
    $pp = $_POST['purpose']??'';
    if ($vn && $ad) {
        $stmt = $staff->prepare("INSERT INTO {$students_db}.appointments (visitor_name,visitor_phone,visitor_email,staff_id,appointment_date,appointment_time,purpose,created_by) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param('sssssssi', $vn, $vp, $ve, $si, $ad, $at, $pp, $uid);
            if ($stmt->execute()) { echo json_encode(['success'=>true]); $stmt->close(); exit; }
            $stmt->close();
        }
        echo json_encode(['success'=>false,'error'=>'Database write failed']); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Name and date required']); exit;
}
if ($view === 'appointment_list' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT a.*, s.full_name as staff_name FROM {$students_db}.appointments a LEFT JOIN staff s ON a.staff_id=s.id ORDER BY a.appointment_date DESC LIMIT 50");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    echo json_encode($rows); exit;
}
if ($view === 'appointment_update' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $aid = (int)($_POST['id']??0); $st = trim($_POST['status']??'');
    if ($aid && $st) {
        $stmt = $staff->prepare("UPDATE {$students_db}.appointments SET status=? WHERE id=?");
        if ($stmt) { $stmt->bind_param('si', $st, $aid); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'meeting_create' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $mt = $_POST['title']??'';
    $md = $_POST['meeting_date']??'';
    $st = $_POST['start_time']??'';
    $et = $_POST['end_time']??'';
    $ml = $_POST['location']??'';
    $ag = $_POST['agenda']??'';
    $tp = $_POST['meeting_type']??'General';
    $at = $_POST['attendees'] ?? '';
    if ($mt && $md) {
        $stmt = $staff->prepare("INSERT INTO {$students_db}.meetings (title,meeting_type,meeting_date,start_time,end_time,location,agenda,created_by) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param('sssssssi', $mt, $tp, $md, $st, $et, $ml, $ag, $uid);
            if ($stmt->execute()) {
                $mid = $staff->insert_id;
                $stmt->close();
                if ($mid && $at) { $names = explode("\n", $at); foreach ($names as $n) { $n = trim($n); if ($n) { $ns = $staff->prepare("INSERT INTO {$students_db}.meeting_attendees (meeting_id,attendee_name) VALUES (?,?)"); if ($ns) { $ns->bind_param('is', $mid, $n); $ns->execute(); $ns->close(); } } } }
                echo json_encode(['success'=>true]); exit;
            }
            $stmt->close();
        }
        echo json_encode(['success'=>false,'error'=>'Database write failed']); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Title and date required']); exit;
}
if ($view === 'meeting_list' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.meetings ORDER BY meeting_date DESC LIMIT 50");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    echo json_encode($rows); exit;
}
if ($view === 'meeting_get' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $mid = (int)($_GET['id']??0);
    if ($mid) { $m = $staff->query("SELECT * FROM {$students_db}.meetings WHERE id=$mid")->fetch_assoc(); $at = $staff->query("SELECT * FROM {$students_db}.meeting_attendees WHERE meeting_id=$mid"); $attendees = []; if ($at) while ($a = $at->fetch_assoc()) $attendees[] = $a; echo json_encode(['meeting'=>$m,'attendees'=>$attendees]); exit; }
    echo json_encode(['error'=>'No id']); exit;
}
if ($view === 'meeting_save_minutes' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $mid = (int)($_POST['meeting_id']??0); $min = trim($_POST['minutes']??'');
    if ($mid) {
        $stmt = $staff->prepare("UPDATE {$students_db}.meetings SET minutes=?, status='completed' WHERE id=?");
        if ($stmt) { $stmt->bind_param('si', $min, $mid); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'doc_upload' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $dt = $_POST['doc_title']??'';
    $dc = $_POST['category']??'General';
    $dr = $_POST['reference_number']??'';
    $dd = $_POST['description']??'';
    $dtp = $_POST['doc_type']??'document';
    $fn = '';
    if (!empty($_FILES['doc_file']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/documents/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $fn = time() . '_' . basename($_FILES['doc_file']['name']);
        move_uploaded_file($_FILES['doc_file']['tmp_name'], $uploadDir . $fn);
    }
    if ($dt) {
        $fp = 'uploads/documents/'.$fn;
        $stmt = $staff->prepare("INSERT INTO {$students_db}.document_tracking (doc_title,doc_type,category,file_name,file_path,reference_number,description,uploaded_by) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('sssssssi', $dt, $dtp, $dc, $fn, $fp, $dr, $dd, $uid); if ($stmt->execute()) { echo json_encode(['success'=>true]); $stmt->close(); exit; } $stmt->close(); }
        echo json_encode(['success'=>false,'error'=>'Upload failed']); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'doc_list' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $sf = trim($_GET['status']??'');
    if ($sf) {
        $stmt = $staff->prepare("SELECT * FROM {$students_db}.document_tracking WHERE status=? ORDER BY created_at DESC LIMIT 50");
        if ($stmt) { $stmt->bind_param('s', $sf); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    } else {
        $r = $staff->query("SELECT * FROM {$students_db}.document_tracking ORDER BY created_at DESC LIMIT 50");
    }
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'doc_update' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $did = (int)($_POST['id']??0); $st = trim($_POST['status']??'');
    if ($did && $st) {
        $stmt = $staff->prepare("UPDATE {$students_db}.document_tracking SET status=? WHERE id=?");
        if ($stmt) { $stmt->bind_param('si', $st, $did); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'request_create' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $rt2 = $_POST['request_title']??'';
    $rd = $_POST['description']??'';
    $ra = $_POST['assigned_to']??'';
    $rp = $_POST['priority']??'normal';
    $rty = $_POST['request_type']??'general';
    if ($rt2) {
        $stmt = $staff->prepare("INSERT INTO {$students_db}.request_tracking (request_title,request_type,description,assigned_to,priority,requested_by) VALUES (?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('sssssi', $rt2, $rty, $rd, $ra, $rp, $uid); if ($stmt->execute()) { echo json_encode(['success'=>true]); $stmt->close(); exit; } $stmt->close(); }
        echo json_encode(['success'=>false,'error'=>'Create failed']); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'request_list' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $f = $_GET['filter'] ?? '';
    $sql = "SELECT * FROM {$students_db}.request_tracking";
    if ($f === 'assigned') {
        $sql .= " WHERE assigned_to LIKE ?";
    } elseif ($f === 'pending') {
        $sql .= " WHERE status='pending'";
    }
    $sql .= " ORDER BY created_at DESC LIMIT 50";
    $stmt = $staff->prepare($sql);
    if ($stmt && $f === 'assigned') { $like = "%$uname%"; $stmt->bind_param('s', $like); }
    $rows = [];
    if ($stmt) { $stmt->execute(); $r = $stmt->get_result(); if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; $stmt->close(); }
    echo json_encode($rows); exit;
}
if ($view === 'request_update' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $rid = (int)($_POST['id']??0); $st = trim($_POST['status']??'');
    if ($rid && $st) {
        $stmt = $staff->prepare("UPDATE {$students_db}.request_tracking SET status=? WHERE id=?");
        if ($stmt) { $stmt->bind_param('si', $st, $rid); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'staff_search' && $ajax === '1' && $q && $staff) {
    header('Content-Type: application/json');
    $like = "%$q%";
    $stmt = $staff->prepare("SELECT id, full_name, position, department, phone, email FROM staff WHERE full_name LIKE ? OR position LIKE ? OR department LIKE ? OR phone LIKE ? LIMIT 20");
    if ($stmt) { $stmt->bind_param('ssss', $like, $like, $like, $like); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'applicant_search' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $q = trim($_GET['q']??'');
    $prog = trim($_GET['program']??'');
    $intake = trim($_GET['intake']??'');
    $status = trim($_GET['status']??'');
    $w = ["1=1"]; $params = []; $types = '';
    if ($q) { $w[] = "(CONCAT(IFNULL(surname,''),' ',IFNULL(first_name,'')) LIKE ? OR student_number LIKE ?)"; $like = "%$q%"; $params[] = $like; $params[] = $like; $types .= 'ss'; }
    if ($prog) { $w[] = "program=?"; $params[] = $prog; $types .= 's'; }
    if ($intake) { $w[] = "(intake_year LIKE ? OR intake_period LIKE ?)"; $like2 = "%$intake%"; $params[] = $like2; $params[] = $like2; $types .= 'ss'; }
    if ($status) { $w[] = "status=?"; $params[] = $status; $types .= 's'; }
    $sql = "SELECT student_number, first_name, surname, other_name, program, intake_year, intake_period, phone, email, status FROM {$students_db}.students WHERE " . implode(" AND ", $w) . " ORDER BY surname ASC LIMIT 100";
    $stmt = $staff->prepare($sql);
    if ($stmt) { if ($types) $stmt->bind_param($types, ...$params); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    $rows = [];
    if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    echo json_encode($rows); exit;
}
if ($view === 'stats_data' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $now = date('Y-m-d');
    $mc = $staff->query("SELECT COUNT(*) c FROM {$students_db}.meetings WHERE meeting_date='$now'")->fetch_assoc()['c'] ?? 0;
    $ac = $staff->query("SELECT COUNT(*) c FROM {$students_db}.appointments WHERE appointment_date='$now' AND status='pending'")->fetch_assoc()['c'] ?? 0;
    $msc = $staff->query("SELECT COUNT(*) c FROM {$students_db}.secretary_messages WHERE (recipient_id=0 OR recipient_id=$uid) AND is_read=0")->fetch_assoc()['c'] ?? 0;
    $rc = $staff->query("SELECT COUNT(*) c FROM {$students_db}.request_tracking WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
    echo json_encode(['meetings'=>$mc,'appointments'=>$ac,'messages'=>$msc,'requests'=>$rc]); exit;
}
if ($view === 'correspondence_create' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $ct = $_POST['type']??'incoming';
    $cr = $_POST['reference']??'';
    $cs = $_POST['sender_name']??'';
    $crc = $_POST['recipient_name']??'';
    $csu = $_POST['subject']??'';
    $cb = $_POST['body']??'';
    $cdr = $_POST['date_received']??date('Y-m-d');
    $cds = $_POST['date_sent']??date('Y-m-d');
    $fn = '';
    if (!empty($_FILES['corr_file']['name'])) { $ud = __DIR__ . '/../uploads/correspondence/'; if (!is_dir($ud)) @mkdir($ud,0755,true); $fn = time().'_'.basename($_FILES['corr_file']['name']); move_uploaded_file($_FILES['corr_file']['tmp_name'],$ud.$fn); }
    $fp = 'uploads/correspondence/'.$fn;
    $stmt = $staff->prepare("INSERT INTO {$students_db}.correspondence (type,reference,sender_name,recipient_name,subject,body,date_received,date_sent,file_name,file_path,handled_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    if ($stmt) { $stmt->bind_param('ssssssssssi', $ct, $cr, $cs, $crc, $csu, $cb, $cdr, $cds, $fn, $fp, $uid); if ($stmt->execute()) { echo json_encode(['success'=>true]); $stmt->close(); exit; } $stmt->close(); }
    echo json_encode(['success'=>false,'error'=>'Database write failed']); exit;
}
if ($view === 'correspondence_list' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $f = trim($_GET['filter']??'');
    if ($f === 'incoming' || $f === 'outgoing') {
        $stmt = $staff->prepare("SELECT * FROM {$students_db}.correspondence WHERE type=? ORDER BY created_at DESC LIMIT 50");
        if ($stmt) { $stmt->bind_param('s', $f); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    } else {
        $r = $staff->query("SELECT * FROM {$students_db}.correspondence ORDER BY created_at DESC LIMIT 50");
    }
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'letter_create' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $lt = $_POST['letter_type']??'Official';
    $lr = $_POST['reference']??'';
    $lti = $_POST['title']??'';
    $lrn = $_POST['recipient_name']??'';
    $lra = $_POST['recipient_address']??'';
    $lb = $_POST['body']??'';
    $lld = $_POST['letter_date']??date('Y-m-d');
    if ($lti && $lrn) {
        $stmt = $staff->prepare("INSERT INTO {$students_db}.official_letters (letter_type,reference,title,recipient_name,recipient_address,body,letter_date,issued_by,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('ssssssssi', $lt, $lr, $lti, $lrn, $lra, $lb, $lld, $uname, $uid); if ($stmt->execute()) { echo json_encode(['success'=>true,'id'=>$staff->insert_id]); $stmt->close(); exit; } $stmt->close(); }
        echo json_encode(['success'=>false,'error'=>'Database write failed']); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Title and recipient required']); exit;
}
if ($view === 'letter_list' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.official_letters ORDER BY created_at DESC LIMIT 50");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'letter_update' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $lid = (int)($_POST['id']??0); $st = trim($_POST['status']??'');
    if ($lid && $st) {
        $stmt = $staff->prepare("UPDATE {$students_db}.official_letters SET status=? WHERE id=?");
        if ($stmt) { $stmt->bind_param('si', $st, $lid); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'circular_create' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $ci = $_POST['title']??''; $cr = $_POST['reference']??''; $cb = $_POST['body']??''; $cd = $_POST['department']??''; $fn = '';
    if (!empty($_FILES['circ_file']['name'])) { $ud = __DIR__.'/../uploads/circulars/'; if(!is_dir($ud)) @mkdir($ud,0755,true); $fn = time().'_'.basename($_FILES['circ_file']['name']); move_uploaded_file($_FILES['circ_file']['tmp_name'],$ud.$fn); }
    if ($ci) { $fp = 'uploads/circulars/'.$fn; $stmt = $staff->prepare("INSERT INTO {$students_db}.circulars (title,reference,body,department,file_name,file_path,issued_by,created_by) VALUES (?,?,?,?,?,?,?,?)"); if ($stmt) { $stmt->bind_param('sssssssi', $ci, $cr, $cb, $cd, $fn, $fp, $uname, $uid); if ($stmt->execute()) { echo json_encode(['success'=>true]); $stmt->close(); exit; } $stmt->close(); } echo json_encode(['success'=>false,'error'=>'Create failed']); exit; }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'circular_list' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.circulars ORDER BY created_at DESC LIMIT 50");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'contact_create' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $cn = $_POST['full_name']??''; $co = $_POST['organization']??''; $cp = $_POST['position']??''; $cph = $_POST['phone']??''; $ce = $_POST['email']??''; $ca = $_POST['address']??''; $cc = $_POST['category']??'General'; $cn2 = $_POST['notes']??'';
    if ($cn) { $stmt = $staff->prepare("INSERT INTO {$students_db}.contact_directory (full_name,organization,position,phone,email,address,category,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?)"); if ($stmt) { $stmt->bind_param('ssssssssi', $cn, $co, $cp, $cph, $ce, $ca, $cc, $cn2, $uid); if ($stmt->execute()) { echo json_encode(['success'=>true]); $stmt->close(); exit; } $stmt->close(); } echo json_encode(['success'=>false,'error'=>'Create failed']); exit; }
    echo json_encode(['success'=>false]); exit;
}
if ($view === 'contact_list' && $ajax === '1' && $staff) {
    header('Content-Type: application/json');
    $r = $staff->query("SELECT * FROM {$students_db}.contact_directory ORDER BY full_name ASC LIMIT 100");
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if ($view === 'report_data' && $ajax === '1') {
    header('Content-Type: application/json');
    if (!$staff) { echo json_encode([]); exit; }
    $from = trim($_GET['from']??date('Y-m-01')); $to = trim($_GET['to']??date('Y-m-d')); $tp = trim($_GET['type']??'appointments');
    $allowed_types = ['meetings','documents','requests','communications','appointments'];
    if (!in_array($tp, $allowed_types)) $tp = 'appointments';
    $table = $tp === 'meetings' ? 'meetings' : ($tp === 'documents' ? 'document_tracking' : ($tp === 'requests' ? 'request_tracking' : ($tp === 'communications' ? 'secretary_messages' : 'appointments')));
    $dateCol = $tp === 'meetings' ? 'meeting_date' : 'created_at';
    if ($tp === 'appointments') $dateCol = 'appointment_date';
    $stmt = $staff->prepare("SELECT * FROM {$students_db}.{$table} WHERE {$dateCol} >= ? AND {$dateCol} <= ? ORDER BY {$dateCol} DESC LIMIT 200");
    if ($stmt) { $stmt->bind_param('ss', $from, $to); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else $r = null;
    $rows = []; if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw; echo json_encode($rows); exit;
}
if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode([]); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];
    if ($act === 'publish_announcement' && $students && $staff) {
        $t = trim($_POST['ann_title']??''); $b = trim($_POST['ann_body']??''); $tg = $_POST['ann_target']??'All'; $pr = $_POST['ann_priority']??'Normal';
        if ($t && $b) {
            $stmt = $students->prepare("INSERT INTO announcements (title,body,target_audience,priority,posted_by,is_active,created_at) VALUES (?,?,?,?,?,1,NOW())");
            if ($stmt) { $stmt->bind_param('ssssi', $t, $b, $tg, $pr, $uid); if ($stmt->execute()) { sec_success('Announcement published.'); } else { sec_error('Database write failed.'); } $stmt->close(); }
            else { sec_error('Database write failed.'); }
        } else { sec_error('Title and body required.'); }
        header('Location: school-secretary.php?section=announcements'); exit;
    }
    if ($act === 'send_message' && $staff) {
        $subj = trim($_POST['msg_subject']??''); $body = trim($_POST['msg_body']??''); $rt = $_POST['msg_recipient']??'staff';
        if ($subj && $body) {
            $stmt = $staff->prepare("INSERT INTO {$students_db}.secretary_messages (sender_id,sender_name,recipient_type,subject,message) VALUES (?,?,?,?,?)");
            if ($stmt) { $stmt->bind_param('issss', $uid, $uname, $rt, $subj, $body); if ($stmt->execute()) { sec_success('Message sent.'); } else { sec_error('Database write failed.'); } $stmt->close(); }
            else { sec_error('Database write failed.'); }
        } else { sec_error('Subject and message required.'); }
        header('Location: school-secretary.php?section=comms'); exit;
    }
}
$sv = $_SESSION['sec_success'] ?? ''; $ev = $_SESSION['sec_error'] ?? '';
unset($_SESSION['sec_success'], $_SESSION['sec_error']);?>
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
.search-card .sri{cursor:pointer;padding:8px 12px;border-radius:8px;transition:background .15s}
.search-card .sri:hover{background:#eef2ff}
.dep-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px}
@media(max-width:768px){.dep-grid{grid-template-columns:1fr 1fr}}
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
</style>
</head><body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="ma content-section dashboard-section active" data-section="secretary" style="margin-left:270px;padding:24px">
<div class="ph mb-4">
<div><h1><i class="fas fa-user-tie me-2"></i>School Secretary Dashboard</h1><p class="text-muted">Administrative Support &amp; Office Management</p></div>
<a href="school-secretary.php" class="bo btn-sm <?= $view==='home'?'d-none':'' ?>"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
<?php if ($sv): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($sv) ?></div><?php endif; ?>
<?php if ($ev): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($ev) ?></div><?php endif; ?><?php if ($view === 'home'): ?>
<?php
$now = date('Y-m-d'); $mToday = $aToday = $msgUnread = $reqPend = 0;
try {
    if ($staff) {
        $r = $staff->query("SELECT COUNT(*) c FROM {$students_db}.meetings WHERE meeting_date='$now'"); if ($r) $mToday = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COUNT(*) c FROM {$students_db}.appointments WHERE appointment_date='$now' AND status='pending'"); if ($r) $aToday = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COUNT(*) c FROM {$students_db}.secretary_messages WHERE is_read=0 AND (recipient_id=0 OR recipient_id=$uid)"); if ($r) $msgUnread = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COUNT(*) c FROM {$students_db}.request_tracking WHERE status='pending'"); if ($r) $reqPend = (int)$r->fetch_assoc()['c'];
    }
} catch (Exception $e) {}
$totalStudents = 0; $totalStaff = 0;
if ($students) { $r = $students->query("SELECT COUNT(*) c FROM students WHERE status='Active'"); if ($r) $totalStudents = (int)$r->fetch_assoc()['c']; }
if ($staff) { $r = $staff->query("SELECT COUNT(*) c FROM staff WHERE status='Active'"); if ($r) $totalStaff = (int)$r->fetch_assoc()['c']; }
$recentActs = []; $recentComms = []; $recentReqs = [];
try {
    if ($staff) {
        $r = $staff->query("SELECT subject, created_at FROM {$students_db}.secretary_messages ORDER BY created_at DESC LIMIT 5");
        if ($r) while ($a = $r->fetch_assoc()) $recentComms[] = $a;
        $r = $staff->query("SELECT request_title, status, created_at FROM {$students_db}.request_tracking ORDER BY created_at DESC LIMIT 5");
        if ($r) while ($a = $r->fetch_assoc()) $recentReqs[] = $a;
    }
} catch (Exception $e) {}
?>
<div class="row g-3 mb-4">
<div class="col-md-3 col-6"><div class="kpi-card primary"><div class="kpi-icon"><i class="fas fa-users"></i></div><div class="kpi-value"><?= number_format($totalStudents) ?></div><div class="kpi-label">Active Students</div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card success"><div class="kpi-icon"><i class="fas fa-user-tie"></i></div><div class="kpi-value"><?= number_format($totalStaff) ?></div><div class="kpi-label">Staff Members</div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card info"><div class="kpi-icon"><i class="fas fa-calendar-check"></i></div><div class="kpi-value"><?= $mToday ?></div><div class="kpi-label">Today's Meetings</div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card warning"><div class="kpi-icon"><i class="fas fa-clock"></i></div><div class="kpi-value"><?= $aToday ?></div><div class="kpi-label">Pending Appointments</div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card purple"><div class="kpi-icon"><i class="fas fa-envelope"></i></div><div class="kpi-value"><?= $msgUnread ?></div><div class="kpi-label">Unread Messages</div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card danger"><div class="kpi-icon"><i class="fas fa-tasks"></i></div><div class="kpi-value"><?= $reqPend ?></div><div class="kpi-label">Pending Requests</div></div></div>
</div>
<div class="row g-3">
<div class="col-md-8">
<div class="scard"><div class="sch"><i class="fas fa-bolt me-2"></i>Quick Actions</div><div class="scb">
<div class="row g-2">
<div class="col-md-3 col-6"><a href="?section=comms" class="btn btn-sec w-100"><i class="fas fa-comments me-1"></i>New Message</a></div>
<div class="col-md-3 col-6"><a href="?section=appointments" class="btn btn-sec w-100"><i class="fas fa-calendar-plus me-1"></i>Appointment</a></div>
<div class="col-md-3 col-6"><a href="?section=meetings" class="btn btn-sec w-100"><i class="fas fa-handshake me-1"></i>New Meeting</a></div>
<div class="col-md-3 col-6"><a href="?section=documents" class="btn btn-sec w-100"><i class="fas fa-upload me-1"></i>New Document</a></div>
</div>
</div></div>
<div class="scard mt-3"><div class="sch"><i class="fas fa-clock me-2"></i>Recent Activity</div><div class="scb">
<?php if ($recentComms || $recentReqs): ?>
<div class="mb-2"><small class="text-muted fw-bold">Latest Communications</small></div>
<?php foreach ($recentComms as $a): ?>
<div class="act-item py-1"><div class="d-flex justify-content-between"><span><i class="fas fa-envelope text-primary me-2 small"></i><?= htmlspecialchars(mb_substr($a['subject'],0,60)) ?></span><span class="time"><?= date('d M',strtotime($a['created_at'])) ?></span></div></div>
<?php endforeach; ?>
<div class="mb-2 mt-2"><small class="text-muted fw-bold">Recent Requests</small></div>
<?php foreach ($recentReqs as $a): ?>
<div class="act-item py-1"><div class="d-flex justify-content-between"><span><i class="fas fa-clipboard text-warning me-2 small"></i><?= htmlspecialchars(mb_substr($a['request_title'],0,60)) ?></span><span class="badge bg-<?= $a['status']==='pending'?'warning text-dark':($a['status']==='approved'?'success':'secondary') ?>"><?= htmlspecialchars($a['status']) ?></span></div></div>
<?php endforeach; ?>
<?php else: ?>
<div class="text-muted small">No recent activity.</div>
<?php endif; ?>
</div></div>
</div>
<div class="col-md-4">
<div class="scard"><div class="sch"><i class="fas fa-calendar-day me-2"></i>Today's Schedule</div><div class="scb p-0">
<?php
$todayItems = '';
try { if ($staff) {
    $r = $staff->query("SELECT title, start_time, location FROM {$students_db}.meetings WHERE meeting_date='$now' ORDER BY start_time LIMIT 10");
    if ($r) while ($m = $r->fetch_assoc()) $todayItems .= '<div class="act-item"><div class="fw-bold small">'.htmlspecialchars($m['title']).'</div><div class="time">'.htmlspecialchars($m['start_time']??'--').' &middot; '.htmlspecialchars($m['location']??'').'</div></div>';
    $r = $staff->query("SELECT visitor_name, appointment_time FROM {$students_db}.appointments WHERE appointment_date='$now' AND status='pending' ORDER BY appointment_time LIMIT 5");
    if ($r) while ($a = $r->fetch_assoc()) $todayItems .= '<div class="act-item py-1"><span><i class="fas fa-user text-info me-2 small"></i>'.htmlspecialchars($a['visitor_name']).'</span><span class="time">'.htmlspecialchars($a['appointment_time']??'--').'</span></div>';
} } catch (Exception $e) {}
echo $todayItems ?: '<div class="p-3 text-muted small">No items scheduled today.</div>';
?>
</div></div>
<div class="scard mt-3"><div class="sch"><i class="fas fa-chart-simple me-2"></i>Quick Stats</div><div class="scb">
<div class="row g-2">
<div class="col-6"><div class="border rounded p-2 text-center"><div class="fw-bold h5 mb-0 text-primary"><?= $mToday ?></div><small class="text-muted">Meetings</small></div></div>
<div class="col-6"><div class="border rounded p-2 text-center"><div class="fw-bold h5 mb-0 text-success"><?= $aToday ?></div><small class="text-muted">Appointments</small></div></div>
<div class="col-6"><div class="border rounded p-2 text-center"><div class="fw-bold h5 mb-0 text-warning"><?= $msgUnread ?></div><small class="text-muted">Unread</small></div></div>
<div class="col-6"><div class="border rounded p-2 text-center"><div class="fw-bold h5 mb-0 text-danger"><?= $reqPend ?></div><small class="text-muted">Requests</small></div></div>
</div>
</div></div>
</div>
</div>
<?php endif; ?><?php if ($view === 'student_search'): ?>
<div class="scard"><div class="sch"><i class="fas fa-search me-2"></i>Student Search</div><div class="scb">
<p class="text-muted small">Search by name, registration number, admission number, student number, or phone.</p>
<form onsubmit="event.preventDefault(); secSearchStudent()" class="row g-2 mb-3">
<div class="col-md-6"><input type="text" id="secStudQ" class="form-control env-field" placeholder="Name, reg number, phone..." autocomplete="off"></div>
<div class="col-md-3"><select id="secStudFilter" class="form-select env-field"><option value="">All Programs</option><option value="Nursing">Nursing</option><option value="Midwifery">Midwifery</option></select></div>
<div class="col-md-3"><button type="submit" class="btn btn-sec w-100"><i class="fas fa-search me-1"></i>Search</button></div>
</form>
<div id="secStudResults" class="row g-2"></div>
<div id="secStudProfile" class="d-none"></div>
</div></div>
<script>
function secSearchStudent(){
    var q = document.getElementById('secStudQ').value.trim(); var f = document.getElementById('secStudFilter').value;
    if(!q){ document.getElementById('secStudResults').innerHTML='<div class="text-muted small">Enter a search term.</div>'; return; }
    var el = document.getElementById('secStudResults'); el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('../includes/ajax_student_search.php?q='+encodeURIComponent(q)+(f?'&program='+encodeURIComponent(f):''))
    .then(function(r){ return r.json(); })
    .then(function(d){
        el.innerHTML = '';
        if(!d||!d.students||!d.students.length){ el.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-user-slash fa-3x mb-3"></i><p>No students found matching your search.</p></div>'; return; }
        d.students.forEach(function(s){
            var card = document.createElement('div'); card.className = 'col-md-6';
            var initials = ((s.surname?s.surname[0]:'')+(s.first_name?s.first_name[0]:''))||'?';
            card.innerHTML = '<div class="search-card border rounded p-3 d-flex align-items-center gap-3" style="cursor:pointer" onclick="secShowStudent(\''+esc(s.student_id)+'\')"><div style="width:50px;height:50px;border-radius:50%;background:#e8eaf6;display:flex;align-items:center;justify-content:center;font-weight:700;color:#1a237e;font-size:20px">'+initials+'</div><div><strong>'+esc(s.surname)+', '+esc(s.first_name)+'</strong><br><small class="text-muted">'+esc(s.student_id)+' | '+(s.program||'')+' L'+(s.level||'')+'</small></div></div>';
            el.appendChild(card);
        });
    }).catch(function(){ el.innerHTML = '<div class="text-danger small">Search failed.</div>'; });
}
function secShowStudent(sid){
    document.getElementById('secStudProfile').classList.remove('d-none');
    document.getElementById('secStudProfile').innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    document.getElementById('secStudResults').innerHTML = '';
    fetch('../includes/ajax_student_search.php?q='+encodeURIComponent(sid))
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.students||!d.students.length){ document.getElementById('secStudProfile').innerHTML = '<div class="alert alert-warning">Student not found.</div>'; return; }
        var s = d.students[0];
        var initials = ((s.surname?s.surname[0]:'')+(s.first_name?s.first_name[0]:''))||'?';
        var h = '<div class="cc mt-2"><div class="ch"><i class="fas fa-user-graduate me-2"></i>Student Details</div><div class="cb"><div class="row g-3"><div class="col-md-2 text-center"><div style="width:80px;height:80px;border-radius:50%;background:#e8eaf6;display:flex;align-items:center;justify-content:center;font-weight:700;color:#1a237e;font-size:28px;margin:0 auto">'+initials+'</div></div><div class="col-md-5"><table class="table table-sm table-borderless mb-0"><tr><td class="text-muted" style="width:130px">Name</td><td><strong>'+esc(s.surname)+', '+esc(s.first_name)+' '+(s.other_name||'')+'</strong></td></tr><tr><td class="text-muted">Student No.</td><td>'+esc(s.student_id||'')+'</td></tr><tr><td class="text-muted">Program</td><td>'+esc(s.program||'')+'</td></tr><tr><td class="text-muted">Level</td><td>'+esc(s.level||'')+'</td></tr><tr><td class="text-muted">Intake</td><td>'+esc(s.intake_year||'')+' '+esc(s.intake_period||'')+'</td></tr></table></div><div class="col-md-5"><table class="table table-sm table-borderless mb-0"><tr><td class="text-muted" style="width:100px">Phone</td><td>'+esc(s.phone||'')+'</td></tr><tr><td class="text-muted">Email</td><td>'+esc(s.email||'')+'</td></tr><tr><td class="text-muted">Guardian</td><td>'+esc(s.guardian_name||'')+'</td></tr><tr><td class="text-muted">Guardian Phone</td><td>'+esc(s.guardian_phone||'')+'</td></tr></table></div></div></div></div>';
        document.getElementById('secStudProfile').innerHTML = h;
    }).catch(function(){ document.getElementById('secStudProfile').innerHTML = '<div class="alert alert-danger">Failed to load student details.</div>'; });
}
</script>
<?php endif; ?><?php if ($view === 'staff_search'): ?>
<div class="scard"><div class="sch"><i class="fas fa-user-search me-2"></i>Staff Search</div><div class="scb">
<p class="text-muted small">Search by name, department, staff number, or position.</p>
<form onsubmit="event.preventDefault(); secSearchStaff()" class="row g-2 mb-3">
<div class="col-md-8"><input type="text" id="secStaffQ" class="form-control env-field" placeholder="Name, department, position..." autocomplete="off"></div>
<div class="col-md-4"><button type="submit" class="btn btn-sec w-100"><i class="fas fa-search me-1"></i>Search</button></div>
</form>
<div id="secStaffResults"></div>
</div></div>
<script>
function secSearchStaff(){
    var q = document.getElementById('secStaffQ').value.trim();
    if(!q){ document.getElementById('secStaffResults').innerHTML='<div class="text-muted small">Enter a search term.</div>'; return; }
    var el = document.getElementById('secStaffResults'); el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    fetch('school-secretary.php?view=staff_search&ajax=1&q='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(d){
        el.innerHTML = '';
        if(!d||!d.length){ el.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-user-slash fa-3x mb-3"></i><p>No staff found.</p></div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Name</th><th>Department</th><th>Position</th><th>Phone</th><th>Email</th></tr></thead><tbody>';
        d.forEach(function(s){ h += '<tr><td><strong>'+esc(s.full_name)+'</strong></td><td>'+esc(s.department||'-')+'</td><td>'+esc(s.position||'-')+'</td><td>'+esc(s.phone||'-')+'</td><td><a href="mailto:'+esc(s.email)+'" class="text-decoration-none">'+esc(s.email||'-')+'</a></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small">Search failed.</div>'; });
}
</script>
<?php endif; ?><?php if ($view === 'admissions'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-signature me-2"></i>Admissions Support</div><div class="scb">
<p class="text-muted small">Search enrolled students and applicants by name, program, intake, or status. View details, print letters, or send communications. <strong>No admission approval actions are available here.</strong></p>
<form onsubmit="event.preventDefault(); secSearchApplicants()" class="row g-2 mb-3">
<div class="col-md-4"><input type="text" id="applQ" class="form-control env-field" placeholder="Name or student number..." autocomplete="off"></div>
<div class="col-md-2"><select id="applProg" class="form-select env-field"><option value="">All Programs</option><option value="Nursing">Nursing</option><option value="Midwifery">Midwifery</option></select></div>
<div class="col-md-2"><input type="text" id="applIntake" class="form-control env-field" placeholder="Intake (e.g. 2025)"></div>
<div class="col-md-2"><select id="applStatus" class="form-select env-field"><option value="">All Status</option><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="Graduated">Graduated</option></select></div>
<div class="col-md-2"><button type="submit" class="btn btn-sec w-100"><i class="fas fa-search me-1"></i>Search</button></div>
</form>
<div id="applicantResults"></div>
</div></div>
<script>
function secSearchApplicants(){
    var q = document.getElementById('applQ').value.trim();
    var prog = document.getElementById('applProg').value;
    var intake = document.getElementById('applIntake').value.trim();
    var status = document.getElementById('applStatus').value;
    var el = document.getElementById('applicantResults');
    if(!q && !prog && !intake && !status){ el.innerHTML = '<div class="text-muted small py-2">Enter search criteria or select filters.</div>'; return; }
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    var params = 'view=applicant_search&ajax=1';
    if(q) params += '&q='+encodeURIComponent(q);
    if(prog) params += '&program='+encodeURIComponent(prog);
    if(intake) params += '&intake='+encodeURIComponent(intake);
    if(status) params += '&status='+encodeURIComponent(status);
    fetch('school-secretary.php?'+params)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-user-slash fa-3x mb-2"></i><p>No records found.</p></div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Name</th><th>Student #</th><th>Program</th><th>Intake</th><th>Phone</th><th>Email</th><th>Status</th><th></th></tr></thead><tbody>';
        d.forEach(function(s){
            var fn = s.surname+', '+s.first_name+(s.other_name?' '+s.other_name:'');
            var intakeStr = (s.intake_year||'')+(s.intake_period?' '+s.intake_period:'');
            var stCls = s.status==='Active'?'success':s.status==='Graduated'?'info':'secondary';
            h += '<tr><td><strong>'+esc(fn)+'</strong></td><td class="small">'+esc(s.student_number||'')+'</td><td>'+esc(s.program||'')+'</td><td class="small">'+esc(intakeStr)+'</td><td>'+esc(s.phone||'-')+'</td><td><a href="mailto:'+esc(s.email)+'" class="text-decoration-none small">'+esc(s.email||'-')+'</a></td><td><span class="badge bg-'+stCls+'">'+esc(s.status)+'</span></td><td><div class="dropdown"><button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="mailto:'+esc(s.email)+'"><i class="fas fa-envelope me-2"></i>Send Email</a></li><li><a class="dropdown-item" href="#" onclick="secPrintApplicantLetter(\''+esc(s.student_number)+'\')"><i class="fas fa-print me-2"></i>Print Letter</a></li><li><a class="dropdown-item" href="?section=student_search&sid='+esc(s.student_number)+'"><i class="fas fa-eye me-2"></i>View Details</a></li></ul></div></td></tr>';
        });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small">Search failed.</div>'; });
}
function secPrintApplicantLetter(sid){
    fetch('../includes/ajax_student_search.php?q='+encodeURIComponent(sid))
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.students||!d.students.length){ alert('Student not found'); return; }
        var s = d.students[0]; var fn = (s.surname||'')+' '+(s.first_name||'');
        var w = window.open('','_blank','width=800,height=600');
        w.document.write('<html><head><title>Admission Letter - '+esc(fn)+'</title><style>body{font-family:serif;padding:40px;line-height:1.8;font-size:13pt}@media print{body{padding:20px}}</style></head><body>');
        w.document.write('<div style="text-align:center;margin-bottom:30px"><h2>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2><p style="font-size:11pt">P.O. Box 135, Masaka | Email: igangaschool@gmail.com | Tel: 0754-984573</p><hr></div>');
        w.document.write('<div style="text-align:right"><p><strong>Date:</strong> '+new Date().toLocaleDateString('en-UG',{year:'numeric',month:'long',day:'numeric'})+'</p></div>');
        w.document.write('<p><strong>Student Name:</strong> '+esc(fn)+'</p>');
        w.document.write('<p><strong>Student No:</strong> '+esc(s.student_id||'')+'</p>');
        w.document.write('<p><strong>Program:</strong> '+esc(s.program||'')+' | Level: '+esc(s.level||'')+'</p>');
        w.document.write('<p><strong>Intake:</strong> '+esc(s.intake_year||'')+' '+esc(s.intake_period||'')+'</p><br>');
        w.document.write('<p>Dear '+esc(fn)+',</p>');
        w.document.write('<p>This is to confirm your admission status and enrollment at Iganga School of Nursing and Midwifery. You are currently enrolled in the <strong>'+esc(s.program||'')+'</strong> program.</p>');
        w.document.write('<p>Your student number is <strong>'+esc(s.student_id||'')+'</strong>. Please keep this letter for your records.</p><br>');
        w.document.write('<p>Yours faithfully,</p><br><br>');
        w.document.write('<p><strong>School Secretary</strong></p>');
        w.document.write('<hr><p style="font-size:10pt;text-align:center;color:#666">This is a computer-generated document. No signature is required.</p>');
        w.document.write('</body></html>'); w.document.close();
        setTimeout(function(){ w.print(); }, 500);
    }).catch(function(){ alert('Failed to load student details.'); });
}
</script>
<?php endif; ?><?php if ($view === 'comms'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-paper-plane me-2"></i>Send Message</div><div class="scb">
<form id="secCommsForm">
<div class="mb-3"><label class="fl">Recipient</label><select id="msgRecipient" class="form-select env-field">
<option value="staff">All Staff</option><option value="director">Director General</option><option value="principal">Principal</option><option value="deputy">Deputy Principal</option><option value="academics">Academic Director</option><option value="finance">Finance Director</option><option value="bursar">Bursar</option><option value="registrar">Registrar</option><option value="hr">HR Manager</option><option value="admissions">Admissions Director</option><option value="librarian">Librarian</option><option value="ict">ICT Director</option><option value="hods">HODs</option><option value="lecturers">Lecturers</option><option value="matrons">Matrons</option><option value="wardens">Wardens</option><option value="students_all">All Students</option>
</select></div>
<div class="mb-3"><label class="fl">Subject *</label><input type="text" id="msgSubject" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Message *</label><textarea id="msgBody" class="form-control env-field" rows="4" required></textarea></div>
<button type="button" class="btn btn-sec" onclick="secSendComms()"><i class="fas fa-paper-plane me-1"></i>Send</button>
</form>
<div id="commsMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-inbox me-2"></i>Sent Messages</div><div class="scb p-0">
<div id="secMsgList"></div>
</div></div>
</div>
</div>
<script>
function secSendComms(){
    var fd = new FormData(); fd.append('recipient_type', document.getElementById('msgRecipient').value);
    fd.append('subject', document.getElementById('msgSubject').value); fd.append('message', document.getElementById('msgBody').value);
    fetch('school-secretary.php?view=comms_send&ajax=1', {method:'POST', body:fd})
    .then(function(r){ return r.json(); })
    .then(function(d){
        document.getElementById('commsMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Message sent.</div>' : '<div class="alert alert-danger py-1 small">Failed.</div>';
        if(d.success){ document.getElementById('msgSubject').value=''; document.getElementById('msgBody').value=''; secLoadMessages(); }
    }).catch(function(){ document.getElementById('commsMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function secLoadMessages(){
    var el = document.getElementById('secMsgList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=comms_fetch&ajax=1')
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No messages sent yet.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Subject</th><th>To</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(m){ h += '<tr><td>'+esc(m.subject)+'</td><td class="small">'+esc(m.recipient_type||'Staff')+'</td><td class="small">'+(m.created_at||'')+'</td><td>'+(m.is_read?'<span class="badge bg-success">Read</span>':'<span class="badge bg-warning text-dark">Sent</span>')+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed to load.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadMessages);
</script>
<?php endif; ?><?php if ($view === 'announcements'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-bullhorn me-2"></i>Publish Announcement</div><div class="scb">
<form method="POST">
<input type="hidden" name="action" value="publish_announcement">
<div class="mb-3"><label class="fl">Title *</label><input type="text" name="ann_title" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Message *</label><textarea name="ann_body" class="form-control env-field" rows="4" required></textarea></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Target</label><select name="ann_target" class="form-select env-field"><option value="All">All</option><option value="Students">Students</option><option value="Staff">Staff</option></select></div>
<div class="col-6"><label class="fl">Priority</label><select name="ann_priority" class="form-select env-field"><option value="Normal">Normal</option><option value="High">High</option><option value="Urgent">Urgent</option></select></div>
</div>
<button type="submit" class="btn btn-sec"><i class="fas fa-paper-plane me-1"></i>Publish</button>
</form>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Recent Announcements</div><div class="scb p-0">
<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Target</th><th>Priority</th><th>Date</th></tr></thead><tbody>
<?php
$annRows = '';
try { if ($students) {
    $r = $students->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 20");
    if ($r) while ($a = $r->fetch_assoc()) {
        $prCls = $a['priority']==='Urgent'?'danger':($a['priority']==='High'?'warning':'secondary');
        $annRows .= '<tr><td>'.htmlspecialchars($a['title']).'</td><td>'.htmlspecialchars($a['target_audience']??'All').'</td><td><span class="badge bg-'.$prCls.'">'.htmlspecialchars($a['priority']).'</span></td><td class="small">'.htmlspecialchars($a['created_at']).'</td></tr>';
    }
} } catch (Exception $e) {}
echo $annRows ?: '<tr><td colspan="4" class="text-center text-muted py-3">No announcements.</td></tr>';
?>
</tbody></table></div>
</div></div>
</div>
</div>
<?php endif; ?><?php if ($view === 'circulars'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-file-circle-plus me-2"></i>New Circular</div><div class="scb">
<form onsubmit="event.preventDefault(); secCreateCircular()" enctype="multipart/form-data">
<div class="mb-3"><label class="fl">Title *</label><input type="text" id="circTitle" class="form-control env-field" required></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Reference</label><input type="text" id="circRef" class="form-control env-field"></div><div class="col-6"><label class="fl">Department</label><input type="text" id="circDept" class="form-control env-field"></div></div>
<div class="mb-3"><label class="fl">Body *</label><textarea id="circBody" class="form-control env-field" rows="5" required></textarea></div>
<div class="mb-3"><label class="fl">Attachment</label><input type="file" id="circFile" class="form-control env-field"></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Issue Circular</button>
</form>
<div id="circMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Issued Circulars</div><div class="scb p-0"><div id="secCircList"></div></div></div>
</div>
</div>
<script>
function secCreateCircular(){
    var fd = new FormData(); fd.append('title', document.getElementById('circTitle').value); fd.append('reference', document.getElementById('circRef').value); fd.append('body', document.getElementById('circBody').value); fd.append('department', document.getElementById('circDept').value);
    var fi = document.getElementById('circFile'); if(fi.files[0]) fd.append('circ_file', fi.files[0]);
    fetch('school-secretary.php?view=circular_create&ajax=1', {method:'POST', body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('circMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Circular issued.</div>' : '<div class="alert alert-danger py-1 small">Failed.</div>';
        if(d.success){ document.getElementById('circTitle').value=''; document.getElementById('circRef').value=''; document.getElementById('circBody').value=''; document.getElementById('circDept').value=''; document.getElementById('circFile').value=''; secLoadCirculars(); }
    }).catch(function(){ document.getElementById('circMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function secLoadCirculars(){
    var el = document.getElementById('secCircList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=circular_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No circulars issued.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Ref</th><th>Dept</th><th>Issued By</th><th>Date</th><th>File</th></tr></thead><tbody>';
        d.forEach(function(c){ h += '<tr><td><strong>'+esc(c.title)+'</strong></td><td class="small">'+esc(c.reference||'-')+'</td><td>'+esc(c.department||'-')+'</td><td>'+esc(c.issued_by||'')+'</td><td class="small">'+(c.created_at||'')+'</td><td>'+(c.file_name?'<a href="../'+esc(c.file_path)+'" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file"></i></a>':'<span class="text-muted">--</span>')+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadCirculars);
</script>
<?php endif; ?><?php if ($view === 'notices'): ?>
<div class="row g-3">
<div class="col-md-6">
<div class="scard"><div class="sch"><i class="fas fa-bullhorn me-2"></i>Latest Announcements</div><div class="scb p-0">
<?php
$noticeRows = '';
try { if ($students) {
    $r = $students->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 10");
    if ($r) while ($a = $r->fetch_assoc()) {
        $prCls = $a['priority']==='Urgent'?'danger':($a['priority']==='High'?'warning':'info');
        $noticeRows .= '<div class="act-item"><div class="d-flex justify-content-between"><span class="fw-bold">'.htmlspecialchars($a['title']).'</span><span class="badge bg-'.$prCls.'">'.htmlspecialchars($a['priority']).'</span></div><div class="small text-muted">'.htmlspecialchars(mb_substr($a['body']??'',0,200)).'</div><div class="time">'.htmlspecialchars($a['target_audience']??'All').' &middot; '.htmlspecialchars($a['created_at']).'</div></div>';
    }
} } catch (Exception $e) {}
echo $noticeRows ?: '<div class="text-muted small p-3">No announcements.</div>';
?>
</div></div>
</div>
<div class="col-md-6">
<div class="scard"><div class="sch"><i class="fas fa-file-circle-plus me-2"></i>Latest Circulars</div><div class="scb p-0">
<?php
$circRows = '';
try { if ($staff) {
    $r = $staff->query("SELECT * FROM {$students_db}.circulars ORDER BY created_at DESC LIMIT 10");
    if ($r) while ($c = $r->fetch_assoc()) {
        $circRows .= '<div class="act-item"><div class="fw-bold">'.htmlspecialchars($c['title']).'</div><div class="small text-muted">'.htmlspecialchars(mb_substr($c['body']??'',0,200)).'</div><div class="d-flex justify-content-between mt-1"><span class="time">Ref: '.htmlspecialchars($c['reference']??'--').'</span><span class="time">'.htmlspecialchars($c['created_at']).'</span></div></div>';
    }
} } catch (Exception $e) {}
echo $circRows ?: '<div class="text-muted small p-3">No circulars.</div>';
?>
</div></div>
</div>
</div>
<?php endif; ?><?php if ($view === 'comm_logs'): ?>
<div class="scard"><div class="sch"><i class="fas fa-history me-2"></i>Communication History</div><div class="scb p-0"><div id="secCommLogs"></div></div></div>
<script>
function secLoadCommLogs(){
    var el = document.getElementById('secCommLogs'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=comms_fetch&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No communication history.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>From</th><th>Subject</th><th>Recipient</th><th>Message</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(m){ h += '<tr><td>'+esc(m.sender_name||'Secretary')+'</td><td><strong>'+esc(m.subject)+'</strong></td><td class="small">'+esc(m.recipient_type||'Staff')+'</td><td class="small">'+esc(mbSubstr(m.message||'',60))+'</td><td class="small">'+(m.created_at||'')+'</td><td>'+(m.is_read?'<span class="badge bg-success">Read</span>':'<span class="badge bg-warning text-dark">Sent</span>')+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadCommLogs);
</script>
<?php endif; ?><?php if ($view === 'incoming_mail'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-inbox me-2"></i>Log Incoming Mail</div><div class="scb">
<form onsubmit="event.preventDefault(); secCreateCorr('incoming')" enctype="multipart/form-data">
<div class="mb-3"><label class="fl">Reference</label><input type="text" id="corrRefI" class="form-control env-field"></div>
<div class="mb-3"><label class="fl">Sender Name *</label><input type="text" id="corrSenderI" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Subject *</label><input type="text" id="corrSubjI" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Body</label><textarea id="corrBodyI" class="form-control env-field" rows="3"></textarea></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Date Received</label><input type="date" id="corrDateRecI" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-6"><label class="fl">Attachment</label><input type="file" id="corrFileI" class="form-control env-field"></div>
</div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Log Incoming Mail</button>
</form>
<div id="corrMsgI" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Incoming Mail Registry</div><div class="scb p-0"><div id="secCorrListI"></div></div></div>
</div>
</div>
<script>
function secCreateCorr(typ){
    var fd = new FormData(); fd.append('type', typ);
    fd.append('reference', document.getElementById('corrRef'+typ.toUpperCase()).value);
    fd.append('sender_name', document.getElementById('corrSender'+typ.toUpperCase()).value);
    fd.append('recipient_name', ''); fd.append('subject', document.getElementById('corrSubj'+typ.toUpperCase()).value);
    fd.append('body', document.getElementById('corrBody'+typ.toUpperCase()).value);
    fd.append('date_received', document.getElementById('corrDateRec'+typ.toUpperCase()).value);
    fd.append('date_sent', document.getElementById('corrDateRec'+typ.toUpperCase()).value);
    var fi = document.getElementById('corrFile'+typ.toUpperCase()); if(fi.files[0]) fd.append('corr_file', fi.files[0]);
    fetch('school-secretary.php?view=correspondence_create&ajax=1', {method:'POST', body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('corrMsg'+typ.toUpperCase()).innerHTML = d.success ? '<div class="alert alert-success py-1 small">Mail logged.</div>' : '<div class="alert alert-danger py-1 small">Failed.</div>';
        if(d.success){ document.getElementById('corrRef'+typ.toUpperCase()).value=''; document.getElementById('corrSender'+typ.toUpperCase()).value=''; document.getElementById('corrSubj'+typ.toUpperCase()).value=''; document.getElementById('corrBody'+typ.toUpperCase()).value=''; document.getElementById('corrFile'+typ.toUpperCase()).value=''; secLoadCorr(typ); }
    }).catch(function(){ document.getElementById('corrMsg'+typ.toUpperCase()).innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function secLoadCorr(typ){
    var el = document.getElementById('secCorrList'+typ.toUpperCase()); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=correspondence_list&ajax=1&filter='+typ)
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No '+typ+' mail recorded.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Ref</th><th>Sender</th><th>Subject</th><th>Date</th><th>Status</th><th>File</th></tr></thead><tbody>';
        d.forEach(function(c){ var stCls = c.status==='actioned'?'success':c.status==='closed'?'info':c.status==='archived'?'secondary':'warning text-dark'; h += '<tr><td class="small">'+esc(c.reference||'-')+'</td><td>'+esc(c.sender_name||c.recipient_name||'')+'</td><td><strong>'+esc(c.subject)+'</strong></td><td class="small">'+esc(c.date_received||c.created_at)+'</td><td><span class="badge bg-'+stCls+'">'+esc(c.status)+'</span></td><td>'+(c.file_name?'<a href="../'+esc(c.file_path)+'" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-paperclip"></i></a>':'<span class="text-muted">--</span>')+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', function(){ secLoadCorr('incoming'); });
</script>
<?php endif; ?><?php if ($view === 'outgoing_mail'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-outdent me-2"></i>Log Outgoing Mail</div><div class="scb">
<form onsubmit="event.preventDefault(); secCreateCorr('outgoing')" enctype="multipart/form-data">
<div class="mb-3"><label class="fl">Reference</label><input type="text" id="corrRefO" class="form-control env-field"></div>
<div class="mb-3"><label class="fl">Recipient Name *</label><input type="text" id="corrSenderO" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Subject *</label><input type="text" id="corrSubjO" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Body</label><textarea id="corrBodyO" class="form-control env-field" rows="3"></textarea></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Date Sent</label><input type="date" id="corrDateRecO" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-6"><label class="fl">Attachment</label><input type="file" id="corrFileO" class="form-control env-field"></div>
</div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Log Outgoing Mail</button>
</form>
<div id="corrMsgO" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Outgoing Mail Registry</div><div class="scb p-0"><div id="secCorrListO"></div></div></div>
</div>
</div>
<script>document.addEventListener('DOMContentLoaded', function(){ secLoadCorr('outgoing'); });</script>
<?php endif; ?><?php if ($view === 'letters'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-envelope-open-text me-2"></i>Generate Letter</div><div class="scb">
<form onsubmit="event.preventDefault(); secCreateLetter()">
<div class="row g-2 mb-3">
<div class="col-6"><label class="fl">Letter Type</label><select id="ltrType" class="form-select env-field"><option>Official</option><option>Appointment</option><option>Invitation</option><option>Meeting Notice</option><option>Recommendation</option><option>Clearance</option></select></div>
<div class="col-6"><label class="fl">Reference</label><input type="text" id="ltrRef" class="form-control env-field"></div>
</div>
<div class="mb-3"><label class="fl">Title *</label><input type="text" id="ltrTitle" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Recipient Name *</label><input type="text" id="ltrRecipient" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Recipient Address</label><textarea id="ltrAddress" class="form-control env-field" rows="2"></textarea></div>
<div class="mb-3"><label class="fl">Body *</label><textarea id="ltrBody" class="form-control env-field" rows="6" required></textarea></div>
<div class="mb-3"><label class="fl">Letter Date</label><input type="date" id="ltrDate" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Generate</button>
</form>
<div id="ltrMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Generated Letters</div><div class="scb p-0"><div id="secLtrList"></div></div></div>
</div>
</div>
<script>
function secCreateLetter(){
    var fd = new FormData(); fd.append('letter_type', document.getElementById('ltrType').value); fd.append('reference', document.getElementById('ltrRef').value); fd.append('title', document.getElementById('ltrTitle').value); fd.append('recipient_name', document.getElementById('ltrRecipient').value); fd.append('recipient_address', document.getElementById('ltrAddress').value); fd.append('body', document.getElementById('ltrBody').value); fd.append('letter_date', document.getElementById('ltrDate').value);
    fetch('school-secretary.php?view=letter_create&ajax=1', {method:'POST', body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('ltrMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Letter generated.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('ltrRef').value=''; document.getElementById('ltrTitle').value=''; document.getElementById('ltrRecipient').value=''; document.getElementById('ltrAddress').value=''; document.getElementById('ltrBody').value=''; secLoadLetters(); }
    }).catch(function(){ document.getElementById('ltrMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function secLoadLetters(){
    var el = document.getElementById('secLtrList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=letter_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No letters generated.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Type</th><th>Title</th><th>Recipient</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>';
        d.forEach(function(l){ var stCls = l.status==='issued'?'info':l.status==='signed'?'success':l.status==='archived'?'secondary':'warning text-dark'; h += '<tr><td><span class="badge bg-secondary">'+esc(l.letter_type)+'</span></td><td><strong>'+esc(l.title)+'</strong></td><td>'+esc(l.recipient_name)+'</td><td class="small">'+esc(l.letter_date||l.created_at)+'</td><td><span class="badge bg-'+stCls+'">'+esc(l.status)+'</span></td><td><button class="btn btn-sm btn-outline-primary" onclick="secPreviewLetter('+l.id+')"><i class="fas fa-eye"></i></button></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
function secPreviewLetter(id){
    var w = window.open('','_blank','width=800,height=600');
    fetch('school-secretary.php?view=letter_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        var l = d.find(function(x){ return x.id == id; }); if(!l){ w.document.write('Not found'); w.document.close(); return; }
        w.document.write('<html><head><title>'+esc(l.title)+'</title><style>body{font-family:serif;padding:40px;line-height:1.6}.letter-header{text-align:center;margin-bottom:30px}.letter-date{text-align:right}.letter-body{margin:20px 0}.letter-footer{margin-top:40px;border-top:1px solid #ccc;padding-top:20px}@media print{body{padding:20px}}</style></head><body>');
        w.document.write('<div class="letter-header"><h2>'+esc(l.title)+'</h2><p>Ref: '+(l.reference||'--')+'</p></div>');
        w.document.write('<div class="letter-date"><p>Date: '+esc(l.letter_date)+'</p></div>');
        w.document.write('<p><strong>To: '+esc(l.recipient_name)+'</strong></p>'); if(l.recipient_address) w.document.write('<p>'+esc(l.recipient_address).replace(/\n/g,'<br>')+'</p>');
        w.document.write('<div class="letter-body"><p>'+esc(l.body).replace(/\n/g,'<br>')+'</p></div>');
        w.document.write('<div class="letter-footer"><p>Yours faithfully,</p><br><br><p><strong>'+esc(l.issued_by||'School Secretary')+'</strong></p></div>');
        w.document.write('</body></html>'); w.document.close();
    });
}
function secPrintLetter(id){
    secPreviewLetter(id); setTimeout(function(){ window.open('','_blank').print(); }, 500);
}
document.addEventListener('DOMContentLoaded', secLoadLetters);
</script>
<?php endif; ?><?php if ($view === 'document_tracking'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-lines me-2"></i>Document Tracking - Combined View</div><div class="scb p-0"><div id="secDocTrack"></div></div></div>
<script>
function secLoadDocTrack(){
    var el = document.getElementById('secDocTrack'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=doc_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(docs){
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Category</th><th>Reference</th><th>Type</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        if(docs) docs.forEach(function(d){ var stCls = d.status==='filed'?'success':d.status==='archived'?'secondary':'warning text-dark'; h += '<tr><td><strong>'+esc(d.doc_title)+'</strong></td><td>'+esc(d.category||'-')+'</td><td class="small">'+esc(d.reference_number||'-')+'</td><td><span class="badge bg-info">'+(d.doc_type||'document')+'</span></td><td><span class="badge bg-'+stCls+'">'+esc(d.status)+'</span></td><td class="small">'+(d.created_at||'')+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h || '<div class="text-muted small p-3">No documents.</div>';
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadDocTrack);
</script>
<?php endif; ?><?php if ($view === 'correspondence_archive'): ?>
<div class="scard"><div class="sch"><i class="fas fa-archive me-2"></i>Correspondence Archive</div><div class="scb p-0"><div id="secCorrArchive"></div></div></div>
<script>
function secLoadCorrArchive(){
    var el = document.getElementById('secCorrArchive'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=correspondence_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        var archived = (d||[]).filter(function(c){ return c.status === 'archived'; });
        if(!archived.length){ el.innerHTML = '<div class="text-muted small p-3">No archived correspondence.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Type</th><th>Ref</th><th>Sender</th><th>Subject</th><th>Date</th></tr></thead><tbody>';
        archived.forEach(function(c){ h += '<tr><td><span class="badge bg-'+(c.type==='incoming'?'primary':'secondary')+'">'+esc(c.type)+'</span></td><td class="small">'+esc(c.reference||'-')+'</td><td>'+esc(c.sender_name||c.recipient_name||'')+'</td><td>'+esc(c.subject)+'</td><td class="small">'+esc(c.created_at)+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadCorrArchive);
</script>
<?php endif; ?><?php if ($view === 'meetings'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Schedule Meeting</div><div class="scb">
<form onsubmit="event.preventDefault(); secCreateMeeting()">
<div class="mb-3"><label class="fl">Title *</label><input type="text" id="mtTitle" class="form-control env-field" required></div>
<div class="row g-2 mb-3">
<div class="col-4"><label class="fl">Type</label><select id="mtType" class="form-select env-field"><option>General</option><option>Executive</option><option>Department</option><option>Staff</option><option>Committee</option></select></div>
<div class="col-4"><label class="fl">Date *</label><input type="date" id="mtDate" class="form-control env-field" required></div>
<div class="col-4"><label class="fl">Location</label><input type="text" id="mtLoc" class="form-control env-field"></div>
</div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Start</label><input type="time" id="mtStart" class="form-control env-field"></div><div class="col-6"><label class="fl">End</label><input type="time" id="mtEnd" class="form-control env-field"></div></div>
<div class="mb-3"><label class="fl">Agenda</label><textarea id="mtAgenda" class="form-control env-field" rows="3"></textarea></div>
<div class="mb-3"><label class="fl">Attendees (one per line)</label><textarea id="mtAttendees" class="form-control env-field" rows="2" placeholder="Name1&#10;Name2"></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Schedule Meeting</button>
</form>
<div id="mtMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Meetings</div><div class="scb p-0"><div id="secMtList"></div></div></div>
</div>
</div>
<script>
function secCreateMeeting(){
    var fd = new FormData(); fd.append('title', document.getElementById('mtTitle').value); fd.append('meeting_type', document.getElementById('mtType').value); fd.append('meeting_date', document.getElementById('mtDate').value); fd.append('start_time', document.getElementById('mtStart').value); fd.append('end_time', document.getElementById('mtEnd').value); fd.append('location', document.getElementById('mtLoc').value); fd.append('agenda', document.getElementById('mtAgenda').value); fd.append('attendees', document.getElementById('mtAttendees').value);
    fetch('school-secretary.php?view=meeting_create&ajax=1', {method:'POST', body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('mtMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Meeting scheduled.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('mtTitle').value=''; document.getElementById('mtDate').value=''; document.getElementById('mtStart').value=''; document.getElementById('mtEnd').value=''; document.getElementById('mtLoc').value=''; document.getElementById('mtAgenda').value=''; document.getElementById('mtAttendees').value=''; secLoadMeetings(); }
    }).catch(function(){ document.getElementById('mtMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function secLoadMeetings(){
    var el = document.getElementById('secMtList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=meeting_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No meetings.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Location</th><th>Status</th><th></th></tr></thead><tbody>';
        d.forEach(function(m){ var stCls = m.status==='completed'?'success':m.status==='ongoing'?'info':m.status==='cancelled'?'danger':'warning text-dark'; h += '<tr><td><strong>'+esc(m.title)+'</strong><br><small class="text-muted">'+esc(m.meeting_type||'')+'</small></td><td class="small">'+esc(m.meeting_date)+'</td><td class="small">'+esc(m.start_time||'--')+'</td><td class="small">'+esc(m.location||'--')+'</td><td><span class="badge bg-'+stCls+'">'+esc(m.status)+'</span></td><td><div class="dropdown"><button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#" onclick="secMinutesModal('+m.id+')"><i class="fas fa-pen me-2"></i>Minutes</a></li><li><a class="dropdown-item" href="#" onclick="secViewMeeting('+m.id+')"><i class="fas fa-info-circle me-2"></i>Details</a></li></ul></div></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
function secMinutesModal(mid){ var txt = prompt('Enter meeting minutes:'); if(txt === null) return; var fd = new FormData(); fd.append('meeting_id', mid); fd.append('minutes', txt); fetch('school-secretary.php?view=meeting_save_minutes&ajax=1', {method:'POST', body:fd}).then(function(r){ return r.json(); }).then(function(d){ if(d.success) secLoadMeetings(); }).catch(function(){}); }
function secViewMeeting(mid){
    fetch('school-secretary.php?view=meeting_get&ajax=1&id='+mid)
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.meeting){ alert('Meeting not found'); return; } var m = d.meeting;
        var info = 'Title: '+m.title+'\nDate: '+m.meeting_date+'\nTime: '+(m.start_time||'--')+' - '+(m.end_time||'--')+'\nLocation: '+(m.location||'--')+'\nType: '+(m.meeting_type||'')+'\nStatus: '+m.status+'\n\nAgenda:\n'+(m.agenda||'N/A')+'\n\nMinutes:\n'+(m.minutes||'Not yet recorded');
        var at = d.attendees || []; if(at.length){ info += '\n\nAttendees:\n'; at.forEach(function(a){ info += '- '+a.attendee_name+' ('+a.attended+')\n'; }); }
        alert(info);
    }).catch(function(){ alert('Failed to load meeting details.'); });
}
document.addEventListener('DOMContentLoaded', secLoadMeetings);
</script>
<?php endif; ?><?php if ($view === 'exec_meetings'): ?>
<div class="scard"><div class="sch"><i class="fas fa-briefcase me-2"></i>Executive Meetings</div><div class="scb p-0"><div id="secExecMtList"></div></div></div>
<script>
function secLoadExecMeetings(){
    var el = document.getElementById('secExecMtList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=meeting_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        var filtered = (d||[]).filter(function(m){ return m.meeting_type === 'Executive'; });
        if(!filtered.length){ el.innerHTML = '<div class="text-muted small p-3">No executive meetings.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead><tbody>';
        filtered.forEach(function(m){ var stCls = m.status==='completed'?'success':m.status==='cancelled'?'danger':'warning text-dark'; h += '<tr><td><strong>'+esc(m.title)+'</strong></td><td class="small">'+esc(m.meeting_date)+'</td><td class="small">'+esc(m.start_time||'--')+'</td><td><span class="badge bg-'+stCls+'">'+esc(m.status)+'</span></td><td><button class="btn btn-sm btn-outline-primary" onclick="secViewMeeting('+m.id+')"><i class="fas fa-eye"></i></button></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadExecMeetings);
</script>
<?php endif; ?><?php if ($view === 'dept_meetings'): ?>
<div class="scard"><div class="sch"><i class="fas fa-building me-2"></i>Department Meetings</div><div class="scb p-0"><div id="secDeptMtList"></div></div></div>
<script>
function secLoadDeptMeetings(){
    var el = document.getElementById('secDeptMtList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=meeting_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        var filtered = (d||[]).filter(function(m){ return m.meeting_type === 'Department'; });
        if(!filtered.length){ el.innerHTML = '<div class="text-muted small p-3">No department meetings.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead><tbody>';
        filtered.forEach(function(m){ var stCls = m.status==='completed'?'success':m.status==='cancelled'?'danger':'warning text-dark'; h += '<tr><td><strong>'+esc(m.title)+'</strong></td><td class="small">'+esc(m.meeting_date)+'</td><td class="small">'+esc(m.start_time||'--')+'</td><td><span class="badge bg-'+stCls+'">'+esc(m.status)+'</span></td><td><button class="btn btn-sm btn-outline-primary" onclick="secViewMeeting('+m.id+')"><i class="fas fa-eye"></i></button></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadDeptMeetings);
</script>
<?php endif; ?><?php if ($view === 'meeting_requests'): ?>
<div class="scard"><div class="sch"><i class="fas fa-handshake me-2"></i>Meeting Requests</div><div class="scb">
<div class="text-muted small">Meeting request tracking is managed via the Requests section. <a href="?section=requests" class="text-decoration-none">Go to Requests</a></div>
</div></div>
<?php endif; ?><?php if ($view === 'appointments'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-calendar-plus me-2"></i>New Appointment</div><div class="scb">
<form onsubmit="event.preventDefault(); secBookAppointment()">
<div class="mb-3"><label class="fl">Visitor Name *</label><input type="text" id="appVName" class="form-control env-field" required></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Phone</label><input type="text" id="appVPhone" class="form-control env-field"></div><div class="col-6"><label class="fl">Email</label><input type="email" id="appVEmail" class="form-control env-field"></div></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Date *</label><input type="date" id="appDate" class="form-control env-field" required></div><div class="col-6"><label class="fl">Time</label><input type="time" id="appTime" class="form-control env-field"></div></div>
<div class="mb-3"><label class="fl">Staff Member</label><select id="appStaffId" class="form-select env-field"><option value="0">-- None --</option>
<?php try { if ($staff) { $r = $staff->query("SELECT id, full_name FROM staff WHERE status='Active' ORDER BY full_name"); if ($r) while ($s = $r->fetch_assoc()) echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['full_name']).'</option>'; } } catch (Exception $e) {} ?>
</select></div>
<div class="mb-3"><label class="fl">Purpose</label><textarea id="appPurpose" class="form-control env-field" rows="2"></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Book Appointment</button>
</form>
<div id="appMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Appointments</div><div class="scb p-0"><div id="secAppList"></div></div></div>
</div>
</div>
<script>
function secBookAppointment(){
    var fd = new FormData(); fd.append('visitor_name', document.getElementById('appVName').value); fd.append('visitor_phone', document.getElementById('appVPhone').value); fd.append('visitor_email', document.getElementById('appVEmail').value); fd.append('staff_id', document.getElementById('appStaffId').value); fd.append('appointment_date', document.getElementById('appDate').value); fd.append('appointment_time', document.getElementById('appTime').value); fd.append('purpose', document.getElementById('appPurpose').value);
    fetch('school-secretary.php?view=appointment_book&ajax=1', {method:'POST', body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('appMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Appointment booked.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('appVName').value=''; document.getElementById('appVPhone').value=''; document.getElementById('appVEmail').value=''; document.getElementById('appDate').value=''; document.getElementById('appTime').value=''; document.getElementById('appPurpose').value=''; secLoadAppointments(); }
    }).catch(function(){ document.getElementById('appMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function secLoadAppointments(){
    var el = document.getElementById('secAppList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=appointment_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No appointments.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Visitor</th><th>Date</th><th>Time</th><th>Staff</th><th>Status</th><th></th></tr></thead><tbody>';
        d.forEach(function(a){ var stCls = a.status==='approved'?'success':a.status==='completed'?'info':a.status==='cancelled'?'danger':'warning text-dark'; h += '<tr><td><strong>'+esc(a.visitor_name)+'</strong><br><small>'+esc(a.visitor_phone||'')+'</small></td><td class="small">'+esc(a.appointment_date)+'</td><td class="small">'+esc(a.appointment_time||'--')+'</td><td class="small">'+esc(a.staff_name||'--')+'</td><td><span class="badge bg-'+stCls+'">'+esc(a.status)+'</span></td><td><select class="form-select form-select-sm" style="width:auto" onchange="secUpdateAppt('+a.id+',this.value)"><option value="">Change</option><option value="approved">Approve</option><option value="completed">Complete</option><option value="cancelled">Cancel</option></select></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
function secUpdateAppt(id, st){ if(!st) return; var fd = new FormData(); fd.append('id', id); fd.append('status', st); fetch('school-secretary.php?view=appointment_update&ajax=1', {method:'POST', body:fd}).then(function(r){ return r.json(); }).then(function(d){ if(d.success) secLoadAppointments(); }).catch(function(){}); }
document.addEventListener('DOMContentLoaded', secLoadAppointments);
</script>
<?php endif; ?><?php if ($view === 'visitor_mgmt'): ?>
<div class="scard"><div class="sch"><i class="fas fa-user-clock me-2"></i>Visitor Management</div><div class="scb p-0"><div id="secVisitorList"></div></div></div>
<script>
function secLoadVisitors(){
    var el = document.getElementById('secVisitorList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=appointment_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No visitors.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Visitor</th><th>Phone</th><th>Staff</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th></tr></thead><tbody>';
        d.forEach(function(a){ h += '<tr><td><strong>'+esc(a.visitor_name)+'</strong></td><td>'+esc(a.visitor_phone||'-')+'</td><td class="small">'+esc(a.staff_name||'--')+'</td><td class="small">'+esc(a.appointment_date)+'</td><td class="small">'+esc(a.appointment_time||'--')+'</td><td class="small">'+esc(mbSubstr(a.purpose||'',40))+'</td><td><span class="badge bg-'+(a.status==='approved'?'success':a.status==='completed'?'info':a.status==='cancelled'?'danger':'warning text-dark')+'">'+esc(a.status)+'</span></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadVisitors);
</script>
<?php endif; ?><?php if ($view === 'exec_appointments'): ?>
<div class="scard"><div class="sch"><i class="fas fa-star me-2"></i>Executive Appointments</div><div class="scb p-0"><div id="secExecAppList"></div></div></div>
<script>
function secLoadExecAppointments(){
    var el = document.getElementById('secExecAppList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=appointment_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        var filtered = (d||[]).filter(function(a){ return a.staff_id > 0; });
        if(!filtered.length){ el.innerHTML = '<div class="text-muted small p-3">No executive appointments.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Visitor</th><th>Staff</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>';
        filtered.forEach(function(a){ h += '<tr><td><strong>'+esc(a.visitor_name)+'</strong></td><td>'+esc(a.staff_name||'--')+'</td><td class="small">'+esc(a.appointment_date)+'</td><td class="small">'+esc(a.appointment_time||'--')+'</td><td><span class="badge bg-'+(a.status==='approved'?'success':a.status==='completed'?'info':a.status==='cancelled'?'danger':'warning text-dark')+'">'+esc(a.status)+'</span></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadExecAppointments);
</script>
<?php endif; ?><?php if ($view === 'calendar'): ?>
<div class="row g-3">
<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-handshake me-2"></i>Upcoming Meetings</div><div class="scb p-0"><div id="secCalMeetings"></div></div></div></div>
<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-calendar-check me-2"></i>Upcoming Appointments</div><div class="scb p-0"><div id="secCalAppointments"></div></div></div></div>
</div>
<script>
function secLoadCalendar(){
    var el1 = document.getElementById('secCalMeetings'); if(el1){
        el1.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
        fetch('school-secretary.php?view=meeting_list&ajax=1').then(function(r){ return r.json(); }).then(function(d){
            var upcoming = (d||[]).filter(function(m){ return m.status === 'scheduled' || m.status === 'ongoing'; });
            if(!upcoming.length){ el1.innerHTML = '<div class="text-muted small p-3">No upcoming meetings.</div>'; return; }
            var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Location</th></tr></thead><tbody>';
            upcoming.forEach(function(m){ h += '<tr><td><strong>'+esc(m.title)+'</strong></td><td class="small">'+esc(m.meeting_date)+'</td><td class="small">'+esc(m.start_time||'--')+'</td><td class="small">'+esc(m.location||'--')+'</td></tr>'; });
            h += '</tbody></table></div>'; el1.innerHTML = h;
        }).catch(function(){ el1.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
    }
    var el2 = document.getElementById('secCalAppointments'); if(el2){
        el2.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
        fetch('school-secretary.php?view=appointment_list&ajax=1').then(function(r){ return r.json(); }).then(function(d){
            var upcoming = (d||[]).filter(function(a){ return a.status === 'pending' || a.status === 'approved'; });
            if(!upcoming.length){ el2.innerHTML = '<div class="text-muted small p-3">No upcoming appointments.</div>'; return; }
            var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Visitor</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>';
            upcoming.forEach(function(a){ h += '<tr><td><strong>'+esc(a.visitor_name)+'</strong></td><td class="small">'+esc(a.appointment_date)+'</td><td class="small">'+esc(a.appointment_time||'--')+'</td><td><span class="badge bg-'+(a.status==='approved'?'success':'warning text-dark')+'">'+esc(a.status)+'</span></td></tr>'; });
            h += '</tbody></table></div>'; el2.innerHTML = h;
        }).catch(function(){ el2.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
    }
}
document.addEventListener('DOMContentLoaded', secLoadCalendar);
</script>
<?php endif; ?><?php if ($view === 'documents'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-upload me-2"></i>Upload Document</div><div class="scb">
<form onsubmit="event.preventDefault(); secUploadDoc()" enctype="multipart/form-data">
<div class="mb-3"><label class="fl">Document Title *</label><input type="text" id="docTitle" class="form-control env-field" required></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Category</label><select id="docCat" class="form-select env-field"><option>Administrative</option><option>Student</option><option>Staff</option><option>Board</option><option>Policy</option></select></div><div class="col-6"><label class="fl">Document Type</label><select id="docType" class="form-select env-field"><option>document</option><option>report</option><option>memo</option><option>policy</option><option>template</option></select></div></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Reference No.</label><input type="text" id="docRef" class="form-control env-field"></div><div class="col-6"><label class="fl">File *</label><input type="file" id="docFile" class="form-control env-field"></div></div>
<div class="mb-3"><label class="fl">Description</label><textarea id="docDesc" class="form-control env-field" rows="2"></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-upload me-1"></i>Upload</button>
</form>
<div id="docMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-folder-open me-2"></i>Document Registry</div><div class="scb p-0"><div id="secDocList"></div></div></div>
</div>
</div>
<script>
function secUploadDoc(){
    var fd = new FormData(); fd.append('doc_title', document.getElementById('docTitle').value); fd.append('category', document.getElementById('docCat').value); fd.append('doc_type', document.getElementById('docType').value); fd.append('reference_number', document.getElementById('docRef').value); fd.append('description', document.getElementById('docDesc').value);
    var fi = document.getElementById('docFile'); if(fi.files[0]) fd.append('doc_file', fi.files[0]);
    fetch('school-secretary.php?view=doc_upload&ajax=1', {method:'POST', body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('docMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Document uploaded.</div>' : '<div class="alert alert-danger py-1 small">Failed.</div>';
        if(d.success){ document.getElementById('docTitle').value=''; document.getElementById('docRef').value=''; document.getElementById('docDesc').value=''; document.getElementById('docFile').value=''; secLoadDocs(); }
    }).catch(function(){ document.getElementById('docMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function secLoadDocs(){
    var el = document.getElementById('secDocList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=doc_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No documents.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Category</th><th>Reference</th><th>Type</th><th>File</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(dc){ h += '<tr><td><strong>'+esc(dc.doc_title)+'</strong></td><td><span class="badge bg-secondary">'+esc(dc.category)+'</span></td><td class="small">'+esc(dc.reference_number||'-')+'</td><td class="small">'+esc(dc.doc_type||'document')+'</td><td>'+(dc.file_name?'<a href="../'+esc(dc.file_path)+'" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fas fa-download"></i></a>':'<span class="text-muted">--</span>')+'</td><td class="small">'+esc(dc.created_at)+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadDocs);
</script>
<?php endif; ?><?php if ($view === 'doc_filing'): ?>
<div class="scard"><div class="sch"><i class="fas fa-folder-tree me-2"></i>Document Status Management</div><div class="scb p-0"><div id="secDocFiling"></div></div></div>
<script>
function secLoadDocFiling(){
    var el = document.getElementById('secDocFiling'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=doc_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No documents for filing.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Category</th><th>Reference</th><th>Status</th><th></th></tr></thead><tbody>';
        d.forEach(function(dc){ var stCls = dc.status==='filed'?'success':dc.status==='archived'?'secondary':'warning text-dark'; h += '<tr><td><strong>'+esc(dc.doc_title)+'</strong></td><td>'+esc(dc.category||'-')+'</td><td class="small">'+esc(dc.reference_number||'-')+'</td><td><span class="badge bg-'+stCls+'">'+esc(dc.status)+'</span></td><td><select class="form-select form-select-sm" style="width:auto" onchange="secUpdateDocStatus('+dc.id+',this.value)"><option value="">Change</option><option value="draft">Draft</option><option value="filed">File</option><option value="archived">Archive</option></select></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
function secUpdateDocStatus(id, st){ if(!st) return; var fd = new FormData(); fd.append('id', id); fd.append('status', st); fetch('school-secretary.php?view=doc_update&ajax=1', {method:'POST', body:fd}).then(function(r){ return r.json(); }).then(function(d){ if(d.success) secLoadDocFiling(); }).catch(function(){}); }
document.addEventListener('DOMContentLoaded', secLoadDocFiling);
</script>
<?php endif; ?><?php if ($view === 'scanned_docs'): ?>
<div class="scard"><div class="sch"><i class="fas fa-scan me-2"></i>Scanned Documents</div><div class="scb p-0"><div id="secScannedDocs"></div></div></div>
<script>
function secLoadScannedDocs(){
    var el = document.getElementById('secScannedDocs'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=doc_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        var wf = (d||[]).filter(function(dc){ return dc.file_name && dc.file_name !== ''; });
        if(!wf.length){ el.innerHTML = '<div class="text-muted small p-3">No scanned documents available.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Category</th><th>File</th><th>Date</th></tr></thead><tbody>';
        wf.forEach(function(dc){ h += '<tr><td><strong>'+esc(dc.doc_title)+'</strong></td><td>'+esc(dc.category||'-')+'</td><td><a href="../'+esc(dc.file_path)+'" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file"></i> '+esc(dc.file_name)+'</a></td><td class="small">'+esc(dc.created_at)+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadScannedDocs);
</script>
<?php endif; ?><?php if ($view === 'templates'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-code me-2"></i>Document Templates</div><div class="scb">
<div class="text-muted small mb-3">Access and manage standard document templates for official correspondence. Click a template to open the letter generator.</div>
<div class="row g-3">
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-file-word fa-3x text-primary mb-2"></i><h6>Official Letter</h6><p class="small text-muted">Standard official letter template</p><a href="?section=letters" class="btn btn-sm bo">Use Template</a></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-file-pen fa-3x text-success mb-2"></i><h6>Appointment Letter</h6><p class="small text-muted">Staff appointment letter template</p><a href="?section=letters" class="btn btn-sm bo">Use Template</a></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-file-lines fa-3x text-info mb-2"></i><h6>Memo</h6><p class="small text-muted">Internal memorandum template</p><a href="?section=letters" class="btn btn-sm bo">Use Template</a></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-envelope-open-text fa-3x text-warning mb-2"></i><h6>Invitation Letter</h6><p class="small text-muted">Event invitation template</p><a href="?section=letters" class="btn btn-sm bo">Use Template</a></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-certificate fa-3x text-secondary mb-2"></i><h6>Recommendation</h6><p class="small text-muted">Recommendation letter template</p><a href="?section=letters" class="btn btn-sm bo">Use Template</a></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><i class="fas fa-check-circle fa-3x text-danger mb-2"></i><h6>Clearance Letter</h6><p class="small text-muted">Student/staff clearance template</p><a href="?section=letters" class="btn btn-sm bo">Use Template</a></div></div>
</div>
</div></div>
<?php endif; ?><?php if ($view === 'archives'): ?>
<div class="scard"><div class="sch"><i class="fas fa-box-archive me-2"></i>Archived Documents</div><div class="scb p-0"><div id="secArchivedDocs"></div></div></div>
<script>
function secLoadArchivedDocs(){
    var el = document.getElementById('secArchivedDocs'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=doc_list&ajax=1&status=archived')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No archived documents.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Category</th><th>Reference</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(dc){ h += '<tr><td><strong>'+esc(dc.doc_title)+'</strong></td><td>'+esc(dc.category||'-')+'</td><td class="small">'+esc(dc.reference_number||'-')+'</td><td class="small">'+esc(dc.created_at)+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadArchivedDocs);
</script>
<?php endif; ?><?php if ($view === 'requests'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>New Request</div><div class="scb">
<form onsubmit="event.preventDefault(); secCreateRequest()">
<div class="mb-3"><label class="fl">Request Title *</label><input type="text" id="rqTitle" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Description</label><textarea id="rqDesc" class="form-control env-field" rows="3"></textarea></div>
<div class="row g-2 mb-3">
<div class="col-4"><label class="fl">Type</label><select id="rqType" class="form-select env-field"><option>general</option><option>maintenance</option><option>supplies</option><option>transport</option><option>document</option></select></div>
<div class="col-4"><label class="fl">Assigned To</label><input type="text" id="rqAssigned" class="form-control env-field" placeholder="Person/Dept"></div>
<div class="col-4"><label class="fl">Priority</label><select id="rqPriority" class="form-select env-field"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option><option value="low">Low</option></select></div>
</div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Create Request</button>
</form>
<div id="rqMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-clipboard-list me-2"></i>Request Tracking</div><div class="scb p-0"><div id="secRqList"></div></div></div>
</div>
</div>
<script>
function secCreateRequest(){
    var fd = new FormData(); fd.append('request_title', document.getElementById('rqTitle').value); fd.append('request_type', document.getElementById('rqType').value); fd.append('description', document.getElementById('rqDesc').value); fd.append('assigned_to', document.getElementById('rqAssigned').value); fd.append('priority', document.getElementById('rqPriority').value);
    fetch('school-secretary.php?view=request_create&ajax=1', {method:'POST', body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('rqMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Request created.</div>' : '<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
        if(d.success){ document.getElementById('rqTitle').value=''; document.getElementById('rqDesc').value=''; document.getElementById('rqAssigned').value=''; secLoadRequests(); }
    }).catch(function(){ document.getElementById('rqMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function secLoadRequests(){
    var el = document.getElementById('secRqList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=request_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No requests.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Type</th><th>Assigned To</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(rq){ var prCls = rq.priority==='urgent'?'danger':rq.priority==='high'?'warning':'secondary'; var stCls = rq.status==='approved'?'success':rq.status==='completed'?'info':rq.status==='rejected'?'danger':'warning text-dark'; h += '<tr><td><strong>'+esc(rq.request_title)+'</strong></td><td class="small">'+esc(rq.request_type||'-')+'</td><td class="small">'+esc(rq.assigned_to||'--')+'</td><td><span class="badge bg-'+prCls+'">'+esc(rq.priority)+'</span></td><td><span class="badge bg-'+stCls+'">'+esc(rq.status)+'</span></td><td class="small">'+esc(rq.created_at)+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadRequests);
</script>
<?php endif; ?><?php if ($view === 'assigned_requests'): ?>
<div class="scard"><div class="sch"><i class="fas fa-user-check me-2"></i>Assigned Requests</div><div class="scb p-0"><div id="secAssignedRqList"></div></div></div>
<script>
function secLoadAssignedRequests(){
    var el = document.getElementById('secAssignedRqList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=request_list&ajax=1&filter=assigned')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No assigned requests.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Type</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        d.forEach(function(rq){ var prCls = rq.priority==='urgent'?'danger':rq.priority==='high'?'warning':'secondary'; var stCls = rq.status==='approved'?'success':rq.status==='completed'?'info':rq.status==='rejected'?'danger':'warning text-dark'; h += '<tr><td><strong>'+esc(rq.request_title)+'</strong></td><td class="small">'+esc(rq.request_type||'-')+'</td><td><span class="badge bg-'+prCls+'">'+esc(rq.priority)+'</span></td><td><span class="badge bg-'+stCls+'">'+esc(rq.status)+'</span></td><td class="small">'+esc(rq.created_at)+'</td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadAssignedRequests);
</script>
<?php endif; ?><?php if ($view === 'follow_ups'): ?>
<div class="scard"><div class="sch"><i class="fas fa-clipboard-check me-2"></i>Follow-up Tracking</div><div class="scb p-0"><div id="secFollowUps"></div></div></div>
<script>
function secLoadFollowUps(){
    var el = document.getElementById('secFollowUps'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=request_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        var pending = (d||[]).filter(function(rq){ return rq.status === 'pending' || rq.status === 'approved'; });
        if(!pending.length){ el.innerHTML = '<div class="text-muted small p-3">No items requiring follow-up.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Request</th><th>Assigned To</th><th>Priority</th><th>Status</th><th>Date</th><th>Follow-up</th></tr></thead><tbody>';
        pending.forEach(function(rq){ var prCls = rq.priority==='urgent'?'danger':rq.priority==='high'?'warning':'secondary'; var stCls = rq.status==='approved'?'success':'warning text-dark'; h += '<tr><td><strong>'+esc(rq.request_title)+'</strong></td><td class="small">'+esc(rq.assigned_to||'--')+'</td><td><span class="badge bg-'+prCls+'">'+esc(rq.priority)+'</span></td><td><span class="badge bg-'+stCls+'">'+esc(rq.status)+'</span></td><td class="small">'+esc(rq.created_at)+'</td><td><button class="btn btn-sm btn-outline-warning" onclick="alert(\'Follow up on: '+esc(rq.request_title)+'\')"><i class="fas fa-bell"></i></button></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadFollowUps);
</script>
<?php endif; ?><?php if ($view === 'request_tracking'): ?>
<div class="scard"><div class="sch"><i class="fas fa-list-check me-2"></i>All Request Tracking</div><div class="scb p-0"><div id="secAllRqTrack"></div></div></div>
<script>
function secLoadAllRqTrack(){
    var el = document.getElementById('secAllRqTrack'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=request_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No requests found.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Type</th><th>Assigned To</th><th>Priority</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>';
        d.forEach(function(rq){ var prCls = rq.priority==='urgent'?'danger':rq.priority==='high'?'warning':'secondary'; var stCls = rq.status==='approved'?'success':rq.status==='completed'?'info':rq.status==='rejected'?'danger':'warning text-dark'; h += '<tr><td><strong>'+esc(rq.request_title)+'</strong></td><td class="small">'+esc(rq.request_type||'-')+'</td><td class="small">'+esc(rq.assigned_to||'--')+'</td><td><span class="badge bg-'+prCls+'">'+esc(rq.priority)+'</span></td><td><span class="badge bg-'+stCls+'">'+esc(rq.status)+'</span></td><td class="small">'+esc(rq.created_at)+'</td><td><select class="form-select form-select-sm" style="width:auto" onchange="secUpdateRequest('+rq.id+',this.value)"><option value="">Action</option><option value="approved">Approve</option><option value="rejected">Reject</option><option value="completed">Complete</option></select></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
function secUpdateRequest(id, st){ if(!st) return; var fd = new FormData(); fd.append('id', id); fd.append('status', st); fetch('school-secretary.php?view=request_update&ajax=1', {method:'POST', body:fd}).then(function(r){ return r.json(); }).then(function(d){ if(d.success) secLoadAllRqTrack(); }).catch(function(){}); }
document.addEventListener('DOMContentLoaded', secLoadAllRqTrack);
</script>
<?php endif; ?><?php if ($view === 'contact_directory'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-address-card me-2"></i>Add Contact</div><div class="scb">
<form onsubmit="event.preventDefault(); secCreateContact()">
<div class="mb-3"><label class="fl">Full Name *</label><input type="text" id="cntName" class="form-control env-field" required></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Organization</label><input type="text" id="cntOrg" class="form-control env-field"></div><div class="col-6"><label class="fl">Position</label><input type="text" id="cntPos" class="form-control env-field"></div></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Phone</label><input type="text" id="cntPhone" class="form-control env-field"></div><div class="col-6"><label class="fl">Email</label><input type="email" id="cntEmail" class="form-control env-field"></div></div>
<div class="mb-3"><label class="fl">Address</label><textarea id="cntAddr" class="form-control env-field" rows="2"></textarea></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Category</label><select id="cntCat" class="form-select env-field"><option>General</option><option>Government</option><option>Education</option><option>Health</option><option>Media</option><option>Supplier</option></select></div><div class="col-6"><label class="fl">Notes</label><input type="text" id="cntNotes" class="form-control env-field"></div></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Add Contact</button>
</form>
<div id="cntMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-address-book me-2"></i>Contact Directory</div><div class="scb p-0"><div id="secContactList"></div></div></div>
</div>
</div>
<script>
function secCreateContact(){
    var fd = new FormData(); fd.append('full_name', document.getElementById('cntName').value); fd.append('organization', document.getElementById('cntOrg').value); fd.append('position', document.getElementById('cntPos').value); fd.append('phone', document.getElementById('cntPhone').value); fd.append('email', document.getElementById('cntEmail').value); fd.append('address', document.getElementById('cntAddr').value); fd.append('category', document.getElementById('cntCat').value); fd.append('notes', document.getElementById('cntNotes').value);
    fetch('school-secretary.php?view=contact_create&ajax=1', {method:'POST', body:fd})
    .then(function(r){ return r.json(); }).then(function(d){
        document.getElementById('cntMsg').innerHTML = d.success ? '<div class="alert alert-success py-1 small">Contact added.</div>' : '<div class="alert alert-danger py-1 small">Failed.</div>';
        if(d.success){ document.getElementById('cntName').value=''; document.getElementById('cntOrg').value=''; document.getElementById('cntPos').value=''; document.getElementById('cntPhone').value=''; document.getElementById('cntEmail').value=''; document.getElementById('cntAddr').value=''; document.getElementById('cntNotes').value=''; secLoadContacts(); }
    }).catch(function(){ document.getElementById('cntMsg').innerHTML = '<div class="alert alert-danger py-1 small">Failed.</div>'; });
}
function secLoadContacts(){
    var el = document.getElementById('secContactList'); if(!el) return;
    el.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=contact_list&ajax=1')
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ el.innerHTML = '<div class="text-muted small p-3">No contacts.</div>'; return; }
        var h = '<div class="table-responsive"><table class="table tb"><thead><tr><th>Name</th><th>Organization</th><th>Phone</th><th>Email</th><th>Category</th></tr></thead><tbody>';
        d.forEach(function(c){ h += '<tr><td><strong>'+esc(c.full_name)+'</strong><br><small>'+esc(c.position||'')+'</small></td><td>'+esc(c.organization||'-')+'</td><td>'+esc(c.phone||'-')+'</td><td><a href="mailto:'+esc(c.email)+'">'+esc(c.email||'-')+'</a></td><td><span class="badge bg-secondary">'+esc(c.category)+'</span></td></tr>'; });
        h += '</tbody></table></div>'; el.innerHTML = h;
    }).catch(function(){ el.innerHTML = '<div class="text-danger small p-3">Failed.</div>'; });
}
document.addEventListener('DOMContentLoaded', secLoadContacts);
</script>
<?php endif; ?><?php if ($view === 'reports'): ?>
<?php $rptType = htmlspecialchars($_GET['type']??'appointments'); ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-bar me-2"></i>Activity Reports</div><div class="scb">
<form onsubmit="event.preventDefault(); secGenReport()" class="row g-2 mb-3">
<div class="col-md-3"><label class="fl">From</label><input type="date" id="rptFrom" class="form-control env-field" value="<?= date('Y-m-01') ?>"></div>
<div class="col-md-3"><label class="fl">To</label><input type="date" id="rptTo" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-md-4"><label class="fl">Report Type</label><select id="rptType" class="form-select env-field"><option value="appointments">Appointments</option><option value="meetings">Meetings</option><option value="documents">Documents</option><option value="requests">Requests</option><option value="communications">Communications</option></select></div>
<div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-sec w-100"><i class="fas fa-search me-1"></i>Generate</button></div>
</form>
<div id="secRptOutput"></div>
</div></div>
<script>
function secGenReport(){
    var f = document.getElementById('rptFrom').value, t = document.getElementById('rptTo').value, tp = document.getElementById('rptType').value;
    var out = document.getElementById('secRptOutput'); out.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
    fetch('school-secretary.php?view=report_data&ajax=1&from='+f+'&to='+t+'&type='+tp)
    .then(function(r){ return r.json(); }).then(function(d){
        if(!d||!d.length){ out.innerHTML = '<div class="text-muted text-center py-3">No data found for this period.</div>'; return; }
        var keys = Object.keys(d[0]); var h = '<div class="table-responsive"><table class="table tb"><thead><tr>';
        keys.forEach(function(k){ h += '<th>'+k.charAt(0).toUpperCase()+k.slice(1).replace('_',' ')+'</th>'; });
        h += '</tr></thead><tbody>';
        d.forEach(function(r){ h += '<tr>'; keys.forEach(function(k){ h += '<td>'+(r[k]||'-')+'</td>'; }); h += '</tr>'; });
        h += '</tbody></table></div>'; out.innerHTML = h;
    }).catch(function(){ out.innerHTML = '<div class="text-danger">Failed.</div>'; });
}
function secLoadReports(){
    var sel = document.getElementById('rptType');
    var tp = '<?= $rptType ?>';
    if(sel && tp !== 'appointments'){ sel.value = tp; secGenReport(); }
}
document.addEventListener('DOMContentLoaded', secLoadReports);
</script>
<?php endif; ?></div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
function esc(s){ if(!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function mbSubstr(s,n){ if(!s) return ''; return s.length>n?s.substring(0,n)+'...':s; }
</script>
</body></html>