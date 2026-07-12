<?php
/**
 * ISNM Academic Transcript Portal
 * Full-featured transcript viewer with download, print, and editing
 * Access: students (own), directors (view all), registrar (view/edit all)
 */
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/certificate_generator.php';
require_once __DIR__ . '/includes/financial_functions.php';
require_once __DIR__ . '/includes/document_settings.php';

// ---------------------------------------------------------------------------
// Auth
// ---------------------------------------------------------------------------
$is_student = !empty($_SESSION['logged_in']) && ($_SESSION['type'] ?? '') === 'student';
$is_staff   = !empty($_SESSION['user_id']);
$user_id    = (int)($_SESSION['user_id'] ?? $_SESSION['student_id'] ?? 0);

// Role detection for staff
$role = '';
if ($is_staff && function_exists('getStaffConnection')) {
    $sdb = getStaffConnection();
    if ($sdb) {
        $r = $sdb->query("SELECT role FROM staff WHERE id = $user_id LIMIT 1");
        if ($r) { $row = $r->fetch_assoc(); $role = $row['role'] ?? ''; }
    }
}
$is_registrar = stripos($role, 'registrar') !== false;
$is_director  = stripos($role, 'director') !== false || stripos($role, 'director general') !== false;
$can_manage_settings = $is_registrar || $is_director;

// Load document settings
$docSettings = loadDocumentSettings();

// DB connections
$studentsDb = null;
if (function_exists('getStudentsConnection')) {
    $studentsDb = getStudentsConnection();
}
if (!$studentsDb) {
    try {
        $studentsDb = @new mysqli(STUDENTS_DB_HOST, STUDENTS_DB_USER, STUDENTS_DB_PASS, STUDENTS_DB_NAME, STUDENTS_DB_PORT);
    } catch (Exception $e) { error_log('print_transcript context: ' . $e->getMessage()); }
}

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------
$action    = $_GET['action'] ?? '';
$studentId = $_GET['student_id'] ?? $_GET['id'] ?? null;

// Student auto-load: use session
if (!$studentId && $is_student && !empty($_SESSION['user_id'])) {
    $studentId = (int)$_SESSION['user_id'];
}

// AJAX student lookup for staff
if ($action === 'lookup' && $is_staff && $studentsDb && isset($_GET['q'])) {
    header('Content-Type: application/json');
    $q = '%' . $_GET['q'] . '%';
    $stmt = $studentsDb->prepare("SELECT id, first_name, surname, other_name, full_name, student_number, registration_number, program, course FROM students WHERE full_name LIKE ? OR student_number LIKE ? OR registration_number LIKE ? LIMIT 20");
    if ($stmt) {
        $stmt->bind_param("sss", $q, $q, $q);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $res = $stmt->get_result();
        $out = [];
        if ($res) while ($row = $res->fetch_assoc()) {
            $out[] = [
                'id' => $row['id'],
                'full_name' => $row['full_name'] ?: trim(($row['first_name']??'') . ' ' . ($row['surname']??'') . ($row['other_name'] ? ' ' . $row['other_name'] : '')),
                'student_number' => $row['student_number'] ?? '',
                'registration_number' => $row['registration_number'] ?? '',
                'program' => $row['program'] ?: $row['course'] ?: '',
            ];
        }
        $stmt->close();
    }
    echo json_encode($out ?? []);
    exit;
}

// Download action: serve HTML as file download
if ($action === 'download' && $studentId && $studentsDb) {
    $student = loadStudentData($studentsDb, $studentId);
    $records = loadAcademicRecords($studentsDb, $student, $studentId);
    $type = $_GET['type'] ?? $docSettings['transcript_default_type'] ?? 'transcript';
    if (empty($records) && function_exists('getStaffConnection')) {
        $ssdb = getStaffConnection();
        if ($ssdb) $records = loadExaminationRecords($ssdb, $student['student_number'] ?? $studentId);
    }
    $html = generateTranscriptHTML($student, $records, $type, $docSettings);
    $fname = 'transcript-' . ($student['student_number'] ?? $studentId) . '-' . date('Ymd') . '.html';
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    echo $html;
    exit;
}

// Preview action: show transcript HTML (no auto-print, for iframe preview)
if ($action === 'preview' && $studentId && $studentsDb) {
    $student = loadStudentData($studentsDb, $studentId);
    $records = loadAcademicRecords($studentsDb, $student, $studentId);
    if (empty($records) && function_exists('getStaffConnection')) {
        $ssdb = getStaffConnection();
        if ($ssdb) $records = loadExaminationRecords($ssdb, $student['student_number'] ?? $studentId);
    }
    $type = $_GET['type'] ?? $docSettings['transcript_default_type'] ?? 'transcript';
    echo generateTranscriptHTML($student, $records, $type, $docSettings);
    exit;
}

// Print action: show transcript with auto-print
if ($action === 'print' && $studentId && $studentsDb) {
    $student = loadStudentData($studentsDb, $studentId);
    $records = loadAcademicRecords($studentsDb, $student, $studentId);
    if (empty($records) && function_exists('getStaffConnection')) {
        $ssdb = getStaffConnection();
        if ($ssdb) $records = loadExaminationRecords($ssdb, $student['student_number'] ?? $studentId);
    }
    $type = $_GET['type'] ?? $docSettings['transcript_default_type'] ?? 'transcript';
    $html = generateTranscriptHTML($student, $records, $type, $docSettings);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Print Transcript</title></head><body>';
    echo $html;
    echo '<script>window.onload=function(){window.print();setTimeout(function(){window.close()},1000)};</script>';
    echo '</body></html>';
    exit;
}

// Edit data AJAX: return JSON of student records
if ($action === 'editdata' && $is_staff && $studentId && $studentsDb) {
    header('Content-Type: application/json');
    $student = loadStudentData($studentsDb, $studentId);
    $records = loadAcademicRecords($studentsDb, $student, $studentId);
    if (empty($records) && function_exists('getStaffConnection')) {
        $ssdb = getStaffConnection();
        if ($ssdb) $records = loadExaminationRecords($ssdb, $student['student_number'] ?? $studentId);
    }
    echo json_encode(['student' => $student, 'records' => $records]);
    exit;
}

// Save settings (registrar/director)
$settingsSaved = false;
if ($action === 'save_settings' && $can_manage_settings && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed_keys = ['institution_name','institution_short_name','institution_address','institution_phone','institution_email','institution_motto','principal_name','director_name','registrar_name','transcript_fee','transcript_purposes','transcript_default_type','transcript_footer','transcript_verify_url','logo_path','background_color','accent_color','font_family'];
    $to_save = [];
    foreach ($allowed_keys as $k) {
        if (isset($_POST[$k])) $to_save[$k] = $_POST[$k];
    }
    saveDocumentSettings($to_save);
    $settingsSaved = true;
    // Refresh settings
    $docSettings = loadDocumentSettings();
    // No redirect - keep on same page
}

// Update draft (registrar only)
$saveMsg = '';
if ($action === 'save_draft' && $is_registrar && $_SERVER['REQUEST_METHOD'] === 'POST' && $studentsDb) {
    $sid = (int)($_POST['student_id'] ?? 0);
    $courses_json = $_POST['courses'] ?? '[]';
    $courses = json_decode($courses_json, true);
    if ($sid && is_array($courses)) {
        foreach ($courses as $c) {
            $cc  = $c['course_code'] ?? '';
            $cn  = $c['course_name'] ?? '';
            $cr  = (float)($c['credits'] ?? 0);
            $sem = $c['semester'] ?? '';
            $yr  = $c['academic_year'] ?? date('Y');
            $mk  = (float)($c['marks'] ?? 0);
            $gr  = $c['grade'] ?? '';
            if ($cc) {
                $stmt = $studentsDb->prepare("INSERT INTO student_academic_records (student_id, course_code, course_name, credits, semester, academic_year, marks, grade, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE course_name=?, credits=?, marks=?, grade=?, updated_at=NOW()");
                if ($stmt) {
                    $stmt->bind_param("issssdsssds", $sid, $cc, $cn, $cr, $sem, $yr, $mk, $gr, $cn, $cr, $mk, $gr);
                    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                    $stmt->close();
                }
            }
        }
        $saveMsg = 'Transcript draft saved successfully.';
    } else {
        $saveMsg = 'Invalid data.';
    }
    // Redirect to avoid re-POST
    header('Location: print_transcript.php?student_id=' . $sid . '&saved=1');
    exit;
}

// ---------------------------------------------------------------------------
// Load student data
// ---------------------------------------------------------------------------
$student = [];
$records = [];

if ($studentId && $studentsDb) {
    $student = loadStudentData($studentsDb, $studentId);
    if (!empty($student)) {
        $records = loadAcademicRecords($studentsDb, $student, $studentId);
        // Fallback to staffs_db examination_records if empty
        if (empty($records) && function_exists('getStaffConnection')) {
            $ssdb = getStaffConnection();
            if ($ssdb) $records = loadExaminationRecords($ssdb, $student['student_number'] ?? $studentId);
        }
    }
}

// ---------------------------------------------------------------------------
// Helper functions
// ---------------------------------------------------------------------------
function loadStudentData($db, $id) {
    $id = (int)$id;
    $q = $db->query("SELECT * FROM students WHERE id = $id LIMIT 1");
    if ($q && ($s = $q->fetch_assoc())) {
        $s['full_name'] = $s['full_name'] ?: trim(($s['first_name']??'') . ' ' . ($s['surname']??'') . ($s['other_name'] ? ' ' . $s['other_name'] : ''));
        $s['registration_number'] = $s['registration_number'] ?: $s['student_number'] ?: '';
        return $s;
    }
    // Try by student_number
    $stmt = $db->prepare("SELECT * FROM students WHERE student_number = ? OR registration_number = ? LIMIT 1");
    if ($stmt) {
        $idStr = (string)$id;
        $stmt->bind_param("ss", $idStr, $idStr);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $q = $stmt->get_result();
        if ($q && ($s = $q->fetch_assoc())) {
            $s['full_name'] = $s['full_name'] ?: trim(($s['first_name']??'') . ' ' . ($s['surname']??'') . ($s['other_name'] ? ' ' . $s['other_name'] : ''));
            $s['registration_number'] = $s['registration_number'] ?: $s['student_number'] ?: '';
            $stmt->close();
            return $s;
        }
        $stmt->close();
    }
    return [];
}

function loadAcademicRecords($db, $student, $id) {
    $id = (int)$id;
    $records = [];
    // Try student_academic_records first
    $tables = $db->query("SHOW TABLES LIKE 'student_academic_records'");
    if ($tables && $tables->num_rows > 0) {
        $r = $db->query("SELECT * FROM student_academic_records WHERE student_id = $id ORDER BY academic_year, semester");
        if ($r) while ($row = $r->fetch_assoc()) $records[] = $row;
        if (!empty($records)) return $records;
    }
    // Try academic_records
    $tables = $db->query("SHOW TABLES LIKE 'academic_records'");
    if ($tables && $tables->num_rows > 0) {
        $r = $db->query("SELECT * FROM academic_records WHERE student_id = $id ORDER BY academic_year, semester");
        if ($r) while ($row = $r->fetch_assoc()) $records[] = $row;
    }
    return $records;
}

function loadExaminationRecords($db, $studentNumber) {
    $records = [];
    // Find student in staffs_db
    $stmt = $db->prepare("SELECT id FROM students WHERE student_number = ? OR registration_number = ? OR id = ? LIMIT 1");
    if (!$stmt) return $records;
    $sn = (string)$studentNumber;
    $snInt = (int)$studentNumber;
    $stmt->bind_param("ssi", $sn, $sn, $snInt);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $s = $stmt->get_result();
    if (!$s || !($srow = $s->fetch_assoc())) { $stmt->close(); return $records; }
    $stmt->close();
    $sid = (int)$srow['id'];
    $r = $db->query("SELECT er.*, cc.course_title AS course_name, cc.credits FROM examination_records er LEFT JOIN academic_course_catalog cc ON er.course_code = cc.course_code WHERE er.student_id = $sid AND er.grade_status = 'Published' ORDER BY er.created_at ASC");
    if ($r) while ($row = $r->fetch_assoc()) {
        $records[] = [
            'course_code' => $row['course_code'] ?? '',
            'course_name' => $row['course_name'] ?? '',
            'credits' => $row['credits'] ?? $row['credit_hours'] ?? 0,
            'semester' => $row['semester'] ?? '',
            'academic_year' => $row['academic_year'] ?? date('Y'),
            'marks' => $row['marks_obtained'] ?? $row['total_marks_calculated'] ?? 0,
            'grade' => $row['grade'] ?? '',
            'continuous_assessment_marks' => $row['continuous_assessment_marks'] ?? 0,
            'final_exam_marks' => $row['final_exam_marks'] ?? 0,
        ];
    }
    return $records;
}

// ---------------------------------------------------------------------------
// Page rendering
// ---------------------------------------------------------------------------
$saved = isset($_GET['saved']);
$type  = $_GET['type'] ?? 'transcript';
$hasStudent = !empty($student);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Transcript - ISNM</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --primary: #0f4c3a;
    --primary-light: #1a6b4e;
    --gold: #d4a843;
    --gold-light: #f5d76e;
    --bg: #f0f4f8;
    --card: #ffffff;
    --text: #2d3748;
    --text-light: #718096;
    --border: #e2e8f0;
    --radius: 12px;
    --shadow: 0 4px 20px rgba(0,0,0,0.08);
}

* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

/* Top Bar */
.topbar { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: #fff; padding: 16px 30px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 15px rgba(0,0,0,0.15); }
.topbar .brand { display: flex; align-items: center; gap: 12px; }
.topbar .brand img { width: 42px; height: 42px; border-radius: 50%; border: 2px solid var(--gold); object-fit: cover; }
.topbar .brand h1 { font-size: 18px; font-weight: 700; letter-spacing: 0.5px; }
.topbar .brand small { display: block; font-size: 10px; opacity: 0.8; font-weight: 400; }
.topbar .nav-links { display: flex; gap: 10px; align-items: center; }
.topbar .nav-links a { color: rgba(255,255,255,0.85); text-decoration: none; font-size: 13px; padding: 6px 14px; border-radius: 6px; transition: all 0.2s; }
.topbar .nav-links a:hover { background: rgba(255,255,255,0.15); color: #fff; }
.topbar .nav-links .role-badge { font-size: 10px; background: var(--gold); color: var(--primary); padding: 3px 10px; border-radius: 20px; font-weight: 700; text-transform: uppercase; }

/* Main layout */
.main-container { max-width: 1200px; margin: 0 auto; padding: 25px 20px; }

/* Alert / Message */
.alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
.alert-success { background: #f0fff4; border: 1px solid #c6f6d5; color: #22543d; }
.alert-info { background: #ebf8ff; border: 1px solid #bee3f8; color: #2a4365; }
.alert-warning { background: #fffff0; border: 1px solid #fefcbf; color: #744210; }

/* Card */
.card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 24px; }
.card-header { padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.card-header h2 { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.card-body { padding: 24px; }

/* Student Selector */
.selector-section { margin-bottom: 24px; }
.search-box { display: flex; gap: 10px; flex-wrap: wrap; }
.search-box input[type="text"] { flex: 1; min-width: 220px; padding: 10px 16px; border: 2px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s; }
.search-box input[type="text"]:focus { border-color: var(--primary); }
.search-box button { padding: 10px 22px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-light); }
.btn-gold { background: var(--gold); color: var(--primary); }
.btn-gold:hover { background: var(--gold-light); }
.btn-outline { background: transparent; border: 2px solid var(--border); color: var(--text); }
.btn-outline:hover { border-color: var(--primary); color: var(--primary); }
.btn-danger { background: #e53e3e; color: #fff; }
.btn-danger:hover { background: #c53030; }
.btn-sm { padding: 6px 14px !important; font-size: 12px !important; }

.search-results { margin-top: 8px; background: #fff; border: 1px solid var(--border); border-radius: 8px; max-height: 280px; overflow-y: auto; display: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.search-results .result-item { padding: 10px 16px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background 0.15s; }
.search-results .result-item:hover { background: #f0fdf4; }
.search-results .result-item:last-child { border-bottom: none; }
.search-results .result-item .name { font-weight: 600; font-size: 14px; }
.search-results .result-item .meta { font-size: 12px; color: var(--text-light); }

.selected-student { display: none; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 14px 18px; margin-top: 12px; }
.selected-student .info { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.selected-student .info strong { font-size: 16px; }
.selected-student .info span { font-size: 13px; color: var(--text-light); }
.selected-student .actions { margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap; }

/* Transcript preview area */
.transcript-preview { display: none; }
.transcript-preview .toolbar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
.transcript-preview .toolbar .btn { display: flex; align-items: center; gap: 6px; }
.transcript-preview iframe { width: 100%; height: 800px; border: 1px solid var(--border); border-radius: 8px; background: #fff; }

/* Edit mode */
.edit-mode { display: none; }
.edit-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.edit-table th { background: var(--primary); color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
.edit-table td { padding: 6px 10px; border-bottom: 1px solid var(--border); }
.edit-table td input, .edit-table td select { width: 100%; padding: 5px 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 12px; }
.edit-table tr:nth-child(even) td { background: #fafafa; }

/* Form controls */
.form-group { margin-bottom: 0; }
.form-control { font-family: inherit; }
.form-control:focus { outline: none; border-color: var(--primary) !important; box-shadow: 0 0 0 3px rgba(15,76,58,0.1); }

/* Empty state */
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); }
.empty-state i { font-size: 48px; color: var(--gold); margin-bottom: 16px; }
.empty-state h3 { font-size: 18px; color: var(--text); margin-bottom: 8px; }
.empty-state p { font-size: 14px; max-width: 400px; margin: 0 auto; }

/* Responsive */
@media (max-width: 768px) {
    .topbar { padding: 12px 16px; flex-direction: column; gap: 8px; }
    .topbar .brand h1 { font-size: 15px; }
    .transcript-preview iframe { height: 500px; }
    .card-body { padding: 16px; }
    .search-box input[type="text"] { min-width: 150px; }
}

/* Loading spinner */
.spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Print-specific */
@media print {
    .topbar, .main-container > .alert, .selector-section, .toolbar, .edit-mode-toggle { display: none !important; }
    .transcript-preview { display: block !important; }
    .transcript-preview iframe { height: auto; border: none; }
}
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
    <div class="brand">
        <img src="images/school-logo.png" alt="ISNM" onerror="this.style.display='none'">
        <div>
            <h1>ISNM Transcript Portal</h1>
            <small>Iganga School of Nursing &amp; Midwifery</small>
        </div>
    </div>
    <div class="nav-links">
        <?php if ($is_student): ?>
            <span class="role-badge">Student</span>
            <span style="font-size:13px;opacity:0.9"><?= htmlspecialchars($_SESSION['student_name'] ?? $_SESSION['full_name'] ?? '') ?></span>
            <a href="dashboards/student.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <?php elseif ($is_staff): ?>
            <span class="role-badge"><?= htmlspecialchars($role ?: 'Staff') ?></span>
            <a href="dashboards/academic-registrar.php"><i class="fas fa-arrow-left"></i> Registrar</a>
            <a href="dashboards/director-general.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <?php endif; ?>
        <?php if (!$is_student && !$is_staff): ?>
            <a href="staff-login.php">Staff Login</a>
            <a href="student-login.php">Student Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="main-container">

<?php if ($saved): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> Transcript draft saved successfully.</div>
<?php endif; ?>

<?php if ($saveMsg): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($saveMsg) ?></div>
<?php endif; ?>

<?php if ($settingsSaved): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> Document settings saved successfully. <a href="javascript:void(0)" onclick="this.closest('.alert').remove()">Dismiss</a></div>
<?php endif; ?>

<?php if (!$is_student && !$is_staff): ?>
<!-- No user logged in -->
<div class="card">
    <div class="empty-state">
        <i class="fas fa-file-alt"></i>
        <h3>Academic Transcript Portal</h3>
        <p>Please log in to access transcripts. Students can view their own, staff can manage all transcripts.</p>
        <div style="margin-top:20px;display:flex;gap:12px;justify-content:center">
            <a href="student-login.php" class="btn btn-primary" style="padding:10px 24px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px"><i class="fas fa-user-graduate"></i> Student Login</a>
            <a href="staff-login.php" class="btn btn-outline" style="padding:10px 24px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px"><i class="fas fa-user-tie"></i> Staff Login</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($is_student && $hasStudent): ?>
<!-- Student view: auto-loaded transcript -->
<div class="alert alert-info"><i class="fas fa-info-circle"></i> Your academic transcript is shown below. Use the buttons to download or print.</div>
<div class="transcript-preview" style="display:block">
    <div class="toolbar">
        <button class="btn btn-primary" onclick="downloadTranscript()"><i class="fas fa-download"></i> Download HTML</button>
        <button class="btn btn-gold" onclick="printTranscript()"><i class="fas fa-print"></i> Print</button>
        <span style="margin-left:auto;font-size:13px;color:var(--text-light);display:flex;align-items:center;gap:6px"><i class="fas fa-user"></i> <?= htmlspecialchars($student['full_name'] ?? '') ?></span>
    </div>
    <iframe id="transcriptFrame" src="print_transcript.php?action=preview&student_id=<?= (int)$student['id'] ?>&type=<?= urlencode($type) ?>" sandbox="allow-scripts allow-same-origin"></iframe>
</div>
<?php endif; ?>

<?php if ($is_staff): ?>
<!-- Staff: Student Search -->
<div class="card selector-section">
    <div class="card-header"><h2><i class="fas fa-search"></i> Find Student</h2></div>
    <div class="card-body">
        <div class="search-box">
            <input type="text" id="studentSearch" placeholder="Search by name, registration number, or student number..." autocomplete="off">
            <button class="btn btn-primary" onclick="searchStudent()"><i class="fas fa-search"></i> Search</button>
        </div>
        <div class="search-results" id="searchResults"></div>
        <div class="selected-student" id="selectedStudent">
            <div class="info">
                <i class="fas fa-user-graduate" style="font-size:24px;color:var(--primary)"></i>
                <div>
                    <strong id="selName"></strong>
                    <span id="selReg"></span>
                    <span id="selProgram" style="display:block;font-size:12px;color:var(--text-light)"></span>
                </div>
            </div>
            <div class="actions">
                <button class="btn btn-primary btn-sm" onclick="viewTranscript()"><i class="fas fa-eye"></i> View Transcript</button>
                <button class="btn btn-gold btn-sm" onclick="downloadTranscript()"><i class="fas fa-download"></i> Download</button>
                <button class="btn btn-outline btn-sm" onclick="printTranscript()"><i class="fas fa-print"></i> Print</button>
                <?php if ($is_registrar): ?>
                <button class="btn btn-outline btn-sm" onclick="toggleEdit()" id="editToggleBtn"><i class="fas fa-edit"></i> Edit Draft</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Transcript Preview (staff) -->
<div class="card transcript-preview" id="transcriptPreview">
    <div class="card-header">
        <h2><i class="fas fa-file-alt"></i> <span id="previewTitle">Academic Transcript</span></h2>
        <?php if ($hasStudent): ?>
        <div style="display:flex;gap:8px">
            <button class="btn btn-primary btn-sm" onclick="downloadTranscript()"><i class="fas fa-download"></i> Download</button>
            <button class="btn btn-gold btn-sm" onclick="printTranscript()"><i class="fas fa-print"></i> Print</button>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body" style="padding:0">
        <?php if ($hasStudent): ?>
        <iframe id="transcriptFrame" src="print_transcript.php?action=preview&student_id=<?= (int)$student['id'] ?>&type=<?= urlencode($type) ?>" sandbox="allow-scripts allow-same-origin" style="height:800px;width:100%;border:none;border-radius:0 0 12px 12px"></iframe>
        <?php else: ?>
        <div class="empty-state" style="padding:40px 20px">
            <i class="fas fa-search" style="font-size:36px"></i>
            <h3>No Student Selected</h3>
            <p>Search and select a student above to view their transcript.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Mode (registrar only) -->
<?php if ($is_registrar): ?>
<div class="card edit-mode" id="editMode">
    <div class="card-header">
        <h2><i class="fas fa-edit"></i> Edit Transcript Draft</h2>
        <div style="display:flex;gap:8px">
            <button class="btn btn-primary btn-sm" onclick="saveDraft()"><i class="fas fa-save"></i> Save Draft</button>
            <button class="btn btn-outline btn-sm" onclick="toggleEdit()"><i class="fas fa-times"></i> Cancel</button>
        </div>
    </div>
    <div class="card-body">
        <form id="editForm" method="POST" action="print_transcript.php">
            <input type="hidden" name="action" value="save_draft">
            <input type="hidden" name="student_id" id="editStudentId" value="<?= $student['id'] ?? '' ?>">
            <input type="hidden" name="courses" id="editCoursesData">
            <div style="overflow-x:auto">
                <table class="edit-table" id="editCoursesTable">
                    <thead>
                        <tr>
                            <th style="width:12%">Course Code</th>
                            <th style="width:25%">Course Name</th>
                            <th style="width:8%">Credits</th>
                            <th style="width:12%">Semester</th>
                            <th style="width:12%">Year</th>
                            <th style="width:10%">Marks</th>
                            <th style="width:10%">Grade</th>
                            <th style="width:11%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="editTableBody">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $i => $r): ?>
                            <tr>
                                <td><input type="text" class="ec-code" value="<?= htmlspecialchars($r['course_code'] ?? '') ?>"></td>
                                <td><input type="text" class="ec-name" value="<?= htmlspecialchars($r['course_name'] ?? '') ?>"></td>
                                <td><input type="number" class="ec-credits" value="<?= (int)($r['credits'] ?? 0) ?>" min="0" max="20" step="1"></td>
                                <td>
                                    <select class="ec-semester">
                                        <option value="Semester 1" <?= ($r['semester']??'') === 'Semester 1' ? 'selected' : '' ?>>Semester 1</option>
                                        <option value="Semester 2" <?= ($r['semester']??'') === 'Semester 2' ? 'selected' : '' ?>>Semester 2</option>
                                        <option value="Semester 3" <?= ($r['semester']??'') === 'Semester 3' ? 'selected' : '' ?>>Semester 3</option>
                                        <option value="Semester 4" <?= ($r['semester']??'') === 'Semester 4' ? 'selected' : '' ?>>Semester 4</option>
                                    </select>
                                </td>
                                <td><input type="text" class="ec-year" value="<?= htmlspecialchars($r['academic_year'] ?? date('Y')) ?>"></td>
                                <td><input type="number" class="ec-marks" value="<?= htmlspecialchars($r['marks'] ?? '') ?>" min="0" max="100" step="0.5"></td>
                                <td>
                                    <select class="ec-grade">
                                        <?php foreach (['A','B+','B','C+','C','D','E','F'] as $g): ?>
                                        <option value="<?= $g ?>" <?= ($r['grade']??'') === $g ? 'selected' : '' ?>><?= $g ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()" style="padding:3px 8px;font-size:11px"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px">
                <button type="button" class="btn btn-outline btn-sm" onclick="addEditRow()"><i class="fas fa-plus"></i> Add Course</button>
            </div>
        </form>
    </div>
</div>
<!-- Document Settings Panel (registrar/director) -->
<?php if ($can_manage_settings): ?>
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <h2><i class="fas fa-cog"></i> Document Settings</h2>
        <button class="btn btn-outline btn-sm" onclick="toggleSettings()" id="settingsToggleBtn"><i class="fas fa-chevron-down"></i> Show Settings</button>
    </div>
    <div class="card-body" id="settingsPanel" style="display:none">
        <form method="POST" action="print_transcript.php" onsubmit="return confirm('Save document settings?')">
            <input type="hidden" name="action" value="save_settings">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:900px">
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Institution Name</label>
                    <input type="text" name="institution_name" value="<?= htmlspecialchars($docSettings['institution_name'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Short Name</label>
                    <input type="text" name="institution_short_name" value="<?= htmlspecialchars($docSettings['institution_short_name'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Address</label>
                    <input type="text" name="institution_address" value="<?= htmlspecialchars($docSettings['institution_address'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Phone</label>
                    <input type="text" name="institution_phone" value="<?= htmlspecialchars($docSettings['institution_phone'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Email</label>
                    <input type="text" name="institution_email" value="<?= htmlspecialchars($docSettings['institution_email'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Motto</label>
                    <input type="text" name="institution_motto" value="<?= htmlspecialchars($docSettings['institution_motto'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Principal Name</label>
                    <input type="text" name="principal_name" value="<?= htmlspecialchars($docSettings['principal_name'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Director General</label>
                    <input type="text" name="director_name" value="<?= htmlspecialchars($docSettings['director_name'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Registrar Name</label>
                    <input type="text" name="registrar_name" value="<?= htmlspecialchars($docSettings['registrar_name'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Transcript Fee (UGX)</label>
                    <input type="number" name="transcript_fee" value="<?= htmlspecialchars($docSettings['transcript_fee'] ?? '50000') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Transcript Purposes (comma-separated)</label>
                    <input type="text" name="transcript_purposes" value="<?= htmlspecialchars($docSettings['transcript_purposes'] ?? '') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Default Type</label>
                    <select name="transcript_default_type" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                        <option value="transcript" <?= ($docSettings['transcript_default_type']??'') === 'transcript' ? 'selected' : '' ?>>Official Transcript</option>
                        <option value="progress" <?= ($docSettings['transcript_default_type']??'') === 'progress' ? 'selected' : '' ?>>Progress Report</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Footer Text</label>
                    <textarea name="transcript_footer" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px;min-height:50px"><?= htmlspecialchars($docSettings['transcript_footer'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Logo Path (relative to root)</label>
                    <input type="text" name="logo_path" value="<?= htmlspecialchars($docSettings['logo_path'] ?? 'images/school-logo.png') ?>" class="form-control" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Primary Color</label>
                    <input type="color" name="background_color" value="<?= htmlspecialchars($docSettings['background_color'] ?? '#0f4c3a') ?>" class="form-control" style="width:100%;padding:3px 5px;border:1px solid var(--border);border-radius:6px;font-size:13px;height:36px">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;color:var(--text)">Accent Color</label>
                    <input type="color" name="accent_color" value="<?= htmlspecialchars($docSettings['accent_color'] ?? '#d4a843') ?>" class="form-control" style="width:100%;padding:3px 5px;border:1px solid var(--border);border-radius:6px;font-size:13px;height:36px">
                </div>
            </div>
            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
                <button type="reset" class="btn btn-outline" onclick="toggleSettings()">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

</div>
<?php endif; ?>

<script>
// -----------------------------------------------------------------------
// Student search (staff)
// -----------------------------------------------------------------------
let selectedStudentId = <?= $student['id'] ?? 'null' ?>;
let selectedStudentName = <?= json_encode($student['full_name'] ?? '') ?>;
let selectedStudentReg = <?= json_encode($student['registration_number'] ?? '') ?>;
let selectedStudentProgram = <?= json_encode($student['program'] ?? $student['course'] ?? '') ?>;

const searchInput = document.getElementById('studentSearch');
const searchResults = document.getElementById('searchResults');
const selectedStudent = document.getElementById('selectedStudent');
const transcriptPreview = document.getElementById('transcriptPreview');

function searchStudent() {
    const q = searchInput ? searchInput.value.trim() : '';
    if (q.length < 2) return;
    searchResults.style.display = 'block';
    searchResults.innerHTML = '<div class="result-item" style="text-align:center;color:#999"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
    fetch('print_transcript.php?action=lookup&q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            if (!data || data.length === 0) {
                searchResults.innerHTML = '<div class="result-item" style="text-align:center;color:#999">No students found.</div>';
                return;
            }
            let html = '';
            data.forEach(s => {
                html += '<div class="result-item" onclick="selectStudent(' + s.id + ',\'' + escJs(s.full_name) + '\',\'' + escJs(s.registration_number || s.student_number) + '\',\'' + escJs(s.program) + '\')">';
                html += '<div class="name">' + escHtml(s.full_name) + '</div>';
                html += '<div class="meta">' + escHtml(s.registration_number || s.student_number) + ' &middot; ' + escHtml(s.program) + '</div>';
                html += '</div>';
            });
            searchResults.innerHTML = html;
        })
        .catch(() => {
            searchResults.innerHTML = '<div class="result-item" style="text-align:center;color:#c00">Search failed.</div>';
        });
}

let searchTimer = null;
if (searchInput) {
    searchInput.addEventListener('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(searchStudent, 300);
    });
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) searchResults.style.display = 'block';
    });
    document.addEventListener('click', function(e) {
        if (searchResults && !e.target.closest('.search-box')) searchResults.style.display = 'none';
    });
}

function selectStudent(id, name, reg, program) {
    selectedStudentId = id;
    selectedStudentName = name;
    selectedStudentReg = reg;
    selectedStudentProgram = program;
    searchResults.style.display = 'none';
    if (searchInput) searchInput.value = name;
    if (selectedStudent) {
        selectedStudent.style.display = 'block';
        document.getElementById('selName').textContent = name;
        document.getElementById('selReg').textContent = reg;
        document.getElementById('selProgram').textContent = program;
    }
    if (transcriptPreview) {
        transcriptPreview.style.display = 'block';
        const frame = document.getElementById('transcriptFrame');
        if (frame) {
            frame.src = 'print_transcript.php?action=preview&student_id=' + id + '&type=transcript';
        }
        document.getElementById('previewTitle').textContent = 'Academic Transcript - ' + name;
    }
    // Set edit form student id
    const editId = document.getElementById('editStudentId');
    if (editId) editId.value = id;
    // Load edit data
    <?php if ($is_registrar): ?>
    loadEditData(id);
    <?php endif; ?>
}

// Already have a student pre-selected
if (selectedStudentId && selectedStudent) {
    selectedStudent.style.display = 'block';
    document.getElementById('selName').textContent = selectedStudentName;
    document.getElementById('selReg').textContent = selectedStudentReg;
    document.getElementById('selProgram').textContent = selectedStudentProgram;
    if (transcriptPreview) transcriptPreview.style.display = 'block';
}

function escJs(s) { return (s + '').replace(/'/g, "\\'").replace(/"/g, '&quot;'); }
function escHtml(s) { return (s + '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// -----------------------------------------------------------------------
// Actions
// -----------------------------------------------------------------------
let currentStudentId = () => selectedStudentId || <?= $student['id'] ?? 'null' ?>;

function viewTranscript() {
    const id = currentStudentId();
    if (!id) return;
    const frame = document.getElementById('transcriptFrame');
    if (frame) {
        frame.src = 'print_transcript.php?action=preview&student_id=' + id + '&type=transcript';
    }
    if (transcriptPreview) transcriptPreview.style.display = 'block';
}

function downloadTranscript() {
    const id = currentStudentId();
    if (!id) { alert('Please select a student first.'); return; }
    window.open('print_transcript.php?action=download&student_id=' + id + '&type=transcript', '_blank');
}

function printTranscript() {
    const id = currentStudentId();
    if (!id) { alert('Please select a student first.'); return; }
    window.open('print_transcript.php?action=print&student_id=' + id + '&type=transcript', '_blank');
}

// -----------------------------------------------------------------------
// Edit mode (registrar)
// -----------------------------------------------------------------------
<?php if ($is_registrar): ?>
let editVisible = false;

function toggleEdit() {
    editVisible = !editVisible;
    const el = document.getElementById('editMode');
    const btn = document.getElementById('editToggleBtn');
    if (!el) return;
    el.style.display = editVisible ? 'block' : 'none';
    if (btn) btn.textContent = editVisible ? 'Cancel Edit' : 'Edit Draft';
    // Load edit data if table is empty
    if (editVisible && document.getElementById('editTableBody').children.length === 0) {
        loadEditData(currentStudentId());
    }
}

function loadEditData(studentId) {
    if (!studentId) return;
    const tbody = document.getElementById('editTableBody');
    // For new AJAX-selected students, fetch fresh data
    fetch('print_transcript.php?action=editdata&student_id=' + studentId)
        .then(r => r.json())
        .then(data => {
            if (data && data.records) {
                populateEditTable(data.records);
            } else {
                populateEditTable([]);
            }
        })
        .catch(() => {
            // If AJAX fails, try reloading the page
        });
}

function populateEditTable(records) {
    const tbody = document.getElementById('editTableBody');
    tbody.innerHTML = '';
    records.forEach(function(r) {
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" class="ec-code" value="' + escHtml(r.course_code||'') + '"></td>' +
            '<td><input type="text" class="ec-name" value="' + escHtml(r.course_name||'') + '"></td>' +
            '<td><input type="number" class="ec-credits" value="' + (r.credits||0) + '" min="0" max="20" step="1"></td>' +
            '<td><select class="ec-semester">' +
                '<option value="Semester 1"' + (r.semester==='Semester 1'?' selected':'') + '>Semester 1</option>' +
                '<option value="Semester 2"' + (r.semester==='Semester 2'?' selected':'') + '>Semester 2</option>' +
                '<option value="Semester 3"' + (r.semester==='Semester 3'?' selected':'') + '>Semester 3</option>' +
                '<option value="Semester 4"' + (r.semester==='Semester 4'?' selected':'') + '>Semester 4</option>' +
            '</select></td>' +
            '<td><input type="text" class="ec-year" value="' + escHtml(r.academic_year||'') + '"></td>' +
            '<td><input type="number" class="ec-marks" value="' + (r.marks||'') + '" min="0" max="100" step="0.5"></td>' +
            '<td><select class="ec-grade">' +
                ['A','B+','B','C+','C','D','E','F'].map(function(g) {
                    return '<option value="' + g + '"' + (r.grade===g?' selected':'') + '>' + g + '</option>';
                }).join('') +
            '</select></td>' +
            '<td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest(\'tr\').remove()" style="padding:3px 8px;font-size:11px"><i class="fas fa-trash"></i></button></td>';
        tbody.appendChild(tr);
    });
}

function addEditRow() {
    const tbody = document.getElementById('editTableBody');
    const tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type="text" class="ec-code" placeholder="e.g. NUR101"></td>' +
        '<td><input type="text" class="ec-name" placeholder="Course name"></td>' +
        '<td><input type="number" class="ec-credits" value="3" min="0" max="20" step="1"></td>' +
        '<td><select class="ec-semester">' +
            '<option value="Semester 1">Semester 1</option>' +
            '<option value="Semester 2">Semester 2</option>' +
            '<option value="Semester 3">Semester 3</option>' +
            '<option value="Semester 4">Semester 4</option>' +
        '</select></td>' +
        '<td><input type="text" class="ec-year" value="<?= date('Y') ?>"></td>' +
        '<td><input type="number" class="ec-marks" value="" min="0" max="100" step="0.5"></td>' +
        '<td><select class="ec-grade">' +
            ['A','B+','B','C+','C','D','E','F'].map(function(g) {
                return '<option value="' + g + '">' + g + '</option>';
            }).join('') +
        '</select></td>' +
        '<td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest(\'tr\').remove()" style="padding:3px 8px;font-size:11px"><i class="fas fa-trash"></i></button></td>';
    tbody.appendChild(tr);
}

function saveDraft() {
    const rows = document.querySelectorAll('#editTableBody tr');
    const courses = [];
    rows.forEach(function(tr) {
        const code = tr.querySelector('.ec-code');
        const name = tr.querySelector('.ec-name');
        const credits = tr.querySelector('.ec-credits');
        const semester = tr.querySelector('.ec-semester');
        const year = tr.querySelector('.ec-year');
        const marks = tr.querySelector('.ec-marks');
        const grade = tr.querySelector('.ec-grade');
        if (code && code.value.trim()) {
            courses.push({
                course_code: code.value.trim(),
                course_name: name ? name.value.trim() : '',
                credits: credits ? parseFloat(credits.value) || 0 : 0,
                semester: semester ? semester.value : '',
                academic_year: year ? year.value : '',
                marks: marks ? parseFloat(marks.value) || 0 : 0,
                grade: grade ? grade.value : '',
            });
        }
    });
    document.getElementById('editCoursesData').value = JSON.stringify(courses);
    document.getElementById('editForm').submit();
}
<?php endif; ?>

<?php if ($can_manage_settings): ?>
// Document settings toggle
function toggleSettings() {
    const panel = document.getElementById('settingsPanel');
    const btn = document.getElementById('settingsToggleBtn');
    if (!panel) return;
    const visible = panel.style.display !== 'none';
    panel.style.display = visible ? 'none' : 'block';
    if (btn) btn.innerHTML = visible ? '<i class="fas fa-chevron-down"></i> Show Settings' : '<i class="fas fa-chevron-up"></i> Hide Settings';
}
<?php endif; ?>

// Handle iframe load events
const frame = document.getElementById('transcriptFrame');
if (frame) {
    frame.addEventListener('load', function() {
        // Could add loading indicator toggle here
    });
}
</script>

</body>
</html>
