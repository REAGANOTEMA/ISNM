<?php
/**
 * Universal Dashboard Back Button & Breadcrumb
 * Shows a "Back to Dashboard" button and contextual breadcrumb.
 * Insert at the top of any sub-page's content area.
 * 
 * Usage: <?php renderDashboardBackButton('Dashboard Name', 'sub-page.php'); ?>
 *        <?php renderDashboardBreadcrumb(['Home', 'Section', 'Page']); ?>
 */
if (!function_exists('renderDashboardBackButton')) {
function renderDashboardBackButton(string $dashboardName = 'Dashboard', string $dashboardPath = ''): void {
    if (empty($dashboardPath)) {
        // Auto-detect: go one directory up and find the main dashboards
        $dashboardPath = '../dashboards/';
    }
    
    // Get current script name to detect which dashboard to link to
    $currentScript = basename($_SERVER['PHP_SELF'], '.php');
    
    // Map common page names to their dashboards
    $dashboardMap = [
        'staff_transcript_generation' => 'academic-registrar.php',
        'staff_receipt_printing' => 'school-bursar.php',
        'document_management' => 'director-general.php',
        'recycle_bin' => 'director-general.php',
        'student_management' => 'director-general.php',
        'student_records' => 'academic-registrar.php',
        'student_attendance' => 'lecturers.php',
    ];
    
    if (isset($dashboardMap[$currentScript])) {
        $dashboardPath = $dashboardMap[$currentScript];
    }
    
    if (strpos($dashboardPath, '.php') === false && substr($dashboardPath, -1) !== '/') {
        $dashboardPath .= '/';
    }
?>
<div class="dashboard-back-bar">
    <a href="<?= htmlspecialchars($dashboardPath) ?>" class="back-to-dashboard">
        <i class="fas fa-arrow-left me-1"></i>
        <span>Back to <?= htmlspecialchars($dashboardName) ?></span>
    </a>
</div>
<?php
}
}

if (!function_exists('renderDashboardBreadcrumb')) {
function renderDashboardBreadcrumb(array $crumbs): void {
    if (empty($crumbs)) return;
?>
<nav aria-label="breadcrumb" class="page-breadcrumb d-none d-md-block">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
            <a href="../dashboards/" class="text-decoration-none">
                <i class="fas fa-home me-1"></i>Dashboard
            </a>
        </li>
        <?php foreach ($crumbs as $i => $crumb): ?>
            <?php if ($i === array_key_last($crumbs)): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($crumb) ?></li>
            <?php else: ?>
                <li class="breadcrumb-item"><?= htmlspecialchars($crumb) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
<?php
}
}

if (!function_exists('renderTopActionBar')) {
function renderTopActionBar(string $title, string $subtitle = '', array $actions = []): void {
?>
<div class="page-title-card d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h3 class="fw-bold mb-1"><?= htmlspecialchars($title) ?></h3>
        <?php if ($subtitle): ?>
        <p class="mb-0 text-muted small"><?= htmlspecialchars($subtitle) ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($actions)): ?>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($actions as $action):
            $url = $action['url'] ?? '#';
            $icon = $action['icon'] ?? 'fas fa-plus';
            $label = $action['label'] ?? 'Action';
            $btnClass = $action['class'] ?? 'btn-primary';
            $onclick = $action['onclick'] ?? '';
            $target = $action['target'] ?? '';
        ?>
        <a href="<?= htmlspecialchars($url) ?>" class="btn <?= htmlspecialchars($btnClass) ?> btn-sm" <?= $onclick ? 'onclick="'.htmlspecialchars($onclick).'"' : '' ?> <?= $target ? 'target="'.htmlspecialchars($target).'"' : '' ?>>
            <i class="<?= $icon ?> me-1"></i><?= htmlspecialchars($label) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php
}
}

// Auto-include styles if not already present
if (!isset($GLOBALS['_dashboard_back_button_styles_loaded'])) {
    $GLOBALS['_dashboard_back_button_styles_loaded'] = true;
?>
<style>
.dashboard-back-bar {
    margin-bottom: 16px;
}
.back-to-dashboard {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 16px;
    background: #fff;
    border: 1px solid rgba(148,163,184,0.2);
    border-radius: 10px;
    color: #475569;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.25s ease;
    box-shadow: 0 2px 8px rgba(15,23,42,0.04);
}
.back-to-dashboard:hover {
    background: var(--theme-primary, #1a237e);
    color: #fff;
    border-color: var(--theme-primary, #1a237e);
    transform: translateX(-3px);
    box-shadow: 0 4px 12px rgba(26,35,126,0.15);
}
.back-to-dashboard i {
    font-size: 12px;
}
.page-breadcrumb .breadcrumb-item a {
    color: var(--theme-accent, #2563eb);
    font-size: 13px;
}
.page-breadcrumb .breadcrumb-item.active {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
}
.page-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
    color: #94a3b8;
    font-size: 10px;
}
</style>
<?php
}
