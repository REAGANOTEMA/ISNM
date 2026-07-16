<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../config/config.php';
$ctx = bootstrapStaffDashboard(['director', 'academics', 'principal', 'head']);
$conn = $ctx['staff'];
$user = $ctx['user'];

generateCsrfToken();
$flash = getFlashMessages();

$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';

if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_curriculum_development` (id INT AUTO_INCREMENT PRIMARY KEY, program_code VARCHAR(50) NOT NULL, revision_number INT DEFAULT 1, academic_year VARCHAR(20) DEFAULT NULL, description TEXT, status VARCHAR(50) DEFAULT 'Draft', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_program (program_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`curriculum_development` (id INT AUTO_INCREMENT PRIMARY KEY, program_name VARCHAR(100) NOT NULL, course_code VARCHAR(50) NOT NULL, course_title VARCHAR(150) NOT NULL, credit_units INT DEFAULT 0, status VARCHAR(50) DEFAULT 'Draft', developed_by VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (!verifyCsrfToken()) {
        flashMessage('error', 'Invalid security token. Please try again.');
        header('Location: curriculum-management.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add_curriculum') {
        $program_name = trim($_POST['program_name'] ?? '');
        $course_code  = trim($_POST['course_code'] ?? '');
        $course_title = trim($_POST['course_title'] ?? '');
        $credit_units = (int)($_POST['credit_units'] ?? 0);
        $status       = trim($_POST['status'] ?? 'Draft');
        $developed_by = trim($_POST['developed_by'] ?? '');
        if ($program_name === '' || $course_code === '' || $course_title === '') {
            flashMessage('error', 'Program name, course code, and course title are required.');
        } else {
            $stmt = $conn->prepare("INSERT INTO curriculum_development (program_name, course_code, course_title, credit_units, status, developed_by) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sssiss', $program_name, $course_code, $course_title, $credit_units, $status, $developed_by);
                if ($stmt->execute()) {
                    flashMessage('success', 'Curriculum entry added successfully.');
                } else {
                    flashMessage('error', 'Failed to add curriculum entry.');
                }
                $stmt->close();
            } else {
                flashMessage('error', 'Database error: ' . $conn->error);
            }
        }
        header('Location: curriculum-management.php');
        exit;
    }
    if ($action === 'update_curriculum') {
        $id           = (int)($_POST['id'] ?? 0);
        $status       = trim($_POST['status'] ?? '');
        $credit_units = (int)($_POST['credit_units'] ?? 0);
        if ($id <= 0) {
            flashMessage('error', 'Invalid curriculum entry ID.');
        } else {
            $stmt = $conn->prepare("UPDATE curriculum_development SET status=?, credit_units=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('sii', $status, $credit_units, $id);
                if ($stmt->execute()) {
                    flashMessage('success', 'Curriculum entry updated successfully.');
                } else {
                    flashMessage('error', 'Failed to update curriculum entry.');
                }
                $stmt->close();
            } else {
                flashMessage('error', 'Database error: ' . $conn->error);
            }
        }
        header('Location: curriculum-management.php');
        exit;
    }
    if ($action === 'delete_curriculum') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flashMessage('error', 'Invalid curriculum entry ID.');
        } else {
            $stmt = $conn->prepare("DELETE FROM curriculum_development WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    flashMessage('success', 'Curriculum entry deleted.');
                } else {
                    flashMessage('error', 'Failed to delete curriculum entry.');
                }
                $stmt->close();
            } else {
                flashMessage('error', 'Database error: ' . $conn->error);
            }
        }
        header('Location: curriculum-management.php');
        exit;
    }
}

$curricula = [];
if ($conn) {
    $r = $conn->query("SELECT c.*, p.program_name FROM academic_curriculum_development c LEFT JOIN academic_programs p ON c.program_code = p.program_code ORDER BY c.created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $curricula[] = $row;
}
$courses = [];
if ($conn) {
    $r = $conn->query("SELECT course_code, course_title, credits, program_code FROM academic_course_catalog ORDER BY course_title LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $courses[] = $row;
}
$curriculumEntries = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM curriculum_development ORDER BY created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $curriculumEntries[] = $row;
}
$pageTitle = 'Curriculum Management';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.modal-backdrop{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center}
.modal-backdrop.show{display:flex}
.modal-box{background:#fff;border-radius:12px;width:95%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,.2)}
.modal-box .modal-head{padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center}
.modal-box .modal-head h5{margin:0;font-size:1.1rem}
.modal-box .modal-body{padding:20px}
.modal-box .modal-foot{padding:12px 20px;border-top:1px solid #e2e8f0;text-align:right}
.flash-msg{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.9rem}
.flash-msg.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
.flash-msg.error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-book-open me-2"></i>Curriculum Management <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button></h2><p>Develop and manage academic curriculum, course catalogs, and program structures</p></div>

<?php foreach ($flash as $type => $message): ?>
<div class="flash-msg <?= $type ?>"><?= htmlspecialchars($message) ?></div>
<?php endforeach; ?>

<div class="row g-4">
<div class="col-lg-7"><div class="card"><div class="card-header">Curriculum Development</div><div class="card-body">
<?php if (empty($curricula)): ?><div class="empty-state"><i class="fas fa-book"></i><p>No curriculum entries yet.</p></div>
<?php else: ?>
<div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchHZLE" type="text" placeholder="Search..." onkeyup="filterTable('srchHZLE','tblHZLE')"></div>
<div class="table-responsive"><table id="tblHZLE" class="table table-hover"><thead><tr><th>Program</th><th>Revision</th><th>Academic Year</th><th>Status</th><th>Created</th></tr></thead><tbody>
<?php foreach ($curricula as $c): ?>
<tr><td><?= htmlspecialchars($c['program_name']??$c['program_code']) ?></td><td>v<?= (int)($c['revision_number']??1) ?></td><td><?= htmlspecialchars($c['academic_year']??'') ?></td><td><span class="status-pill <?= ($c['status']??'Draft') === 'Approved' ? 'success' : (($c['status']??'') === 'Implemented' ? 'info' : 'warning') ?>"><?= htmlspecialchars($c['status']??'Draft') ?></span></td><td class="small"><?= htmlspecialchars($c['created_at']??'') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-header">Course Catalog (<?= count($courses) ?>)</div><div class="card-body" style="max-height:400px;overflow-y:auto">
<?php if (empty($courses)): ?><p class="text-muted small text-center py-3">No courses defined.</p>
<?php else: ?>
<?php foreach ($courses as $c): ?>
<div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
<div><strong class="small"><?= htmlspecialchars($c['course_code']) ?></strong><br><span class="text-muted small"><?= htmlspecialchars($c['course_title']) ?></span></div>
<span class="badge bg-primary"><?= (int)$c['credits'] ?> cr</span>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></div></div></div>

<div class="card mb-4 mt-4"><div class="card-header d-flex justify-content-between align-items-center">Curriculum Entries <span class="badge bg-secondary"><?= count($curriculumEntries) ?></span> <button class="btn btn-sm btn-primary" onclick="openModal('addCurriculumModal')"><i class="fas fa-plus me-1"></i>Add Entry</button></div><div class="card-body">
<?php if (empty($curriculumEntries)): ?><div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No curriculum entries yet. Click "Add Entry" to create one.</p></div>
<?php else: ?>
<div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchCurDev" type="text" placeholder="Search entries..." onkeyup="filterTable('srchCurDev','tblCurDev')"></div>
<div class="table-responsive"><table id="tblCurDev" class="table table-hover"><thead><tr><th>Program</th><th>Course Code</th><th>Course Title</th><th>Units</th><th>Status</th><th>Developed By</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($curriculumEntries as $ce): ?>
<tr>
<td class="small"><?= htmlspecialchars($ce['program_name']) ?></td>
<td><strong class="small"><?= htmlspecialchars($ce['course_code']) ?></strong></td>
<td class="small"><?= htmlspecialchars($ce['course_title']) ?></td>
<td class="text-center"><?= (int)$ce['credit_units'] ?></td>
<td><span class="status-pill <?= ($ce['status']??'Draft') === 'Active' ? 'success' : (($ce['status']??'') === 'Archived' ? 'secondary' : (($ce['status']??'') === 'Under Review' ? 'warning' : 'info')) ?>"><?= htmlspecialchars($ce['status']??'Draft') ?></span></td>
<td class="small"><?= htmlspecialchars($ce['developed_by']??'') ?></td>
<td>
<button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(this)" data-id="<?= (int)$ce['id'] ?>" data-status="<?= htmlspecialchars($ce['status']??'Draft') ?>" data-credit_units="<?= (int)$ce['credit_units'] ?>"><i class="fas fa-edit"></i></button>
<form method="POST" action="curriculum-management.php" style="display:inline" onsubmit="return confirm('Delete this entry?')">
<?php csrfField(); ?>
<input type="hidden" name="action" value="delete_curriculum">
<input type="hidden" name="id" value="<?= (int)$ce['id'] ?>">
<button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div>
</div>

<div class="modal-backdrop" id="addCurriculumModal">
<div class="modal-box">
<div class="modal-head"><h5><i class="fas fa-plus-circle me-2"></i>Add Curriculum Entry</h5><button class="btn-close" onclick="closeModal('addCurriculumModal')">&times;</button></div>
<div class="modal-body">
<form method="POST" action="curriculum-management.php">
<?php csrfField(); ?>
<input type="hidden" name="action" value="add_curriculum">
<div class="mb-3">
<label class="form-label fw-bold">Program Name <span class="text-danger">*</span></label>
<input type="text" name="program_name" class="form-control" required placeholder="e.g. BSN, Midwifery">
</div>
<div class="mb-3">
<label class="form-label fw-bold">Course Code <span class="text-danger">*</span></label>
<input type="text" name="course_code" class="form-control" required placeholder="e.g. NURS-301">
</div>
<div class="mb-3">
<label class="form-label fw-bold">Course Title <span class="text-danger">*</span></label>
<input type="text" name="course_title" class="form-control" required placeholder="e.g. Community Health Nursing">
</div>
<div class="mb-3">
<label class="form-label fw-bold">Credit Units</label>
<input type="number" name="credit_units" class="form-control" min="0" max="20" value="3">
</div>
<div class="mb-3">
<label class="form-label fw-bold">Status</label>
<select name="status" class="form-select">
<option value="Draft">Draft</option>
<option value="Active">Active</option>
<option value="Under Review">Under Review</option>
<option value="Archived">Archived</option>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-bold">Developed By</label>
<input type="text" name="developed_by" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Developer name">
</div>
</div>
<div class="modal-foot">
<button type="button" class="btn btn-secondary" onclick="closeModal('addCurriculumModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Entry</button>
</div>
</form>
</div>
</div>

<div class="modal-backdrop" id="editCurriculumModal">
<div class="modal-box">
<div class="modal-head"><h5><i class="fas fa-edit me-2"></i>Edit Curriculum Entry</h5><button class="btn-close" onclick="closeModal('editCurriculumModal')">&times;</button></div>
<div class="modal-body">
<form method="POST" action="curriculum-management.php">
<?php csrfField(); ?>
<input type="hidden" name="action" value="update_curriculum">
<input type="hidden" name="id" id="editCurId">
<div class="mb-3">
<label class="form-label fw-bold">Status</label>
<select name="status" id="editCurStatus" class="form-select">
<option value="Draft">Draft</option>
<option value="Active">Active</option>
<option value="Under Review">Under Review</option>
<option value="Archived">Archived</option>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-bold">Credit Units</label>
<input type="number" name="credit_units" id="editCurUnits" class="form-control" min="0" max="20">
</div>
</div>
<div class="modal-foot">
<button type="button" class="btn btn-secondary" onclick="closeModal('editCurriculumModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Entry</button>
</div>
</form>
</div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
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
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function openEditModal(btn) {
    document.getElementById('editCurId').value = btn.dataset.id;
    document.getElementById('editCurStatus').value = btn.dataset.status;
    document.getElementById('editCurUnits').value = btn.dataset.credit_units;
    openModal('editCurriculumModal');
}
document.querySelectorAll('.modal-backdrop').forEach(function(el) {
    el.addEventListener('click', function(e) { if (e.target === el) el.classList.remove('show'); });
});
</script>
</body></html>
