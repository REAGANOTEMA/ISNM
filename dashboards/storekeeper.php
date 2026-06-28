<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['storekeeper', 'store', 'inventory']);
$staffConn = $ctx['staff'];
$user = $ctx['user'];
$user_role = $user['role'] ?? '';

$userId = (int)($user['id'] ?? 0);
$userName = $user['full_name'] ?? 'Store Keeper';

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Add / Remove / Adjust stock
    if (in_array($action, ['add_stock', 'remove_stock', 'adjust_stock'])) {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $qty = (float)($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? $action);

        $cur = $staffConn->prepare("SELECT quantity FROM store_inventory WHERE id=?");
        $cur->bind_param("i", $itemId);
        $cur->execute();
        $curRow = $cur->get_result()->fetch_assoc();
        $cur->close();
        $qtyBefore = $curRow ? (float)$curRow['quantity'] : 0;

        if ($action === 'add_stock') {
            $qtyAfter = $qtyBefore + $qty;
            $type = 'add';
        } elseif ($action === 'remove_stock') {
            $qty = min($qty, $qtyBefore);
            $qtyAfter = $qtyBefore - $qty;
            $type = 'remove';
        } else {
            $qtyAfter = max(0, $qty);
            $qty = $qtyAfter - $qtyBefore;
            $type = 'adjust';
        }

        $stmt = $staffConn->prepare("UPDATE store_inventory SET quantity=? WHERE id=?");
        $stmt->bind_param("di", $qtyAfter, $itemId);
        $stmt->execute();
        $stmt->close();
        $stmt = $staffConn->prepare("INSERT INTO store_inventory_transactions (item_id, transaction_type, quantity, quantity_before, quantity_after, reason, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isddd si", $itemId, $type, $qty, $qtyBefore, $qtyAfter, $reason, $userId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['store_msg'] = ['type'=>'success','text'=>'Stock updated successfully.'];
        header('Location: storekeeper.php'); exit;
    }

    // Fulfill request item
    if ($action === 'fulfill_item') {
        $reqItemId = (int)($_POST['req_item_id'] ?? 0);
        $qty = (float)($_POST['quantity'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $reqId = (int)($_POST['request_id'] ?? 0);

        if ($qty > 0) {
            $cur = $staffConn->prepare("SELECT quantity FROM store_inventory WHERE id=?");
            $cur->bind_param("i", $itemId);
            $cur->execute();
            $curRow = $cur->get_result()->fetch_assoc();
            $cur->close();
            $avail = $curRow ? (float)$curRow['quantity'] : 0;
            $qty = min($qty, $avail);

            $stmt = $staffConn->prepare("UPDATE store_request_items SET quantity_fulfilled=quantity_fulfilled+?, status='fulfilled' WHERE id=?");
            $stmt->bind_param("di", $qty, $reqItemId);
            $stmt->execute();
            $stmt->close();
            $stmt = $staffConn->prepare("UPDATE store_inventory SET quantity=quantity-? WHERE id=?");
            $stmt->bind_param("di", $qty, $itemId);
            $stmt->execute();
            $stmt->close();
            $reason = "Fulfilled request #$reqId";
            $stmt = $staffConn->prepare("INSERT INTO store_inventory_transactions (item_id, transaction_type, quantity, reason, created_by, reference_type, reference_id) VALUES (?, 'request_fulfilled', ?, ?, ?, 'request', ?)");
            $stmt->bind_param("idssi", $itemId, $qty, $reason, $userId, $reqId);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: storekeeper.php'); exit;
    }

    // Forward request to HR/Director
    if ($action === 'forward_request') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        $forwardTo = (int)($_POST['forward_to'] ?? 0);
        $forwardRole = $_POST['forward_role'] ?? '';
        $stmt = $staffConn->prepare("UPDATE store_requests SET status='forwarded', forwarded_to=?, forwarded_to_role=? WHERE id=?");
        $stmt->bind_param("isi", $forwardTo, $forwardRole, $reqId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['store_msg'] = ['type'=>'success','text'=>'Request forwarded for approval.'];
        header('Location: storekeeper.php'); exit;
    }

    // Submit for DG Approval
    if ($action === 'submit_for_dg_approval') {
        require_once __DIR__ . '/../includes/approval_integration.php';
        $reqId = (int)($_POST['request_id'] ?? 0);
        if (submitStoreForApproval($reqId, $staffConn)) {
            $_SESSION['store_msg'] = ['type'=>'success','text'=>'Request submitted for Director General approval.'];
        } else {
            $_SESSION['store_msg'] = ['type'=>'error','text'=>'Failed to submit for approval.'];
        }
        header('Location: storekeeper.php'); exit;
    }

    // Mark request as fulfilled
    if ($action === 'fulfill_request') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        $staffConn->query("UPDATE store_requests SET status='fulfilled', fulfilled_by=" . intval($userId) . ", fulfilled_at=NOW() WHERE id=" . intval($reqId));
        header('Location: storekeeper.php'); exit;
    }

    // Reject request
    if ($action === 'reject_request') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        $reason = $staffConn->real_escape_string(trim($_POST['rejection_reason'] ?? 'No reason'));
        $staffConn->query("UPDATE store_requests SET status='rejected', rejection_reason='$reason' WHERE id=" . intval($reqId));
        header('Location: storekeeper.php'); exit;
    }

    // Create order
    if ($action === 'create_order') {
        $items = $_POST['order_items'] ?? [];
        $supplier = $staffConn->real_escape_string(trim($_POST['supplier'] ?? 'Internal Requisition'));
        $notes = $staffConn->real_escape_string(trim($_POST['notes'] ?? ''));
        $valid = [];

        foreach ($items as $i) {
            $iid = (int)($i['item_id'] ?? 0);
            $qty = (float)($i['quantity'] ?? 0);
            $price = (float)($i['unit_price'] ?? 0);
            if ($iid > 0 && $qty > 0) $valid[] = [$iid, $qty, $price];
        }

        if (!empty($valid)) {
            $ordNum = 'PO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $total = array_sum(array_map(fn($v)=>$v[1]*$v[2], $valid));
            $staffConn->query("INSERT INTO store_orders (order_number, supplier, notes, total_amount, status, requested_by) VALUES ('$ordNum', '$supplier', '$notes', $total, 'pending_approval', " . intval($userId) . ")");
            $orderId = $staffConn->insert_id;

            $ins = $staffConn->prepare("INSERT INTO store_order_items (order_id, item_id, quantity_ordered, unit_price) VALUES (?, ?, ?, ?)");
            foreach ($valid as $v) {
                $ins->bind_param("iidd", $orderId, $v[0], $v[1], $v[2]);
                $ins->execute();
            }
            $ins->close();
            $_SESSION['store_msg'] = ['type'=>'success','text'=>"Order <strong>$ordNum</strong> created."];
        }
        header('Location: storekeeper.php'); exit;
    }

    // Receive order
    if ($action === 'receive_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $staffConn->query("UPDATE store_orders SET status='received', received_by=" . intval($userId) . ", received_at=NOW() WHERE id=" . intval($orderId));
        $items = $staffConn->query("SELECT oi.id, oi.item_id, oi.quantity_ordered FROM store_order_items oi WHERE oi.order_id=" . intval($orderId) . " AND oi.status='pending'");
        while ($row = $items->fetch_assoc()) {
            $staffConn->query("UPDATE store_order_items SET quantity_received=quantity_ordered, status='received' WHERE id={$row['id']}");
            $staffConn->query("UPDATE store_inventory SET quantity=quantity+{$row['quantity_ordered']} WHERE id={$row['item_id']}");
            $staffConn->query("INSERT INTO store_inventory_transactions (item_id, transaction_type, quantity, reason, created_by, reference_type, reference_id) VALUES ({$row['item_id']}, 'order_received', {$row['quantity_ordered']}, 'Order #$orderId received', " . intval($userId) . ", 'order', " . intval($orderId) . ")");
        }
        header('Location: storekeeper.php'); exit;
    }

    // Approve order
    if ($action === 'approve_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $staffConn->query("UPDATE store_orders SET status='approved', approved_by=" . intval($userId) . ", approved_at=NOW() WHERE id=" . intval($orderId));
        header('Location: storekeeper.php'); exit;
    }
}

// --- Load Data ---
$msg = $_SESSION['store_msg'] ?? null; unset($_SESSION['store_msg']);

// Categories + items
$categories = [];
$r = $staffConn->query("SELECT id, category_name FROM store_categories WHERE status='active'");
if ($r) while ($row = $r->fetch_assoc()) $categories[] = $row;

$inventory = [];
$r = $staffConn->query("SELECT si.*, sc.category_name FROM store_inventory si JOIN store_categories sc ON si.category_id=sc.id WHERE si.status='active' ORDER BY sc.category_name, si.item_name");
if ($r) while ($row = $r->fetch_assoc()) $inventory[] = $row;

// Low stock items
$lowStock = [];
$r = $staffConn->query("SELECT si.*, sc.category_name FROM store_inventory si JOIN store_categories sc ON si.category_id=sc.id WHERE si.status='active' AND si.quantity <= si.reorder_level ORDER BY (si.quantity / NULLIF(si.reorder_level,0)) ASC LIMIT 20");
if ($r) while ($row = $r->fetch_assoc()) $lowStock[] = $row;

// Pending requests
$pendingReqs = [];
$r = $staffConn->query("SELECT sr.*, s.full_name as requester_name, s.position as requester_role FROM store_requests sr LEFT JOIN staff s ON sr.requested_by=s.id WHERE sr.status IN ('pending','forwarded','pending_approval') ORDER BY FIELD(sr.status,'pending','forwarded','pending_approval'), FIELD(sr.urgency,'urgent','high','medium','low'), sr.created_at ASC");
if ($r) while ($row = $r->fetch_assoc()) $pendingReqs[] = $row;

// Recent fulfilled
$fulfilledReqs = [];
$r = $staffConn->query("SELECT sr.*, s.full_name as requester_name FROM store_requests sr LEFT JOIN staff s ON sr.requested_by=s.id WHERE sr.status IN ('fulfilled','rejected') ORDER BY sr.updated_at DESC LIMIT 10");
if ($r) while ($row = $r->fetch_assoc()) $fulfilledReqs[] = $row;

// Orders
$orders = [];
$r = $staffConn->query("SELECT so.*, s.full_name as requester_name FROM store_orders so LEFT JOIN staff s ON so.requested_by=s.id ORDER BY so.created_at DESC LIMIT 15");
if ($r) while ($row = $r->fetch_assoc()) $orders[] = $row;

// Transaction history (last 50)
$transactions = [];
$r = $staffConn->query("SELECT sit.*, si.item_name FROM store_inventory_transactions sit JOIN store_inventory si ON sit.item_id=si.id ORDER BY sit.created_at DESC LIMIT 50");
if ($r) while ($row = $r->fetch_assoc()) $transactions[] = $row;

// Staff for forwarding
$directors = [];
$r = $staffConn->query("SELECT s.id, s.full_name, s.position, sr.role_name FROM staff s JOIN staff_roles sr ON s.role_id=sr.id WHERE (sr.role_name LIKE '%Director%' OR sr.role_name LIKE '%HR%' OR sr.role_name LIKE '%Principal%' OR sr.role_name LIKE '%CEO%') AND s.status='Active' ORDER BY s.full_name");
if ($r) while ($row = $r->fetch_assoc()) $directors[] = $row;

$tab = $_GET['tab'] ?? 'dashboard';
$statsInv = count($inventory);
$statsPending = count($pendingReqs);
$statsLowStock = count($lowStock);
$statsOrders = count($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-content">
    <div class="top-bar">
        <div>
            <strong><i class="fas fa-warehouse me-2 text-primary"></i>Store Keeper</strong>
            <span class="text-muted small ms-2"><?= htmlspecialchars($userName) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-md-block"><?= date('D, d M Y') ?></span>
            <a href="../news.php" class="btn btn-sm btn-outline-light"><i class="fas fa-newspaper me-1"></i>News</a>
            <a href="../store_request.php" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>New Request</a>
            <a href="../student-directory.php" class="btn btn-sm btn-outline-light"><i class="fas fa-address-book me-1"></i>Directory</a>
            <a href="../index.php" class="btn btn-sm btn-outline-light"><i class="fas fa-home me-1"></i></a>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
        </div>
    </div>

    <div class="content-section dashboard-section active content-area" data-section="overview">
        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show py-2"><?= $msg['text'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#1a237e,#3949ab)"><i class="fas fa-box"></i></div>
                    <div class="stat-info"><h3><?= $statsInv ?></h3><p>Inventory Items</p></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#e65100,#fb8c00)"><i class="fas fa-clipboard-list"></i></div>
                    <div class="stat-info"><h3><?= $statsPending ?></h3><p>Pending Requests</p></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#c62828,#ef5350)"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-info"><h3><?= $statsLowStock ?></h3><p>Low Stock Items</p></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#2e7d32,#43a047)"><i class="fas fa-truck"></i></div>
                    <div class="stat-info"><h3><?= $statsOrders ?></h3><p>Orders</p></div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
            <a href="?tab=requests" class="<?= $tab === 'requests' ? 'active' : '' ?>"><i class="fas fa-clipboard-list me-1"></i>Requests <?= $statsPending ? '<span class="badge bg-danger ms-1">'.$statsPending.'</span>' : '' ?></a>
            <a href="?tab=inventory" class="<?= $tab === 'inventory' ? 'active' : '' ?>"><i class="fas fa-boxes me-1"></i>Inventory</a>
            <a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>"><i class="fas fa-truck me-1"></i>Orders</a>
            <a href="?tab=transactions" class="<?= $tab === 'transactions' ? 'active' : '' ?>"><i class="fas fa-history me-1"></i>History</a>
            <a href="../store_request.php" class="ms-auto"><i class="fas fa-plus-circle me-1"></i>New Request</a>
        </div>

<?php if ($tab === 'dashboard'): ?>
        <!-- === DASHBOARD TAB === -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-exclamation-triangle text-warning me-2"></i>Low Stock Alerts</h2>
                    <?php if (empty($lowStock)): ?>
                        <p class="text-muted small">All items are well stocked.</p>
                    <?php else: ?>
                    <div style="max-height:300px;overflow-y:auto">
                        <?php foreach ($lowStock as $item): 
                            $ratio = $item['reorder_level'] > 0 ? round($item['quantity'] / $item['reorder_level'] * 100) : 0;
                            $cls = $ratio <= 25 ? 'qty-bad' : ($ratio <= 75 ? 'qty-warn' : 'qty-ok');
                        ?>
                        <div class="item-row">
                            <span class="item-name"><small class="text-muted">[<?= htmlspecialchars($item['category_name']) ?>]</small> <?= htmlspecialchars($item['item_name']) ?></span>
                            <span class="qty-badge <?= $cls ?>"><?= number_format($item['quantity']) ?> / <?= number_format($item['reorder_level']) ?> <?= htmlspecialchars($item['unit']) ?></span>
                            <button class="btn btn-sm btn-outline-primary" onclick="openAddStock(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['item_name'])) ?>')"><i class="fas fa-plus"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-card">
                    <h2><i class="fas fa-clipboard-list text-primary me-2"></i>Recent Pending Requests</h2>
                    <?php if (empty($pendingReqs)): ?>
                        <p class="text-muted small">No pending requests.</p>
                    <?php else: foreach (array_slice($pendingReqs, 0, 5) as $req): 
                        $urgencyBadge = $req['urgency'] === 'urgent' ? 'bg-danger' : ($req['urgency'] === 'high' ? 'bg-warning text-dark' : ($req['urgency'] === 'medium' ? 'bg-info' : 'bg-secondary'));
                    ?>
                    <div class="request-card">
                        <div class="d-flex justify-content-between">
                            <span class="req-num"><?= htmlspecialchars($req['request_number']) ?></span>
                            <span class="badge <?= $urgencyBadge ?>"><?= $req['urgency'] ?></span>
                        </div>
                        <div class="req-meta"><?= htmlspecialchars($req['requester_name'] ?? 'Unknown') ?> | <?= htmlspecialchars($req['department'] ?? '') ?> | <?= date('d M Y H:i', strtotime($req['created_at'])) ?></div>
                        <div class="req-actions mt-2">
                            <?php if ($req['status'] === 'pending_approval'): ?>
                            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending DG Approval</span>
                            <?php else: ?>
                            <button class="btn btn-sm btn-success" onclick="openFulfill(<?= $req['id'] ?>)"><i class="fas fa-check me-1"></i>Fulfill</button>
                            <button class="btn btn-sm btn-info text-white" onclick="openForward(<?= $req['id'] ?>)"><i class="fas fa-forward me-1"></i>Forward</button>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="submit_for_dg_approval">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <button class="btn btn-sm btn-warning text-dark" type="submit"><i class="fas fa-crown me-1"></i>DG Approve</button>
                            </form>
                            <button class="btn btn-sm btn-outline-danger" onclick="openReject(<?= $req['id'] ?>)"><i class="fas fa-times me-1"></i>Reject</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                    <div class="text-center mt-2"><a href="?tab=requests" class="btn btn-sm btn-outline-primary">View All Requests</a></div>
                </div>
            </div>
        </div>
<?php elseif ($tab === 'requests'): ?>
        <!-- === REQUESTS TAB === -->
        <div class="section-card">
            <h2><i class="fas fa-clipboard-list me-2"></i>Pending Store Requests</h2>
            <?php if (empty($pendingReqs)): ?>
                <p class="text-muted">No pending requests at the moment.</p>
            <?php else: foreach ($pendingReqs as $req):
                $urgencyBadge = $req['urgency'] === 'urgent' ? 'bg-danger' : ($req['urgency'] === 'high' ? 'bg-warning text-dark' : ($req['urgency'] === 'medium' ? 'bg-info' : 'bg-secondary'));
                $reqItems = $staffConn->query("SELECT sri.*, si.item_name, si.unit, si.quantity as avail_qty FROM store_request_items sri JOIN store_inventory si ON sri.item_id=si.id WHERE sri.request_id={$req['id']}");
            ?>
            <div class="request-card">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <span class="req-num"><?= htmlspecialchars($req['request_number']) ?></span>
                        <span class="badge <?= $urgencyBadge ?> ms-2"><?= $req['urgency'] ?></span>
                        <span class="badge bg-secondary ms-1"><?= $req['status'] ?></span>
                    </div>
                    <small class="text-muted"><?= date('d M Y H:i', strtotime($req['created_at'])) ?></small>
                </div>
                <div class="req-meta mb-2">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($req['requester_name'] ?? 'Unknown') ?>
                    <span class="mx-2">|</span>
                    <i class="fas fa-building me-1"></i><?= htmlspecialchars($req['department'] ?? 'N/A') ?>
                    <?php if ($req['forwarded_to_role']): ?>
                    <span class="mx-2">|</span>
                    <i class="fas fa-forward me-1"></i>Forwarded to: <?= htmlspecialchars($req['forwarded_to_role']) ?>
                    <?php endif; ?>
                </div>
                <?php if ($req['notes']): ?><div class="small text-muted mb-2"><i class="fas fa-comment me-1"></i><?= htmlspecialchars($req['notes']) ?></div><?php endif; ?>
                <table class="table table-sm table-bordered mb-2">
                    <thead class="table-light"><tr><th>Item</th><th>Qty Requested</th><th>Available</th><th>Fulfill</th></tr></thead>
                    <tbody>
                        <?php while ($ri = $reqItems->fetch_assoc()): 
                            $avail = (float)$ri['avail_qty'];
                            $reqd = (float)$ri['quantity_requested'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($ri['item_name']) ?></td>
                            <td><?= number_format($reqd) ?> <?= htmlspecialchars($ri['unit']) ?></td>
                            <td><span class="qty-badge <?= $avail >= $reqd ? 'qty-ok' : 'qty-bad' ?>"><?= number_format($avail) ?></span></td>
                            <td>
                                <?php if ($ri['status'] === 'fulfilled'): ?>
                                <span class="badge bg-success">Done</span>
                                <?php else: ?>
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="action" value="fulfill_item">
                                    <input type="hidden" name="req_item_id" value="<?= $ri['id'] ?>">
                                    <input type="hidden" name="item_id" value="<?= $ri['item_id'] ?>">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <input type="number" name="quantity" class="form-control form-control-sm" style="width:70px" value="<?= min($reqd, $avail) ?>" max="<?= $avail ?>" min="0" step="any">
                                    <button class="btn btn-sm btn-success" <?= $avail <= 0 ? 'disabled' : '' ?>><i class="fas fa-check"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if ($req['status'] === 'pending_approval'): ?>
                    <span class="badge bg-warning text-dark py-2 px-3"><i class="fas fa-clock me-1"></i>Submitted for DG Approval — awaiting decision</span>
                    <a href="../dashboards/director-general.php#approvals" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i>View in Approval Center</a>
                    <?php else: ?>
                    <button class="btn btn-sm btn-success" onclick="fulfillAll(<?= $req['id'] ?>)"><i class="fas fa-check-double me-1"></i>Mark All Fulfilled</button>
                    <button class="btn btn-sm btn-info text-white" onclick="openForward(<?= $req['id'] ?>)"><i class="fas fa-forward me-1"></i>Forward to HR/Director</button>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="submit_for_dg_approval">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <button class="btn btn-sm btn-warning text-dark" type="submit"><i class="fas fa-crown me-1"></i>Submit for DG Approval</button>
                    </form>
                    <button class="btn btn-sm btn-outline-danger" onclick="openReject(<?= $req['id'] ?>)"><i class="fas fa-times me-1"></i>Reject</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="section-card">
            <h2><i class="fas fa-history me-2"></i>Recent Fulfilled / Rejected</h2>
            <?php if (empty($fulfilledReqs)): ?>
                <p class="text-muted small">No history yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>#</th><th>Requester</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($fulfilledReqs as $r): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($r['request_number']) ?></code></td>
                            <td><?= htmlspecialchars($r['requester_name'] ?? '') ?></td>
                            <td><span class="badge bg-<?= $r['status'] === 'fulfilled' ? 'success' : 'danger' ?>"><?= $r['status'] ?></span></td>
                            <td><small><?= date('d M Y', strtotime($r['updated_at'])) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
<?php elseif ($tab === 'inventory'): ?>
        <!-- === INVENTORY TAB === -->
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0"><i class="fas fa-boxes me-2"></i>Full Inventory</h2>
                <input type="text" id="invFilter" class="form-control form-control-sm" style="width:250px" placeholder="Search items...">
            </div>
            <div class="table-responsive" style="max-height:600px;overflow-y:auto">
                <table class="table table-sm table-hover" id="invTable">
                    <thead class="table-light sticky-top"><tr><th>Item</th><th>Category</th><th>Stock</th><th>Unit</th><th>Reorder At</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($inventory as $item):
                            $isLow = $item['quantity'] <= $item['reorder_level'];
                        ?>
                        <tr class="inv-row" data-name="<?= strtolower(htmlspecialchars($item['item_name'].' '.$item['category_name'])) ?>">
                            <td class="fw-semibold"><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($item['category_name']) ?></small></td>
                            <td><span class="qty-badge <?= $isLow ? 'qty-bad' : 'qty-ok' ?>"><?= number_format($item['quantity']) ?></span></td>
                            <td><?= htmlspecialchars($item['unit']) ?></td>
                            <td><?= number_format($item['reorder_level']) ?></td>
                            <td><?= $item['status'] ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openAddStock(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['item_name'])) ?>')"><i class="fas fa-plus me-2 text-success"></i>Add Stock</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openRemoveStock(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['item_name'])) ?>', <?= $item['quantity'] ?>)"><i class="fas fa-minus me-2 text-danger"></i>Remove Stock</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openAdjustStock(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['item_name'])) ?>', <?= $item['quantity'] ?>)"><i class="fas fa-sliders-h me-2 text-primary"></i>Adjust</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php elseif ($tab === 'orders'): ?>
        <!-- === ORDERS TAB === -->
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="section-card">
                    <h2><i class="fas fa-plus-circle me-2 text-success"></i>Create Purchase Order</h2>
                    <form method="POST" id="orderForm">
                        <input type="hidden" name="action" value="create_order">
                        <div class="mb-2"><label class="form-label fw-semibold">Supplier / Source</label><input type="text" name="supplier" class="form-control" placeholder="Internal Requisition"></div>
                        <div class="mb-2"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                        <label class="form-label fw-semibold">Items to Order</label>
                        <div id="orderItems" style="max-height:300px;overflow-y:auto">
                            <?php foreach ($inventory as $item): ?>
                            <div class="d-flex align-items-center gap-1 mb-1" style="font-size:.82rem">
                                <input type="checkbox" class="order-item-cb form-check-input" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['item_name']) ?>">
                                <span style="flex:1"><?= htmlspecialchars($item['item_name']) ?></span>
                                <input type="number" class="form-control form-control-sm" style="width:60px" placeholder="Qty" disabled>
                                <input type="number" class="form-control form-control-sm" style="width:65px" placeholder="Price" disabled>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-2"><i class="fas fa-save me-1"></i>Create Order</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="section-card">
                    <h2><i class="fas fa-truck me-2"></i>Orders &amp; Receiving</h2>
                    <?php if (empty($orders)): ?><p class="text-muted">No orders yet.</p>
                    <?php else: foreach ($orders as $ord): 
                        $badgeCls = $ord['status'] === 'received' ? 'bg-success' : ($ord['status'] === 'approved' ? 'bg-info' : ($ord['status'] === 'pending_approval' ? 'bg-warning text-dark' : ($ord['status'] === 'cancelled' ? 'bg-danger' : 'bg-secondary')));
                    ?>
                    <div class="request-card">
                        <div class="d-flex justify-content-between">
                            <span class="req-num"><?= htmlspecialchars($ord['order_number']) ?></span>
                            <span class="badge <?= $badgeCls ?>"><?= $ord['status'] ?></span>
                        </div>
                        <div class="req-meta"><?= htmlspecialchars($ord['supplier']) ?> | UGX <?= number_format($ord['total_amount']) ?> | <?= htmlspecialchars($ord['requester_name'] ?? '') ?></div>
                        <?php if ($ord['status'] === 'approved'): ?>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="action" value="receive_order">
                            <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                            <button class="btn btn-sm btn-success"><i class="fas fa-check-double me-1"></i>Receive Order</button>
                        </form>
                        <?php endif; ?>
                        <?php if ($ord['status'] === 'pending_approval'): ?>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="action" value="approve_order">
                            <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                            <button class="btn btn-sm btn-primary"><i class="fas fa-check me-1"></i>Approve</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
<?php elseif ($tab === 'transactions'): ?>
        <!-- === TRANSACTIONS TAB === -->
        <div class="section-card">
            <h2><i class="fas fa-history me-2"></i>Transaction History</h2>
            <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                <table class="table table-sm table-hover">
                    <thead class="table-light"><tr><th>Date</th><th>Item</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>Reason</th></tr></thead>
                    <tbody>
                        <?php foreach ($transactions as $t): 
                            $tBadge = $t['transaction_type'] === 'add' || $t['transaction_type'] === 'order_received' ? 'bg-success' : ($t['transaction_type'] === 'remove' || $t['transaction_type'] === 'request_fulfilled' ? 'bg-warning text-dark' : 'bg-secondary');
                        ?>
                        <tr>
                            <td><small><?= date('d M H:i', strtotime($t['created_at'])) ?></small></td>
                            <td><?= htmlspecialchars($t['item_name']) ?></td>
                            <td><span class="badge <?= $tBadge ?>"><?= str_replace('_', ' ', $t['transaction_type']) ?></span></td>
                            <td><?= number_format($t['quantity']) ?></td>
                            <td><?= number_format($t['quantity_before']) ?></td>
                            <td><?= number_format($t['quantity_after']) ?></td>
                            <td><small><?= htmlspecialchars($t['reason'] ?? '') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php endif; ?>
    </div>
</div>

<!-- Stock Action Modals -->
<div class="modal fade" id="addStockModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content">
        <input type="hidden" name="action" value="add_stock">
        <input type="hidden" name="item_id" id="addItemId">
        <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Stock</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p id="addItemName" class="fw-bold"></p>
            <div class="mb-3"><label class="form-label">Quantity to Add</label><input type="number" name="quantity" class="form-control" min="0.1" step="any" required></div>
            <div class="mb-3"><label class="form-label">Reason</label><input type="text" name="reason" class="form-control" placeholder="e.g., New delivery" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Add</button></div>
    </form>
</div></div>

<div class="modal fade" id="removeStockModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content">
        <input type="hidden" name="action" value="remove_stock">
        <input type="hidden" name="item_id" id="rmItemId">
        <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-minus-circle me-2"></i>Remove Stock</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p id="rmItemName" class="fw-bold"></p>
            <p id="rmCurrentQty" class="text-muted small"></p>
            <div class="mb-3"><label class="form-label">Quantity to Remove</label><input type="number" name="quantity" id="rmQuantity" class="form-control" min="0.1" step="any" required></div>
            <div class="mb-3"><label class="form-label">Reason</label><input type="text" name="reason" class="form-control" placeholder="e.g., Usage, damaged" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><i class="fas fa-check me-1"></i>Remove</button></div>
    </form>
</div></div>

<div class="modal fade" id="adjustStockModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content">
        <input type="hidden" name="action" value="adjust_stock">
        <input type="hidden" name="item_id" id="adjItemId">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-sliders-h me-2"></i>Adjust Stock</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p id="adjItemName" class="fw-bold"></p>
            <p id="adjCurrentQty" class="text-muted small"></p>
            <div class="mb-3"><label class="form-label">New Quantity</label><input type="number" name="quantity" class="form-control" min="0" step="any" required></div>
            <div class="mb-3"><label class="form-label">Reason for Adjustment</label><input type="text" name="reason" class="form-control" placeholder="e.g., Stock count correction" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>Adjust</button></div>
    </form>
</div></div>

<!-- Forward Request Modal -->
<div class="modal fade" id="forwardModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content">
        <input type="hidden" name="action" value="forward_request">
        <input type="hidden" name="request_id" id="fwdReqId">
        <div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-forward me-2"></i>Forward Request</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p>Forward this request to HR or a Director for approval:</p>
            <div class="mb-3">
                <label class="form-label fw-semibold">Forward To</label>
                <select name="forward_to" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($directors as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?> (<?= htmlspecialchars($d['position'] ?: $d['role_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="forward_role" id="fwdRole">
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-info text-white"><i class="fas fa-forward me-1"></i>Forward</button></div>
    </form>
</div></div>

<!-- Reject Request Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content">
        <input type="hidden" name="action" value="reject_request">
        <input type="hidden" name="request_id" id="rejReqId">
        <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Reject Request</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label fw-semibold">Reason for Rejection</label><textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Explain why this request is being rejected..."></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><i class="fas fa-check me-1"></i>Reject</button></div>
    </form>
</div></div>

<!-- Fulfill All Form -->
<form method="POST" id="fulfillAllForm"><input type="hidden" name="action" value="fulfill_request"><input type="hidden" name="request_id" id="fulfillAllId"></form>

<!-- Hidden staff list for forward role auto-fill -->
<select id="directorData" style="display:none">
<?php foreach ($directors as $d): ?>
<option value="<?= $d['id'] ?>" data-role="<?= htmlspecialchars($d['position'] ?: $d['role_name']) ?>"></option>
<?php endforeach; ?>
</select>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search filter
$('#invFilter').on('keyup', function() {
    let q = $(this).val().toLowerCase();
    $('.inv-row').each(function() { $(this).toggle($(this).data('name').includes(q)); });
});

// Stock modals
function openAddStock(id, name) { $('#addItemId').val(id); $('#addItemName').text(name); $('#addStockModal').modal('show'); }
function openRemoveStock(id, name, qty) { $('#rmItemId').val(id); $('#rmItemName').text(name); $('#rmCurrentQty').text('Current stock: ' + qty); $('#rmQuantity').attr('max', qty); $('#removeStockModal').modal('show'); }
function openAdjustStock(id, name, qty) { $('#adjItemId').val(id); $('#adjItemName').text(name); $('#adjCurrentQty').text('Current stock: ' + qty); $('input[name="quantity"]', '#adjustStockModal').val(qty); $('#adjustStockModal').modal('show'); }
function openForward(id) { $('#fwdReqId').val(id); $('#forwardModal').modal('show'); }
function openReject(id) { $('#rejReqId').val(id); $('#rejectModal').modal('show'); }
function fulfillAll(id) { if (confirm('Mark this entire request as fulfilled?')) { $('#fulfillAllId').val(id); $('#fulfillAllForm').submit(); } }

// Auto-set forward role
$('select[name="forward_to"]').on('change', function() {
    let v = $(this).val();
    let opt = $('#directorData option[value="' + v + '"]');
    $('#fwdRole').val(opt.length ? opt.data('role') : '');
});

// Order form: enable qty/price on checkbox
$('.order-item-cb').on('change', function() {
    $(this).closest('.d-flex').find('input[type="number"]').prop('disabled', !this.checked);
    if (!this.checked) $(this).closest('.d-flex').find('input[type="number"]').val('');
});
$('#orderForm').on('submit', function(e) {
    let checked = $('.order-item-cb:checked').length;
    if (checked === 0) { e.preventDefault(); alert('Select at least one item.'); return; }
    // Build hidden inputs
    $(this).find('.dynamic-order-item').remove();
    $('.order-item-cb:checked').each(function() {
        let row = $(this).closest('.d-flex');
        let qty = row.find('input[type="number"]:first').val();
        let price = row.find('input[type="number"]:last').val();
        let id = $(this).data('id');
        if (parseFloat(qty) > 0) {
            $(this).closest('form').append(
                '<input type="hidden" class="dynamic-order-item" name="order_items[' + id + '][item_id]" value="' + id + '">',
                '<input type="hidden" class="dynamic-order-item" name="order_items[' + id + '][quantity]" value="' + qty + '">',
                '<input type="hidden" class="dynamic-order-item" name="order_items[' + id + '][unit_price]" value="' + (parseFloat(price) || 0) + '">'
            );
        }
    });
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
