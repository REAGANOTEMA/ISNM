<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['security', 'director', 'manager']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$pageTitle = 'Visitor & Access Control';

// ── POST handler ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    $csrf   = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!empty($csrf) && hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        try {
            if ($action === 'check_in') {
                $name   = trim($_POST['visitor_name'] ?? '');
                $phone  = trim($_POST['visitor_phone'] ?? '');
                $idNum  = trim($_POST['visitor_id_number'] ?? '');
                $purpose = trim($_POST['purpose'] ?? '');
                $host   = trim($_POST['person_to_visit'] ?? '');
                if ($name === '' || $purpose === '') {
                    $_SESSION['error'] = 'Visitor name and purpose are required.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO visitor_logs (visitor_name, visitor_phone, visitor_id_number, purpose, person_visiting, check_in_time, status) VALUES (?, ?, ?, ?, ?, NOW(), 'Checked In')");
                    if ($stmt) {
                        $stmt->bind_param('sssss', $name, $phone, $idNum, $purpose, $host);
                        if ($stmt->execute()) {
                            $_SESSION['success'] = "$name checked in successfully.";
                        } else {
                            $_SESSION['error'] = 'Failed to check in visitor.';
                        }
                        $stmt->close();
                    }
                }
            } elseif ($action === 'check_out') {
                $id = (int) ($_POST['visitor_id'] ?? 0);
                if ($id > 0) {
                    $stmt = $conn->prepare("UPDATE visitor_logs SET check_out_time=NOW(), status='Checked Out' WHERE id=?");
                    if ($stmt) {
                        $stmt->bind_param('i', $id);
                        if ($stmt->execute()) {
                            $_SESSION['success'] = 'Visitor checked out successfully.';
                        } else {
                            $_SESSION['error'] = 'Failed to check out visitor.';
                        }
                        $stmt->close();
                    }
                } else {
                    $_SESSION['error'] = 'Invalid visitor record.';
                }
            } elseif ($action === 'add_access_log') {
                $location    = trim($_POST['location'] ?? '');
                $accessType  = trim($_POST['access_type'] ?? '');
                $personName  = trim($_POST['person_name'] ?? '');
                $status      = trim($_POST['status'] ?? 'granted');
                $notes       = trim($_POST['notes'] ?? '');
                if ($location === '' || $personName === '') {
                    $_SESSION['error'] = 'Location and person name are required.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO access_control_logs (access_point, access_type, person_name, status, notes, access_time) VALUES (?, ?, ?, ?, ?, NOW())");
                    if ($stmt) {
                        $stmt->bind_param('sssss', $location, $accessType, $personName, $status, $notes);
                        if ($stmt->execute()) {
                            $_SESSION['success'] = 'Access log entry added.';
                        } else {
                            $_SESSION['error'] = 'Failed to add access log.';
                        }
                        $stmt->close();
                    }
                }
            }
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'An error occurred.';
            error_log('visitor-access POST: ' . $e->getMessage());
        }
    } else {
        $_SESSION['error'] = 'Invalid security token. Please refresh and try again.';
    }
    // Redirect back to avoid re-POST on refresh
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect");
    exit;
}

// ── Data ───────────────────────────────────────────────────────────
$visitors = []; $accessLogs = [];
if ($conn) {
    $r = $conn->query("SELECT * FROM visitor_logs ORDER BY check_in_time DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $visitors[] = $row;
    $r2 = $conn->query("SELECT * FROM access_control_logs ORDER BY access_time DESC LIMIT 100");
    if ($r2) while ($row = $r2->fetch_assoc()) $accessLogs[] = $row;
}
$loggedIn = count(array_filter($visitors, fn($v) => !($v['check_out_time'] ?? '')));
$today = count(array_filter($visitors, fn($v) => substr($v['check_in_time'] ?? '', 0, 10) === date('Y-m-d')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.btn-action{min-width:90px;margin:2px}
.modal .form-label{font-weight:500;font-size:.875rem}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-shield-alt"></i> Visitor & Access Control</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#checkInModal"><i class="fas fa-sign-in-alt"></i> Check In</button>
            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#accessLogModal"><i class="fas fa-door-open"></i> Add Access Log</button>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <?= function_exists('renderFlashMessages') ? renderFlashMessages() : '' ?>
    <?php
    // Fallback flash rendering if dashboard_components not loaded
    if (!empty($_SESSION['success'])) { echo '<div class="alert alert-success alert-dismissible fade show py-2">' . htmlspecialchars($_SESSION['success']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['success']); }
    if (!empty($_SESSION['error']))   { echo '<div class="alert alert-danger alert-dismissible fade show py-2">'   . htmlspecialchars($_SESSION['error'])   . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; unset($_SESSION['error']); }
    ?>

    <div class="row mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total Visitors</h6><h3><?= count($visitors) ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Currently Inside</h6><h3><?= $loggedIn ?></h3></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Today's Visitors</h6><h3><?= $today ?></h3></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Visitor Logs</h5>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#checkInModal"><i class="fas fa-plus"></i> Check In</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Name</th><th>Purpose</th><th>Check In</th><th>Check Out</th><th>Host</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($visitors as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v['visitor_name'] ?? $v['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($v['purpose'] ?? $v['reason'] ?? '-') ?></td>
                                    <td><?= ($v['check_in_time'] ?? '') ? date('d M Y g:i A', strtotime($v['check_in_time'])) : '-' ?></td>
                                    <td><?= $v['check_out_time'] ? date('d M Y g:i A', strtotime($v['check_out_time'])) : '<span class="badge bg-success">Inside</span>' ?></td>
                                    <td><?= htmlspecialchars($v['person_visiting'] ?? $v['host'] ?? '-') ?></td>
                                    <td>
                                        <?php if (empty($v['check_out_time'])): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Check out <?= htmlspecialchars($v['visitor_name'] ?? $v['name'] ?? 'this visitor') ?>?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="action" value="check_out">
                                            <input type="hidden" name="visitor_id" value="<?= $v['id'] ?>">
                                            <button class="btn btn-warning btn-sm btn-action"><i class="fas fa-sign-out-alt"></i> Check Out</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-muted small">Checked Out</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($visitors)): ?><tr><td colspan="6" class="text-center">No visitor logs</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Access Control Logs</h5>
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#accessLogModal"><i class="fas fa-plus"></i> Add Log</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>User</th><th>Action</th><th>Location</th><th>Time</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($accessLogs as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['person_name'] ?? $a['user'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['access_type'] ?? $a['event'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['access_point'] ?? $a['area'] ?? '-') ?></td>
                                    <td><?= $a['access_time'] ? date('d M Y g:i A', strtotime($a['access_time'])) : ($a['created_at'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= ($a['status'] ?? 'granted') === 'granted' ? 'success' : 'danger' ?>"><?= $a['status'] ?? 'granted' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($accessLogs)): ?><tr><td colspan="5" class="text-center">No access logs</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Check-In Modal ────────────────────────────────────────────── -->
<div class="modal fade" id="checkInModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-sign-in-alt me-2"></i>Visitor Check-In</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="check_in">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Visitor Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="visitor_name" required placeholder="Full name">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control" name="visitor_phone" placeholder="e.g. 0772xxxxxx">
          </div>
          <div class="mb-3">
            <label class="form-label">ID Number</label>
            <input type="text" class="form-control" name="visitor_id_number" placeholder="National ID / Passport">
          </div>
          <div class="mb-3">
            <label class="form-label">Purpose <span class="text-danger">*</span></label>
            <select class="form-select" name="purpose" required>
              <option value="">-- Select Purpose --</option>
              <option value="Official Business">Official Business</option>
              <option value="Meeting">Meeting</option>
              <option value="Delivery">Delivery</option>
              <option value="Student Pickup">Student Pickup</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Person to Visit / Host</label>
            <input type="text" class="form-control" name="person_to_visit" placeholder="Staff member name">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Check In</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Access Log Modal ──────────────────────────────────────────── -->
<div class="modal fade" id="accessLogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-door-open me-2"></i>Add Access Log Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="add_access_log">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Door / Location <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="location" required placeholder="e.g. Main Gate, Building A">
          </div>
          <div class="mb-3">
            <label class="form-label">Access Type <span class="text-danger">*</span></label>
            <select class="form-select" name="access_type" required>
              <option value="Entry">Entry</option>
              <option value="Exit">Exit</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Person Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="person_name" required placeholder="Visitor or staff name">
          </div>
          <div class="mb-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select" name="status" required>
              <option value="granted">Granted</option>
              <option value="denied">Denied</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Optional remarks..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info"><i class="fas fa-save"></i> Save Log</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
