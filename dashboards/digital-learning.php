<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'ict', 'it', 'lecturer']);
$conn = $ctx['staff'];
$user = $ctx['user'];

// ── AJAX CRUD Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'] ?? $_GET['ajax'] ?? '';
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
    }
    if (!$conn) { echo json_encode(['success' => false, 'message' => 'Database unavailable']); exit; }

    @$conn->query("CREATE TABLE IF NOT EXISTS library_digital_resources (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(300), author_creator VARCHAR(200), resource_type VARCHAR(50), access_level VARCHAR(50), publication_year VARCHAR(10), file_url VARCHAR(500), description TEXT, added_date DATE DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    @$conn->query("CREATE TABLE IF NOT EXISTS skills_laboratory (id INT AUTO_INCREMENT PRIMARY KEY, lab_name VARCHAR(200), location VARCHAR(200), capacity INT DEFAULT 0, status VARCHAR(50) DEFAULT 'Active', description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    switch ($action) {
        case 'add_resource':
            $title = trim($_POST['title'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $rtype = trim($_POST['resource_type'] ?? '');
            $alevel = trim($_POST['access_level'] ?? 'Public');
            $pyear = trim($_POST['publication_year'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            if (!$title) { echo json_encode(['success' => false, 'message' => 'Title required']); exit; }
            $stmt = $conn->prepare("INSERT INTO library_digital_resources (title, author_creator, resource_type, access_level, publication_year, description, added_date, created_at) VALUES (?,?,?,?,?,?,CURDATE(),NOW())");
            $stmt->bind_param('ssssss', $title, $author, $rtype, $alevel, $pyear, $desc); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Resource added' : 'Failed']); exit;

        case 'delete_resource':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM library_digital_resources WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']); exit;

        case 'add_lab':
            $name = trim($_POST['lab_name'] ?? '');
            $loc = trim($_POST['location'] ?? '');
            $cap = (int)($_POST['capacity'] ?? 0);
            $desc = trim($_POST['description'] ?? '');
            if (!$name) { echo json_encode(['success' => false, 'message' => 'Lab name required']); exit; }
            $stmt = $conn->prepare("INSERT INTO skills_laboratory (lab_name, location, capacity, status, description, created_at) VALUES (?,?,?,'Active',?,NOW())");
            $stmt->bind_param('ssis', $name, $loc, $cap, $desc); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Lab added' : 'Failed']); exit;

        case 'update_lab':
            $id = (int)($_POST['id'] ?? 0); $status = trim($_POST['status'] ?? 'Active');
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("UPDATE skills_laboratory SET status=? WHERE id=?");
            $stmt->bind_param('si', $status, $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Failed']); exit;

        case 'delete_lab':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM skills_laboratory WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']); exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
}

$resources = []; $labs = [];
if ($conn) {
    @$r = $conn->query("SELECT * FROM library_digital_resources ORDER BY added_date DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $resources[] = $row;
    @$r = $conn->query("SELECT * FROM skills_laboratory ORDER BY lab_name");
    if ($r) while ($row = $r->fetch_assoc()) $labs[] = $row;
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$pageTitle = 'Digital Learning';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-laptop me-2"></i>Digital Learning</h2><p>Manage e-learning resources, digital content, and skills laboratory</p></div>
<div class="row g-4">
<div class="col-lg-7"><div class="card"><div class="card-header d-flex justify-content-between align-items-center">Digital Resources (<?= count($resources) ?>) <button class="btn btn-sm btn-primary" onclick="openResModal()"><i class="fas fa-plus"></i> Add Resource</button></div><div class="card-body" style="max-height:400px;overflow-y:auto">
<?php if (empty($resources)): ?><div class="empty-state"><i class="fas fa-book-open"></i><p>No digital resources uploaded yet.</p></div>
<?php else: ?>
<?php foreach ($resources as $res): ?>
<div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
<div>
<strong class="small"><?= htmlspecialchars($res['title']??'') ?></strong><br>
<span class="text-muted small"><?= htmlspecialchars($res['author_creator']??'') ?></span>
<?php if (!empty($res['resource_type'])): ?><br><span class="badge bg-info mt-1"><?= htmlspecialchars($res['resource_type']) ?></span><?php endif; ?>
<?php if (!empty($res['access_level'])): ?><span class="badge bg-secondary mt-1 ms-1"><?= htmlspecialchars($res['access_level']) ?></span><?php endif; ?>
</div>
<div class="text-end">
<?php if (!empty($res['publication_year'])): ?><div class="text-muted small"><?= htmlspecialchars($res['publication_year']) ?></div><?php endif; ?>
<button class="btn btn-xs btn-outline-danger" onclick="deleteResource(<?=$res['id']?>)"><i class="fas fa-trash"></i></button>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-header d-flex justify-content-between align-items-center">Skills Labs <button class="btn btn-sm btn-success text-white" onclick="openLabModal()"><i class="fas fa-plus"></i> Add Lab</button></div><div class="card-body">
<?php if (empty($labs)): ?><p class="text-muted small text-center py-3">No labs configured.</p>
<?php else: ?><?php foreach ($labs as $lab): ?>
<div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2 small">
<div><strong><?= htmlspecialchars($lab['lab_name']) ?></strong><br><span class="text-muted"><?= htmlspecialchars($lab['location']??'') ?> &middot; Cap: <?= (int)($lab['capacity']??0) ?></span></div>
<div>
<select class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="updateLab(<?=$lab['id']?>,this.value)">
<option value="Active" <?= ($lab['status']??'')==='Active'?'selected':'' ?>>Active</option>
<option value="Under Maintenance" <?= ($lab['status']??'')==='Under Maintenance'?'selected':'' ?>>Maintenance</option>
<option value="Closed" <?= ($lab['status']??'')==='Closed'?'selected':'' ?>>Closed</option>
</select>
<button class="btn btn-xs btn-outline-danger" onclick="deleteLab(<?=$lab['id']?>)"><i class="fas fa-trash"></i></button>
</div>
</div>
<?php endforeach; ?><?php endif; ?>
</div></div></div>
</div>
</div>

<!-- Resource Modal -->
<div class="modal fade" id="resModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Add Digital Resource</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">Title *</label><input type="text" class="form-control" id="resTitle"></div>
    <div class="mb-3"><label class="form-label">Author/Creator</label><input type="text" class="form-control" id="resAuthor"></div>
    <div class="mb-3"><label class="form-label">Resource Type</label><select class="form-control" id="resType"><option>E-Book</option><option>Video</option><option>Article</option><option>Course Material</option><option>Lab Manual</option><option>Other</option></select></div>
    <div class="mb-3"><label class="form-label">Access Level</label><select class="form-control" id="resAccess"><option>Public</option><option>Staff Only</option><option>Students Only</option></select></div>
    <div class="mb-3"><label class="form-label">Publication Year</label><input type="text" class="form-control" id="resYear"></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="resDesc" rows="2"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="saveResource()">Save</button></div>
</div></div></div>

<!-- Lab Modal -->
<div class="modal fade" id="labModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Add Skills Lab</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label">Lab Name *</label><input type="text" class="form-control" id="labName"></div>
    <div class="mb-3"><label class="form-label">Location</label><input type="text" class="form-control" id="labLocation"></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="labDesc" rows="2"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" onclick="saveLab()">Save</button></div>
</div></div></div>

<script>
const CSRF = '<?= $_SESSION['csrf_token'] ?>';
function openResModal() { new bootstrap.Modal(document.getElementById('resModal')).show(); }
function openLabModal() { new bootstrap.Modal(document.getElementById('labModal')).show(); }
function saveResource() {
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('ajax_action', 'add_resource');
    fd.append('title', document.getElementById('resTitle').value);
    fd.append('author', document.getElementById('resAuthor').value);
    fd.append('resource_type', document.getElementById('resType').value);
    fd.append('access_level', document.getElementById('resAccess').value);
    fd.append('publication_year', document.getElementById('resYear').value);
    fd.append('description', document.getElementById('resDesc').value);
    fetch('digital-learning.php?ajax=1', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); });
}
function deleteResource(id) { if(!confirm('Delete?')) return; const fd=new FormData(); fd.append('csrf_token',CSRF); fd.append('ajax_action','delete_resource'); fd.append('id',id); fetch('digital-learning.php?ajax=1',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success)location.reload();else alert(d.message);}); }
function saveLab() {
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('ajax_action', 'add_lab');
    fd.append('lab_name', document.getElementById('labName').value);
    fd.append('location', document.getElementById('labLocation').value);
    fd.append('description', document.getElementById('labDesc').value);
    fetch('digital-learning.php?ajax=1', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); });
}
function updateLab(id, status) { const fd=new FormData(); fd.append('csrf_token',CSRF); fd.append('ajax_action','update_lab'); fd.append('id',id); fd.append('status',status); fetch('digital-learning.php?ajax=1',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(!d.success)alert(d.message);}); }
function deleteLab(id) { if(!confirm('Delete?')) return; const fd=new FormData(); fd.append('csrf_token',CSRF); fd.append('ajax_action','delete_lab'); fd.append('id',id); fetch('digital-learning.php?ajax=1',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success)location.reload();else alert(d.message);}); }
</script>
</body></html>
