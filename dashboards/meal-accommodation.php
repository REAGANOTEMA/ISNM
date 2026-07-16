<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['matron', 'warden', 'registrar', 'director']);
$conn = $ctx['staff'];
$user = $ctx['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action === 'add_meal') {
            $mealType  = trim($_POST['meal_type'] ?? '');
            $menu      = trim($_POST['menu'] ?? '');
            $preparedBy = trim($_POST['prepared_by'] ?? '');
            $mealDate  = trim($_POST['meal_date'] ?? '');
            $status    = trim($_POST['status'] ?? 'served');
            if ($mealType === '' || $mealDate === '') {
                throw new Exception('Meal type and date are required.');
            }
            $stmt = $conn->prepare("INSERT INTO meal_tracking (meal_type, menu, prepared_by, meal_date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $mealType, $menu, $preparedBy, $mealDate, $status);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Meal record added successfully.';
        } elseif ($action === 'add_inspection') {
            $roomNumber = trim($_POST['room_number'] ?? '');
            $inspector  = trim($_POST['inspector'] ?? '');
            $inspDate   = trim($_POST['inspection_date'] ?? '');
            $score      = trim($_POST['score'] ?? '');
            $notes      = trim($_POST['notes'] ?? '');
            $status     = trim($_POST['status'] ?? 'pending');
            if ($roomNumber === '' || $inspector === '' || $inspDate === '') {
                throw new Exception('Room, inspector, and date are required.');
            }
            $stmt = $conn->prepare("INSERT INTO room_inspections (room_number, inspector, inspection_date, score, notes, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $roomNumber, $inspector, $inspDate, $score, $notes, $status);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Inspection record added successfully.';
        } elseif ($action === 'update_inspection') {
            $id     = (int)($_POST['inspection_id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            $notes  = trim($_POST['notes'] ?? '');
            if ($id <= 0 || $status === '') {
                throw new Exception('Invalid inspection record.');
            }
            $stmt = $conn->prepare("UPDATE room_inspections SET status=?, notes=? WHERE id=?");
            $stmt->bind_param('ssi', $status, $notes, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Inspection updated successfully.';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$pageTitle = 'Meal & Accommodation Management';

$meals = [];
$r = $conn->query("SELECT * FROM meal_tracking ORDER BY meal_date DESC LIMIT 100");
if ($r) while ($row = $r->fetch_assoc()) $meals[] = $row;

$inspections = [];
$r2 = $conn->query("SELECT * FROM room_inspections ORDER BY inspection_date DESC LIMIT 100");
if ($r2) while ($row = $r2->fetch_assoc()) $inspections[] = $row;

$totalMeals = count($meals);
$totalInspections = count($inspections);
$passedInsp = count(array_filter($inspections, fn($i) => ($i['status'] ?? '') === 'passed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-utensils"></i> Meal & Accommodation Management</h1><button onclick="window.print()" class="btn btn-sm btn-outline-secondary float-end"><i class="fas fa-print"></i> Print</button>
    </div>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Meal Records</h6><h3><?= $totalMeals ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Room Inspections</h6><h3><?= $totalInspections ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Passed Inspections</h6><h3><?= $passedInsp ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Meal Tracking</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMealModal"><i class="fas fa-plus me-1"></i>Add Meal</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Meal Type</th><th>Menu</th><th>Prepared By</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($meals as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['meal_type'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($m['menu'] ?? $m['description'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($m['prepared_by'] ?? $m['cook'] ?? '-') ?></td>
                                    <td><?= $m['meal_date'] ?? $m['created_at'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($m['status'] ?? 'served') === 'served' ? 'success' : 'warning' ?>"><?= $m['status'] ?? 'served' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($meals)): ?><tr><td colspan="5" class="text-center">No meal records</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Room Inspections</h5>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addInspectionModal"><i class="fas fa-plus me-1"></i>Add Inspection</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Room</th><th>Student</th><th>Inspector</th><th>Score</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($inspections as $i): ?>
                                <tr>
                                    <td><?= htmlspecialchars($i['room_number'] ?? $i['room'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($i['student_name'] ?? $i['student_id'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($i['inspector_name'] ?? $i['inspector'] ?? '-') ?></td>
                                    <td><?= $i['score'] ?? $i['grade'] ?? '-' ?></td>
                                    <td><span class="badge bg-<?= ($i['status'] ?? '') === 'passed' ? 'success' : (($i['status'] ?? '') === 'failed' ? 'danger' : 'warning') ?>"><?= $i['status'] ?? 'pending' ?></span></td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-primary me-1" onclick="openEditInspection(<?= htmlspecialchars(json_encode($i['id'] ?? 0)) ?>, '<?= htmlspecialchars($i['status'] ?? 'pending', ENT_QUOTES) ?>', '<?= htmlspecialchars($i['notes'] ?? '', ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                                        <?php if (($i['status'] ?? '') !== 'closed'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Close this inspection?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="update_inspection">
                                            <input type="hidden" name="inspection_id" value="<?= $i['id'] ?? 0 ?>">
                                            <input type="hidden" name="status" value="closed">
                                            <input type="hidden" name="notes" value="<?= htmlspecialchars($i['notes'] ?? '') ?>">
                                            <button class="btn btn-xs btn-outline-danger" title="Close"><i class="fas fa-times-circle"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($inspections)): ?><tr><td colspan="6" class="text-center">No room inspections</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Meal Modal -->
<div class="modal fade" id="addMealModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="add_meal">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-utensils me-2"></i>Add Meal Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Meal Type *</label>
                        <select name="meal_type" class="form-select" required>
                            <option value="">Select type</option>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Lunch">Lunch</option>
                            <option value="Dinner">Dinner</option>
                            <option value="Snack">Snack</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date *</label>
                        <input type="date" name="meal_date" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Menu</label>
                        <input type="text" name="menu" class="form-control" placeholder="e.g. Rice, Beans, Chicken">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prepared By</label>
                        <input type="text" name="prepared_by" class="form-control" placeholder="Cook / Staff name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="served">Served</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Meal</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Inspection Modal -->
<div class="modal fade" id="addInspectionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="add_inspection">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Add Room Inspection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Room Number *</label>
                        <input type="text" name="room_number" class="form-control" required placeholder="e.g. A-101">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Inspector *</label>
                        <input type="text" name="inspector" class="form-control" required placeholder="Inspector name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Inspection Date *</label>
                        <input type="date" name="inspection_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Score</label>
                        <input type="number" name="score" class="form-control" min="0" max="100" placeholder="0-100">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional observations..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Save Inspection</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Inspection Modal -->
<div class="modal fade" id="editInspectionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="update_inspection">
            <input type="hidden" name="inspection_id" id="editInspId">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Inspection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Status</label>
                        <select name="status" id="editInspStatus" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="editInspNotes" class="form-control" rows="3" placeholder="Inspection notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update Inspection</button>
            </div>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
function openEditInspection(id, status, notes) {
    document.getElementById('editInspId').value = id;
    document.getElementById('editInspStatus').value = status;
    document.getElementById('editInspNotes').value = notes;
    new bootstrap.Modal(document.getElementById('editInspectionModal')).show();
}
</script>
</body>
</html>
