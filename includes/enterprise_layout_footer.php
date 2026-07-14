<?php
/**
 * ISNM Enterprise Layout Footer
 * Include this at the BOTTOM of any dashboard after the main content.
 * Closes the workspace and renders the right control panel.
 */
?>
    </div><!-- /ent-content-area -->
</main>

<!-- ═══ RIGHT CONTROL PANEL ═══ -->
<aside class="ent-control" id="entControlPanel">
    <!-- Profile Card -->
    <div class="ent-control-section ent-profile-card">
        <div class="ent-profile-avatar"><?= strtoupper(substr($elUser, 0, 1)) ?></div>
        <h4><?= htmlspecialchars($elUser) ?></h4>
        <p><?= htmlspecialchars($elRole) ?></p>
        <span class="ent-profile-role"><span class="ent-online-dot"></span>Online</span>
    </div>

    <!-- Quick Stats -->
    <div class="ent-control-section">
        <div class="ent-control-section-title"><i class="fas fa-chart-pie"></i> Quick Stats</div>
        <div class="ent-stats-grid">
            <div class="ent-stat-mini">
                <div class="ent-stat-mini-value" style="color:var(--ent-blue)"><?= number_format($elStats['students']) ?></div>
                <div class="ent-stat-mini-label">Students</div>
            </div>
            <div class="ent-stat-mini">
                <div class="ent-stat-mini-value" style="color:var(--ent-green)"><?= number_format($elStats['staff']) ?></div>
                <div class="ent-stat-mini-label">Staff</div>
            </div>
            <div class="ent-stat-mini">
                <div class="ent-stat-mini-value" style="color:var(--ent-orange)"><?= number_format($elStats['tasks']) ?></div>
                <div class="ent-stat-mini-label">Tasks</div>
            </div>
            <div class="ent-stat-mini">
                <div class="ent-stat-mini-value" style="color:var(--ent-purple)"><?= number_format($elStats['events']) ?></div>
                <div class="ent-stat-mini-label">Events</div>
            </div>
        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="ent-control-section">
        <div class="ent-control-section-title"><i class="fas fa-check-double"></i> Pending Approvals</div>
        <div class="ent-control-empty" style="text-align:center;padding:10px;color:var(--ent-text-muted);font-size:12px">
            <?= $elStats['approvals'] > 0 ? $elStats['approvals'] . ' pending' : 'No pending approvals' ?>
        </div>
    </div>

    <!-- Calendar -->
    <div class="ent-control-section">
        <div class="ent-control-section-title"><i class="fas fa-calendar-alt"></i> Calendar</div>
        <div style="text-align:center;padding:10px">
            <div style="font-size:24px;font-weight:700;color:var(--ent-blue)"><?= date('d') ?></div>
            <div style="font-size:12px;color:var(--ent-text-muted)"><?= date('l, F Y') ?></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="ent-control-section">
        <div class="ent-control-section-title"><i class="fas fa-bolt"></i> Quick Actions</div>
        <div style="display:flex;flex-direction:column;gap:6px">
            <a href="?section=profile" class="ent-control-action" style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;background:var(--ent-bg);color:var(--ent-text);text-decoration:none;font-size:12px;transition:all 0.2s">
                <i class="fas fa-user-circle" style="color:var(--ent-blue);width:18px;text-align:center"></i> My Profile
            </a>
            <a href="?section=settings" class="ent-control-action" style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;background:var(--ent-bg);color:var(--ent-text);text-decoration:none;font-size:12px;transition:all 0.2s">
                <i class="fas fa-cog" style="color:var(--ent-orange);width:18px;text-align:center"></i> Settings
            </a>
            <a href="#" class="ent-control-action" onclick="event.preventDefault();var f=document.createElement('form');f.method='POST';f.action='../auth-handler.php?action=logout';document.body.appendChild(f);f.submit();" style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;background:var(--ent-bg);color:var(--ent-text);text-decoration:none;font-size:12px;transition:all 0.2s">
                <i class="fas fa-sign-out-alt" style="color:var(--ent-red);width:18px;text-align:center"></i> Logout
            </a>
        </div>
    </div>
</aside>

<!-- ═══ Enterprise Layout JS ═══ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar group toggle
    document.querySelectorAll('.ent-sidebar-divider[data-target]').forEach(function(el) {
        el.addEventListener('click', function() {
            var gid = this.getAttribute('data-target');
            var group = this.closest('.ent-sidebar-group');
            var items = document.getElementById('childGroup-' + gid);
            if (items) {
                var isOpen = items.style.maxHeight && items.style.maxHeight !== '0px';
                items.style.maxHeight = isOpen ? '0' : items.scrollHeight + 'px';
                items.style.overflow = 'hidden';
                if (group) group.classList.toggle('expanded', !isOpen);
            }
        });
    });

    // Mobile sidebar toggle
    var hamburger = document.getElementById('entHamburger');
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            document.getElementById('entSidebar').classList.toggle('open');
        });
    }

    // Control panel toggle
    var controlPanel = document.getElementById('entControlPanel');
    if (controlPanel) {
        document.addEventListener('click', function(e) {
            if (!controlPanel.contains(e.target) && !e.target.closest('.ent-header-btn') && !e.target.closest('.ent-user-chip')) {
                controlPanel.classList.remove('open');
            }
        });
    }

    // Expand active groups
    document.querySelectorAll('.ent-sidebar-group.expanded .ent-sidebar-items').forEach(function(el) {
        el.style.maxHeight = el.scrollHeight + 'px';
    });
});
</script>

<?php include_once __DIR__ . '/dashboard_footer.php'; ?>
</body>
</html>
