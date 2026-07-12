<?php
/**
 * Unified News Management Widget
 * Embed in any staff dashboard via renderNewsWidget($staff_conn, $website_conn, $user_id, $user_name, $user_role)
 * Handles POST submission inline, inserts into director_news (staff DB) + syncs to news (website DB).
 */

if (!function_exists('ensureNewsTable')) {
    function ensureNewsTable($website_conn) {
        if (!$website_conn) return false;
        $r = $website_conn->query("SHOW TABLES LIKE 'news'");
        if ($r && $r->num_rows > 0) return true;
        $website_conn->query("CREATE TABLE IF NOT EXISTS news (
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

if (!function_exists('renderNewsWidget')) {
function renderNewsWidget($staff_conn, $website_conn, $user_id, $user_name, $user_role, $limit = 6) {
    $user_id   = (int)$user_id;
    $user_name = htmlspecialchars($user_name ?? 'Staff');
    $user_role = htmlspecialchars($user_role ?? '');

    // Ensure news table exists in website DB
    ensureNewsTable($website_conn);

    // ---- POST: create or update news ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nw_action'])) {
        if (function_exists('verifyCSRFToken') && !verifyCSRFToken()) {
            $_SESSION['nw_error'] = 'Invalid security token. Please try again.';
            echo '<meta http-equiv="refresh" content="0">';
            return;
        }

        $action   = $_POST['nw_action'];
        $title    = trim($_POST['nw_title'] ?? '');
        $content  = trim($_POST['nw_content'] ?? '');
        $excerpt  = trim($_POST['nw_excerpt'] ?? '');
        $status   = $_POST['nw_status'] ?? 'draft';
        $news_id  = (int)($_POST['nw_id'] ?? 0);

        if ($title && $content && in_array($action, ['create','update'])) {
            $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($title)));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-') ?: 'news-' . time();

            // Handle featured image upload
            $featuredImage = '';
            if (!empty($_FILES['nw_image']['name']) && $_FILES['nw_image']['error'] === 0) {
                $uploadDir = __DIR__ . '/../newsUploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['nw_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $fname = 'nw_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['nw_image']['tmp_name'], $uploadDir . $fname)) {
                        $featuredImage = 'newsUploads/' . $fname;
                    }
                }
            }

            $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;

            if ($staff_conn) {
                if ($action === 'create') {
                    $stmt = $staff_conn->prepare("INSERT INTO director_news (title,slug,content,excerpt,featured_image,author_id,status,published_at,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
                    if ($stmt) {
                        $stmt->bind_param('sssssiss', $title, $slug, $content, $excerpt, $featuredImage, $user_id, $status, $published_at);
                        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                        $newId = $stmt->insert_id;
                        $stmt->close();

                        // Sync to website DB
                        if ($website_conn && $newId) {
                            $ws = $website_conn->prepare("INSERT INTO news (id,title,slug,content,excerpt,featured_image,author_id,author_name,author_role,status,published_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
                            if ($ws) {
                                $ws->bind_param('isssssissss', $newId, $title, $slug, $content, $excerpt, $featuredImage, $user_id, $user_name, $user_role, $status, $published_at);
                                if (!$ws->execute()) { error_log('$ws execute failed: ' . ($ws->error ?? 'unknown')); };
                                $ws->close();
                            }
                        }
                        if ($status === 'published' && function_exists('createNotification') && function_exists('notifyAllStaff')) {
                            $nid = createNotification('New News: ' . $title, mb_substr(strip_tags($content), 0, 200), 'news.php', 'news', 'fas fa-newspaper');
                            if ($nid) notifyAllStaff($nid);
                        }
                        $_SESSION['nw_success'] = 'News article published.';
                    }
                } else {
                    $stmt = $staff_conn->prepare("UPDATE director_news SET title=?, content=?, excerpt=?, status=?, published_at=COALESCE(?,published_at) " . ($featuredImage ? ",featured_image=?" : "") . " WHERE id=?");
                    if ($stmt) {
                        if ($featuredImage) {
                            $stmt->bind_param('ssssssi', $title, $content, $excerpt, $status, $published_at, $featuredImage, $news_id);
                        } else {
                            $stmt->bind_param('sssssi', $title, $content, $excerpt, $status, $published_at, $news_id);
                        }
                        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                        $stmt->close();

                        if ($website_conn) {
                            $ws = $website_conn->prepare("UPDATE news SET title=?, content=?, excerpt=?, status=?, published_at=COALESCE(?,published_at), author_name=?, author_role=? WHERE id=?");
                            if ($ws) {
                                $ws->bind_param('sssssssi', $title, $content, $excerpt, $status, $published_at, $user_name, $user_role, $news_id);
                                if (!$ws->execute()) { error_log('$ws execute failed: ' . ($ws->error ?? 'unknown')); };
                                $ws->close();
                            }
                        }
                        if ($status === 'published' && function_exists('createNotification') && function_exists('notifyAllStaff')) {
                            $nid = createNotification('News Updated: ' . $title, mb_substr(strip_tags($content), 0, 200), 'news.php', 'news', 'fas fa-newspaper');
                            if ($nid) notifyAllStaff($nid);
                        }
                        $_SESSION['nw_success'] = 'News article updated.';
                    }
                }
            }
        }

        // POST: toggle status
        if ($action === 'toggle_status' && $news_id) {
            $newStatus = $_POST['nw_new_status'] ?? '';
            if (in_array($newStatus, ['draft','published','archived']) && $staff_conn) {
                $pubAt = ($newStatus === 'published') ? date('Y-m-d H:i:s') : null;
                $stmt = $staff_conn->prepare("UPDATE director_news SET status=?, published_at=COALESCE(?,published_at) WHERE id=?");
                if ($stmt) {
                    $stmt->bind_param('ssi', $newStatus, $pubAt, $news_id);
                    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                    $stmt->close();

                    if ($website_conn) {
                        $ws = $website_conn->prepare("UPDATE news SET status=?, published_at=COALESCE(?,published_at) WHERE id=?");
                        if ($ws) {
                            $ws->bind_param('ssi', $newStatus, $pubAt, $news_id);
                            if (!$ws->execute()) { error_log('$ws execute failed: ' . ($ws->error ?? 'unknown')); };
                            $ws->close();
                        }
                    }
                    $_SESSION['nw_success'] = "Status changed to $newStatus.";
                }
            }
        }

        // POST: delete
        if ($action === 'delete' && $news_id && $staff_conn) {
            $stmt = $staff_conn->prepare("DELETE FROM director_news WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('i', $news_id);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
                $stmt->close();
                if ($website_conn) {
                    $ws = $website_conn->prepare("DELETE FROM news WHERE id=?");
                    if ($ws) { $ws->bind_param('i', $news_id); if (!$ws->execute()) { error_log('$ws execute failed: ' . ($ws->error ?? 'unknown')); }; $ws->close(); }
                }
                $_SESSION['nw_success'] = 'News deleted.';
            }
        }

        echo '<meta http-equiv="refresh" content="0">';
        return;
    }

    // ---- Fetch news ----
    $news = [];
    if ($staff_conn) {
        $stmt = $staff_conn->prepare("SELECT n.*, s.full_name AS author_name, s.position AS author_role FROM director_news n LEFT JOIN staff s ON n.author_id=s.id ORDER BY n.created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $r = $stmt->get_result();
        if ($r) while ($row = $r->fetch_assoc()) $news[] = $row;
    }

    // ---- Messages ----
    $nw_success = $_SESSION['nw_success'] ?? ''; unset($_SESSION['nw_success']);
    $nw_error   = $_SESSION['nw_error'] ?? '';   unset($_SESSION['nw_error']);

    // ---- Render ----
    ?>
    <div class="news-widget">
        <?php if ($nw_success): ?>
        <div class="alert alert-success alert-dismissible fade show py-2"><?= htmlspecialchars($nw_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($nw_error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2"><?= htmlspecialchars($nw_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Create button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-newspaper me-2"></i>News &amp; Announcements</h5>
            <button class="btn btn-sm btn-primary" onclick="toggleNewsForm()"><i class="fas fa-plus me-1"></i>New Article</button>
        </div>

        <!-- Create/Edit Form -->
        <div id="nwForm" class="card border mb-3" style="display:none">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold" id="nwFormTitle"><i class="fas fa-plus-circle me-1"></i>Create News Article</span>
                <button type="button" class="btn-close btn-close-white" onclick="toggleNewsForm()"></button>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php if (function_exists('generateCSRFToken')): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="nw_action" id="nwAction" value="create">
                    <input type="hidden" name="nw_id" id="nwId" value="0">
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Title *</label>
                            <input type="text" name="nw_title" id="nwTitle" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Status</label>
                            <select name="nw_status" class="form-select form-select-sm">
                                <option value="draft">Draft</option>
                                <option value="published" selected>Published</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Image</label>
                            <input type="file" name="nw_image" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Content *</label>
                            <textarea name="nw_content" id="nwContent" class="form-control form-control-sm" rows="4" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Excerpt (optional short summary)</label>
                            <textarea name="nw_excerpt" id="nwExcerpt" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleNewsForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- News list -->
        <?php if (empty($news)): ?>
        <div class="text-center text-muted py-4">
            <i class="far fa-newspaper fa-2x mb-2"></i>
            <p class="small mb-0">No news articles yet.</p>
        </div>
        <?php else: ?>
        <div class="news-grid" style="max-height:400px;overflow-y:auto">
            <?php foreach ($news as $a):
                $bc = $a['status']==='published'?'bg-success':($a['status']==='draft'?'bg-warning text-dark':'bg-secondary');
            ?>
            <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-semibold small text-truncate"><?= htmlspecialchars($a['title']) ?></div>
                        <div class="text-muted small">
                            <i class="far fa-calendar me-1"></i><?= date('M j, Y', strtotime($a['created_at'])) ?>
                            <?php if ($a['author_name']): ?> &middot; <?= htmlspecialchars($a['author_name']) ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                        <span class="badge <?= $bc ?>"><?= $a['status'] ?></span>
                        <div class="dropdown">
                            <button class="btn btn-xs btn-outline-secondary dropdown-toggle py-0 px-1" style="font-size:11px" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width:120px">
                                <?php if ($a['status'] !== 'published'): ?>
                                <li><form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken() ?? '') ?>"><input type="hidden" name="nw_action" value="toggle_status"><input type="hidden" name="nw_id" value="<?= $a['id'] ?>"><input type="hidden" name="nw_new_status" value="published"><button class="dropdown-item small"><i class="fas fa-check text-success me-2"></i>Publish</button></form></li>
                                <?php endif; ?>
                                <?php if ($a['status'] !== 'draft'): ?>
                                <li><form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken() ?? '') ?>"><input type="hidden" name="nw_action" value="toggle_status"><input type="hidden" name="nw_id" value="<?= $a['id'] ?>"><input type="hidden" name="nw_new_status" value="draft"><button class="dropdown-item small"><i class="fas fa-pen text-warning me-2"></i>Draft</button></form></li>
                                <?php endif; ?>
                                <?php if ($a['status'] !== 'archived'): ?>
                                <li><form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken() ?? '') ?>"><input type="hidden" name="nw_action" value="toggle_status"><input type="hidden" name="nw_id" value="<?= $a['id'] ?>"><input type="hidden" name="nw_new_status" value="archived"><button class="dropdown-item small"><i class="fas fa-archive text-secondary me-2"></i>Archive</button></form></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><button class="dropdown-item small" onclick="editNewsWidget(<?= $a['id'] ?>, '<?= addslashes($a['title']) ?>', '<?= addslashes($a['content']) ?>', '<?= addslashes($a['excerpt'] ?? '') ?>', '<?= $a['status'] ?>')"><i class="fas fa-edit text-primary me-2"></i>Edit</button></li>
                                <li><form method="POST" class="d-inline" onsubmit="return confirm('Delete this article?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken() ?? '') ?>"><input type="hidden" name="nw_action" value="delete"><input type="hidden" name="nw_id" value="<?= $a['id'] ?>"><button class="dropdown-item small"><i class="fas fa-trash text-danger me-2"></i>Delete</button></form></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-2">
            <a href="../news.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i>Full News Manager</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
    var nwData = <?= json_encode($news) ?>;
    function toggleNewsForm() {
        var f = document.getElementById('nwForm');
        f.style.display = f.style.display === 'none' ? 'block' : 'none';
        if (f.style.display === 'block') f.scrollIntoView({behavior:'smooth',block:'nearest'});
    }
    function editNewsWidget(id, title, content, excerpt, status) {
        document.getElementById('nwAction').value = 'update';
        document.getElementById('nwId').value = id;
        document.getElementById('nwTitle').value = title;
        document.getElementById('nwContent').value = content;
        document.getElementById('nwExcerpt').value = excerpt;
        var sel = document.querySelector('select[name="nw_status"]');
        sel.value = status || 'draft';
        document.getElementById('nwFormTitle').innerHTML = '<i class="fas fa-edit me-1"></i>Edit News Article';
        var f = document.getElementById('nwForm');
        f.style.display = 'block';
        f.scrollIntoView({behavior:'smooth',block:'nearest'});
    }
    </script>
    <style>
    .news-widget .btn-xs { padding:0 .25rem; font-size:.75rem; line-height:1.5; }
    .news-widget .min-width-0 { min-width:0; }
    .news-widget .news-grid::-webkit-scrollbar { width:4px; }
    .news-widget .news-grid::-webkit-scrollbar-thumb { background:#ccc; border-radius:2px; }
    </style>
    <?php
}}
