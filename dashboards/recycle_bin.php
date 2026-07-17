<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director','ict','system admin']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$staff_id = $user['id'] ?? 0;
$user_role = $user['role'] ?? '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $action = $_POST['action'] ?? '';
    $item_id = (int)($_POST['item_id'] ?? 0);

    if ($action === 'restore' && $item_id) {
        $q = $conn->prepare("SELECT * FROM recycle_bin WHERE id = ?");
        $q->bind_param('i', $item_id);
        if (!$q->execute()) { error_log('$q execute failed: ' . ($q->error ?? 'unknown')); };
        $item = $q->get_result()->fetch_assoc();
        $q->close();

        if ($item) {
            $table = $item['original_table'];
            $col = $item['original_id_column'];
            $original_id = intval($item['original_id']);
            $allowed_tables = ['documents','templates','student_documents','staff_documents','payment_invoices','student_payments','student_invoices','student_receipts'];
            $allowed_cols = ['id','student_id','invoice_id','payment_id','receipt_id'];
            if (!in_array($table, $allowed_tables) || !in_array($col, $allowed_cols)) {
                $_SESSION['error'] = 'Invalid restore target.';
                header('Location: recycle_bin.php');
                exit;
            }
            $restore = $conn->prepare("UPDATE `$table` SET is_deleted = 0, deleted_at = NULL WHERE `$col` = ? AND is_deleted = 1");
            if ($restore) { $restore->bind_param('i', $original_id); $restore->execute(); $restore->close(); }
            if ($restore) {
                $stmt = $conn->prepare("DELETE FROM recycle_bin WHERE id = ?");
                if ($stmt) { $stmt->bind_param('i', $item_id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
                $_SESSION['success'] = 'Item restored successfully.';
            } else {
                $_SESSION['error'] = 'Failed to restore item: ' . $conn->error;
            }
        }
        header('Location: recycle_bin.php');
        exit;
    }

    if ($action === 'delete_forever' && $item_id) {
        $q = $conn->prepare("SELECT original_table, original_id_column, original_id FROM recycle_bin WHERE id = ?");
        $q->bind_param('i', $item_id);
        if (!$q->execute()) { error_log('$q execute failed: ' . ($q->error ?? 'unknown')); };
        $item = $q->get_result()->fetch_assoc();
        $q->close();

        if ($item) {
            $table = $item['original_table'];
            $col = $item['original_id_column'];
            $original_id = intval($item['original_id']);
            $allowed_tables = ['documents','templates','student_documents','staff_documents','payment_invoices','student_payments','student_invoices','student_receipts'];
            $allowed_cols = ['id','student_id','invoice_id','payment_id','receipt_id'];
            if (!in_array($table, $allowed_tables) || !in_array($col, $allowed_cols)) {
                $_SESSION['error'] = 'Invalid delete target.';
                header('Location: recycle_bin.php');
                exit;
            }
            $del = $conn->prepare("DELETE FROM `$table` WHERE `$col` = ?");
            if ($del) { $del->bind_param('i', $original_id); $del->execute(); $del->close(); }
            $stmt = $conn->prepare("DELETE FROM recycle_bin WHERE id = ?");
            if ($stmt) { $stmt->bind_param('i', $item_id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = 'Item permanently deleted.';
        }
        header('Location: recycle_bin.php');
        exit;
    }

    if ($action === 'empty_trash') {
        $conn->query("DELETE FROM recycle_bin");
        $_SESSION['success'] = 'Trash emptied.';
        header('Location: recycle_bin.php');
        exit;
    }
}

// Fetch trashed items
$items = [];
$q = $conn->query("SELECT * FROM recycle_bin ORDER BY deleted_at DESC LIMIT 100");
if ($q) while ($row = $q->fetch_assoc()) $items[] = $row;

$pageTitle = 'Recycle Bin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.rb-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
.rb-stats { display: flex; gap: 16px; }
.rb-stat { text-align: center; padding: 12px 20px; background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); min-width: 100px; }
.rb-stat h4 { font-size: 1.5rem; font-weight: 700; margin: 0; color: #dc2626; }
.rb-stat p { margin: 0; font-size: 12px; color: #64748b; }
.rb-table { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.rb-table table { margin: 0; }
.rb-table th { background: #f8fafc; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; font-weight: 600; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; }
.rb-table td { padding: 12px 16px; vertical-align: middle; font-size: 14px; }
.rb-table tr:hover td { background: #f8fafc; }
.rb-empty { text-align: center; padding: 60px 20px; }
.rb-empty i { font-size: 3rem; color: #cbd5e1; margin-bottom: 16px; }
.rb-empty h4 { color: #64748b; }
.badge-table { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-document { background: #dbeafe; color: #1e40af; }
.badge-payment { background: #dcfce7; color: #166534; }
.badge-student { background: #fef3c7; color: #92400e; }
.badge-staff { background: #ede9fe; color: #5b21b6; }
.badge-other { background: #f1f5f9; color: #475569; }
</style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>

<div class="page-content">
  <div class="top-bar">
    <div>
      <strong><i class="fas fa-trash-alt me-2 text-danger"></i>Recycle Bin</strong>
      <div class="text-muted small">Manage deleted items across the system</div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
  </div>

  <div class="content-area">
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="rb-header">
      <div class="rb-stats">
        <div class="rb-stat"><h4><?= count($items) ?></h4><p>Deleted Items</p></div>
        <div class="rb-stat" style="border-left:3px solid #22c55e"><h4 style="color:#22c55e"><?= count(array_filter($items, fn($i) => !empty($i['original_table']))) ?></h4><p>Restorable</p></div>
      </div>
      <?php if (!empty($items)): ?>
      <form method="POST" onsubmit="return confirm('Permanently delete ALL items in trash? This cannot be undone.')">
        <input type="hidden" name="action" value="empty_trash">
        <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt me-1"></i>Empty Trash</button>
      </form>
      <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
    <div class="rb-table">
      <div class="rb-empty">
        <i class="fas fa-trash-alt"></i>
        <h4>Trash is empty</h4>
        <p class="text-muted">Deleted items will appear here. You can restore them or permanently delete them.</p>
      </div>
    </div>
    <?php else: ?>
    <div class="rb-table">
      <table class="table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Type</th>
            <th>Deleted By</th>
            <th>Deleted At</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item):
            $type_badge = 'badge-other';
            $type_label = 'Other';
            $tbl = $item['original_table'] ?? '';
            if (str_contains($tbl, 'document') || str_contains($tbl, 'template')) { $type_badge = 'badge-document'; $type_label = 'Document'; }
            elseif (str_contains($tbl, 'payment') || str_contains($tbl, 'invoice') || str_contains($tbl, 'receipt')) { $type_badge = 'badge-payment'; $type_label = 'Payment'; }
            elseif (str_contains($tbl, 'student')) { $type_badge = 'badge-student'; $type_label = 'Student'; }
            elseif (str_contains($tbl, 'staff')) { $type_badge = 'badge-staff'; $type_label = 'Staff'; }
          ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($item['item_title'] ?? 'Unnamed Item') ?></strong>
              <?php if (!empty($item['item_description'])): ?>
              <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($item['item_description'], 0, 80, '...')) ?></div>
              <?php endif; ?>
            </td>
            <td><span class="badge-table <?= $type_badge ?>"><?= $type_label ?></span></td>
            <td><span class="small"><?= htmlspecialchars($item['deleted_by_name'] ?? 'Unknown') ?></span></td>
            <td><span class="small text-muted"><?= !empty($item['deleted_at']) ? date('d M Y, H:i', strtotime($item['deleted_at'])) : '—' ?></span></td>
            <td class="text-end">
              <form method="POST" class="d-inline" onsubmit="return confirm('Restore this item?')">
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?? 0 ?>">
                <button class="btn btn-sm btn-success"><i class="fas fa-undo me-1"></i>Restore</button>
              </form>
              <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this item? This cannot be undone.')">
                <input type="hidden" name="action" value="delete_forever">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?? 0 ?>">
                <button class="btn btn-sm btn-danger"><i class="fas fa-times me-1"></i>Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>document.addEventListener('DOMContentLoaded',function(){var t='<?=htmlspecialchars($_SESSION["csrf_token"] ?? "")?>';document.querySelectorAll('form[method="POST"],form[method="post"]').forEach(function(f){if(!f.querySelector('input[name="csrf_token"]')){var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value=t;f.appendChild(i);}});});</script>
</body>
</html>
