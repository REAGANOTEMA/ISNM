<?php
/**
 * ISNM News Publishing System
 * Directors publish news that automatically displays on website
 */

require_once __DIR__ . '/../config/database.php';

class NewsPublisher {
    private $conn;
    
    public function __construct() {
        $this->conn = getWebsiteConnection();
        if (!$this->conn) {
            throw new Exception("Cannot connect to website database");
        }
    }
    
    /**
     * Publish news (from director dashboard)
     */
    public function publishNews($title, $content, $category, $author, $image_url = null, $featured = false) {
        $stmt = $this->conn->prepare(
            "INSERT INTO website_announcements (title, content, category, author, image_url, featured, status, views, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, 'published', 0, NOW(), NOW())"
        );
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error'];
        }
        
        $featured_int = $featured ? 1 : 0;
        $stmt->bind_param('sssssi', $title, $content, $category, $author, $image_url, $featured_int);
        
        if ($stmt->execute()) {
            $newsId = $this->conn->insert_id;
            $stmt->close();
            
            // Broadcast notification to website
            $this->broadcastToWebsite($newsId, $title);
            
            return ['success' => true, 'message' => 'News published successfully', 'news_id' => $newsId];
        }
        
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to publish news'];
    }
    
    /**
     * Update existing news
     */
    public function updateNews($newsId, $title, $content, $category, $featured = false) {
        $stmt = $this->conn->prepare(
            "UPDATE website_announcements SET title = ?, content = ?, category = ?, featured = ?, updated_at = NOW() WHERE id = ?"
        );
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error'];
        }
        
        $featured_int = $featured ? 1 : 0;
        $stmt->bind_param('sssii', $title, $content, $category, $featured_int, $newsId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'News updated successfully'];
        }
        
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update news'];
    }
    
    /**
     * Delete news
     */
    public function deleteNews($newsId) {
        $stmt = $this->conn->prepare("DELETE FROM website_announcements WHERE id = ?");
        
        if ($stmt) {
            $stmt->bind_param('i', $newsId);
            $success = $stmt->execute();
            $stmt->close();
            return ['success' => $success, 'message' => $success ? 'News deleted' : 'Failed to delete'];
        }
        
        return ['success' => false, 'message' => 'Database error'];
    }
    
    /**
     * Get all published news (for website)
     */
    public function getPublishedNews($limit = 10, $offset = 0, $category = null) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        if ($category) {
            $stmt = $this->conn->prepare("SELECT * FROM website_announcements WHERE status = 'published' AND category = ? ORDER BY featured DESC, created_at DESC LIMIT ? OFFSET ?");
            if (!$stmt) return [];
            $stmt->bind_param('sii', $category, $limit, $offset);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM website_announcements WHERE status = 'published' ORDER BY featured DESC, created_at DESC LIMIT ? OFFSET ?");
            if (!$stmt) return [];
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if (!$result) return [];
        
        $news = [];
        while ($row = $result->fetch_assoc()) {
            $news[] = $row;
        }
        
        return $news;
    }
    
    /**
     * Get featured news
     */
    public function getFeaturedNews($limit = 5) {
        $result = $this->conn->query(
            "SELECT * FROM website_announcements WHERE status = 'published' AND featured = 1 
             ORDER BY created_at DESC LIMIT $limit"
        );
        
        if (!$result) return [];
        
        $news = [];
        while ($row = $result->fetch_assoc()) {
            $news[] = $row;
        }
        
        return $news;
    }
    
    /**
     * Get single news item
     */
    public function getNews($newsId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM website_announcements WHERE id = ? AND status = 'published'"
        );
        
        if (!$stmt) return null;
        
        $stmt->bind_param('i', $newsId);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $news = $result->fetch_assoc();
        $stmt->close();
        
        // Increment view count
        if ($news) {
            $this->incrementViews($newsId);
        }
        
        return $news;
    }
    
    /**
     * Increment view count
     */
    private function incrementViews($newsId) {
        $newsId = (int)$newsId;
        $this->conn->query("UPDATE website_announcements SET views = views + 1 WHERE id = $newsId");
    }
    
    /**
     * Search news
     */
    public function searchNews($query, $limit = 10) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM website_announcements 
             WHERE status = 'published' AND (title LIKE ? OR content LIKE ?)
             ORDER BY created_at DESC LIMIT ?"
        );
        if (!$stmt) return [];
        $like = '%' . $query . '%';
        $stmt->bind_param('ssi', $like, $like, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if (!$result) return [];
        
        $news = [];
        while ($row = $result->fetch_assoc()) {
            $news[] = $row;
        }
        
        return $news;
    }
    
    /**
     * Broadcast news to website (update cache/index)
     */
    private function broadcastToWebsite($newsId, $title) {
        // Update website index or trigger cache refresh
        // This could involve updating a JSON feed, triggering webhooks, etc.
        
        // Example: Log the news update
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'news_id' => $newsId,
            'title' => $title,
            'action' => 'published',
        ];
        
        error_log("News published: " . json_encode($logEntry));
    }
    
    /**
     * Get news categories
     */
    public function getCategories() {
        $result = $this->conn->query(
            "SELECT DISTINCT category FROM website_announcements WHERE status = 'published' ORDER BY category"
        );
        
        if (!$result) return [];
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    }
}

// API endpoint for publishing news (director only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'publish_news') {
    header('Content-Type: application/json');
    
    // Check authorization (must be director, CEO, principal, or secretary)
    $allowedRoles = ['director', 'ceo', 'principal', 'secretary', 'ict',
                     'Director General', 'Chief Executive Officer', 'Director Academics',
                     'Director Finance', 'Director ICT', 'School Principal',
                     'Deputy Principal', 'Academic Registrar', 'HR Manager', 'School Bursar'];
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $title = $_POST['title'] ?? null;
    $content = $_POST['content'] ?? null;
    $category = $_POST['category'] ?? 'General';
    $featured = isset($_POST['featured']) ? true : false;
    $image_url = $_POST['image_url'] ?? null;
    $author = $_SESSION['full_name'] ?? 'Administrator';
    
    if (!$title || !$content) {
        echo json_encode(['success' => false, 'message' => 'Title and content required']);
        exit;
    }
    
    try {
        $publisher = new NewsPublisher();
        $result = $publisher->publishNews($title, $content, $category, $author, $image_url, $featured);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API endpoint for getting news (public)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_news') {
    header('Content-Type: application/json');
    
    $limit = intval($_GET['limit'] ?? 10);
    $offset = intval($_GET['offset'] ?? 0);
    $category = $_GET['category'] ?? null;
    
    try {
        $publisher = new NewsPublisher();
        $news = $publisher->getPublishedNews($limit, $offset, $category);
        echo json_encode(['success' => true, 'news' => $news]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API endpoint for getting featured news
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_featured_news') {
    header('Content-Type: application/json');
    
    $limit = intval($_GET['limit'] ?? 5);
    
    try {
        $publisher = new NewsPublisher();
        $news = $publisher->getFeaturedNews($limit);
        echo json_encode(['success' => true, 'news' => $news]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API endpoint for searching news
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'search_news') {
    header('Content-Type: application/json');
    
    $query = $_GET['q'] ?? null;
    
    if (!$query) {
        echo json_encode(['success' => false, 'message' => 'Search query required']);
        exit;
    }
    
    try {
        $publisher = new NewsPublisher();
        $news = $publisher->searchNews($query);
        echo json_encode(['success' => true, 'news' => $news]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

?>
