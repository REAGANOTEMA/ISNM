<?php
/**
 * Director Website Submissions Panel
 * Complete panel for viewing and managing website submissions in director dashboards.
 * 
 * Usage: include_once __DIR__ . '/../includes/director_website_panel.php';
 * Then call: renderDirectorWebsitePanel($websiteConn, $panelTypes, $panelTitle);
 * 
 * Parameters:
 *   $websiteConn - Website database connection
 *   $panelTypes  - Array of types to show (default: all)
 *   $panelTitle  - Title for the panel
 */

if (!function_exists('renderDirectorWebsitePanel')) {
    function renderDirectorWebsitePanel($websiteConn, $panelTypes = null, $panelTitle = 'Website Submissions') {
        if ($panelTypes === null) {
            $panelTypes = ['contacts', 'donations', 'volunteers', 'applications'];
        }

        // Handle POST actions
        if ($websiteConn && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ws_action'])) {
            handleWebsiteSubmissionsAction($websiteConn);
        }

        // Fetch stats
        $pendingContacts = 0;
        $pendingDonations = 0;
        $pendingVolunteers = 0;
        $pendingApplications = 0;
        $totalSubmissions = 0;
        $recentSubmissions = [];

        if ($websiteConn) {
            if (in_array('contacts', $panelTypes)) {
                $r = $websiteConn->query("SELECT COUNT(*) c FROM contact_submissions WHERE status='New'");
                if ($r) $pendingContacts = (int)$r->fetch_assoc()['c'];
            }
            if (in_array('donations', $panelTypes)) {
                $r = $websiteConn->query("SELECT COUNT(*) c FROM donations WHERE status='pending'");
                if ($r) $pendingDonations = (int)$r->fetch_assoc()['c'];
            }
            if (in_array('volunteers', $panelTypes)) {
                $r = $websiteConn->query("SELECT COUNT(*) c FROM volunteer_applications WHERE status='pending'");
                if ($r) $pendingVolunteers = (int)$r->fetch_assoc()['c'];
            }
            if (in_array('applications', $panelTypes)) {
                $r = $websiteConn->query("SELECT COUNT(*) c FROM student_applications WHERE status='Pending'");
                if ($r) $pendingApplications = (int)$r->fetch_assoc()['c'];
            }

            $totalPending = $pendingContacts + $pendingDonations + $pendingVolunteers + $pendingApplications;

            // Build union query for recent submissions
            $queries = [];
            if (in_array('contacts', $panelTypes)) {
                $queries[] = "(SELECT 'contacts' as type, id, full_name as name, email, subject as detail, status, created_at FROM contact_submissions)";
            }
            if (in_array('volunteers', $panelTypes)) {
                $queries[] = "(SELECT 'volunteers' as type, id, CONCAT(first_name,' ',last_name) as name, email, CONCAT(profession,' - ',opportunity) as detail, status, created_at FROM volunteer_applications)";
            }
            if (in_array('donations', $panelTypes)) {
                $queries[] = "(SELECT 'donations' as type, id, donor_name as name, email, CONCAT('UGX ',FORMAT(amount,0)) as detail, status, created_at FROM donations)";
            }
            if (in_array('applications', $panelTypes)) {
                $queries[] = "(SELECT 'applications' as type, id, CONCAT(first_name,' ',surname) as name, email, program_applied as detail, status, submitted_at as created_at FROM student_applications)";
            }

            if (!empty($queries)) {
                $union = implode(" UNION ALL ", $queries) . " ORDER BY created_at DESC LIMIT 15";
                $r = $websiteConn->query($union);
                if ($r) while ($row = $r->fetch_assoc()) $recentSubmissions[] = $row;
            }
        } else {
            $totalPending = 0;
        }

        // Badge colors and icons
        $badgeColors = [
            'contacts'    => 'background:#eff6ff;color:#2563eb;',
            'donations'   => 'background:#ecfdf5;color:#059669;',
            'volunteers'  => 'background:#f5f3ff;color:#7c3aed;',
            'applications'=> 'background:#fff7ed;color:#ea580c;',
        ];
        $icons = [
            'contacts'    => 'fa-envelope',
            'donations'   => 'fa-hand-holding-heart',
            'volunteers'  => 'fa-hands-helping',
            'applications'=> 'fa-file-alt',
        ];
        $labels = [
            'contacts'    => 'Contact',
            'donations'   => 'Donation',
            'volunteers'  => 'Volunteer',
            'applications'=> 'Application',
        ];
        $statusColors = [
            'contacts'    => ['New' => 'warning', 'Read' => 'info', 'Responded' => 'success', 'resolved' => 'success', 'spam' => 'danger'],
            'donations'   => ['pending' => 'warning', 'verified' => 'success', 'approved' => 'success', 'cancelled' => 'danger'],
            'volunteers'  => ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'resolved' => 'success'],
            'applications'=> ['Pending' => 'warning', 'Approved' => 'success', 'Rejected' => 'danger', 'Resolved' => 'info', 'Under Review' => 'info'],
        ];
        ?>
        <div class="section-card" style="margin-bottom:14px;">
            <div class="section-header">
                <div>
                    <h3 class="section-title"><i class="fas fa-globe" style="color:#2563eb;"></i><?= htmlspecialchars($panelTitle) ?></h3>
                    <p class="section-subtitle">All website form submissions and applications</p>
                </div>
                <span class="badge bg-<?= $totalPending > 0 ? 'danger' : 'success' ?> rounded-pill" style="font-size:11px;"><?= $totalPending ?> Pending</span>
            </div>

            <?php if (!empty($_SESSION['ws_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show py-2" style="border:none;border-radius:8px;background:#ecfdf5;color:#065f46;margin-bottom:10px;">
                <i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($_SESSION['ws_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:11px;"></button>
            </div>
            <?php unset($_SESSION['ws_success']); endif; ?>

            <!-- Stats Cards -->
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;">
                <?php if (in_array('contacts', $panelTypes)): ?>
                <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:20px;font-weight:800;color:#1e40af;"><?= $pendingContacts ?></div>
                    <div style="font-size:10px;font-weight:600;color:#1e3a8a;text-transform:uppercase;letter-spacing:0.5px;">Pending Contacts</div>
                </div>
                <?php endif; ?>
                <?php if (in_array('donations', $panelTypes)): ?>
                <div style="background:linear-gradient(135deg,#ecfdf5,#dcfce7);border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:20px;font-weight:800;color:#166534;"><?= $pendingDonations ?></div>
                    <div style="font-size:10px;font-weight:600;color:#14532d;text-transform:uppercase;letter-spacing:0.5px;">Pending Donations</div>
                </div>
                <?php endif; ?>
                <?php if (in_array('volunteers', $panelTypes)): ?>
                <div style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:20px;font-weight:800;color:#6d28d9;"><?= $pendingVolunteers ?></div>
                    <div style="font-size:10px;font-weight:600;color:#5b21b6;text-transform:uppercase;letter-spacing:0.5px;">Pending Volunteers</div>
                </div>
                <?php endif; ?>
                <?php if (in_array('applications', $panelTypes)): ?>
                <div style="background:linear-gradient(135deg,#fff7ed,#fed7aa);border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:20px;font-weight:800;color:#c2410c;"><?= $pendingApplications ?></div>
                    <div style="font-size:10px;font-weight:600;color:#9a3412;text-transform:uppercase;letter-spacing:0.5px;">Pending Applications</div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($recentSubmissions)): ?>
            <div class="table-responsive" style="max-height:350px;overflow-y:auto;">
                <table class="table table-sm table-hover" style="font-size:12px;margin-bottom:0;">
                    <thead>
                        <tr style="position:sticky;top:0;background:#f8fafc;z-index:1;">
                            <th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;border-bottom:2px solid #e2e8f0;">Type</th>
                            <th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;border-bottom:2px solid #e2e8f0;">Name</th>
                            <th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;border-bottom:2px solid #e2e8f0;">Email</th>
                            <th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;border-bottom:2px solid #e2e8f0;">Detail</th>
                            <th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;border-bottom:2px solid #e2e8f0;">Date</th>
                            <th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;border-bottom:2px solid #e2e8f0;">Status</th>
                            <th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;border-bottom:2px solid #e2e8f0;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentSubmissions as $sub): 
                        $t = $sub['type'];
                        $statusKey = $sub['status'] ?? '';
                        $statusCls = $statusColors[$t][$statusKey] ?? 'secondary';
                        $date = !empty($sub['created_at']) ? date('d M Y', strtotime($sub['created_at'])) : '-';
                    ?>
                        <tr>
                            <td>
                                <span style="<?= $badgeColors[$t] ?>font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;display:inline-flex;align-items:center;gap:4px;">
                                    <i class="fas <?= $icons[$t] ?>" style="font-size:9px;"></i><?= $labels[$t] ?>
                                </span>
                            </td>
                            <td style="font-weight:600;"><?= htmlspecialchars($sub['name'] ?? '-') ?></td>
                            <td style="color:#64748b;"><?= htmlspecialchars($sub['email'] ?? '-') ?></td>
                            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($sub['detail'] ?? '-') ?></td>
                            <td style="color:#64748b;white-space:nowrap;"><?= $date ?></td>
                            <td><span class="badge bg-<?= $statusCls ?>" style="font-size:10px;"><?= htmlspecialchars($statusKey) ?></span></td>
                            <td style="white-space:nowrap;">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this?')">
                                    <input type="hidden" name="ws_action" value="approve">
                                    <input type="hidden" name="ws_type" value="<?= htmlspecialchars($t) ?>">
                                    <input type="hidden" name="ws_id" value="<?= intval($sub['id'] ?? 0) ?>">
                                    <button class="btn btn-sm" style="color:#059669;border:none;background:none;padding:2px 4px;" title="Approve"><i class="fas fa-check-circle"></i></button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Resolve this?')">
                                    <input type="hidden" name="ws_action" value="resolve">
                                    <input type="hidden" name="ws_type" value="<?= htmlspecialchars($t) ?>">
                                    <input type="hidden" name="ws_id" value="<?= intval($sub['id'] ?? 0) ?>">
                                    <button class="btn btn-sm" style="color:#2563eb;border:none;background:none;padding:2px 4px;" title="Resolve"><i class="fas fa-check-double"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-2x mb-2" style="color:#10b981;"></i>
                <p>No submissions found.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
