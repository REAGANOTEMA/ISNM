<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'ict', 'it']);
$conn_staff = $ctx['staff'];
$user = $ctx['user'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── AJAX CRUD Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['ajax'] ?? '';
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
    }
    if (!$conn_staff) { echo json_encode(['success' => false, 'message' => 'Database unavailable']); exit; }

    switch ($action) {
        case 'add_asset':
            $name = trim($_POST['asset_name'] ?? '');
            $type = trim($_POST['asset_type'] ?? '');
            $serial = trim($_POST['serial_number'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $status = trim($_POST['status'] ?? 'Operational');
            $assigned = trim($_POST['assigned_to'] ?? '');
            if (!$name || !$type) { echo json_encode(['success' => false, 'message' => 'Asset name and type are required']); exit; }
            $stmt = $conn_staff->prepare("INSERT INTO it_infrastructure (asset_name, asset_type, serial_number, location, status, assigned_to) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssssss', $name, $type, $serial, $location, $status, $assigned);
                $ok = $stmt->execute();
                $msg = $ok ? 'Asset added successfully' : 'Failed to add asset';
                $stmt->close();
                echo json_encode(['success' => $ok, 'message' => $msg]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn_staff->error]);
            }
            exit;

        case 'update_asset':
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'Operational');
            $location = trim($_POST['location'] ?? '');
            $assigned = trim($_POST['assigned_to'] ?? '');
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing asset ID']); exit; }
            $stmt = $conn_staff->prepare("UPDATE it_infrastructure SET status=?, location=?, assigned_to=? WHERE id=?");
            $stmt->bind_param('sssi', $status, $location, $assigned, $id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Asset updated' : 'Failed to update asset']);
            exit;

        case 'delete_asset':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing asset ID']); exit; }
            $stmt = $conn_staff->prepare("DELETE FROM it_infrastructure WHERE id=?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Asset deleted' : 'Failed to delete asset']);
            exit;

        case 'add_software':
            $name = trim($_POST['software_name'] ?? '');
            $license_key = trim($_POST['license_key'] ?? '');
            $expiry = trim($_POST['expiry_date'] ?? '');
            $seats = (int)($_POST['seats'] ?? 1);
            $status = trim($_POST['status'] ?? 'Active');
            if (!$name) { echo json_encode(['success' => false, 'message' => 'Software name is required']); exit; }
            try {
                $conn_ict = getDatabaseConnection('ict');
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'ICT database unavailable']); exit;
            }
            if (!$conn_ict) { echo json_encode(['success' => false, 'message' => 'ICT database unavailable']); exit; }
            $stmt = $conn_ict->prepare("INSERT INTO software_inventory (software_name, license_key, expiry_date, seats, status) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sssds', $name, $license_key, $expiry, $seats, $status);
                $ok = $stmt->execute();
                $msg = $ok ? 'Software added successfully' : 'Failed to add software';
                $stmt->close();
                echo json_encode(['success' => $ok, 'message' => $msg]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn_ict->error]);
            }
            exit;

        case 'update_software':
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'Active');
            $seats = (int)($_POST['seats'] ?? 1);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing software ID']); exit; }
            try {
                $conn_ict = getDatabaseConnection('ict');
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'ICT database unavailable']); exit;
            }
            if (!$conn_ict) { echo json_encode(['success' => false, 'message' => 'ICT database unavailable']); exit; }
            $stmt = $conn_ict->prepare("UPDATE software_inventory SET status=?, seats=? WHERE id=?");
            $stmt->bind_param('sdi', $status, $seats, $id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Software updated' : 'Failed to update software']);
            exit;

        case 'delete_software':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing software ID']); exit; }
            try {
                $conn_ict = getDatabaseConnection('ict');
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'ICT database unavailable']); exit;
            }
            if (!$conn_ict) { echo json_encode(['success' => false, 'message' => 'ICT database unavailable']); exit; }
            $stmt = $conn_ict->prepare("DELETE FROM software_inventory WHERE id=?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Software deleted' : 'Failed to delete software']);
            exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
}

$infrastructure = [];
if ($conn_staff) {
    $r = $conn_staff->query("SELECT * FROM it_infrastructure ORDER BY asset_type, asset_name");
    if ($r) while ($row = $r->fetch_assoc()) $infrastructure[] = $row;
}
$compliance = [];
if ($conn_staff) {
    $r = $conn_staff->query("SELECT * FROM compliance_records ORDER BY created_at DESC LIMIT 30");
    if ($r) while ($row = $r->fetch_assoc()) $compliance[] = $row;
}
$software = [];
try {
    $conn_ict = getDatabaseConnection('ict');
    if ($conn_ict) {
        $r = $conn_ict->query("SELECT * FROM software_inventory ORDER BY software_name");
        if ($r) while ($row = $r->fetch_assoc()) $software[] = $row;
    }
} catch (\Throwable $e) { $software = []; }

$pageTitle = 'ICT Policy';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.modal-backdrop{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1050;display:none;align-items:center;justify-content:center}
.modal-backdrop.show{display:flex}
.ict-modal{background:#fff;border-radius:12px;padding:24px;width:90%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 8px 30px rgba(0,0,0,.2)}
.ict-modal h5{margin-bottom:16px;font-weight:600}
.ict-modal .form-label{font-size:.85rem;font-weight:500}
.toast-msg{position:fixed;top:24px;right:24px;z-index:2000;padding:12px 24px;border-radius:8px;color:#fff;font-size:.9rem;font-weight:500;opacity:0;transform:translateY(-10px);transition:all .3s}
.toast-msg.show{opacity:1;transform:translateY(0)}
.toast-msg.success{background:#28a745}
.toast-msg.danger{background:#dc3545}
.toast-msg.warning{background:#ffc107;color:#333}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="toast-msg" id="toastMsg"></div>

<!-- Add Asset Modal -->
<div class="modal-backdrop" id="addAssetModal">
<div class="ict-modal">
    <h5><i class="fas fa-plus-circle me-2 text-primary"></i>Add IT Asset</h5>
    <form id="addAssetForm" onsubmit="return submitAddAsset(event)">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Asset Name *</label>
                <input type="text" class="form-control" name="asset_name" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Asset Type *</label>
                <select class="form-select" name="asset_type" required>
                    <option value="">Select type</option>
                    <option>Computer</option>
                    <option>Printer</option>
                    <option>Network</option>
                    <option>Server</option>
                    <option>Peripheral</option>
                    <option>Mobile Device</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Serial Number</label>
                <input type="text" class="form-control" name="serial_number">
            </div>
            <div class="col-md-6">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" name="location">
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option>Operational</option>
                    <option>Under Maintenance</option>
                    <option>Decommissioned</option>
                    <option>In Storage</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Assigned To</label>
                <input type="text" class="form-control" name="assigned_to">
            </div>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('addAssetModal')">Cancel</button>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Save Asset</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Asset Modal -->
<div class="modal-backdrop" id="editAssetModal">
<div class="ict-modal">
    <h5><i class="fas fa-edit me-2 text-warning"></i>Edit IT Asset</h5>
    <form id="editAssetForm" onsubmit="return submitEditAsset(event)">
        <input type="hidden" name="id" id="edit_asset_id">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" id="edit_asset_status">
                    <option>Operational</option>
                    <option>Under Maintenance</option>
                    <option>Decommissioned</option>
                    <option>In Storage</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" name="location" id="edit_asset_location">
            </div>
            <div class="col-md-6">
                <label class="form-label">Assigned To</label>
                <input type="text" class="form-control" name="assigned_to" id="edit_asset_assigned">
            </div>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('editAssetModal')">Cancel</button>
            <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-save me-1"></i>Update Asset</button>
        </div>
    </form>
</div>
</div>

<!-- Add Software Modal -->
<div class="modal-backdrop" id="addSoftwareModal">
<div class="ict-modal">
    <h5><i class="fas fa-plus-circle me-2 text-success"></i>Add Software License</h5>
    <form id="addSoftwareForm" onsubmit="return submitAddSoftware(event)">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Software Name *</label>
                <input type="text" class="form-control" name="software_name" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">License Key</label>
                <input type="text" class="form-control" name="license_key">
            </div>
            <div class="col-md-6">
                <label class="form-label">Expiry Date</label>
                <input type="date" class="form-control" name="expiry_date">
            </div>
            <div class="col-md-6">
                <label class="form-label">Seats</label>
                <input type="number" class="form-control" name="seats" value="1" min="1">
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option>Active</option>
                    <option>Expired</option>
                    <option>Suspended</option>
                </select>
            </div>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('addSoftwareModal')">Cancel</button>
            <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-save me-1"></i>Save Software</button>
        </div>
    </form>
</div>
</div>

<!-- Edit Software Modal -->
<div class="modal-backdrop" id="editSoftwareModal">
<div class="ict-modal">
    <h5><i class="fas fa-edit me-2 text-warning"></i>Edit Software License</h5>
    <form id="editSoftwareForm" onsubmit="return submitEditSoftware(event)">
        <input type="hidden" name="id" id="edit_software_id">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" id="edit_software_status">
                    <option>Active</option>
                    <option>Expired</option>
                    <option>Suspended</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Seats</label>
                <input type="number" class="form-control" name="seats" id="edit_software_seats" min="1">
            </div>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('editSoftwareModal')">Cancel</button>
            <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-save me-1"></i>Update Software</button>
        </div>
    </form>
</div>
</div>

<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-file-lines me-2"></i>ICT Policy & Infrastructure <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button></h2><p>Manage ICT policies, IT infrastructure, software inventory, and compliance</p></div>
<div class="row g-4">
<div class="col-md-6"><div class="card"><div class="card-header d-flex justify-content-between align-items-center">IT Infrastructure (<?= count($infrastructure) ?>) <button class="btn btn-sm btn-primary" onclick="openModal('addAssetModal')"><i class="fas fa-plus me-1"></i>Add Asset</button></div><div class="card-body" style="max-height:400px;overflow-y:auto">
<?php if (empty($infrastructure)): ?><div class="empty-state"><i class="fas fa-server"></i><p>No infrastructure assets recorded.</p></div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-hover" id="tblAssets">
<thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Location</th><th>Assigned</th><th style="width:60px"></th></tr></thead>
<tbody>
<?php foreach ($infrastructure as $a): ?>
<tr>
<td class="small"><?= htmlspecialchars($a['asset_name']??$a['asset_code']??'') ?></td>
<td class="small text-muted"><?= htmlspecialchars($a['asset_type']??'') ?></td>
<td><span class="badge bg-<?= ($a['status']??'') === 'Operational' ? 'success' : (($a['status']??'') === 'Under Maintenance' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($a['status']??'') ?></span></td>
<td class="small"><?= htmlspecialchars($a['location']??'') ?></td>
<td class="small"><?= htmlspecialchars($a['assigned_to']??'') ?></td>
<td class="text-end">
<button class="btn btn-sm btn-outline-warning" title="Edit" onclick="editAsset(<?= (int)($a['id']??0) ?>,'<?= htmlspecialchars(addslashes($a['status']??'Operational'),ENT_QUOTES) ?>','<?= htmlspecialchars(addslashes($a['location']??''),ENT_QUOTES) ?>','<?= htmlspecialchars(addslashes($a['assigned_to']??''),ENT_QUOTES) ?>')"><i class="fas fa-pen"></i></button>
<button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteAsset(<?= (int)($a['id']??0) ?>,'<?= htmlspecialchars(addslashes($a['asset_name']??''),ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-md-6"><div class="card"><div class="card-header d-flex justify-content-between align-items-center">Software Inventory (<?= count($software) ?>) <button class="btn btn-sm btn-success" onclick="openModal('addSoftwareModal')"><i class="fas fa-plus me-1"></i>Add Software</button></div><div class="card-body" style="max-height:400px;overflow-y:auto">
<?php if (empty($software)): ?><div class="empty-state"><i class="fas fa-code"></i><p>No software inventory data.</p></div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-hover" id="tblSoftware">
<thead><tr><th>Software</th><th>Details</th><th>Status</th><th>Seats</th><th>Expiry</th><th style="width:60px"></th></tr></thead>
<tbody>
<?php foreach ($software as $s): ?>
<tr>
<td class="small"><strong><?= htmlspecialchars($s['software_name']) ?></strong></td>
<td class="small text-muted">v<?= htmlspecialchars($s['version']??'') ?> &middot; <?= htmlspecialchars($s['category']??$s['license_type']??'') ?></td>
<td><span class="badge bg-<?= ($s['status']??'') === 'Active' ? 'success' : (($s['status']??'') === 'Expired' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($s['status']??'') ?></span></td>
<td class="small"><?= (int)($s['seats']??0) ?></td>
<td class="small"><?= htmlspecialchars($s['expiry_date']??'N/A') ?></td>
<td class="text-end">
<button class="btn btn-sm btn-outline-warning" title="Edit" onclick="editSoftware(<?= (int)($s['id']??0) ?>,'<?= htmlspecialchars(addslashes($s['status']??'Active'),ENT_QUOTES) ?>',<?= (int)($s['seats']??1) ?>)"><i class="fas fa-pen"></i></button>
<button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteSoftware(<?= (int)($s['id']??0) ?>,'<?= htmlspecialchars(addslashes($s['software_name']??''),ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-12"><div class="card"><div class="card-header">Compliance Records</div><div class="card-body">
<?php if (empty($compliance)): ?><div class="empty-state"><i class="fas fa-clipboard"></i><p>No compliance records.</p></div>
<?php else: ?>
<div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchNUGS" type="text" placeholder="Search..." onkeyup="filterTable('srchNUGS','tblNUGS')"></div>
<div class="table-responsive"><table id="tblNUGS" class="table table-sm"><thead><tr><th>Type</th><th>Document</th><th>Issue Date</th><th>Expiry Date</th><th>Status</th></tr></thead><tbody>
<?php foreach ($compliance as $c): ?>
<tr><td class="small"><?= htmlspecialchars($c['compliance_type']??'') ?></td><td class="small"><?= htmlspecialchars($c['document_name']??'') ?></td><td class="small"><?= htmlspecialchars($c['issue_date']??'') ?></td><td class="small"><?= htmlspecialchars($c['expiry_date']??'N/A') ?></td><td><span class="status-pill <?= ($c['status']??'') === 'Valid' ? 'success' : (($c['status']??'') === 'Expired' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($c['status']??'Pending') ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div></div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
const CSRF = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
const AJAX_URL = 'ict-policy.php?ajax=1';

function showToast(msg, type) {
    var t = document.getElementById('toastMsg');
    t.textContent = msg;
    t.className = 'toast-msg show ' + type;
    setTimeout(function(){ t.className = 'toastMsg'; }, 3000);
}

function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}

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

function postAction(data) {
    data.csrf_token = CSRF;
    return fetch(AJAX_URL, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(data) }).then(function(r){ return r.json(); });
}

function submitAddAsset(e) {
    e.preventDefault();
    var fd = new FormData(document.getElementById('addAssetForm'));
    var data = { action: 'add_asset' };
    fd.forEach(function(v,k){ data[k] = v; });
    postAction(data).then(function(r) {
        if (r.success) { showToast(r.message, 'success'); closeModal('addAssetModal'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(r.message, 'danger');
    });
    return false;
}

function editAsset(id, status, location, assigned) {
    document.getElementById('edit_asset_id').value = id;
    document.getElementById('edit_asset_status').value = status;
    document.getElementById('edit_asset_location').value = location;
    document.getElementById('edit_asset_assigned').value = assigned;
    openModal('editAssetModal');
}

function submitEditAsset(e) {
    e.preventDefault();
    var fd = new FormData(document.getElementById('editAssetForm'));
    var data = { action: 'update_asset' };
    fd.forEach(function(v,k){ data[k] = v; });
    postAction(data).then(function(r) {
        if (r.success) { showToast(r.message, 'success'); closeModal('editAssetModal'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(r.message, 'danger');
    });
    return false;
}

function deleteAsset(id, name) {
    if (!confirm('Delete asset "' + name + '"? This cannot be undone.')) return;
    postAction({ action: 'delete_asset', id: id }).then(function(r) {
        if (r.success) { showToast(r.message, 'success'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(r.message, 'danger');
    });
}

function submitAddSoftware(e) {
    e.preventDefault();
    var fd = new FormData(document.getElementById('addSoftwareForm'));
    var data = { action: 'add_software' };
    fd.forEach(function(v,k){ data[k] = v; });
    postAction(data).then(function(r) {
        if (r.success) { showToast(r.message, 'success'); closeModal('addSoftwareModal'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(r.message, 'danger');
    });
    return false;
}

function editSoftware(id, status, seats) {
    document.getElementById('edit_software_id').value = id;
    document.getElementById('edit_software_status').value = status;
    document.getElementById('edit_software_seats').value = seats;
    openModal('editSoftwareModal');
}

function submitEditSoftware(e) {
    e.preventDefault();
    var fd = new FormData(document.getElementById('editSoftwareForm'));
    var data = { action: 'update_software' };
    fd.forEach(function(v,k){ data[k] = v; });
    postAction(data).then(function(r) {
        if (r.success) { showToast(r.message, 'success'); closeModal('editSoftwareModal'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(r.message, 'danger');
    });
    return false;
}

function deleteSoftware(id, name) {
    if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;
    postAction({ action: 'delete_software', id: id }).then(function(r) {
        if (r.success) { showToast(r.message, 'success'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(r.message, 'danger');
    });
}

document.querySelectorAll('.modal-backdrop').forEach(function(m) {
    m.addEventListener('click', function(e) { if (e.target === m) { m.classList.remove('show'); document.body.style.overflow = ''; } });
});
</script>
</body></html>
