<?php
$pageTitle = 'Hostel Management';
require_once __DIR__ . '/../includes/config_enhanced.php';
$conn = getStaffConnection();
$conn2 = null;
try { $conn2 = getDatabaseConnection('students'); } catch (Exception $e) { $conn2 = null; }

$totalRooms = 0; $occupied = 0; $available = 0; $maintenance = 0;
$rooms = [];

if ($conn) {
    $t = $conn->query("SELECT COUNT(*) c FROM hostel_rooms");
    if ($t === false && $conn2) {
        $c = $conn2->query("SELECT COUNT(*) c FROM hostel_rooms");
        if ($c) $totalRooms = (int)$c->fetch_assoc()['c'];
        $o = $conn2->query("SELECT COUNT(*) c FROM hostel_rooms WHERE status='Occupied'");
        if ($o) $occupied = (int)$o->fetch_assoc()['c'];
        $a = $conn2->query("SELECT COUNT(*) c FROM hostel_rooms WHERE status='Available'");
        if ($a) $available = (int)$a->fetch_assoc()['c'];
        $m = $conn2->query("SELECT COUNT(*) c FROM hostel_rooms WHERE status='Under Maintenance'");
        if ($m) $maintenance = (int)$m->fetch_assoc()['c'];
        $r = $conn2->query("SELECT r.room_number, COALESCE(h.name,r.hostel_name) hostel_name, r.capacity, r.occupants, r.status FROM hostel_rooms r LEFT JOIN hostel h ON r.hostel_id=h.id ORDER BY r.room_number");
        if ($r) while ($row = $r->fetch_assoc()) $rooms[] = $row;
    } else {
        if ($t) $totalRooms = (int)$t->fetch_assoc()['c'];
        $o = $conn->query("SELECT COUNT(*) c FROM hostel_rooms WHERE status='Occupied'");
        if ($o) $occupied = (int)$o->fetch_assoc()['c'];
        $a = $conn->query("SELECT COUNT(*) c FROM hostel_rooms WHERE status='Available'");
        if ($a) $available = (int)$a->fetch_assoc()['c'];
        $m = $conn->query("SELECT COUNT(*) c FROM hostel_rooms WHERE status='Under Maintenance'");
        if ($m) $maintenance = (int)$m->fetch_assoc()['c'];
        $r = $conn->query("SELECT r.room_number, COALESCE(h.name,r.hostel_name) hostel_name, r.capacity, r.occupants, r.status FROM hostel_rooms r LEFT JOIN hostel h ON r.hostel_id=h.id ORDER BY r.room_number");
        if ($r) while ($row = $r->fetch_assoc()) $rooms[] = $row;
    }
} elseif ($conn2) {
    $t = $conn2->query("SELECT COUNT(*) c FROM hostel_rooms");
    if ($t) $totalRooms = (int)$t->fetch_assoc()['c'];
    $o = $conn2->query("SELECT COUNT(*) c FROM hostel_rooms WHERE status='Occupied'");
    if ($o) $occupied = (int)$o->fetch_assoc()['c'];
    $a = $conn2->query("SELECT COUNT(*) c FROM hostel_rooms WHERE status='Available'");
    if ($a) $available = (int)$a->fetch_assoc()['c'];
    $m = $conn2->query("SELECT COUNT(*) c FROM hostel_rooms WHERE status='Under Maintenance'");
    if ($m) $maintenance = (int)$m->fetch_assoc()['c'];
    $r = $conn2->query("SELECT r.room_number, COALESCE(h.name,r.hostel_name) hostel_name, r.capacity, r.occupants, r.status FROM hostel_rooms r LEFT JOIN hostel h ON r.hostel_id=h.id ORDER BY r.room_number");
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
<div class="main" style="margin-left:270px;padding:32px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-bed me-2"></i>Hostel Management</h4>
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
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
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
</body>
</html>
