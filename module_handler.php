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

require_once __DIR__ . '/module_registry.php';

header('Content-Type: application/json');

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

// Validate request
if (empty($action)) {
    echo json_encode(['error' => 'Missing action parameter']);
    exit;
}

$registry = getModuleRegistry();

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
        $module = $registry->getModule($moduleName);
        if (!$module) {
            echo json_encode(['error' => 'Module not found']);
            break;
        }
        if (!$registry->checkPermission($module['id'], $roleId, 'view')) {
            echo json_encode(['error' => 'Access denied', 'code' => 403]);
            break;
        }
        
        // Collect filters from request
        $filters = [];
        $filterKeys = array_keys($_GET);
        $skipKeys = ['action', 'module', 'limit', 'offset'];
        foreach ($filterKeys as $key) {
            if (!in_array($key, $skipKeys) && !empty($_GET[$key])) {
                $filters[$key] = $_GET[$key];
            }
        }
        
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $result = $registry->fetchRecords($moduleName, $filters, $limit, $offset);
        
        // Log access
        $registry->logAccess($module['id'], $staffId, 'view');
        
        echo json_encode(['success' => true] + $result);
        break;
    
    // ─── CREATE RECORD ───
    case 'create':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $module = $registry->getModule($moduleName);
        if (!$module) {
            echo json_encode(['error' => 'Module not found']);
            break;
        }
        if (!$registry->checkPermission($module['id'], $roleId, 'create')) {
            echo json_encode(['error' => 'Access denied', 'code' => 403]);
            break;
        }
        
        // Get data from POST body
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        // Remove empty values and security fields
        $data = array_filter($input, function($v) {
            return $v !== '' && $v !== null;
        });
        
        if (empty($data)) {
            echo json_encode(['error' => 'No data provided']);
            break;
        }
        
        $result = $registry->createRecord($moduleName, $data);
        
        if (isset($result['success']) && $result['success']) {
            $registry->logAccess($module['id'], $staffId, 'create', $result['id'] ?? null, $data);
        }
        
        echo json_encode($result);
        break;
    
    // ─── UPDATE RECORD ───
    case 'update':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $module = $registry->getModule($moduleName);
        if (!$module) {
            echo json_encode(['error' => 'Module not found']);
            break;
        }
        if (!$registry->checkPermission($module['id'], $roleId, 'edit')) {
            echo json_encode(['error' => 'Access denied', 'code' => 403]);
            break;
        }
        
        $id = (int)($_REQUEST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['error' => 'Missing record ID']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $data = array_filter($input, function($v) {
            return $v !== '' && $v !== null;
        });
        
        if (empty($data)) {
            echo json_encode(['error' => 'No data provided']);
            break;
        }
        
        $result = $registry->updateRecord($moduleName, $id, $data);
        
        if (isset($result['success']) && $result['success']) {
            $registry->logAccess($module['id'], $staffId, 'update', $id, $data);
        }
        
        echo json_encode($result);
        break;
    
    // ─── DELETE RECORD ───
    case 'delete':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $module = $registry->getModule($moduleName);
        if (!$module) {
            echo json_encode(['error' => 'Module not found']);
            break;
        }
        if (!$registry->checkPermission($module['id'], $roleId, 'delete')) {
            echo json_encode(['error' => 'Access denied', 'code' => 403]);
            break;
        }
        
        $id = (int)($_REQUEST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['error' => 'Missing record ID']);
            break;
        }
        
        $result = $registry->deleteRecord($moduleName, $id);
        
        if (isset($result['success']) && $result['success']) {
            $registry->logAccess($module['id'], $staffId, 'delete', $id);
        }
        
        echo json_encode($result);
        break;
    
    // ─── SEARCH ───
    case 'search':
        if (empty($moduleName)) {
            echo json_encode(['error' => 'Missing module parameter']);
            break;
        }
        $query = $_REQUEST['q'] ?? '';
        if (strlen($query) < 2) {
            echo json_encode(['error' => 'Query too short (min 2 chars)']);
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
