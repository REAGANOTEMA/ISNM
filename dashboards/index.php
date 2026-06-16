<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_config.php';

$ctx = bootstrapStaffDashboard();
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$staff = $ctx['staff'];
$students = $ctx['students'];
$website = $ctx['website'];
$user_name = $user['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Real summary stats
$total_students = 0; $active_students = 0; $total_staff = 0;
$total_programs = 0; $pending_apps = 0; $today_collections = 0;

if ($students) {
    $r = $students->query("SELECT COUNT(*) as c FROM students");
    if ($r) $total_students = (int)$r->fetch_assoc()['c'];
    $r = $students->query("SELECT COUNT(*) as c FROM students WHERE status='Active' OR status='active'");
    if ($r) $active_students = (int)$r->fetch_assoc()['c'];
}
if ($staff) {
    $r = $staff->query("SELECT COUNT(*) as c FROM staff");
    if ($r) $total_staff = (int)$r->fetch_assoc()['c'];
    $r = $staff->query("SELECT COUNT(*) as c FROM programs WHERE status='Active'");
    if ($r) $total_programs = (int)$r->fetch_assoc()['c'];
}
if ($website) {
    $r = $website->query("SELECT COUNT(*) as c FROM applications WHERE status IN ('New','Submitted')");
    if ($r) $pending_apps = (int)$r->fetch_assoc()['c'];
}

// Recent activity
$recent_activities = [];
if ($staff) {
    $r = $staff->query("SELECT a.*, s.full_name FROM activity_log a LEFT JOIN staff s ON a.user_id = s.id ORDER BY a.created_at DESC LIMIT 10");
    if ($r) { while ($row = $r->fetch_assoc()) $recent_activities[] = $row; }
}

$modules = getFilteredModules($user_role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ISNM Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .dashboard-main { margin-left: 270px; padding: 20px; min-height: 100vh; }
        .page-title { margin-bottom: 24px; }
        .page-title h1 { font-size: 24px; font-weight: 700; color: #1a237e; margin: 0; }
        .page-title p { color: #666; margin: 4px 0 0 0; font-size: 14px; }
        .stat-card {
            background: white; border-radius: 12px; padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: transform 0.2s, box-shadow 0.2s;
            display: flex; align-items: center; gap: 16px; height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .stat-icon {
            width: 56px; height: 56px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;
        }
        .stat-info h3 { font-size: 28px; font-weight: 700; margin: 0; color: #1a237e; }
        .stat-info p { margin: 2px 0 0 0; color: #666; font-size: 13px; }
        .card-section { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card-section h5 { font-size: 16px; font-weight: 600; color: #1a237e; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #f0f2f5; }
        .quick-module {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            background: #f8f9ff; border-radius: 8px; text-decoration: none; color: #333;
            transition: all 0.2s; border: 1px solid #eee;
        }
        .quick-module:hover { background: #e8eaff; border-color: #c5cae9; transform: translateX(4px); color: #1a237e; }
        .activity-item { padding: 10px 0; border-bottom: 1px solid #f0f2f5; font-size: 13px; }
        .activity-item:last-child { border-bottom: none; }
        .activity-time { color: #999; font-size: 12px; }
        @media (max-width: 768px) {
            .dashboard-main { margin-left: 0; padding: 70px 12px 12px; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="page-title">
            <h1><i class="fas fa-tachometer-alt me-2" style="color:#3949ab;"></i>Dashboard Overview</h1>
            <p>Welcome back, <?= htmlspecialchars($user_name) ?> — <?= date('l, F j, Y') ?></p>
        </div>

        <!-- Summary stat cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e3f2fd;color:#1565c0;"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($total_students) ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f5e9;color:#2e7d32;"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($total_staff) ?></h3>
                        <p>Total Staff</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fff3e0;color:#e65100;"><i class="fas fa-book-open"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($total_programs) ?></h3>
                        <p>Active Programs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fce4ec;color:#c62828;"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($pending_apps) ?></h3>
                        <p>Pending Applications</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Access to Modules -->
            <div class="col-lg-8">
                <div class="card-section">
                    <h5><i class="fas fa-th-large me-2"></i>Quick Access</h5>
                    <div class="row g-2">
                        <?php foreach ($modules as $parent): ?>
                        <?php foreach ($parent['children'] as $child): ?>
                        <div class="col-md-6">
                            <a href="<?= htmlspecialchars($child['route']) ?>" class="quick-module">
                                <i class="<?= htmlspecialchars($parent['icon']) ?>" style="color:#3949ab;width:20px;"></i>
                                <span><?= htmlspecialchars($child['title']) ?></span>
                            </a>
                        </div>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-4">
                <div class="card-section">
                    <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                    <?php if (!empty($recent_activities)): ?>
                        <?php foreach ($recent_activities as $act): ?>
                        <div class="activity-item">
                            <strong><?= htmlspecialchars($act['full_name'] ?? 'System') ?></strong>
                            <span class="text-muted">— <?= htmlspecialchars(mb_substr($act['action'] ?? $act['description'] ?? '', 0, 80)) ?></span>
                            <div class="activity-time"><?= htmlspecialchars($act['created_at'] ?? '') ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No recent activity recorded.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
