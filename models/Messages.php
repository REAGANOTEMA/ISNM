<?php
/**
 * Messages Model for ISNM Student Management System
 */

require_once __DIR__ . '/../config/database.php';

class Messages {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function __destruct() {
        closeConnection($this->conn);
    }
    
    /**
     * Get user messages
     */
    public function getUserMessages($userId, $page = 1) {
        try {
            $offset = ($page - 1) * 20;
            $limit = 20;
            
            $query = "SELECT m.*, u_sender.full_name as sender_name, u_receiver.full_name as receiver_name 
                      FROM messages m
                      JOIN users u_sender ON m.sender_id = u_sender.id
                      JOIN users u_receiver ON m.receiver_id = u_receiver.id
                      WHERE (m.sender_id = ? OR m.receiver_id = ?) AND m.status != 'deleted'
                      ORDER BY m.sent_date DESC LIMIT ? OFFSET ?";
            
            $stmt = executePrepared($this->conn, $query, 'iiii', [$userId, $userId, $limit, $offset]);
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Mark messages as read if user is receiver
            foreach ($messages as $message) {
                if ($message['receiver_id'] == $userId && $message['status'] == 'sent') {
                    $this->markAsRead($message['id'], $userId);
                }
            }
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM messages 
                          WHERE (sender_id = ? OR receiver_id = ?) AND status != 'deleted'";
            $stmt = executePrepared($this->conn, $countQuery, 'ii', [$userId, $userId]);
            $result = $stmt->get_result();
            $total = $result->fetch_assoc()['total'];
            $stmt->close();
            
            $totalPages = ceil($total / $limit);
            
            return [
                'success' => true,
                'messages' => $messages,
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
     * Send message
     */
    public function sendMessage($data) {
        try {
            $query = "INSERT INTO messages (sender_id, receiver_id, subject, message, message_type, 
                      attachment_path, priority, parent_message_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['sender_id'],
                $data['receiver_id'],
                $data['subject'],
                $data['message'],
                $data['message_type'] ?? 'text',
                $data['attachment_path'] ?? null,
                $data['priority'] ?? 'medium',
                $data['parent_message_id'] ?? null
            ];
            
            $stmt = executePrepared($this->conn, $query, 'iissssii', $params);
            $messageId = $stmt->insert_id;
            $stmt->close();
            
            return ['success' => true, 'message_id' => $messageId];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Mark message as read
     */
    public function markAsRead($messageId, $userId) {
        try {
            $query = "UPDATE messages SET status = 'read', read_date = NOW() 
                      WHERE id = ? AND receiver_id = ? AND status = 'sent'";
            
            $stmt = executePrepared($this->conn, $query, 'ii', [$messageId, $userId]);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Delete message
     */
    public function deleteMessage($messageId, $userId) {
        try {
            $query = "UPDATE messages SET status = 'deleted' 
                      WHERE id = ? AND (sender_id = ? OR receiver_id = ?)";
            
            $stmt = executePrepared($this->conn, $query, 'iii', [$messageId, $userId, $userId]);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return ['success' => $affectedRows > 0];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get unread message count
     */
    public function getUnreadCount($userId) {
        try {
            $query = "SELECT COUNT(*) as unread FROM messages 
                      WHERE receiver_id = ? AND status = 'sent'";
            
            $stmt = executePrepared($this->conn, $query, 'i', [$userId]);
            $result = $stmt->get_result();
            $unread = $result->fetch_assoc()['unread'];
            $stmt->close();
            
            return ['success' => true, 'unread_count' => $unread];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get conversation between two users
     */
    public function getConversation($userId1, $userId2, $page = 1) {
        try {
            $offset = ($page - 1) * 50;
            $limit = 50;
            
            $query = "SELECT m.*, u_sender.full_name as sender_name, u_receiver.full_name as receiver_name 
                      FROM messages m
                      JOIN users u_sender ON m.sender_id = u_sender.id
                      JOIN users u_receiver ON m.receiver_id = u_receiver.id
                      WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                      AND m.status != 'deleted'
                      ORDER BY m.sent_date ASC LIMIT ? OFFSET ?";
            
            $stmt = executePrepared($this->conn, $query, 'iiiiii', [$userId1, $userId2, $userId2, $userId1, $limit, $offset]);
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Mark messages as read
            foreach ($messages as $message) {
                if ($message['receiver_id'] == $userId1 && $message['status'] == 'sent') {
                    $this->markAsRead($message['id'], $userId1);
                }
            }
            
            return ['success' => true, 'messages' => $messages];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all users for messaging
     */
    public function getAllUsers($excludeUserId = null) {
        try {
            $query = "SELECT id, full_name, email, role FROM users WHERE status = 'active'";
            $params = [];
            $types = '';
            
            if ($excludeUserId) {
                $query .= " AND id != ?";
                $params[] = $excludeUserId;
                $types .= 'i';
            }
            
            $query .= " ORDER BY full_name";
            
            $stmt = executePrepared($this->conn, $query, $types, $params);
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return ['success' => true, 'users' => $users];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
