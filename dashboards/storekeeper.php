<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';

$ctx = bootstrapStaffDashboard(['storekeeper']);
$staffConn = $ctx['staff'];
$user = $ctx['user'];
$user_role = $user['role'] ?? '';
$userId = (int)($user['id'] ?? 0);
$userName = $user['full_name'] ?? 'Store Keeper';

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if (in_array($action, ['add_stock', 'remove_stock', 'adjust_stock'])) {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $qty = (float)($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? $action);
        $cur = $staffConn->prepare("SELECT quantity FROM store_inventory WHERE id=?");
        $cur->bind_param("i", $itemId); $cur->execute();
        $curRow = $cur->get_result()->fetch_assoc(); $cur->close();
        $qtyBefore = $curRow ? (float)$curRow['quantity'] : 0;
        if ($action === 'add_stock') { $qtyAfter = $qtyBefore + $qty; $type = 'add'; }
        elseif ($action === 'remove_stock') { $qty = min($qty, $qtyBefore); $qtyAfter = $qtyBefore - $qty; $type = 'remove'; }
        else { $qtyAfter = max(0, $qty); $qty = $qtyAfter - $qtyBefore; $type = 'adjust'; }
        $stmt = $staffConn->prepare("UPDATE store_inventory SET quantity=? WHERE id=?");
        $stmt->bind_param("di", $qtyAfter, $itemId); $stmt->execute(); $stmt->close();
        $stmt = $staffConn->prepare("INSERT INTO store_inventory_transactions (item_id, transaction_type, quantity, quantity_before, quantity_after, reason, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isddddi", $itemId, $type, $qty, $qtyBefore, $qtyAfter, $reason, $userId);
        $stmt->execute(); $stmt->close();
        $_SESSION['store_msg'] = ['type'=>'success','text'=>'Stock updated successfully.'];
        header('Location: storekeeper.php'); exit;
    }

    if ($action === 'fulfill_item') {
        $reqItemId = (int)($_POST['req_item_id'] ?? 0);
        $qty = (float)($_POST['quantity'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $reqId = (int)($_POST['request_id'] ?? 0);
        if ($qty > 0) {
            $cur = $staffConn->prepare("SELECT quantity FROM store_inventory WHERE id=?");
            $cur->bind_param("i", $itemId); $cur->execute();
            $curRow = $cur->get_result()->fetch_assoc(); $cur->close();
            $avail = $curRow ? (float)$curRow['quantity'] : 0;
            $qty = min($qty, $avail);
            $stmt = $staffConn->prepare("UPDATE store_request_items SET quantity_fulfilled=quantity_fulfilled+?, status='fulfilled' WHERE id=?");
            $stmt->bind_param("di", $qty, $reqItemId); $stmt->execute(); $stmt->close();
            $stmt = $staffConn->prepare("UPDATE store_inventory SET quantity=quantity-? WHERE id=?");
            $stmt->bind_param("di", $qty, $itemId); $stmt->execute(); $stmt->close();
            $reason = "Fulfilled request #$reqId";
            $stmt = $staffConn->prepare("INSERT INTO store_inventory_transactions (item_id, transaction_type, quantity, reason, created_by, reference_type, reference_id) VALUES (?, 'request_fulfilled', ?, ?, ?, 'request', ?)");
            $stmt->bind_param("idssi", $itemId, $qty, $reason, $userId, $reqId); $stmt->execute(); $stmt->close();
        }
        header('Location: storekeeper.php'); exit;
    }

    if ($action === 'submit_for_dg_approval') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        $stmt = $staffConn->prepare("UPDATE store_requests SET status='pending_approval', updated_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $reqId); $stmt->execute(); $stmt->close();
        $_SESSION['store_msg'] = ['type'=>'success','text'=>'Request submitted for Director General approval.'];
        header('Location: storekeeper.php'); exit;
    }

    if ($action === 'fulfill_request') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        $stmt = $staffConn->prepare("UPDATE store_requests SET status='fulfilled', fulfilled_by=?, fulfilled_at=NOW(), updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ii", $userId, $reqId); $stmt->execute(); $stmt->close();
        $_SESSION['store_msg'] = ['type'=>'success','text'=>'Request marked as fulfilled.'];
        header('Location: storekeeper.php'); exit;
    }

    if ($action === 'reject_request') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        $reason = trim($_POST['rejection_reason'] ?? 'No reason');
        $stmt = $staffConn->prepare("UPDATE store_requests SET status='rejected', rejection_reason=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $reason, $reqId); $stmt->execute(); $stmt->close();
        $_SESSION['store_msg'] = ['type'=>'success','text'=>'Request rejected.'];
        header('Location: storekeeper.php'); exit;
    }

    if ($action === 'add_item') {
        $itemCode = trim($_POST['item_code'] ?? '');
        $itemName = trim($_POST['item_name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'pcs');
        $qty = (float)($_POST['quantity'] ?? 0);
        $reorderLevel = (int)($_POST['reorder_level'] ?? 10);
        $unitCost = (float)($_POST['unit_cost'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        $batchNumber = trim($_POST['batch_number'] ?? '');
        $expiryDate = trim($_POST['expiry_date'] ?? '') ?: null;
        $supplier = trim($_POST['supplier'] ?? '');
        if ($itemName === '') {
            $_SESSION['store_msg'] = ['type'=>'error','text'=>'Item name is required.'];
        } else {
            $stmt = $staffConn->prepare("INSERT INTO store_inventory (item_code, item_name, category_id, unit, quantity, reorder_level, unit_cost, location, batch_number, expiry_date, supplier, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("sssiiidssss", $itemCode, $itemName, $categoryId, $unit, $qty, $reorderLevel, $unitCost, $location, $batchNumber, $expiryDate, $supplier);
            if ($stmt->execute()) { $_SESSION['store_msg'] = ['type'=>'success','text'=>'Item "' . htmlspecialchars($itemName) . '" added.']; }
            else { $_SESSION['store_msg'] = ['type'=>'error','text'=>'Failed to add item.']; }
            $stmt->close();
        }
        header('Location: storekeeper.php?tab=inventory'); exit;
    }

    if ($action === 'update_item') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $itemCode = trim($_POST['item_code'] ?? '');
        $itemName = trim($_POST['item_name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'pcs');
        $qty = (float)($_POST['quantity'] ?? 0);
        $reorderLevel = (int)($_POST['reorder_level'] ?? 10);
        $unitCost = (float)($_POST['unit_cost'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        $batchNumber = trim($_POST['batch_number'] ?? '');
        $expiryDate = trim($_POST['expiry_date'] ?? '') ?: null;
        $supplier = trim($_POST['supplier'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        if ($itemId <= 0 || $itemName === '') {
            $_SESSION['store_msg'] = ['type'=>'error','text'=>'Invalid item data.'];
        } else {
            $stmt = $staffConn->prepare("UPDATE store_inventory SET item_code=?, item_name=?, category_id=?, unit=?, quantity=?, reorder_level=?, unit_cost=?, location=?, batch_number=?, expiry_date=?, supplier=?, status=? WHERE id=?");
            $stmt->bind_param("sssiidsssssi", $itemCode, $itemName, $categoryId, $unit, $qty, $reorderLevel, $unitCost, $location, $batchNumber, $expiryDate, $supplier, $status, $itemId);
            if ($stmt->execute()) { $_SESSION['store_msg'] = ['type'=>'success','text'=>'Item updated.']; }
            else { $_SESSION['store_msg'] = ['type'=>'error','text'=>'Failed to update item.']; }
            $stmt->close();
        }
        header('Location: storekeeper.php?tab=inventory'); exit;
    }

    if ($action === 'delete_item') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId > 0) {
            $stmt = $staffConn->prepare("UPDATE store_inventory SET status='inactive' WHERE id=?");
            $stmt->bind_param("i", $itemId); $stmt->execute(); $stmt->close();
            $_SESSION['store_msg'] = ['type'=>'success','text'=>'Item deactivated.'];
        }
        header('Location: storekeeper.php?tab=inventory'); exit;
    }

    if ($action === 'add_request') {
        $department = trim($_POST['department'] ?? '');
        $urgency = trim($_POST['urgency'] ?? 'medium');
        $notes = trim($_POST['notes'] ?? '');
        $reqItems = $_POST['req_items'] ?? [];
        if (empty($reqItems)) {
            $_SESSION['store_msg'] = ['type'=>'error','text'=>'Add at least one item to the request.'];
        } else {
            $reqNum = 'SR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $staffConn->prepare("INSERT INTO store_requests (request_number, requested_by, requester_name, department, urgency, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("sissss", $reqNum, $userId, $userName, $department, $urgency, $notes);
            if ($stmt->execute()) {
                $reqId = $staffConn->insert_id;
                $ins = $staffConn->prepare("INSERT INTO store_request_items (request_id, item_id, quantity_requested, notes) VALUES (?, ?, ?, ?)");
                foreach ($reqItems as $ri) {
                    $riItemId = (int)($ri['item_id'] ?? 0); $riQty = (float)($ri['quantity'] ?? 0); $riNotes = trim($ri['notes'] ?? '');
                    if ($riItemId > 0 && $riQty > 0) { $ins->bind_param("iids", $reqId, $riItemId, $riQty, $riNotes); $ins->execute(); }
                }
                $ins->close();
                $_SESSION['store_msg'] = ['type'=>'success','text'=>"Request <strong>$reqNum</strong> created."];
            } else { $_SESSION['store_msg'] = ['type'=>'error','text'=>'Failed to create request.']; }
            $stmt->close();
        }
        header('Location: storekeeper.php?tab=requests'); exit;
    }

    if ($action === 'add_category') {
        $catName = trim($_POST['category_name'] ?? '');
        $catDesc = trim($_POST['description'] ?? '');
        if ($catName) {
            $stmt = $staffConn->prepare("INSERT INTO store_categories (category_name, description, status) VALUES (?, ?, 'active')");
            $stmt->bind_param("ss", $catName, $catDesc); $stmt->execute(); $stmt->close();
            $_SESSION['store_msg'] = ['type'=>'success','text'=>'Category added.'];
        }
        header('Location: storekeeper.php?tab=categories'); exit;
    }

    if ($action === 'delete_category') {
        $catId = (int)($_POST['category_id'] ?? 0);
        if ($catId) {
            $stmt = $staffConn->prepare("UPDATE store_categories SET status='inactive' WHERE id=?");
            $stmt->bind_param("i", $catId); $stmt->execute(); $stmt->close();
            $_SESSION['store_msg'] = ['type'=>'success','text'=>'Category deactivated.'];
        }
        header('Location: storekeeper.php?tab=categories'); exit;
    }
}

// ── Load Data ──
$msg = $_SESSION['store_msg'] ?? null; unset($_SESSION['store_msg']);

$categories = [];
$r = $staffConn->query("SELECT id, category_name, description, status FROM store_categories ORDER BY category_name");
if ($r) while ($row = $r->fetch_assoc()) $categories[] = $row;

$inventory = [];
$r = $staffConn->query("SELECT si.*, sc.category_name FROM store_inventory si LEFT JOIN store_categories sc ON si.category_id=sc.id WHERE si.status='active' ORDER BY sc.category_name, si.item_name");
if ($r) while ($row = $r->fetch_assoc()) $inventory[] = $row;

$lowStock = [];
$r = $staffConn->query("SELECT si.*, sc.category_name FROM store_inventory si LEFT JOIN store_categories sc ON si.category_id=sc.id WHERE si.status='active' AND si.quantity <= si.reorder_level ORDER BY (si.quantity / NULLIF(si.reorder_level,0)) ASC LIMIT 20");
if ($r) while ($row = $r->fetch_assoc()) $lowStock[] = $row;

$expiringItems = [];
$r = $staffConn->query("SELECT si.*, sc.category_name FROM store_inventory si LEFT JOIN store_categories sc ON si.category_id=sc.id WHERE si.status='active' AND si.expiry_date IS NOT NULL AND si.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY si.expiry_date ASC LIMIT 20");
if ($r) while ($row = $r->fetch_assoc()) $expiringItems[] = $row;

$pendingReqs = [];
$r = $staffConn->query("SELECT sr.*, s.full_name as requester_name FROM store_requests sr LEFT JOIN staff s ON sr.requested_by=s.id WHERE sr.status IN ('pending','pending_approval') ORDER BY FIELD(sr.status,'pending','pending_approval'), FIELD(sr.urgency,'urgent','high','medium','low'), sr.created_at ASC");
if ($r) while ($row = $r->fetch_assoc()) $pendingReqs[] = $row;

$fulfilledReqs = [];
$r = $staffConn->query("SELECT sr.*, s.full_name as requester_name FROM store_requests sr LEFT JOIN staff s ON sr.requested_by=s.id WHERE sr.status IN ('fulfilled','rejected') ORDER BY sr.updated_at DESC LIMIT 10");
if ($r) while ($row = $r->fetch_assoc()) $fulfilledReqs[] = $row;

$transactions = [];
$r = $staffConn->query("SELECT sit.*, si.item_name FROM store_inventory_transactions sit JOIN store_inventory si ON sit.item_id=si.id ORDER BY sit.created_at DESC LIMIT 50");
if ($r) while ($row = $r->fetch_assoc()) $transactions[] = $row;

$tab = $_GET['tab'] ?? $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.sk-content{margin-left:270px;padding:28px 32px;min-height:100vh;background:#f0f2f5}
@media(max-width:991px){.sk-content{margin-left:0!important;padding:16px!important}}
@media(max-width:767px){.sk-content{padding:12px!important}}

.sk-page-title{font-size:1.65rem;font-weight:700;color:#1a1d29;margin-bottom:4px}
.sk-page-sub{color:#6b7280;font-size:.92rem;margin-bottom:24px}

.sk-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:28px}
.sk-stat-card{background:#fff;border-radius:12px;padding:22px 20px;box-shadow:0 1px 4px rgba(0,0,0,.06);display:flex;align-items:center;gap:16px;transition:transform .15s,box-shadow .15s}
.sk-stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1)}
.sk-stat-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.35rem;color:#fff;flex-shrink:0}
.sk-stat-icon.blue{background:linear-gradient(135deg,#3b82f6,#2563eb)}
.sk-stat-icon.green{background:linear-gradient(135deg,#10b981,#059669)}
.sk-stat-icon.amber{background:linear-gradient(135deg,#f59e0b,#d97706)}
.sk-stat-icon.red{background:linear-gradient(135deg,#ef4444,#dc2626)}
.sk-stat-icon.purple{background:linear-gradient(135deg,#8b5cf6,#7c3aed)}
.sk-stat-icon.teal{background:linear-gradient(135deg,#14b8a6,#0d9488)}
.sk-stat-info h3{font-size:1.55rem;font-weight:700;color:#1a1d29;margin:0;line-height:1.2}
.sk-stat-info p{font-size:.82rem;color:#6b7280;margin:2px 0 0}

.sk-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:24px;overflow:hidden}
.sk-card-header{padding:18px 24px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between}
.sk-card-header h5{margin:0;font-size:1.05rem;font-weight:600;color:#1a1d29}
.sk-card-body{padding:20px 24px}

.sk-btn{display:inline-flex;align-items:center;gap:8px;padding:9px 20px;border-radius:8px;font-size:.88rem;font-weight:500;border:none;cursor:pointer;transition:all .15s;text-decoration:none}
.sk-btn-primary{background:#3b82f6;color:#fff}.sk-btn-primary:hover{background:#2563eb;color:#fff}
.sk-btn-success{background:#10b981;color:#fff}.sk-btn-success:hover{background:#059669;color:#fff}
.sk-btn-danger{background:#ef4444;color:#fff}.sk-btn-danger:hover{background:#dc2626;color:#fff}
.sk-btn-warning{background:#f59e0b;color:#1a1d29}.sk-btn-warning:hover{background:#d97706;color:#fff}
.sk-btn-outline{background:transparent;border:1px solid #d1d5db;color:#374151}.sk-btn-outline:hover{background:#f3f4f6}
.sk-btn-sm{padding:5px 12px;font-size:.8rem;border-radius:6px}

.sk-table{width:100%;border-collapse:collapse}
.sk-table th{background:#f8f9fa;color:#374151;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.4px;padding:12px 14px;border-bottom:2px solid #e5e7eb;text-align:left}
.sk-table td{padding:12px 14px;border-bottom:1px solid #f0f0f0;font-size:.9rem;color:#1f2937;vertical-align:middle}
.sk-table tbody tr:hover{background:#f8f9fb}

.sk-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.sk-badge-success{background:#d1fae5;color:#065f46}
.sk-badge-warning{background:#fef3c7;color:#92400e}
.sk-badge-danger{background:#fee2e2;color:#991b1b}
.sk-badge-info{background:#dbeafe;color:#1e40af}
.sk-badge-secondary{background:#e5e7eb;color:#374151}
.sk-badge-purple{background:#ede9fe;color:#5b21b6}

.sk-form-label{font-size:.85rem;font-weight:600;color:#374151;margin-bottom:6px}
.sk-form-input{width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem;color:#1f2937;background:#fff;transition:border-color .15s}
.sk-form-input:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.sk-form-select{width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem;color:#1f2937;background:#fff}

.sk-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.sk-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px}
@media(max-width:767px){.sk-grid-2,.sk-grid-3{grid-template-columns:1fr}}

.sk-empty{text-align:center;padding:40px 20px;color:#9ca3af}
.sk-empty i{font-size:2.5rem;margin-bottom:12px;display:block}
.sk-empty p{font-size:.95rem}

.sk-tabs{display:flex;gap:4px;margin-bottom:24px;flex-wrap:wrap}
.sk-tab{padding:10px 20px;border-radius:8px;font-size:.88rem;font-weight:500;color:#6b7280;background:#fff;border:1px solid #e5e7eb;cursor:pointer;transition:all .15s;text-decoration:none}
.sk-tab:hover{background:#f3f4f6;color:#1a1d29}
.sk-tab.active{background:#3b82f6;color:#fff;border-color:#3b82f6}

.sk-alert{padding:14px 18px;border-radius:10px;margin-bottom:16px;display:flex;align-items:center;gap:12px}
.sk-alert-warning{background:#fef3c7;border:1px solid #fcd34d;color:#92400e}
.sk-alert-danger{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.sk-alert-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}

.sk-request-card{background:#f8f9fb;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;margin-bottom:12px;transition:border-color .15s}
.sk-request-card:hover{border-color:#3b82f6}
.sk-request-card h6{font-size:.95rem;font-weight:600;color:#1a1d29;margin:0 0 6px}
.sk-request-card p{font-size:.82rem;color:#6b7280;margin:0}

.sk-req-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="sk-content">

<?php if ($msg): ?>
<div class="sk-alert <?= $msg['type'] === 'success' ? 'sk-alert-success' : 'sk-alert-danger' ?>">
    <i class="fas <?= $msg['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
    <span><?= $msg['text'] ?></span>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="sk-tabs">
    <a href="?tab=dashboard" class="sk-tab <?= $tab === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home me-1"></i> Dashboard</a>
    <a href="?tab=inventory" class="sk-tab <?= $tab === 'inventory' ? 'active' : '' ?>"><i class="fas fa-boxes me-1"></i> Inventory</a>
    <a href="?tab=categories" class="sk-tab <?= $tab === 'categories' ? 'active' : '' ?>"><i class="fas fa-tags me-1"></i> Categories</a>
    <a href="?tab=requests" class="sk-tab <?= $tab === 'requests' ? 'active' : '' ?>"><i class="fas fa-clipboard-list me-1"></i> Requests <span class="sk-badge sk-badge-warning" style="margin-left:4px"><?= count($pendingReqs) ?></span></a>
    <a href="?tab=transactions" class="sk-tab <?= $tab === 'transactions' ? 'active' : '' ?>"><i class="fas fa-history me-1"></i> Transactions</a>
</div>

<?php switch ($tab):

// ════════════════════════════════════════════
// DASHBOARD
// ════════════════════════════════════════════
case 'dashboard': ?>
<h1 class="sk-page-title">Store Dashboard</h1>
<p class="sk-page-sub">Overview of inventory, requests, and stock status</p>

<div class="sk-stat-grid">
    <div class="sk-stat-card"><div class="sk-stat-icon blue"><i class="fas fa-box"></i></div><div class="sk-stat-info"><h3><?= count($inventory) ?></h3><p>Inventory Items</p></div></div>
    <div class="sk-stat-card"><div class="sk-stat-icon amber"><i class="fas fa-clipboard-list"></i></div><div class="sk-stat-info"><h3><?= count($pendingReqs) ?></h3><p>Pending Requests</p></div></div>
    <div class="sk-stat-card"><div class="sk-stat-icon red"><i class="fas fa-exclamation-triangle"></i></div><div class="sk-stat-info"><h3><?= count($lowStock) ?></h3><p>Low Stock Items</p></div></div>
    <div class="sk-stat-card"><div class="sk-stat-icon purple"><i class="fas fa-clock"></i></div><div class="sk-stat-info"><h3><?= count($expiringItems) ?></h3><p>Expiring Soon</p></div></div>
    <div class="sk-stat-card"><div class="sk-stat-icon green"><i class="fas fa-check-circle"></i></div><div class="sk-stat-info"><h3><?= count(array_filter($fulfilledReqs, fn($r) => $r['status'] === 'fulfilled')) ?></h3><p>Fulfilled Requests</p></div></div>
</div>

<div class="sk-grid-2">
    <div class="sk-card">
        <div class="sk-card-header"><h5><i class="fas fa-exclamation-triangle" style="color:#ef4444;margin-right:8px"></i>Low Stock Alerts</h5></div>
        <div class="sk-card-body">
            <?php if (empty($lowStock)): ?>
            <div class="sk-empty"><i class="fas fa-check-circle" style="color:#10b981"></i><p>All items are well stocked</p></div>
            <?php else: ?>
            <?php foreach ($lowStock as $item): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0">
                <div><strong style="color:#1a1d29"><?= htmlspecialchars($item['item_name']) ?></strong><br><small style="color:#6b7280"><?= htmlspecialchars($item['category_name']) ?> | Min: <?= $item['reorder_level'] ?></small></div>
                <span class="sk-badge sk-badge-danger"><?= $item['quantity'] ?> left</span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="sk-card">
        <div class="sk-card-header"><h5><i class="fas fa-clock" style="color:#f59e0b;margin-right:8px"></i>Expiring Soon (90 days)</h5></div>
        <div class="sk-card-body">
            <?php if (empty($expiringItems)): ?>
            <div class="sk-empty"><i class="fas fa-check-circle" style="color:#10b981"></i><p>No items expiring soon</p></div>
            <?php else: ?>
            <?php foreach ($expiringItems as $item):
                $daysLeft = (int)((strtotime($item['expiry_date']) - time()) / 86400);
                $cls = $daysLeft <= 30 ? 'sk-badge-danger' : ($daysLeft <= 60 ? 'sk-badge-warning' : 'sk-badge-info');
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0">
                <div><strong style="color:#1a1d29"><?= htmlspecialchars($item['item_name']) ?></strong><br><small style="color:#6b7280">Batch: <?= htmlspecialchars($item['batch_number'] ?? 'N/A') ?></small></div>
                <span class="sk-badge <?= $cls ?>"><?= $daysLeft ?> days</span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="sk-card">
    <div class="sk-card-header"><h5><i class="fas fa-clipboard-list" style="color:#3b82f6;margin-right:8px"></i>Pending Requests</h5><a href="?tab=requests" class="sk-btn sk-btn-outline sk-btn-sm">View All</a></div>
    <div class="sk-card-body">
        <?php if (empty($pendingReqs)): ?>
        <div class="sk-empty"><i class="fas fa-check-circle" style="color:#10b981"></i><p>No pending requests</p></div>
        <?php else: ?>
        <?php foreach (array_slice($pendingReqs, 0, 5) as $req): ?>
        <?php $urgBadge = $req['urgency'] === 'urgent' ? 'sk-badge-danger' : ($req['urgency'] === 'high' ? 'sk-badge-warning' : 'sk-badge-info'); ?>
        <div class="sk-request-card">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div><strong><?= htmlspecialchars($req['request_number']) ?></strong> <span class="sk-badge <?= $urgBadge ?>"><?= $req['urgency'] ?></span></div>
                <span class="sk-badge <?= $req['status'] === 'pending_approval' ? 'sk-badge-purple' : 'sk-badge-warning' ?>"><?= str_replace('_', ' ', $req['status']) ?></span>
            </div>
            <p style="margin-top:6px"><?= htmlspecialchars($req['requester_name'] ?? 'Unknown') ?> | <?= htmlspecialchars($req['department'] ?? 'N/A') ?> | <?= date('d M Y H:i', strtotime($req['created_at'])) ?></p>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php break;

// ════════════════════════════════════════════
// INVENTORY
// ════════════════════════════════════════════
case 'inventory': ?>
<h1 class="sk-page-title">Inventory Management</h1>
<p class="sk-page-sub">Manage all stock items, expiry dates, and batch numbers</p>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <input type="text" id="invFilter" class="sk-form-input" style="width:300px" placeholder="Search items...">
    <button class="sk-btn sk-btn-success" onclick="document.getElementById('addItemModal').style.display='flex'"><i class="fas fa-plus"></i> Add Item</button>
</div>

<div class="sk-card">
    <div class="sk-card-body" style="padding:0;overflow-x:auto">
        <table class="sk-table" id="invTable">
            <thead><tr><th>#</th><th>Code</th><th>Item Name</th><th>Category</th><th>Qty</th><th>Min</th><th>Unit</th><th>Cost</th><th>Batch</th><th>Expiry</th><th>Supplier</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($inventory)): ?>
            <tr><td colspan="14" class="sk-empty"><i class="fas fa-box"></i><p>No inventory items</p></td></tr>
            <?php else: ?>
            <?php foreach ($inventory as $i => $item):
                $isLow = $item['quantity'] <= $item['reorder_level'];
                $isExpiring = $item['expiry_date'] && strtotime($item['expiry_date']) <= strtotime('+90 days');
                $isExpired = $item['expiry_date'] && strtotime($item['expiry_date']) < time();
            ?>
            <tr class="inv-row" data-name="<?= strtolower(htmlspecialchars($item['item_name'].' '.$item['category_name'].' '.($item['batch_number'] ?? '').' '.($item['supplier'] ?? ''))) ?>">
                <td><?= $i + 1 ?></td>
                <td><small><?= htmlspecialchars($item['item_code'] ?? '') ?></small></td>
                <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                <td><small><?= htmlspecialchars($item['category_name'] ?? '') ?></small></td>
                <td><span class="sk-badge <?= $isLow ? 'sk-badge-danger' : 'sk-badge-success' ?>"><?= number_format($item['quantity']) ?></span></td>
                <td><?= number_format($item['reorder_level']) ?></td>
                <td><?= htmlspecialchars($item['unit']) ?></td>
                <td>UGX <?= number_format($item['unit_cost']) ?></td>
                <td><small><?= htmlspecialchars($item['batch_number'] ?? '-') ?></small></td>
                <td>
                    <?php if ($item['expiry_date']): ?>
                    <span class="sk-badge <?= $isExpired ? 'sk-badge-danger' : ($isExpiring ? 'sk-badge-warning' : 'sk-badge-info') ?>"><?= date('M d, Y', strtotime($item['expiry_date'])) ?></span>
                    <?php else: ?>
                    <span style="color:#9ca3af">-</span>
                    <?php endif; ?>
                </td>
                <td><small><?= htmlspecialchars($item['supplier'] ?? '-') ?></small></td>
                <td><small><?= htmlspecialchars($item['location'] ?? '') ?></small></td>
                <td><span class="sk-badge <?= $item['status'] === 'active' ? 'sk-badge-success' : 'sk-badge-secondary' ?>"><?= $item['status'] ?></span></td>
                <td style="display:flex;gap:4px">
                    <button class="sk-btn sk-btn-outline sk-btn-sm" onclick="openAddStock(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['item_name'])) ?>')" title="Add Stock"><i class="fas fa-plus"></i></button>
                    <button class="sk-btn sk-btn-outline sk-btn-sm" onclick="openRemoveStock(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['item_name'])) ?>', <?= $item['quantity'] ?>)" title="Remove"><i class="fas fa-minus"></i></button>
                    <button class="sk-btn sk-btn-outline sk-btn-sm" onclick="openEditItem(<?= htmlspecialchars(json_encode($item)) ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Deactivate this item?')">
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                        <button type="submit" class="sk-btn sk-btn-danger sk-btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" style="border-radius:12px;border:none">
        <div style="padding:18px 24px;background:#10b981;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
            <h5 style="margin:0"><i class="fas fa-plus" style="margin-right:8px"></i>Add New Item</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div style="padding:24px">
            <input type="hidden" name="action" value="add_item">
            <div class="sk-grid-3">
                <div class="mb-3"><label class="sk-form-label">Item Code</label><input type="text" name="item_code" class="sk-form-input" placeholder="e.g. STA-001"></div>
                <div class="mb-3"><label class="sk-form-label">Item Name *</label><input type="text" name="item_name" class="sk-form-input" required placeholder="Enter item name"></div>
                <div class="mb-3"><label class="sk-form-label">Category</label><select name="category_id" class="sk-form-select"><option value="0">-- Select --</option><?php foreach ($categories as $c): if ($c['status'] !== 'active') continue; ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="sk-grid-3">
                <div class="mb-3"><label class="sk-form-label">Unit</label><input type="text" name="unit" class="sk-form-input" value="pcs" placeholder="pcs, kg, box"></div>
                <div class="mb-3"><label class="sk-form-label">Quantity</label><input type="number" name="quantity" class="sk-form-input" min="0" step="any" value="0"></div>
                <div class="mb-3"><label class="sk-form-label">Reorder Level</label><input type="number" name="reorder_level" class="sk-form-input" min="0" value="10"></div>
            </div>
            <div class="sk-grid-3">
                <div class="mb-3"><label class="sk-form-label">Unit Cost (UGX)</label><input type="number" name="unit_cost" class="sk-form-input" min="0" step="100" value="0"></div>
                <div class="mb-3"><label class="sk-form-label">Location</label><input type="text" name="location" class="sk-form-input" placeholder="e.g. Warehouse A"></div>
                <div class="mb-3"><label class="sk-form-label">Supplier</label><input type="text" name="supplier" class="sk-form-input" placeholder="Supplier name"></div>
            </div>
            <div class="sk-grid-3">
                <div class="mb-3"><label class="sk-form-label">Batch Number</label><input type="text" name="batch_number" class="sk-form-input" placeholder="e.g. BATCH-2026-001"></div>
                <div class="mb-3"><label class="sk-form-label">Expiry Date</label><input type="date" name="expiry_date" class="sk-form-input"></div>
                <div class="mb-3"></div>
            </div>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="sk-btn sk-btn-outline" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="sk-btn sk-btn-success"><i class="fas fa-save"></i> Save Item</button>
        </div>
    </form>
</div></div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" style="border-radius:12px;border:none">
        <div style="padding:18px 24px;background:#3b82f6;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
            <h5 style="margin:0"><i class="fas fa-edit" style="margin-right:8px"></i>Edit Item</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div style="padding:24px">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="item_id" id="editItemId">
            <div class="sk-grid-3">
                <div class="mb-3"><label class="sk-form-label">Item Code</label><input type="text" name="item_code" id="editItemCode" class="sk-form-input"></div>
                <div class="mb-3"><label class="sk-form-label">Item Name *</label><input type="text" name="item_name" id="editItemName" class="sk-form-input" required></div>
                <div class="mb-3"><label class="sk-form-label">Category</label><select name="category_id" id="editCategory" class="sk-form-select"><option value="0">-- Select --</option><?php foreach ($categories as $c): if ($c['status'] !== 'active') continue; ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="sk-grid-3">
                <div class="mb-3"><label class="sk-form-label">Unit</label><input type="text" name="unit" id="editUnit" class="sk-form-input"></div>
                <div class="mb-3"><label class="sk-form-label">Quantity</label><input type="number" name="quantity" id="editQty" class="sk-form-input" min="0" step="any"></div>
                <div class="mb-3"><label class="sk-form-label">Reorder Level</label><input type="number" name="reorder_level" id="editReorder" class="sk-form-input" min="0"></div>
            </div>
            <div class="sk-grid-3">
                <div class="mb-3"><label class="sk-form-label">Unit Cost (UGX)</label><input type="number" name="unit_cost" id="editCost" class="sk-form-input" min="0" step="100"></div>
                <div class="mb-3"><label class="sk-form-label">Location</label><input type="text" name="location" id="editLocation" class="sk-form-input"></div>
                <div class="mb-3"><label class="sk-form-label">Supplier</label><input type="text" name="supplier" id="editSupplier" class="sk-form-input"></div>
            </div>
            <div class="sk-grid-3">
                <div class="mb-3"><label class="sk-form-label">Batch Number</label><input type="text" name="batch_number" id="editBatch" class="sk-form-input"></div>
                <div class="mb-3"><label class="sk-form-label">Expiry Date</label><input type="date" name="expiry_date" id="editExpiry" class="sk-form-input"></div>
                <div class="mb-3"><label class="sk-form-label">Status</label><select name="status" id="editStatus" class="sk-form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            </div>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="sk-btn sk-btn-outline" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="sk-btn sk-btn-primary"><i class="fas fa-save"></i> Update Item</button>
        </div>
    </form>
</div></div>

<!-- Stock Modals -->
<div class="modal fade" id="addStockModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content" style="border-radius:12px;border:none">
        <div style="padding:18px 24px;background:#10b981;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
            <h5 style="margin:0"><i class="fas fa-plus-circle" style="margin-right:8px"></i>Add Stock</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div style="padding:24px">
            <input type="hidden" name="action" value="add_stock">
            <input type="hidden" name="item_id" id="addStockItemId">
            <p id="addStockItemName" class="fw-bold" style="color:#1a1d29"></p>
            <div class="mb-3"><label class="sk-form-label">Quantity to Add</label><input type="number" name="quantity" class="sk-form-input" min="0.1" step="any" required></div>
            <div class="mb-3"><label class="sk-form-label">Reason</label><input type="text" name="reason" class="sk-form-input" placeholder="e.g. New delivery" required></div>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="sk-btn sk-btn-outline" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="sk-btn sk-btn-success"><i class="fas fa-check"></i> Add</button>
        </div>
    </form>
</div></div>

<div class="modal fade" id="removeStockModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content" style="border-radius:12px;border:none">
        <div style="padding:18px 24px;background:#ef4444;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
            <h5 style="margin:0"><i class="fas fa-minus-circle" style="margin-right:8px"></i>Remove Stock</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div style="padding:24px">
            <input type="hidden" name="action" value="remove_stock">
            <input type="hidden" name="item_id" id="rmItemId">
            <p id="rmItemName" class="fw-bold" style="color:#1a1d29"></p>
            <p id="rmCurrentQty" style="color:#6b7280;font-size:.85rem"></p>
            <div class="mb-3"><label class="sk-form-label">Quantity to Remove</label><input type="number" name="quantity" id="rmQuantity" class="sk-form-input" min="0.1" step="any" required></div>
            <div class="mb-3"><label class="sk-form-label">Reason</label><input type="text" name="reason" class="sk-form-input" placeholder="e.g. Usage, damaged" required></div>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="sk-btn sk-btn-outline" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="sk-btn sk-btn-danger"><i class="fas fa-check"></i> Remove</button>
        </div>
    </form>
</div></div>
<?php break;

// ════════════════════════════════════════════
// CATEGORIES
// ════════════════════════════════════════════
case 'categories': ?>
<h1 class="sk-page-title">Stock Categories</h1>
<p class="sk-page-sub">Organize inventory items by category</p>

<div class="sk-grid-2">
    <div class="sk-card">
        <div class="sk-card-header"><h5>Categories</h5><button class="sk-btn sk-btn-success sk-btn-sm" onclick="document.getElementById('addCatModal').style.display='flex'"><i class="fas fa-plus"></i> Add</button></div>
        <div class="sk-card-body" style="padding:0">
            <table class="sk-table">
                <thead><tr><th>#</th><th>Category</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($categories)): ?>
                <tr><td colspan="5" class="sk-empty"><p>No categories</p></td></tr>
                <?php else: ?>
                <?php foreach ($categories as $i => $c): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($c['category_name']) ?></strong></td>
                    <td><small><?= htmlspecialchars($c['description'] ?? '-') ?></small></td>
                    <td><span class="sk-badge <?= $c['status'] === 'active' ? 'sk-badge-success' : 'sk-badge-secondary' ?>"><?= $c['status'] ?></span></td>
                    <td>
                        <?php if ($c['status'] === 'active'): ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Deactivate this category?')">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="sk-btn sk-btn-danger sk-btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="sk-card">
        <div class="sk-card-header"><h5>Items by Category</h5></div>
        <div class="sk-card-body">
            <?php foreach ($categories as $c): if ($c['status'] !== 'active') continue; ?>
            <?php $catItems = array_filter($inventory, fn($i) => (int)$i['category_id'] === (int)$c['id']); ?>
            <div style="padding:10px 0;border-bottom:1px solid #f0f0f0">
                <div style="display:flex;justify-content:space-between">
                    <strong style="color:#1a1d29"><?= htmlspecialchars($c['category_name']) ?></strong>
                    <span class="sk-badge sk-badge-info"><?= count($catItems) ?> items</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="addCatModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content" style="border-radius:12px;border:none">
        <div style="padding:18px 24px;background:#10b981;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
            <h5 style="margin:0"><i class="fas fa-plus" style="margin-right:8px"></i>Add Category</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div style="padding:24px">
            <input type="hidden" name="action" value="add_category">
            <div class="mb-3"><label class="sk-form-label">Category Name *</label><input type="text" name="category_name" class="sk-form-input" required placeholder="e.g. Office Supplies"></div>
            <div class="mb-3"><label class="sk-form-label">Description</label><textarea name="description" class="sk-form-input" rows="2" placeholder="Optional description"></textarea></div>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="sk-btn sk-btn-outline" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="sk-btn sk-btn-success"><i class="fas fa-save"></i> Save</button>
        </div>
    </form>
</div></div>
<?php break;

// ════════════════════════════════════════════
// REQUESTS
// ════════════════════════════════════════════
case 'requests': ?>
<h1 class="sk-page-title">Store Requests</h1>
<p class="sk-page-sub">Create, manage, and submit requests to Director General</p>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
    <button class="sk-btn sk-btn-primary" onclick="document.getElementById('addRequestModal').style.display='flex'"><i class="fas fa-plus"></i> New Request</button>
</div>

<?php if (empty($pendingReqs)): ?>
<div class="sk-card"><div class="sk-card-body"><div class="sk-empty"><i class="fas fa-check-circle" style="color:#10b981"></i><p>No pending requests</p></div></div></div>
<?php else: ?>
<?php foreach ($pendingReqs as $req):
    $urgBadge = $req['urgency'] === 'urgent' ? 'sk-badge-danger' : ($req['urgency'] === 'high' ? 'sk-badge-warning' : ($req['urgency'] === 'medium' ? 'sk-badge-info' : 'sk-badge-secondary'));
    $reqItems = $staffConn->query("SELECT sri.*, si.item_name, si.unit, si.quantity as avail_qty FROM store_request_items sri JOIN store_inventory si ON sri.item_id=si.id WHERE sri.request_id={$req['id']}");
?>
<div class="sk-card">
    <div class="sk-card-header">
        <div>
            <strong style="color:#1a1d29;font-size:1.05rem"><?= htmlspecialchars($req['request_number']) ?></strong>
            <span class="sk-badge <?= $urgBadge ?>" style="margin-left:8px"><?= $req['urgency'] ?></span>
            <span class="sk-badge <?= $req['status'] === 'pending_approval' ? 'sk-badge-purple' : 'sk-badge-warning' ?>" style="margin-left:4px"><?= str_replace('_', ' ', $req['status']) ?></span>
        </div>
        <small style="color:#6b7280"><?= date('d M Y H:i', strtotime($req['created_at'])) ?></small>
    </div>
    <div class="sk-card-body">
        <div style="display:flex;gap:24px;margin-bottom:12px;color:#6b7280;font-size:.88rem">
            <span><i class="fas fa-user me-1"></i><?= htmlspecialchars($req['requester_name'] ?? 'Unknown') ?></span>
            <span><i class="fas fa-building me-1"></i><?= htmlspecialchars($req['department'] ?? 'N/A') ?></span>
        </div>
        <?php if ($req['notes']): ?><p style="color:#6b7280;font-size:.88rem;margin-bottom:12px"><i class="fas fa-comment me-1"></i><?= htmlspecialchars($req['notes']) ?></p><?php endif; ?>

        <table class="sk-table" style="margin-bottom:12px">
            <thead><tr><th>Item</th><th>Requested</th><th>Available</th><th>Fulfilled</th><th>Status</th></tr></thead>
            <tbody>
            <?php while ($ri = $reqItems->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($ri['item_name']) ?></strong></td>
                <td><?= number_format($ri['quantity_requested']) ?> <?= htmlspecialchars($ri['unit']) ?></td>
                <td><span class="sk-badge <?= $ri['avail_qty'] >= $ri['quantity_requested'] ? 'sk-badge-success' : 'sk-badge-danger' ?>"><?= number_format($ri['avail_qty']) ?></span></td>
                <td><?= number_format($ri['quantity_fulfilled']) ?></td>
                <td><span class="sk-badge <?= $ri['status'] === 'fulfilled' ? 'sk-badge-success' : 'sk-badge-warning' ?>"><?= $ri['status'] ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <div class="sk-req-actions">
            <?php if ($req['status'] === 'pending'): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Submit this request for DG approval?')">
                <input type="hidden" name="action" value="submit_for_dg_approval">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                <button type="submit" class="sk-btn sk-btn-warning sk-btn-sm"><i class="fas fa-crown me-1"></i> Submit for DG Approval</button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Mark as fulfilled?')">
                <input type="hidden" name="action" value="fulfill_request">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                <button type="submit" class="sk-btn sk-btn-success sk-btn-sm"><i class="fas fa-check me-1"></i> Mark Fulfilled</button>
            </form>
            <button class="sk-btn sk-btn-danger sk-btn-sm" onclick="openReject(<?= $req['id'] ?>)"><i class="fas fa-times me-1"></i> Reject</button>
            <?php elseif ($req['status'] === 'pending_approval'): ?>
            <span class="sk-badge sk-badge-purple" style="padding:8px 16px;font-size:.85rem"><i class="fas fa-clock me-1"></i> Awaiting DG Approval</span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($fulfilledReqs)): ?>
<div class="sk-card">
    <div class="sk-card-header"><h5><i class="fas fa-history me-2" style="color:#6b7280"></i>Recent History</h5></div>
    <div class="sk-card-body" style="padding:0">
        <table class="sk-table">
            <thead><tr><th>Request</th><th>Requester</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($fulfilledReqs as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['request_number']) ?></strong></td>
                <td><?= htmlspecialchars($r['requester_name'] ?? '') ?></td>
                <td><span class="sk-badge <?= $r['status'] === 'fulfilled' ? 'sk-badge-success' : 'sk-badge-danger' ?>"><?= $r['status'] ?></span></td>
                <td><small><?= date('d M Y', strtotime($r['updated_at'])) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Add Request Modal -->
<div class="modal fade" id="addRequestModal" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" style="border-radius:12px;border:none">
        <div style="padding:18px 24px;background:#f59e0b;color:#1a1d29;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
            <h5 style="margin:0"><i class="fas fa-plus" style="margin-right:8px"></i>New Store Request</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div style="padding:24px">
            <input type="hidden" name="action" value="add_request">
            <div class="sk-grid-2">
                <div class="mb-3"><label class="sk-form-label">Department *</label><input type="text" name="department" class="sk-form-input" required placeholder="e.g. Nursing Department"></div>
                <div class="mb-3"><label class="sk-form-label">Urgency</label><select name="urgency" class="sk-form-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
            </div>
            <div class="mb-3"><label class="sk-form-label">Notes</label><textarea name="notes" class="sk-form-input" rows="2" placeholder="Optional notes..."></textarea></div>
            <label class="sk-form-label">Request Items</label>
            <div id="reqItemsContainer">
                <div class="d-flex gap-2 mb-2 req-item-row align-items-center">
                    <select name="req_items[0][item_id]" class="sk-form-select" style="flex:2" required>
                        <option value="">-- Select Item --</option>
                        <?php foreach ($inventory as $item): ?>
                        <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['item_name']) ?> (<?= number_format($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="req_items[0][quantity]" class="sk-form-input" style="width:90px" placeholder="Qty" min="1" required>
                    <input type="text" name="req_items[0][notes]" class="sk-form-input" style="flex:1" placeholder="Note">
                    <button type="button" class="sk-btn sk-btn-danger sk-btn-sm" onclick="this.closest('.req-item-row').remove()"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <button type="button" class="sk-btn sk-btn-outline sk-btn-sm" style="margin-top:8px" onclick="addReqItemRow()"><i class="fas fa-plus me-1"></i>Add Another Item</button>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="sk-btn sk-btn-outline" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="sk-btn sk-btn-warning"><i class="fas fa-save"></i> Submit Request</button>
        </div>
    </form>
</div></div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1"><div class="modal-dialog">
    <form method="POST" class="modal-content" style="border-radius:12px;border:none">
        <div style="padding:18px 24px;background:#ef4444;color:#fff;border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:space-between">
            <h5 style="margin:0"><i class="fas fa-times-circle" style="margin-right:8px"></i>Reject Request</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div style="padding:24px">
            <input type="hidden" name="action" value="reject_request">
            <input type="hidden" name="request_id" id="rejReqId">
            <div class="mb-3"><label class="sk-form-label">Reason for Rejection *</label><textarea name="rejection_reason" class="sk-form-input" rows="3" required placeholder="Explain why..."></textarea></div>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="sk-btn sk-btn-outline" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="sk-btn sk-btn-danger"><i class="fas fa-check"></i> Reject</button>
        </div>
    </form>
</div></div>
<?php break;

// ════════════════════════════════════════════
// TRANSACTIONS
// ════════════════════════════════════════════
case 'transactions': ?>
<h1 class="sk-page-title">Transaction History</h1>
<p class="sk-page-sub">Complete log of all stock movements</p>

<div class="sk-card">
    <div class="sk-card-body" style="padding:0;overflow-x:auto">
        <table class="sk-table">
            <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>Reason</th><th>By</th></tr></thead>
            <tbody>
            <?php if (empty($transactions)): ?>
            <tr><td colspan="8" class="sk-empty"><i class="fas fa-history"></i><p>No transactions recorded</p></td></tr>
            <?php else: ?>
            <?php foreach ($transactions as $t):
                $tBadge = in_array($t['transaction_type'], ['add', 'order_received']) ? 'sk-badge-success' : (in_array($t['transaction_type'], ['remove', 'request_fulfilled']) ? 'sk-badge-warning' : 'sk-badge-secondary');
            ?>
            <tr>
                <td><small><?= date('d M H:i', strtotime($t['created_at'])) ?></small></td>
                <td><strong><?= htmlspecialchars($t['item_name']) ?></strong></td>
                <td><span class="sk-badge <?= $tBadge ?>"><?= str_replace('_', ' ', $t['transaction_type']) ?></span></td>
                <td><?= number_format($t['quantity']) ?></td>
                <td><?= number_format($t['quantity_before']) ?></td>
                <td><?= number_format($t['quantity_after']) ?></td>
                <td><small><?= htmlspecialchars($t['reason'] ?? '') ?></small></td>
                <td><small><?= $t['created_by'] ? '#' . $t['created_by'] : '-' ?></small></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php break;

default: ?>
<h1 class="sk-page-title">Store Management</h1>
<p class="sk-page-sub">Select a tab above to get started</p>
<?php break;
endswitch; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('invFilter')?.addEventListener('keyup', function() {
    let q = this.value.toLowerCase();
    document.querySelectorAll('.inv-row').forEach(r => r.style.display = r.dataset.name.includes(q) ? '' : 'none');
});

function openAddStock(id, name) { document.getElementById('addStockItemId').value = id; document.getElementById('addStockItemName').textContent = name; new bootstrap.Modal(document.getElementById('addStockModal')).show(); }
function openRemoveStock(id, name, qty) { document.getElementById('rmItemId').value = id; document.getElementById('rmItemName').textContent = name; document.getElementById('rmCurrentQty').textContent = 'Current stock: ' + qty; document.getElementById('rmQuantity').max = qty; new bootstrap.Modal(document.getElementById('removeStockModal')).show(); }
function openReject(id) { document.getElementById('rejReqId').value = id; new bootstrap.Modal(document.getElementById('rejectModal')).show(); }

function openEditItem(item) {
    document.getElementById('editItemId').value = item.id;
    document.getElementById('editItemCode').value = item.item_code || '';
    document.getElementById('editItemName').value = item.item_name;
    document.getElementById('editCategory').value = item.category_id;
    document.getElementById('editUnit').value = item.unit;
    document.getElementById('editQty').value = item.quantity;
    document.getElementById('editReorder').value = item.reorder_level;
    document.getElementById('editCost').value = item.unit_cost;
    document.getElementById('editLocation').value = item.location || '';
    document.getElementById('editBatch').value = item.batch_number || '';
    document.getElementById('editExpiry').value = item.expiry_date || '';
    document.getElementById('editSupplier').value = item.supplier || '';
    document.getElementById('editStatus').value = item.status;
    new bootstrap.Modal(document.getElementById('editItemModal')).show();
}

let reqItemIdx = 1;
function addReqItemRow() {
    let options = '<option value="">-- Select Item --</option>';
    <?php foreach ($inventory as $item): ?>
    options += '<option value="<?= $item['id'] ?>"><?= htmlspecialchars(addslashes($item['item_name'])) ?> (<?= number_format($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?>)</option>';
    <?php endforeach; ?>
    let html = '<div class="d-flex gap-2 mb-2 req-item-row align-items-center">' +
        '<select name="req_items[' + reqItemIdx + '][item_id]" class="sk-form-select" style="flex:2" required>' + options + '</select>' +
        '<input type="number" name="req_items[' + reqItemIdx + '][quantity]" class="sk-form-input" style="width:90px" placeholder="Qty" min="1" required>' +
        '<input type="text" name="req_items[' + reqItemIdx + '][notes]" class="sk-form-input" style="flex:1" placeholder="Note">' +
        '<button type="button" class="sk-btn sk-btn-danger sk-btn-sm" onclick="this.closest(\'.req-item-row\').remove()"><i class="fas fa-times"></i></button></div>';
    document.getElementById('reqItemsContainer').insertAdjacentHTML('beforeend', html);
    reqItemIdx++;
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
