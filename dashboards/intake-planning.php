<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['admissions', 'director', 'registrar', 'academic registrar', 'director general']);
$conn = $ctx['staff'] ?? null;
$studentsDb = $ctx['students'] ?? null;

// CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh the page and try again.']); exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add_plan' && $conn) {
        $program_name   = trim($_POST['program_name'] ?? '');
        $target_students = (int)($_POST['target_students'] ?? 0);
        $academic_year  = trim($_POST['academic_year'] ?? '');
        $semester       = trim($_POST['semester'] ?? '');
        $status         = trim($_POST['status'] ?? 'Planning');
        $notes          = trim($_POST['notes'] ?? '');

        if ($program_name && $academic_year && $semester) {
            $stmt = $conn->prepare("INSERT INTO intake_plans (program_name, target_students, academic_year, semester, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sissss', $program_name, $target_students, $academic_year, $semester, $status, $notes);
                $stmt->execute() ? $_SESSION['success'] = 'Intake plan added.' : $_SESSION['error'] = 'Failed to add plan: ' . $stmt->error;
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = 'Please fill in all required fields.';
        }
        header('Location: intake-planning.php'); exit;
    }

    if ($action === 'update_plan' && $conn) {
        $id             = (int)($_POST['id'] ?? 0);
        $target_students = (int)($_POST['target_students'] ?? 0);
        $status         = trim($_POST['status'] ?? '');
        $notes          = trim($_POST['notes'] ?? '');

        if ($id && $target_students && $status) {
            $stmt = $conn->prepare("UPDATE intake_plans SET target_students=?, status=?, notes=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('issi', $target_students, $status, $notes, $id);
                $stmt->execute() ? $_SESSION['success'] = 'Intake plan updated.' : $_SESSION['error'] = 'Failed to update plan: ' . $stmt->error;
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = 'Please fill in all required fields.';
        }
        header('Location: intake-planning.php'); exit;
    }

    if ($action === 'delete_plan' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM intake_plans WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute() ? $_SESSION['success'] = 'Intake plan deleted.' : $_SESSION['error'] = 'Failed to delete plan.';
                $stmt->close();
            }
        }
        header('Location: intake-planning.php'); exit;
    }
}

// Ensure intake_plans table exists
if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS intake_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_name VARCHAR(255) NOT NULL,
        target_students INT DEFAULT 0,
        academic_year VARCHAR(20) NOT NULL,
        semester VARCHAR(50) NOT NULL,
        status ENUM('Planning','Active','Completed','Cancelled') DEFAULT 'Planning',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed sample data if table is empty
    $r = $conn->query("SELECT COUNT(*) AS c FROM intake_plans"); $count = $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;
    if ((int)$count === 0) {
        $seeds = [
            ['Certificate in Nursing', 60, '2026', 'Semester 1', 'Active'],
            ['Certificate in Midwifery', 40, '2026', 'Semester 1', 'Active'],
            ['Diploma in Nursing - Extension', 30, '2026', 'Semester 1', 'Planning'],
            ['Certificate in Nursing', 75, '2025', 'Semester 2', 'Completed'],
        ];
        $ins = $conn->prepare("INSERT INTO intake_plans (program_name, target_students, academic_year, semester, status) VALUES (?, ?, ?, ?, ?)");
        if ($ins) {
            foreach ($seeds as $s) { $ins->bind_param('sisss', $s[0], $s[1], $s[2], $s[3], $s[4]); $ins->execute(); }
            $ins->close();
        }
    }
}

$intakes = [];
if ($studentsDb) {
    $r = $studentsDb->query("SELECT YEAR(admission_date) AS intake_year, program, COUNT(*) AS student_count FROM students GROUP BY YEAR(admission_date), program ORDER BY intake_year DESC, student_count DESC");
    if ($r) while ($row = $r->fetch_assoc()) $intakes[] = $row;
}
$programs = [];
if ($conn) {
    $r = $conn->query("SELECT id, program_name, program_code, duration_years FROM academic_programs WHERE status='Active' ORDER BY program_name");
    if ($r) while ($row = $r->fetch_assoc()) $programs[] = $row;
}

// Fetch plans
$plans = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM intake_plans ORDER BY created_at DESC");
    if ($r) while ($row = $r->fetch_assoc()) $plans[] = $row;
}

$pageTitle = 'Intake Planning';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.plan-card { border-left: 4px solid transparent; transition: all 0.3s ease; }
.plan-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.plan-card.status-Planning { border-left-color: #ffc107; }
.plan-card.status-Active { border-left-color: #28a745; }
.plan-card.status-Completed { border-left-color: #0d6efd; }
.plan-card.status-Cancelled { border-left-color: #dc3545; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="page-title-card">
    <h2><i class="fas fa-calendar-plus me-2"></i>Intake Planning <span class="fs-6 fw-normal text-muted ms-2"><?= count($plans) ?> plan(s)</span>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button>
    <button class="btn btn-sm btn-primary ms-2" onclick="showAddModal()"><i class="fas fa-plus"></i> Add Plan</button>
    </h2>
    <p>Plan and manage student intakes across programs</p>
</div>

<?php if (!empty($plans)): ?>
<div class="mb-3">
    <input class="form-control form-control-sm" style="max-width:300px" id="srchPlans" type="text" placeholder="Search plans..." onkeyup="filterTable('srchPlans','tblPlans')">
</div>
<div class="card mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tblPlans" class="table table-hover mb-0">
                <thead class="table-light"><tr>
                    <th>Program</th><th>Target</th><th>Academic Year</th><th>Semester</th><th>Status</th><th>Notes</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($plans as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['program_name']) ?></strong></td>
                    <td><span class="badge bg-primary"><?= (int)$p['target_students'] ?></span></td>
                    <td><?= htmlspecialchars($p['academic_year']) ?></td>
                    <td><?= htmlspecialchars($p['semester']) ?></td>
                    <td><span class="badge bg-<?= $p['status'] === 'Active' ? 'success' : ($p['status'] === 'Completed' ? 'info' : ($p['status'] === 'Cancelled' ? 'danger' : 'warning')) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                    <td class="text-muted small"><?= htmlspecialchars(mb_substr($p['notes'] ?? '', 0, 60)) ?: '—' ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editPlan(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="fas fa-edit"></i></button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this intake plan?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="delete_plan">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
<div class="col-md-8"><div class="card"><div class="card-header">Intake History</div><div class="card-body">
<?php if (empty($intakes)): ?><div class="empty-state"><i class="fas fa-database"></i><p>No intake data available.</p></div>
<?php else: ?>
<div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchACGM" type="text" placeholder="Search..." onkeyup="filterTable('srchACGM','tblACGM')"></div>
<div class="table-responsive"><table id="tblACGM" class="table table-hover"><thead><tr><th>Year</th><th>Program</th><th>Students</th></tr></thead><tbody>
<?php foreach ($intakes as $i): ?>
<tr><td><strong><?= htmlspecialchars($i['intake_year']) ?></strong></td><td><?= htmlspecialchars($i['program']) ?></td><td><span class="badge bg-primary"><?= (int)$i['student_count'] ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-md-4"><div class="card"><div class="card-header">Active Programs</div><div class="card-body">
<?php if (empty($programs)): ?><p class="text-muted small">No programs configured.</p>
<?php else: ?>
<?php foreach ($programs as $p): ?>
<div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
<div><strong class="small"><?= htmlspecialchars($p['program_name']) ?></strong><br><span class="text-muted small"><?= htmlspecialchars($p['program_code']) ?> &middot; <?= (int)$p['duration_years'] ?> yrs</span></div>
<span class="badge bg-info"><?= htmlspecialchars($p['program_code']) ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></div></div></div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="planModalTitle"><i class="fas fa-plus-circle me-2"></i>Add Intake Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="planForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="modal-body">
                    <input type="hidden" name="action" id="planAction" value="add_plan">
                    <input type="hidden" name="id" id="planId" value="0">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Name *</label>
                        <input type="text" name="program_name" id="planProgram" class="form-control" required placeholder="e.g. Certificate in Nursing">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Students *</label>
                        <input type="number" name="target_students" id="planTarget" class="form-control" required min="0" value="50">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Academic Year *</label>
                            <input type="text" name="academic_year" id="planYear" class="form-control" required placeholder="e.g. 2026">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Semester *</label>
                            <select name="semester" id="planSemester" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Semester 1">Semester 1</option>
                                <option value="Semester 2">Semester 2</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="planStatus" class="form-select">
                            <option value="Planning">Planning</option>
                            <option value="Active">Active</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" id="planNotes" class="form-control" rows="3" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Plan</button>
                </div>
            </form>
        </div>
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
function showAddModal() {
    document.getElementById('planModalTitle').textContent = 'Add Intake Plan';
    document.getElementById('planAction').value = 'add_plan';
    document.getElementById('planId').value = 0;
    document.getElementById('planProgram').value = '';
    document.getElementById('planTarget').value = 50;
    document.getElementById('planYear').value = '';
    document.getElementById('planSemester').value = '';
    document.getElementById('planStatus').value = 'Planning';
    document.getElementById('planNotes').value = '';
    new bootstrap.Modal(document.getElementById('planModal')).show();
}
function editPlan(data) {
    document.getElementById('planModalTitle').textContent = 'Edit Intake Plan';
    document.getElementById('planAction').value = 'update_plan';
    document.getElementById('planId').value = data.id;
    document.getElementById('planProgram').value = data.program_name;
    document.getElementById('planTarget').value = data.target_students;
    document.getElementById('planYear').value = data.academic_year;
    document.getElementById('planStatus').value = data.status;
    document.getElementById('planSemester').value = data.semester;
    document.getElementById('planNotes').value = data.notes || '';
    new bootstrap.Modal(document.getElementById('planModal')).show();
}
</script>
</body></html>
