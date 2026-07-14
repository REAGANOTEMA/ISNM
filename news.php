<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';

session_start();

$user = null;
$is_admin = false;

// Check for authorized staff (DG, Directors, Principal only)
if (isset($_SESSION['user_id']) && isset($_SESSION['type']) && $_SESSION['type'] === 'staff') {
    $auth = new AuthenticationService();
    if ($auth->isAuthenticated()) {
        $user = $auth->getCurrentUser();
        $role = $user['role'] ?? '';
        $canManageNews = stripos($role, 'director') !== false
                      || stripos($role, 'principal') !== false
                      || stripos($role, 'ceo') !== false;
        if ($canManageNews) {
            $is_admin = true;
        }
    }
}

$staffConn = getStaffConnection();
$websiteConn = getWebsiteConnection();

// Ensure the news table exists in website DB
if (!function_exists('ensureNewsTable')) {
    function ensureNewsTable($conn) {
        if (!$conn) return false;
        $r = $conn->query("SHOW TABLES LIKE 'news'");
        if ($r && $r->num_rows > 0) return true;
        $conn->query("CREATE TABLE IF NOT EXISTS news (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            content LONGTEXT,
            excerpt TEXT,
            featured_image VARCHAR(500),
            author_id INT,
            author_name VARCHAR(255),
            author_role VARCHAR(255),
            status ENUM('draft','published','archived') DEFAULT 'draft',
            published_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return true;
    }
}
ensureNewsTable($websiteConn);

$errors = [];
$success = '';

// Get success/error from session
if (!empty($_SESSION['news_success'])) { $success = $_SESSION['news_success']; unset($_SESSION['news_success']); }
if (!empty($_SESSION['news_error'])) { $errors[] = $_SESSION['news_error']; unset($_SESSION['news_error']); }

// --- Handle Admin Actions ---
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verifyCSRFToken') && !verifyCSRFToken()) {
        $_SESSION['news_error'] = 'Invalid security token. Please try again.';
        header('Location: news.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $news_id = (int)($_POST['news_id'] ?? 0);

        if (!$title) $errors[] = 'Title is required.';
        if (!$content) $errors[] = 'Content is required.';

        if (empty($errors)) {
            $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($title)));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            if (!$slug) $slug = 'news-' . time();

            // Handle featured image upload
            $featuredImage = '';
            if (!empty($_FILES['featured_image']['name'])) {
                $uploadDir = __DIR__ . '/newsUploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed)) {
                    $fname = 'news_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $uploadDir . $fname)) {
                        $featuredImage = 'newsUploads/' . $fname;
                    }
                }
            }

            // Handle editor image uploads (from Summernote)
            $allContent = $content;
            if (!empty($_FILES['editor_images'])) {
                $files = $_FILES['editor_images'];
                $fileCount = is_array($files['name']) ? count($files['name']) : ($files['name'] ? 1 : 0);
                for ($i = 0; $i < $fileCount; $i++) {
                    $fname = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                    $ftmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                    $ferr = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                    if ($ferr === 0) {
                        $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed)) {
                            $nfname = 'editor_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                            move_uploaded_file($ftmp, $uploadDir . $nfname);
                        }
                    }
                }
            }

            $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;
            $authorName = $user['full_name'] ?? '';
            $authorRole = $user['role'] ?? '';

            if ($action === 'create') {
                // Insert into staff DB
                $stmt = $staffConn->prepare("INSERT INTO director_news (title, slug, content, excerpt, featured_image, author_id, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssssiss", $title, $slug, $allContent, $excerpt, $featuredImage, $_SESSION['user_id'], $status, $published_at);
                    if ($stmt->execute()) {
                        $newsId = $stmt->insert_id;
                        // Also insert into website DB for public display
                        if ($websiteConn && $ws = $websiteConn->prepare("INSERT INTO news (title, slug, content, excerpt, featured_image, author_id, author_name, author_role, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
                            $ws->bind_param("sssssissss", $title, $slug, $allContent, $excerpt, $featuredImage, $_SESSION['user_id'], $authorName, $authorRole, $status, $published_at);
                            if (!$ws->execute()) { error_log('$ws execute failed: ' . ($ws->error ?? 'unknown')); };
                            $ws->close();
                        }
                        if ($status === 'published' && function_exists('createNotification') && function_exists('notifyAllStaff')) {
                            $nid = createNotification('New News: ' . $title, mb_substr(strip_tags($content), 0, 200), 'news.php', 'news', 'fas fa-newspaper');
                            if ($nid) notifyAllStaff($nid);
                        }
                        $_SESSION['news_success'] = 'News article created successfully.';
                    } else {
                        $errors[] = 'Database error: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $errors[] = 'Database error: ' . $staffConn->error;
                }
            } else {
                // Update in staff DB
                $stmt = false;
                if ($featuredImage) {
                    $stmt = $staffConn->prepare("UPDATE director_news SET title=?, content=?, excerpt=?, featured_image=?, status=?, published_at=COALESCE(?, published_at) WHERE id=?");
                    if ($stmt) $stmt->bind_param("ssssssi", $title, $allContent, $excerpt, $featuredImage, $status, $published_at, $news_id);
                } else {
                    $stmt = $staffConn->prepare("UPDATE director_news SET title=?, content=?, excerpt=?, status=?, published_at=COALESCE(?, published_at) WHERE id=?");
                    if ($stmt) $stmt->bind_param("sssssi", $title, $allContent, $excerpt, $status, $published_at, $news_id);
                }
                if ($stmt && $stmt->execute()) {
                    // Also update website DB
                    if ($websiteConn) {
                        if ($featuredImage) {
                            $ws = $websiteConn->prepare("UPDATE news SET title=?, content=?, excerpt=?, featured_image=?, status=?, published_at=COALESCE(?, published_at), author_name=?, author_role=? WHERE id=?");
                            if ($ws) { $ws->bind_param("ssssssssi", $title, $allContent, $excerpt, $featuredImage, $status, $published_at, $authorName, $authorRole, $news_id); if (!$ws->execute()) { error_log('$ws execute failed: ' . ($ws->error ?? 'unknown')); }; $ws->close(); }
                        } else {
                            $ws = $websiteConn->prepare("UPDATE news SET title=?, content=?, excerpt=?, status=?, published_at=COALESCE(?, published_at), author_name=?, author_role=? WHERE id=?");
                            if ($ws) { $ws->bind_param("sssssssi", $title, $allContent, $excerpt, $status, $published_at, $authorName, $authorRole, $news_id); if (!$ws->execute()) { error_log('$ws execute failed: ' . ($ws->error ?? 'unknown')); }; $ws->close(); }
                        }
                    }
                    if ($status === 'published' && function_exists('createNotification') && function_exists('notifyAllStaff')) {
                        $nid = createNotification('News Updated: ' . $title, mb_substr(strip_tags($content), 0, 200), 'news.php', 'news', 'fas fa-newspaper');
                        if ($nid) notifyAllStaff($nid);
                    }
                    $_SESSION['news_success'] = 'News article updated successfully.';
                } elseif ($stmt) {
                    $errors[] = 'Database error: ' . $stmt->error;
                }
                if ($stmt) $stmt->close();
            }

            header('Location: news.php');
            exit;
        }
    }

    if ($action === 'delete') {
        $news_id = (int)($_POST['news_id'] ?? 0);
        if ($news_id) {
            $stmt = $staffConn->prepare("DELETE FROM director_news WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("i", $news_id);
                if ($stmt->execute()) {
                    if ($websiteConn && $ws = $websiteConn->prepare("DELETE FROM news WHERE id=?")) {
                        $ws->bind_param("i", $news_id);
                        if (!$ws->execute()) { error_log('$ws execute failed: ' . ($ws->error ?? 'unknown')); };
                        $ws->close();
                    }
                    $_SESSION['news_success'] = 'News article deleted.';
                }
                $stmt->close();
            }
        }
        header('Location: news.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $news_id = (int)($_POST['news_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if ($news_id && in_array($newStatus, ['draft', 'published', 'archived'])) {
            $pubAt = ($newStatus === 'published') ? date('Y-m-d H:i:s') : null;
            $stmt = $staffConn->prepare("UPDATE director_news SET status=?, published_at=COALESCE(?, published_at) WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("ssi", $newStatus, $pubAt, $news_id);
                if ($stmt->execute()) {
                    if ($websiteConn && $ws = $websiteConn->prepare("UPDATE news SET status=?, published_at=COALESCE(?, published_at) WHERE id=?")) {
                        $ws->bind_param("ssi", $newStatus, $pubAt, $news_id);
                        if (!$ws->execute()) { error_log('$ws execute failed: ' . ($ws->error ?? 'unknown')); };
                        $ws->close();
                    }
                    $_SESSION['news_success'] = 'News status updated to ' . $newStatus . '.';
                }
                $stmt->close();
            }
        }
        header('Location: news.php');
        exit;
    }

    // Handle Summernote image upload
    if ($action === 'upload_image') {
        header('Content-Type: application/json');
        if (!empty($_FILES['file']['name'])) {
            $uploadDir = __DIR__ . '/newsUploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $fname = 'editor_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $fname)) {
                    echo json_encode(['url' => 'newsUploads/' . $fname]);
                    exit;
                }
            }
        }
        echo json_encode(['error' => 'Upload failed']);
        exit;
    }
}

// Get news articles for display
$newsList = [];
$singleNews = null;
$view = $_GET['view'] ?? 'list';
$slug = $_GET['slug'] ?? '';

if ($view === 'single' && $slug) {
    // Try staff DB first, then website DB
    $s = $staffConn->prepare("SELECT n.*, s.full_name as author_name, s.position as author_role FROM director_news n LEFT JOIN staff s ON n.author_id=s.id WHERE n.slug=? AND n.status='published' LIMIT 1");
    if (!$s) {
        $s = $staffConn->prepare("SELECT * FROM director_news WHERE slug=? AND status='published' LIMIT 1");
    }
    if ($s) {
        $s->bind_param("s", $slug);
        if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
        $result = $s->get_result();
        if ($result && $result->num_rows > 0) {
            $singleNews = $result->fetch_assoc();
        }
        $s->close();
    }
    // Track the view
    if ($singleNews) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $viewerId = $user['id'] ?? null;
        $viewerType = $is_admin ? 'staff' : 'public';
        $stmtV = $staffConn->prepare("INSERT INTO news_views (news_id, user_id, user_type, ip_address, viewed_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmtV) {
            $stmtV->bind_param('iiss', $singleNews['id'], $viewerId, $viewerType, $ip);
            if (!$stmtV->execute()) { error_log('$stmtV execute failed: ' . ($stmtV->error ?? 'unknown')); };
            $stmtV->close();
        }
    }
} elseif ($view === 'list') {
    if ($is_admin) {
        // Admin sees all
        $result = $staffConn->query("SELECT n.*, s.full_name as author_name, s.position as author_role FROM director_news n LEFT JOIN staff s ON n.author_id=s.id ORDER BY n.created_at DESC");
    } else {
        // Public sees only published
        $result = $staffConn->query("SELECT n.*, s.full_name as author_name, s.position as author_role FROM director_news n LEFT JOIN staff s ON n.author_id=s.id WHERE n.status='published' ORDER BY n.published_at DESC");
    }
    if ($result) {
        while ($row = $result->fetch_assoc()) $newsList[] = $row;
    }
}

$pageTitle = ($singleNews ? htmlspecialchars($singleNews['title'] ?? '') . ' | ISNM News' : 'News | ISNM');
include 'shared/_header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.css" rel="stylesheet">
<style>
  /* Prevent FOUC for Summernote */
  #summernote { display: block; min-height: 200px; }
  .note-editor { border-radius: 8px; }
</style>
<?php if ($is_admin): ?>
<div class="admin-bar">
    <div class="container d-flex justify-content-between align-items-center">
        <span><i class="fas fa-user-shield me-2"></i>Admin: <?= htmlspecialchars($user['full_name'] ?? '') ?> (<?= htmlspecialchars($user['role'] ?? '') ?>)</span>
        <div>
            <a href="news.php" class="me-3"><i class="fas fa-newspaper me-1"></i>All News</a>
            <a href="javascript:void(0)" onclick="showCreateForm()" class="me-3"><i class="fas fa-plus-circle me-1"></i>New Article</a>
            <a href="index.php"><i class="fas fa-home me-1"></i>Homepage</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($view === 'single' && $singleNews): ?>
    <!-- Single Article View -->
    <div class="container py-4">
        <a href="news.php" class="btn btn-sm btn-outline-isnm mb-3"><i class="fas fa-arrow-left me-1"></i>Back to News</a>
        <article class="single-article">
            <?php if ($singleNews['featured_image']): ?>
            <img src="<?= htmlspecialchars($singleNews['featured_image']) ?>" alt="<?= htmlspecialchars($singleNews['title']) ?>" class="featured-img">
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <h1><?= htmlspecialchars($singleNews['title']) ?></h1>
                <?php if ($is_admin): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="editNews(<?= $singleNews['id'] ?>)"><i class="fas fa-edit me-2"></i>Edit</a></li>
                        <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteNews(<?= $singleNews['id'] ?>)"><i class="fas fa-trash me-2"></i>Delete</a></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <div class="meta">
                <i class="far fa-calendar me-1"></i><?= date('F j, Y', strtotime($singleNews['published_at'] ?? $singleNews['created_at'])) ?>
                <?php if ($singleNews['author_name']): ?>
                &nbsp;|&nbsp;<i class="far fa-user me-1"></i><?= htmlspecialchars($singleNews['author_name']) ?>
                <?php if ($singleNews['author_role']): ?> (<?= htmlspecialchars($singleNews['author_role']) ?>)<?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="content"><?= $singleNews['content'] ?></div>
        </article>
    </div>
<?php else: ?>
    <!-- News Listing / Admin View -->
    <div class="hero-section-news animate-on-scroll">
        <div class="container">
            <span class="tag tag-primary"><i class="fas fa-newspaper"></i> Latest News</span>
            <h1><i class="fas fa-newspaper me-2"></i>ISNM News</h1>
            <div class="section-divider section-divider-center"></div>
            <p>Latest updates, announcements, and stories from Iganga School of Nursing &amp; Midwifery</p>
        </div>
    </div>

    <nav aria-label="breadcrumb" class="container mt-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">News</li>
      </ol>
    </nav>

    <div class="container py-4 animate-on-scroll">
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($e) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endforeach; ?>

        <?php if ($is_admin): ?>
        <!-- Admin: Create / Edit Form (hidden by default) -->
        <div id="newsFormContainer" class="card mb-4" style="display:none">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="formTitle"><i class="fas fa-plus-circle me-2"></i>Create News Article</h5>
                <button type="button" class="btn-close btn-close-white" onclick="hideForm()"></button>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="newsForm">
                    <?php if (function_exists('generateCSRFToken')): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="news_id" id="newsId" value="0">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Title *</label>
                            <input type="text" name="title" id="newsTitle" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Content *</label>
                            <textarea name="content" id="summernote" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Excerpt (short summary)</label>
                            <textarea name="excerpt" id="newsExcerpt" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Featured Image</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/*">
                            <div class="form-text">Optional. Recommended: 1200x630px.</div>
                            <div id="currentImage" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-isnm"><i class="fas fa-save me-1"></i>Save Article</button>
                        <button type="button" class="btn btn-secondary" onclick="hideForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Admin: Stats bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <span class="badge bg-primary me-1">Total: <?= count($newsList) ?></span>
                <span class="badge bg-success me-1">Published: <?= count(array_filter($newsList, fn($n) => $n['status'] === 'published')) ?></span>
                <span class="badge bg-warning text-dark me-1">Draft: <?= count(array_filter($newsList, fn($n) => $n['status'] === 'draft')) ?></span>
                <span class="badge bg-secondary">Archived: <?= count(array_filter($newsList, fn($n) => $n['status'] === 'archived')) ?></span>
            </div>
            <button class="btn btn-isnm btn-sm" onclick="showCreateForm()"><i class="fas fa-plus me-1"></i>New Article</button>
        </div>
        <?php endif; ?>

        <!-- News Grid -->
        <?php if (empty($newsList)): ?>
        <div class="empty-state">
            <i class="far fa-newspaper"></i>
            <h4>No news articles yet</h4>
            <p class="text-muted"><?= $is_admin ? 'Click "New Article" to publish the first news item.' : 'Check back later for updates.' ?></p>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php $cardIndex = 0; foreach ($newsList as $article): $cardIndex++; $delayClass = 'animate-delay-' . min($cardIndex, 5); ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="news-card animate-on-scroll <?= $delayClass ?>">
                    <?php if ($article['featured_image']): ?>
                    <img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="news-card-img" loading="lazy">
                    <?php else: ?>
                    <div class="news-card-img d-flex align-items-center justify-content-center bg-light text-muted"><i class="far fa-image fa-3x"></i></div>
                    <?php endif; ?>
                    <div class="news-card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="date"><i class="far fa-calendar me-1"></i><?= date('M j, Y', strtotime($article['published_at'] ?? $article['created_at'])) ?></span>
                            <?php if ($is_admin): ?>
                            <span class="badge bg-<?= $article['status'] === 'published' ? 'success' : ($article['status'] === 'draft' ? 'warning text-dark' : 'secondary') ?>"><?= $article['status'] ?></span>
                            <?php endif; ?>
                        </div>
                        <h5><a href="news.php?view=single&slug=<?= urlencode($article['slug']) ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($article['title']) ?></a></h5>
                        <?php if ($article['excerpt']): ?>
                        <p class="excerpt"><?= htmlspecialchars(substr($article['excerpt'], 0, 120)) ?><?= strlen($article['excerpt']) > 120 ? '...' : '' ?></p>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <?php if ($article['author_name']): ?>
                            <span class="author"><i class="far fa-user me-1"></i><?= htmlspecialchars($article['author_name']) ?></span>
                            <?php endif; ?>
                            <a href="news.php?view=single&slug=<?= urlencode($article['slug']) ?>" class="btn btn-sm btn-outline-isnm">Read More <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <?php if ($is_admin): ?>
                        <div class="mt-2 pt-2 border-top d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="editNews(<?= $article['id'] ?>)"><i class="fas fa-edit"></i></button>
                            <?php if ($article['status'] !== 'published'): ?>
                            <form method="POST" class="d-inline">
                                <?php if (function_exists('generateCSRFToken')): ?><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>"><?php endif; ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="news_id" value="<?= $article['id'] ?>">
                                <input type="hidden" name="new_status" value="published">
                                <button class="btn btn-sm btn-outline-success" title="Publish"><i class="fas fa-check"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($article['status'] !== 'archived'): ?>
                            <form method="POST" class="d-inline">
                                <?php if (function_exists('generateCSRFToken')): ?><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>"><?php endif; ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="news_id" value="<?= $article['id'] ?>">
                                <input type="hidden" name="new_status" value="archived">
                                <button class="btn btn-sm btn-outline-secondary" title="Archive"><i class="fas fa-archive"></i></button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this article?')">
                                <?php if (function_exists('generateCSRFToken')): ?><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>"><?php endif; ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="news_id" value="<?= $article['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
if (typeof jQuery === 'undefined') {
    document.write('<script src="https://code.jquery.com/jquery-3.7.1.min.js"><\/script>');
}
</script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.js"></script>
<script>
let newsData = <?= json_encode($newsList) ?>;

function initSummernote() {
    var el = $('#summernote');
    if (el.length && typeof $.fn.summernote === 'function') {
        el.summernote({
            height: 350,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    for (var i = 0; i < files.length; i++) {
                        uploadEditorImage(files[i]);
                    }
                }
            }
        });
    }
}

$(document).ready(function() {
    initSummernote();
});

function snCode(html) {
    var el = $('#summernote');
    if (el.length && typeof $.fn.summernote === 'function') {
        el.summernote('code', html || '');
    } else {
        el.val(html || '');
    }
}

function uploadEditorImage(file) {
    var formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'upload_image');
    $.ajax({
        url: 'news.php',
        method: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            if (res.url && typeof $.fn.summernote === 'function') {
                $('#summernote').summernote('insertImage', res.url);
            }
        }
    });
}

function showCreateForm() {
    $('#formTitle').html('<i class="fas fa-plus-circle me-2"></i>Create News Article');
    $('#formAction').val('create');
    $('#newsId').val(0);
    $('#newsTitle').val('');
    $('#newsExcerpt').val('');
    $('#currentImage').html('');
    snCode('');
    $('#newsFormContainer').show();
    window.scrollTo({ top: $('#newsFormContainer').offset().top - 20, behavior: 'smooth' });
}

function hideForm() {
    $('#newsFormContainer').hide();
}

function editNews(id) {
    var article = newsData.find(function(n) { return n.id == id; });
    if (!article) return;
    $('#formTitle').html('<i class="fas fa-edit me-2"></i>Edit News Article');
    $('#formAction').val('update');
    $('#newsId').val(article.id);
    $('#newsTitle').val(article.title);
    $('#newsExcerpt').val(article.excerpt || '');
    snCode(article.content || '');
    if (article.featured_image) {
        $('#currentImage').html('<img src="' + article.featured_image + '" style="max-height:80px;border-radius:6px"> <span class="text-muted small">Current image</span>');
    } else {
        $('#currentImage').html('');
    }
    $('select[name="status"]').val(article.status);
    $('#newsFormContainer').show();
    window.scrollTo({ top: $('#newsFormContainer').offset().top - 20, behavior: 'smooth' });
}

function deleteNews(id) {
    if (!confirm('Delete this news article?')) return;
    let form = $('<form method="POST">').append(
        '<input type="hidden" name="action" value="delete">',
        '<input type="hidden" name="news_id" value="' + id + '">'
    );
    $('body').append(form);
    form.submit();
}
</script>
<?php include 'shared/_footer.php'; ?>
