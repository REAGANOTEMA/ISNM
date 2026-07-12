<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/enterprise_auth.php';
$ctx = bootstrapStaffDashboard(['director general', 'ceo', 'system admin', 'director ict', 'director academics', 'hr manager']);
$conn = $ctx['staff'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);

$tasks = [];
$task_statuses = ['pending', 'in_progress', 'completed', 'cancelled', 'on_hold'];
$priorities = ['low', 'medium', 'high', 'urgent'];
$staff = [];

if ($conn) {
    $r = $conn->query("SELECT * FROM task_assignments WHERE assigned_to = $user_id OR assigned_by = $user_id ORDER BY FIELD(priority,'urgent','high','medium','low'), created_at DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $tasks[] = $row;
    $r = $conn->query("SELECT id, full_name FROM staff WHERE status='Active' ORDER BY full_name");
    if ($r) while ($row = $r->fetch_assoc()) $staff[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_task') {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $assigned_to = (int)($_POST['assigned_to'] ?? 0);
        $priority = $_POST['priority'] ?? 'medium';
        $due_date = $_POST['due_date'] ?? '';
        $category = trim($_POST['category'] ?? '');
        if ($title && $assigned_to) {
            $stmt = $conn->prepare("INSERT INTO task_assignments (title, description, assigned_by, assigned_to, priority, due_date, category, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param('ssiisss', $title, $desc, $user_id, $assigned_to, $priority, $due_date, $category);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $_SESSION['success'] = 'Task created.';
        }
        header('Location: tasks.php'); exit;
    }
    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id && in_array($status, $task_statuses)) {
            $conn->query("UPDATE task_assignments SET status='$status' WHERE id=$id");
            $_SESSION['success'] = 'Task updated.';
        }
        header('Location: tasks.php'); exit;
    }
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?><!DOCTYPE html><html lang="en">
<head><?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>body{background:#f0f2f5;font-family:Inter,sans-serif;font-size:13px}.wf-content{padding:20px 24px;margin-left:270px;min-height:100vh}.section-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #e2e8f0;margin-bottom:16px}.section-title{font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;margin-bottom:12px}.badge-urgent{background:#fef2f2;color:#991b1b}.badge-high{background:#ffedd5;color:#9a3412}.badge-medium{background:#fef9c3;color:#854d0e}.badge-low{background:#f0fdf4;color:#166534}@media(max-width:768px){.wf-content{margin-left:0;padding:12px}}</style></head>
<body><?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="wf-content">
<?php if ($success): ?><div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="section-title"><i class="fas fa-tasks text-primary"></i> Task Management</h5>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal"><i class="fas fa-plus"></i> New Task</button>
  </div>
  <div class="mb-3"><input class="form-control form-control-sm" style="max-width:300px" id="taskSearch" placeholder="Search tasks..." onkeyup="filterTable('taskSearch','taskTable')"></div>
  <div class="table-responsive"><table class="table table-hover table-sm" id="taskTable">
    <thead><tr><th>Title</th><th>Assigned To</th><th>Priority</th><th>Due</th><th>Status</th><th>Action</th></tr></thead>
    <tbody><?php if (empty($tasks)): ?><tr><td colspan="6" class="text-muted text-center py-3">No tasks found.</td></tr><?php endif; ?>
    <?php foreach ($tasks as $t): 
      $assigned = '';
      foreach ($staff as $s) { if ($s['id'] == $t['assigned_to']) { $assigned = $s['full_name']; break; } }
      $pclass = strtolower($t['priority'] ?? 'medium');
    ?><tr>
      <td><strong><?= htmlspecialchars($t['title'] ?? '') ?></strong><?php if ($t['description']): ?><br><small class="text-muted"><?= htmlspecialchars(substr($t['description'], 0, 100)) ?></small><?php endif; ?></td>
      <td><?= htmlspecialchars($assigned) ?></td>
      <td><span class="badge badge-<?= $pclass ?>"><?= $t['priority'] ?? 'medium' ?></span></td>
      <td><?= $t['due_date'] ? date('d M y', strtotime($t['due_date'])) : '-' ?></td>
      <td><span class="badge bg-<?= $t['status'] === 'completed' ? 'success' : ($t['status'] === 'in_progress' ? 'primary' : ($t['status'] === 'cancelled' ? 'secondary' : 'warning')) ?>"><?= str_replace('_', ' ', $t['status'] ?? 'pending') ?></span></td>
      <td>
        <form method="post" class="d-inline">
          <input type="hidden" name="id" value="<?= $t['id'] ?>">
          <select name="status" class="form-select form-select-sm d-inline" style="width:auto" onchange="this.form.submit()">
            <?php foreach ($task_statuses as $ts): ?>
              <option value="<?= $ts ?>" <?= $ts === ($t['status']??'pending') ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $ts)) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="action" value="update_status">
        </form>
      </td>
    </tr><?php endforeach; ?></tbody>
  </table></div>
</div></div>

<div class="modal fade" id="taskModal"><div class="modal-dialog"><div class="modal-content">
  <form method="post"><div class="modal-header"><h5 class="modal-title">New Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-2"><label class="form-label">Title *</label><input name="title" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
    <div class="mb-2"><label class="form-label">Assign To *</label>
      <select name="assigned_to" class="form-select" required><option value="">Select staff...</option>
      <?php foreach ($staff as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="row"><div class="col-6 mb-2"><label class="form-label">Priority</label>
      <select name="priority" class="form-select"><?php foreach ($priorities as $p): ?><option value="<?= $p ?>"><?= ucfirst($p) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 mb-2"><label class="form-label">Due Date</label><input name="due_date" type="date" class="form-control"></div></div>
    <div class="mb-2"><label class="form-label">Category</label><input name="category" class="form-control" placeholder="e.g. Academic, Admin, IT"></div>
  </div>
  <div class="modal-footer"><button type="submit" name="action" value="create_task" class="btn btn-primary">Create Task</button></div>
  </form>
</div></div></div>

<script>function filterTable(inputId, tableId) {
  var input = document.getElementById(inputId), filter = input.value.toUpperCase(), table = document.getElementById(tableId), tr = table.getElementsByTagName('tr');
  for (var i = 1; i < tr.length; i++) { tr[i].style.display = tr[i].getElementsByTagName('td')[0] && tr[i].getElementsByTagName('td')[0].textContent.toUpperCase().indexOf(filter) > -1 ? '' : 'none'; }
}</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body></html>
