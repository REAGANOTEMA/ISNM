<?php
/**
 * Announcements Model for ISNM Student Management System
 */

require_once __DIR__ . '/../config/database.php';

class Announcements {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function __destruct() {
        closeConnection($this->conn);
    }
    
    /**
     * Get announcements for user
     */
    public function getAnnouncements($userRole = 'all', $page = 1) {
        try {
            $offset = ($page - 1) * 20;
            $limit = 20;
            
            $query = "SELECT a.*, u.full_name as posted_by_name 
                      FROM announcements a
                      JOIN users u ON a.posted_by = u.id
                      WHERE a.status = 'published' 
                      AND (a.target_audience = 'all' OR a.target_audience = ?)
                      AND (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())
                      ORDER BY a.priority DESC, a.posted_date DESC LIMIT ? OFFSET ?";
            
            $stmt = executePrepared($this->conn, $query, 'sii', [$userRole, $limit, $offset]);
            $result = $stmt->get_result();
            $announcements = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Update view count
            foreach ($announcements as $announcement) {
                $this->incrementViewCount($announcement['id']);
            }
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM announcements a
                          WHERE a.status = 'published' 
                          AND (a.target_audience = 'all' OR a.target_audience = ?)
                          AND (a.expiry_date IS NULL OR a.expiry_date >= CURDATE())";
            
            $stmt = executePrepared($this->conn, $countQuery, 's', [$userRole]);
            $result = $stmt->get_result();
            $total = $result->fetch_assoc()['total'];
            $stmt->close();
            
            $totalPages = ceil($total / $limit);
            
            return [
                'success' => true,
                'announcements' => $announcements,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Create announcement
     */
    public function createAnnouncement($data) {
        try {
            $query = "INSERT INTO announcements (title, content, announcement_type, target_audience, 
                      priority, posted_by, expiry_date, attachment_path, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['title'],
                $data['content'],
                $data['announcement_type'],
                $data['target_audience'],
                $data['priority'],
                $data['posted_by'],
                $data['expiry_date'] ?? null,
                $data['attachment_path'] ?? null,
                $data['status'] ?? 'draft'
            ];
            
            $stmt = executePrepared($this->conn, $query, 'sssssisss', $params);
            $announcementId = $stmt->insert_id;
            $stmt->close();
            
            return ['success' => true, 'announcement_id' => $announcementId];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update announcement
     */
    public function updateAnnouncement($id, $data) {
        try {
            $query = "UPDATE announcements SET title = ?, content = ?, announcement_type = ?, 
                      target_audience = ?, priority = ?, expiry_date = ?, attachment_path = ?, status = ? 
                      WHERE id = ?";
            
            $params = [
                $data['title'],
                $data['content'],
                $data['announcement_type'],
                $data['target_audience'],
                $data['priority'],
                $data['expiry_date'] ?? null,
                $data['attachment_path'] ?? null,
                $data['status'],
                $id
            ];
            
            $stmt = executePrepared($this->conn, $query, 'ssssssssi', $params);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0, 'affected_rows' => $affectedRows];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Delete announcement
     */
    public function deleteAnnouncement($id) {
        try {
            $query = "DELETE FROM announcements WHERE id = ?";
            $stmt = executePrepared($this->conn, $query, 'i', [$id]);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get announcement by ID
     */
    public function getAnnouncementById($id) {
        try {
            $query = "SELECT a.*, u.full_name as posted_by_name 
                      FROM announcements a
                      JOIN users u ON a.posted_by = u.id
                      WHERE a.id = ?";
            
            $stmt = executePrepared($this->conn, $query, 'i', [$id]);
            $result = $stmt->get_result();
            $announcement = $result->fetch_assoc();
            $stmt->close();
            
            return ['success' => true, 'announcement' => $announcement];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all announcements (for staff)
     */
    public function getAllAnnouncements($page = 1, $status = 'published') {
        try {
            $offset = ($page - 1) * 20;
            $limit = 20;
            
            $query = "SELECT a.*, u.full_name as posted_by_name 
                      FROM announcements a
                      JOIN users u ON a.posted_by = u.id
                      WHERE a.status = ?
                      ORDER BY a.posted_date DESC LIMIT ? OFFSET ?";
            
            $stmt = executePrepared($this->conn, $query, 'sii', [$status, $limit, $offset]);
            $result = $stmt->get_result();
            $announcements = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM announcements WHERE status = ?";
            $stmt = executePrepared($this->conn, $countQuery, 's', [$status]);
            $result = $stmt->get_result();
            $total = $result->fetch_assoc()['total'];
            $stmt->close();
            
            $totalPages = ceil($total / $limit);
            
            return [
                'success' => true,
                'announcements' => $announcements,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Increment view count
     */
    private function incrementViewCount($id) {
        try {
            $query = "UPDATE announcements SET view_count = view_count + 1 WHERE id = ?";
            executePrepared($this->conn, $query, 'i', [$id]);
        } catch (Exception $e) {
            // Ignore errors for view count
        }
    }
    
    /**
     * Get announcement statistics
     */
    public function getStatistics() {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                        SUM(view_count) as total_views
                      FROM announcements";
            
            $stmt = executePrepared($this->conn, $query, '', []);
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
            
            return ['success' => true, 'statistics' => $stats];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
