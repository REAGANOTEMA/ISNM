<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['admissions', 'director']);
$studentsConn = $ctx['students'];
$staffConn = $ctx['staff'];
$user = $ctx['user'];
$userName = $user['full_name'] ?? 'Admissions User';

$studentsTables = tableList($studentsConn);
$staffTables = tableList($staffConn);

$stats = [
    'applications' => 0,
    'pending' => 0,
    'admitted' => 0,
    'enrolled' => 0,
];

if (in_array('student_admissions', $studentsTables, true)) {
    $stats['applications'] = scalar($studentsConn, 'SELECT COUNT(*) AS c FROM student_admissions');
    $stats['pending'] = scalar($studentsConn, "SELECT COUNT(*) AS c FROM student_admissions WHERE admission_status IN ('Applied','Interview')");
    $stats['admitted'] = scalar($studentsConn, "SELECT COUNT(*) AS c FROM student_admissions WHERE admission_status = 'Admitted'");
    $stats['enrolled'] = scalar($studentsConn, "SELECT COUNT(*) AS c FROM student_admissions WHERE admission_status = 'Enrolled'");
}

$stats['active_students'] = in_array('students', $studentsTables, true)
    ? scalar($studentsConn, "SELECT COUNT(*) AS c FROM students WHERE status = 'Active'")
    : 0;

$recentApplications = [];
if (in_array('student_admissions', $studentsTables, true)) {
    $recentApplications = rows($studentsConn, "SELECT application_number, applicant_name, program, academic_year, admission_status, application_date FROM student_admissions ORDER BY application_date DESC, id DESC LIMIT 8");
}

$recentActivity = rows($staffConn, "SELECT activity_description AS activity, created_at FROM staff_activity_log WHERE module_accessed IN ('admissions', 'requirements', 'student_admissions') OR activity_description LIKE '%admission%' ORDER BY created_at DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Director Admissions Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="../dashboards/dashboard-mobile.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>ISNM Management</h4>
                <small><?php echo htmlspecialchars($userName); ?></small>
                <span class="badge bg-primary">Admissions</span>
            </div>
            <nav class="sidebar-menu">
                <a href="#overview" class="nav-link active"><i class="fas fa-chart-line"></i> Overview</a>
                <a href="#applications" class="nav-link"><i class="fas fa-file-signature"></i> Applications</a>
                <a href="#activity" class="nav-link"><i class="fas fa-history"></i> Activity</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="dashboard-main">
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Director Admissions Dashboard</h1>
                    <p>Admissions, requirements, and applicant tracking</p>
                </div>
                <div class="header-right">
                    <div class="date-time"><i class="fas fa-calendar-alt"></i> <span><?php echo date('l, F j, Y'); ?></span></div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <span><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>
            </div>
            <div class="dashboard-content">
                <section id="overview" class="content-section">
                    <div class="stats-grid">
                        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-file-signature"></i></div><div class="stat-content"><h3><?php echo number_format($stats['applications']); ?></h3><p>Total Applications</p></div></div>
                        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?php echo number_format($stats['pending']); ?></h3><p>Pending Review</p></div></div>
                        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?php echo number_format($stats['admitted']); ?></h3><p>Admitted</p></div></div>
                        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-user-graduate"></i></div><div class="stat-content"><h3><?php echo number_format($stats['enrolled']); ?></h3><p>Enrolled</p></div></div>
                        <div class="stat-card dark"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-content"><h3><?php echo number_format($stats['active_students']); ?></h3><p>Active Students</p></div></div>
                    </div>
                </section>
                <section id="applications" class="content-section">
                    <div class="section-heading">
                        <h2>Recent Applications</h2>
                        <span>Latest admissions records</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr><th>Application</th><th>Applicant</th><th>Program</th><th>Year</th><th>Status</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentApplications)): ?>
                                    <tr><td colspan="6" class="text-muted">No admissions records found yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentApplications as $application): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($application['application_number'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($application['applicant_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($application['program'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($application['academic_year'] ?? ''); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($application['admission_status'] ?? ''); ?></span></td>
                                            <td><?php echo htmlspecialchars($application['application_date'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <section id="activity" class="content-section">
                    <div class="section-heading">
                        <h2>Recent Admissions Activity</h2>
                        <span>Audit trail from staff logs</span>
                    </div>
                    <div class="timeline">
                        <?php if (empty($recentActivity)): ?>
                            <p class="text-muted">No admissions activity logged yet.</p>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon"><i class="fas fa-check"></i></div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($activity['activity'] ?? ''); ?></strong>
                                        <small><?php echo htmlspecialchars($activity['created_at'] ?? ''); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
</body>
</html>
<?php
function tableList($conn) {
    if (!$conn) return [];
    $result = $conn->query('SHOW TABLES');
    $tables = [];
    if (!$result) return $tables;
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    return $tables;
}

function scalar($conn, $sql) {
    if (!$conn) return 0;
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return (int) ($row['c'] ?? 0);
}

function rows($conn, $sql) {
    if (!$conn) return [];
    $result = $conn->query($sql);
    if (!$result) return [];
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
