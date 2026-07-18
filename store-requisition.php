<?php
/**
 * Department Store Requisition
 * All departments can request items from the store
 */
require_once __DIR__ . '/includes/staff_dashboard_access.php';
require_once __DIR__ . '/includes/csrf_helper.php';

$ctx = bootstrapStaffDashboard();
$staffConn = $ctx['staff'];
$user = $ctx['user'];
$userId = (int)($user['id'] ?? 0);
$userName = $user['full_name'] ?? 'Staff';
$userRole = $_SESSION['role'] ?? '';
$userDept = $user['department'] ?? '';

if (!$staffConn) die('Database connection failed.');

$msg = $_SESSION['store_msg'] ?? null;
unset($_SESSION['store_msg']);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken()) { $msg = ['type' => 'error', 'text' => 'Invalid CSRF token.']; }
    else {
        $action = $_POST['action'];
        if ($action === 'submit_requisition') {
            $department = trim($_POST['department'] ?? '');
            $urgency = trim($_POST['urgency'] ?? 'medium');
            $notes = trim($_POST['notes'] ?? '');
            $submitDg = !empty($_POST['submit_for_dg']);
            $reqItems = $_POST['req_items'] ?? [];

            if (empty($department)) {
                $msg = ['type' => 'error', 'text' => 'Department is required.'];
            } elseif (empty($reqItems)) {
                $msg = ['type' => 'error', 'text' => 'Select at least one item.'];
            } else {
                $validItems = [];
                foreach ($reqItems as $ri) {
                    $itemId = (int)($ri['item_id'] ?? 0);
                    $qty = (float)($ri['quantity'] ?? 0);
                    if ($itemId > 0 && $qty > 0) {
                        $check = $staffConn->prepare("SELECT id FROM store_inventory WHERE id=? AND status='active'");
                        if ($check) {
                            $check->bind_param("i", $itemId);
                            $check->execute();
                            if ($check->get_result()->num_rows > 0) {
                                $validItems[] = ['item_id' => $itemId, 'quantity' => $qty, 'notes' => substr(trim($ri['notes'] ?? ''), 0, 255)];
                            }
                            $check->close();
                        }
                    }
                }
                if (empty($validItems)) {
                    $msg = ['type' => 'error', 'text' => 'No valid items selected.'];
                } else {
                    $reqNum = 'SRQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                    $status = $submitDg ? 'pending_approval' : 'pending';
                    $staffConn->begin_transaction();
                    try {
                        $stmt = $staffConn->prepare("INSERT INTO store_requests (request_number, requested_by, requester_name, department, notes, urgency, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sisssss", $reqNum, $userId, $userName, $department, $notes, $urgency, $status);
                        $stmt->execute();
                        $reqId = $staffConn->insert_id;
                        $stmt->close();
                        $ins = $staffConn->prepare("INSERT INTO store_request_items (request_id, item_id, quantity_requested, notes) VALUES (?, ?, ?, ?)");
                        foreach ($validItems as $vi) {
                            $ins->bind_param("iids", $reqId, $vi['item_id'], $vi['quantity'], $vi['notes']);
                            $ins->execute();
                        }
                        $ins->close();
                        $staffConn->commit();
                        require_once __DIR__ . '/includes/notification_helper.php';
                        $notifMsg = "$userName from $department requested items. Urgency: $urgency.";
                        createNotification("New Store Requisition: $reqNum", $notifMsg, 'dashboards/storekeeper.php', 'info', 'fas fa-clipboard-list');
                        $msg = $submitDg
                            ? ['type' => 'success', 'text' => "Requisition <strong>$reqNum</strong> submitted for <strong>DG Approval</strong>!"]
                            : ['type' => 'success', 'text' => "Requisition <strong>$reqNum</strong> submitted! The storekeeper will process it."];
                    } catch (Exception $e) {
                        $staffConn->rollback();
                        $msg = ['type' => 'error', 'text' => 'Failed: ' . $e->getMessage()];
                    }
                }
            }
        }
    }
    header('Location: store-requisition.php');
    exit;
}

// Load categories and items
$categories = [];
$r = $staffConn->query("SELECT id, category_name, icon FROM store_categories WHERE status='active' ORDER BY category_name");
if ($r) while ($row = $r->fetch_assoc()) $categories[] = $row;

$allItems = [];
$r = $staffConn->query("SELECT id, category_id, item_name, unit, quantity FROM store_inventory WHERE status='active' ORDER BY item_name");
if ($r) while ($row = $r->fetch_assoc()) $allItems[] = $row;

$itemsByCat = [];
foreach ($allItems as $item) {
    $itemsByCat[$item['category_id']][] = $item;
}

// My recent requests
$myRequests = [];
$r = $staffConn->query("SELECT sr.*, COUNT(sri.id) as total_items FROM store_requests sr LEFT JOIN store_request_items sri ON sr.id=sri.request_id WHERE sr.requested_by=" . intval($userId) . " GROUP BY sr.id ORDER BY sr.created_at DESC LIMIT 20");
if ($r) while ($row = $r->fetch_assoc()) $myRequests[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/includes/dashboard_head.php'; ?>
<style>
.srq-content{margin-left:var(--sidebar-w,270px);padding:24px;min-height:100vh;background:#f0f2f5}
@media(max-width:991px){.srq-content{margin-left:0!important;padding:16px!important}}
.srq-header{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:20px 28px;border-radius:14px;margin-bottom:24px}
.srq-header h1{margin:0;font-size:22px;font-weight:700}
.srq-header p{margin:4px 0 0;opacity:.85;font-size:13px}
.srq-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:20px;overflow:hidden}
.srq-card-header{padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between}
.srq-card-header h5{margin:0;font-size:1rem;font-weight:600}
.srq-card-body{padding:20px}
.srq-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:8px;font-size:.85rem;font-weight:500;border:none;cursor:pointer;transition:all .15s;text-decoration:none}
.srq-btn-primary{background:#f59e0b;color:#fff}.srq-btn-primary:hover{background:#d97706;color:#fff}
.srq-btn-success{background:#10b981;color:#fff}.srq-btn-success:hover{background:#059669;color:#fff}
.srq-btn-outline{background:transparent;border:1px solid #d1d5db;color:#374151}.srq-btn-outline:hover{background:#f3f4f6}
.srq-btn-sm{padding:5px 12px;font-size:.8rem}
.srq-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.srq-badge-success{background:#d1fae5;color:#065f46}
.srq-badge-warning{background:#fef3c7;color:#92400e}
.srq-badge-danger{background:#fee2e2;color:#991b1b}
.srq-badge-info{background:#dbeafe;color:#1e40af}
.srq-badge-purple{background:#ede9fe;color:#5b21b6}
.srq-form-label{font-size:.85rem;font-weight:600;color:#374151;margin-bottom:6px}
.srq-form-input{width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem;color:#1f2937;background:#fff;transition:border-color .15s}
.srq-form-input:focus{outline:none;border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.1)}
.srq-alert{padding:14px 18px;border-radius:10px;margin-bottom:16px;display:flex;align-items:center;gap:12px}
.srq-alert-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.srq-alert-danger{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.srq-table{width:100%;border-collapse:collapse}
.srq-table th{background:#f8f9fa;color:#374151;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.4px;padding:12px 14px;border-bottom:2px solid #e5e7eb;text-align:left}
.srq-table td{padding:12px 14px;border-bottom:1px solid #f0f0f0;font-size:.88rem;color:#1f2937}
.srq-table tbody tr:hover{background:#f8f9fb}
.srq-cat-label{font-weight:700;color:#d97706;font-size:.85rem;padding:10px 0 4px;border-bottom:2px solid #fde68a;margin-bottom:6px;margin-top:8px}
.srq-item-row{background:#f8f9fa;border-radius:8px;padding:10px 12px;margin-bottom:4px;display:flex;align-items:center;gap:10px}
.srq-item-row:hover{background:#fef3c7}
.srq-item-name{flex:1;font-weight:500;font-size:.88rem}
.srq-item-unit{color:#92400e;font-size:.8rem;min-width:50px}
.srq-qty-input{width:80px;text-align:center;font-size:.85rem}
.srq-tabs{display:flex;gap:4px;margin-bottom:24px;flex-wrap:wrap}
.srq-tab{padding:10px 20px;border-radius:8px;font-size:.88rem;font-weight:500;color:#6b7280;background:#fff;border:1px solid #e5e7eb;cursor:pointer;transition:all .15s;text-decoration:none}
.srq-tab:hover{background:#f3f4f6;color:#1a1d29}
.srq-tab.active{background:#f59e0b;color:#fff;border-color:#f59e0b}
.srq-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px}
.srq-stat-card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 1px 4px rgba(0,0,0,.06);text-align:center}
.srq-stat-num{font-size:1.6rem;font-weight:700;color:#d97706}
.srq-stat-lbl{font-size:.78rem;color:#6b7280;margin-top:2px}
</style>
</head>
<body class="ent-layout">
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/includes/dashboard_topbar.php'; ?>

<div class="srq-content">
<?php if ($msg): ?>
<div class="srq-alert srq-alert-<?= $msg['type'] ?>">
    <i class="fas fa-<?= $msg['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <span><?= $msg['text'] ?></span>
</div>
<?php endif; ?>

<div class="srq-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-clipboard-list me-2"></i>Store Requisition Portal</h1>
            <p>Request items from the institutional store - <?= htmlspecialchars($userName) ?></p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-dark" style="font-size:12px"><i class="fas fa-calendar"></i> <?= date('d M Y') ?></span>
        </div>
    </div>
</div>

<div class="srq-tabs">
    <a href="?tab=new" class="srq-tab <?= ($_GET['tab'] ?? 'new') === 'new' ? 'active' : '' ?>"><i class="fas fa-plus-circle me-1"></i>New Requisition</a>
    <a href="?tab=my_requests" class="srq-tab <?= ($_GET['tab'] ?? '') === 'my_requests' ? 'active' : '' ?>"><i class="fas fa-list me-1"></i>My Requests (<?= count($myRequests) ?>)</a>
</div>

<?php if (($_GET['tab'] ?? 'new') === 'my_requests'): ?>
<!-- My Requests -->
<div class="srq-card">
    <div class="srq-card-header"><h5><i class="fas fa-history me-2" style="color:#d97706"></i>My Requisition History</h5></div>
    <div class="srq-card-body" style="padding:0;overflow-x:auto">
        <?php if (empty($myRequests)): ?>
        <div style="text-align:center;padding:40px;color:#9ca3af">
            <i class="fas fa-inbox fa-3x mb-3" style="opacity:.3"></i>
            <p>No requisitions yet. Submit your first one!</p>
            <a href="?tab=new" class="srq-btn srq-btn-primary"><i class="fas fa-plus me-1"></i>New Requisition</a>
        </div>
        <?php else: ?>
        <table class="srq-table">
            <thead><tr><th>Request #</th><th>Department</th><th>Items</th><th>Urgency</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($myRequests as $req):
                $uBadge = $req['urgency']==='urgent'?'srq-badge-danger':($req['urgency']==='high'?'srq-badge-warning':'srq-badge-info');
                $sBadge = match($req['status']) {
                    'fulfilled' => 'srq-badge-success',
                    'approved' => 'srq-badge-success',
                    'pending_approval' => 'srq-badge-purple',
                    'pending' => 'srq-badge-warning',
                    'rejected' => 'srq-badge-danger',
                    default => 'srq-badge-info'
                };
                $sLabel = match($req['status']) {
                    'pending_approval' => 'Awaiting DG',
                    'pending' => 'Pending Store',
                    default => ucfirst($req['status'])
                };
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($req['request_number']) ?></strong></td>
                <td><?= htmlspecialchars($req['department'] ?? '-') ?></td>
                <td><?= $req['total_items'] ?> item(s)</td>
                <td><span class="srq-badge <?= $uBadge ?>"><?= $req['urgency'] ?></span></td>
                <td><span class="srq-badge <?= $sBadge ?>"><?= $sLabel ?></span></td>
                <td><small><?= date('d M Y H:i', strtotime($req['created_at'])) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- New Requisition Form -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="srq-card">
            <div class="srq-card-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff">
                <h5 style="color:#fff;margin:0"><i class="fas fa-clipboard-list me-2"></i>New Store Requisition</h5>
            </div>
            <div class="srq-card-body">
                <form method="POST" id="requisitionForm">
                    <input type="hidden" name="action" value="submit_requisition">
                    <?= csrfField() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="srq-form-label">Department *</label>
                            <input type="text" name="department" class="srq-form-input" value="<?= htmlspecialchars($userDept) ?>" required placeholder="e.g. Nursing Department">
                        </div>
                        <div class="col-md-4">
                            <label class="srq-form-label">Urgency</label>
                            <select name="urgency" class="form-select" style="padding:10px 14px;border-radius:8px">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="srq-form-label">Date</label>
                            <input type="text" class="srq-form-input" value="<?= date('d M Y') ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="srq-form-label">Select Items *</label>
                        <div class="mb-2 d-flex gap-2">
                            <input type="text" id="itemSearch" class="srq-form-input" placeholder="Search items..." style="flex:1">
                            <button type="button" class="srq-btn srq-btn-primary srq-btn-sm" onclick="quickSelectMatron()" title="Auto-select the 16 standard Matron items"><i class="fas fa-bolt me-1"></i>Matron Essentials</button>
                        </div>
                        <div style="max-height:450px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;padding:10px" id="itemsContainer">
                        <?php foreach ($categories as $cat):
                            $catItems = $itemsByCat[$cat['id']] ?? [];
                            if (empty($catItems)) continue;
                        ?>
                            <div class="srq-cat-label"><i class="<?= $cat['icon'] ?? 'fas fa-box' ?> me-1"></i><?= htmlspecialchars($cat['category_name']) ?> (<?= count($catItems) ?> items)</div>
                            <?php foreach ($catItems as $item): ?>
                            <div class="srq-item-row item-entry" data-name="<?= strtolower(htmlspecialchars($item['item_name'])) ?>">
                                <input type="checkbox" class="form-check-input item-check" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars(addslashes($item['item_name'])) ?>" data-unit="<?= htmlspecialchars(addslashes($item['unit'])) ?>">
                                <span class="srq-item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                                <span class="srq-item-unit">(<?= htmlspecialchars($item['unit']) ?>)</span>
                                <input type="number" class="form-control form-control-sm srq-qty-input qty-input" placeholder="Qty" min="1" step="any" disabled>
                            </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="srq-form-label">Additional Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions..."></textarea>
                    </div>

                    <div id="selectedSummary" class="mb-3 p-3 bg-light rounded" style="display:none">
                        <strong><i class="fas fa-shopping-cart me-1"></i>Selected Items:</strong>
                        <div id="selectedList" class="mt-2 small"></div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="submit_for_dg" id="submitForDg" class="form-check-input" value="1">
                        <label class="form-check-label fw-semibold" for="submitForDg" style="font-size:13px">
                            <i class="fas fa-crown text-warning me-1"></i>Submit directly for <strong>Director General Approval</strong>
                        </label>
                    </div>

                    <button type="submit" class="srq-btn srq-btn-success w-100" style="justify-content:center;padding:14px;font-size:1rem">
                        <i class="fas fa-paper-plane me-2"></i>Submit Requisition
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="srq-card">
            <div class="srq-card-body text-center">
                <div style="font-size:3rem;color:#d97706;margin-bottom:8px"><i class="fas fa-user-circle"></i></div>
                <h5 class="mb-0"><?= htmlspecialchars($userName) ?></h5>
                <small class="text-muted"><?= htmlspecialchars($userRole) ?></small>
            </div>
        </div>
        <div class="srq-stat-grid">
            <div class="srq-stat-card"><div class="srq-stat-num"><?= count($allItems) ?></div><div class="srq-stat-lbl">Available Items</div></div>
            <div class="srq-stat-card"><div class="srq-stat-num"><?= count($myRequests) ?></div><div class="srq-stat-lbl">My Requests</div></div>
        </div>
        <div class="srq-card">
            <div class="srq-card-header"><h5 style="font-size:.9rem"><i class="fas fa-info-circle me-1"></i>How It Works</h5></div>
            <div class="srq-card-body" style="font-size:.85rem;color:#6b7280">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Select items and quantities from the list</li>
                    <li class="mb-2">Choose urgency level</li>
                    <li class="mb-2">Optionally submit directly for DG approval</li>
                    <li class="mb-2">The storekeeper reviews and fulfills your request</li>
                    <li class="mb-0">Track status in <strong>My Requests</strong></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    // Search filter
    $('#itemSearch').on('keyup', function(){
        let q = $(this).val().toLowerCase();
        $('.item-entry').each(function(){ $(this).toggle($(this).data('name').includes(q)); });
    });

    // Checkbox toggle
    $('.item-check').on('change', function(){
        $(this).closest('.srq-item-row').find('.qty-input').prop('disabled', !this.checked);
        if (!this.checked) $(this).closest('.srq-item-row').find('.qty-input').val('');
        updateSummary();
    });
    $('.qty-input').on('input', updateSummary);

    // Form submit validation
    $('#requisitionForm').on('submit', function(e){
        let checked = $('.item-check:checked').length;
        if (checked === 0) { e.preventDefault(); alert('Please select at least one item.'); return; }
        let hasQty = false;
        $('.item-check:checked').each(function(){
            let qty = $(this).closest('.srq-item-row').find('.qty-input').val();
            if (parseFloat(qty) > 0) hasQty = true;
        });
        if (!hasQty) { e.preventDefault(); alert('Please enter quantities for selected items.'); return; }

        // Build hidden inputs
        let form = $(this);
        form.find('.dynamic-item').remove();
        let idx = 0;
        $('.item-check:checked').each(function(){
            let row = $(this).closest('.srq-item-row');
            let qty = row.find('.qty-input').val();
            let id = $(this).data('id');
            if (parseFloat(qty) > 0) {
                form.append('<input type="hidden" class="dynamic-item" name="req_items['+idx+'][item_id]" value="'+id+'">');
                form.append('<input type="hidden" class="dynamic-item" name="req_items['+idx+'][quantity]" value="'+qty+'">');
                form.append('<input type="hidden" class="dynamic-item" name="req_items['+idx+'][notes]" value="">');
                idx++;
            }
        });
    });

    function updateSummary(){
        let items = [];
        $('.item-check:checked').each(function(){
            let row = $(this).closest('.srq-item-row');
            let qty = row.find('.qty-input').val();
            let name = $(this).data('name');
            let unit = $(this).data('unit');
            if (parseFloat(qty) > 0) items.push('<div class="mb-1"><strong>'+name+'</strong> &times; <strong>'+qty+'</strong> '+unit+'</div>');
        });
        if (items.length > 0) { $('#selectedSummary').show(); $('#selectedList').html(items.join('')); }
        else { $('#selectedSummary').hide(); $('#selectedList').html(''); }
    }
});

function quickSelectMatron() {
    var presetMap = <?= json_encode(array_flip(array_map('strtolower', array_column($allItems, 'item_name', 'id')))) ?>;
    var nameToId = {};
    <?php foreach ($allItems as $item): ?>
    nameToId['<?= strtolower(addslashes($item['item_name'])) ?>'] = <?= (int)$item['id'] ?>;
    <?php endforeach; ?>
    var presets = [
        ['omo', 5], ['jik', 5], ['vim', 5], ['examination gloves', 2], ['surgical gloves', 2],
        ['scrubbing brushes', 5], ['squeezers', 3], ['mops', 5], ['soft brooms', 5], ['compound brooms', 3],
        ['ruled reams', 2], ['toilet brushes', 5], ['bulbs', 5], ['stick glue', 2], ['cobweb brushes', 3], ['sink pumps', 2]
    ];
    $('.item-check').prop('checked', false).trigger('change');
    $('.qty-input').val('').prop('disabled', true);
    presets.forEach(function(p) {
        var id = nameToId[p[0]];
        if (!id) return;
        var chk = $('.item-check[data-id="'+id+'"]');
        if (chk.length) {
            chk.prop('checked', true).trigger('change');
            chk.closest('.srq-item-row').find('.qty-input').val(p[1]).prop('disabled', false);
        }
    });
    updateSummary();
}
</script>
<?php include_once __DIR__ . '/includes/dashboard_footer.php'; ?>
</body>
</html>
