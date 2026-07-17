<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$authData = bootstrapStaffDashboard(['events coordinator','events manager','events']);
$auth = $authData['auth'];
$staffConn = $authData['staff'];
$user = $authData['user'];
$userId = $user['id'] ?? 0;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Ã¢â€â‚¬Ã¢â€â‚¬ Ensure tables exist Ã¢â€â‚¬Ã¢â€â‚¬
$staffConn->query("CREATE TABLE IF NOT EXISTS `events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `event_date` date NOT NULL,
    `event_time` time DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `end_time` time DEFAULT NULL,
    `location` varchar(255) DEFAULT NULL,
    `category` varchar(100) DEFAULT 'General',
    `event_type` enum('academic','cultural','sports','health','meeting','workshop','ceremony','other') DEFAULT 'other',
    `organizer` varchar(200) DEFAULT NULL,
    `organizer_email` varchar(200) DEFAULT NULL,
    `target_audience` varchar(255) DEFAULT NULL,
    `max_attendees` int(11) DEFAULT 0,
    `cover_image` varchar(500) DEFAULT NULL,
    `status` enum('draft','published','cancelled','completed') DEFAULT 'draft',
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_event_date` (`event_date`),
    KEY `idx_event_status` (`status`),
    KEY `idx_event_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$staffConn->query("CREATE TABLE IF NOT EXISTS `event_attendees` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` int(11) NOT NULL,
    `full_name` varchar(200) NOT NULL,
    `email` varchar(200) DEFAULT NULL,
    `phone` varchar(50) DEFAULT NULL,
    `organization` varchar(255) DEFAULT NULL,
    `status` enum('registered','attended','cancelled','no_show') DEFAULT 'registered',
    `registered_at` timestamp NULL DEFAULT current_timestamp(),
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `event_id` (`event_id`),
    KEY `idx_attendee_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$staffConn->query("CREATE TABLE IF NOT EXISTS `event_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `color` varchar(20) DEFAULT '#0d6efd',
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Seed default categories
$staffConn->query("INSERT IGNORE INTO event_categories (name, description, color) VALUES
    ('General', 'General events', '#0d6efd'),
    ('Academic', 'Academic events and conferences', '#198754'),
    ('Cultural', 'Cultural events and ceremonies', '#dc3545'),
    ('Sports', 'Sports and athletic events', '#ffc107'),
    ('Health', 'Health and wellness events', '#20c997'),
    ('Workshop', 'Workshops and training', '#6610f2'),
    ('Meeting', 'Meetings and gatherings', '#fd7e14'),
    ('Ceremony', 'Official ceremonies', '#d63384')");

// Ã¢â€â‚¬Ã¢â€â‚¬ AJAX Handlers Ã¢â€â‚¬Ã¢â€â‚¬
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    header('Content-Type: application/json');
    try {
        switch ($action) {
            case 'create_event':
            case 'update_event': {
                $id = (int)($_POST['id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $event_date = trim($_POST['event_date'] ?? '');
                $event_time = trim($_POST['event_time'] ?? '');
                $end_date = trim($_POST['end_date'] ?? '');
                $end_time = trim($_POST['end_time'] ?? '');
                $location = trim($_POST['location'] ?? '');
                $category = trim($_POST['category'] ?? 'General');
                $event_type = trim($_POST['event_type'] ?? 'other');
                $organizer = trim($_POST['organizer'] ?? '');
                $organizer_email = trim($_POST['organizer_email'] ?? '');
                $target_audience = trim($_POST['target_audience'] ?? '');
                $max_attendees = (int)($_POST['max_attendees'] ?? 0);
                $status = trim($_POST['status'] ?? 'draft');

                if (empty($title)) throw new Exception('Event title is required.');
                if (empty($event_date)) throw new Exception('Event date is required.');

                if ($id > 0) {
                    $s = $staffConn->prepare("UPDATE events SET title=?, description=?, event_date=?, event_time=?, end_date=?, end_time=?, location=?, category=?, event_type=?, organizer=?, organizer_email=?, target_audience=?, max_attendees=?, status=? WHERE id=?");
                    $s->bind_param('ssssssssssssisi', $title, $description, $event_date, $event_time, $end_date, $end_time, $location, $category, $event_type, $organizer, $organizer_email, $target_audience, $max_attendees, $status, $id);
                    if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                    if ($s->affected_rows === 0 && $s->errno > 0) throw new Exception('Update failed: ' . $s->error);
                    $s->close();
                    echo json_encode(['success' => true, 'message' => 'Event updated successfully.']);
                } else {
                    $s = $staffConn->prepare("INSERT INTO events (title, description, event_date, event_time, end_date, end_time, location, category, event_type, organizer, organizer_email, target_audience, max_attendees, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $s->bind_param('sssssssssssssii', $title, $description, $event_date, $event_time, $end_date, $end_time, $location, $category, $event_type, $organizer, $organizer_email, $target_audience, $max_attendees, $status, $userId);
                    if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                    $newId = $s->insert_id;
                    $s->close();
                    echo json_encode(['success' => true, 'message' => 'Event created successfully.', 'id' => $newId]);
                }
                break;
            }

            case 'delete_event': {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception('Invalid event ID.');
                $staffConn->begin_transaction();
                $stmt = $staffConn->prepare("DELETE FROM event_attendees WHERE event_id=?");
                if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
                $stmt = $staffConn->prepare("DELETE FROM events WHERE id=?");
                if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
                $staffConn->commit();
                echo json_encode(['success' => true, 'message' => 'Event deleted.']);
                break;
            }

            case 'get_event': {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $staffConn->prepare("SELECT * FROM events WHERE id=?");
                if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else { $r = null; }
                $ev = $r ? $r->fetch_assoc() : null;
                echo json_encode(['success' => (bool)$ev, 'event' => $ev]);
                break;
            }

            case 'list_events': {
                $statusFilter = trim($_POST['status'] ?? '');
                $categoryFilter = trim($_POST['category'] ?? '');
                $search = trim($_POST['search'] ?? '');
                $where = ['1=1'];
                $params = []; $types = '';
                if ($statusFilter !== '' && $statusFilter !== 'all') {
                    $where[] = 'e.status=?';
                    $params[] = $statusFilter; $types .= 's';
                }
                if ($categoryFilter !== '' && $categoryFilter !== 'all') {
                    $where[] = 'e.category=?';
                    $params[] = $categoryFilter; $types .= 's';
                }
                if ($search !== '') {
                    $where[] = '(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)';
                    $like = '%' . $search . '%';
                    $params[] = $like; $params[] = $like; $params[] = $like;
                    $types .= 'sss';
                }
                $sql = "SELECT e.*, (SELECT COUNT(*) FROM event_attendees WHERE event_id=e.id) as attendee_count FROM events e WHERE " . implode(' AND ', $where) . " ORDER BY e.event_date DESC, e.event_time DESC";
                if (empty($params)) {
                    $r = $staffConn->query($sql);
                    $rows = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
                } else {
                    $s = $staffConn->prepare($sql);
                    if (!$s) throw new Exception('Prepare failed: ' . $staffConn->error);
                    $s->bind_param($types, ...$params);
                    if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                    $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
                    $s->close();
                }
                echo json_encode(['success' => true, 'events' => $rows]);
                break;
            }

            case 'register_attendee': {
                $event_id = (int)($_POST['event_id'] ?? 0);
                $full_name = trim($_POST['full_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $organization = trim($_POST['organization'] ?? '');
                if (empty($full_name) || $event_id <= 0) throw new Exception('Name and event are required.');
                $s = $staffConn->prepare("INSERT INTO event_attendees (event_id, full_name, email, phone, organization) VALUES (?, ?, ?, ?, ?)");
                $s->bind_param('issss', $event_id, $full_name, $email, $phone, $organization);
                if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                echo json_encode(['success' => true, 'message' => 'Attendee registered.']);
                break;
            }

            case 'update_attendee_status': {
                $id = (int)($_POST['id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                if ($id <= 0 || !in_array($status, ['registered','attended','cancelled','no_show'])) throw new Exception('Invalid parameters.');
                $s = $staffConn->prepare("UPDATE event_attendees SET status=? WHERE id=?");
                $s->bind_param('si', $status, $id);
                if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                echo json_encode(['success' => true, 'message' => 'Status updated.']);
                break;
            }

            case 'delete_attendee': {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception('Invalid ID.');
                $stmt = $staffConn->prepare("DELETE FROM event_attendees WHERE id=?");
                if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
                echo json_encode(['success' => true, 'message' => 'Attendee removed.']);
                break;
            }

            case 'list_attendees': {
                $event_id = (int)($_POST['event_id'] ?? 0);
                $stmt = $staffConn->prepare("SELECT * FROM event_attendees WHERE event_id=? ORDER BY registered_at DESC");
                if ($stmt) { $stmt->bind_param('i', $event_id); $stmt->execute(); $r = $stmt->get_result(); $stmt->close(); } else { $r = null; }
                $rows = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
                echo json_encode(['success' => true, 'attendees' => $rows]);
                break;
            }

            case 'get_calendar_events': {
                $start = preg_replace('/[^0-9\-]/', '', trim($_POST['start'] ?? date('Y-m-01')));
                $end = preg_replace('/[^0-9\-]/', '', trim($_POST['end'] ?? date('Y-m-t')));
                $stmt = $staffConn->prepare("SELECT id, title, event_date as start, event_time, location, category, status, description FROM events WHERE status='published' AND event_date BETWEEN ? AND ? ORDER BY event_date");
                if ($stmt) { $stmt->bind_param('ss', $start, $end); $stmt->execute(); $r = $stmt->get_result(); } else { $r = false; }
                $rows = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
                $calendar = [];
                foreach ($rows as $ev) {
                    $colorMap = ['General'=>'#0d6efd','Academic'=>'#198754','Cultural'=>'#dc3545','Sports'=>'#ffc107','Health'=>'#20c997','Workshop'=>'#6610f2','Meeting'=>'#fd7e14','Ceremony'=>'#d63384'];
                    $calendar[] = [
                        'id' => $ev['id'],
                        'title' => $ev['title'],
                        'start' => $ev['start'] . ($ev['event_time'] ? 'T' . $ev['event_time'] : ''),
                        'description' => $ev['description'],
                        'location' => $ev['location'],
                        'backgroundColor' => $colorMap[$ev['category']] ?? '#0d6efd',
                        'borderColor' => $colorMap[$ev['category']] ?? '#0d6efd',
                        'textColor' => in_array($ev['category'], ['Sports','Meeting']) ? '#000' : '#fff',
                    ];
                }
                echo json_encode(['success' => true, 'events' => $calendar]);
                break;
            }

            default:
                throw new Exception('Unknown action: ' . $action);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Events Management Ã¢â‚¬â€ ISNM</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<style>
:root{--primary:#1a237e;--primary-light:#3949ab;--accent:#0d6efd;--bg-light:#f0f2f5;--card-shadow:0 2px 8px rgba(0,0,0,.08)}
body{background:var(--bg-light);font-family:'Segoe UI',system-ui,sans-serif}
.page-header{padding:16px 24px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:0 0 16px 16px;margin-bottom:20px}
.page-header h2{margin:0;font-weight:600;font-size:1.4rem}
.page-header p{margin:4px 0 0;opacity:.85;font-size:.85rem}
.card{border:none;border-radius:12px;box-shadow:var(--card-shadow);margin-bottom:20px}
.card-header{background:#fff;border-bottom:1px solid #e9ecef;padding:14px 20px;font-weight:600;display:flex;align-items:center;justify-content:space-between}
.stat-card{background:#fff;border-radius:12px;padding:18px 20px;box-shadow:var(--card-shadow);text-align:center}
.stat-card .number{font-size:1.8rem;font-weight:700;color:var(--primary)}
.stat-card .label{font-size:.8rem;color:#6c757d;margin-top:4px}
.stat-card .icon{font-size:2rem;color:var(--accent);opacity:.3;position:absolute;right:16px;top:16px}
#calendar{max-height:600px;padding:10px}
.fc-event{cursor:pointer;border-radius:6px;padding:2px 6px;font-size:.8rem}
.event-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.badge-draft{background:#f1f5f9;color:#475569}
.badge-published{background:#dcfce7;color:#16a34a}
.badge-cancelled{background:#fef2f2;color:#dc2626}
.badge-completed{background:#e0e7ff;color:#4338ca}
.modal-content{border-radius:12px;border:none}
.modal-header{background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:12px 12px 0 0}
.modal-header .btn-close{filter:brightness(0) invert(1)}
.btn-action{padding:4px 10px;font-size:.75rem;border-radius:6px}
</style>
<link rel="stylesheet" href="../dashboards/dashboard-mobile.css?v=1.0">
<link rel="stylesheet" href="../css/mobile-fixes.css?v=1">
</head>
<body>
<?php $csrf = htmlspecialchars($_SESSION['csrf_token']); ?>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<style>:root{--sidebar-w:270px}.dashboard-main{margin-left:var(--sidebar-w);width:calc(100% - var(--sidebar-w));min-height:100vh;background:#f5f7fa}@media(max-width:991px){.dashboard-main{margin-left:0;width:100%}}@media(max-width:768px){.dashboard-main{margin-left:0!important;width:100%!important}}</style>
<div class="dashboard-main">
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-calendar-alt me-2"></i>Events Management</h2><button onclick="window.print()" class="btn btn-sm btn-outline-secondary float-end ms-2"><i class="fas fa-print"></i></button>
            <p>Plan, organize, and track institutional events</p>
        </div>
        <div>
            <button class="btn btn-light btn-sm me-2" onclick="showCalendarView()"><i class="fas fa-calendar me-1"></i>Calendar</button>
            <button class="btn btn-light btn-sm" onclick="showListView()"><i class="fas fa-list me-1"></i>List</button>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php
        $total = $staffConn->query("SELECT COUNT(*) c FROM events")->fetch_assoc()['c'] ?? 0;
        $upcoming = $staffConn->query("SELECT COUNT(*) c FROM events WHERE event_date >= CURDATE() AND status='published'")->fetch_assoc()['c'] ?? 0;
        $completed = $staffConn->query("SELECT COUNT(*) c FROM events WHERE status='completed'")->fetch_assoc()['c'] ?? 0;
        $totalAttendees = $staffConn->query("SELECT COUNT(*) c FROM event_attendees")->fetch_assoc()['c'] ?? 0;
        ?>
        <div class="col-md-3"><div class="stat-card position-relative"><div class="number"><?= $total ?></div><div class="label">Total Events</div><div class="icon"><i class="fas fa-calendar-alt"></i></div></div></div>
        <div class="col-md-3"><div class="stat-card position-relative"><div class="number"><?= $upcoming ?></div><div class="label">Upcoming Events</div><div class="icon"><i class="fas fa-clock"></i></div></div></div>
        <div class="col-md-3"><div class="stat-card position-relative"><div class="number"><?= $completed ?></div><div class="label">Completed</div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
        <div class="col-md-3"><div class="stat-card position-relative"><div class="number"><?= $totalAttendees ?></div><div class="label">Total Attendees</div><div class="icon"><i class="fas fa-users"></i></div></div></div>
    </div>

    <!-- Filters + Add Button -->
    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-filter me-2"></i>Events</span>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="filterStatus" onchange="loadEvents()">
                    <option value="all">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="completed">Completed</option>
                </select>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="filterCategory" onchange="loadEvents()">
                    <option value="all">All Categories</option>
                    <?php $cats = $staffConn->query("SELECT name FROM event_categories WHERE is_active=1"); while($c=$cats->fetch_assoc()) echo "<option value=\"{$c['name']}\">{$c['name']}</option>"; ?>
                </select>
                <input type="text" class="form-control form-control-sm d-inline-block w-auto me-2" id="searchEvents" placeholder="Search events..." onkeyup="loadEvents()">
                <button class="btn btn-primary btn-sm" onclick="openEventModal()"><i class="fas fa-plus me-1"></i>New Event</button>
            </div>
        </div>
        <div class="card-body">
            <div id="listView">
                <table id="eventsTable" class="table table-hover" style="width:100%">
                    <thead><tr><th>Title</th><th>Date</th><th>Time</th><th>Location</th><th>Category</th><th>Status</th><th>Attendees</th><th>Actions</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div id="calendarView" style="display:none">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i><span id="eventModalTitle">New Event</span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <input type="hidden" id="eventId" value="0">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Event Title *</label>
            <input type="text" class="form-control" id="evTitle" maxlength="255">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Category</label>
            <select class="form-select" id="evCategory">
                <?php $cats = $staffConn->query("SELECT name FROM event_categories WHERE is_active=1"); while($c=$cats->fetch_assoc()) echo "<option value=\"{$c['name']}\">{$c['name']}</option>"; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Event Type</label>
            <select class="form-select" id="evType">
                <option value="academic">Academic</option><option value="cultural">Cultural</option><option value="sports">Sports</option>
                <option value="health">Health</option><option value="meeting">Meeting</option><option value="workshop">Workshop</option>
                <option value="ceremony">Ceremony</option><option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" id="evStatus">
                <option value="draft">Draft</option><option value="published">Published</option>
                <option value="cancelled">Cancelled</option><option value="completed">Completed</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Event Date *</label>
            <input type="date" class="form-control" id="evDate">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Time</label>
            <input type="time" class="form-control" id="evTime">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">End Date</label>
            <input type="date" class="form-control" id="evEndDate">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">End Time</label>
            <input type="time" class="form-control" id="evEndTime">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Location</label>
            <input type="text" class="form-control" id="evLocation" placeholder="Venue or online link">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Max Attendees</label>
            <input type="number" class="form-control" id="evMaxAttendees" min="0" value="0" placeholder="0 = unlimited">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Organizer Name</label>
            <input type="text" class="form-control" id="evOrganizer">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Organizer Email</label>
            <input type="email" class="form-control" id="evOrganizerEmail">
        </div>
        <div class="col-md-12">
            <label class="form-label fw-semibold">Target Audience</label>
            <input type="text" class="form-control" id="evTargetAudience" placeholder="e.g. Staff, Students, Public">
        </div>
        <div class="col-md-12">
            <label class="form-label fw-semibold">Description</label>
            <textarea class="form-control" id="evDescription" rows="4" placeholder="Event description..."></textarea>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="saveEvent()"><i class="fas fa-save me-1"></i>Save Event</button>
</div>
</div>
</div>
</div>

<!-- Attendees Modal -->
<div class="modal fade" id="attendeesModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title"><i class="fas fa-users me-2"></i>Event Attendees Ã¢â‚¬â€ <span id="attendeeEventTitle"></span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <input type="hidden" id="attendeeEventId" value="0">
    <div class="row g-2 mb-3">
        <div class="col-md-3"><input type="text" class="form-control form-control-sm" id="attName" placeholder="Full name *"></div>
        <div class="col-md-3"><input type="email" class="form-control form-control-sm" id="attEmail" placeholder="Email"></div>
        <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="attPhone" placeholder="Phone"></div>
        <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="attOrg" placeholder="Organization"></div>
        <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" onclick="registerAttendee()"><i class="fas fa-plus"></i> Add</button></div>
    </div>
    <table id="attendeesTable" class="table table-sm">
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Organization</th><th>Status</th><th>Registered</th><th>Action</th></tr></thead>
        <tbody></tbody>
    </table>
</div>
</div>
</div>
</div>

<script>
const CSRF = '<?= $csrf ?>';

$(document).ready(function() {
    loadEvents();
    initCalendar();
});

// Ã¢â€â‚¬Ã¢â€â‚¬ List View Ã¢â€â‚¬Ã¢â€â‚¬
const eventsTable = $('#eventsTable').DataTable({
    processing: true,
    serverSide: false,
    ajax: {
        url: '',
        type: 'POST',
        data: function(d) {
            d.action = 'list_events';
            d.csrf_token = CSRF;
            d.status = $('#filterStatus').val();
            d.category = $('#filterCategory').val();
            d.search = $('#searchEvents').val();
        },
        dataSrc: function(json) {
            return json.success ? json.events : [];
        }
    },
    columns: [
        { data: 'title' },
        { data: 'event_date', render: d => d ? new Date(d).toLocaleDateString() : '-' },
        { data: 'event_time', render: d => d ? d.substring(0,5) : '-' },
        { data: 'location', defaultContent: '-' },
        { data: 'category', render: d => `<span class="badge bg-primary">${d || 'General'}</span>` },
        { data: 'status', render: d => `<span class="event-badge badge-${d}">${d}</span>` },
        { data: 'attendee_count', render: d => `<span class="badge bg-secondary">${d || 0}</span>` },
        { data: null, render: d => `
            <button class="btn btn-sm btn-outline-info btn-action" onclick="viewAttendees(${d.id},'${d.title.replace(/'/g,"\\'")}')" title="Attendees"><i class="fas fa-users"></i></button>
            <button class="btn btn-sm btn-outline-primary btn-action" onclick="editEvent(${d.id})" title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteEvent(${d.id})" title="Delete"><i class="fas fa-trash"></i></button>
        ` }
    ],
    order: [[1,'desc']],
    pageLength: 25,
    language: { emptyTable: 'No events found. Create your first event!' },
    dom: 'rt<"row"<"col-sm-6"i><"col-sm-6"p>>'
});

function loadEvents() { eventsTable.ajax.reload(); }

function showCalendarView() {
    $('#listView').hide();
    $('#calendarView').show();
    setTimeout(() => { if (calendar) calendar.render(); }, 100);
}

function showListView() {
    $('#calendarView').hide();
    $('#listView').show();
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Calendar Ã¢â€â‚¬Ã¢â€â‚¬
let calendar = null;
function initCalendar() {
    const el = document.getElementById('calendar');
    if (!el) return;
    calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
        events: function(fetchInfo, successCallback, failureCallback) {
            $.post('', {
                action: 'get_calendar_events',
                csrf_token: CSRF,
                start: fetchInfo.startStr,
                end: fetchInfo.endStr
            }, function(r) {
                if (r.success) successCallback(r.events);
            }, 'json');
        },
        eventClick: function(info) {
            Swal.fire({
                title: info.event.title,
                html: `<p>${info.event.extendedProps.description || 'No description'}</p>
                       <p><strong>Location:</strong> ${info.event.extendedProps.location || 'N/A'}</p>
                       <p><strong>Time:</strong> ${info.event.start ? info.event.start.toLocaleString() : 'N/A'}</p>`,
                icon: 'info',
                confirmButtonText: 'Close'
            });
        },
        height: 'auto',
        themeSystem: 'bootstrap'
    });
    calendar.render();
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Event CRUD Ã¢â€â‚¬Ã¢â€â‚¬
function openEventModal(data) {
    $('#eventModalTitle').text('New Event');
    $('#eventId').val(0);
    $('#evTitle,#evDescription,#evLocation,#evOrganizer,#evOrganizerEmail,#evTargetAudience').val('');
    $('#evDate,#evTime,#evEndDate,#evEndTime').val('');
    $('#evCategory').val('General');
    $('#evType').val('other');
    $('#evStatus').val('draft');
    $('#evMaxAttendees').val(0);
    if (data) {
        $('#eventModalTitle').text('Edit Event');
        $('#eventId').val(data.id);
        $('#evTitle').val(data.title);
        $('#evDescription').val(data.description || '');
        $('#evDate').val(data.event_date || '');
        $('#evTime').val(data.event_time ? data.event_time.substring(0,5) : '');
        $('#evEndDate').val(data.end_date || '');
        $('#evEndTime').val(data.end_time ? data.end_time.substring(0,5) : '');
        $('#evLocation').val(data.location || '');
        $('#evCategory').val(data.category || 'General');
        $('#evType').val(data.event_type || 'other');
        $('#evStatus').val(data.status || 'draft');
        $('#evOrganizer').val(data.organizer || '');
        $('#evOrganizerEmail').val(data.organizer_email || '');
        $('#evTargetAudience').val(data.target_audience || '');
        $('#evMaxAttendees').val(data.max_attendees || 0);
    }
    new bootstrap.Modal('#eventModal').show();
}

function editEvent(id) {
    $.post('', { action: 'get_event', csrf_token: CSRF, id: id }, function(r) {
        if (r.success) openEventModal(r.event);
        else Swal.fire('Error', r.message, 'error');
    }, 'json').fail(e => Swal.fire('Error', 'Failed to load event', 'error'));
}

function saveEvent() {
    const data = {
        action: $('#eventId').val() > 0 ? 'update_event' : 'create_event',
        csrf_token: CSRF,
        id: $('#eventId').val(),
        title: $('#evTitle').val(),
        description: $('#evDescription').val(),
        event_date: $('#evDate').val(),
        event_time: $('#evTime').val(),
        end_date: $('#evEndDate').val(),
        end_time: $('#evEndTime').val(),
        location: $('#evLocation').val(),
        category: $('#evCategory').val(),
        event_type: $('#evType').val(),
        status: $('#evStatus').val(),
        organizer: $('#evOrganizer').val(),
        organizer_email: $('#evOrganizerEmail').val(),
        target_audience: $('#evTargetAudience').val(),
        max_attendees: $('#evMaxAttendees').val()
    };
    if (!data.title) return Swal.fire('Required', 'Event title is required.', 'warning');
    if (!data.event_date) return Swal.fire('Required', 'Event date is required.', 'warning');
    $.post('', data, function(r) {
        if (r.success) {
            Swal.fire('Saved', r.message, 'success');
            bootstrap.Modal.getInstance('#eventModal').hide();
            loadEvents();
            if (calendar) calendar.refetchEvents();
        } else Swal.fire('Error', r.message, 'error');
    }, 'json').fail(e => Swal.fire('Error', 'Save failed', 'error'));
}

function deleteEvent(id) {
    Swal.fire({
        title: 'Delete Event?',
        text: 'This will also remove all attendee records.',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post('', { action: 'delete_event', csrf_token: CSRF, id: id }, function(r) {
            if (r.success) { Swal.fire('Deleted', r.message, 'success'); loadEvents(); if (calendar) calendar.refetchEvents(); }
            else Swal.fire('Error', r.message, 'error');
        }, 'json');
    });
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Attendees Ã¢â€â‚¬Ã¢â€â‚¬
function viewAttendees(eventId, eventTitle) {
    $('#attendeeEventId').val(eventId);
    $('#attendeeEventTitle').text(eventTitle);
    $('#attName,#attEmail,#attPhone,#attOrg').val('');
    loadAttendees(eventId);
    new bootstrap.Modal('#attendeesModal').show();
}

function loadAttendees(eventId) {
    $.post('', { action: 'list_attendees', csrf_token: CSRF, event_id: eventId }, function(r) {
        if (!r.success) return;
        let h = '';
        r.attendees.forEach((a, i) => {
            const statusBadge = {registered:'bg-secondary',attended:'bg-success',cancelled:'bg-danger',no_show:'bg-warning'};
            h += `<tr>
                <td>${i+1}</td>
                <td>${a.full_name}</td>
                <td>${a.email || '-'}</td>
                <td>${a.phone || '-'}</td>
                <td>${a.organization || '-'}</td>
                <td>
                    <select class="form-select form-select-sm" onchange="updateAttendeeStatus(${a.id},this.value)" style="width:auto">
                        <option value="registered" ${a.status==='registered'?'selected':''}>Registered</option>
                        <option value="attended" ${a.status==='attended'?'selected':''}>Attended</option>
                        <option value="cancelled" ${a.status==='cancelled'?'selected':''}>Cancelled</option>
                        <option value="no_show" ${a.status==='no_show'?'selected':''}>No Show</option>
                    </select>
                </td>
                <td>${a.registered_at ? new Date(a.registered_at).toLocaleDateString() : '-'}</td>
                <td><button class="btn btn-sm btn-outline-danger" onclick="deleteAttendee(${a.id})"><i class="fas fa-times"></i></button></td>
            </tr>`;
        });
        $('#attendeesTable tbody').html(h || '<tr><td colspan="8" class="text-center text-muted">No attendees registered.</td></tr>');
    }, 'json');
}

function registerAttendee() {
    const eventId = $('#attendeeEventId').val();
    const name = $('#attName').val();
    if (!name) return Swal.fire('Required', 'Attendee name is required.', 'warning');
    $.post('', {
        action: 'register_attendee', csrf_token: CSRF,
        event_id: eventId, full_name: name,
        email: $('#attEmail').val(), phone: $('#attPhone').val(),
        organization: $('#attOrg').val()
    }, function(r) {
        if (r.success) { $('#attName,#attEmail,#attPhone,#attOrg').val(''); loadAttendees(eventId); }
        else Swal.fire('Error', r.message, 'error');
    }, 'json');
}

function updateAttendeeStatus(id, status) {
    $.post('', { action: 'update_attendee_status', csrf_token: CSRF, id: id, status: status }, function(r) {
        if (!r.success) Swal.fire('Error', r.message, 'error');
    }, 'json');
}

function deleteAttendee(id) {
    Swal.fire({
        title: 'Remove attendee?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545',
        confirmButtonText: 'Remove'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post('', { action: 'delete_attendee', csrf_token: CSRF, id: id }, function(r) {
            if (r.success) loadAttendees($('#attendeeEventId').val());
            else Swal.fire('Error', r.message, 'error');
        }, 'json');
    });
}
function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var tr = table.getElementsByTagName("tr");
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) { found = true; break; }
        }
        tr[i].style.display = found ? "" : "none";
    }
}

</script>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
