<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
$ctx = bootstrapStaffDashboard(['director', 'academics', 'principal', 'head']);
$conn = $ctx['staff'];
$user = $ctx['user'];

generateCsrfToken();
$flash = getFlashMessages();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (!verifyCsrfToken()) {
        flashMessage('error', 'Invalid security token. Please try again.');
        header('Location: quality-assurance.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add_review') {
        $department     = trim($_POST['department'] ?? '');
        $review_area    = trim($_POST['review_area'] ?? '');
        $findings       = trim($_POST['findings'] ?? '');
        $recommendations = trim($_POST['recommendations'] ?? '');
        $reviewed_by    = trim($_POST['reviewed_by'] ?? '');
        $status         = trim($_POST['status'] ?? 'Pending');
        if ($department === '' || $review_area === '') {
            flashMessage('error', 'Department and review area are required.');
        } else {
            $stmt = $conn->prepare("INSERT INTO quality_assurance (department, review_area, findings, recommendations, reviewed_by, status) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssssss', $department, $review_area, $findings, $recommendations, $reviewed_by, $status);
                if ($stmt->execute()) {
                    flashMessage('success', 'Quality review added successfully.');
                } else {
                    flashMessage('error', 'Failed to add review.');
                }
                $stmt->close();
            } else {
                flashMessage('error', 'Database error: ' . $conn->error);
            }
        }
        header('Location: quality-assurance.php');
        exit;
    }
    if ($action === 'update_review') {
        $id              = (int)($_POST['id'] ?? 0);
        $status          = trim($_POST['status'] ?? '');
        $recommendations = trim($_POST['recommendations'] ?? '');
        if ($id <= 0) {
            flashMessage('error', 'Invalid review ID.');
        } else {
            $stmt = $conn->prepare("UPDATE quality_assurance SET status=?, recommendations=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('ssi', $status, $recommendations, $id);
                if ($stmt->execute()) {
                    flashMessage('success', 'Review updated successfully.');
                } else {
                    flashMessage('error', 'Failed to update review.');
                }
                $stmt->close();
            } else {
                flashMessage('error', 'Database error: ' . $conn->error);
            }
        }
        header('Location: quality-assurance.php');
        exit;
    }
    if ($action === 'delete_review') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flashMessage('error', 'Invalid review ID.');
        } else {
            $stmt = $conn->prepare("DELETE FROM quality_assurance WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    flashMessage('success', 'Review deleted successfully.');
                } else {
                    flashMessage('error', 'Failed to delete review.');
                }
                $stmt->close();
            } else {
                flashMessage('error', 'Database error: ' . $conn->error);
            }
        }
        header('Location: quality-assurance.php');
        exit;
    }
}

$qa = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM quality_assurance ORDER BY created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $qa[] = $row;
}
$indicators = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM performance_indicators WHERE status='active' ORDER BY indicator_category, indicator_name");
    if ($r) while ($row = $r->fetch_assoc()) $indicators[] = $row;
}
$pageTitle = 'Quality Assurance';
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
<div class="page-title-card"><h2><i class="fas fa-check-circle me-2"></i>Quality Assurance <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button></h2><p>Monitor quality standards, accreditation, compliance, and performance indicators</p></div>

<?php foreach ($flash as $type => $message): ?>
<div class="flash-msg <?= $type ?>"><?= htmlspecialchars($message) ?></div>
<?php endforeach; ?>

<div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center">Quality Assessments <button class="btn btn-sm btn-primary" onclick="openModal('addReviewModal')"><i class="fas fa-plus me-1"></i>Add Review</button></div><div class="card-body">
<?php if (empty($qa)): ?><div class="empty-state"><i class="fas fa-clipboard-check"></i><p>No quality assurance records yet.</p></div>
<?php else: ?>
<div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchJTOF" type="text" placeholder="Search..." onkeyup="filterTable('srchJTOF','tblJTOF')"></div>
<div class="table-responsive"><table id="tblJTOF" class="table table-hover"><thead><tr><th>Type</th><th>Title</th><th>Department</th><th>Period</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($qa as $q): ?>
<tr>
<td><span class="badge bg-info"><?= htmlspecialchars($q['assessment_type']??'') ?></span></td>
<td><?= htmlspecialchars(mb_substr($q['title']??'', 0, 50)) ?></td>
<td class="small"><?= htmlspecialchars($q['department']??'') ?></td>
<td class="small"><?= htmlspecialchars($q['assessment_period']??'') ?></td>
<td><span class="status-pill <?= ($q['status']??'') === 'Completed' ? 'success' : (($q['status']??'') === 'In Progress' ? 'warning' : 'info') ?>"><?= htmlspecialchars($q['status']??'Scheduled') ?></span></td>
<td class="small"><?= htmlspecialchars($q['created_at']??'') ?></td>
<td>
<button class="btn btn-sm btn-outline-primary" onclick="openEditModal(this)"
    data-id="<?= htmlspecialchars($q['id']??'') ?>"
    data-status="<?= htmlspecialchars($q['status']??'') ?>"
    data-recommendations="<?= htmlspecialchars($q['recommendations']??'') ?>"><i class="fas fa-edit"></i> Edit</button>
<form method="POST" action="quality-assurance.php" class="d-inline" onsubmit="return confirm('Delete this review?')">
    <?php csrfField(); ?>
    <input type="hidden" name="action" value="delete_review">
    <input type="hidden" name="id" value="<?= (int)($q['id']??0) ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div>
<div class="card"><div class="card-header">Active Performance Indicators (<?= count($indicators) ?>)</div><div class="card-body">
<?php if (!empty($indicators)): ?>
<div class="row g-2"><?php foreach ($indicators as $ind): ?>
<div class="col-md-4 col-6"><div class="border rounded p-2 small bg-light"><?= htmlspecialchars($ind['indicator_name']) ?><br><span class="text-muted"><?= htmlspecialchars($ind['indicator_category']??'') ?> | Target: <?= htmlspecialchars($ind['target_value']??'N/A') ?></span></div></div>
<?php endforeach; ?></div>
<?php else: ?><p class="text-muted small text-center py-3">No active indicators.</p><?php endif; ?>
</div></div></div>

<div class="modal-backdrop" id="addReviewModal">
<div class="modal-box">
<div class="modal-head"><h5><i class="fas fa-plus-circle me-2"></i>Add Quality Review</h5><button class="btn-close" onclick="closeModal('addReviewModal')">&times;</button></div>
<div class="modal-body">
<form method="POST" action="quality-assurance.php">
<?php csrfField(); ?>
<input type="hidden" name="action" value="add_review">
<div class="mb-3">
<label class="form-label fw-bold">Department <span class="text-danger">*</span></label>
<input type="text" name="department" class="form-control" required placeholder="e.g. Academics, Nursing">
</div>
<div class="mb-3">
<label class="form-label fw-bold">Review Area <span class="text-danger">*</span></label>
<input type="text" name="review_area" class="form-control" required placeholder="e.g. Curriculum Delivery, Lab Safety">
</div>
<div class="mb-3">
<label class="form-label fw-bold">Findings</label>
<textarea name="findings" class="form-control" rows="3" placeholder="Describe findings..."></textarea>
</div>
<div class="mb-3">
<label class="form-label fw-bold">Recommendations</label>
<textarea name="recommendations" class="form-control" rows="3" placeholder="Recommendations..."></textarea>
</div>
<div class="mb-3">
<label class="form-label fw-bold">Reviewed By</label>
<input type="text" name="reviewed_by" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Reviewer name">
</div>
<div class="mb-3">
<label class="form-label fw-bold">Status</label>
<select name="status" class="form-select">
<option value="Pending">Pending</option>
<option value="Pass">Pass</option>
<option value="Fail">Fail</option>
<option value="Needs Improvement">Needs Improvement</option>
</select>
</div>
</div>
<div class="modal-foot">
<button type="button" class="btn btn-secondary" onclick="closeModal('addReviewModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Review</button>
</div>
</form>
</div>
</div>

<div class="modal-backdrop" id="editReviewModal">
<div class="modal-box">
<div class="modal-head"><h5><i class="fas fa-edit me-2"></i>Edit Review</h5><button class="btn-close" onclick="closeModal('editReviewModal')">&times;</button></div>
<div class="modal-body">
<form method="POST" action="quality-assurance.php">
<?php csrfField(); ?>
<input type="hidden" name="action" value="update_review">
<input type="hidden" name="id" id="editReviewId">
<div class="mb-3">
<label class="form-label fw-bold">Status</label>
<select name="status" id="editReviewStatus" class="form-select">
<option value="Pending">Pending</option>
<option value="Pass">Pass</option>
<option value="Fail">Fail</option>
<option value="Needs Improvement">Needs Improvement</option>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-bold">Recommendations</label>
<textarea name="recommendations" id="editReviewRecs" class="form-control" rows="4" placeholder="Update recommendations..."></textarea>
</div>
</div>
<div class="modal-foot">
<button type="button" class="btn btn-secondary" onclick="closeModal('editReviewModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Review</button>
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
    document.getElementById('editReviewId').value = btn.dataset.id;
    document.getElementById('editReviewStatus').value = btn.dataset.status;
    document.getElementById('editReviewRecs').value = btn.dataset.recommendations;
    openModal('editReviewModal');
}
document.querySelectorAll('.modal-backdrop').forEach(function(el) {
    el.addEventListener('click', function(e) { if (e.target === el) el.classList.remove('show'); });
});
</script>
</body></html>
