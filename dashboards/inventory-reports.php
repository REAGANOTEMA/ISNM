<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['hr manager', 'school principal', 'director general', 'director finance', 'director ict', 'director academics', 'ceo', 'deputy principal']);
$auth_service = $ctx['auth'];
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? '';
$user_role = strtolower($user['role'] ?? '');

$allowed_update_roles = ['hr manager', 'school principal', 'director general', 'director finance', 'director ict'];
$can_update_status = in_array($user_role, $allowed_update_roles, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_update_status) {
    $report_id = isset($_POST['report_id']) ? (int) $_POST['report_id'] : 0;
    $new_status = trim($_POST['new_status'] ?? '');
    $valid_status = ['Open', 'In Review', 'Resolved', 'Closed'];

    if ($report_id > 0 && in_array($new_status, $valid_status, true)) {
        $update_stmt = $conn->prepare("UPDATE inventory_reports SET request_status = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param('si', $new_status, $report_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
}

$department_filter = $user['department'] ?? '';
$show_all = in_array($user_role, ['school principal', 'principal', 'deputy principal', 'director general', 'ceo', 'hr manager', 'director finance', 'director academics', 'director ict'], true);
$effective_department = $show_all ? 'All Departments' : ($department_filter ?: 'General');

if ($show_all) {
    $report_query = "SELECT ir.*, i.item_name, i.item_category, i.location, i.department AS inventory_department FROM inventory_reports ir LEFT JOIN inventory i ON i.id = ir.inventory_id ORDER BY ir.created_at DESC";
    $report_stmt = $conn->prepare($report_query);
} else {
    $report_query = "SELECT ir.*, i.item_name, i.item_category, i.location, i.department AS inventory_department FROM inventory_reports ir LEFT JOIN inventory i ON i.id = ir.inventory_id WHERE ir.department = ? ORDER BY ir.created_at DESC";
    $report_stmt = $conn->prepare($report_query);
    $report_stmt->bind_param('s', $department_filter);
}

$report_stmt->execute();
$report_result = $report_stmt->get_result();
$reports = $report_result ? $report_result->fetch_all(MYSQLI_ASSOC) : [];
$report_stmt->close();

$status_counts = ['Open' => 0, 'In Review' => 0, 'Resolved' => 0, 'Closed' => 0];
foreach ($reports as $report) {
    if (isset($status_counts[$report['request_status']])) {
        $status_counts[$report['request_status']]++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Inventory Reports - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="dashboard-style.css" rel="stylesheet">
    <link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
</head>
<body class="inventory-reports-page">
    <div class="dashboard-container">
        <?php include_once '../includes/sidebar.php'; ?>

        <div class="main-content">
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Inventory Reports</h1>
                    <p>Department-level reporting and resolution tracking for stores, sickbay, food service, and campus support.</p>
                </div>
                <div class="header-right">
                    <div class="d-flex align-items-center gap-2 me-3">
                        <a href="../student-directory.php" class="btn btn-sm btn-outline-info"><i class="fas fa-address-book me-1"></i>Directory</a>
                        <a href="../store_request.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-shopping-cart me-1"></i>Store</a>
                        <a href="../news.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-newspaper me-1"></i>News</a>
                    </div>
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span id="currentDate"></span>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <section class="content-section">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                        <div>
                            <h2>Current Inventory Reports</h2>
                            <p class="text-muted">Showing reports for the <?php echo htmlspecialchars($effective_department); ?> department.</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php foreach ($status_counts as $status => $count): ?>
                                <span class="badge bg-<?php echo $status === 'Open' ? 'danger' : ($status === 'In Review' ? 'warning' : ($status === 'Resolved' ? 'success' : 'secondary')); ?> py-2 px-3">
                                    <?php echo $status; ?>: <?php echo $count; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="inventory-items-table-wrap">
                        <table class="table inventory-table">
                            <thead>
                                <tr>
                                    <th>Report #</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Department</th>
                                    <th>Report To</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <?php if ($can_update_status): ?>
                                        <th>Action</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                    <tr><td colspan="9" class="text-center text-muted">No inventory reports found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($report['report_number']); ?></td>
                                            <td><?php echo htmlspecialchars($report['item_name'] ?: 'Unknown item'); ?></td>
                                            <td><?php echo htmlspecialchars($report['item_category'] ?: 'General'); ?></td>
                                            <td><?php echo htmlspecialchars($report['department']); ?></td>
                                            <td><?php echo htmlspecialchars($report['report_to']); ?></td>
                                            <td><?php echo htmlspecialchars($report['report_type']); ?></td>
                                            <td><?php echo htmlspecialchars($report['request_status']); ?></td>
                                            <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                                            <?php if ($can_update_status): ?>
                                                <td>
                                                    <form method="POST" class="d-flex gap-2 align-items-center">
                                                        <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                                        <select name="new_status" class="form-select form-select-sm">
                                                            <?php foreach (['Open','In Review','Resolved','Closed'] as $status_option): ?>
                                                                <option value="<?php echo $status_option; ?>" <?php echo $report['request_status'] === $status_option ? 'selected' : ''; ?>><?php echo $status_option; ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);
    </script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
