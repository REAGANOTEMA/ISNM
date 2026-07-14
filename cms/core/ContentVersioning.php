<?php
/**
 * CMS Content Versioning — Revisions and rollback
 */
class ContentVersioning {
    private static function getDb(): ?mysqli {
        $db = function_exists('getWebsiteConnection') ? getWebsiteConnection() : null;
        if (!$db && function_exists('getStudentsConnection')) $db = getStudentsConnection();
        return $db;
    }

    /**
     * Save a content revision
     */
    public static function save(string $contentType, int $contentId, string $title, string $snapshot, ?int $userId = null): int {
        $db = self::getDb();
        if (!$db) return 0;

        // Get next revision number
        $stmt = $db->prepare("SELECT COALESCE(MAX(revision_number), 0) + 1 as next_num FROM cms_revisions WHERE content_type = ? AND content_id = ?");
        if (!$stmt) return 0;
        $stmt->bind_param('si', $contentType, $contentId);
        $stmt->execute();
        $nextNum = $stmt->get_result()->fetch_assoc()['next_num'] ?? 1;
        $stmt->close();

        $userName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'System';

        $stmt = $db->prepare("INSERT INTO cms_revisions (content_type, content_id, revision_number, title, content_snapshot, created_by, created_by_name) VALUES (?,?,?,?,?,?,?)");
        if (!$stmt) return 0;
        $stmt->bind_param('sisissi', $contentType, $contentId, $nextNum, $title, $snapshot, $userId, $userName);
        $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();

        return $insertId;
    }

    /**
     * Get revisions for a content item
     */
    public static function getRevisions(string $contentType, int $contentId, int $limit = 20): array {
        $db = self::getDb();
        if (!$db) return [];

        $stmt = $db->prepare("SELECT * FROM cms_revisions WHERE content_type = ? AND content_id = ? ORDER BY revision_number DESC LIMIT ?");
        if (!$stmt) return [];
        $stmt->bind_param('sii', $contentType, $contentId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $revisions = [];
        while ($row = $result->fetch_assoc()) $revisions[] = $row;
        $stmt->close();
        return $revisions;
    }

    /**
     * Get a specific revision
     */
    public static function getRevision(int $revisionId): ?array {
        $db = self::getDb();
        if (!$db) return null;

        $stmt = $db->prepare("SELECT * FROM cms_revisions WHERE id = ?");
        if (!$stmt) return null;
        $stmt->bind_param('i', $revisionId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    /**
     * Restore a content item to a previous revision
     */
    public static function restore(int $revisionId, ?int $userId = null): bool {
        $revision = self::getRevision($revisionId);
        if (!$revision) return false;

        $db = self::getDb();
        if (!$db) return false;

        $table = match($revision['content_type']) {
            'page' => 'cms_pages',
            'content_block' => 'cms_content_blocks',
            default => null,
        };

        if (!$table) return false;
        $idColumn = $table === 'cms_pages' ? 'id' : 'id';

        $data = json_decode($revision['content_snapshot'], true);
        if (!$data) return false;

        // Save current as new revision before restoring
        self::save($revision['content_type'], $revision['content_id'], 'Before restore to r' . $revision['revision_number'], $revision['content_snapshot'], $userId);

        $fields = [];
        $params = [];
        $types = '';
        foreach ($data as $key => $value) {
            if ($key === 'id') continue;
            $fields[] = "$key = ?";
            $params[] = $value;
            $types .= 's';
        }
        $params[] = (int)$revision['content_id'];
        $types .= 'i';

        $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE $idColumn = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
