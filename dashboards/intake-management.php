<?php
/**
 * Director Dashboard — Intake Management Module
 * Manage student intake periods, quotas, and admission cycles
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director general', 'ceo', 'academic_registrar', 'director_admissions', 'principal']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$websiteConn = $ctx['website'];
$user = $ctx['user'];
$userId = (int)($_SESSION['user_id'] ?? 0);

// CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid security token.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'create' && $conn) {
        $name = trim($_POST['name'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $quota = (int)($_POST['quota'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($name && $program && $startDate && $endDate) {
            $stmt = $conn->prepare("INSERT INTO intakes (name, program, start_date, end_date, quota, description, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 'open', ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('ssssisi', $name, $program, $startDate, $endDate, $quota, $description, $userId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Intake period created successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to create intake: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = 'Please fill in all required fields.';
        }
        header('Location: intake-management.php');
        exit;
    }

    if ($action === 'update' && $conn) {
        $id = (int)($_POST['intake_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $quota = (int)($_POST['quota'] ?? 0);
        $status = $_POST['status'] ?? 'open';
        $description = trim($_POST['description'] ?? '');

        if ($id && $name) {
            $stmt = $conn->prepare("UPDATE intakes SET name=?, program=?, start_date=?, end_date=?, quota=?, status=?, description=?, updated_at=NOW() WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('ssssissi', $name, $program, $startDate, $endDate, $quota, $status, $description, $id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Intake updated successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to update intake: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
        header('Location: intake-management.php');
        exit;
    }

    if ($action === 'delete' && $conn) {
        $id = (int)($_POST['intake_id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM intakes WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Intake deleted.';
                } else {
                    $_SESSION['error'] = 'Failed to delete intake.';
                }
                $stmt->close();
            }
        }
        header('Location: intake-management.php');
        exit;
    }

    if ($action === 'toggle_status' && $conn) {
        $id = (int)($_POST['intake_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if ($id && in_array($newStatus, ['open', 'closed', 'upcoming'])) {
            $stmt = $conn->prepare("UPDATE intakes SET status=?, updated_at=NOW() WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('si', $newStatus, $id);
                $stmt->execute();
                $stmt->close();
            }
            $_SESSION['success'] = 'Intake status updated.';
        }
        header('Location: intake-management.php');
        exit;
    }
}

// Ensure intakes table exists
if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS intakes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        program VARCHAR(255) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        quota INT DEFAULT 0,
        enrolled INT DEFAULT 0,
        description TEXT,
        status ENUM('upcoming','open','closed') DEFAULT 'upcoming',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Fetch intakes
$intakes = [];
if ($conn) {
    $result = $conn->query("SELECT i.*, (SELECT COUNT(*) FROM students s WHERE s.program = i.program AND s.created_at BETWEEN i.start_date AND DATE_ADD(i.end_date, INTERVAL 1 YEAR)) as actual_enrolled FROM intakes i ORDER BY i.start_date DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) $intakes[] = $row;
    }
}

// Get programs
$programs = [];
if ($conn) {
    $result = $conn->query("SELECT name FROM academic_programs WHERE status='Active' ORDER BY name");
    if ($result) {
        while ($row = $result->fetch_assoc()) $programs[] = $row['name'];
    }
}
if (empty($programs)) {
    $programs = ['Certificate in Nursing', 'Certificate in Midwifery', 'Diploma in Nursing - Extension', 'Diploma in Midwifery - Extension'];
}

// Stats
$totalIntakes = count($intakes);
$openIntakes = count(array_filter($intakes, fn($i) => $i['status'] === 'open'));
$closedIntakes = count(array_filter($intakes, fn($i) => $i['status'] === 'closed'));
$upcomingIntakes = count(array_filter($intakes, fn($i) => $i['status'] === 'upcoming'));

$pageTitle = 'Intake Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.intake-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
.intake-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.intake-card.status-open { border-left-color: #28a745; }
.intake-card.status-closed { border-left-color: #dc3545; }
.intake-card.status-upcoming { border-left-color: #ffc107; }
.progress-bar-custom { height: 8px; border-radius: 4px; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-door-open"></i> Intake Management</h1>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary float-end"><i class="fas fa-print"></i> Print</button>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-primary"><div class="card-body text-center"><h3 class="text-primary"><?= $totalIntakes ?></h3><p class="mb-0 text-muted">Total Intakes</p></div></div></div>
        <div class="col-md-3"><div class="card border-success"><div class="card-body text-center"><h3 class="text-success"><?= $openIntakes ?></h3><p class="mb-0 text-muted">Open Now</p></div></div></div>
        <div class="col-md-3"><div class="card border-warning"><div class="card-body text-center"><h3 class="text-warning"><?= $upcomingIntakes ?></h3><p class="mb-0 text-muted">Upcoming</p></div></div></div>
        <div class="col-md-3"><div class="card border-danger"><div class="card-body text-center"><h3 class="text-danger"><?= $closedIntakes ?></h3><p class="mb-0 text-muted">Closed</p></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Intake Periods</h5>
            <button class="btn btn-primary btn-sm" onclick="showCreateForm()"><i class="fas fa-plus me-1"></i>New Intake</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Program</th><th>Start</th><th>End</th><th>Quota</th><th>Enrolled</th><th>Progress</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($intakes)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No intake periods found. Click "New Intake" to create one.</td></tr>
                        <?php else: ?>
                        <?php foreach ($intakes as $intake): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($intake['name']) ?></strong></td>
                            <td><?= htmlspecialchars($intake['program']) ?></td>
                            <td><?= date('M j, Y', strtotime($intake['start_date'])) ?></td>
                            <td><?= date('M j, Y', strtotime($intake['end_date'])) ?></td>
                            <td><?= number_format($intake['quota']) ?></td>
                            <td><?= number_format($intake['enrolled'] ?? 0) ?></td>
                            <td style="min-width:120px">
                                <?php $pct = $intake['quota'] > 0 ? min(100, round(($intake['enrolled'] ?? 0) / $intake['quota'] * 100)) : 0; ?>
                                <div class="progress progress-bar-custom">
                                    <div class="progress-bar bg-<?= $pct >= 100 ? 'danger' : ($pct >= 75 ? 'warning' : 'success') ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $pct ?>%</small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $intake['status'] === 'open' ? 'success' : ($intake['status'] === 'closed' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($intake['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editIntake(<?= htmlspecialchars(json_encode($intake)) ?>)"><i class="fas fa-edit"></i></button>
                                <?php if ($intake['status'] !== 'open'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="intake_id" value="<?= $intake['id'] ?>">
                                    <input type="hidden" name="new_status" value="open">
                                    <button class="btn btn-sm btn-outline-success" title="Open"><i class="fas fa-lock-open"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($intake['status'] !== 'closed'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="intake_id" value="<?= $intake['id'] ?>">
                                    <input type="hidden" name="new_status" value="closed">
                                    <button class="btn btn-sm btn-outline-warning" title="Close"><i class="fas fa-lock"></i></button>
                                </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this intake period?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="intake_id" value="<?= $intake['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="intakeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>New Intake Period</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="intakeForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="intake_id" id="intakeId" value="0">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Intake Name *</label>
                        <input type="text" name="name" id="intakeName" class="form-control" required placeholder="e.g., September 2026 Intake">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program *</label>
                        <select name="program" id="intakeProgram" class="form-select" required>
                            <option value="">Select Program</option>
                            <?php foreach ($programs as $prog): ?>
                            <option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Start Date *</label>
                            <input type="date" name="start_date" id="intakeStart" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">End Date *</label>
                            <input type="date" name="end_date" id="intakeEnd" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Enrollment Quota</label>
                        <input type="number" name="quota" id="intakeQuota" class="form-control" min="0" value="100">
                    </div>
                    <div class="mb-3" id="statusField" style="display:none">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="intakeStatus" class="form-select">
                            <option value="upcoming">Upcoming</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="intakeDescription" class="form-control" rows="3" placeholder="Optional notes about this intake period"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Intake</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateForm() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>New Intake Period';
    document.getElementById('formAction').value = 'create';
    document.getElementById('intakeId').value = 0;
    document.getElementById('intakeName').value = '';
    document.getElementById('intakeProgram').value = '';
    document.getElementById('intakeStart').value = '';
    document.getElementById('intakeEnd').value = '';
    document.getElementById('intakeQuota').value = '100';
    document.getElementById('intakeDescription').value = '';
    document.getElementById('statusField').style.display = 'none';
    new bootstrap.Modal(document.getElementById('intakeModal')).show();
}

function editIntake(data) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Intake';
    document.getElementById('formAction').value = 'update';
    document.getElementById('intakeId').value = data.id;
    document.getElementById('intakeName').value = data.name;
    document.getElementById('intakeProgram').value = data.program;
    document.getElementById('intakeStart').value = data.start_date;
    document.getElementById('intakeEnd').value = data.end_date;
    document.getElementById('intakeQuota').value = data.quota;
    document.getElementById('intakeDescription').value = data.description || '';
    document.getElementById('intakeStatus').value = data.status;
    document.getElementById('statusField').style.display = 'block';
    new bootstrap.Modal(document.getElementById('intakeModal')).show();
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
