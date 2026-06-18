<?php
/**
 * Approval Workflow System - Configurable multi-stage approval engine.
 * Supports: request creation, review, recommendation, approval, rejection, escalation.
 * Works with institutional_framework.php for recording audit trail.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('getWorkflows')) {
function getWorkflows($conn, $activeOnly = true) {
    $workflows = [];
    if (!$conn) return $workflows;
    try {
        $sql = "SELECT * FROM igangaschoolofl_staffs_db.approval_workflows";
        if ($activeOnly) $sql .= " WHERE is_active = 1";
        $sql .= " ORDER BY category, workflow_name";
        $result = $conn->query($sql);
        if ($result) { while ($row = $result->fetch_assoc()) $workflows[] = $row; }
    } catch (Exception $e) {}
    return $workflows;
}
}

if (!function_exists('getWorkflowStages')) {
function getWorkflowStages($workflowId, $conn) {
    $stages = [];
    if (!$conn) return $stages;
    try {
        $stmt = $conn->prepare("SELECT * FROM igangaschoolofl_staffs_db.approval_stages WHERE workflow_id = ? ORDER BY stage_order ASC");
        if (!$stmt) return $stages;
        $stmt->bind_param('i', $workflowId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $stages[] = $row;
        $stmt->close();
    } catch (Exception $e) {}
    return $stages;
}
}

if (!function_exists('createApprovalRequest')) {
function createApprovalRequest($workflowId, $title, $description, $requesterId, $requesterName, $requesterRole, $priority = 'Medium', $referenceType = null, $referenceId = null, $referenceUrl = null, $conn = null) {
    if (!$conn) {
        if (function_exists('getStaffConnection')) $conn = getStaffConnection();
    }
    if (!$conn) return false;
    try {
        $stages = getWorkflowStages($workflowId, $conn);
        if (empty($stages)) return false;
        $firstStage = $stages[0];
        $requestNumber = 'REQ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $stmt = $conn->prepare("INSERT INTO igangaschoolofl_staffs_db.approval_requests (workflow_id, request_number, title, description, priority, requester_id, requester_name, requester_role, current_stage_id, current_stage_order, status, reference_type, reference_id, reference_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, NOW())");
        if (!$stmt) return false;
        $order = (int)$firstStage['stage_order'];
        $stmt->bind_param('issssisssisis', $workflowId, $requestNumber, $title, $description, $priority, $requesterId, $requesterName, $requesterRole, $firstStage['id'], $order, $referenceType, $referenceId, $referenceUrl);
        $r = $stmt->execute();
        $stmt->close();
        if ($r && function_exists('recordAuditTrail')) {
            recordAuditTrail($requesterId, 'CREATE', 'Approval', 'Approval request created: ' . $title, 'approval_requests', null, $requestNumber, null, ['workflow_id' => $workflowId, 'title' => $title, 'priority' => $priority], $conn);
        }
        return $r;
    } catch (Exception $e) {
        error_log('createApprovalRequest error: ' . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('processApprovalAction')) {
function processApprovalAction($requestId, $staffId, $actionType, $comments = null, $notes = null, $conn = null) {
    if (!$conn) {
        if (function_exists('getStaffConnection')) $conn = getStaffConnection();
    }
    if (!$conn) return false;
    try {
        $reqStmt = $conn->prepare("SELECT ar.*, (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.approval_stages WHERE workflow_id = ar.workflow_id) as total_stages FROM igangaschoolofl_staffs_db.approval_requests ar WHERE ar.id = ?");
        if (!$reqStmt) return false;
        $reqStmt->bind_param('i', $requestId);
        $reqStmt->execute();
        $request = $reqStmt->get_result()->fetch_assoc();
        $reqStmt->close();
        if (!$request || $request['status'] !== 'Active') return false;

        $currentStageId = $request['current_stage_id'];
        $currentOrder = (int)$request['current_stage_order'];
        $totalStages = (int)$request['total_stages'];

        $actStmt = $conn->prepare("INSERT INTO igangaschoolofl_staffs_db.approval_actions (request_id, stage_id, action_by, action_type, comments, notes, decision, previous_stage_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$actStmt) return false;
        $decision = 'Approved';
        if ($actionType === 'reject') $decision = 'Rejected';
        elseif ($actionType === 'escalate') $decision = 'Escalated';
        elseif ($actionType === 'return') $decision = 'Returned';
        $prevOrder = $currentOrder;
        $actStmt->bind_param('iiiissi', $requestId, $currentStageId, $staffId, $actionType, $comments, $notes, $decision, $prevOrder);
        $actStmt->execute();
        $actStmt->close();

        if ($actionType === 'reject' || $actionType === 'cancel') {
            $updStmt = $conn->prepare("UPDATE igangaschoolofl_staffs_db.approval_requests SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?");
            $newStatus = $actionType === 'reject' ? 'Rejected' : 'Cancelled';
            if (!$updStmt) return false;
            $updStmt->bind_param('ssi', $newStatus, $comments, $requestId);
            $updStmt->execute();
            $updStmt->close();
        } elseif ($actionType === 'approve' && $currentOrder >= $totalStages) {
            $updStmt = $conn->prepare("UPDATE igangaschoolofl_staffs_db.approval_requests SET status = 'Approved', final_approval_by = ?, final_approval_at = NOW(), updated_at = NOW() WHERE id = ?");
            if (!$updStmt) return false;
            $updStmt->bind_param('ii', $staffId, $requestId);
            $updStmt->execute();
            $updStmt->close();
        } elseif ($actionType === 'approve' || $actionType === 'escalate') {
            $nextOrder = ($actionType === 'escalate') ? $currentOrder + 2 : $currentOrder + 1;
            if ($nextOrder > $totalStages) $nextOrder = $totalStages;
            $nextStmt = $conn->prepare("SELECT id FROM igangaschoolofl_staffs_db.approval_stages WHERE workflow_id = ? AND stage_order = ? LIMIT 1");
            if (!$nextStmt) return false;
            $nextStmt->bind_param('ii', $request['workflow_id'], $nextOrder);
            $nextStmt->execute();
            $nextStage = $nextStmt->get_result()->fetch_assoc();
            $nextStmt->close();
            if ($nextStage) {
                $updStmt = $conn->prepare("UPDATE igangaschoolofl_staffs_db.approval_requests SET current_stage_id = ?, current_stage_order = ?, status = CASE WHEN ? >= ? THEN 'Approved' ELSE 'Active' END, updated_at = NOW() WHERE id = ?");
                if (!$updStmt) return false;
                $updStmt->bind_param('iiiii', $nextStage['id'], $nextOrder, $nextOrder, $totalStages, $requestId);
                $updStmt->execute();
                $updStmt->close();
            }
        }

        if (function_exists('recordAuditTrail')) {
            $staffName = $_SESSION['full_name'] ?? 'Unknown';
            recordAuditTrail($staffId, strtoupper($actionType), 'Approval', 'Approval ' . $actionType . ': ' . $request['title'], 'approval_requests', $requestId, $request['request_number'], null, ['action' => $actionType, 'comments' => $comments], $conn);
        }
        return true;
    } catch (Exception $e) {
        error_log('processApprovalAction error: ' . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('renderApprovalWorkflowCard')) {
function renderApprovalWorkflowCard($request, $conn) {
    if (!$request) return '';
    $priorityClass = 'bg-info';
    if ($request['priority'] === 'Critical') $priorityClass = 'bg-danger';
    elseif ($request['priority'] === 'High') $priorityClass = 'bg-warning text-dark';
    elseif ($request['priority'] === 'Low') $priorityClass = 'bg-secondary';
    $html = '<div class="approval-request-card">';
    $html .= '  <div class="d-flex justify-content-between align-items-start">';
    $html .= '    <div>';
    $html .= '      <span class="badge ' . $priorityClass . '" style="font-size:8px">' . htmlspecialchars($request['priority']) . '</span>';
    $html .= '      <span class="badge bg-secondary ms-1" style="font-size:8px">' . htmlspecialchars($request['workflow_category'] ?? 'General') . '</span>';
    $html .= '    </div>';
    $html .= '    <small class="text-muted">#' . htmlspecialchars($request['request_number']) . '</small>';
    $html .= '  </div>';
    $html .= '  <h6 class="mt-2 mb-1 fw-semibold small">' . htmlspecialchars($request['title']) . '</h6>';
    if ($request['description']) $html .= '  <p class="small text-muted mb-2">' . htmlspecialchars(substr($request['description'], 0, 100)) . '</p>';
    $html .= '  <div class="d-flex justify-content-between align-items-center small">';
    $html .= '    <span class="text-muted">' . htmlspecialchars($request['requester_name']) . '</span>';
    $html .= '    <span class="badge bg-primary" style="font-size:8px">' . htmlspecialchars($request['current_stage_name'] ?? 'Processing') . '</span>';
    $html .= '  </div>';
    $html .= '  <div class="mt-2 text-muted" style="font-size:10px">' . date('d M Y H:i', strtotime($request['created_at'])) . '</div>';
    if ($request['reference_url']) $html .= '  <a href="' . htmlspecialchars($request['reference_url']) . '" class="btn btn-sm btn-link p-0 mt-1" style="font-size:10px"><i class="fas fa-external-link-alt"></i> View Related</a>';
    $html .= '</div>';
    return $html;
}
}

if (!function_exists('renderApprovalActionButtons')) {
function renderApprovalActionButtons($requestId, $showApprove = true, $showReject = true, $showEscalate = false) {
    $html = '<div class="approval-actions mt-2 d-flex gap-1">';
    if ($showApprove) $html .= '<button class="btn btn-sm btn-success" onclick="window.submitApprovalAction(' . $requestId . ', \'approve\')" style="font-size:10px"><i class="fas fa-check"></i> Approve</button>';
    if ($showReject) $html .= '<button class="btn btn-sm btn-danger" onclick="window.submitApprovalAction(' . $requestId . ', \'reject\')" style="font-size:10px"><i class="fas fa-times"></i> Reject</button>';
    if ($showEscalate) $html .= '<button class="btn btn-sm btn-warning" onclick="window.submitApprovalAction(' . $requestId . ', \'escalate\')" style="font-size:10px"><i class="fas fa-arrow-up"></i> Escalate</button>';
    $html .= '</div>';
    return $html;
}
}

// Register the global function once
if (!function_exists('registerApprovalActionHandler')) {
function registerApprovalActionHandler() {
    if (isset($GLOBALS['_approval_handler_registered'])) return;
    $GLOBALS['_approval_handler_registered'] = true;
    echo '<script>
    window.submitApprovalAction = function(requestId, action) {
        var comments = prompt("Enter comments for " + action + ":");
        if (comments === null) return;
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "../ajax/approval_action.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) { location.reload(); }
                    else { alert("Error: " + (resp.error || "Unknown error")); }
                } catch(e) { alert("Response: " + xhr.responseText); }
            }
        };
        xhr.onerror = function() { alert("Network error submitting approval action."); };
        xhr.send("request_id=" + requestId + "&action=" + action + "&comments=" + encodeURIComponent(comments));
    };
    </script>';
}
}
