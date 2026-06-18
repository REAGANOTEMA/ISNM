<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar', 'academics', 'lecturer', 'head']);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$pageTitle = 'Timetable Management';

$totalSlots = $byDay = $byRoom = 0;
$days = [];
$conn = $staffDb ?: $studentsDb;
if ($conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) as c FROM timetable WHERE WEEK(week_start)=WEEK(CURDATE())");
        if ($r) $totalSlots = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(DISTINCT day_of_week) as c FROM timetable");
        if ($r) $byDay = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(DISTINCT room) as c FROM timetable");
        if ($r) $byRoom = (int)$r->fetch_assoc()['c'];
        if (!$r) { $byDay = $conn->query("SELECT COUNT(DISTINCT day) as c FROM class_schedules")->fetch_assoc()['c'] ?? 0; }
        $r = $conn->query("SELECT t.day_of_week, t.time_slot, COALESCE(c.course_name,t.course_name) as course_name, t.instructor, t.room FROM timetable t LEFT JOIN courses c ON t.course_id=c.id ORDER BY FIELD(t.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), t.time_slot LIMIT 100");
        if (!$r) $r = $conn->query("SELECT day_of_week, time_slot, course_name, instructor, room FROM timetable ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), time_slot LIMIT 100");
        if (!$r && $studentsDb) $r = $studentsDb->query("SELECT day_of_week, time_slot, course_name, instructor, room FROM timetable ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), time_slot LIMIT 100");
        if ($r) while ($row = $r->fetch_assoc()) {
            $d = $row['day_of_week'] ?? $row['day'] ?? 'Unknown';
            if (!isset($days[$d])) $days[$d] = [];
            $days[$d][] = $row;
        }
    } catch (Exception $e) {
        if ($studentsDb) try {
            $r = $studentsDb->query("SELECT day_of_week, time_slot, course_name, instructor, room FROM timetable ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), time_slot LIMIT 100");
            if ($r) while ($row = $r->fetch_assoc()) {
                $d = $row['day_of_week'] ?? 'Unknown';
                if (!isset($days[$d])) $days[$d] = [];
                $days[$d][] = $row;
            }
        } catch (Exception $e2) {}
    }
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
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-calendar-alt me-2"></i>Timetable Management</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>
    <div class="stats-grid">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $totalSlots ?></h3><p>This Week</p></div></div>
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
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead><tr><th>Time</th><th>Course</th><th>Instructor</th><th>Room</th></tr></thead>
                <tbody>
                    <?php foreach ($days[$day] as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['time_slot'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['course_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['instructor'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['room'] ?? '') ?></td>
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
</body>
</html>