<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth-service.php';

$auth_service = new AuthenticationService();
if (!$auth_service->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$staff_conn = getStaffConnection();
if ($staff_conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}
$staff_conn->set_charset('utf8mb4');

$user = $auth_service->getCurrentUser();
$user_department = $user['department'] ?? null;
$user_role = $user['role'] ?? 'General';
$user_id = (int) ($user['id'] ?? 0);

function normalizeRoleDepartment(string $role): string {
    $role = strtolower(trim($role));
    $mapping = [
        'school principal' => 'Administration',
        'principal' => 'Administration',
        'deputy principal' => 'Administration',
        'director general' => 'Administration',
        'ceo' => 'Administration',
        'chief executive officer' => 'Administration',
        'school bursar' => 'Finance',
        'bursar' => 'Finance',
        'director finance' => 'Finance',
        'academic registrar' => 'Academic Records',
        'hr manager' => 'HR',
        'school secretary' => 'Administration',
        'school librarian' => 'Library',
        'director ict' => 'ICT',
        'head of nursing' => 'Nursing',
        'head nursing' => 'Nursing',
        'head of midwifery' => 'Midwifery',
        'head midwifery' => 'Midwifery',
        'senior lecturers' => 'Academic',
        'lecturers' => 'Academic',
        'sickbay' => 'Sickbay',
        'matrons' => 'Student Welfare',
        'wardens' => 'Student Welfare',
        'drivers' => 'Transport',
        'security' => 'Security',
        'storekeeper' => 'Store',
        'non-teaching staff' => 'Support'
    ];
    return $mapping[$role] ?? ucwords($role);
}

$effective_department = $user_department ?: normalizeRoleDepartment($user_role);

function allowedReportRecipients(string $role): array {
    $recipients = [
        'HR Manager',
        'School Principal',
        'Director Academics',
        'Director Finance',
        'Director ICT',
        'Deputy Principal',
        'Director General'
    ];
    if (strtolower($role) === 'hr manager') {
        return ['School Principal', 'Director General', 'Director Finance', 'Director ICT'];
    }
    if (strtolower($role) === 'school principal' || strtolower($role) === 'principal') {
        return ['Director General', 'Director Finance', 'Director ICT'];
    }
    return $recipients;
}

function generateReportNumber(): string {
    return 'REPT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)) . '-' . date('YmdHis');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $item_id = (int) ($input['item_id'] ?? 0);
    $report_type = trim($input['report_type'] ?? 'Request');
    $report_to = trim($input['report_to'] ?? 'HR Manager');
    $report_notes = trim($input['report_notes'] ?? '');

    if ($item_id <= 0 || !$report_to || !$report_type) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid report submission']);
        exit();
    }

    $report_number = generateReportNumber();
    $stmt = $staff_conn->prepare(
        "INSERT INTO inventory_reports (report_number, inventory_id, reported_by, report_to, department, report_type, report_notes, request_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Open')"
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database prepare failed']);
        exit();
    }
    $stmt->bind_param('siissss', $report_number, $item_id, $user_id, $report_to, $effective_department, $report_type, $report_notes);
    $executed = $stmt->execute();
    $stmt->close();

    if ($executed) {
        echo json_encode(['status' => 'success', 'message' => 'Inventory report submitted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not submit inventory report']);
    }
    exit();
}

$stmt = $staff_conn->prepare("SELECT * FROM inventory WHERE department = ? ORDER BY status DESC, quantity_on_hand ASC, item_name ASC");
if (!$stmt) {
    http_response_code(500);
    echo '<div class="alert alert-danger">Inventory query failed.</div>';
    exit();
}
$stmt->bind_param('s', $effective_department);
$stmt->execute();
$result = $stmt->get_result();
$items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

if (count($items) === 0) {
    $stmt = $staff_conn->prepare("SELECT * FROM inventory WHERE department = 'General' OR department = ? ORDER BY status DESC, quantity_on_hand ASC, item_name ASC");
    if ($stmt) {
        $stmt->bind_param('s', $effective_department);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}

$total_items = count($items);
$low_stock_items = 0;
$total_stock_value = 0.0;
foreach ($items as $item) {
    if ((int) $item['quantity_on_hand'] <= (int) $item['reorder_level']) {
        $low_stock_items++;
    }
    $total_stock_value += ((float) $item['quantity_on_hand']) * ((float) ($item['unit_cost'] ?? 0));
}

$stmt = $staff_conn->prepare("SELECT COUNT(*) as count FROM inventory_reports WHERE department = ? AND request_status IN ('Open', 'In Review')");
if ($stmt) {
    $stmt->bind_param('s', $effective_department);
    $stmt->execute();
    $report_count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();
} else {
    $report_count = 0;
}

$recipients = allowedReportRecipients($user_role);
header('Content-Type: text/html; charset=UTF-8');
?>
<section id="departmentInventoryWidget" class="inventory-widget content-section">
    <div class="inventory-panel-header">
        <div>
            <h2>Department Inventory</h2>
            <p class="text-muted">Inventory summary for the <?php echo htmlspecialchars($effective_department); ?> department.</p>
        </div>
        <div class="inventory-summary-badges">
            <span class="inventory-badge primary"><?php echo $total_items; ?> Items</span>
            <span class="inventory-badge warning"><?php echo $low_stock_items; ?> Low Stock</span>
            <span class="inventory-badge info"><?php echo $report_count; ?> Pending Reports</span>
        </div>
    </div>

    <div class="inventory-overview-grid">
        <div class="inventory-card">
            <h4>Total Stock Value</h4>
            <p class="inventory-card-value">UGX <?php echo number_format($total_stock_value, 2); ?></p>
            <p class="text-muted">Real time valuation for department items</p>
        </div>
        <div class="inventory-card">
            <h4>Department</h4>
            <p class="inventory-card-value"><?php echo htmlspecialchars($effective_department); ?></p>
            <p class="text-muted">Assigned reporting to <?php echo implode(', ', $recipients); ?></p>
        </div>
        <div class="inventory-card">
            <h4>Action</h4>
            <p class="inventory-card-value"><?php echo htmlspecialchars($user_role); ?></p>
            <p class="text-muted">Report inventory issues to leadership quickly</p>
        </div>
    </div>

    <div class="inventory-items-table-wrap">
        <table class="table inventory-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Reorder</th>
                    <th>Status</th>
                    <th>Report</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr<?php echo ((int) $item['quantity_on_hand'] <= (int) $item['reorder_level']) ? ' class="low-stock-row"' : ''; ?> data-item-id="<?php echo (int) $item['id']; ?>">
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['item_category']); ?></td>
                        <td><?php echo (int) $item['quantity_on_hand']; ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                        <td><?php echo (int) $item['reorder_level']; ?></td>
                        <td><?php echo htmlspecialchars($item['status']); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-warning report-item-button" data-item-id="<?php echo (int) $item['id']; ?>" data-item-name="<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>">Report</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="inventory-report-form card">
        <div class="card-body">
            <h4 class="card-title">Quick Report</h4>
            <form id="inventoryReportForm">
                <input type="hidden" name="item_id" id="inventoryItemId" value="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="reportType" class="form-label">Report Type</label>
                        <select id="reportType" name="report_type" class="form-select" required>
                            <option value="Low Stock">Low Stock</option>
                            <option value="Damage">Damage</option>
                            <option value="Request">Request</option>
                            <option value="Adjustment">Adjustment</option>
                            <option value="Transfer">Transfer</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="reportTo" class="form-label">Report To</label>
                        <select id="reportTo" name="report_to" class="form-select" required>
                            <?php foreach ($recipients as $recipient): ?>
                                <option value="<?php echo htmlspecialchars($recipient); ?>"><?php echo htmlspecialchars($recipient); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="reportNotes" class="form-label">Notes</label>
                        <input id="reportNotes" name="report_notes" type="text" class="form-control" placeholder="Brief comment or request">
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary">Send Report</button>
                </div>
            </form>
            <div id="inventoryReportMessage" class="mt-3"></div>
        </div>
    </div>
</section>
