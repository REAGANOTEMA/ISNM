<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['sickbay']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'Sickbay Staff';
$user_role = $user['role'] ?? 'Sickbay';

$students_conn = getStudentsConnection();
$students_conn->set_charset('utf8mb4');

$active_students = 0;
$today_visits = 0;
$care_requests = 0;

if ($result = $students_conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'Student' AND status = 'active'")) {
    $active_students = (int) ($result->fetch_assoc()['cnt'] ?? 0);
}

if ($result = $students_conn->query("SELECT COUNT(*) AS cnt FROM student_health_records WHERE DATE(created_at) = CURDATE()")) {
    $today_visits = (int) ($result->fetch_assoc()['cnt'] ?? 0);
}

if ($result = $students_conn->query("SELECT COUNT(*) AS cnt FROM student_health_records WHERE status = 'pending'")) {
    $care_requests = (int) ($result->fetch_assoc()['cnt'] ?? 0);
}

$recent_issues = [
    ['title' => 'Review medication log', 'time' => '15 minutes ago'],
    ['title' => 'Student referred for follow-up', 'time' => '1 hour ago'],
    ['title' => 'Temperature check completed', 'time' => '2 hours ago'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Sickbay Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="dashboard-style.css" rel="stylesheet">
    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Sickbay Dashboard</h1>
                <p class="text-muted mb-0">Helping students stay healthy with real-time health records and quick access to care.</p>
            </div>
            <div class="text-end">
                <p class="mb-1">Welcome, <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
                <a href="../student-directory.php" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-address-book me-1"></i>Directory</a>
<a href="../store_request.php" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-shopping-cart me-1"></i>Store</a>
<a href="../news.php" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-newspaper me-1"></i>News</a>
                <a href="../logout.php" class="btn btn-sm btn-danger">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <?php include_once __DIR__ . '/../views/student_search_component.php'; ?>
            </div>
            <div class="col-lg-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Sickbay Summary</h5>
                                <p class="card-text text-muted">Monitor student health activities and incident follow-up.</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $active_students; ?></h3>
                                        <small class="text-muted">Active Students</small>
                                    </div>
                                    <div class="text-primary"><i class="fas fa-user-injured fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Today&apos;s Visits</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $today_visits; ?></h3>
                                        <small class="text-muted">Health visits logged today</small>
                                    </div>
                                    <div class="text-success"><i class="fas fa-calendar-check fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Pending Care</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $care_requests; ?></h3>
                                        <small class="text-muted">Pending requests</small>
                                    </div>
                                    <div class="text-warning"><i class="fas fa-notes-medical fa-2x"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Recent Sickbay Notes</h5>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($recent_issues as $issue): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                            <span><?php echo htmlspecialchars($issue['title']); ?></span>
                                            <small class="text-muted"><?php echo htmlspecialchars($issue['time']); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
