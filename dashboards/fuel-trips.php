<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['driver', 'director', 'manager']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$pageTitle = 'Fuel & Trip Management';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── AJAX CRUD Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'] ?? $_GET['ajax'] ?? '';
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']); exit;
    }
    if (!$conn) { echo json_encode(['success' => false, 'message' => 'Database unavailable']); exit; }

    switch ($action) {
        case 'add_fuel':
            $vid = (int)($_POST['vehicle_id'] ?? 0);
            $qty = (float)($_POST['fuel_quantity'] ?? 0);
            $cost = (float)($_POST['total_cost'] ?? 0);
            $date = trim($_POST['fueling_date'] ?? date('Y-m-d'));
            $did = (int)($_POST['driver_id'] ?? 0);
            if (!$vid || $qty <= 0) { echo json_encode(['success' => false, 'message' => 'Vehicle and quantity required']); exit; }
            $stmt = $conn->prepare("INSERT INTO fuel_management (vehicle_id, fuel_quantity, total_cost, fueling_date, driver_id) VALUES (?,?,?,?,?)");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]); exit; }
            $stmt->bind_param('idssi', $vid, $qty, $cost, $date, $did);
            $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Fuel record added' : 'Failed to add']); exit;

        case 'add_trip':
            $vid = (int)($_POST['vehicle_id'] ?? 0);
            $did = (int)($_POST['driver_id'] ?? 0);
            $tripId = (int)($_POST['trip_id'] ?? 0);
            $tDate = trim($_POST['trip_date'] ?? date('Y-m-d'));
            $route = trim($_POST['route_name'] ?? '');
            $dest = trim($_POST['end_location'] ?? '');
            $startOdo = (int)($_POST['start_odometer'] ?? 0);
            $endOdo = (int)($_POST['end_odometer'] ?? 0);
            $fuel = (float)($_POST['fuel_used'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            if (!$vid) { echo json_encode(['success' => false, 'message' => 'Vehicle required']); exit; }
            $stmt = $conn->prepare("INSERT INTO trip_logs (vehicle_id, driver_id, trip_id, trip_date, route_name, end_location, start_odometer, end_odometer, fuel_used, notes) VALUES (?,?,?,?,?,?,?,?,?,?)");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]); exit; }
            $stmt->bind_param('iiisssiiis', $vid, $did, $tripId, $tDate, $route, $dest, $startOdo, $endOdo, $fuel, $notes);
            $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Trip log added' : 'Failed to add']); exit;

        case 'update_fuel':
            $id = (int)($_POST['id'] ?? 0);
            $vid = (int)($_POST['vehicle_id'] ?? 0);
            $qty = (float)($_POST['fuel_quantity'] ?? 0);
            $cost = (float)($_POST['total_cost'] ?? 0);
            $date = trim($_POST['fueling_date'] ?? '');
            $did = (int)($_POST['driver_id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("UPDATE fuel_management SET vehicle_id=?, fuel_quantity=?, total_cost=?, fueling_date=?, driver_id=? WHERE id=?");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]); exit; }
            $stmt->bind_param('idssii', $vid, $qty, $cost, $date, $did, $id);
            $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Fuel record updated' : 'Failed to update']); exit;

        case 'update_trip':
            $id = (int)($_POST['id'] ?? 0);
            $vid = (int)($_POST['vehicle_id'] ?? 0);
            $did = (int)($_POST['driver_id'] ?? 0);
            $tripId = (int)($_POST['trip_id'] ?? 0);
            $tDate = trim($_POST['trip_date'] ?? '');
            $route = trim($_POST['route_name'] ?? '');
            $dest = trim($_POST['end_location'] ?? '');
            $startOdo = (int)($_POST['start_odometer'] ?? 0);
            $endOdo = (int)($_POST['end_odometer'] ?? 0);
            $fuel = (float)($_POST['fuel_used'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("UPDATE trip_logs SET vehicle_id=?, driver_id=?, trip_id=?, trip_date=?, route_name=?, end_location=?, start_odometer=?, end_odometer=?, fuel_used=?, notes=? WHERE id=?");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]); exit; }
            $stmt->bind_param('iiisssiiisi', $vid, $did, $tripId, $tDate, $route, $dest, $startOdo, $endOdo, $fuel, $notes, $id);
            $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Trip log updated' : 'Failed to update']); exit;

        case 'delete_fuel':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM fuel_management WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']); exit;

        case 'delete_trip':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $stmt = $conn->prepare("DELETE FROM trip_logs WHERE id=?");
            $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Failed']); exit;

        case 'search':
            $q = '%' . trim($_POST['q'] ?? '') . '%';
            $fuelResults = [];
            $stmt = $conn->prepare("SELECT f.*, v.vehicle_name FROM fuel_management f LEFT JOIN vehicles v ON f.vehicle_id=v.id WHERE v.vehicle_name LIKE ? OR f.fueling_date LIKE ? ORDER BY f.fueling_date DESC LIMIT 100");
            $stmt->bind_param('ss', $q, $q); $stmt->execute(); $r = $stmt->get_result();
            while ($row = $r->fetch_assoc()) $fuelResults[] = $row; $stmt->close();
            $tripResults = [];
            $stmt2 = $conn->prepare("SELECT t.*, v.vehicle_name, s.full_name AS driver_name FROM trip_logs t LEFT JOIN vehicles v ON t.vehicle_id=v.id LEFT JOIN staff s ON t.driver_id=s.id WHERE v.vehicle_name LIKE ? OR t.route_name LIKE ? OR t.end_location LIKE ? OR s.full_name LIKE ? ORDER BY t.trip_date DESC LIMIT 100");
            $stmt2->bind_param('ssss', $q, $q, $q, $q); $stmt2->execute(); $r2 = $stmt2->get_result();
            while ($row = $r2->fetch_assoc()) $tripResults[] = $row; $stmt2->close();
            echo json_encode(['success' => true, 'fuel' => $fuelResults, 'trips' => $tripResults]); exit;
    }
    echo json_encode(['success' => false, 'message' => 'Unknown action']); exit;
}

// ── Fetch Data ──
$fuel = []; $trips = []; $vehicles = []; $drivers = [];
if ($conn) {
    $r = $conn->query("SELECT f.*, v.vehicle_name FROM fuel_management f LEFT JOIN vehicles v ON f.vehicle_id=v.id ORDER BY f.fueling_date DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $fuel[] = $row;
    $r2 = $conn->query("SELECT t.*, v.vehicle_name, s.full_name AS driver_name FROM trip_logs t LEFT JOIN vehicles v ON t.vehicle_id=v.id LEFT JOIN staff s ON t.driver_id=s.id ORDER BY t.trip_date DESC LIMIT 100");
    if ($r2) while ($row = $r2->fetch_assoc()) $trips[] = $row;
    $r3 = $conn->query("SELECT * FROM vehicles ORDER BY vehicle_name");
    if ($r3) while ($row = $r3->fetch_assoc()) $vehicles[] = $row;
    $r4 = $conn->query("SELECT id, full_name FROM staff ORDER BY full_name");
    if ($r4) while ($row = $r4->fetch_assoc()) $drivers[] = $row;
}

$totalFuel = count($fuel);
$totalTrips = count($trips);
$totalVehicles = count($vehicles);
$fuelCost = array_sum(array_column($fuel, 'total_cost'));
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
        <h1><i class="fas fa-truck"></i> Fuel & Trip Management</h1>
        <div class="float-end">
            <input type="text" id="searchBox" class="form-control d-inline-block" style="width:200px" placeholder="Search..." onkeyup="doSearch()">
            <button class="btn btn-sm btn-primary" onclick="openFuelModal()"><i class="fas fa-plus"></i> Add Fuel</button>
            <button class="btn btn-sm btn-success" onclick="openTripModal()"><i class="fas fa-plus"></i> Add Trip</button>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Vehicles</h6><h3><?= $totalVehicles ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Fuel Records</h6><h3><?= $totalFuel ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Trips</h6><h3><?= $totalTrips ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Fuel Cost</h6><h3><?= number_format($fuelCost, 0) ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Vehicles</h5></div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($vehicles as $v): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= htmlspecialchars($v['vehicle_name'] ?? $v['license_plate'] ?? '-') ?>
                            <span class="badge bg-<?= ($v['status'] ?? 'Available') === 'Available' ? 'success' : 'secondary' ?>"><?= $v['status'] ?? 'Available' ?></span>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($vehicles)): ?><li class="list-group-item text-center">No vehicles</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Recent Fuel Records</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Vehicle</th><th>Liters</th><th>Cost</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody id="fuelTable">
                                <?php foreach (array_slice($fuel, 0, 10) as $f): ?>
                                <tr>
                                    <td><?= htmlspecialchars($f['vehicle_name'] ?? $f['vehicle'] ?? '-') ?></td>
                                    <td><?= number_format($f['fuel_quantity'] ?? 0, 1) ?></td>
                                    <td><?= number_format($f['total_cost'] ?? 0, 0) ?></td>
                                    <td><?= $f['fueling_date'] ?? $f['created_at'] ?? '-' ?></td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-primary" onclick="editFuel(<?= htmlspecialchars(json_encode($f), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-xs btn-outline-danger" onclick="deleteFuel(<?= (int)$f['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($fuel)): ?><tr><td colspan="5" class="text-center">No fuel records</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Recent Trips</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Vehicle</th><th>Driver</th><th>Destination</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody id="tripTable">
                                <?php foreach (array_slice($trips, 0, 10) as $t): ?>
                                <tr>
                                    <td><?= htmlspecialchars($t['vehicle_name'] ?? $t['vehicle'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($t['driver_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($t['route_name'] ?? $t['end_location'] ?? '-') ?></td>
                                    <td><?= $t['trip_date'] ?? $t['created_at'] ?? '-' ?></td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-primary" onclick="editTrip(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-xs btn-outline-danger" onclick="deleteTrip(<?= (int)$t['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($trips)): ?><tr><td colspan="5" class="text-center">No trip records</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Fuel Modal -->
<div class="modal fade" id="fuelModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="fuelModalTitle">Add Fuel Record</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="fuelEditId" value="">
        <div class="mb-3"><label class="form-label">Vehicle *</label>
            <select class="form-select" id="fuelVehicle"><option value="">Select Vehicle</option>
                <?php foreach ($vehicles as $v): ?><option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['vehicle_name'] ?? $v['license_plate'] ?? '') ?></option><?php endforeach; ?>
            </select></div>
        <div class="row mb-3"><div class="col"><label class="form-label">Liters *</label><input type="number" step="0.1" class="form-control" id="fuelQty"></div>
            <div class="col"><label class="form-label">Total Cost</label><input type="number" step="0.01" class="form-control" id="fuelCost"></div></div>
        <div class="row mb-3"><div class="col"><label class="form-label">Date *</label><input type="date" class="form-control" id="fuelDate" value="<?= date('Y-m-d') ?>"></div>
            <div class="col"><label class="form-label">Driver</label>
                <select class="form-select" id="fuelDriver"><option value="0">Select Driver</option>
                    <?php foreach ($drivers as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option><?php endforeach; ?>
                </select></div></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="fuelSaveBtn" onclick="saveFuel()">Save</button></div>
</div></div></div>

<!-- Add Trip Modal -->
<div class="modal fade" id="tripModal" tabindex="-1"><div class="modal-dialog"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="tripModalTitle">Add Trip Log</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="tripEditId" value="">
        <div class="row mb-3">
            <div class="col"><label class="form-label">Vehicle *</label>
                <select class="form-select" id="tripVehicle"><option value="">Select Vehicle</option>
                    <?php foreach ($vehicles as $v): ?><option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['vehicle_name'] ?? $v['license_plate'] ?? '') ?></option><?php endforeach; ?>
                </select></div>
            <div class="col"><label class="form-label">Driver</label>
                <select class="form-select" id="tripDriver"><option value="0">Select Driver</option>
                    <?php foreach ($drivers as $d): ?><option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option><?php endforeach; ?>
                </select></div>
        </div>
        <div class="row mb-3">
            <div class="col"><label class="form-label">Trip Date *</label><input type="date" class="form-control" id="tripDate" value="<?= date('Y-m-d') ?>"></div>
            <div class="col"><label class="form-label">Route Name</label><input type="text" class="form-control" id="tripRoute" placeholder="e.g. Kampala - Iganga"></div>
            <div class="col"><label class="form-label">Destination</label><input type="text" class="form-control" id="tripDest"></div>
        </div>
        <div class="row mb-3">
            <div class="col"><label class="form-label">Start Odometer</label><input type="number" class="form-control" id="tripStartOdo"></div>
            <div class="col"><label class="form-label">End Odometer</label><input type="number" class="form-control" id="tripEndOdo"></div>
            <div class="col"><label class="form-label">Fuel Used (L)</label><input type="number" step="0.1" class="form-control" id="tripFuelUsed"></div>
        </div>
        <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" id="tripNotes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" id="tripSaveBtn" onclick="saveTrip()">Save</button></div>
</div></div></div>

<script>
const CSRF = '<?= $_SESSION['csrf_token'] ?>';
const AJAX_URL = 'fuel-trips.php?ajax=1';

function openFuelModal() {
    document.getElementById('fuelEditId').value = '';
    document.getElementById('fuelModalTitle').textContent = 'Add Fuel Record';
    document.getElementById('fuelSaveBtn').textContent = 'Save';
    document.getElementById('fuelVehicle').value = '';
    document.getElementById('fuelQty').value = '';
    document.getElementById('fuelCost').value = '';
    document.getElementById('fuelDate').value = '<?= date('Y-m-d') ?>';
    document.getElementById('fuelDriver').value = '0';
    new bootstrap.Modal(document.getElementById('fuelModal')).show();
}

function openTripModal() {
    document.getElementById('tripEditId').value = '';
    document.getElementById('tripModalTitle').textContent = 'Add Trip Log';
    document.getElementById('tripSaveBtn').textContent = 'Save';
    document.getElementById('tripVehicle').value = '';
    document.getElementById('tripDriver').value = '0';
    document.getElementById('tripDate').value = '<?= date('Y-m-d') ?>';
    document.getElementById('tripRoute').value = '';
    document.getElementById('tripDest').value = '';
    document.getElementById('tripStartOdo').value = '';
    document.getElementById('tripEndOdo').value = '';
    document.getElementById('tripFuelUsed').value = '';
    document.getElementById('tripNotes').value = '';
    new bootstrap.Modal(document.getElementById('tripModal')).show();
}

function saveFuel() {
    const editId = document.getElementById('fuelEditId').value;
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('ajax_action', editId ? 'update_fuel' : 'add_fuel');
    if (editId) fd.append('id', editId);
    fd.append('vehicle_id', document.getElementById('fuelVehicle').value);
    fd.append('fuel_quantity', document.getElementById('fuelQty').value);
    fd.append('total_cost', document.getElementById('fuelCost').value);
    fd.append('fueling_date', document.getElementById('fuelDate').value);
    fd.append('driver_id', document.getElementById('fuelDriver').value);
    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}

function saveTrip() {
    const editId = document.getElementById('tripEditId').value;
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('ajax_action', editId ? 'update_trip' : 'add_trip');
    if (editId) fd.append('id', editId);
    fd.append('vehicle_id', document.getElementById('tripVehicle').value);
    fd.append('driver_id', document.getElementById('tripDriver').value);
    fd.append('trip_date', document.getElementById('tripDate').value);
    fd.append('route_name', document.getElementById('tripRoute').value);
    fd.append('end_location', document.getElementById('tripDest').value);
    fd.append('start_odometer', document.getElementById('tripStartOdo').value);
    fd.append('end_odometer', document.getElementById('tripEndOdo').value);
    fd.append('fuel_used', document.getElementById('tripFuelUsed').value);
    fd.append('notes', document.getElementById('tripNotes').value);
    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}

function editFuel(f) {
    document.getElementById('fuelEditId').value = f.id;
    document.getElementById('fuelModalTitle').textContent = 'Edit Fuel Record';
    document.getElementById('fuelSaveBtn').textContent = 'Update';
    document.getElementById('fuelVehicle').value = f.vehicle_id || '';
    document.getElementById('fuelQty').value = f.fuel_quantity || '';
    document.getElementById('fuelCost').value = f.total_cost || '';
    document.getElementById('fuelDate').value = f.fueling_date || '';
    document.getElementById('fuelDriver').value = f.driver_id || '0';
    new bootstrap.Modal(document.getElementById('fuelModal')).show();
}

function editTrip(t) {
    document.getElementById('tripEditId').value = t.id;
    document.getElementById('tripModalTitle').textContent = 'Edit Trip Log';
    document.getElementById('tripSaveBtn').textContent = 'Update';
    document.getElementById('tripVehicle').value = t.vehicle_id || '';
    document.getElementById('tripDriver').value = t.driver_id || '0';
    document.getElementById('tripDate').value = t.trip_date || '';
    document.getElementById('tripRoute').value = t.route_name || '';
    document.getElementById('tripDest').value = t.end_location || '';
    document.getElementById('tripStartOdo').value = t.start_odometer || '';
    document.getElementById('tripEndOdo').value = t.end_odometer || '';
    document.getElementById('tripFuelUsed').value = t.fuel_used || '';
    document.getElementById('tripNotes').value = t.notes || '';
    new bootstrap.Modal(document.getElementById('tripModal')).show();
}

function deleteFuel(id) {
    if (!confirm('Delete this fuel record?')) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('ajax_action', 'delete_fuel'); fd.append('id', id);
    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}

function deleteTrip(id) {
    if (!confirm('Delete this trip log?')) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('ajax_action', 'delete_trip'); fd.append('id', id);
    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message); });
}

let searchTimer;
function doSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const q = document.getElementById('searchBox').value;
        if (q.length < 2) { location.reload(); return; }
        const fd = new FormData();
        fd.append('ajax_action', 'search'); fd.append('q', q);
        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (!d.success) return;
                const ft = document.getElementById('fuelTable');
                if (!d.fuel || !d.fuel.length) { ft.innerHTML = '<tr><td colspan="5" class="text-center">No results</td></tr>'; }
                else {
                    ft.innerHTML = d.fuel.map(f => `<tr>
                        <td>${f.vehicle_name||'-'}</td><td>${parseFloat(f.fuel_quantity||0).toFixed(1)}</td>
                        <td>${parseFloat(f.total_cost||0).toLocaleString()}</td><td>${f.fueling_date||'-'}</td>
                        <td><button class="btn btn-xs btn-outline-primary" onclick='editFuel(${JSON.stringify(f)})'><i class="fas fa-edit"></i></button>
                        <button class="btn btn-xs btn-outline-danger" onclick="deleteFuel(${f.id})"><i class="fas fa-trash"></i></button></td>
                    </tr>`).join('');
                }
                const tt = document.getElementById('tripTable');
                if (!d.trips || !d.trips.length) { tt.innerHTML = '<tr><td colspan="5" class="text-center">No results</td></tr>'; }
                else {
                    tt.innerHTML = d.trips.map(t => `<tr>
                        <td>${t.vehicle_name||'-'}</td><td>${t.driver_name||'-'}</td>
                        <td>${t.route_name||t.end_location||'-'}</td><td>${t.trip_date||'-'}</td>
                        <td><button class="btn btn-xs btn-outline-primary" onclick='editTrip(${JSON.stringify(t)})'><i class="fas fa-edit"></i></button>
                        <button class="btn btn-xs btn-outline-danger" onclick="deleteTrip(${t.id})"><i class="fas fa-trash"></i></button></td>
                    </tr>`).join('');
                }
            });
    }, 400);
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
