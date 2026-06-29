<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';

session_start();

$auth = new AuthenticationService();
if (!$auth->isAuthenticated() || ($_SESSION['type'] ?? '') !== 'staff') {
    header('Location: organogram.php');
    exit();
}

$user = $auth->getCurrentUser();
$staffConn = getStaffConnection();

// Handle form submission
$success = ''; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'submit_request') {
        $items = $_POST['items'] ?? [];
        $notes = trim($_POST['notes'] ?? '');
        $urgency = $_POST['urgency'] ?? 'medium';
        $validItems = [];

        foreach ($items as $idx => $item) {
            $itemId = (int)($item['item_id'] ?? 0);
            $qty = (float)($item['quantity'] ?? 0);
            if ($itemId > 0 && $qty > 0) {
                // Verify item exists and is active
                $check = $staffConn->query("SELECT id FROM store_inventory WHERE id=" . intval($itemId) . " AND status='active'");
                if ($check && $check->num_rows > 0) {
                    $validItems[] = ['item_id' => $itemId, 'quantity' => $qty, 'notes' => substr(trim($item['notes'] ?? ''), 0, 255)];
                }
            }
        }

        if (empty($validItems)) {
            $errors[] = 'Please select at least one item with a valid quantity.';
        } else {
            $reqNum = 'SRQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $userId = (int)$_SESSION['user_id'];
            $dept = $_POST['department'] ?? $user['department'] ?? '';

            $submitDg = !empty($_POST['submit_for_dg']);
            $status = $submitDg ? 'pending_approval' : 'pending';
            $stmt = $staffConn->prepare("INSERT INTO store_requests (request_number, requested_by, department, notes, urgency, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissss", $reqNum, $userId, $dept, $notes, $urgency, $status);
            if ($stmt->execute()) {
                $requestId = $stmt->insert_id;
                $stmt->close();

                $ins = $staffConn->prepare("INSERT INTO store_request_items (request_id, item_id, quantity_requested, notes) VALUES (?, ?, ?, ?)");
                foreach ($validItems as $vi) {
                    $ins->bind_param("iids", $requestId, $vi['item_id'], $vi['quantity'], $vi['notes']);
                    $ins->execute();
                }
                $ins->close();

                // If submitting directly for DG approval, create the approval workflow record
                if ($submitDg) {
                    require_once __DIR__ . '/includes/approval_integration.php';
                    if (submitStoreForApproval($requestId, $staffConn)) {
                        $success = "Request <strong>$reqNum</strong> submitted for <strong>Director General Approval</strong>! Track its status in the Approval Center.";
                    } else {
                        $success = "Request <strong>$reqNum</strong> saved. Submitting for DG approval failed — the storekeeper can forward it manually.";
                    }
                } else {
                    $success = "Request <strong>$reqNum</strong> submitted successfully! The storekeeper will process it.";
                }
            } else {
                $errors[] = 'Database error: ' . $stmt->error;
            }
        }
    }
}

// Get categories and items for the request form
$categories = [];
$r = $staffConn->query("SELECT id, category_name, icon FROM store_categories WHERE status='active' ORDER BY category_name");
if ($r) while ($row = $r->fetch_assoc()) $categories[] = $row;

$allItems = [];
$r = $staffConn->query("SELECT id, category_id, item_name, unit, quantity FROM store_inventory WHERE status='active' ORDER BY item_name");
if ($r) while ($row = $r->fetch_assoc()) $allItems[] = $row;

// Group items by category
$itemsByCat = [];
foreach ($allItems as $item) {
    $itemsByCat[$item['category_id']][] = $item;
}

// Get user's recent requests
$myRequests = [];
$uid = (int)$_SESSION['user_id'];
$r = $staffConn->query("SELECT sr.*, COUNT(sri.id) as total_items FROM store_requests sr LEFT JOIN store_request_items sri ON sr.id=sri.request_id WHERE sr.requested_by=" . intval($uid) . " GROUP BY sr.id ORDER BY sr.created_at DESC LIMIT 10");
if ($r) while ($row = $r->fetch_assoc()) $myRequests[] = $row;

$userName = $user['full_name'] ?? 'Staff';
$userRole = $user['role'] ?? '';
$userDept = $user['department'] ?? '';
$viewTab = $_GET['view'] ?? 'new';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Store Request | ISNM</title>
<?php include_once __DIR__ . '/includes/_favicon.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
:root {
    --primary: #1a237e; --primary-lt: #3949ab; --accent: #ffd700;
    --success: #2e7d32; --danger: #c62828; --warning: #f57f17;
}
body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; min-height:100vh; display:flex; flex-direction:column; }
.navbar { background:linear-gradient(135deg,var(--primary),var(--primary-lt)); }
.navbar-brand, .navbar .nav-link { color:#fff !important; }
.navbar .nav-link:hover { color:var(--accent) !important; }
.page-header { background:linear-gradient(135deg,var(--primary),#283593); color:#fff; padding:30px 0 25px; text-align:center; }
.page-header h1 { font-weight:800; font-size:1.8rem; }
.page-header p { color:rgba(255,255,255,.8); }
.card { border-radius:14px; border:none; box-shadow:0 2px 12px rgba(0,0,0,.07); }
.card-header { border-radius:14px 14px 0 0 !important; font-weight:700; }
.item-row { background:#f8f9fa; border-radius:8px; padding:10px 12px; margin-bottom:6px; transition:background .2s; }
.item-row:hover { background:#e8eaf6; }
.item-row .item-name { font-weight:600; font-size:.9rem; }
.item-row .item-unit { color:#888; font-size:.8rem; }
.cat-label { font-weight:700; color:var(--primary); font-size:.85rem; padding:8px 0 4px; border-bottom:1px solid #e0e0e0; margin-bottom:6px; }
.item-select { font-size:.85rem; }
.qty-input { width:80px; font-size:.85rem; text-align:center; }
.delete-item { color:var(--danger); cursor:pointer; }
.badge-urgency-low { background:#e8f5e9; color:#2e7d32; }
.badge-urgency-medium { background:#fff3e0; color:#e65100; }
.badge-urgency-high { background:#ffebee; color:#c62828; }
.badge-urgency-urgent { background:#fce4ec; color:#b71c1c; animation:pulse 1.5s infinite; }
@keyframes pulse { 0%{opacity:1} 50%{opacity:.6} 100%{opacity:1} }
.stat-card { padding:15px; text-align:center; }
.stat-card .num { font-size:1.8rem; font-weight:800; color:var(--primary); }
.stat-card .label { font-size:.8rem; color:#888; }
footer { background:var(--primary); color:rgba(255,255,255,.8); text-align:center; padding:15px; margin-top:auto; font-size:.85rem; }
footer a { color:var(--accent); text-decoration:none; }
@media(max-width:768px) {
    .page-header h1 { font-size:1.3rem; }
    .qty-input { width:65px; }
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-store me-2"></i>ISNM Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navStore">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navStore">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="store_request.php"><i class="fas fa-clipboard-list me-1"></i>New Request</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars(explode(' ', $userName)[0]) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="store_request.php"><i class="fas fa-clipboard-list me-2"></i>Store Request</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-clipboard-list me-2"></i>Store Requisition</h1>
        <p>Request items from the institutional store</p>
    </div>
</div>

<div class="container py-4">
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($e) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endforeach; ?>

    <!-- Tab Navigation -->
    <div class="d-flex gap-2 mb-3">
        <a href="store_request.php?view=new" class="btn btn-sm <?= $viewTab === 'new' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fas fa-plus-circle me-1"></i>New Request</a>
        <a href="store_request.php?view=my_requests" class="btn btn-sm <?= $viewTab === 'my_requests' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fas fa-list me-1"></i>My Requests (<?= count($myRequests) ?>)</a>
    </div>

    <?php if ($viewTab === 'my_requests'): ?>
    <!-- === MY REQUESTS FULL VIEW === -->
    <div class="card">
        <div class="card-header bg-secondary text-white"><i class="fas fa-history me-2"></i>All My Store Requests</div>
        <div class="card-body p-0">
            <?php if (empty($myRequests)): ?>
            <div class="p-4 text-center text-muted"><i class="fas fa-inbox fa-2x mb-2"></i><p>No requests submitted yet.</p><a href="store_request.php" class="btn btn-primary btn-sm">Submit Your First Request</a></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Request No.</th><th>Items</th><th>Urgency</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($myRequests as $req):
                            $ubadge = $req['urgency']==='urgent'?'bg-danger':($req['urgency']==='high'?'bg-warning text-dark':($req['urgency']==='medium'?'bg-info':'bg-secondary'));
                            $status = $req['status'];
                            $sbadge = $status==='fulfilled'?'bg-success':($status==='approved'?'bg-success':($status==='pending_approval'?'bg-warning text-dark':($status==='pending'?'bg-warning text-dark':($status==='rejected'?'bg-danger':'bg-info'))));
                            $statusLabel = $status==='pending_approval'?'Pending DG Approval':($status==='pending'?'Pending':($status==='approved'?'Approved':$status));
                        ?>
                        <tr>
                            <td><?= $req['id'] ?></td>
                            <td><code><?= htmlspecialchars($req['request_number']) ?></code></td>
                            <td><?= $req['total_items'] ?></td>
                            <td><span class="badge <?= $ubadge ?>"><?= $req['urgency'] ?></span></td>
                            <td><span class="badge <?= $sbadge ?>"><?= ucfirst($statusLabel) ?></span></td>
                            <td><small><?= date('d M Y H:i', strtotime($req['created_at'])) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-center p-2">
            <a href="store_request.php" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>New Request</a>
            <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-home me-1"></i>Home</a>
        </div>
    </div>
    <?php else: ?>
    <!-- === NEW REQUEST FORM === -->
    <div class="row g-4">
        <!-- Request Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-plus-circle me-2"></i>New Store Request
                </div>
                <div class="card-body">
                    <form method="POST" id="requestForm">
                        <input type="hidden" name="action" value="submit_request">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Department</label>
                                <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($userDept) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Urgency</label>
                                <select name="urgency" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Date</label>
                                <input type="text" class="form-control" value="<?= date('d M Y') ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Items</label>
                            <div class="mb-2">
                                <input type="text" id="itemFilter" class="form-control form-control-sm" placeholder="Search items...">
                            </div>
                            <div id="itemsContainer" style="max-height:400px;overflow-y:auto">
                            <?php foreach ($categories as $cat):
                                $catItems = $itemsByCat[$cat['id']] ?? [];
                                if (empty($catItems)) continue;
                            ?>
                                <div class="cat-label"><i class="<?= $cat['icon'] ?? 'fas fa-box' ?> me-1"></i><?= htmlspecialchars($cat['category_name']) ?></div>
                                <?php foreach ($catItems as $item): ?>
                                <div class="item-row d-flex align-items-center gap-2 item-entry" data-name="<?= strtolower(htmlspecialchars($item['item_name'])) ?>">
                                    <input type="checkbox" class="item-select form-check-input me-1" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['item_name']) ?>" data-unit="<?= htmlspecialchars($item['unit']) ?>">
                                    <span class="item-name flex-grow-1"><?= htmlspecialchars($item['item_name']) ?></span>
                                    <span class="item-unit me-2">(<?= htmlspecialchars($item['unit']) ?>)</span>
                                    <input type="number" class="form-control form-control-sm qty-input" placeholder="Qty" min="0.1" step="any" disabled>
                                    <span class="text-muted small" style="width:45px"><?= htmlspecialchars($item['unit']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions or comments..."></textarea>
                        </div>

                        <div id="selectedItemsSummary" class="mb-3 p-3 bg-light rounded" style="display:none">
                            <strong><i class="fas fa-shopping-cart me-1"></i>Selected Items:</strong>
                            <div id="selectedList" class="mt-2 small"></div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="submit_for_dg" id="submitForDg" class="form-check-input" value="1">
                            <label class="form-check-label fw-semibold" for="submitForDg" style="font-size:13px;">
                                <i class="fas fa-crown text-warning me-1"></i>Submit directly for <strong>Director General Approval</strong>
                            </label>
                            <div class="text-muted small mt-1" style="font-size:11px;">If checked, this request bypasses the store and goes directly to the Director General for final approval.</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fas fa-paper-plane me-2"></i>Submit Request</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- User Info -->
            <div class="card mb-3">
                <div class="card-body text-center">
                    <div class="display-6 text-primary mb-2"><i class="fas fa-user-circle"></i></div>
                    <h5 class="mb-0"><?= htmlspecialchars($userName) ?></h5>
                    <small class="text-muted"><?= htmlspecialchars($userRole) ?></small>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-2 mb-3">
                <div class="col-6"><div class="stat-card"><div class="num"><?= count($allItems) ?></div><div class="label">Available Items</div></div></div>
                <div class="col-6"><div class="stat-card"><div class="num"><?= count($myRequests) ?></div><div class="label">My Requests</div></div></div>
            </div>

            <!-- My Recent Requests -->
            <div class="card">
                <div class="card-header bg-secondary text-white"><i class="fas fa-history me-2"></i>My Requests</div>
                <div class="card-body p-0">
                    <?php if (empty($myRequests)): ?>
                    <div class="p-3 text-center text-muted small"><i class="fas fa-inbox me-1"></i>No requests yet</div>
                    <?php else: ?>
                    <div class="list-group list-group-flush" style="max-height:400px;overflow-y:auto">
                        <?php foreach ($myRequests as $req):
                            $st = $req['status'];
                            $badge = $st === 'fulfilled' ? 'bg-success' : ($st === 'approved' ? 'bg-success' : ($st === 'pending_approval' ? 'bg-warning text-dark' : ($st === 'pending' ? 'bg-warning text-dark' : ($st === 'rejected' ? 'bg-danger' : 'bg-info'))));
                            $statusLabel = $st === 'pending_approval' ? 'Pending DG Approval' : ($st === 'pending' ? 'Pending' : ($st === 'approved' ? 'Approved' : $st));
                        ?>
                        <div class="list-group-item py-2">
                            <div class="d-flex justify-content-between">
                                <small class="fw-bold"><?= htmlspecialchars($req['request_number']) ?></small>
                                <span class="badge <?= $badge ?>"><?= ucfirst($statusLabel) ?></span>
                            </div>
                            <small class="text-muted"><?= $req['total_items'] ?> item(s) | <?= date('d M Y', strtotime($req['created_at'])) ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center p-2 d-flex gap-1 justify-content-center">
                    <a href="store_request.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i>New Request</a>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-home me-1"></i>Home</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<footer>
    <div class="container">
        <a href="index.php"><i class="fas fa-home me-1"></i>Back to Homepage</a>
        <span class="mx-2">|</span>
        &copy; <?= date('Y') ?> Iganga School of Nursing &amp; Midwifery
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Filter items by search
    $('#itemFilter').on('keyup', function() {
        let q = $(this).val().toLowerCase();
        $('.item-entry').each(function() {
            $(this).toggle($(this).data('name').includes(q));
        });
    });

    // Enable/disable quantity on checkbox
    $('.item-select').on('change', function() {
        $(this).closest('.item-row').find('.qty-input').prop('disabled', !this.checked);
        if (!this.checked) $(this).closest('.item-row').find('.qty-input').val('');
        updateSummary();
    });

    // Update summary on quantity change
    $('.qty-input').on('input', updateSummary);

    // Validate on submit
    $('#requestForm').on('submit', function(e) {
        let checked = $('.item-select:checked').length;
        if (checked === 0) { e.preventDefault(); alert('Please select at least one item.'); return; }
        let hasQty = false;
        $('.item-select:checked').each(function() {
            let qty = $(this).closest('.item-row').find('.qty-input').val();
            if (parseFloat(qty) > 0) hasQty = true;
        });
        if (!hasQty) { e.preventDefault(); alert('Please enter quantities for selected items.'); return; }

        // Build hidden inputs for selected items
        let form = $(this);
        form.find('.dynamic-item').remove();
        $('.item-select:checked').each(function() {
            let row = $(this).closest('.item-row');
            let qty = row.find('.qty-input').val();
            let id = $(this).data('id');
            let notes = row.find('.item-notes-input').val() || '';
            if (parseFloat(qty) > 0) {
                form.append('<input type="hidden" class="dynamic-item" name="items[' + id + '][item_id]" value="' + id + '">');
                form.append('<input type="hidden" class="dynamic-item" name="items[' + id + '][quantity]" value="' + qty + '">');
                form.append('<input type="hidden" class="dynamic-item" name="items[' + id + '][notes]" value="' + notes + '">');
            }
        });
    });

    function updateSummary() {
        let summary = $('#selectedItemsSummary');
        let list = $('#selectedList');
        let items = [];
        $('.item-select:checked').each(function() {
            let row = $(this).closest('.item-row');
            let qty = row.find('.qty-input').val();
            let name = $(this).data('name');
            let unit = $(this).data('unit');
            if (parseFloat(qty) > 0) items.push('<div>' + name + ' , <strong>' + qty + '</strong> ' + unit + '</div>');
        });
        if (items.length > 0) { summary.show(); list.html(items.join('')); }
        else { summary.hide(); list.html(''); }
    }
});
</script>
</body>
</html>
