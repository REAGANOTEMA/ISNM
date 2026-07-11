<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar', 'academics', 'lecturer', 'head']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$pageTitle = 'Timetable Management';

$staff_db = defined('STAFF_DB_NAME') ? STAFF_DB_NAME : 'igangaschool_staffs';

if ($staffDb) {
    $staffDb->query("CREATE TABLE IF NOT EXISTS `{$staff_db}`.`academic_timetable` (id INT AUTO_INCREMENT PRIMARY KEY, timetable_id VARCHAR(50) UNIQUE, academic_year VARCHAR(20) DEFAULT NULL, semester VARCHAR(100) DEFAULT NULL, program_code VARCHAR(50) DEFAULT '', course_code VARCHAR(50) DEFAULT '', day_of_week VARCHAR(20) NOT NULL, start_time TIME NULL, end_time TIME NULL, venue VARCHAR(200) DEFAULT '', lecturer_id INT DEFAULT 0, timetable_status VARCHAR(50) DEFAULT 'Draft', created_by INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_day (day_of_week), KEY idx_lecturer (lecturer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$totalSlots = $byDay = $byRoom = 0;
$days = [];
$conn = $staffDb;
if ($conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) as c FROM academic_timetable");
        if ($r) $totalSlots = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(DISTINCT day_of_week) as c FROM academic_timetable");
        if ($r) $byDay = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(DISTINCT venue) as c FROM academic_timetable WHERE venue != ''");
        if ($r) $byRoom = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT t.day_of_week, CONCAT(t.start_time, ' - ', t.end_time) as time_slot, t.course_code, t.venue as room, s.full_name as instructor, t.academic_year, t.semester FROM academic_timetable t LEFT JOIN staff s ON t.lecturer_id=s.id ORDER BY FIELD(t.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), t.start_time LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) {
            $d = $row['day_of_week'] ?? 'Unknown';
            if (!isset($days[$d])) $days[$d] = [];
            $days[$d][] = $row;
        }
    } catch (Exception $e) { error_log('timetable context: ' . $e->getMessage()); }
}
$dayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-calendar-alt me-2"></i>Timetable Management</h4> <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-print"></i></button>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="stats-grid">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $totalSlots ?></h3><p>Total Slots</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-calendar-day"></i></div><div class="stat-content"><h3><?= $byDay ?></h3><p>Days Active</p></div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-door-open"></i></div><div class="stat-content"><h3><?= $byRoom ?></h3><p>Rooms Used</p></div></div>
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div><div class="stat-content"><h3><?= array_sum(array_map('count',$days)) ?></h3><p>Total Sessions</p></div></div>
    </div>
    <?php if (empty($days)): ?>
    <div class="content-section"><p class="text-center text-muted my-3">No timetable entries found.</p></div>
    <?php else: ?>
    <?php foreach ($dayOrder as $day): if (!isset($days[$day])) continue; ?>
    <div class="content-section mb-3">
        <h5 class="fw-bold mb-3"><i class="fas fa-calendar-day me-2"></i><?= htmlspecialchars($day) ?></h5>
        <div class="mb-2"><input class="form-control form-control-sm" style="max-width:300px" id="srchUSDL" type="text" placeholder="Search..." onkeyup="filterTable('srchUSDL','tblUSDL')"></div>
<div class="table-responsive">
            <table id="tblUSDL" class="table table-striped table-hover mb-0">
                <thead><tr><th>Time</th><th>Course</th><th>Instructor</th><th>Room</th></tr></thead>
                <tbody>
                    <?php foreach ($days[$day] as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['time_slot'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['course_code'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['instructor'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($t['room'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
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
