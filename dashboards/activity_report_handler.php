<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['matron', 'warden']);
header('Content-Type: application/json');

$conn = $ctx['staff'];
if (!$conn) { echo json_encode(['total_activities' => 0, 'upcoming' => 0, 'completed' => 0, 'total_participants' => 0]); exit; }

$total_activities = $upcoming = $completed = $total_participants = 0;

$r = @$conn->query("SELECT COUNT(*) AS cnt FROM hostel_activities");
if ($r) { $total_activities = (int)$r->fetch_assoc()['cnt']; }

$r2 = @$conn->query("SELECT COUNT(*) AS cnt FROM activity_schedules WHERE schedule_date >= CURDATE()");
if ($r2) { $upcoming = (int)$r2->fetch_assoc()['cnt']; }

$r3 = @$conn->query("SELECT COUNT(*) AS cnt FROM activity_schedules WHERE schedule_date < CURDATE()");
if ($r3) { $completed = (int)$r3->fetch_assoc()['cnt']; }

$r4 = @$conn->query("SELECT COUNT(*) AS cnt FROM activity_participation");
if ($r4) { $total_participants = (int)$r4->fetch_assoc()['cnt']; }

echo json_encode(['total_activities' => $total_activities, 'upcoming' => $upcoming, 'completed' => $completed, 'total_participants' => $total_participants]);
