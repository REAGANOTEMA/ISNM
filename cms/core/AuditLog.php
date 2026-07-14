<?php
/**
 * CMS Audit Log — Tracks all content changes
 */
class AuditLog {
    private static $instance = null;
    private $db;

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
    }

    /**
     * Log an action
     */
    public function log(string $action, string $contentType, int $contentId = 0, string $contentTitle = '', $oldValues = null, $newValues = null): bool {
        if (!$this->db) return false;

        $stmt = $this->db->prepare("INSERT INTO cms_audit_log (user_id, user_name, user_role, action, content_type, content_id, content_title, old_values, new_values, ip_address, user_agent, session_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        if (!$stmt) return false;

        $userId = $_SESSION['user_id'] ?? 0;
        $userName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'System';
        $userRole = $_SESSION['role'] ?? 'system';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessionId = session_id() ?: '';

        $oldJson = is_array($oldValues) ? json_encode($oldValues) : ($oldValues ?? null);
        $newJson = is_array($newValues) ? json_encode($newValues) : ($newValues ?? null);
        $title = mb_substr($contentTitle, 0, 255);

        $stmt->bind_param('issssissssss', $userId, $userName, $userRole, $action, $contentType, $contentId, $title, $oldJson, $newJson, $ip, $ua, $sessionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get audit log entries
     */
    public function getEntries(array $filters = [], int $limit = 50, int $offset = 0): array {
        if (!$this->db) return [];

        $where = ['1=1'];
        $params = [];
        $types = '';

        if (!empty($filters['content_type'])) {
            $where[] = 'content_type = ?';
            $params[] = $filters['content_type'];
            $types .= 's';
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = (int)$filters['user_id'];
            $types .= 'i';
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
            $types .= 's';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }

        $sql = "SELECT * FROM cms_audit_log WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $entries = [];
        while ($row = $result->fetch_assoc()) $entries[] = $row;
        $stmt->close();
        return $entries;
    }

    /**
     * Get total count for pagination
     */
    public function getCount(array $filters = []): int {
        if (!$this->db) return 0;

        $where = ['1=1'];
        $params = [];
        $types = '';

        if (!empty($filters['content_type'])) {
            $where[] = 'content_type = ?';
            $params[] = $filters['content_type'];
            $types .= 's';
        }

        $sql = "SELECT COUNT(*) as total FROM cms_audit_log WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($result['total'] ?? 0);
    }
}
