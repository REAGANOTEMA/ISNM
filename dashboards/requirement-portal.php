<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['director','secretary','registrar','principal','deputy','hr','ict','storekeeper','matron','bursar','finance','admin','ceo']);
$staffConn = $ctx['staff'];
$stuConn = $ctx['students'] ?? null;
$user = $ctx['user'];
$userId = (int)($user['id'] ?? 0);
$userName = $user['full_name'] ?? 'Staff';

if (!$stuConn) { $stuConn = getStudentsConnection(); }

$REQUIRED_ITEMS = [
    'Surgical Gloves', 'Examination Gloves', 'Photocopying Ream', 'Ruled Paper Reams',
    'Omo', 'Toilet Papers', 'Compound Brooms', 'Soft Brooms', 'Rake',
    'Cobweb Brush', 'Scrubbing Brush', 'Squeezer', 'Toilet Brush',
    'JIK', 'Vim', 'Mops', 'Sanitizer', 'Liquid Soap', 'Face Masks', 'Heavy Duty Gloves'
];

if ($staffConn) {
    @$staffConn->query("CREATE TABLE IF NOT EXISTS student_requirements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        student_name VARCHAR(200) DEFAULT NULL,
        registration_number VARCHAR(50) DEFAULT NULL,
        requirement_type VARCHAR(100) NOT NULL,
        status ENUM('pending','submitted','verified','rejected') DEFAULT 'pending',
        verified_by INT DEFAULT NULL,
        verified_date DATE DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sr_student (student_id),
        INDEX idx_sr_type (requirement_type),
        INDEX idx_sr_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staffConn) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('{"success":false,"message":"Invalid CSRF token"}');
    }
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'search_students') {
        $q = trim($_POST['query'] ?? '');
        $filter = trim($_POST['filter'] ?? 'all');
        if (strlen($q) < 2) { echo json_encode(['success' => true, 'students' => []]); exit; }
        $students = [];
        if ($stuConn) {
            $like = "%$q%";
            $sql = "SELECT id, student_number, first_name, surname, other_name, full_name, phone, program, status FROM students WHERE status='Active'";
            if ($filter === 'name') { $sql .= " AND (full_name LIKE ? OR first_name LIKE ? OR surname LIKE ?)"; }
            elseif ($filter === 'admission') { $sql .= " AND student_number LIKE ?"; }
            elseif ($filter === 'phone') { $sql .= " AND (phone LIKE ? OR mobile_number LIKE ?)"; }
            else { $sql .= " AND (full_name LIKE ? OR student_number LIKE ? OR phone LIKE ?)"; }
            $sql .= " ORDER BY full_name LIMIT 50";
            $s = $stuConn->prepare($sql);
            if ($s) {
                if ($filter === 'name') { $s->bind_param("sss", $like, $like, $like); }
                elseif ($filter === 'admission') { $s->bind_param("s", $like); }
                elseif ($filter === 'phone') { $s->bind_param("ss", $like, $like); }
                else { $s->bind_param("sss", $like, $like, $like); }
                $s->execute();
                $students = $s->get_result()->fetch_all(MYSQLI_ASSOC);
                $s->close();
            }
        }
        echo json_encode(['success' => true, 'students' => $students]);
        exit;
    }

    if ($action === 'get_student_requirements') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $reqs = [];
        if ($studentId && $staffConn) {
            $s = $staffConn->prepare("SELECT requirement_type, status, verified_date, verified_by FROM student_requirements WHERE student_id=?");
            if ($s) { $s->bind_param("i", $studentId); $s->execute(); $reqs = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); }
        }
        echo json_encode(['success' => true, 'requirements' => $reqs]);
        exit;
    }

    if ($action === 'toggle_requirement') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $reqType = trim($_POST['requirement_type'] ?? '');
        $newStatus = trim($_POST['new_status'] ?? 'verified');
        if (!$studentId || !$reqType) { echo json_encode(['success' => false, 'message' => 'Missing student or requirement']); exit; }

        $studentName = trim($_POST['student_name'] ?? '');
        $regNum = trim($_POST['registration_number'] ?? '');

        $s = $staffConn->prepare("SELECT id FROM student_requirements WHERE student_id=? AND requirement_type=? LIMIT 1");
        $s->bind_param("is", $studentId, $reqType);
        $s->execute();
        $exists = $s->get_result()->fetch_assoc();
        $s->close();

        if ($exists) {
            $s = $staffConn->prepare("UPDATE student_requirements SET status=?, verified_by=CASE WHEN ?='verified' THEN ? ELSE verified_by END, verified_date=CASE WHEN ?='verified' THEN CURDATE() ELSE verified_date END, updated_at=NOW() WHERE id=?");
            $s->bind_param("ssiis", $newStatus, $newStatus, $userId, $newStatus, $exists['id']);
            $s->execute();
            $s->close();
        } else {
            $s = $staffConn->prepare("INSERT INTO student_requirements (student_id, student_name, registration_number, requirement_type, status, verified_by, verified_date) VALUES (?, ?, ?, ?, ?, ?, CASE WHEN ?='verified' THEN CURDATE() ELSE NULL END)");
            $s->bind_param("issssis", $studentId, $studentName, $regNum, $reqType, $newStatus, $userId, $newStatus);
            $s->execute();
            $s->close();
        }
        echo json_encode(['success' => true, 'message' => "$reqType marked as $newStatus"]);
        exit;
    }

    if ($action === 'bulk_verify') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $items = $_POST['items'] ?? [];
        $studentName = trim($_POST['student_name'] ?? '');
        $regNum = trim($_POST['registration_number'] ?? '');
        if (!$studentId || empty($items)) { echo json_encode(['success' => false, 'message' => 'No items']); exit; }

        foreach ($items as $reqType) {
            $reqType = trim($reqType);
            if (!$reqType) continue;
            $s = $staffConn->prepare("SELECT id FROM student_requirements WHERE student_id=? AND requirement_type=? LIMIT 1");
            $s->bind_param("is", $studentId, $reqType);
            $s->execute();
            $exists = $s->get_result()->fetch_assoc();
            $s->close();
            if ($exists) {
                $s = $staffConn->prepare("UPDATE student_requirements SET status='verified', verified_by=?, verified_date=CURDATE(), updated_at=NOW() WHERE id=?");
                $s->bind_param("ii", $userId, $exists['id']);
                $s->execute();
                $s->close();
            } else {
                $s = $staffConn->prepare("INSERT INTO student_requirements (student_id, student_name, registration_number, requirement_type, status, verified_by, verified_date) VALUES (?, ?, ?, ?, 'verified', ?, CURDATE())");
                $s->bind_param("isssi", $studentId, $studentName, $regNum, $reqType, $userId);
                $s->execute();
                $s->close();
            }
        }
        echo json_encode(['success' => true, 'message' => count($items) . " items verified"]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.rp-content{margin-left:var(--sidebar-w,270px);padding:24px;min-height:100vh;background:#f0f2f5}
@media(max-width:991px){.rp-content{margin-left:0!important;padding:16px!important}}
.rp-header{background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;padding:20px 28px;border-radius:14px;margin-bottom:24px}
.rp-header h1{margin:0;font-size:22px;font-weight:700}
.rp-header p{margin:4px 0 0;opacity:.85;font-size:13px}
.rp-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:20px;overflow:hidden}
.rp-card-header{padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between}
.rp-card-header h5{margin:0;font-size:1rem;font-weight:600}
.rp-card-body{padding:20px}
.rp-search-row{display:flex;gap:10px;flex-wrap:wrap;align-items:end}
.rp-search-input{flex:1;min-width:200px;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem}
.rp-search-input:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.rp-filter-btn{padding:8px 16px;border-radius:8px;font-size:.82rem;font-weight:600;border:1px solid #d1d5db;background:#fff;color:#6b7280;cursor:pointer;transition:all .15s}
.rp-filter-btn:hover{background:#f3f4f6}
.rp-filter-btn.active{background:#7c3aed;color:#fff;border-color:#7c3aed}
.rp-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:8px;font-size:.85rem;font-weight:500;border:none;cursor:pointer;transition:all .15s}
.rp-btn-primary{background:#7c3aed;color:#fff}.rp-btn-primary:hover{background:#6d28d9;color:#fff}
.rp-btn-success{background:#10b981;color:#fff}.rp-btn-success:hover{background:#059669;color:#fff}
.rp-btn-sm{padding:5px 12px;font-size:.8rem}
.rp-student-row{display:flex;align-items:center;gap:14px;padding:14px 18px;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:8px;cursor:pointer;transition:all .15s}
.rp-student-row:hover{border-color:#7c3aed;background:#faf5ff;transform:translateY(-1px);box-shadow:0 2px 8px rgba(124,58,237,.08)}
.rp-student-row.selected{border-color:#7c3aed;background:#ede9fe;box-shadow:0 2px 12px rgba(124,58,237,.15)}
.rp-student-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;flex-shrink:0}
.rp-student-info{flex:1;min-width:0}
.rp-student-name{font-weight:600;font-size:.95rem;color:#1f2937}
.rp-student-meta{font-size:.8rem;color:#6b7280;margin-top:2px}
.rp-student-badge{padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:600;white-space:nowrap}
.rp-badge-cleared{background:#d1fae5;color:#065f46}
.rp-badge-partial{background:#fef3c7;color:#92400e}
.rp-badge-none{background:#fee2e2;color:#991b1b}
.rp-req-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px}
.rp-req-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;transition:all .15s;cursor:pointer;user-select:none}
.rp-req-item:hover{border-color:#a78bfa;background:#faf5ff}
.rp-req-item.cleared{border-color:#10b981;background:#ecfdf5}
.rp-req-item .rp-check{width:22px;height:22px;border-radius:6px;border:2px solid #d1d5db;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s}
.rp-req-item.cleared .rp-check{background:#10b981;border-color:#10b981;color:#fff}
.rp-req-item .rp-check i{font-size:12px;display:none}
.rp-req-item.cleared .rp-check i{display:block}
.rp-req-label{font-size:.88rem;font-weight:500;color:#1f2937;flex:1}
.rp-progress{height:8px;border-radius:4px;background:#e5e7eb;overflow:hidden;margin:12px 0}
.rp-progress-bar{height:100%;border-radius:4px;background:linear-gradient(90deg,#7c3aed,#a78bfa);transition:width .3s ease}
.rp-empty{text-align:center;padding:60px 20px;color:#9ca3af}
.rp-empty i{font-size:3rem;margin-bottom:12px;display:block;opacity:.3}
.rp-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:20px}
.rp-stat-card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);text-align:center}
.rp-stat-num{font-size:1.5rem;font-weight:700;color:#7c3aed}
.rp-stat-lbl{font-size:.75rem;color:#6b7280;margin-top:2px}
.rp-toast{position:fixed;top:20px;right:20px;z-index:10000;padding:12px 20px;border-radius:10px;color:#fff;font-weight:500;font-size:.88rem;box-shadow:0 4px 16px rgba(0,0,0,.2);transform:translateX(120%);transition:transform .3s ease}
.rp-toast.show{transform:translateX(0)}
.rp-toast-success{background:#10b981}
.rp-toast-error{background:#ef4444}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="rp-content">
<div class="rp-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-clipboard-check me-2"></i>Requirement Clearance Portal</h1>
            <p>Track and verify student store item requirements — <?= htmlspecialchars($userName) ?></p>
        </div>
        <span class="badge bg-light text-dark" style="font-size:12px"><i class="fas fa-calendar"></i> <?= date('d M Y') ?></span>
    </div>
</div>

<div class="rp-stat-grid" id="statsGrid">
    <div class="rp-stat-card"><div class="rp-stat-num" id="statTotal">0</div><div class="rp-stat-lbl">Students Found</div></div>
    <div class="rp-stat-card"><div class="rp-stat-num" id="statCleared">0</div><div class="rp-stat-lbl">Fully Cleared</div></div>
    <div class="rp-stat-card"><div class="rp-stat-num" id="statPartial">0</div><div class="rp-stat-lbl">Partially Cleared</div></div>
    <div class="rp-stat-card"><div class="rp-stat-num" id="statItems">20</div><div class="rp-stat-lbl">Required Items</div></div>
</div>

<!-- Search & Filters -->
<div class="rp-card">
    <div class="rp-card-header" style="background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff">
        <h5 style="color:#fff;margin:0"><i class="fas fa-search me-2"></i>Search Students</h5>
    </div>
    <div class="rp-card-body">
        <div class="rp-search-row">
            <input type="text" id="searchInput" class="rp-search-input" placeholder="Type student name, admission number, or phone..." autofocus>
            <div class="d-flex gap-1">
                <button class="rp-filter-btn active" data-filter="all" onclick="setFilter('all',this)">All</button>
                <button class="rp-filter-btn" data-filter="name" onclick="setFilter('name',this)">Name</button>
                <button class="rp-filter-btn" data-filter="admission" onclick="setFilter('admission',this)">Adm #</button>
                <button class="rp-filter-btn" data-filter="phone" onclick="setFilter('phone',this)">Phone</button>
            </div>
        </div>
    </div>
</div>

<!-- Student Results -->
<div class="rp-card">
    <div class="rp-card-header">
        <h5><i class="fas fa-users me-2" style="color:#7c3aed"></i>Student Results</h5>
        <small class="text-muted" id="resultCount">Search for students above</small>
    </div>
    <div class="rp-card-body" id="studentResults">
        <div class="rp-empty">
            <i class="fas fa-search"></i>
            <p>Type at least 2 characters to search for students</p>
        </div>
    </div>
</div>

<!-- Clearance Detail Panel -->
<div class="rp-card" id="clearancePanel" style="display:none">
    <div class="rp-card-header" style="background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff">
        <h5 style="color:#fff;margin:0"><i class="fas fa-clipboard-check me-2"></i>Requirement Clearance — <span id="clearanceStudentName"></span></h5>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-dark" id="clearanceProgress">0/20</span>
            <button class="btn btn-sm btn-warning fw-semibold" onclick="verifyAll()"><i class="fas fa-check-double me-1"></i>Verify All</button>
        </div>
    </div>
    <div class="rp-card-body">
        <div class="rp-progress"><div class="rp-progress-bar" id="progressBar" style="width:0%"></div></div>
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <small class="text-muted">Click items to toggle cleared/pending status</small>
            <small class="text-muted" id="clearanceMeta"></small>
        </div>
        <div class="rp-req-grid" id="reqGrid"></div>
    </div>
</div>
</div>

<div class="rp-toast" id="toast"></div>

<script>
var currentFilter = 'all';
var currentStudentId = null;
var currentStudentName = '';
var currentRegNum = '';
var searchTimer = null;
var <?= 'var CSRF_TOKEN = "' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '";' ?>

function setFilter(f, btn) {
    currentFilter = f;
    document.querySelectorAll('.rp-filter-btn').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    doSearch();
}

document.getElementById('searchInput').addEventListener('keyup', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(doSearch, 300);
});

function doSearch() {
    var q = document.getElementById('searchInput').value.trim();
    if (q.length < 2) {
        document.getElementById('studentResults').innerHTML = '<div class="rp-empty"><i class="fas fa-search"></i><p>Type at least 2 characters to search for students</p></div>';
        document.getElementById('statTotal').textContent = '0';
        document.getElementById('statCleared').textContent = '0';
        document.getElementById('statPartial').textContent = '0';
        return;
    }
    var fd = new FormData();
    fd.append('action', 'search_students');
    fd.append('query', q);
    fd.append('filter', currentFilter);
    fd.append('csrf_token', CSRF_TOKEN);
    fetch('requirement-portal.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (!d.success) return;
        var students = d.students;
        document.getElementById('statTotal').textContent = students.length;
        document.getElementById('resultCount').textContent = students.length + ' student(s) found';
        if (students.length === 0) {
            document.getElementById('studentResults').innerHTML = '<div class="rp-empty"><i class="fas fa-user-slash"></i><p>No students found matching your search</p></div>';
            return;
        }
        var html = '';
        students.forEach(function(s) {
            var initials = ((s.first_name||'')[0]||'') + ((s.surname||'')[0]||'');
            var fullName = s.full_name || (s.first_name + ' ' + s.surname);
            html += '<div class="rp-student-row" data-id="'+s.id+'" data-name="'+escHtml(fullName)+'" data-reg="'+escHtml(s.student_number)+'" onclick="selectStudent('+s.id+',this)">' +
                '<div class="rp-student-avatar">'+escHtml(initials)+'</div>' +
                '<div class="rp-student-info">' +
                    '<div class="rp-student-name">'+escHtml(fullName)+'</div>' +
                    '<div class="rp-student-meta">'+escHtml(s.student_number)+' &middot; '+escHtml(s.program||'')+'&middot; '+escHtml(s.phone||'No phone')+'</div>' +
                '</div>' +
                '<span class="rp-student-badge rp-badge-none" id="badge-'+s.id+'">Loading...</span>' +
            '</div>';
        });
        document.getElementById('studentResults').innerHTML = html;
        students.forEach(function(s) { loadStudentBadge(s.id); });
    });
}

function loadStudentBadge(studentId) {
    var fd = new FormData();
    fd.append('action', 'get_student_requirements');
    fd.append('student_id', studentId);
    fd.append('csrf_token', CSRF_TOKEN);
    fetch('requirement-portal.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (!d.success) return;
        var cleared = 0;
        d.requirements.forEach(function(r){ if (r.status === 'verified') cleared++; });
        var badge = document.getElementById('badge-' + studentId);
        if (!badge) return;
        if (cleared >= 20) { badge.className = 'rp-student-badge rp-badge-cleared'; badge.textContent = 'Cleared ('+cleared+'/20)'; }
        else if (cleared > 0) { badge.className = 'rp-student-badge rp-badge-partial'; badge.textContent = cleared+'/20'; }
        else { badge.className = 'rp-student-badge rp-badge-none'; badge.textContent = '0/20'; }
    });
}

function selectStudent(studentId, el) {
    document.querySelectorAll('.rp-student-row').forEach(function(r){ r.classList.remove('selected'); });
    el.classList.add('selected');
    currentStudentId = studentId;
    currentStudentName = el.dataset.name;
    currentRegNum = el.dataset.reg;
    document.getElementById('clearanceStudentName').textContent = currentStudentName;
    document.getElementById('clearancePanel').style.display = '';
    document.getElementById('clearancePanel').scrollIntoView({ behavior: 'smooth', block: 'start' });
    loadClearanceDetail();
}

function loadClearanceDetail() {
    var fd = new FormData();
    fd.append('action', 'get_student_requirements');
    fd.append('student_id', currentStudentId);
    fd.append('csrf_token', CSRF_TOKEN);
    fetch('requirement-portal.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (!d.success) return;
        var reqMap = {};
        d.requirements.forEach(function(r){ reqMap[r.requirement_type] = r.status; });
        var html = '';
        var clearedCount = 0;
        var required = <?= json_encode($REQUIRED_ITEMS) ?>;
        required.forEach(function(item) {
            var status = reqMap[item] || 'pending';
            var isCleared = (status === 'verified');
            if (isCleared) clearedCount++;
            html += '<div class="rp-req-item'+(isCleared?' cleared':'')+'" onclick="toggleReq(\''+escAttr(item)+'\','+(isCleared?'"pending"':'"verified"')+')">' +
                '<div class="rp-check"><i class="fas fa-check"></i></div>' +
                '<span class="rp-req-label">'+escHtml(item)+'</span>' +
            '</div>';
        });
        document.getElementById('reqGrid').innerHTML = html;
        var pct = Math.round((clearedCount / 20) * 100);
        document.getElementById('progressBar').style.width = pct + '%';
        document.getElementById('clearanceProgress').textContent = clearedCount + '/20';
        document.getElementById('clearanceMeta').textContent = clearedCount >= 20 ? 'Fully cleared!' : (20 - clearedCount) + ' item(s) remaining';
        loadStudentBadge(currentStudentId);
    });
}

function toggleReq(reqType, newStatus) {
    if (!currentStudentId) return;
    var fd = new FormData();
    fd.append('action', 'toggle_requirement');
    fd.append('student_id', currentStudentId);
    fd.append('student_name', currentStudentName);
    fd.append('registration_number', currentRegNum);
    fd.append('requirement_type', reqType);
    fd.append('new_status', newStatus);
    fd.append('csrf_token', CSRF_TOKEN);
    fetch('requirement-portal.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (d.success) { showToast(reqType + ' → ' + newStatus, 'success'); loadClearanceDetail(); }
        else { showToast(d.message || 'Error', 'error'); }
    });
}

function verifyAll() {
    if (!currentStudentId) return;
    var required = <?= json_encode($REQUIRED_ITEMS) ?>;
    var fd = new FormData();
    fd.append('action', 'bulk_verify');
    fd.append('student_id', currentStudentId);
    fd.append('student_name', currentStudentName);
    fd.append('registration_number', currentRegNum);
    fd.append('csrf_token', CSRF_TOKEN);
    required.forEach(function(item){ fd.append('items[]', item); });
    fetch('requirement-portal.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (d.success) { showToast('All 20 items verified!', 'success'); loadClearanceDetail(); }
        else { showToast(d.message || 'Error', 'error'); }
    });
}

function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'rp-toast rp-toast-' + type + ' show';
    setTimeout(function(){ t.classList.remove('show'); }, 3000);
}

function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escAttr(s) { return (s||'').replace(/'/g, "\\'").replace(/"/g, '&quot;'); }
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
