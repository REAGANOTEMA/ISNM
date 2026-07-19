<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/approval_center.php';

$action = $_POST['action'] ?? '';
$conn = getStaffConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

if ($action === 'get_requests') {
    $filters = [];
    $filter = $_POST['filter'] ?? 'active';
    if ($filter !== 'all') $filters['status'] = $filter;
    if (!empty($_POST['search'])) $filters['search'] = $_POST['search'];
    $requests = getApprovalCenterRequests($conn, $filters);
    echo json_encode(['success' => true, 'html' => renderApprovalRequestList($requests)]);
    exit;
}

if ($action === 'get_detail') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Missing request ID']);
        exit;
    }
    try {
        $sql = "SELECT ar.*, ws.workflow_name, ws.category as workflow_category,
                (SELECT stage_name FROM igangaschool_staffs.approval_stages WHERE id = ar.current_stage_id) as current_stage_name,
                (SELECT COUNT(*) FROM igangaschool_staffs.approval_stages WHERE workflow_id = ar.workflow_id) as total_stages
                FROM igangaschool_staffs.approval_requests ar
                LEFT JOIN igangaschool_staffs.approval_workflows ws ON ar.workflow_id = ws.id
                WHERE ar.id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Query failed']);
            exit;
        }
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $req = $result->fetch_assoc();
        $stmt->close();

        if (!$req) {
            echo json_encode(['success' => false, 'error' => 'Request not found']);
            exit;
        }

        $priorityColors = [
            'Critical' => '#ef4444', 'Urgent' => '#f59e0b', 'High' => '#f97316',
            'Medium' => '#3b82f6', 'Normal' => '#64748b', 'Low' => '#94a3b8'
        ];

        $data = [
            'id' => (int)$req['id'],
            'title' => $req['title'],
            'request_number' => $req['request_number'],
            'description' => $req['description'],
            'priority' => $req['priority'] ?? 'Normal',
            'priority_color' => $priorityColors[$req['priority']] ?? '#64748b',
            'status' => $req['status'] ?? 'Active',
            'category' => $req['workflow_category'] ?? 'General',
            'workflow_name' => $req['workflow_name'] ?? '',
            'requester_name' => $req['requester_name'] ?? 'Unknown',
            'created_at_formatted' => date('d M Y H:i', strtotime($req['created_at'])),
            'current_stage_name' => $req['current_stage_name'] ?? 'Pending',
            'reference_type' => $req['reference_type'] ?? '',
        ];

        // Final approver name
        if (!empty($req['final_approval_by'])) {
            $faStmt = $conn->prepare("SELECT full_name FROM igangaschool_staffs.staff WHERE id = ?");
            if ($faStmt) { $faStmt->bind_param('i', $req['final_approval_by']); if (!$faStmt->execute()) { error_log('$faStmt execute failed: ' . ($faStmt->error ?? 'unknown')); }; $fa = $faStmt->get_result(); $faStmt->close(); } else { $fa = false; }
            $data['final_approval_by_name'] = ($fa && $f = $fa->fetch_assoc()) ? $f['full_name'] : 'Unknown';
        } else {
            $data['final_approval_by_name'] = null;
        }

        // Timeline from approval_actions
        $timeline = [];
        $taStmt = $conn->prepare("SELECT aa.*, s.full_name as action_by_name FROM igangaschool_staffs.approval_actions aa LEFT JOIN igangaschool_staffs.staff s ON aa.action_by = s.id WHERE aa.request_id = ? ORDER BY aa.created_at ASC");
        if ($taStmt) { $taStmt->bind_param('i', $id); if (!$taStmt->execute()) { error_log('$taStmt execute failed: ' . ($taStmt->error ?? 'unknown')); }; $ta = $taStmt->get_result(); $taStmt->close(); } else { $ta = false; }
        if ($ta) {
            while ($t = $ta->fetch_assoc()) {
                $actionLabels = [
                    'create' => 'Request Created', 'approve' => 'Approved',
                    'reject' => 'Rejected', 'return' => 'Returned for Correction',
                    'escalate' => 'Escalated', 'cancel' => 'Cancelled'
                ];
                $timeline[] = [
                    'action_type' => $t['action_type'],
                    'action_label' => $actionLabels[$t['action_type']] ?? ucfirst($t['action_type']),
                    'action_by_name' => $t['action_by_name'] ?? 'System',
                    'comments' => $t['comments'] ?? '',
                    'created_at_formatted' => date('d M Y H:i', strtotime($t['created_at']))
                ];
            }
        }
        $data['timeline'] = $timeline;

        // Comments (non-null comments from actions)
        $comments = [];
        $caStmt = $conn->prepare("SELECT aa.*, s.full_name as action_by_name FROM igangaschool_staffs.approval_actions aa LEFT JOIN igangaschool_staffs.staff s ON aa.action_by = s.id WHERE aa.request_id = ? AND aa.comments IS NOT NULL AND aa.comments != '' ORDER BY aa.created_at DESC");
        if ($caStmt) { $caStmt->bind_param('i', $id); if (!$caStmt->execute()) { error_log('$caStmt execute failed: ' . ($caStmt->error ?? 'unknown')); }; $ca = $caStmt->get_result(); $caStmt->close(); } else { $ca = false; }
        if ($ca) {
            while ($c = $ca->fetch_assoc()) {
                $comments[] = [
                    'action_by_name' => $c['action_by_name'] ?? 'System',
                    'action_type' => $c['action_type'],
                    'comments' => $c['comments'],
                    'created_at_formatted' => date('d M Y H:i', strtotime($c['created_at']))
                ];
            }
        }
        $data['comments'] = $comments;

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    } catch (Exception $e) {
        error_log('approval_center_ajax error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'An error occurred loading approval details']);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
