<?php
/**
 * ISNM DYNAMIC SIDEBAR GENERATOR
 * Generates sidebar HTML from the module registry database.
 * Grouped by department, role-filtered, with search and collapse.
 *
 * Usage:
 *   require_once __DIR__ . '/includes/module_sidebar.php';
 *   renderModuleSidebar($roleId);
 */
if (!function_exists('getModuleRegistry')) {
    require_once __DIR__ . '/module_registry.php';
}

function renderModuleSidebar(int $roleId, string $currentSection = ''): void {
    $registry = getModuleRegistry();
    $sidebar = $registry->getSidebarForRole($roleId);
    
    if (empty($sidebar)) {
        echo '<div class="mod-sidebar-empty"><i class="fas fa-lock"></i><p>No modules available</p></div>';
        return;
    }
    
    $currentModule = $_GET['section'] ?? $currentSection;
    ?>
    <nav class="mod-sidebar" id="moduleSidebar">
        <!-- Search -->
        <div class="mod-sidebar-search">
            <i class="fas fa-search"></i>
            <input type="text" id="moduleSearch" placeholder="Search modules..." oninput="filterModules(this.value)">
        </div>
        
        <!-- Module Groups -->
        <div class="mod-sidebar-groups" id="moduleGroups">
            <?php foreach ($sidebar as $deptKey => $dept): ?>
                <div class="mod-sidebar-group" data-dept="<?= htmlspecialchars($deptKey) ?>">
                    <div class="mod-sidebar-group-header" onclick="toggleGroup(this)">
                        <div class="mod-sidebar-group-info">
                            <i class="fas fa-<?= htmlspecialchars($dept['icon']) ?>" style="color:<?= htmlspecialchars($dept['color']) ?>"></i>
                            <span><?= htmlspecialchars($dept['label']) ?></span>
                        </div>
                        <i class="fas fa-chevron-down mod-sidebar-chevron"></i>
                        <span class="mod-sidebar-count"><?= count($dept['modules']) ?></span>
                    </div>
                    <div class="mod-sidebar-group-items collapse in">
                        <?php foreach ($dept['modules'] as $mod): ?>
                            <a href="#" 
                               class="mod-sidebar-item <?= $mod['name'] === $currentModule ? 'active' : '' ?>"
                               data-module="<?= htmlspecialchars($mod['name']) ?>"
                               data-route="<?= htmlspecialchars($mod['route']) ?>"
                               onclick="loadModule('<?= htmlspecialchars($mod['name']) ?>', '<?= htmlspecialchars($mod['route']) ?>', this); return false;"
                               title="<?= htmlspecialchars($mod['description'] ?? $mod['label']) ?>">
                                <i class="fas fa-<?= htmlspecialchars($mod['icon']) ?> mod-sidebar-item-icon"></i>
                                <span class="mod-sidebar-item-label"><?= htmlspecialchars($mod['label']) ?></span>
                                <?php if ($mod['can_create']): ?>
                                    <span class="mod-sidebar-item-badge" title="Can create">+</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- User info -->
        <div class="mod-sidebar-footer">
            <div class="mod-sidebar-user">
                <div class="mod-sidebar-user-avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?></div>
                <div class="mod-sidebar-user-info">
                    <div class="mod-sidebar-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Staff') ?></div>
                    <div class="mod-sidebar-user-role"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </nav>
    
    <script>
    // Toggle department group
    function toggleGroup(header) {
        var items = header.nextElementSibling;
        var chevron = header.querySelector('.mod-sidebar-chevron');
        if (items.classList.contains('in')) {
            items.classList.remove('in');
            items.style.maxHeight = '0';
            chevron.style.transform = 'rotate(-90deg)';
        } else {
            items.classList.add('in');
            items.style.maxHeight = items.scrollHeight + 'px';
            chevron.style.transform = 'rotate(0)';
        }
    }
    
    // Search modules
    function filterModules(query) {
        var items = document.querySelectorAll('.mod-sidebar-item');
        var groups = document.querySelectorAll('.mod-sidebar-group');
        var q = query.toLowerCase();
        
        groups.forEach(function(group) {
            var hasVisible = false;
            var modItems = group.querySelectorAll('.mod-sidebar-item');
            modItems.forEach(function(item) {
                var label = item.querySelector('.mod-sidebar-item-label').textContent.toLowerCase();
                var modName = item.getAttribute('data-module').toLowerCase();
                if (label.indexOf(q) !== -1 || modName.indexOf(q) !== -1) {
                    item.style.display = '';
                    hasVisible = true;
                } else {
                    item.style.display = 'none';
                }
            });
            group.style.display = hasVisible ? '' : 'none';
            if (hasVisible && q) {
                var itemsContainer = group.querySelector('.mod-sidebar-group-items');
                itemsContainer.classList.add('in');
                itemsContainer.style.maxHeight = itemsContainer.scrollHeight + 'px';
            }
        });
    }
    
    // Load module via AJAX
    function loadModule(moduleName, route, element) {
        // Remove active from all
        document.querySelectorAll('.mod-sidebar-item').forEach(function(el) {
            el.classList.remove('active');
        });
        element.classList.add('active');
        
        // Show loading
        var contentArea = document.getElementById('moduleContentArea') || document.querySelector('.ent-content-area');
        if (contentArea) {
            contentArea.innerHTML = '<div class="mod-loading"><div class="mod-spinner"></div><p>Loading ' + moduleName + '...</p></div>';
            
            // Fetch module content
            fetch(route, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Module-Name': moduleName
                }
            })
            .then(function(response) { return response.text(); })
            .then(function(html) {
                contentArea.innerHTML = html;
                // Re-init scripts
                contentArea.querySelectorAll('script').forEach(function(oldScript) {
                    var newScript = document.createElement('script');
                    if (oldScript.src) { newScript.src = oldScript.src; }
                    else { newScript.textContent = oldScript.textContent; }
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
                // Update URL
                history.pushState({module: moduleName}, '', '?section=' + moduleName);
            })
            .catch(function(err) {
                contentArea.innerHTML = '<div class="mod-error"><i class="fas fa-exclamation-triangle"></i><p>Failed to load module</p></div>';
                console.error('[Module Load Error]', err);
            });
        }
        
        // Close mobile sidebar
        if (window.innerWidth <= 768) {
            document.querySelector('.mod-sidebar').classList.remove('open');
        }
    }
    
    // Handle browser back/forward
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.module) {
            var item = document.querySelector('[data-module="' + e.state.module + '"]');
            if (item) {
                loadModule(e.state.module, item.getAttribute('data-route'), item);
            }
        }
    });
    
    // Initialize expand all groups
    document.querySelectorAll('.mod-sidebar-group-items').forEach(function(el) {
        el.style.maxHeight = el.scrollHeight + 'px';
    });
    </script>
    <?php
}

/**
 * Render a compact sidebar for embedded use
 */
function renderCompactModuleSidebar(int $roleId): void {
    $registry = getModuleRegistry();
    $sidebar = $registry->getSidebarForRole($roleId);
    ?>
    <div class="mod-compact-sidebar">
        <?php foreach ($sidebar as $deptKey => $dept): ?>
            <div class="mod-compact-dept">
                <div class="mod-compact-dept-title" style="color:<?= htmlspecialchars($dept['color']) ?>">
                    <i class="fas fa-<?= htmlspecialchars($dept['icon']) ?>"></i>
                    <?= htmlspecialchars($dept['label']) ?>
                </div>
                <?php foreach ($dept['modules'] as $mod): ?>
                    <a href="<?= htmlspecialchars($mod['route']) ?>" class="mod-compact-item">
                        <i class="fas fa-<?= htmlspecialchars($mod['icon']) ?>"></i>
                        <?= htmlspecialchars($mod['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
