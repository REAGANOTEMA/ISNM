<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['chemical', 'lab', 'store', 'inventory']);
$staff_conn = $ctx['staff'];
$students_conn = $ctx['students'];
$user = $ctx['user'];
$user_name = $user['full_name'] ?? 'Chemical Staff';
$user_role = $user['role'] ?? '';
$user_id = (int)($user['id'] ?? 0);

function ci_fetch($conn, $sql) {
    if (!$conn) return [];
    try { $r = $conn->query($sql); if (!$r) return []; return $r->fetch_all(MYSQLI_ASSOC); }
    catch (Exception $e) { error_log('chemical-inventory getChemicals: ' . $e->getMessage()); return []; }
}

$active_section = $_GET['section'] ?? 'dashboard';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staff_conn) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_chemical') {
        $id = (int)($_POST['id'] ?? 0);
        $code = $_POST['chemical_code'];
        $name = $_POST['chemical_name'];
        $type = $_POST['chemical_type'];
        $cas = $_POST['cas_number'] ?? '';
        $hazard = $_POST['hazard_class'];
        $loc = $_POST['storage_location'] ?? '';
        $qty = (float)($_POST['quantity_on_hand'] ?? 0);
        $unit = $_POST['unit_of_measure'] ?? 'ml';
        $rol = !empty($_POST['reorder_level']) ? (float)$_POST['reorder_level'] : null;
        $supplier = $_POST['supplier'] ?? '';
        $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $received = !empty($_POST['date_received']) ? $_POST['date_received'] : null;
        $status = $_POST['status'];

        // Auto-calculate status based on qty and expiry
        $expiry_dt = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
        if ($expiry_dt && $expiry_dt <= date('Y-m-d')) {
            $status = 'Expired';
        } elseif ($status === 'In Stock' && $rol !== null && $qty <= $rol) {
            $status = 'Low Stock';
        } elseif ($status === 'In Stock' && $qty <= 0) {
            $status = 'Discontinued';
        }

        if ($id > 0) {
            $stmt = $staff_conn->prepare("UPDATE chemical_inventory SET chemical_code=?, chemical_name=?, chemical_type=?, cas_number=?, hazard_class=?, storage_location=?, quantity_on_hand=?, unit_of_measure=?, reorder_level=?, supplier=?, expiry_date=?, date_received=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssdsssssi", $code, $name, $type, $cas, $hazard, $loc, $qty, $unit, $rol, $supplier, $expiry, $received, $status, $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Chemical updated successfully.';
        } else {
            $received_by = intval($user_id);
            $stmt = $staff_conn->prepare("INSERT INTO chemical_inventory (chemical_code, chemical_name, chemical_type, cas_number, hazard_class, storage_location, quantity_on_hand, unit_of_measure, reorder_level, supplier, expiry_date, date_received, received_by, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssdssssis", $code, $name, $type, $cas, $hazard, $loc, $qty, $unit, $rol, $supplier, $expiry, $received, $received_by, $status);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Chemical added successfully.';
        }
        header('Location: chemical-inventory.php'); exit;
    }

    if ($action === 'stock_adjust') {
        $id = (int)($_POST['id'] ?? 0);
        $adjust_type = $_POST['adjust_type'];
        $adjust_qty = (float)($_POST['adjust_quantity'] ?? 0);
        if ($id > 0 && $adjust_qty > 0) {
            $stmt = $staff_conn->prepare("SELECT quantity_on_hand, reorder_level, expiry_date FROM chemical_inventory WHERE id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $qrChem = $stmt->get_result();
            $chem = $qrChem ? $qrChem->fetch_assoc() : null;
            $stmt->close();
            if ($chem) {
                $cq = (float)$chem['quantity_on_hand'];
                $nq = ($adjust_type === 'add') ? $cq + $adjust_qty : max(0, $cq - $adjust_qty);
                $rol = $chem['reorder_level'];
                $exp = $chem['expiry_date'];
                $ns = ($exp && $exp <= date('Y-m-d')) ? 'Expired' : ($nq <= 0 ? 'Discontinued' : ($rol !== null && $nq <= (float)$rol ? 'Low Stock' : 'In Stock'));
                $stmt2 = $staff_conn->prepare("UPDATE chemical_inventory SET quantity_on_hand=?, status=? WHERE id=?");
                $stmt2->bind_param("dsi", $nq, $ns, $id);
                if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
                $stmt2->close();
                $_SESSION['success'] = "Stock adjusted. New qty: $nq";
            }
        }
        header('Location: chemical-inventory.php'); exit;
    }

    if ($action === 'mark_disposed') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $staff_conn->prepare("UPDATE chemical_inventory SET status='Discontinued', quantity_on_hand=0 WHERE id=?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Chemical marked as disposed.';
        }
        header('Location: chemical-inventory.php'); exit;
    }

    if ($action === 'delete_chemical') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $staff_conn->prepare("DELETE FROM chemical_inventory WHERE id=?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
            $_SESSION['success'] = 'Chemical deleted.';
        }
        header('Location: chemical-inventory.php'); exit;
    }
}

// Stats
function ci_q($conn, $sql) {
    if (!$conn) return 0;
    try { $r = $conn->query($sql); if (!$r) return 0; $row = $r->fetch_assoc(); return (int)($row[array_key_first($row)] ?? 0); }
    catch (Exception $e) { error_log('chemical-inventory count: ' . $e->getMessage()); return 0; }
}

$total_chemicals = ci_q($staff_conn, "SELECT COUNT(*) FROM chemical_inventory");
$in_stock = ci_q($staff_conn, "SELECT COUNT(*) FROM chemical_inventory WHERE status = 'In Stock'");
$low_stock = ci_q($staff_conn, "SELECT COUNT(*) FROM chemical_inventory WHERE status = 'Low Stock'");
$expired_count = ci_q($staff_conn, "SELECT COUNT(*) FROM chemical_inventory WHERE status = 'Expired'");
$expiring_soon = ci_q($staff_conn, "SELECT COUNT(*) FROM chemical_inventory WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() AND status != 'Expired'");

// Chemicals list
$chemicals = ci_fetch($staff_conn, "SELECT c.*, s.full_name AS receiver_name FROM chemical_inventory c LEFT JOIN staff s ON c.received_by = s.id ORDER BY c.chemical_name ASC");

// Low stock alerts
$low_stock_alerts = ci_fetch($staff_conn, "SELECT * FROM chemical_inventory WHERE status IN ('Low Stock','Expired') OR (reorder_level IS NOT NULL AND quantity_on_hand <= reorder_level AND status != 'Discontinued') ORDER BY quantity_on_hand ASC LIMIT 10");

$pageTitle = 'Chemical Inventory Management';?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.chem-section { display: none; }
.chem-section.active { display: block; }
.health-card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(148,163,184,0.16); box-shadow: 0 1px 2px rgba(15,23,42,0.03), 0 4px 12px rgba(15,23,42,0.04); }
.health-card h2 { font-size: 1rem; font-weight: 600; margin-bottom: 16px; color: #0f172a; display: flex; align-items: center; gap: 8px; }
.stat-card { background: #fff; border-radius: 16px; padding: 18px; border: 1px solid rgba(148,163,184,0.16); box-shadow: 0 1px 2px rgba(15,23,42,0.03), 0 4px 12px rgba(15,23,42,0.04); display: flex; align-items: center; gap: 14px; transition: all 0.25s ease; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
.si { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.si-blue { background: #eef2ff; color: #2563eb; }
.si-green { background: #ecfdf5; color: #059669; }
.si-orange { background: #fff7ed; color: #d97706; }
.si-red { background: #fef2f2; color: #dc2626; }
.si-purple { background: #f5f3ff; color: #7c3aed; }
.si-teal { background: #f0fdfa; color: #0d9488; }
.si-yellow { background: #fefce8; color: #ca8a04; }
.stat-content h3 { font-size: 1.5rem; font-weight: 700; margin: 0; color: #0f172a; line-height: 1.2; }
.stat-content p { font-size: 0.75rem; color: #64748b; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
.form-control, .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 8px 14px; font-size: 0.875rem; }
.form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.btn { border-radius: 10px; font-weight: 500; padding: 8px 18px; }
.table th { font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
.table td { vertical-align: middle; }
.stock-critical { background: #fee2e2 !important; }
.stock-warning { background: #fef3c7 !important; }
.stock-ok { background: #d1fae5 !important; }
.type-tab { cursor: pointer; }
.type-tab.active { background: #2563eb; color: #fff !important; border-color: #2563eb !important; }
.top-bar { background: #fff; border-radius: 16px; padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(148,163,184,0.16); }
@media print { .no-print { display: none !important; } }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<div class="page-content">
<div class="top-bar"><div><strong><i class="fas fa-flask me-2 text-purple"></i>Chemical Inventory Management</strong><div class="text-muted small">Iganga School of Nursing &amp; Midwifery</div></div><div class="d-flex align-items-center gap-3"><span class="text-muted small d-none d-md-block"><?=date('D, d M Y')?></span><button class="btn btn-sm btn-outline-success no-print" onclick="window.print()"><i class="fas fa-print me-1"></i></button><a href="../logout.php" class="btn btn-sm btn-outline-danger no-print"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></div></div>
<div class="content-area">
<?php if(!empty($_SESSION['success'])):?><div class="alert alert-success alert-dismissible fade show py-2"><?=htmlspecialchars($_SESSION['success'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']);endif;?>
<?php if(!empty($_SESSION['error'])):?><div class="alert alert-danger alert-dismissible fade show py-2"><?=htmlspecialchars($_SESSION['error'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error']);endif;?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-blue"><i class="fas fa-flask"></i></div><div class="stat-content"><h3><?=number_format($total_chemicals)?></h3><p>Total Chemicals</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-green"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?=$in_stock?></h3><p>In Stock</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-orange"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-content"><h3><?=$low_stock?></h3><p>Low Stock</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-red"><i class="fas fa-times-circle"></i></div><div class="stat-content"><h3><?=$expired_count?></h3><p>Expired</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-yellow"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?=$expiring_soon?></h3><p>Expiring Soon</p></div></div></div>
<div class="col-6 col-md-4 col-lg-2"><div class="stat-card"><div class="si si-purple"><i class="fas fa-tag"></i></div><div class="stat-content"><h3><?=$total_chemicals - $in_stock - $low_stock - $expired_count?></h3><p>Discontinued</p></div></div></div>
</div>

<!-- Chemical Type Filter Tabs -->
<div class="d-flex flex-wrap gap-2 mb-3 no-print">
<button class="btn btn-sm btn-outline-secondary type-tab active" data-type="all" onclick="filterType('all')">All</button>
<button class="btn btn-sm btn-outline-danger type-tab" data-type="Acid" onclick="filterType('Acid')">Acids</button>
<button class="btn btn-sm btn-outline-primary type-tab" data-type="Base" onclick="filterType('Base')">Bases</button>
<button class="btn btn-sm btn-outline-warning type-tab" data-type="Solvent" onclick="filterType('Solvent')">Solvents</button>
<button class="btn btn-sm btn-outline-info type-tab" data-type="Reagent" onclick="filterType('Reagent')">Reagents</button>
<button class="btn btn-sm btn-outline-success type-tab" data-type="Indicator" onclick="filterType('Indicator')">Indicators</button>
<button class="btn btn-sm btn-outline-secondary type-tab" data-type="Other" onclick="filterType('Other')">Other</button>
</div>

<!-- Low Stock Alerts -->
<?php if (!empty($low_stock_alerts)): ?>
<div class="alert alert-warning alert-dismissible fade show py-2 no-print">
<strong><i class="fas fa-exclamation-circle me-1"></i>Alerts:</strong>
<?php foreach ($low_stock_alerts as $al): $al_type = $al['status'] === 'Expired' ? 'danger' : 'warning'; ?>
<span class="badge bg-<?=$al_type?> me-1"><?=htmlspecialchars($al['chemical_name'])?> (<?=htmlspecialchars($al['status'])?>)</span>
<?php endforeach; ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Main Table -->
<div class="health-card">
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
<h2 class="mb-0"><i class="fas fa-table me-2 text-purple"></i>Chemical Inventory</h2>
<div class="d-flex gap-2">
<div><input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Filter table..." onkeyup="filterTable('chem-tbl', this.value)"></div>
<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addChemicalModal"><i class="fas fa-plus me-1"></i>Add Chemical</button>
</div>
</div>
<div class="table-responsive" style="max-height:600px;overflow-y:auto">
<table class="table table-sm table-hover align-middle" id="chem-tbl">
<thead class="table-light sticky-top"><tr>
<th>Code</th><th>Chemical Name</th><th>Type</th><th>Hazard</th><th>Qty</th><th>Unit</th><th>Location</th><th>Expiry</th><th>Status</th><th class="no-print">Actions</th>
</tr></thead>
<tbody>
<?php if (empty($chemicals)): ?>
<tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-flask fa-3x mb-2 d-block" style="opacity:.3;"></i>No chemicals in inventory.</td></tr>
<?php else: foreach ($chemicals as $c):
$hazard_colors = ['Flammable'=>'danger','Corrosive'=>'orange','Toxic'=>'purple','Reactive'=>'warning text-dark','Oxidizer'=>'primary','Non-hazardous'=>'success'];
$haz_cls = $hazard_colors[$c['hazard_class']] ?? 'secondary';
$status_colors = ['In Stock'=>'success','Low Stock'=>'warning text-dark','Expired'=>'danger','Discontinued'=>'secondary'];
$st_cls = $status_colors[$c['status']] ?? 'secondary';
$row_cls = $c['status'] === 'Expired' ? 'stock-critical' : ($c['status'] === 'Low Stock' ? 'stock-warning' : '');
$type_badges = ['Acid'=>'danger','Base'=>'primary','Solvent'=>'warning text-dark','Reagent'=>'info','Indicator'=>'success','Other'=>'secondary'];
$tp_cls = $type_badges[$c['chemical_type']] ?? 'secondary';
?>
<tr class="<?=$row_cls?>" data-type="<?=htmlspecialchars($c['chemical_type'])?>">
<td><span class="badge bg-secondary"><?=htmlspecialchars($c['chemical_code'])?></span></td>
<td><strong><?=htmlspecialchars($c['chemical_name'])?></strong><?php if ($c['cas_number']):?><br><small class="text-muted">CAS: <?=htmlspecialchars($c['cas_number'])?></small><?php endif;?></td>
<td><span class="badge bg-<?=$tp_cls?>"><?=htmlspecialchars($c['chemical_type'])?></span></td>
<td><span class="badge bg-<?=$haz_cls?>"><?=htmlspecialchars($c['hazard_class'] ?? 'N/A')?></span></td>
<td><strong><?=number_format((float)$c['quantity_on_hand'], 2)?></strong></td>
<td><?=htmlspecialchars($c['unit_of_measure'] ?? 'ml')?></td>
<td><small><?=htmlspecialchars($c['storage_location'] ?? '-')?></small></td>
<td><small><?=$c['expiry_date'] ? date('d M Y', strtotime($c['expiry_date'])) : '-'?></small></td>
<td><span class="badge bg-<?=$st_cls?>"><?=htmlspecialchars($c['status'])?></span></td>
<td class="no-print">
<div class="dropdown">
<button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
<ul class="dropdown-menu dropdown-menu-end">
<li><button class="dropdown-item" onclick="editChemical(<?=$c['id']?>,'<?=htmlspecialchars($c['chemical_code'],ENT_QUOTES)?>','<?=htmlspecialchars($c['chemical_name'],ENT_QUOTES)?>','<?=htmlspecialchars($c['chemical_type'],ENT_QUOTES)?>','<?=htmlspecialchars($c['cas_number']??'',ENT_QUOTES)?>','<?=htmlspecialchars($c['hazard_class']??'',ENT_QUOTES)?>','<?=htmlspecialchars($c['storage_location']??'',ENT_QUOTES)?>','<?=(float)$c['quantity_on_hand']?>','<?=htmlspecialchars($c['unit_of_measure']??'ml',ENT_QUOTES)?>','<?=htmlspecialchars($c['reorder_level']??'',ENT_QUOTES)?>','<?=htmlspecialchars($c['supplier']??'',ENT_QUOTES)?>','<?=$c['expiry_date']??''?>','<?=$c['date_received']??''?>','<?=htmlspecialchars($c['status']??'In Stock',ENT_QUOTES)?>')"><i class="fas fa-edit me-2"></i>Edit</button></li>
<li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#stockModal" onclick="document.getElementById('stock-id').value=<?=$c['id']?>;document.getElementById('stock-name').textContent='<?=htmlspecialchars($c['chemical_name'],ENT_QUOTES)?>'"><i class="fas fa-balance-scale me-2"></i>Update Stock</button></li>
<li><button class="dropdown-item text-danger" onclick="confirmMarkDisposed(<?=$c['id']?>,'<?=htmlspecialchars($c['chemical_name'],ENT_QUOTES)?>')"><i class="fas fa-trash-alt me-2"></i>Mark Disposed</button></li>
<li><hr class="dropdown-divider"></li>
<li><button class="dropdown-item text-danger" onclick="confirmDelete(<?=$c['id']?>,'<?=htmlspecialchars($c['chemical_name'],ENT_QUOTES)?>')"><i class="fas fa-times-circle me-2"></i>Delete</button></li>
</ul>
</div>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>

</div><!-- end content-area -->
</div><!-- end page-content -->

<!-- Add Chemical Modal -->
<div class="modal fade" id="addChemicalModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-scrollable">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Chemical</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form method="POST">
<input type="hidden" name="action" value="save_chemical">
<input type="hidden" name="id" id="chem-id" value="0">
<div class="modal-body">
<div class="row g-3">
<div class="col-md-4">
<label class="form-label fw-semibold">Chemical Code <span class="text-danger">*</span></label>
<input type="text" name="chemical_code" class="form-control" required placeholder="e.g., CH-001" id="chem-code">
</div>
<div class="col-md-8">
<label class="form-label fw-semibold">Chemical Name <span class="text-danger">*</span></label>
<input type="text" name="chemical_name" class="form-control" required placeholder="e.g., Hydrochloric Acid" id="chem-name">
</div>
<div class="col-md-4">
<label class="form-label fw-semibold">Chemical Type <span class="text-danger">*</span></label>
<select name="chemical_type" class="form-select" required id="chem-type">
<option value="Acid">Acid</option>
<option value="Base">Base</option>
<option value="Solvent">Solvent</option>
<option value="Reagent">Reagent</option>
<option value="Indicator">Indicator</option>
<option value="Other">Other</option>
</select>
</div>
<div class="col-md-4">
<label class="form-label fw-semibold">CAS Number</label>
<input type="text" name="cas_number" class="form-control" placeholder="e.g., 7647-01-0" id="chem-cas">
</div>
<div class="col-md-4">
<label class="form-label fw-semibold">Hazard Class</label>
<select name="hazard_class" class="form-select" id="chem-hazard">
<option value="Non-hazardous">Non-hazardous</option>
<option value="Flammable">Flammable</option>
<option value="Corrosive">Corrosive</option>
<option value="Toxic">Toxic</option>
<option value="Reactive">Reactive</option>
<option value="Oxidizer">Oxidizer</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label fw-semibold">Storage Location</label>
<input type="text" name="storage_location" class="form-control" placeholder="e.g., Cabinet A1, Shelf 3" id="chem-loc">
</div>
<div class="col-md-3">
<label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
<input type="number" name="quantity_on_hand" class="form-control" required step="0.01" min="0" value="0" id="chem-qty">
</div>
<div class="col-md-3">
<label class="form-label fw-semibold">Unit of Measure</label>
<select name="unit_of_measure" class="form-select" id="chem-unit">
<option value="ml">ml</option>
<option value="L">L</option>
<option value="g">g</option>
<option value="kg">kg</option>
<option value="mg">mg</option>
<option value="pcs">pcs</option>
<option value="vials">vials</option>
<option value="bottles">bottles</option>
</select>
</div>
<div class="col-md-4">
<label class="form-label fw-semibold">Reorder Level</label>
<input type="number" name="reorder_level" class="form-control" step="0.01" min="0" placeholder="e.g., 500" id="chem-rol">
</div>
<div class="col-md-4">
<label class="form-label fw-semibold">Supplier</label>
<input type="text" name="supplier" class="form-control" placeholder="Supplier name" id="chem-supplier">
</div>
<div class="col-md-4">
<label class="form-label fw-semibold">Status</label>
<select name="status" class="form-select" id="chem-status">
<option value="In Stock">In Stock</option>
<option value="Low Stock">Low Stock</option>
<option value="Expired">Expired</option>
<option value="Discontinued">Discontinued</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label fw-semibold">Expiry Date</label>
<input type="date" name="expiry_date" class="form-control" id="chem-expiry">
</div>
<div class="col-md-6">
<label class="form-label fw-semibold">Date Received</label>
<input type="date" name="date_received" class="form-control" value="<?=date('Y-m-d')?>" id="chem-received">
</div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Chemical</button>
</div>
</form>
</div>
</div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title"><i class="fas fa-balance-scale me-2 text-warning"></i>Update Stock: <span id="stock-name"></span></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form method="POST">
<input type="hidden" name="action" value="stock_adjust">
<input type="hidden" name="id" id="stock-id" value="0">
<div class="modal-body">
<div class="mb-3">
<label class="form-label fw-semibold">Adjustment Type</label>
<select name="adjust_type" class="form-select" required>
<option value="add">Add (Increase Stock)</option>
<option value="remove">Remove (Decrease Stock)</option>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
<input type="number" name="adjust_quantity" class="form-control" required step="0.01" min="0.01" placeholder="Enter quantity">
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button>
</div>
</form>
</div>
</div>
</div>

<!-- Delete Confirmation Form -->
<form method="POST" id="deleteForm" style="display:none">
<input type="hidden" name="action" value="delete_chemical">
<input type="hidden" name="id" id="delete-id" value="0">
</form>
<form method="POST" id="disposeForm" style="display:none">
<input type="hidden" name="action" value="mark_disposed">
<input type="hidden" name="id" id="dispose-id" value="0">
</form>

<script>
function filterTable(tblId, val) {
    val = val.toLowerCase();
    const tbl = document.getElementById(tblId);
    if (!tbl || !tbl.tBodies[0]) return;
    const rows = tbl.tBodies[0].rows;
    for (let i = 0; i < rows.length; i++) {
        const txt = rows[i].textContent.toLowerCase();
        rows[i].style.display = txt.indexOf(val) > -1 ? '' : 'none';
    }
}

function filterType(type) {
    document.querySelectorAll('.type-tab').forEach(t => t.classList.remove('active'));
    document.querySelector('.type-tab[data-type="' + type + '"]')?.classList.add('active');
    const tbl = document.getElementById('chem-tbl');
    if (!tbl || !tbl.tBodies[0]) return;
    const rows = tbl.tBodies[0].rows;
    for (let i = 0; i < rows.length; i++) {
        const rowType = rows[i].getAttribute('data-type');
        rows[i].style.display = (type === 'all' || rowType === type) ? '' : 'none';
    }
}

function editChemical(id, code, name, type, cas, hazard, loc, qty, unit, rol, supplier, expiry, received, status) {
    document.getElementById('chem-id').value = id;
    document.getElementById('chem-code').value = code;
    document.getElementById('chem-name').value = name;
    document.getElementById('chem-type').value = type;
    document.getElementById('chem-cas').value = cas;
    document.getElementById('chem-hazard').value = hazard;
    document.getElementById('chem-loc').value = loc;
    document.getElementById('chem-qty').value = qty;
    document.getElementById('chem-unit').value = unit || 'ml';
    document.getElementById('chem-rol').value = rol || '';
    document.getElementById('chem-supplier').value = supplier;
    document.getElementById('chem-expiry').value = expiry;
    document.getElementById('chem-received').value = received;
    document.getElementById('chem-status').value = status;
    var modal = new bootstrap.Modal(document.getElementById('addChemicalModal'));
    modal.show();
}

function confirmDelete(id, name) {
    if (confirm('Permanently delete "' + name + '"? This cannot be undone.')) {
        document.getElementById('delete-id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function confirmMarkDisposed(id, name) {
    if (confirm('Mark "' + name + '" as disposed/ discontinued?')) {
        document.getElementById('dispose-id').value = id;
        document.getElementById('disposeForm').submit();
    }
}

// Reset modal form when opening for new
document.getElementById('addChemicalModal')?.addEventListener('show.bs.modal', function (e) {
    var idField = document.getElementById('chem-id');
    if (idField && idField.value === '0') {
        document.getElementById('addChemicalModal').querySelector('form').reset();
        document.getElementById('chem-id').value = '0';
        document.getElementById('chem-status').value = 'In Stock';
        document.getElementById('chem-unit').value = 'ml';
        document.getElementById('chem-received').value = '<?=date('Y-m-d')?>';
    }
});
</script>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
