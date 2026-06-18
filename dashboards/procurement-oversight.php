<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director', 'finance', 'bursar', 'store']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$orders = [];
if ($conn) {
    $r = $conn->query("SELECT o.*, s.full_name AS requester_name FROM store_orders o LEFT JOIN staff s ON o.requested_by = s.id ORDER BY o.created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $orders[] = $row;
}
$categories = [];
if ($conn) {
    $r = $conn->query("SELECT c.id, c.name, COUNT(i.id) AS item_count FROM store_categories c LEFT JOIN store_inventory i ON c.id = i.category_id GROUP BY c.id, c.name ORDER BY c.name");
    if ($r) while ($row = $r->fetch_assoc()) $categories[] = $row;
}
$pageTitle = 'Procurement Oversight';
?><!DOCTYPE html>
<html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?></head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:32px">
<div class="page-title-card"><h2><i class="fas fa-truck me-2"></i>Procurement Oversight</h2><p>Monitor procurement, store orders, and inventory categories</p></div>
<div class="row g-4">
<div class="col-lg-8"><div class="card"><div class="card-header">Store Orders</div><div class="card-body">
<?php if (empty($orders)): ?><div class="empty-state"><i class="fas fa-shopping-cart"></i><p>No procurement orders yet.</p></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Order #</th><th>Supplier</th><th>Amount</th><th>Requester</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach ($orders as $o): ?>
<tr><td class="small">#<?= htmlspecialchars($o['order_number']??$o['id']??'') ?></td><td class="small"><?= htmlspecialchars($o['supplier']??'Internal') ?></td><td><strong><?= htmlspecialchars(number_format((float)($o['total_amount']??0), 0)) ?></strong></td><td class="small"><?= htmlspecialchars($o['requester_name']??'') ?></td><td><span class="status-pill <?= ($o['status']??'') === 'approved' ? 'success' : (($o['status']??'') === 'cancelled' ? 'danger' : 'warning') ?>"><?= htmlspecialchars(str_replace('_', ' ', $o['status']??'draft')) ?></span></td><td class="small"><?= htmlspecialchars($o['created_at']??'') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">Inventory Categories</div><div class="card-body">
<?php if (empty($categories)): ?><p class="text-muted small text-center py-3">No categories.</p>
<?php else: ?><?php foreach ($categories as $cat): ?>
<div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2 small">
<span><?= htmlspecialchars($cat['name']) ?></span><span class="badge bg-primary"><?= (int)$cat['item_count'] ?> items</span>
</div>
<?php endforeach; ?><?php endif; ?>
</div></div></div></div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
