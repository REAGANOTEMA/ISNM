<?php
$pageTitle = 'Hostel Management';
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hostel','matron','warden','registrar','director','principal']);
$conn = $ctx['staff'];
$conn2 = $ctx['students'] ?? null;

$totalRooms = 0; $occupied = 0; $available = 0; $maintenance = 0;
$rooms = [];

// hostel_rooms and hostel are in students_db — use $conn2 when available
$hdb = $conn2 ?: $conn;
$hprefix = ($hdb === $conn && !$conn2) ? 'igangaschool_students.' : '';

if ($hdb) {
    $t = $hdb->query("SELECT COUNT(*) c FROM {$hprefix}hostel_rooms");
    if ($t) $totalRooms = (int)$t->fetch_assoc()['c'];
    $o = $hdb->query("SELECT COUNT(*) c FROM {$hprefix}hostel_rooms WHERE status='Occupied'");
    if ($o) $occupied = (int)$o->fetch_assoc()['c'];
    $a = $hdb->query("SELECT COUNT(*) c FROM {$hprefix}hostel_rooms WHERE status='Available'");
    if ($a) $available = (int)$a->fetch_assoc()['c'];
    $m = $hdb->query("SELECT COUNT(*) c FROM {$hprefix}hostel_rooms WHERE status='Under Maintenance'");
    if ($m) $maintenance = (int)$m->fetch_assoc()['c'];
    $r = $hdb->query("SELECT r.room_number, COALESCE(h.name,r.hostel_name) hostel_name, r.capacity, r.occupants, r.status FROM {$hprefix}hostel_rooms r LEFT JOIN {$hprefix}hostel h ON r.hostel_id=h.id ORDER BY r.room_number");
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
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchJLRH" type="text" placeholder="Search..." onkeyup="filterTable('srchJLRH','tblJLRH')"></div>
<div class="table-responsive">
            <table id="tblJLRH" class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Room #</th><th>Hostel</th><th>Capacity</th><th>Occupants</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $rm): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($rm['room_number']) ?></strong></td>
                        <td><?= htmlspecialchars($rm['hostel_name'] ?? '-') ?></td>
                        <td><?= (int)($rm['capacity'] ?? 0) ?></td>
                        <td><?= (int)($rm['occupants'] ?? 0) ?></td>
                        <td>
                            <?php $sc = $rm['status'] === 'Available' ? 'success' : ($rm['status'] === 'Occupied' ? 'warning' : ($rm['status'] === 'Under Maintenance' ? 'info' : 'secondary')); ?>
                            <span class="badge bg-<?= $sc ?>"><?= htmlspecialchars($rm['status']) ?></span>
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
</script>
</body>
</html>
