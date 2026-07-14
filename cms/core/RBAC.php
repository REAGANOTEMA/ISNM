<?php
/**
 * CMS RBAC — Role-Based Access Control
 * Manages permissions for content editing
 */
class RBAC {
    private static $instance = null;
    private $db;
    private $permissions = [];

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = function_exists('getWebsiteConnection') ? getWebsiteConnection() : null;
        if (!$this->db && function_exists('getStudentsConnection')) {
            $this->db = getStudentsConnection();
        }
        $this->loadPermissions();
    }

    private function loadPermissions(): void {
        if (!$this->db) return;
        $result = $this->db->query("SELECT * FROM cms_role_permissions");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->permissions[$row['role_name']][] = $row;
            }
        }
    }

    /**
     * Check if a role has a specific permission
     */
    public function can(string $role, string $permission, string $pageSlug = null, string $contentType = null): bool {
        // Director General has all permissions
        if ($role === 'Director General' || $role === 'CEO') return true;

        if (empty($this->permissions[$role])) return false;

        foreach ($this->permissions[$role] as $perm) {
            if ($perm['permission'] === 'manage_all') return true;
            if ($perm['permission'] === $permission) {
                if ($pageSlug && isset($perm['page_slug']) && $perm['page_slug'] !== null && $perm['page_slug'] !== $pageSlug) {
                    continue;
                }
                if ($contentType && isset($perm['content_type']) && $perm['content_type'] !== null && $perm['content_type'] !== $contentType) {
                    continue;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Get all permissions for a role
     */
    public function getRolePermissions(string $role): array {
        return $this->permissions[$role] ?? [];
    }

    /**
     * Get pages a role can edit
     */
    public function getEditablePages(string $role): array {
        if ($role === 'Director General' || $role === 'CEO') {
            return ['*']; // All pages
        }
        $pages = [];
        if (!empty($this->permissions[$role])) {
            foreach ($this->permissions[$role] as $perm) {
                if (!empty($perm['page_slug'])) {
                    $pages[] = $perm['page_slug'];
                }
            }
        }
        return array_unique($pages);
    }

    /**
     * Get content types a role can manage
     */
    public function getManageableContentTypes(string $role): array {
        if ($role === 'Director General' || $role === 'CEO') {
            return ['*'];
        }
        $types = [];
        if (!empty($this->permissions[$role])) {
            foreach ($this->permissions[$role] as $perm) {
                if (!empty($perm['content_type'])) {
                    $types[] = $perm['content_type'];
                }
            }
        }
        return array_unique($types);
    }

    /**
     * Check if role requires approval before publishing
     */
    public function requiresApproval(string $role, string $permission): bool {
        if ($role === 'Director General') return false;
        if (empty($this->permissions[$role])) return true;
        foreach ($this->permissions[$role] as $perm) {
            if ($perm['permission'] === $permission) {
                return !empty($perm['requires_approval']);
            }
        }
        return true;
    }

    /**
     * Add or update a permission
     */
    public function setPermission(string $role, string $permission, array $data = []): bool {
        if (!$this->db) return false;
        $pageSlug = $data['page_slug'] ?? null;
        $contentType = $data['content_type'] ?? null;

        $stmt = $this->db->prepare("INSERT INTO cms_role_permissions (role_name, permission, page_slug, content_type, can_create, can_edit, can_delete, can_publish, can_approve, requires_approval) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete), can_publish=VALUES(can_publish), can_approve=VALUES(can_approve), requires_approval=VALUES(requires_approval)");
        if (!$stmt) return false;

        $canCreate = (int)($data['can_create'] ?? 0);
        $canEdit = (int)($data['can_edit'] ?? 0);
        $canDelete = (int)($data['can_delete'] ?? 0);
        $canPublish = (int)($data['can_publish'] ?? 0);
        $canApprove = (int)($data['can_approve'] ?? 0);
        $requiresApproval = (int)($data['requires_approval'] ?? 1);

        $stmt->bind_param('sssiiiiiii', $role, $permission, $pageSlug, $contentType, $canCreate, $canEdit, $canDelete, $canPublish, $canApprove, $requiresApproval);
        $result = $stmt->execute();
        $stmt->close();

        // Reload permissions cache
        $this->permissions = [];
        $this->loadPermissions();
        return $result;
    }

    /**
     * Remove a permission
     */
    public function removePermission(string $role, string $permission): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM cms_role_permissions WHERE role_name = ? AND permission = ?");
        if (!$stmt) return false;
        $stmt->bind_param('ss', $role, $permission);
        $result = $stmt->execute();
        $stmt->close();
        $this->permissions = [];
        $this->loadPermissions();
        return $result;
    }
}
