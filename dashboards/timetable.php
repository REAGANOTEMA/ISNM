<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar', 'academics', 'lecturer', 'head']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$pageTitle = 'Timetable Management';

$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';

if ($staffDb) {
    $staffDb->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_timetable` (id INT AUTO_INCREMENT PRIMARY KEY, timetable_id VARCHAR(50) UNIQUE, academic_year VARCHAR(20) DEFAULT NULL, semester VARCHAR(100) DEFAULT NULL, program_code VARCHAR(50) DEFAULT '', course_code VARCHAR(50) DEFAULT '', day_of_week VARCHAR(20) NOT NULL, start_time TIME NULL, end_time TIME NULL, venue VARCHAR(200) DEFAULT '', lecturer_id INT DEFAULT 0, timetable_status VARCHAR(50) DEFAULT 'Draft', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_day (day_of_week), KEY idx_lecturer (lecturer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$staffUser = $ctx['staff_user'] ?? null;
$userId = $staffUser['id'] ?? 0;

$conn = $staffDb;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';
    $token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';

    if ($token !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
        exit;
    }

    try {
        switch ($action) {
            case 'add_entry':
                $timetableId = 'TT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $academicYear = trim($input['academic_year'] ?? '');
                $semester = trim($input['semester'] ?? '');
                $programCode = trim($input['program_code'] ?? '');
                $courseCode = trim($input['course_code'] ?? '');
                $dayOfWeek = trim($input['day_of_week'] ?? '');
                $startTime = trim($input['start_time'] ?? '');
                $endTime = trim($input['end_time'] ?? '');
                $venue = trim($input['venue'] ?? '');
                $lecturerId = (int)($input['lecturer_id'] ?? 0);
                $status = trim($input['timetable_status'] ?? 'Draft');

                if (!$dayOfWeek || !$startTime || !$endTime) {
                    echo json_encode(['success' => false, 'error' => 'Day, start time, and end time are required.']);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO academic_timetable (timetable_id, academic_year, semester, program_code, course_code, day_of_week, start_time, end_time, venue, lecturer_id, timetable_status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssssssssi', $timetableId, $academicYear, $semester, $programCode, $courseCode, $dayOfWeek, $startTime, $endTime, $venue, $lecturerId, $status, $userId);
                $stmt->execute();
                $newId = $stmt->insert_id;
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Entry added.', 'id' => $newId]);
                break;

            case 'update_entry':
                $id = (int)($input['id'] ?? 0);
                if (!$id) {
                    echo json_encode(['success' => false, 'error' => 'Missing entry ID.']);
                    exit;
                }
                $academicYear = trim($input['academic_year'] ?? '');
                $semester = trim($input['semester'] ?? '');
                $programCode = trim($input['program_code'] ?? '');
                $courseCode = trim($input['course_code'] ?? '');
                $dayOfWeek = trim($input['day_of_week'] ?? '');
                $startTime = trim($input['start_time'] ?? '');
                $endTime = trim($input['end_time'] ?? '');
                $venue = trim($input['venue'] ?? '');
                $lecturerId = (int)($input['lecturer_id'] ?? 0);
                $status = trim($input['timetable_status'] ?? 'Draft');

                if (!$dayOfWeek || !$startTime || !$endTime) {
                    echo json_encode(['success' => false, 'error' => 'Day, start time, and end time are required.']);
                    exit;
                }

                $stmt = $conn->prepare("UPDATE academic_timetable SET academic_year=?, semester=?, program_code=?, course_code=?, day_of_week=?, start_time=?, end_time=?, venue=?, lecturer_id=?, timetable_status=? WHERE id=?");
                $stmt->bind_param('sssssssssii', $academicYear, $semester, $programCode, $courseCode, $dayOfWeek, $startTime, $endTime, $venue, $lecturerId, $status, $id);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
                echo json_encode(['success' => true, 'message' => $affected > 0 ? 'Entry updated.' : 'No changes made.', 'affected' => $affected]);
                break;

            case 'delete_entry':
                $id = (int)($input['id'] ?? 0);
                if (!$id) {
                    echo json_encode(['success' => false, 'error' => 'Missing entry ID.']);
                    exit;
                }
                $stmt = $conn->prepare("DELETE FROM academic_timetable WHERE id=?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
                echo json_encode(['success' => $affected > 0, 'message' => $affected > 0 ? 'Entry deleted.' : 'Entry not found.']);
                break;

            case 'search':
                $q = trim($input['query'] ?? '');
                if (strlen($q) < 1) {
                    echo json_encode(['success' => false, 'error' => 'Search query too short.']);
                    exit;
                }
                $like = "%{$q}%";
                $stmt = $conn->prepare("SELECT t.*, s.full_name as instructor FROM academic_timetable t LEFT JOIN staff s ON t.lecturer_id=s.id WHERE t.course_code LIKE ? OR t.venue LIKE ? OR t.day_of_week LIKE ? OR s.full_name LIKE ? OR t.semester LIKE ? OR t.program_code LIKE ? OR t.timetable_id LIKE ? ORDER BY FIELD(t.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), t.start_time LIMIT 50");
                $stmt->bind_param('sssssss', $like, $like, $like, $like, $like, $like, $like);
                $stmt->execute();
                $result = $stmt->get_result();
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $row['start_time'] = $row['start_time'] ? date('H:i', strtotime($row['start_time'])) : '';
                    $row['end_time'] = $row['end_time'] ? date('H:i', strtotime($row['end_time'])) : '';
                    $rows[] = $row;
                }
                $stmt->close();
                echo json_encode(['success' => true, 'data' => $rows]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action.']);
        }
    } catch (Exception $e) {
        error_log('timetable CRUD error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error. Please try again.']);
    }
    exit;
}

$totalSlots = $byDay = $byRoom = 0;
$days = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) as c FROM academic_timetable");
        if ($r) $totalSlots = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(DISTINCT day_of_week) as c FROM academic_timetable");
        if ($r) $byDay = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(DISTINCT venue) as c FROM academic_timetable WHERE venue != ''");
        if ($r) $byRoom = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT t.id, t.timetable_id, t.day_of_week, CONCAT(t.start_time, ' - ', t.end_time) as time_slot, t.course_code, t.venue as room, t.start_time as raw_start, t.end_time as raw_end, s.full_name as instructor, t.academic_year, t.semester, t.program_code, t.lecturer_id, t.timetable_status FROM academic_timetable t LEFT JOIN staff s ON t.lecturer_id=s.id ORDER BY FIELD(t.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), t.start_time LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) {
            $d = $row['day_of_week'] ?? 'Unknown';
            if (!isset($days[$d])) $days[$d] = [];
            $days[$d][] = $row;
        }
    } catch (Exception $e) { error_log('timetable context: ' . $e->getMessage()); }
}
$dayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$lecturers = [];
if ($conn) {
    $lr = $conn->query("SELECT id, full_name FROM staff ORDER BY full_name");
    if ($lr) while ($l = $lr->fetch_assoc()) $lecturers[] = $l;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.modal-backdrop { position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1040;display:none;align-items:center;justify-content:center; }
.modal-backdrop.show { display:flex; }
.crud-modal { background:#fff;border-radius:12px;width:90%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,0.18); }
.crud-modal .modal-header { padding:16px 24px;border-bottom:1px solid #e9ecef;display:flex;justify-content:space-between;align-items:center; }
.crud-modal .modal-header h5 { margin:0;font-weight:700; }
.crud-modal .modal-body { padding:24px; }
.crud-modal .modal-footer { padding:12px 24px;border-top:1px solid #e9ecef;display:flex;gap:8px;justify-content:flex-end; }
.btn-row { display:flex;gap:6px; }
.btn-icon { width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;cursor:pointer;font-size:12px; }
.btn-icon.edit { background:#e7f1ff;color:#0d6efd; }
.btn-icon.edit:hover { background:#cfe2ff; }
.btn-icon.delete { background:#ffe5e5;color:#dc3545; }
.btn-icon.delete:hover { background:#fecdd3; }
#globalSearch { max-width:320px; }
.search-results-panel { display:none;margin-bottom:24px; }
.search-results-panel.active { display:block; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-calendar-alt me-2"></i>Timetable Management</h4>
        <div class="d-flex align-items-center gap-2">
            <input type="text" class="form-control form-control-sm" id="globalSearch" placeholder="Search timetable..." onkeyup="handleGlobalSearch()">
            <button class="btn btn-primary btn-sm" onclick="openAddModal()"><i class="fas fa-plus me-1"></i>Add Entry</button>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i></button>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>
    </div>

    <div id="searchResults" class="search-results-panel">
        <div class="content-section">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0">Search Results</h6>
                <button class="btn btn-sm btn-outline-secondary" onclick="closeSearchResults()"><i class="fas fa-times me-1"></i>Close</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead><tr><th>Day</th><th>Time</th><th>Course</th><th>Instructor</th><th>Room</th><th>Semester</th><th>Actions</th></tr></thead>
                    <tbody id="searchResultsBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $totalSlots ?></h3><p>Total Slots</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-calendar-day"></i></div><div class="stat-content"><h3><?= $byDay ?></h3><p>Days Active</p></div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-door-open"></i></div><div class="stat-content"><h3><?= $byRoom ?></h3><p>Rooms Used</p></div></div>
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div><div class="stat-content"><h3><?= array_sum(array_map('count',$days)) ?></h3><p>Total Sessions</p></div></div>
    </div>
    <?php if (empty($days)): ?>
    <div class="content-section"><p class="text-center text-muted my-3">No timetable entries found.</p></div>
    <?php else: ?>
    <?php foreach ($dayOrder as $day): if (!isset($days[$day])) continue; ?>
    <div class="content-section mb-3">
        <h5 class="fw-bold mb-3"><i class="fas fa-calendar-day me-2"></i><?= htmlspecialchars($day) ?></h5>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchUSDL_<?= md5($day) ?>" type="text" placeholder="Search..." onkeyup="filterTable('srchUSDL_<?= md5($day) ?>','tblUSDL_<?= md5($day) ?>')"></div>
        <div class="table-responsive">
            <table id="tblUSDL_<?= md5($day) ?>" class="table table-striped table-hover mb-0">
                <thead><tr><th>Time</th><th>Course</th><th>Instructor</th><th>Room</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($days[$day] as $t): ?>
                    <tr data-id="<?= (int)$t['id'] ?>" data-timetable-id="<?= htmlspecialchars($t['timetable_id'] ?? '') ?>" data-academic-year="<?= htmlspecialchars($t['academic_year'] ?? '') ?>" data-semester="<?= htmlspecialchars($t['semester'] ?? '') ?>" data-program-code="<?= htmlspecialchars($t['program_code'] ?? '') ?>" data-course-code="<?= htmlspecialchars($t['course_code'] ?? '') ?>" data-day="<?= htmlspecialchars($t['day_of_week'] ?? '') ?>" data-start="<?= htmlspecialchars($t['raw_start'] ?? '') ?>" data-end="<?= htmlspecialchars($t['raw_end'] ?? '') ?>" data-venue="<?= htmlspecialchars($t['room'] ?? '') ?>" data-lecturer-id="<?= (int)($t['lecturer_id'] ?? 0) ?>" data-status="<?= htmlspecialchars($t['timetable_status'] ?? 'Draft') ?>">
                        <td><?= htmlspecialchars($t['time_slot'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['course_code'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['instructor'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($t['room'] ?? '-') ?></td>
                        <td>
                            <div class="btn-row">
                                <button class="btn-icon edit" title="Edit" onclick="openEditModal(this.closest('tr'))"><i class="fas fa-pen"></i></button>
                                <button class="btn-icon delete" title="Delete" onclick="deleteEntry(this.closest('tr'))"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal-backdrop" id="entryModal">
    <div class="crud-modal">
        <div class="modal-header">
            <h5 id="modalTitle">Add Timetable Entry</h5>
            <button class="btn-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="entryId" value="">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Course Code *</label>
                    <input type="text" class="form-control" id="fCourseCode" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Program Code</label>
                    <input type="text" class="form-control" id="fProgramCode">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Day of Week *</label>
                    <select class="form-select" id="fDayOfWeek" required>
                        <option value="">Select Day</option>
                        <?php foreach ($dayOrder as $d): ?>
                        <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Lecturer</label>
                    <select class="form-select" id="fLecturerId">
                        <option value="0">-- None --</option>
                        <?php foreach ($lecturers as $lec): ?>
                        <option value="<?= (int)$lec['id'] ?>"><?= htmlspecialchars($lec['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Start Time *</label>
                    <input type="time" class="form-control" id="fStartTime" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">End Time *</label>
                    <input type="time" class="form-control" id="fEndTime" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Venue / Room</label>
                    <input type="text" class="form-control" id="fVenue">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Academic Year</label>
                    <input type="text" class="form-control" id="fAcademicYear" placeholder="e.g. 2025/2026">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Semester</label>
                    <input type="text" class="form-control" id="fSemester" placeholder="e.g. First Semester">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" id="fStatus">
                        <option value="Draft">Draft</option>
                        <option value="Published">Published</option>
                        <option value="Archived">Archived</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary btn-sm" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary btn-sm" id="modalSaveBtn" onclick="saveEntry()"><i class="fas fa-check me-1"></i>Save</button>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
var CSRF = '<?= $_SESSION['csrf_token'] ?>';

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

var searchTimer = null;
function handleGlobalSearch() {
    clearTimeout(searchTimer);
    var q = document.getElementById('globalSearch').value.trim();
    if (q.length < 2) { closeSearchResults(); return; }
    searchTimer = setTimeout(function() {
        fetchTimetable({ action: 'search', query: q, csrf_token: CSRF }, function(res) {
            var panel = document.getElementById('searchResults');
            var body = document.getElementById('searchResultsBody');
            body.innerHTML = '';
            if (res.success && res.data.length > 0) {
                res.data.forEach(function(r) {
                    var tr = document.createElement('tr');
                    tr.setAttribute('data-id', r.id);
                    tr.setAttribute('data-timetable-id', r.timetable_id || '');
                    tr.setAttribute('data-academic-year', r.academic_year || '');
                    tr.setAttribute('data-semester', r.semester || '');
                    tr.setAttribute('data-program-code', r.program_code || '');
                    tr.setAttribute('data-course-code', r.course_code || '');
                    tr.setAttribute('data-day', r.day_of_week || '');
                    tr.setAttribute('data-start', r.start_time || '');
                    tr.setAttribute('data-end', r.end_time || '');
                    tr.setAttribute('data-venue', r.venue || '');
                    tr.setAttribute('data-lecturer-id', r.lecturer_id || '0');
                    tr.setAttribute('data-status', r.timetable_status || 'Draft');
                    tr.innerHTML = '<td>' + esc(r.day_of_week) + '</td><td>' + esc(r.start_time) + ' - ' + esc(r.end_time) + '</td><td>' + esc(r.course_code) + '</td><td>' + esc(r.instructor || '-') + '</td><td>' + esc(r.venue || '-') + '</td><td>' + esc(r.semester || '-') + '</td><td><div class="btn-row"><button class="btn-icon edit" title="Edit" onclick="openEditModal(this.closest(\'tr\'))"><i class="fas fa-pen"></i></button><button class="btn-icon delete" title="Delete" onclick="deleteEntry(this.closest(\'tr\'))"><i class="fas fa-trash"></i></button></div></td>';
                    body.appendChild(tr);
                });
                panel.classList.add('active');
            } else {
                body.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No results found.</td></tr>';
                panel.classList.add('active');
            }
        });
    }, 300);
}

function closeSearchResults() {
    document.getElementById('searchResults').classList.remove('active');
}

function esc(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s || ''));
    return d.innerHTML;
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Timetable Entry';
    document.getElementById('entryId').value = '';
    document.getElementById('fCourseCode').value = '';
    document.getElementById('fProgramCode').value = '';
    document.getElementById('fDayOfWeek').value = '';
    document.getElementById('fLecturerId').value = '0';
    document.getElementById('fStartTime').value = '';
    document.getElementById('fEndTime').value = '';
    document.getElementById('fVenue').value = '';
    document.getElementById('fAcademicYear').value = '';
    document.getElementById('fSemester').value = '';
    document.getElementById('fStatus').value = 'Draft';
    document.getElementById('entryModal').classList.add('show');
}

function openEditModal(row) {
    document.getElementById('modalTitle').textContent = 'Edit Timetable Entry';
    document.getElementById('entryId').value = row.getAttribute('data-id') || '';
    document.getElementById('fCourseCode').value = row.getAttribute('data-course-code') || '';
    document.getElementById('fProgramCode').value = row.getAttribute('data-program-code') || '';
    document.getElementById('fDayOfWeek').value = row.getAttribute('data-day') || '';
    document.getElementById('fLecturerId').value = row.getAttribute('data-lecturer-id') || '0';
    document.getElementById('fStartTime').value = row.getAttribute('data-start') || '';
    document.getElementById('fEndTime').value = row.getAttribute('data-end') || '';
    document.getElementById('fVenue').value = row.getAttribute('data-venue') || '';
    document.getElementById('fAcademicYear').value = row.getAttribute('data-academic-year') || '';
    document.getElementById('fSemester').value = row.getAttribute('data-semester') || '';
    document.getElementById('fStatus').value = row.getAttribute('data-status') || 'Draft';
    document.getElementById('entryModal').classList.add('show');
}

function closeModal() {
    document.getElementById('entryModal').classList.remove('show');
}

function getFormData() {
    return {
        id: document.getElementById('entryId').value,
        course_code: document.getElementById('fCourseCode').value.trim(),
        program_code: document.getElementById('fProgramCode').value.trim(),
        day_of_week: document.getElementById('fDayOfWeek').value,
        lecturer_id: parseInt(document.getElementById('fLecturerId').value) || 0,
        start_time: document.getElementById('fStartTime').value,
        end_time: document.getElementById('fEndTime').value,
        venue: document.getElementById('fVenue').value.trim(),
        academic_year: document.getElementById('fAcademicYear').value.trim(),
        semester: document.getElementById('fSemester').value.trim(),
        timetable_status: document.getElementById('fStatus').value,
        csrf_token: CSRF
    };
}

function saveEntry() {
    var data = getFormData();
    if (!data.day_of_week || !data.start_time || !data.end_time) {
        alert('Day, start time, and end time are required.');
        return;
    }
    var action = data.id ? 'update_entry' : 'add_entry';
    data.action = action;

    var btn = document.getElementById('modalSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    fetchTimetable(data, function(res) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Save';
        if (res.success) {
            closeModal();
            location.reload();
        } else {
            alert(res.error || 'Failed to save entry.');
        }
    });
}

function deleteEntry(row) {
    var id = row.getAttribute('data-id');
    var course = row.getAttribute('data-course-code') || 'this entry';
    if (!confirm('Delete "' + course + '"? This cannot be undone.')) return;

    fetchTimetable({ action: 'delete_entry', id: parseInt(id), csrf_token: CSRF }, function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert(res.error || 'Failed to delete entry.');
        }
    });
}

function fetchTimetable(data, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.href, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                callback(JSON.parse(xhr.responseText));
            } catch(e) {
                alert('Server error. Please try again.');
            }
        }
    };
    xhr.send(JSON.stringify(data));
}

document.getElementById('entryModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
