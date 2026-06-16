<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/news_management_widget.php';

$ctx = bootstrapStaffDashboard(['director', 'academics']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'Director of Academics';
$website_conn = $ctx['website'];

// Set dashboard statistics from database
$total_students = 0;
$total_lecturers = 0;
$active_courses = 0;
$avg_gpa = 0;

try {
    $students_conn = $ctx['students'] ?? null;
    if ($students_conn) {
        $result = $students_conn->query("SELECT COUNT(*) as cnt FROM students");
        if ($result) $total_students = (int)$result->fetch_assoc()['cnt'];
    }

    if ($conn) {
        $result = $conn->query("SELECT COUNT(*) as cnt FROM staff");
        if ($result) $total_lecturers = (int)$result->fetch_assoc()['cnt'];

        $result = $conn->query("SELECT COUNT(*) as cnt FROM academic_programs WHERE status = 'Active'");
        if ($result) $active_courses = (int)$result->fetch_assoc()['cnt'];

        $result = $conn->query("SELECT AVG(marks) as avg_gpa FROM academic_records");
        if ($result) $avg_gpa = round((float)$result->fetch_assoc()['avg_gpa'], 1);
    }
} catch (Exception $e) {}

// Get recent activities
$recent_activities = [];
if ($conn) {
    try {
        $result = $conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 10");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_activities[] = $row;
            }
        }
    } catch (Exception $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Director of Academics Dashboard - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="dashboard-professional.css">
    <link rel="stylesheet" href="dashboard-mobile.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <style>
        :root {
            --isnm-blue: #1e3a8a;
            --isnm-light-blue: #3b82f6;
            --isnm-green: #059669;
            --isnm-gold: #d97706;
            --isnm-dark-green: #0f4c3a;
        }
        
        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--isnm-blue), var(--isnm-light-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include_once '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Director of Academics Dashboard</h1>
                    <p>Academic Programs Oversight - Iganga School of Nursing and Midwifery</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <a href="../store_request.php" class="btn btn-sm btn-outline-primary ms-2"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                    <a href="../news.php" class="btn btn-sm btn-outline-primary ms-1"><i class="fas fa-newspaper me-1"></i>News</a>
                    <a href="../student-directory.php" class="btn btn-sm btn-outline-info ms-2"><i class="fas fa-address-book me-1"></i>Directory</a>
                    <a href="../index.php" class="btn btn-sm btn-outline-secondary ms-1"><i class="fas fa-home"></i></a>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Academic Overview -->
                <section id="overview" class="content-section">
                    <h2>Academic Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card success">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_students); ?></h3>
                                <p>Total Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card primary">
                            <div class="stat-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($total_lecturers); ?></h3>
                                <p>Total Lecturers</p>
                            </div>
                        </div>
                        
                        <div class="stat-card info">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($active_courses); ?></h3>
                                <p>Active Courses</p>
                            </div>
                        </div>
                        
                        <div class="stat-card warning">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo number_format($avg_gpa, 1); ?></h3>
                                <p>Average GPA</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Program Management -->
                <section id="programs" class="content-section">
                    <h2><i class="fas fa-book me-2"></i>Program Management</h2>
                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                        <div class="stat-card">
                            <h3 class="fw-bold">Certificate in Nursing</h3>
                            <p class="text-muted mb-2">2-Year Program</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Enrolled:</span>
                                <strong>85 Students</strong>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <h3 class="fw-bold">Certificate in Midwifery</h3>
                            <p class="text-muted mb-2">2-Year Program</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Enrolled:</span>
                                <strong>75 Students</strong>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <h3 class="fw-bold">Diploma in Nursing</h3>
                            <p class="text-muted mb-2">3-Year Program</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Enrolled:</span>
                                <strong>65 Students</strong>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <h3 class="fw-bold">Diploma in Midwifery</h3>
                            <p class="text-muted mb-2">3-Year Program</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Enrolled:</span>
                                <strong>75 Students</strong>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Examinations & Assessment -->
                <section id="exams" class="content-section">
                    <h2><i class="fas fa-clipboard-list me-2"></i>Examinations & Assessment</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Exam Title</th>
                                    <th>Program</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>End of Semester Exams</td>
                                    <td>All Programs</td>
                                    <td><?php echo date('F Y'); ?></td>
                                    <td><span class="badge bg-warning">Upcoming</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">View Schedule</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Reports -->
                <section id="reports" class="content-section">
                    <h2><i class="fas fa-chart-bar me-2"></i>Academic Reports</h2>
                    <div class="reports-grid">
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3>Student Progress Report</h3>
                            <p>Track student academic progress</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Attendance Report</h3>
                            <p>View class attendance records</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h3>Graduation Report</h3>
                            <p>Student graduation statistics</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3>Academic Performance Report</h3>
                            <p>Overall student performance summary</p>
                            <button class="btn btn-primary">Generate</button>
                        </div>
                    </div>
                </section>

                <!-- News Management -->
                <section id="news" class="content-section">
                    <h2><i class="fas fa-newspaper me-2"></i>News &amp; Announcements</h2>
                    <?php renderNewsWidget($conn, $website_conn, $ctx['user']['id'] ?? 0, $user_name, $_SESSION['role'] ?? 'Director Academics', 5); ?>
                </section>

                <!-- Recent Activities -->
                <section id="activity" class="content-section">
                    <h2><i class="fas fa-history me-2"></i>Recent Academic Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="activity-content flex-grow-1">
                                <strong><?php echo htmlspecialchars($activity['activity'] ?? 'Activity'); ?></strong>
                                <small class="text-muted d-block"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar navigation
            document.querySelectorAll('.sidebar-menu .nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.sidebar-menu .nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    const target = this.getAttribute('href');
                    document.querySelectorAll('.content-section').forEach(section => {
                        section.style.display = 'none';
                    });
                    document.querySelector(target).style.display = 'block';
                });
            });
        });
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
