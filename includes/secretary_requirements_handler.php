<?php
/**
 * Secretary Requirements Handler — AJAX for requirements checklist, store items, requisitions
 * Included by dashboards that need secretary requirements functionality
 */

if (!function_exists('handleSecretaryRequirementsAjax')) {
    function handleSecretaryRequirementsAjax($action, $conn, $user_id, $user_name, $staff_db, $response) {
        switch ($action) {

            // ── Store Items ──
            case 'get_store_items':
                $category = trim($_REQUEST['category'] ?? '');
                $search = trim($_REQUEST['search'] ?? '');
                $where = "WHERE 1=1";
                $params = [];
                $types = '';
                if ($category) { $where .= " AND category = ?"; $params[] = $category; $types .= 's'; }
                if ($search) { $where .= " AND item_name LIKE ?"; $params[] = "%$search%"; $types .= 's'; }
                $where .= " ORDER BY category, item_name";
                if ($types) {
                    $stmt = $conn->prepare("SELECT * FROM `$staff_db`.`store_items` $where");
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query("SELECT * FROM `$staff_db`.`store_items` $where");
                }
                $items = [];
                if ($result) { while ($r = $result->fetch_assoc()) { $items[] = $r; } }
                $response['success'] = true;
                $response['items'] = $items;
                break;

            case 'save_store_item':
                $id = (int)($_POST['item_id'] ?? 0);
                $item_name = trim($_POST['item_name'] ?? '');
                $category = trim($_POST['category'] ?? 'General Utilities');
                $unit = trim($_POST['unit'] ?? 'piece');
                $qty = max(0, (int)($_POST['quantity_in_stock'] ?? 0));
                $min = max(0, (int)($_POST['minimum_level'] ?? 0));
                $price = max(0, (float)($_POST['unit_price'] ?? 0));
                $location = trim($_POST['location'] ?? '');
                if (!$item_name) { $response['message'] = 'Item name required'; break; }
                if ($id > 0) {
                    $stmt = $conn->prepare("UPDATE `$staff_db`.`store_items` SET item_name=?, category=?, unit=?, quantity_in_stock=?, minimum_level=?, unit_price=?, location=? WHERE id=?");
                    $stmt->bind_param('sssiddsi', $item_name, $category, $unit, $qty, $min, $price, $location, $id);
                } else {
                    $status_val = 'Active';
                    if ($qty <= 0) $status_val = 'Out of Stock';
                    elseif ($qty <= $min) $status_val = 'Low Stock';
                    $stmt = $conn->prepare("INSERT INTO `$staff_db`.`store_items` (item_name, category, unit, quantity_in_stock, minimum_level, unit_price, location, status) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('sssiddss', $item_name, $category, $unit, $qty, $min, $price, $location, $status_val);
                }
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = $id > 0 ? 'Item updated' : 'Item added';
                } else {
                    $response['message'] = 'Failed: ' . $conn->error;
                }
                break;

            case 'delete_store_item':
                $id = (int)($_POST['item_id'] ?? 0);
                if (!$id) { $response['message'] = 'Invalid ID'; break; }
                $conn->query("DELETE FROM `$staff_db`.`store_items` WHERE id=$id");
                $response['success'] = true;
                $response['message'] = 'Item deleted';
                break;

            case 'update_stock':
                $id = (int)($_POST['item_id'] ?? 0);
                $qty = (int)($_POST['quantity_in_stock'] ?? -1);
                $min = (int)($_POST['minimum_level'] ?? -1);
                if (!$id || $qty < 0) { $response['message'] = 'Invalid data'; break; }
                $sets = ["quantity_in_stock=$qty"];
                $status_val = 'Active';
                if ($qty <= 0) $status_val = 'Out of Stock';
                $sets[] = "status='$status_val'";
                if ($min >= 0) { $sets[] = "minimum_level=$min"; if ($qty <= $min && $qty > 0) $status_val = 'Low Stock'; $sets[count($sets)-1] = "minimum_level=$min"; $sets[count($sets)-1] = "status='$status_val'"; }
                $conn->query("UPDATE `$staff_db`.`store_items` SET " . implode(',', $sets) . " WHERE id=$id");
                $response['success'] = true;
                $response['message'] = 'Stock updated';
                break;

            // ── Requisitions ──
            case 'get_requisitions':
                $status_filter = trim($_REQUEST['status'] ?? '');
                $dept_filter = trim($_REQUEST['department'] ?? '');
                $where = "WHERE 1=1";
                $params = [];
                $types = '';
                if ($status_filter) { $where .= " AND r.status = ?"; $params[] = $status_filter; $types .= 's'; }
                if ($dept_filter) { $where .= " AND r.department = ?"; $params[] = $dept_filter; $types .= 's'; }
                $where .= " ORDER BY r.created_at DESC";
                if ($types) {
                    $stmt = $conn->prepare("SELECT r.*, s.full_name AS requested_by_name FROM `$staff_db`.`store_requisitions` r LEFT JOIN `$staff_db`.`staff` s ON r.requested_by = s.id $where");
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query("SELECT r.*, s.full_name AS requested_by_name FROM `$staff_db`.`store_requisitions` r LEFT JOIN `$staff_db`.`staff` s ON r.requested_by = s.id $where");
                }
                $reqs = [];
                if ($result) { while ($r = $result->fetch_assoc()) { $reqs[] = $r; } }
                $response['success'] = true;
                $response['requisitions'] = $reqs;
                break;

            case 'save_requisition':
                $id = (int)($_POST['req_id'] ?? 0);
                $item_name = trim($_POST['item_name'] ?? '');
                $qty_req = max(1, (int)($_POST['quantity_requested'] ?? 1));
                $purpose = trim($_POST['purpose'] ?? '');
                $dept = trim($_POST['department'] ?? '');
                if (!$item_name) { $response['message'] = 'Item name required'; break; }
                if ($id > 0) {
                    $stmt = $conn->prepare("UPDATE `$staff_db`.`store_requisitions` SET item_name=?, quantity_requested=?, purpose=?, department=? WHERE id=?");
                    $stmt->bind_param('siisi', $item_name, $qty_req, $purpose, $dept, $id);
                } else {
                    $req_no = 'REQ-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $stmt = $conn->prepare("INSERT INTO `$staff_db`.`store_requisitions` (requisition_number, requested_by, requestor_name, department, item_name, quantity_requested, purpose) VALUES (?,?,?,?,?,?,?)");
                    $stmt->bind_param('sissisi', $req_no, $user_id, $user_name, $dept, $item_name, $qty_req, $purpose);
                }
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = $id > 0 ? 'Requisition updated' : 'Requisition submitted';
                } else {
                    $response['message'] = 'Failed: ' . $conn->error;
                }
                break;

            case 'update_requisition_status':
                $id = (int)($_POST['req_id'] ?? 0);
                $new_status = $_POST['new_status'] ?? '';
                $qty_approved = (int)($_POST['quantity_approved'] ?? 0);
                $valid = ['Approved','Rejected','Issued','Partially Issued'];
                if (!$id || !in_array($new_status, $valid)) { $response['message'] = 'Invalid data'; break; }
                $extra = '';
                $params_a = [$new_status];
                $types_a = 's';
                if ($new_status === 'Approved') { $extra = ", approved_by=?, approved_at=NOW()"; $params_a[] = $user_id; $types_a .= 'i'; }
                if ($new_status === 'Issued' || $new_status === 'Partially Issued') { $extra = ", issued_at=NOW()"; if ($qty_approved > 0) { $extra .= ", quantity_approved=?"; $params_a[] = $qty_approved; $types_a .= 'i'; } }
                $params_a[] = $id;
                $types_a .= 'i';
                $stmt = $conn->prepare("UPDATE `$staff_db`.`store_requisitions` SET status=?$extra WHERE id=?");
                $stmt->bind_param($types_a, ...$params_a);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Status updated';
                } else {
                    $response['message'] = 'Failed: ' . $conn->error;
                }
                break;

            case 'delete_requisition':
                $id = (int)($_POST['req_id'] ?? 0);
                if (!$id) { $response['message'] = 'Invalid ID'; break; }
                $conn->query("DELETE FROM `$staff_db`.`store_requisitions` WHERE id=$id");
                $response['success'] = true;
                $response['message'] = 'Requisition deleted';
                break;

            // ── Student Application Requirements (per student) ──
            case 'get_student_app_requirements':
                $student_num = trim($_REQUEST['student_number'] ?? '');
                if (!$student_num) { $response['message'] = 'Student number required'; break; }
                $result = $conn->query("SELECT * FROM `$staff_db`.`student_application_requirements` WHERE student_number='" . $conn->real_escape_string($student_num) . "' ORDER BY category, requirement_name");
                $reqs = [];
                if ($result) { while ($r = $result->fetch_assoc()) { $reqs[] = $r; } }
                $response['success'] = true;
                $response['requirements'] = $reqs;
                break;

            case 'save_student_app_requirement':
                $student_num = trim($_POST['student_number'] ?? '');
                $student_name = trim($_POST['student_name'] ?? '');
                $req_name = trim($_POST['requirement_name'] ?? '');
                $category = trim($_POST['category'] ?? 'Application');
                $status = $_POST['status'] ?? 'Pending';
                $remarks = trim($_POST['remarks'] ?? '');
                if (!$student_num || !$req_name) { $response['message'] = 'Student number and requirement name required'; break; }
                $valid_status = ['Pending','Submitted','Missing','Cleared','Rejected'];
                if (!in_array($status, $valid_status)) $status = 'Pending';
                $verified_by = null;
                $verified_at = null;
                if ($status === 'Cleared' || $status === 'Verified') { $verified_by = $user_name; $verified_at = date('Y-m-d H:i:s'); }
                $stmt = $conn->prepare("INSERT INTO `$staff_db`.`student_application_requirements` (student_number, student_name, requirement_name, category, status, remarks, verified_by, verified_at) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), remarks=VALUES(remarks), verified_by=VALUES(verified_by), verified_at=VALUES(verified_at)");
                $stmt->bind_param('ssssssss', $student_num, $student_name, $req_name, $category, $status, $remarks, $verified_by, $verified_at);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Requirement updated';
                } else {
                    $response['message'] = 'Failed: ' . $conn->error;
                }
                break;

            case 'bulk_init_student_requirements':
                $student_num = trim($_POST['student_number'] ?? '');
                $student_name = trim($_POST['student_name'] ?? '');
                if (!$student_num) { $response['message'] = 'Student number required'; break; }
                $existing = $conn->query("SELECT COUNT(*) as cnt FROM `$staff_db`.`student_application_requirements` WHERE student_number='" . $conn->real_escape_string($student_num) . "'");
                $cnt = ($existing && $existing->num_rows > 0) ? (int)$existing->fetch_assoc()['cnt'] : 0;
                if ($cnt > 0) { $response['success'] = true; $response['message'] = 'Requirements already initialized'; break; }
                $apps = [
                    ['Completed Application Form','Application'],['A-Level Certificate (UACE)','Application'],
                    ['O-Level Certificate (UCE)','Application'],['Birth Certificate','Application'],
                    ['Passport Photos (4)','Application'],['National ID Copy','Application'],
                    ['Medical Report','Application'],['Recommendation Letter (LC1)','Application']
                ];
                $ins = $conn->prepare("INSERT INTO `$staff_db`.`student_application_requirements` (student_number, student_name, requirement_name, category, status) VALUES (?,?,?,?,?)");
                foreach ($apps as $a) {
                    $status = 'Pending';
                    $ins->bind_param('sssss', $student_num, $student_name, $a[0], $a[1], $status);
                    $ins->execute();
                }
                $ins->close();
                $response['success'] = true;
                $response['message'] = 'Application requirements initialized';
                break;

            case 'clear_student_requirement':
                $id = (int)($_POST['req_id'] ?? 0);
                $cleared = (int)($_POST['cleared'] ?? 1);
                if (!$id) { $response['message'] = 'Invalid ID'; break; }
                $new_status = $cleared ? 'Cleared' : 'Pending';
                $v_by = $cleared ? $user_name : null;
                $v_at = $cleared ? date('Y-m-d H:i:s') : null;
                $stmt = $conn->prepare("UPDATE `$staff_db`.`student_application_requirements` SET status=?, verified_by=?, verified_at=? WHERE id=?");
                $stmt->bind_param('sssi', $new_status, $v_by, $v_at, $id);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = $cleared ? 'Requirement cleared' : 'Requirement unmarked';
                } else {
                    $response['message'] = 'Failed';
                }
                break;

            // ── Store Stats ──
            case 'get_store_stats':
                $total = $conn->query("SELECT COUNT(*) as c FROM `$staff_db`.`store_items`");
                $low = $conn->query("SELECT COUNT(*) as c FROM `$staff_db`.`store_items` WHERE status='Low Stock'");
                $out = $conn->query("SELECT COUNT(*) as c FROM `$staff_db`.`store_items` WHERE status='Out of Stock'");
                $pending = $conn->query("SELECT COUNT(*) as c FROM `$staff_db`.`store_requisitions` WHERE status='Pending'");
                $response['success'] = true;
                $response['stats'] = [
                    'total_items' => ($total && $total->num_rows > 0) ? (int)$total->fetch_assoc()['c'] : 0,
                    'low_stock' => ($low && $low->num_rows > 0) ? (int)$low->fetch_assoc()['c'] : 0,
                    'out_of_stock' => ($out && $out->num_rows > 0) ? (int)$out->fetch_assoc()['c'] : 0,
                    'pending_requisitions' => ($pending && $pending->num_rows > 0) ? (int)$pending->fetch_assoc()['c'] : 0,
                ];
                break;

            default:
                return false;
        }
        return true;
    }
}
