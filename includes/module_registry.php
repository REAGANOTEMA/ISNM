<?php
/**
 * ISNM MODULE REGISTRY ENGINE
 * The heart of the modular system — reads from DB, provides API for sidebar, permissions, and CRUD.
 * 
 * Usage:
 *   require_once __DIR__ . '/includes/module_registry.php';
 *   $registry = new ModuleRegistry();
 *   $modules = $registry->getModulesForRole($roleId);
 *   $sidebar = $registry->getSidebarForRole($roleId);
 *   $perm = $registry->checkPermission($moduleId, $roleId, 'create');
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('getStaffConnection')) {
    require_once dirname(__DIR__) . '/config/database.php';
}

class ModuleRegistry {
    private $conn;
    private static $cache = [];
    
    public function __construct() {
        $this->conn = getStaffConnection();
    }
    
    /**
     * Get all modules for a given role, grouped by department
     */
    public function getModulesForRole(int $roleId, bool $studentPortal = false): array {
        $cacheKey = "modules_{$roleId}_{$studentPortal}";
        if (isset(self::$cache[$cacheKey])) return self::$cache[$cacheKey];
        
        if (!$this->conn) {
            self::$cache[$cacheKey] = [];
            return [];
        }
        
        // Check that required tables exist before querying
        $requiredTables = ['system_modules', 'module_departments', 'module_permissions'];
        foreach ($requiredTables as $t) {
            $check = @$this->conn->query("SHOW TABLES LIKE '{$t}'");
            if (!$check || $check->num_rows === 0) {
                self::$cache[$cacheKey] = [];
                return [];
            }
        }
        
        $sql = "SELECT 
                    m.id, m.name, m.label, m.icon, m.route, m.handler_url,
                    m.tables_json, m.description, m.sort_order,
                    m.is_student_module, m.is_document_module,
                    d.name as dept_name, d.label as dept_label, d.icon as dept_icon, d.color as dept_color,
                    p.can_view, p.can_create, p.can_edit, p.can_delete, p.can_approve, p.can_export
                FROM system_modules m
                INNER JOIN module_departments d ON m.department_id = d.id
                INNER JOIN module_permissions p ON m.id = p.module_id
                WHERE p.role_id = {$roleId}
                  AND m.is_active = 1
                  AND d.is_active = 1
                  AND p.can_view = 1";
        
        if ($studentPortal) {
            $sql .= " AND m.is_student_module = 1";
        } else {
            $sql .= " AND m.is_student_module = 0";
        }
        
        $sql .= " ORDER BY d.sort_order, m.sort_order";
        
        $result = @$this->conn->query($sql);
        if (!$result) {
            self::$cache[$cacheKey] = [];
            return [];
        }
        $modules = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['tables'] = json_decode($row['tables_json'], true);
            unset($row['tables_json']);
            $modules[] = $row;
        }
        
        self::$cache[$cacheKey] = $modules;
        return $modules;
    }
    
    /**
     * Get modules grouped by department for sidebar rendering
     */
    public function getSidebarForRole(int $roleId): array {
        $modules = $this->getModulesForRole($roleId);
        $grouped = [];
        
        foreach ($modules as $mod) {
            $deptKey = $mod['dept_name'];
            if (!isset($grouped[$deptKey])) {
                $grouped[$deptKey] = [
                    'label' => $mod['dept_label'],
                    'icon'  => $mod['dept_icon'],
                    'color' => $mod['dept_color'],
                    'modules' => []
                ];
            }
            $grouped[$deptKey]['modules'][] = $mod;
        }
        
        return $grouped;
    }
    
    /**
     * Get a single module by name
     */
    public function getModule(string $name): ?array {
        if (!$this->conn) return null;
        $sql = "SELECT m.*, d.name as dept_name, d.label as dept_label 
                FROM system_modules m 
                INNER JOIN module_departments d ON m.department_id = d.id 
                WHERE m.name = '{$this->conn->real_escape_string($name)}' AND m.is_active = 1";
        $result = @$this->conn->query($sql);
        if (!$result) return null;
        if ($row = $result->fetch_assoc()) {
            $row['tables'] = json_decode($row['tables_json'], true);
            unset($row['tables_json']);
            return $row;
        }
        return null;
    }
    
    /**
     * Check if a role has a specific permission on a module
     */
    public function checkPermission(int $moduleId, int $roleId, string $action = 'view'): bool {
        $actionCol = 'can_' . $action;
        $validActions = ['view', 'create', 'edit', 'delete', 'approve', 'export'];
        if (!in_array($action, $validActions)) return false;
        if (!$this->conn) return false;
        
        $sql = "SELECT {$actionCol} FROM module_permissions WHERE module_id = {$moduleId} AND role_id = {$roleId}";
        $result = @$this->conn->query($sql);
        if (!$result) return false;
        if ($row = $result->fetch_assoc()) {
            return (bool)$row[$actionCol];
        }
        return false;
    }
    
    /**
     * Check permission by module name
     */
    public function checkPermissionByName(string $moduleName, int $roleId, string $action = 'view'): bool {
        $module = $this->getModule($moduleName);
        if (!$module) return false;
        return $this->checkPermission($module['id'], $roleId, $action);
    }
    
    /**
     * Get all tables for a module (for CRUD operations)
     */
    public function getModuleTables(string $moduleName): array {
        $module = $this->getModule($moduleName);
        return $module ? $module['tables'] : [];
    }
    
    /**
     * Get the primary table for a module (first table in the list)
     */
    public function getPrimaryTable(string $moduleName): ?string {
        $tables = $this->getModuleTables($moduleName);
        return !empty($tables) ? $tables[0] : null;
    }
    
    /**
     * Fetch records from a module's primary table with optional filters
     */
    public function fetchRecords(string $moduleName, array $filters = [], int $limit = 50, int $offset = 0): array {
        $tables = $this->getModuleTables($moduleName);
        if (empty($tables)) return ['error' => 'Module not found'];
        if (!$this->conn) return ['error' => 'No database connection', 'data' => []];
        
        $primaryTable = $tables[0];
        
        // Build WHERE clause
        $where = [];
        foreach ($filters as $col => $val) {
            $escapedVal = $this->conn->real_escape_string($val);
            $where[] = "`{$col}` = '{$escapedVal}'";
        }
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Check if table exists
        $tableCheck = @$this->conn->query("SHOW TABLES LIKE '{$primaryTable}'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return ['error' => "Table {$primaryTable} not found", 'data' => []];
        }
        
        // Get columns
        $cols = @$this->conn->query("SHOW COLUMNS FROM `{$primaryTable}`");
        $columns = [];
        if ($cols) {
            while ($col = $cols->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
        }
        
        // Fetch data
        $sql = "SELECT * FROM `{$primaryTable}` {$whereClause} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
        $result = @$this->conn->query($sql);
        if (!$result) return ['error' => $this->conn->error, 'data' => []];
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM `{$primaryTable}` {$whereClause}";
        $countResult = @$this->conn->query($countSql);
        $total = ($countResult && $countResult->fetch_assoc()) ? $countResult->fetch_assoc()['total'] ?? 0 : 0;
        
        return [
            'data' => $data,
            'columns' => $columns,
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'table' => $primaryTable
        ];
    }
    
    /**
     * Create a record in a module's primary table
     */
    public function createRecord(string $moduleName, array $data): array {
        $primaryTable = $this->getPrimaryTable($moduleName);
        if (!$primaryTable) return ['error' => 'Module not found'];
        
        $columns = array_keys($data);
        $values = array_map(function($v) {
            return $this->conn->real_escape_string($v);
        }, array_values($data));
        
        $colStr = '`' . implode('`, `', $columns) . '`';
        $valStr = "'" . implode("', '", $values) . "'";
        
        $sql = "INSERT INTO `{$primaryTable}` ({$colStr}) VALUES ({$valStr})";
        
        if ($this->conn->query($sql)) {
            $insertId = $this->conn->insert_id;
            return ['success' => true, 'id' => $insertId, 'message' => 'Record created'];
        }
        return ['error' => $this->conn->error];
    }
    
    /**
     * Update a record in a module's primary table
     */
    public function updateRecord(string $moduleName, int $id, array $data): array {
        $primaryTable = $this->getPrimaryTable($moduleName);
        if (!$primaryTable) return ['error' => 'Module not found'];
        
        $sets = [];
        foreach ($data as $col => $val) {
            $escapedVal = $this->conn->real_escape_string($val);
            $sets[] = "`{$col}` = '{$escapedVal}'";
        }
        $setStr = implode(', ', $sets);
        
        $sql = "UPDATE `{$primaryTable}` SET {$setStr} WHERE id = {$id}";
        
        if ($this->conn->query($sql)) {
            return ['success' => true, 'affected_rows' => $this->conn->affected_rows];
        }
        return ['error' => $this->conn->error];
    }
    
    /**
     * Delete a record from a module's primary table
     */
    public function deleteRecord(string $moduleName, int $id): array {
        $primaryTable = $this->getPrimaryTable($moduleName);
        if (!$primaryTable) return ['error' => 'Module not found'];
        
        $sql = "DELETE FROM `{$primaryTable}` WHERE id = {$id}";
        
        if ($this->conn->query($sql)) {
            return ['success' => true, 'affected_rows' => $this->conn->affected_rows];
        }
        return ['error' => $this->conn->error];
    }
    
    /**
     * Search across a module's tables
     */
    public function searchModule(string $moduleName, string $query, int $limit = 20): array {
        $tables = $this->getModuleTables($moduleName);
        if (empty($tables) || !$this->conn) return [];
        
        $results = [];
        $escapedQuery = $this->conn->real_escape_string($query);
        
        foreach ($tables as $table) {
            $tableCheck = @$this->conn->query("SHOW TABLES LIKE '{$table}'");
            if (!$tableCheck || $tableCheck->num_rows === 0) continue;
            
            $cols = @$this->conn->query("SHOW COLUMNS FROM `{$table}`");
            if (!$cols) continue;
            $stringCols = [];
            while ($col = $cols->fetch_assoc()) {
                if (in_array($col['Type'], ['text', 'varchar', 'longtext', 'mediumtext'])) {
                    $stringCols[] = $col['Field'];
                }
            }
            
            if (empty($stringCols)) continue;
            
            $likeClauses = array_map(function($c) use ($escapedQuery) {
                return "`{$c}` LIKE '%{$escapedQuery}%'";
            }, $stringCols);
            
            $sql = "SELECT *, '{$table}' as _source_table FROM `{$table}` WHERE " . implode(' OR ', $likeClauses) . " LIMIT {$limit}";
            $result = @$this->conn->query($sql);
            if (!$result) continue;
            
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * Get all modules (for admin)
     */
    public function getAllModules(): array {
        if (!$this->conn) return [];
        $sql = "SELECT m.*, d.name as dept_name, d.label as dept_label 
                FROM system_modules m 
                INNER JOIN module_departments d ON m.department_id = d.id 
                ORDER BY d.sort_order, m.sort_order";
        $result = @$this->conn->query($sql);
        if (!$result) return [];
        $modules = [];
        while ($row = $result->fetch_assoc()) {
            $row['tables'] = json_decode($row['tables_json'], true);
            unset($row['tables_json']);
            $modules[] = $row;
        }
        return $modules;
    }
    
    /**
     * Get all departments
     */
    public function getDepartments(): array {
        if (!$this->conn) return [];
        $result = @$this->conn->query("SELECT * FROM module_departments WHERE is_active = 1 ORDER BY sort_order");
        if (!$result) return [];
        $depts = [];
        while ($row = $result->fetch_assoc()) {
            $depts[] = $row;
        }
        return $depts;
    }
    
    /**
     * Log module access
     */
    public function logAccess(int $moduleId, int $staffId, string $action, ?int $recordId = null, ?array $details = null): void {
        if (!$this->conn) return;
        $detailsJson = $details ? json_encode($details) : 'NULL';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $sql = "INSERT INTO module_audit_log (module_id, staff_id, action, record_id, details, ip_address) 
                VALUES ({$moduleId}, {$staffId}, '{$action}', " . ($recordId ?: 'NULL') . ", {$detailsJson}, '{$ip}')";
        @$this->conn->query($sql);
    }
}

// Singleton instance
function getModuleRegistry(): ModuleRegistry {
    static $instance = null;
    if ($instance === null) {
        $instance = new ModuleRegistry();
    }
    return $instance;
}
