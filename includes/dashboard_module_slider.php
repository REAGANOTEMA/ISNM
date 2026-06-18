<?php
/**
 * Universal Dashboard Module Slider
 * Horizontal scrollable carousel of accessible modules for any dashboard.
 * Include after sidebar.php on any dashboard page.
 * Usage: <?php renderModuleSlider($user_role); ?>
 */
if (!function_exists('renderModuleSlider')) {
function renderModuleSlider(string $userRole, array $extraModules = []): void {
    require_once __DIR__ . '/module_config.php';
    $modules = getFilteredModules($userRole);

    // Merge any extra modules (like student self-service)
    if (!empty($extraModules)) {
        $modules = array_merge($modules, $extraModules);
    }

    if (empty($modules)) return;

    $uqid = 'ms_' . uniqid();
?>
<div class="module-slider-section section-card">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h2 class="mb-0"><i class="fas fa-th-large me-2 text-primary"></i>Quick Access Modules</h2>
        <div class="module-slider-controls d-flex gap-1">
            <button class="btn btn-sm btn-outline-secondary slider-prev" onclick="slideModules('<?= $uqid ?>', -1)" title="Scroll left"><i class="fas fa-chevron-left"></i></button>
            <button class="btn btn-sm btn-outline-secondary slider-next" onclick="slideModules('<?= $uqid ?>', 1)" title="Scroll right"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
    <div class="module-slider-track" id="<?= $uqid ?>_track">
        <?php foreach ($modules as $parent):
            $icon = $parent['icon'] ?? 'fas fa-folder';
        ?>
        <div class="module-slider-item" title="<?= htmlspecialchars($parent['title']) ?>">
            <div class="module-card" data-bs-toggle="collapse" data-bs-target="#<?= $uqid ?>_collapse_<?= preg_replace('/[^a-z0-9]/', '', strtolower($parent['title'])) ?>" aria-expanded="false">
                <div class="module-card-icon"><i class="<?= $icon ?>"></i></div>
                <div class="module-card-title"><?= htmlspecialchars($parent['title']) ?></div>
                <div class="module-card-count"><?= count($parent['children'] ?? []) ?> items</div>
            </div>
            <?php if (!empty($parent['children'])): ?>
            <div class="module-card-children collapse" id="<?= $uqid ?>_collapse_<?= preg_replace('/[^a-z0-9]/', '', strtolower($parent['title'])) ?>">
                <div class="module-children-list">
                    <?php foreach ($parent['children'] as $child): ?>
                    <a href="<?= htmlspecialchars($child['route']) ?>" class="module-child-link">
                        <i class="fas fa-circle" style="font-size:6px;color:var(--theme-accent,#2563eb)"></i>
                        <?= htmlspecialchars($child['title']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.module-slider-section .section-card {
    overflow: visible;
}
.module-slider-track {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    padding: 4px 2px 10px;
    scrollbar-width: none;
}
.module-slider-track::-webkit-scrollbar { display: none; }
.module-slider-item {
    flex: 0 0 auto;
    width: 160px;
    min-width: 140px;
}
.module-card {
    background: #fff;
    border: 1px solid rgba(148,163,184,0.18);
    border-radius: 14px;
    padding: 18px 14px 14px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(15,23,42,0.04);
}
.module-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(15,23,42,0.1);
    border-color: var(--theme-accent, #2563eb);
}
.module-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--theme-primary, #1a237e), var(--theme-accent, #2563eb));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(37,99,235,0.2);
}
.module-card-title {
    font-size: 12px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.3;
    text-align: center;
    word-break: break-word;
}
.module-card-count {
    font-size: 10px;
    color: #94a3b8;
}
.module-card-children {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 100;
    width: 220px;
    background: #fff;
    border: 1px solid rgba(148,163,184,0.18);
    border-radius: 12px;
    box-shadow: 0 16px 40px rgba(15,23,42,0.12);
    margin-top: 4px;
    overflow: hidden;
}
.module-children-list {
    display: flex;
    flex-direction: column;
    padding: 6px;
    max-height: 260px;
    overflow-y: auto;
}
.module-child-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    color: #334155;
    text-decoration: none;
    border-radius: 8px;
    font-size: 12px;
    transition: all 0.2s ease;
}
.module-child-link:hover {
    background: #f1f5f9;
    color: var(--theme-primary, #1a237e);
}
.module-slider-item { position: relative; }
.module-slider-controls .btn { border-radius: 8px; padding: 4px 10px; }
.module-slider-controls .btn:hover { background: var(--theme-accent, #2563eb); color: #fff; border-color: var(--theme-accent, #2563eb); }

@media (max-width: 768px) {
    .module-slider-item { width: 120px; min-width: 110px; }
    .module-card { padding: 12px 10px; }
    .module-card-icon { width: 40px; height: 40px; font-size: 16px; }
    .module-card-title { font-size: 11px; }
    .module-card-children { width: 200px; }
}
@media (max-width: 480px) {
    .module-slider-item { width: 100px; min-width: 90px; }
    .module-card-icon { width: 36px; height: 36px; font-size: 14px; }
    .module-card-title { font-size: 10px; }
    .module-card-count { font-size: 9px; }
}
</style>

<script>
function slideModules(id, dir) {
    var track = document.getElementById(id + '_track');
    if (!track) return;
    var scrollAmt = track.clientWidth * 0.6;
    track.scrollBy({ left: dir * scrollAmt, behavior: 'smooth' });
}
document.addEventListener('DOMContentLoaded', function() {
    // Auto-close child menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.module-slider-item')) {
            document.querySelectorAll('.module-card-children.show').forEach(function(el) {
                var bsCollapse = bootstrap.Collapse.getInstance(el);
                if (bsCollapse) bsCollapse.hide();
            });
        }
    });
});
</script>
<?php
}
}
