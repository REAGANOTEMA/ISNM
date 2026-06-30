<?php
/**
 * ISNM Universal Dashboard Header Bar
 * Minimal top bar: page title + date + print. No duplication with sidebar.
 * Usage: include_once __DIR__ . '/../includes/dashboard_topbar.php';
 */
if (!function_exists('renderDashboardTopbar')) {
function renderDashboardTopbar(string $pageTitle = '', string $subtitle = ''): void {
    $pageTitle = $pageTitle ?: ($GLOBALS['pageTitle'] ?? 'Dashboard');
    $date = date('l, d M Y');
    ?>
    <div class="isnm-topbar">
        <div class="isnm-topbar-left">
            <h1 class="isnm-topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
            <?php if ($subtitle): ?>
            <p class="isnm-topbar-subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>
        <div class="isnm-topbar-right">
            <span class="isnm-topbar-date"><i class="fas fa-calendar-alt"></i> <?= $date ?></span>
            <button class="isnm-topbar-btn" onclick="window.print()" title="Print"><i class="fas fa-print"></i></button>
        </div>
    </div>
    <?php
}
}
