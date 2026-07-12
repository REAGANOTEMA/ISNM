<?php
/**
 * Universal Department Approval Request Module
 * Any staff dashboard can include this to submit requests to the DG Approval Center.
 * Integrates with approval_workflow.php â†’ approval_requests â†’ Director General Approval Center.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in']) || ($_SESSION['type'] ?? '') !== 'staff') return;
if (isset($GLOBALS['_department_approval_loaded'])) return;
$GLOBALS['_department_approval_loaded'] = true;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/approval_workflow.php';

/**
 * Get the current user's submitted approval requests.
 */
if (!function_exists('getMyApprovalRequests')) {
function getMyApprovalRequests($conn = null, $limit = 10) {
    if (!$conn) { if (function_exists('getStaffConnection')) $conn = getStaffConnection(); }
    if (!$conn) return [];
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) return [];
    $requests = [];
    try {
        $sql = "SELECT ar.*, ws.workflow_name, ws.category as workflow_category,
                (SELECT stage_name FROM igangaschool_staffs.approval_stages WHERE id = ar.current_stage_id) as current_stage_name
                FROM igangaschool_staffs.approval_requests ar
                LEFT JOIN igangaschool_staffs.approval_workflows ws ON ar.workflow_id = ws.id
                WHERE ar.requester_id = ?
                ORDER BY ar.created_at DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ii', $userId, $limit);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) $requests[] = $row;
            $stmt->close();
        }
    } catch (Exception $e) { error_log('dept_approval getRequests: ' . $e->getMessage()); }
    return $requests;
}
}

/**
 * Render a compact "My Approval Requests" widget for any dashboard.
 */
if (!function_exists('renderMyApprovalRequestsWidget')) {
function renderMyApprovalRequestsWidget($conn = null) {
    $requests = getMyApprovalRequests($conn);
    if (empty($requests)) return '';
    $html = '<div class="card mb-3"><div class="card-header py-2" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
        <h6 class="mb-0" style="font-size:13px;font-weight:700;"><i class="fas fa-file-contract me-1" style="color:#3b82f6;"></i>My Approval Requests</h6></div>
        <div class="list-group list-group-flush" style="max-height:300px;overflow-y:auto;">';
    foreach ($requests as $r) {
        $sc = $r['status'] === 'Approved' ? 'success' : ($r['status'] === 'Rejected' ? 'danger' : ($r['status'] === 'Returned' ? 'warning text-dark' : 'primary'));
        $html .= '<div class="list-group-item py-2 px-3" style="font-size:12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div><span class="fw-semibold">' . htmlspecialchars(substr($r['title'], 0, 50)) . '</span>
                <br><span class="text-muted" style="font-size:11px;">' . htmlspecialchars($r['request_number']) . ' Â· ' . date('d M Y', strtotime($r['created_at'])) . '</span></div>
                <span class="badge bg-' . $sc . '" style="font-size:9px;">' . $r['status'] . '</span>
            </div></div>';
    }
    $html .= '</div></div>';
    return $html;
}
}

if (!function_exists('renderDepartmentApprovalButton')) {
function renderDepartmentApprovalButton() {
    ?>
    <button type="button" class="btn btn-sm btn-primary" onclick="openDepartmentApprovalModal()" title="Submit for Director General Approval" style="border-radius:8px;font-size:12px;">
        <i class="fas fa-paper-plane me-1"></i>Submit Request
    </button>
    <?php
}
}

if (!function_exists('renderDepartmentApprovalModal')) {
function renderDepartmentApprovalModal() {
    $conn = function_exists('getStaffConnection') ? getStaffConnection() : null;
    $categories = [];
    if ($conn) {
        try {
            $r = $conn->query("SELECT DISTINCT category FROM igangaschool_staffs.approval_workflows WHERE is_active=1 ORDER BY category");
            if ($r) while ($row = $r->fetch_assoc()) $categories[] = $row['category'];
        } catch (Exception $e) { error_log('dept_approval process: ' . $e->getMessage()); }
    }
    if (empty($categories)) $categories = ['General Administration','Human Resources','Finance','ICT','Academic','Admissions','Library','Store & Assets'];
    ?>
    <div class="modal fade" id="departmentApprovalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border:none;border-radius:16px;">
                <div class="modal-header" style="background:linear-gradient(135deg,#1e3a5f,#2d5a87);color:#fff;border:none;padding:20px 24px;">
                    <div>
                        <h5 class="modal-title fw-bold"><i class="fas fa-file-signature me-2"></i>New Approval Request</h5>
                        <p style="font-size:12px;opacity:0.8;margin:4px 0 0;">Submit for Director General review and final decision</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="departmentApprovalForm">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold" style="font-size:13px;">Request Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Budget Increase for Nursing Department" style="border-radius:8px;font-size:13px;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px;">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select" required style="border-radius:8px;font-size:13px;">
                                    <option value="Normal">Normal</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px;">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required style="border-radius:8px;font-size:13px;">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:13px;">Department</label>
                                <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($_SESSION['department'] ?? $_SESSION['role'] ?? '') ?>" style="border-radius:8px;font-size:13px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:13px;">Description / Justification <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="4" required placeholder="Explain the reason for this request, expected outcomes, and any supporting details..." style="border-radius:8px;font-size:13px;"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0 py-2" style="font-size:12px;border-radius:8px;">
                                    <i class="fas fa-info-circle me-1"></i>This request will be sent to the <strong>Director General</strong> for final approval. You will be notified when a decision is made.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e2e8f0;padding:16px 24px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitDepartmentApprovalBtn" style="border-radius:8px;padding:8px 28px;">
                        <i class="fas fa-paper-plane me-1"></i>Submit for Approval
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="departmentApprovalSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border:none;border-radius:16px;text-align:center;padding:24px;">
                <div style="font-size:48px;color:#10b981;margin-bottom:12px;"><i class="fas fa-check-circle"></i></div>
                <h5 class="fw-bold mb-2">Request Submitted</h5>
                <p style="font-size:13px;color:#64748b;margin-bottom:16px;" id="approvalSuccessMessage">Your request has been sent to the Director General for review.</p>
                <button type="button" class="btn btn-primary" onclick="closeApprovalSuccessModal()" style="border-radius:8px;">Done</button>
            </div>
        </div>
    </div>
    <?php
}
}

if (!function_exists('renderDepartmentApprovalScripts')) {
function renderDepartmentApprovalScripts() {
    ?>
    <script>
    window.openDepartmentApprovalModal = function() {
        var modal = new bootstrap.Modal(document.getElementById('departmentApprovalModal'));
        modal.show();
    };

    document.addEventListener('DOMContentLoaded', function() {
        var submitBtn = document.getElementById('submitDepartmentApprovalBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                var form = document.getElementById('departmentApprovalForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                var btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';
                var data = new FormData(form);
                data.append('action', 'submit_approval_request');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '../ajax/submit_approval_request.php', true);
                xhr.onload = function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit for Approval';
                    if (xhr.status === 200) {
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            if (resp.success) {
                                var dgModal = bootstrap.Modal.getInstance(document.getElementById('departmentApprovalModal'));
                                if (dgModal) dgModal.hide();
                                form.reset();
                                var successModal = new bootstrap.Modal(document.getElementById('departmentApprovalSuccessModal'));
                                document.getElementById('approvalSuccessMessage').textContent = resp.message || 'Request #' + resp.request_number + ' submitted.';
                                successModal.show();
                            } else {
                                alert('Error: ' + (resp.error || 'Failed to submit request'));
                            }
                        } catch(e) { alert('Unexpected response'); }
                    } else { alert('Network error'); }
                };
                xhr.onerror = function() { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit for Approval'; alert('Network error'); };
                xhr.send(data);
            });
        }
    });

    window.closeApprovalSuccessModal = function() {
        var modal = bootstrap.Modal.getInstance(document.getElementById('departmentApprovalSuccessModal'));
        if (modal) modal.hide();
    };
    </script>
    <?php
}
}
