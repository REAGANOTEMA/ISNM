<?php
/**
 * Website Submissions Widget
 * Reusable widget for viewing website submissions across director dashboards.
 * 
 * Parameters:
 *   $conn    - Website database connection (igangaschool_website)
 *   $types   - Array of types to show: 'contacts', 'donations', 'volunteers', 'applications'
 *   $limit   - Max records per type (default 10)
 */

if (!function_exists('renderWebsiteSubmissionsWidget')) {
    function renderWebsiteSubmissionsWidget($conn, $types = ['contacts', 'donations', 'volunteers', 'applications'], $limit = 10) {
        if (!$conn) {
            echo '<div class="text-center py-4 text-muted"><i class="fas fa-database fa-2x mb-2" style="color:#94a3b8;"></i><p>Website database unavailable.</p></div>';
            return;
        }

        $badgeColors = [
            'contacts'    => 'background:#eff6ff;color:#2563eb;',
            'donations'   => 'background:#ecfdf5;color:#059669;',
            'volunteers'  => 'background:#f5f3ff;color:#7c3aed;',
            'applications'=> 'background:#fff7ed;color:#ea580c;',
        ];
        $badgeLabels = [
            'contacts'    => 'Contact',
            'donations'   => 'Donation',
            'volunteers'  => 'Volunteer',
            'applications'=> 'Application',
        ];
        $icons = [
            'contacts'    => 'fa-envelope',
            'donations'   => 'fa-hand-holding-heart',
            'volunteers'  => 'fa-hands-helping',
            'applications'=> 'fa-file-alt',
        ];
        $statusMap = [
            'contacts'    => ['New' => 'warning', 'Read' => 'info', 'Responded' => 'success', 'resolved' => 'success', 'spam' => 'danger'],
            'donations'   => ['pending' => 'warning', 'verified' => 'success', 'approved' => 'success', 'cancelled' => 'danger', 'rejected' => 'danger'],
            'volunteers'  => ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'resolved' => 'success'],
            'applications'=> ['Pending' => 'warning', 'Approved' => 'success', 'Rejected' => 'danger', 'Resolved' => 'info', 'Under Review' => 'info'],
        ];

        $allSubmissions = [];

        if (in_array('contacts', $types)) {
            $r = $conn->query("SELECT id, full_name AS name, email, subject AS detail, status, created_at FROM contact_submissions ORDER BY created_at DESC LIMIT " . intval($limit));
            if ($r) while ($row = $r->fetch_assoc()) {
                $row['type'] = 'contacts';
                $allSubmissions[] = $row;
            }
        }
        if (in_array('donations', $types)) {
            $r = $conn->query("SELECT id, donor_name AS name, email, CONCAT('UGX ', FORMAT(amount,0)) AS detail, status, created_at FROM donations ORDER BY created_at DESC LIMIT " . intval($limit));
            if ($r) while ($row = $r->fetch_assoc()) {
                $row['type'] = 'donations';
                $allSubmissions[] = $row;
            }
        }
        if (in_array('volunteers', $types)) {
            $r = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) AS name, email, CONCAT(profession,' - ',opportunity) AS detail, status, created_at FROM volunteer_applications ORDER BY created_at DESC LIMIT " . intval($limit));
            if ($r) while ($row = $r->fetch_assoc()) {
                $row['type'] = 'volunteers';
                $allSubmissions[] = $row;
            }
        }
        if (in_array('applications', $types)) {
            $r = $conn->query("SELECT id, CONCAT(first_name,' ',surname) AS name, email, program_applied AS detail, status, submitted_at AS created_at FROM student_applications ORDER BY submitted_at DESC LIMIT " . intval($limit));
            if ($r) while ($row = $r->fetch_assoc()) {
                $row['type'] = 'applications';
                $allSubmissions[] = $row;
            }
        }

        usort($allSubmissions, function($a, $b) {
            return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
        });

        if (empty($allSubmissions)) {
            echo '<div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2" style="color:#10b981;"></i><p>No submissions found.</p></div>';
            return;
        }

        echo '<div class="table-responsive" style="max-height:400px;overflow-y:auto">';
        echo '<table class="table table-sm table-hover" style="font-size:12px;margin-bottom:0">';
        echo '<thead><tr style="position:sticky;top:0;background:#f8fafc;z-index:1;">';
        echo '<th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;">Type</th>';
        echo '<th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;">Name</th>';
        echo '<th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;">Email</th>';
        echo '<th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;">Detail</th>';
        echo '<th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;">Date</th>';
        echo '<th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;">Status</th>';
        echo '<th style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;font-weight:600;padding:7px 10px;">Actions</th>';
        echo '</tr></thead><tbody>';

        foreach ($allSubmissions as $sub) {
            $t = $sub['type'];
            $statusKey = $sub['status'] ?? '';
            $statusCls = $statusMap[$t][$statusKey] ?? 'secondary';
            $date = !empty($sub['created_at']) ? date('d M Y H:i', strtotime($sub['created_at'])) : '-';
            $email = htmlspecialchars($sub['email'] ?? '-');
            $name = htmlspecialchars($sub['name'] ?? '-');
            $detail = htmlspecialchars($sub['detail'] ?? '-');
            $id = intval($sub['id'] ?? 0);

            echo '<tr>';
            echo '<td><span style="' . $badgeColors[$t] . 'font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;display:inline-flex;align-items:center;gap:4px;"><i class="fas ' . $icons[$t] . '" style="font-size:9px;"></i>' . $badgeLabels[$t] . '</span></td>';
            echo '<td style="font-weight:600;">' . $name . '</td>';
            echo '<td style="color:#64748b;">' . $email . '</td>';
            echo '<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' . $detail . '</td>';
            echo '<td style="color:#64748b;white-space:nowrap;">' . $date . '</td>';
            echo '<td><span class="badge bg-' . $statusCls . '" style="font-size:10px;">' . htmlspecialchars($statusKey) . '</span></td>';
            echo '<td style="white-space:nowrap;">';
            // Approve action
            echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Approve this submission?\')">';
            echo '<input type="hidden" name="ws_action" value="approve">';
            echo '<input type="hidden" name="ws_type" value="' . htmlspecialchars($t) . '">';
            echo '<input type="hidden" name="ws_id" value="' . $id . '">';
            echo '<button class="btn btn-sm" style="color:#059669;border:none;background:none;padding:2px 4px;" title="Approve"><i class="fas fa-check-circle"></i></button>';
            echo '</form>';
            // Resolve action
            echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Mark as resolved?\')">';
            echo '<input type="hidden" name="ws_action" value="resolve">';
            echo '<input type="hidden" name="ws_type" value="' . htmlspecialchars($t) . '">';
            echo '<input type="hidden" name="ws_id" value="' . $id . '">';
            echo '<button class="btn btn-sm" style="color:#2563eb;border:none;background:none;padding:2px 4px;" title="Resolve"><i class="fas fa-check-double"></i></button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }
}

if (!function_exists('handleWebsiteSubmissionsAction')) {
    function handleWebsiteSubmissionsAction($conn) {
        if (!$conn || $_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['ws_action'])) {
            return false;
        }

        $action = $_POST['ws_action'];
        $type = $_POST['ws_type'] ?? '';
        $id = intval($_POST['ws_id'] ?? 0);

        if (!$id || !$type) return false;

        if ($action === 'approve') {
            $tableMap = ['contacts' => ['contact_submissions', 'Read'], 'volunteers' => ['volunteer_applications', 'approved'], 'donations' => ['donations', 'verified'], 'applications' => ['student_applications', 'Approved']];
            if (isset($tableMap[$type])) {
                list($tbl, $newStatus) = $tableMap[$type];
                $stmt = $conn->prepare("UPDATE `$tbl` SET status=? WHERE id=?");
                if ($stmt) { $stmt->bind_param('si', $newStatus, $id); $stmt->execute(); $stmt->close(); }
            }
            $_SESSION['ws_success'] = ucfirst($type) . ' submission approved.';
        } elseif ($action === 'resolve') {
            $tableMap = ['contacts' => ['contact_submissions', 'Responded'], 'volunteers' => ['volunteer_applications', 'resolved'], 'donations' => ['donations', 'verified'], 'applications' => ['student_applications', 'Resolved']];
            if (isset($tableMap[$type])) {
                list($tbl, $newStatus) = $tableMap[$type];
                $stmt = $conn->prepare("UPDATE `$tbl` SET status=? WHERE id=?");
                if ($stmt) { $stmt->bind_param('si', $newStatus, $id); $stmt->execute(); $stmt->close(); }
            }
            $_SESSION['ws_success'] = ucfirst($type) . ' submission resolved.';
        }

        return true;
    }
}
