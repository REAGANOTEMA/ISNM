<?php
/**
 * Store AJAX Handler
 * Handles all store-related AJAX operations for all dashboards
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

$ctx = bootstrapStaffDashboard();
$staffConn = $ctx['staff'];
$user = $ctx['user'];
$userId = (int)($user['id'] ?? 0);
$userRole = $_SESSION['role'] ?? '';
$userName = $user['full_name'] ?? 'Staff';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

if (!verifyCsrfToken()) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh.']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'get_inventory_by_category':
        $catId = (int)($_POST['category_id'] ?? 0);
        $rows = [];
        if ($catId > 0) {
            $stmt = $staffConn->prepare("SELECT id, item_name, unit, quantity, reorder_level FROM store_inventory WHERE category_id=? AND status='active' ORDER BY item_name");
            if ($stmt) {
                $stmt->bind_param("i", $catId);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
        } else {
            $r = $staffConn->query("SELECT id, item_name, unit, quantity, reorder_level, category_id FROM store_inventory WHERE status='active' ORDER BY item_name");
            if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
        }
        echo json_encode(['success' => true, 'items' => $rows]);
        break;

    case 'submit_requisition':
        $department = trim($_POST['department'] ?? '');
        $urgency = trim($_POST['urgency'] ?? 'medium');
        $notes = trim($_POST['notes'] ?? '');
        $items = $_POST['items'] ?? [];
        $submitDg = !empty($_POST['submit_for_dg']);

        if (empty($department)) {
            echo json_encode(['success' => false, 'message' => 'Department is required.']);
            break;
        }
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Select at least one item.']);
            break;
        }

        $validItems = [];
        foreach ($items as $item) {
            $itemId = (int)($item['item_id'] ?? 0);
            $qty = (float)($item['quantity'] ?? 0);
            if ($itemId > 0 && $qty > 0) {
                $check = $staffConn->prepare("SELECT id, item_name FROM store_inventory WHERE id=? AND status='active'");
                if ($check) {
                    $check->bind_param("i", $itemId);
                    $check->execute();
                    $res = $check->get_result()->fetch_assoc();
                    $check->close();
                    if ($res) {
                        $validItems[] = [
                            'item_id' => $itemId,
                            'quantity' => $qty,
                            'notes' => substr(trim($item['notes'] ?? ''), 0, 255),
                            'item_name' => $res['item_name']
                        ];
                    }
                }
            }
        }

        if (empty($validItems)) {
            echo json_encode(['success' => false, 'message' => 'No valid items selected.']);
            break;
        }

        $reqNum = 'SRQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $status = $submitDg ? 'pending_approval' : 'pending';

        $staffConn->begin_transaction();
        try {
            $stmt = $staffConn->prepare("INSERT INTO store_requests (request_number, requested_by, requester_name, department, notes, urgency, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $staffConn->error);
            $stmt->bind_param("sisssss", $reqNum, $userId, $userName, $department, $notes, $urgency, $status);
            if (!$stmt->execute()) throw new Exception('Insert failed: ' . $stmt->error);
            $requestId = $staffConn->insert_id;
            $stmt->close();

            $ins = $staffConn->prepare("INSERT INTO store_request_items (request_id, item_id, quantity_requested, notes) VALUES (?, ?, ?, ?)");
            if (!$ins) throw new Exception('Prepare items failed: ' . $staffConn->error);
            foreach ($validItems as $vi) {
                $ins->bind_param("iids", $requestId, $vi['item_id'], $vi['quantity'], $vi['notes']);
                if (!$ins->execute()) throw new Exception('Insert item failed: ' . $ins->error);
            }
            $ins->close();

            $staffConn->commit();
            $statusMsg = $submitDg
                ? "Requisition <strong>$reqNum</strong> submitted for <strong>Director General Approval</strong>!"
                : "Requisition <strong>$reqNum</strong> submitted successfully! The storekeeper will process it.";
            echo json_encode(['success' => true, 'message' => $statusMsg, 'request_number' => $reqNum]);
        } catch (Exception $e) {
            $staffConn->rollback();
            error_log("Store AJAX submit_requisition error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to submit requisition: ' . $e->getMessage()]);
        }
        break;

    case 'get_my_requisitions':
        $lim = min((int)($_POST['limit'] ?? 50), 200);
        $where = "sr.requested_by = ?";
        $params = [$userId];
        $types = 'i';

        $fStatus = trim($_POST['status'] ?? '');
        if ($fStatus && $fStatus !== 'all') {
            $where .= " AND sr.status = ?";
            $params[] = $fStatus;
            $types .= 's';
        }
        $fSearch = trim($_POST['search'] ?? '');
        if (strlen($fSearch) >= 2) {
            $qq = "%$fSearch%";
            $where .= " AND (sr.request_number LIKE ? OR sr.department LIKE ?)";
            $params[] = $qq;
            $params[] = $qq;
            $types .= 'ss';
        }

        $sql = "SELECT sr.*, COUNT(sri.id) as total_items,
                (SELECT GROUP_CONCAT(CONCAT(si.item_name, ' x', sri.quantity_requested) SEPARATOR ', ')
                 FROM store_request_items sri JOIN store_inventory si ON sri.item_id=si.id
                 WHERE sri.request_id=sr.id) as items_summary
                FROM store_requests sr
                WHERE $where
                GROUP BY sr.id
                ORDER BY sr.created_at DESC
                LIMIT $lim";
        $stmt = $staffConn->prepare($sql);
        $rows = [];
        if ($stmt) {
            if ($types) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        echo json_encode(['success' => true, 'requisitions' => $rows]);
        break;

    case 'get_all_pending_requisitions':
        $rows = [];
        $sql = "SELECT sr.*, s.full_name as requester_name,
                (SELECT GROUP_CONCAT(CONCAT(si.item_name, ' x', sri.quantity_requested) SEPARATOR ', ')
                 FROM store_request_items sri JOIN store_inventory si ON sri.item_id=si.id
                 WHERE sri.request_id=sr.id) as items_summary
                FROM store_requests sr
                LEFT JOIN staff s ON sr.requested_by=s.id
                WHERE sr.status IN ('pending','pending_approval','approved')
                ORDER BY FIELD(sr.status,'pending','pending_approval','approved'),
                         FIELD(sr.urgency,'urgent','high','medium','low'),
                         sr.created_at ASC
                LIMIT 100";
        $r = $staffConn->query($sql);
        if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['success' => true, 'requisitions' => $rows]);
        break;

    case 'update_requisition_status':
        $reqId = (int)($_POST['request_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $validStatuses = ['pending', 'pending_approval', 'approved', 'fulfilled', 'rejected'];
        if (!$reqId || !in_array($newStatus, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data.']);
            break;
        }
        $stmt = $staffConn->prepare("UPDATE store_requests SET status=?, rejection_reason=COALESCE(NULLIF(?,''), rejection_reason), updated_at=NOW() WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("ssi", $newStatus, $reason, $reqId);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(['success' => true, 'message' => "Request updated to $newStatus"]);
        break;

    case 'fulfill_requisition_item':
        $reqItemId = (int)($_POST['req_item_id'] ?? 0);
        $qty = (float)($_POST['quantity'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $reqId = (int)($_POST['request_id'] ?? 0);
        if ($qty <= 0 || $itemId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid quantity.']);
            break;
        }
        // Check available stock
        $cur = $staffConn->prepare("SELECT quantity FROM store_inventory WHERE id=?");
        $cur->bind_param("i", $itemId);
        $cur->execute();
        $curRow = $cur->get_result()->fetch_assoc();
        $cur->close();
        $avail = $curRow ? (float)$curRow['quantity'] : 0;
        $qty = min($qty, $avail);
        if ($qty <= 0) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock.']);
            break;
        }
        $staffConn->begin_transaction();
        try {
            $stmt = $staffConn->prepare("UPDATE store_request_items SET quantity_fulfilled=quantity_fulfilled+?, status='fulfilled' WHERE id=?");
            $stmt->bind_param("di", $qty, $reqItemId);
            $stmt->execute();
            $stmt->close();

            $stmt = $staffConn->prepare("UPDATE store_inventory SET quantity=quantity-? WHERE id=?");
            $stmt->bind_param("di", $qty, $itemId);
            $stmt->execute();
            $stmt->close();

            $reason = "Fulfilled request #$reqId";
            $stmt = $staffConn->prepare("INSERT INTO store_inventory_transactions (item_id, transaction_type, quantity, reason, created_by, reference_type, reference_id) VALUES (?, 'request_fulfilled', ?, ?, ?, 'request', ?)");
            $stmt->bind_param("idssi", $itemId, $qty, $reason, $userId, $reqId);
            $stmt->execute();
            $stmt->close();

            $staffConn->commit();
            echo json_encode(['success' => true, 'message' => "Fulfilled $qty units"]);
        } catch (Exception $e) {
            $staffConn->rollback();
            echo json_encode(['success' => false, 'message' => 'Fulfill failed: ' . $e->getMessage()]);
        }
        break;

    case 'get_store_stats':
        $stats = [
            'total_items' => 0,
            'total_categories' => 0,
            'pending_requests' => 0,
            'low_stock' => 0,
            'total_value' => 0,
        ];
        $r = $staffConn->query("SELECT COUNT(*) c FROM store_inventory WHERE status='active'");
        if ($r) $stats['total_items'] = (int)$r->fetch_assoc()['c'];
        $r = $staffConn->query("SELECT COUNT(*) c FROM store_categories WHERE status='active'");
        if ($r) $stats['total_categories'] = (int)$r->fetch_assoc()['c'];
        $r = $staffConn->query("SELECT COUNT(*) c FROM store_requests WHERE status IN ('pending','pending_approval')");
        if ($r) $stats['pending_requests'] = (int)$r->fetch_assoc()['c'];
        $r = $staffConn->query("SELECT COUNT(*) c FROM store_inventory WHERE status='active' AND quantity <= reorder_level");
        if ($r) $stats['low_stock'] = (int)$r->fetch_assoc()['c'];
        $r = $staffConn->query("SELECT COALESCE(SUM(quantity * unit_cost),0) tv FROM store_inventory WHERE status='active'");
        if ($r) $stats['total_value'] = (float)$r->fetch_assoc()['tv'];
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        break;
}
