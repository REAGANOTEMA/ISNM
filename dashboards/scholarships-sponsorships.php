<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar', 'finance', 'director', 'registrar', 'secretary']);
$conn = $ctx['staff'];
$studentsConn = $ctx['students'];
$user = $ctx['user'];

$pageTitle = 'Scholarships & Sponsorships';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS `scholarships` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL,
        `description` TEXT, `amount` DECIMAL(12,2) DEFAULT 0,
        `eligibility` TEXT, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS `sponsorships` (
        `id` INT(11) NOT NULL AUTO_INCREMENT, `sponsor_name` VARCHAR(255) NOT NULL,
        `student_id` INT(11) DEFAULT NULL, `amount` DECIMAL(12,2) DEFAULT 0,
        `start_date` DATE, `end_date` DATE, `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
    }
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');

    switch ($action) {

        case 'add_scholarship':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $eligibility = trim($_POST['eligibility'] ?? '');
            $status = trim($_POST['status'] ?? 'Active');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => 'Scholarship name is required.']);
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO scholarships (name, description, amount, eligibility, status) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Database error.']); exit; }
            $stmt->bind_param('ssdss', $name, $description, $amount, $eligibility, $status);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Scholarship added.', 'id' => $stmt->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add scholarship.']);
            }
            $stmt->close();
            exit;

        case 'add_sponsorship':
            $sponsorName = trim($_POST['sponsor_name'] ?? '');
            $studentId = (int)($_POST['student_id'] ?? 0) ?: null;
            $amount = (float)($_POST['amount'] ?? 0);
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;
            $status = trim($_POST['status'] ?? 'Active');
            if ($sponsorName === '') {
                echo json_encode(['success' => false, 'message' => 'Sponsor name is required.']);
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO sponsorships (sponsor_name, student_id, amount, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Database error.']); exit; }
            $stmt->bind_param('sidsss', $sponsorName, $studentId, $amount, $startDate, $endDate, $status);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Sponsorship added.', 'id' => $stmt->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add sponsorship.']);
            }
            $stmt->close();
            exit;

        case 'update_scholarship':
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $eligibility = trim($_POST['eligibility'] ?? '');
            $status = trim($_POST['status'] ?? 'Active');
            if ($id <= 0 || $name === '') {
                echo json_encode(['success' => false, 'message' => 'Invalid data.']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE scholarships SET name=?, description=?, amount=?, eligibility=?, status=? WHERE id=?");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Database error.']); exit; }
            $stmt->bind_param('ssdssi', $name, $description, $amount, $eligibility, $status, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Scholarship updated.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update scholarship.']);
            }
            $stmt->close();
            exit;

        case 'update_sponsorship':
            $id = (int)($_POST['id'] ?? 0);
            $sponsorName = trim($_POST['sponsor_name'] ?? '');
            $studentId = (int)($_POST['student_id'] ?? 0) ?: null;
            $amount = (float)($_POST['amount'] ?? 0);
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;
            $status = trim($_POST['status'] ?? 'Active');
            if ($id <= 0 || $sponsorName === '') {
                echo json_encode(['success' => false, 'message' => 'Invalid data.']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE sponsorships SET sponsor_name=?, student_id=?, amount=?, start_date=?, end_date=?, status=? WHERE id=?");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Database error.']); exit; }
            $stmt->bind_param('sidsssi', $sponsorName, $studentId, $amount, $startDate, $endDate, $status, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Sponsorship updated.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update sponsorship.']);
            }
            $stmt->close();
            exit;

        case 'delete_scholarship':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
            $stmt = $conn->prepare("DELETE FROM scholarships WHERE id=?");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Database error.']); exit; }
            $stmt->bind_param('i', $id);
            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                echo json_encode(['success' => true, 'message' => 'Scholarship deleted.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete scholarship.']);
            }
            $stmt->close();
            exit;

        case 'delete_sponsorship':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
            $stmt = $conn->prepare("DELETE FROM sponsorships WHERE id=?");
            if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Database error.']); exit; }
            $stmt->bind_param('i', $id);
            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                echo json_encode(['success' => true, 'message' => 'Sponsorship deleted.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete sponsorship.']);
            }
            $stmt->close();
            exit;

        case 'search':
            $q = '%' . trim($_POST['query'] ?? '') . '%';
            $type = $_POST['type'] ?? '';
            $results = [];

            if ($type === 'scholarships' || $type === '') {
                $stmt = $conn->prepare("SELECT * FROM scholarships WHERE name LIKE ? OR description LIKE ? OR eligibility LIKE ? ORDER BY created_at DESC LIMIT 50");
                if ($stmt) {
                    $stmt->bind_param('sss', $q, $q, $q);
                    if ($stmt->execute()) {
                        $r = $stmt->get_result();
                        while ($row = $r->fetch_assoc()) $results[] = ['type' => 'scholarship', 'data' => $row];
                    }
                    $stmt->close();
                }
            }

            if ($type === 'sponsorships' || $type === '') {
                $stmt = $conn->prepare("SELECT * FROM sponsorships WHERE sponsor_name LIKE ? ORDER BY created_at DESC LIMIT 50");
                if ($stmt) {
                    $stmt->bind_param('s', $q);
                    if ($stmt->execute()) {
                        $r = $stmt->get_result();
                        while ($row = $r->fetch_assoc()) $results[] = ['type' => 'sponsorship', 'data' => $row];
                    }
                    $stmt->close();
                }
            }

            echo json_encode(['success' => true, 'results' => $results]);
            exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

$scholarships = [];
foreach ([$conn, $studentsConn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM scholarships ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $scholarships[] = $row; break; }
}

$sponsorships = [];
foreach ([$conn, $studentsConn] as $db) {
    if (!$db) continue;
    $r = $db->query("SELECT * FROM sponsorships ORDER BY created_at DESC LIMIT 100");
    if ($r && $r->num_rows) { while ($row = $r->fetch_assoc()) $sponsorships[] = $row; break; }
}

$totalScholarships = count($scholarships);
$totalSponsorships = count($sponsorships);
$activeScholarships = count(array_filter($scholarships, fn($s) => ($s['status'] ?? '') === 'active' || ($s['status'] ?? '') === 'Active'));
$activeSponsorships = count(array_filter($sponsorships, fn($s) => ($s['status'] ?? '') === 'active' || ($s['status'] ?? '') === 'Active'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
    <div class="content-header">
        <h1><i class="fas fa-trophy"></i> Scholarships & Sponsorships</h1>
        <div class="float-end d-flex gap-2">
            <div class="input-group" style="width:260px">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search scholarships/sponsorships...">
                <button class="btn btn-sm btn-outline-secondary" onclick="performSearch()"><i class="fas fa-search"></i></button>
            </div>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Scholarships</h6><h3 id="totalScholarships"><?= $totalScholarships ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active Scholarships</h6><h3 id="activeScholarships"><?= $activeScholarships ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Sponsorships</h6><h3 id="totalSponsorships"><?= $totalSponsorships ?></h3></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><h6>Active Sponsorships</h6><h3 id="activeSponsorships"><?= $activeSponsorships ?></h3></div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Scholarships</h5>
                    <button class="btn btn-sm btn-primary" onclick="openScholarshipModal()"><i class="fas fa-plus me-1"></i> Add</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="scholarshipsTable">
                            <thead><tr><th>Name</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($scholarships as $s): ?>
                                <tr data-id="<?= (int)($s['id'] ?? 0) ?>"
                                    data-name="<?= htmlspecialchars($s['name'] ?? '') ?>"
                                    data-description="<?= htmlspecialchars($s['description'] ?? '') ?>"
                                    data-amount="<?= htmlspecialchars($s['amount'] ?? 0) ?>"
                                    data-eligibility="<?= htmlspecialchars($s['eligibility'] ?? '') ?>"
                                    data-status="<?= htmlspecialchars($s['status'] ?? 'Active') ?>">
                                    <td><?= htmlspecialchars($s['name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['amount'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= (strtolower($s['status'] ?? 'active') === 'active') ? 'success' : 'secondary' ?>"><?= htmlspecialchars($s['status'] ?? 'Active') ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editScholarship(this)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteScholarship(this)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($scholarships)): ?><tr id="scholarshipsEmpty"><td colspan="4" class="text-center">No scholarships found</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sponsorships</h5>
                    <button class="btn btn-sm btn-primary" onclick="openSponsorshipModal()"><i class="fas fa-plus me-1"></i> Add</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="sponsorshipsTable">
                            <thead><tr><th>Sponsor</th><th>Student ID</th><th>Amount</th><th>Period</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($sponsorships as $s): ?>
                                <tr data-id="<?= (int)($s['id'] ?? 0) ?>"
                                    data-sponsor_name="<?= htmlspecialchars($s['sponsor_name'] ?? '') ?>"
                                    data-student_id="<?= htmlspecialchars($s['student_id'] ?? '') ?>"
                                    data-amount="<?= htmlspecialchars($s['amount'] ?? 0) ?>"
                                    data-start_date="<?= htmlspecialchars($s['start_date'] ?? '') ?>"
                                    data-end_date="<?= htmlspecialchars($s['end_date'] ?? '') ?>"
                                    data-status="<?= htmlspecialchars($s['status'] ?? 'Active') ?>">
                                    <td><?= htmlspecialchars($s['sponsor_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['student_id'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($s['amount'] ?? '-') ?></td>
                                    <td><small><?= htmlspecialchars($s['start_date'] ?? '?') ?> &mdash; <?= htmlspecialchars($s['end_date'] ?? '?') ?></small></td>
                                    <td><span class="badge bg-<?= (strtolower($s['status'] ?? 'active') === 'active') ? 'success' : 'secondary' ?>"><?= htmlspecialchars($s['status'] ?? 'Active') ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editSponsorship(this)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSponsorship(this)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sponsorships)): ?><tr id="sponsorshipsEmpty"><td colspan="6" class="text-center">No sponsorships found</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scholarship Modal -->
<div class="modal fade" id="scholarshipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scholarshipModalTitle"><i class="fas fa-graduation-cap me-2"></i>Add Scholarship</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scholarshipForm" onsubmit="return saveScholarship()">
                <input type="hidden" name="action" value="add_scholarship">
                <input type="hidden" name="id" id="scholarshipId">
                <div class="modal-body">
                    <div id="scholarshipAlert" class="alert d-none"></div>
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="scholarshipName" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="scholarshipDescription" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" class="form-control" name="amount" id="scholarshipAmount" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="scholarshipStatus">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Eligibility</label>
                        <textarea class="form-control" name="eligibility" id="scholarshipEligibility" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="scholarshipSubmit"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sponsorship Modal -->
<div class="modal fade" id="sponsorshipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sponsorshipModalTitle"><i class="fas fa-hand-holding-heart me-2"></i>Add Sponsorship</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="sponsorshipForm" onsubmit="return saveSponsorship()">
                <input type="hidden" name="action" value="add_sponsorship">
                <input type="hidden" name="id" id="sponsorshipId">
                <div class="modal-body">
                    <div id="sponsorshipAlert" class="alert d-none"></div>
                    <div class="mb-3">
                        <label class="form-label">Sponsor Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="sponsor_name" id="sponsorshipSponsorName" required maxlength="255">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="number" class="form-control" name="student_id" id="sponsorshipStudentId" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" class="form-control" name="amount" id="sponsorshipAmount" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="sponsorshipStartDate">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="sponsorshipEndDate">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="sponsorshipStatus">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="sponsorshipSubmit"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
(function(){
    var CSRF = window.CSRF_TOKEN || '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
    var AJAX_BASE = window.AJAX_BASE || '..';

    function post(data, cb) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'scholarships-sponsorships.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-CSRF-Token', CSRF);
        data.csrf_token = CSRF;
        var pairs = [];
        for (var k in data) { if (data.hasOwnProperty(k)) pairs.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k])); }
        xhr.onload = function() {
            try {
                var json = JSON.parse(xhr.responseText.replace(/^\uFEFF/, ''));
                cb(json);
            } catch(e) { cb({ success: false, message: 'Invalid response.' }); }
        };
        xhr.onerror = function() { cb({ success: false, message: 'Network error.' }); };
        xhr.send(pairs.join('&'));
    }

    function showAlert(el, success, msg) {
        el.className = 'alert ' + (success ? 'alert-success' : 'alert-danger');
        el.innerHTML = '<i class="fas fa-' + (success ? 'check-circle' : 'exclamation-circle') + ' me-1"></i> ' + escHtml(msg);
        el.classList.remove('d-none');
    }
    function escHtml(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    /* ── Scholarships ── */
    window.openScholarshipModal = function() {
        document.getElementById('scholarshipForm').reset();
        document.getElementById('scholarshipId').value = '';
        document.getElementById('scholarshipModalTitle').innerHTML = '<i class="fas fa-graduation-cap me-2"></i>Add Scholarship';
        document.getElementById('scholarshipForm').querySelector('[name=action]').value = 'add_scholarship';
        document.getElementById('scholarshipSubmit').innerHTML = '<i class="fas fa-save me-1"></i> Save';
        document.getElementById('scholarshipAlert').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('scholarshipModal')).show();
    };

    window.editScholarship = function(btn) {
        var tr = btn.closest('tr');
        document.getElementById('scholarshipId').value = tr.dataset.id;
        document.getElementById('scholarshipName').value = tr.dataset.name || '';
        document.getElementById('scholarshipDescription').value = tr.dataset.description || '';
        document.getElementById('scholarshipAmount').value = tr.dataset.amount || 0;
        document.getElementById('scholarshipEligibility').value = tr.dataset.eligibility || '';
        document.getElementById('scholarshipStatus').value = tr.dataset.status || 'Active';
        document.getElementById('scholarshipModalTitle').innerHTML = '<i class="fas fa-graduation-cap me-2"></i>Edit Scholarship';
        document.getElementById('scholarshipForm').querySelector('[name=action]').value = 'update_scholarship';
        document.getElementById('scholarshipSubmit').innerHTML = '<i class="fas fa-save me-1"></i> Update';
        document.getElementById('scholarshipAlert').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('scholarshipModal')).show();
    };

    window.saveScholarship = function() {
        var f = document.getElementById('scholarshipForm');
        var d = new FormData(f);
        var a = document.getElementById('scholarshipAlert');
        var b = document.getElementById('scholarshipSubmit');
        a.classList.add('d-none');
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
        post(Object.fromEntries(d), function(res) {
            b.disabled = false;
            b.innerHTML = '<i class="fas fa-save me-1"></i> ' + (d.get('action') === 'add_scholarship' ? 'Save' : 'Update');
            if (res.success) {
                showAlert(a, true, res.message);
                setTimeout(function(){ location.reload(); }, 800);
            } else {
                showAlert(a, false, res.message);
            }
        });
        return false;
    };

    window.deleteScholarship = function(btn) {
        if (!confirm('Are you sure you want to delete this scholarship?')) return;
        var tr = btn.closest('tr');
        var id = tr.dataset.id;
        var a = document.getElementById('scholarshipAlert');
        a.classList.add('d-none');
        post({ action: 'delete_scholarship', id: id }, function(res) {
            if (res.success) {
                tr.style.transition = 'opacity 0.3s';
                tr.style.opacity = '0';
                setTimeout(function() { tr.remove(); }, 300);
            } else {
                alert(res.message || 'Failed to delete.');
            }
        });
    };

    /* ── Sponsorships ── */
    window.openSponsorshipModal = function() {
        document.getElementById('sponsorshipForm').reset();
        document.getElementById('sponsorshipId').value = '';
        document.getElementById('sponsorshipModalTitle').innerHTML = '<i class="fas fa-hand-holding-heart me-2"></i>Add Sponsorship';
        document.getElementById('sponsorshipForm').querySelector('[name=action]').value = 'add_sponsorship';
        document.getElementById('sponsorshipSubmit').innerHTML = '<i class="fas fa-save me-1"></i> Save';
        document.getElementById('sponsorshipAlert').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('sponsorshipModal')).show();
    };

    window.editSponsorship = function(btn) {
        var tr = btn.closest('tr');
        document.getElementById('sponsorshipId').value = tr.dataset.id;
        document.getElementById('sponsorshipSponsorName').value = tr.dataset.sponsor_name || '';
        document.getElementById('sponsorshipStudentId').value = tr.dataset.student_id || '';
        document.getElementById('sponsorshipAmount').value = tr.dataset.amount || 0;
        document.getElementById('sponsorshipStartDate').value = tr.dataset.start_date || '';
        document.getElementById('sponsorshipEndDate').value = tr.dataset.end_date || '';
        document.getElementById('sponsorshipStatus').value = tr.dataset.status || 'Active';
        document.getElementById('sponsorshipModalTitle').innerHTML = '<i class="fas fa-hand-holding-heart me-2"></i>Edit Sponsorship';
        document.getElementById('sponsorshipForm').querySelector('[name=action]').value = 'update_sponsorship';
        document.getElementById('sponsorshipSubmit').innerHTML = '<i class="fas fa-save me-1"></i> Update';
        document.getElementById('sponsorshipAlert').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('sponsorshipModal')).show();
    };

    window.saveSponsorship = function() {
        var f = document.getElementById('sponsorshipForm');
        var d = new FormData(f);
        var a = document.getElementById('sponsorshipAlert');
        var b = document.getElementById('sponsorshipSubmit');
        a.classList.add('d-none');
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
        post(Object.fromEntries(d), function(res) {
            b.disabled = false;
            b.innerHTML = '<i class="fas fa-save me-1"></i> ' + (d.get('action') === 'add_sponsorship' ? 'Save' : 'Update');
            if (res.success) {
                showAlert(a, true, res.message);
                setTimeout(function(){ location.reload(); }, 800);
            } else {
                showAlert(a, false, res.message);
            }
        });
        return false;
    };

    window.deleteSponsorship = function(btn) {
        if (!confirm('Are you sure you want to delete this sponsorship?')) return;
        var tr = btn.closest('tr');
        var id = tr.dataset.id;
        post({ action: 'delete_sponsorship', id: id }, function(res) {
            if (res.success) {
                tr.style.transition = 'opacity 0.3s';
                tr.style.opacity = '0';
                setTimeout(function() { tr.remove(); }, 300);
            } else {
                alert(res.message || 'Failed to delete.');
            }
        });
    };

    /* ── Search ── */
    window.performSearch = function() {
        var q = document.getElementById('searchInput').value.trim();
        if (!q) { location.reload(); return; }
        post({ action: 'search', query: q }, function(res) {
            if (!res.success) return;
            var scholarships = [], sponsorships = [];
            (res.results || []).forEach(function(r) {
                if (r.type === 'scholarship') scholarships.push(r.data);
                else sponsorships.push(r.data);
            });
            renderScholarships(scholarships);
            renderSponsorships(sponsorships);
        });
    };

    document.getElementById('searchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); performSearch(); }
    });

    function renderScholarships(list) {
        var tbody = document.querySelector('#scholarshipsTable tbody');
        tbody.innerHTML = '';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No scholarships found</td></tr>';
            return;
        }
        list.forEach(function(s) {
            var tr = document.createElement('tr');
            tr.dataset.id = s.id || 0;
            tr.dataset.name = s.name || '';
            tr.dataset.description = s.description || '';
            tr.dataset.amount = s.amount || 0;
            tr.dataset.eligibility = s.eligibility || '';
            tr.dataset.status = s.status || 'Active';
            var statusClass = (s.status || '').toLowerCase() === 'active' ? 'success' : 'secondary';
            tr.innerHTML =
                '<td>' + escHtml(s.name || '-') + '</td>' +
                '<td>' + escHtml(s.amount || '-') + '</td>' +
                '<td><span class="badge bg-' + statusClass + '">' + escHtml(s.status || 'Active') + '</span></td>' +
                '<td><button class="btn btn-sm btn-outline-primary me-1" onclick="editScholarship(this)"><i class="fas fa-edit"></i></button>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="deleteScholarship(this)"><i class="fas fa-trash"></i></button></td>';
            tbody.appendChild(tr);
        });
    }

    function renderSponsorships(list) {
        var tbody = document.querySelector('#sponsorshipsTable tbody');
        tbody.innerHTML = '';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No sponsorships found</td></tr>';
            return;
        }
        list.forEach(function(s) {
            var tr = document.createElement('tr');
            tr.dataset.id = s.id || 0;
            tr.dataset.sponsor_name = s.sponsor_name || '';
            tr.dataset.student_id = s.student_id || '';
            tr.dataset.amount = s.amount || 0;
            tr.dataset.start_date = s.start_date || '';
            tr.dataset.end_date = s.end_date || '';
            tr.dataset.status = s.status || 'Active';
            var statusClass = (s.status || '').toLowerCase() === 'active' ? 'success' : 'secondary';
            tr.innerHTML =
                '<td>' + escHtml(s.sponsor_name || '-') + '</td>' +
                '<td>' + escHtml(s.student_id || '-') + '</td>' +
                '<td>' + escHtml(s.amount || '-') + '</td>' +
                '<td><small>' + escHtml(s.start_date || '?') + ' &mdash; ' + escHtml(s.end_date || '?') + '</small></td>' +
                '<td><span class="badge bg-' + statusClass + '">' + escHtml(s.status || 'Active') + '</span></td>' +
                '<td><button class="btn btn-sm btn-outline-primary me-1" onclick="editSponsorship(this)"><i class="fas fa-edit"></i></button>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="deleteSponsorship(this)"><i class="fas fa-trash"></i></button></td>';
            tbody.appendChild(tr);
        });
    }
})();
</script>
</body>
</html>
