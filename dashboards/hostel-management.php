<?php
$pageTitle = 'Hostel Management';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hostel','matron','warden','registrar','director','principal']);
$conn = $ctx['staff'];
$conn2 = $ctx['students'] ?? null;

$hdb_init = $conn2 ?: $conn;
$hprefix_init = ($hdb_init === $conn && !$conn2) ? 'igangaschool_students.' : '';
if ($hdb_init) {
    @$hdb_init->query("CREATE TABLE IF NOT EXISTS `{$hprefix_init}`hostel_rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_number VARCHAR(50) NOT NULL,
        hostel_name VARCHAR(200) DEFAULT '',
        hostel_id INT DEFAULT NULL,
        capacity INT DEFAULT 4,
        occupancy INT DEFAULT 0,
        room_type VARCHAR(50) DEFAULT 'Standard',
        status VARCHAR(30) DEFAULT 'Available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_status (status),
        KEY idx_hostel (hostel_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @$hdb_init->query("CREATE TABLE IF NOT EXISTS `{$hprefix_init}`hostel_allocations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        room_id INT NOT NULL,
        academic_year VARCHAR(10) DEFAULT '',
        semester VARCHAR(50) DEFAULT '',
        check_in_date DATE DEFAULT NULL,
        check_out_date DATE DEFAULT NULL,
        status VARCHAR(30) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_room (room_id),
        KEY idx_student (student_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @$hdb_init->query("CREATE TABLE IF NOT EXISTS `{$hprefix_init}`hostel (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        capacity INT DEFAULT 0,
        status VARCHAR(30) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$totalRooms = 0; $occupied = 0; $available = 0; $maintenance = 0;
$rooms = [];

// hostel_rooms and hostel are in students_db — use $conn2 when available
$hdb = $conn2 ?: $conn;
$hprefix = ($hdb === $conn && !$conn2) ? 'igangaschool_students.' : '';

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');
    if (!$hdb) {
        echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
        exit;
    }
    try {
        $colCheck = @$hdb->query("SHOW COLUMNS FROM {$hprefix}hostel_rooms LIKE 'room_type'");
        if ($colCheck && $colCheck->num_rows === 0) {
            @$hdb->query("ALTER TABLE {$hprefix}hostel_rooms ADD COLUMN `room_type` VARCHAR(50) DEFAULT 'Standard'");
        }
        switch ($action) {
            case 'add_room': {
                $block_name = trim($_POST['block_name'] ?? '');
                $room_number = trim($_POST['room_number'] ?? '');
                $capacity = max(1, (int)($_POST['capacity'] ?? 4));
                $room_type = trim($_POST['room_type'] ?? 'Standard');
                if (empty($block_name) || empty($room_number)) throw new Exception('Block name and room number are required.');
                $chk = $hdb->prepare("SELECT id FROM {$hprefix}hostel_rooms WHERE room_number = ?");
                $chk->bind_param('s', $room_number);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) { $chk->close(); throw new Exception('A room with this number already exists.'); }
                $chk->close();
                $stmt = $hdb->prepare("INSERT INTO {$hprefix}hostel_rooms (room_number, hostel_name, capacity, occupancy, room_type, status) VALUES (?, ?, ?, 0, ?, 'Available')");
                $stmt->bind_param('ssis', $room_number, $block_name, $capacity, $room_type);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Room added successfully.']);
                break;
            }
            case 'update_room': {
                $id = (int)($_POST['id'] ?? 0);
                $capacity = max(1, (int)($_POST['capacity'] ?? 4));
                $room_type = trim($_POST['room_type'] ?? '');
                $status = trim($_POST['status'] ?? 'Available');
                if ($id <= 0) throw new Exception('Invalid room ID.');
                if (!in_array($status, ['Available', 'Full', 'Maintenance'])) throw new Exception('Invalid status.');
                if (!empty($room_type)) {
                    $stmt = $hdb->prepare("UPDATE {$hprefix}hostel_rooms SET capacity=?, room_type=?, status=? WHERE id=?");
                    $stmt->bind_param('issi', $capacity, $room_type, $status, $id);
                } else {
                    $stmt = $hdb->prepare("UPDATE {$hprefix}hostel_rooms SET capacity=?, status=? WHERE id=?");
                    $stmt->bind_param('isi', $capacity, $status, $id);
                }
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Room updated successfully.']);
                break;
            }
            case 'delete_room': {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception('Invalid room ID.');
                $chk = $hdb->prepare("SELECT COUNT(*) c FROM {$hprefix}hostel_allocations WHERE room_id = ? AND status = 'Active'");
                $chk->bind_param('i', $id);
                $chk->execute();
                $active = (int)$chk->get_result()->fetch_assoc()['c'];
                $chk->close();
                if ($active > 0) throw new Exception('Cannot delete room with active student allocations.');
                $stmt = $hdb->prepare("DELETE FROM {$hprefix}hostel_rooms WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Room deleted successfully.']);
                break;
            }
            case 'allocate': {
                $room_id = (int)($_POST['room_id'] ?? 0);
                $student_number = trim($_POST['student_number'] ?? '');
                if ($room_id <= 0 || empty($student_number)) throw new Exception('Room and student number are required.');
                $chk = $hdb->prepare("SELECT id, capacity, occupancy, status FROM {$hprefix}hostel_rooms WHERE id = ?");
                $chk->bind_param('i', $room_id);
                $chk->execute();
                $room = $chk->get_result()->fetch_assoc();
                $chk->close();
                if (!$room) throw new Exception('Room not found.');
                if ((int)$room['occupancy'] >= (int)$room['capacity']) throw new Exception('Room is at full capacity.');
                $currentYear = (string)date('Y');
                $currentSem = (int)date('n') <= 6 ? 'Semester 1' : 'Semester 2';
                $chk2 = $hdb->prepare("SELECT id FROM {$hprefix}hostel_allocations WHERE student_id = ? AND academic_year = ? AND status = 'Active'");
                $chk2->bind_param('ss', $student_number, $currentYear);
                $chk2->execute();
                if ($chk2->get_result()->num_rows > 0) { $chk2->close(); throw new Exception('Student already has an active allocation this year.'); }
                $chk2->close();
                $stmt = $hdb->prepare("INSERT INTO {$hprefix}hostel_allocations (student_id, room_id, academic_year, semester, check_in_date, status) VALUES (?, ?, ?, ?, CURDATE(), 'Active')");
                $stmt->bind_param('siss', $student_number, $room_id, $currentYear, $currentSem);
                $stmt->execute();
                $newId = $stmt->insert_id;
                $stmt->close();
                $upd = $hdb->prepare("UPDATE {$hprefix}hostel_rooms SET occupancy = occupancy + 1, status = IF(occupancy + 1 >= capacity, 'Full', status) WHERE id = ?");
                $upd->bind_param('i', $room_id);
                $upd->execute();
                $upd->close();
                echo json_encode(['success' => true, 'message' => 'Student allocated successfully.', 'allocation_id' => $newId]);
                break;
            }
            case 'deallocate': {
                $allocation_id = (int)($_POST['allocation_id'] ?? 0);
                if ($allocation_id <= 0) throw new Exception('Invalid allocation ID.');
                $chk = $hdb->prepare("SELECT id, room_id FROM {$hprefix}hostel_allocations WHERE id = ? AND status = 'Active'");
                $chk->bind_param('i', $allocation_id);
                $chk->execute();
                $alloc = $chk->get_result()->fetch_assoc();
                $chk->close();
                if (!$alloc) throw new Exception('Active allocation not found.');
                $stmt = $hdb->prepare("UPDATE {$hprefix}hostel_allocations SET status = 'Checked Out', check_out_date = CURDATE() WHERE id = ?");
                $stmt->bind_param('i', $allocation_id);
                $stmt->execute();
                $stmt->close();
                $upd = $hdb->prepare("UPDATE {$hprefix}hostel_rooms SET occupancy = GREATEST(0, occupancy - 1), status = IF(status = 'Full' AND occupancy - 1 < capacity, 'Available', status) WHERE id = ?");
                $upd->bind_param('i', $alloc['room_id']);
                $upd->execute();
                $upd->close();
                echo json_encode(['success' => true, 'message' => 'Student deallocated successfully.']);
                break;
            }
            case 'get_room': {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception('Invalid room ID.');
                $stmt = $hdb->prepare("SELECT * FROM {$hprefix}hostel_rooms WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $room = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                echo json_encode(['success' => true, 'room' => $room]);
                break;
            }
            case 'get_allocations': {
                $room_id = (int)($_POST['room_id'] ?? 0);
                if ($room_id <= 0) throw new Exception('Invalid room ID.');
                $stmt = $hdb->prepare("SELECT a.*, hr.room_number FROM {$hprefix}hostel_allocations a JOIN {$hprefix}hostel_rooms hr ON a.room_id = hr.id WHERE a.room_id = ? AND a.status = 'Active'");
                $stmt->bind_param('i', $room_id);
                $stmt->execute();
                $allocs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                echo json_encode(['success' => true, 'allocations' => $allocs]);
                break;
            }
            case 'search_rooms': {
                $block = trim($_POST['block'] ?? '');
                $status = trim($_POST['status'] ?? '');
                $where = [];
                $types = '';
                $params = [];
                if (!empty($block)) { $where[] = "hostel_name = ?"; $types .= 's'; $params[] = $block; }
                if (!empty($status)) { $where[] = "status = ?"; $types .= 's'; $params[] = $status; }
                $sql = "SELECT id, room_number, hostel_name, capacity, occupancy, room_type, status FROM {$hprefix}hostel_rooms";
                if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
                $sql .= " ORDER BY room_number";
                $stmt = $hdb->prepare($sql);
                if (!empty($params)) $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                echo json_encode(['success' => true, 'rooms' => $results]);
                break;
            }
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($hdb) {
    $t = $hdb->query("SELECT COUNT(*) c FROM {$hprefix}hostel_rooms");
    if ($t) $totalRooms = (int)$t->fetch_assoc()['c'];
    $o = $hdb->query("SELECT COUNT(*) c FROM {$hprefix}hostel_rooms WHERE status='Full'");
    if ($o) $occupied = (int)$o->fetch_assoc()['c'];
    $a = $hdb->query("SELECT COUNT(*) c FROM {$hprefix}hostel_rooms WHERE status='Available'");
    if ($a) $available = (int)$a->fetch_assoc()['c'];
    $m = $hdb->query("SELECT COUNT(*) c FROM {$hprefix}hostel_rooms WHERE status='Maintenance'");
    if ($m) $maintenance = (int)$m->fetch_assoc()['c'];
    $r = $hdb->query("SELECT r.id, r.room_number, COALESCE(h.name,r.hostel_name) hostel_name, r.capacity, r.occupancy, r.status, COALESCE(r.room_type,'Standard') room_type FROM {$hprefix}hostel_rooms r LEFT JOIN {$hprefix}hostel h ON r.hostel_id=h.id ORDER BY r.room_number");
    if ($r) while ($row = $r->fetch_assoc()) $rooms[] = $row;
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
        <h4 class="fw-bold mb-0"><i class="fas fa-bed me-2"></i>Hostel Management</h4> <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-door-open"></i></div>
                <div class="stat-content"><h3><?= number_format($totalRooms) ?></h3><p>Total Rooms</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
                <div class="stat-content"><h3><?= number_format($occupied) ?></h3><p>Occupied</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-check"></i></div>
                <div class="stat-content"><h3><?= number_format($available) ?></h3><p>Available</p></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-tools"></i></div>
                <div class="stat-content"><h3><?= number_format($maintenance) ?></h3><p>Under Maintenance</p></div>
            </div>
        </div>
    </div>
    <div class="content-section">
        <h5 class="fw-bold mb-3"><i class="fas fa-list me-2"></i>Hostel Rooms</h5>
        <?php if (empty($rooms)): ?>
        <div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2"></i><p class="mb-0">No hostel rooms found.</p></div>
        <?php else: ?>
        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
            <select class="form-select form-select-sm" id="filterBlock" style="max-width:200px" onchange="applyFilters()">
                <option value="">All Blocks</option>
                <?php
                $blockList = array_values(array_unique(array_filter(array_column($rooms, 'hostel_name'))));
                sort($blockList);
                foreach ($blockList as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm" id="filterStatus" style="max-width:180px" onchange="applyFilters()">
                <option value="">All Status</option>
                <option value="Available">Available</option>
                <option value="Full">Full</option>
                <option value="Maintenance">Under Maintenance</option>
            </select>
            <input class="form-control form-control-sm" style="max-width:200px" id="srchJLRH" type="text" placeholder="Search..." onkeyup="filterTable('srchJLRH','tblJLRH')">
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-primary" onclick="showAddRoomModal()"><i class="fas fa-plus me-1"></i>Add Room</button>
                <button class="btn btn-sm btn-success" onclick="showAllocateModal()"><i class="fas fa-user-plus me-1"></i>Allocate</button>
            </div>
        </div>
<div class="table-responsive">
            <table id="tblJLRH" class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Room #</th><th>Hostel</th><th>Capacity</th><th>Occupants</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $rm): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($rm['room_number']) ?></strong></td>
                        <td><?= htmlspecialchars($rm['hostel_name'] ?? '-') ?></td>
                        <td><?= (int)($rm['capacity'] ?? 0) ?></td>
                        <td><?= (int)($rm['occupancy'] ?? 0) ?></td>
                        <td>
                            <?php $sc = $rm['status'] === 'Available' ? 'success' : ($rm['status'] === 'Full' ? 'warning' : ($rm['status'] === 'Maintenance' ? 'info' : 'secondary')); ?>
                            <span class="badge bg-<?= $sc ?>"><?= htmlspecialchars($rm['status']) ?></span>
                        </td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" onclick="showEditRoomModal(<?= (int)($rm['id'] ?? 0) ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(<?= (int)($rm['id'] ?? 0) ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                            <button class="btn btn-sm btn-outline-info" onclick="showDeallocateModal(<?= (int)($rm['id'] ?? 0) ?>)" title="Allocations"><i class="fas fa-users"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small mb-0">Showing <?= count($rooms) ?> room(s).</p>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="roomModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="roomFormTitle">Add Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="room_id">
        <div class="mb-3"><label class="form-label">Block / Hostel Name *</label><input type="text" class="form-control" id="roomBlockName" placeholder="e.g. Block A - Queen Anne"></div>
        <div class="mb-3"><label class="form-label">Room Number *</label><input type="text" class="form-control" id="roomNumber" placeholder="e.g. QA-1-01"></div>
        <div class="row mb-3">
            <div class="col"><label class="form-label">Capacity</label><input type="number" class="form-control" id="roomCapacity" value="4" min="1"></div>
            <div class="col"><label class="form-label">Room Type</label><select class="form-select" id="roomType"><option value="Standard">Standard</option><option value="Single">Single</option><option value="Double">Double</option><option value="Dormitory">Dormitory</option></select></div>
        </div>
        <div class="mb-3" id="roomStatusGroup" style="display:none"><label class="form-label">Status</label><select class="form-select" id="roomStatus"><option value="Available">Available</option><option value="Full">Full</option><option value="Maintenance">Under Maintenance</option></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveRoom()"><i class="fas fa-save me-1"></i>Save</button></div>
</div></div></div>

<div class="modal fade" id="allocateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Allocate Student to Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Select Room *</label><select class="form-select" id="allocRoomId"><option value="">Select Room</option></select></div>
        <div class="mb-3"><label class="form-label">Student Number *</label><input type="text" class="form-control" id="allocStudentNumber" placeholder="Enter student number"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-success" onclick="allocateStudent()"><i class="fas fa-user-plus me-1"></i>Allocate</button></div>
</div></div></div>

<div class="modal fade" id="deallocateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Active Allocations</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="allocList"></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
</div></div></div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;

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

function escHtml(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(s || '')); return d.innerHTML; }

function showMsg(msg, type) {
    var c = document.createElement('div');
    c.className = 'alert alert-' + (type || 'danger') + ' alert-dismissible fade show position-fixed top-0 end-0 m-3';
    c.style.zIndex = '9999';
    c.innerHTML = escHtml(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.body.appendChild(c);
    setTimeout(function(){ c.remove(); }, 5000);
}

function applyFilters() {
    var block = document.getElementById('filterBlock').value;
    var status = document.getElementById('filterStatus').value;
    $.post('', { action: 'search_rooms', block: block, status: status, csrf_token: CSRF_TOKEN }, function(r) {
        if (!r.success) { showMsg(r.message); return; }
        var tbody = document.querySelector('#tblJLRH tbody');
        if (!tbody) return;
        if (!r.rooms || r.rooms.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">No rooms match the filter.</td></tr>';
            return;
        }
        var html = '';
        r.rooms.forEach(function(rm) {
            var sc = rm.status === 'Available' ? 'success' : (rm.status === 'Full' ? 'warning' : (rm.status === 'Maintenance' ? 'info' : 'secondary'));
            html += '<tr>';
            html += '<td><strong>' + escHtml(rm.room_number) + '</strong></td>';
            html += '<td>' + escHtml(rm.hostel_name || '-') + '</td>';
            html += '<td>' + (parseInt(rm.capacity) || 0) + '</td>';
            html += '<td>' + (parseInt(rm.occupancy) || 0) + '</td>';
            html += '<td><span class="badge bg-' + sc + '">' + escHtml(rm.status) + '</span></td>';
            html += '<td class="text-nowrap">';
            html += '<button class="btn btn-sm btn-outline-primary" onclick="showEditRoomModal(' + rm.id + ')" title="Edit"><i class="fas fa-edit"></i></button> ';
            html += '<button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(' + rm.id + ')" title="Delete"><i class="fas fa-trash"></i></button> ';
            html += '<button class="btn btn-sm btn-outline-info" onclick="showDeallocateModal(' + rm.id + ')" title="Allocations"><i class="fas fa-users"></i></button>';
            html += '</td></tr>';
        });
        tbody.innerHTML = html;
    }, 'json');
}

function showAddRoomModal() {
    document.getElementById('roomFormTitle').textContent = 'Add Room';
    document.getElementById('room_id').value = '';
    document.getElementById('roomBlockName').value = '';
    document.getElementById('roomNumber').value = '';
    document.getElementById('roomNumber').readOnly = false;
    document.getElementById('roomCapacity').value = '4';
    document.getElementById('roomType').value = 'Standard';
    document.getElementById('roomStatusGroup').style.display = 'none';
    new bootstrap.Modal(document.getElementById('roomModal')).show();
}

function showEditRoomModal(id) {
    $.post('', { action: 'get_room', id: id, csrf_token: CSRF_TOKEN }, function(r) {
        if (!r.success || !r.room) { showMsg(r.message || 'Room not found.'); return; }
        document.getElementById('roomFormTitle').textContent = 'Edit Room';
        document.getElementById('room_id').value = r.room.id;
        document.getElementById('roomBlockName').value = r.room.hostel_name || '';
        document.getElementById('roomNumber').value = r.room.room_number || '';
        document.getElementById('roomNumber').readOnly = true;
        document.getElementById('roomCapacity').value = r.room.capacity || 4;
        document.getElementById('roomType').value = r.room.room_type || 'Standard';
        document.getElementById('roomStatus').value = r.room.status || 'Available';
        document.getElementById('roomStatusGroup').style.display = '';
        new bootstrap.Modal(document.getElementById('roomModal')).show();
    }, 'json');
}

function saveRoom() {
    var id = document.getElementById('room_id').value;
    var data = {
        action: id ? 'update_room' : 'add_room',
        id: id,
        block_name: document.getElementById('roomBlockName').value,
        room_number: document.getElementById('roomNumber').value,
        capacity: document.getElementById('roomCapacity').value,
        room_type: document.getElementById('roomType').value,
        status: document.getElementById('roomStatus').value,
        csrf_token: CSRF_TOKEN
    };
    $.post('', data, function(r) {
        if (r.success) { bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide(); location.reload(); }
        else showMsg(r.message);
    }, 'json');
}

function deleteRoom(id) {
    if (!confirm('Are you sure you want to delete this room?')) return;
    $.post('', { action: 'delete_room', id: id, csrf_token: CSRF_TOKEN }, function(r) {
        if (r.success) location.reload();
        else showMsg(r.message);
    }, 'json');
}

function showAllocateModal() {
    $.post('', { action: 'search_rooms', block: '', status: 'Available', csrf_token: CSRF_TOKEN }, function(r) {
        var sel = document.getElementById('allocRoomId');
        sel.innerHTML = '<option value="">Select Room</option>';
        if (r.success && r.rooms) {
            r.rooms.forEach(function(rm) {
                if (parseInt(rm.occupancy) < parseInt(rm.capacity)) {
                    sel.innerHTML += '<option value="' + rm.id + '">' + escHtml(rm.room_number) + ' - ' + escHtml(rm.hostel_name) + ' (' + rm.occupancy + '/' + rm.capacity + ')</option>';
                }
            });
        }
        document.getElementById('allocStudentNumber').value = '';
        new bootstrap.Modal(document.getElementById('allocateModal')).show();
    }, 'json');
}

function allocateStudent() {
    var data = {
        action: 'allocate',
        room_id: document.getElementById('allocRoomId').value,
        student_number: document.getElementById('allocStudentNumber').value,
        csrf_token: CSRF_TOKEN
    };
    if (!data.room_id || !data.student_number) { showMsg('Please select a room and enter a student number.'); return; }
    $.post('', data, function(r) {
        if (r.success) { bootstrap.Modal.getInstance(document.getElementById('allocateModal')).hide(); location.reload(); }
        else showMsg(r.message);
    }, 'json');
}

function showDeallocateModal(roomId) {
    $.post('', { action: 'get_allocations', room_id: roomId, csrf_token: CSRF_TOKEN }, function(r) {
        var list = document.getElementById('allocList');
        list.innerHTML = '';
        if (!r.success || !r.allocations || r.allocations.length === 0) {
            list.innerHTML = '<p class="text-muted mb-0">No active allocations for this room.</p>';
        } else {
            r.allocations.forEach(function(a) {
                list.innerHTML += '<div class="d-flex justify-content-between align-items-center border-bottom py-2">' +
                    '<span><i class="fas fa-user me-1"></i>' + escHtml(a.student_id) + ' (' + escHtml(a.room_number) + ')</span>' +
                    '<button class="btn btn-sm btn-outline-danger" onclick="deallocateStudent(' + a.id + ')"><i class="fas fa-user-minus me-1"></i>Remove</button>' +
                    '</div>';
            });
        }
        new bootstrap.Modal(document.getElementById('deallocateModal')).show();
    }, 'json');
}

function deallocateStudent(allocationId) {
    if (!confirm('Remove this student from the room?')) return;
    $.post('', { action: 'deallocate', allocation_id: allocationId, csrf_token: CSRF_TOKEN }, function(r) {
        if (r.success) location.reload();
        else showMsg(r.message);
    }, 'json');
}
</script>
</body>
</html>
