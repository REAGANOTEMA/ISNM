<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';

session_start();

$user = null;
$is_admin = false;

// Check for authenticated director
if (isset($_SESSION['user_id']) && isset($_SESSION['type']) && $_SESSION['type'] === 'staff') {
    $auth = new AuthenticationService();
    if ($auth->isAuthenticated()) {
        $user = $auth->getCurrentUser();
        $directorRoles = ['director general', 'ceo', 'school principal', 'director academics', 'director finance', 'director ict', 'deputy principal', 'director admissions'];
        $userRole = strtolower($user['role'] ?? '');
        foreach ($directorRoles as $dr) {
            if (stripos($userRole, $dr) !== false) { $is_admin = true; break; }
        }
    }
}

$staffConn = getStaffConnection();
$websiteConn = getWebsiteConnection();
$errors = [];
$success = '';

// Get success/error from session
if (!empty($_SESSION['news_success'])) { $success = $_SESSION['news_success']; unset($_SESSION['news_success']); }
if (!empty($_SESSION['news_error'])) { $errors[] = $_SESSION['news_error']; unset($_SESSION['news_error']); }

// --- Handle Admin Actions ---
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $stmt->bind_param("sssssiss", $title, $slug, $allContent, $excerpt, $featuredImage, $_SESSION['user_id'], $status, $published_at);
                if ($stmt->execute()) {
                    $newsId = $stmt->insert_id;
                    // Also insert into website DB for public display
                    $ws = $websiteConn->prepare("INSERT INTO news (title, slug, content, excerpt, featured_image, author_id, author_name, author_role, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $ws->bind_param("sssssissss", $title, $slug, $allContent, $excerpt, $featuredImage, $_SESSION['user_id'], $authorName, $authorRole, $status, $published_at);
                    $ws->execute();
                    $ws->close();
                    $_SESSION['news_success'] = 'News article created successfully.';
                } else {
                    $errors[] = 'Database error: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                // Update in staff DB
                if ($featuredImage) {
                    $stmt = $staffConn->prepare("UPDATE director_news SET title=?, content=?, excerpt=?, featured_image=?, status=?, published_at=COALESCE(?, published_at) WHERE id=?");
                    $stmt->bind_param("ssssssi", $title, $allContent, $excerpt, $featuredImage, $status, $published_at, $news_id);
                } else {
                    $stmt = $staffConn->prepare("UPDATE director_news SET title=?, content=?, excerpt=?, status=?, published_at=COALESCE(?, published_at) WHERE id=?");
                    $stmt->bind_param("sssssi", $title, $allContent, $excerpt, $status, $published_at, $news_id);
                }
                if ($stmt->execute()) {
                    // Also update website DB
                    if ($featuredImage) {
                        $ws = $websiteConn->prepare("UPDATE news SET title=?, content=?, excerpt=?, featured_image=?, status=?, published_at=COALESCE(?, published_at), author_name=?, author_role=? WHERE id=?");
                        $ws->bind_param("ssssssssi", $title, $allContent, $excerpt, $featuredImage, $status, $published_at, $authorName, $authorRole, $news_id);
                    } else {
                        $ws = $websiteConn->prepare("UPDATE news SET title=?, content=?, excerpt=?, status=?, published_at=COALESCE(?, published_at), author_name=?, author_role=? WHERE id=?");
                        $ws->bind_param("sssssssi", $title, $allContent, $excerpt, $status, $published_at, $authorName, $authorRole, $news_id);
                    }
                    $ws->execute();
                    $ws->close();
                    $_SESSION['news_success'] = 'News article updated successfully.';
                } else {
                    $errors[] = 'Database error: ' . $stmt->error;
                }
                $stmt->close();
            }

            header('Location: news.php');
            exit;
        }
    }

    if ($action === 'delete') {
        $news_id = (int)($_POST['news_id'] ?? 0);
        if ($news_id) {
            $stmt = $staffConn->prepare("DELETE FROM director_news WHERE id=?");
            $stmt->bind_param("i", $news_id);
            if ($stmt->execute()) {
                $ws = $websiteConn->prepare("DELETE FROM news WHERE id=?");
                $ws->bind_param("i", $news_id);
                $ws->execute();
                $ws->close();
                $_SESSION['news_success'] = 'News article deleted.';
            }
            $stmt->close();
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
            $stmt->bind_param("ssi", $newStatus, $pubAt, $news_id);
            if ($stmt->execute()) {
                $ws = $websiteConn->prepare("UPDATE news SET status=?, published_at=COALESCE(?, published_at) WHERE id=?");
                $ws->bind_param("ssi", $newStatus, $pubAt, $news_id);
                $ws->execute();
                $ws->close();
                $_SESSION['news_success'] = 'News status updated to ' . $newStatus . '.';
            }
            $stmt->close();
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
    $s->bind_param("s", $slug);
    $s->execute();
    $result = $s->get_result();
    if ($result && $result->num_rows > 0) {
        $singleNews = $result->fetch_assoc();
    }
    $s->close();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $singleNews ? htmlspecialchars($singleNews['title']) . ' - ' : '' ?>News - ISNM</title>
<?php include_once __DIR__ . '/includes/_favicon.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.css" rel="stylesheet">
<style>
:root {
    --primary: #1a237e;
    --primary-light: #3949ab;
    --accent: #ffd700;
    --success: #2e7d32;
}
body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: #f0f2f5;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.navbar-isnm {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
}
.navbar-isnm .navbar-brand {
    color: #fff;
    font-weight: 700;
}
.navbar-isnm .nav-link { color: rgba(255,255,255,0.85); }
.navbar-isnm .nav-link:hover { color: var(--accent); }
.hero-section {
    background: linear-gradient(135deg, var(--primary), #283593);
    color: #fff;
    padding: 50px 0 40px;
    text-align: center;
}
.hero-section h1 { font-size: 2.2rem; font-weight: 800; }
.hero-section p { color: rgba(255,255,255,0.8); font-size: 1.05rem; }
.news-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    transition: transform .25s, box-shadow .25s;
    height: 100%;
}
.news-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
.news-card-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}
.news-card-body { padding: 18px; }
.news-card-body .date { font-size: .8rem; color: #888; }
.news-card-body h5 { font-weight: 700; margin: 6px 0; }
.news-card-body .excerpt { color: #555; font-size: .9rem; }
.news-card-body .author { font-size: .82rem; color: var(--primary-light); }

.single-article {
    background: #fff;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    max-width: 900px;
    margin: 0 auto;
}
.single-article h1 { font-weight: 800; font-size: 1.8rem; }
.single-article .meta { color: #888; font-size: .9rem; margin-bottom: 20px; }
.single-article .featured-img {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 20px;
}
.single-article .content { line-height: 1.8; font-size: 1.05rem; }
.single-article .content img { max-width: 100%; height: auto; border-radius: 8px; }

.admin-bar {
    background: #263238;
    color: #fff;
    padding: 10px 0;
    font-size: .85rem;
}
.admin-bar a { color: var(--accent); text-decoration: none; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #888;
}
.empty-state i { font-size: 3rem; margin-bottom: 15px; }

.btn-isnm { background: var(--primary); color: #fff; border: none; }
.btn-isnm:hover { background: var(--primary-light); color: #fff; }
.btn-outline-isnm { color: var(--primary); border-color: var(--primary); }
.btn-outline-isnm:hover { background: var(--primary); color: #fff; }

@media (max-width: 768px) {
    .hero-section h1 { font-size: 1.5rem; }
    .hero-section { padding: 30px 0 25px; }
    .single-article { padding: 18px; }
    .single-article h1 { font-size: 1.3rem; }
}

footer {
    background: var(--primary);
    color: rgba(255,255,255,0.8);
    text-align: center;
    padding: 18px;
    margin-top: auto;
    font-size: .85rem;
}
footer a { color: var(--accent); text-decoration: none; }
</style>
</head>
<body>

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

<nav class="navbar navbar-expand-lg navbar-isnm">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-school me-2"></i>ISNM</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="programs.php">Programs</a></li>
                <li class="nav-item"><a class="nav-link active" href="news.php">News</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                <?php if ($is_admin): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars(explode(' ', $user['full_name'] ?? 'Admin')[0]) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="news.php"><i class="fas fa-newspaper me-2"></i>Manage News</a></li>
                        <li><a class="dropdown-item" href="dashboards/<?= basename($user['role'] ?? 'director-general') ?>.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="staff-login.php"><i class="fas fa-sign-in-alt me-1"></i>Staff Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

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
    <div class="hero-section">
        <div class="container">
            <h1><i class="fas fa-newspaper me-2"></i>ISNM News</h1>
            <p>Latest updates, announcements, and stories from Iganga School of Nursing &amp; Midwifery</p>
        </div>
    </div>

    <div class="container py-4">
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
            <?php foreach ($newsList as $article): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="news-card">
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
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="news_id" value="<?= $article['id'] ?>">
                                <input type="hidden" name="new_status" value="published">
                                <button class="btn btn-sm btn-outline-success" title="Publish"><i class="fas fa-check"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($article['status'] !== 'archived'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="news_id" value="<?= $article['id'] ?>">
                                <input type="hidden" name="new_status" value="archived">
                                <button class="btn btn-sm btn-outline-secondary" title="Archive"><i class="fas fa-archive"></i></button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this article?')">
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

<footer>
    <div class="container">
        <a href="index.php"><i class="fas fa-home me-1"></i>Back to Homepage</a>
        <span class="mx-2">|</span>
        <span>&copy; <?= date('Y') ?> Iganga School of Nursing &amp; Midwifery</span>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.js"></script>
<script>
let newsData = <?= json_encode($newsList) ?>;

$(document).ready(function() {
    $('#summernote').summernote({
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
                for (let i = 0; i < files.length; i++) {
                    uploadEditorImage(files[i]);
                }
            }
        }
    });
});

function uploadEditorImage(file) {
    let formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'upload_image');
    $.ajax({
        url: 'news.php',
        method: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            if (res.url) {
                $('#summernote').summernote('insertImage', res.url);
            }
        }
    });
}

function showCreateForm() {
    $('#formTitle').html('<i class=\"fas fa-plus-circle me-2\"></i>Create News Article');
    $('#formAction').val('create');
    $('#newsId').val(0);
    $('#newsTitle').val('');
    $('#newsExcerpt').val('');
    $('#currentImage').html('');
    $('#summernote').summernote('code', '');
    $('#newsFormContainer').show();
    window.scrollTo({ top: $('#newsFormContainer').offset().top - 20, behavior: 'smooth' });
}

function hideForm() {
    $('#newsFormContainer').hide();
}

function editNews(id) {
    let article = newsData.find(n => n.id == id);
    if (!article) return;
    $('#formTitle').html('<i class=\"fas fa-edit me-2\"></i>Edit News Article');
    $('#formAction').val('update');
    $('#newsId').val(article.id);
    $('#newsTitle').val(article.title);
    $('#newsExcerpt').val(article.excerpt || '');
    $('#summernote').summernote('code', article.content || '');
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
</body>
</html>
