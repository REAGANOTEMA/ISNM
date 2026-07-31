<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['matron', 'warden']);
header('Content-Type: application/json');

$conn = $ctx['staff'];
if (!$conn) { echo json_encode(['total_rooms' => 0, 'occupied' => 0, 'vacant' => 0, 'occupancy_rate' => '0%']); exit; }

$total_rooms = 0;
$occupied = 0;

$r = @$conn->query("SELECT COUNT(*) AS cnt FROM hostel_rooms");
if ($r) { $total_rooms = (int)$r->fetch_assoc()['cnt']; }

$r2 = @$conn->query("SELECT COUNT(DISTINCT room_id) AS cnt FROM hostel_allocations WHERE status = 'Active' OR status = 'allocated'");
if ($r2) { $occupied = (int)$r2->fetch_assoc()['cnt']; }

$vacant = max(0, $total_rooms - $occupied);
$rate = $total_rooms > 0 ? round(($occupied / $total_rooms) * 100, 1) . '%' : '0%';

echo json_encode(['total_rooms' => $total_rooms, 'occupied' => $occupied, 'vacant' => $vacant, 'occupancy_rate' => $rate]);
