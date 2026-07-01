<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['driver']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int) ($user['id'] ?? 0);
$user_role = $user['role'] ?? '';
$user_name = $user['full_name'] ?? '';

// ── POST Handlers ──
$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {

            case 'add_vehicle':
                $vnum  = trim($_POST['vehicle_number'] ?? '');
                $vtype = trim($_POST['vehicle_type'] ?? '');
                $cap   = (int)($_POST['capacity'] ?? 0);
                $ftype = trim($_POST['fuel_type'] ?? 'Diesel');
                $iexp  = trim($_POST['insurance_expiry'] ?? '');
                if ($vnum && $vtype) {
                    $stmt = $conn->prepare("INSERT INTO transport_vehicles (vehicle_number, vehicle_type, capacity, fuel_type, insurance_expiry, status) VALUES (?, ?, ?, ?, ?, 'Available')");
                    $stmt->bind_param('ssiss', $vnum, $vtype, $cap, $ftype, $iexp);
                    $stmt->execute();
                    $flash = 'Vehicle added successfully.';
                } else {
                    $flash = 'Vehicle number and type are required.';
                    $flashType = 'warning';
                }
                break;

            case 'update_vehicle':
                $vid    = (int)($_POST['vehicle_id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                $vnum   = trim($_POST['vehicle_number'] ?? '');
                $vtype  = trim($_POST['vehicle_type'] ?? '');
                $cap    = (int)($_POST['capacity'] ?? 0);
                $ftype  = trim($_POST['fuel_type'] ?? '');
                $iexp   = trim($_POST['insurance_expiry'] ?? '');
                if ($vid) {
                    $stmt = $conn->prepare("UPDATE transport_vehicles SET vehicle_number=?, vehicle_type=?, capacity=?, fuel_type=?, insurance_expiry=?, status=? WHERE id=?");
                    $stmt->bind_param('ssisssi', $vnum, $vtype, $cap, $ftype, $iexp, $status, $vid);
                    $stmt->execute();
                    $flash = 'Vehicle updated successfully.';
                }
                break;

            case 'delete_vehicle':
                $vid = (int)($_POST['vehicle_id'] ?? 0);
                if ($vid) {
                    $stmt = $conn->prepare("DELETE FROM transport_vehicles WHERE id=?");
                    $stmt->bind_param('i', $vid);
                    $stmt->execute();
                    $flash = 'Vehicle deleted successfully.';
                }
                break;

            case 'add_route':
                $rname = trim($_POST['route_name'] ?? '');
                $start = trim($_POST['start_location'] ?? '');
                $end   = trim($_POST['end_location'] ?? '');
                $dist  = (float)($_POST['distance_km'] ?? 0);
                $dur   = (int)($_POST['estimated_duration_minutes'] ?? 30);
                $rtype = trim($_POST['route_type'] ?? 'both');
                $fare  = (float)($_POST['fare_amount'] ?? 0);
                $notes = trim($_POST['notes'] ?? '');
                if ($rname && $start && $end) {
                    $stmt = $conn->prepare("INSERT INTO transport_routes (route_name, start_location, end_location, distance_km, estimated_duration_minutes, route_type, fare_amount, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->bind_param('sssdiids', $rname, $start, $end, $dist, $dur, $rtype, $fare, $notes);
                    $stmt->execute();
                    $flash = 'Route added successfully.';
                } else {
                    $flash = 'Route name, start, and end locations are required.';
                    $flashType = 'warning';
                }
                break;

            case 'update_route':
                $rid   = (int)($_POST['route_id'] ?? 0);
                $rname = trim($_POST['route_name'] ?? '');
                $start = trim($_POST['start_location'] ?? '');
                $end   = trim($_POST['end_location'] ?? '');
                $dist  = (float)($_POST['distance_km'] ?? 0);
                $dur   = (int)($_POST['estimated_duration_minutes'] ?? 30);
                $rtype = trim($_POST['route_type'] ?? 'both');
                $fare  = (float)($_POST['fare_amount'] ?? 0);
                $notes = trim($_POST['notes'] ?? '');
                $status = trim($_POST['status'] ?? 'active');
                if ($rid) {
                    $stmt = $conn->prepare("UPDATE transport_routes SET route_name=?, start_location=?, end_location=?, distance_km=?, estimated_duration_minutes=?, route_type=?, fare_amount=?, notes=?, status=? WHERE id=?");
                    $stmt->bind_param('sssdiidssi', $rname, $start, $end, $dist, $dur, $rtype, $fare, $notes, $status, $rid);
                    $stmt->execute();
                    $flash = 'Route updated successfully.';
                }
                break;

            case 'delete_route':
                $rid = (int)($_POST['route_id'] ?? 0);
                if ($rid) {
                    $stmt = $conn->prepare("DELETE FROM transport_routes WHERE id=?");
                    $stmt->bind_param('i', $rid);
                    $stmt->execute();
                    $flash = 'Route deleted successfully.';
                }
                break;

            case 'add_trip':
                $vid   = (int)($_POST['vehicle_id'] ?? 0);
                $did   = (int)($_POST['driver_id'] ?? 0);
                $rid   = (int)($_POST['route_id'] ?? 0);
                $dep   = trim($_POST['departure_time'] ?? '');
                $arr   = trim($_POST['arrival_time'] ?? '');
                $pax   = (int)($_POST['passengers_count'] ?? 0);
                $fcost = (float)($_POST['fuel_cost'] ?? 0);
                $dist  = (float)($_POST['trip_distance'] ?? 0);
                $fare  = (float)($_POST['trip_fare'] ?? 0);
                $notes = trim($_POST['notes'] ?? '');
                if ($vid && $rid) {
                    $rname_q = $conn->query("SELECT route_name FROM transport_routes WHERE id=$rid");
                    $rname = $rname_q ? $rname_q->fetch_row()[0] : '';
                    $stmt = $conn->prepare("INSERT INTO transport_trips (vehicle_id, driver_id, route_id, route_name, departure_time, arrival_time, passengers_count, fuel_cost, trip_distance, trip_fare, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled')");
                    $stmt->bind_param('iiisssiddss', $vid, $did, $rid, $rname, $dep, $arr, $pax, $fcost, $dist, $fare, $notes);
                    $stmt->execute();
                    $flash = 'Trip added successfully.';
                } else {
                    $flash = 'Vehicle and route are required.';
                    $flashType = 'warning';
                }
                break;

            case 'update_trip':
                $tid   = (int)($_POST['trip_id'] ?? 0);
                $vid   = (int)($_POST['vehicle_id'] ?? 0);
                $did   = (int)($_POST['driver_id'] ?? 0);
                $rid   = (int)($_POST['route_id'] ?? 0);
                $dep   = trim($_POST['departure_time'] ?? '');
                $arr   = trim($_POST['arrival_time'] ?? '');
                $pax   = (int)($_POST['passengers_count'] ?? 0);
                $fcost = (float)($_POST['fuel_cost'] ?? 0);
                $dist  = (float)($_POST['trip_distance'] ?? 0);
                $fare  = (float)($_POST['trip_fare'] ?? 0);
                $status = trim($_POST['status'] ?? 'Scheduled');
                $notes = trim($_POST['notes'] ?? '');
                if ($tid) {
                    $rname_q = $conn->query("SELECT route_name FROM transport_routes WHERE id=$rid");
                    $rname = $rname_q ? $rname_q->fetch_row()[0] : '';
                    $stmt = $conn->prepare("UPDATE transport_trips SET vehicle_id=?, driver_id=?, route_id=?, route_name=?, departure_time=?, arrival_time=?, passengers_count=?, fuel_cost=?, trip_distance=?, trip_fare=?, status=?, notes=? WHERE id=?");
                    $stmt->bind_param('iiisssiddssi', $vid, $did, $rid, $rname, $dep, $arr, $pax, $fcost, $dist, $fare, $status, $notes, $tid);
                    $stmt->execute();
                    $flash = 'Trip updated successfully.';
                }
                break;

            case 'delete_trip':
                $tid = (int)($_POST['trip_id'] ?? 0);
                if ($tid) {
                    $stmt = $conn->prepare("DELETE FROM transport_trips WHERE id=?");
                    $stmt->bind_param('i', $tid);
                    $stmt->execute();
                    $flash = 'Trip deleted successfully.';
                }
                break;

            case 'submit_trip_for_approval':
                $tid = (int)($_POST['trip_id'] ?? 0);
                if ($tid) {
                    $stmt = $conn->prepare("UPDATE transport_trips SET dg_approval_status='pending', requested_by=? WHERE id=?");
                    $stmt->bind_param('ii', $user_id, $tid);
                    $stmt->execute();
                    $stmt->close();
                    $flash = 'Trip submitted for Director General approval.';
                }
                break;

            case 'approve_trip':
                $tid = (int)($_POST['trip_id'] ?? 0);
                if ($tid) {
                    $stmt = $conn->prepare("UPDATE transport_trips SET dg_approval_status='approved', dg_approved_by=?, dg_approved_at=NOW() WHERE id=?");
                    $stmt->bind_param('ii', $user_id, $tid);
                    $stmt->execute();
                    $stmt->close();
                    $flash = 'Trip approved successfully.';
                }
                break;

            case 'reject_trip':
                $tid = (int)($_POST['trip_id'] ?? 0);
                $reason = trim($_POST['rejection_reason'] ?? 'No reason');
                if ($tid) {
                    $stmt = $conn->prepare("UPDATE transport_trips SET dg_approval_status='rejected', rejection_reason=?, dg_approved_by=?, dg_approved_at=NOW() WHERE id=?");
                    $stmt->bind_param('sii', $reason, $user_id, $tid);
                    $stmt->execute();
                    $stmt->close();
                    $flash = 'Trip rejected.';
                }
                break;

            case 'add_student':
                $sid  = (int)($_POST['student_id'] ?? 0);
                $sname = trim($_POST['student_name'] ?? '');
                $sreg  = trim($_POST['registration_number'] ?? '');
                $rid   = (int)($_POST['route_id'] ?? 0);
                $vid   = (int)($_POST['vehicle_id'] ?? 0);
                $pick  = trim($_POST['pickup_point'] ?? '');
                $drop  = trim($_POST['dropoff_point'] ?? '');
                $year  = trim($_POST['academic_year'] ?? '2025/2026');
                if ($sname) {
                    $stmt = $conn->prepare("INSERT INTO transport_student_assignments (student_id, student_name, registration_number, route_id, vehicle_id, pickup_point, dropoff_point, academic_year, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->bind_param('issiiisss', $sid, $sname, $sreg, $rid, $vid, $pick, $drop, $year);
                    $stmt->execute();
                    $flash = 'Student assigned successfully.';
                } else {
                    $flash = 'Student name is required.';
                    $flashType = 'warning';
                }
                break;

            case 'update_student':
                $aid  = (int)($_POST['assignment_id'] ?? 0);
                $sid  = (int)($_POST['student_id'] ?? 0);
                $sname = trim($_POST['student_name'] ?? '');
                $sreg  = trim($_POST['registration_number'] ?? '');
                $rid   = (int)($_POST['route_id'] ?? 0);
                $vid   = (int)($_POST['vehicle_id'] ?? 0);
                $pick  = trim($_POST['pickup_point'] ?? '');
                $drop  = trim($_POST['dropoff_point'] ?? '');
                $year  = trim($_POST['academic_year'] ?? '2025/2026');
                $status = trim($_POST['status'] ?? 'active');
                if ($aid) {
                    $stmt = $conn->prepare("UPDATE transport_student_assignments SET student_id=?, student_name=?, registration_number=?, route_id=?, vehicle_id=?, pickup_point=?, dropoff_point=?, academic_year=?, status=? WHERE id=?");
                    $stmt->bind_param('issiiisssi', $sid, $sname, $sreg, $rid, $vid, $pick, $drop, $year, $status, $aid);
                    $stmt->execute();
                    $flash = 'Student assignment updated.';
                }
                break;

            case 'delete_student':
                $aid = (int)($_POST['assignment_id'] ?? 0);
                if ($aid) {
                    $stmt = $conn->prepare("DELETE FROM transport_student_assignments WHERE id=?");
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $flash = 'Student assignment removed.';
                }
                break;

            case 'add_fuel_log':
                $vid    = (int)($_POST['vehicle_id'] ?? 0);
                $did    = (int)($_POST['driver_id'] ?? 0);
                $fdate  = trim($_POST['fuel_date'] ?? '');
                $liters = (float)($_POST['liters'] ?? 0);
                $cost   = (float)($_POST['cost'] ?? 0);
                $odo    = (float)($_POST['odometer_reading'] ?? 0);
                $station = trim($_POST['station'] ?? '');
                if ($vid && $fdate && $liters > 0) {
                    $stmt = $conn->prepare("INSERT INTO transport_fuel_log (vehicle_id, driver_id, fuel_date, liters, cost, odometer_reading, station) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('iisddds', $vid, $did, $fdate, $liters, $cost, $odo, $station);
                    $stmt->execute();
                    $flash = 'Fuel log added successfully.';
                } else {
                    $flash = 'Vehicle, date, and liters are required.';
                    $flashType = 'warning';
                }
                break;

            case 'update_fuel_log':
                $fid    = (int)($_POST['fuel_id'] ?? 0);
                $vid    = (int)($_POST['vehicle_id'] ?? 0);
                $did    = (int)($_POST['driver_id'] ?? 0);
                $fdate  = trim($_POST['fuel_date'] ?? '');
                $liters = (float)($_POST['liters'] ?? 0);
                $cost   = (float)($_POST['cost'] ?? 0);
                $odo    = (float)($_POST['odometer_reading'] ?? 0);
                $station = trim($_POST['station'] ?? '');
                if ($fid) {
                    $stmt = $conn->prepare("UPDATE transport_fuel_log SET vehicle_id=?, driver_id=?, fuel_date=?, liters=?, cost=?, odometer_reading=?, station=? WHERE id=?");
                    $stmt->bind_param('iisddsii', $vid, $did, $fdate, $liters, $cost, $odo, $station, $fid);
                    $stmt->execute();
                    $flash = 'Fuel log updated.';
                }
                break;

            case 'delete_fuel_log':
                $fid = (int)($_POST['fuel_id'] ?? 0);
                if ($fid) {
                    $stmt = $conn->prepare("DELETE FROM transport_fuel_log WHERE id=?");
                    $stmt->bind_param('i', $fid);
                    $stmt->execute();
                    $flash = 'Fuel log deleted.';
                }
                break;
        }
    } catch (Exception $e) {
        $flash = 'Database error: ' . $e->getMessage();
        $flashType = 'danger';
    }
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirect . '?page=' . ($_POST['page'] ?? 'home') . '&flash=' . urlencode($flash) . '&flash_type=' . $flashType);
    exit;
}

if (isset($_GET['flash'])) {
    $flash = $_GET['flash'];
    $flashType = $_GET['flash_type'] ?? 'success';
}

// ── Page Routing ──
$page = $_GET['page'] ?? 'home';

// ── Data Queries ──
$total_trips_today = $students_transport = $total_vehicles = $total_routes = $total_students = $total_fuel_cost = 0;

if ($conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) FROM transport_trips WHERE DATE(departure_time) = CURDATE()");
        if ($r) { $row = $r->fetch_row(); $total_trips_today = (int)$row[0]; }
    } catch (Exception $e) {}
    try {
        $r = $conn->query("SELECT COALESCE(SUM(student_count),0) FROM transport_trips WHERE DATE(departure_time) = CURDATE()");
        if ($r) { $row = $r->fetch_row(); $students_transport = (int)$row[0]; }
    } catch (Exception $e) {}
    try {
        $r = $conn->query("SELECT COUNT(*) FROM transport_vehicles");
        if ($r) { $row = $r->fetch_row(); $total_vehicles = (int)$row[0]; }
    } catch (Exception $e) {}
    try {
        $r = $conn->query("SELECT COUNT(*) FROM transport_routes WHERE status='active'");
        if ($r) { $row = $r->fetch_row(); $total_routes = (int)$row[0]; }
    } catch (Exception $e) {}
    try {
        $r = $conn->query("SELECT COUNT(*) FROM transport_student_assignments WHERE status='active'");
        if ($r) { $row = $r->fetch_row(); $total_students = (int)$row[0]; }
    } catch (Exception $e) {}
    try {
        $r = $conn->query("SELECT COALESCE(SUM(cost),0) FROM transport_fuel_log WHERE MONTH(fuel_date)=MONTH(CURDATE())");
        if ($r) { $row = $r->fetch_row(); $total_fuel_cost = (float)$row[0]; }
    } catch (Exception $e) {}
}

// Fetch all data for sections
$vehicles = [];
if ($conn) {
    try { $r = $conn->query("SELECT * FROM transport_vehicles ORDER BY vehicle_number"); if ($r) $vehicles = $r->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) {}
}

$routes = [];
if ($conn) {
    try { $r = $conn->query("SELECT * FROM transport_routes ORDER BY route_name"); if ($r) $routes = $r->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) {}
}

$trips = [];
if ($conn) {
    try { $r = $conn->query("SELECT t.*, tv.vehicle_number, COALESCE(s.full_name,'Unassigned') AS driver_name, COALESCE(req.full_name,'') AS requested_by_name FROM transport_trips t LEFT JOIN transport_vehicles tv ON t.vehicle_id=tv.id LEFT JOIN staff s ON t.driver_id=s.id LEFT JOIN staff req ON t.requested_by=req.id ORDER BY t.departure_time DESC"); if ($r) $trips = $r->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) {}
}

$student_assignments = [];
if ($conn) {
    try { $r = $conn->query("SELECT sa.*, tr.route_name, tv.vehicle_number FROM transport_student_assignments sa LEFT JOIN transport_routes tr ON sa.route_id=tr.id LEFT JOIN transport_vehicles tv ON sa.vehicle_id=tv.id ORDER BY sa.student_name"); if ($r) $student_assignments = $r->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) {}
}

$fuel_logs = [];
if ($conn) {
    try { $r = $conn->query("SELECT f.*, tv.vehicle_number, COALESCE(s.full_name,'Unassigned') AS driver_name FROM transport_fuel_log f LEFT JOIN transport_vehicles tv ON f.vehicle_id=tv.id LEFT JOIN staff s ON f.driver_id=s.id ORDER BY f.fuel_date DESC"); if ($r) $fuel_logs = $r->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) {}
}

$staff_list = [];
if ($conn) {
    try { $r = $conn->query("SELECT id, full_name FROM staff ORDER BY full_name"); if ($r) $staff_list = $r->fetch_all(MYSQLI_ASSOC); } catch (Exception $e) {}
}

// Edit entities
$edit_entity = null;
$edit_type = '';
foreach (['edit_vehicle','edit_route','edit_trip','edit_student','edit_fuel'] as $param) {
    if (isset($_GET[$param])) {
        $eid = (int)$_GET[$param];
        $edit_type = $param;
        switch ($param) {
            case 'edit_vehicle':
                $stmt = $conn->prepare("SELECT * FROM transport_vehicles WHERE id=?"); $stmt->bind_param('i', $eid); $stmt->execute(); $edit_entity = $stmt->get_result()->fetch_assoc();
                break;
            case 'edit_route':
                $stmt = $conn->prepare("SELECT * FROM transport_routes WHERE id=?"); $stmt->bind_param('i', $eid); $stmt->execute(); $edit_entity = $stmt->get_result()->fetch_assoc();
                break;
            case 'edit_trip':
                $stmt = $conn->prepare("SELECT * FROM transport_trips WHERE id=?"); $stmt->bind_param('i', $eid); $stmt->execute(); $edit_entity = $stmt->get_result()->fetch_assoc();
                break;
            case 'edit_student':
                $stmt = $conn->prepare("SELECT * FROM transport_student_assignments WHERE id=?"); $stmt->bind_param('i', $eid); $stmt->execute(); $edit_entity = $stmt->get_result()->fetch_assoc();
                break;
            case 'edit_fuel':
                $stmt = $conn->prepare("SELECT * FROM transport_fuel_log WHERE id=?"); $stmt->bind_param('i', $eid); $stmt->execute(); $edit_entity = $stmt->get_result()->fetch_assoc();
                break;
        }
        break;
    }
}

// Fuel stats
$total_liters = $total_cost = 0;
if ($conn) {
    try {
        $r = $conn->query("SELECT COALESCE(SUM(liters),0), COALESCE(SUM(cost),0) FROM transport_fuel_log");
        if ($r) { $row = $r->fetch_row(); $total_liters = (float)$row[0]; $total_cost = (float)$row[0]; }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.drv-content{margin-left:270px;padding:28px 32px;min-height:100vh;background:#f0f2f5}
@media(max-width:991px){.drv-content{margin-left:0!important;padding:16px!important}}
@media(max-width:767px){.drv-content{padding:12px!important}}

.drv-page-title{font-size:1.65rem;font-weight:700;color:#1a1d29;margin-bottom:4px}
.drv-page-sub{color:#6b7280;font-size:.92rem;margin-bottom:24px}

.drv-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:28px}
.drv-stat-card{background:#fff;border-radius:12px;padding:22px 20px;box-shadow:0 1px 4px rgba(0,0,0,.06);display:flex;align-items:center;gap:16px;transition:transform .15s,box-shadow .15s}
.drv-stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1)}
.drv-stat-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.35rem;color:#fff;flex-shrink:0}
.drv-stat-icon.blue{background:linear-gradient(135deg,#3b82f6,#2563eb)}
.drv-stat-icon.green{background:linear-gradient(135deg,#10b981,#059669)}
.drv-stat-icon.amber{background:linear-gradient(135deg,#f59e0b,#d97706)}
.drv-stat-icon.red{background:linear-gradient(135deg,#ef4444,#dc2626)}
.drv-stat-icon.purple{background:linear-gradient(135deg,#8b5cf6,#7c3aed)}
.drv-stat-icon.teal{background:linear-gradient(135deg,#14b8a6,#0d9488)}
.drv-stat-info h3{font-size:1.55rem;font-weight:700;color:#1a1d29;margin:0;line-height:1.2}
.drv-stat-info p{font-size:.82rem;color:#6b7280;margin:2px 0 0}

.drv-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:24px;overflow:hidden}
.drv-card-header{padding:18px 24px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between}
.drv-card-header h5{margin:0;font-size:1.05rem;font-weight:600;color:#1a1d29}
.drv-card-body{padding:20px 24px}

.drv-btn{display:inline-flex;align-items:center;gap:8px;padding:9px 20px;border-radius:8px;font-size:.88rem;font-weight:500;border:none;cursor:pointer;transition:all .15s;text-decoration:none}
.drv-btn-primary{background:#3b82f6;color:#fff}
.drv-btn-primary:hover{background:#2563eb;color:#fff}
.drv-btn-success{background:#10b981;color:#fff}
.drv-btn-success:hover{background:#059669;color:#fff}
.drv-btn-danger{background:#ef4444;color:#fff}
.drv-btn-danger:hover{background:#dc2626;color:#fff}
.drv-btn-outline{background:transparent;border:1px solid #d1d5db;color:#374151}
.drv-btn-outline:hover{background:#f3f4f6}
.drv-btn-sm{padding:5px 12px;font-size:.8rem;border-radius:6px}

.drv-table{width:100%;border-collapse:collapse}
.drv-table th{background:#f8f9fa;color:#374151;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.4px;padding:12px 14px;border-bottom:2px solid #e5e7eb;text-align:left}
.drv-table td{padding:12px 14px;border-bottom:1px solid #f0f0f0;font-size:.9rem;color:#1f2937;vertical-align:middle}
.drv-table tbody tr:hover{background:#f8f9fb}
.drv-table .actions{display:flex;gap:6px;flex-wrap:nowrap}

.drv-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.drv-badge-success{background:#d1fae5;color:#065f46}
.drv-badge-warning{background:#fef3c7;color:#92400e}
.drv-badge-danger{background:#fee2e2;color:#991b1b}
.drv-badge-info{background:#dbeafe;color:#1e40af}
.drv-badge-secondary{background:#e5e7eb;color:#374151}

.drv-form-label{font-size:.85rem;font-weight:600;color:#374151;margin-bottom:6px}
.drv-form-input{width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem;color:#1f2937;background:#fff;transition:border-color .15s}
.drv-form-input:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.drv-form-select{width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem;color:#1f2937;background:#fff}

.drv-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.drv-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px}
@media(max-width:767px){.drv-grid-2,.drv-grid-3{grid-template-columns:1fr}}

.drv-route-card{background:#f8f9fb;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;margin-bottom:12px;transition:border-color .15s}
.drv-route-card:hover{border-color:#3b82f6}
.drv-route-card h6{font-size:.95rem;font-weight:600;color:#1a1d29;margin:0 0 6px}
.drv-route-card p{font-size:.82rem;color:#6b7280;margin:0}

.drv-empty{text-align:center;padding:40px 20px;color:#9ca3af}
.drv-empty i{font-size:2.5rem;margin-bottom:12px;display:block}
.drv-empty p{font-size:.95rem}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="drv-content">

<?php if ($flash): ?>
<div class="alert alert-<?= $flashType ?> alert-dismissible fade show" role="alert" style="border-radius:10px;margin-bottom:20px">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php switch ($page):

// ════════════════════════════════════════════
// HOME / OVERVIEW
// ════════════════════════════════════════════
case 'home': ?>
<h1 class="drv-page-title">Transport Dashboard</h1>
<p class="drv-page-sub">Overview of all transport operations</p>

<div class="drv-stat-grid">
    <div class="drv-stat-card"><div class="drv-stat-icon blue"><i class="fas fa-route"></i></div><div class="drv-stat-info"><h3><?= $total_routes ?></h3><p>Active Routes</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon green"><i class="fas fa-bus"></i></div><div class="drv-stat-info"><h3><?= $total_vehicles ?></h3><p>Total Vehicles</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon amber"><i class="fas fa-road"></i></div><div class="drv-stat-info"><h3><?= $total_trips_today ?></h3><p>Trips Today</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon purple"><i class="fas fa-users"></i></div><div class="drv-stat-info"><h3><?= $total_students ?></h3><p>Assigned Students</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon red"><i class="fas fa-gas-pump"></i></div><div class="drv-stat-info"><h3>UGX <?= number_format($total_fuel_cost) ?></h3><p>Fuel This Month</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon teal"><i class="fas fa-user-shield"></i></div><div class="drv-stat-info"><h3><?= $user_name ?></h3><p>Logged in as Driver</p></div></div>
</div>

<div class="drv-grid-2">
    <div class="drv-card">
        <div class="drv-card-header"><h5><i class="fas fa-bus" style="color:#3b82f6;margin-right:8px"></i>Vehicle Fleet</h5></div>
        <div class="drv-card-body">
            <?php if (empty($vehicles)): ?>
            <div class="drv-empty"><i class="fas fa-bus"></i><p>No vehicles registered yet</p></div>
            <?php else: ?>
            <?php foreach (array_slice($vehicles, 0, 5) as $v): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0">
                <div><strong style="color:#1a1d29"><?= htmlspecialchars($v['vehicle_number']) ?></strong><br><small style="color:#6b7280"><?= htmlspecialchars($v['vehicle_type']) ?> | <?= $v['capacity'] ?> seats</small></div>
                <span class="drv-badge <?= ($v['status'] ?? '')==='Available'?'drv-badge-success':(($v['status'] ?? '')==='On Trip'?'drv-badge-info':'drv-badge-warning') ?>"><?= htmlspecialchars($v['status']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="drv-card">
        <div class="drv-card-header"><h5><i class="fas fa-route" style="color:#10b981;margin-right:8px"></i>Active Routes</h5></div>
        <div class="drv-card-body">
            <?php if (empty($routes)): ?>
            <div class="drv-empty"><i class="fas fa-route"></i><p>No routes defined yet</p></div>
            <?php else: ?>
            <?php foreach (array_slice($routes, 0, 5) as $rt): ?>
            <div class="drv-route-card">
                <h6><?= htmlspecialchars($rt['route_name']) ?></h6>
                <p><?= htmlspecialchars($rt['start_location']) ?> &rarr; <?= htmlspecialchars($rt['end_location']) ?> | <?= $rt['distance_km'] ?> km | <?= $rt['estimated_duration_minutes'] ?> min</p>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="drv-card">
    <div class="drv-card-header"><h5><i class="fas fa-calendar-check" style="color:#f59e0b;margin-right:8px"></i>Recent Trips</h5></div>
    <div class="drv-card-body">
        <?php if (empty($trips)): ?>
        <div class="drv-empty"><i class="fas fa-road"></i><p>No trips recorded yet</p></div>
        <?php else: ?>
        <table class="drv-table">
            <thead><tr><th>Vehicle</th><th>Route</th><th>Driver</th><th>Departure</th><th>Passengers</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($trips, 0, 6) as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['vehicle_number'] ?? '-') ?></td>
                <td><?= htmlspecialchars($t['route_name']) ?></td>
                <td><?= htmlspecialchars($t['driver_name']) ?></td>
                <td><?= $t['departure_time'] ? date('M d, g:i A', strtotime($t['departure_time'])) : '-' ?></td>
                <td><?= (int)($t['passengers_count'] ?? 0) ?></td>
                <td><span class="drv-badge <?= ($t['status'] ?? '')==='Completed'?'drv-badge-success':(($t['status'] ?? '')==='In Transit'?'drv-badge-info':'drv-badge-warning') ?>"><?= htmlspecialchars($t['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php break;

// ════════════════════════════════════════════
// VEHICLES CRUD
// ════════════════════════════════════════════
case 'transport-vehicles': ?>
<h1 class="drv-page-title">Manage Vehicles</h1>
<p class="drv-page-sub">Add, edit, and manage the transport fleet</p>

<?php if ($edit_entity && $edit_type === 'edit_vehicle'): ?>
<div class="drv-card" style="border-left:4px solid #f59e0b">
    <div class="drv-card-header" style="background:#fffbeb"><h5><i class="fas fa-edit" style="color:#d97706;margin-right:8px"></i>Edit Vehicle #<?= (int)$edit_entity['id'] ?></h5></div>
    <div class="drv-card-body">
        <form method="POST">
            <input type="hidden" name="action" value="update_vehicle">
            <input type="hidden" name="vehicle_id" value="<?= (int)$edit_entity['id'] ?>">
            <input type="hidden" name="page" value="transport-vehicles">
            <div class="drv-grid-3">
                <div><label class="drv-form-label">Vehicle Number *</label><input type="text" name="vehicle_number" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['vehicle_number']) ?>" required></div>
                <div><label class="drv-form-label">Vehicle Type *</label><select name="vehicle_type" class="drv-form-select" required><?php foreach(['Bus','Minibus','Van','SUV','Pickup'] as $vt):?><option value="<?= $vt ?>" <?= ($edit_entity['vehicle_type'] ?? '')===$vt?'selected':'' ?>><?= $vt ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Capacity *</label><input type="number" name="capacity" class="drv-form-input" value="<?= (int)$edit_entity['capacity'] ?>" min="1" required></div>
                <div><label class="drv-form-label">Fuel Type</label><select name="fuel_type" class="drv-form-select"><?php foreach(['Diesel','Petrol','Electric'] as $ft):?><option value="<?= $ft ?>" <?= ($edit_entity['fuel_type'] ?? '')===$ft?'selected':'' ?>><?= $ft ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Insurance Expiry</label><input type="date" name="insurance_expiry" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['insurance_expiry'] ?? '') ?>"></div>
                <div><label class="drv-form-label">Status</label><select name="status" class="drv-form-select"><?php foreach(['Available','On Trip','Maintenance','Retired'] as $s):?><option value="<?= $s ?>" <?= ($edit_entity['status'] ?? '')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach;?></select></div>
            </div>
            <div style="margin-top:18px;display:flex;gap:10px">
                <button type="submit" class="drv-btn drv-btn-success"><i class="fas fa-save"></i> Save Changes</button>
                <a href="?page=transport-vehicles" class="drv-btn drv-btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="drv-card">
    <div class="drv-card-header">
        <h5>Fleet List (<?= count($vehicles) ?> vehicles)</h5>
        <button class="drv-btn drv-btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal"><i class="fas fa-plus"></i> Add Vehicle</button>
    </div>
    <div class="drv-card-body" style="padding:0">
        <table class="drv-table">
            <thead><tr><th>#</th><th>Vehicle No.</th><th>Type</th><th>Capacity</th><th>Fuel</th><th>Insurance</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($vehicles)): ?>
            <tr><td colspan="8" class="drv-empty"><i class="fas fa-bus"></i><p>No vehicles registered</p></td></tr>
            <?php else: ?>
            <?php foreach ($vehicles as $i => $v): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($v['vehicle_number']) ?></strong></td>
                <td><?= htmlspecialchars($v['vehicle_type']) ?></td>
                <td><?= (int)$v['capacity'] ?> seats</td>
                <td><?= htmlspecialchars($v['fuel_type'] ?? '-') ?></td>
                <td><?= $v['insurance_expiry'] ? date('M d, Y', strtotime($v['insurance_expiry'])) : '<span style="color:#9ca3af">-</span>' ?></td>
                <td><span class="drv-badge <?= ($v['status'] ?? '')==='Available'?'drv-badge-success':(($v['status'] ?? '')==='On Trip'?'drv-badge-info':(($v['status'] ?? '')==='Maintenance'?'drv-badge-warning':'drv-badge-secondary')) ?>"><?= htmlspecialchars($v['status']) ?></span></td>
                <td class="actions">
                    <a href="?page=transport-vehicles&edit_vehicle=<?= (int)$v['id'] ?>" class="drv-btn drv-btn-outline drv-btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this vehicle permanently?')">
                        <input type="hidden" name="action" value="delete_vehicle">
                        <input type="hidden" name="vehicle_id" value="<?= (int)$v['id'] ?>">
                        <input type="hidden" name="page" value="transport-vehicles">
                        <button type="submit" class="drv-btn drv-btn-danger drv-btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;border:none">
            <form method="POST">
                <div style="padding:18px 24px;background:#3b82f6;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
                    <h5 style="margin:0"><i class="fas fa-plus" style="margin-right:8px"></i>Add New Vehicle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div style="padding:24px">
                    <input type="hidden" name="action" value="add_vehicle">
                    <input type="hidden" name="page" value="transport-vehicles">
                    <div class="mb-3"><label class="drv-form-label">Vehicle Number *</label><input type="text" name="vehicle_number" class="drv-form-input" required placeholder="e.g. UGA-123A"></div>
                    <div class="mb-3"><label class="drv-form-label">Vehicle Type *</label><select name="vehicle_type" class="drv-form-select" required><option value="">Select type...</option><option>Bus</option><option>Minibus</option><option>Van</option><option>SUV</option><option>Pickup</option></select></div>
                    <div class="drv-grid-2">
                        <div class="mb-3"><label class="drv-form-label">Capacity *</label><input type="number" name="capacity" class="drv-form-input" min="1" required placeholder="Passenger seats"></div>
                        <div class="mb-3"><label class="drv-form-label">Fuel Type</label><select name="fuel_type" class="drv-form-select"><option>Diesel</option><option>Petrol</option><option>Electric</option></select></div>
                    </div>
                    <div class="mb-3"><label class="drv-form-label">Insurance Expiry</label><input type="date" name="insurance_expiry" class="drv-form-input"></div>
                </div>
                <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="drv-btn drv-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="drv-btn drv-btn-primary"><i class="fas fa-save"></i> Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php break;

// ════════════════════════════════════════════
// ROUTES CRUD
// ════════════════════════════════════════════
case 'transport-routes': ?>
<h1 class="drv-page-title">Manage Routes</h1>
<p class="drv-page-sub">Define and manage transport routes between locations</p>

<?php if ($edit_entity && $edit_type === 'edit_route'): ?>
<div class="drv-card" style="border-left:4px solid #f59e0b">
    <div class="drv-card-header" style="background:#fffbeb"><h5><i class="fas fa-edit" style="color:#d97706;margin-right:8px"></i>Edit Route #<?= (int)$edit_entity['id'] ?></h5></div>
    <div class="drv-card-body">
        <form method="POST">
            <input type="hidden" name="action" value="update_route">
            <input type="hidden" name="route_id" value="<?= (int)$edit_entity['id'] ?>">
            <input type="hidden" name="page" value="transport-routes">
            <div class="drv-grid-2">
                <div><label class="drv-form-label">Route Name *</label><input type="text" name="route_name" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['route_name']) ?>" required></div>
                <div><label class="drv-form-label">Route Type</label><select name="route_type" class="drv-form-select"><?php foreach(['both','morning','evening'] as $rt):?><option value="<?= $rt ?>" <?= ($edit_entity['route_type'] ?? '')===$rt?'selected':'' ?>><?= ucfirst($rt) ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Start Location *</label><input type="text" name="start_location" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['start_location']) ?>" required></div>
                <div><label class="drv-form-label">End Location *</label><input type="text" name="end_location" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['end_location']) ?>" required></div>
                <div><label class="drv-form-label">Distance (km)</label><input type="number" name="distance_km" class="drv-form-input" value="<?= (float)$edit_entity['distance_km'] ?>" step="0.1" min="0"></div>
                <div><label class="drv-form-label">Duration (min)</label><input type="number" name="estimated_duration_minutes" class="drv-form-input" value="<?= (int)$edit_entity['estimated_duration_minutes'] ?>" min="1"></div>
                <div><label class="drv-form-label">Fare (UGX)</label><input type="number" name="fare_amount" class="drv-form-input" value="<?= (float)$edit_entity['fare_amount'] ?>" step="100" min="0"></div>
                <div><label class="drv-form-label">Status</label><select name="status" class="drv-form-select"><?php foreach(['active','inactive'] as $s):?><option value="<?= $s ?>" <?= ($edit_entity['status'] ?? '')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach;?></select></div>
            </div>
            <div><label class="drv-form-label" style="margin-top:12px">Notes</label><textarea name="notes" class="drv-form-input" rows="2"><?= htmlspecialchars($edit_entity['notes'] ?? '') ?></textarea></div>
            <div style="margin-top:18px;display:flex;gap:10px">
                <button type="submit" class="drv-btn drv-btn-success"><i class="fas fa-save"></i> Save Changes</button>
                <a href="?page=transport-routes" class="drv-btn drv-btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="drv-card">
    <div class="drv-card-header">
        <h5>Routes List (<?= count($routes) ?> routes)</h5>
        <button class="drv-btn drv-btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal"><i class="fas fa-plus"></i> Add Route</button>
    </div>
    <div class="drv-card-body" style="padding:0">
        <table class="drv-table">
            <thead><tr><th>#</th><th>Route Name</th><th>From</th><th>To</th><th>Distance</th><th>Duration</th><th>Fare</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($routes)): ?>
            <tr><td colspan="10" class="drv-empty"><i class="fas fa-route"></i><p>No routes defined</p></td></tr>
            <?php else: ?>
            <?php foreach ($routes as $i => $rt): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($rt['route_name']) ?></strong></td>
                <td><?= htmlspecialchars($rt['start_location']) ?></td>
                <td><?= htmlspecialchars($rt['end_location']) ?></td>
                <td><?= number_format((float)$rt['distance_km'], 1) ?> km</td>
                <td><?= (int)$rt['estimated_duration_minutes'] ?> min</td>
                <td>UGX <?= number_format((float)$rt['fare_amount']) ?></td>
                <td><span class="drv-badge drv-badge-info"><?= ucfirst(htmlspecialchars($rt['route_type'])) ?></span></td>
                <td><span class="drv-badge <?= $rt['status']==='active'?'drv-badge-success':'drv-badge-secondary' ?>"><?= ucfirst(htmlspecialchars($rt['status'])) ?></span></td>
                <td class="actions">
                    <a href="?page=transport-routes&edit_route=<?= (int)$rt['id'] ?>" class="drv-btn drv-btn-outline drv-btn-sm"><i class="fas fa-edit"></i></a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this route?')">
                        <input type="hidden" name="action" value="delete_route">
                        <input type="hidden" name="route_id" value="<?= (int)$rt['id'] ?>">
                        <input type="hidden" name="page" value="transport-routes">
                        <button type="submit" class="drv-btn drv-btn-danger drv-btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px;border:none">
            <form method="POST">
                <div style="padding:18px 24px;background:#10b981;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
                    <h5 style="margin:0"><i class="fas fa-plus" style="margin-right:8px"></i>Add New Route</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div style="padding:24px">
                    <input type="hidden" name="action" value="add_route">
                    <input type="hidden" name="page" value="transport-routes">
                    <div class="mb-3"><label class="drv-form-label">Route Name *</label><input type="text" name="route_name" class="drv-form-input" required placeholder="e.g. Main Campus - Hospital"></div>
                    <div class="drv-grid-2">
                        <div class="mb-3"><label class="drv-form-label">Start Location *</label><input type="text" name="start_location" class="drv-form-input" required placeholder="Starting point"></div>
                        <div class="mb-3"><label class="drv-form-label">End Location *</label><input type="text" name="end_location" class="drv-form-input" required placeholder="Destination"></div>
                    </div>
                    <div class="drv-grid-3">
                        <div class="mb-3"><label class="drv-form-label">Distance (km)</label><input type="number" name="distance_km" class="drv-form-input" step="0.1" min="0" placeholder="0.0"></div>
                        <div class="mb-3"><label class="drv-form-label">Duration (min)</label><input type="number" name="estimated_duration_minutes" class="drv-form-input" min="1" value="30"></div>
                        <div class="mb-3"><label class="drv-form-label">Fare (UGX)</label><input type="number" name="fare_amount" class="drv-form-input" step="100" min="0" placeholder="0"></div>
                    </div>
                    <div class="drv-grid-2">
                        <div class="mb-3"><label class="drv-form-label">Route Type</label><select name="route_type" class="drv-form-select"><option value="both">Both (Morning & Evening)</option><option value="morning">Morning Only</option><option value="evening">Evening Only</option></select></div>
                        <div class="mb-3"><label class="drv-form-label">Notes</label><input type="text" name="notes" class="drv-form-input" placeholder="Optional notes"></div>
                    </div>
                </div>
                <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="drv-btn drv-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="drv-btn drv-btn-success"><i class="fas fa-save"></i> Save Route</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php break;

// ════════════════════════════════════════════
// TRIPS CRUD
// ════════════════════════════════════════════
case 'transport-trips': ?>
<h1 class="drv-page-title">Manage Trips</h1>
<p class="drv-page-sub">Schedule and track all transport trips</p>

<?php if ($edit_entity && $edit_type === 'edit_trip'): ?>
<div class="drv-card" style="border-left:4px solid #f59e0b">
    <div class="drv-card-header" style="background:#fffbeb"><h5><i class="fas fa-edit" style="color:#d97706;margin-right:8px"></i>Edit Trip #<?= (int)$edit_entity['id'] ?></h5></div>
    <div class="drv-card-body">
        <form method="POST">
            <input type="hidden" name="action" value="update_trip">
            <input type="hidden" name="trip_id" value="<?= (int)$edit_entity['id'] ?>">
            <input type="hidden" name="page" value="transport-trips">
            <div class="drv-grid-3">
                <div><label class="drv-form-label">Vehicle *</label><select name="vehicle_id" class="drv-form-select" required><?php foreach($vehicles as $v):?><option value="<?= (int)$v['id'] ?>" <?= (int)($edit_entity['vehicle_id'])===(int)$v['id']?'selected':'' ?>><?= htmlspecialchars($v['vehicle_number'].' - '.$v['vehicle_type']) ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Driver</label><select name="driver_id" class="drv-form-select"><option value="0">Unassigned</option><?php foreach($staff_list as $s):?><option value="<?= (int)$s['id'] ?>" <?= (int)($edit_entity['driver_id'])===(int)$s['id']?'selected':'' ?>><?= htmlspecialchars($s['full_name']) ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Route *</label><select name="route_id" class="drv-form-select" required><?php foreach($routes as $r):?><option value="<?= (int)$r['id'] ?>" <?= (int)($edit_entity['route_id'])===(int)$r['id']?'selected':'' ?>><?= htmlspecialchars($r['route_name']) ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Departure</label><input type="datetime-local" name="departure_time" class="drv-form-input" value="<?= $edit_entity['departure_time'] ? date('Y-m-d\TH:i', strtotime($edit_entity['departure_time'])) : '' ?>"></div>
                <div><label class="drv-form-label">Arrival</label><input type="datetime-local" name="arrival_time" class="drv-form-input" value="<?= $edit_entity['arrival_time'] ? date('Y-m-d\TH:i', strtotime($edit_entity['arrival_time'])) : '' ?>"></div>
                <div><label class="drv-form-label">Status</label><select name="status" class="drv-form-select"><?php foreach(['Scheduled','In Transit','Completed','Cancelled'] as $s):?><option value="<?= $s ?>" <?= ($edit_entity['status'] ?? '')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Passengers</label><input type="number" name="passengers_count" class="drv-form-input" value="<?= (int)($edit_entity['passengers_count'] ?? 0) ?>" min="0"></div>
                <div><label class="drv-form-label">Fuel Cost (UGX)</label><input type="number" name="fuel_cost" class="drv-form-input" value="<?= (float)($edit_entity['fuel_cost'] ?? 0) ?>" step="100" min="0"></div>
                <div><label class="drv-form-label">Distance (km)</label><input type="number" name="trip_distance" class="drv-form-input" value="<?= (float)($edit_entity['trip_distance'] ?? 0) ?>" step="0.1" min="0"></div>
            </div>
            <div class="mb-3" style="margin-top:12px"><label class="drv-form-label">Notes</label><textarea name="notes" class="drv-form-input" rows="2"><?= htmlspecialchars($edit_entity['notes'] ?? '') ?></textarea></div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="drv-btn drv-btn-success"><i class="fas fa-save"></i> Save Changes</button>
                <a href="?page=transport-trips" class="drv-btn drv-btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="drv-card">
    <div class="drv-card-header">
        <h5>Trips List (<?= count($trips) ?> trips)</h5>
        <button class="drv-btn drv-btn-primary" data-bs-toggle="modal" data-bs-target="#addTripModal"><i class="fas fa-plus"></i> Schedule Trip</button>
    </div>
    <div class="drv-card-body" style="padding:0">
        <table class="drv-table">
            <thead><tr><th>#</th><th>Vehicle</th><th>Route</th><th>Driver</th><th>Departure</th><th>Passengers</th><th>Cost</th><th>Trip Status</th><th>DG Approval</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($trips)): ?>
            <tr><td colspan="10" class="drv-empty"><i class="fas fa-road"></i><p>No trips scheduled</p></td></tr>
            <?php else: ?>
            <?php foreach ($trips as $i => $t):
                $dgBadge = ($t['dg_approval_status'] ?? '') === 'approved' ? 'drv-badge-success' : (($t['dg_approval_status'] ?? '') === 'rejected' ? 'drv-badge-danger' : 'drv-badge-warning');
                $dgLabel = ($t['dg_approval_status'] ?? '') === 'approved' ? 'Approved' : (($t['dg_approval_status'] ?? '') === 'rejected' ? 'Rejected' : 'Pending');
            ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($t['vehicle_number'] ?? '-') ?></td>
                <td><strong><?= htmlspecialchars($t['route_name']) ?></strong></td>
                <td><?= htmlspecialchars($t['driver_name']) ?></td>
                <td><?= $t['departure_time'] ? date('M d, g:i A', strtotime($t['departure_time'])) : '-' ?></td>
                <td><?= (int)($t['passengers_count'] ?? 0) ?></td>
                <td>UGX <?= number_format((float)($t['fuel_cost'] ?? 0)) ?></td>
                <td><span class="drv-badge <?= ($t['status'] ?? '')==='completed'?'drv-badge-success':(($t['status'] ?? '')==='in_progress'?'drv-badge-info':(($t['status'] ?? '')==='cancelled'?'drv-badge-danger':'drv-badge-warning')) ?>"><?= ucfirst(htmlspecialchars($t['status'])) ?></span></td>
                <td>
                    <span class="drv-badge <?= $dgBadge ?>"><?= $dgLabel ?></span>
                    <?php if (($t['dg_approval_status'] ?? '') === 'pending'): ?>
                    <form method="POST" style="display:inline;margin-top:4px">
                        <input type="hidden" name="action" value="submit_trip_for_approval">
                        <input type="hidden" name="trip_id" value="<?= (int)$t['id'] ?>">
                        <input type="hidden" name="page" value="transport-trips">
                        <button type="submit" class="drv-btn drv-btn-primary drv-btn-sm" title="Submit for DG Approval"><i class="fas fa-paper-plane"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a href="?page=transport-trips&edit_trip=<?= (int)$t['id'] ?>" class="drv-btn drv-btn-outline drv-btn-sm"><i class="fas fa-edit"></i></a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this trip?')">
                        <input type="hidden" name="action" value="delete_trip">
                        <input type="hidden" name="trip_id" value="<?= (int)$t['id'] ?>">
                        <input type="hidden" name="page" value="transport-trips">
                        <button type="submit" class="drv-btn drv-btn-danger drv-btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addTripModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px;border:none">
            <form method="POST">
                <div style="padding:18px 24px;background:#3b82f6;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
                    <h5 style="margin:0"><i class="fas fa-plus" style="margin-right:8px"></i>Schedule New Trip</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div style="padding:24px">
                    <input type="hidden" name="action" value="add_trip">
                    <input type="hidden" name="page" value="transport-trips">
                    <div class="drv-grid-3">
                        <div class="mb-3"><label class="drv-form-label">Vehicle *</label><select name="vehicle_id" class="drv-form-select" required><option value="">Select...</option><?php foreach($vehicles as $v):?><option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['vehicle_number'].' ('.$v['capacity'].')') ?></option><?php endforeach;?></select></div>
                        <div class="mb-3"><label class="drv-form-label">Driver</label><select name="driver_id" class="drv-form-select"><option value="0">Unassigned</option><?php foreach($staff_list as $s):?><option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option><?php endforeach;?></select></div>
                        <div class="mb-3"><label class="drv-form-label">Route *</label><select name="route_id" class="drv-form-select" required><option value="">Select...</option><?php foreach($routes as $r):?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['route_name']) ?> (<?= number_format((float)$r['fare_amount']) ?> UGX)</option><?php endforeach;?></select></div>
                    </div>
                    <div class="drv-grid-2">
                        <div class="mb-3"><label class="drv-form-label">Departure Time</label><input type="datetime-local" name="departure_time" class="drv-form-input"></div>
                        <div class="mb-3"><label class="drv-form-label">Arrival Time</label><input type="datetime-local" name="arrival_time" class="drv-form-input"></div>
                    </div>
                    <div class="drv-grid-3">
                        <div class="mb-3"><label class="drv-form-label">Passengers</label><input type="number" name="passengers_count" class="drv-form-input" min="0" value="0"></div>
                        <div class="mb-3"><label class="drv-form-label">Fuel Cost (UGX)</label><input type="number" name="fuel_cost" class="drv-form-input" step="100" min="0" value="0"></div>
                        <div class="mb-3"><label class="drv-form-label">Distance (km)</label><input type="number" name="trip_distance" class="drv-form-input" step="0.1" min="0" value="0"></div>
                    </div>
                    <div class="mb-3"><label class="drv-form-label">Notes</label><input type="text" name="notes" class="drv-form-input" placeholder="Optional notes"></div>
                </div>
                <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="drv-btn drv-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="drv-btn drv-btn-primary"><i class="fas fa-save"></i> Save Trip</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php break;

// ════════════════════════════════════════════
// STUDENT TRANSPORT CRUD
// ════════════════════════════════════════════
case 'student-transport': ?>
<h1 class="drv-page-title">Student Transport</h1>
<p class="drv-page-sub">Assign students to routes and vehicles</p>

<?php if ($edit_entity && $edit_type === 'edit_student'): ?>
<div class="drv-card" style="border-left:4px solid #f59e0b">
    <div class="drv-card-header" style="background:#fffbeb"><h5><i class="fas fa-edit" style="color:#d97706;margin-right:8px"></i>Edit Student Assignment #<?= (int)$edit_entity['id'] ?></h5></div>
    <div class="drv-card-body">
        <form method="POST">
            <input type="hidden" name="action" value="update_student">
            <input type="hidden" name="assignment_id" value="<?= (int)$edit_entity['id'] ?>">
            <input type="hidden" name="page" value="student-transport">
            <div class="drv-grid-3">
                <div><label class="drv-form-label">Student Name *</label><input type="text" name="student_name" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['student_name']) ?>" required></div>
                <div><label class="drv-form-label">Registration No.</label><input type="text" name="registration_number" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['registration_number'] ?? '') ?>"></div>
                <div><label class="drv-form-label">Academic Year</label><input type="text" name="academic_year" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['academic_year'] ?? '2025/2026') ?>"></div>
                <div><label class="drv-form-label">Route</label><select name="route_id" class="drv-form-select"><option value="0">No Route</option><?php foreach($routes as $r):?><option value="<?= (int)$r['id'] ?>" <?= (int)($edit_entity['route_id'])===(int)$r['id']?'selected':'' ?>><?= htmlspecialchars($r['route_name']) ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Vehicle</label><select name="vehicle_id" class="drv-form-select"><option value="0">No Vehicle</option><?php foreach($vehicles as $v):?><option value="<?= (int)$v['id'] ?>" <?= (int)($edit_entity['vehicle_id'])===(int)$v['id']?'selected':'' ?>><?= htmlspecialchars($v['vehicle_number']) ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Status</label><select name="status" class="drv-form-select"><?php foreach(['active','inactive','suspended'] as $s):?><option value="<?= $s ?>" <?= ($edit_entity['status'] ?? '')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Pickup Point</label><input type="text" name="pickup_point" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['pickup_point'] ?? '') ?>"></div>
                <div><label class="drv-form-label">Drop-off Point</label><input type="text" name="dropoff_point" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['dropoff_point'] ?? '') ?>"></div>
            </div>
            <div style="margin-top:18px;display:flex;gap:10px">
                <button type="submit" class="drv-btn drv-btn-success"><i class="fas fa-save"></i> Save Changes</button>
                <a href="?page=student-transport" class="drv-btn drv-btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="drv-card">
    <div class="drv-card-header">
        <h5>Student Assignments (<?= count($student_assignments) ?> students)</h5>
        <button class="drv-btn drv-btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-plus"></i> Add Student</button>
    </div>
    <div class="drv-card-body" style="padding:0">
        <table class="drv-table">
            <thead><tr><th>#</th><th>Student Name</th><th>Reg No.</th><th>Route</th><th>Vehicle</th><th>Pickup</th><th>Drop-off</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($student_assignments)): ?>
            <tr><td colspan="10" class="drv-empty"><i class="fas fa-users"></i><p>No students assigned</p></td></tr>
            <?php else: ?>
            <?php foreach ($student_assignments as $i => $sa): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($sa['student_name']) ?></strong></td>
                <td><?= htmlspecialchars($sa['registration_number'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sa['route_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sa['vehicle_number'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sa['pickup_point'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sa['dropoff_point'] ?? '-') ?></td>
                <td><?= htmlspecialchars($sa['academic_year'] ?? '-') ?></td>
                <td><span class="drv-badge <?= $sa['status']==='active'?'drv-badge-success':($sa['status']==='suspended'?'drv-badge-danger':'drv-badge-secondary') ?>"><?= ucfirst(htmlspecialchars($sa['status'])) ?></span></td>
                <td class="actions">
                    <a href="?page=student-transport&edit_student=<?= (int)$sa['id'] ?>" class="drv-btn drv-btn-outline drv-btn-sm"><i class="fas fa-edit"></i></a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Remove this student assignment?')">
                        <input type="hidden" name="action" value="delete_student">
                        <input type="hidden" name="assignment_id" value="<?= (int)$sa['id'] ?>">
                        <input type="hidden" name="page" value="student-transport">
                        <button type="submit" class="drv-btn drv-btn-danger drv-btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px;border:none">
            <form method="POST">
                <div style="padding:18px 24px;background:#8b5cf6;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
                    <h5 style="margin:0"><i class="fas fa-plus" style="margin-right:8px"></i>Add Student to Transport</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div style="padding:24px">
                    <input type="hidden" name="action" value="add_student">
                    <input type="hidden" name="page" value="student-transport">
                    <div class="drv-grid-3">
                        <div class="mb-3"><label class="drv-form-label">Student Name *</label><input type="text" name="student_name" class="drv-form-input" required placeholder="Full name"></div>
                        <div class="mb-3"><label class="drv-form-label">Student ID</label><input type="number" name="student_id" class="drv-form-input" placeholder="System ID"></div>
                        <div class="mb-3"><label class="drv-form-label">Registration No.</label><input type="text" name="registration_number" class="drv-form-input" placeholder="e.g. ISM/2024/001"></div>
                    </div>
                    <div class="drv-grid-2">
                        <div class="mb-3"><label class="drv-form-label">Route</label><select name="route_id" class="drv-form-select"><option value="0">No Route</option><?php foreach($routes as $r):?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['route_name']) ?></option><?php endforeach;?></select></div>
                        <div class="mb-3"><label class="drv-form-label">Vehicle</label><select name="vehicle_id" class="drv-form-select"><option value="0">No Vehicle</option><?php foreach($vehicles as $v):?><option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['vehicle_number']) ?></option><?php endforeach;?></select></div>
                    </div>
                    <div class="drv-grid-2">
                        <div class="mb-3"><label class="drv-form-label">Pickup Point</label><input type="text" name="pickup_point" class="drv-form-input" placeholder="e.g. Town Center"></div>
                        <div class="mb-3"><label class="drv-form-label">Drop-off Point</label><input type="text" name="dropoff_point" class="drv-form-input" placeholder="e.g. Hospital Road"></div>
                    </div>
                    <div class="mb-3"><label class="drv-form-label">Academic Year</label><input type="text" name="academic_year" class="drv-form-input" value="2025/2026"></div>
                </div>
                <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="drv-btn drv-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="drv-btn drv-btn-success"><i class="fas fa-save"></i> Save Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php break;

// ════════════════════════════════════════════
// FUEL LOG CRUD
// ════════════════════════════════════════════
case 'fuel-log': ?>
<h1 class="drv-page-title">Fuel Log</h1>
<p class="drv-page-sub">Track fuel consumption for all vehicles</p>

<div class="drv-stat-grid" style="margin-bottom:24px">
    <div class="drv-stat-card"><div class="drv-stat-icon green"><i class="fas fa-gas-pump"></i></div><div class="drv-stat-info"><h3><?= number_format($total_liters, 1) ?> L</h3><p>Total Fuel Used</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon red"><i class="fas fa-money-bill"></i></div><div class="drv-stat-info"><h3>UGX <?= number_format($total_cost) ?></h3><p>Total Fuel Cost</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon blue"><i class="fas fa-receipt"></i></div><div class="drv-stat-info"><h3><?= count($fuel_logs) ?></h3><p>Fuel Records</p></div></div>
</div>

<?php if ($edit_entity && $edit_type === 'edit_fuel'): ?>
<div class="drv-card" style="border-left:4px solid #f59e0b">
    <div class="drv-card-header" style="background:#fffbeb"><h5><i class="fas fa-edit" style="color:#d97706;margin-right:8px"></i>Edit Fuel Record #<?= (int)$edit_entity['id'] ?></h5></div>
    <div class="drv-card-body">
        <form method="POST">
            <input type="hidden" name="action" value="update_fuel_log">
            <input type="hidden" name="fuel_id" value="<?= (int)$edit_entity['id'] ?>">
            <input type="hidden" name="page" value="fuel-log">
            <div class="drv-grid-3">
                <div><label class="drv-form-label">Vehicle *</label><select name="vehicle_id" class="drv-form-select" required><?php foreach($vehicles as $v):?><option value="<?= (int)$v['id'] ?>" <?= (int)($edit_entity['vehicle_id'])===(int)$v['id']?'selected':'' ?>><?= htmlspecialchars($v['vehicle_number']) ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Driver</label><select name="driver_id" class="drv-form-select"><option value="0">Unassigned</option><?php foreach($staff_list as $s):?><option value="<?= (int)$s['id'] ?>" <?= (int)($edit_entity['driver_id'])===(int)$s['id']?'selected':'' ?>><?= htmlspecialchars($s['full_name']) ?></option><?php endforeach;?></select></div>
                <div><label class="drv-form-label">Date *</label><input type="date" name="fuel_date" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['fuel_date']) ?>" required></div>
                <div><label class="drv-form-label">Liters *</label><input type="number" name="liters" class="drv-form-input" value="<?= (float)$edit_entity['liters'] ?>" step="0.01" min="0.01" required></div>
                <div><label class="drv-form-label">Cost (UGX)</label><input type="number" name="cost" class="drv-form-input" value="<?= (float)$edit_entity['cost'] ?>" step="100" min="0"></div>
                <div><label class="drv-form-label">Odometer</label><input type="number" name="odometer_reading" class="drv-form-input" value="<?= (float)$edit_entity['odometer_reading'] ?>" step="0.1" min="0"></div>
            </div>
            <div class="mb-3" style="margin-top:12px"><label class="drv-form-label">Station</label><input type="text" name="station" class="drv-form-input" value="<?= htmlspecialchars($edit_entity['station'] ?? '') ?>"></div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="drv-btn drv-btn-success"><i class="fas fa-save"></i> Save Changes</button>
                <a href="?page=fuel-log" class="drv-btn drv-btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="drv-card">
    <div class="drv-card-header">
        <h5>Fuel Records (<?= count($fuel_logs) ?> entries)</h5>
        <button class="drv-btn drv-btn-primary" data-bs-toggle="modal" data-bs-target="#addFuelModal"><i class="fas fa-plus"></i> Add Fuel Log</button>
    </div>
    <div class="drv-card-body" style="padding:0">
        <table class="drv-table">
            <thead><tr><th>#</th><th>Vehicle</th><th>Driver</th><th>Date</th><th>Liters</th><th>Cost</th><th>Odometer</th><th>Station</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($fuel_logs)): ?>
            <tr><td colspan="9" class="drv-empty"><i class="fas fa-gas-pump"></i><p>No fuel logs recorded</p></td></tr>
            <?php else: ?>
            <?php foreach ($fuel_logs as $i => $f): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($f['vehicle_number'] ?? '-') ?></strong></td>
                <td><?= htmlspecialchars($f['driver_name']) ?></td>
                <td><?= date('M d, Y', strtotime($f['fuel_date'])) ?></td>
                <td><?= number_format((float)$f['liters'], 2) ?> L</td>
                <td>UGX <?= number_format((float)$f['cost']) ?></td>
                <td><?= number_format((float)$f['odometer_reading'], 0) ?></td>
                <td><?= htmlspecialchars($f['station'] ?? '-') ?></td>
                <td class="actions">
                    <a href="?page=fuel-log&edit_fuel=<?= (int)$f['id'] ?>" class="drv-btn drv-btn-outline drv-btn-sm"><i class="fas fa-edit"></i></a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this fuel record?')">
                        <input type="hidden" name="action" value="delete_fuel_log">
                        <input type="hidden" name="fuel_id" value="<?= (int)$f['id'] ?>">
                        <input type="hidden" name="page" value="fuel-log">
                        <button type="submit" class="drv-btn drv-btn-danger drv-btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addFuelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;border:none">
            <form method="POST">
                <div style="padding:18px 24px;background:#ef4444;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
                    <h5 style="margin:0"><i class="fas fa-plus" style="margin-right:8px"></i>Add Fuel Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div style="padding:24px">
                    <input type="hidden" name="action" value="add_fuel_log">
                    <input type="hidden" name="page" value="fuel-log">
                    <div class="drv-grid-2">
                        <div class="mb-3"><label class="drv-form-label">Vehicle *</label><select name="vehicle_id" class="drv-form-select" required><option value="">Select...</option><?php foreach($vehicles as $v):?><option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['vehicle_number'].' - '.$v['vehicle_type']) ?></option><?php endforeach;?></select></div>
                        <div class="mb-3"><label class="drv-form-label">Driver</label><select name="driver_id" class="drv-form-select"><option value="0">Unassigned</option><?php foreach($staff_list as $s):?><option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option><?php endforeach;?></select></div>
                    </div>
                    <div class="mb-3"><label class="drv-form-label">Fuel Date *</label><input type="date" name="fuel_date" class="drv-form-input" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="drv-grid-2">
                        <div class="mb-3"><label class="drv-form-label">Liters *</label><input type="number" name="liters" class="drv-form-input" step="0.01" min="0.01" required placeholder="0.00"></div>
                        <div class="mb-3"><label class="drv-form-label">Cost (UGX)</label><input type="number" name="cost" class="drv-form-input" step="100" min="0" value="0"></div>
                    </div>
                    <div class="drv-grid-2">
                        <div class="mb-3"><label class="drv-form-label">Odometer Reading</label><input type="number" name="odometer_reading" class="drv-form-input" step="0.1" min="0" placeholder="0"></div>
                        <div class="mb-3"><label class="drv-form-label">Station</label><input type="text" name="station" class="drv-form-input" placeholder="e.g. Shell Iganga"></div>
                    </div>
                </div>
                <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="drv-btn drv-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="drv-btn drv-btn-danger"><i class="fas fa-save"></i> Save Fuel Log</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php break;

// ════════════════════════════════════════════
// REPORTS
// ════════════════════════════════════════════
case 'reports': ?>
<h1 class="drv-page-title">Transport Reports</h1>
<p class="drv-page-sub">Analytics and summaries for transport operations</p>

<div class="drv-stat-grid">
    <div class="drv-stat-card"><div class="drv-stat-icon blue"><i class="fas fa-route"></i></div><div class="drv-stat-info"><h3><?= $total_routes ?></h3><p>Active Routes</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon green"><i class="fas fa-bus"></i></div><div class="drv-stat-info"><h3><?= $total_vehicles ?></h3><p>Total Vehicles</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon amber"><i class="fas fa-road"></i></div><div class="drv-stat-info"><h3><?= count($trips) ?></h3><p>Total Trips</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon purple"><i class="fas fa-users"></i></div><div class="drv-stat-info"><h3><?= $total_students ?></h3><p>Assigned Students</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon red"><i class="fas fa-gas-pump"></i></div><div class="drv-stat-info"><h3>UGX <?= number_format($total_fuel_cost) ?></h3><p>Fuel This Month</p></div></div>
    <div class="drv-stat-card"><div class="drv-stat-icon teal"><i class="fas fa-check-circle"></i></div><div class="drv-stat-info"><h3><?= count(array_filter($trips, fn($t) => ($t['status'] ?? '') === 'Completed')) ?></h3><p>Completed Trips</p></div></div>
</div>

<div class="drv-grid-2">
    <div class="drv-card">
        <div class="drv-card-header"><h5><i class="fas fa-bus" style="color:#3b82f6;margin-right:8px"></i>Fleet Summary</h5></div>
        <div class="drv-card-body">
            <table class="drv-table">
                <thead><tr><th>Vehicle</th><th>Type</th><th>Capacity</th><th>Status</th><th>Trips</th></tr></thead>
                <tbody>
                <?php foreach ($vehicles as $v): ?>
                <?php $trip_count = count(array_filter($trips, fn($t) => (int)($t['vehicle_id'] ?? 0) === (int)$v['id'])); ?>
                <tr>
                    <td><strong><?= htmlspecialchars($v['vehicle_number']) ?></strong></td>
                    <td><?= htmlspecialchars($v['vehicle_type']) ?></td>
                    <td><?= (int)$v['capacity'] ?></td>
                    <td><span class="drv-badge <?= ($v['status'] ?? '')==='Available'?'drv-badge-success':(($v['status'] ?? '')==='On Trip'?'drv-badge-info':'drv-badge-warning') ?>"><?= htmlspecialchars($v['status']) ?></span></td>
                    <td><?= $trip_count ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="drv-card">
        <div class="drv-card-header"><h5><i class="fas fa-route" style="color:#10b981;margin-right:8px"></i>Route Performance</h5></div>
        <div class="drv-card-body">
            <table class="drv-table">
                <thead><tr><th>Route</th><th>Distance</th><th>Fare</th><th>Trips</th></tr></thead>
                <tbody>
                <?php foreach ($routes as $r): ?>
                <?php $trip_count = count(array_filter($trips, fn($t) => (int)($t['route_id'] ?? 0) === (int)$r['id'])); ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['route_name']) ?></strong></td>
                    <td><?= number_format((float)$r['distance_km'], 1) ?> km</td>
                    <td>UGX <?= number_format((float)$r['fare_amount']) ?></td>
                    <td><?= $trip_count ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="drv-card">
    <div class="drv-card-header"><h5><i class="fas fa-gas-pump" style="color:#ef4444;margin-right:8px"></i>Fuel Consumption by Vehicle</h5></div>
    <div class="drv-card-body">
        <table class="drv-table">
            <thead><tr><th>Vehicle</th><th>Total Records</th><th>Total Liters</th><th>Total Cost</th></tr></thead>
            <tbody>
            <?php foreach ($vehicles as $v): ?>
            <?php $vf = array_filter($fuel_logs, fn($f) => (int)($f['vehicle_id'] ?? 0) === (int)$v['id']); $vl = 0; $vc = 0; foreach ($vf as $ff) { $vl += (float)$ff['liters']; $vc += (float)$ff['cost']; } ?>
            <tr>
                <td><strong><?= htmlspecialchars($v['vehicle_number']) ?></strong></td>
                <td><?= count($vf) ?></td>
                <td><?= number_format($vl, 2) ?> L</td>
                <td>UGX <?= number_format($vc) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php break;

// ════════════════════════════════════════════
// DEFAULT
// ════════════════════════════════════════════
default: ?>
<h1 class="drv-page-title">Transport Dashboard</h1>
<p class="drv-page-sub">Select a module from the sidebar</p>
<?php break;
endswitch; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
