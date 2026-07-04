<?php
$pageTitle = 'Performance Appraisal';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr','manager','director','principal','head']);
$conn = $ctx['staff'];
$user = $ctx['user'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => 'Unknown action'];
    $action = $_POST['action'];
    if ($action === 'add_appraisal' && $conn) {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        $rating = trim($_POST['rating'] ?? '');
        $score = (float)($_POST['score'] ?? 0);
        $comments = trim($_POST['comments'] ?? '');
        $period = trim($_POST['period'] ?? date('Y-m'));
        if ($staff_id) {
            $stmt = $conn->prepare("INSERT INTO staff_appraisals (staff_id, reviewer_id, score, rating, comments, appraisal_period, status, created_at) VALUES (?,?,?,?,?,?,'Pending',NOW())");
            $uid = (int)($_SESSION['user_id'] ?? 0);
            $stmt->bind_param('iidsss', $staff_id, $uid, $score, $rating, $comments, $period);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'Staff required'; }
    } elseif ($action === 'update_appraisal' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        $score = (float)($_POST['score'] ?? 0);
        $rating = trim($_POST['rating'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $comments = trim($_POST['comments'] ?? '');
        if ($id && $status) {
            $stmt = $conn->prepare("UPDATE staff_appraisals SET score=?, rating=?, status=?, comments=? WHERE id=?");
            $stmt->bind_param('dsssi', $score, $rating, $status, $comments, $id);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'ID and status required'; }
    } elseif ($action === 'delete_appraisal' && $conn) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM staff_appraisals WHERE id=?");
            $stmt->bind_param('i', $id);
            $response['success'] = $stmt->execute();
            $response['error'] = $stmt->error;
            $stmt->close();
        } else { $response['error'] = 'ID required'; }
    }
    echo json_encode($response); exit;
}

$totalAppraisals = 0; $pending = 0; $completed = 0; $avgScore = 0;
$appraisals = [];

if ($conn) {
    $r1 = $conn->query("SELECT COUNT(*) c FROM staff_appraisals WHERE YEAR(created_at)=YEAR(NOW())");
    if ($r1) $totalAppraisals = (int)$r1->fetch_assoc()['c'];
    $r2 = $conn->query("SELECT COUNT(*) c FROM staff_appraisals WHERE status='Pending'");
    if ($r2) $pending = (int)$r2->fetch_assoc()['c'];
    $r3 = $conn->query("SELECT COUNT(*) c FROM staff_appraisals WHERE status='Completed'");
    if ($r3) $completed = (int)$r3->fetch_assoc()['c'];
    $r4 = $conn->query("SELECT AVG(score) a FROM staff_appraisals WHERE score IS NOT NULL");
    if ($r4 && $row = $r4->fetch_assoc()) $avgScore = round((float)$row['a'], 1);
    $a = $conn->query("SELECT a.*, COALESCE(s.full_name, a.staff_name) staff_name FROM staff_appraisals a LEFT JOIN staff s ON a.staff_id=s.id ORDER BY a.created_at DESC LIMIT 50");
    if ($a) $appraisals = $a->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-chart-line me-2"></i>Performance Appraisal</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-content"><h3><?= number_format($totalAppraisals) ?></h3><p>This Year</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-content"><h3><?= number_format($pending) ?></h3><p>Pending</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content"><h3><?= number_format($completed) ?></h3><p>Completed</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-content"><h3><?= $avgScore ?></h3><p>Avg Score</p></div>
            </div>
        </div>
    </div>
    <div class="content-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Appraisal Records</h5>
            <button class="btn btn-primary btn-sm" onclick="showAddAppraisal()"><i class="fas fa-plus me-1"></i>New Appraisal</button>
        </div>
        <?php if (empty($appraisals)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No appraisal records found.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Staff Name</th><th>Period</th><th>Score</th><th>Rating</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($appraisals as $ap): ?>
                    <tr>
                        <td><?= htmlspecialchars($ap['staff_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($ap['appraisal_period'] ?? '-') ?></td>
                        <td><?= $ap['score'] !== null ? number_format((float)$ap['score'], 1) : '-' ?></td>
                        <td><?php if ($ap['rating']): ?><span class="badge bg-<?= $ap['rating'] === 'Excellent' ? 'success' : ($ap['rating'] === 'Good' ? 'info' : ($ap['rating'] === 'Satisfactory' ? 'warning' : 'danger')) ?>"><?= htmlspecialchars($ap['rating']) ?></span><?php else: ?>-<?php endif; ?></td>
                        <td><span class="badge bg-<?= ($ap['status'] ?? 'Pending') === 'Completed' ? 'success' : 'warning text-dark' ?>"><?= htmlspecialchars($ap['status'] ?? 'Pending') ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editAppraisal(<?= $ap['id'] ?>, <?= $ap['score'] ?? 0 ?>, '<?= htmlspecialchars($ap['rating'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($ap['status'] ?? 'Pending', ENT_QUOTES) ?>', '<?= htmlspecialchars($ap['comments'] ?? '', ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAppraisal(<?= $ap['id'] ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<!-- Add Appraisal Modal -->
<div class="modal fade" id="addAppraisalModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-star me-2"></i>New Appraisal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="addAppraisalForm" onsubmit="event.preventDefault(); submitAddAppraisal()">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Staff *</label><select name="staff_id" class="form-select" required><option value="">Select Staff</option><?php if ($conn) { $so = $conn->query("SELECT id, full_name FROM staff WHERE status='Active' ORDER BY full_name"); if ($so) while ($row = $so->fetch_assoc()) echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['full_name']).'</option>'; } ?></select></div>
                <div class="mb-3"><label class="form-label">Score (0-100)</label><input type="number" name="score" step="0.01" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Rating</label><select name="rating" class="form-select"><option value="">Select</option><option value="Excellent">Excellent</option><option value="Good">Good</option><option value="Satisfactory">Satisfactory</option><option value="Needs Improvement">Needs Improvement</option></select></div>
                <div class="mb-3"><label class="form-label">Period</label><input type="text" name="period" class="form-control" value="<?= date('Y-m') ?>"></div>
                <div class="mb-3"><label class="form-label">Comments</label><textarea name="comments" class="form-control" rows="3"></textarea></div>
                <input type="hidden" name="action" value="add_appraisal">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Appraisal Modal -->
<div class="modal fade" id="editAppraisalModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Appraisal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="editAppraisalForm" onsubmit="event.preventDefault(); submitEditAppraisal()">
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_appr_id">
                <div class="mb-3"><label class="form-label">Score (0-100)</label><input type="number" name="score" id="edit_appr_score" step="0.01" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Rating</label><select name="rating" id="edit_appr_rating" class="form-select"><option value="">Select</option><option value="Excellent">Excellent</option><option value="Good">Good</option><option value="Satisfactory">Satisfactory</option><option value="Needs Improvement">Needs Improvement</option></select></div>
                <div class="mb-3"><label class="form-label">Status</label><select name="status" id="edit_appr_status" class="form-select"><option value="Pending">Pending</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option></select></div>
                <div class="mb-3"><label class="form-label">Comments</label><textarea name="comments" id="edit_appr_comments" class="form-control" rows="3"></textarea></div>
                <input type="hidden" name="action" value="update_appraisal">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function showAddAppraisal() { new bootstrap.Modal(document.getElementById('addAppraisalModal')).show(); }
function editAppraisal(id, score, rating, status, comments) {
    document.getElementById('edit_appr_id').value = id;
    document.getElementById('edit_appr_score').value = score;
    document.getElementById('edit_appr_rating').value = rating;
    document.getElementById('edit_appr_status').value = status;
    document.getElementById('edit_appr_comments').value = comments;
    new bootstrap.Modal(document.getElementById('editAppraisalModal')).show();
}
function deleteAppraisal(id) {
    if (!confirm('Delete this appraisal record?')) return;
    var fd = new FormData(); fd.append('action', 'delete_appraisal'); fd.append('id', id);
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
}
function submitAddAppraisal() {
    var fd = new FormData(document.getElementById('addAppraisalForm'));
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
}
function submitEditAppraisal() {
    var fd = new FormData(document.getElementById('editAppraisalForm'));
    fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) window.location.reload(); else alert('Error: ' + (d.error || 'Failed')); })
        .catch(function(e) { alert('Error'); });
}
</script>
</body>
</html>
