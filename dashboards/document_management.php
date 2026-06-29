<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director','secretary','registrar','ict','it']);
$conn = $ctx['staff'];
$user = $ctx['user'];

$staff_id = $user['id'] ?? 0;
$action = $_GET['action'] ?? 'list';
$template_id = $_GET['id'] ?? null;
$template_type_filter = $_GET['template_type'] ?? '';
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$template_types = ['receipt', 'transcript', 'certificate', 'invoice', 'payslip', 'report', 'timetable', 'exam_schedule', 'leave_form', 'performance_review', 'id_card', 'contract'];
$receipt_types = ['Fee Payment', 'Registration', 'Transcript', 'Certificate', 'General'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'save_template') {
        $id = $_POST['id'] ?? null;
        $template_name = trim($_POST['template_name'] ?? '');
        $template_type = $_POST['template_type'] ?? '';
        $template_content = $_POST['template_content'] ?? '';
        $template_variables = $_POST['template_variables'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        $table = $_POST['table'] ?? 'receipt_templates';
        
        if (empty($template_name) || empty($template_type) || empty($template_content)) {
            $_SESSION['error'] = 'Template name, type, and content are required.';
        } else {
            $json_vars = json_encode(array_filter(array_map('trim', explode(',', $template_variables))));
            
            $stmt = false;
            if ($id) {
                if ($table === 'document_templates') {
                    $sql = "UPDATE $table SET template_name = ?, template_type = ?, template_content = ?, template_variables = ?, is_default = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) $stmt->bind_param('sssssi', $template_name, $template_type, $template_content, $json_vars, $is_default, $id);
                } else {
                    $sql = "UPDATE $table SET template_name = ?, template_type = ?, template_content = ?, template_variables = ?, is_active = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) $stmt->bind_param('sssssi', $template_name, $template_type, $template_content, $json_vars, $is_active, $id);
                }
            } else {
                if ($table === 'document_templates') {
                    $sql = "INSERT INTO $table (template_name, template_type, template_content, template_variables, is_default, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) $stmt->bind_param('sssssi', $template_name, $template_type, $template_content, $json_vars, $is_default, $staff_id);
                } else {
                    $sql = "INSERT INTO $table (template_name, template_type, template_content, template_variables, is_active, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) $stmt->bind_param('sssssi', $template_name, $template_type, $template_content, $json_vars, $is_active, $staff_id);
                }
            }
            
            if ($stmt && $stmt->execute()) {
                $_SESSION['success'] = $id ? 'Template updated successfully.' : 'Template created successfully.';
                header("Location: document_management.php?table=$table");
                exit();
            } else {
                $_SESSION['error'] = 'Database error: ' . $conn->error;
            }
        }
    }
    
    if ($post_action === 'delete_template') {
        $id = $_POST['id'] ?? null;
        $table = $_POST['table'] ?? 'receipt_templates';
        
        if ($id) {
            // Get template name
            $tq = $conn->prepare("SELECT template_name FROM $table WHERE id = ?");
            $tq->bind_param('i', $id);
            $tq->execute();
            $tr = $tq->get_result()->fetch_assoc();
            $tq->close();
            $tname = $tr['template_name'] ?? 'Unnamed';

            // Soft delete: mark as deleted
            $stmt = $conn->prepare("UPDATE $table SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    // Add to recycle bin
                    include_once __DIR__ . '/../includes/functions.php';
                    moveToTrash($conn, (int)$id, $table, 'id', $tname, 'Document template deleted', $staff_id);
                    $_SESSION['success'] = 'Template moved to trash.';
                } else {
                    $_SESSION['error'] = 'Failed to delete template: ' . $conn->error;
                }
                $stmt->close();
            }
        }
        header("Location: document_management.php?table=$table");
        exit();
    }
    
    if ($post_action === 'load_template') {
        $id = $_POST['id'] ?? null;
        $table = $_POST['table'] ?? 'receipt_templates';
        header("Location: document_management.php?action=edit&id=$id&table=$table");
        exit();
    }
}

$current_table = $_GET['table'] ?? 'receipt_templates';
if (!in_array($current_table, ['receipt_templates', 'document_templates'])) {
    $current_table = 'receipt_templates';
}

$editing = null;
if ($action === 'edit' && $template_id) {
    $stmt = $conn->prepare("SELECT * FROM $current_table WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $template_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $editing = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }
    if (!$editing) {
        $_SESSION['error'] = 'Template not found.';
        header("Location: document_management.php?table=$current_table");
        exit();
    }
}

$templates = [];
$search = $_GET['search'] ?? '';
// Check if is_deleted column exists
$hasSoftDelete = false;
$colCheck = $conn->query("SHOW COLUMNS FROM $current_table LIKE 'is_deleted'");
if ($colCheck && $colCheck->num_rows > 0) $hasSoftDelete = true;
$deletedFilter = $hasSoftDelete ? " AND (rt.is_deleted IS NULL OR rt.is_deleted = 0)" : "";
$deletedFilterDt = $hasSoftDelete ? " AND (dt.is_deleted IS NULL OR dt.is_deleted = 0)" : "";

if ($current_table === 'receipt_templates') {
    if ($template_type_filter) {
        $stmt = $conn->prepare("SELECT rt.*, s.full_name as created_by_name FROM receipt_templates rt LEFT JOIN staff s ON rt.created_by = s.id WHERE rt.template_type = ?$deletedFilter ORDER BY rt.is_active DESC, rt.template_name ASC");
        if ($stmt) $stmt->bind_param('s', $template_type_filter);
    } elseif ($search) {
        $like = "%$search%";
        $stmt = $conn->prepare("SELECT rt.*, s.full_name as created_by_name FROM receipt_templates rt LEFT JOIN staff s ON rt.created_by = s.id WHERE (rt.template_name LIKE ? OR rt.template_type LIKE ? OR rt.template_content LIKE ?)$deletedFilter ORDER BY rt.is_active DESC, rt.template_name ASC");
        if ($stmt) $stmt->bind_param('sss', $like, $like, $like);
    } else {
        $stmt = $conn->prepare("SELECT rt.*, s.full_name as created_by_name FROM receipt_templates rt LEFT JOIN staff s ON rt.created_by = s.id WHERE 1=1$deletedFilter ORDER BY rt.is_active DESC, rt.template_name ASC");
    }
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $templates = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
} else {
    if ($search) {
        $like = "%$search%";
        $stmt = $conn->prepare("SELECT dt.*, s.full_name as created_by_name FROM document_templates dt LEFT JOIN staff s ON dt.created_by = s.id WHERE (dt.template_name LIKE ? OR dt.template_type LIKE ? OR dt.template_content LIKE ?)$deletedFilterDt ORDER BY dt.is_default DESC, dt.template_name ASC");
        if ($stmt) $stmt->bind_param('sss', $like, $like, $like);
    } else {
        $stmt = $conn->prepare("SELECT dt.*, s.full_name as created_by_name FROM document_templates dt LEFT JOIN staff s ON dt.created_by = s.id WHERE 1=1$deletedFilterDt ORDER BY dt.is_default DESC, dt.template_name ASC");
    }
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $templates = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}
if ($conn) $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="container" style="margin-left:270px">
    <div class="header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-file-alt me-2"></i>Document Template Management</h2>
            <p class="mb-0">Manage receipt templates, document templates, and generated documents</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../student-directory.php" class="btn btn-sm btn-outline-info"><i class="fas fa-address-book me-1"></i>Directory</a>
            <a href="../store_request.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-shopping-cart me-1"></i>Store</a>
            <a href="../news.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-newspaper me-1"></i>News</a>
        </div>
        <a href="?table=receipt_templates" class="btn btn-light btn-sm">Receipt Templates</a>
        <a href="?table=document_templates" class="btn btn-light btn-sm">Document Templates</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'edit' || $action === 'create'): ?>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-<?php echo $action === 'edit' ? 'edit' : 'plus-circle'; ?> me-2"></i>
            <?php echo $action === 'edit' ? 'Edit Template' : 'Create New Template'; ?>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="save_template">
                <input type="hidden" name="id" value="<?php echo $editing['id'] ?? ''; ?>">
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($current_table); ?>">
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Template Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="template_name" value="<?php echo htmlspecialchars($editing['template_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Template Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="template_type" required>
                            <?php
                            $types = $current_table === 'receipt_templates' ? $receipt_types : $template_types;
                            foreach ($types as $type) {
                                $selected = (($editing['template_type'] ?? '') === $type) ? 'selected' : '';
                                echo "<option value=\"$type\" $selected>$type</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Status / Default</label>
                        <?php if ($current_table === 'receipt_templates'): ?>
                        <select class="form-select" name="is_active">
                            <option value="1" <?php echo (($editing['is_active'] ?? 1) == 1) ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo (($editing['is_active'] ?? 1) == 0) ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <?php else: ?>
                        <select class="form-select" name="is_default">
                            <option value="1" <?php echo (($editing['is_default'] ?? 0) == 1) ? 'selected' : ''; ?>>Default Template</option>
                            <option value="0" <?php echo (($editing['is_default'] ?? 0) == 0) ? 'selected' : ''; ?>>Custom Template</option>
                        </select>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Template Variables (comma separated)</label>
                    <input type="text" class="form-control" name="template_variables" 
                           value="<?php 
                           $vars = $editing['template_variables'] ?? '{}';
                           if (is_string($vars)) {
                               $arr = json_decode($vars, true);
                               echo htmlspecialchars(implode(', ', is_array($arr) ? array_keys($arr) : []));
                           }
                           ?>" 
                           placeholder="e.g. student_name, amount, date, receipt_number">
                    <small class="text-muted">Replaceable variables use {{variable_name}} in the template content.</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Template Content (HTML) <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="template_content" id="template_content" rows="15" required><?php echo htmlspecialchars($editing['template_content'] ?? ''); ?></textarea>
                    <small class="text-muted">Use {{variable_name}} for dynamic values. Supports HTML for formatting.</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Live Preview</label>
                    <div class="template-preview" id="templatePreview"><?php echo ($editing['template_content'] ?? '') ?: 'No preview available'; ?></div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Save Template
                    </button>
                    <a href="?table=<?php echo $current_table; ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i><?php echo $current_table === 'receipt_templates' ? 'Receipt Templates' : 'Document Templates'; ?></span>
            <a href="?action=create&table=<?php echo $current_table; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> New Template
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <input type="hidden" name="table" value="<?php echo $current_table; ?>">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search templates...">
                </div>
                <?php if ($current_table === 'receipt_templates'): ?>
                <div class="col-md-3">
                    <select class="form-select" name="template_type">
                        <option value="">All Types</option>
                        <?php foreach ($receipt_types as $rt): ?>
                        <option value="<?php echo $rt; ?>" <?php echo ($template_type_filter === $rt) ? 'selected' : ''; ?>><?php echo $rt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                    <a href="?table=<?php echo $current_table; ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Template Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($templates)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No templates found. <a href="?action=create&table=<?php echo $current_table; ?>">Create one now</a>.</td></tr>
                        <?php else: foreach ($templates as $t): ?>
                        <tr>
                            <td><?php echo $t['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($t['template_name']); ?></strong></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($t['template_type']); ?></span></td>
                            <td>
                                <?php if ($t['is_active']): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                                <?php if (!empty($t['is_default'])): ?>
                                    <span class="badge bg-warning text-dark">Default</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($t['created_by_name'] ?? 'System'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                            <td class="text-center action-btns" style="white-space:nowrap">
                                <button class="btn btn-outline-primary btn-sm" onclick="viewPreview(<?php echo $t['id']; ?>)" title="Preview">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="printTemplate(<?php echo $t['id']; ?>)" title="Print">
                                    <i class="fas fa-print"></i>
                                </button>
                                <a href="?action=edit&id=<?php echo $t['id']; ?>&table=<?php echo $current_table; ?>" class="btn btn-outline-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $t['id']; ?>" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php foreach ($templates as $t): ?>
    <div class="modal fade" id="deleteModal<?php echo $t['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to move "<strong><?php echo htmlspecialchars($t['template_name']); ?></strong>" to trash?</p>
                    <p class="text-warning small"><i class="fas fa-info-circle me-1"></i>This item will be moved to the <a href="recycle_bin.php" target="_blank">Recycle Bin</a> where it can be restored later.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="action" value="delete_template">
                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                        <input type="hidden" name="table" value="<?php echo $current_table; ?>">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="previewModal<?php echo $t['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo htmlspecialchars($t['template_name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="template-preview" style="max-height:500px;"><?php echo htmlspecialchars($t['template_content']); ?></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewPreview(id) {
    const modal = document.getElementById('previewModal' + id);
    if (modal) new bootstrap.Modal(modal).show();
}
function updatePreview() {
    const content = document.getElementById('template_content').value;
    const preview = document.getElementById('templatePreview');
    if (preview) preview.innerHTML = content || 'No preview available';
}
function printTemplate(id) {
    // Open preview then trigger print
    const modal = document.getElementById('previewModal' + id);
    if (!modal) return;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    modal.addEventListener('shown.bs.modal', function() {
        setTimeout(function() { window.print(); }, 300);
    }, { once: true });
}
document.addEventListener('DOMContentLoaded', function() {
    const ta = document.getElementById('template_content');
    if (ta) ta.addEventListener('input', updatePreview);
});
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
