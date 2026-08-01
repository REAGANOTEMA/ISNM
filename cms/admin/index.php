<?php
/**
 * CMS Admin Panel — Content Management Dashboard
 * Accessible by authorized officials to manage website content
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth-service.php';
require_once __DIR__ . '/../../includes/student_helpers.php';
require_once __DIR__ . '/../core/CMS.php';

if (empty($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'staff') {
    header('Location: ../staff-login.php');
    exit();
}

$cms = CMS::getInstance();
$rbac = RBAC::getInstance();
$audit = AuditLog::getInstance();
$userRole = $_SESSION['role'] ?? '';
$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'User';

// Check if user has CMS access
$hasAccess = $rbac->can($userRole, 'manage_all') || $rbac->can($userRole, 'edit_homepage');
if (!$hasAccess) {
    $editablePages = $rbac->getEditablePages($userRole);
    $hasAccess = !empty($editablePages);
}
if (!$hasAccess) {
    echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>Access Denied</h1><p>You do not have permission to access the CMS.</p><a href="../dashboards/' . strtolower(str_replace(' ', '-', $userRole)) . '.php">Return to Dashboard</a></body></html>';
    exit();
}

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
$contentId = (int)($_GET['id'] ?? 0);
$message = $_SESSION['cms_message'] ?? null;
$messageType = $_SESSION['cms_message_type'] ?? 'success';
unset($_SESSION['cms_message'], $_SESSION['cms_message_type']);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save_page') {
        $pageData = [
            'id' => (int)($_POST['page_id'] ?? 0),
            'slug' => trim($_POST['slug'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'subtitle' => trim($_POST['subtitle'] ?? ''),
            'page_type' => $_POST['page_type'] ?? 'static',
            'hero_title' => trim($_POST['hero_title'] ?? ''),
            'hero_subtitle' => trim($_POST['hero_subtitle'] ?? ''),
            'hero_image' => trim($_POST['hero_image'] ?? ''),
            'hero_overlay_color' => trim($_POST['hero_overlay_color'] ?? 'rgba(26,35,126,0.7)'),
            'content' => $_POST['content'] ?? '',
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'og_title' => trim($_POST['og_title'] ?? ''),
            'og_description' => trim($_POST['og_description'] ?? ''),
            'og_image' => trim($_POST['og_image'] ?? ''),
            'canonical_url' => trim($_POST['canonical_url'] ?? ''),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'updated_by' => $userId,
        ];
        $savedId = $cms->savePage($pageData, $userId);
        $_SESSION['cms_message'] = $savedId ? 'Page saved successfully.' : 'Failed to save page.';
        $_SESSION['cms_message_type'] = $savedId ? 'success' : 'danger';
        header('Location: ?page=pages&action=edit&id=' . $savedId);
        exit();
    }

    if ($postAction === 'save_block') {
        $blockData = [
            'id' => (int)($_POST['block_id'] ?? 0),
            'page_id' => (int)($_POST['page_id'] ?? 0),
            'block_key' => trim($_POST['block_key'] ?? ''),
            'block_type' => $_POST['block_type'] ?? 'text',
            'title' => trim($_POST['block_title'] ?? ''),
            'subtitle' => trim($_POST['block_subtitle'] ?? ''),
            'content' => $_POST['block_content'] ?? '',
            'settings' => $_POST['block_settings'] ?? '',
            'animation' => $_POST['animation'] ?? 'fade-up',
            'background_style' => trim($_POST['background_style'] ?? ''),
            'text_color' => trim($_POST['text_color'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
        ];
        $savedId = $cms->saveBlock($blockData, $userId);
        $_SESSION['cms_message'] = $savedId ? 'Content block saved.' : 'Failed to save block.';
        $_SESSION['cms_message_type'] = $savedId ? 'success' : 'danger';
        header('Location: ?page=blocks&action=edit&id=' . $savedId);
        exit();
    }

    if ($postAction === 'save_setting') {
        $key = trim($_POST['setting_key'] ?? '');
        $value = $_POST['setting_value'] ?? '';
        if ($key) {
            $cms->updateSetting($key, $value);
            $_SESSION['cms_message'] = 'Setting updated.';
            $_SESSION['cms_message_type'] = 'success';
        }
        header('Location: ?page=settings');
        exit();
    }

    if ($postAction === 'delete_item') {
        $type = $_POST['item_type'] ?? '';
        $id = (int)($_POST['item_id'] ?? 0);
        if ($type === 'block' && $id) {
            $cms->deleteBlock($id, $userId);
            $_SESSION['cms_message'] = 'Block deleted.';
            $_SESSION['cms_message_type'] = 'success';
        }
        header('Location: ' . ($_POST['redirect'] ?? '?page=dashboard'));
        exit();
    }
}

// Fetch data for different pages
$pages = $cms->getAllPages(true);
$stats = $cms->getPageStats();
$auditEntries = $audit->getEntries([], 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Admin — ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --cms-primary: #1A237E; --cms-gold: #FFD700; --cms-success: #2E7D32; }
        * { font-family: 'Inter', sans-serif; }
        .cms-admin-layout { display: flex; min-height: 100vh; }
        .cms-sidebar { width: 260px; background: var(--cms-primary); color: white; padding: 20px 0; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 100; }
        .cms-sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 10px; }
        .cms-sidebar-header h5 { font-size: 0.85rem; opacity: 0.7; margin-bottom: 5px; }
        .cms-sidebar-header h4 { font-size: 1.1rem; margin: 0; }
        .cms-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.75); text-decoration: none; transition: all 0.2s; font-size: 0.9rem; border-left: 3px solid transparent; }
        .cms-nav-item:hover { background: rgba(255,255,255,0.08); color: white; }
        .cms-nav-item.active { background: rgba(255,255,255,0.12); color: white; border-left-color: var(--cms-gold); }
        .cms-nav-item i { width: 20px; text-align: center; }
        .cms-main { flex: 1; margin-left: 260px; padding: 30px; background: #f0f2f5; }
        .cms-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .cms-page-header h2 { font-size: 1.5rem; font-weight: 700; }
        .cms-stat-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .cms-stat-card .stat-number { font-size: 2rem; font-weight: 700; color: var(--cms-primary); }
        .cms-stat-card .stat-label { font-size: 0.85rem; color: #6B7280; margin-top: 5px; }
        .cms-content-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden; }
        .cms-content-card .card-header { padding: 16px 24px; border-bottom: 1px solid #E4E7EB; display: flex; justify-content: space-between; align-items: center; }
        .cms-content-card .card-body { padding: 24px; }
        .cms-table th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6B7280; font-weight: 600; }
        .cms-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .cms-badge-success { background: #E8F5E9; color: #2E7D32; }
        .cms-badge-warning { background: #FFF3E0; color: #E65100; }
        .cms-badge-secondary { background: #F0F2F5; color: #6B7280; }
        .form-control:focus { border-color: var(--cms-primary); box-shadow: 0 0 0 3px rgba(26,35,126,0.1); }
        .btn-cms { background: var(--cms-primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; }
        .btn-cms:hover { background: #0D1642; color: white; }
        @media (max-width: 768px) { .cms-sidebar { display: none; } .cms-main { margin-left: 0; } }
    </style>
</head>
<body>
<div class="cms-admin-layout">
    <!-- Sidebar -->
    <aside class="cms-sidebar">
        <div class="cms-sidebar-header">
            <h5>Content Management</h5>
            <h4>ISNM CMS</h4>
        </div>
        <a href="?page=dashboard" class="cms-nav-item <?= $page === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="?page=pages" class="cms-nav-item <?= $page === 'pages' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Pages</a>
        <a href="?page=blocks" class="cms-nav-item <?= $page === 'blocks' ? 'active' : '' ?>"><i class="fas fa-puzzle-piece"></i> Content Blocks</a>
        <a href="?page=banners" class="cms-nav-item <?= $page === 'banners' ? 'active' : '' ?>"><i class="fas fa-images"></i> Hero Banners</a>
        <a href="?page=gallery" class="cms-nav-item <?= $page === 'gallery' ? 'active' : '' ?>"><i class="fas fa-camera"></i> Gallery</a>
        <a href="?page=events" class="cms-nav-item <?= $page === 'events' ? 'active' : '' ?>"><i class="fas fa-calendar"></i> Events</a>
        <a href="?page=news" class="cms-nav-item <?= $page === 'news' ? 'active' : '' ?>"><i class="fas fa-newspaper"></i> News</a>
        <a href="?page=testimonials" class="cms-nav-item <?= $page === 'testimonials' ? 'active' : '' ?>"><i class="fas fa-quote-left"></i> Testimonials</a>
        <a href="?page=faqs" class="cms-nav-item <?= $page === 'faqs' ? 'active' : '' ?>"><i class="fas fa-question-circle"></i> FAQs</a>
        <a href="?page=partners" class="cms-nav-item <?= $page === 'partners' ? 'active' : '' ?>"><i class="fas fa-handshake"></i> Partners</a>
        <a href="?page=staff" class="cms-nav-item <?= $page === 'staff' ? 'active' : '' ?>"><i class="fas fa-users"></i> Staff Directory</a>
        <a href="?page=settings" class="cms-nav-item <?= $page === 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a>
        <a href="?page=seo" class="cms-nav-item <?= $page === 'seo' ? 'active' : '' ?>"><i class="fas fa-search"></i> SEO</a>
        <a href="?page=social" class="cms-nav-item <?= $page === 'social' ? 'active' : '' ?>"><i class="fas fa-share-alt"></i> Social Media</a>
        <a href="?page=audit" class="cms-nav-item <?= $page === 'audit' ? 'active' : '' ?>"><i class="fas fa-history"></i> Audit Log</a>
    </aside>

    <!-- Main Content -->
    <main class="cms-main">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php
        // ─── Dashboard ──────────────────────────────
        if ($page === 'dashboard'): ?>
            <div class="cms-page-header">
                <h2><i class="fas fa-tachometer-alt me-2"></i>CMS Dashboard</h2>
                <span class="text-muted">Welcome, <?= htmlspecialchars($userName) ?> (<?= htmlspecialchars($userRole) ?>)</span>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="cms-stat-card"><div class="stat-number"><?= count($pages) ?></div><div class="stat-label">Total Pages</div></div></div>
                <div class="col-md-3"><div class="cms-stat-card"><div class="stat-number"><?= $stats['total'] ?? 0 ?></div><div class="stat-label">Total Views</div></div></div>
                <div class="col-md-3"><div class="cms-stat-card"><div class="stat-number"><?= $stats['today'] ?? 0 ?></div><div class="stat-label">Today's Views</div></div></div>
                <div class="col-md-3"><div class="cms-stat-card"><div class="stat-number" style="color: var(--cms-success)">Active</div><div class="stat-label">System Status</div></div></div>
            </div>
            <div class="cms-content-card">
                <div class="card-header"><h5 class="mb-0">Recent Activity</h5></div>
                <div class="card-body">
                    <table class="table cms-table">
                        <thead><tr><th>User</th><th>Action</th><th>Content</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($auditEntries as $entry): ?>
                            <tr>
                                <td><?= htmlspecialchars($entry['user_name'] ?? 'System') ?></td>
                                <td><span class="cms-badge cms-badge-<?= $entry['action'] === 'create' ? 'success' : ($entry['action'] === 'delete' ? 'warning' : 'secondary') ?>"><?= ucfirst($entry['action']) ?></span></td>
                                <td><?= htmlspecialchars($entry['content_title'] ?? $entry['content_type'] ?? '') ?></td>
                                <td class="text-muted small"><?= date('M d, Y H:i', strtotime($entry['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($auditEntries)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No activity recorded yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php
        // ─── Pages Manager ──────────────────────────
        elseif ($page === 'pages' && $action === 'edit' && $contentId):
            $editPage = $cms->getPageById($contentId);
            $blocks = $cms->getBlocks($contentId);
            ?>
            <div class="cms-page-header">
                <h2><i class="fas fa-edit me-2"></i>Edit Page</h2>
                <a href="?page=pages" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Pages</a>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_page">
                <input type="hidden" name="page_id" value="<?= $editPage['id'] ?? 0 ?>">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="cms-content-card mb-4">
                            <div class="card-header"><h5 class="mb-0">Content</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Page Title</label>
                                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editPage['title'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Subtitle</label>
                                    <input type="text" name="subtitle" class="form-control" value="<?= htmlspecialchars($editPage['subtitle'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">URL Slug</label>
                                    <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($editPage['slug'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Page Content</label>
                                    <textarea name="content" class="form-control" rows="12"><?= htmlspecialchars($editPage['content'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="cms-content-card mb-4">
                            <div class="card-header"><h5 class="mb-0">Hero Section</h5></div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12"><label class="form-label fw-semibold">Hero Title</label><input type="text" name="hero_title" class="form-control" value="<?= htmlspecialchars($editPage['hero_title'] ?? '') ?>"></div>
                                    <div class="col-12"><label class="form-label fw-semibold">Hero Subtitle</label><input type="text" name="hero_subtitle" class="form-control" value="<?= htmlspecialchars($editPage['hero_subtitle'] ?? '') ?>"></div>
                                    <div class="col-md-8"><label class="form-label fw-semibold">Hero Image URL</label><input type="text" name="hero_image" class="form-control" value="<?= htmlspecialchars($editPage['hero_image'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label fw-semibold">Overlay Color</label><input type="text" name="hero_overlay_color" class="form-control" value="<?= htmlspecialchars($editPage['hero_overlay_color'] ?? 'rgba(26,35,126,0.7)') ?>"></div>
                                </div>
                            </div>
                        </div>
                        <div class="cms-content-card mb-4">
                            <div class="card-header"><h5 class="mb-0">SEO Settings</h5></div>
                            <div class="card-body">
                                <div class="mb-3"><label class="form-label fw-semibold">Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($editPage['meta_title'] ?? '') ?>"></div>
                                <div class="mb-3"><label class="form-label fw-semibold">Meta Description</label><textarea name="meta_description" class="form-control" rows="2"><?= htmlspecialchars($editPage['meta_description'] ?? '') ?></textarea></div>
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label fw-semibold">OG Title</label><input type="text" name="og_title" class="form-control" value="<?= htmlspecialchars($editPage['og_title'] ?? '') ?>"></div>
                                    <div class="col-md-6"><label class="form-label fw-semibold">OG Image</label><input type="text" name="og_image" class="form-control" value="<?= htmlspecialchars($editPage['og_image'] ?? '') ?>"></div>
                                </div>
                                <div class="mb-3 mt-3"><label class="form-label fw-semibold">OG Description</label><textarea name="og_description" class="form-control" rows="2"><?= htmlspecialchars($editPage['og_description'] ?? '') ?></textarea></div>
                                <div class="mb-3"><label class="form-label fw-semibold">Canonical URL</label><input type="text" name="canonical_url" class="form-control" value="<?= htmlspecialchars($editPage['canonical_url'] ?? '') ?>"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="cms-content-card mb-4">
                            <div class="card-header"><h5 class="mb-0">Publish</h5></div>
                            <div class="card-body">
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_published" value="1" <?= ($editPage['is_published'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label">Published</label></div>
                                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_featured" value="1" <?= ($editPage['is_featured'] ?? 0) ? 'checked' : '' ?>><label class="form-check-label">Featured</label></div>
                                <div class="mb-3"><label class="form-label small">Sort Order</label><input type="number" name="sort_order" class="form-control form-control-sm" value="<?= $editPage['sort_order'] ?? 0 ?>"></div>
                                <div class="mb-3"><label class="form-label small">Page Type</label><select name="page_type" class="form-select form-select-sm"><option value="static" <?= ($editPage['page_type'] ?? '') === 'static' ? 'selected' : '' ?>>Static</option><option value="dynamic" <?= ($editPage['page_type'] ?? '') === 'dynamic' ? 'selected' : '' ?>>Dynamic</option></select></div>
                                <button type="submit" class="btn btn-cms w-100"><i class="fas fa-save me-1"></i>Save Page</button>
                            </div>
                        </div>
                        <?php if (!empty($blocks)): ?>
                        <div class="cms-content-card">
                            <div class="card-header"><h5 class="mb-0">Content Blocks</h5></div>
                            <div class="card-body">
                                <?php foreach ($blocks as $b): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                        <div><small class="fw-semibold"><?= htmlspecialchars($b['title'] ?: $b['block_key']) ?></small><br><small class="text-muted"><?= $b['block_type'] ?></small></div>
                                        <a href="?page=blocks&action=edit&id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

        <?php
        // ─── Pages List ─────────────────────────────
        elseif ($page === 'pages'): ?>
            <div class="cms-page-header">
                <h2><i class="fas fa-file-alt me-2"></i>Website Pages</h2>
            </div>
            <div class="cms-content-card">
                <div class="card-body">
                    <table class="table cms-table">
                        <thead><tr><th>Page</th><th>Slug</th><th>Type</th><th>Status</th><th>Views</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($pages as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                                <td><code><?= htmlspecialchars($p['slug']) ?></code></td>
                                <td><span class="cms-badge cms-badge-secondary"><?= $p['page_type'] ?></span></td>
                                <td><span class="cms-badge <?= $p['is_published'] ? 'cms-badge-success' : 'cms-badge-warning' ?>"><?= $p['is_published'] ? 'Published' : 'Draft' ?></span></td>
                                <td><?= number_format($p['page_views'] ?? 0) ?></td>
                                <td><a href="?page=pages&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php
        // ─── Settings ───────────────────────────────
        elseif ($page === 'settings'): ?>
            <div class="cms-page-header">
                <h2><i class="fas fa-cog me-2"></i>Site Settings</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_setting">
                <?php
                $groups = ['general' => 'General', 'contact' => 'Contact Information', 'seo' => 'SEO', 'social' => 'Social Media', 'homepage' => 'Homepage', 'footer' => 'Footer'];
                foreach ($groups as $groupKey => $groupLabel):
                    $settings = $cms->getSettingsByGroup($groupKey);
                    if (empty($settings)) continue;
                ?>
                <div class="cms-content-card mb-4">
                    <div class="card-header"><h5 class="mb-0"><?= $groupLabel ?></h5></div>
                    <div class="card-body">
                        <?php foreach ($settings as $key => $value): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-capitalize"><?= str_replace('_', ' ', $key) ?></label>
                            <input type="text" name="setting_<?= htmlspecialchars($key) ?>" class="form-control" value="<?= htmlspecialchars($value) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-cms mb-4"><i class="fas fa-save me-1"></i>Save All Settings</button>
            </form>

        <?php
        // ─── Audit Log ──────────────────────────────
        elseif ($page === 'audit'): ?>
            <div class="cms-page-header"><h2><i class="fas fa-history me-2"></i>Audit Log</h2></div>
            <div class="cms-content-card">
                <div class="card-body">
                    <table class="table cms-table">
                        <thead><tr><th>Date</th><th>User</th><th>Role</th><th>Action</th><th>Content</th><th>IP</th></tr></thead>
                        <tbody>
                        <?php foreach ($auditEntries as $e): ?>
                            <tr>
                                <td class="small"><?= date('M d, Y H:i', strtotime($e['created_at'])) ?></td>
                                <td><?= htmlspecialchars($e['user_name'] ?? 'System') ?></td>
                                <td><span class="cms-badge cms-badge-secondary"><?= htmlspecialchars($e['user_role'] ?? '') ?></span></td>
                                <td><span class="cms-badge cms-badge-<?= $e['action'] === 'create' ? 'success' : ($e['action'] === 'delete' ? 'warning' : 'secondary') ?>"><?= ucfirst($e['action']) ?></span></td>
                                <td><?= htmlspecialchars($e['content_title'] ?? $e['content_type'] ?? '') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($e['ip_address'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="cms-page-header"><h2>Page: <?= htmlspecialchars($page) ?></h2></div>
            <div class="cms-content-card"><div class="card-body text-center py-5">
                <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                <p class="text-muted">This section is available but can be accessed through the sidebar.</p>
            </div></div>
        <?php endif; ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
