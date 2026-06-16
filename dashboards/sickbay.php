<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/dashboard_inventory_widget.php';

$ctx = bootstrapStaffDashboard(['sickbay']);
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'Sickbay Staff';
$user_role = $user['role'] ?? 'Sickbay';

// Helper: safe query single value
function sb_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { return 0; }
}

// Helper: safe fetch all
function sb_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Exception $e) { return []; }
}

$active_students = sb_q($students_conn, "SELECT COUNT(*) AS cnt FROM users WHERE role = 'Student' AND status = 'active'");
$total_students = sb_q($students_conn, "SELECT COUNT(*) AS cnt FROM students WHERE status = 'Active'");
if ($total_students < 1) $total_students = $active_students;
$today_visits = sb_q($students_conn, "SELECT COUNT(*) AS cnt FROM student_health_records WHERE DATE(created_at) = CURDATE()");
$pending_care = sb_q($students_conn, "SELECT COUNT(*) AS cnt FROM student_health_records WHERE status = 'pending'");
$critical_cases = sb_q($students_conn, "SELECT COUNT(*) AS cnt FROM student_health_records WHERE status = 'critical'");
$recovered_today = sb_q($students_conn, "SELECT COUNT(*) AS cnt FROM student_health_records WHERE status = 'recovered' AND DATE(updated_at) = CURDATE()");
$total_visits = sb_q($students_conn, "SELECT COUNT(*) AS cnt FROM student_health_records");

$recent_records = sb_fetch($students_conn, "SELECT shr.*, s.full_name, s.student_id, s.program, s.phone FROM student_health_records shr LEFT JOIN students s ON shr.student_id = s.id OR shr.student_id = s.student_id ORDER BY shr.created_at DESC LIMIT 10");

$recent_activities = [];
try {
    $r = $staff_conn->query("SELECT activity_description as activity, created_at FROM staff_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 5");
    if ($r) $recent_activities = $r->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {}
if (empty($recent_activities)) {
    $recent_activities = [['activity' => 'Sickbay dashboard accessed', 'created_at' => date('Y-m-d H:i:s')]];
}

// Handle POST: add health record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_health_record' && $students_conn) {
    $student_name = $students_conn->real_escape_string(trim($_POST['student_name'] ?? ''));
    $temperature = $students_conn->real_escape_string(trim($_POST['temperature'] ?? ''));
    $blood_pressure = $students_conn->real_escape_string(trim($_POST['blood_pressure'] ?? ''));
    $symptoms = $students_conn->real_escape_string(trim($_POST['symptoms'] ?? ''));
    $diagnosis = $students_conn->real_escape_string(trim($_POST['diagnosis'] ?? ''));
    $treatment = $students_conn->real_escape_string(trim($_POST['treatment'] ?? ''));
    $status = $students_conn->real_escape_string(trim($_POST['status'] ?? 'treated'));

    if ($student_name) {
        try {
            $students_conn->query("INSERT INTO student_health_records (student_id, diagnosis, symptoms, treatment, temperature, blood_pressure, status, created_by, created_at, updated_at) VALUES (NULL, '$diagnosis', '$symptoms', '$treatment', '$temperature', '$blood_pressure', '$status', '$user_name', NOW(), NOW())");
            $_SESSION['success'] = 'Health record saved successfully.';
        } catch (Exception $e) {
            try {
                $staff_conn->query("INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, created_at) VALUES (0, 'Health Record', 'Sickbay: $student_name - $diagnosis', NOW())");
                $_SESSION['success'] = 'Health record logged to activity.';
            } catch (Exception $e2) {
                $_SESSION['success'] = 'Health record noted. Database tables may need setup.';
            }
        }
    } else {
        $_SESSION['error'] = 'Student name is required.';
    }
    header('Location: sickbay.php');
    exit;
}

$staff_on_duty = sb_q($staff_conn, "SELECT COUNT(*) AS cnt FROM staff WHERE (department LIKE '%Sickbay%' OR department LIKE '%Health%' OR role LIKE '%Sickbay%') AND status = 'Active'");
if ($staff_on_duty < 1) $staff_on_duty = 1;

$pageTitle = 'Sickbay Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Sickbay Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="dashboard-style.css" rel="stylesheet">
    <link href="dashboard-professional.css" rel="stylesheet">
    <link href="dashboard-mobile.css" rel="stylesheet">
    <style>
        :root {
            --isnm-blue: #1a237e;
            --isnm-light-blue: #3949ab;
            --isnm-green: #059669;
            --isnm-gold: #d97706;
            --isnm-red: #dc2626;
            --isnm-teal: #0d9488;
            --isnm-rose: #e11d48;
            --card-yellow-accent: #ffe082;
            --card-yellow: #fef9e7;
            --card-chocolate-accent: #d7ccc8;
            --card-chocolate: #f0dcc8;
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f4f8; }
        .page-content { margin-left: 280px; flex: 1; min-height: 100vh; }
        @media (max-width: 768px) { .page-content { margin-left: 0; } }
        .top-bar { background: #fff; padding: 14px 22px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07); position: sticky; top: 0; z-index: 100; }
        .content-area { padding: 22px; }
        .stat-card { background: linear-gradient(to bottom, var(--card-yellow-accent) 0%, var(--card-yellow-accent) 5px, var(--card-yellow) 5px, var(--card-yellow) 100%); border-radius: 14px; padding: 20px; display: flex; align-items: center; gap: 14px; transition: transform .25s; }
        .stat-card:hover { transform: translateY(-4px); }
        .si { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #fff; flex-shrink: 0; }
        .si-blue { background: linear-gradient(135deg, #1a237e, #3949ab); }
        .si-green { background: linear-gradient(135deg, #2e7d32, #43a047); }
        .si-cyan { background: linear-gradient(135deg, #0277bd, #039be5); }
        .si-orange { background: linear-gradient(135deg, #e65100, #fb8c00); }
        .si-red { background: linear-gradient(135deg, #b71c1c, #ef5350); }
        .si-purple { background: linear-gradient(135deg, #4a148c, #8e24aa); }
        .si-teal { background: linear-gradient(135deg, #0d9488, #14b8a6); }
        .si-rose { background: linear-gradient(135deg, #9d174d, #db2777); }
        .stat-content h3 { font-size: 1.6rem; font-weight: 700; margin: 0; line-height: 1; }
        .stat-content p { font-size: .77rem; color: #666; margin: 2px 0 0; }
        .section-card { background: linear-gradient(to bottom, var(--card-chocolate-accent) 0%, var(--card-chocolate-accent) 5px, var(--card-chocolate) 5px, var(--card-chocolate) 100%); border-radius: 14px; padding: 20px; margin-bottom: 22px; }
        .section-card h2 { font-size: 1rem; font-weight: 700; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #f0f2f5; }
        .health-card { background: linear-gradient(to bottom, var(--card-yellow-accent) 0%, var(--card-yellow-accent) 5px, var(--card-yellow) 5px, var(--card-yellow) 100%); border-radius: 14px; padding: 20px; margin-bottom: 22px; }
        .health-card h2 { font-size: 1rem; font-weight: 700; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #f0f2f5; }
        .btn-outline-rose { color: #e11d48; border-color: #e11d48; }
        .btn-outline-rose:hover { background: #e11d48; color: #fff; border-color: #e11d48; }
        .btn-outline-teal { color: #0d9488; border-color: #0d9488; }
        .btn-outline-teal:hover { background: #0d9488; color: #fff; border-color: #0d9488; }
        @media print { .sidebar, .top-bar, .no-print { display: none !important; } .page-content { margin-left: 0 !important; padding: 20px !important; } body { background: white !important; } }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-content">
    <div class="top-bar">
        <div>
            <strong><i class="fas fa-hospital-user me-2 text-danger"></i>Sickbay Dashboard</strong>
            <div class="text-muted small">Student Health Services | Iganga School of Nursing &amp; Midwifery</div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small d-none d-md-block"><?= date('D, d M Y') ?></span>
            <a href="../dashboards/inventory-reports.php" class="btn btn-sm btn-outline-info no-print" title="Inventory Reports"><i class="fas fa-boxes me-1"></i></a>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger no-print"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
        </div>
    </div>

    <div class="content-area">
        <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="si si-blue"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-content"><h3><?= number_format($total_students) ?></h3><p>Active Students</p></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="si si-green"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-content"><h3><?= $today_visits ?></h3><p>Today's Visits</p></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="si si-orange"><i class="fas fa-clock"></i></div>
                    <div class="stat-content"><h3><?= $pending_care ?></h3><p>Pending Care</p></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="si si-red"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-content"><h3><?= $critical_cases ?></h3><p>Critical Cases</p></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="si si-teal"><i class="fas fa-heartbeat"></i></div>
                    <div class="stat-content"><h3><?= $recovered_today ?></h3><p>Recovered Today</p></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="si si-purple"><i class="fas fa-user-md"></i></div>
                    <div class="stat-content"><h3><?= $staff_on_duty ?></h3><p>Staff on Duty</p></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="section-card">
                    <h2><i class="fas fa-search me-2"></i>Student Search</h2>
                    <?php include_once __DIR__ . '/../views/student_search_component.php'; ?>
                </div>

                <div class="health-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0"><i class="fas fa-notes-medical me-2 text-danger"></i>Recent Health Records</h2>
                        <span class="badge bg-secondary"><?= $total_visits ?> Total</span>
                    </div>
                    <?php if (empty($recent_records)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-notes-medical fa-3x mb-3"></i>
                        <p>No health records found. Patient visits will appear here.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Complaint</th>
                                    <th>Temperature</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recent_records as $r):
                                $status_class = match($r['status'] ?? '') {
                                    'treated', 'recovered', 'discharged' => 'bg-success',
                                    'pending' => 'bg-warning text-dark',
                                    'critical' => 'bg-danger',
                                    'in_progress', 'under_observation' => 'bg-info text-dark',
                                    default => 'bg-secondary'
                                };
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($r['full_name'] ?? 'Unknown') ?></strong>
                                        <small class="d-block text-muted"><?= htmlspecialchars($r['student_id'] ?? '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars(substr($r['diagnosis'] ?? $r['complaint'] ?? $r['symptoms'] ?? 'N/A', 0, 50)) ?></td>
                                    <td><?= $r['temperature'] ? htmlspecialchars($r['temperature']).'°C' : '—' ?></td>
                                    <td><span class="badge <?= $status_class ?>"><?= htmlspecialchars(ucfirst($r['status'] ?? 'Unknown')) ?></span></td>
                                    <td><small class="text-muted"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="health-card">
                    <h2><i class="fas fa-bolt me-2"></i>Quick Actions</h2>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHealthRecordModal"><i class="fas fa-plus-circle me-1"></i>New Health Record</button>
                        <a href="../student-directory.php" class="btn btn-outline-info btn-sm"><i class="fas fa-address-book me-1"></i>Directory</a>
                        <a href="../store_request.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-shopping-cart me-1"></i>Store Request</a>
                        <a href="../news.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-newspaper me-1"></i>News</a>
                        <a href="student-records.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-users-gear me-1"></i>Students</a>
                        <a href="../dashboards/inventory-reports.php" class="btn btn-outline-teal btn-sm"><i class="fas fa-boxes me-1"></i>Inventory</a>
                        <button class="btn btn-outline-success btn-sm no-print" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
                    </div>
                </div>

                <div class="health-card">
                    <h2><i class="fas fa-history me-2"></i>Recent Activity</h2>
                    <?php if (empty($recent_activities)): ?>
                    <p class="text-muted small">No recent activities.</p>
                    <?php else: ?>
                    <ul class="list-unstyled mb-0">
                    <?php foreach ($recent_activities as $act): ?>
                        <li class="border-bottom py-2 d-flex gap-3 align-items-start">
                            <span class="badge bg-primary mt-1"><?= htmlspecialchars($act['activity_type'] ?? 'Activity') ?></span>
                            <div>
                                <div class="small"><?= htmlspecialchars($act['activity'] ?? $act['activity_description'] ?? '') ?></div>
                                <small class="text-muted"><?= isset($act['created_at']) ? date('d M H:i', strtotime($act['created_at'])) : '' ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <div class="health-card">
                    <h2><i class="fas fa-boxes me-2 text-warning"></i>Sickbay Supplies</h2>
                    <?php
                    $inventory_items = [];
                    try {
                        $inv_r = $staff_conn->query("SELECT item_name, quantity, reorder_level, unit FROM inventory WHERE department = 'Sickbay' ORDER BY quantity ASC LIMIT 8");
                        if ($inv_r) $inventory_items = $inv_r->fetch_all(MYSQLI_ASSOC);
                    } catch (Exception $e) {}
                    ?>
                    <?php if (empty($inventory_items)): ?>
                    <div class="text-center py-3 text-muted small">
                        <i class="fas fa-box-open fa-2x mb-2"></i>
                        <p>No inventory configured for Sickbay.</p>
                        <a href="../dashboards/inventory-reports.php" class="btn btn-sm btn-outline-teal"><i class="fas fa-plus me-1"></i>Add Inventory</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <thead><tr><th>Item</th><th>Qty</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($inventory_items as $item):
                                $low_stock = ($item['quantity'] <= $item['reorder_level']);
                            ?>
                                <tr class="<?= $low_stock ? 'table-danger' : '' ?>">
                                    <td><small><?= htmlspecialchars($item['item_name']) ?></small></td>
                                    <td><small><?= (int)$item['quantity'] ?> <?= htmlspecialchars($item['unit'] ?? '') ?></small></td>
                                    <td><?php if ($low_stock): ?><span class="badge bg-danger">Low</span><?php else: ?><span class="badge bg-success">OK</span><?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-2">
                        <a href="../dashboards/inventory-reports.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-warehouse me-1"></i>Manage Inventory</a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="health-card">
                    <h2><i class="fas fa-link me-2"></i>Quick Links</h2>
                    <div class="list-group list-group-flush">
                        <a href="../student_communication_system.php" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0 py-2 border-0">
                            <i class="fas fa-comments text-primary"></i> Communication
                        </a>
                        <a href="../dashboards/staff_profile_management.php" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0 py-2 border-0">
                            <i class="fas fa-id-card text-info"></i> Staff Profiles
                        </a>
                        <a href="../dashboards/document_management.php" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0 py-2 border-0">
                            <i class="fas fa-folder text-warning"></i> Document Management
                        </a>
                        <a href="../dashboards/staff_receipt_printing.php" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0 py-2 border-0">
                            <i class="fas fa-receipt text-success"></i> Receipt Printing
                        </a>
                        <a href="../quality-assurance/qa-report.php" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0 py-2 border-0">
                            <i class="fas fa-check-circle text-secondary"></i> QA Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Health Record Modal -->
<div class="modal fade" id="addHealthRecordModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="sickbay.php" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-notes-medical me-2"></i>New Health Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_health_record">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Student <span class="text-danger">*</span></label>
                    <input type="text" name="student_name" class="form-control" required placeholder="Student full name or ID">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Temperature (°C)</label>
                        <input type="text" name="temperature" class="form-control" placeholder="e.g. 37.5">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Blood Pressure</label>
                        <input type="text" name="blood_pressure" class="form-control" placeholder="e.g. 120/80">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Complaint / Symptoms</label>
                    <textarea name="symptoms" class="form-control" rows="2" placeholder="Describe symptoms..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Diagnosis</label>
                    <textarea name="diagnosis" class="form-control" rows="2" placeholder="Diagnosis..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Treatment / Prescription</label>
                    <textarea name="treatment" class="form-control" rows="2" placeholder="Treatment given..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="under_observation">Under Observation</option>
                        <option value="treated" selected>Treated</option>
                        <option value="referred">Referred</option>
                        <option value="critical">Critical</option>
                        <option value="discharged">Discharged</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-save me-1"></i>Save Record</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var path = window.location.pathname;
    document.querySelectorAll('.sidebar .nav-link, .dashboard-sidebar .nav-link').forEach(function (link) {
        var href = link.getAttribute('href') || '';
        if (href && path.includes(href.replace(/^.*\//, '').replace('.php', ''))) {
            link.classList.add('active');
        }
    });
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
