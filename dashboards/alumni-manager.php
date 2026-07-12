<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$authData = bootstrapStaffDashboard(['alumni relations','alumni','alumni officer']);
$auth = $authData['auth'];
$staffConn = $authData['staff'];
$user = $authData['user'];
$userId = $user['id'] ?? 0;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Ã¢â€â‚¬Ã¢â€â‚¬ Ensure tables exist Ã¢â€â‚¬Ã¢â€â‚¬
$staffConn->query("CREATE TABLE IF NOT EXISTS `alumni` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` varchar(50) DEFAULT NULL,
    `index_number` varchar(50) DEFAULT NULL,
    `first_name` varchar(100) NOT NULL,
    `surname` varchar(100) NOT NULL,
    `other_name` varchar(100) DEFAULT NULL,
    `full_name` varchar(300) DEFAULT NULL,
    `email` varchar(200) DEFAULT NULL,
    `phone` varchar(50) DEFAULT NULL,
    `gender` enum('Male','Female','Other') DEFAULT 'Other',
    `date_of_birth` date DEFAULT NULL,
    `nationality` varchar(100) DEFAULT 'Ugandan',
    `address` text DEFAULT NULL,
    `program` varchar(200) DEFAULT NULL,
    `graduation_year` year(4) DEFAULT NULL,
    `graduation_class` varchar(50) DEFAULT NULL,
    `current_employer` varchar(255) DEFAULT NULL,
    `current_position` varchar(255) DEFAULT NULL,
    `employment_status` enum('employed','self-employed','unemployed','student','retired') DEFAULT 'employed',
    `industry` varchar(200) DEFAULT NULL,
    `location_city` varchar(100) DEFAULT NULL,
    `location_country` varchar(100) DEFAULT 'Uganda',
    `linkedin` varchar(500) DEFAULT NULL,
    `bio` text DEFAULT NULL,
    `skills` text DEFAULT NULL,
    `interests` text DEFAULT NULL,
    `membership_status` enum('active','inactive','lifetime') DEFAULT 'active',
    `profile_photo` varchar(500) DEFAULT NULL,
    `emergency_contact` varchar(100) DEFAULT NULL,
    `emergency_phone` varchar(50) DEFAULT NULL,
    `newsletter_optin` tinyint(1) DEFAULT 1,
    `notes` text DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_alumni_email` (`email`),
    KEY `idx_alumni_graduation` (`graduation_year`),
    KEY `idx_alumni_status` (`membership_status`),
    KEY `idx_alumni_employer` (`current_employer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$staffConn->query("CREATE TABLE IF NOT EXISTS `alumni_contributions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `alumni_id` int(11) NOT NULL,
    `contribution_type` enum('donation','sponsorship','volunteer','mentorship','other') DEFAULT 'donation',
    `amount` decimal(12,2) DEFAULT 0.00,
    `currency` varchar(3) DEFAULT 'UGX',
    `description` text DEFAULT NULL,
    `contribution_date` date DEFAULT curdate(),
    `payment_method` varchar(50) DEFAULT NULL,
    `transaction_ref` varchar(100) DEFAULT NULL,
    `acknowledged` tinyint(1) DEFAULT 0,
    `acknowledged_by` int(11) DEFAULT NULL,
    `acknowledged_at` datetime DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `alumni_id` (`alumni_id`),
    KEY `idx_contrib_type` (`contribution_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$staffConn->query("CREATE TABLE IF NOT EXISTS `alumni_events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `alumni_id` int(11) NOT NULL,
    `event_name` varchar(255) NOT NULL,
    `event_date` date DEFAULT NULL,
    `attended` tinyint(1) DEFAULT 0,
    `notes` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `alumni_id` (`alumni_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$staffConn->query("CREATE TABLE IF NOT EXISTS `alumni_jobs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `alumni_id` int(11) NOT NULL,
    `company` varchar(255) NOT NULL,
    `position` varchar(255) NOT NULL,
    `start_date` date DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `is_current` tinyint(1) DEFAULT 0,
    `description` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `alumni_id` (`alumni_id`),
    KEY `idx_jobs_current` (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Ã¢â€â‚¬Ã¢â€â‚¬ AJAX Handlers Ã¢â€â‚¬Ã¢â€â‚¬
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    header('Content-Type: application/json');
    try {
        switch ($action) {
            case 'create_alumni':
            case 'update_alumni': {
                $id = (int)($_POST['id'] ?? 0);
                $first_name = trim($_POST['first_name'] ?? '');
                $surname = trim($_POST['surname'] ?? '');
                $other_name = trim($_POST['other_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $gender = trim($_POST['gender'] ?? 'Other');
                $date_of_birth = trim($_POST['date_of_birth'] ?? '');
                $nationality = trim($_POST['nationality'] ?? 'Ugandan');
                $address = trim($_POST['address'] ?? '');
                $program = trim($_POST['program'] ?? '');
                $graduation_year = (int)($_POST['graduation_year'] ?? 0);
                $graduation_class = trim($_POST['graduation_class'] ?? '');
                $current_employer = trim($_POST['current_employer'] ?? '');
                $current_position = trim($_POST['current_position'] ?? '');
                $employment_status = trim($_POST['employment_status'] ?? 'employed');
                $industry = trim($_POST['industry'] ?? '');
                $location_city = trim($_POST['location_city'] ?? '');
                $location_country = trim($_POST['location_country'] ?? 'Uganda');
                $linkedin = trim($_POST['linkedin'] ?? '');
                $bio = trim($_POST['bio'] ?? '');
                $skills = trim($_POST['skills'] ?? '');
                $interests = trim($_POST['interests'] ?? '');
                $membership_status = trim($_POST['membership_status'] ?? 'active');
                $newsletter_optin = (int)($_POST['newsletter_optin'] ?? 1);
                $notes = trim($_POST['notes'] ?? '');
                $full_name = trim("$first_name $surname" . ($other_name ? " $other_name" : ''));

                if (empty($first_name) || empty($surname)) throw new Exception('First name and surname are required.');

                if ($id > 0) {
                    $s = $staffConn->prepare("UPDATE alumni SET first_name=?, surname=?, other_name=?, full_name=?, email=?, phone=?, gender=?, date_of_birth=?, nationality=?, address=?, program=?, graduation_year=?, graduation_class=?, current_employer=?, current_position=?, employment_status=?, industry=?, location_city=?, location_country=?, linkedin=?, bio=?, skills=?, interests=?, membership_status=?, newsletter_optin=?, notes=? WHERE id=?");
                    $s->bind_param('ssssssssssssssssssssssssssi', $first_name, $surname, $other_name, $full_name, $email, $phone, $gender, $date_of_birth, $nationality, $address, $program, $graduation_year, $graduation_class, $current_employer, $current_position, $employment_status, $industry, $location_city, $location_country, $linkedin, $bio, $skills, $interests, $membership_status, $newsletter_optin, $notes, $id);
                    if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                    echo json_encode(['success' => true, 'message' => 'Alumni record updated.']);
                } else {
                    $chk = $staffConn->prepare("SELECT id FROM alumni WHERE email=? LIMIT 1");
                    $chk->bind_param('s', $email);
                    if (!$chk->execute()) { error_log('$chk execute failed: ' . ($chk->error ?? 'unknown')); };
                    if ($chk->get_result()->num_rows > 0) throw new Exception('An alumni with this email already exists.');
                    $chk->close();
                    $s = $staffConn->prepare("INSERT INTO alumni (first_name, surname, other_name, full_name, email, phone, gender, date_of_birth, nationality, address, program, graduation_year, graduation_class, current_employer, current_position, employment_status, industry, location_city, location_country, linkedin, bio, skills, interests, membership_status, newsletter_optin, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $s->bind_param('ssssssssssssssssssssssssssi', $first_name, $surname, $other_name, $full_name, $email, $phone, $gender, $date_of_birth, $nationality, $address, $program, $graduation_year, $graduation_class, $current_employer, $current_position, $employment_status, $industry, $location_city, $location_country, $linkedin, $bio, $skills, $interests, $membership_status, $newsletter_optin, $notes, $userId);
                    if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                    echo json_encode(['success' => true, 'message' => 'Alumni added successfully.', 'id' => $s->insert_id]);
                }
                $s->close();
                break;
            }

            case 'delete_alumni': {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception('Invalid ID.');
                $staffConn->begin_transaction();
                $staffConn->query("DELETE FROM alumni_contributions WHERE alumni_id=$id");
                $staffConn->query("DELETE FROM alumni_events WHERE alumni_id=$id");
                $staffConn->query("DELETE FROM alumni_jobs WHERE alumni_id=$id");
                $staffConn->query("DELETE FROM alumni WHERE id=$id");
                $staffConn->commit();
                echo json_encode(['success' => true, 'message' => 'Alumni record deleted.']);
                break;
            }

            case 'get_alumni': {
                $id = (int)($_POST['id'] ?? 0);
                $r = $staffConn->query("SELECT * FROM alumni WHERE id=$id");
                $a = $r ? $r->fetch_assoc() : null;
                echo json_encode(['success' => (bool)$a, 'alumni' => $a]);
                break;
            }

            case 'list_alumni': {
                $search = trim($_POST['search'] ?? '');
                $status = trim($_POST['membership_status'] ?? '');
                $program = trim($_POST['program'] ?? '');
                $gradYear = (int)($_POST['graduation_year'] ?? 0);
                $where = ['1=1']; $params = []; $types = '';
                if ($search) {
                    $where[] = '(a.full_name LIKE ? OR a.email LIKE ? OR a.phone LIKE ? OR a.current_employer LIKE ?)';
                    $like = '%' . $search . '%';
                    foreach (range(1,4) as $_) { $params[] = $like; $types .= 's'; }
                }
                if ($status && $status !== 'all') { $where[] = 'a.membership_status=?'; $params[] = $status; $types .= 's'; }
                if ($program && $program !== 'all') { $where[] = 'a.program=?'; $params[] = $program; $types .= 's'; }
                if ($gradYear > 0) { $where[] = 'a.graduation_year=?'; $params[] = $gradYear; $types .= 'i'; }
                $sql = "SELECT a.*, (SELECT COUNT(*) FROM alumni_contributions WHERE alumni_id=a.id) as contribution_count FROM alumni a WHERE " . implode(' AND ', $where) . " ORDER BY a.surname ASC, a.first_name ASC";
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
                echo json_encode(['success' => true, 'alumni' => $rows]);
                break;
            }

            case 'add_contribution': {
                $alumni_id = (int)($_POST['alumni_id'] ?? 0);
                $contribution_type = trim($_POST['contribution_type'] ?? 'donation');
                $amount = (float)($_POST['amount'] ?? 0);
                $currency = trim($_POST['currency'] ?? 'UGX');
                $description = trim($_POST['description'] ?? '');
                $contribution_date = trim($_POST['contribution_date'] ?? date('Y-m-d'));
                $payment_method = trim($_POST['payment_method'] ?? '');
                $transaction_ref = trim($_POST['transaction_ref'] ?? '');
                if ($alumni_id <= 0) throw new Exception('Invalid alumni.');
                $s = $staffConn->prepare("INSERT INTO alumni_contributions (alumni_id, contribution_type, amount, currency, description, contribution_date, payment_method, transaction_ref, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $s->bind_param('isdsssssi', $alumni_id, $contribution_type, $amount, $currency, $description, $contribution_date, $payment_method, $transaction_ref, $userId);
                if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                echo json_encode(['success' => true, 'message' => 'Contribution recorded.']);
                break;
            }

            case 'list_contributions': {
                $alumni_id = (int)($_POST['alumni_id'] ?? 0);
                $r = $staffConn->query("SELECT * FROM alumni_contributions WHERE alumni_id=$alumni_id ORDER BY contribution_date DESC");
                echo json_encode(['success' => true, 'contributions' => $r ? $r->fetch_all(MYSQLI_ASSOC) : []]);
                break;
            }

            case 'add_job': {
                $alumni_id = (int)($_POST['alumni_id'] ?? 0);
                $company = trim($_POST['company'] ?? '');
                $position = trim($_POST['position'] ?? '');
                $start_date = trim($_POST['start_date'] ?? '');
                $end_date = trim($_POST['end_date'] ?? '');
                $is_current = (int)($_POST['is_current'] ?? 0);
                $description = trim($_POST['description'] ?? '');
                if (empty($company) || empty($position) || $alumni_id <= 0) throw new Exception('Company and position are required.');
                $s = $staffConn->prepare("INSERT INTO alumni_jobs (alumni_id, company, position, start_date, end_date, is_current, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $s->bind_param('issssis', $alumni_id, $company, $position, $start_date, $end_date, $is_current, $description);
                if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                echo json_encode(['success' => true, 'message' => 'Job added.']);
                break;
            }

            case 'list_jobs': {
                $alumni_id = (int)($_POST['alumni_id'] ?? 0);
                $r = $staffConn->query("SELECT * FROM alumni_jobs WHERE alumni_id=$alumni_id ORDER BY is_current DESC, start_date DESC");
                echo json_encode(['success' => true, 'jobs' => $r ? $r->fetch_all(MYSQLI_ASSOC) : []]);
                break;
            }

            case 'add_alumni_event': {
                $alumni_id = (int)($_POST['alumni_id'] ?? 0);
                $event_name = trim($_POST['event_name'] ?? '');
                $event_date = trim($_POST['event_date'] ?? '');
                $attended = (int)($_POST['attended'] ?? 0);
                $notes = trim($_POST['notes'] ?? '');
                if (empty($event_name) || $alumni_id <= 0) throw new Exception('Event name is required.');
                $s = $staffConn->prepare("INSERT INTO alumni_events (alumni_id, event_name, event_date, attended, notes) VALUES (?, ?, ?, ?, ?)");
                $s->bind_param('issis', $alumni_id, $event_name, $event_date, $attended, $notes);
                if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
                echo json_encode(['success' => true, 'message' => 'Event recorded.']);
                break;
            }

            case 'list_alumni_events': {
                $alumni_id = (int)($_POST['alumni_id'] ?? 0);
                $r = $staffConn->query("SELECT * FROM alumni_events WHERE alumni_id=$alumni_id ORDER BY event_date DESC");
                echo json_encode(['success' => true, 'events' => $r ? $r->fetch_all(MYSQLI_ASSOC) : []]);
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
<title>Alumni Management Ã¢â‚¬â€ ISNM</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root{--primary:#4a148c;--primary-light:#7b1fa2;--accent:#9c27b0;--bg-light:#f0f2f5;--card-shadow:0 2px 8px rgba(0,0,0,.08)}
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
.modal-content{border-radius:12px;border:none}
.modal-header{background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:12px 12px 0 0}
.modal-header .btn-close{filter:brightness(0) invert(1)}
.btn-action{padding:4px 10px;font-size:.75rem;border-radius:6px}
.nav-tabs .nav-link{color:#495057;font-weight:500}
.nav-tabs .nav-link.active{color:var(--primary);border-bottom:3px solid var(--primary)}
.employment-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.membership-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
</style>
<link rel="stylesheet" href="../dashboards/dashboard-mobile.css?v=1.0">
<link rel="stylesheet" href="../css/mobile-fixes.css?v=1">
</head>
<body>
<?php $csrf = htmlspecialchars($_SESSION['csrf_token']); ?>

<style>:root{--sidebar-w:270px}.dashboard-main{margin-left:var(--sidebar-w);width:calc(100% - var(--sidebar-w));min-height:100vh;background:#f5f7fa}@media(max-width:991px){.dashboard-main{margin-left:0;width:100%}}@media(max-width:768px){.dashboard-main{margin-left:0!important;width:100%!important}}</style>
<div class="dashboard-main">
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-user-graduate me-2"></i>Alumni Management</h2><button onclick="window.print()" class="btn btn-sm btn-outline-secondary float-end ms-2"><i class="fas fa-print"></i></button>
            <p>Manage alumni records, contributions, and engagement</p>
        </div>
        <div>
            <button class="btn btn-light btn-sm" onclick="openAlumniModal()"><i class="fas fa-plus me-1"></i>Add Alumni</button>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php
        $totalAlumni = $staffConn->query("SELECT COUNT(*) c FROM alumni")->fetch_assoc()['c'] ?? 0;
        $active = $staffConn->query("SELECT COUNT(*) c FROM alumni WHERE membership_status='active'")->fetch_assoc()['c'] ?? 0;
        $employed = $staffConn->query("SELECT COUNT(*) c FROM alumni WHERE employment_status='employed'")->fetch_assoc()['c'] ?? 0;
        $totalContrib = $staffConn->query("SELECT COALESCE(SUM(amount),0) s FROM alumni_contributions")->fetch_assoc()['s'] ?? 0;
        ?>
        <div class="col-md-3"><div class="stat-card position-relative"><div class="number"><?= $totalAlumni ?></div><div class="label">Total Alumni</div><div class="icon"><i class="fas fa-user-graduate"></i></div></div></div>
        <div class="col-md-3"><div class="stat-card position-relative"><div class="number"><?= $active ?></div><div class="label">Active Members</div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
        <div class="col-md-3"><div class="stat-card position-relative"><div class="number"><?= $employed ?></div><div class="label">Employed</div><div class="icon"><i class="fas fa-briefcase"></i></div></div></div>
        <div class="col-md-3"><div class="stat-card position-relative"><div class="number"><?= number_format($totalContrib) ?></div><div class="label">Total Contributions (UGX)</div><div class="icon"><i class="fas fa-hand-holding-usd"></i></div></div></div>
    </div>

    <!-- Filters -->
    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-filter me-2"></i>Alumni Records</span>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="filterMembership" onchange="loadAlumni()">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="lifetime">Lifetime</option>
                </select>
                <select class="form-select form-select-sm d-inline-block w-auto me-2" id="filterProgram" onchange="loadAlumni()">
                    <option value="all">All Programs</option>
                    <?php $progs = $staffConn->query("SELECT DISTINCT program FROM alumni WHERE program IS NOT NULL AND program!='' ORDER BY program"); while($p=$progs->fetch_assoc()) echo "<option value=\"{$p['program']}\">{$p['program']}</option>"; ?>
                </select>
                <input type="text" class="form-control form-control-sm d-inline-block w-auto" id="searchAlumni" placeholder="Search alumni..." onkeyup="loadAlumni()">
            </div>
        </div>
        <div class="card-body">
            <table id="alumniTable" class="table table-hover" style="width:100%">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Program</th><th>Graduation</th><th>Employer</th><th>Status</th><th>Contributions</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Alumni Modal -->
<div class="modal fade" id="alumniModal" tabindex="-1">
<div class="modal-dialog modal-xl">
<div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title"><i class="fas fa-user-graduate me-2"></i><span id="alumniModalTitle">Add Alumni</span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <input type="hidden" id="alumniId" value="0">

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="alumniTabs">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabPersonal">Personal Info</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAcademics">Academics</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabEmployment">Employment</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabExtra">Additional</a></li>
        <li class="nav-item" id="contribTabLi" style="display:none"><a class="nav-link" data-bs-toggle="tab" href="#tabContributions">Contributions</a></li>
        <li class="nav-item" id="jobsTabLi" style="display:none"><a class="nav-link" data-bs-toggle="tab" href="#tabJobs">Job History</a></li>
        <li class="nav-item" id="alumniEventsTabLi" style="display:none"><a class="nav-link" data-bs-toggle="tab" href="#tabAlumniEvents">Events</a></li>
    </ul>

    <div class="tab-content">
        <!-- Personal Info -->
        <div class="tab-pane fade show active" id="tabPersonal">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label fw-semibold">First Name *</label><input type="text" class="form-control" id="alFirstName"></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Surname *</label><input type="text" class="form-control" id="alSurname"></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Other Name</label><input type="text" class="form-control" id="alOtherName"></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control" id="alEmail"></div>
                <div class="col-md-3"><label class="form-label fw-semibold">Phone</label><input type="text" class="form-control" id="alPhone"></div>
                <div class="col-md-2"><label class="form-label fw-semibold">Gender</label><select class="form-select" id="alGender"><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select></div>
                <div class="col-md-3"><label class="form-label fw-semibold">Date of Birth</label><input type="date" class="form-control" id="alDob"></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Nationality</label><input type="text" class="form-control" id="alNationality" value="Ugandan"></div>
                <div class="col-md-12"><label class="form-label fw-semibold">Address</label><textarea class="form-control" id="alAddress" rows="2"></textarea></div>
            </div>
        </div>

        <!-- Academics -->
        <div class="tab-pane fade" id="tabAcademics">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-semibold">Program of Study</label><input type="text" class="form-control" id="alProgram" placeholder="e.g. Diploma in Nursing"></div>
                <div class="col-md-3"><label class="form-label fw-semibold">Graduation Year</label><input type="number" class="form-control" id="alGradYear" min="2000" max="2099"></div>
                <div class="col-md-3"><label class="form-label fw-semibold">Class/Grade</label><input type="text" class="form-control" id="alGradClass" placeholder="e.g. First Class"></div>
                <div class="col-md-12"><label class="form-label fw-semibold">Skills</label><textarea class="form-control" id="alSkills" rows="2" placeholder="Comma-separated skills"></textarea></div>
                <div class="col-md-12"><label class="form-label fw-semibold">Interests</label><textarea class="form-control" id="alInterests" rows="2"></textarea></div>
            </div>
        </div>

        <!-- Employment -->
        <div class="tab-pane fade" id="tabEmployment">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label fw-semibold">Employment Status</label><select class="form-select" id="alEmpStatus"><option value="employed">Employed</option><option value="self-employed">Self-Employed</option><option value="unemployed">Unemployed</option><option value="student">Student</option><option value="retired">Retired</option></select></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Current Employer</label><input type="text" class="form-control" id="alEmployer"></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Current Position</label><input type="text" class="form-control" id="alPosition"></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Industry</label><input type="text" class="form-control" id="alIndustry" placeholder="e.g. Healthcare"></div>
                <div class="col-md-4"><label class="form-label fw-semibold">City</label><input type="text" class="form-control" id="alCity"></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Country</label><input type="text" class="form-control" id="alCountry" value="Uganda"></div>
                <div class="col-md-12"><label class="form-label fw-semibold">LinkedIn Profile</label><input type="url" class="form-control" id="alLinkedin" placeholder="https://linkedin.com/in/..."></div>
                <div class="col-md-12"><label class="form-label fw-semibold">Bio / Professional Summary</label><textarea class="form-control" id="alBio" rows="3"></textarea></div>
            </div>
        </div>

        <!-- Additional -->
        <div class="tab-pane fade" id="tabExtra">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label fw-semibold">Membership Status</label><select class="form-select" id="alMembership"><option value="active">Active</option><option value="inactive">Inactive</option><option value="lifetime">Lifetime</option></select></div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Newsletter</label>
                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="alNewsletter" checked><label class="form-check-label">Opt in to newsletter</label></div>
                </div>
                <div class="col-md-12"><label class="form-label fw-semibold">Internal Notes</label><textarea class="form-control" id="alNotes" rows="3"></textarea></div>
            </div>
        </div>

        <!-- Contributions Tab (view-only for existing records) -->
        <div class="tab-pane fade" id="tabContributions">
            <div class="row g-2 mb-3">
                <div class="col-md-3"><input type="text" class="form-control form-control-sm" id="contribType" placeholder="Type (donation/sponsorship/...)"></div>
                <div class="col-md-2"><input type="number" class="form-control form-control-sm" id="contribAmount" placeholder="Amount"></div>
                <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="contribCurrency" value="UGX"></div>
                <div class="col-md-2"><input type="date" class="form-control form-control-sm" id="contribDate"></div>
                <div class="col-md-3"><button class="btn btn-primary btn-sm w-100" onclick="addContribution()"><i class="fas fa-plus"></i> Add Contribution</button></div>
            </div>
            <div class="row mb-3"><div class="col-md-12"><input type="text" class="form-control form-control-sm" id="contribDesc" placeholder="Description (optional)"></div></div>
            <table id="contribTable" class="table table-sm"><thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Currency</th><th>Description</th><th>Method</th></tr></thead><tbody></tbody></table>
        </div>

        <!-- Job History Tab -->
        <div class="tab-pane fade" id="tabJobs">
            <div class="row g-2 mb-3">
                <div class="col-md-3"><input type="text" class="form-control form-control-sm" id="jobCompany" placeholder="Company *"></div>
                <div class="col-md-3"><input type="text" class="form-control form-control-sm" id="jobPosition" placeholder="Position *"></div>
                <div class="col-md-2"><input type="date" class="form-control form-control-sm" id="jobStart" placeholder="Start date"></div>
                <div class="col-md-2"><input type="date" class="form-control form-control-sm" id="jobEnd" placeholder="End date"></div>
                <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" onclick="addJob()"><i class="fas fa-plus"></i> Add Job</button></div>
            </div>
            <div class="row mb-3"><div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" id="jobCurrent"><label class="form-check-label">Current position</label></div></div><div class="col-md-6"><input type="text" class="form-control form-control-sm" id="jobDesc" placeholder="Description"></div></div>
            <table id="jobsTable" class="table table-sm"><thead><tr><th>Company</th><th>Position</th><th>Start</th><th>End</th><th>Current</th><th>Description</th></tr></thead><tbody></tbody></table>
        </div>

        <!-- Alumni Events Tab -->
        <div class="tab-pane fade" id="tabAlumniEvents">
            <div class="row g-2 mb-3">
                <div class="col-md-4"><input type="text" class="form-control form-control-sm" id="alEventName" placeholder="Event name *"></div>
                <div class="col-md-3"><input type="date" class="form-control form-control-sm" id="alEventDate"></div>
                <div class="col-md-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="alEventAttended"><label class="form-check-label">Attended</label></div></div>
                <div class="col-md-3"><button class="btn btn-primary btn-sm w-100" onclick="addAlumniEvent()"><i class="fas fa-plus"></i> Record Event</button></div>
            </div>
            <table id="alumniEventsTable" class="table table-sm"><thead><tr><th>Event</th><th>Date</th><th>Attended</th><th>Notes</th></tr></thead><tbody></tbody></table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    <button type="button" class="btn btn-primary" onclick="saveAlumni()"><i class="fas fa-save me-1"></i>Save Alumni</button>
</div>
</div>
</div>
</div>

<script>
const CSRF = '<?= $csrf ?>';
let currentAlumniId = 0;

$(document).ready(function() {
    loadAlumni();
});

var alumniTable = $('#alumniTable').DataTable({
    processing: true, serverSide: false,
    ajax: {
        url: '', type: 'POST',
        data: function(d) {
            d.action = 'list_alumni'; d.csrf_token = CSRF;
            d.membership_status = $('#filterMembership').val();
            d.program = $('#filterProgram').val();
            d.search = $('#searchAlumni').val();
        },
        dataSrc: function(j) { return j.success ? j.alumni : []; }
    },
    columns: [
        { data: 'full_name', render: d => d || '-' },
        { data: 'email', defaultContent: '-' },
        { data: 'phone', defaultContent: '-' },
        { data: 'program', defaultContent: '-' },
        { data: 'graduation_year', render: d => d || '-' },
        { data: 'current_employer', defaultContent: '-' },
        { data: 'membership_status', render: d => {
            const colors = {active:'success',inactive:'secondary',lifetime:'warning'};
            return `<span class="membership-badge bg-${colors[d]||'secondary'} text-white">${d}</span>`;
        }},
        { data: 'contribution_count', render: d => `<span class="badge bg-info">${d||0}</span>` },
        { data: null, render: d => `
            <button class="btn btn-sm btn-outline-primary btn-action" onclick="editAlumni(${d.id})" title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteAlumni(${d.id})" title="Delete"><i class="fas fa-trash"></i></button>
        `}
    ],
    order: [[0,'asc']],
    pageLength: 25,
    language: { emptyTable: 'No alumni records found. Add your first alumni!' },
    dom: 'rt<"row"<"col-sm-6"i><"col-sm-6"p>>'
});

function loadAlumni() { alumniTable.ajax.reload(); }

function openAlumniModal(data) {
    $('#alumniModalTitle').text('Add Alumni');
    $('#alumniId').val(0);
    $('#alFirstName,#alSurname,#alOtherName,#alEmail,#alPhone,#alAddress,#alProgram,#alGradClass,#alSkills,#alInterests,#alEmployer,#alPosition,#alIndustry,#alCity,#alCountry,#alLinkedin,#alBio,#alNotes').val('');
    $('#alDob,#alGradYear').val('');
    $('#alGender').val('Male');
    $('#alNationality').val('Ugandan');
    $('#alEmpStatus').val('employed');
    $('#alMembership').val('active');
    $('#alNewsletter').prop('checked', true);
    $('#contribTabLi,#jobsTabLi,#alumniEventsTabLi').hide();
    $('#contribTable tbody,#jobsTable tbody,#alumniEventsTable tbody').html('');

    if (data) {
        $('#alumniModalTitle').text('Edit Alumni');
        $('#alumniId').val(data.id);
        currentAlumniId = data.id;
        $('#alFirstName').val(data.first_name || '');
        $('#alSurname').val(data.surname || '');
        $('#alOtherName').val(data.other_name || '');
        $('#alEmail').val(data.email || '');
        $('#alPhone').val(data.phone || '');
        $('#alGender').val(data.gender || 'Other');
        $('#alDob').val(data.date_of_birth || '');
        $('#alNationality').val(data.nationality || 'Ugandan');
        $('#alAddress').val(data.address || '');
        $('#alProgram').val(data.program || '');
        $('#alGradYear').val(data.graduation_year || '');
        $('#alGradClass').val(data.graduation_class || '');
        $('#alSkills').val(data.skills || '');
        $('#alInterests').val(data.interests || '');
        $('#alEmpStatus').val(data.employment_status || 'employed');
        $('#alEmployer').val(data.current_employer || '');
        $('#alPosition').val(data.current_position || '');
        $('#alIndustry').val(data.industry || '');
        $('#alCity').val(data.location_city || '');
        $('#alCountry').val(data.location_country || 'Uganda');
        $('#alLinkedin').val(data.linkedin || '');
        $('#alBio').val(data.bio || '');
        $('#alMembership').val(data.membership_status || 'active');
        $('#alNewsletter').prop('checked', data.newsletter_optin == 1);
        $('#alNotes').val(data.notes || '');
        $('#contribTabLi,#jobsTabLi,#alumniEventsTabLi').show();
        loadContributions(data.id);
        loadJobs(data.id);
        loadAlumniEvents(data.id);
    } else {
        currentAlumniId = 0;
    }

    // Activate first tab
    $('#alumniTabs a:first').tab('show');
    new bootstrap.Modal('#alumniModal').show();
}

function editAlumni(id) {
    $.post('', { action: 'get_alumni', csrf_token: CSRF, id: id }, function(r) {
        if (r.success) openAlumniModal(r.alumni);
        else Swal.fire('Error', r.message, 'error');
    }, 'json').fail(e => Swal.fire('Error', 'Failed to load alumni', 'error'));
}

function saveAlumni() {
    const data = {
        action: $('#alumniId').val() > 0 ? 'update_alumni' : 'create_alumni',
        csrf_token: CSRF,
        id: $('#alumniId').val(),
        first_name: $('#alFirstName').val(),
        surname: $('#alSurname').val(),
        other_name: $('#alOtherName').val(),
        email: $('#alEmail').val(),
        phone: $('#alPhone').val(),
        gender: $('#alGender').val(),
        date_of_birth: $('#alDob').val(),
        nationality: $('#alNationality').val(),
        address: $('#alAddress').val(),
        program: $('#alProgram').val(),
        graduation_year: $('#alGradYear').val(),
        graduation_class: $('#alGradClass').val(),
        current_employer: $('#alEmployer').val(),
        current_position: $('#alPosition').val(),
        employment_status: $('#alEmpStatus').val(),
        industry: $('#alIndustry').val(),
        location_city: $('#alCity').val(),
        location_country: $('#alCountry').val(),
        linkedin: $('#alLinkedin').val(),
        bio: $('#alBio').val(),
        skills: $('#alSkills').val(),
        interests: $('#alInterests').val(),
        membership_status: $('#alMembership').val(),
        newsletter_optin: $('#alNewsletter').is(':checked') ? 1 : 0,
        notes: $('#alNotes').val()
    };
    if (!data.first_name || !data.surname) return Swal.fire('Required', 'First name and surname are required.', 'warning');
    $.post('', data, function(r) {
        if (r.success) {
            Swal.fire('Saved', r.message, 'success');
            bootstrap.Modal.getInstance('#alumniModal').hide();
            loadAlumni();
        } else Swal.fire('Error', r.message, 'error');
    }, 'json').fail(e => Swal.fire('Error', 'Save failed', 'error'));
}

function deleteAlumni(id) {
    Swal.fire({
        title: 'Delete Alumni Record?',
        text: 'This will remove all associated contributions, jobs, and events.',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post('', { action: 'delete_alumni', csrf_token: CSRF, id: id }, function(r) {
            if (r.success) { Swal.fire('Deleted', r.message, 'success'); loadAlumni(); }
            else Swal.fire('Error', r.message, 'error');
        }, 'json');
    });
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Contributions Ã¢â€â‚¬Ã¢â€â‚¬
function loadContributions(alumniId) {
    $.post('', { action: 'list_contributions', csrf_token: CSRF, alumni_id: alumniId }, function(r) {
        if (!r.success) return;
        let h = '';
        r.contributions.forEach(c => {
            h += `<tr><td>${c.contribution_date||'-'}</td><td>${c.contribution_type}</td><td>${c.amount}</td><td>${c.currency}</td><td>${c.description||'-'}</td><td>${c.payment_method||'-'}</td></tr>`;
        });
        $('#contribTable tbody').html(h || '<tr><td colspan="6" class="text-muted text-center">No contributions yet.</td></tr>');
    }, 'json');
}

function addContribution() {
    const id = $('#alumniId').val();
    if (!id || id <= 0) return Swal.fire('Error', 'Save the alumni record first.', 'warning');
    $.post('', {
        action: 'add_contribution', csrf_token: CSRF,
        alumni_id: id,
        contribution_type: $('#contribType').val() || 'donation',
        amount: $('#contribAmount').val() || 0,
        currency: $('#contribCurrency').val(),
        contribution_date: $('#contribDate').val() || new Date().toISOString().split('T')[0],
        description: $('#contribDesc').val()
    }, function(r) {
        if (r.success) { $('#contribType,#contribAmount,#contribDesc').val(''); loadContributions(id); }
        else Swal.fire('Error', r.message, 'error');
    }, 'json');
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Jobs Ã¢â€â‚¬Ã¢â€â‚¬
function loadJobs(alumniId) {
    $.post('', { action: 'list_jobs', csrf_token: CSRF, alumni_id: alumniId }, function(r) {
        if (!r.success) return;
        let h = '';
        r.jobs.forEach(j => {
            h += `<tr><td>${j.company}</td><td>${j.position}</td><td>${j.start_date||'-'}</td><td>${j.end_date||'-'}</td><td>${j.is_current?'<span class="badge bg-success">Current</span>':'-'}</td><td>${j.description||'-'}</td></tr>`;
        });
        $('#jobsTable tbody').html(h || '<tr><td colspan="6" class="text-muted text-center">No job history.</td></tr>');
    }, 'json');
}

function addJob() {
    const id = $('#alumniId').val();
    if (!id || id <= 0) return Swal.fire('Error', 'Save the alumni record first.', 'warning');
    const company = $('#jobCompany').val();
    const position = $('#jobPosition').val();
    if (!company || !position) return Swal.fire('Required', 'Company and position are required.', 'warning');
    $.post('', {
        action: 'add_job', csrf_token: CSRF,
        alumni_id: id, company: company, position: position,
        start_date: $('#jobStart').val(), end_date: $('#jobEnd').val(),
        is_current: $('#jobCurrent').is(':checked') ? 1 : 0,
        description: $('#jobDesc').val()
    }, function(r) {
        if (r.success) { $('#jobCompany,#jobPosition,#jobStart,#jobEnd,#jobDesc').val(''); $('#jobCurrent').prop('checked', false); loadJobs(id); }
        else Swal.fire('Error', r.message, 'error');
    }, 'json');
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Alumni Events Ã¢â€â‚¬Ã¢â€â‚¬
function loadAlumniEvents(alumniId) {
    $.post('', { action: 'list_alumni_events', csrf_token: CSRF, alumni_id: alumniId }, function(r) {
        if (!r.success) return;
        let h = '';
        r.events.forEach(e => {
            h += `<tr><td>${e.event_name}</td><td>${e.event_date||'-'}</td><td>${e.attended?'<span class="badge bg-success">Yes</span>':'<span class="badge bg-secondary">No</span>'}</td><td>${e.notes||'-'}</td></tr>`;
        });
        $('#alumniEventsTable tbody').html(h || '<tr><td colspan="4" class="text-muted text-center">No events recorded.</td></tr>');
    }, 'json');
}

function addAlumniEvent() {
    const id = $('#alumniId').val();
    if (!id || id <= 0) return Swal.fire('Error', 'Save the alumni record first.', 'warning');
    const name = $('#alEventName').val();
    if (!name) return Swal.fire('Required', 'Event name is required.', 'warning');
    $.post('', {
        action: 'add_alumni_event', csrf_token: CSRF,
        alumni_id: id, event_name: name,
        event_date: $('#alEventDate').val(),
        attended: $('#alEventAttended').is(':checked') ? 1 : 0
    }, function(r) {
        if (r.success) { $('#alEventName,#alEventDate').val(''); $('#alEventAttended').prop('checked', false); loadAlumniEvents(id); }
        else Swal.fire('Error', r.message, 'error');
    }, 'json');
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
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/enterprise_control_panel.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
