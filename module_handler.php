<?php
/**
 * ISNM UNIFIED CRUD MODULE HANDLER
 * Single entry point for all module API operations.
 * 
 * Endpoints (via AJAX POST/GET):
 *   ?action=fetch&module=name        — Fetch records
 *   ?action=create&module=name       — Create record
 *   ?action=update&module=name&id=1  — Update record
 *   ?action=delete&module=name&id=1  — Delete record
 *   ?action=search&module=name&q=... — Search records
 *   ?action=sidebar&role_id=1        — Get sidebar JSON
 *   ?action=modules&role_id=1        — Get all modules for role
 *   ?action=tables&module=name       — Get tables for module
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/module_registry.php';

header('Content-Type: application/json');

// Authentication check
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required', 'code' => 401]);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$moduleName = $_REQUEST['module'] ?? '';
$roleId = (int)($_SESSION['role_id'] ?? 0);
$staffId = (int)($_SESSION['user_id'] ?? 0);

// Auto-detect role from session
if (!$roleId && isset($_SESSION['role'])) {
    $roleMap = [
        'Director General' => 1, 'CEO' => 2, 'Director Academics' => 3,
        'Director Finance' => 4, 'Director ICT' => 5, 'School Principal' => 6,
        'Deputy Principal' => 7, 'Academic Registrar' => 8, 'HR Manager' => 9,
        'School Secretary' => 10, 'School Librarian' => 11, 'Head of Nursing' => 12,
        'Head of Midwifery' => 13, 'Senior Lecturer' => 14, 'Lecturer' => 15,
        'Matron' => 16, 'Warden' => 17, 'Sickbay Nurse' => 18, 'Driver' => 19,
        'Security Officer' => 20, 'Storekeeper' => 21, 'Guild President' => 22,
        'Computer Lab Manager' => 23, 'School Bursar' => 24, 'Store Keeper' => 25,
        'Director Admissions' => 26, 'Bursar' => 27
    ];
    $roleId = $roleMap[$_SESSION['role']] ?? 0;
}

/**
 * Validate request
 */
if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'Missing action parameter']);
    exit;
}

// CSRF protection for state-changing actions
$stateChangingActions = ['create', 'update', 'delete'];
if (in_array($action, $stateChangingActions, true)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $csrfSessionToken = $_SESSION['csrf_token'] ?? '';
    $csrfRequestToken = $_POST['csrf_token'] ?? $_REQUEST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (empty($csrfSessionToken) || empty($csrfRequestToken) || !hash_equals($csrfSessionToken, $csrfRequestToken)) {
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed', 'code' => 419]);
        exit;
    }
}

$registry = getModuleRegistry();

// Direct table CRUD helper (for tables not registered as modules)
$ALLOWED_TABLES = [
    'library_books', 'library_members', 'library_borrowing', 'library_acquisitions',
    'student_documents', 'staff_communications', 'announcements',
    'volunteer_applications', 'volunteer_hours',
    'news', 'news_categories', 'notifications', 'notification_reads',
    'student_fee_tracking', 'student_fee_assignments', 'student_invoices',
    'payments', 'fee_structures', 'expenses', 'bank_reconciliation',
    'student_welfare_cases', 'student_welfare_notes',
    'security_incidents', 'security_incident_notes', 'access_control_logs',
    'staff_inbox', 'staff_activity_log', 'password_changes',
    'ict_assets', 'ict_asset_categories', 'ict_server_status',
    'ict_backup_logs', 'ict_tickets', 'ict_audit_logs',
    'lab_equipment', 'lab_bookings', 'lab_attendance',
    'payroll_employees', 'payroll_runs', 'payroll_details',
    'salary_structures', 'payroll_settings', 'payroll_approvals',
    'contact_submissions', 'volunteer_applications', 'donations',
    'student_profiles', 'student_notifications',
    'cybersecurity_incidents', 'system_activity_logs',
    'system_modules', 'system_settings',
    'it_support_tickets', 'ict_servers', 'ict_system_backups',
    'ict_security_logs', 'ict_system_alerts', 'ict_system_notifications',
    'ict_asset_assignments', 'ict_asset_maintenance',
    'network_devices', 'lab_rooms', 'lab_computers',
    'lab_practical_sessions', 'lab_equipment_checkout',
    'printing_jobs', 'printing_charges', 'computer_repairs',
    'software_inventory', 'software_installations', 'lab_consumables',
    'student_id_cards', 'id_card_print_history', 'id_card_replacements'
];

/**
 * Map table names to their database connections.
 * Returns the correct mysqli connection for the given table.
 */
function getDirectDbConn($table = '') {
    static $conns = [];
    
    // ICT DB tables
    $ictPrefixes = ['ict_', 'cybersecurity_incidents', 'system_activity_logs', 'it_support_tickets',
        'network_devices', 'lab_', 'printing_', 'computer_repairs', 'software_inventory',
        'software_installations', 'lab_consumables', 'ict_servers', 'ict_system_backups',
        'ict_security_logs', 'ict_system_alerts', 'ict_system_notifications'];
    
    // Website DB tables
    $webTables = ['news', 'news_categories', 'notifications', 'notification_reads',
        'contact_submissions', 'volunteer_applications', 'volunteer_hours', 'donations',
        'student_applications', 'pages', 'announcements'];
    
    // Determine which database to use
    $dbKey = 'staffs'; // default
    if (!empty($table)) {
        $isIct = false;
        foreach ($ictPrefixes as $prefix) {
            if (strpos($table, $prefix) === 0 || $table === $prefix) { $isIct = true; break; }
        }
        if ($isIct) {
            $dbKey = 'ict';
        } elseif (in_array($table, $webTables, true)) {
            $dbKey = 'website';
        } elseif (strpos($table, 'student_') === 0 || strpos($table, 'payment_') === 0 ||
                   strpos($table, 'fee_') === 0 || strpos($table, 'expense') === 0 ||
                   strpos($table, 'bank_') === 0 || $table === 'payments') {
            $dbKey = 'students';
        }
    }
    
    if (!isset($conns[$dbKey])) {
        switch ($dbKey) {
            case 'ict':
                $conns[$dbKey] = @getICTConnection();
                break;
            case 'website':
                $conns[$dbKey] = @getWebsiteConnection();
                break;
            case 'students':
                $conns[$dbKey] = @getStudentsConnection();
                break;
            default:
                $conns[$dbKey] = @getStaffConnection();
                if (!$conns[$dbKey]) $conns[$dbKey] = @getStudentsConnection();
                break;
        }
    }
    return $conns[$dbKey] ?? null;
}

function directTableColumns($conn, $table) {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    $cols = [];
    $r = @$conn->query("SHOW COLUMNS FROM `{$table}`");
    if ($r) while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];
    $cache[$table] = $cols;
    return $cols;
}

function isAllowedTable($name) {
    global $ALLOWED_TABLES;
    return in_array($name, $ALLOWED_TABLES, true) && preg_match('/^[a-zA-Z_]+$/', $name);
}

switch ($action) {
    // ─── SIDEBAR ───
    case 'sidebar':
        $targetRoleId = (int)($_REQUEST['role_id'] ?? $roleId);
        $sidebar = $registry->getSidebarForRole($targetRoleId);
        echo json_encode(['success' => true, 'sidebar' => $sidebar]);
        break;
    
    // ─── MODULES LIST ───
    case 'modules':
        $targetRoleId = (int)($_REQUEST['role_id'] ?? $roleId);
        $modules = $registry->getModulesForRole($targetRoleId);
        echo json_encode(['success' => true, 'modules' => $modules, 'count' => count($modules)]);
        break;
    
    // ─── MODULE INFO ───
    case 'info':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $module = $registry->getModule($moduleName);
        if (!$module) {
            echo json_encode(['error' => 'Module not found']);
            break;
        }
        echo json_encode(['success' => true, 'module' => $module]);
        break;
    
    // ─── TABLES ───
    case 'tables':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $tables = $registry->getModuleTables($moduleName);
        echo json_encode(['success' => true, 'tables' => $tables]);
        break;
    
    // ─── FETCH RECORDS ───
    case 'fetch':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        
        $filters = [];
        $skipKeys = ['action', 'module', 'limit', 'offset', 'csrf_token', 'q', 'role_id', 'id', 'tab', 'page', 'section'];
        foreach ($_GET as $key => $val) {
            if (in_array($key, $skipKeys, true)) continue;
            if (empty($val)) continue;
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) continue;
            $filters[$key] = $val;
        }
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $module = $registry->getModule($moduleName);
        if ($module) {
            if (!$registry->checkPermission($module['id'], $roleId, 'view')) {
                echo json_encode(['error' => 'Access denied', 'code' => 403]); break;
            }
            $result = $registry->fetchRecords($moduleName, $filters, $limit, $offset);
            $registry->logAccess($module['id'], $staffId, 'view');
            echo json_encode(['success' => true] + $result);
        } elseif (isAllowedTable($moduleName)) {
            $conn = getDirectDbConn($moduleName);
            if (!$conn) { echo json_encode(['error' => 'No database connection']); break; }
            $tableCheck = @$conn->query("SHOW TABLES LIKE '{$moduleName}'");
            if (!$tableCheck || $tableCheck->num_rows === 0) { echo json_encode(['error' => "Table {$moduleName} not found"]); break; }
            $validCols = directTableColumns($conn, $moduleName);
            $where = [];
            $types = '';
            $bindVals = [];
            foreach ($filters as $col => $val) {
                if (in_array($col, $validCols, true)) { $where[] = "`{$col}` = ?"; $types .= 's'; $bindVals[] = $val; }
            }
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            if (!empty($bindVals)) {
                $stmt = $conn->prepare("SELECT * FROM `{$moduleName}` {$whereClause} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}");
                if ($stmt) { $stmt->bind_param($types, ...$bindVals); $stmt->execute(); $result = $stmt->get_result(); } else { $result = null; }
            } else {
                $result = @$conn->query("SELECT * FROM `{$moduleName}` {$whereClause} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}");
            }
            if (!$result) { echo json_encode(['error' => $conn->error]); break; }
            $data = [];
            while ($row = $result->fetch_assoc()) $data[] = $row;
            if (!empty($bindVals)) {
                $cstmt = $conn->prepare("SELECT COUNT(*) as total FROM `{$moduleName}` {$whereClause}");
                if ($cstmt) { $cstmt->bind_param($types, ...$bindVals); $cstmt->execute(); $countResult = $cstmt->get_result(); } else { $countResult = null; }
            } else {
                $countResult = @$conn->query("SELECT COUNT(*) as total FROM `{$moduleName}` {$whereClause}");
            }
            $total = 0;
            if ($countResult) { $cr = $countResult->fetch_assoc(); $total = $cr ? (int)$cr['total'] : 0; }
            echo json_encode(['success' => true, 'data' => $data, 'columns' => $validCols, 'total' => $total, 'limit' => $limit, 'offset' => $offset, 'table' => $moduleName]);
        } else {
            echo json_encode(['error' => 'Module not found']);
        }
        break;
    
    // ─── CREATE RECORD ───
    case 'create':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $module = $registry->getModule($moduleName);
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        $data = array_filter($input, function($v) { return $v !== '' && $v !== null; });
        
        if (empty($data)) {
            echo json_encode(['error' => 'No data provided']);
            break;
        }
        
        if ($module) {
            if (!$registry->checkPermission($module['id'], $roleId, 'create')) {
                echo json_encode(['error' => 'Access denied', 'code' => 403]);
                break;
            }
            $result = $registry->createRecord($moduleName, $data);
            if (isset($result['success']) && $result['success']) {
                $registry->logAccess($module['id'], $staffId, 'create', $result['id'] ?? null, $data);
            }
            echo json_encode($result);
        } elseif (isAllowedTable($moduleName)) {
            $conn = getDirectDbConn();
            if (!$conn) { echo json_encode(['error' => 'No database connection']); break; }
            $validCols = directTableColumns($conn, $moduleName);
            $filtered = [];
            foreach ($data as $col => $val) {
                if (in_array($col, $validCols, true)) $filtered[$col] = $val;
            }
            if (empty($filtered)) { echo json_encode(['error' => 'No valid columns']); break; }
            $cols = array_keys($filtered);
            $ph = array_fill(0, count($cols), '?');
            $types = '';
            $vals = [];
            foreach ($filtered as $v) { $vals[] = $v; $types .= is_int($v) ? 'i' : 's'; }
            $sql = "INSERT INTO `{$moduleName}` (" . implode('`, `', $cols) . ") VALUES (" . implode(',', $ph) . ")";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { echo json_encode(['error' => $conn->error]); break; }
            $stmt->bind_param($types, ...$vals);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'id' => $stmt->insert_id, 'message' => 'Record created']);
            } else {
                echo json_encode(['error' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Module not found']);
        }
        break;
    
    // ─── UPDATE RECORD ───
    case 'update':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $id = (int)($_REQUEST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Missing record ID']); break; }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        $data = array_filter($input, function($v) { return $v !== '' && $v !== null; });
        if (empty($data)) { echo json_encode(['error' => 'No data provided']); break; }
        
        $module = $registry->getModule($moduleName);
        if ($module) {
            if (!$registry->checkPermission($module['id'], $roleId, 'edit')) {
                echo json_encode(['error' => 'Access denied', 'code' => 403]); break;
            }
            $result = $registry->updateRecord($moduleName, $id, $data);
            if (isset($result['success']) && $result['success']) $registry->logAccess($module['id'], $staffId, 'update', $id, $data);
            echo json_encode($result);
        } elseif (isAllowedTable($moduleName)) {
            $conn = getDirectDbConn();
            if (!$conn) { echo json_encode(['error' => 'No database connection']); break; }
            $validCols = directTableColumns($conn, $moduleName);
            $sets = []; $vals = []; $types = '';
            foreach ($data as $col => $val) {
                if (in_array($col, $validCols, true)) { $sets[] = "`{$col}` = ?"; $vals[] = $val; $types .= is_int($val) ? 'i' : 's'; }
            }
            if (empty($sets)) { echo json_encode(['error' => 'No valid columns']); break; }
            $vals[] = $id; $types .= 'i';
            $sql = "UPDATE `{$moduleName}` SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { echo json_encode(['error' => $conn->error]); break; }
            $stmt->bind_param($types, ...$vals);
            if ($stmt->execute()) echo json_encode(['success' => true, 'affected_rows' => $stmt->affected_rows]);
            else echo json_encode(['error' => $stmt->error]);
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Module not found']);
        }
        break;
    
    // ─── DELETE RECORD ───
    case 'delete':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $id = (int)($_REQUEST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Missing record ID']); break; }
        
        $module = $registry->getModule($moduleName);
        if ($module) {
            if (!$registry->checkPermission($module['id'], $roleId, 'delete')) {
                echo json_encode(['error' => 'Access denied', 'code' => 403]); break;
            }
            $result = $registry->deleteRecord($moduleName, $id);
            if (isset($result['success']) && $result['success']) $registry->logAccess($module['id'], $staffId, 'delete', $id);
            echo json_encode($result);
        } elseif (isAllowedTable($moduleName)) {
            $conn = getDirectDbConn();
            if (!$conn) { echo json_encode(['error' => 'No database connection']); break; }
            $sql = "DELETE FROM `{$moduleName}` WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { echo json_encode(['error' => $conn->error]); break; }
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) echo json_encode(['success' => true, 'affected_rows' => $stmt->affected_rows]);
            else echo json_encode(['error' => $stmt->error]);
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Module not found']);
        }
        break;
    
    // ─── SEARCH ───
    case 'search':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $query = trim($_REQUEST['q'] ?? '');
        if (strlen($query) < 2) {
            echo json_encode(['error' => 'Query too short (min 2 chars)']);
            break;
        }
        if (strlen($query) > 100) {
            $query = substr($query, 0, 100);
        }
        if (!preg_match('/^[a-zA-Z0-9\s\-_.]+$/', $query)) {
            echo json_encode(['error' => 'Invalid search characters']);
            break;
        }
        
        $results = $registry->searchModule($moduleName, $query);
        echo json_encode(['success' => true, 'results' => $results, 'count' => count($results)]);
        break;
    
    // ─── PERMISSIONS CHECK ───
    case 'permissions':
        $targetRoleId = (int)($_REQUEST['role_id'] ?? $roleId);
        $modules = $registry->getAllModules();
        $perms = [];
        foreach ($modules as $mod) {
            $perms[$mod['name']] = [
                'view' => $registry->checkPermission($mod['id'], $targetRoleId, 'view'),
                'create' => $registry->checkPermission($mod['id'], $targetRoleId, 'create'),
                'edit' => $registry->checkPermission($mod['id'], $targetRoleId, 'edit'),
                'delete' => $registry->checkPermission($mod['id'], $targetRoleId, 'delete'),
                'approve' => $registry->checkPermission($mod['id'], $targetRoleId, 'approve'),
                'export' => $registry->checkPermission($mod['id'], $targetRoleId, 'export'),
            ];
        }
        echo json_encode(['success' => true, 'permissions' => $perms]);
        break;
    
    default:
        echo json_encode(['error' => "Unknown action: {$action}"]);
        break;
}
