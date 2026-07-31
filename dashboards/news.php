<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/notification_helper.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
$ctx = bootstrapStaffDashboard(['director','secretary','ict','principal','director general','ceo','hr','academic registrar','bursar','librarian','head nursing','head midwifery']);
$pageTitle = 'News Management';

$auth     = $ctx['auth'];
$staff    = $ctx['staff'];
$website  = $ctx['website'];
$user     = $ctx['user'];
$userId   = (int)($user['id'] ?? $_SESSION['user_id'] ?? 0);
$userName = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Staff';
$role     = $_SESSION['role'] ?? '';

$flash = ['type' => '', 'msg' => ''];
if (!empty($_SESSION['news_flash'])) { $flash = $_SESSION['news_flash']; unset($_SESSION['news_flash']); }

function ensureNewsTables($staffConn, $websiteConn) {
    $schema = "CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(300) NOT NULL,
        slug VARCHAR(300) NOT NULL,
        summary TEXT,
        content LONGTEXT,
        featured_image VARCHAR(500),
        category VARCHAR(100) DEFAULT 'General',
        tags VARCHAR(500),
        status ENUM('draft','published','scheduled','archived') DEFAULT 'draft',
        is_featured TINYINT(1) DEFAULT 0,
        published_at DATETIME,
        scheduled_at DATETIME,
        archived_at DATETIME,
        author_id INT,
        author_name VARCHAR(200),
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $catSchema = "CREATE TABLE IF NOT EXISTS news_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $seedCats = "INSERT IGNORE INTO news_categories (name, slug, description, sort_order, is_active) VALUES
        ('General','general','General news and announcements',1,1),
        ('Academic','academic','Academic news and updates',2,1),
        ('Events','events','Upcoming and past events',3,1),
        ('Sports','sports','Sports news and results',4,1),
        ('Announcements','announcements','Important announcements',5,1),
        ('Staff','staff','Staff-related news',6,1),
        ('Student Life','student-life','Student life and activities',7,1)";

    foreach ([$staffConn, $websiteConn] as $c) {
        if (!$c) continue;
        @$c->query($schema);
        @$c->query($catSchema);
        @$c->query($seedCats);
    }

    // Ensure website DB news table has required columns for sync
    if ($websiteConn) {
        $websiteCols = ['summary', 'category', 'tags', 'is_featured', 'scheduled_at', 'archived_at', 'views', 'excerpt'];
        foreach ($websiteCols as $col) {
            $typeMap = [
                'summary' => 'TEXT',
                'category' => "VARCHAR(100) DEFAULT 'General'",
                'tags' => 'VARCHAR(500)',
                'is_featured' => 'TINYINT(1) DEFAULT 0',
                'scheduled_at' => 'DATETIME',
                'archived_at' => 'DATETIME',
                'views' => 'INT DEFAULT 0',
                'excerpt' => 'TEXT',
            ];
            $type = $typeMap[$col] ?? 'TEXT';
            @$websiteConn->query("ALTER TABLE news ADD COLUMN IF NOT EXISTS `$col` $type");
        }
        // Ensure status ENUM includes 'scheduled'
        @$websiteConn->query("ALTER TABLE news MODIFY COLUMN status ENUM('draft','published','scheduled','archived') DEFAULT 'draft'");
    }
}
ensureNewsTables($staff, $website);

function logActivity($conn, $userId, $action, $description) {
    if (!$conn || !$userId) return;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $conn->prepare("INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent, created_at) VALUES (?, 'news', ?, 'news_management', ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('isss', $userId, $description, $ip, $ua);
        @$stmt->execute();
        $stmt->close();
    }
}

function syncToWebsite($websiteConn, $operation, $data, $staffId) {
    if (!$websiteConn) return;
    try {
        switch ($operation) {
            case 'insert':
                $stmt = $websiteConn->prepare("INSERT INTO news (id,title,slug,content,excerpt,featured_image,author_id,author_name,status,published_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
                if ($stmt) {
                    $summary = $data['summary'] ?? $data['content'] ?? '';
                    if (mb_strlen($summary) > 200) $summary = mb_substr($summary, 0, 200) . '...';
                    $excerpt = $data['summary'] ?? mb_substr(strip_tags($data['content'] ?? ''), 0, 200);
                    $publishedAt = $data['status'] === 'published' ? date('Y-m-d H:i:s') : ($data['published_at'] ?? null);
                    $authorName = $data['author_name'] ?? '';
                    $authorId = $data['author_id'] ?? $staffId;
                    $stmt->bind_param('isssssisss', $data['id'],$data['title'],$data['slug'],$data['content'],$excerpt,$data['featured_image'],$authorId,$authorName,$data['status'],$publishedAt);
                    @$stmt->execute();
                    $stmt->close();
                }
                break;
            case 'update':
                $stmt = $websiteConn->prepare("UPDATE news SET title=?,slug=?,content=?,excerpt=?,featured_image=?,author_id=?,author_name=?,status=?,published_at=?,updated_at=NOW() WHERE id=?");
                if ($stmt) {
                    $excerpt = $data['summary'] ?? mb_substr(strip_tags($data['content'] ?? ''), 0, 200);
                    $publishedAt = $data['status'] === 'published' ? date('Y-m-d H:i:s') : ($data['published_at'] ?? null);
                    $authorName = $data['author_name'] ?? '';
                    $authorId = $data['author_id'] ?? $staffId;
                    $stmt->bind_param('sssssisssi', $data['title'],$data['slug'],$data['content'],$excerpt,$data['featured_image'],$authorId,$authorName,$data['status'],$publishedAt,$data['id']);
                    @$stmt->execute();
                    $stmt->close();
                }
                break;
            case 'delete':
                $stmt = $websiteConn->prepare("DELETE FROM news WHERE id=?");
                if ($stmt) { $stmt->bind_param('i', $data['id']); @$stmt->execute(); $stmt->close(); }
                break;
        }
    } catch (\Throwable $e) { error_log('syncToWebsite error: ' . $e->getMessage()); }
}

function generateSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'news-' . time();
}

function getNewsById($conn, $id) {
    if (!$conn) return null;
    $id = (int)$id;
    $stmt = $conn->prepare("SELECT * FROM news WHERE id=?");
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    @$stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function ensureSlugUnique($conn, $slug, $excludeId = 0) {
    if (!$conn) return $slug;
    $base = $slug;
    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM news WHERE slug=? AND id!=?");
        if (!$stmt) break;
        $stmt->bind_param('si', $slug, $excludeId);
        @$stmt->execute();
        $res = $stmt->get_result();
        $exists = $res && $res->num_rows > 0;
        $stmt->close();
        if (!$exists) return $slug;
        $slug = $base . '-' . (++$counter);
    }
    return $slug;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'Invalid security token. Please refresh.'];
        header('Location: news.php');
        exit;
    }

    switch ($action) {
        case 'create_news': {
            $title    = trim($_POST['title'] ?? '');
            $summary  = trim($_POST['summary'] ?? '');
            $content  = trim($_POST['content'] ?? '');
            $category = trim($_POST['category'] ?? 'General');
            $tags     = trim($_POST['tags'] ?? '');
            $featured = trim($_POST['featured_image'] ?? '');
            $status   = $_POST['status'] ?? 'draft';
            $isFeat   = (int)($_POST['is_featured'] ?? 0);
            $schedAt  = trim($_POST['scheduled_at'] ?? '');
            $autName  = $userName;
            $pubAt    = ($status === 'published') ? date('Y-m-d H:i:s') : null;

            if ($status === 'scheduled' && $schedAt) {
                $pubAt = null;
            } elseif ($status === 'scheduled') {
                $status = 'draft';
            }

            if (empty($title)) {
                $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'Title is required.'];
                header('Location: news.php');
                exit;
            }

            $slug = generateSlug($title);
            $slug = ensureSlugUnique($staff, $slug);

            $stmt = $staff->prepare("INSERT INTO news (title,slug,summary,content,featured_image,category,tags,status,is_featured,published_at,scheduled_at,author_id,author_name,views,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,0,NOW())");
            if ($stmt) {
                $nullDt = null;
                $stmt->bind_param('ssssssssissis', $title,$slug,$summary,$content,$featured,$category,$tags,$status,$isFeat,$pubAt,$schedAt,$userId,$autName);
                @$stmt->execute();
                $newId = $stmt->insert_id;
                $stmt->close();

                if ($newId > 0) {
                    syncToWebsite($website, 'insert', [
                        'id' => $newId, 'title' => $title, 'slug' => $slug,
                        'summary' => $summary, 'content' => $content,
                        'featured_image' => $featured, 'category' => $category,
                        'tags' => $tags, 'status' => $status,
                        'is_featured' => $isFeat, 'published_at' => $pubAt,
                        'scheduled_at' => $schedAt, 'archived_at' => null,
                        'author_id' => $userId, 'author_name' => $autName, 'views' => 0
                    ], $userId);

                    logActivity($staff, $userId, 'create', "Created news: $title");

                    if ($status === 'published') {
                        $nid = createNotification('New News: ' . $title, mb_substr(strip_tags($content), 0, 200), 'news.php', 'info', 'fas fa-newspaper');
                        if ($nid) notifyAllStaff($nid);
                    }

                    $_SESSION['news_flash'] = ['type' => 'success', 'msg' => 'News article created successfully.'];
                } else {
                    $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'Failed to create news article.'];
                }
            } else {
                $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'Database error. Please try again.'];
            }
            header('Location: news.php');
            exit;
        }

        case 'update_news': {
            $newsId   = (int)($_POST['news_id'] ?? 0);
            $title    = trim($_POST['title'] ?? '');
            $summary  = trim($_POST['summary'] ?? '');
            $content  = trim($_POST['content'] ?? '');
            $category = trim($_POST['category'] ?? 'General');
            $tags     = trim($_POST['tags'] ?? '');
            $featured = trim($_POST['featured_image'] ?? '');
            $status   = $_POST['status'] ?? 'draft';
            $isFeat   = (int)($_POST['is_featured'] ?? 0);
            $schedAt  = trim($_POST['scheduled_at'] ?? '');
            $pubAt    = ($status === 'published') ? date('Y-m-d H:i:s') : null;
            $archAt   = ($status === 'archived') ? date('Y-m-d H:i:s') : null;

            if ($status === 'scheduled' && $schedAt) { $pubAt = null; }

            if (empty($title) || $newsId <= 0) {
                $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'Invalid news data.'];
                header('Location: news.php');
                exit;
            }

            $slug = generateSlug($title);
            $slug = ensureSlugUnique($staff, $slug, $newsId);

            $existing = getNewsById($staff, $newsId);
            if (!$existing) {
                $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'News article not found.'];
                header('Location: news.php');
                exit;
            }

            $stmt = $staff->prepare("UPDATE news SET title=?,slug=?,summary=?,content=?,featured_image=?,category=?,tags=?,status=?,is_featured=?,published_at=COALESCE(?,published_at),scheduled_at=?,archived_at=?,author_id=?,author_name=?,updated_at=NOW() WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('ssssssssiissiii', $title,$slug,$summary,$content,$featured,$category,$tags,$status,$isFeat,$pubAt,$schedAt,$archAt,$userId,$userName,$newsId);
                @$stmt->execute();
                $stmt->close();

                $updated = getNewsById($staff, $newsId);
                if ($updated) {
                    syncToWebsite($website, 'update', $updated, $userId);
                }

                logActivity($staff, $userId, 'update', "Updated news: $title");

                $_SESSION['news_flash'] = ['type' => 'success', 'msg' => 'News article updated successfully.'];
            } else {
                $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'Database error. Please try again.'];
            }
            header('Location: news.php');
            exit;
        }

        case 'delete_news': {
            $newsId = (int)($_POST['news_id'] ?? 0);
            if ($newsId <= 0) {
                $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'Invalid news ID.'];
                header('Location: news.php');
                exit;
            }

            $existing = getNewsById($staff, $newsId);
            if (!$existing) {
                $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'News article not found.'];
                header('Location: news.php');
                exit;
            }

            $isAdmin = in_array(strtolower($role), ['director general','ceo','director']);

            if ($isAdmin) {
                $stmt = $staff->prepare("DELETE FROM news WHERE id=?");
                if ($stmt) { $stmt->bind_param('i', $newsId); @$stmt->execute(); $stmt->close(); }
                syncToWebsite($website, 'delete', ['id' => $newsId], $userId);
                logActivity($staff, $userId, 'delete', "Permanently deleted news: " . $existing['title']);
                $_SESSION['news_flash'] = ['type' => 'success', 'msg' => 'News article permanently deleted.'];
            } else {
                $archAt = date('Y-m-d H:i:s');
                $stmt = $staff->prepare("UPDATE news SET status='archived', archived_at=?, updated_at=NOW() WHERE id=?");
                if ($stmt) { $stmt->bind_param('si', $archAt, $newsId); @$stmt->execute(); $stmt->close(); }
                $stmt2 = $website->prepare("UPDATE news SET status='archived', archived_at=?, updated_at=NOW() WHERE id=?");
                if ($stmt2) { $stmt2->bind_param('si', $archAt, $newsId); @$stmt2->execute(); $stmt2->close(); }
                logActivity($staff, $userId, 'archive', "Archived news: " . $existing['title']);
                $_SESSION['news_flash'] = ['type' => 'success', 'msg' => 'News article archived.'];
            }
            header('Location: news.php');
            exit;
        }

        case 'publish_news': {
            $newsId = (int)($_POST['news_id'] ?? 0);
            if ($newsId <= 0) { header('Location: news.php'); exit; }
            $pubAt = date('Y-m-d H:i:s');
            $stmt = $staff->prepare("UPDATE news SET status='published', published_at=?, scheduled_at=NULL, archived_at=NULL, updated_at=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('si', $pubAt, $newsId); @$stmt->execute(); $stmt->close(); }
            $stmt2 = $website->prepare("UPDATE news SET status='published', published_at=?, scheduled_at=NULL, archived_at=NULL, updated_at=NOW() WHERE id=?");
            if ($stmt2) { $stmt2->bind_param('si', $pubAt, $newsId); @$stmt2->execute(); $stmt2->close(); }
            $existing = getNewsById($staff, $newsId);
            logActivity($staff, $userId, 'publish', "Published news: " . ($existing['title'] ?? 'ID ' . $newsId));
            if ($existing) {
                $nid = createNotification('News Published: ' . $existing['title'], mb_substr(strip_tags($existing['content']), 0, 200), 'news.php', 'info', 'fas fa-newspaper');
                if ($nid) notifyAllStaff($nid);
            }
            $_SESSION['news_flash'] = ['type' => 'success', 'msg' => 'News published to website.'];
            header('Location: news.php');
            exit;
        }

        case 'schedule_news': {
            $newsId = (int)($_POST['news_id'] ?? 0);
            $schedAt = trim($_POST['scheduled_at'] ?? '');
            if ($newsId <= 0 || empty($schedAt)) {
                $_SESSION['news_flash'] = ['type' => 'danger', 'msg' => 'Please provide a schedule date.'];
                header('Location: news.php');
                exit;
            }
            $stmt = $staff->prepare("UPDATE news SET status='scheduled', scheduled_at=?, published_at=NULL, archived_at=NULL, updated_at=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('si', $schedAt, $newsId); @$stmt->execute(); $stmt->close(); }
            $stmt2 = $website->prepare("UPDATE news SET status='scheduled', scheduled_at=?, published_at=NULL, archived_at=NULL, updated_at=NOW() WHERE id=?");
            if ($stmt2) { $stmt2->bind_param('si', $schedAt, $newsId); @$stmt2->execute(); $stmt2->close(); }
            $existing = getNewsById($staff, $newsId);
            logActivity($staff, $userId, 'schedule', "Scheduled news: " . ($existing['title'] ?? 'ID ' . $newsId) . " for $schedAt");
            $_SESSION['news_flash'] = ['type' => 'success', 'msg' => 'News scheduled for ' . date('M j, Y H:i', strtotime($schedAt)) . '.'];
            header('Location: news.php');
            exit;
        }

        case 'feature_news': {
            $newsId = (int)($_POST['news_id'] ?? 0);
            if ($newsId <= 0) { header('Location: news.php'); exit; }
            $existing = getNewsById($staff, $newsId);
            $newFeat = $existing && $existing['is_featured'] ? 0 : 1;
            $stmt = $staff->prepare("UPDATE news SET is_featured=?, updated_at=NOW() WHERE id=?");
            if ($stmt) { $stmt->bind_param('ii', $newFeat, $newsId); @$stmt->execute(); $stmt->close(); }
            $stmt2 = $website->prepare("UPDATE news SET is_featured=?, updated_at=NOW() WHERE id=?");
            if ($stmt2) { $stmt2->bind_param('ii', $newFeat, $newsId); @$stmt2->execute(); $stmt2->close(); }
            logActivity($staff, $userId, 'feature', ($newFeat ? 'Featured' : 'Unfeatured') . ' news: ' . ($existing['title'] ?? 'ID ' . $newsId));
            $_SESSION['news_flash'] = ['type' => 'success', 'msg' => $newFeat ? 'News is now featured.' : 'News is no longer featured.'];
            header('Location: news.php');
            exit;
        }
    }
}

$search   = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterCat    = $_GET['category'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = '';
$types  = '';
$bindValues = [];

if ($search !== '') {
    $where .= " AND (title LIKE ? OR summary LIKE ? OR tags LIKE ? OR author_name LIKE ?)";
    $like = "%$search%";
    $params .= 'ssss';
    $types .= $like . $like . $like . $like;
    $bindValues = array_merge($bindValues, [$like, $like, $like, $like]);
}
if ($filterStatus !== '' && in_array($filterStatus, ['draft','published','scheduled','archived'])) {
    $where .= " AND status=?";
    $params .= 's';
    $types .= $filterStatus;
    $bindValues[] = $filterStatus;
}
if ($filterCat !== '') {
    $where .= " AND category=?";
    $params .= 's';
    $types .= $filterCat;
    $bindValues[] = $filterCat;
}

$totalNews = 0;
$newsList  = [];
$stats     = ['total' => 0, 'published' => 0, 'draft' => 0, 'scheduled' => 0];
$categories = [];

if ($staff) {
    $cntStmt = $staff->prepare("SELECT COUNT(*) AS c FROM news $where");
    if ($cntStmt) {
        if (!empty($bindValues)) {
            $cntStmt->bind_param($params, ...$bindValues);
        }
        @$cntStmt->execute();
        $cntRes = $cntStmt->get_result()->fetch_assoc();
        $totalNews = (int)($cntRes['c'] ?? 0);
        $cntStmt->close();
    }

    $totalPages = max(1, ceil($totalNews / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $dataStmt = $staff->prepare("SELECT * FROM news $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
    if ($dataStmt) {
        $lim = (int)$perPage;
        $off = (int)$offset;
        $allParams = array_merge($bindValues, [$lim, $off]);
        $allTypes  = $params . 'ii';
        $dataStmt->bind_param($allTypes, ...$allParams);
        @$dataStmt->execute();
        $res = $dataStmt->get_result();
        if ($res) while ($row = $res->fetch_assoc()) $newsList[] = $row;
        $dataStmt->close();
    }

    $statQ = $staff->query("SELECT
        COUNT(*) AS total,
        SUM(status='published') AS published,
        SUM(status='draft') AS draft,
        SUM(status='scheduled') AS scheduled
        FROM news");
    if ($statQ) $stats = $statQ->fetch_assoc();

    $catQ = $staff->query("SELECT name, slug FROM news_categories WHERE is_active=1 ORDER BY sort_order");
    if ($catQ) while ($c = $catQ->fetch_assoc()) $categories[] = $c;
}

$totalPages = max(1, ceil($totalNews / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.news-status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; letter-spacing: 0.3px; }
.badge-draft { background: #f1f5f9; color: #475569; }
.badge-published { background: #dcfce7; color: #166534; }
.badge-scheduled { background: #fef9c3; color: #854d0e; }
.badge-archived { background: #e2e8f0; color: #64748b; }
.star-btn { cursor: pointer; font-size: 18px; transition: all 0.2s; color: #cbd5e1; }
.star-btn:hover { transform: scale(1.2); }
.star-btn.active { color: #f59e0b; }
.news-table { font-size: 13px; }
.news-table th { background: #f8fafc; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; font-weight: 600; }
.news-table td { vertical-align: middle; padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
.news-table tr:hover td { background: #f8fafc; }
.news-title { font-weight: 600; color: #0f172a; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.news-actions .btn { padding: 3px 8px; font-size: 11px; border-radius: 6px; }
.filter-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; }
.filter-bar .form-control, .filter-bar .form-select { font-size: 13px; border-radius: 8px; border-color: #e2e8f0; }
.filter-bar .form-control:focus, .filter-bar .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.15); }
.stats-card { background: #fff; border-radius: 10px; padding: 16px; border: 1px solid #e2e8f0; text-align: center; }
.stats-card h3 { font-size: 24px; font-weight: 700; margin: 0; }
.stats-card p { font-size: 12px; color: #64748b; margin: 0; }
.stats-card .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; margin-bottom: 8px; }
.news-compose-form label { font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px; }
.news-compose-form .form-control, .news-compose-form .form-select { font-size: 13px; border-radius: 8px; }
.modal-header-gradient { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; }
.modal-header-gradient .btn-close { filter: brightness(0) invert(1); }
.preview-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1060; display: none; align-items: center; justify-content: center; }
.preview-overlay.show { display: flex; }
.preview-box { background: #fff; border-radius: 12px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.preview-box .preview-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.preview-box .preview-body { padding: 24px; line-height: 1.8; font-size: 14px; color: #334155; }
.pagination-custom .page-link { border-radius: 8px; margin: 0 2px; font-size: 13px; color: #1e3a8a; border: 1px solid #e2e8f0; }
.pagination-custom .page-item.active .page-link { background: #1e3a8a; border-color: #1e3a8a; color: #fff; }
.countdown-badge { font-size: 11px; padding: 3px 8px; border-radius: 12px; background: #fef3c7; color: #92400e; display: inline-flex; align-items: center; gap: 4px; }
@media (max-width: 768px) {
    .stats-row { grid-template-columns: repeat(2, 1fr) !important; }
    .news-table { font-size: 12px; }
    .news-title { max-width: 160px; }
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<main class="main" style="margin-left:270px;padding:32px;">
<div class="container-fluid">

<?php if ($flash['msg']): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'danger' ? 'danger' : 'info') ?> alert-dismissible fade show py-2" role="alert" style="border-radius:10px;font-size:13px;">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-1"></i>
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-newspaper me-2" style="color:#1e3a8a;"></i>News Management</h4>
        <small class="text-muted">Manage news articles and announcements</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="text-muted small" id="currentDate"></span>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print"><i class="fas fa-print"></i></button>
        <button class="btn btn-sm btn-outline-secondary" onclick="exportCSV()" title="Export CSV"><i class="fas fa-download"></i></button>
        <button class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;" onclick="openCreateModal()"><i class="fas fa-pen me-1"></i>Compose</button>
    </div>
</div>

<div class="stats-row" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
    <div class="stats-card">
        <div class="stat-icon" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-newspaper"></i></div>
        <h3 style="color:#0f172a;"><?= (int)($stats['total'] ?? 0) ?></h3>
        <p>Total News</p>
    </div>
    <div class="stats-card">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
        <h3 style="color:#16a34a;"><?= (int)($stats['published'] ?? 0) ?></h3>
        <p>Published</p>
    </div>
    <div class="stats-card">
        <div class="stat-icon" style="background:#f1f5f9;color:#64748b;"><i class="fas fa-file-alt"></i></div>
        <h3 style="color:#475569;"><?= (int)($stats['draft'] ?? 0) ?></h3>
        <p>Drafts</p>
    </div>
    <div class="stats-card">
        <div class="stat-icon" style="background:#fef9c3;color:#ca8a04;"><i class="fas fa-clock"></i></div>
        <h3 style="color:#ca8a04;"><?= (int)($stats['scheduled'] ?? 0) ?></h3>
        <p>Scheduled</p>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search news..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="scheduled" <?= $filterStatus === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                <option value="archived" <?= $filterStatus === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $filterCat === $cat['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px;"><i class="fas fa-filter me-1"></i>Filter</button>
            <a href="news.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Clear</a>
        </div>
    </form>
</div>

<div style="background:#fff;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden;">
    <div class="table-responsive">
        <table class="table news-table mb-0" id="newsTable">
            <thead>
                <tr>
                    <th style="width:30px;">#</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th style="width:50px;">Featured</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th style="width:180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($newsList)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="fas fa-newspaper fa-2x text-muted mb-2 d-block"></i>
                        <p class="text-muted mb-0">No news articles found.</p>
                        <button class="btn btn-sm btn-primary mt-3" style="border-radius:8px;" onclick="openCreateModal()"><i class="fas fa-plus me-1"></i>Create First Article</button>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($newsList as $i => $n):
                        $idx = $offset + $i + 1;
                        $statusBadge = 'badge-' . ($n['status'] ?? 'draft');
                        $isScheduled = ($n['status'] === 'scheduled' && !empty($n['scheduled_at']));
                        $schedCountdown = '';
                        if ($isScheduled) {
                            $diff = strtotime($n['scheduled_at']) - time();
                            if ($diff > 0) {
                                $days = floor($diff / 86400);
                                $hours = floor(($diff % 86400) / 3600);
                                $mins = floor(($diff % 3600) / 60);
                                $parts = [];
                                if ($days > 0) $parts[] = $days . 'd';
                                if ($hours > 0) $parts[] = $hours . 'h';
                                $parts[] = $mins . 'm';
                                $schedCountdown = implode(' ', $parts);
                            } else {
                                $schedCountdown = 'Due now';
                            }
                        }
                    ?>
                    <tr data-id="<?= $n['id'] ?>" data-title="<?= htmlspecialchars($n['title']) ?>"
                        data-slug="<?= htmlspecialchars($n['slug']) ?>"
                        data-summary="<?= htmlspecialchars($n['summary'] ?? '') ?>"
                        data-content="<?= htmlspecialchars($n['content'] ?? '') ?>"
                        data-category="<?= htmlspecialchars($n['category'] ?? 'General') ?>"
                        data-tags="<?= htmlspecialchars($n['tags'] ?? '') ?>"
                        data-image="<?= htmlspecialchars($n['featured_image'] ?? '') ?>"
                        data-status="<?= $n['status'] ?>"
                        data-featured="<?= $n['is_featured'] ?>"
                        data-scheduled="<?= htmlspecialchars($n['scheduled_at'] ?? '') ?>">
                        <td><?= $idx ?></td>
                        <td>
                            <div class="news-title" title="<?= htmlspecialchars($n['title']) ?>"><?= htmlspecialchars($n['title']) ?></div>
                            <?php if (!empty($n['summary'])): ?>
                            <small class="text-muted" style="font-size:11px;"><?= htmlspecialchars(mb_substr($n['summary'], 0, 80)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge" style="background:#f1f5f9;color:#475569;font-weight:500;font-size:11px;"><?= htmlspecialchars($n['category'] ?? 'General') ?></span></td>
                        <td>
                            <span class="news-status-badge <?= $statusBadge ?>"><?= ucfirst($n['status']) ?></span>
                            <?php if ($isScheduled && $schedCountdown): ?>
                                <div class="countdown-badge mt-1"><i class="fas fa-clock"></i> <?= $schedCountdown ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return featureConfirm(this);">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="feature_news">
                                <input type="hidden" name="news_id" value="<?= $n['id'] ?>">
                                <button type="submit" class="star-btn <?= $n['is_featured'] ? 'active' : '' ?>" title="<?= $n['is_featured'] ? 'Remove from featured' : 'Mark as featured' ?>">
                                    <i class="fas fa-star"></i>
                                </button>
                            </form>
                        </td>
                        <td><small><?= htmlspecialchars($n['author_name'] ?? 'Unknown') ?></small></td>
                        <td>
                            <small class="text-muted"><?= date('M j, Y', strtotime($n['created_at'])) ?></small>
                            <br><small class="text-muted" style="font-size:10px;"><?= date('H:i', strtotime($n['created_at'])) ?></small>
                        </td>
                        <td class="news-actions">
                            <div class="d-flex gap-1 flex-wrap">
                                <button class="btn btn-sm btn-outline-primary" onclick="previewNews(<?= $n['id'] ?>)" title="Preview"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="openEditModal(<?= $n['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                <?php if ($n['status'] !== 'published'): ?>
                                <form method="POST" style="display:inline;" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="publish_news">
                                    <input type="hidden" name="news_id" value="<?= $n['id'] ?>">
                                    <button class="btn btn-sm btn-outline-success" type="submit" title="Publish"><i class="fas fa-globe"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($n['status'] !== 'scheduled'): ?>
                                <button class="btn btn-sm btn-outline-warning" onclick="openScheduleModal(<?= $n['id'] ?>)" title="Schedule"><i class="fas fa-clock"></i></button>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" class="d-inline" onsubmit="return confirm('Delete this news article?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_news">
                                    <input type="hidden" name="news_id" value="<?= $n['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3 d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing <?= ($offset + 1) ?>-<?= min($offset + $perPage, $totalNews) ?> of <?= $totalNews ?> articles</small>
    <ul class="pagination pagination-custom mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&category=<?= urlencode($filterCat) ?>"><i class="fas fa-chevron-left"></i></a>
        </li>
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&category=<?= urlencode($filterCat) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&category=<?= urlencode($filterCat) ?>"><i class="fas fa-chevron-right"></i></a>
        </li>
    </ul>
</nav>
<?php endif; ?>

</div>
</main>

<!-- Create/Edit Modal -->
<div class="modal fade" id="newsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:12px;border:none;">
            <div class="modal-header modal-header-gradient" style="padding:14px 18px;">
                <h5 class="modal-title" style="font-size:15px;" id="newsModalTitle"><i class="fas fa-pen me-2"></i>New Article</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body news-compose-form" style="padding:18px;">
                <form id="newsForm" onsubmit="return submitNews(event)">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" id="newsFormAction" value="create_news">
                    <input type="hidden" name="news_id" id="newsFormId" value="0">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" id="newsFormTitle" class="form-control" required maxlength="300" placeholder="Enter article title...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" id="newsFormSlug" class="form-control" readonly style="background:#f8fafc;color:#64748b;font-size:12px;">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Summary</label>
                            <textarea name="summary" id="newsFormSummary" class="form-control" rows="2" placeholder="Brief summary for previews..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Content *</label>
                            <textarea name="content" id="newsFormContent" class="form-control" rows="8" required placeholder="Write your news article..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category" id="newsFormCategory" class="form-select">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="newsFormStatus" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tags (comma separated)</label>
                            <input type="text" name="tags" id="newsFormTags" class="form-control" placeholder="news, update, important">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Featured Image URL</label>
                            <input type="url" name="featured_image" id="newsFormImage" class="form-control" placeholder="https://example.com/image.jpg">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Date/Time</label>
                            <input type="datetime-local" name="scheduled_at" id="newsFormScheduled" class="form-control">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="is_featured" id="newsFormFeatured" class="form-check-input" value="1">
                                <label class="form-check-label" for="newsFormFeatured" style="font-size:13px;">Featured Article</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0;padding:12px 18px;">
                <span id="newsFormResult" class="small me-auto"></span>
                <button type="button" class="btn btn-sm" style="background:#e2e8f0;color:#475569;border:none;border-radius:8px;" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;" onclick="document.getElementById('newsForm').requestSubmit()"><i class="fas fa-save me-1"></i><span id="newsFormSubmitText">Save</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:12px;border:none;">
            <div class="modal-header modal-header-gradient" style="padding:14px 18px;">
                <h5 class="modal-title" style="font-size:15px;"><i class="fas fa-clock me-2"></i>Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body news-compose-form" style="padding:18px;">
                <form method="POST" id="scheduleForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="schedule_news">
                    <input type="hidden" name="news_id" id="scheduleNewsId" value="">
                    <div class="mb-3">
                        <label class="form-label">Publish Date & Time *</label>
                        <input type="datetime-local" name="scheduled_at" id="scheduleDateTime" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-sm w-100" style="background:#2563eb;color:#fff;border:none;border-radius:8px;"><i class="fas fa-clock me-1"></i>Schedule</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Preview Overlay -->
<div class="preview-overlay" id="previewOverlay" onclick="closePreview(event)">
    <div class="preview-box" onclick="event.stopPropagation()">
        <div class="preview-header">
            <h5 class="mb-0 fw-bold" style="font-size:16px;" id="previewTitle"></h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('previewOverlay').classList.remove('show')"><i class="fas fa-times"></i></button>
        </div>
        <div class="preview-body" id="previewBody"></div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
var newsData = <?= json_encode($newsList) ?>;
var currentUserId = <?= $userId ?>;

(function() {
    var el = document.getElementById('currentDate');
    if (el) {
        var now = new Date();
        el.textContent = now.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
    }
})();

document.getElementById('newsFormTitle').addEventListener('input', function() {
    var action = document.getElementById('newsFormAction').value;
    if (action === 'create_news') {
        var title = this.value;
        var slug = title.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, '');
        document.getElementById('newsFormSlug').value = slug || '';
    }
});

function openCreateModal() {
    document.getElementById('newsFormAction').value = 'create_news';
    document.getElementById('newsFormId').value = '0';
    document.getElementById('newsFormTitle').value = '';
    document.getElementById('newsFormSlug').value = '';
    document.getElementById('newsFormSummary').value = '';
    document.getElementById('newsFormContent').value = '';
    document.getElementById('newsFormCategory').value = 'General';
    document.getElementById('newsFormStatus').value = 'draft';
    document.getElementById('newsFormTags').value = '';
    document.getElementById('newsFormImage').value = '';
    document.getElementById('newsFormScheduled').value = '';
    document.getElementById('newsFormFeatured').checked = false;
    document.getElementById('newsFormResult').innerHTML = '';
    document.getElementById('newsFormTitle').removeAttribute('readonly');
    document.getElementById('newsModalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>New Article';
    document.getElementById('newsFormSubmitText').textContent = 'Create';
    new bootstrap.Modal(document.getElementById('newsModal')).show();
}

function openEditModal(id) {
    var row = document.querySelector('tr[data-id="' + id + '"]');
    if (!row) return;
    document.getElementById('newsFormAction').value = 'update_news';
    document.getElementById('newsFormId').value = id;
    document.getElementById('newsFormTitle').value = row.dataset.title || '';
    document.getElementById('newsFormSlug').value = row.dataset.slug || '';
    document.getElementById('newsFormSummary').value = row.dataset.summary || '';
    document.getElementById('newsFormContent').value = row.dataset.content || '';
    document.getElementById('newsFormCategory').value = row.dataset.category || 'General';
    document.getElementById('newsFormStatus').value = row.dataset.status || 'draft';
    document.getElementById('newsFormTags').value = row.dataset.tags || '';
    document.getElementById('newsFormImage').value = row.dataset.image || '';
    document.getElementById('newsFormScheduled').value = (row.dataset.scheduled || '').replace(' ', 'T').substring(0, 16);
    document.getElementById('newsFormFeatured').checked = row.dataset.featured === '1';
    document.getElementById('newsFormResult').innerHTML = '';
    document.getElementById('newsFormTitle').removeAttribute('readonly');
    document.getElementById('newsModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Article';
    document.getElementById('newsFormSubmitText').textContent = 'Update';
    new bootstrap.Modal(document.getElementById('newsModal')).show();
}

function submitNews(e) {
    e.preventDefault();
    var form = document.getElementById('newsForm');
    var result = document.getElementById('newsFormResult');
    var title = document.getElementById('newsFormTitle').value.trim();
    var content = document.getElementById('newsFormContent').value.trim();
    var action = document.getElementById('newsFormAction').value;

    if (!title) { result.innerHTML = '<span class="text-danger">Title is required.</span>'; return false; }
    if (!content) { result.innerHTML = '<span class="text-danger">Content is required.</span>'; return false; }

    result.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Saving...</span>';

    var fd = new FormData(form);
    fetch('news.php', { method: 'POST', body: fd })
        .then(function(r) { return r.text().then(function() { return r; }); })
        .then(function(r) {
            if (r.redirected || r.ok) {
                result.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Saved!</span>';
                setTimeout(function() { location.reload(); }, 800);
            } else {
                result.innerHTML = '<span class="text-danger">Error saving article.</span>';
            }
        })
        .catch(function() {
            result.innerHTML = '<span class="text-danger">Network error.</span>';
        });
    return false;
}

function openScheduleModal(id) {
    document.getElementById('scheduleNewsId').value = id;
    document.getElementById('scheduleDateTime').value = '';
    new bootstrap.Modal(document.getElementById('scheduleModal')).show();
}

function previewNews(id) {
    var row = document.querySelector('tr[data-id="' + id + '"]');
    if (!row) return;
    var title = row.dataset.title || '';
    var content = row.dataset.content || '';
    var category = row.dataset.category || 'General';
    var summary = row.dataset.summary || '';
    var image = row.dataset.image || '';
    var status = row.dataset.status || '';
    var featured = row.dataset.featured === '1';
    var tags = row.dataset.tags || '';

    var html = '<div style="margin-bottom:16px;">';
    html += '<span class="news-status-badge badge-' + status + '" style="margin-right:8px;">' + escHtml(status.charAt(0).toUpperCase() + status.slice(1)) + '</span>';
    html += '<span class="badge" style="background:#f1f5f9;color:#475569;font-size:11px;">' + escHtml(category) + '</span>';
    if (featured) html += ' <i class="fas fa-star" style="color:#f59e0b;font-size:12px;"></i> Featured';
    html += '</div>';
    if (image) html += '<img src="' + escHtml(image) + '" class="img-fluid rounded mb-3" style="max-height:200px;width:100%;object-fit:cover;">';
    if (summary) html += '<p class="text-muted" style="font-style:italic;font-size:14px;">' + escHtml(summary) + '</p>';
    html += '<div style="line-height:1.9;color:#334155;">' + (content || 'No content available.').replace(/\n/g, '<br>') + '</div>';
    if (tags) {
        html += '<div class="mt-3">';
        tags.split(',').forEach(function(t) {
            t = t.trim();
            if (t) html += '<span class="badge" style="background:#eff6ff;color:#1e40af;margin-right:4px;font-weight:400;">' + escHtml(t) + '</span>';
        });
        html += '</div>';
    }
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewBody').innerHTML = html;
    document.getElementById('previewOverlay').classList.add('show');
}

function closePreview(e) {
    if (e.target === document.getElementById('previewOverlay')) {
        document.getElementById('previewOverlay').classList.remove('show');
    }
}

function featureConfirm(form) {
    return true;
}

function exportCSV() {
    var rows = document.querySelectorAll('#newsTable tbody tr');
    if (!rows.length || (rows.length === 1 && rows[0].querySelector('td[colspan]'))) {
        alert('No data to export.');
        return;
    }
    var csv = 'ID,Title,Category,Status,Featured,Author,Created At\n';
    rows.forEach(function(row) {
        if (row.querySelector('td[colspan]')) return;
        var cells = row.querySelectorAll('td');
        if (cells.length < 7) return;
        var id = cells[0].textContent.trim();
        var title = '"' + (row.dataset.title || '').replace(/"/g, '""') + '"';
        var category = '"' + (cells[2].textContent.trim()) + '"';
        var status = cells[3].textContent.trim().split('\n')[0].trim();
        var featured = row.dataset.featured === '1' ? 'Yes' : 'No';
        var author = cells[5].textContent.trim();
        var date = cells[6].textContent.trim().replace(/\n/g, ' ');
        csv += id + ',' + title + ',' + category + ',' + status + ',' + featured + ',' + author + ',' + date + '\n';
    });
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'news_export_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
}

function escHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
</script>
</body>
</html>
