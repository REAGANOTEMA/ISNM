<?php
/**
 * Director General Notifications Center
 * Aggregates notifications from all system modules into a unified tabbed feed.
 *
 * Requires these variables in the parent scope:
 *   $conn         â€” staffs_db (igangaschool_staffs)
 *   $studentsConn â€” students_db (igangaschool_students)
 *   $websiteConn  â€” website_db (igangaschool_website)
 *
 * Usage (inside DG dashboard):
 *   include __DIR__ . '/../includes/dg_notifications_center.php';
 *   renderNotificationsCenter($conn, $studentsConn, $websiteConn, $user_id);
 */
if (!function_exists('renderNotificationsCenter')):

/**
 * Ensures the read-tracking table exists in staffs_db.
 */
function dgEnsureReadTable($conn): void {
    if (!$conn) return;
    $conn->query("CREATE TABLE IF NOT EXISTS dg_read_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notification_key VARCHAR(64) NOT NULL,
        user_id INT NOT NULL,
        read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_nk_uid (notification_key, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Returns a relative time string ("just now", "5m ago", "2h ago", "Yesterday", etc.)
 */
function dgRelativeTime(string $datetime): string {
    if (!$datetime) return '';
    $ts = strtotime($datetime);
    if (!$ts) return '';
    $diff = time() - $ts;
    if ($diff < 0) $diff = 0;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 7200) return '1h ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    $yesterday = strtotime('yesterday');
    if ($ts >= $yesterday && $ts < strtotime('today')) return 'Yesterday';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    if ($diff < 2592000) return floor($diff / 604800) . 'w ago';
    if ($diff < 31536000) return date('M j', $ts);
    return date('M j, Y', $ts);
}

/**
 * Marks a notification as read via AJAX POST.
 * Clears output buffers to allow clean JSON response mid-page.
 */
function dgHandleMarkRead($conn, $userId): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['dg_notif_action'])) return;
    $action = $_POST['dg_notif_action'];
    $uid = (int)($_POST['dg_uid'] ?? $userId);
    if (!$uid) return;
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    if ($action === 'mark_read' && !empty($_POST['notif_key'])) {
        $key = $_POST['notif_key'];
        if ($conn) {
            $stmt = $conn->prepare("INSERT IGNORE INTO dg_read_notifications (notification_key, user_id) VALUES (?, ?)");
            $stmt->bind_param("si", $key, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        echo json_encode(['ok' => true]);
        exit;
    }
    if ($action === 'mark_all_read' && !empty($_POST['keys'])) {
        $keys = json_decode($_POST['keys'], true);
        if (is_array($keys) && $conn) {
            $stmt = $conn->prepare("INSERT IGNORE INTO dg_read_notifications (notification_key, user_id) VALUES (?, ?)");
            foreach ($keys as $k) {
                $stmt->bind_param("si", $k, $userId);
                if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            }
            $stmt->close();
        }
        echo json_encode(['ok' => true]);
        exit;
    }
    if ($action === 'delete_notification' && !empty($_POST['notif_key'])) {
        $key = $_POST['notif_key'];
        if ($conn) {
            $stmt = $conn->prepare("INSERT IGNORE INTO dg_read_notifications (notification_key, user_id) VALUES (?, ?)");
            $stmt->bind_param("si", $key, $userId);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
            $stmt->close();
        }
        echo json_encode(['ok' => true]);
        exit;
    }
    if ($action === 'delete_all_notifications') {
        if ($conn) {
            $conn->query("DELETE FROM dg_read_notifications WHERE user_id=" . (int)$userId);
        }
        echo json_encode(['ok' => true]);
        exit;
    }
}

/**
 * Gathers all notifications from every source and returns a sorted array.
 */
function dgGatherNotifications($conn, $studentsConn, $websiteConn, int $userId): array {
    $items = [];
    $readKeys = [];

    // Load previously-read notification keys for this user
    if ($conn) {
        $rkStmt = $conn->prepare("SELECT notification_key FROM dg_read_notifications WHERE user_id = ?");
        $rkStmt->bind_param("i", $userId);
        if (!$rkStmt->execute()) { error_log('$rkStmt execute failed: ' . ($rkStmt->error ?? 'unknown')); };
        $rk = $rkStmt->get_result();
        if ($rk) {
            while ($row = $rk->fetch_assoc()) {
                $readKeys[$row['notification_key']] = true;
            }
        }
    }
    $isRead = function(string $key) use ($readKeys): int {
        return isset($readKeys[$key]) ? 1 : 0;
    };

    // â”€â”€ 1. director_news (staffs_db) â”€â”€
    if ($conn) {
        $r = $conn->query("SELECT id, title, content AS message, created_at, 'published' AS status FROM director_news WHERE status='published' ORDER BY created_at DESC LIMIT 30");
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $key = 'news_' . $row['id'];
                $items[] = [
                    'key' => $key,
                    'tab' => 'all',
                    'type' => 'news',
                    'title' => $row['title'],
                    'message' => mb_substr(strip_tags($row['message'] ?? ''), 0, 200),
                    'created_at' => $row['created_at'],
                    'is_read' => $isRead($key),
                    'icon' => 'fas fa-newspaper',
                    'color' => '#0891b2',
                    'url' => 'director-general.php?page=news',
                ];
            }
        }
    }

    // â”€â”€ 2. staff_activity_log (staffs_db) â”€â”€
    if ($conn) {
        $r = $conn->query("SELECT id, activity_type, activity_description, created_at FROM staff_activity_log ORDER BY created_at DESC LIMIT 50");
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $key = 'activity_' . $row['id'];
                $atype = strtolower($row['activity_type'] ?? '');
                $tab = 'all';
                $color = '#64748b';
                $icon = 'fas fa-history';
                if (stripos($atype, 'login') !== false || stripos($atype, 'security') !== false || stripos($atype, 'auth') !== false) {
                    $tab = 'security';
                    $color = '#dc2626';
                    $icon = 'fas fa-shield-alt';
                } elseif (stripos($atype, 'staff') !== false || stripos($atype, 'leave') !== false || stripos($atype, 'hire') !== false || stripos($atype, 'recruit') !== false) {
                    $tab = 'hr';
                    $color = '#7c3aed';
                    $icon = 'fas fa-users';
                } elseif (stripos($atype, 'payment') !== false || stripos($atype, 'fee') !== false || stripos($atype, 'finance') !== false || stripos($atype, 'budget') !== false) {
                    $tab = 'finance';
                    $color = '#059669';
                    $icon = 'fas fa-coins';
                } elseif (stripos($atype, 'student') !== false || stripos($atype, 'discipline') !== false || stripos($atype, 'enrol') !== false) {
                    $tab = 'students';
                    $color = '#2563eb';
                    $icon = 'fas fa-user-graduate';
                } elseif (stripos($atype, 'admission') !== false || stripos($atype, 'applic') !== false) {
                    $tab = 'admissions';
                    $color = '#3b82f6';
                    $icon = 'fas fa-file-alt';
                }
                $items[] = [
                    'key' => $key,
                    'tab' => $tab,
                    'type' => 'activity',
                    'title' => $row['activity_type'],
                    'message' => $row['activity_description'] ?? '',
                    'created_at' => $row['created_at'],
                    'is_read' => $isRead($key),
                    'icon' => $icon,
                    'color' => $color,
                    'url' => 'director-general.php?page=audit',
                ];
            }
        }
    }

    // â”€â”€ 3. announcements (students_db via $studentsConn or $conn FQN) â”€â”€
    $annConn = $studentsConn ?: $conn;
    if ($annConn) {
        $db = defined('DB_STUDENTS_RAW') ? DB_STUDENTS_RAW : 'igangaschool_students';
        $from = $studentsConn ? 'announcements' : $db . '.announcements';
        try {
            $r = $annConn->query("SELECT id, title, body, target_audience, created_at FROM $from ORDER BY id DESC LIMIT 30");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $key = 'announcement_' . $row['id'];
                    $target = strtolower($row['target_audience'] ?? 'all');
                    $tab = 'all';
                    if ($target === 'students' || $target === 'student') $tab = 'students';
                    elseif ($target === 'staff') $tab = 'hr';
                    $items[] = [
                        'key' => $key,
                        'tab' => $tab,
                        'type' => 'announcement',
                        'title' => $row['title'],
                        'message' => mb_substr(strip_tags($row['body'] ?? ''), 0, 200),
                        'created_at' => $row['created_at'],
                        'is_read' => $isRead($key),
                        'icon' => 'fas fa-bullhorn',
                        'color' => '#f59e0b',
                        'url' => 'director-general.php?page=communications',
                    ];
                }
            }
        } catch (Exception $e) { error_log('dg_notifications announcements: ' . $e->getMessage()); }
    }

    // â”€â”€ 4. student_applications (website_db) â”€â”€
    if ($websiteConn) {
        try {
            $r = $websiteConn->query("SELECT id, first_name, surname, program_applied, submitted_at, status FROM student_applications WHERE status IN ('Pending','Approved','Rejected') ORDER BY submitted_at DESC LIMIT 30");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $key = 'application_' . $row['id'];
                    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['surname'] ?? ''));
                    $statusBadge = strtolower($row['status'] ?? '');
                    $items[] = [
                        'key' => $key,
                        'tab' => 'admissions',
                        'type' => 'application',
                        'title' => "New Application: $name",
                        'message' => ($row['program_applied'] ?? '') . ' â€” ' . ($row['status'] ?? 'Pending'),
                        'created_at' => $row['submitted_at'],
                        'is_read' => $isRead($key),
                        'icon' => 'fas fa-file-alt',
                        'color' => '#3b82f6',
                        'url' => 'director-general.php?page=submissions',
                    ];
                }
            }
        } catch (Exception $e) { error_log('dg_notifications applications: ' . $e->getMessage()); }
    }

    // â”€â”€ 5. contact_submissions (website_db) â”€â”€
    if ($websiteConn) {
        try {
            $r = $websiteConn->query("SELECT id, first_name, last_name, subject, created_at, status FROM contact_submissions WHERE status IN ('unread','resolved') ORDER BY created_at DESC LIMIT 30");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $key = 'contact_' . $row['id'];
                    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $items[] = [
                        'key' => $key,
                        'tab' => 'all',
                        'type' => 'contact',
                        'title' => "Message from $name",
                        'message' => $row['subject'] ?? 'No subject',
                        'created_at' => $row['created_at'],
                        'is_read' => $isRead($key),
                        'icon' => 'fas fa-envelope',
                        'color' => '#8b5cf6',
                        'url' => 'director-general.php?page=submissions',
                    ];
                }
            }
        } catch (Exception $e) { error_log('dg_notifications contacts: ' . $e->getMessage()); }
    }

    // â”€â”€ 6. volunteer_applications (website_db) â”€â”€
    if ($websiteConn) {
        try {
            $r = $websiteConn->query("SELECT id, first_name, last_name, profession, opportunity, created_at, status FROM volunteer_applications WHERE status IN ('pending','approved','rejected') ORDER BY created_at DESC LIMIT 30");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $key = 'volunteer_' . $row['id'];
                    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $items[] = [
                        'key' => $key,
                        'tab' => 'all',
                        'type' => 'volunteer',
                        'title' => "Volunteer: $name",
                        'message' => ($row['profession'] ?? '') . ' â€” ' . ($row['opportunity'] ?? ''),
                        'created_at' => $row['created_at'],
                        'is_read' => $isRead($key),
                        'icon' => 'fas fa-hands-helping',
                        'color' => '#d97706',
                        'url' => 'director-general.php?page=submissions',
                    ];
                }
            }
        } catch (Exception $e) { error_log('dg_notifications volunteers: ' . $e->getMessage()); }
    }

    // â”€â”€ 7. donations (website_db) â”€â”€
    if ($websiteConn) {
        try {
            $r = $websiteConn->query("SELECT id, donor_name, amount, created_at, status FROM donations WHERE status IN ('pending','verified','cancelled') ORDER BY created_at DESC LIMIT 30");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $key = 'donation_' . $row['id'];
                    $amount = number_format($row['amount'] ?? 0);
                    $items[] = [
                        'key' => $key,
                        'tab' => 'finance',
                        'type' => 'donation',
                        'title' => "Donation from {$row['donor_name']}",
                        'message' => "UGX $amount â€” {$row['status']}",
                        'created_at' => $row['created_at'],
                        'is_read' => $isRead($key),
                        'icon' => 'fas fa-hand-holding-heart',
                        'color' => '#059669',
                        'url' => 'director-general.php?page=submissions',
                    ];
                }
            }
        } catch (Exception $e) { error_log('dg_notifications donations: ' . $e->getMessage()); }
    }

    // â”€â”€ 8. notifications table (staffs_db) â”€â”€
    if ($conn) {
        try {
            $r = $conn->query("SELECT id, notification_type, title, message, created_at FROM notifications ORDER BY created_at DESC LIMIT 30");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $key = 'staff_notif_' . $row['id'];
                    $ntype = strtolower($row['notification_type'] ?? 'info');
                    $tab = 'all';
                    $color = '#64748b';
                    $icon = 'fas fa-bell';
                    if (stripos($ntype, 'payment') !== false || stripos($ntype, 'fee') !== false || stripos($ntype, 'finance') !== false) {
                        $tab = 'finance'; $color = '#059669'; $icon = 'fas fa-coins';
                    } elseif (stripos($ntype, 'staff') !== false || stripos($ntype, 'leave') !== false || stripos($ntype, 'hr') !== false) {
                        $tab = 'hr'; $color = '#7c3aed'; $icon = 'fas fa-users';
                    } elseif (stripos($ntype, 'student') !== false || stripos($ntype, 'discipline') !== false) {
                        $tab = 'students'; $color = '#2563eb'; $icon = 'fas fa-user-graduate';
                    } elseif (stripos($ntype, 'admission') !== false || stripos($ntype, 'applic') !== false) {
                        $tab = 'admissions'; $color = '#3b82f6'; $icon = 'fas fa-file-alt';
                    } elseif (stripos($ntype, 'login') !== false || stripos($ntype, 'security') !== false) {
                        $tab = 'security'; $color = '#dc2626'; $icon = 'fas fa-shield-alt';
                    }
                    $items[] = [
                        'key' => $key,
                        'tab' => $tab,
                        'type' => $ntype,
                        'title' => $row['title'] ?? '',
                        'message' => mb_substr(strip_tags($row['message'] ?? ''), 0, 200),
                        'created_at' => $row['created_at'],
                        'is_read' => $isRead($key),
                        'icon' => $icon,
                        'color' => $color,
                        'url' => '#',
                    ];
                }
            }
        } catch (Exception $e) { error_log('dg_notifications staffNotif: ' . $e->getMessage()); }
    }

    // â”€â”€ 9. notifications table (website_db) â”€â”€
    if ($websiteConn) {
        try {
            $r = $websiteConn->query("SELECT id, title, message, url, type, icon, created_at FROM notifications ORDER BY created_at DESC LIMIT 30");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $key = 'web_notif_' . $row['id'];
                    $ntype = strtolower($row['type'] ?? 'info');
                    $tab = 'all';
                    $color = '#64748b';
                    $icon = $row['icon'] ?: 'fas fa-bell';
                    if (stripos($ntype, 'payment') !== false || stripos($ntype, 'fee') !== false || stripos($ntype, 'finance') !== false) {
                        $tab = 'finance'; $color = '#059669'; $icon = 'fas fa-coins';
                    } elseif (stripos($ntype, 'staff') !== false || stripos($ntype, 'leave') !== false || stripos($ntype, 'hr') !== false) {
                        $tab = 'hr'; $color = '#7c3aed'; $icon = 'fas fa-users';
                    } elseif (stripos($ntype, 'student') !== false || stripos($ntype, 'discipline') !== false) {
                        $tab = 'students'; $color = '#2563eb'; $icon = 'fas fa-user-graduate';
                    } elseif (stripos($ntype, 'admission') !== false || stripos($ntype, 'applic') !== false) {
                        $tab = 'admissions'; $color = '#3b82f6'; $icon = 'fas fa-file-alt';
                    } elseif (stripos($ntype, 'login') !== false || stripos($ntype, 'security') !== false || $ntype === 'danger') {
                        $tab = 'security'; $color = '#dc2626'; $icon = 'fas fa-shield-alt';
                    }
                    $items[] = [
                        'key' => $key,
                        'tab' => $tab,
                        'type' => $ntype,
                        'title' => $row['title'] ?? '',
                        'message' => mb_substr(strip_tags($row['message'] ?? ''), 0, 200),
                        'created_at' => $row['created_at'],
                        'is_read' => $isRead($key),
                        'icon' => $icon,
                        'color' => $color,
                        'url' => $row['url'] ?? '#',
                    ];
                }
            }
        } catch (Exception $e) { error_log('dg_notifications webNotif: ' . $e->getMessage()); }
    }

    // â”€â”€ 10. alerts (staffs_db) â€” active alerts â”€â”€
    if ($conn) {
        try {
            $r = $conn->query("SELECT id, alert_type, message, status, created_at FROM alerts WHERE status IN ('active','critical') ORDER BY FIELD(status,'critical','active'), created_at DESC LIMIT 30");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $key = 'alert_' . $row['id'];
                    $atype = strtolower($row['alert_type'] ?? 'general');
                    $tab = 'security';
                    $color = '#dc2626';
                    $icon = 'fas fa-exclamation-triangle';
                    if (stripos($atype, 'payment') !== false || stripos($atype, 'fee') !== false) {
                        $tab = 'finance'; $color = '#059669'; $icon = 'fas fa-coins';
                    }
                    $items[] = [
                        'key' => $key,
                        'tab' => $tab,
                        'type' => 'alert',
                        'title' => 'Alert: ' . ($row['alert_type'] ?? 'General'),
                        'message' => $row['message'] ?? '',
                        'created_at' => $row['created_at'],
                        'is_read' => $isRead($key),
                        'icon' => $icon,
                        'color' => $color,
                        'url' => 'director-general.php?page=audit',
                    ];
                }
            }
        } catch (Exception $e) { error_log('dg_notifications alerts: ' . $e->getMessage()); }
    }

    // â”€â”€ 11. student_notifications (students_db) â€” recent student-facing notifications â”€â”€
    if ($studentsConn) {
        try {
            $r = $studentsConn->query("SELECT id, type, title, message, created_at FROM student_notifications ORDER BY id DESC LIMIT 30");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $key = 'student_notif_' . $row['id'];
                    $items[] = [
                        'key' => $key,
                        'tab' => 'students',
                        'type' => 'student_notification',
                        'title' => $row['title'] ?? '',
                        'message' => mb_substr(strip_tags($row['message'] ?? ''), 0, 200),
                        'created_at' => $row['created_at'],
                        'is_read' => $isRead($key),
                        'icon' => 'fas fa-graduation-cap',
                        'color' => '#2563eb',
                        'url' => '#',
                    ];
                }
            }
        } catch (Exception $e) { error_log('dg_notifications studentNotif: ' . $e->getMessage()); }
    }

    // â”€â”€ Sort by created_at DESC â”€â”€
    usort($items, function ($a, $b) {
        return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
    });

    return $items;
}

/**
 * Main render function â€” outputs the complete Notifications Center HTML.
 */
function renderNotificationsCenter($conn, $studentsConn, $websiteConn, int $userId): void {
    // Handle AJAX mark-read actions (clears output buffers for clean JSON response)
    dgHandleMarkRead($conn, $userId);

    // Ensure read tracking table exists
    dgEnsureReadTable($conn);

    // Gather all notifications
    $notifications = dgGatherNotifications($conn, $studentsConn, $websiteConn, $userId);

    // Compute counts per tab
    $counts = ['all' => count($notifications), 'admissions' => 0, 'finance' => 0, 'hr' => 0, 'students' => 0, 'security' => 0];
    $unreadCounts = ['all' => 0, 'admissions' => 0, 'finance' => 0, 'hr' => 0, 'students' => 0, 'security' => 0];
    foreach ($notifications as $n) {
        $t = $n['tab'];
        if (isset($counts[$t])) $counts[$t]++;
        if (!$n['is_read']) {
            $unreadCounts['all']++;
            if (isset($unreadCounts[$t])) $unreadCounts[$t]++;
        }
    }

    $tabs = [
        'all'        => ['label' => 'All',        'icon' => 'fa-bell',          'color' => '#0f172a'],
        'admissions' => ['label' => 'Admissions', 'icon' => 'fa-file-alt',      'color' => '#3b82f6'],
        'finance'    => ['label' => 'Finance',    'icon' => 'fa-coins',         'color' => '#059669'],
        'hr'         => ['label' => 'HR',         'icon' => 'fa-users',         'color' => '#7c3aed'],
        'students'   => ['label' => 'Students',   'icon' => 'fa-user-graduate', 'color' => '#2563eb'],
        'security'   => ['label' => 'Security',   'icon' => 'fa-shield-alt',    'color' => '#dc2626'],
    ];
?>
<style>
.dg-nc-container {
    font-family: 'Inter', -apple-system, sans-serif;
    font-size: 13px;
    color: #0f172a;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border: 1px solid #f1f5f9;
    overflow: hidden;
}
.dg-nc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    padding: 14px 18px 8px;
    border-bottom: 1px solid #e2e8f0;
}
.dg-nc-header h3 {
    font-size: 15px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0f172a;
}
.dg-nc-header h3 i { font-size: 16px; color: #3b82f6; }
.dg-nc-actions {
    display: flex;
    gap: 6px;
    align-items: center;
}
.dg-nc-btn {
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.dg-nc-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
.dg-nc-btn-primary { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.dg-nc-btn-primary:hover { background: #2563eb; }

.dg-nc-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    padding: 8px 18px 0;
    border-bottom: 1px solid #e2e8f0;
    background: #fafbfc;
}
.dg-nc-tab {
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    color: #64748b;
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    position: relative;
    background: transparent;
    margin-bottom: -1px;
}
.dg-nc-tab:hover { color: #0f172a; background: #f1f5f9; }
.dg-nc-tab.active {
    color: #0f172a;
    background: #fff;
    border-color: #e2e8f0;
    box-shadow: 0 -1px 3px rgba(0,0,0,0.03);
}
.dg-nc-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    color: #fff;
}
.dg-nc-tab .dg-nc-total { background: #e2e8f0; color: #475569; }
.dg-nc-tab.active .dg-nc-total { background: #e2e8f0; color: #475569; }

.dg-nc-body {
    padding: 0;
    max-height: 600px;
    overflow-y: auto;
}
.dg-nc-body::-webkit-scrollbar { width: 4px; }
.dg-nc-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.dg-nc-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}
.dg-nc-empty i { font-size: 32px; margin-bottom: 10px; color: #cbd5e1; }

.dg-nc-item {
    display: flex;
    gap: 12px;
    padding: 12px 18px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
    cursor: default;
    align-items: flex-start;
    position: relative;
}
.dg-nc-item:hover { background: #f8fafc; }
.dg-nc-item.dg-nc-read { opacity: 0.65; }
.dg-nc-item.dg-nc-read .dg-nc-item-title { font-weight: 500; }
.dg-nc-item.dg-nc-unread { background: #fafbff; border-left: 3px solid #3b82f6; }
.dg-nc-item.dg-nc-unread .dg-nc-item-title { font-weight: 700; }

.dg-nc-item-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 15px;
    color: #fff;
}
.dg-nc-item-content { flex: 1; min-width: 0; }
.dg-nc-item-title {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 2px;
    line-height: 1.3;
}
.dg-nc-item-message {
    font-size: 12px;
    color: #64748b;
    line-height: 1.4;
    margin-bottom: 3px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.dg-nc-item-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: #94a3b8;
}
.dg-nc-item-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}
.dg-nc-mark-read {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.15s;
}
.dg-nc-mark-read:hover { background: #e2e8f0; color: #3b82f6; }
.dg-nc-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 3px;
}

.dg-nc-footer {
    padding: 10px 18px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
    font-size: 11px;
    color: #94a3b8;
    background: #fafbfc;
}

@media (max-width: 600px) {
    .dg-nc-header { flex-direction: column; align-items: flex-start; }
    .dg-nc-tabs { overflow-x: auto; flex-wrap: nowrap; }
    .dg-nc-tab { white-space: nowrap; }
    .dg-nc-item { padding: 10px 12px; gap: 8px; }
    .dg-nc-item-icon { width: 30px; height: 30px; font-size: 12px; }
}
</style>

<div class="dg-nc-container animate__animated animate__fadeIn">
    <div class="dg-nc-header">
        <h3><i class="fas fa-bell"></i> Notifications Center</h3>
        <div class="dg-nc-actions">
            <button class="dg-nc-btn dg-nc-btn-primary" onclick="dgMarkAllRead()" id="dgMarkAllBtn">
                <i class="fas fa-check-double"></i> Clear All
            </button>
            <button class="dg-nc-btn" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <div class="dg-nc-tabs" id="dgNcTabs">
        <?php foreach ($tabs as $key => $tab):
            $total = $counts[$key] ?? 0;
            $unread = $unreadCounts[$key] ?? 0;
            $badgeColor = $key === 'all' ? '#3b82f6' : $tab['color'];
        ?>
        <div class="dg-nc-tab <?= $key === 'all' ? 'active' : '' ?>" data-tab="<?= $key ?>" onclick="dgSwitchTab('<?= $key ?>')">
            <i class="fas <?= $tab['icon'] ?>" style="color:<?= $tab['color'] ?>"></i>
            <?= $tab['label'] ?>
            <?php if ($unread > 0): ?>
            <span class="dg-nc-tab-badge" style="background:<?= $badgeColor ?>"><?= $unread > 99 ? '99+' : $unread ?></span>
            <?php elseif ($total > 0): ?>
            <span class="dg-nc-tab-badge dg-nc-total"><?= $total > 99 ? '99+' : $total ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="dg-nc-body" id="dgNcBody">
        <?php if (empty($notifications)): ?>
        <div class="dg-nc-empty">
            <i class="fas fa-check-circle"></i>
            <p>All caught up! No notifications to display.</p>
        </div>
        <?php else: ?>
        <?php foreach ($notifications as $n):
            $cls = $n['is_read'] ? 'dg-nc-read' : 'dg-nc-unread';
            $ts = dgRelativeTime($n['created_at'] ?? '');
        ?>
        <div class="dg-nc-item <?= $cls ?>" data-key="<?= htmlspecialchars($n['key']) ?>" data-tab="<?= $n['tab'] ?>">
            <div class="dg-nc-item-icon" style="background:<?= $n['color'] ?>">
                <i class="<?= $n['icon'] ?>"></i>
            </div>
            <div class="dg-nc-item-content" onclick="dgNavigateTo('<?= htmlspecialchars($n['url'], ENT_QUOTES) ?>')" style="cursor:pointer;">
                <div class="dg-nc-item-title"><?= htmlspecialchars($n['title']) ?></div>
                <?php if (!empty($n['message'])): ?>
                <div class="dg-nc-item-message"><?= htmlspecialchars($n['message']) ?></div>
                <?php endif; ?>
                <div class="dg-nc-item-meta">
                    <i class="far fa-clock"></i> <?= $ts ?>
                    <?php if (!$n['is_read']): ?>
                    <span class="badge bg-primary rounded-pill" style="font-size:9px;">New</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dg-nc-item-actions">
                <?php if (!$n['is_read']): ?>
                <button class="dg-nc-mark-read" onclick="dgMarkRead('<?= htmlspecialchars($n['key'], ENT_QUOTES) ?>', this)" title="Mark as read">
                    <i class="fas fa-check-circle"></i>
                </button>
                <?php endif; ?>
                <button class="dg-nc-delete" onclick="dgDeleteNotif('<?= htmlspecialchars($n['key'], ENT_QUOTES) ?>', this)" title="Delete" style="background:none;border:none;color:#dc2626;cursor:pointer;padding:4px;font-size:13px;">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="dg-nc-footer">
        <div class="d-flex justify-content-between align-items-center w-100">
            <span><i class="fas fa-sync-alt me-1"></i> Auto-updates every 60s &middot;
            <strong><?= $unreadCounts['all'] ?></strong> unread of <strong><?= $counts['all'] ?></strong> total</span>
            <div class="d-flex gap-2">
                <button id="dgMarkAllBtn" class="btn btn-sm btn-outline-primary" onclick="dgMarkAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
                <button class="btn btn-sm btn-outline-danger" onclick="dgDeleteAllNotifs()"><i class="fas fa-trash-alt"></i> Clear All</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var dgNotifData = {
        userId: <?= $userId ?>,
        allKeys: <?= json_encode(array_map(function($n) { return $n['key']; }, $notifications)) ?>,
        refreshInterval: null
    };

    window.dgSwitchTab = function(tab) {
        document.querySelectorAll('.dg-nc-tab').forEach(function(el) {
            el.classList.toggle('active', el.dataset.tab === tab);
        });
        document.querySelectorAll('.dg-nc-item').forEach(function(el) {
            var itemTab = el.dataset.tab;
            el.style.display = (tab === 'all' || itemTab === tab) ? '' : 'none';
        });
    };

    window.dgMarkRead = function(key, btn) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var item = btn ? btn.closest('.dg-nc-item') : null;
                if (item) {
                    item.classList.remove('dg-nc-unread');
                    item.classList.add('dg-nc-read');
                    var badge = item.querySelector('.badge');
                    if (badge) badge.remove();
                    var actionBtn = item.querySelector('.dg-nc-mark-read');
                    if (actionBtn) actionBtn.remove();
                }
                dgUpdateCounts();
            }
        };
        xhr.send('dg_notif_action=mark_read&notif_key=' + encodeURIComponent(key) + '&dg_uid=' + dgNotifData.userId);
    };

    window.dgMarkAllRead = function() {
        var unreadItems = document.querySelectorAll('.dg-nc-item.dg-nc-unread');
        if (unreadItems.length === 0) return;
        var keys = [];
        unreadItems.forEach(function(item) {
            keys.push(item.dataset.key);
        });
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.querySelectorAll('.dg-nc-item.dg-nc-unread').forEach(function(item) {
                    item.classList.remove('dg-nc-unread');
                    item.classList.add('dg-nc-read');
                    var badge = item.querySelector('.badge');
                    if (badge) badge.remove();
                    var actionBtn = item.querySelector('.dg-nc-mark-read');
                    if (actionBtn) actionBtn.remove();
                });
                dgUpdateCounts();
                document.getElementById('dgMarkAllBtn').innerHTML = '<i class="fas fa-check-double"></i> All Read';
                document.getElementById('dgMarkAllBtn').disabled = true;
            }
        };
        xhr.send('dg_notif_action=mark_all_read&keys=' + encodeURIComponent(JSON.stringify(keys)) + '&dg_uid=' + dgNotifData.userId);
    };

    window.dgDeleteNotif = function(key, btn) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var item = btn ? btn.closest('.dg-nc-item') : null;
                if (item) {
                    item.style.transition = 'opacity 0.3s, max-height 0.3s';
                    item.style.opacity = '0';
                    item.style.maxHeight = '0';
                    item.style.overflow = 'hidden';
                    setTimeout(function() { item.remove(); dgUpdateCounts(); }, 300);
                }
            }
        };
        xhr.send('dg_notif_action=delete_notification&notif_key=' + encodeURIComponent(key) + '&dg_uid=' + dgNotifData.userId);
    };

    window.dgDeleteAllNotifs = function() {
        if (!confirm('Remove all notifications from view?')) return;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.querySelectorAll('.dg-nc-item').forEach(function(item) {
                    item.style.transition = 'opacity 0.3s';
                    item.style.opacity = '0';
                    setTimeout(function() { item.remove(); }, 300);
                });
                setTimeout(function() { dgUpdateCounts(); }, 350);
            }
        };
        xhr.send('dg_notif_action=delete_all_notifications&dg_uid=' + dgNotifData.userId);
    };

    window.dgNavigateTo = function(url) {
        if (url && url !== '#') window.location.href = url;
    };

    function dgUpdateCounts() {
        var totalUnread = 0;
        var counts = { all: 0, admissions: 0, finance: 0, hr: 0, students: 0, security: 0 };
        var unreadCounts = { all: 0, admissions: 0, finance: 0, hr: 0, students: 0, security: 0 };

        document.querySelectorAll('.dg-nc-item').forEach(function(item) {
            if (item.style.display === 'none') return;
            var tab = item.dataset.tab;
            if (counts[tab] !== undefined) counts[tab]++;
            counts.all++;
            if (!item.classList.contains('dg-nc-read')) {
                if (unreadCounts[tab] !== undefined) unreadCounts[tab]++;
                unreadCounts.all++;
            }
        });

        totalUnread = unreadCounts.all;

        document.querySelectorAll('.dg-nc-tab').forEach(function(el) {
            var tab = el.dataset.tab;
            var existingBadge = el.querySelector('.dg-nc-tab-badge');
            var unread = unreadCounts[tab] || 0;
            var total = counts[tab] || 0;
            if (existingBadge) existingBadge.remove();
            var badgeHtml = '';
            if (unread > 0) {
                var colors = { all:'#3b82f6', admissions:'#3b82f6', finance:'#059669', hr:'#7c3aed', students:'#2563eb', security:'#dc2626' };
                badgeHtml = '<span class="dg-nc-tab-badge" style="background:' + (colors[tab]||'#3b82f6') + '">' + (unread > 99 ? '99+' : unread) + '</span>';
            } else if (total > 0) {
                badgeHtml = '<span class="dg-nc-tab-badge dg-nc-total">' + (total > 99 ? '99+' : total) + '</span>';
            }
            if (badgeHtml) el.insertAdjacentHTML('beforeend', badgeHtml);
        });

        var footer = document.querySelector('.dg-nc-footer');
        if (footer) {
            footer.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Auto-updates every 60s &middot; <strong>' + totalUnread + '</strong> unread of <strong>' + counts.all + '</strong> total';
        }
    }

    // Auto-refresh every 60 seconds
    dgNotifData.refreshInterval = setInterval(function() {
        location.reload();
    }, 60000);
})();
</script>
<?php
}
endif;
?>
