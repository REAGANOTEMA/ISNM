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
     * Get valid column names for a table (cached).
     */
    private function getTableColumns(string $table): array {
        $cacheKey = "cols_{$table}";
        if (isset(self::$cache[$cacheKey])) return self::$cache[$cacheKey];
        $columns = [];
        $cols = @$this->conn->query("SHOW COLUMNS FROM `{$table}`");
        if ($cols) {
            while ($col = $cols->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
        }
        self::$cache[$cacheKey] = $columns;
        return $columns;
    }

    /**
     * Validate that a column name exists in the table (prevents SQL injection via column names).
     */
    private function isValidColumn(string $col, array $validColumns): bool {
        return in_array($col, $validColumns, true);
    }

    /**
     * Fetch records from a module's primary table with optional filters
     */
    public function fetchRecords(string $moduleName, array $filters = [], int $limit = 50, int $offset = 0): array {
        $tables = $this->getModuleTables($moduleName);
        if (empty($tables)) return ['error' => 'Module not found'];
        if (!$this->conn) return ['error' => 'No database connection', 'data' => []];
        
        $primaryTable = $tables[0];
        
        // Check if table exists
        $tableCheck = @$this->conn->query("SHOW TABLES LIKE '{$primaryTable}'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return ['error' => "Table {$primaryTable} not found", 'data' => []];
        }
        
        // Get valid columns for this table
        $validColumns = $this->getTableColumns($primaryTable);
        
        // Build WHERE clause with validated column names
        $where = [];
        foreach ($filters as $col => $val) {
            if (!$this->isValidColumn($col, $validColumns)) continue;
            $escapedVal = $this->conn->real_escape_string($val);
            $where[] = "`{$col}` = '{$escapedVal}'";
        }
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $limit = max(1, min((int)$limit, 500));
        $offset = max(0, (int)$offset);
        
        // Fetch data
        $sql = "SELECT * FROM `{$primaryTable}` {$whereClause} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
        $result = @$this->conn->query($sql);
        if (!$result) return ['error' => $this->conn->error, 'data' => []];
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        // Get total count (fixed: was calling fetch_assoc twice)
        $countSql = "SELECT COUNT(*) as total FROM `{$primaryTable}` {$whereClause}";
        $countResult = @$this->conn->query($countSql);
        $total = 0;
        if ($countResult) {
            $countRow = $countResult->fetch_assoc();
            $total = $countRow ? (int)$countRow['total'] : 0;
        }
        
        return [
            'data' => $data,
            'columns' => $validColumns,
            'total' => $total,
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
        if (!$this->conn) return ['error' => 'No database connection'];
        
        $validColumns = $this->getTableColumns($primaryTable);
        $filtered = [];
        foreach ($data as $col => $val) {
            if ($this->isValidColumn($col, $validColumns)) {
                $filtered[$col] = $val;
            }
        }
        if (empty($filtered)) return ['error' => 'No valid columns provided'];
        
        $columns = array_keys($filtered);
        $placeholders = [];
        $values = [];
        $types = '';
        foreach ($filtered as $col => $val) {
            $placeholders[] = '?';
            $values[] = $val;
            $types .= is_int($val) ? 'i' : 's';
        }
        
        $colStr = '`' . implode('`, `', $columns) . '`';
        $sql = "INSERT INTO `{$primaryTable}` ({$colStr}) VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['error' => $this->conn->error];
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $insertId, 'message' => 'Record created'];
        }
        $error = $stmt->error;
        $stmt->close();
        return ['error' => $error];
    }
    
    /**
     * Update a record in a module's primary table
     */
    public function updateRecord(string $moduleName, int $id, array $data): array {
        $primaryTable = $this->getPrimaryTable($moduleName);
        if (!$primaryTable) return ['error' => 'Module not found'];
        if (!$this->conn) return ['error' => 'No database connection'];
        
        $validColumns = $this->getTableColumns($primaryTable);
        $sets = [];
        $values = [];
        $types = '';
        foreach ($data as $col => $val) {
            if (!$this->isValidColumn($col, $validColumns)) continue;
            $sets[] = "`{$col}` = ?";
            $values[] = $val;
            $types .= is_int($val) ? 'i' : 's';
        }
        if (empty($sets)) return ['error' => 'No valid columns provided'];
        
        $values[] = $id;
        $types .= 'i';
        $setStr = implode(', ', $sets);
        
        $sql = "UPDATE `{$primaryTable}` SET {$setStr} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['error' => $this->conn->error];
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return ['success' => true, 'affected_rows' => $affected];
        }
        $error = $stmt->error;
        $stmt->close();
        return ['error' => $error];
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
        $limit = max(1, min((int)$limit, 100));
        
        foreach ($tables as $table) {
            $tableCheck = @$this->conn->query("SHOW TABLES LIKE '{$table}'");
            if (!$tableCheck || $tableCheck->num_rows === 0) continue;
            
            $validColumns = $this->getTableColumns($table);
            $stringCols = [];
            foreach ($validColumns as $colName) {
                $colInfo = @$this->conn->query("SHOW COLUMNS FROM `{$table}` WHERE Field = '" . $this->conn->real_escape_string($colName) . "'");
                if ($colInfo && $row = $colInfo->fetch_assoc()) {
                    if (in_array($row['Type'], ['text', 'varchar', 'longtext', 'mediumtext'])) {
                        $stringCols[] = $colName;
                    }
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
        $validActions = ['view', 'create', 'update', 'delete', 'login', 'export'];
        $action = in_array($action, $validActions, true) ? $action : 'view';
        $detailsJson = $details ? json_encode($details) : 'NULL';
        $ip = $this->conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
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
