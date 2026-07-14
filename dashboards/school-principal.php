<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/website_submissions_widget.php';
$ctx = bootstrapStaffDashboard(['school principal', 'principal', 'director general', 'ceo']);
$staff = $ctx['staff']; $students = $ctx['students']; $website = $ctx['website'];
$website_conn = $website;
$user = $ctx['user']; $uid = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? ''; $uname = $_SESSION['full_name'] ?? 'Principal';
// Strict role check: only School Principal, Director General, or CEO allowed (block Deputy Principal substring match)
$strictAllowed = ['school principal', 'director general', 'ceo'];
$roleNorm = strtolower(trim($role));
$isStrictAllowed = in_array($roleNorm, $strictAllowed, true);
if (!$isStrictAllowed) {
    header('HTTP/1.0 403 Forbidden');
    echo '<!DOCTYPE html><html><head><title>403 Forbidden</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8fafc}.card{background:#fff;border-radius:12px;padding:40px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:420px}.card h1{color:#dc2626;font-size:48px;margin:0 0 8px}.card p{color:#64748b;margin:0 0 20px}.card .btn{display:inline-block;padding:10px 24px;background:#1e40af;color:#fff;border-radius:8px;text-decoration:none;font-weight:600}</style></head><body><div class="card"><h1>403</h1><p>Access denied. Only the School Principal, Director General, or CEO may access this dashboard.</p><a href="../dashboard.php" class="btn">Go to My Dashboard</a></div></body></html>';
    exit;
}
$staff_db   = defined('STAFF_DB_NAME')    ? STAFF_DB_NAME    : 'igangaschool_staffs';
$students_db = defined('STUDENTS_DB_NAME') ? STUDENTS_DB_NAME : 'igangaschool_students';
$migrate = function($db) use ($staff_db, $students_db) {
    if (!$db) return;
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.meeting_minutes (id INT AUTO_INCREMENT PRIMARY KEY, meeting_id INT, agenda_item VARCHAR(300), discussion TEXT, resolution TEXT, action_items TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.meeting_actions (id INT AUTO_INCREMENT PRIMARY KEY, meeting_id INT, action_item TEXT, assigned_to VARCHAR(200), due_date DATE, status ENUM('pending','in_progress','completed') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.student_discipline (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, offense TEXT, reported_by VARCHAR(200), hearing_date DATE, outcome VARCHAR(500), action_taken VARCHAR(200), status ENUM('open','resolved','appealed') DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.student_discipline_records (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, violation_type VARCHAR(200), description TEXT, severity ENUM('low','medium','high') DEFAULT 'medium', action_taken VARCHAR(200), status ENUM('pending','resolved','appealed') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.student_welfare_cases (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, case_type VARCHAR(200), description TEXT, severity ENUM('low','medium','high','critical') DEFAULT 'medium', status ENUM('open','in_progress','resolved','closed') DEFAULT 'open', assigned_to VARCHAR(200), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.student_appeals (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, appeal_type VARCHAR(200), reason TEXT, outcome VARCHAR(500), status ENUM('pending','approved','rejected') DEFAULT 'pending', reviewed_by INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.quality_assurance (id INT AUTO_INCREMENT PRIMARY KEY, review_title VARCHAR(300), review_type VARCHAR(200), department VARCHAR(200), reviewer VARCHAR(200), score DECIMAL(5,2), findings TEXT, recommendations TEXT, status ENUM('draft','completed','reviewed') DEFAULT 'draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.department_performance (id INT AUTO_INCREMENT PRIMARY KEY, department VARCHAR(200), metric VARCHAR(200), value DECIMAL(14,2), period VARCHAR(50), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.institutional_kpis (id INT AUTO_INCREMENT PRIMARY KEY, kpi_name VARCHAR(300), kpi_category VARCHAR(200), target_value DECIMAL(14,2), current_value DECIMAL(14,2), period VARCHAR(50), status ENUM('on_track','at_risk','behind') DEFAULT 'on_track', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.strategic_plans (id INT AUTO_INCREMENT PRIMARY KEY, plan_name VARCHAR(300), description TEXT, start_date DATE, end_date DATE, status ENUM('draft','active','completed','cancelled') DEFAULT 'draft', created_by INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.strategic_initiatives (id INT AUTO_INCREMENT PRIMARY KEY, plan_id INT, initiative_name VARCHAR(300), description TEXT, target_date DATE, progress DECIMAL(5,2) DEFAULT 0, status ENUM('not_started','in_progress','completed') DEFAULT 'not_started', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.committee_actions (id INT AUTO_INCREMENT PRIMARY KEY, meeting_id INT, action TEXT, responsible VARCHAR(200), due_date DATE, status ENUM('pending','in_progress','completed') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.communication_log (id INT AUTO_INCREMENT PRIMARY KEY, sender_id INT, sender_name VARCHAR(200), recipient_role VARCHAR(100), subject VARCHAR(300), message TEXT, is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.principal_notices (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300), content TEXT, audience VARCHAR(100), published_by VARCHAR(200), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$staff_db}.staff_appraisals (id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT, reviewer_id INT, review_date DATE, performance_score DECIMAL(5,2), strengths TEXT, areas_improvement TEXT, overall_rating VARCHAR(50), status ENUM('draft','submitted','reviewed','completed') DEFAULT 'draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$staff_db}.department_reviews (id INT AUTO_INCREMENT PRIMARY KEY, department VARCHAR(200), reviewer_id INT, review_period VARCHAR(50), overall_score DECIMAL(5,2), strengths TEXT, weaknesses TEXT, recommendations TEXT, status ENUM('draft','submitted','reviewed') DEFAULT 'draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.meetings (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300) NOT NULL, meeting_type VARCHAR(100) DEFAULT '', meeting_date DATE DEFAULT NULL, start_time TIME DEFAULT NULL, end_time TIME DEFAULT NULL, location VARCHAR(200) DEFAULT '', agenda TEXT, status VARCHAR(50) DEFAULT 'scheduled', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.meeting_attendees (id INT AUTO_INCREMENT PRIMARY KEY, meeting_id INT NOT NULL, attendee_name VARCHAR(200) DEFAULT '', attended TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_meeting (meeting_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.clinical_placements_students (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, clinical_placement_id INT DEFAULT 0, facility_name VARCHAR(300) DEFAULT '', start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, supervisor_name VARCHAR(200) DEFAULT '', status VARCHAR(50) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$students_db}.student_academic_profiles (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL UNIQUE, gpa DECIMAL(4,2) DEFAULT 0.00, credit_hours_earned INT DEFAULT 0, total_credit_hours INT DEFAULT 0, academic_standing VARCHAR(100) DEFAULT 'Good', advisor_name VARCHAR(200) DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$staff_db}.staff_activity_log (id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, action VARCHAR(200) DEFAULT '', description TEXT, ip_address VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_staff (staff_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$staff_db}.grading_approval_workflow_log (id INT AUTO_INCREMENT PRIMARY KEY, workflow_id INT NOT NULL, stage VARCHAR(100) DEFAULT '', action VARCHAR(200) DEFAULT '', comments TEXT, actor_id INT DEFAULT 0, actor_name VARCHAR(200) DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_workflow (workflow_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
$migrate($staff); $migrate($students);
if (isset($_GET['page']) && !isset($_GET['section']) && !isset($_GET['view'])) $_GET['section'] = $_GET['page'];
$_GET['section'] = $_GET['section'] ?? $_GET['view'] ?? 'overview';
$view = $_GET['section']; if ($view === 'overview') $view = 'home';
$viewAliases = [
    'academic' => 'academic_dashboard',
    'student-affairs' => 'student_welfare',
    'operations' => 'institutional_operations',
    'approvals' => 'approval_center',
    'tasks' => 'action_tracking',
    'schedules' => 'meetings',
    'departments' => 'department_performance',
    'performance' => 'department_performance',
    'financial' => 'institutional_operations',
    'student' => 'student_management',
    'quality' => 'quality_assurance',
    'audit' => 'academic_compliance',
    'system-health' => 'institutional_operations',
    'reports-daily' => 'academic_reports',
    'reports-monthly' => 'institutional_reports',
    'reports-annual' => 'institutional_reports',
    'reports' => 'institutional_reports',
    'exports' => 'institutional_reports',
    'print' => 'institutional_reports',
    'notifications' => 'communications',
    'home' => 'home',
];
if (isset($viewAliases[$view])) $view = $viewAliases[$view];
$ajax = $_GET['ajax'] ?? ''; $sid = $_GET['sid'] ?? ''; $q = $_GET['q'] ?? '';
function pcurrency($n) { return 'UGX ' . number_format((float)$n, 0); }
function psuccess($m) { $_SESSION['p_success'] = $m; }
function perror($m) { $_SESSION['p_error'] = $m; }
function pmailto($email) { return $email ? '<a href="mailto:'.htmlspecialchars($email).'" class="text-decoration-none" title="Send email"><i class="fas fa-envelope text-primary"></i></a>' : ''; }

// -- AJAX: principal_stats --
if ($view === 'principal_stats' && $ajax === '1') {
    header('Content-Type: application/json');
    $out = ['total_students'=>0,'total_staff'=>0,'attendance_rate'=>0,'pass_rate'=>0,'welfare_alerts'=>0,'pending_approvals'=>0,'upcoming_meetings'=>0,'recent_notices'=>0,'total_revenue'=>0,'total_expenses'=>0,'health_score'=>0];
    if ($students) {
        $r = $students->query("SELECT COUNT(*) c FROM students WHERE status='Active'"); if ($r) $out['total_students'] = (int)$r->fetch_assoc()['c'];
        $r = $students->query("SELECT ROUND(AVG(CASE WHEN status='Present' THEN 100 WHEN status='Late' THEN 75 ELSE 0 END),1) v FROM student_attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"); if ($r) $out['attendance_rate'] = (float)($r->fetch_assoc()['v']??0);
        $r = $students->query("SELECT COUNT(*) p, (SELECT COUNT(*) FROM {$staff_db}.examination_records WHERE grade IS NOT NULL) t FROM {$staff_db}.examination_records WHERE grade IN('A','B','C','D')"); if ($r) { $rw=$r->fetch_assoc(); $t=(int)($rw['t']??0); $out['pass_rate']=$t>0?round((int)$rw['p']/$t*100,1):0; }
        $r = $students->query("SELECT COUNT(*) c FROM {$students_db}.student_welfare_cases WHERE status='open'"); if ($r) $out['welfare_alerts'] = (int)$r->fetch_assoc()['c'];
        $r = $students->query("SELECT COUNT(*) c FROM {$students_db}.meetings WHERE meeting_date >= CURDATE() AND status='scheduled'"); if ($r) $out['upcoming_meetings'] = (int)$r->fetch_assoc()['c'];
    }
    if ($staff) {
        $r = $staff->query("SELECT COUNT(*) c FROM staff WHERE status='Active'"); if ($r) $out['total_staff'] = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COUNT(*) c FROM grading_approval_workflow WHERE current_stage='Principal Final Approval'"); if ($r) $out['pending_approvals'] = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT IFNULL(SUM(amount),0) v FROM expenses WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"); if ($r) $out['total_expenses'] = (float)$r->fetch_assoc()['v'];
    }
    if ($students) {
        $r = $students->query("SELECT IFNULL(SUM(amount_paid),0) v FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status='completed'"); if ($r) $out['total_revenue'] = (float)$r->fetch_assoc()['v'];
        $r = $students->query("SELECT COUNT(*) c FROM {$students_db}.principal_notices"); if ($r) $out['recent_notices'] = (int)$r->fetch_assoc()['c'];
    }
    $score = 0;
    if ($out['total_students']>0) $score += 20;
    if ($out['attendance_rate']>=80) $score += 20; elseif ($out['attendance_rate']>=60) $score += 10;
    if ($out['pass_rate']>=70) $score += 20; elseif ($out['pass_rate']>=50) $score += 10;
    if ($out['welfare_alerts']==0) $score += 20; elseif ($out['welfare_alerts']<=5) $score += 10;
    if ($out['pending_approvals']==0) $score += 20; elseif ($out['pending_approvals']<=10) $score += 10;
    $out['health_score'] = $score;
    echo json_encode($out); exit;
}

// -- AJAX: program_performance_data --
if ($view === 'program_performance_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT program, COUNT(*) enrolled, ROUND(AVG(IFNULL(gpa,0)),2) avg_gpa FROM students LEFT JOIN student_academic_profiles ON students.id=student_academic_profiles.student_id WHERE students.status='Active' GROUP BY program");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: exam_monitoring_data --
if ($view === 'exam_monitoring_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($staff) {
        $prog = trim($_GET['program']??'');
        if ($prog) {
            $stmt = $staff->prepare("SELECT e.*, s.surname, s.first_name, s.program FROM {$staff_db}.examination_records e LEFT JOIN {$students_db}.students s ON e.student_id=s.id WHERE s.program=? ORDER BY e.created_at DESC LIMIT 100");
            if ($stmt) { $stmt->bind_param('s', $prog); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $r = $stmt->get_result(); $stmt->close(); } else $r = null;
        } else {
            $r = $staff->query("SELECT e.*, s.surname, s.first_name, s.program FROM {$staff_db}.examination_records e LEFT JOIN {$students_db}.students s ON e.student_id=s.id ORDER BY e.created_at DESC LIMIT 100");
        }
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: communication_data --
if ($view === 'communication_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT * FROM {$students_db}.communication_log WHERE sender_name='" . $students->real_escape_string($uname) . "' ORDER BY created_at DESC LIMIT 50");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}


// -- AJAX: clinical_training_data --
if ($view === 'clinical_training_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT cp.*, s.surname, s.first_name, s.program FROM clinical_placements_students cp LEFT JOIN students s ON cp.student_id=s.id ORDER BY cp.created_at DESC LIMIT 100");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: student_welfare_data --
if ($view === 'student_welfare_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT w.*, s.surname, s.first_name, s.program FROM {$students_db}.student_welfare_cases w LEFT JOIN students s ON w.student_id=s.id ORDER BY w.created_at DESC LIMIT 100");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: discipline_data --
if ($view === 'discipline_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT d.*, s.surname, s.first_name, s.program FROM {$students_db}.student_discipline d LEFT JOIN students s ON d.student_id=s.id ORDER BY d.created_at DESC LIMIT 100");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: student_progress_data --
if ($view === 'student_progress_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT s.id, s.surname, s.first_name, s.program, s.level, s.intake_year, s.intake_period, s.status, (SELECT COUNT(*) FROM {$staff_db}.examination_records e WHERE e.student_id=s.id AND e.grade IS NOT NULL) exams_taken FROM students s WHERE s.status='Active' ORDER BY s.surname LIMIT 100");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: staff_attendance_data --
if ($view === 'staff_attendance_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($staff) {
        $r = $staff->query("SELECT sa.*, s.full_name, s.department FROM staff_activity_log sa LEFT JOIN staff s ON sa.staff_id=s.id ORDER BY sa.created_at DESC LIMIT 100");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: staff_appraisal_data --
if ($view === 'staff_appraisal_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($staff) {
        $r = $staff->query("SELECT a.*, s.full_name FROM {$staff_db}.staff_appraisals a LEFT JOIN staff s ON a.staff_id=s.id ORDER BY a.created_at DESC LIMIT 100");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: department_performance_data --
if ($view === 'department_performance_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT * FROM {$students_db}.department_performance ORDER BY created_at DESC LIMIT 100");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: kpi_data --
if ($view === 'kpi_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT * FROM {$students_db}.institutional_kpis ORDER BY created_at DESC");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: strategic_plan_data --
if ($view === 'strategic_plan_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT p.*, (SELECT COUNT(*) FROM {$students_db}.strategic_initiatives WHERE plan_id=p.id) initiatives FROM {$students_db}.strategic_plans p ORDER BY p.created_at DESC");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: quality_assurance_data --
if ($view === 'quality_assurance_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT * FROM {$students_db}.quality_assurance ORDER BY created_at DESC LIMIT 100");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: approval_list --
if ($view === 'approval_list' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($staff) {
        $r = $staff->query("SELECT id, 'grade_approval' source, CONCAT('Grade Approval: ',IFNULL(exam_name,'')) title, requested_by requester, comments description, created_at FROM grading_approval_workflow WHERE current_stage='Principal Final Approval' UNION SELECT id, 'appeal' source, CONCAT('Appeal: ',IFNULL(appeal_type,'')) title, student_id requester, reason description, created_at FROM {$students_db}.student_appeals WHERE status='pending' ORDER BY created_at DESC");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: meeting_data --
if ($view === 'meeting_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT * FROM {$students_db}.meetings WHERE meeting_date >= CURDATE() ORDER BY meeting_date ASC LIMIT 20");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX: meeting_action_data --
if ($view === 'meeting_action_data' && $ajax === '1') {
    header('Content-Type: application/json');
    $rows = [];
    if ($students) {
        $r = $students->query("SELECT a.*, m.title meeting_title FROM {$students_db}.committee_actions a LEFT JOIN {$students_db}.meetings m ON a.meeting_id=m.id ORDER BY a.created_at DESC LIMIT 50");
        if ($r) while ($rw = $r->fetch_assoc()) $rows[] = $rw;
    }
    echo json_encode($rows); exit;
}

// -- AJAX WRITE: submit_approval_action --
if ($view === 'submit_approval_action' && $ajax === '1') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id']??0); $src = $_POST['source']??''; $act = $_POST['action']??'';
    $comments = trim($_POST['comments']??'');
    if (!$id || !$src || !$act) { echo json_encode(['success'=>false,'error'=>'Missing parameters']); exit; }
    if ($src === 'grade_approval') {
        $statusMap = ['approve'=>'approved','reject'=>'rejected','return'=>'returned_for_revision','escalate'=>'escalated'];
        $st = $statusMap[$act]??'';
        if ($st) {
            $stmt = $staff->prepare("UPDATE grading_approval_workflow SET status=?, current_stage=?, updated_at=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssi', $st, $st, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
            if ($ok) {
                $nst = $act === 'escalate' ? 'escalated' : $st;
                $stmt2 = $staff->prepare("INSERT INTO grading_approval_workflow_log (workflow_id, stage, action, comments, actor_id, actor_name, created_at) VALUES (?,?,?,?,?,?,NOW())");
                if ($stmt2) { $stmt2->bind_param('isssis', $id, $nst, $act, $comments, $uid, $uname); if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); }; $stmt2->close(); }
            }
            echo json_encode(['success'=>$ok]); exit;
        }
    } elseif ($src === 'appeal') {
        $stMap = ['approve'=>'approved','reject'=>'rejected','return'=>'pending','escalate'=>'escalated'];
        $st = $stMap[$act]??'';
        if ($st) {
            $stmt = $students->prepare("UPDATE {$students_db}.student_appeals SET status=?, outcome=?, reviewed_by=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssii', $st, $comments, $uid, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
            echo json_encode(['success'=>$ok]); exit;
        }
    }
    echo json_encode(['success'=>false]); exit;
}

// -- AJAX WRITE: create_meeting --
if ($view === 'create_meeting' && $ajax === '1') {
    header('Content-Type: application/json');
    $mt = trim($_POST['title']??'');
    $md = trim($_POST['meeting_date']??'');
    $st = trim($_POST['start_time']??'');
    $et = trim($_POST['end_time']??'');
    $ml = trim($_POST['location']??'');
    $ag = trim($_POST['agenda']??'');
    $tp = trim($_POST['meeting_type']??'Executive');
    if ($mt && $md) {
        $stmt = $students->prepare("INSERT INTO {$students_db}.meetings (title,meeting_type,meeting_date,start_time,end_time,location,agenda,created_by) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('sssssssi', $mt, $tp, $md, $st, $et, $ml, $ag, $uid); $ok = $stmt->execute(); $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Title and date required']); exit;
}

// -- AJAX WRITE: schedule_meeting --
if ($view === 'schedule_meeting' && $ajax === '1') {
    header('Content-Type: application/json');
    $mt = trim($_POST['title']??'');
    $md = trim($_POST['meeting_date']??'');
    $st = trim($_POST['start_time']??'');
    $et = trim($_POST['end_time']??'');
    $ml = trim($_POST['location']??'');
    $ag = trim($_POST['agenda']??'');
    $tp = trim($_POST['meeting_type']??'Executive');
    $at = $_POST['attendees'] ?? '';
    if ($mt && $md) {
        $stmt = $students->prepare("INSERT INTO {$students_db}.meetings (title,meeting_type,meeting_date,start_time,end_time,location,agenda,created_by) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('sssssssi', $mt, $tp, $md, $st, $et, $ml, $ag, $uid); $ok = $stmt->execute(); $mid = $students->insert_id; $stmt->close(); } else { $ok = false; $mid = 0; }
        if ($ok && $mid && $at) {
            $names = explode("\n", $at);
            foreach ($names as $n) {
                $n = trim($n);
                if ($n) {
                    $stmt2 = $students->prepare("INSERT INTO {$students_db}.meeting_attendees (meeting_id,attendee_name) VALUES (?,?)");
                    if ($stmt2) { $stmt2->bind_param('is', $mid, $n); if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); }; $stmt2->close(); }
                }
            }
        }
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Title and date required']); exit;
}

// -- AJAX WRITE: save_meeting_minutes --
if ($view === 'save_meeting_minutes' && $ajax === '1') {
    header('Content-Type: application/json');
    $mid = (int)($_POST['meeting_id']??0); $agenda = trim($_POST['agenda_item']??''); $disc = trim($_POST['discussion']??''); $res = trim($_POST['resolution']??''); $act = trim($_POST['action_items']??'');
    if ($mid) {
        $stmt = $students->prepare("INSERT INTO {$students_db}.meeting_minutes (meeting_id,agenda_item,discussion,resolution,action_items) VALUES (?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('issss', $mid, $agenda, $disc, $res, $act); $ok = $stmt->execute(); $newId = $students->insert_id; $stmt->close(); } else { $ok = false; $newId = 0; }
        echo json_encode(['success'=>$ok,'id'=>$newId]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}

// -- AJAX WRITE: update_action_status --
if ($view === 'update_action_status' && $ajax === '1') {
    header('Content-Type: application/json');
    $aid = (int)($_POST['id']??0); $st = trim($_POST['status']??'');
    if ($aid && $st) {
        $stmt = $students->prepare("UPDATE {$students_db}.committee_actions SET status=? WHERE id=?");
        if ($stmt) { $stmt->bind_param('si', $st, $aid); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}

// -- AJAX WRITE: send_communication --
if ($view === 'send_communication' && $ajax === '1') {
    header('Content-Type: application/json');
    $subj = trim($_POST['subject']??''); $msg = trim($_POST['message']??''); $rcp = trim($_POST['recipient_role']??'staff');
    if ($subj && $msg) {
        $stmt = $students->prepare("INSERT INTO {$students_db}.communication_log (sender_id,sender_name,recipient_role,subject,message) VALUES (?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('issss', $uid, $uname, $rcp, $subj, $msg); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Subject and message required']); exit;
}

// -- AJAX WRITE: publish_notice --
if ($view === 'publish_notice' && $ajax === '1') {
    header('Content-Type: application/json');
    $t = trim($_POST['title']??''); $c = trim($_POST['content']??''); $a = trim($_POST['audience']??'All');
    if ($t && $c) {
        $stmt = $students->prepare("INSERT INTO {$students_db}.principal_notices (title,content,audience,published_by) VALUES (?,?,?,?)");
        if ($stmt) { $stmt->bind_param('ssss', $t, $c, $a, $uname); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Title and content required']); exit;
}

// -- AJAX WRITE: create_student_appeal --
if ($view === 'create_student_appeal' && $ajax === '1') {
    header('Content-Type: application/json');
    $si = (int)($_POST['student_id']??0); $at = trim($_POST['appeal_type']??''); $rs = trim($_POST['reason']??'');
    if ($si && $at && $rs) {
        $stmt = $students->prepare("INSERT INTO {$students_db}.student_appeals (student_id,appeal_type,reason) VALUES (?,?,?)");
        if ($stmt) { $stmt->bind_param('iss', $si, $at, $rs); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Required fields missing']); exit;
}

// -- AJAX WRITE: update_welfare_status --
if ($view === 'update_welfare_status' && $ajax === '1') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id']??0); $st = trim($_POST['status']??'');
    if ($id && $st) {
        $stmt = $students->prepare("UPDATE {$students_db}.student_welfare_cases SET status=? WHERE id=?");
        if ($stmt) { $stmt->bind_param('si', $st, $id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}

// -- AJAX WRITE: create_strategic_plan --
if ($view === 'create_strategic_plan' && $ajax === '1') {
    header('Content-Type: application/json');
    $pn = trim($_POST['plan_name']??''); $pd = trim($_POST['description']??''); $ps = trim($_POST['start_date']??''); $pe = trim($_POST['end_date']??'');
    if ($pn && $ps) {
        $stmt = $students->prepare("INSERT INTO {$students_db}.strategic_plans (plan_name,description,start_date,end_date,created_by) VALUES (?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('ssssi', $pn, $pd, $ps, $pe, $uid); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Name and start date required']); exit;
}

// -- AJAX WRITE: update_kpi --
if ($view === 'update_kpi' && $ajax === '1') {
    header('Content-Type: application/json');
    $kn = trim($_POST['kpi_name']??''); $kc = trim($_POST['kpi_category']??''); $tv = (float)($_POST['target_value']??0); $cv = (float)($_POST['current_value']??0); $kp = trim($_POST['period']??date('Y-m')); $st = trim($_POST['status']??'on_track');
    if ($kn) {
        $stmt = $students->prepare("INSERT INTO {$students_db}.institutional_kpis (kpi_name,kpi_category,target_value,current_value,period,status) VALUES (?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('ssddss', $kn, $kc, $tv, $cv, $kp, $st); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'KPI name required']); exit;
}

// -- AJAX WRITE: create_qa_review --
if ($view === 'create_qa_review' && $ajax === '1') {
    header('Content-Type: application/json');
    $rt = trim($_POST['review_title']??''); $rty = trim($_POST['review_type']??''); $rd = trim($_POST['department']??''); $rv = trim($_POST['reviewer']??$uname); $sc = (float)($_POST['score']??0); $rf = trim($_POST['findings']??''); $rr = trim($_POST['recommendations']??'');
    if ($rt) {
        $stmt = $students->prepare("INSERT INTO {$students_db}.quality_assurance (review_title,review_type,department,reviewer,score,findings,recommendations) VALUES (?,?,?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('ssssdss', $rt, $rty, $rd, $rv, $sc, $rf, $rr); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Review title required']); exit;
}

// -- AJAX WRITE: record_department_review --
if ($view === 'record_department_review' && $ajax === '1') {
    header('Content-Type: application/json');
    $dep = trim($_POST['department']??''); $rp = trim($_POST['review_period']??''); $os = (float)($_POST['overall_score']??0); $str = trim($_POST['strengths']??''); $wk = trim($_POST['weaknesses']??''); $rec = trim($_POST['recommendations']??'');
    if ($dep && $rp) {
        $stmt = $staff->prepare("INSERT INTO {$staff_db}.department_reviews (department,reviewer_id,review_period,overall_score,strengths,weaknesses,recommendations,status) VALUES (?,?,?,?,'submitted')");
        // Note: original query had 6 params + 'submitted', simplified here
        $stmt2 = $staff->prepare("INSERT INTO {$staff_db}.department_reviews (department,reviewer_id,review_period,overall_score,strengths,weaknesses,recommendations,status) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt2) { $s='submitted'; $stmt2->bind_param('sisdssss', $dep, $uid, $rp, $os, $str, $wk, $rec, $s); if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); }; $ok = $stmt2->affected_rows > 0; $stmt2->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Department and period required']); exit;
}

// -- AJAX WRITE: submit_staff_appraisal --
if ($view === 'submit_staff_appraisal' && $ajax === '1') {
    header('Content-Type: application/json');
    $si = (int)($_POST['staff_id']??0); $rd = trim($_POST['review_date']??date('Y-m-d')); $ps = (float)($_POST['performance_score']??0); $stg = trim($_POST['strengths']??''); $ai = trim($_POST['areas_improvement']??''); $or = trim($_POST['overall_rating']??'');
    if ($si && $ps) {
        $stmt2 = $staff->prepare("INSERT INTO {$staff_db}.staff_appraisals (staff_id,reviewer_id,review_date,performance_score,strengths,areas_improvement,overall_rating,status) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt2) { $s='submitted'; $stmt2->bind_param('iisdssss', $si, $uid, $rd, $ps, $stg, $ai, $or, $s); if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); }; $ok = $stmt2->affected_rows > 0; $stmt2->close(); } else $ok = false;
        echo json_encode(['success'=>$ok]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Staff ID and score required']); exit;
}

// -- AJAX WRITE: approve_graduation --
if ($view === 'approve_graduation' && $ajax === '1') {
    header('Content-Type: application/json');
    $sid = (int)($_POST['student_id']??0);
    if ($sid) { $ok = $students->query("UPDATE students SET status='Graduated' WHERE id=$sid"); echo json_encode(['success'=>($ok && $students->affected_rows>0)]); exit; }
    echo json_encode(['success'=>false]); exit;
}

// -- AJAX catch-all --
if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode([]); exit; }

// -- POST handlers --
// Handle website submission actions
if (function_exists('handleWebsiteSubmissionsAction')) {
    handleWebsiteSubmissionsAction($website_conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (function_exists('verifyCsrfToken') && !verifyCsrfToken()) { $_SESSION['error'] = 'Invalid security token.'; header('Location: school-principal.php'); exit; }
    $act = $_POST['action'];
    if ($act === 'publish_notice' && $students && $staff) {
        $t = trim($_POST['notice_title']??''); $c = trim($_POST['notice_content']??''); $a = trim($_POST['notice_audience']??'All');
        if ($t && $c) {
            $stmt = $students->prepare("INSERT INTO {$students_db}.principal_notices (title,content,audience,published_by) VALUES (?,?,?,?)");
            if ($stmt) { $stmt->bind_param('ssss', $t, $c, $a, $uname); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $ok = $stmt->affected_rows > 0; $stmt->close(); } else $ok = false;
            if ($ok) { psuccess('Notice published.'); } else { perror('Database write failed.'); }
        } else { perror('Title and content required.'); }
        header('Location: school-principal.php?section=notices'); exit;
    }
}

$sv = $_SESSION['p_success'] ?? ''; $ev = $_SESSION['p_error'] ?? '';
unset($_SESSION['p_success'], $_SESSION['p_error']);?>
<!DOCTYPE html>
<html lang="en"><head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>









.scard{background:#fff;border-radius:12px;border:1px solid #e5e7eb;transition:all .2s;height:100%}
.scard:hover{box-shadow:0 4px 16px rgba(0,0,0,.06)}
.scard .sch{background:#f8fafc;padding:14px 20px;border-bottom:1px solid #e5e7eb;border-radius:12px 12px 0 0;font-weight:600;color:#1a237e;font-size:14px}
.scard .scb{padding:20px}
.scard .scb.p-0{padding:0}
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
.approval-card{border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:12px;transition:all .2s}
.approval-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.06)}
.approval-card .ac-title{font-weight:600;font-size:14px;color:#0f172a}
.approval-card .ac-meta{font-size:11px;color:#94a3b8}
.approval-card .ac-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
.approval-card .ac-actions .btn{font-size:12px;padding:4px 10px}
@media(max-width:768px){.kpi-card{padding:12px 14px}.kpi-card .kpi-value{font-size:17px}}
</style>
</head><body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="prin-content dashboard-section active" data-section="principal">
<div style="text-align:right;margin-bottom:8px" class="no-print"><button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> Print</button></div>
<?php if ($sv): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($sv) ?></div><?php endif; ?>
<?php if ($ev): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($ev) ?></div><?php endif; ?>
<?php if ($view === 'home'): ?>
<?php
$now = date('Y-m-d'); $totalStudents = 0; $totalStaff = 0; $attRate = 0; $passRate = 0; $welfareAlerts = 0; $pendApprovals = 0; $upMt = 0; $revTotal = 0; $expTotal = 0;
try {
    if ($students) {
        $r = $students->query("SELECT COUNT(*) c FROM students WHERE status='Active'"); if ($r) $totalStudents = (int)$r->fetch_assoc()['c'];
        $r = $students->query("SELECT ROUND(AVG(IFNULL(attendance_percentage,0)),1) v FROM student_attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"); if ($r) $attRate = (float)($r->fetch_assoc()['v']??0);
        $r = $students->query("SELECT COUNT(*) p, (SELECT COUNT(*) FROM igangaschool_staffs.examination_records WHERE grade IS NOT NULL) t FROM igangaschool_staffs.examination_records WHERE grade IN('A','B','C','D')"); if ($r) { $rw=$r->fetch_assoc(); $t=(int)($rw['t']??0); $passRate=$t>0?round((int)$rw['p']/$t*100,1):0; }
        $r = $students->query("SELECT COUNT(*) c FROM {$students_db}.student_welfare_cases WHERE status='open'"); if ($r) $welfareAlerts = (int)$r->fetch_assoc()['c'];
        $r = $students->query("SELECT COUNT(*) c FROM {$students_db}.meetings WHERE meeting_date >= CURDATE() AND status='scheduled'"); if ($r) $upMt = (int)$r->fetch_assoc()['c'];
        $r = $students->query("SELECT IFNULL(SUM(amount_paid),0) v FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status='completed'"); if ($r) $revTotal = (float)$r->fetch_assoc()['v'];
    }
    if ($staff) {
        $r = $staff->query("SELECT COUNT(*) c FROM staff WHERE status='Active'"); if ($r) $totalStaff = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT COUNT(*) c FROM grading_approval_workflow WHERE current_stage='Principal Final Approval'"); if ($r) $pendApprovals = (int)$r->fetch_assoc()['c'];
        $r = $staff->query("SELECT IFNULL(SUM(amount),0) v FROM expenses WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"); if ($r) $expTotal = (float)$r->fetch_assoc()['v'];
    }
    $healthScore = 0;
    if ($totalStudents>0) $healthScore+=20;
    if ($attRate>=80) $healthScore+=20; elseif ($attRate>=60) $healthScore+=10;
    if ($passRate>=70) $healthScore+=20; elseif ($passRate>=50) $healthScore+=10;
    if ($welfareAlerts==0) $healthScore+=20; elseif ($welfareAlerts<=5) $healthScore+=10;
    if ($pendApprovals==0) $healthScore+=20; elseif ($pendApprovals<=10) $healthScore+=10;
} catch (Exception $e) { error_log('school-principal context: ' . $e->getMessage()); }
$recentNotices = []; $upcomingMts = []; $recentComms = [];
try {
    if ($students) {
        $r = $students->query("SELECT * FROM {$students_db}.principal_notices ORDER BY created_at DESC LIMIT 5"); if ($r) while ($a = $r->fetch_assoc()) $recentNotices[] = $a;
        $r = $students->query("SELECT * FROM {$students_db}.meetings WHERE meeting_date >= CURDATE() ORDER BY meeting_date ASC LIMIT 5"); if ($r) while ($a = $r->fetch_assoc()) $upcomingMts[] = $a;
    }
    if ($students) { $r = $students->query("SELECT subject, created_at FROM {$students_db}.communication_log ORDER BY created_at DESC LIMIT 5"); if ($r) while ($a = $r->fetch_assoc()) $recentComms[] = $a; }
} catch (Exception $e) { error_log('school-principal context: ' . $e->getMessage()); }
?>
<div class="row g-3 mb-4">
<div class="col-md-3 col-6"><div class="kpi-card primary"><div class="kpi-icon"><i class="fas fa-users"></i></div><div><div class="kpi-value"><?= number_format($totalStudents) ?></div><div class="kpi-label">Total Students</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card success"><div class="kpi-icon"><i class="fas fa-user-tie"></i></div><div><div class="kpi-value"><?= number_format($totalStaff) ?></div><div class="kpi-label">Total Staff</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card info"><div class="kpi-icon"><i class="fas fa-calendar-check"></i></div><div><div class="kpi-value"><?= $attRate ?>%</div><div class="kpi-label">Attendance Rate</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card purple"><div class="kpi-icon"><i class="fas fa-graduation-cap"></i></div><div><div class="kpi-value"><?= $passRate ?>%</div><div class="kpi-label">Pass Rate</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card warning"><div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div><div><div class="kpi-value"><?= $welfareAlerts ?></div><div class="kpi-label">Welfare Alerts</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card danger"><div class="kpi-icon"><i class="fas fa-check-double"></i></div><div><div class="kpi-value"><?= $pendApprovals ?></div><div class="kpi-label">Pending Approvals</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card" style=""><div class="kpi-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-handshake"></i></div><div><div class="kpi-value"><?= $upMt ?></div><div class="kpi-label">Upcoming Meetings</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card" style=""><div class="kpi-icon" style="background:#e0f2fe;color:#0891b2"><i class="fas fa-heartbeat"></i></div><div><div class="kpi-value"><?= $healthScore ?>/100</div><div class="kpi-label">Health Score</div></div></div></div>
</div>
<div class="row g-3 mb-4">
<div class="col-md-6">
<div class="scard"><div class="sch"><i class="fas fa-bullhorn me-2"></i>Recent Notices</div><div class="scb p-0">
<?php if ($recentNotices): foreach ($recentNotices as $n): ?>
<div class="act-item"><div class="fw-bold small"><?= htmlspecialchars($n['title']) ?></div><div class="time"><?= htmlspecialchars($n['audience']??'All') ?> &middot; <?= htmlspecialchars($n['created_at']) ?></div></div>
<?php endforeach; else: ?>
<div class="text-muted small p-3">No recent notices.</div>
<?php endif; ?>
</div></div>
</div>
<div class="col-md-6">
<div class="scard"><div class="sch"><i class="fas fa-calendar me-2"></i>Upcoming Meetings</div><div class="scb p-0">
<?php if ($upcomingMts): foreach ($upcomingMts as $m): ?>
<div class="act-item"><div class="fw-bold small"><?= htmlspecialchars($m['title']) ?></div><div class="time"><?= htmlspecialchars($m['meeting_date']) ?> &middot; <?= htmlspecialchars($m['start_time']??'--') ?> &middot; <?= htmlspecialchars($m['location']??'') ?></div></div>
<?php endforeach; else: ?>
<div class="text-muted small p-3">No upcoming meetings.</div>
<?php endif; ?>
</div></div>
</div>
</div>
<div class="row g-3">
<div class="col-md-6">
<div class="scard"><div class="sch"><i class="fas fa-chart-line me-2"></i>Financial Summary (MTD)</div><div class="scb">
<div class="d-flex justify-content-around text-center">
<div><div class="fw-bold h4 text-success"><?= pcurrency($revTotal) ?></div><small class="text-muted">Revenue</small></div>
<div><div class="fw-bold h4 text-danger"><?= pcurrency($expTotal) ?></div><small class="text-muted">Expenses</small></div>
<div><div class="fw-bold h4 text-primary"><?= pcurrency($revTotal-$expTotal) ?></div><small class="text-muted">Net</small></div>
</div>
</div></div>
</div>
<div class="col-md-6">
<div class="scard"><div class="sch"><i class="fas fa-comments me-2"></i>Recent Communications</div><div class="scb p-0">
<?php if ($recentComms): foreach ($recentComms as $c): ?>
<div class="act-item"><div class="small"><i class="fas fa-envelope text-primary me-2"></i><?= htmlspecialchars(mb_substr($c['subject'],0,60)) ?></div><div class="time"><?= htmlspecialchars($c['created_at']) ?></div></div>
<?php endforeach; else: ?>
<div class="text-muted small p-3">No communications.</div>
<?php endif; ?>
</div></div>
</div>
</div>
<!-- Website Submissions -->
<div class="row g-3 mb-4">
<div class="col-12">
<div class="scard"><div class="sch"><i class="fas fa-globe me-2"></i>Website Submissions</div><div class="scb p-0">
<?php if (function_exists('renderWebsiteSubmissionsWidget') && $website_conn): ?>
    <?php renderWebsiteSubmissionsWidget($website_conn, ['contacts', 'donations', 'volunteers', 'applications'], 10); ?>
<?php else: ?>
    <div class="text-center py-4 text-muted">
        <i class="fas fa-globe fa-2x mb-2" style="color:#94a3b8;"></i>
        <p>Website submissions will appear here.</p>
    </div>
<?php endif; ?>
</div></div>
</div>
</div>
<?php endif; ?>
<?php if ($view === 'academic_dashboard'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-pie me-2"></i>Academic Dashboard</div><div class="scb">
<div class="row g-3 mb-3">
<div class="col-md-3 col-6"><div class="kpi-card primary"><div class="kpi-icon"><i class="fas fa-users"></i></div><div><div class="kpi-value" id="acdTotal">0</div><div class="kpi-label">Active Students</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card success"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><div><div class="kpi-value" id="acdPass">0%</div><div class="kpi-label">Pass Rate</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card info"><div class="kpi-icon"><i class="fas fa-calendar-check"></i></div><div><div class="kpi-value" id="acdAtt">0%</div><div class="kpi-label">Attendance</div></div></div></div>
<div class="col-md-3 col-6"><div class="kpi-card warning"><div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div><div><div class="kpi-value" id="acdRisk">0</div><div class="kpi-label">Open Welfare</div></div></div></div>
</div>
</div></div>
<script>
fetch('school-principal.php?view=principal_stats&ajax=1').then(function(r){return r.json()}).then(function(d){
document.getElementById('acdTotal').textContent=d.total_students; document.getElementById('acdPass').textContent=d.pass_rate+'%';
document.getElementById('acdAtt').textContent=d.attendance_rate+'%'; document.getElementById('acdRisk').textContent=d.welfare_alerts;
}).catch(function(e){ console.warn('[ISNM]', e); });
</script>
<?php endif; ?>

<?php if ($view === 'program_performance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-layer-group me-2"></i>Program Performance</div><div class="scb p-0"><div id="progPerfList"></div></div></div>
<script>
function loadProgramPerf(){
var el=document.getElementById('progPerfList'); if(!el) return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=program_performance_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No program data.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Program</th><th>Enrolled</th><th>Avg GPA</th></tr></thead><tbody>';
d.forEach(function(p){h+='<tr><td><strong>'+esc(p.program)+'</strong></td><td>'+esc(p.enrolled)+'</td><td>'+esc(p.avg_gpa||'0.00')+'</td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadProgramPerf);
</script>
<?php endif; ?>

<?php if ($view === 'exam_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-alt me-2"></i>Exam Monitoring</div><div class="scb">
<div class="row g-2 mb-3"><div class="col-md-4"><select id="examProgFilter" class="form-select env-field" onchange="loadExamData()"><option value="">All Programs</option><option>Nursing</option><option>Midwifery</option></select></div></div>
<div id="examDataList" class="table-responsive"></div>
</div></div>
<script>
function loadExamData(){var el=document.getElementById('examDataList');if(!el)return;var p=document.getElementById('examProgFilter').value;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=exam_monitoring_data&ajax=1&program='+encodeURIComponent(p)).then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small py-3">No exam records.</div>';return;}
var h='<table class="table tb"><thead><tr><th>Student</th><th>Program</th><th>Exam</th><th>Grade</th><th>Date</th></tr></thead><tbody>';
d.forEach(function(e){h+='<tr><td>'+esc(e.surname||'')+', '+esc(e.first_name||'')+'</td><td>'+esc(e.program||'')+'</td><td>'+esc(e.exam_name||'')+'</td><td><strong>'+esc(e.grade||'-')+'</strong></td><td class="small">'+esc(e.created_at||'')+'</td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadExamData);
</script>
<?php endif; ?>

<?php if ($view === 'result_approvals'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-double me-2"></i>Result Approvals</div><div class="scb">
<p class="text-muted small">Pending grade approvals requiring Principal sign-off.</p>
<div id="resultApprovalList"></div>
</div></div>
<script>
function loadResultApprovals(){
var el=document.getElementById('resultApprovalList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=approval_list&ajax=1').then(function(r){return r.json()}).then(function(d){
var items=(d||[]).filter(function(x){return x.source==='grade_approval';});
if(!items.length){el.innerHTML='<div class="text-muted small py-3">No pending grade approvals.</div>';return;}
var h='';
items.forEach(function(a){h+='<div class="approval-card"><div class="ac-title">'+esc(a.title)+'</div><div class="ac-meta">Requester: '+esc(a.requester)+' | '+esc(a.created_at)+'</div><div class="ac-actions"><button class="btn btn-success btn-sm" onclick="processApproval('+a.id+',\'grade_approval\',\'approve\')"><i class="fas fa-check me-1"></i>Approve</button><button class="btn btn-danger btn-sm" onclick="processApproval('+a.id+',\'grade_approval\',\'reject\')"><i class="fas fa-times me-1"></i>Reject</button><button class="btn btn-warning btn-sm" onclick="processApproval('+a.id+',\'grade_approval\',\'return\')"><i class="fas fa-undo me-1"></i>Return</button><button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none" onclick="processApproval('+a.id+',\'grade_approval\',\'escalate\')"><i class="fas fa-arrow-up me-1"></i>DG</button></div></div>';});
el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadResultApprovals);
</script>
<?php endif; ?>

<?php if ($view === 'clinical_training'): ?>
<div class="scard"><div class="sch"><i class="fas fa-clinic-medical me-2"></i>Clinical Training Oversight</div><div class="scb p-0"><div id="clinicalTrainingList"></div></div></div>
<script>
function loadClinicalTraining(){
var el=document.getElementById('clinicalTrainingList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=clinical_training_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No clinical placements.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Program</th><th>Facility</th><th>Start</th><th>End</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(c){h+='<tr><td>'+esc(c.surname||'')+', '+esc(c.first_name||'')+'</td><td>'+esc(c.program||'')+'</td><td>'+esc(c.facility_name||c.placement_location||'')+'</td><td class="small">'+esc(c.start_date||'')+'</td><td class="small">'+esc(c.end_date||'')+'</td><td>'+esc(c.status||'-')+'</td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadClinicalTraining);
</script>
<?php endif; ?>

<?php if ($view === 'academic_quality'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-circle me-2"></i>Academic Quality</div><div class="scb">
<p class="text-muted small">Quality assurance reviews and academic standards monitoring.</p>
<div id="acadQualityList" class="table-responsive"></div>
</div></div>
<script>
function loadAcadQuality(){
var el=document.getElementById('acadQualityList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=quality_assurance_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small py-3">No QA reviews.</div>';return;}
var h='<table class="table tb"><thead><tr><th>Title</th><th>Type</th><th>Dept</th><th>Score</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(q){h+='<tr><td><strong>'+esc(q.review_title)+'</strong></td><td>'+esc(q.review_type||'')+'</td><td>'+esc(q.department||'')+'</td><td>'+esc(q.score||'0.00')+'</td><td><span class="badge bg-'+(q.status==='reviewed'?'success':q.status==='completed'?'info':'warning text-dark')+'">'+esc(q.status)+'</span></td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadAcadQuality);
</script>
<?php endif; ?>

<?php if ($view === 'graduation_readiness'): ?>
<div class="scard"><div class="sch"><i class="fas fa-graduation-cap me-2"></i>Graduation Readiness</div><div class="scb">
<p class="text-muted small">Review and approve students for graduation.</p>
<div id="gradReadinessList" class="table-responsive"></div>
</div></div>
<script>
function loadGradReadiness(){
var el=document.getElementById('gradReadinessList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=student_progress_data&ajax=1').then(function(r){return r.json()}).then(function(d){
var candidates=(d||[]).filter(function(s){return s.status==='Active'&&parseInt(s.level)>=3;});
if(!candidates.length){el.innerHTML='<div class="text-muted small py-3">No graduation candidates found.</div>';return;}
var h='<table class="table tb"><thead><tr><th>Name</th><th>Program</th><th>Level</th><th>Exams</th><th></th></tr></thead><tbody>';
candidates.forEach(function(s){h+='<tr><td>'+esc(s.surname)+', '+esc(s.first_name)+'</td><td>'+esc(s.program)+'</td><td>'+esc(s.level)+'</td><td>'+esc(s.exams_taken||'0')+'</td><td><button class="btn btn-sm btn-success" onclick="approveGrad('+s.id+')"><i class="fas fa-check me-1"></i>Approve</button></td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
function approveGrad(id){if(!confirm('Approve this student for graduation?'))return;var fd=new FormData();fd.append('student_id',id);fd.append('csrf_token', window.CSRF_TOKEN);fetch('school-principal.php?view=approve_graduation&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(d.success){alert('Graduation approved.');loadGradReadiness();}else{alert('Failed.');}}).catch(function(e){ console.warn('[ISNM]', e); });}
document.addEventListener('DOMContentLoaded',loadGradReadiness);
</script>
<?php endif; ?>

<?php if ($view === 'academic_compliance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-shield-alt me-2"></i>Academic Compliance</div><div class="scb">
<p class="text-muted small">Accreditation readiness and compliance monitoring.</p>
<div class="row g-3">
<div class="col-md-6"><div class="border rounded p-3"><strong>UNMEB Accreditation</strong><br><small class="text-muted">Status: Compliant</small><br><span class="badge bg-success">Compliant</span></div></div>
<div class="col-md-6"><div class="border rounded p-3"><strong>NCHE Compliance</strong><br><small class="text-muted">Status: Compliant</small><br><span class="badge bg-success">Compliant</span></div></div>
<div class="col-md-6"><div class="border rounded p-3"><strong>Curriculum Review</strong><br><small class="text-muted">Last review: 6 months ago</small><br><span class="badge bg-warning text-dark">Due Soon</span></div></div>
<div class="col-md-6"><div class="border rounded p-3"><strong>Clinical Partnerships</strong><br><small class="text-muted">12 active agreements</small><br><span class="badge bg-info">Active</span></div></div>
</div>
</div></div>
<?php endif; ?>
<?php if ($view === 'student_management'): ?>
<div class="scard"><div class="sch"><i class="fas fa-user-graduate me-2"></i>Student Overview</div><div class="scb p-0"><div id="studentOverviewList"></div></div></div>
<script>
function loadStudentOverview(){
var el=document.getElementById('studentOverviewList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=student_progress_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No students.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Name</th><th>Program</th><th>Level</th><th>Intake</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(s){h+='<tr><td><strong>'+esc(s.surname)+', '+esc(s.first_name)+'</strong></td><td>'+esc(s.program)+'</td><td>'+esc(s.level)+'</td><td class="small">'+esc(s.intake_year||'')+' '+esc(s.intake_period||'')+'</td><td><span class="badge bg-'+(s.status==='Active'?'success':'secondary')+'">'+esc(s.status)+'</span></td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadStudentOverview);
</script>
<?php endif; ?>

<?php if ($view === 'student_welfare'): ?>
<div class="scard"><div class="sch"><i class="fas fa-hand-holding-heart me-2"></i>Student Welfare Cases</div><div class="scb">
<div class="mb-2"><select id="welfStatusFilter" class="form-select env-field w-auto" onchange="loadWelfareData()"><option value="">All</option><option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></div>
<div id="welfareDataList" class="table-responsive"></div>
</div></div>
<script>
function loadWelfareData(){
var el=document.getElementById('welfareDataList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=student_welfare_data&ajax=1').then(function(r){return r.json()}).then(function(d){
var f=document.getElementById('welfStatusFilter').value; if(f) d=(d||[]).filter(function(w){return w.status===f;});
if(!d||!d.length){el.innerHTML='<div class="text-muted small py-3">No welfare cases.</div>';return;}
var h='<table class="table tb"><thead><tr><th>Student</th><th>Case Type</th><th>Severity</th><th>Status</th><th></th></tr></thead><tbody>';
d.forEach(function(w){var sc=w.severity==='critical'?'danger':w.severity==='high'?'warning':w.severity==='medium'?'info':'secondary';var stc=w.status==='open'?'danger':w.status==='in_progress'?'warning':w.status==='resolved'?'success':'secondary';
h+='<tr><td>'+esc(w.surname||'')+', '+esc(w.first_name||'')+'</td><td>'+esc(w.case_type||'')+'</td><td><span class="badge bg-'+sc+'">'+esc(w.severity)+'</span></td><td><span class="badge bg-'+stc+'">'+esc(w.status)+'</span></td><td><select class="form-select form-select-sm" style="width:auto" onchange="updateWelfare('+w.id+',this.value)"><option value="">Action</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
function updateWelfare(id,st){if(!st)return;var fd=new FormData();fd.append('id',id);fd.append('status',st);fd.append('csrf_token', window.CSRF_TOKEN);fetch('school-principal.php?view=update_welfare_status&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(d.success)loadWelfareData();}).catch(function(e){ console.warn('[ISNM]', e); });}
document.addEventListener('DOMContentLoaded',loadWelfareData);
</script>
<?php endif; ?>

<?php if ($view === 'discipline_oversight'): ?>
<div class="scard"><div class="sch"><i class="fas fa-gavel me-2"></i>Discipline Oversight</div><div class="scb p-0"><div id="disciplineDataList"></div></div></div>
<script>
function loadDisciplineData(){
var el=document.getElementById('disciplineDataList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=discipline_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No discipline records.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Student</th><th>Offense</th><th>Reported By</th><th>Status</th><th>Date</th></tr></thead><tbody>';
d.forEach(function(dc){h+='<tr><td>'+esc(dc.surname||'')+', '+esc(dc.first_name||'')+'</td><td>'+esc(mbSubstr(dc.offense||'',60))+'</td><td>'+esc(dc.reported_by||'')+'</td><td><span class="badge bg-'+(dc.status==='resolved'?'success':dc.status==='appealed'?'info':'warning text-dark')+'">'+esc(dc.status)+'</span></td><td class="small">'+esc(dc.created_at)+'</td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadDisciplineData);
</script>
<?php endif; ?>

<?php if ($view === 'student_appeals'): ?>
<div class="scard"><div class="sch"><i class="fas fa-file-contract me-2"></i>Student Appeals</div><div class="scb">
<div id="appealList"></div>
</div></div>
<script>
function loadAppeals(){
var el=document.getElementById('appealList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=approval_list&ajax=1').then(function(r){return r.json()}).then(function(d){
var items=(d||[]).filter(function(x){return x.source==='appeal';});
if(!items.length){el.innerHTML='<div class="text-muted small py-3">No pending appeals.</div>';return;}
var h='';
items.forEach(function(a){h+='<div class="approval-card"><div class="ac-title">'+esc(a.title)+'</div><div class="ac-meta">Student ID: '+esc(a.requester)+' | '+esc(a.created_at)+'</div><div class="small mb-2">'+esc(a.description||'')+'</div><div class="ac-actions"><button class="btn btn-success btn-sm" onclick="processApproval('+a.id+',\'appeal\',\'approve\')"><i class="fas fa-check me-1"></i>Approve</button><button class="btn btn-danger btn-sm" onclick="processApproval('+a.id+',\'appeal\',\'reject\')"><i class="fas fa-times me-1"></i>Reject</button><button class="btn btn-warning btn-sm" onclick="processApproval('+a.id+',\'appeal\',\'return\')"><i class="fas fa-undo me-1"></i>Return</button></div></div>';});
el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
processApproval = window.processApproval || function(id,src,action){
var comments=prompt('Enter comments for this action:'); if(comments===null) comments='';
var fd=new FormData();fd.append('id',id);fd.append('source',src);fd.append('action',action);fd.append('comments',comments);fd.append('csrf_token', window.CSRF_TOKEN);
fetch('school-principal.php?view=submit_approval_action&ajax=1',{method:'POST',body:fd})
.then(function(r){return r.json()}).then(function(d){if(d.success){if(typeof loadAppeals==='function')loadAppeals();if(typeof loadResultApprovals==='function')loadResultApprovals();if(typeof loadApprovals==='function')loadApprovals('all');alert('Action completed.');}else{alert('Action failed.');}}).catch(function(){alert('Error.');});
};
document.addEventListener('DOMContentLoaded',loadAppeals);
</script>
<?php endif; ?>

<?php if ($view === 'student_progress'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-bar me-2"></i>Student Progress Tracker</div><div class="scb p-0"><div id="studentOverviewList"></div></div></div>
<script>document.addEventListener('DOMContentLoaded',function(){loadStudentOverview();});</script>
<?php endif; ?>

<?php if ($view === 'student_risk'): ?>
<div class="scard"><div class="sch"><i class="fas fa-exclamation-triangle me-2"></i>Student Risk Indicators</div><div class="scb">
<p class="text-muted small">Students at risk based on welfare cases and academic standing.</p>
<div id="studentRiskList" class="table-responsive"></div>
</div></div>
<script>
function loadStudentRisk(){
var el=document.getElementById('studentRiskList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
Promise.all([
fetch('school-principal.php?view=student_welfare_data&ajax=1').then(function(r){return r.json()}),
fetch('school-principal.php?view=student_progress_data&ajax=1').then(function(r){return r.json()})
]).then(function(results){
var welfare=results[0]||[]; var students=results[1]||[];
if(!students.length){el.innerHTML='<div class="text-muted small py-3">No data.</div>';return;}
var riskMap={}; welfare.filter(function(w){return w.status==='open';}).forEach(function(w){riskMap[w.student_id]=riskMap[w.student_id]||0; riskMap[w.student_id]++;});
var h='<table class="table tb"><thead><tr><th>Name</th><th>Program</th><th>Open Cases</th><th>Risk Level</th></tr></thead><tbody>';
students.forEach(function(s){var cases=riskMap[s.id]||0; var rl=cases>2?'High':cases>0?'Medium':'Low'; var rcls=cases>2?'danger':cases>0?'warning':'success';
h+='<tr><td>'+esc(s.surname)+', '+esc(s.first_name)+'</td><td>'+esc(s.program)+'</td><td>'+cases+'</td><td><span class="badge bg-'+rcls+'">'+rl+'</span></td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadStudentRisk);
</script>
<?php endif; ?>
<?php if ($view === 'staff_overview'): ?>
<div class="scard"><div class="sch"><i class="fas fa-users me-2"></i>Staff Overview</div><div class="scb">
<div id="staffOverviewList" class="table-responsive"></div>
</div></div>
<script>
function loadStaffOverview(){
var el=document.getElementById('staffOverviewList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=staff_attendance_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small py-3">No staff activity.</div>';return;}
var seen={}; var staffList=[]; d.forEach(function(s){if(!seen[s.staff_id]){seen[s.staff_id]=true;staffList.push(s);}});
var h='<table class="table tb"><thead><tr><th>Name</th><th>Department</th><th>Last Activity</th></tr></thead><tbody>';
staffList.slice(0,50).forEach(function(s){h+='<tr><td><strong>'+esc(s.full_name||'')+'</strong></td><td>'+esc(s.department||'-')+'</td><td class="small">'+esc(s.created_at||'')+'</td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadStaffOverview);
</script>
<?php endif; ?>

<?php if ($view === 'department_performance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-building me-2"></i>Department Performance</div><div class="scb p-0"><div id="deptPerfList"></div></div></div>
<script>
function loadDeptPerf(){
var el=document.getElementById('deptPerfList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=department_performance_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No department performance data.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Department</th><th>Metric</th><th>Value</th><th>Period</th></tr></thead><tbody>';
d.forEach(function(p){h+='<tr><td><strong>'+esc(p.department)+'</strong></td><td>'+esc(p.metric)+'</td><td>'+esc(p.value)+'</td><td class="small">'+esc(p.period||'')+'</td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadDeptPerf);
</script>
<?php endif; ?>

<?php if ($view === 'staff_attendance'): ?>
<div class="scard"><div class="sch"><i class="fas fa-clipboard-check me-2"></i>Staff Attendance Log</div><div class="scb p-0"><div id="staffOverviewList"></div></div></div>
<script>document.addEventListener('DOMContentLoaded',function(){loadStaffOverview();});</script>
<?php endif; ?>

<?php if ($view === 'staff_appraisals'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-line me-2"></i>Staff Appraisals</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-3"><input type="text" id="apprStaffId" class="form-control env-field" placeholder="Staff ID"></div>
<div class="col-md-2"><input type="number" id="apprScore" class="form-control env-field" placeholder="Score" step="0.01"></div>
<div class="col-md-2"><input type="text" id="apprRating" class="form-control env-field" placeholder="Rating"></div>
<div class="col-md-2"><button class="btn btn-sec w-100" onclick="submitAppraisal()"><i class="fas fa-save"></i></button></div>
</div>
<div class="mb-3"><textarea id="apprStrengths" class="form-control env-field mb-2" placeholder="Strengths" rows="2"></textarea><textarea id="apprImprove" class="form-control env-field" placeholder="Areas for improvement" rows="2"></textarea></div>
<div id="apprMsg" class="small mb-2"></div>
<div id="apprList" class="table-responsive"></div>
</div></div>
<script>
function submitAppraisal(){
var fd=new FormData();fd.append('staff_id',document.getElementById('apprStaffId').value);fd.append('performance_score',document.getElementById('apprScore').value);fd.append('overall_rating',document.getElementById('apprRating').value);fd.append('strengths',document.getElementById('apprStrengths').value);fd.append('areas_improvement',document.getElementById('apprImprove').value);fd.append('review_date','<?= date('Y-m-d') ?>');fd.append('csrf_token', window.CSRF_TOKEN);
fetch('school-principal.php?view=submit_staff_appraisal&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
document.getElementById('apprMsg').innerHTML=d.success?'<span class="text-success">Saved.</span>':'<span class="text-danger">'+(d.error||'Failed')+'</span>';
if(d.success){document.getElementById('apprStaffId').value='';document.getElementById('apprScore').value='';document.getElementById('apprRating').value='';document.getElementById('apprStrengths').value='';document.getElementById('apprImprove').value='';loadAppraisals();}
}).catch(function(e){ console.warn('[ISNM]', e); });
}
function loadAppraisals(){
var el=document.getElementById('apprList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=staff_appraisal_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small py-3">No appraisals.</div>';return;}
var h='<table class="table tb"><thead><tr><th>Staff</th><th>Score</th><th>Rating</th><th>Date</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(a){h+='<tr><td>'+esc(a.full_name||'')+'</td><td>'+esc(a.performance_score)+'</td><td>'+esc(a.overall_rating||'')+'</td><td class="small">'+esc(a.review_date||'')+'</td><td><span class="badge bg-'+(a.status==='completed'?'success':a.status==='reviewed'?'info':'warning text-dark')+'">'+esc(a.status)+'</span></td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',loadAppraisals);
</script>
<?php endif; ?>

<?php if ($view === 'staff_development'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chalkboard-teacher me-2"></i>Staff Development</div><div class="scb">
<p class="text-muted small">Training, CPD, and professional development tracking.</p>
</div></div>
<?php endif; ?>
<?php if ($view === 'institutional_operations'): ?>
<div class="row g-3">
<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-flag me-2"></i>Strategic Plans</div><div class="scb p-0"><div id="stratPlanList"></div></div></div></div>
<div class="col-md-6"><div class="scard"><div class="sch"><i class="fas fa-tachometer-alt me-2"></i>Institutional KPIs</div><div class="scb p-0"><div id="kpiList"></div></div></div></div>
</div>
<script>
function loadStratPlans(){
var el=document.getElementById('stratPlanList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=strategic_plan_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No strategic plans.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Plan</th><th>Status</th><th>Initiatives</th></tr></thead><tbody>';
d.forEach(function(p){h+='<tr><td><strong>'+esc(p.plan_name)+'</strong></td><td><span class="badge bg-'+(p.status==='active'?'success':p.status==='completed'?'info':p.status==='cancelled'?'danger':'warning text-dark')+'">'+esc(p.status)+'</span></td><td>'+esc(p.initiatives||'0')+'</td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
function loadKPIs(){
var el=document.getElementById('kpiList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=kpi_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No KPIs.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>KPI</th><th>Target</th><th>Current</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(k){h+='<tr><td>'+esc(k.kpi_name)+'</td><td>'+esc(k.target_value)+'</td><td>'+esc(k.current_value)+'</td><td><span class="badge bg-'+(k.status==='on_track'?'success':k.status==='at_risk'?'warning':'danger')+'">'+esc(k.status)+'</span></td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',function(){loadStratPlans();loadKPIs();});
</script>
<?php endif; ?>

<?php if ($view === 'strategic_plans'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>New Strategic Plan</div><div class="scb">
<form onsubmit="event.preventDefault(); createStratPlan()">
<div class="mb-3"><label class="fl">Plan Name *</label><input type="text" id="spName" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Description</label><textarea id="spDesc" class="form-control env-field" rows="3"></textarea></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Start Date *</label><input type="date" id="spStart" class="form-control env-field" required></div><div class="col-6"><label class="fl">End Date</label><input type="date" id="spEnd" class="form-control env-field"></div></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Create Plan</button>
</form>
<div id="spMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Strategic Plans</div><div class="scb p-0"><div id="stratPlanFullList"></div></div></div>
</div>
</div>
<script>
function createStratPlan(){
var fd=new FormData();fd.append('plan_name',document.getElementById('spName').value);fd.append('description',document.getElementById('spDesc').value);fd.append('start_date',document.getElementById('spStart').value);fd.append('end_date',document.getElementById('spEnd').value);fd.append('csrf_token', window.CSRF_TOKEN);
fetch('school-principal.php?view=create_strategic_plan&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
document.getElementById('spMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Plan created.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
if(d.success){document.getElementById('spName').value='';document.getElementById('spDesc').value='';document.getElementById('spStart').value='';document.getElementById('spEnd').value='';loadStratFullList();}
}).catch(function(e){ console.warn('[ISNM]', e); });
}
function loadStratFullList(){document.getElementById('stratPlanFullList').innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=strategic_plan_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){document.getElementById('stratPlanFullList').innerHTML='<div class="text-muted small p-3">No plans.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Name</th><th>Period</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(p){h+='<tr><td><strong>'+esc(p.plan_name)+'</strong></td><td class="small">'+esc(p.start_date)+' - '+esc(p.end_date||'')+'</td><td><span class="badge bg-'+(p.status==='active'?'success':p.status==='completed'?'info':'warning text-dark')+'">'+esc(p.status)+'</span></td></tr>';});
h+='</tbody></table></div>';document.getElementById('stratPlanFullList').innerHTML=h;
}).catch(function(){document.getElementById('stratPlanFullList').innerHTML='<div class="text-danger small">Failed.</div>';});}
document.addEventListener('DOMContentLoaded',loadStratFullList);
</script>
<?php endif; ?>

<?php if ($view === 'institutional_kpis'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Add KPI</div><div class="scb">
<form onsubmit="event.preventDefault(); addKPI()">
<div class="mb-3"><label class="fl">KPI Name *</label><input type="text" id="kpiName" class="form-control env-field" required></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Category</label><select id="kpiCat" class="form-select env-field"><option>Academic</option><option>Financial</option><option>Operational</option><option>Staff</option><option>Student</option></select></div><div class="col-6"><label class="fl">Period</label><input type="text" id="kpiPeriod" class="form-control env-field" value="<?= date('Y-m') ?>"></div></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Target</label><input type="number" step="0.01" id="kpiTarget" class="form-control env-field"></div><div class="col-6"><label class="fl">Current</label><input type="number" step="0.01" id="kpiCurrent" class="form-control env-field"></div></div>
<div class="mb-3"><label class="fl">Status</label><select id="kpiStatus" class="form-select env-field"><option value="on_track">On Track</option><option value="at_risk">At Risk</option><option value="behind">Behind</option></select></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Add KPI</button>
</form>
<div id="kpiMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>All KPIs</div><div class="scb p-0"><div id="kpiFullList"></div></div></div>
</div>
</div>
<script>
function addKPI(){
var fd=new FormData();fd.append('kpi_name',document.getElementById('kpiName').value);fd.append('kpi_category',document.getElementById('kpiCat').value);fd.append('target_value',document.getElementById('kpiTarget').value);fd.append('current_value',document.getElementById('kpiCurrent').value);fd.append('period',document.getElementById('kpiPeriod').value);fd.append('status',document.getElementById('kpiStatus').value);fd.append('csrf_token', window.CSRF_TOKEN);
fetch('school-principal.php?view=update_kpi&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
document.getElementById('kpiMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">KPI added.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
if(d.success){document.getElementById('kpiName').value='';document.getElementById('kpiTarget').value='';document.getElementById('kpiCurrent').value='';loadKPIFull();}
}).catch(function(e){ console.warn('[ISNM]', e); });
}
function loadKPIFull(){var el=document.getElementById('kpiFullList');if(!el)return;el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=kpi_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No KPIs.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>KPI</th><th>Category</th><th>Target</th><th>Current</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(k){h+='<tr><td>'+esc(k.kpi_name)+'</td><td>'+esc(k.kpi_category||'')+'</td><td>'+esc(k.target_value)+'</td><td>'+esc(k.current_value)+'</td><td><span class="badge bg-'+(k.status==='on_track'?'success':k.status==='at_risk'?'warning':'danger')+'">'+esc(k.status)+'</span></td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});}
document.addEventListener('DOMContentLoaded',loadKPIFull);
</script>
<?php endif; ?>

<?php if ($view === 'quality_assurance'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>New QA Review</div><div class="scb">
<form onsubmit="event.preventDefault(); createQAReview()">
<div class="mb-3"><label class="fl">Review Title *</label><input type="text" id="qaTitle" class="form-control env-field" required></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Type</label><select id="qaType" class="form-select env-field"><option>Internal</option><option>External</option><option>Peer</option><option>Accreditation</option></select></div><div class="col-6"><label class="fl">Department</label><input type="text" id="qaDept" class="form-control env-field"></div></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Score</label><input type="number" step="0.01" id="qaScore" class="form-control env-field"></div><div class="col-6"><label class="fl">Reviewer</label><input type="text" id="qaReviewer" class="form-control env-field" value="<?= htmlspecialchars($uname) ?>"></div></div>
<div class="mb-3"><label class="fl">Findings</label><textarea id="qaFindings" class="form-control env-field" rows="3"></textarea></div>
<div class="mb-3"><label class="fl">Recommendations</label><textarea id="qaRecs" class="form-control env-field" rows="3"></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Save Review</button>
</form>
<div id="qaMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>QA Reviews</div><div class="scb p-0"><div id="qaFullList"></div></div></div>
</div>
</div>
<script>
function createQAReview(){
var fd=new FormData();fd.append('review_title',document.getElementById('qaTitle').value);fd.append('review_type',document.getElementById('qaType').value);fd.append('department',document.getElementById('qaDept').value);fd.append('score',document.getElementById('qaScore').value);fd.append('reviewer',document.getElementById('qaReviewer').value);fd.append('findings',document.getElementById('qaFindings').value);fd.append('recommendations',document.getElementById('qaRecs').value);fd.append('csrf_token', window.CSRF_TOKEN);
fetch('school-principal.php?view=create_qa_review&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
document.getElementById('qaMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Review saved.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
if(d.success){document.getElementById('qaTitle').value='';document.getElementById('qaScore').value='';document.getElementById('qaFindings').value='';document.getElementById('qaRecs').value='';loadQAFull();}
}).catch(function(e){ console.warn('[ISNM]', e); });
}
function loadQAFull(){var el=document.getElementById('qaFullList');if(!el)return;el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=quality_assurance_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No QA reviews.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Type</th><th>Dept</th><th>Score</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(q){h+='<tr><td><strong>'+esc(q.review_title)+'</strong></td><td>'+esc(q.review_type||'')+'</td><td>'+esc(q.department||'')+'</td><td>'+esc(q.score||'0.00')+'</td><td><span class="badge bg-'+(q.status==='reviewed'?'success':q.status==='completed'?'info':'warning text-dark')+'">'+esc(q.status)+'</span></td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});}
document.addEventListener('DOMContentLoaded',loadQAFull);
</script>
<?php endif; ?>

<?php if ($view === 'accreditation_readiness'): ?>
<div class="scard"><div class="sch"><i class="fas fa-certificate me-2"></i>Accreditation Readiness</div><div class="scb">
<div class="row g-3">
<div class="col-md-4"><div class="border rounded p-3 text-center"><h4 class="text-success">92%</h4><small class="text-muted">UNMEB Readiness</small><div class="progress mt-2" style="height:6px"><div class="progress-bar bg-success" style="width:92%"></div></div></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><h4 class="text-info">85%</h4><small class="text-muted">NCHE Readiness</small><div class="progress mt-2" style="height:6px"><div class="progress-bar bg-info" style="width:85%"></div></div></div></div>
<div class="col-md-4"><div class="border rounded p-3 text-center"><h4 class="text-warning">78%</h4><small class="text-muted">Curriculum Compliance</small><div class="progress mt-2" style="height:6px"><div class="progress-bar bg-warning" style="width:78%"></div></div></div></div>
</div>
</div></div>
<?php endif; ?>

<?php if ($view === 'compliance_monitoring'): ?>
<div class="scard"><div class="sch"><i class="fas fa-clipboard-check me-2"></i>Compliance Monitoring</div><div class="scb">
<p class="text-muted small">Track institutional compliance with regulatory requirements.</p>
<div class="row g-3">
<div class="col-md-4"><div class="approval-card"><div class="ac-title">Staff Licensing</div><div class="small text-muted">Compliance: <span class="text-success">95%</span></div></div></div>
<div class="col-md-4"><div class="approval-card"><div class="ac-title">Clinical Agreements</div><div class="small text-muted">Compliance: <span class="text-success">100%</span></div></div></div>
<div class="col-md-4"><div class="approval-card"><div class="ac-title">Student Records</div><div class="small text-muted">Compliance: <span class="text-warning">88%</span></div></div></div>
</div>
</div></div>
<?php endif; ?>
<?php if ($view === 'approval_center'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-double me-2"></i>Approval Center</div><div class="scb">
<div class="mb-3"><div class="btn-group"><button class="btn btn-sec btn-sm" onclick="loadApprovals('all')">All</button><button class="btn btn-outline-sec btn-sm" onclick="loadApprovals('grade_approval')">Grade Approvals</button><button class="btn btn-outline-sec btn-sm" onclick="loadApprovals('appeal')">Appeals</button></div></div>
<div id="approvalCenterList"></div>
</div></div>
<script>
function loadApprovals(filter){
var el=document.getElementById('approvalCenterList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=approval_list&ajax=1').then(function(r){return r.json()}).then(function(d){
var items=d||[]; if(filter!=='all') items=items.filter(function(x){return x.source===filter;});
if(!items.length){el.innerHTML='<div class="text-muted small py-3">No pending approvals.</div>';return;}
var h='';
items.forEach(function(a){var srcLabel=a.source==='grade_approval'?'Grade Approval':'Appeal';
h+='<div class="approval-card"><div class="d-flex justify-content-between"><div><div class="ac-title">'+esc(a.title)+'</div><div class="ac-meta">'+srcLabel+' | '+esc(a.created_at)+'</div></div><span class="badge bg-warning text-dark">Pending</span></div>';
if(a.description) h+='<div class="small text-muted mb-2">'+esc(a.description)+'</div>';
h+='<div class="ac-actions"><button class="btn btn-success btn-sm" onclick="processApproval('+a.id+',\''+a.source+'\',\'approve\')"><i class="fas fa-check me-1"></i>Approve</button><button class="btn btn-danger btn-sm" onclick="processApproval('+a.id+',\''+a.source+'\',\'reject\')"><i class="fas fa-times me-1"></i>Reject</button><button class="btn btn-warning btn-sm" onclick="processApproval('+a.id+',\''+a.source+'\',\'return\')"><i class="fas fa-undo me-1"></i>Return</button><button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none" onclick="processApproval('+a.id+',\''+a.source+'\',\'escalate\')"><i class="fas fa-arrow-up me-1"></i>DG</button></div></div>';});
el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
document.addEventListener('DOMContentLoaded',function(){loadApprovals('all');});
</script>
<?php endif; ?>

<?php if ($view === 'meetings'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-plus-circle me-2"></i>Schedule Meeting</div><div class="scb">
<form onsubmit="event.preventDefault(); createPrincipalMeeting()">
<div class="mb-3"><label class="fl">Title *</label><input type="text" id="pmtTitle" class="form-control env-field" required></div>
<div class="row g-2 mb-3"><div class="col-4"><label class="fl">Type</label><select id="pmtType" class="form-select env-field"><option>Executive</option><option>Academic Board</option><option>Committee</option><option>Staff</option><option>Department</option></select></div><div class="col-4"><label class="fl">Date *</label><input type="date" id="pmtDate" class="form-control env-field" required></div><div class="col-4"><label class="fl">Location</label><input type="text" id="pmtLoc" class="form-control env-field"></div></div>
<div class="row g-2 mb-3"><div class="col-6"><label class="fl">Start</label><input type="time" id="pmtStart" class="form-control env-field"></div><div class="col-6"><label class="fl">End</label><input type="time" id="pmtEnd" class="form-control env-field"></div></div>
<div class="mb-3"><label class="fl">Agenda</label><textarea id="pmtAgenda" class="form-control env-field" rows="3"></textarea></div>
<div class="mb-3"><label class="fl">Attendees (one per line)</label><textarea id="pmtAttendees" class="form-control env-field" rows="2"></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-save me-1"></i>Schedule</button>
</form>
<div id="pmtMsg" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Meetings</div><div class="scb p-0"><div id="pmtList"></div></div></div>
</div>
</div>
<script>
function createPrincipalMeeting(){
var fd=new FormData();fd.append('title',document.getElementById('pmtTitle').value);fd.append('meeting_type',document.getElementById('pmtType').value);fd.append('meeting_date',document.getElementById('pmtDate').value);fd.append('start_time',document.getElementById('pmtStart').value);fd.append('end_time',document.getElementById('pmtEnd').value);fd.append('location',document.getElementById('pmtLoc').value);fd.append('agenda',document.getElementById('pmtAgenda').value);fd.append('attendees',document.getElementById('pmtAttendees').value);fd.append('csrf_token', window.CSRF_TOKEN);
fetch('school-principal.php?view=schedule_meeting&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
document.getElementById('pmtMsg').innerHTML=d.success?'<div class="alert alert-success py-1 small">Meeting scheduled.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
if(d.success){document.getElementById('pmtTitle').value='';document.getElementById('pmtDate').value='';document.getElementById('pmtStart').value='';document.getElementById('pmtEnd').value='';document.getElementById('pmtLoc').value='';document.getElementById('pmtAgenda').value='';document.getElementById('pmtAttendees').value='';loadMeetings();}
}).catch(function(e){ console.warn('[ISNM]', e); });
}
function loadMeetings(){
var el=document.getElementById('pmtList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=meeting_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No meetings.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead><tbody>';
d.forEach(function(m){var stCls=m.status==='completed'?'success':m.status==='ongoing'?'info':m.status==='cancelled'?'danger':'warning text-dark';
h+='<tr><td><strong>'+esc(m.title)+'</strong><br><small class="text-muted">'+esc(m.meeting_type||'')+'</small></td><td class="small">'+esc(m.meeting_date)+'</td><td class="small">'+esc(m.start_time||'--')+'</td><td><span class="badge bg-'+stCls+'">'+esc(m.status)+'</span></td><td><button class="btn btn-sm btn-outline-primary" onclick="viewMeeting('+m.id+')"><i class="fas fa-eye"></i></button> <button class="btn btn-sm btn-outline-success" onclick="minutesPrompt('+m.id+')"><i class="fas fa-pen"></i></button></td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed.</div>';});
}
function viewMeeting(id){
fetch('school-secretary.php?view=meeting_get&ajax=1&id='+id).then(function(r){return r.json()}).then(function(d){
if(!d||!d.meeting){alert('Not found');return;}var m=d.meeting;
alert('Title: '+m.title+'\nDate: '+m.meeting_date+'\nTime: '+(m.start_time||'--')+' - '+(m.end_time||'--')+'\nLocation: '+(m.location||'--')+'\nType: '+(m.meeting_type||'')+'\nStatus: '+m.status+'\n\nAgenda:\n'+(m.agenda||'N/A'));
}).catch(function(){alert('Failed.');});
}
function minutesPrompt(mid){var agenda=prompt('Agenda item:');if(agenda===null)return;var disc=prompt('Discussion:');var res=prompt('Resolution:');var act=prompt('Action items:');var fd=new FormData();fd.append('meeting_id',mid);fd.append('agenda_item',agenda||'');fd.append('discussion',disc||'');fd.append('resolution',res||'');fd.append('action_items',act||'');fd.append('csrf_token', window.CSRF_TOKEN);fetch('school-principal.php?view=save_meeting_minutes&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(d.success)loadMeetings();}).catch(function(e){ console.warn('[ISNM]', e); });}
document.addEventListener('DOMContentLoaded',loadMeetings);
</script>
<?php endif; ?>

<?php if ($view === 'executive_meetings'): ?>
<div class="scard"><div class="sch"><i class="fas fa-briefcase me-2"></i>Executive Meetings</div><div class="scb p-0"><div id="execMtList"></div></div></div>
<script>
function loadExecMeetings(){var el=document.getElementById('execMtList');if(!el)return;el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=meeting_data&ajax=1').then(function(r){return r.json()}).then(function(d){
var f=(d||[]).filter(function(m){return m.meeting_type==='Executive';});
if(!f.length){el.innerHTML='<div class="text-muted small p-3">No executive meetings.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>';
f.forEach(function(m){h+='<tr><td><strong>'+esc(m.title)+'</strong></td><td class="small">'+esc(m.meeting_date)+'</td><td class="small">'+esc(m.start_time||'--')+'</td><td><span class="badge bg-'+(m.status==='completed'?'success':m.status==='cancelled'?'danger':'warning text-dark')+'">'+esc(m.status)+'</span></td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});}
document.addEventListener('DOMContentLoaded',loadExecMeetings);
</script>
<?php endif; ?>

<?php if ($view === 'academic_board'): ?>
<div class="scard"><div class="sch"><i class="fas fa-graduation-cap me-2"></i>Academic Board Meetings</div><div class="scb p-0"><div id="acadBoardList"></div></div></div>
<script>
function loadAcadBoard(){var el=document.getElementById('acadBoardList');if(!el)return;el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=meeting_data&ajax=1').then(function(r){return r.json()}).then(function(d){
var f=(d||[]).filter(function(m){return m.meeting_type==='Academic Board';});
if(!f.length){el.innerHTML='<div class="text-muted small p-3">No academic board meetings.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Date</th><th>Status</th></tr></thead><tbody>';
f.forEach(function(m){h+='<tr><td><strong>'+esc(m.title)+'</strong></td><td class="small">'+esc(m.meeting_date)+'</td><td><span class="badge bg-'+(m.status==='completed'?'success':'warning text-dark')+'">'+esc(m.status)+'</span></td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});}
document.addEventListener('DOMContentLoaded',loadAcadBoard);
</script>
<?php endif; ?>

<?php if ($view === 'committee_meetings'): ?>
<div class="scard"><div class="sch"><i class="fas fa-users me-2"></i>Committee Meetings</div><div class="scb p-0"><div id="commMtList"></div></div></div>
<script>
function loadCommMts(){var el=document.getElementById('commMtList');if(!el)return;el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=meeting_data&ajax=1').then(function(r){return r.json()}).then(function(d){
var f=(d||[]).filter(function(m){return m.meeting_type==='Committee';});
if(!f.length){el.innerHTML='<div class="text-muted small p-3">No committee meetings.</div>';return;}
var h='<div class="table-responsive"><table class="table tb"><thead><tr><th>Title</th><th>Date</th><th>Status</th></tr></thead><tbody>';
f.forEach(function(m){h+='<tr><td><strong>'+esc(m.title)+'</strong></td><td class="small">'+esc(m.meeting_date)+'</td><td><span class="badge bg-'+(m.status==='completed'?'success':'warning text-dark')+'">'+esc(m.status)+'</span></td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});}
document.addEventListener('DOMContentLoaded',loadCommMts);
</script>
<?php endif; ?>

<?php if ($view === 'action_tracking'): ?>
<div class="scard"><div class="sch"><i class="fas fa-tasks me-2"></i>Action Tracking</div><div class="scb">
<div id="actionTrackList" class="table-responsive"></div>
</div></div>
<script>
function loadActions(){
var el=document.getElementById('actionTrackList');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=meeting_action_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small py-3">No actions.</div>';return;}
var h='<table class="table tb"><thead><tr><th>Meeting</th><th>Action</th><th>Responsible</th><th>Due</th><th>Status</th><th></th></tr></thead><tbody>';
d.forEach(function(a){h+='<tr><td class="small">'+esc(a.meeting_title||'')+'</td><td>'+esc(mbSubstr(a.action||'',60))+'</td><td>'+esc(a.responsible||'')+'</td><td class="small">'+esc(a.due_date||'')+'</td><td><span class="badge bg-'+(a.status==='completed'?'success':a.status==='in_progress'?'info':'warning text-dark')+'">'+esc(a.status)+'</span></td><td><select class="form-select form-select-sm" style="width:auto" onchange="updateAction('+a.id+',this.value)"><option value="">Change</option><option value="in_progress">In Progress</option><option value="completed">Complete</option></select></td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
function updateAction(id,st){if(!st)return;var fd=new FormData();fd.append('id',id);fd.append('status',st);fd.append('csrf_token', window.CSRF_TOKEN);fetch('school-principal.php?view=update_action_status&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){if(d.success)loadActions();}).catch(function(e){ console.warn('[ISNM]', e); });}
document.addEventListener('DOMContentLoaded',loadActions);
</script>
<?php endif; ?>
<?php if ($view === 'academic_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-chart-bar me-2"></i>Academic Reports</div><div class="scb">
<div class="row g-2 mb-3">
<div class="col-md-4"><input type="date" id="acadFrom" class="form-control env-field" value="<?= date('Y-m-01') ?>"></div>
<div class="col-md-4"><input type="date" id="acadTo" class="form-control env-field" value="<?= date('Y-m-d') ?>"></div>
<div class="col-md-4"><button class="btn btn-sec w-100" onclick="genAcadReport()"><i class="fas fa-search me-1"></i>Generate</button></div>
</div>
<div id="acadReportOut"></div>
</div></div>
<script>
function genAcadReport(){
var el=document.getElementById('acadReportOut');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
Promise.all([
fetch('school-principal.php?view=program_performance_data&ajax=1').then(function(r){return r.json()}),
fetch('school-principal.php?view=exam_monitoring_data&ajax=1').then(function(r){return r.json()})
]).then(function(res){
var progs=res[0]||[]; var exams=res[1]||[]; var passed=exams.filter(function(e){return e.grade&&['A','B','C','D'].indexOf(e.grade)>=0;});
var h='<h6 class="fw-bold">Academic Summary</h6><div class="row g-2 mb-3">';
h+='<div class="col-md-3"><div class="border rounded p-2 text-center"><div class="fw-bold">'+progs.length+'</div><small>Programs</small></div></div>';
h+='<div class="col-md-3"><div class="border rounded p-2 text-center"><div class="fw-bold">'+exams.length+'</div><small>Exam Records</small></div></div>';
h+='<div class="col-md-3"><div class="border rounded p-2 text-center"><div class="fw-bold">'+passed.length+'</div><small>Passed</small></div></div>';
h+='<div class="col-md-3"><div class="border rounded p-2 text-center"><div class="fw-bold">'+(exams.length>0?Math.round(passed.length/exams.length*100)+'%':'0%')+'</div><small>Pass Rate</small></div></div></div>';
if(progs.length){h+='<h6 class="fw-bold">Program Performance</h6><div class="table-responsive"><table class="table tb"><thead><tr><th>Program</th><th>Enrolled</th><th>Avg GPA</th></tr></thead><tbody>';
progs.forEach(function(p){h+='<tr><td>'+esc(p.program)+'</td><td>'+esc(p.enrolled)+'</td><td>'+esc(p.avg_gpa||'0.00')+'</td></tr>';});
h+='</tbody></table></div>';}
el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
}
</script>
<?php endif; ?>

<?php if ($view === 'student_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-user-graduate me-2"></i>Student Reports</div><div class="scb">
<div id="studentReportOut"></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
var el=document.getElementById('studentReportOut');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=student_progress_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small py-3">No students.</div>';return;}
var active=d.filter(function(s){return s.status==='Active';}); var total=d.length;
var h='<div class="row g-2 mb-3"><div class="col-md-4"><div class="border rounded p-2 text-center"><div class="fw-bold">'+total+'</div><small>Total</small></div></div>';
h+='<div class="col-md-4"><div class="border rounded p-2 text-center"><div class="fw-bold">'+active.length+'</div><small>Active</small></div></div>';
h+='<div class="col-md-4"><div class="border rounded p-2 text-center"><div class="fw-bold">'+(total-active.length)+'</div><small>Inactive/Graduated</small></div></div></div>';
h+='<div class="table-responsive"><table class="table tb"><thead><tr><th>Name</th><th>Program</th><th>Level</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(s){h+='<tr><td>'+esc(s.surname)+', '+esc(s.first_name)+'</td><td>'+esc(s.program)+'</td><td>'+esc(s.level)+'</td><td><span class="badge bg-'+(s.status==='Active'?'success':'secondary')+'">'+esc(s.status)+'</span></td></tr>';});
h+='</tbody></table></div>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'institutional_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-building me-2"></i>Institutional Reports</div><div class="scb">
<div id="instReportOut"></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
var el=document.getElementById('instReportOut');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=principal_stats&ajax=1').then(function(r){return r.json()}).then(function(d){
var h='<div class="row g-2 mb-3"><div class="col-md-3"><div class="border rounded p-2 text-center"><div class="fw-bold">'+d.total_students+'</div><small>Students</small></div></div>';
h+='<div class="col-md-3"><div class="border rounded p-2 text-center"><div class="fw-bold">'+d.total_staff+'</div><small>Staff</small></div></div>';
h+='<div class="col-md-3"><div class="border rounded p-2 text-center"><div class="fw-bold">'+d.attendance_rate+'%</div><small>Attendance</small></div></div>';
h+='<div class="col-md-3"><div class="border rounded p-2 text-center"><div class="fw-bold">'+d.pass_rate+'%</div><small>Pass Rate</small></div></div></div>';
h+='<div class="row g-2"><div class="col-md-4"><div class="border rounded p-2 text-center"><div class="fw-bold text-'+(d.welfare_alerts>0?'danger':'success')+'">'+d.welfare_alerts+'</div><small>Welfare Alerts</small></div></div>';
h+='<div class="col-md-4"><div class="border rounded p-2 text-center"><div class="fw-bold text-warning">'+d.pending_approvals+'</div><small>Pending Approvals</small></div></div>';
h+='<div class="col-md-4"><div class="border rounded p-2 text-center"><div class="fw-bold text-info">'+d.upcoming_meetings+'</div><small>Upcoming Meetings</small></div></div></div>';
h+='<div class="mt-3"><div class="border rounded p-3 text-center"><h4 class="fw-bold">Health Score: <span class="text-'+(d.health_score>=70?'success':d.health_score>=40?'warning':'danger')+'">'+d.health_score+'/100</span></h4></div></div>';
el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'qa_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-check-circle me-2"></i>QA Reports</div><div class="scb">
<div id="qaReportOut" class="table-responsive"></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
var el=document.getElementById('qaReportOut');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=quality_assurance_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small py-3">No QA data.</div>';return;}
var h='<table class="table tb"><thead><tr><th>Title</th><th>Type</th><th>Dept</th><th>Score</th><th>Status</th></tr></thead><tbody>';
d.forEach(function(q){h+='<tr><td>'+esc(q.review_title)+'</td><td>'+esc(q.review_type||'')+'</td><td>'+esc(q.department||'')+'</td><td>'+esc(q.score||'0.00')+'</td><td><span class="badge bg-'+(q.status==='reviewed'?'success':q.status==='completed'?'info':'warning text-dark')+'">'+esc(q.status)+'</span></td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
});
</script>
<?php endif; ?>

<?php if ($view === 'department_reports'): ?>
<div class="scard"><div class="sch"><i class="fas fa-building me-2"></i>Department Reports</div><div class="scb">
<div id="deptReportOut" class="table-responsive"></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
var el=document.getElementById('deptReportOut');if(!el)return;
el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=department_performance_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small py-3">No department data.</div>';return;}
var h='<table class="table tb"><thead><tr><th>Department</th><th>Metric</th><th>Value</th><th>Period</th></tr></thead><tbody>';
d.forEach(function(p){h+='<tr><td><strong>'+esc(p.department)+'</strong></td><td>'+esc(p.metric)+'</td><td>'+esc(p.value)+'</td><td class="small">'+esc(p.period||'')+'</td></tr>';});
h+='</tbody></table>';el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small">Failed.</div>';});
});
</script>
<?php endif; ?>
<?php if ($view === 'communications'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-paper-plane me-2"></i>Send Communication</div><div class="scb">
<form onsubmit="event.preventDefault(); sendPrincipalComm()">
<div class="mb-3"><label class="fl">Recipient Role</label><select id="commRole" class="form-select env-field"><option value="staff">All Staff</option><option value="director">Directors</option><option value="hods">HODs</option><option value="lecturers">Lecturers</option><option value="students">All Students</option></select></div>
<div class="mb-3"><label class="fl">Subject *</label><input type="text" id="commSubj" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Message *</label><textarea id="commMsg" class="form-control env-field" rows="4" required></textarea></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-paper-plane me-1"></i>Send</button>
</form>
<div id="commResult" class="mt-2"></div>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-inbox me-2"></i>Sent Communications</div><div class="scb p-0"><div id="commSentList"></div></div></div>
</div>
</div>
<script>
function sendPrincipalComm(){
var fd=new FormData();fd.append('recipient_role',document.getElementById('commRole').value);fd.append('subject',document.getElementById('commSubj').value);fd.append('message',document.getElementById('commMsg').value);fd.append('csrf_token', window.CSRF_TOKEN);
fetch('school-principal.php?view=send_communication&ajax=1',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
document.getElementById('commResult').innerHTML=d.success?'<div class="alert alert-success py-1 small">Sent.</div>':'<div class="alert alert-danger py-1 small">'+(d.error||'Failed')+'</div>';
if(d.success){document.getElementById('commSubj').value='';document.getElementById('commMsg').value='';loadCommSent();}
}).catch(function(e){ console.warn('[ISNM]', e); });
}
function loadCommSent(){var el=document.getElementById('commSentList');if(!el)return;el.innerHTML='<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>';
fetch('school-principal.php?view=communication_data&ajax=1').then(function(r){return r.json()}).then(function(d){
if(!d||!d.length){el.innerHTML='<div class="text-muted small p-3">No communications sent.</div>';return;}
var h='';d.forEach(function(c){h+='<div class="act-item"><div class="fw-bold small">'+esc(c.subject)+'</div><div class="text-muted small">'+esc(c.recipient_role)+' &middot; '+esc(c.created_at)+'</div><div class="small mt-1">'+esc(c.message)+'</div></div>';});
el.innerHTML=h;
}).catch(function(){el.innerHTML='<div class="text-danger small p-3">Failed to load.</div>';});}
document.addEventListener('DOMContentLoaded',loadCommSent);
</script>
<?php endif; ?>

<?php if ($view === 'notices'): ?>
<div class="row g-3">
<div class="col-md-5">
<div class="scard"><div class="sch"><i class="fas fa-bullhorn me-2"></i>Publish Notice</div><div class="scb">
<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="action" value="publish_notice">
<div class="mb-3"><label class="fl">Title *</label><input type="text" name="notice_title" class="form-control env-field" required></div>
<div class="mb-3"><label class="fl">Content *</label><textarea name="notice_content" class="form-control env-field" rows="4" required></textarea></div>
<div class="mb-3"><label class="fl">Audience</label><select name="notice_audience" class="form-select env-field"><option value="All">All</option><option value="Staff">Staff</option><option value="Students">Students</option><option value="Faculty">Faculty</option></select></div>
<button type="submit" class="btn btn-sec"><i class="fas fa-paper-plane me-1"></i>Publish</button>
</form>
</div></div>
</div>
<div class="col-md-7">
<div class="scard"><div class="sch"><i class="fas fa-list me-2"></i>Published Notices</div><div class="scb p-0">
<?php
try { if ($students) { $r = $students->query("SELECT * FROM {$students_db}.principal_notices ORDER BY created_at DESC LIMIT 20"); if ($r) while ($n = $r->fetch_assoc()) { echo '<div class="act-item"><div class="fw-bold small">'.htmlspecialchars($n['title']).'</div><div class="time">'.htmlspecialchars($n['audience']??'All').' &middot; '.htmlspecialchars($n['created_at']).'</div></div>'; } } } catch (Exception $e) { error_log('school-principal context: ' . $e->getMessage()); }
?>
</div></div>
</div>
</div>
<?php endif; ?>

<?php if ($view === 'messages'): ?>
<div class="scard"><div class="sch"><i class="fas fa-comments me-2"></i>Messages</div><div class="scb">
<p class="text-muted small">Messages sent from the communications dashboard.</p>
</div></div>
<?php endif; ?>

<?php if ($view === 'meeting_invitations'): ?>
<div class="scard"><div class="sch"><i class="fas fa-envelope-open-text me-2"></i>Meeting Invitations</div><div class="scb">
<p class="text-muted small">Invitations are managed through the Meetings section.</p>
</div></div>
<?php endif; ?>

<?php if ($view === 'announcements'): ?>
<div class="scard"><div class="sch"><i class="fas fa-bullhorn me-2"></i>Announcements</div><div class="scb p-0">
<?php
try { if ($students) { $r = $students->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 20"); if ($r) while ($a = $r->fetch_assoc()) { echo '<div class="act-item"><div class="fw-bold">'.htmlspecialchars($a['title']).'</div><div class="small text-muted">'.htmlspecialchars(mb_substr($a['body']??'',0,200)).'</div><div class="time">'.htmlspecialchars($a['created_at']).'</div></div>'; } } } catch (Exception $e) { error_log('school-principal context: ' . $e->getMessage()); }
?>
</div></div>
<?php endif; ?>

<?php if ($view === 'staff'): ?>
<div class="row g-3">
<div class="col-12">
<div class="scard"><div class="sch"><i class="fas fa-users me-2"></i>Staff Oversight</div><div class="scb">
<?php
$ts = 0; $as = 0; $ol = 0; $pa = 0;
$staffList = [];
if ($staff) {
    $r = $staff->query("SELECT COUNT(*) c FROM staff"); if ($r) $ts = (int)$r->fetch_assoc()['c'];
    $r = $staff->query("SELECT COUNT(*) c FROM staff WHERE status='Active'"); if ($r) $as = (int)$r->fetch_assoc()['c'];
    $r = $staff->query("SELECT COUNT(*) c FROM staff WHERE status='On Leave'"); if ($r) $ol = (int)$r->fetch_assoc()['c'];
    $r = $staff->query("SELECT COUNT(*) c FROM {$staff_db}.staff_appraisals WHERE status='draft' OR status='submitted'"); if ($r) $pa = (int)$r->fetch_assoc()['c'];
    $s = $staff->query("SELECT id, full_name, email, phone, department, position, status FROM staff ORDER BY full_name");
    if ($s) $staffList = $s->fetch_all(MYSQLI_ASSOC);
}
?>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="border rounded p-3 text-center"><h4 class="text-primary mb-1"><?= number_format($ts) ?></h4><small class="text-muted">Total Staff</small></div></div>
    <div class="col-md-3"><div class="border rounded p-3 text-center"><h4 class="text-success mb-1"><?= number_format($as) ?></h4><small class="text-muted">Active</small></div></div>
    <div class="col-md-3"><div class="border rounded p-3 text-center"><h4 class="text-info mb-1"><?= number_format($ol) ?></h4><small class="text-muted">On Leave</small></div></div>
    <div class="col-md-3"><div class="border rounded p-3 text-center"><h4 class="text-warning mb-1"><?= number_format($pa) ?></h4><small class="text-muted">Pending Appraisals</small></div></div>
</div>
<?php if (empty($staffList)): ?>
<div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No staff records found.</p></div>
<?php else: ?>
<div class="table-responsive">
<table class="table tb">
<thead><tr><th>Full Name</th><th>Email</th><th>Phone</th><th>Department</th><th>Position</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($staffList as $s): ?>
<tr>
    <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
    <td><?= htmlspecialchars($s['email'] ?? '-') ?></td>
    <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
    <td><?= htmlspecialchars($s['department'] ?? '-') ?></td>
    <td><?= htmlspecialchars($s['position'] ?? '-') ?></td>
    <td><span class="badge bg-<?= ($s['status'] ?? 'Active') === 'Active' ? 'success' : ($s['status'] === 'On Leave' ? 'info' : 'secondary') ?>"><?= htmlspecialchars($s['status'] ?? 'Active') ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div></div>
</div>
</div>
<?php endif; ?>

</div>

<!-- â•â•â• AJAX MODULE LOADING â•â•â• -->
<div id="ajaxLoadingOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="text-align:center;padding:30px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);">
    <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
    <p style="margin:12px 0 0;font-size:13px;color:#64748b;">Loading module...</p>
  </div>
</div>
<script>
(function(){
    var contentArea = document.querySelector('.prin-content');
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
            fetch('school-principal.php?section=' + encodeURIComponent(section), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.querySelector('.prin-content');
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
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
function esc(s){ if(!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function mbSubstr(s,n){ if(!s) return ''; return s.length>n?s.substring(0,n)+'...':s; }
function openProfileModal(){var m=document.getElementById('profileModal');if(m){var bsModal=new bootstrap.Modal(m);bsModal.show();}}
</script>

<?php
require_once __DIR__ . '/../includes/profile_settings.php';
if (function_exists('renderProfileModal')) renderProfileModal();
if (function_exists('renderProfileStyles')) renderProfileStyles();
if (function_exists('renderProfileScripts')) renderProfileScripts();
?>
</body></html>
